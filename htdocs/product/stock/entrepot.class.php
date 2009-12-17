<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *  \file       htdocs/product/stock/entrepot.class.php
 *  \ingroup    stock
 *  \brief      Fichier de la classe de gestion des entrepots
 *  \version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/commonobject.class.php");


/**
 *  \class      Entrepot
 *  \brief      Classe permettant la gestion des entrepots
 */

class Entrepot extends CommonObject
{
	var $db;
	var $error;
	var $element='label';
	var $table_element='entrepot';

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
	 *    \brief      Constructeur de l'objet entrepot
	 *    \param      DB      Handler d'acc�s � la base de donn�e
	 */
	function Entrepot($DB)
	{
		$this->db = $DB;

		// List of short language codes for status
		$this->statuts[0] = 'Closed2';
		$this->statuts[1] = 'Opened';
	}

	/**
	 *    \brief      Creation d'un entrepot en base
	 *    \param      Objet user qui cree l'entrepot
	 */
	function create($user)
	{
		// Si libelle non defini, erreur
		if ($this->libelle == '')
		{
			$this->error = "ErrorFieldRequired";
			return 0;
		}

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."entrepot (datec, fk_user_author, label)";
		$sql .= " VALUES (".$this->db->idate(mktime()).",".$user->id.",'".addslashes($this->libelle)."')";

		dol_syslog("Entrepot::create sql=".$sql);
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
					dol_syslog("Entrepot::Create return -3");
					$this->db->rollback();
					return -3;
				}
			}
			else {
				$this->error="Failed to get insert id";
				dol_syslog("Entrepot::Create return -2");
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("Entrepot::Create Error ".$this->db->error());
			$this->db->rollback();
			return -1;
		}

	}

	/**
	 *    \brief      Update properties of a warehouse
	 *    \param      id      id of warehouse to modify
	 *    \param      user
	 */
	function update($id, $user)
	{
		$this->libelle=addslashes(trim($this->libelle));
		$this->description=addslashes(trim($this->description));

		$this->lieu=addslashes(trim($this->lieu));
		$this->address=addslashes(trim($this->address));
		$this->cp=trim($this->cp);
		$this->ville=addslashes(trim($this->ville));
		$this->pays_id=trim($this->pays_id?$this->pays_id:0);

		$sql = "UPDATE ".MAIN_DB_PREFIX."entrepot ";
		$sql .= " SET label = '" . $this->libelle ."'";
		$sql .= ",description = '" . $this->description ."'";
		$sql .= ",statut = " . $this->statut ;
		$sql .= ",lieu = '" . $this->lieu ."'";
		$sql .= ",address = '" . $this->address ."'";
		$sql .= ",cp = '" . $this->cp ."'";
		$sql .= ",ville = '" . $this->ville ."'";
		$sql .= ",fk_pays = " . $this->pays_id;
		$sql .= " WHERE rowid = " . $id;

		$this->db->begin();

		dol_syslog("Entrepot::update sql=".$sql);
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
			dol_syslog("Entrepot::update ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *    	\brief      Delete a warehouse
	 *    	\param      user
	 * 		\return		int		<0 if KO, >0 if OK
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
		
			dol_syslog("Entrepot::delete sql=".$sql);
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
				dol_syslog("Entrepot::delete ".$this->error, LOG_ERR);
				return -1;
			}
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->lasterror();
			dol_syslog("Entrepot::delete ".$this->error, LOG_ERR);
			return -1;
		}
		
	}


	/**
	 *    \brief      Recuperation de la base d'un entrepot
	 *    \param      id      id de l'entrepot a recuperer
	 */
	function fetch($id)
	{
		$sql  = "SELECT rowid, label, description, statut, lieu, address, cp, ville, fk_pays";
		$sql .= " FROM ".MAIN_DB_PREFIX."entrepot";
		$sql .= " WHERE rowid = ".$id;

		dol_syslog("Entrepot::fetch sql=".$sql);
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
			$this->cp             = $obj->cp;
			$this->ville          = $obj->ville;
			$this->pays_id        = $obj->fk_pays;

			if ($this->pays_id)
			{
				$sqlp = "SELECT libelle from ".MAIN_DB_PREFIX."c_pays where rowid = ".$this->pays_id;
				$resql=$this->db->query($sqlp);
				if ($resql)
				{
					$objp = $this->db->fetch_object($resql);
				}
				else
				{
					dol_print_error($db);
				}
				$this->pays=$objp->libelle;
			}

			$this->db->free($result);
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}


	/*
	 * \brief     Charge les informations d'ordre info dans l'objet entrepot
	 * \param     id      id de l'entrepot a charger
	 */
	function info($id)
	{
		$sql  = "SELECT e.rowid, ".$this->db->pdate("datec")." as datec,";
		$sql .= " ".$this->db->pdate("tms")." as datem,";
		$sql .= " fk_user_author";
		$sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e";
		$sql .= " WHERE e.rowid = ".$id;

		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				if ($obj->fk_user_author) {
					$cuser = new User($this->db, $obj->fk_user_author);
					$cuser->fetch();
					$this->user_creation     = $cuser;
				}

				if ($obj->fk_user_valid) {
					$vuser = new User($this->db, $obj->fk_user_valid);
					$vuser->fetch();
					$this->user_validation = $vuser;
				}

				$this->date_creation     = $obj->datec;
				$this->date_modification = $obj->datem;

			}

			$this->db->free($result);

		}
		else
		{
	  dol_print_error($this->db);
		}
	}


	/**
	 *    \brief      Renvoie la liste des entrep�ts ouverts
	 */
	function list_array()
	{
		$liste = array();

		$sql = "SELECT rowid, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."entrepot";
		$sql.= " WHERE statut = 1";

		$result = $this->db->query($sql) ;
		$i = 0;
		$num = $this->db->num_rows();

		if ( $result )
		{
			while ($i < $num)
			{
				$row = $this->db->fetch_row($i);
				$liste[$row[0]] = $row[1];
				$i++;
			}
			$this->db->free();
		}
		return $liste;
	}

	/**
	 *    	\brief      Renvoie le stock (nombre de produits) et valorisation de l'entrepot
	 * 		\return		Array		Array('nb'=>Nb, 'value'=>Value)
	 */
	function nb_products()
	{
		global $conf,$user;

		$ret=array();

		$sql = "SELECT sum(ps.reel) as nb, sum(ps.reel * ps.pmp) as value";
		$sql .= " FROM llx_product_stock as ps";
		if ($conf->categorie->enabled && !$user->rights->categorie->voir)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON cp.fk_product = p.rowid";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON cp.fk_categorie = c.rowid";
		}
		$sql .= " WHERE ps.fk_entrepot = ".$this->id;
		if ($conf->categorie->enabled && !$user->rights->categorie->voir)
		{
			$sql.= ' AND COALESCE(c.visible,1)=1';
		}

		//print $sql;
		$result = $this->db->query($sql) ;
		if ($result)
		{
			$obj = $this->db->fetch_object($result);
			$ret['nb']=$obj->nb;
			$ret['value']=$obj->value;

			$this->db->free();
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}

		return $ret;
	}

	/**
	 *    \brief      Retourne le libell� du statut d'un entrepot (ouvert, ferme)
	 *    \param      mode          0=libell� long, 1=libell� court, 2=Picto + Libell� court, 3=Picto, 4=Picto + Libell� long
	 *    \return     string        Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *    	\brief      Renvoi le libell� d'un statut donn�
	 *    	\param      statut        	Id statut
	 *    	\param      mode          	0=libell� long, 1=libell� court, 2=Picto + Libell� court, 3=Picto, 4=Picto + Libell� long, 5=Libell� court + Picto
	 *    	\return     string        	Libell� du statut
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
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *		\param		withpicto		Inclut le picto dans le lien
	 *		\param		option			Sur quoi pointe le lien
	 *		\return		string			Chaine avec URL
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
