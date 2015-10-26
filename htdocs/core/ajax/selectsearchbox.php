<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/core/ajax/selectsearchbox.php
 *      \ingroup    core
 *      \brief      This script returns content of possible search
 */


// This script is called with a POST method.
// Directory to scan (full path) is inside POST['dir'].

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL',1); // Disables token renewal
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');
if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX','1');

$res=@include '../../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/json.lib.php';


$search_boxvalue=GETPOST('q');

$arrayresult=array();

// Define $searchform
if ((( ! empty($conf->societe->enabled) && (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) || empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))) || ! empty($conf->fournisseur->enabled)) && empty($conf->global->MAIN_SEARCHFORM_SOCIETE_DISABLED) && $user->rights->societe->lire)
{
    $langs->load("companies");
    //$searchform.=printSearchForm(DOL_URL_ROOT.'/societe/list.php', DOL_URL_ROOT.'/societe/list.php', $langs->trans("ThirdParties"), 'soc', 'sall', 'T', 'searchleftt', img_object('','company'));
	$arrayresult['searchintothirdparty']=array('text'=>img_picto('','object_company').' '.$langs->trans("SearchIntoThirdparties", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/societe/list.php?sall='.urlencode($search_boxvalue));
}

if (! empty($conf->societe->enabled) && empty($conf->global->MAIN_SEARCHFORM_CONTACT_DISABLED) && $user->rights->societe->lire)
{
    $langs->load("companies");
    //$searchform.=printSearchForm(DOL_URL_ROOT.'/contact/list.php', DOL_URL_ROOT.'/contact/list.php', $langs->trans("Contacts"), 'contact', 'sall', 'A', 'searchleftc', img_object('','contact'));
	$arrayresult['searchintocontact']=array('text'=>img_picto('','object_contact').' '.$langs->trans("SearchIntoContacts", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/contact/list.php?sall='.urlencode($search_boxvalue));
}

if (((! empty($conf->product->enabled) && $user->rights->produit->lire) || (! empty($conf->service->enabled) && $user->rights->service->lire))
&& empty($conf->global->MAIN_SEARCHFORM_PRODUITSERVICE_DISABLED))
{
    $langs->load("products");
    //$searchform.=printSearchForm(DOL_URL_ROOT.'/product/list.php', DOL_URL_ROOT.'/product/list.php', $langs->trans("Products")."/".$langs->trans("Services"), 'products', 'sall', 'P', 'searchleftp', img_object('','product'));
	$arrayresult['searchintoproduct']=array('text'=>img_picto('','object_product').' '.$langs->trans("SearchIntoProductsOrServices", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/product/list.php?sall='.urlencode($search_boxvalue));
}

if (! empty($conf->projet->enabled) && empty($conf->global->MAIN_SEARCHFORM_PROJECT_DISABLED) && $user->rights->projet->lire)
{
    $langs->load("projects");
    //$searchform.=printSearchForm(DOL_URL_ROOT.'/projet/list.php', DOL_URL_ROOT.'/projet/list.php', $langs->trans("Projects"), 'project', 'search_all', 'Q', 'searchleftproj', img_object('','projectpub'));
	$arrayresult['searchintoprojects']=array('text'=>img_picto('','object_projectpub').' '.$langs->trans("SearchIntoProjects", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/projet/list.php?search_all='.urlencode($search_boxvalue));
}

if (! empty($conf->adherent->enabled) && empty($conf->global->MAIN_SEARCHFORM_ADHERENT_DISABLED) && $user->rights->adherent->lire)
{
    $langs->load("members");
    //$searchform.=printSearchForm(DOL_URL_ROOT.'/adherents/list.php', DOL_URL_ROOT.'/adherents/list.php', $langs->trans("Members"), 'member', 'sall', 'M', 'searchleftm', img_object('','user'));
	$arrayresult['searchintomember']=array('text'=>img_picto('','object_user').' '.$langs->trans("SearchIntoMembers", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/adherents/list.php?sall='.urlencode($search_boxvalue));
}

if (! empty($conf->user->enabled) && empty($conf->global->MAIN_SEARCHFORM_PROJECT_DISABLED) && $user->rights->user->user->lire)
{
    $langs->load("users");
    //$searchform.=printSearchForm(DOL_URL_ROOT.'/projet/list.php', DOL_URL_ROOT.'/projet/list.php', $langs->trans("Projects"), 'project', 'search_all', 'Q', 'searchleftproj', img_object('','projectpub'));
	$arrayresult['searchintouser']=array('text'=>img_picto('','object_user').' '.$langs->trans("SearchIntoUsers", $search_boxvalue), 'url'=>DOL_URL_ROOT.'/user/index.php?sall='.urlencode($search_boxvalue));
}

/* Do we really need this. We already have a select for users, and we should be able to filter into user list on employee flag 
if (! empty($conf->hrm->enabled) && ! empty($conf->global->MAIN_SEARCHFORM_EMPLOYEE) && $user->rights->hrm->employee->read)
{
    $langs->load("hrm");
    $searchform.=printSearchForm(DOL_URL_ROOT.'/hrm/employee/list.php', DOL_URL_ROOT.'/hrm/employee/list.php', $langs->trans("Employees"), 'employee', 'search_all', 'M', 'searchleftemployee', img_object('','user'));
}
*/

// Execute hook addSearchEntry
$parameters=array();
$reshook=$hookmanager->executeHooks('addSearchEntry',$parameters);
if (empty($reshook))
{
	$arrayresult=array_merge($arrayresult, $hookmanager->resArray);
}
else $arrayresult=$hookmanager->resArray;









print json_encode($arrayresult);

if (is_object($db)) $db->close();
