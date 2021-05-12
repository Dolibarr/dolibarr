<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * Copyright (C) 2013-2014 Cedric GROSS         <c.gross@kreiz-it.fr>
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
 *	\defgroup   productbatch     Module batch number management
 *	\brief      Management module for batch number, eat-by and sell-by date for product
 *  \file       htdocs/core/modules/modProductBatch.class.php
 *  \ingroup    productbatch
 *  \brief      Description and activation file for module productbatch
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module productdluo
 */
class modProductBatch extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
<<<<<<< HEAD
	function __construct($db)
=======
	public function __construct($db)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
        global $langs,$conf;

        $this->db = $db;
		$this->numero = 39000;

		$this->family = "products";
<<<<<<< HEAD
		$this->module_position = 45;

		$this->name = preg_replace('/^mod/i','',get_class($this));
=======
		$this->module_position = '45';

		$this->name = preg_replace('/^mod/i', '', get_class($this));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$this->description = "Batch number, eat-by and sell-by date management module";

		$this->rights_class = 'productbatch';
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where dluo is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		$this->picto='stock';

		$this->module_parts = array();

		// Data directories to create when module is enabled.
		$this->dirs = array();

		// Config pages. Put here list of php page, stored into productdluo/admin directory, to use to setup module.
		$this->config_page_url = array("product_lot_extrafields.php@product");

		// Dependencies
<<<<<<< HEAD
		$this->depends = array("modProduct","modStock","modExpedition","modFournisseur");		// List of modules id that must be enabled if this module is enabled. modExpedition is required to manage batch exit (by manual stock decrease on shipment), modSupplier to manage batch entry (after supplier order).
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(5,0);					// Minimum version of PHP required by module
=======
		$this->hidden = false;			// A condition to hide module
		$this->depends = array("modProduct","modStock","modExpedition","modFournisseur");		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array();	// List of module ids to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->phpmin = array(5,4);		// Minimum version of PHP required by module
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$this->need_dolibarr_version = array(3,0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("productbatch");

		// Constants
		$this->const = array();

        $this->tabs = array();

        // Dictionaries
	    if (! isset($conf->productbatch->enabled))
        {
        	$conf->productbatch=new stdClass();
        	$conf->productbatch->enabled=0;
        }
		$this->dictionaries=array();

        // Boxes
        $this->boxes = array();			// List of boxes

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;


		// Menus
		//-------
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.


		// Exports
		$r=0;
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
<<<<<<< HEAD
	function init($options='')
=======
	public function init($options = '')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
	    global $db,$conf;

		$sql = array();

		if (! empty($conf->cashdesk->enabled)) {
    		if (empty($conf->global->CASHDESK_NO_DECREASE_STOCK)) {
    		    include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
<<<<<<< HEAD
    		    $res = dolibarr_set_const($db,"CASHDESK_NO_DECREASE_STOCK",1,'chaine',0,'',$conf->entity);
=======
    		    $res = dolibarr_set_const($db, "CASHDESK_NO_DECREASE_STOCK", 1, 'chaine', 0, '', $conf->entity);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    		}
		}

		return $this->_init($sql, $options);
	}
}
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
