<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2012 Regis Houssin        <regis.houssin@inodbox.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/ecm/class/ecmdirectory.class.php
 *  \ingroup    ecm
 *  \brief      This file is an example for a class file
 */

/**
 *  Class to manage ECM directories
 */
class EcmDirectory // extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element='ecm_directories';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	//public $table_element='ecm_directories';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'dir';

	/**
	 * @var int ID
	 */
	public $id;

	/**
     * @var string ECM directories label
     */
    public $label;

    /**
     * @var int ID
     */
	public $fk_parent;

	/**
	 * @var string description
	 */
	public $description;

	public $cachenbofdoc=-1;	// By default cache initialized with value 'not calculated'
	public $date_c;
	public $date_m;

	/**
     * @var int ID
     */
	public $fk_user_m;

	/**
     * @var int ID
     */
	public $fk_user_c;

	/**
	 * @var string Ref
	 */
	public $ref;

	public $cats=array();
	public $motherof=array();

	public $forbiddenchars = array('<','>',':','/','\\','?','*','|','"');
	public $forbiddencharsdir = array('<','>',':','?','*','|','"');

	public $full_arbo_loaded;

	/**
	 * @var string Error code (or message)
	 */
	public $error;

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		return 1;
	}


	/**
	 *  Create record into database
	 *
	 *  @param      User	$user       User that create
	 *  @return     int      			<0 if KO, >0 if OK
	 */
	public function create($user)
	{
		global $conf, $langs;

		$error=0;
		$now=dol_now();

		// Clean parameters
		$this->label=dol_sanitizeFileName(trim($this->label));
		$this->fk_parent=trim($this->fk_parent);
		$this->description=trim($this->description);
		$this->date_c=$now;
		$this->fk_user_c=$user->id;
		if ($this->fk_parent <= 0) $this->fk_parent=0;


		// Check if same directory does not exists with this name
		$relativepath=$this->label;
		if ($this->fk_parent)
		{
			$parent = new EcmDirectory($this->db);
			$parent->fetch($this->fk_parent);
			$relativepath=$parent->getRelativePath().$relativepath;
		}
		$relativepath=preg_replace('/([\/])+/i', '/', $relativepath);	// Avoid duplicate / or \
		//print $relativepath.'<br>';

		$cat = new EcmDirectory($this->db);
		$cate_arbo = $cat->get_full_arbo(1);
		$pathfound=0;
		foreach ($cate_arbo as $key => $categ)
		{
			$path=str_replace($this->forbiddencharsdir, '_', $categ['fullrelativename']);
			//print $relativepath.' - '.$path.'<br>';
			if ($path == $relativepath)
			{
				$pathfound=1;
				break;
			}
		}

		if ($pathfound)
		{
			$this->error="ErrorDirAlreadyExists";
			dol_syslog(get_class($this)."::create ".$this->error, LOG_WARNING);
			return -1;
		}
		else
		{
			$this->db->begin();

			// Insert request
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."ecm_directories(";
			$sql.= "label,";
			$sql.= "entity,";
			$sql.= "fk_parent,";
			$sql.= "description,";
			$sql.= "cachenbofdoc,";
			$sql.= "date_c,";
			$sql.= "fk_user_c";
			$sql.= ") VALUES (";
			$sql.= " '".$this->db->escape($this->label)."',";
			$sql.= " '".$this->db->escape($conf->entity)."',";
			$sql.= " '".$this->db->escape($this->fk_parent)."',";
			$sql.= " '".$this->db->escape($this->description)."',";
			$sql.= " ".$this->cachenbofdoc.",";
			$sql.= " '".$this->db->idate($this->date_c)."',";
			$sql.= " '".$this->db->escape($this->fk_user_c)."'";
			$sql.= ")";

			dol_syslog(get_class($this)."::create", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."ecm_directories");

				$dir=$conf->ecm->dir_output.'/'.$this->getRelativePath();
				$result=dol_mkdir($dir);
				if ($result < 0) { $error++; $this->error="ErrorFailedToCreateDir"; }

                // Call trigger
                $result=$this->call_trigger('MYECMDIR_CREATE', $user);
                if ($result < 0) { $error++; }
                // End call triggers

				if (! $error)
				{
					$this->db->commit();
					return $this->id;
				}
				else
				{
					$this->db->rollback();
					return -1;
				}
			}
			else
			{
				$this->error="Error ".$this->db->lasterror();
				$this->db->rollback();
				return -1;
			}
		}
	}

	/**
	 *	Update database
	 *
	 *  @param	User	$user        	User that modify
	 *  @param 	int		$notrigger	    0=no, 1=yes (no update trigger)
	 *  @return int 			       	<0 if KO, >0 if OK
	 */
	public function update($user = null, $notrigger = 0)
	{
		global $conf, $langs;

		$error=0;

		// Clean parameters
		$this->label=trim($this->label);
		$this->fk_parent=trim($this->fk_parent);
		$this->description=trim($this->description);

		// Check parameters
		// Put here code to add control on parameters values

		$this->db->begin();

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."ecm_directories SET";
		$sql.= " label='".$this->db->escape($this->label)."',";
		$sql.= " fk_parent='".$this->db->escape($this->fk_parent)."',";
		$sql.= " description='".$this->db->escape($this->description)."'";
		$sql.= " WHERE rowid=".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$error++;
			$this->error="Error ".$this->db->lasterror();
		}

		if (! $error && ! $notrigger)
		{
            // Call trigger
            $result=$this->call_trigger('MYECMDIR_MODIFY', $user);
            if ($result < 0) { $error++; }
            // End call triggers
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Update cache of nb of documents into database
	 *
	 * 	@param	string	$value		'+' or '-' or new number
	 *  @return int		         	<0 if KO, >0 if OK
	 */
	public function changeNbOfFiles($value)
	{
		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."ecm_directories SET";
		if (preg_match('/[0-9]+/', $value)) $sql.= " cachenbofdoc = ".(int) $value;
		else $sql.= " cachenbofdoc = cachenbofdoc ".$value." 1";
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::changeNbOfFiles", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}
		else
		{
		    if (preg_match('/[0-9]+/', $value)) $this->cachenbofdoc = (int) $value;
		    elseif ($value == '+') $this->cachenbofdoc++;
		    elseif ($value == '-') $this->cachenbofdoc--;
		}

		return 1;
	}


	/**
	 * 	Load object in memory from database
	 *
	 *  @param	int		$id			Id of object
	 *  @return int 		        <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id)
	{
		$sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.label,";
		$sql.= " t.fk_parent,";
		$sql.= " t.description,";
		$sql.= " t.cachenbofdoc,";
		$sql.= " t.fk_user_c,";
		$sql.= " t.fk_user_m,";
		$sql.= " t.date_c as date_c,";
		$sql.= " t.date_m as date_m";
		$sql.= " FROM ".MAIN_DB_PREFIX."ecm_directories as t";
		$sql.= " WHERE t.rowid = ".$id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			if ($obj)
			{
				$this->id    = $obj->rowid;
				$this->ref   = $obj->rowid;

				$this->label = $obj->label;
				$this->fk_parent = $obj->fk_parent;
				$this->description = $obj->description;
				$this->cachenbofdoc = $obj->cachenbofdoc;
				$this->fk_user_m = $obj->fk_user_m;
				$this->fk_user_c = $obj->fk_user_c;
				$this->date_c = $this->db->jdate($obj->date_c);
				$this->date_m = $this->db->jdate($obj->date_m);
			}

			$this->db->free($resql);

			return $obj?1:0;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 * 	Delete object on database and/or on disk
	 *
	 *	@param	User	$user					User that delete
	 *  @param	string	$mode					'all'=delete all, 'databaseonly'=only database entry, 'fileonly' (not implemented)
	 *  @param	int		$deletedirrecursive		1=Agree to delete content recursiveley (otherwise an error will be returned when trying to delete)
	 *	@return	int								<0 if KO, >0 if OK
	 */
	public function delete($user, $mode = 'all', $deletedirrecursive = 0)
	{
		global $conf, $langs;
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error=0;

		if ($mode != 'databaseonly') $relativepath=$this->getRelativePath(1);	// Ex: dir1/dir2/dir3

		dol_syslog(get_class($this)."::delete remove directory id=".$this->id." mode=".$mode.(($mode == 'databaseonly')?'':' relativepath='.$relativepath));

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."ecm_directories";
		$sql.= " WHERE rowid=".$this->id;

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->db->rollback();
			$this->error="Error ".$this->db->lasterror();
			return -2;
		}
		else
		{
            // Call trigger
            $result=$this->call_trigger('MYECMDIR_DELETE', $user);
            if ($result < 0)
            {
            	$this->db->rollback();
            	return -2;
            }
            // End call triggers
		}

		if ($mode != 'databaseonly')
		{
			$file = $conf->ecm->dir_output . "/" . $relativepath;
			if ($deletedirrecursive)
			{
				$result=@dol_delete_dir_recursive($file, 0, 0);
			}
			else
			{
				$result=@dol_delete_dir($file, 0);
			}
		}

		if ($result || ! @is_dir(dol_osencode($file)))
		{
			$this->db->commit();
		}
		else
		{
			$this->error='ErrorFailToDeleteDir';
			dol_syslog(get_class($this)."::delete ".$this->error, LOG_ERR);
			$this->db->rollback();
			$error++;
		}

		if (! $error) return 1;
		else return -1;
	}


	/**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
	 */
	public function initAsSpecimen()
	{
		$this->id=0;

		$this->label='MyDirectory';
		$this->fk_parent='0';
		$this->description='This is a directory';
	}


	/**
	 *  Return directory name you can click (and picto)
	 *
	 *  @param	int		$withpicto		0=Pas de picto, 1=Include picto into link, 2=Only picto
	 *  @param	string	$option			Sur quoi pointe le lien
	 *  @param	int		$max			Max length
	 *  @param	string	$more			Add more param on a link
     *  @param	int		$notooltip		1=Disable tooltip
	 *  @return	string					Chaine avec URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $max = 0, $more = '', $notooltip = 0)
	{
		global $langs;

		$result='';
        //$newref=str_replace('_',' ',$this->ref);
        $newref=$this->ref;
        $label=$langs->trans("ShowECMSection").': '.$newref;
        $linkclose='"'.($more?' '.$more:'').' title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';

        $linkstart = '<a href="'.DOL_URL_ROOT.'/ecm/dir_card.php?section='.$this->id.$linkclose;
        if ($option == 'index') $linkstart = '<a href="'.DOL_URL_ROOT.'/ecm/index.php?section='.$this->id.'&amp;sectionexpand=true'.$linkclose;
        if ($option == 'indexexpanded') $linkstart = '<a href="'.DOL_URL_ROOT.'/ecm/index.php?section='.$this->id.'&amp;sectionexpand=false'.$linkclose;
        if ($option == 'indexnotexpanded') $linkstart = '<a href="'.DOL_URL_ROOT.'/ecm/index.php?section='.$this->id.'&amp;sectionexpand=true'.$linkclose;
        $linkend='</a>';

		//$picto=DOL_URL_ROOT.'/theme/common/treemenu/folder.gif';
		$picto='dir';

		$result .= $linkstart;
		if ($withpicto) $result.=img_object(($notooltip?'':$label), $this->picto, ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
		if ($withpicto != 2) $result.= ($max?dol_trunc($newref, $max, 'middle'):$newref);
		$result .= $linkend;

		return $result;
	}

	/**
	 *  Return relative path of a directory on disk
	 *
	 * 	@param	int		$force		Force reload of full arbo even if already loaded
	 *	@return	string				Relative physical path
	 */
	public function getRelativePath($force = 0)
	{
		$this->get_full_arbo($force);

		$ret='';
		$idtosearch=$this->id;
		$i=0;
		do {
			// Get index cursor in this->cats for id_mere
			$cursorindex=-1;
			foreach ($this->cats as $key => $val)
			{
				if ($this->cats[$key]['id'] == $idtosearch)
				{
					$cursorindex=$key;
					break;
				}
			}
			//print "c=".$idtosearch."-".$cursorindex;

			if ($cursorindex >= 0)
			{
				// Path is label sanitized (no space and no special char) and concatenated
				$ret=dol_sanitizeFileName($this->cats[$cursorindex]['label']).'/'.$ret;

				$idtosearch=$this->cats[$cursorindex]['id_mere'];
				$i++;
			}
		}
		while ($cursorindex >= 0 && ! empty($idtosearch) && $i < 100);	// i avoid infinite loop

		return $ret;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Load this->motherof that is array(id_son=>id_parent, ...)
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	public function load_motherof()
	{
        // phpcs:enable
		global $conf;

		$this->motherof=array();

		// Load array[child]=parent
		$sql = "SELECT fk_parent as id_parent, rowid as id_son";
		$sql.= " FROM ".MAIN_DB_PREFIX."ecm_directories";
		$sql.= " WHERE fk_parent != 0";
		$sql.= " AND entity = ".$conf->entity;

		dol_syslog(get_class($this)."::load_motherof", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			// This assignment in condition is not a bug. It allows walking the results.
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->motherof[$obj->id_son]=$obj->id_parent;
			}
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *  Retourne le libelle du status d'un user (actif, inactif)
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 5=Long label + Picto
	 *  @return string 			       	Label of status
	 */
	public static function LibStatut($status, $mode = 0)
	{
        // phpcs:enable
		global $langs;
		return '';
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Reconstruit l'arborescence des categories sous la forme d'un tableau à partir de la base de donnée
	 *	Renvoi un tableau de tableau('id','id_mere',...) trie selon arbre et avec:
	 *				id                  Id de la categorie
	 *				id_mere             Id de la categorie mere
	 *				id_children         Tableau des id enfant
	 *				label               Name of directory
	 *				cachenbofdoc        Nb of documents
	 *				date_c              Date creation
	 * 				fk_user_c           User creation
	 *  			login_c             Login creation
	 * 				fullpath	        Full path of id (Added by build_path_from_id_categ call)
     *              fullrelativename    Full path name (Added by build_path_from_id_categ call)
	 * 				fulllabel	        Full label (Added by build_path_from_id_categ call)
	 * 				level		        Level of line (Added by build_path_from_id_categ call)
	 *
	 *  @param	int		$force	        Force reload of full arbo even if already loaded in cache $this->cats
	 *	@return	array			        Tableau de array
	 */
	public function get_full_arbo($force = 0)
	{
        // phpcs:enable
		global $conf;

		if (empty($force) && ! empty($this->full_arbo_loaded))
		{
			return $this->cats;
		}

		// Init this->motherof that is array(id_son=>id_parent, ...)
		$this->load_motherof();

		// Charge tableau des categories
		$sql = "SELECT c.rowid as rowid, c.label as label,";
		$sql.= " c.description as description, c.cachenbofdoc,";
		$sql.= " c.fk_user_c,";
		$sql.= " c.date_c,";
		$sql.= " u.login as login_c,";
		$sql.= " ca.rowid as rowid_fille";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."ecm_directories as c";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."ecm_directories as ca";
		$sql.= " ON c.rowid = ca.fk_parent";
		$sql.= " WHERE c.fk_user_c = u.rowid";
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " ORDER BY c.label, c.rowid";

		dol_syslog(get_class($this)."::get_full_arbo", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->cats = array();
			$i=0;
			// This assignment in condition is not a bug. It allows walking the results.
			while ($obj = $this->db->fetch_object($resql))
			{
				$this->cats[$obj->rowid]['id'] = $obj->rowid;
				$this->cats[$obj->rowid]['id_mere'] = (isset($this->motherof[$obj->rowid])?$this->motherof[$obj->rowid]:'');
				$this->cats[$obj->rowid]['label'] = $obj->label;
				$this->cats[$obj->rowid]['description'] = $obj->description;
				$this->cats[$obj->rowid]['cachenbofdoc'] = $obj->cachenbofdoc;
				$this->cats[$obj->rowid]['date_c'] = $this->db->jdate($obj->date_c);
				$this->cats[$obj->rowid]['fk_user_c'] = $obj->fk_user_c;
				$this->cats[$obj->rowid]['login_c'] = $obj->login_c;

				if (! empty($obj->rowid_fille))
				{
					if (isset($this->cats[$obj->rowid]['id_children']) && is_array($this->cats[$obj->rowid]['id_children']))
					{
						$newelempos=count($this->cats[$obj->rowid]['id_children']);
						//print "this->cats[$i]['id_children'] est deja un tableau de $newelem elements<br>";
						$this->cats[$obj->rowid]['id_children'][$newelempos]=$obj->rowid_fille;
					}
					else
					{
						//print "this->cats[".$obj->rowid."]['id_children'] n'est pas encore un tableau<br>";
						$this->cats[$obj->rowid]['id_children']=array($obj->rowid_fille);
					}
				}
				$i++;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}

		// We add properties fullxxx to all elements
		foreach($this->cats as $key => $val)
		{
			if (isset($motherof[$key])) continue;
			$this->build_path_from_id_categ($key, 0);
		}

		$this->cats=dol_sort_array($this->cats, 'fulllabel', 'asc', true, false);
		$this->full_arbo_loaded=1;

		return $this->cats;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Define properties fullpath, fullrelativename, fulllabel of a directory of array this->cats and all its childs.
	 *  Separator between directories is always '/', whatever is OS.
	 *
	 * 	@param	int		$id_categ		id_categ entry to update
	 * 	@param	int		$protection		Deep counter to avoid infinite loop
	 * 	@return	void
	 */
	public function build_path_from_id_categ($id_categ, $protection = 0)
	{
        // phpcs:enable
		// Define fullpath
		if (! empty($this->cats[$id_categ]['id_mere']))
		{
			$this->cats[$id_categ]['fullpath'] =$this->cats[$this->cats[$id_categ]['id_mere']]['fullpath'];
			$this->cats[$id_categ]['fullpath'].='_'.$id_categ;
            $this->cats[$id_categ]['fullrelativename'] =$this->cats[$this->cats[$id_categ]['id_mere']]['fullrelativename'];
            $this->cats[$id_categ]['fullrelativename'].='/'.$this->cats[$id_categ]['label'];
			$this->cats[$id_categ]['fulllabel'] =$this->cats[$this->cats[$id_categ]['id_mere']]['fulllabel'];
			$this->cats[$id_categ]['fulllabel'].=' >> '.$this->cats[$id_categ]['label'];
		}
		else
		{
			$this->cats[$id_categ]['fullpath']='_'.$id_categ;
            $this->cats[$id_categ]['fullrelativename']=$this->cats[$id_categ]['label'];
			$this->cats[$id_categ]['fulllabel']=$this->cats[$id_categ]['label'];
		}
		// We count number of _ to have level (we use strlen that is faster than dol_strlen)
		$this->cats[$id_categ]['level']=strlen(preg_replace('/([^_])/i', '', $this->cats[$id_categ]['fullpath']));

		// Traite ces enfants
		$protection++;
		if ($protection > 20) return;	// On ne traite pas plus de 20 niveaux
		if (isset($this->cats[$id_categ]['id_children']) && is_array($this->cats[$id_categ]['id_children']))
		{
			foreach($this->cats[$id_categ]['id_children'] as $key => $val)
			{
				$this->build_path_from_id_categ($val, $protection);
			}
		}
	}

	/**
	 *	Refresh value for cachenboffile. This scan and count files into directory.
	 *
	 *  @param		int		$all       	0=refresh record using this->id , 1=refresh record using this->entity
	 * 	@return		int					-1 if KO, Nb of files in directory if OK
	 */
	public function refreshcachenboffile($all = 0)
	{
		global $conf;
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$dir=$conf->ecm->dir_output.'/'.$this->getRelativePath();
		$filelist=dol_dir_list($dir, 'files', 0, '', '(\.meta|_preview.*\.png)$');

		// Test if filelist is in database


		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."ecm_directories SET";
		$sql.= " cachenbofdoc = '".count($filelist)."'";
		if (empty($all))  // By default
		{
			$sql.= " WHERE rowid = ".$this->id;
		}
		else
		{
			$sql.= " WHERE entity = ".$conf->entity;
		}

		dol_syslog(get_class($this)."::refreshcachenboffile", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->cachenbofdoc=count($filelist);
			return $this->cachenbofdoc;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
     * Call trigger based on this instance
     *
     *  NB: Error from trigger are stacked in errors
     *  NB2: if trigger fail, action should be canceled.
     *  NB3: Should be deleted if EcmDirectory extend CommonObject
     *
     * @param   string    $triggerName   trigger's name to execute
     * @param   User      $user           Object user
     * @return  int                       Result of run_triggers
     */
    public function call_trigger($triggerName, $user)
    {
        // phpcs:enable
        global $langs,$conf;

        include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
        $interface=new Interfaces($this->db);
        $result=$interface->run_triggers($triggerName, $this, $user, $langs, $conf);
        if ($result < 0) {
            if (!empty($this->errors))
            {
                $this->errors=array_merge($this->errors, $interface->errors);
            }
            else
            {
                $this->errors=$interface->errors;
            }
        }
        return $result;
    }
}
