<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');

$db = connect_db();
$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(isset($_REQUEST['tijd']) AND $_REQUEST['tijd'] == 'jaar') {
	$startdag = mktime(0, 0, 0, date("n"), date("j")-1, date("Y")-1);
	$einddag	= mktime(0, 0, 0, date("n"), date("j")-2, date("Y"));
} elseif(isset($_REQUEST['tijd']) AND $_REQUEST['tijd'] == 'kwartaal') {
	$startdag = mktime(0, 0, 0, date("n")-3, date("j")-1, date("Y"));
	$einddag	= mktime(0, 0, 0, date("n"), date("j")-2, date("Y"));
} elseif(isset($_REQUEST['tijd']) AND $_REQUEST['tijd'] == 'maand') {
	$startdag = mktime(0, 0, 0, date("n")-1, date("j")-1, date("Y"));
	$einddag	= mktime(0, 0, 0, date("n"), date("j")-2, date("Y"));	
} elseif(isset($_REQUEST['tijd']) AND $_REQUEST['tijd'] == 'dag') {
	$startdag = mktime(0, 0, 0, date("n"), date("j")-3, date("Y"));
	$einddag	= mktime(0, 0, 0, date("n"), date("j")-2, date("Y"));	
} else {
	$startdag = mktime(0, 0, 0, date("n"), date("j")-9, date("Y"));
	$einddag	= mktime(0, 0, 0, date("n"), date("j")-2, date("Y"));
}

$bDag			= getParam('bDag', date("d", $startdag));
$bMaand		= getParam('bMaand', date("m", $startdag));
$bJaar		= getParam('bJaar', date("Y", $startdag));
$eDag			= getParam('eDag', date("d", $einddag));
$eMaand 	= getParam('eMaand', date("m", $einddag));
$eJaar		= getParam('eJaar', date("Y", $einddag));
$selectie	= getParam('selectie', '');

$HTML = $Debug = array();
if(!isset($_POST['submit']) AND !isset($_REQUEST['id'])) {
	$dateSelection = makeDateSelection('','',$bDag,$bMaand,$bJaar , '','',$eDag,$eMaand,$eJaar);
		
	$HTML[] = "<form method='post' action='". $_SERVER['PHP_SELF'] ."'>";
	$HTML[] = "<input type='hidden' name='datum' value='1'>";
	$HTML[] = "<table>";
	$HTML[] = "<tr>";
	$HTML[] = "	<td>Begin Datum</td>";
	$HTML[] = "	<td>&nbsp;</td>";
	$HTML[] = "	<td>Eind Datum</td>";
	$HTML[] = "	<td>&nbsp;</td>";
	$HTML[] = "	<td>Groep</td>";
	$HTML[] = "	<td>&nbsp;</td>";
	$HTML[] = "	<td rowspan='2'><input type='submit' name='submit' value='Weergeven'></td>";
	$HTML[] = "</tr>";
	$HTML[] = "<tr>";
	$HTML[] = "	<td>". $dateSelection[0] ."</td>";
	$HTML[] = "	<td>&nbsp;</td>";
	$HTML[] = "	<td>". $dateSelection[1] ."</td>";
	$HTML[] = "	<td>&nbsp;</td>";
	$HTML[] = "	<td>". makeSelectionSelection(true, true) ."</td>";
	$HTML[] = "	<td>&nbsp;</td>";
	$HTML[] = "</tr>";
	$HTML[] = "	<td colspan=7><a href='". $_SERVER['PHP_SELF'] ."?tijd=dag'>dag</a> | <a href='". $_SERVER['PHP_SELF'] ."?tijd=week'>week</a> | <a href='". $_SERVER['PHP_SELF'] ."?tijd=maand'>maand</a> | <a href='". $_SERVER['PHP_SELF'] ."?tijd=kwartaal'>kwartaal</a> | <a href='". $_SERVER['PHP_SELF'] ."?tijd=jaar'>jaar</a></td>\n";
	$HTML[] = "</tr>";
	$HTML[] = "</table>";
	$HTML[] = "</form>";
} else {
	$opdracht	= substr($_REQUEST['selectie'], 1);
	
	if(isset($_REQUEST['id'])) {
		$sql_array[] = "SELECT * FROM $TableHuizen WHERE $HuizenID like ". $_REQUEST['id'];
		$HTML[] = '<h1>'. $_REQUEST['id'] ."</h1><br>\n";
	} elseif(isset($_POST['submit'])) {		
		$beginGrens = mktime(0, 0, 0, $_POST['bMaand'], $_POST['bDag'], $_POST['bJaar']);
		$eindGrens	= mktime(23, 59, 59, $_POST['eMaand'], $_POST['eDag'], $_POST['eJaar']);
		$titel = 'Huizen voor het laatst gezien tussen '. date('d-m-y', $beginGrens) .' en '. date('d-m-y', $eindGrens);
				
		$sql_array[] = "SELECT * ";
		$sql_array[] = "FROM $TableVerdeling, $TableHuizen, $TableResultaat ";
		$sql_array[] = "WHERE ";
		$sql_array[] = "$TableResultaat.$ResultaatZoekID = $TableVerdeling.$VerdelingOpdracht AND ";
		$sql_array[] = "$TableResultaat.$ResultaatID = $TableHuizen.$HuizenID AND ";
		$sql_array[] = "$TableHuizen.$HuizenVerkocht NOT like '1' AND";
		//$sql_array[] = "$TableHuizen.$HuizenVerkocht like '1' AND";
		$sql_array[] = "$TableHuizen.$HuizenOffline like '0' AND";
		
		if($_REQUEST['selectie'] != '') {
			$OpdrachtData = getOpdrachtData($opdracht);
			$sql_array[] = "$TableResultaat.$ResultaatZoekID like '$opdracht' AND";
			$titel .= " voor ". $OpdrachtData['naam'];
		}
		
		$sql_array[] = "(($TableHuizen.$HuizenEind BETWEEN $beginGrens AND $eindGrens))";
		$sql_array[] = "GROUP BY $TableHuizen.$HuizenID";
				
		$HTML[] = "<h1>$titel</h1><br>\n";
	}
	
	$sql = implode(" ", $sql_array);	
	$result	= mysqli_query($db, $sql);
	
	$Debug[] = implode("<br>\n", $sql_array) ."<br>\n";  
	$Debug[] = mysqli_num_rows($result) ." resultaten<br>\n";  
		
	$result	= mysqli_query($db, $sql);	
	if($row = mysqli_fetch_array($result)) {
		do {
			$url = 'http://www.funda.nl/'.$row[$HuizenID];
			
			$HTML[] = '<b>'. urldecode($row[$HuizenAdres]) ."</b> (". urldecode($row[$HuizenPlaats]) .")<br>";
			$HTML[] = "[van ". date("d-m-Y", $row[$HuizenStart]) ." tot ". date("d-m-Y", $row[$HuizenEind]) ."]<br>";
			$HTML[] = "<a href='$url' target='funda_huis'>funda.nl</a> | <a href='edit.php?id=". $row[$HuizenID] ."' target='funda_detail'>details</a> | zet <a href='changeState.php?state=offline&id=". $row[$HuizenID] ."' target='funda_state'>offline</a>, <a href='changeState.php?state=verkocht&id=". $row[$HuizenID] ."' target='funda_state'>verkocht</a><br>";
			
			if($row[$HuizenOffline] != 0) {
				$HTML[] = ' -> niet aan beginnen, is offline<br>';
			}

		} while($row = mysqli_fetch_array($result));
	}
}

echo $HTMLHeader;
echo "<tr>\n";
if(count($Debug) == 0) {
	echo "<td width='8%'>&nbsp;</td>\n";
	echo "<td width='84%' valign='top' align='center'>\n";
	echo showBlock(implode("\n", $HTML));
	echo "</td>\n";
	echo "<td width='8%'>&nbsp;</td>\n";
	echo "</tr>\n";
} else {
	echo "<td width='50%' valign='top' align='center'>\n";
	echo showBlock(implode("\n", $HTML));
	echo "</td>";
	echo "<td width='50%' valign='top' align='center'>\n";
	echo showBlock(implode("\n", $Debug));
	echo "</td>";
}
echo "</tr>\n";
echo $HTMLFooter;