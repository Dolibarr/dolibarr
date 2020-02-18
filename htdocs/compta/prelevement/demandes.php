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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/compta/prelevement/demandes.php
 *  \ingroup    prelevement
 *  \brief      Page to list withdraw requests
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/modPrelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'withdrawals', 'companies'));

// Security check
$socid = GETPOST('socid', 'int');
$status = GETPOST('status', 'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'prelevement', '', '', 'bons');

$contextpage= GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'myobjectlist';   // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');											// Go back to a dedicated page
$optioncss  = GETPOST('optioncss', 'aZ');												// Option for the css output (always '' except when 'print')

// Load variable for pagination
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="f.ref";

$massactionbutton = '';


/*
 * View
 */

if (!$status)
{
	$title = $langs->trans("RequestStandingOrderToTreat");
}
else
{
	$title = $langs->trans("RequestStandingOrderTreated");
}

llxHeader('', $title);

$thirdpartystatic=new Societe($db);
$invoicestatic=new Facture($db);

// List of requests

$sql= "SELECT f.ref, f.rowid, f.total_ttc,";
$sql.= " s.nom as name, s.rowid as socid,";
$sql.= " pfd.date_demande as date_demande,";
$sql.= " pfd.fk_user_demande";
$sql.= " FROM ".MAIN_DB_PREFIX."facture as f,";
$sql.= " ".MAIN_DB_PREFIX."societe as s,";
$sql.= " ".MAIN_DB_PREFIX."prelevement_facture_demande as pfd";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.rowid = f.fk_soc";
$sql.= " AND f.entity IN (".getEntity('invoice').")";
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND f.fk_soc = ".$socid;
if (!$status) $sql.= " AND pfd.traite = 0";
if ($status) $sql.= " AND pfd.traite = ".$status;
$sql.= " AND f.total_ttc > 0";
if (empty($conf->global->WITHDRAWAL_ALLOW_ANY_INVOICE_STATUS))
{
	$sql.= " AND f.fk_statut = ".Facture::STATUS_VALIDATED;
}
$sql.= " AND pfd.fk_facture = f.rowid";
if (dol_strlen(trim(GETPOST('search_societe', 'alpha'))))
{
	$sql.= natural_search("s.nom", 'search_societe');
}
$sql.=$db->order($sortfield, $sortorder);


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
}
else
{
	$sql.= $db->plimit($limit+1, $offset);

	$resql=$db->query($sql);
	if (! $resql)
	{
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);
}



$newcardbutton = '<a href="'.DOL_URL_ROOT.'/compta/prelevement/index.php">'.$langs->trans("Back").'</a>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_generic', 0, $newcardbutton, '', $limit);

print '<table class="liste" width="100%">';

print '<tr class="liste_titre">';
print_liste_field_titre("Bill", $_SERVER["PHP_SELF"]);
print_liste_field_titre("Company", $_SERVER["PHP_SELF"]);
print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], "", "", $param, 'class="right"');
print_liste_field_titre("DateRequest", $_SERVER["PHP_SELF"], "", "", $param, 'class="center"');
print_liste_field_titre('');
print '</tr>';

print '<tr class="liste_titre">';
print '<td class="liste_titre"><input type="text" class="flat" name="search_facture" size="12" value="'.dol_escape_htmltag(GETPOST('search_facture', 'alpha')).'"></td>';
print '<td class="liste_titre"><input type="text" class="flat" name="search_societe" size="18" value="'.dol_escape_htmltag(GETPOST('search_societe', 'alpha')).'"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre"></td>';
// Action column
print '<td class="liste_titre" class="middle">';
$searchpicto=$form->showFilterAndCheckAddButtons($massactionbutton?1:0, 'checkforselect', 1);
print $searchpicto;
print '</td>';
print '</tr>';

$users = array();

$i = 0;
while ($i < min($num, $limit))
{
	$obj = $db->fetch_object($resql);
	if (empty($obj)) break;		// Should not happen

	print '<tr class="oddeven">';

	// Ref facture
	print '<td>';
	$invoicestatic->id=$obj->rowid;
	$invoicestatic->ref=$obj->ref;
	print $invoicestatic->getNomUrl(1, 'withdraw');
	print '</td>';

	print '<td>';
	$thirdpartystatic->id=$obj->socid;
	$thirdpartystatic->name=$obj->name;
	print $thirdpartystatic->getNomUrl(1, 'customer');
	print '</td>';

	print '<td class="right">'.price($obj->total_ttc).'</td>';

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
