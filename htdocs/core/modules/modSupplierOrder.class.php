<?php
/* Copyright (C) 2003       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013-2015  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2020       Ahmad Jamaly Rabib      <rabib@metroworks.co.jp>
 * Copyright (C) 2024-2026  MDW                     <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026       Alexandre Spangaro      <alexandre@inovea-conseil.com>
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
 *  \defgroup   Supplier order
 *  \brief      Module to manage suppliers orders
 *
 *  \file       htdocs/core/modules/modSupplierOrder.class.php
 *  \ingroup    supplier_order
 *  \brief      Description and activation file for the module Supplier order
 */
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';

/**
 *    Description and activation class for module supplier order
 */
class modSupplierOrder extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $user, $mysoc;

		$this->db = $db;
		$this->numero = 39;

		// Family can be 'crm','financial','hr','projects','product','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "srm";
		$this->module_position = '12';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Manage supplier order";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		$this->hidden = !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD');

		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		$this->picto = 'supplier_order';

		// Data directories to create when module is enabled
		$this->dirs = array(
			"/fournisseur/temp",
			"/fournisseur/commande",
			"/fournisseur/commande/temp",
		);

		// Dependencies
		$this->depends = array("modSociete");
		$this->requiredby = array("modSupplierProposal");
		$this->langfiles = array('bills', 'companies', 'suppliers', 'orders', 'sendings');

		// Config pages
		$this->config_page_url = array("supplier_order.php@supplier_order");

		// Constants
		$this->const = [
			[
				"COMMANDE_SUPPLIER_ADDON_PDF",
				"chaine",
				"cornas",
				"Name of the PDF purchase order generation manager",
				0,
			],
			[
				"COMMANDE_SUPPLIER_ADDON_NUMBER",
				"chaine",
				"mod_commande_fournisseur_muguet",
				"Name of the supplier invoice numbering manager",
				0,
			],
			// Add ability ODT for Supplier orders
			[
				"SUPPLIER_ORDER_ADDON_PDF_ODT_PATH",
				"chaine",
				"DOL_DATA_ROOT" . ($conf->entity > 1 ? '/' . $conf->entity : '') . "/doctemplates/supplier_orders",
				"",
				0,
			],
		];

		// Boxes
		$this->boxes = [
			['file' => 'box_graph_orders_supplier_permonth.php', 'enabledbydefaulton' => 'Home'],
			['file' => 'box_fournisseurs.php', 'enabledbydefaulton' => 'Home'],
			['file' => 'box_supplier_orders.php', 'enabledbydefaulton' => 'Home'],
			['file' => 'box_supplier_orders_awaiting_reception.php', 'enabledbydefaulton' => 'Home'],
		];

		// Permissions
		$this->rights = array();
		$this->rights_class = 'supplier_order';
		$r = 0;

		$r++;
		$this->rights[$r][0] = 1152;
		$this->rights[$r][1] = 'Read purchase orders';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 1153;
		$this->rights[$r][1] = 'Create a purchase order';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 1154;
		$this->rights[$r][1] = 'Validate purchase orders';	// Advance permission because useless. "Validate" in dolibarr means "Create really" vs "Create as draft only".;
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supplier_order_advance';
		$this->rights[$r][5] = 'validate';

		$r++;
		$this->rights[$r][0] = 1155;
		$this->rights[$r][1] = 'Approve purchase orders';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'approuver';

		$r++;
		$this->rights[$r][0] = 1156;
		$this->rights[$r][1] = 'Order a purchase order';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'commander';

		$r++;
		$this->rights[$r][0] = 1157;
		$this->rights[$r][1] = 'Receive a purchase order';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'receptionner';

		$r++;
		$this->rights[$r][0] = 1159;
		$this->rights[$r][1] = 'Check/Uncheck a supplier order reception';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supplier_order_advance';
		$this->rights[$r][5] = 'check';

		$r++;
		$this->rights[$r][0] = 1158;
		$this->rights[$r][1] = 'Delete a purchase order';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';

		if (getDolGlobalString('SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED')) {
			$r++;
			$this->rights[$r][0] = 1160;
			$this->rights[$r][1] = 'Approve supplier order (second level)'; // $langs->trans("Permission1190");
			$this->rights[$r][2] = 'w';
			$this->rights[$r][3] = 0;
			$this->rights[$r][4] = 'approve2';
		}

		$r++;
		$this->rights[$r][0] = 1161;
		$this->rights[$r][1] = 'Export purchase orders';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'export';

		// Menus
		//-------
		$this->menu = 1; // This module add menu entries. They are coded into menu manager.


		// Exports
		//--------
		$r++;
		$this->export_code[$r] = $this->rights_class . '_' . $r;
		$this->export_label[$r] = 'Purchase Orders and lines of purchase orders';
		$this->export_icon[$r] = 'supplier_order';
		$this->export_permission[$r] = array(array("fournisseur", "commande", "export"));
		$this->export_fields_array[$r] = array(
			's.rowid' => "IdCompany",
			's.nom' => 'CompanyName',
			'ps.nom' => 'ParentCompany',
			's.address' => 'Address',
			's.zip' => 'Zip',
			's.town' => 'Town',
			'c.code' => 'CountryCode',
			's.phone' => 'Phone',
			's.siren' => 'ProfId1',
			's.siret' => 'ProfId2',
			's.ape' => 'ProfId3',
			's.idprof4' => 'ProfId4',
			's.idprof5' => 'ProfId5',
			's.idprof6' => 'ProfId6',
			's.tva_intra' => 'VATIntra',
			'f.rowid' => "OrderId",
			'f.ref' => "Ref",
			'f.ref_supplier' => "RefSupplier",
			'f.date_creation' => "DateCreation",
			'f.date_commande' => "OrderDate",
			'f.date_livraison' => "DateDeliveryPlanned",
			'f.total_ht' => "TotalHT",
			'f.total_ttc' => "TotalTTC",
			'f.total_tva' => "TotalVAT",
			'f.fk_statut' => 'Status',
			'f.date_valid' => 'DateValidation',
			'f.date_approve' => 'DateApprove',
			'f.date_approve2' => 'DateApprove2',
			'f.note_public' => "NotePublic",
			'f.note_private' => "NotePrivate",
			'uv.login' => 'UserValidation',
			'ua1.login' => 'ApprovedBy',
			'ua2.login' => 'ApprovedBy2',
			'f.source' => 'Source',
			'fd.rowid' => 'LineId',
			'fd.description' => "LineDescription",
			'fd.tva_tx' => "LineVATRate",
			'fd.qty' => "LineQty",
			'fd.remise_percent' => "Discount",
			'fd.total_ht' => "LineTotalHT",
			'fd.total_ttc' => "LineTotalTTC",
			'fd.total_tva' => "LineTotalVAT",
			'fd.date_start' => "DateStart",
			'fd.date_end' => "DateEnd",
			'fd.special_code' => 'SpecialCode',
			'fd.product_type' => 'TypeOfLineServiceOrProduct',
			'fd.ref' => 'SupplierRef',
			'fd.fk_product' => 'ProductId',
			'p.ref' => 'ProductRef',
			'p.label' => 'ProductLabel',
			'project.rowid' => 'ProjectId',
			'project.ref' => 'ProjectRef',
			'project.title' => 'ProjectLabel'
		);
		if (isModEnabled("multicurrency")) {
			$this->export_fields_array[$r]['f.multicurrency_code'] = 'Currency';
			$this->export_fields_array[$r]['f.multicurrency_tx'] = 'CurrencyRate';
			$this->export_fields_array[$r]['f.multicurrency_total_ht'] = 'MulticurrencyAmountHT';
			$this->export_fields_array[$r]['f.multicurrency_total_tva'] = 'MulticurrencyAmountVAT';
			$this->export_fields_array[$r]['f.multicurrency_total_ttc'] = 'MulticurrencyAmountTTC';
		}
		if (!getDolGlobalString('SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED')) {
			unset($this->export_fields_array['f.date_approve2']);
			unset($this->export_fields_array['ua2.login']);
		}
		$this->export_TypeFields_array[$r] = array(
			's.rowid' => "company",
			's.nom' => 'Text',
			'ps.nom' => 'Text',
			's.address' => 'Text',
			's.cp' => 'Text',
			's.ville' => 'Text',
			'c.code' => 'Text',
			's.tel' => 'Text',
			's.siren' => 'Text',
			's.siret' => 'Text',
			's.ape' => 'Text',
			's.idprof4' => 'Text',
			's.idprof5' => 'Text',
			's.idprof6' => 'Text',
			's.tva_intra' => 'Text',
			'f.ref' => "Text",
			'f.ref_supplier' => "Text",
			'f.date_creation' => "Date",
			'f.date_commande' => "Date",
			'f.date_livraison' => "Date",
			'f.total_ht' => "Numeric",
			'f.total_ttc' => "Numeric",
			'f.total_tva' => "Numeric",
			'f.fk_statut' => 'Status',
			'f.date_valid' => 'Date',
			'f.date_approve' => 'Date',
			'f.date_approve2' => 'Date',
			'f.note_public' => "Text",
			'f.note_private' => "Text",
			'f.source' => 'Numeric',
			'fd.description' => "Text",
			'fd.tva_tx' => "Numeric",
			'fd.qty' => "Numeric",
			'fd.remise_percent' => "Numeric",
			'fd.total_ht' => "Numeric",
			'fd.total_ttc' => "Numeric",
			'fd.total_tva' => "Numeric",
			'fd.date_start' => "Date",
			'fd.date_end' => "Date",
			'fd.special_code' => "Numeric",
			'fd.product_type' => 'Numeric',
			'fd.ref' => 'Text',
			'fd.fk_product' => 'List:product:label',
			'p.ref' => 'Text',
			'p.label' => 'Text',
			'project.ref' => 'Text',
			'project.title' => 'Text'
		);
		$this->export_entities_array[$r] = array(
			's.rowid' => "company",
			's.nom' => 'company',
			'ps.nom' => 'company',
			's.address' => 'company',
			's.zip' => 'company',
			's.town' => 'company',
			'c.code' => 'company',
			's.phone' => 'company',
			's.siren' => 'company',
			's.siret' => 'company',
			's.ape' => 'company',
			's.idprof4' => 'company',
			's.idprof5' => 'company',
			's.idprof6' => 'company',
			's.tva_intra' => 'company',
			'uv.login' => 'user',
			'ua1.login' => 'user',
			'ua2.login' => 'user',
			'f.source' => 'order',
			'fd.rowid' => 'order_line',
			'fd.description' => "order_line",
			'fd.tva_tx' => "order_line",
			'fd.qty' => "order_line",
			'fd.remise_percent' => "order_line",
			'fd.total_ht' => "order_line",
			'fd.total_ttc' => "order_line",
			'fd.total_tva' => "order_line",
			'fd.date_start' => "order_line",
			'fd.date_end' => "order_line",
			'fd.special_code' => "order_line",
			'fd.product_type' => 'order_line',
			'fd.ref' => 'order_line',
			'fd.fk_product' => 'product',
			'p.ref' => 'product',
			'p.label' => 'product',
			'project.rowid' => 'project',
			'project.ref' => 'project',
			'project.title' => 'project'
		);
		$this->export_dependencies_array[$r] = array('order_line' => 'fd.rowid', 'product' => 'fd.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them
		// Add extra fields object
		$keyforselect = 'commande_fournisseur';
		$keyforelement = 'order';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT . '/core/extrafieldsinexport.inc.php';
		// End add extra fields object
		// Add extra fields line
		$keyforselect = 'commande_fournisseurdet';
		$keyforelement = 'order_line';
		$keyforaliasextra = 'extraline';
		include DOL_DOCUMENT_ROOT . '/core/extrafieldsinexport.inc.php';
		// End add extra fields line
		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r] = ' FROM ' . MAIN_DB_PREFIX . 'societe as s';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as ps ON ps.rowid = s.parent';
		if (is_object($user) && !$user->hasRight('societe', 'client', 'voir')) {
			$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe_commerciaux as sc ON sc.fk_soc = s.rowid';
		}
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_country as c ON s.fk_pays = c.rowid,';
		$this->export_sql_end[$r] .= ' ' . MAIN_DB_PREFIX . 'commande_fournisseur as f';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'projet as project on (f.fk_projet = project.rowid)';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as uv ON uv.rowid = f.fk_user_valid';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as ua1 ON ua1.rowid = f.fk_user_approve';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user as ua2 ON ua2.rowid = f.fk_user_approve2';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseur_extrafields as extra ON f.rowid = extra.fk_object,';
		$this->export_sql_end[$r] .= ' ' . MAIN_DB_PREFIX . 'commande_fournisseurdet as fd';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'commande_fournisseurdet_extrafields as extraline ON fd.rowid = extraline.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product as p on (fd.fk_product = p.rowid)';
		$this->export_sql_end[$r] .= ' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_commande';
		$this->export_sql_end[$r] .= ' AND f.entity IN (' . getEntity('supplier_order') . ')';
		if (is_object($user) && !$user->hasRight('societe', 'client', 'voir')) {
			$this->export_sql_end[$r] .= ' AND sc.fk_user = ' . ((int) $user->id);
		}

		// Imports
		//--------
		$r = 0;

		$r++;
		$this->import_code[$r] = 'commande_fournisseur_' . $r;
		$this->import_label[$r] = 'SuppliersOrders';
		$this->import_icon[$r] = 'supplier_order';
		$this->import_entities_array[$r] = array();
		$this->import_tables_array[$r] = array('c' => MAIN_DB_PREFIX . 'commande_fournisseur', 'extra' => MAIN_DB_PREFIX . 'commande_fournisseur_extrafields');
		$this->import_tables_creator_array[$r] = array('c' => 'fk_user_author'); // Fields to store import user id
		$this->import_fields_array[$r] = array(
			'c.ref' => 'Ref*',
			'c.ref_supplier' => 'RefSupplier',
			'c.fk_soc' => 'ThirdPartyName*',
			'c.fk_projet' => 'ProjectId',
			'c.date_creation' => 'DateCreation',
			'c.date_valid' => 'DateValidation',
			'c.date_approve' => 'DateApprove',
			'c.date_commande' => 'DateOrder',
			'c.fk_user_modif' => 'ModifiedById',
			'c.fk_user_valid' => 'ValidatedById',
			'c.fk_user_approve' => 'ApprovedById',
			'c.source' => 'Source',
			'c.fk_statut' => 'Status*',
			'c.billed' => 'Billed',
			'c.total_tva' => 'TotalTVA',
			'c.total_ht' => 'TotalHT',
			'c.total_ttc' => 'TotalTTC',
			'c.note_private' => 'NotePrivate',
			'c.note_public' => 'Note',
			'c.date_livraison' => 'DeliveryDate',
			'c.fk_cond_reglement' => 'Payment Condition',
			'c.fk_mode_reglement' => 'Payment Mode',
			'c.model_pdf' => 'Model'
		);

		if (isModEnabled("multicurrency")) {
			$this->import_fields_array[$r]['c.multicurrency_code'] = 'Currency';
			$this->import_fields_array[$r]['c.multicurrency_tx'] = 'CurrencyRate';
			$this->import_fields_array[$r]['c.multicurrency_total_ht'] = 'MulticurrencyAmountHT';
			$this->import_fields_array[$r]['c.multicurrency_total_tva'] = 'MulticurrencyAmountVAT';
			$this->import_fields_array[$r]['c.multicurrency_total_ttc'] = 'MulticurrencyAmountTTC';
		}

		// Add extra fields
		$import_extrafield_sample = array();
		$sql = "SELECT name, label, fieldrequired FROM " . MAIN_DB_PREFIX . "extrafields WHERE type <> 'separate' AND elementtype = 'commande_fournisseur' AND entity IN (0, " . $conf->entity . ")";
		$resql = $this->db->query($sql);

		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$fieldname = 'extra.' . $obj->name;
				$fieldlabel = ucfirst($obj->label);
				$this->import_fields_array[$r][$fieldname] = $fieldlabel . ($obj->fieldrequired ? '*' : '');
				$import_extrafield_sample[$fieldname] = $fieldlabel;
			}
		}
		// End add extra fields

		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-' . MAIN_DB_PREFIX . 'commande_fournisseur');
		$this->import_regex_array[$r] = array(
			'c.multicurrency_code' => 'code@' . MAIN_DB_PREFIX . 'multicurrency'
		);

		$this->import_updatekeys_array[$r] = array('c.ref' => 'Ref');
		$this->import_convertvalue_array[$r] = array(
			'c.ref' => array(
				'rule' => 'getrefifauto',
				'class' => (!getDolGlobalString('COMMANDE_SUPPLIER_ADDON_NUMBER') ? 'mod_commande_fournisseur_muguet' : $conf->global->COMMANDE_SUPPLIER_ADDON_NUMBER),
				'path' => "/core/modules/supplier_order/" . (!getDolGlobalString('COMMANDE_SUPPLIER_ADDON_NUMBER') ? 'mod_commande_fournisseur_muguet' : $conf->global->COMMANDE_SUPPLIER_ADDON_NUMBER) . '.php',
				'classobject' => 'CommandeFournisseur',
				'pathobject' => '/fourn/class/fournisseur.commande.class.php',
			),
			'c.fk_soc' => array(
				'rule' => 'fetchidfromref',
				'file' => '/societe/class/societe.class.php',
				'class' => 'Societe',
				'method' => 'fetch',
				'element' => 'ThirdParty'
			),
			'c.fk_mode_reglement' => array(
				'rule' => 'fetchidfromcodeorlabel',
				'file' => '/compta/paiement/class/cpaiement.class.php',
				'class' => 'Cpaiement',
				'method' => 'fetch',
				'element' => 'cpayment'
			),
			'c.source' => array('rule' => 'zeroifnull'),
		);

		// Import PO Lines
		$r++;
		$this->import_code[$r] = 'commande_fournisseurdet_' . $r;
		$this->import_label[$r] = 'PurchaseOrderLines';
		$this->import_icon[$r] = 'supplier_order';
		$this->import_entities_array[$r] = array();
		$this->import_tables_array[$r] = array('cd' => MAIN_DB_PREFIX . 'commande_fournisseurdet', 'extra' => MAIN_DB_PREFIX . 'commande_fournisseurdet_extrafields');
		$this->import_fields_array[$r] = array(
			'cd.fk_commande' => 'PurchaseOrder*',
			'cd.fk_parent_line' => 'ParentLine',
			'cd.fk_product' => 'IdProduct',
			'cd.ref' => 'SupplierRef',
			'cd.description' => 'LineDescription',
			'cd.tva_tx' => 'LineVATRate',
			'cd.qty' => 'LineQty',
			'cd.remise_percent' => 'Reduc. Percent',
			'cd.subprice' => 'Sub Price',
			'cd.total_ht' => 'LineTotalHT',
			'cd.total_tva' => 'LineTotalVAT',
			'cd.total_ttc' => 'LineTotalTTC',
			'cd.product_type' => 'TypeOfLineServiceOrProduct',
			'cd.date_start' => 'Start Date',
			'cd.date_end' => 'End Date',
			'cd.info_bits' => 'InfoBits',
			'cd.special_code' => 'Special Code',
			'cd.rang' => 'LinePosition',
			'cd.fk_unit' => 'Unit'
		);

		if (isModEnabled("multicurrency")) {
			$this->import_fields_array[$r]['cd.multicurrency_code'] = 'Currency';
			$this->import_fields_array[$r]['cd.multicurrency_subprice'] = 'CurrencyRate';
			$this->import_fields_array[$r]['cd.multicurrency_total_ht'] = 'MulticurrencyAmountHT';
			$this->import_fields_array[$r]['cd.multicurrency_total_tva'] = 'MulticurrencyAmountVAT';
			$this->import_fields_array[$r]['cd.multicurrency_total_ttc'] = 'MulticurrencyAmountTTC';
		}

		// Add extra fields
		$sql = "SELECT name, label, fieldrequired FROM " . MAIN_DB_PREFIX . "extrafields WHERE type <> 'separate' AND elementtype = 'commande_fournisseurdet' AND entity IN (0, " . $conf->entity . ")";
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$fieldname = 'extra.' . $obj->name;
				$fieldlabel = ucfirst($obj->label);
				$this->import_fields_array[$r][$fieldname] = $fieldlabel . ($obj->fieldrequired ? '*' : '');
			}
		}
		// End add extra fields

		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-' . MAIN_DB_PREFIX . 'commande_fournisseurdet');
		$this->import_regex_array[$r] = array(
			'cd.product_type' => '[0|1]$',
			'cd.fk_product' => 'rowid@' . MAIN_DB_PREFIX . 'product',
			'cd.multicurrency_code' => 'code@' . MAIN_DB_PREFIX . 'multicurrency'
		);
		$this->import_updatekeys_array[$r] = array('cd.fk_commande' => 'Purchase Order Id', 'cd.rang' => 'LinePosition');
		$this->import_convertvalue_array[$r] = array(
			'cd.fk_commande' => array(
				'rule' => 'fetchidfromref',
				'file' => '/fourn/class/fournisseur.commande.class.php',
				'class' => 'CommandeFournisseur',
				'method' => 'fetch',
				'element' => 'order_supplier'
			),
			'cd.info_bits' => array('rule' => 'zeroifnull'),
			'cd.special_code' => array('rule' => 'zeroifnull'),
		);
	}


	/**
	 *        Function called when module is enabled.
	 *        The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *        It also creates data directories
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return     int                1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		$this->remove($options);

		$src = DOL_DOCUMENT_ROOT . '/install/doctemplates/supplier_orders/template_supplier_order.odt';
		$dirodt = DOL_DATA_ROOT . ($conf->entity > 1 ? '/' . $conf->entity : '') . '/doctemplates/supplier_orders';
		$dest = $dirodt . '/template_supplier_order.odt';

		if (file_exists($src) && !file_exists($dest)) {
			require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
			dol_mkdir($dirodt);
			$result = dol_copy($src, $dest, '0', 0);
			if ($result < 0) {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
				return 0;
			}
		}

		$sql_order = array(
			"DELETE FROM " . MAIN_DB_PREFIX . "document_model WHERE nom = '" . $this->db->escape($this->const[0][2]) . "' AND type = 'order_supplier' AND entity = " . ((int) $conf->entity),
			"INSERT INTO " . MAIN_DB_PREFIX . "document_model (nom, type, entity) VALUES('" . $this->db->escape($this->const[0][2]) . "', 'order_supplier', " . ((int) $conf->entity) . ")",
		);

		$sql = $sql_order;

		return $this->_init($sql, $options);
	}
}
