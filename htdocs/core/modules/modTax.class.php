<?php
/* Copyright (C) 2003		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012	Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	Classe de description et activation du module Tax
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
		$this->special = 0;
		$this->picto='bill';

		// Data directories to create when module is enabled
		$this->dirs = array("/tax/temp");

		// Config pages
		$this->config_page_url = array("taxes.php");

		// Dependances
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array("compta","bills");

		// Constantes
		$this->const = array();

		// Boites
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'tax';
		$r=0;

		$r++;
		$this->rights[$r][0] = 91;
		$this->rights[$r][1] = 'Lire les charges';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
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


		// Exports
		//--------
		$r=0;

		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='Taxes et charges sociales, et leurs reglements';
		$this->export_permission[$r]=array(array("tax","charges","export"));
		$this->export_fields_array[$r]=array('cc.libelle'=>"Type",'c.rowid'=>"IdSocialContribution",'c.libelle'=>"Label",'c.date_ech'=>'DateDue','c.periode'=>'Period','c.amount'=>"AmountExpected","c.paye"=>"Status",'p.rowid'=>'PaymentId','p.datep'=>'DatePayment','p.amount'=>'AmountPayment','p.num_paiement'=>'Numero');
		$this->export_TypeFields_array[$r]=array('cc.libelle'=>"List:c_chargesociales:libelle:id",'c.libelle'=>"Text",'c.date_ech'=>'Date','c.periode'=>'Period','c.amount'=>"Number","c.paye"=>"Boolean",'p.datep'=>'Date','p.amount'=>'Number','p.num_paiement'=>'Number');
		$this->export_entities_array[$r]=array('cc.libelle'=>"tax_type",'c.rowid'=>"tax",'c.libelle'=>'tax','c.date_ech'=>'tax','c.periode'=>'tax','c.amount'=>"tax","c.paye"=>"tax",'p.rowid'=>'payment','p.datep'=>'payment','p.amount'=>'payment','p.num_paiement'=>'payment');

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'c_chargesociales as cc, '.MAIN_DB_PREFIX.'chargesociales as c';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'paiementcharge as p ON p.fk_charge = c.rowid';
		$this->export_sql_end[$r] .=' WHERE c.fk_type = cc.id';
		$this->export_sql_end[$r] .=' AND c.entity = '.$conf->entity;
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

    /**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
     */
    function remove($options='')
    {
		$sql = array();

		return $this->_remove($sql,$options);
    }

}
?>
