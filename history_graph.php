<?
require_once("db.php");
require_once("utilities.php");

if ($_REQUEST['bock']) {
	$GLOBALS['gp_sql'] = "game_points";
} else {
	$GLOBALS['gp_sql'] = "CASE WHEN bock THEN game_points / 2 ELSE game_points END";
} 

$points = array();
$player_id = $_GET['player_id'];
$games = 0;

$player_stats = pg_exec("SELECT ".$GLOBALS['gp_sql']." FROM player_data WHERE " .
		"player_id = ".$player_id." ORDER BY match_id, game_number");
		
while ($row = pg_fetch_array($player_stats)) {
	$points[] = $row['game_points'];
	$games++;
}

$min_points = 0;
$max_points = 0;
$curr_points = 0;
foreach ($points as $p) {
	$curr_points += $p;
	if ($curr_points > $max_points) {
		$max_points = $curr_points;
	} else if ($curr_points < $min_points) {
		$min_points = $curr_points;
	}
}
$im = imagecreatetruecolor(800, 600);
$size_x = imagesx($im);
$size_y = imagesy($im);

$x_scale = ($size_x - 50) / $games;
$y_scale = 1;

$max = max(abs($max_points), abs($min_points));
if ($max > ($size_y / 2 - 20)) {
	$y_scale = ($size_y / 2 - 20) / $max;
}


$bg = imagecolorallocate($im, 250, 250, 250);
$gray = imagecolorallocate($im, 180, 180, 180);
$black = imagecolorallocate($im, 0, 0, 0);

$red = imagecolorallocate($im, 200, 0, 0);
$slight_green = imagecolorallocate($im, 150, 200, 0);
$yellow = imagecolorallocate($im, 200, 200, 0);
$orange = imagecolorallocate($im, 220, 150, 0);
$green = imagecolorallocate($im, 0, 160, 0);

imagefill($im, 0, 0, $bg);
imagerectangle($im, 0,0, $size_x - 1, imagesy($im) - 1, $black);

// baseline
imagestring($im, 5, 3, $size_y / 2 - 5, "0", $black);
imageline($im, 50, $size_y / 2, $size_x - 1, $size_y / 2, $black);

imagestring($im, 5, 50, 0, "Aktuell: $curr_points", $black);
imagestring($im, 5, 200, 0, "Maximum: $max_points", $green);
imagestring($im, 5, 350, 0, "Minimum: $min_points", $red);

// one line every 100 points
$i = 100;
while ($i * $y_scale < ($size_y / 2 - 20)) {
	$y = $i * $y_scale;
	imagestring($im, 5, 3, $size_y / 2 - 5 - $y, "+".$i, $black);
	imageline($im, 50, $size_y / 2 - $y, $size_x - 1, $size_y / 2 -$y, $gray);
	imagestring($im, 5, 3, $y + $size_y / 2 - 5, "-".$i, $black);
	imageline($im, 50, $size_y / 2 + $y, $size_x - 1, $size_y / 2 + $y, $gray);
	$i += 100;
}

$sum = $points['0'];
for ($i = 1; $i < sizeof($points); $i++) {
	$x = ($i - 1) * $x_scale + 50;
	$old_sum = $sum;
	$sum += $points[$i];
	$draw_color = $yellow;
	if ($sum - $old_sum > 15) {
		$draw_color = $green;
	} else if ($sum - $old_sum > 0) {
		$draw_color = $slight_green;
	} else if ($sum - $old_sum < -15) {
		$draw_color = $red;
	} else if ($sum - $old_sum < 0) {
		$draw_color = $orange;
	}
//	echo "X-values: $x";
	imageline($im, $x, $size_y / 2 - $old_sum * $y_scale, $x + $x_scale, $size_y / 2 - $sum * $y_scale, $draw_color);
}

header('Content-type: image/png');
imagePNG($im);
imageDestroy($im);

?>