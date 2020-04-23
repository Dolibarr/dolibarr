<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	\file       htdocs/zapier/zapierindex.php
 *	\ingroup    zapier
 *	\brief      Home page of zapier top menu
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("zapier@zapier"));

$action = GETPOST('action', 'alpha');


// Securite acces client
if (!$user->rights->zapier->read) accessforbidden();
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0)
{
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("ZapierForDolibarrArea"));

print load_fiche_titre($langs->trans("ZapierForDolibarrArea"), '', 'zapier.png@zapier');

print '<div class="fichecenter"><div class="fichethirdleft">';


/* BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (! empty($conf->zapier->enabled) && $user->rights->zapier->read)
{
	$langs->load("orders");

	$sql = "SELECT c.rowid, c.ref, c.ref_client, c.total_ht, c.tva as total_tva, c.total_ttc, s.rowid as socid, s.nom as name, s.client, s.canvas";
    $sql.= ", s.code_client";
	$sql.= " FROM ".MAIN_DB_PREFIX."commande as c";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.fk_statut = 0";
	$sql.= " AND c.entity IN (".getEntity('commande').")";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND c.fk_soc = ".$socid;

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("DraftOrders").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th></tr>';

		$var = true;
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td class="nowrap">';
                $orderstatic->id=$obj->rowid;
                $orderstatic->ref=$obj->ref;
                $orderstatic->ref_client=$obj->ref_client;
                $orderstatic->total_ht = $obj->total_ht;
                $orderstatic->total_tva = $obj->total_tva;
                $orderstatic->total_ttc = $obj->total_ttc;
                print $orderstatic->getNomUrl(1);
                print '</td>';
				print '<td class="nowrap">';
				$companystatic->id=$obj->socid;
				$companystatic->name=$obj->name;
				$companystatic->client=$obj->client;
                $companystatic->code_client = $obj->code_client;
                $companystatic->code_fournisseur = $obj->code_fournisseur;
                $companystatic->canvas=$obj->canvas;
				print $companystatic->getNomUrl(1,'customer',16);
				print '</td>';
				print '<td class="right" class="nowrap">'.price($obj->total_ttc).'</td></tr>';
				$i++;
				$total += $obj->total_ttc;
			}
			if ($total>0)
			{

				print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" class="right">'.price($total)."</td></tr>";
			}
		}
		else
		{

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoOrder").'</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}
END MODULEBUILDER DRAFT MYOBJECT */


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


$NBMAX = 3;
$max = 3;

/* BEGIN MODULEBUILDER LASTMODIFIED MYOBJECT
// Last modified myobject
if (! empty($conf->zapier->enabled) && $user->rights->zapier->read)
{
	$sql = "SELECT s.rowid, s.nom as name, s.client, s.datec, s.tms, s.canvas";
    $sql.= ", s.code_client";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.client IN (1, 2, 3)";
	$sql.= " AND s.entity IN (".getEntity($companystatic->element).")";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND s.rowid = $socid";
	$sql .= " ORDER BY s.tms DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">';
		if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print $langs->trans("BoxTitleLastCustomersOrProspects",$max);
        else if (! empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print $langs->trans("BoxTitleLastModifiedProspects",$max);
		else print $langs->trans("BoxTitleLastModifiedCustomers",$max);
		print '</th>';
		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '</tr>';
		if ($num)
		{
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);
				$companystatic->id=$objp->rowid;
				$companystatic->name=$objp->name;
				$companystatic->client=$objp->client;
                $companystatic->code_client = $objp->code_client;
                $companystatic->code_fournisseur = $objp->code_fournisseur;
                $companystatic->canvas=$objp->canvas;
				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$companystatic->getNomUrl(1,'customer',48).'</td>';
				print '<td class="right nowrap">';
				print $companystatic->getLibCustProspStatut();
				print "</td>";
				print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms),'day')."</td>";
				print '</tr>';
				$i++;


			}

			$db->free($resql);
		}
		else
		{
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print "</table><br>";
	}
}
*/

print '</div></div></div>';

// End of page
llxFooter();
$db->close();
