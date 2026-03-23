<?php
/* Copyright (C) 2004-2023	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2025	Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2024-2025  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
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
 * \file       htdocs/accountancy/admin/categories_list.php
 * \ingroup    setup
 * \brief      Page to administer accountancy groups (Personalized)
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancycategory.class.php';

$langs->loadLangs(array("errors", "admin", "accountancy"));

$action = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view';
$confirm = GETPOST('confirm', 'alpha');
$id = 32;
$rowid = GETPOST('rowid', 'int');
$code = GETPOST('code', 'alpha');

if (!$user->hasRight('accounting', 'chartofaccount')) {
	accessforbidden();
}

$acts = array(0 => "activate", 1 => "disable");
$actl = array(
	0 => img_picto($langs->trans("Disabled"), 'switch_off', 'class="size15x"'),
	1 => img_picto($langs->trans("Activated"), 'switch_on', 'class="size15x"')
);

$listlimit = GETPOSTINT('listlimit') > 0 ? GETPOSTINT('listlimit') : 1000;
$sortfield = GETPOST("sortfield", 'aZ09comma') ? GETPOST("sortfield", 'aZ09comma') : 'position';
$sortorder = GETPOST("sortorder", 'aZ09comma');
$page = GETPOSTINT("page") > 0 ? GETPOSTINT("page") : 0;
$offset = $listlimit * $page;

$search_country_id = GETPOST('search_country_id', 'int');

$tabname = array(32 => MAIN_DB_PREFIX."c_accounting_category");
$tablib = array(32 => "DictionaryAccountancyCategory");
$tabfieldinsert = array(32 => "code,label,range_account,category_type,formula,position,fk_country,entity");
$tabfieldvalue = array(32 => "code,label,range_account,category_type,formula,position,country_id,entity");
$tabrowid = array(32 => "rowid"); // On définit rowid pour le WHERE mais on ne l'insère pas

$accountingcategory = new AccountancyCategory($db);

/*
 * Actions
 */

if (GETPOST('button_removefilter', 'alpha')) {
	$search_country_id = '';
}

if (GETPOST('actionadd', 'alpha') || GETPOST('actionmodify', 'alpha')) {
	$ok = 1;
	if (!GETPOST('code')) { $ok = 0; setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("Code")), null, 'errors'); }
	if (!GETPOST('label')) { $ok = 0; setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("Label")), null, 'errors'); }

	if ($ok && GETPOST('actionadd', 'alpha')) {
		$sql = "INSERT INTO ".$db->sanitize($tabname[$id])." (".$db->sanitize($tabfieldinsert[$id]).", active)";
		$sql .= " VALUES (";
		$sql .= "'".$db->escape(GETPOST('code'))."',";
		$sql .= "'".$db->escape(GETPOST('label'))."',";
		$sql .= "'".$db->escape(GETPOST('range_account'))."',";
		$sql .= (int) GETPOST('category_type').",";
		$sql .= "'".$db->escape(GETPOST('formula'))."',";
		$sql .= (int) GETPOST('position').",";
		$sql .= (GETPOST('country_id') > 0 ? (int) GETPOST('country_id') : "null").",";
		$sql .= (int) $conf->entity.", 1)";

		if ($db->query($sql)) {
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			$_POST = array('id' => $id);
		} else {
			setEventMessages($db->lasterror(), null, 'errors');
		}
	}

	if ($ok && GETPOST('actionmodify', 'alpha')) {
		$sql = "UPDATE ".$db->sanitize($tabname[$id])." SET ";
		$sql .= "code='".$db->escape(GETPOST('code'))."',";
		$sql .= "label='".$db->escape(GETPOST('label'))."',";
		$sql .= "range_account='".$db->escape(GETPOST('range_account'))."',";
		$sql .= "category_type=".(int) GETPOST('category_type').",";
		$sql .= "formula='".$db->escape(GETPOST('formula'))."',";
		$sql .= "position=".(int) GETPOST('position').",";
		$sql .= "fk_country=".(GETPOST('country_id') > 0 ? (int) GETPOST('country_id') : "null");
		$sql .= " WHERE rowid = ".(int) $rowid;

		if ($db->query($sql)) {
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
		} else {
			setEventMessages($db->lasterror(), null, 'errors');
		}
	}
}

if ($action == 'confirm_delete' && $confirm == 'yes') {
	$sql = "DELETE FROM ".$db->sanitize($tabname[$id])." WHERE rowid = ".(int) $rowid;
	if (!$db->query($sql)) setEventMessages($db->lasterror(), null, 'errors');
}

// Activate / Disable
if ($action == 'activate' || $action == 'disable') {
	$newstat = ($action == 'activate' ? 1 : 0);
	$sql = "UPDATE ".$db->sanitize($tabname[$id])." SET active = ".$newstat." WHERE rowid = ".(int) $rowid;
	$db->query($sql);
}

/*
 * View
 */

$form = new Form($db);
llxHeader('', $langs->trans('DictionaryAccountancyCategory'));

$titre = $langs->trans($tablib[$id]);
print load_fiche_titre($titre, '', 'setup');

// --- CORRECTION TICKET 1 : LIBELLE ---
print '<div class="info">'.img_info().' '.$langs->trans("AccountingAccountGroupsDesc").'</div>';
//print '<div class="warning"><span style="color:red; font-weight:bold;">'.img_warning().' Attention :</span> L\'activation de cette configuration va supprimer les groupes et comptes comptables déjà présents. Veuillez faire une sauvegarde de votre base de données.</div><br>';

if ($action == 'delete') {
	print $form->formconfirm($_SERVER["PHP_SELF"].'?rowid='.$rowid.'&id='.$id, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete', '', 0, 1);
}

// Liste
$sql = "SELECT a.rowid, a.code, a.label, a.range_account, a.category_type, a.formula, a.position, a.active, c.code as country_code";
$sql .= " FROM ".$db->sanitize($tabname[$id])." as a";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON a.fk_country = c.rowid";
$sql .= " WHERE a.entity = ".(int) $conf->entity;
if ($search_country_id > 0) {
	$sql .= " AND (a.fk_country = ".(int) $search_country_id." OR a.fk_country IS NULL OR a.fk_country = 0)";
}
$sql .= $db->order($sortfield, $sortorder);

$resql = $db->query($sql);

print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Code").'</td>';
print '<td>'.$langs->trans("Label").'</td>';
print '<td>'.$langs->trans("Comment").'</td>';
print '<td>'.$langs->trans("Calculated").'</td>';
print '<td>'.$langs->trans("Formula").'</td>';
print '<td>'.$langs->trans("Position").'</td>';
print '<td>'.$langs->trans("Country").'</td>';
print '<td></td><td></td></tr>';

// Ligne ajout
print '<tr class="oddeven"><td><input type="text" name="code" class="width75"></td>';
print '<td><input type="text" name="label" class="maxwidth150"></td>';
print '<td><input type="text" name="range_account" class="maxwidth150"></td>';
print '<td>'.$form->selectyesno('category_type', 0, 1).'</td>';
print '<td><input type="text" name="formula" class="width75"></td>';
print '<td><input type="text" name="position" class="width50" value="0"></td>';
print '<td>'.$form->select_country($mysoc->country_id, 'country_id').'</td>';
print '<td colspan="2" class="right"><input type="submit" class="button button-add" name="actionadd" value="'.$langs->trans("Add").'"></td></tr>';

if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		if ($action == 'edit' && $rowid == $obj->rowid) {
			print '<tr class="oddeven">';
			print '<td><input type="text" name="code" value="'.$obj->code.'"></td>';
			print '<td><input type="text" name="label" value="'.$obj->label.'"></td>';
			print '<td><input type="text" name="range_account" value="'.$obj->range_account.'"></td>';
			print '<td>'.$form->selectyesno('category_type', $obj->category_type, 1).'</td>';
			print '<td><input type="text" name="formula" value="'.$obj->formula.'"></td>';
			print '<td><input type="text" name="position" value="'.$obj->position.'"></td>';
			print '<td>'.$form->select_country($obj->fk_country, 'country_id').'</td>';
			print '<td colspan="2" class="center">';
			print '<input type="hidden" name="rowid" value="'.$obj->rowid.'">';
			print '<input type="submit" class="button button-save" name="actionmodify" value="'.$langs->trans("Save").'">';
			print '</td></tr>';
		} else {
			print '<tr class="oddeven">';
			print '<td>'.$obj->code.'</td>';
			print '<td>'.$obj->label.'</td>';
			print '<td>'.$obj->range_account.'</td>';
			print '<td>'.yn($obj->category_type).'</td>';
			print '<td>'.$obj->formula.'</td>';
			print '<td>'.$obj->position.'</td>';
			print '<td>'.($obj->country_code ? $obj->country_code : '').'</td>';
			print '<td class="center"><a href="'.$_SERVER["PHP_SELF"].'?action=edit&rowid='.$obj->rowid.'">'.img_edit().'</a></td>';
			print '<td class="center"><a href="'.$_SERVER["PHP_SELF"].'?action=delete&rowid='.$obj->rowid.'">'.img_delete().'</a></td></tr>';
		}
	}
}
print '</table></div></form>';

llxFooter();
$db->close();
