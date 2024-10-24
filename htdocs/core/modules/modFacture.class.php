<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2018	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2021-2024	Alexandre Spangaro		<alexandre@inovea-conseil.com>
 * Copyright (C) 2022-2024	Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		William Mead			<william.mead@manchenumerique.fr>
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
 * 		\defgroup   facture     Module customer invoices
 *      \brief      Module to manage customer invoices
 *      \file       htdocs/core/modules/modFacture.class.php
 *		\ingroup    invoice
 *		\brief      Description and activation file for the module customer invoices
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *  Class to describe module customer invoices
 */
class modFacture extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $user, $mysoc;

		$this->db = $db;
		$this->numero = 30;

		$this->family = "financial";
		$this->module_position = '11';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Gestion des factures";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'bill';

		// Data directories to create when module is enabled
		$this->dirs = array("/facture/temp");

		// Dependencies
		$this->depends = array('always' => "modSociete");
		$this->requiredby = array("modComptabilite", "modAccounting");
		$this->conflictwith = array();
		$this->langfiles = array("bills", "companies", "compta", "products");
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='text')
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='text')

		// Config pages
		$this->config_page_url = array("invoice.php");

		// Constants
		$this->const = array();
		$r = 0;

		$this->const[$r][0] = "FACTURE_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_facture_terre";
		$this->const[$r][3] = 'Name of numbering numerotation rules of invoice';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "FACTURE_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "sponge";
		$this->const[$r][3] = 'Name of PDF model of invoice';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "FACTURE_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/invoices";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;
		$r++;

		// Boxes
		//$this->boxes = array(0=>array(1=>'box_factures_imp.php'),1=>array(1=>'box_factures.php'));
		$this->boxes = array(
				0 => array('file' => 'box_factures_imp.php', 'enabledbydefaulton' => 'Home'),
				1 => array('file' => 'box_factures.php', 'enabledbydefaulton' => 'Home'),
				2 => array('file' => 'box_graph_invoices_permonth.php', 'enabledbydefaulton' => 'Home'),
				3 => array('file' => 'box_customers_outstanding_bill_reached.php', 'enabledbydefaulton' => 'Home')
		);

		// Cronjobs
		$arraydate = dol_getdate(dol_now());
		$datestart = dol_mktime(23, 0, 0, $arraydate['mon'], $arraydate['mday'], $arraydate['year']);
		$this->cronjobs = array(
			0 => array(
				'label' => 'RecurringInvoicesJob',
				'jobtype' => 'method',
				'class' => 'compta/facture/class/facture-rec.class.php',
				'objectname' => 'FactureRec',
				'method' => 'createRecurringInvoices',
				'parameters' => '',
				'comment' => 'Generate recurring invoices',
				'frequency' => 1,
				'unitfrequency' => 3600 * 24,
				'priority' => 51,
				'status' => 1,
				'test' => '$conf->facture->enabled',
				'datestart' => $datestart
			),
			1 => array(
				'label' => 'SendEmailsRemindersOnInvoiceDueDate',
				'jobtype' => 'method',
				'class' => 'compta/facture/class/facture.class.php',
				'objectname' => 'Facture',
				'method' => 'sendEmailsRemindersOnInvoiceDueDate',
				'parameters' => "10,all,EmailTemplateCode,duedate",
				'comment' => 'Send an email when we reach the invoice due date (or invoice date) - n days. First param is n, the number of days before due date (or invoice date) to send the remind (or after if value is negative), second parameter is "all" or a payment mode code, third parameter is the code of the email template to use (an email template with the EmailTemplateCode must exists. The version of the email template in the language of the thirdparty will be used in priority. Language of the thirdparty will be also used to update the PDF of the sent invoice). The fourth parameter is the string "duedate" (default) or "invoicedate" to define which date of the invoice to use.',
				'frequency' => 1,
				'unitfrequency' => 3600 * 24,
				'priority' => 50,
				'status' => 0,
				'test' => '$conf->facture->enabled',
				'datestart' => $datestart
			),
		);

		// Permissions
		$this->rights = array();
		$this->rights_class = 'facture';
		$r = 0;

		$r++;
		$this->rights[$r][0] = 11;
		$this->rights[$r][1] = 'Read invoices';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 12;
		$this->rights[$r][1] = 'Create and update invoices';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';

		// There is a particular permission for unvalidate because this may be not forbidden by some laws
		$r++;
		$this->rights[$r][0] = 13;
		$this->rights[$r][1] = 'Devalidate invoices';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'invoice_advance';
		$this->rights[$r][5] = 'unvalidate';

		$r++;
		$this->rights[$r][0] = 14;
		$this->rights[$r][1] = 'Validate invoices';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'invoice_advance';
		$this->rights[$r][5] = 'validate';

		$r++;
		$this->rights[$r][0] = 15;
		$this->rights[$r][1] = 'Send invoices by email';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'invoice_advance';
		$this->rights[$r][5] = 'send';

		$r++;
		$this->rights[$r][0] = 16;
		$this->rights[$r][1] = 'Issue payments on invoices';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'paiement';

		$r++;
		$this->rights[$r][0] = 19;
		$this->rights[$r][1] = 'Delete invoices';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 1321;
		$this->rights[$r][1] = 'Export customer invoices, attributes and payments';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'facture';
		$this->rights[$r][5] = 'export';

		$r++;
		$this->rights[$r][0] = 1322;
		$this->rights[$r][1] = 'Re-open a fully paid invoice';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'invoice_advance';
		$this->rights[$r][5] = 'reopen';


		// Menus
		//-------
		$this->menu = 1; // This module add menu entries. They are coded into menu manager.


		// Imports
		//--------
		$r = 1;

		$r++;
		$this->import_code[$r] = $this->rights_class.'_'.$r;
		$this->import_label[$r] = "Invoices"; // Translation key
		$this->import_icon[$r] = $this->picto;
		$this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r] = array('f' => MAIN_DB_PREFIX.'facture', 'extra' => MAIN_DB_PREFIX.'facture_extrafields');
		$this->import_tables_creator_array[$r] = array('f' => 'fk_user_author'); // Fields to store import user id
		$this->import_fields_array[$r] = array(
			'f.ref' => 'InvoiceRef*',
			'f.ref_ext' => 'ExternalRef',
			'f.ref_client' => 'RefCustomer',
			'f.type' => 'Type*',
			'f.fk_soc' => 'Customer*',
			'f.datec' => 'InvoiceDateCreation',
			'f.datef' => 'DateInvoice',
			'f.date_valid' => 'Validation Date',
			'f.paye' => 'InvoicePaid',
			'f.total_tva' => 'TotalVAT',
			'f.total_ht' => 'TotalHT',
			'f.total_ttc' => 'TotalTTC',
			'f.fk_statut' => 'InvoiceStatus',
			'f.fk_user_modif' => 'Modifier Id',
			'f.fk_user_valid' => 'Validator Id',
			'f.fk_user_closing' => 'Closer Id',
			'f.fk_facture_source' => 'Invoice Source Id',
			'f.fk_projet' => 'Project Id',
			'f.fk_account' => 'Bank Account',
			'f.fk_currency' => 'Currency*',
			'f.fk_cond_reglement' => 'PaymentTerm',
			'f.fk_mode_reglement' => 'PaymentMode',
			'f.date_lim_reglement' => 'DateMaxPayment',
			'f.note_public' => 'InvoiceNote',
			'f.note_private' => 'NotePrivate',
			'f.model_pdf' => 'Model'
		);
		if (isModEnabled("multicurrency")) {
			$this->import_fields_array[$r]['f.multicurrency_code'] = 'Currency';
			$this->import_fields_array[$r]['f.multicurrency_tx'] = 'CurrencyRate';
			$this->import_fields_array[$r]['f.multicurrency_total_ht'] = 'MulticurrencyAmountHT';
			$this->import_fields_array[$r]['f.multicurrency_total_tva'] = 'MulticurrencyAmountVAT';
			$this->import_fields_array[$r]['f.multicurrency_total_ttc'] = 'MulticurrencyAmountTTC';
		}
		// Add extra fields
		$import_extrafield_sample = array();
		$sql = "SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'facture' AND entity IN (0, ".$conf->entity.")";
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$fieldname = 'extra.'.$obj->name;
				$fieldlabel = ucfirst($obj->label);
				$this->import_fields_array[$r][$fieldname] = $fieldlabel.($obj->fieldrequired ? '*' : '');
				$import_extrafield_sample[$fieldname] = $fieldlabel;
			}
		}
		// End add extra fields
		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'facture');
		$this->import_regex_array[$r] = array('f.multicurrency_code' => 'code@'.MAIN_DB_PREFIX.'multicurrency');
		$import_sample = array(
			'f.ref' => '(PROV0001)',
			'f.ref_ext' => '',
			'f.ref_client' => '',
			'f.type' => '0',
			'f.fk_soc' => '80LIMIT',
			'f.datec' => '2021-11-24',
			'f.datef' => '2021-11-24',
			'f.date_valid' => '2021-11-24',
			'f.paye' => '1',
			'f.total_tva' => '21',
			'f.total_ht' => '100',
			'f.total_ttc' => '121',
			'f.fk_statut' => '1',
			'f.fk_user_modif' => '',
			'f.fk_user_valid' => '',
			'f.fk_user_closing' => '',
			'f.fk_facture_source' => '',
			'f.fk_projet' => '',
			'f.fk_account' => '',
			'f.fk_currency' => 'EUR',
			'f.fk_cond_reglement' => '30D',
			'f.fk_mode_reglement' => 'VIR',
			'f.date_lim_reglement' => '2021-12-24',
			'f.note_public' => '',
			'f.note_private' => '',
			'f.model_pdf' => 'sponge',
			'f.multicurrency_code' => 'EUR',
			'f.multicurrency_tx' => '1',
			'f.multicurrency_total_ht' => '100',
			'f.multicurrency_total_tva' => '21',
			'f.multicurrency_total_ttc' => '121'
		);
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = array('f.ref' => 'Ref');
		$this->import_convertvalue_array[$r] = array(
			'f.fk_soc' => array(
				'rule' => 'fetchidfromref',
				'file' => '/societe/class/societe.class.php',
				'class' => 'Societe',
				'method' => 'fetch',
				'element' => 'ThirdParty'
			),
			'f.fk_projet' => array(
				'rule' => 'fetchidfromref',
				'file' => '/projet/class/project.class.php',
				'class' => 'Project',
				'method' => 'fetch',
				'element' => 'facture'
			),
			'f.fk_cond_reglement' => array(
				'rule' => 'fetchidfromcodeorlabel',
				'file' => '/compta/facture/class/paymentterm.class.php',
				'class' => 'PaymentTerm',
				'method' => 'fetch',
				'element' => 'c_payment_term'
			)
		);

		// Import Invoice Lines
		$r++;
		$this->import_code[$r] = $this->rights_class.'_'.$r;
		$this->import_label[$r] = "InvoiceLine"; // Translation key
		$this->import_icon[$r] = $this->picto;
		$this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r] = array('fd' => MAIN_DB_PREFIX.'facturedet', 'extra' => MAIN_DB_PREFIX.'facturedet_extrafields');
		$this->import_fields_array[$r] = array(
			'fd.fk_facture' => 'InvoiceRef*',
			'fd.fk_parent_line' => 'FacParentLine',
			'fd.fk_product' => 'ProductOrService',
			'fd.label' => 'Label',
			'fd.description' => 'LineDescription*',
			'fd.vat_src_code' => 'Vat Source Code',
			'fd.tva_tx' => 'LineVATRate*',
			// localtax1_tx
			// localtax1_type
			// localtax2_tx
			// localtax2_type
			'fd.qty' => 'LineQty',
			'fd.remise_percent' => 'Reduc. (%)',
			// remise
			// fk_remise_except
			'fd.subprice' => 'UnitPriceHT',
			// price
			'fd.total_ht' => 'LineTotalHT',
			'fd.total_tva' => 'LineTotalVAT',
			// total_localtax1
			// total_localtax2
			'fd.total_ttc' => 'LineTotalTTC',
			'fd.product_type' => 'TypeOfLineServiceOrProduct',
			'fd.date_start' => 'Start Date',
			'fd.date_end' => 'End Date',
			// info_bits
			// buy_price_ht
			// fk_product_fournisseur_price
			// specia_code
			// rang
			// fk_contract_line
			'fd.fk_unit' => 'Unit',
			// fk_code_ventilation
			// situation_percent
			// fk_prev_id
			// fk_user_author
			// fk_user_modif
			// ref_ext
		);
		if (isModEnabled("multicurrency")) {
			$this->import_fields_array[$r]['fd.multicurrency_code'] = 'Currency';
			$this->import_fields_array[$r]['fd.multicurrency_subprice'] = 'CurrencyRate';
			$this->import_fields_array[$r]['fd.multicurrency_total_ht'] = 'MulticurrencyAmountHT';
			$this->import_fields_array[$r]['fd.multicurrency_total_tva'] = 'MulticurrencyAmountVAT';
			$this->import_fields_array[$r]['fd.multicurrency_total_ttc'] = 'MulticurrencyAmountTTC';
		}
		// Add extra fields
		$import_extrafield_sample = array();
		$sql = "SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'facture_det' AND entity IN (0, ".$conf->entity.")";
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$fieldname = 'extra.'.$obj->name;
				$fieldlabel = ucfirst($obj->label);
				$this->import_fields_array[$r][$fieldname] = $fieldlabel.($obj->fieldrequired ? '*' : '');
				$import_extrafield_sample[$fieldname] = $fieldlabel;
			}
		}
		// End add extra fields
		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'facturedet');
		$this->import_regex_array[$r] = array(
			'fd.multicurrency_code' => 'code@'.MAIN_DB_PREFIX.'multicurrency'
		);
		$import_sample = array(
			'fd.fk_facture' => '(PROV00001)',
			'fd.fk_parent_line' => '',
			'fd.fk_product' => 'ref:PRODUCT_REF or id:123456',
			'fd.label' => '',
			'fd.description' => 'Test product',
			'fd.vat_src_code' => '',
			'fd.tva_tx' => '21',
			// localtax1_tx
			// localtax1_type
			// localtax2_tx
			// localtax2_type
			'fd.qty' => '1',
			'fd.remise_percent' => '0',
			// remise
			// fk_remise_except
			'fd.subprice' => '100',
			// price
			'fd.total_ht' => '100',
			'fd.total_tva' => '21',
			// total_localtax1
			// total_localtax2
			'fd.total_ttc' => '121',
			'fd.product_type' => '0',
			'fd.date_start' => '',
			'fd.date_end' => '',
			// info_bits
			// buy_price_ht
			// fk_product_fournisseur_price
			// specia_code
			// rang
			// fk_contract_line
			'fd.fk_unit' => '',
			// fk_code_ventilation
			// situation_percent
			// fk_prev_id
			// fk_user_author
			// fk_user_modif
			// ref_ext
			'fd.multicurrency_code' => 'EUR',
			'fd.multicurrency_tx' => '21',
			'fd.multicurrency_total_ht' => '100',
			'fd.multicurrency_total_tva' => '21',
			'fd.multicurrency_total_ttc' => '121'
		);
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = array(
			'fd.rowid' => 'Row Id',
			'fd.fk_facture' => 'Invoice Id',
			'fd.fk_product'=> 'ProductRef'
		);
		$this->import_convertvalue_array[$r] = array(
			'fd.fk_facture' => array(
				'rule' => 'fetchidfromref',
				'file' => '/compta/facture/class/facture.class.php',
				'class' => 'Facture',
				'method' => 'fetch',
				'element' => 'facture'
			),
			'fd.fk_product' => array(
				'rule'=>'fetchidfromref',
				'classfile'=>'/product/class/product.class.php',
				'class'=>'Product',
				'method'=>'fetch',
				'element'=>'Product'
			),
			'fd.fk_projet' => array(
				'rule' => 'fetchidfromref',
				'file' => '/projet/class/project.class.php',
				'class' => 'Project',
				'method' => 'fetch',
				'element' => 'facture'
			),
		);


		// Exports
		//--------
		$uselocaltax1 = (is_object($mysoc) && $mysoc->localtax1_assuj) ? $mysoc->localtax1_assuj : 0;
		$uselocaltax2 = (is_object($mysoc) && $mysoc->localtax2_assuj) ? $mysoc->localtax2_assuj : 0;

		$r = 0;

		$langs->loadLangs(array("suppliers", "multicurrency", "bills"));

		$uselocaltax1 = $mysoc->localtax1_assuj ?? 0;
		$uselocaltax2 = $mysoc->localtax2_assuj ?? 0;

		$alias_product_perentity = !getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED') ? "p" : "ppe";

		// Invoices and lines
		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'CustomersInvoicesAndInvoiceLines'; // Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r] = 'invoice';
		$this->export_permission[$r] = array(array("facture", "facture", "export", "other"));

		$this->export_fields_array[$r] = array(
			's.rowid' => "IdCompany", 's.nom' => 'CompanyName', 'ps.nom' => 'ParentCompany', 's.code_client' => 'CustomerCode', 's.address' => 'Address', 's.zip' => 'Zip', 's.town' => 'Town', 'c.code' => 'CountryCode', 'cd.nom' => 'State',
			's.phone' => 'Phone',
			's.siren' => 'ProfId1', 's.siret' => 'ProfId2', 's.ape' => 'ProfId3', 's.idprof4' => 'ProfId4',
			's.code_compta' => 'CustomerAccountancyCode',
			's.code_compta_fournisseur' => 'SupplierAccountancyCode',
			's.tva_intra' => 'VATIntra',
			't.libelle' => "ThirdPartyType", // 'ce.code'=>"Staff", "cfj.libelle"=>"JuridicalStatus",
			'f.rowid' => "InvoiceId", 'f.ref' => "InvoiceRef", 'f.ref_client' => 'RefCustomer', 'f.fk_facture_source' => 'SourceInvoiceId',
			'f.type' => "Type", 'f.datec' => "InvoiceDateCreation", 'f.datef' => "DateInvoice", 'f.date_lim_reglement' => "DateDue",
			'f.fk_cond_reglement' => 'IdPaymentTerm', 'f.fk_mode_reglement' => 'IdPaymentMode',
			'f.total_ht' => "TotalHT", 'f.total_ttc' => "TotalTTC", 'f.total_tva' => "TotalVAT",
			'f.localtax1' => "TotalLT1", 'f.localtax2' => "TotalLT2",
			'f.paye' => "InvoicePaidCompletely", 'f.fk_statut' => 'InvoiceStatus', 'f.close_code' => 'EarlyClosingReason', 'f.close_note' => 'EarlyClosingComment',
			'none.rest' => 'Rest',
			'f.note_private' => "NotePrivate", 'f.note_public' => "NotePublic"
		);
		if (!$uselocaltax1) {
			unset($this->export_fields_array[$r]['f.localtax1']);
		}
		if (!$uselocaltax2) {
			unset($this->export_fields_array[$r]['f.localtax2']);
		}

		// Add multicurrency fields
		if (isModEnabled("multicurrency")) {
			$this->export_fields_array[$r]['f.multicurrency_code'] = 'Currency';
			$this->export_fields_array[$r]['f.multicurrency_tx'] = 'CurrencyRate';
			$this->export_fields_array[$r]['f.multicurrency_total_ht'] = 'MulticurrencyAmountHT';
			$this->export_fields_array[$r]['f.multicurrency_total_tva'] = 'MulticurrencyAmountVAT';
			$this->export_fields_array[$r]['f.multicurrency_total_ttc'] = 'MulticurrencyAmountTTC';
		}
		// Add POS fields
		if (!empty($conf->cashdesk->enabled) || !empty($conf->takepos->enabled) || getDolGlobalString('INVOICE_SHOW_POS')) {
			$this->export_fields_array[$r]['f.module_source'] = 'Module';
			$this->export_fields_array[$r]['f.pos_source'] = 'POSTerminal';
		}
		$this->export_fields_array[$r] += array(
			'f.fk_user_author' => 'CreatedById', 'uc.login' => 'CreatedByLogin',
			'f.fk_user_valid' => 'ValidatedById', 'uv.login' => 'ValidatedByLogin',
			'pj.ref' => 'ProjectRef', 'pj.title' => 'ProjectLabel'
		);
		// Add multicompany field
		if (getDolGlobalString('MULTICOMPANY_ENTITY_IN_EXPORT_IF_SHARED')) {
			$nbofallowedentities = count(explode(',', getEntity('invoice')));
			if (isModEnabled('multicompany') && $nbofallowedentities > 1) {
				$this->export_fields_array[$r]['f.entity'] = 'Entity';
			}
		}
		$this->export_fields_array[$r] += array(
			'fd.rowid' => 'LineId', 'fd.description' => "LineDescription",
			'fd.subprice' => "LineUnitPrice", 'fd.qty' => "LineQty",
			'fd.tva_tx' => "LineVATRate",
			'fd.total_ht' => "LineTotalHT", 'fd.total_tva' => "LineTotalVAT", 'fd.total_ttc' => "LineTotalTTC",
			'fd.localtax1_tx' => "LineLT1Rate", 'fd.localtax1_type' => "LineLT1Type", 'fd.total_localtax1' => "LineTotalLT1",
			'fd.localtax2_tx' => "LineLT2Rate", 'fd.localtax2_type' => "LineLT2Type", 'fd.total_localtax2' => "LineTotalLT2",
			'fd.buy_price_ht' => 'BuyingPrice', 'fd.date_start' => "DateStart", 'fd.date_end' => "DateEnd", 'fd.special_code' => 'SpecialCode',
			'fd.product_type' => "TypeOfLineServiceOrProduct", 'fd.fk_product' => 'ProductId', 'p.ref' => 'ProductRef', 'p.label' => 'ProductLabel',
			$alias_product_perentity . '.accountancy_code_sell' => 'ProductAccountancySellCode',
			'aa.account_number' => 'AccountingAffectation'
		);
		if (!$uselocaltax1) {
			unset($this->export_fields_array[$r]['fd.localtax1_tx']);
			unset($this->export_fields_array[$r]['fd.localtax1_type']);
			unset($this->export_fields_array[$r]['fd.total_localtax1']);
		}
		if (!$uselocaltax2) {
			unset($this->export_fields_array[$r]['fd.localtax2_tx']);
			unset($this->export_fields_array[$r]['fd.localtax2_type']);
			unset($this->export_fields_array[$r]['fd.total_localtax2']);
		}

		$this->export_TypeFields_array[$r] = array(
			's.rowid' => 'Numeric', 's.nom' => 'Text', 'ps.nom' => 'Text', 's.code_client' => 'Text', 's.address' => 'Text', 's.zip' => 'Text', 's.town' => 'Text', 'c.code' => 'Text', 'cd.nom' => 'Text', 's.phone' => 'Text', 's.siren' => 'Text',
			's.siret' => 'Text', 's.ape' => 'Text', 's.idprof4' => 'Text', 's.code_compta' => 'Text', 's.code_compta_fournisseur' => 'Text', 's.tva_intra' => 'Text',
			't.libelle' => "Text", // 'ce.code'=>"List:c_effectif:libelle:code", "cfj.libelle"=>"Text",
			'f.rowid' => 'Numeric', 'f.ref' => "Text", 'f.ref_client' => 'Text', 'f.fk_facture_source' => 'Numeric', 'f.type' => "Numeric", 'f.datec' => "Date", 'f.datef' => "Date", 'f.date_lim_reglement' => "Date",
			'f.fk_cond_reglement' => 'Numeric', 'f.fk_mode_reglement' => 'Numeric',
			'f.total_ht' => "Numeric", 'f.total_ttc' => "Numeric", 'f.total_tva' => "Numeric", 'f.localtax1' => 'Numeric', 'f.localtax2' => 'Numeric', 'f.paye' => "Boolean", 'f.fk_statut' => 'Numeric', 'f.close_code' => 'Text', 'f.close_note' => 'Text',
			'none.rest' => "NumericCompute",
			'f.note_private' => "Text", 'f.note_public' => "Text",
			'f.module_source' => 'Text',
			'f.pos_source' => 'Text',
			'f.entity' => 'List:entity:label:rowid',
			'f.fk_user_author' => 'Numeric', 'uc.login' => 'Text', 'f.fk_user_valid' => 'Numeric', 'uv.login' => 'Text',
			'pj.ref' => 'Text', 'pj.title' => 'Text', 'fd.rowid' => 'Numeric', 'fd.description' => "Text", 'fd.subprice' => "Numeric", 'fd.tva_tx' => "Numeric",
			'fd.qty' => "Numeric", 'fd.buy_price_ht' => "Numeric", 'fd.date_start' => "Date", 'fd.date_end' => "Date",
			'fd.total_ht' => "Numeric", 'fd.total_tva' => "Numeric", 'fd.total_ttc' => "Numeric", 'fd.total_localtax1' => "Numeric", 'fd.total_localtax2' => "Numeric",
			'fd.localtax1_tx' => 'Numeric', 'fd.localtax2_tx' => 'Numeric', 'fd.localtax1_type' => 'Numeric', 'fd.localtax2_type' => 'Numeric',
			'fd.special_code' => 'Numeric', 'fd.product_type' => "Numeric", 'fd.fk_product' => 'List:product:label', 'p.ref' => 'Text', 'p.label' => 'Text',
			$alias_product_perentity . '.accountancy_code_sell' => 'Text',
			'aa.account_number' => 'Text',
			'f.multicurrency_code' => 'Text',
			'f.multicurrency_tx' => 'Number', 'f.multicurrency_total_ht' => 'Number', 'f.multicurrency_total_tva' => 'Number', 'f.multicurrency_total_ttc' => 'Number'
		);
		$this->export_entities_array[$r] = array(
			's.rowid' => "company", 's.nom' => 'company', 'ps.nom' => 'company', 's.code_client' => 'company', 's.address' => 'company', 's.zip' => 'company', 's.town' => 'company', 'c.code' => 'company', 'cd.nom' => 'company', 's.phone' => 'company',
			's.siren' => 'company', 's.siret' => 'company', 's.ape' => 'company', 's.idprof4' => 'company', 's.code_compta' => 'company', 's.code_compta_fournisseur' => 'company', 's.tva_intra' => 'company',
			't.libelle' => 'company', // 'ce.code'=>'company', 'cfj.libelle'=>'company'
			'pj.ref' => 'project', 'pj.title' => 'project', 'fd.rowid' => 'invoice_line', 'fd.description' => "invoice_line",
			'fd.subprice' => "invoice_line", 'fd.buy_price_ht' => 'invoice_line',
			'fd.total_ht' => "invoice_line", 'fd.total_tva' => "invoice_line", 'fd.total_ttc' => "invoice_line", 'fd.total_localtax1' => "invoice_line", 'fd.total_localtax2' => "invoice_line",
			'fd.tva_tx' => "invoice_line", 'fd.localtax1_tx' => "invoice_line", 'fd.localtax2_tx' => "invoice_line", 'fd.localtax1_type' => "invoice_line", 'fd.localtax2_type' => "invoice_line",
			'fd.qty' => "invoice_line", 'fd.date_start' => "invoice_line", 'fd.date_end' => "invoice_line", 'fd.special_code' => 'invoice_line',
			'fd.product_type' => 'invoice_line', 'fd.fk_product' => 'product', 'p.ref' => 'product', 'p.label' => 'product', $alias_product_perentity . '.accountancy_code_sell' => 'product',
			'f.fk_user_author' => 'user', 'uc.login' => 'user', 'f.fk_user_valid' => 'user', 'uv.login' => 'user',
			'aa.account_number' => "invoice_line",
		);
		$this->export_help_array[$r] = array('fd.buy_price_ht' => 'CostPriceUsage');
		$this->export_special_array[$r] = array('none.rest' => 'getRemainToPay');
		$this->export_dependencies_array[$r] = array('invoice_line' => 'fd.rowid', 'product' => 'fd.rowid', 'none.rest' => array('f.rowid', 'f.total_ttc', 'f.close_code')); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them
		$keyforselect = 'facture';
		$keyforelement = 'invoice';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$keyforselect = 'facturedet';
		$keyforelement = 'invoice_line';
		$keyforaliasextra = 'extra2';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$keyforselect = 'product';
		$keyforelement = 'product';
		$keyforaliasextra = 'extra3';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$keyforselect = 'societe';
		$keyforelement = 'company';
		$keyforaliasextra = 'extra4';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'societe as s';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe_extrafields as extra4 ON s.rowid = extra4.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as ps ON ps.rowid = s.parent';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_typent as t ON s.fk_typent = t.id';
		if (!empty($user) && !$user->hasRight('societe', 'client', 'voir')) {
			$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe_commerciaux as sc ON sc.fk_soc = s.rowid';
		}
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c on s.fk_pays = c.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_departements as cd on s.fk_departement = cd.rowid,';
		$this->export_sql_end[$r] .= ' '.MAIN_DB_PREFIX.'facture as f';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as pj ON f.fk_projet = pj.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as uc ON f.fk_user_author = uc.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as uv ON f.fk_user_valid = uv.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture_extrafields as extra ON f.rowid = extra.fk_object';
		$this->export_sql_end[$r] .= ' , '.MAIN_DB_PREFIX.'facturedet as fd';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facturedet_extrafields as extra2 on fd.rowid = extra2.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
			$this->export_sql_end[$r] .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_perentity as ppe ON ppe.fk_product = p.rowid AND ppe.entity = " . ((int) $conf->entity);
		}
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_extrafields as extra3 on p.rowid = extra3.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'accounting_account as aa on fd.fk_code_ventilation = aa.rowid';
		$this->export_sql_end[$r] .= ' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture';
		$this->export_sql_end[$r] .= ' AND f.entity IN ('.getEntity('invoice').')';
		if (!empty($user) && !$user->hasRight('societe', 'client', 'voir')) {
			$this->export_sql_end[$r] .= ' AND sc.fk_user = '.(empty($user) ? 0 : $user->id);
		}
		$r++;

		// Invoices and payments
		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'CustomersInvoicesAndPayments'; // Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r] = 'invoice';
		$this->export_permission[$r] = array(array("facture", "facture", "export"));
		$this->export_fields_array[$r] = array(
			's.rowid' => "IdCompany", 's.nom' => 'CompanyName', 's.code_client' => 'CustomerCode', 's.address' => 'Address', 's.zip' => 'Zip', 's.town' => 'Town', 'c.code' => 'CountryCode', 'cd.nom' => 'State',
			's.phone' => 'Phone',
			's.siren' => 'ProfId1', 's.siret' => 'ProfId2', 's.ape' => 'ProfId3', 's.idprof4' => 'ProfId4', 's.code_compta' => 'CustomerAccountancyCode',
			's.code_compta_fournisseur' => 'SupplierAccountancyCode', 's.tva_intra' => 'VATIntra',
			'f.rowid' => "InvoiceId", 'f.ref' => "InvoiceRef", 'f.ref_client' => 'RefCustomer', 'f.fk_facture_source' => 'SourceInvoiceId',
			'f.type' => "Type", 'f.datec' => "InvoiceDateCreation", 'f.datef' => "DateInvoice", 'f.date_lim_reglement' => "DateDue",
			'f.fk_cond_reglement' => 'IdPaymentTerm', 'f.fk_mode_reglement' => 'IdPaymentMode',
			'f.total_ht' => "TotalHT", 'f.total_ttc' => "TotalTTC", 'f.total_tva' => "TotalVAT", 'f.localtax1' => 'LT1', 'f.localtax2' => 'LT2', 'f.paye' => "InvoicePaidCompletely", 'f.fk_statut' => 'InvoiceStatus', 'f.close_code' => 'EarlyClosingReason', 'f.close_note' => 'EarlyClosingComment',
			'none.rest' => 'Rest',
			'f.note_private' => "NotePrivate", 'f.note_public' => "NotePublic", 'f.fk_user_author' => 'CreatedById', 'uc.login' => 'CreatedByLogin',
			'f.fk_user_valid' => 'ValidatedById', 'uv.login' => 'ValidatedByLogin', 'pj.ref' => 'ProjectRef', 'pj.title' => 'ProjectLabel', 'p.rowid' => 'PaymentId', 'p.ref' => 'PaymentRef',
			'p.amount' => 'AmountPayment', 'pf.amount' => 'AmountPaymentDistributedOnInvoice', 'p.datep' => 'DatePayment', 'p.num_paiement' => 'PaymentNumber',
			'pt.code' => 'CodePaymentMode', 'pt.libelle' => 'LabelPaymentMode', 'p.note' => 'PaymentNote', 'p.fk_bank' => 'IdTransaction', 'ba.ref' => 'AccountRef'
		);
		if (!$uselocaltax1) {
			unset($this->export_fields_array[$r]['f.localtax1']);
		}
		if (!$uselocaltax2) {
			unset($this->export_fields_array[$r]['f.localtax2']);
		}

		$this->export_help_array[$r] = array('f.paye' => 'InvoicePaidCompletelyHelp');
		if (isModEnabled("multicurrency")) {
			$this->export_fields_array[$r]['f.multicurrency_code'] = 'Currency';
			$this->export_fields_array[$r]['f.multicurrency_tx'] = 'CurrencyRate';
			$this->export_fields_array[$r]['f.multicurrency_total_ht'] = 'MulticurrencyAmountHT';
			$this->export_fields_array[$r]['f.multicurrency_total_tva'] = 'MulticurrencyAmountVAT';
			$this->export_fields_array[$r]['f.multicurrency_total_ttc'] = 'MulticurrencyAmountTTC';
			$this->export_examplevalues_array[$r]['f.multicurrency_code'] = 'EUR';
		}
		if (!empty($conf->cashdesk->enabled) || !empty($conf->takepos->enabled) || getDolGlobalString('INVOICE_SHOW_POS')) {
			$this->export_fields_array[$r]['f.module_source'] = 'POSModule';
			$this->export_fields_array[$r]['f.pos_source'] = 'POSTerminal';
		}
		$this->export_TypeFields_array[$r] = array(
			's.rowid' => 'Numeric', 's.nom' => 'Text', 's.code_client' => 'Text', 's.address' => 'Text', 's.zip' => 'Text', 's.town' => 'Text', 'c.code' => 'Text', 'cd.nom' => 'Text', 's.phone' => 'Text', 's.siren' => 'Text',
			's.siret' => 'Text', 's.ape' => 'Text', 's.idprof4' => 'Text', 's.code_compta' => 'Text', 's.code_compta_fournisseur' => 'Text', 's.tva_intra' => 'Text',
			'f.rowid' => "Numeric", 'f.ref' => "Text", 'f.ref_client' => 'Text', 'f.fk_facture_source' => 'Numeric', 'f.type' => "Numeric", 'f.datec' => "Date", 'f.datef' => "Date", 'f.date_lim_reglement' => "Date",
			'f.fk_cond_reglement' => 'Numeric', 'f.fk_mode_reglement' => 'Numeric',
			'f.total_ht' => "Numeric", 'f.total_ttc' => "Numeric", 'f.total_tva' => "Numeric", 'f.localtax1' => 'Numeric', 'f.localtax2' => 'Numeric', 'f.paye' => "Boolean", 'f.fk_statut' => 'Status', 'f.close_code' => 'Text', 'f.close_note' => 'Text',
			'none.rest' => 'NumericCompute',
			'f.note_private' => "Text", 'f.note_public' => "Text", 'f.fk_user_author' => 'Numeric', 'uc.login' => 'Text', 'f.fk_user_valid' => 'Numeric', 'uv.login' => 'Text',
			'pj.ref' => 'Text', 'pj.title' => 'Text', 'p.amount' => 'Numeric', 'pf.amount' => 'Numeric', 'p.rowid' => 'Numeric', 'p.ref' => 'Text', 'p.title' => 'Text', 'p.datep' => 'Date', 'p.num_paiement' => 'Numeric',
			'p.fk_bank' => 'Numeric', 'p.note' => 'Text', 'pt.code' => 'Text', 'pt.libelle' => 'Text', 'ba.ref' => 'Text'
		);
		if (!empty($conf->cashdesk->enabled) || !empty($conf->takepos->enabled) || getDolGlobalString('INVOICE_SHOW_POS')) {
			$this->export_fields_array[$r]['f.module_source'] = 'POSModule';
			$this->export_fields_array[$r]['f.pos_source'] = 'POSTerminal';
		}
		$this->export_entities_array[$r] = array(
			's.rowid' => "company", 's.nom' => 'company', 's.code_client' => 'company', 's.address' => 'company', 's.zip' => 'company', 's.town' => 'company', 'c.code' => 'company', 'cd.nom' => 'company', 's.phone' => 'company',
			's.siren' => 'company', 's.siret' => 'company', 's.ape' => 'company', 's.idprof4' => 'company', 's.code_compta' => 'company', 's.code_compta_fournisseur' => 'company',
			's.tva_intra' => 'company', 'pj.ref' => 'project', 'pj.title' => 'project', 'p.rowid' => 'payment', 'p.ref' => 'payment', 'p.amount' => 'payment', 'pf.amount' => 'payment', 'p.datep' => 'payment',
			'p.num_paiement' => 'payment', 'pt.code' => 'payment', 'pt.libelle' => 'payment', 'p.note' => 'payment', 'f.fk_user_author' => 'user', 'uc.login' => 'user',
			'f.fk_user_valid' => 'user', 'uv.login' => 'user', 'p.fk_bank' => 'account', 'ba.ref' => 'account'
		);
		$this->export_special_array[$r] = array('none.rest' => 'getRemainToPay');
		$this->export_dependencies_array[$r] = array('payment' => 'p.rowid', 'none.rest' => array('f.rowid', 'f.total_ttc', 'f.close_code')); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them, or just to have field we need
		$keyforselect = 'facture';
		$keyforelement = 'invoice';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'societe as s';
		if (!empty($user) && !$user->hasRight('societe', 'client', 'voir')) {
			$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe_commerciaux as sc ON sc.fk_soc = s.rowid';
		}
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c on s.fk_pays = c.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_departements as cd on s.fk_departement = cd.rowid,';
		$this->export_sql_end[$r] .= ' '.MAIN_DB_PREFIX.'facture as f';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as pj ON f.fk_projet = pj.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as uc ON f.fk_user_author = uc.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as uv ON f.fk_user_valid = uv.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture_extrafields as extra ON f.rowid = extra.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiement as p ON pf.fk_paiement = p.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as pt ON pt.id = p.fk_paiement';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON b.rowid = p.fk_bank';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON ba.rowid = b.fk_account';
		$this->export_sql_end[$r] .= ' WHERE f.fk_soc = s.rowid';
		$this->export_sql_end[$r] .= ' AND f.entity IN ('.getEntity('invoice').')';
		if (!empty($user) && !$user->hasRight('societe', 'client', 'voir')) {
			$this->export_sql_end[$r] .= ' AND sc.fk_user = '.(empty($user) ? 0 : $user->id);
		}
		$r++;
	}


	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string	$options    Options when enabling module ('', 'newboxdefonly', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		// Remove permissions and default values
		$this->remove($options);

		//ODT template
		$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/invoices/template_invoice.odt';
		$dirodt = DOL_DATA_ROOT.'/doctemplates/invoices';
		$dest = $dirodt.'/template_invoice.odt';

		if (file_exists($src) && !file_exists($dest)) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_mkdir($dirodt);
			$result = dol_copy($src, $dest, '0', 0);
			if ($result < 0) {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
				return 0;
			}
		}

		$sql = array(
			"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[1][2])."' AND type = 'invoice' AND entity = ".((int) $conf->entity),
			"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[1][2])."','invoice',".((int) $conf->entity).")"
		);

		return $this->_init($sql, $options);
	}
}
