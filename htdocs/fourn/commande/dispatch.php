<?php
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Cedric Gross         <c.gross@kreiz-it.fr>
 * Copyright (C) 2016      Florian Henry         <florian.henry@atm-consulting.fr>
 * Copyright (C) 2017      Ferran Marcet        <fmarcet@2byte.es>
 *
 * This	program	is free	software; you can redistribute it and/or modify
 * it under the	terms of the GNU General Public	License	as published by
 * the Free Software Foundation; either	version	2 of the License, or
 * (at your option) any later version.
 *
 * This	program	is distributed in the hope that	it will	be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 * \file htdocs/fourn/commande/dispatch.php
 * \ingroup commande
 * \brief Page to dispatch receiving
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/supplier_order/modules_commandefournisseur.php';
require_once DOL_DOCUMENT_ROOT . '/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.dispatch.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
if (! empty($conf->projet->enabled))
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';

$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('deliveries');
$langs->load('products');
$langs->load('stocks');
if (! empty($conf->productbatch->enabled))
	$langs->load('productbatch');

	// Security check
$id = GETPOST("id", 'int');
$ref = GETPOST('ref');
$lineid = GETPOST('lineid', 'int');
$action = GETPOST('action','aZ09');
if ($user->societe_id)
	$socid = $user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, 'commande_fournisseur', 'commande');

if (empty($conf->stock->enabled)) {
	accessforbidden();
}

// Recuperation de l'id de projet
$projectid = 0;
if ($_GET["projectid"])
	$projectid = GETPOST("projectid", 'int');

$object = new CommandeFournisseur($db);

if ($id > 0 || ! empty($ref)) {
	$result = $object->fetch($id, $ref);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$result = $object->fetch_thirdparty();
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}


/*
 * Actions
 */

if ($action == 'checkdispatchline' && ! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande->receptionner)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande_advance->check))))
{
	$error=0;
	$supplierorderdispatch = new CommandeFournisseurDispatch($db);

	$db->begin();

	$result = $supplierorderdispatch->fetch($lineid);
	if (! $result)
	{
		$error++;
		setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
		$action = '';
	}

	if (! $error)
	{
		$result = $supplierorderdispatch->setStatut(1);
		if ($result < 0) {
			setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
			$error++;
			$action = '';
		}
	}

	if (! $error)
	{
		$result = $object->calcAndSetStatusDispatch($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error ++;
			$action = '';
		}
	}
	if (! $error)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
	}
}

if ($action == 'uncheckdispatchline' && ! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande->receptionner)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande_advance->check))))
{
	$error=0;
	$supplierorderdispatch = new CommandeFournisseurDispatch($db);

	$db->begin();

	$result = $supplierorderdispatch->fetch($lineid);
	if (! $result)
	{
		$error++;
		setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
		$action = '';
	}

	if (! $error)
	{
		$result = $supplierorderdispatch->setStatut(0);
		if ($result < 0) {
			setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
			$error ++;
			$action = '';
		}
	}
	if (! $error)
	{
		$result = $object->calcAndSetStatusDispatch($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error ++;
			$action = '';
		}
	}
	if (! $error)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
	}
}

if ($action == 'denydispatchline' && ! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande->receptionner)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande_advance->check))))
{
	$error=0;
	$supplierorderdispatch = new CommandeFournisseurDispatch($db);

	$db->begin();

	$result = $supplierorderdispatch->fetch($lineid);
	if (! $result)
	{
		$error++;
		setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
		$action = '';
	}

	if (! $error)
	{
		$result = $supplierorderdispatch->setStatut(2);
		if ($result < 0) {
			setEventMessages($supplierorderdispatch->error, $supplierorderdispatch->errors, 'errors');
			$error ++;
			$action = '';
		}
	}
	if (! $error)
	{
		$result = $object->calcAndSetStatusDispatch($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error ++;
			$action = '';
		}
	}
	if (! $error)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
	}
}

if ($action == 'dispatch' && $user->rights->fournisseur->commande->receptionner) {
	$error = 0;

	$db->begin();

	$pos = 0;
	foreach ($_POST as $key => $value)
	{
		// without batch module enabled
		if (preg_match('/^product_([0-9]+)_([0-9]+)$/i', $key, $reg))
		{
			$pos ++;

			// $numline=$reg[2] + 1; // line of product
			$numline = $pos;
			$prod = "product_" . $reg[1] . '_' . $reg[2];
			$qty = "qty_" . $reg[1] . '_' . $reg[2];
			$ent = "entrepot_" . $reg[1] . '_' . $reg[2];
			$pu = "pu_" . $reg[1] . '_' . $reg[2]; // This is unit price including discount
			$fk_commandefourndet = "fk_commandefourndet_" . $reg[1] . '_' . $reg[2];

			// We ask to move a qty
			if (GETPOST($qty) > 0) {
				if (! (GETPOST($ent, 'int') > 0)) {
					dol_syslog('No dispatch for line ' . $key . ' as no warehouse choosed');
					$text = $langs->transnoentities('Warehouse') . ', ' . $langs->transnoentities('Line') . ' ' . ($numline);
					setEventMessages($langs->trans('ErrorFieldRequired', $text), null, 'errors');
					$error ++;
				}

				if (! $error) {
					$result = $object->dispatchProduct($user, GETPOST($prod, 'int'), GETPOST($qty), GETPOST($ent, 'int'), GETPOST($pu), GETPOST('comment'), '', '', '', GETPOST($fk_commandefourndet, 'int'), $notrigger);
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
						$error ++;
					}
				}
			}
		}
		// with batch module enabled
		if (preg_match('/^product_batch_([0-9]+)_([0-9]+)$/i', $key, $reg))
		{
			$pos ++;

			// eat-by date dispatch
			// $numline=$reg[2] + 1; // line of product
			$numline = $pos;
			$prod = 'product_batch_' . $reg[1] . '_' . $reg[2];
			$qty = 'qty_' . $reg[1] . '_' . $reg[2];
			$ent = 'entrepot_' . $reg[1] . '_' . $reg[2];
			$pu = 'pu_' . $reg[1] . '_' . $reg[2];
			$fk_commandefourndet = 'fk_commandefourndet_' . $reg[1] . '_' . $reg[2];
			$lot = 'lot_number_' . $reg[1] . '_' . $reg[2];
			$dDLUO = dol_mktime(12, 0, 0, $_POST['dluo_' . $reg[1] . '_' . $reg[2] . 'month'], $_POST['dluo_' . $reg[1] . '_' . $reg[2] . 'day'], $_POST['dluo_' . $reg[1] . '_' . $reg[2] . 'year']);
			$dDLC = dol_mktime(12, 0, 0, $_POST['dlc_' . $reg[1] . '_' . $reg[2] . 'month'], $_POST['dlc_' . $reg[1] . '_' . $reg[2] . 'day'], $_POST['dlc_' . $reg[1] . '_' . $reg[2] . 'year']);

			$fk_commandefourndet = 'fk_commandefourndet_' . $reg[1] . '_' . $reg[2];

			// We ask to move a qty
			if (GETPOST($qty) > 0) {
				if (! (GETPOST($ent, 'int') > 0)) {
					dol_syslog('No dispatch for line ' . $key . ' as no warehouse choosed');
					$text = $langs->transnoentities('Warehouse') . ', ' . $langs->transnoentities('Line') . ' ' . ($numline) . '-' . ($reg[1] + 1);
					setEventMessages($langs->trans('ErrorFieldRequired', $text), null, 'errors');
					$error ++;
				}

				if (! (GETPOST($lot, 'alpha') || $dDLUO || $dDLC)) {
					dol_syslog('No dispatch for line ' . $key . ' as serial/eat-by/sellby date are not set');
					$text = $langs->transnoentities('atleast1batchfield') . ', ' . $langs->transnoentities('Line') . ' ' . ($numline) . '-' . ($reg[1] + 1);
					setEventMessages($langs->trans('ErrorFieldRequired', $text), null, 'errors');
					$error ++;
				}

				if (! $error) {
					$result = $object->dispatchProduct($user, GETPOST($prod, 'int'), GETPOST($qty), GETPOST($ent, 'int'), GETPOST($pu), GETPOST('comment'), $dDLC, $dDLUO, GETPOST($lot, 'alpha'), GETPOST($fk_commandefourndet, 'int'), $notrigger);
					if ($result < 0) {
						setEventMessages($object->error, $object->errors, 'errors');
						$error ++;
					}
				}
			}
		}
	}

	if (! $error) {
		$result = $object->calcAndSetStatusDispatch($user, GETPOST('closeopenorder')?1:0, GETPOST('comment'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error ++;
		}
	}

	if (! $notrigger && ! $error) {
		global $conf, $langs, $user;
		// Call trigger

		$result = $object->call_trigger('ORDER_SUPPLIER_DISPATCH', $user);
		// End call triggers

		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error ++;
		}
	}

	if ($result >= 0 && ! $error) {
		$db->commit();

		header("Location: dispatch.php?id=" . $id);
		exit();
	} else {
		$db->rollback();
	}
}


/*
 * View
 */

$now = dol_now();

$form = new Form($db);
$formproduct = new FormProduct($db);
$warehouse_static = new Entrepot($db);
$supplierorderdispatch = new CommandeFournisseurDispatch($db);

$help_url='EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
llxHeader('', $langs->trans("Order"), $help_url, '', 0, 0, array('/fourn/js/lib_dispatch.js'));

if ($id > 0 || ! empty($ref)) {
	$soc = new Societe($db);
	$soc->fetch($object->socid);

	$author = new User($db);
	$author->fetch($object->user_author_id);

	$head = ordersupplier_prepare_head($object);

	$title = $langs->trans("SupplierOrder");
	dol_fiche_head($head, 'dispatch', $title, -1, 'order');


	// Supplier order card

	$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref='<div class="refidno">';
	// Ref supplier
	$morehtmlref.=$form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
	// Project
	if (! empty($conf->projet->enabled))
	{
	    $langs->load("projects");
	    $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
	    if ($user->rights->fournisseur->commande->creer)
	    {
	        if ($action != 'classify')
	            //$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
	            $morehtmlref.=' : ';
	        	if ($action == 'classify') {
	                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
	                $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	                $morehtmlref.='<input type="hidden" name="action" value="classin">';
	                $morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	                $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
	                $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
	                $morehtmlref.='</form>';
	            } else {
	                $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
	            }
	    } else {
	        if (! empty($object->fk_project)) {
	            $proj = new Project($db);
	            $proj->fetch($object->fk_project);
	            $morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
	            $morehtmlref.=$proj->ref;
	            $morehtmlref.='</a>';
	        } else {
	            $morehtmlref.='';
	        }
	    }
	}
	$morehtmlref.='</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border" width="100%">';

	// Date
	if ($object->methode_commande_id > 0) {
		print '<tr><td class="titlefield">' . $langs->trans("Date") . '</td><td>';
		if ($object->date_commande) {
			print dol_print_date($object->date_commande, "dayhourtext") . "\n";
		}
		print "</td></tr>";

		if ($object->methode_commande) {
			print '<tr><td>' . $langs->trans("Method") . '</td><td>' . $object->getInputMethod() . '</td></tr>';
		}
	}

	// Author
	print '<tr><td class="titlefield">' . $langs->trans("AuthorRequest") . '</td>';
	print '<td>' . $author->getNomUrl(1, '', 0, 0, 0) . '</td>';
	print '</tr>';

	print "</table>";

	print '</div>';

	// if ($mesg) print $mesg;
	print '<br>';

	$disabled = 1;
	if (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER))
		$disabled = 0;

	// Line of orders
	if ($object->statut <= CommandeFournisseur::STATUS_ACCEPTED || $object->statut >= CommandeFournisseur::STATUS_CANCELED) {
		print '<span class="opacitymedium">'.$langs->trans("OrderStatusNotReadyToDispatch").'</span>';
	}

	if ($object->statut == CommandeFournisseur::STATUS_ORDERSENT
		|| $object->statut == CommandeFournisseur::STATUS_RECEIVED_PARTIALLY
		|| $object->statut == CommandeFournisseur::STATUS_RECEIVED_COMPLETELY) {
		$entrepot = new Entrepot($db);
		$listwarehouses = $entrepot->list_array(1);

		print '<form method="POST" action="dispatch.php?id=' . $object->id . '">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="action" value="dispatch">';

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder" width="100%">';

		// Set $products_dispatched with qty dispatched for each product id
		$products_dispatched = array();
		$sql = "SELECT l.rowid, cfd.fk_product, sum(cfd.qty) as qty";
		$sql .= " FROM " . MAIN_DB_PREFIX . "commande_fournisseur_dispatch as cfd";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande_fournisseurdet as l on l.rowid = cfd.fk_commandefourndet";
		$sql .= " WHERE cfd.fk_commande = " . $object->id;
		$sql .= " GROUP BY l.rowid, cfd.fk_product";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;

			if ($num) {
				while ( $i < $num ) {
					$objd = $db->fetch_object($resql);
					$products_dispatched[$objd->rowid] = price2num($objd->qty, 5);
					$i++;
				}
			}
			$db->free($resql);
		}

		$sql = "SELECT l.rowid, l.fk_product, l.subprice, l.remise_percent, SUM(l.qty) as qty,";
		$sql .= " p.ref, p.label, p.tobatch";
		$sql .= " FROM " . MAIN_DB_PREFIX . "commande_fournisseurdet as l";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON l.fk_product=p.rowid";
		$sql .= " WHERE l.fk_commande = " . $object->id;
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
			$sql .= " AND l.product_type = 0";
		$sql .= " GROUP BY p.ref, p.label, p.tobatch, l.rowid, l.fk_product, l.subprice, l.remise_percent"; // Calculation of amount dispatched is done per fk_product so we must group by fk_product
		$sql .= " ORDER BY p.ref, p.label";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;

			if ($num) {
				print '<tr class="liste_titre">';

				print '<td>' . $langs->trans("Description") . '</td>';
				print '<td></td>';
				print '<td></td>';
				print '<td></td>';
				print '<td align="right">' . $langs->trans("QtyOrdered") . '</td>';
				print '<td align="right">' . $langs->trans("QtyDispatchedShort") . '</td>';
				print '<td align="right">' . $langs->trans("QtyToDispatchShort") . '</td>';
				print '<td width="32"></td>';
				print '<td align="right">' . $langs->trans("Warehouse") . '</td>';
				print "</tr>\n";

				if (! empty($conf->productbatch->enabled)) {
					print '<tr class="liste_titre">';
					print '<td></td>';
					print '<td>' . $langs->trans("batch_number") . '</td>';
					print '<td>' . $langs->trans("EatByDate") . '</td>';
					print '<td>' . $langs->trans("SellByDate") . '</td>';
					print '<td colspan="5">&nbsp;</td>';
					print "</tr>\n";
				}
			}

			$nbfreeproduct = 0;		// Nb of lins of free products/services
			$nbproduct = 0;			// Nb of predefined product lines to dispatch (already done or not) if SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED is off (default)
									// or nb of line that remain to dispatch if SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED is on.

			$var = false;
			while ( $i < $num ) {
				$objp = $db->fetch_object($resql);

				// On n'affiche pas les produits libres
				if (! $objp->fk_product > 0) {
					$nbfreeproduct++;
				} else {
					$remaintodispatch = price2num($objp->qty - (( float ) $products_dispatched[$objp->rowid]), 5); // Calculation of dispatched
					if ($remaintodispatch < 0)
						$remaintodispatch = 0;

					if ($remaintodispatch || empty($conf->global->SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED)) {
						$nbproduct++;

						// To show detail cref and description value, we must make calculation by cref
						// print ($objp->cref?' ('.$objp->cref.')':'');
						// if ($objp->description) print '<br>'.nl2br($objp->description);
						$suffix = '_0_' . $i;

						print "\n";
						print '<!-- Line to dispatch ' . $suffix . ' -->' . "\n";
						// hidden fields for js function
						print '<input id="qty_ordered' . $suffix . '" type="hidden" value="' . $objp->qty . '">';
						print '<input id="qty_dispatched' . $suffix . '" type="hidden" value="' . ( float ) $products_dispatched[$objp->rowid] . '">';
						print '<tr class="oddeven">';

						$linktoprod = '<a href="' . DOL_URL_ROOT . '/product/fournisseurs.php?id=' . $objp->fk_product . '">' . img_object($langs->trans("ShowProduct"), 'product') . ' ' . $objp->ref . '</a>';
						$linktoprod .= ' - ' . $objp->label . "\n";

						if (! empty($conf->productbatch->enabled)) {
							if ($objp->tobatch) {
								print '<td colspan="4">';
								print $linktoprod;
								print "</td>";
							} else {
								print '<td>';
								print $linktoprod;
								print "</td>";
								print '<td colspan="3">';
								print $langs->trans("ProductDoesNotUseBatchSerial");
								print '</td>';
							}
						} else {
							print '<td colspan="4">';
							print $linktoprod;
							print "</td>";
						}

						// Define unit price for PMP calculation
						$up_ht_disc = $objp->subprice;
						if (! empty($objp->remise_percent) && empty($conf->global->STOCK_EXCLUDE_DISCOUNT_FOR_PMP))
							$up_ht_disc = price2num($up_ht_disc * (100 - $objp->remise_percent) / 100, 'MU');

						// Qty ordered
						print '<td align="right">' . $objp->qty . '</td>';

						// Already dispatched
						print '<td align="right">' . $products_dispatched[$objp->rowid] . '</td>';

						if (! empty($conf->productbatch->enabled) && $objp->tobatch == 1) {
							$type = 'batch';
							print '<td align="right">';
							print '</td>';     // Qty to dispatch
							print '<td>';
							//print img_picto($langs->trans('AddDispatchBatchLine'), 'split.png', 'onClick="addDispatchLine(' . $i . ',\'' . $type . '\')"');
							print '</td>';     // Dispatch column
							print '<td></td>'; // Warehouse column
							print '</tr>';

							print '<tr class="oddeven" name="' . $type . $suffix . '">';
							print '<td>';
							print '<input name="fk_commandefourndet' . $suffix . '" type="hidden" value="' . $objp->rowid . '">';
							print '<input name="product_batch' . $suffix . '" type="hidden" value="' . $objp->fk_product . '">';

							print '<!-- This is a up (may include discount or not depending on STOCK_EXCLUDE_DISCOUNT_FOR_PMP. will be used for PMP calculation) -->';
							if (! empty($conf->global->SUPPLIER_ORDER_EDIT_BUYINGPRICE_DURING_RECEIPT)) // Not tested !
							{
							    print $langs->trans("BuyingPrice").': <input class="maxwidth75" name="pu' . $suffix . '" type="text" value="' . price2num($up_ht_disc, 'MU') . '">';
							}
							else
							{
							    print '<input class="maxwidth75" name="pu' . $suffix . '" type="hidden" value="' . price2num($up_ht_disc, 'MU') . '">';
							}

							print '</td>';

							print '<td>';
							print '<input type="text" class="inputlotnumber" id="lot_number' . $suffix . '" name="lot_number' . $suffix . '" size="40" value="' . GETPOST('lot_number' . $suffix) . '">';
							print '</td>';
							print '<td>';
							$dlcdatesuffix = dol_mktime(0, 0, 0, GETPOST('dlc' . $suffix . 'month'), GETPOST('dlc' . $suffix . 'day'), GETPOST('dlc' . $suffix . 'year'));
							$form->select_date($dlcdatesuffix, 'dlc' . $suffix, '', '', 1, "");
							print '</td>';
							print '<td>';
							$dluodatesuffix = dol_mktime(0, 0, 0, GETPOST('dluo' . $suffix . 'month'), GETPOST('dluo' . $suffix . 'day'), GETPOST('dluo' . $suffix . 'year'));
							$form->select_date($dluodatesuffix, 'dluo' . $suffix, '', '', 1, "");
							print '</td>';
							print '<td colspan="2">&nbsp</td>'; // Qty ordered + qty already dispatached
						} else {
							$type = 'dispatch';
							print '<td align="right">';
							print '</td>';     // Qty to dispatch
							print '<td>';
							//print img_picto($langs->trans('AddStockLocationLine'), 'split.png', 'onClick="addDispatchLine(' . $i . ',\'' . $type . '\')"');
							print '</td>';      // Dispatch column
							print '<td></td>'; // Warehouse column
							print '</tr>';

							print '<tr class="oddeven" name="' . $type . $suffix . '">';
							print '<td colspan="6">';
							print '<input name="fk_commandefourndet' . $suffix . '" type="hidden" value="' . $objp->rowid . '">';
							print '<input name="product' . $suffix . '" type="hidden" value="' . $objp->fk_product . '">';

							print '<!-- This is a up (may include discount or not depending on STOCK_EXCLUDE_DISCOUNT_FOR_PMP. will be used for PMP calculation) -->';
							if (! empty($conf->global->SUPPLIER_ORDER_EDIT_BUYINGPRICE_DURING_RECEIPT)) // Not tested !
							{
							    print $langs->trans("BuyingPrice").': <input class="maxwidth75" name="pu' . $suffix . '" type="text" value="' . price2num($up_ht_disc, 'MU') . '">';
							}
							else
							{
							    print '<input class="maxwidth75" name="pu' . $suffix . '" type="hidden" value="' . price2num($up_ht_disc, 'MU') . '">';
							}

							print '</td>';
						}

						// Qty to dispatch
						print '<td align="right">';
						print '<input id="qty' . $suffix . '" name="qty' . $suffix . '" type="text" size="8" value="' . (GETPOST('qty' . $suffix) != '' ? GETPOST('qty' . $suffix) : $remaintodispatch) . '">';
						print '</td>';

                        print '<td>';
						if (! empty($conf->productbatch->enabled) && $objp->tobatch == 1) {
						    $type = 'batch';
						    //print img_picto($langs->trans('AddDispatchBatchLine'), 'split.png', 'class="splitbutton" onClick="addDispatchLine(' . $i . ',\'' . $type . '\')"');
						    print img_picto($langs->trans('AddStockLocationLine'), 'split.png', 'class="splitbutton" onClick="addDispatchLine(' . $i . ',\'' . $type . '\')"');
						}
						else
						{
						    $type = 'dispatch';
						    print img_picto($langs->trans('AddStockLocationLine'), 'split.png', 'class="splitbutton" onClick="addDispatchLine(' . $i . ',\'' . $type . '\')"');
						}

						print '</td>';

						// Warehouse
						print '<td align="right">';
						if (count($listwarehouses) > 1) {
							print $formproduct->selectWarehouses(GETPOST("entrepot" . $suffix), "entrepot" . $suffix, '', 1, 0, $objp->fk_product, '', 1);
						} elseif (count($listwarehouses) == 1) {
							print $formproduct->selectWarehouses(GETPOST("entrepot" . $suffix), "entrepot" . $suffix, '', 0, 0, $objp->fk_product, '', 1);
						} else {
							$langs->load("errors");
							print $langs->trans("ErrorNoWarehouseDefined");
						}
						print "</td>\n";

						print "</tr>\n";
					}
				}
				$i++;
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}

		print "</table>\n";
		print '</div>';
		print "<br>\n";

		if ($nbproduct)
		{
            $checkboxlabel=$langs->trans("CloseReceivedSupplierOrdersAutomatically", $langs->transnoentitiesnoconv($object->statuts[5]));

			print '<br><div class="center">';
            print $langs->trans("Comment") . ' : ';
			print '<input type="text" class="minwidth400" maxlength="128" name="comment" value="';
			print $_POST["comment"] ? GETPOST("comment") : $langs->trans("DispatchSupplierOrder", $object->ref);
			// print ' / '.$object->ref_supplier; // Not yet available
			print '" class="flat"><br>';

			print '<input type="checkbox" checked="checked" name="closeopenorder"> '.$checkboxlabel;

			print '<br><input type="submit" class="button" value="' . $langs->trans("DispatchVerb") . '"';
			if (count($listwarehouses) <= 0)
				print ' disabled';
			print '>';
			print '</div>';
		}

		// Message if nothing to dispatch
		if (! $nbproduct) {
			if (empty($conf->global->SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED))
				print '<div class="opacitymedium">'.$langs->trans("NoPredefinedProductToDispatch").'</div>';		// No predefined line at all
			else
				print '<div class="opacitymedium">'.$langs->trans("NoMorePredefinedProductToDispatch").'</div>';	// No predefined line that remain to be dispatched.
		}

		print '</form>';
	}

	dol_fiche_end();


	// List of lines already dispatched
	$sql = "SELECT p.ref, p.label,";
	$sql .= " e.rowid as warehouse_id, e.ref as entrepot,";
	$sql .= " cfd.rowid as dispatchlineid, cfd.fk_product, cfd.qty, cfd.eatby, cfd.sellby, cfd.batch, cfd.comment, cfd.status";
	$sql .= " FROM " . MAIN_DB_PREFIX . "product as p,";
	$sql .= " " . MAIN_DB_PREFIX . "commande_fournisseur_dispatch as cfd";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "entrepot as e ON cfd.fk_entrepot = e.rowid";
	$sql .= " WHERE cfd.fk_commande = " . $object->id;
	$sql .= " AND cfd.fk_product = p.rowid";
	$sql .= " ORDER BY cfd.rowid ASC";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		if ($num > 0) {
			print "<br>\n";

			print load_fiche_titre($langs->trans("ReceivingForSameOrder"));

			print '<div class="div-table-responsive">';
			print '<table class="noborder" width="100%">';

			print '<tr class="liste_titre">';
			print '<td>' . $langs->trans("Product") . '</td>';
			if (! empty($conf->productbatch->enabled)) {
				print '<td>' . $langs->trans("batch_number") . '</td>';
				print '<td>' . $langs->trans("EatByDate") . '</td>';
				print '<td>' . $langs->trans("SellByDate") . '</td>';
			}
			print '<td align="right">' . $langs->trans("QtyDispatched") . '</td>';
			print '<td></td>';
			print '<td>' . $langs->trans("Warehouse") . '</td>';
			print '<td>' . $langs->trans("Comment") . '</td>';
			if (! empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS))
				print '<td align="center" colspan="2">' . $langs->trans("Status") . '</td>';
			print "</tr>\n";

			$var = false;

			while ( $i < $num ) {
				$objp = $db->fetch_object($resql);

				print "<tr " . $bc[$var] . ">";
				print '<td>';
				print '<a href="' . DOL_URL_ROOT . '/product/fournisseurs.php?id=' . $objp->fk_product . '">' . img_object($langs->trans("ShowProduct"), 'product') . ' ' . $objp->ref . '</a>';
				print ' - ' . $objp->label;
				print "</td>\n";

				if (! empty($conf->productbatch->enabled)) {
					print '<td>' . $objp->batch . '</td>';
					print '<td>' . dol_print_date($db->jdate($objp->eatby), 'day') . '</td>';
					print '<td>' . dol_print_date($db->jdate($objp->sellby), 'day') . '</td>';
				}

				// Qty
				print '<td align="right">' . $objp->qty . '</td>';
				print '<td>&nbsp;</td>';

				// Warehouse
				print '<td>';
				$warehouse_static->id = $objp->warehouse_id;
				$warehouse_static->libelle = $objp->entrepot;
				print $warehouse_static->getNomUrl(1);
				print '</td>';

				// Comment
				print '<td class="tdoverflowmax300">' . $objp->comment . '</td>';

				// Status
				if (! empty($conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS)) {
					print '<td align="right">';
					$supplierorderdispatch->status = (empty($objp->status) ? 0 : $objp->status);
					// print $supplierorderdispatch->status;
					print $supplierorderdispatch->getLibStatut(5);
					print '</td>';

					// Add button to check/uncheck disaptching
					print '<td align="center">';
					if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande->receptionner)) || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->fournisseur->commande_advance->check)))
					{
						if (empty($objp->status)) {
							print '<a class="button buttonRefused" href="#">' . $langs->trans("Approve") . '</a>';
							print '<a class="button buttonRefused" href="#">' . $langs->trans("Deny") . '</a>';
						} else {
							print '<a class="button buttonRefused" href="#">' . $langs->trans("Disapprove") . '</a>';
							print '<a class="button buttonRefused" href="#">' . $langs->trans("Deny") . '</a>';
						}
					} else {
						$disabled = '';
						if ($object->statut == 5)
							$disabled = 1;
						if (empty($objp->status)) {
							print '<a class="button' . ($disabled ? ' buttonRefused' : '') . '" href="' . $_SERVER["PHP_SELF"] . "?id=" . $id . "&action=checkdispatchline&lineid=" . $objp->dispatchlineid . '">' . $langs->trans("Approve") . '</a>';
							print '<a class="button' . ($disabled ? ' buttonRefused' : '') . '" href="' . $_SERVER["PHP_SELF"] . "?id=" . $id . "&action=denydispatchline&lineid=" . $objp->dispatchlineid . '">' . $langs->trans("Deny") . '</a>';
						}
						if ($objp->status == 1) {
							print '<a class="button' . ($disabled ? ' buttonRefused' : '') . '" href="' . $_SERVER["PHP_SELF"] . "?id=" . $id . "&action=uncheckdispatchline&lineid=" . $objp->dispatchlineid . '">' . $langs->trans("Reinit") . '</a>';
							print '<a class="button' . ($disabled ? ' buttonRefused' : '') . '" href="' . $_SERVER["PHP_SELF"] . "?id=" . $id . "&action=denydispatchline&lineid=" . $objp->dispatchlineid . '">' . $langs->trans("Deny") . '</a>';
						}
						if ($objp->status == 2) {
							print '<a class="button' . ($disabled ? ' buttonRefused' : '') . '" href="' . $_SERVER["PHP_SELF"] . "?id=" . $id . "&action=uncheckdispatchline&lineid=" . $objp->dispatchlineid . '">' . $langs->trans("Reinit") . '</a>';
							print '<a class="button' . ($disabled ? ' buttonRefused' : '') . '" href="' . $_SERVER["PHP_SELF"] . "?id=" . $id . "&action=checkdispatchline&lineid=" . $objp->dispatchlineid . '">' . $langs->trans("Approve") . '</a>';
						}
					}
					print '</td>';
				}

				print "</tr>\n";

				$i ++;
				$var = ! $var;
			}
			$db->free($resql);

			print "</table>\n";
			print '</div>';
		}
	} else {
		dol_print_error($db);
	}
}

llxFooter();

$db->close();
