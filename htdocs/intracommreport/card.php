<?php
/* Copyright (C) 2015       ATM Consulting          <support@atm-consulting.fr>
 * Copyright (C) 2019-2020  Open-DSI                <support@open-dsi.fr>
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
 *	\file       htdocs/intracommreport/card.php
 *	\ingroup    Intracomm report
 *	\brief      Page to manage intracomm report export
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/intracommreport/class/intracommreport.class.php';

$langs->loadLangs(array("intracommreport"));

$action = GETPOST('action');
$exporttype = GETPOSTISSET('exporttype') ? GETPOST('exporttype', 'alphanohtml') : 'deb'; // DEB ou DES
$year = GETPOSTINT('year');
$month = GETPOSTINT('month');
$label = (string) GETPOST('label', 'alphanohtml');
$type_declaration = (string) GETPOST('type_declaration', 'alphanohtml');
$backtopage = GETPOST('backtopage', 'alpha');
$declaration = array(
	"deb" => $langs->trans("DEB"),
	"des" => $langs->trans("DES"),
);
$typeOfDeclaration = array(
	"introduction" => $langs->trans("Introduction"),
	"expedition" => $langs->trans("Expedition"),
);
$object = new IntracommReport($db);
if ($id > 0) {
	$object->fetch($id);
}
$form = new Form($db);
$formother = new FormOther($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('intracommcard', 'globalcard'));

/*
 * 	Actions
 */
$parameters = array('id' => $id);
// Note that $action and $object may have been modified by some hooks
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if ($user->rights->intracommreport->delete && $action == 'confirm_delete' && $confirm == 'yes') {
	$result = $object->delete($id, $user);
	if ($result > 0) {
		if (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		} else {
			header("Location: list.php");
			exit;
		}
	} else {
		$errmesg = $object->error;
	}
}

if ($action == 'add' && $user->rights->intracommreport->write) {
	$object->label = trim($label);
	$object->type = trim($exporttype);
	$object->type_declaration =  $type_declaration;
	$object->subscription = (int) $subscription;

	// Fill array 'array_options' with data from add form
	// $ret = $extrafields->setOptionalsFromPost($extralabels, $object);
	// if ($ret < 0) {
	// 	$error++;
	// }

	if (empty($object->label)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
	} else {
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."intracommreport WHERE ref='".$db->escape($object->label)."'";
		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
		}
		if ($num) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorLabelAlreadyExists", $login), null, 'errors');
		}
	}

	if (!$error) {
		$id = $object->create($user);
		if ($id > 0) {
			header("Location: ".$_SERVER["PHP_SELF"].'?id='.$id);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'create';
		}
	} else {
		$action = 'create';
	}
}

/*
 * View
 */

// Creation mode
if ($action == 'create') {
	$title = $langs->trans("IntracommReportTitle");
	llxHeader("", $title);
	print load_fiche_titre($langs->trans("IntracommReportTitle"));

	print '<form name="charge" method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add" />';

	print dol_get_fiche_head();

	print '<table class="border" width="100%">';

	// Label
	print '<tr><td class="titlefieldcreate">'.$langs->trans("Label").'</td><td><input type="text" class="minwidth200" name="label" autofocus="autofocus"></td></tr>';

	// Declaration
	print '<tr><td class="fieldrequired">'.$langs->trans("Declaration")."</td><td>\n";
	print $form->selectarray("declaration", $declaration, GETPOST('declaration', 'alpha') ? GETPOST('declaration', 'alpha') : $object->declaration, 0);
	print "</td>\n";

	// Analysis period
	print '<tr>';
	print '<td class="titlefieldcreate fieldrequired">';
	print $langs->trans("AnalysisPeriod");
	print '</td>';
	print '<td>';
	print $formother->select_month($month ? date('M') : $month, 'month', 0, 1, 'widthauto valignmiddle ');
	print $formother->select_year($year ? date('Y') : $year, 'year', 0, 3, 3);
	print '</td>';
	print '</tr>';

	// Type of declaration
	print '<tr><td class="fieldrequired">'.$langs->trans("TypeOfDeclaration")."</td><td>\n";
	print $form->selectarray("type_declaration", $typeOfDeclaration, GETPOST('type_declaration', 'alpha') ? GETPOST('type_declaration', 'alpha') : $object->type_declaration, 0);
	print "</td>\n";

	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center"><input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" class="button button-cancel" value="'.$langs->trans("Cancel").'" onClick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';
}

if ($id > 0 && $action != 'edit') {
	/* ************************************************************************** */
	/*                                                                            */
	/* View mode                                                                  */
	/*                                                                            */
	/* ************************************************************************** */
	$res = $object->fetch($id);
	if ($res < 0) {
		dol_print_error($db, $object->error);
		exit;
	}

	/*
	 * Show tabs
	 */
	//$head = intracommreport_prepare_head($object);

	print dol_get_fiche_head("", 'general', $langs->trans("IntracommReport"), -1, 'user');

	// Confirm remove report
	if ($action == 'delete') {
		$formquestion = array();
		if ($backtopage) {
			$formquestion[] = array(
				'type' => 'hidden',
				'name' => 'backtopage',
				'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"])
			);
		}
		print $form->formconfirm(
			"card.php?rowid=".$id,
			$langs->trans("DeleteReport"),
			$langs->trans("ConfirmDeleteReport"),
			"confirm_delete",
			$formquestion,
			'no',
			1
		);
	}

	$linkback = '<a href="'.DOL_URL_ROOT.'/intracommreport/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'rowid', $linkback);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield centpercent">';

	// Type
	print '<tr><td class="titlefield">'.$langs->trans("Type").'</td><td class="valeur">'.$object->declaration."</td></tr>\n";

	// Analysis Period
	print '<tr><td>'.$langs->trans("AnalysisPeriod").'</td><td class="valeur">'.$object->period.'</td>';
	print '</tr>';

	// Type of Declaration
	print '<tr><td>'.$langs->trans("TypeOfDeclaration").'</td><td class="valeur">'.$object->type_declaration.'</td>';
	print '</tr>';

	print "</table>\n";

	print "</div></div></div>\n";
	print '<div style="clear:both"></div>';

	print dol_get_fiche_end();
}

	/*
	switch($action) {
		case 'generateXML':
			$obj = new TDebProdouane($PDOdb);
			$obj->load($PDOdb, GETPOST('id_declaration'));
			$obj->generateXMLFile();
			break;
		case 'list':
			_liste($exporttype);
			break;
		case 'export':
			if ($exporttype == 'deb') _export_xml_deb($type_declaration, $year, str_pad($month, 2, 0, STR_PAD_LEFT));
			else _export_xml_des($type_declaration, $year, str_pad($month, 2, 0, STR_PAD_LEFT));
		default:
			if ($exporttype == 'deb') _print_form_deb();
			else _print_form_des();
			break;
	}

	function _print_form_des()
	{
		global $langs, $formother, $year, $month, $type_declaration;

		$title = $langs->trans("IntracommReportDESTitle");
		llxHeader("", $title);
		print load_fiche_titre($langs->trans("IntracommReportDESTitle"));

		print dol_get_fiche_head();

		print '<form action="'.$_SERVER['PHP_SELF'].'" name="save" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="export" />';
		print '<input type="hidden" name="exporttype" value="des" />';
		print '<input type="hidden" name="type" value="expedition" />'; // Permet d'utiliser le bon select de la requête sql

		print '<table width="100%" class="noborder">';

		print '<tr class="liste_titre"><td colspan="2">';
		print 'Paramètres de l\'export';
		print '</td></tr>';

		print '<tr>';
		print '<td>Période d\'analyse</td>';
		print '<td>';
		$TabMonth = array();
		for($i=1;$i<=12;$i++) $TabMonth[$i] = $langs->trans('Month'.str_pad($i, 2, 0, STR_PAD_LEFT));
		//print $ATMform->combo('','month', $TabMonth, empty($month) ? date('m') : $month);
		print $formother->selectyear(empty($year) ? date('Y') : $year,'year',0, 20, 5);
		print '</td>';
		print '</tr>';

		print '</table>';

		print '<div class="tabsAction">';
		print '<input class="butAction" type="submit" value="Exporter XML" />';
		print '</div>';

		print '</form>';
	}

	function _export_xml_deb($type_declaration, $period_year, $period_month) {

		global $db, $conf;

		$obj = new TDebProdouane($db);
		$obj->entity = $conf->entity;
		$obj->mode = 'O';
		$obj->periode = $period_year.'-'.$period_month;
		$obj->type_declaration = $type_declaration;
		$obj->numero_declaration = $obj->getNextNumeroDeclaration();
		$obj->content_xml = $obj->getXML('O', $type_declaration, $period_year.'-'.$period_month);
		if(empty($obj->errors)) {
			$obj->save($PDOdb);
			$obj->generateXMLFile();
		}
		else setEventMessage($obj->errors, 'warnings');
	}

	function _export_xml_des($type_declaration, $period_year, $period_month) {

		global $PDOdb, $conf;

		$obj = new TDebProdouane($PDOdb);
		$obj->entity = $conf->entity;
		$obj->periode = $period_year.'-'.$period_month;
		$obj->type_declaration = $type_declaration;
		$obj->exporttype = 'des';
		$obj->numero_declaration = $obj->getNextNumeroDeclaration();
		$obj->content_xml = $obj->getXMLDes($period_year, $period_month, $type_declaration);
		if(empty($obj->errors)) {
			$obj->save($PDOdb);
			$obj->generateXMLFile();
		}
		else setEventMessage($obj->errors, 'warnings');
	}
	*/

// End of page
llxFooter();
$db->close();
