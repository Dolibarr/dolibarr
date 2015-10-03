<?php
/* Copyright (C) 2003-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Simon TOSSER			<simon@kornog-computing.com>
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@capnetworks.com>
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
 *	\file       htdocs/livraison/card.php
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
if (!empty($conf->incoterm->enabled)) $langs->load('incoterm');

$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');
$backtopage=GETPOST('backtopage');

// Security check
$id = GETPOST('id', 'int');
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'expedition',$id,'livraison','livraison');

$object = new Livraison($db);

// Load object
if ($id > 0 || ! empty($ref)) {
	$ret = $object->fetch($id, $ref);
	if ($ret > 0)
		$ret = $object->fetch_thirdparty();
	if ($ret < 0)
		dol_print_error('', $object->error);
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('deliverycard','globalcard'));

/*
 * Actions
 */
$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

if ($action == 'add')
{
	$db->begin();

	$object->date_livraison   = time();
	$object->note             = $_POST["note"];
	$object->commande_id      = $_POST["commande_id"];
	$object->fk_incoterms = GETPOST('incoterm_id', 'int');

	if (!$conf->expedition_bon->enabled && ! empty($conf->stock->enabled))
	{
		$expedition->entrepot_id     = $_POST["entrepot_id"];
	}

	// On boucle sur chaque ligne de commande pour completer objet livraison
	// avec qte a livrer
	$commande = new Commande($db);
	$commande->fetch($object->commande_id);
	$commande->fetch_lines();
	$num=count($commande->lines);
	for ($i = 0; $i < $num; $i++)
	{
		$qty = "qtyl".$i;
		$idl = "idl".$i;
		if ($_POST[$qty] > 0)
		{
			$object->addline($_POST[$idl],$_POST[$qty]);
		}
	}

	$ret=$object->create($user);
	if ($ret > 0)
	{
		$db->commit();
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}
	else
	{
		setEventMessage($object->error, 'errors');
		$db->rollback();

		$_GET["commande_id"]=$_POST["commande_id"];
		$action='create';
	}
}

else if ($action == 'confirm_valid' && $confirm == 'yes' &&
    ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->expedition->livraison->creer))
    || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->expedition->livraison_advance->validate)))
)
{
	$result = $object->valid($user);

	// Define output language
	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
	{
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
		if (! empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		$model=$object->modelpdf;
		$ret = $object->fetch($id); // Reload to get new records

		$result=$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
		if ($result < 0) dol_print_error($db,$result);
	}
}

if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->expedition->livraison->supprimer)
{
	$db->begin();
	$result=$object->delete();

	if ($result > 0)
	{
		$db->commit();
		if (! empty($backtopage)) header("Location: ".$backtopage);
		else header("Location: ".DOL_URL_ROOT.'/expedition/index.php');
		exit;
	}
	else
	{
		$db->rollback();
	}
}

if ($action == 'setdate_livraison' && $user->rights->expedition->livraison->creer)
{
    $datedelivery=dol_mktime(GETPOST('liv_hour','int'), GETPOST('liv_min','int'), 0, GETPOST('liv_month','int'), GETPOST('liv_day','int'), GETPOST('liv_year','int'));
    $result=$object->set_date_livraison($user,$datedelivery);
    if ($result < 0)
    {
        $mesg='<div class="error">'.$object->error.'</div>';
    }
}

// Set incoterm
elseif ($action == 'set_incoterms' && !empty($conf->incoterm->enabled))
{
	$result = $object->setIncoterms(GETPOST('incoterm_id', 'int'), GETPOST('location_incoterms', 'alpha'));
}

/*
 * Build document
 */
if ($action == 'builddoc')	// En get ou en post
{
	// Save last template used to generate document
	if (GETPOST('model')) $object->setDocModel($user, GETPOST('model','alpha'));

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
    $ret=$object->fetch($id);    // Reload to get new records
	$result= $object->generateDocument($object->modelpdf, $outputlangs);
	if ($result < 0)
	{
		setEventMessages($object->error, $object->errors, 'errors');
        $action='';
	}
}

// Delete file in doc form
elseif ($action == 'remove_file')
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$upload_dir =	$conf->expedition->dir_output . "/receipt";
	$file =	$upload_dir	. '/' .	GETPOST('file');
	$ret=dol_delete_file($file,0,0,0,$object);
	if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
	else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
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

	print load_fiche_titre($langs->trans("CreateADeliveryOrder"));

	if ($mesg)
	{
		print $mesg.'<br>';
	}

	$commande = new Commande($db);
	$commande->livraison_array();

	if ($commande->fetch(GETPOST("commande_id")))
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
		print '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="commande_id" value="'.$commande->id.'">';
		if (!$conf->expedition_bon->enabled && ! empty($conf->stock->enabled))
		{
			print '<input type="hidden" name="entrepot_id" value="'.$_GET["entrepot_id"].'">';
		}
		print '<table class="border" width="100%">';
		print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
		print '<td width="30%"><b><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$soc->id.'">'.$soc->name.'</a></b></td>';

		print '<td width="50%" colspan="2">';

		print "</td></tr>";

		print "<tr><td>".$langs->trans("Date")."</td>";
		print "<td>".dol_print_date($commande->date,'dayhourtext')."</td>\n";

		print '<td>'.$langs->trans("Order").'</td><td><a href="'.DOL_URL_ROOT.'/commande/card.php?id='.$commande->id.'">'.img_object($langs->trans("ShowOrder"),'order').' '.$commande->ref.'</a>';
		print "</td></tr>\n";

		print '<tr>';

		if (!$conf->expedition_bon->enabled && ! empty($conf->stock->enabled))
		{
			print '<td>'.$langs->trans("Warehouse").'</td>';
			print '<td>';
			$ents = $entrepot->list_array();
			print '<a href="'.DOL_URL_ROOT.'/product/stock/card.php?id='.$_GET["entrepot_id"].'">'.img_object($langs->trans("ShowWarehouse"),'stock').' '.$ents[$_GET["entrepot_id"]].'</a>';
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

		$commande->fetch_lines(1);
		$lines = $commande->lines;

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
			print "<tr ".$bc[$var].">\n";
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
				print '<a href="'.DOL_URL_ROOT.'/product/card.php?id='.$line->fk_product.'">'.img_object($langs->trans("ShowProduct"),"product").' '.$product->ref.'</a> - '.$label;
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
			print $quantite_livree;
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
	if ($object->id > 0)
	{
		// Origin of a 'livraison' (delivery) is ALWAYS 'expedition' (shipment).
		// However, origin of shipment in future may differs (commande, proposal, ...)
		// TODO REGIS:
		// Je ne suis pas d'accord, beaucoup entreprises n'utilisent pas les bons d'expéditions car ces derniers sont gérés par le transporteur,
		// donc les bons de livraisons peuvent avoir une origine différente de 'expedition'
		// les bons de livraisons et d'expéditions devraient être considérés comme des objets à part entière, voir des modules différents comme une propal ou autres.

		$expedition=new Expedition($db);
		$result = $expedition->fetch($object->origin_id);
		$typeobject = $expedition->origin;	// example: commande
		if ($object->origin_id > 0)
		{
			$object->fetch_origin();
		}

		if ($object->id > 0)
		{
			$soc = new Societe($db);
			$soc->fetch($object->socid);

			$head=delivery_prepare_head($object);
			dol_fiche_head($head, 'delivery', $langs->trans("Shipment"), 0, 'sending');

			/*
			 * Confirmation de la suppression
			 *
			 */
			if ($action == 'delete')
			{
				$expedition_id = GETPOST("expid");
				print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id.'&expid='.$expedition_id.'&backtopage='.urlencode($backtopage),$langs->trans("DeleteDeliveryReceipt"),$langs->trans("DeleteDeliveryReceiptConfirm",$object->ref),'confirm_delete','','',1);

			}

			/*
			 * Confirmation de la validation
			 */
			if ($action == 'valid')
			{
				print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id,$langs->trans("ValidateDeliveryReceipt"),$langs->trans("ValidateDeliveryReceiptConfirm",$object->ref),'confirm_valid','','',1);

			}


			/*
			 *   Livraison
			 */
			print '<table class="border" width="100%">';

			// Shipment
			if (($object->origin == 'shipment' || $object->origin == 'expedition') && $object->origin_id > 0)
			{
				$linkback = '<a href="'.DOL_URL_ROOT.'/expedition/list.php">'.$langs->trans("BackToList").'</a>';

				// Ref
				print '<tr><td width="20%">'.$langs->trans("RefSending").'</td>';
				print '<td colspan="3">';
				// Nav is hidden because on a delivery receipt of a shipment, if we go on next shipment, we may find no tab (a shipment may not have delivery receipt yet)
				//print $form->showrefnav($expedition, 'refshipment', $linkback, 1, 'ref', 'ref');
				print $form->showrefnav($expedition, 'refshipment', $linkback, 0, 'ref', 'ref');
				print '</td></tr>';
			}

			// Ref
			print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
			print '<td colspan="3">'.$object->ref.'</td></tr>';

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
			print '<td colspan="3">'.$object->ref_customer."</a></td>\n";
			print '</tr>';

			// Date
			print '<tr><td>'.$langs->trans("DateCreation").'</td>';
			print '<td colspan="3">'.dol_print_date($object->date_creation,'daytext')."</td>\n";
			print '</tr>';

			// Date delivery real / Received
			print '<tr><td height="10">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('DateReceived');
			print '</td>';

			if ($action != 'editdate_livraison') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDeliveryDate'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="2">';
			if ($action == 'editdate_livraison')
			{
				print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="setdate_livraison">';
				$form->select_date($object->date_delivery?$object->date_delivery:-1,'liv_',1,1,'',"setdate_livraison");
				print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
				print '</form>';
			}
			else
			{
				print $object->date_delivery ? dol_print_date($object->date_delivery,'dayhourtext') : '&nbsp;';
			}
			print '</td>';
			print '</tr>';

			// Incoterms
			if (!empty($conf->incoterm->enabled))
			{
				print '<tr><td>';
		        print '<table width="100%" class="nobordernopadding"><tr><td>';
		        print $langs->trans('IncotermLabel');
		        print '<td><td align="right">';
		        if ($user->rights->expedition->livraison->creer) print '<a href="'.DOL_URL_ROOT.'/livaison/card.php?id='.$object->id.'&action=editincoterm">'.img_edit().'</a>';
		        else print '&nbsp;';
		        print '</td></tr></table>';
		        print '</td>';
		        print '<td colspan="3">';
				if ($action != 'editincoterm')
				{
					print $form->textwithpicto($object->display_incoterms(), $object->libelle_incoterms, 1);
				}
				else
				{
					print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms)?$object->location_incoterms:''), $_SERVER['PHP_SELF'].'?id='.$object->id);
				}
		        print '</td></tr>';
			}

			// Note Public
            print '<tr><td>'.$langs->trans("NotePublic").'</td>';
            print '<td colspan="3">';
            print nl2br($object->note_public);
            /*$doleditor = new DolEditor('note_public', $object->note_public, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
            print $doleditor->Create(1);*/
            print "</td></tr>";

			// Note Private
            print '<tr><td>'.$langs->trans("NotePrivate").'</td>';
            print '<td colspan="3">';
            print nl2br($object->note_private);
            /*$doleditor = new DolEditor('note_pprivate', $object->note_private, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
            print $doleditor->Create(1);*/
            print "</td></tr>";


			// Statut
			print '<tr><td>'.$langs->trans("Status").'</td>';
			print '<td colspan="3">'.$object->getLibStatut(4)."</td>\n";
			print '</tr>';

			if (!$conf->expedition_bon->enabled && ! empty($conf->stock->enabled))
			{
				// Entrepot
				$entrepot = new Entrepot($db);
				$entrepot->fetch($object->entrepot_id);
				print '<tr><td width="20%">'.$langs->trans("Warehouse").'</td>';
				print '<td colspan="3"><a href="'.DOL_URL_ROOT.'/product/stock/card.php?id='.$entrepot->id.'">'.$entrepot->libelle.'</a></td>';
				print '</tr>';
			}

			print "</table><br>\n";

			/*
			 * Lignes produits
			 */

			$num_prod = count($object->lines);
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

				print "<tr ".$bc[$var].">";
				if ($object->lines[$i]->fk_product > 0)
				{
					$product = new Product($db);
					$product->fetch($object->lines[$i]->fk_product);

					// Define output language
					if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE))
					{
						$outputlangs = $langs;
						$newlang='';
						if (empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
						if (empty($newlang)) $newlang=$object->client->default_lang;
						if (! empty($newlang))
						{
							$outputlangs = new Translate("",$conf);
							$outputlangs->setDefaultLang($newlang);
						}

						$label = (! empty($product->multilangs[$outputlangs->defaultlang]["label"])) ? $product->multilangs[$outputlangs->defaultlang]["label"] : $object->lines[$i]->product_label;
					}
					else
						$label = ( ! empty($object->lines[$i]->label)?$object->lines[$i]->label:$object->lines[$i]->product_label);

					print '<td>';

					// Affiche ligne produit
					$text = '<a href="'.DOL_URL_ROOT.'/product/card.php?id='.$object->lines[$i]->fk_product.'">';
					if ($object->lines[$i]->fk_product_type==1) $text.= img_object($langs->trans('ShowService'),'service');
					else $text.= img_object($langs->trans('ShowProduct'),'product');
					$text.= ' '.$object->lines[$i]->product_ref.'</a>';
					$text.= ' - '.$label;
					$description=(! empty($conf->global->PRODUIT_DESC_IN_FORM)?'':dol_htmlentitiesbr($object->lines[$i]->description));
					//print $description;
					print $form->textwithtooltip($text,$description,3,'','',$i);
					print_date_range($object->lines[$i]->date_start,$object->lines[$i]->date_end);
					if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
					{
						print (! empty($object->lines[$i]->description) && $object->lines[$i]->description!=$object->lines[$i]->product_label)?'<br>'.dol_htmlentitiesbr($object->lines[$i]->description):'';
					}
				}
				else
				{
					print "<td>";
					if ($object->lines[$i]->fk_product_type==1) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');

					if (! empty($object->lines[$i]->label)) {
						$text.= ' <strong>'.$object->lines[$i]->label.'</strong>';
						print $form->textwithtooltip($text,$object->lines[$i]->description,3,'','',$i);
					} else {
						print $text.' '.nl2br($object->lines[$i]->description);
					}

					print_date_range($objp->date_start,$objp->date_end);
					print "</td>\n";
				}

				print '<td align="center">'.$object->lines[$i]->qty_asked.'</td>';
				print '<td align="center">'.$object->lines[$i]->qty_shipped.'</td>';

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

				if ($object->statut == 0 && $num_prod > 0) 
				{
					if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->expedition->livraison->creer))
						|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->expedition->livraison_advance->validate)))
					{
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=valid">'.$langs->trans("Validate").'</a>';
					}
				}

				if ($user->rights->expedition->livraison->supprimer)
				{
					if ($conf->expedition_bon->enabled)
					{
						print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;expid='.$object->origin_id.'&amp;action=delete&amp;backtopage='.urlencode(DOL_URL_ROOT.'/expedition/card.php?id='.$object->origin_id).'">'.$langs->trans("Delete").'</a>';
					}
					else
					{
						print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
					}
				}

				print '</div>';
			}
			print "\n";

			print '<table width="100%" cellspacing="2"><tr><td width="50%" valign="top">';

			/*
		 	 * Documents generated
			 */

			$objectref = dol_sanitizeFileName($object->ref);
			$filedir = $conf->expedition->dir_output . "/receipt/" . $objectref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;

			$genallowed=$user->rights->expedition->livraison->creer;
			$delallowed=$user->rights->expedition->livraison->supprimer;

			$somethingshown=$formfile->show_documents('livraison',$objectref,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,28,0,'','','',$soc->default_lang);

			/*
		 	 * Linked object block (of linked shipment)
		 	 */
			if ($object->origin == 'expedition')
			{
				$shipment = new Expedition($db);
				$shipment->fetch($object->origin_id);

				// Linked object block
				$somethingshown = $form->showLinkedObjectBlock($shipment);

				// Show links to link elements
				//$linktoelem = $form->showLinkToObjectBlock($shipment);
				//if ($linktoelem) print '<br>'.$linktoelem;
			}


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
