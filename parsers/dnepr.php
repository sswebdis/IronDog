#!/usr/bin/php
<?php
/**
 * central file for parsing dnipro irondogs 
 * just start it from command line.
 * if need another database configuration, look into database.php file
 * 
 * @uses data.php array with train base info
 * @uses parser_functions.php main functions
 */

ini_set('default_charset', "utf-8");

require 'parser_functions.php';

$inputs = require 'data.php';

$stations = array();

$trainUrl = 'http://www.dp.uz.gov.ua/ukr/timetable/search_direction';
$stationUrl = 'http://www.dp.uz.gov.ua/ukr/timetable/search/';

$count = 0;
foreach ($inputs as $way => $trains) {
    foreach ($trains as $id => $text) {

        print "Start process #$id train ($way)\n";

        $id_win = iconv("utf-8", "windows-1251", $id);

        $res = processCurl('http://www.dp.uz.gov.ua/ukr/timetable/search_numbertrain/' . htmlspecialchars($id));

        $data = getTrainData($res);

        saveData($data);

        print "Processed!\n";

        $count++;
        print "$count trains parsed\n";
    }
}

?>