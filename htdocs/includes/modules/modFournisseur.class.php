<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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

/**     \defgroup   fournisseur     Module fournisseur
        \brief      Module pour g�rer des soci�t�s et contacts de type fournisseurs
*/

/**
        \file       htdocs/includes/modules/modFournisseur.class.php
        \ingroup    fournisseur
        \brief      Fichier de description et activation du module Fournisseur
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**     \class      modFournisseur
		\brief      Classe de description et activation du module Fournisseur
*/

class modFournisseur extends DolibarrModules
{

    /**
     *   \brief      Constructeur. Definit les noms, constantes et boites
     *   \param      DB      handler d'acc�s base
     */
  	function modFournisseur($DB)
  	{
		$this->db = $DB ;
		$this->numero = 40 ;
		
		$this->family = "products";
		$this->name = "Fournisseur";
		$this->description = "Gestion des fournisseurs";
		
		$this->revision = explode(' ','$Revision$');
		$this->version = $this->revision[1];
		
		$this->const_name = 'MAIN_MODULE_FOURNISSEUR';
		$this->special = 0;
		$this->picto='company';
		
		// Dir
		$this->dirs = array();

		// D�pendances
		$this->depends = array("modSociete");
		$this->requiredby = array();
		$this->langfiles = array("bills","companies","suppliers");
        
		// Config pages
		$this->config_page_url = array("fournisseur.php");
		
		// Constantes
		$this->const = array();
		
		$this->const[0][0] = "COMMANDE_SUPPLIER_ADDON_PDF";
		$this->const[0][1] = "chaine";
		$this->const[0][2] = "muscadet";
		
		$this->const[1][0] = "COMMANDE_SUPPLIER_ADDON";
		$this->const[1][1] = "chaine";
		$this->const[1][2] = "emeraude";
    
		// Boxes
		$this->boxes = array();
		$r=0;
		
		$this->boxes[$r][1] = "box_fournisseurs.php";
		$r++;
		
        $this->boxes[$r][1] = "box_factures_fourn_imp.php";
		$r++;

        $this->boxes[$r][1] = "box_factures_fourn.php";
		$r++;
		
		// Permissions
		$this->rights = array();
		$this->rights_class = 'fournisseur';
		$r=0;
		
		$r++;
		$this->rights[$r][0] = 1181;
		$this->rights[$r][1] = 'Consulter les fournisseurs';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'lire';
		
		$r++;
		$this->rights[$r][0] = 1182;
		$this->rights[$r][1] = 'Lire les commandes fournisseur';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'commande';
		$this->rights[$r][5] = 'lire';
		
		$r++;
		$this->rights[$r][0] = 1183;
		$this->rights[$r][1] = 'Cr�er une commande fournisseur';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'commande';
		$this->rights[$r][5] = 'creer';
		
		$r++;
		$this->rights[$r][0] = 1184;
		$this->rights[$r][1] = 'Valider une commande fournisseur';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'commande';
		$this->rights[$r][5] = 'valider';
		
		$r++;
		$this->rights[$r][0] = 1185;
		$this->rights[$r][1] = 'Approuver les commandes fournisseur';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'commande';
		$this->rights[$r][5] = 'approuver';
		
		$r++;
		$this->rights[$r][0] = 1186;
		$this->rights[$r][1] = 'Commander une commande fournisseur';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'commande';
		$this->rights[$r][5] = 'commander';
		
		$r++;
		$this->rights[$r][0] = 1187;
		$this->rights[$r][1] = 'R�ceptionner les commandes fournisseur';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'commande';
		$this->rights[$r][5] = 'receptionner';
		
		$r++;
		$this->rights[$r][0] = 1188;
		$this->rights[$r][1] = 'Clot�rer les commandes fournisseur';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'commande';
		$this->rights[$r][5] = 'cloturer';
		
		$r++;
		$this->rights[$r][0] = 1189;
		$this->rights[$r][1] = 'Annuler les commandes fournisseur';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'commande';
		$this->rights[$r][5] = 'annuler';
		
		$r++;
		$this->rights[$r][0] = 1231;
		$this->rights[$r][1] = 'Lire les factures fournisseur';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'facture';
		$this->rights[$r][5] = 'lire';
		
		$r++;
		$this->rights[$r][0] = 1232;
		$this->rights[$r][1] = 'Cr�er une facture fournisseur';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'facture';
		$this->rights[$r][5] = 'creer';
		
		$r++;
		$this->rights[$r][0] = 1233;
		$this->rights[$r][1] = 'Valider une facture fournisseur';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'facture';
		$this->rights[$r][5] = 'valider';
		
		$r++;
		$this->rights[$r][0] = 1234;
		$this->rights[$r][1] = 'Supprimer une facture fournisseur';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'facture';
		$this->rights[$r][5] = 'supprimer';

	    $r++;
	    $this->rights[$r][0] = 1236;
	    $this->rights[$r][1] = 'Exporter les factures fournisseurs, attributs et r�glements';
	    $this->rights[$r][2] = 'r';
	    $this->rights[$r][3] = 0;
	    $this->rights[$r][4] = 'facture';
	    $this->rights[$r][5] = 'export';


        // Exports
        //--------
        $r=0;
    
        $r++;
        $this->export_code[$r]=$this->rights_class.'_'.$r;
        $this->export_label[$r]='Factures fournisseurs et lignes de facture';
        $this->export_permission[$r]=array(array("fournisseur","facture","export"));
        $this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.cp'=>'Zip','s.ville'=>'Town','s.fk_pays'=>'Country','s.tel'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','f.rowid'=>"InvoiceId",'f.facnumber'=>"InvoiceRef",'f.datec'=>"InvoiceDateCreation",'f.datef'=>"DateInvoice",'f.total_ht'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.total_tva'=>"TotalVAT",'f.paye'=>"InvoicePayed",'f.fk_statut'=>'InvoiceStatus','f.note'=>"InvoiceNote",'fd.rowid'=>'LineId','fd.description'=>"LineDescription",'fd.tva_taux'=>"LineVATRate",'fd.qty'=>"LineQty");
        $this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.cp'=>'company','s.ville'=>'company','s.fk_pays'=>'company','s.tel'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total_ht'=>"invoice",'f.total_ttc'=>"invoice",'f.total_tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.tva_taux'=>"invoice_line",'fd.qty'=>"invoice_line");
        $this->export_alias_array[$r]=array('s.rowid'=>"socid",'s.nom'=>'soc_name','s.address'=>'soc_adres','s.cp'=>'soc_zip','s.ville'=>'soc_ville','s.fk_pays'=>'soc_pays','s.tel'=>'soc_tel','s.siren'=>'soc_siren','s.siret'=>'soc_siret','s.ape'=>'soc_ape','s.idprof4'=>'soc_idprof4','f.rowid'=>"invoiceid",'f.facnumber'=>"ref",'f.datec'=>"datecreation",'f.datef'=>"dateinvoice",'f.total_ht'=>"totalht",'f.total_ttc'=>"totalttc",'f.total_tva'=>"totalvat",'f.paye'=>"paid",'f.fk_statut'=>'status','f.note'=>"note",'fd.rowid'=>'lineid','fd.description'=>"linedescription",'fd.tva_taux'=>"linevatrate",'fd.qty'=>"lineqty");
        $this->export_sql[$r]="select distinct ";
        $i=0;
        foreach ($this->export_alias_array[$r] as $key => $value)
        {
            if ($i > 0) $this->export_sql[$r].=', ';
            else $i++;
            $this->export_sql[$r].=$key.' as '.$value;
        }
        $this->export_sql[$r].=' from '.MAIN_DB_PREFIX.'facture_fourn as f, '.MAIN_DB_PREFIX.'facture_fourn_det as fd, '.MAIN_DB_PREFIX.'societe as s WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture_fourn';

        $r++;
        $this->export_code[$r]=$this->rights_class.'_'.$r;
        $this->export_label[$r]='Factures fournisseurs et r�glements';
        $this->export_permission[$r]=array(array("fournisseur","facture","export"));
        $this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.cp'=>'Zip','s.ville'=>'Town','s.fk_pays'=>'Country','s.tel'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','f.rowid'=>"InvoiceId",'f.facnumber'=>"InvoiceRef",'f.datec'=>"InvoiceDateCreation",'f.datef'=>"DateInvoice",'f.total_ht'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.total_tva'=>"TotalVAT",'f.paye'=>"InvoicePayed",'f.fk_statut'=>'InvoiceStatus','f.note'=>"InvoiceNote",'pf.amount'=>'AmountPayment','p.datep'=>'DatePayment','p.num_paiement'=>'PaymentNumber');
        $this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.cp'=>'company','s.ville'=>'company','s.fk_pays'=>'company','s.tel'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total_ht'=>"invoice",'f.total_ttc'=>"invoice",'f.total_tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'pf.amount'=>'payment','p.datep'=>'payment','p.num_paiement'=>'payment');
        $this->export_alias_array[$r]=array('s.rowid'=>"socid",'s.nom'=>'soc_name','s.address'=>'soc_adres','s.cp'=>'soc_zip','s.ville'=>'soc_ville','s.fk_pays'=>'soc_pays','s.tel'=>'soc_tel','s.siren'=>'soc_siren','s.siret'=>'soc_siret','s.ape'=>'soc_ape','s.idprof4'=>'soc_idprof4','f.rowid'=>"invoiceid",'f.facnumber'=>"ref",'f.datec'=>"datecreation",'f.datef'=>"dateinvoice",'f.total_ht'=>"totalht",'f.total_ttc'=>"totalttc",'f.total_tva'=>"totalvat",'f.paye'=>"paid",'f.fk_statut'=>'status','f.note'=>"note",'pf.amount'=>'amount_payment','p.datep'=>'date_payment','p.num_paiement'=>'num_payment');
        $this->export_sql_start[$r]='SELECT DISTINCT ';
        $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'facture_fourn as f, '.MAIN_DB_PREFIX.'societe as s) LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf ON pf.fk_facturefourn = f.rowid LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn as p ON pf.fk_paiementfourn = p.rowid';
		$this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid';
    }


   /**
    *   \brief      Fonction appel�e lors de l'activation du module. Ins�re en base les constantes, boites, permissions du module.
    *               D�finit �galement les r�pertoires de donn�es � cr�er pour ce module.
    */
  function init()
  {
    $this->remove();
    
    // Dir
    $this->dirs[0] = $conf->fournisseur->dir_output;
    //$this->dirs[1] = $conf->fournisseur->commande->dir_output;
    //$this->dirs[2] = $conf->fournisseur->facture->dir_output;

    $sql = array();

    $this->load_datas();
  
    return $this->_init($sql);
  }

  /**
   *    \brief      Fonction appel�e lors de la d�sactivation d'un module.
   *                Supprime de la base les constantes, boites et permissions du module.
   */
  function remove()
  {
    $sql = array();

    return $this->_remove($sql);
  }

  /**
   *    \brief      Fonction appel� par l'init (donc lors de l'activation d'un module)
   *
   */
  function load_datas()
  {
    $sql = "SELECT count(rowid) FROM ".MAIN_DB_PREFIX."c_methode_commande_fournisseur";

    if ($this->db->query($sql))
      {
	$row = $this->db->fetch_row();

	if ($row[0] == 0)
	  {
	    $this->db->free();

	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_methode_commande_fournisseur";
	    $sql .= " (code,libelle) VALUES ('OrderByMail','Courrier')";

	    $this->db->query($sql);

	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_methode_commande_fournisseur";
	    $sql .= " (code,libelle) VALUES ('OrderByFax','Fax')";

	    $this->db->query($sql);

	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_methode_commande_fournisseur";
	    $sql .= " (code,libelle) VALUES ('OrderByEMail','EMail')";

	    $this->db->query($sql);

	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_methode_commande_fournisseur";
	    $sql .= " (code,libelle) VALUES ('OrderByPhone','T�l�phone')";

	    $this->db->query($sql);

	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."c_methode_commande_fournisseur";
	    $sql .= " (code,libelle) VALUES ('OrderByWWW','En ligne')";

	    $this->db->query($sql);
	  }
      }
  }
}
?>
