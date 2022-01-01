<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
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
 *	\file       htdocs/fourn/index.php
 *	\ingroup    fournisseur
 *	\brief      Home page of suppliers area
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$langs->loadLangs(array("suppliers", "orders", "companies"));

// Security check
$socid = GETPOST("socid", 'int');
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'societe', $socid, '');


/*
 * View
 */

$commandestatic = new CommandeFournisseur($db);
$facturestatic = new FactureFournisseur($db);
$companystatic = new Societe($db);

llxHeader("", $langs->trans("SuppliersArea"));

print load_fiche_titre($langs->trans("SuppliersArea"));


//print '<table border="0" width="100%" class="notopnoleftnoright">';
//print '<tr><td valign="top" width="30%" class="notopnoleft">';
print '<div class="fichecenter"><div class="fichethirdleft">';


// Orders
$sql = "SELECT count(cf.rowid), cf.fk_statut";
$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as cf,";
$sql .= " ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
$sql .= " WHERE cf.fk_soc = s.rowid ";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND sc.fk_user = ".$user->id;
$sql .= " AND cf.entity = ".$conf->entity;
$sql .= " GROUP BY cf.fk_statut";

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Orders").'</td><td class="center">'.$langs->trans("Nb").'</td><td>&nbsp;</td>';
	print "</tr>\n";

	while ($i < $num)
	{
		$row = $db->fetch_row($resql);

		print '<tr class="oddeven">';
		print '<td>'.$commandestatic->LibStatut($row[1]).'</td>';
		print '<td class="center">'.$row[0].'</td>';
		print '<td class="center"><a href="'.DOL_URL_ROOT.'/fourn/commande/list.php?statut='.$row[1].'">'.$commandestatic->LibStatut($row[1], 3).'</a></td>';

		print "</tr>\n";
		$i++;
	}
	print "</table>";
	print "<br>\n";
	$db->free($resql);
}
else
{
	dol_print_error($db);
}


// Draft orders
if (!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_order->enabled))
{
	$langs->load("orders");

	$sql = "SELECT cf.rowid, cf.ref, cf.total_ttc,";
	$sql .= " s.nom as name, s.rowid as socid";
	$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as cf";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
	$sql .= " WHERE cf.fk_soc = s.rowid";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND sc.fk_user = ".$user->id;
	$sql .= " AND cf.entity = ".$conf->entity;
	$sql .= " AND cf.fk_statut = 0";
	if ($socid) $sql .= " AND cf.fk_soc = ".$socid;

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);
		if ($num)
		{
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<td colspan="3">'.$langs->trans("DraftOrders").'<span class="badge marginleftonlyshort">'.$num.'</span></td></tr>';

			$i = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				print '<tr class="oddeven"><td  class="nowrap">';
				$commandestatic->id = $obj->rowid;
				$commandestatic->ref = $obj->ref;
				print $commandestatic->getNomUrl(1, '', 16);
				print '</td>';
				print '<td  class="nowrap">';
				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				$companystatic->client = 0;
				print $companystatic->getNomUrl(1, '', 16);
				print '</td>';
				print '<td class="right nowrap">'.price($obj->total_ttc).'</td></tr>';
				$i++;
				$total += $obj->total_ttc;
			}
			if ($total > 0)
			{
				print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" class="right">'.price($total)."</td></tr>";
			}
			print "</table>";
			print "<br>\n";
		}
	}
}

// Draft invoices
if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_invoice->enabled)) && $user->rights->fournisseur->facture->lire)
{
	$sql = "SELECT ff.ref_supplier, ff.rowid, ff.total_ttc, ff.type";
	$sql .= ", s.nom as name, s.rowid as socid";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as ff";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
	$sql .= " WHERE s.rowid = ff.fk_soc";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND sc.fk_user = ".$user->id;
	$sql .= " AND ff.entity = ".$conf->entity;
	$sql .= " AND ff.fk_statut = 0";
	if ($socid)	$sql .= " AND f.fk_soc = ".$socid;

	$resql = $db->query($sql);

	if ($resql)
	{
		$num = $db->num_rows($resql);
		if ($num)
		{
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<td colspan="3">'.$langs->trans("DraftBills").'<span class="badge marginleftonlyshort">'.$num.'</span></td></tr>';
			$i = 0;
			$tot_ttc = 0;

			while ($i < $num && $i < 20)
			{
				$obj = $db->fetch_object($resql);

				print '<tr class="oddeven"><td class="nowrap">';
				$facturestatic->ref = $obj->ref;
				$facturestatic->id = $obj->rowid;
				$facturestatic->type = $obj->type;
				print $facturestatic->getNomUrl(1, '');
				print '</td>';
				print '<td class="nowrap">';
				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				$companystatic->client = 0;
				print $companystatic->getNomUrl(1, '', 16);
				print '</td>';
				print '<td class="right">'.price($obj->total_ttc).'</td>';
				print '</tr>';
				$tot_ttc += $obj->total_ttc;
				$i++;
			}

			print '<tr class="liste_total"><td class="left">'.$langs->trans("Total").'</td>';
			print '<td colspan="2" class="right">'.price($tot_ttc).'</td>';
			print '</tr>';

			print "</table>";
			print "<br>\n";
		}
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}


//print '</td><td valign="top" width="70%" class="notopnoleftnoright">';
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * List last modified supliers
 */
$max = 10;
$sql = "SELECT s.rowid as socid, s.nom as name, s.town, s.datec, s.tms, s.prefix_comm, s.code_fournisseur, s.code_compta_fournisseur";
$sql .= ", st.libelle as stcomm";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= ", ".MAIN_DB_PREFIX."c_stcomm as st";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " WHERE s.fk_stcomm = st.id";
$sql .= " AND s.fournisseur = 1";
$sql .= " AND s.entity IN (".getEntity('societe').")";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
if ($socid) $sql .= " AND s.rowid = ".$socid;
$sql .= " ORDER BY s.tms DESC";
$sql .= $db->plimit($max, 0);

$resql = $db->query($sql);
if ($resql)
{
	$langs->load("boxes");
	$num = $db->num_rows($resql);
	$i = 0;

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td colspan="2">'.$langs->trans("BoxTitleLastSuppliers", min($max, $num))."</td>\n";
	print '<td class="right">'.$langs->trans("DateModification")."</td>\n";
	print "</tr>\n";

	while ($obj = $db->fetch_object($resql))
	{
		print '<tr class="oddeven">';
		print '<td><a href="card.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowSupplier"), "company").'</a>';
		print "&nbsp;<a href=\"card.php?socid=".$obj->socid."\">".$obj->name."</a></td>\n";
		print '<td class="left">'.$obj->code_fournisseur.'&nbsp;</td>';
		print '<td class="right">'.dol_print_date($db->jdate($obj->tms), 'day').'</td>';
		print "</tr>\n";
	}
	print "</table>\n";

	$db->free($resql);
}
else
{
	dol_print_error($db);
}


/*
 * List of suppliers categories
 */
$companystatic->LoadSupplierCateg();
$categstatic = new Categorie($db);

if (count($companystatic->SupplierCategories))
{
	print '<br>';

	print '<table class="liste centpercent">';
	print '<tr class="liste_titre"><td colspan="2">';
	print $langs->trans("Category");
	print "</td></tr>\n";

	foreach ($companystatic->SupplierCategories as $rowid => $label)
	{
		print '<tr class="oddeven">'."\n";
		print '<td>';
		$categstatic->id = $rowid;
		$categstatic->ref = $label;
		$categstatic->label = $label;
		print $categstatic->getNomUrl(1);
		print '</td>'."\n";
		// TODO this page not exist
		/*
		print '<td class="right">';
		print '<a href="stats.php?cat='.$rowid.'">('.$langs->trans("Stats").')</a>';
		print "</tr>\n";
		*/
	}
	print "</table>\n";
	print "<br>\n";
}


//print "</td></tr></table>\n";
print '</div></div></div>';

// End of page
llxFooter();
$db->close();
