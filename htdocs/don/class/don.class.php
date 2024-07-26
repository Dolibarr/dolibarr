<?php
/* Copyright (C) 2002      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2014      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2015-2017 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2016      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2019      Thibault FOUCART     <support@ptibogxiv.net>
 * Copyright (C) 2019-2024  Frédéric France      <frederic.france@free.fr>
 * Copyright (C) 2021      Maxime DEMAREST      <maxime@indelog.fr>
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
 *   	\file       htdocs/don/class/don.class.php
 *		\ingroup    Donation
 *		\brief      File of class to manage donations
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonpeople.class.php';


/**
 *		Class to manage donations
 */
class Don extends CommonObject
{
	use CommonPeople;

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'don';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'don';

	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_donation';

	/**
	 * @var string String with name of icon for object don. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'donation';

	/**
	 * @var int|'' Date of the donation
	 */
	public $date;

	/**
	 * @var int|'' Date of creation
	 */
	public $datec;

	/**
	 * @var int|'' Date of modification
	 */
	public $datem;

	/**
	 * @var int|'' date validation
	 */
	public $date_valid;

	/**
	 * amount of donation
	 * @var double
	 */
	public $amount;

	/**
	 * @var integer Thirdparty ID
	 */
	public $socid;

	/**
	 * @var string Thirdparty name
	 */
	public $societe;

	/**
	 * @var string Address
	 */
	public $address;

	/**
	 * @var string Zipcode
	 */
	public $zip;

	/**
	 * @var string Town
	 */
	public $town;

	/**
	 * @var string Email
	 */
	public $email;

	/**
	 * @var string phone
	 */
	public $phone;

	/**
	 * @var string phone mobile
	 */
	public $phone_mobile;

	/**
	 * @var string
	 */
	public $mode_reglement;

	/**
	 * @var string
	 */
	public $mode_reglement_code;

	/**
	 * @var int 0 or 1
	 */
	public $public;

	/**
	 * @var int project ID
	 */
	public $fk_project;

	/**
	 * @var int type payment ID
	 */
	public $fk_typepayment;

	/**
	 * @var string      Payment reference
	 *                  (Cheque or bank transfer reference. Can be "ABC123")
	 */
	public $num_payment;

	/**
	 * @var int payment mode id
	 */
	public $modepaymentid = 0;

	public $paid;

	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_PAID = 2;
	const STATUS_CANCELED = -1;


	/**
	 *  Constructor
	 *
	 *  @param	DoliDB	$db 	Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->ismultientitymanaged = 1;
	}


	/**
	 * 	Returns the donation status label (draft, valid, abandoned, paid)
	 *
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string        			Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the label of a given status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			$langs->load("donations");
			$this->labelStatus[-1] = $langs->transnoentitiesnoconv("Canceled");
			$this->labelStatus[0] = $langs->transnoentitiesnoconv("DonationStatusPromiseNotValidated");
			$this->labelStatus[1] = $langs->transnoentitiesnoconv("DonationStatusPromiseValidated");
			$this->labelStatus[2] = $langs->transnoentitiesnoconv("DonationStatusPaid");
			$this->labelStatusShort[-1] = $langs->transnoentitiesnoconv("Canceled");
			$this->labelStatusShort[0] = $langs->transnoentitiesnoconv("DonationStatusPromiseNotValidatedShort");
			$this->labelStatusShort[1] = $langs->transnoentitiesnoconv("DonationStatusPromiseValidatedShort");
			$this->labelStatusShort[2] = $langs->transnoentitiesnoconv("DonationStatusPaidShort");
		}

		$statusType = 'status'.$status;
		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status9';
		}
		if ($status == self::STATUS_PAID) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
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
		global $conf;

		$now = dol_now();

		// Charge tableau des id de societe socids
		$socids = array();

		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe";
		$sql .= " WHERE client IN (1, 3)";
		$sql .= " AND entity = ".$conf->entity;
		$sql .= " LIMIT 10";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num_socs = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_socs) {
				$row = $this->db->fetch_row($resql);
				$socids[$i] = $row[0];

				$i++;
			}
		}

		// Initialise parameters
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;
		$this->lastname = 'Doe';
		$this->firstname = 'John';
		$this->socid = empty($socids[0]) ? 0 : $socids[0];
		$this->date = $now;
		$this->date_valid = $now;
		$this->amount = 100.90;
		$this->public = 1;
		$this->societe = 'The Company';
		$this->address = 'Twist road';
		$this->zip = '99999';
		$this->town = 'Town';
		$this->note_private = 'Private note';
		$this->note_public = 'Public note';
		$this->email = 'email@email.com';
		$this->phone = '0123456789';
		$this->phone_mobile = '0606060606';
		$this->status = 1;

		return 1;
	}


	/**
	 *	Check params and init ->errors array.
	 *  TODO This function seems to not be used by core code.
	 *
	 *	@param	int	$minimum	Minimum
	 *	@return	int				0 if KO, >0 if OK
	 */
	public function check($minimum = 0)
	{
		global $langs;
		$langs->load('main');
		$langs->load('companies');

		$error_string = array();
		$err = 0;
		$amount_invalid = 0;

		if (dol_strlen(trim($this->societe)) == 0) {
			if ((dol_strlen(trim($this->lastname)) + dol_strlen(trim($this->firstname))) == 0) {
				$error_string[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Company').'/'.$langs->transnoentitiesnoconv('Firstname').'-'.$langs->transnoentitiesnoconv('Lastname'));
				$err++;
			}
		}

		if (dol_strlen(trim($this->address)) == 0) {
			$error_string[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Address'));
			$err++;
		}

		if (dol_strlen(trim($this->zip)) == 0) {
			$error_string[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Zip'));
			$err++;
		}

		if (dol_strlen(trim($this->town)) == 0) {
			$error_string[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Town'));
			$err++;
		}

		if (dol_strlen(trim($this->email)) == 0) {
			$error_string[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('EMail'));
			$err++;
		}

		$this->amount = (float) $this->amount;

		$map = range(0, 9);
		$len = dol_strlen((string) $this->amount);
		for ($i = 0; $i < $len; $i++) {
			if (!isset($map[substr((string) $this->amount, $i, 1)])) {
				$error_string[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Amount'));
				$err++;
				$amount_invalid = 1;
				break;
			}
		}

		if (!$amount_invalid) {
			if ($this->amount == 0) {
				$error_string[] = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Amount'));
				$err++;
			} else {
				if ($this->amount < $minimum && $minimum > 0) {
					$error_string[] = $langs->trans('MinimumAmount', $minimum);
					$err++;
				}
			}
		}

		if ($err) {
			$this->errors = $error_string;
			return 0;
		} else {
			return 1;
		}
	}

	/**
	 * Create donation record into database
	 *
	 * @param	User	$user		User who created the donation
	 * @param	int		$notrigger	Disable triggers
	 * @return  int  		        Return integer <0 if KO, id of created donation if OK
	 * TODO    add numbering module for Ref
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf, $langs;

		$error = 0;
		$ret = 0;
		$now = dol_now();

		// Clean parameters
		// $this->address = ($this->address > 0 ? $this->address : $this->address);
		// $this->zip = ($this->zip > 0 ? $this->zip : $this->zip);
		// $this->town = ($this->town > 0 ? $this->town : $this->town);
		// $this->country_id = ($this->country_id > 0 ? $this->country_id : $this->country_id);
		// $this->country = ($this->country ? $this->country : $this->country);
		$this->amount = (float) price2num($this->amount);

		// Check parameters
		if ($this->amount < 0) {
			$this->error = $langs->trans('FieldCannotBeNegative', $langs->transnoentitiesnoconv("Amount"));
			return -1;
		}

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."don (";
		$sql .= "datec";
		$sql .= ", entity";
		$sql .= ", amount";
		$sql .= ", fk_payment";
		$sql .= ", fk_soc";
		$sql .= ", firstname";
		$sql .= ", lastname";
		$sql .= ", societe";
		$sql .= ", address";
		$sql .= ", zip";
		$sql .= ", town";
		$sql .= ", fk_country";
		$sql .= ", public";
		$sql .= ", fk_projet";
		$sql .= ", note_private";
		$sql .= ", note_public";
		$sql .= ", fk_user_author";
		$sql .= ", fk_user_valid";
		$sql .= ", datedon";
		$sql .= ", email";
		$sql .= ", phone";
		$sql .= ", phone_mobile";
		$sql .= ") VALUES (";
		$sql .= "'".$this->db->idate($this->date ? $this->date : $now)."'";
		$sql .= ", ".((int) $conf->entity);
		$sql .= ", ".((float) $this->amount);
		$sql .= ", ".($this->modepaymentid ? $this->modepaymentid : "null");
		$sql .= ", ".($this->socid > 0 ? $this->socid : "null");
		$sql .= ", '".$this->db->escape($this->firstname)."'";
		$sql .= ", '".$this->db->escape($this->lastname)."'";
		$sql .= ", '".$this->db->escape($this->societe)."'";
		$sql .= ", '".$this->db->escape($this->address)."'";
		$sql .= ", '".$this->db->escape($this->zip)."'";
		$sql .= ", '".$this->db->escape($this->town)."'";
		$sql .= ", ".(int) ($this->country_id > 0 ? $this->country_id : 0);
		$sql .= ", ".(int) $this->public;
		$sql .= ", ".($this->fk_project > 0 ? (int) $this->fk_project : "null");
		$sql .= ", ".(!empty($this->note_private) ? ("'".$this->db->escape($this->note_private)."'") : "NULL");
		$sql .= ", ".(!empty($this->note_public) ? ("'".$this->db->escape($this->note_public)."'") : "NULL");
		$sql .= ", ".((int) $user->id);
		$sql .= ", null";
		$sql .= ", '".$this->db->idate($this->date)."'";
		$sql .= ", '".(!empty($this->email) ? $this->db->escape(trim($this->email)) : "")."'";
		$sql .= ", '".(!empty($this->phone) ? $this->db->escape(trim($this->phone)) : "")."'";
		$sql .= ", '".(!empty($this->phone_mobile) ? $this->db->escape(trim($this->phone_mobile)) : "")."'";
		$sql .= ")";

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."don");
			$ret = $this->id;

			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('DON_CREATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		} else {
			$this->error = $this->db->lasterror();
			$error++;
		}

		// Update extrafield
		if (!$error) {
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error && (getDolGlobalString('MAIN_DISABLEDRAFTSTATUS') || getDolGlobalString('MAIN_DISABLEDRAFTSTATUS_DONATION'))) {
			//$res = $this->setValid($user);
			//if ($res < 0) $error++;
		}

		if (!$error) {
			$this->db->commit();
			return $ret;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Update a donation record
	 *
	 *  @param 		User	$user   Object utilisateur qui met a jour le don
	 *  @param      int		$notrigger	Disable triggers
	 *  @return     int      		>0 if OK, <0 if KO
	 */
	public function update($user, $notrigger = 0)
	{
		global $langs;

		$error = 0;

		// Clean parameters
		// $this->address = ($this->address > 0 ? $this->address : $this->address);
		// $this->zip = ($this->zip > 0 ? $this->zip : $this->zip);
		// $this->town = ($this->town > 0 ? $this->town : $this->town);
		// $this->country_id = ($this->country_id > 0 ? $this->country_id : $this->country_id);
		// $this->country = ($this->country ? $this->country : $this->country);
		$this->amount = (float) price2num($this->amount);

		// Check parameters
		if ($this->amount < 0) {
			$this->error = $langs->trans('FieldCannotBeNegative', $langs->transnoentitiesnoconv("Amount"));
			return -1;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."don SET";
		$sql .= " amount = ".((float) $this->amount);
		$sql .= ", fk_payment = ".($this->modepaymentid ? $this->modepaymentid : "null");
		$sql .= ", firstname = '".$this->db->escape($this->firstname)."'";
		$sql .= ", lastname='".$this->db->escape($this->lastname)."'";
		$sql .= ", societe='".$this->db->escape($this->societe)."'";
		$sql .= ", address='".$this->db->escape($this->address)."'";
		$sql .= ", zip='".$this->db->escape($this->zip)."'";
		$sql .= ", town='".$this->db->escape($this->town)."'";
		$sql .= ", fk_country = ".($this->country_id > 0 ? ((int) $this->country_id) : '0');
		$sql .= ", public=".((int) $this->public);
		$sql .= ", fk_projet=".($this->fk_project > 0 ? $this->fk_project : 'null');
		$sql .= ", note_private=".(!empty($this->note_private) ? ("'".$this->db->escape($this->note_private)."'") : "NULL");
		$sql .= ", note_public=".(!empty($this->note_public) ? ("'".$this->db->escape($this->note_public)."'") : "NULL");
		$sql .= ", datedon='".$this->db->idate($this->date)."'";
		$sql .= ", date_valid=".($this->date_valid ? "'".$this->db->idate($this->date)."'" : "null");
		$sql .= ", email='".$this->db->escape(trim($this->email))."'";
		$sql .= ", phone='".$this->db->escape(trim($this->phone))."'";
		$sql .= ", phone_mobile='".$this->db->escape(trim($this->phone_mobile))."'";
		$sql .= ", fk_statut=".((int) $this->status);
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::Update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('DON_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			// Update extrafield
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error) {
				$this->db->commit();
				$result = 1;
			} else {
				$this->db->rollback();
				$result = -1;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			$this->db->rollback();
			dol_syslog(get_class($this)."::Update error -2 ".$this->error, LOG_ERR);
			$result = -2;
		}
		return $result;
	}

	/**
	 *    Delete a donation from database
	 *
	 *    @param       User		$user            User
	 *    @param       int		$notrigger       Disable triggers
	 *    @return      int       			      Return integer <0 if KO, 0 if not possible, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$this->db->begin();

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('DON_DELETE', $user);

			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Delete donation
		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."don_extrafields";
			$sql .= " WHERE fk_object = ".((int) $this->id);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->lasterror();
				$error++;
			}
		}

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."don";
			$sql .= " WHERE rowid=".((int) $this->id);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->lasterror();
				$error++;
			} else {
				// we delete file with dol_delete_dir_recursive
				$this->deleteEcmFiles(1);

				$dir = DOL_DATA_ROOT.'/'.$this->element.'/'.$this->ref;
				// For remove dir
				if (dol_is_dir($dir)) {
					if (!dol_delete_dir_recursive($dir)) {
						$this->errors[] = $this->error;
					}
				}
			}
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			dol_syslog(get_class($this)."::delete ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *      Load donation from database
	 *
	 *      @param      int		$id      Id of donation to load
	 *      @param      string	$ref        Ref of donation to load
	 *      @return     int      			Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id, $ref = '')
	{
		$sql = "SELECT d.rowid, d.datec, d.date_valid, d.tms as datem, d.datedon,";
		$sql .= " d.fk_soc as socid, d.firstname, d.lastname, d.societe, d.amount, d.fk_statut as status, d.address, d.zip, d.town, ";
		$sql .= " d.fk_country, d.public, d.amount, d.fk_payment, d.paid, d.note_private, d.note_public, d.email, d.phone, ";
		$sql .= " d.phone_mobile, d.fk_projet as fk_project, d.model_pdf,";
		$sql .= " p.ref as project_ref,";
		$sql .= " cp.libelle as payment_label, cp.code as payment_code,";
		$sql .= " c.code as country_code, c.label as country";
		$sql .= " FROM ".MAIN_DB_PREFIX."don as d";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = d.fk_projet";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as cp ON cp.id = d.fk_payment";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON d.fk_country = c.rowid";
		$sql .= " WHERE d.entity IN (".getEntity('donation').")";
		if (!empty($id)) {
			$sql .= " AND d.rowid=".((int) $id);
		} elseif (!empty($ref)) {
			$sql .= " AND d.ref='".$this->db->escape($ref)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id                 = $obj->rowid;
				$this->ref                = $obj->rowid;
				$this->date_creation      = $this->db->jdate($obj->datec);
				$this->datec              = $this->db->jdate($obj->datec);
				$this->date_validation    = $this->db->jdate($obj->date_valid);
				$this->date_valid = $this->db->jdate($obj->date_valid);
				$this->date_modification  = $this->db->jdate($obj->datem);
				$this->datem              = $this->db->jdate($obj->datem);
				$this->date               = $this->db->jdate($obj->datedon);
				$this->socid              = $obj->socid;
				$this->firstname          = $obj->firstname;
				$this->lastname           = $obj->lastname;
				$this->societe            = $obj->societe;
				$this->status             = $obj->status;
				$this->statut             = $obj->status;
				$this->address            = $obj->address;
				$this->zip                = $obj->zip;
				$this->town               = $obj->town;
				$this->country_id         = $obj->fk_country;
				$this->country_code       = $obj->country_code;
				$this->country            = $obj->country;
				$this->email              = $obj->email;
				$this->phone              = $obj->phone;
				$this->phone_mobile       = $obj->phone_mobile;
				$this->project            = $obj->project_ref;
				$this->fk_projet          = $obj->fk_project; // deprecated
				$this->fk_project         = $obj->fk_project;
				$this->public             = $obj->public;
				$this->mode_reglement_id  = $obj->fk_payment;
				$this->mode_reglement_code = $obj->payment_code;
				$this->mode_reglement     = $obj->payment_label;
				$this->paid = $obj->paid;
				$this->amount             = $obj->amount;
				$this->note_private	      = $obj->note_private;
				$this->note_public = $obj->note_public;
				$this->model_pdf          = $obj->model_pdf;

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();
			}
			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *	Validate a intervention
	 *
	 *	@param		User		$user		User that validate
	 *  @param		int			$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return		int						Return integer <0 if KO, >0 if OK
	 */
	public function setValid($user, $notrigger = 0)
	{
		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		return $this->valid_promesse($this->id, $user->id, $notrigger);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Validate a promise of donation
	 *
	 *    @param	int		$id   		id of donation
	 *    @param  	int		$userid  	User who validate the donation/promise
	 *    @param	int		$notrigger	Disable triggers
	 *    @return   int     			Return integer <0 if KO, >0 if OK
	 */
	public function valid_promesse($id, $userid, $notrigger = 0)
	{
		// phpcs:enable
		global $user;

		$error = 0;

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."don SET fk_statut = 1, fk_user_valid = ".((int) $userid)." WHERE rowid = ".((int) $id)." AND fk_statut = 0";

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->affected_rows($resql)) {
				if (!$notrigger) {
					// Call trigger
					$result = $this->call_trigger('DON_VALIDATE', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}
			}
		} else {
			$error++;
			$this->error = $this->db->lasterror();
		}

		if (!$error) {
			$this->status = 1;
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *    Classify the donation as paid, the donation was received
	 *
	 *    @param	int		$id           	    id of donation
	 *    @param    int		$modepayment   	    mode of payment
	 *    @return   int      					Return integer <0 if KO, >0 if OK
	 */
	public function setPaid($id, $modepayment = 0)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."don SET fk_statut = 2, paid = 1";
		if ($modepayment) {
			$sql .= ", fk_payment = ".((int) $modepayment);
		}
		$sql .= " WHERE rowid = ".((int) $id)." AND fk_statut = 1";

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->affected_rows($resql)) {
				$this->status = 2;
				$this->paid = 1;
				return 1;
			} else {
				return 0;
			}
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Set donation to status cancelled
	 *
	 *    @param	int		$id   	    id of donation
	 *    @return   int     			Return integer <0 if KO, >0 if OK
	 */
	public function set_cancel($id)
	{
		// phpcs:enable
		$sql = "UPDATE ".MAIN_DB_PREFIX."don SET fk_statut = -1 WHERE rowid = ".((int) $id);

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->affected_rows($resql)) {
				$this->status = -1;
				return 1;
			} else {
				return 0;
			}
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *	Set cancel status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function reopen($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_CANCELED) {
			return 0;
		}

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'DON_REOPEN');
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Sum of donations
	 *
	 *	@param	string	$param	1=promesses de dons validees , 2=xxx, 3=encaisses
	 *	@return	int				Summ of donations
	 */
	public function sum_donations($param)
	{
		// phpcs:enable
		global $conf;

		$result = 0;

		$sql = "SELECT sum(amount) as total";
		$sql .= " FROM ".MAIN_DB_PREFIX."don";
		$sql .= " WHERE fk_statut = ".((int) $param);
		$sql .= " AND entity = ".$conf->entity;

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$result = $obj->total;
		}

		return $result;
	}

	/**
	 *	Load the indicators this->nb for the state board
	 *
	 *	@return     int         Return integer <0 if KO, >0 if OK
	 */
	public function loadStateBoard()
	{
		$this->nb = array();

		$sql = "SELECT count(d.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."don as d";
		$sql .= " WHERE d.fk_statut > 0";
		$sql .= " AND d.entity IN (".getEntity('donation').")";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["donations"] = $obj->nb;
			}
			$this->db->free($resql);
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param	int		$withpicto					0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param	int  	$notooltip					1=Disable tooltip
	 *	@param	string	$moretitle					Add more text to title tooltip
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								Chaine avec URL
	 */
	public function getNomUrl($withpicto = 0, $notooltip = 0, $moretitle = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		$label = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Donation").'</u>';
		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		if (!empty($this->id)) {
			$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->id;
			$label .= '<br><b>'.$langs->trans('Date').':</b> '.dol_print_date($this->date, 'day');
		}
		if ($moretitle) {
			$label .= ' - '.$moretitle;
		}

		$url = DOL_URL_ROOT.'/don/card.php?id='.$this->id;

		$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
			$add_save_lastsearch_values = 1;
		}
		if ($add_save_lastsearch_values) {
			$url .= '&save_lastsearch_values=1';
		}

		$linkstart = '<a href="'.$url.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), $this->picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;
		global $action;
		$hookmanager->initHooks(array($this->element . 'dao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}
		return $result;
	}

	/**
	 * Information on record
	 *
	 * @param	int		$id      Id of record
	 * @return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT d.rowid, d.datec, d.fk_user_author, d.fk_user_valid,';
		$sql .= ' d.tms as datem';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'don as d';
		$sql .= ' WHERE d.rowid = '.((int) $id);

		dol_syslog(get_class($this).'::info', LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_author;
				$this->user_validation_id = $obj->fk_user_valid;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = (!empty($obj->tms) ? $this->db->jdate($obj->tms) : "");
			}
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}


	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	object lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $conf, $langs;

		$langs->load("bills");

		if (!dol_strlen($modele)) {
			$modele = 'html_cerfafr';

			if ($this->model_pdf) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('DON_ADDON_MODEL')) {
				$modele = getDolGlobalString('DON_ADDON_MODEL');
			}
		}

		//$modelpath = "core/modules/dons/";

		// TODO Restore use of commonGenerateDocument instead of dedicated code here
		//return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);

		// Increase limit for PDF build
		$err = error_reporting();
		error_reporting(0);
		@set_time_limit(120);
		error_reporting($err);

		$srctemplatepath = '';

		// If selected modele is a filename template (then $modele="modelname:filename")
		$tmp = explode(':', $modele, 2);
		if (!empty($tmp[1])) {
			$modele = $tmp[0];
			$srctemplatepath = $tmp[1];
		}

		// Search template files
		$file = '';
		$classname = '';
		$filefound = 0;
		$dirmodels = array('/');
		if (is_array($conf->modules_parts['models'])) {
			$dirmodels = array_merge($dirmodels, $conf->modules_parts['models']);
		}
		foreach ($dirmodels as $reldir) {
			foreach (array('html', 'doc', 'pdf') as $prefix) {
				$file = $prefix."_".preg_replace('/^html_/', '', $modele).".modules.php";

				// Verify the path for the module
				$file = dol_buildpath($reldir."core/modules/dons/".$file, 0);
				if (file_exists($file)) {
					$filefound = 1;
					$classname = $prefix.'_'.$modele;
					break;
				}
			}
			if ($filefound) {
				break;
			}
		}

		// Charge le modele
		if ($filefound) {
			require_once $file;

			$object = $this;

			$classname = $modele;
			$obj = new $classname($this->db);

			// We save charset_output to restore it because write_file can change it if needed for
			// output format that does not support UTF8.
			$sav_charset_output = $outputlangs->charset_output;
			if ($obj->write_file($object, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref) > 0) {
				$outputlangs->charset_output = $sav_charset_output;

				// we delete preview files
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
				dol_delete_preview($object);
				return 1;
			} else {
				$outputlangs->charset_output = $sav_charset_output;
				dol_syslog("Erreur dans don_create");
				dol_print_error($this->db, $obj->error);
				return 0;
			}
		} else {
			print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists", $file);
			return 0;
		}
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param 	DoliDB 	$dbs 		Database handler, because function is static we name it $dbs not $db to avoid breaking coding test
	 * @param 	int 	$origin_id 	Old thirdparty id
	 * @param 	int 	$dest_id 	New thirdparty id
	 * @return 	bool
	 */
	public static function replaceThirdparty(DoliDB $dbs, $origin_id, $dest_id)
	{
		$tables = array(
			'don'
		);

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}

	/**
	 * Function to get remaining amount to pay for a donation
	 *
	 * @return   float|int<-2,-1>      					Return integer <0 if KO, > remaining amount to pay if  OK
	 */
	public function getRemainToPay()
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		if (empty($this->id)) {
			$this->error = 'Missing object id';
			$this->errors[] = $this->error;
			dol_syslog(__METHOD__.' : '.$this->error, LOG_ERR);
			return -1;
		}

		$sql = "SELECT SUM(amount) as sum_amount FROM ".MAIN_DB_PREFIX."payment_donation WHERE fk_donation = ".((int) $this->id);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			return -2;
		} else {
			$sum_amount = (float) $this->db->fetch_object($resql)->sum_amount;
			return (float) ($this->amount - $sum_amount);
		}
	}

	/**
	 *	Return clicable link of object (with eventually picto)
	 *
	 *	@param      string	    $option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		array		$arraydata				Array of data
	 *  @return		string								HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $conf, $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl(1) : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'date')) {
			$return .= ' &nbsp; | &nbsp; <span class="info-box-label">'.dol_print_date($this->date, 'day', 'tzuserrel').'</span>';
		}
		if (property_exists($this, 'societe') && !empty($this->societe)) {
			$return .= '<br><span class="opacitymedium">'.$langs->trans("Company").'</span> : <span class="info-box-label">'.$this->societe.'</span>';
		}
		if (property_exists($this, 'amount')) {
			$return .= '<br><span class="info-box-label amount">'.price($this->amount, 1, $langs, 1, -1, -1, $conf->currency).'</span>';
		}
		if (method_exists($this, 'LibStatut')) {
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}
}
