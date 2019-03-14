<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012      Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013-2015 Philippe Grand	    <philippe.grand@atoo-net.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 		\defgroup   fournisseur     Module suppliers
 *		\file       htdocs/core/modules/modFournisseur.class.php
 *		\ingroup    fournisseur
 *		\brief      Description and activation file for module Supplier
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


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
	function __construct($db)
	{
		global $conf, $user;

		$this->db = $db;
		$this->numero = 40;

		// Family can be 'crm','financial','hr','projects','product','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "srm";
		$this->module_position = '10';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des fournisseurs";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto='company';

		// Data directories to create when module is enabled
		$this->dirs = array("/fournisseur/temp",
							"/fournisseur/commande",
		                    "/fournisseur/commande/temp",
		                    "/fournisseur/facture",
		                    "/fournisseur/facture/temp"
		                    );

		// Dependencies
		$this->depends = array("modSociete");
		$this->requiredby = array();
		$this->langfiles = array('bills', 'companies', 'suppliers', 'orders', 'sendings');

		// Config pages
		$this->config_page_url = array("supplier_order.php");

		// Constants
		$this->const = array();
		$r=0;

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

		// Boxes
		$this->boxes = array(
		0=>array('file'=>'box_graph_invoices_supplier_permonth.php','enabledbydefaulton'=>'Home'),
		1=>array('file'=>'box_graph_orders_supplier_permonth.php','enabledbydefaulton'=>'Home'),
		2=>array('file'=>'box_fournisseurs.php','enabledbydefaulton'=>'Home'),
		3=>array('file'=>'box_factures_fourn_imp.php','enabledbydefaulton'=>'Home'),
		4=>array('file'=>'box_factures_fourn.php','enabledbydefaulton'=>'Home'),
		5=>array('file'=>'box_supplier_orders.php','enabledbydefaulton'=>'Home'),
		);

		// Permissions
		$this->rights = array();
		$this->rights_class = 'fournisseur';
		$r=0;

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

		if (! empty($conf->global->SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED))
		{
			$r++;
			$this->rights[$r][0] = 1190;
			$this->rights[$r][1] = 'Approve supplier order (second level)';		// $langs->trans("Permission1190");
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
	    $this->menu = 1;        // This module add menu entries. They are coded into menu manager.


		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Factures fournisseurs et lignes de facture';
		$this->export_icon[$r]='bill';
		$this->export_permission[$r]=array(array("fournisseur","facture","export"));
		$this->export_fields_array[$r]=array(
			's.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','c.code'=>'CountryCode','s.phone'=>'Phone',
			's.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.idprof5'=>'ProfId5','s.idprof6'=>'ProfId6','s.tva_intra'=>'VATIntra',
			'f.rowid'=>"InvoiceId",'f.ref'=>"InvoiceRef",'f.ref_supplier'=>"RefSupplier",'f.datec'=>"InvoiceDateCreation",'f.datef'=>"DateInvoice",'f.date_lim_reglement'=>'DateMaxPayment',
			'f.total_ht'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.total_tva'=>"TotalVAT",'f.paye'=>"InvoicePaid",'f.fk_statut'=>'InvoiceStatus','f.note_public'=>"InvoiceNote",
			'fd.rowid'=>'LineId','fd.description'=>"LineDescription",'fd.tva_tx'=>"LineVATRate",'fd.qty'=>"LineQty",'fd.remise_percent'=>"Discount",'fd.total_ht'=>"LineTotalHT",
			'fd.total_ttc'=>"LineTotalTTC",'fd.tva'=>"LineTotalVAT",'fd.product_type'=>'TypeOfLineServiceOrProduct','fd.fk_product'=>'ProductId',
			'p.ref'=>'ProductRef','p.label'=>'ProductLabel','p.accountancy_code_buy'=>'ProductAccountancyBuyCode','project.rowid'=>'ProjectId',
			'project.ref'=>'ProjectRef','project.title'=>'ProjectLabel'
		);
		//$this->export_TypeFields_array[$r]=array(
		//    's.rowid'=>"List:societe:CompanyName",'s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','c.code'=>'Text','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text',
		//    's.ape'=>'Text','s.idprof4'=>'Text','s.tva_intra'=>'Text','f.ref'=>"Text",'f.datec'=>"Date",'f.datef'=>"Date",'f.total_ht'=>"Numeric",'f.total_ttc'=>"Numeric",'f.total_tva'=>"Numeric",
		//    'f.paye'=>"Boolean",'f.fk_statut'=>'Status','f.note_public'=>"Text",'fd.description'=>"Text",'fd.tva_tx'=>"Text",'fd.qty'=>"Numeric",'fd.total_ht'=>"Numeric",'fd.total_ttc'=>"Numeric",
		//     'fd.tva'=>"Numeric",'fd.product_type'=>'Numeric','fd.fk_product'=>'List:product:label','p.ref'=>'Text','p.label'=>'Text'
		//);
		$this->export_TypeFields_array[$r]=array(
			's.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','c.code'=>'Text','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text',
			's.tva_intra'=>'Text','f.ref'=>"Text",'f.ref_supplier'=>"Text",'f.datec'=>"Date",'f.datef'=>"Date",'f.date_lim_reglement'=>'Date','f.total_ht'=>"Numeric",'f.total_ttc'=>"Numeric",
			'f.total_tva'=>"Numeric",'f.paye'=>"Boolean",'f.fk_statut'=>'Status','f.note_public'=>"Text",'fd.description'=>"Text",'fd.tva_tx'=>"Text",'fd.qty'=>"Numeric",'fd.total_ht'=>"Numeric",
			'fd.total_ttc'=>"Numeric",'fd.tva'=>"Numeric",'fd.product_type'=>'Numeric','fd.fk_product'=>'List:product:label',
			'p.ref'=>'Text','p.label'=>'Text','project.ref'=>'Text','project.title'=>'Text'
		);
		$this->export_entities_array[$r]=array(
			's.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','c.code'=>'company','s.phone'=>'company','s.siren'=>'company','s.siret'=>'company',
			's.ape'=>'company','s.idprof4'=>'company','s.idprof5'=>'company','s.idprof6'=>'company','s.tva_intra'=>'company','f.rowid'=>"invoice",'f.ref'=>"invoice",'f.ref_supplier'=>"invoice",
			'f.datec'=>"invoice",'f.datef'=>"invoice",'f.date_lim_reglement'=>'invoice','f.total_ht'=>"invoice",'f.total_ttc'=>"invoice",'f.total_tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice',
			'f.note_public'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.tva_tx'=>"invoice_line",'fd.qty'=>"invoice_line",'fd.remise_percent'=>"invoice_line",
			'fd.total_ht'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva'=>"invoice_line",'fd.product_type'=>'invoice_line','fd.fk_product'=>'product',
			'p.ref'=>'product','p.label'=>'product','p.accountancy_code_buy'=>'product','project.rowid'=>'project','project.ref'=>'project','project.title'=>'project'
		);
		$this->export_dependencies_array[$r]=array('invoice_line'=>'fd.rowid','product'=>'fd.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them
		// Add extra fields object
		$sql="SELECT name, label, type, param FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'facture_fourn'";
		$resql=$this->db->query($sql);
		if ($resql)    // This can fail when class is used on old database (during migration for example)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$fieldname='extra.'.$obj->name;
				$fieldlabel=ucfirst($obj->label);
				$typeFilter="Text";
				switch($obj->type)
				{
					case 'int':
					case 'double':
					case 'price':
						$typeFilter="Numeric";
						break;
					case 'date':
					case 'datetime':
						$typeFilter="Date";
						break;
					case 'boolean':
						$typeFilter="Boolean";
						break;
					case 'sellist':
						$tmp='';
						$tmpparam=unserialize($obj->param);	// $tmp ay be array 'options' => array 'c_currencies:code_iso:code_iso' => null
						if ($tmpparam['options'] && is_array($tmpparam['options'])) $tmp=array_shift(array_keys($tmpparam['options']));
						if (preg_match('/[a-z0-9_]+:[a-z0-9_]+:[a-z0-9_]+/', $tmp)) $typeFilter="List:".$tmp;
						break;
				}
				$this->export_fields_array[$r][$fieldname]=$fieldlabel;
				$this->export_TypeFields_array[$r][$fieldname]=$typeFilter;
				$this->export_entities_array[$r][$fieldname]='invoice';
			}
		}
		// End add extra fields
		// Add extra fields line
		$sql="SELECT name, label, type, param FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'facture_fourn_det'";
		$resql=$this->db->query($sql);
		if ($resql)    // This can fail when class is used on old database (during migration for example)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$fieldname='extraline.'.$obj->name;
				$fieldlabel=ucfirst($obj->label);
				$typeFilter="Text";
				switch($obj->type)
				{
					case 'int':
					case 'double':
					case 'price':
						$typeFilter="Numeric";
						break;
					case 'date':
					case 'datetime':
						$typeFilter="Date";
						break;
					case 'boolean':
						$typeFilter="Boolean";
						break;
					case 'sellist':
						$tmp='';
						$tmpparam=unserialize($obj->param);	// $tmp ay be array 'options' => array 'c_currencies:code_iso:code_iso' => null
						if ($tmpparam['options'] && is_array($tmpparam['options'])) $tmp=array_shift(array_keys($tmpparam['options']));
						if (preg_match('/[a-z0-9_]+:[a-z0-9_]+:[a-z0-9_]+/', $tmp)) $typeFilter="List:".$tmp;
						break;
				}
				$this->export_fields_array[$r][$fieldname]=$fieldlabel;
				$this->export_TypeFields_array[$r][$fieldname]=$typeFilter;
				$this->export_entities_array[$r][$fieldname]='invoice_line';
			}
		}
		// End add extra fields line
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'societe as s';
		if (is_object($user) && empty($user->rights->societe->client->voir)) $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe_commerciaux as sc ON sc.fk_soc = s.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON s.fk_pays = c.rowid,';
		$this->export_sql_end[$r] .=' '.MAIN_DB_PREFIX.'facture_fourn as f';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'projet as project on (f.fk_projet = project.rowid)';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn_extrafields as extra ON f.rowid = extra.fk_object';
		$this->export_sql_end[$r] .=' , '.MAIN_DB_PREFIX.'facture_fourn_det as fd';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn_det_extrafields as extraline ON fd.rowid = extraline.fk_object';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		$this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture_fourn';
		$this->export_sql_end[$r] .=' AND f.entity IN ('.getEntity('supplier_invoice').')';
		if (is_object($user) && empty($user->rights->societe->client->voir)) $this->export_sql_end[$r] .=' AND sc.fk_user = '.$user->id;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Factures fournisseurs et reglements';
		$this->export_icon[$r]='bill';
		$this->export_permission[$r]=array(array("fournisseur","facture","export"));
		$this->export_fields_array[$r]=array(
			's.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','c.code'=>'CountryCode','s.phone'=>'Phone',
			's.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.idprof5'=>'ProfId5','s.idprof6'=>'ProfId6',
			's.tva_intra'=>'VATIntra','f.rowid'=>"InvoiceId",'f.ref'=>"InvoiceRef",'f.ref_supplier'=>"RefSupplier",'f.datec'=>"InvoiceDateCreation",
			'f.datef'=>"DateInvoice",'f.total_ht'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.total_tva'=>"TotalVAT",'f.paye'=>"InvoicePaid",
			'f.fk_statut'=>'InvoiceStatus','f.note_public'=>"InvoiceNote",'p.rowid'=>'PaymentId','pf.amount'=>'AmountPayment',
			'p.datep'=>'DatePayment','p.num_paiement'=>'PaymentNumber','project.rowid'=>'ProjectId','project.ref'=>'ProjectRef','project.title'=>'ProjectLabel'
		);
		//$this->export_TypeFields_array[$r]=array(
		//	's.rowid'=>"List:societe:CompanyName",'s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','c.code'=>'Text','s.phone'=>'Text',
		//	's.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','s.tva_intra'=>'Text','f.ref'=>"Text",'f.datec'=>"Date",'f.datef'=>"Date",
		//	'f.total_ht'=>"Numeric",'f.total_ttc'=>"Numeric",'f.total_tva'=>"Numeric",'f.paye'=>"Boolean",'f.fk_statut'=>'Status','f.note_public'=>"Text",
		//	'pf.amount'=>'Numeric','p.datep'=>'Date','p.num_paiement'=>'Numeric'
		//);
		$this->export_TypeFields_array[$r]=array(
			's.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','c.code'=>'Text','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text',
			's.idprof4'=>'Text','s.tva_intra'=>'Text','f.ref'=>"Text",'f.ref_supplier'=>"Text",'f.datec'=>"Date",'f.datef'=>"Date",'f.total_ht'=>"Numeric",
			'f.total_ttc'=>"Numeric",'f.total_tva'=>"Numeric",'f.paye'=>"Boolean",'f.fk_statut'=>'Status','f.note_public'=>"Text",'pf.amount'=>'Numeric',
			'p.datep'=>'Date','p.num_paiement'=>'Numeric','project.ref'=>'Text','project.title'=>'Text'
		);
		$this->export_entities_array[$r]=array(
			's.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','c.code'=>'company','s.phone'=>'company',
			's.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.idprof5'=>'company','s.idprof6'=>'company','s.tva_intra'=>'company',
			'f.rowid'=>"invoice",'f.ref'=>"invoice",'f.ref_supplier'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total_ht'=>"invoice",
			'f.total_ttc'=>"invoice",'f.total_tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note_public'=>"invoice",'p.rowid'=>'payment','pf.amount'=>'payment',
		    'p.datep'=>'payment','p.num_paiement'=>'payment','project.rowid'=>'project','project.ref'=>'project','project.title'=>'project');
		$this->export_dependencies_array[$r]=array('payment'=>'p.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them
		// Add extra fields object
		$sql="SELECT name, label, type, param FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'facture_fourn'";
		$resql=$this->db->query($sql);
		if ($resql)    // This can fail when class is used on old database (during migration for example)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$fieldname='extra.'.$obj->name;
				$fieldlabel=ucfirst($obj->label);
				$typeFilter="Text";
				switch($obj->type)
				{
					case 'int':
					case 'double':
					case 'price':
						$typeFilter="Numeric";
						break;
					case 'date':
					case 'datetime':
						$typeFilter="Date";
						break;
					case 'boolean':
						$typeFilter="Boolean";
						break;
					case 'sellist':
						$tmp='';
						$tmpparam=unserialize($obj->param);	// $tmp ay be array 'options' => array 'c_currencies:code_iso:code_iso' => null
						if ($tmpparam['options'] && is_array($tmpparam['options'])) $tmp=array_shift(array_keys($tmpparam['options']));
						if (preg_match('/[a-z0-9_]+:[a-z0-9_]+:[a-z0-9_]+/', $tmp)) $typeFilter="List:".$tmp;
						break;
				}
				$this->export_fields_array[$r][$fieldname]=$fieldlabel;
				$this->export_TypeFields_array[$r][$fieldname]=$typeFilter;
				$this->export_entities_array[$r][$fieldname]='invoice';
			}
		}
		// End add extra fields object
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'societe as s';
		if (is_object($user) && empty($user->rights->societe->client->voir)) $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe_commerciaux as sc ON sc.fk_soc = s.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON s.fk_pays = c.rowid,';
		$this->export_sql_end[$r] .=' '.MAIN_DB_PREFIX.'facture_fourn as f';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'projet as project on (f.fk_projet = project.rowid)';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'facture_fourn_extrafields as extra ON f.rowid = extra.fk_object';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf ON pf.fk_facturefourn = f.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn as p ON pf.fk_paiementfourn = p.rowid';
		$this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid';
        $this->export_sql_end[$r] .=' AND f.entity IN ('.getEntity('supplier_invoice').')';
		if (is_object($user) && empty($user->rights->societe->client->voir)) $this->export_sql_end[$r] .=' AND sc.fk_user = '.$user->id;

		// Order
		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Commandes fournisseurs et lignes de commandes';
		$this->export_icon[$r]='order';
		$this->export_permission[$r]=array(array("fournisseur","commande","export"));
		$this->export_fields_array[$r]=array(
			's.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','c.code'=>'CountryCode','s.phone'=>'Phone',
			's.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.idprof5'=>'ProfId5','s.idprof6'=>'ProfId6','s.tva_intra'=>'VATIntra',
			'f.rowid'=>"OrderId",'f.ref'=>"Ref",'f.ref_supplier'=>"RefSupplier",'f.date_creation'=>"DateCreation",'f.date_commande'=>"OrderDate",'f.date_livraison'=>"DateDeliveryPlanned",
			'f.total_ht'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.tva'=>"TotalVAT",'f.fk_statut'=>'Status','f.date_approve'=>'DateApprove','f.date_approve2'=>'DateApprove2',
			'f.note_public'=>"NotePublic",'f.note_private'=>"NotePrivate",'ua1.login'=>'ApprovedBy','ua2.login'=>'ApprovedBy2','fd.rowid'=>'LineId','fd.description'=>"LineDescription",
			'fd.tva_tx'=>"LineVATRate",'fd.qty'=>"LineQty",'fd.remise_percent'=>"Discount",'fd.total_ht'=>"LineTotalHT",'fd.total_ttc'=>"LineTotalTTC",
			'fd.total_tva'=>"LineTotalVAT",'fd.product_type'=>'TypeOfLineServiceOrProduct','fd.ref'=>'RefSupplier','fd.fk_product'=>'ProductId',
			'p.ref'=>'ProductRef','p.label'=>'ProductLabel','project.rowid'=>'ProjectId','project.ref'=>'ProjectRef','project.title'=>'ProjectLabel'
		);
		if (empty($conf->global->SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED))
		{
			unset($this->export_fields_array['f.date_approve2']);
			unset($this->export_fields_array['ua2.login']);
		}
		$this->export_TypeFields_array[$r]=array(
			's.rowid'=>"company",'s.nom'=>'Text','s.address'=>'Text','s.cp'=>'Text','s.ville'=>'Text','c.code'=>'Text','s.tel'=>'Text','s.siren'=>'Text',
			's.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','s.idprof5'=>'Text','s.idprof6'=>'Text','s.tva_intra'=>'Text','f.ref'=>"Text",'f.ref_supplier'=>"Text",
			'f.date_creation'=>"Date",'f.date_commande'=>"Date",'f.date_livraison'=>"Date",'f.total_ht'=>"Numeric",'f.total_ttc'=>"Numeric",'f.tva'=>"Numeric",
			'f.fk_statut'=>'Status','f.date_approve'=>'Date','f.date_approve2'=>'Date','f.note_public'=>"Text",'f.note_private'=>"Text",'fd.description'=>"Text",
			'fd.tva_tx'=>"Numeric",'fd.qty'=>"Numeric",'fd.remise_percent'=>"Numeric",'fd.total_ht'=>"Numeric",'fd.total_ttc'=>"Numeric",'fd.total_tva'=>"Numeric",
			'fd.product_type'=>'Numeric','fd.ref'=>'Text','fd.fk_product'=>'List:product:label','p.ref'=>'Text','p.label'=>'Text','project.ref'=>'Text','project.title'=>'Text'
		);
		$this->export_entities_array[$r]=array(
			's.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','c.code'=>'company','s.phone'=>'company','s.siren'=>'company',
			's.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.idprof5'=>'company','s.idprof6'=>'company','s.tva_intra'=>'company','ua1.login'=>'user',
			'ua2.login'=>'user','fd.rowid'=>'order_line','fd.description'=>"order_line",'fd.tva_tx'=>"order_line",'fd.qty'=>"order_line",'fd.remise_percent'=>"order_line",
			'fd.total_ht'=>"order_line",'fd.total_ttc'=>"order_line",'fd.total_tva'=>"order_line",'fd.product_type'=>'order_line','fd.ref'=>'order_line','fd.fk_product'=>'product',
			'p.ref'=>'product','p.label'=>'product','project.rowid'=>'project','project.ref'=>'project','project.title'=>'project'
		);
		$this->export_dependencies_array[$r]=array('order_line'=>'fd.rowid','product'=>'fd.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them
		// Add extra fields object
		$sql="SELECT name, label, type, param FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'commande_fournisseur'";
		$resql=$this->db->query($sql);
		if ($resql)    // This can fail when class is used on old database (during migration for example)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$fieldname='extra.'.$obj->name;
				$fieldlabel=ucfirst($obj->label);
				$typeFilter="Text";
				switch($obj->type)
				{
					case 'int':
					case 'double':
					case 'price':
						$typeFilter="Numeric";
						break;
					case 'date':
					case 'datetime':
						$typeFilter="Date";
						break;
					case 'boolean':
						$typeFilter="Boolean";
						break;
					case 'sellist':
						$tmp='';
						$tmpparam=unserialize($obj->param);	// $tmp ay be array 'options' => array 'c_currencies:code_iso:code_iso' => null
						$tmpkey=array_keys($tmpparam['options']);
						if ($tmpparam['options'] && is_array($tmpparam['options'])) $tmp=array_shift($tmpkey);
						if (preg_match('/[a-z0-9_]+:[a-z0-9_]+:[a-z0-9_]+/', $tmp)) $typeFilter="List:".$tmp;
						break;
				}
				$this->export_fields_array[$r][$fieldname]=$fieldlabel;
				$this->export_TypeFields_array[$r][$fieldname]=$typeFilter;
				$this->export_entities_array[$r][$fieldname]='order';
			}
		}
		// End add extra fields object
		// Add extra fields line
		$sql="SELECT name, label, type, param FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'commande_fournisseurdet'";
		$resql=$this->db->query($sql);
		if ($resql)    // This can fail when class is used on old database (during migration for example)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$fieldname='extraline.'.$obj->name;
				$fieldlabel=ucfirst($obj->label);
				$typeFilter="Text";
				switch($obj->type)
				{
					case 'int':
					case 'double':
					case 'price':
						$typeFilter="Numeric";
						break;
					case 'date':
					case 'datetime':
						$typeFilter="Date";
						break;
					case 'boolean':
						$typeFilter="Boolean";
						break;
					case 'sellist':
						$tmp='';
						$tmpparam=unserialize($obj->param);	// $tmp ay be array 'options' => array 'c_currencies:code_iso:code_iso' => null

						if ($tmpparam['options'] && is_array($tmpparam['options'])) {
							$tmpparam_param_key=array_keys($tmpparam['options']);
							$tmp=array_shift($tmpparam_param_key);
						}
						if (preg_match('/[a-z0-9_]+:[a-z0-9_]+:[a-z0-9_]+/', $tmp)) $typeFilter="List:".$tmp;
						break;
				}
				$this->export_fields_array[$r][$fieldname]=$fieldlabel;
				$this->export_TypeFields_array[$r][$fieldname]=$typeFilter;
				$this->export_entities_array[$r][$fieldname]='order_line';
			}
		}
		// End add extra fields line
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'societe as s';
		if (is_object($user) && empty($user->rights->societe->client->voir)) $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe_commerciaux as sc ON sc.fk_soc = s.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON s.fk_pays = c.rowid,';
		$this->export_sql_end[$r] .=' '.MAIN_DB_PREFIX.'commande_fournisseur as f';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'projet as project on (f.fk_projet = project.rowid)';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'user as ua1 ON ua1.rowid = f.fk_user_approve';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'user as ua2 ON ua2.rowid = f.fk_user_approve2';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'commande_fournisseur_extrafields as extra ON f.rowid = extra.fk_object,';
		$this->export_sql_end[$r] .=' '.MAIN_DB_PREFIX.'commande_fournisseurdet as fd';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'commande_fournisseurdet_extrafields as extraline ON fd.rowid = extraline.fk_object';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		$this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_commande';
		$this->export_sql_end[$r] .=' AND f.entity IN ('.getEntity('supplier_order').')';
		if (is_object($user) && empty($user->rights->societe->client->voir)) $this->export_sql_end[$r] .=' AND sc.fk_user = '.$user->id;
	}


	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf;

		$this->remove($options);

		$sql = array(
			 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[0][2])."' AND type = 'order_supplier' AND entity = ".$conf->entity,
			 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[0][2])."','order_supplier',".$conf->entity.")",
		);

		return $this->_init($sql,$options);
	}
}
