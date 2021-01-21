<?php
/* Copyright (C) 2003		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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
 *      \file       htdocs/compta/deplacement/class/deplacement.class.php
 *      \ingroup    deplacement
 *      \brief      File of class to manage trips
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 *		Class to manage trips and working credit notes
 */
class Deplacement extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'deplacement';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'deplacement';

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = '';

	/**
	 * @var string Fieldname with ID of parent key if this field has a parent
	 */
	public $fk_element = '';

	/**
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 0;

	/**
	 * Date creation record (datec)
	 *
	 * @var integer
	 */
	public $datec;

	/**
	 * Date (dated)
	 *
	 * @var integer
	 */
	public $dated;

	/**
	 * @var int ID
	 */
	public $fk_user_author;

	/**
	 * @var int User ID
	 */
	public $fk_user;

	/**
	 * @var string km value formatted
	 */
	public $km;

	/**
	 * @var int Thirdparty id
	 */
	public $socid;

	/**
	 * @var int Status 0=draft, 1=validated, 2=Refunded
	 */
	public $statut;
	public $extraparams = array();

	public $statuts = array();
	public $statuts_short = array();

	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;

	/**
	 * Validated status
	 */
	const STATUS_VALIDATED = 1;

	/**
	 * Refunded status
	 */
	const STATUS_REFUNDED = 2;

	/**
	 * Constructor
	 *
	 * @param	DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->statuts_short = array(0 => 'Draft', 1 => 'Validated', 2 => 'Refunded');
		$this->statuts = array(0 => 'Draft', 1 => 'Validated', 2 => 'Refunded');
	}

	/**
	 * Create object in database
	 * TODO Add ref number
	 *
	 * @param	User	$user	User that creates
	 * @return 	int				<0 if KO, >0 if OK
	 */
	public function create($user)
	{
		global $conf;

		// Check parameters
		if (empty($this->type) || $this->type < 0)
		{
			$this->error = 'ErrorBadParameter';
			return -1;
		}
		if (empty($this->fk_user) || $this->fk_user < 0)
		{
			$this->error = 'ErrorBadParameter';
			return -1;
		}

		$now = dol_now();

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."deplacement (";
		$sql .= "datec";
		//$sql.= ", dated";
		$sql .= ", entity";
		$sql .= ", fk_user_author";
		$sql .= ", fk_user";
		$sql .= ", type";
		$sql .= ", note_private";
		$sql .= ", note_public";
		$sql .= ", fk_projet";
		$sql .= ", fk_soc";
		$sql .= ") VALUES (";
		$sql .= " '".$this->db->idate($now)."'";
		$sql .= ", ".$conf->entity;
		$sql .= ", ".$user->id;
		$sql .= ", ".$this->fk_user;
		$sql .= ", '".$this->db->escape($this->type)."'";
		$sql .= ", ".($this->note_private ? "'".$this->db->escape($this->note_private)."'" : "null");
		$sql .= ", ".($this->note_public ? "'".$this->db->escape($this->note_public)."'" : "null");
		$sql .= ", ".($this->fk_project > 0 ? $this->fk_project : 0);
		$sql .= ", ".($this->fk_soc > 0 ? $this->fk_soc : "null");
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."deplacement");

			// Call trigger
			$result = $this->call_trigger('DEPLACEMENT_CREATE', $user);
			if ($result < 0)
			{
				$this->db->rollback();
				return -2;
			}
			// End call triggers

			$result = $this->update($user);
			if ($result > 0)
			{
				$this->db->commit();
				return $this->id;
			} else {
				$this->error = $this->db->error();
				$this->db->rollback();
				return $result;
			}
		} else {
			$this->error = $this->db->error()." sql=".$sql;
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
	public function update($user)
	{
		global $langs;

		// Clean parameters
		$this->km = price2num($this->km);

		// Check parameters
		if (!is_numeric($this->km)) $this->km = 0;
		if (empty($this->date))
		{
			$this->error = 'ErrorBadParameter';
			return -1;
		}
		if (empty($this->type) || $this->type < 0)
		{
			$this->error = 'ErrorBadParameter';
			return -1;
		}
		if (empty($this->fk_user) || $this->fk_user < 0)
		{
			$this->error = 'ErrorBadParameter';
			return -1;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."deplacement ";
		$sql .= " SET km = ".$this->km; // This is a distance or amount
		$sql .= " , dated = '".$this->db->idate($this->date)."'";
		$sql .= " , type = '".$this->db->escape($this->type)."'";
		$sql .= " , fk_statut = '".$this->db->escape($this->statut)."'";
		$sql .= " , fk_user = ".$this->fk_user;
		$sql .= " , fk_user_modif = ".$user->id;
		$sql .= " , fk_soc = ".($this->socid > 0 ? $this->socid : 'null');
		$sql .= " , note_private = ".($this->note_private ? "'".$this->db->escape($this->note_private)."'" : "null");
		$sql .= " , note_public = ".($this->note_public ? "'".$this->db->escape($this->note_public)."'" : "null");
		$sql .= " , fk_projet = ".($this->fk_project > 0 ? $this->fk_project : 0);
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
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
	 * Load an object from database
	 *
	 * @param	int		$id		Id of record to load
	 * @param	string	$ref	Ref of record
	 * @return	int				<0 if KO, >0 if OK
	 */
	public function fetch($id, $ref = '')
	{
		$sql = "SELECT rowid, fk_user, type, fk_statut, km, fk_soc, dated, note_private, note_public, fk_projet as fk_project, extraparams";
		$sql .= " FROM ".MAIN_DB_PREFIX."deplacement";
		$sql .= " WHERE entity IN (".getEntity('deplacement').")";
		if ($ref) $sql .= " AND ref ='".$this->db->escape($ref)."'";
		else $sql .= " AND rowid = ".$id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$obj = $this->db->fetch_object($result);

			$this->id = $obj->rowid;
			$this->ref = $obj->rowid;
			$this->date			= $this->db->jdate($obj->dated);
			$this->fk_user = $obj->fk_user;
			$this->socid		= $obj->fk_soc;
			$this->km = $obj->km;
			$this->type			= $obj->type;
			$this->statut	    = $obj->fk_statut;
			$this->note_private = $obj->note_private;
			$this->note_public	= $obj->note_public;
			$this->fk_project	= $obj->fk_project;

			$this->extraparams	= (array) json_decode($obj->extraparams, true);

			return 1;
		} else {
			$this->error = $this->db->error();
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

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."deplacement WHERE rowid = ".$id;

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 * Retourne le libelle du statut
	 *
	 * @param	int		$mode   	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * @return  string   		   	Libelle
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->statut, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Renvoi le libelle d'un statut donne
	 *
	 *  @param	int		$status     Id status
	 *  @param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *  @return string      		Libelle
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs;

		if ($mode == 0)
		{
			return $langs->trans($this->statuts[$status]);
		} elseif ($mode == 1)
		{
			return $langs->trans($this->statuts_short[$status]);
		} elseif ($mode == 2)
		{
			if ($status == 0) return img_picto($langs->trans($this->statuts_short[$status]), 'statut0').' '.$langs->trans($this->statuts_short[$status]);
			elseif ($status == 1) return img_picto($langs->trans($this->statuts_short[$status]), 'statut4').' '.$langs->trans($this->statuts_short[$status]);
			elseif ($status == 2) return img_picto($langs->trans($this->statuts_short[$status]), 'statut6').' '.$langs->trans($this->statuts_short[$status]);
		} elseif ($mode == 3)
		{
			if ($status == 0 && !empty($this->statuts_short[$status])) return img_picto($langs->trans($this->statuts_short[$status]), 'statut0');
			elseif ($status == 1 && !empty($this->statuts_short[$status])) return img_picto($langs->trans($this->statuts_short[$status]), 'statut4');
			elseif ($status == 2 && !empty($this->statuts_short[$status])) return img_picto($langs->trans($this->statuts_short[$status]), 'statut6');
		} elseif ($mode == 4)
		{
			if ($status == 0 && !empty($this->statuts_short[$status])) return img_picto($langs->trans($this->statuts_short[$status]), 'statut0').' '.$langs->trans($this->statuts[$status]);
			elseif ($status == 1 && !empty($this->statuts_short[$status])) return img_picto($langs->trans($this->statuts_short[$status]), 'statut4').' '.$langs->trans($this->statuts[$status]);
			elseif ($status == 2 && !empty($this->statuts_short[$status])) return img_picto($langs->trans($this->statuts_short[$status]), 'statut6').' '.$langs->trans($this->statuts[$status]);
		} elseif ($mode == 5)
		{
			if ($status == 0 && !empty($this->statuts_short[$status])) return $langs->trans($this->statuts_short[$status]).' '.img_picto($langs->trans($this->statuts_short[$status]), 'statut0');
			elseif ($status == 1 && !empty($this->statuts_short[$status])) return $langs->trans($this->statuts_short[$status]).' '.img_picto($langs->trans($this->statuts_short[$status]), 'statut4');
			elseif ($status == 2 && !empty($this->statuts_short[$status])) return $langs->trans($this->statuts_short[$status]).' '.img_picto($langs->trans($this->statuts_short[$status]), 'statut6');
		}
	}

	/**
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param		int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@return		string					Chaine avec URL
	 */
	public function getNomUrl($withpicto = 0)
	{
		global $langs;

		$result = '';
		$label = $langs->trans("Show").': '.$this->ref;

		$link = '<a href="'.DOL_URL_ROOT.'/compta/deplacement/card.php?id='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend = '</a>';

		$picto = 'trip';


		if ($withpicto) $result .= ($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
		if ($withpicto && $withpicto != 2) $result .= ' ';
		if ($withpicto != 2) $result .= $link.$this->ref.$linkend;
		return $result;
	}


	/**
	 * List of types
	 *
	 * @param	int		$active		Active or not
	 * @return	array
	 */
	public function listOfTypes($active = 1)
	{
		global $langs;

		$ret = array();

		$sql = "SELECT id, code, label";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_fees";
		$sql .= " WHERE active = ".$active;

		dol_syslog(get_class($this)."::listOfTypes", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				$ret[$obj->code] = (($langs->trans($obj->code) != $obj->code) ? $langs->trans($obj->code) : $obj->label);
				$i++;
			}
		} else {
			dol_print_error($this->db);
		}

		return $ret;
	}

	/**
	 * Information on record
	 *
	 * @param	int		$id      Id of record
	 * @return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT c.rowid, c.datec, c.fk_user_author, c.fk_user_modif,';
		$sql .= ' c.tms';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'deplacement as c';
		$sql .= ' WHERE c.rowid = '.$id;

		dol_syslog(get_class($this).'::info', LOG_DEBUG);
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
		} else {
			dol_print_error($this->db);
		}
	}
}
