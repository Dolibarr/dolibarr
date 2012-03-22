<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/core/lib/admin.lib.php
 *  \brief			Library of admin functions
 */


/**
 *  Renvoi une version en chaine depuis une version en tableau
 *
 *  @param		array		$versionarray		Tableau de version (vermajeur,vermineur,autre)
 *  @return     string        			      	Chaine version
 */
function versiontostring($versionarray)
{
    $string='?';
    if (isset($versionarray[0])) $string=$versionarray[0];
    if (isset($versionarray[1])) $string.='.'.$versionarray[1];
    if (isset($versionarray[2])) $string.='.'.$versionarray[2];
    return $string;
}

/**
 *	Compare 2 versions (stored into 2 arrays)
 *
 *	@param      array		$versionarray1      Array of version (vermajor,verminor,patch)
 *	@param      array		$versionarray2		Array of version (vermajor,verminor,patch)
 *	@return     int          			       	-4,-3,-2,-1 if versionarray1<versionarray2 (value depends on level of difference)
 * 												0 if same
 * 												1,2,3,4 if versionarray1>versionarray2 (value depends on level of difference)
 */
function versioncompare($versionarray1,$versionarray2)
{
    $ret=0;
    $level=0;
    $count1=count($versionarray1);
    $count2=count($versionarray2);
    $maxcount=max($count1,$count2);
    while ($level < $maxcount)
    {
        $operande1=isset($versionarray1[$level])?$versionarray1[$level]:0;
        $operande2=isset($versionarray2[$level])?$versionarray2[$level]:0;
        if (preg_match('/alpha|dev/i',$operande1)) $operande1=-3;
        if (preg_match('/alpha|dev/i',$operande2)) $operande2=-3;
        if (preg_match('/beta/i',$operande1)) $operande1=-2;
        if (preg_match('/beta/i',$operande2)) $operande2=-2;
        if (preg_match('/rc/i',$operande1)) $operande1=-1;
        if (preg_match('/rc/i',$operande2)) $operande2=-1;
        $level++;
        //print 'level '.$level.' '.$operande1.'-'.$operande2.'<br>';
        if ($operande1 < $operande2) { $ret = -$level; break; }
        if ($operande1 > $operande2) { $ret = $level; break; }
    }
    //print join('.',$versionarray1).'('.count($versionarray1).') / '.join('.',$versionarray2).'('.count($versionarray2).') => '.$ret;
    return $ret;
}


/**
 *	Return version PHP
 *
 *	@return     array               Tableau de version (vermajeur,vermineur,autre)
 */
function versionphparray()
{
    return explode('.',PHP_VERSION);
}

/**
 *	Return version Dolibarr
 *
 *	@return     array               Tableau de version (vermajeur,vermineur,autre)
 */
function versiondolibarrarray()
{
    return explode('.',DOL_VERSION);
}


/**
 *	Launch a sql file. Function used by:
 *  - Migrate process (dolibarr-xyz-abc.sql)
 *  - Loading sql menus (auguria)
 *  - Running specific Sql by a module init
 *  Install process however does not use it.
 *  Note that Sql files must have all comments at start of line.
 *
 *	@param		string	$sqlfile		Full path to sql file
 * 	@param		int		$silent			1=Do not output anything, 0=Output line for update page
 * 	@param		int		$entity			Entity targeted for multicompany module
 *	@param		int		$usesavepoint	1=Run a savepoint before each request and a rollback to savepoint if error (this allow to have some request with errors inside global transactions).
 *	@param		string	$handler		Handler targeted for menu
 *	@param 		string	$okerror		Family of errors we accept ('default', 'none')
 * 	@return		int						<=0 if KO, >0 if OK
 */
function run_sql($sqlfile,$silent=1,$entity='',$usesavepoint=1,$handler='',$okerror='default')
{
    global $db, $conf, $langs, $user;

    dol_syslog("Admin.lib::run_sql run sql file ".$sqlfile." silent=".$silent." entity=".$entity." usesavepoint=".$usesavepoint." handler=".$handler." okerror=".$okerror, LOG_DEBUG);

    $ok=0;
    $error=0;
    $i=0;
    $buffer = '';
    $arraysql = Array();

    // Get version of database
    $versionarray=$db->getVersionArray();

    $fp = fopen($sqlfile,"r");
    if ($fp)
    {
        while (! feof($fp))
        {
            $buf = fgets($fp, 4096);

            // Test if request must be ran only for particular database or version (if yes, we must remove the -- comment)
            if (preg_match('/^--\sV(MYSQL|PGSQL|)([0-9\.]+)/i',$buf,$reg))
            {
            	$qualified=1;

            	// restrict on database type
            	if (! empty($reg[1]))
            	{
            		if (! preg_match('/'.preg_quote($reg[1]).'/i',$db->type)) $qualified=0;
            	}

            	// restrict on version
            	if ($qualified)
            	{

	                $versionrequest=explode('.',$reg[2]);
	                //print var_dump($versionrequest);
	                //print var_dump($versionarray);
	                if (! count($versionrequest) || ! count($versionarray) || versioncompare($versionrequest,$versionarray) > 0)
	                {
	                	$qualified=0;
	                }
            	}

                if ($qualified)
                {
                    // Version qualified, delete SQL comments
                    $buf=preg_replace('/^--\sV(MYSQL|PGSQL|)([0-9\.]+)/i','',$buf);
                    //print "Ligne $i qualifi?e par version: ".$buf.'<br>';
                }
            }

            // Add line buf to buffer if not a comment
            if (! preg_match('/^--/',$buf))
            {
                $buf=preg_replace('/--.*$/','',$buf); //remove comment from a line that not start with -- before add it to the buffer
                $buffer .= trim($buf);
            }

            //          print $buf.'<br>';

            if (preg_match('/;/',$buffer))	// If string contains ';', it's end of a request string, we save it in arraysql.
            {
                // Found new request
                if ($buffer) $arraysql[$i]=$buffer;
                $i++;
                $buffer='';
            }
        }

        if ($buffer) $arraysql[$i]=$buffer;
        fclose($fp);
    }
    else
    {
        dol_syslog("Admin.lib::run_sql failed to open file ".$sqlfile, LOG_ERR);
    }

    // Loop on each request to see if there is a __+MAX_table__ key
    $listofmaxrowid=array();	// This is a cache table
    foreach($arraysql as $i => $sql)
    {
        $newsql=$sql;

        // Replace __+MAX_table__ with max of table
        while (preg_match('/__\+MAX_([A-Za-z_]+)__/i',$newsql,$reg))
        {
            $table=$reg[1];
            if (! isset($listofmaxrowid[$table]))
            {
                //var_dump($db);
                $sqlgetrowid='SELECT MAX(rowid) as max from '.$table;
                $resql=$db->query($sqlgetrowid);
                if ($resql)
                {
                    $obj=$db->fetch_object($resql);
                    $listofmaxrowid[$table]=$obj->max;
                    if (empty($listofmaxrowid[$table])) $listofmaxrowid[$table]=0;
                }
                else
                {
                    dol_syslog('Admin.lib::run_sql Failed to get max rowid for '.$table.' '.$db->lasterror().' sql='.$sqlgetrowid, LOG_ERR);
                    if (! $silent) print '<tr><td valign="top" colspan="2">';
                    if (! $silent) print '<div class="error">'.$langs->trans("Failed to get max rowid for ".$table)."</div></td>";
                    if (! $silent) print '</tr>';
                    $error++;
                    break;
                }
            }
            $from='__+MAX_'.$table.'__';
            $to='+'.$listofmaxrowid[$table];
            $newsql=str_replace($from,$to,$newsql);
            dol_syslog('Admin.lib::run_sql New Request '.($i+1).' (replacing '.$from.' to '.$to.') sql='.$newsql, LOG_DEBUG);

            $arraysql[$i]=$newsql;
        }
    }

    // Loop on each request to execute request
    $cursorinsert=0;
    $listofinsertedrowid=array();
    foreach($arraysql as $i => $sql)
    {
        if ($sql)
        {
        	// Replace the prefix tables
        	if (MAIN_DB_PREFIX != 'llx_')
        	{
        		$sql=preg_replace('/llx_/i',MAIN_DB_PREFIX,$sql);
        	}

            if (!empty($handler)) $sql=preg_replace('/__HANDLER__/i',"'".$handler."'",$sql);

            $newsql=preg_replace('/__ENTITY__/i',(!empty($entity)?$entity:$conf->entity),$sql);

            // Ajout trace sur requete (eventuellement a commenter si beaucoup de requetes)
            if (! $silent) print '<tr><td valign="top">'.$langs->trans("Request").' '.($i+1)." sql='".$newsql."'</td></tr>\n";
            dol_syslog('Admin.lib::run_sql Request '.($i+1).' sql='.$newsql, LOG_DEBUG);

            // Replace for encrypt data
            if (preg_match_all('/__ENCRYPT\(\'([^\,]+)\'\)__/i',$newsql,$reg))
            {
                $num=count($reg[0]);

                for($i=0;$i<$num;$i++)
                {
                    $from 	= $reg[0][$i];
                    $to		= $db->encrypt($reg[1][$i],1);
                    $newsql	= str_replace($from,$to,$newsql);
                }
            }

            // Replace for decrypt data
            if (preg_match_all('/__DECRYPT\(\'([^\,]+)\'\)__/i',$newsql,$reg))
            {
                $num=count($reg[0]);

                for($i=0;$i<$num;$i++)
                {
                    $from 	= $reg[0][$i];
                    $to		= $db->decrypt($reg[1][$i]);
                    $newsql	= str_replace($from,$to,$newsql);
                }
            }

            // Replace __x__ with rowid of insert nb x
            while (preg_match('/__([0-9]+)__/',$newsql,$reg))
            {
                $cursor=$reg[1];
                if (empty($listofinsertedrowid[$cursor]))
                {
                    if (! $silent) print '<tr><td valign="top" colspan="2">';
                    if (! $silent) print '<div class="error">'.$langs->trans("FileIsNotCorrect")."</div></td>";
                    if (! $silent) print '</tr>';
                    $error++;
                    break;
                }
                $from='__'.$cursor.'__';
                $to=$listofinsertedrowid[$cursor];
                $newsql=str_replace($from,$to,$newsql);
                dol_syslog('Admin.lib::run_sql New Request '.($i+1).' (replacing '.$from.' to '.$to.') sql='.$newsql, LOG_DEBUG);
            }

            $result=$db->query($newsql,$usesavepoint);
            if ($result)
            {
                if (! $silent) print '<!-- Result = OK -->'."\n";

                if (preg_replace('/insert into ([^\s]+)/i',$newsql,$reg))
                {
                    $cursorinsert++;

                    // It's an insert
                    $table=preg_replace('/([^a-zA-Z_]+)/i','',$reg[1]);
                    $insertedrowid=$db->last_insert_id($table);
                    $listofinsertedrowid[$cursorinsert]=$insertedrowid;
                    dol_syslog('Admin.lib::run_sql Insert nb '.$cursorinsert.', done in table '.$table.', rowid is '.$listofinsertedrowid[$cursorinsert], LOG_DEBUG);
                }
                // 	          print '<td align="right">OK</td>';
            }
            else
            {
                $errno=$db->errno();
                if (! $silent) print '<!-- Result = '.$errno.' -->'."\n";

				// Define list of errors we accept (array $okerrors)
            	$okerrors=array(	// By default
					'DB_ERROR_TABLE_ALREADY_EXISTS',
					'DB_ERROR_COLUMN_ALREADY_EXISTS',
					'DB_ERROR_KEY_NAME_ALREADY_EXISTS',
					'DB_ERROR_TABLE_OR_KEY_ALREADY_EXISTS',		// PgSql use same code for table and key already exist
					'DB_ERROR_RECORD_ALREADY_EXISTS',
					'DB_ERROR_NOSUCHTABLE',
					'DB_ERROR_NOSUCHFIELD',
					'DB_ERROR_NO_FOREIGN_KEY_TO_DROP',
					'DB_ERROR_NO_INDEX_TO_DROP',
					'DB_ERROR_CANNOT_CREATE',    		// Qd contrainte deja existante
					'DB_ERROR_CANT_DROP_PRIMARY_KEY',
					'DB_ERROR_PRIMARY_KEY_ALREADY_EXISTS'
				);
                if ($okerror == 'none') $okerrors=array();

                // Is it an error we accept
				if (! in_array($errno,$okerrors))
				{
				    if (! $silent) print '<tr><td valign="top" colspan="2">';
				    if (! $silent) print '<div class="error">'.$langs->trans("Error")." ".$db->errno().": ".$newsql."<br>".$db->error()."</div></td>";
				    if (! $silent) print '</tr>'."\n";
				    dol_syslog('Admin.lib::run_sql Request '.($i+1)." Error ".$db->errno()." ".$newsql."<br>".$db->error(), LOG_ERR);
				    $error++;
				}
            }

            if (! $silent) print '</tr>'."\n";
        }
    }

    if ($error == 0)
    {
        if (! $silent) print '<tr><td>'.$langs->trans("ProcessMigrateScript").'</td>';
        if (! $silent) print '<td align="right">'.$langs->trans("OK").'</td></tr>'."\n";
        $ok = 1;
    }
    else
    {
        if (! $silent) print '<tr><td>'.$langs->trans("ProcessMigrateScript").'</td>';
        if (! $silent) print '<td align="right"><font class="error">'.$langs->trans("KO").'</font></td></tr>'."\n";
        $ok = 0;
    }

    return $ok;
}


/**
 *	Effacement d'une constante dans la base de donnees
 *
 *	@param	    DoliDB		$db         Database handler
 *	@param	    string		$name		Name of constant or rowid of line
 *	@param	    int			$entity		Multi company id, -1 for all entities
 *	@return     int         			<0 if KO, >0 if OK
 *
 *	@see		dolibarr_get_const, dolibarr_set_const, dol_set_user_param
 */
function dolibarr_del_const($db, $name, $entity=1)
{
    global $conf;

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
    $sql.= " WHERE (".$db->decrypt('name')." = '".$db->escape($name)."'";
    if (is_numeric($name)) $sql.= " OR rowid = '".$db->escape($name)."'";
    $sql.= ")";
    if ($entity >= 0) $sql.= " AND entity = ".$entity;

    dol_syslog("admin.lib::dolibarr_del_const sql=".$sql);
    $resql=$db->query($sql);
    if ($resql)
    {
        $conf->global->$name='';
        return 1;
    }
    else
    {
        dol_print_error($db);
        return -1;
    }
}

/**
 *	Recupere une constante depuis la base de donnees.
 *
 *	@param	    DoliDB		$db         Database handler
 *	@param	    string		$name		Nom de la constante
 *	@param	    int			$entity		Multi company id
 *	@return     string      			Valeur de la constante
 *
 *	@see		dolibarr_del_const, dolibarr_set_const, dol_set_user_param
 */
function dolibarr_get_const($db, $name, $entity=1)
{
    global $conf;
    $value='';

    $sql = "SELECT ".$db->decrypt('value')." as value";
    $sql.= " FROM ".MAIN_DB_PREFIX."const";
    $sql.= " WHERE name = ".$db->encrypt($name,1);
    $sql.= " AND entity = ".$entity;

    dol_syslog("admin.lib::dolibarr_get_const sql=".$sql);
    $resql=$db->query($sql);
    if ($resql)
    {
        $obj=$db->fetch_object($resql);
        if ($obj) $value=$obj->value;
    }
    return $value;
}


/**
 *	Insert a parameter (key,value) into database.
 *
 *	@param	    DoliDB		$db         Database handler
 *	@param	    string		$name		Name of constant
 *	@param	    string		$value		Value of constant
 *	@param	    string		$type		Type of constante (chaine par defaut)
 *	@param	    int			$visible	Is constant visible in Setup->Other page (0 by default)
 *	@param	    string		$note		Note on parameter
 *	@param	    int			$entity		Multi company id (0 means all entities)
 *	@return     int         			-1 if KO, 1 if OK
 *
 *	@see		dolibarr_del_const, dolibarr_get_const, dol_set_user_param
 */
function dolibarr_set_const($db, $name, $value, $type='chaine', $visible=0, $note='', $entity=1)
{
    global $conf;

    // Clean parameters
    $name=trim($name);

    // Check parameters
    if (empty($name))
    {
        dol_print_error($db,"Error: Call to function dolibarr_set_const with wrong parameters", LOG_ERR);
        exit;
    }

    //dol_syslog("dolibarr_set_const name=$name, value=$value type=$type, visible=$visible, note=$note entity=$entity");

    $db->begin();

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
    $sql.= " WHERE name = ".$db->encrypt($name,1);
    if ($entity >= 0) $sql.= " AND entity = ".$entity;

    dol_syslog("admin.lib::dolibarr_set_const sql=".$sql, LOG_DEBUG);
    $resql=$db->query($sql);

    if (strcmp($value,''))	// true if different. Must work for $value='0' or $value=0
    {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."const(name,value,type,visible,note,entity)";
        $sql.= " VALUES (";
        $sql.= $db->encrypt($name,1);
        $sql.= ", ".$db->encrypt($value,1);
        $sql.= ",'".$type."',".$visible.",'".$db->escape($note)."',".$entity.")";

        //print "sql".$value."-".pg_escape_string($value)."-".$sql;exit;
        //print "xx".$db->escape($value);
        //print $sql;exit;
        dol_syslog("admin.lib::dolibarr_set_const sql=".$sql, LOG_DEBUG);
        $resql=$db->query($sql);
    }

    if ($resql)
    {
        $db->commit();
        $conf->global->$name=$value;
        return 1;
    }
    else
    {
        $error=$db->lasterror();
        dol_syslog("admin.lib::dolibarr_set_const ".$error, LOG_ERR);
        $db->rollback();
        return -1;
    }
}


/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to shoc
 */
function security_prepare_head()
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT."/admin/proxy.php";
    $head[$h][1] = $langs->trans("ExternalAccess");
    $head[$h][2] = 'proxy';
    $h++;

    $head[$h][0] = DOL_URL_ROOT."/admin/security_other.php";
    $head[$h][1] = $langs->trans("Miscellanous");
    $head[$h][2] = 'misc';
    $h++;

    $head[$h][0] = DOL_URL_ROOT."/admin/security.php";
    $head[$h][1] = $langs->trans("Passwords");
    $head[$h][2] = 'passwords';
    $h++;

    $head[$h][0] = DOL_URL_ROOT."/admin/events.php";
    $head[$h][1] = $langs->trans("Audit");
    $head[$h][2] = 'audit';
    $h++;

    $head[$h][0] = DOL_URL_ROOT."/admin/perms.php";
    $head[$h][1] = $langs->trans("DefaultRights");
    $head[$h][2] = 'default';
    $h++;

    return $head;
}


/**
 * 	Return list of session
 *
 *	@return		array			Array list of sessions
 */
function listOfSessions()
{
    global $conf;

    $arrayofSessions = array();
    $sessPath = ini_get("session.save_path").'/';
    dol_syslog('admin.lib:listOfSessions sessPath='.$sessPath);

    $dh = @opendir(dol_osencode($sessPath));
    if ($dh)
    {
        while(($file = @readdir($dh)) !== false)
        {
            if (preg_match('/^sess_/i',$file) && $file != "." && $file != "..")
            {
                $fullpath = $sessPath.$file;
                if(! @is_dir($fullpath) && is_readable($fullpath))
                {
                    $sessValues = file_get_contents($fullpath);	// get raw session data

                    if (preg_match('/dol_login/i',$sessValues) && // limit to dolibarr session
                    preg_match('/dol_entity\|s:([0-9]+):"('.$conf->entity.')"/i',$sessValues) && // limit to current entity
                    preg_match('/dol_company\|s:([0-9]+):"('.$conf->global->MAIN_INFO_SOCIETE_NOM.')"/i',$sessValues)) // limit to company name
                    {
                        $tmp=explode('_', $file);
                        $idsess=$tmp[1];
                        $login = preg_match('/dol_login\|s:[0-9]+:"([A-Za-z0-9]+)"/i',$sessValues,$regs);
                        $arrayofSessions[$idsess]["login"] = $regs[1];
                        $arrayofSessions[$idsess]["age"] = time()-filectime($fullpath);
                        $arrayofSessions[$idsess]["creation"] = filectime($fullpath);
                        $arrayofSessions[$idsess]["modification"] = filemtime($fullpath);
                        $arrayofSessions[$idsess]["raw"] = $sessValues;
                    }
                }
            }
        }
        @closedir($dh);
    }

    return $arrayofSessions;
}

/**
 * 	Purge existing sessions
 *
 * 	@param		int		$mysessionid		To avoid to try to delete my own session
 * 	@return		int							>0 if OK, <0 if KO
 */
function purgeSessions($mysessionid)
{
    global $conf;

    $arrayofSessions = array();
    $sessPath = ini_get("session.save_path")."/";
    dol_syslog('admin.lib:purgeSessions mysessionid='.$mysessionid.' sessPath='.$sessPath);

    $error=0;
    $dh = @opendir(dol_osencode($sessPath));
    while(($file = @readdir($dh)) !== false)
    {
        if ($file != "." && $file != "..")
        {
            $fullpath = $sessPath.$file;
            if(! @is_dir($fullpath))
            {
                $sessValues = file_get_contents($fullpath);	// get raw session data

                if (preg_match('/dol_login/i',$sessValues) && // limit to dolibarr session
                preg_match('/dol_entity\|s:([0-9]+):"('.$conf->entity.')"/i',$sessValues) && // limit to current entity
                preg_match('/dol_company\|s:([0-9]+):"('.$conf->global->MAIN_INFO_SOCIETE_NOM.')"/i',$sessValues)) // limit to company name
                {
                    $tmp=explode('_', $file);
                    $idsess=$tmp[1];
                    // We remove session if it's not ourself
                    if ($idsess != $mysessionid)
                    {
                        $res=@unlink($fullpath);
                        if (! $res) $error++;
                    }
                }
            }
        }
    }
    @closedir($dh);

    if (! $error) return 1;
    else return -$error;
}



/**
 *  Enable a module
 *
 *  @param      string		$value      Name of module to activate
 *  @param      int			$withdeps   Activate/Disable also all dependencies
 *  @return     string      			Error message or '';
 */
function activateModule($value,$withdeps=1)
{
    global $db, $modules, $langs, $conf;

    // Check parameters
    if (empty($value)) return 'ErrorBadParameter';

    $ret='';
    $modName = $value;
    $modFile = $modName . ".class.php";

    // Loop on each directory to fill $modulesdir
    $modulesdir = array();
    foreach ($conf->file->dol_document_root as $type => $dirroot)
    {
        $modulesdir[] = $dirroot."/core/modules/";

            $handle=@opendir(dol_osencode($dirroot));
            if (is_resource($handle))
            {
                while (($file = readdir($handle))!==false)
                {
                    if (is_dir($dirroot.'/'.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS' && $file != 'includes')
                    {
                        if (is_dir($dirroot . '/' . $file . '/core/modules/'))
                        {
                            $modulesdir[] = $dirroot . '/' . $file . '/core/modules/';
                        }
                    }
                }
                closedir($handle);
            }
    }

    // Loop on each directory
    $found=false;
    foreach ($modulesdir as $dir)
    {
        if (file_exists($dir.$modFile))
        {
            $found=@include_once($dir.$modFile);
            if ($found) break;
        }
    }

    $objMod = new $modName($db);

    // Test if PHP version ok
    $verphp=versionphparray();
    $vermin=isset($objMod->phpmin)?$objMod->phpmin:0;
    if (is_array($vermin) && versioncompare($verphp,$vermin) < 0)
    {
        return $langs->trans("ErrorModuleRequirePHPVersion",versiontostring($vermin));
    }

    // Test if Dolibarr version ok
    $verdol=versiondolibarrarray();
    $vermin=isset($objMod->need_dolibarr_version)?$objMod->need_dolibarr_version:0;
    //print 'eee'.versioncompare($verdol,$vermin).join(',',$verdol).' - '.join(',',$vermin);exit;
    if (is_array($vermin) && versioncompare($verdol,$vermin) < 0)
    {
        return $langs->trans("ErrorModuleRequireDolibarrVersion",versiontostring($vermin));
    }

    // Test if javascript requirement ok
    if (! empty($objMod->need_javascript_ajax) && empty($conf->use_javascript_ajax))
    {
        return $langs->trans("ErrorModuleRequireJavascript");
    }

    $result=$objMod->init();
    if ($result <= 0) $ret=$objMod->error;

    if (! $ret && $withdeps)
    {
        if (is_array($objMod->depends) && !empty($objMod->depends))
        {
            // Activation des modules dont le module depend
            $num = count($objMod->depends);
            for ($i = 0; $i < $num; $i++)
            {
                if (file_exists(DOL_DOCUMENT_ROOT."/core/modules/".$objMod->depends[$i].".class.php"))
                {
                    activateModule($objMod->depends[$i]);
                }
            }
        }

        if (isset($objMod->conflictwith) && is_array($objMod->conflictwith))
        {
            // Desactivation des modules qui entrent en conflit
            $num = count($objMod->conflictwith);
            for ($i = 0; $i < $num; $i++)
            {
                if (file_exists(DOL_DOCUMENT_ROOT."/core/modules/".$objMod->conflictwith[$i].".class.php"))
                {
                    unActivateModule($objMod->conflictwith[$i],0);
                }
            }
        }
    }

    return $ret;
}


/**
 *  Disable a module
 *
 *  @param      string		$value               Nom du module a desactiver
 *  @param      int			$requiredby          1=Desactive aussi modules dependants
 *  @return     string     				         Error message or '';
 */
function unActivateModule($value, $requiredby=1)
{
    global $db, $modules, $conf;

    // Check parameters
    if (empty($value)) return 'ErrorBadParameter';

    $ret='';
    $modName = $value;
    $modFile = $modName . ".class.php";

    // Loop on each directory to fill $modulesdir
    $modulesdir = array();
    foreach ($conf->file->dol_document_root as $type => $dirroot)
    {
        $modulesdir[] = $dirroot."/core/modules/";

            $handle=@opendir(dol_osencode($dirroot));
            if (is_resource($handle))
            {
                while (($file = readdir($handle))!==false)
                {
                    if (is_dir($dirroot.'/'.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS' && $file != 'includes')
                    {
                        if (is_dir($dirroot . '/' . $file . '/core/modules/'))
                        {
                            $modulesdir[] = $dirroot . '/' . $file . '/core/modules/';
                        }
                    }
                }
                closedir($handle);
            }
    }

    // Loop on each directory
    $found=false;
    foreach ($modulesdir as $dir)
    {
        if (file_exists($dir.$modFile))
        {
            $found=@include_once($dir.$modFile);
            if ($found) break;
        }
    }

    if ($found)
    {
        $objMod = new $modName($db);
        $result=$objMod->remove();
    }
    else
    {
        // TODO Cannot instantiate abstract class
    	//$genericMod = new DolibarrModul($db);
        //$genericMod->name=preg_replace('/^mod/i','',$modName);
        //$genericMod->rights_class=strtolower(preg_replace('/^mod/i','',$modName));
        //$genericMod->const_name='MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i','',$modName));
        dol_syslog("modules::unActivateModule Failed to find module file, we use generic function with name " . $modName);
        //$genericMod->_remove();
    }

    // Desactivation des modules qui dependent de lui
    if ($requiredby)
    {
        $countrb=count($objMod->requiredby);
        for ($i = 0; $i < $countrb; $i++)
        {
            unActivateModule($objMod->requiredby[$i]);
        }
    }

    return $ret;
}


/**
 *  Add external modules to list of dictionnaries
 *
 * 	@param		array		&$taborder			Taborder
 * 	@param		array		&$tabname			Tabname
 * 	@param		array		&$tablib			Tablib
 * 	@param		array		&$tabsql			Tabsql
 * 	@param		array		&$tabsqlsort		Tabsqlsort
 * 	@param		array		&$tabfield			Tabfield
 * 	@param		array		&$tabfieldvalue		Tabfieldvalue
 * 	@param		array		&$tabfieldinsert	Tabfieldinsert
 * 	@param		array		&$tabrowid			Tabrowid
 * 	@param		array		&$tabcond			Tabcond
 * 	@return		int			1
 */
function complete_dictionnary_with_modules(&$taborder,&$tabname,&$tablib,&$tabsql,&$tabsqlsort,&$tabfield,&$tabfieldvalue,&$tabfieldinsert,&$tabrowid,&$tabcond)
{
    global $db, $modules, $conf, $langs;

    // Search modules
    $filename = array();
    $modules = array();
    $orders = array();
    $categ = array();
    $dirmod = array();
    $modulesdir = array();
    $i = 0; // is a sequencer of modules found
    $j = 0; // j is module number. Automatically affected if module number not defined.

    foreach ($conf->file->dol_document_root as $type => $dirroot)
    {
        $modulesdir[$dirroot . '/core/modules/'] = $dirroot . '/core/modules/';

        $handle=@opendir($dirroot);
        if (is_resource($handle))
        {
            while (($file = readdir($handle))!==false)
            {
                if (is_dir($dirroot.'/'.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS' && $file != 'includes')
                {
                    if (is_dir($dirroot . '/' . $file . '/core/modules/'))
                    {
                        $modulesdir[$dirroot . '/' . $file . '/core/modules/'] = $dirroot . '/' . $file . '/core/modules/';
                    }
                }
            }
            closedir($handle);
        }
    }
    //var_dump($modulesdir);

    foreach ($modulesdir as $dir)
    {
    	// Load modules attributes in arrays (name, numero, orders) from dir directory
    	//print $dir."\n<br>";
    	dol_syslog("Scan directory ".$dir." for modules");
        $handle=@opendir(dol_osencode($dir));
        if (is_resource($handle))
        {
            while (($file = readdir($handle))!==false)
            {
                //print "$i ".$file."\n<br>";
                if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod'  && substr($file, dol_strlen($file) - 10) == '.class.php')
                {
                    $modName = substr($file, 0, dol_strlen($file) - 10);

                    if ($modName)
                    {
                        include_once($dir.$file);
                        $objMod = new $modName($db);

                        if ($objMod->numero > 0)
                        {
                            $j = $objMod->numero;
                        }
                        else
                        {
                            $j = 1000 + $i;
                        }

                        $modulequalified=1;

                        // We discard modules according to features level (PS: if module is activated we always show it)
                        $const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i','',get_class($objMod)));
                        if ($objMod->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2 && ! $conf->global->$const_name) $modulequalified=0;
                        if ($objMod->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1 && ! $conf->global->$const_name) $modulequalified=0;

                        if ($modulequalified)
                        {
							// Load languages files of module
                            if (isset($objMod->langfiles) && is_array($objMod->langfiles))
                            {
                             	foreach($objMod->langfiles as $langfile)
                              	{
                               		$langs->load($langfile);
                               	}
                           	}

                            $modules[$i] = $objMod;
                            $filename[$i]= $modName;
                            $orders[$i]  = $objMod->family."_".$j;   // Tri par famille puis numero module
                            //print "x".$modName." ".$orders[$i]."\n<br>";
                            if (isset($categ[$objMod->special])) $categ[$objMod->special]++;                    // Array of all different modules categories
                            else $categ[$objMod->special]=1;
                            $dirmod[$i] = $dirroot;

                            // Complete arrays
                            //&$tabname,&$tablib,&$tabsql,&$tabsqlsort,&$tabfield,&$tabfieldvalue,&$tabfieldinsert,&$tabrowid,&$tabcond
                            //$objMod
                            if (! empty($objMod->dictionnaries))
                            {
                                //var_dump($objMod->dictionnaries['tabname']);
                                $taborder[] = 0;
                                foreach($objMod->dictionnaries['tabname'] as $val)
                                {
                                    $taborder[] = count($tabname)+1;
                                    $tabname[] = $val;
                                }
                                foreach($objMod->dictionnaries['tablib'] as $val) $tablib[] = $val;
                                foreach($objMod->dictionnaries['tabsql'] as $val) $tabsql[] = $val;
                                foreach($objMod->dictionnaries['tabsqlsort'] as $val) $tabsqlsort[] = $val;
                                foreach($objMod->dictionnaries['tabfield'] as $val) $tabfield[] = $val;
                                foreach($objMod->dictionnaries['tabfieldvalue'] as $val) $tabfieldvalue[] = $val;
                                foreach($objMod->dictionnaries['tabfieldinsert'] as $val) $tabfieldinsert[] = $val;
                                foreach($objMod->dictionnaries['tabrowid'] as $val) $tabrowid[] = $val;
                                foreach($objMod->dictionnaries['tabcond'] as $val) $tabcond[] = $val;
                                //                                foreach($objMod->dictionnaries['tabsqlsort'] as $val) $tablib[] = $val;
                                //$tabname = array_merge ($tabname, $objMod->dictionnaries['tabname']);
                                //var_dump($tabcond);
                                //exit;
                            }

                            $j++;
                            $i++;
                        }
                        else dol_syslog("Module ".get_class($objMod)." not qualified");
                    }
                }
            }
            closedir($handle);
        }
        else
        {
            dol_syslog("htdocs/admin/modules.php: Failed to open directory ".$dir.". See permission and open_basedir option.", LOG_WARNING);
        }
    }

    return 1;
}


/**
 *	Show array with constants to edit
 *
 *	@param	array	$tableau		Array of constants
 *	@return	void
 */
function form_constantes($tableau)
{
    global $db,$bc,$langs,$conf,$_Avery_Labels;

    $form = new Form($db);

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Description").'</td>';
    print '<td>'.$langs->trans("Value").'*</td>';
    print '<td>&nbsp;</td>';
    print '<td align="center" width="80">'.$langs->trans("Action").'</td>';
    print "</tr>\n";
    $var=true;

    $listofparam=array();
    foreach($tableau as $const)	// Loop on each param
    {
        $sql = "SELECT ";
        $sql.= "rowid";
        $sql.= ", ".$db->decrypt('name')." as name";
        $sql.= ", ".$db->decrypt('value')." as value";
        $sql.= ", type";
        $sql.= ", note";
        $sql.= " FROM ".MAIN_DB_PREFIX."const";
        $sql.= " WHERE ".$db->decrypt('name')." = '".$const."'";
        $sql.= " AND entity in (0, ".$conf->entity.")";
        $sql.= " ORDER BY name ASC, entity DESC";
        $result = $db->query($sql);

        dol_syslog("List params sql=".$sql);
        if ($result)
        {
            $obj = $db->fetch_object($result);	// Take first result of select
            $var=!$var;

            print "\n".'<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';

            print "<tr ".$bc[$var].">";

            // Affiche nom constante
            print '<td>';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="rowid" value="'.$obj->rowid.'">';
            print '<input type="hidden" name="constname" value="'.$const.'">';
            print '<input type="hidden" name="constnote" value="'.nl2br($obj->note).'">';

            print $langs->trans("Desc".$const) != ("Desc".$const) ? $langs->trans("Desc".$const) : ($obj->note?$obj->note:$const);

            if ($const=='ADHERENT_MAILMAN_URL')
            {
                print '. '.$langs->trans("Example").': <a href="#" id="exampleclick1">'.img_down().'</a><br>';
                //print 'http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%MAILMAN_ADMINPW%&subscribees=%EMAIL%&send_welcome_msg_to_this_batch=1';
                print '<div id="example1" class="hidden">';
                print 'http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members/add?subscribees_upload=%EMAIL%&amp;adminpw=%MAILMAN_ADMINPW%&amp;subscribe_or_invite=0&amp;send_welcome_msg_to_this_batch=0&amp;notification_to_list_owner=0';
                print '</div>';
            }
            if ($const=='ADHERENT_MAILMAN_UNSUB_URL')
            {
                print '. '.$langs->trans("Example").': <a href="#" id="exampleclick2">'.img_down().'</a><br>';
                print '<div id="example2" class="hidden">';
                print 'http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members/remove?unsubscribees_upload=%EMAIL%&amp;adminpw=%MAILMAN_ADMINPW%&amp;send_unsub_ack_to_this_batch=0&amp;send_unsub_notifications_to_list_owner=0';
                print '</div>';
                //print 'http://lists.domain.com/cgi-bin/mailman/admin/%LISTE%/members/remove?adminpw=%MAILMAN_ADMINPW%&unsubscribees=%EMAIL%';
            }


            print "</td>\n";

            if ($const == 'ADHERENT_CARD_TYPE' || $const == 'ADHERENT_ETIQUETTE_TYPE')
            {
                print '<td>';
                // List of possible labels (defined into $_Avery_Labels variable set into format_cards.lib.php)
                require_once(DOL_DOCUMENT_ROOT.'/core/lib/format_cards.lib.php');
                $arrayoflabels=array();
                foreach(array_keys($_Avery_Labels) as $codecards)
                {
                    $arrayoflabels[$codecards]=$_Avery_Labels[$codecards]['name'];
                }
                print $form->selectarray('constvalue',$arrayoflabels,($obj->value?$obj->value:'CARD'),1,0,0);
                print '</td><td>';
                print '<input type="hidden" name="consttype" value="yesno">';
                print '</td>';
            }
            else
            {
                print '<td>';
                //print 'aa'.$const;
                if (in_array($const,array('ADHERENT_CARD_TEXT','ADHERENT_CARD_TEXT_RIGHT')))
                {
                    print '<textarea class="flat" name="constvalue" cols="35" rows="5" wrap="soft">'."\n";
                    print $obj->value;
                    print "</textarea>\n";
                    print '</td><td>';
                    print '<input type="hidden" name="consttype" value="texte">';
                }
                else if (in_array($const,array('ADHERENT_AUTOREGISTER_MAIL','ADHERENT_MAIL_VALID','ADHERENT_MAIL_COTIS','ADHERENT_MAIL_RESIL')))
                {
                    require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
                    $doleditor=new DolEditor('constvalue'.$const,$obj->value,'',160,'dolibarr_notes','',false,false,$conf->fckeditor->enabled,5,60);
                    $doleditor->Create();

                    print '</td><td>';
                    print '<input type="hidden" name="consttype" value="texte">';
                }
                else if ($obj->type == 'yesno')
                {
                    print $form->selectyesno('constvalue',$obj->value,1);
                    print '</td><td>';
                    print '<input type="hidden" name="consttype" value="yesno">';
                }
                else
                {
                    print '<input type="text" class="flat" size="48" name="constvalue" value="'.$obj->value.'">';
                    print '</td><td>';
                    print '<input type="hidden" name="consttype" value="chaine">';
                }
                print '</td>';
            }
            print '<td align="center">';
            print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button"> &nbsp;';
            // print '<a href="adherent.php?name='.$const.'&action=unset">'.img_delete().'</a>';
            print "</td>";
            print "</tr>\n";
            print "</form>\n";
        }
    }
    print '</table>';
}

?>