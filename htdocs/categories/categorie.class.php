<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2005      Davoleau Brice       <brice.davoleau@gmail.com>
 * Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2007 Regis Houssin        <regis@dolibarr.fr>
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
	    \file       htdocs/categories/categorie.class.php
        \ingroup    categorie
		\brief      Fichier de la classe des categorie
		\version	$Id$
*/

require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/fournisseur.class.php");


/**
        \class      Categorie
		\brief      Classe permettant la gestion des categories
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
	var $type;					// 0=Produit, 1=Fournisseur, 2=Tiers

	var $cats=array();			// Tableau en memoire des categories
	var $motherof = array();	// Tableau des correspondances id_fille -> id_mere


	/**
	* Constructeur
	* db : accès base de données
	* id : id de la catégorie
	*/
	function Categorie($db, $id=-1)
	{
		$this->db = $db;
		$this->id = $id;

		if ($id != -1) $this->fetch ($this->id);
	}

	/**
	* Charge la catégorie
	* id : id de la catégorie à charger
	*/
	function fetch($id)
	{
		$sql = "SELECT rowid, label, description, visible, type";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE rowid = ".$id;

		dolibarr_syslog("Categorie::fetch sql=".$sql);
		$resql  = $this->db->query ($sql);
		if ($resql)
		{
			$res = $this->db->fetch_array($resql);

			$this->id		   = $res['rowid'];
			$this->label	   = $res['label'];
			$this->description = $res['description'];
			$this->visible     = $res['visible'];
			$this->type        = $res['type'];

			$this->db->free($resql);
		}
		else
		{
			dolibarr_print_error ($this->db);
			return -1;
		}

		$sql = "SELECT fk_categorie_mere";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie_association";
		$sql.= " WHERE fk_categorie_fille = '".$id."'";

		dolibarr_syslog("Categorie::fetch sql=".$sql);
		$resql  = $this->db->query ($sql);
		if ($resql)
		{
			$res = $this->db->fetch_array($resql);
			$this->id_mere = $res['fk_categorie_mere'];
			return $this->id;
		}
		else
		{
			dolibarr_print_error ($this->db);
			return -1;
		}
	}

	/**
	* Ajoute la catégorie dans la base de données
	* retour : -1 : erreur SQL
	*          -2 : nouvel ID inconnu
	*          -3 : catégorie invalide
	*/
	function create()
	{
		global $langs;
		$langs->load('categories');
		
		if ($this->already_exists ())
		{
			$this->error=$langs->trans("ImpossibleAddCat");
			$this->error.=" : ".$langs->trans("CategoryExistsAtSameLevel");
			return -1;
		}

		$sql  = "INSERT INTO ".MAIN_DB_PREFIX."categorie (label, description, fk_soc, visible, type) ";
		$sql .= "VALUES ('".addslashes($this->label)."', '".addslashes($this->description)."','".$this->socid."','".$this->visible."',".$this->type.")";


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
				return $id;
			}
			else
			{
				return -2;
			}
		}
		else
		{
			dolibarr_print_error ($this->db);
			return -1;
		}
	}

	/**
	* Mise à jour de la catégorie
	* retour :  1 : OK
	*          -1 : erreur SQL
	*          -2 : catégorie invalide
	*/
	function update()
	{
		// Clean parameters
		$this->label=trim($this->label);
		$this->description=trim($this->description);
		
		
		$this->db->begin();

		$sql = 'delete from '.MAIN_DB_PREFIX.'categorie_association';
		$sql .= ' WHERE fk_categorie_fille = "'.$this->id.'"';

		dolibarr_syslog("Categorie::update sql=".$sql);
		if (! $this->db->query($sql))
		{
			$this->db->rollback();
			dolibarr_print_error($this->db);
			return -1;
		}

		if($this->id_mere !="" && $this->id_mere!=$this->id)
		{
			$sql = 'insert into '.MAIN_DB_PREFIX.'categorie_association(fk_categorie_mere,fk_categorie_fille)';
			$sql .= ' VALUES ("'.$this->id_mere.'","'.$this->id.'")';
			
			dolibarr_syslog("Categorie::update sql=".$sql);
			if (! $this->db->query($sql))
			{
				$this->db->rollback();
				dolibarr_print_error($this->db);
				return -1;
			}
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."categorie";
		$sql.= " SET label = '".addslashes($this->label)."'";
		if ($this->description)
		{
			$sql .= ", description = '".addslashes($this->description)."'";
		}
		$sql .= ", visible = '".$this->visible."'";
		$sql .= " WHERE rowid = ".$this->id;

		dolibarr_syslog("Categorie::update sql=".$sql);
		if ($this->db->query($sql))
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			dolibarr_print_error($this->db);
			return -1;
		}
	}

	/**
	* Supprime la catégorie
	* Les produits et sous-catégories deviennent orphelins
	* si $all = false, et sont (seront :) supprimés sinon
	*/
	function remove ($all = false)
	{

		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_product ";
		$sql .= "WHERE fk_categorie = ".$this->id;

		if (!$this->db->query($sql))
		{
			dolibarr_print_error($this->db);
			return -1;
		}

		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_association ";
		$sql .= "WHERE fk_categorie_mere  = ".$this->id;
		$sql .= "   OR fk_categorie_fille = ".$this->id;

		if (!$this->db->query($sql))
		{
			dolibarr_print_error($this->db);
			return -1;
		}

		$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie ";
		$sql .= "WHERE rowid = ".$this->id;

		if (!$this->db->query($sql))
		{
			dolibarr_print_error($this->db);
			return -1;
		}
		else
		{
			return 1;
		}

	}


	/**
	* Ajout d'une sous-catégorie
	* $fille : objet catégorie
	* retour :  1 : OK
	*          -2 : $fille est déjà dans la famille de $this
	*          -3 : catégorie ($this ou $fille) invalide
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
			dolibarr_print_error($this->db);
			return -1;
		}
	}

	/**
	* Suppression d'une sous-catégorie (seulement "désassociation")
	* $fille : objet catégorie
	* retour :  1 : OK
	*          -3 : catégorie ($this ou $fille) invalide
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
	* Suppresion d'un produit de la catégorie
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
	* 	\brief	Retourne les produits de la catégorie
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

		dolibarr_syslog("Categorie::get_type sql=".$sql);
		$res  = $this->db->query($sql);
		if ($res)
		{
			while ($rec = $this->db->fetch_array ($res))
			{
				$obj = new $class ($this->db, $rec['fk_'.$field]);
				$obj->fetch ($obj->id);
				$objs[] = $obj;
			}
			return $objs;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			dolibarr_syslog("Categorie::get_type ".$this->error, LOG_ERR);
			return -1;
		}
	}
	
		


	/**
	* Retourne les filles de la catégorie
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
			dolibarr_print_error ($this->db);
			return -1;
		}
	}


	/**
	* retourne la description d'une catégorie
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
	* La catégorie $fille est-elle une fille de cette catégorie ?
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
	* 	\brief		Reconstruit l'arborescence des catégories sous la forme d'un tableau
	*				Renvoi un tableau de tableau('id','id_mere',...) trié selon
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
		// Charge tableau des meres
		$sql = "SELECT fk_categorie_mere as id_mere, fk_categorie_fille as id_fille";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie_association";

		dolibarr_syslog("Categorie::get_full_arbo sql=".$sql);
		$resql = $this->db->query ($sql);
		if ($resql)
		{
			while ($obj= $this->db->fetch_object($resql))
			{
				$this->motherof[$obj->id_fille]=$obj->id_mere;
			}
		}
		else
		{
			dolibarr_print_error ($this->db);
			return -1;
		}

		// Charge tableau des categories
		$sql = "SELECT c.rowid, c.label as label, ca.fk_categorie_fille as rowid_fille";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_association as ca";
		$sql.= " ON c.rowid=ca.fk_categorie_mere";
		$sql.= " WHERE c.type = ".$type;
		$sql.= " ORDER BY c.label, c.rowid";

		dolibarr_syslog("Categorie::get_full_arbo sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->cats = array();
			$i=0;
			while ($obj = $this->db->fetch_object($resql))
			{
				
				$this->cats[$obj->rowid]['id'] = $obj->rowid;
				$this->cats[$obj->rowid]['id_mere'] = $this->motherof[$obj->rowid];
				$this->cats[$obj->rowid]['label'] = $obj->label;

				if ($obj->rowid_fille)
				{
					if (is_array($this->cats[$obj->rowid]['id_children']))
					{
						$newelempos=sizeof($this->cats[$obj->rowid]['id_children']);
						//print "this->cats[$i]['id_children'] est deja un tableau de $newelem elements<br>";
						$this->cats[$obj->rowid]['id_children'][$newelempos]=$obj->rowid_fille;
					}
					else
					{
						//print "this->cats[".$obj->rowid."]['id_children'] n'est pas encore un tableau<br>";
						$this->cats[$obj->rowid]['id_children']=array($obj->rowid_fille);
					}
				}				
				$i++;

			}
		}
		else
		{
			dolibarr_print_error ($this->db);
			return -1;
		}
		
		// On ajoute la propriete fullpath a tous les éléments
		foreach($this->cats as $key => $val)
		{
			if (isset($motherof[$key])) continue;	
			$this->build_path_from_id_categ($key,0);
		}
		
		$this->cats=dol_sort_array($this->cats, 'fulllabel', 'asc', true, false);

		//$this->debug_cats();
		
		return $this->cats;
	}

	/**
	*	\brief		Calcule les propriétés fullpath et fulllabel d'une categorie
	*				du tableau this->cats et de toutes ces enfants
	* 	\param		id_categ		id_categ entry to update
	* 	\param		protection		Deep counter to avoid infinite loop
	*/
	function build_path_from_id_categ($id_categ,$protection=0)
	{
		// Defini fullpath
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
		$this->cats[$id_categ]['level']=strlen(eregi_replace('[^_]','',$this->cats[$id_categ]['fullpath']));
		
		// Traite ces enfants
		$protection++;
		if ($protection > 20) return;	// On ne traite pas plus de 20 niveaux
		if (! is_array($this->cats[$id_categ]['id_children'])) return;
		foreach($this->cats[$id_categ]['id_children'] as $key => $val)
		{
			$this->build_path_from_id_categ($val,$protection);
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
	* 		\brief		Retourne toutes les catégories
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
			dolibarr_print_error ($this->db);
			return -1;
		}
	}
	
	/**
	* 	\brief		Retourne le nombre total de catégories
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
			dolibarr_print_error ($this->db);
			return -1;
		}
	}

	/**
	* 		Vérifie si une catégorie porte le label $label
	*/
	function already_exists()
	{
		$sql = "SELECT count(c.rowid)";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c, ".MAIN_DB_PREFIX."categorie_association as ca";
		$sql.= " WHERE c.label = '".addslashes($this -> label)."' AND type=".$this->type;
		if($this -> id_mere != "")
		{
			$sql.= " AND c.rowid = ca.fk_categorie_fille";
			$sql.= " AND ca.fk_categorie_mere = '".$this -> id_mere."'";
		}

		$res  = $this->db->query ($sql);
		if($res)
		{
			$res  = $this->db->fetch_array ($res);
			if($res[0] > 0)
			return true;
			else
			return false;
		}
		else
		{
			dolibarr_print_error ($this->db);
			return -1;
		}

	}

	/**
	* 		\brief		Retourne les catégories de premier niveau (qui ne sont pas filles)
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
	* Retourne les chemin de la catégorie, avec les noms des catégories
	* séparés par $sep (" >> " par défaut)
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
	* get_primary_way() affiche le chemin le plus court pour se rendre à un produit
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
	* print_primary_way() affiche le chemin le plus court pour se rendre à un produit
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
	* Retourne un tableau contenant la liste des catégories mères
	*/
	function get_meres ()
	{
		$meres = array ();

		$sql  = "SELECT fk_categorie_mere FROM ".MAIN_DB_PREFIX."categorie_association ";
		$sql .= "WHERE fk_categorie_fille = ".$this->id;

		$res  = $this->db->query ($sql);

		while ($cat = $this->db->fetch_array ($res))
		{
			$meres[] = new Categorie ($this->db, $cat['fk_categorie_mere']);
		}

		return $meres;
	}

	/**
	* Retourne dans un tableau tous les chemins possibles pour arriver à la catégorie
	* en partant des catégories principales, représentés par des tableaux de catégories
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
			dolibarr_print_error ($this->db);
			return -1;
		}
	}

	/**
	* Retourne les catégories contenant le produit $ref
	*/
	function containing_ref ($ref,$type)
	{
		$cats = array ();

		$sql = "SELECT c.fk_categorie, c.fk_".$type.", p.rowid, p.ref";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie_".$type." as c, ".MAIN_DB_PREFIX.$type." as p";
		$sql.= " WHERE  p.ref = '".$ref."' AND c.fk_".$type." = p.rowid";

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
			dolibarr_print_error ($this->db);
			return -1;
		}
	}
	
	
	/**
	* 	\brief	Vérifie le type de la catégorie
	* 
	*/
	function verify_type($id)
	{
		$sql  = "SELECT type FROM ".MAIN_DB_PREFIX."categorie ";
		$sql .= "WHERE rowid = ".$id." ";

		$result = $this->db->query ($sql);
		if ($result)
		{
			$obj = $this->db->fetch_object($result);

			return $obj->type;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			dolibarr_print_error('',$this->error);
			return -1;
		}
	}



	/**
	* 	\brief	Retourne les catégories dont l'id ou le nom correspond
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
			//dolibarr_syslog($this->error);
			dolibarr_print_error('',$this->error);
			return -1;
		}
	}
}
?>
