<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2012      Juanjo Menent		<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *		\defgroup   commande     Module orders
 *		\brief      Module pour gerer le suivi des commandes
 *		\file       htdocs/core/modules/modCommande.class.php
 *		\ingroup    commande
 *		\brief      Fichier de description et activation du module Commande
 */

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 *		\class      modCommande
 *		\brief      Classe de description et activation du module Commande
 */
class modCommande extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function modCommande($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 25;

		$this->family = "crm";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des commandes clients";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='order';

		// Data directories to create when module is enabled
		$this->dirs = array("/commande/temp");

		// Config pages
		$this->config_page_url = array("commande.php");

		// Dependancies
		$this->depends = array("modSociete");
		$this->requiredby = array("modExpedition");
		$this->conflictwith = array();
		$this->langfiles = array("orders","bills","companies","products");

		// Constantes
		$this->const = array();
		$r=0;

		$this->const[$r][0] = "COMMANDE_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "einstein";
		$this->const[$r][3] = 'Nom du gestionnaire de generation des commandes en PDF';
		$this->const[$r][4] = 0;

		$r++;
		$this->const[$r][0] = "COMMANDE_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_commande_marbre";
		$this->const[$r][3] = 'Nom du gestionnaire de numerotation des commandes';
		$this->const[$r][4] = 0;
		
		$r++;
		$this->const[$r][0] = "COMMANDE_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/orders";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;

		// Boites
		$this->boxes = array();
		$this->boxes[0][1] = "box_commandes.php";

		// Permissions
		$this->rights = array();
		$this->rights_class = 'commande';

		$r=0;

		$r++;
		$this->rights[$r][0] = 81;
		$this->rights[$r][1] = 'Lire les commandes clients';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 82;
		$this->rights[$r][1] = 'Creer/modifier les commandes clients';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 84;
		$this->rights[$r][1] = 'Valider les commandes clients';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'valider';

		$r++;
		$this->rights[$r][0] = 86;
		$this->rights[$r][1] = 'Envoyer les commandes clients';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'order_advance';
        $this->rights[$r][5] = 'send';

		$r++;
		$this->rights[$r][0] = 87;
		$this->rights[$r][1] = 'Cloturer les commandes clients';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'cloturer';

		$r++;
		$this->rights[$r][0] = 88;
		$this->rights[$r][1] = 'Annuler les commandes clients';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'annuler';

		$r++;
		$this->rights[$r][0] = 89;
		$this->rights[$r][1] = 'Supprimer les commandes clients';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 1421;
		$this->rights[$r][1] = 'Exporter les commandes clients et attributs';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'commande';
		$this->rights[$r][5] = 'export';

		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='CustomersOrdersAndOrdersLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("commande","commande","export"));
		$this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.cp'=>'Zip','s.ville'=>'Town','s.fk_pays'=>'Country','s.tel'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','c.rowid'=>"Id",'c.ref'=>"Ref",'c.ref_client'=>"RefClient",'c.fk_soc'=>"IdCompany",'c.date_creation'=>"DateCreation",'c.date_commande'=>"DateOrder",'c.amount_ht'=>"Amount",'c.remise_percent'=>"GlobalDiscount",'c.total_ht'=>"TotalHT",'c.total_ttc'=>"TotalTTC",'c.facture'=>"OrderShortStatusInvoicee",'c.fk_statut'=>'Status','c.note'=>"Note",'c.date_livraison'=>'DeliveryDate','cd.rowid'=>'LineId','cd.description'=>"LineDescription",'cd.product_type'=>'TypeOfLineServiceOrProduct','cd.tva_tx'=>"LineVATRate",'cd.qty'=>"LineQty",'cd.total_ht'=>"LineTotalHT",'cd.total_tva'=>"LineTotalVAT",'cd.total_ttc'=>"LineTotalTTC",'p.rowid'=>'ProductId','p.ref'=>'ProductRef','p.label'=>'Label');
		$this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.cp'=>'company','s.ville'=>'company','s.fk_pays'=>'company','s.tel'=>'company','s.siren'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.siret'=>'company','c.rowid'=>"order",'c.ref'=>"order",'c.ref_client'=>"order",'c.fk_soc'=>"order",'c.date_creation'=>"order",'c.date_commande'=>"order",'c.amount_ht'=>"order",'c.remise_percent'=>"order",'c.total_ht'=>"order",'c.total_ttc'=>"order",'c.facture'=>"order",'c.fk_statut'=>"order",'c.note'=>"order",'c.date_livraison'=>"order",'cd.rowid'=>'order_line','cd.description'=>"order_line",'cd.product_type'=>'order_line','cd.tva_tx'=>"order_line",'cd.qty'=>"order_line",'cd.total_ht'=>"order_line",'cd.total_tva'=>"order_line",'cd.total_ttc'=>"order_line",'p.rowid'=>'product','p.ref'=>'product','p.label'=>'product');

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'commande as c, '.MAIN_DB_PREFIX.'commandedet as cd, '.MAIN_DB_PREFIX.'societe as s)';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (cd.fk_product = p.rowid)';
		$this->export_sql_end[$r] .=' WHERE c.fk_soc = s.rowid AND c.rowid = cd.fk_commande';
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
		$this->remove();

		//ODT template
		require_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');
		$dirodt=DOL_DATA_ROOT.'/doctemplates/orders';
		create_exdir($dirodt);
		dol_copy(DOL_DOCUMENT_ROOT.'/install/doctemplates/orders/template_order.odt',$dirodt.'/template_order.odt',0,0);
		
		$sql = array(
		 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->const[0][2]."'",
		 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom,type) VALUES('".$this->const[0][2]."','order')"
		 );

		 return $this->_init($sql,$options);
	}


	/**
	 *    \brief      Fonction appelee lors de la desactivation d'un module.
	 *                Supprime de la base les constantes, boites et permissions du module.
	 */
	function remove()
	{
		$sql = array();

		return $this->_remove($sql);

	}
}
?>
