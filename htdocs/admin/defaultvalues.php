<?php
/* Copyright (C) 2017		Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2017-2018	Regis Houssin		<regis.houssin@capnetworks.com>
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

// Load translation files required by the page
$langs->loadLangs(array('companies', 'products', 'admin', 'sms', 'other', 'errors'));

if (!$user->admin) accessforbidden();

$id=GETPOST('rowid','int');
$action=GETPOST('action','alpha');

$mode = GETPOST('mode','aZ09')?GETPOST('mode','aZ09'):'createform';   // 'createform', 'filters', 'sortorder', 'focus'

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='page,param';
if (! $sortorder) $sortorder='ASC';

$defaulturl = GETPOST('defaulturl');
$defaultkey = GETPOST('defaultkey','alpha');
$defaultvalue = GETPOST('defaultvalue');

$defaulturl=preg_replace('/^\//', '', $defaulturl);

$urlpage = GETPOST('urlpage');
$key = GETPOST('key');
$value = GETPOST('value');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('admindefaultvalues','globaladmin'));


/*
 * Actions
 */

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
{
    $defaulturl='';
    $defaultkey='';
    $defaultvalue='';
    $toselect='';
    $search_array_options=array();
}

if ($action == 'setMAIN_ENABLE_DEFAULT_VALUES')
{
    if (GETPOST('value')) dolibarr_set_const($db, 'MAIN_ENABLE_DEFAULT_VALUES', 1, 'chaine', 0, '', $conf->entity);
    else dolibarr_set_const($db, 'MAIN_ENABLE_DEFAULT_VALUES', 0, 'chaine', 0, '', $conf->entity);
}

if (($action == 'add' || (GETPOST('add') && $action != 'update')) || GETPOST('actionmodify'))
{
	$error=0;

	if (($action == 'add' || (GETPOST('add') && $action != 'update')))
	{
    	if (empty($defaulturl))
    	{
    		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Url")), null, 'errors');
    		$error++;
    	}
    	if (empty($defaultkey))
    	{
    		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Field")), null, 'errors');
    		$error++;
    	}
	}
	if (GETPOST('actionmodify'))
	{
	    if (empty($urlpage))
	    {
	        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Url")), null, 'errors');
	        $error++;
	    }
	    if (empty($key))
	    {
	        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Field")), null, 'errors');
	        $error++;
	    }
	}

	if (! $error)
	{
	    $db->begin();

	    if ($action == 'add' || (GETPOST('add') && $action != 'update'))
	    {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."default_values(type, user_id, page, param, value, entity) VALUES ('".$db->escape($mode)."', 0, '".$db->escape($defaulturl)."','".$db->escape($defaultkey)."','".$db->escape($defaultvalue)."', ".$db->escape($conf->entity).")";
	    }
	    if (GETPOST('actionmodify'))
	    {
		    $sql = "UPDATE ".MAIN_DB_PREFIX."default_values SET page = '".$db->escape($urlpage)."', param = '".$db->escape($key)."', value = '".$db->escape($value)."'";
		    $sql.= " WHERE rowid = ".$id;
	    }

		$result = $db->query($sql);
		if ($result > 0)
		{
		    $db->commit();
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			$action="";
			$defaulturl='';
			$defaultkey='';
			$defaultvalue='';
		}
		else
		{
	        $db->rollback();
		    setEventMessages($db->lasterror(), null, 'errors');
			$action='';
		}
	}
}

// Delete line from delete picto
if ($action == 'delete')
{
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."default_values WHERE rowid = ".$db->escape($id);
	// Delete const
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

$param='&mode='.$mode;

$enabledisablehtml.= $langs->trans("EnableDefaultValues").' ';
if (empty($conf->global->MAIN_ENABLE_DEFAULT_VALUES))
{
    // Button off, click to enable
    $enabledisablehtml.= '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setMAIN_ENABLE_DEFAULT_VALUES&value=1'.$param.'">';
    $enabledisablehtml.= img_picto($langs->trans("Disabled"),'switch_off');
    $enabledisablehtml.= '</a>';
}
else
{
    // Button on, click to disable
    $enabledisablehtml.= '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setMAIN_ENABLE_DEFAULT_VALUES&value=0'.$param.'">';
    $enabledisablehtml.= img_picto($langs->trans("Activated"),'switch_on');
    $enabledisablehtml.= '</a>';
}

print load_fiche_titre($langs->trans("DefaultValues"), $enabledisablehtml, 'title_setup');

print $langs->trans("DefaultValuesDesc")."<br>\n";
print "<br>\n";

if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
if ($optioncss != '')  $param.='&optioncss='.$optioncss;
if ($defaulturl)        $param.='&defaulturl='.urlencode($defaulturl);
if ($defaultkey)        $param.='&defaultkey='.urlencode($defaultkey);
if ($defaultvalue)      $param.='&defaultvalue='.urlencode($defaultvalue);


print '<form action="'.$_SERVER["PHP_SELF"].((empty($user->entity) && $debug)?'?debug=1':'').'" method="POST">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';

$head=defaultvalues_prepare_head();

dol_fiche_head($head, $mode, '', -1, '');

if ($mode == 'sortorder')
{
    print info_admin($langs->trans("WarningSettingSortOrder")).'<br>';
}
if ($mode == 'focus')
{
    print info_admin($langs->trans("FeatureNotYetAvailable")).'<br>';
}

print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" id="action" name="action" value="">';
print '<input type="hidden" id="mode" name="mode" value="'.dol_escape_htmltag($mode).'">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
// Page
$texthelp=$langs->trans("PageUrlForDefaultValues");
if ($mode == 'createform') $texthelp.=$langs->trans("PageUrlForDefaultValuesCreate", 'societe/card.php', 'societe/card.php?abc=val1&def=val2');
else $texthelp.=$langs->trans("PageUrlForDefaultValuesList", 'societe/list.php', 'societe/card.php?abc=val1&def=val2');
$texturl=$form->textwithpicto($langs->trans("Url"), $texthelp);
print_liste_field_titre($texturl,$_SERVER["PHP_SELF"],'page,param','',$param,'',$sortfield,$sortorder);
// Field
$texthelp=$langs->trans("TheKeyIsTheNameOfHtmlField");
if ($mode != 'sortorder')
{
    $textkey=$form->textwithpicto($langs->trans("Field"), $texthelp);
}
else
{
    $texthelp='field or alias.field';
    $textkey=$form->textwithpicto($langs->trans("Field"), $texthelp);
}
print_liste_field_titre($textkey,$_SERVER["PHP_SELF"],'param','',$param,'',$sortfield,$sortorder);
// Value
if ($mode != 'focus')
{
    if ($mode != 'sortorder')
    {
        $substitutionarray=getCommonSubstitutionArray($langs, 2, array('object','objectamount')); // Must match list into GETPOST
		unset($substitutionarray['__USER_SIGNATURE__']);
        $texthelp=$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
        foreach($substitutionarray as $key => $val)
        {
            $texthelp.=$key.' -> '.$val.'<br>';
        }
        $textvalue=$form->textwithpicto($langs->trans("Value"), $texthelp, 1, 'help', '', 0, 2, 'subsitutiontooltip');
    }
    else
    {
        $texthelp='ASC or DESC';
        $textvalue=$form->textwithpicto($langs->trans("SortOrder"), $texthelp);
    }
    print_liste_field_titre($textvalue, $_SERVER["PHP_SELF"], 'value', '', $param, '', $sortfield, $sortorder);
}
// Entity
if (! empty($conf->multicompany->enabled) && !$user->entity) print_liste_field_titre("Entity",$_SERVER["PHP_SELF"],'entity,page','',$param,'',$sortfield,$sortorder);
// Actions
print '<td align="center"></td>';
print "</tr>\n";


// Line to add new record
print "\n";

print '<tr class="oddeven">';
// Page
print '<td>';
print '<input type="text" class="flat minwidth200 maxwidthonsmartphone" name="defaulturl" value="">';
print '</td>'."\n";
// Field
print '<td>';
print '<input type="text" class="flat maxwidth100onsmartphone" name="defaultkey" value="">';
print '</td>';
// Value
if ($mode != 'focus')
{
    print '<td>';
    print '<input type="text" class="flat maxwidth100onsmartphone" name="defaultvalue" value="">';
    print '</td>';
}
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
$disabled='';
if (empty($conf->global->MAIN_ENABLE_DEFAULT_VALUES)) $disabled=' disabled="disabled"';
print '<input type="submit" class="button"'.$disabled.' value="'.$langs->trans("Add").'" name="add">';
print "</td>\n";
print '</tr>';


// Show constants
$sql = "SELECT rowid, entity, type, page, param, value";
$sql.= " FROM ".MAIN_DB_PREFIX."default_values";
$sql.= " WHERE type = '".$db->escape($mode)."'";
$sql.= " AND entity IN (".$user->entity.",".$conf->entity.")";
$sql.= $db->order($sortfield, $sortorder);

dol_syslog("translation::select from table", LOG_DEBUG);
$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;

	while ($i < $num)
	{
		$obj = $db->fetch_object($result);


		print "\n";

		print '<tr class="oddeven">';

		// Page
		print '<td>';
		if ($action != 'edit' || GETPOST('rowid') != $obj->rowid) print $obj->page;
		else print '<input type="text" name="urlpage" value="'.dol_escape_htmltag($obj->page).'">';
		print '</td>'."\n";

		// Field
		print '<td>';
		if ($action != 'edit' || GETPOST('rowid') != $obj->rowid) print $obj->param;
		else print '<input type="text" name="key" value="'.dol_escape_htmltag($obj->param).'">';
		print '</td>'."\n";

		// Value
		if ($mode != 'focus')
		{
    		print '<td>';
    		/*print '<input type="hidden" name="const['.$i.'][rowid]" value="'.$obj->rowid.'">';
    		print '<input type="hidden" name="const['.$i.'][lang]" value="'.$obj->lang.'">';
    		print '<input type="hidden" name="const['.$i.'][name]" value="'.$obj->transkey.'">';
    		print '<input type="text" id="value_'.$i.'" class="flat inputforupdate" size="30" name="const['.$i.'][value]" value="'.dol_escape_htmltag($obj->transvalue).'">';
    		*/
    		if ($action != 'edit' || GETPOST('rowid') != $obj->rowid) print $obj->value;
    		else print '<input type="text" name="value" value="'.dol_escape_htmltag($obj->value).'">';
    		print '</td>';
		}

		if (! empty($conf->multicompany->enabled) && !$user->entity)
		{
		    print '<td></td>';
		}

		// Actions
		print '<td align="center">';
		if ($action != 'edit' || GETPOST('rowid') != $obj->rowid)
		{
    		print '<a href="'.$_SERVER['PHP_SELF'].'?rowid='.$obj->rowid.'&entity='.$obj->entity.'&mode='.$mode.'&action=edit'.((empty($user->entity) && $debug)?'&debug=1':'').'">'.img_edit().'</a>';
    		print ' &nbsp; ';
    		print '<a href="'.$_SERVER['PHP_SELF'].'?rowid='.$obj->rowid.'&entity='.$obj->entity.'&mode='.$mode.'&action=delete'.((empty($user->entity) && $debug)?'&debug=1':'').'">'.img_delete().'</a>';
		}
		else
		{
		    print '<input type="hidden" name="page" value="'.$page.'">';
		    print '<input type="hidden" name="rowid" value="'.$id.'">';
		    print '<div name="'.(! empty($obj->rowid)?$obj->rowid:'none').'"></div>';
		    print '<input type="submit" class="button" name="actionmodify" value="'.$langs->trans("Modify").'">';
		    print '<input type="submit" class="button" name="actioncancel" value="'.$langs->trans("Cancel").'">';
		}
		print '</td>';

		print "</tr>\n";
		print "\n";
		$i++;
	}
}
else
{
    dol_print_error($db);
}


print '</table>';
print '</div>';

dol_fiche_end();

print "</form>\n";


llxFooter();

$db->close();
