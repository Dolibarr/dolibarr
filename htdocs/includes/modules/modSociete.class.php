<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005      Regis Houssin        <regis.houssin@cap-networks.com>
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
		\defgroup   societe     Module societe
		\brief      Module pour gérer les societes et contacts clients
*/

/**
		\file       htdocs/includes/modules/modSociete.class.php
		\ingroup    societe
		\brief      Fichier de description et activation du module Societe
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/** 
		\class      modSociete
		\brief      Classe de description et activation du module Societe
*/

class modSociete extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modSociete($DB)
  {
    $this->db = $DB ;
    $this->id = 'company';   // Same value xxx than in file modXxx.class.php file
    $this->numero = 1 ;

    $this->family = "crm";
    $this->name = "Module societe";
    $this->description = "Gestion des sociétés et contacts";

    $this->revision = explode(' ','$Revision$');
    $this->version = $this->revision[1];

    $this->const_name = 'MAIN_MODULE_SOCIETE';
    $this->special = 0;
    $this->config_page_url = array("societe.php");
    $this->picto='company';
    
    // Dir
    $this->dirs = array();

    // Dépendances
    $this->depends = array();
    $this->requiredby = array("modCommercial","modFacture","modFournisseur","modFicheinter","modPropale","modContrat","modCommande");
    $this->langfiles = array("companies");
    
    // Constantes
    $this->const = array();
    $r=0;
    
    $this->const[$r][0] = "SOCIETE_FISCAL_MONTH_START";
    $this->const[$r][1] = "chaine";
    $this->const[$r][2] = "0";
    $this->const[$r][3] = "Mettre le numéro du mois du début d\'année fiscale, ex: 9 pour septembre";
    $this->const[$r][4] = 1;
	$r++;
	
    $this->const[$r][0] = "MAIN_SEARCHFORM_SOCIETE";
    $this->const[$r][1] = "yesno";
    $this->const[$r][2] = "1";
    $this->const[$r][3] = "Affichage formulaire de recherche des Sociétés dans la barre de gauche";
    $this->const[$r][4] = 0;
	$r++;

    $this->const[$r][0] = "MAIN_SEARCHFORM_CONTACT";
    $this->const[$r][1] = "yesno";
    $this->const[$r][2] = "1";
    $this->const[$r][3] = "Affichage formulaire de recherche des Contacts dans la barre de gauche";
    $this->const[$r][4] = 0;
	$r++;

    // Boxes
    $this->boxes = array();

    // Permissions
    $this->rights = array();
    $this->rights_class = 'societe';
    $r=0;
    
    $r++;
    $this->rights[$r][0] = 121; // id de la permission
    $this->rights[$r][1] = 'Lire les societes'; // libelle de la permission
    $this->rights[$r][2] = 'r'; // type de la permission (déprécié à ce jour)
    $this->rights[$r][3] = 1; // La permission est-elle une permission par défaut
    $this->rights[$r][4] = 'lire';

    $r++;
    $this->rights[$r][0] = 122; // id de la permission
    $this->rights[$r][1] = 'Créer modifier les societes'; // libelle de la permission
    $this->rights[$r][2] = 'w'; // type de la permission (déprécié à ce jour)
    $this->rights[$r][3] = 0; // La permission est-elle une permission par défaut
    $this->rights[$r][4] = 'creer';

    $r++;
    $this->rights[$r][0] = 125; // id de la permission
    $this->rights[$r][1] = 'Supprimer les sociétés'; // libelle de la permission
    $this->rights[$r][2] = 'd'; // type de la permission (déprécié à ce jour)
    $this->rights[$r][3] = 0; // La permission est-elle une permission par défaut
    $this->rights[$r][4] = 'supprimer';

    $r++;
    $this->rights[$r][0] = 126; // id de la permission
    $this->rights[$r][1] = 'Exporter les sociétés'; // libelle de la permission
    $this->rights[$r][2] = 'r'; // type de la permission (déprécié à ce jour)
    $this->rights[$r][3] = 0; // La permission est-elle une permission par défaut
    $this->rights[$r][4] = 'export';
    
    $r++;
    $this->rights[$r][0] = 281; // id de la permission
    $this->rights[$r][1] = 'Lire les contacts'; // libelle de la permission
    $this->rights[$r][2] = 'r'; // type de la permission (déprécié à ce jour)
    $this->rights[$r][3] = 1; // La permission est-elle une permission par défaut
    $this->rights[$r][4] = 'contact';
    $this->rights[$r][5] = 'lire';
    
    $r++;
    $this->rights[$r][0] = 282; // id de la permission
    $this->rights[$r][1] = 'Créer modifier les contacts'; // libelle de la permission
    $this->rights[$r][2] = 'w'; // type de la permission (déprécié à ce jour)
    $this->rights[$r][3] = 0; // La permission est-elle une permission par défaut
    $this->rights[$r][4] = 'contact';
    $this->rights[$r][5] = 'creer';
    
    $r++;
    $this->rights[$r][0] = 283; // id de la permission
    $this->rights[$r][1] = 'Supprimer les contacts'; // libelle de la permission
    $this->rights[$r][2] = 'd'; // type de la permission (déprécié à ce jour)
    $this->rights[$r][3] = 0; // La permission est-elle une permission par défaut
    $this->rights[$r][4] = 'contact';
    $this->rights[$r][5] = 'supprimer';

    $r++;
    $this->rights[$r][0] = 286; // id de la permission
    $this->rights[$r][1] = 'Exporter les contacts'; // libelle de la permission
    $this->rights[$r][2] = 'd'; // type de la permission (déprécié à ce jour)
    $this->rights[$r][3] = 0; // La permission est-elle une permission par défaut
    $this->rights[$r][4] = 'contact';
    $this->rights[$r][5] = 'export';

    // Exports
    //--------
    $r=0;

    // Export des liste des societes et attributs
    $r++;
    $this->export_code[$r]=$this->id.'_'.$r;
    $this->export_label[$r]='Tiers (sociétés/institutions) et attributs';
    $this->export_fields_array[$r]=array('s.rowid'=>"Id",'s.nom'=>"Name",'s.prefix_comm'=>"Prefix",'s.client'=>"Customer",'s.fournisseur'=>"Supplier",'s.datec'=>"DateCreation",'s.tms'=>"DateLastModification",'s.code_client'=>"CustomerCode",'s.code_fournisseur'=>"SupplierCode",'s.address'=>"Address",'s.cp'=>"Zip",'s.ville'=>"Town",'p.libelle'=>"Country",'p.code'=>"CountryCode",'s.tel'=>"Phone",'s.fax'=>"Fax",'s.url'=>"Url",'s.siret'=>"IdProf1",'s.siren'=>"IdProf2",'s.ape'=>"IdProf3",'s.idprof4'=>"IdProf4",'s.tva_intra'=>"VATIntraShort",'s.capital'=>"Capital",'s.note'=>"Note");
    $this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>"company",'s.prefix_comm'=>"company",'s.client'=>"company",'s.fournisseur'=>"company",'s.datec'=>"company",'s.tms'=>"company",'s.code_client'=>"company",'s.code_fournisseur'=>"company",'s.address'=>"company",'s.cp'=>"company",'s.ville'=>"company",'p.libelle'=>"company",'p.code'=>"company",'s.tel'=>"company",'s.fax'=>"company",'s.url'=>"company",'s.siret'=>"company",'s.siren'=>"company",'s.ape'=>"company",'s.idprof4'=>"company",'s.tva_intra'=>"company",'s.capital'=>"company",'s.note'=>"company");
    $this->export_alias_array[$r]=array('s.rowid'=>"socid",'s.nom'=>"name",'s.prefix_comm'=>"prefix",'s.client'=>"iscustomer",'s.fournisseur'=>"issupplier",'s.datec'=>"datecreation",'s.tms'=>"datelastmodification",'s.code_client'=>"customercode",'s.code_fournisseur'=>"suppliercode",'s.address'=>"address",'s.cp'=>"zip",'s.ville'=>"town",'p.libelle'=>"country",'p.code'=>"countrycode",'s.tel'=>"phone",'s.fax'=>"fax",'s.url'=>"url",'s.siret'=>"idprof1",'s.siren'=>"idprof2",'s.ape'=>"idprof3",'s.idprof4'=>"idprof4",'s.tva_intra'=>"vatintra",'s.capital'=>"capital",'s.note'=>"note");
    $this->export_sql[$r]="select ";
    $i=0;
    foreach ($this->export_alias_array[$r] as $key => $value)
    {
        if ($i > 0) $this->export_sql[$r].=', ';
        else $i++;
        $this->export_sql[$r].=$key.' as '.$value;
    }
    $this->export_sql[$r].=' from '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'c_pays as p where s.fk_pays = p.rowid';
    $this->export_permission[$r]=array(array("societe","export"));

    // Export des liste des contacts et attributs
    $r++;
    $this->export_code[$r]=$this->id.'_'.$r;
    $this->export_label[$r]='Contacts (de tiers) et attributs';
    $this->export_fields_array[$r]=array('c.civilite'=>"CivilityCode",'c.name'=>'Lastname','c.firstname'=>'Firstname','c.datec'=>"DateCreation",'c.tms'=>"DateLastModification",'c.address'=>"Address",'c.cp'=>"Zip",'c.ville'=>"Town",'c.phone'=>"Phone",'c.fax'=>"Fax",'c.email'=>"EMail",'p.libelle'=>"Country",'p.code'=>"CountryCode",'s.rowid'=>"IdCompany",'s.nom'=>"CompanyName",'s.code_client'=>"CustomerCode",'s.code_fournisseur'=>"SupplierCode");
    $this->export_entities_array[$r]=array('c.civilite'=>"contact",'c.name'=>'contact','c.firstname'=>'contact','c.datec'=>"contact",'c.tms'=>"contact",'c.address'=>"contact",'c.cp'=>"contact",'c.ville'=>"contact",'c.phone'=>"contact",'c.fax'=>"contact",'c.email'=>"contact",'p.libelle'=>"contact",'p.code'=>"contact",'s.rowid'=>"company",'s.nom'=>"company",'s.code_client'=>"company",'s.code_fournisseur'=>"company");
    $this->export_alias_array[$r]=array('c.civilite'=>"civilitycode",'c.name'=>'lastname','c.firstname'=>'firstname','c.datec'=>"datecreation",'c.tms'=>"datelastmodification",'c.address'=>"address",'c.cp'=>"zip",'c.ville'=>"town",'c.phone'=>"phone",'c.fax'=>"fax",'c.email'=>"email",'p.libelle'=>"country",'p.code'=>"countrycode",'s.rowid'=>"socid",'s.nom'=>"companyname",'s.code_client'=>"customercode",'s.code_fournisseur'=>"suppliercode");
    $this->export_sql[$r]="select ";
    $i=0;
    foreach ($this->export_alias_array[$r] as $key => $value)
    {
        if ($i > 0) $this->export_sql[$r].=', ';
        else $i++;
        $this->export_sql[$r].=$key.' as '.$value;
    }
    $this->export_sql[$r].=' from '.MAIN_DB_PREFIX.'c_pays as p, '.MAIN_DB_PREFIX.'socpeople as c LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON c.fk_soc = s.rowid WHERE c.fk_pays = p.rowid';
    $this->export_permission[$r]=array(array("societe","contact","export"));

}

  
  /**
   *   \brief      Fonction appelée lors de l'activation du module. Insère en base les constantes, boites, permissions du module.
   *               Définit également les répertoires de données à créer pour ce module.
   */
  function init()
  {
    global $conf;
    
    // Permissions
    $this->remove();
    
    // Dir
    $this->dirs[0] = $conf->societe->dir_output;
    
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
