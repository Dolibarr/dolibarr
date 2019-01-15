<?php
/* Copyright (C) 2014-2018  Alexandre Spangaro  <aspangaro@zendsi.com>
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
 *      \file       htdocs/core/class/fiscalyear.class.php
 *		\ingroup    fiscal year
 *		\brief      File of class to manage fiscal years
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 * Class to manage fiscal year
 */
class Fiscalyear extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element='fiscalyear';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element='accounting_fiscalyear';

	/**
	 * @var int    Name of subtable line
	 */
	public $table_element_line = '';

	/**
	 * @var int Field with ID of parent key if this field has a parent
	 */
	public $fk_element = '';

	/**
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int ID
	 */
	public $rowid;

	/**
	 * @var string fiscal year label
	 */
	public $label;

	public $date_start;
	public $date_end;
	public $datec;
	public $statut;		// 0=open, 1=closed

	/**
	 * @var int Entity
	 */
	public $entity;

	public $statuts=array();
	public $statuts_short=array();

	/**
	 * Constructor
	 *
	 * @param	DoliDB		$db		Database handler
	 */
	function __construct(DoliDB $db)
	{
		global $langs;

		$this->db = $db;

		$this->statuts_short = array(0 => 'Opened', 1 => 'Closed');
		$this->statuts = array(0 => 'Opened', 1 => 'Closed');
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

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."accounting_fiscalyear (";
		$sql.= "label";
		$sql.= ", date_start";
		$sql.= ", date_end";
		$sql.= ", statut";
		$sql.= ", entity";
		$sql.= ", datec";
		$sql.= ", fk_user_author";
		$sql.= ") VALUES (";
		$sql.= " '".$this->db->escape($this->label)."'";
		$sql.= ", '".$this->db->idate($this->date_start)."'";
		$sql.= ", ".($this->date_end ? "'".$this->db->idate($this->date_end)."'":"null");
		$sql.= ", 0";
		$sql.= ", ".$conf->entity;
		$sql.= ", '".$this->db->idate($now)."'";
		$sql.= ", ". $user->id;
		$sql.= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."accounting_fiscalyear");

			$result=$this->update($user);
			if ($result > 0)
			{
				$this->db->commit();
				return $this->id;
			}
			else
			{
				$this->error=$this->db->lasterror();
				$this->db->rollback();
				return $result;
			}
		}
		else
		{
			$this->error=$this->db->lasterror()." sql=".$sql;
			$this->db->rollback();
			return -1;
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
		if (empty($this->date_start) && empty($this->date_end))
		{
			$this->error='ErrorBadParameter';
			return -1;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."accounting_fiscalyear";
		$sql .= " SET label = '".$this->db->escape($this->label)."'";
		$sql .= ", date_start = '".$this->db->idate($this->date_start)."'";
		$sql .= ", date_end = ".($this->date_end ? "'".$this->db->idate($this->date_end)."'" : "null");
		$sql .= ", statut = '".$this->db->escape($this->statut?$this->statut:0)."'";
		$sql .= ", datec = " . ($this->datec != '' ? "'".$this->db->idate($this->datec)."'" : 'null');
		$sql .= ", fk_user_modif = " . $user->id;
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog($this->error, LOG_ERR);
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
	function fetch($id)
	{
		$sql = "SELECT rowid, label, date_start, date_end, statut";
		$sql.= " FROM ".MAIN_DB_PREFIX."accounting_fiscalyear";
		$sql.= " WHERE rowid = ".$id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ( $result )
		{
			$obj = $this->db->fetch_object($result);

			$this->id			= $obj->rowid;
			$this->ref			= $obj->rowid;
			$this->date_start	= $this->db->jdate($obj->date_start);
			$this->date_end		= $this->db->jdate($obj->date_end);
			$this->label		= $obj->label;
			$this->statut	    = $obj->statut;

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

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."accounting_fiscalyear WHERE rowid = ".$id;

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
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Give a label from a status
	 *
	 *  @param	int		$statut     Id status
	 *  @param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *  @return string      		Label
	 */
	function LibStatut($statut,$mode=0)
	{
		// phpcs:enable
		global $langs;

		if ($mode == 0)
		{
			return $langs->trans($this->statuts[$statut]);
		}
		elseif ($mode == 1)
		{
			return $langs->trans($this->statuts_short[$statut]);
		}
		elseif ($mode == 2)
		{
			if ($statut==0) return img_picto($langs->trans($this->statuts_short[$statut]),'statut4').' '.$langs->trans($this->statuts_short[$statut]);
			elseif ($statut==1) return img_picto($langs->trans($this->statuts_short[$statut]),'statut8').' '.$langs->trans($this->statuts_short[$statut]);
		}
		elseif ($mode == 3)
		{
			if ($statut==0 && ! empty($this->statuts_short[$statut])) return img_picto($langs->trans($this->statuts_short[$statut]),'statut4');
			elseif ($statut==1 && ! empty($this->statuts_short[$statut])) return img_picto($langs->trans($this->statuts_short[$statut]),'statut8');
		}
		elseif ($mode == 4)
		{
			if ($statut==0 && ! empty($this->statuts_short[$statut])) return img_picto($langs->trans($this->statuts_short[$statut]),'statut4').' '.$langs->trans($this->statuts[$statut]);
			elseif ($statut==1 && ! empty($this->statuts_short[$statut])) return img_picto($langs->trans($this->statuts_short[$statut]),'statut8').' '.$langs->trans($this->statuts[$statut]);
		}
		elseif ($mode == 5)
		{
			if ($statut==0 && ! empty($this->statuts_short[$statut])) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut4');
			elseif ($statut==1 && ! empty($this->statuts_short[$statut])) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut6');
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
		$sql = 'SELECT fy.rowid, fy.datec, fy.fk_user_author, fy.fk_user_modif,';
		$sql.= ' fy.tms';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'accounting_fiscalyear as fy';
		$sql.= ' WHERE fy.rowid = '.$id;

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
					$muser->fetch($obj->fk_user_modif);
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

	/**
	 *  Return the number of entries by fiscal year
	 *
	 *	@param	int		$datestart	Date start to scan
	 *	@param	int		$dateend	Date end to scan
	 *	@return	string				Number of entries
	 */
	function getAccountancyEntriesByFiscalYear($datestart, $dateend)
	{
		global $conf;

		$sql = "SELECT count(DISTINCT piece_num) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping ";
		$sql.= " WHERE doc_date >= '".$datestart."' and doc_date <= '".$dateend."'";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			$nb = $obj->nb;
		}
		else dol_print_error($this->db);

		return $nb;
	}

	/**
	 *  Return the number of movements by fiscal year
	 *
	 *	@param	int		$datestart	Date start to scan
	 *	@param	int		$dateend	Date end to scan
	 *	@return	string				Number of movements
	 */
	function getAccountancyMovementsByFiscalYear($datestart, $dateend)
	{
		global $conf;

		$sql = "SELECT count(rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping ";
		$sql.= " WHERE doc_date >= '".$datestart."' AND doc_date <= '".$dateend."'";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			$nb = $obj->nb;
		}
		else dol_print_error($this->db);

		return $nb;
	}
}
