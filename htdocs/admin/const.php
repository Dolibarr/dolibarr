<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
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
 *	\file       htdocs/admin/const.php
 *	\ingroup    setup
 *	\brief      Admin page to define miscellaneous constants
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("admin");

if (! $user->admin)
	accessforbidden();

$rowid=GETPOST('rowid','int');
$entity=GETPOST('entity','int');
$action=GETPOST('action','alpha');
$update=GETPOST('update','alpha');
$delete=GETPOST('delete');	// Do not use alpha here
$debug=GETPOST('debug','int');
$consts=GETPOST('const');
$constname=GETPOST('constname','alpha');
$constvalue=GETPOST('constvalue');
$constnote=GETPOST('constnote','alpha');
$consttype=(GETPOST('consttype','alpha')?GETPOST('consttype','alpha'):'chaine');

$typeconst=array('yesno' => 'yesno', 'texte' => 'texte', 'chaine' => 'chaine');
$mesg='';



/*
 * Actions
 */

if ($action == 'add')
{
	$error=0;

	if (empty($constname))
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Name")).'</div>';
		$error++;
	}
	if ($constvalue == '')
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Value")).'</div>';
		$error++;
	}

	if (! $error)
	{
		if (dolibarr_set_const($db, $constname, $constvalue, $typeconst[$consttype], 1, $constnote, $entity) >= 0)
		{
			setEventMessage($langs->trans("RecordSaved"));
		}
		else
		{
			dol_print_error($db);
		}
	}
}

// Mass update
if (! empty($consts) && $action == 'update')
{
	$nbmodified=0;
	foreach($consts as $const)
	{
		if (! empty($const["check"]))
		{
			if (dolibarr_set_const($db, $const["name"], $const["value"], $const["type"], 1, $const["note"], $const["entity"]) >= 0)
			{
				$nbmodified++;
			}
			else
			{
				dol_print_error($db);
			}
		}
	}
	if ($nbmodified > 0) setEventMessage($langs->trans("RecordSaved"));
	$action='';
}

// Mass delete
if (! empty($consts) && $action == 'delete')
{

	$nbdeleted=0;
	foreach($consts as $const)
	{
		if (! empty($const["check"]))	// Is checkbox checked
		{
			if (dolibarr_del_const($db, $const["rowid"], -1) >= 0)
			{
				$nbdeleted++;
			}
			else
			{
				dol_print_error($db);
			}
		}
	}
	if ($nbdeleted > 0) setEventMessage($langs->trans("RecordDeleted"));
	$action='';
}

// Delete line from delete picto
if ($action == 'delete')
{
	if (dolibarr_del_const($db, $rowid, $entity) >= 0)
	{
		setEventMessage($langs->trans("RecordDeleted"));
	}
	else
	{
		dol_print_error($db);
	}
}


/*
 * View
 */

llxHeader('',$langs->trans("OtherSetup"));

// Add logic to show/hide buttons
if ($conf->use_javascript_ajax)
{
?>
<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery("#updateconst").hide();
	jQuery("#delconst").hide();
	jQuery(".checkboxfordelete").click(function() {
		jQuery("#delconst").show();
		jQuery("#action").val('delete');
	});
	jQuery(".inputforupdate").keyup(function() {	// keypress does not support back
		var field_id = jQuery(this).attr("id");
		var row_num = field_id.split("_");
		jQuery("#updateconst").show();
		jQuery("#action").val('update');
		jQuery("#check_" + row_num[1]).attr("checked",true);
	});
});
</script>
<?php
}

print_fiche_titre($langs->trans("OtherSetup"),'','setup');

print $langs->trans("ConstDesc")."<br>\n";
print "<br>\n";

dol_htmloutput_mesg($mesg);

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td>'.$langs->trans("Comment").'</td>';
if (! empty($conf->multicompany->enabled) && !$user->entity) print '<td>'.$langs->trans("Entity").'</td>';
print '<td align="center">'.$langs->trans("Action").'</td>';
print "</tr>\n";


$form = new Form($db);


// Line to add new record
$var=false;
print "\n";
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="add">';

print '<tr '.$bc[$var].'><td><input type="text" class="flat" size="24" name="constname" value=""></td>'."\n";
print '<td>';
print '<input type="text" class="flat" size="30" name="constvalue" value="">';
print '</td><td>';
print '<input type="text" class="flat" size="40" name="constnote" value="">';
print '</td>';
// Limit to superadmin
if (! empty($conf->multicompany->enabled) && !$user->entity)
{
	print '<td>';
	print '<input type="text" class="flat" size="1" name="entity" value="'.$conf->entity.'">';
	print '</td>';
}
else
{
	print '<input type="hidden" name="entity" value="'.$conf->entity.'">';
}
print '<td align="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Add").'" name="Button">';
print "</td>\n";
print '</tr>';

print '</form>';
print "\n";

print '<form action="'.$_SERVER["PHP_SELF"].((empty($user->entity) && $debug)?'?debug=1':'').'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" id="action" name="action" value="">';

// Show constants
$sql = "SELECT";
$sql.= " rowid";
$sql.= ", ".$db->decrypt('name')." as name";
$sql.= ", ".$db->decrypt('value')." as value";
$sql.= ", type";
$sql.= ", note";
$sql.= ", entity";
$sql.= " FROM ".MAIN_DB_PREFIX."const";
$sql.= " WHERE entity IN (".$user->entity.",".$conf->entity.")";
if (empty($user->entity) && $debug) {} // to force for superadmin
elseif ($user->entity || empty($conf->multicompany->enabled)) $sql.= " AND visible = 1";
$sql.= " ORDER BY entity, name ASC";

dol_syslog("Const::listConstant sql=".$sql);
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
		print '<input type="hidden" name="const['.$i.'][rowid]" value="'.$obj->rowid.'">';
		print '<input type="hidden" name="const['.$i.'][name]" value="'.$obj->name.'">';
		print '<input type="hidden" name="const['.$i.'][type]" value="'.$obj->type.'">';

		print '<tr '.$bc[$var].'><td>'.$obj->name.'</td>'."\n";

		// Value
		print '<td>';
		print '<input type="text" id="value_'.$i.'" class="flat inputforupdate" size="30" name="const['.$i.'][value]" value="'.htmlspecialchars($obj->value).'"';
		print '>';
		print '</td><td>';

		// Note
		print '<input type="text" id="note_'.$i.'"class="flat inputforupdate" size="40" name="const['.$i.'][note]" value="'.htmlspecialchars($obj->note,1).'"';
		print '>';
		print '</td>';

		// Entity limit to superadmin
		if (! empty($conf->multicompany->enabled) && !$user->entity)
		{
			print '<td>';
			print '<input type="text" class="flat" size="1" name="const['.$i.'][entity]" value="'.$obj->entity.'">';
			print '</td>';
		}
		else
		{
			print '<input type="hidden" name="const['.$i.'][entity]" value="'.$obj->entity.'">';
		}

		print '<td align="center">';
		if ($conf->use_javascript_ajax)
		{
			print '<input type="checkbox" class="flat checkboxfordelete" id="check_'.$i.'" name="const['.$i.'][check]" value="1">';
			print ' &nbsp; ';
		}
		else
		{
			print '<a href="'.$_SERVER['PHP_SELF'].'?rowid='.$obj->rowid.'&entity='.$obj->entity.'&action=delete'.((empty($user->entity) && $debug)?'&debug=1':'').'">'.img_delete().'</a>';
		}

		print "</td></tr>\n";

		print "\n";
		$i++;
	}
}


print '</table>';

if ($conf->use_javascript_ajax)
{
	print '<br>';
	print '<div id="updateconst" align="right">';
	print '<input type="submit" name="update" class="button" value="'.$langs->trans("Modify").'">';
	print '</div>';
	print '<div id="delconst" align="right">';
	print '<input type="submit" name="delete" class="button" value="'.$langs->trans("Delete").'">';
	print '</div>';
}

print "</form>\n";

llxFooter();

$db->close();
?>
