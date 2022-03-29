<?php
/* Copyright (C) 2015		Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2018-2020  Frédéric France     <frederic.france@netlogic.fr>
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
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'building';

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
		'rowid' =>array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>10),
		'entity' =>array('type'=>'integer', 'label'=>'Entity', 'default'=>1, 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>15, 'index'=>1),
		'ref' =>array('type'=>'varchar(30)', 'label'=>'Ref', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'showoncombobox'=>1, 'position'=>20),
		'label' =>array('type'=>'varchar(128)', 'label'=>'Label', 'enabled'=>1, 'visible'=>-1, 'showoncombobox'=>1, 'position'=>22),
		'address' =>array('type'=>'varchar(255)', 'label'=>'Address', 'enabled'=>1, 'visible'=>-1, 'position'=>25),
		'zip' =>array('type'=>'varchar(25)', 'label'=>'Zip', 'enabled'=>1, 'visible'=>-1, 'position'=>30),
		'town' =>array('type'=>'varchar(50)', 'label'=>'Town', 'enabled'=>1, 'visible'=>-1, 'position'=>35),
		'fk_state' =>array('type'=>'integer', 'label'=>'Fkstate', 'enabled'=>1, 'visible'=>-1, 'position'=>40),
		'fk_country' =>array('type'=>'integer', 'label'=>'Fkcountry', 'enabled'=>1, 'visible'=>-1, 'position'=>45),
		'profid1' =>array('type'=>'varchar(20)', 'label'=>'Profid1', 'enabled'=>1, 'visible'=>-1, 'position'=>50),
		'profid2' =>array('type'=>'varchar(20)', 'label'=>'Profid2', 'enabled'=>1, 'visible'=>-1, 'position'=>55),
		'profid3' =>array('type'=>'varchar(20)', 'label'=>'Profid3', 'enabled'=>1, 'visible'=>-1, 'position'=>60),
		'phone' =>array('type'=>'varchar(20)', 'label'=>'Phone', 'enabled'=>1, 'visible'=>-1, 'position'=>65),
		'fk_user_author' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Fkuserauthor', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>70),
		'fk_user_mod' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Fkusermod', 'enabled'=>1, 'visible'=>-1, 'position'=>75),
		'datec' =>array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>80),
		'tms' =>array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>85),
		'status' =>array('type'=>'integer', 'label'=>'Status', 'enabled'=>1, 'visible'=>-1, 'position'=>500),
	);


	/**
	 * Constructor
	 *
	 * @param	DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Create object in database
	 *
	 *	@param		User	$user   User making creation
	 *	@return 	int				<0 if KO, >0 if OK
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

		if (empty($this->ref)) $this->ref = '(PROV)';

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."establishment (";
		$sql .= "ref";
		$sql .= ", label";
		$sql .= ", address";
		$sql .= ", zip";
		$sql .= ", town";
		$sql .= ", status";
		$sql .= ", fk_country";
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
		$sql .= ", ".$this->country_id;
		$sql .= ", ".$this->status;
		$sql .= ", ".$conf->entity;
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ", ".$user->id;
		$sql .= ", ".$user->id;
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
			$sql .= " WHERE rowid = ".$this->id;
			$this->db->query($sql);

			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 *	Update record
	 *
	 *	@param	User	$user		User making update
	 *	@return	int					<0 if KO, >0 if OK
	 */
	public function update($user)
	{
		global $langs;

		// Check parameters
		if (empty($this->label))
		{
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
		$sql .= ", status = ".$this->db->escape($this->status);
		$sql .= ", fk_user_mod = ".$user->id;
		$sql .= ", entity = ".$this->entity;
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
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
	 * @return	int				<0 if KO, >0 if OK
	 */
	public function fetch($id)
	{
		$sql = "SELECT e.rowid, e.ref, e.label, e.address, e.zip, e.town, e.status, e.fk_country as country_id, e.entity,";
		$sql .= ' c.code as country_code, c.label as country';
		$sql .= " FROM ".MAIN_DB_PREFIX."establishment as e";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON e.fk_country = c.rowid';
		$sql .= " WHERE e.rowid = ".$id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$obj = $this->db->fetch_object($result);

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
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Delete record
	 *
	 *	@param	int		$id		Id of record to delete
	 *	@return	int				<0 if KO, >0 if OK
	 */
	public function delete($id)
	{
		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."establishment WHERE rowid = ".$id;

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
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
		if (empty($this->labelStatus) || empty($this->labelStatusShort))
		{
			global $langs;
			//$langs->load("mymodule");
			$this->labelStatus[self::STATUS_OPEN] = $langs->trans('Open');
			$this->labelStatus[self::STATUS_CLOSED] = $langs->trans('Closed');
			$this->labelStatusShort[self::STATUS_OPEN] = $langs->trans('Open');
			$this->labelStatusShort[self::STATUS_CLOSED] = $langs->trans('Closed');
		}

		$statusType = 'status'.$status;
		if ($status == self::STATUS_OPEN) $statusType = 'status4';
		if ($status == self::STATUS_CLOSED) $statusType = 'status6';

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
		$sql = 'SELECT e.rowid, e.ref, e.datec, e.fk_user_author, e.tms, e.fk_user_mod, e.entity';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'establishment as e';
		$sql .= ' WHERE e.rowid = '.$id;

		dol_syslog(get_class($this)."::fetch info", LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;

				$this->date_creation = $this->db->jdate($obj->datec);
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}
				if ($obj->fk_user_mod)
				{
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_mod);
					$this->user_modification = $muser;

					$this->date_modification = $this->db->jdate($obj->tms);
				}
			}
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 *  Return clicable name (with picto eventually)
	 *
	 *  @param      int     $withpicto      0=No picto, 1=Include picto into link, 2=Only picto
	 *  @return     string                  String with URL
	 */
	public function getNomUrl($withpicto = 0)
	{
		global $langs;

		$result = '';

		$link = '<a href="'.DOL_URL_ROOT.'/hrm/establishment/card.php?id='.$this->id.'">';
		$linkend = '</a>';

		$picto = 'building';

		$label = '<u>'.$langs->trans("Establishment").'</u>';
		$label .= '<br>'.$langs->trans("Label").': '.$this->label;

		if ($withpicto) $result .= ($link.img_object($label, $picto).$linkend);
		if ($withpicto && $withpicto != 2) $result .= ' ';
		if ($withpicto != 2) $result .= $link.$this->label.$linkend;
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
		if (!empty($this->country_code)) return $this->country_code;

		// We return country code of managed company
		if (!empty($mysoc->country_code)) return $mysoc->country_code;

		return '';
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;
		$this->ref = '0';
		$this->label = 'Department AAA';
	}
}
