<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
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
 *	\file       htdocs/compta/propal.php
 *	\ingroup    propale
 *	\brief      Page liste des propales (vision compta)
 *	\version	$Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/propal.lib.php");
if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/project.class.php');
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT.'/commande/commande.class.php');

$langs->load('companies');
$langs->load('compta');
$langs->load('orders');

$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
$viewstatut=$_GET['viewstatut'];
$propal_statut = $_GET['propal_statut'];
if($propal_statut != '')
$viewstatut=$propal_statut;

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.datep";
if ($page == -1) { $page = 0 ; }

$module='propale';
if (! empty($_GET["socid"]))
{
	$objectid=$_GET["socid"];
	$module='societe';
	$dbtable='';
}
else if (! empty($_GET["propalid"]))
{
	$objectid=$_GET["propalid"];
	$module='propale';
	$dbtable='propal';
}

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, $module, $objectid, $dbtable);



/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_GET["action"] == 'setstatut')
{
	// Close proposal
	$propal = new Propal($db);
	$propal->id = $_GET["propalid"];
	$propal->cloture($user, $_GET["statut"], $note);

}

if ( $action == 'delete' )
{
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."propal WHERE rowid = $propalid;";
	if ( $db->query($sql) )
	{

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."propaldet WHERE fk_propal = $propalid ;";
		if ( $db->query($sql) )
		{
			print '<div class="ok">'.$langs->trans("Deleted").'</div>';
		}
		else
		{
			dol_print_error($db);
		}
	}
	else
	{
		dol_print_error($db);
	}
	$propalid = 0;
	$brouillon = 1;
}

// Set project
if ($_POST['action'] == 'classin')
{
	$propal = new Propal($db);
	$propal->fetch($_GET['propalid']);
	$propal->setProject($_POST['projetidp']);
}



/*
 * View
 */

$now=gmmktime();

llxHeader();

$html = new Form($db);
$formfile = new FormFile($db);
$societestatic=new Societe($db);
$propalstatic=new Propal($db);

$now=gmmktime();

$id = $_GET['propalid'];
$ref= $_GET['ref'];
if ($id > 0 || ! empty($ref))
{
	if ($mesg) print "$mesg<br>";

	$product_static=new Product($db);

	$propal = new Propal($db);
	$propal->fetch($_GET['propalid'],$_GET["ref"]);

	$societe = new Societe($db);
	$societe->fetch($propal->socid);

	$head = propal_prepare_head($propal);
	dol_fiche_head($head, 'compta', $langs->trans('Proposal'), 0, 'propal');


	/*
	 * Fiche propal
	 *
	 */
	print '<table class="border" width="100%">';

	$linkback="<a href=\"propal.php?page=$page&socid=$socid&viewstatut=$viewstatut&sortfield=$sortfield&$sortorder\">".$langs->trans("BackToList")."</a>";

	// Ref
	print '<tr><td>'.$langs->trans('Ref').'</td><td colspan="5">';
	print $html->showrefnav($propal,'ref',$linkback,1,'ref','ref','');
	print '</td></tr>';

	// Ref client
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
	print $langs->trans('RefCustomer').'</td><td align="left">';
	print '</td>';
	if ($_GET['action'] != 'refclient' && $propal->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=refclient&amp;propalid='.$propal->id.'">'.img_edit($langs->trans('Modify')).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="5">';
	print $propal->ref_client;
	print '</td>';
	print '</tr>';

	$rowspan=8;

	// Company
	print '<tr><td>'.$langs->trans('Company').'</td><td colspan="5">'.$societe->getNomUrl(1).'</td></tr>';

	// Ligne info remises tiers
	print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="5">';
	if ($societe->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$societe->remise_client);
	else print $langs->trans("CompanyHasNoRelativeDiscount");
	$absolute_discount=$societe->getAvailableDiscounts();
	print '. ';
	if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->monnaie));
	else print $langs->trans("CompanyHasNoAbsoluteDiscount");
	print '.';
	print '</td></tr>';

	// Dates
	print '<tr><td>'.$langs->trans('Date').'</td><td colspan="3">';
	print dol_print_date($propal->date,'daytext');
	print '</td>';

	if ($conf->projet->enabled) $rowspan++;

	// Note
	print '<td valign="top" colspan="2" width="50%" rowspan="'.$rowspan.'">'.$langs->trans('NotePublic').' :<br>'. nl2br($propal->note_public).'</td>';
	print '</tr>';

	// Date fin propal
	print '<tr>';
	print '<td>'.$langs->trans('DateEndPropal').'</td><td colspan="3">';
	if ($propal->fin_validite)
	{
		print dol_print_date($propal->fin_validite,'daytext');
		if ($propal->statut == 1 && $propal->fin_validite < ($now - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
	}
	else
	{
		print $langs->trans("Unknown");
	}
	print '</td>';
	print '</tr>';

	// Payment term
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentConditionsShort');
	print '</td>';
	if ($_GET['action'] != 'editconditions' && $propal->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;propalid='.$propal->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($_GET['action'] == 'editconditions')
	{
		$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?propalid='.$propal->id,$propal->cond_reglement_id,'cond_reglement_id');
	}
	else
	{
		$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?propalid='.$propal->id,$propal->cond_reglement_id,'none');
	}
	print '</td>';

	// Payment mode
	print '<tr>';
	print '<td width="25%">';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentMode');
	print '</td>';
	if ($_GET['action'] != 'editmode' && $propal->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;propalid='.$propal->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($_GET['action'] == 'editmode')
	{
		$html->form_modes_reglement($_SERVER['PHP_SELF'].'?propalid='.$propal->id,$propal->mode_reglement_id,'mode_reglement_id');
	}
	else
	{
		$html->form_modes_reglement($_SERVER['PHP_SELF'].'?propalid='.$propal->id,$propal->mode_reglement_id,'none');
	}
	print '</td></tr>';

	// Project
	if ($conf->projet->enabled)
	{
		$langs->load("projects");
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Project').'</td>';
		if (1 == 2 && $user->rights->propale->creer)
		{
			if ($_GET['action'] != 'classer') print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=classer&amp;propalid='.$propal->id.'">'.img_edit($langs->trans('SetProject')).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="3">';
			if ($_GET['action'] == 'classer')
			{
				$html->form_project($_SERVER['PHP_SELF'].'?propalid='.$propal->id, $propal->socid, $propal->projetidp, 'projetidp');
			}
			else
			{
				$html->form_project($_SERVER['PHP_SELF'].'?propalid='.$propal->id, $propal->socid, $propal->projetidp, 'none');
			}
			print '</td></tr>';
		}
		else
		{
			print '</td></tr></table>';
			if (!empty($propal->projetidp))
			{
				print '<td colspan="3">';
				$proj = new Project($db);
				$proj->fetch($propal->projetidp);
				print '<a href="../projet/fiche.php?id='.$propal->projetidp.'" title="'.$langs->trans('ShowProject').'">';
				print $proj->ref;
				print '</a>';
				print '</td>';
			}
			else {
				print '<td colspan="3">&nbsp;</td>';
			}
		}
		print '</tr>';
	}

	// Amount
	print '<tr><td height="10">'.$langs->trans('AmountHT').'</td>';
	print '<td align="right" colspan="2"><b>'.price($propal->total_ht).'</b></td>';
	print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

	print '<tr><td height="10">'.$langs->trans('AmountVAT').'</td><td align="right" colspan="2">'.price($propal->total_tva).'</td>';
	print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
	print '<tr><td height="10">'.$langs->trans('AmountTTC').'</td><td align="right" colspan="2">'.price($propal->total_ttc).'</td>';
	print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';


	// Statut
	print '<tr><td height="10">'.$langs->trans('Status').'</td><td align="left" colspan="3">'.$propal->getLibStatut(4).'</td></tr>';
	print '</table><br>';

	/*
	 * Lines
	 */
	print '<table class="noborder" width="100%">';

	$sql = 'SELECT pt.rowid, pt.description, pt.price, pt.fk_product, pt.fk_remise_except,';
	$sql.= ' pt.qty, pt.tva_tx, pt.remise_percent, pt.subprice, pt.info_bits,';
	$sql.= ' pt.total_ht, pt.total_tva, pt.total_ttc,';
	$sql.= ' pt.product_type,';
	$sql.= ' p.rowid as prodid, p.label as product_label, p.ref, p.fk_product_type, ';
	$sql.= ' p.description as product_desc';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'propaldet as pt';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pt.fk_product=p.rowid';
	$sql.= ' WHERE pt.fk_propal = '.$propal->id;
	$sql.= ' ORDER BY pt.rang ASC, pt.rowid';
	$resql = $db->query($sql);
	if ($resql)
	{
		$num_lignes = $db->num_rows($resql);
		$i = 0;
		$total = 0;

		if ($num_lignes)
		{
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans('Description').'</td>';
			print '<td align="right" width="50">'.$langs->trans('VAT').'</td>';
			print '<td align="right" width="80">'.$langs->trans('PriceUHT').'</td>';
			print '<td align="right" width="50">'.$langs->trans('Qty').'</td>';
			print '<td align="right" width="50">'.$langs->trans('ReductionShort').'</td>';
			print '<td align="right" nowrap="nowrap">'.$langs->trans('AmountHT').'</td>';
			print '<td width="16">&nbsp;</td>';
			print '<td width="16">&nbsp;</td>';
			print '<td width="16">&nbsp;</td>';
			print "</tr>\n";
		}
		$var=true;
		while ($i < $num_lignes)
		{
			$objp = $db->fetch_object($resql);
			$var=!$var;

			// Show product and description
			$type=$objp->product_type?$objp->product_type:$objp->fk_product_type;
			// Try to enhance type detection using date_start and date_end for free lines where type
			// was not saved.
			if (! empty($objp->date_start)) $type=1;
			if (! empty($objp->date_end)) $type=1;

			if ($_GET['action'] != 'editline' || $_GET['rowid'] != $objp->rowid)
			{
				print '<tr '.$bc[$var].'>';
				if ($objp->fk_product > 0)
				{
					print '<td>';
					print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne;

					// Show product and description
					$product_static->type=$objp->fk_product_type;
					$product_static->id=$objp->fk_product;
					$product_static->ref=$objp->ref;
					$product_static->libelle=$objp->product_label;
					$text=$product_static->getNomUrl(1);
					$text.= ' - '.$objp->product_label;
					$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($objp->description));
					print $html->textwithtooltip($text,$description,3,'','',$i);

					// Show range
					print_date_range($objp->date_start,$objp->date_end);

					// Add description in form
					if ($conf->global->PRODUIT_DESC_IN_FORM)
					{
						print ($objp->description && $objp->description!=$objp->product_label)?'<br>'.dol_htmlentitiesbr($objp->description):'';
					}
					print '</td>';
				}
				else
				{
					print '<td>';
					print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
					if (($objp->info_bits & 2) == 2)
					{
						print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$propal->socid.'">';
						print img_object($langs->trans("ShowReduc"),'reduc').' '.$langs->trans("Discount");
						print '</a>';
						if ($objp->description)
						{
							if ($objp->description == '(CREDIT_NOTE)')
							{
								$discount=new DiscountAbsolute($db);
								$discount->fetch($objp->fk_remise_except);
								print ' - '.$langs->transnoentities("DiscountFromCreditNote",$discount->getNomUrl(0));
							}
							else
							{
								print ' - '.nl2br($objp->description);
							}
						}
					}
					else
					{
						if ($type==1) $text = img_object($langs->trans('Service'),'service');
						else $text = img_object($langs->trans('Product'),'product');
						print $text.' '.nl2br($objp->description);

						// Show range
						print_date_range($objp->date_start,$objp->date_end);
					}
					print "</td>\n";
				}
				print '<td align="right">'.vatrate($objp->tva_tx).'%</td>';
				print '<td align="right">'.price($objp->subprice)."</td>\n";

				// Qty
				print '<td align="right">';
				if (($objp->info_bits & 2) != 2)
				{
					print $objp->qty;
				}
				else print '&nbsp;';
				print '</td>';

				if ($objp->remise_percent > 0)
				{
					print '<td align="right">'.$objp->remise_percent."%</td>\n";
				}
				else
				{
					print '<td>&nbsp;</td>';
				}
				print '<td align="right">'.price($objp->total_ht)."</td>\n";

				print '<td colspan="3">&nbsp;</td>';

				print '</tr>';
			}

			$i++;
		}
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}

	print '</table>';


	print '</div>';


	/*
	 * Boutons Actions
	 */
	print '<div class="tabsAction">';

	if ($propal->statut <> 4 && $user->societe_id == 0)
	{
		if ($propal->statut == 2 && $user->rights->facture->creer)
		{
			print '<a class="butAction" href="facture.php?propalid='.$propal->id."&action=create&socid=$socid&viewstatut=$viewstatut&sortfield=$sortfield&$sortorder\">".$langs->trans("BuildBill")."</a>";
		}

		$arraypropal=$propal->getInvoiceArrayList();
		if ($propal->statut == 2 && is_array($arraypropal) && sizeof($arraypropal) > 0)
		{
			print '<a class="butAction" href="propal.php?propalid='.$propal->id."&action=setstatut&statut=4&socid=$socid&viewstatut=$viewstatut&sortfield=$sortfield&$sortorder\">".$langs->trans("ClassifyBilled")."</a>";
		}
	}
	print "</div>";
	print "<br>\n";



	print '<table width="100%"><tr><td width="50%" valign="top">';

	/*
	 * Documents generes
	 */
	$filename=dol_sanitizeFileName($propal->ref);
	$filedir=$conf->propale->dir_output . "/" . dol_sanitizeFileName($propal->ref);
	$urlsource=$_SERVER["PHP_SELF"]."?propalid=".$propal->id;
	$genallowed=0;
	$delallowed=0;

	$var=true;

	$somethingshown=$formfile->show_documents('propal',$filename,$filedir,$urlsource,$genallowed,$delallowed);


	/*
	 * Commandes rattachees
	 */
	if($conf->commande->enabled)
	{
		$propal->loadOrders();
		$coms = $propal->commandes;
		if (sizeof($coms) > 0)
		{
			$total=0;
			if ($somethingshown) { print '<br>'; $somethingshown=1; }
			print_titre($langs->trans('RelatedOrders'));
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Ref").'</td>';
			print '<td align="center">'.$langs->trans("Date").'</td>';
			print '<td align="right">'.$langs->trans("Price").'</td>';
			print '<td align="right">'.$langs->trans("Status").'</td>';
			print '</tr>';
			$var=true;
			for ($i = 0 ; $i < sizeof($coms) ; $i++)
			{
				$var=!$var;
				print '<tr '.$bc[$var].'><td>';
				print '<a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$coms[$i]->id.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$coms[$i]->ref."</a></td>\n";
				print '<td align="center">'.dol_print_date($coms[$i]->date,'day').'</td>';
				print '<td align="right">'.price($coms[$i]->total_ttc).'</td>';
				print '<td align="right">'.$coms[$i]->getLibStatut(3).'</td>';
				print "</tr>\n";
				$total = $total + $objp->total;
			}
			print '</table>';
		}
	}


	/*
	 * Factures associees
	 */
	// Cas des factures lies directement
	$sql = "SELECT f.facnumber, f.total,".$db->pdate("f.datef")." as df, f.rowid as facid, f.fk_user_author, f.fk_statut, f.paye";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql.= ", ".MAIN_DB_PREFIX."fa_pr as fp";
	//$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	$sql.= " WHERE fp.fk_facture = f.rowid";
	$sql.= " AND fp.fk_propal = ".$propal->id;
	//$sql.= " AND f.fk_soc = s.rowid";
	//$sql.= " AND s.entity = ".$conf->entity;
	//$sql.= " UNION ";
	// Cas des factures lier via la commande
	$sql2= "SELECT f.facnumber, f.total,".$db->pdate("f.datef")." as df, f.rowid as facid, f.fk_user_author, f.fk_statut, f.paye";
	$sql2.= " FROM ".MAIN_DB_PREFIX."facture as f";
	//$sql2.= ", ".MAIN_DB_PREFIX."societe as s";
	$sql2.= ", ".MAIN_DB_PREFIX."co_pr as cp";
	$sql2.= ", ".MAIN_DB_PREFIX."co_fa as cf";
	$sql2.= " WHERE cp.fk_propale = ".$propal->id;
	$sql2.= " AND cf.fk_commande = cp.fk_commande";
	$sql2.= " AND cf.fk_facture = f.rowid";
	//$sql2.= " AND f.fk_soc = s.rowid";
	//$sql2.= " AND s.entity = ".$conf->entity;

	dol_syslog("propal.php::liste factures sql=".$sql);
	$resql=$db->query($sql);
	$resql2=null;
	if ($resql)
	{
		dol_syslog("propal.php::liste factures sql2=".$sql2);
		$resql2=$db->query($sql2);
	}
	if ($resql2)
	{
		$tab_sqlobj=array();

		$num_fac_asso = $db->num_rows($resql);
		for ($i = 0;$i < $num_fac_asso;$i++)
		{
			$sqlobj = $db->fetch_object($resql);
			$tab_sqlobj[] = $sqlobj;
			//$tab_sqlobjOrder[]= $sqlobj->dc;
		}
		$db->free($resql);

		$num_fac_asso = $db->num_rows($resql2);
		for ($i = 0;$i < $num_fac_asso;$i++)
		{
			$sqlobj = $db->fetch_object($resql2);
			$tab_sqlobj[] = $sqlobj;
			//$tab_sqlobjOrder[]= $sqlobj->dc;
		}
		$db->free($resql2);

		//array_multisort ($tab_sqlobjOrder,$tab_sqlobj);

		$num_fac_asso = sizeOf($tab_sqlobj);
		//$num_fac_asso = $db->num_rows($resql);
		$i = 0; $total = 0;
		if ($somethingshown) { print '<br>'; $somethingshown=1; }
		if ($num_fac_asso > 1) print_titre($langs->trans("RelatedBills"));
		else print_titre($langs->trans("RelatedBill"));
		print '<table class="noborder" width="100%">';
		print "<tr class=\"liste_titre\">";
		print '<td>'.$langs->trans("Ref").'</td>';
		print '<td align="center">'.$langs->trans("Date").'</td>';
		print '<td align="right">'.$langs->trans("Price").'</td>';
		print '<td align="right">'.$langs->trans("Status").'</td>';
		print "</tr>\n";

		require_once(DOL_DOCUMENT_ROOT.'/facture.class.php');
		$staticfacture=new Facture($db);

		$var=True;
		while ($i < $num_fac_asso)
		{
			//$objp = $db->fetch_object($resql);
			$objp = array_shift($tab_sqlobj);
			$var=!$var;
			print "<tr $bc[$var]>";
			print '<td><a href="../compta/facture.php?facid='.$objp->facid.'">'.img_object($langs->trans("ShowBill"),"bill").' '.$objp->facnumber.'</a></td>';
			print '<td align="center">'.dol_print_date($objp->df,'day').'</td>';
			print '<td align="right">'.price($objp->total).'</td>';
			print '<td align="right">'.$staticfacture->LibStatut($objp->paye,$objp->fk_statut,3).'</td>';
			print "</tr>";
			$total = $total + $objp->total;
			$i++;
		}
		print "<tr class=\"liste_total\"><td align=\"right\" colspan=\"2\">".$langs->trans("TotalHT")."</td>";
		print "<td align=\"right\">".price($total)."</td>";
		print "<td>&nbsp;</td></tr>\n";
		print "</table>";
		//$db->free();
	}


	print '</td><td valign="top" width="50%">';

	// List of actions on element
	include_once(DOL_DOCUMENT_ROOT.'/html.formactions.class.php');
	$formactions=new FormActions($db);
	$somethingshown=$formactions->showactions($propal,'propal',$socid);

	print '</td></tr></table>';


}
else
{
	/**
	 *
	 * Mode Liste des propales
	 *
	 */

	$now=gmmktime();

	$limit = $conf->liste_limit;
	$offset = $limit * $page ;
	$pageprev = $page - 1;
	$pagenext = $page + 1;

	$year = $_REQUEST["year"];
	$month = $_REQUEST["month"];

	$sql = "SELECT s.nom, s.rowid as socid, s.client,";
	$sql.= " p.rowid as propalid, p.ref, p.fk_statut,";
	$sql.= " p.total_ht, p.tva, p.total,";
	$sql.= $db->pdate("p.datep")." as dp, ";
	$sql.= $db->pdate("p.fin_validite")." as dfin";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql.= ", ".MAIN_DB_PREFIX."propal as p";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE p.fk_soc = s.rowid";
	$sql.= " AND s.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND s.rowid = ".$socid;
	if ($viewstatut <> '') $sql.= " AND p.fk_statut in ($viewstatut)"; // viewstatut peut etre combinaisons separe par virgules
	if ($month > 0)
	{
		if ($year > 0)
		$sql.= " AND date_format(p.datep, '%Y-%m') = '$year-$month'";
		else
		$sql.= " AND date_format(p.datep, '%m') = '$month'";
	}
	if ($year > 0)         $sql .= " AND date_format(p.datep, '%Y') = $year";
	if (!empty($_GET['search_ref']))
	{
		$sql.= " AND p.ref LIKE '%".addslashes($_GET['search_ref'])."%'";
	}
	if (!empty($_GET['search_societe']))
	{
		$sql.= " AND s.nom LIKE '%".addslashes($_GET['search_societe'])."%'";
	}
	if (!empty($_GET['search_montant_ht']))
	{
		$sql.= " AND p.price='".addslashes($_GET['search_montant_ht'])."'";
	}
	$sql.= " ORDER BY $sortfield $sortorder, p.rowid DESC ";
	$sql.= $db->plimit($limit + 1,$offset);

	if ( $result = $db->query($sql) )
	{
		$num = $db->num_rows();


		print_barre_liste($langs->trans("Proposals"), $page, "propal.php","&socid=$socid&month=$month&year=$year&search_ref=$search_ref&search_societe=$search_societe&search_montant_ht=$search_montant_ht".'&amp;viewstatut='.$viewstatut,$sortfield,$sortorder,'',$num);

		$i = 0;
		$var=true;

		print "<table class=\"noborder\" width=\"100%\">";
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("Ref"),"propal.php","p.ref","","&year=$year&viewstatut=$viewstatut",'width=20%',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Company"),"propal.php","s.nom","&viewstatut=$viewstatut","",'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Date"),"propal.php","p.datep","&viewstatut=$viewstatut","",'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("AmountHT"),"propal.php","p.price","&viewstatut=$viewstatut","",'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Status"),"propal.php","p.fk_statut","&viewstatut=$viewstatut","",'align="right"',$sortfield,$sortorder);
		print '<td class="liste_titre">&nbsp;</td>';
		print "</tr>\n";

		// Lignes des champs de filtre
		print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';

		print '<tr class="liste_titre">';
		print '<td class="liste_titre" valign="right">';
		print '<input class="flat" size="10" type="text" name="search_ref" value="'.$_GET['search_ref'].'">';
		print '</td>';
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" size="40" name="search_societe" value="'.$_GET['search_societe'].'">';
		print '</td>';
		print '<td class="liste_titre" colspan="1" align="right">';
		print $langs->trans('Month').': <input class="flat" type="text" size="2" maxlength="2" name="month" value="'.$month.'">';
		print '&nbsp;'.$langs->trans('Year').': ';
		$max_year = date("Y");
		$syear = $year;
		if($syear == '')
		$syear = date("Y");
		$html->select_year($syear,'year',1, '', $max_year);
		print '</td>';
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$_GET['search_montant_ht'].'">';
		print '</td>';
		print '<td class="liste_titre" align="right">';
		$html->select_propal_statut($viewstatut);
		print '</td>';
		print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
		print '</td>';
		print "</tr>\n";
		print '</form>';

		while ($i < min($num, $limit))
		{
			$objp = $db->fetch_object($result);

			$var=!$var;
			print "<tr $bc[$var]>";
			print '<td nowrap="nowrap">';

			$propalstatic->id=$objp->propalid;
			$propalstatic->ref=$objp->ref;

			//Ref
			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td width="90" class="nobordernopadding" nowrap="nowrap">';
			print $propalstatic->getNomUrl(1, 'compta', "&socid=$socid&viewstatut=$viewstatut&sortfield=$sortfield&$sortorder");
			print '</td>';

			print '<td width="20" class="nobordernopadding" nowrap="nowrap">';
			if ($objp->fk_statut == 1 && $objp->dfin < ($now - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
			print '</td>';

			print '<td width="16" align="right" class="nobordernopadding">';

			$filename=dol_sanitizeFileName($objp->ref);
			$filedir=$conf->propale->dir_output . '/' . dol_sanitizeFileName($objp->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?propalid='.$objp->propalid;
			$formfile->show_documents('propal',$filename,$filedir,$urlsource,'','','','','',1);

			print '</td></tr></table>';

			print "</td>\n";

			// Societe
			print "<td>";
			$societestatic->nom=$objp->nom;
			$societestatic->id=$objp->socid;
			$societestatic->client=$objp->client;
			print $societestatic->getNomUrl(1,'customer',44);
			print "</td>";

			// Date
			print "<td align=\"right\">";
			$y = dol_print_date($objp->dp,"%Y");
			$m = dol_print_date($objp->dp,"%m");
			$mt = dol_print_date($objp->dp,"%b");
			$d = dol_print_date($objp->dp,"%d");
			print $d."\n";
			print " <a href=\"propal.php?year=$y&month=$m\">";
			print $mt."</a>\n";
			print " <a href=\"propal.php?year=$y\">";
			print $y."</a></td>\n";

			// Prix
			print "<td align=\"right\">".price($objp->total_ht)."</td>\n";
			print "<td align=\"right\">".$propalstatic->LibStatut($objp->fk_statut,5)."</td>\n";
			print "<td>&nbsp;</td>";
			print "</tr>\n";

			$i++;
		}

		print "</table>";
		$db->free($result);
	}
	else
	{
		dol_print_error($db);
	}
}
$db->close();


llxFooter('$Date$ - $Revision$');

?>
