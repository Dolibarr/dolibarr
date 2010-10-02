<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *      \file       htdocs/admin/menus.php
 *      \ingroup    core
 *      \brief      Page to setup menu manager to use
 *		\version	$Id$
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formadmin.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");


$langs->load("companies");
$langs->load("products");
$langs->load("admin");
$langs->load("users");

// Security check
if (!$user->admin) accessforbidden();

$dirtop = "../includes/menus/standard";
$dirleft = "../includes/menus/standard";
$dirsmartphone = "../includes/menus/smartphone";


// Cette page peut etre longue. On augmente le delai autorise.
// Ne fonctionne que si on est pas en safe_mode.
$err=error_reporting();
error_reporting(0);     // Disable all errors
//error_reporting(E_ALL);
@set_time_limit(300);   // Need more than 240 on Windows 7/64
error_reporting($err);


/*
 * Actions
 */

if (isset($_POST["action"]) && $_POST["action"] == 'update' && empty($_POST["cancel"]))
{
	$_SESSION["mainmenu"]="home";   // Le gestionnaire de menu a pu changer

	dolibarr_set_const($db, "MAIN_MENU_BARRETOP",      $_POST["main_menu_barretop"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MENU_SMARTPHONE",     $_POST["main_menu_smartphone"],'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "MAIN_MENUFRONT_BARRETOP", $_POST["main_menufront_barretop"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MENUFRONT_SMARTPHONE",$_POST["main_menufront_smartphone"],'chaine',0,'',$conf->entity);

	// Define list of menu handlers to initialize
	$listofmenuhandler=array();
	$listofmenuhandler[preg_replace('/((_back|_front)office)?\.php/i','',$_POST["main_menu_barretop"])]=1;
	$listofmenuhandler[preg_replace('/((_back|_front)office)?\.php/i','',$_POST["main_menufront_barretop"])]=1;
	if (isset($_POST["main_menu_smartphone"]))      $listofmenuhandler[preg_replace('/((_back|_front)office)?\.php/i','',$_POST["main_menu_smartphone"])]=1;
	if (isset($_POST["main_menufront_smartphone"])) $listofmenuhandler[preg_replace('/((_back|_front)office)?\.php/i','',$_POST["main_menufront_smartphone"])]=1;

	// Initialize menu handlers
	foreach ($listofmenuhandler as $key => $val)
	{
		$dir = DOL_DOCUMENT_ROOT."/includes/menus/";

		// Load sql init_menu_handler.sql file
		$file='init_menu_'.$key.'.sql';
		if (file_exists($dir.$file))
		{
			$result=run_sql($dir.$file,1,'',1,$key);
		}
	}

	// We make a header redirect because we need to change menu NOW.
	header("Location: ".$_SERVER["PHP_SELF"]);
	exit;
}


/*
 * View
 */

$html=new Form($db);
$htmladmin=new FormAdmin($db);

$wikihelp='EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader('',$langs->trans("Setup"),$wikihelp);

print_fiche_titre($langs->trans("Menus"),'','setup');

print $langs->trans("MenusDesc")."<br>\n";
print "<br>\n";

$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/menus.php";
$head[$h][1] = $langs->trans("MenuHandlers");
$head[$h][2] = 'handler';
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/menus/index.php";
$head[$h][1] = $langs->trans("MenuAdmin");
$head[$h][2] = 'editor';
$h++;


dol_fiche_head($head, 'handler', $langs->trans("Menus"));


if (isset($_GET["action"]) && $_GET["action"] == 'edit')
{
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';

	clearstatcache();

	// Gestionnaires de menu
	$var=true;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td width="35%">'.$langs->trans("Menu").'</td>';
	print '<td>';
	print $html->textwithpicto($langs->trans("InternalUsers"),$langs->trans("InternalExternalDesc"));
	print '</td>';
	print '<td>';
	print $html->textwithpicto($langs->trans("ExternalUsers"),$langs->trans("InternalExternalDesc"));
	print '</td>';
	print '</tr>';

	// Menu top
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("DefaultMenuManager").'</td>';
	print '<td>';
	print $htmladmin->select_menu($conf->global->MAIN_MENU_BARRETOP,'main_menu_barretop',$dirtop);
	print '</td>';
	print '<td>';
	print $htmladmin->select_menu($conf->global->MAIN_MENUFRONT_BARRETOP,'main_menufront_barretop',$dirtop);
	print '</td>';
	print '</tr>';

	// Menu smartphone
	if ($conf->global->MAIN_FEATURES_LEVEL >= 1)
	{
    	$var=!$var;
    	print '<tr '.$bc[$var].'><td>'.$langs->trans("DefaultMenuSmartphoneManager").'</td>';
    	print '<td>';
    	print $htmladmin->select_menu($conf->global->MAIN_MENU_SMARTPHONE,'main_menu_smartphone',$dirsmartphone);
    	print '</td>';
    	print '<td>';
    	print $htmladmin->select_menu($conf->global->MAIN_MENUFRONT_SMARTPHONE,'main_menufront_smartphone',$dirsmartphone);
    	print '</td>';
    	print '</tr>';
	}

	print '</table>';

	print '<br><center>';
	print '<input class="button" type="submit" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; &nbsp; ';
	print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</center>';

	print '</form>';
}
else
{
	// Gestionnaires de menu
	$var=true;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td width="35%">'.$langs->trans("Menu").'</td>';
	print '<td>';
	print $html->textwithpicto($langs->trans("InternalUsers"),$langs->trans("InternalExternalDesc"));
	print '</td>';
	print '<td>';
	print $html->textwithpicto($langs->trans("ExternalUsers"),$langs->trans("InternalExternalDesc"));
	print '</td>';
	print '</tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("DefaultMenuManager").'</td>';
	print '<td>';
	$filelib=preg_replace('/.php$/i','',$conf->global->MAIN_MENU_BARRETOP);
	print $filelib;
	print '</td>';
	print '<td>';
	$filelib=preg_replace('/.php$/i','',$conf->global->MAIN_MENUFRONT_BARRETOP);
	print $filelib;
	print '</td>';
	print '</tr>';

    if ($conf->global->MAIN_FEATURES_LEVEL >= 1)
    {
    	$var=!$var;
    	print '<tr '.$bc[$var].'>';
    	print '<td>'.$langs->trans("DefaultMenuSmartphoneManager").'</td>';
    	print '<td>';
    	$filelib=preg_replace('/.php$/i','',$conf->global->MAIN_MENU_SMARTPHONE);
    	print $filelib;
    	print '</td>';
    	print '<td>';
    	$filelib=preg_replace('/.php$/i','',$conf->global->MAIN_MENUFRONT_SMARTPHONE);
    	print $filelib;
    	print '</td>';
    	print '</tr>';
    }

	print '</table>';
}

print '</div>';


if (! isset($_GET["action"]) || $_GET["action"] != 'edit')
{
	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
	print '</div>';
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
