<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2005      Davoleau Brice       <brice.davoleau@gmail.com>
 * Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2008 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/categories/categorie.class.php
 *	\ingroup    categorie
 *	\brief      Fichier de la classe des categorie
 *	\version	$Id$
 */

require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/fournisseur.class.php");


/**
 *	\class      Categorie
 *	\brief      Classe permettant la gestion des categories
 */
class Categorie
{
	var $error;
	var $db;

	var $id;
	var $id_mere;
	var $label;
	var $description;
	var $socid;
	var $statut;
	var $type;					// 0=Produit, 1=Tiers fournisseur, 2=Tiers client/prospect

	var $cats=array();			// Tableau en memoire des categories
	var $motherof = array();	// Tableau des correspondances id_fille -> id_mere


	/**
	 * 	Constructor
	 * 	@param	DB		acces base de donnees
	 * 	@param	id		id de la categorie
	 */
	function Categorie($DB, $id=-1)
	{
		$this->db = $DB;
		$this->id = $id;

		if ($id != -1) $this->fetch ($this->id);
	}

	/**
	 * 	Charge la categorie
	 * 	@param	id		id de la categorie a charger
	 */
	function fetch($id)
	{
		$sql = "SELECT rowid, label, description, fk_soc, visible, type";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE rowid = ".$id;

		dol_syslog("Categorie::fetch sql=".$sql);
		$resql  = $this->db->query ($sql);
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
			dol_print_error ($this->db);
			return -1;
		}

		$sql = "SELECT fk_categorie_mere";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie_association";
		$sql.= " WHERE fk_categorie_fille = '".$id."'";

		dol_syslog("Categorie::fetch sql=".$sql);
		$resql  = $this->db->query ($sql);
		if ($resql)
		{
			$res = $this->db->fetch_array($resql);
			$this->id_mere = $res['fk_categorie_mere'];
			return $this->id;
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
	}

	/**
	 * Ajoute la categorie dans la base de donnees
	 * 	@return	int 	-1 : erreur SQL
	 *          		-2 : nouvel ID inconnu
	 *          		-3 : categorie invalide
	 */
	function create()
	{
		global $conf,$langs;
		$langs->load('categories');

		if ($this->already_exists())
		{
			$this->error=$langs->trans("ImpossibleAddCat");
			$this->error.=" : ".$langs->trans("CategoryExistsAtSameLevel");
			return -1;
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."categorie (label, description,";
		if ($conf->global->CATEGORY_ASSIGNED_TO_A_CUSTOMER)
		{
			$sql.= "fk_soc,";
		}
		$sql.=  "visible, type) ";
		$sql.= "VALUES ('".addslashes($this->label)."', '".addslashes($this->description)."',";
		if ($conf->global->CATEGORY_ASSIGNED_TO_A_CUSTOMER)
		{
			$sql.= ($this->socid != -1 ? $this->socid : 'null').",";
		}
		$sql.= "'".$this->visible."',".$this->type.")";


		$res  = $this->db->query ($sql);
		if ($res)
		{
			$id = $this->db->last_insert_id (MAIN_DB_PREFIX."categorie");

			if ($id > 0)
			{
				$this->id = $id;
				if($this->id_mere != "")
				{
					if($this->add_fille() < 0)
					{
						$this->error=$langs->trans("ImpossibleAssociateCategory");
						return -1;
					}
				}

				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('CATEGORY_CREATE',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// Fin appel triggers

				return $id;
			}
			else
			{
				return -2;
			}
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
	}

	/**
	 * 	Update category
	 * 	@return	int		 1 : OK
	 *          		-1 : SQL error
	 *          		-2 : invalid category
	 */
	function update()
	{
		global $conf;

		// Clean parameters
		$this->label=trim($this->label);
		$this->description=trim($this->description);

		$this->db->begin();

		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'categorie_association';
		$sql .= ' WHERE fk_categorie_fille = "'.$this->id.'"';

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
			$sql .= ' VALUES ("'.$this->id_mere.'","'.$this->id.'")';

			dol_syslog("Categorie::update sql=".$sql);
			if (! $this->db->query($sql))
			{
				$this->db->rollback();
				dol_print_error($this->db);
				return -1;
			}
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."categorie";
		$sql.= " SET label = '".addslashes($this->label)."'";
		if ($this->description)
		{
			$sql .= ", description = '".addslashes($this->description)."'";
		}
		if ($conf->global->CATEGORY_ASSIGNED_TO_A_CUSTOMER)
		{
			$sql .= ", fk_soc = ".($this->socid != -1 ? $this->socid : 'null');
		}
		$sql .= ", visible = '".$this->visible."'";
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog("Categorie::update sql=".$sql);
		if ($this->db->query($sql))
		{
			$this->db->commit();

			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('CATEGORY_UPDATE',$this,$user,$langs,$conf);
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
	 * 	Delete category
	 * 	Les produits et sous-categories deviennent orphelins
	 * 	si $all = false, et sont (seront :) supprimes sinon
	 */
	function remove ($all = false)
	{

		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_product";
		$sql .= " WHERE fk_categorie = ".$this->id;

		if (!$this->db->query($sql))
		{
			dol_print_error($this->db);
			return -1;
		}

		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_association";
		$sql .= " WHERE fk_categorie_mere  = ".$this->id;
		$sql .= " OR fk_categorie_fille = ".$this->id;

		if (!$this->db->query($sql))
		{
			dol_print_error($this->db);
			return -1;
		}

		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie";
		$sql .= " WHERE rowid = ".$this->id;

		if (!$this->db->query($sql))
		{
			dol_print_error($this->db);
			return -1;
		}
		else
		{
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('CATEGORY_DELETE',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

			return 1;
		}

	}


	/**
	 * 	Ajout d'une sous-categorie
	 * 	@param	$fille		objet categorie
	 * 	@return	int			 1 : OK
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
	 * 	@param	$fille		objet categorie
	 *  @return	int			1 : OK
	 *          		   -3 : categorie ($this ou $fille) invalide
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
	 * 	\brief			Link an object to the category
	 *	\param			obj		Object to link to category
	 * 	\param			type	Type of category
	 * 	\return			int		1 : OK, -1 : erreur SQL, -2 : id non renseign, -3 : Already linked
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
	 * Suppresion d'un produit de la categorie
	 * @param $prod est un objet de type produit
	 * retour :  1 : OK
	 *          -1 : erreur SQL
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
	 * 	\brief	Retourne les produits de la categorie
	 * 	\param	field	Field name for select in table. Full field name will be fk_field.
	 * 	\param	class	PHP Class of object to store entity
	 * 	\param	table	Table name for select in table. Full table name will be PREFIX_categorie_table.
	 */
	function get_type($field,$class,$table='')
	{
		$objs = array();

		// Clean parameters
		if (empty($table)) $table=$field;


		$sql  = "SELECT fk_".$field." FROM ".MAIN_DB_PREFIX."categorie_".$table;
		$sql .= " WHERE fk_categorie = ".$this->id;

		dol_syslog("Categorie::get_type sql=".$sql);
		$res  = $this->db->query($sql);
		if ($res)
		{
			while ($rec = $this->db->fetch_array ($res))
			{
				$obj = new $class ($this->db);
				$obj->fetch ($rec['fk_'.$field]);
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
	 */
	function get_filles ()
	{
		$sql  = "SELECT fk_categorie_fille FROM ".MAIN_DB_PREFIX."categorie_association ";
		$sql .= "WHERE fk_categorie_mere = ".$this->id;

		$res  = $this->db->query ($sql);

		if ($res)
		{
			$cats = array ();
			while ($rec = $this->db->fetch_array ($res))
			{
				$cat = new Categorie ($this->db, $rec['fk_categorie_fille']);
				$cats[] = $cat;
			}
			return $cats;
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
	}


	/**
	 * retourne la description d'une categorie
	 */
	function get_desc ($cate)
	{
		$sql  = "SELECT description FROM ".MAIN_DB_PREFIX."categorie ";
		$sql .= "WHERE rowid = '".$cate."'";

		$res  = $this->db->query ($sql);
		$n    = $this->db->fetch_array ($res);

		return ($n[0]);
	}

	/**
	 * La categorie $fille est-elle une fille de cette categorie ?
	 */
	function is_fille ($fille)
	{
		$sql  = "SELECT count(fk_categorie_fille) FROM ".MAIN_DB_PREFIX."categorie_association ";
		$sql .= "WHERE fk_categorie_mere = ".$this->id." AND fk_categorie_fille = ".$fille->id;

		$res  = $this->db->query ($sql);

		$n    = $this->db->fetch_array ($res);

		return ($n[0] > 0);
	}


	/**
	 * 	\brief		Reconstruit l'arborescence des categories sous la forme d'un tableau
	 *				Renvoi un tableau de tableau('id','id_mere',...) trie selon
	 *				arbre et avec:
	 *				id = id de la categorie
	 *				id_mere = id de la categorie mere
	 *				id_children = tableau des id enfant
	 *				label = nom de la categorie
	 *				fulllabel = nom avec chemin complet de la categorie
	 *				fullpath = chemin complet compose des id
	 *	\param    	type		Type de categories (0=produit, 1=fournisseur, 2=client)
	 *	\return		array		Tableau de array
	 */
	function get_full_arbo($type)
	{
		$this->cats = array();

		// Charge tableau des meres
		$sql = "SELECT fk_categorie_mere as id_mere, fk_categorie_fille as id_fille";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie_association";

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
			dol_print_error ($this->db);
			return -1;
		}

		// Init $this->cats array
		$sql = "SELECT DISTINCT c.rowid, c.label as label, ca.fk_categorie_fille as rowid_fille";	// Distinct reduce pb with old tables with duplicates
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_association as ca";
		$sql.= " ON c.rowid=ca.fk_categorie_mere";
		$sql.= " WHERE c.type = ".$type;
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
			dol_print_error ($this->db);
			return -1;
		}

		// We add the fulpath property to each elements of first level (no parent exists)
		dol_syslog("Categorie::get_full_arbo call to build_path_from_id_categ", LOG_DEBUG);
		foreach($this->cats as $key => $val)
		{
			if (isset($this->motherof[$key])) continue;
			$this->build_path_from_id_categ($key,0);	// Process a path of a root category (no parent exists)
		}

		dol_syslog("Categorie::get_full_arbo dol_sort_array", LOG_DEBUG);
		$this->cats=dol_sort_array($this->cats, 'fulllabel', 'asc', true, false);

		//$this->debug_cats();

		return $this->cats;
	}

	/**
	 *	\brief		For category id_categ and its child available in this->cats, define property fullpath and fulllabel
	 * 	\param		id_categ		id_categ entry to update
	 * 	\param		protection		Deep counter to avoid infinite loop
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
		$this->cats[$id_categ]['level']=strlen(preg_replace('/[^_]/i','',$this->cats[$id_categ]['fullpath']));

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
	 *	\brief		Affiche contenu de $this->cats
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
	 * 		\brief		Retourne toutes les categories
	 *		\return		array		Tableau d'objet Categorie
	 */
	function get_all_categories ()
	{
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."categorie";

		$res = $this->db->query ($sql);
		if ($res)
		{
			$cats = array ();
			while ($record = $this->db->fetch_array ($res))
			{
				$cat = new Categorie ($this->db, $record['rowid']);
				$cats[$record['rowid']] = $cat;
			}
			return $cats;
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
	}

	/**
	 * 	\brief		Retourne le nombre total de categories
	 *	\return		int		Nombre de categories
	 */
	function get_nb_categories ()
	{
		$sql = "SELECT count(rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
		$res = $this->db->query ($sql);
		if ($res)
		{
			$res = $this->db->fetch_array($res);
			return $res[0];
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
	}

	/**
	 * 	\brief		Check if no category with same label already exists
	 * 	\return		boolean		1 if already exist, 0 otherwise, -1 if error
	 */
	function already_exists()
	{
		$sql = "SELECT count(c.rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c, ".MAIN_DB_PREFIX."categorie_association as ca";
		$sql.= " WHERE c.label = '".addslashes($this -> label)."' AND type=".$this->type;
		dol_syslog("Categorie::already_exists sql=".$sql);
		$res  = $this->db->query($sql);
		if ($res)
		{
			$obj = $this->db->fetch_array($res);
			if($obj[0] > 0) return 1;
			else return 0;
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
	}

	/**
	 * 		\brief		Retourne les categories de premier niveau (qui ne sont pas filles)
	 */
	function get_main_categories ()
	{
		$allcats = $this->get_all_categories ();
		$maincats = array ();
		$filles   = array ();

		$sql = "SELECT fk_categorie_fille FROM ".MAIN_DB_PREFIX."categorie_association";
		$res = $this->db->query ($sql);
		while ($res = $this->db->fetch_array ($res))
		{
			$filles[] = $res['fk_categorie_fille'];
		}

		foreach ($allcats as $cat)
		{
			if (!in_array ($cat->id, $filles))
			{
				$maincats[] = $cat;
			}
			else
			{
			}
		}

		return $maincats;
	}

	/**
	 * Retourne les chemin de la categorie, avec les noms des categories
	 * separes par $sep (" >> " par defaut)
	 */
	function print_all_ways ($sep = " &gt;&gt; ", $url='')
	{
		$ways = array ();

		foreach ($this->get_all_ways () as $way)
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
			$ways[] = implode ($sep, $w);
		}

		return $ways;
	}


	/**
	 * get_primary_way() affiche le chemin le plus court pour se rendre a un produit
	 */
	function get_primary_way($id, $type="")
	{
		$primary_way = Array("taille"=>-1,"chemin"=>Array());
		$meres = $this->containing($id,$type);
		foreach ($meres as $mere)
		{
			foreach ($mere->get_all_ways() as $way)
			{
				if(sizeof($way)<$primary_way["taille"] || $primary_way["taille"]<0)
				{
					$primary_way["taille"] = sizeOf($way);
					$primary_way["chemin"] = $way;
				}
			}
		}
		return $primary_way["chemin"];

	}

	/**
	 * print_primary_way() affiche le chemin le plus court pour se rendre a un produit
	 */
	function print_primary_way($id, $sep= " &gt;&gt; ", $url, $type="")
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
	 * Retourne un tableau contenant la liste des categories meres
	 */
	function get_meres ()
	{
		$meres = array ();

		$sql  = "SELECT fk_categorie_mere FROM ".MAIN_DB_PREFIX."categorie_association ";
		$sql .= "WHERE fk_categorie_fille = ".$this->id;

		$res  = $this->db->query ($sql);

		if ($res)
		{
			while ($cat = $this->db->fetch_array ($res))
			{
				$meres[] = new Categorie ($this->db, $cat['fk_categorie_mere']);
			}
			return $meres;
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
	}

	/**
	 * Retourne dans un tableau tous les chemins possibles pour arriver a la categorie
	 * en partant des categories principales, representes par des tableaux de categories
	 */
	function get_all_ways ()
	{
		$ways = array ();

		foreach ($this->get_meres () as $mere)
		{
			foreach ($mere->get_all_ways () as $way)
			{
				$w   = $way;
				$w[] = $this;

				$ways[] = $w;
			}
		}

		if (sizeof ($ways) == 0)
		$ways[0][0] = $this;

		return $ways;
	}

	/**
	 * 		Return list of categories linked to element of type $type with id $typeid
	 * 		@param		id			Id of element
	 * 		@param		type		Type of link ('customer','fournisseur','societe'...)
	 * 		@param		typeid		Type id of link (0,1,2...)
	 * 		@return		array		List of category objects
	 */
	function containing ($id,$type,$typeid)
	{
		$cats = array ();

		$sql = "SELECT ct.fk_categorie";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie_".$type." as ct";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON ct.fk_categorie = c.rowid";
		$sql.= " WHERE  ct.fk_".($type=='fournisseur'?'societe':$type)." = ".$id." AND c.type = ".$typeid;

		$res = $this->db->query ($sql);
		if ($res)
		{
			while ($cat = $this->db->fetch_array ($res))
			{
				$cats[] = new Categorie ($this->db, $cat['fk_categorie']);
			}

			return $cats;
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
	}


	/**
	 * 	\brief	Retourne les categories dont l'id ou le nom correspond
	 * 			ajoute des wildcards au nom sauf si $exact = true
	 */
	function rechercher($id, $nom, $type, $exact = false)
	{
		$cats = array ();

		// Generation requete recherche
		$sql  = "SELECT rowid FROM ".MAIN_DB_PREFIX."categorie ";
		$sql .= "WHERE type = ".$type." ";
		if ($nom)
		{
			if (! $exact)
			{
				$nom = '%'.str_replace ('*', '%', $nom).'%';
			}
			$sql.= "AND label LIKE '".$nom."'";
		}
		if ($id)
		{
			$sql.="AND rowid = '".$id."'";
		}

		$res  = $this->db->query ($sql);
		if ($res)
		{
			while ($id = $this->db->fetch_array ($res))
			{
				$cats[] = new Categorie ($this->db, $id['rowid']);
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
	 *	\brief      Return name and link of category (with picto)
	 *	\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *	\param		option			Sur quoi pointe le lien ('', 'xyz')
	 * 	\param		maxlength		Max length of text
	 *	\return		string			Chaine avec URL
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
	 *    \brief      Deplace fichier uploade sous le nom $files dans le repertoire sdir
	 *    \param      sdir        Repertoire destination finale
	 *    \param      $file       Nom du fichier uploade
	 *    \param      maxWidth    Largeur maximum que dois faire la miniature (160 par defaut)
	 *    \param      maxHeight   Hauteur maximum que dois faire la miniature (120 par defaut)
	 */
	function add_photo($sdir, $file, $maxWidth = 160, $maxHeight = 120)
	{
		$dir = $sdir .'/'. get_exdir($this->id,2) . $this->id ."/";
		$dir .= "photos/";

		if (! file_exists($dir))
		{
			create_exdir($dir);
		}

		if (file_exists($dir))
		{
			$originImage = $dir . $file['name'];

			// Cree fichier en taille origine
			$result=dol_move_uploaded_file($file['tmp_name'], $originImage, 1);

			if (file_exists($originImage))
			{
				// Cree fichier en taille vignette
				$this->add_thumb($originImage,$maxWidth,$maxHeight);
			}
		}
	}

	/**
	 *    \brief      Build thumb
	 *    \param      sdir           Repertoire destination finale
	 *    \param      file           Chemin du fichier d'origine
	 *    \param      maxWidth       Largeur maximum que dois faire la miniature (160 par defaut)
	 *    \param      maxHeight      Hauteur maximum que dois faire la miniature (120 par defaut)
	 */
	function add_thumb($file, $maxWidth = 160, $maxHeight = 120)
	{
		require_once(DOL_DOCUMENT_ROOT ."/lib/images.lib.php");

		if (file_exists($file))
		{
			vignette($file,$maxWidth,$maxHeight);
		}
	}


	/**
	 *    \brief      Retourne tableau de toutes les photos de la categorie
	 *    \param      dir         Repertoire a scanner
	 *    \param      nbmax       Nombre maximum de photos (0=pas de max)
	 *    \return     array       Tableau de photos
	 */
	function liste_photos($dir,$nbmax=0)
	{
		$nbphoto=0;
		$tabobj=array();

		$dirthumb = $dir.'thumbs/';

		if (file_exists($dir))
		{
			$handle=opendir($dir);

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

		return $tabobj;
	}

	/**
	 *    \brief      Efface la photo de la categorie et sa vignette
	 *    \param      file        Chemin de l'image
	 */
	function delete_photo($file)
	{
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
	 *    \brief      Load size of image file
	 *    \param      file        Path to file
	 */
	function get_image_size($file)
	{
		$infoImg = getimagesize($file); // Recuperation des infos de l'image
		$this->imgWidth = $infoImg[0]; // Largeur de l'image
		$this->imgHeight = $infoImg[1]; // Hauteur de l'image
	}

}
?>
