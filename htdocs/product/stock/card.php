<?php
/* Copyright (C) 2003-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Simon Tosser			<simon@kornog-computing.com>
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2016	    Francis Appels       	<francis.appels@yahoo.com>
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
 *	\file       htdocs/product/stock/card.php
 *	\ingroup    stock
 *	\brief      Page fiche entrepot
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'companies', 'categories'));

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm');

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
if (!$sortfield) $sortfield = "p.ref";
if (!$sortorder) $sortorder = "DESC";

$backtopage = GETPOST('backtopage', 'alpha');

// Security check
//$result=restrictedArea($user,'stock', $id, 'entrepot&stock');
$result = restrictedArea($user, 'stock');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('warehousecard', 'globalcard'));

$object = new Entrepot($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
	if ($ret <= 0) {
		setEventMessages($object->error, $object->errors, 'errors');
		$action = '';
	}
}


/*
 * Actions
 */

$error = 0;

$usercanread = (($user->rights->stock->lire));
$usercancreate = (($user->rights->stock->creer));
$usercandelete = (($user->rights->stock->supprimer));

$parameters = array('id'=>$id, 'ref'=>$ref);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
if (empty($reshook))
{
	// Ajout entrepot
	if ($action == 'add' && $user->rights->stock->creer)
	{
		$object->ref = (string) GETPOST("ref", "alpha");
		$object->fk_parent = (int) GETPOST("fk_parent", "int");
		$object->label = (string) GETPOST("libelle", "alpha");
		$object->description = (string) GETPOST("desc", "alpha");
		$object->statut      = GETPOST("statut");
		$object->lieu = (string) GETPOST("lieu", "alpha");
		$object->address = (string) GETPOST("address", "alpha");
		$object->zip = (string) GETPOST("zipcode", "alpha");
		$object->town = (string) GETPOST("town", "alpha");
		$object->country_id = GETPOST("country_id");
		$object->phone = (string) GETPOST("phone", "alpha");
		$object->fax = (string) GETPOST("fax", "alpha");

		if (!empty($object->label)) {
			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) {
				$error++;
				$action = 'create';
			}

			if (!$error) {
				$id = $object->create($user);
				if ($id > 0) {
					setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');

					$categories = GETPOST('categories', 'array');
					$object->setCategories($categories);
					if (!empty($backtopage)) {
						header("Location: ".$backtopage);
						exit;
					} else {
						header("Location: card.php?id=".$id);
						exit;
					}
				} else {
					$action = 'create';
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		} else {
			setEventMessages($langs->trans("ErrorWarehouseRefRequired"), null, 'errors');
			$action = "create"; // Force retour sur page creation
		}
	}

	// Delete warehouse
	if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->stock->supprimer)
	{
		$object->fetch(GETPOST('id', 'int'));
		$result = $object->delete($user);
		if ($result > 0)
		{
			setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
			header("Location: ".DOL_URL_ROOT.'/product/stock/list.php?restore_lastsearch_values=1');
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		}
	}

	// Modification entrepot
	if ($action == 'update' && $cancel <> $langs->trans("Cancel"))
	{
		if ($object->fetch($id))
		{
			$object->label = GETPOST("libelle");
			$object->fk_parent   = GETPOST("fk_parent");
			$object->description = GETPOST("desc");
			$object->statut      = GETPOST("statut");
			$object->lieu        = GETPOST("lieu");
			$object->address     = GETPOST("address");
			$object->zip         = GETPOST("zipcode");
			$object->town        = GETPOST("town");
			$object->country_id  = GETPOST("country_id");
			$object->phone = GETPOST("phone");
			$object->fax = GETPOST("fax");

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0)   $error++;

			if (!$error) {
				$ret = $object->update($id, $user);
				if ($ret < 0)   $error++;
			}

			if ($error) {
				$action = 'edit';
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				$categories = GETPOST('categories', 'array');
				$object->setCategories($categories);
				$action = '';
			}
		} else {
			$action = 'edit';
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'update_extras') {
		$object->oldcopy = dol_clone($object);

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'restricthtml'));
		if ($ret < 0) $error++;
		if (!$error) {
			$result = $object->insertExtraFields();
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}
		if ($error) $action = 'edit_extras';
	}

	if ($cancel == $langs->trans("Cancel"))
	{
		$action = '';
	}


	// Actions to build doc
	$upload_dir = $conf->stock->dir_output;
	$permissiontoadd = $user->rights->stock->creer;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}


/*
 * View
 */

$productstatic = new Product($db);
$form = new Form($db);
$formproduct = new FormProduct($db);
$formcompany = new FormCompany($db);
$formfile = new FormFile($db);

$help_url = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
llxHeader("", $langs->trans("WarehouseCard"), $help_url);


if ($action == 'create')
{
	print load_fiche_titre($langs->trans("NewWarehouse"), '', 'stock');

	dol_set_focus('input[name="libelle"]');

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	// Ref
	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td><input name="libelle" size="20" value=""></td></tr>';

	print '<tr><td>'.$langs->trans("LocationSummary").'</td><td><input name="lieu" size="40" value="'.(!empty($object->lieu) ? $object->lieu : '').'"></td></tr>';

	// Parent entrepot
	print '<tr><td>'.$langs->trans("AddIn").'</td><td>';
	print img_picto('', 'stock').$formproduct->selectWarehouses((GETPOSTISSET('fk_parent') ? GETPOST('fk_parent', 'int') : 'ifone'), 'fk_parent', '', 1);
	print '</td></tr>';

	// Description
	print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
	// Editeur wysiwyg
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('desc', (!empty($object->description) ? $object->description : ''), '', 180, 'dolibarr_notes', 'In', false, true, $conf->fckeditor->enabled, ROWS_5, '90%');
	$doleditor->Create();
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Address').'</td><td><textarea name="address" class="quatrevingtpercent" rows="3" wrap="soft">';
	print (!empty($object->address) ? $object->address : '');
	print '</textarea></td></tr>';

	// Zip / Town
	print '<tr><td>'.$langs->trans('Zip').'</td><td>';
	print $formcompany->select_ziptown((!empty($object->zip) ? $object->zip : ''), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
	print '</td></tr>';
	print '<tr><td>'.$langs->trans('Town').'</td><td>';
	print $formcompany->select_ziptown((!empty($object->town) ? $object->town : ''), 'town', array('zipcode', 'selectcountry_id', 'state_id'));
	print '</td></tr>';

	// Country
	print '<tr><td>'.$langs->trans('Country').'</td><td>';
	print img_picto('', 'globe-americas', 'class="paddingright"');
	print $form->select_country((!empty($object->country_id) ? $object->country_id : $mysoc->country_code), 'country_id');
	if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
	print '</td></tr>';

	// Phone / Fax
	print '<tr><td class="titlefieldcreate">'.$form->editfieldkey('Phone', 'phone', '', $object, 0).'</td><td>';
	print img_picto('', 'object_phoning', 'class="paddingright"');
	print '<input name="phone" size="20" value="'.$object->phone.'"></td></tr>';
	print '<tr><td class="titlefieldcreate">'.$form->editfieldkey('Fax', 'fax', '', $object, 0).'</td>';
	print '<td>';
	print img_picto('', 'object_phoning_fax', 'class="paddingright"');
	print '<input name="fax" size="20" value="'.$object->fax.'"></td></tr>';

	// Status
	print '<tr><td>'.$langs->trans("Status").'</td><td>';
	print '<select name="statut" class="flat">';
	foreach ($object->statuts as $key => $value)
	{
		if ($key == 1)
		{
			print '<option value="'.$key.'" selected>'.$langs->trans($value).'</option>';
		} else {
			print '<option value="'.$key.'">'.$langs->trans($value).'</option>';
		}
	}
	print '</select>';
	print '</td></tr>';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	if ($conf->categorie->enabled) {
		// Categories
		print '<tr><td>'.$langs->trans("Categories").'</td><td colspan="3">';
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_WAREHOUSE, '', 'parent', 64, 0, 1);
		print img_picto('', 'category').$form->multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
		print "</td></tr>";
	}
	print '</table>';

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Create").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button button-cancel" value="'.$langs->trans("Cancel").'" onClick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';
} else {
	$id = GETPOST("id", 'int');
	if ($id > 0 || $ref)
	{
		$object = new Entrepot($db);
		$result = $object->fetch($id, $ref);
		if ($result <= 0)
		{
			print 'No record found';
			exit;
		}

		/*
		 * Affichage fiche
		 */
		if ($action <> 'edit' && $action <> 're-edit')
		{
			$head = stock_prepare_head($object);

			print dol_get_fiche_head($head, 'card', $langs->trans("Warehouse"), -1, 'stock');

			$formconfirm = '';

			// Confirm delete warehouse
			if ($action == 'delete')
			{
				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("DeleteAWarehouse"), $langs->trans("ConfirmDeleteWarehouse", $object->label), "confirm_delete", '', 0, 2);
			}

			// Call Hook formConfirm
			$parameters = array('formConfirm' => $formconfirm);
			$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
			elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

			// Print form confirm
			print $formconfirm;

			// Warehouse card
			$linkback = '<a href="'.DOL_URL_ROOT.'/product/stock/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

			$morehtmlref = '<div class="refidno">';
			$morehtmlref .= $langs->trans("LocationSummary").' : '.$object->lieu;
			$morehtmlref .= '</div>';

			$shownav = 1;
			if ($user->socid && !in_array('stock', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

			dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref', 'ref', $morehtmlref);

			print '<div class="fichecenter">';
			print '<div class="fichehalfleft">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border centpercent tableforfield">';

			// Parent entrepot
			$parentwarehouse = new Entrepot($db);
			if (!empty($object->fk_parent) && $parentwarehouse->fetch($object->fk_parent) > 0) {
				print '<tr><td>'.$langs->trans("ParentWarehouse").'</td><td>';
				print $parentwarehouse->getNomUrl(3);
				print '</td></tr>';
			}

			// Description
			print '<tr><td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>'.nl2br($object->description).'</td></tr>';

			$calcproductsunique = $object->nb_different_products();
			$calcproducts = $object->nb_products();

			// Total nb of different products
			print '<tr><td>'.$langs->trans("NumberOfDifferentProducts").'</td><td>';
			print empty($calcproductsunique['nb']) ? '0' : $calcproductsunique['nb'];
			print "</td></tr>";

			// Nb of products
			print '<tr><td>'.$langs->trans("NumberOfProducts").'</td><td>';
			$valtoshow = price2num($calcproducts['nb'], 'MS');
			print empty($valtoshow) ? '0' : $valtoshow;
			print "</td></tr>";

			print '</table>';

			print '</div>';
			print '<div class="fichehalfright">';
			print '<div class="ficheaddleft">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border centpercent tableforfield">';

			// Value
			print '<tr><td class="titlefield">'.$langs->trans("EstimatedStockValueShort").'</td><td>';
			print price((empty($calcproducts['value']) ? '0' : price2num($calcproducts['value'], 'MT')), 0, $langs, 0, -1, -1, $conf->currency);
			print "</td></tr>";

			// Last movement
			if (!empty($user->rights->stock->mouvement->lire)) {
				$sql = "SELECT max(m.datem) as datem";
				$sql .= " FROM ".MAIN_DB_PREFIX."stock_mouvement as m";
				$sql .= " WHERE m.fk_entrepot = ".((int) $object->id);
				$resqlbis = $db->query($sql);
				if ($resqlbis) {
					$obj = $db->fetch_object($resqlbis);
					$lastmovementdate = $db->jdate($obj->datem);
				} else {
					dol_print_error($db);
				}
				print '<tr><td>'.$langs->trans("LastMovement").'</td><td>';
				if ($lastmovementdate) {
					print dol_print_date($lastmovementdate, 'dayhour').' ';
					print '(<a href="'.DOL_URL_ROOT.'/product/stock/movement_list.php?id='.$object->id.'">'.$langs->trans("FullList").'</a>)';
				} else {
					print $langs->trans("None");
				}
				print "</td></tr>";
			}

			// Other attributes
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

			// Categories
			if ($conf->categorie->enabled) {
				print '<tr><td valign="middle">'.$langs->trans("Categories").'</td><td colspan="3">';
				print $form->showCategories($object->id, Categorie::TYPE_WAREHOUSE, 1);
				print "</td></tr>";
			}

			print "</table>";

			print '</div>';
			print '</div>';
			print '</div>';

			print '<div class="clearboth"></div>';

			print dol_get_fiche_end();


			/* ************************************************************************** */
			/*                                                                            */
			/* Barre d'action                                                             */
			/*                                                                            */
			/* ************************************************************************** */

			print "<div class=\"tabsAction\">\n";

			$parameters = array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if (empty($reshook))
			{
				if (empty($action))
				{
					if ($user->rights->stock->creer) {
						print '<a class="butAction" href="card.php?action=edit&token='.newToken().'&id='.$object->id.'">'.$langs->trans("Modify").'</a>';
					}
					else {
						print '<a class="butActionRefused classfortooltip" href="#">'.$langs->trans("Modify").'</a>';
					}

					if ($user->rights->stock->supprimer) {
						print '<a class="butActionDelete" href="card.php?action=delete&token='.newToken().'&id='.$object->id.'">'.$langs->trans("Delete").'</a>';
					}
					else {
						print '<a class="butActionRefused classfortooltip" href="#">'.$langs->trans("Delete").'</a>';
					}
				}
			}

			print "</div>";


			/* ************************************************************************** */
			/*                                                                            */
			/* Affichage de la liste des produits de l'entrepot                           */
			/*                                                                            */
			/* ************************************************************************** */
			print '<br>';

			print '<table class="noborder centpercent">';
			print "<tr class=\"liste_titre\">";
			print_liste_field_titre("Product", "", "p.ref", "&amp;id=".$id, "", "", $sortfield, $sortorder);
			print_liste_field_titre("Label", "", "p.label", "&amp;id=".$id, "", "", $sortfield, $sortorder);
			print_liste_field_titre("Units", "", "ps.reel", "&amp;id=".$id, "", '', $sortfield, $sortorder, 'right ');
			if (!empty($conf->global->PRODUCT_USE_UNITS)) print_liste_field_titre("Unit", "", "p.fk_unit", "&amp;id=".$id, "", 'align="left"', $sortfield, $sortorder);
			print_liste_field_titre("AverageUnitPricePMPShort", "", "p.pmp", "&amp;id=".$id, "", '', $sortfield, $sortorder, 'right ');
			print_liste_field_titre("EstimatedStockValueShort", "", "", "&amp;id=".$id, "", '', $sortfield, $sortorder, 'right ');
			if (empty($conf->global->PRODUIT_MULTIPRICES)) {
				print_liste_field_titre("SellPriceMin", "", "p.price", "&amp;id=".$id, "", '', $sortfield, $sortorder, 'right ');
			}
			if (empty($conf->global->PRODUIT_MULTIPRICES)) {
				print_liste_field_titre("EstimatedStockValueSellShort", "", "", "&amp;id=".$id, "", '', $sortfield, $sortorder, 'right ');
			}
			if ($user->rights->stock->mouvement->creer) {
				print_liste_field_titre('');
			}
			if ($user->rights->stock->creer) {
				print_liste_field_titre('');
			}
			print "</tr>\n";

			$totalunit = 0;
			$totalvalue = $totalvaluesell = 0;

			$sql = "SELECT p.rowid as rowid, p.ref, p.label as produit, p.tobatch, p.fk_product_type as type, p.pmp as ppmp, p.price, p.price_ttc, p.entity,";
			$sql .= " ps.reel as value";
			if (!empty($conf->global->PRODUCT_USE_UNITS)) $sql .= ",fk_unit";
			$sql .= " FROM ".MAIN_DB_PREFIX."product_stock as ps, ".MAIN_DB_PREFIX."product as p";
			$sql .= " WHERE ps.fk_product = p.rowid";
			$sql .= " AND ps.reel <> 0"; // We do not show if stock is 0 (no product in this warehouse)
			$sql .= " AND ps.fk_entrepot = ".$object->id;
			$sql .= $db->order($sortfield, $sortorder);

			dol_syslog('List products', LOG_DEBUG);
			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				$i = 0;
				while ($i < $num)
				{
					$objp = $db->fetch_object($resql);

					// Multilangs
					if (!empty($conf->global->MAIN_MULTILANGS)) // si l'option est active
					{
						$sql = "SELECT label";
						$sql .= " FROM ".MAIN_DB_PREFIX."product_lang";
						$sql .= " WHERE fk_product=".$objp->rowid;
						$sql .= " AND lang='".$db->escape($langs->getDefaultLang())."'";
						$sql .= " LIMIT 1";

						$result = $db->query($sql);
						if ($result)
						{
							$objtp = $db->fetch_object($result);
							if ($objtp->label != '') $objp->produit = $objtp->label;
						}
					}


					//print '<td>'.dol_print_date($objp->datem).'</td>';
					print '<tr class="oddeven">';
					print "<td>";
					$productstatic->id = $objp->rowid;
					$productstatic->ref = $objp->ref;
					$productstatic->label = $objp->produit;
					$productstatic->type = $objp->type;
					$productstatic->entity = $objp->entity;
					$productstatic->status_batch = $objp->tobatch;
					$productstatic->fk_unit = $objp->fk_unit;
					print $productstatic->getNomUrl(1, 'stock', 16);
					print '</td>';

					// Label
					print '<td>'.$objp->produit.'</td>';

					print '<td class="right">';
					$valtoshow = price(price2num($objp->value, 'MS'), 0, '', 0, 0); // TODO replace with a qty() function
					print empty($valtoshow) ? '0' : $valtoshow;
					print '</td>';
					$totalunit += $objp->value;

					if (!empty($conf->global->PRODUCT_USE_UNITS)) {
						// Units
						print '<td align="left">';
						if (is_null($productstatic->fk_unit))$productstatic->fk_unit = 1;
						print $langs->trans($productstatic->getLabelOfUnit());
						print '</td>';
					}
					// Price buy PMP
					print '<td class="right">'.price(price2num($objp->ppmp, 'MU')).'</td>';

					// Total PMP
					print '<td class="right">'.price(price2num($objp->ppmp * $objp->value, 'MT')).'</td>';
					$totalvalue += price2num($objp->ppmp * $objp->value, 'MT');

					// Price sell min
					if (empty($conf->global->PRODUIT_MULTIPRICES))
					{
						$pricemin = $objp->price;
						print '<td class="right">';
						print price(price2num($pricemin, 'MU'), 1);
						print '</td>';
						// Total sell min
						print '<td class="right">';
						print price(price2num($pricemin * $objp->value, 'MT'), 1);
						print '</td>';
					}
					$totalvaluesell += price2num($pricemin * $objp->value, 'MT');

					if ($user->rights->stock->mouvement->creer)
					{
						print '<td class="center"><a href="'.DOL_URL_ROOT.'/product/stock/product.php?dwid='.$object->id.'&id='.$objp->rowid.'&action=transfert&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$id).'">';
						print img_picto($langs->trans("StockMovement"), 'uparrow.png', 'class="hideonsmartphone"').' '.$langs->trans("StockMovement");
						print "</a></td>";
					}

					if ($user->rights->stock->creer)
					{
						print '<td class="center"><a href="'.DOL_URL_ROOT.'/product/stock/product.php?dwid='.$object->id.'&id='.$objp->rowid.'&action=correction&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$id).'">';
						print $langs->trans("StockCorrection");
						print "</a></td>";
					}
					if (!empty($conf->global->PRODUCT_USE_UNITS)) {
						if ($i == 0) $units = $productstatic->fk_unit;
						elseif ($productstatic->fk_unit != $units) $sameunits = false;
					}
					print "</tr>";
					$i++;
				}
				$db->free($resql);

				print '<tr class="liste_total"><td class="liste_total" colspan="2">'.$langs->trans("Total").'</td>';
				print '<td class="liste_total right">';
				$valtoshow = price2num($totalunit, 'MS');
				if (empty($conf->global->PRODUCT_USE_UNITS) || $sameunits) print empty($valtoshow) ? '0' : $valtoshow;
				print '</td>';
				print '<td class="liste_total">';
				if (empty($conf->global->PRODUCT_USE_UNITS) && $sameunits) print $langs->trans($productstatic->getLabelOfUnit());
				print '</td>';
				print '<td class="liste_total right">'.price(price2num($totalvalue, 'MT')).'</td>';
				if (empty($conf->global->PRODUIT_MULTIPRICES))
				{
					print '<td class="liste_total">&nbsp;</td>';
					print '<td class="liste_total right">'.price(price2num($totalvaluesell, 'MT')).'</td>';
				}
				print '<td class="liste_total">&nbsp;</td>';
				print '<td class="liste_total">&nbsp;</td>';
				print '</tr>';
			} else {
				dol_print_error($db);
			}
			print "</table>\n";
		}


		/*
		 * Edition fiche
		 */
		if ($action == 'edit' || $action == 're-edit')
		{
			$langs->trans("WarehouseEdit");

			print '<form action="card.php" method="POST">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';

			$head = stock_prepare_head($object);

			print dol_get_fiche_head($head, 'card', $langs->trans("Warehouse"), 0, 'stock');

			print '<table class="border centpercent">';

			// Ref
			print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td><input name="libelle" size="20" value="'.$object->label.'"></td></tr>';

			print '<tr><td>'.$langs->trans("LocationSummary").'</td><td><input name="lieu" class="minwidth300" value="'.$object->lieu.'"></td></tr>';

			// Parent entrepot
			print '<tr><td>'.$langs->trans("AddIn").'</td><td>';
			print $formproduct->selectWarehouses($object->fk_parent, 'fk_parent', '', 1);
			print '</td></tr>';

			// Description
			print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
			// Editeur wysiwyg
			require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
			$doleditor = new DolEditor('desc', $object->description, '', 180, 'dolibarr_notes', 'In', false, true, $conf->fckeditor->enabled, ROWS_5, '90%');
			$doleditor->Create();
			print '</td></tr>';

			print '<tr><td>'.$langs->trans('Address').'</td><td><textarea name="address" class="quatrevingtpercent" rows="3" wrap="soft">';
			print $object->address;
			print '</textarea></td></tr>';

			// Zip / Town
			print '<tr><td>'.$langs->trans('Zip').'</td><td>';
			print $formcompany->select_ziptown($object->zip, 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6);
			print '</td></tr>';
			print '<tr><td>'.$langs->trans('Town').'</td><td>';
			print $formcompany->select_ziptown($object->town, 'town', array('zipcode', 'selectcountry_id', 'state_id'));
			print '</td></tr>';

			// Country
			print '<tr><td>'.$langs->trans('Country').'</td><td>';
			print img_picto('', 'globe-americas', 'class="paddingright"');
			print $form->select_country($object->country_id ? $object->country_id : $mysoc->country_code, 'country_id');
			if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			print '</td></tr>';

			// Phone / Fax
			print '<tr><td class="titlefieldcreate">'.$form->editfieldkey('Phone', 'phone', '', $object, 0).'</td><td>';
			print img_picto('', 'object_phoning', 'class="paddingright"');
			print '<input name="phone" size="20" value="'.$object->phone.'"></td></tr>';
			print '<tr><td class="titlefieldcreate">'.$form->editfieldkey('Fax', 'fax', '', $object, 0).'</td><td>';
			print img_picto('', 'object_phoning_fax', 'class="paddingright"');
			print '<input name="fax" size="20" value="'.$object->fax.'"></td></tr>';

			// Status
			print '<tr><td>'.$langs->trans("Status").'</td><td>';
			print '<select name="statut" class="flat">';
			foreach ($object->statuts as $key => $value)
			{
				if ($key == $object->statut)
				{
					print '<option value="'.$key.'" selected>'.$langs->trans($value).'</option>';
				} else {
					print '<option value="'.$key.'">'.$langs->trans($value).'</option>';
				}
			}
			print '</select>';
			print '</td></tr>';

			// Other attributes
			$parameters = array('colspan' => ' colspan="3"', 'cols' => '3');
			$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			if (empty($reshook))
			{
				print $object->showOptionals($extrafields, 'edit', $parameters);
			}

			// Tags-Categories
			if ($conf->categorie->enabled)
			{
				print '<tr><td class="tdtop">'.$langs->trans("Categories").'</td><td colspan="3">';
				$cate_arbo = $form->select_all_categories(Categorie::TYPE_WAREHOUSE, '', 'parent', 64, 0, 1);
				$c = new Categorie($db);
				$cats = $c->containing($object->id, Categorie::TYPE_WAREHOUSE);
				$arrayselected = array();
				foreach ($cats as $cat) {
					$arrayselected[] = $cat->id;
				}
				print img_picto('', 'category').$form->multiselectarray('categories', $cate_arbo, $arrayselected, '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
				print "</td></tr>";
			}

			print '</table>';

			print dol_get_fiche_end();

			print '<div class="center">';
			print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</div>';

			print '</form>';
		}
	}
}

/*
 * Documents generated
 */

$modulepart = 'stock';

if ($action != 'create' && $action != 'edit' && $action != 'delete')
{
	print '<br>';
	print '<div class="fichecenter"><div class="fichehalfleft">';
	print '<a name="builddoc"></a>'; // ancre

	// Documents
	$objectref = dol_sanitizeFileName($object->ref);
	$relativepath = $object->ref.'/'.$objectref.'.pdf';
	$filedir = $conf->stock->dir_output.'/'.$objectref;
	$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
	$genallowed = $usercanread;
	$delallowed = $usercancreate;
	$modulepart = 'stock';

	print $formfile->showdocuments($modulepart, $object->ref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 0, 0, 0, 28, 0, '', 0, '', $object->default_lang, '', $object);
	$somethingshown = $formfile->numoffiles;

	print '</div><div class="fichehalfright"><div class="ficheaddleft">';

	$MAXEVENT = 10;

	$morehtmlright = '<a href="'.DOL_URL_ROOT.'/product/agenda.php?id='.$object->id.'">';
	$morehtmlright .= $langs->trans("SeeAll");
	$morehtmlright .= '</a>';

	// List of actions on element
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);
	$somethingshown = $formactions->showactions($object, 'stock', 0, 1, '', $MAXEVENT, '', $morehtmlright); // Show all action for product

	print '</div></div></div>';
}

// End of page
llxFooter();
$db->close();
