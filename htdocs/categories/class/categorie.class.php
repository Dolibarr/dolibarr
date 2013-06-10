<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2005      Davoleau Brice       <brice.davoleau@gmail.com>
 * Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2006-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin	  	<patrick.raguin@gmail.com>
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

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';


/**
 *	Class to manage categories
 */
class Categorie
{
	public $element='category';
	public $table_element='category';

	var $id;
	var $fk_parent;
	var $label;
	var $description;
	var $socid;
	var $type;					// 0=Product, 1=Supplier, 2=Customer/Prospect, 3=Member
	var $import_key;

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

		$sql = "SELECT rowid, fk_parent, entity, label, description, fk_soc, visible, type";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
		if ($id)
		{
			$sql.= " WHERE rowid = '".$id."'";
		}

		else
		{
			if ($label) $sql.= " WHERE label = '".$this->db->escape($label)."' AND entity=".$conf->entity;;
		}

		dol_syslog(get_class($this)."::fetch sql=".$sql);
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
				$this->socid		= $res['fk_soc'];
				$this->visible		= $res['visible'];
				$this->type			= $res['type'];
				$this->entity		= $res['entity'];

				$this->db->free($resql);

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
	function create($user='')
	{
		global $conf,$langs;
		$langs->load('categories');

		$error=0;

		// Clean parameters
		$this->label = trim($this->label);
		$this->description = trim($this->description);
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

		dol_syslog(get_class($this).'::create sql='.$sql);
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."categorie (";
		$sql.= "fk_parent,";
		$sql.= " label,";
		$sql.= " description,";
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
		if (! empty($conf->global->CATEGORY_ASSIGNED_TO_A_CUSTOMER))
		{
			$sql.= ($this->socid != -1 ? $this->socid : 'null').",";
		}
		$sql.= "'".$this->visible."',";
		$sql.= $this->type.",";
		$sql.= (! empty($this->import_key)?"'".$this->db->escape($this->import_key)."'":'null').",";
		$sql.= $conf->entity;
		$sql.= ")";

		dol_syslog(get_class($this).'::create sql='.$sql);
		$res = $this->db->query($sql);
		if ($res)
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."categorie");

			if ($id > 0)
			{
				$this->id = $id;

				// Appel des triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('CATEGORY_CREATE',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// Fin appel triggers

				$this->db->commit();
				return $id;
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
            dol_syslog(get_class($this)."::create error ".$this->error." sql=".$sql, LOG_ERR);
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
		global $conf, $langs;

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
		$sql.= " description = '".$this->db->escape($this->description)."'";
		if (! empty($conf->global->CATEGORY_ASSIGNED_TO_A_CUSTOMER))
		{
			$sql .= ", fk_soc = ".($this->socid != -1 ? $this->socid : 'null');
		}
		$sql .= ", visible = '".$this->visible."'";
		$sql .= ", fk_parent = ".$this->fk_parent;
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update sql=".$sql);
		if ($this->db->query($sql))
		{
			$this->db->commit();

			// Appel des triggers
			include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('CATEGORY_MODIFY',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

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
	 *	@return	void
	 */
	function delete($user)
	{
		global $conf,$langs;

		$error=0;

		dol_syslog(get_class($this)."::remove");

		$this->db->begin();

		if (! $error)
		{
			$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_societe";
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
			$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_fournisseur";
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
			$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_product";
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
			$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_member";
			$sql .= " WHERE fk_categorie = ".$this->id;
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
				dol_syslog("Error sql=".$sql." ".$this->error, LOG_ERR);
				$error++;
			}
			else
			{
				// Appel des triggers
				include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('CATEGORY_DELETE',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; $this->error=join(',',$this->errors); }
				// Fin appel triggers
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
	 * 	Link an object to the category
	 *
	 *	@param		Object	$obj	Object to link to category
	 * 	@param		string	$type	Type of category (member, supplier, product, customer)
	 * 	@return		int				1 : OK, -1 : erreur SQL, -2 : id not defined, -3 : Already linked
	 */
	function add_type($obj,$type)
	{
		if ($this->id == -1) return -2;
		if ($type == 'company')     $type='societe';
		if ($type == 'fournisseur') $type='societe';

		$sql  = "INSERT INTO ".MAIN_DB_PREFIX."categorie_".$type." (fk_categorie, fk_".$type.")";
		$sql .= " VALUES (".$this->id.", ".$obj->id.")";

		dol_syslog(get_class($this).'::add_type sql='.$sql);
		if ($this->db->query($sql))
		{
			return 1;
		}
		else
		{
			if ($this->db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$this->error=$this->db->lasterrno();
				return -3;
			}
			else
			{
				$this->error=$this->db->error().' sql='.$sql;
			}
			return -1;
		}
	}

	/**
	 * Delete object from category
	 *
	 * @param 	Object	$obj	Object
	 * @param	string	$type	Type
	 * @return 	int				1 if OK, -1 if KO
	 */
	function del_type($obj,$type)
	{
		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_".$type;
		$sql .= " WHERE fk_categorie = ".$this->id;
		$sql .= " AND   fk_".($type=='fournisseur'?'societe':$type)."   = ".$obj->id;

		dol_syslog(get_class($this).'::del_type sql='.$sql);
		if ($this->db->query($sql))
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			return -1;
		}
	}

	/**
	 * 	Return list of contents of a category
	 *
	 * 	@param	string	$field				Field name for select in table. Full field name will be fk_field.
	 * 	@param	string	$classname			PHP Class of object to store entity
	 * 	@param	string	$category_table		Table name for select in table. Full table name will be PREFIX_categorie_table.
	 *	@param	string	$object_table		Table name for select in table. Full table name will be PREFIX_table.
	 *	@return	void
	 */
	function get_type($field,$classname,$category_table='',$object_table='')
	{
		$objs = array();

		// Clean parameters
		if (empty($category_table)) $category_table=$field;
		if (empty($object_table)) $object_table=$field;

		$sql = "SELECT c.fk_".$field;
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie_".$category_table." as c";
		$sql.= ", ".MAIN_DB_PREFIX.$object_table." as o";
		$sql.= " WHERE o.entity IN (".getEntity($field, 1).")";
		$sql.= " AND c.fk_categorie = ".$this->id;
		$sql.= " AND c.fk_".$field." = o.rowid";

		dol_syslog(get_class($this)."::get_type sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($rec = $this->db->fetch_array($resql))
			{
				$obj = new $classname($this->db);
				$obj->fetch($rec['fk_'.$field]);
				$objs[] = $obj;
			}
			return $objs;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			dol_syslog(get_class($this)."::get_type ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 * Return childs of a category
	 *
	 * @return	void
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
		$sql.= " AND entity = ".$conf->entity;

		dol_syslog(get_class($this)."::load_motherof sql=".$sql);
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
	 * 	Reconstruit l'arborescence des categories sous la forme d'un tableau
	 *	Renvoi un tableau de tableau('id','id_mere',...) trie selon arbre et avec:
	 *				id = id de la categorie
	 *				id_mere = id de la categorie mere
	 *				id_children = tableau des id enfant
	 *				label = nom de la categorie
	 *				fulllabel = nom avec chemin complet de la categorie
	 *				fullpath = chemin complet compose des id
	 *
	 *	@param      string	$type		      Type of categories (0=product, 1=suppliers, 2=customers, 3=members)
     *  @param      int		$markafterid      Removed all categories including the leaf $markafterid in category tree.
	 *	@return		array		      		  Array of categories. this->cats and this->motherof are set.
	 */
	function get_full_arbo($type,$markafterid=0)
	{
		$this->cats = array();

		// Init this->motherof that is array(id_son=>id_parent, ...)
		$this->load_motherof();

		// Init $this->cats array
		$sql = "SELECT DISTINCT c.rowid, c.label, c.description, c.fk_parent";	// Distinct reduce pb with old tables with duplicates
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c";
		$sql.= " WHERE c.entity IN (".getEntity('category',1).")";
		$sql.= " AND c.type = ".$type;

		dol_syslog(get_class($this)."::get_full_arbo get category list sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$i=0;
			while ($obj = $this->db->fetch_object($resql))
			{
				$this->cats[$obj->rowid]['rowid'] = $obj->rowid;
				$this->cats[$obj->rowid]['id'] = $obj->rowid;
				$this->cats[$obj->rowid]['fk_parent'] = $obj->fk_parent;
				$this->cats[$obj->rowid]['label'] = $obj->label;
				$this->cats[$obj->rowid]['description'] = $obj->description;
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
	 * 	@return		boolean		1 if already exist, 0 otherwise, -1 if error
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

		dol_syslog(get_class($this)."::already_exists sql=".$sql, LOG_DEBUG);
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
            dol_syslog(get_class($this)."::already_exists error ".$this->error." sql=".$sql, LOG_ERR);
			return -1;
		}
	}

	/**
	 *	Retourne les categories de premier niveau (qui ne sont pas filles)
	 *
	 *	@param		int		$type		Type of category
	 *	@return		void
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
	 * @return	void
	 */
	function print_all_ways($sep = " &gt;&gt; ", $url='')
	{
		$ways = array ();

		foreach ($this->get_all_ways() as $way)
		{
			$w = array ();
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
	 *	Affiche le chemin le plus court pour se rendre a un produit
	 *
	 *	@param	int		$id		Id of category
	 *	@param	string	$type	Type of category
	 *	@return	void
	 *	@deprecated function not used ?
	 */
	function get_primary_way($id, $type="")
	{
		$primary_way = array("taille" => -1, "chemin" => array());
		$meres = $this->containing($id,$type);
		foreach ($meres as $mere)
		{
			foreach ($mere->get_all_ways() as $way)
			{
				if(count($way) < $primary_way["taille"] || $primary_way["taille"] < 0)
				{
					$primary_way["taille"] = count($way);
					$primary_way["chemin"] = $way;
				}
			}
		}
		return $primary_way["chemin"];

	}

	/**
	 *	Affiche le chemin le plus court pour se rendre a un produit
	 *
	 *	@param	int		$id		Id of category
	 *	@param	string	$sep	Separator
	 *	@param	string	$url	Url
	 *	@param	string	$type	Type
	 *	@return	void
	 *	@deprecated function not used ?
	 */
	function print_primary_way($id, $sep= " &gt;&gt; ", $url="", $type="")
	{
		$primary_way = array();
		$way = $this->get_primary_way($id,$type);
		$w = array();
		foreach ($way as $cat)
		{
			if ($url == '')
			{
				$w[] = "<a href='".DOL_URL_ROOT."/categories/viewcat.php?id=".$cat->id."'>".$cat->label."</a>";
			}
			else
			{
				$w[] = "<a href='".DOL_URL_ROOT."/".$url."?catid=".$cat->id."'>".$cat->label."</a>";
			}
		}

		return implode($sep, $w);
	}

	/**
	 *	Retourne un tableau contenant la liste des categories meres
	 *
	 *	@return		void
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
	 *	@return		void
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
	 * 	Return list of categories linked to element of id $id and type $typeid
	 *
	 * 	@param		int		$id			Id of element
	 * 	@param		int		$typeid		Type of link (0 or 'product', 1 or 'supplier', 2 or 'customer', 3 or 'member', ...)
	 * 	@param		string	$mode		'object'=Get array of categories, 'label'=Get array of category labels
	 * 	@return		mixed				Array of category objects or < 0 if KO
	 */
	function containing($id,$typeid,$mode='object')
	{
		$cats = array();

		$table=''; $type='';
		if ($typeid == 0 || $typeid == 'product')         { $typeid=0; $table='product'; $type='product'; }
		else if ($typeid == 1 || $typeid == 'supplier')  { $typeid=1; $table='societe'; $type='fournisseur'; }
		else if ($typeid == 2 || $typeid == 'customer')  { $typeid=2; $table='societe'; $type='societe'; }
		else if ($typeid == 3 || $typeid == 'member')    { $typeid=3; $table='member';  $type='member'; }

		$sql = "SELECT ct.fk_categorie, c.label";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie_".$type." as ct, ".MAIN_DB_PREFIX."categorie as c";
		$sql.= " WHERE ct.fk_categorie = c.rowid AND ct.fk_".$table." = ".$id." AND c.type = ".$typeid;
		$sql.= " AND c.entity IN (".getEntity('category',1).")";

		dol_syslog(get_class($this).'::containing sql='.$sql);
		$res = $this->db->query($sql);
		if ($res)
		{
			while ($obj = $this->db->fetch_object($res))
			{
				if ($mode == 'label')
				{
					$cats[] = $obj->label;
				}
				else {
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
	 * 	@param		string		$type		Type
	 * 	@param		boolean		$exact		Exact string search (true/false)
	 * 	@param		boolean		$case		Case sensitive (true/false)
	 * 	@return		array					Array of category id
	 */
	function rechercher($id, $nom, $type, $exact = false, $case = false)
	{
		$cats = array();

		// Generation requete recherche
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE type = ".$type." ";
		$sql.= " AND entity IN (".getEntity('category',1).")";
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
			dol_syslog(get_class($this)."::rechercher ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *	Return name and link of category (with picto)
	 *
	 *	@param		int		$withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *	@param		string	$option			Sur quoi pointe le lien ('', 'xyz')
	 * 	@param		int		$maxlength		Max length of text
	 *	@return		string					Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='',$maxlength=0)
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/categories/viewcat.php?id='.$this->id.'&type='.$this->type.'">';
		$label=$langs->trans("ShowCategory").': '.$this->label;
		$lienfin='</a>';

		$picto='category';


		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.dol_trunc($this->ref,$maxlength).$lienfin;
		return $result;
	}


	/**
	 *  Deplace fichier uploade sous le nom $files dans le repertoire sdir
	 *
	 *  @param      string	$sdir       Repertoire destination finale
	 *  @param      string	$file		Nom du fichier uploade
	 *  @param      int		$maxWidth   Largeur maximum que dois faire la miniature (160 par defaut)
	 *  @param      int		$maxHeight  Hauteur maximum que dois faire la miniature (120 par defaut)
	 *	@return		void
	 */
	function add_photo($sdir, $file, $maxWidth = 160, $maxHeight = 120)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$dir = $sdir .'/'. get_exdir($this->id,2) . $this->id ."/";
		$dir .= "photos/";

		if (! file_exists($dir))
		{
			dol_mkdir($dir);
		}

		if (file_exists($dir))
		{
			$originImage = $dir . $file['name'];

			// Cree fichier en taille origine
			$result=dol_move_uploaded_file($file['tmp_name'], $originImage, 1, 0, 0);

			if (file_exists($originImage))
			{
				// Cree fichier en taille vignette
				$this->add_thumb($originImage,$maxWidth,$maxHeight);
			}
		}
	}

	/**
	 *  Build thumb
	 *
	 *  @param      string	$file           Chemin du fichier d'origine
	 *  @param      int		$maxWidth       Largeur maximum que dois faire la miniature (160 par defaut)
	 *  @param      int		$maxHeight      Hauteur maximum que dois faire la miniature (120 par defaut)
	 *	@return		void
	 */
	function add_thumb($file, $maxWidth = 160, $maxHeight = 120)
	{
		require_once DOL_DOCUMENT_ROOT .'/core/lib/images.lib.php';

		if (file_exists($file))
		{
			vignette($file,$maxWidth,$maxHeight);
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
    					if ($photo_vignette && is_file($dirthumb.$photo_vignette)) $obj['photo_vignette']=$photo_vignette;
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
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
     */
    function initAsSpecimen()
    {
        global $user,$langs,$conf;

        dol_syslog(get_class($this)."::initAsSpecimen");

        // Initialise parametres
        $this->id=0;
        $this->fk_parent=0;
        $this->label = 'SPECIMEN';
        $this->specimen=1;
        $this->description = 'This is a description';
        $this->socid = 1;
        $this->type = 0;
    }
}
?>
