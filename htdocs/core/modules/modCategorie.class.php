<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2005-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012-2016 Juanjo Menent		<jmenent@2byte.es>
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
 *      \defgroup   category       Module categories
 *      \brief      Module to manage categories
 *      \file       htdocs/core/modules/modCategorie.class.php
 *      \ingroup    category
 *      \brief      Fichier de description et activation du module Categorie
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


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
	function __construct($db)
	{
	    global $conf;

		$this->db = $db;
		$this->numero = 1780;

		$this->family = "technic";
		$this->module_position = '20';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
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
		$this->langfiles = array("products","companies","categories","members");

		// Constants
		$this->const = array();
		$r=0;
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

		$r=0;

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
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.


		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]='category_'.$r;
		$this->export_label[$r]='CatSupList';
		$this->export_icon[$r]='category';
		$this->export_enabled[$r]='$conf->fournisseur->enabled';
		$this->export_permission[$r]=array(array("categorie","lire"),array("fournisseur","lire"));
		$this->export_fields_array[$r]=array(
			'u.rowid'=>"CategId",'u.label'=>"Label",'u.description'=>"Description",'s.rowid'=>'IdThirdParty','s.nom'=>'Name','s.prefix_comm'=>"Prefix",
			's.client'=>"Customer",'s.datec'=>"DateCreation",'s.tms'=>"DateLastModification",'s.code_client'=>"CustomerCode",'s.address'=>"Address",
			's.zip'=>"Zip",'s.town'=>"Town",'c.label'=>"Country",'c.code'=>"CountryCode",'s.phone'=>"Phone",'s.fax'=>"Fax",'s.url'=>"Url",'s.email'=>"Email",
			's.siret'=>"ProfId1",'s.siren'=>"ProfId2",'s.ape'=>"ProfId3",'s.idprof4'=>"ProfId4",'s.tva_intra'=>"VATIntraShort",'s.capital'=>"Capital",
			's.note_public'=>"NotePublic"
		);
		$this->export_TypeFields_array[$r]=array(
			'u.label'=>"Text",'u.description'=>"Text",'s.rowid'=>'List:societe:nom','s.nom'=>'Text','s.prefix_comm'=>"Text",'s.client'=>"Text",'s.datec'=>"Date",
			's.tms'=>"Date",'s.code_client'=>"Text",'s.address'=>"Text",'s.zip'=>"Text",'s.town'=>"Text",'c.label'=>"List:c_country:label:label",'c.code'=>"Text",
			's.phone'=>"Text",'s.fax'=>"Text",'s.url'=>"Text",'s.email'=>"Text",'s.siret'=>"Text",'s.siren'=>"Text",'s.ape'=>"Text",'s.idprof4'=>"Text",
			's.tva_intra'=>"Text",'s.capital'=>"Numeric",'s.note_public'=>"Text"
		);
		$this->export_entities_array[$r]=array(
			's.rowid'=>'company','s.nom'=>'company','s.prefix_comm'=>"company",'s.client'=>"company",'s.datec'=>"company",'s.tms'=>"company",
			's.code_client'=>"company",'s.address'=>"company",'s.zip'=>"company",'s.town'=>"company",'c.label'=>"company",'c.code'=>"company",
			's.phone'=>"company",'s.fax'=>"company",'s.url'=>"company",'s.email'=>"company",'s.siret'=>"company",'s.siren'=>"company",'s.ape'=>"company",
			's.idprof4'=>"company",'s.tva_intra'=>"company",'s.capital'=>"company",'s.note_public'=>"company"
		);	// We define here only fields that use another picto
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'categorie as u, ';
		$this->export_sql_end[$r] .= MAIN_DB_PREFIX.'categorie_fournisseur as cf, ';
		$this->export_sql_end[$r] .= MAIN_DB_PREFIX.'societe as s LEFT JOIN '.MAIN_DB_PREFIX.'c_typent as t ON s.fk_typent = t.id LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON s.fk_pays = c.rowid LEFT JOIN '.MAIN_DB_PREFIX.'c_effectif as ce ON s.fk_effectif = ce.id LEFT JOIN '.MAIN_DB_PREFIX.'c_forme_juridique as cfj ON s.fk_forme_juridique = cfj.code';
		$this->export_sql_end[$r] .=' WHERE u.rowid = cf.fk_categorie AND cf.fk_soc = s.rowid';
		$this->export_sql_end[$r] .=' AND u.entity IN ('.getEntity('category').')';
		$this->export_sql_end[$r] .=' AND u.type = 1';	// Supplier categories

		$r++;
		$this->export_code[$r]='category_'.$r;
		$this->export_label[$r]='CatCusList';
		$this->export_icon[$r]='category';
        $this->export_enabled[$r]='$conf->societe->enabled';
		$this->export_permission[$r]=array(array("categorie","lire"),array("societe","lire"));
		$this->export_fields_array[$r]=array(
			'u.rowid'=>"CategId",'u.label'=>"Label",'u.description'=>"Description",'s.rowid'=>'IdThirdParty','s.nom'=>'Name','s.prefix_comm'=>"Prefix",
			's.client'=>"Customer",'s.datec'=>"DateCreation",'s.tms'=>"DateLastModification",'s.code_client'=>"CustomerCode",'s.address'=>"Address",
			's.zip'=>"Zip",'s.town'=>"Town",'c.label'=>"Country",'c.code'=>"CountryCode",'s.phone'=>"Phone",'s.fax'=>"Fax",'s.url'=>"Url",'s.email'=>"Email",
			's.siret'=>"ProfId1",'s.siren'=>"ProfId2",'s.ape'=>"ProfId3",'s.idprof4'=>"ProfId4",'s.tva_intra'=>"VATIntraShort",'s.capital'=>"Capital",
			's.note_public'=>"NotePublic",'s.fk_prospectlevel'=>'ProspectLevel','s.fk_stcomm'=>'ProspectStatus'
		);
		$this->export_TypeFields_array[$r]=array(
			'u.label'=>"Text",'u.description'=>"Text",'s.rowid'=>'List:societe:nom','s.nom'=>'Text','s.prefix_comm'=>"Text",'s.client'=>"Text",
			's.datec'=>"Date",'s.tms'=>"Date",'s.code_client'=>"Text",'s.address'=>"Text",'s.zip'=>"Text",'s.town'=>"Text",'c.label'=>"List:c_country:label:label",
			'c.code'=>"Text",'s.phone'=>"Text",'s.fax'=>"Text",'s.url'=>"Text",'s.email'=>"Text",'s.siret'=>"Text",'s.siren'=>"Text",'s.ape'=>"Text",
			's.idprof4'=>"Text",'s.tva_intra'=>"Text",'s.capital'=>"Numeric",'s.note_public'=>"Text",'s.fk_prospectlevel'=>'List:c_prospectlevel:label:code',
			's.fk_stcomm'=>'List:c_stcomm:libelle:code'
		);
		$this->export_entities_array[$r]=array(
			's.rowid'=>'company','s.nom'=>'company','s.prefix_comm'=>"company",'s.client'=>"company",'s.datec'=>"company",'s.tms'=>"company",
			's.code_client'=>"company",'s.address'=>"company",'s.zip'=>"company",'s.town'=>"company",'c.label'=>"company",'c.code'=>"company",
			's.phone'=>"company",'s.fax'=>"company",'s.url'=>"company",'s.email'=>"company",'s.siret'=>"company",'s.siren'=>"company",'s.ape'=>"company",
			's.idprof4'=>"company",'s.tva_intra'=>"company",'s.capital'=>"company",'s.note_public'=>"company",'s.fk_prospectlevel'=>'company',
			's.fk_stcomm'=>'company'
		);	// We define here only fields that use another picto
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'categorie as u, ';
		$this->export_sql_end[$r] .= MAIN_DB_PREFIX.'categorie_societe as cf, ';
		$this->export_sql_end[$r] .= MAIN_DB_PREFIX.'societe as s LEFT JOIN '.MAIN_DB_PREFIX.'c_typent as t ON s.fk_typent = t.id LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON s.fk_pays = c.rowid LEFT JOIN '.MAIN_DB_PREFIX.'c_effectif as ce ON s.fk_effectif = ce.id LEFT JOIN '.MAIN_DB_PREFIX.'c_forme_juridique as cfj ON s.fk_forme_juridique = cfj.code LEFT JOIN '.MAIN_DB_PREFIX.'societe_extrafields as extra ON s.rowid = extra.fk_object ';
		$this->export_sql_end[$r] .=' WHERE u.rowid = cf.fk_categorie AND cf.fk_soc = s.rowid';
		$this->export_sql_end[$r] .=' AND u.entity IN ('.getEntity('category').')';
		$this->export_sql_end[$r] .=' AND u.type = 2';	// Customer/Prospect categories

        // Add extra fields
        $sql="SELECT name, label, type, param FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'societe'";
        $resql=$this->db->query($sql);
        if ($resql)    // This can fail when class is used on old database (during migration for example)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $fieldname='extra.'.$obj->name;
                $fieldlabel=ucfirst($obj->label);
                $typeFilter="Text";
                switch($obj->type)
                {
                    case 'int':
                    case 'double':
                    case 'price':
                        $typeFilter="Numeric";
                        break;
                    case 'date':
                    case 'datetime':
                        $typeFilter="Date";
                        break;
                    case 'boolean':
                        $typeFilter="Boolean";
                        break;
                    case 'sellist':
                        $typeFilter="List:".$obj->param;
                        break;
                    case 'select':
                        $typeFilter="Select:".$obj->param;
                        break;
                }
                $this->export_fields_array[$r][$fieldname]=$fieldlabel;
                $this->export_TypeFields_array[$r][$fieldname]=$typeFilter;
                $this->export_entities_array[$r][$fieldname]='company';
            }
        }
        // End add axtra fields





		$r++;
		$this->export_code[$r]='category_'.$r;
		$this->export_label[$r]='CatProdList';
		$this->export_icon[$r]='category';
        $this->export_enabled[$r]='$conf->produit->enabled';
		$this->export_permission[$r]=array(array("categorie","lire"),array("produit","lire"));
		$this->export_fields_array[$r]=array('u.rowid'=>"CategId",'u.label'=>"Label",'u.description'=>"Description",'p.rowid'=>'ProductId','p.ref'=>'Ref');
		$this->export_TypeFields_array[$r]=array('u.label'=>"Text",'u.description'=>"Text",'p.ref'=>'Text');
		$this->export_entities_array[$r]=array('p.rowid'=>'product','p.ref'=>'product');	// We define here only fields that use another picto
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'categorie as u, '.MAIN_DB_PREFIX.'categorie_product as cp, '.MAIN_DB_PREFIX.'product as p';
		$this->export_sql_end[$r] .=' WHERE u.rowid = cp.fk_categorie AND cp.fk_product = p.rowid';
		$this->export_sql_end[$r] .=' AND u.entity IN ('.getEntity('category').')';
		$this->export_sql_end[$r] .=' AND u.type = 0';	// Supplier categories

		$r++;
		$this->export_code[$r]='category_'.$r;
		$this->export_label[$r]='CatMemberList';
		$this->export_icon[$r]='category';
        $this->export_enabled[$r]='$conf->adherent->enabled';
		$this->export_permission[$r]=array(array("categorie","lire"),array("adherent","lire"));
		$this->export_fields_array[$r]=array('u.rowid'=>"CategId",'u.label'=>"Label",'u.description'=>"Description",'p.rowid'=>'MemberId','p.lastname'=>'LastName','p.firstname'=>'Firstname');
		$this->export_TypeFields_array[$r]=array('u.label'=>"Text",'u.description'=>"Text",'p.lastname'=>'Text','p.firstname'=>'Text');
		$this->export_entities_array[$r]=array('p.rowid'=>'member','p.lastname'=>'member','p.firstname'=>'member');	// We define here only fields that use another picto
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'categorie as u, '.MAIN_DB_PREFIX.'categorie_member as cp, '.MAIN_DB_PREFIX.'adherent as p';
		$this->export_sql_end[$r] .=' WHERE u.rowid = cp.fk_categorie AND cp.fk_member = p.rowid';
		$this->export_sql_end[$r] .=' AND u.entity IN ('.getEntity('category').')';
		$this->export_sql_end[$r] .=' AND u.type = 3';	// Member categories

		$r++;
		$this->export_code[$r]='category_'.$r;
		$this->export_label[$r]='CatContactList';
		$this->export_icon[$r]='category';
		$this->export_enabled[$r]='$conf->societe->enabled';
		$this->export_permission[$r]=array(array("categorie", "lire"), array ("societe", "lire"));
		$this->export_fields_array[$r]=array (
			'u.rowid' => "CategId",
			'u.label' => "Label",
			'u.description' => "Description",
			'p.rowid' => 'ContactId',
			'p.civility' => 'UserTitle',
			'p.lastname' => 'LastName',
			'p.firstname' => 'Firstname',
			'p.address' => 'Address',
			'p.zip' => 'Zip',
			'p.town' => 'Town',
			'country.code' => 'CountryCode',
			'country.label' => 'Country',
			'p.birthday' => 'DateToBirth',
			'p.poste' => 'PostOrFunction',
			'p.phone' => 'Phone',
			'p.phone_perso' => 'PhonePerso',
			'p.phone_mobile' => 'PhoneMobile',
			'p.fax' => 'Fax',
			'p.email' => 'Email',
			'p.note_private' => 'NotePrivate',
			'p.note_public' => 'NotePublic',
            'p.statut' => 'Status',
			's.nom'=>"Name",
			's.client'=>"Customer",
			's.fournisseur'=>"Supplier",
			's.status'=>"Status",
			's.address'=>"Address",
			's.zip'=>"Zip",
			's.town'=>"Town",
			's.phone'=>"Phone",
			's.fax'=>"Fax",
			's.url'=>"Url",
			's.email'=>"Email"
		);
		$this->export_TypeFields_array[$r] = array (
			'u.label' => "Text",
			'u.description' => "Text",
			'p.lastname' => 'Text',
			'p.firstname' => 'Text',
            'p.statut'=>"Numeric",
			's.nom'=>"Text",
			's.status'=>"Text",
			's.address'=>"Text",
			's.zip'=>"Text",
			's.town'=>"Text",
			's.phone'=>"Text",
			's.fax'=>"Text",
			's.url'=>"Text",
			's.email'=>"Text"
		);
		$this->export_entities_array[$r] = array (
			'u.rowid' => "category",
			'u.label' => "category",
			'u.description' => "category",
			'p.rowid' => 'contact',
			'p.civility' => 'contact',
			'p.lastname' => 'contact',
			'p.firstname' => 'contact',
			'p.address' => 'contact',
			'p.zip' => 'contact',
			'p.town' => 'contact',
			'country.code' => 'contact',
			'country.label' => 'contact',
			'p.birthday' => 'contact',
			'p.poste' => 'contact',
			'p.phone' => 'contact',
			'p.phone_perso' => 'contact',
			'p.phone_mobile' => 'contact',
			'p.fax' => 'contact',
			'p.email' => 'contact',
			'p.note_private' => 'contact',
			'p.note_public' => 'contact',
            'p.statut' => 'contact',
			's.nom'=>"company",
			's.client'=>"company",
			's.fournisseur'=>"company",
			's.status'=>"company",
			's.address'=>"company",
			's.zip'=>"company",
			's.town'=>"company",
			's.phone'=>"company",
			's.fax'=>"company",
			's.url'=>"company",
			's.email'=>"company"
		); // We define here only fields that use another picto

        // Add extra fields
        $sql="SELECT name, label, type, param FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'socpeople'";
        $resql=$this->db->query($sql);
        if ($resql)    // This can fail when class is used on old database (during migration for example)
        {
        	while ($obj=$this->db->fetch_object($resql))
        	{
        		$fieldname='extra.'.$obj->name;
        		$fieldlabel=ucfirst($obj->label);
        		$typeFilter="Text";
        		switch($obj->type)
        		{
        			case 'int':
        			case 'double':
        			case 'price':
        				$typeFilter="Numeric";
        				break;
        			case 'date':
        			case 'datetime':
        				$typeFilter="Date";
        				break;
        			case 'boolean':
        				$typeFilter="Boolean";
        				break;
        			case 'sellist':
        				$typeFilter="List:".$obj->param;
        				break;
					case 'select':
						$typeFilter="Select:".$obj->param;
						break;
        		}
        		$this->export_fields_array[$r][$fieldname]=$fieldlabel;
        		$this->export_TypeFields_array[$r][$fieldname]=$typeFilter;
        		$this->export_entities_array[$r][$fieldname]='contact';
        	}
        }
        // End add axtra fields

		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM ' . MAIN_DB_PREFIX . 'categorie as u, '.MAIN_DB_PREFIX . 'categorie_contact as cp, '.MAIN_DB_PREFIX . 'socpeople as p';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_country as country ON p.fk_pays = country.rowid';
		$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as s ON s.rowid = p.fk_soc';
        $this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople_extrafields as extra ON extra.fk_object = p.rowid';
		$this->export_sql_end[$r] .= ' WHERE u.rowid = cp.fk_categorie AND cp.fk_socpeople = p.rowid AND u.entity IN ('.getEntity('category').')';
		$this->export_sql_end[$r] .= ' AND u.type = 4'; // contact categories

		// Imports
		//--------

		$r=0;

		$r++;
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]="CatList";	// Translation key
		$this->import_icon[$r]=$this->picto;
		$this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r]=array('ca'=>MAIN_DB_PREFIX.'categorie');
		$this->import_fields_array[$r]=array('ca.label'=>"Label*",'ca.type'=>"Type*",'ca.description'=>"Description");

		$this->import_regex_array[$r]=array('ca.type'=>'^[0|1|2|3]');
		$typeexample="";
		if ($conf->product->enabled)     { $typeexample.=($typeexample?"/":"")."0=Product"; }
		if ($conf->fournisseur->enabled) { $typeexample.=($typeexample?"/":"")."1=Supplier"; }
		if ($conf->societe->enabled)     { $typeexample.=($typeexample?"/":"")."2=Customer-Prospect"; }
		if ($conf->adherent->enabled)    { $typeexample.=($typeexample?"/":"")."3=Member"; }
		$this->import_examplevalues_array[$r]=array('ca.label'=>"Supplier Category",'ca.type'=>$typeexample,'ca.description'=>"Imported category");

		if (! empty($conf->product->enabled))
		{
			//Products
			$r++;
			$this->import_code[$r]=$this->rights_class.'_'.$r;
			$this->import_label[$r]="CatProdLinks";	// Translation key
			$this->import_icon[$r]=$this->picto;
			$this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
			$this->import_tables_array[$r]=array('cp'=>MAIN_DB_PREFIX.'categorie_product');
			$this->import_fields_array[$r]=array('cp.fk_categorie'=>"Category*",'cp.fk_product'=>"Product*"
			);

			$this->import_convertvalue_array[$r]=array(
					'cp.fk_categorie'=>array('rule'=>'fetchidfromref','classfile'=>'/categories/class/categorie.class.php','class'=>'Categorie','method'=>'fetch','element'=>'category'),
					'cp.fk_product'=>array('rule'=>'fetchidfromref','classfile'=>'/product/class/product.class.php','class'=>'Product','method'=>'fetch','element'=>'product')
			);
			$this->import_examplevalues_array[$r]=array('cp.fk_categorie'=>"Imported category",'cp.fk_product'=>"PREF123456");
		}

		if (! empty($conf->societe->enabled))
		{
			//Customers
			$r++;
			$this->import_code[$r]=$this->rights_class.'_'.$r;
			$this->import_label[$r]="CatCusLinks";	// Translation key
			$this->import_icon[$r]=$this->picto;
			$this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
			$this->import_tables_array[$r]=array('cs'=>MAIN_DB_PREFIX.'categorie_societe');
			$this->import_fields_array[$r]=array('cs.fk_categorie'=>"Category*",'cs.fk_soc'=>"ThirdParty*"
			);

			$this->import_convertvalue_array[$r]=array(
					'cs.fk_categorie'=>array('rule'=>'fetchidfromref','classfile'=>'/categories/class/categorie.class.php','class'=>'Categorie','method'=>'fetch','element'=>'category'),
					'cs.fk_soc'=>array('rule'=>'fetchidfromref','classfile'=>'/societe/class/societe.class.php','class'=>'Societe','method'=>'fetch','element'=>'ThirdParty')
			);
			$this->import_examplevalues_array[$r]=array('cs.fk_categorie'=>"Imported category",'cs.fk_soc'=>"MyBigCompany");
		}

		if (! empty($conf->fournisseur->enabled))
		{
			// Suppliers
			$r++;
			$this->import_code[$r]=$this->rights_class.'_'.$r;
			$this->import_label[$r]="CatSupLinks";	// Translation key
			$this->import_icon[$r]=$this->picto;
			$this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
			$this->import_tables_array[$r]=array('cs'=>MAIN_DB_PREFIX.'categorie_fournisseur');
			$this->import_fields_array[$r]=array('cs.fk_categorie'=>"Category*",'cs.fk_soc'=>"Supplier*"
			);

			$this->import_convertvalue_array[$r]=array(
					'cs.fk_categorie'=>array('rule'=>'fetchidfromref','classfile'=>'/categories/class/categorie.class.php','class'=>'Categorie','method'=>'fetch','element'=>'category'),
					'cs.fk_soc'=>array('rule'=>'fetchidfromref','classfile'=>'/societe/class/societe.class.php','class'=>'Societe','method'=>'fetch','element'=>'ThirdParty')
			);
			$this->import_examplevalues_array[$r]=array('cs.fk_categorie'=>"Imported category",'cs.fk_soc'=>"MyBigCompany");
		}
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
		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql,$options);
	}
}
