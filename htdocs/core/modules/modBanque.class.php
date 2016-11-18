<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2008-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 * 	\defgroup   banque     Module bank
 * 	\brief      Module pour gerer la tenue d'un compte bancaire et rapprochements
 *	\file       htdocs/core/modules/modBanque.class.php
 *	\ingroup    banque
 *	\brief      Fichier de description et activation du module Banque
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Banque
 */
class modBanque extends DolibarrModules
{

	/**
	 *	Constructor.
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 85;

		$this->family = "financial";
		$this->module_position = 510;
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des comptes financiers de type Comptes bancaires ou postaux";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 0;
		$this->picto='account';

		// Data directories to create when module is enabled
		$this->dirs = array("/banque/temp");

        // Config pages
        //-------------
        $this->config_page_url = array("bank.php");

		// Dependancies
		$this->depends = array();
		$this->requiredby = array("modComptabilite","modAccounting","modPrelevement");
		$this->conflictwith = array();
		$this->langfiles = array("banks","compta","bills","companies");

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array(0=>array('file'=>'box_comptes.php','enabledbydefaulton'=>'Home'));

		// Permissions
		$this->rights = array();
		$this->rights_class = 'banque';
		$r=0;

		$r++;
		$this->rights[$r][0] = 111; // id de la permission
		$this->rights[$r][1] = 'Lire les comptes bancaires'; // libelle de la permission
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 112; // id de la permission
		$this->rights[$r][1] = 'Creer/modifier montant/supprimer ecriture bancaire'; // libelle de la permission
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'modifier';

		$r++;
		$this->rights[$r][0] = 113; // id de la permission
		$this->rights[$r][1] = 'Configurer les comptes bancaires (creer, gerer categories)'; // libelle de la permission
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'configurer';

		$r++;
		$this->rights[$r][0] = 114; // id de la permission
		$this->rights[$r][1] = 'Rapprocher les ecritures bancaires'; // libelle de la permission
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'consolidate';

		$r++;
		$this->rights[$r][0] = 115; // id de la permission
		$this->rights[$r][1] = 'Exporter transactions et releves'; // libelle de la permission
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'export';

		$r++;
		$this->rights[$r][0] = 116; // id de la permission
		$this->rights[$r][1] = 'Virements entre comptes'; // libelle de la permission
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'transfer';

		$r++;
		$this->rights[$r][0] = 117; // id de la permission
		$this->rights[$r][1] = 'Gerer les envois de cheques'; // libelle de la permission
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'cheque';


		// Menus
		//-------
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.
		
		
		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Ecritures bancaires et releves';
		$this->export_permission[$r]=array(array("banque","export"));
		$this->export_fields_array[$r]=array('b.rowid'=>'IdTransaction','ba.ref'=>'AccountRef','ba.label'=>'AccountLabel','b.datev'=>'DateValue','b.dateo'=>'DateOperation','b.label'=>'Label','b.num_chq'=>'ChequeOrTransferNumber','b.fk_bordereau'=>'ChequeBordereau','-b.amount'=>'Debit','b.amount'=>'Credit','b.num_releve'=>'AccountStatement','b.datec'=>"DateCreation","bu.url_id"=>"IdThirdParty","s.nom"=>"ThirdParty","s.code_compta"=>"CustomerAccountancyCode","s.code_compta_fournisseur"=>"SupplierAccountancyCode");
		$this->export_TypeFields_array[$r]=array('ba.ref'=>'Text','ba.label'=>'Text','b.datev'=>'Date','b.dateo'=>'Date','b.label'=>'Text','b.num_chq'=>'Text','b.fk_bordereau'=>'Text','-b.amount'=>'Numeric','b.amount'=>'Numeric','b.num_releve'=>'Text','b.datec'=>"Date","bu.url_id"=>"Text","s.nom"=>"Text","s.code_compta"=>"Text","s.code_compta_fournisseur"=>"Text");
		$this->export_entities_array[$r]=array('b.rowid'=>'account','ba.ref'=>'account','ba.label'=>'account','b.datev'=>'account','b.dateo'=>'account','b.label'=>'account','b.num_chq'=>'account','b.fk_bordereau'=>'account','-b.amount'=>'account','b.amount'=>'account','b.num_releve'=>'account','b.datec'=>"account","bu.url_id"=>"company","s.nom"=>"company","s.code_compta"=>"company","s.code_compta_fournisseur"=>"company");
		$this->export_special_array[$r]=array('-b.amount'=>'NULLIFNEG','b.amount'=>'NULLIFNEG');
	    if (empty($conf->fournisseur->enabled))
        {
            unset($this->export_fields_array[$r]['s.code_compta_fournisseur']);
            unset($this->export_entities_array[$r]['s.code_compta_fournisseur']);
        }
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'bank_account as ba, '.MAIN_DB_PREFIX.'bank as b)';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX."bank_url as bu ON (bu.fk_bank = b.rowid AND bu.type = 'company')";
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON bu.url_id = s.rowid';
		$this->export_sql_end[$r] .=' WHERE ba.rowid = b.fk_account';
		$this->export_sql_end[$r] .=' AND ba.entity IN ('.getEntity('bank_account',1).')';
		$this->export_sql_order[$r] =' ORDER BY b.datev, b.num_releve';

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Bordereaux remise Chq/Fact';
		$this->export_permission[$r]=array(array("banque","export"));
		$this->export_fields_array[$r]=array("bch.rowid"=>"DepositId","bch.ref"=>"Numero","bch.ref_ext"=>"RefExt",'ba.ref'=>'AccountRef','ba.label'=>'AccountLabel','b.datev'=>'DateValue','b.num_chq'=>'ChequeOrTransferNumber','b.amount'=>'Credit','b.num_releve'=>'AccountStatement','b.datec'=>"DateCreation",
			"bch.date_bordereau"=>"Date","bch.amount"=>"Total","bch.nbcheque"=>"NbCheque","bu.url_id"=>"IdThirdParty","s.nom"=>"ThirdParty","f.facnumber"=>"InvoiceRef"
			);
		$this->export_TypeFields_array[$r]=array('ba.ref'=>'Text','ba.label'=>'Text','b.datev'=>'Date','b.num_chq'=>'Text','b.amount'=>'Numeric','b.num_releve'=>'Text','b.datec'=>"Date",
			"bch.date_bordereau"=>"Date","bch.rowid"=>"Numeric","bch.ref"=>"Numeric","bch.ref_ext"=>"Text","bch.amount"=>"Numeric","bch.nbcheque"=>"Numeric","bu.url_id"=>"Text","s.nom"=>"Text","f.facnumber"=>"Text"
			);
		$this->export_entities_array[$r]=array('ba.ref'=>'account','ba.label'=>'account','b.datev'=>'account','b.num_chq'=>'account','b.amount'=>'account','b.num_releve'=>'account','b.datec'=>"account",
			"bu.url_id"=>"company","s.nom"=>"company","s.code_compta"=>"company","s.code_compta_fournisseur"=>"company","f.facnumber"=>"invoice");
		$this->export_special_array[$r]=array('b.amount'=>'NULLIFNEG');

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'bordereau_cheque as bch, '.MAIN_DB_PREFIX.'bank_account as ba, '.MAIN_DB_PREFIX.'bank as b)';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX."bank_url as bu ON (bu.fk_bank = b.rowid AND bu.type = 'company')";
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'paiement as p ON b.rowid = p.fk_bank';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'paiement_facture as pf ON p.rowid = pf.fk_paiement';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'facture as f ON f.rowid = pf.fk_facture';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON f.fk_soc = s.rowid';
		$this->export_sql_end[$r] .=' WHERE ba.rowid = b.fk_account AND bch.rowid = b.fk_bordereau and bch.fk_bank_account=ba.rowid';
		$this->export_sql_end[$r] .=" AND b.fk_type = 'CHQ'";
		$this->export_sql_end[$r] .=' AND p.fk_paiement = 7';
		$this->export_sql_end[$r] .=' AND ba.entity IN ('.getEntity('bank_account',1).')';
		$this->export_sql_order[$r] =' ORDER BY b.datev, b.num_releve';

	}


    /**
     *      Function called when module is enabled.
     *      The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *      It also creates data directories.
     *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf;

		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql,$options);
	}
}
