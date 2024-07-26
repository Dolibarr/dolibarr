<?php
/* Copyright (C) 2001-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2016		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2021       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2021-2023  Anthony Berton          <anthony.berton@bb2a.fr>
 * Copyright (C) 2023       Eric Seigne      		<eric.seigne@cap-rel.fr>
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
 *       \file       htdocs/admin/ihm.php
 *       \brief      Page to setup GUI display options
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'languages', 'other', 'companies', 'products', 'members', 'projects', 'hrm', 'agenda'));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'adminihm'; // To manage different context of search

$mode = GETPOST('mode', 'aZ09') ? GETPOST('mode', 'aZ09') : 'other'; // 'template', 'dashboard', 'login', 'other'

if (!defined("MAIN_MOTD")) {
	define("MAIN_MOTD", "");
}

/*
 * Action
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (GETPOST('cancel', 'alpha')) {
	$action = '';
}

// Convert action set_XXX and del_XXX to set var (this is used when no javascript on for ajax_constantonoff)
$regs = array();
if (preg_match('/^(set|del)_([A-Z_]+)$/', $action, $regs)) {
	if ($regs[1] == 'set') {
		dolibarr_set_const($db, $regs[2], 1, 'chaine', 0, '', $conf->entity);
	} else {
		dolibarr_del_const($db, $regs[2], $conf->entity);
	}
}

if ($action == 'removebackgroundlogin' && getDolGlobalString('MAIN_LOGIN_BACKGROUND')) {
	dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$logofile = $conf->mycompany->dir_output.'/logos/' . getDolGlobalString('MAIN_LOGIN_BACKGROUND');
	dol_delete_file($logofile);
	dolibarr_del_const($db, "MAIN_LOGIN_BACKGROUND", $conf->entity);
	$mysoc->logo = '';

	/*$logosmallfile=$conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small;
	dol_delete_file($logosmallfile);
	dolibarr_del_const($db, "MAIN_INFO_SOCIETE_LOGO_SMALL",$conf->entity);
	$mysoc->logo_small='';

	$logominifile=$conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini;
	dol_delete_file($logominifile);
	dolibarr_del_const($db, "MAIN_INFO_SOCIETE_LOGO_MINI",$conf->entity);
	$mysoc->logo_mini='';*/
}

if ($action == 'update') {
	$error = 0;

	if ($mode == 'template') {
		//dolibarr_del_const($db, "MAIN_THEME", 0);	// To be sure we don't have this constant set for all entities

		dolibarr_set_const($db, "MAIN_THEME", GETPOST("main_theme", 'aZ09'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);

		if (GETPOSTISSET('THEME_ELDY_USECOMOACTROW')) {
			dolibarr_set_const($db, "THEME_ELDY_USECOMOACTROW", GETPOST('THEME_ELDY_USECOMOACTROW'), 'chaine', 0, '', $conf->entity);
		}

		if (GETPOSTISSET('THEME_DARKMODEENABLED')) {
			$val = GETPOST('THEME_DARKMODEENABLED');
			if (!$val) {
				dolibarr_del_const($db, "THEME_DARKMODEENABLED", $conf->entity);
			}
			if ($val) {
				dolibarr_set_const($db, "THEME_DARKMODEENABLED", $val, 'chaine', 0, '', $conf->entity);
			}
		}

		if (GETPOSTISSET('THEME_TOPMENU_DISABLE_IMAGE')) {
			$val = GETPOST('THEME_TOPMENU_DISABLE_IMAGE');
			if (!$val) {
				dolibarr_del_const($db, 'THEME_TOPMENU_DISABLE_IMAGE', $conf->entity);
			} else {
				dolibarr_set_const($db, 'THEME_TOPMENU_DISABLE_IMAGE', GETPOST('THEME_TOPMENU_DISABLE_IMAGE'), 'chaine', 0, '', $conf->entity);
			}
		}

		$val = (implode(',', (colorStringToArray(GETPOST('THEME_ELDY_BACKBODY'), array()))));
		if ($val == '') {
			dolibarr_del_const($db, 'THEME_ELDY_BACKBODY', $conf->entity);
		} else {
			dolibarr_set_const($db, 'THEME_ELDY_BACKBODY', $val, 'chaine', 0, '', $conf->entity);
		}

		$val = (implode(',', (colorStringToArray(GETPOST('THEME_ELDY_TOPMENU_BACK1'), array()))));
		if ($val == '') {
			dolibarr_del_const($db, 'THEME_ELDY_TOPMENU_BACK1', $conf->entity);
		} else {
			dolibarr_set_const($db, 'THEME_ELDY_TOPMENU_BACK1', $val, 'chaine', 0, '', $conf->entity);
		}

		$val = (implode(',', (colorStringToArray(GETPOST('THEME_ELDY_VERMENU_BACK1'), array()))));
		if ($val == '') {
			dolibarr_del_const($db, 'THEME_ELDY_VERMENU_BACK1', $conf->entity);
		} else {
			dolibarr_set_const($db, 'THEME_ELDY_VERMENU_BACK1', $val, 'chaine', 0, '', $conf->entity);
		}

		$val = (implode(',', (colorStringToArray(GETPOST('THEME_ELDY_TEXTTITLENOTAB'), array()))));
		if ($val == '') {
			dolibarr_del_const($db, 'THEME_ELDY_TEXTTITLENOTAB', $conf->entity);
		} else {
			dolibarr_set_const($db, 'THEME_ELDY_TEXTTITLENOTAB', $val, 'chaine', 0, '', $conf->entity);
		}

		$val = (implode(',', (colorStringToArray(GETPOST('THEME_ELDY_BACKTITLE1'), array()))));
		if ($val == '') {
			dolibarr_del_const($db, 'THEME_ELDY_BACKTITLE1', $conf->entity);
		} else {
			dolibarr_set_const($db, 'THEME_ELDY_BACKTITLE1', $val, 'chaine', 0, '', $conf->entity);
		}

		$val = (implode(',', (colorStringToArray(GETPOST('THEME_ELDY_TEXTTITLE'), array()))));
		if ($val == '') {
			dolibarr_del_const($db, 'THEME_ELDY_TEXTTITLE', $conf->entity);
		} else {
			dolibarr_set_const($db, 'THEME_ELDY_TEXTTITLE', $val, 'chaine', 0, '', $conf->entity);
		}

		$val = (implode(',', (colorStringToArray(GETPOST('THEME_ELDY_TEXTTITLELINK'), array()))));
		if ($val == '') {
			dolibarr_del_const($db, 'THEME_ELDY_TEXTTITLELINK', $conf->entity);
		} else {
			dolibarr_set_const($db, 'THEME_ELDY_TEXTTITLELINK', $val, 'chaine', 0, '', $conf->entity);
		}

		$val = (implode(',', (colorStringToArray(GETPOST('THEME_ELDY_LINEIMPAIR1'), array()))));
		if ($val == '') {
			dolibarr_del_const($db, 'THEME_ELDY_LINEIMPAIR1', $conf->entity);
		} else {
			dolibarr_set_const($db, 'THEME_ELDY_LINEIMPAIR1', $val, 'chaine', 0, '', $conf->entity);
		}
		$val = (implode(',', (colorStringToArray(GETPOST('THEME_ELDY_LINEIMPAIR1'), array()))));
		if ($val == '') {
			dolibarr_del_const($db, 'THEME_ELDY_LINEIMPAIR2', $conf->entity);
		} else {
			dolibarr_set_const($db, 'THEME_ELDY_LINEIMPAIR2', $val, 'chaine', 0, '', $conf->entity);
		}

		$val = (implode(',', (colorStringToArray(GETPOST('THEME_ELDY_LINEPAIR1'), array()))));
		if ($val == '') {
			dolibarr_del_const($db, 'THEME_ELDY_LINEPAIR1', $conf->entity);
		} else {
			dolibarr_set_const($db, 'THEME_ELDY_LINEPAIR1', $val, 'chaine', 0, '', $conf->entity);
		}
		$val = (implode(',', (colorStringToArray(GETPOST('THEME_ELDY_LINEPAIR1'), array()))));
		if ($val == '') {
			dolibarr_del_const($db, 'THEME_ELDY_LINEPAIR2', $conf->entity);
		} else {
			dolibarr_set_const($db, 'THEME_ELDY_LINEPAIR2', $val, 'chaine', 0, '', $conf->entity);
		}

		$val = (implode(',', (colorStringToArray(GETPOST('THEME_ELDY_TEXTLINK'), array()))));
		if ($val == '') {
			dolibarr_del_const($db, 'THEME_ELDY_TEXTLINK', $conf->entity);
		} else {
			dolibarr_set_const($db, 'THEME_ELDY_TEXTLINK', $val, 'chaine', 0, '', $conf->entity);
		}

		$val = (implode(',', (colorStringToArray(GETPOST('THEME_ELDY_USE_HOVER'), array()))));
		if ($val == '') {
			dolibarr_del_const($db, 'THEME_ELDY_USE_HOVER', $conf->entity);
		} else {
			dolibarr_set_const($db, "THEME_ELDY_USE_HOVER", $val, 'chaine', 0, '', $conf->entity);
		}

		$val = (implode(',', (colorStringToArray(GETPOST('THEME_ELDY_USE_CHECKED'), array()))));
		if ($val == '') {
			dolibarr_del_const($db, 'THEME_ELDY_USE_CHECKED', $conf->entity);
		} else {
			dolibarr_set_const($db, "THEME_ELDY_USE_CHECKED", $val, 'chaine', 0, '', $conf->entity);
		}

		$val=(implode(',', (colorStringToArray(GETPOST('THEME_ELDY_BTNACTION'), array()))));
		if ($val == '') {
			dolibarr_del_const($db, 'THEME_ELDY_BTNACTION', $conf->entity);
		} else {
			dolibarr_set_const($db, 'THEME_ELDY_BTNACTION', $val, 'chaine', 0, '', $conf->entity);
		}

		$val=(implode(',', (colorStringToArray(GETPOST('THEME_ELDY_TEXTBTNACTION'), array()))));
		if ($val == '') {
			dolibarr_del_const($db, 'THEME_ELDY_TEXTBTNACTION', $conf->entity);
		} else {
			dolibarr_set_const($db, 'THEME_ELDY_TEXTBTNACTION', $val, 'chaine', 0, '', $conf->entity);
		}
	}

	if ($mode == 'dashboard') {
		dolibarr_set_const($db, "MAIN_MOTD", dol_htmlcleanlastbr(GETPOST("main_motd", 'restricthtml')), 'chaine', 0, '', $conf->entity);
	}

	if ($mode == 'other') {
		dolibarr_set_const($db, "MAIN_LANG_DEFAULT", GETPOST("MAIN_LANG_DEFAULT", 'aZ09'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);

		dolibarr_set_const($db, "MAIN_SIZE_LISTE_LIMIT", GETPOSTINT("MAIN_SIZE_LISTE_LIMIT"), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "MAIN_SIZE_SHORTLIST_LIMIT", GETPOSTINT("MAIN_SIZE_SHORTLIST_LIMIT"), 'chaine', 0, '', $conf->entity);

		if (GETPOSTISSET("MAIN_CHECKBOX_LEFT_COLUMN")) {
			dolibarr_set_const($db, "MAIN_CHECKBOX_LEFT_COLUMN", GETPOSTINT("MAIN_CHECKBOX_LEFT_COLUMN"), 'chaine', 0, '', $conf->entity);
		}

		//dolibarr_set_const($db, "MAIN_DISABLE_JAVASCRIPT", GETPOST("MAIN_DISABLE_JAVASCRIPT", 'aZ09'), 'chaine', 0, '', $conf->entity);
		//dolibarr_set_const($db, "MAIN_BUTTON_HIDE_UNAUTHORIZED", GETPOST("MAIN_BUTTON_HIDE_UNAUTHORIZED", 'aZ09'), 'chaine', 0, '', $conf->entity);
		//dolibarr_set_const($db, "MAIN_MENU_HIDE_UNAUTHORIZED", GETPOST("MAIN_MENU_HIDE_UNAUTHORIZED", 'aZ09'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "MAIN_START_WEEK", GETPOSTINT("MAIN_START_WEEK"), 'chaine', 0, '', $conf->entity);

		dolibarr_set_const($db, "MAIN_DEFAULT_WORKING_DAYS", GETPOST("MAIN_DEFAULT_WORKING_DAYS", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "MAIN_DEFAULT_WORKING_HOURS", GETPOST("MAIN_DEFAULT_WORKING_HOURS", 'alphanohtml'), 'chaine', 0, '', $conf->entity);

		dolibarr_set_const($db, "MAIN_BUGTRACK_ENABLELINK", GETPOST("MAIN_BUGTRACK_ENABLELINK", 'alpha'), 'chaine', 0, '', $conf->entity);

		dolibarr_set_const($db, "MAIN_FIRSTNAME_NAME_POSITION", GETPOST("MAIN_FIRSTNAME_NAME_POSITION", 'aZ09'), 'chaine', 0, '', $conf->entity);
	}

	if ($mode == 'login') {
		dolibarr_set_const($db, "MAIN_HOME", dol_htmlcleanlastbr(GETPOST("main_home", 'restricthtml')), 'chaine', 0, '', $conf->entity);
		//dolibarr_set_const($db, "MAIN_HELP_DISABLELINK", GETPOST("MAIN_HELP_DISABLELINK", 'aZ09'), 'chaine', 0, '', 0); // Param for all entities

		$varforimage = 'imagebackground';
		$dirforimage = $conf->mycompany->dir_output . '/logos/';
		if ($_FILES[$varforimage]["tmp_name"]) {
			$reg = array();
			if (preg_match('/([^\\/:]+)$/i', $_FILES[$varforimage]["name"], $reg)) {
				$original_file = $reg[1];

				$isimage = image_format_supported($original_file);
				if ($isimage >= 0) {
					dol_syslog("Move file " . $_FILES[$varforimage]["tmp_name"] . " to " . $dirforimage . $original_file);
					if (!is_dir($dirforimage)) {
						dol_mkdir($dirforimage);
					}
					$result = dol_move_uploaded_file($_FILES[$varforimage]["tmp_name"], $dirforimage . $original_file, 1, 0, $_FILES[$varforimage]['error']);
					if ($result > 0) {
						dolibarr_set_const($db, "MAIN_LOGIN_BACKGROUND", $original_file, 'chaine', 0, '', $conf->entity);
					} elseif (preg_match('/^ErrorFileIsInfectedWithAVirus/', $result)) {
						$error++;
						$langs->load("errors");
						$tmparray = explode(':', $result);
						setEventMessages($langs->trans('ErrorFileIsInfectedWithAVirus', $tmparray[1]), null, 'errors');
					} else {
						$error++;
						setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
					}
				} else {
					$error++;
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorBadImageFormat"), null, 'errors');
				}
			}
		}
	}

	if ($mode == 'css') {
		//file_put_contents(DOL_DATA_ROOT.'/admin/customcss.css', $data);
		//dol_chmod(DOL_DATA_ROOT.'/admin/customcss.css');
		dolibarr_set_const($db, "MAIN_IHM_CUSTOM_CSS", GETPOST('MAIN_IHM_CUSTOM_CSS', 'restricthtml'), 'chaine', 0, '', $conf->entity);
	}

	$_SESSION["mainmenu"] = ""; // The menu manager may have changed

	if (GETPOST('dol_resetcache')) {
		dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", getDolGlobalInt('MAIN_IHM_PARAMS_REV') + 1, 'chaine', 0, '', $conf->entity);
	}

	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup&mode=".$mode.(GETPOSTISSET('page_y') ? '&page_y='.GETPOSTINT('page_y') : ''));
	exit;
}


/*
 * View
 */

$wikihelp = 'EN:First_setup|FR:Premiers_param&eacute;trages|ES:Primeras_configuraciones';

llxHeader(
	'',
	$langs->trans("Setup"),
	$wikihelp,
	'',
	0,
	0,
	array(
	'/includes/ace/src/ace.js',
	'/includes/ace/src/ext-statusbar.js',
	'/includes/ace/src/ext-language_tools.js',
	),
	array(),
	'',
	'mod-admin page-ihm'
);

$form = new Form($db);
$formother = new FormOther($db);
$formadmin = new FormAdmin($db);

print load_fiche_titre($langs->trans("GUISetup"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("DisplayDesc")."</span><br>\n";
print "<br>\n";

//WYSIWYG Editor
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

print '<form enctype="multipart/form-data" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" id="mode" name="mode" value="'.dol_escape_htmltag($mode).'">';
print '<input type="hidden" name="dol_resetcache" value="1">';

$head = ihm_prepare_head();

print dol_get_fiche_head($head, $mode, '', -1, '');

print '<br>';

clearstatcache();

if ($mode == 'other') {
	print '<div class="div-table-responsive-no-min">';
	print '<table summary="edit" class="noborder centpercent editmode tableforfield">';

	print '<tr class="liste_titre"><td class="titlefieldmiddle">';
	print $langs->trans("Language");
	print '</td><td class="titlefieldmiddle">';
	print '</td></tr>';

	// Default language
	print '<tr class="oddeven"><td>'.$langs->trans("DefaultLanguage").'</td><td>';
	print img_picto('', 'language', 'class="pictofixedwidth"');
	print $formadmin->select_language(getDolGlobalString('MAIN_LANG_DEFAULT'), 'MAIN_LANG_DEFAULT', 1, null, '', 0, 0, 'minwidth300', 2);
	//print '<input class="button button-save smallpaddingimp" type="submit" name="submit" value="'.$langs->trans("Save").'">';
	print '</td>';
	print '</tr>';

	// Multilingual GUI
	print '<tr class="oddeven"><td>' . $langs->trans("EnableMultilangInterface") . '</td><td>';
	print ajax_constantonoff("MAIN_MULTILANGS", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '', 'language');
	print '</td>';
	print '</tr>';

	print '</table>' . "\n";
	print '</div>';

	print '<div class="center">';
	print '<input class="button button-save reposition" type="submit" name="submit" value="' . $langs->trans("Save") . '">';
	print '<input class="button button-cancel reposition" type="submit" name="cancel" value="' . $langs->trans("Cancel") . '">';
	print '</div>';

	print '<br>';
	print '<br>';

	// Other
	print '<div class="div-table-responsive-no-min">';
	print '<table summary="otherparameters" class="noborder centpercent editmode tableforfield">';

	print '<tr class="liste_titre"><td class="titlefieldmiddle">';
	print $langs->trans("Miscellaneous");
	print '</td>';
	print '<td class="titlefieldmiddle"></td>';
	print '</tr>';

	if (!empty($conf->use_javascript_ajax)) {
		// Show Quick Add link
		print '<tr class="oddeven"><td>' . $langs->trans("ShowQuickAddLink") . '</td><td>';
		print ajax_constantonoff("MAIN_USE_TOP_MENU_QUICKADD_DROPDOWN", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '', 'other');
		print '</td>';
		print '</tr>';
	}

	// Hide wiki link on login page
	$pictohelp = '<span class="fa fa-question-circle"></span>';
	print '<tr class="oddeven"><td>' . str_replace('{picto}', $pictohelp, $langs->trans("DisableLinkToHelp", '{picto}')) . '</td><td>';
	print ajax_constantonoff("MAIN_HELP_DISABLELINK", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '', 'other');
	//print $form->selectyesno('MAIN_HELP_DISABLELINK', isset($conf->global->MAIN_HELP_DISABLELINK) ? $conf->global->MAIN_HELP_DISABLELINK : 0, 1);
	print '</td>';
	print '</tr>';

	// Max size of lists
	print '<tr class="oddeven"><td>' . $langs->trans("DefaultMaxSizeList") . '</td><td><input class="flat width50" name="MAIN_SIZE_LISTE_LIMIT" value="';
	if (getDolGlobalInt('MAIN_SIZE_LISTE_LIMIT') > 0) {
		print getDolGlobalString('MAIN_SIZE_LISTE_LIMIT');
	}
	print '">';
	if (getDolGlobalInt('MAIN_SIZE_LISTE_LIMIT') <= 0) {
		print ' &nbsp; <span class="opacitymedium">('.$langs->trans("Automatic").')</span>';
	}
	print '</td>';
	print '</tr>';

	// Max size of short lists on customer card
	print '<tr class="oddeven"><td>' . $langs->trans("DefaultMaxSizeShortList") . '</td><td><input class="flat width50" name="MAIN_SIZE_SHORTLIST_LIMIT" value="' . getDolGlobalString('MAIN_SIZE_SHORTLIST_LIMIT') . '"></td>';
	print '</tr>';

	// Display checkboxes and fields menu left / right
	print '<tr class="oddeven"><td>' . $langs->trans("MAIN_CHECKBOX_LEFT_COLUMN") . '</td><td>';
	print ajax_constantonoff("MAIN_CHECKBOX_LEFT_COLUMN", array(), $conf->entity, 0, 0, 1, 0, 0, 1, '', 'other');
	print '</td>';
	print '</tr>';

	// show input border
	/*
	 print '<tr><td>'.$langs->trans("showInputBorder").'</td><td>';
	 print $form->selectyesno('main_showInputBorder',isset($conf->global->THEME_ELDY_SHOW_BORDER_INPUT)?$conf->global->THEME_ELDY_SHOW_BORDER_INPUT:0,1);
	 print '</td>';
	 print '</tr>';
	 */

	// First day for weeks
	print '<tr class="oddeven"><td>' . $langs->trans("WeekStartOnDay") . '</td><td>';
	print $formother->select_dayofweek((isset($conf->global->MAIN_START_WEEK) ? $conf->global->MAIN_START_WEEK : '1'), 'MAIN_START_WEEK', 0);
	print '</td>';
	print '</tr>';

	// DefaultWorkingDays
	print '<tr class="oddeven"><td>' . $langs->trans("DefaultWorkingDays") . '</td><td>';
	print '<input type="text" name="MAIN_DEFAULT_WORKING_DAYS" size="5" value="' . (isset($conf->global->MAIN_DEFAULT_WORKING_DAYS) ? $conf->global->MAIN_DEFAULT_WORKING_DAYS : '1-5') . '">';
	print '</td>';
	print '</tr>';

	// DefaultWorkingHours
	print '<tr class="oddeven"><td>' . $langs->trans("DefaultWorkingHours") . '</td><td>';
	print '<input type="text" name="MAIN_DEFAULT_WORKING_HOURS" size="5" value="' . (isset($conf->global->MAIN_DEFAULT_WORKING_HOURS) ? $conf->global->MAIN_DEFAULT_WORKING_HOURS : '9-18') . '">';
	print '</td>';
	print '</tr>';

	// Firstname/Name
	print '<tr class="oddeven"><td>' . $langs->trans("FirstnameNamePosition") . '</td><td>';
	$array = array(0 => $langs->trans("Firstname") . ' ' . $langs->trans("Lastname"), 1 => $langs->trans("Lastname") . ' ' . $langs->trans("Firstname"));
	print $form->selectarray('MAIN_FIRSTNAME_NAME_POSITION', $array, (isset($conf->global->MAIN_FIRSTNAME_NAME_POSITION) ? $conf->global->MAIN_FIRSTNAME_NAME_POSITION : 0));
	print '</td>';
	print '</tr>';

	// Hide unauthorized menus
	print '<tr class="oddeven"><td>' . $langs->trans("HideUnauthorizedMenu") . '</td><td>';
	//print $form->selectyesno('MAIN_MENU_HIDE_UNAUTHORIZED', isset($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED) ? $conf->global->MAIN_MENU_HIDE_UNAUTHORIZED : 0, 1);
	print ajax_constantonoff("MAIN_MENU_HIDE_UNAUTHORIZED", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '', 'other');
	print '</td>';
	print '</tr>';

	// Hide unauthorized button
	print '<tr class="oddeven"><td>' . $langs->trans("ButtonHideUnauthorized") . '</td><td>';
	//print $form->selectyesno('MAIN_BUTTON_HIDE_UNAUTHORIZED', isset($conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED) ? $conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED : 0, 1);
	print ajax_constantonoff("MAIN_BUTTON_HIDE_UNAUTHORIZED", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '', 'other');
	print '</td>';
	print '</tr>';

	// Hide version link
	/*

	print '<tr><td>'.$langs->trans("HideVersionLink").'</td><td>';
	print $form->selectyesno('MAIN_HIDE_VERSION',$conf->global->MAIN_HIDE_VERSION,1);
	print '</td>';
	print '</tr>';
	*/


	// Show search area in top menu
	print '<tr class="oddeven"><td>' . $langs->trans("ShowSearchAreaInTopMenu") . '</td><td>';
	print ajax_constantonoff("MAIN_USE_TOP_MENU_SEARCH_DROPDOWN", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '', 'other');
	print '</td>';
	print '</tr>';

	// Show bugtrack link
	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("ShowBugTrackLink", $langs->transnoentitiesnoconv("FindBug")), $langs->trans("ShowBugTrackLinkDesc"));
	print '</td><td>';
	print '<input type="text" name="MAIN_BUGTRACK_ENABLELINK" value="' . (!getDolGlobalString('MAIN_BUGTRACK_ENABLELINK') ? '' : $conf->global->MAIN_BUGTRACK_ENABLELINK) . '">';
	print '</td>';
	print '</tr>';

	// Disable javascript and ajax
	print '<tr class="oddeven"><td>' . $form->textwithpicto($langs->trans("DisableJavascript"), $langs->trans("DisableJavascriptNote")) . '</td><td>';
	print ajax_constantonoff("MAIN_DISABLE_JAVASCRIPT", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '', 'other');
	print '</td>';
	print '</tr>';

	print '</table>' . "\n";
	print '</div>';
}


if ($mode == 'template') {
	// Themes and themes options
	showSkins(null, 1);
}


if ($mode == 'dashboard') {
	print '<div class="div-table-responsive-no-min">';
	print '<table summary="blockdashboard" class="noborder centpercent editmode tableforfield">';

	// Message of the day on home page
	$substitutionarray = getCommonSubstitutionArray($langs, 0, array('object', 'objectamount'));
	complete_substitutions_array($substitutionarray, $langs);

	print '<tr class="oddeven width25p"><td>';
	$texthelp = $langs->trans("FollowingConstantsWillBeSubstituted") . '<br>';
	foreach ($substitutionarray as $key => $val) {
		$texthelp .= $key . '<br>';
	}
	print $form->textwithpicto($langs->trans("MessageOfDay"), $texthelp, 1, 'help', '', 0, 2, 'tooltipmessageofday');

	print '</td><td>';

	$doleditor = new DolEditor('main_motd', (isset($conf->global->MAIN_MOTD) ? $conf->global->MAIN_MOTD : ''), '', 142, 'dolibarr_notes', 'In', false, true, true, ROWS_4, '90%');
	$doleditor->Create();

	print '</td></tr>' . "\n";

	/* no more need for this option. It is now a widget already controlled by end user
	 print '<tr class="oddeven"><td>' . $langs->trans('BoxstatsDisableGlobal') . '</td><td>';
	 print ajax_constantonoff("MAIN_DISABLE_GLOBAL_BOXSTATS", array(), $conf->entity, 0, 0, 1, 0);
	 print '</td>';
	 print '</tr>';
	 */

	print '</table>';
	print '</div>';

	print '<br>';

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="blockdashboard" class="noborder centpercent editmode tableforfield">';

	print '<tr class="liste_titre"><td class="titlefieldmiddle">';
	print $langs->trans("DashboardDisableBlocks");
	print '</td><td class="titlefieldmiddle">';
	print '</td></tr>';

	print '<tr class="oddeven"><td>' . $langs->trans('DashboardDisableGlobal') . '</td><td>';
	print ajax_constantonoff("MAIN_DISABLE_GLOBAL_WORKBOARD", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '_red', 'dashboard');
	print '</td>';
	print '</tr>';

	if (!getDolGlobalString('MAIN_DISABLE_GLOBAL_WORKBOARD')) {
		// Block meteo
		print '<tr class="oddeven"><td>' . $langs->trans('MAIN_DISABLE_METEO') . '</td><td>';
		print ajax_constantonoff("MAIN_DISABLE_METEO", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '_red', 'dashboard');
		print '</td>';
		print '</tr>';

		// Block agenda
		print '<tr class="oddeven"><td>' . $langs->trans('DashboardDisableBlockAgenda') . '</td><td>';
		print ajax_constantonoff("MAIN_DISABLE_BLOCK_AGENDA", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '_red', 'dashboard');
		print '</td>';
		print '</tr>';

		// Block agenda
		print '<tr class="oddeven"><td>' . $langs->trans('DashboardDisableBlockProject') . '</td><td>';
		print ajax_constantonoff("MAIN_DISABLE_BLOCK_PROJECT", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '_red', 'dashboard');
		print '</td>';
		print '</tr>';

		// Block customer
		print '<tr class="oddeven"><td>' . $langs->trans('DashboardDisableBlockCustomer') . '</td><td>';
		print ajax_constantonoff("MAIN_DISABLE_BLOCK_CUSTOMER", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '_red', 'dashboard');
		print '</td>';
		print '</tr>';

		// Block supplier
		print '<tr class="oddeven"><td>' . $langs->trans('DashboardDisableBlockSupplier') . '</td><td>';
		print ajax_constantonoff("MAIN_DISABLE_BLOCK_SUPPLIER", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '_red', 'dashboard');
		print '</td>';
		print '</tr>';

		// Block contract
		print '<tr class="oddeven"><td>' . $langs->trans('DashboardDisableBlockContract') . '</td><td>';
		print ajax_constantonoff("MAIN_DISABLE_BLOCK_CONTRACT", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '_red', 'dashboard');
		print '</td>';
		print '</tr>';

		// Block ticket
		print '<tr class="oddeven"><td>' . $langs->trans('DashboardDisableBlockTicket') . '</td><td>';
		print ajax_constantonoff("MAIN_DISABLE_BLOCK_TICKET", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '_red', 'dashboard');
		print '</td>';
		print '</tr>';

		// Block bank
		print '<tr class="oddeven"><td>' . $langs->trans('DashboardDisableBlockBank') . '</td><td>';
		print ajax_constantonoff("MAIN_DISABLE_BLOCK_BANK", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '_red', 'dashboard');
		print '</td>';
		print '</tr>';

		// Block adherent
		print '<tr class="oddeven"><td>' . $langs->trans('DashboardDisableBlockAdherent') . '</td><td>';
		print ajax_constantonoff("MAIN_DISABLE_BLOCK_ADHERENT", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '_red', 'dashboard');
		print '</td>';
		print '</tr>';

		// Block expense report
		print '<tr class="oddeven"><td>' . $langs->trans('DashboardDisableBlockExpenseReport') . '</td><td>';
		print ajax_constantonoff("MAIN_DISABLE_BLOCK_EXPENSEREPORT", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '_red', 'dashboard');
		print '</td>';
		print '</tr>';

		// Block holiday
		print '<tr class="oddeven"><td>' . $langs->trans('DashboardDisableBlockHoliday') . '</td><td>';
		print ajax_constantonoff("MAIN_DISABLE_BLOCK_HOLIDAY", array(), $conf->entity, 0, 0, 1, 0, 0, 0, '_red', 'dashboard');
		print '</td>';
		print '</tr>';
	}

	print '</table>' . "\n";
	print '</div>';
}


if ($mode == 'login') {
	// Other
	print '<div class="div-table-responsive-no-min">';
	print '<table summary="edit" class="noborder centpercent editmode tableforfield">';

	print '<tr class="liste_titre"><td class="titlefieldmax45">';
	print $langs->trans("Parameter");
	print '</td><td>';
	print $langs->trans("Value");
	print '</td></tr>';

	// Hide helpcenter link on login page
	print '<tr class="oddeven"><td>' . $langs->trans("DisableLinkToHelpCenter") . '</td><td>';
	print ajax_constantonoff("MAIN_HELPCENTER_DISABLELINK", array(), $conf->entity, 0, 0, 0, 0, 0, 0, '', 'login');
	print '</td>';
	print '</tr>';

	// Message on login page
	$substitutionarray = getCommonSubstitutionArray($langs, 0, array('object', 'objectamount', 'user'));
	complete_substitutions_array($substitutionarray, $langs);
	print '<tr class="oddeven"><td>';
	$texthelp = $langs->trans("FollowingConstantsWillBeSubstituted") . '<br>';
	foreach ($substitutionarray as $key => $val) {
		$texthelp .= $key . '<br>';
	}
	print $form->textwithpicto($langs->trans("MessageLogin"), $texthelp, 1, 'help', '', 0, 2, 'tooltipmessagelogin');
	print '</td><td>';
	$doleditor = new DolEditor('main_home', (isset($conf->global->MAIN_HOME) ? $conf->global->MAIN_HOME : ''), '', 142, 'dolibarr_notes', 'In', false, true, true, ROWS_4, '90%');
	$doleditor->Create();
	print '</td></tr>' . "\n";

	// Background
	print '<tr class="oddeven"><td><label for="imagebackground">' . $langs->trans("BackgroundImageLogin") . ' (png,jpg)</label></td><td>';
	print '<div class="centpercent inline-block">';
	$disabled = '';
	if (getDolGlobalString('ADD_UNSPLASH_LOGIN_BACKGROUND')) {
		$disabled = ' disabled="disabled"';
	}
	$maxfilesizearray = getMaxFileSizeArray();
	$maxmin = $maxfilesizearray['maxmin'];
	if ($maxmin > 0) {
		print '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';	// MAX_FILE_SIZE must precede the field type=file
	}
	print '<input type="file" class="flat maxwidthinputfileonsmartphone" name="imagebackground" id="imagebackground"' . $disabled . '>';
	if ($disabled) {
		print '(' . $langs->trans("DisabledByOptionADD_UNSPLASH_LOGIN_BACKGROUND") . ') ';
	}
	if (getDolGlobalString('MAIN_LOGIN_BACKGROUND')) {
		print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?action=removebackgroundlogin&token='.newToken().'&mode=login">' . img_delete($langs->trans("Delete")) . '</a>';
		if (file_exists($conf->mycompany->dir_output . '/logos/' . getDolGlobalString('MAIN_LOGIN_BACKGROUND'))) {
			print ' &nbsp; ';
			print '<img class="marginleftonly boxshadow valignmiddle" width="100" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&amp;file=' . urlencode('logos/' . getDolGlobalString('MAIN_LOGIN_BACKGROUND')) . '">';
		}
	} else {
		print '<img class="marginleftonly valignmiddle" width="100" src="' . DOL_URL_ROOT . '/public/theme/common/nophoto.png">';
	}
	print '</div>';
	print '</td></tr>';

	print '</table>' . "\n";
	print '</div>';
}

if ($mode == 'css') {
	print '<div class="div-table-responsive-no-min">';
	print '<table summary="edit" class="noborder centpercent editmode tableforfield">';

	print '<tr class="liste_titre">';
	print '<td colspan="2">';

	//$customcssValue = file_get_contents(DOL_DATA_ROOT.'/admin/customcss.css');
	$customcssValue = getDolGlobalString('MAIN_IHM_CUSTOM_CSS');

	$doleditor = new DolEditor('MAIN_IHM_CUSTOM_CSS', $customcssValue, '80%', 400, 'Basic', 'In', true, false, 'ace', 10, '90%');
	$doleditor->Create(0, '', true, 'css', 'css');
	print '</td></tr>'."\n";

	print '</table>'."\n";
	print '</div>';
}


print '<div class="center">';
print '<input class="button button-save reposition buttonforacesave" type="submit" name="submit" value="' . $langs->trans("Save") . '">';
print '<input class="button button-cancel reposition" type="submit" name="cancel" value="' . $langs->trans("Cancel") . '">';
print '</div>';

print '</form>';


// End of page
llxFooter();
$db->close();
