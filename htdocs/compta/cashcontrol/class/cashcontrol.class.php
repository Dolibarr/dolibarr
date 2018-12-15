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
 * \brief      This file is CRUD class file (Create/Read/Update/Delete) for cash fence table
 */

/**
 *    Class to manage cash fence
 */
class CashControl // extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'CashControl';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'pos_cash_fence';

	/**
	 * @var int  Does pos_cash_fence support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does pos_cash_fence support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var string String with name of icon for pos_cash_fence. Must be the part after the 'object_' into object_pos_cash_fence.png
	 */
	public $picto = 'bank';

	public $fields=array(
		'rowid' =>array('type'=>'integer', 'label'=>'ID', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>10),
		'entity' =>array('type'=>'integer', 'label'=>'Entity', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>15),
		'label' =>array('type'=>'varchar(255)', 'label'=>'Label', 'enabled'=>1, 'visible'=>1, 'position'=>20),
		'opening' =>array('type'=>'double(24,8)', 'label'=>'Opening', 'enabled'=>1, 'visible'=>1, 'position'=>25),
		'cash' =>array('type'=>'double(24,8)', 'label'=>'Cash', 'enabled'=>1, 'visible'=>1, 'position'=>30),
		//'card' =>array('type'=>'double(24,8)', 'label'=>'Card', 'enabled'=>1, 'visible'=>1, 'position'=>35),
		'day_close' =>array('type'=>'integer', 'label'=>'Day close', 'enabled'=>1, 'visible'=>1, 'position'=>50),
		'month_close' =>array('type'=>'integer', 'label'=>'Month close', 'enabled'=>1, 'visible'=>1, 'position'=>55),
		'year_close' =>array('type'=>'integer', 'label'=>'Year close', 'enabled'=>1, 'visible'=>1, 'position'=>60),
		'posmodule' =>array('type'=>'varchar(30)', 'label'=>'Module', 'enabled'=>1, 'visible'=>1, 'position'=>65),
		'posnumber' =>array('type'=>'varchar(30)', 'label'=>'CashDesk', 'enabled'=>1, 'visible'=>1, 'position'=>70),
		//'status' =>array('type'=>'integer', 'label'=>'Status', 'enabled'=>1, 'visible'=>1, 'position'=>40),
		'date_creation' =>array('type'=>'datetime', 'label'=>'Date creation', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>500),
		'tms' =>array('type'=>'timestamp', 'label'=>'Tms', 'enabled'=>1, 'visible'=>0, 'notnull'=>1, 'position'=>505),
		'import_key' =>array('type'=>'varchar(14)', 'label'=>'Import key', 'enabled'=>1, 'visible'=>0, 'position'=>510),
	);

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

	/**
	 * close
	 *
	 * @param 	User 		$user		User
	 * @param 	number 		$notrigger	No trigger
	 * @return 	int						<0 if KO, >0 if OK
	 */
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
	 * @param  int 	$id 		Id object
	 * @return int 				<0 if KO, >0 if OK
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