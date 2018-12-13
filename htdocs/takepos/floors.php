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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN','1');
if (! defined('NOCSRFCHECK'))		define('NOCSRFCHECK','1');
if (! defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL','1');
if (! defined('NOREQUIREMENU'))		define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))		define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX','1');

$_GET['theme']="md"; // Force theme. MD theme provides better look and feel to TakePOS

require '../main.inc.php';	// Load $user and permissions

$langs->loadLangs(array("bills","orders","commercial","cashdesk"));

$floor=GETPOST('floor','alpha');
if ($floor=="") $floor=1;
$id = GETPOST('id','int');
$action = GETPOST('action','alpha');
$left = GETPOST('left','alpha');
$top = GETPOST('top','alpha');
$place = GETPOST('place','int');
$newname = GETPOST('newname');
$mode = GETPOST('mode','alpha');

if ($action=="getTables"){
    $sql="SELECT * from ".MAIN_DB_PREFIX."takepos_floor_tables where floor=".$floor;
    $resql = $db->query($sql);
    $rows = array();
    while($row = $db->fetch_array ($resql)){
        $rows[] = $row;
    }
    echo json_encode($rows);
    exit;
}

if ($action=="update")
{
    if ($left>95) $left=95;
    if ($top>95) $top=95;
    if ($left>3 or $top>4) $db->query("update ".MAIN_DB_PREFIX."takepos_floor_tables set leftpos=$left, toppos=$top where label='$place'");
    else $db->query("delete from ".MAIN_DB_PREFIX."takepos_floor_tables where label='$place'");
}

if ($action=="updatename")
{
	$newname = preg_replace("/[^a-zA-Z0-9\s]/", "", $newname); // Only English chars
	if (strlen($newname) > 3) $newname = substr($newname, 0, 3); // Only 3 chars
    $db->query("update ".MAIN_DB_PREFIX."takepos_floor_tables set label='$newname' where label='$place'");
}

if ($action=="add")
{
    $asdf=$db->query("insert into ".MAIN_DB_PREFIX."takepos_floor_tables values ('', '', '', '45', '45', $floor)");
	$db->query("update ".MAIN_DB_PREFIX."takepos_floor_tables set label=rowid where label=''"); // No empty table names
}

// Title
$title='TakePOS - Dolibarr '.DOL_VERSION;
if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $title='TakePOS - '.$conf->global->MAIN_APPLICATION_TITLE;
top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);
?>
<link rel="stylesheet" href="css/pos.css?a=xxx">
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
html, body
{
height: 100%;
}
</style>

<script>
var DragDrop='<?php echo $langs->trans("DragDrop"); ?>';

function updateplace(idplace, left, top) {
	$.ajax({
		type: "POST",
		url: "floors.php",
		data: { action: "update", left: left, top: top, place: idplace }
		}).done(function( msg ) {
		window.location.href='floors.php?mode=edit&floor=<?php echo $floor;?>';
	});
}

function updatename(before) {
	var after=$("#"+before).text();
	$.ajax({
		type: "POST",
		url: "floors.php",
		data: { action: "updatename", place: before, newname: after }
		}).done(function( msg ) {
		window.location.href='floors.php?mode=edit&floor=<?php echo $floor;?>';
		});
	}

function LoadPlace(place){
	parent.location.href='takepos.php?place='+place;
}


$( document ).ready(function() {
	$.getJSON('./floors.php?action=getTables&floor=<?php echo $floor; ?>', function(data) {
        $.each(data, function(key, val) {
			<?php if ($mode=="edit"){?>
			$('body').append('<div class="tablediv" contenteditable onblur="updatename('+val.label+');" style="position: absolute; left: '+val.leftpos+'%; top: '+val.toppos+'%;" id="'+val.label+'">'+val.label+'</div>');
			$( "#"+val.label ).draggable(
				{
					start: function() {
					$("#add").html("<?php echo $langs->trans("Delete"); ?>");
                    },
					stop: function() {
					var left=$(this).offset().left*100/$(window).width();
					var top=$(this).offset().top*100/$(window).height();
					updateplace($(this).attr('id'), left, top);
					}
				}
			);
			//simultaneous draggable and contenteditable
			$('#'+val.label).draggable().bind('click', function(){
				$(this).focus();
			})
			<?php }
			else {?>
			$('body').append('<div class="tablediv" onclick="LoadPlace('+val.label+');" style="position: absolute; left: '+val.leftpos+'%; top: '+val.toppos+'%;" id="'+val.label+'">'+val.label+'</div>');
			<?php } ?>
		});
	});
});

</script>
</head>
<body style="overflow: hidden">
<?php if ($user->admin){?>
<div style="position: absolute; left: 0.1%; top: 0.8%; width:8%; height:11%;">
<?php if ($mode=="edit"){?>
<a id="add" onclick="window.location.href='floors.php?mode=edit&action=add&floor=<?php echo $floor;?>';"><?php echo $langs->trans("AddTable"); ?></a>
<?php } else { ?>
<a onclick="window.location.href='floors.php?mode=edit&floor=<?php echo $floor;?>';"><?php echo $langs->trans("Edit"); ?></a>
<?php } ?>
</div>
<?php }
?>

<div style="position: absolute; left: 25%; bottom: 8%; width:50%; height:3%;">
    <center>
    <h1><img src="./img/arrow-prev.png" width="5%" onclick="location.href='floors.php?floor=<?php if ($floor>1) { $floor--; echo $floor; $floor++;} else echo "1"; ?>';"><?php echo $langs->trans("Floor")." ".$floor; ?><img src="./img/arrow-next.png" width="5%" onclick="location.href='floors.php?floor=<?php $floor++; echo $floor; ?>';"></h1>
    </center>
</div>
</body>
</html>