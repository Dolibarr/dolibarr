<?php
/* Copyright (C) 2015-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/core/ajax/selectsearchbox.php
 *      \ingroup    core
 *      \brief      This script returns content of possible search
 */


// This script is called with a POST method or as an include.

if (! isset($usedbyinclude) || empty($usedbyinclude))
{
    if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
    if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
    if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
    if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
    if (! defined('NOREDIRECTBYMAINTOLOGIN')) define('NOREDIRECTBYMAINTOLOGIN', '1');

    $res=@include '../../main.inc.php';
    if ($res == 'ERROR_NOT_LOGGED')
    {
    	$langs->load("other");
    	$arrayresult['jumptologin']=array('img'=>'object_generic', 'label'=>$langs->trans("JumpToLogin"), 'text'=>'<span class="fa fa-sign-in"></span> '.$langs->trans("JumpToLogin"), 'url'=>DOL_URL_ROOT.'/index.php');
    	print json_encode($arrayresult);
    	if (is_object($db)) $db->close();
    	exit;
    }
}

include_once DOL_DOCUMENT_ROOT.'/core/lib/json.lib.php';

//global $hookmanager;
$hookmanager->initHooks(array('searchform'));

$search_boxvalue=GETPOST('q', 'none');

$arrayresult=array();

// Define $searchform

if (! empty($conf->adherent->enabled) && empty($conf->global->MAIN_SEARCHFORM_ADHERENT_DISABLED) && $user->rights->adherent->lire)
{
	$arrayresult['searchintomember']=array('position'=>8, 'shortcut'=>'M', 'img'=>'object_user', 'label'=>$langs->trans("SearchIntoMembers", $search_boxvalue), 'text'=>img_picto('', 'object_user').' '.$langs->trans("SearchIntoMembers", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/adherents/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
}

if ((( ! empty($conf->societe->enabled) && (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) || empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))) || ! empty($conf->fournisseur->enabled)) && empty($conf->global->MAIN_SEARCHFORM_SOCIETE_DISABLED) && $user->rights->societe->lire)
{
	$arrayresult['searchintothirdparty']=array('position'=>10, 'shortcut'=>'T', 'img'=>'object_company', 'label'=>$langs->trans("SearchIntoThirdparties", $search_boxvalue), 'text'=>img_picto('', 'object_company').' '.$langs->trans("SearchIntoThirdparties", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/societe/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
}

if (! empty($conf->societe->enabled) && empty($conf->global->MAIN_SEARCHFORM_CONTACT_DISABLED) && $user->rights->societe->lire)
{
	$arrayresult['searchintocontact']=array('position'=>15, 'shortcut'=>'A', 'img'=>'object_contact', 'label'=>$langs->trans("SearchIntoContacts", $search_boxvalue), 'text'=>img_picto('', 'object_contact').' '.$langs->trans("SearchIntoContacts", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/contact/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
}

if (((! empty($conf->product->enabled) && $user->rights->produit->lire) || (! empty($conf->service->enabled) && $user->rights->service->lire))
&& empty($conf->global->MAIN_SEARCHFORM_PRODUITSERVICE_DISABLED))
{
	$arrayresult['searchintoproduct']=array('position'=>30, 'shortcut'=>'P', 'img'=>'object_product', 'label'=>$langs->trans("SearchIntoProductsOrServices", $search_boxvalue),'text'=>img_picto('', 'object_product').' '.$langs->trans("SearchIntoProductsOrServices", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/product/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
}

if (! empty($conf->projet->enabled) && empty($conf->global->MAIN_SEARCHFORM_PROJECT_DISABLED) && $user->rights->projet->lire)
{
	$arrayresult['searchintoprojects']=array('position'=>40, 'shortcut'=>'Q', 'img'=>'object_projectpub', 'label'=>$langs->trans("SearchIntoProjects", $search_boxvalue), 'text'=>img_picto('', 'object_projectpub').' '.$langs->trans("SearchIntoProjects", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/projet/list.php'.($search_boxvalue?'?search_all='.urlencode($search_boxvalue):''));
}
if (! empty($conf->projet->enabled) && empty($conf->global->MAIN_SEARCHFORM_TASK_DISABLED) && $user->rights->projet->lire)
{
	$arrayresult['searchintotasks']=array('position'=>45, 'img'=>'object_task', 'label'=>$langs->trans("SearchIntoTasks", $search_boxvalue), 'text'=>img_picto('', 'object_task').' '.$langs->trans("SearchIntoTasks", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/projet/tasks/list.php'.($search_boxvalue?'?search_all='.urlencode($search_boxvalue):''));
}

if (! empty($conf->propal->enabled) && empty($conf->global->MAIN_SEARCHFORM_CUSTOMER_PROPAL_DISABLED) && $user->rights->propal->lire)
{
	$arrayresult['searchintopropal']=array('position'=>60, 'img'=>'object_propal', 'label'=>$langs->trans("SearchIntoCustomerProposals", $search_boxvalue), 'text'=>img_picto('', 'object_propal').' '.$langs->trans("SearchIntoCustomerProposals", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/comm/propal/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
}
if (! empty($conf->commande->enabled) && empty($conf->global->MAIN_SEARCHFORM_CUSTOMER_ORDER_DISABLED) && $user->rights->commande->lire)
{
	$arrayresult['searchintoorder']=array('position'=>70, 'img'=>'object_order', 'label'=>$langs->trans("SearchIntoCustomerOrders", $search_boxvalue), 'text'=>img_picto('', 'object_order').' '.$langs->trans("SearchIntoCustomerOrders", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/commande/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
}
if (! empty($conf->expedition->enabled) && empty($conf->global->MAIN_SEARCHFORM_CUSTOMER_SHIPMENT_DISABLED) && $user->rights->expedition->lire)
{
	$arrayresult['searchintoshipment']=array('position'=>80, 'img'=>'object_sending', 'label'=>$langs->trans("SearchIntoCustomerShipments", $search_boxvalue), 'text'=>img_picto('', 'object_sending').' '.$langs->trans("SearchIntoCustomerShipments", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/expedition/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
}
if (! empty($conf->facture->enabled) && empty($conf->global->MAIN_SEARCHFORM_CUSTOMER_INVOICE_DISABLED) && $user->rights->facture->lire)
{
	$arrayresult['searchintoinvoice']=array('position'=>90, 'img'=>'object_bill', 'label'=>$langs->trans("SearchIntoCustomerInvoices", $search_boxvalue), 'text'=>img_picto('', 'object_bill').' '.$langs->trans("SearchIntoCustomerInvoices", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/compta/facture/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
}

if (! empty($conf->supplier_proposal->enabled) && empty($conf->global->MAIN_SEARCHFORM_SUPPLIER_PROPAL_DISABLED) && $user->rights->supplier_proposal->lire)
{
	$arrayresult['searchintosupplierpropal']=array('position'=>100, 'img'=>'object_propal', 'label'=>$langs->trans("SearchIntoSupplierProposals", $search_boxvalue), 'text'=>img_picto('', 'object_propal').' '.$langs->trans("SearchIntoSupplierProposals", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/supplier_proposal/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
}
if (! empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_SEARCHFORM_SUPPLIER_ORDER_DISABLED) && $user->rights->fournisseur->commande->lire)
{
	$arrayresult['searchintosupplierorder']=array('position'=>110, 'img'=>'object_order', 'label'=>$langs->trans("SearchIntoSupplierOrders", $search_boxvalue), 'text'=>img_picto('', 'object_order').' '.$langs->trans("SearchIntoSupplierOrders", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/fourn/commande/list.php'.($search_boxvalue?'?search_all='.urlencode($search_boxvalue):''));
}
if (! empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_SEARCHFORM_SUPPLIER_INVOICE_DISABLED) && $user->rights->fournisseur->facture->lire)
{
	$arrayresult['searchintosupplierinvoice']=array('position'=>120, 'img'=>'object_bill', 'label'=>$langs->trans("SearchIntoSupplierInvoices", $search_boxvalue), 'text'=>img_picto('', 'object_bill').' '.$langs->trans("SearchIntoSupplierInvoices", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/fourn/facture/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
}

if (! empty($conf->contrat->enabled) && empty($conf->global->MAIN_SEARCHFORM_CONTRACT_DISABLED) && $user->rights->contrat->lire)
{
	$arrayresult['searchintocontract']=array('position'=>130, 'img'=>'object_contract', 'label'=>$langs->trans("SearchIntoContracts", $search_boxvalue), 'text'=>img_picto('', 'object_contract').' '.$langs->trans("SearchIntoContracts", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/contrat/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
}
if (! empty($conf->ficheinter->enabled) && empty($conf->global->MAIN_SEARCHFORM_FICHINTER_DISABLED) && $user->rights->ficheinter->lire)
{
	$arrayresult['searchintointervention']=array('position'=>140, 'img'=>'object_intervention', 'label'=>$langs->trans("SearchIntoInterventions", $search_boxvalue), 'text'=>img_picto('', 'object_intervention').' '.$langs->trans("SearchIntoInterventions", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/fichinter/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
}

// HR
if (! empty($conf->user->enabled) && empty($conf->global->MAIN_SEARCHFORM_USER_DISABLED) && $user->rights->user->user->lire)
{
	$arrayresult['searchintouser']=array('position'=>200, 'shortcut'=>'U', 'img'=>'object_user', 'label'=>$langs->trans("SearchIntoUsers", $search_boxvalue), 'text'=>img_picto('', 'object_user').' '.$langs->trans("SearchIntoUsers", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/user/list.php'.($search_boxvalue?'?sall='.urlencode($search_boxvalue):''));
}
if (! empty($conf->expensereport->enabled) && empty($conf->global->MAIN_SEARCHFORM_EXPENSEREPORT_DISABLED) && $user->rights->expensereport->lire)
{
	$arrayresult['searchintoexpensereport']=array('position'=>210, 'img'=>'object_trip', 'label'=>$langs->trans("SearchIntoExpenseReports", $search_boxvalue), 'text'=>img_picto('', 'object_trip').' '.$langs->trans("SearchIntoExpenseReports", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/expensereport/list.php?mainmenu=hrm'.($search_boxvalue?'&sall='.urlencode($search_boxvalue):''));
}
if (! empty($conf->holiday->enabled) && empty($conf->global->MAIN_SEARCHFORM_HOLIDAY_DISABLED) && $user->rights->holiday->read)
{
	$arrayresult['searchintoleaves']=array('position'=>220, 'img'=>'object_holiday', 'label'=>$langs->trans("SearchIntoLeaves", $search_boxvalue), 'text'=>img_picto('', 'object_holiday').' '.$langs->trans("SearchIntoLeaves", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/holiday/list.php?mainmenu=hrm'.($search_boxvalue?'&sall='.urlencode($search_boxvalue):''));
}
if (! empty($conf->ticket->enabled) && empty($conf->global->MAIN_SEARCHFORM_TICKET_DISABLED) && $user->rights->ticket->read)
{
    $arrayresult['searchintotickets']=array('position'=>220, 'img'=>'object_ticket', 'label'=>$langs->trans("SearchIntoTickets", $search_boxvalue), 'text'=>img_picto('', 'object_ticket').' '.$langs->trans("SearchIntoTickets", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/ticket/list.php?mainmenu=ticket'.($search_boxvalue?'&sall='.urlencode($search_boxvalue):''));
}


/* Do we really need this. We already have a select for users, and we should be able to filter into user list on employee flag
if (! empty($conf->hrm->enabled) && ! empty($conf->global->MAIN_SEARCHFORM_EMPLOYEE) && $user->rights->hrm->employee->read)
{
    $langs->load("hrm");
    $searchform.=printSearchForm(DOL_URL_ROOT.'/hrm/employee/list.php', DOL_URL_ROOT.'/hrm/employee/list.php', $langs->trans("Employees"), 'employee', 'search_all', 'M', 'searchleftemployee', img_object('','user'));
}
*/

// Execute hook addSearchEntry
$parameters=array('search_boxvalue'=>$search_boxvalue, 'arrayresult'=>$arrayresult);
$reshook=$hookmanager->executeHooks('addSearchEntry', $parameters);
if (empty($reshook))
{
	$arrayresult=array_merge($arrayresult, $hookmanager->resArray);
}
else $arrayresult=$hookmanager->resArray;

// This allow to keep a search entry to the top
if(! empty($conf->global->DEFAULT_SEARCH_INTO_MODULE)) {
    $key = 'searchinto'.$conf->global->DEFAULT_SEARCH_INTO_MODULE;
    if(array_key_exists($key, $arrayresult)) $arrayresult[$key]['position'] = -10;
}

// Sort on position
$arrayresult = dol_sort_array($arrayresult, 'position');

// Print output if called by ajax or do nothing (var $arrayresult will be used) if called by an include
if (! isset($usedbyinclude) || empty($usedbyinclude))
{
    print json_encode($arrayresult);
    if (is_object($db)) $db->close();
}
