<?php
/* Copyright (C) 2003		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012	Regis Houssin        <regis.houssin@inodbox.com>
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
 * or see http://www.gnu.org/
 */

/**
 * 		\defgroup   tax		Module taxes
 * 		\brief      Module pour inclure des fonctions de saisies des taxes (tva) et charges sociales
 *      \file       htdocs/core/modules/modTax.class.php
 *      \ingroup    tax
 *      \brief      Fichier de description et activation du module Taxe
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Tax
 */
class modTax extends DolibarrModules
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
		$this->numero = 500;

		$this->family = "financial";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Gestion des taxes, charges sociales et dividendes";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto='bill';

		// Data directories to create when module is enabled
		$this->dirs = array("/tax/temp");

		// Config pages
		$this->config_page_url = array("taxes.php");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array();	// List of module ids to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->phpmin = array(5,4);		// Minimum version of PHP required by module
		$this->langfiles = array("compta","bills");

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'tax';
		$r=0;

		$r++;
		$this->rights[$r][0] = 91;
		$this->rights[$r][1] = 'Lire les charges';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'charges';
		$this->rights[$r][5] = 'lire';

		$r++;
		$this->rights[$r][0] = 92;
		$this->rights[$r][1] = 'Creer/modifier les charges';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'charges';
		$this->rights[$r][5] = 'creer';

		$r++;
		$this->rights[$r][0] = 93;
		$this->rights[$r][1] = 'Supprimer les charges';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'charges';
		$this->rights[$r][5] = 'supprimer';

		$r++;
		$this->rights[$r][0] = 94;
		$this->rights[$r][1] = 'Exporter les charges';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'charges';
		$this->rights[$r][5] = 'export';


		// Menus
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.


		// Exports
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Taxes et charges sociales, et leurs reglements';
		$this->export_permission[$r]=array(array("tax","charges","export"));
		$this->export_fields_array[$r]=array('cc.libelle'=>"Type",'c.rowid'=>"IdSocialContribution",'c.libelle'=>"Label",'c.date_ech'=>'DateDue','c.periode'=>'Period','c.amount'=>"AmountExpected","c.paye"=>"Status",'p.rowid'=>'PaymentId','p.datep'=>'DatePayment','p.amount'=>'AmountPayment','p.num_paiement'=>'Numero');
		$this->export_TypeFields_array[$r]=array('cc.libelle'=>"List:c_chargesociales:libelle:id",'c.libelle'=>"Text",'c.date_ech'=>'Date','c.periode'=>'Period','c.amount'=>"Numeric","c.paye"=>"Boolean",'p.datep'=>'Date','p.amount'=>'Numeric','p.num_paiement'=>'Numeric');
		$this->export_entities_array[$r]=array('cc.libelle'=>"tax_type",'c.rowid'=>"tax",'c.libelle'=>'tax','c.date_ech'=>'tax','c.periode'=>'tax','c.amount'=>"tax","c.paye"=>"tax",'p.rowid'=>'payment','p.datep'=>'payment','p.amount'=>'payment','p.num_paiement'=>'payment');

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'c_chargesociales as cc, '.MAIN_DB_PREFIX.'chargesociales as c';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'paiementcharge as p ON p.fk_charge = c.rowid';
		$this->export_sql_end[$r] .=' WHERE c.fk_type = cc.id';
		$this->export_sql_end[$r] .=' AND c.entity IN ('.getEntity('tax').')';

		// Import social contributions
		$r++;
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]="ImportDataset_tax_contrib";	// Translation key
		$this->import_icon[$r]='tax';
		$this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r]=array('t'=>MAIN_DB_PREFIX.'chargesociales');
		$this->import_fields_array[$r]=array('t.libelle'=>"Label*",'t.fk_type'=>"Type",
		    't.amount'=>"Amount*",'t.date_ech'=>"DateDue*",'t.periode'=>"PeriodEndDate*"
		);

		$this->import_convertvalue_array[$r]=array(
		    't.fk_type'=>array('rule'=>'fetchidfromref','classfile'=>'/compta/sociales/class/cchargesociales.class.php','class'=>'Cchargesociales','method'=>'fetch','element'=>'Cchargesociales')
		);
		$this->import_examplevalues_array[$r]=array('t.libelle'=>"Social/fiscal contribution",'t.fk_type'=>"TAXPRO (must be id or code found into dictionary)",
		    't.date_ech'=>"2016-01-01", 't.periode'=>"2016-01-01"
		);

		// Import Taxes
		$r++;
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]="ImportDataset_tax_vat";	// Translation key
		$this->import_icon[$r]='tax';
		$this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r]=array('t'=>MAIN_DB_PREFIX.'tva');
		$this->import_fields_array[$r]=array('t.datep'=>"DatePayment*",'t.datev'=>"DateValue*",'t.label'=>"Label*",'t.fk_typepayment'=>"PaymentMode*",
		    't.amount'=>"Amount*",'t.num_payment'=>'Numero'
		);

		$this->import_convertvalue_array[$r]=array(
		    't.fk_typepayment'=>array('rule'=>'fetchidfromref','classfile'=>'/compta/paiement/class/cpaiement.class.php','class'=>'Cpaiement','method'=>'fetch','element'=>'Cpaiement')
		);
		$this->import_examplevalues_array[$r]=array('t.label'=>"VAT Payment 1st quarter 2016",'t.fk_typepayment'=>"CHQ (must be id or code found into dictionary)",
		    't.datep'=>"2016-04-02", 't.datev'=>"2016-03-31", 't.amount'=>1000, 't.num_payment'=>'123456'
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
	function init($options='')
	{
		global $conf;

		// Nettoyage avant activation
		$this->remove($options);

		$sql = array();

		return $this->_init($sql,$options);
	}
}
