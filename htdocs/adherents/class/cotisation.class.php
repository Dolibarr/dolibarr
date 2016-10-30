<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *		\file 		htdocs/adherents/class/cotisation.class.php
 *      \ingroup    member
 *		\brief      File of class to manage subscriptions of foundation members
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class to manage subscriptions of foundation members
 */
class Cotisation extends CommonObject
{
	public $element='subscription';
	public $table_element='cotisation';

	var $datec;				// Date creation
	var $datem;				// Date modification
	var $dateh;				// Subscription start date (date subscription)
	var $datef;				// Subscription end date
	var $fk_adherent;
	var $amount;
	var $fk_bank;


	/**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *	Fonction qui permet de creer la cotisation
	 *
	 *	@param	int		$userid		userid de celui qui insere
	 *	@return	int					<0 if KO, Id subscription created if OK
	 */
	function create($userid)
	{
		global $langs;

		$now=dol_now();

		// Check parameters
		if ($this->datef <= $this->dateh)
		{
			$this->error=$langs->trans("ErrorBadValueForDate");
			return -1;
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."cotisation (fk_adherent, datec, dateadh, datef, cotisation, note)";
        $sql.= " VALUES (".$this->fk_adherent.", '".$this->db->idate($now)."',";
		$sql.= " '".$this->db->idate($this->dateh)."',";
		$sql.= " '".$this->db->idate($this->datef)."',";
		$sql.= " ".$this->amount.",'".$this->db->escape($this->note)."')";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			return $this->db->last_insert_id(MAIN_DB_PREFIX."cotisation");
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Method to load a subscription
	 *
	 *  @param	int		$rowid		Id subscription
	 *  @return	int					<0 if KO, =0 if not found, >0 if OK
	 */
	function fetch($rowid)
	{
        $sql ="SELECT rowid, fk_adherent, datec,";
		$sql.=" tms,";
		$sql.=" dateadh as dateh,";
		$sql.=" datef,";
		$sql.=" cotisation, note, fk_bank";
		$sql.=" FROM ".MAIN_DB_PREFIX."cotisation";
		$sql.="	WHERE rowid=".$rowid;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id             = $obj->rowid;
				$this->ref            = $obj->rowid;

				$this->fk_adherent    = $obj->fk_adherent;
				$this->datec          = $this->db->jdate($obj->datec);
				$this->datem          = $this->db->jdate($obj->tms);
				$this->dateh          = $this->db->jdate($obj->dateh);
				$this->datef          = $this->db->jdate($obj->datef);
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
			$this->error=$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *	Met a jour en base la cotisation
	 *
	 *	@param	User	$user			Objet user qui met a jour
	 *	@param 	int		$notrigger		0=Desactive les triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	function update($user,$notrigger=0)
	{
		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."cotisation SET ";
		$sql .= " fk_adherent = ".$this->fk_adherent.",";
		$sql .= " note=".($this->note ? "'".$this->db->escape($this->note)."'" : 'null').",";
		$sql .= " cotisation = '".price2num($this->amount)."',";
		$sql .= " dateadh='".$this->db->idate($this->dateh)."',";
		$sql .= " datef='".$this->db->idate($this->datef)."',";
		$sql .= " datec='".$this->db->idate($this->datec)."',";
		$sql .= " fk_bank = ".($this->fk_bank ? $this->fk_bank : 'null');
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
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
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Delete a subscription
	 *
	 *	@param	User	$user		User that delete
	 *	@return	int					<0 if KO, 0 if not found, >0 if OK
	 */
	function delete($user)
	{
		// It subscription is linked to a bank transaction, we get it
		if ($this->fk_bank)
		{
			require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
			$accountline=new AccountLine($this->db);
			$result=$accountline->fetch($this->fk_bank);
		}

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."cotisation WHERE rowid = ".$this->id;
		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->affected_rows($resql);
			if ($num)
			{
				require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
				$member=new Adherent($this->db);
				$result=$member->fetch($this->fk_adherent);
				$result=$member->update_end_date($user);

				if ($accountline->id > 0)						// If we found bank account line (this means this->fk_bank defined)
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
	 *  Return clicable name (with picto eventually)
	 *
	 *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@return	string					Chaine avec URL
	 */
	function getNomUrl($withpicto=0)
	{
		global $langs;

		$result='';
        $label=$langs->trans("ShowSubscription").': '.$this->ref;

        $link = '<a href="'.DOL_URL_ROOT.'/adherents/fiche_subscription.php?rowid='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend='</a>';

		$picto='payment';

        if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';
		$result.=$link.$this->ref.$linkend;
		return $result;
	}


    /**
     *  Charge les informations d'ordre info dans l'objet cotisation
	 *
     *  @param	int		$id       Id subscription
     *  @return	void
     */
	function info($id)
	{
		$sql = 'SELECT c.rowid, c.datec,';
		$sql.= ' c.tms as datem';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'cotisation as c';
		$sql.= ' WHERE c.rowid = '.$id;

		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;

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
}
