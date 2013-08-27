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
class modPropale extends DolibarrModules
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
		$this->numero = 20;

		$this->family = "crm";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des propositions commerciales";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='propal';

		// Data directories to create when module is enabled
		$this->dirs = array("/propale/temp");

		// Dependancies
		$this->depends = array("modSociete");
		$this->requiredby = array();
		$this->config_page_url = array("propal.php");
		$this->langfiles = array("propal","bills","companies","deliveries","products");

		// Constants
		$this->const = array();
		$r=0;

		$this->const[$r][0] = "PROPALE_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "azur";
		$this->const[$r][3] = 'Nom du gestionnaire de generation des propales en PDF';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "PROPALE_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_propale_marbre";
		$this->const[$r][3] = 'Nom du gestionnaire de numerotation des propales';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "PROPALE_VALIDITY_DURATION";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "15";
		$this->const[$r][3] = 'Duration of validity of business proposals';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "PROPALE_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/proposals";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;

		// Boxes
		$this->boxes = array(
           	0=>array('file'=>'box_graph_propales_permonth.php','enabledbydefaulton'=>'Home'),
           	1=>array('file'=>'box_propales.php','enabledbydefaulton'=>'Home'),
		);

		// Permissions
		$this->rights = array();
		$this->rights_class = 'propale';
		$r=0;

		$r++;
		$this->rights[$r][0] = 21; // id de la permission
		$this->rights[$r][1] = 'Lire les propositions commerciales'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 22; // id de la permission
		$this->rights[$r][1] = 'Creer/modifier les propositions commerciales'; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 24; // id de la permission
		$this->rights[$r][1] = 'Valider les propositions commerciales'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'valider';

		$r++;
		$this->rights[$r][0] = 25; // id de la permission
		$this->rights[$r][1] = 'Envoyer les propositions commerciales aux clients'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'propal_advance';
        $this->rights[$r][5] = 'send';

		$r++;
		$this->rights[$r][0] = 26; // id de la permission
		$this->rights[$r][1] = 'Cloturer les propositions commerciales'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'cloturer';

		$r++;
		$this->rights[$r][0] = 27; // id de la permission
		$this->rights[$r][1] = 'Supprimer les propositions commerciales'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 28; // id de la permission
		$this->rights[$r][1] = 'Exporter les propositions commerciales et attributs'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'export';

		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='ProposalsAndProposalsLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("propale","export"));
		$this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','cp.code'=>'CountryCode','s.phone'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','c.rowid'=>"Id",'c.ref'=>"Ref",'c.ref_client'=>"RefCustomer",'c.fk_soc'=>"IdCompany",'c.datec'=>"DateCreation",'c.datep'=>"DatePropal",'c.fin_validite'=>"DateEndPropal",'c.remise_percent'=>"GlobalDiscount",'c.total_ht'=>"TotalHT",'c.total'=>"TotalTTC",'c.fk_statut'=>'Status','c.note_public'=>"Note",'c.date_livraison'=>'DeliveryDate','cd.rowid'=>'LineId','cd.label'=>"Label",'cd.description'=>"LineDescription",'cd.product_type'=>'TypeOfLineServiceOrProduct','cd.tva_tx'=>"LineVATRate",'cd.qty'=>"LineQty",'cd.total_ht'=>"LineTotalHT",'cd.total_tva'=>"LineTotalVAT",'cd.total_ttc'=>"LineTotalTTC",'p.rowid'=>'ProductId','p.ref'=>'ProductRef','p.label'=>'ProductLabel');
		//$this->export_TypeFields_array[$r]=array('s.rowid'=>"List:societe:nom",'s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','cp.code'=>'Text','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','c.ref'=>"Text",'c.ref_client'=>"Text",'c.datec'=>"Date",'c.datep'=>"Date",'c.fin_validite'=>"Date",'c.remise_percent'=>"Numeric",'c.total_ht'=>"Numeric",'c.total'=>"Numeric",'c.fk_statut'=>'Status','c.note_public'=>"Text",'c.date_livraison'=>'Date','cd.description'=>"Text",'cd.product_type'=>'Boolean','cd.tva_tx'=>"Numeric",'cd.qty'=>"Numeric",'cd.total_ht'=>"Numeric",'cd.total_tva'=>"Numeric",'cd.total_ttc'=>"Numeric",'p.rowid'=>'List:Product:label','p.ref'=>'Text','p.label'=>'Text');
		$this->export_TypeFields_array[$r]=array('s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','cp.code'=>'Text','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','c.ref'=>"Text",'c.ref_client'=>"Text",'c.datec'=>"Date",'c.datep'=>"Date",'c.fin_validite'=>"Date",'c.remise_percent'=>"Numeric",'c.total_ht'=>"Numeric",'c.total'=>"Numeric",'c.fk_statut'=>'Status','c.note_public'=>"Text",'c.date_livraison'=>'Date','cd.description'=>"Text",'cd.product_type'=>'Boolean','cd.tva_tx'=>"Numeric",'cd.qty'=>"Numeric",'cd.total_ht'=>"Numeric",'cd.total_tva'=>"Numeric",'cd.total_ttc'=>"Numeric",'p.ref'=>'Text','p.label'=>'Text');
		$this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','cp.code'=>'company','s.phone'=>'company','s.siren'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.siret'=>'company','c.rowid'=>"propal",'c.ref'=>"propal",'c.ref_client'=>"propal",'c.fk_soc'=>"propal",'c.datec'=>"propal",'c.datep'=>"propal",'c.fin_validite'=>"propal",'c.remise_percent'=>"propal",'c.total_ht'=>"propal",'c.total'=>"propal",'c.fk_statut'=>"propal",'c.note_public'=>"propal",'c.date_livraison'=>"propal",'cd.rowid'=>'propal_line','cd.label'=>"propal_line",'cd.description'=>"propal_line",'cd.product_type'=>'propal_line','cd.tva_tx'=>"propal_line",'cd.qty'=>"propal_line",'cd.total_ht'=>"propal_line",'cd.total_tva'=>"propal_line",'cd.total_ttc'=>"propal_line",'p.rowid'=>'product','p.ref'=>'product','p.label'=>'product');
		$this->export_dependencies_array[$r]=array('propal_line'=>'cd.rowid','product'=>'cd.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'societe as s ';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_pays as cp ON s.fk_pays = cp.rowid,';
		$this->export_sql_end[$r] .=' '.MAIN_DB_PREFIX.'propal as c, '.MAIN_DB_PREFIX.'propaldet as cd';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (cd.fk_product = p.rowid)';
		$this->export_sql_end[$r] .=' WHERE c.fk_soc = s.rowid AND c.rowid = cd.fk_propal';
		$this->export_sql_end[$r] .=' AND c.entity = '.$conf->entity;
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
		$src=DOL_DOCUMENT_ROOT.'/install/doctemplates/proposals/template_proposal.odt';
		$dirodt=DOL_DATA_ROOT.'/doctemplates/proposals';
		$dest=$dirodt.'/template_proposal.odt';

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
				"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->const[0][2]."','propal',".$conf->entity.")",
		);

		return $this->_init($sql,$options);

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
?>
