<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2012		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2020		Ahmad Jamaly Rabib		<rabib@metroworks.co.jp>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\defgroup   propale     Module commercial proposals
 *	\brief      Module to manage commercial proposals
 *	\file       htdocs/core/modules/modPropale.class.php
 *	\ingroup    propale
 *	\brief      Description and activation file for the module customer proposal
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Propale
 */
class modPropale extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $user;

		$this->db = $db;
		$this->numero = 20;

		$this->family = "crm";
		$this->module_position = '10';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Gestion des propositions commerciales";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'propal';

		// Data directories to create when module is enabled
		$this->dirs = array("/propale/temp");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array("modSociete"); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->config_page_url = array("propal.php");
		$this->langfiles = array("propal", "bills", "companies", "deliveries", "products");

		// Constants
		$this->const = array();
		$r = 0;

		$this->const[$r][0] = "PROPALE_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "cyan";
		$this->const[$r][3] = 'Name of the proposal generation manager in PDF format';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "PROPALE_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_propale_marbre";
		$this->const[$r][3] = 'Name of proposal numbering manager';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "PROPALE_VALIDITY_DURATION";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "15";
		$this->const[$r][3] = 'Duration of validity of business proposals';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "PROPALE_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/proposals";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "PROPOSAL_ALLOW_ONLINESIGN";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "1";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;
		$r++;

		/*$this->const[$r][0] = "PROPALE_DRAFT_WATERMARK";
		$this->const[$r][2] = "__(Draft)__";
		$this->const[$r][3] = 'Watermark to show on draft proposals';
		$this->const[$r][4] = 0;
		$r++;*/

		// Boxes
		$this->boxes = array(
			0=>array('file'=>'box_graph_propales_permonth.php', 'enabledbydefaulton'=>'Home'),
			1=>array('file'=>'box_propales.php', 'enabledbydefaulton'=>'Home'),
		);

		// Permissions
		$this->rights = array();
		$this->rights_class = 'propale';
		$r = 0;

		$r++;
		$this->rights[$r][0] = 21; // id de la permission
		$this->rights[$r][1] = 'Read commercial proposals'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecated)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par default
		$this->rights[$r][4] = 'lire';

		$r++;
		$this->rights[$r][0] = 22; // id de la permission
		$this->rights[$r][1] = 'Create and update commercial proposals'; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecated)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par default
		$this->rights[$r][4] = 'creer';

		$r++;
		$this->rights[$r][0] = 24; // id de la permission
		$this->rights[$r][1] = 'Validate commercial proposals'; // Validate proposal
		$this->rights[$r][2] = 'd'; // type de la permission (deprecated)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par default
		$this->rights[$r][4] = 'propal_advance';
		$this->rights[$r][5] = 'validate';

		$r++;
		$this->rights[$r][0] = 25; // id de la permission
		$this->rights[$r][1] = 'Send commercial proposals to customers'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecated)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par default
		$this->rights[$r][4] = 'propal_advance';
		$this->rights[$r][5] = 'send';

		$r++;
		$this->rights[$r][0] = 26; // id de la permission
		$this->rights[$r][1] = 'Close commercial proposals'; // Set proposal to signed or refused
		$this->rights[$r][2] = 'd'; // type de la permission (deprecated)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par default
		$this->rights[$r][4] = 'propal_advance';
		$this->rights[$r][5] = 'close';

		$r++;
		$this->rights[$r][0] = 27; // id de la permission
		$this->rights[$r][1] = 'Delete commercial proposals'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecated)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par default
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 28; // id de la permission
		$this->rights[$r][1] = 'Exporting commercial proposals and attributes'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecated)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par default
		$this->rights[$r][4] = 'export';


		// Menus
		//-------
		$this->menu = 1; // This module add menu entries. They are coded into menu manager.


		// Exports
		//--------
		$r = 0;

		$r++;
		$this->export_code[$r] = $this->rights_class.'_'.$r;
		$this->export_label[$r] = 'ProposalsAndProposalsLines'; // Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_permission[$r] = array(array("propale", "export"));
		$this->export_fields_array[$r] = array(
			's.rowid'=>"IdCompany", 's.nom'=>'CompanyName', 'ps.nom'=>'ParentCompany', 's.code_client'=>'CustomerCode', 's.address'=>'Address', 's.zip'=>'Zip', 's.town'=>'Town', 'co.code'=>'CountryCode', 's.phone'=>'Phone',
			's.siren'=>'ProfId1', 's.siret'=>'ProfId2', 's.ape'=>'ProfId3', 's.idprof4'=>'ProfId4', 'c.rowid'=>"Id", 'c.ref'=>"Ref", 'c.ref_client'=>"RefCustomer",
			'c.fk_soc'=>"IdCompany", 'c.datec'=>"DateCreation", 'c.datep'=>"DatePropal", 'c.fin_validite'=>"DateEndPropal",
			'c.total_ht'=>"TotalHT", 'c.total_ttc'=>"TotalTTC",
			'cir.label'=>'Source',
		);
		if (isModEnabled("multicurrency")) {
			$this->export_fields_array[$r]['c.multicurrency_code'] = 'Currency';
			$this->export_fields_array[$r]['c.multicurrency_tx'] = 'CurrencyRate';
			$this->export_fields_array[$r]['c.multicurrency_total_ht'] = 'MulticurrencyAmountHT';
			$this->export_fields_array[$r]['c.multicurrency_total_tva'] = 'MulticurrencyAmountVAT';
			$this->export_fields_array[$r]['c.multicurrency_total_ttc'] = 'MulticurrencyAmountTTC';
		}
		$this->export_fields_array[$r] = array_merge($this->export_fields_array[$r], array(
			'c.fk_statut'=>'Status', 'c.note_public'=>"NotePublic", 'c.note_private'=>"NotePrivate", 'c.date_livraison'=>'DeliveryDate',
			'c.fk_user_author'=>'CreatedById', 'uc.login'=>'CreatedByLogin',
			'c.fk_user_valid'=>'ValidatedById', 'uv.login'=>'ValidatedByLogin'));
		if (isModEnabled("project")) {
			$this->export_fields_array[$r]['pj.ref'] = 'ProjectRef';
		}
		$this->export_fields_array[$r] = array_merge($this->export_fields_array[$r], array(
			'cd.rowid'=>'LineId', 'cd.description'=>"LineDescription", 'cd.product_type'=>'TypeOfLineServiceOrProduct',
			'cd.tva_tx'=>"LineVATRate", 'cd.qty'=>"LineQty", 'cd.total_ht'=>"LineTotalHT", 'cd.total_tva'=>"LineTotalVAT", 'cd.total_ttc'=>"LineTotalTTC",
		));
		$this->export_fields_array[$r] = array_merge($this->export_fields_array[$r], array(
			'p.rowid'=>'ProductId', 'p.ref'=>'ProductRef', 'p.label'=>'ProductLabel'
		));
		// Add multicompany field
		if (getDolGlobalString('MULTICOMPANY_ENTITY_IN_EXPORT_IF_SHARED')) {
			$nbofallowedentities = count(explode(',', getEntity('propal')));
			if (isModEnabled('multicompany') && $nbofallowedentities > 1) {
				$this->export_fields_array[$r]['c.entity'] = 'Entity';
			}
		}
		//$this->export_TypeFields_array[$r]=array(
		//	's.rowid'=>"Numeric",'s.nom'=>'Text','s.address'=>'Text','s.zip'=>'Text','s.town'=>'Text','co.code'=>'Text','s.phone'=>'Text',
		//	's.siren'=>'Text','s.siret'=>'Text','s.ape'=>'Text','s.idprof4'=>'Text','c.ref'=>"Text",'c.ref_client'=>"Text",'c.datec'=>"Date",'c.datep'=>"Date",
		//	'c.fin_validite'=>"Date",'c.total_ht'=>"Numeric",'c.total_ttc'=>"Numeric",'c.fk_statut'=>'Status','c.note_public'=>"Text",'c.note_private'=>"Text",
		//	'c.date_livraison'=>'Date','cd.description'=>"Text",'cd.product_type'=>'Boolean','cd.tva_tx'=>"Numeric",'cd.qty'=>"Numeric",'cd.total_ht'=>"Numeric",
		//	'cd.total_tva'=>"Numeric",'cd.total_ttc'=>"Numeric",'p.rowid'=>'List:product:label','p.ref'=>'Text','p.label'=>'Text'
		//);
		$this->export_TypeFields_array[$r] = array(
			's.nom'=>'Text', 'ps.nom'=>'Text', 's.code_client'=>'Text', 's.address'=>'Text', 's.zip'=>'Text', 's.town'=>'Text', 'co.code'=>'Text', 's.phone'=>'Text', 's.siren'=>'Text', 's.siret'=>'Text',
			's.ape'=>'Text', 's.idprof4'=>'Text', 'c.ref'=>"Text", 'c.ref_client'=>"Text", 'c.datec'=>"Date", 'c.datep'=>"Date", 'c.fin_validite'=>"Date",
			'c.total_ht'=>"Numeric", 'c.total_ttc'=>"Numeric", 'c.fk_statut'=>'Status', 'c.note_public'=>"Text", 'c.note_private'=>"Text", 'c.date_livraison'=>'Date',
			'pj.ref'=>'Text', 'cd.description'=>"Text", 'cd.product_type'=>'Boolean', 'cd.tva_tx'=>"Numeric", 'cd.qty'=>"Numeric", 'cd.total_ht'=>"Numeric",
			'cd.total_tva'=>"Numeric", 'cd.total_ttc'=>"Numeric", 'p.ref'=>'Text', 'p.label'=>'Text',
			'c.entity'=>'List:entity:label:rowid',
			'cir.label'=>'Text',
		);
		$this->export_entities_array[$r] = array(
			's.rowid'=>"company", 's.nom'=>'company', 'ps.nom'=>'company', 's.code_client'=>'company', 's.address'=>'company', 's.zip'=>'company', 's.town'=>'company', 'co.code'=>'company', 's.phone'=>'company',
			's.siren'=>'company', 's.ape'=>'company', 's.idprof4'=>'company', 's.siret'=>'company', 'c.rowid'=>"propal", 'c.ref'=>"propal", 'c.ref_client'=>"propal",
			'c.fk_soc'=>"propal", 'c.datec'=>"propal", 'c.datep'=>"propal", 'c.fin_validite'=>"propal", 'c.total_ht'=>"propal",
			'c.total_ttc'=>"propal", 'c.fk_statut'=>"propal", 'c.note_public'=>"propal", 'c.note_private'=>"propal", 'c.date_livraison'=>"propal",
			'c.fk_user_author'=>'user', 'uc.login'=>'user',
			'c.fk_user_valid'=>'user', 'uv.login'=>'user',
			'pj.ref'=>'project',
			'cd.rowid'=>'propal_line',
			'cd.description'=>"propal_line", 'cd.product_type'=>'propal_line', 'cd.tva_tx'=>"propal_line", 'cd.qty'=>"propal_line",
			'cd.total_ht'=>"propal_line", 'cd.total_tva'=>"propal_line", 'cd.total_ttc'=>"propal_line", 'p.rowid'=>'product', 'p.ref'=>'product', 'p.label'=>'product'
		);
		$this->export_dependencies_array[$r] = array('propal_line'=>'cd.rowid', 'product'=>'cd.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them
		$keyforselect = 'propal';
		$keyforelement = 'propal';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$keyforselect = 'propaldet';
		$keyforelement = 'propal_line';
		$keyforaliasextra = 'extra2';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$keyforselect = 'product';
		$keyforelement = 'product';
		$keyforaliasextra = 'extra3';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$keyforselect = 'societe';
		$keyforelement = 'company';
		$keyforaliasextra = 'extra4';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'societe as s ';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe_extrafields as extra4 ON s.rowid = extra4.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as ps ON ps.rowid = s.parent';
		if (!empty($user) && !$user->hasRight('societe', 'client', 'voir')) {
			$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe_commerciaux as sc ON sc.fk_soc = s.rowid';
		}
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as co ON s.fk_pays = co.rowid,';
		$this->export_sql_end[$r] .= ' '.MAIN_DB_PREFIX.'propal as c';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_input_reason as cir ON cir.rowid = c.fk_input_reason';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as pj ON c.fk_projet = pj.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as uc ON c.fk_user_author = uc.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as uv ON c.fk_user_valid = uv.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'propal_extrafields as extra ON c.rowid = extra.fk_object';
		$this->export_sql_end[$r] .= ', '.MAIN_DB_PREFIX.'propaldet as cd';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'propaldet_extrafields as extra2 on cd.rowid = extra2.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (cd.fk_product = p.rowid)';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_extrafields as extra3 on p.rowid = extra3.fk_object';
		$this->export_sql_end[$r] .= ' WHERE c.fk_soc = s.rowid AND c.rowid = cd.fk_propal';
		$this->export_sql_end[$r] .= ' AND c.entity IN ('.getEntity('propal').')';
		if (!empty($user) && !$user->hasRight('societe', 'client', 'voir')) {
			$this->export_sql_end[$r] .= ' AND sc.fk_user = '.(empty($user) ? 0 : $user->id);
		}

		// Imports
		//--------
		$r = 0;

		$r++;
		$this->import_code[$r] = $this->rights_class.'_'.$r;
		$this->import_label[$r] = 'Proposals'; // Translation key
		$this->import_icon[$r] = $this->picto;
		$this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r] = array('c' => MAIN_DB_PREFIX.'propal', 'extra' => MAIN_DB_PREFIX.'propal_extrafields');
		$this->import_tables_creator_array[$r] = array('c'=>'fk_user_author'); // Fields to store import user id
		$this->import_fields_array[$r] = array(
			'c.ref' => 'Ref*',
			'c.ref_client' => 'RefCustomer',
			'c.fk_soc' => 'ThirdPartyName*',
			'c.datec' => 'DateCreation',
			'c.datep' => 'DatePropal',
			'c.fin_validite' => 'DateEndPropal',
			'c.total_ht' => 'TotalHT',
			'c.total_ttc' => 'TotalTTC',
			'c.fk_statut' => 'Status*',
			'c.note_public' => 'NotePublic',
			'c.note_private' => 'NotePrivate',
			'c.date_livraison' => 'DeliveryDate',
			'c.fk_user_valid' => 'ValidatedById'
		);
		if (isModEnabled("multicurrency")) {
			$this->import_fields_array[$r]['c.multicurrency_code'] = 'Currency';
			$this->import_fields_array[$r]['c.multicurrency_tx'] = 'CurrencyRate';
			$this->import_fields_array[$r]['c.multicurrency_total_ht'] = 'MulticurrencyAmountHT';
			$this->import_fields_array[$r]['c.multicurrency_total_tva'] = 'MulticurrencyAmountVAT';
			$this->import_fields_array[$r]['c.multicurrency_total_ttc'] = 'MulticurrencyAmountTTC';
		}
		// Add extra fields
		$import_extrafield_sample = array();
		$sql = "SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE type <> 'separate' AND elementtype = 'propal' AND entity IN (0, ".$conf->entity.")";
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$fieldname = 'extra.'.$obj->name;
				$fieldlabel = ucfirst($obj->label);
				$this->import_fields_array[$r][$fieldname] = $fieldlabel.($obj->fieldrequired ? '*' : '');
				$import_extrafield_sample[$fieldname] = $fieldlabel;
			}
		}
		// End add extra fields
		$this->import_fieldshidden_array[$r] = ['extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'propal'];
		$this->import_regex_array[$r] = ['c.ref' => '[^ ]'];
		$import_sample = [
			'c.ref' => 'PROV0077',
			'c.ref_client' => 'Client1',
			'c.fk_soc' => 'MyBigCompany',
			'c.datec' => '2020-01-01',
			'c.datep' => '2020-01-01',
			'c.fin_validite' => '2020-01-01',
			'c.total_ht' => '0',
			'c.total_ttc' => '0',
			'c.fk_statut' => '1',
			'c.note_public' => '',
			'c.note_private' => '',
			'c.date_livraison' => '2020-01-01',
			'c.fk_user_valid' => '1',
			'c.multicurrency_code' => '',
			'c.multicurrency_tx' => '1',
			'c.multicurrency_total_ht' => '0',
			'c.multicurrency_total_tva' => '0',
			'c.multicurrency_total_ttc' => '0'
		];
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = array('c.ref'=>'Ref');
		$this->import_convertvalue_array[$r] = array(
			'c.ref' => array(
				'rule'=>'getrefifauto',
				'class'=>(!getDolGlobalString('PROPALE_ADDON') ? 'mod_propale_marbre' : $conf->global->PROPALE_ADDON),
				'path'=>"/core/modules/propale/".(!getDolGlobalString('PROPALE_ADDON') ? 'mod_propale_marbre' : $conf->global->PROPALE_ADDON).'.php',
				'classobject'=>'Propal',
				'pathobject'=>'/comm/propal/class/propal.class.php',
			),
			'c.fk_soc' => array(
				'rule' => 'fetchidfromref',
				'file' => '/societe/class/societe.class.php',
				'class' => 'Societe',
				'method' => 'fetch',
				'element' => 'ThirdParty'
			)
		);

		//Import Proposal Lines
		$r++;
		$this->import_code[$r] = $this->rights_class.'line_'.$r;
		$this->import_label[$r] = "ProposalLines"; // Translation key
		$this->import_icon[$r] = $this->picto;
		$this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r] = array(
			'cd' => MAIN_DB_PREFIX.'propaldet',
			'extra' => MAIN_DB_PREFIX.'propaldet_extrafields'
		);
		$this->import_fields_array[$r] = array(
			'cd.fk_propal' => 'Proposal*',
			'cd.fk_parent_line' => 'ParentLine',
			'cd.fk_product' => 'IdProduct',
			'cd.description' => 'LineDescription',
			'cd.product_type' => 'TypeOfLineServiceOrProduct',
			'cd.tva_tx' => 'LineVATRate',
			'cd.qty' => 'LineQty',
			'cd.remise_percent' => 'Reduc. Percent',
			'cd.price' => 'Price',
			'cd.subprice' => 'Sub Price',
			'cd.total_ht' => 'LineTotalHT',
			'cd.total_tva' => 'LineTotalVAT',
			'cd.total_ttc' => 'LineTotalTTC',
			'cd.date_start' => 'Start Date',
			'cd.date_end' => 'End Date',
			'cd.buy_price_ht' => 'LineBuyPriceHT'
		);
		if (isModEnabled("multicurrency")) {
			$this->import_fields_array[$r]['cd.multicurrency_code'] = 'Currency';
			$this->import_fields_array[$r]['cd.multicurrency_subprice'] = 'CurrencyRate';
			$this->import_fields_array[$r]['cd.multicurrency_total_ht'] = 'MulticurrencyAmountHT';
			$this->import_fields_array[$r]['cd.multicurrency_total_tva'] = 'MulticurrencyAmountVAT';
			$this->import_fields_array[$r]['cd.multicurrency_total_ttc'] = 'MulticurrencyAmountTTC';
		}
		// Add extra fields
		$import_extrafield_sample = array();
		$sql = "SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE type <> 'separate' AND elementtype = 'propaldet' AND entity IN (0, ".$conf->entity.")";
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$fieldname = 'extra.'.$obj->name;
				$fieldlabel = ucfirst($obj->label);
				$this->import_fields_array[$r][$fieldname] = $fieldlabel.($obj->fieldrequired ? '*' : '');
				$import_extrafield_sample[$fieldname] = $fieldlabel;
			}
		}
		// End add extra fields
		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'propaldet');
		$this->import_regex_array[$r] = array('cd.product_type' => '[0|1]$');
		$import_sample = array(
			'cd.fk_propal' => 'PROV(0001)',
			'cd.fk_parent_line' => '',
			'cd.fk_product' => '',
			'cd.description' => 'Line description',
			'cd.product_type' => '1',
			'cd.tva_tx' => '0',
			'cd.qty' => '2',
			'cd.remise_percent' => '0',
			'cd.price' => '',
			'cd.subprice' => '5000',
			'cd.total_ht' => '10000',
			'cd.total_tva' => '0',
			'cd.total_ttc' => '10100',
			'cd.date_start' => '',
			'cd.date_end' => '',
			'cd.buy_price_ht' => '7000',
			'cd.multicurrency_code' => 'JPY',
			'cd.multicurrency_tx' => '1',
			'cd.multicurrency_total_ht' => '10000',
			'cd.multicurrency_total_tva' => '0',
			'cd.multicurrency_total_ttc' => '10100'
		);
		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = array('cd.fk_propal' => 'Quotation Id', 'cd.fk_product' => 'Product Id');
		$this->import_convertvalue_array[$r] = array(
			'cd.fk_propal' => array(
				'rule'=>'fetchidfromref',
				'file'=>'/comm/propal/class/propal.class.php',
				'class'=>'Propal',
				'method'=>'fetch'
			)
		);
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

		// Remove permissions and default values
		$this->remove($options);

		//ODT template
		$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/proposals/template_proposal.odt';
		$dirodt = DOL_DATA_ROOT.'/doctemplates/proposals';
		$dest = $dirodt.'/template_proposal.odt';

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
			"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape($this->const[0][2])."' AND type = 'propal' AND entity = ".((int) $conf->entity),
			"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape($this->const[0][2])."','propal',".((int) $conf->entity).")",
		);

		return $this->_init($sql, $options);
	}
}
