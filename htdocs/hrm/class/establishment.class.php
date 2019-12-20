<?php
/* Copyright (C) 2015		Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Frédéric France     <frederic.france@netlogic.fr>
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

require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';

/**
 * Class to manage establishments
 */
class Establishment extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element='establishment';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element='establishment';

	/**
	 * @var int    Name of subtable line
	 */
	public $table_element_line = '';

	/**
	 * @var int Field with ID of parent key if this field has a parent
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
	public $picto='building';

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

	public $name;

	/**
	 * @var string Address
	 */
	public $address;

	public $zip;
	public $town;

	/**
	 * @var int Status 0=open, 1=closed
	 */
	public $status;

	/**
	 * @var int Entity
	 */
	public $entity;

	public $country_id;

	public $statuts=array();
	public $statuts_short=array();

	/**
	 * Constructor
	 *
	 * @param	DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->statuts_short = array(0 => 'Closed', 1 => 'Opened');
        $this->statuts = array(0 => 'Closed', 1 => 'Opened');
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
		$ret = 0;
		$now=dol_now();

        // Clean parameters
        $this->address=($this->address>0?$this->address:$this->address);
        $this->zip=($this->zip>0?$this->zip:$this->zip);
        $this->town=($this->town>0?$this->town:$this->town);
        $this->country_id=($this->country_id>0?$this->country_id:$this->country_id);

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."establishment (";
		$sql.= "name";
		$sql.= ", address";
		$sql.= ", zip";
		$sql.= ", town";
		$sql.= ", status";
		$sql.= ", fk_country";
		$sql.= ", entity";
		$sql.= ", datec";
		$sql.= ", fk_user_author";
		$sql.= ", fk_user_mod";
		$sql.= ") VALUES (";
		$sql.= " '".$this->db->escape($this->name)."'";
		$sql.= ", '".$this->db->escape($this->address)."'";
        $sql.= ", '".$this->db->escape($this->zip)."'";
        $sql.= ", '".$this->db->escape($this->town)."'";
		$sql.= ", ".$this->country_id;
		$sql.= ", ".$this->status;
		$sql.= ", ".$conf->entity;
		$sql.= ", '".$this->db->idate($now)."'";
		$sql.= ", ". $user->id;
		$sql.= ", ". $user->id;
		$sql.= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error ++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "establishment");
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
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
        if (empty($this->name))
        {
            $this->error='ErrorBadParameter';
            return -1;
        }

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."establishment";
		$sql .= " SET name = '".$this->db->escape($this->name)."'";
		$sql .= ", address = '".$this->db->escape($this->address)."'";
		$sql .= ", zip = '".$this->db->escape($this->zip)."'";
		$sql .= ", town = '".$this->db->escape($this->town)."'";
		$sql .= ", fk_country = ".($this->country_id > 0 ? $this->country_id : 'null');
		$sql .= ", status = ".$this->db->escape($this->status);
		$sql .= ", fk_user_mod = " . $user->id;
		$sql .= ", entity = " . $this->entity;
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return - 1;
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
		$sql = "SELECT e.rowid, e.name, e.address, e.zip, e.town, e.status, e.fk_country as country_id, e.entity,";
		$sql.= ' c.code as country_code, c.label as country';
		$sql.= " FROM ".MAIN_DB_PREFIX."establishment as e";
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON e.fk_country = c.rowid';
		$sql.= " WHERE e.rowid = ".$id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ( $result )
		{
			$obj = $this->db->fetch_object($result);

			$this->id			= $obj->rowid;
			$this->ref			= $obj->rowid;
			$this->name			= $obj->name;
			$this->address		= $obj->address;
			$this->zip			= $obj->zip;
			$this->town			= $obj->town;
			$this->status	    = $obj->status;
			$this->entity		= $obj->entity;

            $this->country_id   = $obj->country_id;
            $this->country_code = $obj->country_code;
            $this->country      = $obj->country;

			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
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
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Give a label from a status
	 *
	 * @param	int		$mode   	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * @return  string   		   	Label
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Give a label from a status
	 *
	 *  @param	int		$status     Id status
	 *  @param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *  @return string      		Label
	 */
	public function LibStatut($status, $mode = 0)
	{
        // phpcs:enable
		global $langs;

		if ($mode == 0)
		{
			return $langs->trans($this->statuts[$status]);
		}
		elseif ($mode == 1)
		{
			return $langs->trans($this->statuts_short[$status]);
		}
		elseif ($mode == 2)
		{
			if ($status==0) return img_picto($langs->trans($this->statuts_short[$status]), 'statut5').' '.$langs->trans($this->statuts_short[$status]);
			elseif ($status==1) return img_picto($langs->trans($this->statuts_short[$status]), 'statut4').' '.$langs->trans($this->statuts_short[$status]);
		}
		elseif ($mode == 3)
		{
			if ($status==0 && ! empty($this->statuts_short[$status])) return img_picto($langs->trans($this->statuts_short[$status]), 'statut5');
			elseif ($status==1 && ! empty($this->statuts_short[$status])) return img_picto($langs->trans($this->statuts_short[$status]), 'statut4');
		}
		elseif ($mode == 4)
		{
			if ($status==0 && ! empty($this->statuts_short[$status])) return img_picto($langs->trans($this->statuts_short[$status]), 'statut5').' '.$langs->trans($this->statuts[$status]);
			elseif ($status==1 && ! empty($this->statuts_short[$status])) return img_picto($langs->trans($this->statuts_short[$status]), 'statut4').' '.$langs->trans($this->statuts[$status]);
		}
		elseif ($mode == 5)
		{
			if ($status==0 && ! empty($this->statuts_short[$status])) return $langs->trans($this->statuts_short[$status]).' '.img_picto($langs->trans($this->statuts_short[$status]), 'statut5');
			elseif ($status==1 && ! empty($this->statuts_short[$status])) return $langs->trans($this->statuts_short[$status]).' '.img_picto($langs->trans($this->statuts_short[$status]), 'statut4');
		}
	}

	/**
	 * Information on record
	 *
	 * @param	int		$id      Id of record
	 * @return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT e.rowid, e.datec, e.fk_user_author, e.tms, e.fk_user_mod, e.entity';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'establishment as e';
		$sql.= ' WHERE e.rowid = '.$id;

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
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 * Get on record Establishment
	 *
	 * @param	int		$id      Id of record
	 * @return	Object
	 */
	public function getEstablishment($id)
	{
		$sql = 'SELECT e.rowid, e.name, e.datec, e.fk_user_author, e.tms, e.fk_user_mod, e.entity';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'establishment as e';
		$sql.= ' WHERE e.rowid = '.$id;

		dol_syslog(get_class($this)."::fetch info", LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
			}
			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}

		return $obj;
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

        $result='';

        $link = '<a href="'.DOL_URL_ROOT.'/hrm/establishment/card.php?id='.$this->id.'">';
        $linkend='</a>';

        $picto='building';

        $label=$langs->trans("Show").': '.$this->name;

        if ($withpicto) $result.=($link.img_object($label, $picto).$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';
        if ($withpicto != 2) $result.=$link.$this->name.$linkend;
        return $result;
    }

	/**
     *  Return clicable name (with picto eventually)
     *
     *  @param		int		$id				Id of record
     *  @param      int     $withpicto      0=No picto, 1=Include picto into link, 2=Only picto
     *  @return     string                  String with URL
     */
    public function getNomUrlParent($id = 0, $withpicto = 0)
    {
        global $langs, $conf;

        $result='';

        $obj = $this->getEstablishment(($id>0)?$id:$conf->entity);

        $link = '<a href="'.DOL_URL_ROOT.'/hrm/establishment/card.php?id='.$obj->rowid.'">';
        $linkend='</a>';

        $picto='building';

        $label=$langs->trans("Show").': '.$obj->name;

        if ($withpicto) $result.=($link.img_object($label, $picto).$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';
        if ($withpicto != 2) $result.=$link.$obj->name.$linkend;
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
        if (! empty($this->country_code)) return $this->country_code;

        // We return country code of managed company
        if (! empty($mysoc->country_code)) return $mysoc->country_code;

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
        $this->ref = 'DEAAA';
    }
}
