<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2012		Juanjo Menent			<jmenent@2byte.es>
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
 *	\defgroup   propale     Module commercial proposals
 *	\brief      Module pour gerer la tenue de propositions commerciales
 *	\file       htdocs/core/modules/modPropale.class.php
 *	\ingroup    propale
 *	\brief      Fichier de description et activation du module Propale
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Classe de description et activation du module Propale
 */
class modAskPriceSupplier extends DolibarrModules
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
		$this->numero = 999999;

		$this->family = "products";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "askpricesupplierDESC";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='askpricesupplier';

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Dependancies
		$this->depends = array('modFournisseur');
		$this->requiredby = array();
		$this->config_page_url = array("askpricesupplier.php");
		$this->langfiles = array("askpricesupplier");

		// Constants
		$this->const = array();
		$r=0;

		$this->const[$r][0] = "ASKPRICESUPPLIER_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "aurore";
		$this->const[$r][3] = 'Nom du gestionnaire de generation des demandes de prix fournisseurs en PDF';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "ASKPRICESUPPLIER_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_askpricesupplier_marbre";
		$this->const[$r][3] = 'Nom du gestionnaire de numerotation des demandes de prix fournisseurs';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "ASKPRICESUPPLIER_VALIDITY_DURATION";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "15";
		$this->const[$r][3] = 'Durée de validitée des demandes de prix fournisseurs';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "ASKPRICESUPPLIER_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/askpricesupplier";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'askpricesupplier';
		$r=0;

		$r++;
		$this->rights[$r][0] = $this->numero + $r; // id de la permission
		$this->rights[$r][1] = 'Lire les demandes fournisseurs'; // libelle de la permission
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';
		
		$r++;
		$this->rights[$r][0] = $this->numero + $r; // id de la permission
		$this->rights[$r][1] = 'Créer/modifier les demandes fournisseurs'; // libelle de la permission
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';
		

		$r++;
		$this->rights[$r][0] = $this->numero + $r; // id de la permission
		$this->rights[$r][1] = 'Valider les demandes fournisseurs'; // libelle de la permission
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = '';
		$this->rights[$r][5] = 'validate';
		
		$r++;
		$this->rights[$r][0] = $this->numero + $r; // id de la permission
		$this->rights[$r][1] = 'Envoyer les demandes fournisseurs'; // libelle de la permission
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = '';
        $this->rights[$r][5] = 'send';
		
		$r++;
		$this->rights[$r][0] = $this->numero + $r; // id de la permission
		$this->rights[$r][1] = 'Supprimer les demandes fournisseurs'; // libelle de la permission
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'supprimer';
		
		$r++;
		$this->rights[$r][0] = $this->numero + $r; // id de la permission
		$this->rights[$r][1] = 'Cloturer les demandes de prix fournisseurs'; // libelle de la permission
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'cloturer';

		// Exports
		//--------
		$r=0;
/*
		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='ProposalsAndProposalsLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("propale","export"));
		$this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','co.code'=>'CountryCode','s.phone'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','c.rowid'=>"Id",'c.ref'=>"Ref",'c.ref_client'=>"RefCustomer",'c.fk_soc'=>"IdCompany",'c.datec'=>"DateCreation",'c.datep'=>"DatePropal",'c.fin_validite'=>"DateEndPropal",'c.remise_percent'=>"GlobalDiscount",'c.total_ht'=>"TotalHT",'c.total'=>"TotalTTC",'c.fk_statut'=>'Status','c.note_public'=>"Note",'c.date_livraison'=>'DeliveryDate','c.fk_user_author'=>'CreatedById','uc.login'=>'CreatedByLogin','c.fk_user_valid'=>'ValidatedById','uv.login'=>'ValidatedByLogin','cd.rowid'=>'LineId','cd.label'=>"Label",'cd.description'=>"LineDescription",'cd.product_type'=>'TypeOfLineServiceOrProduct','cd.tva_tx'=>"LineVATRate",'cd.qty'=>"LineQty",'cd.total_ht'=>"LineTotalHT",'cd.total_tva'=>"LineTotalVAT",'cd.total_ttc'=>"LineTotalTTC",'p.rowid'=>'ProductId','p.ref'=>'ProductRef','p.label'=>'ProductLabel');
		//$this->export_TypeFields_array[$r]=array('s.rowid'=>"List:societe:nom",'s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','co.code'=>'Text','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','c.ref'=>"Text",'c.ref_client'=>"Text",'c.datec'=>"Date",'c.datep'=>"Date",'c.fin_validite'=>"Date",'c.remise_percent'=>"Numeric",'c.total_ht'=>"Numeric",'c.total'=>"Numeric",'c.fk_statut'=>'Status','c.note_public'=>"Text",'c.date_livraison'=>'Date','cd.description'=>"Text",'cd.product_type'=>'Boolean','cd.tva_tx'=>"Numeric",'cd.qty'=>"Numeric",'cd.total_ht'=>"Numeric",'cd.total_tva'=>"Numeric",'cd.total_ttc'=>"Numeric",'p.rowid'=>'List:Product:label','p.ref'=>'Text','p.label'=>'Text');
		$this->export_TypeFields_array[$r]=array('s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','co.code'=>'Text','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','c.ref'=>"Text",'c.ref_client'=>"Text",'c.datec'=>"Date",'c.datep'=>"Date",'c.fin_validite'=>"Date",'c.remise_percent'=>"Numeric",'c.total_ht'=>"Numeric",'c.total'=>"Numeric",'c.fk_statut'=>'Status','c.note_public'=>"Text",'c.date_livraison'=>'Date','cd.description'=>"Text",'cd.product_type'=>'Boolean','cd.tva_tx'=>"Numeric",'cd.qty'=>"Numeric",'cd.total_ht'=>"Numeric",'cd.total_tva'=>"Numeric",'cd.total_ttc'=>"Numeric",'p.ref'=>'Text','p.label'=>'Text');
		$this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','co.code'=>'company','s.phone'=>'company','s.siren'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.siret'=>'company','c.rowid'=>"propal",'c.ref'=>"propal",'c.ref_client'=>"propal",'c.fk_soc'=>"propal",'c.datec'=>"propal",'c.datep'=>"propal",'c.fin_validite'=>"propal",'c.remise_percent'=>"propal",'c.total_ht'=>"propal",'c.total'=>"propal",'c.fk_statut'=>"propal",'c.note_public'=>"propal",'c.date_livraison'=>"propal",'cd.rowid'=>'propal_line','cd.label'=>"propal_line",'cd.description'=>"propal_line",'cd.product_type'=>'propal_line','cd.tva_tx'=>"propal_line",'cd.qty'=>"propal_line",'cd.total_ht'=>"propal_line",'cd.total_tva'=>"propal_line",'cd.total_ttc'=>"propal_line",'p.rowid'=>'product','p.ref'=>'product','p.label'=>'product');
		$this->export_dependencies_array[$r]=array('propal_line'=>'cd.rowid','product'=>'cd.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'societe as s ';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as co ON s.fk_pays = co.rowid,';
		$this->export_sql_end[$r] .=' '.MAIN_DB_PREFIX.'propal as c';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'user as uc ON c.fk_user_author = uc.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'user as uv ON c.fk_user_valid = uc.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'propal_extrafields as extra ON c.rowid = extra.fk_object';
		$this->export_sql_end[$r] .=', '.MAIN_DB_PREFIX.'propaldet as cd';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (cd.fk_product = p.rowid)';
		$this->export_sql_end[$r] .=' WHERE c.fk_soc = s.rowid AND c.rowid = cd.fk_propal';
		$this->export_sql_end[$r] .=' AND c.entity = '.$conf->entity;
 */
 
 		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;
		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=commercial',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'askpricesupplierMENU_LEFT_TITLE',
			'leftmenu'=>'askpricesuppliersubmenu',
			'url'=>'/comm/askpricesupplier/index.php',
			'langs'=>'askpricesupplier',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'enabled'=>'$conf->askpricesupplier->enabled',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->askpricesupplier->lire',	// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
			'user'=>2 // 0=Menu for internal users, 1=external users, 2=both
		);				                
		$r++;
		
		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=commercial,fk_leftmenu=askpricesuppliersubmenu',
			'type'=>'left',
			'titre'=>'askpricesupplierMENU_LEFT_TITLE_NEW',
			'url'=>'/comm/askpricesupplier.php?action=create',
			'langs'=>'askpricesupplier',
			'enabled'=>'$conf->askpricesupplier->enabled',
			'perms'=>'$user->rights->askpricesupplier->creer',
			'user'=>2
		);
		$r++;
		
		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=commercial,fk_leftmenu=askpricesuppliersubmenu',
			'type'=>'left',
			'titre'=>'askpricesupplierMENU_LEFT_TITLE_LIST',
			'url'=>'/comm/askpricesupplier/list.php',
			'langs'=>'askpricesupplier',
			'enabled'=>'$conf->askpricesupplier->enabled',
			'perms'=>'$user->rights->askpricesupplier->lire',
			'user'=>2
		);
		$r++;		
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
		global $conf,$langs;

		// Remove permissions and default values
		$this->remove($options);

		//ODT template
		$src=DOL_DOCUMENT_ROOT.'/install/doctemplates/askpricesupplier/template_askpricesupplier.odt';
		$dirodt=DOL_DATA_ROOT.'/doctemplates/askpricesupplier';
		$dest=$dirodt.'/template_askpricesupplier.odt';

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
				"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->const[0][2]."','askpricesupplier',".$conf->entity.")",
		);

		$result=$this->_load_tables('/comm/askpricesupplier/sql/');
		return $this->_init($sql, $options);
	}

    /**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
     */
    function remove($options='')
    {
		$sql = array();

		return $this->_remove($sql,$options);
    }

}


/*
 * 
DROP TABLE llx_askpricesupplier_extrafields;
DROP TABLE llx_askpricesupplierdet_extrafields;
DROP TABLE llx_askpricesupplierdet;
DROP TABLE llx_askpricesupplier;
 * 
 * CREATE TABLE IF NOT EXISTS llx_askpricesupplier (
	rowid INT AUTO_INCREMENT,
	fk_statut INT NOT NULL,
	fk_soc INT NOT NULL,
	price DOUBLE(24,8),
	date_create TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	date_send TIMESTAMP,
	PRIMARY KEY pk_rowid (rowid),
	CONSTRAINT fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid)
);
 * 
 * 
 * 
 * 
 */
