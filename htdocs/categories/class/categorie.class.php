<?php
/* Copyright (C) 2005       Matthieu Valleton       <mv@seeschloss.org>
 * Copyright (C) 2005       Davoleau Brice          <brice.davoleau@gmail.com>
 * Copyright (C) 2005       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2012  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2006-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007       Patrick Raguin          <patrick.raguin@gmail.com>
 * Copyright (C) 2013       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013       Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
	// Categories types
	const TYPE_PRODUCT = 0;
	const TYPE_SUPPLIER = 1;
	const TYPE_CUSTOMER = 2;
	const TYPE_MEMBER = 3;
	const TYPE_CONTACT = 4;

	/**
	 * @var array ID mapping from type string
	 *
	 * @note Move to const array when PHP 5.6 will be our minimum target
	 */
	private $MAP_ID = array(
		'product'  => 0,
		'supplier' => 1,
		'customer' => 2,
		'member'   => 3,
		'contact'  => 4,
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
	);

	public $element='category';
	public $table_element='categories';

	var $fk_parent;
	var $label;
	var $description;
	/**
	 * @var string     Color
	 */
	var $color;
	/**
	 * @var ???
	 */
	var $socid;
	/**
	 * @var int Category type
	 *
	 * @see Categorie::TYPE_PRODUCT
	 * @see Categorie::TYPE_SUPPLIER
	 * @see Categorie::TYPE_CUSTOMER
	 * @see Categorie::TYPE_MEMBER
	 * @see Categorie::TYPE_CONTACT
	 */
	var $type;

	var $cats=array();			// Tableau en memoire des categories
	var $motherof=array();

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
	 * 	@return		int				<0 if KO, >0 if OK
	 */
	function fetch($id,$label='')
	{
		global $conf;

		// Check parameters
		if (empty($id) && empty($label)) return -1;

		$sql = "SELECT rowid, fk_parent, entity, label, description, color, fk_soc, visible, type";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
		if ($id)
		{
			$sql.= " WHERE rowid = '".$id."'";
		}

		else
		{
			if ($label) $sql.= " WHERE label = '".$this->db->escape($label)."' AND entity IN (".getEntity('category',1).")";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql) > 0)
			{
				$res = $this->db->fetch_array($resql);

				$this->id			= $res['rowid'];
				$this->fk_parent	= $res['fk_parent'];
				$this->label		= $res['label'];
				$this->description	= $res['description'];
				$this->color    	= $res['color'];
				$this->socid		= $res['fk_soc'];
				$this->visible		= $res['visible'];
				$this->type			= $res['type'];
				$this->entity		= $res['entity'];

				$this->fetch_optionals($this->id,null);

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
	 * 	@return	int 				-1 : erreur SQL
	 *          					-2 : nouvel ID inconnu
	 *          					-3 : categorie invalide
	 * 								-4 : category already exists
	 */
	function create($user)
	{
		global $conf,$langs,$hookmanager;
		$langs->load('categories');

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
			$this->error=$langs->trans("ImpossibleAddCat");
			$this->error.=" : ".$langs->trans("CategoryExistsAtSameLevel");
			dol_syslog($this->error, LOG_ERR);
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
		$sql.= $this->fk_parent.",";
		$sql.= "'".$this->db->escape($this->label)."',";
		$sql.= "'".$this->db->escape($this->description)."',";
		$sql.= "'".$this->db->escape($this->color)."',";
		if (! empty($conf->global->CATEGORY_ASSIGNED_TO_A_CUSTOMER))
		{
			$sql.= ($this->socid != -1 ? $this->socid : 'null').",";
		}
		$sql.= "'".$this->visible."',";
		$sql.= $this->type.",";
		$sql.= (! empty($this->import_key)?"'".$this->db->escape($this->import_key)."'":'null').",";
		$sql.= $conf->entity;
		$sql.= ")";

		$res = $this->db->query($sql);
		if ($res)
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."categorie");

			if ($id > 0)
			{
				$this->id = $id;

				$action='create';

				// Actions on extra fields (by external module or standard code)
				// TODO le hook fait double emploi avec le trigger !!
				$hookmanager->initHooks(array('HookModuleNamedao'));
				$parameters=array('socid'=>$this->id);
				$reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
				if (empty($reshook))
				{
					if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
					{
						$result=$this->insertExtraFields();
						if ($result < 0)
						{
							$error++;
						}
					}
				}
				else if ($reshook < 0) $error++;

                // Call trigger
                $result=$this->call_trigger('CATEGORY_CREATE',$user);
                if ($result < 0) { $error++; }
                // End call triggers

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
	function update($user='')
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
		$sql .= ", visible = '".$this->visible."'";
		$sql .= ", fk_parent = ".$this->fk_parent;
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		if ($this->db->query($sql))
		{
			$action='update';

			// Actions on extra fields (by external module or standard code)
			// TODO le hook fait double emploi avec le trigger !!
			$hookmanager->initHooks(array('HookCategorydao'));
			$parameters=array();
			$reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
			if (empty($reshook))
			{
				if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
				{
					$result=$this->insertExtraFields();
					if ($result < 0)
					{
						$error++;
					}
				}
			}
			else if ($reshook < 0) $error++;

			$this->db->commit();


            // Call trigger
            $result=$this->call_trigger('CATEGORY_MODIFY',$user);
            if ($result < 0) { $error++; $this->db->rollback(); return -1; }
            // End call triggers

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
	 *	@return	int <0 KO >0 OK
	 */
	function delete($user)
	{
		global $conf,$langs;

		$error=0;

        // Clean parameters
		$this->fk_parent = ($this->fk_parent != "" ? intval($this->fk_parent) : 0);

		dol_syslog(get_class($this)."::remove");

		$this->db->begin();

		/* FIX #1317 : Check for child cat and move up 1 level*/
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
			else
			{
				// Removed extrafields
				if (! $error)
				{
					if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
					{
						$result=$this->deleteExtraFields();
						if ($result < 0)
						{
							$error++;
							dol_syslog(get_class($this)."::delete erreur ".$this->error, LOG_ERR);
						}
					}
				}
                // Call trigger
                $result=$this->call_trigger('CATEGORY_DELETE',$user);
                if ($result < 0) { $error++; }
                // End call triggers
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
	 * @param   CommonObject $obj  Object to link to category
	 * @param   string       $type Type of category ('customer', 'supplier', 'contact', 'product', 'member')
	 *
	 * @return  int                1 : OK, -1 : erreur SQL, -2 : id not defined, -3 : Already linked
	 */
	function add_type($obj,$type)
	{
		global $user,$langs,$conf;

		$error=0;

		if ($this->id == -1) return -2;

		// For backward compatibility
		if ($type == 'societe') 
		{
			$type = 'customer';
			dol_syslog(get_class($this) . "::add_type(): type 'societe' is deprecated, please use 'customer' instead",	LOG_WARNING);
		}
		elseif ($type == 'fournisseur') 
		{
			$type = 'supplier';
			dol_syslog(get_class($this) . "::add_type(): type 'fournisseur' is deprecated, please use 'supplier' instead", LOG_WARNING);
		}

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
							$cat->id=$objparent->fk_parent;
							$result=$cat->add_type($obj, $type);
							if ($result < 0)
							{
								$this->error=$cat->error;
								$error++;
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

			// Save object we want to link category to into category instance to provide information to trigger
			$this->linkto=$obj;

            // Call trigger
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
			dol_syslog( get_class( $this ) . "::del_type(): type 'societe' is deprecated, please use 'customer' instead",
				LOG_WARNING );
		} elseif ($type == 'fournisseur') {
			$type = 'supplier';
			dol_syslog( get_class( $this ) . "::del_type(): type 'fournisseur' is deprecated, please use 'supplier' instead",
				LOG_WARNING );
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
	 * @param   string $type Type of category ('customer', 'supplier', 'contact', 'product', 'member')
	 *
	 * @return  mixed        -1 if KO, array of instance of object if OK
	 */
	function getObjectsInCateg($type)
	{
		$objs = array();

		$obj = new $this->MAP_OBJ_CLASS[$type]( $this->db );

		$sql = "SELECT c.fk_" . $this->MAP_CAT_FK[$type];
		$sql .= " FROM " . MAIN_DB_PREFIX . "categorie_" . $this->MAP_CAT_TABLE[$type] . " as c";
		$sql .= ", " . MAIN_DB_PREFIX . $this->MAP_OBJ_TABLE[$type] . " as o";
		$sql .= " WHERE o.entity IN (" . getEntity( $obj->element, 1).")";
		$sql.= " AND c.fk_categorie = ".$this->id;
		$sql .= " AND c.fk_" . $this->MAP_CAT_FK[$type] . " = o.rowid";

		dol_syslog(get_class($this)."::getObjectsInCateg", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($rec = $this->db->fetch_array($resql)) {
				$obj = new $this->MAP_OBJ_CLASS[$type]( $this->db );
				$obj->fetch( $rec['fk_' . $this->MAP_CAT_FK[$type]]);
				$objs[] = $obj;
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
	 * @param   string $type      Type of category ('customer', 'supplier', 'contact', 'product', 'member')
	 * @param   int    $object_id id of the object to search
	 *
	 * @return  int                        number of occurrences
	 */
	function containsObject($type, $object_id )
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
		$sql.= " AND entity IN (".getEntity('category',1).")";

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
	 * Reconstruit l'arborescence des categories sous la forme d'un tableau
	 * Renvoi un tableau de tableau('id','id_mere',...) trie selon arbre et avec:
	 *                id = id de la categorie
	 *                id_mere = id de la categorie mere
	 *                id_children = tableau des id enfant
	 *                label = nom de la categorie
	 *                fulllabel = nom avec chemin complet de la categorie
	 *                fullpath = chemin complet compose des id
	 *
	 * @param   string $type        Type of categories ('customer', 'supplier', 'contact', 'product', 'member').
	 *                              Old mode (0, 1, 2, ...) is deprecated.
	 * @param   int    $markafterid Removed all categories including the leaf $markafterid in category tree.
	 *
	 * @return  array               Array of categories. this->cats and this->motherof are set.
	 */
	function get_full_arbo($type,$markafterid=0)
	{
	    global $conf, $langs;

		// For backward compatibility
		if (is_numeric($type))
		{
			// We want to reverse lookup
			$map_type = array_flip($this->MAP_ID);
			$type = $map_type[$type];
			dol_syslog( get_class( $this ) . "::get_full_arbo(): numeric types are deprecated, please use string instead", LOG_WARNING);
		}

		$this->cats = array();

		// Init this->motherof that is array(id_son=>id_parent, ...)
		$this->load_motherof();
		$current_lang = $langs->getDefaultLang();

		// Init $this->cats array
		$sql = "SELECT DISTINCT c.rowid, c.label, c.description, c.color, c.fk_parent";	// Distinct reduce pb with old tables with duplicates
		if (! empty($conf->global->MAIN_MULTILANGS)) $sql.= ", t.label as label_trans, t.description as description_trans";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c";
		if (! empty($conf->global->MAIN_MULTILANGS)) $sql.= " LEFT  JOIN ".MAIN_DB_PREFIX."categorie_lang as t ON t.fk_category=c.rowid AND t.lang='".$current_lang."'";
		$sql .= " WHERE c.entity IN (" . getEntity( 'category', 1 ) . ")";
		$sql .= " AND c.type = " . $this->MAP_ID[$type];

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
	 *	Affiche contenu de $this->cats
	 *
	 *	@return	void
	 */
	function debug_cats()
	{
		// Affiche $this->cats
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
	 * 	Retourne toutes les categories
	 *
	 *	@param	int			$type		Type of category
	 *	@param	boolean		$parent		Just parent categories if true
	 *	@return	array					Tableau d'objet Categorie
	 */
	function get_all_categories($type=null, $parent=false)
	{
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE entity IN (".getEntity('category',1).")";
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
	 * 	Retourne le nombre total de categories
	 *
	 *	@return		int		Nombre de categories
	 *	@deprecated function not used ?
	 */
	function get_nb_categories()
	{
		$sql = "SELECT count(rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE entity IN (".getEntity('category',1).")";
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
		/* We have to select any rowid from llx_categorie which category's mother and label
		 * are equals to those of the calling category
		 */
		$sql = "SELECT c.rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c ";
		$sql.= " WHERE c.entity IN (".getEntity('category',1).")";
		$sql.= " AND c.type = ".$this->type;
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
					dol_syslog(get_class($this)."::already_exists category with name=".$this->label." exist rowid=".$obj[0]." current_id=".$this->id, LOG_DEBUG);
					return 1;
				}
			}
			dol_syslog(get_class($this)."::already_exists no category with same name=".$this->label." rowid=".$obj[0]." current_id=".$this->id, LOG_DEBUG);
			return 0;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *	Retourne les categories de premier niveau (qui ne sont pas filles)
	 *
	 *	@param		int		$type		Type of category
	 *	@return		array
	 */
	function get_main_categories($type=null)
	{
		return $this->get_all_categories($type, true);
	}

	/**
	 * Retourne les chemin de la categorie, avec les noms des categories
	 * separes par $sep (" >> " par defaut)
	 *
	 * @param	string	$sep	Separator
	 * @param	string	$url	Url
	 * @return	array
	 */
	function print_all_ways($sep = " &gt;&gt; ", $url='')
	{
		$ways = array();

		foreach ($this->get_all_ways() as $way)
		{
			$w = array();
			foreach ($way as $cat)
			{
				if ($url == '')
				{
					$w[] = "<a href='".DOL_URL_ROOT."/categories/viewcat.php?id=".$cat->id."&amp;type=".$cat->type."'>".$cat->label."</a>";
				}
				else
				{
					$w[] = "<a href='".DOL_URL_ROOT."/$url?catid=".$cat->id."'>".$cat->label."</a>";
				}
			}
			$ways[] = implode($sep, $w);
		}

		return $ways;
	}


	/**
	 *	Retourne un tableau contenant la liste des categories meres
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
	 * 	Retourne dans un tableau tous les chemins possibles pour arriver a la categorie
	 * 	en partant des categories principales, representes par des tableaux de categories
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
	 * @param   int    $id   Id of element
	 * @param   string $type Type of category ('customer', 'supplier', 'contact', 'product', 'member'). Old mode
	 *                       (0, 1, 2, ...) is deprecated.
	 * @param   string $mode 'object'=Get array of fetched category instances, 'label'=Get array of category
	 *                       labels, 'id'= Get array of category IDs
	 *
	 * @return  mixed        Array of category objects or < 0 if KO
	 */
	function containing($id,$type,$mode='object')
	{
		$cats = array();

		// For backward compatibility
		if (is_numeric($type))
		{
			dol_syslog(__METHOD__ . ': using numeric value for parameter type is deprecated. Use string code instead.', LOG_WARNING);
			// We want to reverse lookup
			$map_type = array_flip($this->MAP_ID);
			$type = $map_type[$type];
		}

		$sql = "SELECT ct.fk_categorie, c.label, c.rowid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "categorie_" . $this->MAP_CAT_TABLE[$type] . " as ct, " . MAIN_DB_PREFIX . "categorie as c";
		$sql .= " WHERE ct.fk_categorie = c.rowid AND ct.fk_" . $this->MAP_CAT_FK[$type] . " = " . $id . " AND c.type = " . $this->MAP_ID[$type];
		$sql .= " AND c.entity IN (" . getEntity( 'category', 1 ) . ")";

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

			return $cats;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 * 	Retourne les categories dont l'id ou le nom correspond
	 * 	ajoute des wildcards au nom sauf si $exact = true
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
		if (is_numeric( $type )) {
			// We want to reverse lookup
			$map_type = array_flip( $this->MAP_ID );
			$type = $map_type;
			dol_syslog( get_class( $this ) . "::rechercher(): numeric types are deprecated, please use string instead",
				LOG_WARNING );
		}

		// Generation requete recherche
		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "categorie";
		$sql .= " WHERE type = " . $this->MAP_ID[$type];
		$sql .= " AND entity IN (" . getEntity( 'category', 1 ) . ")";
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

        $link = '<a href="'.DOL_URL_ROOT.'/categories/viewcat.php?id='.$this->id.'&type='.$this->type.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
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

		if (file_exists($dir))
		{
			$originImage = $dir . $file['name'];

			// Cree fichier en taille origine
			dol_move_uploaded_file($file['tmp_name'], $originImage, 1, 0, 0);

			if (file_exists($originImage))
			{
				// Cree fichier en taille vignette
				$this->add_thumb($originImage);
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
    			while (($file = readdir($handle)) != false)
    			{
    				if (dol_is_file($dir.$file) && preg_match('/(\.jpg|\.bmp|\.gif|\.png|\.tiff)$/i',$dir.$file))
    				{
    					$nbphoto++;
    					$photo = $file;

    					// On determine nom du fichier vignette
    					$photo_vignette='';
    					if (preg_match('/(\.jpg|\.bmp|\.gif|\.png|\.tiff)$/i',$photo,$regs))
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
		if (preg_match('/(\.jpg|\.bmp|\.gif|\.png|\.tiff)$/i',$filename,$regs))
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
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function setMultiLangs()
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
	                $sql2.= " WHERE fk_category=".$this->id." AND lang='".$key."'";
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
	                $sql2.= " WHERE fk_category=".$this->id." AND lang='".$key."'";
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

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}
}
