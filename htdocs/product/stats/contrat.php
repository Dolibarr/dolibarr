<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *       \file       htdocs/product/stats/contrat.php
 *       \ingroup    product service contrat
 *       \brief      Page des stats des contrats pour un produit
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->load("contracts");
$langs->load("products");
$langs->load("companies");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service',$fieldvalue,'product&product','','',$fieldtype);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('productstatscontract'));

$mesg = '';

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="c.date_contrat";


/*
 * View
 */

$staticcontrat=new Contrat($db);
$staticcontratligne=new ContratLigne($db);

$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
	$product = new Product($db);
	$result = $product->fetch($id, $ref);

	$parameters=array('id'=>$id);
	$reshook=$hookmanager->executeHooks('doActions',$parameters,$product,$action);    // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

	llxHeader("","",$langs->trans("CardProduct".$product->type));

	if ($result > 0)
	{
		$head=product_prepare_head($product,$user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==Product::TYPE_SERVICE?'service':'product');
		dol_fiche_head($head, 'referers', $titre, 0, $picto);

		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$product,$action);    // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		print '<table class="border" width="100%">';

		// Reference
		print '<tr>';
		print '<td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $form->showrefnav($product,'ref','',1,'ref');
		print '</td>';
		print '</tr>';

		// Libelle
		print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$product->libelle.'</td>';
		print '</tr>';

		// Status (to sell)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td colspan="3">';
		print $product->getLibStatut(2,0);
		print '</td></tr>';

		// Status (to buy)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td colspan="3">';
		print $product->getLibStatut(2,1);
		print '</td></tr>';

		show_stats_for_company($product,$socid);

		print "</table>";

		print '</div>';

		$now=dol_now();

		$sql = "SELECT";
		$sql.= ' sum('.$db->ifsql("cd.statut=0",1,0).') as nb_initial,';
		$sql.= ' sum('.$db->ifsql("cd.statut=4 AND cd.date_fin_validite > '".$db->idate($now)."'",1,0).") as nb_running,";
		$sql.= ' sum('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite <= '".$db->idate($now)."')",1,0).') as nb_late,';
		$sql.= ' sum('.$db->ifsql("cd.statut=5",1,0).') as nb_closed,';
		$sql.= " c.rowid as rowid, c.date_contrat, c.statut as statut,";
		$sql.= " s.nom as name, s.rowid as socid, s.code_client";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= ", ".MAIN_DB_PREFIX."contrat as c";
		$sql.= ", ".MAIN_DB_PREFIX."contratdet as cd";
		$sql.= " WHERE c.rowid = cd.fk_contrat";
		$sql.= " AND c.fk_soc = s.rowid";
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " AND cd.fk_product =".$product->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid) $sql.= " AND s.rowid = ".$socid;
		$sql.= " GROUP BY c.rowid, c.date_contrat, c.statut, s.nom, s.rowid, s.code_client";
		$sql.= $db->order($sortfield, $sortorder);
		$sql.= $db->plimit($conf->liste_limit +1, $offset);

		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);

			print_barre_liste($langs->trans("Contrats"),$page,$_SERVER["PHP_SELF"],"&amp;id=$product->id",$sortfield,$sortorder,'',$num,0,'');

			$i = 0;
			print "<table class=\"noborder\" width=\"100%\">";

			print '<tr class="liste_titre">';
			print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"c.rowid","","&amp;id=".$product->id,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","&amp;id=".$product->id,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("CustomerCode"),$_SERVER["PHP_SELF"],"s.code_client","","&amp;id=".$product->id,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"c.date_contrat","","&amp;id=".$product->id,'align="center"',$sortfield,$sortorder);
			//print_liste_field_titre($langs->trans("AmountHT"),$_SERVER["PHP_SELF"],"c.amount","","&amp;id=".$product->id,'align="right"',$sortfield,$sortorder);
			print '<td class="liste_titre" width="16">'.$staticcontratligne->LibStatut(0,3).'</td>';
			print '<td class="liste_titre" width="16">'.$staticcontratligne->LibStatut(4,3).'</td>';
			print '<td class="liste_titre" width="16">'.$staticcontratligne->LibStatut(5,3).'</td>';
			print "</tr>\n";

			$contratstatic=new Contrat($db);

			if ($num > 0)
			{
				$var=True;
				while ($i < $num && $i < $conf->liste_limit)
				{
					$objp = $db->fetch_object($result);
					$var=!$var;

					print "<tr ".$bc[$var].">";
					print '<td><a href="'.DOL_URL_ROOT.'/contrat/card.php?id='.$objp->rowid.'">'.img_object($langs->trans("ShowContract"),"contract").' ';
					print $objp->rowid;
					print "</a></td>\n";
					print '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$objp->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($objp->name,44).'</a></td>';
					print "<td>".$objp->code_client."</td>\n";
					print "<td align=\"center\">";
					print dol_print_date($db->jdate($objp->date_contrat))."</td>";
					//print "<td align=\"right\">".price($objp->total_ht)."</td>\n";
					//print '<td align="right">';
					print '<td align="center">'.($objp->nb_initial>0?$objp->nb_initial:'').'</td>';
					print '<td align="center">'.($objp->nb_running+$objp->nb_late>0?$objp->nb_running+$objp->nb_late:'').'</td>';
					print '<td align="center">'.($objp->nb_closed>0?$objp->nb_closed:'').'</td>';
					//$contratstatic->LibStatut($objp->statut,5).'</td>';
					print "</tr>\n";
					$i++;
				}
			}
		}
		else
		{
			dol_print_error($db);
		}
		print "</table>";
		print '<br>';
		$db->free($result);
	}
}
else
{
	dol_print_error();
}


llxFooter();
$db->close();
