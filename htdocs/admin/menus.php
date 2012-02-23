<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/admin/menus.php
 *      \ingroup    core
 *      \brief      Page to setup menu manager to use
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formadmin.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

$action=GETPOST('action');

$langs->load("companies");
$langs->load("products");
$langs->load("admin");
$langs->load("users");
$langs->load("other");

// Security check
if (!$user->admin) accessforbidden();

$dirstandard = array("/core/menus/standard");
$dirsmartphone = array("/core/menus/smartphone");
foreach($conf->menus_modules as $dir)
{
    $dirstandard[]=$dir.'standard';
    $dirsmartphone[]=$dir.'standard';
}


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

if ($action == 'update' && empty($_POST["cancel"]))
{
	$_SESSION["mainmenu"]="home";   // Le gestionnaire de menu a pu changer

	dolibarr_set_const($db, "MAIN_MENU_STANDARD",      $_POST["MAIN_MENU_STANDARD"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MENU_SMARTPHONE",     $_POST["MAIN_MENU_SMARTPHONE"],'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "MAIN_MENUFRONT_STANDARD", $_POST["MAIN_MENUFRONT_STANDARD"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MENUFRONT_SMARTPHONE",$_POST["MAIN_MENUFRONT_SMARTPHONE"],'chaine',0,'',$conf->entity);

	// Define list of menu handlers to initialize
	$listofmenuhandler=array();
	$listofmenuhandler[preg_replace('/((_back|_front)office)?\.php/i','',$_POST["MAIN_MENU_STANDARD"])]=1;
	$listofmenuhandler[preg_replace('/((_back|_front)office)?\.php/i','',$_POST["MAIN_MENUFRONT_STANDARD"])]=1;
	if (isset($_POST["MAIN_MENU_SMARTPHONE"]))      $listofmenuhandler[preg_replace('/((_back|_front)office)?\.php/i','',$_POST["MAIN_MENU_SMARTPHONE"])]=1;
	if (isset($_POST["MAIN_MENUFRONT_SMARTPHONE"])) $listofmenuhandler[preg_replace('/((_back|_front)office)?\.php/i','',$_POST["MAIN_MENUFRONT_SMARTPHONE"])]=1;

	// Initialize menu handlers
	$error=0; $errmsgs=array();
	foreach ($listofmenuhandler as $key => $val)
	{
		// Load sql init_menu_handler.sql file
        $dir = "/core/menus/";
	    $file='init_menu_'.$key.'.sql';
	    $fullpath=dol_buildpath($dir.$file);

		if (file_exists($fullpath))
		{
			$db->begin();

			$result=run_sql($fullpath,1,'',1,$key,'none');
			if ($result > 0)
			{
				$db->commit();
			}
			else
			{
				$error++;
				$errmsgs[]='Failed to initialize menu '.$key.'.';
				$db->rollback();
			}
		}
	}

	if (! $error)
	{
		$db->close();

		// We make a header redirect because we need to change menu NOW.
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}


/*
 * View
 */

$form=new Form($db);
$formadmin=new FormAdmin($db);

$wikihelp='EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader('',$langs->trans("Setup"),$wikihelp);

print_fiche_titre($langs->trans("Menus"),'','setup');


$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/menus.php";
$head[$h][1] = $langs->trans("MenuHandlers");
$head[$h][2] = 'handler';
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/menus/index.php";
$head[$h][1] = $langs->trans("MenuAdmin");
$head[$h][2] = 'editor';
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/menus/other.php";
$head[$h][1] = $langs->trans("Miscellanous");
$head[$h][2] = 'misc';
$h++;


dol_fiche_head($head, 'handler', $langs->trans("Menus"));

print $langs->trans("MenusDesc")."<br>\n";
print "<br>\n";


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
	print $form->textwithpicto($langs->trans("InternalUsers"),$langs->trans("InternalExternalDesc"));
	print '</td>';
	print '<td>';
	print $form->textwithpicto($langs->trans("ExternalUsers"),$langs->trans("InternalExternalDesc"));
	print '</td>';
	print '</tr>';

	// Menu top
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("DefaultMenuManager").'</td>';
	print '<td>';
	print $formadmin->select_menu(empty($conf->global->MAIN_MENU_STANDARD_FORCED)?$conf->global->MAIN_MENU_STANDARD:$conf->global->MAIN_MENU_STANDARD_FORCED, 'MAIN_MENU_STANDARD', $dirstandard, empty($conf->global->MAIN_MENU_STANDARD_FORCED)?'':' disabled="disabled"');
	print '</td>';
	print '<td>';
	print $formadmin->select_menu(empty($conf->global->MAIN_MENUFRONT_STANDARD_FORCED)?$conf->global->MAIN_MENUFRONT_STANDARD:$conf->global->MAIN_MENUFRONT_STANDARD_FORCED, 'MAIN_MENUFRONT_STANDARD', $dirstandard, empty($conf->global->MAIN_MENUFRONT_STANDARD_FORCED)?'':' disabled="disabled"');
	print '</td>';
	print '</tr>';

	// Menu smartphone
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("DefaultMenuSmartphoneManager").'</td>';
	print '<td>';
	print $formadmin->select_menu(empty($conf->global->MAIN_MENU_SMARTPHONE_FORCED)?$conf->global->MAIN_MENU_SMARTPHONE:$conf->global->MAIN_MENU_SMARTPHONE_FORCED, 'MAIN_MENU_SMARTPHONE', array_merge($dirstandard,$dirsmartphone), empty($conf->global->MAIN_MENU_SMARTPHONE_FORCED)?'':' disabled="disabled"');
	print '</td>';
	print '<td>';
	print $formadmin->select_menu(empty($conf->global->MAIN_MENUFRONT_SMARTPHONE_FORCED)?$conf->global->MAIN_MENUFRONT_SMARTPHONE:$conf->global->MAIN_MENUFRONT_SMARTPHONE_FORCED, 'MAIN_MENUFRONT_SMARTPHONE', array_merge($dirstandard,$dirsmartphone), empty($conf->global->MAIN_MENUFRONT_SMARTPHONE_FORCED)?'':' disabled="disabled"');
	print '</td>';
	print '</tr>';

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
	print $form->textwithpicto($langs->trans("InternalUsers"),$langs->trans("InternalExternalDesc"));
	print '</td>';
	print '<td>';
	print $form->textwithpicto($langs->trans("ExternalUsers"),$langs->trans("InternalExternalDesc"));
	print '</td>';
	print '</tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("DefaultMenuManager").'</td>';
	print '<td>';
	$filelib=preg_replace('/.php$/i','',(empty($conf->global->MAIN_MENU_STANDARD_FORCED)?$conf->global->MAIN_MENU_STANDARD:$conf->global->MAIN_MENU_STANDARD_FORCED));
	print $filelib;
	print '</td>';
	print '<td>';
	$filelib=preg_replace('/.php$/i','',(empty($conf->global->MAIN_MENUFRONT_STANDARD_FORCED)?$conf->global->MAIN_MENUFRONT_STANDARD:$conf->global->MAIN_MENUFRONT_STANDARD_FORCED));
	print $filelib;
	print '</td>';
	print '</tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("DefaultMenuSmartphoneManager").'</td>';
	print '<td>';
	$filelib=preg_replace('/.php$/i','',(empty($conf->global->MAIN_MENU_SMARTPHONE_FORCED)?$conf->global->MAIN_MENU_SMARTPHONE:$conf->global->MAIN_MENU_SMARTPHONE_FORCED));
	print $filelib;
	if (preg_match('/smartphone/',$conf->global->MAIN_MENU_SMARTPHONE_FORCED)
	|| (empty($conf->global->MAIN_MENU_SMARTPHONE_FORCED) && preg_match('/smartphone/',$conf->global->MAIN_MENU_SMARTPHONE)))
	{
		print ' '.img_warning($langs->transnoentitiesnoconv("ThisForceAlsoTheme"));
	}
	print '</td>';
	print '<td>';
	$filelib=preg_replace('/.php$/i','',(empty($conf->global->MAIN_MENUFRONT_SMARTPHONE_FORCED)?$conf->global->MAIN_MENUFRONT_SMARTPHONE:$conf->global->MAIN_MENUFRONT_SMARTPHONE_FORCED));
	print $filelib;
	if (preg_match('/smartphone/',$conf->global->MAIN_MENUFRONT_SMARTPHONE_FORCED)
	|| (empty($conf->global->MAIN_MENUFRONT_SMARTPHONE_FORCED) && preg_match('/smartphone/',$conf->global->MAIN_MENUFRONT_SMARTPHONE)))
	{
		print ' '.img_warning($langs->transnoentitiesnoconv("ThisForceAlsoTheme"));
	}
	print '</td>';
	print '</tr>';

	print '</table>';
}

print '</div>';


dol_htmloutput_errors('',$errmsgs);


if (! isset($_GET["action"]) || $_GET["action"] != 'edit')
{
	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
	print '</div>';
}


llxFooter();

$db->close();
?>
