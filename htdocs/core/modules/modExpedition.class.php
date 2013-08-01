<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2013	   Philippe Grand	    <philippe.grand@atoo-net.com>
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
 *	\defgroup   expedition     Module shipping
 *	\brief      Module pour gerer les expeditions de produits
 *	\file       htdocs/core/modules/modExpedition.class.php
 *	\ingroup    expedition
 *	\brief      Fichier de description et activation du module Expedition
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Classe de description et activation du module Expedition
 */
class modExpedition extends DolibarrModules
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
		$this->numero = 80;

		$this->family = "crm";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des expeditions";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto = "sending";

		// Data directories to create when module is enabled
		$this->dirs = array("/expedition/temp",
							"/expedition/sending",
		                    "/expedition/sending/temp",
		                    "/expedition/receipt",
		                    "/expedition/receipt/temp"
		                    );

		// Config pages
		$this->config_page_url = array("confexped.php");

		// Dependances
		$this->depends = array("modCommande");
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array('deliveries','sendings');

		// Constantes
		$this->const = array();
		$r=0;

		$this->const[$r][0] = "EXPEDITION_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "rouget";
		$this->const[$r][3] = 'Nom du gestionnaire de generation des bons expeditions en PDF';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "EXPEDITION_ADDON_NUMBER";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_expedition_safor";
		$this->const[$r][3] = 'Nom du gestionnaire de numerotation des expeditions';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "LIVRAISON_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "typhon";
		$this->const[$r][3] = 'Nom du gestionnaire de generation des bons de reception en PDF';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "LIVRAISON_ADDON_NUMBER";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_livraison_jade";
		$this->const[$r][3] = 'Nom du gestionnaire de numerotation des bons de reception';
		$this->const[$r][4] = 0;
		$r++;

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'expedition';
		$r=0;

		$r++;
		$this->rights[$r][0] = 101;
		$this->rights[$r][1] = 'Lire les expeditions';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 102;
		$this->rights[$r][1] = 'Creer modifier les expeditions';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 104;
		$this->rights[$r][1] = 'Valider les expeditions';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'valider';

		$r++;
		$this->rights[$r][0] = 105; // id de la permission
		$this->rights[$r][1] = 'Envoyer les expeditions aux clients'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'shipping_advance';
        $this->rights[$r][5] = 'send';

		$r++;
		$this->rights[$r][0] = 106;
		$this->rights[$r][1] = 'Exporter les expeditions';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'shipment';
		$this->rights[$r][5] = 'export';

		$r++;
		$this->rights[$r][0] = 109;
		$this->rights[$r][1] = 'Supprimer les expeditions';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 1101;
		$this->rights[$r][1] = 'Lire les bons de livraison';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'livraison';
		$this->rights[$r][5] = 'lire';

		$r++;
		$this->rights[$r][0] = 1102;
		$this->rights[$r][1] = 'Creer modifier les bons de livraison';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'livraison';
		$this->rights[$r][5] = 'creer';

		$r++;
		$this->rights[$r][0] = 1104;
		$this->rights[$r][1] = 'Valider les bons de livraison';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'livraison';
		$this->rights[$r][5] = 'valider';

		$r++;
		$this->rights[$r][0] = 1109;
		$this->rights[$r][1] = 'Supprimer les bons de livraison';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'livraison';
		$this->rights[$r][5] = 'supprimer';

		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Shipments';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("expedition","shipment","export"));
		$this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','co.libelle'=>'Country','co.code'=>'CountryCode','s.phone'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.idprof5'=>'ProfId5','s.idprof6'=>'ProfId6','c.rowid'=>"Id",'c.ref'=>"Ref",'c.ref_customer'=>"RefCustomer",'c.fk_soc'=>"IdCompany",'c.date_creation'=>"DateCreation",'c.date_delivery'=>"DateSending",'c.tracking_number'=>"TrackingNumber",'c.height'=>"Height",'c.width'=>"Width",'c.size'=>"Depth",'c.size_units'=>'SizeUnits','c.weight'=>"Weight",'c.weight_units'=>"WeightUnits",'c.fk_statut'=>'Status','c.note'=>"Note",'ed.rowid'=>'LineId','cd.description'=>'Description','ed.qty'=>"Qty",'p.rowid'=>'ProductId','p.ref'=>'ProductRef','p.label'=>'ProductLabel');
		//$this->export_TypeFields_array[$r]=array('s.rowid'=>"List:societe:nom",'s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','co.libelle'=>'List:c_pays:libelle:libelle','co.code'=>'Text','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','c.ref'=>"Text",'c.ref_client'=>"Text",'c.date_creation'=>"Date",'c.date_commande'=>"Date",'c.amount_ht'=>"Number",'c.remise_percent'=>"Number",'c.total_ht'=>"Number",'c.total_ttc'=>"Number",'c.facture'=>"Boolean",'c.fk_statut'=>'Status','c.note'=>"Text",'c.date_livraison'=>'Date','ed.qty'=>"Text");
		$this->export_TypeFields_array[$r]=array('s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','co.libelle'=>'List:c_pays:libelle:libelle','co.code'=>'Text','s.phone'=>'Text','s.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','c.ref'=>"Text",'c.ref_customer'=>"Text",'c.date_creation'=>"Date",'c.date_delivery'=>"Date",'c.tracking_number'=>"Number",'c.height'=>"Number",'c.width'=>"Number",'c.weight'=>"Number",'c.fk_statut'=>'Status','c.note'=>"Text",'ed.qty'=>"Number");
		$this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','s.fk_pays'=>'company','s.phone'=>'company','s.siren'=>'company','s.ape'=>'company','s.siret'=>'company','s.idprof4'=>'company','s.idprof5'=>'company','s.idprof6'=>'company','c.rowid'=>"shipment",'c.ref'=>"shipment",'c.ref_customer'=>"shipment",'c.fk_soc'=>"shipment",'c.date_creation'=>"shipment",'c.date_delivery'=>"shipment",'c.tracking_number'=>'shipment','c.height'=>"shipment",'c.width'=>"shipment",'c.size'=>'shipment','c.size_units'=>'shipment','c.weight'=>"shipment",'c.weight_units'=>'shipment','c.fk_statut'=>"shipment",'c.note'=>"shipment",'ed.rowid'=>'shipment_line','cd.description'=>'shipment_line','ed.qty'=>"shipment_line",'p.rowid'=>'product','p.ref'=>'product','p.label'=>'product');
		$this->export_dependencies_array[$r]=array('shipment_line'=>'ed.rowid','product'=>'ed.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'expedition as c, '.MAIN_DB_PREFIX.'societe as s';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_pays as co ON s.fk_pays = co.rowid,';
		$this->export_sql_end[$r]  =' '.MAIN_DB_PREFIX.'expeditiondet as ed, '.MAIN_DB_PREFIX.'commandedet as cd';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on cd.fk_product = p.rowid';
		$this->export_sql_end[$r] .=' WHERE c.fk_soc = s.rowid AND c.rowid = ed.fk_expedition AND ed.fk_origin_line = cd.rowid';
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
		global $conf;

		// Permissions
		$this->remove($options);

		$sql = array();

		$sql = array(
			 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->const[0][2]."' AND entity = ".$conf->entity,
			 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->const[0][2]."','shipping',".$conf->entity.")",
			 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->const[2][2]."' AND entity = ".$conf->entity,
			 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->const[2][2]."','delivery',".$conf->entity.")",
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
