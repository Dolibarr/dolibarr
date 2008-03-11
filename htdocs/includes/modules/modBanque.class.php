<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

/**     \defgroup   banque     Module banque
        \brief      Module pour gérer la tenue d'un compte bancaire et rapprochements
		\version	$Id$
*/

/**
        \file       htdocs/includes/modules/modBanque.class.php
        \ingroup    banque
        \brief      Fichier de description et activation du module Banque
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**     \class      modBanque
		\brief      Classe de description et activation du module Banque
*/

class modBanque extends DolibarrModules
{

   /**
    *   \brief      Constructeur. Definit les noms, constantes et boites
    *   \param      DB      handler d'accès base
    */
	function modBanque($DB)
	{
	    $this->db = $DB ;
	    $this->numero = 85 ;

	    $this->family = "financial";
	    $this->name = "Banque";
	    $this->description = "Gestion des comptes financiers de type Comptes bancaires ou postaux";

	    $this->revision = explode(' ','$Revision$');
	    $this->version = $this->revision[1];

	    $this->const_name = 'MAIN_MODULE_BANQUE';
	    $this->special = 0;
	    $this->picto='account';

	    // Dépendances
	    $this->depends = array();
	    $this->requiredby = array("modComptabilite","modComptabiliteExpert");
		$this->conflictwith = array();
		$this->langfiles = array("banks","compta","companies");
		
	    // Constantes
	    $this->const = array();

	    $this->dirs = array();

	    // Boites
	    $this->boxes = array();
	    $this->boxes[0][1] = "box_comptes.php";

	    // Permissions
	    $this->rights = array();
	    $this->rights_class = 'banque';
	    $r=0;
	    
	    $r++;
	    $this->rights[$r][0] = 111; // id de la permission
	    $this->rights[$r][1] = 'Lire les comptes bancaires'; // libelle de la permission
	    $this->rights[$r][2] = 'r'; // type de la permission (déprécié à ce jour)
	    $this->rights[$r][3] = 1; // La permission est-elle une permission par défaut
	    $this->rights[$r][4] = 'lire';

	    $r++;
	    $this->rights[$r][0] = 112; // id de la permission
	    $this->rights[$r][1] = 'Créer/modifier montant/supprimer écriture bancaire'; // libelle de la permission
	    $this->rights[$r][2] = 'w'; // type de la permission (déprécié à ce jour)
	    $this->rights[$r][3] = 0; // La permission est-elle une permission par défaut
	    $this->rights[$r][4] = 'modifier';

	    $r++;
	    $this->rights[$r][0] = 113; // id de la permission
	    $this->rights[$r][1] = 'Configurer les comptes bancaires (créer, gérer catégories)'; // libelle de la permission
	    $this->rights[$r][2] = 'a'; // type de la permission (déprécié à ce jour)
	    $this->rights[$r][3] = 0; // La permission est-elle une permission par défaut
	    $this->rights[$r][4] = 'configurer';

	    $r++;
	    $this->rights[$r][0] = 114; // id de la permission
	    $this->rights[$r][1] = 'Rapprocher les écritures bancaires'; // libelle de la permission
	    $this->rights[$r][2] = 'w'; // type de la permission (déprécié à ce jour)
	    $this->rights[$r][3] = 0; // La permission est-elle une permission par défaut
	    $this->rights[$r][4] = 'consolidate';

	    $r++;
	    $this->rights[$r][0] = 115; // id de la permission
	    $this->rights[$r][1] = 'Exporter transactions et relevés'; // libelle de la permission
	    $this->rights[$r][2] = 'r'; // type de la permission (déprécié à ce jour)
	    $this->rights[$r][3] = 0; // La permission est-elle une permission par défaut
	    $this->rights[$r][4] = 'export';

   	    $r++;
	    $this->rights[$r][0] = 116; // id de la permission
	    $this->rights[$r][1] = 'Virements entre comptes'; // libelle de la permission
	    $this->rights[$r][2] = 'w'; // type de la permission (déprécié à ce jour)
	    $this->rights[$r][3] = 0; // La permission est-elle une permission par défaut
	    $this->rights[$r][4] = 'transfer';

	    
		
		// Exports
        //--------
        $r=0;

        $r++;
        $this->export_code[$r]=$this->rights_class.'_'.$r;
        $this->export_label[$r]='Ecritures bancaires et relevés';
        $this->export_permission[$r]=array(array("banque","export"));
        $this->export_fields_array[$r]=array('b.rowid'=>'IdTransaction','ba.ref'=>'AccountRef','ba.label'=>'AccountLabel','b.datev'=>'DateValue','b.dateo'=>'DateOperation','b.label'=>'Label','b.num_chq'=>'CheckNumber','-b.amount'=>'Debit','b.amount'=>'Credit','b.num_releve'=>'AccountStatement','b.datec'=>"DateCreation","but.url_id"=>"IdThirdParty","s.nom"=>"ThirdParty","s.code_compta"=>"CustomerAccountancyCode","s.code_compta_fournisseur"=>"SupplierAccountancyCode");
		$this->export_entities_array[$r]=array('b.rowid'=>'account','ba.ref'=>'account','ba.label'=>'account','b.datev'=>'account','b.dateo'=>'account','b.label'=>'account','b.num_chq'=>'account','-b.amount'=>'account','b.amount'=>'account','b.num_releve'=>'account','b.datec'=>"account","but.url_id"=>"company","s.nom"=>"company","s.code_compta"=>"company","s.code_compta_fournisseur"=>"company");
        $this->export_alias_array[$r]=array('b.rowid'=>'tran_id','ba.ref'=>'account_ref','ba.label'=>'account_label','b.datev'=>'datev','b.dateo'=>'dateo','b.label'=>'label','b.num_chq'=>'num','-b.amount'=>'debit','b.amount'=>'credit','b.num_releve'=>'numrel','b.datec'=>"datec","but.url_id"=>"soc_id","s.nom"=>"thirdparty","s.code_compta"=>"customeracccode","s.code_compta_fournisseur"=>"supplieracccode");

        $this->export_sql_start[$r]='SELECT DISTINCT ';
        $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'bank_account as ba, '.MAIN_DB_PREFIX.'bank as b';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'bank_url as but ON but.fk_bank = b.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON but.url_id = s.rowid';
        $this->export_sql_end[$r] .=" WHERE IFNULL(but.type,'company') = 'company'";
		$this->export_sql_end[$r] .=' AND ba.rowid=b.fk_account';
		$this->export_sql_end[$r] .=' ORDER BY b.datev, b.num_releve';
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
    
        $sql = array();
    
        $this->dirs[0] = $conf->banque->dir_output;
    
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
