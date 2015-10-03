<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2013 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 * 	\file       htdocs/fichinter/class/fichinter.class.php
 * 	\ingroup    ficheinter
 * 	\brief      Fichier de la classe des gestion des fiches interventions
 */
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobjectline.class.php';


/**
 *	Class to manage interventions
 */
class Fichinter extends CommonObject
{
	public $element='fichinter';
	public $table_element='fichinter';
	public $fk_element='fk_fichinter';
	public $table_element_line='fichinterdet';

	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'ref';

	var $socid;		// Id client

	var $author;
	var $datec;
	var $datev;
	var $datem;
	var $duration;
	var $statut;		// 0=draft, 1=validated, 2=invoiced
	var $description;
	var $fk_contrat;
	var $extraparams=array();

	var $lines = array();

	/**
	 *	Constructor
	 *
	 *  @param	DoliDB	$db		Database handler
 	 */
	function __construct($db)
	{
		$this->db = $db;
		$this->products = array();
		$this->fk_project = 0;
		$this->fk_contrat = 0;
		$this->statut = 0;

		// List of language codes for status
		$this->statuts[0]='Draft';
		$this->statuts[1]='Validated';
		$this->statuts[2]='StatusInterInvoiced';
		$this->statuts_short[0]='Draft';
		$this->statuts_short[1]='Validated';
		$this->statuts_short[2]='StatusInterInvoiced';
		$this->statuts_logo[0]='statut0';
		$this->statuts_logo[1]='statut4';
		$this->statuts_logo[2]='statut6';
	}


	/**
	 *	Create an intervention into data base
	 *
	 *  @param		User	$user 		Objet user that make creation
     *	@param		int		$notrigger	Disable all triggers
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function create($user, $notrigger=0)
	{
		global $conf, $user, $langs;

		dol_syslog(get_class($this)."::create ref=".$this->ref);

		// Check parameters
		if (! empty($this->ref))	// We check that ref is not already used
		{
			$result=self::isExistingObject($this->element, 0, $this->ref);	// Check ref is not yet used
			if ($result > 0)
			{
				$this->error='ErrorRefAlreadyExists';
				dol_syslog(get_class($this)."::create ".$this->error,LOG_WARNING);
				$this->db->rollback();
				return -1;
			}
		}
		if (! is_numeric($this->duration)) $this->duration = 0;

		if ($this->socid <= 0)
		{
			$this->error='ErrorBadParameterForFunc';
			dol_syslog(get_class($this)."::create ".$this->error,LOG_ERR);
			return -1;
		}

		$soc = new Societe($this->db);
		$result=$soc->fetch($this->socid);

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
		$sql.= ", fk_contrat";
		$sql.= ", fk_statut";
		$sql.= ", note_private";
		$sql.= ", note_public";
		$sql.= ") ";
		$sql.= " VALUES (";
		$sql.= $this->socid;
		$sql.= ", '".$this->db->idate($now)."'";
		$sql.= ", '".$this->db->escape($this->ref)."'";
		$sql.= ", ".$conf->entity;
		$sql.= ", ".$user->id;
		$sql.= ", ".($this->description?"'".$this->db->escape($this->description)."'":"null");
		$sql.= ", '".$this->db->escape($this->modelpdf)."'";
		$sql.= ", ".($this->fk_project ? $this->fk_project : 0);
		$sql.= ", ".($this->fk_contrat ? $this->fk_contrat : 0);
		$sql.= ", ".$this->statut;
		$sql.= ", ".($this->note_private?"'".$this->db->escape($this->note_private)."'":"null");
		$sql.= ", ".($this->note_public?"'".$this->db->escape($this->note_public)."'":"null");
		$sql.= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$result=$this->db->query($sql);
		if ($result)
		{
			$this->id=$this->db->last_insert_id(MAIN_DB_PREFIX."fichinter");

			if ($this->id)
			{
				$this->ref='(PROV'.$this->id.')';
				$sql = 'UPDATE '.MAIN_DB_PREFIX."fichinter SET ref='".$this->ref."' WHERE rowid=".$this->id;

				dol_syslog(get_class($this)."::create", LOG_DEBUG);
				$resql=$this->db->query($sql);
				if (! $resql) $error++;
			}

			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
            {

            	$result=$this->insertExtraFields();
            	if ($result < 0)
            	{
            		$error++;
            	}
            }

			// Add linked object
			if (! $error && $this->origin && $this->origin_id)
			{
				$ret = $this->add_object_linked();
				if (! $ret)	dol_print_error($this->db);
			}


            if (! $notrigger)
            {
                // Call trigger
                $result=$this->call_trigger('FICHINTER_CREATE',$user);
                if ($result < 0) { $error++; }
                // End call triggers
            }

			if (! $error)
			{
				$this->db->commit();
				return $this->id;
			}
			else
			{
				$this->db->rollback();
				$this->error=join(',',$this->errors);
				dol_syslog(get_class($this)."::create ".$this->error,LOG_ERR);
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}

	}

	/**
	 *	Update an intervention
	 *
	 *	@param		User	$user 		Objet user that make creation
     *	@param		int		$notrigger	Disable all triggers
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function update($user, $notrigger=0)
	{
	 	if (! is_numeric($this->duration)) {
	 		$this->duration = 0;
	 	}
	 	if (! dol_strlen($this->fk_project)) {
	 		$this->fk_project = 0;
	 	}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter SET ";
		$sql.= "description  = '".$this->db->escape($this->description)."'";
		$sql.= ", duree = ".$this->duration;
		$sql.= ", fk_projet = ".$this->fk_project;
		$sql.= ", note_private = ".($this->note_private?"'".$this->db->escape($this->note_private)."'":"null");
		$sql.= ", note_public = ".($this->note_public?"'".$this->db->escape($this->note_public)."'":"null");
		$sql.= ", fk_user_modif = ".$user->id;
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		if ($this->db->query($sql))
		{

			if (! $notrigger)
			{
                // Call trigger
                $result=$this->call_trigger('FICHINTER_MODIFY',$user);
                if ($result < 0) { $error++; $this->db->rollback(); return -1; }
                // End call triggers
			}

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Fetch a intervention
	 *
	 *	@param		int		$rowid		Id of intervention
	 *	@param		string	$ref		Ref of intervention
	 *	@return		int					<0 if KO, >0 if OK
	 */
	function fetch($rowid,$ref='')
	{
		$sql = "SELECT f.rowid, f.ref, f.description, f.fk_soc, f.fk_statut,";
		$sql.= " f.datec,";
		$sql.= " f.date_valid as datev,";
		$sql.= " f.tms as datem,";
		$sql.= " f.duree, f.fk_projet, f.note_public, f.note_private, f.model_pdf, f.extraparams, fk_contrat";
		$sql.= " FROM ".MAIN_DB_PREFIX."fichinter as f";
		if ($ref) $sql.= " WHERE f.ref='".$this->db->escape($ref)."'";
		else $sql.= " WHERE f.rowid=".$rowid;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
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
				$this->duration     = $obj->duree;
				$this->datec        = $this->db->jdate($obj->datec);
				$this->datev        = $this->db->jdate($obj->datev);
				$this->datem        = $this->db->jdate($obj->datem);
				$this->fk_project   = $obj->fk_projet;
				$this->note_public  = $obj->note_public;
				$this->note_private = $obj->note_private;
				$this->modelpdf     = $obj->model_pdf;
				$this->fk_contrat	= $obj->fk_contrat;

				$this->extraparams	= (array) json_decode($obj->extraparams, true);

				if ($this->statut == 0) $this->brouillon = 1;

				require_once(DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php');
				$extrafields=new ExtraFields($this->db);
				$extralabels=$extrafields->fetch_name_optionals_label($this->table_element,true);
				$this->fetch_optionals($this->id,$extralabels);

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
			return -1;
		}
	}

	/**
	 *	Set status to draft
	 *
	 *	@param		User	$user	User that set draft
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

			dol_syslog("Fichinter::setDraft", LOG_DEBUG);
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
	}

	/**
	 *	Validate a intervention
	 *
	 *	@param		User		$user		User that validate
     *  @param		int			$notrigger	1=Does not execute triggers, 0= execuete triggers
	 *	@return		int						<0 if KO, >0 if OK
	 */
	function setValid($user, $notrigger=0)
	{
		global $conf;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error=0;

		if ($this->statut != 1)
		{
			$this->db->begin();

			$now=dol_now();

			// Define new ref
			if (! $error && (preg_match('/^[\(]?PROV/i', $this->ref)))
			{
				$num = $this->getNextNumRef($this->thirdparty);
			}
			else
			{
				$num = $this->ref;
			}
            $this->newref = $num;

			$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter";
			$sql.= " SET fk_statut = 1";
			$sql.= ", ref = '".$num."'";
			$sql.= ", date_valid = '".$this->db->idate($now)."'";
			$sql.= ", fk_user_valid = ".$user->id;
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			$sql.= " AND fk_statut = 0";

			dol_syslog(get_class($this)."::setValid", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				dol_print_error($this->db);
				$error++;
			}

			if (! $error && ! $notrigger)
			{
                // Call trigger
                $result=$this->call_trigger('FICHINTER_VALIDATE',$user);
                if ($result < 0) { $error++; }
                // End call triggers
			}

			if (! $error)
			{
				$this->oldref = $this->ref;

				// Rename directory if dir was a temporary ref
				if (preg_match('/^[\(]?PROV/i', $this->ref))
				{
					// Rename of object directory ($this->ref = old ref, $num = new ref)
					// to  not lose the linked files
					$oldref = dol_sanitizeFileName($this->ref);
					$newref = dol_sanitizeFileName($num);
					$dirsource = $conf->ficheinter->dir_output.'/'.$oldref;
					$dirdest = $conf->ficheinter->dir_output.'/'.$newref;
					if (file_exists($dirsource))
					{
						dol_syslog(get_class($this)."::setValid rename dir ".$dirsource." into ".$dirdest);

						if (@rename($dirsource, $dirdest))
						{
	                        dol_syslog("Rename ok");
	                        // Rename docs starting with $oldref with $newref
	                        $listoffiles=dol_dir_list($conf->ficheinter->dir_output.'/'.$newref, 'files', 1, '^'.preg_quote($oldref,'/'));
	                        foreach($listoffiles as $fileentry)
	                        {
	                        	$dirsource=$fileentry['name'];
	                        	$dirdest=preg_replace('/^'.preg_quote($oldref,'/').'/',$newref, $dirsource);
	                        	$dirsource=$fileentry['path'].'/'.$dirsource;
	                        	$dirdest=$fileentry['path'].'/'.$dirdest;
	                        	@rename($dirsource, $dirdest);
	                        }
						}
					}
				}
			}

			// Set new ref and define current statut
			if (! $error)
			{
				$this->ref = $num;
				$this->statut=1;
				$this->brouillon=0;
				$this->date_validation=$now;
			}

			if (! $error)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				dol_syslog(get_class($this)."::setValid ".$this->error,LOG_ERR);
				return -1;
			}
		}
	}


	/**
	 *	Returns the label status
	 *
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return     string      		Label
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *	Returns the label of a statut
	 *
	 *	@param      int		$statut     id statut
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return     string      		Label
	 */
	function LibStatut($statut,$mode=0)
	{
		global $langs;

		if ($mode == 0)
			return $langs->trans($this->statuts[$statut]);

		if ($mode == 1)
			return $langs->trans($this->statuts_short[$statut]);

		if ($mode == 2)
			return img_picto($langs->trans($this->statuts_short[$statut]), $this->statuts_logo[$statut]).' '.$langs->trans($this->statuts_short[$statut]);

		if ($mode == 3)
			return img_picto($langs->trans($this->statuts_short[$statut]), $this->statuts_logo[$statut]);

		if ($mode == 4)
			return img_picto($langs->trans($this->statuts_short[$statut]),$this->statuts_logo[$statut]).' '.$langs->trans($this->statuts[$statut]);

		if ($mode == 5)
			return '<span class="hideonsmartphone">'.$langs->trans($this->statuts_short[$statut]).' </span>'.img_picto($langs->trans($this->statuts_short[$statut]),$this->statuts_logo[$statut]);

	}

	/**
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param		int		$withpicto		0=_No picto, 1=Includes the picto in the linkn, 2=Picto only
	 *	@param		string	$option			Options
	 *	@return		string					String with URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';
        $label = '<u>' . $langs->trans("ShowIntervention") . '</u>';
        if (! empty($this->ref))
            $label .= '<br><b>' . $langs->trans('Ref') . ':</b> '.$this->ref;

        $link = '<a href="'.DOL_URL_ROOT.'/fichinter/card.php?id='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend='</a>';

		$picto='intervention';


        if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$link.$this->ref.$linkend;
		return $result;
	}


	/**
	 *	Returns the next non used reference of intervention
	 *	depending on the module numbering assets within FICHEINTER_ADDON
	 *
	 *	@param	    Societe		$soc		Thirdparty object
	 *	@return     string					Free reference for intervention
	 */
	function getNextNumRef($soc)
	{
		global $conf, $db, $langs;
		$langs->load("interventions");

		if (! empty($conf->global->FICHEINTER_ADDON))
		{
			$mybool = false;

			$file = "mod_".$conf->global->FICHEINTER_ADDON.".php";
			$classname = "mod_".$conf->global->FICHEINTER_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

			foreach ($dirmodels as $reldir) {

				$dir = dol_buildpath($reldir."core/modules/fichinter/");

				// Load file with numbering class (if found)
				$mybool|=@include_once $dir.$file;
			}

			if (! $mybool)
			{
				dol_print_error('',"Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			$numref = "";
			$numref = $obj->getNextValue($soc,$this);

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
			$langs->load("errors");
			print $langs->trans("Error")." ".$langs->trans("Error_FICHEINTER_ADDON_NotDefined");
			return "";
		}
	}

	/**
	 * 	Load information on object
	 *
	 *	@param	int		$id      Id of object
	 *	@return	void
	 */
	function info($id)
	{
		global $conf;

		$sql = "SELECT f.rowid,";
		$sql.= " f.datec,";
		$sql.= " f.tms as date_modification,";
		$sql.= " f.date_valid as datev,";
		$sql.= " f.fk_user_author,";
		$sql.= " f.fk_user_modif as fk_user_modification,";
		$sql.= " f.fk_user_valid";
		$sql.= " FROM ".MAIN_DB_PREFIX."fichinter as f";
		$sql.= " WHERE f.rowid = ".$id;
		$sql.= " AND f.entity = ".$conf->entity;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id                = $obj->rowid;

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->date_modification);
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
				if ($obj->fk_user_modification)
				{
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_modification);
					$this->user_modification   = $muser;
				}

			}
			$this->db->free($resql);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 *	Delete intervetnion
	 *
	 *	@param      User	$user			Object user who delete
	 *	@param		int		$notrigger		Disable trigger
	 *	@return		int						<0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf,$langs;
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error=0;

		$this->db->begin();

		// Delete linked object
		$res = $this->deleteObjectLinked();
		if ($res < 0) $error++;

		// Delete linked contacts
		$res = $this->delete_linked_contact();
		if ($res < 0)
		{
			$this->error='ErrorFailToDeleteLinkedContact';
			$error++;
		}

		if ($error)
		{
			$this->db->rollback();
			return -1;
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."fichinterdet";
		$sql.= " WHERE fk_fichinter = ".$this->id;

		dol_syslog("Fichinter::delete", LOG_DEBUG);
		if ( $this->db->query($sql) )
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."fichinter";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			dol_syslog("Fichinter::delete", LOG_DEBUG);
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
						dol_delete_preview($this);

						if (! dol_delete_file($file,0,0,0,$this)) // For triggers
						{
							$this->error=$langs->trans("ErrorCanNotDeleteFile",$file);
							return 0;
						}
					}
					if (file_exists($dir))
					{
						if (! dol_delete_dir_recursive($dir))
						{
							$this->error=$langs->trans("ErrorCanNotDeleteDir",$dir);
							return 0;
						}
					}
				}

				if (! $notrigger)
				{
                    // Call trigger
                    $result=$this->call_trigger('FICHINTER_DELETE',$user);
                    if ($result < 0) { $error++; $this->db->rollback(); return -1; }
                    // End call triggers
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
	 *
	 *	@param      User	$user				Object user who define
	 *	@param      date	$date_delivery   	date of delivery
	 *	@return     int							<0 if ko, >0 if ok
	 */
	function set_date_delivery($user, $date_delivery)
	{
		global $conf;

		if ($user->rights->ficheinter->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter ";
			$sql.= " SET datei = '".$this->db->idate($date_delivery)."'";
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
	 *
	 *	@param      User	$user			Object user who modify
	 *	@param      string	$description    description
	 *	@return     int						<0 if KO, >0 if OK
	 */
	function set_description($user, $description)
	{
		global $conf;

		if ($user->rights->ficheinter->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter ";
			$sql.= " SET description = '".$this->db->escape($description)."',";
			$sql.= " fk_user_modif = ".$user->id;
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

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
	 *	Link intervention to a contract
	 *
	 *	@param      User	$user			Object user who modify
	 *	@param      int		$contractid		Description
	 *	@return     int						<0 if ko, >0 if ok
	 */
	function set_contrat($user, $contractid)
	{
		global $conf;

		if ($user->rights->ficheinter->creer)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."fichinter ";
			$sql.= " SET fk_contrat = '".$contractid."'";
			$sql.= " WHERE rowid = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;

			if ($this->db->query($sql))
			{
				$this->fk_contrat = $contractid;
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				return -1;
			}
		}
		return -2;
	}

	/**
	 *	Adding a line of intervention into data base
	 *
	 *  @param      user	$user					User that do the action
	 *	@param    	int		$fichinterid			Id of intervention
	 *	@param    	string	$desc					Line description
	 *	@param      date	$date_intervention  	Intervention date
	 *	@param      int		$duration            	Intervention duration
	 *  @param		array	$array_options			Array option
	 *	@return    	int             				>0 if ok, <0 if ko
	 */
	function addline($user,$fichinterid, $desc, $date_intervention, $duration, $array_options='')
	{
		dol_syslog(get_class($this)."::addline $fichinterid, $desc, $date_intervention, $duration");

		if ($this->statut == 0)
		{
			$this->db->begin();

			// Insertion ligne
			$line=new FichinterLigne($this->db);

			$line->fk_fichinter = $fichinterid;
			$line->desc         = $desc;
			$line->datei        = $date_intervention;
			$line->duration     = $duration;

			if (is_array($array_options) && count($array_options)>0) {
				$line->array_options=$array_options;
			}

			$result=$line->insert($user);

			if ($result >= 0)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				$this->db->rollback();
				return -1;
			}
		}
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
		global $user,$langs,$conf;

		$now=dol_now();

		// Initialise parametres
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;
		$this->socid = 1;
		$this->datec = $now;
		$this->note_private='Private note';
		$this->note_public='SPECIMEN';
		$this->duration = 0;
		$nbp = 25;
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

			$this->duration+=$line->duration;
		}
	}

	/**
	 *	Load array lines ->lines
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function fetch_lines()
	{
		$sql = 'SELECT rowid, description, duree, date, rang';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'fichinterdet';
		$sql.=' WHERE fk_fichinter = '.$this->id .' ORDER BY rang ASC' ;

		dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);
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

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old thirdparty id
	 * @param int $dest_id New thirdparty id
	 * @return bool
	 */
	public static function replaceThirdparty(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'fichinter'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}
}

/**
 *	Classe permettant la gestion des lignes d'intervention
 */
class FichinterLigne extends CommonObjectLine
{
	var $db;
	var $error;

	// From llx_fichinterdet
	var $fk_fichinter;
	var $desc;          	// Description ligne
	var $datei;           // Date intervention
	var $duration;        // Duree de l'intervention
	var $rang = 0;

	public $element='fichinterdet';
	public $table_element='fichinterdet';
	public $fk_element='fk_fichinter';

	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *	Retrieve the line of intervention
	 *
	 *	@param  int		$rowid		Line id
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function fetch($rowid)
	{
		$sql = 'SELECT ft.rowid, ft.fk_fichinter, ft.description, ft.duree, ft.rang,';
		$sql.= ' ft.date as datei';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'fichinterdet as ft';
		$sql.= ' WHERE ft.rowid = '.$rowid;

		dol_syslog("FichinterLigne::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$objp = $this->db->fetch_object($result);
			$this->rowid          	= $objp->rowid;
			$this->id 				= $objp->rowid;
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
			return -1;
		}
	}

	/**
	 *	Insert the line into database
	 *
	 *	@param		User	$user 		Objet user that make creation
     *	@param		int		$notrigger	Disable all triggers
	 *	@return		int		<0 if ko, >0 if ok
	 */
	function insert($user, $notrigger=0)
	{
		global $langs,$conf;

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
		$sql.= " '".$this->db->idate($this->datei)."',";
		$sql.= " ".$this->duration.",";
		$sql.= ' '.$rangToUse;
		$sql.= ')';

		dol_syslog("FichinterLigne::insert", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->rowid=$this->db->last_insert_id(MAIN_DB_PREFIX.'fichinterdet');

			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
            {
            	$this->id=$this->rowid;
            	$result=$this->insertExtraFields();
            	if ($result < 0)
            	{
            		$error++;
            	}
            }


			$result=$this->update_total();

			if ($result > 0)
			{
				$this->rang=$rangToUse;

				if (! $notrigger)
				{
                    // Call trigger
                    $result=$this->call_trigger('LINEFICHINTER_CREATE',$user);
                    if ($result < 0) { $error++; }
                    // End call triggers
				}
			}

			if (!$error) {
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
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Update intervention into database
	 *
	 *	@param		User	$user 		Objet user that make creation
     *	@param		int		$notrigger	Disable all triggers
	 *	@return		int		<0 if ko, >0 if ok
	 */
	function update($user,$notrigger=0)
	{
		global $langs,$conf;

		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."fichinterdet SET";
		$sql.= " description='".$this->db->escape($this->desc)."'";
		$sql.= ",date=".$this->db->idate($this->datei);
		$sql.= ",duree=".$this->duration;
		$sql.= ",rang='".$this->rang."'";
		$sql.= " WHERE rowid = ".$this->rowid;

		dol_syslog("FichinterLigne::update", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{

			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
        	{
        		$this->id=$this->rowid;
        		$result=$this->insertExtraFields();
        		if ($result < 0)
        		{
        			$error++;
        		}
        	}

			$result=$this->update_total();
			if ($result > 0)
			{

				if (! $notrigger)
				{
                    // Call trigger
                    $result=$this->call_trigger('LINEFICHINTER_UPDATE',$user);
                    if ($result < 0) { $error++; }
                    // End call triggers
				}
			}

			if (!$error)
			{
				$this->db->commit();
				return $result;
			}
			else
			{
				$this->error=$this->db->lasterror();
				$this->db->rollback();
				return -1;
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
	 *	Update total duration into llx_fichinter
	 *
	 *	@return		int		<0 si ko, >0 si ok
	 */
	function update_total()
	{
		global $conf;

		$this->db->begin();

		$sql = "SELECT SUM(duree) as total_duration";
		$sql.= " FROM ".MAIN_DB_PREFIX."fichinterdet";
		$sql.= " WHERE fk_fichinter=".$this->fk_fichinter;

		dol_syslog("FichinterLigne::update_total", LOG_DEBUG);
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

			dol_syslog("FichinterLigne::update_total", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->error=$this->db->error();
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Delete a intervention line
	 *
	 *	@param		User	$user 		Objet user that make creation
     *	@param		int		$notrigger	Disable all triggers
	 *	@return     int		>0 if ok, <0 if ko
	 */
	function deleteline($user,$notrigger=0)
	{
		global $langs,$conf;

		if ($this->statut == 0)
		{
			dol_syslog(get_class($this)."::deleteline lineid=".$this->rowid);
			$this->db->begin();

			$sql = "DELETE FROM ".MAIN_DB_PREFIX."fichinterdet WHERE rowid = ".$this->rowid;
			$resql = $this->db->query($sql);

			if ($resql)
			{
				$result = $this->update_total();
				if ($result > 0)
				{
					if (! $notrigger)
					{
                        // Call trigger
                        $result=$this->call_trigger('LINEFICHINTER_DELETE',$user);
                        if ($result < 0) { $error++; $this->db->rollback(); return -1; }
                        // End call triggers
					}

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

