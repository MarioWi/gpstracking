<?php

// written by sqall
// twitter: https://twitter.com/sqall01
// blog: http://blog.h4des.org
// 
// Licensed under the GNU Public License, version 2.
//
// converted for use wis PDO by MarioWi
// 

// include connection data for mysql db
require_once('../inc/def/def.php');

// load additional classes
//function __autoload($class){
//	require '../inc/class/'.$class.'.class.php';
//}

//$db = new Db($options, $attributes);

$db = new PDO(SERVER, USER, PW);
foreach ($attributes as $key => $value) {
	$db -> setAttribute($value[0], $value[1]);
}
$db->exec('USE '.$database);

// check if mode is set
if(!isset($_GET['mode'])) {
	echo "no mode selected";
	exit(1);
}


switch($_GET['mode']) {
	// in this case only a point newer than a given time is outputed
	case "livetracking":

		// check if needed data is given
		if(isset($_GET['lasttime']) 
			&& isset($_GET["trackingdevice"])) {

			$lastTime = doubleval($_GET['lasttime']);

			// get newest point (if exists)
			$sql = "SELECT 
						* 
					FROM 
						$mysql_table 
					WHERE 
						utctime > :lastTime 
					AND 
						name = :trackingdevice 
					ORDER BY 
						utctime
					DESC LIMIT 1";
			$query = $db->prepare($sql);
			$parameters = array(
								'lastTime'         => $lastTime,
								':trackingdevice' => $_GET["trackingdevice"]
								);
			$query -> execute($parameters);
			$row = $query->fetch(FETCH);

			// check if no newer entry exists
			if($row == null) {
				// output empty json objects
				header('Content-type: application/json');
				echo "{}";
			}
			else {
				// get only values that should be outputed
				$output = array("name" => $row["name"],
					"utctime" => $row["utctime"],
					"latitude" => $row["latitude"],
					"longitude" => $row["longitude"],
					"altitude" => $row["altitude"],
					"speed" => $row["speed"]);

				// output entry as a json object
				header('Content-type: application/json');
				echo json_encode($output);
			}
		}
		else {
			echo '"lasttime" or "trackingdevice" not given';
			exit(1);
		}
		break;

	// in this case all gps entries will be given back 
	// within a specific time window
	case "view":

		// check if needed data is given
		if(isset($_GET["trackingdevice"])
			&& isset($_GET["starttime"])
			&& isset($_GET["endtime"])) {

			$starttime = doubleval($_GET['starttime']);
			$endtime = doubleval($_GET['endtime']);

			// get all entries within the given time frame
			$sql = "SELECT 
						* 
					FROM 
						$mysql_table 
					WHERE 
						utctime <= :endtime 
					AND 
						utctime >= :starttime 
					AND 
						name = :trackingdevice 
					ORDER BY 
						utctime
					ASC";
			$query = $db->prepare($sql);
			$parameters = array(
								'endtime'         => $endtime,
								':starttime'      => $starttime,
								':trackingdevice' => $_GET["trackingdevice"]
								);
			$query -> execute($parameters);

			// generate output array that contains all gps entries
			$output = array();
			while($row = $query->fetch(FETCH)) {
				// get only values that should be outputed
				$entry = array("name" => $row["name"],
					"utctime" => $row["utctime"],
					"latitude" => $row["latitude"],
					"longitude" => $row["longitude"],
					"altitude" => $row["altitude"],
					"speed" => $row["speed"]);

				array_push($output, $entry);
			}

			// output array as a json object
			header('Content-type: application/json');
			echo json_encode($output);
		}

		break;

	// mode is unknown
	default:
		echo "mode does not exist";
		exit(1);
}

?>