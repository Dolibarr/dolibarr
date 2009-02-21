<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/fourn/fiche.php
 *	\ingroup    fournisseur, facture
 *	\brief      Page de fiche fournisseur
 *	\version	$Id$
 */

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");

$langs->load('suppliers');
$langs->load('products');
$langs->load('bills');
$langs->load('orders');
$langs->load('companies');
$langs->load('commercial');

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');



/*
 * View
 */

$societe = new Fournisseur($db);
$contactstatic = new Contact($db);
$form = new Form($db);

if ( $societe->fetch($socid) )
{
	$addons[0][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$socid;
	$addons[0][1] = $societe->nom;

	llxHeader('',$langs->trans('SupplierCard').' : '.$societe->nom, $addons);

	/*
	 * Affichage onglets
	 */
	$head = societe_prepare_head($societe);

	dol_fiche_head($head, 'supplier', $langs->trans("ThirdParty"));


	print '<table width="100%" class="notopnoleftnoright">';
	print '<tr><td valign="top" width="50%" class="notopnoleft">';

	print '<table width="100%" class="border">';
	print '<tr><td width="20%">'.$langs->trans("Name").'</td><td width="80%" colspan="3">'.$societe->nom.'</td></tr>';

	print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$societe->prefix_comm.'</td></tr>';

	if ($societe->fournisseur)
	{
		print '<tr><td nowrap="nowrap">';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $societe->code_fournisseur;
		if ($societe->check_codefournisseur() <> 0) print ' '.$langs->trans("WrongSupplierCode");
		print '</td></tr>';
	}

	print '<tr><td valign="top">'.$langs->trans("Address").'</td><td colspan="3">'.nl2br($societe->adresse).'</td></tr>';

	print '<tr><td>'.$langs->trans("Zip").'</td><td>'.$societe->cp.'</td>';
	print '<td>'.$langs->trans("Town").'</td><td>'.$societe->ville.'</td></tr>';

	// Country
	print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
	if ($societe->isInEEC()) print $form->textwithhelp($societe->pays,$langs->trans("CountryIsInEEC"),1,0);
	else print $societe->pays;
	print '</td></tr>';

	// Phone
	print '<tr><td>'.$langs->trans("Phone").'</td><td>'.dol_print_phone($societe->tel,$societe->pays_code,0,$societe->id,'AC_TEL').'</td>';
	
	// Fax
	print '<td>'.$langs->trans("Fax").'</td><td>'.dol_print_phone($societe->fax,$societe->pays_code,0,$societe->id,'AC_FAX').'</td></tr>';
	
    // EMail
	print '<td>'.$langs->trans('EMail').'</td><td colspan="3">'.dol_print_email($societe->email,0,$societe->id,'AC_EMAIL').'</td></tr>';

	// Web
	print '<tr><td>'.$langs->trans("Web")."</td><td colspan=\"3\"><a href=\"http://$societe->url\">$societe->url</a>&nbsp;</td></tr>";

	// Assujetti a TVA ou pas
	print '<tr>';
	print '<td nowrap="nowrap">'.$langs->trans('VATIsUsed').'</td><td colspan="3">';
	print yn($societe->tva_assuj);
	print '</td>';
	print '</tr>';

	print '</table>';


	print '</td><td valign="top" width="50%" class="notopnoleftnoright">';
	$var=true;

	$MAXLIST=5;

	// Lien recap
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="4"><table width="100%" class="noborder"><tr><td>'.$langs->trans("Summary").'</td>';
	print '<td align="right"><a href="'.DOL_URL_ROOT.'/fourn/recap-fourn.php?socid='.$societe->id.'">'.$langs->trans("ShowSupplierPreview").'</a></td></tr></table></td>';
	print '</tr>';
	print '</table>';

	/*
	 * List of products
	 */
	if ($conf->produit->enabled || $conf->service->enabled)
	{
		$langs->load("products");
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("ProductsAndServices").'</td><td align="right">';
		print '<a href="'.DOL_URL_ROOT.'/fourn/product/liste.php?fourn_id='.$societe->id.'">'.$langs->trans("All").' ('.$societe->NbProduct().')';
		print '</a></td></tr></table>';
	}

	
	print '<br>';

	/*
	 * Liste des commandes associees
	 */
	$orderstatic = new CommandeFournisseur($db);

	$sql  = "SELECT p.rowid,p.ref,".$db->pdate("p.date_commande")." as dc, p.fk_statut";
	$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as p ";
	$sql.= " WHERE p.fk_soc =".$societe->id;
	$sql.= " ORDER BY p.date_commande DESC";
	$sql.= " ".$db->plimit($MAXLIST);
	$resql=$db->query($sql);
	if ($resql)
	{
		$i = 0 ;
		$num = $db->num_rows($resql);
		if ($num > 0)
		{
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td colspan="3">';
			print '<table class="noborder" width="100%"><tr><td>'.$langs->trans("LastOrders",($num<$MAXLIST?$num:$MAXLIST)).'</td>';
			print '<td align="right"><a href="commande/liste.php?socid='.$societe->id.'">'.$langs->trans("AllOrders").' ('.$num.')</td></tr></table>';
			print '</td></tr>';
		}
		while ($i < $num && $i <= $MAXLIST)
		{
			$obj = $db->fetch_object($resql);
			$var=!$var;

			print "<tr $bc[$var]>";
			print '<td><a href="commande/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowOrder"),"order")." ".$obj->ref.'</a></td>';
			print '<td align="center" width="80">';
			if ($obj->dc)
			{
				print dol_print_date($obj->dc,'day');
			}
			else
			{
				print "-";
			}
			print '</td>';
			print '<td align="right" nowrap="nowrap">'.$orderstatic->LibStatut($obj->fk_statut,5).'</td>';
			print '</tr>';
			$i++;
		}
		$db->free($resql);
		if ($num > 0)
		{
			print "</table>";
		}
	}
	else
	{
		dol_print_error($db);
	}


	/*
	 * Liste des factures associees
	 */
	$MAXLIST=5;

	$langs->load('bills');
	$facturestatic = new FactureFournisseur($db);

	$sql = 'SELECT p.rowid,p.libelle,p.facnumber,p.fk_statut,'.$db->pdate('p.datef').' as df, total_ttc as amount, paye';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'facture_fourn as p';
	$sql.= ' WHERE p.fk_soc = '.$societe->id;
	$sql.= ' ORDER BY p.datef DESC';
	$resql=$db->query($sql);
	if ($resql)
	{
		$i = 0 ;
		$num = $db->num_rows($resql);
		if ($num > 0)
		{
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td colspan="4">';
			print '<table class="noborder" width="100%"><tr><td>'.$langs->trans('LastSuppliersBills',($num<=$MAXLIST?"":$MAXLIST)).'</td><td align="right"><a href="facture/index.php?socid='.$societe->id.'">'.$langs->trans('AllBills').' ('.$num.')</td></tr></table>';
			print '</td></tr>';
		}
		while ($i < min($num,$MAXLIST))
		{
			$obj = $db->fetch_object($resql);
			$var=!$var;
			print '<tr '.$bc[$var].'>';
			print '<td>';
			print '<a href="facture/fiche.php?facid='.$obj->rowid.'">';
			print img_object($langs->trans('ShowBill'),'bill').' '.$obj->facnumber.'</a> '.dol_trunc($obj->libelle,14).'</td>';
			print '<td align="center" nowrap="nowrap">'.dol_print_date($obj->df,'day').'</td>';
			print '<td align="right" nowrap="nowrap">'.price($obj->amount).'</td>';
			print '<td align="right" nowrap="nowrap">'.$facturestatic->LibStatut($obj->paye,$obj->fk_statut,5).'</td>';
			print '</tr>';
			$i++;
		}
		$db->free($resql);
		if ($num > 0)
		{
			print '</table>';
		}
	}
	else
	{
		dol_print_error($db);
	}

	print '</td></tr>';
	print '</table>' . "\n";
	print '</div>';


	/*
	 *
	 * Barre d'actions
	 *
	 */

	print '<div class="tabsAction">';

	if ($user->rights->fournisseur->commande->creer)
	{
		$langs->load("orders");
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/commande/fiche.php?action=create&socid='.$societe->id.'">'.$langs->trans("AddOrder").'</a>';
	}

	if ($user->rights->fournisseur->facture->creer)
	{
		$langs->load("bills");
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/fiche.php?action=create&socid='.$societe->id.'">'.$langs->trans("AddBill").'</a>';
	}

	if ($conf->agenda->enabled && $user->rights->agenda->myactions->create)
	{
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&socid='.$societe->id.'">'.$langs->trans("AddAction").'</a>';
	}

	if ($user->rights->societe->contact->creer)
	{
		print "<a class=\"butAction\" href=\"".DOL_URL_ROOT.'/contact/fiche.php?socid='.$socid."&amp;action=create\">".$langs->trans("AddContact")."</a>";
	}

	print '</div>';
	print '<br>';

	/*
	 * Liste des contacts
	 */
	show_contacts($conf,$langs,$db,$societe);

	/*
	 *      Listes des actions a faire
	 */
	show_actions_todo($conf,$langs,$db,$societe);

	/*
	 *      Listes des actions effectuees
	 */
	show_actions_done($conf,$langs,$db,$societe);
}
else
{
	dol_print_error($db);
}
$db->close();

llxFooter('$Date$ - $Revision$');
?>
