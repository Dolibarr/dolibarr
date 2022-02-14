<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *    \file       handson/handsonindex.php
 *    \ingroup    handson
 *    \brief      Home page of handson top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/handson/class/programm.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/handson/class/saison.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/handson/class/region.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/handson/class/foerderung.class.php';


// Load translation files required by the page
$langs->loadLangs(array("handson@handson"));

$action = GETPOST('action', 'aZ09');


// Security check
// if (! $user->rights->handson->myobject->read) {
// 	accessforbidden();
// }
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$programmstatic = new Programm($db);
$saisonstatic = new Saison($db);
$regionstatic = new Region($db);
$foerderungstatic = new Foerderung($db);


llxHeader("", $langs->trans("HandsOnArea"));

print load_fiche_titre($langs->trans("Wähle links aus, was du bearbeiten möchtest."), '', 'handson.png@handson');


print '<div class="fichecenter"><div class="fichehalfleft">';

if (!empty($conf->handson->enabled) && $user->rights->handson->programm->read) {
	$langs->load("Programme");

	$sql = "SELECT c.rowid, c.ref";
	$sql .= " FROM " . MAIN_DB_PREFIX . "handson_programm as c";

	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">' . $langs->trans("Programme") . ($num ? '<span class="badge marginleftonlyshort">' . $num . '</span>' : '') . '</th></tr>';

		$var = true;
		if ($num > 0) {
			$i = 0;
			while ($i < $num) {

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td class="nowrap">';

				$programmstatic->id = $obj->rowid;
				$programmstatic->ref = $obj->ref;
				$obj->total_ttc = 25;


				print $programmstatic->getNomUrl(0);
				print '</td>';
				print '<td class="nowrap">';
				print '</td>';
				print '</tr>';
				$i++;
			}

		} else {

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">' . $langs->trans("NoOrder") . '</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}

print '</div><div class="fichehalfright">';

if (!empty($conf->handson->enabled) && $user->rights->handson->saison->read) {
	$langs->load("Programme");

	$sql = "SELECT c.rowid, c.ref";
	$sql .= " FROM " . MAIN_DB_PREFIX . "handson_saison as c";

	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">' . $langs->trans("Saisons") . ($num ? '<span class="badge marginleftonlyshort">' . $num . '</span>' : '') . '</th></tr>';

		$var = true;
		if ($num > 0) {
			$i = 0;
			while ($i < $num) {

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td class="nowrap">';

				$saisonstatic->id = $obj->rowid;
				$saisonstatic->ref = $obj->ref;

				print $saisonstatic->getNomUrl(0);
				print '</td>';
				print '<td class="nowrap">';
				print '</td>';
				print '</tr>';
				$i++;
			}

		} else {

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">' . $langs->trans("NoOrder") . '</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}

print '</div></div><div class="fichecenter"><div class="fichehalfleft">';

if (!empty($conf->handson->enabled) && $user->rights->handson->foerderung->read) {
	$langs->load("Förderungen");

	$sql = "SELECT c.rowid, c.ref";
	$sql .= " FROM " . MAIN_DB_PREFIX . "handson_foerderung as c";

	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">' . $langs->trans("Förderungen") . ($num ? '<span class="badge marginleftonlyshort">' . $num . '</span>' : '') . '</th></tr>';

		$var = true;
		if ($num > 0) {
			$i = 0;
			while ($i < $num) {

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td class="nowrap">';

				$saisonstatic->id = $obj->rowid;
				$saisonstatic->ref = $obj->ref;

				print $saisonstatic->getNomUrl(0);
				print '</td>';
				print '<td class="nowrap">';
				print '</td>';
				print '</tr>';
				$i++;
			}

		} else {

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">' . $langs->trans("NoOrder") . '</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}

print '</div>';


$pictos = array(
	'1downarrow', '1uparrow', '1leftarrow', '1rightarrow', '1uparrow_selected', '1downarrow_selected', '1leftarrow_selected', '1rightarrow_selected',
	'accountancy', 'account', 'accountline', 'action', 'add', 'address', 'bank_account', 'barcode', 'bank', 'bill', 'billa', 'billr', 'billd', 'bookmark', 'bom', 'building',
	'cash-register', 'category', 'check', 'clock', 'close_title', 'company', 'contact', 'contract', 'cron', 'cubes',
	'delete', 'dolly', 'dollyrevert', 'donation', 'download', 'edit', 'ellipsis-h', 'email', 'eraser', 'external-link-alt', 'external-link-square-alt',
	'filter', 'file-code', 'file-export', 'file-import', 'file-upload', 'folder', 'folder-open', 'globe', 'globe-americas', 'grip', 'grip_title', 'group',
	'help', 'holiday',
	'intervention', 'label', 'language', 'link', 'list', 'listlight', 'lot',
	'map-marker-alt', 'member', 'money-bill-alt', 'mrp', 'note', 'next',
	'object_accounting', 'object_account', 'object_accountline', 'object_action', 'object_barcode', 'object_bill', 'object_billa', 'object_billd', 'object_bom',
	'object_category', 'object_conversation', 'object_bookmark', 'object_bug', 'object_clock', 'object_dolly', 'object_dollyrevert', 'object_generic', 'object_folder',
	'object_list-alt', 'object_calendar', 'object_calendarweek', 'object_calendarmonth', 'object_calendarday', 'object_calendarperuser',
	'object_cash-register', 'object_company', 'object_contact', 'object_contract', 'object_donation', 'object_dynamicprice',
	'object_globe', 'object_holiday', 'object_hrm', 'object_invoice', 'object_intervention', 'object_label',
	'object_margin', 'object_money-bill-alt', 'object_multicurrency', 'object_order', 'object_payment',
	'object_lot', 'object_mrp', 'object_other',
	'object_payment', 'object_pdf', 'object_product', 'object_propal',
	'object_paragraph', 'object_poll', 'object_printer', 'object_project', 'object_projectpub', 'object_propal', 'object_resource', 'object_rss', 'object_projecttask',
	'object_recruitmentjobposition', 'object_recruitmentcandidature',
	'object_shipment', 'object_share-alt', 'object_supplier_invoice', 'object_supplier_invoicea', 'object_supplier_invoiced', 'object_supplier_order', 'object_supplier_proposal', 'object_service', 'object_stock',
	'object_technic', 'object_ticket', 'object_trip', 'object_user', 'object_group', 'object_member',
	'object_phoning', 'object_phoning_mobile', 'object_phoning_fax', 'object_email', 'object_website',
	'off', 'on', 'order',
	'paiment', 'play', 'pdf', 'playdisabled', 'previous', 'poll', 'printer', 'product', 'propal', 'projecttask', 'stock', 'resize', 'service', 'stats', 'trip',
	'setup', 'share-alt', 'sign-out', 'split', 'stripe-s', 'switch_off', 'switch_on', 'tools', 'unlink', 'uparrow', 'user', 'vcard', 'wrench',
	'jabber', 'skype', 'twitter', 'facebook', 'linkedin', 'instagram', 'snapchat', 'youtube', 'google-plus-g', 'whatsapp',
	'chevron-left', 'chevron-right', 'chevron-down', 'chevron-top', 'commercial', 'companies',
	'generic', 'home', 'hrm', 'members', 'products', 'invoicing',
	'payment', 'pencil-ruler', 'preview', 'project', 'projectpub', 'refresh', 'supplier_invoice', 'ticket',
	'error', 'warning',
	'recruitmentcandidature', 'recruitmentjobposition', 'resource',
	'supplier_proposal', 'supplier_order', 'supplier_invoice',
	'title_setup', 'title_accountancy', 'title_bank', 'title_hrm', 'title_agenda',
	'envelope'
);

print '<div class="fichehalfright">';

print_fiche_titre($langs->trans("Alle verfügabren Icons"));

foreach ($pictos as $picto) {
	print img_picto($picto, $picto, 'style="font-size: 2em;"', 0, 0, 0);
}

print '</div></div>';

// End of page
llxFooter();
$db->close();
