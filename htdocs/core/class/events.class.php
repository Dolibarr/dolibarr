<?php
/* Copyright (C) 2007-2019	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2023		William Mead		<william.mead@manchenumerique.fr>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *      \file       htdocs/core/class/events.class.php
 *      \ingroup    core
 *		\brief      File of class to manage security events.
 */


/**
 *  Events class
 */
class Events // extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'events';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'events';

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var int timestamp
	 */
	public $tms;

	/**
	 * @var string Type
	 */
	public $type;

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var int|string
	 */
	public $dateevent;

	/**
	 * @var string IP
	 */
	public $ip;

	/**
	 * @var string User agent
	 */
	public $user_agent;

	/**
	 * @var string label
	 */
	public $label;

	/**
	 * @var string description
	 */
	public $description;

	/**
	 * @var string	Prefix session obtained with method dol_getprefix()
	 */
	public $prefix_session;

	/**
	 * @var string	Authentication method used for USER_LOGIN with success
	 */
	public $authentication_method;


	/**
	 * @var array<array{id:string,test:int<0,1>}> List of all Audit/Security events supported by triggers
	 */
	public $eventstolog = array(
		array('id' => 'USER_LOGIN', 'test' => 1),
		array('id' => 'USER_LOGIN_FAILED', 'test' => 1),
		array('id' => 'USER_LOGOUT', 'test' => 1),
		array('id' => 'USER_CREATE', 'test' => 1),
		array('id' => 'USER_MODIFY', 'test' => 1),
		array('id' => 'USER_NEW_PASSWORD', 'test' => 1),
		array('id' => 'USER_ENABLEDISABLE', 'test' => 1),
		array('id' => 'USER_DELETE', 'test' => 1),
		array('id' => 'USERGROUP_CREATE', 'test' => 1),
		array('id' => 'USERGROUP_MODIFY', 'test' => 1),
		array('id' => 'USERGROUP_DELETE', 'test' => 1),
	);


	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-5,5>|string,alwayseditable?:int<0,1>,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,4>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,autofocusoncreate?:int<0,1>,comment?:string,copytoclipboard?:int<1,2>,validate?:int<0,1>,showonheader?:int<0,1>}>	Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid'         => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -2, 'noteditable' => 1, 'notnull' => 1, 'index' => 1, 'position' => 1, 'comment' => 'Id'),
		'entity'        => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'default' => '1', 'index' => 1, 'position' => 20),
		'prefix_session' => array('type' => 'varchar(255)', 'label' => 'PrefixSession', 'enabled' => 1, 'visible' => -1, 'notnull' => -1, 'index' => 0, 'position' => 1000),
		'user_agent'    => array('type' => 'varchar(255)', 'label' => 'UserAgent', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'default' => '0', 'index' => 1, 'position' => 1000),
	);


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *   Create in database
	 *
	 *   @param      User	$user       User that create
	 *   @return     int                Return integer <0 if KO, >0 if OK
	 */
	public function create($user)
	{
		global $conf;

		// Clean parameters
		$this->description = trim($this->description);
		if (empty($this->user_agent)) {
			$this->user_agent = (empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT']);
		}

		// Check parameters
		if (empty($this->description)) {
			$this->error = 'ErrorBadValueForParameterCreateEventDesc';
			return -1;
		}

		// Insert request
		$sql = "INSERT INTO ".$this->db->prefix()."events(";
		$sql .= "type,";
		$sql .= "entity,";
		$sql .= "ip,";
		$sql .= "user_agent,";
		$sql .= "dateevent,";
		$sql .= "fk_user,";
		$sql .= "description,";
		$sql .= "prefix_session";
		$sql .= ") VALUES (";
		$sql .= " '".$this->db->escape($this->type)."',";
		$sql .= " ".((int) $conf->entity).",";
		$sql .= " '".$this->db->escape(getUserRemoteIP())."',";
		$sql .= " ".($this->user_agent ? "'".$this->db->escape(dol_trunc($this->user_agent, 250))."'" : 'NULL').",";
		$sql .= " '".$this->db->idate($this->dateevent)."',";
		$sql .= " ".($user->id > 0 ? ((int) $user->id) : 'NULL').",";
		$sql .= " '".$this->db->escape(dol_trunc($this->description, 250))."',";
		$sql .= " '".$this->db->escape(dol_getprefix())."'";
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id($this->db->prefix()."events");
			return $this->id;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 * Update database
	 *
	 * @param	User    $user        	User that modify
	 * @param   int		$notrigger	    0=no, 1=yes (no update trigger)
	 * @return  int         			Return integer <0 if KO, >0 if OK
	 */
	public function update($user = null, $notrigger = 0)
	{
		// Clean parameters
		$this->id = (int) $this->id;
		$this->type = trim($this->type);
		$this->description = trim($this->description);

		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".$this->db->prefix()."events SET";
		$sql .= " type='".$this->db->escape($this->type)."',";
		$sql .= " dateevent='".$this->db->idate($this->dateevent)."',";
		$sql .= " description='".$this->db->escape($this->description)."'";
		$sql .= " WHERE rowid=".((int) $this->id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
		return 1;
	}


	/**
	 *  Load object in memory from database
	 *
	 *  @param	int		$id         Id object
	 *  @param  User	$user       User that load
	 *  @return int         		Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id, $user = null)
	{
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.tms,";
		$sql .= " t.type,";
		$sql .= " t.entity,";
		$sql .= " t.dateevent,";
		$sql .= " t.description,";
		$sql .= " t.ip,";
		$sql .= " t.user_agent,";
		$sql .= " t.prefix_session";
		$sql .= " FROM ".$this->db->prefix()."events as t";
		$sql .= " WHERE t.rowid = ".((int) $id);

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->tms = $this->db->jdate($obj->tms);
				$this->type = $obj->type;
				$this->entity = $obj->entity;
				$this->dateevent = $this->db->jdate($obj->dateevent);
				$this->description = $obj->description;
				$this->ip = $obj->ip;
				$this->user_agent = $obj->user_agent;
				$this->prefix_session = $obj->prefix_session;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Delete object in database
	 *
	 *	@param	User	$user       User that delete
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function delete($user)
	{
		$sql = "DELETE FROM ".$this->db->prefix()."events";
		$sql .= " WHERE rowid=".((int) $this->id);

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}

		return 1;
	}


	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return int
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;

		$this->tms = time();
		$this->type = '';
		$this->dateevent = time();
		$this->description = 'This is a specimen event';
		$this->ip = '1.2.3.4';
		$this->user_agent = 'Mozilla specimen User Agent X.Y';
		$this->prefix_session = dol_getprefix();

		return 1;
	}
}
