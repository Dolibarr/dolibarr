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
	const TRIGGER_PREFIX = 'OBJECTLINK';
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'objectlink';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'element_element';

	/**
	 * @var int source id is a foreign key
	 */
	public $fk_source;

	/**
	 * @var string source type
	 */
	public $sourcetype;

	/**
	 * @var int target id is a foreign key
	 */
	public $fk_target;

	/**
	 * @var string source type
	 */
	public $targettype;

	/**
	 * @var  null|string relation type, not sure if ever used, but it is in the database
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

				$this->fk_source = (int) $obj->fk_source;
				$this->sourcetype = (string) $obj->sourcetype;
				$this->fk_target = (int) $obj->fk_target;
				$this->targettype = (string) $obj->targettype;
				$this->relationtype = $obj->relationtype;

				return 1;
			} else {
				$this->error = 'Object link with id '.((string) $rowid).' not found sql='.$sql;
				return 0;
			}
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *	fetch object link By Values, not id
	 *
	 *  @param		int		$fk_source		source id of object we link from
	 *  @param		string	$sourcetype		type of the source object
	 *  @param		int		$fk_target		target id of object we link to
	 *  @param		string	$targettype 	type of the target object
	 *  @param		string	$relationtype 	type of the relation, usually null
	 *	@return 	int			        	Return integer <0 if KO, >0 if OK
	 */
	public function fetchByValues($fk_source, $sourcetype, $fk_target, $targettype, $relationtype = null)
	{
		$sql = "SELECT rowid, fk_source, sourcetype, fk_target,";
		$sql .= " targettype, relationtype FROM";
		$sql .= " ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE fk_source=".((int) $fk_source);
		$sql .= " AND sourcetype='".$this->db->escape($sourcetype)."'";
		$sql .= " AND fk_target=".((int) $fk_target);
		$sql .= " AND targettype='".$this->db->escape($targettype)."'";
		if ($relationtype) {
			$sql .= " AND relationtype='".$this->db->escape($relationtype)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			if ($obj) {
				$this->id = $obj->rowid;
				//$this->entity = $obj->entity;

				$this->fk_source = (int) $obj->fk_source;
				$this->sourcetype = (string) $obj->sourcetype;
				$this->fk_target = (int) $obj->fk_target;
				$this->targettype = (string) $obj->targettype;
				$this->relationtype = $obj->relationtype;

				return 1;
			} else {
				$this->error = 'Object link not found sql='.$sql;
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
			$result = $this->call_trigger(self::TRIGGER_PREFIX.'_DELETE', $user);
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

		$alreadyexists = $this->fetchByValues($fk_source, $sourcetype, $fk_target, $targettype, $relationtype);
		if ($alreadyexists == 1) {
			return 0;
		}

		// create sourceobject and targetobject, make sure they exist with the respective numbers
		$sourceobject = $this->_makeobject($fk_source, $sourcetype);
		if ($sourceobject < 0 ) {
			$this->error = "Error when looking for Object id=".$fk_source." of type=".$sourcetype;
			return -2;
		}
		if ($sourceobject == 0 ) {
			$this->error = "Object id ".$fk_source." of type ".$sourcetype." does not exist";
			return -1;
		}

		$targetobject = $this->_makeobject($fk_target, $targettype);
		if ($targetobject < 0 ) {
			$this->error = "Error when looking for Object id=".$fk_target." of type=".$targettype;
			return -2;
		}
		if ($targetobject == 0 ) {
			$this->error = "Object id ".$fk_target." of type ".$targettype." does not exist";
			return -1;
		}

		dol_syslog(get_class($this)."::create user=".$user->id);

		$this->db->begin();

		if (!$notrigger) {
			// Call trigger
			$result = $this->call_trigger(self::TRIGGER_PREFIX.'_CREATE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."$this->table_element";
		if ($relationtype) {
			$sql .= " (fk_source, sourcetype, fk_target, targettype, relationtype )";
		} else {
			$sql .= " (fk_source, sourcetype, fk_target, targettype )";
		}
		$sql .= " VALUES (".((int) $this->fk_source).", '".$this->db->escape($sourcetype)."', ";
		$sql .= ((int) $this->fk_target).", '".$this->db->escape($targettype)."'";
		if ($relationtype) {
			$sql .= ", '".$this->db->escape($relationtype)."'";
		}
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}
	/**
	 * Creates an object of the right kind and try to fetch it to make sure the id exists
	 *
	 * Return 1 if created, -1 if it does not exist
	 *
	 * @param   int         $objectid       ID of the object
	 * @param   string      $objecttype     ID of the object
	 * @return  int							1 if created, -1 if it does not exist
	 *
	 */
	private function _makeobject($objectid, $objecttype)
	{
		if ($objecttype == 'adherent') {
			require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
			$newobject = new Adherent($this->db);
			$result = $newobject->fetch($objectid);
			return $result;
		}
		if ($objecttype == 'commande') {
			require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
			$newobject = new Commande($this->db);
			$result = $newobject->fetch($objectid);
			return $result;
		}
		if ($objecttype == 'facture') {
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			$newobject = new Facture($this->db);
			$result = $newobject->fetch($objectid);
			return $result;
		}
		if ($objecttype == 'propal') {
			require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
			$newobject = new Propal($this->db);
			$result = $newobject->fetch($objectid);
			return $result;
		}
		if ($objecttype == 'subscription') {
			require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
			$newobject = new Subscription($this->db);
			$result = $newobject->fetch($objectid);
			return $result;
		}
		dol_syslog("objectlink->_makeobject called with unknown objecttype=".$objecttype, LOG_ERR);
		return -2;
	}
}
