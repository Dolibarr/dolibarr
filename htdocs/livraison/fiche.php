<?php
/* Copyright (C) 2003-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Simon TOSSER			<simon@kornog-computing.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2007		Franky Van Liedekerke	<franky.van.liedekerke@telenet.be>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
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
 *	\file       htdocs/livraison/fiche.php
 *	\ingroup    livraison
 *	\brief      Fiche descriptive d'un bon de livraison=reception
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/livraison/class/livraison.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/livraison/modules_livraison.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';
if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
if (! empty($conf->expedition_bon->enabled))
	require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
if (! empty($conf->stock->enabled))
	require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';


$langs->load("sendings");
$langs->load("bills");
$langs->load('deliveries');
$langs->load('orders');

$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');

// Security check
$id = GETPOST('id', 'int');
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'expedition',$id,'livraison','livraison');



/*
 * Actions
 */

if ($action == 'add')
{
	$db->begin();

	// Creation de l'objet livraison
	$delivery = new Livraison($db);

	$delivery->date_livraison   = time();
	$delivery->note             = $_POST["note"];
	$delivery->commande_id      = $_POST["commande_id"];

	if (!$conf->expedition_bon->enabled && ! empty($conf->stock->enabled))
	{
		$expedition->entrepot_id     = $_POST["entrepot_id"];
	}

	// On boucle sur chaque ligne de commande pour completer objet livraison
	// avec qte a livrer
	$commande = new Commande($db);
	$commande->fetch($delivery->commande_id);
	$commande->fetch_lines();
	$num=count($commande->lines);
	for ($i = 0; $i < $num; $i++)
	{
		$qty = "qtyl".$i;
		$idl = "idl".$i;
		if ($_POST[$qty] > 0)
		{
			$delivery->addline($_POST[$idl],$_POST[$qty]);
		}
	}

	$ret=$delivery->create($user);
	if ($ret > 0)
	{
		$db->commit();
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$delivery->id);
		exit;
	}
	else
	{
		setEventMessage($delivery->error, 'errors');
		$db->rollback();

		$_GET["commande_id"]=$_POST["commande_id"];
		$action='create';
	}
}

else if ($action == 'confirm_valid' && $confirm == 'yes' && $user->rights->expedition->livraison->valider)
{
	$object = new Livraison($db);
	$object->fetch($id);
	$object->fetch_thirdparty();

	$result = $object->valid($user);

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
	{
        $ret=$object->fetch($id);    // Reload to get new records
	    $result=delivery_order_pdf_create($db, $object,$_REQUEST['model'],$outputlangs);
	}
   	if ($result < 0)
   	{
   		dol_print_error($db,$result);
   		exit;
	}
}

if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->expedition->livraison->supprimer)
{
	$object = new Livraison($db);
	$object->fetch($id);
	$object->fetch_thirdparty();

	$db->begin();
	$result=$object->delete();

	if ($result > 0)
	{
		$db->commit();
		header("Location: ".DOL_URL_ROOT.'/expedition/index.php');
		exit;
	}
	else
	{
		$db->rollback();
	}
}

if ($action == 'setdate_livraison' && $user->rights->expedition->livraison->creer)
{
	$object = new Livraison($db);
	$object->fetch($id);
	$object->fetch_thirdparty();

	//print "x ".$_POST['liv_month'].", ".$_POST['liv_day'].", ".$_POST['liv_year'];
    $datedelivery=dol_mktime(GETPOST('liv_hour','int'), GETPOST('liv_min','int'), 0, GETPOST('liv_month','int'), GETPOST('liv_day','int'), GETPOST('liv_year','int'));

    $object->fetch($id);
    $result=$object->set_date_livraison($user,$datedelivery);
    if ($result < 0)
    {
        $mesg='<div class="error">'.$object->error.'</div>';
    }
}

/*
 * Build document
 */
if ($action == 'builddoc')	// En get ou en post
{
	$object = new Livraison($db);
	$object->fetch($id);
	$object->fetch_thirdparty();

	if ($_REQUEST['model'])
	{
		$object->setDocModel($user, $_REQUEST['model']);
	}

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
	{
	    $ret=$object->fetch($id);    // Reload to get new records
    	$result=delivery_order_pdf_create($db, $object, $object->modelpdf, $outputlangs);
	}
	if ($result < 0)
	{
		dol_print_error($db,$result);
		exit;
	}
}

// Delete file in doc form
elseif ($action == 'remove_file')
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$object = new Livraison($db);
	if ($object->fetch($id))
	{
		$object->fetch_thirdparty();
		$upload_dir =	$conf->expedition->dir_output . "/receipt";
		$file =	$upload_dir	. '/' .	GETPOST('file');
		$ret=dol_delete_file($file,0,0,0,$object);
		if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
		else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
	}
}


/*
 *	View
 */

llxHeader('',$langs->trans('Delivery'),'Livraison');

$form = new Form($db);
$formfile = new FormFile($db);

/*********************************************************************
 *
 * Mode creation
 *
 *********************************************************************/
if ($action == 'create')
{

	print_fiche_titre($langs->trans("CreateADeliveryOrder"));

	if ($mesg)
	{
		print $mesg.'<br>';
	}

	$commande = new Commande($db);
	$commande->livraison_array();

	if ($commande->fetch($_GET["commande_id"]))
	{
		$soc = new Societe($db);
		$soc->fetch($commande->socid);
		$author = new User($db);
		$author->fetch($commande->user_author_id);

		if (!$conf->expedition_bon->enabled && ! empty($conf->stock->enabled))
		{
			$entrepot = new Entrepot($db);
		}

		/*
		 *   Commande
		 */
		print '<form action="fiche.php" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="commande_id" value="'.$commande->id.'">';
		if (!$conf->expedition_bon->enabled && ! empty($conf->stock->enabled))
		{
			print '<input type="hidden" name="entrepot_id" value="'.$_GET["entrepot_id"].'">';
		}
		print '<table class="border" width="100%">';
		print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
		print '<td width="30%"><b><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';

		print '<td width="50%" colspan="2">';

		print "</td></tr>";

		print "<tr><td>".$langs->trans("Date")."</td>";
		print "<td>".dol_print_date($commande->date,'dayhourtext')."</td>\n";

		print '<td>'.$langs->trans("Order").'</td><td><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$commande->id.'">'.img_object($langs->trans("ShowOrder"),'order').' '.$commande->ref.'</a>';
		print "</td></tr>\n";

		print '<tr>';

		if (!$conf->expedition_bon->enabled && ! empty($conf->stock->enabled))
		{
			print '<td>'.$langs->trans("Warehouse").'</td>';
			print '<td>';
			$ents = $entrepot->list_array();
			print '<a href="'.DOL_URL_ROOT.'/product/stock/fiche.php?id='.$_GET["entrepot_id"].'">'.img_object($langs->trans("ShowWarehouse"),'stock').' '.$ents[$_GET["entrepot_id"]].'</a>';
			print '</td>';
		}

		print "<td>".$langs->trans("Author")."</td><td>".$author->getFullName($langs)."</td>\n";

		if ($commande->note)
		{
			print '<tr><td colspan="3">Note : '.nl2br($commande->note)."</td></tr>";
		}
		print "</table>";

		/*
		 * Lignes de commandes
		 */
		print '<br><table class="noborder" width="100%">';

		$lines = $commande->fetch_lines(1);

		// Lecture des livraisons deja effectuees
		$commande->livraison_array();

		$num = count($commande->lines);
		$i = 0;

		if ($num)
		{
			print '<tr class="liste_titre">';
			print '<td width="54%">'.$langs->trans("Description").'</td>';
			print '<td align="center">'.$langs->trans("QtyOrdered").'</td>';
			print '<td align="center">'.$langs->trans("QtyReceived").'</td>';
			print '<td align="center">'.$langs->trans("QtyToShip").'</td>';
			if (! empty($conf->stock->enabled))
			{
				print '<td width="12%" align="center">'.$langs->trans("Stock").'</td>';
			}
			print "</tr>\n";
		}
		$var=true;
		while ($i < $num)
		{
			$product = new Product($db);

			$line = $commande->lines[$i];
			$var=!$var;
			print "<tr $bc[$var]>\n";
			if ($line->fk_product > 0)
			{
				$product->fetch($line->fk_product);
				$product->load_stock();

				// Define output language
				if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE))
				{
					$commande->fetch_thirdparty();
					$outputlangs = $langs;
					$newlang='';
					if (empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
					if (empty($newlang)) $newlang=$commande->client->default_lang;
					if (! empty($newlang))
					{
						$outputlangs = new Translate("",$conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$label = (! empty($product->multilangs[$outputlangs->defaultlang]["label"])) ? $product->multilangs[$outputlangs->defaultlang]["label"] : $product->label;
				}
				else
					$label = (! empty($line->label)?$line->label:$product->label);

				print '<td>';
				print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$line->fk_product.'">'.img_object($langs->trans("ShowProduct"),"product").' '.$product->ref.'</a> - '.$label;
				if ($line->description) print nl2br($line->description);
				print '</td>';
			}
			else
			{
				print "<td>";
				if ($line->fk_product_type==1) $text = img_object($langs->trans('Service'),'service');
				else $text = img_object($langs->trans('Product'),'product');

				if (! empty($line->label)) {
					$text.= ' <strong>'.$line->label.'</strong>';
					print $form->textwithtooltip($text,$line->description,3,'','',$i);
				} else {
					print $text.' '.nl2br($line->description);
				}

				print_date_range($lines[$i]->date_start,$lines[$i]->date_end);
				print "</td>\n";
			}

			print '<td align="center">'.$line->qty.'</td>';
			/*
			 *
			 */
			print '<td align="center">';
			$quantite_livree = $commande->livraisons[$line->id];
			print $quantite_livree;;
			print '</td>';

			$quantite_commandee = $line->qty;
			$quantite_a_livrer = $quantite_commandee - $quantite_livree;

			if (! empty($conf->stock->enabled))
			{
				$stock = $product->stock_warehouse[$_GET["entrepot_id"]]->real;
				$stock+=0;  // Convertit en numerique

				// Quantite a livrer
				print '<td align="center">';
				print '<input name="idl'.$i.'" type="hidden" value="'.$line->id.'">';
				print '<input name="qtyl'.$i.'" type="text" size="6" value="'.min($quantite_a_livrer, $stock).'">';
				print '</td>';

				// Stock
				if ($stock < $quantite_a_livrer)
				{
					print '<td align="center">'.$stock.' '.img_warning().'</td>';
				}
				else
				{
					print '<td align="center">'.$stock.'</td>';
				}
			}
			else
			{
				// Quantite a livrer
				print '<td align="center">';
				print '<input name="idl'.$i.'" type="hidden" value="'.$line->id.'">';
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
		dol_print_error($db);
	}
}
else
/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
{
	if ($id > 0)
	{
		$delivery = new Livraison($db);
		$result = $delivery->fetch($id);
		$delivery->fetch_thirdparty();

		$expedition=new Expedition($db);
		$result = $expedition->fetch($delivery->origin_id);
		$typeobject = $expedition->origin;

		if ($delivery->origin_id)
		{
			$delivery->fetch_origin();
		}

		if ($delivery->id > 0)
		{
			$soc = new Societe($db);
			$soc->fetch($delivery->socid);

			$head=delivery_prepare_head($delivery);
			dol_fiche_head($head, 'delivery', $langs->trans("Sending"), 0, 'sending');

			/*
			 * Confirmation de la suppression
			 *
			 */
			if ($action == 'delete')
			{
				$expedition_id = $_GET["expid"];
				$ret=$form->form_confirm($_SERVER['PHP_SELF'].'?id='.$delivery->id.'&amp;expid='.$expedition_id,$langs->trans("DeleteDeliveryReceipt"),$langs->trans("DeleteDeliveryReceiptConfirm",$delivery->ref),'confirm_delete','','',1);
				if ($ret == 'html') print '<br>';
			}

			/*
			 * Confirmation de la validation
			 *
			 */
			if ($action == 'valid')
			{
				$ret=$form->form_confirm($_SERVER['PHP_SELF'].'?id='.$delivery->id,$langs->trans("ValidateDeliveryReceipt"),$langs->trans("ValidateDeliveryReceiptConfirm",$delivery->ref),'confirm_valid','','',1);
				if ($ret == 'html') print '<br>';
			}


			/*
			 *   Livraison
			 */
			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
			print '<td colspan="3">'.$delivery->ref.'</td></tr>';

			// Client
			print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
			print '<td align="3">'.$soc->getNomUrl(1).'</td>';
			print "</tr>";

			// Document origine
			if ($typeobject == 'commande' && $expedition->origin_id && ! empty($conf->commande->enabled))
			{
				print '<tr><td>'.$langs->trans("RefOrder").'</td>';
				$order=new Commande($db);
				$order->fetch($expedition->origin_id);
				print '<td colspan="3">';
				print $order->getNomUrl(1,'commande');
				print "</td>\n";
				print '</tr>';
			}
			if ($typeobject == 'propal' && $expedition->origin_id && ! empty($conf->propal->enabled))
			{
				$propal=new Propal($db);
				$propal->fetch($expedition->origin_id);
				print '<tr><td>'.$langs->trans("RefProposal").'</td>';
				print '<td colspan="3">';
				print $propal->getNomUrl(1,'expedition');
				print "</td>\n";
				print '</tr>';
			}

			// Ref client
			print '<tr><td>'.$langs->trans("RefCustomer").'</td>';
			print '<td colspan="3">'.$delivery->ref_customer."</a></td>\n";
			print '</tr>';

			// Date
			print '<tr><td>'.$langs->trans("DateCreation").'</td>';
			print '<td colspan="3">'.dol_print_date($delivery->date_creation,'daytext')."</td>\n";
			print '</tr>';

			// Date delivery real / Received
			print '<tr><td height="10">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('DateReceived');
			print '</td>';

			if ($action != 'editdate_livraison') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;id='.$delivery->id.'">'.img_edit($langs->trans('SetDeliveryDate'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="2">';
			if ($action == 'editdate_livraison')
			{
				print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$delivery->id.'" method="post">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="setdate_livraison">';
				$form->select_date($delivery->date_delivery?$delivery->date_delivery:-1,'liv_',1,1,'',"setdate_livraison");
				print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
				print '</form>';
			}
			else
			{
				print $delivery->date_delivery ? dol_print_date($delivery->date_delivery,'dayhourtext') : '&nbsp;';
			}
			print '</td>';
			print '</tr>';

			// Note Public
            print '<tr><td>'.$langs->trans("NotePublic").'</td>';
            print '<td colspan="3">';
            print nl2br($delivery->note_public);
            /*$doleditor = new DolEditor('note_public', $object->note_public, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
            print $doleditor->Create(1);*/
            print "</td></tr>";

			// Note Private
            print '<tr><td>'.$langs->trans("NotePrivate").'</td>';
            print '<td colspan="3">';
            print nl2br($delivery->note_private);
            /*$doleditor = new DolEditor('note_pprivate', $object->note_private, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
            print $doleditor->Create(1);*/
            print "</td></tr>";


			// Statut
			print '<tr><td>'.$langs->trans("Status").'</td>';
			print '<td colspan="3">'.$delivery->getLibStatut(4)."</td>\n";
			print '</tr>';

			if (!$conf->expedition_bon->enabled && ! empty($conf->stock->enabled))
			{
				// Entrepot
				$entrepot = new Entrepot($db);
				$entrepot->fetch($delivery->entrepot_id);
				print '<tr><td width="20%">'.$langs->trans("Warehouse").'</td>';
				print '<td colspan="3"><a href="'.DOL_URL_ROOT.'/product/stock/fiche.php?id='.$entrepot->id.'">'.$entrepot->libelle.'</a></td>';
				print '</tr>';
			}

			print "</table><br>\n";

			/*
			 * Lignes produits
			 */

			$num_prod = count($delivery->lines);
			$i = 0; $total = 0;

			print '<table class="noborder" width="100%">';

			if ($num_prod)
			{
				$i = 0;

				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Products").'</td>';
				print '<td align="center">'.$langs->trans("QtyOrdered").'</td>';
				print '<td align="center">'.$langs->trans("QtyReceived").'</td>';
				print "</tr>\n";
			}
			$var=true;
			while ($i < $num_prod)
			{
				$var=!$var;

				print "<tr $bc[$var]>";
				if ($delivery->lines[$i]->fk_product > 0)
				{
					$product = new Product($db);
					$product->fetch($delivery->lines[$i]->fk_product);

					// Define output language
					if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE))
					{
						$delivery->fetch_thirdparty();
						$outputlangs = $langs;
						$newlang='';
						if (empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
						if (empty($newlang)) $newlang=$delivery->client->default_lang;
						if (! empty($newlang))
						{
							$outputlangs = new Translate("",$conf);
							$outputlangs->setDefaultLang($newlang);
						}

						$label = (! empty($product->multilangs[$outputlangs->defaultlang]["label"])) ? $product->multilangs[$outputlangs->defaultlang]["label"] : $delivery->lines[$i]->product_label;
					}
					else
						$label = ( ! empty($delivery->lines[$i]->label)?$delivery->lines[$i]->label:$delivery->lines[$i]->product_label);

					print '<td>';

					// Affiche ligne produit
					$text = '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$delivery->lines[$i]->fk_product.'">';
					if ($delivery->lines[$i]->fk_product_type==1) $text.= img_object($langs->trans('ShowService'),'service');
					else $text.= img_object($langs->trans('ShowProduct'),'product');
					$text.= ' '.$delivery->lines[$i]->product_ref.'</a>';
					$text.= ' - '.$label;
					$description=(! empty($conf->global->PRODUIT_DESC_IN_FORM)?'':dol_htmlentitiesbr($delivery->lines[$i]->description));
					//print $description;
					print $form->textwithtooltip($text,$description,3,'','',$i);
					print_date_range($delivery->lines[$i]->date_start,$delivery->lines[$i]->date_end);
					if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
					{
						print (! empty($delivery->lines[$i]->description) && $delivery->lines[$i]->description!=$delivery->lines[$i]->product_label)?'<br>'.dol_htmlentitiesbr($delivery->lines[$i]->description):'';
					}
				}
				else
				{
					print "<td>";
					if ($delivery->lines[$i]->fk_product_type==1) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');

					if (! empty($delivery->lines[$i]->label)) {
						$text.= ' <strong>'.$delivery->lines[$i]->label.'</strong>';
						print $form->textwithtooltip($text,$delivery->lines[$i]->description,3,'','',$i);
					} else {
						print $text.' '.nl2br($delivery->lines[$i]->description);
					}

					print_date_range($objp->date_start,$objp->date_end);
					print "</td>\n";
				}

				print '<td align="center">'.$delivery->lines[$i]->qty_asked.'</td>';
				print '<td align="center">'.$delivery->lines[$i]->qty_shipped.'</td>';

				print "</tr>";

				$i++;
			}

			print "</table>\n";

			print "\n</div>\n";


			/*
			 *    Boutons actions
			 */

			if ($user->societe_id == 0)
			{
				print '<div class="tabsAction">';

				if ($delivery->statut == 0 && $user->rights->expedition->livraison->valider && $num_prod > 0)
				{
					print '<a class="butAction" href="fiche.php?id='.$delivery->id.'&amp;action=valid">'.$langs->trans("Validate").'</a>';
				}

				if ($user->rights->expedition->livraison->supprimer)
				{
					if ($conf->expedition_bon->enabled)
					{
						print '<a class="butActionDelete" href="fiche.php?id='.$delivery->id.'&amp;expid='.$delivery->expedition_id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
					}
					else
					{
						print '<a class="butActionDelete" href="fiche.php?id='.$delivery->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
					}
				}

				print '</div>';
			}
			print "\n";

			print '<table width="100%" cellspacing="2"><tr><td width="50%" valign="top">';

			/*
		 	 * Documents generated
			 */

			$deliveryref = dol_sanitizeFileName($delivery->ref);
			$filedir = $conf->expedition->dir_output . "/receipt/" . $deliveryref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$delivery->id;

			$genallowed=$user->rights->expedition->livraison->creer;
			$delallowed=$user->rights->expedition->livraison->supprimer;

			$somethingshown=$formfile->show_documents('livraison',$deliveryref,$filedir,$urlsource,$genallowed,$delallowed,$delivery->modelpdf,1,0,0,28,0,'','','',$soc->default_lang);

			/*
		 	 * Linked object block (of linked shipment)
		 	 */
			if ($delivery->origin == 'expedition')
			{
				$shipment = new Expedition($db);
				$shipment->fetch($delivery->origin_id);

				$somethingshown=$shipment->showLinkedObjectBlock();
			}

			if ($genallowed && ! $somethingshown) $somethingshown=1;

			print '</td><td valign="top" width="50%">';

			// Rien a droite

			print '</td></tr></table>';

			if ($expedition->origin_id)
			{
				print '<br>';
				//show_list_sending_receive($expedition->origin,$expedition->origin_id," AND e.rowid <> ".$expedition->id);
				show_list_sending_receive($expedition->origin,$expedition->origin_id);
			}
		}
		else
		{
			/* Expedition non trouvee */
			print "Expedition inexistante ou acces refuse";
		}
	}
	else
	{
		/* Expedition non trouvee */
		print "Expedition inexistante ou acces refuse";
	}
}


llxFooter();
$db->close();
?>
