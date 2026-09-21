<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2006	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2012       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2014-2020  Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2015  		Benoit Bruchard			<benoitb21@gmail.com>
 * Copyright (C) 2024-2026	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
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
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functionsnumtoword.lib.php';


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
		global $langs;

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
				$donref = dol_sanitizeFileName((string) $don->ref);
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
				// Don::fetch() stores the payment mode into mode_reglement_id. The modepaymentid property is only
				// set by the create/update forms, so it is always empty when the document is built from a fetched
				// donation and no payment mode checkbox was ever ticked on the receipt.
				$modepaymentid = !empty($don->mode_reglement_id) ? $don->mode_reglement_id : $don->modepaymentid;
				if ($modepaymentid) {
					$paymentmode = !empty($formclass->cache_types_paiements[(int) $modepaymentid]['label']) ? $formclass->cache_types_paiements[$modepaymentid]['label'] : '';
				} else {
					$paymentmode = '';
				}
				$modepaymentcode = !empty($formclass->cache_types_paiements[(int) $modepaymentid]['code']) ? $formclass->cache_types_paiements[(int) $modepaymentid]['code'] : "";
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
				// Donator identity.
				// When option DONATION_USE_THIRDPARTIES is on, the donation form does not show the name/address
				// inputs at all, so llx_don only holds fk_soc and every donator field stays empty. The receipt
				// would then be issued without the mandatory identity of the donator, so fall back on the linked
				// third party for each field left empty.
				$donatorsociete = (string) $don->societe;
				$donatorlastname = (string) $don->lastname;
				$donatorfirstname = (string) $don->firstname;
				$donatoraddress = (string) $don->address;
				$donatorzip = (string) $don->zip;
				$donatortown = (string) $don->town;

				if (!empty($don->socid) && $don->socid > 0) {
					$donatorthirdparty = new Societe($this->db);
					if ($donatorthirdparty->fetch($don->socid) > 0) {
						if (dol_strlen(trim($donatorsociete.$donatorlastname.$donatorfirstname)) == 0) {
							// A third party holds a single name field, even for a private individual, so it goes
							// to the "Name" cell of the form and the "Firstname" cell is left empty.
							$donatorsociete = (string) $donatorthirdparty->name;
						}
						if (dol_strlen(trim($donatoraddress)) == 0) {
							$donatoraddress = (string) $donatorthirdparty->address;
						}
						if (dol_strlen(trim($donatorzip)) == 0) {
							$donatorzip = (string) $donatorthirdparty->zip;
						}
						if (dol_strlen(trim($donatortown)) == 0) {
							$donatortown = (string) $donatorthirdparty->town;
						}
					} else {
						dol_syslog("html_cerfafr::write_file Failed to load thirdparty ".$don->socid." linked to donation ".$don->id.", donator block will remain empty - ".$donatorthirdparty->error, LOG_ERR);
					}
				}

				if (dol_strlen(trim($donatorsociete.$donatorlastname.$donatorfirstname)) == 0) {
					dol_syslog("html_cerfafr::write_file No name found for the donator of donation ".$don->id.", the receipt will not be compliant", LOG_WARNING);
				}

				// Define contents
				$donmodel = DOL_DOCUMENT_ROOT."/core/modules/dons/html_cerfafr.html";

				$form = file_get_contents($donmodel);

				// TODO Remove what was already included into getCommonSubstitutionArray / make_substitutions
				$form = str_replace('__REF__', (string) $don->id, $form);
				$form = str_replace('__DATE__', dol_print_date($don->date, 'day', false, $outputlangs), $form);
				//$form = str_replace('__IP__',$user->ip,$form); // TODO $user->ip not exist
				$form = str_replace('__AMOUNT__', price($don->amount), $form);
				$form = str_replace('__AMOUNT_TEXT__', dol_convertToWord($don->amount, $outputlangs, $outputlangs->transnoentitiesnoconv("Currency".$currency), true), $form);

				$form = str_replace('__DONATOR_FIRSTNAME__', dol_escape_htmltag($donatorfirstname), $form);
				// The template concatenates __DONATOR_SOCIETE__ and __DONATOR_LASTNAME__ with no separator,
				// so add a line break when both are filled in.
				$donatorlastnameprefix = (dol_strlen(trim($donatorsociete)) > 0 && dol_strlen(trim($donatorlastname)) > 0) ? '<br>' : '';
				$form = str_replace('__DONATOR_LASTNAME__', $donatorlastnameprefix.dol_escape_htmltag($donatorlastname), $form);
				$form = str_replace('__DONATOR_SOCIETE__', dol_escape_htmltag($donatorsociete), $form);

				$form = str_replace('__DONATOR_STATUT__', (string) $don->statut, $form);
				$form = str_replace('__DONATOR_ADDRESS__', dol_nl2br(dol_escape_htmltag($donatoraddress, 0, 1)), $form);
				$form = str_replace('__DONATOR_ZIP__', dol_escape_htmltag($donatorzip), $form);
				$form = str_replace('__DONATOR_TOWN__', dol_escape_htmltag($donatortown), $form);

				$form = str_replace('__PAYMENTMODE_LIB__ ', (string) $paymentmode, $form);
				$form = str_replace('__NOW__', dol_print_date($now, 'day', false, $outputlangs), $form);
				$form = str_replace('__DONATION_PAYMENT_MODE__', $ModePaiement, $form);
				$form = str_replace('__DONATION_MESSAGE__', getDolGlobalString('DONATION_MESSAGE'), $form);

				// Replace with a generic replacement feature '__(XXX)__' must become $outputlangs->trans('XXX')
				$substitutionkey = getCommonSubstitutionArray($outputlangs, 0, null, $don);

				$form = make_substitutions($form, $substitutionkey, $outputlangs);


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
}
