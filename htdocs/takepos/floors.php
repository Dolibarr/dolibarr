<?php
/* Copyright (C) 2018	Andreu Bisquerra	<jove@bisquerra.com>
 * Copyright (C) 2019	JC Prieto			<jcprieto@virtual20.com>
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
if (! defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (! defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (! defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');

$_GET['theme']="md"; // Force theme. MD theme provides better look and feel to TakePOS

require '../main.inc.php';	// Load $user and permissions

$langs->loadLangs(array("bills","orders","commercial","cashdesk"));
$langs->load('takepos@takepos');	//V20

//V20: Terminal
$term=$_SESSION['term'];

$floor=GETPOST('floor', 'alpha');
if ($floor=="") $floor=$term;
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$left = GETPOST('left', 'alpha');
$top = GETPOST('top', 'alpha');
$place = GETPOST('place', 'int');
$newname = GETPOST('newname');
$mode = GETPOST('mode', 'alpha');

$selectobject= GETPOST('selectobject', 'alpha');	//v20

if ($action=="getTables"){

	//V20: Better
	$sql="SELECT f.rowid as facid, f.total_ttc, fe.diner, t.* FROM ".MAIN_DB_PREFIX."takepos_floor_tables as t ".
		"LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON f.facnumber=concat('(PROV-POS-',t.rowid,')') ".
		"LEFT JOIN ".MAIN_DB_PREFIX."facture_extrafields as fe ON f.rowid=fe.fk_object ".
		"WHERE t.floor=".$floor;	//V20
	$resql = $db->query($sql);
    $rows = array();
    while($row = $db->fetch_array ($resql)){
	    if(!is_null($row['facid']) && ($row['total_ttc']>0 || $row['diner']>0)){	//V20: means busy
			$row['total_ttc']=price($row['total_ttc'],0,'',1,2,2,'auto');	//V20: 2 decimals
			$row['busy']=true;
		}else{
			$row['total_ttc']='';
			$row['busy']=false;
		}
		if(is_null($row['diner']))	$row['diner']='';
		$rows[] = $row;
    }
    
    echo json_encode($rows);
    exit;
}

if ($action=="update")
{
    if ($left>95) $left=95;
    if ($top>95) $top=95;
    if ($left>3 or $top>4) $db->query("update ".MAIN_DB_PREFIX."takepos_floor_tables set leftpos=$left, toppos=$top where rowid='$place'");
    else $db->query("delete from ".MAIN_DB_PREFIX."takepos_floor_tables where rowid='$place'");
}

if ($action=="updatename")
{
	$newname = preg_replace("/[^a-zA-Z0-9\s]/", "", $newname); // Only English chars
	if (strlen($newname) > 10) $newname = substr($newname, 0, 10); // Only 10 chars	V20: antes solo 3!!
    $db->query("update ".MAIN_DB_PREFIX."takepos_floor_tables set label='$newname' where rowid='$place'");
}

if ($action=="add")
{
    //$asdf=$db->query("insert into ".MAIN_DB_PREFIX."takepos_floor_tables values ('', '', '', '45', '45', $floor)");
    $asdf=$db->query("insert into ".MAIN_DB_PREFIX."takepos_floor_tables (entity,label,leftpos,toppos,floor,object) values (1, '', '45', '45', '$floor','$selectobject')");	//V20
	$db->query("update ".MAIN_DB_PREFIX."takepos_floor_tables set label=rowid where label=''"); // No empty table names
}

// Title
$title='TakePOS - Dolibarr '.DOL_VERSION;
if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $title='TakePOS - '.$conf->global->MAIN_APPLICATION_TITLE;
top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);
?>
<link rel="stylesheet" href="css/pos.css?a=xxx">
<style type="text/css">

/*V20: CSS for objects */
div.barhdiv{
background-image:url(img/bar.jpg);
-moz-background-size:100% 100%;
-webkit-background-size:100% 100%;
background-size:100% 100%;
height:5%;
width:20%;  /*V20. Before 10%*/
text-align: center;
font-size:120%; 
color:white;
}
div.barvdiv{
background-image:url(img/bar.jpg);
-moz-background-size:100% 100%;
-webkit-background-size:100% 100%;
background-size:100% 100%;
height:25%;
width:4%;   /*V20. Antes 10%*/
text-align: center;
font-size:120%; 
color:white;
}
div.chairdiv{
background-image:url(img/chair.png);
-moz-background-size:100% 100%;
-webkit-background-size:100% 100%;
background-size:100% 100%;
height:5%;
width:4%;   /*V20. Antes 10%*/
text-align: center;
font-size:120%; 
color:black;
}
div.grassdiv{
background-image:url(img/grass.png);
-moz-background-size:100% 100%;
-webkit-background-size:100% 100%;
background-size:100% 100%;
height:5%;
width:4%;   /*V20. Antes 10%*/
text-align: center;
font-size:120%; 
color:white;
}
div.walldiv{
background-image:url(img/wall.jpg);
-moz-background-size:100% 100%;
-webkit-background-size:100% 100%;
background-size:100% 100%;
height:5%;
width:4%;   /*V20. Antes 10%*/
text-align: center;
font-size:120%; 
color:white;
}
div.tablediv{
background-image:url(img/table.gif);
-moz-background-size:100% 100%;
-webkit-background-size:100% 100%;
background-size:100% 100%;
height:10%;
width:6%;   /*V20. Antes 10-6*/
text-align: center;
font-size:120%; 
color:white;
}
div.table2div{
background-image:url(img/table2.png);
-moz-background-size:100% 100%;
-webkit-background-size:100% 100%;
background-size:100% 100%;
height:10%;
width:6%;   /*V20. Antes 10-6*/
text-align: center;
font-size:120%; 
color:black;
}
div.tableBdiv{
background-image:url(img/table.gif);
-moz-background-size:100% 100%;
-webkit-background-size:100% 100%;
background-size:100% 100%;
height:24%;
width:8%;   /*V20. Antes 10-6*/
text-align: center;
font-size:120%; 
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
	parent.$.colorbox.close();
	
	<?php 
	if($action=='movetable'){
	?>
		$.ajax({
			type: "POST",
			url: "invoice.php",
			data: { action: "movetable", newplace: place}
		});
		parent.$("#takepos").load("takepos.php?place="+place);
	<?php
	}
	else 		echo 'parent.$("#takepos").load("takepos.php?place="+place);';
	?>
	

}


$( document ).ready(function() {
	$.getJSON('./floors.php?action=getTables&floor=<?php echo $floor; ?>', function(data) {
        $.each(data, function(key, val) {
            //V20: Objects. TODO: Need improve.
            var object='';
            var busy='';
            var mode="<?php  echo $mode;?>";
            if (mode=="edit")	mode='contenteditable onblur="updatename(';
            else 				mode='onclick="LoadPlace(';
            
			if(val.busy)	busy='background-color: magenta; ';

			if(val.object=='barv'){
	            	object='<div class="barvdiv" '+mode+val.rowid+');" style="'+busy+
							'position: absolute; left: '+val.leftpos+'%; top: '+val.toppos+'%;" id="'+val.rowid+'"></div>';	
			}
			else if(val.object=='barh'){
            	object='<div class="barhdiv" '+mode+val.rowid+');" style="'+busy+
						'position: absolute; left: '+val.leftpos+'%; top: '+val.toppos+'%;" id="'+val.rowid+'"></div>';	
			}
			else if(val.object=='chair'){
            	object='<div class="chairdiv" '+mode+val.rowid+');" style="'+busy+
	            	'position: absolute; left: '+val.leftpos+'%; top: '+val.toppos+'%;" id="'+val.rowid+'">'
					+val.label+'<br>'+val.total_ttc+'<br>'+val.diner+'</div>';
			}
			else if(val.object=='grass'){
            	object='<div class="grassdiv" '+mode+val.rowid+');" style="'+busy+
						'position: absolute; left: '+val.leftpos+'%; top: '+val.toppos+'%;" id="'+val.rowid+'"></div>';	
			}
			else if(val.object=='wall'){
            	object='<div class="walldiv" '+mode+val.rowid+');" style="'+busy+
						'position: absolute; left: '+val.leftpos+'%; top: '+val.toppos+'%;" id="'+val.rowid+'"></div>';	
			}
			else if(val.object=='table2'){
				object='<div class="table2div" '+mode+val.rowid+');" style="'+busy+
					'position: absolute; left: '+val.leftpos+'%; top: '+val.toppos+'%;" id="'+val.rowid+'"><br>'
					+val.label+'<br>'+val.total_ttc+'<br>'+val.diner+'</div>';	
			}
			else if(val.object=='tableB'){
				object='<div class="tableBdiv" '+mode+val.rowid+');" style="'+busy+
					'position: absolute; left: '+val.leftpos+'%; top: '+val.toppos+'%;" id="'+val.rowid+'"><br>'
					+val.label+'<br>'+val.total_ttc+'<br>'+val.diner+'</div>';	
			}
			else{
				object='<div class="tablediv" '+mode+val.rowid+');" style="'+busy+
					'position: absolute; left: '+val.leftpos+'%; top: '+val.toppos+'%;" id="'+val.rowid+'">'
					+val.label+'<br>'+val.total_ttc+'<br>'+val.diner+'</div>';	//V20: Default is table.
			}
			
			<?php if ($mode=="edit"){?>
			$('body').append(object);	//V20
			
			$( "#"+val.rowid ).draggable(
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
			$('#'+val.rowid).draggable().bind('click', function(){
				$(this).focus();
			})
			<?php }
			else {?>
			//V20: More data
			$('body').append(object);
			
			<?php } ?>
		});
	});
});

</script>
</head>
<body style="overflow: hidden">
<?php //V20: Reformated
if ($user->admin){
	echo '<div style="position: absolute; left: 0.1%; top: 0.8%; width:48%; height:11%;">';
	
	
	if ($mode=="edit"){
		echo '<a id="add" onclick="window.location.href=\'floors.php?mode=edit&action=add&floor='.$floor.'\';">'. $langs->trans("Delete") .'</a>';
		echo '<br><br>';
		echo '<form method="post" action="floors.php?mode=edit&action=add&floor='.$floor.'" id="objectform">';
		$out.= '<select class="flat" name="selectobject" form="objectform">';
		$out.= '<option value="table" selected="selected">'.$langs->trans('table').'</option>';
		$out.= '<option value="table2">'.$langs->trans('table2').'</option>';
		$out.= '<option value="tableB">'.$langs->trans('tableB').'</option>';
		$out.= '<option value="chair">'.$langs->trans('chair').'</option>';
		$out.= '<option value="grass">'.$langs->trans('grass').'</option>';
		$out.= '<option value="wall">'.$langs->trans('wall').'</option>';
		$out.= '<option value="barh">'.$langs->trans('barh').'</option>';
		$out.= '<option value="barv">'.$langs->trans('barv').'</option>';
		$out.= '</select>';
		
		echo $out;
		echo '<input  type="submit" value="OK"></form>';
		
	} else {
		echo '<a onclick="window.location.href=\'floors.php?mode=edit&floor='.$floor.'\';">'.$langs->trans("Edit").'</a>';
	}
	echo '</div>';
}
?>

<div style="position: absolute; left: 25%; bottom: 8%; width:50%; height:3%;">
    <center>
    <h1>
    <img src="./img/arrow-prev.png" width="5%" onclick="location.href='floors.php?action=<?php echo $action ?>&floor=<?php if ($floor>1) { $floor--; echo $floor; $floor++;}  else echo "1"; ?>';">
    <?php 
    //echo $langs->trans("Floor")." ".$floor;	//v20
    echo $langs->trans("Floor".$floor);
    ?>
    <img src="./img/arrow-next.png" width="5%" onclick="location.href='floors.php?action=<?php echo $action ?>&floor=<?php $floor++; echo $floor; ?>';">
    </h1>
    </center>
</div>
</body>
</html>