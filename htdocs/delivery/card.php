<?php
/* Copyright (C) 2003-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Simon TOSSER			<simon@kornog-computing.com>
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2007		Franky Van Liedekerke	<franky.van.liedekerke@telenet.be>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2015	    Claudio Aschieri		<c.aschieri@19.coop>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/delivery/card.php
 *	\ingroup    livraison
 *	\brief      Page to describe a delivery receipt
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/delivery/class/delivery.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/delivery/modules_delivery.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
if (isModEnabled("product") || isModEnabled("service")) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
}
if (isModEnabled('shipping')) {
	require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
}
if (isModEnabled('stock')) {
	require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
}
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('bills', 'deliveries', 'orders', 'sendings'));

if (isModEnabled('incoterm')) {
	$langs->load('incoterm');
}

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$id = GETPOSTINT('id');


// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('deliverycard', 'globalcard'));

$object = new Delivery($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// fetch optionals attributes lines and labels
$extrafields->fetch_name_optionals_label($object->table_element_line);

// Load object. Make an object->fetch
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'

$error = 0;

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'expedition', $id, 'delivery', 'delivery');

$permissiontoread = $user->hasRight('expedition', 'delivery', 'read');
$permissiontoadd = $user->hasRight('expedition', 'delivery', 'creer'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('expedition', 'delivery', 'supprimer') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissiontovalidate = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('expedition', 'delivery', 'creer')) || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('expedition', 'delivery_advance', 'validate')));
$permissionnote = $user->hasRight('expedition', 'delivery', 'creer'); // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->hasRight('expedition', 'delivery', 'creer'); // Used by the include of actions_dellink.inc.php


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);       // Note that $action and $object may have been modified by some hooks
// Delete Link
$permissiondellink = $user->hasRight('expedition', 'delivery', 'supprimer'); // Used by the include of actions_dellink.inc.php
include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';     // Must be 'include', not 'include_once'

if ($action == 'add' && $permissiontoadd) {
	$db->begin();

	$object->date_delivery = dol_now();
	$object->note          = GETPOST("note", 'restricthtml');
	$object->note_private  = GETPOST("note", 'restricthtml');
	$object->commande_id   = GETPOSTINT("commande_id");
	$object->fk_incoterms  = GETPOSTINT('incoterm_id');

	/* ->entrepot_id seems to not exists
	if (!getDolGlobalInt('MAIN_SUBMODULE_EXPEDITION') && isModEnabled('stock')) {
		$object->entrepot_id = GETPOST('entrepot_id', 'int');
	}*/

	// We loop on each line of order to complete object delivery with qty to delivery
	$commande = new Commande($db);
	$commande->fetch($object->commande_id);
	$commande->fetch_lines();
	$num = count($commande->lines);
	for ($i = 0; $i < $num; $i++) {
		$qty = "qtyl".$i;
		$idl = "idl".$i;
		$qtytouse = price2num(GETPOST($qty));
		if ($qtytouse > 0) {
			$object->addline(GETPOST($idl), price2num($qtytouse), $arrayoptions);
		}
	}

	$ret = $object->create($user);
	if ($ret > 0) {
		$db->commit();
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
		$db->rollback();

		$action = 'create';
	}
} elseif ($action == 'confirm_valid' && $confirm == 'yes' && $permissiontovalidate) {
	$result = $object->valid($user);

	// Define output language
	if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
		$outputlangs = $langs;
		$newlang = '';
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}
		if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
			$newlang = $object->thirdparty->default_lang;
		}
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
		$model = $object->model_pdf;
		$ret = $object->fetch($id); // Reload to get new records

		$result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
		if ($result < 0) {
			dol_print_error($db, $object->error, $object->errors);
		}
	}
}

if ($action == 'confirm_delete' && $confirm == 'yes' && $permissiontodelete) {
	$db->begin();
	$result = $object->delete($user);

	if ($result > 0) {
		$db->commit();
		if (!empty($backtopage)) {
			header("Location: ".$backtopage);
		} else {
			header("Location: ".DOL_URL_ROOT.'/expedition/list.php?restore_lastsearch_values=1');
		}
		exit;
	} else {
		$db->rollback();
	}
}

if ($action == 'setdate_delivery' && $permissiontoadd) {
	$datedelivery = dol_mktime(GETPOSTINT('liv_hour'), GETPOSTINT('liv_min'), 0, GETPOSTINT('liv_month'), GETPOSTINT('liv_day'), GETPOSTINT('liv_year'));
	$result = $object->setDeliveryDate($user, $datedelivery);
	if ($result < 0) {
		$mesg = '<div class="error">'.$object->error.'</div>';
	}
} elseif ($action == 'set_incoterms' && isModEnabled('incoterm')) {
	// Set incoterm
	$result = $object->setIncoterms(GETPOSTINT('incoterm_id'), GETPOST('location_incoterms'));
}

// Update extrafields
if ($action == 'update_extras' && $permissiontoadd) {
	$object->oldcopy = dol_clone($object, 2);

	// Fill array 'array_options' with data from update form
	$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'restricthtml'));
	if ($ret < 0) {
		$error++;
	}

	if (!$error) {
		// Actions on extra fields
		$result = $object->insertExtraFields('DELIVERY_MODIFY');
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
		}
	}

	if ($error) {
		$action = 'edit_extras';
	}
}

// Extrafields line
if ($action == 'update_extras_line' && $permissiontoadd) {
	$array_options = array();
	$num = count($object->lines);

	for ($i = 0; $i < $num; $i++) {
		// Extrafields
		$extralabelsline = $extrafields->fetch_name_optionals_label($object->table_element_line);
		$array_options[$i] = $extrafields->getOptionalsFromPost($extralabelsline, $i);
		// Unset extrafield
		if (is_array($extralabelsline)) {
			// Get extra fields
			foreach ($extralabelsline as $key => $value) {
				unset($_POST["options_".$key]);
			}
		}

		$ret = $object->update_line($object->lines[$i]->id, $array_options[$i]); // extrafields update
		if ($ret < 0) {
			$mesg = '<div class="error">'.$object->error.'</div>';
			$error++;
		}
	}
}


// Actions to build doc
$upload_dir = $conf->expedition->dir_output.'/receipt';
include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';


/*
 *	View
 */

$title = $langs->trans('Delivery');

llxHeader('', $title, 'Livraison', '', 0, 0, '', '', '', 'mod-delivery page-card');

$form = new Form($db);
$formfile = new FormFile($db);

if ($action == 'create') {
	// Create. Seems to no be used
} else {
	// View
	if ($object->id > 0) {
		// Origin of a 'livraison' (delivery receipt) is ALWAYS 'expedition' (shipment).
		// However, origin of shipment in future may differs (commande, proposal, ...)
		$expedition = new Expedition($db);
		$result = $expedition->fetch($object->origin_id);
		$typeobject = $expedition->origin; // example: commande
		if ($object->origin_id > 0) {
			$object->fetch_origin();
		}

		if ($object->id > 0) {
			$soc = new Societe($db);
			$soc->fetch($object->socid);

			$head = delivery_prepare_head($object);

			print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update_extras_line">';
			print '<input type="hidden" name="origin" value="'.$object->origin.'">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print '<input type="hidden" name="ref" value="'.$object->ref.'">';

			print dol_get_fiche_head($head, 'delivery', $langs->trans("Shipment"), -1, 'dolly');

			/*
			 * Confirmation de la suppression
			 *
			 */
			if ($action == 'delete') {
				$expedition_id = GETPOST("expid");
				print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id.'&expid='.$expedition_id.'&backtopage='.urlencode($backtopage), $langs->trans("DeleteDeliveryReceipt"), $langs->trans("DeleteDeliveryReceiptConfirm", $object->ref), 'confirm_delete', '', '', 1);
			}

			/*
			 * Confirmation de la validation
			 */
			if ($action == 'valid') {
				print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans("ValidateDeliveryReceipt"), $langs->trans("ValidateDeliveryReceiptConfirm", $object->ref), 'confirm_valid', '', '', 1);
			}


			/*
			 *   Delivery
			 */

			if ($typeobject == 'commande' && $expedition->origin_id > 0 && isModEnabled('order')) {
				$objectsrc = new Commande($db);
				$objectsrc->fetch($expedition->origin_id);
			}
			if ($typeobject == 'propal' && $expedition->origin_id > 0 && isModEnabled("propal")) {
				$objectsrc = new Propal($db);
				$objectsrc->fetch($expedition->origin_id);
			}

			// Shipment card
			$linkback = '<a href="'.DOL_URL_ROOT.'/expedition/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

			$morehtmlref = '<div class="refidno">';
			// Ref customer shipment
			$morehtmlref .= $form->editfieldkey("RefCustomer", '', $expedition->ref_customer, $expedition, $user->hasRight('expedition', 'creer'), 'string', '', 0, 1);
			$morehtmlref .= $form->editfieldval("RefCustomer", '', $expedition->ref_customer, $expedition, $user->hasRight('expedition', 'creer'), 'string'.(getDolGlobalString('THIRDPARTY_REF_INPUT_SIZE') ? ':' . getDolGlobalString('THIRDPARTY_REF_INPUT_SIZE') : ''), '', null, null, '', 1);
			$morehtmlref .= '<br>'.$langs->trans("RefDeliveryReceipt").' : '.$object->ref;
			// Thirdparty
			$morehtmlref .= '<br>'.$expedition->thirdparty->getNomUrl(1);
			// Project
			if (isModEnabled('project')) {
				$langs->load("projects");
				$morehtmlref .= '<br>';
				if (0) {	// Do not change on shipment
					$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
					if ($action != 'classify') {
						$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
					}
					$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $objectsrc->socid, $objectsrc->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
				} else {
					if (!empty($objectsrc->fk_project)) {
						$proj = new Project($db);
						$proj->fetch($objectsrc->fk_project);
						$morehtmlref .= $proj->getNomUrl(1);
						if ($proj->title) {
							$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
						}
					}
				}
			}
			$morehtmlref .= '</div>';

			$morehtmlstatus = $langs->trans("StatusReceipt").' : '.$object->getLibStatut(6).'<br><br class="small">';

			dol_banner_tab($expedition, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', $morehtmlstatus);


			print '<div class="fichecenter">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield" width="100%">';

			// Shipment
			/*
			if (($object->origin == 'shipment' || $object->origin == 'expedition') && $object->origin_id > 0)
			{
				$linkback = '<a href="'.DOL_URL_ROOT.'/expedition/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

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
			print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
			print "</tr>";
			*/

			// Document origine
			if ($typeobject == 'commande' && $expedition->origin_id && isModEnabled('order')) {
				print '<tr><td class="titlefield">'.$langs->trans("RefOrder").'</td>';
				$order = new Commande($db);
				$order->fetch($expedition->origin_id);
				print '<td colspan="3">';
				print $order->getNomUrl(1, 'commande');
				print "</td>\n";
				print '</tr>';
			}
			if ($typeobject == 'propal' && $expedition->origin_id && isModEnabled("propal")) {
				$propal = new Propal($db);
				$propal->fetch($expedition->origin_id);
				print '<tr><td class="titlefield">'.$langs->trans("RefProposal").'</td>';
				print '<td colspan="3">';
				print $propal->getNomUrl(1, 'expedition');
				print "</td>\n";
				print '</tr>';
			}

			// Date
			print '<tr><td class="titlefield">'.$langs->trans("DateCreation").'</td>';
			print '<td colspan="3">'.dol_print_date($object->date_creation, 'dayhour')."</td>\n";
			print '</tr>';

			// Date delivery real / Received
			print '<tr><td height="10">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('DateReceived');
			print '</td>';

			if ($action != 'editdate_delivery') {
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editdate_delivery&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetDeliveryDate'), 1).'</a></td>';
			}
			print '</tr></table>';
			print '</td><td colspan="2">';
			if ($action == 'editdate_delivery') {
				print '<form name="setdate_delivery" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="setdate_delivery">';
				print $form->selectDate($object->date_delivery ? $object->date_delivery : -1, 'liv_', 1, 1, 0, "setdate_delivery", 1, 1);
				print '<input type="submit" class="button button-edit" value="'.$langs->trans('Modify').'">';
				print '</form>';
			} else {
				print $object->date_delivery ? dol_print_date($object->date_delivery, 'dayhour') : '&nbsp;';
			}
			print '</td>';
			print '</tr>';

			// Incoterms
			if (isModEnabled('incoterm')) {
				print '<tr><td>';
				print '<table class="centpercent nobordernopadding"><tr><td>';
				print $langs->trans('IncotermLabel');
				print '<td><td class="right">';
				if ($user->hasRight('expedition', 'delivery', 'creer')) {
					print '<a class="editfielda" href="'.DOL_URL_ROOT.'/delivery/card.php?id='.$object->id.'&action=editincoterm&token='.newToken().'">'.img_edit().'</a>';
				} else {
					print '&nbsp;';
				}
				print '</td></tr></table>';
				print '</td>';
				print '<td colspan="3">';
				if ($action != 'editincoterm') {
					print $form->textwithpicto($object->display_incoterms(), $object->label_incoterms, 1);
				} else {
					print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms) ? $object->location_incoterms : ''), $_SERVER['PHP_SELF'].'?id='.$object->id);
				}
				print '</td></tr>';
			}

			/* A delivery note should be just more properties of a shipment, so notes are on shipment
			// Note Public
			print '<tr><td>'.$langs->trans("NotePublic").'</td>';
			print '<td colspan="3">';
			print dol_string_onlythesehtmltags(dol_htmlcleanlastbr($object->note_public));
			print "</td></tr>";

			// Note Private
			print '<tr><td>'.$langs->trans("NotePrivate").'</td>';
			print '<td colspan="3">';
			print dol_string_onlythesehtmltags(dol_htmlcleanlastbr($object->note_private));
			print "</td></tr>";
			*/

			// Statut
			/*print '<tr><td>'.$langs->trans("Status").'</td>';
			print '<td colspan="3">'.$object->getLibStatut(4)."</td>\n";
			print '</tr>';*/


			if (!getDolGlobalInt('MAIN_SUBMODULE_EXPEDITION') && isModEnabled('stock')) {
				// Entrepot
				$entrepot = new Entrepot($db);
				$entrepot->fetch($expedition->entrepot_id);
				print '<tr><td width="20%">'.$langs->trans("Warehouse").'</td>';
				print '<td colspan="3"><a href="'.DOL_URL_ROOT.'/product/stock/card.php?id='.$entrepot->id.'">'.$entrepot->label.'</a></td>';
				print '</tr>';
			}

			// Other attributes
			if ($action == 'create_delivery') {
				// copy from expedition
				$extrafields->fetch_name_optionals_label($expedition->table_element);
				if ($expedition->fetch_optionals() > 0) {
					$object->array_options = array_merge($object->array_options, $expedition->array_options);
				}
			}
			$cols = 2;
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

			print "</table><br>\n";

			print '</div>';

			/*
			 * Products lines
			 */

			$num_prod = count($object->lines);
			$i = 0;
			$total = 0;

			print '<table class="noborder centpercent">';

			if ($num_prod) {
				$i = 0;

				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Products").'</td>';
				print '<td class="center">'.$langs->trans("QtyOrdered").'</td>';
				print '<td class="center">'.$langs->trans("QtyReceived").'</td>';
				print "</tr>\n";
			}
			while ($i < $num_prod) {
				$parameters = array('i' => $i, 'line' => $object->lines[$i], 'num' => $num_prod);
				$reshook = $hookmanager->executeHooks('printObjectLine', $parameters, $object, $action);
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}

				if (empty($reshook)) {
					print '<tr class="oddeven">';
					if ($object->lines[$i]->fk_product > 0) {
						$product = new Product($db);
						$product->fetch($object->lines[$i]->fk_product);

						// Define output language
						if (getDolGlobalInt('MAIN_MULTILANGS') && getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE')) {
							$outputlangs = $langs;
							$newlang = '';
							if (empty($newlang) && GETPOST('lang_id', 'aZ09')) {
								$newlang = GETPOST('lang_id', 'aZ09');
							}
							if (empty($newlang)) {
								$newlang = $object->thirdparty->default_lang;
							}
							if (!empty($newlang)) {
								$outputlangs = new Translate("", $conf);
								$outputlangs->setDefaultLang($newlang);
							}

							$label = (!empty($product->multilangs[$outputlangs->defaultlang]["label"])) ? $product->multilangs[$outputlangs->defaultlang]["label"] : $object->lines[$i]->product_label;
						} else {
							$label = (!empty($object->lines[$i]->label) ? $object->lines[$i]->label : $object->lines[$i]->product_label);
						}

						print '<td>';

						// Affiche ligne produit
						$text = '<a href="'.DOL_URL_ROOT.'/product/card.php?id='.$object->lines[$i]->fk_product.'">';
						if ($object->lines[$i]->fk_product_type == 1) {
							$text .= img_object($langs->trans('ShowService'), 'service');
						} else {
							$text .= img_object($langs->trans('ShowProduct'), 'product');
						}
						$text .= ' '.$object->lines[$i]->product_ref.'</a>';
						$text .= ' - '.$label;
						$description = (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE') ? '' : dol_htmlentitiesbr($object->lines[$i]->description));
						//print $description;
						print $form->textwithtooltip($text, $description, 3, '', '', $i);
						//print_date_range($object->lines[$i]->date_start, $object->lines[$i]->date_end);
						if (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE')) {
							print (!empty($object->lines[$i]->description) && $object->lines[$i]->description != $object->lines[$i]->product_label) ? '<br>'.dol_htmlentitiesbr($object->lines[$i]->description) : '';
						}
					} else {
						print "<td>";
						if ($object->lines[$i]->fk_product_type == 1) {
							$text = img_object($langs->trans('Service'), 'service');
						} else {
							$text = img_object($langs->trans('Product'), 'product');
						}

						if (!empty($object->lines[$i]->label)) {
							$text .= ' <strong>'.$object->lines[$i]->label.'</strong>';
							print $form->textwithtooltip($text, $object->lines[$i]->description, 3, '', '', $i);
						} else {
							print $text.' '.nl2br($object->lines[$i]->description);
						}

						//print_date_range($objp->date_start, $objp->date_end);
						print "</td>\n";
					}

					print '<td class="center">'.$object->lines[$i]->qty_asked.'</td>';
					print '<td class="center">'.$object->lines[$i]->qty_shipped.'</td>';

					print "</tr>";

					// Display lines extrafields
					//if (!empty($extrafields)) {
					$colspan = 2;
					$mode = ($object->statut == 0) ? 'edit' : 'view';

					$object->lines[$i]->fetch_optionals();

					if ($action == 'create_delivery') {
						$srcLine = new ExpeditionLigne($db);

						$extrafields->fetch_name_optionals_label($srcLine->table_element);
						$srcLine->id = $expedition->lines[$i]->id;
						$srcLine->fetch_optionals();

						$object->lines[$i]->array_options = array_merge($object->lines[$i]->array_options, $srcLine->array_options);
					} else {
						$srcLine = new DeliveryLine($db);
						$extrafields->fetch_name_optionals_label($srcLine->table_element);
					}
					print $object->lines[$i]->showOptionals($extrafields, $mode, array('style' => 'class="oddeven"', 'colspan' => $colspan), '');
					//}
				}

				$i++;
			}

			print "</table>\n";

			print dol_get_fiche_end();

			//if ($object->statut == 0)	// only if draft
			// print $form->buttonsSaveCancel("Save", '');

			print '</form>';


			/*
			 *    Boutons actions
			 */

			if ($user->socid == 0) {
				print '<div class="tabsAction">';

				if ($object->statut == 0 && $num_prod > 0) {
					if ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('expedition', 'delivery', 'creer'))
						|| (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('expedition', 'delivery_advance', 'validate'))) {
						print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER["PHP_SELF"].'?action=valid&amp;token='.newToken().'&amp;id='.$object->id, '');
					}
				}

				if ($user->hasRight('expedition', 'delivery', 'supprimer')) {
					if (getDolGlobalInt('MAIN_SUBMODULE_EXPEDITION')) {
						print dolGetButtonAction('', $langs->trans('Delete'), 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;expid='.$object->origin_id.'&amp;action=delete&amp;token='.newToken().'&amp;backtopage='.urlencode(DOL_URL_ROOT.'/expedition/card.php?id='.$object->origin_id), '');
					} else {
						print dolGetButtonAction('', $langs->trans('Delete'), 'delete', $_SERVER["PHP_SELF"].'?action=delete&amp;token='.newToken().'&amp;id='.$object->id, '');
					}
				}

				print '</div>';
			}
			print "\n";

			print '<div class="fichecenter"><div class="fichehalfleft">';

			/*
			  * Documents generated
			 */

			$objectref = dol_sanitizeFileName($object->ref);
			$filedir = $conf->expedition->dir_output."/receipt/".$objectref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;

			$genallowed = $user->hasRight('expedition', 'delivery', 'lire');
			$delallowed = $user->hasRight('expedition', 'delivery', 'creer');

			print $formfile->showdocuments('delivery', $objectref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang);

			/*
			  * Linked object block (of linked shipment)
			  */
			if ($object->origin == 'expedition') {
				$shipment = new Expedition($db);
				$shipment->fetch($object->origin_id);

				// Show links to link elements
				//$tmparray = $form->showLinkToObjectBlock($object, null, array('order'), 1);
				$somethingshown = $form->showLinkedObjectBlock($object, '');
			}


			print '</div><div class="fichehalfright">';

			// Nothing on right

			print '</div></div>';
		} else {
			/* Expedition non trouvee */
			print "Expedition inexistante ou access refuse";
		}
	} else {
		/* Expedition non trouvee */
		print "Expedition inexistante ou access refuse";
	}
}

// End of page
llxFooter();
$db->close();
