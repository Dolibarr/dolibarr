<?php
/* Copyright (c) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	 \file       htdocs/usergroup.class.php
 *	 \brief      Fichier de la classe des groupes d'utilisateur
 *	 \author     Rodolphe Qiedeville
 *	 \version    $Id$
 */

if ($conf->ldap->enabled) require_once (DOL_DOCUMENT_ROOT."/lib/ldap.class.php");


/**
 \class      UserGroup
 \brief      Classe permettant la gestion des groupes d'utilisateur
 */

class UserGroup extends CommonObject
{
	var $db;			// Database handler
	var $error;
	var $errors=array();
	var $table_element='usergroup';

	var $id;			// Group id
	var $entity;  // Entity of group
	var $nom;			// Name of group
	var $note;			// Note on group
	var $datec;			// Creation date of group
	var $datem;			// Modification date of group


	/**
	 *    \brief Constructeur de la classe
	 *    \param  DB         Handler acces base de donnees
	 *    \param  id         Id du groupe (0 par defaut)
	 */
	function UserGroup($DB, $id=0)
	{
		$this->db = $DB;
		$this->id = $id;

		return 0;
	}


	/**
	 *	\brief      Charge un objet group avec toutes ces caracteristiques
	 *	\param      id      id du groupe a charger
	 *	\return		int		<0 si KO, >0 si OK
	 */
	function fetch($id)
	{
		global $conf;

		$this->id = $id;

		$sql = "SELECT g.rowid, g.entity, g.nom, g.note, g.datec, g.tms as datem";
		$sql.= " FROM ".MAIN_DB_PREFIX."usergroup as g";
		$sql.= " WHERE g.rowid = ".$this->id;

		dol_syslog("Usergroup::fetch sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;
				$this->ref = $obj->rowid;
				$this->entity = $obj->entity;
				$this->nom  = $obj->nom;
				$this->note = $obj->note;
				$this->datec = $obj->datec;
				$this->datem = $obj->datem;

				$this->next_prev_filter = 'entity IN (0,'.$conf->entity.')';

			}
			$this->db->free($result);
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog("UserGroup::Fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 * 	\brief		Return array of groups of a user
	 *	\param		usertosearch
	 * 	\return		array of groups objects
	 */
	function listGroupsForUser($usertosearch)
	{
		$ret=array();

		$sql = "SELECT g.rowid, g.nom, g.note, g.datec, tms as datem";
		$sql.= " FROM ".MAIN_DB_PREFIX."usergroup as g,";
		$sql.= " ".MAIN_DB_PREFIX."usergroup_user as ug";
		$sql.= " WHERE ug.fk_usergroup = g.rowid";
		$sql.= " AND ug.fk_user = ".$usertosearch->id;
		$sql.= " ORDER BY g.nom";

		dol_syslog("UserGroup::listGroupsForUser sql=".$sql,LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			while ($obj = $this->db->fetch_object($result))
			{
				$group=new UserGroup($this->db);
				$group->id=$obj->rowid;
				$group->nom=$obj->nom;
				$group->note=$obj->note;
				$group->datec = $obj->datec;
				$group->datem = $obj->datem;

				$ret[]=$group;
			}
			$this->db->free($result);
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog("UserGroup::listGroupsForUser ".$this->error, LOG_ERR);
			return -1;
		}
		return $ret;
	}

	/**
	 *    \brief      Ajoute un droit a l'utilisateur
	 *    \param      rid         id du droit a ajouter
	 *    \param      allmodule   Ajouter tous les droits du module allmodule
	 *    \param      allperms    Ajouter tous les droits du module allmodule, perms allperms
	 *    \return     int         > 0 si ok, < 0 si erreur
	 */
	function addrights($rid,$allmodule='',$allperms='')
	{
		global $conf;

		$err=0;
		$whereforadd='';

		$this->db->begin();

		if ($rid)
		{
			// Si on a demande ajout d'un droit en particulier, on recupere
			// les caracteristiques (module, perms et subperms) de ce droit.
			$sql = "SELECT module, perms, subperms";
			$sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
			$sql.= " WHERE id = '".$rid."'";
			$sql.= " AND entity = ".$conf->entity;

			$result=$this->db->query($sql);
			if ($result) {
				$obj = $this->db->fetch_object($result);
				$module=$obj->module;
				$perms=$obj->perms;
				$subperms=$obj->subperms;
			}
			else {
				$err++;
				dol_print_error($this->db);
			}

			// Where pour la liste des droits a ajouter
			$whereforadd="id=".$rid;
			// Ajout des droits induits
			if ($subperms) $whereforadd.=" OR (module='$module' AND perms='$perms' AND subperms='lire')";
			if ($perms)    $whereforadd.=" OR (module='$module' AND perms='lire' AND subperms IS NULL)";

			// Pour compatibilite, si lowid = 0, on est en mode ajout de tout
			// TODO A virer quand sera gere par l'appelant
			if (substr($rid,-1,1) == 0) $whereforadd="module='$module'";
		}
		else {
			// Where pour la liste des droits a ajouter
			if ($allmodule) $whereforadd="module='$allmodule'";
			if ($allperms)  $whereforadd=" AND perms='$allperms'";
		}

		// Ajout des droits de la liste whereforadd
		if ($whereforadd)
		{
			//print "$module-$perms-$subperms";
			$sql = "SELECT id";
			$sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
			$sql.= " WHERE $whereforadd";
			$sql.= " AND entity = ".$conf->entity;

			$result=$this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$i = 0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					$nid = $obj->id;

					$sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_rights WHERE fk_usergroup = $this->id AND fk_id=$nid";
					if (! $this->db->query($sql)) $err++;
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."usergroup_rights (fk_usergroup, fk_id) VALUES ($this->id, $nid)";
					if (! $this->db->query($sql)) $err++;

					$i++;
				}
			}
			else
			{
				$err++;
				dol_print_error($this->db);
			}
		}

		if ($err) {
			$this->db->rollback();
			return -$err;
		}
		else {
			$this->db->commit();
			return 1;
		}

	}


	/**
	 *    \brief      Retire un droit a l'utilisateur
	 *    \param      rid         id du droit a retirer
	 *    \param      allmodule   Retirer tous les droits du module allmodule
	 *    \param      allperms    Retirer tous les droits du module allmodule, perms allperms
	 *    \return     int         > 0 si ok, < 0 si erreur
	 */
	function delrights($rid,$allmodule='',$allperms='')
	{
		global $conf;

		$err=0;
		$wherefordel='';

		$this->db->begin();

		if ($rid)
		{
			// Si on a demande supression d'un droit en particulier, on recupere
			// les caracteristiques module, perms et subperms de ce droit.
			$sql = "SELECT module, perms, subperms";
			$sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
			$sql.= " WHERE id = '".$rid."'";
			$sql.= " AND entity = ".$conf->entity;

			$result=$this->db->query($sql);
			if ($result) {
				$obj = $this->db->fetch_object($result);
				$module=$obj->module;
				$perms=$obj->perms;
				$subperms=$obj->subperms;
			}
			else {
				$err++;
				dol_print_error($this->db);
			}

			// Where pour la liste des droits a supprimer
			$wherefordel="id=".$rid;
			// Suppression des droits induits
			if ($subperms=='lire') $wherefordel.=" OR (module='$module' AND perms='$perms' AND subperms IS NOT NULL)";
			if ($perms=='lire')    $wherefordel.=" OR (module='$module')";

			// Pour compatibilite, si lowid = 0, on est en mode suppression de tout
			// \todo A virer quand sera gere par l'appelant
			if (substr($rid,-1,1) == 0) $wherefordel="module='$module'";
		}
		else {
			// Where pour la liste des droits a supprimer
			if ($allmodule) $wherefordel="module='$allmodule'";
			if ($allperms)  $wherefordel=" AND perms='$allperms'";
		}

		// Suppression des droits de la liste wherefordel
		if ($wherefordel)
		{
			//print "$module-$perms-$subperms";
			$sql = "SELECT id";
			$sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
			$sql.= " WHERE $wherefordel";
			$sql.= " AND entity = ".$conf->entity;

			$result=$this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$i = 0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					$nid = $obj->id;

					$sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_rights WHERE fk_usergroup = $this->id AND fk_id=$nid";
					if (! $this->db->query($sql)) $err++;

					$i++;
				}
			}
			else
			{
				$err++;
				dol_print_error($this->db);
			}
		}

		if ($err) {
			$this->db->rollback();
			return -$err;
		}
		else {
			$this->db->commit();
			return 1;
		}

	}


	/**
	 *    \brief      Charge dans l'objet group, la liste des permissions auquels le groupe a droit
	 *    \param      module    	Nom du module dont il faut recuperer les droits ('' par defaut signifie tous les droits)
	 */
	function getrights($module='')
	{
		global $conf;

		if ($this->all_permissions_are_loaded)
		{
			// Si les permissions ont deja ete chargees, on quitte
			return;
		}

		/*
		 * Recuperation des droits
		 */
		$sql = "SELECT r.module, r.perms, r.subperms ";
		$sql.= " FROM ".MAIN_DB_PREFIX."usergroup_rights as u, ".MAIN_DB_PREFIX."rights_def as r";
		$sql.= " WHERE r.id = u.fk_id";
		$sql.= " AND r.entity = ".$conf->entity;
		$sql.= " AND u.fk_usergroup = ".$this->id;
		$sql.= " AND r.perms IS NOT NULL";
		if ($this->db->query($sql))
		{
	  $num = $this->db->num_rows();
	  $i = 0;
	  while ($i < $num)
	  {
	  	$row = $this->db->fetch_row();

	  	if (strlen($row[1]) > 0)
	  	{

	  		if (strlen($row[2]) > 0)
	  		{
	  			$this->rights->$row[0]->$row[1]->$row[2] = 1;
	  		}
	  		else
	  		{
	  			$this->rights->$row[0]->$row[1] = 1;
	  		}

	  	}
	  	$i++;
	  }
		}

		if ($module == '')
		{
			// Si module etait non defini, alors on a tout charge, on peut donc considerer
			// que les droits sont en cache (car tous charges) pour cet instance de user
			$this->all_permissions_are_loaded=1;
		}

	}

	/**
	 *        \brief      Efface un groupe de la base
	 *        \return     < 0 si erreur, > 0 si ok
	 */
	function delete()
	{
		global $user,$conf,$langs;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_rights";
		$sql .= " WHERE fk_usergroup = ".$this->id;
		$this->db->query($sql);

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_user";
		$sql .= " WHERE fk_usergroup = ".$this->id;
		$this->db->query($sql);

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup";
		$sql .= " WHERE rowid = ".$this->id;
		$result=$this->db->query($sql);
		if ($result)
		{
			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('USER_DELETE',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

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
	 *        \brief      Cree un groupe en base
	 *        \return     si erreur <0, si ok renvoie id groupe cr
	 */
	function create()
	{
		global $user, $conf, $langs;

		$sql = "INSERT into ".MAIN_DB_PREFIX."usergroup (datec, nom, entity)";
		$sql .= " VALUES(".$this->db->idate(mktime()).",'".addslashes($this->nom)."',".$conf->entity.")";

		$result=$this->db->query($sql);
		if ($result)
		{
			$table =  "".MAIN_DB_PREFIX."usergroup";
			$this->id = $this->db->last_insert_id($table);

			if ($this->update(1) < 0) return -2;

			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
			$interface=new Interfaces($this->db);
			$result=$interface->run_triggers('GROUP_CREATE',$this,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

			return $this->id;
		}
		else
		{
			dol_syslog("UserGroup::Create");
			return -1;
		}
	}


	/**
	 *		\brief      Mise a jour en base d'un utilisateur
	 *      	\param      notrigger	    0=non, 1=oui
	 *    	\return     int				<0 si KO, >=0 si OK
	 */
	function update($notrigger=0)
	{
		global $user, $conf, $langs;

		$sql = "UPDATE ".MAIN_DB_PREFIX."usergroup SET ";
		$sql .= " nom = '".addslashes($this->nom)."',";
		$sql .= " note = '".addslashes($this->note)."'";
		$sql .= " WHERE rowid = ".$this->id;

		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->affected_rows())
			{

				if (! $notrigger)
				{
					// Appel des triggers
					include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
					$interface=new Interfaces($this->db);
					$result=$interface->run_triggers('GROUP_MODIFY',$this,$user,$langs,$conf);
					if ($result < 0) { $error++; $this->errors=$interface->errors; }
					// Fin appel triggers
				}

				return 1;
			}
			return 0;
		}
		else
		{
			dol_print_error($this->db);
			return -2;
		}
	}


	/*
	 *	\brief		Retourne chaine DN complete dans l'annuaire LDAP pour l'objet
	 *	\param		info		Info string loaded by _load_ldap_info
	 *	\param		mode		0=Return DN without key inside (ou=xxx,dc=aaa,dc=bbb)
	 1=Return full DN (uid=qqq,ou=xxx,dc=aaa,dc=bbb)
	 2=Return key only (uid=qqq)
	 *	\return		string		DN
	 */
	function _load_ldap_dn($info,$mode=0)
	{
		global $conf;
		$dn='';
		if ($mode==0) $dn=$conf->global->LDAP_KEY_GROUPS."=".$info[$conf->global->LDAP_KEY_GROUPS].",".$conf->global->LDAP_USER_DN;
		if ($mode==1) $dn=$conf->global->LDAP_GROUP_DN;
		if ($mode==2) $dn=$conf->global->LDAP_KEY_GROUPS."=".$info[$conf->global->LDAP_KEY_GROUPS];
		return $dn;
	}


	/*
	 *	\brief		Initialise tableau info (tableau des attributs LDAP)
	 *	\return		array		Tableau info des attributs
	 */
	function _load_ldap_info()
	{
		global $conf,$langs;
		$info=array();

		// Object classes
		$info["objectclass"]=split(',',$conf->global->LDAP_GROUP_OBJECT_CLASS);

		// Champs
		if ($this->nom && $conf->global->LDAP_FIELD_FULLNAME) $info[$conf->global->LDAP_FIELD_FULLNAME] = $this->nom;
		if ($this->nom && $conf->global->LDAP_FIELD_NAME) $info[$conf->global->LDAP_FIELD_NAME] = $this->nom;
		if ($this->note && $conf->global->LDAP_FIELD_DESCRIPTION) $info[$conf->global->LDAP_FIELD_DESCRIPTION] = $this->note;

		return $info;
	}


	/**
	 *		\brief		Initialise le groupe avec valeurs fictives aleatoire
	 */
	function initAsSpecimen()
	{
		global $user,$langs;

		// Initialise parametres
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;

		$this->nom='DOLIBARR GROUP SPECIMEN';
		$this->note='This is a note';
		$this->datec=time();
		$this->datem=time();
	}
}

?>
