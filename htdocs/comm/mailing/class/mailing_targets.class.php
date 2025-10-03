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
		$this->statut_dest[3] = 'TargetStatusReadAndUnsubscribe'; // Read but unsubscribed
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

		// Check properties
		if (preg_match('/^InvalidHTMLStringCantBeCleaned/', $this->body)) {
			$this->error = 'InvalidHTMLStringCantBeCleaned';
			return -1;
		}

		if (empty($this->fk_mailing)) {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Mailing"));
			return -2;
			// we probably should also check that this number actually exists in ".MAIN_DB_PREFIX."mailing";
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
		if ($status != $statut) {
			return -4;
		}
		if (empty($this->fk_contact)) {
			$fk_contact = 0;
		}

		$error = 0;
		$now = dol_now();

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."mailing_target";
		$sql .= " (fk_mailing, fk_contact, email, statut)";
		$sql .= " VALUES ('".$this->db->escape($this->fk_mailing)."', "((int) $this->fk_contact).", '".$this->db->escape($this->email)."', ".((int) $conf->statut).")";

		dol_syslog(__METHOD__, LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."mailing_target");

			$result = $this->update($user, 1);
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
	 *  Update an Mailing Target
	 *
	 *  @param  User	$user 		Object of user making change
	 *  @return int				    Return integer < 0 if KO, > 0 if OK
	 */
	public function update($user)
	{
		global $langs;

		// Check properties
		if (preg_match('/^InvalidHTMLStringCantBeCleaned/', $this->body)) {
			$this->error = 'InvalidHTMLStringCantBeCleaned';
			return -1;
		}

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
		if ($status != $statut) {
			return -4;
		}
		if (empty($this->fk_contact)) {
			$fk_contact = 0;
		}

		$error = 0;
		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."mailing_target";
		$sql .= " SET fk_mailing = '".$this->db->escape($this->fk_mailing)."'";
		$sql .= ", fk_contact = '".$this->db->escape($this->fk_contact)."'";
		$sql .= ", lastname = '".$this->db->escape($this->lastname)."'";
		$sql .= ", firstname = '".$this->db->escape($this->firstname)."'";
		$sql .= ", email = '".$this->db->escape($this->email)."'";
		$sql .= ", other = '".$this->db->escape($this->other)."'";
		$sql .= ", tag = '".$this->db->escape($this->tag)."'";
		$sql .= ", statut = '".$this->db->escape($this->statut)."'";
		$sql .= ", source_url = '".$this->db->escape($this->source_url)."'";
		$sql .= ", source_id = '".($this->source_id ? $this->db->escape($this->source_id) : null)."'";
		$sql .= ", source_type = '".$this->db->escape($this->source_type)."'";
		$sql .= ", date_envoi = '".($this->date_envoi ? $this->db->escape($this->date_envoi) : null)."'";
		$sql .= ", tms = '".$this->db->idate($now)."'";
		$sql .= ", error_text = '".($this->error_text ? $this->db->escape($this->error_text) : null)."'";
		$sql .= " WHERE rowid = ".(int) $this->id;

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if (!$error) {
				dol_syslog(__METHOD__ . ' success');
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				dol_syslog(__METHOD__ . ' ' . $this->error, LOG_ERR);
				return -5;
			}
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
		// $sql .= " WHERE entity IN (".getEntity('mailing_target').")";

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;
				// $this->entity = $obj->entity;
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
