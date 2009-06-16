<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * $Id$
 */

/**
 *	\defgroup   service     Module service
 *	\brief      Module pour gerer le suivi de services predefinis
 */

/**
 *	\file       htdocs/includes/modules/modService.class.php
 *	\ingroup    service
 *	\brief      Fichier de description et activation du module Service
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/** \class 		modService
 *	\brief      Classe de description et activation du module Service
 */
class modService extends DolibarrModules
{

	/**
	 *   \brief      Constructeur. Definit les noms, constantes et boites
	 *   \param      DB      handler d'acces base
	 */
	function modService($DB)
	{
		global $conf;

		$this->db = $DB ;
		$this->numero = 53 ;

		$this->family = "products";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		$this->description = "Gestion des services";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='service';

		// Data directories to create when module is enabled
		$this->dirs = array("/produit/temp");

		// Dependancies
		$this->depends = array();
		$this->requiredby = array("modContrat");

		// Config pages
		$this->config_page_url = array("produit.php");
		$this->langfiles = array("products","companies","bills");

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();
		$this->boxes[0][1] = "box_services_vendus.php";

		// Permissions
		$this->rights = array();
		$this->rights_class = 'service';
		$r=0;

		$r++;
		$this->rights[$r][0] = 531; // id de la permission
		$this->rights[$r][1] = 'Lire les services'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 1; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 532; // id de la permission
		$this->rights[$r][1] = 'Creer/modifier les services'; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 534; // id de la permission
		$this->rights[$r][1] = 'Supprimer les services'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 538;	// Must be same permission than in product module
		$this->rights[$r][1] = 'Exporter les services';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'export';


		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]="Services";	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r]=array(array("service","export"));
		$this->export_fields_array[$r]=array('p.rowid'=>"Id",'p.ref'=>"Ref",'p.label'=>"Label",'p.description'=>"Description",'p.note'=>"Note",'p.price_base_type'=>"PriceBase",'p.price'=>"UnitPriceHT",'p.price_ttc'=>"UnitPriceTTC",'p.tva_tx'=>'VATRate','p.envente'=>"OnSell",'p.duration'=>"Duration",'p.datec'=>'DateCreation','p.tms'=>'DateModification');
		if (! empty($conf->stock->enabled)) $this->export_fields_array[$r]=array_merge ($this->export_fields_array[$r],array('p.stock'=>'Stock'));
		$this->export_entities_array[$r]=array('p.rowid'=>"service",'p.ref'=>"service",'p.label'=>"service",'p.description'=>"service",'p.note'=>"service",'p.price_base_type'=>"service",'p.price'=>"service",'p.price_ttc'=>"service",'p.tva_tx'=>"service",'p.envente'=>"service",'p.duration'=>"service",'p.datec'=>"service",'p.tms'=>"service");
		if (! empty($conf->stock->enabled)) $this->export_entities_array[$r]=array_merge ($this->export_entities_array[$r],array('p.stock'=>'product'));
		$this->export_alias_array[$r]=array('p.rowid'=>"id",'p.ref'=>"ref",'p.label'=>"label",'p.description'=>"description",'p.note'=>"note",'p.price_base_type'=>'pricebase','p.price'=>"priceht",'p.price_ttc'=>"pricettc",'p.tva_tx'=>'vat','p.envente'=>"onsell",'p.duration'=>"duration",'p.datec'=>'datecreation','p.tms'=>'datemodification');
		if (! empty($conf->stock->enabled)) $this->export_alias_array[$r]=array_merge ($this->export_alias_array[$r],array('p.stock'=>'stock'));

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'product as p';
		$this->export_sql_end[$r] .=' WHERE p.fk_product_type = 1 AND p.entity = '.$conf->entity;

	}


	/**
	 *   \brief      Fonction appelee lors de l'activation du module. Insere en base les constantes, boites, permissions du module.
	 *               Definit egalement les repertoires de donnees a creer pour ce module.
	 */
	function init()
	{
		// Permissions et valeurs par defaut
		$this->remove();

		$sql = array();

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
