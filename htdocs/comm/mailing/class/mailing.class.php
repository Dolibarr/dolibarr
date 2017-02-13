<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/comm/mailing/class/mailing.class.php
 *	\ingroup    mailing
 *	\brief      File of class to manage emailings module
 */

require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';


/**
 *	Class to manage emailings module
 */
class Mailing extends CommonObject
{
	public $element='mailing';
	public $table_element='mailing';
	public $picto='email';
	
	var $titre;
	var $sujet;
	var $body;
	var $nbemail;
	var $bgcolor;
	var $bgimage;

	var $statut;       // Status 0=Draft, 1=Validated, 2=Sent partially, 3=Sent completely
	
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

	public $statut_dest=array();
	public $statuts=array();


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

		$this->statut_dest[0] = 'MailingStatusNotSent';
		$this->statut_dest[-1] = 'MailingStatusError';
		$this->statut_dest[1] = 'MailingStatusSent';
		$this->statut_dest[2] = 'MailingStatusRead';
		$this->statut_dest[3] = 'MailingStatusReadAndUnsubscribe';    // Read but ask to not be contacted anymore

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
		$sql .= " VALUES ('".$this->db->idate($now)."', ".$user->id.", ".$conf->entity.")";

		if (! $this->titre)
		{
			$this->titre = $langs->trans("NoTitle");
		}

		dol_syslog("Mailing::Create", LOG_DEBUG);
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
				$this->db->rollback();
				return -1;
			}

			return $this->id;
		}
		else
		{
			$this->error=$this->db->lasterror();
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

		dol_syslog("Mailing::Update", LOG_DEBUG);
		$result=$this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
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
		global $conf;

		$sql = "SELECT m.rowid, m.titre, m.sujet, m.body, m.bgcolor, m.bgimage";
		$sql.= ", m.email_from, m.email_replyto, m.email_errorsto";
		$sql.= ", m.statut, m.nbemail";
		$sql.= ", m.fk_user_creat, m.fk_user_valid";
		$sql.= ", m.date_creat";
		$sql.= ", m.date_valid";
		$sql.= ", m.date_envoi";
		$sql.= ", m.extraparams";
		$sql.= " FROM ".MAIN_DB_PREFIX."mailing as m";
		$sql.= " WHERE m.rowid = ".(int) $rowid;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
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
				if (!empty($conf->global->FCKEDITOR_ENABLE_MAILING) && dol_textishtml(dol_html_entity_decode($obj->body, ENT_COMPAT | ENT_HTML401))) {
					$this->body				= dol_html_entity_decode($obj->body, ENT_COMPAT | ENT_HTML401);
				}else {
					$this->body				= $obj->body;
				}

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

		$object->context['createfromclone']='createfromclone';

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
			//Clone target
			if (!empty($option2)) {

				require_once DOL_DOCUMENT_ROOT .'/core/modules/mailings/modules_mailings.php';

				$mailing_target = new MailingTargets($this->db);

				$target_array=array();

				$sql = "SELECT fk_contact, ";
				$sql.=" lastname,   ";
				$sql.=" firstname,";
				$sql.=" email,";
				$sql.=" other,";
				$sql.=" source_url,";
				$sql.=" source_id ,";
				$sql.=" source_type ";
				$sql.= " FROM ".MAIN_DB_PREFIX."mailing_cibles ";
				$sql.= " WHERE fk_mailing = ".$fromid;

				dol_syslog(get_class($this)."::createFromClone", LOG_DEBUG);
				$result=$this->db->query($sql);
				if ($result)
				{
					if ($this->db->num_rows($result))
					{
						while ($obj = $this->db->fetch_object($result)) {

							$target_array[]=array('fk_contact'=>$obj->fk_contact,
							'lastname'=>$obj->lastname,
							'firstname'=>$obj->firstname,
							'email'=>$obj->email,
							'other'=>$obj->other,
							'source_url'=>$obj->source_url,
							'source_id'=>$obj->source_id,
							'source_type'=>$obj->source_type);
						}

					}
				}
				else
				{
					$this->error=$this->db->lasterror();
					return -1;
				}

				$mailing_target->add_to_target($object->id, $target_array);
			}

		}

		unset($object->context['createfromclone']);

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
		$sql .= " SET statut = 1, date_valid = '".$this->db->idate($now)."', fk_user_valid=".$user->id;
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog("Mailing::valid", LOG_DEBUG);
		if ($this->db->query($sql))
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
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

		dol_syslog("Mailing::delete", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			return $this->delete_targets();
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}
	
	/**
	 *  Delete targets emailing
	 *
	 *  @return int       1 if OK, 0 if error
	 */
	function delete_targets()
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."mailing_cibles";
		$sql.= " WHERE fk_mailing = ".$this->id;

		dol_syslog("Mailing::delete_targets", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return 0;
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

		dol_syslog("Mailing::reset_targets_status", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	
	/**
	 *  Count number of target with status
	 *
	 *  @param  string	$mode   Mode ('alreadysent' = Sent success or error) 
	 *  @return int        		Nb of target with status
	 */
	function countNbOfTargets($mode)
	{
	    $sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."mailing_cibles";
	    $sql.= " WHERE fk_mailing = ".$this->id;
	    if ($mode == 'alreadysent') $sql.= " AND statut <> 0";
	    else 
	    {
	        $this->error='BadValueForParameterMode';
	        return -2;
	    }
	     
	    $resql=$this->db->query($sql);
	    if ($resql)
	    {
	        $obj = $this->db->fetch_object($resql);
	        if ($obj) return $obj->nb;
	    }
	    else
	    {
	        $this->error=$this->db->lasterror();
	        return -1;
	    }
	    return 0;
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


	/**
	 *  Renvoi le libelle d'un statut donne
	 *  TODO Add class mailin_target.class.php
	 *
	 *  @param	int		$statut        	Id statut
	 *  @param  int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @param	strin	$desc			Desc error
	 *  @return string        			Label
	 */
	static public function libStatutDest($statut,$mode=0,$desc='')
	{
		global $langs;
		$langs->load('mails');

		if ($mode == 0)
		{
			return $langs->trans('MailingStatusError');
		}
		if ($mode == 1)
		{
			return $langs->trans('MailingStatusSent');
		}
		if ($mode == 2)
		{
			if ($statut==-1) return $langs->trans("MailingStatusError").' '.img_error($desc);
			if ($statut==1) return $langs->trans("MailingStatusSent").' '.img_picto($langs->trans("MailingStatusSent"),'statut6');
			if ($statut==2) return $langs->trans("MailingStatusRead").' '.img_picto($langs->trans("MailingStatusRead"),'statut4');
			if ($statut==3) return $langs->trans("MailingStatusNotContact").' '.img_picto($langs->trans("MailingStatusNotContact"),'statut3');
		}
		if ($mode == 3)
		{
			if ($statut==-1) return $langs->trans("MailingStatusError").' '.img_error($desc);
			if ($statut==1) return $langs->trans("MailingStatusSent").' '.img_picto($langs->trans("MailingStatusSent"),'statut6');
			if ($statut==2) return $langs->trans("MailingStatusRead").' '.img_picto($langs->trans("MailingStatusRead"),'statut4');
			if ($statut==3) return $langs->trans("MailingStatusNotContact").' '.img_picto($langs->trans("MailingStatusNotContact"),'statut3');
		}
		if ($mode == 4)
		{
			if ($statut==-1) return $langs->trans("MailingStatusError").' '.img_error($desc);
			if ($statut==1) return $langs->trans("MailingStatusSent").' '.img_picto($langs->trans("MailingStatusSent"),'statut6');
			if ($statut==2) return $langs->trans("MailingStatusRead").' '.img_picto($langs->trans("MailingStatusRead"),'statut4');
			if ($statut==3) return $langs->trans("MailingStatusNotContact").' '.img_picto($langs->trans("MailingStatusNotContact"),'statut3');
		}
		if ($mode == 5)
		{
		    if ($statut==-1) return $langs->trans("MailingStatusError").' '.img_error($desc);
		    if ($statut==1) return $langs->trans("MailingStatusSent").' '.img_picto($langs->trans("MailingStatusSent"),'statut6');
		    if ($statut==2) return $langs->trans("MailingStatusRead").' '.img_picto($langs->trans("MailingStatusRead"),'statut4');
		    if ($statut==3) return $langs->trans("MailingStatusNotContact").' '.img_picto($langs->trans("MailingStatusNotContact"),'statut3');
		}
		if ($mode == 6)
		{
		    if ($statut==-1) return $langs->trans("MailingStatusError").' '.img_error($desc);
		    if ($statut==1) return $langs->trans("MailingStatusSent").' '.img_picto($langs->trans("MailingStatusSent"),'statut6');
		    if ($statut==2) return $langs->trans("MailingStatusRead").' '.img_picto($langs->trans("MailingStatusRead"),'statut4');
		    if ($statut==3) return $langs->trans("MailingStatusNotContact").' '.img_picto($langs->trans("MailingStatusNotContact"),'statut3');
		}
	}

}

