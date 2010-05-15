<?

function computeExtraPoints($fGefRe, $fGefCo, $cGefRe, $cGefCo, $cPunktRe, $cPunktCo, $dkRe, $dkCo) {
    // Extrapunkte
	$p = 0;
        $p += $fGefRe + $cGefRe + $cPunktRe + $dkRe;
        $p -= $fGefCo + $cGefCo + $cPunktCo + $dkCo;
	return $p;
}



function calculate($kpRe, $ansageRe, $ansageCo, $fGefRe, $fGefCo, $cGefRe, $cGefCo, $cPunktRe, $cPunktCo, $dkRe, $dkCo, $armut, $bock, $type)
{
//	echo "KP:$kpRe<BR>";
//	echo "AnsageRe:$ansageRe<BR>";
//	echo "AnsageKo:$ansageCo<BR>";
//	echo "fGefRe:$fGefRe<BR>";
//	echo "fGefCo:$fGefCo<BR>";
//	echo "cGefRe:$cGefRe<BR>";
//	echo "cGefCo:$cGefCo<BR>";
//	echo "cPunkteRe:$cPunktRe<BR>";
//	echo "cPunktCo:$cPunktCo<BR>";
//	echo "dkRe:$dkRe<BR>";
//	echo "dkCo:$dkCo<BR>";
//	echo "Armut:$armut<BR>";
//	echo "Bock:$Bock<BR>";

	$extraPoints = computeExtraPoints($fGefRe, $fGefCo, $cGefRe, $cGefCo, $cPunktRe, $cPunktCo, $dkRe, $dkCo);
	$ret = array();
	// Mauer-Regel:
	if ($ansageRe <= 0 && $ansageCo <= 0 && $type == 0) {
		if ($kpRe > 180) {
			$ret["game_points"] = -2 + $extraPoints;
			$ret["winner"] = "contra";
			if ($bock) {
				$ret['game_points'] *= 2;
			}
			return $ret;
		} else if ($kpRe < 60) {
			$ret["game_points"] = 1 + $extraPoints;
			$ret["winner"] = "re";
			if ($bock) {
				$ret['game_points'] *= 2;
			}
			return $ret;
		}
		
	}

	if ($kpRe > 120 && ((240 - $kpRe) < $ansageRe || $ansageRe <= 0 )
			|| ($kpRe == 120 && $ansageCo >= 0)
			|| ($kpRe >= $ansageCo && $ansageCo >= 0)) {
		/* re hat gewonnen */
		$punkteRe = 1;
		$inc = 1;
		$ret["winner"] = "re";
	} else {
 		/* re hat verloren */
  		$ret["winner"] = "contra";
		/* armut oder solo wurde gespielt, kein gegen die alten */
		if ($armut || $type != 0) {
			$punkteRe = -1;
		} else {
			/* normales spiel, gegen die alten */
			$punkteRe = -2;
		}
		$inc = -1;
	}
	

	$k = 90;
	/* Punkte durch Ansagen: */
	while ($k >= 0) {
		if (($ansageRe <= $k) && ($ansageRe > 0)) {
			$punkteRe += $inc;
		}
		if (($ansageCo <= $k) && ($ansageCo > 0)) {
			$punkteRe += $inc;
		}
		$k -= 30;
	}

	/* Punkte für Re durch mehr als 150 Punkte (keine 90, keine 60, keine 30, schwarz) */
	$k = 150;
	while ($kpRe > $k && $k <= 240 && $ret["winner"] == "re") {
		$punkteRe++;
		$k += 30;
	}

	/* Punkt für schwarz */
	if ($kpRe == 240) {
		$punkteRe++;
	}

	/* Punkte für Re, wenn Kontra-Party Ansage verloren hat */
	if (($ansageCo > 0) && ($kpRe >= $ansageCo)) {
		$k = $ansageCo;              
		while ($k < 120) {
			$punkteRe++;
			$k += 30;   
		}
	}


	/* Punkte gegen Re, durch weniger als 90,60,30,0 Punkte */
	$k = 90;       
	while ($kpRe < $k && $k >= 0 && $ret["winner"] == "contra") {
		$punkteRe--;
		$k -= 30;
	}

	/* Punkt für schwarz */
	if ($kpRe == 0) {
		$punkteRe--;
	}

	/* Punkte für Kontra, wenn Re-Party Ansage verloren hat */
	if (($ansageRe > 0) && (240 - $kpRe >= $ansageRe)){
		$k = 120 - $ansageRe;
		while ($k > 0) {
			$punkteRe--;
			$k -= 30;   
		}
	}  
	
	if ($ansageRe > 0) {
		$punkteRe *= 2;
	}
	if ($ansageCo > 0) {
		$punkteRe *= 2;
	}

	// Extrapunkte
	$punkteRe += $extraPoints;

	if ($bock) {
		$punkteRe *= 2;
	}
	$ret["game_points"] = $punkteRe;
	return $ret;
}
?>