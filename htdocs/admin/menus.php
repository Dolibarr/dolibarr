<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
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
 *      \file       htdocs/admin/menus.php
 *      \ingroup    core
 *      \brief      Page to setup menu manager to use
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');

// Load translation files required by the page
$langs->loadLangs(array("companies", "products", "admin", "users", "other"));

// Security check
if (!$user->admin) accessforbidden();

$dirstandard = array();
$dirsmartphone = array();
$dirmenus = array_merge(array("/core/menus/"), (array) $conf->modules_parts['menus']);
foreach ($dirmenus as $dirmenu)
{
	$dirstandard[] = $dirmenu.'standard';
	$dirsmartphone[] = $dirmenu.'smartphone';
}

$error = 0;

// Cette page peut etre longue. On augmente le delai autorise.
// Ne fonctionne que si on est pas en safe_mode.
$err = error_reporting();
error_reporting(0); // Disable all errors
//error_reporting(E_ALL);
@set_time_limit(300); // Need more than 240 on Windows 7/64
error_reporting($err);


/*
 * Actions
 */

if ($action == 'update' && !$cancel)
{
	$_SESSION["mainmenu"] = "home"; // Le gestionnaire de menu a pu changer

	dolibarr_set_const($db, "MAIN_MENU_STANDARD", GETPOST('MAIN_MENU_STANDARD', 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_MENU_SMARTPHONE", GETPOST('MAIN_MENU_SMARTPHONE', 'alpha'), 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, "MAIN_MENUFRONT_STANDARD", GETPOST('MAIN_MENUFRONT_STANDARD', 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_MENUFRONT_SMARTPHONE", GETPOST('MAIN_MENUFRONT_SMARTPHONE', 'alpha'), 'chaine', 0, '', $conf->entity);

	// Define list of menu handlers to initialize
	$listofmenuhandler = array();
	$listofmenuhandler[preg_replace('/(_backoffice|_frontoffice|_menu)?\.php/i', '', GETPOST('MAIN_MENU_STANDARD', 'alpha'))] = 1;
	$listofmenuhandler[preg_replace('/(_backoffice|_frontoffice|_menu)?\.php/i', '', GETPOST('MAIN_MENUFRONT_STANDARD', 'alpha'))] = 1;
	if (GETPOST('MAIN_MENU_SMARTPHONE', 'alpha'))      $listofmenuhandler[preg_replace('/(_backoffice|_frontoffice|_menu)?\.php/i', '', GETPOST('MAIN_MENU_SMARTPHONE', 'alpha'))] = 1;
	if (GETPOST('MAIN_MENUFRONT_SMARTPHONE', 'alpha')) $listofmenuhandler[preg_replace('/(_backoffice|_frontoffice|_menu)?\.php/i', '', GETPOST('MAIN_MENUFRONT_SMARTPHONE', 'alpha'))] = 1;

	// Initialize menu handlers
	foreach ($listofmenuhandler as $key => $val)
	{
		// Load sql init_menu_handler.sql file
		$dirmenus = array_merge(array("/core/menus/"), (array) $conf->modules_parts['menus']);
		foreach ($dirmenus as $dirmenu)
		{
			$file = 'init_menu_'.$key.'.sql';
			$fullpath = dol_buildpath($dirmenu.$file);
			//print 'action='.$action.' Search menu into fullpath='.$fullpath.'<br>';exit;

			if (file_exists($fullpath))
			{
				$db->begin();

				$result = run_sql($fullpath, 1, '', 1, $key, 'none');
				if ($result > 0)
				{
					$db->commit();
				} else {
					$error++;
					setEventMessages($langs->trans("FailedToInitializeMenu").' '.$key, null, 'errors');
					$db->rollback();
				}
			}
		}
	}

	if (!$error)
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

$form = new Form($db);
$formadmin = new FormAdmin($db);

$wikihelp = 'EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader('', $langs->trans("Setup"), $wikihelp);

print load_fiche_titre($langs->trans("Menus"), '', 'title_setup');


$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/menus.php";
$head[$h][1] = $langs->trans("MenuHandlers");
$head[$h][2] = 'handler';
$h++;

$head[$h][0] = DOL_URL_ROOT."/admin/menus/index.php";
$head[$h][1] = $langs->trans("MenuAdmin");
$head[$h][2] = 'editor';
$h++;

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print dol_get_fiche_head($head, 'handler', '', -1);

print '<span class="opacitymedium">'.$langs->trans("MenusDesc")."</span><br>\n";
print "<br>\n";


clearstatcache();

// Gestionnaires de menu
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td width="35%">'.$langs->trans("Menu").'</td>';
print '<td>';
print $form->textwithpicto($langs->trans("InternalUsers"), $langs->trans("InternalExternalDesc"));
print '</td>';
print '<td>';
print $form->textwithpicto($langs->trans("ExternalUsers"), $langs->trans("InternalExternalDesc"));
print '</td>';
print '</tr>';

// Menu top
print '<tr class="oddeven"><td>'.$langs->trans("DefaultMenuManager").'</td>';
print '<td>';
$formadmin->select_menu(empty($conf->global->MAIN_MENU_STANDARD_FORCED) ? $conf->global->MAIN_MENU_STANDARD : $conf->global->MAIN_MENU_STANDARD_FORCED, 'MAIN_MENU_STANDARD', $dirstandard, empty($conf->global->MAIN_MENU_STANDARD_FORCED) ? '' : ' disabled');
print '</td>';
print '<td>';
$formadmin->select_menu(empty($conf->global->MAIN_MENUFRONT_STANDARD_FORCED) ? $conf->global->MAIN_MENUFRONT_STANDARD : $conf->global->MAIN_MENUFRONT_STANDARD_FORCED, 'MAIN_MENUFRONT_STANDARD', $dirstandard, empty($conf->global->MAIN_MENUFRONT_STANDARD_FORCED) ? '' : ' disabled');
print '</td>';
print '</tr>';

// Menu smartphone
print '<tr class="oddeven"><td>'.$langs->trans("DefaultMenuSmartphoneManager").'</td>';
print '<td>';
$formadmin->select_menu(empty($conf->global->MAIN_MENU_SMARTPHONE_FORCED) ? $conf->global->MAIN_MENU_SMARTPHONE : $conf->global->MAIN_MENU_SMARTPHONE_FORCED, 'MAIN_MENU_SMARTPHONE', array_merge($dirstandard, $dirsmartphone), empty($conf->global->MAIN_MENU_SMARTPHONE_FORCED) ? '' : ' disabled');

if (!empty($conf->global->MAIN_MENU_SMARTPHONE_FORCED) && preg_match('/smartphone/', $conf->global->MAIN_MENU_SMARTPHONE_FORCED)
	|| (empty($conf->global->MAIN_MENU_SMARTPHONE_FORCED) && !empty($conf->global->MAIN_MENU_SMARTPHONE) && preg_match('/smartphone/', $conf->global->MAIN_MENU_SMARTPHONE)))
{
	print ' '.img_warning($langs->transnoentitiesnoconv("ThisForceAlsoTheme"));
}

print '</td>';
print '<td>';
$formadmin->select_menu(empty($conf->global->MAIN_MENUFRONT_SMARTPHONE_FORCED) ? $conf->global->MAIN_MENUFRONT_SMARTPHONE : $conf->global->MAIN_MENUFRONT_SMARTPHONE_FORCED, 'MAIN_MENUFRONT_SMARTPHONE', array_merge($dirstandard, $dirsmartphone), empty($conf->global->MAIN_MENUFRONT_SMARTPHONE_FORCED) ? '' : ' disabled');

if (!empty($conf->global->MAIN_MENU_SMARTPHONE_FORCED) && preg_match('/smartphone/', $conf->global->MAIN_MENUFRONT_SMARTPHONE_FORCED)
	|| (empty($conf->global->MAIN_MENUFRONT_SMARTPHONE_FORCED) && !empty($conf->global->MAIN_MENU_SMARTPHONE) && preg_match('/smartphone/', $conf->global->MAIN_MENUFRONT_SMARTPHONE)))
{
	print ' '.img_warning($langs->transnoentitiesnoconv("ThisForceAlsoTheme"));
}

print '</td>';
print '</tr>';

print '</table>';

print dol_get_fiche_end();

print '<div class="center">';
print '<input class="button button-save" type="submit" name="save" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

// End of page
llxFooter();
$db->close();
