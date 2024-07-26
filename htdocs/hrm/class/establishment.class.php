<?php
/* Copyright (C) 2015		Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2018-2024  Frédéric France     <frederic.france@free.fr>
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
 *    \file       htdocs/hrm/class/establishment.class.php
 *    \ingroup    HRM
 *    \brief      File of class to manage establishments
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class to manage establishments
 */
class Establishment extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'establishment';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'establishment';

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = '';

	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_establishment';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'establishment';

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
	public $rowid;

	/**
	 * @var string Label
	 */
	public $label;

	/**
	 * @var string Address
	 */
	public $address;

	/**
	 * @var string Zip
	 */
	public $zip;

	/**
	 * @var string Town
	 */
	public $town;

	/**
	 * @var int country id
	 */
	public $country_id;

	/**
	 * @var int Status 0=open, 1=closed
	 */
	public $status;

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var int user mod id
	 */
	public $fk_user_mod;

	/**
	 * @var int user author id
	 */
	public $fk_user_author;

	/**
	 * @var int date create
	 */
	public $datec;

	const STATUS_OPEN = 1;
	const STATUS_CLOSED = 0;


	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 10),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 15, 'index' => 1),
		'ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'showoncombobox' => 1, 'position' => 20),
		'label' => array('type' => 'varchar(128)', 'label' => 'Label', 'enabled' => 1, 'visible' => -1, 'showoncombobox' => 2, 'position' => 22),
		'address' => array('type' => 'varchar(255)', 'label' => 'Address', 'enabled' => 1, 'visible' => -1, 'position' => 25),
		'zip' => array('type' => 'varchar(25)', 'label' => 'Zip', 'enabled' => 1, 'visible' => -1, 'position' => 30),
		'town' => array('type' => 'varchar(50)', 'label' => 'Town', 'enabled' => 1, 'visible' => -1, 'position' => 35),
		'fk_state' => array('type' => 'integer', 'label' => 'Fkstate', 'enabled' => 1, 'visible' => -1, 'position' => 40),
		'fk_country' => array('type' => 'integer', 'label' => 'Fkcountry', 'enabled' => 1, 'visible' => -1, 'position' => 45),
		'profid1' => array('type' => 'varchar(20)', 'label' => 'Profid1', 'enabled' => 1, 'visible' => -1, 'position' => 50),
		'profid2' => array('type' => 'varchar(20)', 'label' => 'Profid2', 'enabled' => 1, 'visible' => -1, 'position' => 55),
		'profid3' => array('type' => 'varchar(20)', 'label' => 'Profid3', 'enabled' => 1, 'visible' => -1, 'position' => 60),
		'phone' => array('type' => 'varchar(20)', 'label' => 'Phone', 'enabled' => 1, 'visible' => -1, 'position' => 65),
		'fk_user_author' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'Fkuserauthor', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 70),
		'fk_user_mod' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'Fkusermod', 'enabled' => 1, 'visible' => -1, 'position' => 75),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 80),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 85),
		'status' => array('type' => 'integer', 'label' => 'Status', 'enabled' => 1, 'visible' => -1, 'position' => 500),
	);


	/**
	 * Constructor
	 *
	 * @param	DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->ismultientitymanaged = 1;
	}

	/**
	 *	Create object in database
	 *
	 *	@param		User	$user   User making creation
	 *	@return 	int				Return integer <0 if KO, >0 if OK
	 */
	public function create($user)
	{
		global $conf, $langs;

		$error = 0;
		$now = dol_now();

		// Clean parameters
		$this->label = trim($this->label);
		$this->address = trim($this->address);
		$this->zip = trim($this->zip);
		$this->town = trim($this->town);

		if (empty($this->ref)) {
			$this->ref = '(PROV)';
		}

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."establishment (";
		$sql .= "ref";
		$sql .= ", label";
		$sql .= ", address";
		$sql .= ", zip";
		$sql .= ", town";
		$sql .= ", fk_country";
		$sql .= ", status";
		$sql .= ", entity";
		$sql .= ", datec";
		$sql .= ", fk_user_author";
		$sql .= ", fk_user_mod";
		$sql .= ") VALUES (";
		$sql .= "'".$this->db->escape($this->ref)."'";
		$sql .= ", '".$this->db->escape($this->label)."'";
		$sql .= ", '".$this->db->escape($this->address)."'";
		$sql .= ", '".$this->db->escape($this->zip)."'";
		$sql .= ", '".$this->db->escape($this->town)."'";
		$sql .= ", ".((int) $this->country_id);
		$sql .= ", ".((int) $this->status);
		$sql .= ", ".((int) $conf->entity);
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ", ".((int) $user->id);
		$sql .= ", ".((int) $user->id);
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'establishment');

			$sql = 'UPDATE '.MAIN_DB_PREFIX."establishment SET ref = '".$this->db->escape($this->id)."'";
			$sql .= " WHERE rowid = ".((int) $this->id);
			$this->db->query($sql);

			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 *	Update record
	 *
	 *	@param	User	$user		User making update
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function update($user)
	{
		global $langs;

		// Check parameters
		if (empty($this->label)) {
			$this->error = 'ErrorBadParameter';
			return -1;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."establishment";
		$sql .= " SET ref = '".$this->db->escape($this->ref)."'";
		$sql .= ", label = '".$this->db->escape($this->label)."'";
		$sql .= ", address = '".$this->db->escape($this->address)."'";
		$sql .= ", zip = '".$this->db->escape($this->zip)."'";
		$sql .= ", town = '".$this->db->escape($this->town)."'";
		$sql .= ", fk_country = ".($this->country_id > 0 ? $this->country_id : 'null');
		$sql .= ", status = ".((int) $this->status);
		$sql .= ", fk_user_mod = ".((int) $user->id);
		$sql .= ", entity = ".((int) $this->entity);
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Load an object from database
	 *
	 * @param	int		$id		Id of record to load
	 * @return	int				Return integer <0 if KO, >=0 if OK
	 */
	public function fetch($id)
	{
		$sql = "SELECT e.rowid, e.ref, e.label, e.address, e.zip, e.town, e.status, e.fk_country as country_id, e.entity,";
		$sql .= ' c.code as country_code, c.label as country';
		$sql .= " FROM ".MAIN_DB_PREFIX."establishment as e";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON e.fk_country = c.rowid';
		$sql .= " WHERE e.rowid = ".((int) $id);

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			if ($obj) {
				$this->id = $obj->rowid;
				$this->ref			= $obj->ref;
				$this->label		= $obj->label;
				$this->address = $obj->address;
				$this->zip			= $obj->zip;
				$this->town			= $obj->town;
				$this->status = $obj->status;
				$this->entity = $obj->entity;

				$this->country_id   = $obj->country_id;
				$this->country_code = $obj->country_code;
				$this->country      = $obj->country;

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
	 *	Delete record
	 *
	 *  @param	User	$user	User making the change
	 *	@return	int				Return integer <0 if KO, >0 if OK
	 */
	public function delete($user)
	{
		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."establishment WHERE rowid = ".((int) $user->id);

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);

		$result = $this->db->query($sql);
		if ($result) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Give a label from a status
	 *
	 * @param  	int		$mode		0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return  string   		 	Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
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
			//$langs->load("mymodule");
			$this->labelStatus[self::STATUS_OPEN] = $langs->transnoentitiesnoconv('Open');
			$this->labelStatus[self::STATUS_CLOSED] = $langs->transnoentitiesnoconv('Closed');
			$this->labelStatusShort[self::STATUS_OPEN] = $langs->transnoentitiesnoconv('Open');
			$this->labelStatusShort[self::STATUS_CLOSED] = $langs->transnoentitiesnoconv('Closed');
		}

		$statusType = 'status'.$status;
		if ($status == self::STATUS_OPEN) {
			$statusType = 'status4';
		}
		if ($status == self::STATUS_CLOSED) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}


	/**
	 * Information on record
	 *
	 * @param	int		$id      Id of record
	 * @return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT e.rowid, e.ref, e.datec, e.fk_user_author, e.tms as datem, e.fk_user_mod, e.entity';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'establishment as e';
		$sql .= ' WHERE e.rowid = '.((int) $id);

		dol_syslog(get_class($this)."::fetch info", LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_author;
				$this->user_modification_id = $obj->fk_user_mod;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);
			}
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 *  Return a link to the object card (with optionally the picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$label = img_picto('', $this->picto).' <u>'.$langs->trans("Establishment").'</u>';
		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Residence').':</b> '.$this->address.', '.$this->zip.' '.$this->town;

		$url = DOL_URL_ROOT.'/hrm/establishment/card.php?id='.$this->id;

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
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("Establishment");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
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
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}

		if ($withpicto != 2) {
			$result .= $this->label;
		}

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('establishmentdao'));
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
	 * 	Return account country code
	 *
	 *	@return		string		country code
	 */
	public function getCountryCode()
	{
		global $mysoc;

		// We return country code of bank account
		if (!empty($this->country_code)) {
			return $this->country_code;
		}

		// We return country code of managed company
		if (!empty($mysoc->country_code)) {
			return $mysoc->country_code;
		}

		return '';
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return int
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;
		$this->ref = '0';
		$this->label = 'Department AAA';

		return 1;
	}
}
