<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * $Source$
 */

/**     \defgroup   facture     Module facture
        \brief      Module pour gérer les factures clients et/ou fournisseurs
*/


/**
        \file       htdocs/includes/modules/modFacture.class.php
		\ingroup    facture
		\brief      Fichier de la classe de description et activation du module Facture
*/

include_once "DolibarrModules.class.php";


/**
        \class      modFacture
        \brief      Classe de description et activation du module Facture
*/

class modFacture extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
    function modFacture($DB)
    {
        $this->db = $DB ;
        $this->numero = 30 ;
    
        $this->family = "financial";
        $this->name = "Factures";
        $this->description = "Gestion des factures";
    
        $this->revision = explode(' ','$Revision$');
        $this->version = $this->revision[1];
    
        $this->const_name = 'MAIN_MODULE_FACTURE';
        $this->special = 0;
        $this->picto='bill';
    
        // Dir
        $this->dirs = array();
    
        // Dépendances
        $this->depends = array("modSociete","modComptabilite");
        $this->requiredby = array();
        $this->langfiles = array("bills","companies");
    
        // Config pages
        $this->config_page_url = "facture.php";
    
        // Constantes
        $this->const = array();
    
        $this->const[0][0] = "FAC_PDF_INTITULE";
        $this->const[0][1] = "chaine";
        $this->const[0][2] = "Facture Dolibarr";
        $this->const[0][4] = 1;
    
        $this->const[1][0] = "FAC_PDF_ADRESSE";
        $this->const[1][1] = "texte";
        $this->const[1][2] = "Adresse";
        $this->const[1][4] = 1;
    
        $this->const[2][0] = "FAC_PDF_TEL";
        $this->const[2][1] = "chaine";
        $this->const[2][2] = "02 97 42 42 42";
        $this->const[2][4] = 1;
    
        $this->const[3][0] = "FAC_PDF_FAX";
        $this->const[3][1] = "chaine";
        $this->const[3][2] = "02 97 00 00 00";
        $this->const[3][4] = 1;
    
        $this->const[4][0] = "FAC_PDF_MEL";
        $this->const[4][1] = "chaine";
        $this->const[4][2] = "02 97 00 00 00";
        $this->const[4][4] = 1;
    
        $this->const[5][0] = "FAC_PDF_WWW";
        $this->const[5][1] = "chaine";
        $this->const[5][2] = "www.masociete.com";
        $this->const[5][4] = 1;
    
        $this->const[6][0] = "FAC_PDF_LOGO";
        $this->const[6][1] = "chaine";
        $this->const[6][2] = "/logo/mylogo.png";
        $this->const[6][4] = 1;
    
        $this->const[7][0] = "FACTURE_ADDON_PDF";
        $this->const[7][1] = "chaine";
        $this->const[7][2] = "bulot";
    
        $this->const[7][0] = "FACTURE_ADDON";
        $this->const[7][1] = "chaine";
        $this->const[7][2] = "pluton";
    
        $this->const[8][0] = "FAC_FORCE_DATE_VALIDATION";
        $this->const[8][1] = "yesno";
        $this->const[8][2] = "0";
    
        $this->const[9][0] = "FAC_ADD_PROD_DESC";
        $this->const[9][1] = "chaine";
        $this->const[9][2] = "0";
        $this->const[9][3] = "Mettre à 1 pour voir la description d'un produit dans une facture";
        $this->const[9][4] = 1;
    
        // Boites
        $this->boxes = array();
    
        $this->boxes[0][0] = "Factures clients récentes impayées";
        $this->boxes[0][1] = "box_factures_imp.php";
    
        $this->boxes[1][0] = "Factures fournisseurs récentes impayées";
        $this->boxes[1][1] = "box_factures_fourn_imp.php";
    
        $this->boxes[2][0] = "Dernières factures clients saisies";
        $this->boxes[2][1] = "box_factures.php";
    
        $this->boxes[3][0] = "Dernières factures fournisseurs saisies";
        $this->boxes[3][1] = "box_factures_fourn.php";
    
        // Permissions
        $this->rights = array();
        $this->rights_class = 'facture';
    
        $r++;
        $this->rights[$r][0] = 11;
        $this->rights[$r][1] = 'Lire les factures';
        $this->rights[$r][2] = 'a';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'lire';
    
        $r++;
        $this->rights[$r][0] = 12;
        $this->rights[$r][1] = 'Créer/modifier les factures';
        $this->rights[$r][2] = 'a';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'creer';
    
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
        $this->rights[$r][4] = 'envoyer';
    
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
        $this->rights[$r][0] = 1320;
        $this->rights[$r][1] = 'Exporter les factures et attributs';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'facture';
        $this->rights[$r][5] = 'export';
    
/*
        $r++;
        $this->rights[$r][0] = 1325;
        $this->rights[$r][1] = 'Exporter les paiements';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'paiement';
        $this->rights[$r][5] = 'export';
*/    
        // Exports
        //--------
        $r=0;
    
        $r++;
        $this->export_code[$r]=$this->numero.'_'.$r;
        $this->export_label[$r]='Liste des factures clients et lignes de facture';
        $this->export_fields_array[$r]=array('f.rowid'=>"Id",'f.facnumber'=>"Ref",'f.fk_soc'=>"IdCompany",'f.datec'=>"DateCreation",'f.datef'=>"DateInvoice",'f.amount'=>"Amount",'f.remise_percent'=>"GlobalDiscount",'f.total'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.paye'=>"Paid",'f.fk_statut'=>'Status','f.note'=>"Note",'fd.rowid'=>'LineId','fd.description'=>"LineDescription",'fd.tva_taux'=>"LineVATRate",'fd.qty'=>"LineQty",'fd.date_start'=>"DateStart",'fd.date_end'=>"DateEnd");
        $this->export_alias_array[$r]=array('f.rowid'=>"invoiceid",'f.facnumber'=>"ref",'f.fk_soc'=>"fk_soc",'f.datec'=>"datecreation",'f.datef'=>"dateinvoice",'f.amount'=>"amount",'f.remise_percent'=>"globaldiscount",'f.total'=>"totalht",'f.total_ttc'=>"totalttc",'f.paye'=>"paid",'f.fk_statut'=>'status','f.note'=>"note",'fd.rowid'=>'lineid','fd.description'=>"linedescription",'fd.tva_taux'=>"linevatrate",'fd.qty'=>"lineqty",'fd.date_start'=>"linedatestart",'fd.date_end'=>"linedateend");
        $this->export_sql[$r]="select distinct ";
        $i=0;
        foreach ($this->export_alias_array[$r] as $key => $value)
        {
            if ($i > 0) $this->export_sql[$r].=', ';
            else $i++;
            $this->export_sql[$r].=$key.' as '.$value;
        }
        $this->export_sql[$r].=' from '.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'facturedet as fd WHERE fd.fk_facture = f.rowid';
        $this->export_permission[$r]=array(array("facture","facture","export"));
    
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
        $this->dirs[0] = $conf->facture->dir_output;
    
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
