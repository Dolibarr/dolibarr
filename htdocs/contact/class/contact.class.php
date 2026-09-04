<?php
/* Copyright (C) 2002-2004  Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004       Benoit Mortier              <benoit.mortier@opensides.be>
 * Copyright (C) 2004-2013  Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2007       Franky Van Liedekerke       <franky.van.liedekerker@telenet.be>
 * Copyright (C) 2008       Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2013       Florian Henry		  	    <florian.henry@open-concept.pro>
 * Copyright (C) 2013       Alexandre Spangaro 	        <aspangaro@open-dsi.fr>
 * Copyright (C) 2013       Juanjo Menent	 	        <jmenent@2byte.es>
 * Copyright (C) 2015       Marcos García               <marcosgdf@gmail.com>
 * Copyright (C) 2019       Nicolas ZABOURI             <info@inovea-conseil.com>
 * Copyright (C) 2020       Open-Dsi                    <support@open-dsi.fr>
 * Copyright (C) 2024-2025  Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024-2026	MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/contact/class/contact.class.php
 *	\ingroup    societe
 *	\brief      File of contacts class
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonsocialnetworks.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonpeople.class.php';


/**
 *	Class to manage contact/addresses
 */
class Contact extends CommonObject
{
	use CommonSocialNetworks;
	use CommonPeople;

	/**
	 * @var string		Prefix to check for any trigger code of any business class to prevent bad value for trigger code.
	 * @see CommonTrigger::call_trigger()
	 */
	public $TRIGGER_PREFIX = 'CONTACT';

	/**
	 * @var string[] Properties copied from the merged contact when they are empty on the target one.
	 *               Only properties actually loaded by fetch() can be listed here: url, no_email,
	 *               fk_parent, ip and the geo columns are not, and no_email is deprecated in favour
	 *               of the llx_mailing_unsubscribe table. photo is excluded on purpose: the file is
	 *               moved once the transaction is committed and may be renamed on a name collision.
	 */
	public const MERGE_FIELDS_FILL_IF_EMPTY = array(
		'civility_code', 'lastname', 'firstname', 'name_alias', 'address', 'zip', 'town',
		'state_id', 'country_id', 'poste', 'phone_pro', 'phone_perso', 'phone_mobile', 'fax',
		'email', 'socialnetworks', 'birthday', 'default_lang', 'ref_ext',
		'fk_prospectlevel', 'stcomm_id', 'socid'
	);

	/**
	 * @var string[] Properties concatenated when merging two contacts.
	 */
	public const MERGE_FIELDS_CONCAT = array('note_public', 'note_private');

	/**
	 * @var int Maximum depth walked when looking for the ancestors of a contact, to avoid an
	 *          infinite loop should the parent hierarchy already contain a cycle.
	 */
	public const MERGE_MAX_PARENT_DEPTH = 100;

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'contact';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'socpeople';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'contact';

	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:>:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwritten by the Setup of Default Values if the field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
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
	 * array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-2,5>|string,alwayseditable?:int<0,1>,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,2>,disabled?:int<0,1>,arrayofkeyval?:array<int,string>,autofocusoncreate?:int<0,1>,comment?:string,copytoclipboard?:int<1,2>,validate?:int<0,1>}> Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -2, 'noteditable' => 1, 'notnull' => 1, 'index' => 1, 'position' => 1, 'comment' => 'Id', 'css' => 'left'),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => 3, 'notnull' => 1, 'position' => 30, 'index' => 1),
		'ref_ext' => array('type' => 'varchar(255)', 'label' => 'RefExt', 'enabled' => 1, 'visible' => 0, 'position' => 35),
		'civility' => array('type' => 'varchar(6)', 'label' => 'Civility', 'enabled' => 1, 'visible' => 3, 'position' => 40),
		'lastname' => array('type' => 'varchar(50)', 'label' => 'Lastname', 'enabled' => 1, 'visible' => 1, 'position' => 45, 'showoncombobox' => 1, 'searchall' => 1),
		'name_alias' => array('type' => 'varchar(255)', 'label' => 'Name alias', 'enabled' => 1, 'visible' => -1, 'position' => 46, 'searchall' => 1),
		'firstname' => array('type' => 'varchar(50)', 'label' => 'Firstname', 'enabled' => 1, 'visible' => 1, 'position' => 50, 'showoncombobox' => 1, 'searchall' => 1),
		'poste' => array('type' => 'varchar(80)', 'label' => 'PostOrFunction', 'enabled' => 1, 'visible' => -1, 'position' => 52),
		'address' => array('type' => 'varchar(255)', 'label' => 'Address', 'enabled' => 1, 'visible' => -1, 'position' => 55),
		'zip' => array('type' => 'varchar(25)', 'label' => 'Zip', 'enabled' => 1, 'visible' => -1, 'position' => 60),
		'town' => array('type' => 'varchar(50)', 'label' => 'Town', 'enabled' => 1, 'visible' => -1, 'position' => 65),
		'fk_departement' => array('type' => 'integer', 'label' => 'Fk departement', 'enabled' => 1, 'visible' => 3, 'position' => 70),
		'fk_pays' => array('type' => 'integer', 'label' => 'Fk pays', 'enabled' => 1, 'visible' => 3, 'position' => 75),
		'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'ThirdParty', 'enabled' => 1, 'visible' => 1, 'position' => 77, 'searchall' => 1),
		'birthday' => array('type' => 'date', 'label' => 'Birthday', 'enabled' => 1, 'visible' => -1, 'position' => 80),
		'phone' => array('type' => 'varchar(30)', 'label' => 'Phone', 'enabled' => 1, 'visible' => 1, 'position' => 90, 'searchall' => 1),
		'phone_perso' => array('type' => 'varchar(30)', 'label' => 'PhonePerso', 'enabled' => 1, 'visible' => -1, 'position' => 95, 'searchall' => 1),
		'phone_mobile' => array('type' => 'varchar(30)', 'label' => 'PhoneMobile', 'enabled' => 1, 'visible' => 1, 'position' => 100, 'searchall' => 1),
		'fax' => array('type' => 'varchar(30)', 'label' => 'Fax', 'enabled' => 1, 'visible' => -1, 'position' => 105, 'searchall' => 1),
		'email' => array('type' => 'varchar(255)', 'label' => 'Email', 'enabled' => 1, 'visible' => 1, 'position' => 110, 'searchall' => 1),
		'socialnetworks' => array('type' => 'text', 'label' => 'SocialNetworks', 'enabled' => 1, 'visible' => 3, 'position' => 115),
		'photo' => array('type' => 'varchar(255)', 'label' => 'Photo', 'enabled' => 1, 'visible' => 3, 'position' => 170),
		'priv' => array('type' => 'smallint(6)', 'label' => 'ContactVisibility', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 175),
		'fk_stcommcontact' => array('type' => 'integer', 'label' => 'ProspectStatus', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 220),
		'fk_prospectcontactlevel' => array('type' => 'varchar(12)', 'label' => 'ProspectLevel', 'enabled' => 1, 'visible' => -1, 'position' => 255),
		//no more used. Replace by a scan of email into mailing_unsubscribe. 'no_email' =>array('type'=>'smallint(6)', 'label'=>'No_Email', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>180),
		'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => 1, 'visible' => 0, 'position' => 195, 'searchall' => 1),
		'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => 1, 'visible' => 0, 'position' => 200, 'searchall' => 1),
		'default_lang' => array('type' => 'varchar(6)', 'label' => 'Default lang', 'enabled' => 1, 'visible' => 3, 'position' => 205),
		'canvas' => array('type' => 'varchar(32)', 'label' => 'Canvas', 'enabled' => 1, 'visible' => 3, 'position' => 210),
		'ip' => array('type' => 'ip', 'label' => 'IPAddress', 'enabled' => '1', 'position' => 700, 'notnull' => 0, 'visible' => '-2', 'comment' => 'ip used to create record (for public submission page)'),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -1, 'position' => 300),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 305),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'visible' => 3, 'position' => 310),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'visible' => 3, 'position' => 315),
		'statut' => array('type' => 'tinyint(4)', 'label' => 'Status', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 500),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -1, 'position' => 1000),
	);

	/**
	 * @var string
	 */
	public $civility_id; // In fact we store civility_code
	/**
	 * @var string
	 */
	public $civility_code;
	/**
	 * @var string
	 */
	public $civility;

	/**
	 * @var string gender
	 */
	public $gender;

	/**
	 * @var int birthday_alert
	 */
	public $birthday_alert;

	/**
	 * @var string The civilite code, not an integer
	 * @deprecated Use $civility_code
	 * @see $civility_code
	 */
	public $civilite;

	/**
	 * @var string fullname
	 */
	public $fullname;

	/**
	 * @var string Name alias
	 */
	public $name_alias;

	/**
	 * @var ?string Address
	 */
	public $address;

	/**
	 * @var ?string zip code
	 */
	public $zip;

	/**
	 * @var ?string Town
	 */
	public $town;

	/**
	 * @var int  Id of department
	 */
	public $state_id;

	/**
	 * @var string  Code of department
	 */
	public $state_code;

	/**
	 * @var string  Label of department
	 */
	public $state;

	/**
	 * @var string  Job Position
	 */
	public $poste;

	/**
	 * @var int Thirdparty ID
	 */
	public $socid;		// both socid and fk_soc are used
	/**
	 * @var int
	 */
	public $fk_soc;		// both socid and fk_soc are used

	/**
	 * @var string Thirdparty name
	 */
	public $socname;

	/**
	 * @var int  Status 0=inactive, 1=active
	 * @deprecated Use $status
	 */
	public $statut;

	/**
	 * @var string
	 */
	public $code;

	/**
	 * Email
	 * @var ?string
	 */
	public $email;

	/**
	 * Email
	 * @var ?string
	 * @deprecated Use $email
	 * @see $email
	 */
	public $mail;

	/**
	 * URL
	 * @var ?string
	 */
	public $url;

	/**
	 * Unsubscribe all : 1 = contact has globally unsubscribed of all mass emailing
	 * @var int
	 * @deprecated Has been replaced by a search into llx_mailing_unsubscribe
	 */
	public $no_email;

	/**
	 * Array of social-networks
	 * @var array<string,string>
	 */
	public $socialnetworks;

	/**
	 * @var string filename for photo
	 */
	public $photo;

	/**
	 * @var string phone pro (professional/business)
	 */
	public $phone_pro;

	/**
	 * @var string phone perso (personal/private)
	 */
	public $phone_perso;

	/**
	 * @var string phone mobile
	 */
	public $phone_mobile;

	/**
	 * @var string fax
	 */
	public $fax;

	/**
	 * Private or public
	 * @var int
	 */
	public $priv;

	/**
	 * @var int|string Date
	 */
	public $birthday;

	/**
	 * @var string language for contact communication  -- only with multilanguage enabled
	 */
	public $default_lang;

	/**
	 * @var int Number of invoices for which he is contact
	 */
	public $ref_facturation;

	/**
	 * @var int  Number of contracts for which he is contact
	 */
	public $ref_contrat;

	/**
	 * @var int Number of orders for which he is contact
	 */
	public $ref_commande;

	/**
	 * @var int Number of proposals for which he is contact
	 */
	public $ref_propal;

	/**
	 * @var int user ID
	 */
	public $user_id;

	/**
	 * @var string user login
	 */
	public $user_login;

	/**
	 * @var string IP address
	 */
	public $ip;
	// END MODULEBUILDER PROPERTIES

	/**
	 * @var null|array<int,array{id:int,socid:int,element:string,source:string,code:string,label:string}> roles, null until fetched or set
	 */
	public $roles;

	/**
	 * @var array<int,array{id:int,code:string,label:string,picto:string}>
	 */
	public $cacheprospectstatus = array();

	/**
	 * @var string	Prospect level. ie: 'PL_LOW', 'PL...'
	 */
	public $fk_prospectlevel;

	/**
	 * @var null|int Is null until fetched or set
	 */
	public $stcomm_id;

	/**
	 * @var string
	 */
	public $statut_commercial;

	/**
	 * @var string picto
	 */
	public $stcomm_picto;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->statut = 1; // By default, status is enabled
		$this->status = 1; // By default, status is enabled
		$this->ismultientitymanaged = 1;
		$this->isextrafieldmanaged = 1;

		$this->fields['ref_ext']['visible'] = getDolGlobalInt('MAIN_LIST_SHOW_REF_EXT');

		if (!isModEnabled('mailing')) {
			$this->fields['no_email']['enabled'] = 0;
		}
		// typical ['s.nom'] is used for third-parties
		if (!getDolGlobalString('SOCIETE_DISABLE_CONTACTS')) {
			$this->fields['fk_soc']['enabled'] = 0;
			$this->fields['fk_soc']['searchall'] = 0;
		}

		// If THIRDPARTY_ENABLE_PROSPECTION_ON_ALTERNATIVE_ADRESSES not set, there is no prospect level on contact level, only on thirdparty
		if (getDolGlobalString('SOCIETE_DISABLE_PROSPECTS') || !getDolGlobalString('THIRDPARTY_ENABLE_PROSPECTION_ON_ALTERNATIVE_ADRESSES')) {	// Default behaviour
			$this->fields['fk_stcommcontact']['enabled'] = 0;
			$this->fields['fk_prospectcontactlevel']['enabled'] = 0;
		}

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		/*if (is_object($langs))
		{
			foreach($this->fields as $key => $val)
			{
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval']))
				{
					foreach($val['arrayofkeyval'] as $key2 => $val2)
					{
						$this->fields[$key]['arrayofkeyval'][$key2]=$langs->trans($val2);
					}
				}
			}
		}*/
	}

	/**
	 *  Load indicators into this->nb for board
	 *
	 *  @return     int         Return integer <0 if KO, >0 if OK
	 */
	public function loadStateBoard()
	{
		global $user, $hookmanager;

		$this->nb = array();
		$clause = "WHERE";

		$sql = "SELECT count(sp.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as sp";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ", ".MAIN_DB_PREFIX."societe as s";
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql .= " WHERE sp.fk_soc = s.rowid AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
			$clause = "AND";
		}
		$sql .= " ".$clause." sp.entity IN (".getEntity($this->element).")";
		$sql .= " AND (sp.priv='0' OR (sp.priv='1' AND sp.fk_user_creat = ".((int) $user->id)."))";
		if ($user->socid > 0) {
			$sql .= " AND sp.fk_soc = ".((int) $user->socid);
		}
		// Add where from hooks
		if (is_object($hookmanager)) {
			$parameters = array();
			$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $this); // Note that $action and $object may have been modified by hook
			$sql .= $hookmanager->resPrint;
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["contacts"] = $obj->nb;
			}
			$this->db->free($resql);
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Add a contact into database
	 *
	 *  @param      User	$user           Object user that create
	 *  @param      int     $notrigger	    1=Does not execute triggers, 0= execute triggers
	 *  @return     int      			    Return integer <0 if KO, >0 if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf;

		$error = 0;
		$now = dol_now();

		if (empty($this->date_creation)) {
			$this->date_creation = $now;
		}

		$this->db->begin();

		// Clean parameters
		$this->name_alias = trim($this->name_alias);
		$this->lastname = $this->lastname ? trim($this->lastname) : trim($this->name);
		$this->firstname = trim($this->firstname);
		$this->setUpperOrLowerCase();
		if (empty($this->socid)) {
			$this->socid = 0;
		}
		if (empty($this->priv)) {
			$this->priv = 0;
		}
		if (!empty($this->statut) && empty($this->status)) {
			$this->status = 1;
		}
		if (empty($this->status)) {
			$this->status = 0; // This is to convert '' into '0' to avoid bad sql request
			$this->statut = 0; // This is to convert '' into '0' to avoid bad sql request
		}

		// setEntity will set entity with the right value if empty or change it for the right value if multicompany module is active
		$this->entity = setEntity($this);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."socpeople (";
		$sql .= " datec";
		$sql .= ", fk_soc";
		$sql .= ", name_alias";
		$sql .= ", lastname";
		$sql .= ", firstname";
		$sql .= ", fk_user_creat";
		$sql .= ", priv";
		$sql .= ", fk_stcommcontact";
		$sql .= ", statut";
		$sql .= ", canvas";
		$sql .= ", entity";
		$sql .= ", ref_ext";
		$sql .= ", import_key";
		$sql .= ", ip";
		$sql .= ") VALUES (";
		$sql .= "'".$this->db->idate($now)."',";
		if ($this->socid > 0) {
			$sql .= " ".((int) $this->socid).",";
		} else {
			$sql .= "null,";
		}
		$sql .= "'".$this->db->escape($this->name_alias)."',";
		$sql .= "'".$this->db->escape($this->lastname)."',";
		$sql .= "'".$this->db->escape($this->firstname)."',";
		$sql .= " ".($user->id > 0 ? ((int) $user->id) : "null").",";
		$sql .= " ".((int) $this->priv).",";
		$sql .= " 0,";
		$sql .= " ".((int) $this->status).",";
		$sql .= " ".(!empty($this->canvas) ? "'".$this->db->escape($this->canvas)."'" : "null").",";
		$sql .= " ".((int) $this->entity).",";
		$sql .= "'".$this->db->escape($this->ref_ext)."',";
		$sql .= " ".(!empty($this->import_key) ? "'".$this->db->escape($this->import_key)."'" : "null").",";
		$sql .= " ".(!empty($this->ip) ? "'".$this->db->escape($this->ip)."'" : "null");
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."socpeople");

			$result = $this->update($this->id, $user, 1, 'add'); // This include updateRoles(), ...
			if ($result < 0) {
				$error++;
				$this->error = $this->db->lasterror();
			}

			if (!$error) {
				$result = $this->update_perso($this->id, $user, 1); // TODO Remove function update_perso, should be same than update
				if ($result < 0) {
					$error++;
					$this->error = $this->db->lasterror();
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('CONTACT_CREATE', $user);
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
				dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
				return -2;
			}
		} else {
			$this->error = $this->db->lasterror();

			$this->db->rollback();
			dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *      Update information into database
	 *
	 *      @param      int		$id          	Id of contact/address to update
	 *      @param      User	$user        	Object user making change
	 *      @param      int		$notrigger	    0=no, 1=yes
	 *      @param		string	$action			Current action for hookmanager
	 *      @param		int		$nosyncuser		No sync linked user (external users and contacts are linked)
	 *      @return     int      			   	Return integer <0 if KO, >0 if OK
	 */
	public function update($id, $user = null, $notrigger = 0, $action = 'update', $nosyncuser = 0)
	{
		global $conf;

		if (empty($this->country_id) && !empty($this->country_code)) {
			$country_id = getCountry($this->country_code, '3');
			$this->country_id = is_int($country_id) ? $country_id : 0;
		}

		$error = 0;

		$this->id = $id;

		$this->entity = ((isset($this->entity) && is_numeric($this->entity)) ? $this->entity : $conf->entity);

		// Clean parameters
		$this->ref_ext = (empty($this->ref_ext) ? '' : trim($this->ref_ext));
		$this->name_alias = trim($this->name_alias);
		$this->lastname = trim($this->lastname) ? trim($this->lastname) : trim($this->lastname);
		$this->firstname = trim($this->firstname);
		$this->email = trim($this->email ?? '');
		$this->phone_pro = trim($this->phone_pro);
		$this->phone_perso = trim($this->phone_perso);
		$this->phone_mobile = trim($this->phone_mobile);
		$this->photo = trim($this->photo);
		$this->fax = trim($this->fax);
		$this->zip = (empty($this->zip) ? '' : trim($this->zip));
		$this->town = (empty($this->town) ? '' : trim($this->town));
		$this->country_id = (empty($this->country_id) || $this->country_id < 0) ? 0 : $this->country_id;
		if (!empty($this->statut) && empty($this->status)) {
			$this->status = 1;
		}
		if (empty($this->status)) {
			$this->status = 0;
			$this->statut = 0;
		}
		if (empty($this->civility_code) && !is_numeric($this->civility_id)) {
			$this->civility_code = $this->civility_id; // For backward compatibility
		}
		$this->setUpperOrLowerCase();

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET";
		if ($this->socid > 0) {
			$sql .= " fk_soc = ".((int) $this->socid).",";
		} elseif ($this->socid == -1) {
			$sql .= " fk_soc = NULL,";
		}
		$sql .= " civility='".$this->db->escape($this->civility_code)."'";
		$sql .= ", name_alias='".$this->db->escape($this->name_alias)."'";
		$sql .= ", lastname='".$this->db->escape($this->lastname)."'";
		$sql .= ", firstname='".$this->db->escape($this->firstname)."'";
		$sql .= ", address='".$this->db->escape((string) $this->address)."'";
		$sql .= ", zip='".$this->db->escape($this->zip)."'";
		$sql .= ", town='".$this->db->escape($this->town)."'";
		$sql .= ", ref_ext = ".(!empty($this->ref_ext) ? "'".$this->db->escape($this->ref_ext)."'" : "NULL");
		$sql .= ", fk_pays=".($this->country_id > 0 ? ((int) $this->country_id) : 'NULL');
		$sql .= ", fk_departement=".($this->state_id > 0 ? ((int) $this->state_id) : 'NULL');
		$sql .= ", poste='".$this->db->escape($this->poste)."'";
		$sql .= ", fax='".$this->db->escape($this->fax)."'";
		$sql .= ", email='".$this->db->escape($this->email)."'";
		$sql .= ", socialnetworks = '".$this->db->escape(json_encode($this->socialnetworks))."'";
		$sql .= ", photo='".$this->db->escape($this->photo)."'";
		$sql .= ", birthday=".($this->birthday ? "'".$this->db->idate($this->birthday)."'" : "null");
		$sql .= ", note_private = ".(isset($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "NULL");
		$sql .= ", note_public = ".(isset($this->note_public) ? "'".$this->db->escape($this->note_public)."'" : "NULL");
		$sql .= ", phone = ".(isset($this->phone_pro) ? "'".$this->db->escape($this->phone_pro)."'" : "NULL");
		$sql .= ", phone_perso = ".(isset($this->phone_perso) ? "'".$this->db->escape($this->phone_perso)."'" : "NULL");
		$sql .= ", phone_mobile = ".(isset($this->phone_mobile) ? "'".$this->db->escape($this->phone_mobile)."'" : "NULL");
		$sql .= ", priv = ".((int) $this->priv);
		$sql .= ", fk_prospectlevel = '".$this->db->escape($this->fk_prospectlevel)."'";
		if (isset($this->stcomm_id)) {
			$sql .= ", fk_stcommcontact = ".($this->stcomm_id > 0 || $this->stcomm_id == -1 ? ((int) $this->stcomm_id) : "0");
		}
		$sql .= ", statut = ".((int) $this->status);
		$sql .= ", fk_user_modif=".($user->id > 0 ? "'".$this->db->escape((string) $user->id)."'" : "NULL");
		$sql .= ", default_lang=".($this->default_lang ? "'".$this->db->escape($this->default_lang)."'" : "NULL");
		$sql .= ", entity = ".((int) $this->entity);
		$sql .= " WHERE rowid = ".((int) $id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			unset($this->country_code);
			unset($this->country);
			unset($this->state_code);
			unset($this->state);

			$action = 'update';

			// Actions on extra fields
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}

			if (!$error) {
				$result = $this->updateRoles();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && $this->user_id > 0) {
				// If contact is linked to a user
				$tmpobj = new User($this->db);
				$tmpobj->fetch($this->user_id);
				$usermustbemodified = 0;
				if ($tmpobj->office_phone != $this->phone_pro) {
					$tmpobj->office_phone = $this->phone_pro;
					$usermustbemodified++;
				}
				if ($tmpobj->office_fax != $this->fax) {
					$tmpobj->office_fax = $this->fax;
					$usermustbemodified++;
				}
				if ($tmpobj->address != $this->address) {
					$tmpobj->address = $this->address;
					$usermustbemodified++;
				}
				if ($tmpobj->town != $this->town) {
					$tmpobj->town = $this->town;
					$usermustbemodified++;
				}
				if ($tmpobj->zip != $this->zip) {
					$tmpobj->zip = $this->zip;
					$usermustbemodified++;
				}
				if ($tmpobj->zip != $this->zip) {
					$tmpobj->state_id = $this->state_id;
					$usermustbemodified++;
				}
				if ($tmpobj->country_id != $this->country_id) {
					$tmpobj->country_id = $this->country_id;
					$usermustbemodified++;
				}
				if ($tmpobj->email != $this->email) {
					$tmpobj->email = $this->email;
					$usermustbemodified++;
				}
				if (!empty(array_diff($tmpobj->socialnetworks, $this->socialnetworks))) {
					$tmpobj->socialnetworks = $this->socialnetworks;
					$usermustbemodified++;
				}
				if ($usermustbemodified) {
					$result = $tmpobj->update($user, 0, 1, 1, 1);
					if ($result < 0) {
						$error++;
					}
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('CONTACT_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				dol_syslog(get_class($this)."::update Error ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -$error;
			}
		} else {
			$this->error = $this->db->lasterror().' sql='.$sql;
			$this->db->rollback();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *	Return DN string complete in the LDAP directory for the object
	 *
	 *	@param	array<string,mixed>	$info	Info array loaded by _load_ldap_info
	 *	@param	int<0,2>			$mode	0=Return full DN (uid=qqq,ou=xxx,dc=aaa,dc=bbb)
	 *										1=Return DN without key inside (ou=xxx,dc=aaa,dc=bbb)
	 *										2=Return key only (uid=qqq)
	 *	@return	string						DN
	 */
	public function _load_ldap_dn($info, $mode = 0)
	{
		// phpcs:enable
		global $conf;
		$dn = '';
		if ($mode == 0) {
			$dn = getDolGlobalString('LDAP_KEY_CONTACTS') . "=".$info[getDolGlobalString('LDAP_KEY_CONTACTS')]."," . getDolGlobalString('LDAP_CONTACT_DN');
		} elseif ($mode == 1) {
			$dn = getDolGlobalString('LDAP_CONTACT_DN');
		} elseif ($mode == 2) {
			$dn = getDolGlobalString('LDAP_KEY_CONTACTS') . "=".$info[getDolGlobalString('LDAP_KEY_CONTACTS')];
		}
		return $dn;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *	Initialize info table (LDAP attributes table)
	 *
	 *	@return		array<string,mixed>		Attributes info table
	 */
	public function _load_ldap_info()
	{
		// phpcs:enable
		global $conf, $langs;

		$info = array();

		// Object classes
		$info["objectclass"] = explode(',', getDolGlobalString('LDAP_CONTACT_OBJECT_CLASS'));

		$this->fullname = $this->getFullName($langs);

		// Fields
		if ($this->fullname && getDolGlobalString('LDAP_CONTACT_FIELD_FULLNAME')) {
			$info[getDolGlobalString('LDAP_CONTACT_FIELD_FULLNAME')] = $this->fullname;
		}
		if ($this->lastname && getDolGlobalString('LDAP_CONTACT_FIELD_NAME')) {
			$info[getDolGlobalString('LDAP_CONTACT_FIELD_NAME')] = $this->lastname;
		}
		if ($this->firstname && getDolGlobalString('LDAP_CONTACT_FIELD_FIRSTNAME')) {
			$info[getDolGlobalString('LDAP_CONTACT_FIELD_FIRSTNAME')] = $this->firstname;
		}

		if ($this->poste) {
			$info["title"] = $this->poste;
		}
		if ($this->socid > 0) {
			$soc = new Societe($this->db);
			$soc->fetch($this->socid);

			$info[getDolGlobalString('LDAP_CONTACT_FIELD_COMPANY')] = $soc->name;
			if ($soc->client == 1) {
				$info["businessCategory"] = "Customers";
			}
			if ($soc->client == 2) {
				$info["businessCategory"] = "Prospects";
			}
			if ($soc->fournisseur == 1) {
				$info["businessCategory"] = "Suppliers";
			}
		}
		if ($this->address && getDolGlobalString('LDAP_CONTACT_FIELD_ADDRESS')) {
			$info[getDolGlobalString('LDAP_CONTACT_FIELD_ADDRESS')] = $this->address;
		}
		if ($this->zip && getDolGlobalString('LDAP_CONTACT_FIELD_ZIP')) {
			$info[getDolGlobalString('LDAP_CONTACT_FIELD_ZIP')] = $this->zip;
		}
		if ($this->town && getDolGlobalString('LDAP_CONTACT_FIELD_TOWN')) {
			$info[getDolGlobalString('LDAP_CONTACT_FIELD_TOWN')] = $this->town;
		}
		if ($this->country_code && getDolGlobalString('LDAP_CONTACT_FIELD_COUNTRY')) {
			$info[getDolGlobalString('LDAP_CONTACT_FIELD_COUNTRY')] = $this->country_code;
		}
		if ($this->phone_pro && getDolGlobalString('LDAP_CONTACT_FIELD_PHONE')) {
			$info[getDolGlobalString('LDAP_CONTACT_FIELD_PHONE')] = $this->phone_pro;
		}
		if ($this->phone_perso && getDolGlobalString('LDAP_CONTACT_FIELD_HOMEPHONE')) {
			$info[getDolGlobalString('LDAP_CONTACT_FIELD_HOMEPHONE')] = $this->phone_perso;
		}
		if ($this->phone_mobile && getDolGlobalString('LDAP_CONTACT_FIELD_MOBILE')) {
			$info[getDolGlobalString('LDAP_CONTACT_FIELD_MOBILE')] = $this->phone_mobile;
		}
		if ($this->fax && getDolGlobalString('LDAP_CONTACT_FIELD_FAX')) {
			$info[getDolGlobalString('LDAP_CONTACT_FIELD_FAX')] = $this->fax;
		}
		if ($this->note_private && getDolGlobalString('LDAP_CONTACT_FIELD_DESCRIPTION')) {
			$info[getDolGlobalString('LDAP_CONTACT_FIELD_DESCRIPTION')] = dol_string_nohtmltag($this->note_private, 2);
		}
		if ($this->email && getDolGlobalString('LDAP_CONTACT_FIELD_MAIL')) {
			$info[getDolGlobalString('LDAP_CONTACT_FIELD_MAIL')] = $this->email;
		}

		if (getDolGlobalString('LDAP_SERVER_TYPE') == 'egroupware') {
			$info["objectclass"][4] = "phpgwContact"; // compatibilite egroupware

			$info['uidnumber'] = $this->id;

			$info['phpgwTz'] = 0;
			$info['phpgwMailType'] = 'INTERNET';
			$info['phpgwMailHomeType'] = 'INTERNET';

			$info["phpgwContactTypeId"] = 'n';
			$info["phpgwContactCatId"] = 0;
			$info["phpgwContactAccess"] = "public";

			$info["phpgwContactOwner"] = 1;

			if ($this->email) {
				$info["rfc822Mailbox"] = $this->email;
			}
			if ($this->phone_mobile) {
				$info["phpgwCellTelephoneNumber"] = $this->phone_mobile;
			}
		}

		return $info;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Update field alert birthday
	 *
	 *  @param      int			$id         Id of contact
	 *  @param      ?User		$user		User asking to change alert or birthday
	 *  @param      int<0,1>    $notrigger	0=no, 1=yes
	 *  @return     int         			Return integer <0 if KO, >=0 if OK
	 */
	public function update_perso($id, $user = null, $notrigger = 0)
	{
		// phpcs:enable
		$error = 0;
		$result = false;

		$this->db->begin();

		// Update the contact
		$sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET";
		$sql .= " birthday = ".($this->birthday ? "'".$this->db->idate($this->birthday)."'" : "null");
		$sql .= ", photo = ".($this->photo ? "'".$this->db->escape($this->photo)."'" : "null");
		if ($user) {
			$sql .= ", fk_user_modif = ".((int) $user->id);
		}
		$sql .= " WHERE rowid = ".((int) $id);

		dol_syslog(get_class($this)."::update_perso this->birthday=".$this->birthday." -", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->error = $this->db->lasterror();
		}

		if ($user) {
			// Update birthday alert
			if (!empty($this->birthday_alert)) {
				//check existing
				$sql_check = "SELECT rowid FROM " . MAIN_DB_PREFIX . "user_alert WHERE type = 1 AND fk_contact = " . ((int) $id) . " AND fk_user = " . ((int) $user->id);
				$result_check = $this->db->query($sql_check);
				if (!$result_check || ($this->db->num_rows($result_check) < 1)) {
					//insert
					$sql = "INSERT INTO " . MAIN_DB_PREFIX . "user_alert(type, fk_contact, fk_user) ";
					$sql .= "VALUES (1," . ((int) $id) . "," . ((int) $user->id) . ")";
					$result = $this->db->query($sql);
					if (!$result) {
						$error++;
						$this->error = $this->db->lasterror();
					}
				} else {
					$result = true;
				}
			} else {
				$sql = "DELETE FROM " . MAIN_DB_PREFIX . "user_alert ";
				$sql .= "WHERE type=1 AND fk_contact=" . ((int) $id) . " AND fk_user=" . ((int) $user->id);
				$result = $this->db->query($sql);
				if (!$result) {
					$error++;
					$this->error = $this->db->lasterror();
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('CONTACT_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			dol_syslog(get_class($this)."::update Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -$error;
		}
	}


	/**
	 *  Load object contact.
	 *
	 *  @param      int		$id         	Id of contact
	 *  @param      ?User	$user       	Load also alerts of this user (subscribing to alerts) that want alerts about this contact
	 *  @param      string  $ref_ext    	External reference, not given by Dolibarr
	 *  @param		string	$email			Email
	 *  @param		int		$loadalsoroles	Load also roles. Try to always use 0 here and load roles with a separate call of fetchRoles().
	 *  @param		int		$socid			Filter on thirdparty id
	 *  @return     int     		    	>0 if OK, <0 if KO or if two records found for same ref or idprof, 0 if not found.
	 */
	public function fetch($id, $user = null, $ref_ext = '', $email = '', $loadalsoroles = 0, $socid = 0)
	{
		global $langs;

		dol_syslog(get_class($this)."::fetch id=".$id." ref_ext=".$ref_ext." email=".$email, LOG_DEBUG);

		if (empty($id) && empty($ref_ext) && empty($email)) {
			$this->error = 'BadParameter';
			return -1;
		}

		$langs->loadLangs(array("dict", "companies"));

		$sql = "SELECT c.rowid, c.entity, c.fk_soc, c.ref_ext, c.civility as civility_code, c.name_alias, c.lastname, c.firstname,";
		$sql .= " c.address, c.statut as status, c.zip, c.town,";
		$sql .= " c.fk_pays as country_id,";
		$sql .= " c.fk_departement as state_id,";
		$sql .= " c.birthday,";
		$sql .= " c.poste, c.phone, c.phone_perso, c.phone_mobile, c.fax, c.email,";
		$sql .= " c.socialnetworks,";
		$sql .= " c.photo,";
		$sql .= " c.priv, c.note_private, c.note_public, c.default_lang, c.canvas,";
		$sql .= " c.fk_prospectlevel, c.fk_stcommcontact, st.libelle as stcomm, st.picto as stcomm_picto,";
		$sql .= " c.import_key,";
		$sql .= " c.datec as date_creation, GREATEST(c.tms, cef.tms) as date_modification, c.fk_user_creat, c.fk_user_modif,";
		$sql .= " co.label as country, co.code as country_code,";
		$sql .= " d.nom as state, d.code_departement as state_code,";
		$sql .= " u.rowid as user_id, u.login as user_login,";
		$sql .= " s.nom as socname, s.address as socaddress, s.zip as soccp, s.town as soccity, s.default_lang as socdefault_lang";
		$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople_extrafields as cef ON cef.fk_object=c.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as co ON c.fk_pays = co.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d ON c.fk_departement = d.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON c.rowid = u.fk_socpeople";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON c.fk_soc = s.rowid";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_stcommcontact as st ON c.fk_stcommcontact = st.id';
		if ($id) {
			$sql .= " WHERE c.rowid = ".((int) $id);
		} else {
			$sql .= " WHERE c.entity IN (".getEntity($this->element).")";
			if ($ref_ext) {
				$sql .= " AND c.ref_ext = '".$this->db->escape($ref_ext)."'";
			}
			if ($email) {
				$sql .= " AND c.email = '".$this->db->escape($email)."'";
			}
			if ($socid) {
				$sql .= " AND c.fk_soc = ".((int) $socid);
			}
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num > 1) {
				$this->error = 'Fetch found several records. Rename one of contact to avoid duplicate.';
				dol_syslog($this->error, LOG_ERR);

				return 2;
			} elseif ($num) {   // $num = 1
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->entity = $obj->entity;
				$this->ref = $obj->rowid;
				$this->ref_ext = $obj->ref_ext;

				$this->civility_code = $obj->civility_code;
				$this->civility = $obj->civility_code ? ($langs->trans("Civility".$obj->civility_code) != "Civility".$obj->civility_code ? $langs->trans("Civility".$obj->civility_code) : $obj->civility_code) : '';

				$this->name_alias	= $obj->name_alias;
				$this->lastname		= $obj->lastname;
				$this->firstname	= $obj->firstname;
				$this->address		= $obj->address;
				$this->zip			= $obj->zip;
				$this->town			= $obj->town;

				$this->date_creation = $this->db->jdate($obj->date_creation);
				$this->date_modification = $this->db->jdate($obj->date_modification);
				$this->user_creation_id = $obj->fk_user_creat;
				$this->user_modification_id = $obj->fk_user_modif;

				$this->state_id		= $obj->state_id;
				$this->state_code	= $obj->state_code;
				$this->state		= $obj->state;

				$this->country_id	= $obj->country_id;
				$this->country_code	= $obj->country_id ? $obj->country_code : '';
				$this->country		= $obj->country_id ? ($langs->trans('Country'.$obj->country_code) != 'Country'.$obj->country_code ? $langs->transnoentities('Country'.$obj->country_code) : $obj->country) : '';

				$this->fk_soc		= $obj->fk_soc;		// Both fk_soc and socid are used
				$this->socid		= $obj->fk_soc;		// Both fk_soc and socid are used
				$this->socname		= $obj->socname;
				$this->poste		= $obj->poste;
				$this->status		= $obj->status;
				$this->statut		= $obj->status; // deprecated

				$this->fk_prospectlevel = $obj->fk_prospectlevel;

				$transcode = $langs->trans('StatusProspect'.$obj->fk_stcommcontact);
				$label_sale_status = ($transcode != 'StatusProspect'.$obj->fk_stcommcontact ? $transcode : $obj->stcomm);
				$this->stcomm_id = $obj->fk_stcommcontact; // id statut commercial
				$this->statut_commercial = $label_sale_status; // libelle statut commercial
				$this->stcomm_picto = $obj->stcomm_picto; // Picto statut commercial

				$this->phone_pro = trim($obj->phone);
				$this->fax = trim($obj->fax);
				$this->phone_perso = trim($obj->phone_perso);
				$this->phone_mobile = trim($obj->phone_mobile);

				$this->email			= $obj->email;
				$this->socialnetworks	= ($obj->socialnetworks ? (array) json_decode($obj->socialnetworks, true) : array());
				$this->photo			= $obj->photo;
				$this->priv				= $obj->priv;
				$this->mail				= $obj->email;

				$this->birthday		= $this->db->jdate($obj->birthday);
				$this->note			= $obj->note_private; // deprecated
				$this->note_private	= $obj->note_private;
				$this->note_public	= $obj->note_public;
				$this->default_lang	= $obj->default_lang;
				$this->user_id		= $obj->user_id;
				$this->user_login	= $obj->user_login;
				$this->canvas		= $obj->canvas;

				$this->import_key = $obj->import_key;

				// Define gender according to civility
				$this->setGenderFromCivility();

				// Search Dolibarr user linked to this contact
				$sql = "SELECT u.rowid ";
				$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
				$sql .= " WHERE u.fk_socpeople = ".((int) $this->id);

				$resql = $this->db->query($sql);
				if ($resql) {
					if ($this->db->num_rows($resql)) {
						$uobj = $this->db->fetch_object($resql);

						$this->user_id = $uobj->rowid;
					}
					$this->db->free($resql);
				} else {
					$this->error = $this->db->error();
					return -1;
				}

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				// Load also alerts of this user
				if ($user) {
					$sql = "SELECT fk_user";
					$sql .= " FROM ".MAIN_DB_PREFIX."user_alert";
					$sql .= " WHERE fk_user = ".((int) $user->id)." AND fk_contact = ".((int) $id);

					$resql = $this->db->query($sql);
					if ($resql) {
						if ($this->db->num_rows($resql)) {
							$obj = $this->db->fetch_object($resql);

							$this->birthday_alert = 1;
						}
						$this->db->free($resql);
					} else {
						$this->error = $this->db->error();
						return -1;
					}
				}

				// Load also roles of this address
				if ($loadalsoroles) {
					$resultRole = $this->fetchRoles();
					if ($resultRole < 0) {
						return $resultRole;
					}
				}

				return $this->id;
			} else {
				$langs->load('errors');
				$this->error = $langs->trans("ErrorRecordNotFound");
				return 0;
			}
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *    Search the contact that match the most the provided parameters.
	 *    Searching rules try to find the existing contact.
	 *
	 *  @param      int		$id         	Id of contact
	 *  @param      string  $lastname    	Lastname (TODO Not yet implemented)
	 *  @param      string  $firstname   	Firstname (TODO Not yet implemented)
	 *  @param      string  $ref_ext    	External reference, not given by Dolibarr
	 *  @param		string	$email			Email
	 *  @param		string	$ref_alias		Name alias (TODO Not yet implemented)
	 *  @param		int		$socid			Filter on thirdparty id
	 *  @return     int     		    	ID of contact if OK, <0 if KO or if two records found for same ref or idprof, 0 if not found.
	 */
	public function findNearest($id = 0, $lastname = '', $firstname = '', $ref_ext = '', $email = '', $ref_alias = '', $socid = 0)
	{
		// A rowid is known, it is a unique key so we found it
		if ($id) {
			return $id;
		}

		// We try to find the contact with exact matching on all fields
		// TODO Replace this with step by step search
		// Then search on email
		// Then search on lastname + firstname
		// Then search ref_ext or alias with a OR
		$tmpcontact = new Contact($this->db);
		$result = $tmpcontact->fetch($id, null, $ref_ext, $email, 0, $socid);

		return $result;
	}



	/**
	 * Set the property "gender" of this class, based on the property "civility_id"
	 * or use property "civility_code" as fallback, when "civility_id" is not available.
	 *
	 * @return void
	 */
	public function setGenderFromCivility()
	{
		unset($this->gender);

		if (in_array($this->civility_id, array('MR')) || in_array($this->civility_code, array('MR'))) {
			$this->gender = 'man';
		} elseif (in_array($this->civility_id, array('MME', 'MLE')) || in_array($this->civility_code, array('MME', 'MLE'))) {
			$this->gender = 'woman';
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Load number of elements the contact is used as a link for
	 *  ref_facturation
	 *  ref_contrat
	 *  ref_commande (for order and/or shipments)
	 *  ref_propale
	 *
	 *  @return     int             					Return integer <0 if KO, >=0 if OK
	 */
	public function load_ref_elements()
	{
		// phpcs:enable
		// Count the elements for which it is contact
		$sql = "SELECT tc.element, count(ec.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as tc";
		$sql .= " WHERE ec.fk_c_type_contact = tc.rowid";
		$sql .= " AND fk_socpeople = ".((int) $this->id);
		$sql .= " AND tc.source = 'external'";
		$sql .= " GROUP BY tc.element";

		dol_syslog(get_class($this)."::load_ref_elements", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				if ($obj->nb) {
					if ($obj->element == 'facture') {
						$this->ref_facturation = $obj->nb;
					} elseif ($obj->element == 'contrat') {
						$this->ref_contrat = $obj->nb;
					} elseif ($obj->element == 'commande') {
						$this->ref_commande = $obj->nb;
					} elseif ($obj->element == 'propal') {
						$this->ref_propal = $obj->nb;
					}
				}
			}
			$this->db->free($resql);
			return 0;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Delete a contact from database
	 *
	 *  @param		User	$user			User making the delete
	 *  @param		int		$notrigger		Disable all trigger
	 *	@return		int						Return integer <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		$error = 0;

		$this->db->begin();

		if (!$notrigger) {
			// Call trigger
			$result = $this->call_trigger('CONTACT_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			// Get all rowid of element_contact linked to a type that is link to llx_socpeople
			$sql = "SELECT ec.rowid";
			$sql .= " FROM ".MAIN_DB_PREFIX."element_contact ec,";
			$sql .= " ".MAIN_DB_PREFIX."c_type_contact tc";
			$sql .= " WHERE ec.fk_socpeople=".((int) $this->id);
			$sql .= " AND ec.fk_c_type_contact=tc.rowid";
			$sql .= " AND tc.source='external'";
			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);

				$i = 0;
				while ($i < $num && !$error) {
					$obj = $this->db->fetch_object($resql);

					$sqldel = "DELETE FROM ".MAIN_DB_PREFIX."element_contact";
					$sqldel .= " WHERE rowid = ".((int) $obj->rowid);
					dol_syslog(__METHOD__, LOG_DEBUG);
					$result = $this->db->query($sqldel);
					if (!$result) {
						$error++;
						$this->error = $this->db->error().' sql='.$sqldel;
					}

					$i++;
				}
			} else {
				$error++;
				$this->error = $this->db->error().' sql='.$sql;
			}
		}

		if (!$error) {
			// Remove Roles
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."societe_contacts WHERE fk_socpeople = ".((int) $this->id);
			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->error .= $this->db->lasterror();
				$errorflag = -1;
			}
		}

		if (!$error) {
			// Remove Notifications
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def WHERE fk_contact = ".((int) $this->id);
			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->error .= $this->db->lasterror();
				$errorflag = -1;
			}
		}

		if (!$error) {
			// Remove category
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."categorie_contact WHERE fk_socpeople = ".((int) $this->id);
			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->error .= $this->db->lasterror();
				$errorflag = -1;
			}
		}

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."socpeople";
			$sql .= " WHERE rowid = ".((int) $this->id);
			dol_syslog(__METHOD__, LOG_DEBUG);
			$result = $this->db->query($sql);
			if (!$result) {
				$error++;
				$this->error = $this->db->error().' sql='.$sql;
			}
		}

		// Remove extrafields
		if (!$error) {
			// For avoid conflicts if trigger used
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			dol_syslog("Error ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *  Load contact information from the database
	 *
	 *  @param		int		$id      Id of the contact to load
	 *  @return		void
	 */
	public function info($id)
	{
		$sql = "SELECT c.rowid, c.datec as datec, c.fk_user_creat,";
		$sql .= " GREATEST(c.tms, cef.tms) as tms, c.fk_user_modif";
		$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople_extrafields as cef ON cef.fk_object=c.rowid";
		$sql .= " WHERE c.rowid = ".((int) $id);

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_creat;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
			}

			$this->db->free($resql);
		} else {
			print $this->db->error();
		}
	}

	/**
	 *  Return number of mass Emailing received by these contacts with its email
	 *
	 *  @return       int     Number of EMailings
	 */
	public function getNbOfEMailings()
	{
		$sql = "SELECT count(mc.email) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc, ".MAIN_DB_PREFIX."mailing as m";
		$sql .= " WHERE mc.fk_mailing=m.rowid AND mc.email = '".$this->db->escape($this->email)."' ";
		$sql .= " AND m.entity IN (".getEntity($this->element).") AND mc.statut NOT IN (-1,0)"; // -1 error, 0 not sent, 1 sent with success

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$nb = (int) $obj->nb;

			$this->db->free($resql);
			return $nb;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 * getTooltipContentArray
	 * @param array<string,mixed> $params params to construct tooltip data
	 * @since v18
	 * @return array{picto?:string,ref?:string,refsupplier?:string,label?:string,date?:string,date_echeance?:string,amountht?:string,total_ht?:string,totaltva?:string,amountlt1?:string,amountlt2?:string,amountrevenustamp?:string,totalttc?:string}|array{optimize:string}
	 */
	public function getTooltipContentArray($params)
	{
		global $conf, $langs, $user;

		$datas = [];

		if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			return ['optimize' => $langs->trans("ShowContact")];
		}
		if (!empty($this->photo) && class_exists('Form')) {
			$photo = '<div class="photointooltip floatright">';
			$photo .= Form::showphoto('contact', $this, 0, 40, 0, 'photoref', 'mini', 0); // Important, we must force height so image will have height tags and if image is inside a tooltip, the tooltip manager can calculate height and position correctly the tooltip.
			$photo .= '</div>';
			$datas['photo'] = $photo;
		}

		$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Contact").'</u> ' . $this->getLibStatut(4);
		$datas['name'] = '<br><b>'.$langs->trans("Name").':</b> '.$this->getFullName($langs);
		// if ($this->civility_id) $datas['civility'] = '<br><b>' . $langs->trans("Civility") . ':</b> '.$this->civility_id;		// TODO Translate civilty_id code
		if (!empty($this->poste)) {
			$datas['job'] = '<br><b>'.$langs->trans("Poste").':</b> '.$this->poste;
		}
		$datas['email'] = '<br><b>'.$langs->trans("EMail").':</b> '.$this->email;
		$phonelist = array();
		$country_code = empty($this->country_code) ? '' : $this->country_code;
		if ($this->phone_pro) {
			$phonelist[] = dol_print_phone($this->phone_pro, $country_code, $this->id, 0, '', '&nbsp;', 'phone');
		}
		if ($this->phone_mobile) {
			$phonelist[] = dol_print_phone($this->phone_mobile, $country_code, $this->id, 0, '', '&nbsp;', 'mobile');
		}
		if ($this->phone_perso) {
			$phonelist[] = dol_print_phone($this->phone_perso, $country_code, $this->id, 0, '', '&nbsp;', 'phone');
		}
		$datas['phonelist'] = '<br><b>'.$langs->trans("Phone").':</b> '.implode('&nbsp;', $phonelist);
		$datas['address'] = '<br><b>'.$langs->trans("Address").':</b> '.dol_format_address($this, 1, ' ', $langs);

		return $datas;
	}

	/**
	 *  Return name of contact with link (and eventually picto)
	 *	Use $this->id, $this->lastname, $this->firstname, this->civility_id
	 *
	 *	@param		int			$withpicto					Include picto with link (0=no picto, 1=picto + name, 2=picto only, -1=photo+name, -2=photo only, -3=picto small + name)
	 *	@param		string		$option						Where the link point to ('nolink', ...)
	 *	@param		int			$notooltip					1=Disable tooltip
	 *  @param		string		$moreparam					Add more param into URL
	 *  @param      int     	$save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@param		int			$maxlen						Max len
	 *  @param  	string  	$morecss            		Add more css on link
	 *	@return		string									String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $moreparam = '', $save_lastsearch_value = -1, $maxlen = 0, $morecss = 'valignmiddle')
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

		$baseurl = DOL_URL_ROOT . '/contact/card.php';
		$query = ['id' => $this->id];
		if ($option !== 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$query = array_merge($query, ['save_lastsearch_values' => 1]);
			}
		}
		$url = dolBuildUrl($baseurl, $query);

		$url .= $moreparam;

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowContact");
				$linkclose .= ' alt="'.dolPrintHTMLForAttribute($label).'"';
			}
			$linkclose .= ($label ? ' title="'.dolPrintHTMLForAttribute($label).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		if ($option == 'nolink') {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="'.$url.'"';
		}
		$linkstart .= $linkclose.'>';
		if ($option == 'nolink') {
			$linkend = '</span>';
		} else {
			$linkend = '</a>';
		}

		$result .= $linkstart;

		if ($withpicto) {
			if ($withpicto < 0) {
				$result .= '<!-- picto photo contact --><span class="nopadding userimg'.($morecss ? ' '.$morecss : '').'">'.Form::showphoto('contact', $this, 0, 0, 0, 'userphoto'.($withpicto == -3 ? 'small' : ''), 'mini', 0, 1).'</span>';
				if ($withpicto != 2 && $withpicto != -2) {
					$result .= ' ';
				}
			} else {
				$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="pictofixedwidth valignmiddle"' : '') : 'class="'.(($withpicto != 2) ? 'pictofixedwidth valignmiddle' : '').'"'), 0, 0, $notooltip ? 0 : 1);
			}
		}
		if ($withpicto != 2 && $withpicto != -2) {
			$result .= '<span class="valignmiddle">'.($maxlen ? dol_trunc($this->getFullName($langs), $maxlen) : $this->getFullName($langs)).'</span>';
		}

		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array('contactdao'));
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
	 *    Return civility label of contact
	 *
	 *    @return	string      			Translated name of civility
	 */
	public function getCivilityLabel()
	{
		global $langs;

		$code = ($this->civility_code ? $this->civility_code : (!empty($this->civility_id) ? $this->civility : (!empty($this->civilite) ? $this->civilite : '')));
		if (empty($code)) {
			return '';
		}

		$langs->load("dict");
		return $langs->getLabelFromKey($this->db, "Civility".$code, "c_civility", "code", "label", $code);
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode)
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
	public function LibStatut($status, $mode)
	{
		// phpcs:enable
		global $langs;

		$labelStatus = array(
			0 => 'ActivityCeased',
			1 => 'InActivity',
			4 => 'InActivity',
			5 => 'ActivityCeased',
		);
		$labelStatusShort = array(
			0 => 'ActivityCeased',
			1 => 'InActivity',
			4 => 'InActivity',
			5 => 'ActivityCeased',
		);

		$statusType = 'status4';
		if ($status == 0 || $status == 5) {
			$statusType = 'status5';
		}

		$label = $langs->transnoentitiesnoconv($labelStatus[$status]);
		$labelshort = $langs->transnoentitiesnoconv($labelStatusShort[$status]);

		return dolGetStatus($label, $labelshort, '', $statusType, $mode);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return translated label of Public or Private
	 *
	 * 	@param      int			$status		Type (0 = public, 1 = private)
	 *  @param		int			$decorate	0=No decorate, 1=Add html decorated code (color) around text, 2=Show picto for private and empty for public
	 *  @return     string					Label translated
	 */
	public function LibPubPriv($status, $decorate = 0)
	{
		// phpcs:enable
		global $langs;
		if ($status == '1') {
			$s = ($decorate == 2 ? '' : $langs->trans('ContactPrivate'));
			if ($decorate) {
				$s = '<span title="'.$langs->trans('ContactPrivateDesc').'">'.img_picto('', 'private', 'class="paddingrightonly"').$s.'</span>';
			}
			return $s;
		} else {
			$s = ($decorate == 2 ? '' : $langs->trans('ContactPublic'));
			if ($decorate) {
				$s = '<span title="'.$langs->trans('ContactPublicDesc').'">'.img_picto('', 'public', 'class="paddingrightonly"').$s.'</span>';
			}
			return $s;
		}
	}


	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return	int >0 if ok
	 */
	public function initAsSpecimen()
	{
		// Get first id of existing company and save it into $socid
		$socid = 0;
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe ORDER BY rowid LIMIT 1";
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$socid = $obj->rowid;
			}
		}

		// Initialise parameters
		$this->id = 0;
		$this->entity = 1;
		$this->specimen = 1;
		$this->lastname = 'DOLIBARR';
		$this->firstname = 'SPECIMEN';
		$this->address = '21 jump street';
		$this->zip = '99999';
		$this->town = 'MyTown';
		$this->country_id = 1;
		$this->country_code = 'FR';
		$this->country = 'France';
		$this->email = 'specimen@specimen.com';
		$this->socialnetworks = array(
			'skype' => 'tom.hanson',
			'twitter' => 'tomhanson',
			'linkedin' => 'tomhanson',
		);
		$this->phone_pro = '0909090901';
		$this->phone_perso = '0909090902';
		$this->phone_mobile = '0909090903';
		$this->fax = '0909090909';

		$this->note_public = 'This is a comment (public)';
		$this->note_private = 'This is a comment (private)';

		$this->socid = $socid;
		$this->status = 1;

		return 1;
	}

	/**
	 *  Change status of a user
	 *
	 *	@param	int		$status		Status to set
	 *  @return int     			Return integer <0 if KO, 0 if nothing is done, >0 if OK
	 */
	public function setstatus($status)
	{
		global $conf, $langs, $user;

		$error = 0;

		// Check parameters
		if (!empty($this->statut) && empty($this->status)) {
			$this->status = 1;
		}
		if ($this->status == $status) {
			return 0;
		} else {
			$this->status = $status;
			$this->statut = $status;
		}

		$this->db->begin();

		// User disable
		$sql = "UPDATE ".MAIN_DB_PREFIX."socpeople";
		$sql .= " SET statut = ".((int) $this->status);
		$sql .= ", fk_user_modif = ".((int) $user->id);
		$sql .= " WHERE rowid = ".((int) $this->id);
		$result = $this->db->query($sql);

		dol_syslog(get_class($this)."::setstatus", LOG_DEBUG);
		if ($result) {
			// Call trigger
			$result = $this->call_trigger('CONTACT_ENABLEDISABLE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if ($error) {
			$this->db->rollback();
			return -$error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 * Sets object to supplied categories.
	 *
	 * Assign the object to all categories not yet assigned.
	 * Unasign object from existing categories not supplied in $categories (if remove_existing==true).
	 * If remove_existing is false, existing categories are left untouch.
	 *
	 * @param 	int[]|int 	$categories 		Category or categories IDs
	 * @param 	boolean		$remove_existing 	True: Remove existings categories from Object if not supplies by $categories, False: let them
	 * @return 	int								Return integer <0 if KO, >0 if OK
	 */
	public function setCategories($categories, $remove_existing = true)
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		return parent::setCategoriesCommon($categories, Categorie::TYPE_CONTACT, $remove_existing);
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
			'socpeople', 'societe_contacts'
		);

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}

	/**
	 * Function used to replace a contact id with another one when merging two contacts.
	 * Every table having a unique index on the contact id is deduplicated before its update, so the
	 * update cannot violate it.
	 * llx_categorie_contact is not handled here (done by setCategories) and llx_socpeople_extrafields
	 * is not either (values are merged into the target contact before its update).
	 *
	 * @param  DoliDB	$dbs		Database handler
	 * @param  int		$origin_id	Old contact id (the contact to delete)
	 * @param  int		$dest_id	New contact id (the contact that will receive elements of the other)
	 * @return bool					True if success, False if error
	 */
	public static function replaceContact(DoliDB $dbs, $origin_id, $dest_id)
	{
		// llx_societe_contacts: UNIQUE(entity, fk_soc, fk_c_type_contact, fk_socpeople).
		// Delete the roles the target contact already has, then move the remaining ones.
		$sql = 'DELETE FROM '.$dbs->prefix().'societe_contacts WHERE rowid IN (';
		$sql .= ' SELECT x.rowid FROM (';
		$sql .= '  SELECT origin.rowid FROM '.$dbs->prefix().'societe_contacts as origin';
		$sql .= '  INNER JOIN '.$dbs->prefix().'societe_contacts as dest ON dest.entity = origin.entity';
		$sql .= '   AND dest.fk_soc = origin.fk_soc AND dest.fk_c_type_contact = origin.fk_c_type_contact';
		$sql .= '  WHERE origin.fk_socpeople = '.((int) $origin_id).' AND dest.fk_socpeople = '.((int) $dest_id);
		$sql .= ' ) as x)';
		if (!$dbs->query($sql)) {
			return false;
		}

		if (!CommonObject::commonReplaceContact($dbs, $origin_id, $dest_id, array('societe_contacts'))) {
			return false;
		}

		// llx_element_contact.fk_socpeople points to llx_socpeople only when c_type_contact.source is
		// 'external'. It points to llx_user when source is 'internal', so both queries below MUST filter
		// on it, otherwise internal (user) assignments would be moved to the merged contact.


		// llx_element_contact: UNIQUE(element_id, fk_c_type_contact, fk_socpeople)
		$sql = 'DELETE FROM '.$dbs->prefix().'element_contact WHERE rowid IN (';
		$sql .= ' SELECT x.rowid FROM (';
		$sql .= '  SELECT origin.rowid FROM '.$dbs->prefix().'element_contact as origin';
		$sql .= '  INNER JOIN '.$dbs->prefix().'element_contact as dest ON dest.element_id = origin.element_id';
		$sql .= '   AND dest.fk_c_type_contact = origin.fk_c_type_contact';
		$sql .= '  WHERE origin.fk_socpeople = '.((int) $origin_id).' AND dest.fk_socpeople = '.((int) $dest_id);
		$sql .= "   AND origin.fk_c_type_contact IN (SELECT rowid FROM ".$dbs->prefix()."c_type_contact WHERE source = 'external')";
		$sql .= ' ) as x)';
		if (!$dbs->query($sql)) {
			return false;
		}

		$sql = 'UPDATE '.$dbs->prefix().'element_contact SET fk_socpeople = '.((int) $dest_id);
		$sql .= ' WHERE fk_socpeople = '.((int) $origin_id);
		$sql .= " AND fk_c_type_contact IN (SELECT rowid FROM ".$dbs->prefix()."c_type_contact WHERE source = 'external')";
		if (!$dbs->query($sql)) {
			return false;
		}

		// References to a contact stored as a (type, id) couple. All the names below are literals, so
		// they are safe to concatenate. 'unique' lists the other columns of the unique index of the
		// table, if any, so the rows the target contact already has can be dropped before the update.
		$polymorphic = array(
			array('table' => 'object_lang', 'id' => 'fk_object', 'type' => 'type_object',
				'values' => array('contact', 'socpeople'), 'unique' => array('property', 'lang')),
			array('table' => 'links', 'id' => 'objectid', 'type' => 'objecttype',
				'values' => array('contact'), 'unique' => array('label')),
			array('table' => 'element_element', 'id' => 'fk_source', 'type' => 'sourcetype',
				'values' => array('contact'), 'unique' => array('fk_target', 'targettype')),
			array('table' => 'element_element', 'id' => 'fk_target', 'type' => 'targettype',
				'values' => array('contact'), 'unique' => array('fk_source', 'sourcetype')),
			// An event can be linked to a contact as its related object
			array('table' => 'actioncomm', 'id' => 'fk_element', 'type' => 'elementtype',
				'values' => array('contact'), 'unique' => array()),
			// dol_move() updates the path of the indexed files but never their source object, so the
			// index rows have to be moved here or they would point to the deleted contact. The unique
			// index of the table is on (filepath, filename, entity), which is left untouched.
			array('table' => 'ecm_files', 'id' => 'src_object_id', 'type' => 'src_object_type',
				'values' => array('contact', 'socpeople'), 'unique' => array()),
			array('table' => 'quickmemo_memo', 'id' => 'fk_element', 'type' => 'element_type',
				'values' => array('contact'), 'unique' => array()),
			array('table' => 'comment', 'id' => 'fk_element', 'type' => 'element_type',
				'values' => array('contact'), 'unique' => array()),
		);

		foreach ($polymorphic as $ref) {
			// Some of these tables are provided by modules that may not be installed
			$sanitizedtable = $dbs->sanitize($ref['table']);
			$sanitizedidcol = $dbs->sanitize($ref['id']);
			$sanitizedtypecol = $dbs->sanitize($ref['type']);
			if (!$dbs->DDLListTables((string) $dbs->database_name, $dbs->prefix().$sanitizedtable)) {
				continue;
			}
			// Each value is escaped on its own: sanitize() removes the quotes inside the string it is
			// given, so sanitizing an already assembled list would collapse it into a single value
			$quotedvalues = array();
			foreach ($ref['values'] as $refvalue) {
				$quotedvalues[] = "'".$dbs->escape($refvalue)."'";
			}
			$sanitizedvalues = implode(', ', $quotedvalues);  // @phan-suppress-current-line SqlInjection
			$sanitizedtypefilter = $sanitizedtypecol." IN (".$sanitizedvalues.")";

			if (!empty($ref['unique'])) {
				$sql = "DELETE FROM ".$dbs->prefix().$sanitizedtable." WHERE rowid IN (";
				$sql .= " SELECT x.rowid FROM (";
				$sql .= "  SELECT origin.rowid FROM ".$dbs->prefix().$sanitizedtable." as origin";
				$sql .= "  INNER JOIN ".$dbs->prefix().$sanitizedtable." as dest";
				$sql .= "   ON dest.".$sanitizedtypecol." = origin.".$sanitizedtypecol;
				foreach ($ref['unique'] as $uniquecol) {
					$sanitizeduniquecol = $dbs->sanitize($uniquecol);
					$sql .= " AND dest.".$sanitizeduniquecol." = origin.".$sanitizeduniquecol;
				}
				$sql .= "  WHERE origin.".$sanitizedidcol." = ".((int) $origin_id);
				$sql .= "   AND dest.".$sanitizedidcol." = ".((int) $dest_id);
				$sql .= "   AND origin.".$sanitizedtypefilter;
				$sql .= " ) as x)";
				if (!$dbs->query($sql)) {
					return false;
				}
			}

			$sql = "UPDATE ".$dbs->prefix().$sanitizedtable." SET ".$sanitizedidcol." = ".((int) $dest_id);
			$sql .= " WHERE ".$sanitizedtypefilter;
			$sql .= " AND ".$sanitizedidcol." = ".((int) $origin_id);
			if (!$dbs->query($sql)) {
				return false;
			}
		}

		// A link between the two contacts became a link of the target contact to itself, which the
		// linked objects box would then display. There is no unique index violation, so nothing failed.
		$sql = "DELETE FROM ".$dbs->prefix()."element_element WHERE fk_source = fk_target";
		$sql .= " AND sourcetype = targettype AND fk_source = ".((int) $dest_id);
		$sql .= " AND sourcetype = 'contact'";
		if (!$dbs->query($sql)) {
			return false;
		}

		return true;
	}

	/**
	 * Merge a contact with the current one, deleting the given contact $contact_origin_id.
	 * All satellite data of the merged contact are moved to the current contact.
	 * Access guards are implemented here and not into the calling page, and cover the two contacts, so
	 * the REST API, the scheduled jobs and the external modules also benefit from them.
	 * Must not be called inside an already open transaction: DoliDB::rollback() only decrements the
	 * nesting counter, so the caller would commit a partially merged contact.
	 *
	 * @param	int		$contact_origin_id	Contact to merge the data from (will be deleted)
	 * @return	int							Return integer -1 if error, >=0 if OK
	 */
	public function mergeContact($contact_origin_id)
	{
		global $langs, $hookmanager, $user, $action;

		$error = 0;
		$langs->loadLangs(array('errors', 'companies'));

		// The target contact must have been loaded: update() would silently update no row and the
		// satellite data would then be moved to the contact id 0.
		if (!($this->id > 0) || empty($this->entity)) {
			$this->error = $langs->trans('ErrorBadParameters');
			dol_syslog(__METHOD__.' Called on a contact that was not loaded', LOG_ERR);
			return -1;
		}
		if ($contact_origin_id <= 0 || $contact_origin_id == $this->id) {
			$this->error = $langs->trans('ErrorBadParameters');
			return -1;
		}
		// A merge deletes a contact, so it requires the permission to delete one, whatever the caller
		if (!$user->hasRight('societe', 'contact', 'creer') || !$user->hasRight('societe', 'contact', 'supprimer')) {
			$this->error = $langs->trans('ErrorForbidden');
			return -1;
		}
		// An external user never merges anything, as on the third party card
		if ($user->socid > 0) {
			$this->error = $langs->trans('ErrorForbidden');
			return -1;
		}

		$contact_origin = new Contact($this->db);	// The contact that we will delete
		$resultfetch = $contact_origin->fetch($contact_origin_id);
		// fetch() returns the id when found, 2 when several records were found, 0 when not found and -1 on error
		if ($resultfetch != $contact_origin_id) {
			$this->error = $langs->trans('ErrorRecordNotFound');
			dol_syslog(__METHOD__.' Cannot fetch contact id='.$contact_origin_id.', result='.$resultfetch, LOG_ERR);
			return -1;
		}

		// Access guards. fetch() by rowid applies neither an entity filter nor a permission filter, so
		// the two contacts are revalidated here, including the current one: an id coming from a POST is
		// not to be trusted, and the checks must also protect the callers that are not the contact card.
		$entities = explode(',', getEntity('contact'));
		foreach (array($this, $contact_origin) as $tmpcontact) {
			if (!in_array($tmpcontact->entity, $entities) || $tmpcontact->entity != $this->entity) {
				$this->error = $langs->trans('ErrorContactsMergeDifferentEntity');
				return -1;
			}
			if (!empty($tmpcontact->priv) && $tmpcontact->user_creation_id != $user->id) {
				$this->error = $langs->trans('ErrorContactsMergePrivate');
				return -1;
			}
			// A contact without a third party is shared, so the perimeter of the sales representatives
			// does not apply to it, as in restrictedArea()
			if ($tmpcontact->socid > 0 && !$user->hasRight('societe', 'client', 'voir')
				&& !$this->isSalesRepresentativeOf($tmpcontact->socid)) {
				$this->error = $langs->trans('ErrorForbidden');
				return -1;
			}
		}
		// Absorbing a shared contact into a private one would hide its data from everybody else,
		// including the administrators, and the merged contact is deleted so it is not reversible
		if (!empty($this->priv) && empty($contact_origin->priv)) {
			$this->error = $langs->trans('ErrorContactsMergeIntoPrivate');
			return -1;
		}
		$originlinked = $this->isLinkedToUser($contact_origin->id);
		$destlinked = $this->isLinkedToUser($this->id);
		if ($originlinked < 0 || $destlinked < 0) {
			// The guard below is a security one, so it must refuse and not let the merge through
			$this->error = $langs->trans('ErrorContactsMerge');
			return -1;
		}
		// Moving llx_user.fk_socpeople would give the contact of a user account to another contact,
		// and Contact::update() then propagates the email of that contact to the user, which is a way
		// to take over the account. Changing the contact of a user requires the permission to do so.
		if (($originlinked > 0 || $destlinked > 0) && !$user->hasRight('user', 'user', 'creer')) {
			$this->error = $langs->trans('ErrorContactsMergeLinkedToUser');
			return -1;
		}
		// llx_user.fk_socpeople has a unique key: refuse rather than silently break a user link
		if ($originlinked > 0 && $destlinked > 0) {
			$this->error = $langs->trans('ErrorContactsMergeBothLinkedToUser');
			return -1;
		}

		dol_syslog(__METHOD__.' merge contact id='.$contact_origin->id.' (will be deleted) into the contact id='.$this->id);

		$this->db->begin();

		// Recopy some data
		foreach (self::MERGE_FIELDS_FILL_IF_EMPTY as $property) {
			if (empty($this->$property) && !empty($contact_origin->$property)) {
				$this->$property = $contact_origin->$property;
			}
		}

		// Concat some data, with a dated mention so a targeted erasure stays possible later
		$mention = '['.$langs->transnoentitiesnoconv('MergedFromContact', dol_print_date(dol_now(), 'day'), (string) $contact_origin->id).']';
		foreach (self::MERGE_FIELDS_CONCAT as $property) {
			if (!empty($contact_origin->$property)) {
				$this->$property = dol_concatdesc($this->$property, $mention."\n".$contact_origin->$property);
			}
		}

		// A merge must never make the data of a private contact visible to everybody
		if (!empty($contact_origin->priv)) {
			$this->priv = 1;
		}

		// If alias name is not defined on target contact, we can store in it the old name of the contact
		if (empty($this->name_alias) && $this->getFullName($langs) != $contact_origin->getFullName($langs)) {
			$this->name_alias = $contact_origin->getFullName($langs);
		}

		// Merge extrafields. They are saved by the update() below.
		if (is_array($contact_origin->array_options)) {
			foreach ($contact_origin->array_options as $key => $val) {
				if (empty($this->array_options[$key])) {
					$this->array_options[$key] = $val;
				}
			}
		}

		// updateRoles(), called by update(), deletes then reinserts every societe_contacts row of the
		// contact from $this->roles, which would wipe the roles we are about to move. It is a no-op
		// when roles is not set. Set it to null instead of using unset(): roles is a declared property
		// and unset() would make any later access emit an "Undefined property" warning.
		$this->roles = null;

		// Update. The trigger is called once at the end of the merge, hence $notrigger = 1.
		if ($this->update($this->id, $user, 1) <= 0) {
			$error++;
			dol_syslog(__METHOD__.' Failed to update the target contact: '.$this->errorsToString(), LOG_ERR);
		}

		// Merge categories, before the deletion below: llx_categorie_contact has a foreign key on
		// llx_socpeople without ON DELETE.
		if (!$error) {
			include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
			$static_cat = new Categorie($this->db);
			$cats_origin = $static_cat->containing($contact_origin->id, 'contact', 'id');
			$cats_dest = $static_cat->containing($this->id, 'contact', 'id');
			// containing() returns the int -1 on SQL error. Reading it as an empty list would replace
			// the categories of the target contact by the ones of the merged contact only.
			if (!is_array($cats_origin) || !is_array($cats_dest)) {
				$this->error = $static_cat->error;
				dol_syslog(__METHOD__.' Cannot read the categories of the contacts: '.$this->error, LOG_ERR);
				$error++;
			} else {
				// array_merge() must be used here: the + operator on arrays is a union on keys, it
				// would silently drop categories.
				$cats = array_merge($cats_origin, $cats_dest);
				if ($this->setCategories(array_values(array_unique($cats))) < 0) {
					$error++;
				}
			}
		}

		// Children contacts
		if (!$error) {
			$error += $this->mergeContactChildren($contact_origin);
		}

		// Move links
		if (!$error) {
			$objects = array(
				'ActionComm' => '/comm/action/class/actioncomm.class.php',
				'Contact' => '/contact/class/contact.class.php',
				'User' => '/user/class/user.class.php',
			);
			foreach ($objects as $object_name => $object_file) {
				require_once DOL_DOCUMENT_ROOT.$object_file;

				if (!$object_name::replaceContact($this->db, $contact_origin->id, $this->id)) {
					$error++;
					$this->error = $this->db->lasterror();
					dol_syslog(__METHOD__.' '.$object_name.'::replaceContact failed: '.$this->error, LOG_ERR);
					break;
				}
			}
		}

		// Tables of the optional modules
		if (!$error) {
			$error += $this->mergeContactOptionalTables($contact_origin);
		}

		// External modules should update their ones too
		if (!$error) {
			$parameters = array('contact_origin' => $contact_origin->id, 'contact_dest' => $this->id);
			$reshook = $hookmanager->executeHooks('replaceContact', $parameters, $this, $action);

			if ($reshook < 0) {
				$this->error = $hookmanager->error;
				$this->errors = $hookmanager->errors;
				$error++;
			}
		}

		if (!$error) {
			$this->context = array(
				'merge' => 1,
				'mergefromid' => $contact_origin->id,
				'mergefromname' => $contact_origin->getFullName($langs)
			);

			// Call trigger
			$result = $this->call_trigger('CONTACT_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			// We finally remove the old contact
			if ($contact_origin->delete($user) < 1) {
				$this->error = $contact_origin->error;
				$this->errors = $contact_origin->errors;
				$error++;
			}
		}

		if ($error) {
			$this->error = $langs->trans('ErrorContactsMerge').' '.$this->error;
			$this->db->rollback();
			// The object still holds the merged values in memory, reload it so the caller does not
			// display data that was rolled back
			$this->fetch($this->id);
			return -1;
		}

		$this->db->commit();

		// Files are moved once the transaction is committed: dol_move() is not transactional, and
		// Contact::delete() does not remove the directory of the contact, so the files are still there.
		$this->mergeContactFiles($contact_origin->id);

		return 0;
	}

	/**
	 * Tell whether a user account is linked to the given contact.
	 * llx_user.fk_socpeople has a unique key, so a merge cannot move that link when the target
	 * contact is already linked to another user.
	 *
	 * @param	int		$contactid	Id of the contact to check
	 * @return	int					1 if a user is linked to this contact, 0 if none, -1 on error
	 */
	private function isLinkedToUser($contactid)
	{
		$sql = "SELECT rowid FROM ".$this->db->prefix()."user WHERE fk_socpeople = ".((int) $contactid);

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			return -1;
		}
		$found = ($this->db->num_rows($resql) > 0 ? 1 : 0);
		$this->db->free($resql);

		return $found;
	}

	/**
	 * Tell whether the current user is a sales representative of the given third party.
	 * Used to keep a user restricted to his own portfolio from merging a contact he cannot see.
	 *
	 * @param	int		$socid	Id of the third party of the contact to merge
	 * @return	bool			True if allowed
	 */
	private function isSalesRepresentativeOf($socid)
	{
		global $user;

		if (empty($socid)) {
			return false;	// A shared contact with no third party is out of any portfolio
		}

		$sql = "SELECT fk_soc FROM ".$this->db->prefix()."societe_commerciaux";
		$sql .= " WHERE fk_soc = ".((int) $socid)." AND fk_user = ".((int) $user->id);

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			return false;
		}
		$found = ($this->db->num_rows($resql) > 0);
		$this->db->free($resql);

		return $found;
	}

	/**
	 * Move the children of the merged contact to the target contact.
	 * llx_socpeople.fk_parent has neither a foreign key nor an index, and it is not written by
	 * update(), so the hierarchy must be fixed with dedicated queries. Two corruptions have to be
	 * avoided: a dangling pointer when the target contact is a child of the merged one, and a cycle
	 * when a child of the merged contact is an ancestor of the target one.
	 *
	 * @param	Contact	$contact_origin	Contact being merged into the current one
	 * @return	int						Number of errors
	 */
	private function mergeContactChildren($contact_origin)
	{
		$error = 0;

		// fk_parent is not loaded by fetch()
		$parentofdest = $this->getParentId($this->id);

		// The target contact is a child of the merged one: its parent is about to be deleted
		if ($parentofdest == $contact_origin->id) {
			$newparent = $this->getParentId($contact_origin->id);
			// A dedicated query is used rather than setValueFrom(): the 'int' format of the latter
			// casts null to 0, while fk_parent is nullable, and its trigger key would fetch the
			// record again and overwrite the values merged into memory.
			$sql = 'UPDATE '.$this->db->prefix().'socpeople';
			$sql .= ' SET fk_parent = '.($newparent > 0 ? ((int) $newparent) : 'NULL');
			$sql .= ' WHERE rowid = '.((int) $this->id);
			if (!$this->db->query($sql)) {
				$this->error = $this->db->lasterror();
				dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
				return 1;
			}
		}

		// Collect the ancestors of the target contact, they must not become its children
		$ancestors = array();
		$currentid = $this->getParentId($this->id);
		$depth = 0;
		while ($currentid > 0 && $depth < self::MERGE_MAX_PARENT_DEPTH) {
			if (in_array($currentid, $ancestors)) {
				break;	// The hierarchy already contains a cycle, stop walking it
			}
			$ancestors[] = (int) $currentid;
			$currentid = $this->getParentId($currentid);
			$depth++;
		}

		$sql = 'UPDATE '.$this->db->prefix().'socpeople SET fk_parent = '.((int) $this->id);
		$sql .= ' WHERE fk_parent = '.((int) $contact_origin->id);
		$sql .= ' AND rowid <> '.((int) $this->id);
		if (!empty($ancestors)) {
			// $ancestors only contains ids already cast to int
			$sanitizedancestors = implode(',', $ancestors);  // @phan-suppress-current-line SqlInjection
			$sql .= " AND rowid NOT IN (".$sanitizedancestors.")";
		}
		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			$error++;
		}

		// The children excluded above, being ancestors of the target contact, still point to the
		// contact about to be deleted. Detach them rather than leave a dangling parent.
		if (!$error) {
			$sql = 'UPDATE '.$this->db->prefix().'socpeople SET fk_parent = NULL';
			$sql .= ' WHERE fk_parent = '.((int) $contact_origin->id);
			if (!$this->db->query($sql)) {
				$this->error = $this->db->lasterror();
				dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
				$error++;
			}
		}

		return $error;
	}

	/**
	 * Return the id of the parent contact of a contact, 0 if none.
	 * fk_parent is not among the columns loaded by fetch().
	 *
	 * @param	int		$contactid	Id of the contact
	 * @return	int					Id of the parent contact, 0 if none or on error
	 */
	private function getParentId($contactid)
	{
		$sql = "SELECT fk_parent FROM ".$this->db->prefix()."socpeople WHERE rowid = ".((int) $contactid);

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			return 0;
		}
		$parentid = 0;
		if ($obj = $this->db->fetch_object($resql)) {
			$parentid = (empty($obj->fk_parent) ? 0 : (int) $obj->fk_parent);
		}
		$this->db->free($resql);

		return $parentid;
	}

	/**
	 * Move the data stored by the optional modules and by the notification system.
	 * These tables are handled here instead of in a replaceContact() of their own class, to keep the
	 * number of modified files low, the same way Adherent::mergeMembers() does.
	 *
	 * @param	Contact	$contact_origin	Contact being merged into the current one
	 * @return	int						Number of errors
	 */
	private function mergeContactOptionalTables($contact_origin)
	{
		$error = 0;

		// Notifications. llx_notify_def has no unique key, but a duplicated row means the same
		// notification sent twice, so it must be deduplicated as well.
		$sql = 'DELETE FROM '.$this->db->prefix().'notify_def WHERE rowid IN (';
		$sql .= ' SELECT x.rowid FROM (';
		$sql .= '  SELECT origin.rowid FROM '.$this->db->prefix().'notify_def as origin';
		$sql .= '  INNER JOIN '.$this->db->prefix().'notify_def as dest ON dest.fk_action = origin.fk_action';
		// A row is a duplicate only if the whole definition matches, recipient included: fk_soc,
		// entity, type, threshold, context, fk_user and email are nullable, hence the NULL safe
		// comparisons, the MySQL <=> operator not being portable to PostgreSQL.
		$sql .= '   AND (dest.fk_soc = origin.fk_soc OR (dest.fk_soc IS NULL AND origin.fk_soc IS NULL))';
		$sql .= '   AND (dest.entity = origin.entity OR (dest.entity IS NULL AND origin.entity IS NULL))';
		$sql .= '   AND (dest.type = origin.type OR (dest.type IS NULL AND origin.type IS NULL))';
		$sql .= '   AND (dest.threshold = origin.threshold OR (dest.threshold IS NULL AND origin.threshold IS NULL))';
		$sql .= '   AND (dest.context = origin.context OR (dest.context IS NULL AND origin.context IS NULL))';
		$sql .= '   AND (dest.fk_user = origin.fk_user OR (dest.fk_user IS NULL AND origin.fk_user IS NULL))';
		$sql .= '   AND (dest.email = origin.email OR (dest.email IS NULL AND origin.email IS NULL))';
		$sql .= '  WHERE origin.fk_contact = '.((int) $contact_origin->id).' AND dest.fk_contact = '.((int) $this->id);
		$sql .= ' ) as x)';
		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			return 1;
		}

		if (!CommonObject::commonReplaceContact($this->db, $contact_origin->id, $this->id, array('notify', 'notify_def'), 'fk_contact')) {
			dol_syslog(__METHOD__.' Failed to move the notifications: '.$this->db->lasterror(), LOG_ERR);
			return 1;
		}

		// Mass emailing targets
		if ($this->db->DDLListTables((string) $this->db->database_name, $this->db->prefix().'mailing_cibles')) {
			// No deduplication here: uk_mailing_cibles is (fk_mailing, email), it does not contain
			// fk_contact, so moving fk_contact cannot violate it, and the rows hold the send history.
			if (!CommonObject::commonReplaceContact($this->db, $contact_origin->id, $this->id, array('mailing_cibles'), 'fk_contact')) {
				dol_syslog(__METHOD__.' Failed to move the mass emailing targets: '.$this->db->lasterror(), LOG_ERR);
				return 1;
			}

			// The source of a target is also stored as a (source_type, source_id) couple
			$sql = 'UPDATE '.$this->db->prefix().'mailing_cibles SET source_id = '.((int) $this->id);
			$sql .= " WHERE source_type = 'contact' AND source_id = ".((int) $contact_origin->id);
			if (!$this->db->query($sql)) {
				$this->error = $this->db->lasterror();
				dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
				return 1;
			}
		}

		return $error;
	}

	/**
	 * Move the files of the merged contact into the directory of the target contact.
	 * Called once the transaction is committed, because dol_move() is not transactional. A failure
	 * cannot be rolled back, so it is reported to the user instead of being silently logged.
	 *
	 * @param	int		$contact_origin_id	Id of the merged contact
	 * @return	void
	 */
	private function mergeContactFiles($contact_origin_id)
	{
		global $conf, $langs;

		if (empty($conf->societe->multidir_output[$this->entity])) {
			return;
		}

		// files.lib.php is not loaded when the merge is called outside of a web context
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		// The id is used and not $this->ref, which is null unless the contact was loaded by fetch()
		$srcdir = $conf->societe->multidir_output[$this->entity].'/contact/'.((int) $contact_origin_id);
		$destdir = $conf->societe->multidir_output[$this->entity].'/contact/'.((int) $this->id);

		if (!dol_is_dir($srcdir)) {
			return;
		}

		$failed = array();
		$dirlist = dol_dir_list($srcdir, 'files', 1);
		foreach ($dirlist as $filetomove) {
			$destfile = $destdir.'/'.$filetomove['relativename'];
			// dol_move() is called below with $overwriteifexists = 0, so a file already existing on
			// the target contact is renamed rather than lost
			if (dol_is_file($destfile)) {
				$info = pathinfo($filetomove['relativename']);
				$suffix = (empty($info['extension']) ? '' : '.'.$info['extension']);
				$destfile = $destdir.'/'.(empty($info['dirname']) || $info['dirname'] == '.' ? '' : $info['dirname'].'/');
				$destfile .= $info['filename'].'-'.((int) $contact_origin_id).$suffix;
			}
			// dol_move() does not create the target directory, and the target contact usually has
			// none yet, so it has to be created for every level of the source tree
			dol_mkdir(dirname($destfile));
			if (!dol_move($filetomove['fullname'], $destfile, '0', 0, 0, 1)) {
				$failed[] = $filetomove['relativename'];
			}
		}

		if (!empty($failed)) {
			dol_syslog(__METHOD__.' Failed to move '.count($failed).' file(s) from '.$srcdir, LOG_ERR);
			// The merge itself is committed, so this is reported as a warning and not as a failure
			$this->warnings[] = $langs->trans('WarningContactsMergeFilesNotMoved', implode(', ', $failed));
		}
	}

	/**
	 * Fetch roles (default contact of some companies) for the current contact.
	 * This load the array ->roles.
	 *
	 * @return 	int			Return integer <0 if KO, Nb of roles found if OK
	 * @see updateRoles()
	 */
	public function fetchRoles()
	{
		global $langs;
		$error = 0;
		$num = 0;

		$sql = "SELECT tc.rowid, tc.element, tc.source, tc.code, tc.libelle as label, sc.rowid as contactroleid, sc.fk_soc as socid";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_contacts as sc, ".MAIN_DB_PREFIX."c_type_contact as tc";
		$sql .= " WHERE tc.rowid = sc.fk_c_type_contact";
		$sql .= " AND tc.source = 'external' AND tc.active=1";
		$sql .= " AND sc.fk_socpeople = ".((int) $this->id);
		$sql .= " AND sc.entity IN (".getEntity('societe').')';

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->roles = array();

			$num = $this->db->num_rows($resql);
			if ($num > 0) {
				while ($obj = $this->db->fetch_object($resql)) {
					$transkey = "TypeContact_".$obj->element."_".$obj->source."_".$obj->code;
					$label_element = $langs->trans('ContactDefault_'.$obj->element);
					$this->roles[$obj->contactroleid] = array('id' => $obj->rowid, 'socid' => $obj->socid, 'element' => $obj->element, 'source' => $obj->source, 'code' => $obj->code, 'label' => $label_element.' - '.($langs->trans($transkey) != $transkey ? $langs->trans($transkey) : $obj->label));
				}
			}
		} else {
			$error++;
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->db->lasterror();
		}

		if (empty($error)) {
			return $num;
		} else {
			return $error * -1;
		}
	}

	/**
	 * Get thirdparty contact roles of a given contact
	 *
	 * @param  string 	$element 	Element type
	 * @return array<array{fk_socpeople:int,type_contact:int}>|int<-1,-1>	Array of contact roles or -1 if error
	 * @throws Exception
	 */
	public function getContactRoles($element = '')
	{
		$tab = array();

		if ($element == 'action') {
			$element = 'agenda';
		}

		$sql = "SELECT sc.fk_socpeople as id, sc.fk_c_type_contact";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_contact tc";
		$sql .= ", ".MAIN_DB_PREFIX."societe_contacts sc";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."socpeople sp";
		$sql .= " ON sc.fk_socpeople = sp.rowid AND sp.statut = 1";
		$sql .= " WHERE sc.fk_soc = ".((int) $this->socid);
		$sql .= " AND sc.fk_c_type_contact=tc.rowid";
		$sql .= " AND tc.element = '".$this->db->escape($element)."'";
		$sql .= " AND sp.entity IN (".getEntity('contact').")";
		$sql .= " AND tc.active = 1";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$tab[$obj->id] = array('fk_socpeople' => $obj->id, 'type_contact' => $obj->fk_c_type_contact);

				$i++;
			}

			return $tab;
		} else {
			$this->error = $this->db->error();
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * Updates all roles (default contact for companies) according to values inside the ->roles array.
	 * This is called by update of contact.
	 *
	 * @return int
	 * @see fetchRoles()
	 */
	public function updateRoles()
	{
		global $conf;

		$error = 0;

		if (!isset($this->roles)) {
			return 0;	// Avoid to loose roles when property not set
		}

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."societe_contacts WHERE fk_socpeople=".((int) $this->id)." AND entity IN (".getEntity("contact").")";

		$result = $this->db->query($sql);
		if (!$result) {
			$this->errors[] = $this->db->lasterror().' sql='.$sql;
			$error++;
		} else {
			if (count($this->roles) > 0) {
				foreach ($this->roles as $keyRoles => $valRoles) {
					if (empty($valRoles)) {
						continue;
					}
					$idrole = 0;
					if (is_array($valRoles)) {
						$idrole = $valRoles['id'];
					} else {
						$idrole = $valRoles;
					}

					$socid = 0;
					if (is_array($valRoles)) {
						$socid = $valRoles['socid'];
					} else {
						$socid = $this->socid;
					}

					if ($socid > 0) {
						$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_contacts";
						$sql .= " (entity,";
						$sql .= "date_creation,";
						$sql .= "fk_soc,";
						$sql .= "fk_c_type_contact,";
						$sql .= "fk_socpeople) ";
						$sql .= " VALUES (".((int) $conf->entity).",";
						$sql .= "'".$this->db->idate(dol_now())."',";
						$sql .= ((int) $socid).", ";
						$sql .= ((int) $idrole)." , ";
						$sql .= ((int) $this->id);
						$sql .= ")";

						$result = $this->db->query($sql);
						if (!$result) {
							$this->errors[] = $this->db->lasterror().' sql='.$sql;
							$error++;
						}
					}
				}
			}
		}
		if (empty($error)) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = implode(' ', $this->errors);
			$this->db->rollback();
			return $error * -1;
		}
	}

	/**
	 *  Load array of prospect status
	 *
	 *  @param	int		$active     1=Active only, 0=Not active only, -1=All
	 *  @return int					Return integer <0 if KO, >0 if OK
	 */
	public function loadCacheOfProspStatus($active = 1)
	{
		global $langs;

		$sql = "SELECT id, code, libelle as label, picto FROM ".MAIN_DB_PREFIX."c_stcommcontact";
		if ($active >= 0) {
			$sql .= " WHERE active = ".((int) $active);
		}
		$resql = $this->db->query($sql);
		$num = $this->db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$obj = $this->db->fetch_object($resql);
			$this->cacheprospectstatus[$obj->id] = array('id' => $obj->id, 'code' => $obj->code, 'label' => ($langs->trans("ST_".strtoupper($obj->code)) == "ST_".strtoupper($obj->code)) ? $obj->label : $langs->trans("ST_".strtoupper($obj->code)), 'picto' => $obj->picto);
			$i++;
		}
		return 1;
	}

	/**
	 *	Return prospect level
	 *
	 *  @return     string        Label
	 */
	public function getLibProspLevel()
	{
		return $this->libProspLevel($this->fk_prospectlevel);
	}

	/**
	 *  Return label of prospect level
	 *
	 *  @param	string	$fk_prospectlevel   	Prospect level
	 *  @return string        					label of level
	 */
	public function libProspLevel($fk_prospectlevel)
	{
		global $langs;

		$lib = $langs->trans("ProspectLevel".$fk_prospectlevel);
		// If lib not found in language file, we get label from cache/database
		if ($lib == "ProspectLevel".$fk_prospectlevel) {
			$lib = $langs->getLabelFromKey($this->db, $fk_prospectlevel, 'c_prospectlevel', 'code', 'label');
		}
		return $lib;
	}


	/**
	 *  Set prospect level
	 *
	 *  @param  User	$user		User who defines the discount
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 * @deprecated Use update function instead
	 */
	public function setProspectLevel(User $user)
	{
		return $this->update($this->id, $user);
	}

	/**
	 *  Return status of prospect
	 *
	 *  @param	int		$mode       0=label long, 1=label short, 2=Picto + Label short, 3=Picto, 4=Picto + Label long
	 *  @param	string	$label		Label to use for status for added status
	 *  @return string        		Label
	 */
	public function getLibProspCommStatut($mode = 0, $label = '')
	{
		return $this->libProspCommStatut($this->stcomm_id, $mode, $label, $this->stcomm_picto);
	}

	/**
	 *  Return label of a given status
	 *
	 *  @param	int|string	$statut        	Id or code for prospection status
	 *  @param  int			$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *  @param	string		$label			Label to use for status for added status
	 *	@param 	string		$picto      	Name of image file to show ('filenew', ...)
	 *                                      If no extension provided, we use '.png'. Image must be stored into theme/xxx/img directory.
	 *                                      Example: picto.png                  if picto.png is stored into htdocs/theme/mytheme/img
	 *                                      Example: picto.png@mymodule         if picto.png is stored into htdocs/mymodule/img
	 *                                      Example: /mydir/mysubdir/picto.png  if picto.png is stored into htdocs/mydir/mysubdir (pictoisfullpath must be set to 1)
	 *  @return string       	 			Label of status
	 */
	public function libProspCommStatut($statut, $mode = 0, $label = '', $picto = '')
	{
		global $langs;
		$langs->load('customers');

		if ($mode == 2) {
			if ($statut == '-1' || $statut == 'ST_NO') {
				return img_action($langs->trans("StatusProspect-1"), '-1', $picto).' '.$langs->trans("StatusProspect-1");
			} elseif ($statut == '0' || $statut == 'ST_NEVER') {
				return img_action($langs->trans("StatusProspect0"), '0', $picto).' '.$langs->trans("StatusProspect0");
			} elseif ($statut == '1' || $statut == 'ST_TODO') {
				return img_action($langs->trans("StatusProspect1"), '1', $picto).' '.$langs->trans("StatusProspect1");
			} elseif ($statut == '2' || $statut == 'ST_PEND') {
				return img_action($langs->trans("StatusProspect2"), '2', $picto).' '.$langs->trans("StatusProspect2");
			} elseif ($statut == '3' || $statut == 'ST_DONE') {
				return img_action($langs->trans("StatusProspect3"), '3', $picto).' '.$langs->trans("StatusProspect3");
			} else {
				return img_action(($langs->trans("StatusProspect".$statut) != "StatusProspect".$statut) ? $langs->trans("StatusProspect".$statut) : $label, '0', $picto).' '.(($langs->trans("StatusProspect".$statut) != "StatusProspect".$statut) ? $langs->trans("StatusProspect".$statut) : $label);
			}
		}
		if ($mode == 3) {
			if ($statut == '-1' || $statut == 'ST_NO') {
				return img_action($langs->trans("StatusProspect-1"), '-1', $picto);
			} elseif ($statut == '0' || $statut == 'ST_NEVER') {
				return img_action($langs->trans("StatusProspect0"), '0', $picto);
			} elseif ($statut == '1' || $statut == 'ST_TODO') {
				return img_action($langs->trans("StatusProspect1"), '1', $picto);
			} elseif ($statut == '2' || $statut == 'ST_PEND') {
				return img_action($langs->trans("StatusProspect2"), '2', $picto);
			} elseif ($statut == '3' || $statut == 'ST_DONE') {
				return img_action($langs->trans("StatusProspect3"), '3', $picto);
			} else {
				return img_action(($langs->trans("StatusProspect".$statut) != "StatusProspect".$statut) ? $langs->trans("StatusProspect".$statut) : $label, '0', $picto);
			}
		}
		if ($mode == 4) {
			if ($statut == '-1' || $statut == 'ST_NO') {
				return img_action($langs->trans("StatusProspect-1"), '-1', $picto).' '.$langs->trans("StatusProspect-1");
			} elseif ($statut == '0' || $statut == 'ST_NEVER') {
				return img_action($langs->trans("StatusProspect0"), '0', $picto).' '.$langs->trans("StatusProspect0");
			} elseif ($statut == '1' || $statut == 'ST_TODO') {
				return img_action($langs->trans("StatusProspect1"), '1', $picto).' '.$langs->trans("StatusProspect1");
			} elseif ($statut == '2' || $statut == 'ST_PEND') {
				return img_action($langs->trans("StatusProspect2"), '2', $picto).' '.$langs->trans("StatusProspect2");
			} elseif ($statut == '3' || $statut == 'ST_DONE') {
				return img_action($langs->trans("StatusProspect3"), '3', $picto).' '.$langs->trans("StatusProspect3");
			} else {
				return img_action(($langs->trans("StatusProspect".$statut) != "StatusProspect".$statut) ? $langs->trans("StatusProspect".$statut) : $label, '0', $picto).' '.(($langs->trans("StatusProspect".$statut) != "StatusProspect".$statut) ? $langs->trans("StatusProspect".$statut) : $label);
			}
		}

		return "Error, mode/status not found";
	}


	/**
	 *  Set "blacklist" mailing status
	 *
	 *  @param	int		$no_email	1=Do not send mailing, 0=Ok to receive mailing
	 *  @return int					Return integer <0 if KO, >0 if OK
	 */
	public function setNoEmail($no_email)
	{
		$error = 0;

		// Update mass emailing flag into table mailing_unsubscribe
		if ($this->email) {
			$this->db->begin();

			if ($no_email) {
				$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."mailing_unsubscribe WHERE entity IN (".getEntity('mailing', 0).") AND email = '".$this->db->escape($this->email)."'";
				$resql = $this->db->query($sql);
				if ($resql) {
					$obj = $this->db->fetch_object($resql);
					$noemail = $obj->nb;
					if (empty($noemail)) {
						$sql = "INSERT INTO ".MAIN_DB_PREFIX."mailing_unsubscribe(email, entity, date_creat) VALUES ('".$this->db->escape($this->email)."', ".getEntity('mailing', 0).", '".$this->db->idate(dol_now())."')";
						$resql = $this->db->query($sql);
						if (!$resql) {
							$error++;
							$this->error = $this->db->lasterror();
							$this->errors[] = $this->error;
						}
					}
				} else {
					$error++;
					$this->error = $this->db->lasterror();
					$this->errors[] = $this->error;
				}
			} else {
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."mailing_unsubscribe WHERE email = '".$this->db->escape($this->email)."' AND entity IN (".getEntity('mailing', 0).")";
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
					$this->errors[] = $this->error;
				}
			}

			if (empty($error)) {
				$this->no_email = $no_email;
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return $error * -1;
			}
		}

		return 0;
	}

	/**
	 *  get "blacklist" mailing status
	 * 	set no_email attribute to 1 or 0
	 *
	 *  @return int					Return integer <0 if KO, >0 if OK
	 */
	public function getNoEmail()
	{
		if ($this->email) {
			$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."mailing_unsubscribe WHERE entity IN (".getEntity('mailing').") AND email = '".$this->db->escape($this->email)."'";
			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				$this->no_email = $obj->nb;
				return 1;
			} else {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				return -1;
			}
		}
		return 0;
	}


	/**
	 *	Return clickable link of object (with eventually picto)
	 *
	 *	@param      string	    			$option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		?array<string,mixed>	$arraydata				Array of data
	 *  @return		string											HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		if (!is_null($this->photo)) {
			$return .= Form::showphoto('contact', $this, 0, 60, 0, 'photokanban photoref photowithmargin photologintooltip', 'small', 0, 1);
		} else {
			$return .= img_picto('', $this->picto);
		}
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<div class="info-box-ref inline-block tdoverflowmax150 valignmiddle">' . $this->getNomUrl(0) . '</div>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (is_object($this->thirdparty)) {
			$return .= '<div class="info-box-ref tdoverflowmax150">'.$this->thirdparty->getNomUrl(1).'</div>';
		}
		/*if (property_exists($this, 'phone_pro') && !empty($this->phone_pro)) {
			$return .= '<br>'.img_picto($langs->trans("Phone"), 'phone');
			$return .= ' <span class="info-box-label">'.$this->phone_pro.'</span>';
		}*/
		/*if (method_exists($this, 'LibPubPriv')) {
			$return .= '<br><span class="info-box-label opacitymedium">'.$langs->trans("Visibility").'</span>';
			$return .= '<span> : '.$this->LibPubPriv($this->priv).'</span>';
		}*/
		$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3).'</div>';
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}
}
