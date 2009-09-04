<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/lib/admin.lib.php
 *  \brief			Library of admin functions
 *  \version		$Id$
 */


/**
 *  \brief      Renvoi une version en chaine depuis une version en tableau
 *  \param	   versionarray        Tableau de version (vermajeur,vermineur,autre)
 *  \return     string              Chaine version
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
 *	\brief      Compare 2 versions
 *	\param      versionarray1       Array of version (vermajor,verminor,patch)
 *	\param      versionarray2       Array of version (vermajor,verminor,patch)
 *	\return     int                 -4,-3,-2,-1 if versionarray1<versionarray2 (value depends on level of difference)
 * 									0 if =
 * 									1,2,3,4 if versionarray1>versionarray2 (value depends on level of difference)
 */
function versioncompare($versionarray1,$versionarray2)
{
	$ret=0;
	$level=0;
	while ($level < max(sizeof($versionarray1),sizeof($versionarray2)))
	{
		$operande1=isset($versionarray1[$level])?$versionarray1[$level]:0;
		$operande2=isset($versionarray2[$level])?$versionarray2[$level]:0;
		if (eregi('beta|alpha',$operande1)) $operande1=-1;
		if (eregi('beta|alpha',$operande2)) $operande2=-1;
		$level++;
		//print 'level '.$level.' '.$operande1.'-'.$operande2;
		if ($operande1 < $operande2) { $ret = -$level; break; }
		if ($operande1 > $operande2) { $ret = $level; break; }
	}
	//print join('.',$versionarray1).'('.sizeof($versionarray1).') / '.join('.',$versionarray2).'('.sizeof($versionarray2).') => '.$ret;
	return $ret;
}


/**
 \brief      Return version PHP
 \return     array               Tableau de version (vermajeur,vermineur,autre)
 */
function versionphparray()
{
	return split('\.',PHP_VERSION);
}

/**
 \brief      Return version Dolibarr
 \return     array               Tableau de version (vermajeur,vermineur,autre)
 */
function versiondolibarrarray()
{
	return split('\.',DOL_VERSION);
}


/**
 *	\brief		Launch a sql file
 *	\param		sqlfile		Full path to sql file
 *	\return		int			<=0 if KO, >0 if OK
 */
function run_sql($sqlfile,$silent=1)
{
	global $db, $conf, $langs, $user;

	dol_syslog("Admin.lib::run_sql run sql file ".$sqlfile, LOG_DEBUG);

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
		while (!feof ($fp))
		{
			$buf = fgets($fp, 4096);

			// Cas special de lignes autorisees pour certaines versions uniquement
			if (eregi('^-- V([0-9\.]+)',$buf,$reg))
			{
				$versioncommande=split('\.',$reg[1]);
				//print var_dump($versioncommande);
				//print var_dump($versionarray);
				if (sizeof($versioncommande) && sizeof($versionarray)
				&& versioncompare($versioncommande,$versionarray) <= 0)
				{
					// Version qualified, delete SQL comments
					$buf=eregi_replace('^-- V([0-9\.]+)','',$buf);
					//print "Ligne $i qualifi?e par version: ".$buf.'<br>';
				}
			}

			// Ajout ligne si non commentaire
			if (! eregi('^--',$buf)) $buffer .= $buf;

			//          print $buf.'<br>';

			if (eregi(';',$buffer))
			{
				// Found new request
				$arraysql[$i]=trim($buffer);
				$i++;
				$buffer='';
			}
		}

		if ($buffer) $arraysql[$i]=trim($buffer);
		fclose($fp);
	}

	// Loop on each request to see if there is a __+MAX_table__ key
	$listofmaxrowid=array();
	foreach($arraysql as $i => $sql)
	{
		if ($sql)
		{
			$newsql=$sql;

			// Replace __+MAX_table__ with max of table
			while (eregi('__\+MAX_([A-Za-z_]+)__',$newsql,$reg))
			{
				$table=$reg[1];
				if (! isset($listofmaxrowid[$table]))
				{
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
				dol_syslog('Admin.lib::run_sql New Request '.($i+1).' sql='.$newsql, LOG_DEBUG);

				$arraysql[$i]=$newsql;
			}
		}
	}

	// Loop on each request to execute request
	$cursorinsert=0;
	$listofinsertedrowid=array();
	foreach($arraysql as $i => $sql)
	{
		if ($sql)
		{
			$newsql=$sql;

			// Ajout trace sur requete (eventuellement ? commenter si beaucoup de requetes)
			if (! $silent) print '<tr><td valign="top">'.$langs->trans("Request").' '.($i+1)." sql='".$newsql."'</td></tr>\n";
			dol_syslog('Admin.lib::run_sql Request '.($i+1).' sql='.$newsql, LOG_DEBUG);

			if (eregi('insert into ([^ ]+)',$newsql,$reg))
			{
				// It's an insert
				$cursorinsert++;
			}

			// Replace __x__ with rowid of insert nb x
			while (eregi('__([0-9]+)__',$newsql,$reg))
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
				dol_syslog('Admin.lib::run_sql New Request '.($i+1).' sql='.$newsql, LOG_DEBUG);
			}

			// Replace __ENTITY__ with current entity id
			while (eregi('(__ENTITY__)',$newsql,$reg))
			{
				$from   = $reg[1];
				$to     = $conf->entity;
				$newsql = str_replace($from,$to,$newsql);
				dol_syslog('Admin.lib::run_sql New Request '.($i+1).' sql='.$newsql, LOG_DEBUG);
			}

			$result=$db->query($newsql);
			if ($result)
			{
				if (eregi('insert into ([^ ]+)',$newsql,$reg))
				{
					// It's an insert
					$table=eregi_replace('[^a-zA-Z_]+','',$reg[1]);
					$insertedrowid=$db->last_insert_id($table);
					$listofinsertedrowid[$cursorinsert]=$insertedrowid;
					dol_syslog('Admin.lib::run_sql Insert nb '.$cursorinsert.', done in table '.$table.', rowid is '.$listofinsertedrowid[$cursorinsert], LOG_DEBUG);
				}
				// 	          print '<td align="right">OK</td>';
			}
			else
			{
				$errno=$db->errno();

				$okerror=array( 'DB_ERROR_TABLE_ALREADY_EXISTS',
				'DB_ERROR_COLUMN_ALREADY_EXISTS',
				'DB_ERROR_KEY_NAME_ALREADY_EXISTS',
				'DB_ERROR_RECORD_ALREADY_EXISTS',
				'DB_ERROR_NOSUCHTABLE',
				'DB_ERROR_NOSUCHFIELD',
				'DB_ERROR_NO_FOREIGN_KEY_TO_DROP',
				'DB_ERROR_CANNOT_CREATE',    		// Qd contrainte deja existante
				'DB_ERROR_CANT_DROP_PRIMARY_KEY',
				'DB_ERROR_PRIMARY_KEY_ALREADY_EXISTS'
				);
				if (in_array($errno,$okerror))
				{
					//if (! $silent) print $langs->trans("OK");
				}
				else
				{
					if (! $silent) print '<tr><td valign="top" colspan="2">';
					if (! $silent) print '<div class="error">'.$langs->trans("Error")." ".$db->errno().": ".$newsql."<br>".$db->error()."</div></td>";
					if (! $silent) print '</tr>';
					dol_syslog('Admin.lib::run_sql Request '.($i+1)." Error ".$db->errno()." ".$newsql."<br>".$db->error(), LOG_ERR);
					$error++;
				}
			}

			if (! $silent) print '</tr>';
		}
	}

	if ($error == 0)
	{
		if (! $silent) print '<tr><td>'.$langs->trans("ProcessMigrateScript").'</td>';
		if (! $silent) print '<td align="right">'.$langs->trans("OK").'</td></tr>';
		$ok = 1;
	}
	else
	{
		if (! $silent) print '<tr><td>'.$langs->trans("ProcessMigrateScript").'</td>';
		if (! $silent) print '<td align="right"><font class="error">'.$langs->trans("KO").'</font></td></tr>';
		$ok = 0;
	}

	return $ok;
}


/**
 *	\brief		Effacement d'une constante dans la base de donnees
 *	\sa			dolibarr_get_const, dolibarr_sel_const
 *	\param	    db          Handler d'acces base
 *	\param	    name		Nom ou rowid de la constante
 *	\param	    entity		Multi company id, -1 for all entities
 *	\return     int         <0 if KO, >0 if OK
 */
function dolibarr_del_const($db, $name, $entity=1)
{
	global $conf;

	$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
	$sql.=" WHERE (".$db->decrypt('name',$conf->db->dolibarr_main_db_encryption,$conf->db->dolibarr_main_db_cryptkey)." = '".addslashes($name)."' OR rowid = '".addslashes($name)."')";
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
		$this->error=$db->lasterror();
		return -1;
	}
}

/**
 \brief      Recupere une constante depuis la base de donnees.
 \sa			dolibarr_del_const, dolibarr_set_const
 \param	    db          Handler d'acces base
 \param	    name				Nom de la constante
 \param	    entity			Multi company id
 \return     string      Valeur de la constante
 */
function dolibarr_get_const($db, $name, $entity=1)
{
	global $conf;
	$value='';

	$sql = "SELECT ".$db->decrypt('value',$conf->db->dolibarr_main_db_encryption,$conf->db->dolibarr_main_db_cryptkey)." as value";
	$sql.= " FROM ".MAIN_DB_PREFIX."const";
	$sql.= " WHERE ".$db->decrypt('name',$conf->db->dolibarr_main_db_encryption,$conf->db->dolibarr_main_db_cryptkey)." = '".addslashes($name)."'";
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
 *	\brief      Insert a parameter (key,value) into database.
 *	\sa			dolibarr_del_const, dolibarr_get_const
 *	\param	    db          Database handler
 *	\param	    name		Name of constant
 *	\param	    value		Value of constant
 *	\param	    type		Type of constante (chaine par defaut)
 *	\param	    visible	    Is constant visible in Setup->Other page (0 by default)
 *	\param	    note		Note on parameter
 *	\param	    entity		Multi company id
 *	\return     int         -1 if KO, 1 if OK
 */
function dolibarr_set_const($db, $name, $value, $type='chaine', $visible=0, $note='', $entity=1)
{
	global $conf;

	if (empty($name))
	{
		dol_print_error("Error: Call to function dolibarr_set_const with wrong parameters", LOG_ERR);
		exit;
	}

	$db->begin();

	//dol_syslog("dolibarr_set_const name=$name, value=$value");
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."const";
	$sql.= " WHERE ".$db->decrypt('name',$conf->db->dolibarr_main_db_encryption,$conf->db->dolibarr_main_db_cryptkey)." = '".addslashes($name)."'";
	$sql.= " AND entity = ".$entity;
	dol_syslog("admin.lib::dolibarr_set_const sql=".$sql, LOG_DEBUG);
	$resql=$db->query($sql);

	if (strcmp($value,''))	// true if different. Must work for $value='0' or $value=0
	{
		$sql = "INSERT INTO llx_const(name,value,type,visible,note,entity)";
		$sql.= " VALUES (";
		$sql.= $db->encrypt("'".$name."'",$conf->db->dolibarr_main_db_encryption,$conf->db->dolibarr_main_db_cryptkey);
		$sql.= ",".$db->encrypt("'".addslashes($value)."'",$conf->db->dolibarr_main_db_encryption,$conf->db->dolibarr_main_db_cryptkey);
		$sql.= ",'".$type."',".$visible.",'".addslashes($note)."',".$entity.")";

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
 *  \brief      	Define head array for tabs of security setup pages
 *  \return			Array of head
 *  \version    	$Id$
 */
function security_prepare_head()
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/perms.php";
	$head[$h][1] = $langs->trans("DefaultRights");
	$head[$h][2] = 'default';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/security.php";
	$head[$h][1] = $langs->trans("Passwords");
	$head[$h][2] = 'passwords';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/security_other.php";
	$head[$h][1] = $langs->trans("Miscellanous");
	$head[$h][2] = 'misc';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/admin/events.php";
	$head[$h][1] = $langs->trans("Audit");
	$head[$h][2] = 'audit';
	$h++;

	return $head;
}


/**
 * 	Return list of session
 *	@return		array			Array list of sessions
 */
function listOfSessions()
{
	$arrayofSessions = array();
	$sessPath = get_cfg_var("session.save_path").'/';
	dol_syslog('admin.lib:listOfSessions sessPath='.$sessPath);

	$dh = @opendir($sessPath);
	while(($file = @readdir($dh)) !== false)
	{
		if (eregi('^sess_',$file) && $file != "." && $file != "..")
		{
			$fullpath = $sessPath.$file;
			if(! @is_dir($fullpath))
			{
				$tmp=split('_', $file);
				$idsess=$tmp[1];
				//print 'file='.$file.' id='.$idsess;
				$sessValues = file_get_contents($fullpath);	// get raw session data
				$arrayofSessions[$idsess]["age"] = time()-filectime( $fullpath );
				$arrayofSessions[$idsess]["creation"] = filectime( $fullpath );
				$arrayofSessions[$idsess]["modification"] = filemtime( $fullpath );
				$arrayofSessions[$idsess]["raw"] = $sessValues;
			}
		}
	}
	@closedir($dh);

	return $arrayofSessions;
}

/**
 * 	Purge existing sessions
 * 	@param		mysessionid		To avoid to try to delete my own session
 * 	@return		int		>0 if OK, <0 if KO
 */
function purgeSessions($mysessionid)
{
	$arrayofSessions = array();
	$sessPath = get_cfg_var("session.save_path")."\\";

	dol_syslog('admin.lib:purgeSessions mysessionid='.$mysessionid.' sessPath='.$sessPath);

	$error=0;
	$dh = @opendir($sessPath);
	while(($file = @readdir($dh)) !== false)
	{
		if ($file != "." && $file != "..")
		{
			$fullpath = $sessPath.$file;
			if(! @is_dir($fullpath))
			{
				$tmp=split('_', $file);
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
	@closedir($dh);

	if (! $error) return 1;
	else return -$error;
}
?>