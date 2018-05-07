<?php
/* Copyright (C) 2008-2011  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2016  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2012       J. Fernando Lagrange    <fernando@demo-tic.org>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/core/lib/admin.lib.php
 *  \brief			Library of admin functions
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

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
 *	Compare 2 versions (stored into 2 arrays).
 *  To check if Dolibarr version is lower than (x,y,z), do "if versioncompare(versiondolibarrarray(), array(x.y.z)) <= 0"
 *  For example: if (versioncompare(versiondolibarrarray(),array(4,0,-4)) >= 0) is true if version is 4.0 alpha or higher.
 *  For example: if (versioncompare(versiondolibarrarray(),array(4,0,0)) >= 0) is true if version is 4.0 final or higher.
 *  For example: if (versioncompare(versiondolibarrarray(),array(4,0,1)) >= 0) is true if version is 4.0.1 or higher.
 *  Alternative way to compare: if ((float) DOL_VERSION >= 4.0) is true if version is 4.0 alpha or higher (works only to compare first and second level)
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
        if (preg_match('/alpha|dev/i',$operande1)) $operande1=-5;
        if (preg_match('/alpha|dev/i',$operande2)) $operande2=-5;
        if (preg_match('/beta$/i',$operande1)) $operande1=-4;
        if (preg_match('/beta$/i',$operande2)) $operande2=-4;
        if (preg_match('/beta([0-9])+/i',$operande1)) $operande1=-3;
        if (preg_match('/beta([0-9])+/i',$operande2)) $operande2=-3;
        if (preg_match('/rc$/i',$operande1)) $operande1=-2;
        if (preg_match('/rc$/i',$operande2)) $operande2=-2;
        if (preg_match('/rc([0-9])+/i',$operande1)) $operande1=-1;
        if (preg_match('/rc([0-9])+/i',$operande2)) $operande2=-1;
        $level++;
        //print 'level '.$level.' '.$operande1.'-'.$operande2.'<br>';
        if ($operande1 < $operande2) { $ret = -$level; break; }
        if ($operande1 > $operande2) { $ret = $level; break; }
    }
    //print join('.',$versionarray1).'('.count($versionarray1).') / '.join('.',$versionarray2).'('.count($versionarray2).') => '.$ret.'<br>'."\n";
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
    $arraysql = array();

    // Get version of database
    $versionarray=$db->getVersionArray();

    $fp = fopen($sqlfile,"r");
    if ($fp)
    {
        while (! feof($fp))
        {
            $buf = fgets($fp, 32768);

            // Test if request must be ran only for particular database or version (if yes, we must remove the -- comment)
            if (preg_match('/^--\sV(MYSQL|PGSQL)([^\s]*)/i',$buf,$reg))
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
            		if (! empty($reg[2]))
            		{
            			if (is_numeric($reg[2]))	// This is a version
            			{
			                $versionrequest=explode('.',$reg[2]);
			                //print var_dump($versionrequest);
			                //print var_dump($versionarray);
			                if (! count($versionrequest) || ! count($versionarray) || versioncompare($versionrequest,$versionarray) > 0)
			                {
			                	$qualified=0;
			                }
            			}
            			else						// This is a test on a constant. For example when we have -- VMYSQLUTF8UNICODE, we test constant $conf->global->UTF8UNICODE
            			{
            				$dbcollation = strtoupper(preg_replace('/_/', '', $conf->db->dolibarr_main_db_collation));
            				//var_dump($reg[2]);
            				//var_dump($dbcollation);
            				if (empty($conf->db->dolibarr_main_db_collation) || ($reg[2] != $dbcollation)) $qualified=0;
            				//var_dump($qualified);
            			}
            		}
            	}

                if ($qualified)
                {
                    // Version qualified, delete SQL comments
                    $buf=preg_replace('/^--\sV(MYSQL|PGSQL)([^\s]*)/i','',$buf);
                    //print "Ligne $i qualifi?e par version: ".$buf.'<br>';
                }
            }

            // Add line buf to buffer if not a comment
            if (! preg_match('/^\s*--/',$buf))
            {
                $buf=preg_replace('/([,;ERLT\)])\s*--.*$/i','\1',$buf); //remove comment from a line that not start with -- before add it to the buffer
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
            dol_syslog('Admin.lib::run_sql New Request '.($i+1).' (replacing '.$from.' to '.$to.')', LOG_DEBUG);

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
            if (! $silent) print '<tr><td class="tdtop">'.$langs->trans("Request").' '.($i+1)." sql='".dol_htmlentities($newsql,ENT_NOQUOTES)."'</td></tr>\n";
            dol_syslog('Admin.lib::run_sql Request '.($i+1), LOG_DEBUG);
			$sqlmodified=0;

            // Replace for encrypt data
            if (preg_match_all('/__ENCRYPT\(\'([^\']+)\'\)__/i',$newsql,$reg))
            {
                $num=count($reg[0]);

                for($j=0;$j<$num;$j++)
                {
                    $from 	= $reg[0][$j];
                    $to		= $db->encrypt($reg[1][$j],1);
                    $newsql	= str_replace($from,$to,$newsql);
                }
                $sqlmodified++;
            }

            // Replace for decrypt data
            if (preg_match_all('/__DECRYPT\(\'([A-Za-z0-9_]+)\'\)__/i',$newsql,$reg))
            {
                $num=count($reg[0]);

                for($j=0;$j<$num;$j++)
                {
                    $from 	= $reg[0][$j];
                    $to		= $db->decrypt($reg[1][$j]);
                    $newsql	= str_replace($from,$to,$newsql);
                }
                $sqlmodified++;
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
                $sqlmodified++;
            }

            if ($sqlmodified) dol_syslog('Admin.lib::run_sql New Request '.($i+1), LOG_DEBUG);

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
					'DB_ERROR_PRIMARY_KEY_ALREADY_EXISTS',
            		'DB_ERROR_22P02'
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

    if (empty($name))
    {
    	dol_print_error('','Error call dolibar_del_const with parameter name empty');
    	return -1;
    }

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
    $sql.= " WHERE (".$db->decrypt('name')." = '".$db->escape($name)."'";
    if (is_numeric($name)) $sql.= " OR rowid = '".$db->escape($name)."'";
    $sql.= ")";
    if ($entity >= 0) $sql.= " AND entity = ".$entity;

    dol_syslog("admin.lib::dolibarr_del_const", LOG_DEBUG);
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

    dol_syslog("admin.lib::dolibarr_get_const", LOG_DEBUG);
    $resql=$db->query($sql);
    if ($resql)
    {
        $obj=$db->fetch_object($resql);
        if ($obj) $value=$obj->value;
    }
    return $value;
}


/**
 *	Insert a parameter (key,value) into database (delete old key then insert it again).
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

    dol_syslog("admin.lib::dolibarr_set_const", LOG_DEBUG);
    $resql=$db->query($sql);

    if (strcmp($value,''))	// true if different. Must work for $value='0' or $value=0
    {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."const(name,value,type,visible,note,entity)";
        $sql.= " VALUES (";
        $sql.= $db->encrypt($name,1);
        $sql.= ", ".$db->encrypt($value,1);
        $sql.= ",'".$db->escape($type)."',".$visible.",'".$db->escape($note)."',".$entity.")";

        //print "sql".$value."-".pg_escape_string($value)."-".$sql;exit;
        //print "xx".$db->escape($value);
        dol_syslog("admin.lib::dolibarr_set_const", LOG_DEBUG);
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
        $db->rollback();
        return -1;
    }
}




/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function modules_prepare_head()
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=common";
	$head[$h][1] = $langs->trans("AvailableModules");
	$head[$h][2] = 'common';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=marketplace";
	$head[$h][1] = $langs->trans("ModulesMarketPlaces");
	$head[$h][2] = 'marketplace';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=deploy";
	$head[$h][1] = $langs->trans("AddExtensionThemeModuleOrOther");
	$head[$h][2] = 'deploy';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/modules.php?mode=develop";
	$head[$h][1] = $langs->trans("ModulesDevelopYourModule");
	$head[$h][2] = 'develop';
	$h++;

	return $head;
}


/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function security_prepare_head()
{
    global $db, $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT."/admin/security_other.php";
    $head[$h][1] = $langs->trans("Miscellaneous");
    $head[$h][2] = 'misc';
    $h++;

    $head[$h][0] = DOL_URL_ROOT."/admin/security.php";
    $head[$h][1] = $langs->trans("Passwords");
    $head[$h][2] = 'passwords';
    $h++;

    $head[$h][0] = DOL_URL_ROOT."/admin/security_file.php";
    $head[$h][1] = $langs->trans("Files").' ('.$langs->trans("Upload").')';
    $head[$h][2] = 'file';
    $h++;

    /*
    $head[$h][0] = DOL_URL_ROOT."/admin/security_file_download.php";
    $head[$h][1] = $langs->trans("Files").' ('.$langs->trans("Download").')';
    $head[$h][2] = 'filedownload';
    $h++;
	*/

    $head[$h][0] = DOL_URL_ROOT."/admin/proxy.php";
    $head[$h][1] = $langs->trans("ExternalAccess");
    $head[$h][2] = 'proxy';
    $h++;

    $head[$h][0] = DOL_URL_ROOT."/admin/events.php";
    $head[$h][1] = $langs->trans("Audit");
    $head[$h][2] = 'audit';
    $h++;


    // Show permissions lines
    $nbPerms=0;
    $sql = "SELECT COUNT(r.id) as nb";
    $sql.= " FROM ".MAIN_DB_PREFIX."rights_def as r";
    $sql.= " WHERE r.libelle NOT LIKE 'tou%'";    // On ignore droits "tous"
    $sql.= " AND entity = ".$conf->entity;
    $sql.= " AND bydefault = 1";
    if (empty($conf->global->MAIN_USE_ADVANCED_PERMS)) $sql.= " AND r.perms NOT LIKE '%_advance'";  // Hide advanced perms if option is not enabled
    $resql = $db->query($sql);
    if ($resql)
    {
    	$obj = $db->fetch_object($resql);
    	if ($obj) $nbPerms = $obj->nb;
    }
    else dol_print_error($db);

    $head[$h][0] = DOL_URL_ROOT."/admin/perms.php";
    $head[$h][1] = $langs->trans("DefaultRights");
    if ($nbPerms > 0) $head[$h][1].= ' <span class="badge">'.$nbPerms.'</span>';
    $head[$h][2] = 'default';
    $h++;

    return $head;
}


/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function translation_prepare_head()
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT."/admin/translation.php?mode=overwrite";
    $head[$h][1] = $langs->trans("TranslationOverwriteKey");
    $head[$h][2] = 'overwrite';
    $h++;

    $head[$h][0] = DOL_URL_ROOT."/admin/translation.php?mode=searchkey";
    $head[$h][1] = $langs->trans("TranslationKeySearch");
    $head[$h][2] = 'searchkey';
    $h++;

    complete_head_from_modules($conf,$langs,null,$head,$h,'translation_admin');

    complete_head_from_modules($conf,$langs,null,$head,$h,'translation_admin','remove');


    return $head;
}


/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function defaultvalues_prepare_head()
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT."/admin/defaultvalues.php?mode=createform";
    $head[$h][1] = $langs->trans("DefaultCreateForm");
    $head[$h][2] = 'createform';
    $h++;

    $head[$h][0] = DOL_URL_ROOT."/admin/defaultvalues.php?mode=filters";
    $head[$h][1] = $langs->trans("DefaultSearchFilters");
    $head[$h][2] = 'filters';
    $h++;

    $head[$h][0] = DOL_URL_ROOT."/admin/defaultvalues.php?mode=sortorder";
    $head[$h][1] = $langs->trans("DefaultSortOrder");
    $head[$h][2] = 'sortorder';
    $h++;

    $head[$h][0] = DOL_URL_ROOT."/admin/defaultvalues.php?mode=focus";
    $head[$h][1] = $langs->trans("DefaultFocus");
    $head[$h][2] = 'focus';
    $h++;

    /*$head[$h][0] = DOL_URL_ROOT."/admin/translation.php?mode=searchkey";
    $head[$h][1] = $langs->trans("TranslationKeySearch");
    $head[$h][2] = 'searchkey';
    $h++;*/

    complete_head_from_modules($conf,$langs,null,$head,$h,'defaultvalues_admin');

    complete_head_from_modules($conf,$langs,null,$head,$h,'defaultvalues_admin','remove');


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
    // session.save_path can be returned empty so we set a default location and work from there
    $sessPath = '/tmp';
    $iniPath = ini_get("session.save_path");
    if ($iniPath) {
        $sessPath = $iniPath;
    }
    $sessPath .= '/'; // We need the trailing slash
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
                    // Example of possible value
                    //$sessValues = 'newtoken|s:32:"1239f7a0c4b899200fe9ca5ea394f307";dol_loginmesg|s:0:"";newtoken|s:32:"1236457104f7ae0f328c2928973f3cb5";dol_loginmesg|s:0:"";token|s:32:"123615ad8d650c5cc4199b9a1a76783f";dol_login|s:5:"admin";dol_authmode|s:8:"dolibarr";dol_tz|s:1:"1";dol_tz_string|s:13:"Europe/Berlin";dol_dst|i:0;dol_dst_observed|s:1:"1";dol_dst_first|s:0:"";dol_dst_second|s:0:"";dol_screenwidth|s:4:"1920";dol_screenheight|s:3:"971";dol_company|s:12:"MyBigCompany";dol_entity|i:1;mainmenu|s:4:"home";leftmenuopened|s:10:"admintools";idmenu|s:0:"";leftmenu|s:10:"admintools";';

                    if (preg_match('/dol_login/i',$sessValues) && // limit to dolibarr session
                        (preg_match('/dol_entity\|i:'.$conf->entity.';/i',$sessValues) || preg_match('/dol_entity\|s:([0-9]+):"'.$conf->entity.'"/i',$sessValues)) && // limit to current entity
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
 *  @return     array      			    array('nbmodules'=>nb modules activated with success, 'errors=>array of error messages, 'nbperms'=>Nb permission added);
 */
function activateModule($value,$withdeps=1)
{
    global $db, $modules, $langs, $conf, $mysoc;

	// Check parameters
	if (empty($value)) {
		$ret['errors'][] = 'ErrorBadParameter';
		return $ret;
	}

    $ret=array('nbmodules'=>0, 'errors'=>array(), 'nbperms'=>0);
    $modName = $value;
    $modFile = $modName . ".class.php";

    // Loop on each directory to fill $modulesdir
    $modulesdir = dolGetModulesDirs();

    // Loop on each modulesdir directories
    $found=false;
    foreach ($modulesdir as $dir)
    {
        if (file_exists($dir.$modFile))
        {
            $found=@include_once $dir.$modFile;
            if ($found) break;
        }
    }

    $objMod = new $modName($db);

    // Test if PHP version ok
    $verphp=versionphparray();
    $vermin=isset($objMod->phpmin)?$objMod->phpmin:0;
	if (is_array($vermin) && versioncompare($verphp, $vermin) < 0) {
		$ret['errors'][] = $langs->trans("ErrorModuleRequirePHPVersion", versiontostring($vermin));
		return $ret;
	}

    // Test if Dolibarr version ok
    $verdol=versiondolibarrarray();
    $vermin=isset($objMod->need_dolibarr_version)?$objMod->need_dolibarr_version:0;
    //print 'eee '.versioncompare($verdol,$vermin).' - '.join(',',$verdol).' - '.join(',',$vermin);exit;
	if (is_array($vermin) && versioncompare($verdol, $vermin) < 0) {
		$ret['errors'][] = $langs->trans("ErrorModuleRequireDolibarrVersion", versiontostring($vermin));
		return $ret;
	}

	// Test if javascript requirement ok
	if (!empty($objMod->need_javascript_ajax) && empty($conf->use_javascript_ajax)) {
		$ret['errors'][] = $langs->trans("ErrorModuleRequireJavascript");
		return $ret;
	}

	$const_name = $objMod->const_name;
	if(!empty($conf->global->$const_name)){
        return $ret;
    }

    $result=$objMod->init();    // Enable module
    if ($result <= 0)
    {
        $ret['errors'][]=$objMod->error;
    }
    else
    {
        if ($withdeps)
        {
            if (isset($objMod->depends) && is_array($objMod->depends) && ! empty($objMod->depends))
            {
                // Activation of modules this module depends on
                // this->depends may be array('modModule1', 'mmodModule2') or array('always'=>"modModule1", 'FR'=>'modModule2')
                foreach ($objMod->depends as $key => $modulestring)
                {
                    if ((! is_numeric($key)) && $key != 'always' && $key != $mysoc->country_code)
                    {
                        dol_syslog("We are not concerned by dependency with key=".$key." because our country is ".$mysoc->country_code);
                        continue;
                    }
                	$activate = false;
                	foreach ($modulesdir as $dir)
                	{
                		if (file_exists($dir.$modulestring.".class.php"))
                		{
                			$resarray = activateModule($modulestring);
    						if (empty($resarray['errors'])){
    						    $activate = true;
                            }else{
    						    foreach ($resarray['errors'] as $errorMessage){
                                    dol_syslog($errorMessage, LOG_ERR);
                                }
                            }
    						break;
                		}
                	}

    				if ($activate)
    				{
    				    $ret['nbmodules']+=$resarray['nbmodules'];
    				    $ret['nbperms']+=$resarray['nbperms'];
    				}
    				else
    				{
    				    $ret['errors'][] = $langs->trans('activateModuleDependNotSatisfied', $objMod->name, $modulestring);
    				}
                }
            }

            if (isset($objMod->conflictwith) && is_array($objMod->conflictwith) && ! empty($objMod->conflictwith))
            {
                // Desactivation des modules qui entrent en conflit
                $num = count($objMod->conflictwith);
                for ($i = 0; $i < $num; $i++)
                {
                	foreach ($modulesdir as $dir)
                	{
                		if (file_exists($dir.$objMod->conflictwith[$i].".class.php"))
                		{
                			unActivateModule($objMod->conflictwith[$i],0);
                		}
                	}
                }
            }
        }
    }

    if (! count($ret['errors']))
    {
        $ret['nbmodules']++;
        $ret['nbperms']+=count($objMod->rights);
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
    $modulesdir = dolGetModulesDirs();

    // Loop on each modulesdir directories
    $found=false;
    foreach ($modulesdir as $dir)
    {
        if (file_exists($dir.$modFile))
        {
            $found=@include_once $dir.$modFile;
            if ($found) break;
        }
    }

    if ($found)
    {
        $objMod = new $modName($db);
        $result=$objMod->remove();
        if ($result <= 0) $ret=$objMod->error;
    }
    else
    {
        //print $dir.$modFile;
    	// TODO Replace this after DolibarrModules is moved as abstract class with a try catch to show module we try to disable has not been found or could not be loaded
        include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
    	$genericMod = new DolibarrModules($db);
        $genericMod->name=preg_replace('/^mod/i','',$modName);
        $genericMod->rights_class=strtolower(preg_replace('/^mod/i','',$modName));
        $genericMod->const_name='MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i','',$modName));
        dol_syslog("modules::unActivateModule Failed to find module file, we use generic function with name " . $modName);
        $genericMod->_remove(array());
    }

    // Desactivation des modules qui dependent de lui
    if (! $ret && $requiredby)
    {
        $countrb=count($objMod->requiredby);
        for ($i = 0; $i < $countrb; $i++)
        {
            //var_dump($objMod->requiredby[$i]);
            unActivateModule($objMod->requiredby[$i]);
        }
    }

    return $ret;
}


/**
 *  Add external modules to list of dictionaries
 *
 * 	@param		array		$taborder			Taborder
 * 	@param		array		$tabname			Tabname
 * 	@param		array		$tablib				Tablib
 * 	@param		array		$tabsql				Tabsql
 * 	@param		array		$tabsqlsort			Tabsqlsort
 * 	@param		array		$tabfield			Tabfield
 * 	@param		array		$tabfieldvalue		Tabfieldvalue
 * 	@param		array		$tabfieldinsert		Tabfieldinsert
 * 	@param		array		$tabrowid			Tabrowid
 * 	@param		array		$tabcond			Tabcond
 * 	@param		array		$tabhelp			Tabhelp
 *  @param		array		$tabfieldcheck		Tabfieldcheck
 * 	@return		int			1
 */
function complete_dictionary_with_modules(&$taborder,&$tabname,&$tablib,&$tabsql,&$tabsqlsort,&$tabfield,&$tabfieldvalue,&$tabfieldinsert,&$tabrowid,&$tabcond,&$tabhelp,&$tabfieldcheck)
{
    global $db, $modules, $conf, $langs;

    // Search modules
	$modulesdir = dolGetModulesDirs();
    $i = 0; // is a sequencer of modules found
    $j = 0; // j is module number. Automatically affected if module number not defined.

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
                        include_once $dir.$file;
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
                        //If module is not activated disqualified
                        if (empty($conf->global->$const_name)) $modulequalified=0;

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

                            // Complete arrays
                            //&$tabname,&$tablib,&$tabsql,&$tabsqlsort,&$tabfield,&$tabfieldvalue,&$tabfieldinsert,&$tabrowid,&$tabcond
                            if (empty($objMod->dictionaries) && ! empty($objMod->dictionnaries)) $objMod->dictionaries=$objMod->dictionnaries;		// For backward compatibility

                            if (! empty($objMod->dictionaries))
                            {
                                //var_dump($objMod->dictionaries['tabname']);
                                $nbtabname=$nbtablib=$nbtabsql=$nbtabsqlsort=$nbtabfield=$nbtabfieldvalue=$nbtabfieldinsert=$nbtabrowid=$nbtabcond=$nbtabfieldcheck=$nbtabhelp=0;
                                foreach($objMod->dictionaries['tabname'] as $val)        { $nbtabname++; $taborder[] = max($taborder)+1; $tabname[] = $val; }
                                foreach($objMod->dictionaries['tablib'] as $val)         { $nbtablib++; $tablib[] = $val; }
                                foreach($objMod->dictionaries['tabsql'] as $val)         { $nbtabsql++; $tabsql[] = $val; }
                                foreach($objMod->dictionaries['tabsqlsort'] as $val)     { $nbtabsqlsort++; $tabsqlsort[] = $val; }
                                foreach($objMod->dictionaries['tabfield'] as $val)       { $nbtabfield++; $tabfield[] = $val; }
                                foreach($objMod->dictionaries['tabfieldvalue'] as $val)  { $nbtabfieldvalue++; $tabfieldvalue[] = $val; }
                                foreach($objMod->dictionaries['tabfieldinsert'] as $val) { $nbtabfieldinsert++; $tabfieldinsert[] = $val; }
                                foreach($objMod->dictionaries['tabrowid'] as $val)       { $nbtabrowid++; $tabrowid[] = $val; }
                                foreach($objMod->dictionaries['tabcond'] as $val)        { $nbtabcond++; $tabcond[] = $val; }
                                if (! empty($objMod->dictionaries['tabhelp']))       foreach($objMod->dictionaries['tabhelp'] as $val)       { $nbtabhelp++; $tabhelp[] = $val; }
                                if (! empty($objMod->dictionaries['tabfieldcheck'])) foreach($objMod->dictionaries['tabfieldcheck'] as $val) { $nbtabfieldcheck++; $tabfieldcheck[] = $val; }

                                if ($nbtabname != $nbtablib || $nbtablib != $nbtabsql || $nbtabsql != $nbtabsqlsort)
                                {
                                    print 'Error in descriptor of module '.$const_name.'. Array ->dictionaries has not same number of record for key "tabname", "tablib", "tabsql" and "tabsqlsort"';
                                    //print "$const_name: $nbtabname=$nbtablib=$nbtabsql=$nbtabsqlsort=$nbtabfield=$nbtabfieldvalue=$nbtabfieldinsert=$nbtabrowid=$nbtabcond=$nbtabfieldcheck=$nbtabhelp\n";
                                }
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
 *  Activate external modules mandatroy when country is country_code
 *
 * 	@param		string		$country_code	CountryCode
 * 	@return		int			1
 */
function activateModulesRequiredByCountry($country_code)
{
	global $db, $conf, $langs;

	$modulesdir = dolGetModulesDirs();

	foreach ($modulesdir as $dir)
	{
		// Load modules attributes in arrays (name, numero, orders) from dir directory
		dol_syslog("Scan directory ".$dir." for modules");
		$handle=@opendir(dol_osencode($dir));
		if (is_resource($handle))
		{
			while (($file = readdir($handle))!==false)
			{
				if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod'  && substr($file, dol_strlen($file) - 10) == '.class.php')
				{
					$modName = substr($file, 0, dol_strlen($file) - 10);

					if ($modName)
					{
						include_once $dir.$file;
						$objMod = new $modName($db);

						$modulequalified=1;

						// We discard modules according to features level (PS: if module is activated we always show it)
						$const_name = 'MAIN_MODULE_'.strtoupper(preg_replace('/^mod/i','',get_class($objMod)));

						if ($objMod->version == 'development'  && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified=0;
						if ($objMod->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified=0;
						if(!empty($conf->global->$const_name)) $modulequalified=0; // already activated

						if ($modulequalified)
						{
							// Load languages files of module
							if (isset($objMod->automatic_activation) && is_array($objMod->automatic_activation) && isset($objMod->automatic_activation[$country_code]))
							{
								activateModule($modName);

								setEventMessage($objMod->automatic_activation[$country_code],'warnings');
							}

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
 *  Add external modules to list of contact element
 *
 * 	@param		array		$elementList			elementList
 * 	@return		int			1
 */
function complete_elementList_with_modules(&$elementList)
{
    global $db, $modules, $conf, $langs;

    // Search modules
    $filename = array();
    $modules = array();
    $orders = array();
    $categ = array();
    $dirmod = array();

    $i = 0; // is a sequencer of modules found
    $j = 0; // j is module number. Automatically affected if module number not defined.

    $modulesdir = dolGetModulesDirs();

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
                        include_once $dir.$file;
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
                        //If module is not activated disqualified
                        if (empty($conf->global->$const_name)) $modulequalified=0;

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
                            if (! empty($objMod->module_parts['contactelement']))
                            {
                            	$elementList[$objMod->name] = $langs->trans($objMod->name);
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
 *	@param	int		$strictw3c		0=Include form into table (deprecated), 1=Form is outside table to respect W3C (no form into table), 2=No form nor button at all
 *  @param  string  $helptext       Help
 *	@return	void
 */
function form_constantes($tableau, $strictw3c=0, $helptext='')
{
    global $db,$bc,$langs,$conf,$_Avery_Labels;

    $form = new Form($db);

    if (! empty($strictw3c) && $strictw3c == 1) print "\n".'<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td class="titlefield">'.$langs->trans("Description").'</td>';
    print '<td>';
    $text = $langs->trans("Value");
    print $form->textwithpicto($text, $helptext, 1, 'help', '', 0, 2, 'idhelptext');
    print '</td>';
    if (empty($strictw3c)) print '<td align="center" width="80">'.$langs->trans("Action").'</td>';
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
        $sql.= " AND entity IN (0, ".$conf->entity.")";
        $sql.= " ORDER BY name ASC, entity DESC";
        $result = $db->query($sql);

        dol_syslog("List params", LOG_DEBUG);
        if ($result)
        {
            $obj = $db->fetch_object($result);	// Take first result of select


            // For avoid warning in strict mode
            if (empty($obj)) {
            	$obj = (object) array('rowid'=>'','name'=>'','value'=>'','type'=>'','note'=>'');
            }

            if (empty($strictw3c)) print "\n".'<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';

            print '<tr class="oddeven">';

            // Show constant
            print '<td>';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="rowid'.(empty($strictw3c)?'':'[]').'" value="'.$obj->rowid.'">';
            print '<input type="hidden" name="constname'.(empty($strictw3c)?'':'[]').'" value="'.$const.'">';
            print '<input type="hidden" name="constnote'.(empty($strictw3c)?'':'[]').'" value="'.nl2br(dol_escape_htmltag($obj->note)).'">';

            print $langs->trans('Desc'.$const);

            if ($const == 'ADHERENT_MAILMAN_URL')
            {
                print '. '.$langs->trans("Example").': <a href="#" id="exampleclick1">'.img_down().'</a><br>';
                //print 'http://lists.exampe.com/cgi-bin/mailman/admin/%LISTE%/members?adminpw=%MAILMAN_ADMINPW%&subscribees=%EMAIL%&send_welcome_msg_to_this_batch=1';
                print '<div id="example1" class="hidden">';
                print 'http://lists.example.com/cgi-bin/mailman/admin/%LISTE%/members/add?subscribees_upload=%EMAIL%&amp;adminpw=%MAILMAN_ADMINPW%&amp;subscribe_or_invite=0&amp;send_welcome_msg_to_this_batch=0&amp;notification_to_list_owner=0';
                print '</div>';
            }
            if ($const == 'ADHERENT_MAILMAN_UNSUB_URL')
            {
                print '. '.$langs->trans("Example").': <a href="#" id="exampleclick2">'.img_down().'</a><br>';
                print '<div id="example2" class="hidden">';
                print 'http://lists.example.com/cgi-bin/mailman/admin/%LISTE%/members/remove?unsubscribees_upload=%EMAIL%&amp;adminpw=%MAILMAN_ADMINPW%&amp;send_unsub_ack_to_this_batch=0&amp;send_unsub_notifications_to_list_owner=0';
                print '</div>';
                //print 'http://lists.example.com/cgi-bin/mailman/admin/%LISTE%/members/remove?adminpw=%MAILMAN_ADMINPW%&unsubscribees=%EMAIL%';
            }
            if ($const == 'ADHERENT_MAILMAN_LISTS')
            {
            	print '. '.$langs->trans("Example").': <a href="#" id="exampleclick3">'.img_down().'</a><br>';
            	print '<div id="example3" class="hidden">';
            	print 'mymailmanlist<br>';
            	print 'mymailmanlist1,mymailmanlist2<br>';
            	print 'TYPE:Type1:mymailmanlist1,TYPE:Type2:mymailmanlist2<br>';
            	if ($conf->categorie->enabled) print 'CATEG:Categ1:mymailmanlist1,CATEG:Categ2:mymailmanlist2<br>';
            	print '</div>';
            	//print 'http://lists.example.com/cgi-bin/mailman/admin/%LISTE%/members/remove?adminpw=%MAILMAN_ADMINPW%&unsubscribees=%EMAIL%';
            }

            print "</td>\n";

            // Value
            if ($const == 'ADHERENT_CARD_TYPE' || $const == 'ADHERENT_ETIQUETTE_TYPE')
            {
                print '<td>';
                // List of possible labels (defined into $_Avery_Labels variable set into format_cards.lib.php)
                require_once DOL_DOCUMENT_ROOT.'/core/lib/format_cards.lib.php';
                $arrayoflabels=array();
                foreach(array_keys($_Avery_Labels) as $codecards)
                {
                    $arrayoflabels[$codecards]=$_Avery_Labels[$codecards]['name'];
                }
                print $form->selectarray('constvalue'.(empty($strictw3c)?'':'[]'),$arrayoflabels,($obj->value?$obj->value:'CARD'),1,0,0);
                print '<input type="hidden" name="consttype" value="yesno">';
                print '</td>';
            }
            else
            {
                print '<td>';
                if (in_array($const,array('ADHERENT_CARD_TEXT','ADHERENT_CARD_TEXT_RIGHT','ADHERENT_ETIQUETTE_TEXT')))
                {
                    print '<textarea class="flat" name="constvalue'.(empty($strictw3c)?'':'[]').'" cols="50" rows="5" wrap="soft">'."\n";
                    print $obj->value;
                    print "</textarea>\n";
                    print '<input type="hidden" name="consttype" value="texte">';
                }
                else if (in_array($const,array('ADHERENT_AUTOREGISTER_NOTIF_MAIL','ADHERENT_AUTOREGISTER_MAIL','ADHERENT_MAIL_VALID','ADHERENT_MAIL_COTIS','ADHERENT_MAIL_RESIL')))
                {
                    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
                    $doleditor=new DolEditor('constvalue_'.$const.(empty($strictw3c)?'':'[]'),$obj->value,'',160,'dolibarr_notes','',false,false,$conf->fckeditor->enabled,ROWS_5,'90%');
                    $doleditor->Create();
                    print '<input type="hidden" name="consttype'.(empty($strictw3c)?'':'[]').'" value="texte">';
                }
                else if ($obj->type == 'yesno')
                {
                    print $form->selectyesno('constvalue'.(empty($strictw3c)?'':'[]'),$obj->value,1);
                    print '<input type="hidden" name="consttype'.(empty($strictw3c)?'':'[]').'" value="yesno">';
                }
                else
                {
                    print '<input type="text" class="flat" size="48" name="constvalue'.(empty($strictw3c)?'':'[]').'" value="'.dol_escape_htmltag($obj->value).'">';
                    print '<input type="hidden" name="consttype'.(empty($strictw3c)?'':'[]').'" value="chaine">';
                }
                print '</td>';
            }
            // Submit
            if (empty($strictw3c))
            {
            	print '<td align="center">';
            	print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
	            print "</td>";
            }
    	    print "</tr>\n";
            if (empty($strictw3c)) print "</form>\n";
        }
    }
    print '</table>';

    if (! empty($strictw3c) && $strictw3c == 1)
    {
    	print '<div align="center"><input type="submit" class="button" value="'.$langs->trans("Update").'" name="update"></div>';
    	print "</form>\n";
    }
}


/**
 *	Show array with constants to edit
 *
 *	@param	array	$modules		Array of all modules
 *	@return	string					HTML string with warning
 */
function showModulesExludedForExternal($modules)
{
	global $conf,$langs;

	$text=$langs->trans("OnlyFollowingModulesAreOpenedToExternalUsers");
	$listofmodules=explode(',',$conf->global->MAIN_MODULES_FOR_EXTERNAL);
	$i=0;
	if (!empty($modules)) {
		foreach($modules as $module)
		{
			$moduleconst=$module->const_name;
			$modulename=strtolower($module->name);
			//print 'modulename='.$modulename;

			//if (empty($conf->global->$moduleconst)) continue;
			if (! in_array($modulename,$listofmodules)) continue;
			//var_dump($modulename.'eee'.$langs->trans('Module'.$module->numero.'Name'));

			if ($i > 0) $text.=', ';
			else $text.=' ';
			$i++;
			$text .= $langs->trans('Module'.$module->numero.'Name');
		}
	}
	return $text;
}


/**
 *	Add document model used by doc generator
 *
 *	@param		string	$name			Model name
 *	@param		string	$type			Model type
 *	@param		string	$label			Model label
 *	@param		string	$description	Model description
 *	@return		int						<0 if KO, >0 if OK
 */
function addDocumentModel($name, $type, $label='', $description='')
{
	global $db, $conf;

	$db->begin();

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity, libelle, description)";
    $sql.= " VALUES ('".$db->escape($name)."','".$type."',".$conf->entity.", ";
    $sql.= ($label?"'".$db->escape($label)."'":'null').", ";
    $sql.= (! empty($description)?"'".$db->escape($description)."'":"null");
    $sql.= ")";

    dol_syslog("admin.lib::addDocumentModel", LOG_DEBUG);
	$resql=$db->query($sql);
	if ($resql)
	{
		$db->commit();
		return 1;
	}
	else
	{
		dol_print_error($db);
		$db->rollback();
		return -1;
	}
}

/**
 *	Delete document model used by doc generator
 *
 *	@param		string	$name			Model name
 *	@param		string	$type			Model type
 *	@return		int						<0 if KO, >0 if OK
 */
function delDocumentModel($name, $type)
{
	global $db, $conf;

	$db->begin();

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."document_model";
	$sql.= " WHERE nom = '".$db->escape($name)."'";
	$sql.= " AND type = '".$type."'";
	$sql.= " AND entity = ".$conf->entity;

	dol_syslog("admin.lib::delDocumentModel", LOG_DEBUG);
	$resql=$db->query($sql);
	if ($resql)
	{
		$db->commit();
		return 1;
	}
	else
	{
		dol_print_error($db);
		$db->rollback();
		return -1;
	}
}


/**
 *	Return the php_info into an array
 *
 *	@return		array		Array with PHP infos
 */
function phpinfo_array()
{
	ob_start();
	phpinfo();
	$info_arr = array();
	$info_lines = explode("\n", strip_tags(ob_get_clean(), "<tr><td><h2>"));	// end of ob_start()
	$cat = "General";
	foreach($info_lines as $line)
	{
		// new cat?
		preg_match("~<h2>(.*)</h2>~", $line, $title) ? $cat = $title[1] : null;
		if(preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val))
		{
			$info_arr[trim($cat)][trim($val[1])] = $val[2];
		}
		elseif(preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val))
		{
			$info_arr[trim($cat)][trim($val[1])] = array("local" => $val[2], "master" => $val[3]);
		}
	}
	return $info_arr;
}

/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @return	array   	    		    head array with tabs
 */
function email_admin_prepare_head()
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	if ($user->admin && (empty($_SESSION['leftmenu']) || $_SESSION['leftmenu'] != 'email_templates'))
	{
		$head[$h][0] = DOL_URL_ROOT."/admin/mails.php";
		$head[$h][1] = $langs->trans("OutGoingEmailSetup");
		$head[$h][2] = 'common';
		$h++;

		if ($conf->mailing->enabled)
		{
			$head[$h][0] = DOL_URL_ROOT."/admin/mails_emailing.php";
			$head[$h][1] = $langs->trans("OutGoingEmailSetupForEmailing");
			$head[$h][2] = 'common_emailing';
			$h++;
		}
	}

	$head[$h][0] = DOL_URL_ROOT."/admin/mails_templates.php";
	$head[$h][1] = $langs->trans("DictionaryEMailTemplates");
	$head[$h][2] = 'templates';
	$h++;

	if ($conf->global->MAIN_FEATURES_LEVEL >= 1)
	{
		$head[$h][0] = DOL_URL_ROOT."/admin/mails_senderprofile_list.php";
		$head[$h][1] = $langs->trans("EmailSenderProfiles");
		$head[$h][2] = 'senderprofiles';
		$h++;
	}

	complete_head_from_modules($conf,$langs,null,$head,$h,'email_admin','remove');

	return $head;
}


