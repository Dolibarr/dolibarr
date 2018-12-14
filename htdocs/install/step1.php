<?php
/* Copyright (C) 2004-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2005-2011  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2016  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *		\file       htdocs/install/step1.php
 *		\ingroup	install
 *		\brief      Build conf file on disk
 */

define('DONOTLOADCONF',1);	// To avoid loading conf by file inc.php

include 'inc.php';

global $langs;

$action=GETPOST('action','aZ09')?GETPOST('action','aZ09'):(empty($argv[1])?'':$argv[1]);
$setuplang=GETPOST('selectlang','aZ09',3)?GETPOST('selectlang','aZ09',3):(empty($argv[2])?'auto':$argv[2]);
$langs->setDefaultLang($setuplang);

$langs->loadLangs(array("admin", "install", "errors"));

// Dolibarr pages directory
$main_dir = GETPOST('main_dir')?GETPOST('main_dir'):(empty($argv[3])?'':$argv[3]);
// Directory for generated documents (invoices, orders, ecm, etc...)
$main_data_dir = GETPOST('main_data_dir') ? GETPOST('main_data_dir') : (empty($argv[4])? ($main_dir . '/documents') :$argv[4]);
// Dolibarr root URL
$main_url = GETPOST('main_url')?GETPOST('main_url'):(empty($argv[5])?'':$argv[5]);
// Database login information
$userroot=GETPOST('db_user_root','alpha')?GETPOST('db_user_root','alpha'):(empty($argv[6])?'':$argv[6]);
$passroot=GETPOST('db_pass_root','none')?GETPOST('db_pass_root','none'):(empty($argv[7])?'':$argv[7]);
// Database server
$db_type=GETPOST('db_type','aZ09')?GETPOST('db_type','aZ09'):(empty($argv[8])?'':$argv[8]);
$db_host=GETPOST('db_host','alpha')?GETPOST('db_host','alpha'):(empty($argv[9])?'':$argv[9]);
$db_name=GETPOST('db_name','aZ09')?GETPOST('db_name','aZ09'):(empty($argv[10])?'':$argv[10]);
$db_user=GETPOST('db_user','alpha')?GETPOST('db_user','alpha'):(empty($argv[11])?'':$argv[11]);
$db_pass=GETPOST('db_pass','none')?GETPOST('db_pass','none'):(empty($argv[12])?'':$argv[12]);
$db_port=GETPOST('db_port','int')?GETPOST('db_port','int'):(empty($argv[13])?'':$argv[13]);
$db_prefix=GETPOST('db_prefix','aZ09')?GETPOST('db_prefix','aZ09'):(empty($argv[14])?'':$argv[14]);
$db_create_database = GETPOST('db_create_database','none')?GETPOST('db_create_database','none'):(empty($argv[15])?'':$argv[15]);
$db_create_user = GETPOST('db_create_user','none')?GETPOST('db_create_user','none'):(empty($argv[16])?'':$argv[16]);
// Force https
$main_force_https = ((GETPOST("main_force_https",'alpha') && (GETPOST("main_force_https",'alpha') == "on" || GETPOST("main_force_https",'alpha') == 1)) ? '1' : '0');
// Use alternative directory
$main_use_alt_dir = ((GETPOST("main_use_alt_dir",'alpha') == '' || (GETPOST("main_use_alt_dir",'alpha') == "on" || GETPOST("main_use_alt_dir",'alpha') == 1)) ? '' : '//');
// Alternative root directory name
$main_alt_dir_name = ((GETPOST("main_alt_dir_name",'alpha') && GETPOST("main_alt_dir_name",'alpha') != '') ? GETPOST("main_alt_dir_name",'alpha') : 'custom');

session_start();    // To be able to keep info into session (used for not losing password during navigation. The password must not transit through parameters)

// Save a flag to tell to restore input value if we go back
$_SESSION['dol_save_pass']=$db_pass;
//$_SESSION['dol_save_passroot']=$passroot;

// Now we load forced values from install.forced.php file.
$useforcedwizard=false;
$forcedfile="./install.forced.php";
if ($conffile == "/etc/dolibarr/conf.php") $forcedfile="/etc/dolibarr/install.forced.php";
if (@file_exists($forcedfile)) {
	$useforcedwizard = true;
	include_once $forcedfile;
	// If forced install is enabled, replace the post values. These are empty because form fields are disabled.
	if ($force_install_noedit) {
		$main_dir = detect_dolibarr_main_document_root();
		if (!empty($force_install_main_data_root)) {
			$main_data_dir = $force_install_main_data_root;
		} else {
			$main_data_dir = detect_dolibarr_main_data_root($main_dir);
		}
		$main_url = detect_dolibarr_main_url_root();

		if (!empty($force_install_databaserootlogin)) {
			$userroot = parse_database_login($force_install_databaserootlogin);
		}
		if (!empty($force_install_databaserootpass)) {
			$passroot = parse_database_pass($force_install_databaserootpass);
		}
	}
	if ($force_install_noedit == 2) {
		if (!empty($force_install_type)) {
			$db_type = $force_install_type;
		}
		if (!empty($force_install_dbserver)) {
			$db_host = $force_install_dbserver;
		}
		if (!empty($force_install_database)) {
			$db_name = $force_install_database;
		}
		if (!empty($force_install_databaselogin)) {
			$db_user = $force_install_databaselogin;
		}
		if (!empty($force_install_databasepass)) {
			$db_pass = $force_install_databasepass;
		}
		if (!empty($force_install_port)) {
			$db_port = $force_install_port;
		}
		if (!empty($force_install_prefix)) {
			$db_prefix = $force_install_prefix;
		}
		if (!empty($force_install_createdatabase)) {
			$db_create_database = $force_install_createdatabase;
		}
		if (!empty($force_install_createuser)) {
			$db_create_user = $force_install_createuser;
		}
		if (!empty($force_install_mainforcehttps)) {
			$main_force_https = $force_install_mainforcehttps;
		}
	}
}


$error = 0;


/*
 *	View
 */

dolibarr_install_syslog("--- step1: entering step1.php page");

pHeader($langs->trans("ConfigurationFile"),"step2");

// Test if we can run a first install process
if (! is_writable($conffile))
{
    print $langs->trans("ConfFileIsNotWritable",$conffiletoshow);
    pFooter(1,$setuplang,'jscheckparam');
    exit;
}


// Check parameters
$is_sqlite = false;
if (empty($db_type))
{
    print '<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("DatabaseType")).'</div>';
    $error++;
} else {
	$is_sqlite = ($db_type === 'sqlite' || $db_type === 'sqlite3' );
}
if (empty($db_host) && ! $is_sqlite)
{
    print '<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Server")).'</div>';
    $error++;
}
if (empty($db_name))
{
    print '<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("DatabaseName")).'</div>';
    $error++;
}
if (empty($db_user)  && ! $is_sqlite)
{
    print '<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Login")).'</div>';
    $error++;
}
if (! empty($db_port) && ! is_numeric($db_port))
{
    print '<div class="error">'.$langs->trans("ErrorBadValueForParameter",$db_port,$langs->transnoentities("Port")).'</div>';
    $error++;
}
if (! empty($db_prefix) && ! preg_match('/^[a-z0-9]+_$/i', $db_prefix))
{
	print '<div class="error">'.$langs->trans("ErrorBadValueForParameter",$db_prefix,$langs->transnoentities("DatabasePrefix")).'</div>';
	$error++;
}


// Remove last / into dans main_dir
if (substr($main_dir, dol_strlen($main_dir) -1) == "/")
{
    $main_dir = substr($main_dir, 0, dol_strlen($main_dir)-1);
}

// Remove last / into dans main_url
if (! empty($main_url) && substr($main_url, dol_strlen($main_url) -1) == "/")
{
    $main_url = substr($main_url, 0, dol_strlen($main_url)-1);
}

// Test database connection
if (! $error) {
    $result=@include_once $main_dir."/core/db/".$db_type.'.class.php';
    if ($result)
    {
        // If we require database or user creation we need to connect as root, so we need root login credentials
        if (!empty($db_create_database) && !$userroot) {
            print '<div class="error">'.$langs->trans("YouAskDatabaseCreationSoDolibarrNeedToConnect",$db_name).'</div>';
            print '<br>';
            print $langs->trans("BecauseConnectionFailedParametersMayBeWrong").'<br><br>';
            print $langs->trans("ErrorGoBackAndCorrectParameters");
            $error++;
        }
        if (!empty($db_create_user) && !$userroot) {
            print '<div class="error">'.$langs->trans("YouAskLoginCreationSoDolibarrNeedToConnect",$db_user).'</div>';
            print '<br>';
            print $langs->trans("BecauseConnectionFailedParametersMayBeWrong").'<br><br>';
            print $langs->trans("ErrorGoBackAndCorrectParameters");
            $error++;
        }

        // If we need root access
        if (!$error && (!empty($db_create_database) || !empty($db_create_user))) {
            $databasefortest=$db_name;
            if (!empty($db_create_database)) {
                if ($db_type == 'mysql' || $db_type == 'mysqli')
                {
                    $databasefortest='mysql';
                }
                elseif ($db_type == 'pgsql')
                {
                    $databasefortest='postgres';
                }
                else
                {
                    $databasefortest='master';
                }
            }
            //print $_POST["db_type"].",".$_POST["db_host"].",$userroot,$passroot,$databasefortest,".$_POST["db_port"];

            $db=getDoliDBInstance($db_type, $db_host, $userroot, $passroot, $databasefortest, $db_port);

            dol_syslog("databasefortest=" . $databasefortest . " connected=" . $db->connected . " database_selected=" . $db->database_selected, LOG_DEBUG);
            //print "databasefortest=".$databasefortest." connected=".$db->connected." database_selected=".$db->database_selected;

			if (empty($db_create_database) && $db->connected && !$db->database_selected) {
                print '<div class="error">'.$langs->trans("ErrorConnectedButDatabaseNotFound",$db_name).'</div>';
                print '<br>';
                if (! $db->connected) print $langs->trans("IfDatabaseNotExistsGoBackAndUncheckCreate").'<br><br>';
                print $langs->trans("ErrorGoBackAndCorrectParameters");
                $error++;
            } elseif ($db->error && ! (! empty($db_create_database) && $db->connected)) {
            	// Note: you may experience error here with message "No such file or directory" when mysql was installed for the first time but not yet launched.
                if ($db->error == "No such file or directory") print '<div class="error">'.$langs->trans("ErrorToConnectToMysqlCheckInstance").'</div>';
                else print '<div class="error">'.$db->error.'</div>';
                if (! $db->connected) print $langs->trans("BecauseConnectionFailedParametersMayBeWrong").'<br><br>';
                //print '<a href="#" onClick="javascript: history.back();">';
                print $langs->trans("ErrorGoBackAndCorrectParameters");
                //print '</a>';
                $error++;
            }
        }
        // If we need simple access
        if (!$error && (empty($db_create_database) && empty($db_create_user))) {
            $db=getDoliDBInstance($db_type, $db_host, $db_user, $db_pass, $db_name, $db_port);

            if ($db->error)
            {
                print '<div class="error">'.$db->error.'</div>';
                if (! $db->connected) print $langs->trans("BecauseConnectionFailedParametersMayBeWrong").'<br><br>';
                //print '<a href="#" onClick="javascript: history.back();">';
                print $langs->trans("ErrorGoBackAndCorrectParameters");
                //print '</a>';
                $error++;
            }
        }
    }
    else
    {
        print "<br>\nFailed to include_once(\"".$main_dir."/core/db/".$db_type.".class.php\")<br>\n";
        print '<div class="error">'.$langs->trans("ErrorWrongValueForParameter",$langs->transnoentities("WebPagesDirectory")).'</div>';
        //print '<a href="#" onClick="javascript: history.back();">';
        print $langs->trans("ErrorGoBackAndCorrectParameters");
        //print '</a>';
        $error++;
    }
}

else
{
    if (isset($db)) print $db->lasterror();
    if (isset($db) && ! $db->connected) print '<br>'.$langs->trans("BecauseConnectionFailedParametersMayBeWrong").'<br><br>';
    print $langs->trans("ErrorGoBackAndCorrectParameters");
    $error++;
}

if (! $error && $db->connected)
{
    if (! empty($db_create_database)) {
        $result=$db->select_db($db_name);
        if ($result)
        {
            print '<div class="error">'.$langs->trans("ErrorDatabaseAlreadyExists", $db_name).'</div>';
            print $langs->trans("IfDatabaseExistsGoBackAndCheckCreate").'<br><br>';
            print $langs->trans("ErrorGoBackAndCorrectParameters");
            $error++;
        }
    }
}

// Define $defaultCharacterSet and $defaultDBSortingCollation
if (! $error && $db->connected)
{
    if (!empty($db_create_database))    // If we create database, we force default value
    {
        // Default values come from the database handler

        $defaultCharacterSet=$db->forcecharset;
    	$defaultDBSortingCollation=$db->forcecollate;
    }
    else	// If already created, we take current value
    {
        $defaultCharacterSet=$db->getDefaultCharacterSetDatabase();
        $defaultDBSortingCollation=$db->getDefaultCollationDatabase();
    }

    // Force to avoid utf8mb4 because index on field char 255 reach limit of 767 char for indexes (example with mysql 5.6.34 = mariadb 10.0.29)
    // TODO Remove this when utf8mb4 is supported
    if ($defaultCharacterSet == 'utf8mb4' || $defaultDBSortingCollation == 'utf8mb4_unicode_ci')
    {
        $defaultCharacterSet = 'utf8';
        $defaultDBSortingCollation = 'utf8_unicode_ci';
    }

    print '<input type="hidden" name="dolibarr_main_db_character_set" value="'.$defaultCharacterSet.'">';
    print '<input type="hidden" name="dolibarr_main_db_collation" value="'.$defaultDBSortingCollation.'">';
    $db_character_set=$defaultCharacterSet;
    $db_collation=$defaultDBSortingCollation;
    dolibarr_install_syslog("step1: db_character_set=" . $db_character_set . " db_collation=" . $db_collation);
}


// Create config file
if (! $error && $db->connected && $action == "set")
{
    umask(0);
    if (is_array($_POST))
    {
        foreach($_POST as $key => $value)
        {
            if (! preg_match('/^db_pass/i', $key)) {
    			dolibarr_install_syslog("step1: choice for " . $key . " = " . $value);
    		}
        }
    }

    // Show title of step
    print '<h3><img class="valigntextbottom" src="../theme/common/octicons/build/svg/gear.svg" width="20" alt="Configuration"> '.$langs->trans("ConfigurationFile").'</h3>';
    print '<table cellspacing="0" width="100%" cellpadding="1" border="0">';

    // Check parameter main_dir
    if (! $error)
    {
        if (! is_dir($main_dir))
        {
            dolibarr_install_syslog("step1: directory '" . $main_dir . "' is unavailable or can't be accessed");

            print "<tr><td>";
            print $langs->trans("ErrorDirDoesNotExists",$main_dir).'<br>';
            print $langs->trans("ErrorWrongValueForParameter",$langs->trans("WebPagesDirectory")).'<br>';
            print $langs->trans("ErrorGoBackAndCorrectParameters").'<br><br>';
            print '</td><td>';
            print $langs->trans("Error");
            print "</td></tr>";
            $error++;
        }
    }

    if (! $error)
    {
        dolibarr_install_syslog("step1: directory '" . $main_dir . "' exists");
    }


    // Create subdirectory main_data_dir
    if (! $error)
    {
        // Create directory for documents
        if (! is_dir($main_data_dir))
        {
            dol_mkdir($main_data_dir);
        }

        if (! is_dir($main_data_dir))
        {
            print "<tr><td>".$langs->trans("ErrorDirDoesNotExists",$main_data_dir);
            print ' '.$langs->trans("YouMustCreateItAndAllowServerToWrite");
            print '</td><td>';
            print '<span class="error">'.$langs->trans("Error").'</span>';
            print "</td></tr>";
            print '<tr><td colspan="2"><br>'.$langs->trans("CorrectProblemAndReloadPage",$_SERVER['PHP_SELF'].'?testget=ok').'</td></tr>';
            $error++;
        }
        else
        {
            // Create .htaccess file in document directory
            $pathhtaccess=$main_data_dir.'/.htaccess';
            if (! file_exists($pathhtaccess))
            {
                dolibarr_install_syslog("step1: .htaccess file did not exist, we created it in '" . $main_data_dir . "'");
                $handlehtaccess=@fopen($pathhtaccess,'w');
                if ($handlehtaccess)
                {
                    fwrite($handlehtaccess,'Order allow,deny'."\n");
                    fwrite($handlehtaccess,'Deny from all'."\n");

                    fclose($handlehtaccess);
                    dolibarr_install_syslog("step1: .htaccess file created");
                }
            }

            // Documents are stored above the web pages root to prevent being downloaded without authentification
            $dir=array();
            $dir[] = $main_data_dir."/mycompany";
            $dir[] = $main_data_dir."/medias";
            $dir[] = $main_data_dir."/users";
            $dir[] = $main_data_dir."/facture";
            $dir[] = $main_data_dir."/propale";
            $dir[] = $main_data_dir."/ficheinter";
            $dir[] = $main_data_dir."/produit";
            $dir[] = $main_data_dir."/doctemplates";

            // Loop on each directory of dir [] to create them if they do not exist
            $num=count($dir);
            for ($i = 0; $i < $num; $i++)
            {
                if (is_dir($dir[$i]))
                {
                    dolibarr_install_syslog("step1: directory '" . $dir[$i] . "' exists");
                }
                else
                {
                    if (dol_mkdir($dir[$i]) < 0)
                    {
                        print "<tr><td>";
                        print "Failed to create directory: ".$dir[$i];
                        print '</td><td>';
                        print $langs->trans("Error");
                        print "</td></tr>";
                        $error++;
                    }
                    else
                    {
                        dolibarr_install_syslog("step1: directory '" . $dir[$i] . "' created");
                    }
                }
            }

            require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

            // Copy directory medias
            $srcroot=$main_dir.'/install/medias';
            $destroot=$main_data_dir.'/medias';
            dolCopyDir($srcroot, $destroot, 0, 0);

            if ($error)
            {
                print "<tr><td>".$langs->trans("ErrorDirDoesNotExists",$main_data_dir);
                print ' '.$langs->trans("YouMustCreateItAndAllowServerToWrite");
                print '</td><td>';
                print '<span class="error">'.$langs->trans("Error").'</span>';
                print "</td></tr>";
                print '<tr><td colspan="2"><br>'.$langs->trans("CorrectProblemAndReloadPage",$_SERVER['PHP_SELF'].'?testget=ok').'</td></tr>';
            }
            else
            {
            	//ODT templates
            	$srcroot=$main_dir.'/install/doctemplates';
            	$destroot=$main_data_dir.'/doctemplates';
            	$docs=array(
            		'contracts' => 'contract',
            		'invoices' => 'invoice',
            		'orders' => 'order',
            		'products' => 'product',
            		'projects' => 'project',
            		'proposals' => 'proposal',
            		'shipments' => 'shipment',
            		'supplier_proposals' => 'supplier_proposal',
            		'tasks' => 'task_summary',
            		'thirdparties' => 'thirdparty',
            		'usergroups' => 'usergroups',
            		'users' => 'user',
            	);
            	foreach($docs as $cursordir => $cursorfile)
            	{
            		$src=$srcroot.'/'.$cursordir.'/template_'.$cursorfile.'.odt';
            		$dirodt=$destroot.'/'.$cursordir;
            		$dest=$dirodt.'/template_'.$cursorfile.'.odt';

            		dol_mkdir($dirodt);
            		$result=dol_copy($src,$dest,0,0);
            		if ($result < 0)
            		{
            			print '<tr><td colspan="2"><br>'.$langs->trans('ErrorFailToCopyFile',$src,$dest).'</td></tr>';
            		}
            	}
            }
        }
    }

    // Table prefix
    $main_db_prefix = (! empty($db_prefix) ? $db_prefix : 'llx_');

    // Write conf file on disk
    if (! $error)
    {
        // Save old conf file on disk
        if (file_exists("$conffile"))
        {
            // We must ignore errors as an existing old file may already exist and not be replaceable or
            // the installer (like for ubuntu) may not have permission to create another file than conf.php.
            // Also no other process must be able to read file or we expose the new file, so content with password.
            @dol_copy($conffile, $conffile.'.old', '0400');
        }

        $error+=write_conf_file($conffile);
    }

    // Create database and admin user database
    if (! $error)
    {
        // We reload configuration file
        conf($dolibarr_main_document_root);

        print '<tr><td>';
        print $langs->trans("ConfFileReload");
        print '</td>';
        print '<td><img src="../theme/eldy/img/tick.png" alt="Ok"></td></tr>';

        // Create database user if requested
        if (isset($db_create_user) && ($db_create_user == "1" || $db_create_user == "on")) {
            dolibarr_install_syslog("step1: create database user: " . $dolibarr_main_db_user);

            //print $conf->db->host." , ".$conf->db->name." , ".$conf->db->user." , ".$conf->db->port;
            $databasefortest=$conf->db->name;
            if ($conf->db->type == 'mysql' || $conf->db->type == 'mysqli')
            {
                $databasefortest='mysql';
            }
            else if ($conf->db->type == 'pgsql')
            {
                $databasefortest='postgres';
            }
            else if ($conf->db->type == 'mssql')
            {
                $databasefortest='master';
            }

            // Check database connection

            $db=getDoliDBInstance($conf->db->type,$conf->db->host,$userroot,$passroot,$databasefortest,$conf->db->port);

            if ($db->error)
            {
                print '<div class="error">'.$db->error.'</div>';
                $error++;
            }

            if (! $error)
            {
                if ($db->connected)
                {
                    $resultbis = 1;

                    // Create user
                    $result=$db->DDLCreateUser($dolibarr_main_db_host, $dolibarr_main_db_user, $dolibarr_main_db_pass, $dolibarr_main_db_name);
                    // Create user bis
                    if ($databasefortest == 'mysql')
                    {
                        if (! in_array($dolibarr_main_db_host, array('127.0.0.1', '::1', 'localhost', 'localhost.local')))
                        {
                            $resultbis=$db->DDLCreateUser('%', $dolibarr_main_db_user, $dolibarr_main_db_pass, $dolibarr_main_db_name);
                        }
                    }

                    if ($result > 0 && $resultbis > 0)
                    {

                        print '<tr><td>';
                        print $langs->trans("UserCreation").' : ';
                        print $dolibarr_main_db_user;
                        print '</td>';
                        print '<td><img src="../theme/eldy/img/tick.png" alt="Ok"></td></tr>';
                    }
                    else
                    {
                        if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS'
                        || $db->errno() == 'DB_ERROR_KEY_NAME_ALREADY_EXISTS'
                        || $db->errno() == 'DB_ERROR_USER_ALREADY_EXISTS')
                        {
                            dolibarr_install_syslog("step1: user already exists");
                            print '<tr><td>';
                            print $langs->trans("UserCreation").' : ';
                            print $dolibarr_main_db_user;
                            print '</td>';
                            print '<td>'.$langs->trans("LoginAlreadyExists").'</td></tr>';
                        }
                        else
                        {
                            dolibarr_install_syslog("step1: failed to create user", LOG_ERR);
                            print '<tr><td>';
                            print $langs->trans("UserCreation").' : ';
                            print $dolibarr_main_db_user;
                            print '</td>';
                            print '<td>'.$langs->trans("Error").': '.$db->errno().' '.$db->error()."</td></tr>";
                        }
                    }

                    $db->close();
                }
                else
                {
                    print '<tr><td>';
                    print $langs->trans("UserCreation").' : ';
                    print $dolibarr_main_db_user;
                    print '</td>';
                    print '<td><img src="../theme/eldy/img/error.png" alt="Error"></td>';
                    print '</tr>';

                    // warning message due to connection failure
                    print '<tr><td colspan="2"><br>';
                    print $langs->trans("YouAskDatabaseCreationSoDolibarrNeedToConnect",$dolibarr_main_db_user,$dolibarr_main_db_host,$userroot);
                    print '<br>';
                    print $langs->trans("BecauseConnectionFailedParametersMayBeWrong").'<br><br>';
                    print $langs->trans("ErrorGoBackAndCorrectParameters").'<br><br>';
                    print '</td></tr>';

                    $error++;
                }
            }
        }   // end of user account creation


        // If database creation was asked, we create it
        if (!$error && (isset($db_create_database) && ($db_create_database == "1" || $db_create_database == "on"))) {
            dolibarr_install_syslog("step1: create database: " . $dolibarr_main_db_name . " " . $dolibarr_main_db_character_set . " " . $dolibarr_main_db_collation . " " . $dolibarr_main_db_user);
        	$newdb=getDoliDBInstance($conf->db->type,$conf->db->host,$userroot,$passroot,'',$conf->db->port);
            //print 'eee'.$conf->db->type." ".$conf->db->host." ".$userroot." ".$passroot." ".$conf->db->port." ".$newdb->connected." ".$newdb->forcecharset;exit;

            if ($newdb->connected)
            {
                $result=$newdb->DDLCreateDb($dolibarr_main_db_name, $dolibarr_main_db_character_set, $dolibarr_main_db_collation, $dolibarr_main_db_user);

                if ($result)
                {
                    print '<tr><td>';
                    print $langs->trans("DatabaseCreation")." (".$langs->trans("User")." ".$userroot.") : ";
                    print $dolibarr_main_db_name;
                    print '</td>';
                    print '<td><img src="../theme/eldy/img/tick.png" alt="Ok"></td></tr>';

                    $newdb->select_db($dolibarr_main_db_name);
                    $check1=$newdb->getDefaultCharacterSetDatabase();
                    $check2=$newdb->getDefaultCollationDatabase();
                    dolibarr_install_syslog('step1: new database is using charset=' . $check1 . ' collation=' . $check2);

                    // If values differs, we save conf file again
                    //if ($check1 != $dolibarr_main_db_character_set) dolibarr_install_syslog('step1: value for character_set is not the one asked for database creation', LOG_WARNING);
                    //if ($check2 != $dolibarr_main_db_collation)     dolibarr_install_syslog('step1: value for collation is not the one asked for database creation', LOG_WARNING);
                }
                else
                {
                    // warning message
                    print '<tr><td colspan="2"><br>';
                    print $langs->trans("ErrorFailedToCreateDatabase",$dolibarr_main_db_name).'<br>';
                    print $newdb->lasterror().'<br>';
                    print $langs->trans("IfDatabaseExistsGoBackAndCheckCreate");
                    print '<br>';
                    print '</td></tr>';

                    dolibarr_install_syslog('step1: failed to create database ' . $dolibarr_main_db_name . ' ' . $newdb->lasterrno() . ' ' . $newdb->lasterror(), LOG_ERR);
                    $error++;
                }
                $newdb->close();
            }
            else {
                print '<tr><td>';
                print $langs->trans("DatabaseCreation")." (".$langs->trans("User")." ".$userroot.") : ";
                print $dolibarr_main_db_name;
                print '</td>';
                print '<td><img src="../theme/eldy/img/error.png" alt="Error"></td>';
                print '</tr>';

                // warning message
                print '<tr><td colspan="2"><br>';
                print $langs->trans("YouAskDatabaseCreationSoDolibarrNeedToConnect",$dolibarr_main_db_user,$dolibarr_main_db_host,$userroot);
                print '<br>';
                print $langs->trans("BecauseConnectionFailedParametersMayBeWrong").'<br><br>';
                print $langs->trans("ErrorGoBackAndCorrectParameters").'<br><br>';
                print '</td></tr>';

                $error++;
            }
        }   // end of create database


        // We test access with dolibarr database user (not admin)
        if (! $error)
        {
            dolibarr_install_syslog("step1: connection type=" . $conf->db->type . " on host=" . $conf->db->host . " port=" . $conf->db->port . " user=" . $conf->db->user . " name=" . $conf->db->name);
            //print "connexion de type=".$conf->db->type." sur host=".$conf->db->host." port=".$conf->db->port." user=".$conf->db->user." name=".$conf->db->name;

            $db=getDoliDBInstance($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);

            if ($db->connected)
            {
                dolibarr_install_syslog("step1: connection to server by user " . $conf->db->user . " ok");
                print "<tr><td>";
                print $langs->trans("ServerConnection")." (".$langs->trans("User")." ".$conf->db->user.") : ";
                print $dolibarr_main_db_host;
                print "</td><td>";
                print '<img src="../theme/eldy/img/tick.png" alt="Ok">';
                print "</td></tr>";

                // server access ok, basic access ok
                if ($db->database_selected)
                {
                    dolibarr_install_syslog("step1: connection to database " . $conf->db->name . " by user " . $conf->db->user . " ok");
                    print "<tr><td>";
                    print $langs->trans("DatabaseConnection")." (".$langs->trans("User")." ".$conf->db->user.") : ";
                    print $dolibarr_main_db_name;
                    print "</td><td>";
                    print '<img src="../theme/eldy/img/tick.png" alt="Ok">';
                    print "</td></tr>";

                    $error = 0;
                }
                else
                {
                    dolibarr_install_syslog("step1: connection to database " . $conf->db->name . " by user " . $conf->db->user . " failed", LOG_ERR);
                    print "<tr><td>";
                    print $langs->trans("DatabaseConnection")." (".$langs->trans("User")." ".$conf->db->user.") : ";
                    print $dolibarr_main_db_name;
                    print '</td><td>';
                    print '<img src="../theme/eldy/img/error.png" alt="Error">';
                    print "</td></tr>";

                    // warning message
                    print '<tr><td colspan="2"><br>';
                    print $langs->trans('CheckThatDatabasenameIsCorrect',$dolibarr_main_db_name).'<br>';
                    print $langs->trans('IfAlreadyExistsCheckOption').'<br>';
                    print $langs->trans("ErrorGoBackAndCorrectParameters").'<br><br>';
                    print '</td></tr>';

                    $error++;
                }
            }
            else
            {
                dolibarr_install_syslog("step1: connection to server by user " . $conf->db->user . " failed", LOG_ERR);
                print "<tr><td>";
                print $langs->trans("ServerConnection")." (".$langs->trans("User")." ".$conf->db->user.") : ";
                print $dolibarr_main_db_host;
                print '</td><td>';
                print '<img src="../theme/eldy/img/error.png" alt="Error">';
                print "</td></tr>";

                // warning message
                print '<tr><td colspan="2"><br>';
                print $langs->trans("ErrorConnection",$conf->db->host,$conf->db->name,$conf->db->user);
                print $langs->trans('IfLoginDoesNotExistsCheckCreateUser').'<br>';
                print $langs->trans("ErrorGoBackAndCorrectParameters").'<br><br>';
                print '</td></tr>';

                $error++;
            }
        }
    }

    print '</table>';
}

?>

<script type="text/javascript">
function jsinfo()
{
	ok=true;

	//alert('<?php echo dol_escape_js($langs->transnoentities("NextStepMightLastALongTime")); ?>');

	document.getElementById('nextbutton').style.visibility="hidden";
	document.getElementById('pleasewait').style.visibility="visible";

	return ok;
}
</script>

<?php

$ret=0;
if ($error && isset($argv[1])) $ret=1;
dolibarr_install_syslog("Exit ".$ret);

dolibarr_install_syslog("--- step1: end");

pFooter($error?1:0,$setuplang,'jsinfo',1);

// Return code if ran from command line
if ($ret) exit($ret);


/**
 *  Create main file. No particular permissions are set by installer.
 *
 *  @param  string		$mainfile       Full path name of main file to generate/update
 *  @param	string		$main_dir		Full path name to main.inc.php file
 *  @return	void
 */
function write_main_file($mainfile,$main_dir)
{
    $fp = @fopen("$mainfile", "w");
    if($fp)
    {
        clearstatcache();
        fputs($fp, '<?php'."\n");
        fputs($fp, "// Wrapper to include main into htdocs\n");
        fputs($fp, "include_once '".$main_dir."/main.inc.php';\n");
        fclose($fp);
    }
}


/**
 *  Create master file. No particular permissions are set by installer.
 *
 *  @param  string		$masterfile     Full path name of master file to generate/update
 *  @param	string		$main_dir		Full path name to master.inc.php file
 *  @return	void
 */
function write_master_file($masterfile,$main_dir)
{
    $fp = @fopen("$masterfile", "w");
    if($fp)
    {
        clearstatcache();
        fputs($fp, '<?php'."\n");
        fputs($fp, "// Wrapper to include master into htdocs\n");
        fputs($fp, "include_once '".$main_dir."/master.inc.php';\n");
        fclose($fp);
    }
}


/**
 *  Save configuration file. No particular permissions are set by installer.
 *
 *  @param  string		$conffile        Path to conf file to generate/update
 *  @return	integer
 */
function write_conf_file($conffile)
{
    global $conf,$langs;
    global $main_url,$main_dir,$main_data_dir,$main_force_https,$main_use_alt_dir,$main_alt_dir_name,$main_db_prefix;
    global $dolibarr_main_url_root,$dolibarr_main_document_root,$dolibarr_main_data_root,$dolibarr_main_db_host;
    global $dolibarr_main_db_port,$dolibarr_main_db_name,$dolibarr_main_db_user,$dolibarr_main_db_pass;
    global $dolibarr_main_db_type,$dolibarr_main_db_character_set,$dolibarr_main_db_collation,$dolibarr_main_authentication;
    global $db_host,$db_port,$db_name,$db_user,$db_pass,$db_type,$db_character_set,$db_collation;
    global $conffile,$conffiletoshow,$conffiletoshowshort;
    global $force_dolibarr_lib_ADODB_PATH, $force_dolibarr_lib_NUSOAP_PATH;
    global $force_dolibarr_lib_TCPDF_PATH, $force_dolibarr_lib_FPDI_PATH;
    global $force_dolibarr_lib_PHPEXCEL_PATH, $force_dolibarr_lib_GEOIP_PATH;
    global $force_dolibarr_lib_ODTPHP_PATH, $force_dolibarr_lib_ODTPHP_PATHTOPCLZIP;
    global $force_dolibarr_js_CKEDITOR, $force_dolibarr_js_JQUERY, $force_dolibarr_js_JQUERY_UI, $force_dolibarr_js_JQUERY_FLOT;
    global $force_dolibarr_font_DOL_DEFAULT_TTF, $force_dolibarr_font_DOL_DEFAULT_TTF_BOLD;

    $error=0;

    $key = md5(uniqid(mt_rand(), true)); // Generate random hash

    $fp = fopen("$conffile", "w");
    if($fp)
    {
        clearstatcache();

        fputs($fp,'<?php'."\n");
        fputs($fp,'//'."\n");
        fputs($fp,'// File generated by Dolibarr installer '.DOL_VERSION.' on '.dol_print_date(dol_now(),'')."\n");
        fputs($fp,'//'."\n");
        fputs($fp,'// Take a look at conf.php.example file for an example of '.$conffiletoshowshort.' file'."\n");
        fputs($fp,'// and explanations for all possibles parameters.'."\n");
        fputs($fp,'//'."\n");

        fputs($fp, '$dolibarr_main_url_root=\''.str_replace("'","\'",trim($main_url)).'\';');
        fputs($fp,"\n");

        fputs($fp, '$dolibarr_main_document_root=\''.str_replace("'","\'",trim($main_dir)).'\';');
        fputs($fp,"\n");

        fputs($fp, $main_use_alt_dir.'$dolibarr_main_url_root_alt=\''.str_replace("'","\'",trim("/".$main_alt_dir_name)).'\';');
        fputs($fp,"\n");

        fputs($fp, $main_use_alt_dir.'$dolibarr_main_document_root_alt=\''.str_replace("'","\'",trim($main_dir."/".$main_alt_dir_name)).'\';');
		fputs($fp,"\n");

		fputs($fp, '$dolibarr_main_data_root=\''.str_replace("'","\'",trim($main_data_dir)).'\';');
		fputs($fp,"\n");

		fputs($fp, '$dolibarr_main_db_host=\''.str_replace("'","\'",trim($db_host)).'\';');
		fputs($fp,"\n");

		fputs($fp, '$dolibarr_main_db_port=\''.str_replace("'","\'",trim($db_port)).'\';');
		fputs($fp,"\n");

		fputs($fp, '$dolibarr_main_db_name=\''.str_replace("'","\'",trim($db_name)).'\';');
		fputs($fp,"\n");

		fputs($fp, '$dolibarr_main_db_prefix=\''.str_replace("'","\'",trim($main_db_prefix)).'\';');
		fputs($fp,"\n");

		fputs($fp, '$dolibarr_main_db_user=\''.str_replace("'","\'",trim($db_user)).'\';');
		fputs($fp,"\n");
		fputs($fp, '$dolibarr_main_db_pass=\''.str_replace("'","\'",trim($db_pass)).'\';');
		fputs($fp,"\n");

		fputs($fp, '$dolibarr_main_db_type=\''.str_replace("'","\'",trim($db_type)).'\';');
		fputs($fp,"\n");

		fputs($fp, '$dolibarr_main_db_character_set=\''.str_replace("'","\'",trim($db_character_set)).'\';');
		fputs($fp,"\n");

		fputs($fp, '$dolibarr_main_db_collation=\''.str_replace("'","\'",trim($db_collation)).'\';');
		fputs($fp,"\n");

		/* Authentication */
		fputs($fp, '// Authentication settings');
        fputs($fp,"\n");

		fputs($fp, '$dolibarr_main_authentication=\'dolibarr\';');
		fputs($fp,"\n\n");

        fputs($fp, '//$dolibarr_main_demo=\'autologin,autopass\';');
        fputs($fp,"\n");

		fputs($fp, '// Security settings');
        fputs($fp,"\n");

        fputs($fp, '$dolibarr_main_prod=\'0\';');
        fputs($fp,"\n");

        fputs($fp, '$dolibarr_main_force_https=\''.$main_force_https.'\';');
		fputs($fp,"\n");

        fputs($fp, '$dolibarr_main_restrict_os_commands=\'mysqldump, mysql, pg_dump, pgrestore\';');
		fputs($fp,"\n");

        fputs($fp, '$dolibarr_nocsrfcheck=\'0\';');
        fputs($fp,"\n");

		fputs($fp, '$dolibarr_main_cookie_cryptkey=\''.$key.'\';');
		fputs($fp,"\n");

		fputs($fp, '$dolibarr_mailing_limit_sendbyweb=\'0\';');
        fputs($fp,"\n");

        // Write params to overwrites default lib path
        fputs($fp,"\n");
        if (empty($force_dolibarr_lib_FPDF_PATH)) { fputs($fp, '//'); $force_dolibarr_lib_FPDF_PATH=''; }
        fputs($fp, '$dolibarr_lib_FPDF_PATH=\''.$force_dolibarr_lib_FPDF_PATH.'\';');
        fputs($fp,"\n");
        if (empty($force_dolibarr_lib_TCPDF_PATH)) { fputs($fp, '//'); $force_dolibarr_lib_TCPDF_PATH=''; }
        fputs($fp, '$dolibarr_lib_TCPDF_PATH=\''.$force_dolibarr_lib_TCPDF_PATH.'\';');
        fputs($fp,"\n");
        if (empty($force_dolibarr_lib_FPDI_PATH)) { fputs($fp, '//'); $force_dolibarr_lib_FPDI_PATH=''; }
        fputs($fp, '$dolibarr_lib_FPDI_PATH=\''.$force_dolibarr_lib_FPDI_PATH.'\';');
        fputs($fp,"\n");
        if (empty($force_dolibarr_lib_TCPDI_PATH)) { fputs($fp, '//'); $force_dolibarr_lib_TCPDI_PATH=''; }
        fputs($fp, '$dolibarr_lib_TCPDI_PATH=\''.$force_dolibarr_lib_TCPDI_PATH.'\';');
        fputs($fp,"\n");
        if (empty($force_dolibarr_lib_ADODB_PATH)) { fputs($fp, '//'); $force_dolibarr_lib_ADODB_PATH=''; }
        fputs($fp, '$dolibarr_lib_ADODB_PATH=\''.$force_dolibarr_lib_ADODB_PATH.'\';');
        fputs($fp,"\n");
        if (empty($force_dolibarr_lib_GEOIP_PATH)) { fputs($fp, '//'); $force_dolibarr_lib_GEOIP_PATH=''; }
        fputs($fp, '$dolibarr_lib_GEOIP_PATH=\''.$force_dolibarr_lib_GEOIP_PATH.'\';');
        fputs($fp,"\n");
        if (empty($force_dolibarr_lib_NUSOAP_PATH)) { fputs($fp, '//'); $force_dolibarr_lib_NUSOAP_PATH=''; }
        fputs($fp, '$dolibarr_lib_NUSOAP_PATH=\''.$force_dolibarr_lib_NUSOAP_PATH.'\';');
        fputs($fp,"\n");
        if (empty($force_dolibarr_lib_PHPEXCEL_PATH)) { fputs($fp, '//'); $force_dolibarr_lib_PHPEXCEL_PATH=''; }
        fputs($fp, '$dolibarr_lib_PHPEXCEL_PATH=\''.$force_dolibarr_lib_PHPEXCEL_PATH.'\';');
        fputs($fp,"\n");
        if (empty($force_dolibarr_lib_ODTPHP_PATH)) { fputs($fp, '//'); $force_dolibarr_lib_ODTPHP_PATH=''; }
        fputs($fp, '$dolibarr_lib_ODTPHP_PATH=\''.$force_dolibarr_lib_ODTPHP_PATH.'\';');
        fputs($fp,"\n");
        if (empty($force_dolibarr_lib_ODTPHP_PATHTOPCLZIP)) { fputs($fp, '//'); $force_dolibarr_lib_ODTPHP_PATHTOPCLZIP=''; }
        fputs($fp, '$dolibarr_lib_ODTPHP_PATHTOPCLZIP=\''.$force_dolibarr_lib_ODTPHP_PATHTOPCLZIP.'\';');
        fputs($fp,"\n");
        if (empty($force_dolibarr_js_CKEDITOR)) { fputs($fp, '//'); $force_dolibarr_js_CKEDITOR=''; }
        fputs($fp, '$dolibarr_js_CKEDITOR=\''.$force_dolibarr_js_CKEDITOR.'\';');
        fputs($fp,"\n");
        if (empty($force_dolibarr_js_JQUERY)) { fputs($fp, '//'); $force_dolibarr_js_JQUERY=''; }
        fputs($fp, '$dolibarr_js_JQUERY=\''.$force_dolibarr_js_JQUERY.'\';');
        fputs($fp,"\n");
        if (empty($force_dolibarr_js_JQUERY_UI)) { fputs($fp, '//'); $force_dolibarr_js_JQUERY_UI=''; }
        fputs($fp, '$dolibarr_js_JQUERY_UI=\''.$force_dolibarr_js_JQUERY_UI.'\';');
        fputs($fp,"\n");
        if (empty($force_dolibarr_js_JQUERY_FLOT)) { fputs($fp, '//'); $force_dolibarr_js_JQUERY_FLOT=''; }
        fputs($fp, '$dolibarr_js_JQUERY_FLOT=\''.$force_dolibarr_js_JQUERY_FLOT.'\';');
        fputs($fp,"\n");

        // Write params to overwrites default font path
        fputs($fp,"\n");
        if (empty($force_dolibarr_font_DOL_DEFAULT_TTF)) { fputs($fp, '//'); $force_dolibarr_font_DOL_DEFAULT_TTF=''; }
   		fputs($fp, '$dolibarr_font_DOL_DEFAULT_TTF=\''.$force_dolibarr_font_DOL_DEFAULT_TTF.'\';');
        fputs($fp,"\n");
        if (empty($force_dolibarr_font_DOL_DEFAULT_TTF_BOLD)) { fputs($fp, '//'); $force_dolibarr_font_DOL_DEFAULT_TTF_BOLD=''; }
        fputs($fp, '$dolibarr_font_DOL_DEFAULT_TTF_BOLD=\''.$force_dolibarr_font_DOL_DEFAULT_TTF_BOLD.'\';');
        fputs($fp,"\n");

		fclose($fp);

		if (file_exists("$conffile"))
		{
			include $conffile;	// force config reload, do not put include_once
			conf($dolibarr_main_document_root);

			print "<tr><td>";
			print $langs->trans("SaveConfigurationFile");
			print ' <strong>'.$conffile.'</strong>';
			print "</td><td>";
			print '<img src="../theme/eldy/img/tick.png" alt="Ok">';
			print "</td></tr>";
		}
		else
		{
			$error++;
		}
	}

	return $error;
}
