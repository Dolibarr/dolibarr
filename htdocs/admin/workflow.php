<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
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
 *	\file       htdocs/admin/workflow.php
 *	\ingroup    company
 *	\brief      Workflows setup page
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin","workflow","propal","workflow","orders","supplier_proposals"));

if (! $user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');

/*
 * Actions
 */
if (preg_match('/set(.*)/',$action,$reg))
{
    if (! dolibarr_set_const($db, $reg[1], '1', 'chaine', 0, '', $conf->entity) > 0)
    {
        dol_print_error($db);
    }
}

if (preg_match('/del(.*)/',$action,$reg))
{
    if (! dolibarr_del_const($db, $reg[1], $conf->entity) > 0)
    {
        dol_print_error($db);
    }
}


/*
 * 	View
 */

llxHeader('',$langs->trans("WorkflowSetup"),'');

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("WorkflowSetup"),$linkback,'title_setup');

print $langs->trans("WorkflowDesc").'<br>';
print "<br>";

// List of workflow we can enable

clearstatcache();

$workflowcodes=array(
    // Automatic creation
    'WORKFLOW_PROPAL_AUTOCREATE_ORDER'=>array('family'=>'create', 'position'=>10, 'enabled'=>'! empty($conf->propal->enabled) && ! empty($conf->commande->enabled)', 'picto'=>'order'),
	'WORKFLOW_ORDER_AUTOCREATE_INVOICE'=>array('family'=>'create', 'position'=>20, 'enabled'=>'! empty($conf->commande->enabled) && ! empty($conf->facture->enabled)', 'picto'=>'bill'),
    'separator1'=>array('family'=>'separator', 'position'=>25),
	// Automatic classification of proposal
	'WORKFLOW_ORDER_CLASSIFY_BILLED_PROPAL'=>array('family'=>'classify_proposal', 'position'=>30, 'enabled'=>'! empty($conf->propal->enabled) && ! empty($conf->commande->enabled)', 'picto'=>'propal','warning'=>''),
	'WORKFLOW_INVOICE_CLASSIFY_BILLED_PROPAL'=>array('family'=>'classify_proposal', 'position'=>31, 'enabled'=>'! empty($conf->propal->enabled) && ! empty($conf->facture->enabled)', 'picto'=>'propal','warning'=>''),
	// Automatic classification of order
	'WORKFLOW_ORDER_CLASSIFY_SHIPPED_SHIPPING'=>array('family'=>'classify_order', 'position'=>40, 'enabled'=>'! empty($conf->expedition->enabled) && ! empty($conf->commande->enabled)', 'picto'=>'order'),
	'WORKFLOW_INVOICE_AMOUNT_CLASSIFY_BILLED_ORDER'=>array('family'=>'classify_order', 'position'=>41, 'enabled'=>'! empty($conf->facture->enabled) && ! empty($conf->commande->enabled)', 'picto'=>'order','warning'=>''),	// For this option, if module invoice is disabled, it does not exists, so "Classify billed" for order must be done manually from order card.
    'separator2'=>array('family'=>'separator', 'position'=>50),
	// Automatic classification supplier proposal
	'WORKFLOW_ORDER_CLASSIFY_BILLED_SUPPLIER_PROPOSAL'=>array('family'=>'classify_supplier_proposal', 'position'=>60, 'enabled'=>'! empty($conf->supplier_proposal->enabled) && ! empty($conf->fournisseur->enabled)', 'picto'=>'propal','warning'=>''),
	// Automatic classification supplier order
	'WORKFLOW_INVOICE_AMOUNT_CLASSIFY_BILLED_SUPPLIER_ORDER'=>array('family'=>'classify_supplier_order', 'position'=>62, 'enabled'=>'! empty($conf->fournisseur->enabled)', 'picto'=>'order','warning'=>''),
);

if (! empty($conf->modules_parts['workflow']) && is_array($conf->modules_parts['workflow']))
{
	foreach($conf->modules_parts['workflow'] as $workflow)
	{
		$workflowcodes = array_merge($workflowcodes, $workflow);
	}
}

// Sort on position
$workflowcodes = dol_sort_array($workflowcodes, 'position');

$nbqualified=0;
$oldfamily='';

print '<table class="noborder" width="100%">'."\n";

foreach($workflowcodes as $key => $params)
{
	$picto=$params['picto'];
	$enabled=$params['enabled'];
	$family=$params['family'];

	if ($family == 'separator')
	{
		print '</table><br>';
		print '<table class="noborder" width="100%">'."\n";

		continue;
	}

	if (! verifCond($enabled)) continue;

   	$nbqualified++;


   	if ($oldfamily != $family)
   	{
	   	print '<tr class="liste_titre">'."\n";
		print '  <td>';
		if ($family == 'create')
		{
			print $langs->trans("AutomaticCreation");
		}
		elseif (preg_match('/classify_(.*)/', $family, $reg))
		{
			print $langs->trans("AutomaticClassification");
			if ($reg[1] == 'proposal') print ' - '.$langs->trans('Proposal');
			if ($reg[1] == 'order') print ' - '.$langs->trans('Order');
			if ($reg[1] == 'supplier_proposal') print ' - '.$langs->trans('SupplierProposal');
			if ($reg[1] == 'supplier_order') print ' - '.$langs->trans('SupplierOrder');
		}
		else
		{
			print $langs->trans("Description");
		}
		print '</td>';
		print '  <td align="center">'.$langs->trans("Status").'</td>';
		print "</tr>\n";
		$oldfamily = $family;
   	}

   	print "<tr class=\"oddeven\">\n";
   	print "<td>".img_object('', $picto).$langs->trans('desc'.$key);
   	if (! empty($params['warning']))
   	{
   		$langs->load("errors");
   		print ' '.img_warning($langs->transnoentitiesnoconv($params['warning']));
   	}
   	print "</td>\n";
   	print '<td align="center">';
   	if (! empty($conf->use_javascript_ajax))
   	{
   		print ajax_constantonoff($key);
   	}
   	else
   	{
   		if (! empty($conf->global->$key))
   		{
   			print '<a href="'.$_SERVER['PHP_SELF'].'?action=del'.$key.'">';
  			print img_picto($langs->trans("Activated"),'switch_on');
   			print '</a>';
   		}
   		else
   		{
   			print '<a href="'.$_SERVER['PHP_SELF'].'?action=set'.$key.'">';
  			print img_picto($langs->trans("Disabled"),'switch_off');
   			print '</a>';
   		}
   	}
   	print '</td>';
   	print '</tr>';
}

if ($nbqualified == 0)
{
    print '<tr><td colspan="3">'.$langs->trans("ThereIsNoWorkflowToModify");
}
print '</table>';

// End of page
llxFooter();
$db->close();
