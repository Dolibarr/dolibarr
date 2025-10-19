<?php
/* Copyright (C) 2025		Cloned from htdocs/comm/mailing/class/mailing.class.php then modified
 * Copyright (C) 2025		Jon Bendtsen <jon.bendtsen.github@jonb.dk>
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
 *	\file       htdocs/comm/mailing/class/mailing_target.class.php
 *	\ingroup    mailing
 *	\brief      File of class to manage emailing targets module
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class to manage emailings module
 */
class MailingTarget extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'mailing_target';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'mailing_cibles';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'contact';

	/**
	 * @var int Mailing id that this mailing_target is related to.
	 */
	public $fk_mailing;

	/**
	 * @var int Contact id that this mailing_target is related to.
	 */
	public $fk_contact;

	/**
	 * @var string lastname of the mailing_target
	 */
	public $lastname;

	/**
	 * @var string firstname of the mailing_target
	 */
	public $firstname;

	/**
	 * @var string email of the mailing_target
	 */
	public $email;

	/**
	 * @var	string other
	 */
	public $other;

	/**
	 * @var	string tag
	 */
	public $tag;

	/**
	 * @var int status
	 * @deprecated Use $status
	 */
	public $statut; // Status 0=Not sent, 1=Sent, 2=Read, 3=Read and unsubscribed, -1=Error

	/**
	 * @var int status
	 */
	public $status; // Status 0=Not sent, 1=Sent, 2=Read, 3=Read and unsubscribed, -1=Error

	/**
	 * @var array<int,string> statut dest
	 */
	public $statut_dest = array();

	/**
	 * @var string source_url of the mailing_target
	 */
	public $source_url;

	/**
	 * @var int source_id of the mailing_target
	 */
	public $source_id;

	/**
	 * @var string source_type
	 */
	public $source_type;

	/**
	 * @var integer|''|null		date sending
	 */
	public $date_envoi;

	/**
	 * Update timestamp record (tms)
	 * @var integer
	 * @deprecated					Use $date_modification
	 */
	public $tms;

	/**
	 * @var string error_text from trying to send email
	 */
	public $error_text;

	const STATUS_NOTSENT = 0;
	const STATUS_SENT = 1;
	const STATUS_READ = 2;
	const STATUS_READANDUNSUBSCRIBED = 3;
	const STATUS_ERROR = -1;

	/**
	 *  Constructor
	 *
	 *  @param      DoliDB      $db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		// List of language codes for status
		$this->labelStatus[0] = 'TargetStatusNotSent';
		$this->labelStatus[1] = 'TargetStatusSent';
		$this->labelStatus[2] = 'TargetStatusRead';
		$this->labelStatus[3] = 'TargetStatusReadAndUnsubscribed';
		$this->labelStatus[-1] = 'TargetStatusError';

		$this->statut_dest[0] = 'TargetStatusNotSent';
		$this->statut_dest[1] = 'TargetStatusSent';
		$this->statut_dest[2] = 'TargetStatusRead';
		$this->statut_dest[3] = 'TargetStatusReadAndUnsubscribed'; // Read but ask to not be contacted anymore
		$this->statut_dest[-1] = 'TargetStatusError';
	}

	/**
	 *  Create an Mailing Target
	 *
	 *  @param	User	$user 		Object of user making creation
	 *  @return int				    Return integer <0 if KO, Id of created object if OK
	 */
	public function create($user)
	{
		global $conf, $langs;

		if (empty($this->fk_mailing)) {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Mailing"));
			return -2;
			// we probably should also check that this number actually exists in ".MAIN_DB_PREFIX."mailing";
		}
		if (0 == $this->fk_mailing) {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Mailing"));
			return -4;
		}
		if (empty($this->email)) {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Email"));
			return -3;
		}
		if (empty($this->statut)) {
			$statut = 0;
		}
		if (empty($this->status)) {
			$status = 0;
		}
		if ($this->status !== $this->statut) {
			$this->error = 'Status='.$this->status.' and Statut='.$this->statut.' field must be identical';
			return -4;
		}
		if (empty($this->fk_contact)) {
			$fk_contact = 0;
		}

		$error = 0;

		$this->db->begin();


		// 2025-10-09 06:33:26 DEBUG   192.168.127.1        52     33  sql=INSERT INTO llx_mailing_cibles (fk_mailing, fk_contact, email, statut) VALUES ('4',  .((int) 0)., 'jon@jonb.dk',  .((int) )).
		//2025-10-09 06:35:13 DEBUG   192.168.127.1        54     33  sql=INSERT INTO llx_mailing_cibles (fk_mailing, fk_contact, email, statut) VALUES (4,  .((int) 0)., 'jon@jonb.dk',  .((int) )).

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."mailing_cibles";
		$sql .= " (fk_mailing, fk_contact, email, statut)";
		$sql .= " VALUES (".((int) $this->fk_mailing).", ";
		$sql .=  ((int) $this->fk_contact).", ";
		$sql .= "'".$this->db->escape($this->email)."', ";
		$sql .=  ((int) $conf->statut)." )";

		dol_syslog(__METHOD__, LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."mailing_cibles");

			$result = $this->update($user);
			if ($result < 0) {
				$error++;
			}

			if (!$error) {
				$this->db->commit();
				return $this->id;
			} else {
				$this->db->rollback();
				dol_syslog(__METHOD__ . ' ' . $this->error, LOG_ERR);
				return -2;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Delete Mailing target
	 *
	 *  @param	User	$user		User that delete
	 *  @return int         		>0 if OK, <0 if KO
	 */
	public function delete($user)
	{
		$error = 0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."mailing_cibles";
		$sql .= " WHERE rowid = " . ((int) $this->id);

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			dol_syslog(__METHOD__ . ' success');
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Set notsent mailing target
	 *
	 * 	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function setNotSent()
	{
		$now = dol_now();

		$sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles ";
		$sql .= " SET statut = ".((int) self::STATUS_NOTSENT).", tms = '".$this->db->idate($now)."'";
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog("Mailing::valid", LOG_DEBUG);
		if ($this->db->query($sql)) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Set sent mailing target
	 *
	 * 	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function setSent()
	{
		$now = dol_now();

		$sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles ";
		$sql .= " SET statut = ".((int) self::STATUS_SENT).", tms = '".$this->db->idate($now)."'";
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog("Mailing::valid", LOG_DEBUG);
		if ($this->db->query($sql)) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Set read mailing target
	 *
	 * 	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function setRead()
	{
		$now = dol_now();

		$sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles ";
		$sql .= " SET statut = ".((int) self::STATUS_READ).", tms = '".$this->db->idate($now)."'";
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog("Mailing::valid", LOG_DEBUG);
		if ($this->db->query($sql)) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Set read and unsubscribed mailing target
	 *
	 * 	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function setReadAndUnsubscribed()
	{
		$now = dol_now();

		$sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles ";
		$sql .= " SET statut = ".((int) self::STATUS_READANDUNSUBSCRIBED).", tms = '".$this->db->idate($now)."'";
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog("Mailing::valid", LOG_DEBUG);
		if ($this->db->query($sql)) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Set error mailing target
	 *
	 * 	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function setError()
	{
		$now = dol_now();

		$sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles ";
		$sql .= " SET statut = ".((int) self::STATUS_ERROR).", tms = '".$this->db->idate($now)."'";
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog("Mailing::valid", LOG_DEBUG);
		if ($this->db->query($sql)) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Update an Mailing Target
	 *
	 *  @param  User	$user 		Object of user making change
	 *  @return int				    Return integer < 0 if KO, > 0 if OK
	 */
	public function update($user)
	{
		global $langs;

		if (empty($this->fk_mailing)) {
			return -2;
			// we probably should also check that this number actually exists in ".MAIN_DB_PREFIX."mailing";
		}
		if (empty($this->email)) {
			return -3;
		}
		if (empty($this->statut)) {
			$statut = 0;
		}
		if (empty($this->status)) {
			$status = 0;
		}
		if ($this->status !== $this->statut) {
			return -4;
		}
		if (empty($this->fk_contact)) {
			$fk_contact = 0;
		}

		$now = dol_now();
		$error = 0;
		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
		$sql .= " SET fk_mailing = '".((int) $this->fk_mailing)."'";
		$sql .= ", fk_contact = '".((int) $this->fk_contact)."'";
		$sql .= ", lastname = '".$this->db->escape($this->lastname)."'";
		$sql .= ", firstname = '".$this->db->escape($this->firstname)."'";
		$sql .= ", email = '".$this->db->escape($this->email)."'";
		$sql .= ", other = '".$this->db->escape($this->other)."'";
		$sql .= ", tag = '".$this->db->escape($this->tag)."'";
		$sql .= ", statut = '".((int) $this->statut)."'";
		$sql .= ", source_url = '".$this->db->escape($this->source_url)."'";
		$sql .= ", source_id = '".((int) $this->source_id)."'";
		$sql .= ", source_type = '".$this->db->escape($this->source_type)."'";
		if ($this->date_envoi) {
			$sql .= ", date_envoi = '".$this->db->idate($this->date_envoi)."'";
		}
		$sql .= ", error_text = '".($this->error_text ? $this->db->escape($this->error_text) : null)."'";
		$sql .= " WHERE rowid = ".(int) $this->id;

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			dol_syslog(__METHOD__ . ' success');
			$this->db->commit();
			return 1;
		} else {
			if ($this->db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				$this->error = $langs->trans("ErrorRecordAlreadyExists", $this->email);
			} else {
				$this->error = $this->db->lasterror();
			}
			$this->db->rollback();
			return -6;
		}
	}

	/**
	 *	Get object from database
	 *
	 *	@param	int		$rowid      Id of Mailing Target
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		$sql = "SELECT t.rowid";
		$sql .= ", t.fk_mailing";
		$sql .= ", t.fk_contact";
		$sql .= ", t.lastname";
		$sql .= ", t.firstname";
		$sql .= ", t.email";
		$sql .= ", t.other";
		$sql .= ", t.tag";
		$sql .= ", t.statut as status";
		$sql .= ", t.source_url";
		$sql .= ", t.source_id";
		$sql .= ", t.source_type";
		$sql .= ", t.date_envoi";
		$sql .= ", t.tms as date_modification";
		$sql .= ", t.error_text";
		$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as t";
		$sql .= " WHERE t.rowid = ".(int) $rowid;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;
				$this->fk_mailing = $obj->fk_mailing;
				$this->fk_contact = $obj->fk_contact;
				$this->lastname = $obj->lastname;
				$this->firstname = $obj->firstname;
				$this->email = $obj->email;
				$this->other = $obj->other;
				$this->tag = $obj->tag;
				$this->statut = $obj->status;	// deprecated
				$this->status = $obj->status;
				$this->source_url = $obj->source_url;
				$this->source_id = $obj->source_id;
				$this->source_type = $obj->source_type;
				$this->date_envoi = $this->db->jdate($obj->date_envoi);
				$this->date_modification = $this->db->jdate($obj->date_modification); // tms
				$this->tms = $this->db->jdate($obj->date_modification); // tms
				$this->error_text = $obj->error_text;

				return 1;
			} else {
				dol_syslog(get_class($this)."::fetch Error -1");
				return -1;
			}
		} else {
			dol_syslog(get_class($this)."::fetch Error -2");
			return -2;
		}
	}
}
