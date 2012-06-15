<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/comm/mailing/class/mailing.class.php
 *	\ingroup    mailing
 *	\brief      File of class to manage emailings module
 */

require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");


/**
 *	Class to manage emailings module
 */
class Mailing extends CommonObject
{
	public $element='mailing';
	public $table_element='mailing';

	var $id;
	var $statut;
	var $titre;
	var $sujet;
	var $body;
	var $nbemail;
	var $bgcolor;
	var $bgimage;

	var $email_from;
	var $email_replyto;
	var $email_errorsto;

	var $joined_file1;
	var $joined_file2;
	var $joined_file3;
	var $joined_file4;

	var $user_creat;
	var $user_valid;

	var $date_creat;
	var $date_valid;
	
	var $extraparams=array();


	/**
     *  Constructor
     *
     *  @param      DoliDb		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;

		// List of language codes for status
		$this->statuts[0] = 'MailingStatusDraft';
		$this->statuts[1] = 'MailingStatusValidated';
		$this->statuts[2] = 'MailingStatusSentPartialy';
		$this->statuts[3] = 'MailingStatusSentCompletely';
	}

	/**
	 *  Create an EMailing
	 *
	 *  @param	User	$user 		Object of user making creation
	 *  @return int	   				-1 if error, Id of created object if OK
	 */
	function create($user)
	{
		global $conf, $langs;

		$this->db->begin();

		$this->titre=trim($this->titre);
		$this->email_from=trim($this->email_from);

		if (! $this->email_from)
		{
			$this->error = $langs->trans("ErrorMailFromRequired");
			return -1;
		}

		$now=dol_now();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."mailing";
		$sql .= " (date_creat, fk_user_creat, entity)";
		$sql .= " VALUES (".$this->db->idate($now).", ".$user->id.", ".$conf->entity.")";

		if (! $this->titre)
		{
			$this->titre = $langs->trans("NoTitle");
		}

		dol_syslog("Mailing::Create sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."mailing");

			if ($this->update($user) > 0)
			{
				$this->db->commit();
			}
			else
			{
				$this->error=$this->db->lasterror();
				dol_syslog("Mailing::Create ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}

			return $this->id;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Mailing::Create ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Update emailing record
	 *
	 *  @param	User	$user 		Object of user making change
	 *  @return int				    < 0 if KO, > 0 if OK
	 */
	function update($user)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."mailing ";
		$sql .= " SET titre = '".$this->db->escape($this->titre)."'";
		$sql .= ", sujet = '".$this->db->escape($this->sujet)."'";
		$sql .= ", body = '".$this->db->escape($this->body)."'";
		$sql .= ", email_from = '".$this->email_from."'";
		$sql .= ", email_replyto = '".$this->email_replyto."'";
		$sql .= ", email_errorsto = '".$this->email_errorsto."'";
		$sql .= ", bgcolor = '".($this->bgcolor?$this->bgcolor:null)."'";
		$sql .= ", bgimage = '".($this->bgimage?$this->bgimage:null)."'";
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog("Mailing::Update sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Mailing::Update ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *	Get object from database
	 *
	 *	@param	int		$rowid      Id of emailing
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		$sql = "SELECT m.rowid, m.titre, m.sujet, m.body, m.bgcolor, m.bgimage";
		$sql.= ", m.email_from, m.email_replyto, m.email_errorsto";
		$sql.= ", m.statut, m.nbemail";
		$sql.= ", m.fk_user_creat, m.fk_user_valid";
		$sql.= ", m.date_creat";
		$sql.= ", m.date_valid";
		$sql.= ", m.date_envoi";
		$sql.= ", m.extraparams";
		$sql.= " FROM ".MAIN_DB_PREFIX."mailing as m";
		$sql.= " WHERE m.rowid = ".$rowid;

		dol_syslog(get_class($this)."::fetch sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id				= $obj->rowid;
				$this->ref				= $obj->rowid;
				$this->statut			= $obj->statut;
				$this->nbemail			= $obj->nbemail;
				$this->titre			= $obj->titre;
				$this->sujet			= $obj->sujet;
				$this->body				= $obj->body;
				$this->bgcolor			= $obj->bgcolor;
				$this->bgimage			= $obj->bgimage;

				$this->email_from		= $obj->email_from;
				$this->email_replyto	= $obj->email_replyto;
				$this->email_errorsto	= $obj->email_errorsto;

				$this->user_creat		= $obj->fk_user_creat;
				$this->user_valid		= $obj->fk_user_valid;

				$this->date_creat		= $this->db->jdate($obj->date_creat);
				$this->date_valid		= $this->db->jdate($obj->date_valid);
				$this->date_envoi		= $this->db->jdate($obj->date_envoi);
				
				$this->extraparams		= (array) json_decode($obj->extraparams, true);

				return 1;
			}
			else
			{
				dol_syslog(get_class($this)."::fetch Erreur -1");
				return -1;
			}
		}
		else
		{
			dol_syslog(get_class($this)."::fetch Erreur -2");
			return -2;
		}
	}


	/**
	 *	Load an object from its id and create a new one in database
	 *
	 *	@param  int		$fromid     	Id of object to clone
	 *	@param	int		$option1		1=Copy content, 0=Forget content
	 *	@param	int		$option2		Not used
	 *	@return	int						New id of clone
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
		$object->titre=$langs->trans("CopyOf").' '.$object->titre.' '.dol_print_date(dol_now());

		// If no option copy content
		if (empty($option1))
		{
			// Clear values
			$object->nbemail            = 0;
			$object->sujet              = '';
			$object->body               = '';
			$object->bgcolor            = '';
			$object->bgimage            = '';

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
	 *  Validate emailing
	 *
	 *  @param	User	$user      	Objet user qui valide
	 * 	@return	int					<0 if KO, >0 if OK
	 */
	function valid($user)
	{
		$now=dol_now();

		$sql = "UPDATE ".MAIN_DB_PREFIX."mailing ";
		$sql .= " SET statut = 1, date_valid = ".$this->db->idate($now).", fk_user_valid=".$user->id;
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog("Mailing::valid sql=".$sql, LOG_DEBUG);
		if ($this->db->query($sql))
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Mailing::Valid ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *  Delete emailing
	 *
	 *  @param	int		$rowid      id du mailing a supprimer
	 *  @return int         		1 en cas de succes
	 */
	function delete($rowid)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."mailing";
		$sql.= " WHERE rowid = ".$rowid;

		dol_syslog("Mailing::delete sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Mailing::Valid ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *  Change status of each recipient
	 *
	 *	@param	User	$user      	Objet user qui valide
	 *  @return int         		<0 if KO, >0 if OK
	 */
	function reset_targets_status($user)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
		$sql.= " SET statut = 0";
		$sql.= " WHERE fk_mailing = ".$this->id;

		dol_syslog("Mailing::reset_targets_status sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Mailing::Valid ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *  Retourne le libelle du statut d'un mailing (brouillon, validee, ...
	 *
	 *  @param	int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
	 *  @return string        			Label
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *  Renvoi le libelle d'un statut donne
	 *
	 *  @param	int		$statut        	Id statut
	 *  @param  int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string        			Label
	 */
	function LibStatut($statut,$mode=0)
	{
		global $langs;
		$langs->load('mails');

		if ($mode == 0)
		{
			return $langs->trans($this->statuts[$statut]);
		}
		if ($mode == 1)
		{
			return $langs->trans($this->statuts[$statut]);
		}
		if ($mode == 2)
		{
			if ($statut == 0) return img_picto($langs->trans($this->statuts[$statut]),'statut0').' '.$langs->trans($this->statuts[$statut]);
			if ($statut == 1) return img_picto($langs->trans($this->statuts[$statut]),'statut1').' '.$langs->trans($this->statuts[$statut]);
			if ($statut == 2) return img_picto($langs->trans($this->statuts[$statut]),'statut3').' '.$langs->trans($this->statuts[$statut]);
			if ($statut == 3) return img_picto($langs->trans($this->statuts[$statut]),'statut6').' '.$langs->trans($this->statuts[$statut]);
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
			if ($statut == 0) return img_picto($langs->trans($this->statuts[$statut]),'statut0').' '.$langs->trans($this->statuts[$statut]);
			if ($statut == 1) return img_picto($langs->trans($this->statuts[$statut]),'statut1').' '.$langs->trans($this->statuts[$statut]);
			if ($statut == 2) return img_picto($langs->trans($this->statuts[$statut]),'statut3').' '.$langs->trans($this->statuts[$statut]);
			if ($statut == 3) return img_picto($langs->trans($this->statuts[$statut]),'statut6').' '.$langs->trans($this->statuts[$statut]);
		}
		if ($mode == 5)
		{
			if ($statut == 0)  return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),'statut0');
			if ($statut == 1)  return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),'statut1');
			if ($statut == 2)  return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),'statut3');
			if ($statut == 3)  return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts[$statut]),'statut6');
		}
	}

}

?>
