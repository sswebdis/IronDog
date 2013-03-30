<?php
/**
 * create $db object and provide PDO access to target database
 */
$db = new PDO('pgsql:host=localhost;dbname=irondog', 'irondog', 'irondog');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
