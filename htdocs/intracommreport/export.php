<?php

require './config.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
dol_include_once('/intracommreport/class/deb_prodouane.class.php');

$action = GETPOST('action');
$exporttype = GETPOST('exporttype'); // DEB ou DES
if (empty($exporttype)) $exporttype = 'deb';

$PDOdb = new TPDOdb;
$ATMform = new TFormCore;
$formother = new FormOther($db);
$year = GETPOST('year');
$month = GETPOST('month');
$type_declaration = GETPOST('type');

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



function _print_form_deb() {
	
	global $langs, $ATMform, $formother, $year, $month, $type_declaration;
	
	$langs->load('intracommreport@intracommreport');
	$langs->load('main');
	
	llxHeader();
	print_fiche_titre($langs->trans('intracommreportTitle'));
	dol_fiche_head();

	print '<form action="'.$_SERVER['PHP_SELF'].'" name="save" method="POST">';
	print '<input type="hidden" name="action" value="export" />';
	
	print '<table width="100%" class="noborder" style="background-color: #fff;">';
	print '<tr class="liste_titre">';
	print '<td colspan="2">';
	print 'Paramètres de l\'export';
	print '</td>';
	print '</tr>';
	print '<tr>';
	print '<td>';
	print 'Période d\'analyse';
	print '</td>';
	print '<td>';
	$TabMonth = array();
	for($i=1;$i<=12;$i++) $TabMonth[$i] = $langs->trans('Month'.str_pad($i, 2, 0, STR_PAD_LEFT));
	print $ATMform->combo('','month', $TabMonth, empty($month) ? date('m') : $month);
	print $formother->selectyear(empty($year) ? date('Y') : $year,'year',0, 20, 5);
	print '</td>';
	print '</tr>';
	print '<tr>';
	print '<td>';
	print 'Type de déclaration';
	print '</td>';
	print '<td>';
	print $ATMform->combo('','type', array('introduction'=>'Introduction', 'expedition'=>'Expédition'), $type_declaration);
	print '</td>';
	print '</tr>';
	
	print '</table>';
	
	print '<div class="tabsAction">';
	print '<input class="butAction" type="SUBMIT" name="subFormExport" value="Exporter XML" />';
	print '</div>';
	
	print '</form>';
	
}

function _print_form_des()
{
	global $langs, $ATMform, $formother, $year, $month, $type_declaration;
	
	$langs->load('intracommreport@intracommreport');
	$langs->load('main');
	
	llxHeader();
	print_fiche_titre($langs->trans('exportprodesTitle'));
	dol_fiche_head();

	print '<form action="'.$_SERVER['PHP_SELF'].'" name="save" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="export" />';
	print '<input type="hidden" name="exporttype" value="des" />';
	print '<input type="hidden" name="type" value="expedition" />'; // Permet d'utiliser le bon select de la requête sql
	
	print '<table width="100%" class="noborder" style="background-color: #fff;">';
	
	print '<tr class="liste_titre"><td colspan="2">';
	print 'Paramètres de l\'export';
	print '</td></tr>';
	
	print '<tr>';
	print '<td>Période d\'analyse</td>';
	print '<td>';
	$TabMonth = array();
	for($i=1;$i<=12;$i++) $TabMonth[$i] = $langs->trans('Month'.str_pad($i, 2, 0, STR_PAD_LEFT));
	print $ATMform->combo('','month', $TabMonth, empty($month) ? date('m') : $month);
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
	
	global $PDOdb, $conf;
	
	$obj = new TDebProdouane($PDOdb);
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

function _liste($exporttype='deb') {
	
	global $db, $conf, $PDOdb, $langs;
	
	$langs->load('intracommreport@intracommreport');
	
	llxHeader();
	$l = new TListviewTBS('intracommreport');
	
	$sql = 'SELECT numero_declaration, type_declaration, periode, rowid as dl
			FROM '.MAIN_DB_PREFIX.'deb_prodouane
			WHERE entity = '.$conf->entity.' AND exporttype = '.$PDOdb->quote($exporttype);
	
	print $l->render($PDOdb, $sql, array(
		'type'=>array(
			//'date_cre'=>'date'
		)
		,'link'=>array(
			'dl'=>'<a href="'.dol_buildpath('/intracommreport/export.php', 1).'?action=generateXML&id_declaration=@dl@">'.img_picto('', 'file.png').'</a>'
		)
		,'eval'=>array(
			'numero_declaration'=>'TDebProdouane::getNumeroDeclaration("@val@")'
			,'type_declaration'=>'TDebProdouane::$TType["@val@"]'
		)
		,'liste'=>array(
			'titre'=>$langs->trans('intracommreportList'.$exporttype)
			,'image'=>img_picto('','title.png', '', 0)
			,'picto_precedent'=>img_picto('','back.png', '', 0)
			,'picto_suivant'=>img_picto('','next.png', '', 0)
			,'messageNothing'=>"Il n'y a aucune déclaration à afficher"
			,'picto_search'=>img_picto('','search.png', '', 0)
		)
		,'title'=>array(
			'numero_declaration'=>$langs->trans('intracommreportNumber')
			,'type_declaration'=>$langs->trans('intracommreportTypeDeclaration')
			,'periode'=>$langs->trans('intracommreportPeriod')
			,'dl'=>$langs->trans('intracommreportDownload')
		)
	));
	
}

llxFooter();
