<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Destailleur Laurent		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2008		Raphael Bertrand		<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2016	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013		Christophe Battarel		<christophe.battarel@altairis.fr>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2014-2015	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2018   	Nicolas ZABOURI			<info@inovea-conseil.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2015-2018	Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2024		William Mead			<william.mead@manchenumerique.fr>
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
 *	\file       htdocs/contrat/class/contrat.class.php
 *	\ingroup    contrat
 *	\brief      File of class to manage contracts
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT."/core/class/commonobjectline.class.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';

/**
 *	Class to manage contracts
 */
class Contrat extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'contrat';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'contrat';

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = 'contratdet';

	/**
	 * @var string Fieldname with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_contrat';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'contract';

	/**
	 * 0=Default, 1=View may be restricted to sales representative only if no permission to see all or to company of external user if external user
	 * @var integer
	 */
	public $restrictiononfksoc = 1;

	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'ref';

	/**
	 * Customer reference of the contract
	 * @var string
	 */
	public $ref_customer;
	public $from;

	/**
	 * Supplier reference of the contract
	 * @var string
	 */
	public $ref_supplier;

	/**
	 * Entity of the contract
	 * @var int
	 */
	public $entity;

	/**
	 * Client id linked to the contract
	 * @var int
	 */
	public $socid;

	/**
	 * Client id linked to the contract
	 * @var int
	 * @deprecated Use $socid
	 */
	public $fk_soc;


	public $societe; // Object societe

	/**
	 * Status of the contract
	 * @var int
	 * @deprecated
	 */
	public $statut = 0;
	/**
	 * Status of the contract (0=Draft, 1=Validated)
	 * @var int
	 */
	public $status = 0;

	public $product;

	/**
	 * @var int		Id of user author of the contract
	 */
	public $fk_user_author;

	/**
	 * TODO: Which is the correct one?
	 * Author of the contract
	 * @var int
	 */
	public $user_author_id;


	/**
	 * @var User 	Object user that create the contract. Set by the info method.
	 * @deprecated
	 */
	public $user_creation;

	/**
	 * @var User 	Object user that close the contract. Set by the info method.
	 */
	public $user_cloture;

	/**
	 * @var int|string		Date when contract was signed
	 */
	public $date_contrat;

	/**
	 * Status of the contract (0=NoSignature, 1=SignedBySender, 2=SignedByReceiver, 9=SignedByAll)
	 * @var int
	 */
	public $signed_status = 0;

	public $commercial_signature_id;
	public $fk_commercial_signature;
	public $commercial_suivi_id;
	public $fk_commercial_suivi;

	/**
	 * @deprecated Use fk_project instead
	 * @see $fk_project
	 */
	public $fk_projet;

	public $extraparams = array();

	/**
	 * @var ContratLigne[]		Contract lines
	 */
	public $lines = array();

	public $nbofservices;
	public $nbofserviceswait;
	public $nbofservicesopened;
	public $nbofservicesexpired;
	public $nbofservicesclosed;
	//public $lower_planned_end_date;
	//public $higher_planner_end_date;

	/**
	 * Maps ContratLigne IDs to $this->lines indexes
	 * @var int[]
	 */
	protected $lines_id_index_mapper = array();


	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 10),
		'ref' => array('type' => 'varchar(50)', 'label' => 'Ref', 'enabled' => 1, 'visible' => -1, 'showoncombobox' => 1, 'position' => 15, 'searchall' => 1),
		'ref_ext' => array('type' => 'varchar(255)', 'label' => 'Ref ext', 'enabled' => 1, 'visible' => 0, 'position' => 20),
		'ref_customer' => array('type' => 'varchar(50)', 'label' => 'RefCustomer', 'enabled' => 1, 'visible' => -1, 'position' => 25, 'searchall' => 1),
		'ref_supplier' => array('type' => 'varchar(50)', 'label' => 'RefSupplier', 'enabled' => 1, 'visible' => -1, 'position' => 26, 'searchall' => 1),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 30, 'index' => 1),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 35),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -1, 'position' => 40),
		'date_contrat' => array('type' => 'datetime', 'label' => 'Date contrat', 'enabled' => 1, 'visible' => -1, 'position' => 45),
		'signed_status' => array('type' => 'smallint(6)', 'label' => 'SignedStatus', 'enabled' => 1, 'visible' => -1, 'position' => 50, 'arrayofkeyval' => array(0 => 'NoSignature', 1 => 'SignedSender', 2 => 'SignedReceiver', 9 => 'SignedAll')),
		'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'ThirdParty', 'enabled' => 'isModEnabled("societe")', 'visible' => -1, 'notnull' => 1, 'position' => 70),
		'fk_projet' => array('type' => 'integer:Project:projet/class/project.class.php:1:(fk_statut:=:1)', 'label' => 'Project', 'enabled' => "isModEnabled('project')", 'visible' => -1, 'position' => 75),
		'fk_commercial_signature' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'SaleRepresentative Signature', 'enabled' => 1, 'visible' => -1, 'position' => 80),
		'fk_commercial_suivi' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'SaleRepresentative follower', 'enabled' => 1, 'visible' => -1, 'position' => 85),
		'fk_user_author' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 90),
		'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => 1, 'visible' => 0, 'position' => 105, 'searchall' => 1),
		'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => 1, 'visible' => 0, 'position' => 110, 'searchall' => 1),
		'model_pdf' => array('type' => 'varchar(255)', 'label' => 'Model pdf', 'enabled' => 1, 'visible' => 0, 'position' => 115),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'position' => 120),
		'extraparams' => array('type' => 'varchar(255)', 'label' => 'Extraparams', 'enabled' => 1, 'visible' => -1, 'position' => 125),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'visible' => -2, 'notnull' => -1, 'position' => 135),
		'last_main_doc' => array('type' => 'varchar(255)', 'label' => 'Last main doc', 'enabled' => 1, 'visible' => -1, 'position' => 140),
		'statut' => array('type' => 'smallint(6)', 'label' => 'Statut', 'enabled' => 1, 'visible' => -1, 'position' => 500, 'notnull' => 1, 'arrayofkeyval' => array(0 => 'Draft', 1 => 'Validated', 2 => 'Closed'))
	);
	// END MODULEBUILDER PROPERTIES

	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CLOSED = 2;

	/*
	 * No signature
	 */
	const STATUS_NO_SIGNATURE    = 0;

	/*
	 * Signed by sender
	 */
	const STATUS_SIGNED_SENDER   = 1;

	/*
	 * Signed by receiver
	 */
	const STATUS_SIGNED_RECEIVER = 2;

	/*
	 * Signed by all
	 */
	const STATUS_SIGNED_ALL      = 9; // To handle future kind of signature (ex: tripartite contract)


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->ismultientitymanaged = 1;
		$this->isextrafieldmanaged = 1;
	}

	/**
	 *	Return next contract ref
	 *
	 *	@param	Societe		$soc		Thirdparty object
	 *	@return string					free reference for contract
	 */
	public function getNextNumRef($soc)
	{
		global $db, $langs, $conf;
		$langs->load("contracts");

		if (getDolGlobalString('CONTRACT_ADDON')) {
			$mybool = false;

			$file = getDolGlobalString('CONTRACT_ADDON') . ".php";
			$classname = getDolGlobalString('CONTRACT_ADDON');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/contract/");

				// Load file with numbering class (if found)
				$mybool = ((bool) @include_once $dir.$file) || $mybool;
			}

			if (!$mybool) {
				dol_print_error(null, "Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			'@phan-var-force CommonNumRefGenerator $obj';
			$numref = $obj->getNextValue($soc, $this);

			if ($numref != "") {
				return $numref;
			} else {
				$this->error = $obj->error;
				dol_print_error($db, get_class($this)."::getNextValue ".$obj->error);
				return "";
			}
		} else {
			$langs->load("errors");
			print $langs->trans("Error")." ".$langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("Contract"));
			return "";
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Activate a contract line
	 *
	 *  @param	User		$user       Object User who activate contract
	 *  @param  int			$line_id    Id of line to activate
	 *  @param  int			$date_start Opening date
	 *  @param  int|string	$date_end   Expected end date
	 * 	@param	string		$comment	A comment typed by user
	 *  @return int         			Return integer <0 if KO, >0 if OK
	 */
	public function active_line($user, $line_id, $date_start, $date_end = '', $comment = '')
	{
		// phpcs:enable
		$result = $this->lines[$this->lines_id_index_mapper[$line_id]]->active_line($user, $date_start, $date_end, $comment);
		if ($result < 0) {
			$this->error = $this->lines[$this->lines_id_index_mapper[$line_id]]->error;
			$this->errors = $this->lines[$this->lines_id_index_mapper[$line_id]]->errors;
		}
		return $result;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Close a contract line
	 *
	 *  @param	User		$user       Object User who close contract
	 *  @param  int			$line_id    Id of line to close
	 *  @param  int			$date_end	End date
	 * 	@param	string		$comment	A comment typed by user
	 *  @return int         			Return integer <0 if KO, >0 if OK
	 */
	public function close_line($user, $line_id, $date_end, $comment = '')
	{
		// phpcs:enable
		$result = $this->lines[$this->lines_id_index_mapper[$line_id]]->close_line($user, $date_end, $comment);
		if ($result < 0) {
			$this->error = $this->lines[$this->lines_id_index_mapper[$line_id]]->error;
			$this->errors = $this->lines[$this->lines_id_index_mapper[$line_id]]->errors;
		}
		return $result;
	}


	/**
	 *  Open all lines of a contract
	 *
	 *  @param	User		$user      		Object User making action
	 *  @param	int|string	$date_start		Date start (now if empty)
	 *  @param	int			$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *  @param	string		$comment		Comment
	 *  @param	int|string	$date_end		Date end
	 *	@return	int							Return integer <0 if KO, >0 if OK
	 *  @see ()
	 */
	public function activateAll($user, $date_start = '', $notrigger = 0, $comment = '', $date_end = '')
	{
		if (empty($date_start)) {
			$date_start = dol_now();
		}

		$this->db->begin();

		$error = 0;

		// Load lines
		$this->fetch_lines();

		foreach ($this->lines as $contratline) {
			// Open lines not already open
			if ($contratline->statut != ContratLigne::STATUS_OPEN) {
				$contratline->context = $this->context;

				$result = $contratline->active_line($user, $date_start, !empty($date_end) ? $date_end : -1, $comment);	// This call trigger LINECONTRACT_ACTIVATE
				if ($result < 0) {
					$error++;
					$this->error = $contratline->error;
					$this->errors = $contratline->errors;
					break;
				}
			}
		}

		if (!$error && $this->statut == 0) {
			$result = $this->validate($user, '', $notrigger);
			if ($result < 0) {
				$error++;
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

	/**
	 * Close all lines of a contract
	 *
	 * @param	User		$user      		Object User making action
	 * @param	int			$notrigger		1=Does not execute triggers, 0=Execute triggers
	 * @param	string		$comment		Comment
	 * @return	int							Return integer <0 if KO, >0 if OK
	 * @see activateAll()
	 */
	public function closeAll(User $user, $notrigger = 0, $comment = '')
	{
		$this->db->begin();

		// Load lines
		$this->fetch_lines();

		$now = dol_now();

		$error = 0;

		foreach ($this->lines as $contratline) {
			// Close lines not already closed
			if ($contratline->statut != ContratLigne::STATUS_CLOSED) {
				$contratline->date_end_real = $now;
				$contratline->date_cloture = $now;	// For backward compatibility
				$contratline->user_closing_id = $user->id;
				$contratline->statut = ContratLigne::STATUS_CLOSED;
				$result = $contratline->close_line($user, $now, $comment, $notrigger);
				if ($result < 0) {
					$error++;
					$this->error = $contratline->error;
					$this->errors = $contratline->errors;
					break;
				}
			}
		}

		if (!$error && $this->statut == 0) {
			$result = $this->validate($user, '', $notrigger);
			if ($result < 0) {
				$error++;
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

	/**
	 * Validate a contract
	 *
	 * @param	User	$user      		Object User
	 * @param   string	$force_number	Reference to force on contract (not implemented yet)
	 * @param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 * @return	int						Return integer <0 if KO, >0 if OK
	 */
	public function validate(User $user, $force_number = '', $notrigger = 0)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		global $conf;

		$now = dol_now();

		$error = 0;
		dol_syslog(get_class($this).'::validate user='.$user->id.', force_number='.$force_number);


		$this->db->begin();

		$this->fetch_thirdparty();

		// A contract is validated so we can move thirdparty to status customer
		if (!getDolGlobalString('CONTRACT_DISABLE_AUTOSET_AS_CLIENT_ON_CONTRACT_VALIDATION') && $this->thirdparty->fournisseur == 0) {
			$result = $this->thirdparty->setAsCustomer();
		}

		// Define new ref
		if ($force_number) {
			$num = $force_number;
		} elseif (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happened, but when it occurs, the test save life
			$num = $this->getNextNumRef($this->thirdparty);
		} else {
			$num = $this->ref;
		}
		$this->newref = dol_sanitizeFileName($num);

		if ($num) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET ref = '".$this->db->escape($num)."', statut = 1";
			//$sql.= ", fk_user_valid = ".$user->id.", date_valid = '".$this->db->idate($now)."'";
			$sql .= " WHERE rowid = ".((int) $this->id)." AND statut = 0";

			dol_syslog(get_class($this)."::validate", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$error++;
				$this->error = $this->db->lasterror();
			}

			// Trigger calls
			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('CONTRACT_VALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->oldref = $this->ref;

				// Rename directory if dir was a temporary ref
				if (preg_match('/^[\(]?PROV/i', $this->ref)) {
					// Now we rename also files into index
					$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'contract/".$this->db->escape($this->newref)."'";
					$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'contract/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
					$resql = $this->db->query($sql);
					if (!$resql) {
						$error++;
						$this->error = $this->db->lasterror();
					}
					$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filepath = 'contract/".$this->db->escape($this->newref)."'";
					$sql .= " WHERE filepath = 'contract/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
					$resql = $this->db->query($sql);
					if (!$resql) {
						$error++;
						$this->error = $this->db->lasterror();
					}

					// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
					$oldref = dol_sanitizeFileName($this->ref);
					$newref = dol_sanitizeFileName($num);
					$dirsource = $conf->contract->dir_output.'/'.$oldref;
					$dirdest = $conf->contract->dir_output.'/'.$newref;
					if (!$error && file_exists($dirsource)) {
						dol_syslog(get_class($this)."::validate rename dir ".$dirsource." into ".$dirdest);

						if (@rename($dirsource, $dirdest)) {
							dol_syslog("Rename ok");
							// Rename docs starting with $oldref with $newref
							$listoffiles = dol_dir_list($conf->contract->dir_output.'/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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

			// Set new ref and define current statut
			if (!$error) {
				$this->ref = $num;
				$this->status = self::STATUS_VALIDATED;
				$this->statut = self::STATUS_VALIDATED;	// deprecated
				$this->date_validation = $now;
			}
		} else {
			$error++;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Unvalidate a contract
	 *
	 * @param	User	$user      		Object User
	 * @param	int		$notrigger		1=Does not execute triggers, 0=execute triggers
	 * @return	int						Return integer <0 if KO, >0 if OK
	 */
	public function reopen($user, $notrigger = 0)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$now = dol_now();

		$error = 0;
		dol_syslog(get_class($this).'::reopen user='.$user->id);

		$this->db->begin();

		$this->fetch_thirdparty();

		$sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET statut = 0";
		//$sql.= ", fk_user_valid = null, date_valid = null";
		$sql .= " WHERE rowid = ".((int) $this->id)." AND statut = 1";

		dol_syslog(get_class($this)."::validate", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$error++;
			$this->error = $this->db->lasterror();
		}

		// Trigger calls
		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('CONTRACT_REOPEN', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Set new ref and define current status
		if (!$error) {
			$this->statut = self::STATUS_DRAFT;
			$this->status = self::STATUS_DRAFT;
			$this->date_validation = $now;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Load a contract from database
	 *
	 *  @param	int		$id     		Id of contract to load
	 *  @param	string	$ref			Ref
	 *  @param	string	$ref_customer	Customer ref
	 *  @param	string	$ref_supplier	Supplier ref
	 *  @param	int		$noextrafields	0=Default to load extrafields, 1=No extrafields
	 *  @param	int		$nolines		0=Default to load lines, 1=No lines
	 *  @return int     				Return integer <0 if KO, 0 if not found or if two records found for same ref, Id of contract if OK
	 */
	public function fetch($id, $ref = '', $ref_customer = '', $ref_supplier = '', $noextrafields = 0, $nolines = 0)
	{
		$sql = "SELECT rowid, statut as status, ref, fk_soc as thirdpartyid,";
		$sql .= " ref_supplier, ref_customer,";
		$sql .= " ref_ext,";
		$sql .= " entity,";
		$sql .= " date_contrat as datecontrat,";
		$sql .= " fk_user_author,";
		$sql .= " fk_projet as fk_project,";
		$sql .= " fk_commercial_signature, fk_commercial_suivi,";
		$sql .= " note_private, note_public, model_pdf, last_main_doc, extraparams";
		$sql .= " FROM ".MAIN_DB_PREFIX."contrat";
		if (!$id) {
			$sql .= " WHERE entity IN (".getEntity('contract').")";
		} else {
			$sql .= " WHERE rowid = ".(int) $id;
		}
		if ($ref_customer) {
			$sql .= " AND ref_customer = '".$this->db->escape($ref_customer)."'";
		}
		if ($ref_supplier) {
			$sql .= " AND ref_supplier = '".$this->db->escape($ref_supplier)."'";
		}
		if ($ref) {
			$sql .= " AND ref = '".$this->db->escape($ref)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num > 1) {
				$this->error = 'Fetch found several records.';
				dol_syslog($this->error, LOG_ERR);
				$result = -2;
				return 0;
			} elseif ($num) {   // $num = 1
				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					$this->id = $obj->rowid;
					$this->ref = (!isset($obj->ref) || !$obj->ref) ? $obj->rowid : $obj->ref;
					$this->ref_customer = $obj->ref_customer;
					$this->ref_supplier = $obj->ref_supplier;
					$this->ref_ext = $obj->ref_ext;
					$this->entity = $obj->entity;
					$this->statut = $obj->status;
					$this->status = $obj->status;

					$this->date_contrat = $this->db->jdate($obj->datecontrat);
					$this->date_creation = $this->db->jdate($obj->datecontrat);

					$this->user_author_id = $obj->fk_user_author;

					$this->commercial_signature_id = $obj->fk_commercial_signature;
					$this->commercial_suivi_id = $obj->fk_commercial_suivi;

					$this->note_private = $obj->note_private;
					$this->note_public = $obj->note_public;
					$this->model_pdf = $obj->model_pdf;

					$this->fk_projet = $obj->fk_project; // deprecated
					$this->fk_project = $obj->fk_project;

					$this->socid = $obj->thirdpartyid;
					$this->fk_soc = $obj->thirdpartyid;
					$this->last_main_doc = $obj->last_main_doc;
					$this->extraparams = (isset($obj->extraparams) ? (array) json_decode($obj->extraparams, true) : null);

					$this->db->free($resql);

					// Retrieve all extrafields
					// fetch optionals attributes and labels
					if (empty($noextrafields)) {
						$result = $this->fetch_optionals();
						if ($result < 0) {
							$this->error = $this->db->lasterror();
							return -4;
						}
					}

					// Lines
					if (empty($nolines)) {
						if ($result >= 0 && !empty($this->table_element_line)) {
							$result = $this->fetch_lines();
							if ($result < 0) {
								$this->error = $this->db->lasterror();
								return -3;
							}
						}
					}

					return $this->id;
				} else {
					dol_syslog(get_class($this)."::fetch Contract failed");
					$this->error = "Fetch contract failed";
					return -1;
				}
			} else {
				dol_syslog(get_class($this)."::fetch Contract not found");
				$this->error = "Contract not found";
				return 0;
			}
		} else {
			dol_syslog(get_class($this)."::fetch Error searching contract");
			$this->error = $this->db->error();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Load lines array into this->lines.
	 *  This set also nbofserviceswait, nbofservicesopened, nbofservicesexpired and nbofservicesclosed
	 *
	 *	@param		int				$only_services			0=Default for all, 1=Force only services (depending on setup, we may also have physical products in a contract)
	 *	@param		int				$loadalsotranslation	0=Default to not load translations, 1=Load also translations of product descriptions
	 *  @param		int				$noextrafields			0=Default to load extrafields, 1=Do not load the extrafields of lines
	 *  @return 	array|int  								Return array of contract lines
	 */
	public function fetch_lines($only_services = 0, $loadalsotranslation = 0, $noextrafields = 0)
	{
		// phpcs:enable
		$this->nbofservices = 0;
		$this->nbofserviceswait = 0;
		$this->nbofservicesopened = 0;
		$this->nbofservicesexpired = 0;
		$this->nbofservicesclosed = 0;

		$total_ttc = 0;
		$total_vat = 0;
		$total_ht = 0;

		$now = dol_now();

		$this->lines = array();
		$pos = 0;

		// Selects contract lines related to a product
		$sql = "SELECT p.label as product_label, p.description as product_desc, p.ref as product_ref, p.fk_product_type as product_type,";
		$sql .= " d.rowid, d.fk_contrat, d.statut as status, d.description, d.price_ht, d.vat_src_code, d.tva_tx, d.localtax1_tx, d.localtax2_tx, d.localtax1_type, d.localtax2_type, d.qty, d.remise_percent, d.subprice, d.fk_product_fournisseur_price as fk_fournprice, d.buy_price_ht as pa_ht,";
		$sql .= " d.total_ht,";
		$sql .= " d.total_tva,";
		$sql .= " d.total_localtax1,";
		$sql .= " d.total_localtax2,";
		$sql .= " d.total_ttc,";
		$sql .= " d.info_bits, d.fk_product,";
		$sql .= " d.date_ouverture_prevue as date_start,";
		$sql .= " d.date_ouverture as date_start_real,";
		$sql .= " d.date_fin_validite as date_end,";
		$sql .= " d.date_cloture as date_end_real,";
		$sql .= " d.fk_user_author,";
		$sql .= " d.fk_user_ouverture,";
		$sql .= " d.fk_user_cloture,";
		$sql .= " d.fk_unit,";
		$sql .= " d.product_type as type,";
		$sql .= " d.rang";
		$sql .= " FROM ".MAIN_DB_PREFIX."contratdet as d LEFT JOIN ".MAIN_DB_PREFIX."product as p ON d.fk_product = p.rowid";
		$sql .= " WHERE d.fk_contrat = ".((int) $this->id);
		if ($only_services == 1) {
			$sql .= " AND d.product_type = 1";
		}
		$sql .= " ORDER by d.rang ASC";

		dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;

			while ($i < $num) {
				$objp = $this->db->fetch_object($result);

				$line = new ContratLigne($this->db);

				$line->id = $objp->rowid;
				$line->ref				= $objp->rowid;
				$line->fk_contrat = $objp->fk_contrat;
				$line->desc = $objp->description; // Description line
				$line->qty				= $objp->qty;
				$line->vat_src_code 	= $objp->vat_src_code;
				$line->tva_tx = $objp->tva_tx;
				$line->localtax1_tx		= $objp->localtax1_tx;
				$line->localtax2_tx		= $objp->localtax2_tx;
				$line->localtax1_type	= $objp->localtax1_type;
				$line->localtax2_type	= $objp->localtax2_type;
				$line->subprice			= $objp->subprice;
				$line->statut = $objp->status;
				$line->status = $objp->status;
				$line->remise_percent	= $objp->remise_percent;
				$line->price_ht			= $objp->price_ht;
				$line->price = $objp->price_ht; // For backward compatibility
				$line->total_ht			= $objp->total_ht;
				$line->total_tva		= $objp->total_tva;
				$line->total_localtax1	= $objp->total_localtax1;
				$line->total_localtax2	= $objp->total_localtax2;
				$line->total_ttc		= $objp->total_ttc;
				$line->fk_product = (($objp->fk_product > 0) ? $objp->fk_product : 0);
				$line->info_bits		= $objp->info_bits;
				$line->type = $objp->type;

				$line->fk_fournprice = $objp->fk_fournprice;
				$marginInfos = getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $objp->fk_fournprice, $objp->pa_ht);
				$line->pa_ht = $marginInfos[0];

				$line->fk_user_author = $objp->fk_user_author;
				$line->fk_user_ouverture = $objp->fk_user_ouverture;
				$line->fk_user_cloture = $objp->fk_user_cloture;
				$line->fk_unit = $objp->fk_unit;

				$line->ref = $objp->product_ref; // deprecated
				$line->product_ref = $objp->product_ref; // Product Ref
				$line->product_type		= $objp->product_type; // Product Type
				$line->product_desc		= $objp->product_desc; // Product Description
				$line->product_label	= $objp->product_label; // Product Label

				$line->description = $objp->description;

				$line->date_start            = $this->db->jdate($objp->date_start);
				$line->date_start_real       = $this->db->jdate($objp->date_start_real);
				$line->date_end              = $this->db->jdate($objp->date_end);
				$line->date_end_real         = $this->db->jdate($objp->date_end_real);
				// For backward compatibility
				//$line->date_ouverture_prevue = $this->db->jdate($objp->date_ouverture_prevue);
				//$line->date_ouverture        = $this->db->jdate($objp->date_ouverture);
				//$line->date_fin_validite     = $this->db->jdate($objp->date_fin_validite);
				//$line->date_cloture          = $this->db->jdate($objp->date_cloture);
				//$line->date_debut_prevue = $this->db->jdate($objp->date_ouverture_prevue);
				//$line->date_debut_reel   = $this->db->jdate($objp->date_ouverture);
				//$line->date_fin_prevue   = $this->db->jdate($objp->date_fin_validite);
				//$line->date_fin_reel     = $this->db->jdate($objp->date_cloture);

				$line->rang     = $objp->rang;

				// Retrieve all extrafields for contract line
				// fetch optionals attributes and labels
				if (empty($noextrafields)) {
					$line->fetch_optionals();
				}

				// multilangs
				if (getDolGlobalInt('MAIN_MULTILANGS') && !empty($objp->fk_product) && !empty($loadalsotranslation)) {
					$tmpproduct = new Product($this->db);
					$tmpproduct->fetch($objp->fk_product);
					$tmpproduct->getMultiLangs();

					$line->multilangs = $tmpproduct->multilangs;
				}

				$this->lines[$pos] = $line;

				$this->lines_id_index_mapper[$line->id] = $pos;

				//dol_syslog("1 ".$line->desc);
				//dol_syslog("2 ".$line->product_desc);

				if ($line->statut == ContratLigne::STATUS_INITIAL) {
					$this->nbofserviceswait++;
				}
				if ($line->statut == ContratLigne::STATUS_OPEN && (empty($line->date_end) || $line->date_end >= $now)) {
					$this->nbofservicesopened++;
				}
				if ($line->statut == ContratLigne::STATUS_OPEN && (!empty($line->date_end) && $line->date_end < $now)) {
					$this->nbofservicesexpired++;
				}
				if ($line->statut == ContratLigne::STATUS_CLOSED) {
					$this->nbofservicesclosed++;
				}

				$total_ttc += $objp->total_ttc; // TODO Not saved into database
				$total_vat += $objp->total_tva;
				$total_ht += $objp->total_ht;

				$i++;
				$pos++;
			}
			$this->db->free($result);
		} else {
			dol_syslog(get_class($this)."::Fetch Error when reading lines of contracts linked to products");
			return -3;
		}

		// Now set the global properties on contract not stored into database.
		$this->nbofservices = count($this->lines);
		$this->total_ttc = (float) price2num($total_ttc);
		$this->total_tva = (float) price2num($total_vat);
		$this->total_ht = (float) price2num($total_ht);

		return $this->lines;
	}

	/**
	 *  Create a contract into database
	 *
	 *  @param	User	$user       User that create
	 *  @return int  				Return integer <0 if KO, id of contract if OK
	 */
	public function create($user)
	{
		global $conf, $langs, $mysoc;

		// Check parameters
		$paramsok = 1;
		if ($this->commercial_signature_id <= 0) {
			$langs->load("commercial");
			$this->error .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("SalesRepresentativeSignature"));
			$paramsok = 0;
		}
		if ($this->commercial_suivi_id <= 0) {
			$langs->load("commercial");
			$this->error .= ($this->error ? "<br>" : '');
			$this->error .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("SalesRepresentativeFollowUp"));
			$paramsok = 0;
		}
		if (!$paramsok) {
			return -1;
		}


		$this->db->begin();

		$now = dol_now();

		// Insert contract
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."contrat (datec, fk_soc, fk_user_author, date_contrat,";
		$sql .= " fk_commercial_signature, fk_commercial_suivi, fk_projet,";
		$sql .= " ref, entity, note_private, note_public, ref_customer, ref_supplier, ref_ext)";
		$sql .= " VALUES ('".$this->db->idate($now)."', ".((int) $this->socid).", ".((int) $user->id);
		$sql .= ", ".(dol_strlen($this->date_contrat) != 0 ? "'".$this->db->idate($this->date_contrat)."'" : "NULL");
		$sql .= ",".($this->commercial_signature_id > 0 ? ((int) $this->commercial_signature_id) : "NULL");
		$sql .= ",".($this->commercial_suivi_id > 0 ? ((int) $this->commercial_suivi_id) : "NULL");
		$sql .= ",".($this->fk_project > 0 ? ((int) $this->fk_project) : "NULL");
		$sql .= ", ".(dol_strlen($this->ref) <= 0 ? "null" : "'".$this->db->escape($this->ref)."'");
		$sql .= ", ".((int) $conf->entity);
		$sql .= ", ".(!empty($this->note_private) ? ("'".$this->db->escape($this->note_private)."'") : "NULL");
		$sql .= ", ".(!empty($this->note_public) ? ("'".$this->db->escape($this->note_public)."'") : "NULL");
		$sql .= ", ".(!empty($this->ref_customer) ? ("'".$this->db->escape($this->ref_customer)."'") : "NULL");
		$sql .= ", ".(!empty($this->ref_supplier) ? ("'".$this->db->escape($this->ref_supplier)."'") : "NULL");
		$sql .= ", ".(!empty($this->ref_ext) ? ("'".$this->db->escape($this->ref_ext)."'") : "NULL");
		$sql .= ")";
		$resql = $this->db->query($sql);

		if ($resql) {
			$error = 0;

			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."contrat");

			// Load object modContract
			$module = (getDolGlobalString('CONTRACT_ADDON') ? $conf->global->CONTRACT_ADDON : 'mod_contract_serpis');
			if (substr($module, 0, 13) == 'mod_contract_' && substr($module, -3) == 'php') {
				$module = substr($module, 0, dol_strlen($module) - 4);
			}
			$result = dol_include_once('/core/modules/contract/'.$module.'.php');
			if ($result > 0) {
				$modCodeContract = new $module();
				'@phan-var-force CommonNumRefGenerator $modCodeContrat';

				if (!empty($modCodeContract->code_auto)) {
					// Force the ref to a draft value if numbering module is an automatic numbering
					$sql = 'UPDATE '.MAIN_DB_PREFIX."contrat SET ref='(PROV".$this->id.")' WHERE rowid=".((int) $this->id);
					if ($this->db->query($sql)) {
						if ($this->id) {
							$this->ref = "(PROV".$this->id.")";
						}
					}
				}
			}

			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			// Insert business contacts ('SALESREPSIGN','contrat')
			if (!$error) {
				$result = $this->add_contact($this->commercial_signature_id, 'SALESREPSIGN', 'internal');
				if ($result < 0) {
					$error++;
				}
			}

			// Insert business contacts ('SALESREPFOLL','contrat')
			if (!$error) {
				$result = $this->add_contact($this->commercial_suivi_id, 'SALESREPFOLL', 'internal');
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error) {
				if (!empty($this->linkedObjectsIds) && empty($this->linked_objects)) {	// To use new linkedObjectsIds instead of old linked_objects
					$this->linked_objects = $this->linkedObjectsIds; // TODO Replace linked_objects with linkedObjectsIds
				}

				// Add object linked
				if (!$error && $this->id && !empty($this->linked_objects) && is_array($this->linked_objects)) {
					foreach ($this->linked_objects as $origin => $tmp_origin_id) {
						if (is_array($tmp_origin_id)) {       // New behaviour, if linked_object can have several links per type, so is something like array('contract'=>array(id1, id2, ...))
							foreach ($tmp_origin_id as $origin_id) {
								$ret = $this->add_object_linked($origin, $origin_id);
								if (!$ret) {
									$this->error = $this->db->lasterror();
									$error++;
								}
							}
						} else { // Old behaviour, if linked_object has only one link per type, so is something like array('contract'=>id1))
							$origin_id = $tmp_origin_id;
							$ret = $this->add_object_linked($origin, $origin_id);
							if (!$ret) {
								$this->error = $this->db->lasterror();
								$error++;
							}
						}
					}
				}

				if (!$error && $this->id && getDolGlobalString('MAIN_PROPAGATE_CONTACTS_FROM_ORIGIN') && !empty($this->origin) && !empty($this->origin_id)) {   // Get contact from origin object
					$originforcontact = $this->origin;
					$originidforcontact = $this->origin_id;
					if ($originforcontact == 'shipping') {     // shipment and order share the same contacts. If creating from shipment we take data of order
						require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
						$exp = new Expedition($this->db);
						$exp->fetch($this->origin_id);
						$exp->fetchObjectLinked();
						if (count($exp->linkedObjectsIds['commande']) > 0) {
							foreach ($exp->linkedObjectsIds['commande'] as $key => $value) {
								$originforcontact = 'commande';
								$originidforcontact = $value;
								break; // We take first one
							}
						}
					}

					$sqlcontact = "SELECT ctc.code, ctc.source, ec.fk_socpeople FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as ctc";
					$sqlcontact .= " WHERE element_id = ".((int) $originidforcontact)." AND ec.fk_c_type_contact = ctc.rowid AND ctc.element = '".$this->db->escape($originforcontact)."'";

					$resqlcontact = $this->db->query($sqlcontact);
					if ($resqlcontact) {
						while ($objcontact = $this->db->fetch_object($resqlcontact)) {
							if ($objcontact->source == 'internal' && in_array($objcontact->code, array('SALESREPSIGN', 'SALESREPFOLL'))) {
								continue; // ignore this, already forced previously
							}

							$this->add_contact($objcontact->fk_socpeople, $objcontact->code, $objcontact->source); // May failed because of duplicate key or because code of contact type does not exists for new object
						}
					} else {
						dol_print_error($this->db, $resqlcontact);
					}
				}
			}

			if (!$error) {
				// Call trigger
				$result = $this->call_trigger('CONTRACT_CREATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers

				if (!$error) {
					$this->db->commit();
					return $this->id;
				} else {
					dol_syslog(get_class($this)."::create - 30 - ".$this->error, LOG_ERR);
					$this->db->rollback();
					return -3;
				}
			} else {
				$this->error = "Failed to add contract";
				dol_syslog(get_class($this)."::create - 20 - ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $langs->trans("UnknownError").": ".$this->db->error();
			dol_syslog(get_class($this)."::create - 10 - ".$this->error, LOG_ERR);

			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Delete object
	 *
	 *  @param	User		$user       User that deletes
	 *  @return int         			Return integer < 0 if KO, > 0 if OK
	 */
	public function delete($user)
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$this->db->begin();

		// Call trigger
		$result = $this->call_trigger('CONTRACT_DELETE', $user);
		if ($result < 0) {
			$error++;
		}
		// End call triggers

		if (!$error) {
			// Delete linked contacts
			$res = $this->delete_linked_contact();
			if ($res < 0) {
				dol_syslog(get_class($this)."::delete error", LOG_ERR);
				$error++;
			}
		}

		if (!$error) {
			// Delete linked object
			$res = $this->deleteObjectLinked();
			if ($res < 0) {
				$error++;
			}
		}

		// Delete lines
		if (!$error) {
			// Delete contratdet extrafields
			$main = MAIN_DB_PREFIX.'contratdet';
			$ef = $main."_extrafields";
			$sql = "DELETE FROM ".$ef." WHERE fk_object IN (SELECT rowid FROM ".$main." WHERE fk_contrat = ".((int) $this->id).")";

			dol_syslog(get_class($this)."::delete contratdet_extrafields", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->error();
				$error++;
			}
		}

		if (!$error) {
			// Delete contratdet
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."contratdet";
			$sql .= " WHERE fk_contrat=".((int) $this->id);

			dol_syslog(get_class($this)."::delete contratdet", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->error();
				$error++;
			}
		}

		// Delete llx_ecm_files
		if (!$error) {
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX."ecm_files WHERE src_object_type = '".$this->db->escape($this->table_element.(empty($this->module) ? "" : "@".$this->module))."' AND src_object_id = ".((int) $this->id);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				$error++;
			}
		}

		// Delete contract
		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."contrat";
			$sql .= " WHERE rowid=".((int) $this->id);

			dol_syslog(get_class($this)."::delete contrat", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->error();
				$error++;
			}
		}

		// Removed extrafields
		if (!$error) {
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$error++;
				dol_syslog(get_class($this)."::delete error -3 ".$this->error, LOG_ERR);
			}
		}

		if (!$error) {
			// We remove directory
			$ref = dol_sanitizeFileName($this->ref);
			if ($conf->contrat->dir_output) {
				$dir = $conf->contrat->multidir_output[$this->entity]."/".$ref;
				if (file_exists($dir)) {
					$res = @dol_delete_dir_recursive($dir);
					if (!$res) {
						$this->error = 'ErrorFailToDeleteDir';
						$error++;
					}
				}
			}
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Update object into database
	 *
	 *  @param	User	$user        User that modifies
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return int     		   	 Return integer <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0)
	{
		global $conf;
		$error = 0;

		// Clean parameters
		if (empty($this->fk_commercial_signature) && $this->commercial_signature_id > 0) {
			$this->fk_commercial_signature = $this->commercial_signature_id;
		}
		if (empty($this->fk_commercial_suivi) && $this->commercial_suivi_id > 0) {
			$this->fk_commercial_suivi = $this->commercial_suivi_id;
		}
		if (empty($this->socid) && $this->fk_soc > 0) {
			$this->socid = (int) $this->fk_soc;
		}
		if (empty($this->fk_project) && $this->projet > 0) {
			$this->fk_project = (int) $this->projet;
		}

		if (isset($this->ref)) {
			$this->ref = trim($this->ref);
		}
		if (isset($this->ref_customer)) {
			$this->ref_customer = trim($this->ref_customer);
		}
		if (isset($this->ref_supplier)) {
			$this->ref_supplier = trim($this->ref_supplier);
		}
		if (isset($this->ref_ext)) {
			$this->ref_ext = trim($this->ref_ext);
		}
		if (isset($this->entity)) {
			$this->entity = (int) $this->entity;
		}
		if (isset($this->statut)) {
			$this->statut = (int) $this->statut;
		}
		if (isset($this->status)) {
			$this->status = (int) $this->status;
		}
		if (isset($this->socid)) {
			$this->socid = (int) $this->socid;
		}
		if (isset($this->fk_commercial_signature)) {
			$this->fk_commercial_signature = trim($this->fk_commercial_signature);
		}
		if (isset($this->fk_commercial_suivi)) {
			$this->fk_commercial_suivi = trim($this->fk_commercial_suivi);
		}
		if (isset($this->note_private)) {
			$this->note_private = trim($this->note_private);
		}
		if (isset($this->note_public)) {
			$this->note_public = trim($this->note_public);
		}
		if (isset($this->import_key)) {
			$this->import_key = trim($this->import_key);
		}
		//if (isset($this->extraparams)) $this->extraparams=trim($this->extraparams);

		// $this->oldcopy must have been set by the caller of update

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."contrat SET";
		$sql .= " ref=".(isset($this->ref) ? "'".$this->db->escape($this->ref)."'" : "null").",";
		$sql .= " ref_customer=".(isset($this->ref_customer) ? "'".$this->db->escape($this->ref_customer)."'" : "null").",";
		$sql .= " ref_supplier=".(isset($this->ref_supplier) ? "'".$this->db->escape($this->ref_supplier)."'" : "null").",";
		$sql .= " ref_ext=".(isset($this->ref_ext) ? "'".$this->db->escape($this->ref_ext)."'" : "null").",";
		$sql .= " entity=".$conf->entity.",";
		$sql .= " date_contrat=".(dol_strlen($this->date_contrat) != 0 ? "'".$this->db->idate($this->date_contrat)."'" : 'null').",";
		$sql .= " statut=".(isset($this->statut) ? $this->statut : (isset($this->status) ? $this->status : "null")).",";
		$sql .= " fk_soc=".($this->socid > 0 ? $this->socid : "null").",";
		$sql .= " fk_projet=".($this->fk_project > 0 ? $this->fk_project : "null").",";
		$sql .= " fk_commercial_signature=".(isset($this->fk_commercial_signature) ? $this->fk_commercial_signature : "null").",";
		$sql .= " fk_commercial_suivi=".(isset($this->fk_commercial_suivi) ? $this->fk_commercial_suivi : "null").",";
		$sql .= " note_private=".(isset($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "null").",";
		$sql .= " note_public=".(isset($this->note_public) ? "'".$this->db->escape($this->note_public)."'" : "null").",";
		$sql .= " import_key=".(isset($this->import_key) ? "'".$this->db->escape($this->import_key)."'" : "null");
		//$sql.= " extraparams=".(isset($this->extraparams)?"'".$this->db->escape($this->extraparams)."'":"null");
		$sql .= " WHERE rowid=".((int) $this->id);

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$result = $this->insertExtraFields();	// This delete and reinsert extrafields
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error && !$notrigger) {
			// Call triggers
			$result = $this->call_trigger('CONTRACT_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *  Ajoute une ligne de contrat en base
	 *
	 *  @param	string		$desc            	Description of line
	 *  @param  float		$pu_ht              Unit price net
	 *  @param  float	 	$qty             	Quantity
	 *  @param  float		$txtva           	Vat rate
	 *  @param  float		$txlocaltax1        Local tax 1 rate
	 *  @param  float		$txlocaltax2        Local tax 2 rate
	 *  @param  int			$fk_product      	Id produit
	 *  @param  float		$remise_percent  	Percentage discount of the line
	 *  @param  int			$date_start      	Date de debut prevue
	 *  @param  int			$date_end        	Date de fin prevue
	 *	@param	string		$price_base_type	HT or TTC
	 * 	@param  float		$pu_ttc             Prix unitaire TTC
	 * 	@param  int			$info_bits			Bits of type of lines
	 * 	@param  int			$fk_fournprice		Fourn price id
	 *  @param  int			$pa_ht				Buying price HT
	 *  @param	array		$array_options		extrafields array
	 * 	@param 	string		$fk_unit 			Code of the unit to use. Null to use the default one
	 * 	@param 	int			$rang 				Position
	 *  @return int             				Return integer <0 if KO, >0 if OK
	 */
	public function addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $date_start, $date_end, $price_base_type = 'HT', $pu_ttc = 0.0, $info_bits = 0, $fk_fournprice = null, $pa_ht = 0, $array_options = array(), $fk_unit = null, $rang = 0)
	{
		global $user, $langs, $conf, $mysoc;
		$error = 0;

		dol_syslog(get_class($this)."::addline $desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $remise_percent, $date_start, $date_end, $price_base_type, $pu_ttc, $info_bits, $rang");

		// Check parameters
		if ($fk_product <= 0 && empty($desc)) {
			$this->error = "ErrorDescRequiredForFreeProductLines";
			return -1;
		}

		if ($this->statut >= 0) {
			// Clean parameters
			$pu_ht = price2num($pu_ht);
			$pu_ttc = price2num($pu_ttc);
			$pa_ht = price2num($pa_ht);

			// Clean vat code
			$reg = array();
			$vat_src_code = '';
			if (preg_match('/\((.*)\)/', (string) $txtva, $reg)) {
				$vat_src_code = $reg[1];
				$txtva = preg_replace('/\s*\(.*\)/', '', (string) $txtva); // Remove code into vatrate.
			}
			$txtva = price2num($txtva);
			$txlocaltax1 = price2num($txlocaltax1);
			$txlocaltax2 = price2num($txlocaltax2);

			$remise_percent = price2num($remise_percent);
			$qty = price2num($qty);
			if (empty($qty)) {
				$qty = 1;
			}
			if (empty($info_bits)) {
				$info_bits = 0;
			}
			if (empty($pu_ht) || !is_numeric($pu_ht)) {
				$pu_ht = 0;
			}
			if (empty($pu_ttc)) {
				$pu_ttc = 0;
			}
			if (empty($txtva) || !is_numeric($txtva)) {
				$txtva = 0;
			}
			if (empty($txlocaltax1) || !is_numeric($txlocaltax1)) {
				$txlocaltax1 = 0;
			}
			if (empty($txlocaltax2) || !is_numeric($txlocaltax2)) {
				$txlocaltax2 = 0;
			}

			if ($price_base_type == 'HT') {
				$pu = $pu_ht;
			} else {
				$pu = $pu_ttc;
			}

			// Check parameters
			if (empty($remise_percent)) {
				$remise_percent = 0;
			}
			if (empty($rang)) {
				$rang = 0;
			}

			if ($date_start && $date_end && $date_start > $date_end) {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorStartDateGreaterEnd');
				return -1;
			}

			$this->db->begin();

			$localtaxes_type = getLocalTaxesFromRate($txtva.($vat_src_code ? ' ('.$vat_src_code.')' : ''), 0, $this->societe, $mysoc);

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

			$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, 1, $mysoc, $localtaxes_type);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			$total_localtax1 = $tabprice[9];
			$total_localtax2 = $tabprice[10];

			$localtax1_type = $localtaxes_type[0];
			$localtax2_type = $localtaxes_type[2];

			// TODO A virer
			// Anciens indicateurs: $price, $remise (a ne plus utiliser)
			$remise = 0;
			$price = price2num(round($pu_ht, 2));
			if (dol_strlen($remise_percent) > 0) {
				$remise = round(($pu_ht * $remise_percent / 100), 2);
				$price = $pu_ht - $remise;
			}

			if (empty($pa_ht)) {
				$pa_ht = 0;
			}


			// if buy price not defined, define buyprice as configured in margin admin
			if ($pa_ht == 0) {
				$result = $this->defineBuyPrice($pu_ht, $remise_percent, $fk_product);
				if ($result < 0) {
					return -1;
				} else {
					$pa_ht = $result;
				}
			}

			// Insertion dans la base
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."contratdet";
			$sql .= " (fk_contrat, label, description, fk_product, qty, tva_tx, vat_src_code,";
			$sql .= " localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, remise_percent, subprice,";
			$sql .= " total_ht, total_tva, total_localtax1, total_localtax2, total_ttc,";
			$sql .= " info_bits,";
			$sql .= " price_ht, remise, fk_product_fournisseur_price, buy_price_ht";
			if ($date_start > 0) {
				$sql .= ",date_ouverture_prevue";
			}
			if ($date_end > 0) {
				$sql .= ",date_fin_validite";
			}
			$sql .= ", fk_unit";
			$sql .= ", rang";
			$sql .= ") VALUES (";
			$sql .= $this->id.", '', '".$this->db->escape($desc)."',";
			$sql .= ($fk_product > 0 ? $fk_product : "null").",";
			$sql .= " ".((float) $qty).",";
			$sql .= " ".((float) $txtva).",";
			$sql .= " ".($vat_src_code ? "'".$this->db->escape($vat_src_code)."'" : "null").",";
			$sql .= " ".((float) $txlocaltax1).",";
			$sql .= " ".((float) $txlocaltax2).",";
			$sql .= " '".$this->db->escape($localtax1_type)."',";
			$sql .= " '".$this->db->escape($localtax2_type)."',";
			$sql .= " ".price2num($remise_percent).",";
			$sql .= " ".price2num($pu_ht).",";
			$sql .= " ".price2num($total_ht).",".price2num($total_tva).",".price2num($total_localtax1).",".price2num($total_localtax2).",".price2num($total_ttc).",";
			$sql .= " ".((int) $info_bits).",";
			$sql .= " ".price2num($price).",".price2num($remise).",";
			if (isset($fk_fournprice)) {
				$sql .= ' '.((int) $fk_fournprice).',';
			} else {
				$sql .= ' null,';
			}
			if (isset($pa_ht)) {
				$sql .= ' '.price2num($pa_ht);
			} else {
				$sql .= ' null';
			}
			if ($date_start > 0) {
				$sql .= ",'".$this->db->idate($date_start)."'";
			}
			if ($date_end > 0) {
				$sql .= ",'".$this->db->idate($date_end)."'";
			}
			$sql .= ", ".($fk_unit ? "'".$this->db->escape($fk_unit)."'" : "null");
			$sql .= ", ".(!empty($rang) ? (int) $rang : "0");
			$sql .= ")";

			$resql = $this->db->query($sql);
			if ($resql) {
				$contractlineid = $this->db->last_insert_id(MAIN_DB_PREFIX."contratdet");

				if (!$error) {
					$contractline = new ContratLigne($this->db);
					$contractline->array_options = $array_options;
					$contractline->id = $contractlineid;
					$result = $contractline->insertExtraFields();
					if ($result < 0) {
						$this->errors = array_merge($this->errors, $contractline->errors);
						$this->error = $contractline->error;
						$error++;
					}
				}

				if (empty($error)) {
					// Call trigger
					$this->context['line_id'] = $contractlineid;
					$result = $this->call_trigger('LINECONTRACT_INSERT', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}

				if ($error) {
					$this->db->rollback();
					return -1;
				} else {
					$this->db->commit();
					return $contractlineid;
				}
			} else {
				$this->db->rollback();
				$this->error = $this->db->error()." sql=".$sql;
				return -1;
			}
		} else {
			dol_syslog(get_class($this)."::addline ErrorTryToAddLineOnValidatedContract", LOG_ERR);
			return -2;
		}
	}

	/**
	 *  Mets a jour une ligne de contrat
	 *
	 *  @param	int			$rowid            	Id de la ligne de facture
	 *  @param  string		$desc             	Description de la ligne
	 *  @param  float		$pu               	Prix unitaire
	 *  @param  float		$qty              	Quantite
	 *  @param  float		$remise_percent   	Percentage discount of the line
	 *  @param  int			$date_start       	Date de debut prevue
	 *  @param  int			$date_end         	Date de fin prevue
	 *  @param  float		$tvatx            	Taux TVA
	 *  @param  float		$localtax1tx      	Local tax 1 rate
	 *  @param  float		$localtax2tx      	Local tax 2 rate
	 *  @param  int|string	$date_start_real  	Date de debut reelle
	 *  @param  int|string	$date_end_real    	Date de fin reelle
	 *	@param	string		$price_base_type	HT or TTC
	 * 	@param  int			$info_bits			Bits of type of lines
	 * 	@param  int			$fk_fournprice		Fourn price id
	 *  @param  int			$pa_ht				Buying price HT
	 *  @param	array		$array_options		extrafields array
	 * 	@param 	string		$fk_unit 			Code of the unit to use. Null to use the default one
	 * 	@param 	int			$rang 				Position
	 *  @return int              				Return integer <0 if KO, >0 if OK
	 */
	public function updateline($rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $tvatx, $localtax1tx = 0.0, $localtax2tx = 0.0, $date_start_real = '', $date_end_real = '', $price_base_type = 'HT', $info_bits = 0, $fk_fournprice = null, $pa_ht = 0, $array_options = array(), $fk_unit = null, $rang = 0)
	{
		global $user, $conf, $langs, $mysoc;

		$error = 0;

		// Clean parameters
		$qty = trim((string) $qty);
		$desc = trim($desc);
		$desc = trim($desc);
		$price = price2num($pu);
		$tvatx = price2num($tvatx);
		$localtax1tx = price2num($localtax1tx);
		$localtax2tx = price2num($localtax2tx);
		$pa_ht = price2num($pa_ht);
		if (empty($fk_fournprice)) {
			$fk_fournprice = 0;
		}
		if (empty($rang)) {
			$rang = 0;
		}

		$subprice = $price;
		$remise = 0;
		if (dol_strlen($remise_percent) > 0) {
			$remise = round(($pu * $remise_percent / 100), 2);
			$price = $pu - $remise;
		} else {
			$remise_percent = 0;
		}

		if ($date_start && $date_end && $date_start > $date_end) {
			$langs->load("errors");
			$this->error = $langs->trans('ErrorStartDateGreaterEnd');
			return -1;
		}

		dol_syslog(get_class($this)."::updateline $rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $date_start_real, $date_end_real, $tvatx, $localtax1tx, $localtax2tx, $price_base_type, $info_bits, $rang");

		$this->db->begin();

		// Calcul du total TTC et de la TVA pour la ligne a partir de
		// qty, pu, remise_percent et tvatx
		// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
		// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

		$localtaxes_type = getLocalTaxesFromRate($tvatx, 0, $this->societe, $mysoc);
		$tvatx = preg_replace('/\s*\(.*\)/', '', $tvatx); // Remove code into vatrate.

		$tabprice = calcul_price_total($qty, $pu, $remise_percent, (float) price2num($tvatx), $localtax1tx, $localtax2tx, 0, $price_base_type, $info_bits, 1, $mysoc, $localtaxes_type);
		$total_ht  = $tabprice[0];
		$total_tva = $tabprice[1];
		$total_ttc = $tabprice[2];
		$total_localtax1 = $tabprice[9];
		$total_localtax2 = $tabprice[10];

		$localtax1_type = (empty($localtaxes_type[0]) ? '' : $localtaxes_type[0]);
		$localtax2_type = (empty($localtaxes_type[2]) ? '' : $localtaxes_type[2]);

		// TODO A virer
		// Anciens indicateurs: $price, $remise (a ne plus utiliser)
		$remise = 0;
		$price = price2num(round($pu, 2));
		if (dol_strlen($remise_percent) > 0) {
			$remise = round(($pu * $remise_percent / 100), 2);
			$price = $pu - $remise;
		}

		if (empty($pa_ht)) {
			$pa_ht = 0;
		}

		// if buy price not defined, define buyprice as configured in margin admin
		if ($pa_ht == 0) {
			$result = $this->defineBuyPrice($pu, $remise_percent);
			if ($result < 0) {
				return -1;
			} else {
				$pa_ht = $result;
			}
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet set description = '".$this->db->escape($desc)."'";
		$sql .= ",price_ht = ".((float) price2num($price));
		$sql .= ",subprice = ".((float) price2num($subprice));
		$sql .= ",remise = ".((float) price2num($remise));
		$sql .= ",remise_percent = ".((float) price2num($remise_percent));
		$sql .= ",qty = ".((float) $qty);
		$sql .= ",tva_tx = ".((float) price2num($tvatx));
		$sql .= ",localtax1_tx = ".((float) price2num($localtax1tx));
		$sql .= ",localtax2_tx = ".((float) price2num($localtax2tx));
		$sql .= ",localtax1_type='".$this->db->escape($localtax1_type)."'";
		$sql .= ",localtax2_type='".$this->db->escape($localtax2_type)."'";
		$sql .= ", total_ht = ".((float) price2num($total_ht));
		$sql .= ", total_tva = ".((float) price2num($total_tva));
		$sql .= ", total_localtax1 = ".((float) price2num($total_localtax1));
		$sql .= ", total_localtax2 = ".((float) price2num($total_localtax2));
		$sql .= ", total_ttc = ".((float) price2num($total_ttc));
		$sql .= ", fk_product_fournisseur_price=".($fk_fournprice > 0 ? $fk_fournprice : "null");
		$sql .= ", buy_price_ht = ".((float) price2num($pa_ht));
		if ($date_start > 0) {
			$sql .= ",date_ouverture_prevue = '".$this->db->idate($date_start)."'";
		} else {
			$sql .= ",date_ouverture_prevue = null";
		}
		if ($date_end > 0) {
			$sql .= ",date_fin_validite = '".$this->db->idate($date_end)."'";
		} else {
			$sql .= ",date_fin_validite = null";
		}
		if ($date_start_real > 0) {
			$sql .= ",date_ouverture = '".$this->db->idate($date_start_real)."'";
		} else {
			$sql .= ",date_ouverture = null";
		}
		if ($date_end_real > 0) {
			$sql .= ",date_cloture = '".$this->db->idate($date_end_real)."'";
		} else {
			$sql .= ",date_cloture = null";
		}
		$sql .= ", fk_unit = ".($fk_unit > 0 ? ((int) $fk_unit) : "null");
		$sql .= ", rang = ".(!empty($rang) ? ((int) $rang) : "0");
		$sql .= " WHERE rowid = ".((int) $rowid);

		dol_syslog(get_class($this)."::updateline", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if (is_array($array_options) && count($array_options) > 0) { // For avoid conflicts if trigger used
				$contractline = new ContratLigne($this->db);
				$contractline->fetch($rowid);

				// We replace values in $contractline->array_options only for entries defined into $array_options
				foreach ($array_options as $key => $value) {
					$contractline->array_options[$key] = $array_options[$key];
				}

				$result = $contractline->insertExtraFields();
				if ($result < 0) {
					$this->errors[] = $contractline->error;
					$error++;
				}
			}

			if (empty($error)) {
				// Call trigger
				$this->context['line_id'] = $rowid;
				$result = $this->call_trigger('LINECONTRACT_MODIFY', $user);
				if ($result < 0) {
					$this->db->rollback();
					return -3;
				}
				// End call triggers

				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->db->rollback();
			$this->error = $this->db->error();
			dol_syslog(get_class($this)."::updateline Erreur -1");
			return -1;
		}
	}

	/**
	 *  Delete a contract line
	 *
	 *  @param	int		$idline		Id of line to delete
	 *	@param  User	$user       User that delete
	 *  @return int         		>0 if OK, <0 if KO
	 */
	public function deleteLine($idline, User $user)
	{
		$error = 0;

		if ($this->statut >= 0) {
			// Call trigger
			$this->context['line_id'] = $idline;
			$result = $this->call_trigger('LINECONTRACT_DELETE', $user);
			if ($result < 0) {
				return -1;
			}
			// End call triggers

			$this->db->begin();

			$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element_line;
			$sql .= " WHERE rowid = ".((int) $idline);

			dol_syslog(get_class($this)."::deleteline", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = "Error ".$this->db->lasterror();
				$error++;
			}

			if (!$error) {
				// Remove extrafields
				$contractline = new ContratLigne($this->db);
				$contractline->id = $idline;
				$result = $contractline->deleteExtraFields();
				if ($result < 0) {
					$error++;
					$this->error = "Error ".get_class($this)."::deleteline deleteExtraFields error -4 ".$contractline->error;
				}
			}

			if (empty($error)) {
				$this->db->commit();
				return 1;
			} else {
				dol_syslog(get_class($this)."::deleteline ERROR:".$this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Update statut of contract according to services
	 *
	 *	@param	User	$user		Object user
	 *	@return int     			Return integer <0 if KO, >0 if OK
	 *  @deprecated					This function will never be used. Status of a contract is status of its lines.
	 */
	public function update_statut($user)
	{
		// phpcs:enable
		dol_syslog(__METHOD__." is deprecated", LOG_WARNING);

		// If draft, we keep it (should not happen)
		if ($this->statut == 0) {
			return 1;
		}

		// Load $this->lines array
		//		$this->fetch_lines();

		//		$newstatut=1;
		//		foreach($this->lines as $key => $contractline)
		//		{
		//			//			if ($contractline)         // Loop on each service
		//		}

		return 1;
	}


	/**
	 *  Return label of a contract status
	 *
	 *  @param  int		$mode       	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto, 7=Same than 6 with fixed length
	 *  @return string      			Label
	 */
	public function getLibStatut($mode)
	{
		return $this->LibStatut($this->statut, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the label of a given contrat status
	 *
	 *  @param	int		$status      	Id status
	 *  @param  int		$mode       	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label (status of services), 5=Short label + Picto, 6=Long label + Picto (status of services), 7=Same than 6 with fixed length (status of services)
	 *	@return string      			Label
	 */
	public function LibStatut($status, $mode)
	{
		// phpcs:enable
		global $langs;

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			$langs->load("contracts");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('ContractStatusDraft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ContractStatusValidated');
			$this->labelStatus[self::STATUS_CLOSED] = $langs->transnoentitiesnoconv('ContractStatusClosed');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('ContractStatusDraft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ContractStatusValidated');
			$this->labelStatusShort[self::STATUS_CLOSED] = $langs->transnoentitiesnoconv('ContractStatusClosed');
		}

		$statusType = 'status'.$status;
		if ($status == self::STATUS_VALIDATED) {
			$statusType = 'status6';
		}

		if ($mode == 4 || $mode == 6 || $mode == 7) {
			$text = '';
			if ($mode == 4) {
				$text = '<span class="hideonsmartphone">';
				$text .= ($this->nbofserviceswait + $this->nbofservicesopened + $this->nbofservicesexpired + $this->nbofservicesclosed);
				$text .= ' '.$langs->trans("Services");
				$text .= ': &nbsp; &nbsp; ';
				$text .= '</span>';
			}
			$text .= ($mode == 7 ? '<span class="nowraponall">' : '');
			$text .= ($mode != 7 || $this->nbofserviceswait > 0) ? ($this->nbofserviceswait.ContratLigne::LibStatut(0, 3, -1, 'class="marginleft2"')).(($mode != 7 || $this->nbofservicesopened || $this->nbofservicesexpired || $this->nbofservicesclosed) ? ' &nbsp; ' : '') : '';
			$text .= ($mode == 7 ? '</span><span class="nowraponall">' : '');
			$text .= ($mode != 7 || $this->nbofservicesopened > 0) ? ($this->nbofservicesopened.ContratLigne::LibStatut(4, 3, 0, 'class="marginleft2"')).(($mode != 7 || $this->nbofservicesexpired || $this->nbofservicesclosed) ? ' &nbsp; ' : '') : '';
			$text .= ($mode == 7 ? '</span><span class="nowraponall">' : '');
			$text .= ($mode != 7 || $this->nbofservicesexpired > 0) ? ($this->nbofservicesexpired.ContratLigne::LibStatut(4, 3, 1, 'class="marginleft2"')).(($mode != 7 || $this->nbofservicesclosed) ? ' &nbsp; ' : '') : '';
			$text .= ($mode == 7 ? '</span><span class="nowraponall">' : '');
			$text .= ($mode != 7 || $this->nbofservicesclosed > 0) ? ($this->nbofservicesclosed.ContratLigne::LibStatut(5, 3, -1, 'class="marginleft2"')) : '';
			$text .= ($mode == 7 ? '</span>' : '');
			return $text;
		} else {
			return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
		}
	}

	/**
	 * getTooltipContentArray
	 * @param array $params params to construct tooltip data
	 * @since v18
	 * @return array
	 */
	public function getTooltipContentArray($params)
	{
		global $conf, $langs, $user;

		$langs->load('contracts');

		$datas = [];
		$nofetch = !empty($params['nofetch']);

		if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			return ['optimize' => $langs->trans("ShowContract")];
		}
		if ($user->hasRight('contrat', 'lire')) {
			$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Contract").'</u>';
			/* Status of a contract is status of all services, so disabled
			if (isset($this->statut)) {
				$label .= ' '.$this->getLibStatut(5);
			}*/
			$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.($this->ref ? $this->ref : $this->id);
			if (!$nofetch) {
				$langs->load('companies');
				if (empty($this->thirdparty)) {
					$this->fetch_thirdparty();
				}
				$datas['customer'] = '<br><b>'.$langs->trans('Customer').':</b> '.$this->thirdparty->getNomUrl(1, '', 0, 1);
			}
			$datas['refcustomer'] = '<br><b>'.$langs->trans('RefCustomer').':</b> '. $this->ref_customer;
			if (!$nofetch) {
				$langs->load('project');
				if (is_null($this->project) || (is_object($this->project) && $this->project->isEmpty())) {
					$res = $this->fetch_project();
					if ($res > 0 && $this->project instanceof Project) {
						$datas['project'] = '<br><b>'.$langs->trans('Project').':</b> '.$this->project->getNomUrl(1, '', 0, 1);
					}
				}
			}
			$datas['refsupplier'] = '<br><b>'.$langs->trans('RefSupplier').':</b> '.$this->ref_supplier;
			if (!empty($this->total_ht)) {
				$datas['amountht'] = '<br><b>'.$langs->trans('AmountHT').':</b> '.price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($this->total_tva)) {
				$datas['vatamount'] = '<br><b>'.$langs->trans('VAT').':</b> '.price($this->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($this->total_ttc)) {
				$datas['amounttc'] = '<br><b>'.$langs->trans('AmountTTC').':</b> '.price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
			}
		}
		return $datas;
	}

	/**
	 *	Return clickable name (with picto eventually)
	 *
	 *	@param	int		$withpicto					0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param	int		$maxlength					Max length of ref
	 *  @param	int     $notooltip					1=Disable tooltip
	 *  @param  int     $save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								Chaine avec URL
	 */
	public function getNomUrl($withpicto = 0, $maxlength = 0, $notooltip = 0, $save_lastsearch_value = -1)
	{
		global $conf, $langs, $user, $hookmanager;

		$result = '';

		$url = DOL_URL_ROOT.'/contrat/card.php?id='.$this->id;

		//if ($option !== 'nolink')
		//{
		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
			$add_save_lastsearch_values = 1;
		}
		if ($add_save_lastsearch_values) {
			$url .= '&save_lastsearch_values=1';
		}
		//}
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'nofetch' => 1,
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

		$linkclose = '';
		if (empty($notooltip) && $user->hasRight('contrat', 'lire')) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowContract");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.'"';
		}
		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), (($withpicto != 2) ? 'class="paddingright"' : ''), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= ($this->ref ? $this->ref : $this->id);
		}
		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array('contractdao'));
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
	 *  Charge les information d'ordre info dans l'objet contrat
	 *
	 *  @param  int		$id     id du contrat a charger
	 *  @return	void
	 */
	public function info($id)
	{
		$sql = "SELECT c.rowid, c.ref, c.datec,";
		$sql .= " c.tms as date_modification,";
		$sql .= " fk_user_author";
		$sql .= " FROM ".MAIN_DB_PREFIX."contrat as c";
		$sql .= " WHERE c.rowid = ".((int) $id);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;
				$this->ref = (!$obj->ref) ? $obj->rowid : $obj->ref;

				$this->user_creation_id = $obj->fk_user_author;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->date_modification);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of line rowid
	 *
	 *  @param	int		$status     Status of lines to get
	 *  @return array|int       	Array of line's rowid or <0 if error
	 */
	public function array_detail($status = -1)
	{
		// phpcs:enable
		$tab = array();

		$sql = "SELECT cd.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
		$sql .= " WHERE fk_contrat =".((int) $this->id);
		if ($status >= 0) {
			$sql .= " AND statut = ".((int) $status);
		}

		dol_syslog(get_class($this)."::array_detail()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$tab[$i] = $obj->rowid;
				$i++;
			}
			return $tab;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *  Return list of other contracts for the same company than current contract
	 *
	 *	@param	string		$option					'all' or 'others'
	 *	@param	array		$status					sort contracts having these status
	 *	@param  array		$product_categories		sort contracts containing these product categories
	 *	@param	array		$line_status			sort contracts where lines have these status
	 *  @return array|int   						Array of contracts id or <0 if error
	 */
	public function getListOfContracts($option = 'all', $status = [], $product_categories = [], $line_status = [])
	{
		$tab = array();

		$sql = "SELECT c.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."contrat as c";
		if (!empty($product_categories)) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd ON cd.fk_contrat = c.rowid";
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON cp.fk_product = cd.fk_product AND cp.fk_categorie IN (".$this->db->sanitize(implode(', ', $product_categories)).")";
		}
		$sql .= " WHERE c.fk_soc =".((int) $this->socid);
		$sql .= ($option == 'others') ? " AND c.rowid <> ".((int) $this->id) : "";
		$sql .= (!empty($status)) ? " AND c.statut IN (".$this->db->sanitize(implode(', ', $status)).")" : "";
		$sql .= (!empty($line_status)) ? " AND cd.statut IN (".$this->db->sanitize(implode(', ', $line_status)).")" : "";
		$sql .= " GROUP BY c.rowid";

		dol_syslog(get_class($this)."::getOtherContracts()", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$contrat = new Contrat($this->db);
				$contrat->fetch($obj->rowid);
				$tab[$contrat->id] = $contrat;
				$i++;
			}
			return $tab;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @param	User	$user           Object user
	 *      @param  string	$mode           "inactive" pour services a activer, "expired" pour services expires
	 *      @return WorkboardResponse|int Return integer <0 if KO, WorkboardResponse if OK
	 */
	public function load_board($user, $mode)
	{
		// phpcs:enable
		global $conf, $langs;

		$this->from = " FROM ".MAIN_DB_PREFIX."contrat as c";
		$this->from .= ", ".MAIN_DB_PREFIX."contratdet as cd";
		$this->from .= ", ".MAIN_DB_PREFIX."societe as s";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$this->from .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}

		if ($mode == 'inactive') {
			$sql = "SELECT cd.rowid, cd.date_ouverture_prevue as datefin";
			$sql .= $this->from;
			$sql .= " WHERE c.statut = 1";
			$sql .= " AND c.rowid = cd.fk_contrat";
			$sql .= " AND cd.statut = 0";
		} elseif ($mode == 'expired') {
			$sql = "SELECT cd.rowid, cd.date_fin_validite as datefin";
			$sql .= $this->from;
			$sql .= " WHERE c.statut = 1";
			$sql .= " AND c.rowid = cd.fk_contrat";
			$sql .= " AND cd.statut = 4";
			$sql .= " AND cd.date_fin_validite < '".$this->db->idate(dol_now())."'";
		} elseif ($mode == 'active') {
			$sql = "SELECT cd.rowid, cd.date_fin_validite as datefin";
			$sql .= $this->from;
			$sql .= " WHERE c.statut = 1";
			$sql .= " AND c.rowid = cd.fk_contrat";
			$sql .= " AND cd.statut = 4";
			//$datetouse = dol_now();
			//$sql.= " AND cd.date_fin_validite < '".$this->db->idate($datetouse)."'";
		}
		$sql .= " AND c.fk_soc = s.rowid";
		$sql .= " AND c.entity = ".((int) $conf->entity);
		if ($user->socid) {
			$sql .= " AND c.fk_soc = ".((int) $user->socid);
		}
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " AND c.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$langs->load("contracts");
			$now = dol_now();

			if ($mode == 'inactive') {
				$warning_delay = $conf->contrat->services->inactifs->warning_delay;
				$label = $langs->trans("BoardNotActivatedServices");
				$labelShort = $langs->trans("BoardNotActivatedServicesShort");
				$url = DOL_URL_ROOT.'/contrat/services_list.php?mainmenu=commercial&leftmenu=contracts&search_status=0&sortfield=cd.date_fin_validite&sortorder=asc';
			} elseif ($mode == 'expired') {
				$warning_delay = $conf->contrat->services->expires->warning_delay;
				$url = DOL_URL_ROOT.'/contrat/services_list.php?mainmenu=commercial&leftmenu=contracts&search_status=4&filter=expired&sortfield=cd.date_fin_validite&sortorder=asc';
				$label = $langs->trans("BoardExpiredServices");
				$labelShort = $langs->trans("BoardExpiredServicesShort");
			} else {
				$warning_delay = $conf->contrat->services->expires->warning_delay;
				$url = DOL_URL_ROOT.'/contrat/services_list.php?mainmenu=commercial&leftmenu=contracts&search_status=4&sortfield=cd.date_fin_validite&sortorder=asc';
				//$url.= '&op2day='.$arraydatetouse['mday'].'&op2month='.$arraydatetouse['mon'].'&op2year='.$arraydatetouse['year'];
				//if ($warning_delay >= 0) $url.='&amp;filter=expired';
				$label = $langs->trans("BoardRunningServices");
				$labelShort = $langs->trans("BoardRunningServicesShort");
			}

			$response = new WorkboardResponse();
			$response->warning_delay = $warning_delay / 60 / 60 / 24;
			$response->label = $label;
			$response->labelShort = $labelShort;
			$response->url = $url;
			$response->img = img_object('', "contract");

			while ($obj = $this->db->fetch_object($resql)) {
				$response->nbtodo++;

				if ($obj->datefin && $this->db->jdate($obj->datefin) < ($now - $warning_delay)) {
					$response->nbtodolate++;
				}
			}

			return $response;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *   Load the indicators this->nb for state board
	 *
	 *   @return     int         Return integer <0 si ko, >0 si ok
	 */
	public function loadStateBoard()
	{
		global $conf, $user;

		$this->nb = array();
		$clause = "WHERE";

		$sql = "SELECT count(c.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."contrat as c";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON c.fk_soc = s.rowid";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql .= " WHERE sc.fk_user = ".((int) $user->id);
			$clause = "AND";
		}
		$sql .= " ".$clause." c.entity = ".$conf->entity;

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["contracts"] = $obj->nb;
			}
			$this->db->free($resql);
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}


	/* gestion des contacts d'un contrat */

	/**
	 *  Return id des contacts clients de facturation
	 *
	 *  @return     array       Liste des id contacts facturation
	 */
	public function getIdBillingContact()
	{
		return $this->getIdContact('external', 'BILLING');
	}

	/**
	 *  Return id des contacts clients de prestation
	 *
	 *  @return     array       Liste des id contacts prestation
	 */
	public function getIdServiceContact()
	{
		return $this->getIdContact('external', 'SERVICE');
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
		global $user, $langs, $conf;

		// Load array of products prodids
		$num_prods = 0;
		$prodids = array();
		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."product";
		$sql .= " WHERE entity IN (".getEntity('product').")";
		$sql .= " AND tosell = 1";
		$sql .= $this->db->plimit(100);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num_prods = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_prods) {
				$i++;
				$row = $this->db->fetch_row($resql);
				$prodids[$i] = $row[0];
			}
		}

		// Initialise parameters
		$this->id = 0;
		$this->specimen = 1;

		$this->ref = 'SPECIMEN';
		$this->ref_customer = 'SPECIMENCUST';
		$this->ref_supplier = 'SPECIMENSUPP';
		$this->socid = 1;
		$this->status = 0;
		$this->date_creation = (dol_now() - 3600 * 24 * 7);
		$this->date_contrat = dol_now();
		$this->commercial_signature_id = 1;
		$this->commercial_suivi_id = 1;
		$this->note_private = 'This is a comment (private)';
		$this->note_public = 'This is a comment (public)';
		$this->fk_project = 0;
		// Lines
		$nbp = 5;
		$xnbp = 0;
		while ($xnbp < $nbp) {
			$line = new ContratLigne($this->db);
			$line->qty = 1;
			$line->subprice = 100;
			$line->tva_tx = 19.6;
			$line->remise_percent = 10;
			$line->total_ht = 90;
			$line->total_ttc = 107.64; // 90 * 1.196
			$line->total_tva = 17.64;
			$line->date_start = dol_now() - 500000;
			$line->date_start_real = dol_now() - 200000;
			$line->date_end = dol_now() + 500000;
			$line->date_end_real = dol_now() - 100000;
			if ($num_prods > 0) {
				$prodid = mt_rand(1, $num_prods);
				$line->fk_product = $prodids[$prodid];
			}
			$this->lines[$xnbp] = $line;
			$xnbp++;
		}

		return 1;
	}

	/**
	 * 	Create an array of order lines
	 *
	 * 	@return int		>0 if OK, <0 if KO
	 */
	public function getLinesArray()
	{
		return $this->fetch_lines();
	}

	/**
	 * 	Create an array of associated tickets
	 *
	 * 	@return array|int		Array o tickets or <0 if KO
	 */
	public function getTicketsArray()
	{
		global $user;

		$ticket = new Ticket($this->db);
		$nbTicket =  $ticket->fetchAll($user, 'ASC', 't.datec', '', 0, '', array('t.fk_contract' => $this->id));

		return ($nbTicket < 0 ? $nbTicket : $ticket->lines);
	}


	/**
	 *  Create a document onto disk according to template module.
	 *
	 * 	@param	    string		$modele			Force model to use ('' to not force)
	 * 	@param		Translate	$outputlangs	Object langs to use for output
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @param   	null|array  $moreparams     Array to provide more information
	 * 	@return     int         				Return integer < 0 if KO, 0 = no doc generated, > 0 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		if (!dol_strlen($modele)) {
			$modele = '';	// No doc template/generation by default

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('CONTRACT_ADDON_PDF')) {
				$modele = getDolGlobalString('CONTRACT_ADDON_PDF');
			}
		}

		if (empty($modele)) {
			return 0;
		} else {
			$langs->load("contracts");
			$outputlangs->load("products");

			$modelpath = "core/modules/contract/doc/";
			return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
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
			'contrat'
		);

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}

	/**
	 * Function used to replace a product id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old product id
	 * @param int $dest_id New product id
	 * @return bool
	 */
	public static function replaceProduct(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'contratdet'
		);

		return CommonObject::commonReplaceProduct($db, $origin_id, $dest_id, $tables);
	}

	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param	User	$user		  User making the clone
	 * @param   int     $socid        Id of thirdparty
	 * @param   int     $notrigger	  1=Does not execute triggers, 0= execute triggers
	 * @return  int                   New id of clone
	 */
	public function createFromClone(User $user, $socid = 0, $notrigger = 0)
	{
		global $db, $langs, $conf, $hookmanager, $extrafields;

		dol_include_once('/projet/class/project.class.php');

		$error = 0;

		$this->fetch($this->id);

		// Load dest object
		$clonedObj = clone $this;
		$clonedObj->socid = $socid;

		$this->db->begin();

		$objsoc = new Societe($this->db);

		$objsoc->fetch($clonedObj->socid);

		// Clean data
		$clonedObj->statut = 0;
		// Clean extrafields
		if (is_array($clonedObj->array_options) && count($clonedObj->array_options) > 0) {
			$extrafields->fetch_name_optionals_label($this->table_element);
			foreach ($clonedObj->array_options as $key => $option) {
				$shortkey = preg_replace('/options_/', '', $key);
				//var_dump($shortkey); var_dump($extrafields->attributes[$this->element]['unique'][$shortkey]);
				if (!empty($extrafields->attributes[$this->element]['unique'][$shortkey])) {
					//var_dump($key); var_dump($clonedObj->array_options[$key]); exit;
					unset($clonedObj->array_options[$key]);
				}
			}
		}

		if (!getDolGlobalString('CONTRACT_ADDON') || !is_readable(DOL_DOCUMENT_ROOT."/core/modules/contract/" . getDolGlobalString('CONTRACT_ADDON').".php")) {
			$this->error = 'ErrorSetupNotComplete';
			dol_syslog($this->error);
			return -1;
		}

		// Set ref
		require_once DOL_DOCUMENT_ROOT."/core/modules/contract/" . getDolGlobalString('CONTRACT_ADDON').'.php';
		$obj = getDolGlobalString('CONTRACT_ADDON');
		$modContract = new $obj();
		'@phan-var-force CommonNumRefGenerator $modContrat';
		$clonedObj->ref = $modContract->getNextValue($objsoc, $clonedObj);

		// get extrafields so they will be clone
		foreach ($this->lines as $line) {
			$line->fetch_optionals($line->id);
		}

		// Create clone
		$clonedObj->context['createfromclone'] = 'createfromclone';
		$result = $clonedObj->create($user);
		if ($result < 0) {
			$error++;
			$this->error = $clonedObj->error;
			$this->errors[] = $clonedObj->error;
		} else {
			// copy external contacts if same company
			if ($this->socid == $clonedObj->socid) {
				if ($clonedObj->copy_linked_contact($this, 'external') < 0) {
					$error++;
				}
			}
		}

		if (!$error) {
			foreach ($this->lines as $line) {
				$result = $clonedObj->addline($line->description, $line->subprice, $line->qty, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->fk_product, $line->remise_percent, $line->date_start, $line->date_cloture, 'HT', 0, $line->info_bits, $line->fk_fournprice, $line->pa_ht, $line->array_options, $line->fk_unit, $line->rang);
				if ($result < 0) {
					$error++;
					$this->error = $clonedObj->error;
					$this->errors[] = $clonedObj->error;
				}
			}
		}

		if (!$error) {
			// Hook of thirdparty module
			if (is_object($hookmanager)) {
				$parameters = array(
						'objFrom' => $this,
						'clonedObj' => $clonedObj
				);
				$action = '';
				$reshook = $hookmanager->executeHooks('createFrom', $parameters, $clonedObj, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$this->setErrorsFromObject($hookmanager);
					$error++;
				}
			}
		}

		unset($clonedObj->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $clonedObj->id;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK
	 * Loop on each contract lines and update the end of date. Do not execute the update if there is one pending invoice linked to contract.
	 *
	 * @param	int		$thirdparty_id			Thirdparty id
	 * @param	int		$delayindaysshort 		To renew the resources x day before (positive value) or after (negative value) the end of date (default is 0)
	 * @return	int								0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doAutoRenewContracts($thirdparty_id = 0, $delayindaysshort = 0)
	{
		global $langs, $user;

		$langs->load("agenda");

		$now = dol_now();

		$enddatetoscan = dol_time_plus_duree($now, -1 * abs($delayindaysshort), 'd');

		$error = 0;
		$this->output = '';
		$this->error = '';

		$contractlineprocessed = array();
		$contractignored = array();
		$contracterror = array();

		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT c.rowid, c.ref_customer, cd.rowid as lid, cd.date_fin_validite, p.duration';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'contrat as c, '.MAIN_DB_PREFIX.'contratdet as cd';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON p.rowid = cd.fk_product';
		$sql .= ' WHERE cd.fk_contrat = c.rowid';
		$sql .= " AND date_format(cd.date_fin_validite, '%Y-%m-%d') <= date_format('".$this->db->idate($enddatetoscan)."', '%Y-%m-%d')";
		$sql .= " AND cd.statut = 4";
		if ($thirdparty_id > 0) {
			$sql .= " AND c.fk_soc = ".((int) $thirdparty_id);
		}
		//print $sql;

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';

			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					if (!empty($contractlineprocessed[$obj->lid]) || !empty($contractignored[$obj->rowid]) || !empty($contracterror[$obj->rowid])) {
						continue;
					}

					// Load contract
					$object = new Contrat($this->db);
					$object->fetch($obj->rowid);		// fetch also lines
					//$object->fetch_thirdparty();

					if ($object->id <= 0) {
						$error++;
						$this->errors[] = 'Failed to load contract with id='.$obj->rowid;
						continue;
					}

					dol_syslog("* Process contract line in doRenewalContracts for contract id=".$object->id." ref=".$object->ref." ref_customer=".$object->ref_customer." contract line id=".$obj->lid);

					// Update expiration date of line
					$expirationdate = $this->db->jdate($obj->date_fin_validite);
					$duration_value = preg_replace('/[^0-9]/', '', $obj->duration);
					$duration_unit = preg_replace('/\d/', '', $obj->duration);
					//var_dump($expirationdate.' '.$enddatetoscan);

					// Load linked ->linkedObjects (objects linked)
					// @TODO Comment this line and then make the search if there is n open invoice(s) by doing a dedicated SQL COUNT request to fill $contractcanceled.
					$object->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 1);

					// Test if there is at least 1 open invoice
					if (isset($object->linkedObjects['facture']) && is_array($object->linkedObjects['facture']) && count($object->linkedObjects['facture']) > 0) {
						// Sort array of linked invoices by ascending date
						usort($object->linkedObjects['facture'], array('Contrat', 'contractCmpDate'));
						//dol_sort_array($object->linkedObjects['facture'], 'date');

						$someinvoicenotpaid = 0;
						foreach ($object->linkedObjects['facture'] as $idinvoice => $invoice) {
							if ($invoice->statut == Facture::STATUS_DRAFT) {
								continue;
							}	// Draft invoice are not invoice not paid

							if (empty($invoice->paye)) {
								$someinvoicenotpaid++;
							}
						}
						if ($someinvoicenotpaid) {
							$this->output .= 'Contract '.$object->ref.' is qualified for renewal but there is '.$someinvoicenotpaid.' invoice(s) unpayed so we cancel renewal'."\n";
							$contractignored[$object->id] = $object->ref;
							continue;
						}
					}

					if ($expirationdate && $expirationdate < $enddatetoscan) {
						dol_syslog("Define the newdate of end of services from expirationdate=".$expirationdate);
						$newdate = $expirationdate;
						$protecti = 0;	// $protecti is to avoid infinite loop
						while ($newdate < $enddatetoscan && $protecti < 1000) {
							$newdate = dol_time_plus_duree($newdate, (int) $duration_value, $duration_unit);
							$protecti++;
						}

						if ($protecti < 1000) {	// If not, there is a pb
							// We will update the end of date of contrat, so first we refresh contract data
							dol_syslog("We will update the end of date of contract with newdate = ".dol_print_date($newdate, 'dayhourrfc'));

							$this->db->begin();

							$errorforlocaltransaction = 0;

							$label = 'Renewal of contrat '.$object->ref.' line '.$obj->lid;
							$comment = 'Renew date of contract '.$object->ref.' line '.$obj->lid.' by doAutoRenewContracts';

							$sqlupdate = 'UPDATE '.MAIN_DB_PREFIX."contratdet SET date_fin_validite = '".$this->db->idate($newdate)."'";
							$sqlupdate .= ' WHERE rowid = '.((int) $obj->lid);
							$resqlupdate = $this->db->query($sqlupdate);
							if ($resqlupdate) {
								$contractlineprocessed[$obj->lid] = $object->ref;

								$actioncode = 'RENEW_CONTRACT';
								$now = dol_now();

								// Create an event
								$actioncomm = new ActionComm($this->db);
								$actioncomm->type_code    = 'AC_OTH_AUTO';		// Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
								$actioncomm->code         = 'AC_'.$actioncode;
								$actioncomm->label        = $label;
								$actioncomm->datep        = $now;
								$actioncomm->datef        = $now;
								$actioncomm->percentage   = -1;   // Not applicable
								$actioncomm->socid        = $object->socid;
								$actioncomm->authorid     = $user->id;   // User saving action
								$actioncomm->userownerid  = $user->id;	// Owner of action
								$actioncomm->fk_element   = $object->id;
								$actioncomm->elementtype  = 'contract';
								$actioncomm->note_private = $comment;

								$ret = $actioncomm->create($user);       // User creating action
							} else {
								$contracterror[$object->id] = $object->ref;

								$error++;
								$errorforlocaltransaction++;
								$this->error = $this->db->lasterror();
							}

							if (! $errorforlocaltransaction) {
								$this->db->commit();
							} else {
								$this->db->rollback();
							}
						} else {
							$error++;
							$this->error = "Bad value for newdate in doAutoRenewContracts - expirationdate=".$expirationdate." enddatetoscan=".$enddatetoscan." duration_value=".$duration_value." duration_unit=".$duration_value;
							dol_syslog($this->error, LOG_ERR);
						}
					}
				}
				$i++;
			}
		} else {
			$error++;
			$this->error = $this->db->lasterror();
		}

		$this->output .= count($contractlineprocessed).' contract line(s) with end date before '.dol_print_date($enddatetoscan, 'day').' were renewed'.(count($contractlineprocessed) > 0 ? ' : '.implode(',', $contractlineprocessed) : '');

		return ($error ? 1 : 0);
	}

	/**
	 * Used to sort lines by date
	 *
	 * @param	Object	$a		1st element to test
	 * @param	Object	$b		2nd element to test
	 * @return int
	 */
	public static function contractCmpDate($a, $b)
	{
		if ($a->date == $b->date) {
			return strcmp((string) $a->id, (string) $b->id);
		}
		return ($a->date < $b->date) ? -1 : 1;
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
			$return .= '<br><div class="info-box-label inline-block valignmiddle">'.$tmpthirdparty->getNomUrl(1).'</div>';
		}
		if (property_exists($this, 'date_contrat')) {
			$return .= '<br><span class="opacitymedium valignmiddle">'.$langs->trans("DateContract").' : </span><span class="info-box-label valignmiddle">'.dol_print_date($this->date_contrat, 'day').'</span>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<br><div class="info-box-status valignmiddle">'.$this->getLibStatut(7).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';

		return $return;
	}

	// @Todo getLibSignedStatus, LibSignedStatus

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
 *	Class to manage lines of contracts
 */
class ContratLigne extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'contratdet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'contratdet';

	/**
	 * @see CommonObjectLine
	 */
	public $parent_element = 'contrat';

	/**
	 * @see CommonObjectLine
	 */
	public $fk_parent_attribute = 'fk_contrat';

	/**
	 * @var string 	Name to use for 'features' parameter to check module permissions user->rights->feature with restrictedArea().
	 * 				Undefined means same value than $element. Can be use to force a check on another element for example for class of line, we mention here the parent element.
	 */
	public $element_for_permission = 'contrat';

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var string Ref
	 */
	public $ref;

	/**
	 * @var int ID
	 */
	public $fk_contrat;

	/**
	 * @var int ID
	 */
	public $fk_product;

	public $statut; // 0 inactive, 4 active, 5 closed
	public $type; // 0 for product, 1 for service

	/**
	 * @var string
	 * @deprecated
	 */
	public $label;

	/**
	 * @var string
	 * @deprecated
	 */
	public $libelle;

	/**
	 * @var string description
	 */
	public $description;

	public $product_type; // 0 for product, 1 for service
	public $product_ref;
	public $product_label;

	public $date_commande;

	public $date_start; // date start planned
	public $date_start_real; // date start real
	public $date_end; // date end planned
	public $date_end_real; // date end real

	public $tva_tx;
	public $vat_src_code;
	public $localtax1_tx;
	public $localtax2_tx;
	public $localtax1_type; // Local tax 1 type
	public $localtax2_type; // Local tax 2 type
	public $qty;
	public $remise_percent;
	public $remise;

	/**
	 * @var int ID
	 */
	public $fk_remise_except;

	/**
	 * Unit price before taxes
	 * @var float
	 */
	public $subprice;

	/**
	 * @var float
	 * @deprecated Use $price_ht instead
	 * @see $price_ht
	 */
	public $price;

	/**
	 * @var float price without tax
	 */
	public $price_ht;

	public $total_ht;
	public $total_tva;
	public $total_localtax1;
	public $total_localtax2;
	public $total_ttc;

	/**
	 * @var int 	ID
	 */
	public $fk_fournprice;

	public $pa_ht;

	/**
	 * @var int		Info bits
	 */
	public $info_bits;

	/**
	 * @var int 	ID of user that insert the service
	 */
	public $fk_user_author;

	/**
	 * @var int 	ID of user opening the service
	 */
	public $fk_user_ouverture;

	/**
	 * @var int 	ID of user closing the service
	 */
	public $fk_user_cloture;

	/**
	 * @var string	Comment
	 */
	public $commentaire;


	/**
	 * @var int line rank
	 */
	public $rang = 0;


	const STATUS_INITIAL = 0;
	const STATUS_OPEN = 4;
	const STATUS_CLOSED = 5;


	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 10),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 30, 'index' => 1),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 35),
		'qty' => array('type' => 'integer', 'label' => 'Quantity', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 35, 'isameasure' => 1),
		'total_ht' => array('type' => 'integer', 'label' => 'AmountHT', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 36, 'isameasure' => 1),
		'total_tva' => array('type' => 'integer', 'label' => 'AmountVAT', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 37, 'isameasure' => 1),
		'total_ttc' => array('type' => 'integer', 'label' => 'AmountTTC', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 38, 'isameasure' => 1),
		//'datec' =>array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>-1, 'position'=>40),
		//'fk_soc' =>array('type'=>'integer:Societe:societe/class/societe.class.php', 'label'=>'ThirdParty', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>70),
		'fk_contrat' => array('type' => 'integer:Contrat:contrat/class/contrat.class.php', 'label' => 'Contract', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 70),
		'fk_product' => array('type' => 'integer:Product:product/class/product.class.php:1', 'label' => 'Product', 'enabled' => 1, 'visible' => -1, 'position' => 75),
		//'fk_user_author' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Fk user author', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>90),
		'note_private' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => 1, 'visible' => 0, 'position' => 105),
		'note_public' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => 1, 'visible' => 0, 'position' => 110),
		//'model_pdf' =>array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>1, 'visible'=>0, 'position'=>115),
		//'import_key' =>array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'visible'=>-2, 'position'=>120),
		//'extraparams' =>array('type'=>'varchar(255)', 'label'=>'Extraparams', 'enabled'=>1, 'visible'=>-1, 'position'=>125),
		'fk_user_ouverture' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserStartingService', 'enabled' => 1, 'visible' => -2, 'notnull' => -1, 'position' => 135),
		'fk_user_cloture' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserClosingService', 'enabled' => 1, 'visible' => -2, 'notnull' => -1, 'position' => 135),
		'statut' => array('type' => 'smallint(6)', 'label' => 'Statut', 'enabled' => 1, 'visible' => -1, 'position' => 500, 'arrayofkeyval' => array(0 => 'Draft', 4 => 'Open', 5 => 'Closed')),
		'rang' => array('type' => 'integer', 'label' => 'Rank', 'enabled' => 1, 'visible' => 0, 'position' => 500, 'default' => '0')
	);
	// END MODULEBUILDER PROPERTIES


	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *  Return label of this contract line status
	 *
	 *  @param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string      		Label of status
	 */
	public function getLibStatut($mode)
	{
		return $this->LibStatut($this->statut, $mode, ((!empty($this->date_end)) ? ($this->date_end < dol_now() ? 1 : 0) : -1));
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return label of a contract line status
	 *
	 *  @param	int		$status     Id status
	 *  @param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *	@param	int		$expired	0=Not expired, 1=Expired, -1=Both or unknown
	 *  @param	string	$moreatt	More attribute
	 *  @param	string	$morelabel	More label
	 *  @return string      		Label of status
	 */
	public static function LibStatut($status, $mode, $expired = -1, $moreatt = '', $morelabel = '')
	{
		// phpcs:enable
		global $langs;
		$langs->load("contracts");

		if ($status == self::STATUS_INITIAL) {
			$labelStatus = $langs->transnoentities("ServiceStatusInitial");
			$labelStatusShort = $langs->transnoentities("ServiceStatusInitial");
		} elseif ($status == self::STATUS_OPEN && $expired == -1) {
			$labelStatus = $langs->transnoentities("ServiceStatusRunning");
			$labelStatusShort = $langs->transnoentities("ServiceStatusRunning");
		} elseif ($status == self::STATUS_OPEN && $expired == 0) {
			$labelStatus = $langs->transnoentities("ServiceStatusNotLate");
			$labelStatusShort = $langs->transnoentities("ServiceStatusNotLateShort");
		} elseif ($status == self::STATUS_OPEN && $expired == 1) {
			$labelStatus = $langs->transnoentities("ServiceStatusLate");
			$labelStatusShort = $langs->transnoentities("ServiceStatusLateShort");
		} elseif ($status == self::STATUS_CLOSED) {
			$labelStatus = $langs->transnoentities("ServiceStatusClosed");
			$labelStatusShort = $langs->transnoentities("ServiceStatusClosed");
		}

		$statusType = 'status'.$status;
		if ($status == self::STATUS_OPEN && $expired == 1) {
			$statusType = 'status1';
		}
		if ($status == self::STATUS_CLOSED) {
			$statusType = 'status6';
		}

		$params = array();
		$reg = array();
		if (preg_match('/class="(.*)"/', $moreatt, $reg)) {
			$params = array('badgeParams' => array('css' => $reg[1]));
		}
		return dolGetStatus($labelStatus.($morelabel ? ' '.$morelabel : ''), $labelStatusShort.($morelabel ? ' '.$morelabel : ''), '', $statusType, $mode, '', $params);
	}

	/**
	 * getTooltipContentArray
	 * @param array $params params to construct tooltip data
	 * @since v18
	 * @return array
	 */
	public function getTooltipContentArray($params)
	{
		global $conf, $langs, $user;

		$datas = [];
		$datas['label'] = $langs->trans("ShowContractOfService").': '.$this->label;
		if (empty($datas['label'])) {
			$datas['label'] = $this->description;
		}

		return $datas;
	}

	/**
	 *	Return clickable name (with picto eventually) for ContratLigne
	 *
	 *  @param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *  @param	int		$maxlength		Max length
	 *  @return	string					Chaine avec URL
	 */
	public function getNomUrl($withpicto = 0, $maxlength = 0)
	{
		global $langs;

		$result = '';
		$label = $langs->trans("ShowContractOfService").': '.$this->label;
		if (empty($label)) {
			$label = $this->description;
		}
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$params = [
				'id' => $this->fk_contrat,
				'objecttype' => $this->element,
			];
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		}

		$link = '<a href="'.DOL_URL_ROOT.'/contrat/card.php?id='.$this->fk_contrat.'"';
		$link .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
		$link .= $dataparams.' class="'.$classfortooltip.'">';
		$linkend = '</a>';

		$picto = 'service';
		if ($this->type == 0) {
			$picto = 'product';
		}

		if ($withpicto) {
			$result .= ($link.img_object($label, $picto, $dataparams.' class="'.$classfortooltip.'"').$linkend);
		}
		if ($withpicto && $withpicto != 2) {
			$result .= ' ';
		}
		if ($withpicto != 2) {
			$result .= $link.($this->product_ref ? $this->product_ref.' ' : '').($this->label ? $this->label : $this->description).$linkend;
		}
		return $result;
	}

	/**
	 *  Load object in memory from database
	 *
	 *  @param	int		$id         Id object
	 *  @param	string	$ref		Ref of contract line
	 *  @return int         		Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id, $ref = '')
	{
		// Check parameters
		if (empty($id) && empty($ref)) {
			return -1;
		}

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.tms,";
		$sql .= " t.fk_contrat,";
		$sql .= " t.fk_product,";
		$sql .= " t.statut,";
		$sql .= " t.label,"; // This field is not used. Only label of product
		$sql .= " p.ref as product_ref,";
		$sql .= " p.label as product_label,";
		$sql .= " p.description as product_desc,";
		$sql .= " p.fk_product_type as product_type,";
		$sql .= " t.description,";
		$sql .= " t.date_commande,";
		$sql .= " t.date_ouverture_prevue as date_start,";
		$sql .= " t.date_ouverture as date_start_real,";
		$sql .= " t.date_fin_validite as date_end,";
		$sql .= " t.date_cloture as date_end_real,";
		$sql .= " t.tva_tx,";
		$sql .= " t.vat_src_code,";
		$sql .= " t.localtax1_tx,";
		$sql .= " t.localtax2_tx,";
		$sql .= " t.localtax1_type,";
		$sql .= " t.localtax2_type,";
		$sql .= " t.qty,";
		$sql .= " t.remise_percent,";
		$sql .= " t.remise,";
		$sql .= " t.fk_remise_except,";
		$sql .= " t.subprice,";
		$sql .= " t.price_ht,";
		$sql .= " t.total_ht,";
		$sql .= " t.total_tva,";
		$sql .= " t.total_localtax1,";
		$sql .= " t.total_localtax2,";
		$sql .= " t.total_ttc,";
		$sql .= " t.fk_product_fournisseur_price as fk_fournprice,";
		$sql .= " t.buy_price_ht as pa_ht,";
		$sql .= " t.info_bits,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.fk_user_ouverture,";
		$sql .= " t.fk_user_cloture,";
		$sql .= " t.commentaire,";
		$sql .= " t.fk_unit,";
		$sql .= " t.rang";
		$sql .= " FROM ".MAIN_DB_PREFIX."contratdet as t LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = t.fk_product";
		if ($id) {
			$sql .= " WHERE t.rowid = ".((int) $id);
		}
		if ($ref) {
			$sql .= " WHERE t.rowid = '".$this->db->escape($ref)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;
				$this->ref   = $obj->rowid;

				$this->tms = $this->db->jdate($obj->tms);
				$this->fk_contrat = $obj->fk_contrat;
				$this->fk_product = $obj->fk_product;
				$this->statut = $obj->statut;
				$this->product_ref = $obj->product_ref;
				$this->product_label = $obj->product_label;
				$this->product_type = $obj->product_type;
				$this->label = $obj->label; // deprecated. We do not use this field. Only ref and label of product, and description of contract line
				$this->description = $obj->description;
				$this->date_commande = $this->db->jdate($obj->date_commande);

				$this->date_start = $this->db->jdate($obj->date_start);
				$this->date_start_real = $this->db->jdate($obj->date_start_real);
				$this->date_end = $this->db->jdate($obj->date_end);
				$this->date_end_real = $this->db->jdate($obj->date_end_real);
				// For backward compatibility
				//$this->date_ouverture_prevue = $this->db->jdate($obj->date_ouverture_prevue);
				//$this->date_ouverture = $this->db->jdate($obj->date_ouverture);
				//$this->date_fin_validite = $this->db->jdate($obj->date_fin_validite);
				//$this->date_cloture = $this->db->jdate($obj->date_cloture);

				$this->tva_tx = $obj->tva_tx;
				$this->vat_src_code = $obj->vat_src_code;
				$this->localtax1_tx = $obj->localtax1_tx;
				$this->localtax2_tx = $obj->localtax2_tx;
				$this->localtax1_type = $obj->localtax1_type;
				$this->localtax2_type = $obj->localtax2_type;
				$this->qty = $obj->qty;
				$this->remise_percent = $obj->remise_percent;
				$this->fk_remise_except = $obj->fk_remise_except;
				$this->subprice = $obj->subprice;
				$this->price_ht = $obj->price_ht;
				$this->total_ht = $obj->total_ht;
				$this->total_tva = $obj->total_tva;
				$this->total_localtax1 = $obj->total_localtax1;
				$this->total_localtax2 = $obj->total_localtax2;
				$this->total_ttc = $obj->total_ttc;
				$this->info_bits = $obj->info_bits;
				$this->fk_user_author = $obj->fk_user_author;
				$this->fk_user_ouverture = $obj->fk_user_ouverture;
				$this->fk_user_cloture = $obj->fk_user_cloture;
				$this->commentaire = $obj->commentaire;
				$this->fk_fournprice = $obj->fk_fournprice;

				$marginInfos = getMarginInfos($obj->subprice, $obj->remise_percent, $obj->tva_tx, $obj->localtax1_tx, $obj->localtax2_tx, $this->fk_fournprice, $obj->pa_ht);
				$this->pa_ht = $marginInfos[0];
				$this->fk_unit = $obj->fk_unit;

				$this->rang = $obj->rang;

				$this->fetch_optionals();
			}

			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *      Update database for contract line
	 *
	 *      @param	User	$user        	User that modify
	 *      @param  int		$notrigger	    0=no, 1=yes (no update trigger)
	 *      @return int         			Return integer <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0)
	{
		global $mysoc;

		$error = 0;

		// Clean parameters
		$this->fk_contrat = (int) $this->fk_contrat;
		$this->fk_product = (int) $this->fk_product;
		$this->statut = (int) $this->statut;
		$this->label = trim($this->label);
		$this->description = trim($this->description);
		$this->vat_src_code = trim($this->vat_src_code);
		$this->tva_tx = trim((string) $this->tva_tx);
		$this->localtax1_tx = trim($this->localtax1_tx);
		$this->localtax2_tx = trim($this->localtax2_tx);
		$this->qty = (float) $this->qty;
		$this->remise_percent = trim((string) $this->remise_percent);
		$this->fk_remise_except = (int) $this->fk_remise_except;
		$this->subprice = (float) price2num($this->subprice);
		$this->price_ht = (float) price2num($this->price_ht);
		$this->info_bits = (int) $this->info_bits;
		$this->fk_user_author = (int) $this->fk_user_author;
		$this->fk_user_ouverture = (int) $this->fk_user_ouverture;
		$this->fk_user_cloture = (int) $this->fk_user_cloture;
		$this->commentaire = trim($this->commentaire);
		$this->rang = (int) $this->rang;
		//if (empty($this->subprice)) $this->subprice = 0;
		if (empty($this->price_ht)) {
			$this->price_ht = 0;
		}
		if (empty($this->total_ht)) {
			$this->total_ht = 0;
		}
		if (empty($this->total_tva)) {
			$this->total_tva = 0;
		}
		if (empty($this->total_ttc)) {
			$this->total_ttc = 0;
		}
		if (empty($this->localtax1_tx)) {
			$this->localtax1_tx = 0;
		}
		if (empty($this->localtax2_tx)) {
			$this->localtax2_tx = 0;
		}
		if (empty($this->remise_percent)) {
			$this->remise_percent = 0;
		}

		// Calcul du total TTC et de la TVA pour la ligne a partir de
		// qty, pu, remise_percent et txtva
		// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
		// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.
		$localtaxes_type = getLocalTaxesFromRate($this->tva_tx, 0, $this->thirdparty, $mysoc);

		$tabprice = calcul_price_total($this->qty, $this->price_ht, $this->remise_percent, $this->tva_tx, $this->localtax1_tx, $this->localtax2_tx, 0, 'HT', 0, 1, $mysoc, $localtaxes_type);
		$this->total_ht  = $tabprice[0];
		$this->total_tva = $tabprice[1];
		$this->total_ttc = $tabprice[2];
		$this->total_localtax1 = $tabprice[9];
		$this->total_localtax2 = $tabprice[10];

		if (empty($this->pa_ht)) {
			$this->pa_ht = 0;
		}

		// if buy price not defined, define buyprice as configured in margin admin
		if ($this->pa_ht == 0) {
			$result = $this->defineBuyPrice($this->subprice, $this->remise_percent, $this->fk_product);
			if ($result < 0) {
				return -1;
			} else {
				$this->pa_ht = $result;
			}
		}

		// $this->oldcopy should have been set by the caller of update (here properties were already modified)
		if (empty($this->oldcopy)) {
			dol_syslog("this->oldcopy should have been set by the caller of update (here properties were already modified)", LOG_WARNING);
			$this->oldcopy = dol_clone($this, 2);
		}

		$this->db->begin();

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET";
		$sql .= " fk_contrat = ".((int) $this->fk_contrat).",";
		$sql .= " fk_product = ".($this->fk_product ? ((int) $this->fk_product) : 'null').",";
		$sql .= " statut = ".((int) $this->statut).",";
		$sql .= " label = '".$this->db->escape($this->label)."',";
		$sql .= " description = '".$this->db->escape($this->description)."',";
		$sql .= " date_commande = ".($this->date_commande != '' ? "'".$this->db->idate($this->date_commande)."'" : "null").",";
		$sql .= " date_ouverture_prevue = ".($this->date_start != '' ? "'".$this->db->idate($this->date_start)."'" : "null").",";
		$sql .= " date_ouverture = ".($this->date_start_real != '' ? "'".$this->db->idate($this->date_start_real)."'" : "null").",";
		$sql .= " date_fin_validite = ".($this->date_end != '' ? "'".$this->db->idate($this->date_end)."'" : "null").",";
		$sql .= " date_cloture = ".($this->date_end_real != '' ? "'".$this->db->idate($this->date_end_real)."'" : "null").",";
		$sql .= " vat_src_code = '".$this->db->escape($this->vat_src_code)."',";
		$sql .= " tva_tx = ".price2num($this->tva_tx).",";
		$sql .= " localtax1_tx = ".price2num($this->localtax1_tx).",";
		$sql .= " localtax2_tx = ".price2num($this->localtax2_tx).",";
		$sql .= " qty = ".price2num($this->qty).",";
		$sql .= " remise_percent = ".price2num($this->remise_percent).",";
		$sql .= " remise = ".($this->remise ? price2num($this->remise) : "null").",";
		$sql .= " fk_remise_except = ".($this->fk_remise_except > 0 ? $this->fk_remise_except : "null").",";
		$sql .= " subprice = ".($this->subprice != '' ? $this->subprice : "null").",";
		$sql .= " price_ht = ".($this->price_ht != '' ? $this->price_ht : "null").",";
		$sql .= " total_ht = ".$this->total_ht.",";
		$sql .= " total_tva = ".$this->total_tva.",";
		$sql .= " total_localtax1 = ".$this->total_localtax1.",";
		$sql .= " total_localtax2 = ".$this->total_localtax2.",";
		$sql .= " total_ttc = ".$this->total_ttc.",";
		$sql .= " fk_product_fournisseur_price = ".(!empty($this->fk_fournprice) ? $this->fk_fournprice : "NULL").",";
		$sql .= " buy_price_ht = '".price2num($this->pa_ht)."',";
		$sql .= " info_bits = '".$this->db->escape($this->info_bits)."',";
		$sql .= " fk_user_author = ".($this->fk_user_author >= 0 ? $this->fk_user_author : "NULL").",";
		$sql .= " fk_user_ouverture = ".($this->fk_user_ouverture > 0 ? $this->fk_user_ouverture : "NULL").",";
		$sql .= " fk_user_cloture = ".($this->fk_user_cloture > 0 ? $this->fk_user_cloture : "NULL").",";
		$sql .= " commentaire = '".$this->db->escape($this->commentaire)."',";
		$sql .= " fk_unit = ".(!$this->fk_unit ? 'NULL' : $this->fk_unit).",";
		$sql .= " rang = ".(empty($this->rang) ? '0' : (int) $this->rang);
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = "Error ".$this->db->lasterror();
			$error++;
		}

		if (!$error) { // For avoid conflicts if trigger used
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		// If we change a planned date (start or end) of one contract line, sync dates for all other services too
		if (!$error && getDolGlobalString('CONTRACT_SYNC_PLANNED_DATE_OF_SERVICES')) {
			dol_syslog(get_class($this)."::update CONTRACT_SYNC_PLANNED_DATE_OF_SERVICES is on so we update date for all lines", LOG_DEBUG);

			if ($this->date_start != $this->oldcopy->date_start) {
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'contratdet SET';
				$sql .= " date_ouverture_prevue = ".($this->date_start != '' ? "'".$this->db->idate($this->date_start)."'" : "null");
				$sql .= " WHERE fk_contrat = ".((int) $this->fk_contrat);

				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = "Error ".$this->db->lasterror();
				}
			}
			if ($this->date_end != $this->oldcopy->date_end) {
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'contratdet SET';
				$sql .= " date_fin_validite = ".($this->date_end != '' ? "'".$this->db->idate($this->date_end)."'" : "null");
				$sql .= " WHERE fk_contrat = ".((int) $this->fk_contrat);

				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = "Error ".$this->db->lasterror();
				}
			}
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('LINECONTRACT_MODIFY', $user);
			if ($result < 0) {
				$error++;
				$this->db->rollback();
			}
			// End call triggers
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			$this->errors[] = $this->error;
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Update in database the fields total_xxx of lines
	 *	Used by migration process
	 *
	 *	@return		int		Return integer <0 if KO, >0 if OK
	 */
	public function update_total()
	{
		// phpcs:enable
		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET";
		$sql .= " total_ht=".price2num($this->total_ht, 'MT');
		$sql .= ",total_tva=".price2num($this->total_tva, 'MT');
		$sql .= ",total_localtax1=".price2num($this->total_localtax1, 'MT');
		$sql .= ",total_localtax2=".price2num($this->total_localtax2, 'MT');
		$sql .= ",total_ttc=".price2num($this->total_ttc, 'MT');
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::update_total", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -2;
		}
	}


	/**
	 * Inserts a contrat line into database
	 *
	 * @param int $notrigger Set to 1 if you don't want triggers to be fired
	 * @return int Return integer <0 if KO, >0 if OK
	 */
	public function insert($notrigger = 0)
	{
		global $user;

		$error = 0;

		// Insertion dans la base
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."contratdet";
		$sql .= " (fk_contrat, label, description, fk_product, qty, vat_src_code, tva_tx,";
		$sql .= " localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, remise_percent, subprice,";
		$sql .= " total_ht, total_tva, total_localtax1, total_localtax2, total_ttc,";
		$sql .= " info_bits,";
		$sql .= " rang,";
		$sql .= " price_ht, remise, fk_product_fournisseur_price, buy_price_ht";
		if ($this->date_start > 0) {
			$sql .= ",date_ouverture_prevue";
		}
		if ($this->date_end > 0) {
			$sql .= ",date_fin_validite";
		}
		$sql .= ") VALUES ($this->fk_contrat, '', '".$this->db->escape($this->description)."',";
		$sql .= ($this->fk_product > 0 ? $this->fk_product : "null").",";
		$sql .= " '".$this->db->escape($this->qty)."',";
		$sql .= " '".$this->db->escape($this->vat_src_code)."',";
		$sql .= " '".$this->db->escape($this->tva_tx)."',";
		$sql .= " '".$this->db->escape($this->localtax1_tx)."',";
		$sql .= " '".$this->db->escape($this->localtax2_tx)."',";
		$sql .= " '".$this->db->escape($this->localtax1_type)."',";
		$sql .= " '".$this->db->escape($this->localtax2_type)."',";
		$sql .= " ".price2num($this->remise_percent).",".price2num($this->subprice).",";
		$sql .= " ".price2num($this->total_ht).",".price2num($this->total_tva).",".price2num($this->total_localtax1).",".price2num($this->total_localtax2).",".price2num($this->total_ttc).",";
		$sql .= " '".$this->db->escape($this->info_bits)."',";
		$sql .= " ".(empty($this->rang) ? '0' : (int) $this->rang).",";
		$sql .= " ".price2num($this->price_ht).",".price2num($this->remise).",";
		if ($this->fk_fournprice > 0) {
			$sql .= ' '.((int) $this->fk_fournprice).',';
		} else {
			$sql .= ' null,';
		}
		if ($this->pa_ht > 0) {
			$sql .= ' '.((float) price2num($this->pa_ht));
		} else {
			$sql .= ' null';
		}
		if ($this->date_start > 0) {
			$sql .= ",'".$this->db->idate($this->date_start)."'";
		}
		if ($this->date_end > 0) {
			$sql .= ",'".$this->db->idate($this->date_end)."'";
		}
		$sql .= ")";

		dol_syslog(get_class($this)."::insert", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'contratdet');

			// Insert of extrafields
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$this->db->rollback();
					return -1;
				}
			}

			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINECONTRACT_INSERT', $user);
				if ($result < 0) {
					$this->db->rollback();
					return -1;
				}
				// End call triggers
			}

			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			$this->error = $this->db->error()." sql=".$sql;
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Activate a contract line
	 *
	 * @param   User 		$user 		Object User who activate contract
	 * @param  	int 		$date 		Date real activation
	 * @param  	int|string 	$date_end 	Date planned end. Use '-1' to keep it unchanged.
	 * @param   string 		$comment 	A comment typed by user
	 * @return 	int                    	Return integer <0 if KO, >0 if OK
	 */
	public function active_line($user, $date, $date_end = '', $comment = '')
	{
		// phpcs:enable
		$error = 0;

		$this->db->begin();

		$this->statut = ContratLigne::STATUS_OPEN;
		$this->date_start_real = $date;
		$this->date_end = $date_end;
		$this->fk_user_ouverture = $user->id;
		$this->date_end_real = null;
		$this->commentaire = $comment;

		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET statut = ".((int) $this->statut).",";
		$sql .= " date_ouverture = ".(dol_strlen($this->date_start_real) != 0 ? "'".$this->db->idate($this->date_start_real)."'" : "null").",";
		if ($date_end >= 0) {
			$sql .= " date_fin_validite = ".(dol_strlen($this->date_end) != 0 ? "'".$this->db->idate($this->date_end)."'" : "null").",";
		}
		$sql .= " fk_user_ouverture = ".((int) $this->fk_user_ouverture).",";
		$sql .= " date_cloture = null,";
		$sql .= " commentaire = '".$this->db->escape($comment)."'";
		$sql .= " WHERE rowid = ".((int) $this->id)." AND (statut = ".ContratLigne::STATUS_INITIAL." OR statut = ".ContratLigne::STATUS_CLOSED.")";

		dol_syslog(get_class($this)."::active_line", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			// Call trigger
			$result = $this->call_trigger('LINECONTRACT_ACTIVATE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers

			if (!$error) {
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

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Close a contract line
	 *
	 * @param    User 	$user 			Object User who close contract
	 * @param  	 int 	$date_end_real 	Date end
	 * @param    string $comment 		A comment typed by user
	 * @param    int	$notrigger		1=Does not execute triggers, 0=Execute triggers
	 * @return int                    	Return integer <0 if KO, >0 if OK
	 */
	public function close_line($user, $date_end_real, $comment = '', $notrigger = 0)
	{
		// phpcs:enable
		$this->date_cloture = $date_end_real;
		$this->date_end_real = $date_end_real;
		$this->user_closing_id = $user->id;
		$this->commentaire = $comment;

		$error = 0;

		// statut actif : 4

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."contratdet SET statut = ".((int) ContratLigne::STATUS_CLOSED).",";
		$sql .= " date_cloture = '".$this->db->idate($date_end_real)."',";
		$sql .= " fk_user_cloture = ".((int) $user->id).",";
		$sql .= " commentaire = '".$this->db->escape($comment)."'";
		$sql .= " WHERE rowid = ".((int) $this->id)." AND statut = ".((int) ContratLigne::STATUS_OPEN);

		$resql = $this->db->query($sql);
		if ($resql) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINECONTRACT_CLOSE', $user);
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
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}
}
