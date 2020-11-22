<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012 Juanjo Menent        <jmenent@2byte.es>
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
 *  \file       htdocs/compta/prelevement/demandes.php
 *  \ingroup    prelevement
 *  \brief      Page to list bank transfer requests (debit order or payments of vendors)
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/modPrelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'withdrawals', 'companies'));

// Security check
$socid = GETPOST('socid', 'int');
$status = GETPOST('status', 'int');
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'prelevement', '', '', 'bons');

$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'directdebitcredittransferlist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss  = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$type = GETPOST('type', 'aZ09');

$search_facture = GETPOST('search_facture', 'alpha');
$search_societe = GETPOST('search_societe', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) $sortorder = "DESC";
if (!$sortfield) $sortfield = "f.ref";

$massactionbutton = '';

$hookmanager->initHooks(array('withdrawalstodolist'));


/*
 * Actions
 */

$parameters = array('socid' => $socid, 'limit' => $limit, 'page' => $page, 'offset' => $offset);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$search_facture = '';
	$search_societe = '';
	$search_array_options = array();
}



/*
 * View
 */

if ($type != 'bank-transfer') {
	if (!$status) {
		$title = $langs->trans("RequestStandingOrderToTreat");
	} else {
		$title = $langs->trans("RequestStandingOrderTreated");
	}
} else {
	if (!$status) {
		$title = $langs->trans("RequestPaymentsByBankTransferToTreat");
	} else {
		$title = $langs->trans("RequestPaymentsByBankTransferTreated");
	}
}

llxHeader('', $title);

$thirdpartystatic = new Societe($db);
if ($type == 'bank-transfer') {
	$invoicestatic = new FactureFournisseur($db);
} else {
	$invoicestatic = new Facture($db);
}

// List of requests

$sql = "SELECT f.ref, f.rowid, f.total_ttc,";
$sql .= " s.nom as name, s.rowid as socid,";
$sql .= " pfd.date_demande as date_demande, pfd.amount, pfd.fk_user_demande";
if ($type != 'bank-transfer') {
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f,";
} else {
	$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f,";
}
$sql .= " ".MAIN_DB_PREFIX."societe as s,";
$sql .= " ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " WHERE s.rowid = f.fk_soc";
$sql .= " AND f.entity IN (".getEntity('invoice').")";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
if ($socid) $sql .= " AND f.fk_soc = ".$socid;
if (!$status) $sql .= " AND pfd.traite = 0";
$sql .= " AND pfd.ext_payment_id IS NULL";
if ($status) $sql .= " AND pfd.traite = ".$status;
$sql .= " AND f.total_ttc > 0";
if (empty($conf->global->WITHDRAWAL_ALLOW_ANY_INVOICE_STATUS))
{
	$sql .= " AND f.fk_statut = ".Facture::STATUS_VALIDATED;
}
if ($type != 'bank-transfer') {
	$sql .= " AND pfd.fk_facture = f.rowid";
} else {
	$sql .= " AND pfd.fk_facture_fourn = f.rowid";
}
if ($search_facture) $sql .= natural_search("f.ref", $search_facture);
if ($search_societe) $sql .= natural_search("s.nom", $search_societe);
$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords)	// if total of record found is smaller than page * limit, goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}
// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
if (is_numeric($nbtotalofrecords) && $limit > $nbtotalofrecords)
{
	$num = $nbtotalofrecords;
} else {
	$sql .= $db->plimit($limit + 1, $offset);

	$resql = $db->query($sql);
	if (!$resql)
	{
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);
}



$newcardbutton = '<a class="marginrightonly" href="'.DOL_URL_ROOT.'/compta/prelevement/index.php">'.$langs->trans("Back").'</a>';
if ($type == 'bank-transfer') {
	$newcardbutton = '<a class="marginrightonly" href="'.DOL_URL_ROOT.'/compta/paymentbybanktransfer/index.php">'.$langs->trans("Back").'</a>';
}

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST"  id="searchFormList" name="searchFormList">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

$param = '';

$label = 'NewStandingOrder';
$typefilter = '';
if ($type == 'bank-transfer') {
	$label = 'NewPaymentByBankTransfer';
	$typefilter = 'type='.$type;
}
$newcardbutton .= dolGetButtonTitle($langs->trans($label), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/compta/prelevement/create.php'.($typefilter ? '?'.$typefilter : ''));

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'generic', 0, $newcardbutton, '', $limit);

print '<table class="liste centpercent">';

print '<tr class="liste_titre">';
print_liste_field_titre("Bill", $_SERVER["PHP_SELF"]);
print_liste_field_titre("Company", $_SERVER["PHP_SELF"]);
print_liste_field_titre("AmountRequested", $_SERVER["PHP_SELF"], "", "", $param, '', '', '', 'right ');
print_liste_field_titre("DateRequest", $_SERVER["PHP_SELF"], "", "", $param, '', '', '', 'center ');
print_liste_field_titre('');
print '</tr>';

print '<tr class="liste_titre">';
print '<td class="liste_titre"><input type="text" class="flat maxwidth150" name="search_facture" value="'.dol_escape_htmltag($search_facture).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat maxwidth150" name="search_societe" value="'.dol_escape_htmltag($search_societe).'"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>';

$i = 0;
while ($i < min($num, $limit))
{
	$obj = $db->fetch_object($resql);
	if (empty($obj)) break; // Should not happen

	$invoicestatic->fetch($obj->rowid);

	print '<tr class="oddeven">';

	// Ref facture
	print '<td>';
	print $invoicestatic->getNomUrl(1, 'withdraw');
	print '</td>';

	print '<td>';
	$thirdpartystatic->id = $obj->socid;
	$thirdpartystatic->name = $obj->name;
	print $thirdpartystatic->getNomUrl(1, 'customer');
	print '</td>';

	print '<td class="right">';
	print price($obj->amount, 1, $langs, 1, -1, -1, $conf->currency).' / '.price($obj->total_ttc, 1, $langs, 1, -1, -1, $conf->currency);
	print '</td>';

	print '<td class="center">'.dol_print_date($db->jdate($obj->date_demande), 'day').'</td>';

	print '<td class="right"></td>';

	print '</tr>';
	$i++;
}

print "</table><br>";

print '</form>';


// End of page
llxFooter();
$db->close();
