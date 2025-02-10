<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2006	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2012       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2014-2020  Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2015  		Benoit Bruchard			<benoitb21@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/core/modules/dons/html_cerfafr.modules.php
 *	\ingroup    don
 *	\brief      Form of donation
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/dons/modules_don.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';


/**
 *	Class to generate document for subscriptions
 */
class html_cerfafr extends ModeleDon
{
	/**
	 *  Constructor
	 *
	 *  @param      DoliDB      $db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;
		$this->name = "cerfafr";
		$this->description = $langs->trans('DonationsReceiptModel').' - fr_FR - Cerfa 11580*04';

		// Dimension page for size A4
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


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Write the object to document file to disk
	 *
	 *	@param	Don			$don			Donation object
	 *  @param  Translate	$outputlangs	Lang object for output language
	 *  @param	string		$currency		Currency code
	 *	@return	int<-1,1>					>0 if OK, <0 if KO
	 */
	public function write_file($don, $outputlangs, $currency = '')
	{
		// phpcs:enable
		global $user, $conf, $langs, $mysoc;

		$now = dol_now();
		$id = (!is_object($don) ? $don : '');

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "donations"));

		$currency = !empty($currency) ? $currency : $conf->currency;

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
				$formclass = new Form($this->db);

				// This is not the proper way to do it but $formclass->form_modes_reglement
				// prints the translation instead of returning it
				$formclass->load_cache_types_paiements();
				if ($don->modepaymentid) {
					$paymentmode = $formclass->cache_types_paiements[$don->modepaymentid]['label'];
				} else {
					$paymentmode = '';
				}
				$modepaymentcode = !empty($formclass->cache_types_paiements[$don->modepaymentid]['code']) ? $formclass->cache_types_paiements[$don->modepaymentid]['code'] : "";
				if ($modepaymentcode == 'CHQ') {
					$ModePaiement = '<td width="25%"><input type="checkbox"> Remise d\'espèces</td><td width="25%"><input type="checkbox" disabled="true" checked="checked"> Chèque</td><td width="50%"><input type="checkbox"> Virement, prélèvement, carte bancaire</td>';
				} elseif ($modepaymentcode == 'LIQ') {
					$ModePaiement = '<td width="25%"><input type="checkbox" checked="checked"> Remise d\'espèces</td><td width="25%"><input type="checkbox"> Chèque</td><td width="50%"><input type="checkbox"> Virement, prélèvement, carte bancaire</td>';
				} elseif ($modepaymentcode == 'VIR' || $modepaymentcode == 'PRE' || $modepaymentcode == 'CB') {
					$ModePaiement = '<td width="25%"><input type="checkbox"> Remise d\'espèces</td><td width="25%"><input type="checkbox"> Chèque</td><td width="50%"><input type="checkbox" checked="checked"> Virement, prélèvement, carte bancaire</td>';
				} else {
					$ModePaiement = '<td width="25%"><input type="checkbox"> Remise d\'espèces</td><td width="25%"><input type="checkbox"> Chèque</td><td width="50%"><input type="checkbox"> Virement, prélèvement, carte bancaire</td>';
				}

				/*
				if (empty($don->societe))
				{
					$CodeDon = '<td width="33%"><input type="checkbox" disabled="true" checked="checked" > 200 du CGI</td><td width="33%"><input type="checkbox" disabled="true" > 238 bis du CGI</td><td width="33%"><input type="checkbox" disabled="true" > 978 du CGI</td>';
				}
				else
				{
					$CodeDon = '<td width="33%"><input type="checkbox" disabled="true" > 200 du CGI</td><td width="33%"><input type="checkbox" disabled="true" checked="checked" > 238 bis du CGI</td><td width="33%"><input type="checkbox" disabled="true" > 978 du CGI</td>';
				}
				*/

				// Define contents
				$donmodel = DOL_DOCUMENT_ROOT."/core/modules/dons/html_cerfafr.html";
				$form = implode('', file($donmodel));
				$form = str_replace('__REF__', (string) $don->id, $form);
				$form = str_replace('__DATE__', dol_print_date($don->date, 'day', false, $outputlangs), $form);
				//$form = str_replace('__IP__',$user->ip,$form); // TODO $user->ip not exist
				$form = str_replace('__AMOUNT__', price($don->amount), $form);
				$form = str_replace('__AMOUNTLETTERS__', $this->amountToLetters($don->amount), $form);
				$form = str_replace('__CURRENCY__', $outputlangs->transnoentitiesnoconv("Currency".$currency), $form);
				$form = str_replace('__CURRENCYCODE__', $conf->currency, $form);
				$form = str_replace('__MAIN_INFO_SOCIETE_NOM__', $mysoc->name, $form);
				$form = str_replace('__MAIN_INFO_SOCIETE_ADDRESS__', $mysoc->address, $form);
				$form = str_replace('__MAIN_INFO_SOCIETE_ZIP__', $mysoc->zip, $form);
				$form = str_replace('__MAIN_INFO_SOCIETE_TOWN__', $mysoc->town, $form);
				$form = str_replace('__MAIN_INFO_SOCIETE_OBJECT__', $mysoc->socialobject, $form);
				$form = str_replace('__DONATOR_FIRSTNAME__', $don->firstname, $form);
				$form = str_replace('__DONATOR_LASTNAME__', $don->lastname, $form);
				$form = str_replace('__DONATOR_SOCIETE__', $don->societe, $form);
				$form = str_replace('__DONATOR_STATUT__', (string) $don->statut, $form);
				$form = str_replace('__DONATOR_ADDRESS__', $don->address, $form);
				$form = str_replace('__DONATOR_ZIP__', $don->zip, $form);
				$form = str_replace('__DONATOR_TOWN__', $don->town, $form);
				$form = str_replace('__PAYMENTMODE_LIB__ ', $paymentmode, $form);
				$form = str_replace('__NOW__', dol_print_date($now, 'day', false, $outputlangs), $form);
				$form = str_replace('__DonationRef__', $outputlangs->trans("DonationRef"), $form);
				$form = str_replace('__DonationTitle__', $outputlangs->trans("DonationTitle"), $form);
				$form = str_replace('__DonationReceipt__', $outputlangs->trans("DonationReceipt"), $form);
				$form = str_replace('__DonationRecipient__', $outputlangs->trans("DonationRecipient"), $form);
				$form = str_replace('__DonationDatePayment__', $outputlangs->trans("DonationDatePayment"), $form);
				$form = str_replace('__PaymentMode__', $outputlangs->trans("PaymentMode"), $form);
				// $form = str_replace('__CodeDon__',$CodeDon,$form);
				$form = str_replace('__Name__', $outputlangs->trans("Name"), $form);
				$form = str_replace('__Address__', $outputlangs->trans("Address"), $form);
				$form = str_replace('__Zip__', $outputlangs->trans("Zip"), $form);
				$form = str_replace('__Town__', $outputlangs->trans("Town"), $form);
				$form = str_replace('__Object__', $outputlangs->trans("Object"), $form);
				$form = str_replace('__Donor__', $outputlangs->trans("Donor"), $form);
				$form = str_replace('__Date__', $outputlangs->trans("Date"), $form);
				$form = str_replace('__Signature__', $outputlangs->trans("Signature"), $form);
				$form = str_replace('__Message__', $outputlangs->trans("Message"), $form);
				$form = str_replace('__IConfirmDonationReception__', $outputlangs->trans("IConfirmDonationReception"), $form);
				$form = str_replace('__DonationMessage__', $conf->global->DONATION_MESSAGE, $form);

				$form = str_replace('__ModePaiement__', $ModePaiement, $form);

				$frencharticle = '';
				if (preg_match('/fr/i', $outputlangs->defaultlang)) {
					$frencharticle = '<font size="+1">Article 200, 238 bis et 978 du code général des impôts (CGI)</font>';
				}
				$form = str_replace('__FrenchArticle__', $frencharticle, $form);

				$frencheligibility = '';
				if (preg_match('/fr/i', $outputlangs->defaultlang)) {
					$frencheligibility = 'Le bénéficiaire certifie sur l\'honneur que les dons et versements qu\'il reçoit ouvrent droit à la réduction d\'impôt prévue à l\'article :';
				}
				$form = str_replace('__FrenchEligibility__', $frencheligibility, $form);

				$art200 = '';
				if ($mysoc->country_code == 'FR') {
					if (getDolGlobalInt('DONATION_ART200') >= 1) {
						$art200 = '<input type="checkbox" disabled="true" checked="checked" >200 du CGI';
					} else {
						$art200 = '<input type="checkbox" disabled="true">200 du CGI';
					}
				}
				$form = str_replace('__ARTICLE200__', $art200, $form);

				$art238 = '';
				if ($mysoc->country_code == 'FR') {
					if (getDolGlobalInt('DONATION_ART238') >= 1) {
						$art238 = '<input type="checkbox" disabled="true" checked="checked" >238 bis du CGI';
					} else {
						$art238 = '<input type="checkbox" disabled="true">238 bis du CGI';
					}
				}
				$form = str_replace('__ARTICLE238__', $art238, $form);

				$art978 = '';
				if ($mysoc->country_code == 'FR') {
					if (getDolGlobalInt('DONATION_ART978') >= 1) {
						$art978 = '<input type="checkbox" disabled="true" checked="checked" >978 du CGI';
					} else {
						$art978 = '<input type="checkbox" disabled="true">978 du CGI';
					}
				}
				$form = str_replace('__ARTICLE978__', $art978, $form);

				// Save file on disk
				dol_syslog("html_cerfafr::write_file $file");
				$handle = fopen($file, "w");
				fwrite($handle, $form);
				fclose($handle);
				dolChmod($file);

				$this->result = array('fullpath' => $file);

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

	/**
	 * numbers to letters
	 *
	 * @param   mixed   $montant    amount
	 * @param   mixed   $devise1    devise 1 ex: euro
	 * @param   mixed   $devise2    devise 2 ex: centimes
	 * @return string               amount in letters
	 */
	private function amountToLetters($montant, $devise1 = '', $devise2 = '')
	{
		$unite = array();
		$dix = array();
		$cent = array();
		if (empty($devise1)) {
			$dev1 = 'euros';
		} else {
			$dev1 = $devise1;
		}
		if (empty($devise2)) {
			$dev2 = 'centimes';
		} else {
			$dev2 = $devise2;
		}
		$valeur_entiere = intval($montant);
		$valeur_decimal = intval(round($montant - intval($montant), 2) * 100);
		$dix_c = intval($valeur_decimal % 100 / 10);
		$cent_c = intval($valeur_decimal % 1000 / 100);
		$unite[1] = $valeur_entiere % 10;
		$dix[1] = intval($valeur_entiere % 100 / 10);
		$cent[1] = intval($valeur_entiere % 1000 / 100);
		$unite[2] = intval($valeur_entiere % 10000 / 1000);
		$dix[2] = intval($valeur_entiere % 100000 / 10000);
		$cent[2] = intval($valeur_entiere % 1000000 / 100000);
		$unite[3] = intval($valeur_entiere % 10000000 / 1000000);
		$dix[3] = intval($valeur_entiere % 100000000 / 10000000);
		$cent[3] = intval($valeur_entiere % 1000000000 / 100000000);
		$chif = array('', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix sept', 'dix huit', 'dix neuf');
		$secon_c = '';
		$trio_c = '';
		$prim = array();
		$secon = array();
		$trio = array();
		// @phpstan-ignore-next-line
		'@phan-var string[] $prim
		 @phan-var string[] $secon
		 @phan-var string[] $trio
		';
		for ($i = 1; $i <= 3; $i++) {
			$prim[$i] = '';
			$secon[$i] = '';
			$trio[$i] = '';
			if ($dix[$i] == 0) {
				$secon[$i] = '';
				$prim[$i] = $chif[$unite[$i]];
			} elseif ($dix[$i] == 1) {
				$secon[$i] = '';
				$prim[$i] = $chif[($unite[$i] + 10)];
			} elseif ($dix[$i] == 2) {
				if ($unite[$i] == 1) {
					$secon[$i] = 'vingt et';
					$prim[$i] = $chif[$unite[$i]];
				} else {
					$secon[$i] = 'vingt';
					$prim[$i] = $chif[$unite[$i]];
				}
			} elseif ($dix[$i] == 3) {
				if ($unite[$i] == 1) {
					$secon[$i] = 'trente et';
					$prim[$i] = $chif[$unite[$i]];
				} else {
					$secon[$i] = 'trente';
					$prim[$i] = $chif[$unite[$i]];
				}
			} elseif ($dix[$i] == 4) {
				if ($unite[$i] == 1) {
					$secon[$i] = 'quarante et';
					$prim[$i] = $chif[$unite[$i]];
				} else {
					$secon[$i] = 'quarante';
					$prim[$i] = $chif[$unite[$i]];
				}
			} elseif ($dix[$i] == 5) {
				if ($unite[$i] == 1) {
					$secon[$i] = 'cinquante et';
					$prim[$i] = $chif[$unite[$i]];
				} else {
					$secon[$i] = 'cinquante';
					$prim[$i] = $chif[$unite[$i]];
				}
			} elseif ($dix[$i] == 6) {
				if ($unite[$i] == 1) {
					$secon[$i] = 'soixante et';
					$prim[$i] = $chif[$unite[$i]];
				} else {
					$secon[$i] = 'soixante';
					$prim[$i] = $chif[$unite[$i]];
				}
			} elseif ($dix[$i] == 7) {
				if ($unite[$i] == 1) {
					$secon[$i] = 'soixante et';
					$prim[$i] = $chif[$unite[$i] + 10];
				} else {
					$secon[$i] = 'soixante';
					$prim[$i] = $chif[$unite[$i] + 10];
				}
			} elseif ($dix[$i] == 8) {
				if ($unite[$i] == 1) {
					$secon[$i] = 'quatre-vingts et';
					$prim[$i] = $chif[$unite[$i]];
				} else {
					$secon[$i] = 'quatre-vingt';
					$prim[$i] = $chif[$unite[$i]];
				}
			} elseif ($dix[$i] == 9) {
				if ($unite[$i] == 1) {
					$secon[$i] = 'quatre-vingts et';
					$prim[$i] = $chif[$unite[$i] + 10];
				} else {
					$secon[$i] = 'quatre-vingts';
					$prim[$i] = $chif[$unite[$i] + 10];
				}
			}
			if ($cent[$i] == 1) {
				$trio[$i] = 'cent';
			} elseif ($cent[$i] != 0 || $cent[$i] != '') {
				$trio[$i] = $chif[$cent[$i]].' cents';
			}
		}


		$chif2 = array('', 'dix', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante-dix', 'quatre-vingts', 'quatre-vingts dix');
		$secon_c = $chif2[$dix_c];
		if ($cent_c == 1) {
			$trio_c = 'cent';
		} elseif ($cent_c != 0 || $cent_c != '') {
			$trio_c = $chif[$cent_c].' cents';
		}

		if (($cent[3] == 0 || $cent[3] == '') && ($dix[3] == 0 || $dix[3] == '') && ($unite[3] == 1)) {
			$somme = $trio[3].'  '.$secon[3].' '.$prim[3].' million ';
		} elseif (($cent[3] != 0 && $cent[3] != '') || ($dix[3] != 0 && $dix[3] != '') || ($unite[3] != 0 && $unite[3] != '')) {
			$somme = $trio[3].' '.$secon[3].' '.$prim[3].' millions ';
		} else {
			$somme = $trio[3].' '.$secon[3].' '.$prim[3];
		}

		if (($cent[2] == 0 || $cent[2] == '') && ($dix[2] == 0 || $dix[2] == '') && ($unite[2] == 1)) {
			$somme .= ' mille ';
		} elseif (($cent[2] != 0 && $cent[2] != '') || ($dix[2] != 0 && $dix[2] != '') || ($unite[2] != 0 && $unite[2] != '')) {
			$somme .= $trio[2].' '.$secon[2].' '.$prim[2].' milles ';
		} else {
			$somme .= $trio[2].' '.$secon[2].' '.$prim[2];
		}

		$somme .= $trio[1].' '.$secon[1].' '.$prim[1];

		$somme .= ' '.$dev1.' ';

		if (($cent_c == '0' || $cent_c == '') && ($dix_c == '0' || $dix_c == '')) {
			return $somme.' et z&eacute;ro '.$dev2;
		} else {
			return $somme.$trio_c.' '.$secon_c.' '.$dev2;
		}
	}
}
