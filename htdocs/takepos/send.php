<?php
/* Copyright (C) 2019	Thibault FOUCART      <support@ptibogxiv.net>
 * Copyright (C) 2020	Andreu Bisquerra Gaya <jove@bisquerra.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2025-2026	MDW						<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/takepos/send.php
 *	\ingroup	takepos
 *	\brief      Page with the content of the popup to enter payments
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER', '1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB', '1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))	define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN'))	define('NOREQUIRETRAN', '1');
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

// Load Dolibarr environment
require '../main.inc.php'; // Load $user and permissions
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$facid = GETPOSTINT('facid');
$action = GETPOST('action', 'aZ09');
$email = GETPOST('email', 'alpha');

if (!$user->hasRight('takepos', 'run')) {
	accessforbidden();
}

$langs->loadLangs(array("main", "bills", "cashdesk"));

$invoice = new Facture($db);
$invoice->fetch($facid);
$customer = new Societe($db);
$customer->fetch($invoice->socid);

$error = 0;


/*
 * Actions
 */

if ($action == "send" && $user->hasRight('takepos', 'run')) {
	top_httphead('text/html');

	include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	$formmail = new FormMail($db);
	$outputlangs = new Translate('', $conf);
	$model_id = getDolGlobalInt('TAKEPOS_EMAIL_TEMPLATE_INVOICE');
	$arraydefaultmessage = $formmail->getEMailTemplate($db, 'facture_send', $user, $outputlangs, $model_id);

	// Subject
	$subject = $arraydefaultmessage->topic;

	$receipt = '';

	$joinFile = [];
	$joinFileName = [];
	$joinFileMime = [];

	if (!getDolGlobalString('TAKEPOS_SEND_INVOICE_AS_PDF')) {
		$nojs = 1;	// used by include of takepos/receipt.php

		ob_start(); // turn on output receipt
		include DOL_DOCUMENT_ROOT.'/takepos/receipt.php';
		$receipt = ob_get_contents(); // get the contents of the output buffer
		ob_end_clean();
	} else {
		if ($arraydefaultmessage->joinfiles == 1 && !empty($invoice->last_main_doc)) {
			// TODO
			// Generate a new customer using the email, switch invoice to the new customer
			// TODO Ask also name/lastname to complete the customer card
			$joinFile[] = DOL_DATA_ROOT.'/'.$invoice->last_main_doc;
			$joinFileName[] = basename($invoice->last_main_doc);
			$joinFileMime[] = dol_mimetype(DOL_DATA_ROOT.'/'.$invoice->last_main_doc);
		}
	}

	// From / To
	$sendto = $email;
	$from = $mysoc->email;

	// Content
	$msg = "<html>";
	$msg .= $arraydefaultmessage->content;
	if ($receipt) {
		$msg .= "<br>";
		$msg .= $receipt;
	}
	$msg .= "</html>";

	// Send email
	$mail = new CMailFile($subject, $sendto, $from, $msg, $joinFile, $joinFileMime, $joinFileName, '', '', 0, 1, '', '', '', '', '', '', DOL_DATA_ROOT.'/documents/takepos/temp');

	if ($mail->error || !empty($mail->errors)) {
		setEventMessages($mail->error, $mail->errors, 'errors');

		print 'Failed to send email: '.$mail->error;

		http_response_code(500);
	} else {
		$result = $mail->sendfile();
		if ($result) {
			$triggersendname = 'BILL_SENTBYMAIL';
			$object = $invoice;
			$object->context['email_from'] = $from;
			$object->context['email_to'] = $sendto;
			$object->context['email_msgid'] = $mail->msgid;

			// Same code as in actions_sendmail.inc.php
			// If sending email for invoice, we increase the counter of invoices sent by email
			$sql = "UPDATE ".MAIN_DB_PREFIX."facture SET email_sent_counter = email_sent_counter + 1";
			$sql .= " WHERE rowid = ".((int) $object->id);

			$resql = $db->query($sql);
			if ($resql) {
				$object->email_sent_counter += 1;
			}
			// }

			$result = $object->call_trigger($triggersendname, $user);  // @phan-suppress-current-line PhanPossiblyUndeclaredGlobalVariable
			if ($result < 0) {
				$error++;
			}
			if ($error) {
				setEventMessages($object->error, $object->errors, 'errors');
			}

			if (!$error) {
				print 'Mail successfully sent to '.$sendto.' with subject '.$subject;
				print "\n";
				print 'If template ask to join file, it may include the file '.implode(',', $joinFile);
			} else {
				http_response_code(500);
			}
		} else {
			print 'Failed to send email: '.$mail->error;

			http_response_code(500);
		}
	}

	exit;
}


/*
 * View
 */

$arrayofcss = array('/takepos/css/pos.css.php');
$arrayofjs  = array();
$head = '';

top_htmlhead($head, '', 0, 0, $arrayofjs, $arrayofcss);

?>
<body class="center">

<script>
function SendMail() {
	$.ajax({
		type: "GET",
		data: { token: '<?php echo currentToken(); ?>' },
		url: '<?php print DOL_URL_ROOT.'/takepos/send.php?action=send&token='.newToken().'&facid='.((int) $facid).'&email='; ?>' + $("#email").val(),
		success: function(response) {
			console.log("Email sent");
			alert("Email sent");
		},
		error: function(xhr, status, error) {
			console.log("Failed to send email");
			alert("Failed to send email : " + error);
		}
	});
	parent.$.colorbox.close();
}

</script>

<div class="center">
<center>
<center>
<input type="email" id="email" name="email" style="width:60%;font-size: 200%;" value="<?php echo $customer->email; ?>"></center>
</center>
</div>
<br>
<div class="center">

<button type="button" class="calcbutton"  onclick="SendMail()"><?php print $langs->trans("SendTicket"); ?></button>

</div>

</body>
</html>
