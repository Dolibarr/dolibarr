<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file       htdocs/fourn/index.php
 *	\ingroup    fournisseur
 *	\brief      Home page of suppliers area
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$langs->load("suppliers");
$langs->load("orders");
$langs->load("companies");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');


/*
 * View
 */

$commandestatic=new CommandeFournisseur($db);
$facturestatic=new FactureFournisseur($db);
$companystatic=new Societe($db);

llxHeader("",$langs->trans("SuppliersArea"));

print_fiche_titre($langs->trans("SuppliersArea"));

print '<table border="0" width="100%" class="notopnoleftnoright">';
print '<tr><td valign="top" width="30%" class="notopnoleft">';

// Orders
$commande = new CommandeFournisseur($db);
$sql = "SELECT count(cf.rowid), cf.fk_statut";
$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as cf,";
$sql.= " ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
$sql.= " WHERE cf.fk_soc = s.rowid ";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND sc.fk_user = " .$user->id;
$sql.= " AND cf.entity = ".$conf->entity;
$sql.= " GROUP BY cf.fk_statut";

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Orders").'</td><td align="center">'.$langs->trans("Nb").'</td><td>&nbsp;</td>';
	print "</tr>\n";
	$var=True;

	while ($i < $num)
	{
		$row = $db->fetch_row($resql);
		$var=!$var;

		print "<tr $bc[$var]>";
		print '<td>'.$langs->trans($commande->statuts[$row[1]]).'</td>';
		print '<td align="center">'.$row[0].'</td>';
		print '<td align="center"><a href="'.DOL_URL_ROOT.'/fourn/commande/liste.php?statut='.$row[1].'">'.$commande->LibStatut($row[1],3).'</a></td>';

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
if (! empty($conf->fournisseur->enabled))
{
	$langs->load("orders");

	$sql = "SELECT cf.rowid, cf.ref, cf.total_ttc";
	$sql.= ", s.nom, s.rowid as socid";
	$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as cf";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
	$sql.= " WHERE cf.fk_soc = s.rowid";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND sc.fk_user = " .$user->id;
	$sql.= " AND cf.entity = ".$conf->entity;
	$sql.= " AND cf.fk_statut = 0";
	if ($socid) $sql .= " AND cf.fk_soc = ".$socid;

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);
		if ($num)
		{
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td colspan="3">'.$langs->trans("DraftOrders").' ('.$num.')</td></tr>';

			$i = 0;
			$var = true;
			while ($i < $num)
			{
				$var=!$var;
				$obj = $db->fetch_object($resql);
				print '<tr '.$bc[$var].'><td  nowrap="nowrap">';
				$commandestatic->id=$obj->rowid;
				$commandestatic->ref=$obj->ref;
				print $commandestatic->getNomUrl(1,'',16);
				print '</td>';
				print '<td  nowrap="nowrap">';
				$companystatic->id=$obj->socid;
				$companystatic->nom=$obj->nom;
				$companystatic->client=0;
				print $companystatic->getNomUrl(1,'',16);
				print '</td>';
				print '<td align="right" nowrap="nowrap">'.price($obj->total_ttc).'</td></tr>';
				$i++;
				$total += $obj->total_ttc;
			}
			if ($total>0)
			{
				$var=!$var;
				print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" align="right">'.price($total)."</td></tr>";
			}
			print "</table>";
			print "<br>\n";
		}
	}
}

// Draft invoices
if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->lire)
{
	$sql = "SELECT ff.facnumber, ff.rowid, ff.total_ttc, ff.type";
	$sql.= ", s.nom, s.rowid as socid";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as ff";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
	$sql.= " WHERE s.rowid = ff.fk_soc";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND sc.fk_user = " .$user->id;
	$sql.= " AND ff.entity = ".$conf->entity;
	$sql.= " AND ff.fk_statut = 0";
	if ($socid)	$sql .= " AND f.fk_soc = ".$socid;

	$resql = $db->query($sql);

	if ( $resql )
	{
		$num = $db->num_rows($resql);
		if ($num)
		{
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td colspan="3">'.$langs->trans("DraftBills").' ('.$num.')</td></tr>';
			$i = 0;
			$tot_ttc = 0;
			$var = True;
			while ($i < $num && $i < 20)
			{
				$obj = $db->fetch_object($resql);
				$var=!$var;
				print '<tr '.$bc[$var].'><td nowrap="nowrap">';
				$facturestatic->ref=$obj->facnumber;
				$facturestatic->id=$obj->rowid;
				$facturestatic->type=$obj->type;
				print $facturestatic->getNomUrl(1,'');
				print '</td>';
				print '<td nowrap="nowrap">';
				$companystatic->id=$obj->socid;
				$companystatic->nom=$obj->nom;
				$companystatic->client=0;
				print $companystatic->getNomUrl(1,'',16);
				print '</td>';
				print '<td align="right">'.price($obj->total_ttc).'</td>';
				print '</tr>';
				$tot_ttc+=$obj->total_ttc;
				$i++;
			}

			print '<tr class="liste_total"><td align="left">'.$langs->trans("Total").'</td>';
			print '<td colspan="2" align="right">'.price($tot_ttc).'</td>';
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

print "</td>\n";
print '<td valign="top" width="70%" class="notopnoleftnoright">';

/*
 * List last modified supliers
 */
$max=10;
$sql = "SELECT s.rowid as socid, s.nom, s.ville, s.datec, s.datea, s.tms, s.prefix_comm, s.code_fournisseur, s.code_compta_fournisseur";
$sql.= ", st.libelle as stcomm";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."c_stcomm as st";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.fk_stcomm = st.id";
$sql.= " AND s.fournisseur = 1";
$sql.= " AND s.entity IN (".getEntity('societe', 1).")";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql .= " AND s.rowid = ".$socid;
$sql.= " ORDER BY s.tms DESC";
$sql .= $db->plimit($max, 0);

$resql = $db->query($sql);
if ($resql)
{
	$langs->load("boxes");
	$num = $db->num_rows($resql);
	$i = 0;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="2">'.$langs->trans("BoxTitleLastSuppliers",min($max,$num))."</td>\n";
	print '<td align="right">'.$langs->trans("DateModification")."</td>\n";
	print "</tr>\n";

	$var=True;

	while ($obj = $db->fetch_object($resql) )
	{
		$var=!$var;

		print "<tr $bc[$var]>";
		print '<td><a href="fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowSupplier"),"company").'</a>';
		print "&nbsp;<a href=\"fiche.php?socid=".$obj->socid."\">".$obj->nom."</a></td>\n";
		print '<td align="left">'.$obj->code_fournisseur.'&nbsp;</td>';
		print '<td align="right">'.dol_print_date($db->jdate($obj->tms),'day').'</td>';
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
$categstatic=new Categorie($db);

if (count($companystatic->SupplierCategories))
{
	print '<br>';

	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre"><td colspan="2">';
	print $langs->trans("Category");
	print "</td></tr>\n";
	$var=True;

	foreach ($companystatic->SupplierCategories as $rowid => $label)
	{
		$var=!$var;
		print "<tr $bc[$var]>\n";
		print '<td>';
		$categstatic->id=$rowid;
		$categstatic->ref=$label;
		$categstatic->label=$label;
		print $categstatic->getNomUrl(1);
		print '</td>'."\n";
		// TODO this page not exist
		/*
		print '<td align="right">';
		print '<a href="stats.php?cat='.$rowid.'">('.$langs->trans("Stats").')</a>';
		print "</tr>\n";
		*/
	}
	print "</table>\n";
	print "<br>\n";
}

print "</td></tr>\n";
print "</table>\n";

$db->close();

llxFooter();
?>
