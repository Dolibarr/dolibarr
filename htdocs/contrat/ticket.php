<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2023		Charlene BENKE		<charlene@patas-monkey.com>
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
 *	  	\file	   	htdocs/contrat/ticket.php
 *	  	\ingroup	contrat
 *		\brief	 	Page of associated ticket
 */


require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT."/ticket/class/ticket.class.php";

$langs->loadLangs(array('companies', 'contracts', 'tickets'));

$socid=GETPOST('socid', 'int');
$id=GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$action=GETPOST('action', 'alpha');

if ($id == '' && $ref == '') {
	dol_print_error('', 'Bad parameter');
	exit;
}

// Security check
$socid=0;
if ($user->socid > 0)
	$socid=$user->socid;

$result=restrictedArea($user, 'contrat', $id);


/*
 *	View
 */

llxHeader("", $langs->trans("Tickets"), "Contrat");

$form = new Form($db);
$userstatic=new User($db);

$object= new Contrat($db);
$result=$object->fetch($id, $ref);
$ret=$object->fetch_thirdparty();
$head = contract_prepare_head($object);


dol_fiche_head($head, 'ticket', $langs->trans("Contract"), -1, 'contract');

$linkback = '<a href="'.DOL_URL_ROOT.'/contrat/list.php'.(! empty($socid)?'?socid='.$socid:'').'">';
$linkback.= $langs->trans("BackToList").'</a>';

$morehtmlref='';
$morehtmlref.=$object->ref;

$morehtmlref.='<div class="refidno">';
// Ref customer
$morehtmlref.=$form->editfieldkey(
				"RefCustomer", 'ref_customer', $object->ref_customer,
				$object, 0, 'string', '', 0, 1
);
$morehtmlref.=$form->editfieldval(
				"RefCustomer", 'ref_customer', $object->ref_customer,
				$object, 0, 'string', '', null, null, '', 1
);
// Ref supplier
$morehtmlref.='<br>';
$morehtmlref.=$form->editfieldkey(
				"RefSupplier", 'ref_supplier', $object->ref_supplier,
				$object, 0, 'string', '', 0, 1
);
$morehtmlref.=$form->editfieldval(
				"RefSupplier", 'ref_supplier', $object->ref_supplier,
				$object, 0, 'string', '', null, null, '', 1
);
// Thirdparty
$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
// Project
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

	$langs->load("projects");
	$morehtmlref.='<br>'.$langs->trans('Project') . ' : ';
	if (! empty($object->fk_project)) {
		$proj = new Project($db);
		$proj->fetch($object->fk_project);
		$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=';
		$morehtmlref.=$object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
		$morehtmlref.=$proj->ref;
		$morehtmlref.='</a>';
	} else {
		$morehtmlref.='';
	}
}
$morehtmlref.='</div>';

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'none', $morehtmlref);

print '<div class="underbanner clearboth"></div>';


/*
 * Referers types
 */

$title=$langs->trans("ListTicketsLinkToContract");

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td >'.$langs->trans("Ref").'</td>';
print '<td width="300">'.$langs->trans("Subject").'</td>';
print '<td align="left">'.$langs->trans("Type").'</td>';
print '<td align="left" nowrap >'.$langs->trans("TicketCategory").'</td>';
print '<td align="left">'.$langs->trans("Severity").'</td>';
print '<td  align="center">'.$langs->trans("Date").'</td>';
print '<td  align="center" nowrap >'.$langs->trans("DateEnd").'</td>';
print '<td  align="right">'.$langs->trans("Progress").'</td>';
print '<td align="right" width="100">'.$langs->trans("Status").'</td>';
print '</tr>';
// on récupère la totalité des tickets liés au contrat
$allticketarray = $object->getTicketsArray();
if (is_array($allticketarray) && count($allticketarray) > 0) {
	foreach ($allticketarray as $key => $value) {
		$total_ht = 0;
		$total_ttc = 0;

		$element = $value;

		print "<tr>";

		// Ref
		print '<td align="left">';
		print $element->getNomUrl(1);
		print "</td>\n";

		// Information
		print '<td align="left">'.$value->subject.'</td>';
		print '<td align="left">'.$value->type_label.'</td>';
		print '<td align="left">'.$value->category_label.'</td>';
		print '<td align="left">'.$value->severity_label.'</td>';

		// Date
		print '<td align="center">'.dol_print_date($element->datec, 'day').'</td>';
		print '<td align="center">'.dol_print_date($element->date_close, 'day').'</td>';

		// Duration
		print '<td align="right">';
		print (isset($element->progress) ? $element->progress.'%' : '');
		print '</td>';

		// Status
		print '<td align="right">'.$element->getLibStatut(5).'</td>';
		print '</tr>';
	}
}
print "</table>";


llxFooter();
$db->close();
