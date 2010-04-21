<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
 *		\file       htdocs/business/class/business.class.php
 *		\ingroup    business
 *		\brief      Fichier de la classe de gestion des affaires
 *		\version    $Id$
 */
require_once(DOL_DOCUMENT_ROOT ."/core/commonobject.class.php");

/**
 *		\class      Business
 *		\brief      Class to manage business
 */
class Business extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='business';			//!< Id that identify managed objects
	var $table_element='business';		//!< Name of table without prefix where object is stored
	var $table_element_line='business_phase';
	var $fk_element='fk_business';

	var $id;
	var $ref;
	var $description;
	var $statut;
	var $label;
	var $date_c;
	var $date_m;
	var $date_start;
	var $date_end;
	var $socid;
	var $user_author_id;				//!< Id of business creator. Not defined if shared business.
	var $public;						//!< Tell if this is a public or private business
	var $note_private;
	var $note_public;

	var $statuts_short;
	var $statuts;

	/**
	 *    \brief  Constructeur de la classe
	 *    \param  DB          handler acces base de donnees
	 */
	function Business($DB)
	{
		$this->db = $DB;
		$this->societe = new Societe($DB);

		$this->statuts_short=array(0=>'Draft',1=>'Validated',2=>'Closed');
		$this->statuts=array(0=>'Draft',1=>'Validated',2=>'Closed');
	}

	/**
	 *    \brief      Create a business into database
	 *    \param      user        Id utilisateur qui cree
	 *    \return     int         <0 si ko, id du projet cree si ok
	 */
	function create($user, $notrigger=0)
	{
		global $conf;
		
		$ret=0;

		// Clean parameters
		$this->label = trim($this->label);
		$this->description = trim($this->description);
		
		// Check parameters
		if (! trim($this->ref))
		{
			$this->error='ErrorFieldsRequired';
			dol_syslog("Business::Create error -1 ref null", LOG_ERR);
			return -1;
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."business (";
		$sql.= "ref";
		$sql.= ", label";
		$sql.= ", description";
		$sql.= ", fk_soc";
		$sql.= ", fk_user_creat";
		$sql.= ", public";
		$sql.= ", datec";
		$sql.= ", dateo";
		$sql.= ", datee";
		$sql.= ") VALUES (";
		$sql.= "'".addslashes($this->ref)."'";
		$sql.= ", '".addslashes($this->label)."'";
		$sql.= ", '".addslashes($this->description)."'";
		$sql.= ", ".($this->socid > 0?$this->socid:"null");
		$sql.= ", ".$user->id;
		$sql.= ", ".($this->public?1:0);
		$sql.= ", ".($this->date_c!=''?$this->db->idate($this->date_c):'null');
		$sql.= ", ".($this->date_start!=''?$this->db->idate($this->date_start):'null');
		$sql.= ", ".($this->date_end!=''?$this->db->idate($this->date_end):'null');
		$sql.= ")";

		dol_syslog("Business::create sql=".$sql,LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."business");
			$ret = $this->id;

			if (! $notrigger)
			{
	            // Call triggers
	            include_once(DOL_DOCUMENT_ROOT . "/core/interfaces.class.php");
	            $interface=new Interfaces($this->db);
	            $result=$interface->run_triggers('BUSINESS_CREATE',$this,$user,$langs,$conf);
	            if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            // End call triggers
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Business::Create error -2 ".$this->error, LOG_ERR);
			$ret = -2;
		}

		return $ret;
	}


	/**
	 * Update a business
	 *
	 * @param unknown_type $user
	 * @return unknown
	 */
	function update($user, $notrigger=0)
	{
		global $conf;
		
		// Clean parameters
		$this->label = trim($this->label);
		$this->description = trim($this->description);

		if (strlen(trim($this->ref)) > 0)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."business SET";
			$sql.= " ref='".$this->ref."'";
			$sql.= ", label = '".addslashes($this->label)."'";
			$sql.= ", description = '".addslashes($this->description)."'";
			$sql.= ", fk_soc = ".($this->socid > 0?$this->socid:"null");
			$sql.= ", fk_statut = ".$this->statut;
			$sql.= ", public = ".($this->public?1:0);
			$sql.= ", datec=".($this->date_c!=''?$this->db->idate($this->date_c):'null');
			$sql.= ", dateo=".($this->date_start!=''?$this->db->idate($this->date_start):'null');
			$sql.= ", datee=".($this->date_end!=''?$this->db->idate($this->date_end):'null');
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog("Business::Update sql=".$sql,LOG_DEBUG);
			if ($this->db->query($sql) )
			{
				if (! $notrigger)
				{
					// Call triggers
					include_once(DOL_DOCUMENT_ROOT . "/core/interfaces.class.php");
					$interface=new Interfaces($this->db);
					$result=$interface->run_triggers('BUSINESS_MODIFY',$this,$user,$langs,$conf);
					if ($result < 0) { $error++; $this->errors=$interface->errors; }
					// End call triggers
				}

				$result = 1;
			}
			else
			{
				$this->error=$this->db->lasterror();
				dol_syslog("Business::Update error -2 ".$this->error, LOG_ERR);
				$result = -2;
			}
		}
		else
		{
			dol_syslog("Business::Update ref null");
			$result = -1;
		}

		return $result;
	}


	/**
	 *	\brief      Get object and lines from database
	 *	\param      rowid       id of object to load
	 * 	\param		ref			Ref of business
	 *	\return     int         >0 if OK, <0 if KO
	 */
	function fetch($id,$ref='')
	{
		if (empty($id) && empty($ref)) return -1;

		$sql = "SELECT rowid, ref, label, description, public, datec";
		$sql.= ", tms, dateo, datee, fk_soc, fk_user_creat, fk_statut, note_private, note_public";
		$sql.= " FROM ".MAIN_DB_PREFIX."business";
		if ($ref) $sql.= " WHERE ref='".$ref."'";
		else $sql.= " WHERE rowid=".$id;

		dol_syslog("Business::fetch sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id             = $obj->rowid;
				$this->ref            = $obj->ref;
				$this->label          = $obj->label;
				$this->description    = $obj->description;
				$this->date_c         = $this->db->jdate($obj->datec);
				$this->date_m         = $this->db->jdate($obj->tms);
				$this->date_start     = $this->db->jdate($obj->dateo);
				$this->date_end       = $this->db->jdate($obj->datee);
				$this->note_private   = $obj->note_private;
				$this->note_public    = $obj->note_public;
				$this->socid          = $obj->fk_soc;
				$this->user_author_id = $obj->fk_user_creat;
				$this->public         = $obj->public;
				$this->statut         = $obj->fk_statut;

				$this->db->free($resql);

				return 1;
			}
			else
			{
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Business::fetch ".$this->error, LOG_ERR);
			return -2;
		}
	}

	/**
	 *	\brief		Return list of business
	 * 	\param		socid			To filter on a particular third party
	 * 	\return		array			Business list
	 */
	function liste_array($socid='')
	{
		global $conf;

		$business = array();

		$sql = "SELECT rowid, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."business";
		$sql.= " WHERE entity = ".$conf->entity;
		if (! empty($socid)) $sql.= " AND fk_soc = ".$socid;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$nump = $this->db->num_rows($resql);

			if ($nump)
			{
				$i = 0;
				while ($i < $nump)
				{
					$obj = $this->db->fetch_object($resql);

					$business[$obj->rowid] = $obj->label;
					$i++;
				}
			}
			return $business;
		}
		else
		{
			print $this->db->lasterror();
		}

	}

	/**
	 *    \brief    Delete business in database
	 *    \param    User
	 */
	function delete($user, $notrigger=0)
	{
		global $conf;
		
		$this->db->begin();
		
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."business";
		$sql.= " WHERE rowid=".$this->id;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			// We remove directory
			$businessref = dol_sanitizeFileName($this->ref);
			if ($conf->business->dir_output)
			{
				$dir = $conf->business->dir_output . "/" . $businessref;
				if (file_exists($dir))
				{
					$res=@dol_delete_dir($dir);
					if (! $res)
					{
						$this->error='ErrorFailToDeleteDir';
						$this->db->rollback();
						return 0;
					}
				}
			}
			
			if (! $notrigger)
			{
	            // Call triggers
	            include_once(DOL_DOCUMENT_ROOT . "/core/interfaces.class.php");
	            $interface=new Interfaces($this->db);
	            $result=$interface->run_triggers('BUSINESS_DELETE',$this,$user,$langs,$conf);
	            if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            // End call triggers
			}
			
			dol_syslog("Business::delete sql=".$sql, LOG_DEBUG);
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog("Business::delete ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *		\brief		Validate a business
	 *		\param		user		User that validate
	 *		\return		int			<0 if KO, >0 if OK
	 */
	function setValid($user, $outputdir)
	{
		global $langs, $conf;

		if ($this->statut != 1)
		{
			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."business";
			$sql.= " SET fk_statut = 1";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			dol_syslog("Business::setValid sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/core/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('BUSINESS_VALIDATE',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// Fin appel triggers

				if (! $error)
				{
					$this->db->commit();
					return 1;
				}
				else
				{
					$this->db->rollback();
					$this->error=join(',',$this->errors);
					dol_syslog("Business::setValid ".$this->error,LOG_ERR);
					return -1;
				}
			}
			else
			{
				$this->db->rollback();
				$this->error=$this->db->lasterror();
				dol_syslog("Business::setValid ".$this->error,LOG_ERR);
				return -1;
			}
		}
	}

	/**
	 *		\brief		Close a business
	 *		\param		user		User that validate
	 *		\return		int			<0 if KO, >0 if OK
	 */
	function setClose($user, $outputdir)
	{
		global $langs, $conf;

		if ($this->statut != 2)
		{
			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."business";
			$sql.= " SET fk_statut = 2";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			$sql.= " AND fk_statut = 1";

			dol_syslog("Business::setClose sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/core/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('BUSINESS_CLOSE',$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// Fin appel triggers

				if (! $error)
				{
					$this->db->commit();
					return 1;
				}
				else
				{
					$this->db->rollback();
					$this->error=join(',',$this->errors);
					dol_syslog("Business::setClose ".$this->error,LOG_ERR);
					return -1;
				}
			}
			else
			{
				$this->db->rollback();
				$this->error=$this->db->lasterror();
				dol_syslog("Business::setClose ".$this->error,LOG_ERR);
				return -1;
			}
		}
	}

	/**
	 *    \brief      Return status label of object
	 *    \param      mode        0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * 	  \return     string      Label
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *    \brief      Return status label of object
	 *    \param      statut      id statut
	 *    \param      mode        0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * 	  \return     string      Label
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
			if ($statut==0) return img_picto($langs->trans($this->statuts_short[$statut]),'statut0').' '.$langs->trans($this->statuts_short[$statut]);
			if ($statut==1) return img_picto($langs->trans($this->statuts_short[$statut]),'statut4').' '.$langs->trans($this->statuts_short[$statut]);
			if ($statut==2) return img_picto($langs->trans($this->statuts_short[$statut]),'statut6').' '.$langs->trans($this->statuts_short[$statut]);
		}
		if ($mode == 3)
		{
			if ($statut==0) return img_picto($langs->trans($this->statuts_short[$statut]),'statut0');
			if ($statut==1) return img_picto($langs->trans($this->statuts_short[$statut]),'statut4');
			if ($statut==2) return img_picto($langs->trans($this->statuts_short[$statut]),'statut6');
		}
		if ($mode == 4)
		{
			if ($statut==0) return img_picto($langs->trans($this->statuts_short[$statut]),'statut0').' '.$langs->trans($this->statuts_short[$statut]);
			if ($statut==1) return img_picto($langs->trans($this->statuts_short[$statut]),'statut4').' '.$langs->trans($this->statuts_short[$statut]);
			if ($statut==2) return img_picto($langs->trans($this->statuts_short[$statut]),'statut6').' '.$langs->trans($this->statuts_short[$statut]);
		}
		if ($mode == 5)
		{
			if ($statut==0) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut0');
			if ($statut==1) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut1');
			if ($statut==2) return img_picto($langs->trans($this->statuts_short[$statut]),'statut6').' '.$langs->trans($this->statuts_short[$statut]);
		}
	}

	/**
	 *	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *	\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *	\param		option			Sur quoi pointe le lien
	 *	\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/business/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

		$picto='businesspub@business';
		if (! $this->public) $picto='business@business';

		$label=$langs->trans("ShowBusiness").': '.$this->ref.($this->label?' - '.$this->label:'');

		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
		return $result;
	}

	/**
	 *		\brief		Check permissions
	 */
	function restrictedBusinessArea($user,$list=0)
	{
		// To verify role of users
		$userAccess = 0;
		if ((!empty($this->user_author_id) && $this->user_author_id == $user->id) || $user->rights->business->all->read)
		{
			$userAccess = 1;
		}
		else if ($this->public && $user->rights->business->read)
		{
			$userAccess = 1;
		}
		else
		{
			foreach(array('internal','external') as $source)
			{
				$userRole = $this->liste_contact(4,$source);
				$num=sizeof($userRole);

				$i = 0;
				while ($i < $num)
				{
					if ($userRole[$i]['code'] == 'ACCOUNTMANAGER' && $user->id == $userRole[$i]['id'])
					{
						$userAccess++;
					}
					$i++;
				}
			}
		}

		if (!$userAccess && !$this->public)
		{
			if (!$list)
			{
				accessforbidden('',0);
			}
			else
			{
				return -1;
			}

		}

		return $userAccess;
	}

	/**
	 * Return array of business authorized for a user
	 *
	 * @param unknown_type $user
	 * @return unknown
	 */
	function getBusinessAuthorizedForUser($user,$mine=0,$list=0)
	{
		global $conf;

		$business = array();
		$temp = array();

		$sql = "SELECT DISTINCT b.rowid, b.ref";
		$sql.= " FROM ".MAIN_DB_PREFIX."business as b";
		$sql.= ", ".MAIN_DB_PREFIX."element_contact as ec";
		$sql.= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
		$sql.= " WHERE b.entity = ".$conf->entity;

		if ($mine)
		{
			$sql.= " AND ec.element_id = b.rowid";
			$sql.= " AND ctc.rowid = ec.fk_c_type_contact";
			$sql.= " AND ctc.element = '".$this->element."'";
			$sql.= " AND ec.fk_socpeople = ".$user->id;
		}
		else
		{
			$sql.= " AND ( b.public = 1";
			$sql.= " OR b.fk_user_creat = ".$user->id;
			$sql.= " OR ( ec.element_id = b.rowid";
			$sql.= " AND ctc.rowid = ec.fk_c_type_contact";
			$sql.= " AND ctc.element = '".$this->element."'";
			$sql.= " AND ec.fk_socpeople = ".$user->id." ) )";
		}

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $this->db->fetch_row($resql);
				$business[$row[0]] = $row[1];
				$temp[] = $row[0];
				$i++;
			}

			$this->db->free($resql);

			if ($list)
			{
				if (empty($temp)) return 0;
				$result = implode(',',$temp);
				return $result;
			}
		}
		else
		{
			dol_print_error($this->db);
		}

		return $business;
	}
	
	/**
	 * Return list of roles for a user for each projects or each tasks (or a particular project or task)
	 * @param 	user
	 * @param 	businessid		Business id to filter on a business
	 * @return 	array			Array (businessid => 'list of roles for business')
	 */
	function getUserRolesForBusiness($user,$businessid=0)
	{
		$businessrole = array();

		dol_syslog("Phase::getUserRolesForBusiness user=".is_object($user)." businessid=".$businessid);

		$sql = "SELECT b.rowid as businessid, ec.element_id, ctc.code";
		$sql.= " FROM ".MAIN_DB_PREFIX."business as b";
		$sql.= ", ".MAIN_DB_PREFIX."element_contact as ec";
		$sql.= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
		$sql.= " WHERE b.rowid = ec.element_id";
		$sql.= " AND ctc.element = 'business'";
		$sql.= " AND ctc.rowid = ec.fk_c_type_contact";
		if (is_object($user)) $sql.= " AND ec.fk_socpeople = ".$user->id;
		$sql.= " AND ec.statut = 4";
		if ($businessid) $sql.= " AND b.rowid = ".$businessid;

		print $sql.'<br>';
		dol_syslog("Phase::getUserRolesForBusiness sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				if (empty($businessrole[$obj->businessid])) $businessrole[$obj->businessid] = $obj->code;
				else $businessrole[$obj->businessid].=','.$obj->code;
				$i++;
			}
			$this->db->free($resql);
		}
		else
		{
			dol_print_error($this->db);
		}

		return $businessrole;
	}

}
?>
