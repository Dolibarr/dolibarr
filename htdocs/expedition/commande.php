<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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

// Code identique a /expedition/fiche.php

/**
 \file       htdocs/expedition/commande.php
 \ingroup    expedition
 \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/stock/entrepot.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/order.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/sendings.lib.php");

$langs->load('orders');
$langs->load("companies");
$langs->load("bills");
$langs->load('propal');
$langs->load('deliveries');
$langs->load('stocks');

if (!$user->rights->commande->lire)
accessforbidden();

// S�curit� acc�s client
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}

// Chargement des permissions
$error = $user->load_entrepots();

/*
 * Actions
 */
if ($_POST["action"] == 'confirm_cloture' && $_POST["confirm"] == 'yes')
{
	$commande = new Commande($db);
	$commande->fetch($_GET["id"]);
	$result = $commande->cloture($user);
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
	$datelivraison=dol_mktime(0, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']);

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
	if ($result < 0) dol_print_error($db,$commande->error);
}

if ($_POST['action'] == 'setconditions' && $user->rights->commande->creer)
{
	$commande = new Commande($db);
	$commande->fetch($_GET['id']);
	$result=$commande->cond_reglement($_POST['cond_reglement_id']);
	if ($result < 0) dol_print_error($db,$commande->error);
}


$html = new Form($db);
$formfile = new FormFile($db);


/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

llxHeader('',$langs->trans("OrderCard"));

$id = $_GET['id'];
$ref= $_GET['ref'];
if ($id > 0 || ! empty($ref))
{
	$commande = new Commande($db);
	if ( $commande->fetch($_GET['id'],$_GET['ref']) > 0)
	{
		$commande->loadExpeditions(1);

		$soc = new Societe($db);
		$soc->fetch($commande->socid);

		$author = new User($db);
		$author->id = $commande->user_author_id;
		$author->fetch();

		$head = commande_prepare_head($commande);
		dol_fiche_head($head, 'shipping', $langs->trans("CustomerOrder"));

		/*
		 * Confirmation de la validation
		 *
		 */
		if ($_GET["action"] == 'cloture')
		{
			$html->form_confirm("commande.php?id=".$_GET["id"],"Cl�turer la commande","Etes-vous s�r de vouloir cl�turer cette commande ?","confirm_cloture");
			print "<br />";
		}

		// Onglet commande
		$nbrow=7;
		if ($conf->projet->enabled) $nbrow++;

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="18%">'.$langs->trans('Ref').'</td>';
		print '<td colspan="3">';
		print $html->showrefnav($commande,'ref','',1,'ref','ref');
		print '</td>';
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
		print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
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
		print '<td colspan="2">'.dol_print_date($commande->date,'daytext').'</td>';
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

		if ($_GET['action'] != 'editdate_livraison' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetDeliveryDate'),1).'</a></td>';
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
			print dol_print_date($commande->date_livraison,'daytext');
		}
		print '</td>';
		print '<td rowspan="'.$nbrow.'" valign="top">'.$langs->trans('NotePublic').' :<br>';
		print nl2br($commande->note_public);
		print '</td>';
		print '</tr>';

		if ($conf->global->PROPALE_ADD_DELIVERY_ADDRESS)
		{
			// Adresse de livraison
			print '<tr><td height="10">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('DeliveryAddress');
			print '</td>';
	
			if ($_GET['action'] != 'editdelivery_adress' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdelivery_adress&amp;socid='.$commande->socid.'&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetDeliveryAddress'),1).'</a></td>';
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
		}
		
		// Conditions et modes de r�glement
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

		print '</table><br>';


		/**
		 *  Lignes de commandes avec quantit� livr�es et reste � livrer
		 *  Les quantit�s livr�es sont stock�es dans $commande->expeditions[fk_product]
		 */
		print '<table class="liste" width="100%">';

		$sql = "SELECT cd.rowid, cd.fk_product, cd.description, cd.price, cd.tva_tx, cd.subprice,";
		$sql.= " qty";
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
		$sql.= " WHERE cd.fk_commande = ".$commande->id;
		// $sql.= " AND p.fk_product_type <> 1";		Why this line ?
		$sql.= " GROUP by cd.rowid, cd.fk_product";
		$sql.= " ORDER BY cd.rowid";

		//print $sql;
		dol_syslog("commande.php sql=".$sql, LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;

			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Description").'</td>';
			print '<td align="center">'.$langs->trans("QtyOrdered").'</td>';
			print '<td align="center">'.$langs->trans("QtyShipped").'</td>';
			print '<td align="center">'.$langs->trans("KeepToShip").'</td>';
			if ($conf->stock->enabled)
			{
				print '<td align="center">'.$langs->trans("Stock").'</td>';
			}
			else
			{
				print '<td>&nbsp;</td>';
			}
			print "</tr>\n";

			$var=true;
			$reste_a_livrer = array();
			while ($i < $num)
			{
				$product = new Product($db);

				$objp = $db->fetch_object($resql);

				$var=!$var;
				print "<tr ".$bc[$var].">";
				if ($objp->fk_product > 0)
				{
					print '<td>';
					print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

					$product->fetch($objp->fk_product);
					// LDR Add a product line from object product
					$text = '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$product->id.'">';
					if ($product->type==1) $text.= img_object($langs->trans('ShowService'),'service');
					else $text.= img_object($langs->trans('ShowProduct'),'product');
					$text.= ' '.$product->ref.'</a>';
					$text.= ' - '.$product->libelle;
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
					print "<td>".nl2br($objp->description)."</td>\n";
				}

				print '<td align="center">'.$objp->qty.'</td>';

				$qtyProdCom=$objp->qty;
				print '<td align="center">';
				// Nb of sending products for this line of order
				$quantite_livree = $commande->expeditions[$objp->rowid];
				print $quantite_livree;
				print '</td>';

				$reste_a_livrer[$objp->fk_product] = $objp->qty - $quantite_livree;
				$reste_a_livrer_total = $reste_a_livrer_total + $reste_a_livrer[$objp->fk_product];
				print '<td align="center">';
				print $reste_a_livrer[$objp->fk_product];
				print '</td>';

				if ($conf->stock->enabled)
				{
					print '<td align="center">';
					print $product->stock_reel;
					if ($product->stock_reel < $reste_a_livrer[$objp->fk_product])
					{
						print ' '.img_warning($langs->trans("StockTooLow"));
					}
					print '</td>';
				}
				else
				{
					print '<td>&nbsp;</td>';
				}
				print "</tr>";

				// associations sous produits
				if (! empty($conf->global->PRODUIT_SOUSPRODUITS) && $objp->fk_product > 0)
				{
					$product->get_sousproduits_arbo ();
					$prods_arbo = $product->get_arbo_each_prod($qtyProdCom);
					if(sizeof($prods_arbo) > 0)
					{
						foreach($prods_arbo as $key => $value)
						{
							print $value[0];
						}
					}
				}

				$i++;
			}
			$db->free($resql);

			if (! $num)
			{
				print '<tr '.$bc[false].'><td colspan="5">'.$langs->trans("NoArticleOfTypeProduct").'<br>';
			}

			print "</table>";
		}
		else
		{
			dol_print_error($db);
		}

		print '</div>';


		/*
		 * Boutons Actions
		 */

		if ($user->societe_id == 0)
		{
			print '<div class="tabsAction">';

			// Bouton expedier sans gestion des stocks
			if (! $conf->stock->enabled && ! $commande->brouillon)
			{
				if ($user->rights->expedition->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/expedition/fiche.php?action=create&amp;origin=commande&amp;object_id='.$_GET["id"].'">'.$langs->trans("NewSending").'</a>';
					if ($reste_a_livrer_total <= 0)
					{
						print ' '.img_warning($langs->trans("WarningNoQtyLeftToSend"));
					}
				}
				else
				{
					print '<a class="butActionRefused" href="#">'.$langs->trans("NewSending").'</a>';
				}
			}
			print "</div>";
		}


		// Bouton expedier avec gestion des stocks
		if ($conf->stock->enabled && $commande->statut > 0 && $commande->statut < 3)
		{
			if ($user->rights->expedition->creer)
			{
				print_titre($langs->trans("NewSending"));

				print '<form method="GET" action="'.DOL_URL_ROOT.'/expedition/fiche.php">';
				print '<input type="hidden" name="action" value="create">';
				print '<input type="hidden" name="id" value="'.$commande->id.'">';
				print '<input type="hidden" name="origin" value="commande">';
				print '<input type="hidden" name="object_id" value="'.$commande->id.'">';
				print '<table class="border" width="100%">';

				$entrepot = new Entrepot($db);
				$langs->load("stocks");

				print '<tr>';
				print '<td>'.$langs->trans("Warehouse").'</td>';
				print '<td>';

				if (sizeof($user->entrepots) === 1)
				{
					$uentrepot = array();
					$uentrepot[$user->entrepots[0]['id']] = $user->entrepots[0]['label'];
					$html->select_array("entrepot_id",$uentrepot);
				}
				else
				{
					$html->select_array("entrepot_id",$entrepot->list_array());
				}

				if (sizeof($entrepot->list_array()) <= 0)
				{
					print ' &nbsp; No warehouse defined, <a href="'.DOL_URL_ROOT.'/product/stock/fiche.php?action=create">add one</a>';
				}
				print '</td><td align="center">';
				print '<input type="submit" class="button" named="save" value="'.$langs->trans("NewSending").'">';
				if ($reste_a_livrer_total <= 0)
				{
					print ' '.img_warning($langs->trans("WarningNoQtyLeftToSend"));
				}
				print '</td></tr>';

				print "</table>";
				print "</form>\n";
				print '<br>';

				$somethingshown=1;

			}
			else
			{
				print '<div class="tabsAction">';
				print '<a class="butActionRefused" href="#">'.$langs->trans("NewSending").'</a>';
				print '</div>';
			}
		}


		show_list_sending_receive('commande',$commande->id);

	}
	else
	{
		/* Commande non trouv�e */
		print "Commande inexistante";
	}
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
