<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2005      Davoleau Brice       <brice.davoleau@gmail.com>
 * Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2012 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin	  	<patrick.raguin@gmail.com>
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

/**
 *	\file       htdocs/categories/class/categorie.class.php
 *	\ingroup    categorie
 *	\brief      File of class to manage categories
 */

require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.class.php");


/**
 *	\class      Categorie
 *	\brief      Class to manage categories
 */
class Categorie
{
	public $element='category';
	public $table_element='category';

	var $id;
	var $id_mere;
	var $label;
	var $description;
	var $socid;
	var $type;					// 0=Product, 1=Supplier, 2=Customer/Prospect, 3=Member
	var $parentId;

	var $cats=array();			// Tableau en memoire des categories
	var $motherof = array();	// Tableau des correspondances id_fille -> id_mere


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db     Database handler
	 *  @param		int			$id		Id of category to fetch during init
	 */
	function Categorie($db, $id=-1)
	{
		$this->db = $db;
		$this->id = $id;

		if ($id != -1) $this->fetch($this->id);
	}

	/**
	 * 	Load category into memory from database
	 *
	 * 	@param		int		$id		Id of category
	 * 	@return		int				<0 if KO, >0 if OK
	 */
	function fetch($id)
	{
		$sql = "SELECT rowid, label, description, fk_soc, visible, type";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE rowid = ".$id;

		dol_syslog("Categorie::fetch sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$res = $this->db->fetch_array($resql);

			$this->id		       = $res['rowid'];
			$this->label	     = $res['label'];
			$this->description = $res['description'];
			$this->socid       = $res['fk_soc'];
			$this->visible     = $res['visible'];
			$this->type        = $res['type'];

			$this->db->free($resql);
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}

		$sql = "SELECT fk_categorie_mere";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie_association";
		$sql.= " WHERE fk_categorie_fille = ".$id;

		dol_syslog("Categorie::fetch sql=".$sql);
		$resql  = $this->db->query($sql);
		if ($resql)
		{
			$res = $this->db->fetch_array($resql);
			$this->id_mere = $res['fk_categorie_mere'];
			$this->parentId = $res['fk_categorie_mere'] ? $res['fk_categorie_mere'] : 0;
			return $this->id;
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
		if (empty($this->visible)) $this->visible=0;
		$this->parentId = ($this->id_mere) != "" ? intval($this->id_mere) : 0;

		if ($this->already_exists())
		{
			$this->error=$langs->trans("ImpossibleAddCat");
			$this->error.=" : ".$langs->trans("CategoryExistsAtSameLevel");
			return -4;
		}
		
		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."categorie (label, description,";
		if ($conf->global->CATEGORY_ASSIGNED_TO_A_CUSTOMER)
		{
			$sql.= "fk_soc,";
		}
		$sql.= " visible,";
		$sql.= " type,";
		$sql.= " entity";
		//$sql.= ", fk_parent_id";
		$sql.= ")";
		$sql.= " VALUES ('".$this->db->escape($this->label)."', '".$this->db->escape($this->description)."',";
		if ($conf->global->CATEGORY_ASSIGNED_TO_A_CUSTOMER)
		{
			$sql.= ($this->socid != -1 ? $this->socid : 'null').",";
		}
		$sql.= "'".$this->visible."',".$this->type.",".$conf->entity;
		//$sql.= ",".$this->parentId;
		$sql.= ")";

		$res  = $this->db->query($sql);
		if ($res)
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."categorie");

			if ($id > 0)
			{
				$this->id = $id;
				if($this->id_mere != "")
				{
					if($this->add_fille() < 0)
					{
						$this->error=$langs->trans("ImpossibleAssociateCategory");
						$this->db->rollback();
						return -3;
					}
				}

				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
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
		$this->parentId = ($this->id_mere) != "" ? intval($this->id_mere) : 0;
		$this->visible = ($this->visible) != "" ? intval($this->visible) : 0;

		if ($this->already_exists())
		{
			$this->error=$langs->trans("ImpossibleUpdateCat");
			$this->error.=" : ".$langs->trans("CategoryExistsAtSameLevel");
			return -1;
		}

		$this->db->begin();

		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'categorie_association';
		$sql.= ' WHERE fk_categorie_fille = '.$this->id;

		dol_syslog("Categorie::update sql=".$sql);
		if (! $this->db->query($sql))
		{
			$this->db->rollback();
			dol_print_error($this->db);
			return -1;
		}

		if($this->id_mere !="" && $this->id_mere!=$this->id)
		{
			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'categorie_association(fk_categorie_mere,fk_categorie_fille)';
			$sql.= ' VALUES ('.$this->id_mere.', '.$this->id.')';

			dol_syslog("Categorie::update sql=".$sql);
			if (! $this->db->query($sql))
			{
				$this->db->rollback();
				dol_print_error($this->db);
				return -1;
			}
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."categorie";
		$sql.= " SET label = '".$this->db->escape($this->label)."'";
		if ($this->description)
		{
			$sql .= ", description = '".$this->db->escape($this->description)."'";
		}
		if ($conf->global->CATEGORY_ASSIGNED_TO_A_CUSTOMER)
		{
			$sql .= ", fk_soc = ".($this->socid != -1 ? $this->socid : 'null');
		}
		$sql .= ", visible = '".$this->visible."'";
		//$sql .= ", fk_parent_id = ".$this->parentId;
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog("Categorie::update sql=".$sql);
		if ($this->db->query($sql))
		{
			$this->db->commit();

			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
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

		dol_syslog("Categorie::remove");

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

		// Link childs to parent
		if (! $error)
		{
			$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_association";
			$sql .= " WHERE fk_categorie_mere  = ".$this->id;
			$sql .= " OR fk_categorie_fille = ".$this->id;
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
				include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
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
	 * 	Ajout d'une sous-categorie
	 *
	 * 	@return		int		 1 : OK
	 *          			-2 : $fille est deja dans la famille de $this
	 *          			-3 : categorie ($this ou $fille) invalide
	 */
	function add_fille()
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."categorie_association (fk_categorie_mere, fk_categorie_fille)";
		$sql.= " VALUES (".$this->id_mere.", ".$this->id.")";

		if ($this->db->query($sql))
		{
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Suppression d'une sous-categorie (seulement "desassociation")
	 *
	 * 	@param	Category	$fille		Objet category
	 *  @return	int						1 : OK
	 *          		   				-3 : categorie ($this ou $fille) invalide
	 */
	function del_fille($fille)
	{
		if (!$this->check() || !$fille->check())
		{
			return -3;
		}

		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_association";
		$sql .= " WHERE fk_categorie_mere = ".$this->id." and fk_categorie_fille = ".$fille->id;

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
	 * 	Link an object to the category
	 *
	 *	@param		Object	$obj	Object to link to category
	 * 	@param		string	$type	Type of category
	 * 	@return		int				1 : OK, -1 : erreur SQL, -2 : id non renseign, -3 : Already linked
	 */
	function add_type($obj,$type)
	{
		if ($this->id == -1)
		{
			return -2;
		}

		$sql  = "INSERT INTO ".MAIN_DB_PREFIX."categorie_".$type." (fk_categorie, fk_".($type=='fournisseur'?'societe':$type).")";
		$sql .= " VALUES (".$this->id.", ".$obj->id.")";

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
	 * 	@param	string	$field		Field name for select in table. Full field name will be fk_field.
	 * 	@param	string	$classname	PHP Class of object to store entity
	 * 	@param	string	$table		Table name for select in table. Full table name will be PREFIX_categorie_table.
	 *	@return	void
	 */
	function get_type($field,$classname,$table='')
	{
		$objs = array();

		// Clean parameters
		if (empty($table)) $table=$field;

		$sql = "SELECT fk_".$field." FROM ".MAIN_DB_PREFIX."categorie_".$table;
		$sql.= " WHERE fk_categorie = ".$this->id;

		dol_syslog("Categorie::get_type sql=".$sql);
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
			dol_syslog("Categorie::get_type ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 * Retourne les filles de la categorie
	 *
	 * @return	void
	 */
	function get_filles()
	{
		$sql  = "SELECT fk_categorie_fille FROM ".MAIN_DB_PREFIX."categorie_association ";
		$sql .= "WHERE fk_categorie_mere = ".$this->id;

		$res  = $this->db->query($sql);

		if ($res)
		{
			$cats = array ();
			while ($rec = $this->db->fetch_array($res))
			{
				$cat = new Categorie($this->db, $rec['fk_categorie_fille']);
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
	 * Return category description
	 *
	 * @param	int		$cate		Category id
	 * @return	string				Description
	 */
	function get_desc($cate)
	{
		$sql = "SELECT description FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE rowid = ".$cate;

		$res = $this->db->query($sql);
		$n   = $this->db->fetch_array($res);

		return($n[0]);
	}

	/**
	 * La categorie $fille est-elle une fille de cette categorie ?
	 *
	 * @param	Category	$fille		Object category
	 * @return	void
	 */
	function is_fille($fille)
	{
		$sql  = "SELECT count(fk_categorie_fille) FROM ".MAIN_DB_PREFIX."categorie_association ";
		$sql .= "WHERE fk_categorie_mere = ".$this->id." AND fk_categorie_fille = ".$fille->id;

		$res  = $this->db->query($sql);
		$n    = $this->db->fetch_array($res);

		return ($n[0] > 0);
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
     *  @param      int		$markafterid      Mark all categories after this leaf in category tree.
	 *	@return		array		      		  Array of categories
	 */
	function get_full_arbo($type,$markafterid=0)
	{
		$this->cats = array();

		// Charge tableau des meres
		$sql = "SELECT ca.fk_categorie_mere as id_mere, ca.fk_categorie_fille as id_fille";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie_association ca";
		$sql.= ", ".MAIN_DB_PREFIX."categorie as c";
		$sql.= " WHERE ca.fk_categorie_mere = c.rowid";
		$sql.= " AND c.entity IN (".getEntity('category',1).")";

		// Load array this->motherof
		dol_syslog("Categorie::get_full_arbo build motherof array sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->motherof[$obj->id_fille]=$obj->id_mere;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}

		// Init $this->cats array
		$sql = "SELECT DISTINCT c.rowid, c.label as label, ca.fk_categorie_fille as rowid_fille";	// Distinct reduce pb with old tables with duplicates
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_association as ca";
		$sql.= " ON c.rowid = ca.fk_categorie_mere";
		$sql.= " WHERE c.type = ".$type;
		$sql.= " AND c.entity IN (".getEntity('category',1).")";
		$sql.= " ORDER BY c.label, c.rowid";

		dol_syslog("Categorie::get_full_arbo get category list sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$i=0;
			while ($obj = $this->db->fetch_object($resql))
			{
				$this->cats[$obj->rowid]['id'] = $obj->rowid;
				if (isset($this->motherof[$obj->rowid])) $this->cats[$obj->rowid]['id_mere'] = $this->motherof[$obj->rowid];
				$this->cats[$obj->rowid]['label'] = $obj->label;

				if ($obj->rowid_fille)
				{
					$this->cats[$obj->rowid]['id_children'][]=$obj->rowid_fille;
				}
				$i++;

			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}

		// We add the fullpath property to each elements of first level (no parent exists)
		dol_syslog("Categorie::get_full_arbo call to build_path_from_id_categ", LOG_DEBUG);
		foreach($this->cats as $key => $val)
		{
			if (isset($this->motherof[$key])) continue;
			$this->build_path_from_id_categ($key,0);	// Process a branch from the root category key (this category has no parent)
		}

        // Exclude tree for $markafterid
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
                    //print "Categ discarded ".$this->cats[$key]['fullpath']."\n";
                    //$this->cats[$key]['marked']=1;
                    unset($this->cats[$key]);
                }
            }
        }

		dol_syslog("Categorie::get_full_arbo dol_sort_array", LOG_DEBUG);
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
	function build_path_from_id_categ($id_categ,$protection=0)
	{
		dol_syslog("Categorie::build_path_from_id_categ id_categ=".$id_categ." protection=".$protection, LOG_DEBUG);

		//if (! empty($this->cats[$id_categ]['fullpath']))
		//{
		// Already defined
		//	dol_syslog("Categorie::build_path_from_id_categ fullpath and fulllabel already defined", LOG_WARNING);
		//	return;
		//}

		// Define fullpath and fulllabel
		if (isset($this->cats[$id_categ]['id_mere']))
		{
			$this->cats[$id_categ]['fullpath'] =$this->cats[$this->cats[$id_categ]['id_mere']]['fullpath'];
			$this->cats[$id_categ]['fullpath'].='_'.$id_categ;
			$this->cats[$id_categ]['fulllabel'] =$this->cats[$this->cats[$id_categ]['id_mere']]['fulllabel'];
			$this->cats[$id_categ]['fulllabel'].=' >> '.$this->cats[$id_categ]['label'];
		}
		else
		{
			$this->cats[$id_categ]['fullpath']='_'.$id_categ;
			$this->cats[$id_categ]['fulllabel']=$this->cats[$id_categ]['label'];
		}
		// We count number of _ to have level
		$this->cats[$id_categ]['level']=dol_strlen(preg_replace('/[^_]/i','',$this->cats[$id_categ]['fullpath']));

		// Process all childs on several levels of this category
		$protection++;
		if ($protection > 10) return;	// On ne traite pas plus de 10 niveaux de profondeurs
		if (! is_array($this->cats[$id_categ]['id_children'])) return;
		foreach($this->cats[$id_categ]['id_children'] as $key => $idchild)
		{
			// Protection when a category has itself as a child (should not happen)
			if ($idchild == $id_categ)
			{
				dol_syslog("Categorie::build_path_from_id_categ bad couple (".$idchild.",".$id_categ.") in association table: An entry should not have itself has child", LOG_WARNING);
				continue;
			}

			$this->build_path_from_id_categ($idchild,$protection);
		}

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
			print ' mother: '.$this->cats[$key]['id_mere'];
			print ' children: '.(is_array($this->cats[$key]['id_children'])?join(',',$this->cats[$key]['id_children']):'');
			print ' fullpath: '.$this->cats[$key]['fullpath'];
			print ' fulllabel: '.$this->cats[$key]['fulllabel'];
			print "<br>\n";
		}
	}


	/**
	 * 	Retourne toutes les categories
	 *
	 *	@return		array		Tableau d'objet Categorie
	 */
	function get_all_categories ()
	{
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE entity IN (".getEntity('category',1).")";

		$res = $this->db->query($sql);
		if ($res)
		{
			$cats = array ();
			while ($record = $this->db->fetch_array($res))
			{
				$cat = new Categorie($this->db, $record['rowid']);
				$cats[$record['rowid']] = $cat;
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
		if($this->id_mere != "")					// mother_id defined
		{
			/* We have to select any rowid from llx_categorie which category's mother and label
			 * are equals to those of the calling category
			 */
			$sql = "SELECT c.rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c ";
			$sql.= " JOIN ".MAIN_DB_PREFIX."categorie_association as ca";
			$sql.= " ON c.rowid=ca.fk_categorie_fille";
			$sql.= " WHERE ca.fk_categorie_mere=".$this->id_mere;
			$sql.= " AND c.label='".$this->db->escape($this->label)."'";
			$sql.= " AND c.entity IN (".getEntity('category',1).")";
		}
		else 										// mother_id undefined (so it's root)
		{
			/* We have to select any rowid from llx_categorie which which category's type and label
			 * are equals to those of the calling category, AND which doesn't exist in categorie association
			 * as children (rowid != fk_categorie_fille)
			 */
			$sql = "SELECT c.rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c ";
			$sql.= " JOIN ".MAIN_DB_PREFIX."categorie_association as ca";
			$sql.= " ON c.rowid!=ca.fk_categorie_fille";
			$sql.= " WHERE c.type=".$this->type;
			$sql.= " AND c.label='".$this->db->escape($this->label)."'";
			$sql.= " AND c.entity IN (".getEntity('category',1).")";
		}
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
	 *	@return		void
	 */
	function get_main_categories()
	{
		$allcats = $this->get_all_categories();
		$maincats = array ();
		$filles   = array ();

		$sql = "SELECT fk_categorie_fille FROM ".MAIN_DB_PREFIX."categorie_association";
		$res = $this->db->query($sql);
		while ($res = $this->db->fetch_array($res))
		{
			$filles[] = $res['fk_categorie_fille'];
		}

		foreach ($allcats as $cat)
		{
			if (! in_array($cat->id, $filles))
			{
				$maincats[] = $cat;
			}
		}

		return $maincats;
	}

	/**
	 * Retourne les chemin de la categorie, avec les noms des categories
	 * separes par $sep (" >> " par defaut)
	 *
	 * @param	string	$sep	Separator
	 * @param	string	$url	Url
	 * @return	void
	 */
	function print_all_ways ($sep = " &gt;&gt; ", $url='')
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
	 */
	function get_primary_way($id, $type="")
	{
		$primary_way = Array("taille"=>-1,"chemin"=>Array());
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
	 */
	function print_primary_way($id, $sep= " &gt;&gt; ", $url="", $type="")
	{
		$primary_way = Array();
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
		$meres = array();

		$sql  = "SELECT fk_categorie_mere FROM ".MAIN_DB_PREFIX."categorie_association ";
		$sql .= "WHERE fk_categorie_fille = ".$this->id;

		$res  = $this->db->query($sql);

		if ($res)
		{
			while ($cat = $this->db->fetch_array($res))
			{
				$meres[] = new Categorie($this->db, $cat['fk_categorie_mere']);
			}
			return $meres;
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
	function get_all_ways ()
	{
		$ways = array ();

		foreach ($this->get_meres() as $mere)
		{
			foreach ($mere->get_all_ways() as $way)
			{
				$w   = $way;
				$w[] = $this;

				$ways[] = $w;
			}
		}

		if (count($ways) == 0)
		$ways[0][0] = $this;

		return $ways;
	}

	/**
	 * 		Return list of categories linked to element of type $type with id $typeid
	 *
	 * 		@param		int		$id			Id of element
	 * 		@param		int		$typeid		Type id of link (0,1,2,3...)
	 * 		@return		array				List of category objects
	 */
	function containing($id,$typeid)
	{
		$cats = array ();

		$table=''; $type='';
		if ($typeid == 0)  { $table='product'; $type='product'; }
		if ($typeid == 1)  { $table='societe'; $type='fournisseur'; }
		if ($typeid == 2)  { $table='societe'; $type='societe'; }
		if ($typeid == 3)  { $table='member'; $type='member'; }

		$sql = "SELECT ct.fk_categorie";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie_".$type." as ct";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON ct.fk_categorie = c.rowid";
		$sql.= " WHERE ct.fk_".$table." = ".$id." AND c.type = ".$typeid;
		$sql.= " AND c.entity IN (".getEntity('category',1).")";

		$res = $this->db->query($sql);
		if ($res)
		{
			while ($cat = $this->db->fetch_array($res))
			{
				$cats[] = new Categorie($this->db, $cat['fk_categorie']);
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
	 * 	@param		boolean		$exact		Ture or false
	 * 	@return		array		Array of category id
	 */
	function rechercher($id, $nom, $type, $exact = false)
	{
		$cats = array ();

		// Generation requete recherche
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE type = ".$type." ";
		$sql.= " AND entity IN (".getEntity('category',1).")";
		if ($nom)
		{
			if (! $exact)
			{
				$nom = '%'.str_replace('*', '%', $nom).'%';
			}
			$sql.= "AND label LIKE '".$nom."'";
		}
		if ($id)
		{
			$sql.="AND rowid = '".$id."'";
		}

		$res  = $this->db->query($sql);
		if ($res)
		{
			while ($id = $this->db->fetch_array($res))
			{
				$cats[] = new Categorie($this->db, $id['rowid']);
			}

			return $cats;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			dol_syslog("Categorie::rechercher ".$this->error, LOG_ERR);
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
		require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

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
		require_once(DOL_DOCUMENT_ROOT ."/core/lib/images.lib.php");

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
    				if (is_file($dir.$file))
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
        require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

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
        $this->id_mere=0;
        $this->label = 'SPECIMEN';
        $this->specimen=1;
        $this->description = 'This is a description';
        $this->socid = 1;
        $this->type = 0;
    }
}
?>
