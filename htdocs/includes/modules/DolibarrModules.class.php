<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 */

/**
   \file       htdocs/includes/modules/DolibarrModules.class.php
   \brief      Fichier de description et activation des modules Dolibarr
*/


/**
   \class      DolibarrModules
   \brief      Classe mere des classes de description et activation des modules Dolibarr
*/
class DolibarrModules
{
  //! Database handler
  var $db;
  //! Tableau des boites
  var $boxes;
  //! Tableau des constantes
  var $const;
  //! Tableau des droits
  var $rights;
  //! Tableau des documents
  var $docs;
  //! Tableau des menus
  var $menu=array();
  
  var $dbversion;


  /**
   *      \brief      Constructeur
   *      \param      DB      handler d'acces base
   */
  function DolibarrModules($DB)
  {
    $this->db = $DB ;
    $this->dbversion = "-";
  }


  /**
   *      \brief      Fonction d'activation. Insere en base les constantes et boites du module
   *      \param      array_sql       tableau de requete sql a executer a l'activation
   *      \return     int             1 si ok, 0 si erreur
   */
  function _init($array_sql)
  {
    global $langs;
    $err='';
    
    // Insere une entree dans llx_dolibarr_modules
    $err+=$this->_dbactive();
    
    // Insere la constante d'activation module
    $err+=$this->_active();
    
    // Insere les constantes associees au module dans llx_const
    $err+=$this->insert_const();
    
    // Insere les boites dans llx_boxes_def
    $err+=$this->insert_boxes();
    
    // Insere les permissions associees au module actif dans llx_rights_def
    $err+=$this->insert_permissions();
    
    // Insere les constantes associees au module dans llx_const
    $err+=$this->insert_menus();
    
    // Cree les repertoires
    if (is_array($this->dirs))
      {
	foreach ($this->dirs as $key => $dir)
	  {
	    if ($dir && ! file_exists($dir))
	      {
		if (create_exdir($dir) < 0)
		  {
		    $this->error = $langs->trans("ErrorCanNotCreateDir",$dir);
		    dolibarr_syslog("DolibarrModules::_init error");
		    dolibarr_syslog("ErrorCanNotCreateDir $dir");
		  }
	      }
	  }
      }
    
    // Execute les requetes sql complementaires
    for ($i = 0 ; $i < sizeof($array_sql) ; $i++)
      {
	$sql=$array_sql[$i];
	$result=$this->db->query($sql);
	if (! $result)
	  {
	    dolibarr_syslog("DolibarrModules.class::init Error sql=".$sql." - ".$this->db->error());
	    $err++;
	  }
      }
    
    // Cree les documents generables
    if (is_array($this->docs))
      {
	foreach ($this->docs as $key => $doc)
	  {
	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_generator (rowid,name,classfile,class) VALUES ";
	    $sql .= "(".$doc[0].",'".addslashes($doc[1])."','".$doc[2]."','".$doc[3]."');";
	    
	    $result=$this->db->query($sql);
	    if (! $result)
	      {
		dolibarr_syslog("DolibarrModules.class::init Error sql=".$sql." - ".$this->db->error());
		$err++;
	      }
	    
	  }
      }
    
    
    // Renvoi valeur de retour
    if ($err > 0) return 0;
    return 1;
  }
  
  /**
     \brief      Fonction de desactivation. Supprime de la base les constantes et boites du module
     \param      array_sql       tableau de requete sql a executer a la desactivation
     \return     int             1 si ok, 0 si erreur
  */
  function _remove($array_sql)
  {
    $err = 0;
    
    // Supprime entree des modules
    $err+=$this->_dbunactive();
    
    // Supprime la constante d'activation du module
    $err+=$this->_unactive();
    
    // Supprime les boites de la liste des boites disponibles
    $err+=$this->delete_boxes();
    
    // Supprime les droits de la liste des droits disponibles
    $err+=$this->delete_permissions();
    
    // Supprime les menus apportes par le module
    $err+=$this->delete_menus();
    
    // Supprime les documents generables
    $err+=$this->delete_docs();

    // Execute les requetes sql complementaires
    for ($i = 0 ; $i < sizeof($array_sql) ; $i++)
      {
	if (!$this->db->query($array_sql[$i]))
	  {
	    $err++;
	  }
      }
    
    // Renvoi valeur de retour
        if ($err > 0) return 0;
        return 1;
  }
  

  /**
     \brief      Retourne le nom traduit du module si la traduction existe dans admin.lang,
     sinon le nom defini par defaut dans le module.
     \return     string      Nom du module traduit
  */
  function getName()
  {
    global $langs;
    $langs->load("admin");
    
    if ($langs->trans("Module".$this->numero."Name") != ("Module".$this->numero."Name"))
      {
	// Si traduction du nom du module existe
	return $langs->trans("Module".$this->numero."Name");
      }
    else
      {
	// Si traduction du nom du module n'existe pas, on prend definition en dur dans module
	return $this->name;
      }
  }
  
  
  /**
     \brief      Retourne la description traduite du module si la traduction existe dans admin.lang,
     sinon la description definie par defaut dans le module.
     \return     string      Nom du module traduit
  */
  function getDesc()
  {
    global $langs;
    $langs->load("admin");
    
    if ($langs->trans("Module".$this->numero."Desc") != ("Module".$this->numero."Desc"))
      {
	// Si traduction de la description du module existe
	return $langs->trans("Module".$this->numero."Desc");
      }
    else
      {
	// Si traduction de la description du module n'existe pas, on prend definition en dur dans module
	return $this->description;
      }
  }
  
  
  /**
     \brief      Retourne la version du module.
     Pour les modules a l'etat 'experimental', retourne la traduction de 'experimental'
     Pour les modules 'dolibarr', retourne la version de Dolibarr
     Pour les autres modules, retourne la version du module
     \return     string      Version du module
  */
  function getVersion()
  {
    global $langs;
    $langs->load("admin");
    
    if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
    elseif ($this->version == 'development') return $langs->trans("VersionDevelopment");
    elseif ($this->version == 'dolibarr') return DOL_VERSION;
    elseif ($this->version) return $this->version;
    else return $langs->trans("VersionUnknown");
  }
  

    /**
            \brief      Retourne la version en base du module.
            \return     string      Version du module
     */
    function getDbVersion()
    {
        global $langs;
        $langs->load("admin");

        $sql ="SELECT active_version FROM ".MAIN_DB_PREFIX."dolibarr_modules";
        $sql .= " WHERE numero=".$this->numero." AND active = 1";

        $resql = $this->db->query($sql);

        if ($resql)
        {
            $num = $this->db->num_rows($resql);

            if ($num > 0)
            {
                $row = $this->db->fetch_row($resql);

                $this->dbversion = $row[0];
            }

            $this->db->free($resql);
        }
        
        
        if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
        elseif ($this->version == 'development') return $langs->trans("VersionDevelopment");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return "";

    }


    /**
            \brief      Retourne la liste des fichiers lang en rapport avec le module
            \return     array       Tableau des fichier lang
     */
    function getLangFilesArray()
    {
        return $this->langfiles;
    }
    
    /**
            \brief      Retourne le libelle d'un lot de donnees exportable
            \return     string      Libelle du lot de donnees
     */
    function getDatasetLabel($r)
    {
        global $langs;
        
        $langstring="ExportDataset_".$this->export_code[$r];
        if ($langs->trans($langstring) == $langstring)
        {
            // Traduction non trouvee
            return $this->export_label[$r];
        }
        else
        {
            // Traduction trouvee
            return $langs->trans($langstring);
        }
    }
    
    
  /**
     \brief      Insere ligne module
     \return     int         Nombre d'erreurs (0 si ok)
  */
  function _dbactive()
  {
    $err = 0;
    
    $sql_del = "DELETE FROM ".MAIN_DB_PREFIX."dolibarr_modules WHERE numero=".$this->numero.";";
    $this->db->query($sql_del);
    
    $sql ="INSERT INTO ".MAIN_DB_PREFIX."dolibarr_modules (numero,active,active_date,active_version)";
    $sql .= " VALUES (";
    $sql .= $this->numero.",1,now(),'".$this->version."')";
    
    $this->db->query($sql);
    
    return $err;
  }
  
  
  /**
     \brief      Supprime ligne module
     \return     int     Nombre d'erreurs (0 si ok)
  */
  function _dbunactive()
  {
    $err = 0;
    
    $sql_del = "DELETE FROM ".MAIN_DB_PREFIX."dolibarr_modules WHERE numero=".$this->numero.";";
    $this->db->query($sql_del);
    
    return $err;
  }
    

    /**
            \brief      Insere constante d'activation module
            \return     int     Nombre d'erreurs (0 si ok)
     */
    function _active()
    {
        $err = 0;

        $sql_del = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = '".$this->const_name."';";
        $this->db->query($sql_del);

        $sql ="INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES
        ('".$this->const_name."','1',0);";
        if (!$this->db->query($sql))
        {
            $err++;
        }
        
        return $err;
    }
    
    
    /**
            \brief      Supprime constante d'activation module
            \return     int     Nombre d'erreurs (0 si ok)
     */
    function _unactive()
    {
        $err = 0;

        $sql_del = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = '".$this->const_name."';";
        $this->db->query($sql_del);

        return $err;
    }


    /**
            \brief      Insere les boites associees au module dans llx_boxes_def
            \return     int     Nombre d'erreurs (0 si ok)
     */
    function insert_boxes()
    {
        $err=0;

        if (is_array($this->boxes))
        {
	        foreach ($this->boxes as $key => $value)
	        {
	            //$titre = $this->boxes[$key][0];
	            $file  = $this->boxes[$key][1];
	            $note  = $this->boxes[$key][2];
	
	            $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."boxes_def";
	            $sql.= " WHERE file ='".$file."'";
	            if ($note) $sql.=" AND note ='".addslashes($note)."'";
	
	            $result=$this->db->query($sql);
	            if ($result)
	            {
	                $row = $this->db->fetch_row($result);
	                if ($row[0] == 0)
	                {
	                    $sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes_def (file,note)";
	                    $sql.= " VALUES ('".addslashes($file)."',";
	                    $sql.= $note?"'".addslashes($note)."'":"null";
	                    $sql.= ")";
						//print $sql;
	                    if (! $this->db->query($sql))
	                    {
	                        $err++;
	                    }
	                }
	            }
	            else
	            {
	                $err++;
	            }
	        }
        }
        
        return $err;
    }


  
	/**
     \brief      Supprime les documents
     \return     int     Nombre d'erreurs (0 si ok)
	*/
	function delete_docs()
	{   
		$err=0;

		// Supprime les documents generables
		if (is_array($this->docs))
		{
			foreach ($this->docs as $key => $doc)
			{
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."document_generator ";
				$sql .= "WHERE name= '".addslashes($doc[0])."' AND classfile='".$doc[1]."'AND class='".$doc[2]."';";
				
				$result=$this->db->query($sql);
				if (! $result)
				{
					dolibarr_syslog("DolibarrModules.class::delete_docs Error sql=".$sql." - ".$this->db->error());
					$err++;
				}
				
			}
		}
		return $err;
	}
  
  
  /**
     \brief      Supprime les boites
     \return     int     Nombre d'erreurs (0 si ok)
  */
  function delete_boxes()
  {
    $err=0;
    
    if (is_array($this->boxes))
      {
	foreach ($this->boxes as $key => $value)
	  {
	    //$titre = $this->boxes[$key][0];
	    $file  = $this->boxes[$key][1];
	    //$note  = $this->boxes[$key][2];
	    
	    $sql = "DELETE ".MAIN_DB_PREFIX."boxes";
	    $sql.= " FROM ".MAIN_DB_PREFIX."boxes, ".MAIN_DB_PREFIX."boxes_def";
	    $sql.= " WHERE ".MAIN_DB_PREFIX."boxes.box_id = ".MAIN_DB_PREFIX."boxes_def.rowid";
	    $sql.= " AND ".MAIN_DB_PREFIX."boxes_def.file = '".addslashes($file)."'";
	    dolibarr_syslog("DolibarrModules::delete_boxes sql=".$sql);
	    $this->db->query($sql);
	    
	    $sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes_def";
	    $sql.= " WHERE file = '".addslashes($file)."'";
	    dolibarr_syslog("DolibarrModules::delete_boxes sql=".$sql);
	    if (! $this->db->query($sql))
	      {
		$err++;
	      }
	  }
      }
    
    return $err;
  }
  
    /**
            \brief      Insere les constantes associees au module dans llx_const
            \return     int     Nombre d'erreurs (0 si ok)
     */
    function insert_const()
    {
        $err=0;
        
        foreach ($this->const as $key => $value)
        {
            $name   = $this->const[$key][0];
            $type   = $this->const[$key][1];
            $val    = $this->const[$key][2];
            $note   = $this->const[$key][3];
            $visible= $this->const[$key][4];

            $sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."const WHERE name ='".$name."'";

            $result=$this->db->query($sql);
            if ($result)
            {
                $row = $this->db->fetch_row($result);

                if ($row[0] == 0)
                {
                    if (! $visible) $visible='0';
                    if (strlen($note))
                    {
                        $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,type,value,note,visible) VALUES ('$name','$type','$val','$note','$visible');";
                    }
                    elseif (strlen($val))
                    {
                        $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,type,value,visible) VALUES ('$name','$type','$val','$visible');";
                    }
                    else
                    {
                        $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,type,visible) VALUES ('$name','$type','$visible');";
                    }

                    if (! $this->db->query($sql) )
                    {
                        $err++;
                    }
                }
            }
            else
            {
                $err++;
            }
        }
        
        return $err;
    }
    
    /**
            \brief      Insere les permissions associees au module dans llx_rights_def
            \return     int     Nombre d'erreurs (0 si ok)
     */
    function insert_permissions()
    {
        $err=0;

        //print $this->rights_class." ".sizeof($this->rights)."<br>";

        // Test si module actif
        $sql_del = "SELECT value FROM ".MAIN_DB_PREFIX."const WHERE name = '".$this->const_name."';";
        $resql=$this->db->query($sql_del);
        if ($resql) {
            
            $obj=$this->db->fetch_object($resql);
            if ($obj->value) {
                
                // Si module actif
                foreach ($this->rights as $key => $value)
                {
                    $r_id       = $this->rights[$key][0];
                    $r_desc     = $this->rights[$key][1];
                    $r_type     = $this->rights[$key][2];
                    $r_def      = $this->rights[$key][3];
                    $r_perms    = $this->rights[$key][4];
                    $r_subperms = $this->rights[$key][5];
                    $r_modul    = $this->rights_class;
        
                    if (strlen($r_perms) )
                    {
                        if (strlen($r_subperms) )
                        {
                            $sql = "INSERT INTO ".MAIN_DB_PREFIX."rights_def ";
                            $sql .= " (id, libelle, module, type, bydefault, perms, subperms)";
                            $sql .= " VALUES ";
                            $sql .= "(".$r_id.",'".addslashes($r_desc)."','".$r_modul."','".$r_type."',".$r_def.",'".$r_perms."','".$r_subperms."');";
                        }
                        else
                        {
                            $sql = "INSERT INTO ".MAIN_DB_PREFIX."rights_def ";
                            $sql .= " (id, libelle, module, type, bydefault, perms)";
                            $sql .= " VALUES ";
                            $sql .= "(".$r_id.",'".addslashes($r_desc)."','".$r_modul."','".$r_type."',".$r_def.",'".$r_perms."');";
                        }
                    }
                    else
                    {
                        $sql = "INSERT INTO ".MAIN_DB_PREFIX."rights_def ";
                        $sql .= " (id, libelle, module, type, bydefault)";
                        $sql .= " VALUES ";
                        $sql .= "(".$r_id.",'".addslashes($r_desc)."','".$r_modul."','".$r_type."',".$r_def.");";
                    }
        
                    $resql=$this->db->query($sql);
                    if (! $resql)
                    {
                        if ($this->db->errno() != "DB_ERROR_RECORD_ALREADY_EXISTS") {
                            $err++;
                        }
                    }
                }
            }
        }
        
        return $err;
    }


    /**
            \brief      Supprime les permissions
            \return     int     Nombre d'erreurs (0 si ok)
     */
    function delete_permissions()
    {
        $err=0;
        
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."rights_def WHERE module = '".$this->rights_class."';";
        if (!$this->db->query($sql))
        {
            $err++;
        }

        return $err;
    }

    
    /**
            \brief      Insere les menus dans llx_menu*
            \return     int     Nombre d'erreurs (0 si ok)
     */
    function insert_menus()
    {
		global $user;
    	
		require_once(DOL_DOCUMENT_ROOT."/lib/menubase.class.php");

		$err=0;
		        
        foreach ($this->menu as $key => $value)
        {
       		$menu = new Menubase($this->db);
			$menu->menu_handler='all';	
			$menu->module=$this->rights_class;
			$menu->fk_menu=$this->menu[$key]['fk_menu'];
			$menu->type=$this->menu[$key]['type'];
			$menu->mainmenu=$this->menu[$key]['mainmenu'];
			$menu->titre=$this->menu[$key]['titre'];
			$menu->leftmenu=$this->menu[$key]['leftmenu'];
			$menu->url=$this->menu[$key]['url'];
			$menu->langs=$this->menu[$key]['langs'];
			$menu->position=$this->menu[$key]['position'];
			$menu->perms=$this->menu[$key]['perms'];
			$menu->target=$this->menu[$key]['target'];
			$menu->user=$this->menu[$key]['user'];
			$result=$menu->create($user);
			if ($result <= 0)
            {
                $err++;
            }
        }
        
        return $err;
    }


    /**
            \brief      Supprime les permissions
            \return     int     Nombre d'erreurs (0 si ok)
     */
    function delete_menus()
    {
        $err=0;
        
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."menu";
        $sql.= " WHERE module = '".addslashes($this->rights_class)."'";
        
		dolibarr_syslog("DolibarrModules::delete_menus sql=".$sql);
		$resql=$this->db->query($sql);
		if (! $resql)
        {
            $err++;
        }

        return $err;
    }
        
}
?>
