<?php
/* Copyright (C) 2019       Open-DSI       <support@open-dsi.fr>
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
 *	\file       htdocs/intracommreport/export.php
 *	\ingroup    Intracomm report
 *	\brief      Page to manage intracomm report export
 */
require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/intracommreport/class/intracommreport.class.php';

$langs->loadLangs(array("intracommreport"));

$action = GETPOST('action');
$exporttype = GETPOST('exporttype'); // DEB ou DES
if (empty($exporttype)) $exporttype = 'deb';

$form = new Form($db);
$formother = new FormOther($db);
$year = GETPOST('year');
$month = GETPOST('month');
$type_declaration = GETPOST('type');

// Mode creation
if ($action == 'create')
{
    $title = $langs->trans("IntracommReportDEBTitle");
    llxHeader("", $title);
    print load_fiche_titre($langs->trans("IntracommReportDEBTitle"));

    print '<form name="charge" method="post" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="export" />';

    dol_fiche_head();

    print '<table class="border" width="100%">';

    // Analysis period
    print '<tr>';
    print '<td class="titlefieldcreate fieldrequired">';
    print $langs->trans("AnalysisPeriod");
    print '</td>';
    print '<td>';
    $TabMonth = array();
    for($i=1;$i<=12;$i++) $TabMonth[$i] = $langs->trans('Month'.str_pad($i, 2, 0, STR_PAD_LEFT));
    //print $ATMform->combo('','month', $TabMonth, empty($month) ? date('m') : $month);
    print $formother->select_month(empty($month) ? date('M') : $month,'month',0, 1);
    print $formother->select_year(empty($year) ? date('Y') : $year,'year',0, 3, 3);
    print '</td>';
    print '</tr>';

    // Type of declaration
    print '<tr>';
    print '<td>';
    print $langs->trans("TypeOfDeclaration");
    print '</td>';
    print '<td>';
    //print $ATMform->combo('','type', array('introduction'=>'Introduction', 'expedition'=>'Expédition'), $type_declaration);
    print $form->selectarray('type', $type, $type_declaration);
    print '</td>';
    print '</tr>';

    print '</table>';

    dol_fiche_end();

    print '<div class="center">';
    print '<input type="submit" class="button" value="'.$langs->trans("ExportXML").'">';
    print '</div>';

    print '</form>';
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

    dol_fiche_head();

	print '<form action="'.$_SERVER['PHP_SELF'].'" name="save" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
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

llxFooter();
