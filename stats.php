<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="de" xml:lang="en">

<head>
<title>Webkopf - Spielerstatistiken</title>
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
?>
<div class="controlbar">
	<a href="index.php?bock=<?= $GLOBALS['bock'] ?>">&Uuml;bersichtsseite</a>
	<a href="team_stats.php?bock=<?= $GLOBALS['bock'] ?>">Teamstatistiken</a>
<?  if ($GLOBALS['bock'] == 0) { ?>
	<a href="stats.php?bock=1">Bock anschalten</a>
<? } else { ?>
	<a href="stats.php?bock=0">Bock ausschalten</a>
<? } ?>	
</div>
<?

$player_result=pg_exec("select * from players");

while ($row = pg_fetch_array($player_result)) {
	$users[] = $row["full_name"];
	$users_id[] = $row["id"];
}

$query_result = pg_exec("SELECT COUNT(won) AS count, SUM(won) AS re_wins, COUNT(won) - SUM(won) AS contra_wins FROM games");
$row = pg_fetch_array($query_result);
$GLOBALS['game_count'] = $row["count"];
$re_wins = $row["re_wins"];
$contra_wins = $row["contra_wins"];

function get_player_table_data()
{
	$global_stats = pg_exec("SELECT players.full_name, players.nick_name, " .
			"players.id, game_points, wins, games, re, announcement_number, " .
			"won_announcements, lost_announcements FROM " .
			"(SELECT player_id, ".$GLOBALS['gp_sql']." AS game_points, " .
			"SUM(won) AS wins, COUNT(player_id) AS games, SUM(re) AS re, " .
			"SUM (CASE WHEN announcement <> 0 THEN 1 END) AS announcement_number, " .
			"SUM (CASE WHEN announcement <> 0 AND won = 1 THEN 1 END) AS won_announcements, " .
			"SUM (CASE WHEN announcement <> 0 AND won = 0 THEN 1 END) AS lost_announcements " .
			"FROM player_data, game_properties WHERE game_props = game_properties.id " .
			"GROUP BY player_id) AS stats, players WHERE players.id = stats.player_id");
			
	while ($row = pg_fetch_array($global_stats)) {
		$row["win_rate"] = $row["wins"] / $row["games"];
		$row["points_per_game"] = $row["game_points"] / $row["games"];
		$row["re_rate"] = $row["re"] / $row["games"];
		$row["announce_rate"] = $row["announcement_number"] / $row["games"];
		if ($row["announcement_number"] > 0) {
			$row["announce_success_rate"] = $row["won_announcements"] / $row["announcement_number"];
		}
		$pt_data[] = $row;
	} 
	
	return $pt_data;
}

// TODO: merge with the function in player_details.php
function print_extra_point_row($title, $value, $bold = false)
{
	$percentage = $value / $GLOBALS['game_count'];
	echo "<tr><td>";
	if ($bold === true) {
		echo "<b>";
	}
	echo $title;
	if ($bold === true) {
		echo "</b>";
	}
	echo "</td><td>";
	if ($bold === true) {
		echo "<b>";
	}
	echo $value;
	if ($bold === true) {
		echo "</b>";
	}
	echo "</td><td>";
	if ($bold === true) {
		echo "<b>";
	}
	echo number_format($percentage * 100, 3)." ";
	if ($bold === true) {
		echo "</b>";
	}
	echo "</td></tr>";
}

/*
  listet die ansagen nach re bzw. kontra auf, inklusiv gew. verloren
 select full_name, c, w from 
	(select player_id as pi, count(*) as c, sum(won) as w  
		from game_properties, player_data where id=game_props and re=1 
				and announcement > 0 group by player_id) 
	as t, players where pi = id order by c desc;

*/

$announcement_globals = pg_exec("SELECT COUNT(announcement) AS announcements, " .
		"SUM(won) AS wins " .
		"FROM game_properties, player_data " .
		"WHERE game_props = game_properties.id AND game_properties.announcement <> 0;");

$row = pg_fetch_array($announcement_globals);
$announcement_count = $row["announcements"];
$announcement_wins = $row["wins"];
$announcement_average = ($announcement_count / $GLOBALS['game_count']) / 4;
$announcement_success_average = ($announcement_wins / $announcement_count);
//echo "Ansagen:$announcement_count, Schnitt: $announcement_average, Erfolg: $announcement_success_average"; 

?>
<div>
<div style="clear:both" class="stats_box">

<h3>Spieler&uuml;bersicht</h3>
<table>

<tr>
	<th><a href="stats.php?pt_sort=full_name">Name</a></th>
	<th><a href="stats.php?pt_sort=nick_name">Spitzname</a></th>
	<th><a href="stats.php?pt_sort=game_points">&Sigma; Punkte</a></th>
	<th><a href="stats.php?pt_sort=games">#Spiele</a></th>
	<th><a href="stats.php?pt_sort=wins">#Siege</a></th>
	<th><a href="stats.php?pt_sort=win_rate">Sieg %</a></th>
	<th><a href="stats.php?pt_sort=points_per_game">Pkt./Spiel</a></th>
	<th><a href="stats.php?pt_sort=re_rate">Re %</a></th>
	<th><a href="stats.php?pt_sort=announce_rate">Ansagen %</a></th>
	<th><a href="stats.php?pt_sort=announce_success_rate">Gew. Ansagen %</a></th>
</tr>

<? 
   	$pt_data = get_player_table_data();
   	$order = "DESC";
   	if (isset($_GET["pt_sort"])) {
   		if ($_GET["pt_sort"] == "full_name" || $_GET["pt_sort"] == "nick_name") {
   			$order = "ASC";
   		}
   		$pt_data = table_sort($pt_data, $_GET["pt_sort"], $order);
	} else {
   		$pt_data = table_sort($pt_data, "game_points", $order);
	}
   	foreach($pt_data as $row) {
?>
<tr>
<td>
<?
	echo "<a href=\"player_details.php?player_id=$row[id]&bock=$GLOBALS[bock]\">";
	echo "$row[full_name]";
	echo "</a>";
?>
</td>
<td>
<?
	echo $row["nick_name"];
?>
</td>
<td>
<?
	echo $row["game_points"];
?>
</td>
<td>
<?
	echo $row["games"];
?>
</td>
<td>
<?
	echo $row["wins"];
?>
</td>
<td>
<?
	echo number_format($row["wins"] / $row["games"] * 100, 3);
?>
</td>
<td>
<?
	echo number_format($row["game_points"] / $row["games"], 3);
?>
</td>
<td>
<?
	echo number_format($row["re"] / $row["games"] * 100, 3)
?>
</td>
<td>
<?
	$announcement_rate = $row["announcement_number"] / $row["games"];
	$rate_diff = $announcement_rate - $announcement_average;
	echo number_format($announcement_rate * 100, 3)." ";
	highlight_diff($rate_diff);
?>
</td>
<td>
<?
	if ($row["announcement_number"] == 0) {
		echo "-";
	} else {
		$announcement_success_rate = $row["won_announcements"] / $row["announcement_number"];
		$rate_diff = $announcement_success_rate - $announcement_success_average;
		echo number_format($announcement_success_rate * 100, 3)." ";
		highlight_diff($rate_diff);
	}
?>
</td>

</tr>
<? } ?>
</table>
</div>
<?
$query_string = "SELECT full_name, pid, c, number_re_announcements,
								won_re, number_co_announcements, won_contra FROM
    						(SELECT player_id as pid, count(*) AS c, 
    								SUM(re) AS number_re_announcements, 
    								SUM (CASE WHEN re=1 AND won=1 THEN 1 END) AS won_re, 
    								SUM(contra) AS number_co_announcements, 
    								SUM (CASE WHEN contra=1 AND won=1 THEN 1 END) AS won_contra 
    						FROM game_properties, player_data 
    								WHERE 	id=game_props AND 
    										announcement > 0 
    						GROUP BY player_id) as t, players WHERE pid = id ORDER BY c DESC";
$query_result = pg_exec($query_string);

$res = pg_exec("SELECT number_re_announcements, won_re," .
				"number_co_announcements, won_contra " .
				"FROM " .
				"(SELECT SUM(re) AS number_re_announcements, 
    					 SUM (CASE WHEN re=1 AND won=1 THEN 1 END) AS won_re, 
    					 SUM(contra) AS number_co_announcements, 
    					 SUM (CASE WHEN contra=1 AND won=1 THEN 1 END) AS won_contra 
    					 FROM game_properties, player_data 
    								WHERE id = game_props AND 
    										announcement > 0 )
    						AS t");
$row = pg_fetch_array($res);
$announcement_re_rate = $row["number_re_announcements"] / $announcement_count;
$announcement_co_rate = $row["number_co_announcements"] / $announcement_count;
$announcement_re_win_rate = $row["won_re"] / $row["number_re_announcements"]; 
$announcement_co_win_rate = $row["won_contra"] / $row["number_co_announcements"]; 
//echo "Re: $announcement_re_rate, Co: $announcement_co_rate," .
//	 "Re-Win: $announcement_re_win_rate, Co-Win: $announcement_co_win_rate"; 

?>
<div class="stats_box">
<h3>Ansagen</h3>
<table>
	<tr> 
		<th>Spieler</th>
		<th>#Ansagen</th>
		<th>Re-Quote</th>
		<th>Gewinnquote Re Ansagen</th>
		<th>Ko-Quote</th>
		<th>Gewinnquote Ko Ansagen</th>
	</tr>
<?
while ($row = pg_fetch_array($query_result)) {
?>
	<tr>
		<td>
<?		echo "<a href=\"player_details.php?player_id=$row[pid]&bock=$GLOBALS[bock]\">";
		echo "$row[full_name]";
		echo "</a>";
?>
		</td>
		<td>
<?		echo $row["c"];
?>
		</td>
		<td>
<?		if ($row["number_re_announcements"] == 0) {
			echo "-";
		} else {
			$re_rate = $row["number_re_announcements"] / $row["c"];
			$rate_diff = $re_rate - $announcement_re_rate;
			echo number_format($re_rate * 100, 3)." ";
			highlight_diff($rate_diff);
		}
?>
		</td>
		<td>
<?		if ($row["number_re_announcements"] == 0) {
			echo "-";
		} else {
			$re_win_rate = $row["won_re"] / $row["number_re_announcements"];
			$rate_diff = $re_win_rate - $announcement_re_win_rate;
			echo number_format($re_win_rate * 100, 3)." ";
			highlight_diff($rate_diff);
		}
?>
		</td>
		<td>
<?		if ($row["number_co_announcements"] == 0) {
			echo "-";
		} else {
			$co_rate = $row["number_co_announcements"] / $row["c"];
			$rate_diff = $co_rate - $announcement_co_rate;
			echo number_format($co_rate * 100, 3)." ";
			highlight_diff($rate_diff);
		}
?>		
		</td>
		<td>
<?		if ($row["number_co_announcements"] == 0) {
			echo "-";
		} else {
			$co_win_rate = $row["won_contra"] / $row["number_co_announcements"];
			$rate_diff = $co_win_rate - $announcement_co_win_rate;
			echo number_format($co_win_rate * 100, 3)." ";
			highlight_diff($rate_diff);
		}
?>		
		</td>
	</tr>
<?
}
?>
</table>			
</div>		
<?
/* allgemeine statistische Merkmale bestimmen: */
$query_string = "SELECT distinct sum(hochzeit) as hochzeit,
					sum (case when hochzeit=1 and won=1 then 1 end) as hochzeit_won, 
					sum(armut) as armut, 
					sum (case when armut=1 and won=1 then 1 end) as armut_won, 
					sum(schweine) as schweine, 
					sum (case when schweine=1 and won=1 then 1 end) as schweine_won,
					sum(case when game_type <> 0 then 1 end) / 4 as soli,
					sum(case when game_type <> 0 and won=1 and re = 1 then 1 end) as soli_won
				FROM game_properties, player_data where
					 game_properties.id=player_data.game_props";
$query_result = pg_exec($query_string);

$row = pg_fetch_array($query_result);
$schweine = $row["schweine"];
$schweine_won = $row["schweine_won"];
$armut = $row["armut"];
$armut_won = $row["armut_won"];
$hochzeit=$row["hochzeit"];
$hochzeit_won=$row["hochzeit_won"];
$soli = $row['soli'];
$soli_won = $row['soli_won'];


?>
<div style="float:left; width:45%">
<div class="stats_box">
<h3> Allgemeine statistische Doppelkopfmerkmale</h3>

<table>
<tr>
	<th>Spiele gesamt</th>
	<th>Siege Re</th>
	<th>Siege Kontra</th>
</tr>
<tr>
	<td><? echo $GLOBALS['game_count'] ?></td>
	<td><?  $percentage = number_format($re_wins / $GLOBALS['game_count'] * 100, 3);
		echo "$re_wins ($percentage %)"; ?></td>
	<td><?  $percentage = number_format($contra_wins / $GLOBALS['game_count'] * 100, 3);
		echo "$contra_wins ($percentage %)"; ?></td>
</tr>
</table>
</div>
<div class="stats_box">
<table>
<tr>
<th>Merkmal</th>
<th>#Gesamt</th>
<th>Prozent</th>
<th>Siege</th>
<th>Siegquote</th>
</tr>

<tr>
<td>#Hochzeit</td>
<td><? echo $hochzeit; ?></td>
<td><? echo number_format($hochzeit / $GLOBALS['game_count'] * 100, 3); ?> </td>
<td><? echo $hochzeit_won;?></td>
<td><?  if ($hochzeit == 0) echo "-";
		else echo number_format($hochzeit_won / $hochzeit * 100, 3);
	?>
</td>
</tr>

<tr> 
<td>#Armut</td> 
<td><? echo $armut; ?></td> 
<td><? echo number_format($armut / $GLOBALS['game_count'] * 100, 3); ?> </td>
<td><? echo $armut_won;?></td>
<td><?  if ($armut == 0) echo "-";
		else echo number_format($armut_won / $armut * 100, 3);
	?>
</td>
</tr>

<tr> 
<td>#Schweine</td> 
<td><? echo $schweine; ?></td> 
<td><? echo number_format($schweine / $GLOBALS['game_count'] * 100, 3); ?> </td>
<td><? echo $schweine_won;?></td>
<td><?  if ($schweine == 0) echo "-";
		else echo number_format($schweine_won / $schweine * 100, 3);
	?>
</td>
</tr>

<tr> 
<td>#Soli</td> 
<td><? echo $soli; ?></td> 
<td><? echo number_format($soli / $GLOBALS['game_count'] * 100, 3); ?> </td>
<td><? echo $soli_won;?></td>
<td><?  if ($soli == 0) echo "-";
		else echo number_format($soli_won / $soli * 100, 3);
	?>
</td>
</tr>

</table>
</div>
</div>
<?
$query_result = pg_exec("SELECT SUM(charlie_caught) AS charlie_caught,
                                SUM(charlie_lost) AS charlie_lost,
                                SUM(charlie_won) AS charlie_won,  
                                SUM(schweine_caught) AS schweine_caught,
                                SUM(schweine_lost) AS schweine_lost,
                                SUM(fuechse_caught) AS fuechse_caught,
                                SUM(fuechse_lost) AS fuechse_lost,
                                SUM(doppelkopf) AS doppelkopf
                                FROM game_properties, player_data
                                        WHERE game_properties.id = player_data.game_props");
$row = pg_fetch_array($query_result);
?>
<div class="stats_box">
<h3>Extrapunkte:</h3>                                
<table>
<tr>
	<th>Extrapunkt</th>
	<th>Anzahl</th>
	<th>Quote</th>
</tr>
<?
	print_extra_point_row('Doppelköpfe',$row['doppelkopf']);
    print_extra_point_row('Gefangene Charlies', $row['charlie_caught']);
    print_extra_point_row('Verlorene Charlies', $row['charlie_lost']);
    print_extra_point_row('Siegreiche Charlies', $row['charlie_won']);
    print_extra_point_row('Schweine gefangen', $row['schweine_caught']);
    print_extra_point_row('Schweine verloren', $row['schweine_lost']);
    print_extra_point_row('F&uuml;chse gefangen', $row['fuechse_caught']);
    print_extra_point_row('F&uuml;chse verloren', $row['fuechse_lost']);
?>
</table>
</div>

<?
$won_pos = array();
$query_result = pg_exec("SELECT SUM(won) as won_quote, COUNT(won) as game_number FROM player_data WHERE player_position=0 AND game_type=0");
$row = pg_fetch_array($query_result);
$won_pos[] = $row["won_quote"];
$query_result = pg_exec("SELECT SUM(won) as won_quote, COUNT(won) as game_number FROM player_data WHERE player_position=1 AND game_type=0");
$row = pg_fetch_array($query_result);
$won_pos[] = $row["won_quote"];
$query_result = pg_exec("SELECT SUM(won) as won_quote, COUNT(won) as game_number FROM player_data WHERE player_position=2 AND game_type=0");
$row = pg_fetch_array($query_result);
$won_pos[] = $row["won_quote"];
$query_result = pg_exec("SELECT SUM(won) as won_quote, COUNT(won) as game_number FROM player_data WHERE player_position=3 AND game_type=0");
$row = pg_fetch_array($query_result);
$won_pos[] = $row["won_quote"];

?>
<div class="stats_box">
<h3>Siegquote nach Position</h3>
<table>
	<tr>
		<th>Position</th>
		<th>Siequote</th>
	</tr>
	<tr>
		<td>1</td>
		<td><? echo number_format($won_pos[0] / $row["game_number"] * 100, 3);?></td>
	</tr>
	<tr>
		<td>2</td>
		<td><? echo number_format($won_pos[1] / $row["game_number"] * 100, 3);?></td>
	</tr>
	<tr>
		<td>3</td>
		<td><? echo number_format($won_pos[2] / $row["game_number"] * 100, 3);?></td>
	</tr>
	<tr>
		<td>4</td>
		<td><? echo number_format($won_pos[3] / $row["game_number"] * 100, 3);?></td>
	</tr>
</table>
</div>

</div>

</body>
</html>