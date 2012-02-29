<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011	   Juanjo Menent        <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *  \file       htdocs/product/stock/class/entrepot.class.php
 *  \ingroup    stock
 *  \brief      Fichier de la classe de gestion des entrepots
 */

require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");


/**
 *  Class to manage warehouses
 */
class Entrepot extends CommonObject
{
	public $element='label';
	public $table_element='entrepot';

	var $id;
	var $libelle;
	var $description;
	//! Statut 1 pour ouvert, 0 pour ferme
	var $statut;
	var $lieu;
	var $address;
	//! Code Postal
	var $cp;
	var $ville;
	var $pays_id;

	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;

		// List of short language codes for status
		$this->statuts[0] = 'Closed2';
		$this->statuts[1] = 'Opened';
	}

	/**
	 *	Creation d'un entrepot en base
	 *
	 *	@param		User	$user       Object user that create the warehouse
	 *	@return		int					>0 if OK, =<0 if KO
	 */
	function create($user)
	{
		// Si libelle non defini, erreur
		if ($this->libelle == '')
		{
			$this->error = "ErrorFieldRequired";
			return 0;
		}

		$now=dol_now();

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."entrepot (datec, fk_user_author, label)";
		$sql .= " VALUES (".$this->db->idate($now).",".$user->id.",'".$this->db->escape($this->libelle)."')";

		dol_syslog(get_class($this)."::create sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."entrepot");
			if ($id > 0)
			{
				$this->id = $id;

				if ( $this->update($id, $user) > 0)
				{
					$this->db->commit();
					return $id;
				}
				else
				{
					dol_syslog(get_class($this)."::create return -3");
					$this->db->rollback();
					return -3;
				}
			}
			else {
				$this->error="Failed to get insert id";
				dol_syslog(get_class($this)."::create return -2");
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog(get_class($this)."::create Error ".$this->db->error());
			$this->db->rollback();
			return -1;
		}

	}

	/**
	 *	Update properties of a warehouse
	 *
	 *	@param		int		$id     id of warehouse to modify
	 *	@param      User	$user	User object
	 *	@return		int				>0 if OK, <0 if KO
	 */
	function update($id, $user)
	{
		$this->libelle=$this->db->escape(trim($this->libelle));
		$this->description=$this->db->escape(trim($this->description));

		$this->lieu=$this->db->escape(trim($this->lieu));
		$this->address=$this->db->escape(trim($this->address));
		$this->cp=trim($this->cp);
		$this->ville=$this->db->escape(trim($this->ville));
		$this->zip=trim($this->cp);
		$this->town=$this->db->escape(trim($this->ville));
		$this->country_id=($this->country_id > 0 ? $this->country_id:$this->pays_id);

		$sql = "UPDATE ".MAIN_DB_PREFIX."entrepot ";
		$sql .= " SET label = '" . $this->libelle ."'";
		$sql .= ",description = '" . $this->description ."'";
		$sql .= ",statut = " . $this->statut ;
		$sql .= ",lieu = '" . $this->lieu ."'";
		$sql .= ",address = '" . $this->address ."'";
		$sql .= ",cp = '" . $this->zip ."'";
		$sql .= ",ville = '" . $this->town ."'";
		$sql .= ",fk_pays = " . $this->country_id;
		$sql .= " WHERE rowid = " . $id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::update ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *	Delete a warehouse
	 *
	 *	@param		User	$user		Object user that made deletion
	 *	@return		int					<0 if KO, >0 if OK
	 */
	function delete($user)
	{

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."stock_mouvement";
		$sql.= " WHERE fk_entrepot = " . $this->id;
		dol_syslog("Entrepot::delete sql=".$sql);
		$resql1=$this->db->query($sql);

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_stock";
		$sql.= " WHERE fk_entrepot = " . $this->id;
		dol_syslog("Entrepot::delete sql=".$sql);
		$resql2=$this->db->query($sql);

		if ($resql1 && $resql2)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."entrepot";
			$sql.= " WHERE rowid = " . $this->id;

			dol_syslog(get_class($this)."::delete sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				$this->error=$this->db->lasterror();
				dol_syslog(get_class($this)."::delete ".$this->error, LOG_ERR);
				return -1;
			}
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::delete ".$this->error, LOG_ERR);
			return -1;
		}

	}


	/**
	 *	Load warehouse data
	 *
	 *	@param		int		$id     Warehouse id
	 *	@return		int				>0 if OK, <0 if KO
	 */
	function fetch($id)
	{
		$sql  = "SELECT rowid, label, description, statut, lieu, address, cp as zip, ville as town, fk_pays as country_id";
		$sql .= " FROM ".MAIN_DB_PREFIX."entrepot";
		$sql .= " WHERE rowid = ".$id;

		dol_syslog(get_class($this)."::fetch sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$obj=$this->db->fetch_object($result);

			$this->id             = $obj->rowid;
			$this->ref            = $obj->rowid;
			$this->libelle        = $obj->label;
			$this->description    = $obj->description;
			$this->statut         = $obj->statut;
			$this->lieu           = $obj->lieu;
			$this->address        = $obj->address;
			$this->cp             = $obj->zip;
			$this->ville          = $obj->town;
			$this->pays_id        = $obj->country_id;
			$this->zip            = $obj->zip;
			$this->town           = $obj->town;
			$this->country_id     = $obj->country_id;

			include_once(DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php');
            $tmp=getCountry($this->country_id,'all');
			$this->pays=$tmp['label'];
			$this->pays_code=$tmp['code'];
			$this->country=$tmp['label'];
			$this->country_code=$tmp['code'];

			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}


	/**
	 * 	Load warehouse info data
	 *
	 *  @param	int		$id      warehouse id
	 *  @return	void
	 */
	function info($id)
	{
		$sql = "SELECT e.rowid, e.datec, e.tms as datem, e.fk_user_author";
		$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e";
		$sql.= " WHERE e.rowid = ".$id;

		dol_syslog(get_class($this)."::info sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				if ($obj->fk_user_author) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation     = $cuser;
				}

				if ($obj->fk_user_valid) {
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);

			}

			$this->db->free($result);

		}
		else
		{
	        dol_print_error($this->db);
		}
	}


	/**
	 *  Return list of all warehouses
	 *
	 *	@param	int		$status		Status
	 * 	@return array				Array list of warehouses
	 */
	function list_array($status=1)
	{
		$liste = array();

		$sql = "SELECT rowid, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."entrepot";
		$sql.= " WHERE statut = ".$status;

		$result = $this->db->query($sql);
		$i = 0;
		$num = $this->db->num_rows($result);
		if ( $result )
		{
			while ($i < $num)
			{
				$row = $this->db->fetch_row($result);
				$liste[$row[0]] = $row[1];
				$i++;
			}
			$this->db->free($result);
		}
		return $liste;
	}

	/**
	 *	Return stock and value of warehosue
	 *
	 * 	@return		Array		Array('nb'=>Nb, 'value'=>Value)
	 */
	function nb_products()
	{
		global $conf,$user;

		$ret=array();

		$sql = "SELECT sum(ps.reel) as nb, sum(ps.reel * ps.pmp) as value";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_stock as ps, ".MAIN_DB_PREFIX."product as p";
		$sql .= " WHERE ps.fk_entrepot = ".$this->id." AND ps.fk_product=p.rowid";

		//print $sql;
		$result = $this->db->query($sql);
		if ($result)
		{
			$obj = $this->db->fetch_object($result);
			$ret['nb']=$obj->nb;
			$ret['value']=$obj->value;
			$this->db->free($result);
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}

		return $ret;
	}

	/**
	 *	Return label of status of object
	 *
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return     string      		Label of status
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *	Return label of a given status
	 *
	 *	@param	int		$statut     Status
	 *	@param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return string      		Label of status
	 */
	function LibStatut($statut,$mode=0)
	{
		global $langs;
		$langs->load('stocks');

		if ($mode == 0)
		{
			$prefix='';
			if ($statut == 0) return $langs->trans($this->statuts[$statut]);
			if ($statut == 1) return $langs->trans($this->statuts[$statut]);
		}
		if ($mode == 1)
		{
			$prefix='Short';
			if ($statut == 0) return $langs->trans($this->statuts[$statut]);
			if ($statut == 1) return $langs->trans($this->statuts[$statut]);
		}
		if ($mode == 2)
		{
			$prefix='Short';
			if ($statut == 0) return img_picto($langs->trans($this->statuts[$statut]),'statut5').' '.$langs->trans($this->statuts[$statut]);
			if ($statut == 1) return img_picto($langs->trans($this->statuts[$statut]),'statut4').' '.$langs->trans($this->statuts[$statut]);
		}
		if ($mode == 3)
		{
			$prefix='Short';
			if ($statut == 0) return img_picto($langs->trans($this->statuts[$statut]),'statut5');
			if ($statut == 1) return img_picto($langs->trans($this->statuts[$statut]),'statut4');
		}
		if ($mode == 4)
		{
			if ($statut == 0) return img_picto($langs->trans($this->statuts[$statut]),'statut5').' '.$langs->trans($this->statuts[$statut]);
			if ($statut == 1) return img_picto($langs->trans($this->statuts[$statut]),'statut4').' '.$langs->trans($this->statuts[$statut]);
		}
		if ($mode == 5)
		{
			$prefix='Short';
			if ($statut == 0) return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),'statut5');
			if ($statut == 1) return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),'statut4');
		}
	}


	/**
	 *	Return clickable name (possibility with the pictogram)
	 *
	 *	@param		int		$withpicto		with pictogram
	 *	@param		string	$option			What point the link
	 *	@return		string					String with URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';

		$lien='<a href="'.DOL_URL_ROOT.'/product/stock/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

		if ($withpicto) $result.=($lien.img_object($langs->trans("ShowStock"),'stock').$lienfin.' ');
		$result.=$lien.$this->libelle.$lienfin;
		return $result;
	}

}
?>
