<?php

// written by sqall
// twitter: https://twitter.com/sqall01
// blog: http://blog.h4des.org
// 
// Licensed under the GNU Public License, version 2.
//
// modified for PDO by Mario Wicke
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
// if not show the device selection
if(!isset($_GET['mode'])) {

	// get all tracking devices
	$sql = "SELECT DISTINCT
				name
			FROM 
				$mysql_table";
	$query = $db->prepare($sql);
	$parameters = array(
					':mysql_table' => $mysql_table
					);
	$query -> execute($parameters);
	//$query = $db -> selectOne($sql);

	echo '<table border="0">';
	echo '<tr>';
	echo '<td>';
	echo '<b>Tracking Device:</b>';
	echo '</td>';
	echo '</tr>';
	// create list of tracking devices
	if($query->errorCode() == 0) {
		while(($row = $query->fetch(FETCH)) != false) {
			echo '<tr>';
			echo '<td>';
			echo '<a href="get.php?mode=timeselect&trackingdevice=' . htmlentities($row["name"], ENT_QUOTES) . '">';
			echo htmlentities($row["name"], ENT_QUOTES);
			echo '</a>';
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
	}

}
else {
	switch($_GET['mode']) {
		// in this case the user can select the time frame of the gps data
		case "timeselect":

			// check if trackingdevice is set
			if(!isset($_GET['trackingdevice'])) {
				echo "no trackingdevice selected";
				exit(1);
			}

			// get first entry
			$sql = "SELECT 
						*
					FROM 
						$mysql_table 
					WHERE 
						name = :trackingdevice 
					ORDER BY 
						utctime 
					ASC LIMIT
						1";			
			$query = $db->prepare($sql);
			$parameters = array(
								':trackingdevice' => $_GET["trackingdevice"]
								);
			$query -> execute($parameters);
			$result = $query->fetch(FETCH);
			$firstentry = $result['utctime'];

			// get last entry
			$sql = "SELECT 
						*
					FROM 
						$mysql_table 
					WHERE 
						name = :trackingdevice 
					ORDER BY 
						utctime 
					DESC LIMIT
						1";			
			$query = $db->prepare($sql);
			$parameters = array(
								':trackingdevice' => $_GET["trackingdevice"]
								);
			$query -> execute($parameters);
			$result = $query->fetch(FETCH);
			$lastentry = $result["utctime"];

			// create time array of first and last entry
			$firstentryarray = getdate($firstentry);
			$lastentryarray = getdate($lastentry);

			// create form for time selection
			echo '<form action="get.php" method="get">';

			// give time from the day of the firstentry 00:00h until day of the lastentry 00:00h for selection
			echo '<label>Start time:</label><br />';
			echo '<select name="starttime">';
			for($i = mktime(0, 0, 0, $firstentryarray["mon"], $firstentryarray["mday"], $firstentryarray["year"]); $i <= mktime(0, 0, 0, $lastentryarray["mon"], $lastentryarray["mday"], $lastentryarray["year"]);) {
				echo '<option value="' . $i . '">' 
					. date("d.m.Y - H:i:s", $i) . '</option>';
				// increment by 1 day
				$i = strtotime("+1 day", $i);
			}
			echo '</select>';

			echo '<hr />';

			// give time from the day of the firstentry 23:59h 
			// until day of the lastentry 23:59h for selection
			echo '<label>End time:</label><br />';
			echo '<select name="endtime">';
			for($i = mktime(23, 59, 59, $firstentryarray["mon"], 
				$firstentryarray["mday"], $firstentryarray["year"]); 
				$i <= mktime(23, 59, 59, $lastentryarray["mon"], 
					$lastentryarray["mday"], $lastentryarray["year"]);) {

				echo '<option value="' . $i . '">' 
					. date("d.m.Y - H:i:s", $i) . '</option>';
				// increment by 1 day
				$i = strtotime("+1 day", $i);
			}
			echo '</select>';

			echo '<hr />';

			echo '<input type="hidden" name="trackingdevice" value="' 
				. htmlentities($_GET["trackingdevice"], ENT_QUOTES) . '" />';
			echo '<input type="hidden" name="mode" value="show" />';

			echo '<input type="submit" value="enter" />';
			echo '<input type="reset" value="clear" />';
			echo '</form>';

			break;

		// in this case the selected data is shown
		case "show":

			// check if trackingdevice is set
			if(!isset($_GET['trackingdevice'])) {
				echo "no trackingdevice selected";
				exit(1);
			}

			// check if starttime is set
			if(!isset($_GET['starttime'])) {
				echo "no starttime selected";
				exit(1);
			}

			// check if endtime is set
			if(!isset($_GET['endtime'])) {
				echo "no endtime selected";
				exit(1);
			}

			$starttime = doubleval($_GET['starttime']);
			$endtime = doubleval($_GET['endtime']);

			echo '<a href="get.php">back</a><br />';
			echo '<a href="./show_map.php?mode=livetracking&trackingdevice='
			. htmlentities($_GET["trackingdevice"], ENT_QUOTES) . '
			" target="_blank">live tracking of "'
			. htmlentities($_GET["trackingdevice"], ENT_QUOTES) 
			. '"</a><br />';

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
			//$result = $query->fetch(FETCH);

			$lasttime = 0.0;
			$tracks = array();
			$count = 0;

			// extract all tracks from gps data
			$first_entry = True;
			while($row = $query->fetch(FETCH)) {
				// start new track when position has not changed for 30 minutes
				if(($row["utctime"]-$lasttime) >= 1800) {

					// check if it is the first gps entry
					if($first_entry) {
						$first_entry = False;
						$starttime = $row["utctime"];
					}
					else {
						// set endtime and add data to array
						$endtime = $lasttime;
						array_push($tracks, array("starttime" => $starttime, 
							"endtime" => $endtime, 
							"count" => $count));

						// reset starttime and count
						$starttime = $row["utctime"];
						$count = 0;
					}
				}
				$lasttime = $row["utctime"];
				$count++;
			}
			$endtime = $lasttime;
			// add last track to array
			array_push($tracks, array("starttime" => $starttime, 
				"endtime" => $endtime, 
				"count" => $count));

			// output gps tracks
			echo "<table>";
			echo "<tr>";

			echo '<td width="250">';
			echo "<b>";
			echo "starttime (UTC)";
			echo "</b>";
			echo "</td>";

			echo '<td width="250">';
			echo "<b>";
			echo "endtime (UTC)";
			echo "</b>";
			echo "</td>";

			echo '<td width="150">';
			echo "<b>";
			echo "gps points";
			echo "</b>";
			echo "</td>";

			echo '<td width="350">';
			echo "<b>";
			echo "action";
			echo "</b>";
			echo "</td>";			

			echo "</tr>";

			for($i=0; $i<count($tracks); $i++) {
				echo "<tr>";

				echo "<td>";
				echo date("d.m.Y - H:i:s", $tracks[$i]["starttime"]);
				echo "</td>";

				echo "<td>";
				echo date("d.m.Y - H:i:s", $tracks[$i]["endtime"]);
				echo "</td>";				

				echo "<td>";
				echo $tracks[$i]["count"];
				echo "</td>";

				echo "<td>";
				echo '<a href="./show_map.php?mode=view&trackingdevice='
					. htmlentities($_GET["trackingdevice"], ENT_QUOTES) 
					. '&starttime='
					. $tracks[$i]["starttime"] . '&endtime=' 
					. $tracks[$i]["endtime"]
					. '" target="_blank">';
				echo "show on map";
				echo "</a>";
				echo " || ";
				echo '<a href="get.php?mode=gpx&trackingdevice='
					. htmlentities($_GET["trackingdevice"], ENT_QUOTES) 
					. '&starttime='
					. $tracks[$i]["starttime"] . '&endtime=' 
					. $tracks[$i]["endtime"]
					. '">';
				echo "gpx download";
				echo "</a>";
				echo "</td>";				

				echo "</tr>";
			}

			echo "</table>";

			break;

		// in this case the selected timeframe will be exported as a gpx file
		case "gpx":

			// check if trackingdevice is set
			if(!isset($_GET['trackingdevice'])) {
				echo "no trackingdevice selected";
				exit(1);
			}

			// check if starttime is set
			if(!isset($_GET['starttime'])) {
				echo "no starttime selected";
				exit(1);
			}

			// check if endtime is set
			if(!isset($_GET['endtime'])) {
				echo "no endtime selected";
				exit(1);
			}

			$starttime = doubleval($_GET['starttime']);
			$endtime = doubleval($_GET['endtime']);

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

			// set header for download
			header("Cache-Control: public");
			header("Content-Description: File Transfer");
			header("Content-Type: application/octet-stream;"); 
			header("Content-Transfer-Encoding: binary");
			header("Content-Disposition: attachment; filename="
				. htmlentities($_GET["trackingdevice"], ENT_QUOTES)
				. "_-_" . date("d.m.Y_H:i:s", $starttime)
				. "_-_" . date("d.m.Y_H:i:s", $endtime)  . ".gpx");

			$lasttime = 0.0;
			$tracknr = 1;

			// create gpx file
			// https://de.wikipedia.org/wiki/GPS_Exchange_Format
			echo '<gpx version="1.1" creator="h4des.org GPS Tracking System">';
			echo "\n";
			echo '<metadata>';
			echo "\n";
			echo '</metadata>';
			echo "\n";

			while($row = $query->fetch(FETCH)) {
				// start new track when position has not changed for 30 minutes
				if(($row["utctime"]-$lasttime) >= 1800) {

					// check if not first track
					if($tracknr !== 1) { 
						// end old track
						echo '</trkseg>';
						echo "\n";
				  		echo '</trk>';
						echo "\n";
					}

					// start new track
					echo '<trk>';
					echo "\n";
					echo '<name>';
					echo "\n";
					echo htmlentities($_GET["trackingdevice"], ENT_QUOTES);
					echo " - Track $tracknr - ";
					echo date("d.m.Y/H:i:s", $row["utctime"]);
					echo "\n";
					echo '</name>';
					echo "\n";
					echo '<trkseg>';
					echo "\n";

					$tracknr++;
				}

				echo '<trkpt lat="' . $row["latitude"] . '" lon="' 
					. $row["longitude"] . '">';
				echo "\n";
				echo '<ele>' . $row["altitude"] . '</ele>';
				echo "\n";
				echo '<time>' . $row["utctime"] . '</time>';
				echo "\n";
				echo '</trkpt>';
				echo "\n";
				$lasttime = $row["utctime"];
			}
			echo '</trkseg>';
			echo "\n";
		  	echo '</trk>';
			echo "\n";
			echo '</gpx>';

			break;

		default:
			echo "mode does not exist";
			exit(1);
	}
}
?>