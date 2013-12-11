<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
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
 * 		\defgroup   facture     Module invoices
 *      \brief      Module pour gerer les factures clients et/ou fournisseurs
 *      \file       htdocs/core/modules/modFacture.class.php
 *		\ingroup    facture
 *		\brief      Fichier de la classe de description et activation du module Facture
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


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
	function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 30;

		$this->family = "financial";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des factures";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='bill';

		// Data directories to create when module is enabled
		$this->dirs = array("/facture/temp");

		// Dependencies
		$this->depends = array("modSociete");
		$this->requiredby = array("modComptabilite","modAccounting");
		$this->conflictwith = array();
		$this->langfiles = array("bills","companies","compta","products");

		// Config pages
		$this->config_page_url = array("facture.php");

		// Constantes
		$this->const = array();
		$r=0;

		$this->const[$r][0] = "FACTURE_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "crabe";
		$this->const[$r][3] = 'Name of PDF model of invoice';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "FACTURE_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_facture_terre";
		$this->const[$r][3] = 'Name of numbering numerotation rules of invoice';
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
				0=>array('file'=>'box_factures_imp.php','enabledbydefaulton'=>'Home'),
				1=>array('file'=>'box_factures.php','enabledbydefaulton'=>'Home'),
				2=>array('file'=>'box_graph_invoices_permonth.php','enabledbydefaulton'=>'Home')
		);

		// Permissions
		$this->rights = array();
		$this->rights_class = 'facture';
		$r=0;

		$r++;
		$this->rights[$r][0] = 11;
		$this->rights[$r][1] = 'Lire les factures';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 12;
		$this->rights[$r][1] = 'Creer/modifier les factures';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';

		// There is a particular permission for unvalidate because this may be not forbidden by some laws
		$r++;
		$this->rights[$r][0] = 13;
		$this->rights[$r][1] = 'Dévalider les factures';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'invoice_advance';
		$this->rights[$r][5] = 'unvalidate';

		$r++;
		$this->rights[$r][0] = 14;
		$this->rights[$r][1] = 'Valider les factures';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'valider';

		$r++;
		$this->rights[$r][0] = 15;
		$this->rights[$r][1] = 'Envoyer les factures par mail';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'invoice_advance';
        $this->rights[$r][5] = 'send';

		$r++;
		$this->rights[$r][0] = 16;
		$this->rights[$r][1] = 'Emettre des paiements sur les factures';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'paiement';

		$r++;
		$this->rights[$r][0] = 19;
		$this->rights[$r][1] = 'Supprimer les factures';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 1321;
		$this->rights[$r][1] = 'Exporter les factures clients, attributs et reglements';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'facture';
		$this->rights[$r][5] = 'export';


		// Exports
		//--------
		$r=1;

		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='CustomersInvoicesAndInvoiceLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='bill';
		$this->export_permission[$r]=array(array("facture","facture","export"));
		$this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','c.code'=>'CountryCode','s.phone'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode','s.tva_intra'=>'VATIntra','f.rowid'=>"InvoiceId",'f.facnumber'=>"InvoiceRef",'f.datec'=>"InvoiceDateCreation",'f.datef'=>"DateInvoice",'f.date_lim_reglement'=>"DateDue",'f.total'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.tva'=>"TotalVAT",'f.paye'=>"InvoicePaid",'f.fk_statut'=>'InvoiceStatus','f.note_private'=>"NotePrivate",'f.note_public'=>"NotePublic",'fd.rowid'=>'LineId','fd.label'=>"Label",'fd.description'=>"LineDescription",'fd.price'=>"LineUnitPrice",'fd.tva_tx'=>"LineVATRate",'fd.qty'=>"LineQty",'fd.total_ht'=>"LineTotalHT",'fd.total_tva'=>"LineTotalVAT",'fd.total_ttc'=>"LineTotalTTC",'fd.date_start'=>"DateStart",'fd.date_end'=>"DateEnd",'fd.product_type'=>"TypeOfLineServiceOrProduct",'fd.fk_product'=>'ProductId','p.ref'=>'ProductRef','p.label'=>'ProductLabel','p.accountancy_code_sell'=>'ProductAccountancyBuyCode');
		//$this->export_TypeFields_array[$r]=array('s.rowid'=>"List:societe:nom",'s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','c.code'=>'Text','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','s.code_compta'=>'Text','s.code_compta_fournisseur'=>'Text','s.tva_intra'=>'Text','f.facnumber'=>"Text",'f.datec'=>"Date",'f.datef'=>"Date",'f.date_lim_reglement'=>"Date",'f.total'=>"Numeric",'f.total_ttc'=>"Numeric",'f.tva'=>"Numeric",'f.paye'=>"Boolean",'f.fk_statut'=>'Status','f.note_private'=>"Text",'f.note_public'=>"Text",'fd.description'=>"Text",'fd.price'=>"Numeric",'fd.tva_tx'=>"Numeric",'fd.qty'=>"Numeric",'fd.total_ht'=>"Numeric",'fd.total_tva'=>"Numeric",'fd.total_ttc'=>"Numeric",'fd.date_start'=>"Date",'fd.date_end'=>"Date",'fd.product_type'=>"Numeric",'fd.fk_product'=>'List:Product:label','p.ref'=>'Text','p.label'=>'Text');
		$this->export_TypeFields_array[$r]=array('s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','c.code'=>'Text','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','s.code_compta'=>'Text','s.code_compta_fournisseur'=>'Text','s.tva_intra'=>'Text','f.facnumber'=>"Text",'f.datec'=>"Date",'f.datef'=>"Date",'f.date_lim_reglement'=>"Date",'f.total'=>"Numeric",'f.total_ttc'=>"Numeric",'f.tva'=>"Numeric",'f.paye'=>"Boolean",'f.fk_statut'=>'Status','f.note_private'=>"Text",'f.note_public'=>"Text",'fd.description'=>"Text",'fd.price'=>"Numeric",'fd.tva_tx'=>"Numeric",'fd.qty'=>"Numeric",'fd.total_ht'=>"Numeric",'fd.total_tva'=>"Numeric",'fd.total_ttc'=>"Numeric",'fd.date_start'=>"Date",'fd.date_end'=>"Date",'fd.product_type'=>"Numeric",'fd.fk_product'=>'List:Product:label','p.ref'=>'Text','p.label'=>'Text','p.accountancy_code_sell'=>'Text');
		$this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','c.code'=>'company','s.phone'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','s.tva_intra'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.date_lim_reglement'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note_private'=>"invoice",'f.note_public'=>"invoice",'fd.rowid'=>'invoice_line','fd.label'=>"invoice_line",'fd.description'=>"invoice_line",'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_tx'=>"invoice_line",'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.product_type'=>'invoice_line','fd.fk_product'=>'product','p.ref'=>'product','p.label'=>'product','p.accountancy_code_sell'=>'product');
		$this->export_dependencies_array[$r]=array('invoice_line'=>'fd.rowid','product'=>'fd.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'societe as s';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_pays as c on s.fk_pays = c.rowid,';
		$this->export_sql_end[$r] .=' '.MAIN_DB_PREFIX.'facture as f,';
		$this->export_sql_end[$r] .=' '.MAIN_DB_PREFIX.'facturedet as fd';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		$this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture';
		$this->export_sql_end[$r] .=' AND f.entity = '.$conf->entity;
		$r++;

		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='CustomersInvoicesAndPayments';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='bill';
		$this->export_permission[$r]=array(array("facture","facture","export"));
		$this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','c.code'=>'CountryCode','s.phone'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode','s.tva_intra'=>'VATIntra','f.rowid'=>"InvoiceId",'f.facnumber'=>"InvoiceRef",'f.datec'=>"InvoiceDateCreation",'f.datef'=>"DateInvoice",'f.date_lim_reglement'=>"DateDue",'f.total'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.tva'=>"TotalVAT",'f.paye'=>"InvoicePaid",'f.fk_statut'=>'InvoiceStatus','f.note_private'=>"NotePrivate",'f.note_public'=>"NotePublic",'p.rowid'=>'PaymentId','pf.amount'=>'AmountPayment','p.datep'=>'DatePayment','p.num_paiement'=>'PaymentNumber');
		//$this->export_TypeFields_array[$r]=array('s.rowid'=>"List:societe:nom",'s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','c.code'=>'Text','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','s.code_compta'=>'Text','s.code_compta_fournisseur'=>'Text','s.tva_intra'=>'Text','f.rowid'=>"List:facture:facnumber",'f.facnumber'=>"Text",'f.datec'=>"Date",'f.datef'=>"Date",'f.date_lim_reglement'=>"Date",'f.total'=>"Number",'f.total_ttc'=>"Number",'f.tva'=>"Number",'f.paye'=>"Boolean",'f.fk_statut'=>'Status','f.note_private'=>"Text",'f.note_public'=>"Text",'pf.amount'=>'Number','p.datep'=>'Date','p.num_paiement'=>'Number');
		$this->export_TypeFields_array[$r]=array('s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','c.code'=>'Text','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','s.code_compta'=>'Text','s.code_compta_fournisseur'=>'Text','s.tva_intra'=>'Text','f.rowid'=>"List:facture:facnumber",'f.facnumber'=>"Text",'f.datec'=>"Date",'f.datef'=>"Date",'f.date_lim_reglement'=>"Date",'f.total'=>"Number",'f.total_ttc'=>"Number",'f.tva'=>"Number",'f.paye'=>"Boolean",'f.fk_statut'=>'Status','f.note_private'=>"Text",'f.note_public'=>"Text",'pf.amount'=>'Number','p.datep'=>'Date','p.num_paiement'=>'Number');
		$this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','c.code'=>'company','s.phone'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','s.tva_intra'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.date_lim_reglement'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note_private'=>"invoice",'f.note_public'=>"invoice",'p.rowid'=>'payment','pf.amount'=>'payment','p.datep'=>'payment','p.num_paiement'=>'payment');
		$this->export_dependencies_array[$r]=array('payment'=>'p.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'societe as s';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_pays as c on s.fk_pays = c.rowid,';
		$this->export_sql_end[$r] .=' '.MAIN_DB_PREFIX.'facture as f';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'paiement as p ON pf.fk_paiement = p.rowid';
		$this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid';
		$this->export_sql_end[$r] .=' AND f.entity = '.$conf->entity;
		$r++;
	}


	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'newboxdefonly', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf,$langs;

		// Remove permissions and default values
		$this->remove($options);

		//ODT template
		$src=DOL_DOCUMENT_ROOT.'/install/doctemplates/invoices/template_invoice.odt';
		$dirodt=DOL_DATA_ROOT.'/doctemplates/invoices';
		$dest=$dirodt.'/template_invoice.odt';

		if (file_exists($src) && ! file_exists($dest))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_mkdir($dirodt);
			$result=dol_copy($src,$dest,0,0);
			if ($result < 0)
			{
				$langs->load("errors");
				$this->error=$langs->trans('ErrorFailToCopyFile',$src,$dest);
				return 0;
			}
		}

		$sql = array(
				"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->const[0][2]."' AND entity = ".$conf->entity,
				"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->const[0][2]."','invoice',".$conf->entity.")"
		);

		return $this->_init($sql,$options);
	}

    /**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
     *      @param      string	$options    Options when enabling module ('', 'newboxdefonly', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
     */
    function remove($options='')
    {
		$sql = array();

		return $this->_remove($sql,$options);
    }

}
?>
