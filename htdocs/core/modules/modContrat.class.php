<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\defgroup   contrat     Module contract
 *	\brief      Module pour gerer la tenue de contrat de services
 *	\file       htdocs/core/modules/modContrat.class.php
 *	\ingroup    contrat
 *	\brief      Description and activation file for the module contract
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Contrat
 */
class modContrat extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;
		$this->numero = 54;

		$this->family = "crm";
		$this->module_position = '41';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Gestion des contrats de services";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'contract';

		// Data directories to create when module is enabled
		$this->dirs = array("/contract/temp");

		// Dependencies
		$this->depends = array("modSociete");
		$this->requiredby = array();

		// Config pages
		$this->config_page_url = array("contract.php");

		// Constants
		$this->const = array();
		$r = 0;

		$this->const[$r][0] = "CONTRACT_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_contract_serpis";
		$this->const[$r][3] = 'Nom du gestionnaire de numerotation des contrats';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "CONTRACT_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "strato";
		$this->const[$r][3] = 'Name of PDF model of contract';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "CONTRACT_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/contracts";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;
		$r++;

		// Boxes
		$this->boxes = array(
			0=>array('file'=>'box_contracts.php', 'enabledbydefaulton'=>'Home'),
			1=>array('file'=>'box_services_expired.php', 'enabledbydefaulton'=>'Home')
		);

		// Permissions
		$this->rights = array();
		$this->rights_class = 'contrat';
		$r = 0;

		$r++;
		$this->rights[$r][0] = 161;
		$this->rights[$r][1] = 'Lire les contrats';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 162;
		$this->rights[$r][1] = 'Creer / modifier les contrats';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 163;
		$this->rights[$r][1] = 'Activer un service d\'un contrat';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'activer';

		$r++;
		$this->rights[$r][0] = 164;
		$this->rights[$r][1] = 'Desactiver un service d\'un contrat';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'desactiver';

		$r++;
		$this->rights[$r][0] = 165;
		$this->rights[$r][1] = 'Supprimer un contrat';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 167;
		$this->rights[$r][1] = 'Export contracts';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'export';


		// Menus
		//-------
		$this->menu = 1; // This module add menu entries. They are coded into menu manager.


		// Exports
		//--------
		$langs->load("contracts");

		$r = 1;

		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'ContractsAndLine'; // Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r] = 'contract';
		$this->export_permission[$r] = array(array("contrat", "export"));
		$this->export_fields_array[$r] = array('s.rowid'=>"IdCompany", 's.nom'=>'CompanyName', 's.address'=>'Address', 's.zip'=>'Zip', 's.town'=>'Town', 'c.code'=>'CountryCode',
		's.phone'=>'Phone', 's.siren'=>'ProfId1', 's.siret'=>'ProfId2', 's.ape'=>'ProfId3', 's.idprof4'=>'ProfId4', 's.code_compta'=>'CustomerAccountancyCode',
		's.code_compta_fournisseur'=>'SupplierAccountancyCode', 's.tva_intra'=>'VATIntra',
		'co.rowid'=>"Id", 'co.ref'=>"Ref", 'co.datec'=>"DateCreation", 'co.date_contrat'=>"DateContract",
		'co.fin_validite'=>"ContractEndDate", 'co.date_cloture'=>"Closing", 'co.note_private'=>"NotePrivate", 'co.note_public'=>"NotePublic",
		'cod.rowid'=>'LineId', 'cod.label'=>"LineLabel", 'cod.description'=>"LineDescription", 'cod.price_ht'=>"LineUnitPrice", 'cod.tva_tx'=>"LineVATRate",
		'cod.qty'=>"LineQty", 'cod.total_ht'=>"LineTotalHT", 'cod.total_tva'=>"LineTotalVAT", 'cod.total_ttc'=>"LineTotalTTC",
		'cod.date_ouverture'=>"DateStart", 'cod.date_ouverture_prevue'=>"DateStartPlanned", 'cod.date_fin_validite'=>"DateEndPlanned", 'cod.date_cloture'=>"DateEnd",
		'p.rowid'=>'ProductId', 'p.ref'=>'ProductRef', 'p.label'=>'ProductLabel');

		$this->export_entities_array[$r] = array('s.rowid'=>"company", 's.nom'=>'company', 's.address'=>'company', 's.zip'=>'company',
		's.town'=>'company', 'c.code'=>'company', 's.phone'=>'company', 's.siren'=>'company', 's.siret'=>'company', 's.ape'=>'company',
		's.idprof4'=>'company', 's.code_compta'=>'company', 's.code_compta_fournisseur'=>'company', 's.tva_intra'=>'company',
		'co.rowid'=>"contract", 'co.ref'=>"contract", 'co.datec'=>"contract", 'co.date_contrat'=>"contract",
		'co.fin_validite'=>"contract", 'co.date_cloture'=>"contract", 'co.note_private'=>"contract", 'co.note_public'=>"contract",
		'cod.rowid'=>'contract_line', 'cod.label'=>"contract_line", 'cod.description'=>"contract_line", 'cod.price_ht'=>"contract_line", 'cod.tva_tx'=>"contract_line",
		'cod.qty'=>"contract_line", 'cod.total_ht'=>"contract_line", 'cod.total_tva'=>"contract_line", 'cod.total_ttc'=>"contract_line",
		'cod.date_ouverture'=>"contract_line", 'cod.date_ouverture_prevue'=>"contract_line", 'cod.date_fin_validite'=>"contract_line", 'cod.date_cloture'=>"contract_line",
		'p.rowid'=>'product', 'p.ref'=>'product', 'p.label'=>'product');

		$this->export_TypeFields_array[$r] = array('s.rowid'=>"Numeric", 's.nom'=>'Text', 's.address'=>'Text', 's.zip'=>'Text', 's.town'=>'Text', 'c.code'=>'Text',
		's.phone'=>'Text', 's.siren'=>'Text', 's.siret'=>'Text', 's.ape'=>'Text', 's.idprof4'=>'Text', 's.code_compta'=>'Text',
		's.code_compta_fournisseur'=>'Text', 's.tva_intra'=>'Text',
		'co.ref'=>"Text", 'co.datec'=>"Date", 'co.date_contrat'=>"Date",
		'co.fin_validite'=>"Date", 'co.date_cloture'=>"Date", 'co.note_private'=>"Text", 'co.note_public'=>"Text",
		'cod.label'=>"Text", 'cod.description'=>"Text", 'cod.price_ht'=>"Numeric", 'cod.tva_tx'=>"Numeric",
		'cod.qty'=>"Numeric", 'cod.total_ht'=>"Numeric", 'cod.total_tva'=>"Numeric", 'cod.total_ttc'=>"Numeric",
		'cod.date_ouverture'=>"Date", 'cod.date_ouverture_prevue'=>"Date", 'cod.date_fin_validite'=>"Date", 'cod.date_cloture'=>"Date",
		'p.rowid'=>'List:product:label', 'p.ref'=>'Text', 'p.label'=>'Text');


		$keyforselect = 'contrat';
		$keyforelement = 'contract';
		$keyforaliasextra = 'coextra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$keyforselect = 'contratdet';
		$keyforelement = 'contract_line';
		$keyforaliasextra = 'codextra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'societe as s';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c on s.fk_pays = c.rowid';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'contrat as co ON co.fk_soc = s.rowid';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'contratdet as cod ON co.rowid = cod.fk_contrat';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON (cod.fk_product = p.rowid)';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'contrat_extrafields as coextra on (co.rowid = coextra.fk_object)';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'contratdet_extrafields as codextra on (cod.rowid = codextra.fk_object)';
		$this->export_sql_end[$r] .= ' WHERE co.entity IN ('.getEntity('contract').')';
	}


	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
	 *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		// Nettoyage avant activation
		$this->remove($options);

		//ODT template
		$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/contracts/template_contract.odt';
		$dirodt = DOL_DATA_ROOT.'/doctemplates/contracts';
		$dest = $dirodt.'/template_contract.odt';

		if (file_exists($src) && !file_exists($dest)) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_mkdir($dirodt);
			$result = dol_copy($src, $dest, 0, 0);
			if ($result < 0) {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
				return 0;
			}
		}

		$sql = array(
			"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[1][2])."' AND type = 'contract' AND entity = ".((int) $conf->entity),
			"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[1][2])."', 'contract', ".((int) $conf->entity).")"
		);

		return $this->_init($sql, $options);
	}
}
