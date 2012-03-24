<?php
/* Copyright (C) 2010-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**     \defgroup   concatpdf		Module ConcatPdf
 *      \brief      Module for concatpdf server
 *		\version	$Id: modConcatPdf.class.php,v 1.1 2011/05/18 15:51:47 eldy Exp $
 */

/**
 *       \file       htdocs/concatpdf/core/modules/modconcatpdf.class.php
 *       \ingroup    ftp
 *       \brief      Description and activation file for module concatpdf
 */

include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**     \class      modConcatPdf
 *      \brief      Description and activation class for module concatpdf
 */
class modConcatPdf extends DolibarrModules
{

    /**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param		DoliDB		$db		Database handler
     */
	function modConcatPdf($db)
	{
		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id.
		$this->numero = 101400;

		// Family can be 'crm','financial','hr','projects','product','ecm','technic','other'
		// It is used to sort modules in module setup page
		$this->family = "other";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description used if translation string 'ModuleXXXDesc' not found (XXX is id value)
		$this->description = "Concat pdfs found into a directory to generated pdf files (proposals, orders, invoices)";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '3.2';
		// Key used in llx_const table to save module status enabled/disabled (XXX is id value)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 2;
		// Name of png file (without png) used for this module
		$this->picto='bill';

		// Data directories to create when module is enabled
		$this->dirs = array('/concatpdf/invoices','/concatpdf/orders','/concatpdf/proposals','/concatpdf/temp');

		// Config pages. Put here list of php page names stored in admin directory used to setup module
		$this->config_page_url = array('concatpdf.php@concatpdf');

		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
        $this->phpmin = array(4,3);                 // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(3,2,-3);  // Minimum version of Dolibarr required by module
        $this->langfiles = array("concatpdf@concatpdf");

        // Constants
        // Example: $this->const=array(0=>array('MODULE_MY_NEW_CONST1','chaine','myvalue','This is a constant to add',1),
        //                             1=>array('MODULE_MY_NEW_CONST2','chaine','myvalue','This is another constant to add',1) );
        $this->const = array(0=>array('MAIN_MODULE_CONCATPDF_HOOKS','chaine','invoicecard:propalcard:ordercard:pdfgeneration','This module add hooks on invoicecard.propalcard and ordercard context pages and pdf generation',0,'current',1));

		// Boxes
		$this->boxes = array();			// List of boxes
		$r=0;

		// Add here list of php file(s) stored in includes/boxes that contains class to show a box.
		// Example:
        //$this->boxes[$r][1] = "myboxa.php";
    	//$r++;
        //$this->boxes[$r][1] = "myboxb.php";
    	//$r++;

		// Permissions
		$this->rights_class = 'concatpdf';	// Permission key
		$this->rights = array();		// Permission array used by this module


		// Menus
		//------
		$this->menus = array();			// List of menus to add
		$r=0;

		// Top menu
		/*$this->menu[$r]=array('fk_menu'=>0,
							  'type'=>'top',
							  'titre'=>'FTP',
							  'mainmenu'=>'ftp',
							  'url'=>'/ftp/index.php',
							  'langs'=>'ftp',
							  'position'=>100,
							  'perms'=>'$user->rights->ftp->read || $user->rights->ftp->write || $user->rights->ftp->setup',
							  'enabled'=>1,
							  'target'=>'',
							  'user'=>2);			// 0=Menu for internal users, 1=external users, 2=both
		$r++;
		*/
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
    	$sql = array();

    	return $this->_init($sql, $options);
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

    	return $this->_remove($sql, $options);
  	}

}

?>
