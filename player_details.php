<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="de" xml:lang="en">
<head>
<title>Webkopf - Spielerdetails</title>
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
	<a href="index.php?bock=<?= $GLOBALS[bock] ?>">&Uuml;bersichtsseite</a>
	<a href="stats.php?bock=<?= $GLOBALS[bock] ?>">Spielerstatistiken</a>
	<a href="team_stats.php?bock=<?= $GLOBALS[bock] ?>">Teamstatistiken</a>
<?  if ($GLOBALS['bock'] == 0) { ?>
	<a href="player_details.php?player_id=<?= $_GET[player_id] ?>&bock=1">Bock anschalten</a>
<? } else { ?>
	<a href="player_details.php?player_id=<?= $_GET[player_id] ?>&bock=0">Bock ausschalten</a>
<? } ?>
</div><?

$player_id = $_GET['player_id'];

$player_stats = null;

$player_stats = pg_exec("SELECT".$GLOBALS['gp_sql']." AS game_points ,
			SUM(won) AS wins, COUNT(player_id) AS games FROM player_data " .
			"WHERE player_id =".$player_id);
$row = pg_fetch_array($player_stats);

$GLOBALS['games_played'] = $row["games"];
$game_points = $row['game_points'];
$wins = $row['wins'];
$win_rate = $row['wins'] / $row['games'];

$query_result = pg_exec("SELECT COUNT(won) AS count, SUM(won) AS re_wins, COUNT(won) - SUM(won) AS contra_wins FROM games");
$row = pg_fetch_array($query_result);
$GLOBALS['global_game_count'] = $row["count"];
$global_re_wins = $row["re_wins"];
$global_co_wins = $row["contra_wins"];

$extra_points_query = "SELECT SUM(charlie_caught) AS charlie_caught,
                                SUM(charlie_lost) AS charlie_lost,
                                SUM(charlie_won) AS charlie_won,
                                SUM(schweine_caught) AS schweine_caught,
                                SUM(schweine_lost) AS schweine_lost,
                                SUM(fuechse_caught) AS fuechse_caught,
                                SUM(fuechse_lost) AS fuechse_lost,
                                SUM(doppelkopf) AS doppelkopf
                                FROM game_properties, player_data    
                                        WHERE game_properties.id = player_data.game_props";

$query_result = pg_exec($extra_points_query." AND
						player_data.player_id=$player_id");
fill_extra_points_global('extra_points', pg_fetch_array($query_result));

$query_result = pg_exec($extra_points_query);
fill_extra_points_global('global_extra_points', pg_fetch_array($query_result));

$query_result = pg_exec("SELECT re_count, re_wins, re_points, contra_count, " .
		"contra_wins, contra_points FROM
			(select COUNT(won) AS re_count, SUM(won) AS re_wins, " .
			$GLOBALS['gp_sql']." AS re_points FROM player_data WHERE " .
			"player_id = ".$player_id." AND re = 1)	AS re_stats, " .
			"(select COUNT(won) AS contra_count, SUM(won) AS contra_wins, " .
			$GLOBALS['gp_sql']." AS contra_points FROM player_data WHERE " .
			"player_id = ".$player_id." AND re = 0)	AS contra_stats");
			
$row = pg_fetch_array($query_result);
$re_count = $row[re_count];
$re_wins = $row[re_wins];
$re_points = $row[re_points];
$contra_count = $row[contra_count];
$contra_wins = $row[contra_wins];
$contra_points = $row[contra_points];

function fill_extra_points_global($field, $data)
{
	$GLOBALS[$field] = $data;
	$GLOBALS[$field]['sum'] = $GLOBALS[$field]['doppelkopf'] + $GLOBALS[$field]['charlie_caught']
			- $GLOBALS[$field]['charlie_lost'] + $GLOBALS[$field]['charlie_won']
			+ $GLOBALS[$field]['schweine_caught'] - $GLOBALS[$field]['schweine_lost']
			+ $GLOBALS[$field]['fuechse_caught'] - $GLOBALS[$field]['fuechse_lost'];
}

// TODO: merge with the function in stats.php
function print_extra_point_row($title, $field, $bold = false)
{
	$xp = $GLOBALS['extra_points'];
	$g_xp = $GLOBALS['global_extra_points']; 
	$rate = $xp[$field] / $GLOBALS['games_played'];
	$g_rate = $g_xp[$field] / $GLOBALS['global_game_count'] / 4;
	$diff_rate = $rate - $g_rate;
	$g_xp_average = $g_xp[$field] /$GLOBALS['global_game_count'] *$GLOBALS['games_played'] / 4;
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
	echo $xp[$field];
	echo " (".number_format($g_xp_average, 3).")";
	if ($bold === true) {
		echo "</b>";
	}
	echo "</td><td>";
	if ($bold === true) {
		echo "<b>";
	}
	echo number_format($rate * 100, 3)." ";
	highlight_diff($diff_rate);
	if ($bold === true) {
		echo "</b>";
	}
	echo "</td></tr>";
}


$query_string = "select * from players where id=$player_id";

$player_result=pg_exec($query_string);

$row = pg_fetch_array($player_result);
echo "<h2>Hallo $row[full_name]</h2>";

/*
  listet die ansagen nach re bzw. kontra auf, inklusiv gew. verloren
 select full_name, c, w from 
	(select player_id as pi, count(*) as c, sum(won) as w  
		from game_properties, player_data where id=game_props and re=1 
				and announcement > 0 group by player_id) 
	as t, players where pi = id order by c desc;

*/
?>
<div class="stats_box">
<table>
	<tr>
	    <th>&Sigma; Punkte</th>
	    <th>#Spiele</th>
	    <th>#Siege</th>
	    <th>Gewinnquote</th>
	    <th>Punkte/Spiel</th>
		<th>Requote</th>
	</tr>
	<tr>
		<td><? echo $game_points ?></td>
		<td><? echo $GLOBALS['games_played'] ?></td>
		<td><? echo $wins ?></td>
		<td><? echo number_format($win_rate * 100, 3); ?></td>
		<td><? echo number_format($game_points / $GLOBALS['games_played'], 3); ?></td>
		<td><? echo number_format($re_count / $GLOBALS['games_played'] * 100, 3) ?></td>
	</tr>
</table>
</div>
<?
/* globale allgemeine statistische Merkmale bestimmen: */
$special_query = "SELECT distinct sum(hochzeit) as hochzeit,
					sum (case when hochzeit=1 and won=1 then 1 end) as hochzeit_won, 
					sum(armut) as armut, 
					sum (case when armut=1 and won=1 then 1 end) as armut_won, 
					sum(schweine) as schweine, 
					sum (case when schweine=1 and won=1 then 1 end) as schweine_won
				FROM game_properties, player_data where
					 game_properties.id=player_data.game_props";
$query_result = pg_exec($special_query);

$row = pg_fetch_array($query_result);
$global_schweine_rate = ($row["schweine"] / $GLOBALS['global_game_count']) / 4;
$global_schweine_win_rate = $row["schweine_won"] / $row["schweine"];
$global_armut_rate = ($row["armut"] / $GLOBALS['global_game_count']) / 4;
$global_armut_win_rate = $row["armut_won"] / $row["armut"];
$global_hochzeit_rate = ($row["hochzeit"] / $GLOBALS['global_game_count']) / 4;
$global_hochzeit_win_rate = $row["hochzeit_won"] / $row["hochzeit"];

/* allgemeine statistische Merkmale für diesen Spieler bestimmen: */
$query_result = pg_exec($special_query." and player_data.player_id=$player_id");

$row = pg_fetch_array($query_result);
$schweine = $row["schweine"];
$schweine_win_rate = $row["schweine_won"] / $schweine;
$armut = $row["armut"];
$armut_win_rate = $row["armut_won"] / $armut;
$hochzeit = $row["hochzeit"];
$hochzeit_win_rate = $row["hochzeit_won"] / $hochzeit;


?>
<div style="clear:both"/>
<div class="stats_box">
<table>
<tr>
    <th>Siege Re</th>
    <th>Re-Siegquote</th>
	<th>Punkte Re</th>
    <th>Siege Kontra</th>
    <th>Kontra-Siegquote</th>
	<th>Punkte Kontra</th>
</tr>
<tr> 
        <td><? echo "$re_wins"; ?> </td>
  		<td><? $percentage = $re_wins / $re_count;
  			   $diff = $percentage - ($global_re_wins / $global_game_count);
  		       echo number_format($percentage * 100, 3)." ";
  		       highlight_diff($diff);
  		      ?></td>
        <td><? echo $re_points ?></td>
        <td><?  echo "$contra_wins"; ?></td>
  		<td><? $percentage = $contra_wins / $contra_count;
  			   $diff = $percentage - ($global_co_wins / $global_game_count);
  		       echo number_format($percentage * 100, 3)." ";
  		       highlight_diff($diff);
  		      ?></td>
        <td><? echo $contra_points ?></td>
</tr>
</table>
</div>

<div style="clear:both">
<div class="stats_box">
<table>
<tr>
<th>Merkmal</th>
<th>#Gesamt</th>
<th>Prozent</th>
<th>Gewinnquote</th>
</tr>

<tr>
	<td>#Hochzeit</td>
	<td><? echo $hochzeit; ?></td>
	<td><? $rate = $hochzeit / $GLOBALS['games_played'];
		   $diff = $rate - $global_hochzeit_rate;
		   echo number_format($rate * 100, 3)." ";
		   highlight_diff($diff);
		?> </td>
	<td><? $diff = $hochzeit_win_rate - $global_hochzeit_win_rate;
		   echo number_format($hochzeit_win_rate * 100, 3)." ";
		   highlight_diff($diff);
		   echo "FIXME";
		?> </td> 
</tr>

<tr> 
<td>#Armut</td> 
	<td><? echo $armut; ?></td>
	<td><? $rate = $armut / $GLOBALS['games_played'];
		   $diff = $rate - $global_armut_rate;
		   echo number_format($rate * 100, 3)." ";
		   highlight_diff($diff);
		?> </td>
	<td><? $diff = $armut_win_rate - $global_armut_win_rate;
		   echo number_format($armut_win_rate * 100, 3)." ";
		   highlight_diff($diff);
		   echo "FIXME";
		?> </td>
</tr>

<tr> 
<td>#Schweine</td> 
	<td><? echo $schweine; ?></td>
	<td><? $rate = $schweine / $GLOBALS['games_played'];
		   $diff = $rate - $global_schweine_rate;
		   echo number_format($rate * 100, 3)." ";
		   highlight_diff($diff);
		?> </td>
	<td><? $diff = $schweine_win_rate - $global_schweine_win_rate;
		   echo number_format($schweine_win_rate * 100, 3)." ";
		   highlight_diff($diff);
		   echo "FIXME";
		?> </td>
</tr>

</table>
</div>
<div class="stats_box">
<?
$solo_stats = pg_exec("SELECT SUM(won) AS won, COUNT(won) AS number, " .
		$GLOBALS['gp_sql']." AS points FROM player_data WHERE " .
		"player_id = ".$player_id." AND game_type != 0 AND re = 1;");
$row = pg_fetch_array($solo_stats);
?>
<table>
<tr>
	<th>#Solospiele</th>
	<th>#Siege</th>
	<th>Punkte</th>
</tr>
<tr>
	<td><? echo $row['number']; ?></td>
	<td><? echo $row['won']; ?></td>
	<td><? echo $row['points']; ?></td>
</tr>
</table>
</div>
</div>
<?
$teams_query = "SELECT full_name, teams.player_id, wins, gp, teams.games " .
		"FROM players, " .
			"(SELECT player_id, pl1, SUM(won) AS wins, " .
			$GLOBALS['gp_sql']." AS gp, COUNT(player_id) AS games " .
			"FROM player_data, " .
				"players," .
				"(SELECT match_id AS mid1, game_round AS gr1, game_number AS gn1, " .
					"player_id AS pl1, re AS re1 " .
					"FROM player_data " .
					"WHERE player_id = ".$player_id." AND game_type = 0) AS pd1 " .
			"WHERE match_id = mid1 AND game_round = gr1 " .
				"AND game_number = gn1 AND re = re1 AND pl1 <> player_id AND " .
				"player_id = players.id AND game_type = 0 GROUP BY player_id, pl1) " .
				"AS teams " .
		"WHERE players.id = teams.player_id ORDER BY gp DESC";

//$start = (float) array_sum(explode(' ', microtime()));
$team_stats = pg_exec($teams_query);
//$end = (float) array_sum(explode(' ', microtime()));

//$time = $end - $start;
//echo "QUERY took $time seconds";
?>
<div style="clear:both">
<? /*echo $teams_query2; */ ?>
<div class="stats_box">
<h3>Team-Statistiken:</h3>
<table>
	<tr>
	        <th>Mitspieler</th>
	        <th>#Spiele</th>
	        <th>#Siege</th>
	        <th>Gewinnquote</th>
	        <th>Punkte</th>
	</tr>
<? while ($row = pg_fetch_array($team_stats)) { ?>
	<tr>
		<td><? echo "<a href=\"player_details.php?player_id=$row[player_id]&bock=$GLOBALS[bock]\">$row[full_name]</a>"; ?></td>
		<td><? echo $row[games] ?></td>
		<td><? echo $row[wins] ?></td>
		<td><? $team_win_rate = $row[wins] / $row[games];
		       $diff = $team_win_rate - $win_rate;
			   echo number_format($team_win_rate * 100, 3)." ";
			   highlight_diff($diff);
			?></td>
		<td><? echo $row[gp]; ?></td>
	</tr>
<? } ?>
</table>
</div>

<div class="stats_box">
<h3>Extrapunkte:</h3>                                
<table>
<tr>
	<th>Extrapunkt</th>
	<th>Anzahl</th>
	<th>Quote</th>
</tr>
<?
	print_extra_point_row('Doppelköpfe','doppelkopf');
    print_extra_point_row('Gefangene Charlies', 'charlie_caught');
    print_extra_point_row('Verlorene Charlies', 'charlie_lost');
    print_extra_point_row('Siegreiche Charlies', 'charlie_won');
    print_extra_point_row('Schweine gefangen', 'schweine_caught');
    print_extra_point_row('Schweine verloren', 'schweine_lost');
    print_extra_point_row('F&uuml;chse gefangen', 'fuechse_caught');
    print_extra_point_row('F&uuml;chse verloren', 'fuechse_lost');
   	print_extra_point_row('<b>Summe</b>', 'sum', true);
?>
</table>
</div>

</div>
<div style="clear:both; margin:10px">
<h3>Punkteverlauf:</h3>
<img src="history_graph.php?player_id=<?= $player_id ?>&bock=<?= $GLOBALS['bock'] ?>" alt="Punkteverlaufsgrafik"/>
</div>

</body>
</html>