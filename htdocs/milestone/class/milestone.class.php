<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
 *	\file       htdocs/milestone/milestone.class.php
 *	\ingroup    milestone
 *	\brief      Fichier de la classe des jalons
 *	\version	$Id$
 */


/**
 *	\class      Milestone
 *	\brief      Classe permettant la gestion des jalons
 */
class Milestone
{
	var $error;
	var $db;

	var $id;
	var $label;
	var $description;
	var $statut;
	var $fk_element;
	var $elementtype;

	var $cats=array();			// Tableau en memoire des categories


	/**
	 * 	Constructor
	 * 	@param	DB		acces base de donnees
	 * 	@param	id		milestone id
	 */
	function Milestone($DB)
	{
		$this->db = $DB;
	}

	/**
	 * 	Charge le jalon
	 * 	@param	id		id du jalon a charger
	 */
	function fetch($id)
	{
		$sql = "SELECT rowid, label, description, visible, type";
		$sql.= " FROM ".MAIN_DB_PREFIX."milestone";
		$sql.= " WHERE rowid = ".$id;

		dol_syslog("Milestone::fetch sql=".$sql);
		$resql  = $this->db->query ($sql);
		if ($resql)
		{
			$res = $this->db->fetch_array($resql);

			$this->id		   = $res['rowid'];
			$this->label	   = $res['label'];
			$this->description = $res['description'];
			$this->type        = $res['type'];

			$this->db->free($resql);
		}
		else
		{
			dol_print_error ($this->db);
			return -1;
		}
	}

	/**
	 *  Ajoute le jalon dans la base de donnees
	 * 	@return	int 	-1 : erreur SQL
	 *          		-2 : nouvel ID inconnu
	 *          		-3 : jalon invalide
	 */
	function create($user)
	{
		global $conf,$langs;
		
		$langs->load('milestone');

		// Clean parameters
		$this->label=trim($this->label);
		$this->description=trim($this->description);

		if ($this->already_exists())
		{
			$this->error = $langs->trans("ImpossibleAddMilestone");
			$this->error.=" : ".$langs->trans("MilestoneAlreadyExists");
			return -1;
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."milestone (";
		$sql.= "label";
		$sql.= ", description";
		$sql.= ", type";
		$sql.= ") VALUES (";
		$sql.= "'".addslashes($this->label)."'";
		$sql.= ", '".addslashes($this->description)."'";
		$sql.= ", ".$this->type;
		$sql.= ")";


		$res  = $this->db->query ($sql);
		if ($res)
		{
			$id = $this->db->last_insert_id (MAIN_DB_PREFIX."milestone");

			if ($id > 0)
			{
				$this->id = $id;

				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/core/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('MILESTONE_CREATE',$this,$user,$langs,$conf);
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
	 * 	Update milestone
	 * 	@return	int		 1 : OK
	 *          		-1 : SQL error
	 *          		-2 : invalid milestone
	 */
	function update($user)
	{
		global $conf;

		// Clean parameters
		$this->label=trim($this->label);
		$this->description=trim($this->description);

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."milestone SET";
		$sql.= " label = '".addslashes($this->label)."'";
		$sql.= ", description = '".addslashes($this->description)."'";
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog("Milestone::update sql=".$sql);
		if ($this->db->query($sql))
		{
			$this->db->commit();

			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/core/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('MILESTONE_MODIFY',$this,$user,$langs,$conf);
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
	 * 	Delete milestone
	 */
	function remove()
	{

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."element_milestone";
		$sql.= " WHERE fk_categorie = ".$this->id;

		if (!$this->db->query($sql))
		{
			dol_print_error($this->db);
			return -1;
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."milestone";
		$sql.= " WHERE rowid = ".$this->id;

		if (!$this->db->query($sql))
		{
			dol_print_error($this->db);
			return -1;
		}
		else
		{
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/core/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('MILESTONE_DELETE',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

			return 1;
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
	 * 	\brief	Return list of contents of a category
	 * 	\param	field	Field name for select in table. Full field name will be fk_field.
	 * 	\param	class	PHP Class of object to store entity
	 * 	\param	table	Table name for select in table. Full table name will be PREFIX_categorie_table.
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
	 * 		Return list of categories linked to element of type $type with id $typeid
	 * 		@param		id			Id of element
	 * 		@param		typeid		Type id of link (0,1,2,3...)
	 * 		@return		array		List of category objects
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
		$sql.= " WHERE  ct.fk_".$table." = ".$id." AND c.type = ".$typeid;

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

}
?>
