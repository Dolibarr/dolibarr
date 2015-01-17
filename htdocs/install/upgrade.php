<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *
 * Upgrade scripts can be ran from command line with syntax:
 *
 * cd htdocs/install
 * php upgrade.php 3.4.0 3.5.0
 * php upgrade2.php 3.4.0 3.5.0
 *
 * Return code is 0 if OK, >0 if error
 */

/**
 *		\file       htdocs/install/upgrade.php
 *      \brief      Run migration script
 */

include_once 'inc.php';
if (! file_exists($conffile))
{
    print 'Error: Dolibarr config file was not found. This may means that Dolibarr is not installed yet. Please call the page "/install/index.php" instead of "/install/upgrade.php").';
}
require_once $conffile; if (! isset($dolibarr_main_db_type)) $dolibarr_main_db_type='mysql';	// For backward compatibility
require_once $dolibarr_main_document_root.'/core/lib/admin.lib.php';

$grant_query='';
$etape = 2;
$ok = 0;


// Cette page peut etre longue. On augmente le delai autorise.
// Ne fonctionne que si on est pas en safe_mode.
$err=error_reporting();
error_reporting(0);
@set_time_limit(120);
error_reporting($err);


$setuplang=GETPOST("selectlang",'',3)?GETPOST("selectlang",'',3):'auto';
$langs->setDefaultLang($setuplang);
$versionfrom=GETPOST("versionfrom",'',3)?GETPOST("versionfrom",'',3):(empty($argv[1])?'':$argv[1]);
$versionto=GETPOST("versionto",'',3)?GETPOST("versionto",'',3):(empty($argv[2])?'':$argv[2]);
$versionmodule=GETPOST("versionmodule",'',3)?GETPOST("versionmodule",'',3):(empty($argv[3])?'':$argv[3]);

$langs->load("admin");
$langs->load("install");
$langs->load("errors");

if ($dolibarr_main_db_type == "mysql") $choix=1;
if ($dolibarr_main_db_type == "mysqli") $choix=1;
if ($dolibarr_main_db_type == "pgsql") $choix=2;
if ($dolibarr_main_db_type == "mssql") $choix=3;


dolibarr_install_syslog("upgrade: Entering upgrade.php page");
if (! is_object($conf)) dolibarr_install_syslog("upgrade2: conf file not initialized",LOG_ERR);


/*
 * View
 */

if (! $versionfrom && ! $versionto)
{
	print 'Error: Parameter versionfrom or versionto missing.'."\n";
	print 'Upgrade must be ran from cmmand line with parameters or called from page install/index.php (like a first install) instead of page install/upgrade.php'."\n";
	// Test if batch mode
	$sapi_type = php_sapi_name();
	$script_file = basename(__FILE__);
	$path=dirname(__FILE__).'/';
	if (substr($sapi_type, 0, 3) == 'cli') 
	{
		print 'Syntax from command line: '.$script_file." x.y.z a.b.c\n";
	}
	exit;
}


pHeader('',"upgrade2",GETPOST('action'),'versionfrom='.$versionfrom.'&versionto='.$versionto);

$actiondone=0;

// Action to launch the migrate script
if (! GETPOST("action") || preg_match('/upgrade/i',GETPOST('action')))
{
    $actiondone=1;

    print '<h3>'.$langs->trans("DatabaseMigration").'</h3>';

    print '<table cellspacing="0" cellpadding="1" border="0" width="100%">';
    $error=0;

    // If password is encoded, we decode it
    if (preg_match('/crypted:/i',$dolibarr_main_db_pass) || ! empty($dolibarr_main_db_encrypted_pass))
    {
        require_once $dolibarr_main_document_root.'/core/lib/security.lib.php';
        if (preg_match('/crypted:/i',$dolibarr_main_db_pass))
        {
            $dolibarr_main_db_pass = preg_replace('/crypted:/i', '', $dolibarr_main_db_pass);
            $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_pass);
            $dolibarr_main_db_encrypted_pass = $dolibarr_main_db_pass;	// We need to set this as it is used to know the password was initially crypted
        }
        else $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
    }

    // $conf is already instancied inside inc.php
    $conf->db->type = $dolibarr_main_db_type;
    $conf->db->host = $dolibarr_main_db_host;
    $conf->db->port = $dolibarr_main_db_port;
    $conf->db->name = $dolibarr_main_db_name;
    $conf->db->user = $dolibarr_main_db_user;
    $conf->db->pass = $dolibarr_main_db_pass;

    // Load type and crypt key
    if (empty($dolibarr_main_db_encryption)) $dolibarr_main_db_encryption=0;
    $conf->db->dolibarr_main_db_encryption = $dolibarr_main_db_encryption;
    if (empty($dolibarr_main_db_cryptkey)) $dolibarr_main_db_cryptkey='';
    $conf->db->dolibarr_main_db_cryptkey = $dolibarr_main_db_cryptkey;

    $db=getDoliDBInstance($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);

    // Create the global $hookmanager object
    include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
    $hookmanager=new HookManager($db);

    if ($db->connected == 1)
    {
        print '<tr><td class="nowrap">';
        print $langs->trans("ServerConnection")." : $dolibarr_main_db_host</td><td align=\"right\">".$langs->trans("OK")."</td></tr>\n";
        dolibarr_install_syslog("upgrade: ".$langs->transnoentities("ServerConnection")." : $dolibarr_main_db_host ".$langs->transnoentities("OK"));
        $ok = 1;
    }
    else
    {
        print "<tr><td>".$langs->trans("ErrorFailedToConnectToDatabase",$dolibarr_main_db_name)."</td><td align=\"right\">".$langs->transnoentities("Error")."</td></tr>\n";
        dolibarr_install_syslog("upgrade: ".$langs->transnoentities("ErrorFailedToConnectToDatabase",$dolibarr_main_db_name));
        $ok = 0;
    }

    if ($ok)
    {
        if($db->database_selected == 1)
        {
            print '<tr><td class="nowrap">';
            print $langs->trans("DatabaseConnection")." : ".$dolibarr_main_db_name."</td><td align=\"right\">".$langs->trans("OK")."</td></tr>\n";
            dolibarr_install_syslog("upgrade: Database connection successfull : $dolibarr_main_db_name");
            $ok=1;
        }
        else
        {
            print "<tr><td>".$langs->trans("ErrorFailedToConnectToDatabase",$dolibarr_main_db_name)."</td><td align=\"right\">".$langs->trans("Error")."</td></tr>\n";
            dolibarr_install_syslog("upgrade: ".$langs->transnoentities("ErrorFailedToConnectToDatabase",$dolibarr_main_db_name));
            $ok=0;
        }
    }

    // Affiche version
    if ($ok)
    {
        $version=$db->getVersion();
        $versionarray=$db->getVersionArray();
        print '<tr><td>'.$langs->trans("ServerVersion").'</td>';
        print '<td align="right">'.$version.'</td></tr>';
        dolibarr_install_syslog("upgrade: ".$langs->transnoentities("ServerVersion")." : $version");

        // Test database version
        $versionmindb=$db::VERSIONMIN;
        //print join('.',$versionarray).' - '.join('.',$versionmindb);
        if (count($versionmindb) && count($versionarray)
        	&& versioncompare($versionarray,$versionmindb) < 0)
        {
        	// Warning: database version too low.
        	print "<tr><td>".$langs->trans("ErrorDatabaseVersionTooLow",join('.',$versionarray),join('.',$versionmindb))."</td><td align=\"right\">".$langs->trans("Error")."</td></tr>\n";
        	dolibarr_install_syslog("upgrade: ".$langs->transnoentities("ErrorDatabaseVersionTooLow",join('.',$versionarray),join('.',$versionmindb)));
        	$ok=0;
        }

    }

    // Force l'affichage de la progression
    if ($ok)
    {
	    print '<tr><td colspan="2">'.$langs->trans("PleaseBePatient").'</td></tr>';
	    flush();
    }

    /*
     * Delete duplicates in table categorie_association
     */
    if ($ok)
    {
	    $result = $db->DDLDescTable(MAIN_DB_PREFIX."categorie_association");
	    if ($result)	// result defined for version 3.2 or -
	    {
		    $obj = $db->fetch_object($result);
		    if ($obj)	// It table categorie_association exists
		    {
		    	$couples=array();
			    $filles=array();
			    $sql = "SELECT fk_categorie_mere, fk_categorie_fille";
			    $sql.= " FROM ".MAIN_DB_PREFIX."categorie_association";
			    dolibarr_install_syslog("upgrade: search duplicate", LOG_DEBUG);
			    $resql = $db->query($sql);
			    if ($resql)
			    {
			        $num=$db->num_rows($resql);
			        while ($obj=$db->fetch_object($resql))
			        {
			            if (! isset($filles[$obj->fk_categorie_fille]))	// Only one record as child (a child has only on parent).
			            {
			                if ($obj->fk_categorie_mere != $obj->fk_categorie_fille)
			                {
			                    $filles[$obj->fk_categorie_fille]=1;	// Set record for this child
			                    $couples[$obj->fk_categorie_mere.'_'.$obj->fk_categorie_fille]=array('mere'=>$obj->fk_categorie_mere, 'fille'=>$obj->fk_categorie_fille);
			                }
			            }
			        }

			        dolibarr_install_syslog("upgrade: result is num=".$num." count(couples)=".count($couples));

			        // If there is duplicates couples or child with two parents
			        if (count($couples) > 0 && $num > count($couples))
			        {
			            $error=0;

			            $db->begin();

			            // We delete all
			            $sql="DELETE FROM ".MAIN_DB_PREFIX."categorie_association";
			            dolibarr_install_syslog("upgrade: delete association", LOG_DEBUG);
			            $resqld=$db->query($sql);
			            if ($resqld)
			            {
			            	// And we insert only each record once
			                foreach($couples as $key => $val)
			                {
			                    $sql ="INSERT INTO ".MAIN_DB_PREFIX."categorie_association(fk_categorie_mere,fk_categorie_fille)";
			                    $sql.=" VALUES(".$val['mere'].", ".$val['fille'].")";
			                    dolibarr_install_syslog("upgrade: insert association", LOG_DEBUG);
			                    $resqli=$db->query($sql);
			                    if (! $resqli) $error++;
			                }
			            }

			            if (! $error)
			            {
			                print '<tr><td>'.$langs->trans("RemoveDuplicates").'</td>';
			                print '<td align="right">'.$langs->trans("Success").' ('.$num.'=>'.count($couples).')</td></tr>';
			                $db->commit();
			            }
			            else
			            {
			                print '<tr><td>'.$langs->trans("RemoveDuplicates").'</td>';
			                print '<td align="right">'.$langs->trans("Failed").'</td></tr>';
			                $db->rollback();
			            }
			        }
			    }
			    else
			    {
			        print '<div class="error">'.$langs->trans("Error").' '.$db->lasterror().'</div>';
			    }
		    }
	    }
    }


	/*
	 * Remove deprecated indexes and constraints for Mysql
	 */
    if ($ok && preg_match('/mysql/',$db->type))
    {
        $versioncommande=array(4,0,0);
        if (count($versioncommande) && count($versionarray)
        && versioncompare($versioncommande,$versionarray) <= 0)	// Si mysql >= 4.0
        {
            // Suppression vieilles contraintes sans noms et en doubles
            // Les contraintes indesirables ont un nom qui commence par 0_ ou se termine par ibfk_999
            $listtables=array(
            					MAIN_DB_PREFIX.'adherent_options',
            					MAIN_DB_PREFIX.'bank_class',
            					MAIN_DB_PREFIX.'c_ecotaxe',
            					MAIN_DB_PREFIX.'c_methode_commande_fournisseur',   // table renamed
    		                    MAIN_DB_PREFIX.'c_input_method'
            );

            $listtables = $db->DDLListTables($conf->db->name,'');
            foreach ($listtables as $val)
            {
            	// Database prefix filter
            	if (preg_match('/^'.MAIN_DB_PREFIX.'/', $val))
            	{
            		//print "x".$val."<br>";
            		$sql = "SHOW CREATE TABLE ".$val;
            		$resql = $db->query($sql);
            		if ($resql)
            		{
            			$values=$db->fetch_array($resql);
            			$i=0;
            			$createsql=$values[1];
            			while (preg_match('/CONSTRAINT `(0_[0-9a-zA-Z]+|[_0-9a-zA-Z]+_ibfk_[0-9]+)`/i',$createsql,$reg) && $i < 100)
            			{
            				$sqldrop="ALTER TABLE ".$val." DROP FOREIGN KEY ".$reg[1];
            				$resqldrop = $db->query($sqldrop);
            				if ($resqldrop)
            				{
            					print '<tr><td colspan="2">'.$sqldrop.";</td></tr>\n";
            				}
            				$createsql=preg_replace('/CONSTRAINT `'.$reg[1].'`/i','XXX',$createsql);
            				$i++;
            			}
            			$db->free($resql);
            		}
            		else
            		{
            			if ($db->lasterrno() != 'DB_ERROR_NOSUCHTABLE')
            			{
            				print '<tr><td colspan="2"><font  class="error">'.$sql.' : '.$db->lasterror()."</font></td></tr>\n";
            			}
            		}
            	}
            }
        }
    }

    /*
     *	Load sql files
     */
    if ($ok)
    {
        $dir = "mysql/migration/";		// We use mysql migration scripts whatever is database driver
		if (! empty($versionmodule)) $dir=dol_buildpath('/'.$versionmodule.'/sql/',0);

		// Clean last part to exclude minor version x.y.z -> x.y
        $newversionfrom=preg_replace('/(\.[0-9]+)$/i','.0',$versionfrom);
        $newversionto=preg_replace('/(\.[0-9]+)$/i','.0',$versionto);

        $filelist=array();
        $i = 0;
        $ok = 0;
        $from='^'.$newversionfrom;
        $to=$newversionto.'\.sql$';

        // Get files list
        $filesindir=array();
        $handle=opendir($dir);
        if (is_resource($handle))
        {
            while (($file = readdir($handle))!==false)
            {
            	if (preg_match('/\.sql$/i',$file)) $filesindir[]=$file;
            }
            sort($filesindir);
        }
        else
		{
            print '<div class="error">'.$langs->trans("ErrorCanNotReadDir",$dir).'</div>';
        }

        // Define which file to run
        foreach($filesindir as $file)
        {
            if (preg_match('/'.$from.'/i',$file))
            {
                $filelist[]=$file;
            }
            else if (preg_match('/'.$to.'/i',$file))	// First test may be false if we migrate from x.y.* to x.y.*
            {
                $filelist[]=$file;
            }
        }

        if (count($filelist) == 0)
        {
        	print '<div class="error">'.$langs->trans("ErrorNoMigrationFilesFoundForParameters").'</div>';
        }
		else
		{
	        // Loop on each migrate files
	        foreach($filelist as $file)
	        {
	        	print '<tr><td colspan="2"><hr></td></tr>';
	            print '<tr><td class="nowrap">'.$langs->trans("ChoosedMigrateScript").'</td><td align="right">'.$file.'</td></tr>'."\n";

	            // Run sql script
	            $ok=run_sql($dir.$file, 0, '', 1);

	            // Scan if there is migration scripts for modules htdocs/module/sql or htdocs/custom/module/sql
	            $modulesfile = array();
	            foreach ($conf->file->dol_document_root as $type => $dirroot)
	            {
	            	$handlemodule=@opendir($dirroot);		// $dirroot may be '..'
	            	if (is_resource($handlemodule))
	            	{
	            		while (($filemodule = readdir($handlemodule))!==false)
	            		{
	            			if (! preg_match('/\./',$filemodule) && is_dir($dirroot.'/'.$filemodule.'/sql'))	// We exclude filemodule that contains . (are not directories) and are not directories.
	            			{
	            				//print "Scan for ".$dirroot . '/' . $filemodule . '/sql/'.$file;
	            				if (is_file($dirroot . '/' . $filemodule . '/sql/'.$file))
	            				{
	            					$modulesfile[$dirroot . '/' . $filemodule . '/sql/'.$file] = '/' . $filemodule . '/sql/'.$file;
	            				}
	            			}
	            		}
	            		closedir($handlemodule);
	            	}
	            }

	            foreach ($modulesfile as $modulefilelong => $modulefileshort)
	            {
	            	print '<tr><td colspan="2"><hr></td></tr>';
	            	print '<tr><td class="nowrap">'.$langs->trans("ChoosedMigrateScript").' (external modules)</td><td align="right">'.$modulefileshort.'</td></tr>'."\n";

		            // Run sql script
	            	$okmodule=run_sql($modulefilelong, 0, '', 1);	// Note: Result of migration of external module should not decide if we continue migration of Dolibarr or not.
	            }

	        }
		}
    }

    print '</table>';

    if ($db->connected) $db->close();
}

if (empty($actiondone))
{
    print '<div class="error">'.$langs->trans("ErrorWrongParameters").'</div>';
}

$ret=0;
if (! $ok && isset($argv[1])) $ret=1;
dol_syslog("Exit ".$ret);

pFooter(((! $ok && empty($_GET["ignoreerrors"])) || $versionmodule),$setuplang);

if ($db->connected) $db->close();

// Return code if ran from command line
if ($ret) exit($ret);
