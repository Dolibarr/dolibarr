<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2005-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012-2016 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2020      Stéphane Lesage		<stephane.lesage@ateis.com>
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
 *      \defgroup   category       Module categories
 *      \brief      Module to manage categories
 *      \file       htdocs/core/modules/modCategorie.class.php
 *      \ingroup    category
 *      \brief      Description and activation file for the module Category
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';


/**
 *	Class to describe and enable module Categorie
 */
class modCategorie extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 1780;

		$this->family = "technic";
		$this->module_position = '25';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Gestion des categories (produits, clients, fournisseurs...)";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'category';

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Dependencies
		$this->depends = array();

		// Config pages
		$this->config_page_url = array('categorie.php@categories');
		$this->langfiles = array("products", "companies", "categories", "members", "stocks", "website");

		// Constants
		$this->const = array();
		$r = 0;
		$this->const[$r][0] = "CATEGORIE_RECURSIV_ADD";
		$this->const[$r][1] = "yesno";
		$this->const[$r][2] = "0";
		$this->const[$r][3] = 'Affect parent categories';
		$this->const[$r][4] = 0;
		$r++;

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'categorie';

		$r = 0;

		$this->rights[$r][0] = 241; // id de la permission
		$this->rights[$r][1] = 'Lire les categories'; // libelle de la permission
		$this->rights[$r][2] = 'r'; // type de la permission (deprecated)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'lire';
		$r++;

		$this->rights[$r][0] = 242; // id de la permission
		$this->rights[$r][1] = 'Creer/modifier les categories'; // libelle de la permission
		$this->rights[$r][2] = 'w'; // type de la permission (deprecated)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'creer';
		$r++;

		$this->rights[$r][0] = 243; // id de la permission
		$this->rights[$r][1] = 'Supprimer les categories'; // libelle de la permission
		$this->rights[$r][2] = 'd'; // type de la permission (deprecated)
		$this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
		$this->rights[$r][4] = 'supprimer';
		$r++;


		// Menus
		//-------
		$this->menu = 1; // This module add menu entries. They are coded into menu manager.


		// Exports
		//--------
		$r = 0;

		// All Categories List
		$r++;
		$this->export_code[$r] = $this->rights_class.'_list';
		$this->export_label[$r] = 'CatListAll';
		$this->export_icon[$r] = $this->picto;
		$this->export_enabled[$r] = 'true';
		$this->export_permission[$r] = array(array("categorie", "lire"));

		$typeexample = "";
		if (isModEnabled("product") || isModEnabled("service")) {
			$typeexample .= ($typeexample ? " / " : "")."0=Product-Service";
		}
		if (isModEnabled("supplier_order") || isModEnabled("supplier_invoice")) {
			$typeexample .= ($typeexample ? "/" : "")."1=Supplier";
		}
		if (isModEnabled("societe")) {
			$typeexample .= ($typeexample ? " / " : "")."2=Customer-Prospect";
		}
		if (isModEnabled('adherent')) {
			$typeexample .= ($typeexample ? " / " : "")."3=Member";
		}
		if (isModEnabled("societe")) {
			$typeexample .= ($typeexample ? " / " : "")."4=Contact";
		}
		if (isModEnabled('bank')) {
			$typeexample .= ($typeexample ? " / " : "")."5=Bank account";
		}
		if (isModEnabled('project')) {
			$typeexample .= ($typeexample ? " / " : "")."6=Project";
		}
		if (isModEnabled('user')) {
			$typeexample .= ($typeexample ? " / " : "")."7=User";
		}
		if (isModEnabled('bank')) {
			$typeexample .= ($typeexample ? " / " : "")."8=Bank line";
		}
		if (isModEnabled('stock')) {
			$typeexample .= ($typeexample ? " / " : "")."9=Warehouse";
		}
		if (isModEnabled('agenda')) {
			$typeexample .= ($typeexample ? " / " : "")."10=Agenda event";
		}
		if (isModEnabled('website')) {
			$typeexample .= ($typeexample ? " / " : "")."11=Website page";
		}

		// Definition of vars
		$this->export_fields_array[$r] = array('cat.rowid'=>"CategId", 'cat.label'=>"Label", 'cat.type'=>"Type", 'cat.description'=>"Description", 'cat.fk_parent'=>"ParentCategoryID", 'pcat.label'=>"ParentCategoryLabel", 'cat.color'=>"Color", 'cat.date_creation'=>"DateCreation", 'cat.tms'=>"DateLastModification");
		$this->export_TypeFields_array[$r] = array('cat.rowid'=>'Numeric', 'cat.label'=>"Text", 'cat.type'=>"Numeric", 'cat.description'=>"Text", 'cat.fk_parent'=>'Numeric', 'pcat.label'=>'Text');
		$this->export_entities_array[$r] = array(); // We define here only fields that use another picto
		$this->export_help_array[$r] = array('cat.type'=>$typeexample);

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'categorie as cat';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie as pcat ON pcat.rowid = cat.fk_parent';
		$this->export_sql_end[$r] .= ' WHERE cat.entity IN ('.getEntity('category').')';

		// 0 Products
		$r++;
		$this->export_code[$r] = $this->rights_class.'_0_'.Categorie::$MAP_ID_TO_CODE[0];
		$this->export_label[$r] = 'CatProdList';
		$this->export_icon[$r] = $this->picto;
		$this->export_enabled[$r] = 'isModEnabled("product") || isModEnabled("service")';
		$this->export_permission[$r] = array(array("categorie", "lire"), array("produit", "export"));
		$this->export_fields_array[$r] = array('cat.rowid'=>"CategId", 'cat.label'=>"Label", 'cat.description'=>"Description", 'cat.fk_parent'=>"ParentCategoryID", 'pcat.label'=>"ParentCategoryLabel", 'cat.color'=>"Color", 'cat.date_creation'=>"DateCreation", 'cat.tms'=>"DateLastModification", 'p.rowid'=>'ProductId', 'p.ref'=>'Ref', 'p.label'=>'Label');
		$this->export_TypeFields_array[$r] = array('cat.rowid'=>'Numeric', 'cat.label'=>"Text", 'cat.description'=>"Text", 'cat.fk_parent'=>'Numeric', 'pcat.label'=>'Text', 'p.rowid'=>'Numeric', 'p.ref'=>'Text', 'p.label'=>'Text');
		$this->export_entities_array[$r] = array('p.rowid'=>'product', 'p.ref'=>'product', 'p.label'=>'product'); // We define here only fields that use another picto

		$keyforselect = 'product';
		$keyforelement = 'product';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'categorie as cat';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie as pcat ON pcat.rowid = cat.fk_parent';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_categorie = cat.rowid';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'product as p ON p.rowid = cp.fk_product';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_extrafields as extra ON extra.fk_object = p.rowid';
		$this->export_sql_end[$r] .= ' WHERE cat.entity IN ('.getEntity('category').')';
		$this->export_sql_end[$r] .= ' AND cat.type = 0';

		// 1 Suppliers
		$r++;
		$this->export_code[$r] = $this->rights_class.'_1_'.Categorie::$MAP_ID_TO_CODE[1];
		$this->export_label[$r] = 'CatSupList';
		$this->export_icon[$r] = $this->picto;
		$this->export_enabled[$r] = 'isModEnabled("supplier_order") || isModEnabled("supplier_invoice")';
		$this->export_permission[$r] = array(array("categorie", "lire"), array("fournisseur", "lire"));
		$this->export_fields_array[$r] = array(
			'cat.rowid'=>"CategId", 'cat.label'=>"Label", 'cat.description'=>"Description", 'cat.fk_parent'=>"ParentCategoryID", 'pcat.label'=>"ParentCategoryLabel",
			'cat.color'=>"Color", 'cat.date_creation'=>"DateCreation", 'cat.tms'=>"DateLastModification",
			's.rowid'=>'IdThirdParty', 's.nom'=>'Name', 's.prefix_comm'=>"Prefix", 's.fournisseur'=>"Supplier", 's.datec'=>"DateCreation", 's.tms'=>"DateLastModification", 's.code_fournisseur'=>"SupplierCode",
			's.address'=>"Address", 's.zip'=>"Zip", 's.town'=>"Town", 'c.label'=>"Country", 'c.code'=>"CountryCode",
			's.phone'=>"Phone", 's.fax'=>"Fax", 's.url'=>"Url", 's.email'=>"Email",
			's.siret'=>"ProfId1", 's.siren'=>"ProfId2", 's.ape'=>"ProfId3", 's.idprof4'=>"ProfId4", 's.tva_intra'=>"VATIntraShort", 's.capital'=>"Capital", 's.note_public'=>"NotePublic",
			't.libelle'=>'ThirdPartyType'
		);
		$this->export_TypeFields_array[$r] = array(
			'cat.rowid'=>'Numeric', 'cat.label'=>"Text", 'cat.description'=>"Text", 'cat.fk_parent'=>'Numeric', 'pcat.label'=>'Text',
			's.rowid'=>'List:societe:nom', 's.nom'=>'Text', 's.prefix_comm'=>"Text", 's.fournisseur'=>"Text", 's.datec'=>"Date", 's.tms'=>"Date", 's.code_fournisseur'=>"Text",
			's.address'=>"Text", 's.zip'=>"Text", 's.town'=>"Text", 'c.label'=>"List:c_country:label:label", 'c.code'=>"Text",
			's.phone'=>"Text", 's.fax'=>"Text", 's.url'=>"Text", 's.email'=>"Text",
			's.siret'=>"Text", 's.siren'=>"Text", 's.ape'=>"Text", 's.idprof4'=>"Text", 's.tva_intra'=>"Text", 's.capital'=>"Numeric", 's.note_public'=>"Text",
			't.libelle'=>'List:c_typent:libelle:code'
		);
		$this->export_entities_array[$r] = array(
			's.rowid'=>'company', 's.nom'=>'company', 's.prefix_comm'=>"company", 's.fournisseur'=>"company", 's.datec'=>"company", 's.tms'=>"company", 's.code_fournisseur'=>"company",
			's.address'=>"company", 's.zip'=>"company", 's.town'=>"company", 'c.label'=>"company", 'c.code'=>"company",
			's.phone'=>"company", 's.fax'=>"company", 's.url'=>"company", 's.email'=>"company",
			's.siret'=>"company", 's.siren'=>"company", 's.ape'=>"company", 's.idprof4'=>"company", 's.tva_intra'=>"company", 's.capital'=>"company", 's.note_public'=>"company",
			't.libelle'=>'company'
		); // We define here only fields that use another picto

		$keyforselect = 'societe';
		$keyforelement = 'company';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'categorie as cat';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie as pcat ON pcat.rowid = cat.fk_parent';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'categorie_fournisseur as cf ON cf.fk_categorie = cat.rowid';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'societe as s ON s.rowid = cf.fk_soc';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe_extrafields as extra ON s.rowid = extra.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON s.fk_pays = c.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_typent as t ON s.fk_typent = t.id';
		$this->export_sql_end[$r] .= ' WHERE cat.entity IN ('.getEntity('category').')';
		$this->export_sql_end[$r] .= ' AND cat.type = 1';

		// 2 Customers/Prospects
		$r++;
		$this->export_code[$r] = $this->rights_class.'_2_'.Categorie::$MAP_ID_TO_CODE[2];
		$this->export_label[$r] = 'CatCusList';
		$this->export_icon[$r] = $this->picto;
		$this->export_enabled[$r] = 'isModEnabled("societe")';
		$this->export_permission[$r] = array(array("categorie", "lire"), array("societe", "export"));
		$this->export_fields_array[$r] = array(
			'cat.rowid'=>"CategId", 'cat.label'=>"Label", 'cat.description'=>"Description", 'cat.fk_parent'=>"ParentCategoryID", 'pcat.label'=>"ParentCategoryLabel",
			'cat.color'=>"Color", 'cat.date_creation'=>"DateCreation", 'cat.tms'=>"DateLastModification",
			's.rowid'=>'IdThirdParty', 's.nom'=>'Name', 's.prefix_comm'=>"Prefix", 's.client'=>"Customer", 's.datec'=>"DateCreation", 's.tms'=>"DateLastModification", 's.code_client'=>"CustomerCode",
			's.address'=>"Address", 's.zip'=>"Zip", 's.town'=>"Town", 'c.label'=>"Country", 'c.code'=>"CountryCode",
			's.phone'=>"Phone", 's.fax'=>"Fax", 's.url'=>"Url", 's.email'=>"Email",
			's.siret'=>"ProfId1", 's.siren'=>"ProfId2", 's.ape'=>"ProfId3", 's.idprof4'=>"ProfId4", 's.tva_intra'=>"VATIntraShort", 's.capital'=>"Capital", 's.note_public'=>"NotePublic",
			't.libelle'=>'ThirdPartyType', 'pl.code'=>'ProspectLevel', 'st.code'=>'ProspectStatus'
		);
		$this->export_TypeFields_array[$r] = array(
			'cat.rowid'=>'Numeric', 'cat.label'=>"Text", 'cat.description'=>"Text", 'cat.fk_parent'=>'Numeric', 'pcat.label'=>'Text',
			's.rowid'=>'List:societe:nom', 's.nom'=>'Text', 's.prefix_comm'=>"Text", 's.client'=>"Text", 's.datec'=>"Date", 's.tms'=>"Date", 's.code_client'=>"Text",
			's.address'=>"Text", 's.zip'=>"Text", 's.town'=>"Text", 'c.label'=>"List:c_country:label:label", 'c.code'=>"Text",
			's.phone'=>"Text", 's.fax'=>"Text", 's.url'=>"Text", 's.email'=>"Text",
			's.siret'=>"Text", 's.siren'=>"Text", 's.ape'=>"Text", 's.idprof4'=>"Text", 's.tva_intra'=>"Text", 's.capital'=>"Numeric", 's.note_public'=>"Text",
			't.libelle'=>'List:c_typent:libelle:code', 'pl.code'=>'List:c_prospectlevel:label:code', 'st.code'=>'List:c_stcomm:libelle:code'
		);
		$this->export_entities_array[$r] = array(
			's.rowid'=>'company', 's.nom'=>'company', 's.prefix_comm'=>"company", 's.client'=>"company", 's.datec'=>"company", 's.tms'=>"company", 's.code_client'=>"company",
			's.address'=>"company", 's.zip'=>"company", 's.town'=>"company", 'c.label'=>"company", 'c.code'=>"company",
			's.phone'=>"company", 's.fax'=>"company", 's.url'=>"company", 's.email'=>"company",
			's.siret'=>"company", 's.siren'=>"company", 's.ape'=>"company", 's.idprof4'=>"company", 's.tva_intra'=>"company", 's.capital'=>"company", 's.note_public'=>"company",
			't.libelle'=>'company', 'pl.code'=>'company', 'st.code'=>'company'
		); // We define here only fields that use another picto

		$keyforselect = 'societe';
		$keyforelement = 'company';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'categorie as cat';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie as pcat ON pcat.rowid = cat.fk_parent';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'categorie_societe as cs ON cs.fk_categorie = cat.rowid';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'societe as s ON s.rowid = cs.fk_soc';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe_extrafields as extra ON s.rowid = extra.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON s.fk_pays = c.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_typent as t ON s.fk_typent = t.id';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_prospectlevel as pl ON s.fk_prospectlevel = pl.code';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_stcomm as st ON s.fk_stcomm = st.id';
		$this->export_sql_end[$r] .= ' WHERE cat.entity IN ('.getEntity('category').')';
		$this->export_sql_end[$r] .= ' AND cat.type = 2';

		// 3 Members
		$r++;
		$this->export_code[$r] = $this->rights_class.'_3_'.Categorie::$MAP_ID_TO_CODE[3];
		$this->export_label[$r] = 'CatMemberList';
		$this->export_icon[$r] = $this->picto;
		$this->export_enabled[$r] = 'isModEnabled("adherent")';
		$this->export_permission[$r] = array(array("categorie", "lire"), array("adherent", "export"));
		$this->export_fields_array[$r] = array('cat.rowid'=>"CategId", 'cat.label'=>"Label", 'cat.description'=>"Description", 'cat.fk_parent'=>"ParentCategoryID", 'pcat.label'=>"ParentCategoryLabel", 'p.rowid'=>'MemberId', 'p.lastname'=>'LastName', 'p.firstname'=>'Firstname');
		$this->export_TypeFields_array[$r] = array('cat.rowid'=>"Numeric", 'cat.label'=>"Text", 'cat.description'=>"Text", 'cat.fk_parent'=>'Numeric', 'pcat.label'=>'Text', 'p.lastname'=>'Text', 'p.firstname'=>'Text');
		$this->export_entities_array[$r] = array('p.rowid'=>'member', 'p.lastname'=>'member', 'p.firstname'=>'member'); // We define here only fields that use another picto

		$keyforselect = 'adherent';
		$keyforelement = 'member';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'categorie as cat';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie as pcat ON pcat.rowid = cat.fk_parent';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'categorie_member as cm ON cm.fk_categorie = cat.rowid';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'adherent as p ON p.rowid = cm.fk_member';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'adherent_extrafields as extra ON cat.rowid = extra.fk_object ';
		$this->export_sql_end[$r] .= ' WHERE cat.entity IN ('.getEntity('category').')';
		$this->export_sql_end[$r] .= ' AND cat.type = 3';

		// 4 Contacts
		$r++;
		$this->export_code[$r] = $this->rights_class.'_4_'.Categorie::$MAP_ID_TO_CODE[4];
		$this->export_label[$r] = 'CatContactList';
		$this->export_icon[$r] = $this->picto;
		$this->export_enabled[$r] = 'isModEnabled("societe")';
		$this->export_permission[$r] = array(array("categorie", "lire"), array("societe", "contact", "export"));
		$this->export_fields_array[$r] = array(
			'cat.rowid'=>"CategId", 'cat.label'=>"Label", 'cat.description'=>"Description", 'cat.fk_parent'=>"ParentCategoryID", 'pcat.label'=>"ParentCategoryLabel",
			'cat.color'=>"Color", 'cat.date_creation'=>"DateCreation", 'cat.tms'=>"DateLastModification",
			'p.rowid' => 'ContactId', 'civ.label' => 'UserTitle', 'p.lastname' => 'LastName', 'p.firstname' => 'Firstname',
			'p.address' => 'Address', 'p.zip' => 'Zip', 'p.town' => 'Town', 'c.code' => 'CountryCode', 'c.label' => 'Country',
			'p.birthday' => 'DateOfBirth', 'p.poste' => 'PostOrFunction',
			'p.phone' => 'Phone', 'p.phone_perso' => 'PhonePerso', 'p.phone_mobile' => 'PhoneMobile', 'p.fax' => 'Fax', 'p.email' => 'Email',
			'p.note_private' => 'NotePrivate', 'p.note_public' => 'NotePublic', 'p.statut' => 'Status',
			's.nom'=>"Name", 's.client'=>"Customer", 's.fournisseur'=>"Supplier", 's.status'=>"Status",
			's.address'=>"Address", 's.zip'=>"Zip", 's.town'=>"Town",
			's.phone'=>"Phone", 's.fax'=>"Fax", 's.url'=>"Url", 's.email'=>"Email"
		);
		$this->export_TypeFields_array[$r] = array(
			'cat.rowid'=>'Numeric', 'cat.label'=>"Text", 'cat.description'=>"Text", 'cat.fk_parent'=>'Numeric', 'pcat.label'=>'Text',
			'civ.label' => 'List:c_civility:label:label', 'p.rowid'=>'Numeric', 'p.lastname' => 'Text', 'p.firstname' => 'Text',
			'p.address' => 'Text', 'p.zip' => 'Text', 'p.town' => 'Text', 'c.code' => 'Text', 'c.label' => 'List:c_country:label:label',
			'p.birthday' => 'Date', 'p.poste' => 'Text',
			'p.phone' => 'Text', 'p.phone_perso' => 'Text', 'p.phone_mobile' => 'Text', 'p.fax' => 'Text', 'p.email' => 'Text',
			'p.note_private' => 'Text', 'p.note_public' => 'Text', 'p.statut' => 'Boolean',
			's.nom'=>"Text", 's.client'=>"Boolean", 's.fournisseur'=>"Boolean", 's.status'=>"Boolean",
			's.address'=>"Text", 's.zip'=>"Text", 's.town'=>"Text",
			's.phone'=>"Text", 's.fax'=>"Text", 's.url'=>"Text", 's.email'=>"Text"
		);
		$this->export_entities_array[$r] = array(
			'p.rowid' => 'contact', 'civ.label' => 'contact', 'p.lastname' => 'contact', 'p.firstname' => 'contact',
			'p.address' => 'contact', 'p.zip' => 'contact', 'p.town' => 'contact', 'c.code' => 'contact', 'c.label' => 'contact',
			'p.birthday' => 'contact', 'p.poste' => 'contact',
			'p.phone' => 'contact', 'p.phone_perso' => 'contact', 'p.phone_mobile' => 'contact', 'p.fax' => 'contact', 'p.email' => 'contact',
			'p.note_private' => 'contact', 'p.note_public' => 'contact', 'p.statut' => 'contact',
			's.nom'=>"company", 's.client'=>"company", 's.fournisseur'=>"company", 's.status'=>"company",
			's.address'=>"company", 's.zip'=>"company", 's.town'=>"company",
			's.phone'=>"company", 's.fax'=>"company", 's.url'=>"company", 's.email'=>"company"
		); // We define here only fields that use another picto

		$keyforselect = 'socpeople';
		$keyforelement = 'contact';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'categorie as cat';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie as pcat ON pcat.rowid = cat.fk_parent';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'categorie_contact as cc ON cc.fk_categorie = cat.rowid';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'socpeople as p ON p.rowid = cc.fk_socpeople';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'socpeople_extrafields as extra ON extra.fk_object = p.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_civility as civ ON civ.code = p.civility';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON c.rowid = p.fk_pays';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON s.rowid = p.fk_soc';
		$this->export_sql_end[$r] .= ' WHERE cat.entity IN ('.getEntity('category').')';
		$this->export_sql_end[$r] .= ' AND cat.type = 4';

		// 5 Bank accounts, TODO ?

		// 6 Projects
		$r++;
		$this->export_code[$r] = $this->rights_class.'_6_'.Categorie::$MAP_ID_TO_CODE[6];
		$this->export_label[$r] = 'CatProjectsList';
		$this->export_icon[$r] = $this->picto;
		$this->export_enabled[$r] = "isModEnabled('project')";
		$this->export_permission[$r] = array(array("categorie", "lire"), array("projet", "export"));
		$this->export_fields_array[$r] = array('cat.rowid'=>"CategId", 'cat.label'=>"Label", 'cat.description'=>"Description", 'cat.fk_parent'=>"ParentCategory", 'pcat.label'=>"ParentCategoryLabel", 'cat.color'=>"Color", 'cat.date_creation'=>"DateCreation", 'cat.tms'=>"DateLastModification", 'p.rowid'=>'ProjectId', 'p.ref'=>'Ref', 's.rowid'=>"IdThirdParty", 's.nom'=>"Name");
		$this->export_TypeFields_array[$r] = array('cat.rowid'=>'Numeric', 'cat.label'=>"Text", 'cat.description'=>"Text", 'cat.fk_parent'=>'Numeric', 'pcat.label'=>'Text', 'p.rowid'=>'Numeric', 'p.ref'=>'Text', 's.rowid'=>"Numeric", 's.nom'=>"Text");
		$this->export_entities_array[$r] = array('p.rowid'=>'project', 'p.ref'=>'project', 's.rowid'=>"company", 's.nom'=>"company"); // We define here only fields that use another picto

		$keyforselect = 'projet';
		$keyforelement = 'project';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'categorie as cat';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie as pcat ON pcat.rowid = cat.fk_parent';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'categorie_project as cp ON cp.fk_categorie = cat.rowid';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'projet as p ON p.rowid = cp.fk_project';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet_extrafields as extra ON extra.fk_object = p.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON s.rowid = p.fk_soc';
		$this->export_sql_end[$r] .= ' WHERE cat.entity IN ('.getEntity('category').')';
		$this->export_sql_end[$r] .= ' AND cat.type = 6';

		// 7 Users
		$r++;
		$this->export_code[$r] = $this->rights_class.'_7_'.Categorie::$MAP_ID_TO_CODE[7];
		$this->export_label[$r] = 'CatUsersList';
		$this->export_icon[$r] = $this->picto;
		$this->export_enabled[$r] = 'isModEnabled("user")';
		$this->export_permission[$r] = array(array("categorie", "lire"), array("user", "export"));
		$this->export_fields_array[$r] = array('cat.rowid'=>"CategId", 'cat.label'=>"Label", 'cat.description'=>"Description", 'cat.fk_parent'=>"ParentCategory", 'pcat.label'=>"ParentCategoryLabel", 'cat.color'=>"Color", 'cat.date_creation'=>"DateCreation", 'cat.tms'=>"DateLastModification", 'p.rowid'=>'UserID', 'p.login'=>'Login', 'p.lastname'=>'Lastname', 'p.firstname'=>'Firstname');
		$this->export_TypeFields_array[$r] = array('cat.rowid'=>"Numeric", 'cat.label'=>"Text", 'cat.description'=>"Text", 'cat.fk_parent'=>'Numeric', 'pcat.label'=>'Text', 'p.rowid'=>'Numeric', 'p.login'=>'Text', 'p.lastname'=>'Text', 'p.firstname'=>'Text');
		$this->export_entities_array[$r] = array('p.rowid'=>'user', 'p.login'=>'user', 'p.lastname'=>'user', 'p.firstname'=>'user'); // We define here only fields that use another picto

		$keyforselect = 'user';
		$keyforelement = 'user';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'categorie as cat';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie as pcat ON pcat.rowid = cat.fk_parent';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'categorie_user as cu ON cu.fk_categorie = cat.rowid';
		$this->export_sql_end[$r] .= ' INNER JOIN '.MAIN_DB_PREFIX.'user as p ON p.rowid = cu.fk_user';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user_extrafields as extra ON extra.fk_object = p.rowid';
		$this->export_sql_end[$r] .= ' WHERE cat.entity IN ('.getEntity('category').')';
		$this->export_sql_end[$r] .= ' AND cat.type = 7';

		// 8 Bank Lines, TODO ?

		// 9 Warehouses, TODO ?

		// 10 Agenda Events, TODO ?

		// 11 Website Pages, TODO ?

		// Imports
		//--------

		$r = 0;

		// Categories
		$r++;
		$this->import_code[$r] = $this->rights_class.'_list';
		$this->import_label[$r] = "CatList"; // Translation key
		$this->import_icon[$r] = $this->picto;
		$this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r] = array('ca'=>MAIN_DB_PREFIX.'categorie');
		$this->import_fields_array[$r] = array(
			'ca.label'=>"Label*", 'ca.type'=>"Type*", 'ca.description'=>"Description",
			'ca.fk_parent' => 'ParentCategory'
		);
		$this->import_regex_array[$r] = array('ca.type'=>'^(0|1|2|3|4|5|6|7|8|9|10|11)$');
		$this->import_convertvalue_array[$r] = array(
			'ca.fk_parent' => array(
				'rule'          => 'fetchidfromcodeandlabel',
				'classfile'     => '/categories/class/categorie.class.php',
				'class'         => 'Categorie',
				'method'        => 'fetch',
				'element'       => 'category',
				'codefromfield' => 'ca.type'
			)
		);

		$this->import_examplevalues_array[$r] = array(
			'ca.label'=>"My Category Label", 'ca.type'=>$typeexample, 'ca.description'=>"My Category description", // $typeexample built above in exports
			'ca.fk_parent' => 'rowid or label'
		);
		$this->import_updatekeys_array[$r] = array('ca.label'=>'Label', 'ca.type' => 'Type');

		// 0 Products
		if (isModEnabled("product")) {
			$r++;
			$this->import_code[$r] = $this->rights_class.'_0_'.Categorie::$MAP_ID_TO_CODE[0];
			$this->import_label[$r] = "CatProdLinks"; // Translation key
			$this->import_icon[$r] = $this->picto;
			$this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
			$this->import_tables_array[$r] = array('cp'=>MAIN_DB_PREFIX.'categorie_product');
			$this->import_fields_array[$r] = array('cp.fk_categorie'=>"Category*", 'cp.fk_product'=>"Product*");
			$this->import_regex_array[$r] = array('cp.fk_categorie'=>'rowid@'.MAIN_DB_PREFIX.'categorie:type=0');

			$this->import_convertvalue_array[$r] = array(
					'cp.fk_categorie'=>array('rule'=>'fetchidfromref', 'classfile'=>'/categories/class/categorie.class.php', 'class'=>'Categorie', 'method'=>'fetch', 'element'=>'category'),
					'cp.fk_product'=>array('rule'=>'fetchidfromref', 'classfile'=>'/product/class/product.class.php', 'class'=>'Product', 'method'=>'fetch', 'element'=>'Product')
			);
			$this->import_examplevalues_array[$r] = array('cp.fk_categorie'=>"rowid or label", 'cp.fk_product'=>"rowid or ref");
			$this->import_updatekeys_array[$r] = array('cp.fk_categorie' => 'Category', 'cp.fk_product' => 'ProductRef');
		}

		// 1 Suppliers
		if (isModEnabled("supplier_order") || isModEnabled("supplier_invoice")) {
			$r++;
			$this->import_code[$r] = $this->rights_class.'_1_'.Categorie::$MAP_ID_TO_CODE[1];
			$this->import_label[$r] = "CatSupLinks"; // Translation key
			$this->import_icon[$r] = $this->picto;
			$this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
			$this->import_tables_array[$r] = array('cs'=>MAIN_DB_PREFIX.'categorie_fournisseur');
			$this->import_fields_array[$r] = array('cs.fk_categorie'=>"Category*", 'cs.fk_soc'=>"Supplier*");
			$this->import_regex_array[$r] = array(
				'cs.fk_categorie'=>'rowid@'.MAIN_DB_PREFIX.'categorie:type=1',
				'cs.fk_soc'=>'rowid@'.MAIN_DB_PREFIX.'societe:fournisseur>0'
			);

			$this->import_convertvalue_array[$r] = array(
					'cs.fk_categorie'=>array('rule'=>'fetchidfromref', 'classfile'=>'/categories/class/categorie.class.php', 'class'=>'Categorie', 'method'=>'fetch', 'element'=>'category'),
					'cs.fk_soc'=>array('rule'=>'fetchidfromref', 'classfile'=>'/societe/class/societe.class.php', 'class'=>'Societe', 'method'=>'fetch', 'element'=>'ThirdParty')
			);
			$this->import_examplevalues_array[$r] = array('cs.fk_categorie'=>"rowid or label", 'cs.fk_soc'=>"rowid or name");
		}

		// 2 Customers
		if (isModEnabled("societe")) {
			$r++;
			$this->import_code[$r] = $this->rights_class.'_2_'.Categorie::$MAP_ID_TO_CODE[2];
			$this->import_label[$r] = "CatCusLinks"; // Translation key
			$this->import_icon[$r] = $this->picto;
			$this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
			$this->import_tables_array[$r] = array('cs'=>MAIN_DB_PREFIX.'categorie_societe');
			$this->import_fields_array[$r] = array('cs.fk_categorie'=>"Category*", 'cs.fk_soc'=>"Customer*");
			$this->import_regex_array[$r] = array(
				'cs.fk_categorie'=>'rowid@'.MAIN_DB_PREFIX.'categorie:type=2',
				'cs.fk_soc'=>'rowid@'.MAIN_DB_PREFIX.'societe:client>0'
			);

			$this->import_convertvalue_array[$r] = array(
					'cs.fk_categorie'=>array('rule'=>'fetchidfromref', 'classfile'=>'/categories/class/categorie.class.php', 'class'=>'Categorie', 'method'=>'fetch', 'element'=>'category'),
					'cs.fk_soc'=>array('rule'=>'fetchidfromref', 'classfile'=>'/societe/class/societe.class.php', 'class'=>'Societe', 'method'=>'fetch', 'element'=>'ThirdParty')
			);
			$this->import_examplevalues_array[$r] = array('cs.fk_categorie'=>"rowid or label", 'cs.fk_soc'=>"rowid or name");
		}

		// 3 Members
		if (isModEnabled('adherent')) {
			$r++;
			$this->import_code[$r] = $this->rights_class.'_3_'.Categorie::$MAP_ID_TO_CODE[3];
			$this->import_label[$r] = "CatMembersLinks"; // Translation key
			$this->import_icon[$r] = $this->picto;
			$this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
			$this->import_tables_array[$r] = array('cm'=>MAIN_DB_PREFIX.'categorie_contact');
			$this->import_fields_array[$r] = array('cm.fk_categorie'=>"Category*", 'cm.fk_member'=>"Member*");
			$this->import_regex_array[$r] = array('cm.fk_categorie'=>'rowid@'.MAIN_DB_PREFIX.'categorie:type=3');

			$this->import_convertvalue_array[$r] = array(
				'cs.fk_categorie'=>array('rule'=>'fetchidfromref', 'classfile'=>'/categories/class/categorie.class.php', 'class'=>'Categorie', 'method'=>'fetch', 'element'=>'category'),
				'cs.fk_member'=>array('rule'=>'fetchidfromref', 'classfile'=>'/adherents/class/adherent.class.php', 'class'=>'Adherent', 'method'=>'fetch', 'element'=>'Member')
			);
			$this->import_examplevalues_array[$r] = array('cs.fk_categorie'=>"rowid or label", 'cs.fk_member'=>"rowid or ref");
		}

		// 4 Contacts/Addresses
		if (isModEnabled("societe")) {
			$r++;
			$this->import_code[$r] = $this->rights_class.'_4_'.Categorie::$MAP_ID_TO_CODE[4];
			$this->import_label[$r] = "CatContactsLinks"; // Translation key
			$this->import_icon[$r] = $this->picto;
			$this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
			$this->import_tables_array[$r] = array('cc'=>MAIN_DB_PREFIX.'categorie_contact');
			$this->import_fields_array[$r] = array('cc.fk_categorie'=>"Category*", 'cc.fk_socpeople'=>"IdContact*");
			$this->import_regex_array[$r] = array(
				'cc.fk_categorie'=>'rowid@'.MAIN_DB_PREFIX.'categorie:type=4'
				//'cc.fk_socpeople'=>'rowid@'.MAIN_DB_PREFIX.'socpeople'
			);

			$this->import_convertvalue_array[$r] = array(
				'cc.fk_categorie'=>array('rule'=>'fetchidfromref', 'classfile'=>'/categories/class/categorie.class.php', 'class'=>'Categorie', 'method'=>'fetch', 'element'=>'category'),
				//'cc.fk_socpeople'=>array('rule'=>'fetchidfromref','classfile'=>'/contact/class/contact.class.php','class'=>'Contact','method'=>'fetch','element'=>'Contact')
			);
			$this->import_examplevalues_array[$r] = array('cc.fk_categorie'=>"rowid or label", 'cc.fk_socpeople'=>"rowid");
		}

		// 5 Bank accounts, TODO ?

		// 6 Projects
		if (isModEnabled('project')) {
			$r++;
			$this->import_code[$r] = $this->rights_class.'_6_'.Categorie::$MAP_ID_TO_CODE[6];
			$this->import_label[$r] = "CatProjectsLinks"; // Translation key
			$this->import_icon[$r] = $this->picto;
			$this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
			$this->import_tables_array[$r] = array('cp'=>MAIN_DB_PREFIX.'categorie_project');
			$this->import_fields_array[$r] = array('cp.fk_categorie'=>"Category*", 'cp.fk_project'=>"Project*");
			$this->import_regex_array[$r] = array('cp.fk_categorie'=>'rowid@'.MAIN_DB_PREFIX.'categorie:type=6');

			$this->import_convertvalue_array[$r] = array(
				'cs.fk_categorie'=>array('rule'=>'fetchidfromref', 'classfile'=>'/categories/class/categorie.class.php', 'class'=>'Categorie', 'method'=>'fetch', 'element'=>'category'),
				'cs.fk_project'=>array('rule'=>'fetchidfromref', 'classfile'=>'/projet/class/project.class.php', 'class'=>'Project', 'method'=>'fetch', 'element'=>'Project')
			);
			$this->import_examplevalues_array[$r] = array('cp.fk_categorie'=>"rowid or label", 'cp.fk_project'=>"rowid or ref");
		}

		// 7 Users
		if (isModEnabled('user')) {
			$r++;
			$this->import_code[$r] = $this->rights_class.'_7_'.Categorie::$MAP_ID_TO_CODE[7];
			$this->import_label[$r] = "CatUsersLinks"; // Translation key
			$this->import_icon[$r] = $this->picto;
			$this->import_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
			$this->import_tables_array[$r] = array('cu'=>MAIN_DB_PREFIX.'categorie_user');
			$this->import_fields_array[$r] = array('cu.fk_categorie'=>"Category*", 'cu.fk_user'=>"User*");
			$this->import_regex_array[$r] = array('cu.fk_categorie'=>'rowid@'.MAIN_DB_PREFIX.'categorie:type=7');

			$this->import_convertvalue_array[$r] = array(
				'cu.fk_categorie'=>array('rule'=>'fetchidfromref', 'classfile'=>'/categories/class/categorie.class.php', 'class'=>'Categorie', 'method'=>'fetch', 'element'=>'category'),
				'cu.fk_user'=>array('rule'=>'fetchidfromref', 'classfile'=>'/user/class/user.class.php', 'class'=>'User', 'method'=>'fetch', 'element'=>'User')
			);
			$this->import_examplevalues_array[$r] = array('cu.fk_categorie'=>"rowid or label", 'cu.fk_user'=>"rowid or login");
		}

		// 8 Bank Lines, TODO ?

		// 9 Warehouses, TODO ?

		// 10 Agenda Events, TODO ?

		// 11 Website Pages, TODO ?
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
		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
	}
}
