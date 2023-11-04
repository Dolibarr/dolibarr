<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012      Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013-2015 Philippe Grand	    <philippe.grand@atoo-net.com>
 * Copyright (C) 2020      Ahmad Jamaly Rabib   <rabib@metroworks.co.jp>
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
 * 		\defgroup   fournisseur     Module suppliers
 *		\file       htdocs/core/modules/modFournisseur.class.php
 *		\ingroup    fournisseur
 *		\brief      Description and activation file for the module Supplier
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *	Description and activation class for module Fournisseur
 */
class modFournisseur extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $user;

		$this->db = $db;
		$this->numero = 40;

		// Family can be 'crm','financial','hr','projects','product','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "srm";
		$this->module_position = '12';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Gestion des fournisseurs";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'company';

		// Data directories to create when module is enabled
		$this->dirs = array(
			"/fournisseur/temp",
			"/fournisseur/commande",
			"/fournisseur/commande/temp",
			"/fournisseur/facture",
			"/fournisseur/facture/temp"
		);

		// Dependencies
		$this->depends = array("modSociete");
		$this->requiredby = array("modSupplierProposal");
		$this->langfiles = array('bills', 'companies', 'suppliers', 'orders', 'sendings');

		// Config pages
		$this->config_page_url = array("supplier_order.php");

		// Constants
		$this->const = array();
		$r = 0;

		$this->const[$r][0] = "COMMANDE_SUPPLIER_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "muscadet";
		$this->const[$r][3] = 'Nom du gestionnaire de generation des bons de commande en PDF';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "COMMANDE_SUPPLIER_ADDON_NUMBER";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_commande_fournisseur_muguet";
		$this->const[$r][3] = 'Nom du gestionnaire de numerotation des commandes fournisseur';
		$this->const[$r][4] = 0;
		$r++;

		/* For supplier invoice, we must not have default pdf template on. In most cases, we need to join PDF from supplier, not have a document generated.
		$this->const[$r][0] = "INVOICE_SUPPLIER_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "canelle";
		$this->const[$r][3] = 'Nom du gestionnaire de generation des factures fournisseur en PDF';
		$this->const[$r][4] = 0;
		$r++;
		*/

		$this->const[$r][0] = "INVOICE_SUPPLIER_ADDON_NUMBER";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_facture_fournisseur_cactus";
		$this->const[$r][3] = 'Nom du gestionnaire de numerotation des factures fournisseur';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SUPPLIER_ORDER_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/supplier_orders";
		$this->const[$r][3] = '';
		$this->const[$r][4] = 0;
		$r++;

		// Boxes
		$this->boxes = array(
			0=>array('file'=>'box_graph_invoices_supplier_permonth.php', 'enabledbydefaulton'=>'Home'),
			1=>array('file'=>'box_graph_orders_supplier_permonth.php', 'enabledbydefaulton'=>'Home'),
			2=>array('file'=>'box_fournisseurs.php', 'enabledbydefaulton'=>'Home'),
			3=>array('file'=>'box_factures_fourn_imp.php', 'enabledbydefaulton'=>'Home'),
			4=>array('file'=>'box_factures_fourn.php', 'enabledbydefaulton'=>'Home'),
			5=>array('file'=>'box_supplier_orders.php', 'enabledbydefaulton'=>'Home'),
			6=>array('file'=>'box_supplier_orders_awaiting_reception.php', 'enabledbydefaulton'=>'Home'),
		);

		// Permissions
		$this->rights = array();
		$this->rights_class = 'fournisseur';
		$r = 0;

		$r++;
		$this->rights[$r][0] = 1181;
		$this->rights[$r][1] = 'Consulter les fournisseurs';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 1182;
		$this->rights[$r][1] = 'Consulter les commandes fournisseur';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'commande';
		$this->rights[$r][5] = 'lire';

		$r++;
		$this->rights[$r][0] = 1183;
		$this->rights[$r][1] = 'Creer une commande fournisseur';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'commande';
		$this->rights[$r][5] = 'creer';

		$r++;
		$this->rights[$r][0] = 1184;
		$this->rights[$r][1] = 'Valider une commande fournisseur';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supplier_order_advance';
		$this->rights[$r][5] = 'validate';

		$r++;
		$this->rights[$r][0] = 1185;
		$this->rights[$r][1] = 'Approuver une commande fournisseur';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'commande';
		$this->rights[$r][5] = 'approuver';

		$r++;
		$this->rights[$r][0] = 1186;
		$this->rights[$r][1] = 'Commander une commande fournisseur';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'commande';
		$this->rights[$r][5] = 'commander';

		$r++;
		$this->rights[$r][0] = 1187;
		$this->rights[$r][1] = 'Receptionner une commande fournisseur';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'commande';
		$this->rights[$r][5] = 'receptionner';

		$r++;
		$this->rights[$r][0] = 1189;
		$this->rights[$r][1] = 'Check/Uncheck a supplier order reception';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'commande_advance';
		$this->rights[$r][5] = 'check';

		$r++;
		$this->rights[$r][0] = 1188;
		$this->rights[$r][1] = 'Supprimer une commande fournisseur';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'commande';
		$this->rights[$r][5] = 'supprimer';

		if (!empty($conf->global->SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED)) {
			$r++;
			$this->rights[$r][0] = 1190;
			$this->rights[$r][1] = 'Approve supplier order (second level)'; // $langs->trans("Permission1190");
			$this->rights[$r][2] = 'w';
			$this->rights[$r][3] = 0;
			$this->rights[$r][4] = 'commande';
			$this->rights[$r][5] = 'approve2';
		}

		$r++;
		$this->rights[$r][0] = 1191;
		$this->rights[$r][1] = 'Exporter les commande fournisseurs, attributs';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'commande';
		$this->rights[$r][5] = 'export';

		$r++;
		$this->rights[$r][0] = 1231;
		$this->rights[$r][1] = 'Consulter les factures fournisseur';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'facture';
		$this->rights[$r][5] = 'lire';

		$r++;
		$this->rights[$r][0] = 1232;
		$this->rights[$r][1] = 'Creer une facture fournisseur';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'facture';
		$this->rights[$r][5] = 'creer';

		$r++;
		$this->rights[$r][0] = 1233;
		$this->rights[$r][1] = 'Valider une facture fournisseur';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supplier_invoice_advance';
		$this->rights[$r][5] = 'validate';

		$r++;
		$this->rights[$r][0] = 1234;
		$this->rights[$r][1] = 'Supprimer une facture fournisseur';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'facture';
		$this->rights[$r][5] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 1235;
		$this->rights[$r][1] = 'Envoyer les factures par mail';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supplier_invoice_advance';
		$this->rights[$r][5] = 'send';

		$r++;
		$this->rights[$r][0] = 1236;
		$this->rights[$r][1] = 'Exporter les factures fournisseurs, attributs et reglements';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'facture';
		$this->rights[$r][5] = 'export';


		// Menus
		//-------
		$this->menu = 1; // This module add menu entries. They are coded into menu manager.


		// Exports
		//--------
		$r = 0;

		$r++;
		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'Vendor invoices and lines of invoices';
		$this->export_icon[$r] = 'invoice';
		$this->export_permission[$r] = array(array("fournisseur", "facture", "export"));
		$this->export_fields_array[$r] = array(
			's.rowid'=>"IdCompany", 's.nom'=>'CompanyName', 'ps.nom'=>'ParentCompany', 's.address'=>'Address', 's.zip'=>'Zip', 's.town'=>'Town', 'c.code'=>'CountryCode', 's.phone'=>'Phone',
			's.siren'=>'ProfId1', 's.siret'=>'ProfId2', 's.ape'=>'ProfId3', 's.idprof4'=>'ProfId4', 's.idprof5'=>'ProfId5', 's.idprof6'=>'ProfId6',
			's.code_compta'=>'CustomerAccountancyCode', 's.code_compta_fournisseur'=>'SupplierAccountancyCode', 's.tva_intra'=>'VATIntra',
			'f.rowid'=>"InvoiceId", 'f.ref'=>"InvoiceRef", 'f.ref_supplier'=>"RefSupplier", 'f.datec'=>"InvoiceDateCreation", 'f.datef'=>"DateInvoice", 'f.date_lim_reglement'=>'DateMaxPayment',
			'f.total_ht'=>"TotalHT", 'f.total_ttc'=>"TotalTTC", 'f.total_tva'=>"TotalVAT", 'f.paye'=>"InvoicePaid", 'f.fk_statut'=>'InvoiceStatus', 'f.note_public'=>"InvoiceNote",
			'fd.rowid'=>'LineId', 'fd.description'=>"LineDescription", 'fd.tva_tx'=>"LineVATRate", 'fd.qty'=>"LineQty", 'fd.remise_percent'=>"Discount", 'fd.total_ht'=>"LineTotalHT",
			'fd.total_ttc'=>"LineTotalTTC", 'fd.tva'=>"LineTotalVAT", 'fd.product_type'=>'TypeOfLineServiceOrProduct', 'fd.fk_product'=>'ProductId',
			'p.ref'=>'ProductRef', 'p.label'=>'ProductLabel', 'p.accountancy_code_buy'=>'ProductAccountancyBuyCode', 'project.rowid'=>'ProjectId',
			'project.ref'=>'ProjectRef', 'project.title'=>'ProjectLabel'
		);
		if (!empty($conf->multicurrency->enabled)) {
			$this->export_fields_array[$r]['f.multicurrency_code'] = 'Currency';
			$this->export_fields_array[$r]['f.multicurrency_tx'] = 'CurrencyRate';
			$this->export_fields_array[$r]['f.multicurrency_total_ht'] = 'MulticurrencyAmountHT';
			$this->export_fields_array[$r]['f.multicurrency_total_tva'] = 'MulticurrencyAmountVAT';
			$this->export_fields_array[$r]['f.multicurrency_total_ttc'] = 'MulticurrencyAmountTTC';
		}
		//$this->export_TypeFields_array[$r]=array(
		//    's.rowid'=>"List:societe:CompanyName",'s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','c.code'=>'Text','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text',
		//    's.ape'=>'Text','s.idprof4'=>'Text','s.tva_intra'=>'Text','f.ref'=>"Text",'f.datec'=>"Date",'f.datef'=>"Date",'f.total_ht'=>"Numeric",'f.total_ttc'=>"Numeric",'f.total_tva'=>"Numeric",
		//    'f.paye'=>"Boolean",'f.fk_statut'=>'Status','f.note_public'=>"Text",'fd.description'=>"Text",'fd.tva_tx'=>"Text",'fd.qty'=>"Numeric",'fd.total_ht'=>"Numeric",'fd.total_ttc'=>"Numeric",
		//     'fd.tva'=>"Numeric",'fd.product_type'=>'Numeric','fd.fk_product'=>'List:product:label','p.ref'=>'Text','p.label'=>'Text'
		//);
		$this->export_TypeFields_array[$r] = array(
			's.nom'=>'Text', 'ps.nom'=>'Text', 's.address'=>'Text', 's.zip'=>'Text', 's.town'=>'Text', 'c.code'=>'Text', 's.phone'=>'Text', 's.siren'=>'Text', 's.siret'=>'Text', 's.ape'=>'Text', 's.idprof4'=>'Text', 's.idprof5'=>'Text', 's.idprof6'=>'Text',
			's.code_compta'=>'Text', 's.code_compta_fournisseur'=>'Text', 's.tva_intra'=>'Text', 'f.ref'=>"Text", 'f.ref_supplier'=>"Text", 'f.datec'=>"Date", 'f.datef'=>"Date", 'f.date_lim_reglement'=>'Date',
			'f.total_ht'=>"Numeric", 'f.total_ttc'=>"Numeric", 'f.total_tva'=>"Numeric", 'f.paye'=>"Boolean", 'f.fk_statut'=>'Status', 'f.note_public'=>"Text", 'fd.description'=>"Text", 'fd.tva_tx'=>"Text",
			'fd.qty'=>"Numeric", 'fd.total_ht'=>"Numeric", 'fd.total_ttc'=>"Numeric", 'fd.tva'=>"Numeric", 'fd.product_type'=>'Numeric', 'fd.fk_product'=>'List:product:label',
			'p.ref'=>'Text', 'p.label'=>'Text', 'project.ref'=>'Text', 'project.title'=>'Text'
		);
		$this->export_entities_array[$r] = array(
			's.rowid'=>"company", 's.nom'=>'company', 'ps.nom'=>'company', 's.address'=>'company', 's.zip'=>'company', 's.town'=>'company', 'c.code'=>'company', 's.phone'=>'company', 's.siren'=>'company', 's.siret'=>'company',
			's.ape'=>'company', 's.idprof4'=>'company', 's.idprof5'=>'company', 's.idprof6'=>'company', 's.code_compta'=>'company', 's.code_compta_fournisseur'=>'company', 's.tva_intra'=>'company', 'f.rowid'=>"invoice",
			'f.ref'=>"invoice", 'f.ref_supplier'=>"invoice", 'f.datec'=>"invoice", 'f.datef'=>"invoice", 'f.date_lim_reglement'=>'invoice', 'f.total_ht'=>"invoice", 'f.total_ttc'=>"invoice", 'f.total_tva'=>"invoice",
			'f.paye'=>"invoice", 'f.fk_statut'=>'invoice', 'f.note_public'=>"invoice", 'fd.rowid'=>'invoice_line', 'fd.description'=>"invoice_line", 'fd.tva_tx'=>"invoice_line", 'fd.qty'=>"invoice_line",
			'fd.remise_percent'=>"invoice_line", 'fd.total_ht'=>"invoice_line", 'fd.total_ttc'=>"invoice_line", 'fd.tva'=>"invoice_line", 'fd.product_type'=>'invoice_line', 'fd.fk_product'=>'product',
			'p.ref'=>'product', 'p.label'=>'product', 'p.accountancy_code_buy'=>'product', 'project.rowid'=>'project', 'project.ref'=>'project', 'project.title'=>'project'
		);
		$this->export_dependencies_array[$r] = array('invoice_line'=>'fd.rowid', 'product'=>'fd.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them
		// Add extra fields object
		$keyforselect = 'facture_fourn';
		$keyforelement = 'invoice';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$keyforselect = 'facture_fourn_det';
		$keyforelement = 'invoice_line';
		$keyforaliasextra = 'extraline';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		// End add extra fields line
		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'societe as s';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as ps ON ps.rowid = s.parent';
		if (is_object($user) && empty($user->rights->societe->client->voir)) {
			$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe_commerciaux as sc ON sc.fk_soc = s.rowid';
		}
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON s.fk_pays = c.rowid,';
		$this->export_sql_end[$r] .= ' '.MAIN_DB_PREFIX.'facture_fourn as f';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as project on (f.fk_projet = project.rowid)';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn_extrafields as extra ON f.rowid = extra.fk_object';
		$this->export_sql_end[$r] .= ' , '.MAIN_DB_PREFIX.'facture_fourn_det as fd';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn_det_extrafields as extraline ON fd.rowid = extraline.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		$this->export_sql_end[$r] .= ' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture_fourn';
		$this->export_sql_end[$r] .= ' AND f.entity IN ('.getEntity('supplier_invoice').')';
		if (is_object($user) && empty($user->rights->societe->client->voir)) {
			$this->export_sql_end[$r] .= ' AND sc.fk_user = '.$user->id;
		}

		$r++;
		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'Factures fournisseurs et reglements';
		$this->export_icon[$r] = 'invoice';
		$this->export_permission[$r] = array(array("fournisseur", "facture", "export"));
		$this->export_fields_array[$r] = array(
			's.rowid'=>"IdCompany", 's.nom'=>'CompanyName', 's.address'=>'Address', 's.zip'=>'Zip', 's.town'=>'Town', 'c.code'=>'CountryCode', 's.phone'=>'Phone',
			's.siren'=>'ProfId1', 's.siret'=>'ProfId2', 's.ape'=>'ProfId3', 's.idprof4'=>'ProfId4', 's.idprof5'=>'ProfId5', 's.idprof6'=>'ProfId6',
			's.code_compta'=>'CustomerAccountancyCode', 's.code_compta_fournisseur'=>'SupplierAccountancyCode', 's.tva_intra'=>'VATIntra',
			'f.rowid'=>"InvoiceId", 'f.ref'=>"InvoiceRef", 'f.ref_supplier'=>"RefSupplier", 'f.datec'=>"InvoiceDateCreation",
			'f.datef'=>"DateInvoice", 'f.total_ht'=>"TotalHT", 'f.total_ttc'=>"TotalTTC", 'f.total_tva'=>"TotalVAT", 'f.paye'=>"InvoicePaid",
			'f.fk_statut'=>'InvoiceStatus', 'f.note_public'=>"InvoiceNote", 'p.rowid'=>'PaymentId', 'pf.amount'=>'AmountPayment',
			'p.datep'=>'DatePayment', 'p.num_paiement'=>'PaymentNumber', 'p.fk_bank'=>'IdTransaction', 'project.rowid'=>'ProjectId', 'project.ref'=>'ProjectRef', 'project.title'=>'ProjectLabel'
		);
		if (!empty($conf->multicurrency->enabled)) {
			$this->export_fields_array[$r]['f.multicurrency_code'] = 'Currency';
			$this->export_fields_array[$r]['f.multicurrency_tx'] = 'CurrencyRate';
			$this->export_fields_array[$r]['f.multicurrency_total_ht'] = 'MulticurrencyAmountHT';
			$this->export_fields_array[$r]['f.multicurrency_total_tva'] = 'MulticurrencyAmountVAT';
			$this->export_fields_array[$r]['f.multicurrency_total_ttc'] = 'MulticurrencyAmountTTC';
		}
		//$this->export_TypeFields_array[$r]=array(
		//	's.rowid'=>"List:societe:CompanyName",'s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','c.code'=>'Text','s.phone'=>'Text',
		//	's.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','s.tva_intra'=>'Text','f.ref'=>"Text",'f.datec'=>"Date",'f.datef'=>"Date",
		//	'f.total_ht'=>"Numeric",'f.total_ttc'=>"Numeric",'f.total_tva'=>"Numeric",'f.paye'=>"Boolean",'f.fk_statut'=>'Status','f.note_public'=>"Text",
		//	'pf.amount'=>'Numeric','p.datep'=>'Date','p.num_paiement'=>'Numeric'
		//);
		$this->export_TypeFields_array[$r] = array(
			's.nom'=>'Text', 's.address'=>'Text', 's.zip'=>'Text', 's.town'=>'Text', 'c.code'=>'Text', 's.phone'=>'Text', 's.siren'=>'Text', 's.siret'=>'Text', 's.ape'=>'Text',
			's.idprof4'=>'Text', 's.code_compta'=>'Text', 's.code_compta_fournisseur'=>'Text', 's.tva_intra'=>'Text', 'f.ref'=>"Text", 'f.ref_supplier'=>"Text", 'f.datec'=>"Date", 'f.datef'=>"Date", 'f.total_ht'=>"Numeric",
			'f.total_ttc'=>"Numeric", 'f.total_tva'=>"Numeric", 'f.paye'=>"Boolean", 'f.fk_statut'=>'Status', 'f.note_public'=>"Text", 'pf.amount'=>'Numeric',
			'p.datep'=>'Date', 'p.num_paiement'=>'Numeric', 'p.fk_bank'=>'Numeric', 'project.ref'=>'Text', 'project.title'=>'Text'
		);
		$this->export_entities_array[$r] = array(
			's.rowid'=>"company", 's.nom'=>'company', 's.address'=>'company', 's.zip'=>'company', 's.town'=>'company', 'c.code'=>'company', 's.phone'=>'company',
			's.siren'=>'company', 's.siret'=>'company', 's.ape'=>'company', 's.idprof4'=>'company', 's.idprof5'=>'company', 's.idprof6'=>'company',
			's.code_compta'=>'company', 's.code_compta_fournisseur'=>'company', 's.tva_intra'=>'company',
			'f.rowid'=>"invoice", 'f.ref'=>"invoice", 'f.ref_supplier'=>"invoice", 'f.datec'=>"invoice", 'f.datef'=>"invoice", 'f.total_ht'=>"invoice",
			'f.total_ttc'=>"invoice", 'f.total_tva'=>"invoice", 'f.paye'=>"invoice", 'f.fk_statut'=>'invoice', 'f.note_public'=>"invoice", 'p.rowid'=>'payment', 'pf.amount'=>'payment',
			'p.datep'=>'payment', 'p.num_paiement'=>'payment', 'p.fk_bank'=>'account', 'project.rowid'=>'project', 'project.ref'=>'project', 'project.title'=>'project');
		$this->export_dependencies_array[$r] = array('payment'=>'p.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them
		// Add extra fields object
		$keyforselect = 'facture_fourn';
		$keyforelement = 'invoice';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		// End add extra fields object
		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'societe as s';
		if (is_object($user) && empty($user->rights->societe->client->voir)) {
			$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe_commerciaux as sc ON sc.fk_soc = s.rowid';
		}
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON s.fk_pays = c.rowid,';
		$this->export_sql_end[$r] .= ' '.MAIN_DB_PREFIX.'facture_fourn as f';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as project on (f.fk_projet = project.rowid)';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn_extrafields as extra ON f.rowid = extra.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf ON pf.fk_facturefourn = f.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn as p ON pf.fk_paiementfourn = p.rowid';
		$this->export_sql_end[$r] .= ' WHERE f.fk_soc = s.rowid';
		$this->export_sql_end[$r] .= ' AND f.entity IN ('.getEntity('supplier_invoice').')';
		if (is_object($user) && empty($user->rights->societe->client->voir)) {
			$this->export_sql_end[$r] .= ' AND sc.fk_user = '.$user->id;
		}

		// Order
		$r++;
		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'Purchase Orders and lines of purchase orders';
		$this->export_icon[$r] = 'order';
		$this->export_permission[$r] = array(array("fournisseur", "commande", "export"));
		$this->export_fields_array[$r] = array(
			's.rowid'=>"IdCompany", 's.nom'=>'CompanyName', 'ps.nom'=>'ParentCompany', 's.address'=>'Address', 's.zip'=>'Zip', 's.town'=>'Town', 'c.code'=>'CountryCode', 's.phone'=>'Phone',
			's.siren'=>'ProfId1', 's.siret'=>'ProfId2', 's.ape'=>'ProfId3', 's.idprof4'=>'ProfId4', 's.idprof5'=>'ProfId5', 's.idprof6'=>'ProfId6', 's.tva_intra'=>'VATIntra',
			'f.rowid'=>"OrderId", 'f.ref'=>"Ref", 'f.ref_supplier'=>"RefSupplier", 'f.date_creation'=>"DateCreation", 'f.date_commande'=>"OrderDate", 'f.date_livraison'=>"DateDeliveryPlanned",
			'f.total_ht'=>"TotalHT", 'f.total_ttc'=>"TotalTTC", 'f.total_tva'=>"TotalVAT", 'f.fk_statut'=>'Status', 'f.date_approve'=>'DateApprove', 'f.date_approve2'=>'DateApprove2',
			'f.note_public'=>"NotePublic", 'f.note_private'=>"NotePrivate", 'ua1.login'=>'ApprovedBy', 'ua2.login'=>'ApprovedBy2', 'fd.rowid'=>'LineId', 'fd.description'=>"LineDescription",
			'fd.tva_tx'=>"LineVATRate", 'fd.qty'=>"LineQty", 'fd.remise_percent'=>"Discount", 'fd.total_ht'=>"LineTotalHT", 'fd.total_ttc'=>"LineTotalTTC",
			'fd.total_tva'=>"LineTotalVAT", 'fd.product_type'=>'TypeOfLineServiceOrProduct', 'fd.ref'=>'RefSupplier', 'fd.fk_product'=>'ProductId',
			'p.ref'=>'ProductRef', 'p.label'=>'ProductLabel', 'project.rowid'=>'ProjectId', 'project.ref'=>'ProjectRef', 'project.title'=>'ProjectLabel'
		);
		if (!empty($conf->multicurrency->enabled)) {
			$this->export_fields_array[$r]['f.multicurrency_code'] = 'Currency';
			$this->export_fields_array[$r]['f.multicurrency_tx'] = 'CurrencyRate';
			$this->export_fields_array[$r]['f.multicurrency_total_ht'] = 'MulticurrencyAmountHT';
			$this->export_fields_array[$r]['f.multicurrency_total_tva'] = 'MulticurrencyAmountVAT';
			$this->export_fields_array[$r]['f.multicurrency_total_ttc'] = 'MulticurrencyAmountTTC';
		}
		if (empty($conf->global->SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED)) {
			unset($this->export_fields_array['f.date_approve2']);
			unset($this->export_fields_array['ua2.login']);
		}
		$this->export_TypeFields_array[$r] = array(
			's.rowid'=>"company", 's.nom'=>'Text', 'ps.nom'=>'Text', 's.address'=>'Text', 's.cp'=>'Text', 's.ville'=>'Text', 'c.code'=>'Text', 's.tel'=>'Text', 's.siren'=>'Text',
			's.siret'=>'Text', 's.ape'=>'Text', 's.idprof4'=>'Text', 's.idprof5'=>'Text', 's.idprof6'=>'Text', 's.tva_intra'=>'Text', 'f.ref'=>"Text", 'f.ref_supplier'=>"Text",
			'f.date_creation'=>"Date", 'f.date_commande'=>"Date", 'f.date_livraison'=>"Date", 'f.total_ht'=>"Numeric", 'f.total_ttc'=>"Numeric", 'f.total_tva'=>"Numeric",
			'f.fk_statut'=>'Status', 'f.date_approve'=>'Date', 'f.date_approve2'=>'Date', 'f.note_public'=>"Text", 'f.note_private'=>"Text", 'fd.description'=>"Text",
			'fd.tva_tx'=>"Numeric", 'fd.qty'=>"Numeric", 'fd.remise_percent'=>"Numeric", 'fd.total_ht'=>"Numeric", 'fd.total_ttc'=>"Numeric", 'fd.total_tva'=>"Numeric",
			'fd.product_type'=>'Numeric', 'fd.ref'=>'Text', 'fd.fk_product'=>'List:product:label', 'p.ref'=>'Text', 'p.label'=>'Text', 'project.ref'=>'Text', 'project.title'=>'Text'
		);
		$this->export_entities_array[$r] = array(
			's.rowid'=>"company", 's.nom'=>'company', 'ps.nom'=>'company', 's.address'=>'company', 's.zip'=>'company', 's.town'=>'company', 'c.code'=>'company', 's.phone'=>'company', 's.siren'=>'company',
			's.siret'=>'company', 's.ape'=>'company', 's.idprof4'=>'company', 's.idprof5'=>'company', 's.idprof6'=>'company', 's.tva_intra'=>'company', 'ua1.login'=>'user',
			'ua2.login'=>'user', 'fd.rowid'=>'order_line', 'fd.description'=>"order_line", 'fd.tva_tx'=>"order_line", 'fd.qty'=>"order_line", 'fd.remise_percent'=>"order_line",
			'fd.total_ht'=>"order_line", 'fd.total_ttc'=>"order_line", 'fd.total_tva'=>"order_line", 'fd.product_type'=>'order_line', 'fd.ref'=>'order_line', 'fd.fk_product'=>'product',
			'p.ref'=>'product', 'p.label'=>'product', 'project.rowid'=>'project', 'project.ref'=>'project', 'project.title'=>'project'
		);
		$this->export_dependencies_array[$r] = array('order_line'=>'fd.rowid', 'product'=>'fd.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them
		// Add extra fields object
		$keyforselect = 'commande_fournisseur';
		$keyforelement = 'order';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		// End add extra fields object
		// Add extra fields line
		$keyforselect = 'commande_fournisseurdet';
		$keyforelement = 'order_line';
		$keyforaliasextra = 'extraline';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		// End add extra fields line
		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'societe as s';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as ps ON ps.rowid = s.parent';
		if (is_object($user) && empty($user->rights->societe->client->voir)) {
			$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe_commerciaux as sc ON sc.fk_soc = s.rowid';
		}
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON s.fk_pays = c.rowid,';
		$this->export_sql_end[$r] .= ' '.MAIN_DB_PREFIX.'commande_fournisseur as f';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as project on (f.fk_projet = project.rowid)';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as ua1 ON ua1.rowid = f.fk_user_approve';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as ua2 ON ua2.rowid = f.fk_user_approve2';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commande_fournisseur_extrafields as extra ON f.rowid = extra.fk_object,';
		$this->export_sql_end[$r] .= ' '.MAIN_DB_PREFIX.'commande_fournisseurdet as fd';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commande_fournisseurdet_extrafields as extraline ON fd.rowid = extraline.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		$this->export_sql_end[$r] .= ' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_commande';
		$this->export_sql_end[$r] .= ' AND f.entity IN ('.getEntity('supplier_order').')';
		if (is_object($user) && empty($user->rights->societe->client->voir)) {
			$this->export_sql_end[$r] .= ' AND sc.fk_user = '.$user->id;
		}

		//Import Supplier Invoice
		//--------
		$r = 0;

		$r++;
		$this->import_code[$r] = $this->rights_class.'_'.$r;
		$this->import_label[$r] = "SupplierInvoices"; // Translation key
		$this->import_icon[$r] = $this->picto;
		$this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r] = ['f' => MAIN_DB_PREFIX.'facture_fourn', 'extra' => MAIN_DB_PREFIX.'facture_fourn_extrafields'];
		$this->import_tables_creator_array[$r] = ['f' => 'fk_user_author']; // Fields to store import user id
		$this->import_fields_array[$r] = [
			'f.ref' => 'InvoiceRef*',
			'f.ref_supplier' => 'RefSupplier',
			'f.type' => 'Type*',
			'f.fk_soc' => 'Supplier/Vendor*',
			'f.datec' => 'InvoiceDateCreation',
			'f.datef' => 'DateInvoice',
			'f.date_lim_reglement' => 'DateMaxPayment',
			'f.total_ht' => 'TotalHT',
			'f.total_ttc' => 'TotalTTC',
			'f.total_tva' => 'TotalVAT',
			'f.paye' => 'InvoicePaid',
			'f.fk_statut' => 'InvoiceStatus',
			'f.fk_user_modif' => 'Modifier Id',
			'f.fk_user_valid' => 'Validator Id',
			'f.fk_facture_source' => 'Invoice Source Id',
			'f.fk_projet' => 'Project Id',
			'f.fk_account' => 'Bank Account*',
			'f.note_public' => 'InvoiceNote',
			'f.note_private' => 'NotePrivate',
			'f.fk_cond_reglement' => 'Payment Condition',
			'f.fk_mode_reglement' => 'Payment Mode',
			'f.model_pdf' => 'Model',
			'f.date_valid' => 'Validation Date'
		];
		if (!empty($conf->multicurrency->enabled)) {
			$this->import_fields_array[$r]['f.multicurrency_code'] = 'Currency';
			$this->import_fields_array[$r]['f.multicurrency_tx'] = 'CurrencyRate';
			$this->import_fields_array[$r]['f.multicurrency_total_ht'] = 'MulticurrencyAmountHT';
			$this->import_fields_array[$r]['f.multicurrency_total_tva'] = 'MulticurrencyAmountVAT';
			$this->import_fields_array[$r]['f.multicurrency_total_ttc'] = 'MulticurrencyAmountTTC';
		}
		// Add extra fields
		$import_extrafield_sample = array();
		$sql = "SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE type <> 'separate' AND elementtype = 'facture_fourn' AND entity IN (0, ".$conf->entity.")";
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
		$this->import_fieldshidden_array[$r] = ['extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'facture_fourn'];
		$this->import_regex_array[$r] = ['f.ref' => '(SI\d{4}-\d{4}|PROV.{1,32}$)', 'f.multicurrency_code' => 'code@'.MAIN_DB_PREFIX.'multicurrency'];
		$import_sample = [
			'f.ref' => '(PROV001)',
			'f.ref_supplier' => 'Supplier1',
			'f.type' => '0',
			'f.fk_soc' => 'Vendor1',
			'f.datec' => '2021-01-01',
			'f.datef' => '',
			'f.date_lim_reglement' => '2021-01-30',
			'f.total_ht' => '1000',
			'f.total_ttc' => '1000',
			'f.total_tva' => '0',
			'f.paye' => '0',
			'f.fk_statut' => '0',
			'f.fk_user_modif' => '',
			'f.fk_user_valid' => '',
			'f.fk_facture_source' => '',
			'f.fk_projet' => '',
			'f.fk_account' => 'BANK1',
			'f.note_public' => 'Note: ',
			'f.note_private' => '',
			'f.fk_cond_reglement' => '1',
			'f.fk_mode_reglement' => '2',
			'f.model_pdf' => 'crab',
			'f.date_valid' => '',
			'f.multicurrency_code' => 'USD',
			'f.multicurrency_tx' => '1',
			'f.multicurrency_total_ht' => '1000',
			'f.multicurrency_total_tva' => '0',
			'f.multicurrency_total_ttc' => '1000'
		];
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = ['f.ref' => 'Ref'];
		$this->import_convertvalue_array[$r] = [
			//'c.ref'=>array('rule'=>'getrefifauto'),
			'f.fk_soc' => ['rule' => 'fetchidfromref', 'file' => '/societe/class/societe.class.php', 'class' => 'Societe', 'method' => 'fetch', 'element' => 'ThirdParty'],
			'f.fk_account' => ['rule' => 'fetchidfromref', 'file' => '/compta/bank/class/account.class.php', 'class' => 'Account', 'method' => 'fetch', 'element' => 'bank_account'],
		];

		//Import Supplier Invoice Lines
		$r++;
		$this->import_code[$r] = $this->rights_class.'_'.$r;
		$this->import_label[$r] = "SupplierInvoiceLines"; // Translation key
		$this->import_icon[$r] = $this->picto;
		$this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r] = ['fd' => MAIN_DB_PREFIX.'facture_fourn_det', 'extra' => MAIN_DB_PREFIX.'facture_fourn_det_extrafields'];
		$this->import_fields_array[$r] = [
			'fd.fk_facture_fourn' => 'InvoiceRef*',
			'fd.fk_parent_line' => 'FacParentLine',
			'fd.fk_product' => 'IdProduct',
			'fd.label' => 'Label',
			'fd.description' => 'LineDescription',
			'fd.pu_ht' => 'PriceUHT',
			'fd.pu_ttc' => 'PriceUTTC',
			'fd.qty' => 'LineQty',
			'fd.remise_percent' => 'Reduc.',
			'fd.vat_src_code' => 'Vat Source Code',
			'fd.product_type' => 'TypeOfLineServiceOrProduct',
			'fd.tva_tx' => 'LineVATRate',
			'fd.total_ht' => 'LineTotalHT',
			'fd.tva' => 'LineTotalVAT',
			'fd.total_ttc' => 'LineTotalTTC',
			'fd.date_start' => 'Start Date',
			'fd.date_end' => 'End Date',
			'fd.fk_unit' => 'Unit'
		];
		if (!empty($conf->multicurrency->enabled)) {
			$this->import_fields_array[$r]['fd.multicurrency_code'] = 'Currency';
			$this->import_fields_array[$r]['fd.multicurrency_subprice'] = 'CurrencyRate';
			$this->import_fields_array[$r]['fd.multicurrency_total_ht'] = 'MulticurrencyAmountHT';
			$this->import_fields_array[$r]['fd.multicurrency_total_tva'] = 'MulticurrencyAmountVAT';
			$this->import_fields_array[$r]['fd.multicurrency_total_ttc'] = 'MulticurrencyAmountTTC';
		}
		// Add extra fields
		$import_extrafield_sample = array();
		$sql = "SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE type <> 'separate' AND elementtype = 'facture_fourn_det' AND entity IN (0, ".$conf->entity.")";
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
		$this->import_fieldshidden_array[$r] = ['extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'facture_fourn_det'];
		$this->import_regex_array[$r] = ['fd.product_type' => '[0|1]$', 'fd.fk_product' => 'rowid@'.MAIN_DB_PREFIX.'product', 'fd.multicurrency_code' => 'code@'.MAIN_DB_PREFIX.'multicurrency'];
		$import_sample = [
			'fd.fk_facture_fourn' => '(PROV001)',
			'fd.fk_parent_line' => '',
			'fd.fk_product' => '',
			'fd.label' => '',
			'fd.description' => 'Test Product',
			'fd.pu_ht' => '50000',
			'fd.pu_ttc' => '50000',
			'fd.qty' => '1',
			'fd.remise_percent' => '0',
			'fd.vat_src_code' => '',
			'fd.product_type' => '0',
			'fd.tva_tx' => '0',
			'fd.total_ht' => '50000',
			'fd.tva' => '0',
			'fd.total_ttc' => '50000',
			'fd.date_start' => '',
			'fd.date_end' => '',
			'fd.fk_unit' => '',
			'fd.multicurrency_code' => 'USD',
			'fd.multicurrency_tx' => '0',
			'fd.multicurrency_total_ht' => '50000',
			'fd.multicurrency_total_tva' => '0',
			'fd.multicurrency_total_ttc' => '50000'
		];
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = ['fd.rowid' => 'Row Id', 'fd.fk_facture_fourn' => 'Invoice Id', 'fd.fk_product' => 'Product Id'];
		$this->import_convertvalue_array[$r] = [
			'fd.fk_facture_fourn' => ['rule' => 'fetchidfromref', 'file' => '/fourn/class/fournisseur.facture.class.php', 'class' => 'FactureFournisseur', 'method' => 'fetch'],
		];

		//Import Purchase Orders
		$r++;
		$this->import_code[$r] = 'commande_fournisseur_'.$r;
		$this->import_label[$r] = 'SuppliersOrders';
		$this->import_icon[$r] = $this->picto;
		$this->import_entities_array[$r] = [];
		$this->import_tables_array[$r] = ['c' => MAIN_DB_PREFIX.'commande_fournisseur', 'extra' => MAIN_DB_PREFIX.'commande_fournisseur_extrafields'];
		$this->import_tables_creator_array[$r] = ['c' => 'fk_user_author']; // Fields to store import user id
		$this->import_fields_array[$r] = [
			'c.ref'               => 'Document Ref*',
			'c.ref_supplier'      => 'RefSupplier',
			'c.fk_soc'            => 'ThirdPartyName*',
			'c.fk_projet'         => 'ProjectId',
			'c.date_creation'     => 'DateCreation',
			'c.date_valid'        => 'DateValid',
			'c.date_approve'      => 'DateApprove',
			'c.date_commande'     => 'DateOrder',
			'c.fk_user_modif'     => 'ModifiedById',
			'c.fk_user_valid'     => 'ValidatedById',
			'c.fk_user_approve'   => 'ApprovedById',
			'c.source'            => 'Source',
			'c.fk_statut'         => 'Status*',
			'c.billed'            => 'Billed(0/1)',
			'c.remise_percent'    => 'GlobalDiscount',
			'c.total_tva'         => 'TotalTVA',
			'c.total_ht'          => 'TotalHT',
			'c.total_ttc'         => 'TotalTTC',
			'c.note_private'      => 'NotePrivate',
			'c.note_public'       => 'Note',
			'c.date_livraison'    => 'DeliveryDate',
			'c.fk_cond_reglement' => 'Payment Condition',
			'c.fk_mode_reglement' => 'Payment Mode',
			'c.model_pdf'         => 'Model'
		];

		if (!empty($conf->multicurrency->enabled)) {
			$this->import_fields_array[$r]['c.multicurrency_code']      = 'Currency';
			$this->import_fields_array[$r]['c.multicurrency_tx']        = 'CurrencyRate';
			$this->import_fields_array[$r]['c.multicurrency_total_ht']  = 'MulticurrencyAmountHT';
			$this->import_fields_array[$r]['c.multicurrency_total_tva'] = 'MulticurrencyAmountVAT';
			$this->import_fields_array[$r]['c.multicurrency_total_ttc'] = 'MulticurrencyAmountTTC';
		}

		// Add extra fields
		$import_extrafield_sample = array();
		$sql = "SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE type <> 'separate' AND elementtype = 'commande_fournisseur' AND entity IN (0, ".$conf->entity.")";
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

		$this->import_fieldshidden_array[$r] = ['extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'commande_fournisseur'];
		$this->import_regex_array[$r] = [
			'c.ref' => '(PO\d{4}-\d{4}|PORDER.{1,32}$|PROV.{1,32}$)',
			'c.multicurrency_code' => 'code@'.MAIN_DB_PREFIX.'multicurrency'
		];

		$this->import_updatekeys_array[$r] = ['c.ref' => 'Ref'];
		$this->import_convertvalue_array[$r] = [
			'c.fk_soc' => [
				'rule'    => 'fetchidfromref',
				'file'    => '/societe/class/societe.class.php',
				'class'   => 'Societe',
				'method'  => 'fetch',
				'element' => 'ThirdParty'
			],
			'c.fk_mode_reglement' => [
				'rule' => 'fetchidfromcodeorlabel',
				'file' => '/compta/paiement/class/cpaiement.class.php',
				'class' => 'Cpaiement',
				'method' => 'fetch',
				'element' => 'cpayment'
			],
			'c.source' => ['rule' => 'zeroifnull'],
		];

		//Import PO Lines
		$r++;
		$this->import_code[$r] = 'commande_fournisseurdet_'.$r;
		$this->import_label[$r] = 'PurchaseOrderLines';
		$this->import_icon[$r] = $this->picto;
		$this->import_entities_array[$r] = array();
		$this->import_tables_array[$r] = ['cd' => MAIN_DB_PREFIX.'commande_fournisseurdet', 'extra' => MAIN_DB_PREFIX.'commande_fournisseurdet_extrafields'];
		$this->import_fields_array[$r] = [
			'cd.fk_commande'    => 'Document Ref*',
			'cd.fk_parent_line' => 'PrParentLine',
			'cd.fk_product'     => 'IdProduct',
			'cd.label'          => 'Label',
			'cd.description'    => 'LineDescription',
			'cd.tva_tx'         => 'LineVATRate',
			'cd.qty'            => 'LineQty',
			'cd.remise_percent' => 'Reduc. Percent',
			'cd.remise'         => 'Reduc.',
			'cd.subprice'       => 'Sub Price',
			'cd.total_ht'       => 'LineTotalHT',
			'cd.total_tva'      => 'LineTotalVAT',
			'cd.total_ttc'      => 'LineTotalTTC',
			'cd.product_type'   => 'TypeOfLineServiceOrProduct',
			'cd.date_start'     => 'Start Date',
			'cd.date_end'       => 'End Date',
			'cd.info_bits'      => 'InfoBits',
			'cd.special_code'   => 'Special Code',
			'cd.rang'           => 'LinePosition',
			'cd.fk_unit'        => 'Unit'
		];

		if (!empty($conf->multicurrency->enabled)) {
			$this->import_fields_array[$r]['cd.multicurrency_code'] = 'Currency';
			$this->import_fields_array[$r]['cd.multicurrency_subprice'] = 'CurrencyRate';
			$this->import_fields_array[$r]['cd.multicurrency_total_ht'] = 'MulticurrencyAmountHT';
			$this->import_fields_array[$r]['cd.multicurrency_total_tva'] = 'MulticurrencyAmountVAT';
			$this->import_fields_array[$r]['cd.multicurrency_total_ttc'] = 'MulticurrencyAmountTTC';
		}

		// Add extra fields
		$sql = "SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE type <> 'separate' AND elementtype = 'commande_fournisseurdet' AND entity IN (0, ".$conf->entity.")";
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$fieldname = 'extra.'.$obj->name;
				$fieldlabel = ucfirst($obj->label);
				$this->import_fields_array[$r][$fieldname] = $fieldlabel.($obj->fieldrequired ? '*' : '');
			}
		}
		// End add extra fields

		$this->import_fieldshidden_array[$r] = ['extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'commande_fournisseurdet'];
		$this->import_regex_array[$r] = [
			'cd.product_type'       => '[0|1]$',
			'cd.fk_product'         => 'rowid@'.MAIN_DB_PREFIX.'product',
			'cd.multicurrency_code' => 'code@'.MAIN_DB_PREFIX.'multicurrency'
		];
		$this->import_updatekeys_array[$r] = ['cd.fk_commande' => 'Purchase Order Id'];
		$this->import_convertvalue_array[$r] = [
			'cd.fk_commande' => [
				'rule'    => 'fetchidfromref',
				'file'    => '/fourn/class/fournisseur.commande.class.php',
				'class'   => 'CommandeFournisseur',
				'method'  => 'fetch',
				'element' => 'order_supplier'
			],
			'cd.info_bits' => ['rule' => 'zeroifnull'],
			'cd.special_code' => ['rule' => 'zeroifnull'],
		];
	}


	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
	 *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		$this->remove($options);

		//ODT template
		$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/supplier_orders/template_supplier_order.odt';
		$dirodt = DOL_DATA_ROOT.'/doctemplates/supplier_orders';
		$dest = $dirodt.'/template_supplier_order.odt';

		if (file_exists($src) && !file_exists($dest)) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_mkdir($dirodt);
			$result = dol_copy($src, $dest, 0, 0);
			if ($result < 0) {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
				return 0;
			}
		}

		$sql = array(
			 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[0][2])."' AND type = 'order_supplier' AND entity = ".$conf->entity,
			 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[0][2])."','order_supplier',".$conf->entity.")",
		);

		return $this->_init($sql, $options);
	}
}
