<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/compta/deplacement/deplacement.class.php
        \ingroup    deplacement
        \brief      Fichier de la classe des deplacements
        \version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT ."/commonobject.class.php");

/**
        \class      Deplacement
        \brief      Class to manage trips and working credit notes
*/
class Deplacement extends CommonObject
{
	var $db;
	var $errors;
	
	var $id;
	var $fk_user_author;
	var $fk_user;
	var $km;
	var $note;
	var $socid;
	
	
	/*
	* Constructor
	*/
	function Deplacement($DB)
	{
		$this->db = $DB;

		return 1;
	}

	/**
	 * Create object in database
	 *
	 * @param unknown_type $user	User that creat
	 * @param unknown_type $type	Type of record: 0=trip, 1=credit note
	 * @return unknown
	 */
	function create($user)
	{
		// Check parameters
		if (empty($this->type) || $this->type < 0)
		{
			$this->error='ErrorBadParameter';
			return -1;
		}
		if (empty($this->fk_user) || $this->fk_user < 0)
		{
			$this->error='ErrorBadParameter';
			return -1;
		}
		
		$this->db->begin();
		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."deplacement";
		$sql.= " (datec, fk_user_author, fk_user, type)";
		$sql.= " VALUES (".$this->db->idate(mktime()).", ".$user->id.", ".$this->fk_user.", '".$this->type."')";

		dolibarr_syslog("Deplacement::create sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."deplacement");
			$result=$this->update($user);
			if ($result > 0)
			{
				$this->db->commit();
				return $this->id;
			}
			else
			{
				$this->db->rollback();
				return $result;
			}
		}
		else
		{
			$this->error=$this->db->error()." sql=".$sql;
			$this->db->rollback();
			return -1;	
		}
		
	}

	/**
	 * 
	 *
	 */
	function update($user)
	{
		global $langs;

		// Clean parameters
		$this->km=price2num($this->km);
		
		// Check parameters
		if (! is_numeric($this->km)) $this->km = 0;
		if (empty($this->type) || $this->type < 0)
		{
			$this->error='ErrorBadParameter';
			return -1;
		}
		if (empty($this->fk_user) || $this->fk_user < 0)
		{
			$this->error='ErrorBadParameter';
			return -1;
		}
		
		$sql = "UPDATE ".MAIN_DB_PREFIX."deplacement ";
		$sql .= " SET km = ".$this->km;		// This is a distance or amount
		$sql .= " , dated = '".$this->db->idate($this->date)."'";
		$sql .= " , type = '".$this->type."'";
		$sql .= " , fk_user = ".$this->fk_user;
		$sql .= " , fk_soc = ".($this->socid > 0?$this->socid:'null');
		$sql .= " WHERE rowid = ".$this->id;

		dolibarr_syslog("Deplacement::update sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	*
	*/
	function fetch($id)
	{
		$sql = "SELECT rowid, fk_user, type, km, fk_soc,".$this->db->pdate("dated")." as dated";
		$sql.= " FROM ".MAIN_DB_PREFIX."deplacement";
		$sql.= " WHERE rowid = ".$id;

		dolibarr_syslog("Deplacement::fetch sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql) ;
		if ( $result )
		{
			$obj = $this->db->fetch_object($result);

			$this->id       = $obj->rowid;
			$this->ref      = $obj->rowid;
			$this->date     = $obj->dated;
			$this->fk_user  = $obj->fk_user;
			$this->socid    = $obj->fk_soc;
			$this->km       = $obj->km;
			$this->type     = $obj->type;

			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/*
	*
	*/
	function delete($id)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."deplacement WHERE rowid = ".$id;

		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

}

?>
