<?php
/* Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       dev/skeletons/ecm_directories.class.php
        \ingroup    mymodule othermodule1 othermodule2
        \brief      This file is an example for a class file
		\version    $Id$
		\author		Put author name here
		\remarks	Initialy built by build_class_from_table on 2008-02-24 19:24
*/

// Put here all includes required by your class file
//require_once(DOL_DOCUMENT_ROOT."/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product.class.php");


/**
        \class      EcmDirectory
        \brief      Class to manage ECM directories
		\remarks	Initialy built by build_class_from_table on 2008-02-24 19:24
*/
class EcmDirectory // extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	//var $element='ecm_directories';			//!< Id that identify managed objects
	//var $table_element='ecm_directories';	//!< Name of table without prefix where object is stored
    
    var $id;
    
	var $label;
	var $fk_parent;
	var $description;
	var $tms;

    var $cats=array();

	
    /**
     *      \brief      Constructor
     *      \param      DB      Database handler
     */
    function EcmDirectory($DB) 
    {
        $this->db = $DB;
        return 1;
    }

	
    /**
     *      \brief      Create in database
     *      \param      user        User that create
     *      \return     int         <0 si ko, >0 si ok
     */
    function create($user)
    {
    	global $conf, $langs;
    	
		$now=time();
		
		// Clean parameters
        $this->label=sanitize_string($this->label);
		$this->fk_parent=trim($this->fk_parent);
		$this->description=trim($this->description);
		if (! $this->cachenbofdoc) $this->cachenbofdoc=0;
		$this->date_c=$now;
		$this->fk_user_c=$user->id;
        if ($this->fk_parent <= 0) $this->fk_parent=0;

		// Check parameters
		// Put here code to add control on parameters values
		
        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."ecm_directories(";
		$sql.= "label,";
		$sql.= "fk_parent,";
		$sql.= "description,";
		$sql.= "cachenbofdoc,";
		$sql.= "date_c,";
		$sql.= "fk_user_c";
        $sql.= ") VALUES (";
		$sql.= " '".$this->label."',";
		$sql.= " '".$this->fk_parent."',";
		$sql.= " '".$this->description."',";
		$sql.= " ".($this->cachenbofdoc).",";
		$sql.= " ".$this->db->idate($this->date_c).",";
		$sql.= " '".$this->fk_user_c."'";
		$sql.= ")";

	   	dolibarr_syslog("Ecm_directories::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."ecm_directories");
    
			$result=create_exdir($conf->ecm->dir_output.'/'.$this->label);
			
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            return $this->id;
        }
        else
        {
            $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Ecm_directories::create ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /*
     *      \brief      Update database
     *      \param      user        	User that modify
     *      \param      notrigger	    0=no, 1=yes (no update trigger)
     *      \return     int         	<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
    	
		// Clean parameters
        
		$this->label=trim($this->label);
		$this->fk_parent=trim($this->fk_parent);
		$this->description=trim($this->description);

        

		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."ecm_directories SET";
        
		$sql.= " label='".addslashes($this->label)."',";
		$sql.= " fk_parent='".$this->fk_parent."',";
		$sql.= " description='".addslashes($this->description)."',";
		$sql.= " tms=".$this->db->idate($this->tms)."";

        
        $sql.= " WHERE rowid=".$this->id;

        dolibarr_syslog("Ecm_directories::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Ecm_directories::update ".$this->error, LOG_ERR);
            return -1;
        }

		if (! $notrigger)
		{
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers
    	}

        return 1;
    }
  
  
    /*
     *      \brief      Update database
     * 		\sign		'+' or '-'
     *      \return     int         	<0 if KO, >0 if OK
     */
    function changeNbOfFiles($sign)
    {
    	global $conf, $langs;
    	
        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."ecm_directories SET";
        
		$sql.= " cachenbofdoc = cachenbofdoc ".$sign." 1";
        $sql.= " WHERE rowid = ".$this->id;

        dolibarr_syslog("Ecm_directories::changeNbOfFiles sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Ecm_directories::changeNbOfFiles ".$this->error, LOG_ERR);
            return -1;
        }

        return 1;
    }

    
	/*
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \param      user        User that load
     *    \return     int         <0 if KO, >0 if OK
     */
    function fetch($id, $user=0)
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.label,";
		$sql.= " t.fk_parent,";
		$sql.= " t.description,";
		$sql.= " t.cachenbofdoc,";
		$sql.= " t.fk_user_c,";
		$sql.= " t.fk_user_m,";
		$sql.= " ".$this->db->pdate('t.date_c').",";
		$sql.= " ".$this->db->pdate('t.date_m')."";
        $sql.= " FROM ".MAIN_DB_PREFIX."ecm_directories as t";
        $sql.= " WHERE t.rowid = ".$id;
    
    	dolibarr_syslog("Ecm_directories::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
    
                $this->id    = $obj->rowid;
                $this->ref   = $obj->rowid;
                
				$this->label = $obj->label;
				$this->fk_parent = $obj->fk_parent;
				$this->description = $obj->description;
				$this->cachenbofdoc = $obj->cachenbofdoc;
				$this->fk_user_m = $obj->fk_user_m;
				$this->fk_user_c = $obj->fk_user_c;
				$this->date_c = $obj->date_c;
				$this->date_m = $obj->date_m;
            }
            $this->db->free($resql);
            
            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Ecm_directories::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }
    
    
 	/*
	*   \brief      Delete object in database
    *	\param      user        User that delete
	*	\return		int			<0 if KO, >0 if OK
	*/
	function delete($user)
	{
		global $conf, $langs;
	
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."ecm_directories";
		$sql.= " WHERE rowid=".$this->id;
	
	   	dolibarr_syslog("Ecm_directories::delete sql=".$sql);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Ecm_directories::delete ".$this->error, LOG_ERR);
			return -1;
		}
	
		$file = $conf->ecm->dir_output . "/" . $this->label;
		$result=@dol_delete_dir($file);
		
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
        $interface=new Interfaces($this->db);
        $result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }
        // Fin appel triggers

		return 1;
	}

  
	/**
	 *		\brief		Initialise object with example values
	 *		\remarks	id must be 0 if object instance is a specimen.
	 */
	function initAsSpecimen()
	{
		$this->id=0;
		
		$this->label='MyDirectory';
		$this->fk_parent='0';
		$this->description='This is a directory';
	}

	
  /**
     \brief      	Renvoie nom clicable (avec eventuellement le picto)
     \param			withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
     \param			option			Sur quoi pointe le lien
     \return		string			Chaine avec URL
   */
  	function getNomUrl($withpicto=0,$option='')
  	{
	    global $langs;
			
	    $result='';
			
	    $lien = '<a href="'.DOL_URL_ROOT.'/ecm/docmine.php?section='.$this->id.'">';
	    $lienfin='</a>';
			
	    //$picto=DOL_URL_ROOT.'/theme/common/treemenu/folder.gif';
		$picto='dir';
	
	    $label=$langs->trans("ShowECMSection").': '.$this->ref;
			
	    if ($withpicto) $result.=($lien.img_object($label,$picto,'',1).$lienfin);
	    if ($withpicto && $withpicto != 2) $result.=' ';
	    if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
	    return $result;
  	}

  /**
	* 	\brief		Load this->motherof array
	*	\return		int		<0 if KO, >0 if OK
	*/
	function load_motherof()
	{
		$this->motherof=array();
		
		// Charge tableau des meres
		$sql = "SELECT fk_parent as id_parent, rowid as id_son";
		$sql.= " FROM ".MAIN_DB_PREFIX."ecm_directories";
		$sql.= " WHERE fk_parent != 0";
		
		dolibarr_syslog("ECMDirectory::get_full_arbo sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj= $this->db->fetch_object($resql))
			{
				$this->motherof[$obj->id_son]=$obj->id_parent;
			}
			return 1;
		}
		else
		{
			dolibarr_print_error ($this->db);
			return -1;
		}
	}
		

	/**
	* 	\brief		Reconstruit l'arborescence des catégories sous la forme d'un tableau
	*				Renvoi un tableau de tableau('id','id_mere',...) trié selon
	*				arbre et avec:
	*				id = id de la categorie
	*				id_mere = id de la categorie mere
	*				id_children = tableau des id enfant
	*				label = nom de la categorie
	*				fulllabel = nom avec chemin complet de la categorie
	*				fullpath = chemin complet compose des id
	*	\return		array		Tableau de array
	*/
	function get_full_arbo()
	{
		// Init this->motherof array
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
		$sql.= " ON c.rowid=ca.fk_parent";
		$sql.= " WHERE c.fk_user_c = u.rowid";
		$sql.= " ORDER BY c.label, c.rowid";

		dolibarr_syslog("ECMDirectory::get_full_arbo sql=".$sql);
		$resql = $this->db->query ($sql);
		if ($resql)
		{
			$this->cats = array();
			$i=0;
			while ($obj = $this->db->fetch_object($resql))
			{
				$this->cats[$obj->rowid]['id'] = $obj->rowid;
				$this->cats[$obj->rowid]['id_mere'] = $this->motherof[$obj->rowid];
				$this->cats[$obj->rowid]['label'] = $obj->label;
				$this->cats[$obj->rowid]['description'] = $obj->description;
				$this->cats[$obj->rowid]['cachenbofdoc'] = $obj->cachenbofdoc;
				$this->cats[$obj->rowid]['date_c'] = $obj->date_c;
				$this->cats[$obj->rowid]['fk_user_c'] = $obj->fk_user_c;
				$this->cats[$obj->rowid]['login_c'] = $obj->login_c;
				
				if ($obj->rowid_fille)
				{
					if (is_array($this->cats[$obj->rowid]['id_children']))
					{
						$newelempos=sizeof($this->cats[$obj->rowid]['id_children']);
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
			dolibarr_print_error ($this->db);
			return -1;
		}
		
		// On ajoute la propriete fullpath a tous les éléments
		foreach($this->cats as $key => $val)
		{
			if (isset($motherof[$key])) continue;	
			$this->build_path_from_id_categ($key,0);
		}
		
		$this->cats=dol_sort_array($this->cats, 'fulllabel', 'asc', true, false);

		//$this->debug_cats();
		
		return $this->cats;
	}
	
	/**
	*	\brief		Calcule les propriétés fullpath et fulllabel d'une categorie
	*				du tableau this->cats et de toutes ces enfants
	* 	\param		id_categ		id_categ entry to update
	* 	\param		protection		Deep counter to avoid infinite loop
	*/
	function build_path_from_id_categ($id_categ,$protection=0)
	{
		// Define fullpath
		if (isset($this->cats[$id_categ]['id_mere']))
		{
			$this->cats[$id_categ]['fullpath'] =$this->cats[$this->cats[$id_categ]['id_mere']]['fullpath'];
			$this->cats[$id_categ]['fullpath'].='_'.$id_categ;
			$this->cats[$id_categ]['fulllabel'] =$this->cats[$this->cats[$id_categ]['id_mere']]['fulllabel'];
			$this->cats[$id_categ]['fulllabel'].=' >> '.$this->cats[$id_categ]['label'];
		}
		else
		{
			$this->cats[$id_categ]['fullpath']='_'.$id_categ;			
			$this->cats[$id_categ]['fulllabel']=$this->cats[$id_categ]['label'];
		}
		// We count number of _ to have level
		$this->cats[$id_categ]['level']=strlen(eregi_replace('[^_]','',$this->cats[$id_categ]['fullpath']));
				
		// Traite ces enfants
		$protection++;
		if ($protection > 20) return;	// On ne traite pas plus de 20 niveaux
		if (is_array($this->cats[$id_categ]['id_children']))
		{
			foreach($this->cats[$id_categ]['id_children'] as $key => $val)
			{
				$this->build_path_from_id_categ($val,$protection);
			}
		}
		
		return;
	}	
}
?>
