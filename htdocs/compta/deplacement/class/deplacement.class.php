<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
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
 *      \file       htdocs/compta/deplacement/class/deplacement.class.php
 *      \ingroup    deplacement
 *      \brief      File of class to manage trips
 */

require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");

/**
 *      \class      Deplacement
 *      \brief      Class to manage trips and working credit notes
 */
class Deplacement extends CommonObject
{
	var $db;
	var $error;
	var $element='deplacement';
	var $table_element='deplacement';
	var $table_element_line = '';
	var $fk_element = '';
	var $ismultientitymanaged = 0;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	var $id;

	var $datec;         // Creation date
	var $dated;
	var $fk_user_author;
	var $fk_user;
	var $km;
	var $note;
	var $note_public;
	var $socid;
	var $statut;		// 0=draft, 1=validated
	var $fk_project;

   /**
	*  \brief  Constructeur de la classe
	*  \param  DB          handler acces base de donnees
	*/
	function Deplacement($DB)
	{
		$this->db = $DB;

		return 1;
	}

	/**
	 * Create object in database
	 *
	 * @param 	$user	User that creates
	 * @return 	int		<0 if KO, >0 if OK
	 * TODO Add ref number
	 */
	function create($user)
	{
		global $conf;

		// Check parameters
		if (empty($this->type) || $this->type < 0)
		{
			$this->error='ErrorBadParameter';
			return -1;
		}
		if (empty($this->fk_user) || $this->fk_user < 0)
		{
			$this->error='ErrorBadParameter';
			return -1;
		}

        $now=dol_now();

        $this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."deplacement (";
		$sql.= "datec";
		//$sql.= ", dated";
		$sql.= ", entity";
		$sql.= ", fk_user_author";
		$sql.= ", fk_user";
		$sql.= ", type";
		$sql.= ", note";
		$sql.= ", note_public";
		$sql.= ", fk_projet";
		$sql.= ", fk_soc";
		$sql.= ") VALUES (";
		$sql.= " '".$this->db->idate($now)."'";
		$sql.= ", ".$conf->entity;
		$sql.= ", ".$user->id;
		$sql.= ", ".$this->fk_user;
		$sql.= ", '".$this->type."'";
		$sql.= ", ".($this->note?"'".$this->db->escape($this->note)."'":"null");
		$sql.= ", ".($this->note_public?"'".$this->db->escape($this->note_public)."'":"null");
		$sql.= ", ".($this->fk_project > 0? $this->fk_project : 0);
		$sql.= ", ".($this->fk_soc > 0? $this->fk_soc : "null");
		$sql.= ")";

		dol_syslog("Deplacement::create sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."deplacement");
			$result=$this->update($user);
			if ($result > 0)
			{
				$this->db->commit();
				return $this->id;
			}
			else
			{
				$this->db->rollback();
				return $result;
			}
		}
		else
		{
			$this->error=$this->db->error()." sql=".$sql;
			$this->db->rollback();
			return -1;
		}

	}

	/**
	 *
	 */
	function update($user)
	{
		global $langs;

		// Clean parameters
		$this->km=price2num($this->km);

		// Check parameters
		if (! is_numeric($this->km)) $this->km = 0;
        if (empty($this->date))
        {
            $this->error='ErrorBadParameter';
            return -1;
        }
        if (empty($this->type) || $this->type < 0)
		{
			$this->error='ErrorBadParameter';
			return -1;
		}
		if (empty($this->fk_user) || $this->fk_user < 0)
		{
			$this->error='ErrorBadParameter';
			return -1;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."deplacement ";
		$sql .= " SET km = ".$this->km;		// This is a distance or amount
		$sql .= " , dated = '".$this->db->idate($this->date)."'";
		$sql .= " , type = '".$this->type."'";
		$sql .= " , fk_user = ".$this->fk_user;
		$sql .= " , fk_soc = ".($this->socid > 0?$this->socid:'null');
		$sql .= " , note = ".($this->note?"'".$this->db->escape($this->note)."'":"null");
		$sql .= " , note_public = ".($this->note_public?"'".$this->db->escape($this->note_public)."'":"null");
		$sql .= " , fk_projet = ".($this->fk_project>0?$this->fk_project:0);
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog("Deplacement::update sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
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
	*	Load an object from database
	*/
	function fetch($id)
	{
		$sql = "SELECT rowid, fk_user, type, fk_statut, km, fk_soc, dated, note, note_public, fk_projet";
		$sql.= " FROM ".MAIN_DB_PREFIX."deplacement";
		$sql.= " WHERE rowid = ".$id;

		dol_syslog("Deplacement::fetch sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ( $result )
		{
			$obj = $this->db->fetch_object($result);

			$this->id          = $obj->rowid;
			$this->ref         = $obj->rowid;
			$this->date        = $this->db->jdate($obj->dated);
			$this->fk_user     = $obj->fk_user;
			$this->socid       = $obj->fk_soc;
			$this->km          = $obj->km;
			$this->type        = $obj->type;
			$this->fk_statut   = $obj->fk_statut;
			$this->note        = $obj->note;
			$this->note_public = $obj->note_public;
			$this->fk_project  = $obj->fk_projet;

			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

   /**
	*
	*/
	function delete($id)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."deplacement WHERE rowid = ".$id;

		dol_syslog("Deplacement::delete sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}


	/**
	 *    \brief      Retourne le libelle du statut
	 *    \param      mode        0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * 	  \return     string      Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *    \brief      Renvoi le libelle d'un statut donne
	 *    \param      statut      id statut
	 *    \param      mode        0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *    \return     string      Libelle
	 */
	function LibStatut($statut,$mode=0)
	{
		global $langs;

		if ($mode == 0)
		{
			return $langs->trans($this->statuts[$statut]);
		}
		if ($mode == 1)
		{
			return $langs->trans($this->statuts_short[$statut]);
		}
		if ($mode == 2)
		{
			//if ($statut==0) return img_picto($langs->trans($this->statuts_short[$statut]),'statut0').' '.$langs->trans($this->statuts_short[$statut]);
			if ($statut==0 || $statut==1) return img_picto($langs->trans($this->statuts_short[$statut]),'statut4').' '.$langs->trans($this->statuts_short[$statut]);
		}
		if ($mode == 3)
		{
			//if ($statut==0) return img_picto($langs->trans($this->statuts_short[$statut]),'statut0');
			if ($statut==0 || $statut==1) return img_picto($langs->trans($this->statuts_short[$statut]),'statut4');
		}
		if ($mode == 4)
		{
			//if ($statut==0) return img_picto($langs->trans($this->statuts_short[$statut]),'statut0').' '.$langs->trans($this->statuts[$statut]);
			if ($statut==0 || $statut==1) return img_picto($langs->trans($this->statuts_short[$statut]),'statut4').' '.$langs->trans($this->statuts[$statut]);
		}
		if ($mode == 5)
		{
			//if ($statut==0) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut0');
			if ($statut==0 || $statut==1) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut4');
		}
	}

	/**
	 *	\brief      Return clicable name (with picto eventually)
	 *	\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *	\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0)
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/compta/deplacement/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

		$picto='trip';

		$label=$langs->trans("Show").': '.$this->ref;

		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
		return $result;
	}


	/**
	 * List of types
	 */
	function listOfTypes($active=1)
	{
	   global $conf,$langs;

	   $ret=array();

        $sql = "SELECT id, code, libelle as label";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_type_fees";
        $sql.= " WHERE active = ".$active;

        dol_syslog("Deplacement::listOfTypes sql=".$sql, LOG_DEBUG);
        $result = $this->db->query($sql);
        if ( $result )
        {
            $num = $this->db->num_rows($result);
            $i=0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                $ret[$obj->id]=array('code'=>$obj->code, 'label'=>(($langs->trans($obj->code)!=$obj->code)?$langs->trans($obj->code):$obj->label));
                $i++;
            }
        }
        else dol_print_error($this->db);

        return $ret;
	}

}

?>
