<?php
/**
 * Copyright (C) 2013	    Marcos García	        <marcosgdf@gmail.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2020       Abbes Bahfir            <bafbes@gmail.com>
 * Copyright (C) 2021       Waël Almoman            <info@almoman.com>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 * or see https://www.gnu.org/
 */

/**
 * Returns an array with the tabs for the "Payment" section
 * It loads tabs from modules looking for the entity payment
 *
 * @param Paiement $object Current payment object
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs for the payment section
 */
function payment_prepare_head(Paiement $object)
{
	global $langs, $conf, $db;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Payment");
	$head[$h][2] = 'payment';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'payment');

	$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->compta->payment->dir_output.'/'.$object->ref;
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'payment', 'remove');

	return $head;
}

/**
 * Returns an array with the tabs for the "Bannkline" section
 * It loads tabs from modules looking for the entity payment
 *
 * @param 	int		$id		ID of bank line
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs for the Banline section
 */
function bankline_prepare_head($id)
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/compta/bank/line.php?rowid='.$id;
	$head[$h][1] = $langs->trans('BankTransaction');
	$head[$h][2] = 'bankline';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'bankline');

	$head[$h][0] = DOL_URL_ROOT.'/compta/bank/info.php?rowid='.$id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'bankline', 'remove');

	return $head;
}

/**
 * Returns an array with the tabs for the "Supplier payment" section
 * It loads tabs from modules looking for the entity payment_supplier
 *
 * @param Paiement $object Current payment object
 * @return	array<array{0:string,1:string,2:string}>	Tabs for the payment section
 */
function payment_supplier_prepare_head(Paiement $object)
{
	global $db, $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/fourn/paiement/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Payment");
	$head[$h][2] = 'payment';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'payment_supplier');

	$head[$h][0] = DOL_URL_ROOT.'/fourn/paiement/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Info');
	$head[$h][2] = 'info';
	$h++;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->fournisseur->payment->dir_output.'/'.$object->ref;
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/fourn/paiement/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'payment_supplier', 'remove');

	return $head;
}

/**
 * Return array of valid payment mode
 *
 * @param	string	$paymentmethod		Filter on this payment method (''=none, 'paypal', 'stripe', ...)
 * @param	int		$mode				0=Return array with key, 1=Return array with more information like label
 * @return	array<string,string>		Array of valid payment method
 */
function getValidOnlinePaymentMethods($paymentmethod = '', $mode = 0)
{
	global $langs, $hookmanager, $action;

	$validpaymentmethod = array();

	if ((empty($paymentmethod) || $paymentmethod == 'paypal') && isModEnabled('paypal')) {
		$langs->load("paypal");
		if ($mode) {
			$validpaymentmethod['paypal'] = array('label' => 'PayPal', 'status' => 'valid');
		} else {
			$validpaymentmethod['paypal'] = 'valid';
		}
	}
	if ((empty($paymentmethod) || $paymentmethod == 'stripe') && isModEnabled('stripe')) {
		$langs->load("stripe");
		if ($mode) {
			$validpaymentmethod['stripe'] = array('label' => 'Stripe', 'status' => 'valid');
		} else {
			$validpaymentmethod['stripe'] = 'valid';
		}
	}

	// This hook is used to complete the $validpaymentmethod array so an external payment modules
	// can add its own key (ie 'payzen' for Payzen, 'helloasso' for HelloAsso...)
	$parameters = [
		'paymentmethod' => $paymentmethod,
		'mode' => $mode,
		'validpaymentmethod' => &$validpaymentmethod
	];
	$tmpobject = new stdClass();
	$reshook = $hookmanager->executeHooks('getValidPayment', $parameters, $tmpobject, $action);
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	} elseif (!empty($hookmanager->resArray['validpaymentmethod'])) {
		if ($reshook == 0) {
			$validpaymentmethod = array_merge($validpaymentmethod, $hookmanager->resArray['validpaymentmethod']);
		} else {
			$validpaymentmethod = $hookmanager->resArray['validpaymentmethod'];
		}
	}

	return $validpaymentmethod;
}

/**
 * Return string with full online payment Url
 *
 * @param   string		$type		Type of URL ('free', 'order', 'invoice', 'contractline', 'member' ...)
 * @param	string		$ref		Ref of object
 * @param	int|float	$amount		Amount of money to request for
 * @return	string					Url string
 */
function showOnlinePaymentUrl($type, $ref, $amount = 0)
{
	global $langs;

	// Load translation files required by the page
	$langs->loadLangs(array('payment', 'stripe'));

	$servicename = '';	// Link is a generic link for all payments services (paypal, stripe, ...)

	$out = img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans($type == 'thirdparty' ? "ToOfferALinkForOnlinePaymentOfUnpaidInvoices" : "ToOfferALinkForOnlinePayment", $servicename).'</span><br>';
	$url = getOnlinePaymentUrl(0, $type, $ref, $amount);
	$out .= '<div class="urllink"><input type="text" id="onlinepaymenturl" spellcheck="false" class="quatrevingtpercentminusx" value="'.$url.'">';
	$out .= '<a class="" href="'.$url.'" target="_blank" rel="noopener noreferrer">'.img_picto('', 'globe', 'class="paddingleft"').'</a>';
	$out .= '</div>';
	$out .= ajax_autoselect("onlinepaymenturl", '');
	return $out;
}

/**
 * Return string with HTML link for online payment
 *
 * @param	string		$type		Type of URL ('free', 'order', 'invoice', 'contractline', 'member' ...)
 * @param	string		$ref		Ref of object
 * @param	string		$label		Text or HTML tag to display, if empty it display the URL
 * @param	int|float	$amount		Amount of money to request for
 * @return	string					Url string
 */
function getHtmlOnlinePaymentLink($type, $ref, $label = '', $amount = 0)
{
	$url = getOnlinePaymentUrl(0, $type, $ref, $amount);
	$label = $label ? $label : $url;
	return '<a href="'.$url.'" target="_blank" rel="noopener noreferrer">'.$label.'</a>';
}


/**
 * Return string with full Url
 *
 * @param   int			$mode		      0=True url, 1=Url formatted with colors
 * @param   string		$type		      Type of URL ('free', 'order', 'invoice', 'contractline', 'member', 'boothlocation', 'thirdparty', ...)
 * @param	string		$ref		      Ref of object
 * @param	int|float	$amount		      Amount of money to request for
 * @param	string		$freetag	      Free tag (required and used for $type='free' only)
 * @param   int|string 	$localorexternal  0=Url of current browsing, 1=Url for external access, or string with virtual host url
 * @return	string					      Url string
 */
function getOnlinePaymentUrl($mode, $type, $ref = '', $amount = 0, $freetag = 'your_tag', $localorexternal = 1)
{
	global $conf, $dolibarr_main_url_root;

	$out = '';

	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	$urltouse = DOL_MAIN_URL_ROOT;						// Should be "https://www.mydomain.com/mydolibarr" for example
	//dol_syslog("getOnlinePaymentUrl DOL_MAIN_URL_ROOT=".DOL_MAIN_URL_ROOT);

	if ((string) $localorexternal == '1') {
		$urltouse = $urlwithroot;
	} elseif ((string) $localorexternal != '0') {
		$urltouse = $localorexternal;
	}

	if ($type == 'free') {
		$out = $urltouse.'/public/payment/newpayment.php?amount='.($mode ? '<span style="color: #666666">' : '').price2num($amount, 'MT').($mode ? '</span>' : '').'&tag='.($mode ? '<span style="color: #666666">' : '').urlencode($freetag).($mode ? '</span>' : '');
		if (getDolGlobalString('PAYMENT_SECURITY_TOKEN')) {
			$out .= '&securekey='.urlencode(dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN'), 'sha1md5'));
		}
		//if ($mode) $out.='&noidempotency=1';
	} elseif ($type == 'order') {
		$out = $urltouse.'/public/payment/newpayment.php?source='.$type.'&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= 'order_ref';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if (getDolGlobalString('PAYMENT_SECURITY_TOKEN')) {
			$out .= '&securekey='.($mode ? '<span style="color: #666666">' : '');
			if ($mode == 1) {
				$out .= "hash('" . getDolGlobalString('PAYMENT_SECURITY_TOKEN')."' + '".$type."' + order_ref)";
			}
			if ($mode == 0) {
				$out .= dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN').$type.$ref, 'sha1md5');
			}
			$out .= ($mode ? '</span>' : '');
		}
	} elseif ($type == 'invoice') {
		$out = $urltouse.'/public/payment/newpayment.php?source='.$type.'&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= 'invoice_ref';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if (getDolGlobalString('PAYMENT_SECURITY_TOKEN')) {
			$out .= '&securekey='.($mode ? '<span style="color: #666666">' : '');
			if ($mode == 1) {
				$out .= "hash('" . getDolGlobalString('PAYMENT_SECURITY_TOKEN')."' + '".$type."' + invoice_ref)";
			}
			if ($mode == 0) {
				$out .= dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN').$type.$ref, 'sha1md5');
			}
			$out .= ($mode ? '</span>' : '');
		}
	} elseif ($type == 'contractline') {
		$out = $urltouse.'/public/payment/newpayment.php?source='.$type.'&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= 'contractline_ref';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if (getDolGlobalString('PAYMENT_SECURITY_TOKEN')) {
			$out .= '&securekey='.($mode ? '<span style="color: #666666">' : '');
			if ($mode == 1) {
				$out .= "hash('" . getDolGlobalString('PAYMENT_SECURITY_TOKEN')."' + '".$type."' + contractline_ref)";
			}
			if ($mode == 0) {
				$out .= dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN').$type.$ref, 'sha1md5');
			}
			$out .= ($mode ? '</span>' : '');
		}
	} elseif ($type == 'member' || $type == 'membersubscription') {
		$newtype = 'member';
		$out = $urltouse.'/public/payment/newpayment.php?source=member';
		$out .= '&amount='.price2num($amount, 'MT');
		$out .= '&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= 'member_ref';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if (getDolGlobalString('PAYMENT_SECURITY_TOKEN')) {
			$out .= '&securekey='.($mode ? '<span style="color: #666666">' : '');
			if ($mode == 1) {	// mode tuto
				$out .= "hash('" . getDolGlobalString('PAYMENT_SECURITY_TOKEN')."' + '".$newtype."' + member_ref)";
			}
			if ($mode == 0) {	// mode real
				$out .= dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN').$newtype.$ref, 'sha1md5');
			}
			$out .= ($mode ? '</span>' : '');
		}
	} elseif ($type == 'donation') {
		$out = $urltouse.'/public/payment/newpayment.php?source='.$type.'&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= 'donation_ref';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if (getDolGlobalString('PAYMENT_SECURITY_TOKEN')) {
			$out .= '&securekey='.($mode ? '<span style="color: #666666">' : '');
			if ($mode == 1) {
				$out .= "hash('" . getDolGlobalString('PAYMENT_SECURITY_TOKEN')."' + '".$type."' + donation_ref)";
			}
			if ($mode == 0) {
				$out .= dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN').$type.$ref, 'sha1md5');
			}
			$out .= ($mode ? '</span>' : '');
		}
	} elseif ($type == 'thirdparty') {
		// Payment of all the unpaid invoices of a customer. $ref is the id of the third party.
		$out = $urltouse.'/public/payment/newpayment.php?source='.$type.'&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= 'thirdparty_id';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if (getDolGlobalString('PAYMENT_SECURITY_TOKEN')) {
			$out .= '&securekey='.($mode ? '<span style="color: #666666">' : '');
			if ($mode == 1) {
				$out .= "hash('" . getDolGlobalString('PAYMENT_SECURITY_TOKEN')."' + '".$type."' + thirdparty_id)";
			}
			if ($mode == 0) {
				$out .= dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN').$type.$ref, 'sha1md5');
			}
			$out .= ($mode ? '</span>' : '');
		}
	} elseif ($type == 'boothlocation') {
		$out = $urltouse.'/public/payment/newpayment.php?source='.$type.'&ref='.($mode ? '<span style="color: #666666">' : '');
		if ($mode == 1) {
			$out .= 'invoice_ref';
		}
		if ($mode == 0) {
			$out .= urlencode($ref);
		}
		$out .= ($mode ? '</span>' : '');
		if (getDolGlobalString('PAYMENT_SECURITY_TOKEN')) {
			$out .= '&securekey='.($mode ? '<span style="color: #666666">' : '');
			if ($mode == 1) {
				$out .= "hash('" . getDolGlobalString('PAYMENT_SECURITY_TOKEN')."' + '".$type."' + invoice_ref)";
			}
			if ($mode == 0) {
				$out .= dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN').$type.$ref, 'sha1md5');
			}
			$out .= ($mode ? '</span>' : '');
		}
	}

	// For multicompany
	if (!empty($out) && isModEnabled('multicompany')) {
		$out .= "&entity=".$conf->entity; // Check the entity because we may have the same reference in several entities
	}

	return $out;
}

/**
 * Return the customer invoices of a third party that can be paid with the online payment page of source 'thirdparty'.
 * They are the validated and unpaid invoices that are not credit notes, of the current entity, in the main currency of the
 * company and with a remainder to pay greater than zero. The oldest invoice comes first.
 *
 * @param	DoliDB		$db					Database handler
 * @param	int			$socid				Id of the third party
 * @param	?int[]		$filterinvoiceids	If an array is provided, keep only the invoices with these ids (an empty array returns no invoice)
 * @return	array<int,array{id:int,ref:string,date:int|'',total_ttc:float,remaintopay:float}>|int	Payable invoices with key = invoice id, -1 if error
 */
function getOnlinePaymentInvoicesOfThirdparty($db, $socid, $filterinvoiceids = null)
{
	global $conf;

	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

	$invoices = [];

	if ((int) $socid <= 0) {
		return $invoices;
	}
	if (is_array($filterinvoiceids)) {
		$tmpids = [];
		foreach ($filterinvoiceids as $tmpid) {
			if ((int) $tmpid > 0) {
				$tmpids[] = (int) $tmpid;
			}
		}
		if (empty($tmpids)) {
			return $invoices;
		}
		$filterinvoiceids = $tmpids;
	}

	$sql = "SELECT f.rowid, f.ref, f.datef, f.total_ttc, f.fk_statut as status, f.close_code, f.type";
	$sql .= " FROM ".$db->prefix()."facture as f";
	$sql .= " WHERE f.fk_soc = ".((int) $socid);
	$sql .= " AND f.entity = ".((int) $conf->entity);
	$sql .= " AND f.fk_statut = ".((int) Facture::STATUS_VALIDATED);
	$sql .= " AND f.paye = 0";
	$sql .= " AND f.type IN (".((int) Facture::TYPE_STANDARD).", ".((int) Facture::TYPE_REPLACEMENT).", ".((int) Facture::TYPE_DEPOSIT).", ".((int) Facture::TYPE_SITUATION).")";
	$sql .= " AND (f.multicurrency_code IS NULL OR f.multicurrency_code = '' OR f.multicurrency_code = '".$db->escape($conf->currency)."')";
	if (is_array($filterinvoiceids)) {
		$sql .= " AND f.rowid IN (".$db->sanitize(implode(',', $filterinvoiceids)).")";
	}
	$sql .= " ORDER BY f.datef ASC, f.ref ASC";

	dol_syslog("getOnlinePaymentInvoicesOfThirdparty socid=".((int) $socid), LOG_DEBUG);

	$resql = $db->query($sql);
	if (!$resql) {
		dol_syslog("getOnlinePaymentInvoicesOfThirdparty ".$db->lasterror(), LOG_ERR);
		return -1;
	}
	$rows = [];
	while ($obj = $db->fetch_object($resql)) {
		$rows[] = $obj;
	}
	$db->free($resql);

	foreach ($rows as $obj) {
		$invoice = new Facture($db);
		$invoice->id = (int) $obj->rowid;
		$invoice->ref = $obj->ref;
		$invoice->type = (int) $obj->type;
		$invoice->status = (int) $obj->status;
		$invoice->close_code = $obj->close_code;
		$invoice->total_ttc = (float) $obj->total_ttc;

		// Same calculation of the remainder to pay as the online payment page of a single invoice
		$alreadypaid = $invoice->getSommePaiement();
		$creditnotesused = $invoice->getSumCreditNotesUsed();
		$depositsused = $invoice->getSumDepositsUsed();
		if (!is_numeric($alreadypaid) || $alreadypaid < 0 || (is_string($creditnotesused) && !is_numeric($creditnotesused)) || $depositsused < 0) {
			dol_syslog("getOnlinePaymentInvoicesOfThirdparty failed to get the remainder to pay of invoice id=".$invoice->id." ".$invoice->error, LOG_ERR);
			return -1;
		}
		$remaintopay = (float) price2num($invoice->total_ttc - ((float) $alreadypaid + (float) $creditnotesused + (float) $depositsused), 'MT');

		if ($remaintopay > 0) {
			$invoices[$invoice->id] = [
				'id' => $invoice->id,
				'ref' => (string) $invoice->ref,
				'date' => $db->jdate($obj->datef),
				'total_ttc' => $invoice->total_ttc,
				'remaintopay' => $remaintopay,
			];
		}
	}

	return $invoices;
}

/**
 * Spread an amount on a list of invoices, oldest first, each invoice receiving at most its remainder to pay.
 *
 * @param	array<int,array{remaintopay:float|int|string}>	$invoices	Invoices ordered oldest first, with key = invoice id (see getOnlinePaymentInvoicesOfThirdparty())
 * @param	float|int|string								$amount		Amount to spread
 * @return	array{amounts:array<int,float>,excess:float}				'amounts' = array(invoice id => amount), 'excess' = part of the amount that was not allocated
 */
function allocateOnlinePaymentToInvoices($invoices, $amount)
{
	$amounts = [];
	$remain = (float) price2num($amount, 'MT');

	foreach ($invoices as $invoiceid => $invoice) {
		if ($remain <= 0) {
			break;
		}
		$part = min($remain, (float) price2num($invoice['remaintopay'], 'MT'));
		if ($part <= 0) {
			continue;
		}
		$amounts[(int) $invoiceid] = $part;
		$remain = (float) price2num($remain - $part, 'MT');
	}

	return ['amounts' => $amounts, 'excess' => max(0.0, $remain)];
}

/**
 * Record one payment received online for several invoices of a customer (online payment page of source 'thirdparty').
 * The invoices are loaded again from the database and the amount paid is spread again on their current remainder to pay,
 * oldest first. If some money remains, because an invoice was paid in the meantime, it is added to the last invoice: the
 * money was already captured by the payment service so the payment must be recorded.
 * The payment and its bank line are recorded in one transaction.
 *
 * @param	DoliDB			$db					Database handler
 * @param	User			$user				User recording the payment
 * @param	int				$socid				Id of the third party
 * @param	int[]			$invoiceids			Ids of the invoices that were shown on the payment page. Empty array = all the payable invoices of the third party.
 * @param	float|string	$amount				Amount really paid, in the main currency
 * @param	int				$paymenttypeid		Id of the payment mode (llx_c_paiement)
 * @param	string			$ext_payment_id		Id of the payment in the external payment service
 * @param	string			$ext_payment_site	Name of the external payment service
 * @param	int				$bankaccountid		Id of the bank account on which to record the payment (used only if module bank is enabled)
 * @param	string			$note_public		Public note of the payment
 * @return	array{result:int,messages:string[],paidinvoices:array<int,string>,excess:float}	'result' = id of the payment if OK, <0 if KO (nothing recorded)
 */
function recordOnlinePaymentOfThirdpartyInvoices($db, $user, $socid, $invoiceids, $amount, $paymenttypeid, $ext_payment_id, $ext_payment_site, $bankaccountid, $note_public)
{
	require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

	$messages = [];
	$paidinvoices = [];
	$excess = 0.0;

	$invoices = getOnlinePaymentInvoicesOfThirdparty($db, $socid, empty($invoiceids) ? null : $invoiceids);
	if (!is_array($invoices)) {
		$messages[] = 'Failed to load the invoices of third party '.((int) $socid).' to record the payment';
		return ['result' => -1, 'messages' => $messages, 'paidinvoices' => $paidinvoices, 'excess' => $excess];
	}
	if (empty($invoices) || (float) price2num($amount, 'MT') <= 0) {
		$messages[] = 'No unpaid invoice of third party '.((int) $socid).' remains to record the payment of '.price2num($amount, 'MT').'. The payment was received but it was not recorded.';
		return ['result' => -2, 'messages' => $messages, 'paidinvoices' => $paidinvoices, 'excess' => $excess];
	}

	$allocation = allocateOnlinePaymentToInvoices($invoices, $amount);
	$amounts = $allocation['amounts'];
	$excess = $allocation['excess'];
	if ($excess > 0) {
		// The amount paid is higher than what remains to pay (an invoice was paid in the meantime): the money is already
		// captured, so we record the excess on the last invoice instead of failing.
		$tmpids = array_keys($invoices);
		$lastinvoiceid = (int) end($tmpids);
		$amounts[$lastinvoiceid] = (float) price2num((empty($amounts[$lastinvoiceid]) ? 0 : $amounts[$lastinvoiceid]) + $excess, 'MT');
		$messages[] = 'Warning: the amount paid is higher than the remainder to pay of the invoices, the excess of '.price2num($excess, 'MT').' was added to the payment of invoice '.$invoices[$lastinvoiceid]['ref'];
		dol_syslog("recordOnlinePaymentOfThirdpartyInvoices excess of ".$excess." added to invoice id=".$lastinvoiceid, LOG_WARNING);
	}
	foreach ($amounts as $invoiceid => $tmpamount) {
		$paidinvoices[$invoiceid] = $invoices[$invoiceid]['ref'];
	}

	$error = 0;

	$db->begin();

	$paiement = new Paiement($db);
	$paiement->datepaye = dol_now();
	$paiement->amounts = $amounts;
	$paiement->paiementid = $paymenttypeid;
	$paiement->num_payment = '';
	$paiement->note_public = $note_public;
	$paiement->ext_payment_id = $ext_payment_id;
	$paiement->ext_payment_site = $ext_payment_site;

	$paymentid = $paiement->create($user, 1);	// This includes the closing of the paid invoices
	if ($paymentid < 0) {
		$messages[] = $paiement->error.' '.implode("<br>\n", $paiement->errors);
		$error++;
	} else {
		$messages[] = 'Payment created for invoices '.implode(', ', $paidinvoices);
	}

	if (!$error && isModEnabled("bank")) {
		if ($bankaccountid > 0) {
			$result = $paiement->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $bankaccountid, '', '');
			if ($result < 0) {
				$messages[] = $paiement->error.' '.implode("<br>\n", $paiement->errors);
				$error++;
			} else {
				$messages[] = 'Bank transaction of payment created';
			}
		} else {
			$messages[] = 'Setup of bank account to use in module '.$ext_payment_site.' was not set. Your payment was really executed but we failed to record it. Please contact us.';
			$error++;
		}
	}

	if (!$error) {
		$db->commit();
		return ['result' => $paymentid, 'messages' => $messages, 'paidinvoices' => $paidinvoices, 'excess' => $excess];
	}

	$db->rollback();
	return ['result' => -3, 'messages' => $messages, 'paidinvoices' => $paidinvoices, 'excess' => $excess];
}
