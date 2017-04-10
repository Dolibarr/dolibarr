<?php
/* Copyright (C) 2007-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/admin/defaultvalues.php
 *       \brief      Page to set default values used used in a create form
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';

$langs->load("companies");
$langs->load("products");
$langs->load("admin");
$langs->load("sms");
$langs->load("other");
$langs->load("errors");

if (!$user->admin) accessforbidden();

$id=GETPOST('rowid','int');
$action=GETPOST('action','alpha');

$mode = GETPOST('mode')?GETPOST('mode'):'overwrite';

$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='lang,transkey';
if (! $sortorder) $sortorder='ASC';

$defaulturl = GETPOST('defaulturl');
$defaultkey = GETPOST('defaultkey','alpha');
$defaultvalue = GETPOST('defaultvalue');


/*
 * Actions
 */

if (GETPOST('cancel')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // All test are required to be compatible with all browsers
{
    $defaulturl='';
    $defaultkey='';
    $defaultvalue='';
    $toselect='';
    $search_array_options=array();
}


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
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NewTranslationStringToShow")), null, 'errors');
		$error++;
	}
	if (! $error)
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."overwrite_trans(lang, transkey, transvalue) VALUES ('".$db->escape($langcode)."','".$db->escape($transkey)."','".$db->escape($transvalue)."')";
		$result = $db->query($sql);
		if ($result > 0)
		{
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			$action="";
			$defaulturl='';
			$defaultkey='';
			$defaultvalue='';
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

$form=new Form($db);
$formadmin = new FormAdmin($db);

$wikihelp='EN:Setup|FR:Paramétrage|ES:Configuración';
llxHeader('',$langs->trans("Setup"),$wikihelp);

print load_fiche_titre($langs->trans("DefaultValues"),'','title_setup');

print $langs->trans("DefaultValuesDesc")."<br>\n";
print "<br>\n";

$param='&mode='.$mode;
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
if ($optioncss != '')  $param.='&optioncss='.$optioncss;
if (defaulturl)        $param.='&defaulturl='.urlencode(defaulturl);
if (defaultkey)        $param.='&defaultkey='.urlencode(defaultkey);
if (defaultvalue)      $param.='&defaultvalue='.urlencode(defaultvalue);


print '<form action="'.$_SERVER["PHP_SELF"].((empty($user->entity) && $debug)?'?debug=1':'').'" method="POST">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

$head=defaultvalues_prepare_head();
    
dol_fiche_head($head, $mode, '', -1, '');


print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" id="action" name="action" value="">';
print '<input type="hidden" id="mode" name="mode" value="'.$mode.'">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
$texthelp=$langs->trans("PageUrlForDefaultValues");
if ($mode == 'createform') $texthelp.=$langs->trans("PageUrlForDefaultValuesCreate", 'societe/card.php');
else $texthelp.=$langs->trans("PageUrlForDefaultValuesList", 'societe/list.php');
$texturl=$form->textwithpicto($langs->trans("Url"), $texthelp);
print_liste_field_titre($texturl,$_SERVER["PHP_SELF"],'defaulturl','',$param,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Key"),$_SERVER["PHP_SELF"],'defaultkey','',$param,'',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Value"),$_SERVER["PHP_SELF"],'defaultvalue','',$param,'',$sortfield,$sortorder);
//if (! empty($conf->multicompany->enabled) && !$user->entity) print_liste_field_titre($langs->trans("Entity"),$_SERVER["PHP_SELF"],'entity,transkey','',$param,'',$sortfield,$sortorder);
print '<td align="center"></td>';
print "</tr>\n";


// Line to add new record
print "\n";

print '<tr class="oddeven">';
print '<td>';
print '<input type="text" class="flat minwidth200 maxwidthonsmartphone" name="defaulturl" value="">';
print '</td>'."\n";
print '<td>';
print '<input type="text" class="flat maxwidth100" name="defaultkey" value="">';
print '</td><td>';
print '<input type="text" class="flat maxwidthonsmartphone" name="defaultvalue" value="">';
print '</td>';
// Limit to superadmin
/*if (! empty($conf->multicompany->enabled) && !$user->entity)
{
	print '<td>';
	print '<input type="text" class="flat" size="1" name="entity" value="'.$conf->entity.'">';
	print '</td>';
	print '<td align="center">';
}
else
{*/
	print '<td align="center">';
	print '<input type="hidden" name="entity" value="'.$conf->entity.'">';
//}
print '<input type="submit" class="button" value="'.$langs->trans("Add").'" name="add">';
print "</td>\n";
print '</tr>';


// Show constants
$sql = "SELECT";
$sql.= " rowid";
$sql.= ", lang";
$sql.= ", transkey";
$sql.= ", transvalue";
$sql.= " FROM ".MAIN_DB_PREFIX."overwrite_trans";
$sql.= " WHERE 1 = 1";
//$sql.= " AND entity IN (".$user->entity.",".$conf->entity.")";
//if ((empty($user->entity) || $user->admin) && $debug) {} 										// to force for superadmin to debug
//else if (! GETPOST('visible') || GETPOST('visible') != 'all') $sql.= " AND visible = 1";		// We must always have this. Otherwise, array is too large and submitting data fails due to apache POST or GET limits
//if (GETPOST('name')) $sql.=natural_search("name", GETPOST('name'));
//$sql.= " ORDER BY entity, name ASC";
$sql.= $db->order($sortfield, $sortorder);

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

dol_fiche_end();

print "</form>\n";


llxFooter();

$db->close();
