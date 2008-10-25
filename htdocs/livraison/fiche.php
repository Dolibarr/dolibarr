<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon TOSSER          <simon@kornog-computing.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2005-2008 Regis Houssin         <regis@dolibarr.fr>
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
 *	\file       htdocs/livraison/fiche.php
 *	\ingroup    livraison
 *	\brief      Fiche descriptive d'un bon de livraison
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/includes/modules/livraison/modules_livraison.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/sendings.lib.php");
if ($conf->produit->enabled) require_once(DOL_DOCUMENT_ROOT."/product.class.php");
if ($conf->expedition_bon->enabled) require_once(DOL_DOCUMENT_ROOT."/expedition/expedition.class.php");
if ($conf->stock->enabled) require_once(DOL_DOCUMENT_ROOT."/product/stock/entrepot.class.php");

$langs->load("sendings");
$langs->load("bills");
$langs->load('deliveries');

if (!$user->rights->expedition->livraison->lire)
accessforbidden();


// Security check
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}


/*
 * Actions
 */

if ($_POST["action"] == 'add')
{
	$db->begin();

	// Creation de l'objet livraison
	$livraison = new Livraison($db);

	$livraison->date_livraison   = time();
	$livraison->note             = $_POST["note"];
	$livraison->commande_id      = $_POST["commande_id"];

	if (!$conf->expedition_bon->enabled && $conf->stock->enabled)
	{
		$expedition->entrepot_id     = $_POST["entrepot_id"];
	}

	// On boucle sur chaque ligne de commande pour compl�ter objet livraison
	// avec qt� � livrer
	$commande = new Commande($db);
	$commande->fetch($livraison->commande_id);
	$commande->fetch_lines();
	for ($i = 0 ; $i < sizeof($commande->lignes) ; $i++)
	{
		$qty = "qtyl".$i;
		$idl = "idl".$i;
		if ($_POST[$qty] > 0)
		{
			$livraison->addline($_POST[$idl],$_POST[$qty]);
		}
	}

	$ret=$livraison->create($user);
	if ($ret > 0)
	{
		$db->commit();
		Header("Location: fiche.php?id=".$livraison->id);
		exit;
	}
	else
	{
		$db->rollback();
		$mesg='<div class="error">'.$livraison->error.'</div>';
		$_GET["commande_id"]=$_POST["commande_id"];
		$_GET["action"]='create';
	}
}

if ($_POST["action"] == 'confirm_valid' && $_POST["confirm"] == 'yes' && $user->rights->expedition->livraison->valider)
{
	$livraison = new Livraison($db);
	$livraison->fetch($_GET["id"]);
	$result = $livraison->valid($user);
	//$livraison->PdfWrite();
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes')
{
	if ($user->rights->expedition->livraison->supprimer )
	{
		$livraison = new Livraison($db);
		$livraison->fetch($_GET["id"]);
		$expedition_id = $_GET["expid"];
		$livraison->delete();
		if ($conf->expedition_bon->enabled)
		{
			Header("Location: ".DOL_URL_ROOT.'/expedition/fiche.php?id='.$expedition_id);
		}
		else
		{
			Header("Location: liste.php");
		}
		exit;
	}
}

/*
 * Build document
 */
if ($_REQUEST['action'] == 'builddoc')	// En get ou en post
{
	$delivery = new Livraison($db, 0, $_REQUEST['id']);
	$delivery->fetch($_REQUEST['id']);

	if ($_REQUEST['model'])
	{
		$delivery->setDocModel($user, $_REQUEST['model']);
	}

	if ($_REQUEST['lang_id'])
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	$result=delivery_order_pdf_create($db, $_REQUEST['id'],$_REQUEST['model'],$outputlangs);
	if ($result <= 0)
	{
		dolibarr_print_error($db,$result);
		exit;
	}
}


/*
 *	View
 */

llxHeader('',$langs->trans('Delivery'),'Livraison');

$html = new Form($db);
$formfile = new FormFile($db);

/*********************************************************************
 *
 * Mode creation
 *
 *********************************************************************/
if ($_GET["action"] == 'create')
{

	print_titre($langs->trans("CreateADeliveryOrder"));

	if ($mesg)
	{
		print $mesg.'<br>';
	}

	$commande = new Commande($db);
	$commande->livraison_array();

	if ( $commande->fetch($_GET["commande_id"]))
	{
		$soc = new Societe($db);
		$soc->fetch($commande->socid);
		$author = new User($db);
		$author->id = $commande->user_author_id;
		$author->fetch();

		if (!$conf->expedition_bon->enabled && $conf->stock->enabled)
		{
			$entrepot = new Entrepot($db);
		}

		/*
		 *   Commande
		 */
		print '<form action="fiche.php" method="post">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="commande_id" value="'.$commande->id.'">';
		if (!$conf->expedition_bon->enabled && $conf->stock->enabled)
		{
			print '<input type="hidden" name="entrepot_id" value="'.$_GET["entrepot_id"].'">';
		}
		print '<table class="border" width="100%">';
		print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
		print '<td width="30%"><b><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';

		print '<td width="50%" colspan="2">';

		print "</td></tr>";

		print "<tr><td>".$langs->trans("Date")."</td>";
		print "<td>".dolibarr_print_date($commande->date,'dayhourtext')."</td>\n";

		print '<td>'.$langs->trans("Order").'</td><td><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$commande->id.'">'.img_object($langs->trans("ShowOrder"),'order').' '.$commande->ref.'</a>';
		print "</td></tr>\n";

		print '<tr>';

		if (!$conf->expedition_bon->enabled && $conf->stock->enabled)
		{
			print '<td>'.$langs->trans("Warehouse").'</td>';
			print '<td>';
			$ents = $entrepot->list_array();
			print '<a href="'.DOL_URL_ROOT.'/product/stock/fiche.php?id='.$_GET["entrepot_id"].'">'.img_object($langs->trans("ShowWarehouse"),'stock').' '.$ents[$_GET["entrepot_id"]].'</a>';
			print '</td>';
		}

		print "<td>".$langs->trans("Author")."</td><td>$author->fullname</td>\n";

		if ($commande->note)
		{
			print '<tr><td colspan="3">Note : '.nl2br($commande->note)."</td></tr>";
		}
		print "</table>";

		/*
		 * Lignes de commandes
		 *
		 */
		echo '<br><table class="noborder" width="100%">';

		$lignes = $commande->fetch_lines(1);

		/* Lecture des livraisons d�j� effectu�es */
		$commande->livraison_array();

		$num = sizeof($commande->lignes);
		$i = 0;

		if ($num)
		{
			print '<tr class="liste_titre">';
			print '<td width="54%">'.$langs->trans("Description").'</td>';
			print '<td align="center">Quan. command�e</td>';
			print '<td align="center">Quan. livr�e</td>';
			print '<td align="center">Quan. � livrer</td>';
			if ($conf->stock->enabled)
			{
				print '<td width="12%" align="center">'.$langs->trans("Stock").'</td>';
			}
			print "</tr>\n";
		}
		$var=true;
		while ($i < $num)
		{
			$ligne = $commande->lignes[$i];
			$var=!$var;
			print "<tr $bc[$var]>\n";
			if ($ligne->fk_product > 0)
			{
				$product = new Product($db);
				$product->fetch($ligne->fk_product);

				print '<td>';
				print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$ligne->fk_product.'">'.img_object($langs->trans("ShowProduct"),"product").' '.$product->ref.'</a> - '.$product->libelle;
				if ($ligne->description) print nl2br($ligne->description);
				print '</td>';
			}
			else
			{
				print "<td>".nl2br($ligne->description)."</td>\n";
			}

			print '<td align="center">'.$ligne->qty.'</td>';
			/*
			 *
			 */
			print '<td align="center">';
			$quantite_livree = $commande->livraisons[$ligne->id];
			print $quantite_livree;;
			print '</td>';

			$quantite_commandee = $ligne->qty;
			$quantite_a_livrer = $quantite_commandee - $quantite_livree;

			if ($conf->stock->enabled)
			{
				$stock = $product->stock_entrepot[$_GET["entrepot_id"]];
				$stock+=0;  // Convertit en num�rique

				// Quantit� � livrer
				print '<td align="center">';
				print '<input name="idl'.$i.'" type="hidden" value="'.$ligne->id.'">';
				print '<input name="qtyl'.$i.'" type="text" size="6" value="'.min($quantite_a_livrer, $stock).'">';
				print '</td>';

				// Stock
				if ($stock < $quantite_a_livrer)
				{
					print '<td align="center">'.$stock.' '.img_alerte().'</td>';
				}
				else
				{
					print '<td align="center">'.$stock.'</td>';
				}
			}
			else
			{
				// Quantit� � livrer
				print '<td align="center">';
				print '<input name="idl'.$i.'" type="hidden" value="'.$ligne->id.'">';
				print '<input name="qtyl'.$i.'" type="text" size="6" value="'.$quantite_a_livrer.'">';
				print '</td>';
			}

			print "</tr>\n";

			$i++;
			$var=!$var;
		}

		/*
		 *
		 */

		print '<tr><td align="center" colspan="4"><br><input type="submit" class="button" value="'.$langs->trans("Create").'"></td></tr>';
		print "</table>";
		print '</form>';
	}
	else
	{
		dolibarr_print_error($db);
	}
}
else
/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
{
	if ($_GET["id"] > 0)
	{
		$livraison = new Livraison($db);
		$result = $livraison->fetch($_GET["id"]);
		$livraison->fetch_client();

		$expedition=new Expedition($db);
		$result = $expedition->fetch($livraison->expedition_id);
		
		if ($livraison->origin_id)
		{
			$object = $livraison->origin;
			$livraison->fetch_object();
		}

		if ( $livraison->id > 0)
		{
			$soc = new Societe($db);
			$soc->fetch($livraison->socid);

			$h=0;
			if ($conf->expedition_bon->enabled)
			{
				$head[$h][0] = DOL_URL_ROOT."/expedition/fiche.php?id=".$livraison->expedition_id;
				$head[$h][1] = $langs->trans("SendingCard");
				$h++;
			}

			$head[$h][0] = DOL_URL_ROOT."/livraison/fiche.php?id=".$livraison->id;
			$head[$h][1] = $langs->trans("DeliveryCard");
			$hselected = $h;
			$h++;

			dolibarr_fiche_head($head, $hselected, $langs->trans("Sending"));

			/*
			 * Confirmation de la suppression
			 *
			 */
			if ($_GET["action"] == 'delete')
			{
				$expedition_id = $_GET["expid"];
				$html->form_confirm($_SERVER['PHP_SELF'].'?id='.$livraison->id.'&amp;expid='.$expedition_id,'Supprimer le bon de livraison','Etes-vous s�r de vouloir supprimer ce bon de livraison ?','confirm_delete');
				print '<br>';
			}

			/*
			 * Confirmation de la validation
			 *
			 */
			if ($_GET["action"] == 'valid')
			{
				$html->form_confirm($_SERVER['PHP_SELF'].'?id='.$livraison->id,$langs->trans("ValidateDeliveryReceipt"),$langs->trans("ValidateDeliveryReceiptConfirm"),'confirm_valid');
				print '<br>';
			}


			/*
			 *   Livraison
			 */
			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
			print '<td colspan="3">'.$livraison->ref.'</td></tr>';

			// Client
			print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
			print '<td align="3">'.$soc->getNomUrl(1).'</td>';
			print "</tr>";

			// Document origine
			if ($conf->commande->enabled)
			{
				print '<tr><td>'.$langs->trans("RefOrder").'</td>';
				$order=new Commande($db);
				$order->fetch($expedition->origin_id);
				print '<td colspan="3">';
				print $order->getNomUrl(1,4);
				print "</td>\n";
				print '</tr>';
			}
			else
			{
				$propal=new Propal($db);
				$propal->fetch($livraison->origin_id);
				print '<tr><td>'.$langs->trans("RefProposal").'</td>';
				print '<td colspan="3">';
				print $propal->getNomUrl(1,'expedition');
				print "</td>\n";
				print '</tr>';
			}

			// Ref client
			print '<tr><td>'.$langs->trans("RefCustomer").'</td>';
			print '<td colspan="3">'.$livraison->ref_client."</a></td>\n";
			print '</tr>';

			// Date
			print '<tr><td>'.$langs->trans("Date").'</td>';
			print '<td colspan="3">'.dolibarr_print_date($livraison->date_creation,'dayhourtext')."</td>\n";
			print '</tr>';

			// Statut
			print '<tr><td>'.$langs->trans("Status").'</td>';
			print '<td colspan="3">'.$livraison->getLibStatut(4)."</td>\n";
			print '</tr>';

			if (!$conf->expedition_bon->enabled && $conf->stock->enabled)
			{
				// Entrepot
				$entrepot = new Entrepot($db);
				$entrepot->fetch($livraison->entrepot_id);
				print '<tr><td width="20%">'.$langs->trans("Warehouse").'</td>';
				print '<td colspan="3"><a href="'.DOL_URL_ROOT.'/product/stock/fiche.php?id='.$entrepot->id.'">'.$entrepot->libelle.'</a></td>';
				print '</tr>';
			}

			print "</table>\n";

			/*
			 * Lignes produits
			 */
			print '<br><table class="noborder" width="100%">';

			$num_prod = sizeof($livraison->lignes);

			if ($num_prod)
			{
				$i = 0;
					
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Products").'</td>';
				print '<td align="center">'.$langs->trans("QtyOrdered").'</td>';
				print '<td align="center">'.$langs->trans("QtyReceived").'</td>';
				print "</tr>\n";
					
				$var=true;
				while ($i < $num_prod)
				{
					$var=!$var;
					print "<tr $bc[$var]>";
					if ($livraison->lignes[$i]->fk_product > 0)
					{
						$product = new Product($db);
						$product->fetch($livraison->lignes[$i]->fk_product);
							
						print '<td>';
						print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$livraison->lignes[$i]->fk_product.'">'.img_object($langs->trans("ShowProduct"),"product").' '.$product->ref.'</a> - '.$product->libelle;
						if ($livraison->lignes[$i]->description) print '<br>'.$livraison->lignes[$i]->description;
						print '</td>';
					}
					else
					{
						print "<td>".$livraison->lignes[$i]->description."</td>\n";
					}

					print '<td align="center">'.$livraison->lignes[$i]->qty_asked.'</td>';
					print '<td align="center">'.$livraison->lignes[$i]->qty_shipped.'</td>';

					print "</tr>";

					$i++;
				}
			}

			print "</table>\n";

			print "\n</div>\n";


			/*
			 *    Boutons actions
			 */

			if ($user->societe_id == 0)
			{
				print '<div class="tabsAction">';
					
				if (! eregi('^(valid|delete)',$_REQUEST["action"]))
				{
					if ($livraison->statut == 0 && $user->rights->expedition->livraison->valider && $num_prod > 0)
					{
						print '<a class="butAction" href="fiche.php?id='.$livraison->id.'&amp;action=valid">'.$langs->trans("Validate").'</a>';
					}

					if ($livraison->brouillon && $user->rights->expedition->livraison->supprimer)
					{
						if ($conf->expedition_bon->enabled)
						{
							print '<a class="butActionDelete" href="fiche.php?id='.$livraison->id.'&amp;expid='.$livraison->expedition_id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
						}
						else
						{
							print '<a class="butActionDelete" href="fiche.php?id='.$livraison->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
						}
					}
				}
					
				print '</div>';
			}
			print "\n";

			print "<table width=\"100%\" cellspacing=2><tr><td width=\"50%\" valign=\"top\">";

			/*
		 	 * Documents generated
			 */

			$livraisonref = sanitize_string($livraison->ref);
			$filedir = $conf->livraison_bon->dir_output . '/' . $livraisonref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$livraison->id;

			$genallowed=$user->rights->expedition->livraison->creer;
			$delallowed=$user->rights->expedition->livraison->supprimer;

			$somethingshown=$formfile->show_documents('livraison',$livraisonref,$filedir,$urlsource,$genallowed,$delallowed,$livraison->modelpdf);
			if ($genallowed && ! $somethingshown) $somethingshown=1;

			print '</td><td valign="top" width="50%">';

			// Rien a droite
			
			print '</td></tr></table>';

			print '<br>';
			//show_list_sending_receive($expedition->origin,$expedition->origin_id," AND e.rowid <> ".$expedition->id);
			show_list_sending_receive($expedition->origin,$expedition->origin_id);
		}
		else
		{
			/* Expedition non trouv�e */
			print "Expedition inexistante ou acc�s refus�";
		}
	}
	else
	{
		/* Expedition non trouv�e */
		print "Expedition inexistante ou acc�s refus�";
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
