<?php
/* Copyright (C) 2003-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Simon TOSSER			<simon@kornog-computing.com>
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2007		Franky Van Liedekerke	<franky.van.liedekerke@telenet.be>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2015			  Claudio Aschieri		<c.aschieri@19.coop>
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
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
if (! empty($conf->expedition_bon->enabled))
	require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
if (! empty($conf->stock->enabled))
	require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
if (! empty($conf->projet->enabled)) {
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}


$langs->load("sendings");
$langs->load("bills");
$langs->load('deliveries');
$langs->load('orders');
if (!empty($conf->incoterm->enabled)) $langs->load('incoterm');

$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');
$backtopage=GETPOST('backtopage','alpha');

// Security check
$id = GETPOST('id', 'int');
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'expedition',$id,'livraison','livraison');

$object = new Livraison($db);
$extrafields = new ExtraFields($db);
$extrafieldsline = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// fetch optionals attributes lines and labels
$extralabelslines=$extrafieldsline->fetch_name_optionals_label($object->table_element_line);

// Load object. Make an object->fetch
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
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
		setEventMessages($object->error, $object->errors, 'errors');
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
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang = GETPOST('lang_id','aZ09');
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
		else header("Location: ".DOL_URL_ROOT.'/expedition/list.php?restore_lastsearch_values=1');
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

// Update extrafields
if ($action == 'update_extras')
{
	// Fill array 'array_options' with data from update form
	$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
	$ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute'));
	if ($ret < 0) $error++;

	if (! $error)
	{
		// Actions on extra fields (by external module or standard code)
		// TODO le hook fait double emploi avec le trigger !!
		$hookmanager->initHooks(array('livraisondao'));
		$parameters = array('id' => $object->id);
		$reshook = $hookmanager->executeHooks('insertExtraFields', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
		if (empty($reshook)) {
			$result = $object->insertExtraFields();
			if ($result < 0)
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		} else if ($reshook < 0)
			$error++;
	}

	if ($error)
		$action = 'edit_extras';
}

// Extrafields line
if ($action == 'update_extras_line')
{
	$array_options=array();
	$num=count($object->lines);

	for ($i = 0; $i < $num; $i++)
	{
		// Extrafields
		$extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
		$array_options[$i] = $extrafieldsline->getOptionalsFromPost($extralabelsline, $i);
		// Unset extrafield
		if (is_array($extralabelsline)) {
			// Get extra fields
			foreach ($extralabelsline as $key => $value) {
				unset($_POST["options_" . $key]);
			}
		}

		$ret = $object->update_line($object->lines[$i]->id,$array_options[$i]);	// extrafields update
		if ($ret < 0)
		{
			$mesg='<div class="error">'.$object->error.'</div>';
			$error++;
		}
	}

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
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang=GETPOST('lang_id','aZ09');
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->thirdparty->default_lang;
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
	if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
	else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
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
if ($action == 'create')    // Seems to no be used
{


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
		// Origin of a 'livraison' (delivery receipt) is ALWAYS 'expedition' (shipment).
		// However, origin of shipment in future may differs (commande, proposal, ...)
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


			print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="update_extras_line">';
			print '<input type="hidden" name="origin" value="'.$origin.'">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print '<input type="hidden" name="ref" value="'.$object->ref.'">';

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

			if ($typeobject == 'commande' && $expedition->origin_id > 0 && ! empty($conf->commande->enabled))
			{
			    $objectsrc=new Commande($db);
			    $objectsrc->fetch($expedition->origin_id);
			}
			if ($typeobject == 'propal' && $expedition->origin_id > 0 && ! empty($conf->propal->enabled))
			{
			    $objectsrc=new Propal($db);
			    $objectsrc->fetch($expedition->origin_id);
			}

			// Shipment card
			$linkback = '<a href="'.DOL_URL_ROOT.'/expedition/list.php?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">'.$langs->trans("BackToList").'</a>';

			$morehtmlref='<div class="refidno">';
			// Ref customer shipment
			$morehtmlref.=$form->editfieldkey("RefCustomer", '', $expedition->ref_customer, $expedition, $user->rights->expedition->creer, 'string', '', 0, 1);
			$morehtmlref.=$form->editfieldval("RefCustomer", '', $expedition->ref_customer, $expedition, $user->rights->expedition->creer, 'string', '', null, null, '', 1);
			$morehtmlref.='<br>'.$langs->trans("RefDeliveryReceipt").' : '.$object->ref;
			// Thirdparty
			$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $expedition->thirdparty->getNomUrl(1);
			// Project
			if (! empty($conf->projet->enabled)) {
			    $langs->load("projects");
			    $morehtmlref .= '<br>' . $langs->trans('Project') . ' ';
			    if (0) {    // Do not change on shipment
			        if ($action != 'classify') {
			            $morehtmlref .= '<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $expedition->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
			        }
			        if ($action == 'classify') {
			            // $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $expedition->id, $expedition->socid, $expedition->fk_project, 'projectid', 0, 0, 1, 1);
			            $morehtmlref .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id=' . $expedition->id . '">';
			            $morehtmlref .= '<input type="hidden" name="action" value="classin">';
			            $morehtmlref .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			            $morehtmlref .= $formproject->select_projects($expedition->socid, $expedition->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
			            $morehtmlref .= '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
			            $morehtmlref .= '</form>';
			        } else {
			            $morehtmlref .= $form->form_project($_SERVER['PHP_SELF'] . '?id=' . $expedition->id, $expedition->socid, $expedition->fk_project, 'none', 0, 0, 0, 1);
			        }
			    } else {
			        $morehtmlref .= ' : ';
			        if (! empty($objectsrc->fk_project)) {
			            $proj = new Project($db);
			            $proj->fetch($objectsrc->fk_project);
			            $morehtmlref .= '<a href="' . DOL_URL_ROOT . '/projet/card.php?id=' . $objectsrc->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
			            $morehtmlref .= $proj->ref;
			            $morehtmlref .= '</a>';
			        } else {
			            $morehtmlref .= '';
			        }
			    }
			}
			$morehtmlref.='</div>';

			$morehtmlright = $langs->trans("StatusReceipt").' : '.$object->getLibStatut(6).'<br>';

			dol_banner_tab($expedition, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', $morehtmlright);


			print '<div class="fichecenter">';
			print '<div class="underbanner clearboth"></div>';

		    print '<table class="border" width="100%">';

			// Shipment
			/*
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
    		print '<td colspan="3">';
    		print $object->ref;
    		print '</td></tr>';

			// Client
			print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
			print '<td align="3">'.$soc->getNomUrl(1).'</td>';
			print "</tr>";
            */

			// Document origine
			if ($typeobject == 'commande' && $expedition->origin_id && ! empty($conf->commande->enabled))
			{
				print '<tr><td class="titlefield">'.$langs->trans("RefOrder").'</td>';
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
				print '<tr><td class="titlefield">'.$langs->trans("RefProposal").'</td>';
				print '<td colspan="3">';
				print $propal->getNomUrl(1,'expedition');
				print "</td>\n";
				print '</tr>';
			}

			// Date
			print '<tr><td class="titlefield">'.$langs->trans("DateCreation").'</td>';
			print '<td colspan="3">'.dol_print_date($object->date_creation,'dayhour')."</td>\n";
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
				$form->select_date($object->date_delivery?$object->date_delivery:-1, 'liv_', 1, 1, '', "setdate_livraison", 1, 1);
				print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
				print '</form>';
			}
			else
			{
				print $object->date_delivery ? dol_print_date($object->date_delivery,'dayhour') : '&nbsp;';
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

			/* A delivery note should be just more properties of a shipment, so notes are on shipment
			// Note Public
            print '<tr><td>'.$langs->trans("NotePublic").'</td>';
            print '<td colspan="3">';
            print nl2br($object->note_public);
            print "</td></tr>";

			// Note Private
            print '<tr><td>'.$langs->trans("NotePrivate").'</td>';
            print '<td colspan="3">';
            print nl2br($object->note_private);
            print "</td></tr>";
            */

			// Statut
			/*print '<tr><td>'.$langs->trans("Status").'</td>';
			print '<td colspan="3">'.$object->getLibStatut(4)."</td>\n";
			print '</tr>';*/

			if (!$conf->expedition_bon->enabled && ! empty($conf->stock->enabled))
			{
				// Entrepot
				$entrepot = new Entrepot($db);
				$entrepot->fetch($object->entrepot_id);
				print '<tr><td width="20%">'.$langs->trans("Warehouse").'</td>';
				print '<td colspan="3"><a href="'.DOL_URL_ROOT.'/product/stock/card.php?id='.$entrepot->id.'">'.$entrepot->libelle.'</a></td>';
				print '</tr>';
			}

			// Other attributes
			if ($action = 'create_delivery') {
				// copy from expedition
				$expeditionExtrafields = new Extrafields($db);
				$expeditionExtrafieldLabels = $expeditionExtrafields->fetch_name_optionals_label($expedition->table_element);
				if ($expedition->fetch_optionals($object->origin_id, $expeditionExtrafieldLabels) > 0) {
					$object->array_options = array_merge($object->array_options, $expedition->array_options);
				}
			}
			$cols = 2;
			include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

			print "</table><br>\n";

			print '</div>';

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


				print '<tr class="oddeven">';
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
						if (empty($newlang)) $newlang=$object->thirdparty->default_lang;
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

				//Display lines extrafields
				if (is_array($extralabelslines) && count($extralabelslines)>0) {
					$colspan=2;
					$mode = ($object->statut == 0) ? 'edit' : 'view';
					$line = new LivraisonLigne($db);
					$line->fetch_optionals($object->lines[$i]->id,$extralabelslines);
					if ($action = 'create_delivery') {
						$srcLine = new ExpeditionLigne($db);
						$expeditionLineExtrafields = new Extrafields($db);
						$expeditionLineExtrafieldLabels = $expeditionLineExtrafields->fetch_name_optionals_label($srcLine->table_element);
						$srcLine->fetch_optionals($expedition->lines[$i]->id,$expeditionLineExtrafieldLabels);
						$line->array_options = array_merge($line->array_options, $srcLine->array_options);
					}
					print '<tr class="oddeven">';
					print $line->showOptionals($extrafieldsline, $mode, array('style'=>$bc[$var], 'colspan'=>$colspan),$i);
					print '</tr>';
				}

				$i++;
			}

			print "</table>\n";

            dol_fiche_end();

			//if ($object->statut == 0)	// only if draft
			//	print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';

			print '</form>';


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

			$genallowed=$user->rights->expedition->livraison->lire;
			$delallowed=$user->rights->expedition->livraison->creer;

			print $formfile->showdocuments('livraison',$objectref,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,28,0,'','','',$soc->default_lang);

			/*
		 	 * Linked object block (of linked shipment)
		 	 */
			if ($object->origin == 'expedition')
			{
				$shipment = new Expedition($db);
				$shipment->fetch($object->origin_id);

    			// Show links to link elements
    			//$linktoelem = $form->showLinkToObjectBlock($object, null, array('order'));
    			$somethingshown = $form->showLinkedObjectBlock($object, '');
			}


			print '</td><td valign="top" width="50%">';

			// Rien a droite

			print '</td></tr></table>';
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
