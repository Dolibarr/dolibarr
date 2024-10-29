<?php
/* Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *	\file       htdocs/core/class/link.class.php
 *	\ingroup    link
 *	\brief      File for link class
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class to manage links
 */
class Link extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'link';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'links';

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var int|'' date add
	 */
	public $datea;

	/**
	 * @var string Object url
	 */
	public $url;

	/**
	 * @var string Links label
	 */
	public $label;

	/**
	 * @var string Object type
	 */
	public $objecttype;

	/**
	 * @var int Object ID
	 */
	public $objectid;


	/**
	 *    Constructor
	 *
	 *    @param	DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *    Create link in database
	 *
	 *    @param	User	$user       Object of user that ask creation
	 *    @return   int         		>= 0 if OK, < 0 if KO
	 */
	public function create(User $user)
	{
		global $langs, $conf;

		$error = 0;
		$langs->load("errors");
		// Clean parameters
		if (empty($this->label)) {
			$this->label = trim(basename($this->url));
		}
		if (empty($this->datea)) {
			$this->datea = dol_now();
		}
		$this->url = trim($this->url);

		dol_syslog(get_class($this)."::create ".$this->url);

		// Check parameters
		if (empty($this->url)) {
			$this->error = $langs->trans("NoURL");
			return -1;
		}

		$this->db->begin();

		$sql = "INSERT INTO ".$this->db->prefix()."links (entity, datea, url, label, objecttype, objectid)";
		$sql .= " VALUES (".$conf->entity.", '".$this->db->idate($this->datea)."'";
		$sql .= ", '".$this->db->escape($this->url)."'";
		$sql .= ", '".$this->db->escape($this->label)."'";
		$sql .= ", '".$this->db->escape($this->objecttype)."'";
		$sql .= ", ".((int) $this->objectid).")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$this->id = $this->db->last_insert_id($this->db->prefix()."links");

			if ($this->id > 0) {
				// Call trigger
				$result = $this->call_trigger('LINK_CREATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			} else {
				$error++;
			}

			if (!$error) {
				dol_syslog(get_class($this)."::Create success id=".$this->id);
				$this->db->commit();
				return $this->id;
			} else {
				dol_syslog(get_class($this)."::Create echec update ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -3;
			}
		} else {
			if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				$this->error = $langs->trans("ErrorCompanyNameAlreadyExists", $this->name);
				$result = -1;
			} else {
				$this->error = $this->db->lasterror();
				$result = -2;
			}
			$this->db->rollback();
			return $result;
		}
	}

	/**
	 *  Update parameters of third party
	 *
	 *  @param  User	$user            			User executing update
	 *  @param  int		$call_trigger    			0=no, 1=yes
	 *  @return int  			           			Return integer <0 if KO, >=0 if OK
	 */
	public function update(User $user, $call_trigger = 1)
	{
		global $langs, $conf;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$langs->load("errors");
		$error = 0;

		dol_syslog(get_class($this)."::Update id = ".$this->id." call_trigger = ".$call_trigger);

		// Check parameters
		if (empty($this->url)) {
			$this->error = $langs->trans("NoURL");
			return -1;
		}

		// Clean parameters
		$this->url = clean_url($this->url, 1);
		if (empty($this->label)) {
			$this->label = basename($this->url);
		}
		$this->label = trim($this->label);


		$this->db->begin();

		$sql  = "UPDATE ".$this->db->prefix()."links SET ";
		$sql .= "entity = ".((int) $conf->entity);
		$sql .= ", datea = '".$this->db->idate(dol_now())."'";
		$sql .= ", url = '".$this->db->escape($this->url)."'";
		$sql .= ", label = '".$this->db->escape($this->label)."'";
		$sql .= ", objecttype = '".$this->db->escape($this->objecttype)."'";
		$sql .= ", objectid = ".$this->objectid;
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::update sql = ".$sql);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($call_trigger) {
				// Call trigger
				$result = $this->call_trigger('LINK_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				dol_syslog(get_class($this)."::Update success");
				$this->db->commit();
				return 1;
			} else {
				setEventMessages('', $this->errors, 'errors');
				$this->db->rollback();
				return -1;
			}
		} else {
			if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				// Duplicate
				$this->error = $langs->trans("ErrorDuplicateField");
				$result = -1;
			} else {
				$this->error = $langs->trans("Error sql")."= $sql";
				$result = -2;
			}
			$this->db->rollback();
			return $result;
		}
	}

	/**
	 *  Loads all links from database
	 *
	 *  @param  Link[]	$links      array of Link objects to fill
	 *  @param  string  $objecttype type of the associated object in dolibarr
	 *  @param  int     $objectid   id of the associated object in dolibarr
	 *  @param  ?string	$sortfield  field used to sort
	 *  @param  ?string	$sortorder  sort order
	 *  @return int<-1,1>           1 if ok, 0 if no records, -1 if error
	 */
	public function fetchAll(&$links, $objecttype, $objectid, $sortfield = null, $sortorder = null)
	{
		global $conf;

		$sql = "SELECT rowid, entity, datea, url, label, objecttype, objectid FROM ".$this->db->prefix()."links";
		$sql .= " WHERE objecttype = '".$this->db->escape($objecttype)."' AND objectid = ".((int) $objectid);
		if ($conf->entity != 0) {
			$sql .= " AND entity = ".((int) $conf->entity);
		}
		if ($sortfield) {
			if (empty($sortorder)) {
				$sortorder = "ASC";
			}
			$sql .= " ORDER BY ".$sortfield." ".$sortorder;
		}

		dol_syslog(get_class($this)."::fetchAll", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			dol_syslog(get_class($this)."::fetchAll num=".((int) $num), LOG_DEBUG);
			if ($num > 0) {
				while ($obj = $this->db->fetch_object($resql)) {
					$link = new Link($this->db);
					$link->id = (int) $obj->rowid;
					$link->entity = $obj->entity;
					$link->datea = $this->db->jdate($obj->datea);
					$link->url = $obj->url;
					$link->label = $obj->label;
					$link->objecttype = $obj->objecttype;
					$link->objectid = $obj->objectid;
					$links[] = $link;
				}
				return 1;
			} else {
				return 0;
			}
		} else {
			return -1;
		}
	}

	/**
	 *  Return nb of links
	 *
	 *  @param  DoliDB  $dbs		Database handler
	 *  @param  string  $objecttype Type of the associated object in dolibarr
	 *  @param  int     $objectid   Id of the associated object in dolibarr
	 *  @return int                 Nb of links, -1 if error
	 **/
	public static function count($dbs, $objecttype, $objectid)
	{
		global $conf;

		$sql = "SELECT COUNT(rowid) as nb FROM ".$dbs->prefix()."links";
		$sql .= " WHERE objecttype = '".$dbs->escape($objecttype)."' AND objectid = ".((int) $objectid);
		if ($conf->entity != 0) {
			$sql .= " AND entity = ".$conf->entity;
		}

		$resql = $dbs->query($sql);
		if ($resql) {
			$obj = $dbs->fetch_object($resql);
			if ($obj) {
				return $obj->nb;
			}
		}
		return -1;
	}

	/**
	 *  Loads a link from database
	 *
	 *  @param 	int		$rowid 		Id of link to load
	 *  @return int 				1 if ok, 0 if no record found, -1 if error
	 **/
	public function fetch($rowid = null)
	{
		global $conf;

		if (empty($rowid)) {
			$rowid = $this->id;
		}

		$sql = "SELECT rowid, entity, datea, url, label, objecttype, objectid FROM ".$this->db->prefix()."links";
		$sql .= " WHERE rowid = ".((int) $rowid);
		if ($conf->entity != 0) {
			$sql .= " AND entity = ".$conf->entity;
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql) > 0) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->entity = $obj->entity;
				$this->datea = $this->db->jdate($obj->datea);
				$this->url = $obj->url;
				$this->label = $obj->label;
				$this->objecttype = $obj->objecttype;
				$this->objectid = $obj->objectid;
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *    Delete a link from database
	 *
	 *	  @param	User		$user		Object suer
	 *    @return	int						Return integer <0 if KO, 0 if nothing done, >0 if OK
	 */
	public function delete($user)
	{
		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$error = 0;

		$this->db->begin();

		// Call trigger
		$result = $this->call_trigger('LINK_DELETE', $user);
		if ($result < 0) {
			$this->db->rollback();
			return -1;
		}
		// End call triggers

		// Remove link
		$sql = "DELETE FROM ".$this->db->prefix()."links";
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		if (!$this->db->query($sql)) {
			$error++;
			$this->error = $this->db->lasterror();
		}

		if (!$error) {
			$this->db->commit();

			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}
}
