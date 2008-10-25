<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \file       htdocs/compta/commande/fiche.php
 \ingroup    commande
 \brief      Fiche commande
 \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/order.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/sendings.lib.php");
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT."/project.class.php");

$langs->load("orders");
$langs->load("companies");
$langs->load("bills");
$langs->load('deliveries');
$langs->load('sendings');

if (! $user->rights->commande->lire) accessforbidden();

// Sécurité accès client
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}


/*
 *	Actions
 */

if ($_GET["action"] == 'facturee')
{
	$commande = new Commande($db);
	$commande->fetch($_GET["id"]);
	$commande->classer_facturee();
}

// Positionne ref commande client
if ($_POST['action'] == 'set_ref_client' && $user->rights->commande->creer)
{
	$commande = new Commande($db);
	$commande->fetch($_GET['id']);
	$commande->set_ref_client($user, $_POST['ref_client']);
}

if ($_POST['action'] == 'setdate_livraison' && $user->rights->commande->creer)
{
	//print "x ".$_POST['liv_month'].", ".$_POST['liv_day'].", ".$_POST['liv_year'];
	$datelivraison=dolibarr_mktime(0, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']);

	$commande = new Commande($db);
	$commande->fetch($_GET['id']);
	$result=$commande->set_date_livraison($user,$datelivraison);
	if ($result < 0)
	{
		$mesg='<div class="error">'.$commande->error.'</div>';
	}
}

if ($_POST['action'] == 'setdeliveryadress' && $user->rights->commande->creer)
{
	$commande = new Commande($db);
	$commande->fetch($_GET['id']);
	$commande->set_adresse_livraison($user,$_POST['adresse_livraison_id']);
}

if ($_POST['action'] == 'setmode' && $user->rights->commande->creer)
{
	$commande = new Commande($db);
	$commande->fetch($_GET['id']);
	$result=$commande->mode_reglement($_POST['mode_reglement_id']);
	if ($result < 0) dolibarr_print_error($db,$commande->error);
}

if ($_POST['action'] == 'setconditions' && $user->rights->commande->creer)
{
	$commande = new Commande($db);
	$commande->fetch($_GET['id']);
	$result=$commande->cond_reglement($_POST['cond_reglement_id']);
	if ($result < 0) dolibarr_print_error($db,$commande->error);
}


llxHeader('',$langs->trans("OrderCard"),"Commande");


$html = new Form($db);
$formfile = new FormFile($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

if ($_GET["id"] > 0)
{
	$commande = new Commande($db);
	if ( $commande->fetch($_GET["id"]) > 0)
	{
		$soc = new Societe($db);
		$soc->fetch($commande->socid);

		$author = new User($db);
		$author->id = $commande->user_author_id;
		$author->fetch();

		$head = commande_prepare_head($commande);
		dolibarr_fiche_head($head, 'accountancy', $langs->trans("CustomerOrder"));

		/*
		 *   Commande
		 */
		$nbrow=8;
		if ($conf->projet->enabled) $nbrow++;

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="18%">'.$langs->trans('Ref').'</td>';
		print '<td colspan="3">'.$commande->ref.'</td>';
		print '</tr>';

		// Ref commande client
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
		print $langs->trans('RefCustomer').'</td><td align="left">';
		print '</td>';
		if ($_GET['action'] != 'RefCustomerOrder' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=RefCustomerOrder&amp;id='.$commande->id.'">'.img_edit($langs->trans('Modify')).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		if ($user->rights->commande->creer && $_GET['action'] == 'RefCustomerOrder')
		{
			print '<form action="fiche.php?id='.$id.'" method="post">';
			print '<input type="hidden" name="action" value="set_ref_client">';
			print '<input type="text" class="flat" size="20" name="ref_client" value="'.$commande->ref_client.'">';
			print ' <input type="submit" class="button" value="'.$langs->trans('Modify').'">';
			print '</form>';
		}
		else
		{
			print $commande->ref_client;
		}
		print '</td>';
		print '</tr>';


		// Third party
		print '<tr><td>'.$langs->trans('Company').'</td>';
		print '<td colspan="3">'.$soc->getNomUrl(1,'compta').'</td>';
		print '</tr>';

		// Discounts for third party
		print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="3">';
		if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
		else print $langs->trans("CompanyHasNoRelativeDiscount");
		$absolute_discount=$soc->getAvailableDiscounts('','fk_facture_source IS NULL');
		$absolute_creditnote=$soc->getAvailableDiscounts('','fk_facture_source IS NOT NULL');
		print '. ';
		if ($absolute_discount)
		{
			if ($commande->statut > 0)
			{
				print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->monnaie));
			}
			else
			{
				// Remise dispo de type non avoir
				$filter='fk_facture_source IS NULL';
				print '<br>';
				$html->form_remise_dispo($_SERVER["PHP_SELF"].'?id='.$commande->id,0,'remise_id',$soc->id,$absolute_discount,$filter);
			}
		}
		if ($absolute_creditnote)
		{
			print $langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->monnaie)).'. ';
		}
		if (! $absolute_discount && ! $absolute_creditnote) print $langs->trans("CompanyHasNoAbsoluteDiscount").'.';
		print '</td></tr>';

		// Date
		print '<tr><td>'.$langs->trans('Date').'</td>';
		print '<td colspan="2">'.dolibarr_print_date($commande->date,'daytext').'</td>';
		print '<td width="50%">'.$langs->trans('Source').' : '.$commande->getLabelSource();
		if ($commande->source == 0)
		{
			// Si source = propal
			$propal = new Propal($db);
			$propal->fetch($commande->propale_id);
			print ' -> <a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id.'">'.$propal->ref.'</a>';
		}
		print '</td>';
		print '</tr>';

		// Date de livraison
		print '<tr><td height="10">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('DeliveryDate');
		print '</td>';

		if (1 == 2 && $_GET['action'] != 'editdate_livraison' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetDeliveryDate'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="2">';
		if ($_GET['action'] == 'editdate_livraison')
		{
			print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'" method="post">';
			print '<input type="hidden" name="action" value="setdate_livraison">';
			$html->select_date($commande->date_livraison,'liv_','','','',"setdate_livraison");
			print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
			print '</form>';
		}
		else
		{
			print dolibarr_print_date($commande->date_livraison,'daytext');
		}
		print '</td>';
		print '<td rowspan="'.$nbrow.'" valign="top">'.$langs->trans('NotePublic').' :<br>';
		print nl2br($commande->note_public);
		print '</td>';
		print '</tr>';


		// Adresse de livraison
		print '<tr><td height="10">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('DeliveryAddress');
		print '</td>';

		if (1 == 2 && $_GET['action'] != 'editdelivery_adress' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdelivery_adress&amp;socid='.$commande->socid.'&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetDeliveryAddress'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="2">';

		if ($_GET['action'] == 'editdelivery_adress')
		{
			$html->form_adresse_livraison($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->adresse_livraison_id,$_GET['socid'],'adresse_livraison_id','commande',$commande->id);
		}
		else
		{
			$html->form_adresse_livraison($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->adresse_livraison_id,$_GET['socid'],'none','commande',$commande->id);
		}
		print '</td></tr>';

		// Conditions et modes de règlement
		print '<tr><td height="10">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('PaymentConditionsShort');
		print '</td>';

		if ($_GET['action'] != 'editconditions' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="2">';
		if ($_GET['action'] == 'editconditions')
		{
			$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->cond_reglement_id,'cond_reglement_id');
		}
		else
		{
			$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->cond_reglement_id,'none');
		}
		print '</td></tr>';
		print '<tr><td height="10">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('PaymentMode');
		print '</td>';
		if ($_GET['action'] != 'editmode' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="2">';
		if ($_GET['action'] == 'editmode')
		{
			$html->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->mode_reglement_id,'mode_reglement_id');
		}
		else
		{
			$html->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->mode_reglement_id,'none');
		}
		print '</td></tr>';

		// Projet
		if ($conf->projet->enabled)
		{
			$langs->load('projects');
			print '<tr><td height="10">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('Project');
			print '</td>';
			if ($_GET['action'] != 'classer' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=classer&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetProject')).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="2">';
			if ($_GET['action'] == 'classer')
			{
				$html->form_project($_SERVER['PHP_SELF'].'?id='.$commande->id, $commande->socid, $commande->projet_id, 'projetid');
			}
			else
			{
				$html->form_project($_SERVER['PHP_SELF'].'?id='.$commande->id, $commande->socid, $commande->projet_id, 'none');
			}
			print '</td></tr>';
		}

		// Lignes de 3 colonnes

		// Total HT
		print '<tr><td>'.$langs->trans('AmountHT').'</td>';
		print '<td align="right"><b>'.price($commande->total_ht).'</b></td>';
		print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

		// Total TVA
		print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right">'.price($commande->total_tva).'</td>';
		print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

		// Total TTC
		print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right">'.price($commande->total_ttc).'</td>';
		print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

		// Statut
		print '<tr><td>'.$langs->trans('Status').'</td>';
		print '<td colspan="2">'.$commande->getLibStatut(4).'</td>';
		print '</tr>';

		print '</table>';

		/*
		 * Lignes de commandes
		 */
		$sql = 'SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_tx, l.fk_remise_except, l.remise_percent, l.subprice,';
		$sql.= ' l.info_bits, l.total_ht, l.total_tva, l.total_ttc,';
		$sql.= ' p.label as product, p.ref, p.fk_product_type, p.rowid as prodid,';
		$sql.= ' p.description as product_desc';
		$sql.= ' FROM '.MAIN_DB_PREFIX."commandedet as l";
		$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product=p.rowid';
		$sql.= " WHERE l.fk_commande = ".$commande->id;
		$sql.= " ORDER BY l.rang, l.rowid";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0; $total = 0;

			if ($num) print '<br>';
			print '<table class="noborder" width="100%">';
			if ($num)
			{
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans('Description').'</td>';
				print '<td align="right" width="50">'.$langs->trans('VAT').'</td>';
				print '<td align="right" width="80">'.$langs->trans('PriceUHT').'</td>';
				print '<td align="right" width="50">'.$langs->trans('Qty').'</td>';
				print '<td align="right" width="50">'.$langs->trans('ReductionShort').'</td>';
				print '<td align="right" width="50">'.$langs->trans('AmountHT').'</td>';
				print '<td width="48" colspan="3">&nbsp;</td>';
				print "</tr>\n";
			}

			$var=true;
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);

				$var=!$var;
				print '<tr '.$bc[$var].'>';
				if ($objp->fk_product > 0)
				{
					print '<td>';
					print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

					// Affiche ligne produit
					$text = '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
					if ($objp->fk_product_type==1) $text.= img_object($langs->trans('ShowService'),'service');
					else $text.= img_object($langs->trans('ShowProduct'),'product');
					$text.= ' '.$objp->ref.'</a>';
					$text.= ' - '.$objp->product;
					$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($objp->description));
					print $html->textwithtooltip($text,$description,3,'','',$i);
					// Print the start and end dates
					print_date_range($objp->date_start,$objp->date_end);
					if ($conf->global->PRODUIT_DESC_IN_FORM)
					{
						print ($objp->description && $objp->description!=$objp->product)?'<br>'.dol_htmlentitiesbr($objp->description):'';
					}
					
					print '</td>';
				}
				else
				{
					print '<td>';
					if (($objp->info_bits & 2) == 2)
					{
						print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$commande->socid.'">';
						print img_object($langs->trans("ShowReduc"),'reduc').' '.$langs->trans("Discount");
						print '</a>';
						if ($objp->description)
						{
							if ($objp->description == '(CREDIT_NOTE)')
							{
								require_once(DOL_DOCUMENT_ROOT.'/discount.class.php');
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
						print nl2br($objp->description);
					}
					print "</td>\n";
				}
				print '<td align="right" nowrap="nowrap">'.vatrate($objp->tva_tx).'%</td>';

				print '<td align="right" nowrap="nowrap">'.price($objp->subprice)."</td>\n";

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

				print '<td align="right" nowrap="nowrap">'.price($objp->total_ht)."</td>\n";

				print '<td colspan="3">&nbsp;</td>';
				print '</tr>';

				$total = $total + ($objp->qty * $objp->price);
				$i++;
			}
			$db->free($resql);
		}
		else
		{
			dolibarr_print_error($db);
		}

		print '</table>';

		print '</div>';


		/*
		 * Boutons actions
		 */

		if (! $user->societe_id && ! $commande->facturee)
		{
			print "<div class=\"tabsAction\">\n";

			if ($commande->statut > 0 && $user->rights->facture->creer)
			{
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;commandeid='.$commande->id.'&amp;socid='.$commande->socid.'">'.$langs->trans("CreateBill").'</a>';
			}

			if ($commande->statut > 0 && $user->rights->commande->creer)
			{
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/commande/fiche.php?action=facturee&amp;id='.$commande->id.'">'.$langs->trans("ClassifyBilled").'</a>';
			}
			print '</div>';
		}


		print '<table width="100%"><tr><td width="50%" valign="top">';


		/*
		 * Documents générés
		 *
		 */
		$comref = sanitize_string($commande->ref);
		$file = $conf->commande->dir_output . '/' . $comref . '/' . $comref . '.pdf';
		$relativepath = $comref.'/'.$comref.'.pdf';
		$filedir = $conf->commande->dir_output . '/' . $comref;
		$urlsource=$_SERVER["PHP_SELF"]."?id=".$commande->id;
		$genallowed=0;
		$delallowed=0;

		$somethingshown=$formfile->show_documents('commande',$comref,$filedir,$urlsource,$genallowed,$delallowed,$commande->modelpdf);

		/*
		 * Liste des factures
		 */
		$sql = "SELECT f.rowid,f.facnumber, f.total_ttc, ".$db->pdate("f.datef")." as df";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."co_fa as cf";
		$sql .= " WHERE f.rowid = cf.fk_facture AND cf.fk_commande = ". $commande->id;

		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			if ($num)
			{
				print '<br>';
				print_titre($langs->trans("RelatedBills"));
				$i = 0; $total = 0;
				print '<table class="noborder" width="100%">';
				print '<tr class="liste_titre"><td>'.$langs->trans("Ref")."</td>";
				print '<td align="center">'.$langs->trans("Date").'</td>';
				print '<td align="right">'.$langs->trans("Price").'</td>';
				print "</tr>\n";

				$var=True;
				while ($i < $num)
				{
					$objp = $db->fetch_object($result);
					$var=!$var;
					print "<tr $bc[$var]>";
					print '<td><a href="../facture.php?facid='.$objp->rowid.'">'.img_object($langs->trans("ShowBill"),"bill").' '.$objp->facnumber.'</a></td>';
					print '<td align="center">'.dolibarr_print_date($objp->df).'</td>';
					print '<td align="right">'.$objp->total_ttc.'</td></tr>';
					$i++;
				}
				print "</table>";
			}
		}
		else
		{
			dolibarr_print_error($db);
		}

		print '</td><td valign="top" width="50%">';

		// List of actions on element
		include_once(DOL_DOCUMENT_ROOT.'/html.formactions.class.php');
		$formactions=new FormActions($db);
		$somethingshown=$formactions->showactions($commande,'order',$socid);

		print "</td></tr></table>";


		show_list_sending_receive('commande',$commande->id);
	}
	else
	{
		// Commande non trouvée
		print "Commande inexistante";
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
