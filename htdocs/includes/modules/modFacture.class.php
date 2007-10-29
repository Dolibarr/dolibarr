<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@cap-networks.com>
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

/**     \defgroup   facture     Module facture
        \brief      Module pour gérer les factures clients et/ou fournisseurs
*/


/**
        \file       htdocs/includes/modules/modFacture.class.php
		\ingroup    facture
		\brief      Fichier de la classe de description et activation du module Facture
*/
include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


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
        $this->id = 'invoice';   // Same value xxx than in file modXxx.class.php file
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
        $this->depends = array("modSociete");
        $this->requiredby = array("modComptabilite","modComptabiliteExpert");
        $this->conflictwith = array();
		$this->langfiles = array("bills","companies","compta","products");
    
        // Config pages
        $this->config_page_url = array("facture.php");
    
        // Constantes
        $this->const = array();
    	$r=0;
    	
        $this->const[$r][0] = "FACTURE_ADDON_PDF";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "crabe";
    	$r++;
    
        $this->const[$r][0] = "FACTURE_ADDON";
        $this->const[$r][1] = "chaine";
        $this->const[$r][2] = "terre";
    	$r++;
    
        $this->const[$r][0] = "FAC_FORCE_DATE_VALIDATION";
        $this->const[$r][1] = "yesno";
        $this->const[$r][2] = "0";
    	$r++;
    
        // Boites
        $this->boxes = array();
		$r=0;
		    
        $this->boxes[$r][0] = "Factures clients récentes impayées";
        $this->boxes[$r][1] = "box_factures_imp.php";
    	$r++;
    	
        $this->boxes[$r][0] = "Dernières factures clients saisies";
        $this->boxes[$r][1] = "box_factures.php";
    	$r++;
    	
        // Permissions
        $this->rights = array();
        $this->rights_class = 'facture';
		$r=0;
		    
        $r++;
        $this->rights[$r][0] = 11;
        $this->rights[$r][1] = 'Lire les factures';
        $this->rights[$r][2] = 'a';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'lire';
    
        $r++;
        $this->rights[$r][0] = 12;
        $this->rights[$r][1] = 'Créer les factures';
        $this->rights[$r][2] = 'a';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'creer';
        
        $r++;
        $this->rights[$r][0] = 13;
        $this->rights[$r][1] = 'Modifier les factures';
        $this->rights[$r][2] = 'a';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'modifier';
    
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
        $this->rights[$r][0] = 1321;
        $this->rights[$r][1] = 'Exporter les factures clients, attributs et règlements';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'facture';
        $this->rights[$r][5] = 'export';
    

		// Exports
        //--------
        $r=0;
    
        $r++;
        $this->export_code[$r]=$this->id.'_'.$r;
        $this->export_label[$r]='CustomersInvoicesAndInvoiceLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
        $this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.cp'=>'Zip','s.ville'=>'Town','s.fk_pays'=>'Country','s.tel'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode','f.rowid'=>"Id",'f.facnumber'=>"Ref",'f.datec'=>"DateCreation",'f.datef'=>"DateInvoice",'f.total'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.tva'=>"TotalVAT",'f.paye'=>"BillShortStatusPayed",'f.fk_statut'=>'Status','f.note'=>"Note",'fd.rowid'=>'LineId','fd.description'=>"LineDescription",'fd.price'=>"LineUnitPrice",'fd.tva_taux'=>"LineVATRate",'fd.qty'=>"LineQty",'fd.total_ht'=>"LineTotalHT",'fd.total_tva'=>"LineTotalTVA",'fd.total_ttc'=>"LineTotalTTC",'fd.date_start'=>"DateStart",'fd.date_end'=>"DateEnd",'fd.fk_product'=>'ProductId','p.ref'=>'ProductRef');
		$this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.cp'=>'company','s.ville'=>'company','s.fk_pays'=>'company','s.tel'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_taux'=>"invoice_line",'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product');
        $this->export_alias_array[$r]=array('s.rowid'=>"socid",'s.nom'=>'soc_name','s.address'=>'soc_adres','s.cp'=>'soc_zip','s.ville'=>'soc_ville','s.fk_pays'=>'soc_pays','s.tel'=>'soc_tel','s.siren'=>'soc_siren','s.siret'=>'soc_siret','s.ape'=>'soc_ape','s.idprof4'=>'soc_idprof4','s.code_compta'=>'soc_customer_accountancy','s.code_compta_fournisseur'=>'soc_supplier_accountancy','f.rowid'=>"invoiceid",'f.facnumber'=>"ref",'f.datec'=>"datecreation",'f.datef'=>"dateinvoice",'f.total'=>"totalht",'f.total_ttc'=>"totalttc",'f.tva'=>"totalvat",'f.paye'=>"paid",'f.fk_statut'=>'status','f.note'=>"note",'fd.rowid'=>'lineid','fd.description'=>"linedescription",'fd.price'=>"lineprice",'fd.total_ht'=>"linetotalht",'fd.total_tva'=>"linetotaltva",'fd.total_ttc'=>"linetotalttc",'fd.tva_taux'=>"linevatrate",'fd.qty'=>"lineqty",'fd.date_start'=>"linedatestart",'fd.date_end'=>"linedateend",'fd.fk_product'=>'productid','p.ref'=>'productref');
        $this->export_sql[$r]="select distinct ";
        $i=0;
        foreach ($this->export_alias_array[$r] as $key => $value)
        {
            if ($i > 0) $this->export_sql[$r].=', ';
            else $i++;
            $this->export_sql[$r].=$key.' as '.$value;
        }
        $this->export_sql[$r].=' from ('.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'facturedet as fd, '.MAIN_DB_PREFIX.'societe as s)';
		$this->export_sql[$r].=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		$this->export_sql[$r].=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture';
        $this->export_permission[$r]=array(array("facture","facture","export"));

        $r++;
        $this->export_code[$r]=$this->id.'_'.$r;
        $this->export_label[$r]='CustomersInvoicesAndPayments';	// Translation key (used only if key ExportDataset_xxx_z not found)
        $this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.cp'=>'Zip','s.ville'=>'Town','s.fk_pays'=>'Country','s.tel'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode','f.rowid'=>"Id",'f.facnumber'=>"Ref",'f.datec'=>"DateCreation",'f.datef'=>"DateInvoice",'f.total'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.tva'=>"TotalVAT",'f.paye'=>"BillShortStatusPayed",'f.fk_statut'=>'Status','f.note'=>"Note",'pf.amount'=>'AmountPayment','p.datep'=>'DatePayment','p.num_paiement'=>'Numero');
		$this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.cp'=>'company','s.ville'=>'company','s.fk_pays'=>'company','s.tel'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'pf.amount'=>'payment','p.datep'=>'payment','p.num_paiement'=>'payment');
        $this->export_alias_array[$r]=array('s.rowid'=>"socid",'s.nom'=>'soc_name','s.address'=>'soc_adres','s.cp'=>'soc_zip','s.ville'=>'soc_ville','s.fk_pays'=>'soc_pays','s.tel'=>'soc_tel','s.siren'=>'soc_siren','s.siret'=>'soc_siret','s.ape'=>'soc_ape','s.idprof4'=>'soc_idprof4','s.code_compta'=>'soc_customer_accountancy','s.code_compta_fournisseur'=>'soc_supplier_accountancy','f.rowid'=>"invoiceid",'f.facnumber'=>"ref",'f.datec'=>"datecreation",'f.datef'=>"dateinvoice",'f.total'=>"totalht",'f.total_ttc'=>"totalttc",'f.tva'=>"totalvat",'f.paye'=>"paid",'f.fk_statut'=>'status','f.note'=>"note",'pf.amount'=>'amount_payment','p.datep'=>'date_payment','p.num_paiement'=>'num_payment');
        $this->export_sql[$r]="select distinct ";
        $i=0;
        foreach ($this->export_alias_array[$r] as $key => $value)
        {
            if ($i > 0) $this->export_sql[$r].=', ';
            else $i++;
            $this->export_sql[$r].=$key.' as '.$value;
        }
        $this->export_sql[$r].=' from ('.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'societe as s) LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON pf.fk_facture = f.rowid LEFT JOIN '.MAIN_DB_PREFIX.'paiement as p ON pf.fk_paiement = p.rowid WHERE f.fk_soc = s.rowid';
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
    
	    $sql = array(
			 "DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->const[0][2]."'",
			 "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type) VALUES('".$this->const[0][2]."','invoice')",
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
