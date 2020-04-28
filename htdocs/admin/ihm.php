<?php
/* Copyright (C) 2001-2005	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2016		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018       Ferran Marcet           <fmarcet@2byte.es>
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

if (! $user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');
$contextpage=GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'adminihm';   // To manage different context of search

if (! defined("MAIN_MOTD")) define("MAIN_MOTD", "");



/*
 * Action
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (GETPOST('cancel', 'alpha'))
{
	$action='';
}

// Convert action set_XXX and del_XXX to set var (this is used when no javascript on for ajax_constantonoff)
$regs = array();
if (preg_match('/^(set|del)_([A-Z_]+)$/', $action, $regs)) {
	if ($regs[1] == 'set') dolibarr_set_const($db, $regs[2], 1, 'chaine', 0, '', $conf->entity);
	else dolibarr_del_const($db, $regs[2], $conf->entity);
}

if ($action == 'removebackgroundlogin' && ! empty($conf->global->MAIN_LOGIN_BACKGROUND))
{
	dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", (int) $conf->global->MAIN_IHM_PARAMS_REV+1, 'chaine', 0, '', $conf->entity);
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$logofile=$conf->mycompany->dir_output.'/logos/'.$conf->global->MAIN_LOGIN_BACKGROUND;
	dol_delete_file($logofile);
	dolibarr_del_const($db, "MAIN_LOGIN_BACKGROUND", $conf->entity);
	$mysoc->logo='';

	/*$logosmallfile=$conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small;
    dol_delete_file($logosmallfile);
    dolibarr_del_const($db, "MAIN_INFO_SOCIETE_LOGO_SMALL",$conf->entity);
    $mysoc->logo_small='';

    $logominifile=$conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini;
    dol_delete_file($logominifile);
    dolibarr_del_const($db, "MAIN_INFO_SOCIETE_LOGO_MINI",$conf->entity);
    $mysoc->logo_mini='';*/
}

if ($action == 'update')
{
	dolibarr_set_const($db, "MAIN_LANG_DEFAULT", GETPOST("MAIN_LANG_DEFAULT", 'aZ09'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_IHM_PARAMS_REV", (int) $conf->global->MAIN_IHM_PARAMS_REV+1, 'chaine', 0, '', $conf->entity);
	//dolibarr_set_const($db, "MAIN_MULTILANGS", $_POST["MAIN_MULTILANGS"], 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, "MAIN_THEME", GETPOST("main_theme", 'aZ09'), 'chaine', 0, '', $conf->entity);

	/*$val=GETPOST('THEME_TOPMENU_DISABLE_IMAGE');
	if (! $val) dolibarr_del_const($db, 'THEME_TOPMENU_DISABLE_IMAGE', $conf->entity);
	else dolibarr_set_const($db, 'THEME_TOPMENU_DISABLE_IMAGE', GETPOST('THEME_TOPMENU_DISABLE_IMAGE'), 'chaine', 0, '', $conf->entity);*/

	$val=(implode(',', (colorStringToArray(GETPOST('THEME_ELDY_BACKBODY'), array()))));
	if ($val == '') dolibarr_del_const($db, 'THEME_ELDY_BACKBODY', $conf->entity);
	else dolibarr_set_const($db, 'THEME_ELDY_BACKBODY', $val, 'chaine', 0, '', $conf->entity);

	$val=(implode(',', (colorStringToArray(GETPOST('THEME_ELDY_TOPMENU_BACK1'), array()))));
	if ($val == '') dolibarr_del_const($db, 'THEME_ELDY_TOPMENU_BACK1', $conf->entity);
	else dolibarr_set_const($db, 'THEME_ELDY_TOPMENU_BACK1', $val, 'chaine', 0, '', $conf->entity);

	$val=(implode(',', (colorStringToArray(GETPOST('THEME_ELDY_VERMENU_BACK1'), array()))));
	if ($val == '') dolibarr_del_const($db, 'THEME_ELDY_VERMENU_BACK1', $conf->entity);
	else dolibarr_set_const($db, 'THEME_ELDY_VERMENU_BACK1', $val, 'chaine', 0, '', $conf->entity);

	$val=(implode(',', (colorStringToArray(GETPOST('THEME_ELDY_TEXTTITLENOTAB'), array()))));
	if ($val == '') dolibarr_del_const($db, 'THEME_ELDY_TEXTTITLENOTAB', $conf->entity);
	else dolibarr_set_const($db, 'THEME_ELDY_TEXTTITLENOTAB', $val, 'chaine', 0, '', $conf->entity);

	$val=(implode(',', (colorStringToArray(GETPOST('THEME_ELDY_BACKTITLE1'), array()))));
	if ($val == '') dolibarr_del_const($db, 'THEME_ELDY_BACKTITLE1', $conf->entity);
	else dolibarr_set_const($db, 'THEME_ELDY_BACKTITLE1', $val, 'chaine', 0, '', $conf->entity);

	$val=(implode(',', (colorStringToArray(GETPOST('THEME_ELDY_TEXTTITLE'), array()))));
	if ($val == '') dolibarr_del_const($db, 'THEME_ELDY_TEXTTITLE', $conf->entity);
	else dolibarr_set_const($db, 'THEME_ELDY_TEXTTITLE', $val, 'chaine', 0, '', $conf->entity);

	$val=(implode(',', (colorStringToArray(GETPOST('THEME_ELDY_LINEIMPAIR1'), array()))));
	if ($val == '') dolibarr_del_const($db, 'THEME_ELDY_LINEIMPAIR1', $conf->entity);
	else dolibarr_set_const($db, 'THEME_ELDY_LINEIMPAIR1', $val, 'chaine', 0, '', $conf->entity);
	$val=(implode(',', (colorStringToArray(GETPOST('THEME_ELDY_LINEIMPAIR1'), array()))));
	if ($val == '') dolibarr_del_const($db, 'THEME_ELDY_LINEIMPAIR2', $conf->entity);
	else dolibarr_set_const($db, 'THEME_ELDY_LINEIMPAIR2', $val, 'chaine', 0, '', $conf->entity);

	$val=(implode(',', (colorStringToArray(GETPOST('THEME_ELDY_LINEPAIR1'), array()))));
	if ($val == '') dolibarr_del_const($db, 'THEME_ELDY_LINEPAIR1', $conf->entity);
	else dolibarr_set_const($db, 'THEME_ELDY_LINEPAIR1', $val, 'chaine', 0, '', $conf->entity);
	$val=(implode(',', (colorStringToArray(GETPOST('THEME_ELDY_LINEPAIR1'), array()))));
	if ($val == '') dolibarr_del_const($db, 'THEME_ELDY_LINEPAIR2', $conf->entity);
	else dolibarr_set_const($db, 'THEME_ELDY_LINEPAIR2', $val, 'chaine', 0, '', $conf->entity);

	$val=(implode(',', (colorStringToArray(GETPOST('THEME_ELDY_TEXTLINK'), array()))));
	if ($val == '') dolibarr_del_const($db, 'THEME_ELDY_TEXTLINK', $conf->entity);
	else dolibarr_set_const($db, 'THEME_ELDY_TEXTLINK', $val, 'chaine', 0, '', $conf->entity);

	$val=(implode(',', (colorStringToArray(GETPOST('THEME_ELDY_USE_HOVER'), array()))));
	if ($val == '') dolibarr_del_const($db, 'THEME_ELDY_USE_HOVER', $conf->entity);
	else dolibarr_set_const($db, "THEME_ELDY_USE_HOVER", $val, 'chaine', 0, '', $conf->entity);

	$val=(implode(',', (colorStringToArray(GETPOST('THEME_ELDY_USE_CHECKED'), array()))));
	if ($val == '') dolibarr_del_const($db, 'THEME_ELDY_USE_CHECKED', $conf->entity);
	else dolibarr_set_const($db, "THEME_ELDY_USE_CHECKED", $val, 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, "MAIN_SIZE_LISTE_LIMIT", GETPOST("main_size_liste_limit", 'int'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_SIZE_SHORTLIST_LIMIT", GETPOST("main_size_shortliste_limit", 'int'), 'chaine', 0, '', $conf->entity);

	//dolibarr_set_const($db, "MAIN_DISABLE_JAVASCRIPT", GETPOST("MAIN_DISABLE_JAVASCRIPT", 'aZ09'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_BUTTON_HIDE_UNAUTHORIZED", GETPOST("MAIN_BUTTON_HIDE_UNAUTHORIZED", 'aZ09'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_START_WEEK", GETPOST("MAIN_START_WEEK", 'int'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_DEFAULT_WORKING_DAYS", GETPOST("MAIN_DEFAULT_WORKING_DAYS", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_DEFAULT_WORKING_HOURS", GETPOST("MAIN_DEFAULT_WORKING_HOURS", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_SHOW_LOGO", GETPOST("MAIN_SHOW_LOGO", 'aZ09'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_FIRSTNAME_NAME_POSITION", GETPOST("MAIN_FIRSTNAME_NAME_POSITION", 'aZ09'), 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, "MAIN_HELPCENTER_DISABLELINK", GETPOST('MAIN_HELPCENTER_DISABLELINK', 'aZ09'), 'chaine', 0, '', 0);	// Param for all entities
	dolibarr_set_const($db, "MAIN_MOTD", dol_htmlcleanlastbr(GETPOST("main_motd", 'none')), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_HOME", dol_htmlcleanlastbr(GETPOST("main_home", 'none')), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_HELP_DISABLELINK", GETPOST("MAIN_HELP_DISABLELINK", 'aZ09'), 'chaine', 0, '', 0);	    // Param for all entities
	dolibarr_set_const($db, "MAIN_BUGTRACK_ENABLELINK", GETPOST('MAIN_BUGTRACK_ENABLELINK', 'aZ09'), 'chaine', 0, '', $conf->entity);

	$varforimage='imagebackground'; $dirforimage=$conf->mycompany->dir_output.'/logos/';
	if ($_FILES[$varforimage]["tmp_name"])
	{
		$reg = array();
		if (preg_match('/([^\\/:]+)$/i', $_FILES[$varforimage]["name"], $reg))
		{
			$original_file=$reg[1];

			$isimage=image_format_supported($original_file);
			if ($isimage >= 0)
			{
				dol_syslog("Move file ".$_FILES[$varforimage]["tmp_name"]." to ".$dirforimage.$original_file);
				if (! is_dir($dirforimage))
				{
					dol_mkdir($dirforimage);
				}
				$result=dol_move_uploaded_file($_FILES[$varforimage]["tmp_name"], $dirforimage.$original_file, 1, 0, $_FILES[$varforimage]['error']);
				if ($result > 0)
				{
					dolibarr_set_const($db, "MAIN_LOGIN_BACKGROUND", $original_file, 'chaine', 0, '', $conf->entity);
				}
				elseif (preg_match('/^ErrorFileIsInfectedWithAVirus/', $result))
				{
					$error++;
					$langs->load("errors");
					$tmparray=explode(':', $result);
					setEventMessages($langs->trans('ErrorFileIsInfectedWithAVirus', $tmparray[1]), null, 'errors');
				}
				else
				{
					$error++;
					setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
				}
			}
			else
			{
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorBadImageFormat"), null, 'errors');
			}
		}
	}



	$_SESSION["mainmenu"]="";   // Le gestionnaire de menu a pu changer

	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}


/*
 * View
 */

$wikihelp='EN:First_setup|FR:Premiers_param&eacute;trages|ES:Primeras_configuraciones';
llxHeader('', $langs->trans("Setup"), $wikihelp);

$form=new Form($db);
$formother=new FormOther($db);
$formadmin=new FormAdmin($db);

print load_fiche_titre($langs->trans("GUISetup"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("DisplayDesc")."</span><br>\n";

//WYSIWYG Editor
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

print '<form enctype="multipart/form-data" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

clearstatcache();

print '<br>';
print '<table summary="edit" class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Language").'</td><td></td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';

// Default language
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("DefaultLanguage").'</td><td>';
print $formadmin->select_language($conf->global->MAIN_LANG_DEFAULT, 'MAIN_LANG_DEFAULT', 1, 0, 0, 0, 0, 'minwidth300', 2);
print '</td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';

// Multilingual GUI
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("EnableMultilangInterface").'</td><td>';
print ajax_constantonoff("MAIN_MULTILANGS");
print '</td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';

print '</table><br>'."\n";

// Themes and themes options
showSkins(null, 1);
print '<br>';

// Other
print '<table summary="otherparameters" class="noborder centpercent">';
print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Miscellaneous").'</td><td></td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';

// Disable javascript and ajax
print '<tr class="oddeven"><td>'.$langs->trans("DisableJavascript").'</td><td>';
print ajax_constantonoff("MAIN_DISABLE_JAVASCRIPT");
print '</td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';

// Max size of lists
print '<tr class="oddeven"><td>'.$langs->trans("DefaultMaxSizeList").'</td><td><input class="flat" name="main_size_liste_limit" size="4" value="' . $conf->global->MAIN_SIZE_LISTE_LIMIT . '"></td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';

// Max size of short lists on customer card
print '<tr class="oddeven"><td>'.$langs->trans("DefaultMaxSizeShortList").'</td><td><input class="flat" name="main_size_shortliste_limit" size="4" value="' . $conf->global->MAIN_SIZE_SHORTLIST_LIMIT . '"></td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';

// show input border
/*
 print '<tr><td>'.$langs->trans("showInputBorder").'</td><td>';
 print $form->selectyesno('main_showInputBorder',isset($conf->global->THEME_ELDY_SHOW_BORDER_INPUT)?$conf->global->THEME_ELDY_SHOW_BORDER_INPUT:0,1);
 print '</td>';
 print '<td width="20">&nbsp;</td>';
 print '</tr>';
 */

// First day for weeks
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("WeekStartOnDay").'</td><td>';
print $formother->select_dayofweek((isset($conf->global->MAIN_START_WEEK)?$conf->global->MAIN_START_WEEK:'1'), 'MAIN_START_WEEK', 0);
print '</td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';

// DefaultWorkingDays
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("DefaultWorkingDays").'</td><td>';
print '<input type="text" name="MAIN_DEFAULT_WORKING_DAYS" size="5" value="'.(isset($conf->global->MAIN_DEFAULT_WORKING_DAYS)?$conf->global->MAIN_DEFAULT_WORKING_DAYS:'1-5').'">';
print '</td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';

// DefaultWorkingHours
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("DefaultWorkingHours").'</td><td>';
print '<input type="text" name="MAIN_DEFAULT_WORKING_HOURS" size="5" value="'.(isset($conf->global->MAIN_DEFAULT_WORKING_HOURS)?$conf->global->MAIN_DEFAULT_WORKING_HOURS:'9-18').'">';
print '</td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';

// Firstname/Name
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("FirstnameNamePosition").'</td><td>';
$array=array(0=>$langs->trans("Firstname").' '.$langs->trans("Lastname"), 1=>$langs->trans("Lastname").' '.$langs->trans("Firstname"));
print $form->selectarray('MAIN_FIRSTNAME_NAME_POSITION', $array, (isset($conf->global->MAIN_FIRSTNAME_NAME_POSITION)?$conf->global->MAIN_FIRSTNAME_NAME_POSITION:0));
print '</td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';

// Hide unauthorized button
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("ButtonHideUnauthorized").'</td><td>';
print $form->selectyesno('MAIN_BUTTON_HIDE_UNAUTHORIZED', isset($conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED)?$conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED:0, 1);
print '</td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';

// Hide version link
/*

print '<tr><td class="titlefield">'.$langs->trans("HideVersionLink").'</td><td>';
print $form->selectyesno('MAIN_HIDE_VERSION',$conf->global->MAIN_HIDE_VERSION,1);
print '</td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';
*/

// Show bugtrack link
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("ShowBugTrackLink", $langs->transnoentitiesnoconv("FindBug")).'</td><td>';
print $form->selectyesno('MAIN_BUGTRACK_ENABLELINK', $conf->global->MAIN_BUGTRACK_ENABLELINK, 1);
print '</td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';

// Hide wiki link on login page
$pictohelp='<span class="fa fa-question-circle"></span>';
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("DisableLinkToHelp", $pictohelp).'</td><td>';
print $form->selectyesno('MAIN_HELP_DISABLELINK', isset($conf->global->MAIN_HELP_DISABLELINK)?$conf->global->MAIN_HELP_DISABLELINK:0, 1);
print '</td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';

// Message of the day on home page
$substitutionarray=getCommonSubstitutionArray($langs, 0, array('object','objectamount'));
complete_substitutions_array($substitutionarray, $langs);

print '<tr class="oddeven"><td class="titlefield">';
$texthelp=$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
foreach($substitutionarray as $key => $val)
{
	$texthelp.=$key.'<br>';
}
print $form->textwithpicto($langs->trans("MessageOfDay"), $texthelp, 1, 'help', '', 0, 2, 'tooltipmessageofday');

print '</td><td colspan="2">';

$doleditor = new DolEditor('main_motd', (isset($conf->global->MAIN_MOTD)?$conf->global->MAIN_MOTD:''), '', 142, 'dolibarr_notes', 'In', false, true, true, ROWS_4, '90%');
$doleditor->Create();

print '</td></tr>'."\n";

print '</table>'."\n";

print '<br>';

// Other
print '<div class="div-table-responsive-no-min">';
print '<table summary="edit" class="noborder centpercent">';
print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("LoginPage").'</td><td></td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';

// Message on login page
$substitutionarray=getCommonSubstitutionArray($langs, 0, array('object','objectamount','user'));
complete_substitutions_array($substitutionarray, $langs);
print '<tr class="oddeven"><td>';
$texthelp=$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
foreach($substitutionarray as $key => $val)
{
	$texthelp.=$key.'<br>';
}
print $form->textwithpicto($langs->trans("MessageLogin"), $texthelp, 1, 'help', '', 0, 2, 'tooltipmessagelogin');
print '</td><td colspan="2">';
$doleditor = new DolEditor('main_home', (isset($conf->global->MAIN_HOME)?$conf->global->MAIN_HOME:''), '', 142, 'dolibarr_notes', 'In', false, true, true, ROWS_4, '90%');
$doleditor->Create();
print '</td></tr>'."\n";

// Hide helpcenter link on login page
print '<tr class="oddeven"><td class="titlefield">'.$langs->trans("DisableLinkToHelpCenter").'</td><td>';
print $form->selectyesno('MAIN_HELPCENTER_DISABLELINK', isset($conf->global->MAIN_HELPCENTER_DISABLELINK)?$conf->global->MAIN_HELPCENTER_DISABLELINK:0, 1);
print '</td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';

// Background
print '<tr class="oddeven"><td><label for="imagebackground">'.$langs->trans("BackgroundImageLogin").' (png,jpg)</label></td><td colspan="2">';
print '<div class="centpercent inline-block">';
$disabled = '';
if (! empty($conf->global->ADD_UNSPLASH_LOGIN_BACKGROUND)) $disabled = ' disabled="disabled"';
print '<input type="file" class="flat class=minwidth200" name="imagebackground" id="imagebackground"'.$disabled.'>';
if ($disabled)
{
	print '('.$langs->trans("DisabledByOptionADD_UNSPLASH_LOGIN_BACKGROUND").') ';
}
if (! empty($conf->global->MAIN_LOGIN_BACKGROUND)) {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=removebackgroundlogin">'.img_delete($langs->trans("Delete")).'</a>';
	if (file_exists($conf->mycompany->dir_output.'/logos/'.$conf->global->MAIN_LOGIN_BACKGROUND)) {
		print ' &nbsp; ';
		print '<img class="paddingleft valignmiddle" width="100px" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;file='.urlencode('logos/'.$conf->global->MAIN_LOGIN_BACKGROUND).'">';
	}
} else {
	print '<img class="paddingleft valignmiddle" width="100" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png">';
}
print '</div>';
print '</td></tr>';

print '</table>'."\n";
print '</div>';

print '<br>';
print '<div class="center">';
print '<input class="button" type="submit" name="submit" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';


// End of page
llxFooter();
$db->close();
