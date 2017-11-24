<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * 	\file       htdocs/societe/class/address.class.php
 * 	\ingroup    societe
 *  \brief      File of class to manage addresses. This class is deprecated.
 */


/**
 *  Class to manage addresses
 *
 *  @deprecated This class is dedicated to a not supported and deprecated feature.
 */
class Address
{
	protected $db;

	public $id;
	public $type;
	public $label;
	public $socid;
	public $name;
	public $address;
	public $zip;
	public $town;
	public $country_id;
	public $country_code;
	public $phone;
	public $fax;
	public $note;

	/**
	 * Adresses liees a la societe
	 * @var array
	 */
	public $lines;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @deprecated
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *  Create address into database
	 *
	 *  @param	int		$socid      Company socid
	 *  @param  User	$user       Object user making creation
	 *  @return int         		0 if OK, < 0 if KO
	 */
	function create($socid, $user='')
	{
		global $langs,$conf;

		// Nettoyage parametres
		$this->name  = trim($this->name);
		$this->label = trim($this->label);

		dol_syslog(get_class($this)."::create label=".$this->label);

		$this->db->begin();

		$result = $this->verify();

		if ($result >= 0)
		{
			$now=dol_now();

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_address (label, fk_soc, name, datec, fk_user_creat) ";
			$sql .= " VALUES ('".$this->db->escape($this->label)."', '".$socid."', '".$this->db->escape($this->name)."', '".$this->db->idate($now)."', '".$user->id."')";

			$result=$this->db->query($sql);
			if ($result)
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."societe_address");

				$ret = $this->update($this->id, $socid, $user);

				if ($ret >= 0)
				{
					dol_syslog(get_class($this)."::create success id=".$this->id);
					$this->db->commit();
					return 0;
				}
				else
				{
					dol_syslog(get_class($this)."::create echec update");
					$this->db->rollback();
					return -3;
				}
			}
			else

			{
				if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
				{

					$this->error=$langs->trans("ErrorCompanyNameAlreadyExists",$this->name);
				}

				$this->db->rollback();
				return -2;
			}

		}
		else
		{
			$this->db->rollback();
			dol_syslog(get_class($this)."::create echec verify sql=$sql");
			return -1;
		}
	}


	/**
	 *  Verification lors de la modification de l'adresse
	 *
	 *  @return		int	    0 if OK, <0 if KO
	 */
	function verify()
	{
		$this->label = trim($this->label);
		$this->name  = trim($this->name);
		$result = 0;
		if (!$this->name || !$this->label)
		{
			$this->error = "The name of company and the label can not be empty.\n";
			$result = -2;
		}
		return $result;
	}


	/**
	 *  Mise a jour des parametres de l'adresse
	 *
	 *  @param	int		$id             id address
	 *  @param	int		$socid			id third party
	 *  @param  User	$user           Utilisateur qui demande la mise a jour
	 *  @return int             		<0 if KO, >=0 if OK
	 */
	function update($id, $socid, $user='')
	{
		global $langs;

		// Clean parameters
		$this->fk_soc		= $socid;
		$this->label		= trim($this->label);
		$this->name			= trim($this->name);
		$this->address		= trim($this->address);
		$this->zip			= trim($this->zip);
		$this->town			= trim($this->town);
		$this->country_id	= trim($this->country_id);
		$this->phone		= trim($this->phone);
		$this->phone		= preg_replace("/\s/","",$this->phone);
		$this->phone		= preg_replace("/\./","",$this->phone);
		$this->fax			= trim($this->fax);
		$this->fax			= preg_replace("/\s/","",$this->fax);
		$this->fax			= preg_replace("/\./","",$this->fax);
		$this->note			= trim($this->note);

		$result = $this->verify();		// Verifie que name et label obligatoire

		if ($result >= 0)
		{
			dol_syslog(get_class($this)."::Update verify ok");

			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."societe_address";
			$sql.= " SET label = '" . $this->db->escape($this->label) ."'"; // Champ obligatoire
			$sql.= ", name = '" . $this->db->escape($this->name) ."'"; // Champ obligatoire
			$sql.= ", address = ".($this->address?"'".$this->db->escape($this->address)."'":"null");
			$sql.= ", zip = ".($this->zip?"'".$this->db->escape($this->zip)."'":"null");
			$sql.= ", town = ".($this->town?"'".$this->db->escape($this->town)."'":"null");
			$sql.= ", fk_pays = '" . ($this->country_id?$this->db->escape($this->country_id):'0') ."'";
			$sql.= ", note = ".($this->note?"'".$this->db->escape($this->note)."'":"null");
			$sql.= ", phone = ".($this->phone?"'".$this->db->escape($this->phone)."'":"null");
			$sql.= ", fax = ".($this->fax?"'".$this->db->escape($this->fax)."'":"null");
			if ($user) $sql .= ",fk_user_modif = '".$user->id."'";
			$sql .= " WHERE fk_soc = '" . $socid ."' AND rowid = '" . $this->db->escape($id) ."'";

			dol_syslog(get_class($this)."::Update", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				dol_syslog(get_class($this)."::Update success");
				$this->db->commit();
				return 1;
			}
			else
			{
				if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
				{

					$this->error=$langs->trans("ErrorDuplicateField",$this->name);
					$result=-1;
				}
				else
				{
					$this->error=$this->db->lasterror();
					$result=-2;
				}
				$this->db->rollback();
				return $result;
			}
		}

	}

	/**
	 *  Charge depuis la base toutes les adresses d'une societe
	 *
	 *  @param	int		$socid       Id de la societe a charger en memoire
	 *  @param  User	$user        Objet de l'utilisateur
	 *  @return int 			     >0 si ok, <0 si ko
	 */
	function fetch_lines($socid, $user=null)
	{
		global $langs, $conf;

		$sql = 'SELECT rowid, nom as name, client, fournisseur';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'societe';
		$sql .= ' WHERE rowid = '.$socid;

		$resqlsoc=$this->db->query($sql);
		if ($resqlsoc)
		{
			if ($this->db->num_rows($resqlsoc))
			{
				$obj = $this->db->fetch_object($resqlsoc);

				$this->socname 		= $obj->name;
				$this->socid		= $obj->rowid;
				$this->id			= $obj->rowid;
				$this->client		= $obj->client;
				$this->fournisseur	= $obj->fournisseur;
			}

			$this->db->free($resqlsoc);

            $this->lines = array();

            // Adresses liees a la societe
			if ($this->socid)
			{
				$sql = 'SELECT a.rowid as id, a.label, a.name, a.address, a.datec as date_creation, a.tms as date_modification, a.fk_soc';
				$sql .= ', a.zip, a.town, a.note, a.fk_pays as country_id, a.phone, a.fax';
				$sql .= ', c.code as country_code, c.label as country';
				$sql .= ' FROM '.MAIN_DB_PREFIX.'societe_address as a';
				$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON a.fk_pays = c.rowid';
				$sql .= ' WHERE a.fk_soc = '.$this->socid;

				$resql=$this->db->query($sql);
				if ($resql)
				{
					$num = $this->db->num_rows($resql);
					$i = 0;
					while ($i < $num)
					{
						$objp = $this->db->fetch_object($resql);

						$line = new AddressLine($this->db);

						$line->id				= $objp->id;
						$line->date_creation	 = $this->db->jdate($objp->date_creation);
						$line->date_modification = $this->db->jdate($objp->date_modification);
						$line->label			= $objp->label;
						$line->name				= $objp->name;
						$line->address			= $objp->address;
						$line->zip				= $objp->zip;
						$line->town				= $objp->town;
						$line->country_id		= $objp->country_id;
						$line->country_code		= $objp->country_id?$objp->country_code:'';
						$line->country			= $objp->country_id?($langs->trans('Country'.$objp->country_code)!='Country'.$objp->country_code?$langs->trans('Country'.$objp->country_code):$objp->country):'';
						$line->phone			= $objp->phone;
						$line->fax				= $objp->fax;
						$line->note				= $objp->note;

						$this->lines[$i]		= $line;
						$i++;
					}
					$this->db->free($resql);
					return $num;
				}
				else
				{
					dol_syslog(get_class($this).'::Fetch Erreur: aucune adresse', LOG_ERR);
					return 0;
				}
			}
			else
			{
				dol_syslog(get_class($this).'::Fetch Erreur: societe inconnue', LOG_ERR);
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->error();
		}
	}

	/**
	 *  Charge depuis la base l'objet adresse
	 *
	 *  @param	int		$id       	Id de l'adresse a charger en memoire
	 *  @param  User	$user       Objet de l'utilisateur
	 *  @return int 				>0 si ok, <0 si ko
	 */
	function fetch_address($id, $user=null)
	{
		global $langs;
		global $conf;

		$sql = 'SELECT a.rowid, a.fk_soc, a.label, a.name, a.address, a.datec as date_creation, a.tms as date_modification';
		$sql .= ', a.zip, a.town, a.note, a.fk_pays as country_id, a.phone, a.fax';
		$sql .= ', c.code as country_code, c.label as country';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'societe_address as a';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON a.fk_pays = c.rowid';
		$sql .= ' WHERE a.rowid = '.$id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id				= $obj->rowid;
				$this->socid			= $obj->fk_soc;

				$this->date_modification		= $this->db->jdate($obj->date_modification);
				$this->date_creation 	= $this->db->jdate($obj->date_creation);

				$this->label 			= $obj->label;
				$this->name 			= $obj->name;
				$this->address 			= $obj->address;
				$this->zip 				= $obj->zip;
				$this->town 			= $obj->town;

				$this->country_id 		= $obj->country_id;
				$this->country_code 	= $obj->country_id?$obj->country_code:'';
				$this->country			= $obj->country_id?($langs->trans('Country'.$obj->country_code)!='Country'.$obj->country_code?$langs->trans('Country'.$obj->country_code):$obj->country):'';

				$this->phone			= $obj->phone;
				$this->fax				= $obj->fax;
				$this->note				= $obj->note;

				$result = 1;
			}
			else
			{
				dol_syslog('Erreur Societe::Fetch aucune adresse avec id='.$this->id.' - '.$sql);
				$this->error='Erreur Societe::Fetch aucune adresse avec id='.$this->id.' - '.$sql;
				$result = -2;
			}

			$this->db->free($resql);
		}
		else
		{
			dol_syslog('Erreur Societe::Fetch echec', LOG_DEBUG);
			dol_syslog('Erreur Societe::Fetch '.$this->db->error());
			$this->error=$this->db->error();
			$result = -3;
		}

		return $result;
	}


	/**
	 * 	Suppression d'une adresse
	 *
	 *  @param	int		$id      id de la societe a supprimer
	 *  @param	int		$socid	id third party
	 *  @return	<0 KO >0 OK
	 */
	function delete($id,$socid)
	{
		dol_syslog("Address::Delete");

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."societe_address";
		$sql.= " WHERE rowid = ".$id;
		$sql.= " AND fk_soc = ".$socid;

		$result = $this->db->query($sql);

		if (!$result) {
			return -1;
		}

		return 1;
	}

	/**
	 *  Return name of address with link (and eventually picto)
	 *	Use $this->id, $this->label, $this->socid
	 *
	 *	@param		int			$withpicto		Include picto with link
	 *	@param		string		$option			Where the link point to
	 *	@return		string						String with URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';
        $label = $langs->trans("ShowAddress").': '.$this->label;

        $link = '<a href="'.DOL_URL_ROOT.'/comm/address.php?id='.$this->id.'&socid='.$this->socid.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend='</a>';

        if ($withpicto) $result.=($link.img_object($langs->trans("ShowAddress").': '.$this->label, 'address', 'class="classfortooltip"').$linkend.' ');
		$result.=$link.$this->label.$linkend;
		return $result;
	}


	/**
	 * 	Charge les informations d'ordre info dans l'objet societe
	 *
	 *  @param  int		$id     id de la societe a charger
	 *  @return	void
	 */
	function info($id)
	{
		$sql = "SELECT s.rowid, s.nom as name, datec as date_creation, tms as date_modification,";
		$sql.= " fk_user_creat, fk_user_modif";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql.= " WHERE s.rowid = ".$id;

		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				if ($obj->fk_user_creat) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_creat);
					$this->user_creation     = $cuser;
				}

				if ($obj->fk_user_modif) {
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_modif);
					$this->user_modification = $muser;
				}
				$this->ref			     = $obj->name;
				$this->date_creation     = $this->db->jdate($obj->date_creation);
				$this->date_modification = $this->db->jdate($obj->date_modification);
			}

			$this->db->free($result);

		}
		else
		{
			dol_print_error($this->db);
		}
	}

}


/**
 *  Class to manage one address line
 */
class AddressLine
{
	protected $db;
	public $id;
	public $date_creation;
	public $date_modification;
	public $label;
	public $name;
	public $address;
	public $zip;
	public $town;
	public $country_id;
	public $country_code;
	public $country;
	public $phone;
	public $fax;
	public $note;


	/**
	 *  Constructor
	 *
	 *  @param	DoliDB		$db     Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}
}
