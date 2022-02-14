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
 *    \file       handson/mailindex.php
 *    \ingroup    handson
 *    \brief      Home page of mail menu
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
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

global $conf, $langs, $db;

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
$error = '';
if ($action == 'trigger_order_conf') {

	include_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
	include_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
	include_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

	$order = new Commande($db);
	$order->fetch((int) GETPOST('refnr', 'aZ09'));
	$contactId = $order->getIdContact('external', "CUSTOMER");
	$foerd = $order->array_options['options_foerderung'];


	if (!is_dir(DOL_DATA_ROOT . '/commande/' . $order->ref . '/' . $order->ref . '.pdf')) {
		include_once DOL_DOCUMENT_ROOT . '/core/class/translate.class.php';
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang('de_DE');
		$order->generateDocument('', $outputlangs);
	}

	// All contact data is now in $contact
	$contact = new Contact($db);
	$contact->fetch($contactId[0]);

	switch($foerd) {
		case "1":
			$template = 'confirmOrder_KLA_FOERD_CHA_HESSEN';
			break;
		case "4":
			$template = 'confirmOrder_KLA_FOERD_CHA_SIEMENS';
			break;
		case "5":
			$template = 'confirmOrder_KLA_FOERD_CHA_SIEMENS';
			break;
		default:
			$template = 'confirmOrder_KLA_2020-21';
	}

	$sql = "SELECT topic, content, joinfiles FROM llx_c_email_templates WHERE label='" . $template . "'";
	$result = $db->query($sql)->fetch_array(MYSQLI_ASSOC);

	$to = $contact->email;
	$from = 'HANDS on TECHNOLOGY e.V.<info@hands-on-technology.org>';
	$file = array(DOL_DATA_ROOT . '/commande/' . $order->ref . '/' . $order->ref . '.pdf');
	$mime = array('application/pdf');
	$filnenames = array($order->ref . '.pdf');


	$content = $result['content'];
	$content = str_replace('__BESTELLNUMMER__', $order->ref, $content);
	$content = str_replace('__VORNAME__', $contact->firstname, $content);
	$content = str_replace('__NACHNAME__', $contact->lastname, $content);

	$mailfile = new CMailFile($result['topic'], $to, $from, $content, $file, $mime, $filnenames, '', '', 0, 1);

	if ($mailfile->sendfile()) {
		$error = 'Bestätigung verschickt';
	} else {
		$error = $langs->trans("ErrorFailedToSendMail", $from, $to) . '.<br> ' . $mailfile->error;
	}


}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
$com = new Commande($db);

llxHeader("", $langs->trans("HandsOnArea"));
//var_dump($com);
print load_fiche_titre($langs->trans("E-Mail-Bestätigung von Hand triggern"), '', 'handson.png@handson');
print $error.'<br>';
print '<form action="mailindex.php" method="POST">';

//print 'Bestellnummer: <input type="text" name="refnr"> <input type="submit" class="button" value="Bestätigung senden">';
print 'Bestellnummer: '.$form->selectForForms('Commande:/commande/class/commande.class.php','refnr','').' <input type="submit" class="button" value="Bestätigung senden">';

print '<input type="hidden" name="action" value="trigger_order_conf">';
print '</form>';


// End of page
llxFooter();
$db->close();
