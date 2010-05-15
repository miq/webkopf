<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="de" xml:lang="en">

<head>
<title>Webkopf - Neues Spiel Eintragen</title>

<link rel="stylesheet" type="text/css" href="game_input_style.css" />
<link rel="stylesheet" type="text/css" href="global_style.css" />
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"/>
</head>
<body>
<div class="controlbar">
<a href="index.php">&Uuml;bersichtsseite</a>
<a href="stats.php">Spielerstatistiken</a>
<a href="team_stats.php">Teamstatistiken</a>
</div>
<?
require_once("db.php");

define("CHARLIE_NOTHING", 0);
define("CHARLIE_POINT", 1);
define("CHARLIE_CAUGHT", 2);
define("CHARLIE_LOST", 3);
define("CHARLIE_POINT_CAUGHT", 4);
define("CHARLIE_TWO_CAUGHT", 5);

define("FOX_NOTHING", 0);
define("FOX_CAUGHT", 1);
define("FOX_LOST", 2);
define("FOX_LOST_CAUGHT", 3);
define("FOX_TWO_LOST", 4);
define("FOX_TWO_CAUGHT", 5);

$result=pg_exec("select * from players");

while ($row = pg_fetch_array($result)) {
	$users[] = $row["full_name"];
	$users_id[] = $row["id"];
}

$result=pg_exec("select * from game_types");
while ($row = pg_fetch_array($result)) {
	$game_types[] = $row["type"];
	$game_types_id[] = $row["id"];
}


$result=pg_exec("select * from announcements");
while($row = pg_fetch_array($result)) {
	$announcements_text[] = $row["text"];
	$announcements_id[] = $row["id"];
	$announcements_type[] = $row["type"];
}


$players = $_POST['playercount'];
$location = $_POST['locality'];
$game_date = $_POST['date'];
$game_count = $_POST['game_count'];
$game_round = 0;
$game_position = 0;

function getGamePropertiesID($row) {
	$queryString = "SELECT id FROM game_properties WHERE
		schweine='$row[schweine]' AND 
		hochzeit='$row[hochzeit]' AND 
		armut='$row[armut]' AND
		charlie_caught='$row[charlie_caught]' AND
		charlie_lost='$row[charlie_lost]' AND 
		charlie_won='$row[charlie_won]' AND 
		schweine_caught='$row[schweine_caught]' AND
		schweine_lost='$row[schweine_lost]' AND
		fuechse_caught='$row[fuechse_caught]' AND 
		fuechse_lost='$row[fuechse_lost]' AND 
		doppelkopf='$row[doppelkopf]' AND
		announcement='$row[announcement]'";
	$result = pg_exec($queryString);
	if ($result_row = pg_fetch_array($result)) {
		$id = $result_row["id"];
	} else {
		$insertString = "INSERT INTO game_properties VALUES (
			'$row[schweine]', '$row[hochzeit]',
			'$row[armut]', '$row[charlie_caught]',
			'$row[charlie_lost]', '$row[charlie_won]',
			'$row[schweine_caught]', '$row[schweine_lost]', 
			'$row[fuechse_caught]', '$row[fuechse_lost]', 
			'$row[doppelkopf]', '$row[announcement]')";
		pg_exec($insertString);
		$result = pg_exec($queryString);
		$result_row = pg_fetch_array($result);
		$id = $result_row["id"];
	}
	return $id;
}

function insertGame($match_id, $game, $game_position, $game_round, $player, $repunkte, $won, $max_players) {
 // schreibe spiel in db:
	global $location, $users_id, $game_date, $announcements_type, $announcements_id;
	$kp0 = $_POST["kp$game"];  
	$announcements = $_POST["call$game$player"];
	for ($j = 0; $j < count($announcements_type); $j++) {
		if ($announcements_type[$j] == $announcements) {
			$call_id = $announcements_id[$j];
		}
	}
	
  	if ($_POST["re$game$player"] == "on") {
		$player_points = $repunkte;
		$card_points = $kp0;
		$re = 1;
		$contra = 0;
	} else {
		$player_points = (-1) * $repunkte;
		$card_points = 240 - $kp0;
		$re = 0;
		$contra = 1;
	}
	$charlie_won=0;
	$charlie_lost = 0;
	$charlie_caught = 0;
	if ($_POST["charlie$game$player"] == CHARLIE_POINT) {
		$charlie_won = 1;
	} else if ($_POST["charlie$game$player"] == CHARLIE_TWO_CAUGHT) {
		$charlie_caught = 2;
	} else if ($_POST["charlie$game$player"] == CHARLIE_LOST) {
		$charlie_lost = 1;
	} else if ($_POST["charlie$game$player"] == CHARLIE_CAUGHT) {
		$charlie_caught = 1;
	} else if ($_POST["charlie$game$player"] == CHARLIE_POINT_CAUGHT) {
		$charlie_won = 1;
		$charlie_caught = 1;
	}

	$schweine = $_POST["s$game$player"];
	$game_schweine = 0;
	for ($i = 0; $i < $max_players; $i++) {
  		if ($_POST["s$game$i"]) {
  	        	$game_schweine = $_POST["s$game$i"];
  		}
  	}
	if (!$schweine) $schweine=0;
	else $schweine =  1;

	$hochzeit = $_POST["h$game$player"];
	if (!$hochzeit) $hochzeit=0;
	else $hochzeit = 1;

	$armut = $_POST["a$game$player"];
	if (!$armut) $armut=0;
	else $armut = 1;

	$doppelkopf = $_POST["dk$game$player"];
	if (!$doppelkopf) $doppelkopf = 0;
	$schweine_caught = 0;
	$fuechse_caught = 0;
	$schweine_lost = 0;
	$fuechse_lost = 0;
	if ($game_schweine) {
		if ($_POST["fox$game$player"] == FOX_CAUGHT) {
			$schweine_caught = 1;
		} else if ($_POST["fox$game$player"] == FOX_LOST) {
			$schweine_lost = 1;
		} else if ($_POST["fox$game$player"] == FOX_TWO_CAUGHT) {
			$schweine_caught = 2;
		} else if ($_POST["fox$game$player"] == FOX_TWO_LOST) {
			$schweine_lost = 2;
		}
	} else {
		if ($_POST["fox$game$player"] == FOX_CAUGHT) {
			$fuechse_caught = 1;
		} else if ($_POST["fox$game$player"] == FOX_LOST) {
			$fuechse_lost = 1;
		} else if ($_POST["fox$game$player"] == FOX_TWO_CAUGHT) {
			$fuechse_caught = 2;
		} else if ($_POST["fox$game$player"] == FOX_TWO_LOST) {
			$fuechse_lost = 2;
		} else if ($_POST["fox$game$player"] == FOX_LOST_CAUGHT) {
			$fuechse_caught = 1;
			$fuechse_lost = 1;
		}
	}
/*	echo "-------game_properties----<br/>";
	echo "schweine=$schweine<br/>";
	echo "hochzeit=$hochzeit<br/>";
	echo "armut=$armut<br/>";
	echo "charlie_caught=$charlie_caught<br/>";
	echo "charlie_lost=$charlie_lost<br/>";
	echo "charlie_won=$charlie_won<br/>";
	echo "schweine_caught=$schweine_caught<br/>";
	echo "schweine_lost=$schweine_lost<br/>";
	echo "fuechse_caught=$fuechse_caught<br/>";
	echo "fuechse_lost=$fuechse_lost<br/>";
	echo "doppelkopf=$doppelkopf<br/>";
	echo "annoucements=$call_id<br/>"; 
*/	// build array
	$gprop['schweine'] = $schweine;
	$gprop['hochzeit'] = $hochzeit;
	$gprop['armut'] = $armut;	
	$gprop['charlie_caught'] = $charlie_caught;
	$gprop['charlie_lost'] = $charlie_lost;		
	$gprop['charlie_won'] = $charlie_won;		
	$gprop['schweine_caught'] = $schweine_caught;		
	$gprop['schweine_lost'] = $schweine_lost;		
	$gprop['fuechse_caught'] = $fuechse_caught;		
	$gprop['fuechse_lost'] = $fuechse_lost;		
	$gprop['doppelkopf'] = $doppelkopf;		
	$gprop['announcement'] = $call_id; 
	

	$game_number = $game;
	$game_props = getGamePropertiesID($gprop);
	$userid = $_POST["player$player"];

//	echo "----------player_data-----<br/>";
//	echo "player_id=$userid<br/>";
//	echo "location=$location<br/>";
//	echo "game_date=$game_date<br/>";
//	echo "game_round=$game_round<br/>";
//	echo "game_number=$game_number<br/>";
//	echo "game_props=$game_props<br/>";
//	echo "re=$re<br/>";
//	echo "kontra=$contra<br/>";
//	echo "game_points=$player_points<br/>";
//	echo "card_points=$card_points<br/>";
//	echo "won=$won<br/>";

	$bock = $_POST["b$game"] == "on";
	if (!$bock) $bock=0;
	$game_type=$_POST["ty$game"];
	$obligatory_solos=0;
	$player_position = ($player - $game_position)  % $max_players;
	if ($player_position < 0) {
		$player_position = $max_players + $player_position;
	}
	$comments = "";
	$date_parts = explode(".", $game_date);
	$iso_date = date("Y-m-d", mktime(0, 0, 0, $date_parts[1], $date_parts[0], $date_parts[2]));
	// build player-data array:
	$insertString = "INSERT INTO player_data VALUES (
		'$userid', '$location', '$iso_date', '$game_round',
		'$game_number', '$game_props', '$re', '$contra', '$player_points',
		'$card_points', '$won', '$bock', '$game_type',
		'$obligatory_solos', '$player_position', '$comments', '$match_id')";
//	echo "InsertString = $insertString<br/>";
	pg_exec($insertString);
}

function punkte($game, $player) {
	global $users, $users_id, $game_date, $location;
	$players = $_POST['playercount'];
	$ansagere = -1;
	$ansagekontra = -1;
	$fgefRe = 0;
	$fgefCo = 0;
	$cgefRe = 0;
	$cgefCo = 0;
	$cpunktRe = 0;
	$cpunktCo = 0;
	$dkRe = 0;
	$dkCo = 0;
	$armut = 0;
	$bock = $_POST["b$game"] == "on";
	$kp0 = $_POST["kp$game"];

	if (strlen($kp0) > 0) {
		for ($i = 0; $i < $players; $i++) {
			if ($_POST["a$game$i"] == "on") {
				$armut = 1;
			}
			
			if ($_POST["re$game$i"] == "on") {
				if (($ansagere == -1 || $ansagere > $_POST["call$game$i"]) && ($_POST["call$game$i"] > 0)) {
				 	$ansagere = $_POST["call$game$i"];
				}
				if ($_POST["fox$game$i"] == FOX_CAUGHT || $_POST["fox$game$i"] == FOX_LOST_CAUGHT) {
					$fgefRe++;
				} else if ($_POST["fox$game$i"] == FOX_TWO_CAUGHT) {
					$fgefRe += 2;
				}
				if ($_POST["charlie$game$i"] == CHARLIE_CAUGHT) {
					$cgefRe++;
				} else if ($_POST["charlie$game$i"] == CHARLIE_POINT) {
					$cpunktRe++;
				} else if ($_POST["charlie$game$i"] == CHARLIE_POINT_CAUGHT) {
					$cgefRe++;
					$cpunktRe++;
				} else if ($_POST["charlie$game$i"] == CHARLIE_TWO_CAUGHT) {
					$cgefRe += 2;
				}
				
				$dkRe += $_POST["dk$game$i"];
			} else {
				if (($ansagekontra == -1 || $ansagekontra> $_POST["call$game$i"]) && ($_POST["call$game$i"] > 0)) {
					$ansagekontra = $_POST["call$game$i"];
				}
				
				if ($_POST["fox$game$i"] == FOX_CAUGHT || $_POST["fox$game$i"] == FOX_LOST_CAUGHT) {
					$fgefCo++;
				} else if ($_POST["fox$game$i"] == FOX_TWO_CAUGHT) {
					$fgefCo += 2;
				}
				
				if ($_POST["charlie$game$i"] == CHARLIE_CAUGHT) {
					$cgefCo++;
				} else if ($_POST["charlie$game$i"] == CHARLIE_POINT) {
					$cpunktCo++;
				} else if ($_POST["charlie$game$i"] == CHARLIE_POINT_CAUGHT) {
					$cgefCo++;
					$cpunktCo++;
				} else if ($_POST["charlie$game$i"] == CHARLIE_TWO_CAUGHT) {
					$cgefCo += 2;
				}
				
				$dkCo += $_POST["dk$game$i"];
			}
		} /* for players */

		require_once("calc.php");
		$result = calculate($kp0, $ansagere, $ansagekontra, $fgefRe, $fgefCo, $cgefRe, $cgefCo, $cpunktRe, $cpunktCo, $dkRe, $dkCo, $armut, $bock, $_POST["ty$game"]);
		/* check, if solo */
		if ($_POST["ty$game"] != 0) {
			if ($_POST["re$game$player"] == "on") {
				$result["game_points"] = $result["game_points"] * 3;
			}
		}
		return $result;
	} /* if */
}

/* prüft Eingaben für einen Spieler*/
function checkPlayerInputs($game_number, $player) {
	$errors = array();
	if ($_POST["h$game_number$player"] == "on" && 
				$_POST["a$game_number$player"] == "on") {
		$errors[] = "Armut und Hochzeit zusammen nicht möglich";
	}
	if ($_POST["h$game_number$player"] == "on" && 
				!$_POST["re$game_number$player"] == "on") {
		$errors[] = "Wenn Hochzeit muss Spieler in Re-Partei sein";
	}
/* unneeded now! */
//	if ($_POST["fg$game_number$player"] + $_POST["fv$game_number$player"] > 2) {
//		$errors[] = "Summe gef. und verl. Füchse ungültig";
//	}
	if ($_POST["s$game_number$player"] == "on" &&
			($_POST["fox$game_number$player"] == FOX_CAUGHT ||
			 $_POST["fox$game_number$player"] == FOX_TWO_CAUGHT ||
			 $_POST["fox$game_number$player"] == FOX_LOST_CAUGHT) ) {
		$errors[] = "Schwein/Fuchs kann nicht gefangen worden sein";
	}
	if ($_POST["s$game_number$player"] == "on" && $_POST["a$game_number$player"] == "on") {
		$errors[] = "Schweine und Armut zusammen nicht möglich";
	}
	return $errors;
}

function checkIfTwoElementsEqual ($ar) {
	for ($i = 0; $i < count($ar); $i++) {
		for ($j = $i+1; $j < count($ar); $j++) {
			if ($ar[$i] == $ar[$j]) {
				return true;
			}
		}
	}	
	return false;
}

/* prüft Eingaben für das gesamte einzelne Spiel */
function checkGameInputs($game_number, $player_list) {
	$errors = array();
	$re_number = 0;
	$hochzeit_number = 0;
	$fg_number_re = 0;
	$fv_number_re = 0;
	$fg_number_ko = 0;
	$fv_number_ko = 0;
	$cg_number = 0;
	$cv_number = 0;
	$cp_number = 0;
	$armut_number = 0;
	$call_re = array();
	$call_ko = array();
	$player_ids = array();
	foreach ($player_list as $i) {
		if ($_POST["re$game_number$i"] == "on") {
			$re_number++;
			
			if ($_POST["fox$game_number$i"] == FOX_CAUGHT) {
				$fg_number_re++;
			} else if ($_POST["fox$game_number$i"] == FOX_TWO_CAUGHT) {
				$fg_number_re += 2;
			} else if ($_POST["fox$game_number$i"] == FOX_LOST) {
				$fv_number_re++;
			} else if ($_POST["fox$game_number$i"] == FOX_TWO_LOST) {
				$fv_number_re += 2;
			} else if ($_POST["fox$game_number$i"] == FOX_LOST_CAUGHT) {
				$fv_number_re++;
				$fg_number_re++;
			}			
			
			if ($_POST["call$game_number$i"] != -1) {
				/* ansage re */
				$call_re[] = $_POST["call$game_number$i"];
			}
		} else {
		
			if ($_POST["fox$game_number$i"] == FOX_CAUGHT) {
				$fg_number_ko++;
			} else if ($_POST["fox$game_number$i"] == FOX_TWO_CAUGHT) {
				$fg_number_ko += 2;
			} else if ($_POST["fox$game_number$i"] == FOX_LOST) {
				$fv_number_ko++;
			} else if ($_POST["fox$game_number$i"] == FOX_TWO_LOST) {
				$fv_number_ko += 2;
			} else if ($_POST["fox$game_number$i"] == FOX_LOST_CAUGHT) {
				$fv_number_ko++;
				$fg_number_ko++;
			}			
			
			if ($_POST["call$game_number$i"] != -1) {
				/* ansage kontra */
				$call_ko[] = $_POST["call$game_number$i"];
			}	
		}
		
		if ($_POST["h$game_number$i"]) {
			$hochzeit_number++;
		}
		if ($_POST["a$game_number$i"]) {
			$armut_number++;
		}
		if ($_POST["charlie$game_number$i"] == CHARLIE_POINT) {
			$cp_number++;
		} else if ($_POST["charlie$game_number$i"] == CHARLIE_CAUGHT) {
			$cg_number++;
		} else if ($_POST["charlie$game_number$i"] == CHARLIE_LOST) {
			$cv_number++;
		} else if ($_POST["charlie$game_number$i"] == CHARLIE_POINT_CAUGHT) {
			$cp_number++;
			$cg_number++;
		} else if ($_POST["charlie$game_number$i"] == CHARLIE_TWO_CAUGHT) {
			$cg_number+=2;
		}
		$player_ids[] = $_POST["player$i"];
	}
	
	if (checkIfTwoElementsEqual($player_ids)) {
		$errors[] = "Spielerauswahl fehlerhaft";
	}
	if (checkIfTwoElementsEqual($call_re)) {
		 $errors[] = "Bei der Re-Partei ex. gleiche Ansagen";
	}	
	if (checkIfTwoElementsEqual($call_ko)) {
		 $errors[] = "Bei der Kontra-Partei ex. gleiche Ansagen";
	}	
	if ($cg_number != $cv_number) {
		$errors[] = "Anzahl gef. Charlies ungleich Anzahl verl. Charlies";
	}
	if ($cg_number + $cv_number > 4) {
		$errors[] = "Anzahl gef. oder verl. Charlies zu hoch";
	}
	if ($cp_number > 1) {
		$errors[] = "Maximal ein Charlie kann punkten";
	}
	
	if ($_POST["ty$game_number"] != 0 && $re_number != 1) {
		/* solo */
		$errors[] = "Solospiel muss genau einen Re-Spieler besitzen";
	}
	
	if ($_POST["ty$game_number"] == 0 && $re_number != 2) {
		$errors[] = "Re-Partei muss bei Normalspiel aus zwei Spielern bestehen";
	}	
		
	if ($armut_number > 2) {
		$errors[] = "Mehr als zwei Spieler können keine Armut haben";
	}
	if ($fg_number > 2) {
		$errors[] = "Mehr als 2 Füchse können nicht gefangen werden";
	}
	if ($fv_number > 2) {
		$errors[] = "Mehr als 2 Füchse können nicht verloren werden";
	}
	if ($fg_number_re + $fg_number_ko != $fv_number_re + $fv_number_ko) {
		$errors[] = "Anzahl gef. Füchse ungleich Anzahl verl. Füchse";
	}
	if ($fg_number_re != $fv_number_ko || $fg_number_ko != $fv_number_re) {
		$errors[] = "Zuweisung gef. Fuchs verl. Fuchs ungültig";
	}
	if ($fg_number_re + $fg_number_ko + $fv_number_re + $fv_number_ko > 4) {
		$errors[] = "Anzahl gef. und verl. Füchse zu hoch";
	}
	if ($re_number < 1 || $re_number > 2) {
		$errors[] = "Zuweisungen Re-Partei ungültig";
	}
	if ($hochzeit_number > 1) {
		$errors[] = "Mehr als ein Spieler kann nicht Hochzeit haben";
	}
	if ($_POST["kp$game_number"] > 240 || $_POST["kp$game_number"] < 0 
					|| !preg_match('/^\d+$/', $_POST["kp$game_number"])) {
		$errors[] = "Ungültige Kartenpunkte";
	} 
	return $errors;
}
?>
<div class="controls">
<div class="row">
<span class="label">Datum:</span>
<span class="formw">
<input class="text" type='text' name='date' value="<? echo $game_date; ?>" size="10"/>
</span>
</div>
<div class="row">
<span class="label">Ort des Spiels:</span>
<span class="formw">
<input class="text" type="text" name="locality" size="10" value="<? echo $location; ?>"/>
</span>
</div>
<div class="row">
<span class="label">Anzahl Spieler:</span>
<span class="formw">
<input class="text" type="text" name="playercount" size="10" value="<?echo "$players";?>"/>
</span>
</div>
<div class="row">
<span class="label">Anzahl Spiele:</span>
<span class="formw">
<input class="text" type="text" name="game_count" size="10" value="<?echo "$game_count";?>"/>
</span>
</div>
</div>

<form action="" method="post">
<div style="clear:both">
<table>
<tr>
<? 
for ($i = 0 ; $i < $players ; $i++) {
?>
<th>
<select name="player<? echo $i ?>">
<? 
	for ($j = 0; $j < count($users); $j++) {
?>
		<option value="<? echo $users_id[$j]; ?>" <?if ($_POST["player$i"]=="$users_id[$j]") echo "selected";?>><? echo $users[$j]; ?></option>
<?
	}
?>
</select>
</th>
<? 
} 
?>
<th>Spiel-Info</th>
<th>Punkte</th>
</tr>
<?

$error_occured = false;

for ($game=0;$game<$game_count;$game++) { 
?>
	<tr>
<?
	$player_list = array();
	for ($player=0;$player<$players;$player++) {
?>
		<td>
<?
		$left = (4 + $game) % $players;
		$right = ($players - 1 + $game) % $players;
		$setout = ($players > 4) && (($left <= $right && $player >= $left && $player
		<= $right) || ($left > $right && ($player >= $left || $player <= $right)));
		if ($setout) {
			echo "-----";
			$punkte[-1]["$player"] = 0;
			$punkte["$game"]["$player"] = $punkte["$game"-1]["$player"];
		} else {
			$player_list[] = $player;
			$result = punkte($game, $player);
			$repunkte = $result["game_points"];
			/* set bock flag if necessary */
			if (($repunkte < 0 && $_POST["re$game$player"] == "on" && $_POST["call$game$player"] > 0) || 
					($repunkte > 0 && !$_POST["re$game$player"] && $_POST["call$game$player"] > 0)) {
				echo "<span class=\"info_text\">Spiel führt zu Bock</span><br/>";
				for ($i=1; $i <= $players; $i++) {
					$bock_game = $game + $i;
					/* erstmal auskommentiert, da noch nicht korrekt */
					//$_POST["b$bock_game"] = "on";
				}
			}
			if (($_POST["re$game$player"] == "on" && $result["winner"] == "re") || 
						(!$_POST["re$game$player"] && $result["winner"] == "contra")) {
				$winner[$game][$player] = 1;
//				echo "Spieler hat gewonnen<br/>";
			} else {
				$winner[$game][$player] = 0;
			} 
			$game_points[$game][$player] = $repunkte;
			$punkte[-1]["$player"] = 0;
			if ($_POST["re$game$player"] == "on") {
				$punkte["$game"]["$player"] = $punkte["$game"-1]["$player"]+$repunkte;
			} else {
				$punkte["$game"]["$player"] = $punkte["$game"-1]["$player"]-$repunkte;
			}
			$p = $punkte["$game"]["$player"];
?>
<div class="trow">
<? echo "Gesamtpunkte: $p"; ?>
</div>
<div class="trow">
			<input type="checkbox" name='re<?echo "$game$player" ?>'
			<? if ($_POST["re$game$player"] == "on") echo "checked=\"checked\""; ?> />Re
</div>
<div class="trow">
			<input type="checkbox" name='h<?echo "$game$player"; ?>'
			<? if ($_POST["h$game$player"] == "on") echo "checked=\"checked\""; ?> />Hochzeit
</div>
<div class="trow">
			<input type="checkbox" name='s<?echo "$game$player"; ?>'
			<? if ($_POST["s$game$player"] == "on") echo "checked=\"checked\""; ?>/>Schweine 
</div>
<div class="trow">
			<input type="checkbox" name='a<?echo "$game$player"; ?>'
			<? if ($_POST["a$game$player"] == "on") echo "checked=\"checked\""; ?> />Armut 
</div>
<div class="trow">
	<span class="label">
		F&uuml;chse:</span>
	<span class="formw"><select name='<? echo "fox$game$player"; ?>'>
				<option value="0" 
					<? if ($_POST["fox$game$player"] == FOX_NOTHING) {
						echo "selected=\"selected\"";
					} ?>
				>-</option>
				<option value="1" 
					<? if ($_POST["fox$game$player"] == FOX_CAUGHT) {
						echo "selected=\"selected\"";
					} ?>
				>Gef.</option>
				<option value="2" 
					<? if ($_POST["fox$game$player"] == FOX_LOST) {
						echo "selected=\"selected\"";
					} ?>
				>Verl.</option>
				<option value="3" 
					<? if ($_POST["fox$game$player"] == FOX_LOST_CAUGHT) {
						echo "selected=\"selected\"";
					} ?>
				>Gef./Verl.</option>
				<option value="4" 
					<? if ($_POST["fox$game$player"] == FOX_TWO_LOST) {
						echo "selected=\"selected\"";
					} ?>
				>2 verl.</option>
				<option value="5" 
					<? if ($_POST["fox$game$player"] == FOX_TWO_CAUGHT) {
						echo "selected=\"selected\"";
					} ?>
				>2 gef.</option>
			</select>
		</span>
</div>
<div class="trow">
	<span class="label">Charlie:</span>
	<span class="formw">
			 <select name='<? echo "charlie$game$player"; ?>'>
				<option value="0" 
					<? if ($_POST["charlie$game$player"] == CHARLIE_NOTHING) {
						echo "selected=\"selected\"";
					} ?>
				>-</option>
				<option value="1" 
					<? if ($_POST["charlie$game$player"] == CHARLIE_POINT) {
						echo "selected=\"selected\"";
					} ?>
				>Punkt</option>
				<option value="2" 
					<? if ($_POST["charlie$game$player"] == CHARLIE_CAUGHT) {
						echo "selected=\"selected\"";
					} ?>
				>Gef.</option>
				<option value="3" 
					<? if ($_POST["charlie$game$player"] == CHARLIE_LOST) {
						echo "selected=\"selected\"";
					} ?>
				>Verl.</option>
				<option value="4" 
					<? if ($_POST["charlie$game$player"] == CHARLIE_POINT_CAUGHT) {
						echo "selected=\"selected\"";
					} ?>
				>Pkt./Gef.</option>
				<option value="5" 
					<? if ($_POST["charlie$game$player"] == CHARLIE_TWO_CAUGHT) {
						echo "selected=\"selected\"";
					} ?>
				>2 gef.</option>
			</select>
	</span>
</div>
<div class="trow">
	<span class="label">DK:</span>
	<span class="formw">
			 <select name='<? echo "dk$game$player"; ?>'>
				<option value="0" 
					<? if ($_POST["dk$game$player"] == 0) {
						echo "selected=\"selected\"";
					} ?>
				>-</option>
				<option value="1" 
					<? if ($_POST["dk$game$player"] == 1) {
						echo "selected=\"selected\"";
					} ?>
				>1</option>
				<option value="2" 
					<? if ($_POST["dk$game$player"] == 2) {
						echo "selected=\"selected\"";
					} ?>
				>2</option>
			</select>
	</span>
</div>
<div class="trow">
	<span class="label">Ansage:</span>
	<span class="formw">
			<select name="<? echo "call$game$player";?>">
			<? for ($i=0; $i < count($announcements_text); $i++) { ?>
				<option 
					value="<?echo $announcements_type[$i]?>" 
<?
					if($_POST["call$game$player"] == $announcements_type[$i]) { 
						echo "selected=\"selected\"";
					}
					if (!$_POST["call$game$player"] && $announcements_type[$i]==-1) {
						echo "selected=\"selected\"";
					}
?>				>
<? 
					echo "$announcements_text[$i]"
?>
				</option>
			<? } ?> 
			</select>
	</span>
</div>
<?
		}	//else setout
		$errors = checkPlayerInputs($game, $player);
		foreach ($errors as $error) {
			echo "<span class=\"error_text\">$error</span><br/>";
		}
?>
	</td>
<? 
	} // for players 
?>
	<td>
		<div class="trow">
			<span class="label">KP:</span>
			<span class="formw">
				<input class="text" type="text" name="<? echo "kp$game"; ?>" size="5" value="<? echo $_POST["kp$game"];?>"/>
			</span>
		</div>
		<div class="trow">
			<input type="checkbox" name='b<? echo "$game"; ?>' <? if($_POST["b$game"] == "on") echo "checked";?>/>Bock
		</div>
		<div class="trow">
		<select name="<?echo "ty$game"; ?>"> 
<? 
		for ($i=0; $i < count($game_types); $i++) { ?>
			<option value="<?echo $game_types_id[$i] ?>" <?if($_POST["ty$game"] == $game_types_id[$i]) echo "selected"; ?>
			>
				<? echo $game_types[$i] ?>
			</option>
<? 
		} 
?>
		</select>
		</div>
		<div class="trow">
			<span class="label"><? echo "Spiel-Nr.:"; ?></span>
			<span class="value"><? echo ($game + 1); ?></span>
		</div>
		<div class="trow">
			<span class="label"><? echo "Runde:"; ?></span>
			<span class="value"><? echo ($game_round + 1); ?></span>
		</div>
		<div class="trow">
			<span class="label"><? echo "Position:"; ?></span>
			<span class="value"><? echo ($game_position + 1); ?></span>
		</div>
<?
	$game_number++;
	if (($_POST["ty$game"] != 0) && ($players == 4)) {
		// spiel einfügen, spielposition ist solospieler
//	} else if (($_POST["ty$game"] != 0) && ($players > 4)) {
		// solo mit mehr als 4 spielern, solo spieler kommt raus
	} else {
		$game_position = ($game_position + 1) % $players;
		if ($game_position == 0) {
			$game_round++;
		}
	}
?>
	</td>
	<td>
<? 
	echo "$repunkte";

	/* take player_list ...*/
	$errors = checkGameInputs($game, $player_list);
	foreach ($errors as $error) {
		echo "<span class=\"error_text\">$error</span><br/>";
		
	}
?>
	</td>
	</tr>
<? 
} // for games



 
?>
<tr>
<?
for ($player=0;$player<$players;$player++) {
?>  
<td>
<? 
	echo $punkte["$game_count"-1]["$player"]; 
?>
</td>
<? 
} 



?>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
</table>
</div>
<?
$error_occured = 0;
if ($_POST["save"]) {
	$game_round = 0;
	$game_position = 0;
	for ($game=0;$game<$game_count;$game++) {
		for ($player=0;$player<$players;$player++) {
			if (count(checkPlayerInputs($game, $player)) > 0) {
				$error_occured = true;
			}
		}
		/*if (count(checkGameInputs($game, $player_list)) > 0) {
			$error_occured = true;
		}*/
	} /* games */
	if (!$error_occured) {
		$res = pg_exec("SELECT nextval('match_id_sequence')");
		$row = pg_fetch_array($res);
		$match_id = $row['nextval'];
    	for ($game=0;$game<$game_count;$game++) {
    		for ($player=0;$player<$players;$player++) {
    			$left = (4 + $game) % $players;
				$right = ($players - 1 + $game) % $players;
				$setout = ($players > 4) && (($left <= $right && $player >= $left && $player
						<= $right) || ($left > $right && ($player >= $left || $player <= $right)));
				if (!$setout) {
					insertGame($match_id, $game, $game_position, $game_round, $player, $game_points[$game][$player], $winner[$game][$player], $players);
				} 
    		}
    		if (($_POST["ty$game"] != 0) && ($players == 4)) {
				// spiel einfügen
			} else {
        		$game_position = ($game_position + 1) % $players;
        		if ($game_position == 0) {
        			$game_round++;
        		}
			}
    		
    	} 
	}
}
?>
<div style="margin-top: 10px" class="controls">
<div class="row">
<span class="label">Datum:</span>
<span class="formw">
<input class="text" type='text' name='date' value="<? echo $game_date; ?>" size="10"/>
</span>
</div>
<div class="row">
<span class="label">Ort des Spiels:</span>
<span class="formw">
<input class="text" type="text" name="locality" size="10" value="<? echo $location; ?>"/>
</span>
</div>
<div class="row">
<span class="label">Anzahl Spieler:</span>
<span class="formw">
<input class="text" type="text" name="playercount" size="10" value="<?echo "$players";?>"/>
</span>
</div>
<div class="row">
<span class="label">Anzahl Spiele:</span>
<span class="formw">
<input class="text" type="text" name="game_count" size="10" value="<?echo "$game_count";?>"/>
</span>
</div>
</div>

<div style="margin-top: 10px; margin-left: 10px" class="controls">
<div class="row">
<input style="width:100%" type="submit" value="Werte berechnen" name="calculate"/>
</div>
<div class="row">
<input style="width:100%" type="submit" value="In Datenbank speichern" name="save"/>
</div>

<div class="row">
<input style="width:100%" type="submit" value="Clear all"/>
</div>
<div class="row">
<a href="index.php">Zur&uuml;ck zur &Uuml;bersichtsseite</a>
</div>
</div>

</form>

</body>
</html>