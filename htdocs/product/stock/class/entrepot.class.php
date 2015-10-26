<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011	   Juanjo Menent        <jmenent@2byte.es>
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
 *  \file       htdocs/product/stock/class/entrepot.class.php
 *  \ingroup    stock
 *  \brief      Fichier de la classe de gestion des entrepots
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *  Class to manage warehouses
 */
class Entrepot extends CommonObject
{
	public $element='stock';
	public $table_element='entrepot';

	var $libelle;
	var $description;
	//! Statut 1 pour ouvert, 0 pour ferme
	var $statut;
	var $lieu;
	var $address;
	//! Code Postal
	var $zip;
	var $town;


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
		global $conf;

		// Si libelle non defini, erreur
		if ($this->libelle == '')
		{
			$this->error = "ErrorFieldRequired";
			return 0;
		}

		$now=dol_now();

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."entrepot (entity, datec, fk_user_author, label)";
		$sql .= " VALUES (".$conf->entity.",'".$this->db->idate($now)."',".$user->id.",'".$this->db->escape($this->libelle)."')";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$result=$this->db->query($sql);
		if ($result)
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."entrepot");
			if ($id > 0)
			{
				$this->id = $id;

				if ($this->update($id, $user) > 0)
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
		$this->libelle=trim($this->libelle);
		$this->description=trim($this->description);

		$this->lieu=trim($this->lieu);

		$this->address=trim($this->address);
	        $this->zip=trim($this->zip);
        	$this->town=trim($this->town);
		$this->country_id=($this->country_id > 0 ? $this->country_id : $this->country_id);

		$sql = "UPDATE ".MAIN_DB_PREFIX."entrepot ";
		$sql .= " SET label = '" . $this->db->escape($this->libelle) ."'";
		$sql .= ", description = '" . $this->db->escape($this->description) ."'";
		$sql .= ", statut = " . $this->statut;
		$sql .= ", lieu = '" . $this->db->escape($this->lieu) ."'";
		$sql .= ", address = '" . $this->db->escape($this->address) ."'";
		$sql .= ", zip = '" . $this->db->escape($this->zip) ."'";
		$sql .= ", town = '" . $this->db->escape($this->town) ."'";
		$sql .= ", fk_pays = " . $this->country_id;
		$sql .= " WHERE rowid = " . $id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
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
		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql1=$this->db->query($sql);

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_stock";
		$sql.= " WHERE fk_entrepot = " . $this->id;
		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql2=$this->db->query($sql);

		if ($resql1 && $resql2)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."entrepot";
			$sql.= " WHERE rowid = " . $this->id;

			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql1=$this->db->query($sql);

			// Update denormalized fields because we change content of produt_stock. Warning: Do not use "SET p.stock", does not works with pgsql
			$sql = "UPDATE ".MAIN_DB_PREFIX."product as p SET stock = (SELECT SUM(ps.reel) FROM ".MAIN_DB_PREFIX."product_stock as ps WHERE ps.fk_product = p.rowid)";

			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql2=$this->db->query($sql);

			if ($resql1 && $resql2)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				$this->error=$this->db->lasterror();
				return -2;
			}
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->lasterror();
			return -1;
		}

	}


	/**
	 *	Load warehouse data
	 *
	 *	@param		int		$id     Warehouse id
	 *	@param		string	$ref	Warehouse label
	 *	@return		int				>0 if OK, <0 if KO
	 */
	function fetch($id, $ref='')
	{
		global $conf;

		$sql  = "SELECT rowid, label, description, statut, lieu, address, zip, town, fk_pays as country_id";
		$sql .= " FROM ".MAIN_DB_PREFIX."entrepot";

		if ($id)
		{
			$sql.= " WHERE rowid = '".$id."'";
		}

		else
		{
			$sql.= " WHERE entity = " .$conf->entity;
			if ($ref) $sql.= " AND label = '".$this->db->escape($ref)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result) > 0)
			{
				$obj=$this->db->fetch_object($result);

				$this->id             = $obj->rowid;
				$this->ref            = $obj->rowid;
				$this->libelle        = $obj->label;
				$this->description    = $obj->description;
				$this->statut         = $obj->statut;
				$this->lieu           = $obj->lieu;
				$this->address        = $obj->address;
				$this->zip            = $obj->zip;
				$this->town           = $obj->town;
				$this->country_id     = $obj->country_id;

				include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	            $tmp=getCountry($this->country_id,'all');
				$this->country=$tmp['label'];
				$this->country_code=$tmp['code'];

				return 1;
			}
			else
			{
				return 0;
			}
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

		dol_syslog(get_class($this)."::info", LOG_DEBUG);
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
		$sql.= " WHERE entity IN (".getEntity('stock', 1).")";
		$sql.= " AND statut = ".$status;

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
	 *	Return number of unique different product into a warehosue
	 *
	 * 	@return		Array		Array('nb'=>Nb, 'value'=>Value)
	 */
	function nb_different_products()
	{
		$ret=array();

		$sql = "SELECT count(distinct p.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_stock as ps";
		$sql.= ", ".MAIN_DB_PREFIX."product as p";
		$sql.= " WHERE ps.fk_entrepot = ".$this->id;
		$sql.= " AND ps.fk_product = p.rowid";

		//print $sql;
		$result = $this->db->query($sql);
		if ($result)
		{
			$obj = $this->db->fetch_object($result);
			$ret['nb']=$obj->nb;
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
	 *	Return stock and value of warehosue
	 *
	 * 	@return		Array		Array('nb'=>Nb, 'value'=>Value)
	 */
	function nb_products()
	{
		$ret=array();

		$sql = "SELECT sum(ps.reel) as nb, sum(ps.reel * p.pmp) as value";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_stock as ps";
		$sql.= ", ".MAIN_DB_PREFIX."product as p";
		$sql.= " WHERE ps.fk_entrepot = ".$this->id;
		$sql.= " AND ps.fk_product = p.rowid";

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
		$langs->load("stocks");

		$result='';
        $label = '<u>' . $langs->trans("ShowWarehouse").'</u>';
        $label.= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->libelle;
        if (! empty($this->lieu))
            $label.= '<br><b>' . $langs->trans('LocationSummary').':</b> '.$this->lieu;

        $link='<a href="'.DOL_URL_ROOT.'/product/stock/card.php?id='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
        $linkend='</a>';

        if ($withpicto) $result.=($link.img_object($label, 'stock', 'class="classfortooltip"').$linkend.' ');
		$result.=$link.$this->libelle.$linkend;
		return $result;
	}

	/**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
     */
    function initAsSpecimen()
    {
        global $user,$langs,$conf,$mysoc;

        $now=dol_now();

        // Initialize parameters
        $this->id=0;
        $this->libelle = 'WAREHOUSE SPECIMEN';
        $this->description = 'WAREHOUSE SPECIMEN '.dol_print_date($now,'dayhourlog');
		$this->statut=1;
        $this->specimen=1;

		$this->lieu='Location test';
        $this->address='21 jump street';
        $this->zip='99999';
        $this->town='MyTown';
        $this->country_id=1;
        $this->country_code='FR';
    }
}
