<?php
/* Copyright (C) 2005       Matthieu Valleton       <mv@seeschloss.org>
 * Copyright (C) 2005       Davoleau Brice          <brice.davoleau@gmail.com>
 * Copyright (C) 2005       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2006-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007       Patrick Raguin          <patrick.raguin@gmail.com>
 * Copyright (C) 2013-2016  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013-2016  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016       Charlie Benke           <charlie@patas-monkey.com>
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
 *	\file       htdocs/categories/class/categorie.class.php
 *	\ingroup    categorie
 *	\brief      File of class to manage categories
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';


/**
 *	Class to manage categories
 */
class Categorie extends CommonObject
{
	// Categories types (we use string because we want to accept any modules/types in a future)
	const TYPE_PRODUCT   = 'product';
	const TYPE_SUPPLIER  = 'supplier';
	const TYPE_CUSTOMER  = 'customer';
	const TYPE_MEMBER    = 'member';
	const TYPE_CONTACT   = 'contact';
	const TYPE_USER      = 'user';
	const TYPE_PROJECT   = 'project';
	const TYPE_ACCOUNT   = 'bank_account';
    const TYPE_BANK_LINE = 'bank_line';

	public $picto = 'category';


	/**
	 * @var array ID mapping from type string
	 *
	 * @note This array should be remove in future, once previous constants are moved to the string value. Deprecated
	 */
	private $MAP_ID = array(
		'product'      => 0,
		'supplier'     => 1,
		'customer'     => 2,
		'member'       => 3,
		'contact'      => 4,
		'bank_account' => 5,
        'project'      => 6,
		'user'         => 7,
		'bank_line'    => 8,
	);
	public static $MAP_ID_TO_CODE = array(
		0 => 'product',
		1 => 'supplier',
		2 => 'customer',
		3 => 'member',
		4 => 'contact',
		5 => 'bank_account',
		6 => 'project',
		7 => 'user',
		8 => 'bank_line',
	);

	/**
	 * @var array Foreign keys mapping from type string
	 *
	 * @note Move to const array when PHP 5.6 will be our minimum target
	 */
	private $MAP_CAT_FK = array(
		'product'  => 'product',
		'customer' => 'soc',
		'supplier' => 'soc',
		'member'   => 'member',
		'contact'  => 'socpeople',
		'user'     => 'user',
        'account'  => 'account',		// old for bank_account
        'bank_account' => 'account',
        'project'  => 'project',
	);
	/**
	 * @var array Category tables mapping from type string
	 *
	 * @note Move to const array when PHP 5.6 will be our minimum target
	 */
	private $MAP_CAT_TABLE = array(
		'product'  => 'product',
		'customer' => 'societe',
		'supplier' => 'fournisseur',
		'member'   => 'member',
		'contact'  => 'contact',
		'user'     => 'user',
        'account'  => 'account',		// old for bank_account
        'bank_account'=> 'account',
        'project'  => 'project',
	);
	/**
	 * @var array Object class mapping from type string
	 *
	 * @note Move to const array when PHP 5.6 will be our minimum target
	 */
	private $MAP_OBJ_CLASS = array(
		'product'  => 'Product',
		'customer' => 'Societe',
		'supplier' => 'Fournisseur',
		'member'   => 'Adherent',
		'contact'  => 'Contact',
		'user'     => 'User',
		'account'  => 'Account',		// old for bank account
		'bank_account'  => 'Account',
        'project'  => 'Project',
	);
	/**
	 * @var array Object table mapping from type string
	 *
	 * @note Move to const array when PHP 5.6 will be our minimum target
	 */
	private $MAP_OBJ_TABLE = array(
		'product'  => 'product',
		'customer' => 'societe',
		'supplier' => 'societe',
		'member'   => 'adherent',
		'contact'  => 'socpeople',
		'user'     => 'user',
        'account'  => 'bank_account',
        'project'  => 'projet',
	);

	public $element='category';
	public $table_element='categorie';

	public $fk_parent;
	public $label;
	public $description;
	/**
	 * @var string     Color
	 */
	public $color;
	/**
	 * @var ???
	 */
	public $socid;
	/**
	 * @var string	Category type
	 *
	 * @see Categorie::TYPE_PRODUCT
	 * @see Categorie::TYPE_SUPPLIER
	 * @see Categorie::TYPE_CUSTOMER
	 * @see Categorie::TYPE_MEMBER
	 * @see Categorie::TYPE_CONTACT
	 * @see Categorie::TYPE_USER
	 * @see Categorie::TYPE_ACCOUNT
	 * @see Categorie::TYPE_PROJECT
	 */
	public $type;

	public $cats = array();			// Categories table in memory
	public $motherof = array();

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db     Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * 	Load category into memory from database
	 *
	 * 	@param		int		$id		Id of category
	 *  @param		string	$label	Label of category
	 *  @param		string	$type	Type of category ('product', '...') or (0, 1, ...)
	 * 	@return		int				<0 if KO, >0 if OK
	 */
	function fetch($id, $label='', $type=null)
	{
		global $conf;

		// Check parameters
		if (empty($id) && empty($label)) return -1;
		if (! is_numeric($type)) $type=$this->MAP_ID[$type];

		$sql = "SELECT rowid, fk_parent, entity, label, description, color, fk_soc, visible, type";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
		if ($id > 0)
		{
			$sql.= " WHERE rowid = ".$id;
		}
		else
		{
			$sql.= " WHERE label = '".$this->db->escape($label)."' AND entity IN (".getEntity('category').")";
			if (! is_null($type)) $sql.= " AND type = ".$this->db->escape($type);
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$res = $this->db->fetch_array($resql);

				$this->id			= $res['rowid'];
				//$this->ref			= $res['rowid'];
				$this->fk_parent	= $res['fk_parent'];
				$this->label		= $res['label'];
				$this->description	= $res['description'];
				$this->color    	= $res['color'];
				$this->socid		= $res['fk_soc'];
				$this->visible		= $res['visible'];
				$this->type			= $res['type'];
				$this->entity		= $res['entity'];

				// Retreive all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				$this->db->free($resql);

				// multilangs
				if (! empty($conf->global->MAIN_MULTILANGS)) $this->getMultiLangs();

				return 1;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Add category into database
	 *
	 * 	@param	User	$user		Object user
	 * 	@return	int 				-1 : SQL error
	 *          					-2 : new ID unknown
	 *          					-3 : Invalid category
	 * 								-4 : category already exists
	 */
	function create($user)
	{
		global $conf,$langs,$hookmanager;
		$langs->load('categories');

		$type=$this->type;

		if (! is_numeric($type)) $type=$this->MAP_ID[$type];

		$error=0;

		dol_syslog(get_class($this).'::create', LOG_DEBUG);

		// Clean parameters
		$this->label = trim($this->label);
		$this->description = trim($this->description);
		$this->color = trim($this->color);
		$this->import_key = trim($this->import_key);
		if (empty($this->visible)) $this->visible=0;
		$this->fk_parent = ($this->fk_parent != "" ? intval($this->fk_parent) : 0);

		if ($this->already_exists())
		{
			$this->error=$langs->trans("ImpossibleAddCat", $this->label);
			$this->error.=" : ".$langs->trans("CategoryExistsAtSameLevel");
			dol_syslog($this->error, LOG_WARNING);
			return -4;
		}

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."categorie (";
		$sql.= "fk_parent,";
		$sql.= " label,";
		$sql.= " description,";
		$sql.= " color,";
		if (! empty($conf->global->CATEGORY_ASSIGNED_TO_A_CUSTOMER))
		{
			$sql.= "fk_soc,";
		}
		$sql.= " visible,";
		$sql.= " type,";
		$sql.= " import_key,";
		$sql.= " entity";
		$sql.= ") VALUES (";
		$sql.= $this->db->escape($this->fk_parent).",";
		$sql.= "'".$this->db->escape($this->label)."',";
		$sql.= "'".$this->db->escape($this->description)."',";
		$sql.= "'".$this->db->escape($this->color)."',";
		if (! empty($conf->global->CATEGORY_ASSIGNED_TO_A_CUSTOMER))
		{
			$sql.= ($this->socid != -1 ? $this->socid : 'null').",";
		}
		$sql.= "'".$this->db->escape($this->visible)."',";
		$sql.= $this->db->escape($type).",";
		$sql.= (! empty($this->import_key)?"'".$this->db->escape($this->import_key)."'":'null').",";
		$sql.= $this->db->escape($conf->entity);
		$sql.= ")";

		$res = $this->db->query($sql);
		if ($res)
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."categorie");

			if ($id > 0)
			{
				$this->id = $id;

				$action='create';

				// Actions on extra fields
				if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
				{
					$result=$this->insertExtraFields();
					if ($result < 0)
					{
						$error++;
					}
				}

				if (! $error)
				{
	                // Call trigger
    	            $result=$this->call_trigger('CATEGORY_CREATE',$user);
        	        if ($result < 0) { $error++; }
            	    // End call triggers
				}

                if ( ! $error )
                {
    				$this->db->commit();
    				return $id;
                }
                else
              	{
                	$this->db->rollback();
                    return -3;
                }
			}
			else
			{
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * 	Update category
	 *
	 *	@param	User	$user		Object user
	 * 	@return	int		 			1 : OK
	 *          					-1 : SQL error
	 *          					-2 : invalid category
	 */
	function update(User $user)
	{
		global $conf, $langs,$hookmanager;

		$error=0;

		// Clean parameters
		$this->label=trim($this->label);
		$this->description=trim($this->description);
		$this->fk_parent = ($this->fk_parent != "" ? intval($this->fk_parent) : 0);
		$this->visible = ($this->visible != "" ? intval($this->visible) : 0);

		if ($this->already_exists())
		{
			$this->error=$langs->trans("ImpossibleUpdateCat");
			$this->error.=" : ".$langs->trans("CategoryExistsAtSameLevel");
			return -1;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."categorie";
		$sql.= " SET label = '".$this->db->escape($this->label)."',";
		$sql.= " description = '".$this->db->escape($this->description)."',";
		$sql.= " color = '".$this->db->escape($this->color)."'";
		if (! empty($conf->global->CATEGORY_ASSIGNED_TO_A_CUSTOMER))
		{
			$sql .= ", fk_soc = ".($this->socid != -1 ? $this->socid : 'null');
		}
		$sql .= ", visible = '".$this->db->escape($this->visible)."'";
		$sql .= ", fk_parent = ".$this->fk_parent;
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		if ($this->db->query($sql))
		{
			$action='update';

			// Actions on extra fields
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
			{
				$result=$this->insertExtraFields();
				if ($result < 0)
				{
					$error++;
				}
			}

			if (! $error)
			{
	            // Call trigger
    	        $result=$this->call_trigger('CATEGORY_MODIFY',$user);
        	    if ($result < 0) { $error++; $this->db->rollback(); return -1; }
            	// End call triggers
			}

			$this->db->commit();

			return 1;
		}
		else
		{
			$this->db->rollback();
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Delete a category from database
	 *
	 * 	@param	User	$user		Object user that ask to delete
     *	@param	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return	int                 <0 KO >0 OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf,$langs;

		$error=0;

        // Clean parameters
		$this->fk_parent = ($this->fk_parent != "" ? intval($this->fk_parent) : 0);

		dol_syslog(get_class($this)."::remove");

		$this->db->begin();

		if (! $error && ! $notrigger)
		{
		    // Call trigger
		    $result=$this->call_trigger('CATEGORY_DELETE',$user);
		    if ($result < 0) $error++;
		    // End call triggers
		}

		/* FIX #1317 : Check for child category and move up 1 level*/
		if (! $error)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."categorie";
			$sql.= " SET fk_parent = ".$this->fk_parent;
			$sql.= " WHERE fk_parent = ".$this->id;

			if (!$this->db->query($sql))
			{
				$this->error=$this->db->lasterror();
				$error++;
			}
		}
		if (! $error)
		{
			$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_societe";
			$sql .= " WHERE fk_categorie = ".$this->id;
			if (!$this->db->query($sql))
			{
				$this->error=$this->db->lasterror();
				$error++;
			}
		}
		if (! $error)
		{
			$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_fournisseur";
			$sql .= " WHERE fk_categorie = ".$this->id;
			if (!$this->db->query($sql))
			{
				$this->error=$this->db->lasterror();
				$error++;
			}
		}
		if (! $error)
		{
			$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_product";
			$sql .= " WHERE fk_categorie = ".$this->id;
			if (!$this->db->query($sql))
			{
				$this->error=$this->db->lasterror();
				$error++;
			}
		}
		if (! $error)
		{
			$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_member";
			$sql .= " WHERE fk_categorie = ".$this->id;
			if (!$this->db->query($sql))
			{
				$this->error=$this->db->lasterror();
				$error++;
			}
		}
		if (! $error)
		{
			$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_contact";
			$sql .= " WHERE fk_categorie = ".$this->id;
			if (!$this->db->query($sql))
			{
				$this->error=$this->db->lasterror();
				$error++;
			}
		}
		if (! $error)
		{
			$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_contact";
			$sql .= " WHERE fk_categorie = ".$this->id;
			if (!$this->db->query($sql))
			{
				$this->error=$this->db->lasterror();
				$error++;
			}
		}
		if (! $error)
		{
			$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_account";
			$sql .= " WHERE fk_categorie = ".$this->id;
			if (!$this->db->query($sql))
			{
				$this->error=$this->db->lasterror();
				dol_syslog("Error sql=".$sql." ".$this->error, LOG_ERR);
				$error++;
			}
		}
		if (! $error)
		{
		    $sql  = "DELETE FROM ".MAIN_DB_PREFIX."bank_class";
		    $sql .= " WHERE fk_categ = ".$this->id;
		    if (!$this->db->query($sql))
		    {
		        $this->error=$this->db->lasterror();
		        dol_syslog("Error sql=".$sql." ".$this->error, LOG_ERR);
		        $error++;
		    }
		}

		if (! $error)
		{
			$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_lang";
			$sql .= " WHERE fk_category = ".$this->id;
			if (!$this->db->query($sql))
			{
				$this->error=$this->db->lasterror();
				dol_syslog("Error sql=".$sql." ".$this->error, LOG_ERR);
				$error++;
			}
		}

		// Delete category
		if (! $error)
		{
			$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie";
			$sql .= " WHERE rowid = ".$this->id;
			if (!$this->db->query($sql))
			{
				$this->error=$this->db->lasterror();
				$error++;
			}
		}

		// Removed extrafields
		if (! $error && empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
		{
			$result=$this->deleteExtraFields();
			if ($result < 0)
			{
				$error++;
				dol_syslog(get_class($this)."::delete erreur ".$this->error, LOG_ERR);
			}
		}

		if (! $error)
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

	/**
	 * Link an object to the category
	 *
	 * @param   CommonObject 	$obj  	Object to link to category
	 * @param   string     		$type 	Type of category ('product', ...)
	 * @return  int                		1 : OK, -1 : erreur SQL, -2 : id not defined, -3 : Already linked
	 */
	function add_type($obj, $type)
	{
		global $user,$langs,$conf;

		$error=0;

		if ($this->id == -1) return -2;

        $this->db->begin();

		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "categorie_" . $this->MAP_CAT_TABLE[$type];
		$sql .= " (fk_categorie, fk_" . $this->MAP_CAT_FK[$type] . ")";
		$sql .= " VALUES (" . $this->id . ", " . $obj->id . ")";

		dol_syslog(get_class($this).'::add_type', LOG_DEBUG);
		if ($this->db->query($sql))
		{
			if (! empty($conf->global->CATEGORIE_RECURSIV_ADD))
			{
				$sql = 'SELECT fk_parent FROM '.MAIN_DB_PREFIX.'categorie';
				$sql.= " WHERE rowid = ".$this->id;

				dol_syslog(get_class($this)."::add_type", LOG_DEBUG);
				$resql=$this->db->query($sql);
				if ($resql)
				{
					if ($this->db->num_rows($resql) > 0)
					{
                        $objparent = $this->db->fetch_object($resql);

						if (!empty($objparent->fk_parent))
						{
							$cat = new Categorie($this->db);
							$cat->id = $objparent->fk_parent;
							if (!$cat->containsObject($type, $obj->id)) {
								$result = $cat->add_type($obj, $type);
								if ($result < 0)
								{
									$this->error = $cat->error;
									$error++;
								}
							}
						}
					}
				}
				else
				{
					$error++;
					$this->error=$this->db->lasterror();
				}

				if ($error)
				{
				    $this->db->rollback();
					return -1;
				}
			}



            // Call trigger
			$this->context=array('linkto'=>$obj);	// Save object we want to link category to into category instance to provide information to trigger
			$result=$this->call_trigger('CATEGORY_LINK',$user);
            if ($result < 0) { $error++; }
            // End call triggers

			if (! $error)
			{
			    $this->db->commit();
			    return 1;
			}
			else
			{
			    $this->db->rollback();
			    return -2;
			}

		}
		else
		{
		    $this->db->rollback();
			if ($this->db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$this->error=$this->db->lasterrno();
				return -3;
			}
			else
			{
				$this->error=$this->db->lasterror();
			}
			return -1;
		}
	}

	/**
	 * Delete object from category
	 *
	 * @param   CommonObject $obj  Object
	 * @param   string       $type Type of category ('customer', 'supplier', 'contact', 'product', 'member')
	 *
	 * @return  int          1 if OK, -1 if KO
	 */
	function del_type($obj,$type)
	{
		global $user,$langs,$conf;

		$error=0;

		// For backward compatibility
		if ($type == 'societe') {
			$type = 'customer';
			dol_syslog( get_class( $this ) . "::del_type(): type 'societe' is deprecated, please use 'customer' instead", LOG_WARNING);
		} elseif ($type == 'fournisseur') {
			$type = 'supplier';
			dol_syslog( get_class( $this ) . "::del_type(): type 'fournisseur' is deprecated, please use 'supplier' instead", LOG_WARNING);
		}

        $this->db->begin();

		$sql = "DELETE FROM " . MAIN_DB_PREFIX . "categorie_" . $this->MAP_CAT_TABLE[$type];
		$sql .= " WHERE fk_categorie = " . $this->id;
		$sql .= " AND   fk_" . $this->MAP_CAT_FK[$type] . "  = " . $obj->id;

		dol_syslog(get_class($this).'::del_type', LOG_DEBUG);
		if ($this->db->query($sql))
		{
			// Save object we want to unlink category off into category instance to provide information to trigger
			$this->unlinkoff=$obj;

            // Call trigger
            $result=$this->call_trigger('CATEGORY_UNLINK',$user);
            if ($result < 0) { $error++; }
            // End call triggers

			if (! $error)
			{
			    $this->db->commit();
			    return 1;
			}
			else
			{
			    $this->db->rollback();
                return -2;
			}
		}
		else
		{
		    $this->db->rollback();
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Return list of fetched instance of elements having this category
	 *
	 * @param   string     $type       Type of category ('customer', 'supplier', 'contact', 'product', 'member')
	 * @param   int        $onlyids    Return only ids of objects (consume less memory)
	 * @return  mixed                  -1 if KO, array of instance of object if OK
	 * @see containsObject
	 */
	function getObjectsInCateg($type, $onlyids=0)
	{
		$objs = array();

		$obj = new $this->MAP_OBJ_CLASS[$type]( $this->db );

		$sql = "SELECT c.fk_" . $this->MAP_CAT_FK[$type];
		$sql .= " FROM " . MAIN_DB_PREFIX . "categorie_" . $this->MAP_CAT_TABLE[$type] . " as c";
		$sql .= ", " . MAIN_DB_PREFIX . $this->MAP_OBJ_TABLE[$type] . " as o";
		$sql .= " WHERE o.entity IN (" . getEntity( $obj->element).")";
		$sql.= " AND c.fk_categorie = ".$this->id;
		$sql .= " AND c.fk_" . $this->MAP_CAT_FK[$type] . " = o.rowid";

		dol_syslog(get_class($this)."::getObjectsInCateg", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($rec = $this->db->fetch_array($resql))
			{
			    if ($onlyids)
			    {
			        $objs[] = $rec['fk_' . $this->MAP_CAT_FK[$type]];
			    }
			    else
			    {
				    $obj = new $this->MAP_OBJ_CLASS[$type]( $this->db );
				    $obj->fetch( $rec['fk_' . $this->MAP_CAT_FK[$type]]);
				    $objs[] = $obj;
			    }
			}
			return $objs;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			return -1;
		}
	}

	/**
	 * Check for the presence of an object in a category
	 *
	 * @param   string $type      		Type of category ('customer', 'supplier', 'contact', 'product', 'member')
	 * @param   int    $object_id 		Id of the object to search
	 * @return  int                     Number of occurrences
	 * @see getObjectsInCateg
	 */
	function containsObject($type, $object_id)
	{
		$sql = "SELECT COUNT(*) as nb FROM " . MAIN_DB_PREFIX . "categorie_" . $this->MAP_CAT_TABLE[$type];
		$sql .= " WHERE fk_categorie = " . $this->id . " AND fk_" . $this->MAP_CAT_FK[$type] . " = " . $object_id;
		dol_syslog(get_class($this)."::containsObject", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			return $this->db->fetch_object($resql)->nb;
		} else {
			$this->error=$this->db->error().' sql='.$sql;
			return -1;
		}
	}

	/**
	 * List categories of an element id
	 *
	 * @param	int		$id			Id of element
	 * @param	string	$type		Type of category ('member', 'customer', 'supplier', 'product', 'contact')
	 * @param	string	$sortfield	Sort field
	 * @param	string	$sortorder	Sort order
	 * @param	int		$limit		Limit for list
	 * @param	int		$page		Page number
	 * @return	array				Array of categories
	 */
	function getListForItem($id, $type='customer', $sortfield = "s.rowid", $sortorder = 'ASC', $limit = 0, $page = 0)
	{
		global $conf;

		$categories = array();

		$sub_type = $type;
		$subcol_name = "fk_".$type;
		if ($type=="customer") {
			$sub_type="societe";
			$subcol_name="fk_soc";
		}
		if ($type=="supplier") {
			$sub_type="fournisseur";
			$subcol_name="fk_soc";
		}
		if ($type=="contact") {
			$subcol_name="fk_socpeople";
		}
		$sql = "SELECT s.rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie as s";
		$sql.= " , ".MAIN_DB_PREFIX."categorie_".$sub_type." as sub ";
		$sql.= ' WHERE s.entity IN ('.getEntity('category').')';
		$sql.= ' AND s.type='.array_search($type, self::$MAP_ID_TO_CODE);
		$sql.= ' AND s.rowid = sub.fk_categorie';
		$sql.= ' AND sub.'.$subcol_name.' = '.$id;

		$nbtotalofrecords = '';
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
		{
			$result = $this->db->query($sql);
			$nbtotalofrecords = $this->db->num_rows($result);
		}

		$sql.= $this->db->order($sortfield, $sortorder);
		if ($limit)	{
			if ($page < 0)
			{
				$page = 0;
			}
			$offset = $limit * $page;

			$sql.= $this->db->plimit($limit + 1, $offset);
		}

		$result = $this->db->query($sql);
		if ($result)
		{
			$i=0;
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min)
			{
				$obj = $this->db->fetch_object($result);
				$category_static = new Categorie($this->db);
				if ($category_static->fetch($obj->rowid))
				{
					$categories[$i]['id'] 				= $category_static->id;
					$categories[$i]['fk_parent']		= $category_static->fk_parent;
					$categories[$i]['label']			= $category_static->label;
					$categories[$i]['description']		= $category_static->description;
					$categories[$i]['color']    		= $category_static->color;
					$categories[$i]['socid']			= $category_static->socid;
					$categories[$i]['visible']			= $category_static->visible;
					$categories[$i]['type']			= $category_static->type;
					$categories[$i]['entity']			= $category_static->entity;
					$categories[$i]['array_options']	= $category_static->array_options;

					// multilangs
					if (! empty($conf->global->MAIN_MULTILANGS)) 	{
						$categories[$i]['multilangs']	= $category_static->multilangs;
					}
				}
				$i++;
			}
		}
		else {
			$this->error = $this->db->lasterror();
			return -1;
		}
		if ( ! count($categories)) {
			return 0;
		}

		return $categories;
	}

	/**
	 * Return childs of a category
	 *
	 * @return	array|int   <0 KO, array ok
	 */
	function get_filles()
	{
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE fk_parent = ".$this->id;

		$res  = $this->db->query($sql);
		if ($res)
		{
			$cats = array ();
			while ($rec = $this->db->fetch_array($res))
			{
				$cat = new Categorie($this->db);
				$cat->fetch($rec['rowid']);
				$cats[] = $cat;
			}
			return $cats;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Load this->motherof that is array(id_son=>id_parent, ...)
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	private function load_motherof()
	{
		global $conf;

		$this->motherof=array();

		// Load array[child]=parent
		$sql = "SELECT fk_parent as id_parent, rowid as id_son";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE fk_parent != 0";
		$sql.= " AND entity IN (".getEntity('category').")";

		dol_syslog(get_class($this)."::load_motherof", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj= $this->db->fetch_object($resql))
			{
				$this->motherof[$obj->id_son]=$obj->id_parent;
			}
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * Rebuilding the category tree as an array
	 * Return an array of table('id','id_mere',...) trie selon arbre et avec:
	 *                id = id de la categorie
	 *                id_mere = id de la categorie mere
	 *                id_children = tableau des id enfant
	 *                label = nom de la categorie
	 *                fulllabel = nom avec chemin complet de la categorie
	 *                fullpath = chemin complet compose des id
	 *
	 * @param   string 	$type        	Type of categories ('customer', 'supplier', 'contact', 'product', 'member') or (0, 1, 2, ...).
	 * @param   int    	$markafterid 	Removed all categories including the leaf $markafterid in category tree.
	 *
	 * @return  array               	Array of categories. this->cats and this->motherof are set.
	 */
	function get_full_arbo($type, $markafterid=0)
	{
	    global $conf, $langs;

		if (! is_numeric($type)) $type = $this->MAP_ID[$type];

		$this->cats = array();

		// Init this->motherof that is array(id_son=>id_parent, ...)
		$this->load_motherof();
		$current_lang = $langs->getDefaultLang();

		// Init $this->cats array
		$sql = "SELECT DISTINCT c.rowid, c.label, c.description, c.color, c.fk_parent";	// Distinct reduce pb with old tables with duplicates
		if (! empty($conf->global->MAIN_MULTILANGS)) $sql.= ", t.label as label_trans, t.description as description_trans";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c";
		if (! empty($conf->global->MAIN_MULTILANGS)) $sql.= " LEFT  JOIN ".MAIN_DB_PREFIX."categorie_lang as t ON t.fk_category=c.rowid AND t.lang='".$current_lang."'";
		$sql .= " WHERE c.entity IN (" . getEntity( 'category') . ")";
		$sql .= " AND c.type = " . $type;

		dol_syslog(get_class($this)."::get_full_arbo get category list", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$i=0;
			while ($obj = $this->db->fetch_object($resql))
			{
				$this->cats[$obj->rowid]['rowid'] = $obj->rowid;
				$this->cats[$obj->rowid]['id'] = $obj->rowid;
				$this->cats[$obj->rowid]['fk_parent'] = $obj->fk_parent;
				$this->cats[$obj->rowid]['label'] = ! empty($obj->label_trans) ? $obj->label_trans : $obj->label;
				$this->cats[$obj->rowid]['description'] = ! empty($obj->description_trans) ? $obj->description_trans : $obj->description;
				$this->cats[$obj->rowid]['color'] = $obj->color;
				$i++;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}

		// We add the fullpath property to each elements of first level (no parent exists)
		dol_syslog(get_class($this)."::get_full_arbo call to build_path_from_id_categ", LOG_DEBUG);
		foreach($this->cats as $key => $val)
		{
			//print 'key='.$key.'<br>'."\n";
			$this->build_path_from_id_categ($key,0);	// Process a branch from the root category key (this category has no parent)
		}

        // Exclude leaf including $markafterid from tree
        if ($markafterid)
        {
            //print "Look to discard category ".$markafterid."\n";
            $keyfilter1='^'.$markafterid.'$';
            $keyfilter2='_'.$markafterid.'$';
            $keyfilter3='^'.$markafterid.'_';
            $keyfilter4='_'.$markafterid.'_';
            foreach($this->cats as $key => $val)
            {
                if (preg_match('/'.$keyfilter1.'/',$val['fullpath']) || preg_match('/'.$keyfilter2.'/',$val['fullpath'])
                || preg_match('/'.$keyfilter3.'/',$val['fullpath']) || preg_match('/'.$keyfilter4.'/',$val['fullpath']))
                {
                    unset($this->cats[$key]);
                }
            }
        }

		dol_syslog(get_class($this)."::get_full_arbo dol_sort_array", LOG_DEBUG);
		$this->cats=dol_sort_array($this->cats, 'fulllabel', 'asc', true, false);

		//$this->debug_cats();

		return $this->cats;
	}

	/**
	 *	For category id_categ and its childs available in this->cats, define property fullpath and fulllabel
	 *
	 * 	@param		int		$id_categ		id_categ entry to update
	 * 	@param		int		$protection		Deep counter to avoid infinite loop
	 *	@return		void
	 */
	function build_path_from_id_categ($id_categ,$protection=1000)
	{
		dol_syslog(get_class($this)."::build_path_from_id_categ id_categ=".$id_categ." protection=".$protection, LOG_DEBUG);

		if (! empty($this->cats[$id_categ]['fullpath']))
		{
			// Already defined
			dol_syslog(get_class($this)."::build_path_from_id_categ fullpath and fulllabel already defined", LOG_WARNING);
			return;
		}

		// First build full array $motherof
		//$this->load_motherof();	// Disabled because already done by caller of build_path_from_id_categ

		// Define fullpath and fulllabel
		$this->cats[$id_categ]['fullpath'] = '_'.$id_categ;
		$this->cats[$id_categ]['fulllabel'] = $this->cats[$id_categ]['label'];
		$i=0; $cursor_categ=$id_categ;
		//print 'Work for id_categ='.$id_categ.'<br>'."\n";
		while ((empty($protection) || $i < $protection) && ! empty($this->motherof[$cursor_categ]))
		{
			//print '&nbsp; cursor_categ='.$cursor_categ.' i='.$i.' '.$this->motherof[$cursor_categ].'<br>'."\n";
			$this->cats[$id_categ]['fullpath'] = '_'.$this->motherof[$cursor_categ].$this->cats[$id_categ]['fullpath'];
			$this->cats[$id_categ]['fulllabel'] = $this->cats[$this->motherof[$cursor_categ]]['label'].' >> '.$this->cats[$id_categ]['fulllabel'];
			//print '&nbsp; Result for id_categ='.$id_categ.' : '.$this->cats[$id_categ]['fullpath'].' '.$this->cats[$id_categ]['fulllabel'].'<br>'."\n";
			$i++; $cursor_categ=$this->motherof[$cursor_categ];
		}
		//print 'Result for id_categ='.$id_categ.' : '.$this->cats[$id_categ]['fullpath'].'<br>'."\n";

		// We count number of _ to have level
		$this->cats[$id_categ]['level']=dol_strlen(preg_replace('/[^_]/i','',$this->cats[$id_categ]['fullpath']));

		return;
	}

	/**
	 *	Display content of $this->cats
	 *
	 *	@return	void
	 */
	function debug_cats()
	{
		// Display $this->cats
		foreach($this->cats as $key => $val)
		{
			print 'id: '.$this->cats[$key]['id'];
			print ' label: '.$this->cats[$key]['label'];
			print ' mother: '.$this->cats[$key]['fk_parent'];
			//print ' children: '.(is_array($this->cats[$key]['id_children'])?join(',',$this->cats[$key]['id_children']):'');
			print ' fullpath: '.$this->cats[$key]['fullpath'];
			print ' fulllabel: '.$this->cats[$key]['fulllabel'];
			print "<br>\n";
		}
	}


	/**
	 * 	Returns all categories
	 *
	 *	@param	int			$type		Type of category (0, 1, ...)
	 *	@param	boolean		$parent		Just parent categories if true
	 *	@return	array					Table of Object Category
	 */
	function get_all_categories($type=null, $parent=false)
	{
		if (! is_numeric($type)) $type = $this->MAP_ID[$type];

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE entity IN (".getEntity('category').")";
		if (! is_null($type))
			$sql.= " AND type = ".$type;
		if ($parent)
			$sql.= " AND fk_parent = 0";

		$res = $this->db->query($sql);
		if ($res)
		{
			$cats = array ();
			while ($rec = $this->db->fetch_array($res))
			{
				$cat = new Categorie($this->db);
				$cat->fetch($rec['rowid']);
				$cats[$rec['rowid']] = $cat;
			}
			return $cats;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Returns total number of categories
	 *
	 *	@return		int		Number of categories
	 *	@deprecated function not used ?
	 */
	function get_nb_categories()
	{
		$sql = "SELECT count(rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE entity IN (".getEntity('category').")";
		$res = $this->db->query($sql);
		if ($res)
		{
			$res = $this->db->fetch_array($res);
			return $res[0];
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Check if no category with same label already exists for this cat's parent or root and for this cat's type
	 *
	 * 	@return		integer		1 if already exist, 0 otherwise, -1 if error
	 */
	function already_exists()
	{
		$type=$this->type;

		if (! is_numeric($type)) $type=$this->MAP_ID[$type];

		/* We have to select any rowid from llx_categorie which category's mother and label
		 * are equals to those of the calling category
		 */
		$sql = "SELECT c.rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c ";
		$sql.= " WHERE c.entity IN (".getEntity('category').")";
		$sql.= " AND c.type = ".$type;
		$sql.= " AND c.fk_parent = ".$this->fk_parent;
		$sql.= " AND c.label = '".$this->db->escape($this->label)."'";

		dol_syslog(get_class($this)."::already_exists", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql) > 0)						// Checking for empty resql
			{
				$obj = $this->db->fetch_array($resql);
				/* If object called create, obj cannot have is id.
				 * If object called update, he mustn't have the same label as an other category for this mother.
				 * So if the result have the same id, update is not for label, and if result have an other one,
				 * update may be for label.
				 */
				if($obj[0] > 0 && $obj[0] != $this->id)
				{
					dol_syslog(get_class($this)."::already_exists category with name=".$this->label." and parent ".$this->fk_parent." exists: rowid=".$obj[0]." current_id=".$this->id, LOG_DEBUG);
					return 1;
				}
			}
			dol_syslog(get_class($this)."::already_exists no category with same name=".$this->label." and same parent ".$this->fk_parent." than category id=".$this->id, LOG_DEBUG);
			return 0;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *	Returns the top level categories (which are not girls)
	 *
	 *	@param		int		$type		Type of category (0, 1, ...)
	 *	@return		array
	 */
	function get_main_categories($type=null)
	{
		return $this->get_all_categories($type, true);
	}

	/**
	 * Returns the path of the category, with the names of the categories
	 * separated by $sep (" >> " by default)
	 *
	 * @param	string	$sep	     Separator
	 * @param	string	$url	     Url
	 * @param   int     $nocolor     0
	 * @return	array
	 */
	function print_all_ways($sep = " &gt;&gt; ", $url='', $nocolor=0)
	{
		$ways = array();

		$allways = $this->get_all_ways(); // Load array of categories
		foreach ($allways as $way)
		{
			$w = array();
			$i = 0;
			$forced_color='';
			foreach ($way as $cat)
			{
			    $i++;

			    if (empty($nocolor))
			    {
    			    $forced_color='toreplace';
    			    if ($i == count($way))
    			    {
    			        // Check contrast with background and correct text color
    			        $forced_color='categtextwhite';
    			        if ($cat->color)
    			        {
    			            if (colorIsLight($cat->color)) $forced_color='categtextblack';
    			        }
    			    }
			    }

				if ($url == '')
				{
			        $link = '<a href="'.DOL_URL_ROOT.'/categories/viewcat.php?id='.$cat->id.'&type='.$cat->type.'" class="'.$forced_color .'">';
			        $linkend='</a>';
				    $w[] = $link.$cat->label.$linkend;
				}
				else
				{
					$w[] = "<a href='".DOL_URL_ROOT."/$url?catid=".$cat->id."'>".$cat->label."</a>";
				}
			}
			$newcategwithpath = preg_replace('/toreplace/', $forced_color, implode($sep, $w));

			$ways[] = $newcategwithpath;
		}

		return $ways;
	}


	/**
	 *	Returns an array containing the list of parent categories
	 *
	 *	@return	int|array <0 KO, array OK
	 */
	function get_meres()
	{
		$parents = array();

		$sql = "SELECT fk_parent FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE rowid = ".$this->id;

		$res  = $this->db->query($sql);

		if ($res)
		{
			while ($rec = $this->db->fetch_array($res))
			{
				if ($rec['fk_parent'] > 0)
				{
					$cat = new Categorie($this->db);
					$cat->fetch($rec['fk_parent']);
					$parents[] = $cat;
				}
			}
			return $parents;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Returns in a table all possible paths to get to the category
	 * 	starting with the major categories represented by Tables of categories
	 *
	 *	@return	array
	 */
	function get_all_ways()
	{
		$ways = array();

		$parents=$this->get_meres();
		if (! empty($parents))
		{
			foreach ($parents as $parent)
			{
				$allways=$parent->get_all_ways();
				foreach ($allways as $way)
				{
					$w		= $way;
					$w[]	= $this;
					$ways[]	= $w;
				}
			}
		}

		if (count($ways) == 0)
			$ways[0][0] = $this;

		return $ways;
	}

	/**
	 * Return list of categories (object instances or labels) linked to element of id $id and type $type
	 * Should be named getListOfCategForObject
	 *
	 * @param   int    		$id     Id of element
	 * @param   string|int	$type   Type of category ('customer', 'supplier', 'contact', 'product', 'member') or (0, 1, 2, ...)
	 * @param   string 		$mode   'id'=Get array of category ids, 'object'=Get array of fetched category instances, 'label'=Get array of category
	 *                      	    labels, 'id'= Get array of category IDs
	 * @return  mixed           	Array of category objects or < 0 if KO
	 */
	function containing($id, $type, $mode='object')
	{
		$cats = array();

		if (is_numeric($type)) $type = Categorie::$MAP_ID_TO_CODE[$type];

		if ($type === Categorie::TYPE_BANK_LINE)   // TODO Remove this with standard category code
		{
		    // Load bank groups
		    $sql = "SELECT c.label, c.rowid";
		    $sql.= " FROM ".MAIN_DB_PREFIX."bank_class as a, ".MAIN_DB_PREFIX."bank_categ as c";
		    $sql.= " WHERE a.lineid=".$id." AND a.fk_categ = c.rowid";
		    $sql.= " ORDER BY c.label";

		    $res = $this->db->query($sql);
		    if ($res)
		    {
		        while ($obj = $this->db->fetch_object($res))
		        {
    				if ($mode == 'id') {
    				    $cats[] = $obj->rowid;
    				} else if ($mode == 'label') {
    				    $cats[] = $obj->label;
    				} else {
    				    $cat = new Categorie($this->db);
    				    $cat->id = $obj->rowid;
    				    $cat->label = $obj->label;
    				    $cats[] = $cat;
    				}
		        }
		    }
		    else
		    {
		        dol_print_error($this->db);
		        return -1;
		    }
		}
        else
        {
    		$sql = "SELECT ct.fk_categorie, c.label, c.rowid";
    		$sql .= " FROM " . MAIN_DB_PREFIX . "categorie_" . $this->MAP_CAT_TABLE[$type] . " as ct, " . MAIN_DB_PREFIX . "categorie as c";
    		$sql .= " WHERE ct.fk_categorie = c.rowid AND ct.fk_" . $this->MAP_CAT_FK[$type] . " = " . (int) $id . " AND c.type = " . $this->MAP_ID[$type];
    		$sql .= " AND c.entity IN (" . getEntity( 'category') . ")";

    		$res = $this->db->query($sql);
    		if ($res)
    		{
    			while ($obj = $this->db->fetch_object($res))
    			{
    				if ($mode == 'id') {
    					$cats[] = $obj->rowid;
    				} else if ($mode == 'label') {
    					$cats[] = $obj->label;
    				} else {
    					$cat = new Categorie($this->db);
    					$cat->fetch($obj->fk_categorie);
    					$cats[] = $cat;
    				}
    			}
    		}
    		else
    		{
    			dol_print_error($this->db);
    			return -1;
    		}
        }

        return $cats;
	}


	/**
	 * 	Returns categories whose id or name match
	 * 	add wildcards in the name unless $exact = true
	 *
	 * 	@param		int			$id			Id
	 * 	@param		string		$nom		Name
 	 * 	@param		string		$type		Type of category ('member', 'customer', 'supplier', 'product', 'contact'). Old mode (0, 1, 2, ...) is deprecated.
	 * 	@param		boolean		$exact		Exact string search (true/false)
	 * 	@param		boolean		$case		Case sensitive (true/false)
	 * 	@return		array					Array of category id
	 */
	function rechercher($id, $nom, $type, $exact = false, $case = false)
	{
		// Deprecation warning
		if (is_numeric($type)) {
			dol_syslog(__METHOD__ . ': using numeric types is deprecated.', LOG_WARNING);
		}

		$cats = array();

		// For backward compatibility
		if (is_numeric($type)) {
			// We want to reverse lookup
			$map_type = array_flip( $this->MAP_ID );
			$type = $map_type[$type];
			dol_syslog( get_class( $this ) . "::rechercher(): numeric types are deprecated, please use string instead",
				LOG_WARNING );
		}

		// Generation requete recherche
		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "categorie";
		$sql .= " WHERE type = " . $this->MAP_ID[$type];
		$sql .= " AND entity IN (" . getEntity( 'category') . ")";
		if ($nom)
		{
			if (! $exact)
				$nom = '%'.str_replace('*', '%', $nom).'%';
			if (! $case)
				$sql.= " AND label LIKE '".$this->db->escape($nom)."'";
			else
				$sql.= " AND label LIKE BINARY '".$this->db->escape($nom)."'";
		}
		if ($id)
		{
			$sql.=" AND rowid = '".$id."'";
		}

		$res  = $this->db->query($sql);
		if ($res)
		{
			while ($rec = $this->db->fetch_array($res))
			{
				$cat = new Categorie($this->db);
				$cat->fetch($rec['rowid']);
				$cats[] = $cat;
			}

			return $cats;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			return -1;
		}
	}

	/**
	 *	Return name and link of category (with picto)
	 *  Use ->id, ->ref, ->label, ->color
	 *
	 *	@param		int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param		string	$option			Sur quoi pointe le lien ('', 'xyz')
	 * 	@param		int		$maxlength		Max length of text
	 *	@return		string					Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='',$maxlength=0)
	{
		global $langs;

		$result='';
		$label=$langs->trans("ShowCategory").': '. ($this->ref?$this->ref:$this->label);

		// Check contrast with background and correct text color
		$forced_color='categtextwhite';
		if ($this->color)
		{
			if (colorIsLight($this->color)) $forced_color='categtextblack';
		}

        $link = '<a href="'.DOL_URL_ROOT.'/categories/viewcat.php?id='.$this->id.'&type='.$this->type.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip '.$forced_color .'">';
		$linkend='</a>';

		$picto='category';


        if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$link.dol_trunc(($this->ref?$this->ref:$this->label),$maxlength).$linkend;
		return $result;
	}


	/**
	 *  Deplace fichier uploade sous le nom $files dans le repertoire sdir
	 *
	 *  @param      string	$sdir       Repertoire destination finale
	 *  @param      string	$file		Nom du fichier uploade
	 *	@return		void
	 */
	function add_photo($sdir, $file)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$dir = $sdir .'/'. get_exdir($this->id,2,0,0,$this,'category') . $this->id ."/";
		$dir .= "photos/";

		if (! file_exists($dir))
		{
			dol_mkdir($dir);
		}

		if (file_exists($dir)) {
			if (is_array($file['name']) && count($file['name']) > 0)
			{
				$nbfile = count($file['name']);
				for ($i = 0; $i <= $nbfile; $i ++) {

					$originImage = $dir . $file['name'][$i];

					// Cree fichier en taille origine
					dol_move_uploaded_file($file['tmp_name'][$i], $originImage, 1, 0, 0);

					if (file_exists($originImage)) {
						// Create thumbs
						$this->addThumbs($originImage);
					}
				}
			} else {
				$originImage = $dir . $file['name'];

				// Cree fichier en taille origine
				dol_move_uploaded_file($file['tmp_name'], $originImage, 1, 0, 0);

				if (file_exists($originImage)) {
					// Create thumbs
					$this->addThumbs($originImage);
				}
			}
		}
	}

	/**
	 *    Return tableau de toutes les photos de la categorie
	 *
	 *    @param      string	$dir        Repertoire a scanner
	 *    @param      int		$nbmax      Nombre maximum de photos (0=pas de max)
	 *    @return     array       			Tableau de photos
	 */
	function liste_photos($dir,$nbmax=0)
	{
		include_once DOL_DOCUMENT_ROOT .'/core/lib/files.lib.php';

		$nbphoto=0;
		$tabobj=array();

		$dirthumb = $dir.'thumbs/';

		if (file_exists($dir))
		{
			$handle=opendir($dir);
            if (is_resource($handle))
            {
    			while (($file = readdir($handle)) !== false)
    			{
    				if (dol_is_file($dir.$file) && preg_match('/(\.jpeg|\.jpg|\.bmp|\.gif|\.png|\.tiff)$/i',$dir.$file))
    				{
    					$nbphoto++;
    					$photo = $file;

    					// On determine nom du fichier vignette
    					$photo_vignette='';
    					if (preg_match('/(\.jpeg|\.jpg|\.bmp|\.gif|\.png|\.tiff)$/i',$photo,$regs))
    					{
    						$photo_vignette=preg_replace('/'.$regs[0].'/i','',$photo).'_small'.$regs[0];
    					}

    					// Objet
    					$obj=array();
    					$obj['photo']=$photo;
    					if ($photo_vignette && is_file($dirthumb.$photo_vignette)) $obj['photo_vignette']='thumbs/' . $photo_vignette;
    					else $obj['photo_vignette']="";

    					$tabobj[$nbphoto-1]=$obj;

    					// On continue ou on arrete de boucler
    					if ($nbmax && $nbphoto >= $nbmax) break;
    				}
    			}

    			closedir($handle);
            }
		}

		return $tabobj;
	}

	/**
	 *    Efface la photo de la categorie et sa vignette
	 *
	 *    @param	string		$file		Path to file
	 *    @return	void
	 */
	function delete_photo($file)
	{
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	    $dir = dirname($file).'/'; // Chemin du dossier contenant l'image d'origine
		$dirthumb = $dir.'/thumbs/'; // Chemin du dossier contenant la vignette
		$filename = preg_replace('/'.preg_quote($dir,'/').'/i','',$file); // Nom du fichier

		// On efface l'image d'origine
		dol_delete_file($file,1);

		// Si elle existe, on efface la vignette
		if (preg_match('/(\.jpeg|\.jpg|\.bmp|\.gif|\.png|\.tiff)$/i',$filename,$regs))
		{
			$photo_vignette=preg_replace('/'.$regs[0].'/i','',$filename).'_small'.$regs[0];
			if (file_exists($dirthumb.$photo_vignette))
			{
				dol_delete_file($dirthumb.$photo_vignette,1);
			}
		}
	}

	/**
	 *  Load size of image file
	 *
	 *  @param    	string	$file        Path to file
	 *  @return		void
	 */
	function get_image_size($file)
	{
		$infoImg = getimagesize($file); // Recuperation des infos de l'image
		$this->imgWidth = $infoImg[0]; // Largeur de l'image
		$this->imgHeight = $infoImg[1]; // Hauteur de l'image
	}

	/**
	 *	Update ou cree les traductions des infos produits
	 *
	 *	@param	User	$user		Object user
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function setMultiLangs($user)
	{
	    global $langs;

	    $langs_available = $langs->get_available_languages();
	    $current_lang = $langs->getDefaultLang();

	    foreach ($langs_available as $key => $value)
	    {
	        $sql = "SELECT rowid";
	        $sql.= " FROM ".MAIN_DB_PREFIX."categorie_lang";
	        $sql.= " WHERE fk_category=".$this->id;
	        $sql.= " AND lang='".$key."'";

	        $result = $this->db->query($sql);

	        if ($key == $current_lang)
	        {
	            if ($this->db->num_rows($result)) // si aucune ligne dans la base
	            {
	                $sql2 = "UPDATE ".MAIN_DB_PREFIX."categorie_lang";
	                $sql2.= " SET label='".$this->db->escape($this->label)."',";
	                $sql2.= " description='".$this->db->escape($this->description)."'";
	                $sql2.= " WHERE fk_category=".$this->id." AND lang='".$this->db->escape($key)."'";
	            }
	            else
	            {
	                $sql2 = "INSERT INTO ".MAIN_DB_PREFIX."categorie_lang (fk_category, lang, label, description)";
	                $sql2.= " VALUES(".$this->id.",'".$key."','". $this->db->escape($this->label);
	                $sql2.= "','".$this->db->escape($this->multilangs["$key"]["description"])."')";
	            }
	            dol_syslog(get_class($this).'::setMultiLangs', LOG_DEBUG);
	            if (! $this->db->query($sql2))
	            {
	                $this->error=$this->db->lasterror();
	                return -1;
	            }
	        }
	        else if (isset($this->multilangs["$key"]))
	        {
	            if ($this->db->num_rows($result)) // si aucune ligne dans la base
	            {
	                $sql2 = "UPDATE ".MAIN_DB_PREFIX."categorie_lang";
	                $sql2.= " SET label='".$this->db->escape($this->multilangs["$key"]["label"])."',";
	                $sql2.= " description='".$this->db->escape($this->multilangs["$key"]["description"])."'";
	                $sql2.= " WHERE fk_category=".$this->id." AND lang='".$this->db->escape($key)."'";
	            }
	            else
	            {
	                $sql2 = "INSERT INTO ".MAIN_DB_PREFIX."categorie_lang (fk_category, lang, label, description)";
	                $sql2.= " VALUES(".$this->id.",'".$key."','". $this->db->escape($this->multilangs["$key"]["label"]);
	                $sql2.= "','".$this->db->escape($this->multilangs["$key"]["description"])."')";
	            }

	            // on ne sauvegarde pas des champs vides
	            if ( $this->multilangs["$key"]["label"] || $this->multilangs["$key"]["description"] || $this->multilangs["$key"]["note"] )
	                dol_syslog(get_class($this).'::setMultiLangs', LOG_DEBUG);
	            if (! $this->db->query($sql2))
	            {
	                $this->error=$this->db->lasterror();
	                return -1;
	            }
	        }
	    }

			// Call trigger
			$result = $this->call_trigger('CATEGORY_SET_MULTILANGS',$user);
			if ($result < 0) {
				$this->error = $this->db->lasterror();
				return -1;
			}
			// End call triggers

	    return 1;
	}

	/**
	 *	Load array this->multilangs
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function getMultiLangs()
	{
	    global $langs;

	    $current_lang = $langs->getDefaultLang();

	    $sql = "SELECT lang, label, description";
	    $sql.= " FROM ".MAIN_DB_PREFIX."categorie_lang";
	    $sql.= " WHERE fk_category=".$this->id;

	    $result = $this->db->query($sql);
	    if ($result)
	    {
	        while ( $obj = $this->db->fetch_object($result) )
	        {
	            //print 'lang='.$obj->lang.' current='.$current_lang.'<br>';
	            if( $obj->lang == $current_lang ) // si on a les traduct. dans la langue courante on les charge en infos principales.
	            {
	                $this->label		= $obj->label;
	                $this->description	= $obj->description;

	            }
	            $this->multilangs["$obj->lang"]["label"]		= $obj->label;
	            $this->multilangs["$obj->lang"]["description"]	= $obj->description;
	        }
	        return 1;
	    }
	    else
	    {
	        $this->error=$langs->trans("Error")." : ".$this->db->error()." - ".$sql;
	        return -1;
	    }
	}

	/**
	 *	Return label of contact status
	 *
	 *	@param      int			$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 * 	@return 	string					Label of contact status
	 */
	function getLibStatut($mode)
	{
	    return '';
	}


    /**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
     */
    function initAsSpecimen()
    {
        dol_syslog(get_class($this)."::initAsSpecimen");

        // Initialise parametres
        $this->id=0;
        $this->fk_parent=0;
        $this->label = 'SPECIMEN';
        $this->specimen=1;
        $this->description = 'This is a description';
        $this->socid = 1;
        $this->type = self::TYPE_PRODUCT;
    }

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old thirdparty id
	 * @param int $dest_id New thirdparty id
	 * @return bool
	 */
	public static function replaceThirdparty(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'categorie_societe'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables, 1);
	}
}
