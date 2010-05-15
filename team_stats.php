<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="de" xml:lang="en">

<head>
<title>Webkopf - Teamstatistiken</title>
<link rel="stylesheet" type="text/css" href="global_style.css" />
</head>
<body>
<div class="controlbar">
<a href="index.php">&Uuml;bersichtsseite</a>
<a href="stats.php">Spielerstatistiken</a>
</div>
<?
require_once("db.php");
require_once("utilities.php");

$query_result = pg_exec("SELECT COUNT(won) AS count, SUM(won) AS re_wins, COUNT(won) - SUM(won) AS contra_wins FROM games");
$row = pg_fetch_array($query_result);
$game_count = $row["count"];
$re_wins = $row["re_wins"];
$contra_wins = $row["contra_wins"];

$announcement_globals = pg_exec("SELECT COUNT(announcement) AS announcements, " .
		"SUM(won) AS wins " .
		"FROM game_properties, player_data " .
		"WHERE game_props = game_properties.id AND game_properties.announcement <> 0;");

$row = pg_fetch_array($announcement_globals);
$announcement_count = $row["announcements"];
$announcement_wins = $row["wins"];
$announcement_average = ($announcement_count / $game_count) / 4;
$announcement_success_average = ($announcement_wins / $announcement_count);

$player_result=pg_exec("select * from players");


while ($row = pg_fetch_array($player_result)) {
	$GLOBALS[$users][$row["id"]] = $row["full_name"];
}

function get_team_table_data()
{
	$global_stats = pg_exec("SELECT  p1,
        p2,
        SUM(game_points) AS game_points,
        SUM(1) AS games,
        SUM(wins) AS wins,
        SUM(re) AS re,".
  //      SUM(contra) AS contra,
        "SUM(announcement_number) AS announcement_number,
        SUM(won_announcements) AS won_announcements,
        SUM(lost_announcements) AS lost_announcements
        FROM (SELECT DISTINCT
                CASE WHEN player1.player_id < player2.player_id
                        THEN player1.player_id
                        ELSE player2.player_id END AS p1,
                CASE WHEN player1.player_id > player2.player_id
                        THEN player1.player_id
                        ELSE player2.player_id END AS p2,
                player1.game_points AS game_points,
                player1.re AS re,
         " .
         	//	"       player1.contra AS contra,
                
                "player1.won AS wins,
                player1.game_date,
                player1.game_number,
                CASE WHEN player1.announcement + player2.announcement <> 0 THEN 1 END AS announcement_number,
                CASE WHEN player1.announcement + player2.announcement <> 0 AND player1.won = 1 THEN 1 END AS won_announcements,
                CASE WHEN player1.announcement + player2.announcement <> 0 AND player1.won = 0 THEN 1 END AS lost_announcements
                FROM (SELECT player_id,
                		        game_points,
                                game_date,
                                game_number,
                                game_props,
                                re, CASE WHEN re = 0 THEN 1 END as contra, won,
                                announcement
                                FROM player_data, game_properties
                                WHERE game_props = game_properties.id)
                                AS player1,
                      (SELECT player_id,
                                game_date,
                                game_number,
                                game_props,
                                re,
                                announcement
                                FROM player_data, game_properties
                                WHERE game_props = game_properties.id)
                                AS player2
                WHERE player1.game_date = player2.game_date
                        AND player1.game_number = player2.game_number
                        AND player1.re = player2.re
                        AND player1.player_id <> player2.player_id)
        AS teams
 
GROUP BY p1, p2;");


		
while ($row = pg_fetch_array($global_stats)) {
		$row['p1_name'] = $GLOBALS[$users][$row['p1']];
		$row['p2_name'] = $GLOBALS[$users][$row['p2']];
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
?>
<div>
<h3>Team&uuml;bersicht</h3>
<table>

<tr>
	<th><a href="team_stats.php?pt_sort=p1_name">Spieler1</a></th>
	<th><a href="team_stats.php?pt_sort=p2_name">Spieler2</a></th>
	<th><a href="team_stats.php?pt_sort=game_points">&Sigma; Punkte</a></th>
	<th><a href="team_stats.php?pt_sort=games">#Spiele</a></th>
	<th><a href="team_stats.php?pt_sort=wins">#Siege</a></th>
	<th><a href="team_stats.php?pt_sort=win_rate">Gewinn %</a></th>
	<th><a href="team_stats.php?pt_sort=points_per_game">Pkt./Spiel</a></th>
	<th><a href="team_stats.php?pt_sort=re_rate">Re %</a></th>
	<th><a href="team_stats.php?pt_sort=announce_rate">Ansagen %</a></th>
	<th><a href="team_stats.php?pt_sort=announce_success_rate">Gew. Ansagen %</a></th>
</tr>

<? 
   	$pt_data = get_team_table_data();
   	$order = "DESC";
   	if (isset($_GET["pt_sort"])) {
   		if ($_GET["pt_sort"] == "p1_name" || $_GET["pt_sort"] == "p2_name") {
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
	echo "<a href=\"player_details.php?player_id=$row[p1]\">";
	echo "$row[p1_name]";
	echo "</a>";
?>
</td>
<td>
<?
	echo "<a href=\"player_details.php?player_id=$row[p2]\">";
	echo "$row[p2_name]";
	echo "</a>";
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
<?
}

?>
</table>
</div>
</body>
</html>
