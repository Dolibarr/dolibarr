<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
		\file 		htdocs/adherents/cotisation.class.php
        \ingroup    adherent
		\brief      Fichier de la classe permettant de gèrer les cotisations
		\version    $Id$
*/


/**
	\class 		Cotisation
	\brief      Classe permettant de gèrer les cotisations
*/
class Cotisation extends CommonObject
{
	var $id;
	var $db;
	var $error;
	var $errors;

	var $datec;
	var $datem;
	var $dateh;				// Subscription start date
	var $datef;				// Subscription end date
	var $fk_adherent;
	var $amount;
	var $note;
	var $fk_bank;


	/**
			\brief Cotisation
			\param DB				Handler base de données
	*/
	function Cotisation($DB)
	{
		$this->db = $DB;
	}


	/**
		\brief 		Fonction qui permet de créer le don
		\param 		userid		userid de celui qui insere
		\return		int			<0 si KO, Id cotisation créé si OK
	*/
	function create($userid)
	{
		// Check parameters
		if ($this->datef <= $this->dateh)
		{
			$this->error="Error: Bad value for datef or dateh";
			return -1;
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."cotisation (fk_adherent, datec, dateadh, datef, cotisation, note)";
        $sql.= " VALUES (".$this->fk_adherent.", ".$this->db->idate(mktime()).",";
		$sql.= " ".$this->db->idate($this->dateh).",";
		$sql.= " ".$this->db->idate($this->datef).",";
		$sql.= " ".$this->amount.",'".$this->note."')";

		dol_syslog("Cotisation::create sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			return $this->db->last_insert_id(MAIN_DB_PREFIX."cotisation");
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog($this->error, LOG_ERR);
			return -1;
		}
	}


	/**
		\brief 		Fonction qui permet de récupèrer une cotisation
		\param 		rowid		Id cotisation
		\return		int			<0 si KO, =0 si OK mais non trouve, >0 si OK
	*/
	function fetch($rowid)
	{
        $sql ="SELECT rowid, fk_adherent, ".$this->db->pdate("datec")." as datec,";
		$sql.=" tms,";
		$sql.=" ".$this->db->pdate("dateadh")." as dateadh,";
		$sql.=" ".$this->db->pdate("datef")." as datef,";
		$sql.=" cotisation, note, fk_bank";
		$sql.=" FROM ".MAIN_DB_PREFIX."cotisation";
		$sql.="	WHERE rowid=".$rowid;

		dol_syslog("Cotisation::fetch sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id             = $obj->rowid;
				$this->ref            = $obj->rowid;

				$this->fk_adherent    = $obj->fk_adherent;
				$this->datec          = $obj->datec;
				$this->datem          = $obj->tms;
				$this->dateh          = $obj->dateadh;
				$this->datef          = $obj->datef;
				$this->amount         = $obj->cotisation;
				$this->note           = $obj->note;
				$this->fk_bank        = $obj->fk_bank;
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
	 *		\brief 		Met a jour en base la cotisation
	 *		\param 		user			Objet user qui met a jour
	 *		\param 		notrigger		0=Desactive les triggers
	 *		\param		int				<0 if KO, >0 if OK
	 */
	function update($user,$notrigger=0)
	{
		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."cotisation SET ";
		$sql .= " fk_adherent = ".$this->fk_adherent.",";
		$sql .= " note=".($this->note ? "'".addslashes($this->note)."'" : 'null').",";
		$sql .= " cotisation = '".price2num($this->amount)."',";
		$sql .= " dateadh='".$this->db->idate($this->dateh)."',";
		$sql .= " datef='".$this->db->idate($this->datef)."',";
		$sql .= " datec='".$this->db->idate($this->datec)."',";
		$sql .= " fk_bank = ".($this->fk_bank ? $this->fk_bank : 'null');
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog("Cotisation::update sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$member=new Adherent($this->db);
			$result=$member->fetch($this->fk_adherent);
			$result=$member->update_end_date($user);

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->error();
			dol_syslog("Cotisation::update ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *		\brief		Delete a subscription
	 *		\param 		rowid	Id cotisation
	 *		\return		int		<0 si KO, 0 si OK mais non trouve, >0 si OK
	 */
	function delete($user)
	{
		// It subscription is linked to a bank transaction, we get it
		if ($this->fk_bank)
		{
			require_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");
			$accountline=new AccountLine($this->db);
			$result=$accountline->fetch($this->fk_bank);
		}

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."cotisation WHERE rowid = ".$this->id;
		dol_syslog("Cotisation::delete sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->affected_rows($resql);
			if ($num)
			{
				require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
				$member=new Adherent($this->db);
				$result=$member->fetch($this->fk_adherent);
				$result=$member->update_end_date($user);

				if ($this->fk_bank)
				{
					$result=$accountline->delete($user);		// Return false if refused because line is conciliated
					if ($result > 0)
					{
						$this->db->commit();
						return 1;
					}
					else
					{
						$this->error=$accountline->error;
						$this->db->rollback();
						return -1;
					}
				}
				else
				{
					$this->db->commit();
					return 1;
				}
			}
			else
			{
				$this->db->commit();
				return 0;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *    	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *		\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *		\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0)
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/adherents/fiche_subscription.php?rowid='.$this->id.'">';
		$lienfin='</a>';

		$picto='payment';
		$label=$langs->trans("ShowSubscription");

		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		$result.=$lien.$this->ref.$lienfin;
		return $result;
	}


    /**
     *      \brief     Charge les informations d'ordre info dans l'objet cotisation
     *      \param     id       Id adhesion a charger
     */
	function info($id)
	{
		$sql = 'SELECT c.rowid, '.$this->db->pdate('c.datec').' as datec,';
		$sql.= ' '.$this->db->pdate('c.tms').' as datem';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'cotisation as c';
		$sql.= ' WHERE c.rowid = '.$id;

		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;

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
}
?>
