<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *  \file           htdocs/core/modules/DolibarrModules.class.php
 *  \brief          File of parent class of module descriptor class files
 */


/**
 *  Parent class of module descriptor class files
 */
abstract class DolibarrModules
{
    //! Database handler
    var $db;
    //! Relative path to module style sheet
    var $style_sheet = ''; // deprecated
    //! Path to create when module activated
    var $dirs = array();
    //! Tableau des boites
    var $boxes;
    //! Tableau des constantes
    var $const;
    //! Tableau des droits
    var $rights;
    //! Tableau des menus
    var $menu=array();
    //! Module parts array
    var $module_parts=array();
    //! Tableau des documents ???
    var $docs;

    var $dbversion = "-";


    /**
     *  Method to enable a module. Insert into database all constants, boxes of module
     *
     *  @param      array	$array_sql  	Array of SQL requests to execute when enabling module
     *  @param      string	$options    	String with options when disabling module ('newboxdefonly|noboxes')
     *  @return     int              		1 if OK, 0 if KO
     */
    function _init($array_sql, $options='')
    {
        global $conf, $langs;
        $err=0;

        $this->db->begin();

        // Insert activation module constant
        if (! $err) $err+=$this->_active();

        // Insert new pages for tabs into llx_const
        if (! $err) $err+=$this->insert_tabs();

        // Insert activation of module's parts
        if (! $err) $err+=$this->insert_module_parts();

        // Insert constant defined by modules, into llx_const
        if (! $err) $err+=$this->insert_const();

        // Insert boxes def into llx_boxes_def and boxes setup into llx_boxes
        if (! $err && ! preg_match('/noboxes/',$options)) $err+=$this->insert_boxes($options);

        // Insert permission definitions of module into llx_rights_def. If user is admin, grant this permission to user.
        if (! $err) $err+=$this->insert_permissions(1);

        // Insert specific menus entries into database
        if (! $err) $err+=$this->insert_menus();

        // Create module's directories
        if (! $err) $err+=$this->create_dirs();

        // Execute addons requests
        $num=count($array_sql);
    	for ($i = 0; $i < $num; $i++)
        {
            if (! $err)
            {
                $val=$array_sql[$i];
                $sql='';
                $ignoreerror=0;
                if (is_array($val))
                {
                    $sql=$val['sql'];
                    $ignoreerror=$val['ignoreerror'];
                }
                else
                {
                    $sql=$val;
                }

                // Add current entity id
                $sql=str_replace('__ENTITY__', $conf->entity, $sql);

                dol_syslog(get_class($this)."::_init ignoreerror=".$ignoreerror." sql=".$sql, LOG_DEBUG);
                $result=$this->db->query($sql);
                if (! $result)
                {
                    if (! $ignoreerror)
                    {
                        $this->error=$this->db->lasterror();
                        dol_syslog(get_class($this)."::_init Error ".$this->error, LOG_ERR);
                        $err++;
                    }
                    else
                    {
                        dol_syslog(get_class($this)."::_init Warning ".$this->db->lasterror(), LOG_WARNING);
                    }
                }
            }
        }

        // Return code
        if (! $err)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->db->rollback();
            return 0;
        }
    }

    /**
     *  Fonction de desactivation. Supprime de la base les constantes et boites du module
     *
     *  @param      array	$array_sql      Array of SQL requests to execute when disable module
     *  @param      string	$options		String with options when disabling module ('newboxdefonly|noboxes')
     *  @return     int      		       	1 if OK, 0 if KO
     */
    function _remove($array_sql, $options='')
    {
        global $langs;
        $err=0;

        $this->db->begin();

        // Remove activation module line (constant MAIN_MODULE_MYMODULE in llx_const)
        if (! $err) $err+=$this->_unactive();

        // Remove activation of module's new tabs (MAIN_MODULE_MYMODULE_TABS_XXX in llx_const)
        if (! $err) $err+=$this->delete_tabs();

        // Remove activation of module's parts (MAIN_MODULE_MYMODULE_XXX in llx_const)
        if (! $err) $err+=$this->delete_module_parts();

        // Remove constants defined by modules
        if (! $err) $err+=$this->delete_const();

        // Remove list of module's available boxes (entry in llx_boxes)
        if (! $err && ! preg_match('/(newboxdefonly|noboxes)/',$options)) $err+=$this->delete_boxes();	// We don't have to delete if option ask to keep boxes safe or ask to add new box def only

        // Remove module's permissions from list of available permissions (entries in llx_rights_def)
        if (! $err) $err+=$this->delete_permissions();

        // Remove module's menus (entries in llx_menu)
        if (! $err) $err+=$this->delete_menus();

        // Remove module's directories
        if (! $err) $err+=$this->delete_dirs();

        // Run complementary sql requests
        $num=count($array_sql);
        for ($i = 0; $i < $num; $i++)
        {
            if (! $err)
            {
                dol_syslog(get_class($this)."::_remove sql=".$array_sql[$i], LOG_DEBUG);
                $result=$this->db->query($array_sql[$i]);
                if (! $result)
                {
                    $this->error=$this->db->error();
                    dol_syslog(get_class($this)."::_remove Error ".$this->error, LOG_ERR);
                    $err++;
                }
            }
        }

        // Return code
        if (! $err)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->db->rollback();
            return 0;
        }
    }


    /**
     *  Retourne le nom traduit du module si la traduction existe dans admin.lang,
     *  sinon le nom defini par defaut dans le module.
     *
     *  @return     string      Nom du module traduit
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
            // If translation of module with its numero does not exists, we take its name
            return $this->name;
        }
    }


    /**
     *  Retourne la description traduite du module si la traduction existe dans admin.lang,
     *  sinon la description definie par defaut dans le module
     *
     *  @return     string      Nom du module traduit
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
     *  Return module version.
     *  Pour les modules a l'etat 'experimental', retourne la traduction de 'experimental'
     *  Pour les modules 'dolibarr', retourne la version de Dolibarr
     *  Pour les autres modules, retourne la version du module
     *
     *  @return     string      Version du module
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
     *  Return if a module is a core or external module
     *
     *  @return     string      'core', 'external' or 'unknown'
     */
    function isCoreOrExternalModule()
    {
        if ($this->version == 'dolibarr') return 'core';
        if (! empty($this->version) && ! in_array($this->version,array('experimental','development'))) return 'external';
        if (! empty($this->editor_name) || ! empty($this->editor_web)) return 'external';
        return 'unknown';
    }


    /**
     *  Return list of lang files related to module
     *
     *  @return     array       Array of lang files
     */
    function getLangFilesArray()
    {
        return $this->langfiles;
    }

    /**
     *  Return translated label of a export dataset
     *
     *	@param	int		$r		Index of dataset
     *  @return string      	Label of databaset
     */
    function getExportDatasetLabel($r)
    {
        global $langs;

        $langstring="ExportDataset_".$this->export_code[$r];
        if ($langs->trans($langstring) == $langstring)
        {
            // Traduction non trouvee
            return $langs->trans($this->export_label[$r]);
        }
        else
        {
            // Traduction trouvee
            return $langs->trans($langstring);
        }
    }


    /**
     *  Return translated label of an import dataset
     *
     *  @param	int		$r		Index of dataset
     *  @return	string    		Label of databaset
     */
    function getImportDatasetLabel($r)
    {
        global $langs;

        $langstring="ImportDataset_".$this->import_code[$r];
        //print "x".$langstring;
        if ($langs->trans($langstring) == $langstring)
        {
            // Traduction non trouvee
            return $langs->trans($this->import_label[$r]);
        }
        else
        {
            // Traduction trouvee
            return $langs->trans($langstring);
        }
    }


    /**
     *      Insert constant to activate module
     *
     *      @return     int     Nb of errors (0 if OK)
     */
    function _active()
    {
        global $conf;

        $err = 0;

        // Common module
        $entity = ((! empty($this->always_enabled) || ! empty($this->core_enabled)) ? 0 : $conf->entity);

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
        $sql.= " WHERE ".$this->db->decrypt('name')." = '".$this->const_name."'";
        $sql.= " AND entity IN (0, ".$entity.")";

        dol_syslog(get_class($this)."::_active sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if (! $resql) $err++;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible,entity) VALUES";
        $sql.= " (".$this->db->encrypt($this->const_name,1);
        $sql.= ",".$this->db->encrypt('1',1);
        $sql.= ",0,".$entity.")";

        dol_syslog(get_class($this)."::_active sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if (! $resql) $err++;

        return $err;
    }


    /**
     *      Remove activation line
     *
     *      @return     int     Nb of errors (0 if OK)
     **/
    function _unactive()
    {
        global $conf;

        $err = 0;

        // Common module
        $entity = ((! empty($this->always_enabled) || ! empty($this->core_enabled)) ? 0 : $conf->entity);

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
        $sql.= " WHERE ".$this->db->decrypt('name')." = '".$this->const_name."'";
        $sql.= " AND entity IN (0, ".$entity.")";

        dol_syslog(get_class($this)."::_unactive sql=".$sql);
        $this->db->query($sql);

        return $err;
    }


    /**
     *  Create tables and keys required by module.
     *  Files module.sql and module.key.sql with create table and create keys
     *  commands must be stored in directory reldir='/module/sql/'
     *  This function is called by this->init
     *
     *  @param	string	$reldir		Relative directory where to scan files
     *  @return	int     			<=0 if KO, >0 if OK
     */
    function _load_tables($reldir)
    {
        global $db,$conf;

        $error=0;
		$dirfound=0;

		if (empty($reldir)) return 1;

        include_once DOL_DOCUMENT_ROOT .'/core/lib/admin.lib.php';

        $ok = 1;
        foreach($conf->file->dol_document_root as $dirroot)
        {
            if ($ok)
            {
                $dir = $dirroot.$reldir;
                $ok = 0;

                $handle=@opendir($dir);         // Dir may not exists
                if (is_resource($handle))
                {
                	$dirfound++;

	                // Run llx_mytable.sql files
                	while (($file = readdir($handle))!==false)
                    {
                        if (preg_match('/\.sql$/i',$file) && ! preg_match('/\.key\.sql$/i',$file) && substr($file,0,4) == 'llx_' && substr($file,0,4) != 'data')
                        {
                            $result=run_sql($dir.$file,1,'',1);
                            if ($result <= 0) $error++;
                        }
                    }

                    rewinddir($handle);

	                // Run llx_mytable.key.sql files (Must be done after llx_mytable.sql)
                	while (($file = readdir($handle))!==false)
                    {
                        if (preg_match('/\.key\.sql$/i',$file) && substr($file,0,4) == 'llx_' && substr($file,0,4) != 'data')
                        {
                            $result=run_sql($dir.$file,1,'',1);
                            if ($result <= 0) $error++;
                        }
                    }

                    rewinddir($handle);

                    // Run data_xxx.sql files (Must be done after llx_mytable.key.sql)
                	while (($file = readdir($handle))!==false)
                    {
                        if (preg_match('/\.sql$/i',$file) && ! preg_match('/\.key\.sql$/i',$file) && substr($file,0,4) == 'data')
                        {
                            $result=run_sql($dir.$file,1,'',1);
                            if ($result <= 0) $error++;
                        }
                    }

                    rewinddir($handle);

                    // Run update_xxx.sql files
                	while (($file = readdir($handle))!==false)
                    {
                        if (preg_match('/\.sql$/i',$file) && ! preg_match('/\.key\.sql$/i',$file) && substr($file,0,6) == 'update')
                        {
                            $result=run_sql($dir.$file,1,'',1);
                            if ($result <= 0) $error++;
                        }
                    }

                    closedir($handle);
                }

                if ($error == 0)
                {
                    $ok = 1;
                }
            }
        }

        if (! $dirfound) dol_syslog("A module ask to load sql files into ".$reldir." but this directory was not found.", LOG_WARNING);
        return $ok;
    }


    /**
     *  Insert boxes into llx_boxes_def
     *
     *	@param		string	$option		String with options when disabling module ('newboxdefonly'=insert only boxes definition)
     *  @return     int     			Nb of errors (0 if OK)
     */
    function insert_boxes($option='')
    {
		require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';

    	global $conf;

        $err=0;

        if (is_array($this->boxes))
        {
            foreach ($this->boxes as $key => $value)
            {
                $file  = isset($this->boxes[$key]['file'])?$this->boxes[$key]['file']:'';
                $note  = isset($this->boxes[$key]['note'])?$this->boxes[$key]['note']:'';
                $enabledbydefaulton = isset($this->boxes[$key]['enabledbydefaulton'])?$this->boxes[$key]['enabledbydefaulton']:'Home';

            	if (empty($file)) $file  = isset($this->boxes[$key][1])?$this->boxes[$key][1]:'';	// For backward compatibility
                if (empty($note)) $note  = isset($this->boxes[$key][2])?$this->boxes[$key][2]:'';	// For backward compatibility

                // Search if boxes def already present
                $sql = "SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."boxes_def";
                $sql.= " WHERE file = '".$this->db->escape($file)."'";
                $sql.= " AND entity = ".$conf->entity;
                if ($note) $sql.=" AND note ='".$this->db->escape($note)."'";

                dol_syslog(get_class($this)."::insert_boxes sql=".$sql);
                $result=$this->db->query($sql);
                if ($result)
                {
                    $obj = $this->db->fetch_object($result);
                    if ($obj->nb == 0)
                    {
                        $this->db->begin();

                        if (! $err)
                        {
                            $sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes_def (file, entity, note)";
                            $sql.= " VALUES ('".$this->db->escape($file)."', ";
                            $sql.= $conf->entity.", ";
                            $sql.= $note?"'".$this->db->escape($note)."'":"null";
                            $sql.= ")";

                            dol_syslog(get_class($this)."::insert_boxes sql=".$sql);
                            $resql=$this->db->query($sql);
                            if (! $resql) $err++;

                        }
                        if (! $err && ! preg_match('/newboxdefonly/',$options))
                        {
                            $lastid=$this->db->last_insert_id(MAIN_DB_PREFIX."boxes_def","rowid");

                            $pos_name = getStaticMember('InfoBox','listOfPages');
                            foreach ($pos_name as $key2 => $val2)
                            {
                            	//print 'key2='.$key2.'-val2='.$val2."<br>\n";
                            	if ($enabledbydefaulton && $val2 != $enabledbydefaulton) continue;		// Not enabled by default onto this page.

	                            $sql = "INSERT INTO ".MAIN_DB_PREFIX."boxes (box_id,position,box_order,fk_user,entity)";
    	                        $sql.= " VALUES (".$lastid.", ".$key2.", '0', 0, ".$conf->entity.")";

    	                        dol_syslog(get_class($this)."::insert_boxes onto page ".$key2."=".$val2." sql=".$sql);
                            	$resql=$this->db->query($sql);
                            	if (! $resql) $err++;
                            }
                        }

                        if (! $err)
                        {
                            $this->db->commit();
                        }
                        else
						{
                            $this->error=$this->db->lasterror();
                            dol_syslog(get_class($this)."::insert_boxes ".$this->error, LOG_ERR);
                            $this->db->rollback();
                        }
                    }
                    // else box already registered into database
                }
                else
              {
                    $this->error=$this->db->lasterror();
                    dol_syslog(get_class($this)."::insert_boxes ".$this->error, LOG_ERR);
                    $err++;
                }
            }
        }

        return $err;
    }


    /**
     *  Delete boxes
     *
     *  @return     int     Nb of errors (0 if OK)
     */
    function delete_boxes()
    {
        global $conf;

        $err=0;

        if (is_array($this->boxes))
        {
            foreach ($this->boxes as $key => $value)
            {
                //$titre = $this->boxes[$key][0];
                $file  = $this->boxes[$key]['file'];
                //$note  = $this->boxes[$key][2];

                if (empty($file)) $file  = isset($this->boxes[$key][1])?$this->boxes[$key][1]:'';	// For backward compatibility

                $sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes";
                $sql.= " USING ".MAIN_DB_PREFIX."boxes, ".MAIN_DB_PREFIX."boxes_def";
                $sql.= " WHERE ".MAIN_DB_PREFIX."boxes.box_id = ".MAIN_DB_PREFIX."boxes_def.rowid";
                $sql.= " AND ".MAIN_DB_PREFIX."boxes_def.file = '".$this->db->escape($file)."'";
                $sql.= " AND ".MAIN_DB_PREFIX."boxes.entity = ".$conf->entity;

                dol_syslog(get_class($this)."::delete_boxes sql=".$sql);
                $resql=$this->db->query($sql);
                if (! $resql)
                {
                    $this->error=$this->db->lasterror();
                    dol_syslog(get_class($this)."::delete_boxes ".$this->error, LOG_ERR);
                    $err++;
                }

                $sql = "DELETE FROM ".MAIN_DB_PREFIX."boxes_def";
                $sql.= " WHERE file = '".$this->db->escape($file)."'";
                $sql.= " AND entity = ".$conf->entity;

                dol_syslog(get_class($this)."::delete_boxes sql=".$sql);
                $resql=$this->db->query($sql);
                if (! $resql)
                {
                    $this->error=$this->db->lasterror();
                    dol_syslog(get_class($this)."::delete_boxes ".$this->error, LOG_ERR);
                    $err++;
                }
            }
        }

        return $err;
    }

    /**
     *  Remove links to new module page present in llx_const
     *
     *  @return     int     Nb of errors (0 if OK)
     */
    function delete_tabs()
    {
        global $conf;

        $err=0;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
        $sql.= " WHERE ".$this->db->decrypt('name')." like '".$this->const_name."_TABS_%'";
        $sql.= " AND entity = ".$conf->entity;

        dol_syslog(get_class($this)."::delete_tabs sql=".$sql);
        if (! $this->db->query($sql))
        {
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::delete_tabs ".$this->error, LOG_ERR);
            $err++;
        }

        return $err;
    }

    /**
     *  Add links of new pages from modules in llx_const
     *
     *  @return     int     Number of errors (0 if ok)
     */
    function insert_tabs()
    {
        global $conf;

        $err=0;

        if (! empty($this->tabs))
        {
            $i=0;
            foreach ($this->tabs as $key => $value)
            {
                if ($value)
                {
                    $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (";
                    $sql.= "name";
                    $sql.= ", type";
                    $sql.= ", value";
                    $sql.= ", note";
                    $sql.= ", visible";
                    $sql.= ", entity";
                    $sql.= ")";
                    $sql.= " VALUES (";
                    $sql.= $this->db->encrypt($this->const_name."_TABS_".$i,1);
                    $sql.= ", 'chaine'";
                    $sql.= ", ".$this->db->encrypt($value,1);
                    $sql.= ", null";
                    $sql.= ", '0'";
                    $sql.= ", ".$conf->entity;
                    $sql.= ")";

                    dol_syslog(get_class($this)."::insert_tabs sql=".$sql);
                    $resql=$this->db->query($sql);
                    /* Allow duplicate key
                     if (! $resql)
                     {
                        $err++;
                        }
                        */
                }
                $i++;
            }
        }
        return $err;
    }

    /**
     *  Insert constants defined into $this->const array into table llx_const
     *
     *  @return     int     Number of errors (0 if OK)
     */
    function insert_const()
    {
        global $conf;

        $err=0;

        foreach ($this->const as $key => $value)
        {
            $name      = $this->const[$key][0];
            $type      = $this->const[$key][1];
            $val       = $this->const[$key][2];
            $note      = isset($this->const[$key][3])?$this->const[$key][3]:'';
            $visible   = isset($this->const[$key][4])?$this->const[$key][4]:0;
            $entity    = (! empty($this->const[$key][5]) && $this->const[$key][5]!='current')?0:$conf->entity;

            // Clean
            if (empty($visible)) $visible='0';
            if (empty($val)) $val='';

            $sql = "SELECT count(*)";
            $sql.= " FROM ".MAIN_DB_PREFIX."const";
            $sql.= " WHERE ".$this->db->decrypt('name')." = '".$name."'";
            $sql.= " AND entity = ".$entity;

            $result=$this->db->query($sql);
            if ($result)
            {
                $row = $this->db->fetch_row($result);

                if ($row[0] == 0)   // If not found
                {
                    $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,type,value,note,visible,entity)";
                    $sql.= " VALUES (";
                    $sql.= $this->db->encrypt($name,1);
                    $sql.= ",'".$type."'";
                    $sql.= ",".($val?$this->db->encrypt($val,1):"''");
                    $sql.= ",".($note?"'".$this->db->escape($note)."'":"null");
                    $sql.= ",'".$visible."'";
                    $sql.= ",".$entity;
                    $sql.= ")";


                    dol_syslog(get_class($this)."::insert_const sql=".$sql);
                    if (! $this->db->query($sql) )
                    {
                        dol_syslog(get_class($this)."::insert_const ".$this->db->lasterror(), LOG_ERR);
                        $err++;
                    }
                }
                else
                {
                    dol_syslog(get_class($this)."::insert_const constant '".$name."' already exists", LOG_WARNING);
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
     * Remove constants with tags deleteonunactive
     *
     * @return     int     <0 if KO, 0 if OK
     */
    function delete_const()
    {
        global $conf;

        $err=0;

        foreach ($this->const as $key => $value)
        {
            $name      = $this->const[$key][0];
            $deleteonunactive = (! empty($this->const[$key][6]))?1:0;

            if ($deleteonunactive)
            {
                $sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
                $sql.= " WHERE ".$this->db->decrypt('name')." = '".$name."'";
                $sql.= " AND entity in (0, ".$conf->entity.")";
                dol_syslog(get_class($this)."::delete_const sql=".$sql);
                if (! $this->db->query($sql))
                {
                    $this->error=$this->db->lasterror();
                    dol_syslog(get_class($this)."::delete_const ".$this->error, LOG_ERR);
                    $err++;
                }
            }
        }

        return $err;
    }

    /**
     *  Insert permissions definitions related to the module into llx_rights_def
     *
     *  @param	int		$reinitadminperms   If 1, we also grant them to all admin users
     *  @param	int		$force_entity		Force current entity
     *  @return int                 		Number of error (0 if OK)
     */
    function insert_permissions($reinitadminperms=0, $force_entity=null)
    {
        global $conf,$user;

        $err=0;
        $entity=(! empty($force_entity) ? $force_entity : $conf->entity);

        // Test if module is activated
        $sql_del = "SELECT ".$this->db->decrypt('value')." as value";
        $sql_del.= " FROM ".MAIN_DB_PREFIX."const";
        $sql_del.= " WHERE ".$this->db->decrypt('name')." = '".$this->const_name."'";
        $sql_del.= " AND entity IN (0,".$entity.")";

        dol_syslog(get_class($this)."::insert_permissions sql=".$sql_del);
        $resql=$this->db->query($sql_del);
        if ($resql)
        {
            $obj=$this->db->fetch_object($resql);
            if (! empty($obj->value) && ! empty($this->rights))
            {
                // Si module actif
                foreach ($this->rights as $key => $value)
                {
                    $r_id       = $this->rights[$key][0];
                    $r_desc     = $this->rights[$key][1];
                    $r_type     = isset($this->rights[$key][2])?$this->rights[$key][2]:'';
                    $r_def      = $this->rights[$key][3];
                    $r_perms    = $this->rights[$key][4];
                    $r_subperms = isset($this->rights[$key][5])?$this->rights[$key][5]:'';
                    $r_modul    = $this->rights_class;

                    if (empty($r_type)) $r_type='w';

                    if (dol_strlen($r_perms) )
                    {
                        if (dol_strlen($r_subperms) )
                        {
                            $sql = "INSERT INTO ".MAIN_DB_PREFIX."rights_def";
                            $sql.= " (id, entity, libelle, module, type, bydefault, perms, subperms)";
                            $sql.= " VALUES ";
                            $sql.= "(".$r_id.",".$entity.",'".$this->db->escape($r_desc)."','".$r_modul."','".$r_type."',".$r_def.",'".$r_perms."','".$r_subperms."')";
                        }
                        else
                        {
                            $sql = "INSERT INTO ".MAIN_DB_PREFIX."rights_def";
                            $sql.= " (id, entity, libelle, module, type, bydefault, perms)";
                            $sql.= " VALUES ";
                            $sql.= "(".$r_id.",".$entity.",'".$this->db->escape($r_desc)."','".$r_modul."','".$r_type."',".$r_def.",'".$r_perms."')";
                        }
                    }
                    else
                    {
                        $sql = "INSERT INTO ".MAIN_DB_PREFIX."rights_def ";
                        $sql .= " (id, entity, libelle, module, type, bydefault)";
                        $sql .= " VALUES ";
                        $sql .= "(".$r_id.",".$entity.",'".$this->db->escape($r_desc)."','".$r_modul."','".$r_type."',".$r_def.")";
                    }

                    dol_syslog(get_class($this)."::insert_permissions sql=".$sql, LOG_DEBUG);
                    $resqlinsert=$this->db->query($sql,1);
                    if (! $resqlinsert)
                    {
                        if ($this->db->errno() != "DB_ERROR_RECORD_ALREADY_EXISTS")
                        {
                            $this->error=$this->db->lasterror();
                            dol_syslog(get_class($this)."::insert_permissions errno = ".$this->db->errno()." error ".$this->error, LOG_ERR);
                            $err++;
                            break;
                        }
                        else dol_syslog(get_class($this)."::insert_permissions record already exists", LOG_INFO);

                    }
                    $this->db->free($resqlinsert);

                    // If we want to init permissions on admin users
                    if ($reinitadminperms)
                    {
                    	if (! class_exists('User')) {
                    		require DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
                    	}
                        $sql="SELECT rowid FROM ".MAIN_DB_PREFIX."user WHERE admin = 1";
                        dol_syslog(get_class($this)."::insert_permissions Search all admin users sql=".$sql);
                        $resqlseladmin=$this->db->query($sql,1);
                        if ($resqlseladmin)
                        {
                            $num=$this->db->num_rows($resqlseladmin);
                            $i=0;
                            while ($i < $num)
                            {
                                $obj2=$this->db->fetch_object($resqlseladmin);
                                dol_syslog(get_class($this)."::insert_permissions Add permission to user id=".$obj2->rowid);
                                $tmpuser=new User($this->db);
                                $tmpuser->fetch($obj2->rowid);
                                if (!empty($tmpuser->id)) {
                                	$tmpuser->addrights($r_id);
                                }
                                $i++;
                            }
                            if (! empty($user->admin))  // Reload permission for current user if defined
                            {
                                // We reload permissions
                                $user->clearrights();
                                $user->getrights();
                            }
                        }
                        else dol_print_error($this->db);
                    }
                }
            }
            $this->db->free($resql);
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::insert_permissions ".$this->error, LOG_ERR);
            $err++;
        }

        return $err;
    }


    /**
     * Delete permissions
     *
     * @return     int     Nb of errors (0 if OK)
     */
    function delete_permissions()
    {
        global $conf;

        $err=0;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."rights_def";
        $sql.= " WHERE module = '".$this->rights_class."'";
        $sql.= " AND entity = ".$conf->entity;
        dol_syslog(get_class($this)."::delete_permissions sql=".$sql);
        if (! $this->db->query($sql))
        {
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::delete_permissions ".$this->error, LOG_ERR);
            $err++;
        }

        return $err;
    }


    /**
     *  Insert menus entries found into $this->menu into llx_menu*
     *
     *  @return     int     Nb of errors (0 if OK)
     */
    function insert_menus()
    {
    	global $user;

        require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';

        $err=0;

        $this->db->begin();

        //var_dump($this->menu); exit;
        foreach ($this->menu as $key => $value)
        {
            $menu = new Menubase($this->db);
            $menu->menu_handler='all';
            $menu->module=$this->rights_class;
            if (! $this->menu[$key]['fk_menu'])
            {
                $menu->fk_menu=0;
                //print 'aaa'.$this->menu[$key]['fk_menu'];
            }
            else
            {
                //print 'xxx'.$this->menu[$key]['fk_menu'];exit;
                $foundparent=0;
                $fk_parent=$this->menu[$key]['fk_menu'];
                if (preg_match('/^r=/',$fk_parent))	// old deprecated method
                {
                    $fk_parent=str_replace('r=','',$fk_parent);
                    if (isset($this->menu[$fk_parent]['rowid']))
                    {
                        $menu->fk_menu=$this->menu[$fk_parent]['rowid'];
                        $foundparent=1;
                    }
                }
                elseif (preg_match('/^fk_mainmenu=([a-zA-Z0-9_]+),fk_leftmenu=([a-zA-Z0-9_]+)$/',$fk_parent,$reg))
                {
                    $menu->fk_menu=-1;
                    $menu->fk_mainmenu=$reg[1];
                    $menu->fk_leftmenu=$reg[2];
                    $foundparent=1;
                }
                elseif (preg_match('/^fk_mainmenu=([a-zA-Z0-9_]+)$/',$fk_parent,$reg))
                {
                    $menu->fk_menu=-1;
                    $menu->fk_mainmenu=$reg[1];
                    $menu->fk_leftmenu='';
                    $foundparent=1;
                }
                if (! $foundparent)
                {
                    $this->error="ErrorBadDefinitionOfMenuArrayInModuleDescriptor (bad value for key fk_menu)";
                    dol_syslog(get_class($this)."::insert_menus ".$this->error." ".$this->menu[$key]['fk_menu'], LOG_ERR);
                    $err++;
                }
            }
            $menu->type=$this->menu[$key]['type'];
            $menu->mainmenu=isset($this->menu[$key]['mainmenu'])?$this->menu[$key]['mainmenu']:(isset($menu->fk_mainmenu)?$menu->fk_mainmenu:'');
            $menu->leftmenu=isset($this->menu[$key]['leftmenu'])?$this->menu[$key]['leftmenu']:'';
            $menu->titre=$this->menu[$key]['titre'];
            $menu->url=$this->menu[$key]['url'];
            $menu->langs=$this->menu[$key]['langs'];
            $menu->position=$this->menu[$key]['position'];
            $menu->perms=$this->menu[$key]['perms'];
            $menu->target=$this->menu[$key]['target'];
            $menu->user=$this->menu[$key]['user'];
            $menu->enabled=isset($this->menu[$key]['enabled'])?$this->menu[$key]['enabled']:0;

            if (! $err)
            {
                $result=$menu->create($user);
                if ($result > 0)
                {
                    $this->menu[$key]['rowid']=$result;
                }
                else
                {
                    $this->error=$menu->error;
                    dol_syslog(get_class($this).'::insert_menus result='.$result." ".$this->error, LOG_ERR);
                    $err++;
                    break;
                }
            }
        }

        if (! $err)
        {
            $this->db->commit();
        }
        else
        {
            dol_syslog(get_class($this)."::insert_menus ".$this->error, LOG_ERR);
            $this->db->rollback();
        }

        return $err;
    }


    /**
     *  Remove menus entries
     *
     *  @return     int     Nb of errors (0 if OK)
     */
    function delete_menus()
    {
        global $conf;

        $err=0;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."menu";
        $sql.= " WHERE module = '".$this->db->escape($this->rights_class)."'";
        $sql.= " AND entity = ".$conf->entity;

        dol_syslog(get_class($this)."::delete_menus sql=".$sql);
        $resql=$this->db->query($sql);
        if (! $resql)
        {
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::delete_menus ".$this->error, LOG_ERR);
            $err++;
        }

        return $err;
    }

    /**
     *  Create directories required by module
     *
     *  @return     int     Nb of errors (0 if OK)
     */
    function create_dirs()
    {
        global $langs, $conf;

        $err=0;

        if (isset($this->dirs) && is_array($this->dirs))
        {
            foreach ($this->dirs as $key => $value)
            {
                $addtodatabase=0;

                if (! is_array($value)) $dir=$value;    // Default simple mode
                else {
                    $constname = $this->const_name."_DIR_";
                    $dir       = $this->dirs[$key][1];
                    $addtodatabase = empty($this->dirs[$key][2])?'':$this->dirs[$key][2]; // Create constante in llx_const
                    $subname   = empty($this->dirs[$key][3])?'':strtoupper($this->dirs[$key][3]); // Add submodule name (ex: $conf->module->submodule->dir_output)
                    $forcename = empty($this->dirs[$key][4])?'':strtoupper($this->dirs[$key][4]); // Change the module name if different

                    if (! empty($forcename)) $constname = 'MAIN_MODULE_'.$forcename."_DIR_";
                    if (! empty($subname))   $constname = $constname.$subname."_";

                    $name = $constname.strtoupper($this->dirs[$key][0]);
                }

                // Define directory full path ($dir must start with "/")
                if (empty($conf->global->MAIN_MODULE_MULTICOMPANY) || $conf->entity == 1) $fulldir = DOL_DATA_ROOT.$dir;
                else $fulldir = DOL_DATA_ROOT."/".$conf->entity.$dir;
                // Create dir if it does not exists
                if (! empty($fulldir) && ! file_exists($fulldir))
                {
                    if (dol_mkdir($fulldir, DOL_DATA_ROOT) < 0)
                    {
                        $this->error = $langs->trans("ErrorCanNotCreateDir",$fulldir);
                        dol_syslog(get_class($this)."::_init ".$this->error, LOG_ERR);
                        $err++;
                    }
                }

                // Define the constant in database if requested (not the default mode)
                if (! empty($addtodatabase))
                {
                    $result = $this->insert_dirs($name, $dir);
                    if ($result) $err++;
                }
            }
        }

        return $err;
    }


    /**
     *  Insert directories in llx_const
     *
     *  @param	string	$name		Name
     *  @param	string	$dir		Directory
     *  @return	int     			Nb of errors (0 if OK)
     */
    function insert_dirs($name,$dir)
    {
        global $conf;

        $err=0;

        $sql = "SELECT count(*)";
        $sql.= " FROM ".MAIN_DB_PREFIX."const";
        $sql.= " WHERE ".$this->db->decrypt('name')." = '".$name."'";
        $sql.= " AND entity = ".$conf->entity;

        dol_syslog(get_class($this)."::insert_dirs sql=".$sql);
        $result=$this->db->query($sql);
        if ($result)
        {
            $row = $this->db->fetch_row($result);

            if ($row[0] == 0)
            {
                $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,type,value,note,visible,entity)";
                $sql.= " VALUES (".$this->db->encrypt($name,1).",'chaine',".$this->db->encrypt($dir,1).",'Directory for module ".$this->name."','0',".$conf->entity.")";

                dol_syslog(get_class($this)."::insert_dirs sql=".$sql);
                $resql=$this->db->query($sql);
            }
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::insert_dirs ".$this->error, LOG_ERR);
            $err++;
        }

        return $err;
    }


    /**
     *  Remove directory entries
     *
     *  @return     int     Nb of errors (0 if OK)
     */
    function delete_dirs()
    {
        global $conf;

        $err=0;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
        $sql.= " WHERE ".$this->db->decrypt('name')." LIKE '".$this->const_name."_DIR_%'";
        $sql.= " AND entity = ".$conf->entity;

        dol_syslog(get_class($this)."::delete_dirs sql=".$sql);
        if (! $this->db->query($sql))
        {
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::delete_dirs ".$this->error, LOG_ERR);
            $err++;
        }

        return $err;
    }

    /**
     * Insert activation of generic parts from modules in llx_const
	 * Input entry use $this->module_parts = array(
	 *                        	'triggers' => 0,                                 // Set this to 1 if module has its own trigger directory (/mymodule/core/triggers)
	 *							'login' => 0,                                    // Set this to 1 if module has its own login method directory (/mymodule/core/login)
	 *							'substitutions' => 0,                            // Set this to 1 if module has its own substitution function file (/mymodule/core/substitutions)
	 *							'menus' => 0,                                    // Set this to 1 if module has its own menus handler directory (/mymodule/core/menus)
	 *							'theme' => 0,                                    // Set this to 1 if module has its own theme directory (/mymodule/theme)
	 *                        	'tpl' => 0,                                      // Set this to 1 if module overwrite template dir (/mymodule/core/tpl)
	 *							'barcode' => 0,                                  // Set this to 1 if module has its own barcode directory (/mymodule/core/modules/barcode)
	 *							'models' => 0,                                   // Set this to 1 if module has its own models directory (/mymodule/core/modules/xxx)
	 *							'css' => '/mymodule/css/mymodule.css.php',       // Set this to relative path of css file if module has its own css file
	 *							'js' => '/mymodule/js/mymodule.js',              // Set this to relative path of js file if module must load a js on all pages
	 *							'hooks' => array('hookcontext1','hookcontext2')  // Set here all hooks context managed by module
	 *							'workflow' => array('WORKFLOW_MODULE1_YOURACTIONTYPE_MODULE2'=>array('enabled'=>'! empty($conf->module1->enabled) && ! empty($conf->module2->enabled)', 'picto'=>'yourpicto@mymodule') // Set here all workflow context managed by module
	 * )
     *
     * @return     int     Nb of errors (0 if OK)
     */
    function insert_module_parts()
    {
    	global $conf;

    	$error=0;
    	$entity=$conf->entity;

    	if (is_array($this->module_parts) && ! empty($this->module_parts))
    	{
    		foreach($this->module_parts as $key => $value)
    		{
    			if (is_array($value) && count($value) == 0) continue;	// Discard empty arrays

    			$newvalue = $value;

    			// Serialize array parameters
    			if (is_array($value))
    			{
    				// Can defined other parameters
    				if (is_array($value['data']) && ! empty($value['data']))
    				{
    					$newvalue = json_encode($value['data']);
    					if (isset($value['entity'])) $entity = $value['entity'];
    				}
    				else
    				{
    					$newvalue = json_encode($value);
    				}
    			}

    			$sql = "INSERT INTO ".MAIN_DB_PREFIX."const (";
    			$sql.= "name";
    			$sql.= ", type";
    			$sql.= ", value";
    			$sql.= ", note";
    			$sql.= ", visible";
    			$sql.= ", entity";
    			$sql.= ")";
    			$sql.= " VALUES (";
    			$sql.= $this->db->encrypt($this->const_name."_".strtoupper($key), 1);
    			$sql.= ", 'chaine'";
    			$sql.= ", ".$this->db->encrypt($newvalue, 1);
    			$sql.= ", null";
    			$sql.= ", '0'";
    			$sql.= ", ".$entity;
    			$sql.= ")";

    			dol_syslog(get_class($this)."::insert_const_".$key." sql=".$sql);
    			$resql=$this->db->query($sql,1);
    			if (! $resql)
    			{
    			    if ($this->db->lasterrno() != 'DB_ERROR_RECORD_ALREADY_EXISTS')
    			    {
        			    $error++;
        				$this->error=$this->db->lasterror();
        				dol_syslog(get_class($this)."::insert_const_".$key." ".$this->error, LOG_ERR);
    			    }
    			    else
    			    {
    			        dol_syslog(get_class($this)."::insert_const_".$key." Record already exists.", LOG_WARNING);
    			    }
    			}
    		}
    	}
    	return $error;
    }

    /**
     * Remove activation of generic parts of modules from llx_const
     *
     * @return     int     Nb of errors (0 if OK)
     */
    function delete_module_parts()
    {
    	global $conf;

    	$err=0;
    	$entity=$conf->entity;

    	if (is_array($this->module_parts) && ! empty($this->module_parts))
    	{
    		foreach($this->module_parts as $key => $value)
    		{
    			// If entity is defined
    			if (is_array($value) && isset($value['entity'])) $entity = $value['entity'];

    			$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
    			$sql.= " WHERE ".$this->db->decrypt('name')." LIKE '".$this->const_name."_".strtoupper($key)."'";
    			$sql.= " AND entity = ".$entity;

    			dol_syslog(get_class($this)."::delete_const_".$key." sql=".$sql);
    			if (! $this->db->query($sql))
    			{
    				$this->error=$this->db->lasterror();
    				dol_syslog(get_class($this)."::delete_const_".$key." ".$this->error, LOG_ERR);
    				$err++;
    			}
    		}
    	}

    	return $err;
    }

}
?>
