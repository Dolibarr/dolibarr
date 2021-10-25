<?php
/* Copyright (C) 2018	Andreu Bisquerra	<jove@bisquerra.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/takepos/floors.php
 *	\ingroup    takepos
 *	\brief      Page to edit floors and tables.
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))	define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))	define('NOREQUIRETRAN','1');
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

require '../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$langs->loadLangs(array("bills", "orders", "commercial", "cashdesk"));

$floor = GETPOST('floor', 'int');
if ($floor == "") {
	$floor = 1;
}
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$left = GETPOST('left', 'alpha');
$top = GETPOST('top', 'alpha');

$place = (GETPOST('place', 'aZ09') ? GETPOST('place', 'aZ09') : 0); // $place is id of table for Ba or Restaurant

$newname = GETPOST('newname', 'alpha');
$mode = GETPOST('mode', 'alpha');

if (empty($user->rights->takepos->run)) {
	accessforbidden();
}


/*
 * Actions
 */

if ($action == "getTables") {
	$sql = "SELECT rowid, entity, label, leftpos, toppos, floor FROM ".MAIN_DB_PREFIX."takepos_floor_tables where floor = ".((int) $floor);
	$resql = $db->query($sql);
	$rows = array();
	while ($row = $db->fetch_array($resql)) {
		$invoice = new Facture($db);
		$result = $invoice->fetch('', '(PROV-POS'.$_SESSION['takeposterminal'].'-'.$row['rowid'].')');
		if ($result > 0) {
			$row['occupied'] = "red";
		}
		$rows[] = $row;
	}
	echo json_encode($rows);
	exit;
}

if ($action == "update") {
	if ($left > 95) {
		$left = 95;
	}
	if ($top > 95) {
		$top = 95;
	}
	if ($left > 3 or $top > 4) {
		$db->query("UPDATE ".MAIN_DB_PREFIX."takepos_floor_tables set leftpos=".$left.", toppos=".$top." WHERE rowid='".$place."'");
	} else {
		$db->query("DELETE from ".MAIN_DB_PREFIX."takepos_floor_tables where rowid='".$place."'");
	}
}

if ($action == "updatename") {
	$newname = preg_replace("/[^a-zA-Z0-9\s]/", "", $newname); // Only English chars
	if (strlen($newname) > 3) {
		$newname = substr($newname, 0, 3); // Only 3 chars
	}
	$db->query("UPDATE ".MAIN_DB_PREFIX."takepos_floor_tables set label='".$db->escape($newname)."' WHERE rowid='".$place."'");
}

if ($action == "add") {
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."takepos_floor_tables(entity, label, leftpos, toppos, floor) VALUES (".$conf->entity.", '', '45', '45', ".$floor.")";
	$asdf = $db->query($sql);
	$db->query("update ".MAIN_DB_PREFIX."takepos_floor_tables set label=rowid where label=''"); // No empty table names
}


/*
 * View
 */

// Title
$title = 'TakePOS - Dolibarr '.DOL_VERSION;
if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
	$title = 'TakePOS - '.$conf->global->MAIN_APPLICATION_TITLE;
}
top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);
?>
<link rel="stylesheet" href="css/pos.css.php?a=xxx">
<style type="text/css">
div.tablediv{
background-image:url(img/table.gif);
-moz-background-size:100% 100%;
-webkit-background-size:100% 100%;
background-size:100% 100%;
height:10%;
width:10%;
text-align: center;
font-size:300%;
color:white;
}
div.red{
color:red;
}
html, body
{
height: 100%;
}
</style>

<script>
var DragDrop='<?php echo $langs->trans("DragDrop"); ?>';

function updateplace(idplace, left, top) {
	console.log("updateplace idplace="+idplace+" left="+left+" top="+top);
	$.ajax({
		type: "POST",
		url: "<?php echo DOL_URL_ROOT.'/takepos/floors.php'; ?>",
		data: { action: "update", left: left, top: top, place: idplace, token: '<?php echo currentToken(); ?>' }
	}).done(function( msg ) {
		window.location.href='floors.php?mode=edit&floor=<?php echo urlencode($floor); ?>';
	});
}

function updatename(rowid) {
	var after=$("#tablename"+rowid).text();
	console.log("updatename rowid="+rowid+" after="+after);
	$.ajax({
		type: "POST",
		url: "<?php echo DOL_URL_ROOT.'/takepos/floors.php'; ?>",
		data: { action: "updatename", place: rowid, newname: after, token: '<?php echo currentToken(); ?>' }
	}).done(function( msg ) {
		window.location.href='floors.php?mode=edit&floor=<?php echo urlencode($floor); ?>';
	});
}

function LoadPlace(place){
	parent.location.href='index.php?place='+place;
}


$( document ).ready(function() {
	$.getJSON('./floors.php?action=getTables&floor=<?php echo $floor; ?>', function(data) {
		$.each(data, function(key, val) {
			<?php if ($mode == "edit") {?>
			$('body').append('<div class="tablediv" contenteditable onblur="updatename('+val.rowid+');" style="position: absolute; left: '+val.leftpos+'%; top: '+val.toppos+'%;" id="tablename'+val.rowid+'">'+val.label+'</div>');
			$( "#tablename"+val.rowid ).draggable(
				{
					start: function() {
					$("#add").html("<?php echo $langs->trans("Delete"); ?>");
					},
					stop: function() {
					var left=$(this).offset().left*100/$(window).width();
					var top=$(this).offset().top*100/$(window).height();
					updateplace($(this).attr('id').substr(9), left, top);
					}
				}
			);
			//simultaneous draggable and contenteditable
			$('#'+val.label).draggable().bind('click', function(){
				$(this).focus();
			})
			<?php } else {?>
			$('body').append('<div class="tablediv '+val.occupied+'" onclick="LoadPlace('+val.rowid+');" style="position: absolute; left: '+val.leftpos+'%; top: '+val.toppos+'%;" id="tablename'+val.rowid+'">'+val.label+'</div>');
			<?php } ?>
		});
	});
});

</script>
</head>
<body style="overflow: hidden">
<?php if ($user->admin) {?>
<div style="position: absolute; left: 0.1%; top: 0.8%; width:8%; height:11%;">
	<?php if ($mode == "edit") {?>
<a id="add" onclick="window.location.href='floors.php?mode=edit&action=add&floor=<?php echo $floor; ?>';"><?php echo $langs->trans("AddTable"); ?></a>
	<?php } else { ?>
<a onclick="window.location.href='floors.php?mode=edit&floor=<?php echo $floor; ?>';"><?php echo $langs->trans("Edit"); ?></a>
	<?php } ?>
</div>
<?php }
?>

<div style="position: absolute; left: 25%; bottom: 8%; width:50%; height:3%;">
	<center>
	<h1>
	<?php if ($floor > 1) { ?>
	<img class="valignmiddle" src="./img/arrow-prev.png" width="5%" onclick="location.href='floors.php?floor=<?php if ($floor > 1) {
		$floor--; echo $floor; $floor++;
																											 } else {
																												 echo "1";
																											 } ?>';">
	<?php } ?>
	<span class="valignmiddle"><?php echo $langs->trans("Floor")." ".$floor; ?></span>
	<img src="./img/arrow-next.png" class="valignmiddle" width="5%" onclick="location.href='floors.php?floor=<?php $floor++; echo $floor; ?>';">
	</h1>
	</center>
</div>
</body>
</html>
