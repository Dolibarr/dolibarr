<?php
/* Copyright (C) 2026		Guenter Lukas			<gl@gl.co.at>
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
 * 		\file		htdocs/user/class/userpasskey.class.php
 *		\ingroup    user
 *		\brief      File of class to manage the passkeys (WebAuthn credentials) of users
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 * 	Class to manage the passkeys (WebAuthn credentials) of users.
 *  They are saved into llx_oauth_token with service 'passkey'. The public key (PEM) is saved into tokenstring.
 *  Only the public key is stored, the private key never leaves the authenticator.
 */
class UserPasskey extends CommonObject
{
	/**
	 * Value of field service into llx_oauth_token
	 */
	const SERVICE = 'passkey';

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'user_passkey';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'oauth_token';

	/**
	 * @var int Id of the user owning the passkey
	 */
	public $fk_user;

	/**
	 * @var string Name given by the user to recognize the passkey
	 */
	public $label;

	/**
	 * @var string Credential ID, lower case hex encoded
	 */
	public $credential_id;

	/**
	 * @var string WebAuthn user.id, lower case hex encoded. Same for all passkeys of a user.
	 */
	public $user_handle;

	/**
	 * @var string Public key, PEM format
	 */
	public $public_key;

	/**
	 * @var int Last signature counter returned by the authenticator
	 */
	public $sign_count = 0;

	/**
	 * @var string Model of authenticator, as UUID
	 */
	public $aaguid;

	/**
	 * @var int<0,1> 1 if the passkey can be synced to other devices
	 */
	public $backup_eligible = 0;

	/**
	 * @var int<0,1> 1 if the passkey was synced at last use
	 */
	public $backup_state = 0;

	/**
	 * @var int|''	Date of last use for a login
	 */
	public $date_last_use;


	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}


	/**
	 * Create the passkey record
	 *
	 * @param	User		$user		User making the creation
	 * @return	int						Return integer <0 if KO, id of record if OK
	 */
	public function create($user)
	{
		global $conf;

		$now = dol_now();

		$sql = "INSERT INTO ".$this->db->prefix().$this->table_element." (service, entity, fk_user, tokenstring, datec,";
		$sql .= " passkey_label, passkey_credential_id, passkey_user_handle, passkey_sign_count, passkey_aaguid, passkey_backup_eligible, passkey_backup_state)";
		$sql .= " VALUES (";
		$sql .= "'".$this->db->escape(self::SERVICE)."'";
		$sql .= ", ".((int) $conf->entity);
		$sql .= ", ".((int) $this->fk_user);
		$sql .= ", '".$this->db->escape($this->public_key)."'";
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ", ".($this->label !== '' && $this->label !== null ? "'".$this->db->escape($this->label)."'" : "NULL");
		$sql .= ", '".$this->db->escape($this->credential_id)."'";
		$sql .= ", '".$this->db->escape($this->user_handle)."'";
		$sql .= ", ".((int) $this->sign_count);
		$sql .= ", ".($this->aaguid ? "'".$this->db->escape($this->aaguid)."'" : "NULL");
		$sql .= ", ".((int) $this->backup_eligible);
		$sql .= ", ".((int) $this->backup_state);
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}

		$this->id = $this->db->last_insert_id($this->db->prefix().$this->table_element);
		$this->entity = $conf->entity;
		$this->date_creation = $now;

		return $this->id;
	}

	/**
	 * Return the list of fields to select
	 *
	 * @return	string
	 */
	private function getSqlFields()
	{
		return "rowid, service, entity, fk_user, tokenstring, datec, lastaccess, tms, passkey_label, passkey_credential_id, passkey_user_handle, passkey_sign_count, passkey_aaguid, passkey_backup_eligible, passkey_backup_state";
	}

	/**
	 * Load a passkey from database, by its id or by its credential ID
	 *
	 * @param	int		$id					Id of passkey
	 * @param	string	$credential_id		Credential ID (lower case hex) to search if $id is 0
	 * @return	int							Return integer <0 if KO, 0 if not found or not a passkey, >0 if OK
	 */
	public function fetch($id, $credential_id = '')
	{
		$sql = "SELECT ".$this->getSqlFields();
		$sql .= " FROM ".$this->db->prefix().$this->table_element;
		if ($id > 0) {
			$sql .= " WHERE rowid = ".((int) $id);
		} else {
			$sql .= " WHERE passkey_credential_id = '".$this->db->escape($credential_id)."'";
			$sql .= " AND service = '".$this->db->escape(self::SERVICE)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}

		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);
		if (!$obj || $obj->service !== self::SERVICE) {	// The table also contains other kinds of tokens
			return 0;
		}

		$this->setVarsFromFetchObj($obj);

		return 1;
	}

	/**
	 * Return the list of passkeys of a user
	 *
	 * @param	int					$fk_user	Id of user
	 * @return	UserPasskey[]|int				Array of passkeys, <0 if KO
	 */
	public function fetchAllForUser($fk_user)
	{
		$list = array();

		$sql = "SELECT ".$this->getSqlFields();
		$sql .= " FROM ".$this->db->prefix().$this->table_element;
		$sql .= " WHERE fk_user = ".((int) $fk_user);
		$sql .= " AND service = '".$this->db->escape(self::SERVICE)."'";
		$sql .= " ORDER BY datec ASC, rowid ASC";

		dol_syslog(get_class($this)."::fetchAllForUser", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$passkey = new UserPasskey($this->db);
			$passkey->setVarsFromFetchObj($obj);
			$list[] = $passkey;
		}
		$this->db->free($resql);

		return $list;
	}

	/**
	 * Return the number of passkeys of a user
	 *
	 * @param	int		$fk_user	Id of user
	 * @return	int					Number of passkeys, <0 if KO
	 */
	public function countForUser($fk_user)
	{
		$sql = "SELECT COUNT(rowid) as nb FROM ".$this->db->prefix().$this->table_element;
		$sql .= " WHERE fk_user = ".((int) $fk_user);
		$sql .= " AND service = '".$this->db->escape(self::SERVICE)."'";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}
		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);

		return ($obj ? (int) $obj->nb : 0);
	}

	/**
	 * Return the user handle (WebAuthn user.id) already used by the passkeys of a user
	 *
	 * @param	int		$fk_user	Id of user
	 * @return	string				User handle (lower case hex), '' if the user has no passkey yet
	 */
	public function getUserHandleOfUser($fk_user)
	{
		$sql = "SELECT passkey_user_handle FROM ".$this->db->prefix().$this->table_element;
		$sql .= " WHERE fk_user = ".((int) $fk_user);
		$sql .= " AND service = '".$this->db->escape(self::SERVICE)."'";
		$sql .= " ORDER BY rowid ASC";
		$sql .= $this->db->plimit(1);

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$this->db->free($resql);
			if ($obj) {
				return (string) $obj->passkey_user_handle;
			}
		}

		return '';
	}

	/**
	 * Save the data returned by the authenticator after a successful login
	 *
	 * @param	int		$sign_count		New signature counter
	 * @param	int		$backup_state	1 if the passkey is currently synced
	 * @return	int						Return integer <0 if KO, >0 if OK
	 */
	public function updateAfterLogin($sign_count, $backup_state)
	{
		$now = dol_now();

		// Synced passkeys always return 0, so keep the highest counter ever seen
		$this->sign_count = max((int) $this->sign_count, (int) $sign_count);
		$this->backup_state = ($backup_state ? 1 : 0);
		$this->date_last_use = $now;

		$sql = "UPDATE ".$this->db->prefix().$this->table_element." SET";
		$sql .= " passkey_sign_count = ".((int) $this->sign_count);
		$sql .= ", passkey_backup_state = ".((int) $this->backup_state);
		$sql .= ", lastaccess = '".$this->db->idate($now)."'";
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::updateAfterLogin", LOG_DEBUG);
		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}

		return 1;
	}

	/**
	 * Change the label of the passkey
	 *
	 * @param	string	$label		New label
	 * @return	int					Return integer <0 if KO, >0 if OK
	 */
	public function setLabel($label)
	{
		$sql = "UPDATE ".$this->db->prefix().$this->table_element." SET";
		$sql .= " passkey_label = ".($label !== '' ? "'".$this->db->escape($label)."'" : "NULL");
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::setLabel", LOG_DEBUG);
		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}
		$this->label = $label;

		return 1;
	}

	/**
	 * Delete the passkey
	 *
	 * @param	User	$user		User making the deletion
	 * @return	int					Return integer <0 if KO, >0 if OK
	 */
	public function delete($user)
	{
		$sql = "DELETE FROM ".$this->db->prefix().$this->table_element;
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}

		return 1;
	}

	/**
	 * Set the properties of the object from a database record
	 *
	 * @param	stdClass	$obj	Record of table llx_oauth_token with service 'passkey'
	 * @return	void
	 */
	public function setVarsFromFetchObj(&$obj)
	{
		$this->id = (int) $obj->rowid;
		$this->entity = (int) $obj->entity;
		$this->fk_user = (int) $obj->fk_user;
		$this->public_key = (string) $obj->tokenstring;
		$this->label = (string) $obj->passkey_label;
		$this->credential_id = (string) $obj->passkey_credential_id;
		$this->user_handle = (string) $obj->passkey_user_handle;
		$this->sign_count = (int) $obj->passkey_sign_count;
		$this->aaguid = (string) $obj->passkey_aaguid;
		$this->backup_eligible = ($obj->passkey_backup_eligible ? 1 : 0);
		$this->backup_state = ($obj->passkey_backup_state ? 1 : 0);
		$this->date_creation = $this->db->jdate($obj->datec);
		$this->date_last_use = $this->db->jdate($obj->lastaccess);
		$this->date_modification = $this->db->jdate($obj->tms);
	}
}
