<?php
/* Copyright (C) 2015		Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
	public $element='establishment';
	public $table_element='establishment';
	public $table_element_line = '';
	public $fk_element = 'fk_establishment';
	protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	var $rowid;

	var $name;
	var $address;
	var $zip;
	var $town;
	var $status;		// 0=open, 1=closed
	var $entity;

	var $statuts=array();
	var $statuts_short=array();

	/**
	 * Constructor
	 *
	 * @param	DoliDB		$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;

		$this->statuts_short = array(0 => 'Opened', 1 => 'Closed');
        $this->statuts = array(0 => 'Opened', 1 => 'Closed');

		return 1;
	}

	/**
	 *	Create object in database
	 *
	 *	@param		User	$user   User making creation
	 *	@return 	int				<0 if KO, >0 if OK
	 */
	function create($user)
	{
		global $conf;

		$error = 0;

		$now=dol_now();

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."establishment (";
		$sql.= "name";
		$sql.= ", address";
		$sql.= ", zip";
		$sql.= ", town";
		$sql.= ", status";
		$sql.= ", entity";
		$sql.= ", datec";
		$sql.= ", fk_user_author";
		$sql.= ") VALUES (";
		$sql.= " '".$this->name."'";
		$sql.= ", '".$this->address."'";
		$sql.= ", '".$this->zip."'";
		$sql.= ", '".$this->town."'";
		$sql.= ", ".$this->status;
		$sql.= ", ".$conf->entity;
		$sql.= ", '".$this->db->idate($now)."'";
		$sql.= ", ". $user->id;
		$sql.= ")";

		$this->db->begin();

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
			foreach ( $this->errors as $errmsg ) {
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
	function update($user)
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
		$sql .= " SET name = '".$this->name."'";
		$sql .= ", address = '".$this->address."'";
		$sql .= ", zip = '".$this->zip."'";
		$sql .= ", town = '".$this->town."'";
		$sql .= ", status = '".$this->status."'";
		$sql .= ", fk_user_mod = " . $user->id;
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
	function fetch($id)
	{
		$sql = "SELECT rowid, name, address, zip, town, status";
		$sql.= " FROM ".MAIN_DB_PREFIX."establishment";
		$sql.= " WHERE rowid = ".$id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ( $result )
		{
			$obj = $this->db->fetch_object($result);

			$this->id			= $obj->rowid;
			$this->name			= $obj->name;
			$this->address		= $obj->address;
			$this->zip			= $obj->zip;
			$this->town			= $obj->town;
			$this->status	    = $obj->status;

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
	function delete($id)
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
	function getLibStatus($mode=0)
	{
		return $this->LibStatus($this->status,$mode);
	}

	/**
	 *  Give a label from a status
	 *
	 *  @param	int		$status     Id status
	 *  @param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *  @return string      		Label
	 */
	function LibStatus($status,$mode=0)
	{
		global $langs;

		if ($mode == 0)
		{
			return $langs->trans($this->statuts[$status]);
		}
		if ($mode == 1)
		{
			return $langs->trans($this->statuts_short[$status]);
		}
		if ($mode == 2)
		{
			if ($status==0) return img_picto($langs->trans($this->statuts_short[$status]),'status4').' '.$langs->trans($this->statuts_short[$status]);
			if ($status==1) return img_picto($langs->trans($this->statuts_short[$status]),'status8').' '.$langs->trans($this->statuts_short[$status]);
		}
		if ($mode == 3)
		{
			if ($status==0 && ! empty($this->statuts_short[$status])) return img_picto($langs->trans($this->statuts_short[$status]),'status4');
			if ($status==1 && ! empty($this->statuts_short[$status])) return img_picto($langs->trans($this->statuts_short[$status]),'status8');
		}
		if ($mode == 4)
		{
			if ($status==0 && ! empty($this->statuts_short[$status])) return img_picto($langs->trans($this->statuts_short[$status]),'status4').' '.$langs->trans($this->statuts[$status]);
			if ($status==1 && ! empty($this->statuts_short[$status])) return img_picto($langs->trans($this->statuts_short[$status]),'status8').' '.$langs->trans($this->statuts[$status]);
		}
		if ($mode == 5)
		{
			if ($status==0 && ! empty($this->statuts_short[$status])) return $langs->trans($this->statuts_short[$status]).' '.img_picto($langs->trans($this->statuts_short[$status]),'status4');
			if ($status==1 && ! empty($this->statuts_short[$status])) return $langs->trans($this->statuts_short[$status]).' '.img_picto($langs->trans($this->statuts_short[$status]),'status8');
		}
	}

	/**
	 * Information on record
	 *
	 * @param	int		$id      Id of record
	 * @return	void
	 */
	function info($id)
	{
		$sql = 'SELECT e.rowid, e.datec, e.fk_user_author, e.tms, e.fk_user_mod';
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
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}
				if ($obj->fk_user_modif)
				{
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_mod);
					$this->user_modification = $muser;
				}
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
			}
			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

}
