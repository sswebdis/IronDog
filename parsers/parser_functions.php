<?php
/**
 * functions for dnepr.php
 * parse and save info from dp.uz.gov.ua
 */

/**
 * get target url content.
 * 
 * @param string $url url for download
 * 
 * @return string $data loaded data
 */
function processCurl($url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($curl);
    curl_close($curl);

    $data = iconv("windows-1251","utf-8", $data);

    return $data;
}
/**
 * parse html from http://www.dp.uz.gov.ua/ukr/timetable/search_numbertrain/
 * table fields: 
 *      № Поїзда/обіг
 *      Термін дії
 *      Станція
 *      приб.
 *      відпр.
 *      км
 * 
 * @param string $data curled html
 * 
 * @return array $result array with parsed data
 */
function getTrainData($data)
{
    $data = preg_replace('/\s+/', ' ', $data);

    //get first table tag content
    preg_match('/<div[^>]*?(?!class="firstsearch")[^>]*?><table[^>]*?>(.*?)<\/table>/u', $data, $output);

    $info = $output[1];

    //split by tr tags
    preg_match_all('/<tr[^>]*?>(.*?)<\/tr>/u', $info, $output, PREG_PATTERN_ORDER);

    $info = array();
    foreach($output[1] as $key => $tr) {
        //split by td tags
        preg_match_all('/<td[^>]*?>(.*?)<\/td>/u', $tr, $tds, PREG_PATTERN_ORDER);

        $rawResult[$key] = array();
        foreach($tds[1] as $td) {
            $rawResult[$key][] = $td;
        }
    }

    $result = array('stations' => array());
    
    //get td content from splitted tags
    //if count td == 2 this is top fields
    //if count td == 5 this is station about content
    foreach($rawResult as $lines) {
        if (count($lines) == 1) {
            break;
        }
        if (count($lines) == 2) {
            if (strpos($lines[0], '/')) {
                $headers = explode('/', strip_tags($lines[0]));

                //split (train_id/periodical) cell
                preg_match('/((?:\(?\d+?\)?\/?)+)\/(.*)$/', $lines[1], $temp);

                $datas = array(
                    0 => $temp[1],
                    1 => $temp[2],
                );

                $result[trim($headers[0])] = trim($datas[0]);
                $result[trim($headers[1])] = trim($datas[1]);
            }
            else {
                $result[trim(strip_tags($lines[0]))] = trim(strip_tags($lines[1]));
            }

        }
        elseif(count($lines) == 5) {
            if ($lines[1] == 'Станція') {//skip stations header
                continue;
            }
            $station = array();
            foreach($lines as $key => $item) {
                if ($key == 0 || $key == 4) {//skip not needed fields
                    continue;
                }

                $temp = trim(strip_tags($item));

                if (empty($temp)) {
                    continue;
                }

                $stKey = $key == 1 ? 'name'
                    : ($key == 2 ? 'arrival'
                    : 'dispatch');//key == 3
                $station[$stKey] = $temp;
            }
            //fix times (was finded bug in their system: sometimes wrong time format)
            if (!isset($station['arrival']) || !preg_match('/\d?\d\.\d\d/', $station['arrival'], $temp)) {
                $station['arrival'] = '-';
            }
            else {
                $station['arrival'] = $temp[0];
            }

            if (!isset($station['dispatch']) || !preg_match('/\d?\d\.\d\d/', $station['dispatch'], $temp)) {
                $station['dispatch'] = '-';
            }
            else {
                $station['dispatch'] = $temp[0];
            }
            $result['stations'][] = $station;
        }
    }

    return $result;
}

/**
 * save parsed data
 * 
 * @param string $data data for saving
 * 
 * @return bool is file saved
 */
function saveData($data)
{
    //init $db var - PDO object for database
    require 'database.php';

    $number = $data['№ Поїзда'];

    //check if train record exist
    $id_train = $db->prepare("SELECT id FROM trains WHERE formal_id = :formal_id AND periodical = :periodical");
    $id_train->bindValue(':formal_id', $number, PDO::PARAM_STR);
    $id_train->bindValue(':periodical', $data['обіг'], PDO::PARAM_STR);
    $id_train->execute();
    $id_train = $id_train->fetch();
    $id_train = empty($id_train) ? 0 : $id_train[0];

    //get only end date for schedule
    $actual_to = trim(mb_substr($data['Термін дії'], mb_strpos($data['Термін дії'], '-') + 1));

    //fix date format
    $actual_to = date_create_from_format('d.m.Y', $actual_to);
    $actual_to = date_format($actual_to, 'Y-m-d');

    //save train data
    $sql = $id_train 
        ? "UPDATE trains SET actual_to = :actual_to, periodical = :periodical WHERE id = :id_train RETURNING id"
        : "INSERT INTO trains (formal_id, actual_to, periodical) VALUES (:formal_id, :actual_to, :periodical) RETURNING id";

    $train = $db->prepare($sql);

    if ($id_train) {
        $train->bindValue(':id_train', $id_train, PDO::PARAM_INT);
    }
    else {
        $train->bindValue(':formal_id', $number, PDO::PARAM_STR);        
    }
    $train->bindValue(':actual_to', $actual_to, PDO::PARAM_STR);
    $train->bindValue(':periodical', $data['обіг'], PDO::PARAM_STR);
    $train->execute();

    $id_train = $train->fetch();
    $id_train = $id_train[0];
    $train = null;

    $id_station = 0; //end station id, temp id in foreach
    $id_start_station = 0; //start station id

    foreach($data['stations'] as $station) {
        $id_station = $db->prepare("SELECT id FROM stations WHERE name = :name");
        $id_station->bindValue(':name', $station['name'], PDO::PARAM_STR);
        $id_station->execute();

        $id_station = $id_station->fetch();

        if (!is_array($id_station)) {
            $id_station = $db->prepare("INSERT INTO stations(name) VALUES(:name) RETURNING id");
            $id_station->bindValue(':name', $station['name']);
            $id_station->execute();

            $id_station = $id_station->fetch();
        }

        $id_station = $id_station[0];

        if (empty($id_start_station)) {
            $id_start_station = $id_station;
        }


        if ($station['arrival'] != '-') {
            $station['arrival'] = date_create_from_format('G.i', $station['arrival']);
            $station['arrival'] = date_format($station['arrival'], 'H:i:s');
        }
        else {
            $station['arrival'] = null;
        }

        if ($station['dispatch'] != '-') {
            $station['dispatch'] = date_create_from_format('G.i', $station['dispatch']);
            $station['dispatch'] = date_format($station['dispatch'], 'H:i:s');
        }
        else {
            $station['dispatch'] = null;
        }

        //skip record without timeset
        if ($station['arrival'] == null && $station['dispatch'] == null) {
            continue;
        }

        $statement = $db->prepare("SELECT 1 FROM schedule WHERE id_train = :id_train AND id_station = :id_station AND (arrival = :arrival OR dispatch = :dispatch) ");
        $statement->bindValue(':id_train', $id_train, PDO::PARAM_INT);
        $statement->bindValue(':id_station', $id_station, PDO::PARAM_INT);
        $statement->bindValue(':arrival', $station['arrival'], PDO::PARAM_STR);
        $statement->bindValue(':dispatch', $station['dispatch'], PDO::PARAM_STR);
        $statement->execute();
        $times = $statement->fetch();

        //skip existing records
        if ($times) {
            continue;
        }

        $statement = $db->prepare("INSERT INTO schedule VALUES(:id_train, :id_station, :arrival, :dispatch)");
        $statement->bindValue(':id_train', $id_train, PDO::PARAM_INT);
        $statement->bindValue(':id_station', $id_station, PDO::PARAM_INT);
        $statement->bindValue(':arrival', $station['arrival'], PDO::PARAM_STR);
        $statement->bindValue(':dispatch', $station['dispatch'], PDO::PARAM_STR);
        $statement->execute();
    }

    //save first and last stations
    $statement = $db->prepare("SELECT 1 FROM ways WHERE id_train = :id_train AND id_start_station = :id_start_station AND id_end_station = :id_end_station");
    $statement->bindValue(':id_train', $id_train, PDO::PARAM_INT);
    $statement->bindValue(':id_start_station', $id_start_station, PDO::PARAM_INT);
    $statement->bindValue(':id_end_station', $id_station, PDO::PARAM_INT);
    $statement->execute();
    $exist = $statement->fetch();

    if (!isset($exist[0])) {
        $statement = $db->prepare("INSERT INTO ways VALUES(:id_train, :id_start_station, :id_end_station)");
        $statement->bindValue(':id_train', $id_train, PDO::PARAM_INT);
        $statement->bindValue(':id_start_station', $id_start_station, PDO::PARAM_INT);
        $statement->bindValue(':id_end_station', $id_station, PDO::PARAM_INT);
        $statement->execute();
    }
    //close database connection
    $db = null;
}