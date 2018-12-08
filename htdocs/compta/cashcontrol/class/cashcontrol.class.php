<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2016 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018 Andreu Bisquerra     <jove@bisquerra.com> 
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
 * \file       cashcontrol/class/cashcontrol.class.php
 * \ingroup    bank
 * \brief      This file is CRUD class file (Create/Read/Update/Delete) for bank categories
 */

/**
 *    Class to manage bank categories
 */
class CashControl // extends CommonObject
{
	public $id;
	public $opening;
	public $status;
	public $date_creation;
	public $year_close;
	public $month_close;
	public $day_close;
	public $posmodule;
	public $posnumber;
   

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}


	/**
	 *  Create in database
	 *
	 * @param  User $user User that create
	 * @param  int $notrigger 0=launch triggers after, 1=disable triggers
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."pos_cash_fence (";
		$sql .= "entity";
		$sql .= ", opening";
        $sql .= ", status";
		$sql .= ", date_creation";
		$sql .= ", posmodule";
		$sql .= ", posnumber";   
		$sql .= ") VALUES (";
		$sql .= $conf->entity;
		$sql .= ", ".$this->opening;
        $sql .= ",1";
		$sql .= ", now()";
		$sql .= ", '".$this->posmodule."'";
		$sql .= ", '".$this->posnumber."'";        
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."pos_cash_fence");
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	
	
	
	
	public function close(User $user, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."pos_cash_fence ";
		$sql .= "SET";
		$sql .= " day_close=DAYOFMONTH(NOW())";
		$sql .= ", month_close=MONTH(NOW())";
		$sql .= ", year_close=YEAR(NOW())";		
        $sql .= ", status=2";
		$sql .= " where rowid=".$this->id;
		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."pos_cash_fence");
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}
	
	
	
	

	/**
	 * Load object in memory from database
	 *
	 * @param  int $id Id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($id)
	{
		global $conf;

		$sql = "SELECT";
		$sql .= " *";
		$sql .= " FROM ".MAIN_DB_PREFIX."pos_cash_fence";
		$sql .= " WHERE rowid = ".$id;
		$sql .= " AND entity = ".$conf->entity;
		
		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->opening = $obj->opening;
				$this->status = $obj->status;
				$this->date_creation = $obj->date_creation;
				$this->year_close = $obj->year_close;
				$this->month_close = $obj->month_close;
				$this->day_close = $obj->day_close;
				$this->posmodule = $obj->posmodule;
				$this->posnumber = $obj->posnumber;
				$this->id=$id;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}
}
