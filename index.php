<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="de" xml:lang="en">

<head>
<title>Webkopf - Hauptseite</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"/>

<link rel="stylesheet" type="text/css" href="global_style.css" />
</head>
<body>
<?
require_once("db.php");
require_once("utilities.php");

if ($_REQUEST['bock']) {
	$GLOBALS['bock'] = 1;
	$GLOBALS['gp_sql'] = SQL_GP_WITH_BOCK;
} else {
	$GLOBALS['bock'] = 0;
	$GLOBALS['gp_sql'] = SQL_GP_WITHOUT_BOCK;
} 

function getPlayerList($match_id) {
    $query_string = "SELECT DISTINCT player_id, full_name, nick_name FROM 
			player_data, players WHERE players.id = player_data.player_id 
			AND match_id =".$match_id."";
	//echo "$query_string<BR>";
    $player_result=pg_exec($query_string);
    
    $i = 0;
    while ($row = pg_fetch_array($player_result)) {
    	$users[$i][0] = $row["full_name"];
    	$users[$i][1] = $row["player_id"];
    	$i++;
    }
    return $users;
}

function getGamePointsForGame($match_id, $player_id) {
	$query_string = "SELECT ".$GLOBALS['gp_sql']."AS game_points FROM " .
			"game_properties, player_data WHERE id = game_props AND " .
			"match_id = ".$match_id." AND player_id = ".$player_id;
	$handle = pg_exec($query_string);
	$result = pg_fetch_array($handle);
	return $result['game_points'];
}
?>
<div style="background-color:#ffddbd; text-align:center; margin-bottom:20px;">
<span style="font-size:2em">Webkopf - Titlebar</span>
</div>
<div class="side">
<div class="row">
<?  if ($_REQUEST['bock']) { ?>
	<a href="index.php?bock=0">Bock ausschalten</a>
<? } else { ?>
	<a href="index.php?bock=1">Bock anschalten</a>
<? } ?>
</div>
<div class="row">
<a href="stats.php?bock=<?= $_REQUEST['bock'] ?>">Statistiken</a>
</div>
<div class="row">
<a href="new_player.php">Spieler hinzuf&uuml;gen</a>
</div>

<form action="game_form.php" method="post">
	<div class="row">
      <span class="label">Datum:</span>
      <span class="formw">
      	<input class="text" type="text" name='date' value="<?= date('d.m.Y') ?>"/>
      </span>
    </div>
	<div class="row">
		<span class="label">Ort:</span>
      <span class="formw">
      	<input class="text" type="text" name="locality"/>
      </span>
	</div>

	<div class="row">
		<span class="label">Anzahl Spieler:</span>
      <span class="formw">
		 <input class="text" type="text" name="playercount"/>
      </span>
	</div>
	<div class="row">
		<span class="label">Anzahl Spiele:</span>
      <span class="formw">
		 <input class="text" type="text" name="game_count"/>
      </span>
	</div>
	<div class="row">
		<input type="submit" value="Daten eingeben"/>
	</div>
</form>
<p>
      <a href="http://validator.w3.org/check?uri=referer"><img
          src="http://www.w3.org/Icons/valid-xhtml10"
          alt="Valid XHTML 1.0!" height="31" width="88" /></a>
</p>
</div>
<?
$result=pg_exec("SELECT DISTINCT match_id, location, game_date from player_data ORDER BY game_date DESC, match_id DESC");
?>
<div id="mainContent">
<table>
<tr>
	<th>Ort</th>
	<th>Datum</th>
	<th>Spieler</th>
</tr>
<?
while ($row = pg_fetch_array($result)) {
?>
<tr>
	<td>
<?		echo $row["location"];?>
	</td>
	<td>
<?		echo $row["game_date"];?>
	</td>
	<td>
<?
		$users = getPlayerList($row['match_id']);
		foreach ($users as $player) {
			$points = getGamePointsForGame($row['match_id'], $player[1]);
?>
			<a href="player_details.php?player_id=<?= $player[1] ?>"><?= $player[0] ?></a>
			(<?= $points ?>)&nbsp;
<?
		}
?>
	</td>
</tr>
<?
}
?>
</table>
</div>

</body>
</html>