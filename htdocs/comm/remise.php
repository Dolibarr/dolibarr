<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2021 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/comm/remise.php
 *      \ingroup    societe
 *		\brief      Page to edit relative discount of a customer
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'orders', 'bills'));

$id = GETPOST("id", 'int');

$socid = GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('socid', 'int');
// Security check
if ($user->socid > 0) {
	$socid = $user->socid;
}

$backtopage = GETPOST('backtopage', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$action = GETPOST('action', 'aZ09');

// Security check
if ($user->socid > 0) {
	$id = $user->socid;
}
$result = restrictedArea($user, 'societe', $id, '&societe', '', 'fk_soc', 'rowid', 0);


/*
 * Actions
 */

if ($cancel) {
	if (!empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	} else {
		$action = '';
	}
}

if ($action == 'setremise') {
	$object = new Societe($db);
	$object->fetch($id);

	$discount_type = GETPOST('discount_type', 'int');

	if (!empty($discount_type)) {
		$result = $object->set_remise_supplier(price2num(GETPOST("remise")), GETPOST("note", "alphanohtml"), $user);
	} else {
		$result = $object->set_remise_client(price2num(GETPOST("remise")), GETPOST("note", "alphanohtml"), $user);
	}

	if ($result > 0) {
		if (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		} else {
			header("Location: remise.php?id=".GETPOST("id", 'int'));
			exit;
		}
	} else {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}



/*
 * View
 */

$form = new Form($db);

llxHeader();

/*********************************************************************************
 *
 * Mode fiche
 *
 *********************************************************************************/
if ($socid > 0) {
	// On recupere les donnees societes par l'objet
	$object = new Societe($db);
	$object->fetch($socid);

	$head = societe_prepare_head($object);

	$isCustomer = ($object->client == 1 || $object->client == 3);
	$isSupplier = $object->fournisseur == 1;

	print '<form method="POST" action="remise.php?id='.$object->id.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="setremise">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print dol_get_fiche_head($head, 'relativediscount', $langs->trans("ThirdParty"), -1, 'company');

	dol_banner_tab($object, 'socid', '', ($user->socid ? 0 : 1), 'rowid', 'nom');

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';

	if (!$isCustomer && !$isSupplier) {
		print '<p class="opacitymedium">'.$langs->trans('ThirdpartyIsNeitherCustomerNorClientSoCannotHaveDiscounts').'</p>';

		print dol_get_fiche_end();

		print '</form>';

		// End of page
		llxFooter();
		$db->close();
		exit;
	}

	print '<table class="border centpercent">';

	if ($isCustomer) {
		// Customer discount
		print '<tr><td class="titlefield">';
		print $langs->trans("CustomerRelativeDiscount").'</td><td>'.price2num($object->remise_percent)."%</td></tr>";
	}

	if ($isSupplier) {
		// Supplier discount
		print '<tr><td class="titlefield">';
		print $langs->trans("SupplierRelativeDiscount").'</td><td>'.price2num($object->remise_supplier_percent)."%</td></tr>";
	}

	print '</table>';
	print '<br>';

	print load_fiche_titre($langs->trans("NewRelativeDiscount"), '', '');

	print '<div class="underbanner clearboth"></div>';

	/*if (! ($isCustomer && $isSupplier))
	{
		if ($isCustomer && ! $isSupplier) {
			print '<input type="hidden" name="discount_type" value="0" />';
		}
		if (! $isCustomer && $isSupplier) {
			print '<input type="hidden" name="discount_type" value="1" />';
		}
	}*/

	print '<table class="border centpercent">';

	if ($isCustomer || $isSupplier) {
		// Discount type
		print '<tr><td class="titlefield fieldrequired">'.$langs->trans('DiscountType').'</td><td>';
		if ($isCustomer) {
			print '<input type="radio" name="discount_type" id="discount_type_0" '.(GETPOSTISSET('discount_type') ? (GETPOST('discount_type', 'int') == 0 ? ' checked' : '') : ' checked').' value="0"> <label for="discount_type_0">'.$langs->trans('Customer').'</label>';
		}
		if ($isSupplier) {
			print ' <input type="radio" name="discount_type" id="discount_type_1"'.(GETPOSTISSET('discount_type') ? (GETPOST('discount_type', 'int') ? ' checked' : '') : ($isCustomer ? '' : ' checked')).' value="1"> <label for="discount_type_1">'.$langs->trans('Supplier').'</label>';
		}
		print '</td></tr>';
	}

	// New value
	print '<tr><td class="titlefield fieldrequired">';
	print $langs->trans("NewValue").'</td><td><input type="text" size="5" name="remise" value="'.dol_escape_htmltag(GETPOST("remise")).'">%</td></tr>';

	// Motif/Note
	print '<tr><td class="fieldrequired">';
	print $langs->trans("NoteReason").'</td><td><input type="text" size="60" name="note" value="'.dol_escape_htmltag(GETPOST("note", "alphanohtml")).'"></td></tr>';

	print "</table>";

	print '</div>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Modify");

	print "</form>";

	print '<br>';

	if ($isCustomer) {
		if ($isSupplier) {
			print '<div class="fichecenter">';
			print '<div class="fichehalfleft">';
			print load_fiche_titre($langs->trans("CustomerDiscounts"), '', '');
		}

		/*
		 * List log of all customer percent discounts
		 */
		$sql = "SELECT rc.rowid, rc.remise_client as remise_percent, rc.note, rc.datec as dc,";
		$sql .= " u.login, u.rowid as user_id";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_remise as rc, ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE rc.fk_soc = ".((int) $object->id);
		$sql .= " AND rc.entity IN (".getEntity('discount').")";
		$sql .= " AND u.rowid = rc.fk_user_author";
		$sql .= " ORDER BY rc.datec DESC";

		$resql = $db->query($sql);
		if ($resql) {
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<td width="160">'.$langs->trans("Date").'</td>';
			print '<td width="160" align="center">'.$langs->trans("CustomerRelativeDiscountShort").'</td>';
			print '<td class="left">'.$langs->trans("NoteReason").'</td>';
			print '<td class="center">'.$langs->trans("User").'</td>';
			print '</tr>';
			$num = $db->num_rows($resql);
			if ($num > 0) {
				$i = 0;
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
					print '<tr class="oddeven">';
					print '<td>'.dol_print_date($db->jdate($obj->dc), "dayhour").'</td>';
					print '<td class="center">'.price2num($obj->remise_percent).'%</td>';
					print '<td class="left">'.$obj->note.'</td>';
					print '<td align="center"><a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"), 'user').' '.$obj->login.'</a></td>';
					print '</tr>';
					$i++;
				}
			} else {
				print '<tr><td colspan="8"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
			}
			$db->free($resql);
			print "</table>";
		} else {
			dol_print_error($db);
		}
	}

	if ($isSupplier) {
		if ($isCustomer) {
			print '</div>'; // class="fichehalfleft"
			print '<div class="fichehalfright">';
			print load_fiche_titre($langs->trans("SupplierDiscounts"), '', '');
		}

		/*
		 * List log of all supplier percent discounts
		 */
		$sql = "SELECT rc.rowid, rc.remise_supplier as remise_percent, rc.note, rc.datec as dc,";
		$sql .= " u.login, u.rowid as user_id";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_remise_supplier as rc, ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE rc.fk_soc = ".((int) $object->id);
		$sql .= " AND rc.entity IN (".getEntity('discount').")";
		$sql .= " AND u.rowid = rc.fk_user_author";
		$sql .= " ORDER BY rc.datec DESC";

		$resql = $db->query($sql);
		if ($resql) {
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<td width="160">'.$langs->trans("Date").'</td>';
			print '<td width="160" align="center">'.$langs->trans("CustomerRelativeDiscountShort").'</td>';
			print '<td class="left">'.$langs->trans("NoteReason").'</td>';
			print '<td class="center">'.$langs->trans("User").'</td>';
			print '</tr>';
			$num = $db->num_rows($resql);
			if ($num > 0) {
				$i = 0;
				while ($i < $num) {
					$obj = $db->fetch_object($resql);
					print '<tr class="oddeven">';
					print '<td>'.dol_print_date($db->jdate($obj->dc), "dayhour").'</td>';
					print '<td class="center">'.price2num($obj->remise_percent).'%</td>';
					print '<td class="left">'.$obj->note.'</td>';
					print '<td align="center"><a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"), 'user').' '.$obj->login.'</a></td>';
					print '</tr>';
					$i++;
				}
			} else {
				print '<tr><td colspan="8"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
			}
			$db->free($resql);
			print "</table>";
		} else {
			dol_print_error($db);
		}

		if ($isCustomer) {
			print '</div>'; // class="fichehalfright"
			print '</div>'; // class="fichecenter"
		}
	}
}

// End of page
llxFooter();
$db->close();
