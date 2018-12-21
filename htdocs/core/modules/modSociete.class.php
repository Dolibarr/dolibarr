<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012-2014 Juanjo Menent        <jmenent@2byte.es>
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
 *	\defgroup   societe     Module societe
 *	\brief      Module to manage third parties (customers, prospects)
 *	\file       htdocs/core/modules/modSociete.class.php
 *	\ingroup    societe
 *	\brief      Fichier de description et activation du module Societe
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Societe
 */
class modSociete extends DolibarrModules
{

	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf, $user;

		$this->db = $db;
		$this->numero = 1;

		$this->family = "crm";
		$this->module_position = '10';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Gestion des sociétés et contacts";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->config_page_url = array("societe.php@societe");
		// Name of image file used for this module.
		$this->picto='company';

		// Data directories to create when module is enabled
		$this->dirs = array("/societe/temp");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array("modExpedition","modFacture","modFournisseur","modFicheinter","modPropale","modContrat","modCommande");	// List of module ids to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->phpmin = array(5,4);		// Minimum version of PHP required by module
		$this->langfiles = array("companies",'bills');

		// Constants
		$this->const = array();
		$r=0;

		$this->const[$r][0] = "SOCIETE_CODECLIENT_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_codeclient_monkey";
		$this->const[$r][3] = 'Module to control third parties codes';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SOCIETE_CODECOMPTA_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_codecompta_panicum";
		$this->const[$r][3] = 'Module to control third parties codes';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "SOCIETE_FISCAL_MONTH_START";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "0";
		$this->const[$r][3] = "Enter the month number of the first month of the fiscal year, e. g. 9 for September";
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "COMPANY_ADDON_PDF_ODT_PATH";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "DOL_DATA_ROOT/doctemplates/thirdparties";
		$this->const[$r][3] = "";
		$this->const[$r][4] = 0;
		$r++;

		/*
		$this->const[$r][0] = "COMPANY_HIDE_INACTIVE_IN_COMBOBOX";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "0";
		$this->const[$r][3] = "hide thirdparty customer inative in combobox";
		$this->const[$r][4] = 1;
		$r++;
		*/

		$this->const[$r][0] = "SOCIETE_ADD_REF_IN_LIST";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = "0";
		$this->const[$r][3] = "Display customer ref into select list";
		$this->const[$r][4] = 0;
		$r++;

		// Boxes
		$this->boxes = array();
		$r=0;
		$this->boxes[$r][1] = "box_clients.php";
		$r++;
		$this->boxes[$r][1] = "box_prospect.php";
		$r++;
        $this->boxes[$r][1] = "box_contacts.php";
        $r++;
        $this->boxes[$r][1] = "box_activity.php";
        $this->boxes[$r][2] = '(WarningUsingThisBoxSlowDown)';
        $r++;
        $this->boxes[$r][1] = "box_goodcustomers.php";
        $this->boxes[$r][2] = '(WarningUsingThisBoxSlowDown)';
        $r++;
		// Permissions
		$this->rights = array();
		$this->rights_class = 'societe';
		$r=0;

		$r++;
		$this->rights[$r][0] = 121; // id de la permission
		$this->rights[$r][1] = 'Read third parties'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';

/*		$r++;
		$this->rights[$r][0] = 241;
		$this->rights[$r][1] = 'Read thirdparties customers';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'thirparty_customer_advance';      // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'read';

		$r++;
		$this->rights[$r][0] = 242;
		$this->rights[$r][1] = 'Read thirdparties suppliers';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'thirdparty_supplier_advance';      // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'read';
*/

		$r++;
		$this->rights[$r][0] = 122; // id de la permission
		$this->rights[$r][1] = 'Create and update third parties'; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';

/*		$r++;
		 $this->rights[$r][0] = 251;
		$this->rights[$r][1] = 'Create thirdparties customers';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'thirparty_customer_advance';      // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'read';

		$r++;
		$this->rights[$r][0] = 252;
		$this->rights[$r][1] = 'Create thirdparties suppliers';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'thirdparty_supplier_advance';      // Visible if option MAIN_USE_ADVANCED_PERMS is on
		$this->rights[$r][5] = 'read';
*/

		$r++;
		$this->rights[$r][0] = 125; // id de la permission
		$this->rights[$r][1] = 'Delete third parties'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 126; // id de la permission
		$this->rights[$r][1] = 'Export third parties'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'export';

		// 262 : Resteindre l'acces des commerciaux
		$r++;
		$this->rights[$r][0] = 262;
		$this->rights[$r][1] = 'Read all third parties by internal users (otherwise only if commercial contact). Not effective for external users (limited to themselves).';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'client';
		$this->rights[$r][5] = 'voir';

		$r++;
		$this->rights[$r][0] = 281; // id de la permission
		$this->rights[$r][1] = 'Read contacts'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'contact';
		$this->rights[$r][5] = 'lire';

		$r++;
		$this->rights[$r][0] = 282; // id de la permission
		$this->rights[$r][1] = 'Create and update contact'; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'contact';
		$this->rights[$r][5] = 'creer';

		$r++;
		$this->rights[$r][0] = 283; // id de la permission
		$this->rights[$r][1] = 'Delete contacts'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'contact';
		$this->rights[$r][5] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 286; // id de la permission
		$this->rights[$r][1] = 'Export contacts'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecie a ce jour)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'contact';
		$this->rights[$r][5] = 'export';


		// Menus
		//-------
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.


		// Exports
		//--------
		$r=0;

		// Export list of third parties and attributes
		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='ExportDataset_company_1';
		$this->export_icon[$r]='company';
		$this->export_permission[$r]=array(array("societe","export"));
		$this->export_fields_array[$r]=array(
			's.rowid'=>"Id",'s.nom'=>"Name",'s.name_alias'=>"AliasNames",'s.status'=>"Status",'s.client'=>"Customer",'s.fournisseur'=>"Supplier",'s.datec'=>"DateCreation",'s.tms'=>"DateLastModification",
			's.code_client'=>"CustomerCode",'s.code_fournisseur'=>"SupplierCode",'s.code_compta'=>"AccountancyCode",'s.code_compta_fournisseur'=>"SupplierAccountancyCode",
			's.address'=>"Address",'s.zip'=>"Zip",'s.town'=>"Town",'d.nom'=>'State','c.label'=>"Country",'c.code'=>"CountryCode",'s.phone'=>"Phone",'s.fax'=>"Fax",
			's.url'=>"Url",'s.email'=>"Email",'s.default_lang'=>"DefaultLang",'s.siren'=>"ProfId1",'s.siret'=>"ProfId2",'s.ape'=>"ProfId3",'s.idprof4'=>"ProfId4",
			's.idprof5'=>"ProfId5",'s.idprof6'=>"ProfId6",'s.tva_intra'=>"VATIntraShort",'s.capital'=>"Capital",'s.note_private'=>"NotePrivate",'s.note_public'=>"NotePublic",'s.canvas'=>"Canvas",
			't.libelle'=>"ThirdPartyType",'ce.code'=>"Staff","cfj.libelle"=>"JuridicalStatus",'s.fk_prospectlevel'=>'ProspectLevel',
			'st.code'=>'ProspectStatus','payterm.libelle'=>'PaymentConditions','paymode.libelle'=>'PaymentMode'
		);
		if (! empty($conf->global->SOCIETE_USEPREFIX)) $this->export_fields_array[$r]['s.prefix']='Prefix';
		// Add multicompany field
		if (! empty($conf->global->MULTICOMPANY_ENTITY_IN_EXPORT_IF_SHARED))
		{
		    $nbofallowedentities=count(explode(',',getEntity('societe')));    // If project are shared, nb will be > 1
		    if (! empty($conf->multicompany->enabled) && $nbofallowedentities > 1) $this->export_fields_array[$r]+=array('s.entity'=>'Entity');
		}
		$keyforselect='societe'; $keyforelement='company'; $keyforaliasextra='extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$this->export_fields_array[$r]+=array('u.login'=>'SaleRepresentativeLogin','u.firstname'=>'SaleRepresentativeFirstname', 'u.lastname'=>'SaleRepresentativeLastname');
		//$this->export_TypeFields_array[$r]=array(
		//	's.rowid'=>"List:societe:nom", 's.nom'=>"Text", 's.name_alias'=>"Text", 's.status'=>"Text",'s.client'=>"Boolean",'s.fournisseur'=>"Boolean",'s.datec'=>"Date",'s.tms'=>"Date",
		//	's.code_client'=>"Text",'s.code_fournisseur'=>"Text",'s.address'=>"Text",'s.zip'=>"Text",'s.town'=>"Text",'c.label'=>"List:c_country:label:label",
		//	'c.code'=>"Text",'s.phone'=>"Text",'s.fax'=>"Text",'s.url'=>"Text",'s.email'=>"Text",'s.default_lang'=>"Text",'s.siret'=>"Text",'s.siren'=>"Text",
		//	's.ape'=>"Text",'s.idprof4'=>"Text",'s.idprof5'=>"Text",'s.idprof6'=>"Text",'s.tva_intra'=>"Text",'s.capital'=>"Numeric",'s.note'=>"Text",
		//	't.libelle'=>"Text",'ce.code'=>"List:c_effectif:libelle:code","cfj.libelle"=>"Text",'s.fk_prospectlevel'=>'List:c_prospectlevel:label:code',
		//	's.fk_stcomm'=>'List:c_stcomm:libelle:code','d.nom'=>'List:c_departements:nom:rowid'
		//);
		$this->export_TypeFields_array[$r]=array(
			's.rowid'=>"Numeric", 's.nom'=>"Text", 's.name_alias'=>"Text", 's.status'=>"Numeric",'s.client'=>"Numeric",'s.fournisseur'=>"Boolean",'s.datec'=>"Date",'s.tms'=>"Date",
			's.code_client'=>"Text",'s.code_fournisseur'=>"Text",'s.code_compta'=>"Text",'s.code_compta_fournisseur'=>"Text",'s.address'=>"Text",'s.zip'=>"Text",
			's.town'=>"Text",'c.label'=>"List:c_country:label:label",'c.code'=>"Text",'s.phone'=>"Text",'s.fax'=>"Text",'s.url'=>"Text",'s.email'=>"Text",
			's.default_lang'=>"Text",'s.siret'=>"Text",'s.siren'=>"Text",'s.ape'=>"Text",'s.idprof4'=>"Text",'s.idprof5'=>"Text",'s.idprof6'=>"Text",
			's.tva_intra'=>"Text",'s.capital'=>"Numeric",'s.note_private'=>"Text",'s.note_public'=>"Text",'s.canvas'=>"Text",'t.libelle'=>"Text",
			'ce.code'=>"List:c_effectif:libelle:code","cfj.libelle"=>"Text",'s.fk_prospectlevel'=>'List:c_prospectlevel:label:code',
			'st.code'=>'List:c_stcomm:libelle:code','d.nom'=>'Text','u.login'=>'Text','u.firstname'=>'Text','u.lastname'=>'Text','payterm.libelle'=>'Text',
			'paymode.libelle'=>'Text','s.entity'=>'Numeric'
		);

		$this->export_entities_array[$r]=array('u.login'=>'user','u.firstname'=>'user','u.lastname'=>'user');	// We define here only fields that use another picto
		$this->export_examplevalues_array[$r]=array('s.client'=>'0 (no customer no prospect)/1 (customer)/2 (prospect)/3 (customer and prospect)','s.fournisseur'=>'0 (not a supplier) or 1 (supplier)');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'societe as s';
        $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe_extrafields as extra ON s.rowid = extra.fk_object';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_typent as t ON s.fk_typent = t.id';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON s.fk_pays = c.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_effectif as ce ON s.fk_effectif = ce.id';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_forme_juridique as cfj ON s.fk_forme_juridique = cfj.code';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_departements as d ON s.fk_departement = d.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_stcomm as st ON s.fk_stcomm = st.id';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe_commerciaux as sc ON sc.fk_soc = s.rowid LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON sc.fk_user = u.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_payment_term as payterm ON s.cond_reglement = payterm.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as paymode ON s.mode_reglement = paymode.id';
		$this->export_sql_end[$r] .=' WHERE s.entity IN ('.getEntity('societe').')';
		if (is_object($user) && empty($user->rights->societe->client->voir)) {
			$this->export_sql_end[$r] .=' AND (sc.fk_user = '.$user->id.' ';
			if (! empty($conf->global->SOCIETE_EXPORT_SUBORDINATES_CHILDS)) {
				$subordinatesids = $user->getAllChildIds();
				$this->export_sql_end[$r] .=count($subordinatesids)>0 ? ' OR (sc.fk_user IN ('.implode(',',$subordinatesids).')' : '';
			}
			$this->export_sql_end[$r] .=')';
		}

		// Export list of contacts and attributes
		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='ExportDataset_company_2';
		$this->export_icon[$r]='contact';
		$this->export_permission[$r]=array(array("societe","contact","export"));
		$this->export_fields_array[$r]=array(
			'c.rowid'=>"IdContact",'c.civility'=>"CivilityCode",'c.lastname'=>'Lastname','c.firstname'=>'Firstname','c.poste'=>'PostOrFunction',
			'c.datec'=>"DateCreation",'c.tms'=>"DateLastModification",'c.priv'=>"ContactPrivate",'c.address'=>"Address",'c.zip'=>"Zip",'c.town'=>"Town",
			'd.nom'=>'State','co.label'=>"Country",'co.code'=>"CountryCode",'c.phone'=>"Phone",'c.fax'=>"Fax",'c.phone_mobile'=>"Mobile",'c.email'=>"EMail",
			's.rowid'=>"IdCompany",'s.nom'=>"CompanyName",'s.status'=>"Status",'s.code_client'=>"CustomerCode",'s.code_fournisseur'=>"SupplierCode",
			's.client'=>'Customer','s.fournisseur'=>'Supplier'
		);
		$this->export_examplevalues_array[$r]=array('s.client'=>'0 (no customer no prospect)/1 (customer)/2 (prospect)/3 (customer and prospect)','s.fournisseur'=>'0 (not a supplier) or 1 (supplier)');
		$this->export_TypeFields_array[$r]=array(
			'c.civility'=>"List:c_civility:label:code",'c.lastname'=>'Text','c.firstname'=>'Text','c.poste'=>'Text','c.datec'=>"Date",'c.priv'=>"Boolean",
			'c.address'=>"Text",'c.zip'=>"Text",'c.town'=>"Text",'d.nom'=>'Text','co.label'=>"List:c_country:label:rowid",'co.code'=>"Text",'c.phone'=>"Text",
			'c.fax'=>"Text",'c.email'=>"Text",'s.rowid'=>"List:societe:nom",'s.nom'=>"Text",'s.status'=>"Status",'s.code_client'=>"Text",'s.code_fournisseur'=>"Text",
			's.client'=>"Text",'s.fournisseur'=>"Text"
		);
		$this->export_entities_array[$r]=array(
			's.rowid'=>"company",'s.nom'=>"company", 's.status'=>'company', 's.code_client'=>"company",'s.code_fournisseur'=>"company", 's.client'=>"company",
			's.fournisseur'=>"company"
		);	// We define here only fields that use another picto
        if (empty($conf->fournisseur->enabled))
        {
            unset($this->export_fields_array[$r]['s.code_fournisseur']);
            unset($this->export_entities_array[$r]['s.code_fournisseur']);
        }
        $keyforselect='socpeople'; $keyforelement='contact'; $keyforaliasextra='extra';
        include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
        $keyforselect='societe'; $keyforelement='company'; $keyforaliasextra='extrasoc';
        include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
        $this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'socpeople as c';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON c.fk_soc = s.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe_extrafields as extrasoc ON s.rowid = extrasoc.fk_object';
		if (is_object($user) && empty($user->rights->societe->client->voir)) $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'societe_commerciaux as sc ON sc.fk_soc = s.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_departements as d ON c.fk_departement = d.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as co ON c.fk_pays = co.rowid';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'socpeople_extrafields as extra ON extra.fk_object = c.rowid';
		$this->export_sql_end[$r] .=' WHERE c.entity IN ('.getEntity('socpeople').')';
		if (is_object($user) && empty($user->rights->societe->client->voir)) {
			$this->export_sql_end[$r] .=' AND (sc.fk_user = '.$user->id.' ';
			if (! empty($conf->global->SOCIETE_EXPORT_SUBORDINATES_CHILDS)) {
				$subordinatesids = $user->getAllChildIds();
				$this->export_sql_end[$r] .=count($subordinatesids)>0 ? ' OR (sc.fk_user IN ('.implode(',',$subordinatesids).')' : '';
			}
			$this->export_sql_end[$r] .=')';
		}


		// Imports
		//--------
		$r=0;

		// Import list of third parties and attributes
		$r++;
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]='ImportDataset_company_1';
		$this->import_icon[$r]='company';
		$this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r]=array('s'=>MAIN_DB_PREFIX.'societe','extra'=>MAIN_DB_PREFIX.'societe_extrafields');	// List of tables to insert into (insert done in same order)
		$this->import_fields_array[$r]=array(
			's.nom'=>"Name*", 's.name_alias'=>"Alias", 's.status'=>"Status",'s.client'=>"Customer*",'s.fournisseur'=>"Supplier*",'s.code_client'=>"CustomerCode",'s.code_fournisseur'=>"SupplierCode",
			's.code_compta'=>"CustomerAccountancyCode",'s.code_compta_fournisseur'=>"SupplierAccountancyCode",'s.address'=>"Address",'s.zip'=>"Zip",'s.town'=>"Town",
			's.fk_departement'=>"StateId",'s.fk_pays'=>"CountryCode",'s.phone'=>"Phone",'s.fax'=>"Fax",'s.url'=>"Url",'s.email'=>"Email",'s.siren'=>"ProfId1",
			's.siret'=>"ProfId2",'s.ape'=>"ProfId3",'s.idprof4'=>"ProfId4",'s.idprof5'=>"ProfId5",'s.idprof6'=>"ProfId6",'s.tva_intra'=>"VATIntraShort",
			's.capital'=>"Capital",'s.note_private'=>"NotePrivate",'s.note_public'=>"NotePublic",'s.canvas'=>"Canvas",'s.fk_typent'=>"ThirdPartyType",'s.fk_effectif'=>"Staff",
			"s.fk_forme_juridique"=>"JuridicalStatus",'s.fk_prospectlevel'=>'ProspectLevel','s.fk_stcomm'=>'ProspectStatus','s.default_lang'=>'DefaultLanguage',
			's.barcode'=>'BarCode','s.datec'=>"DateCreation"
		);
		// Add extra fields
		$sql="SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'societe' AND entity IN (0,".$conf->entity.")";
		$resql=$this->db->query($sql);
		if ($resql)    // This can fail when class is used on old database (during migration for example)
		{
		    while ($obj=$this->db->fetch_object($resql))
		    {
		        $fieldname='extra.'.$obj->name;
		        $fieldlabel=ucfirst($obj->label);
		        $this->import_fields_array[$r][$fieldname]=$fieldlabel.($obj->fieldrequired?'*':'');
		    }
		}
		// End add extra fields
		$this->import_fieldshidden_array[$r]=array('s.fk_user_creat'=>'user->id','extra.fk_object'=>'lastrowid-'.MAIN_DB_PREFIX.'societe');    // aliastable.field => ('user->id' or 'lastrowid-'.tableparent)
		$this->import_convertvalue_array[$r]=array(
			's.fk_typent'=>array('rule'=>'fetchidfromcodeorlabel','classfile'=>'/core/class/ctypent.class.php','class'=>'Ctypent','method'=>'fetch','dict'=>'DictionaryCompanyType'),
			's.fk_departement'=>array('rule'=>'fetchidfromcodeid','classfile'=>'/core/class/cstate.class.php','class'=>'Cstate','method'=>'fetch','dict'=>'DictionaryState'),
		    's.fk_pays'=>array('rule'=>'fetchidfromcodeid','classfile'=>'/core/class/ccountry.class.php','class'=>'Ccountry','method'=>'fetch','dict'=>'DictionaryCountry'),
			's.fk_stcomm'=>array('rule'=>'zeroifnull'),
		    's.code_client'=>array('rule'=>'getcustomercodeifauto'),
		    's.code_fournisseur'=>array('rule'=>'getsuppliercodeifauto'),
		    's.code_compta'=>array('rule'=>'getcustomeraccountancycodeifauto'),
		    's.code_compta_fournisseur'=>array('rule'=>'getsupplieraccountancycodeifauto'),
		    's.capital'=>array('rule'=>'numeric')
		);
		//$this->import_convertvalue_array[$r]=array('s.fk_soc'=>array('rule'=>'lastrowid',table='t');
		$this->import_regex_array[$r]=array('s.status'=>'^[0|1]','s.client'=>'^[0|1|2|3]','s.fournisseur'=>'^[0|1]','s.fk_typent'=>'id@'.MAIN_DB_PREFIX.'c_typent','s.datec'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]( [0-9][0-9]:[0-9][0-9]:[0-9][0-9])?$');
		$this->import_examplevalues_array[$r]=array(
			's.nom'=>"MyBigCompany", 's.name_alias'=>"MyBigAlias", 's.status'=>"0 (closed) or 1 (active)",'s.client'=>'0 (no customer no prospect)/1 (customer)/2 (prospect)/3 (customer and prospect)',
			's.fournisseur'=>'0 or 1','s.datec'=>dol_print_date(dol_now(),'%Y-%m-%d'),'s.code_client'=>'CU01-0001 or empty or "auto"','s.code_fournisseur'=>'SU01-0001 or empty or "auto"',
			's.address'=>"61 jump street",'s.zip'=>"123456",'s.town'=>"Big town",'s.fk_pays'=>'US, FR, DE...','s.phone'=>"0101010101",'s.fax'=>"0101010102",
			's.url'=>"http://mycompany.com",'s.email'=>"test@mycompany.com",'s.siret'=>"",'s.siren'=>"",'s.ape'=>"",'s.idprof4'=>"",'s.idprof5'=>"",'s.idprof6'=>"",
			's.tva_intra'=>"FR0123456789",'s.capital'=>"10000",'s.note_private'=>"This is an example of private note for record",'s.note_public'=>"This is an example of public note for record",
			's.fk_typent'=>"2",'s.fk_effectif'=>"3","s.fk_forme_juridique"=>"1",'s.fk_prospectlevel'=>'PL_MEDIUM','s.fk_stcomm'=>'0','s.default_lang'=>'en_US','s.barcode'=>'123456789',
			's.datec'=>"2015-01-01 or 2015-01-01 12:30:00"
		);
		$this->import_updatekeys_array[$r]=array('s.nom'=>'Name','s.code_client'=>'CustomerCode','s.code_fournisseur'=>'SupplierCode','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode');

		// Import list of contact and attributes
		$r++;
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]='ImportDataset_company_2';
		$this->import_icon[$r]='contact';
		$this->import_entities_array[$r]=array('s.fk_soc'=>'company');	// We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r]=array('s'=>MAIN_DB_PREFIX.'socpeople','extra'=>MAIN_DB_PREFIX.'socpeople_extrafields');	// List of tables to insert into (insert done in same order)
		$this->import_fields_array[$r]=array(
			's.fk_soc'=>'ThirdPartyName','s.civility'=>'UserTitle','s.lastname'=>"Lastname*",'s.firstname'=>"Firstname",'s.address'=>"Address",'s.zip'=>"Zip",
			's.town'=>"Town",'s.fk_departement'=>"StateId",'s.fk_pays'=>"CountryCode",'s.birthday'=>"BirthdayDate",'s.poste'=>"Role",'s.phone'=>"Phone",'s.phone_perso'=>"PhonePerso",
			's.phone_mobile'=>"PhoneMobile",'s.fax'=>"Fax",'s.email'=>"Email",'s.note_private'=>"Note",'s.note_public'=>"Note",'s.datec'=>"DateCreation"
		);
		// Add extra fields
		$sql="SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'socpeople' AND entity IN (0,".$conf->entity.")";
		$resql=$this->db->query($sql);
		if ($resql)    // This can fail when class is used on old database (during migration for example)
		{
		    while ($obj=$this->db->fetch_object($resql))
		    {
		        $fieldname='extra.'.$obj->name;
		        $fieldlabel=ucfirst($obj->label);
		        $this->import_fields_array[$r][$fieldname]=$fieldlabel.($obj->fieldrequired?'*':'');
		    }
		}
		// End add extra fields
		$this->import_fieldshidden_array[$r]=array('s.fk_user_creat'=>'user->id','extra.fk_object'=>'lastrowid-'.MAIN_DB_PREFIX.'socpeople');    // aliastable.field => ('user->id' or 'lastrowid-'.tableparent)
		$this->import_convertvalue_array[$r]=array(
			's.fk_soc'=>array('rule'=>'fetchidfromref','file'=>'/societe/class/societe.class.php','class'=>'Societe','method'=>'fetch','element'=>'ThirdParty'),
			's.fk_departement'=>array('rule'=>'fetchidfromcodeid','classfile'=>'/core/class/cstate.class.php','class'=>'Cstate','method'=>'fetch','dict'=>'DictionaryState'),
			's.fk_pays'=>array('rule'=>'fetchidfromcodeid','classfile'=>'/core/class/ccountry.class.php','class'=>'Ccountry','method'=>'fetch','dict'=>'DictionaryCountry'),
		);
		//$this->import_convertvalue_array[$r]=array('s.fk_soc'=>array('rule'=>'lastrowid',table='t');
		$this->import_regex_array[$r]=array('s.birthday'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$','s.datec'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]( [0-9][0-9]:[0-9][0-9]:[0-9][0-9])?$');
		$this->import_examplevalues_array[$r]=array(
			's.fk_soc'=>'MyBigCompany','s.civility'=>"MR",'s.lastname'=>"Smith",'s.firstname'=>'John','s.address'=>'61 jump street','s.zip'=>'75000',
			's.town'=>'Bigtown','s.fk_pays'=>'US, FR, DE...','s.datec'=>'1972-10-10','s.poste'=>"Director",'s.phone'=>"5551122",'s.phone_perso'=>"5551133",
			's.phone_mobile'=>"5551144",'s.fax'=>"5551155",'s.email'=>"johnsmith@email.com",'s.note_private'=>"My private note",'s.note_public'=>"My public note"
		);

		// Import Bank Accounts
		$r++;
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]="ImportDataset_company_3";	// Translation key
		$this->import_icon[$r]='company';
		$this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r]=array('sr'=>MAIN_DB_PREFIX.'societe_rib');
		$this->import_fields_array[$r]=array('sr.fk_soc'=>"ThirdPartyName*",'sr.bank'=>"Bank",
				'sr.code_banque'=>"BankCode",'sr.code_guichet'=>"DeskCode",'sr.number'=>"BankAccountNumber*",
				'sr.cle_rib'=>"BankAccountNumberKey",'sr.bic'=>"BIC",'sr.iban_prefix'=>"IBAN", 'sr.domiciliation'=>"BankAccountDomiciliation",'sr.proprio' => "BankAccountOwner", 'sr.owner_address' => "BankAccountOwnerAddress", 'sr.default_rib' => 'Default'
		);

		$this->import_convertvalue_array[$r]=array(
				'sr.fk_soc'=>array('rule'=>'fetchidfromref','classfile'=>'/societe/class/societe.class.php','class'=>'Societe','method'=>'fetch','element'=>'ThirdParty')
		);
		$this->import_examplevalues_array[$r]=array('sr.fk_soc'=>"MyBigCompany",'sr.bank'=>"ING",
				'sr.code_banque'=>"0000", 'sr.code_guichet'=>"1111",'sr.number'=>"3333333333",
				'sr.cle_rib'=>"22",'sr.bic'=>"USHINGMMXXX",'sr.iban_prefix'=>"US00 0000 1111 22 3333 3333",'sr.domiciliation'=>"PARIS",'sr.proprio' => "Name of owner", 'sr.owner_address' => "15 paris street 75000 Paris", 'sr.default_rib' => '1 or 0'
		);

		// Import Company Sales representatives
		$r++;
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]="ImportDataset_company_4";	// Translation key
		$this->import_icon[$r]='company';
		$this->import_entities_array[$r]=array('sr.fk_user'=>'user');		// We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r]=array('sr'=>MAIN_DB_PREFIX.'societe_commerciaux');
		$this->import_fields_array[$r]=array('sr.fk_soc'=>"ThirdPartyName*",'sr.fk_user'=>"User*");

		$this->import_convertvalue_array[$r]=array(
				'sr.fk_soc'=>array('rule'=>'fetchidfromref','classfile'=>'/societe/class/societe.class.php','class'=>'Societe','method'=>'fetch','element'=>'ThirdParty'),
				'sr.fk_user'=>array('rule'=>'fetchidfromref','classfile'=>'/user/class/user.class.php','class'=>'User','method'=>'fetch','element'=>'User')
		);
		$this->import_examplevalues_array[$r]=array('sr.fk_soc'=>"MyBigCompany",'sr.fk_user'=>"login");
	}


    /**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
     */
	function init($options='')
	{
		global $conf, $langs;

		// We disable this to prevent pb of modules not correctly disabled
		//$this->remove($options);

		//ODT template
		$src=DOL_DOCUMENT_ROOT.'/install/doctemplates/thirdparties/template_thirdparty.odt';
		$dirodt=DOL_DATA_ROOT.'/doctemplates/thirdparties';
		$dest=$dirodt.'/template_thirdparty.odt';

		if (file_exists($src) && ! file_exists($dest))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_mkdir($dirodt);
			$result=dol_copy($src,$dest,0,0);
			if ($result < 0)
			{
				$langs->load("errors");
				$this->error=$langs->trans('ErrorFailToCopyFile',$src,$dest);
				return 0;
			}
		}

		$sql = array();

		return $this->_init($sql,$options);
	}
}
