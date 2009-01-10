<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
		\file       htdocs/project.class.php
		\ingroup    projet
		\brief      Fichier de la classe de gestion des projets
		\version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT ."/commonobject.class.php");

/**
		\class      Project
		\brief      Classe permettant la gestion des projets
*/
class Project extends CommonObject
{
	var $id;
	var $db;
	var $ref;
	var $title;
	var $socid;
	var $user_resp_id;

	/**
	*    \brief  Constructeur de la classe
	*    \param  DB          handler acc�s base de donn�es
	*/
	function Project($DB)
	{
		$this->db = $DB;
		$this->societe = new Societe($DB);
	}

	/*
	*    \brief      Cree un projet en base
	*    \param      user        Id utilisateur qui cree
	*    \return     int         <0 si ko, id du projet cree si ok
	*/
	function create($user)
	{
		// Check parameters
		if (! trim($this->ref))
		{
			$this->error='ErrorFieldsRequired';
			dolibarr_syslog("Project::Create error -1 ref null");
			return -1;
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."projet (ref, title, fk_soc, fk_user_creat, fk_user_resp, dateo, fk_statut)";
		$sql.= " VALUES ('".addslashes($this->ref)."', '".addslashes($this->title)."',";
		$sql.= " ".($this->socid > 0?$this->socid:"null").",";
		$sql.= " ".$user->id.",";
		$sql.= " ".$this->user_resp_id.", ".$this->db->idate(mktime()).", 0)";
		
		dolibarr_syslog("Project::create sql=".$sql,LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."projet");
			$result = $this->id;
		}
		else
		{
			dolibarr_syslog("Project::Create error -2");
			$this->error=$this->db->error();
			$result = -2;
		}

		return $result;
	}


	function update($user)
	{
		if (strlen(trim($this->ref)) > 0)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."projet";
			$sql.= " SET ref='".$this->ref."'";
			$sql.= ", title = '".$this->title."'";
			$sql.= ", fk_soc = ".($this->socid > 0?$this->socid:"null");
			$sql.= ", fk_user_resp = ".$this->user_resp_id;
			$sql.= " WHERE rowid = ".$this->id;

			dolibarr_syslog("Project::update sql=".$sql,LOG_DEBUG);
			if ($this->db->query($sql) )
			{
				$result = 0;
			}
			else
			{
				dolibarr_syslog($this->db->error());
				$result = -2;
			}
		}
		else
		{
			dolibarr_syslog("Project::Update ref null");
			$result = -1;
		}

		return $result;
	}


	/*
	*    \brief      Charge objet projet depuis la base
	*    \param      rowid       id du projet a charger
	*/
	function fetch($rowid)
	{

		$sql = "SELECT title, ref, fk_soc, fk_user_creat, fk_user_resp, fk_statut, note";
		$sql.= " FROM ".MAIN_DB_PREFIX."projet";
		$sql.= " WHERE rowid=".$rowid;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id             = $rowid;
				$this->ref            = $obj->ref;
				$this->title          = $obj->title;
				$this->titre          = $obj->title;
				$this->note           = $obj->note;
				$this->socid          = $obj->fk_soc;
				$this->societe->id    = $obj->fk_soc;	// For backward compatibility
				$this->user_author_id = $obj->fk_user_creat;
				$this->user_resp_id   = $obj->fk_user_resp;
				$this->statut         = $obj->fk_statut;

				$this->db->free($resql);

				return 0;
			}
			else
			{
				return -1;
			}
		}
		else
		{
			print $this->db->error();
			return -2;
		}
	}

	/**
	*	\brief		Return list of projects
	* 	\param		id_societe	To filter on a particular third party
	* 	\return		array		Liste of projects
	*/
	function liste_array($id_societe='')
	{
		$projets = array();

		$sql = "SELECT rowid, title FROM ".MAIN_DB_PREFIX."projet";
		if (! empty($id_societe))
		{
			$sql .= " WHERE fk_soc = ".$id_societe;
		}

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$nump = $this->db->num_rows($resql);

			if ($nump)
			{
				$i = 0;
				while ($i < $nump)
				{
					$obj = $this->db->fetch_object($resql);

					$projets[$obj->rowid] = $obj->title;
					$i++;
				}
			}
			return $projets;
		}
		else
		{
			print $this->db->error();
		}

	}
	
	/**
	* 	\brief		Return list of elements for type linked to project
	*	\param		type		'propal','order','invoice','order_supplier','invoice_supplier'
	*	\return		array		List of orders linked to project, <0 if error
	*/
	function get_element_list($type)
	{
		$elements = array();

		$sql='';
		if ($type == 'propal')           $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."propal WHERE fk_projet=".$this->id;
		if ($type == 'order')            $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."commande WHERE fk_projet=".$this->id;
		if ($type == 'invoice')          $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture WHERE fk_projet=".$this->id;
		if ($type == 'order_supplier')   $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."commande_fournisseur WHERE fk_projet=".$this->id;
		if ($type == 'invoice_supplier') $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture_fourn WHERE fk_projet=".$this->id;
		if ($type == 'contract')         $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."contrat WHERE fk_projet=".$this->id;
		if (! $sql) return -1;

		dolibarr_syslog("Project::get_element_list sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
			$nump = $this->db->num_rows($result);
			if ($nump)
			{
				$i = 0;
				while ($i < $nump)
				{
					$obj = $this->db->fetch_object($result);

					$elements[$i] = $obj->rowid;

					$i++;
				}
				$this->db->free($result);

				/* Return array */
				return $elements;
			}
		}
		else
		{
			dolibarr_print_error($this->db);
		}
	}

	/**
	*    \brief    Supprime le projet dans la base
	*    \param    Utilisateur
	*/
	function delete($user)
	{

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."projet";
		$sql .= " WHERE rowid=".$this->id;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			return 0;
		}
		else
		{
			return -1;
		}
	}

	/**
	*    \brief     Create a task into project
	*    \param     user     Id user that create
	*    \param		title    Title of task
	*    \param     parent   Id task parent
	*/
	function CreateTask($user, $title, $parent = 0)
	{
		$result = 0;
		if (trim($title))
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."projet_task (fk_projet, title, fk_user_creat, fk_task_parent, duration_effective)";
			$sql.= " VALUES (".$this->id.",'$title', ".$user->id.",".$parent.", 0)";

			dolibarr_syslog("Project::CreateTask sql=".$sql,LOG_DEBUG);
			if ($this->db->query($sql) )
			{
				$task_id = $this->db->last_insert_id(MAIN_DB_PREFIX."projet_task");
				$result = 0;
			}
			else
			{
				$this->error=$this->db->error();
				dolibarr_syslog("Project::CreateTask error -2 ".$this->error,LOG_ERR);
				$result = -2;
			}

			if ($result == 0)
			{
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."projet_task_actors (fk_projet_task, fk_user)";
				$sql.= " VALUES (".$task_id.",".$user->id.")";

				dolibarr_syslog("Project::CreateTask sql=".$sql,LOG_DEBUG);
				if ($this->db->query($sql) )
				{
					$result = 0;
				}
				else
				{
					$this->error=$this->db->error();
					dolibarr_syslog("Project::CreateTask error -3 ".$this->error,LOG_ERR);
					$result = -2;
				}
			}


		}
		else
		{
			dolibarr_syslog("Project::CreateTask error -1 ref null");
			$result = -1;
		}

		return $result;
	}


	/**
	*    \brief     Cree une tache dans le projet
	*    \param     user     Id utilisateur qui cree
	*    \param     title    titre de la tache
	*    \param     parent   tache parente
	*/
	function TaskAddTime($user, $task, $time, $date)
	{
		$result = 0;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."projet_task_time (fk_task, task_date, task_duration, fk_user)";
		$sql .= " VALUES (".$task.",'".$this->db->idate($date)."',".$time.", ".$user->id.")";

		dolibarr_syslog("Project::TaskAddTime sql=".$sql, LOG_DEBUG);
		if ($this->db->query($sql) )
		{
			$task_id = $this->db->last_insert_id(MAIN_DB_PREFIX."projet_task");
			$result = 0;
		}
		else
		{
			dolibarr_syslog("Project::TaskAddTime error -2",LOG_ERR);
			$this->error=$this->db->error();
			$result = -2;
		}

		if ($result == 0)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
			$sql .= " SET duration_effective = duration_effective + '".price2num($time)."'";
			$sql .= " WHERE rowid = '".$task."';";

			dolibarr_syslog("Project::TaskAddTime sql=".$sql, LOG_DEBUG);
			if ($this->db->query($sql) )
			{
				$result = 0;
			}
			else
			{
				dolibarr_syslog("Project::TaskAddTime error -3",LOG_ERR);
				$this->error=$this->db->error();
				$result = -2;
			}
		}

		return $result;
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $user
	 * @return unknown
	 */
	function getTasksRoleForUser($user)
	{
		$tasksrole = array();

		/* Liste des taches et role sur la tache du user courant dans $tasksrole */
		$sql = "SELECT a.fk_projet_task, a.role";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task_actors as a";
		$sql .= " WHERE a.fk_user = ".$user->id;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $this->db->fetch_row($resql);
				$tasksrole[$row[0]] = $row[1];
				$i++;
			}
			$this->db->free();
		}
		else
		{
			dolibarr_print_error($this->db);
		}

		return $tasksrole;
	}

	/**
	 * Return list of project - tasks
	 *
	 * @return unknown
	 */
	function getTasksArray()
	{
		$tasks = array();

		/* List of tasks */
	
		$sql = "SELECT p.rowid as projectid, p.ref, p.title as ptitle,";
		$sql.= " t.rowid, t.title, t.fk_task_parent, t.duration_effective";
		$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t on t.fk_projet = p.rowid";
		if ($this->id) $sql .= " WHERE t.fk_projet =".$this->id;
		$sql.= " ORDER BY p.ref, t.title";

		dolibarr_syslog("Project::getTasksArray sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$tasks[$i]->projectid    = $obj->projectid;
				$tasks[$i]->projectref   = $obj->ref;
				$tasks[$i]->projectlabel = $obj->title;
				$tasks[$i]->id         = $obj->rowid;
				$tasks[$i]->title      = $obj->title;
				$tasks[$i]->fk_parent  = $obj->fk_task_parent;
				$tasks[$i]->duration   = $obj->duration_effective;
				$i++;
			}
			$this->db->free();
		}
		else
		{
			dolibarr_print_error($this->db);
		}
		
		return $tasks;
	}

	/**
	 *	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *	\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *	\param		option			Sur quoi pointe le lien
	 *	\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

		$picto='project';

		$label=$langs->trans("ShowProject").': '.$this->ref;

		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
		return $result;
	}	
}
?>
