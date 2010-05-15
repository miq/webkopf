<HTML>
<HEAD>
<link rel="stylesheet" type="text/css" href="global_style.css" />
</HEAD>
<BODY>
<?
require_once("db.php");

function checkInputs() {
	$errors = array();
	if (!$_POST['pwd1'] || !$_POST['pwd2'] || !$_POST['full_name'] || !$_POST['login'] || !$_POST['nick']) {
		$errors[] = "Alle Felder müssen ausgefüllt werden";
	}
	if (strcmp($_POST['pwd1'], $_POST['pwd2']) != 0) {
		$errors[] = "Passwörter ungleich";
	}
	return $errors;
}
if ($_POST['add_player']) {
	$errors = checkInputs();
	foreach ($errors as $error) {
		echo "<font color=\"#FF0000\">$error</font><BR>";
	}
	if (count($errors) == 0) {
		/* trage spieler in db */
		$queryString = "insert into players values('$_POST[full_name]', '$_POST[nick]', '$_POST[login]','$_POST[pwd]')";
		$result = pg_exec($queryString);
		if ($result != FALSE) {
			echo "Spieler wurde erfolgreich eingetragen<BR>";
		} else {
			echo "Fehler beim Eintragen in die Datenbank<BR>";
		}
	}
}
?>

<FORM action="new_player.php" method="POST">
Name: <INPUT type="text" name="full_name" size="25" value="<? echo $_POST['full_name'];?>"/>
<BR/>
Spitzname: <INPUT type="text" name="nick" size="10" value="<? echo $_POST['nick'];?>"/>
<BR/>
Login: <INPUT type="text" name="login" size="10" value="<? echo $_POST['login'];?>"/>
<BR/>
Passwort: <INPUT type="password" name="pwd1" size="10"/>
<BR/>
Passwort: <INPUT type="password" name="pwd2" size="10"/>
<BR>
<INPUT type="submit" value="Spieler hinzuf&uuml;gen" name="add_player"/>
</FORM>
<a href="index.php">Zur&uuml;ck</a>

</BODY>
</HTML>