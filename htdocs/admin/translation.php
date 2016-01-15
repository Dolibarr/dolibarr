<?php
/* Copyright (C) 2007-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis.houssin@capnetworks.com>
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
 *       \file       htdocs/admin/translation.php
 *       \brief      Page to show translation information
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("companies");
$langs->load("products");
$langs->load("admin");
$langs->load("sms");
$langs->load("other");
$langs->load("errors");

if (!$user->admin) accessforbidden();

$id=GETPOST('rowid','int');
$action=GETPOST('action','alpha');
$langcode=GETPOST('langcode','alpha');
$transkey=GETPOST('transkey','alpha');
$transvalue=GETPOST('transvalue','alpha');


/*
 * Actions
 */


if ($action == 'add' || (GETPOST('add') && $action != 'update'))
{
	$error=0;

	if (empty($langcode))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Language")), null, 'errors');
		$error++;
	}
	if ($transkey == '')
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Key")), null, 'errors');
		$error++;
	}
	if ($transvalue == '')
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Value")), null, 'errors');
		$error++;
	}
	if (! $error)
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."overwrite_trans(lang, transkey, transvalue) VALUE ('".$db->escape($langcode)."','".$db->escape($transkey)."','".$db->escape($transvalue)."')";
		$result = $db->query($sql);
		if ($result > 0)
		{
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			$action="";
			$transkey="";
			$transvalue="";
		}
		else
		{
			dol_print_error($db);
			$action='';
		}
	}
}

// Delete line from delete picto
if ($action == 'delete')
{
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."overwrite_trans WHERE rowid = ".$db->escape($id);
	$result = $db->query($sql);
	if ($result >= 0)
	{
		setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
	}
	else
	{
		dol_print_error($db);
	}
}






/*
 * View
 */

$wikihelp='EN:Setup|FR:Paramétrage|ES:Configuración';
llxHeader('',$langs->trans("Setup"),$wikihelp);

print load_fiche_titre($langs->trans("TranslationSetup"),'','title_setup');

print $langs->trans("TranslationDesc")."<br>\n";
print "<br>\n";

print $langs->trans("CurrentUserLanguage").': <strong>'.$langs->defaultlang.'</strong><br>';
print '<br>';

print img_warning().' '.$langs->trans("SomeTranslationAreUncomplete").'<br>';
$urlwikitranslatordoc='http://wiki.dolibarr.org/index.php/Translator_documentation';
print $langs->trans("SeeAlso").': <a href="'.$urlwikitranslatordoc.'" target="_blank">'.$urlwikitranslatordoc.'</a><br>';

print '<br>';

print $langs->trans("TranslationOverwriteDesc",$langs->transnoentitiesnoconv("Language"),$langs->transnoentitiesnoconv("Key"),$langs->transnoentitiesnoconv("Value"))."<br>\n";

print '<br>';

print '<form action="'.$_SERVER["PHP_SELF"].((empty($user->entity) && $debug)?'?debug=1':'').'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" id="action" name="action" value="">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Language").'</td>';
print '<td>'.$langs->trans("Key").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
if (! empty($conf->multicompany->enabled) && !$user->entity) print '<td>'.$langs->trans("Entity").'</td>';
print '<td align="center"></td>';
print "</tr>\n";


// Line to add new record
$var=false;
print "\n";

print '<tr '.$bc[$var].'><td><input type="text" class="flat" size="24" name="langcode" value="'.GETPOST('langcode').'"></td>'."\n";
print '<td>';
print '<input type="text" class="flat" size="30" name="transkey" value="">';
print '</td><td>';
print '<input type="text" class="flat" size="40" name="transvalue" value="">';
print '</td>';
// Limit to superadmin
if (! empty($conf->multicompany->enabled) && !$user->entity)
{
	print '<td>';
	print '<input type="text" class="flat" size="1" name="entity" value="'.$conf->entity.'">';
	print '</td>';
	print '<td align="center">';
}
else
{
	print '<td align="center">';
	print '<input type="hidden" name="entity" value="'.$conf->entity.'">';
}
print '<input type="submit" class="button" value="'.$langs->trans("Add").'" name="add">';
print "</td>\n";
print '</tr>';


// Show constants
$sql = "SELECT";
$sql.= " rowid";
$sql.= ", lang";
$sql.= ", transkey";
$sql.= ", transvalue";
//$sql.= ", entity";
$sql.= " FROM ".MAIN_DB_PREFIX."overwrite_trans";
$sql.= " WHERE 1 = 1";
//$sql.= " AND entity IN (".$user->entity.",".$conf->entity.")";
//if ((empty($user->entity) || $user->admin) && $debug) {} 										// to force for superadmin to debug
//else if (! GETPOST('visible') || GETPOST('visible') != 'all') $sql.= " AND visible = 1";		// We must always have this. Otherwise, array is too large and submitting data fails due to apache POST or GET limits
//if (GETPOST('name')) $sql.=natural_search("name", GETPOST('name'));
//$sql.= " ORDER BY entity, name ASC";
$sql.= " ORDER BY lang ASC, transkey ASC";

dol_syslog("translation::select from table", LOG_DEBUG);
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	$var=false;

	while ($i < $num)
	{
		$obj = $db->fetch_object($result);
		$var=!$var;

		print "\n";

		print '<tr '.$bc[$var].'>';
		
		print '<td>'.$obj->lang.'</td>'."\n";
		print '<td>'.$obj->transkey.'</td>'."\n";

		// Value
		print '<td>';
		/*print '<input type="hidden" name="const['.$i.'][rowid]" value="'.$obj->rowid.'">';
		print '<input type="hidden" name="const['.$i.'][lang]" value="'.$obj->lang.'">';
		print '<input type="hidden" name="const['.$i.'][name]" value="'.$obj->transkey.'">';
		print '<input type="text" id="value_'.$i.'" class="flat inputforupdate" size="30" name="const['.$i.'][value]" value="'.dol_escape_htmltag($obj->transvalue).'">';
		*/
		print $obj->transvalue;
		print '</td>';

		print '<td align="center">';
		print '<a href="'.$_SERVER['PHP_SELF'].'?rowid='.$obj->rowid.'&entity='.$obj->entity.'&action=delete'.((empty($user->entity) && $debug)?'&debug=1':'').'">'.img_delete().'</a>';
		print '</td>';
		
		print "</tr>\n";
		print "\n";
		$i++;
	}
}


print '</table>';

print "</form>\n";


llxFooter();

$db->close();
