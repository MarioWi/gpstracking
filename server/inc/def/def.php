<?php
// DB-Data
    //define('SERVER', 'mysql:host=db;dbname=gpstracking');
    define('SERVER', 'mysql:host=db;');
    define('USER', 'gpstracking');
    define('PW', 'myBADsecret');
    define('FETCH', PDO::FETCH_ASSOC);

    $options    = array (
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
                        );
    $attributes = array (
                        'case'  => array(PDO::ATTR_CASE, PDO::CASE_NATURAL),
                        'error' => array(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION)
                        );

    $database    = 'gpstracking';                 
    $mysql_table = 'gpsdata';
                        
// other
    date_default_timezone_set("UTC");
?>