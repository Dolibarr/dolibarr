<?php
/* Copyright (C) 2025  Jon Bendtsen         <jon.bendtsen.github@jonb.dk>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/class/commonobject.class.php
 *	\ingroup    core
 *	\brief      File of parent class of all other business classes (invoices, contracts, proposals, orders, ...)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/doldeprecationhandler.class.php';

/**
 *	Parent class of all other business classes (invoices, contracts, proposals, orders, ...)
 *
 * @phan-forbid-undeclared-magic-properties
 */
class ObjectLink extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'objectlink';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'element_element';

	const TRIGGER_PREFIX = 'OBJECTLINK'; // to be overridden in child class implementations, i.e. 'BILL', 'TASK', 'PROPAL', etc.

	/**
	 * @var int source id is a foreign key
	 */
	public $fk_source;
	/**
	 * @var string source type
	 */
	public $sourcetype;

	/**
	 * @var int source id is a foreign key
	 */
	public $target;
	/**
	 * @var string source type
	 */
	public $targettype;

	/**
	 * @var string relation type, not sure if ever used, but it is in the database
	 */
	public $relationtype;

	/**
	 * Constructor of the class
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Get object link from database.
	 *
	 *	@param      int			$rowid       	row Id of object link
	 *	@return     int         				>0 if OK, <0 if KO, 0 if not found
	 */
	public function fetch($rowid)
	{
		$sql = "SELECT rowid, fk_source, sourcetype, fk_target,";
		$sql .= " targettype, relationtype FROM";
		$sql .= " ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE rowid = ".((int) $rowid);

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			if ($obj) {
				$this->id = $obj->rowid;
				//$this->entity = $obj->entity;

				$this->fk_source = $obj->fk_source;
				$this->sourcetype = $obj->sourcetype;
				$this->fk_target = $obj->fk_target;
				$this->targettype = $obj->targettype;
				$this->relationtype = $obj->relationtype;

				return 1;
			} else {
				$this->error = 'Object link with id '.$rowid.' not found sql='.$sql;
				return 0;
			}
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *	Delete the object link
	 *
	 *	@param	User	$user		User object
	 *	@param	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 * 	@return	int					Return integer <=0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{

		global $conf, $langs;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		dol_syslog(get_class($this)."::delete ".$this->id, LOG_DEBUG);

		$this->db->begin();

		if (!$notrigger) {
			// Call trigger
			$result = $this->call_trigger('OBJECTLINK_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Delete object link
		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid = ".((int) $this->id);
			$res = $this->db->query($sql);
			if (!$res) {
				$error++;
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				dol_syslog(get_class($this)."::delete error ".$this->error, LOG_ERR);
			}
		}

		if (!$error) {
			dol_syslog(get_class($this)."::delete ".$this->id." by ".$user->id, LOG_DEBUG);
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Create object link
	 *
	 *	@param		User	$user			Object user that make creation
	 *  @param		int		$fk_source		source id of object we link from
	 *  @param		string	$sourcetype		type of the source object
	 *  @param		int		$fk_target		target id of object we link to
	 *  @param		string	$targettype 	type of the target object
	 *  @param		string	$relationtype 	type of the relation, usually null
	 *	@param		int	    $notrigger		Disable all triggers
	 *	@return 	int			        	Return integer <0 if KO, >0 if OK
	 */
	public function create($user, $fk_source, $sourcetype, $fk_target, $targettype, $relationtype = null, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		dol_syslog(get_class($this)."::create user=".$user->id);

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."$this->table_element";
		if ($relationtype) {
			$sql .= " (fk_source, sourcetype, fk_target, targettype, relationtype )";
		} else {
			$sql .= " (fk_source, sourcetype, fk_target, targettype )";
		}
		$sql .= " VALUES (".((int) $this->fk_source).", ".((string) $this->sourcetype).",";
		$sql .= ((int) $this->fk_target).", ".((string) $this->targettype);
		if ($relationtype) {
			$sql .= ", ".((string) $this->targettype);
		}
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		dol_syslog("sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			return 0;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}
}
