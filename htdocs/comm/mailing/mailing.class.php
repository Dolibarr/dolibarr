<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \file       htdocs/comm/mailing/mailing.class.php
 \ingroup    mailing
 \brief      Fichier de la classe de gestion des mailings
 \version    $Id$
 */


/**
 *	\class      Mailing
 *	\brief      Classe permettant la gestion des mailings
 */
class Mailing
{
	var $id;
	var $error;

	var $statut;
	var $titre;
	var $sujet;
	var $body;
	var $nbemail;

	var $email_from;
	var $email_replyto;
	var $email_errorsto;

	var $user_creat;
	var $user_valid;

	var $date_creat;
	var $date_valid;


	/**
	 *    \brief  Constructeur de la classe
	 *    \param  DB          handler acces base de donnees
	 */
	function Mailing($DB)
	{
		global $langs;
		$langs->load("mails");

		$this->db = $DB ;
		$this->db_table = MAIN_DB_PREFIX."mailing";

		$this->statuts[0]  = $langs->trans("MailingStatusDraft");
		$this->statuts[1]  = $langs->trans("MailingStatusValidated");
		$this->statuts[2]  = $langs->trans("MailingStatusSentPartialy");
		$this->statuts[3]  = $langs->trans("MailingStatusSentCompletely");
	}

	/**
	 *    \brief      Create an EMailing
	 *    \param      user object utilisateur qui cree
	 *    \return     -1 if error, Id of created object if OK
	 */
	function create($user)
	{
		global $langs;

		$this->db->begin();

		$this->titre=trim($this->titre);
		$this->email_from=trim($this->email_from);

		if (! $this->email_from)
		{
			$this->error = $langs->trans("ErrorMailFromRequired");
			return -1;
		}

		$sql = "INSERT INTO ".$this->db_table;
		$sql .= " (date_creat, fk_user_creat)";
		$sql .= " VALUES (".$this->db->idate(mktime()).", ".$user->id.")";

		if (! $this->titre)
		{
			$this->titre = $langs->trans("NoTitle");
		}

		dolibarr_syslog("Mailing::Create sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
			$this->id = $this->db->last_insert_id($this->db_table);

			if ($this->update() > 0)
			{
				$this->db->commit();
			}
			else
			{
				$this->db->rollback();
				return -1;
			}

			return $this->id;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dolibarr_syslog("Mailing::Create ".$this->error, LOG_ERROR);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *    \brief      Update les infos du mailing
	 *    \return     < 0 si erreur, > 0 si ok
	 */
	function update()
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."mailing ";
		$sql .= " SET titre = '".addslashes($this->titre)."'";
		$sql .= " , sujet = '".addslashes($this->sujet)."'";
		$sql .= " , body = '".addslashes($this->body)."'";
		$sql .= " , email_from = '".$this->email_from."'";
		$sql .= " WHERE rowid = ".$this->id;

		dolibarr_syslog("Mailing::Update sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dolibarr_syslog("Mailing::Update ".$this->error, LOG_ERROR);
			return -1;
		}
	}

	/**
	 *		\brief      Get object from database
	 *		\param      rowid       id du mailing
	 *		\return		int
	 */
	function fetch($rowid)
	{
		$sql = "SELECT m.rowid, m.titre, m.sujet, m.body";
		$sql .= ", m.email_from, m.email_replyto, m.email_errorsto";
		$sql .= ", m.statut, m.nbemail";
		$sql .= ", m.fk_user_creat, m.fk_user_valid";
		$sql .= ", ".$this->db->pdate("m.date_creat") . " as date_creat";
		$sql .= ", ".$this->db->pdate("m.date_valid") . " as date_valid";
		$sql .= ", ".$this->db->pdate("m.date_envoi") . " as date_envoi";
		$sql .= " FROM ".MAIN_DB_PREFIX."mailing as m";
		$sql .= " WHERE m.rowid = ".$rowid;

		dolibarr_syslog("Mailing.class::fetch sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id                 = $obj->rowid;
				$this->ref                = $obj->rowid;
				$this->statut             = $obj->statut;
				$this->nbemail            = $obj->nbemail;
				$this->titre              = $obj->titre;
				$this->sujet              = $obj->sujet;
				$this->body               = $obj->body;

				$this->email_from         = $obj->email_from;
				$this->email_replyto      = $obj->email_replyto;
				$this->email_errorsto     = $obj->email_errorsto;

				$this->user_creat         = $obj->fk_user_creat;
				$this->user_valid         = $obj->fk_user_valid;

				$this->date_creat         = $obj->date_creat;
				$this->date_valid         = $obj->date_valid;
				$this->date_envoi         = $obj->date_envoi;

				return 1;
			}
			else
			{
				dolibarr_syslog("Mailing::Fetch Erreur -1");
				return -1;
			}
		}
		else
		{
			dolibarr_syslog("Mailing::Fetch Erreur -2");
			return -2;
		}
	}


	/**
	 *		\brief      Load an object from its id and create a new one in database
	 *		\param      fromid     	Id of object to clone
	 *		\return		int			New id of clone
	 */
	function createFromClone($fromid,$option1,$option2)
	{
		global $user,$langs;
		
		$error=0;
		
		$object=new Mailing($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;

		// Clear fields
		$object->titre=$langs->trans("CopyOf").' '.$object->titre;

		// If no option copy content
		if (empty($option1))
		{
			// Clear values	
			$object->nbemail            = 0;
			$object->titre              = $langs->trans("Draft").' '.mktime();
			$object->sujet              = '';
			$object->body               = '';

			$object->email_from         = '';
			$object->email_replyto      = '';
			$object->email_errorsto     = '';

			$object->user_creat         = $user->id;
			$object->user_valid         = '';

			$object->date_creat         = '';
			$object->date_valid         = '';
			$object->date_envoi         = '';
		}

		// Create clone
		$result=$object->create($user);

		// Other options
		if ($result < 0) 
		{
			$this->error=$object->error;
			$error++;
		}
		
		if (! $error)
		{
			
			
			
		}
		
		// End
		if (! $error)
		{
			$this->db->commit();
			return $object->id;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *    \brief     Valide le mailing
	 *    \param     user      objet user qui valide
	 */
	function valid($user)
	{
		dolibarr_syslog("Mailing::Valid");

		$sql = "UPDATE ".MAIN_DB_PREFIX."mailing ";
		$sql .= " SET statut = 1, date_valid = ".$this->db->idate(mktime()).", fk_user_valid=".$user->id;
		$sql .= " WHERE rowid = ".$this->id." AND statut = 0 ;";

		if ($this->db->query($sql) )
		{
	  return 0;
		}
		else
		{
	  dolibarr_syslog("Mailing::Valid Erreur -1");
	  return -1;
		}
	}

	/**
	 *    \brief      Supprime le mailing
	 *    \param      rowid       id du mailing a supprimer
	 *    \return     int         1 en cas de succes
	 */
	function delete($rowid)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."mailing";
		$sql .= " WHERE rowid = ".$rowid;

		$this->db->query($sql);
		return 1;
	}


	/**
	 *    \brief      Retourne le libell� du statut d'un mailing (brouillon, valid�e, ...
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
		$langs->load('mails');

		if ($mode == 0)
		{
			return $this->statuts[$statut];
		}
		if ($mode == 1)
		{
			return $this->statuts[$statut];
		}
		if ($mode == 2)
		{
			if ($statut == 0) return img_picto($langs->trans($this->statuts[$statut]),'statut0').' '.$this->statuts[$statut];
			if ($statut == 1) return img_picto($langs->trans($this->statuts[$statut]),'statut1').' '.$this->statuts[$statut];
			if ($statut == 2) return img_picto($langs->trans($this->statuts[$statut]),'statut3').' '.$this->statuts[$statut];
			if ($statut == 3) return img_picto($langs->trans($this->statuts[$statut]),'statut6').' '.$this->statuts[$statut];
		}
		if ($mode == 3)
		{
			if ($statut == 0) return img_picto($langs->trans($this->statuts[$statut]),'statut0');
			if ($statut == 1) return img_picto($langs->trans($this->statuts[$statut]),'statut1');
			if ($statut == 2) return img_picto($langs->trans($this->statuts[$statut]),'statut3');
			if ($statut == 3) return img_picto($langs->trans($this->statuts[$statut]),'statut6');
		}
		if ($mode == 4)
		{
			if ($statut == 0) return img_picto($langs->trans($this->statuts[$statut]),'statut0').' '.$this->statuts[$statut];
			if ($statut == 1) return img_picto($langs->trans($this->statuts[$statut]),'statut1').' '.$this->statuts[$statut];
			if ($statut == 2) return img_picto($langs->trans($this->statuts[$statut]),'statut3').' '.$this->statuts[$statut];
			if ($statut == 3) return img_picto($langs->trans($this->statuts[$statut]),'statut6').' '.$this->statuts[$statut];
		}
		if ($mode == 5)
		{
			if ($statut == 0)  return $this->statuts[$statut].' '.img_picto($langs->trans($this->statuts[$statut]),'statut0');
			if ($statut == 1)  return $this->statuts[$statut].' '.img_picto($langs->trans($this->statuts[$statut]),'statut1');
			if ($statut == 2)  return $this->statuts[$statut].' '.img_picto($langs->trans($this->statuts[$statut]),'statut3');
			if ($statut == 3)  return $this->statuts[$statut].' '.img_picto($langs->trans($this->statuts[$statut]),'statut6');
		}

	}

}

?>
