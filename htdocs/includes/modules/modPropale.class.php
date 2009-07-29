<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**     \defgroup   propale     Module commercial proposals
 *		\brief      Module pour gerer la tenue de propositions commerciales
 */

/**
 *	\file       htdocs/includes/modules/modPropale.class.php
 *	\ingroup    propale
 *	\brief      Fichier de description et activation du module Propale
 * 	\version	$Id$
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/** \class 		modPropale
 *	\brief      Classe de description et activation du module Propale
 */
class modPropale extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modPropale($DB)
	{
		global $conf;

		$this->db = $DB ;
		$this->numero = 20 ;

		$this->family = "crm";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
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
		$this->config_page_url = array("propale.php");
		$this->langfiles = array("propal","bills","companies","deliveries","products");

		// Constants
		$this->const = array();

		$this->const[0][0] = "PROPALE_ADDON_PDF";
		$this->const[0][1] = "chaine";
		$this->const[0][2] = "azur";
		$this->const[0][3] = 'Nom du gestionnaire de generation des propales en PDF';
		$this->const[0][4] = 0;

		$this->const[1][0] = "PROPALE_ADDON";
		$this->const[1][1] = "chaine";
		$this->const[1][2] = "mod_propale_marbre";
		$this->const[1][3] = 'Nom du gestionnaire de numerotation des propales';
		$this->const[1][4] = 0;

		// Boxes
		$this->boxes = array();
		$this->boxes[0][1] = "box_propales.php";

		// Permissions
		$this->rights = array();
		$this->rights_class = 'propale';

		$this->rights[1][0] = 21; // id de la permission
		$this->rights[1][1] = 'Lire les propositions commerciales'; // libelle de la permission
		$this->rights[1][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[1][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[1][4] = 'lire';

		$this->rights[2][0] = 22; // id de la permission
		$this->rights[2][1] = 'Creer/modifier les propositions commerciales'; // libelle de la permission
		$this->rights[2][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[2][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[2][4] = 'creer';

		$this->rights[3][0] = 24; // id de la permission
		$this->rights[3][1] = 'Valider les propositions commerciales'; // libelle de la permission
		$this->rights[3][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[3][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[3][4] = 'valider';

		$this->rights[4][0] = 25; // id de la permission
		$this->rights[4][1] = 'Envoyer les propositions commerciales aux clients'; // libelle de la permission
		$this->rights[4][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[4][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[4][4] = 'envoyer';

		$this->rights[5][0] = 26; // id de la permission
		$this->rights[5][1] = 'Cloturer les propositions commerciales'; // libelle de la permission
		$this->rights[5][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[5][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[5][4] = 'cloturer';

		$this->rights[6][0] = 27; // id de la permission
		$this->rights[6][1] = 'Supprimer les propositions commerciales'; // libelle de la permission
		$this->rights[6][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[6][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[6][4] = 'supprimer';

		$this->rights[6][0] = 28; // id de la permission
		$this->rights[6][1] = 'Exporter les propositions commerciales et attributs'; // libelle de la permission
		$this->rights[6][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[6][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[6][4] = 'export';

		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='ProposalsAndProposalsLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("propale","export"));
		$this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.cp'=>'Zip','s.ville'=>'Town','s.fk_pays'=>'Country','s.tel'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','c.rowid'=>"Id",'c.ref'=>"Ref",'c.ref_client'=>"RefClient",'c.fk_soc'=>"IdCompany",'c.datec'=>"DateCreation",'c.datep'=>"DatePropal",'c.fin_validite'=>"DateEndPropal",'c.remise_percent'=>"GlobalDiscount",'c.total_ht'=>"TotalHT",'c.total'=>"TotalTTC",'c.fk_statut'=>'Status','c.note'=>"Note",'c.date_livraison'=>'DeliveryDate','cd.rowid'=>'LineId','cd.description'=>"LineDescription",'cd.product_type'=>'TypeOfLineServiceOrProduct','cd.tva_tx'=>"LineVATRate",'cd.qty'=>"LineQty",'cd.total_ht'=>"LineTotalHT",'cd.total_tva'=>"LineTotalVAT",'cd.total_ttc'=>"LineTotalTTC",'p.rowid'=>'ProductId','p.ref'=>'ProductRef','p.label'=>'Label');
		$this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.cp'=>'company','s.ville'=>'company','s.fk_pays'=>'company','s.tel'=>'company','s.siren'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.siret'=>'company','c.rowid'=>"propal",'c.ref'=>"propal",'c.ref_client'=>"propal",'c.fk_soc'=>"propal",'c.datec'=>"propal",'c.datep'=>"propal",'c.fin_validite'=>"propal",'c.remise_percent'=>"propal",'c.total_ht'=>"propal",'c.total'=>"propal",'c.fk_statut'=>"propal",'c.note'=>"propal",'c.date_livraison'=>"propal",'cd.rowid'=>'propal_line','cd.description'=>"propal_line",'cd.product_type'=>'propal_line','cd.tva_tx'=>"propal_line",'cd.qty'=>"propal_line",'cd.total_ht'=>"propal_line",'cd.total_tva'=>"propal_line",'cd.total_ttc'=>"propal_line",'p.rowid'=>'product','p.ref'=>'product','p.label'=>'product');
		$this->export_alias_array[$r]=array('s.rowid'=>"socid",'s.nom'=>'soc_name','s.address'=>'soc_adres','s.cp'=>'soc_zip','s.ville'=>'soc_ville','s.fk_pays'=>'soc_pays','s.tel'=>'soc_tel','s.siren'=>'soc_siren','s.siret'=>'soc_siret','s.ape'=>'soc_ape','s.idprof4'=>'soc_idprof4','c.rowid'=>"orderid",'c.ref'=>"ref",'c.ref_client'=>"refclient",'c.fk_soc'=>"fk_soc",'c.datec'=>"datecreation",'c.datep'=>"datepropal",'c.fin_validite'=>"dateendpropal",'c.remise_percent'=>"globaldiscount",'c.total_ht'=>"totalht",'c.total'=>"totalttc",'c.fk_statut'=>'status','c.note'=>"note",'c.date_livraison'=>'datedelivery','cd.rowid'=>'lineid','cd.description'=>"linedescription",'cd.product_type'=>'linetype','cd.tva_tx'=>"linevatrate",'cd.qty'=>"lineqty",'cd.total_ht'=>"lientotalht",'cd.total_tva'=>"linetotalvat",'cd.total_ttc'=>"linetotalttc",'p.rowid'=>'idproduct','p.ref'=>'refproduct','p.label'=>'label');

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'propal as c, '.MAIN_DB_PREFIX.'propaldet as cd, '.MAIN_DB_PREFIX.'societe as s)';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (cd.fk_product = p.rowid)';
		$this->export_sql_end[$r] .=' WHERE c.fk_soc = s.rowid AND c.rowid = cd.fk_propal';
		$this->export_sql_end[$r] .=' AND c.entity = '.$conf->entity;
	}


	/**
	 *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *               Definit egalement les repertoires de donnees a creer pour ce module.
	 */
	function init()
	{
		global $conf;
		// Permissions et valeurs par defaut
		$this->remove();

		$sql = array(
		 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->const[0][2]."' AND entity = ".$conf->entity,
		 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->const[0][2]."','propal',".$conf->entity.")",
		);

		return $this->_init($sql);

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
