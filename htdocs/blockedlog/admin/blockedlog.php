<?php
/* Copyright (C) 2017      ATM Consulting      <contact@atm-consulting.fr>
 * Copyright (C) 2017-2018 Laurent Destailleur <eldy@destailleur.fr>
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
 *	\file       htdocs/blockedlog/admin/blockedlog.php
 *  \ingroup    blockedlog
 *  \brief      Page setup for blockedlog module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/blockedlog/lib/blockedlog.lib.php';
require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin","other","blockedlog"));

if (! $user->admin || empty($conf->blockedlog->enabled)) accessforbidden();

$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');


/*
 * Actions
 */

if (preg_match('/set_(.*)/', $action, $reg))
{
	$code=$reg[1];
	$values = GETPOST($code);
	if(is_array($values))$values = implode(',', $values);

	if (dolibarr_set_const($db, $code, $values, 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/', $action, $reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}


/*
 *	View
 */

$form=new Form($db);
$block_static = new BlockedLog($db);

llxHeader('', $langs->trans("BlockedLogSetup"));

$linkback='';
if (GETPOST('withtab', 'alpha'))
{
	$linkback='<a href="'.($backtopage?$backtopage:DOL_URL_ROOT.'/admin/modules.php').'">'.$langs->trans("BackToModuleList").'</a>';
}

print load_fiche_titre($langs->trans("ModuleSetup").' '.$langs->trans('BlockedLog'), $linkback);

if (GETPOST('withtab', 'alpha'))
{
	$head=blockedlogadmin_prepare_head();
	dol_fiche_head($head, 'blockedlog', '', -1);
}


print '<span class="opacitymedium">'.$langs->trans("BlockedLogDesc")."</span><br>\n";

print '<br>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Key").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

print '<tr class="oddeven">';
print '<td class="titlefield">';
print $langs->trans("CompanyInitialKey").'</td><td>';
print $block_static->getSignature();
print '</td></tr>';

if (!empty($conf->global->BLOCKEDLOG_USE_REMOTE_AUTHORITY)) {
	// Example with a yes / no select
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("BlockedLogAuthorityUrl").img_info($langs->trans('BlockedLogAuthorityNeededToStoreYouFingerprintsInNonAlterableRemote')).'</td>';
	print '<td class="right" width="300">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_BLOCKEDLOG_AUTHORITY_URL">';
	print '<input type="text" name="BLOCKEDLOG_AUTHORITY_URL" value="'.$conf->global->BLOCKEDLOG_AUTHORITY_URL.'" size="40" />';
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';
}

print '<tr class="oddeven">';
print '<td>'.$langs->trans("BlockedLogDisableNotAllowedForCountry").'</td>';
print '<td>';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY">';

$sql = "SELECT rowid, code as code_iso, code_iso as code_iso3, label, favorite";
$sql.= " FROM ".MAIN_DB_PREFIX."c_country";
$sql.= " WHERE active > 0";

$countryArray=array();
$resql=$db->query($sql);
if ($resql)
{
	while ($obj = $db->fetch_object($resql))
	{
			$countryArray[$obj->code_iso] = ($obj->code_iso && $langs->transnoentitiesnoconv("Country".$obj->code_iso)!="Country".$obj->code_iso?$langs->transnoentitiesnoconv("Country".$obj->code_iso):($obj->label!='-'?$obj->label:''));
	}
}

$seledted = empty($conf->global->BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY) ? array() : explode(',', $conf->global->BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY);

print $form->multiselectarray('BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY', $countryArray, $seledted);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';

print '</td>';


print '<tr class="oddeven">';
print '<td class="titlefield">';
print $langs->trans("ListOfTrackedEvents").'</td><td>';
$arrayoftrackedevents=$block_static->trackedevents;
foreach($arrayoftrackedevents as $key => $val)
{
	print $key.' - '.$langs->trans($val).'<br>';
}

print '</td></tr>';

print '</tr>';

print '</table>';

if (GETPOST('withtab', 'alpha'))
{
	dol_fiche_end();
}

print '<br><br>';

// End of page
llxFooter();
$db->close();
