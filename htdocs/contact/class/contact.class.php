<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Benoit Mortier              <benoit.mortier@opensides.be>
 * Copyright (C) 2004-2013 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2007      Franky Van Liedekerke       <franky.van.liedekerker@telenet.be>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2013      Florian Henry		  	   <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Alexandre Spangaro 	       <aspangaro@open-dsi.fr>
 * Copyright (C) 2013      Juanjo Menent	 	       <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García               <marcosgdf@gmail.com>
 * Copyright (C) 2019      Nicolas ZABOURI 	           <info@inovea-conseil.com>
 * Copyright (C) 2020      Open-Dsi  	               <support@open-dsi.fr>
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


/**
 *	Class to manage contact/addresses
 */
class Contact extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'contact';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'socpeople';

	/**
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'contact';

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
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' =>array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>10),
		'datec' =>array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>-1, 'position'=>15),
		'tms' =>array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>20),
		'fk_soc' =>array('type'=>'integer', 'label'=>'ThirdParty', 'enabled'=>1, 'visible'=>1, 'position'=>25, 'searchall'=>1),
		'entity' =>array('type'=>'integer', 'label'=>'Entity', 'default'=>1, 'enabled'=>1, 'visible'=>3, 'notnull'=>1, 'position'=>30, 'index'=>1),
		'ref_ext' =>array('type'=>'varchar(255)', 'label'=>'Ref ext', 'enabled'=>1, 'visible'=>3, 'position'=>35),
		'civility' =>array('type'=>'varchar(6)', 'label'=>'Civility', 'enabled'=>1, 'visible'=>3, 'position'=>40),
		'lastname' =>array('type'=>'varchar(50)', 'label'=>'Lastname', 'enabled'=>1, 'visible'=>1, 'position'=>45, 'showoncombobox'=>1, 'searchall'=>1),
		'firstname' =>array('type'=>'varchar(50)', 'label'=>'Firstname', 'enabled'=>1, 'visible'=>1, 'position'=>50, 'showoncombobox'=>1, 'searchall'=>1),
		'address' =>array('type'=>'varchar(255)', 'label'=>'Address', 'enabled'=>1, 'visible'=>-1, 'position'=>55),
		'zip' =>array('type'=>'varchar(25)', 'label'=>'Zip', 'enabled'=>1, 'visible'=>1, 'position'=>60),
		'town' =>array('type'=>'text', 'label'=>'Town', 'enabled'=>1, 'visible'=>1, 'position'=>65),
		'fk_departement' =>array('type'=>'integer', 'label'=>'Fk departement', 'enabled'=>1, 'visible'=>3, 'position'=>70),
		'fk_pays' =>array('type'=>'integer', 'label'=>'Fk pays', 'enabled'=>1, 'visible'=>3, 'position'=>75),
		'birthday' =>array('type'=>'date', 'label'=>'Birthday', 'enabled'=>1, 'visible'=>3, 'position'=>80),
		'poste' =>array('type'=>'varchar(80)', 'label'=>'PostOrFunction', 'enabled'=>1, 'visible'=>-1, 'position'=>85),
		'phone' =>array('type'=>'varchar(30)', 'label'=>'Phone', 'enabled'=>1, 'visible'=>1, 'position'=>90, 'searchall'=>1),
		'phone_perso' =>array('type'=>'varchar(30)', 'label'=>'PhonePerso', 'enabled'=>1, 'visible'=>1, 'position'=>95, 'searchall'=>1),
		'phone_mobile' =>array('type'=>'varchar(30)', 'label'=>'PhoneMobile', 'enabled'=>1, 'visible'=>1, 'position'=>100, 'searchall'=>1),
		'fax' =>array('type'=>'varchar(30)', 'label'=>'Fax', 'enabled'=>1, 'visible'=>1, 'position'=>105, 'searchall'=>1),
		'email' =>array('type'=>'varchar(255)', 'label'=>'Email', 'enabled'=>1, 'visible'=>1, 'position'=>110, 'searchall'=>1),
		'socialnetworks' =>array('type'=>'text', 'label'=>'SocialNetworks', 'enabled'=>1, 'visible'=>3, 'position'=>115),
		'photo' =>array('type'=>'varchar(255)', 'label'=>'Photo', 'enabled'=>1, 'visible'=>3, 'position'=>170),
		'priv' =>array('type'=>'smallint(6)', 'label'=>'ContactVisibility', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'position'=>175),
		'fk_stcommcontact' =>array('type'=>'integer', 'label'=>'Fk stcommcontact', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>220),
		'fk_prospectlevel' =>array('type'=>'varchar(12)', 'label'=>'ProspectLevel', 'enabled'=>1, 'visible'=>-1, 'position'=>255),
		'no_email' =>array('type'=>'smallint(6)', 'label'=>'No_Email', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>180),
		'fk_user_creat' =>array('type'=>'integer', 'label'=>'UserAuthor', 'enabled'=>1, 'visible'=>3, 'position'=>185),
		'fk_user_modif' =>array('type'=>'integer', 'label'=>'UserModif', 'enabled'=>1, 'visible'=>3, 'position'=>190),
		'note_private' =>array('type'=>'text', 'label'=>'NotePrivate', 'enabled'=>1, 'visible'=>3, 'position'=>195, 'searchall'=>1),
		'note_public' =>array('type'=>'text', 'label'=>'NotePublic', 'enabled'=>1, 'visible'=>3, 'position'=>200, 'searchall'=>1),
		'default_lang' =>array('type'=>'varchar(6)', 'label'=>'Default lang', 'enabled'=>1, 'visible'=>3, 'position'=>205),
		'canvas' =>array('type'=>'varchar(32)', 'label'=>'Canvas', 'enabled'=>1, 'visible'=>3, 'position'=>210),
		'statut' =>array('type'=>'tinyint(4)', 'label'=>'Status', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'position'=>500),
		'import_key' =>array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'visible'=>-1, 'position'=>1000),
	);

	public $civility_id; // In fact we store civility_code
	public $civility_code;
	public $civility;

	/**
	 * @var string Address
	 */
	public $address;

	/**
	 * @var string zip code
	 */
	public $zip;

	/**
	 * @var string Town
	 */
	public $town;

	public $state_id; // Id of department
	public $state_code; // Code of department
	public $state; // Label of department

	public $poste; // Position

	public $socid; // fk_soc
	public $statut; // 0=inactif, 1=actif

	public $code;

	/**
	 * Email
	 * @var string
	 */
	public $email;

	/**
	 * Unsuscribe all : 1 = contact has globaly unsubscribe of all mass emailings
	 * @var int
	 */
	public $no_email;

	/**
	 * @var array array of socialnetworks
	 */
	public $socialnetworks;

	/**
	 * Skype username
	 * @var string
	 * @deprecated
	 */
	public $skype;

	/**
	 * Twitter username
	 * @var string
	 * @deprecated
	 */
	public $twitter;

	 /**
	  * Facebook username
	  * @var string
	  * @deprecated
	  */
	public $facebook;

	 /**
	  * Linkedin username
	  * @var string
	  * @deprecated
	  */
	public $linkedin;

	/**
	 * Jabber username
	 * @var string
	 * @deprecated
	 */
	public $jabberid;

	/**
	 * @var string filename for photo
	 */
	public $photo;

	/**
	 * @var string phone pro
	 */
	public $phone_pro;

	/**
	 * @var string phone perso
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

	public $birthday;
	public $default_lang;

	public $ref_facturation; // Reference number of invoice for which it is contact
	public $ref_contrat; // Nb de reference contrat pour lequel il est contact
	public $ref_commande; // Nb de reference commande pour lequel il est contact
	public $ref_propal; // Nb de reference propal pour lequel il est contact

	public $user_id;
	public $user_login;

	// END MODULEBUILDER PROPERTIES


	/**
	 * Old copy
	 * @var Contact
	 */
	public $oldcopy; // To contains a clone of this when we need to save old properties of object

	public $roles = null;

	public $cacheprospectstatus = array();
	public $fk_prospectlevel;
	public $stcomm_id;
	public $statut_commercial;
	public $stcomm_picto;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;
		$this->statut = 1; // By default, status is enabled

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID)) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (empty($conf->mailing->enabled)) {
			$this->fields['no_email']['enabled'] = 0;
		}
		// typical ['s.nom'] is used for third-parties
		if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
			$this->fields['fk_soc']['enabled'] = 0;
			$this->fields['fk_soc']['searchall'] = 0;
		}

		if (empty($conf->global->THIRDPARTY_ENABLE_PROSPECTION_ON_ALTERNATIVE_ADRESSES)) {	// Default behaviour
			$this->field['fk_stcommcontact']['enabled'] = 0;
			$this->field['fk_prospectcontactlevel']['enabled'] = 0;
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

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Load indicators into this->nb for board
	 *
	 *  @return     int         <0 if KO, >0 if OK
	 */
	public function load_state_board()
	{
		// phpcs:enable
		global $user;

		$this->nb = array();
		$clause = "WHERE";

		$sql = "SELECT count(sp.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as sp";
		if (!$user->rights->societe->client->voir && !$user->socid)
		{
			$sql .= ", ".MAIN_DB_PREFIX."societe as s";
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql .= " WHERE sp.fk_soc = s.rowid AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
			$clause = "AND";
		}
		$sql .= ' '.$clause.' sp.entity IN ('.getEntity($this->element).')';
		$sql .= " AND (sp.priv='0' OR (sp.priv='1' AND sp.fk_user_creat=".$user->id."))";
		if ($user->socid > 0) $sql .= " AND sp.fk_soc = ".$user->socid;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj = $this->db->fetch_object($resql))
			{
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
	 *  @param      User	$user       Object user that create
	 *  @return     int      			<0 if KO, >0 if OK
	 */
	public function create($user)
	{
		global $conf, $langs;

		$error = 0;
		$now = dol_now();

		$this->db->begin();

		// Clean parameters
		$this->lastname = $this->lastname ?trim($this->lastname) : trim($this->name);
		$this->firstname = trim($this->firstname);
		$this->setUpperOrLowerCase();
		if (empty($this->socid)) $this->socid = 0;
		if (empty($this->priv)) $this->priv = 0;
		if (empty($this->statut)) $this->statut = 0; // This is to convert '' into '0' to avoid bad sql request

		$this->entity = ((isset($this->entity) && is_numeric($this->entity)) ? $this->entity : $conf->entity);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."socpeople (";
		$sql .= " datec";
		$sql .= ", fk_soc";
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
		$sql .= ") VALUES (";
		$sql .= "'".$this->db->idate($now)."',";
		if ($this->socid > 0) $sql .= " ".$this->db->escape($this->socid).",";
		else $sql .= "null,";
		$sql .= "'".$this->db->escape($this->lastname)."',";
		$sql .= "'".$this->db->escape($this->firstname)."',";
		$sql .= " ".($user->id > 0 ? "'".$this->db->escape($user->id)."'" : "null").",";
		$sql .= " ".$this->db->escape($this->priv).",";
		$sql .= " 0,";
		$sql .= " ".$this->db->escape($this->statut).",";
		$sql .= " ".(!empty($this->canvas) ? "'".$this->db->escape($this->canvas)."'" : "null").",";
		$sql .= " ".$this->db->escape($this->entity).",";
		$sql .= "'".$this->db->escape($this->ref_ext)."',";
		$sql .= " ".(!empty($this->import_key) ? "'".$this->db->escape($this->import_key)."'" : "null");
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."socpeople");

			if (!$error)
			{
				$result = $this->update($this->id, $user, 1, 'add'); // This include updateRoles(), ...
				if ($result < 0)
				{
					$error++;
					$this->error = $this->db->lasterror();
				}
			}

			if (!$error)
			{
				$result = $this->update_perso($this->id, $user, 1); // TODO Remove function update_perso, should be same than update
				if ($result < 0)
				{
					$error++;
					$this->error = $this->db->lasterror();
				}
			}

			if (!$error)
			{
				// Call trigger
				$result = $this->call_trigger('CONTACT_CREATE', $user);
				if ($result < 0) { $error++; }
				// End call triggers
			}

			if (!$error)
			{
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
	 *      Update informations into database
	 *
	 *      @param      int		$id          	Id of contact/address to update
	 *      @param      User	$user        	Objet user making change
	 *      @param      int		$notrigger	    0=no, 1=yes
	 *      @param		string	$action			Current action for hookmanager
	 *      @param		int		$nosyncuser		No sync linked user (external users and contacts are linked)
	 *      @return     int      			   	<0 if KO, >0 if OK
	 */
	public function update($id, $user = null, $notrigger = 0, $action = 'update', $nosyncuser = 0)
	{
		global $conf, $langs, $hookmanager;

		$error = 0;

		$this->id = $id;

		$this->entity = ((isset($this->entity) && is_numeric($this->entity)) ? $this->entity : $conf->entity);

		// Clean parameters
		$this->lastname = trim($this->lastname) ?trim($this->lastname) : trim($this->lastname);
		$this->firstname = trim($this->firstname);
		$this->email = trim($this->email);
		$this->phone_pro = trim($this->phone_pro);
		$this->phone_perso = trim($this->phone_perso);
		$this->phone_mobile = trim($this->phone_mobile);
		$this->jabberid = trim($this->jabberid);
		$this->skype = trim($this->skype);
		$this->photo = trim($this->photo);
		$this->fax = trim($this->fax);
		$this->zip = (empty($this->zip) ? '' : trim($this->zip));
		$this->town = (empty($this->town) ? '' : trim($this->town));
		$this->setUpperOrLowerCase();
		$this->country_id = ($this->country_id > 0 ? $this->country_id : $this->country_id);
		if (empty($this->statut)) $this->statut = 0;
		if (empty($this->civility_code) && !is_numeric($this->civility_id)) $this->civility_code = $this->civility_id; // For backward compatibility
		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET ";
		if ($this->socid > 0) $sql .= " fk_soc='".$this->db->escape($this->socid)."',";
		elseif ($this->socid == -1) $sql .= " fk_soc=null,";
		$sql .= "  civility='".$this->db->escape($this->civility_code)."'";
		$sql .= ", lastname='".$this->db->escape($this->lastname)."'";
		$sql .= ", firstname='".$this->db->escape($this->firstname)."'";
		$sql .= ", address='".$this->db->escape($this->address)."'";
		$sql .= ", zip='".$this->db->escape($this->zip)."'";
		$sql .= ", town='".$this->db->escape($this->town)."'";
		$sql .= ", fk_pays=".($this->country_id > 0 ? $this->country_id : 'NULL');
		$sql .= ", fk_departement=".($this->state_id > 0 ? $this->state_id : 'NULL');
		$sql .= ", poste='".$this->db->escape($this->poste)."'";
		$sql .= ", fax='".$this->db->escape($this->fax)."'";
		$sql .= ", email='".$this->db->escape($this->email)."'";
		$sql .= ", socialnetworks = '".$this->db->escape(json_encode($this->socialnetworks))."'";
		$sql .= ", photo='".$this->db->escape($this->photo)."'";
		$sql .= ", birthday=".($this->birthday ? "'".$this->db->idate($this->birthday)."'" : "null");
		$sql .= ", note_private = ".(isset($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "null");
		$sql .= ", note_public = ".(isset($this->note_public) ? "'".$this->db->escape($this->note_public)."'" : "null");
		$sql .= ", phone = ".(isset($this->phone_pro) ? "'".$this->db->escape($this->phone_pro)."'" : "null");
		$sql .= ", phone_perso = ".(isset($this->phone_perso) ? "'".$this->db->escape($this->phone_perso)."'" : "null");
		$sql .= ", phone_mobile = ".(isset($this->phone_mobile) ? "'".$this->db->escape($this->phone_mobile)."'" : "null");
		$sql .= ", priv = '".$this->db->escape($this->priv)."'";
		$sql .= ", fk_prospectcontactlevel = '".$this->db->escape($this->fk_prospectlevel)."'";
		if (isset($this->stcomm_id))
		{
			$sql .= ", fk_stcommcontact = ".($this->stcomm_id > 0 || $this->stcomm_id == -1 ? $this->stcomm_id : "0");
		}
		$sql .= ", statut = ".$this->db->escape($this->statut);
		$sql .= ", fk_user_modif=".($user->id > 0 ? "'".$this->db->escape($user->id)."'" : "NULL");
		$sql .= ", default_lang=".($this->default_lang ? "'".$this->db->escape($this->default_lang)."'" : "NULL");
		$sql .= ", entity = ".$this->db->escape($this->entity);
		$sql .= " WHERE rowid=".$this->db->escape($id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			unset($this->country_code);
			unset($this->country);
			unset($this->state_code);
			unset($this->state);

			$action = 'update';

			// Actions on extra fields
			if (!$error)
			{
				$result = $this->insertExtraFields();
				if ($result < 0)
				{
					$error++;
				}
			}

			if (!$error) {
				$result = $this->updateRoles();
				if ($result < 0)
				{
					$error++;
				}
			}

			if (!$error && $this->user_id > 0)
			{
				// If contact is linked to a user
				$tmpobj = new User($this->db);
				$tmpobj->fetch($this->user_id);
				$usermustbemodified = 0;
				if ($tmpobj->office_phone != $this->phone_pro)
				{
					$tmpobj->office_phone = $this->phone_pro;
					$usermustbemodified++;
				}
				if ($tmpobj->office_fax != $this->fax)
				{
					$tmpobj->office_fax = $this->fax;
					$usermustbemodified++;
				}
				if ($tmpobj->address != $this->address)
				{
					$tmpobj->address = $this->address;
					$usermustbemodified++;
				}
				if ($tmpobj->town != $this->town)
				{
					$tmpobj->town = $this->town;
					$usermustbemodified++;
				}
				if ($tmpobj->zip != $this->zip)
				{
					$tmpobj->zip = $this->zip;
					$usermustbemodified++;
				}
				if ($tmpobj->zip != $this->zip)
				{
					$tmpobj->state_id = $this->state_id;
					$usermustbemodified++;
				}
				if ($tmpobj->country_id != $this->country_id)
				{
					$tmpobj->country_id = $this->country_id;
					$usermustbemodified++;
				}
				if ($tmpobj->email != $this->email)
				{
					$tmpobj->email = $this->email;
					$usermustbemodified++;
				}
				if (!empty(array_diff($tmpobj->socialnetworks, $this->socialnetworks)))
				{
					$tmpobj->socialnetworks = $this->socialnetworks;
					$usermustbemodified++;
				}
				// if ($tmpobj->skype != $this->skype)
				// {
				// 	$tmpobj->skype = $this->skype;
				// 	$usermustbemodified++;
				// }
				// if ($tmpobj->twitter != $this->twitter)
				// {
				// 	$tmpobj->twitter = $this->twitter;
				// 	$usermustbemodified++;
				// }
				// if ($tmpobj->facebook != $this->facebook)
				// {
				// 	$tmpobj->facebook = $this->facebook;
				// 	$usermustbemodified++;
				// }
				// if ($tmpobj->linkedin != $this->linkedin)
				// {
				//     $tmpobj->linkedin = $this->linkedin;
				//     $usermustbemodified++;
				// }
				if ($usermustbemodified)
				{
					$result = $tmpobj->update($user, 0, 1, 1, 1);
					if ($result < 0) { $error++; }
				}
			}

			if (!$error && !$notrigger)
			{
				// Call trigger
				$result = $this->call_trigger('CONTACT_MODIFY', $user);
				if ($result < 0) { $error++; }
				// End call triggers
			}

			if (!$error)
			{
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
	 *	Retourne chaine DN complete dans l'annuaire LDAP pour l'objet
	 *
	 *	@param		array	$info		Info string loaded by _load_ldap_info
	 *	@param		int		$mode		0=Return full DN (uid=qqq,ou=xxx,dc=aaa,dc=bbb)
	 *									1=Return DN without key inside (ou=xxx,dc=aaa,dc=bbb)
	 *									2=Return key only (uid=qqq)
	 *	@return		string				DN
	 */
	public function _load_ldap_dn($info, $mode = 0)
	{
		// phpcs:enable
		global $conf;
		$dn = '';
		if ($mode == 0) $dn = $conf->global->LDAP_KEY_CONTACTS."=".$info[$conf->global->LDAP_KEY_CONTACTS].",".$conf->global->LDAP_CONTACT_DN;
		elseif ($mode == 1) $dn = $conf->global->LDAP_CONTACT_DN;
		elseif ($mode == 2) $dn = $conf->global->LDAP_KEY_CONTACTS."=".$info[$conf->global->LDAP_KEY_CONTACTS];
		return $dn;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *	Initialise tableau info (tableau des attributs LDAP)
	 *
	 *	@return		array		Tableau info des attributs
	 */
	public function _load_ldap_info()
	{
		// phpcs:enable
		global $conf, $langs;

		$info = array();

		// Object classes
		$info["objectclass"] = explode(',', $conf->global->LDAP_CONTACT_OBJECT_CLASS);

		$this->fullname = $this->getFullName($langs);

		// Fields
		if ($this->fullname && !empty($conf->global->LDAP_CONTACT_FIELD_FULLNAME)) $info[$conf->global->LDAP_CONTACT_FIELD_FULLNAME] = $this->fullname;
		if ($this->lastname && !empty($conf->global->LDAP_CONTACT_FIELD_NAME)) $info[$conf->global->LDAP_CONTACT_FIELD_NAME] = $this->lastname;
		if ($this->firstname && !empty($conf->global->LDAP_CONTACT_FIELD_FIRSTNAME)) $info[$conf->global->LDAP_CONTACT_FIELD_FIRSTNAME] = $this->firstname;

		if ($this->poste) $info["title"] = $this->poste;
		if ($this->socid > 0)
		{
			$soc = new Societe($this->db);
			$soc->fetch($this->socid);

			$info[$conf->global->LDAP_CONTACT_FIELD_COMPANY] = $soc->name;
			if ($soc->client == 1)      $info["businessCategory"] = "Customers";
			if ($soc->client == 2)      $info["businessCategory"] = "Prospects";
			if ($soc->fournisseur == 1) $info["businessCategory"] = "Suppliers";
		}
		if ($this->address && !empty($conf->global->LDAP_CONTACT_FIELD_ADDRESS)) $info[$conf->global->LDAP_CONTACT_FIELD_ADDRESS] = $this->address;
		if ($this->zip && !empty($conf->global->LDAP_CONTACT_FIELD_ZIP))          $info[$conf->global->LDAP_CONTACT_FIELD_ZIP] = $this->zip;
		if ($this->town && !empty($conf->global->LDAP_CONTACT_FIELD_TOWN))      $info[$conf->global->LDAP_CONTACT_FIELD_TOWN] = $this->town;
		if ($this->country_code && !empty($conf->global->LDAP_CONTACT_FIELD_COUNTRY))      $info[$conf->global->LDAP_CONTACT_FIELD_COUNTRY] = $this->country_code;
		if ($this->phone_pro && !empty($conf->global->LDAP_CONTACT_FIELD_PHONE)) $info[$conf->global->LDAP_CONTACT_FIELD_PHONE] = $this->phone_pro;
		if ($this->phone_perso && !empty($conf->global->LDAP_CONTACT_FIELD_HOMEPHONE)) $info[$conf->global->LDAP_CONTACT_FIELD_HOMEPHONE] = $this->phone_perso;
		if ($this->phone_mobile && !empty($conf->global->LDAP_CONTACT_FIELD_MOBILE)) $info[$conf->global->LDAP_CONTACT_FIELD_MOBILE] = $this->phone_mobile;
		if ($this->fax && !empty($conf->global->LDAP_CONTACT_FIELD_FAX))	    $info[$conf->global->LDAP_CONTACT_FIELD_FAX] = $this->fax;
		if ($this->skype && !empty($conf->global->LDAP_CONTACT_FIELD_SKYPE))	    $info[$conf->global->LDAP_CONTACT_FIELD_SKYPE] = $this->skype;
		if ($this->note_private && !empty($conf->global->LDAP_CONTACT_FIELD_DESCRIPTION)) $info[$conf->global->LDAP_CONTACT_FIELD_DESCRIPTION] = dol_string_nohtmltag($this->note_private, 2);
		if ($this->email && !empty($conf->global->LDAP_CONTACT_FIELD_MAIL))     $info[$conf->global->LDAP_CONTACT_FIELD_MAIL] = $this->email;

		if ($conf->global->LDAP_SERVER_TYPE == 'egroupware')
		{
			$info["objectclass"][4] = "phpgwContact"; // compatibilite egroupware

			$info['uidnumber'] = $this->id;

			$info['phpgwTz'] = 0;
			$info['phpgwMailType'] = 'INTERNET';
			$info['phpgwMailHomeType'] = 'INTERNET';

			$info["phpgwContactTypeId"] = 'n';
			$info["phpgwContactCatId"] = 0;
			$info["phpgwContactAccess"] = "public";

			if (dol_strlen($this->egroupware_id) == 0)
			{
				$this->egroupware_id = 1;
			}

			$info["phpgwContactOwner"] = $this->egroupware_id;

			if ($this->email) $info["rfc822Mailbox"] = $this->email;
			if ($this->phone_mobile) $info["phpgwCellTelephoneNumber"] = $this->phone_mobile;
		}

		return $info;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Update field alert birthday
	 *
	 *  @param      int			$id         Id of contact
	 *  @param      User		$user		User asking to change alert or birthday
	 *  @param      int		    $notrigger	0=no, 1=yes
	 *  @return     int         			<0 if KO, >=0 if OK
	 */
	public function update_perso($id, $user = null, $notrigger = 0)
	{
		// phpcs:enable
		$error = 0;
		$result = false;

		$this->db->begin();

		// Mis a jour contact
		$sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET";
		$sql .= " birthday=".($this->birthday ? "'".$this->db->idate($this->birthday)."'" : "null");
		$sql .= ", photo = ".($this->photo ? "'".$this->db->escape($this->photo)."'" : "null");
		if ($user) $sql .= ", fk_user_modif=".$user->id;
		$sql .= " WHERE rowid=".$this->db->escape($id);

		dol_syslog(get_class($this)."::update_perso this->birthday=".$this->birthday." -", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql)
		{
			$error++;
			$this->error = $this->db->lasterror();
		}

		// Mis a jour alerte birthday
		if (!empty($this->birthday_alert)) {
			//check existing
			$sql_check = "SELECT rowid FROM ".MAIN_DB_PREFIX."user_alert WHERE type=1 AND fk_contact=".$this->db->escape($id)." AND fk_user=".$user->id;
			$result_check = $this->db->query($sql_check);
			if (!$result_check || ($this->db->num_rows($result_check) < 1)) {
				//insert
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_alert(type,fk_contact,fk_user) ";
				$sql .= "VALUES (1,".$this->db->escape($id).",".$user->id.")";
				$result = $this->db->query($sql);
				if (!$result) {
					$error++;
					$this->error = $this->db->lasterror();
				}
			} else {
				$result = true;
			}
		} else {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_alert ";
			$sql .= "WHERE type=1 AND fk_contact=".$this->db->escape($id)." AND fk_user=".$user->id;
			$result = $this->db->query($sql);
			if (!$result) {
				$error++;
				$this->error = $this->db->lasterror();
			}
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('CONTACT_MODIFY', $user);
			if ($result < 0) { $error++; }
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
	}


	/**
	 *  Load object contact.
	 *
	 *  @param      int		$id         	Id of contact
	 *  @param      User	$user       	Load also alerts of this user (subscribing to alerts) that want alerts about this contact
	 *  @param      string  $ref_ext    	External reference, not given by Dolibarr
	 *  @param		string	$email			Email
	 *  @param		int		$loadalsoroles	Load also roles. Try to always 0 here and load roles with a separate call of fetchRoles().
	 *  @return     int     		    	>0 if OK, <0 if KO or if two records found for same ref or idprof, 0 if not found.
	 */
	public function fetch($id, $user = null, $ref_ext = '', $email = '', $loadalsoroles = 0)
	{
		global $langs;

		dol_syslog(get_class($this)."::fetch id=".$id." ref_ext=".$ref_ext." email=".$email, LOG_DEBUG);

		if (empty($id) && empty($ref_ext) && empty($email))
		{
			$this->error = 'BadParameter';
			return -1;
		}

		$langs->loadLangs(array("dict", "companies"));

		$sql = "SELECT c.rowid, c.entity, c.fk_soc, c.ref_ext, c.civility as civility_code, c.lastname, c.firstname,";
		$sql .= " c.address, c.statut, c.zip, c.town,";
		$sql .= " c.fk_pays as country_id,";
		$sql .= " c.fk_departement as state_id,";
		$sql .= " c.birthday,";
		$sql .= " c.poste, c.phone, c.phone_perso, c.phone_mobile, c.fax, c.email,";
		$sql .= " c.socialnetworks,";
		$sql .= " c.photo,";
		$sql .= " c.priv, c.note_private, c.note_public, c.default_lang, c.canvas,";
		$sql .= " c.fk_prospectcontactlevel, c.fk_stcommcontact, st.libelle as stcomm, st.picto as stcomm_picto,";
		$sql .= " c.import_key,";
		$sql .= " c.datec as date_creation, c.tms as date_modification,";
		$sql .= " co.label as country, co.code as country_code,";
		$sql .= " d.nom as state, d.code_departement as state_code,";
		$sql .= " u.rowid as user_id, u.login as user_login,";
		$sql .= " s.nom as socname, s.address as socaddress, s.zip as soccp, s.town as soccity, s.default_lang as socdefault_lang";
		$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as co ON c.fk_pays = co.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d ON c.fk_departement = d.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON c.rowid = u.fk_socpeople";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON c.fk_soc = s.rowid";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_stcommcontact as st ON c.fk_stcommcontact = st.id';
		if ($id) $sql .= " WHERE c.rowid = ".((int) $id);
		else {
			$sql .= " WHERE c.entity IN (".getEntity($this->element).")";
			if ($ref_ext) {
				$sql .= " AND c.ref_ext = '".$this->db->escape($ref_ext)."'";
			}
			if ($email) {
				$sql .= " AND c.email = '".$this->db->escape($email)."'";
			}
		}

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num > 1)
			{
				$this->error = 'Fetch found several records. Rename one of contact to avoid duplicate.';
				dol_syslog($this->error, LOG_ERR);

				return 2;
			} elseif ($num)   // $num = 1
			{
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->entity = $obj->entity;
				$this->ref = $obj->rowid;
				$this->ref_ext = $obj->ref_ext;

				$this->civility_code    = $obj->civility_code;
				$this->civility	        = $obj->civility_code ? ($langs->trans("Civility".$obj->civility_code) != ("Civility".$obj->civility_code) ? $langs->trans("Civility".$obj->civility_code) : $obj->civility_code) : '';

				$this->lastname = $obj->lastname;
				$this->firstname = $obj->firstname;
				$this->address = $obj->address;
				$this->zip = $obj->zip;
				$this->town = $obj->town;

				$this->date_creation     = $this->db->jdate($obj->date_creation);
				$this->date_modification = $this->db->jdate($obj->date_modification);

				$this->state_id = $obj->state_id;
				$this->state_code = $obj->state_code;
				$this->state = $obj->state;

				$this->country_id = $obj->country_id;
				$this->country_code = $obj->country_id ? $obj->country_code : '';
				$this->country			= $obj->country_id ? ($langs->trans('Country'.$obj->country_code) != 'Country'.$obj->country_code ? $langs->transnoentities('Country'.$obj->country_code) : $obj->country) : '';

				$this->socid			= $obj->fk_soc;
				$this->socname			= $obj->socname;
				$this->poste			= $obj->poste;
				$this->statut = $obj->statut;

				$this->fk_prospectlevel = $obj->fk_prospectcontactlevel;

				$transcode = $langs->trans('StatusProspect'.$obj->fk_stcommcontact);
				$libelle = ($transcode != 'StatusProspect'.$obj->fk_stcommcontact ? $transcode : $obj->stcomm);
				$this->stcomm_id = $obj->fk_stcommcontact; // id statut commercial
				$this->statut_commercial = $libelle; // libelle statut commercial
				$this->stcomm_picto = $obj->stcomm_picto; // Picto statut commercial

				$this->phone_pro = trim($obj->phone);
				$this->fax = trim($obj->fax);
				$this->phone_perso = trim($obj->phone_perso);
				$this->phone_mobile = trim($obj->phone_mobile);

				$this->email			= $obj->email;
				$this->socialnetworks = (array) json_decode($obj->socialnetworks, true);
				$this->photo			= $obj->photo;
				$this->priv				= $obj->priv;
				$this->mail				= $obj->email;

				$this->birthday = $this->db->jdate($obj->birthday);
				$this->note				= $obj->note_private; // deprecated
				$this->note_private		= $obj->note_private;
				$this->note_public = $obj->note_public;
				$this->default_lang		= $obj->default_lang;
				$this->user_id = $obj->user_id;
				$this->user_login		= $obj->user_login;
				$this->canvas = $obj->canvas;

				$this->import_key		= $obj->import_key;

				// Define gender according to civility
				$this->setGenderFromCivility();

				// Search Dolibarr user linked to this contact
				$sql = "SELECT u.rowid ";
				$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
				$sql .= " WHERE u.fk_socpeople = ".$this->id;

				$resql = $this->db->query($sql);
				if ($resql)
				{
					if ($this->db->num_rows($resql))
					{
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
				if ($user)
				{
					$sql = "SELECT fk_user";
					$sql .= " FROM ".MAIN_DB_PREFIX."user_alert";
					$sql .= " WHERE fk_user = ".$user->id." AND fk_contact = ".$this->db->escape($id);

					$resql = $this->db->query($sql);
					if ($resql)
					{
						if ($this->db->num_rows($resql))
						{
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

				return 1;
			} else {
				$this->error = $langs->trans("RecordNotFound");
				return 0;
			}
		} else {
			$this->error = $this->db->error();
			return -1;
		}
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

		if (in_array($this->civility_id, array('MR')) || in_array($this->civility_code, array('MR')))
		{
			$this->gender = 'man';
		} elseif (in_array($this->civility_id, array('MME', 'MLE')) || in_array($this->civility_code, array('MME', 'MLE')))
		{
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
	 *  @return     int             					<0 if KO, >=0 if OK
	 */
	public function load_ref_elements()
	{
		// phpcs:enable
		// Compte les elements pour lesquels il est contact
		$sql = "SELECT tc.element, count(ec.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as tc";
		$sql .= " WHERE ec.fk_c_type_contact = tc.rowid";
		$sql .= " AND fk_socpeople = ".$this->id;
		$sql .= " AND tc.source = 'external'";
		$sql .= " GROUP BY tc.element";

		dol_syslog(get_class($this)."::load_ref_elements", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj = $this->db->fetch_object($resql))
			{
				if ($obj->nb)
				{
					if ($obj->element == 'facture')  $this->ref_facturation = $obj->nb;
					elseif ($obj->element == 'contrat')  $this->ref_contrat = $obj->nb;
					elseif ($obj->element == 'commande') $this->ref_commande = $obj->nb;
					elseif ($obj->element == 'propal')   $this->ref_propal = $obj->nb;
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
	 *   	Efface le contact de la base
	 *
	 *   	@param		int		$notrigger		Disable all trigger
	 *		@return		int						<0 if KO, >0 if OK
	 */
	public function delete($notrigger = 0)
	{
		global $conf, $langs, $user;

		$error = 0;

		$this->db->begin();

		if (!$error)
		{
			// Get all rowid of element_contact linked to a type that is link to llx_socpeople
			$sql = "SELECT ec.rowid";
			$sql .= " FROM ".MAIN_DB_PREFIX."element_contact ec,";
			$sql .= " ".MAIN_DB_PREFIX."c_type_contact tc";
			$sql .= " WHERE ec.fk_socpeople=".$this->id;
			$sql .= " AND ec.fk_c_type_contact=tc.rowid";
			$sql .= " AND tc.source='external'";
			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$num = $this->db->num_rows($resql);

				$i = 0;
				while ($i < $num && !$error)
				{
					$obj = $this->db->fetch_object($resql);

					$sqldel = "DELETE FROM ".MAIN_DB_PREFIX."element_contact";
					$sqldel .= " WHERE rowid = ".$obj->rowid;
					dol_syslog(__METHOD__, LOG_DEBUG);
					$result = $this->db->query($sqldel);
					if (!$result)
					{
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

		if (!$error)
		{
			// Remove Roles
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."societe_contacts WHERE fk_socpeople = ".$this->id;
			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql)
			{
				$error++;
				$this->error .= $this->db->lasterror();
				$errorflag = -1;
			}
		}

		if (!$error)
		{
			// Remove category
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."categorie_contact WHERE fk_socpeople = ".$this->id;
			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql)
			{
				$error++;
				$this->error .= $this->db->lasterror();
				$errorflag = -1;
			}
		}

		if (!$error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."socpeople";
			$sql .= " WHERE rowid=".$this->id;
			dol_syslog(__METHOD__, LOG_DEBUG);
			$result = $this->db->query($sql);
			if (!$result)
			{
				$error++;
				$this->error = $this->db->error().' sql='.$sql;
			}
		}

		// Removed extrafields
		if (!$error) {
			// For avoid conflicts if trigger used
			$result = $this->deleteExtraFields();
			if ($result < 0) $error++;
		}

		if (!$error && !$notrigger)
		{
			// Call trigger
			$result = $this->call_trigger('CONTACT_DELETE', $user);
			if ($result < 0) { $error++; }
			// End call triggers
		}

		if (!$error)
		{
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			dol_syslog("Error ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *  Charge les informations sur le contact, depuis la base
	 *
	 *  @param		int		$id      Id du contact a charger
	 *  @return		void
	 */
	public function info($id)
	{
		$sql = "SELECT c.rowid, c.datec as datec, c.fk_user_creat,";
		$sql .= " c.tms as tms, c.fk_user_modif";
		$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c";
		$sql .= " WHERE c.rowid = ".$this->db->escape($id);

		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				if ($obj->fk_user_creat) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_creat);
					$this->user_creation = $cuser;
				}

				if ($obj->fk_user_modif) {
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_modif);
					$this->user_modification = $muser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
			}

			$this->db->free($resql);
		} else {
			print $this->db->error();
		}
	}

	/**
	 *  Return number of mass Emailing received by this contacts with its email
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
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			$nb = $obj->nb;

			$this->db->free($resql);
			return $nb;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *  Return name of contact with link (and eventually picto)
	 *	Use $this->id, $this->lastname, $this->firstname, this->civility_id
	 *
	 *	@param		int			$withpicto					Include picto with link
	 *	@param		string		$option						Where the link point to
	 *	@param		int			$maxlen						Max length of
	 *  @param		string		$moreparam					Add more param into URL
	 *  @param      int     	$save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@param		int			$notooltip					1=Disable tooltip
	 *	@return		string									String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $maxlen = 0, $moreparam = '', $save_lastsearch_value = -1, $notooltip = 0)
	{
		global $conf, $langs, $hookmanager;

		$result = ''; $label = '';

		if (!empty($this->photo) && class_exists('Form'))
		{
			$label .= '<div class="photointooltip">';
			$label .= Form::showphoto('contact', $this, 0, 40, 0, '', 'mini', 0); // Important, we must force height so image will have height tags and if image is inside a tooltip, the tooltip manager can calculate height and position correctly the tooltip.
			$label .= '</div><div style="clear: both;"></div>';
		}

		$label .= img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Contact").'</u>';
		$label .= ' '.$this->getLibStatut(4);
		$label .= '<br><b>'.$langs->trans("Name").':</b> '.$this->getFullName($langs);
		//if ($this->civility_id) $label.= '<br><b>' . $langs->trans("Civility") . ':</b> '.$this->civility_id;		// TODO Translate cibilty_id code
		if (!empty($this->poste)) $label .= '<br><b>'.$langs->trans("Poste").':</b> '.$this->poste;
		$label .= '<br><b>'.$langs->trans("EMail").':</b> '.$this->email;
		$phonelist = array();
		$country_code = empty($this->country_code) ? '': $this->country_code;
		if ($this->phone_pro) $phonelist[] = dol_print_phone($this->phone_pro, $country_code, $this->id, 0, '', '&nbsp;', 'phone');
		if ($this->phone_mobile) $phonelist[] = dol_print_phone($this->phone_mobile, $country_code, $this->id, 0, '', '&nbsp;', 'mobile');
		if ($this->phone_perso) $phonelist[] = dol_print_phone($this->phone_perso, $country_code, $this->id, 0, '', '&nbsp;', 'phone');
		$label .= '<br><b>'.$langs->trans("Phone").':</b> '.implode('&nbsp;', $phonelist);
		$label .= '<br><b>'.$langs->trans("Address").':</b> '.dol_format_address($this, 1, ' ', $langs);

		$url = DOL_URL_ROOT.'/contact/card.php?id='.$this->id;

		if ($option !== 'nolink')
		{
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
			if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
		}

		$url .= $moreparam;

		$linkclose = "";
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$label = $langs->trans("ShowContact");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
		   	$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
		   	$linkclose .= ' class="classfortooltip"';

		   	/*
    	   	 $hookmanager->initHooks(array('contactdao'));
    	   	 $parameters=array('id'=>$this->id);
    	   	 $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
    	   	 if ($reshook > 0) $linkclose = $hookmanager->resPrint;
    	   	 */
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		if ($option == 'xxx')
		{
			$linkstart = '<a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$this->id.$moreparam.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
			$linkend = '</a>';
		}

		$result .= $linkstart;
		if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		if ($withpicto != 2) $result .= ($maxlen ?dol_trunc($this->getFullName($langs), $maxlen) : $this->getFullName($langs));
		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array('contactdao'));
		$parameters = array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

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
		if (empty($code)) return '';

		$langs->load("dict");
		return $langs->getLabelFromKey($this->db, "Civility".$code, "c_civility", "code", "label", $code);
	}

	/**
	 *	Return label of contact status
	 *
	 *	@param      int			$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 * 	@return 	string					Label of contact status
	 */
	public function getLibStatut($mode)
	{
		return $this->LibStatut($this->statut, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Renvoi le libelle d'un statut donne
	 *
	 *  @param      int			$status     Id statut
	 *  @param      int			$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return     string					Libelle
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
		if ($status == 0 || $status == 5) $statusType = 'status5';

		$label = $langs->trans($labelStatus[$status]);
		$labelshort = $langs->trans($labelStatusShort[$status]);

		return dolGetStatus($label, $labelshort, '', $statusType, $mode);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return translated label of Public or Private
	 *
	 * 	@param      int			$status		Type (0 = public, 1 = private)
	 *  @return     string					Label translated
	 */
	public function LibPubPriv($status)
	{
		// phpcs:enable
		global $langs;
		if ($status == '1') return $langs->trans('ContactPrivate');
		else return $langs->trans('ContactPublic');
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
			if ($obj) $socid = $obj->rowid;
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
		);
		$this->phone_pro = '0909090901';
		$this->phone_perso = '0909090902';
		$this->phone_mobile = '0909090903';
		$this->fax = '0909090909';

		$this->note_public = 'This is a comment (public)';
		$this->note_private = 'This is a comment (private)';

		$this->socid = $socid;
		$this->statut = 1;
		return 1;
	}

	/**
	 *  Change status of a user
	 *
	 *	@param	int		$status		Status to set
	 *  @return int     			<0 if KO, 0 if nothing is done, >0 if OK
	 */
	public function setstatus($status)
	{
		global $conf, $langs, $user;

		$error = 0;

		// Check parameters
		if ($this->statut == $status) return 0;
		else $this->statut = $status;

		$this->db->begin();

		// Desactive utilisateur
		$sql = "UPDATE ".MAIN_DB_PREFIX."socpeople";
		$sql .= " SET statut = ".$this->statut;
		$sql .= " WHERE rowid = ".$this->id;
		$result = $this->db->query($sql);

		dol_syslog(get_class($this)."::setstatus", LOG_DEBUG);
		if ($result)
		{
			// Call trigger
			$result = $this->call_trigger('CONTACT_ENABLEDISABLE', $user);
			if ($result < 0) { $error++; }
			// End call triggers
		}

		if ($error)
		{
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
	 * Deletes object from existing categories not supplied.
	 * Adds it to non existing supplied categories.
	 * Existing categories are left untouch.
	 *
	 * @param int[]|int $categories Category or categories IDs
	 * @return void
	 */
	public function setCategories($categories)
	{
		// Handle single category
		if (!is_array($categories)) {
			$categories = array($categories);
		}

		// Get current categories
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$c = new Categorie($this->db);
		$existing = $c->containing($this->id, Categorie::TYPE_CONTACT, 'id');

		// Diff
		if (is_array($existing)) {
			$to_del = array_diff($existing, $categories);
			$to_add = array_diff($categories, $existing);
		} else {
			$to_del = array(); // Nothing to delete
			$to_add = $categories;
		}

		// Process
		foreach ($to_del as $del) {
			if ($c->fetch($del) > 0) {
				$c->del_type($this, Categorie::TYPE_CONTACT);
			}
		}
		foreach ($to_add as $add) {
			if ($c->fetch($add) > 0) {
				$c->add_type($this, Categorie::TYPE_CONTACT);
			}
		}

		return;
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old thirdparty id
	 * @param int $dest_id New thirdparty id
	 * @return bool
	 */
	public static function replaceThirdparty(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'socpeople', 'societe_contacts'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}

	/**
	 * Fetch roles (default contact of some companies) for the current contact.
	 * This load the array ->roles.
	 *
	 * @return 	int			<0 if KO, Nb of roles found if OK
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
		$sql .= " AND sc.fk_socpeople = ".$this->id;
		$sql .= " AND sc.entity IN (".getEntity('societe').')';

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->roles = array();

			$num = $this->db->num_rows($resql);
			if ($num > 0) {
				while ($obj = $this->db->fetch_object($resql)) {
					$transkey = "TypeContact_".$obj->element."_".$obj->source."_".$obj->code;
					$libelle_element = $langs->trans('ContactDefault_'.$obj->element);
					$this->roles[$obj->contactroleid] = array('id'=>$obj->rowid, 'socid'=>$obj->socid, 'element'=>$obj->element, 'source'=>$obj->source, 'code'=>$obj->code, 'label'=>$libelle_element.' - '.($langs->trans($transkey) != $transkey ? $langs->trans($transkey) : $obj->label));
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
	 * Get Contact roles for a thirdparty
	 *
	 * @param  string 	$element 	Element type
	 * @return array|int			Array of contact roles or -1
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
		$sql .= " WHERE sc.fk_soc =".$this->socid;
		$sql .= " AND sc.fk_c_type_contact=tc.rowid";
		$sql .= " AND tc.element='".$this->db->escape($element)."'";
		$sql .= " AND tc.active=1";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$tab[] = array('fk_socpeople'=>$obj->id, 'type_contact'=>$obj->fk_c_type_contact);

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
	 * @return float|int
	 * @see fetchRoles()
	 */
	public function updateRoles()
	{
		global $conf;

		$error = 0;

		if (!isset($this->roles)) return;	// Avoid to loose roles when property not set

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."societe_contacts WHERE fk_socpeople=".$this->id." AND entity IN (".getEntity("societe_contact").")";

		$result = $this->db->query($sql);
		if (!$result) {
			$this->errors[] = $this->db->lasterror().' sql='.$sql;
			$error++;
		} else {
			if (count($this->roles) > 0) {
				foreach ($this->roles as $keyRoles => $valRoles) {
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
						$sql .= " VALUES (".$conf->entity.",";
						$sql .= "'".$this->db->idate(dol_now())."',";
						$sql .= $socid.", ";
						$sql .= $idrole." , ";
						$sql .= $this->id;
						$sql .= ")";

						$result = $this->db->query($sql);
						if (!$result)
						{
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
	 *  @return int					<0 if KO, >0 if OK
	 */
	public function loadCacheOfProspStatus($active = 1)
	{
		global $langs;

		$sql = "SELECT id, code, libelle as label, picto FROM ".MAIN_DB_PREFIX."c_stcommcontact";
		if ($active >= 0) $sql .= " WHERE active = ".$active;
		$resql = $this->db->query($sql);
		$num = $this->db->num_rows($resql);
		$i = 0;
		while ($i < $num)
		{
			$obj = $this->db->fetch_object($resql);
			$this->cacheprospectstatus[$obj->id] = array('id' => $obj->id, 'code' => $obj->code, 'label' => ($langs->trans("ST_".strtoupper($obj->code)) == "ST_".strtoupper($obj->code)) ? $obj->label : $langs->trans("ST_".strtoupper($obj->code)), 'picto' => $obj->picto);
			$i++;
		}
		return 1;
	}

	/**
	 *	Return prostect level
	 *
	 *  @return     string        Libelle
	 */
	public function getLibProspLevel()
	{
		return $this->libProspLevel($this->fk_prospectlevel);
	}

	/**
	 *  Return label of prospect level
	 *
	 *  @param	int		$fk_prospectlevel   	Prospect level
	 *  @return string        					label of level
	 */
	public function libProspLevel($fk_prospectlevel)
	{
		global $langs;

		$lib = $langs->trans("ProspectLevel".$fk_prospectlevel);
		// If lib not found in language file, we get label from cache/databse
		if ($lib == $langs->trans("ProspectLevel".$fk_prospectlevel))
		{
			$lib = $langs->getLabelFromKey($this->db, $fk_prospectlevel, 'c_prospectlevel', 'code', 'label');
		}
		return $lib;
	}


	/**
	 *  Set prospect level
	 *
	 *  @param  User	$user		Utilisateur qui definie la remise
	 *	@return	int					<0 if KO, >0 if OK
	 * @deprecated Use update function instead
	 */
	public function setProspectLevel(User $user)
	{
		return $this->update($this->id, $user);
	}

	/**
	 *  Return status of prospect
	 *
	 *  @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
	 *  @param	string	$label		Label to use for status for added status
	 *  @return string        		Libelle
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
	 *  @return string       	 			Libelle du statut
	 */
	public function libProspCommStatut($statut, $mode = 0, $label = '', $picto = '')
	{
		global $langs;
		$langs->load('customers');

		if ($mode == 2)
		{
			if ($statut == '-1' || $statut == 'ST_NO') return img_action($langs->trans("StatusProspect-1"), -1, $picto).' '.$langs->trans("StatusProspect-1");
			elseif ($statut == '0' || $statut == 'ST_NEVER') return img_action($langs->trans("StatusProspect0"), 0, $picto).' '.$langs->trans("StatusProspect0");
			elseif ($statut == '1' || $statut == 'ST_TODO') return img_action($langs->trans("StatusProspect1"), 1, $picto).' '.$langs->trans("StatusProspect1");
			elseif ($statut == '2' || $statut == 'ST_PEND') return img_action($langs->trans("StatusProspect2"), 2, $picto).' '.$langs->trans("StatusProspect2");
			elseif ($statut == '3' || $statut == 'ST_DONE') return img_action($langs->trans("StatusProspect3"), 3, $picto).' '.$langs->trans("StatusProspect3");
			else {
				return img_action(($langs->trans("StatusProspect".$statut) != "StatusProspect".$statut) ? $langs->trans("StatusProspect".$statut) : $label, 0, $picto).' '.(($langs->trans("StatusProspect".$statut) != "StatusProspect".$statut) ? $langs->trans("StatusProspect".$statut) : $label);
			}
		}
		if ($mode == 3)
		{
			if ($statut == '-1' || $statut == 'ST_NO') return img_action($langs->trans("StatusProspect-1"), -1, $picto);
			elseif ($statut == '0' || $statut == 'ST_NEVER') return img_action($langs->trans("StatusProspect0"), 0, $picto);
			elseif ($statut == '1' || $statut == 'ST_TODO') return img_action($langs->trans("StatusProspect1"), 1, $picto);
			elseif ($statut == '2' || $statut == 'ST_PEND') return img_action($langs->trans("StatusProspect2"), 2, $picto);
			elseif ($statut == '3' || $statut == 'ST_DONE') return img_action($langs->trans("StatusProspect3"), 3, $picto);
			else {
				return img_action(($langs->trans("StatusProspect".$statut) != "StatusProspect".$statut) ? $langs->trans("StatusProspect".$statut) : $label, 0, $picto);
			}
		}
		if ($mode == 4)
		{
			if ($statut == '-1' || $statut == 'ST_NO') return img_action($langs->trans("StatusProspect-1"), -1, $picto).' '.$langs->trans("StatusProspect-1");
			elseif ($statut == '0' || $statut == 'ST_NEVER') return img_action($langs->trans("StatusProspect0"), 0, $picto).' '.$langs->trans("StatusProspect0");
			elseif ($statut == '1' || $statut == 'ST_TODO') return img_action($langs->trans("StatusProspect1"), 1, $picto).' '.$langs->trans("StatusProspect1");
			elseif ($statut == '2' || $statut == 'ST_PEND') return img_action($langs->trans("StatusProspect2"), 2, $picto).' '.$langs->trans("StatusProspect2");
			elseif ($statut == '3' || $statut == 'ST_DONE') return img_action($langs->trans("StatusProspect3"), 3, $picto).' '.$langs->trans("StatusProspect3");
			else {
				return img_action(($langs->trans("StatusProspect".$statut) != "StatusProspect".$statut) ? $langs->trans("StatusProspect".$statut) : $label, 0, $picto).' '.(($langs->trans("StatusProspect".$statut) != "StatusProspect".$statut) ? $langs->trans("StatusProspect".$statut) : $label);
			}
		}

		return "Error, mode/status not found";
	}
}
