<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2021	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
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
 *	\file		htdocs/admin/workflow.php
 *	\ingroup	company
 *	\brief		Workflows setup page
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// security check
if (!$user->admin) {
	accessforbidden();
}

// Load translation files required by the page
$langs->loadLangs(array("admin", "workflow", "propal", "workflow", "orders", "supplier_proposal", "receptions", "errors", 'sendings'));

$action = GETPOST('action', 'aZ09');


/*
 * Actions
 */

if (preg_match('/set(.*)/', $action, $reg)) {
	if (!dolibarr_set_const($db, $reg[1], '1', 'chaine', 0, '', $conf->entity) > 0) {
		dol_print_error($db);
	}
}

if (preg_match('/del(.*)/', $action, $reg)) {
	if (!dolibarr_set_const($db, $reg[1], '0', 'chaine', 0, '', $conf->entity) > 0) {
		dol_print_error($db);
	}
}

// List of workflow we can enable
clearstatcache();

$workflowcodes = array(
	// Automatic creation
	'WORKFLOW_PROPAL_AUTOCREATE_ORDER'=>array(
		'family'=>'create',
		'position'=>10,
		'enabled'=>(isModEnabled("propal") && isModEnabled('commande')),
		'picto'=>'order'
	),
	'WORKFLOW_ORDER_AUTOCREATE_INVOICE'=>array(
		'family'=>'create',
		'position'=>20,
		'enabled'=>(isModEnabled('commande') && isModEnabled('facture')),
		'picto'=>'bill'
	),
	'WORKFLOW_TICKET_CREATE_INTERVENTION' => array(
		'family'=>'create',
		'position'=>25,
		'enabled'=>(isModEnabled('ticket') && isModEnabled('ficheinter')),
		'picto'=>'ticket'
	),

	'separator1'=>array('family'=>'separator', 'position'=>25, 'title'=>'', 'enabled'=>((isModEnabled("propal") && isModEnabled('commande')) || (isModEnabled('commande') && isModEnabled('facture')) || (isModEnabled('ticket') && isModEnabled('ficheinter')))),

	// Automatic classification of proposal
	'WORKFLOW_ORDER_CLASSIFY_BILLED_PROPAL'=>array(
		'family'=>'classify_proposal',
		'position'=>30,
		'enabled'=>(isModEnabled("propal") && isModEnabled('commande')),
		'picto'=>'propal',
		'warning'=>''
	),
	'WORKFLOW_INVOICE_CLASSIFY_BILLED_PROPAL'=>array(
		'family'=>'classify_proposal',
		'position'=>31,
		'enabled'=>(isModEnabled("propal") && isModEnabled('facture')),
		'picto'=>'propal',
		'warning'=>''
	),

	// Automatic classification of order
	'WORKFLOW_ORDER_CLASSIFY_SHIPPED_SHIPPING'=>array(  // when shipping validated
		'family'=>'classify_order',
		'position'=>40,
		'enabled'=>(isModEnabled("expedition") && isModEnabled('commande')),
		'picto'=>'order'
	),
	'WORKFLOW_ORDER_CLASSIFY_SHIPPED_SHIPPING_CLOSED'=>array( // when shipping closed
		'family'=>'classify_order',
		'position'=>41,
		'enabled'=>(isModEnabled("expedition") && isModEnabled('commande')),
		'picto'=>'order'
	),
	'WORKFLOW_INVOICE_AMOUNT_CLASSIFY_BILLED_ORDER'=>array(
		'family'=>'classify_order',
		'position'=>42,
		'enabled'=>(isModEnabled('facture') && isModEnabled('commande')),
		'picto'=>'order',
		'warning'=>''
	), // For this option, if module invoice is disabled, it does not exists, so "Classify billed" for order must be done manually from order card.

	// Automatic classification supplier proposal
	'WORKFLOW_ORDER_CLASSIFY_BILLED_SUPPLIER_PROPOSAL'=>array(
		'family'=>'classify_supplier_proposal',
		'position'=>60,
		'enabled'=>(isModEnabled('supplier_proposal') && (isModEnabled("supplier_order") || isModEnabled("supplier_invoice"))),
		'picto'=>'supplier_proposal',
		'warning'=>''
	),

	// Automatic classification supplier order
	'WORKFLOW_ORDER_CLASSIFY_RECEIVED_RECEPTION'=>array(
		'family'=>'classify_supplier_order',
		'position'=>63,
		'enabled'=>(getDolGlobalString('MAIN_FEATURES_LEVEL') && isModEnabled("reception") && isModEnabled('supplier_order')),
		'picto'=>'supplier_order',
		'warning'=>''
	),

	'WORKFLOW_ORDER_CLASSIFY_RECEIVED_RECEPTION_CLOSED'=>array(
		'family'=>'classify_supplier_order',
		'position'=>64,
		'enabled'=>(getDolGlobalString('MAIN_FEATURES_LEVEL') && isModEnabled("reception") && isModEnabled('supplier_order')),
		'picto'=>'supplier_order',
		'warning'=>''
	),

	'WORKFLOW_INVOICE_AMOUNT_CLASSIFY_BILLED_SUPPLIER_ORDER'=>array(
		'family'=>'classify_supplier_order',
		'position'=>65,
		'enabled'=>(isModEnabled("supplier_order") || isModEnabled("supplier_invoice")),
		'picto'=>'supplier_order',
		'warning'=>''
	),

	// Automatic classification shipping
	/* Replaced by next option
	'WORKFLOW_SHIPPING_CLASSIFY_CLOSED_INVOICE' => array(
		'family' => 'classify_shipping',
		'position' => 90,
		'enabled' => isModEnabled("expedition") && isModEnabled("facture"),
		'picto' => 'shipment',
		'deprecated' => 1
	),
	*/

	'WORKFLOW_SHIPPING_CLASSIFY_BILLED_INVOICE' => array(
		'family' => 'classify_shipping',
		'position' => 91,
		'enabled' => isModEnabled("expedition") && isModEnabled("facture") && getDolGlobalString('WORKFLOW_BILL_ON_SHIPMENT') !== '0',
		'picto' => 'shipment'
	),

	// Automatic classification reception
	/*
	'WORKFLOW_RECEPTION_CLASSIFY_CLOSED_INVOICE'=>array(
		'family'=>'classify_reception',
		'position'=>95,
		'enabled'=>(isModEnabled("reception") && (isModEnabled("supplier_order") || isModEnabled("supplier_invoice"))),
		'picto'=>'reception'
	),
	*/

	'WORKFLOW_RECEPTION_CLASSIFY_BILLED_INVOICE' => array(
		'family' => 'classify_reception',
		'position' => 91,
		'enabled' => isModEnabled("reception") && isModEnabled("supplier_invoice") && getDolGlobalString('WORKFLOW_BILL_ON_RECEPTION') !== '0',
		'picto' => 'shipment'
	),


	'separator2'=>array('family'=>'separator', 'position'=>400, 'enabled' => (isModEnabled('ticket') && isModEnabled('contract'))),

	// Automatic link ticket -> contract
	'WORKFLOW_TICKET_LINK_CONTRACT' => array(
		'family' => 'link_ticket',
		'position' => 500,
		'enabled' => (isModEnabled('ticket') && isModEnabled('contract')),
		'picto' => 'ticket'
	),
	'WORKFLOW_TICKET_USE_PARENT_COMPANY_CONTRACTS' => array(
		'family' => 'link_ticket',
		'position' => 501,
		'enabled' => (isModEnabled('ticket') && isModEnabled('contract')),
		'picto' => 'ticket'
	),
);

if (!empty($conf->modules_parts['workflow']) && is_array($conf->modules_parts['workflow'])) {
	foreach ($conf->modules_parts['workflow'] as $workflow) {
		$workflowcodes = array_merge($workflowcodes, $workflow);
	}
}

// remove not available workflows (based on activated modules and global defined keys)
$workflowcodes = array_filter($workflowcodes, function ($var) {
	return $var['enabled'];
});

/*
 * View
 */

llxHeader('', $langs->trans("WorkflowSetup"), "EN:Module_Workflow_En|FR:Module_Workflow|ES:Módulo_Workflow");

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("WorkflowSetup"), $linkback, 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("WorkflowDesc").'</span>';
print '<br>';
print '<br>';

// current module setup don't support any automatic workflow of this module
if (count($workflowcodes) < 1) {
	print $langs->trans("ThereIsNoWorkflowToModify");

	llxFooter();
	$db->close();
	return;
}

// Sort on position
$workflowcodes = dol_sort_array($workflowcodes, 'position');

print '<table class="noborder centpercent">';

$oldfamily = '';

foreach ($workflowcodes as $key => $params) {
	if ($params['family'] == 'separator') {
		print '</table>';
		print '<br>';

		print '<table class="noborder centpercent">';

		continue;
	}

	$reg = array();
	if ($oldfamily != $params['family']) {
		if ($params['family'] == 'create') {
			$header = $langs->trans("AutomaticCreation");
		} elseif (preg_match('/classify_(.*)/', $params['family'], $reg)) {
			$header = $langs->trans("AutomaticClassification");
			if ($reg[1] == 'proposal') {
				$header .= ' - '.$langs->trans('Proposal');
			}
			if ($reg[1] == 'order') {
				$header .= ' - '.$langs->trans('Order');
			}
			if ($reg[1] == 'supplier_proposal') {
				$header .= ' - '.$langs->trans('SupplierProposal');
			}
			if ($reg[1] == 'supplier_order') {
				$header .= ' - '.$langs->trans('SupplierOrder');
			}
			if ($reg[1] == 'reception') {
				$header .= ' - '.$langs->trans('Reception');
			}
			if ($reg[1] == 'shipping') {
				$header .= ' - '.$langs->trans('Shipment');
			}
		} elseif (preg_match('/link_(.*)/', $params['family'], $reg)) {
			$header = $langs->trans("AutomaticLinking");
			if ($reg[1] == 'ticket') {
				$header .= ' - '.$langs->trans('Ticket');
			}
		} else {
			$header = $langs->trans("Description");
		}

		print '<tr class="liste_titre">';
		print '<th>'.$header.'</th>';
		print '<th class="right">'.$langs->trans("Status").'</th>';
		print '</tr>';

		$oldfamily = $params['family'];
	}

	print '<tr class="oddeven">';
	print '<td>';
	print img_object('', $params['picto'], 'class="pictofixedwidth"');
	print ' '.$langs->trans('desc'.$key);

	if (!empty($params['warning'])) {
		print ' '.img_warning($langs->transnoentitiesnoconv($params['warning']));
	}
	if (!empty($params['deprecated'])) {
		print ' '.img_warning($langs->transnoentitiesnoconv("Deprecated"));
	}

	print '</td>';

	print '<td class="right">';

	if (!empty($conf->use_javascript_ajax)) {
		print ajax_constantonoff($key);
	} else {
		if (getDolGlobalString($key)) {
			print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=del'.$key.'&token='.newToken().'">';
			print img_picto($langs->trans("Activated"), 'switch_on');
			print '</a>';
		} else {
			print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=set'.$key.'&token='.newToken().'">';
			print img_picto($langs->trans("Disabled"), 'switch_off');
			print '</a>';
		}
	}

	print '</td>';
	print '</tr>';
}

print '</table>';

// End of page
llxFooter();
$db->close();
