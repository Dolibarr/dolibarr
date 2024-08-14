<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2020 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015-2020 Charlene Benke       <charlie@patas-monkey.com>
 * Copyright (C) 2018      Nicolas ZABOURI	    <info@inovea-conseil.com>
 * Copyright (C) 2018-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2023-2024  William Mead        <william.mead@manchenumerique.fr>
 * Copyright (C) 2024		MDW					<mdeweerd@users.noreply.github.com>
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
 * 	\file       htdocs/fichinter/class/fichinter.class.php
 * 	\ingroup    fichinter
 * 	\brief      File for class to manage interventions
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';


/**
 *	Class to manage interventions
 */
class Fichinter extends CommonObject
{
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 10),
		'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'ThirdParty', 'enabled' => 'isModEnabled("societe")', 'visible' => -1, 'notnull' => 1, 'position' => 15),
		'fk_projet' => array('type' => 'integer:Project:projet/class/project.class.php:1:(fk_statut:=:1)', 'label' => 'Fk projet', 'enabled' => 'isModEnabled("project")', 'visible' => -1, 'position' => 20),
		'fk_contrat' => array('type' => 'integer', 'label' => 'Fk contrat', 'enabled' => '$conf->contrat->enabled', 'visible' => -1, 'position' => 25),
		'ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'showoncombobox' => 1, 'position' => 30),
		'ref_ext' => array('type' => 'varchar(255)', 'label' => 'Ref ext', 'enabled' => 1, 'visible' => 0, 'position' => 35),
		'ref_client' => array('type' => 'varchar(255)', 'label' => 'RefCustomer', 'enabled' => 1, 'visible' => -1, 'position' => 36),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 40, 'index' => 1),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 45),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -1, 'position' => 50),
		'date_valid' => array('type' => 'datetime', 'label' => 'DateValidation', 'enabled' => 1, 'visible' => -1, 'position' => 55),
		'datei' => array('type' => 'date', 'label' => 'Datei', 'enabled' => 1, 'visible' => -1, 'position' => 60),
		'fk_user_author' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'Fk user author', 'enabled' => 1, 'visible' => -1, 'position' => 65),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'visible' => -2, 'notnull' => -1, 'position' => 70),
		'fk_user_valid' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserValidation', 'enabled' => 1, 'visible' => -1, 'position' => 75),
		'dateo' => array('type' => 'date', 'label' => 'Dateo', 'enabled' => 1, 'visible' => -1, 'position' => 85),
		'datee' => array('type' => 'date', 'label' => 'Datee', 'enabled' => 1, 'visible' => -1, 'position' => 90),
		'datet' => array('type' => 'date', 'label' => 'Datet', 'enabled' => 1, 'visible' => -1, 'position' => 95),
		'duree' => array('type' => 'double', 'label' => 'Duree', 'enabled' => 1, 'visible' => -1, 'position' => 100),
		'signed_status' => array('type' => 'smallint(6)', 'label' => 'SignedStatus', 'enabled' => 1, 'visible' => -1, 'position' => 101, 'arrayofkeyval' => array(0 => 'NoSignature', 1 => 'SignedSender', 2 => 'SignedReceiver', 9 => 'SignedAll')),
		'description' => array('type' => 'html', 'label' => 'Description', 'enabled' => 1, 'visible' => -1, 'position' => 105, 'showoncombobox' => 2),
		'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => 1, 'visible' => 0, 'position' => 110),
		'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => 1, 'visible' => 0, 'position' => 115),
		'model_pdf' => array('type' => 'varchar(255)', 'label' => 'Model pdf', 'enabled' => 1, 'visible' => 0, 'position' => 120),
		'last_main_doc' => array('type' => 'varchar(255)', 'label' => 'Last main doc', 'enabled' => 1, 'visible' => -1, 'position' => 125),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'position' => 130),
		'extraparams' => array('type' => 'varchar(255)', 'label' => 'Extraparams', 'enabled' => 1, 'visible' => -1, 'position' => 135),
		'fk_statut' => array('type' => 'integer', 'label' => 'Fk statut', 'enabled' => 1, 'visible' => -1, 'position' => 500),
	);

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'fichinter';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'fichinter';

	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_fichinter';

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = 'fichinterdet';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'intervention';

	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'ref';

	/**
	 * @var int Thirdparty Id
	 */
	public $socid;

	public $author;

	/**
	 * @var null|int|'' Date creation record (datec)
	 */
	public $datec;

	/**
	 * @var null|int|'' Date record (datev)
	 */
	public $datev;
	/**
	 * @var null|int|'' Date record (dateo)
	 */
	public $dateo;
	/**
	 * @var null|int|'' Date record (datee)
	 */
	public $datee;

	/**
	 * @var null|int|'' Date t record (datet)
	 */
	public $datet;

	/**
	 * Date modification record (tms)
	 *
	 * @var integer
	 */
	public $datem;

	/**
	 * @var int duration
	 */
	public $duration;

	/**
	 * @var int status
	 */
	public $statut = 0; // 0=draft, 1=validated, 2=invoiced, 3=Terminate

	/**
	 * Signed Status of the intervention (0=NoSignature, 1=SignedBySender, 2=SignedByReceiver, 9=SignedByAll)
	 * @var int
	 */
	public $signed_status = 0;

	/**
	 * @var string description
	 */
	public $description;

	/**
	 * @var int Contract ID
	 */
	public $fk_contrat = 0;

	/**
	 * @var int Project ID
	 */
	public $fk_project = 0;

	/**
	 * Customer Ref
	 * @var string
	 */
	public $ref_client;

	/**
	 * @var array extraparams
	 */
	public $extraparams = array();

	/**
	 * @var FichinterLigne[] lines
	 */
	public $lines = array();

	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;

	/**
	 * Validated status
	 */
	const STATUS_VALIDATED = 1;

	/**
	 * Billed
	 */
	const STATUS_BILLED = 2;

	/**
	 * Closed
	 */
	const STATUS_CLOSED = 3;


	/**
	 * No signature
	 */
	const STATUS_NO_SIGNATURE    = 0;

	/**
	 * Signed by sender
	 */
	const STATUS_SIGNED_SENDER   = 1;

	/**
	 * Signed by receiver
	 */
	const STATUS_SIGNED_RECEIVER = 2;

	/**
	 * Signed by all
	 */
	const STATUS_SIGNED_ALL      = 9; // To handle future kind of signature (ex: tripartite contract)

	/**
	 * Signed statuses dictionary. Label used as key for string localizations.
	 */
	const SIGNED_STATUSES = [
		'STATUS_NO_SIGNATURE' => 0,
		'STATUS_SIGNED_SENDER' => 1,
		'STATUS_SIGNED_RECEIVER' => 2,
		'STATUS_SIGNED_ALL' => 9 // To handle future kind of signature (ex: tripartite contract)
	];

	/**
	 * Date delivery
	 * @var null|int|''		Delivery int
	 */
	public $date_delivery;

	/**
	 * Author Id
	 * @var int
	 */
	public $user_author_id;


	/**
	 *	Constructor
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *  Load indicators into this->nb for board
	 *
	 *  @return     int         Return integer <0 if KO, >0 if OK
	 */
	public function loadStateBoard()
	{
		global $user;

		$this->nb = array();
		$clause = "WHERE";

		$sql = "SELECT count(fi.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."fichinter as fi";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON fi.fk_soc = s.rowid";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql .= " WHERE sc.fk_user = ".((int) $user->id);
			$clause = "AND";
		}
		$sql .= " ".$clause." fi.entity IN (".getEntity('intervention').")";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["interventions"] = $obj->nb;
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
	 *	Create an intervention into data base
	 *
	 *  @param		User	$user 		Object user that make creation
	 *	@param		int		$notrigger	Disable all triggers
	 *	@return		int		Return integer <0 if KO, >0 if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf, $langs;

		$error = 0;

		dol_syslog(get_class($this)."::create ref=".$this->ref);

		// Check parameters
		if (!empty($this->ref)) {	// We check that ref is not already used
			$result = self::isExistingObject($this->element, 0, $this->ref); // Check ref is not yet used
			if ($result > 0) {
				$this->error = 'ErrorRefAlreadyExists';
				dol_syslog(get_class($this)."::create ".$this->error, LOG_WARNING);
				$this->db->rollback();
				return -1;
			}
		}
		if (!is_numeric($this->duration)) {
			$this->duration = 0;
		}
		if (isset($this->ref_client)) {
			$this->ref_client = trim($this->ref_client);
		}

		if ($this->socid <= 0) {
			$this->error = 'ErrorFicheinterCompanyDoesNotExist';
			dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
			return -1;
		}

		$soc = new Societe($this->db);
		$result = $soc->fetch($this->socid);

		$now = dol_now();

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."fichinter (";
		$sql .= "fk_soc";
		$sql .= ", datec";
		$sql .= ", ref";
		$sql .= ", ref_client";
		$sql .= ", entity";
		$sql .= ", fk_user_author";
		$sql .= ", fk_user_modif";
		$sql .= ", description";
		$sql .= ", model_pdf";
		$sql .= ", fk_projet";
		$sql .= ", fk_contrat";
		$sql .= ", fk_statut";
		$sql .= ", signed_status";
		$sql .= ", note_private";
		$sql .= ", note_public";
		$sql .= ") ";
		$sql .= " VALUES (";
		$sql .= $this->socid;
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ", '".$this->db->escape($this->ref)."'";
		$sql .= ", ".($this->ref_client ? "'".$this->db->escape($this->ref_client)."'" : "null");
		$sql .= ", ".((int) $conf->entity);
		$sql .= ", ".((int) $user->id);
		$sql .= ", ".((int) $user->id);
		$sql .= ", ".($this->description ? "'".$this->db->escape($this->description)."'" : "null");
		$sql .= ", '".$this->db->escape($this->model_pdf)."'";
		$sql .= ", ".($this->fk_project ? ((int) $this->fk_project) : 0);
		$sql .= ", ".($this->fk_contrat ? ((int) $this->fk_contrat) : 0);
		$sql .= ", ".((int) $this->statut);
		$sql .= ", ".($this->signed_status);
		$sql .= ", ".($this->note_private ? "'".$this->db->escape($this->note_private)."'" : "null");
		$sql .= ", ".($this->note_public ? "'".$this->db->escape($this->note_public)."'" : "null");
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."fichinter");

			if ($this->id) {
				$this->ref = '(PROV'.$this->id.')';
				$sql = 'UPDATE '.MAIN_DB_PREFIX."fichinter SET ref='".$this->db->escape($this->ref)."' WHERE rowid=".((int) $this->id);

				dol_syslog(get_class($this)."::create", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
				}
			}

			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			// Add linked object
			if (!$error && $this->origin && $this->origin_id) {
				$ret = $this->add_object_linked();
				if (!$ret) {
					dol_print_error($this->db);
				}
			}


			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('FICHINTER_CREATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return $this->id;
			} else {
				$this->db->rollback();
				$this->error = implode(',', $this->errors);
				dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
				return -1;
			}
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Update an intervention
	 *
	 *	@param		User	$user 		Object user that make creation
	 *	@param		int		$notrigger	Disable all triggers
	 *	@return		int		Return integer <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0)
	{
		global $conf;

		if (!is_numeric($this->duration)) {
			$this->duration = 0;
		}
		if (!dol_strlen($this->fk_project)) {
			$this->fk_project = 0;
		}
		if (isset($this->ref_client)) {
			$this->ref_client = trim($this->ref_client);
		}

		$error = 0;

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter SET ";
		$sql .= "description  = '".$this->db->escape($this->description)."'";
		$sql .= ", duree = ".((int) $this->duration);
		$sql .= ", ref_client = ".($this->ref_client ? "'".$this->db->escape($this->ref_client)."'" : "null");
		$sql .= ", fk_projet = ".((int) $this->fk_project);
		$sql .= ", note_private = ".($this->note_private ? "'".$this->db->escape($this->note_private)."'" : "null");
		$sql .= ", note_public = ".($this->note_public ? "'".$this->db->escape($this->note_public)."'" : "null");
		$sql .= ", fk_user_modif = ".((int) $user->id);
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		if ($this->db->query($sql)) {
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('FICHINTER_MODIFY', $user);
				if ($result < 0) {
					$error++;
					$this->db->rollback();
					return -1;
				}
				// End call triggers
			}

			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Fetch a intervention
	 *
	 *	@param		int		$rowid		Id of intervention
	 *	@param		string	$ref		Ref of intervention
	 *	@return		int					Return integer <0 if KO, >0 if OK
	 */
	public function fetch($rowid, $ref = '')
	{
		$sql = "SELECT f.rowid, f.ref, f.ref_client, f.description, f.fk_soc, f.fk_statut as status, f.signed_status,";
		$sql .= " f.datec, f.dateo, f.datee, f.datet, f.fk_user_author,";
		$sql .= " f.date_valid as datev,";
		$sql .= " f.tms as datem,";
		$sql .= " f.duree, f.fk_projet as fk_project, f.note_public, f.note_private, f.model_pdf, f.last_main_doc, f.extraparams, fk_contrat, f.entity as entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."fichinter as f";
		if ($ref) {
			$sql .= " WHERE f.entity IN (".getEntity('intervention').")";
			$sql .= " AND f.ref = '".$this->db->escape($ref)."'";
		} else {
			$sql .= " WHERE f.rowid = ".((int) $rowid);
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id           = $obj->rowid;
				$this->ref          = $obj->ref;
				$this->ref_client   = $obj->ref_client;
				$this->description  = $obj->description;
				$this->socid        = $obj->fk_soc;
				$this->status       = $obj->status;
				$this->statut       = $obj->status;	// deprecated
				$this->signed_status= $obj->signed_status;
				$this->duration     = $obj->duree;
				$this->datec        = $this->db->jdate($obj->datec);
				$this->dateo        = $this->db->jdate($obj->dateo);
				$this->datee        = $this->db->jdate($obj->datee);
				$this->datet        = $this->db->jdate($obj->datet);
				$this->datev        = $this->db->jdate($obj->datev);
				$this->datem        = $this->db->jdate($obj->datem);
				$this->fk_project   = $obj->fk_project;
				$this->note_public  = $obj->note_public;
				$this->note_private = $obj->note_private;
				$this->model_pdf    = $obj->model_pdf;
				$this->fk_contrat = $obj->fk_contrat;
				$this->entity = $obj->entity;

				$this->user_creation_id = $obj->fk_user_author;

				$this->extraparams = (array) json_decode($obj->extraparams, true);

				$this->last_main_doc = $obj->last_main_doc;

				// Retrieve extrafields
				$this->fetch_optionals();

				/*
				 * Lines
				 */
				$result = $this->fetch_lines();
				if ($result < 0) {
					return -3;
				}
				$this->db->free($resql);
				return 1;
			}

			return 0;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Set status to draft
	 *
	 *	@param		User	$user	User that set draft
	 *	@return		int			    Return integer <0 if KO, >0 if OK
	 */
	public function setDraft($user)
	{
		$error = 0;

		// Protection
		if ($this->statut <= self::STATUS_DRAFT) {
			return 0;
		}

		dol_syslog(get_class($this)."::setDraft", LOG_DEBUG);

		$this->oldcopy = dol_clone($this, 2);

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter";
		$sql .= " SET fk_statut = ".self::STATUS_DRAFT;
		$sql .= " WHERE rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);
		if ($resql) {
			if (!$error) {
				// Call trigger
				$result = $this->call_trigger('FICHINTER_UNVALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error) {
				$this->statut = self::STATUS_DRAFT;
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->db->rollback();
			$this->error = $this->db->lasterror();
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
		global $conf;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		if ($this->status != self::STATUS_VALIDATED) {
			$this->db->begin();

			$now = dol_now();

			// Define new ref
			if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happened, but when it occurs, the test save life
				$num = $this->getNextNumRef($this->thirdparty);
			} else {
				$num = $this->ref;
			}
			$this->newref = dol_sanitizeFileName($num);

			$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter";
			$sql .= " SET fk_statut = 1";
			$sql .= ", ref = '".$this->db->escape($num)."'";
			$sql .= ", date_valid = '".$this->db->idate($now)."'";
			$sql .= ", fk_user_valid = ".($user->id > 0 ? (int) $user->id : "null");
			$sql .= " WHERE rowid = ".((int) $this->id);
			$sql .= " AND entity = ".((int) $this->entity);

			$sql .= " AND fk_statut = 0";

			dol_syslog(get_class($this)."::setValid", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('FICHINTER_VALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->oldref = $this->ref;

				// Rename directory if dir was a temporary ref
				if (preg_match('/^[\(]?PROV/i', $this->ref)) {
					require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

					// Now we rename also files into index
					$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'ficheinter/".$this->db->escape($this->newref)."'";
					$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'ficheinter/".$this->db->escape($this->ref)."' and entity = ".((int) $this->entity);
					$resql = $this->db->query($sql);
					if (!$resql) {
						$error++;
						$this->error = $this->db->lasterror();
					}
					$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filepath = 'ficheinter/".$this->db->escape($this->newref)."'";
					$sql .= " WHERE filepath = 'ficheinter/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
					$resql = $this->db->query($sql);
					if (!$resql) {
						$error++;
						$this->error = $this->db->lasterror();
					}

					// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
					$oldref = dol_sanitizeFileName($this->ref);
					$newref = dol_sanitizeFileName($num);
					$dirsource = $conf->ficheinter->dir_output.'/'.$oldref;
					$dirdest = $conf->ficheinter->dir_output.'/'.$newref;
					if (!$error && file_exists($dirsource)) {
						dol_syslog(get_class($this)."::setValid rename dir ".$dirsource." into ".$dirdest);

						if (@rename($dirsource, $dirdest)) {
							dol_syslog("Rename ok");
							// Rename docs starting with $oldref with $newref
							$listoffiles = dol_dir_list($conf->ficheinter->dir_output.'/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
							foreach ($listoffiles as $fileentry) {
								$dirsource = $fileentry['name'];
								$dirdest = preg_replace('/^'.preg_quote($oldref, '/').'/', $newref, $dirsource);
								$dirsource = $fileentry['path'].'/'.$dirsource;
								$dirdest = $fileentry['path'].'/'.$dirdest;
								@rename($dirsource, $dirdest);
							}
						}
					}
				}
			}

			// Set new ref and define current status
			if (!$error) {
				$this->ref = $num;
				$this->status = self::STATUS_VALIDATED;
				$this->statut = self::STATUS_VALIDATED;	// deprecated
				$this->date_validation = $now;
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				dol_syslog(get_class($this)."::setValid ".$this->error, LOG_ERR);
				return -1;
			}
		}

		return 0;
	}

	/**
	 *  Close intervention
	 *
	 * 	@param      User	$user       Object user that close
	 *  @param		int		$notrigger	1=Does not execute triggers, 0=Execute triggers
	 *	@return		int					Return integer <0 if KO, >0 if OK
	 */
	public function setClose($user, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		if ($this->statut == self::STATUS_CLOSED) {
			return 0;
		} else {
			$this->db->begin();

			$now = dol_now();

			$sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element;
			$sql .= ' SET fk_statut = ' . self::STATUS_CLOSED . ',';
			$sql .= " datet = '" . $this->db->idate($now) . "',";
			$sql .= " fk_user_modif = " . ((int) $user->id);
			$sql .= " WHERE rowid = " . ((int) $this->id);
			$sql .= " AND fk_statut > " . self::STATUS_DRAFT;
			$sql .= " AND entity = " . ((int) $conf->entity);

			if ($this->db->query($sql)) {
				if (!$notrigger) {
					// Call trigger
					$result = $this->call_trigger('FICHINTER_CLOSE', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}

				if (!$error) {
					$this->statut = self::STATUS_CLOSED;
					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					return -1;
				}
			} else {
				$this->error = $this->db->lasterror();
				$this->db->rollback();
				return -1;
			}
		}
	}

	/**
	 *	Returns amount based on user thm
	 *
	 *	@return     float 		Amount
	 */
	public function getAmount()
	{
		$amount = 0;

		$this->author = new User($this->db);
		$this->author->fetch($this->user_creation_id);

		$thm = $this->author->thm;

		foreach ($this->lines as $line) {
			$amount += ($line->duration / 60 / 60 * (float) $thm);
		}

		return (float) price2num($amount, 'MT');
	}


	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param      string                  $modele         Force model to use ('' to not force)
	 *  @param      Translate               $outputlangs    Object langs to use for output
	 *  @param      int                     $hidedetails    Hide details of lines
	 *  @param      int                     $hidedesc       Hide description
	 *  @param      int                     $hideref        Hide ref
	 *  @param   null|array  $moreparams     Array to provide more information
	 *  @return     int                                     0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf;

		$outputlangs->load("interventions");

		if (!dol_strlen($modele)) {
			$modele = 'soleil';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('FICHEINTER_ADDON_PDF')) {
				$modele = getDolGlobalString('FICHEINTER_ADDON_PDF');
			}
		}

		$modelpath = "core/modules/fichinter/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}

	/**
	 *	Returns the label status
	 *
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return     string      		Label
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut((isset($this->statut) ? $this->statut : $this->status), $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Returns the label of a status
	 *
	 *	@param      int		$status     Id status
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *	@return     string      		Label
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs;
		// Init/load array of translation of status
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			$langs->load("interventions");
			$langs->load("propal");

			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validated');
			$this->labelStatus[self::STATUS_BILLED] = $langs->transnoentitiesnoconv('StatusInterInvoiced');
			$this->labelStatus[self::STATUS_CLOSED] = $langs->transnoentitiesnoconv('Done');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Validated');
			$this->labelStatusShort[self::STATUS_BILLED] = $langs->transnoentitiesnoconv('StatusInterInvoiced');
			$this->labelStatusShort[self::STATUS_CLOSED] = $langs->transnoentitiesnoconv('Done');
		}

		$statuscode = 'status'.$status;
		if ($status == self::STATUS_BILLED || $status == self::STATUS_CLOSED) {
			$statuscode = 'status6';
		}

		$signed_label = ' (' . $this->getLibSignedStatus() . ')';
		$status_label = $this->signed_status ? $this->labelStatus[$status] . $signed_label : $this->labelStatus[$status];
		$status_label_short = $this->signed_status ? $this->labelStatusShort[$status] . $signed_label : $this->labelStatusShort[$status];

		return dolGetStatus($status_label, $status_label_short, '', $statuscode, $mode);
	}

	/**
	 *	Returns the label for signed status
	 *
	 *	@param		int		$mode	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return		string			Label
	 */
	public function getLibSignedStatus(int $mode = 0): string
	{
		global $langs;
		$langs->load("commercial");
		$list_signed_status = $this->getSignedStatusLocalisedArray();
		$signed_status_label = $list_signed_status[$this->signed_status];
		$signed_status_label_short = $list_signed_status[$this->signed_status];
		$signed_status_code = 'status'.$this->signed_status;
		return dolGetStatus($signed_status_label, $signed_status_label_short, '', $signed_status_code, $mode);
	}

	/**
	 *	Returns an array of signed statuses with associated localized labels
	 *
	 *	@return array
	 */
	public function getSignedStatusLocalisedArray(): array
	{
		global $langs;
		$langs->load("commercial");

		$l10n_signed_status_labels = [
			self::SIGNED_STATUSES['STATUS_NO_SIGNATURE']	=> 'NoSignature',
			self::SIGNED_STATUSES['STATUS_SIGNED_SENDER']	=> 'SignedSender',
			self::SIGNED_STATUSES['STATUS_SIGNED_RECEIVER']	=> 'SignedReceiver',
			self::SIGNED_STATUSES['STATUS_SIGNED_ALL']		=> 'SignedAll'
		];

		$l10n_signed_status = [];
		foreach (self::SIGNED_STATUSES as $signed_status_code) {
			$l10n_signed_status[$signed_status_code] = $langs->transnoentitiesnoconv($l10n_signed_status_labels[$signed_status_code]);
		}
		return $l10n_signed_status;
	}

	/**
	 * getTooltipContentArray
	 *
	 * @param array $params ex option, infologin
	 * @since v18
	 * @return array
	 */
	public function getTooltipContentArray($params)
	{
		global $conf, $langs;

		$langs->load('interventions');

		$datas = [];
		$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Intervention").'</u>';
		if (isset($this->status)) {
			$datas['picto'] .= ' '.$this->getLibStatut(5);
		}
		$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;

		return $datas;
	}

	/**
	 *	Return clickable name (with picto eventually)
	 *
	 *	@param		int		$withpicto					0=_No picto, 1=Includes the picto in the linkn, 2=Picto only
	 *	@param		string	$option						Options
	 *  @param	    int   	$notooltip					1=Disable tooltip
	 *  @param      int     $save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @param  	string  $morecss                    Add more css on link
	 *	@return		string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $save_lastsearch_value = -1, $morecss = '')
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'option' => $option,
		];
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		$url = DOL_URL_ROOT.'/fichinter/card.php?id='.$this->id;

		if ($option !== 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowIntervention");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		if ($option == 'nolink' || empty($url)) {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="'.$url.'"';
		}
		$linkstart .= $linkclose.'>';
		if ($option == 'nolink' || empty($url)) {
			$linkend = '</span>';
		} else {
			$linkend = '</a>';
		}

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'"'), 0, 0, $notooltip ? 0 : 1);
		}

		if ($withpicto != 2) {
			$result .= $this->ref;
		}

		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array('interventiondao'));
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
	 *	Returns the next non used reference of intervention
	 *	depending on the module numbering assets within FICHEINTER_ADDON
	 *
	 *	@param	    Societe		$soc		Thirdparty object
	 *	@return     string					Free reference for intervention
	 */
	public function getNextNumRef($soc)
	{
		global $conf, $db, $langs;
		$langs->load("interventions");

		if (getDolGlobalString('FICHEINTER_ADDON')) {
			$mybool = false;

			$file = "mod_" . getDolGlobalString('FICHEINTER_ADDON').".php";
			$classname = "mod_" . getDolGlobalString('FICHEINTER_ADDON');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/fichinter/");

				// Load file with numbering class (if found)
				$mybool = ((bool) @include_once $dir.$file) || $mybool;
			}

			if (!$mybool) {
				dol_print_error(null, "Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			$numref = "";
			$numref = $obj->getNextValue($soc, $this);

			if ($numref != "") {
				return $numref;
			} else {
				dol_print_error($db, "Fichinter::getNextNumRef ".$obj->error);
				return "";
			}
		} else {
			$langs->load("errors");
			print $langs->trans("Error")." ".$langs->trans("Error_FICHEINTER_ADDON_NotDefined");
			return "";
		}
	}

	/**
	 * 	Load information on object
	 *
	 *	@param	int		$id      Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = "SELECT f.rowid,";
		$sql .= " f.datec,";
		$sql .= " f.tms as date_modification,";
		$sql .= " f.date_valid as datev,";
		$sql .= " f.fk_user_author,";
		$sql .= " f.fk_user_modif as fk_user_modification,";
		$sql .= " f.fk_user_valid";
		$sql .= " FROM ".MAIN_DB_PREFIX."fichinter as f";
		$sql .= " WHERE f.rowid = ".((int) $id);

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id                = $obj->rowid;

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->date_modification);
				$this->date_validation   = $this->db->jdate($obj->datev);

				$this->user_creation_id = $obj->fk_user_author;
				$this->user_validation_id = $obj->fk_user_valid;
				$this->user_modification_id = $obj->fk_user_modification;
			}
			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 *	Delete intervetnion
	 *
	 *	@param      User	$user			Object user who delete
	 *	@param		int		$notrigger		Disable trigger
	 *	@return		int						Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		global $conf, $langs;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$this->db->begin();

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('FICHINTER_DELETE', $user);
			if ($result < 0) {
				$error++;
				$this->db->rollback();
				return -1;
			}
			// End call triggers
		}

		// Delete linked object
		if (!$error) {
			$res = $this->deleteObjectLinked();
			if ($res < 0) {
				$error++;
			}
		}

		// Delete linked contacts
		if (!$error) {
			$res = $this->delete_linked_contact();
			if ($res < 0) {
				$this->error = 'ErrorFailToDeleteLinkedContact';
				$error++;
			}
		}

		if (!$error) {
			$main = MAIN_DB_PREFIX.'fichinterdet';
			$ef = $main."_extrafields";
			$sql = "DELETE FROM $ef WHERE fk_object IN (SELECT rowid FROM $main WHERE fk_fichinter = ".((int) $this->id).")";

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
			}
		}

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."fichinterdet";
			$sql .= " WHERE fk_fichinter = ".((int) $this->id);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
			}
		}

		if (!$error) {
			// Remove extrafields
			$res = $this->deleteExtraFields();
			if ($res < 0) {
				$error++;
			}
		}

		if (!$error) {
			// Delete object
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."fichinter";
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog("Fichinter::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
			}
		}

		if (!$error) {
			// Delete record into ECM index (Note that delete is also done when deleting files with the dol_delete_dir_recursive
			$this->deleteEcmFiles(0); // Deleting files physically is done later with the dol_delete_dir_recursive
			$this->deleteEcmFiles(1); // Deleting files physically is done later with the dol_delete_dir_recursive

			// Remove directory with files
			$fichinterref = dol_sanitizeFileName($this->ref);
			if ($conf->ficheinter->dir_output) {
				$dir = $conf->ficheinter->dir_output."/".$fichinterref;
				$file = $conf->ficheinter->dir_output."/".$fichinterref."/".$fichinterref.".pdf";
				if (file_exists($file)) {
					dol_delete_preview($this);

					if (!dol_delete_file($file, 0, 0, 0, $this)) { // For triggers
						$langs->load("errors");
						$this->error = $langs->trans("ErrorFailToDeleteFile", $file);
						return 0;
					}
				}
				if (file_exists($dir)) {
					if (!dol_delete_dir_recursive($dir)) {
						$langs->load("errors");
						$this->error = $langs->trans("ErrorFailToDeleteDir", $dir);
						return 0;
					}
				}
			}
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Defines a delivery date of intervention
	 *
	 *  @param      User	$user				Object user who define
	 *  @param      integer	$date_delivery   	date of delivery
	 *  @return     int							Return integer <0 if KO, >0 if OK
	 */
	public function set_date_delivery($user, $date_delivery)
	{
		// phpcs:enable
		if ($user->hasRight('ficheinter', 'creer')) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter ";
			$sql .= " SET datei = '".$this->db->idate($date_delivery)."'";
			$sql .= " WHERE rowid = ".((int) $this->id);
			$sql .= " AND fk_statut = 0";

			if ($this->db->query($sql)) {
				$this->date_delivery = $date_delivery;
				return 1;
			} else {
				$this->error = $this->db->error();
				dol_syslog("Fichinter::set_date_delivery Erreur SQL");
				return -1;
			}
		}

		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Define the label of the intervention
	 *
	 *	@param      User	$user			Object user who modify
	 *	@param      string	$description    description
	 *	@return     int						Return integer <0 if KO, >0 if OK
	 */
	public function set_description($user, $description)
	{
		// phpcs:enable
		if ($user->hasRight('ficheinter', 'creer')) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter ";
			$sql .= " SET description = '".$this->db->escape($description)."',";
			$sql .= " fk_user_modif = ".$user->id;
			$sql .= " WHERE rowid = ".((int) $this->id);

			if ($this->db->query($sql)) {
				$this->description = $description;
				return 1;
			} else {
				$this->error = $this->db->error();
				dol_syslog("Fichinter::set_description Erreur SQL");
				return -1;
			}
		}

		return 0;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Link intervention to a contract
	 *
	 *	@param      User	$user			Object user who modify
	 *	@param      int		$contractid		Description
	 *	@return     int						Return integer <0 if KO, >0 if OK
	 */
	public function set_contrat($user, $contractid)
	{
		// phpcs:enable
		if ($user->hasRight('ficheinter', 'creer')) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter ";
			$sql .= " SET fk_contrat = ".((int) $contractid);
			$sql .= " WHERE rowid = ".((int) $this->id);

			if ($this->db->query($sql)) {
				$this->fk_contrat = $contractid;
				return 1;
			} else {
				$this->error = $this->db->error();
				return -1;
			}
		}

		return -2;
	}



	/**
	 *	Load an object from its id and create a new one in database
	 *
	 *  @param	    User	$user		    User making the clone
	 *	@param		int		$socid			Id of thirdparty
	 *	@return		int						New id of clone
	 */
	public function createFromClone(User $user, $socid = 0)
	{
		global $hookmanager;

		$error = 0;

		$this->db->begin();

		// get extrafields so they will be clone
		foreach ($this->lines as $line) {
			$line->fetch_optionals();
		}

		// Load source object
		$objFrom = clone $this;

		// Change socid if needed
		if (!empty($socid) && $socid != $this->socid) {
			$objsoc = new Societe($this->db);

			if ($objsoc->fetch($socid) > 0) {
				$this->socid = $objsoc->id;
				//$this->cond_reglement_id	= (!empty($objsoc->cond_reglement_id) ? $objsoc->cond_reglement_id : 0);
				//$this->mode_reglement_id	= (!empty($objsoc->mode_reglement_id) ? $objsoc->mode_reglement_id : 0);
				$this->fk_project = 0;
				$this->fk_delivery_address = 0;
			}

			// TODO Change product price if multi-prices
		}

		$this->id = 0;
		$this->ref = '';
		$this->status = self::STATUS_DRAFT;
		$this->statut = self::STATUS_DRAFT;	//  deprecated

		// Clear fields
		$this->user_author_id     = $user->id;
		$this->user_validation_id = 0;
		$this->date_creation      = '';
		$this->date_validation    = '';

		$this->ref_client         = '';

		// Create clone
		$this->context['createfromclone'] = 'createfromclone';
		$result = $this->create($user);
		if ($result < 0) {
			$error++;
		}

		if (!$error) {
			// Add lines because it is not included into create function
			foreach ($this->lines as $line) {
				$this->addline($user, $this->id, $line->desc, $line->datei, $line->duration, $line->array_options);
			}

			// Hook of thirdparty module
			if (is_object($hookmanager)) {
				$parameters = array('objFrom' => $objFrom);
				$action = '';
				$reshook = $hookmanager->executeHooks('createFrom', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$this->setErrorsFromObject($hookmanager);
					$error++;
				}
			}
		}

		unset($this->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $this->id;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Adding a line of intervention into data base
	 *
	 *  @param      user	$user					User that do the action
	 *	@param    	int		$fichinterid			Id of intervention
	 *	@param    	string	$desc					Line description
	 *	@param      integer	$date_intervention  	Intervention date
	 *	@param      int		$duration            	Intervention duration
	 *  @param		array	$array_options			Array option
	 *	@return    	int             				>0 if ok, <0 if ko
	 */
	public function addline($user, $fichinterid, $desc, $date_intervention, $duration, $array_options = [])
	{
		dol_syslog(get_class($this)."::addline $fichinterid, $desc, $date_intervention, $duration");

		if ($this->status == self::STATUS_DRAFT) {
			$this->db->begin();

			// Insertion ligne
			$line = new FichinterLigne($this->db);

			$line->fk_fichinter = $fichinterid;
			$line->desc         = $desc;
			$line->date         = $date_intervention;
			$line->datei        = $date_intervention;	// For backward compatibility
			$line->duration     = $duration;

			if (is_array($array_options) && count($array_options) > 0) {
				$line->array_options = $array_options;
			}

			$result = $line->insert($user);

			if ($result >= 0) {
				$this->db->commit();
				return 1;
			} else {
				$this->error = $this->db->error();
				$this->db->rollback();
				return -1;
			}
		}

		return 0;
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
		global $langs;

		$now = dol_now();

		// Initialise parameters
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->ref_client = 'SPECIMEN CLIENT';
		$this->specimen = 1;
		$this->socid = 1;
		$this->datec = $now;
		$this->note_private = 'Private note';
		$this->note_public = 'SPECIMEN';
		$this->duration = 0;
		$nbp = 25;
		$xnbp = 0;
		while ($xnbp < $nbp) {
			$line = new FichinterLigne($this->db);
			$line->desc = $langs->trans("Description")." ".$xnbp;
			$line->date = ($now - 3600 * (1 + $xnbp));
			$line->datei = ($now - 3600 * (1 + $xnbp));	// For backward compatibility
			$line->duration = 600;
			$line->fk_fichinter = 0;
			$this->lines[$xnbp] = $line;
			$xnbp++;

			$this->duration += $line->duration;
		}

		return 1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Load array lines ->lines
	 *
	 *	@return		int		Return integer <0 if KO, >0 if OK
	 */
	public function fetch_lines()
	{
		// phpcs:enable
		$this->lines = array();

		$sql = "SELECT rowid, fk_fichinter, description, duree, date, rang";
		$sql .= " FROM ".MAIN_DB_PREFIX."fichinterdet";
		$sql .= " WHERE fk_fichinter = ".((int) $this->id);
		$sql .= " ORDER BY rang ASC, date ASC";

		dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$objp = $this->db->fetch_object($resql);

				$line = new FichinterLigne($this->db);
				$line->id = $objp->rowid;
				$line->fk_fichinter = $objp->fk_fichinter;
				$line->desc = $objp->description;
				$line->duration = $objp->duree;
				//For invoicing we calculing hours
				$line->qty = round($objp->duree / 3600, 2);
				$line->date	= $this->db->jdate($objp->date);
				$line->datei = $this->db->jdate($objp->date);	// For backward compatibility
				$line->rang	= $objp->rang;
				$line->product_type = 1;
				$line->fetch_optionals();

				$this->lines[$i] = $line;
				$i++;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = $this->db->error();
			return -1;
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
			'fichinter'
		);

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}

	/**
	 * Set customer reference number
	 *
	 *  @param      User	$user			Object user that modify
	 *  @param      string	$ref_client		Customer reference
	 *  @param  	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *  @return     int						Return integer <0 if ko, >0 if ok
	 */
	public function setRefClient($user, $ref_client, $notrigger = 0)
	{
		// phpcs:enable
		if ($user->hasRight('ficheinter', 'creer')) {
			$error = 0;

			$this->db->begin();

			$this->oldcopy = dol_clone($this, 2);

			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET ref_client = ".(empty($ref_client) ? 'NULL' : "'".$this->db->escape($ref_client)."'");
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(__METHOD__.' $this->id='.$this->id.', ref_client='.$ref_client, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->error();
				$error++;
			}

			if (!$error) {
				$this->ref_client = $ref_client;
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('FICHINTER_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		} else {
			return -1;
		}
	}

	/**
	 *	Return clickable link of object (with eventually picto)
	 *
	 *	@param      string	    $option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		array		$arraydata				Array of data
	 *  @return		string								HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (!empty($arraydata['thirdparty'])) {
			$tmpthirdparty = $arraydata['thirdparty'];
			$return .= '<br><span class="info-box-label">'.$tmpthirdparty->getNomUrl(1).'</span>';
		}
		if (property_exists($this, 'duration')) {
			$return .= '<br><span class="info-box-label ">'.$langs->trans("Duration").' : '.convertSecondToTime($this->duration, 'allhourmin').'</span>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}

	/**
	 * Set signed status
	 *
	 * @param  User   $user        Object user that modify
	 * @param  int    $status      Newsigned  status to set (often a constant like self::STATUS_XXX)
	 * @param  int    $notrigger   1 = Does not execute triggers, 0 = Execute triggers
	 * @param  string $triggercode Trigger code to use
	 * @return int                 0 < if KO, > 0 if OK
	 */
	public function setSignedStatus(User $user, int $status = 0, int $notrigger = 0, $triggercode = ''): int
	{
		return $this->setSignedStatusCommon($user, $status, $notrigger, $triggercode);
	}
}

/**
 *	Class to manage intervention lines
 */
class FichinterLigne extends CommonObjectLine
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	// From llx_fichinterdet
	/**
	 * @var int ID
	 */
	public $fk_fichinter;

	public $desc; 		// Description ligne

	/**
	 * @var int Date of intervention
	 */
	public $date; 		// Date intervention
	/**
	 * @var int Date of intervention
	 * @deprecated
	 */
	public $datei; 		// Date intervention

	public $duration; 	// Duration of intervention
	public $rang = 0;
	public $tva_tx;

	/**
	 * Unit price before taxes
	 * @var float
	 */
	public $subprice;

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'fichinterdet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'fichinterdet';

	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_fichinter';



	/**
	 *  Constructor
	 *
	 *  @param  DoliDB  $db     Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Retrieve the line of intervention
	 *
	 *	@param  int		$rowid		Line id
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		dol_syslog("FichinterLigne::fetch", LOG_DEBUG);

		$sql = 'SELECT ft.rowid, ft.fk_fichinter, ft.description, ft.duree, ft.rang, ft.date';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'fichinterdet as ft';
		$sql .= ' WHERE ft.rowid = '.((int) $rowid);

		$resql = $this->db->query($sql);
		if ($resql) {
			$objp = $this->db->fetch_object($resql);
			$this->rowid          	= $objp->rowid;
			$this->id               = $objp->rowid;
			$this->fk_fichinter   	= $objp->fk_fichinter;
			$this->date = $this->db->jdate($objp->date);
			$this->datei = $this->db->jdate($objp->date);	// For backward compatibility
			$this->desc           	= $objp->description;
			$this->duration       	= $objp->duree;
			$this->rang           	= $objp->rang;

			$this->db->free($resql);

			$this->fetch_optionals();

			return 1;
		} else {
			$this->error = $this->db->error().' sql='.$sql;
			return -1;
		}
	}

	/**
	 *	Insert the line into database
	 *
	 *	@param		User	$user 		Object user that make creation
	 *	@param		int		$notrigger	Disable all triggers
	 *	@return		int		Return integer <0 if ko, >0 if ok
	 */
	public function insert($user, $notrigger = 0)
	{
		$error = 0;

		dol_syslog("FichinterLigne::insert rang=".$this->rang);

		if (empty($this->date) && !empty($this->datei)) {	// For backward compatibility
			$this->date = $this->datei;
		}

		$this->db->begin();

		$rangToUse = $this->rang;
		if ($rangToUse == -1) {
			// Recupere rang max de la ligne d'intervention dans $rangmax
			$sql = 'SELECT max(rang) as max FROM '.MAIN_DB_PREFIX.'fichinterdet';
			$sql .= ' WHERE fk_fichinter = '.((int) $this->fk_fichinter);
			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				$rangToUse = $obj->max + 1;
			} else {
				dol_print_error($this->db);
				$this->db->rollback();
				return -1;
			}
		}

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'fichinterdet';
		$sql .= ' (fk_fichinter, description, date, duree, rang)';
		$sql .= " VALUES (".((int) $this->fk_fichinter).",";
		$sql .= " '".$this->db->escape($this->desc)."',";
		$sql .= " '".$this->db->idate($this->date)."',";
		$sql .= " ".((int) $this->duration).",";
		$sql .= ' '.((int) $rangToUse);
		$sql .= ')';

		dol_syslog("FichinterLigne::insert", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'fichinterdet');
			$this->rowid = $this->id;

			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}


			$result = $this->update_total();

			if ($result > 0) {
				$this->rang = $rangToUse;

				if (!$notrigger) {
					// Call trigger
					$result = $this->call_trigger('LINEFICHINTER_CREATE', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}
			}

			if (!$error) {
				$this->db->commit();
				return $result;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->error()." sql=".$sql;
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Update intervention into database
	 *
	 *	@param		User	$user 		Object user that make creation
	 *	@param		int		$notrigger	Disable all triggers
	 *	@return		int		Return integer <0 if ko, >0 if ok
	 */
	public function update($user, $notrigger = 0)
	{
		$error = 0;

		if (empty($this->date) && !empty($this->datei)) {	// For backward compatibility
			$this->date = $this->datei;
		}

		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."fichinterdet SET";
		$sql .= " description = '".$this->db->escape($this->desc)."',";
		$sql .= " date = '".$this->db->idate($this->date)."',";
		$sql .= " duree = ".((int) $this->duration).",";
		$sql .= " rang = ".((int) $this->rang);
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog("FichinterLigne::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			$result = $this->update_total();
			if ($result > 0) {
				if (!$notrigger) {
					// Call trigger
					$result = $this->call_trigger('LINEFICHINTER_MODIFY', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}
			}

			if (!$error) {
				$this->db->commit();
				return $result;
			} else {
				$this->error = $this->db->lasterror();
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Update total duration into llx_fichinter
	 *
	 *	@return		int		Return integer <0 si ko, >0 si ok
	 */
	public function update_total()
	{
		// phpcs:enable
		global $conf;

		$this->db->begin();

		$sql = "SELECT SUM(duree) as total_duration, min(date) as dateo, max(date) as datee ";
		$sql .= " FROM ".MAIN_DB_PREFIX."fichinterdet";
		$sql .= " WHERE fk_fichinter=".((int) $this->fk_fichinter);

		dol_syslog("FichinterLigne::update_total", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$total_duration = 0;
			if (!empty($obj->total_duration)) {
				$total_duration = $obj->total_duration;
			}

			$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter";
			$sql .= " SET duree = ".((int) $total_duration);
			$sql .= " , dateo = ".(!empty($obj->dateo) ? "'".$this->db->escape($obj->dateo)."'" : "null");
			$sql .= " , datee = ".(!empty($obj->datee) ? "'".$this->db->escape($obj->datee)."'" : "null");
			$sql .= " WHERE rowid = ".((int) $this->fk_fichinter);

			dol_syslog("FichinterLigne::update_total", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$this->db->commit();
				return 1;
			} else {
				$this->error = $this->db->error();
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Delete a intervention line
	 *
	 *	@param		User	$user 		Object user that make creation
	 *	@param		int		$notrigger	Disable all triggers
	 *	@return     int		>0 if ok, <0 if ko
	 */
	public function deleteLine($user, $notrigger = 0)
	{
		$error = 0;

		dol_syslog(get_class($this)."::deleteline lineid=".$this->id);

		$this->db->begin();

		$result = $this->deleteExtraFields();
		if ($result < 0) {
			$error++;
			$this->db->rollback();
			return -1;
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."fichinterdet WHERE rowid = ".((int) $this->id);
		$resql = $this->db->query($sql);

		if ($resql) {
			$result = $this->update_total();
			if ($result > 0) {
				if (!$notrigger) {
					// Call trigger
					$result = $this->call_trigger('LINEFICHINTER_DELETE', $user);
					if ($result < 0) {
						$error++;
						$this->db->rollback();
						return -1;
					}
					// End call triggers
				}

				$this->db->commit();
				return $result;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->error()." sql=".$sql;
			$this->db->rollback();
			return -1;
		}
	}
}
