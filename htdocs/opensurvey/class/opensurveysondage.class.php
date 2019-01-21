<?php
/* Copyright (C) 2013-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014      Marcos García	    <marcosgdf@gmail.com>
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
 *  \file       htdocs/opensurvey/class/opensurveysondage.class.php
 *  \ingroup    opensurvey
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *				Initialy built by build_class_from_table on 2013-03-10 00:32
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
//require_once DOL_DOCUMENT_ROOT."/societe/class/societe.class.php";
//require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";


/**
 *	Put here description of your class
 */
class Opensurveysondage extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element='opensurvey_sondage';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element='opensurvey_sondage';

    public $picto = 'opensurvey';

	public $id_sondage;
	/**
	 * @deprecated
	 * @see description
	 */
	public $commentaires;

	/**
	 * @var string description
	 */
	public $description;

	public $mail_admin;
	public $nom_admin;

	/**
	 * Id of user author of the poll
	 * @var int
	 */
	public $fk_user_creat;

	public $titre;
	public $date_fin='';
	public $status=1;
	public $format;
	public $mailsonde;

	public $sujet;

	/**
	 * Allow comments on this poll
	 * @var bool
	 */
	public $allow_comments;

	/**
	 * Allow users see others vote
	 * @var bool
	 */
	public $allow_spy;


	/**
	 * Draft status (not used)
	 */
	const STATUS_DRAFT = 0;
	/**
	 * Validated/Opened status
	 */
	const STATUS_VALIDATED = 1;
	/**
	 * Closed
	 */
	const STATUS_CLOSED = 2;



    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    }


    /**
     *  Create object into database
     *
     *  @param	User	$user        User that creates
     *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
     *  @return int      		   	 <0 if KO, Id of created object if OK
     */
    function create(User $user, $notrigger=0)
    {
		$error=0;

		// Clean parameters
		$this->cleanParameters();

		// Check parameters
		if (! $this->date_fin > 0)
		{
			$this->error='BadValueForEndDate';
			dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
			return -1;
		}

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."opensurvey_sondage(";
		$sql.= "id_sondage,";
		$sql.= "commentaires,";
		$sql.= "fk_user_creat,";
		$sql.= "titre,";
		$sql.= "date_fin,";
		$sql.= "status,";
		$sql.= "format,";
		$sql.= "mailsonde,";
		$sql.= "allow_comments,";
		$sql.= "allow_spy,";
		$sql.= "sujet";
        $sql.= ") VALUES (";
		$sql.= "'".$this->db->escape($this->id_sondage)."',";
		$sql.= " ".(empty($this->commentaires)?'NULL':"'".$this->db->escape($this->commentaires)."'").",";
		$sql.= " ".$user->id.",";
		$sql.= " '".$this->db->escape($this->titre)."',";
		$sql.= " '".$this->db->idate($this->date_fin)."',";
		$sql.= " ".$this->status.",";
		$sql.= " '".$this->db->escape($this->format)."',";
		$sql.= " ".$this->db->escape($this->mailsonde).",";
		$sql.= " ".$this->db->escape($this->allow_comments).",";
		$sql.= " ".$this->db->escape($this->allow_spy).",";
		$sql.= " '".$this->db->escape($this->sujet)."'";
		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(get_class($this)."::create", LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
			if (! $notrigger)
			{
				global $langs, $conf;

                // Call trigger
                $result=$this->call_trigger('OPENSURVEY_CREATE',$user);
                if ($result < 0) $error++;
                // End call triggers
			}
        }

        // Commit or rollback
        if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}
    }


    /**
     *  Load object in memory from the database
     *
     *  @param	int		$id    				Id object
     *  @param	string	$numsurvey			Ref of survey (admin or not)
     *  @return int          				<0 if KO, >0 if OK
     */
    function fetch($id, $numsurvey='')
    {
    	$sql = "SELECT";
		$sql.= " t.id_sondage,";
		$sql.= " t.commentaires as description,";
		$sql.= " t.mail_admin,";
		$sql.= " t.nom_admin,";
		$sql.= " t.fk_user_creat,";
		$sql.= " t.titre,";
		$sql.= " t.date_fin,";
		$sql.= " t.status,";
		$sql.= " t.format,";
		$sql.= " t.mailsonde,";
		$sql.= " t.allow_comments,";
		$sql.= " t.allow_spy,";
		$sql.= " t.sujet,";
		$sql.= " t.tms";
        $sql.= " FROM ".MAIN_DB_PREFIX."opensurvey_sondage as t";
        $sql.= " WHERE t.id_sondage = '".$this->db->escape($id ? $id : $numsurvey)."'";

    	dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

				$this->id_sondage = $obj->id_sondage;
				$this->ref = $this->id_sondage;             //For compatibility

				$this->commentaires = $obj->description;	// deprecated
				$this->description = $obj->description;
				$this->mail_admin = $obj->mail_admin;
				$this->nom_admin = $obj->nom_admin;
				$this->titre = $obj->titre;
				$this->date_fin = $this->db->jdate($obj->date_fin);
				$this->status = $obj->status;
				$this->format = $obj->format;
				$this->mailsonde = $obj->mailsonde;
				$this->allow_comments = $obj->allow_comments;
				$this->allow_spy = $obj->allow_spy;
				$this->sujet = $obj->sujet;
				$this->fk_user_creat = $obj->fk_user_creat;

				$this->date_m = $this->db->jdate($obj->tls);
				$ret=1;
            }
            else
            {
            	$sondage = ($id ? 'id='.$id : 'sondageid='.$numsurvey);
                $this->error='Fetch no poll found for '.$sondage;
                dol_syslog($this->error, LOG_ERR);
                $ret = 0;
            }

            $this->db->free($resql);
        }
        else
       {
      	    $this->error="Error ".$this->db->lasterror();
            $ret=-1;
        }

        return $ret;
    }


    /**
     *  Update object into database
     *
     *  @param	User	$user        User that modifies
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    function update(User $user, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
		$this->cleanParameters();

		// Check parameters
		// Put here code to add a control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."opensurvey_sondage SET";

		$sql.= " id_sondage=".(isset($this->id_sondage)?"'".$this->db->escape($this->id_sondage)."'":"null").",";
		$sql.= " commentaires=".(isset($this->commentaires)?"'".$this->db->escape($this->commentaires)."'":"null").",";
		$sql.= " mail_admin=".(isset($this->mail_admin)?"'".$this->db->escape($this->mail_admin)."'":"null").",";
		$sql.= " nom_admin=".(isset($this->nom_admin)?"'".$this->db->escape($this->nom_admin)."'":"null").",";
		$sql.= " titre=".(isset($this->titre)?"'".$this->db->escape($this->titre)."'":"null").",";
		$sql.= " date_fin=".(dol_strlen($this->date_fin)!=0 ? "'".$this->db->idate($this->date_fin)."'" : 'null').",";
		$sql.= " status=".(isset($this->status)?"'".$this->db->escape($this->status)."'":"null").",";
		$sql.= " format=".(isset($this->format)?"'".$this->db->escape($this->format)."'":"null").",";
		$sql.= " mailsonde=".(isset($this->mailsonde)?$this->db->escape($this->mailsonde):"null").",";
		$sql.= " allow_comments=".$this->db->escape($this->allow_comments).",";
		$sql.= " allow_spy=".$this->db->escape($this->allow_spy);

		$sql.= " WHERE id_sondage='".$this->db->escape($this->id_sondage)."'";

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Call triggers
	            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
	            $interface=new Interfaces($this->db);
	            $result=$interface->run_triggers('OPENSURVEY_MODIFY',$this,$user,$langs,$conf);
	            if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            // End call triggers
	    	}
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
    }

    /**
     *  Delete object in database
     *
     *	@param  User	$user        		User that deletes
     *  @param  int		$notrigger	 		0=launch triggers after, 1=disable triggers
     *  @param	string	$numsondage			Num sondage admin to delete
     *  @return	int					 		<0 if KO, >0 if OK
     */
    function delete(User $user, $notrigger=0, $numsondage='')
    {
		global $conf, $langs;
		$error=0;

		if (empty($numsondage))
		{
		    $numsondage = $this->id_sondage;
		}

		$this->db->begin();

		if (! $error)
		{
			if (! $notrigger)
			{
                // Call trigger
                $result=$this->call_trigger('OPENSURVEY_DELETE',$user);
                if ($result < 0) $error++;
                // End call triggers
			}
		}

		if (! $error)
		{

			$sql='DELETE FROM '.MAIN_DB_PREFIX."opensurvey_comments WHERE id_sondage = '".$this->db->escape($numsondage)."'";
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql=$this->db->query($sql);
			$sql='DELETE FROM '.MAIN_DB_PREFIX."opensurvey_user_studs WHERE id_sondage = '".$this->db->escape($numsondage)."'";
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql=$this->db->query($sql);

    		$sql = "DELETE FROM ".MAIN_DB_PREFIX."opensurvey_sondage";
    		$sql.= " WHERE id_sondage = '".$this->db->escape($numsondage)."'";

    		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
    		$resql = $this->db->query($sql);
        	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *	@param	int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param	int  	$notooltip					1=Disable tooltip
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	function getNomUrl($withpicto=0, $notooltip=0, $morecss='', $save_lastsearch_value=-1)
	{
		global $db, $conf, $langs;
		global $dolibarr_main_authentication, $dolibarr_main_demo;
		global $menumanager;

		if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

		$result = '';
		$companylink = '';

		$label = '<u>' . $langs->trans("ShowSurvey") . '</u>';
		$label.= '<br>';
		$label.= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref.'<br>';
		$label.= '<b>' . $langs->trans('Title') . ':</b> ' . $this->title.'<br>';

		$url = DOL_URL_ROOT.'/opensurvey/card.php?id='.$this->id;

		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
		if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';

		$linkclose='';
		if (empty($notooltip))
		{
			if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$label=$langs->trans("ShowMyObject");
				$linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose.=' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose.=' class="classfortooltip'.($morecss?' '.$morecss:'').'"';
		}
		else $linkclose = ($morecss?' class="'.$morecss.'"':'');

		$linkstart = '<a href="'.$url.'"';
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

		$result .= $linkstart;
		if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
		if ($withpicto != 2) $result.= $this->ref;
		$result .= $linkend;

		return $result;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 * Return array of lines
	 *
	 * @return 	int		<0 if KO, >0 if OK
	 */
	function fetch_lines()
	{
        // phpcs:enable
		$ret=array();

		$sql = "SELECT id_users, nom as name, reponses FROM ".MAIN_DB_PREFIX."opensurvey_user_studs";
		$sql.= " WHERE id_sondage = '".$this->db->escape($this->id_sondage)."'";
		$resql=$this->db->query($sql);

		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj=$this->db->fetch_object($resql);
				$tmp=array('id_users'=>$obj->id_users, 'nom'=>$obj->name, 'reponses'=>$obj->reponses);

				$ret[]=$tmp;
				$i++;
			}
		}
		else dol_print_error($this->db);

		$this->lines=$ret;

		return count($this->lines);
	}

	/**
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;

		$this->id_sondage='';
		$this->commentaires='Comment of the specimen survey';
		$this->mail_admin='';
		$this->nom_admin='';
		$this->titre='This is a specimen survey';
		$this->date_fin=dol_now()+3600*24*10;
		$this->status=1;
		$this->format='classic';
		$this->mailsonde='';
	}

	/**
	 * Returns all comments for the current opensurvey poll
	 *
	 * @return Object[]
	 */
	public function getComments()
	{
		$comments = array();

		$sql = 'SELECT id_comment, usercomment, comment';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'opensurvey_comments';
		$sql.= " WHERE id_sondage='".$this->db->escape($this->id_sondage)."'";
		$sql.= " ORDER BY id_comment";
		$resql = $this->db->query($sql);

		if ($resql)
		{
			$num_rows=$this->db->num_rows($resql);

			if ($num_rows > 0)
			{
				while ($obj = $this->db->fetch_object($resql))
				{
					$comments[] = $obj;
				}
			}
		}

		return $comments;
	}

	/**
	 * Adds a comment to the poll
	 *
	 * @param string $comment Comment content
	 * @param string $comment_user Comment author
	 * @return boolean False in case of the query fails, true if it was successful
	 */
	public function addComment($comment, $comment_user)
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."opensurvey_comments (id_sondage, comment, usercomment)";
		$sql.= " VALUES ('".$this->db->escape($this->id_sondage)."','".$this->db->escape($comment)."','".$this->db->escape($comment_user)."')";
		$resql = $this->db->query($sql);

		if (!$resql) {
			return false;
		}

		return true;
	}

	/**
	 * Deletes a comment of the poll
	 *
	 * @param int $id_comment Id of the comment
	 * @return boolean False in case of the query fails, true if it was successful
	 */
	public function deleteComment($id_comment)
	{
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'opensurvey_comments WHERE id_comment = '.$id_comment.' AND id_sondage = "'.$this->db->escape($this->id_sondage).'"';
		$resql = $this->db->query($sql);

		if (!$resql) {
			return false;
		}

		return true;
	}

	/**
	 * Cleans all the class variables before doing an update or an insert
	 *
	 * @return void
	 */
	private function cleanParameters()
	{
		$this->id_sondage = trim($this->id_sondage);
		$this->commentaires = trim($this->commentaires);
		$this->mail_admin = trim($this->mail_admin);
		$this->nom_admin = trim($this->nom_admin);
		$this->titre = trim($this->titre);
		$this->status = trim($this->status);
		$this->format = trim($this->format);
		$this->mailsonde = ($this->mailsonde ? 1 : 0);
		$this->allow_comments = ($this->allow_comments ? 1 : 0);
		$this->allow_spy = ($this->allow_spy ? 1 : 0);
		$this->sujet = trim($this->sujet);
	}


	/**
	 *	Return status label of Order
	 *
	 *	@param      int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *	@return     string      		Libelle
	 */
	function getLibStatut($mode)
	{
	    return $this->LibStatut($this->status,$mode);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Return label of status
	 *
	 *	@param		int		$status      	  Id statut
	 *	@param      int		$mode        	  0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return     string					  Label of status
	 */
	function LibStatut($status,$mode)
	{
        // phpcs:enable
	    global $langs, $conf;

	    //print 'x'.$status.'-'.$billed;
	    if ($mode == 0)
	    {
	        if ($status==self::STATUS_DRAFT) return $langs->trans('Draft');
	        if ($status==self::STATUS_VALIDATED) return $langs->trans('Opened');
	        if ($status==self::STATUS_CLOSED) return $langs->trans('Closed');
	    }
	    elseif ($mode == 1)
	    {
	        if ($status==self::STATUS_DRAFT) return $langs->trans('Draft');
	        if ($status==self::STATUS_VALIDATED) return $langs->trans('Opened');
	        if ($status==self::STATUS_CLOSED) return $langs->trans('Closed');
	    }
	    elseif ($mode == 2)
	    {
	        if ($status==self::STATUS_DRAFT) return img_picto($langs->trans('Draft'),'statut0').' '.$langs->trans('Draft');
	        if ($status==self::STATUS_VALIDATED) return img_picto($langs->trans('Opened'),'statut1').' '.$langs->trans('Opened');
	        if ($status==self::STATUS_CLOSED) return img_picto($langs->trans('Closed'),'statut6').' '.$langs->trans('Closed');
	    }
	    elseif ($mode == 3)
	    {
	        if ($status==self::STATUS_DRAFT) return img_picto($langs->trans('Draft'),'statut0');
	        if ($status==self::STATUS_VALIDATED) return img_picto($langs->trans('Opened'),'statut1');
	        if ($status==self::STATUS_CLOSED) return img_picto($langs->trans('Closed'),'statut6');
	    }
	    elseif ($mode == 4)
	    {
	        if ($status==self::STATUS_DRAFT) return img_picto($langs->trans('Draft'),'statut0').' '.$langs->trans('Draft');
	        if ($status==self::STATUS_VALIDATED) return img_picto($langs->trans('Opened').$billedtext,'statut1').' '.$langs->trans('Opened');
	        if ($status==self::STATUS_CLOSED) return img_picto($langs->trans('Closed'),'statut6').' '.$langs->trans('Closed');
	    }
	    elseif ($mode == 5)
	    {
	        if ($status==self::STATUS_DRAFT) return '<span class="hideonsmartphone">'.$langs->trans('Draft').' </span>'.img_picto($langs->trans('Draft'),'statut0');
	        if ($status==self::STATUS_VALIDATED) return '<span class="hideonsmartphone">'.$langs->trans('Opened').' </span>'.img_picto($langs->trans('Opened'),'statut1');
	        if ($status==self::STATUS_CLOSED) return '<span class="hideonsmartphone">'.$langs->trans('Closed').' </span>'.img_picto($langs->trans('Closed'),'statut6');
	    }
	}
}
