<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Juanjo Menent			<jmenent@2byte.es>
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
 *	\file       htdocs/admin/const.php
 *	\ingroup    setup
 *	\brief      Admin page to define miscellaneous constants
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->load("admin");

if (!$user->admin) {
	accessforbidden();
}

$rowid = GETPOST('rowid', 'int');
$entity = GETPOST('entity', 'int');
$action = GETPOST('action', 'aZ09');
$debug = GETPOST('debug', 'int');
$consts = GETPOST('const', 'array');
$constname = GETPOST('constname', 'alphanohtml');
$constvalue = GETPOST('constvalue', 'restricthtml'); // We should be able to send everything here
$constnote = GETPOST('constnote', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) {
	$page = 0;
}     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (empty($sortfield)) {
	$sortfield = 'entity,name';
}
if (empty($sortorder)) {
	$sortorder = 'ASC';
}


/*
 * Actions
 */

if ($action == 'add' || (GETPOST('add') && $action != 'update')) {
	$error = 0;

	if (empty($constname)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Name")), null, 'errors');
		$error++;
	}
	if ($constvalue == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Value")), null, 'errors');
		$error++;
	}

	if (!$error) {
		if (dolibarr_set_const($db, $constname, $constvalue, 'chaine', 1, $constnote, $entity) >= 0) {
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			$action = "";
			$constname = "";
			$constvalue = "";
			$constnote = "";
		} else {
			dol_print_error($db);
		}
	}
}

// Mass update
if (!empty($consts) && $action == 'update') {
	$nbmodified = 0;
	foreach ($consts as $const) {
		if (!empty($const["check"])) {
			if (dolibarr_set_const($db, $const["name"], $const["value"], $const["type"], 1, $const["note"], $const["entity"]) >= 0) {
				$nbmodified++;
			} else {
				dol_print_error($db);
			}
		}
	}
	if ($nbmodified > 0) {
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
	}
	$action = '';
}

// Mass delete
if (!empty($consts) && $action == 'delete') {
	$nbdeleted = 0;
	foreach ($consts as $const) {
		if (!empty($const["check"])) {	// Is checkbox checked
			if (dolibarr_del_const($db, $const["rowid"], -1) >= 0) {
				$nbdeleted++;
			} else {
				dol_print_error($db);
			}
		}
	}
	if ($nbdeleted > 0) {
		setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
	}
	$action = '';
}

// Delete line from delete picto
if ($action == 'delete') {
	if (dolibarr_del_const($db, $rowid, $entity) >= 0) {
		setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
	} else {
		dol_print_error($db);
	}
}


/*
 * View
 */

$form = new Form($db);

$wikihelp = 'EN:Setup_Other|FR:Paramétrage_Divers|ES:Configuración_Varios';
llxHeader('', $langs->trans("Setup"), $wikihelp);

// Add logic to show/hide buttons
if ($conf->use_javascript_ajax) {
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
		jQuery("#check_" + row_num[1]).prop("checked",true);
	});
});
</script>
	<?php
}

print load_fiche_titre($langs->trans("OtherSetup"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("ConstDesc")."</span><br>\n";
print "<br>\n";

$param = '';

print '<form action="'.$_SERVER["PHP_SELF"].((empty($user->entity) && $debug) ? '?debug=1' : '').'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" id="action" name="action" value="">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print getTitleFieldOfList('Name', 0, $_SERVER['PHP_SELF'], 'name', '', $param, '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList("Value", 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
print getTitleFieldOfList("Comment", 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
print getTitleFieldOfList('DateModificationShort', 0, $_SERVER['PHP_SELF'], 'tms', '', $param, '', $sortfield, $sortorder, 'center ')."\n";
if (!empty($conf->multicompany->enabled) && !$user->entity) {
	print getTitleFieldOfList('Entity', 0, $_SERVER['PHP_SELF'], 'tms', '', $param, '', $sortfield, $sortorder, 'center ')."\n";
}
print getTitleFieldOfList("", 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center ');
print "</tr>\n";


// Line to add new record
print "\n";

print '<tr class="oddeven nohover"><td>';
print '<input type="text" class="flat minwidth300" name="constname" value="'.$constname.'">';
print '</td>'."\n";
print '<td>';
print '<input type="text" class="flat minwidth100" name="constvalue" value="'.$constvalue.'">';
print '</td>';
print '<td>';
print '<input type="text" class="flat minwidth100" name="constnote" value="'.$constnote.'">';
print '</td>';
print '<td>';
print '</td>';
// Limit to superadmin
if (!empty($conf->multicompany->enabled) && !$user->entity) {
	print '<td>';
	print '<input type="text" class="flat" size="1" name="entity" value="'.$conf->entity.'">';
	print '</td>';
	print '<td class="center">';
} else {
	print '<td class="center">';
	print '<input type="hidden" name="entity" value="'.$conf->entity.'">';
}
print '<input type="submit" class="button button-add small" name="add" value="'.$langs->trans("Add").'">';
print "</td>\n";
print '</tr>';


// Show constants
$sql = "SELECT";
$sql .= " rowid";
$sql .= ", ".$db->decrypt('name')." as name";
$sql .= ", ".$db->decrypt('value')." as value";
$sql .= ", type";
$sql .= ", note";
$sql .= ", tms";
$sql .= ", entity";
$sql .= " FROM ".MAIN_DB_PREFIX."const";
$sql .= " WHERE entity IN (".$db->sanitize($user->entity.",".$conf->entity).")";
if ((empty($user->entity) || $user->admin) && $debug) {
} elseif (!GETPOST('visible') || GETPOST('visible') != 'all') {
	// to force for superadmin to debug
	$sql .= " AND visible = 1"; // We must always have this. Otherwise, array is too large and submitting data fails due to apache POST or GET limits
}
if (GETPOST('name')) {
	$sql .= natural_search("name", GETPOST('name'));
}
$sql .= $db->order($sortfield, $sortorder);

dol_syslog("Const::listConstant", LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;

	while ($i < $num) {
		$obj = $db->fetch_object($result);

		print "\n";

		print '<tr class="oddeven" data-checkbox-id="check_'.$i.'"><td>'.$obj->name.'</td>'."\n";

		// Value
		print '<td>';
		print '<input type="hidden" name="const['.$i.'][rowid]" value="'.$obj->rowid.'">';
		print '<input type="hidden" name="const['.$i.'][name]" value="'.$obj->name.'">';
		print '<input type="hidden" name="const['.$i.'][type]" value="'.$obj->type.'">';
		print '<input type="text" id="value_'.$i.'" class="flat inputforupdate minwidth150" name="const['.$i.'][value]" value="'.htmlspecialchars($obj->value).'">';
		print '</td>';

		// Note
		print '<td>';
		print '<input type="text" id="note_'.$i.'" class="flat inputforupdate minwidth200" name="const['.$i.'][note]" value="'.htmlspecialchars($obj->note, 1).'">';
		print '</td>';

		// Date last change
		print '<td class="nowraponall center">';
		print dol_print_date($db->jdate($obj->tms), 'dayhour');
		print '</td>';

		// Entity limit to superadmin
		if (!empty($conf->multicompany->enabled) && !$user->entity) {
			print '<td>';
			print '<input type="text" class="flat" size="1" name="const['.$i.'][entity]" value="'.$obj->entity.'">';
			print '</td>';
			print '<td class="center">';
		} else {
			print '<td class="center">';
			print '<input type="hidden" name="const['.$i.'][entity]" value="'.$obj->entity.'">';
		}

		if ($conf->use_javascript_ajax) {
			print '<input type="checkbox" class="flat checkboxfordelete" id="check_'.$i.'" name="const['.$i.'][check]" value="1">';
		} else {
			print '<a href="'.$_SERVER['PHP_SELF'].'?rowid='.$obj->rowid.'&entity='.$obj->entity.'&action=delete&token='.newToken().((empty($user->entity) && $debug) ? '&debug=1' : '').'">'.img_delete().'</a>';
		}

		print "</td></tr>\n";

		print "\n";
		$i++;
	}
}


print '</table>';
print '</div>';

if ($conf->use_javascript_ajax) {
	print '<br>';
	print '<div id="updateconst" class="right">';
	print '<input type="submit" class="button button-edit marginbottomonly" name="update" value="'.$langs->trans("Modify").'">';
	print '</div>';
	print '<div id="delconst" class="right">';
	print '<input type="submit" class="button button-cancel marginbottomonly" name="delete" value="'.$langs->trans("Delete").'">';
	print '</div>';
}

print "</form>\n";

// End of page
llxFooter();
$db->close();
