<?php

// written by sqall
// twitter: https://twitter.com/sqall01
// blog: http://blog.h4des.org
// 
// Licensed under the GNU Public License, version 2.
//
// converted for use wis PDO by MarioWi
// 

function add_data_to_db($utctime, $latitude, $longitude, $altitude, $speed) {

	// include connection data for mysql db and some other definitons
	require_once('../inc/def/def.php');

	// load additional classes
	//function __autoload($class){
	//	require '../inc/class/'.$class.'.class.php';
	//}

	// create db-object (connection to db)
	//$db = new Db($options, $attributes);
	$db = new PDO(SERVER, USER, PW);
	foreach ($attributes as $key => $value) {
		$db -> setAttribute($value[0], $value[1]);
	}

	$mysql_insert_query = " INSERT INTO $mysql_table (name, utctime, 
							latitude, longitude, altitude, speed)
							VALUES 
							(:USER, :utctime, :latitude, :longitude, :altitude, 
							:speed);";
	$mysql_select_query = "SELECT * FROM $mysql_table WHERE name= 
		 :USER 
		 AND
		 utctime=:utctime";

	if($db) {
		// use mysql database
		try{
			$db->exec('USE '.$database);
		}
		catch(Exception $e){
			// return 4 for "error mysql_select_db"
			return 4;
		}
		// get data to check duplicate entries
		$query = $db->prepare($mysql_select_query);
		$parameters = array(
							':USER'        => $_SERVER['PHP_AUTH_USER'],
							':utctime'     => $utctime
							);
		$query -> execute($parameters);
		$row = $query->rowCount();
		//if($row !== 1) {
		if($row > 1) {
			// return 5 for "duplicate entries"
			return 5;
		}

		// insert data
		try{
			$query = $db->prepare($mysql_insert_query);
			$parameters = array(
								':USER'        => $_SERVER['PHP_AUTH_USER'],
								':utctime'     => $utctime,
								':latitude'    => $latitude,
								':longitude'   => $longitude,
								':altitude'    => $altitude,
								':speed'       => $speed
								);
			$query -> execute($parameters);
		} 
		catch(Exception $e) {
			// return 3 for "error mysql_query"
			return 3;
		}

		// print ok for the client
		// return 0 for "ok"
		return 0;
		}
	else {
		// print error message for client
		// return 1 for "error mysql_connect"
		return 1;
	}
}


if(isset($_SERVER['PHP_AUTH_USER']) 
	&& isset($_POST['latitude'])
	&& isset($_POST['longitude'])
	&& isset($_POST['utctime'])
	&& isset($_POST['altitude'])
	&& isset($_POST['speed'])) {

	// set initial error code
	$error_code = -1;

	// check if more than only one gps point is transmitted
	if(is_array($_POST['latitude'])
		&& is_array($_POST['longitude'])
		&& is_array($_POST['utctime'])
		&& is_array($_POST['altitude'])
		&& is_array($_POST['speed'])) {

		// check if all arrays have the same length
		if(count($_POST['latitude']) === count($_POST['longitude'])
			&& count($_POST['latitude']) === count($_POST['utctime'])
			&& count($_POST['latitude']) === count($_POST['altitude'])
			&& count($_POST['latitude']) === count($_POST['speed'])) {

			// add all POST data to db
			for($i = 0; $i < count($_POST['latitude']); $i++) {

				// sanitize POST data
				// Latitude in degrees: +/- signifies West/East
				$latitude = doubleval($_POST['latitude'][$i]);
				// Longitude in degrees: +/- signifies North/South
				$longitude = doubleval($_POST['longitude'][$i]);
				$utctime = doubleval($_POST['utctime'][$i]);
				$altitude = doubleval($_POST['altitude'][$i]);
				$speed = doubleval($_POST['speed'][$i]);

				// add data to db
				$error_code = add_data_to_db($utctime, $latitude, 
					$longitude, $altitude, $speed);

				// ignore error code for "ok" and "duplicate entries"
				if($error_code != 0
					&& $error_code != 5) {
					break;
				}
			}
		}
		else {
			// error code for "error arrays_not_same_length"
			$error_code = 6;
		}
	}
	else {
		// sanitize POST data
		$latitude = doubleval($_POST['latitude']);
		$longitude = doubleval($_POST['longitude']);
		$utctime = doubleval($_POST['utctime']);
		$altitude = doubleval($_POST['altitude']);
		$speed = doubleval($_POST['speed']);

		// add data to db
		$error_code = add_data_to_db($utctime, $latitude, $longitude, 
			$altitude, $speed);
	}

	// print error message
	switch($error_code) {
		case 0:
			echo "ok";
			break;
		case 1:
			echo "error mysql_connect";
			break;
		case 2:
			// not needed anymore 
			// connection will be closed automaticly by PDO
			echo "error mysql_close";
			break;
		case 3:
			echo "error mysql_query";
			break;
		case 4:
			echo "error mysql_select_db";
			break;
		case 5:
			echo "duplicate entries";
			break;
		case 6:
			echo "error arrays_not_same_length";
			break;
		}
}
else {
	echo "error data missing";
}

?>
