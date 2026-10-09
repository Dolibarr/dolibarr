<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026		MDW						<mdeweerd@users.noreply.github.com>
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

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';

// Load translation files required by the page
$langs->load("admin");

$entity = GETPOSTINT('entity');
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'aZ09');
$toselect = GETPOST('toselect', 'array:int');
$confirm = GETPOST('confirm', 'alpha');

$debug = GETPOSTINT('debug');
$consts = GETPOST('const', 'array');
$constname = GETPOST('constname', 'aZ09');
$constvalue = GETPOST('constvalue', 'restricthtml'); // We should be able to send everything here
$constnote = GETPOST('constnote', 'alphanohtml');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
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

if ($action == 'add' && GETPOST('update')) {	// Click on button update must be used in priority before param $action
	$action = 'update';
}

if (!$user->admin) {
	accessforbidden();
}


/*
 * Actions
 */

// Mass action
if (!GETPOST('confirmmassaction', 'alpha')) {
	$massaction = '';
}

// Add a new record (only when the zone to add a new record was submitted and no mass action is in progress)
if ($action == 'add' && empty($massaction) && GETPOSTISSET('constname')) {
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
		if (!empty($const["rowid"]) && in_array((int) $const["rowid"], $toselect)) {	// Only records checked
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

// Mass delete (massaction = 'delete', or action = 'delete' after confirmation of massaction = 'predelete')
if ($massaction == 'delete' || ($action == 'delete' && $confirm == 'yes')) {
	$nbdeleted = 0;
	foreach ($toselect as $constrowid) {
		if (dolibarr_del_const($db, $constrowid, -1) >= 0) {
			$nbdeleted++;
		} else {
			dol_print_error($db);
		}
	}
	if ($nbdeleted > 1) {
		setEventMessages($langs->trans("RecordsDeleted", $nbdeleted), null, 'mesgs');
	} elseif ($nbdeleted > 0) {
		setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("NoRecordDeleted"), null, 'mesgs');
	}
	$action = '';
	$massaction = '';
}


/*
 * View
 */

$form = new Form($db);

$wikihelp = 'EN:Setup_Other|FR:Paramétrage_Divers|ES:Configuración_Varios';
llxHeader('', $langs->trans("Setup"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-const');

// Add logic to show/hide buttons
if ($conf->use_javascript_ajax) {
	?>
<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery("#updateconst").hide();
	jQuery(".checkforselect").change(function() {
		jQuery("#updateconst").show();
	});
	jQuery(".inputforupdate").keyup(function() {	// keypress does not support back
		jQuery("#updateconst").show();
		jQuery("#action").val('update');			// so default action if we type enter will be update, but correct action is also detected correctly without that when clicking on "Update" button.
		jQuery(this).closest("tr").find(".checkforselect").prop("checked", true);
	});
	jQuery("#add").click(function() {
		jQuery("#action").val('add');		// so the add is forced even if we typed before into a field of a record
	});
});
</script>
	<?php
}

// List of mass actions available
$arrayofmassactions = array(
	'predelete' => img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete"),
);
if (GETPOSTINT('nomassaction') || $massaction == 'predelete') {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

// Button to check all into the action column of title of list
$selectedfields = (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

$arrayofselected = is_array($toselect) ? $toselect : array();

// Button + to show/hide the zone to add a new record
$addformvisible = (($action == 'create' || $action == 'add') && empty($massaction));	// $action == 'add' keeps the zone visible if a field is missing
$urlbuttonplus = $_SERVER['PHP_SELF'].($addformvisible ? '' : '?action=create');
if (empty($user->entity) && $debug) {
	$urlbuttonplus .= $addformvisible ? '?debug=1' : '&debug=1';
}
$newcardbutton = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', $urlbuttonplus, '', ($addformvisible ? 2 : 1));

$param = '';

// Unique form, so the combo of mass actions into the line of title can submit the selection of records
print '<form action="'.$_SERVER["PHP_SELF"].((empty($user->entity) && $debug) ? '?debug=1' : '').'" method="POST" spellcheck="false">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" id="action" name="action" value="add">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

print load_fiche_titre($langs->trans("OtherSetup"), $newcardbutton, 'title_setup', 0, '', '', $massactionbutton);

print '<div class="info">'.$langs->trans("ConstDesc")."</div><br>\n";

// Zone to add a new record (visible only when we click on the button +)
if ($addformvisible) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	// Line of title
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("Name").'</th>';
	print '<th>'.$langs->trans("Value").'</th>';
	print '<th>'.$langs->trans("Comment").'</th>';
	print '<th class="center">'.$langs->trans("DateModificationShort").'</th>';
	if (isModEnabled('multicompany') && !$user->entity) {
		print '<th class="center">'.$langs->trans("Entity").'</th>';
	}
	print '<th class="center"></th>';
	print '</tr>'."\n";

	// Line to add new record
	print "\n";

	print '<tr class="oddeven nohover"><td>';
	print '<input type="text" class="flat minwidth300" name="constname" value="'.dolPrintHTMLForAttribute($constname).'" spellcheck="false">';
	print '</td>'."\n";
	print '<td>';
	print '<input type="text" class="flat minwidth100" name="constvalue" value="'.dolPrintHTMLForAttribute($constvalue, 1).'" spellcheck="false">';
	print '</td>';
	print '<td>';
	print '<input type="text" class="flat minwidth100" name="constnote" value="'.dolPrintHTMLForAttribute($constnote).'">';
	print '</td>';
	print '<td>';
	print '</td>';
	// Limit to superadmin
	if (isModEnabled('multicompany') && !$user->entity) {
		print '<td class="center">';
		print '<input type="text" class="width50" name="entity" value="' . $conf->entity . '">';
		print '</td>';
		print '<td class="center">';
	} else {
		print '<td class="center">';
		print '<input type="hidden" name="entity" value="' . $conf->entity . '">';
	}
	print '<input type="submit" class="button button-add small" id="add" name="add" value="'.$langs->trans("Add").'">';
	print "</td>\n";
	print '</tr>';

	print '</table>';
	print '</div>';
	print '<br>';
}

// Code for pre mass action (confirmation of mass deletion)
if ($massaction == 'predelete') {
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassDeletion"), $langs->trans("ConfirmMassDeletionQuestion", count($toselect)), "delete", null, '', 0, 200, 500, 1);
}

// List of records
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
// Action column
if ($conf->main_checkbox_left_column) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center maxwidthsearch ');
}
print getTitleFieldOfList('Name', 0, $_SERVER['PHP_SELF'], 'name', '', $param, '', $sortfield, $sortorder, '') . "\n";
print getTitleFieldOfList("Value", 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
print getTitleFieldOfList("Comment", 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
print getTitleFieldOfList('DateModificationShort', 0, $_SERVER['PHP_SELF'], 'tms', '', $param, '', $sortfield, $sortorder, 'center ') . "\n";
if (isModEnabled('multicompany') && !$user->entity) {
	print getTitleFieldOfList('Entity', 0, $_SERVER['PHP_SELF'], 'tms', '', $param, '', $sortfield, $sortorder, 'center ') . "\n";
}
// Action column
if (!$conf->main_checkbox_left_column) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center maxwidthsearch ');
}
print "</tr>\n";


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
$sql .= " WHERE entity IN (".$db->sanitize($user->entity.",".((int) $conf->entity)).")";
if ((empty($user->entity)/*  || $user->admin */) && $debug) {
	// empty
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

		$value = dolDecrypt($obj->value);

		// Content of the action column (checkbox to select record for mass actions)
		$selected = 0;
		if (in_array((int) $obj->rowid, $arrayofselected)) {
			$selected = 1;
		}
		$actioncolumncontent = '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';

		print "\n";

		print '<tr class="oddeven">';

		// Action column
		if ($conf->main_checkbox_left_column) {
			print '<td class="center">'.$actioncolumncontent.'</td>';
		}

		print '<td>'.dol_escape_htmltag($obj->name).'</td>'."\n";

		// Value
		print '<td>';
		print '<input type="hidden" name="const['.$i.'][rowid]" value="'.$obj->rowid.'">';
		print '<input type="hidden" name="const['.$i.'][name]" value="'.$obj->name.'">';
		print '<input type="hidden" name="const['.$i.'][type]" value="'.$obj->type.'">';
		if (!isModEnabled('multicompany') || !empty($user->entity)) {	// If there is no input for entity, we save it into a hidden input
			print '<input type="hidden" name="const['.$i.'][entity]" value="'.((int) $obj->entity).'">';
		}
		print '<input type="text" id="value_'.$i.'" class="flat inputforupdate minwidth150" name="const['.$i.'][value]" value="'.(isset($value) ? htmlspecialchars($value) : '').'">';
		print '</td>';

		// Note
		print '<td>';
		print '<input type="text" id="note_'.$i.'" class="flat inputforupdate minwidth200" name="const['.$i.'][note]" value="'.(empty($obj->note) ? '' : htmlspecialchars($obj->note, 1)).'">';
		print '</td>';

		// Date last change
		print '<td class="nowraponall center">';
		print dol_print_date($db->jdate($obj->tms), 'dayhour');
		print '</td>';

		// Entity limit to superadmin
		if (isModEnabled('multicompany') && empty($user->entity)) {
			print '<td class="center">';
			print '<input type="text" class="flat" size="1" name="const['.$i.'][entity]" value="'.((int) $obj->entity).'">';
			print '</td>';
		}

		// Action column
		if (!$conf->main_checkbox_left_column) {
			print '<td class="center">'.$actioncolumncontent.'</td>';
		}

		print "</tr>\n";

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
}

print "</form>\n";

// End of page
llxFooter();
$db->close();
