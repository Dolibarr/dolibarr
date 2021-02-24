<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2019      Frédéric France             <frederic.france@netlogic.fr>
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
 *	    \file       htdocs/comm/remx.php
 *      \ingroup    societe
 *		\brief      Page to edit absolute discounts for a customer
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

// Load translation files required by the page
$langs->loadLangs(array('orders', 'bills', 'companies'));

$id = GETPOST('id', 'int');

$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Security check
$socid = GETPOST('id', 'int') ?GETPOST('id', 'int') : GETPOST('socid', 'int');
if ($user->socid > 0)
{
	$socid = $user->socid;
}


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha') && !empty($backtopage))
{
	 header("Location: ".$backtopage);
	 exit;
}

if ($action == 'confirm_split' && GETPOST("confirm", "alpha") == 'yes')
{
	//if ($user->rights->societe->creer)
	//if ($user->rights->facture->creer)

	$amount_ttc_1 = GETPOST('amount_ttc_1', 'alpha');
	$amount_ttc_1 = price2num($amount_ttc_1);
	$amount_ttc_2 = GETPOST('amount_ttc_2', 'alpha');
	$amount_ttc_2 = price2num($amount_ttc_2);

	$error = 0;
	$remid = (GETPOST("remid", 'int') ? GETPOST("remid", 'int') : 0);
	$discount = new DiscountAbsolute($db);
	$res = $discount->fetch($remid);
	if (!($res > 0))
	{
		$error++;
		setEventMessages($langs->trans("ErrorFailedToLoadDiscount"), null, 'errors');
	}
	if (!$error && price2num($amount_ttc_1 + $amount_ttc_2) != $discount->amount_ttc)
	{
		$error++;
		setEventMessages($langs->trans("TotalOfTwoDiscountMustEqualsOriginal"), null, 'errors');
	}
	if (!$error && $discount->fk_facture_line)
	{
		$error++;
		setEventMessages($langs->trans("ErrorCantSplitAUsedDiscount"), null, 'errors');
	}
	if (!$error)
	{
		$newdiscount1 = new DiscountAbsolute($db);
		$newdiscount2 = new DiscountAbsolute($db);
		$newdiscount1->fk_facture_source = $discount->fk_facture_source;
		$newdiscount2->fk_facture_source = $discount->fk_facture_source;
		$newdiscount1->fk_facture = $discount->fk_facture;
		$newdiscount2->fk_facture = $discount->fk_facture;
		$newdiscount1->fk_facture_line = $discount->fk_facture_line;
		$newdiscount2->fk_facture_line = $discount->fk_facture_line;
		$newdiscount1->fk_invoice_supplier_source = $discount->fk_invoice_supplier_source;
		$newdiscount2->fk_invoice_supplier_source = $discount->fk_invoice_supplier_source;
		$newdiscount1->fk_invoice_supplier = $discount->fk_invoice_supplier;
		$newdiscount2->fk_invoice_supplier = $discount->fk_invoice_supplier;
		$newdiscount1->fk_invoice_supplier_line = $discount->fk_invoice_supplier_line;
		$newdiscount2->fk_invoice_supplier_line = $discount->fk_invoice_supplier_line;
		if ($discount->description == '(CREDIT_NOTE)' || $discount->description == '(DEPOSIT)')
		{
			$newdiscount1->description = $discount->description;
			$newdiscount2->description = $discount->description;
		} else {
			$newdiscount1->description = $discount->description.' (1)';
			$newdiscount2->description = $discount->description.' (2)';
		}

		$newdiscount1->fk_user = $discount->fk_user;
		$newdiscount2->fk_user = $discount->fk_user;
		$newdiscount1->fk_soc = $discount->fk_soc;
		$newdiscount2->fk_soc = $discount->fk_soc;
		$newdiscount1->discount_type = $discount->discount_type;
		$newdiscount2->discount_type = $discount->discount_type;
		$newdiscount1->datec = $discount->datec;
		$newdiscount2->datec = $discount->datec;
		$newdiscount1->tva_tx = $discount->tva_tx;
		$newdiscount2->tva_tx = $discount->tva_tx;
		$newdiscount1->vat_src_code = $discount->vat_src_code;
		$newdiscount2->vat_src_code = $discount->vat_src_code;
		$newdiscount1->amount_ttc = $amount_ttc_1;
		$newdiscount2->amount_ttc = price2num($discount->amount_ttc - $newdiscount1->amount_ttc);
		$newdiscount1->amount_ht = price2num($newdiscount1->amount_ttc / (1 + $newdiscount1->tva_tx / 100), 'MT');
		$newdiscount2->amount_ht = price2num($newdiscount2->amount_ttc / (1 + $newdiscount2->tva_tx / 100), 'MT');
		$newdiscount1->amount_tva = price2num($newdiscount1->amount_ttc - $newdiscount1->amount_ht);
		$newdiscount2->amount_tva = price2num($newdiscount2->amount_ttc - $newdiscount2->amount_ht);

		$newdiscount1->multicurrency_amount_ttc = $amount_ttc_1 * ($discount->multicurrency_amount_ttc / $discount->amount_ttc);
		$newdiscount2->multicurrency_amount_ttc = price2num($discount->multicurrency_amount_ttc - $newdiscount1->multicurrency_amount_ttc);
		$newdiscount1->multicurrency_amount_ht = price2num($newdiscount1->multicurrency_amount_ttc / (1 + $newdiscount1->tva_tx / 100), 'MT');
		$newdiscount2->multicurrency_amount_ht = price2num($newdiscount2->multicurrency_amount_ttc / (1 + $newdiscount2->tva_tx / 100), 'MT');
		$newdiscount1->multicurrency_amount_tva = price2num($newdiscount1->multicurrency_amount_ttc - $newdiscount1->multicurrency_amount_ht);
		$newdiscount2->multicurrency_amount_tva = price2num($newdiscount2->multicurrency_amount_ttc - $newdiscount2->multicurrency_amount_ht);

		$db->begin();
		$discount->fk_facture_source = 0; // This is to delete only the require record (that we will recreate with two records) and not all family with same fk_facture_source
		// This is to delete only the require record (that we will recreate with two records) and not all family with same fk_invoice_supplier_source
		$discount->fk_invoice_supplier_source = 0;
		$res = $discount->delete($user);
		$newid1 = $newdiscount1->create($user);
		$newid2 = $newdiscount2->create($user);
		if ($res > 0 && $newid1 > 0 && $newid2 > 0)
		{
			$db->commit();
			header("Location: ".$_SERVER["PHP_SELF"].'?id='.$id.($backtopage ? '&backtopage='.urlencode($backtopage) : '')); // To avoid pb whith back
			exit;
		} else {
			$db->rollback();
		}
	}
}

if ($action == 'setremise' && $user->rights->societe->creer)
{
	//if ($user->rights->societe->creer)
	//if ($user->rights->facture->creer)

	$amount_ht = price2num(GETPOST('amount_ht', 'alpha'));
	$desc = GETPOST('desc', 'alpha');
	$tva_tx = GETPOST('tva_tx', 'alpha');
	$discount_type = !empty($_POST['discount_type']) ?GETPOST('discount_type', 'alpha') : 0;

	if ($amount_ht > 0)
	{
		$error = 0;
		if (empty($desc))
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ReasonDiscount")), null, 'errors');
			$error++;
		}

		if (!$error)
		{
			$soc = new Societe($db);
			$soc->fetch($id);
			$discountid = $soc->set_remise_except($amount_ht, $user, $desc, $tva_tx, $discount_type);

			if ($discountid > 0)
			{
				if (!empty($backtopage))
				{
					header("Location: ".$backtopage.'&discountid='.$discountid);
					exit;
				} else {
					header("Location: remx.php?id=".$id);
					exit;
				}
			} else {
				$error++;
				setEventMessages($soc->error, $soc->errors, 'errors');
			}
		}
	} else {
		setEventMessages($langs->trans("ErrorFieldFormat", $langs->transnoentitiesnoconv("AmountHT")), null, 'errors');
	}
}

if (GETPOST('action', 'aZ09') == 'confirm_remove' && GETPOST("confirm") == 'yes')
{
	//if ($user->rights->societe->creer)
	//if ($user->rights->facture->creer)

	$db->begin();

	$discount = new DiscountAbsolute($db);
	$result = $discount->fetch(GETPOST("remid"));
	$result = $discount->delete($user);
	if ($result > 0)
	{
		$db->commit();
		header("Location: ".$_SERVER["PHP_SELF"].'?id='.$id); // To avoid pb whith back
		exit;
	} else {
		setEventMessages($discount->error, $discount->errors, 'errors');
		$db->rollback();
	}
}


/*
 * View
 */

$form = new Form($db);
$facturestatic = new Facture($db);
$facturefournstatic = new FactureFournisseur($db);

llxHeader('', $langs->trans("GlobalDiscount"));

if ($socid > 0)
{
	// On recupere les donnees societes par l'objet
	$object = new Societe($db);
	$object->fetch($socid);

	$isCustomer = $object->client == 1 || $object->client == 3;
	$isSupplier = $object->fournisseur == 1;

	/*
	 * Display tabs
	 */
	$head = societe_prepare_head($object);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="setremise">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print dol_get_fiche_head($head, 'absolutediscount', $langs->trans("ThirdParty"), -1, 'company');

	dol_banner_tab($object, 'socid', '', ($user->socid ? 0 : 1), 'rowid', 'nom');

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';

	if (!$isCustomer && !$isSupplier) {
		print '<p class="opacitymedium">'.$langs->trans('ThirdpartyIsNeitherCustomerNorClientSoCannotHaveDiscounts').'</p>';

		print dol_get_fiche_end();

		print '</form>';

		llxFooter();
		$db->close();
		exit;
	}


	print '<table class="border centpercent tableforfield borderbottom">';

	if ($isCustomer) {	// Calcul avoirs client en cours
		$remise_all = $remise_user = 0;
		$sql = "SELECT SUM(rc.amount_ht) as amount, rc.fk_user";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_remise_except as rc";
		$sql .= " WHERE rc.fk_soc = ".$object->id;
		$sql .= " AND rc.entity = ".$conf->entity;
		$sql .= " AND discount_type = 0"; // Exclude supplier discounts
		$sql .= " AND (fk_facture_line IS NULL AND fk_facture IS NULL)";
		$sql .= " GROUP BY rc.fk_user";
		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			$remise_all += $obj->amount;
			if ($obj->fk_user == $user->id) $remise_user += $obj->amount;
		} else {
			dol_print_error($db);
		}

		print '<tr><td class="titlefield">'.$langs->trans("CustomerAbsoluteDiscountAllUsers").'</td>';
		print '<td>'.$remise_all.'&nbsp;'.$langs->trans("Currency".$conf->currency).' '.$langs->trans("HT").'</td></tr>';

		if (!empty($user->fk_soc))    // No need to show this for external users
		{
			print '<tr><td>'.$langs->trans("CustomerAbsoluteDiscountMy").'</td>';
			print '<td>'.$remise_user.'&nbsp;'.$langs->trans("Currency".$conf->currency).' '.$langs->trans("HT").'</td></tr>';
		}
	}

	if ($isSupplier) {
		// Calcul avoirs fournisseur en cours
		$remise_all = $remise_user = 0;
		$sql = "SELECT SUM(rc.amount_ht) as amount, rc.fk_user";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_remise_except as rc";
		$sql .= " WHERE rc.fk_soc = ".$object->id;
		$sql .= " AND rc.entity = ".$conf->entity;
		$sql .= " AND discount_type = 1"; // Exclude customer discounts
		$sql .= " AND (fk_invoice_supplier_line IS NULL AND fk_invoice_supplier IS NULL)";
		$sql .= " GROUP BY rc.fk_user";
		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			$remise_all += $obj->amount;
			if ($obj->fk_user == $user->id) $remise_user += $obj->amount;
		} else {
			dol_print_error($db);
		}

		print '<tr><td class="titlefield">'.$langs->trans("SupplierAbsoluteDiscountAllUsers").'</td>';
		print '<td>'.$remise_all.'&nbsp;'.$langs->trans("Currency".$conf->currency).' '.$langs->trans("HT").'</td></tr>';

		if (!empty($user->fk_soc))    // No need to show this for external users
		{
			print '<tr><td>'.$langs->trans("SupplierAbsoluteDiscountMy").'</td>';
			print '<td>'.$remise_user.'&nbsp;'.$langs->trans("Currency".$conf->currency).' '.$langs->trans("HT").'</td></tr>';
		}
	}

	print '</table>';

	print '</div>';

	print dol_get_fiche_end();


	if ($user->rights->societe->creer)
	{
		print '<br>';

		print load_fiche_titre($langs->trans("NewGlobalDiscount"), '', '');


		if ($isCustomer && !$isSupplier) {
			print '<input type="hidden" name="discount_type" value="0" />';
		}

		if (!$isCustomer && $isSupplier) {
			print '<input type="hidden" name="discount_type" value="1" />';
		}

		print dol_get_fiche_head();

		print '<table class="border centpercent">';
		if ($isCustomer && $isSupplier) {
			print '<tr><td class="titlefield fieldrequired">'.$langs->trans('DiscountType').'</td>';
			print '<td><input type="radio" name="discount_type" id="discount_type_0" checked="checked" value="0"/> <label for="discount_type_0">'.$langs->trans('Customer').'</label>';
			print ' &nbsp; <input type="radio" name="discount_type" id="discount_type_1" value="1"/> <label for="discount_type_1">'.$langs->trans('Supplier').'</label>';
			print '</td></tr>';
		}
		print '<tr><td class="titlefield fieldrequired">'.$langs->trans("AmountHT").'</td>';
		print '<td><input type="text" size="5" name="amount_ht" value="'.price2num(GETPOST("amount_ht")).'">';
		print '<span class="hideonsmartphone">&nbsp;'.$langs->trans("Currency".$conf->currency).'</span></td></tr>';
		print '<tr><td>'.$langs->trans("VAT").'</td>';
		print '<td>';
		print $form->load_tva('tva_tx', GETPOSTISSET('tva_tx') ? GETPOST('tva_tx', 'alpha') : 0, $mysoc, $object, 0, 0, '', 0, 1);
		print '</td></tr>';
		print '<tr><td class="fieldrequired" >'.$langs->trans("NoteReason").'</td>';
		print '<td><input type="text" class="quatrevingtpercent" name="desc" value="'.GETPOST('desc', 'alphanohtml').'"></td></tr>';

		print "</table>";

		print dol_get_fiche_end();
	}

	if ($user->rights->societe->creer)
	{
		print '<div class="center">';
		print '<input type="submit" class="button" name="submit" value="'.$langs->trans("AddGlobalDiscount").'">';
		if (!empty($backtopage))
		{
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
		}
		print '</div>';
	}

	print '</form>';


	print '<br>';

	if ($_GET['action'] == 'remove')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&remid='.GETPOST('remid'), $langs->trans('RemoveDiscount'), $langs->trans('ConfirmRemoveDiscount'), 'confirm_remove', '', 0, 1);
	}


	/*
	 * List not consumed available credits (= linked to no invoice and no invoice line)
	 */

	print load_fiche_titre($langs->trans("DiscountStillRemaining"));

	if ($isCustomer) {
		if ($isSupplier) {
			print '<div class="fichecenter">';
			print '<div class="fichehalfleft fichehalfleft-lg">';
			print load_fiche_titre($langs->trans("CustomerDiscounts"), '', '');
		}

		$sql = "SELECT rc.rowid, rc.amount_ht, rc.amount_tva, rc.amount_ttc, rc.tva_tx, rc.vat_src_code,";
		$sql .= " rc.multicurrency_amount_ht, rc.multicurrency_amount_tva, rc.multicurrency_amount_ttc,";
		$sql .= " rc.datec as dc, rc.description,";
		$sql .= " rc.fk_facture_source,";
		$sql .= " u.login, u.rowid as user_id,";
		$sql .= " fa.ref as ref, fa.type as type";
		$sql .= " FROM  ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."societe_remise_except as rc";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture as fa ON rc.fk_facture_source = fa.rowid";
		$sql .= " WHERE rc.fk_soc = ".$object->id;
		$sql .= " AND rc.entity = ".$conf->entity;
		$sql .= " AND u.rowid = rc.fk_user";
		$sql .= " AND rc.discount_type = 0"; // Eliminate supplier discounts
		$sql .= " AND (rc.fk_facture_line IS NULL AND rc.fk_facture IS NULL)";
		$sql .= " ORDER BY rc.datec DESC";

		$resql = $db->query($sql);
		if ($resql)
		{
			print '<div class="div-table-responsive-no-min">';
			print '<table width="100%" class="noborder">';
			print '<tr class="liste_titre">';
			print '<td class="widthdate">'.$langs->trans("Date").'</td>'; // Need 120+ for format with AM/PM
			print '<td>'.$langs->trans("ReasonDiscount").'</td>';
			print '<td class="nowrap">'.$langs->trans("ConsumedBy").'</td>';
			print '<td class="right">'.$langs->trans("AmountHT").'</td>';
			if (!empty($conf->multicurrency->enabled))
			{
				print '<td class="right">'.$langs->trans("MulticurrencyAmountHT").'</td>';
			}
			print '<td class="right">'.$langs->trans("VATRate").'</td>';
			print '<td class="right">'.$langs->trans("AmountTTC").'</td>';
			if (!empty($conf->multicurrency->enabled))
			{
				print '<td class="right">'.$langs->trans("MulticurrencyAmountTTC").'</td>';
			}
			print '<td width="100" class="center">'.$langs->trans("DiscountOfferedBy").'</td>';
			print '<td width="50">&nbsp;</td>';
			print '</tr>';

			$showconfirminfo = array();

			$i = 0;
			$num = $db->num_rows($resql);
			if ($num > 0)
			{
				while ($i < $num)
				{
					$obj = $db->fetch_object($resql);

					print '<tr class="oddeven">';
					print '<td>'.dol_print_date($db->jdate($obj->dc), 'dayhour').'</td>';
					if (preg_match('/\(CREDIT_NOTE\)/', $obj->description))
					{
						print '<td class="minwidth100">';
						$facturestatic->id = $obj->fk_facture_source;
						$facturestatic->ref = $obj->ref;
						$facturestatic->type = $obj->type;
						print preg_replace('/\(CREDIT_NOTE\)/', $langs->trans("CreditNote"), $obj->description).' '.$facturestatic->getNomURl(1);
						print '</td>';
					} elseif (preg_match('/\(DEPOSIT\)/', $obj->description))
					{
						print '<td class="minwidth100">';
						$facturestatic->id = $obj->fk_facture_source;
						$facturestatic->ref = $obj->ref;
						$facturestatic->type = $obj->type;
						print preg_replace('/\(DEPOSIT\)/', $langs->trans("InvoiceDeposit"), $obj->description).' '.$facturestatic->getNomURl(1);
						print '</td>';
					} elseif (preg_match('/\(EXCESS RECEIVED\)/', $obj->description))
					{
						print '<td class="minwidth100">';
						$facturestatic->id = $obj->fk_facture_source;
						$facturestatic->ref = $obj->ref;
						$facturestatic->type = $obj->type;
						print preg_replace('/\(EXCESS RECEIVED\)/', $langs->trans("ExcessReceived"), $obj->description).' '.$facturestatic->getNomURl(1);
						print '</td>';
					} else {
						print '<td class="minwidth100">';
						print $obj->description;
						print '</td>';
					}
					print '<td class="nowrap">'.$langs->trans("NotConsumed").'</td>';
					print '<td class="right">'.price($obj->amount_ht).'</td>';
					if (!empty($conf->multicurrency->enabled))
					{
						print '<td class="right">'.price($obj->multicurrency_amount_ht).'</td>';
					}
					print '<td class="right">'.vatrate($obj->tva_tx.($obj->vat_src_code ? ' ('.$obj->vat_src_code.')' : ''), true).'</td>';
					print '<td class="right">'.price($obj->amount_ttc).'</td>';
					if (!empty($conf->multicurrency->enabled))
					{
						print '<td class="right">'.price($obj->multicurrency_amount_ttc).'</td>';
					}
					print '<td class="center">';
					print '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"), 'user').' '.$obj->login.'</a>';
					print '</td>';
					if ($user->rights->societe->creer || $user->rights->facture->creer)
					{
						print '<td class="center nowrap">';
						print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=split&remid='.$obj->rowid.($backtopage ? '&backtopage='.urlencode($backtopage) : '').'">'.img_split($langs->trans("SplitDiscount")).'</a>';
						print '<a class="reposition marginleftonly" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=remove&remid='.$obj->rowid.($backtopage ? '&backtopage='.urlencode($backtopage) : '').'">'.img_delete($langs->trans("RemoveDiscount")).'</a>';
						print '</td>';
					} else print '<td>&nbsp;</td>';
					print '</tr>';

					if ($_GET["action"] == 'split' && GETPOST('remid') == $obj->rowid)
					{
						$showconfirminfo['rowid'] = $obj->rowid;
						$showconfirminfo['amount_ttc'] = $obj->amount_ttc;
					}
					$i++;
				}
			} else {
				$colspan = 8;
				if (!empty($conf->multicurrency->enabled)) $colspan += 2;
				print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
			}
			$db->free($resql);
			print "</table>";
			print '</div>';

			if (count($showconfirminfo))
			{
				$amount1 = price2num($showconfirminfo['amount_ttc'] / 2, 'MT');
				$amount2 = ($showconfirminfo['amount_ttc'] - $amount1);
				$formquestion = array(
					'text' => $langs->trans('TypeAmountOfEachNewDiscount'),
					array('type' => 'text', 'name' => 'amount_ttc_1', 'label' => $langs->trans("AmountTTC").' 1', 'value' => $amount1, 'size' => '5'),
					array('type' => 'text', 'name' => 'amount_ttc_2', 'label' => $langs->trans("AmountTTC").' 2', 'value' => $amount2, 'size' => '5')
				);
				$langs->load("dict");
				print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&remid='.$showconfirminfo['rowid'].($backtopage ? '&backtopage='.urlencode($backtopage) : ''), $langs->trans('SplitDiscount'), $langs->trans('ConfirmSplitDiscount', price($showconfirminfo['amount_ttc']), $langs->transnoentities("Currency".$conf->currency)), 'confirm_split', $formquestion, '', 0);
			}
		} else {
			dol_print_error($db);
		}
	}

	if ($isSupplier) {
		if ($isCustomer) {
			print '</div>'; // class="fichehalfleft"
			print '<div class="fichehalfright fichehalfright-lg">';
			print '<div class="ficheaddleft">';
			print load_fiche_titre($langs->trans("SupplierDiscounts"), '', '');
		}

		/*
		 * Liste remises fixes fournisseur restant en cours (= liees a aucune facture ni ligne de facture)
		 */
		$sql = "SELECT rc.rowid, rc.amount_ht, rc.amount_tva, rc.amount_ttc, rc.tva_tx, rc.vat_src_code,";
		$sql .= " rc.multicurrency_amount_ht, rc.multicurrency_amount_tva, rc.multicurrency_amount_ttc,";
		$sql .= " rc.datec as dc, rc.description,";
		$sql .= " rc.fk_invoice_supplier_source,";
		$sql .= " u.login, u.rowid as user_id,";
		$sql .= " fa.ref, fa.type as type";
		$sql .= " FROM  ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."societe_remise_except as rc";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as fa ON rc.fk_invoice_supplier_source = fa.rowid";
		$sql .= " WHERE rc.fk_soc = ".$object->id;
		$sql .= " AND rc.entity = ".$conf->entity;
		$sql .= " AND u.rowid = rc.fk_user";
		$sql .= " AND rc.discount_type = 1"; // Eliminate customer discounts
		$sql .= " AND (rc.fk_invoice_supplier IS NULL AND rc.fk_invoice_supplier_line IS NULL)";
		$sql .= " ORDER BY rc.datec DESC";

		$resql = $db->query($sql);
		if ($resql)
		{
			print '<div class="div-table-responsive-no-min">';
			print '<table width="100%" class="noborder">';
			print '<tr class="liste_titre">';
			print '<td class="widthdate">'.$langs->trans("Date").'</td>'; // Need 120+ for format with AM/PM
			print '<td>'.$langs->trans("ReasonDiscount").'</td>';
			print '<td class="nowrap">'.$langs->trans("ConsumedBy").'</td>';
			print '<td class="right">'.$langs->trans("AmountHT").'</td>';
			if (!empty($conf->multicurrency->enabled))
			{
				print '<td class="right">'.$langs->trans("MulticurrencyAmountHT").'</td>';
			}
			print '<td class="right">'.$langs->trans("VATRate").'</td>';
			print '<td class="right">'.$langs->trans("AmountTTC").'</td>';
			if (!empty($conf->multicurrency->enabled))
			{
				print '<td class="right">'.$langs->trans("MulticurrencyAmountTTC").'</td>';
			}
			print '<td width="100" class="center">'.$langs->trans("DiscountOfferedBy").'</td>';
			print '<td width="50">&nbsp;</td>';
			print '</tr>';

			$showconfirminfo = array();

			$i = 0;
			$num = $db->num_rows($resql);
			if ($num > 0)
			{
				while ($i < $num)
				{
					$obj = $db->fetch_object($resql);

					print '<tr class="oddeven">';
					print '<td>'.dol_print_date($db->jdate($obj->dc), 'dayhour').'</td>';
					if (preg_match('/\(CREDIT_NOTE\)/', $obj->description))
					{
						print '<td class="minwidth100">';
						$facturefournstatic->id = $obj->fk_invoice_supplier_source;
						$facturefournstatic->ref = $obj->ref;
						$facturefournstatic->type = $obj->type;
						print preg_replace('/\(CREDIT_NOTE\)/', $langs->trans("CreditNote"), $obj->description).' '.$facturefournstatic->getNomURl(1);
						print '</td>';
					} elseif (preg_match('/\(DEPOSIT\)/', $obj->description))
					{
						print '<td class="minwidth100">';
						$facturefournstatic->id = $obj->fk_invoice_supplier_source;
						$facturefournstatic->ref = $obj->ref;
						$facturefournstatic->type = $obj->type;
						print preg_replace('/\(DEPOSIT\)/', $langs->trans("InvoiceDeposit"), $obj->description).' '.$facturefournstatic->getNomURl(1);
						print '</td>';
					} elseif (preg_match('/\(EXCESS PAID\)/', $obj->description))
					{
						print '<td class="minwidth100">';
						$facturefournstatic->id = $obj->fk_invoice_supplier_source;
						$facturefournstatic->ref = $obj->ref;
						$facturefournstatic->type = $obj->type;
						print preg_replace('/\(EXCESS PAID\)/', $langs->trans("ExcessPaid"), $obj->description).' '.$facturefournstatic->getNomURl(1);
						print '</td>';
					} else {
						print '<td class="minwidth100">';
						print $obj->description;
						print '</td>';
					}
					print '<td class="nowrap">'.$langs->trans("NotConsumed").'</td>';
					print '<td class="right">'.price($obj->amount_ht).'</td>';
					if (!empty($conf->multicurrency->enabled))
					{
						print '<td class="right">'.price($obj->multicurrency_amount_ht).'</td>';
					}
					print '<td class="right">'.vatrate($obj->tva_tx.($obj->vat_src_code ? ' ('.$obj->vat_src_code.')' : ''), true).'</td>';
					print '<td class="right">'.price($obj->amount_ttc).'</td>';
					if (!empty($conf->multicurrency->enabled))
					{
						print '<td class="right">'.price($obj->multicurrency_amount_ttc).'</td>';
					}
					print '<td class="center">';
					print '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"), 'user').' '.$obj->login.'</a>';
					print '</td>';
					if ($user->rights->societe->creer || $user->rights->facture->creer)
					{
						print '<td class="center nowrap">';
						print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=split&remid='.$obj->rowid.($backtopage ? '&backtopage='.urlencode($backtopage) : '').'">'.img_split($langs->trans("SplitDiscount")).'</a>';
						print '<a class="reposition marginleftonly" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=remove&remid='.$obj->rowid.($backtopage ? '&backtopage='.urlencode($backtopage) : '').'">'.img_delete($langs->trans("RemoveDiscount")).'</a>';
						print '</td>';
					} else print '<td>&nbsp;</td>';
					print '</tr>';

					if ($_GET["action"] == 'split' && GETPOST('remid') == $obj->rowid)
					{
						$showconfirminfo['rowid'] = $obj->rowid;
						$showconfirminfo['amount_ttc'] = $obj->amount_ttc;
					}
					$i++;
				}
			} else {
				$colspan = 8;
				if (!empty($conf->multicurrency->enabled)) $colspan += 2;
				print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
			}
			$db->free($resql);
			print "</table>";
			print '</div>';

			if (count($showconfirminfo))
			{
				$amount1 = price2num($showconfirminfo['amount_ttc'] / 2, 'MT');
				$amount2 = ($showconfirminfo['amount_ttc'] - $amount1);
				$formquestion = array(
					'text' => $langs->trans('TypeAmountOfEachNewDiscount'),
					array('type' => 'text', 'name' => 'amount_ttc_1', 'label' => $langs->trans("AmountTTC").' 1', 'value' => $amount1, 'size' => '5'),
					array('type' => 'text', 'name' => 'amount_ttc_2', 'label' => $langs->trans("AmountTTC").' 2', 'value' => $amount2, 'size' => '5')
				);
				$langs->load("dict");
				print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&remid='.$showconfirminfo['rowid'].($backtopage ? '&backtopage='.urlencode($backtopage) : ''), $langs->trans('SplitDiscount'), $langs->trans('ConfirmSplitDiscount', price($showconfirminfo['amount_ttc']), $langs->transnoentities("Currency".$conf->currency)), 'confirm_split', $formquestion, 0, 0);
			}
		} else {
			dol_print_error($db);
		}

		if ($isCustomer) {
			print '</div>'; // class="ficheaddleft"
			print '</div>'; // class="fichehalfright"
			print '</div>'; // class="fichecenter"
		}
	}

	print '<div class="clearboth"></div><br>';

	/*
	 * List discount consumed (=liees a une ligne de facture ou facture)
	 */

	print load_fiche_titre($langs->trans("DiscountAlreadyCounted"));

	if ($isCustomer) {
		if ($isSupplier) {
			print '<div class="fichecenter">';
			print '<div class="fichehalfleft fichehalfleft-lg">';
			print load_fiche_titre($langs->trans("CustomerDiscounts"), '', '');
		}

		// Discount linked to invoice lines
		$sql = "SELECT rc.rowid, rc.amount_ht, rc.amount_tva, rc.amount_ttc, rc.tva_tx, rc.vat_src_code,";
		$sql .= " rc.multicurrency_amount_ht, rc.multicurrency_amount_tva, rc.multicurrency_amount_ttc,";
		$sql .= " rc.datec as dc, rc.description, rc.fk_facture_line, rc.fk_facture_source,";
		$sql .= " u.login, u.rowid as user_id,";
		$sql .= " f.rowid as invoiceid, f.ref,";
		$sql .= " fa.ref as invoice_source_ref, fa.type as type";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql .= " , ".MAIN_DB_PREFIX."user as u";
		$sql .= " , ".MAIN_DB_PREFIX."facturedet as fc";
		$sql .= " , ".MAIN_DB_PREFIX."societe_remise_except as rc";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture as fa ON rc.fk_facture_source = fa.rowid";
		$sql .= " WHERE rc.fk_soc =".$object->id;
		$sql .= " AND rc.fk_facture_line = fc.rowid";
		$sql .= " AND fc.fk_facture = f.rowid";
		$sql .= " AND rc.fk_user = u.rowid";
		$sql .= " AND rc.discount_type = 0"; // Eliminate supplier discounts
		$sql .= " ORDER BY dc DESC";
		//$sql.= " UNION ";
		// Discount linked to invoices
		$sql2 = "SELECT rc.rowid, rc.amount_ht, rc.amount_tva, rc.amount_ttc, rc.tva_tx, rc.vat_src_code,";
		$sql2 .= " rc.datec as dc, rc.description, rc.fk_facture_line, rc.fk_facture,";
		$sql2 .= " rc.fk_facture_source,";
		$sql2 .= " u.login, u.rowid as user_id,";
		$sql2 .= " f.rowid as invoiceid, f.ref,";
		$sql2 .= " fa.ref as invoice_source_ref, fa.type as type";
		$sql2 .= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql2 .= " , ".MAIN_DB_PREFIX."user as u";
		$sql2 .= " , ".MAIN_DB_PREFIX."societe_remise_except as rc";
		$sql2 .= " LEFT JOIN ".MAIN_DB_PREFIX."facture as fa ON rc.fk_facture_source = fa.rowid";
		$sql2 .= " WHERE rc.fk_soc =".$object->id;
		$sql2 .= " AND rc.fk_facture = f.rowid";
		$sql2 .= " AND rc.fk_user = u.rowid";
		$sql2 .= " AND rc.discount_type = 0"; // Eliminate supplier discounts
		$sql2 .= " ORDER BY dc DESC";

		$resql = $db->query($sql);
		$resql2 = null;
		if ($resql) $resql2 = $db->query($sql2);
		if ($resql2)
		{
			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<td class="widthdate">'.$langs->trans("Date").'</td>'; // Need 120+ for format with AM/PM
			print '<td>'.$langs->trans("ReasonDiscount").'</td>';
			print '<td class="nowrap">'.$langs->trans("ConsumedBy").'</td>';
			print '<td class="right">'.$langs->trans("AmountHT").'</td>';
			if (!empty($conf->multicurrency->enabled))
			{
				print '<td class="right">'.$langs->trans("MulticurrencyAmountHT").'</td>';
			}
			print '<td class="right">'.$langs->trans("VATRate").'</td>';
			print '<td class="right">'.$langs->trans("AmountTTC").'</td>';
			if (!empty($conf->multicurrency->enabled))
			{
				print '<td class="right">'.$langs->trans("MulticurrencyAmountTTC").'</td>';
			}
			print '<td width="100" class="center">'.$langs->trans("Author").'</td>';
			print '<td width="50">&nbsp;</td>';
			print '</tr>';

			$tab_sqlobj = array();
			$tab_sqlobjOrder = array();
			$num = $db->num_rows($resql);
			if ($num > 0)
			{
				for ($i = 0; $i < $num; $i++)
				{
					$sqlobj = $db->fetch_object($resql);
					$tab_sqlobj[] = $sqlobj;
					$tab_sqlobjOrder[] = $db->jdate($sqlobj->dc);
				}
			}
			$db->free($resql);

			$num = $db->num_rows($resql2);
			for ($i = 0; $i < $num; $i++)
			{
				$sqlobj = $db->fetch_object($resql2);
				$tab_sqlobj[] = $sqlobj;
				$tab_sqlobjOrder[] = $db->jdate($sqlobj->dc);
			}
			$db->free($resql2);
			array_multisort($tab_sqlobjOrder, SORT_DESC, $tab_sqlobj);

			$num = count($tab_sqlobj);
			if ($num > 0)
			{
				$i = 0;
				while ($i < $num)
				{
					$obj = array_shift($tab_sqlobj);
					print '<tr class="oddeven">';
					print '<td>'.dol_print_date($db->jdate($obj->dc), 'dayhour').'</td>';
					if (preg_match('/\(CREDIT_NOTE\)/', $obj->description))
					{
						print '<td class="minwidth100">';
						$facturestatic->id = $obj->fk_facture_source;
						$facturestatic->ref = $obj->invoice_source_ref;
						$facturestatic->type = $obj->type;
						print preg_replace('/\(CREDIT_NOTE\)/', $langs->trans("CreditNote"), $obj->description).' '.$facturestatic->getNomURl(1);
						print '</td>';
					} elseif (preg_match('/\(DEPOSIT\)/', $obj->description))
					{
						print '<td class="minwidth100">';
						$facturestatic->id = $obj->fk_facture_source;
						$facturestatic->ref = $obj->invoice_source_ref;
						$facturestatic->type = $obj->type;
						print preg_replace('/\(DEPOSIT\)/', $langs->trans("InvoiceDeposit"), $obj->description).' '.$facturestatic->getNomURl(1);
						print '</td>';
					} elseif (preg_match('/\(EXCESS RECEIVED\)/', $obj->description))
					{
						print '<td class="minwidth100">';
						$facturestatic->id = $obj->fk_facture_source;
						$facturestatic->ref = $obj->invoice_source_ref;
						$facturestatic->type = $obj->type;
						print preg_replace('/\(EXCESS RECEIVED\)/', $langs->trans("Invoice"), $obj->description).' '.$facturestatic->getNomURl(1);
						print '</td>';
					} else {
						print '<td class="minwidth100">';
						print $obj->description;
						print '</td>';
					}
					print '<td class="left nowrap">';
					if ($obj->invoiceid)
					{
						print '<a href="'.DOL_URL_ROOT.'/compta/facture/card.php?facid='.$obj->invoiceid.'">'.img_object($langs->trans("ShowBill"), 'bill').' '.$obj->ref.'</a>';
					}
					print '</td>';
					print '<td class="right">'.price($obj->amount_ht).'</td>';
					if (!empty($conf->multicurrency->enabled))
					{
						print '<td class="right">'.price($obj->multicurrency_amount_ht).'</td>';
					}
					print '<td class="right">'.vatrate($obj->tva_tx.($obj->vat_src_code ? ' ('.$obj->vat_src_code.')' : ''), true).'</td>';
					print '<td class="right">'.price($obj->amount_ttc).'</td>';
					if (!empty($conf->multicurrency->enabled))
					{
						print '<td class="right">'.price($obj->multicurrency_amount_ttc).'</td>';
					}
					print '<td class="center">';
					print '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"), 'user').' '.$obj->login.'</a>';
					print '</td>';
					print '<td>&nbsp;</td>';
					print '</tr>';
					$i++;
				}
			} else {
				$colspan = 8;
				if (!empty($conf->multicurrency->enabled)) $colspan += 2;
				print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
			}

			print "</table>";
			print '</div>';
		} else {
			dol_print_error($db);
		}
	}

	if ($isSupplier) {
		if ($isCustomer) {
			print '</div>'; // class="fichehalfleft"
			print '<div class="fichehalfright fichehalfright-lg">';
			print '<div class="ficheaddleft">';
			print load_fiche_titre($langs->trans("SupplierDiscounts"), '', '');
		}

		// Discount linked to invoice lines
		$sql = "SELECT rc.rowid, rc.amount_ht, rc.amount_tva, rc.amount_ttc, rc.tva_tx, rc.vat_src_code,";
		$sql .= " rc.multicurrency_amount_ht, rc.multicurrency_amount_tva, rc.multicurrency_amount_ttc,";
		$sql .= " rc.datec as dc, rc.description, rc.fk_invoice_supplier_line, rc.fk_invoice_supplier,";
		$sql .= " rc.fk_invoice_supplier_source,";
		$sql .= " u.login, u.rowid as user_id,";
		$sql .= " f.rowid as invoiceid, f.ref as ref,";
		$sql .= " fa.ref as invoice_source_ref, fa.type as type";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
		$sql .= " , ".MAIN_DB_PREFIX."user as u";
		$sql .= " , ".MAIN_DB_PREFIX."facture_fourn_det as fc";
		$sql .= " , ".MAIN_DB_PREFIX."societe_remise_except as rc";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as fa ON rc.fk_invoice_supplier_source = fa.rowid";
		$sql .= " WHERE rc.fk_soc =".$object->id;
		$sql .= " AND rc.fk_invoice_supplier_line = fc.rowid";
		$sql .= " AND fc.fk_facture_fourn = f.rowid";
		$sql .= " AND rc.fk_user = u.rowid";
		$sql .= " AND rc.discount_type = 1"; // Eliminate customer discounts
		$sql .= " ORDER BY dc DESC";
		//$sql.= " UNION ";
		// Discount linked to invoices
		$sql2 = "SELECT rc.rowid, rc.amount_ht, rc.amount_tva, rc.amount_ttc, rc.tva_tx, rc.vat_src_code,";
		$sql2 .= " rc.datec as dc, rc.description, rc.fk_invoice_supplier_line, rc.fk_invoice_supplier,";
		$sql2 .= " rc.fk_invoice_supplier_source,";
		$sql2 .= " u.login, u.rowid as user_id,";
		$sql2 .= " f.rowid as invoiceid, f.ref as ref,";
		$sql2 .= " fa.ref as invoice_source_ref, fa.type as type";
		$sql2 .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
		$sql2 .= " , ".MAIN_DB_PREFIX."user as u";
		$sql2 .= " , ".MAIN_DB_PREFIX."societe_remise_except as rc";
		$sql2 .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_fourn as fa ON rc.fk_invoice_supplier_source = fa.rowid";
		$sql2 .= " WHERE rc.fk_soc =".$object->id;
		$sql2 .= " AND rc.fk_invoice_supplier = f.rowid";
		$sql2 .= " AND rc.fk_user = u.rowid";
		$sql2 .= " AND rc.discount_type = 1"; // Eliminate customer discounts
		$sql2 .= " ORDER BY dc DESC";

		$resql = $db->query($sql);
		$resql2 = null;
		if ($resql) $resql2 = $db->query($sql2);
		if ($resql2)
		{
			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<td class="widthdate">'.$langs->trans("Date").'</td>'; // Need 120+ for format with AM/PM
			print '<td>'.$langs->trans("ReasonDiscount").'</td>';
			print '<td class="nowrap">'.$langs->trans("ConsumedBy").'</td>';
			print '<td class="right">'.$langs->trans("AmountHT").'</td>';
			if (!empty($conf->multicurrency->enabled))
			{
				print '<td class="right">'.$langs->trans("MulticurrencyAmountHT").'</td>';
			}
			print '<td class="right">'.$langs->trans("VATRate").'</td>';
			print '<td class="right">'.$langs->trans("AmountTTC").'</td>';
			if (!empty($conf->multicurrency->enabled))
			{
				print '<td class="right">'.$langs->trans("MulticurrencyAmountTTC").'</td>';
			}
			print '<td width="100" class="center">'.$langs->trans("Author").'</td>';
			print '<td width="50">&nbsp;</td>';
			print '</tr>';

			$tab_sqlobj = array();
			$tab_sqlobjOrder = array();
			$num = $db->num_rows($resql);
			if ($num > 0)
			{
				for ($i = 0; $i < $num; $i++)
				{
					$sqlobj = $db->fetch_object($resql);
					$tab_sqlobj[] = $sqlobj;
					$tab_sqlobjOrder[] = $db->jdate($sqlobj->dc);
				}
			}
			$db->free($resql);

			$num = $db->num_rows($resql2);
			for ($i = 0; $i < $num; $i++)
			{
				$sqlobj = $db->fetch_object($resql2);
				$tab_sqlobj[] = $sqlobj;
				$tab_sqlobjOrder[] = $db->jdate($sqlobj->dc);
			}
			$db->free($resql2);
			array_multisort($tab_sqlobjOrder, SORT_DESC, $tab_sqlobj);

			$num = count($tab_sqlobj);
			if ($num > 0)
			{
				$i = 0;
				while ($i < $num)
				{
					$obj = array_shift($tab_sqlobj);
					print '<tr class="oddeven">';
					print '<td>'.dol_print_date($db->jdate($obj->dc), 'dayhour').'</td>';
					if (preg_match('/\(CREDIT_NOTE\)/', $obj->description))
					{
						print '<td class="minwidth100">';
						$facturefournstatic->id = $obj->fk_invoice_supplier_source;
						$facturefournstatic->ref = $obj->invoice_source_ref;
						$facturefournstatic->type = $obj->type;
						print preg_replace('/\(CREDIT_NOTE\)/', $langs->trans("CreditNote"), $obj->description).' '.$facturefournstatic->getNomURl(1);
						print '</td>';
					} elseif (preg_match('/\(DEPOSIT\)/', $obj->description))
					{
						print '<td class="minwidth100">';
						$facturefournstatic->id = $obj->fk_invoice_supplier_source;
						$facturefournstatic->ref = $obj->invoice_source_ref;
						$facturefournstatic->type = $obj->type;
						print preg_replace('/\(DEPOSIT\)/', $langs->trans("InvoiceDeposit"), $obj->description).' '.$facturefournstatic->getNomURl(1);
						print '</td>';
					} elseif (preg_match('/\(EXCESS PAID\)/', $obj->description))
					{
						print '<td class="minwidth100">';
						$facturefournstatic->id = $obj->fk_invoice_supplier_source;
						$facturefournstatic->ref = $obj->invoice_source_ref;
						$facturefournstatic->type = $obj->type;
						print preg_replace('/\(EXCESS PAID\)/', $langs->trans("Invoice"), $obj->description).' '.$facturefournstatic->getNomURl(1);
						print '</td>';
					} else {
						print '<td class="minwidth100">';
						print $obj->description;
						print '</td>';
					}
					print '<td class="left nowrap">';
					if ($obj->invoiceid) {
						print '<a href="'.DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$obj->invoiceid.'">'.img_object($langs->trans("ShowBill"), 'bill').' '.$obj->ref.'</a>';
					}
					print '</td>';
					print '<td class="right">'.price($obj->amount_ht).'</td>';
					if (!empty($conf->multicurrency->enabled))
					{
						print '<td class="right">'.price($obj->multicurrency_amount_ht).'</td>';
					}
					print '<td class="right">'.vatrate($obj->tva_tx.($obj->vat_src_code ? ' ('.$obj->vat_src_code.')' : ''), true).'</td>';
					print '<td class="right">'.price($obj->amount_ttc).'</td>';
					if (!empty($conf->multicurrency->enabled))
					{
						print '<td class="right">'.price($obj->multicurrency_amount_ttc).'</td>';
					}
					print '<td class="center">';
					print '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"), 'user').' '.$obj->login.'</a>';
					print '</td>';
					print '<td>&nbsp;</td>';
					print '</tr>';
					$i++;
				}
			} else {
				$colspan = 8;
				if (!empty($conf->multicurrency->enabled)) $colspan += 2;
				print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
			}

			print "</table>";
			print '</div>';
		} else {
			dol_print_error($db);
		}

		if ($isCustomer) {
			print '</div>'; // class="ficheaddleft"
			print '</div>'; // class="fichehalfright"
			print '</div>'; // class="fichecenter"
		}
	}
}

// End of page
llxFooter();
$db->close();
