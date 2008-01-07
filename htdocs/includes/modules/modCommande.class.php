<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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

/**     \defgroup   commande     Module commande
        \brief      Module pour gérer le suivi des commandes
*/

/**
        \file       htdocs/includes/modules/modCommande.class.php
        \ingroup    commande
        \brief      Fichier de description et activation du module Commande
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");

/**     \class      modCommande
        \brief      Classe de description et activation du module Commande
*/

class modCommande extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
  function modCommande($DB)
  {
    $this->db = $DB ;
    $this->id = 'commande';   // Same value xxx than in file modXxx.class.php file
    $this->numero = 25 ;

    $this->family = "crm";
    $this->name = "Commande";
    $this->description = "Gestion des commandes clients";
    $this->revision = explode(' ','$Revision$');
    $this->version = $this->revision[1];
    $this->const_name = 'MAIN_MODULE_COMMANDE';
    $this->special = 0;
    $this->picto='order';

    // Dir
    $this->dirs = array();

    // Config pages
    $this->config_page_url = array("commande.php");

    // Dépendances
    $this->depends = array("modCommercial");
    $this->requiredby = array("modExpedition");
    $this->conflictwith = array();
	$this->langfiles = array("orders","bills","companies");

    // Constantes
    $this->const = array();
	$this->const[0][0] = "COMMANDE_ADDON_PDF";
    $this->const[0][1] = "chaine";
    $this->const[0][2] = "einstein";
    $this->const[0][3] = 'Nom du gestionnaire de génération des commandes en PDF';
    $this->const[0][4] = 0;
    
    $this->const[1][0] = "COMMANDE_ADDON";
    $this->const[1][1] = "chaine";
    $this->const[1][2] = "mod_commande_marbre";
    $this->const[1][3] = 'Nom du gestionnaire de numérotation des commandes';
    $this->const[1][4] = 0;

    // Boites
    $this->boxes = array();
    $this->boxes[0][0] = "Commandes";
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
    $this->rights[$r][1] = 'Créer modifier les commandes clients';
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
    $this->rights[$r][4] = 'envoyer';
    
    $r++;
    $this->rights[$r][0] = 87;
    $this->rights[$r][1] = 'Clôturer les commandes clients';
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
        $this->export_code[$r]=$this->id.'_'.$r;
        $this->export_label[$r]='Commandes clients et lignes de commande';
        $this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.cp'=>'Zip','s.ville'=>'Town','s.fk_pays'=>'Country','s.tel'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','c.rowid'=>"Id",'c.ref'=>"Ref",'c.ref_client'=>"RefClient",'c.fk_soc'=>"IdCompany",'c.date_creation'=>"DateCreation",'c.date_commande'=>"DateOrder",'c.amount_ht'=>"Amount",'c.remise_percent'=>"GlobalDiscount",'c.total_ht'=>"TotalHT",'c.total_ttc'=>"TotalTTC",'c.facture'=>"OrderShortStatusInvoicee",'c.fk_statut'=>'Status','c.note'=>"Note",'c.date_livraison'=>'DeliveryDate','p.ref'=>'RefProduct','p.label'=>'Label','cd.rowid'=>'LineId','cd.description'=>"LineDescription",'cd.total_ht'=>"LineTotalHT",'cd.tva_tx'=>"LineVATRate",'cd.qty'=>"LineQty");
        $this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.cp'=>'company','s.ville'=>'company','s.fk_pays'=>'company','s.tel'=>'company','s.siren'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.siret'=>'company','c.rowid'=>"order",'c.ref'=>"order",'c.ref_client'=>"order",'c.fk_soc'=>"order",'c.date_creation'=>"order",'c.date_commande'=>"order",'c.amount_ht'=>"order",'c.remise_percent'=>"order",'c.total_ht'=>"order",'c.total_ttc'=>"order",'c.facture'=>"order",'c.fk_statut'=>'order','c.note'=>"order",'c.date_livraison'=>"order",'p.ref'=>'product','p.label'=>'product','cd.rowid'=>'order_line','cd.description'=>"order_line",'cd.total_ht'=>"order_line",'cd.tva_tx'=>"order_line",'cd.qty'=>"order_line");
        $this->export_alias_array[$r]=array('s.rowid'=>"socid",'s.nom'=>'soc_name','s.address'=>'soc_adres','s.cp'=>'soc_zip','s.ville'=>'soc_ville','s.fk_pays'=>'soc_pays','s.tel'=>'soc_tel','s.siren'=>'soc_siren','s.siret'=>'soc_siret','s.ape'=>'soc_ape','s.idprof4'=>'soc_idprof4','c.rowid'=>"orderid",'c.ref'=>"ref",'c.ref_client'=>"refclient",'c.fk_soc'=>"fk_soc",'c.date_creation'=>"datecreation",'c.date_commande'=>"dateorder",'c.amount_ht'=>"amount",'c.remise_percent'=>"globaldiscount",'c.total_ht'=>"totalht",'c.total_ttc'=>"totalttc",'c.facture'=>"invoicee",'c.fk_statut'=>'status','c.note'=>"note",'c.date_livraison'=>'datedelivery','p.ref'=>'refproduct','p.label'=>'label','cd.rowid'=>'lineid','cd.description'=>"linedescription",'cd.total_ht'=>"linetotalht",'cd.tva_tx'=>"linevatrate",'cd.qty'=>"lineqty");
        $this->export_sql[$r]="select distinct ";
        $i=0;
        foreach ($this->export_alias_array[$r] as $key => $value)
        {
            if ($i > 0) $this->export_sql[$r].=', ';
            else $i++;
            $this->export_sql[$r].=$key.' as '.$value;
        }
        $this->export_sql[$r].=' from '.MAIN_DB_PREFIX.'commande as c, '.MAIN_DB_PREFIX.'commandedet as cd, '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'product as p WHERE c.fk_soc = s.rowid AND c.rowid = cd.fk_commande AND cd.fk_product = p.rowid';
        $this->export_permission[$r]=array(array("commande","commande","export"));

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
    $this->dirs[0] = $conf->commande->dir_output;
    $this->dirs[1] = $conf->commande->dir_temp;
	$sql = array(
		 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->const[0][2]."'",
		 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom,type) VALUES('".$this->const[0][2]."','order')"
		 );

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
