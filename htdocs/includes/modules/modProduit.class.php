<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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
        \defgroup   produit     Module produit
        \brief      Module pour gérer le suivi de produits prédéfinis
*/

/**
        \file       htdocs/includes/modules/modProduit.class.php
        \ingroup    produit
        \brief      Fichier de description et activation du module Produit
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**
        \class      modProduit
		\brief      Classe de description et activation du module Produit
*/

class modProduit extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modProduit($DB)
  {
    global $conf;
    
    $this->db = $DB ;
    $this->id = 'produit';   // Same value xxx than in file modXxx.class.php file
    $this->numero = 50 ;
    
    $this->family = "products";
    $this->name = "Produit";
    $this->description = "Gestion des produits";

    $this->revision = explode(' ','$Revision$');
    $this->version = $this->revision[1];

    $this->const_name = 'MAIN_MODULE_PRODUIT';
    $this->special = 0;
    $this->picto='product';

    // Dir
    $this->dirs = array();

    // Dépendances
    $this->depends = array();
	$this->requiredby = array("modStock","modService","modBarcode");

	// Config pages
    $this->config_page_url = array("produit.php");
	$this->langfiles = array("products","companies");
	
    // Constantes
    $this->const = array();
    $r=0;
	
    $this->const[$r][0] = "MAIN_SEARCHFORM_PRODUITSERVICE";
    $this->const[$r][1] = "yesno";
    $this->const[$r][2] = "1";
    $this->const[$r][3] = "Affichage formulaire de recherche des Produits et Services dans la barre de gauche";
    $this->const[$r][4] = 0;
    $r++;

    // Boxes
    $this->boxes = array();
    $this->boxes[0][0] = "Derniers produits/services enregistrés";
    $this->boxes[0][1] = "box_produits.php";

    // Permissions
    $this->rights = array();
    $this->rights_class = 'produit';
    $r=0;
    
    $r++;
    $this->rights[$r][0] = 31; // id de la permission
    $this->rights[$r][1] = 'Lire les produits/services'; // libelle de la permission
    $this->rights[$r][2] = 'r'; // type de la permission (déprécié à ce jour)
    $this->rights[$r][3] = 1; // La permission est-elle une permission par défaut
    $this->rights[$r][4] = 'lire';

    $r++;
    $this->rights[$r][0] = 32; // id de la permission
    $this->rights[$r][1] = 'Créer modifier les produits/services'; // libelle de la permission
    $this->rights[$r][2] = 'w'; // type de la permission (déprécié à ce jour)
    $this->rights[$r][3] = 0; // La permission est-elle une permission par défaut
    $this->rights[$r][4] = 'creer';

    $r++;
    $this->rights[$r][0] = 33; // id de la permission
    $this->rights[$r][1] = 'Commander les produits/services'; // libelle de la permission
    $this->rights[$r][2] = 'w'; // type de la permission (déprécié à ce jour)
    $this->rights[$r][3] = 0; // La permission est-elle une permission par défaut
    $this->rights[$r][4] = 'commander';

    $r++;
    $this->rights[$r][0] = 34; // id de la permission
    $this->rights[$r][1] = 'Supprimer les produits/services'; // libelle de la permission
    $this->rights[$r][2] = 'd'; // type de la permission (déprécié à ce jour)
    $this->rights[$r][3] = 0; // La permission est-elle une permission par défaut
    $this->rights[$r][4] = 'supprimer';

    $r++;
    $this->rights[$r][0] = 38;
    $this->rights[$r][1] = 'Exporter les produits';
    $this->rights[$r][2] = 'r';
    $this->rights[$r][3] = 0;
    $this->rights[$r][4] = 'export';


    // Exports
    //--------
    $r=0;

    $r++;
    $this->export_code[$r]=$this->id.'_'.$r;
    $this->export_label[$r]="ProductsOrServices";	// Translation key (used only if key ExportDataset_xxx_z not found)
    $this->export_fields_array[$r]=array('p.rowid'=>"Id",'p.ref'=>"Ref",'p.fk_product_type'=>"Type",'p.label'=>"Label",'p.description'=>"Description",'p.note'=>"Note",'p.price'=>"Price",'p.tva_tx'=>'VAT','p.envente'=>"OnSell",'p.duration'=>"Duration",'p.datec'=>'DateCreation','p.tms'=>'DateModification');
    $this->export_entities_array[$r]=array('p.rowid'=>"product",'p.ref'=>"product",'p.fk_product_type'=>"product",'p.label'=>"product",'p.description'=>"product",'p.note'=>"product",'p.price'=>"product",'p.tva_tx'=>'product','p.envente'=>"product",'p.duration'=>"product",'p.datec'=>'product','p.tms'=>'product');
    $this->export_alias_array[$r]=array('p.rowid'=>"id",'p.ref'=>"ref",'p.fk_product_type'=>"type",'p.label'=>"label",'p.description'=>"description",'p.note'=>"note",'p.price'=>"price",'p.tva_tx'=>'vat','p.envente'=>"onsell",'p.duration'=>"duration",'p.datec'=>'datecreation','p.tms'=>'datemodification');
    $this->export_sql[$r]="select ";
    $i=0;
    foreach ($this->export_alias_array[$r] as $key => $value)
    {
        if ($i > 0) $this->export_sql[$r].=', ';
        else $i++;
        $this->export_sql[$r].=$key.' as '.$value;
    }
    $this->export_sql[$r].=' from '.MAIN_DB_PREFIX.'product as p';
    $this->export_permission[$r]=array(array("produit","export"));
    
    
    // Imports
    //--------
    $r=0;

    $r++;
    $this->import_code[$r]=$this->id.'_'.$r;
    $this->import_label[$r]="ProductsOrServices";	// Translation key
    $this->import_fields_array[$r]=array('p.rowid'=>"Id",'p.ref'=>"Ref",'p.fk_product_type'=>"Type",'p.label'=>"Label",'p.description'=>"Description",'p.note'=>"Note",'p.price'=>"SellingPriceHT",'p.price_ttc'=>"SellingPriceTTC",'p.tva_tx'=>'VAT','p.envente'=>"OnSell",'p.duration'=>"Duration");
    $this->import_entities_array[$r]=array('p.rowid'=>"product",'p.ref'=>"product",'p.fk_product_type'=>"product",'p.label'=>"product",'p.description'=>"product",'p.note'=>"product",'p.price'=>"product",'p.price_ttc'=>"product",'p.tva_tx'=>'product','p.envente'=>"product",'p.duration'=>"product");
    //$this->import_alias_array[$r]=array('p.rowid'=>"id",'p.ref'=>"ref",'p.fk_product_type'=>"type",'p.label'=>"label",'p.description'=>"description",'p.note'=>"note",'p.price'=>"price",'p.tva_tx'=>'vat','p.envente'=>"onsell",'p.duration'=>"duration");
        
    if($conf->global->PRODUIT_MULTIPRICES)
    {
    	$this->import_multiprices[$r]=array();
    	
    	for($i=1;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
    	{
    		$this->import_multiprices[$r][$i]["price_ht"] = "SellingPriceHT ".$i;
    		$this->import_multiprices[$r][$i]["price_ttc"] = "SellingPriceTTC ".$i;
    	}
    }
    

	}


   /**
    *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
    *               Définit également les répertoires de données à créer pour ce module.
    */
  function init()
  {
    // Permissions
    $this->remove();

    $sql = array();

    return $this->_init($sql);
  }

  /**
   *    \brief      Fonction appelée lors de la désactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array();

    return $this->_remove($sql);
  }
}
?>
