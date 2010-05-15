<?php
define("SQL_GP_WITH_BOCK", " SUM(game_points) ");
define("SQL_GP_WITHOUT_BOCK", " SUM(CASE WHEN bock THEN game_points / 2 ELSE game_points END) ");


function highlight_diff($difference)
{
	echo "(";
	if ($difference > 0) {
		echo "<span style=\"color:green\">+";
	} else if ($difference < 0) {
		echo "<span style=\"color:red\">";
	}
	echo number_format($difference * 100, 3);
	if ($difference != 0) {
		echo "</span>";
	}
	echo ")";
}

function table_sort($array, $key, $order = "DESC")
{
   for ($i = 0; $i < sizeof($array); $i++) {
       $sort_values[$i] = $array[$i][$key];
   }
   if ($order == "DESC") {
   		arsort($sort_values);
   } else {
   		asort($sort_values);
   }
   reset($sort_values);
   while (list ($arr_key, $arr_val) = each($sort_values)) {
         $sorted_arr[] = $array[$arr_key];
   }
   return $sorted_arr;
}

?>