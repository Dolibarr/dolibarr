<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       dev/skeletons/opensurveysondage.class.php
 *  \ingroup    mymodule othermodule1 othermodule2
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *				Initialy built by build_class_from_table on 2013-03-10 00:32
 */

// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");


/**
 *	Put here description of your class
 */
class Opensurveysondage extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='opensurvey_sondage';			//!< Id that identify managed objects
	var $table_element='opensurvey_sondage';	//!< Name of table without prefix where object is stored

    var $id;

	var $id_sondage;
	var $commentaires;
	var $mail_admin;
	var $nom_admin;
	var $titre;
	var $id_sondage_admin;
	var $date_fin='';
	var $format;
	var $mailsonde;
	var $survey_link_visible;
	var $canedit;




    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }


    /**
     *  Create object into database
     *
     *  @param	User	$user        User that creates
     *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
     *  @return int      		   	 <0 if KO, Id of created object if OK
     */
    function create($user, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->id_sondage)) $this->id_sondage=trim($this->id_sondage);
		if (isset($this->commentaires)) $this->commentaires=trim($this->commentaires);
		if (isset($this->mail_admin)) $this->mail_admin=trim($this->mail_admin);
		if (isset($this->nom_admin)) $this->nom_admin=trim($this->nom_admin);
		if (isset($this->titre)) $this->titre=trim($this->titre);
		if (isset($this->id_sondage_admin)) $this->id_sondage_admin=trim($this->id_sondage_admin);
		if (isset($this->format)) $this->format=trim($this->format);
		if (isset($this->mailsonde)) $this->mailsonde=trim($this->mailsonde);
		if (isset($this->survey_link_visible)) $this->survey_link_visible=trim($this->survey_link_visible);
		if (isset($this->canedit)) $this->canedit=trim($this->canedit);



		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."opensurvey_sondage(";

		$sql.= "id_sondage,";
		$sql.= "commentaires,";
		$sql.= "mail_admin,";
		$sql.= "nom_admin,";
		$sql.= "titre,";
		$sql.= "id_sondage_admin,";
		$sql.= "date_fin,";
		$sql.= "format,";
		$sql.= "mailsonde,";
		$sql.= "survey_link_visible,";
		$sql.= "canedit";
        $sql.= ") VALUES (";

		$sql.= " ".(! isset($this->id_sondage)?'NULL':"'".$this->id_sondage."'").",";
		$sql.= " ".(! isset($this->commentaires)?'NULL':"'".$this->db->escape($this->commentaires)."'").",";
		$sql.= " ".(! isset($this->mail_admin)?'NULL':"'".$this->db->escape($this->mail_admin)."'").",";
		$sql.= " ".(! isset($this->nom_admin)?'NULL':"'".$this->db->escape($this->nom_admin)."'").",";
		$sql.= " ".(! isset($this->titre)?'NULL':"'".$this->db->escape($this->titre)."'").",";
		$sql.= " ".(! isset($this->id_sondage_admin)?'NULL':"'".$this->id_sondage_admin."'").",";
		$sql.= " ".(! isset($this->date_fin) || dol_strlen($this->date_fin)==0?'NULL':$this->db->idate($this->date_fin)).",";
		$sql.= " ".(! isset($this->format)?'NULL':"'".$this->db->escape($this->format)."'").",";
		$sql.= " ".(! isset($this->mailsonde)?'NULL':"'".$this->mailsonde."'").",";
		$sql.= " ".(! isset($this->survey_link_visible)?'NULL':"'".$this->survey_link_visible."'").",";
		$sql.= " ".(! isset($this->canedit)?'NULL':"'".$this->canedit."'")."";

		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."opensurvey_sondage");

			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
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
    function fetch($id,$numsurvey='')
    {
    	global $langs;

    	$sql = "SELECT";
		//$sql.= " t.rowid,";
		$sql.= " t.id_sondage,";
		$sql.= " t.commentaires,";
		$sql.= " t.mail_admin,";
		$sql.= " t.nom_admin,";
		$sql.= " t.titre,";
		$sql.= " t.id_sondage_admin,";
		$sql.= " t.date_fin,";
		$sql.= " t.format,";
		$sql.= " t.mailsonde,";
		$sql.= " t.survey_link_visible,";
		$sql.= " t.canedit,";
		$sql.= " t.sujet,";
		$sql.= " t.tms";
        $sql.= " FROM ".MAIN_DB_PREFIX."opensurvey_sondage as t";
        if ($id > 0) $sql.= " WHERE t.rowid = ".$id;
        else if (strlen($numsurvey) == 16) $sql.= " WHERE t.id_sondage = '".$numsurvey."'";
        else $sql.= " WHERE t.id_sondage_admin = '".$numsurvey."'";

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                //$this->id  = $obj->rowid;
				$this->ref = $obj->id_sondage_admin;

				$this->id_sondage = $obj->id_sondage;
				$this->commentaires = $obj->commentaires;
				$this->mail_admin = $obj->mail_admin;
				$this->nom_admin = $obj->nom_admin;
				$this->titre = $obj->titre;
				$this->id_sondage_admin = $obj->id_sondage_admin;
				$this->date_fin = $this->db->jdate($obj->date_fin);
				$this->format = $obj->format;
				$this->mailsonde = $obj->mailsonde;
				$this->survey_link_visible = $obj->survey_link_visible;
				$this->canedit = $obj->canedit;
				$this->sujet = $obj->sujet;

				$this->date_m = $this->db->jdate($obj->tls);
				$ret=1;
            }
            else $ret=0;

            $this->db->free($resql);
        }
        else
       {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
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
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->id_sondage)) $this->id_sondage=trim($this->id_sondage);
		if (isset($this->commentaires)) $this->commentaires=trim($this->commentaires);
		if (isset($this->mail_admin)) $this->mail_admin=trim($this->mail_admin);
		if (isset($this->nom_admin)) $this->nom_admin=trim($this->nom_admin);
		if (isset($this->titre)) $this->titre=trim($this->titre);
		if (isset($this->id_sondage_admin)) $this->id_sondage_admin=trim($this->id_sondage_admin);
		if (isset($this->format)) $this->format=trim($this->format);
		if (isset($this->mailsonde)) $this->mailsonde=trim($this->mailsonde);
		if (isset($this->survey_link_visible)) $this->survey_link_visible=trim($this->survey_link_visible);
		if (isset($this->canedit)) $this->canedit=trim($this->canedit);


		// Check parameters
		// Put here code to add a control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."opensurvey_sondage SET";

		$sql.= " id_sondage='".(isset($this->id_sondage)?$this->id_sondage:"null")."',";
		$sql.= " commentaires=".(isset($this->commentaires)?"'".$this->db->escape($this->commentaires)."'":"null").",";
		$sql.= " mail_admin=".(isset($this->mail_admin)?"'".$this->db->escape($this->mail_admin)."'":"null").",";
		$sql.= " nom_admin=".(isset($this->nom_admin)?"'".$this->db->escape($this->nom_admin)."'":"null").",";
		$sql.= " titre=".(isset($this->titre)?"'".$this->db->escape($this->titre)."'":"null").",";
		$sql.= " id_sondage_admin='".(isset($this->id_sondage_admin)?$this->id_sondage_admin:"null")."',";
		$sql.= " date_fin=".(dol_strlen($this->date_fin)!=0 ? "'".$this->db->idate($this->date_fin)."'" : 'null').",";
		$sql.= " format=".(isset($this->format)?"'".$this->db->escape($this->format)."'":"null").",";
		$sql.= " mailsonde=".(isset($this->mailsonde)?$this->mailsonde:"null").",";
		$sql.= " survey_link_visible=".(isset($this->survey_link_visible)?$this->survey_link_visible:"null").",";
		$sql.= " canedit=".(isset($this->canedit)?$this->canedit:"null")."";

        //$sql.= " WHERE rowid=".$this->id;
		$sql.= " WHERE id_sondage_admin='".$this->id_sondage_admin."'";

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
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
     *  @param	string	$numsondageadmin	Num sondage admin to delete
	 *  @return	int					 		<0 if KO, >0 if OK
	 */
	function delete($user, $notrigger, $numsondageadmin)
	{
		global $conf, $langs;
		$error=0;

		$numsondage=substr($numsondageadmin, 0, 16);
		
		$this->db->begin();

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
		        // want this action calls a trigger.

		        //// Call triggers
		        //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
		        //$interface=new Interfaces($this->db);
		        //$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
		        //if ($result < 0) { $error++; $this->errors=$interface->errors; }
		        //// End call triggers
			}
		}

		if (! $error)
		{

			$sql='DELETE FROM '.MAIN_DB_PREFIX."opensurvey_comments WHERE id_sondage = '".$numsondage."'";
			dol_syslog(get_class($this)."::delete sql=".$sql, LOG_DEBUG);
			$resql=$this->db->query($sql);
			$sql='DELETE FROM '.MAIN_DB_PREFIX."opensurvey_user_studs WHERE id_sondage = '".$numsondage."'";
			dol_syslog(get_class($this)."::delete sql=".$sql, LOG_DEBUG);
			$resql=$this->db->query($sql);

    		$sql = "DELETE FROM ".MAIN_DB_PREFIX."opensurvey_sondage";
    		$sql.= " WHERE id_sondage_admin = '".$numsondageadmin."'";

    		dol_syslog(get_class($this)."::delete sql=".$sql);
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
	 * Return array of lines
	 *
	 * @return 	array	Array of lines
	 */
	function fetch_lines()
	{
		$ret=array();
		$sql = "SELECT id_users, nom, reponses FROM ".MAIN_DB_PREFIX."opensurvey_user_studs";
		$sql.= " WHERE id_sondage = '".$this->id_sondage."'";
		$resql=$this->db->query($sql);

		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj=$this->db->fetch_object($resql);
				$tmp=array('id_users'=>$obj->id_users, 'nom'=>$obj->nom, 'reponses'=>$obj->reponses);

				$ret[]=$tmp;
				$i++;
			}
		}
		else dol_print_error($this->db);

		$this->lines=$ret;

		return $this->lines;
	}

	/**
	 *	Load an object from its id and create a new one in database
	 *
	 *	@param	int		$fromid     Id of object to clone
	 * 	@return	int					New id of clone
	 */
	function createFromClone($fromid)
	{
		global $user,$langs;

		$error=0;

		$object=new Opensurveysondage($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;

		// Clear fields
		// ...

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
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;

		$this->id_sondage='';
		$this->commentaires='';
		$this->mail_admin='';
		$this->nom_admin='';
		$this->titre='';
		$this->id_sondage_admin='';
		$this->date_fin='';
		$this->format='';
		$this->mailsonde='';
		$this->survey_link_visible='';
		$this->canedit=0;
	}

}
?>
