<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2006	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2012       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2014-2020  Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2015  		Benoit Bruchard			<benoitb21@gmail.com>
 * Copyright (C) 2015  		Benjamin Neumann <btdn@sigsoft.org>
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
 *	\file       htdocs/core/modules/dons/html_generic.modules.php
 *	\ingroup    don
 *	\brief      Form of donation
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/dons/modules_don.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';


/**
 *	Class to generate document for a generic donations receipt
 */
class html_generic extends ModeleDon
{
	/**
	 *  Constructor
	 *
	 *  @param      DoliDb      $db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;
		$this->name = "generic";
		$this->description = $langs->trans('DonationsReceiptModel').'';
		$this->option_multilang = 1;

		$this->type = 'html';
	}

	/**
	 * 	Return if a module can be used or not
	 *
	 *  @return	boolean     true if module can be used
	 */
	public function isEnabled()
	{
		return true;
	}

	/**
	 *  Load translation files
	 *
	 *  @param	Translate	$outputlangs    Lang object for output language
	 *  @return	Translate	$outputlangs    Lang object for output language
	 */
	private function loadTranslationFiles($outputlangs)
	{
		if (!is_object($outputlangs)) {
			global $langs;
			$outputlangs = $langs;
		}

		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "donations"));

		return $outputlangs;
	}

	/**
	 *  Write the object to document file to disk
	 *
	 *  @param	Don			$don	        Donation object
	 *  @return	string             			Label for payment type
	 */
	private function getDonationPaymentType($don)
	{
		$formclass = new Form($this->db);

		// This is not the proper way to do it but $formclass->form_modes_reglement
		// prints the translation instead of returning it
		$formclass->load_cache_types_paiements();

		if ($don->mode_reglement_id) {
			$paymentmode = $formclass->cache_types_paiements[$don->mode_reglement_id]['label'];
		} else {
			$paymentmode = '';
		}

		return $paymentmode;
	}

	/**
	 *  Get the contents of the file
	 *
	 *  @param	Don			$don	        Donation object
	 *  @param	Translate	$outputlangs    Lang object for output language
	 *  @param	string		$currency		Currency code
	 *  @return	string             			Contents of the file
	 */
	private function getContents($don, $outputlangs, $currency)
	{
		global $user, $conf, $langs, $mysoc;

		$now = dol_now();

		$currency = !empty($currency) ? $currency : $conf->currency;

		$donmodel = DOL_DOCUMENT_ROOT."/core/modules/dons/html_generic.html";
		$form = implode('', file($donmodel));
		$form = str_replace('__NOW__', dol_print_date($now, 'day', false, $outputlangs), $form);
		$form = str_replace('__REF__', $don->id, $form);
		$form = str_replace('__DATE__', dol_print_date($don->date, 'day', false, $outputlangs), $form);

		$form = str_replace('__BENEFICIARY_NAME__', $mysoc->name, $form);
		$form = str_replace('__BENEFICIARY_FULL_ADDRESS__', $mysoc->getFullAddress(1, "<br>", 1), $form);

		$form = str_replace('__PAYMENTMODE_LABEL__', $this->getDonationPaymentType($don), $form);
		$form = str_replace('__AMOUNT__', price($don->amount), $form);
		$form = str_replace('__CURRENCY_CODE__', $conf->currency, $form);
		if (isModEnabled("societe") && getDolGlobalString('DONATION_USE_THIRDPARTIES') && $don->socid > 0 && $don->thirdparty) {
			$form = str_replace('__DONOR_FULL_NAME__', $don->thirdparty->name, $form);
			$form = str_replace('__DONOR_FULL_ADDRESS__', $don->thirdparty->getFullAddress(1, ", ", 1), $form);
		} else {
			$form = str_replace('__DONOR_FULL_NAME__', $don->getFullName($langs), $form);
			$form = str_replace('__DONOR_FULL_ADDRESS__', $don->getFullAddress(1, " ", 1), $form);
		}

		$form = str_replace('__DonationTitle__', $outputlangs->trans("DonationTitle"), $form);
		$form = str_replace('__DonationRef__', $outputlangs->trans("DonationRef"), $form);
		$form = str_replace('__Date__', $outputlangs->trans("Date"), $form);
		$form = str_replace('__DonationDatePayment__', $outputlangs->trans("DonationDatePayment"), $form);
		$form = str_replace('__Donor__', $outputlangs->trans("Donor"), $form);
		$form = str_replace('__Amount__', $outputlangs->trans("Amount"), $form);
		$form = str_replace('__PaymentMode__', $outputlangs->trans("PaymentMode"), $form);

		$notePublic = '';
		if (getDolGlobalInt('DONATION_NOTE_PUBLIC') >= 1 && !empty($don->note_public)) {
			$notePublic = '<div id="note-public"><p>'.$don->note_public.'</p></div>';
		}
		$form = str_replace('__NOTE_PUBLIC__', $notePublic, $form);

		$donationMessage = '';
		if (getDolGlobalString('DONATION_MESSAGE')) {
			$donationMessage = '<div id="donation-message"><p>' . getDolGlobalString('DONATION_MESSAGE').'</p></div>';
		}
		$form = str_replace('__DONATION_MESAGE__', $donationMessage, $form);

		return $form;
	}

	/**
	 *  Write the object to document file to disk
	 *
	 *  @param	string			$path	        Path for the file
	 *  @param	string			$contents	Contents of the file
	 *  @return	NULL
	 */
	private function saveFile($path, $contents)
	{
		dol_syslog("html_generic::saveFile $path");
		$handle = fopen($path, "w");
		fwrite($handle, $contents);
		fclose($handle);
		dolChmod($path);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Write the object to document file to disk
	 *
	 *  @param	Don			$don	        Donation object
	 *  @param	Translate	$outputlangs    Lang object for output language
	 *  @param	string		$currency		Currency code
	 *  @return	int             			>0 if OK, <0 if KO
	 */
	public function write_file($don, $outputlangs, $currency = '')
	{
		// phpcs:enable
		global $user, $conf, $langs, $mysoc;

		$id = (!is_object($don) ? $don : '');

		$outputlangs = $this->loadTranslationFiles($outputlangs);

		if (!empty($conf->don->dir_output)) {
			// Definition of the object don (for upward compatibility)
			if (!is_object($don)) {
				$don = new Don($this->db);
				$ret = $don->fetch($id);
				$id = $don->id;
			}

			// Definition of $dir and $file
			if (!empty($don->specimen)) {
				$dir = $conf->don->dir_output;
				$file = $dir."/SPECIMEN.html";
			} else {
				$donref = dol_sanitizeFileName($don->ref);
				$dir = $conf->don->dir_output."/".$donref;
				$file = $dir."/".$donref.".html";
			}

			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
					return -1;
				}
			}

			if (file_exists($dir)) {
				$this->saveFile($file, $this->getContents($don, $outputlangs, $currency));

				$this->result = array('fullpath'=>$file);

				return 1;
			} else {
				$this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $langs->trans("ErrorConstantNotDefined", "DON_OUTPUTDIR");
			return 0;
		}
	}
}
