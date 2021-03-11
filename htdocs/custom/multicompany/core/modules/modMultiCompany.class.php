<?php
/* Copyright (C) 2009-2020 Regis Houssin  <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Herve Prot     <herve.prot@symeos.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *      \defgroup   multicompany     Module multicompany
 *      \brief      Descriptor file for module multicompany
 *      \file       htdocs/core/modules/modMultiCompany.class.php
 *      \ingroup    multicompany
 *      \brief      Description and activation file for module MultiCompany
 */
include_once DOL_DOCUMENT_ROOT . '/core/modules/DolibarrModules.class.php';


/**
 *      \class      modMultiCompany
 *      \brief      Description and activation class for module MultiCompany
 */
class modMultiCompany extends DolibarrModules
{
	/**
	 *  Constructor.
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		global $langs;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 5000;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'multicompany';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "base";
		// Gives the possibility to the module, to provide his own family info and position of this family.
		$this->familyinfo = array(
			'core' => array(
				'position' => '001',
				'label' => $langs->trans("iNodbox")
			)
		);
		// Module position in the family
		$this->module_position = 1;
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Multi-Company Management";
		//$this->descriptionlong = "A very lon description. Can be a full HTML content";
		$this->editor_name = 'Régis Houssin';
		$this->editor_url = 'https://www.inodbox.com';
		// Can be enabled / disabled only in the main company with superadmin account
		$this->core_enabled = 1;
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '12.0.1';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of png file (without png) used for this module.
		// Png file must be in theme/yourtheme/img directory under name object_pictovalue.png.
		$this->picto='multicompany@multicompany';

		// Data directories to create when module is enabled.
		$this->dirs = array();

		// Config pages. Put here list of php page names stored in admmin directory used to setup module.
		$this->config_page_url = array("multicompany.php@multicompany");

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			'login' => array(
				'data' => 1,
				'entity' => '0'
			),
			'triggers' => array(
				'data' => 1,
				'entity' => '0'
			),
			'hooks' => array(
				'data' => array(
					'login',
					'main',
					'mainloginpage',
					'cashdeskloginpage',
					'passwordforgottenpage',
					'toprightmenu',
					'adminmodules',
					'admincompany',
					'commonobject',
					'thirdpartycard',
					'thirdpartylist',
					'customerlist',
					'prospectlist',
					'supplierlist',
					'usercard',
					'userperms',
					'groupcard',
					'groupperms',
					'userlist',
					'userhome',
					'userdao',
					'contactlist',
					'contactprospectlist',
					'contactcustomerlist',
					'contactsupplierlist',
					'contactotherlist',
					'productcard',
					'pricesuppliercard',
					'propalcard',
					'propallist',
					'ordercard',
					'orderlist',
					'invoicecard',
					'invoicelist',
				    'warehousecard',
				    'stocklist'
				),
				'entity' => '0'
			),
			'css' => array(
				'data' => '/multicompany/css/multicompany.css.php',
				'entity' => '0'
			)
		);

		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(5,6);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(12,0,0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("multicompany@multicompany");

		// Constants
		// List of particular constants to add when module is enabled
		$this->const=array(
			1 => array('MULTICOMPANY_MAIN_VERSION', 'chaine', $this->version, '', 0, 'multicompany', 1),
			2 => array('MULTICOMPANY_EXTERNAL_MODULES_SHARING', 'chaine', '', '', 0, 'multicompany', 0),
			3 => array('MULTICOMPANY_NO_TOP_MENU_ENTITY_LABEL', 'chaine', 1, '', 0, 'multicompany', 0)
		);

		// Boxes
		$this->boxes = array();			// List of boxes
		$r=0;

		// Permissions
		$this->rights = array();
		$r=0;

		$r++;
		$this->rights[$r][0] = 5001;
		$this->rights[$r][1] = 'Read entities (For superadmin users)';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'read';

		$r++;
		$this->rights[$r][0] = 5002;
		$this->rights[$r][1] = 'Create/modify entities (For superadmin users)';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'write';

		$r++;
		$this->rights[$r][0] = 5003;
		$this->rights[$r][1] = 'Delete entities (For superadmin users)';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'delete';

		// Thirdparty sharing (501x)

		$r++;
		$this->rights[$r][0] = 5011;
		$this->rights[$r][1] = 'Read shared third parties';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'thirdparty';
		$this->rights[$r][5] = 'read';

		$r++;
		$this->rights[$r][0] = 5012;
		$this->rights[$r][1] = 'Create/modify shared third parties';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'thirdparty';
		$this->rights[$r][5] = 'write';

		$r++;
		$this->rights[$r][0] = 5013;
		$this->rights[$r][1] = 'Delete shared third parties';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'thirdparty';
		$this->rights[$r][5] = 'delete';

		// Contact sharing (502x)

		$r++;
		$this->rights[$r][0] = 5021;
		$this->rights[$r][1] = 'Read shared contacts';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'contact';
		$this->rights[$r][5] = 'read';

		$r++;
		$this->rights[$r][0] = 5022;
		$this->rights[$r][1] = 'Create/modify shared contacts';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'contact';
		$this->rights[$r][5] = 'write';

		$r++;
		$this->rights[$r][0] = 5023;
		$this->rights[$r][1] = 'Delete shared contacts';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'contact';
		$this->rights[$r][5] = 'delete';

		// Product/service sharing (503x)

		$r++;
		$this->rights[$r][0] = 5031;
		$this->rights[$r][1] = 'Read shared products/services';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'product';
		$this->rights[$r][5] = 'read';

		$r++;
		$this->rights[$r][0] = 5032;
		$this->rights[$r][1] = 'Create/modify shared products/services';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'product';
		$this->rights[$r][5] = 'write';

		$r++;
		$this->rights[$r][0] = 5033;
		$this->rights[$r][1] = 'Delete shared products/services';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'product';
		$this->rights[$r][5] = 'delete';

		// Proposal sharing (504x)

		$r++;
		$this->rights[$r][0] = 5041;
		$this->rights[$r][1] = 'Read shared customer proposals';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'propal';
		$this->rights[$r][5] = 'read';

		$r++;
		$this->rights[$r][0] = 5042;
		$this->rights[$r][1] = 'Create/modify shared customer proposals';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'propal';
		$this->rights[$r][5] = 'write';

		$r++;
		$this->rights[$r][0] = 5043;
		$this->rights[$r][1] = 'Validate shared customer proposals';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'propal_advance';
		$this->rights[$r][5] = 'validate';

		$r++;
		$this->rights[$r][0] = 5044;
		$this->rights[$r][1] = 'Send shared customer proposals';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'propal_advance';
		$this->rights[$r][5] = 'send';

		$r++;
		$this->rights[$r][0] = 5045;
		$this->rights[$r][1] = 'Close shared customer proposals';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'propal';
		$this->rights[$r][5] = 'close';

		$r++;
		$this->rights[$r][0] = 5046;
		$this->rights[$r][1] = 'Delete shared customer proposals';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'propal';
		$this->rights[$r][5] = 'delete';

		// Order sharing (505x)

		$r++;
		$this->rights[$r][0] = 5051;
		$this->rights[$r][1] = 'Read shared customer orders';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'order';
		$this->rights[$r][5] = 'read';

		$r++;
		$this->rights[$r][0] = 5052;
		$this->rights[$r][1] = 'Close shared customer orders';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'order';
		$this->rights[$r][5] = 'write';

		$r++;
		$this->rights[$r][0] = 5054;
		$this->rights[$r][1] = 'Validate shared customer orders';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'order_advance';
		$this->rights[$r][5] = 'validate';

		$r++;
		$this->rights[$r][0] = 5056;
		$this->rights[$r][1] = 'Send shared customer orders';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'order_advance';
		$this->rights[$r][5] = 'send';

		$r++;
		$this->rights[$r][0] = 5057;
		$this->rights[$r][1] = 'Close shared customer orders';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'order';
		$this->rights[$r][5] = 'close';

		$r++;
		$this->rights[$r][0] = 5058;
		$this->rights[$r][1] = 'Cancel shared customer orders';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'order_advance';
		$this->rights[$r][5] = 'cancel';

		$r++;
		$this->rights[$r][0] = 5059;
		$this->rights[$r][1] = 'Delete shared customer orders';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'order';
		$this->rights[$r][5] = 'delete';

		// Invoice sharing (506x)

		$r++;
		$this->rights[$r][0] = 5061;
		$this->rights[$r][1] = 'Read shared customer invoices';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'invoice';
		$this->rights[$r][5] = 'read';

		$r++;
		$this->rights[$r][0] = 5062;
		$this->rights[$r][1] = 'Create/modify shared customer invoices';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'invoice';
		$this->rights[$r][5] = 'write';

		// There is a particular permission for unvalidate because this may be not forbidden by some laws
		$r++;
		$this->rights[$r][0] = 5063;
		$this->rights[$r][1] = 'Devalidate shared customer invoices';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'invoice_advance';
		$this->rights[$r][5] = 'unvalidate';

		$r++;
		$this->rights[$r][0] = 5064;
		$this->rights[$r][1] = 'Validate shared customer invoices';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'invoice_advance';
		$this->rights[$r][5] = 'validate';

		$r++;
		$this->rights[$r][0] = 5065;
		$this->rights[$r][1] = 'Send shared customer invoices';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'invoice_advance';
		$this->rights[$r][5] = 'send';

		$r++;
		$this->rights[$r][0] = 5066;
		$this->rights[$r][1] = 'Issue payments on shared customer invoices';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'invoice';
		$this->rights[$r][5] = 'payment';

		$r++;
		$this->rights[$r][0] = 5067;
		$this->rights[$r][1] = 'Re-open a fully paid shared customer invoices';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'invoice_advance';
		$this->rights[$r][5] = 'reopen';

		$r++;
		$this->rights[$r][0] = 5069;
		$this->rights[$r][1] = 'Delete shared customer invoices';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'invoice';
		$this->rights[$r][5] = 'delete';

		// Main menu entries
		$this->menus = array();			// List of menus to add
		$r=0;

  	}

	/**
     *		Function called when module is enabled.
     *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *		It also creates data directories.
	 *
	 *      @param string $options Options when enabling module ('', 'newboxdefonly', 'noboxes')
	 *                             'noboxes' = Do not insert boxes
	 *                             'newboxdefonly' = For boxes, insert def of boxes only and not boxes activation
	 *      @return int 1 if OK, 0 if KO
     */
	function init($options = '')
	{
		$sql = array();

		$result=$this->load_tables();

		$result=$this->setSuperAdmin();

		$result=$this->setFirstEntity();

		return $this->_init($sql, $options);
	}

	/**
	 *		Function called when module is disabled.
 	 *      Remove from database constants, boxes and permissions from Dolibarr database.
 	 *		Data directories are not deleted.
	 *
	 *      @param string $options Options when enabling module ('', 'noboxes')
	 *      @return int 1 if OK, 0 if KO
 	 */
	function remove($options = '')
	{
		$sql = array(
			"DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = ".$this->db->encrypt('MAIN_MODULE_MULTICOMPANY_CSS', 1),
			"DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = ".$this->db->encrypt('MAIN_MODULE_MULTICOMPANY_LOGIN', 1)
		);

		return $this->_remove($sql, $options);
	}

	/**
	 *		Create tables and keys required by module
	 *		This function is called by this->init.
	 * 		@return		int		<=0 if KO, >0 if OK
	 */
	function load_tables()
	{
		return $this->_load_tables('/multicompany/sql/');
	}

	/**
	 *	Set the first entity
	 *
	 *	@return int
	 */
	function setSuperAdmin()
	{
		global $user;

		$sql = 'SELECT count(rowid) FROM '.MAIN_DB_PREFIX.'user';
		$sql.= ' WHERE admin = 1 AND entity = 0';
		$res = $this->db->query($sql);
		if ($res) $num = $this->db->fetch_array($res);
		else dol_print_error($this->db);

		if (empty($num[0]))
		{
			$this->db->begin();

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'user SET entity = 0';
			$sql.= ' WHERE admin = 1 AND rowid IN (1,'.$user->id.')';
			if ($this->db->query($sql))
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			return 0;
		}
	}

   /**
	*	Set the first entity
	*
	*	@return int
	*/
	function setFirstEntity()
	{
		global $user, $langs;

		$langs->load('multicompany@multicompany');

		$sql = 'SELECT count(rowid) FROM '.MAIN_DB_PREFIX.'entity';
		$res = $this->db->query($sql);
		if ($res) $num = $this->db->fetch_array($res);
		else dol_print_error($this->db);

		if (empty($num[0]))
		{
			$this->db->begin();

			$now = dol_now();

			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'entity (';
			$sql.= 'label';
			$sql.= ', description';
			$sql.= ', datec';
			$sql.= ', fk_user_creat';
			$sql.= ') VALUES (';
			$sql.= '\''.$langs->trans("MasterEntity").'\'';
			$sql.= ', \''.$langs->trans("MasterEntityDesc").'\'';
			$sql.= ', \''.$this->db->idate($now).'\'';
			$sql.= ', '.$user->id;
			$sql.= ')';

			if ($this->db->query($sql))
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			return 0;
		}
	}
}
