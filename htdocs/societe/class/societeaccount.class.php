<?php
/* Copyright (C) 2007-2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 * \file        societe/class/societeaccount.class.php
 * \ingroup     societe
 * \brief       This file is a CRUD class file for SocieteAccount (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for SocieteAccount
 */
class SocieteAccount extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'societeaccount';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'societe_account';

	/**
	 * @var array  Does societeaccount support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var string String with name of icon for societeaccount. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'lock';


	/**
	 *  'type' if the field format.
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only. Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'position' is the sort order of field.
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'help' is a string visible as a tooltip on field
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *  'default' is a default value for creation (can still be replaced by the global setup of default values)
	 *  'showoncombobox' if field must be shown into the label of combobox
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'visible'=>-2, 'enabled'=>1, 'position'=>1, 'notnull'=>1, 'index'=>1, 'comment'=>'Id',),
		'entity' => array('type'=>'integer', 'label'=>'Entity', 'visible'=>0, 'enabled'=>1, 'position'=>5, 'default'=>1),
		'login' => array('type'=>'varchar(64)', 'label'=>'Login', 'visible'=>1, 'enabled'=>1, 'notnull'=>1, 'position'=>10, 'showoncombobox'=>1),
		'pass_encoding' => array('type'=>'varchar(24)', 'label'=>'PassEncoding', 'visible'=>0, 'enabled'=>1, 'position'=>30),
		'pass_crypted' => array('type'=>'password', 'label'=>'Password', 'visible'=>1, 'enabled'=>1, 'position'=>31, 'notnull'=>1),
		'pass_temp'    => array('type'=>'varchar(128)', 'label'=>'Temp', 'visible'=>0, 'enabled'=>0, 'position'=>32, 'notnull'=>-1,),
		'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php', 'label'=>'ThirdParty', 'visible'=>1, 'enabled'=>1, 'position'=>40, 'notnull'=>-1, 'index'=>1, 'picto'=>'company', 'css'=>'maxwidth300 widthcentpercentminusxx'),
		'site' => array('type'=>'varchar(128)', 'label'=>'WebsiteTypeLabel', 'visible'=>0, 'enabled'=>0, 'position'=>41, 'notnull'=>1, 'default' => '', 'help'=>'Name of the website or service if this is account on an external website or service'),
		'fk_website' => array('type'=>'integer:Website:website/class/website.class.php', 'label'=>'WebSite', 'visible'=>0, 'enabled'=>0, 'position'=>42, 'notnull'=>-1, 'index'=>1, 'picto'=>'website', 'css'=>'maxwidth300 widthcentpercentminusxx'),
		'site_account' => array('type'=>'varchar(128)', 'label'=>'ExternalSiteAccount', 'visible'=>0, 'enabled'=>1, 'position'=>44, 'help'=>'A key to identify the account on external web site if this is an account on an external website'),
		'key_account' => array('type'=>'varchar(128)', 'label'=>'KeyAccount', 'visible'=>0, 'enabled'=>1, 'position'=>48, 'notnull'=>0, 'index'=>1, 'searchall'=>1, 'comment'=>'The id of third party in the external web site (for site_account if site_account defined)',),
		'date_last_login' => array('type'=>'datetime', 'label'=>'LastConnexion', 'visible'=>2, 'enabled'=>1, 'position'=>50, 'notnull'=>0,),
		'date_previous_login' => array('type'=>'datetime', 'label'=>'PreviousConnexion', 'visible'=>2, 'enabled'=>1, 'position'=>51, 'notnull'=>0,),
		//'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'visible'=>-1, 'enabled'=>1, 'position'=>45, 'notnull'=>-1,),
		'note_private' => array('type'=>'html', 'label'=>'NotePrivate', 'visible'=>-1, 'enabled'=>1, 'position'=>46, 'notnull'=>-1,),
		'date_creation' => array('type'=>'datetime', 'label'=>'DateCreation', 'visible'=>-2, 'enabled'=>1, 'position'=>500, 'notnull'=>1,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'visible'=>-2, 'enabled'=>1, 'position'=>500, 'notnull'=>1,),
		'fk_user_creat' => array('type'=>'integer', 'label'=>'UserAuthor', 'visible'=>-2, 'enabled'=>1, 'position'=>500, 'notnull'=>1,),
		'fk_user_modif' => array('type'=>'integer', 'label'=>'UserModif', 'visible'=>-2, 'enabled'=>1, 'position'=>500, 'notnull'=>-1,),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'visible'=>-2, 'enabled'=>1, 'position'=>1000, 'notnull'=>-1, 'index'=>1,),
		'status' => array('type'=>'integer', 'label'=>'Status', 'visible'=>1, 'enabled'=>1, 'position'=>1000, 'notnull'=>1, 'index'=>1, 'default'=>1, 'arrayofkeyval'=>array('1'=>'Active', '0'=>'Disabled')),
	);

	/**
	 * @var int ID
	 */
	public $rowid;

	/**
	 * @var int Entity
	 */
	public $entity;

	public $key_account;
	public $login;
	public $pass_encoding;
	public $pass_crypted;
	public $pass_temp;

	/**
	 * @var int Thirdparty ID
	 */
	public $fk_soc;

	public $site;
	public $site_account;

	/**
	 * @var integer|string date_last_login
	 */
	public $date_last_login;


	public $date_previous_login;
	public $note_private;

	/**
	 * @var integer|string date_creation
	 */
	public $date_creation;


	public $tms;

	/**
	 * @var int ID
	 */
	public $fk_user_creat;

	/**
	 * @var int ID
	 */
	public $fk_user_modif;

	public $import_key;

	/**
	 * @var int Status
	 */
	public $status;

	// END MODULEBUILDER PROPERTIES


	const STATUS_ENABLED = 1;
	const STATUS_DISABLED = 0;


	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (!getDolGlobalString('MAIN_SHOW_TECHNICAL_ID')) {
			$this->fields['rowid']['visible'] = 0;
		}

		// add site type list and set visible
		$site_type_list = array();
		if (isModEnabled('website')) {
			$this->fields['fk_website']['visible'] = 1;
			$this->fields['fk_website']['enabled'] = 1;
			$this->fields['site']['visible'] = 1;
			$this->fields['site']['enabled'] = 1;
			$site_type_list['dolibarr_website'] = $langs->trans('WebsiteTypeDolibarrWebsite');
		}
		if (isModEnabled('webportal')) {
			$this->fields['site']['visible'] = 1;
			$this->fields['site']['enabled'] = 1;
			$site_type_list['dolibarr_portal'] = $langs->trans('WebsiteTypeDolibarrPortal');
		}
		$this->fields['site']['arrayofkeyval'] = $site_type_list;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Clone and object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $hookmanager, $langs;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$object->fetchCommon($fromid);
		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		$object->ref = "copy_of_".$object->ref;
		// $object->title = $langs->trans("CopyOf")." ".$object->title;

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->createCommon($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		if ($result > 0 && !empty($this->table_element_line)) {
			$this->fetchLines();
		}
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines = array();

		// Load lines with object societeAccountLine

		return count($this->lines) ? 1 : 0;
	}

	/**
	 * Try to find the external customer id of a thirdparty for another site/system.
	 *
	 * @param	int		$id				Id of third party
	 * @param	string	$site			Site (example: 'stripe', '...')
	 * @param	int		$status			Status (0=test, 1=live)
	 * @param	string	$site_account 	Value to use to identify with account to use on site when site can offer several accounts. For example: 'pk_live_123456' when using Stripe service.
	 * @return	string					Stripe customer ref 'cu_xxxxxxxxxxxxx' or ''
	 * @see getThirdPartyID()
	 */
	public function getCustomerAccount($id, $site, $status = 0, $site_account = '')
	{
		$sql = "SELECT sa.key_account as key_account, sa.entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_account as sa";
		$sql .= " WHERE sa.fk_soc = ".((int) $id);
		$sql .= " AND sa.entity IN (".getEntity('societe').")";
		$sql .= " AND sa.site = '".$this->db->escape($site)."' AND sa.status = ".((int) $status);
		$sql .= " AND sa.key_account IS NOT NULL AND sa.key_account <> ''";
		$sql .= " AND (sa.site_account = '' OR sa.site_account IS NULL OR sa.site_account = '".$this->db->escape($site_account)."')";
		$sql .= " ORDER BY sa.site_account DESC"; // To get the entry with a site_account defined in priority

		dol_syslog(get_class($this)."::getCustomerAccount Try to find the first system customer id for ".$site." of thirdparty id=".$id." (exemple: cus_.... for stripe)", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$key = $obj->key_account;
			} else {
				$key = '';
			}
		} else {
			$key = '';
		}

		return $key;
	}

	/**
	 * Try to find the thirdparty id from an another site/system external id.
	 *
	 * @param	string	$id			Id of customer in external system (example: 'cu_xxxxxxxxxxxxx', ...)
	 * @param	string	$site		Site (example: 'stripe', '...')
	 * @param	int		$status		Status (0=test, 1=live)
	 * @return	int					Id of third party
	 * @see getCustomerAccount()
	 */
	public function getThirdPartyID($id, $site, $status = 0)
	{
		$socid = 0;

		$sql = "SELECT sa.fk_soc as fk_soc, sa.key_account, sa.entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_account as sa";
		$sql .= " WHERE sa.key_account = '".$this->db->escape($id)."'";
		$sql .= " AND sa.entity IN (".getEntity('societe').")";
		$sql .= " AND sa.site = '".$this->db->escape($site)."' AND sa.status = ".((int) $status);
		$sql .= " AND sa.fk_soc > 0";

		dol_syslog(get_class($this)."::getCustomerAccount Try to find the first thirdparty id for ".$site." for external id=".$id, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$socid = $obj->fk_soc;
			}
		}

		return $socid;
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       User that deletes
	 * @param bool $notrigger  false=launch triggers after, true=disable triggers
	 * @return int             Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
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

		$langs->loadLangs(['companies, commercial']);

		$datas = [];
		$option = $params['option'] ?? '';

		$datas['picto'] = '<u>'.$langs->trans("WebsiteAccount").'</u>';
		$datas['login'] = '<br><b>'.$langs->trans('Login').':</b> '.$this->login;

		return $datas;
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *	@param	int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option						On what the link point to ('nolink', ...)
	 *  @param	int  	$notooltip					1=Disable tooltip
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $db, $conf, $langs;
		global $dolibarr_main_authentication, $dolibarr_main_demo;
		global $menumanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$this->ref = $this->login;

		$label = '<u>'.$langs->trans("WebsiteAccount").'</u>';
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Login').':</b> '.$this->ref;
		//$label.= '<b>' . $langs->trans('WebSite') . ':</b> ' . $this->ref;

		$url = DOL_URL_ROOT.'/website/websiteaccount_card.php?id='.$this->id;

		if ($option != 'nolink') {
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
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$params = [
				'id' => $this->id,
				'objecttype' => $this->element,
				'option' => $option,
			];
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		}

		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("WebsiteAccount");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : $dataparams.' class="'.(($withpicto != 2) ? 'paddingright ' : '').$classfortooltip.'"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;

		return $result;
	}

	/**
	 *  Return the label of a given status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       	Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (is_null($status)) {
			return '';
		}

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("mymodule@mymodule");
			$this->labelStatus[self::STATUS_ENABLED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatus[self::STATUS_DISABLED] = $langs->transnoentitiesnoconv('Disabled');
			$this->labelStatusShort[self::STATUS_ENABLED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatusShort[self::STATUS_DISABLED] = $langs->transnoentitiesnoconv('Disabled');
		}

		$statusType = 'status4';
		//if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		if ($status == self::STATUS_DISABLED) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 *	Charge les informations d'ordre info dans l'objet commande
	 *
	 *	@param  int		$id       Id of order
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql .= ' fk_user_creat, fk_user_modif';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE t.rowid = '.((int) $id);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_creat;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->initAsSpecimenCommon();
	}
}
