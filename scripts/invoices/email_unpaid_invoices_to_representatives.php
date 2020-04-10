#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2013 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2013 Juanjo Menent <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file scripts/invoices/email_unpaid_invoices_to_representatives.php
 * \ingroup facture
 * \brief Script to send a mail to dolibarr users linked to companies with unpaid invoices
 */
$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__.'/';

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

if (!isset($argv[1]) || !$argv[1] || !in_array($argv[1], array('test', 'confirm'))) {
	print "Usage: $script_file (test|confirm) [delay]\n";
	print "\n";
	print "Send an email to users to remind all unpaid customer invoices user is sale representative for.\n";
	print "If you choose 'test' mode, no emails are sent.\n";
	print "If you add a delay (nb of days), only invoice with due date < today + delay are included.\n";
	exit(-1);
}
$mode = $argv[1];

require $path."../../htdocs/master.inc.php";
require_once DOL_DOCUMENT_ROOT."/core/class/CMailFile.class.php";

$langs->load('main');

// Global variables
$version = DOL_VERSION;
$error = 0;

/*
 * Main
 */

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";
dol_syslog($script_file." launched with arg ".join(',', $argv));

$now = dol_now('tzserver');
$duration_value = isset($argv[2]) ? $argv[2] : 'none';

print $script_file." launched with mode ".$mode." default lang=".$langs->defaultlang.(is_numeric($duration_value) ? " delay=".$duration_value : "")."\n";

if ($mode != 'confirm')
	$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

$sql = "SELECT f.ref, f.total_ttc, f.date_lim_reglement as due_date, s.nom as name, s.email, s.default_lang,";
$sql .= " u.rowid as uid, u.lastname, u.firstname, u.email, u.lang";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql .= " , ".MAIN_DB_PREFIX."societe as s";
$sql .= " , ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " , ".MAIN_DB_PREFIX."user as u";
$sql .= " WHERE f.fk_statut = 1 AND f.paye = 0";
$sql .= " AND f.fk_soc = s.rowid";
if (is_numeric($duration_value))
	$sql .= " AND f.date_lim_reglement < '".$db->idate(dol_time_plus_duree($now, $duration_value, "d"))."'";
$sql .= " AND sc.fk_soc = s.rowid";
$sql .= " AND sc.fk_user = u.rowid";
$sql .= " ORDER BY u.email ASC, s.rowid ASC, f.ref ASC"; // Order by email to allow one message per email

// print $sql;
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	$oldemail = 'none';
	$olduid = 0;
	$oldlang = '';
	$total = 0;
	$foundtoprocess = 0;
	print "We found ".$num." couples (unpayed validated invoice - sale representative) qualified\n";
	dol_syslog("We found ".$num." couples (unpayed validated invoice - sale representative) qualified");
	$message = '';

	if ($num) {
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			if (($obj->email != $oldemail || $obj->uid != $olduid) || $oldemail == 'none') {
				// Break onto sales representative (new email or uid)
				if (dol_strlen($oldemail) && $oldemail != 'none') {
					envoi_mail($mode, $oldemail, $message, $total, $oldlang, $oldsalerepresentative);
				} else {
					if ($oldemail != 'none')
						print "- No email sent for ".$oldsalerepresentative.", total: ".$total."\n";
				}
				$oldemail = $obj->email;
				$olduid = $obj->uid;
				$oldlang = $obj->lang;
				$oldsalerepresentative = dolGetFirstLastname($obj->firstname, $obj->lastname);
				$message = '';
				$total = 0;
				$foundtoprocess = 0;
				$salerepresentative = dolGetFirstLastname($obj->firstname, $obj->lastname);
				if (empty($obj->email))
					print "Warning: Sale representative ".$salerepresentative." has no email. Notice disabled.\n";
			}

			// Define line content
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang(empty($obj->lang) ? $langs->defaultlang : $obj->lang); // By default language of sale representative

			// Load translation files required by the page
			$outputlangs->loadLangs(array("main", "bills"));

			if (dol_strlen($obj->email)) {
				$message .= $outputlangs->trans("Invoice")." ".$obj->ref." : ".price($obj->total_ttc, 0, $outputlangs, 0, 0, - 1, $conf->currency)." : ".$obj->name."\n";
				dol_syslog("email_unpaid_invoices_to_representatives.php: ".$obj->email);
				$foundtoprocess++;
			}
			print "Unpaid invoice ".$obj->ref.", price ".price2num($obj->total_ttc).", due date ".dol_print_date($db->jdate($obj->due_date), 'day')." (linked to company ".$obj->name.", sale representative ".dolGetFirstLastname($obj->firstname, $obj->lastname).", email ".$obj->email.", lang ".$outputlangs->defaultlang."): ";
			if (dol_strlen($obj->email))
				print "qualified.";
			else
				print "disqualified (no email).";
			print "\n";

			unset($outputlangs);

			$total += $obj->total_ttc;
			$i++;
		}

		// Si il reste des envois en buffer
		if ($foundtoprocess) {
			if (dol_strlen($oldemail) && $oldemail != 'none') // Break onto email (new email)
			{
				envoi_mail($mode, $oldemail, $message, $total, $oldlang, $oldsalerepresentative);
			} else {
				if ($oldemail != 'none')
					print "- No email sent for ".$oldsalerepresentative.", total: ".$total."\n";
			}
		}
	} else {
		print "No unpaid invoices (for companies linked to a particular commercial dolibarr user) found\n";
	}

	exit(0);
} else {
	dol_print_error($db);
	dol_syslog("email_unpaid_invoices_to_representatives.php: Error");

	exit(-1);
}

/**
 * Send email
 *
 * @param string $mode						Mode (test | confirm)
 * @param string $oldemail					Old email
 * @param string $message					Message to send
 * @param string $total						Total amount of unpayed invoices
 * @param string $userlang					Code lang to use for email output.
 * @param string $oldsalerepresentative		Old sale representative
 * @return int 								<0 if KO, >0 if OK
 */
function envoi_mail($mode, $oldemail, $message, $total, $userlang, $oldsalerepresentative)
{
	global $conf, $langs;

	if (getenv('DOL_FORCE_EMAIL_TO'))
		$oldemail = getenv('DOL_FORCE_EMAIL_TO');

	$newlangs = new Translate('', $conf);
	$newlangs->setDefaultLang(empty($userlang) ? (empty($conf->global->MAIN_LANG_DEFAULT) ? 'auto' : $conf->global->MAIN_LANG_DEFAULT) : $userlang);
	$newlangs->load("main");
	$newlangs->load("bills");

	$subject = (empty($conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_SALESREPRESENTATIVES_SUBJECT) ? $newlangs->trans("ListOfYourUnpaidInvoices") : $conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_SALESREPRESENTATIVES_SUBJECT);
	$sendto = $oldemail;
	$from = $conf->global->MAIN_MAIL_EMAIL_FROM;
	$errorsto = $conf->global->MAIN_MAIL_ERRORS_TO;
	$msgishtml = - 1;

	print "- Send email for ".$oldsalerepresentative." (".$oldemail."), total: ".$total."\n";
	dol_syslog("email_unpaid_invoices_to_representatives.php: send mail to ".$oldemail);

	$usehtml = 0;
	if (dol_textishtml($conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_SALESREPRESENTATIVES_FOOTER))
		$usehtml += 1;
	if (dol_textishtml($conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_SALESREPRESENTATIVES_HEADER))
		$usehtml += 1;

	$allmessage = '';
	if (!empty($conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_SALESREPRESENTATIVES_HEADER)) {
		$allmessage .= $conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_SALESREPRESENTATIVES_HEADER;
	} else {
		$allmessage .= $newlangs->transnoentities("ListOfYourUnpaidInvoices").($usehtml ? "<br>\n" : "\n").($usehtml ? "<br>\n" : "\n");
		$allmessage .= $newlangs->transnoentities("NoteListOfYourUnpaidInvoices").($usehtml ? "<br>\n" : "\n");
	}
	$allmessage .= $message.($usehtml ? "<br>\n" : "\n");
	$allmessage .= $langs->trans("Total")." = ".price($total, 0, $newlangs, 0, 0, - 1, $conf->currency).($usehtml ? "<br>\n" : "\n");
	if (!empty($conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_SALESREPRESENTATIVES_FOOTER)) {
		$allmessage .= $conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_SALESREPRESENTATIVES_FOOTER;
		if (dol_textishtml($conf->global->SCRIPT_EMAIL_UNPAID_INVOICES_SALESREPRESENTATIVES_FOOTER))
			$usehtml += 1;
	}

	$mail = new CMailFile($subject, $sendto, $from, $allmessage, array(), array(), array(), '', '', 0, $msgishtml);

	$mail->errors_to = $errorsto;

	// Send or not email
	if ($mode == 'confirm') {
		$result = $mail->sendfile();
		if (!$result) {
			print "Error sending email ".$mail->error."\n";
			dol_syslog("Error sending email ".$mail->error."\n");
		}
	} else {
		print "No email sent (test mode)\n";
		dol_syslog("No email sent (test mode)");
		$mail->dump_mail();
		$result = 1;
	}

	if ($result) {
		return 1;
	} else {
		return -1;
	}
}
