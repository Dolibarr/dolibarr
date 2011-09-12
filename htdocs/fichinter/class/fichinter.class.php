<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 * 	\file       htdocs/fichinter/class/fichinter.class.php
 * 	\ingroup    ficheinter
 * 	\brief      Fichier de la classe des gestion des fiches interventions
 */
require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");


/**
 * 	\class      Fichinter
 *	\brief      Classe des gestion des fiches interventions
 */
class Fichinter extends CommonObject
{
	var $db;
	var $element='fichinter';
	var $table_element='fichinter';
	var $fk_element='fk_fichinter';
	var $table_element_line='fichinterdet';

	var $id;

	var $socid;		// Id client
	var $client;		// Objet societe client (a charger par fetch_client)

	var $author;
	var $ref;
	var $datec;
	var $datev;
	var $datem;
	var $duree;
	var $statut;		// 0=draft, 1=validated, 2=invoiced
	var $description;
	var $note_private;
	var $note_public;
	var $fk_project;
	var $modelpdf;

	var $lines = array();

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$DB      Database handler
	 */
	function Fichinter($DB)
	{
		$this->db = $DB ;
		$this->products = array();
		$this->fk_project = 0;
		$this->statut = 0;

		// List of language codes for status
		$this->statuts[0]='Draft';
		$this->statuts[1]='Validated';
		$this->statuts[2]='Invoiced';
		$this->statuts_short[0]='Draft';
		$this->statuts_short[1]='Validated';
		$this->statuts_short[2]='Invoiced';
	}


	/**
	 *	Create a intervention into data base
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function create()
	{
		global $conf;

		dol_syslog("Fichinter.class::create ref=".$this->ref);

		// Check parameters
		if (! is_numeric($this->duree)) { $this->duree = 0; }
		if ($this->socid <= 0)
		{
			$this->error='ErrorBadParameterForFunc';
			dol_syslog("Fichinter::create ".$this->error,LOG_ERR);
			return -1;
		}
		// on verifie si la ref n'est pas utilisee
		$soc = new Societe($this->db);
		$result=$soc->fetch($this->socid);
		if (! empty($this->ref))
		{
			$result=$this->verifyNumRef();	// Check ref is not yet used
			if ($result > 0)
			{
				$this->error='ErrorRefAlreadyExists';
				dol_syslog("Fichinter::create ".$this->error,LOG_WARNING);
				$this->db->rollback();
				return -3;
			}
			else if ($result < 0)
			{
				$this->error=$this->db->error();
				dol_syslog("Fichinter::create ".$this->error,LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		}

		$now=dol_now();

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."fichinter (";
		$sql.= "fk_soc";
		$sql.= ", datec";
		$sql.= ", ref";
		$sql.= ", entity";
		$sql.= ", fk_user_author";
		$sql.= ", description";
		$sql.= ", model_pdf";
		$sql.= ", fk_projet";
		$sql.= ", fk_statut";
		$sql.= ") ";
		$sql.= " VALUES (";
		$sql.= $this->socid;
		$sql.= ", '".$this->db->idate($now)."'";
		$sql.= ", '".$this->ref."'";
		$sql.= ", ".$conf->entity;
		$sql.= ", ".$this->author;
		$sql.= ", ".($this->description?"'".$this->db->escape($this->description)."'":"null");
		$sql.= ", '".$this->modelpdf."'";
		$sql.= ", ".($this->fk_project ? $this->fk_project : 0);
		$sql.= ", ".$this->statut;
		$sql.= ")";

		dol_syslog("Fichinter::create sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
			$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX."fichinter");
			$this->db->commit();
			return $this->id;
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("Fichinter::create ".$this->error,LOG_ERR);
			$this->db->rollback();
			return -1;
		}

	}

	/**
	 *	Update a intervention
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function update($user)
	{
		global $conf;

		if (! is_numeric($this->duree)) { $this->duree = 0; }
		if (! dol_strlen($this->fk_project)) { $this->fk_project = 0; }

		/*
		 *  Insertion dans la base
		 */
		$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter SET ";
		$sql.= ", description  = '".$this->db->escape($this->description)."'";
		$sql.= ", duree = ".$this->duree;
		$sql.= ", fk_projet = ".$this->fk_project;
		$sql.= " WHERE rowid = ".$this->id;
		$sql.= " AND entity = ".$conf->entity;

		dol_syslog("Fichinter::update sql=".$sql);
		if (! $this->db->query($sql))
		{
			$this->error=$this->db->error();
			dol_syslog("Fichinter::update error ".$this->error,LOG_ERR);
			return -1;
		}

		return 1;
	}

	/**
	 *	Fetch a intervention
	 *	@param		rowid		Id of intervention
	 *	@param		ref			Ref of intervention
	 *	@return		int			<0 if ko, >0 if ok
	 */
	function fetch($rowid,$ref='')
	{
		$sql = "SELECT rowid, ref, description, fk_soc, fk_statut,";
		$sql.= " datec,";
		$sql.= " date_valid as datev,";
		$sql.= " tms as datem,";
		$sql.= " duree, fk_projet, note_public, note_private, model_pdf";
		$sql.= " FROM ".MAIN_DB_PREFIX."fichinter as f";
		if ($ref) $sql.= " WHERE f.ref='".$ref."'";
		else $sql.= " WHERE f.rowid=".$rowid;

		dol_syslog("Fichinter::fetch sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id           = $obj->rowid;
				$this->ref          = $obj->ref;
				$this->description  = $obj->description;
				$this->socid        = $obj->fk_soc;
				$this->statut       = $obj->fk_statut;
				$this->duree        = $obj->duree;
				$this->datec        = $this->db->jdate($obj->datec);
				$this->datev        = $this->db->jdate($obj->datev);
				$this->datem        = $this->db->jdate($obj->datem);
				$this->fk_project   = $obj->fk_projet;
				$this->note_public  = $obj->note_public;
				$this->note_private = $obj->note_private;
				$this->modelpdf     = $obj->model_pdf;

				if ($this->statut == 0) $this->brouillon = 1;

				/*
				 * Lines
				 */
				$result=$this->fetch_lines();
				if ($result < 0)
				{
					return -3;
				}
				$this->db->free($resql);
				return 1;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("Fichinter::fetch ".$this->error,LOG_ERR);
			return -1;
		}
	}

	/**
	 *	Set status to draft
	 *	@return		int			<0 if KO, >0 if OK
	 */
	function setDraft($user)
	{
		global $langs, $conf;

		if ($this->statut != 0)
		{
			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter";
			$sql.= " SET fk_statut = 0";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			dol_syslog("Fichinter::setDraft sql=".$sql);
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
				dol_syslog("Fichinter::setDraft ".$this->error,LOG_ERR);
				return -1;
			}
		}
	}

	/**
	 *	Validate a intervention
	 *	@param		user		User that validate
	 *	@param		outputdir
	 *	@return		int			<0 if KO, >0 if OK
	 */
	function setValid($user, $outputdir)
	{
		global $langs, $conf;

		if ($this->statut != 1)
		{
			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter";
			$sql.= " SET fk_statut = 1";
			$sql.= ", date_valid = ".$this->db->idate(mktime());
			$sql.= ", fk_user_valid = ".$user->id;
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			$sql.= " AND fk_statut = 0";

			dol_syslog("Fichinter::setValid sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				// Appel des triggers
				include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers('FICHEINTER_VALIDATE',$this,$user,$langs,$conf);
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
					dol_syslog("Fichinter::setValid ".$this->error,LOG_ERR);
					return -1;
				}
			}
			else
			{
				$this->db->rollback();
				$this->error=$this->db->lasterror();
				dol_syslog("Fichinter::setValid ".$this->error,LOG_ERR);
				return -1;
			}
		}
	}

	/**
	 * 	Set intervetnion as billed
	 *  @return int     <0 si ko, >0 si ok
	 */
	function setBilled()
	{
		global $conf;

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'fichinter SET fk_statut = 2';
		$sql.= ' WHERE rowid = '.$this->id;
		$sql.= " AND entity = ".$conf->entity;
		$sql.= " AND fk_statut = 1";

		if ($this->db->query($sql) )
		{
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *	Returns the label status
	 *	@param      mode        0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return     string      Label
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *	Returns the label of a statut
	 *	@param      statut      id statut
	 *	@param      mode        0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return     string      Label
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
			if ($statut==2) return img_picto($langs->trans('StatusInterInvoiced'),'statut6').' '.$langs->trans('StatusOrderProcessed');
		}
		if ($mode == 3)
		{
			if ($statut==0) return img_picto($langs->trans($this->statuts_short[$statut]),'statut0');
			if ($statut==1) return img_picto($langs->trans($this->statuts_short[$statut]),'statut4');
			if ($statut==2) return img_picto($langs->trans('StatusInterInvoiced'),'statut6');
		}
		if ($mode == 4)
		{
			if ($statut==0) return img_picto($langs->trans($this->statuts_short[$statut]),'statut0').' '.$langs->trans($this->statuts[$statut]);
			if ($statut==1) return img_picto($langs->trans($this->statuts_short[$statut]),'statut4').' '.$langs->trans($this->statuts[$statut]);
			if ($statut==2) return img_picto($langs->trans('StatusInterInvoiced'),'statut6').' '.$langs->trans('StatusInterInvoiced');
		}
		if ($mode == 5)
		{
			if ($statut==0) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut0');
			if ($statut==1) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut4');
			if ($statut==2) return $langs->trans('StatusInterInvoiced').' '.img_picto($langs->trans('StatusInterInvoiced'),'statut6');
		}
	}

	/**
	 *	Return clicable name (with picto eventually)
	 *	@param		withpicto		0=_No picto, 1=Includes the picto in the linkn, 2=Picto only
	 *	@return		string			String with URL
	 */
	function getNomUrl($withpicto=0)
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/fichinter/fiche.php?id='.$this->id.'">';
		$lienfin='</a>';

		$picto='intervention';

		$label=$langs->trans("Show").': '.$this->ref;

		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
		return $result;
	}


	/**
	 *	Returns the next non used reference of intervention
	 *	depending on the module numbering assets within FICHEINTER_ADDON
	 *	@param	    soc		Object society
	 *	@return     string	Free reference for intervention
	 */
	function getNextNumRef($soc)
	{
		global $conf, $db, $langs;
		$langs->load("interventions");

		$dir = DOL_DOCUMENT_ROOT . "/includes/modules/fichinter/";

		if (! empty($conf->global->FICHEINTER_ADDON))
		{
			$file = $conf->global->FICHEINTER_ADDON.".php";
			$classname = $conf->global->FICHEINTER_ADDON;
			if (! file_exists($dir.$file))
			{
				$file='mod_'.$file;
				$classname='mod_'.$classname;
			}

			// Chargement de la classe de numerotation
			require_once($dir.$file);

			$obj = new $classname();

			$numref = "";
			$numref = $obj->getNumRef($soc,$this);

			if ( $numref != "")
			{
				return $numref;
			}
			else
			{
				dol_print_error($db,"Fichinter::getNextNumRef ".$obj->error);
				return "";
			}
		}
		else
		{
			print $langs->trans("Error")." ".$langs->trans("Error_FICHEINTER_ADDON_NotDefined");
			return "";
		}
	}

	/**
	 * 	Information sur l'objet fiche intervention
	 *	@param      id      id de la fiche d'intervention
	 */
	function info($id)
	{
		global $conf;

		$sql = "SELECT f.rowid,";
		$sql.= " datec,";
		$sql.= " f.date_valid as datev,";
		$sql.= " f.fk_user_author,";
		$sql.= " f.fk_user_valid";
		$sql.= " FROM ".MAIN_DB_PREFIX."fichinter as f";
		$sql.= " WHERE f.rowid = ".$id;
		$sql.= " AND f.entity = ".$conf->entity;

		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id                = $obj->rowid;

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_validation   = $this->db->jdate($obj->datev);

				$cuser = new User($this->db);
				$cuser->fetch($obj->fk_user_author);
				$this->user_creation     = $cuser;

				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation     = $vuser;
				}
			}
			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 *	Delete intervetnion
	 *	@param      user	Object user who deletes
	 */
	function delete($user)
	{
		global $conf;
        require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");

		$error=0;

		$this->db->begin();

		// Delete linked object
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."element_element";
		$sql.= " WHERE fk_target = ".$this->id;
		$sql.= " AND targettype = '".$this->element."'";
		dol_syslog("Fichinter::delete sql=".$sql);
		if (! $this->db->query($sql) )
		{
			dol_syslog("Fichinter::delete error", LOG_ERR);
			$error++;
		}

		// Delete linked contacts
		$res = $this->delete_linked_contact();
		if ($res < 0)
		{
			$this->error='ErrorFailToDeleteLinkedContact';
			$error++;
		}

		if ($err > 0)
		{
			$this->db->rollback();
			return -1;
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."fichinterdet";
		$sql.= " WHERE fk_fichinter = ".$this->id;

		dol_syslog("Fichinter::delete sql=".$sql);
		if ( $this->db->query($sql) )
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."fichinter";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			dol_syslog("Fichinter::delete sql=".$sql);
			if ( $this->db->query($sql) )
			{

				// Remove directory with files
				$fichinterref = dol_sanitizeFileName($this->ref);
				if ($conf->ficheinter->dir_output)
				{
					$dir = $conf->ficheinter->dir_output . "/" . $fichinterref ;
					$file = $conf->ficheinter->dir_output . "/" . $fichinterref . "/" . $fichinterref . ".pdf";
					if (file_exists($file))
					{
						fichinter_delete_preview($this->db, $this->id, $this->ref);

						if (!dol_delete_file($file))
						{
							$this->error=$langs->trans("ErrorCanNotDeleteFile",$file);
							return 0;
						}
					}
					if (file_exists($dir))
					{
						if (!dol_delete_dir($dir))
						{
							$this->error=$langs->trans("ErrorCanNotDeleteDir",$dir);
							return 0;
						}
					}
				}

				$this->db->commit();
				return 1;
			}
			else
			{
				$this->error=$this->db->lasterror();
				$this->db->rollback();
				return -2;
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
	 *	Defines a delivery date of intervention
	 *	@param      user			Object user who define
	 *	@param      date_delivery   date of delivery
	 *	@return     int				<0 if ko, >0 if ok
	 */
	function set_date_delivery($user, $date_delivery)
	{
		global $conf;

		if ($user->rights->ficheinter->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter ";
			$sql.= " SET datei = ".$this->db->idate($date_delivery);
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			$sql.= " AND fk_statut = 0";

			if ($this->db->query($sql))
			{
				$this->date_delivery = $date_delivery;
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("Fichinter::set_date_delivery Erreur SQL");
				return -1;
			}
		}
	}

	/**
	 *	Define the label of the intervention
	 *	@param      user			Object user who modify
	 *	@param      description     description
	 *	@return     int				<0 if ko, >0 if ok
	 */
	function set_description($user, $description)
	{
		global $conf;

		if ($user->rights->ficheinter->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter ";
			$sql.= " SET description = '".$this->db->escape($description)."'";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			$sql.= " AND fk_statut = 0";

			if ($this->db->query($sql))
			{
				$this->description = $description;
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("Fichinter::set_description Erreur SQL");
				return -1;
			}
		}
	}

	/**
	 *	Adding a line of intervention into data base
	 *	@param    	fichinterid			Id of intervention
	 *	@param    	desc				Line description
	 *	@param      date_intervention  	Intervention date
	 *	@param      duration            Intervention duration
	 *	@return    	int             	>0 if ok, <0 if ko
	 */
	function addline($fichinterid, $desc, $date_intervention, $duration)
	{
		dol_syslog("Fichinter::Addline $fichinterid, $desc, $date_intervention, $duration");

		if ($this->statut == 0)
		{
			$this->db->begin();

			// Insertion ligne
			$line=new FichinterLigne($this->db);

			$line->fk_fichinter = $fichinterid;
			$line->desc         = $desc;
			$line->datei        = $date_intervention;
			$line->duration     = $duration;

			$result=$line->insert();
			if ($result > 0)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("Error sql=$sql, error=".$this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}
		}
	}


	/**
	 *	Initializes the intervention with random values
	 *	Used to generate a intervention for the preview or demo models
	 */
	function initAsSpecimen()
	{
		global $user,$langs,$conf;

		$now=dol_now();

		// Initialise parametres
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;
		$this->socid = 1;
		$this->date = $now;
		$this->note_public='SPECIMEN';
		$this->duree = 0;
		$nbp = 5;
		$xnbp = 0;
		while ($xnbp < $nbp)
		{
			$line=new FichinterLigne($this->db);
			$line->desc=$langs->trans("Description")." ".$xnbp;
			$line->datei=($now-3600*(1+$xnbp));
			$line->duration=600;
            $line->fk_fichinter=0;
			$this->lines[$xnbp]=$line;
			$xnbp++;

			$this->duree+=$line->duration;
		}
	}

	/**
	 *	Load array lines
	 *	@return		int		<0 if Ko,	>0 if OK
	 */
	function fetch_lines()
	{
		$sql = 'SELECT rowid, description, duree, date, rang';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'fichinterdet';
		$sql.= ' where fk_fichinter = '.$this->id;

		dol_syslog("Fichinter::fetch_lines sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($resql);

				$line = new FichinterLigne($this->db);
				$line->id = $objp->rowid;
				$line->desc = $objp->description;
				//For invoicing we calculing hours
				$line->qty = round($objp->duree/3600,2);
				$line->date	= $this->db->jdate($objp->date);
				$line->rang	= $objp->rang;
				$line->product_type = 1;

				$this->lines[$i] = $line;

				$i++;
			}
			$this->db->free($resql);

			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}
}

/**
 *	\class      FichinterLigne
 *	\brief      Classe permettant la gestion des lignes d'intervention
 */
class FichinterLigne
{
	var $db;
	var $error;

	// From llx_fichinterdet
	var $rowid;
	var $fk_fichinter;
	var $desc;          	// Description ligne
	var $datei;           // Date intervention
	var $duration;        // Duree de l'intervention
	var $rang = 0;


	/**
	 *      \brief     Constructeur d'objets ligne d'intervention
	 *      \param     DB      handler d'acces base de donnee
	 */
	function FichinterLigne($DB)
	{
		$this->db= $DB;
	}

	/**
	 *	Retrieve the line of intervention
	 *	@param     rowid	line id
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT ft.rowid, ft.fk_fichinter, ft.description, ft.duree, ft.rang,';
		$sql.= ' ft.date as datei';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'fichinterdet as ft';
		$sql.= ' WHERE ft.rowid = '.$rowid;

		dol_syslog("FichinterLigne::fetch sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$objp = $this->db->fetch_object($result);
			$this->rowid          	= $objp->rowid;
			$this->fk_fichinter   	= $objp->fk_fichinter;
			$this->datei			= $this->db->jdate($objp->datei);
			$this->desc           	= $objp->description;
			$this->duration       	= $objp->duree;
			$this->rang           	= $objp->rang;

			$this->db->free($result);
			return 1;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			dol_print_error($this->db,$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *	Insert the line into database
	 *	@return		int		<0 if ko, >0 if ok
	 */
	function insert()
	{
		dol_syslog("FichinterLigne::insert rang=".$this->rang);

		$this->db->begin();

		$rangToUse=$this->rang;
		if ($rangToUse == -1)
		{
			// Recupere rang max de la ligne d'intervention dans $rangmax
			$sql = 'SELECT max(rang) as max FROM '.MAIN_DB_PREFIX.'fichinterdet';
			$sql.= ' WHERE fk_fichinter ='.$this->fk_fichinter;
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$obj = $this->db->fetch_object($resql);
				$rangToUse = $obj->max + 1;
			}
			else
			{
				dol_print_error($this->db);
				$this->db->rollback();
				return -1;
			}
		}

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'fichinterdet';
		$sql.= ' (fk_fichinter, description, date, duree, rang)';
		$sql.= " VALUES (".$this->fk_fichinter.",";
		$sql.= " '".$this->db->escape($this->desc)."',";
		$sql.= " ".$this->db->idate($this->datei).",";
		$sql.= " ".$this->duration.",";
		$sql.= ' '.$rangToUse;
		$sql.= ')';

		dol_syslog("FichinterLigne::insert sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$result=$this->update_total();
			if ($result > 0)
			{
				$this->rang=$rangToUse;
				$this->db->commit();
				return $result;
			}
			else
			{
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->error()." sql=".$sql;
			dol_syslog("FichinterLigne::insert Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Update intervention into database
	 *	@return		int		<0 if ko, >0 if ok
	 */
	function update()
	{
		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."fichinterdet SET";
		$sql.= " description='".$this->db->escape($this->desc)."'";
		$sql.= ",date=".$this->db->idate($this->datei);
		$sql.= ",duree=".$this->duration;
		$sql.= ",rang='".$this->rang."'";
		$sql.= " WHERE rowid = ".$this->rowid;

		dol_syslog("FichinterLigne::update sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$result=$this->update_total();
			if ($result > 0)
			{
				$this->db->commit();
				return $result;
			}
			else
			{
				$this->error=$this->db->lasterror();
				dol_syslog("FichinterLigne::update Error ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog("FichinterLigne::update Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Update total duration into llx_fichinter
	 *	@return		int		<0 si ko, >0 si ok
	 */
	function update_total()
	{
		global $conf;

		$this->db->begin();

		$sql = "SELECT SUM(duree) as total_duration";
		$sql.= " FROM ".MAIN_DB_PREFIX."fichinterdet";
		$sql.= " WHERE fk_fichinter=".$this->fk_fichinter;

		dol_syslog("FichinterLigne::update_total sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj=$this->db->fetch_object($resql);
			$total_duration=0;
			if (!empty($obj->total_duration)) $total_duration = $obj->total_duration;

			$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter";
			$sql.= " SET duree = ".$total_duration;
			$sql.= " WHERE rowid = ".$this->fk_fichinter;
			$sql.= " AND entity = ".$conf->entity;

			dol_syslog("FichinterLigne::update_total sql=".$sql);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				dol_syslog("FichinterLigne::update_total Error ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			dol_syslog("FichinterLigne::update Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Delete a intervention line
	 *	@return     int		>0 if ok, <0 if ko
	 */
	function deleteline()
	{
		if ($this->statut == 0)
		{
			dol_syslog("FichinterLigne::deleteline lineid=".$this->rowid);
			$this->db->begin();

			$sql = "DELETE FROM ".MAIN_DB_PREFIX."fichinterdet WHERE rowid = ".$this->rowid;
			$resql = $this->db->query($sql);
			dol_syslog("FichinterLigne::deleteline sql=".$sql);

			if ($resql)
			{
				$result = $this->update_total();
				if ($result > 0)
				{
					$this->db->commit();
					return $result;
				}
				else
				{
					$this->db->rollback();
					return -1;
				}
			}
			else
			{
				$this->error=$this->db->error()." sql=".$sql;
				dol_syslog("FichinterLigne::deleteline Error ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			return -2;
		}
	}

}

?>