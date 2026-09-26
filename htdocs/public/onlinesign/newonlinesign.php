<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2023		anthony Berton			<anthony.berton@bb2a.fr>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026		Lenin Rivas				<lenin.rivas777@gmail.com>
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
 *     	\file       htdocs/public/onlinesign/newonlinesign.php
 *		\ingroup    core
 *		\brief      File to offer a way to make an online signature for a particular Dolibarr entity
 *					Example of URL: https://localhost/public/onlinesign/newonlinesign.php?ref=PR...
 *
 *					The signature is added by calling the file /htdocs/core/ajax/onlinSign.php
 */

if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// Because 2 entities can have the same ref.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 *
 * @var string $dolibarr_main_url_root
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/signature.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';

// Load translation files
$langs->loadLangs(array("main", "other", "dict", "bills", "companies", "errors", "members", "paybox", "stripe", "propal", "commercial"));

// Security check
// No check on module enabled. Done later according to $validpaymentmethod

// Get parameters
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');


$refusepropal = GETPOST('refusepropal', 'alpha');
$message = GETPOST('message', 'aZ09');

// Input are:
// type ('invoice','order','contractline'),
// id (object id),
// amount (required if id is empty),
// tag (a free text, required if type is empty)
// currency (iso code)

$suffix = GETPOST("suffix", 'aZ09');
$source = (string) GETPOST("source", 'aZ09');
$ref = $REF = GETPOST("ref", 'alpha');
$urlok = '';
$urlko = '';

if ($source === '') {
	$source = 'proposal';
}
if (!empty($refusepropal)) {
	$action = "refusepropal";
}

// Define $urlwithroot
//$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
//$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
$urlwithroot = DOL_MAIN_URL_ROOT; // This is to use same domain name than current. For Paypal payment, we can use internal URL like localhost.


// Complete urls for post treatment
$SECUREKEY = GETPOST("securekey"); // Secure key

$urlok .= 'source='.urlencode($source).'&';
$urlko .= 'source='.urlencode($source).'&';
if (!empty($REF)) {
	$urlok .= 'ref='.urlencode($REF).'&';
	$urlko .= 'ref='.urlencode($REF).'&';
}
if (!empty($SECUREKEY)) {
	$urlok .= 'securekey='.urlencode($SECUREKEY).'&';
	$urlko .= 'securekey='.urlencode($SECUREKEY).'&';
}
if (!empty($entity)) {
	$urlok .= 'entity='.urlencode((string) ($entity)).'&';
	$urlko .= 'entity='.urlencode((string) ($entity)).'&';
}
$urlok = preg_replace('/&$/', '', $urlok); // Remove last &
$urlko = preg_replace('/&$/', '', $urlko); // Remove last &

$creditor = $mysoc->name;

$type = $source;
if (!$action) {
	if (!$ref) {
		httponly_accessforbidden($langs->trans('ErrorBadParameters')." - ref missing", 400, 1);
	}
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('onlinesign'));

$sourceDefinition = getOnlineSignatureSourceDefinition($source, $ref, (int) $entity);
if (empty($sourceDefinition)) {
	httponly_accessforbidden($langs->trans('ErrorBadParameters')." - Bad value for source. Value not supported.", 400, 1);
}

if (!empty($sourceDefinition['langfiles']) && is_array($sourceDefinition['langfiles'])) {
	$langs->loadLangs($sourceDefinition['langfiles']);
}

if (!isOnlineSignatureSourceEnabled($sourceDefinition)) {
	httponly_accessforbidden($langs->trans('FeatureOnlineSignDisabled'), 403, 1);
}

if (!verifyOnlineSignatureSecureKey($sourceDefinition, $ref, (int) $entity, $SECUREKEY)) {
	httponly_accessforbidden('Bad value for securitykey. Value provided '.dol_escape_htmltag($SECUREKEY).' does not match expected value for ref='.dol_escape_htmltag($ref), 403, 1);
}

$object = fetchOnlineSignatureObject($sourceDefinition, $ref, (int) $entity);
if (!is_object($object) || empty($object->id)) {
	httponly_accessforbidden($langs->trans('ErrorRecordNotFound'), 404, 1);
}

$error = 0;
$mesg = '';


/*
 * Actions
 */

if ($action == 'confirm_refusepropal' && $confirm == 'yes' && $source === 'proposal' && $object instanceof Propal) {	// Test on permission not required here. Public form. Security checked on the securekey and on mitigation
	$db->begin();

	$sql  = "UPDATE ".MAIN_DB_PREFIX."propal";
	$sql .= " SET fk_statut = ".((int) $object::STATUS_NOTSIGNED).", note_private = '".$db->escape($object->note_private)."', date_signature = '".$db->idate(dol_now())."'";
	$sql .= " WHERE rowid = ".((int) $object->id);

	dol_syslog(__FILE__, LOG_DEBUG);
	$resql = $db->query($sql);
	if (!$resql) {
		$error++;
	}

	if (!$error) {
		$db->commit();

		$message = 'refused';
		setEventMessages("PropalRefused", null, 'warnings');
		$object->context = array('closedfromonlinesignature' => 'closedfromonlinesignature');
		$result = $object->call_trigger('PROPAL_CLOSE_REFUSED', $user);
		if ($result < 0) {
			$error++;
		}
	} else {
		$db->rollback();
	}

	$object->fetch(0, $ref);
}

// $action == "dosign" is handled later...


/*
 * View
 */

$form = new Form($db);

$head = '';
if (getDolGlobalString('MAIN_SIGN_CSS_URL')) {
	$head = '<link rel="stylesheet" type="text/css" href="' . getDolGlobalString('MAIN_SIGN_CSS_URL').'?lang='.$langs->defaultlang.'">'."\n";
}

$conf->dol_hide_topmenu = 1;
$conf->dol_hide_leftmenu = 1;

$title = $langs->trans("OnlineSignature");

$replacemainarea = (empty($conf->dol_hide_leftmenu) ? '<div>' : '').'<div>';
llxHeader($head, $title, '', '', 0, 0, '', '', '', 'onlinepaymentbody', $replacemainarea, 1);

htmlPrintOnlineHeader($mysoc, $langs, 1, '', 'ONLINE_SIGN_IMAGE_PUBLIC_INTERFACE', 'ONLINE_SIGN_LOGO_'.$suffix, 'ONLINE_SIGN_LOGO');

if ($action == 'refusepropal') {
	print $form->formconfirm($_SERVER["PHP_SELF"].'?ref='.urlencode($ref).'&securekey='.urlencode($SECUREKEY).(isModEnabled('multicompany') ? '&entity='.$entity : ''), $langs->trans('RefusePropal'), $langs->trans('ConfirmRefusePropal', $object->ref), 'confirm_refusepropal', '', '', 1);
}

// Check link validity for param 'source' to avoid use of the examples as value
if (/* $source !== '' :never empty &&  */ in_array($ref, array('member_ref', 'contractline_ref', 'invoice_ref', 'order_ref', 'proposal_ref', ''))) {
	$langs->load("errors");
	dol_print_error_email('BADREFINONLINESIGNFORM', $langs->trans("ErrorBadLinkSourceSetButBadValueForRef", $source, $ref));
	// End of page
	llxFooter();
	$db->close();
	exit;
}

print '<span id="dolpaymentspan"></span>'."\n";
print '<div class="center">'."\n";
print '<form id="dolpaymentform" class="center" name="paymentform" action="'.$_SERVER["PHP_SELF"].'" method="POST">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
print '<input type="hidden" name="action" value="dosign">'."\n";
print '<input type="hidden" name="tag" value="'.GETPOST("tag", 'alpha').'">'."\n";
print '<input type="hidden" name="suffix" value="'.GETPOST("suffix", 'alpha').'">'."\n";
print '<input type="hidden" name="securekey" value="'.$SECUREKEY.'">'."\n";
print '<input type="hidden" name="entity" value="'.$entity.'" />';
print '<input type="hidden" name="page_y" value="" />';
print '<input type="hidden" name="source" value="'.$source.'" />';
print '<input type="hidden" name="ref" value="'.$ref.'" />';
print "\n";
print '<!-- Form to sign -->'."\n";

if ($source == 'proposal' && getDolGlobalString('PROPOSAL_IMAGE_PUBLIC_SIGN')) {
	print '<div class="backimagepublicproposalsign">';
	print '<img id="idPROPOSAL_IMAGE_PUBLIC_INTERFACE" src="' . getDolGlobalString('PROPOSAL_IMAGE_PUBLIC_SIGN').'">';
	print '</div>';
}

print '<table id="dolpublictable" summary="Payment form" class="center">'."\n";

// Output introduction text
$text = '';
if (getDolGlobalString('ONLINE_SIGN_NEWFORM_TEXT')) {
	$reg = array();
	if (preg_match('/^\((.*)\)$/', $conf->global->ONLINE_SIGN_NEWFORM_TEXT, $reg)) {
		$text .= $langs->trans($reg[1])."<br>\n";
	} else {
		$text .= getDolGlobalString('ONLINE_SIGN_NEWFORM_TEXT') . "<br>\n";
	}
	$text = '<tr><td align="center"><br>'.$text.'<br></td></tr>'."\n";
}
if (empty($text)) {
	if ($source === 'proposal' && $object instanceof Propal) {
		$text .= '<tr><td class="textpublicpayment"><br><strong>'.$langs->trans("WelcomeOnOnlineSignaturePageProposal", $mysoc->name).'</strong></td></tr>'."\n";
		$text .= '<tr><td class="textpublicpayment small opacitymedium">'.$langs->trans("ThisScreenAllowsYouToSignDocFromProposal", $creditor).'<br><br></td></tr>'."\n";
	} elseif ($source === 'contract' && $object instanceof Contrat) {
		$text .= '<tr><td class="textpublicpayment"><br><strong>'.$langs->trans("WelcomeOnOnlineSignaturePageContract", $mysoc->name).'</strong></td></tr>'."\n";
		$text .= '<tr><td class="textpublicpayment small opacitymedium">'.$langs->trans("ThisScreenAllowsYouToSignDocFromContract", $creditor).'<br><br></td></tr>'."\n";
	} elseif ($source === 'fichinter' && $object instanceof Fichinter) {
		$text .= '<tr><td class="textpublicpayment"><br><strong>'.$langs->trans("WelcomeOnOnlineSignaturePageFichinter", $mysoc->name).'</strong></td></tr>'."\n";
		$text .= '<tr><td class="textpublicpayment small opacitymedium">'.$langs->trans("ThisScreenAllowsYouToSignDocFromFichinter", $creditor).'<br><br></td></tr>'."\n";
	} elseif ($source === 'expedition' && $object instanceof Expedition) {
		$text .= '<tr><td class="textpublicpayment"><br><strong>'.$langs->trans("WelcomeOnOnlineSignaturePageExpedition", $mysoc->name).'</strong></td></tr>'."\n";
		$text .= '<tr><td class="textpublicpayment small opacitymedium">'.$langs->trans("ThisScreenAllowsYouToSignDocFromExpedition", $creditor).'<br><br></td></tr>'."\n";
	} else {
		$text .= '<tr><td class="textpublicpayment"><br><strong>'.$langs->trans("WelcomeOnOnlineSignaturePage".dol_ucfirst($source), $mysoc->name).'</strong></td></tr>'."\n";
		$text .= '<tr><td class="textpublicpayment small opacitymedium">'.$langs->trans("ThisScreenAllowsYouToSignDocFrom".dol_ucfirst($source), $creditor).'<br><br></td></tr>'."\n";
	}
}
print $text;

// Output payment summary form
print '<tr><td align="center">';
print '<table with="100%" id="tablepublicpayment">';
if ($source === 'proposal' && $object instanceof Propal) {
	print '<tr><td colspan="2" class="left small opacitymedium">'.$langs->trans("ThisIsInformationOnDocumentToSignProposal").'<br><br></td></tr>'."\n";
} elseif ($source === 'contract' && $object instanceof Contrat) {
	print '<tr><td colspan="2" class="left small opacitymedium">'.$langs->trans("ThisIsInformationOnDocumentToSignContract").'<br><br></td></tr>'."\n";
} elseif ($source === 'fichinter' && $object instanceof Fichinter) {
	print '<tr><td colspan="2" class="left small opacitymedium">'.$langs->trans("ThisIsInformationOnDocumentToSignFichinter").'<br><br></td></tr>'."\n";
} elseif ($source === 'expedition' && $object instanceof Expedition) {
	print '<tr><td colspan="2" class="left small opacitymedium">'.$langs->trans("ThisIsInformationOnDocumentToSignExpedition").'<br><br></td></tr>'."\n";
} else {
	print '<tr><td colspan="2" class="left small opacitymedium">'.$langs->trans("ThisIsInformationOnDocumentToSign".dol_ucfirst($source)).'<br><br></td></tr>'."\n";
}
$found = false;
$error = 0;

// Signature on commercial proposal
if ($source === 'proposal' && $object instanceof Propal) {
	$found = true;
	$langs->load("proposal");

	$result = $object->fetch_thirdparty($object->socid);

	// Creditor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Creditor");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>'.$creditor.'</b>';
	print '<input type="hidden" name="creditor" value="'.$creditor.'">';
	print '</td></tr>'."\n";

	// Debitor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>'.$object->thirdparty->name.'</b>';
	print '</td></tr>'."\n";

	// Amount

	$amount = '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Amount");
	$amount .= '</td><td class="CTableRow2">';
	$amount .= '<b>'.price($object->total_ttc, 0, $langs, 1, -1, -1, getDolCurrency()).'</b>';
	if ($object->multicurrency_code != getDolCurrency()) {
		$amount .= ' ('.price($object->multicurrency_total_ttc, 0, $langs, 1, -1, -1, $object->multicurrency_code).')';
	}
	$amount .= '</td></tr>'."\n";

	// Call Hook amountPropalSign
	$parameters = array('source' => $source);
	$reshook = $hookmanager->executeHooks('amountPropalSign', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$amount .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$amount = $hookmanager->resPrint;
	}

	print $amount;

	// Object
	$text = '<b>'.$langs->trans("SignatureProposalRef", $object->ref).'</b>';
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Designation");
	print '</td><td class="CTableRow2">'.$text;

	$last_main_doc_file = $object->last_main_doc;

	if ($object->status == $object::STATUS_VALIDATED) {
		$object->last_main_doc = preg_replace('/_signed-(\d+)/', '', $object->last_main_doc);	// We want to be sure to not work on the signed version

		if (empty($last_main_doc_file) || !dol_is_file(DOL_DATA_ROOT.'/'.$object->last_main_doc)) {
			// It seems document has never been generated, or was generated and then deleted.
			// So we try to regenerate it with its default template.
			$defaulttemplate = '';		// We force the use an empty string instead of $object->model_pdf to be sure to use a "main" default template and not the last one used.
			$object->generateDocument($defaulttemplate, $langs);
		}

		$directdownloadlink = $object->getLastMainDocLink('proposal');
		if ($directdownloadlink) {
			print '<br><a href="'.$directdownloadlink.'">';
			print img_mime($object->last_main_doc, '');
			print $langs->trans("DownloadDocument").'</a>';
		}
	} else {
		if ($object->status == $object::STATUS_NOTSIGNED) {
			$directdownloadlink = $object->getLastMainDocLink('proposal');
			if ($directdownloadlink) {
				print '<br><a href="'.$directdownloadlink.'">';
				print img_mime($last_main_doc_file, '');
				print $langs->trans("DownloadDocument").'</a>';
			}
		} elseif ($object->status == $object::STATUS_SIGNED || $object->status == $object::STATUS_BILLED) {
			if (preg_match('/_signed-(\d+)/', $last_main_doc_file)) {	// If the last main doc has been signed
				$last_main_doc_file_not_signed = preg_replace('/_signed-(\d+)/', '', $last_main_doc_file);

				$datefilesigned = dol_filemtime($last_main_doc_file);
				$datefilenotsigned = dol_filemtime($last_main_doc_file_not_signed);

				if (empty($datefilenotsigned) || $datefilesigned > $datefilenotsigned) {	// If file signed is more recent
					$directdownloadlink = $object->getLastMainDocLink('proposal');
					if ($directdownloadlink) {
						print '<br><a href="'.$directdownloadlink.'">';
						print img_mime($object->last_main_doc, '');
						print $langs->trans("DownloadDocument").'</a>';
					}
				}
			}
		}
	}

	print '<input type="hidden" name="source" value="'.GETPOST("source", 'aZ09').'">';
	print '<input type="hidden" name="ref" value="'.$object->ref.'">';
	print '</td></tr>'."\n";
} elseif ($source === 'order' && $object instanceof Commande) {
	$found = true;
	$langs->load("orders");

	$result = $object->fetch_thirdparty($object->socid);

	// Creditor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Creditor");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>'.$creditor.'</b>';
	print '<input type="hidden" name="creditor" value="'.$creditor.'">';
	print '</td></tr>'."\n";

	// Debitor
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>'.$object->thirdparty->name.'</b>';
	print '</td></tr>'."\n";

	// Amount
	$amount = '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Amount");
	$amount .= '</td><td class="CTableRow2">';
	$amount .= '<b>'.price($object->total_ttc, 0, $langs, 1, -1, -1, getDolCurrency()).'</b>';
	if ($object->multicurrency_code != getDolCurrency()) {
		$amount .= ' ('.price($object->multicurrency_total_ttc, 0, $langs, 1, -1, -1, $object->multicurrency_code).')';
	}
	$amount .= '</td></tr>'."\n";

	print $amount;

	// Object
	$text = '<b>'.$langs->trans("SignatureOrderRef", $object->ref).'</b>';
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Designation");
	print '</td><td class="CTableRow2">'.$text;

	$last_main_doc_file = $object->last_main_doc;

	if ($object->status == $object::STATUS_VALIDATED) {
		$object->last_main_doc = preg_replace('/_signed-(\d+)/', '', $object->last_main_doc);	// We want to be sure to not work on the signed version

		if (empty($last_main_doc_file) || !dol_is_file(DOL_DATA_ROOT.'/'.$object->last_main_doc)) {
			// It seems document has never been generated, or was generated and then deleted.
			// So we try to regenerate it with its default template.
			$defaulttemplate = '';		// We force the use an empty string instead of $object->model_pdf to be sure to use a "main" default template and not the last one used.
			$object->generateDocument($defaulttemplate, $langs);
		}

		$directdownloadlink = $object->getLastMainDocLink('order');
		if ($directdownloadlink) {
			print '<br><a href="'.$directdownloadlink.'">';
			print img_mime($object->last_main_doc, '');
			print $langs->trans("DownloadDocument").'</a>';
		}
	} else {
		if ($object->status == $object::STATUS_DRAFT) {
			$directdownloadlink = $object->getLastMainDocLink('order');
			if ($directdownloadlink) {
				print '<br><a href="'.$directdownloadlink.'">';
				print img_mime($last_main_doc_file, '');
				print $langs->trans("DownloadDocument").'</a>';
			}
		} else {
			if (preg_match('/_signed-(\d+)/', $last_main_doc_file)) {	// If the last main doc has been signed
				$last_main_doc_file_not_signed = preg_replace('/_signed-(\d+)/', '', $last_main_doc_file);

				$datefilesigned = dol_filemtime($last_main_doc_file);
				$datefilenotsigned = dol_filemtime($last_main_doc_file_not_signed);

				if (empty($datefilenotsigned) || $datefilesigned > $datefilenotsigned) {	// If file signed is more recent
					$directdownloadlink = $object->getLastMainDocLink('order');
					if ($directdownloadlink) {
						print '<br><a href="'.$directdownloadlink.'">';
						print img_mime($object->last_main_doc, '');
						print $langs->trans("DownloadDocument").'</a>';
					}
				}
			}
		}
	}

	print '<input type="hidden" name="source" value="'.GETPOST("source", 'aZ09').'">';
	print '<input type="hidden" name="ref" value="'.$object->ref.'">';
	print '</td></tr>'."\n";
} elseif ($source === 'contract' && $object instanceof Contrat) { // Signature on contract
	$found = true;
	$langs->load("contract");

	$result = $object->fetch_thirdparty($object->socid);

	// Proposer
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Proposer");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>'.$creditor.'</b>';
	print '<input type="hidden" name="creditor" value="'.$creditor.'">';
	print '</td></tr>'."\n";

	// Target
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>'.$object->thirdparty->name.'</b>';
	print '</td></tr>'."\n";

	// Object
	$text = '<b>'.$langs->trans("SignatureContractRef", $object->ref).'</b>';
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Designation");
	print '</td><td class="CTableRow2">'.$text;

	$last_main_doc_file = $object->last_main_doc;

	if (empty($last_main_doc_file) || !dol_is_file(DOL_DATA_ROOT.'/'.$object->last_main_doc)) {
		// It seems document has never been generated, or was generated and then deleted.
		// So we try to regenerate it with its default template.
		$defaulttemplate = '';		// We force the use an empty string instead of $object->model_pdf to be sure to use a "main" default template and not the last one used.
		$object->generateDocument($defaulttemplate, $langs);
	}

	$directdownloadlink = $object->getLastMainDocLink('contract');
	if ($directdownloadlink) {
		print '<br><a href="'.$directdownloadlink.'">';
		print img_mime($object->last_main_doc, '');
		if ($message == "signed") {
			print $langs->trans("DownloadSignedDocument").'</a>';
		} else {
			print $langs->trans("DownloadDocument").'</a>';
		}
	}


	print '<input type="hidden" name="source" value="'.GETPOST("source", 'aZ09').'">';
	print '<input type="hidden" name="ref" value="'.$object->ref.'">';
	print '</td></tr>'."\n";
} elseif ($source === 'fichinter' && $object instanceof Fichinter) {
	// Signature on fichinter
	$found = true;
	$langs->load("interventions");

	$result = $object->fetch_thirdparty($object->socid);

	// Proposer
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Proposer");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>'.$creditor.'</b>';
	print '<input type="hidden" name="creditor" value="'.$creditor.'">';
	print '</td></tr>'."\n";

	// Target
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>'.$object->thirdparty->name.'</b>';
	print '</td></tr>'."\n";

	// Object
	$text = '<b>'.$langs->trans("SignatureFichinterRef", $object->ref).'</b>';
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Designation");
	print '</td><td class="CTableRow2">'.$text;

	$last_main_doc_file = $object->last_main_doc;

	if (empty($last_main_doc_file) || !dol_is_file(DOL_DATA_ROOT.'/'.$object->last_main_doc)) {
		// It seems document has never been generated, or was generated and then deleted.
		// So we try to regenerate it with its default template.
		$defaulttemplate = '';		// We force the use an empty string instead of $object->model_pdf to be sure to use a "main" default template and not the last one used.
		$object->generateDocument($defaulttemplate, $langs);
	}

	$directdownloadlink = $object->getLastMainDocLink('fichinter');
	if ($directdownloadlink) {
		print '<br><a href="'.$directdownloadlink.'">';
		print img_mime($object->last_main_doc, '');
		if ($message == "signed") {
			print $langs->trans("DownloadSignedDocument").'</a>';
		} else {
			print $langs->trans("DownloadDocument").'</a>';
		}
	}
	print '<input type="hidden" name="source" value="'.GETPOST("source", 'aZ09').'">';
	print '<input type="hidden" name="ref" value="'.$object->ref.'">';
	print '</td></tr>'."\n";
} elseif ($source === 'societe_rib' && $object instanceof CompanyBankAccount) {
	$found = true;
	$langs->loadLangs(array("companies", "commercial", "withdrawals"));

	$result = $object->fetch_thirdparty();

	// Proposer
	print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("CreditorName");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>' . $creditor . '</b>';
	print '<input type="hidden" name="creditor" value="' . $creditor . '">';
	print '</td></tr>' . "\n";

	// Target
	print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("ThirdParty");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>' . $object->thirdparty->name . '</b>';
	print '</td></tr>' . "\n";

	// Object
	$text = '<b>' . $langs->trans("Signature" . dol_ucfirst($source) . "Ref", $object->ref) . '</b>';
	print '<tr class="CTableRow2"><td class="CTableRow2">' . $langs->trans("Designation");
	print '</td><td class="CTableRow2">' . $text;

	$last_main_doc_file = $object->last_main_doc;
	$diroutput = $conf->societe->multidir_output[$object->thirdparty->entity].'/'
			.dol_sanitizeFileName((string) $object->thirdparty->id).'/';
	if ((empty($last_main_doc_file) ||
		!dol_is_file($diroutput
			.$langs->transnoentitiesnoconv("SepaMandateShort").' '.$object->id."-".dol_sanitizeFileName($object->rum).".pdf"))
		&& 	$message != "signed") {
		// It seems document has never been generated, or was generated and then deleted.
		// So we try to regenerate it with its default template.
		//$defaulttemplate = 'sepamandate';
		$defaulttemplate = getDolGlobalString("BANKADDON_PDF");

		$object->setDocModel($user, $defaulttemplate);
		$moreparams = array(
			'use_companybankid' => $object->id,
			'force_dir_output' => $diroutput
		);
		$result = $object->thirdparty->generateDocument($defaulttemplate, $langs, 0, 0, 0, $moreparams);
		$object->last_main_doc = $object->thirdparty->last_main_doc;
	}
	$directdownloadlink = $object->getLastMainDocLink('company');
	if ($directdownloadlink) {
		print '<br><a href="'.$directdownloadlink.'">';
		print img_mime($object->last_main_doc, '');
		if ($message == "signed") {
			print $langs->trans("DownloadSignedDocument").'</a>';
		} else {
			print $langs->trans("DownloadDocument").'</a>';
		}
	}
} elseif ($source === 'expedition' && $object instanceof Expedition) {
	// Signature on expedition
	$found = true;
	$langs->load("interventions");

	$result = $object->fetch_thirdparty($object->socid);

	// Proposer
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Proposer");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>'.$creditor.'</b>';
	print '<input type="hidden" name="creditor" value="'.$creditor.'">';
	print '</td></tr>'."\n";

	// Target
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("ThirdParty");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>'.$object->thirdparty->name.'</b>';
	print '</td></tr>'."\n";

	// Object
	$text = '<b>'.$langs->trans("SignatureFichinterRef", $object->ref).'</b>';
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Designation");
	print '</td><td class="CTableRow2">'.$text;

	$last_main_doc_file = $object->last_main_doc;
	if (empty($last_main_doc_file) || !dol_is_file(DOL_DATA_ROOT.'/'.$object->last_main_doc)) {
		// It seems document has never been generated, or was generated and then deleted.
		// So we try to regenerate it with its default template.
		$defaulttemplate = '';		// We force the use an empty string instead of $object->model_pdf to be sure to use a "main" default template and not the last one used.
		$object->generateDocument($defaulttemplate, $langs);
	}
	$directdownloadlink = $object->getLastMainDocLink('', 0, 0);
	if ($directdownloadlink) {
		print '<br><a href="'.$directdownloadlink.'">';
		print img_mime($object->last_main_doc, '');
		if ($message == "signed") {
			print $langs->trans("DownloadSignedDocument").'</a>';
		} else {
			print $langs->trans("DownloadDocument").'</a>';
		}
	}
	print '<input type="hidden" name="source" value="'.GETPOST("source", 'aZ09').'">';
	print '<input type="hidden" name="ref" value="'.$object->ref.'">';
	print '</td></tr>'."\n";
} else {
	$found = true;
	$langs->load('companies');

	if (method_exists($object, 'fetch_thirdparty') && (!empty($object->socid) || !empty($object->fk_soc))) {
		$result = $object->fetch_thirdparty();
	}

	// Proposer
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Proposer");
	print '</td><td class="CTableRow2">';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print '<b>'.$creditor.'</b>';
	print '<input type="hidden" name="creditor" value="'.$creditor.'">';
	print '</td></tr>'."\n";

	if (!empty($object->thirdparty) && is_object($object->thirdparty) && !empty($object->thirdparty->name)) {
		// Target
		print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("ThirdParty");
		print '</td><td class="CTableRow2">';
		print img_picto('', 'company', 'class="pictofixedwidth"');
		print '<b>'.$object->thirdparty->name.'</b>';
		print '</td></tr>'."\n";
	}

	// Object
	$objectref = empty($object->ref) ? $ref : $object->ref;
	$text = '<b>'.$langs->trans("Signature".dol_ucfirst($source)."Ref", $objectref).'</b>';
	print '<tr class="CTableRow2"><td class="CTableRow2">'.$langs->trans("Designation");
	print '</td><td class="CTableRow2">'.$text;

	$last_main_doc_file = empty($object->last_main_doc) ? '' : $object->last_main_doc;

	if (method_exists($object, 'generateDocument') && (empty($last_main_doc_file) || !dol_is_file(DOL_DATA_ROOT.'/'.$last_main_doc_file))) {
		// It seems document has never been generated, or was generated and then deleted.
		// So we try to regenerate it with its default template.
		$defaulttemplate = '';		// We force the use an empty string instead of $object->model_pdf to be sure to use a "main" default template and not the last one used.
		$object->generateDocument($defaulttemplate, $langs);
		$last_main_doc_file = empty($object->last_main_doc) ? '' : $object->last_main_doc;
	}

	$documentmodulepart = empty($sourceDefinition['document_modulepart']) ? (empty($sourceDefinition['modulepart']) ? $source : (string) $sourceDefinition['modulepart']) : (string) $sourceDefinition['document_modulepart'];
	$directdownloadlink = method_exists($object, 'getLastMainDocLink') ? $object->getLastMainDocLink($documentmodulepart) : '';
	if ($directdownloadlink) {
		print '<br><a href="'.$directdownloadlink.'">';
		print img_mime($last_main_doc_file, '');
		if ($message == "signed") {
			print $langs->trans("DownloadSignedDocument").'</a>';
		} else {
			print $langs->trans("DownloadDocument").'</a>';
		}
	}
}

// Call Hook addFormSign
$parameters = array('source' => $source);
$reshook = $hookmanager->executeHooks('addFormSign', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

if (!$found) {
	print '<tr><td class="center" colspan="2"><br><div class="warning">'.dol_escape_htmltag($langs->transnoentitiesnoconv("ErrorBadParameters")).'</div></td></tr>'."\n";
}

print '</table>'."\n";
print "\n";

if ($action != 'dosign') {
	if ($found && !$error) {
		// We are in a management option and no error
	} else {
		dol_print_error_email('ERRORNEWONLINESIGN');
	}
} else {
	// Print
}

print '</td></tr>'."\n";
print '<tr><td class="center">';


if ($action == "dosign" && empty($cancel)) {
	// Show the field to sign
	print '<div class="tablepublicpayment">';
	print '<input type="text" class="paddingleftonly marginleftonly paddingright marginrightonly marginbottomonly borderbottom" id="name"  placeholder="'.$langs->trans("Lastname").'" spellcheck="false" autofocus>';
	print '<div id="signature" style="border:solid;"></div>';
	print '</div>';
	print '<input type="button" class="small noborderall cursorpointer buttonreset" id="clearsignature" value="'.$langs->trans("ClearSignature").'">';

	// Do not use class="reposition" here: It breaks the submit and there is a message on top to say it's ok, so going back top is better.
	print '<div>';
	print '<input type="button" class="button butActionSign marginleftonly marginrightonly" id="signbutton" value="'.$langs->trans("Sign").'">';
	print '<input type="submit" class="button butActionDelete marginleftonly marginrightonly" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	// Define $urlwithroot
	$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
	//$urlwithroot = DOL_MAIN_URL_ROOT; // This is to use same domain name than current. For Paypal payment, we can use internal URL like localhost.
	// TODO Replace DOL_URL_ROOT with $urlwithroot ?

	// Add js code managed into the div #signature
	$urltogo = $_SERVER["PHP_SELF"].'?ref='.urlencode($ref).'&source='.urlencode($source).'&message=signed&securekey='.urlencode($SECUREKEY).(isModEnabled('multicompany') ? '&entity='.(int) $entity : '');
	print '<script language="JavaScript" type="text/javascript" src="'.DOL_URL_ROOT.'/public/includes/jquery/plugins/jSignature/jSignature.js"></script>
	<script type="text/javascript">
	$(document).ready(function() {
	  $("#signature").jSignature({ color:"#000", lineWidth:0, '.(empty($conf->dol_optimize_smallscreen) ? '' : 'width: 280, ').'height: 180});

	  $("#signature").on("change",function(){
		$("#clearsignature").css("display","");
		$("#signbutton").attr("disabled",false);
		if(!$._data($("#signbutton")[0], "events")){
			$("#signbutton").on("click",function(){
				console.log("We click on button sign");
				document.body.style.cursor = \'wait\';
				var signature = $("#signature").jSignature("getData", "image");
				var name = document.getElementById("name").value;
				$.ajax({
					type: "POST",
					url: \''.DOL_URL_ROOT.'/core/ajax/onlineSign.php\',
					dataType: "text",
					data: {
						"action" : \'importSignature\',
						"token" : \''.newToken().'\',
						"signaturebase64" : signature,
						"onlinesignname" : name,
						"ref" : \''.dol_escape_js($REF).'\',
						"securekey" : \''.dol_escape_js($SECUREKEY).'\',
						"mode" : \''.dol_escape_js($source).'\',
						"entity" : \''.dol_escape_js((string) $entity).'\',
					},
					success: function(response) {
						if (response.trim() === "success") {
							console.log("Success on saving signature");
							window.location.replace(\''.dol_escape_js($urltogo).'\');
						} else {
							document.body.style.cursor = \'auto\';
							console.error(response);
							alert("Error on calling the core/ajax/onlineSign.php. See console log.");
						}
					},
					error: function(response) {
						document.body.style.cursor = \'auto\';
						console.error(response);
						alert("Error on calling the core/ajax/onlineSign.php. "+response.responseText);
					}
				});
			});
		}
	  });

	  $("#clearsignature").on("click",function(){
		$("#signature").jSignature("clear");
		$("#signbutton").attr("disabled",true);
		// document.getElementById("onlinesignname").value = "";
	  });

	  $("#signbutton").attr("disabled",true);
	});
	</script>';
} else {
	if ($source === 'proposal' && $object instanceof Propal) {
		if ($object->status == $object::STATUS_SIGNED) {
			print '<br>';
			if ($message == 'signed') {
				print img_picto('', 'check', '', 0, 0, 0, '', 'size2x').'<br>';
				print '<span class="ok">'.$langs->trans("PropalSigned").'</span>';
			} else {
				print img_picto('', 'check', '', 0, 0, 0, '', 'size2x').'<br>';
				print '<span class="ok">'.$langs->trans("PropalAlreadySigned").'</span>';
			}
		} elseif ($object->status == $object::STATUS_NOTSIGNED) {
			print '<br>';
			if ($message == 'refused') {
				print img_picto('', 'cross', '', 0, 0, 0, '', 'size2x').'<br>';
				print '<span class="ok">'.$langs->trans("PropalRefused").'</span>';
			} else {
				print img_picto('', 'cross', '', 0, 0, 0, '', 'size2x').'<br>';
				print '<span class="warning">'.$langs->trans("PropalAlreadyRefused").'</span>';
			}
		} else {
			print '<input type="submit" class="butAction butActionSign small wraponsmartphone marginbottomonly marginleftonly marginrightonly reposition" value="'.$langs->trans("SignPropal").'">';
			print '<input name="refusepropal" type="submit" class="butActionDelete small wraponsmartphone marginbottomonly marginleftonly marginrightonly reposition" value="'.$langs->trans("RefusePropal").'">';
		}
	} elseif ($source === 'contract' && $object instanceof Contrat) {
		if ($message == 'signed') {
			print '<span class="ok">'.$langs->trans("ContractSigned").'</span>';
		} else {
			print '<input type="submit" class="butAction butActionSign small wraponsmartphone marginbottomonly marginleftonly marginrightonly reposition" value="'.$langs->trans("SignContract").'">';
		}
	} elseif ($source === 'fichinter' && $object instanceof Fichinter) {
		if ($message == 'signed') {
			print '<span class="ok">'.$langs->trans("FichinterSigned").'</span>';
		} else {
			print '<input type="submit" class="butAction butActionSign small wraponsmartphone marginbottomonly marginleftonly marginrightonly reposition" value="'.$langs->trans("SignFichinter").'">';
		}
	} elseif ($source === 'expedition' && $object instanceof Expedition) {
		if ($message == 'signed' || $object->signed_status == Expedition::$SIGNED_STATUSES['STATUS_SIGNED_SENDER']) {
			print '<span class="ok">'.$langs->trans("ExpeditionSigned").'</span>';
		} else {
			print '<input type="submit" class="butAction butActionSign small wraponsmartphone marginbottomonly marginleftonly marginrightonly reposition" value="'.$langs->trans("SignExpedition").'">';
		}
	} else {
		if ($message == 'signed') {
			print '<span class="ok">'.$langs->trans(dol_ucfirst($source)."Signed").'</span>';
		} else {
			print '<input type="submit" class="butAction butActionSign small wraponsmartphone marginbottomonly marginleftonly marginrightonly reposition" value="'.$langs->trans("Sign".dol_ucfirst($source)).'">';
		}
	}
}
print '</td></tr>'."\n";
print '</table>'."\n";

print '</form>'."\n";
print '</div>'."\n";
print '<br>';


htmlPrintOnlineFooter($mysoc, $langs);

llxFooter('', 'public');

$db->close();
