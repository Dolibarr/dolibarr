<?php
/* Copyright (C) 2018	Andreu Bisquerra	<jove@bisquerra.com>
 * Copyright (C) 2019	Josep Lluís Amador	<joseplluis@lliuretic.cat>
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

/**
 *	\file       htdocs/takepos/index.php
 *	\ingroup    Takepos
 *	\brief      Head bar to start Takepos.
 */

//v20: Spliting original takepos.php DIV we can refresh screen quickly. This is usefull for manage user, terminal, etc

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

include '../main.inc.php';	// Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';	//V20


//V20: Define terminal.
/*$term = GETPOST('term','int');

//if(is_null($term) || empty($term)){
if(!is_numeric($term)){
	$term=$_SESSION['term'];
	if(empty($term))	$term=0;
}
$_SESSION['term']=$term;
*/
$term = GETPOST('setterminal', 'int');

if ($term>0)
{
	$_SESSION["takeposterminal"]=$term;
	///////////////Don't use after PR #11177
	if ($term==1) $_SESSION["takepostermvar"]="";
	else $_SESSION["takepostermvar"]=$term;
	/////////////////////////
}else $term=$_SESSION['takeposterminal'];


$userid=GETPOST('userid', 'int');

if($userid>0){
	//V20: TODO: Could be a lack of security, but it's the faster way of change users.
	$user->fetch($userid);
	$user->getRights();
	$_SESSION["dol_login"]=$user->login;
}

if(!$user->rights->takepos->use && !$user->admin)	accessforbidden();

/*
 * View
 */

// Title
$title='TakePOS - Dolibarr '.DOL_VERSION;
if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $title='TakePOS - '.$conf->global->MAIN_APPLICATION_TITLE;
$head='<meta name="apple-mobile-web-app-title" content="TakePOS"/>
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>';
$disablehead=0;
$disablejs=0;
top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);
print '<input type="hidden" id="keybuffer" name="keybuffer" value="0">';	//V20: To find keypad value




?>
<link rel="stylesheet" href="css/pos.css">
<script type="text/javascript" src="js/takepos.js" ></script>
<link rel="stylesheet" href="css/colorbox.css" type="text/css" media="screen" />
<script type="text/javascript" src="js/jquery.colorbox-min.js"></script>
<script language="javascript">

var fullscreen=0;	//V20

function FullScreen(val) {
	  if (fullscreen == 0 || val==1) {
		    var docElm = document.documentElement;
		   
		    if (docElm.requestFullscreen) {
		      docElm.requestFullscreen();
		    } else if (docElm.mozRequestFullScreen) {
		      docElm.mozRequestFullScreen();
		    } else if (docElm.webkitRequestFullScreen) {
		      docElm.webkitRequestFullScreen(docElm.ALLOW_KEYBOARD_INPUT);
		    }
		    fullscreen = 1;
		    $("#fullscreen").attr("src","img/collapse.png");
	  } else {
		    if (document.exitFullscreen) {
		      document.exitFullscreen();
		    } else if (document.mozCancelFullScreen) {
		      document.mozCancelFullScreen();
		    } else if (document.webkitCancelFullScreen) {
		      document.webkitCancelFullScreen();
		    }
		    fullscreen = 0;
		    $("#fullscreen").attr("src","img/expand.png");
	  }
}

function change_user(){
	
	$.getJSON('./ajax.php?action=changeuser&term='+$('#userid').val(), function(data) {
		$("#takepos").load("takepos.php");
	});
	
}

function exit(){
	var term="<?php echo $term;?>";
	
	$.get('../user/logout.php',function(data, status){
		location.href='index.php?term='+term;
	});
	
}

function TerminalsDialog()
{
    jQuery("#dialog-info").dialog({
	    resizable: false,
	    height:220,
	    width:400,
	    modal: true,
	    buttons: {
			Terminal1: function() {
				location.href='index.php?setterminal=1';
			}
			<?php
			for ($i = 2; $i <= $conf->global->TAKEPOS_NUM_TERMINALS; $i++)
			{
				print "
				,
				Terminal".$i.": function() {
					location.href='index.php?setterminal=".$i."';
				}
				";
			}
			?>
	    }
	});
}

$( document ).ready(function() {
	var admin="<?php echo $user->admin;?>";
	$("#takepos").load("takepos.php", function() {
		if(fullscreen==0 && admin==0 )	FullScreen(1);	//V20: As admin, avoid auto fullscreen.

	});
	<?php
	
	//IF NO TERMINAL SELECTED
	if (empty($_SESSION["takeposterminal"]))
	{
		if ($conf->global->TAKEPOS_NUM_TERMINALS=="1") $_SESSION["takeposterminal"]=1;
		else print "TerminalsDialog();";
	}
	?>
	
});


</script>

<?php

require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
$object = new Usergroup($db);
$object->fetch($conf->global->{'TAKEPOS_ID_GROUP'.$term});
$include = array();

if (! empty($object->members))
{
	foreach($object->members as $useringroup)
	{
		$include[]=$useringroup->id;
	}
}
$include[]=$user->id;



if ($conf->global->TAKEPOS_NUM_TERMINALS!="1" && $_SESSION["takeposterminal"]=="") print '<div id="dialog-info" title="TakePOS">'.$langs->trans('TerminalSelect').'</div>';


print '<div class="row0" ><table width="100%"><tr>';
	
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="terminal">';
	echo '<td><b>'.date('d/m/Y').'</b></td>';
	echo '<td><b> Terminal: '.$term.'</b></td>';
	
	$form = new Form($db);
	print '<td><b> Usuario: ';
	print $form->select_dolusers($user->id, 'userid" onchange="change_user()" style="background-color: black;color: lightcyan;font-weight: bold;', 0, '', 0, $include);
	print '</b></td>';
	
	echo '<td align="right"><img id="fullscreen" src="img/expand.png" class="topbuttons" onclick="FullScreen();">&nbsp;&nbsp;  </img>';
	echo '<img src="img/shutdown.png" class="topbuttons" onclick="exit();"></img></td>';	//Cierra Navegador
	
	print '</form>';
	
print '</tr></table></div>';

print '<div class="maincontainer" id="takepos">';
print '</div>';
print '</body>';
