<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2004       Sebastien DiCintio      <sdicintio@ressource-toi.org>
 * Copyright (C) 2007-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2016       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * 	\file       htdocs/install/inc.php
 * 	\ingroup	core
 *	\brief      File that define environment for support pages
 */

// Just to define version DOL_VERSION
if (! defined('DOL_INC_FOR_VERSION_ERROR')) define('DOL_INC_FOR_VERSION_ERROR', '1');
require_once '../filefunc.inc.php';



// Define DOL_DOCUMENT_ROOT and ADODB_PATH used for install/upgrade process
if (! defined('DOL_DOCUMENT_ROOT'))	    define('DOL_DOCUMENT_ROOT', '..');
if (! defined('ADODB_PATH'))
{
    $foundpath=DOL_DOCUMENT_ROOT .'/includes/adodbtime/';
    if (! is_dir($foundpath)) $foundpath='/usr/share/php/adodb/';
    define('ADODB_PATH', $foundpath);
}

require_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once ADODB_PATH.'adodb-time.inc.php';

// Avoid warnings with strict mode E_STRICT
$conf = new stdClass(); // instantiate $conf explicitely
$conf->global	= new stdClass();
$conf->file		= new stdClass();
$conf->db		= new stdClass();
$conf->syslog	= new stdClass();

// Force $_REQUEST["logtohtml"]
$_REQUEST["logtohtml"]=1;

// Correction PHP_SELF (ex pour apache via caudium) car PHP_SELF doit valoir URL relative
// et non path absolu.
if (isset($_SERVER["DOCUMENT_URI"]) && $_SERVER["DOCUMENT_URI"])
{
    $_SERVER["PHP_SELF"]=$_SERVER["DOCUMENT_URI"];
}


$includeconferror='';


// Define vars
$conffiletoshowshort = "conf.php";
// Define localization of conf file
$conffile = "../conf/conf.php";
$conffiletoshow = "htdocs/conf/conf.php";
// For debian/redhat like systems
//$conffile = "/etc/dolibarr/conf.php";
//$conffiletoshow = "/etc/dolibarr/conf.php";


// Load conf file if it is already defined
if (! defined('DONOTLOADCONF') && file_exists($conffile) && filesize($conffile) > 8) // Test on filesize is to ensure that conf file is more that an empty template with just <?php in first line
{
    $result=include_once $conffile;	// Load conf file
    if ($result)
    {
		if (empty($dolibarr_main_db_type)) $dolibarr_main_db_type='mysqli';	// For backward compatibility

        //Mysql driver support has been removed in favor of mysqli
        if ($dolibarr_main_db_type == 'mysql') {
            $dolibarr_main_db_type = 'mysqli';
        }

		if (empty($dolibarr_main_db_port) && ($dolibarr_main_db_type=='mysqli')) $dolibarr_main_db_port='3306'; // For backward compatibility

		// Clean parameters
		$dolibarr_main_data_root        =isset($dolibarr_main_data_root)?trim($dolibarr_main_data_root):DOL_DOCUMENT_ROOT . '/../documents';
    	$dolibarr_main_url_root         =isset($dolibarr_main_url_root)?trim($dolibarr_main_url_root):'';
    	$dolibarr_main_url_root_alt     =isset($dolibarr_main_url_root_alt)?trim($dolibarr_main_url_root_alt):'';
    	$dolibarr_main_document_root    =isset($dolibarr_main_document_root)?trim($dolibarr_main_document_root):'';
    	$dolibarr_main_document_root_alt=isset($dolibarr_main_document_root_alt)?trim($dolibarr_main_document_root_alt):'';

        // Remove last / or \ on directories or url value
        if (! empty($dolibarr_main_document_root)		&& ! preg_match('/^[\\/]+$/', $dolibarr_main_document_root))		$dolibarr_main_document_root=preg_replace('/[\\/]+$/', '', $dolibarr_main_document_root);
        if (! empty($dolibarr_main_url_root)			&& ! preg_match('/^[\\/]+$/', $dolibarr_main_url_root))			$dolibarr_main_url_root=preg_replace('/[\\/]+$/', '', $dolibarr_main_url_root);
        if (! empty($dolibarr_main_data_root)			&& ! preg_match('/^[\\/]+$/', $dolibarr_main_data_root))			$dolibarr_main_data_root=preg_replace('/[\\/]+$/', '', $dolibarr_main_data_root);
        if (! empty($dolibarr_main_document_root_alt)	&& ! preg_match('/^[\\/]+$/', $dolibarr_main_document_root_alt))	$dolibarr_main_document_root_alt=preg_replace('/[\\/]+$/', '', $dolibarr_main_document_root_alt);
        if (! empty($dolibarr_main_url_root_alt)		&& ! preg_match('/^[\\/]+$/', $dolibarr_main_url_root_alt))		$dolibarr_main_url_root_alt=preg_replace('/[\\/]+$/', '', $dolibarr_main_url_root_alt);

        // Create conf object
        if (! empty($dolibarr_main_document_root))
        {
            $result=conf($dolibarr_main_document_root);
        }
        // Load database driver
        if ($result)
        {
            if (! empty($dolibarr_main_document_root) && ! empty($dolibarr_main_db_type))
            {
                $result=include_once $dolibarr_main_document_root . "/core/db/".$dolibarr_main_db_type.'.class.php';
                if (! $result)
                {
                    $includeconferror='ErrorBadValueForDolibarrMainDBType';
                }
            }
        }
        else
        {
            $includeconferror='ErrorBadValueForDolibarrMainDocumentRoot';
        }
    }
    else
    {
        $includeconferror='ErrorBadFormatForConfFile';
    }
}
$conf->global->MAIN_ENABLE_LOG_TO_HTML = 1;

// Define prefix
if (! isset($dolibarr_main_db_prefix) || ! $dolibarr_main_db_prefix) $dolibarr_main_db_prefix='llx_';
define('MAIN_DB_PREFIX', (isset($dolibarr_main_db_prefix)?$dolibarr_main_db_prefix:''));

define('DOL_CLASS_PATH', 'class/');                             // Filsystem path to class dir
define('DOL_DATA_ROOT', (isset($dolibarr_main_data_root)?$dolibarr_main_data_root:DOL_DOCUMENT_ROOT . '/../documents'));
define('DOL_MAIN_URL_ROOT', (isset($dolibarr_main_url_root)?$dolibarr_main_url_root:''));           // URL relative root
$uri=preg_replace('/^http(s?):\/\//i', '', constant('DOL_MAIN_URL_ROOT'));  // $uri contains url without http*
$suburi = strstr($uri, '/');       // $suburi contains url without domain
if ($suburi == '/') $suburi = '';   // If $suburi is /, it is now ''
define('DOL_URL_ROOT', $suburi);    // URL relative root ('', '/dolibarr', ...)


if (empty($conf->file->character_set_client))      	$conf->file->character_set_client="utf-8";
if (empty($conf->db->character_set))  				$conf->db->character_set='utf8';
if (empty($conf->db->dolibarr_main_db_collation))  	$conf->db->dolibarr_main_db_collation='utf8_unicode_ci';
if (empty($conf->db->dolibarr_main_db_encryption)) 	$conf->db->dolibarr_main_db_encryption=0;
if (empty($conf->db->dolibarr_main_db_cryptkey))   	$conf->db->dolibarr_main_db_cryptkey='';
if (empty($conf->db->user)) $conf->db->user='';

// Define array of document root directories
$conf->file->dol_document_root=array(DOL_DOCUMENT_ROOT);
if (! empty($dolibarr_main_document_root_alt))
{
    // dolibarr_main_document_root_alt contains several directories
    $values=preg_split('/[;,]/', $dolibarr_main_document_root_alt);
    foreach($values as $value)
    {
        $conf->file->dol_document_root[]=$value;
    }
}


// Security check (old method, when directory is renamed /install.lock)
if (preg_match('/install\.lock/i', $_SERVER["SCRIPT_FILENAME"]))
{
	if (! is_object($langs))
	{
		$langs = new Translate('..', $conf);
		$langs->setDefaultLang('auto');
	}
	$langs->load("install");
	print $langs->trans("YouTryInstallDisabledByDirLock");
    if (! empty($dolibarr_main_url_root))
    {
        print 'Click on following link, <a href="'.$dolibarr_main_url_root .'/admin/index.php?mainmenu=home&leftmenu=setup'.(isset($_POST["login"])?'&username='.urlencode($_POST["login"]):'').'">';
        print $langs->trans("ClickHereToGoToApp");
        print '</a>';
    }
    exit;
}

$lockfile = DOL_DATA_ROOT . '/install.lock';
if (constant('DOL_DATA_ROOT') === null) {
	// We don't have a configuration file yet
	// Try to detect any lockfile in the default documents path
	$lockfile = '../../documents/install.lock';
}
if (@file_exists($lockfile))
{
	if (! isset($langs) || ! is_object($langs))
	{
		$langs = new Translate('..', $conf);
		$langs->setDefaultLang('auto');
	}
	$langs->load("install");
	print $langs->trans("YouTryInstallDisabledByFileLock");
    if (! empty($dolibarr_main_url_root))
    {
        print $langs->trans("ClickOnLinkOrRemoveManualy").'<br>';
        print '<a href="'.$dolibarr_main_url_root .'/admin/index.php?mainmenu=home&leftmenu=setup'.(isset($_POST["login"])?'&username='.urlencode($_POST["login"]):'').'">';
        print $langs->trans("ClickHereToGoToApp");
        print '</a>';
    }
    else
    {
        print 'If you always reach this page, you must remove install.lock file manually.<br>';
    }
    exit;
}


// Force usage of log file for install and upgrades
$conf->syslog->enabled=1;
$conf->global->SYSLOG_LEVEL=constant('LOG_DEBUG');
if (! defined('SYSLOG_HANDLERS')) define('SYSLOG_HANDLERS', '["mod_syslog_file"]');
if (! defined('SYSLOG_FILE'))	// To avoid warning on systems with constant already defined
{
	if (@is_writable('/tmp')) define('SYSLOG_FILE', '/tmp/dolibarr_install.log');
	elseif (! empty($_ENV["TMP"])  && @is_writable($_ENV["TMP"])) define('SYSLOG_FILE', $_ENV["TMP"].'/dolibarr_install.log');
	elseif (! empty($_ENV["TEMP"]) && @is_writable($_ENV["TEMP"])) define('SYSLOG_FILE', $_ENV["TEMP"].'/dolibarr_install.log');
	elseif (@is_writable('../../../../') && @file_exists('../../../../startdoliwamp.bat')) define('SYSLOG_FILE', '../../../../dolibarr_install.log');	// For DoliWamp
	elseif (@is_writable('../../')) define('SYSLOG_FILE', '../../dolibarr_install.log');				// For others
	//print 'SYSLOG_FILE='.SYSLOG_FILE;exit;
}
if (defined('SYSLOG_FILE')) $conf->global->SYSLOG_FILE=constant('SYSLOG_FILE');
if (! defined('SYSLOG_FILE_NO_ERROR')) define('SYSLOG_FILE_NO_ERROR', 1);
// We init log handler for install
$handlers = array('mod_syslog_file');
foreach ($handlers as $handler)
{
	$file = DOL_DOCUMENT_ROOT.'/core/modules/syslog/'.$handler.'.php';
	if (!file_exists($file))
	{
		throw new Exception('Missing log handler file '.$handler.'.php');
	}

	require_once $file;
	$loghandlerinstance = new $handler();
	if (!$loghandlerinstance instanceof LogHandlerInterface)
	{
		throw new Exception('Log handler does not extend LogHandlerInterface');
	}

	if (empty($conf->loghandlers[$handler])) $conf->loghandlers[$handler]=$loghandlerinstance;
}

// Define object $langs
$langs = new Translate('..', $conf);
if (GETPOST('lang', 'aZ09')) $langs->setDefaultLang(GETPOST('lang', 'aZ09'));
else $langs->setDefaultLang('auto');


/**
 * Load conf file (file must exists)
 *
 * @param	string		$dolibarr_main_document_root		Root directory of Dolibarr bin files
 * @return	int												<0 if KO, >0 if OK
 */
function conf($dolibarr_main_document_root)
{
    global $conf;
    global $dolibarr_main_db_type;
    global $dolibarr_main_db_host;
    global $dolibarr_main_db_port;
    global $dolibarr_main_db_name;
    global $dolibarr_main_db_user;
    global $dolibarr_main_db_pass;
    global $character_set_client;

    $return=include_once $dolibarr_main_document_root.'/core/class/conf.class.php';
    if (! $return) return -1;

    $conf=new Conf();
    $conf->db->type = trim($dolibarr_main_db_type);
    $conf->db->host = trim($dolibarr_main_db_host);
    $conf->db->port = trim($dolibarr_main_db_port);
    $conf->db->name = trim($dolibarr_main_db_name);
    $conf->db->user = trim($dolibarr_main_db_user);
    $conf->db->pass = trim($dolibarr_main_db_pass);

    // Mysql driver support has been removed in favor of mysqli
    if ($conf->db->type == 'mysql') $conf->db->type = 'mysqli';
    if (empty($character_set_client)) $character_set_client="UTF-8";
    $conf->file->character_set_client=strtoupper($character_set_client);
    if (empty($dolibarr_main_db_character_set)) $dolibarr_main_db_character_set=($conf->db->type=='mysqli'?'utf8':'');
    $conf->db->character_set=$dolibarr_main_db_character_set;
    if (empty($dolibarr_main_db_collation)) $dolibarr_main_db_collation=($conf->db->type=='mysqli'?'utf8_unicode_ci':'');
    $conf->db->dolibarr_main_db_collation=$dolibarr_main_db_collation;
    if (empty($dolibarr_main_db_encryption)) $dolibarr_main_db_encryption=0;
    $conf->db->dolibarr_main_db_encryption = $dolibarr_main_db_encryption;
    if (empty($dolibarr_main_db_cryptkey)) $dolibarr_main_db_cryptkey='';
    $conf->db->dolibarr_main_db_cryptkey = $dolibarr_main_db_cryptkey;

    // Force usage of log file for install and upgrades
    $conf->syslog->enabled=1;
    $conf->global->SYSLOG_LEVEL=constant('LOG_DEBUG');
    if (! defined('SYSLOG_HANDLERS')) define('SYSLOG_HANDLERS', '["mod_syslog_file"]');
    if (! defined('SYSLOG_FILE'))	// To avoid warning on systems with constant already defined
    {
        if (@is_writable('/tmp')) define('SYSLOG_FILE', '/tmp/dolibarr_install.log');
        elseif (! empty($_ENV["TMP"])  && @is_writable($_ENV["TMP"]))  define('SYSLOG_FILE', $_ENV["TMP"].'/dolibarr_install.log');
        elseif (! empty($_ENV["TEMP"]) && @is_writable($_ENV["TEMP"])) define('SYSLOG_FILE', $_ENV["TEMP"].'/dolibarr_install.log');
        elseif (@is_writable('../../../../') && @file_exists('../../../../startdoliwamp.bat')) define('SYSLOG_FILE', '../../../../dolibarr_install.log');	// For DoliWamp
        elseif (@is_writable('../../')) define('SYSLOG_FILE', '../../dolibarr_install.log');				// For others
        //print 'SYSLOG_FILE='.SYSLOG_FILE;exit;
    }
    if (defined('SYSLOG_FILE')) $conf->global->SYSLOG_FILE=constant('SYSLOG_FILE');
    if (! defined('SYSLOG_FILE_NO_ERROR')) define('SYSLOG_FILE_NO_ERROR', 1);
    // We init log handler for install
    $handlers = array('mod_syslog_file');
    foreach ($handlers as $handler)
    {
    	$file = DOL_DOCUMENT_ROOT.'/core/modules/syslog/'.$handler.'.php';
    	if (!file_exists($file))
    	{
    		throw new Exception('Missing log handler file '.$handler.'.php');
    	}

    	require_once $file;
    	$loghandlerinstance = new $handler();
    	if (!$loghandlerinstance instanceof LogHandlerInterface)
    	{
    		throw new Exception('Log handler does not extend LogHandlerInterface');
    	}

		if (empty($conf->loghandlers[$handler])) $conf->loghandlers[$handler]=$loghandlerinstance;
    }

    return 1;
}


/**
 * Show HTML header of install pages
 *
 * @param	string		$subtitle			Title
 * @param 	string		$next				Next
 * @param 	string		$action    			Action code ('set' or 'upgrade')
 * @param 	string		$param				Param
 * @param	string		$forcejqueryurl		Set jquery relative URL (must end with / if defined)
 * @param   string      $csstable           Css for table
 * @return	void
 */
function pHeader($subtitle, $next, $action = 'set', $param = '', $forcejqueryurl = '', $csstable = 'main-inside')
{
    global $conf;
    global $langs;
    $langs->load("main");
    $langs->load("admin");
	$langs->load("install");

    $jquerytheme='base';

    if ($forcejqueryurl)
    {
        $jQueryCustomPath = $forcejqueryurl;
        $jQueryUiCustomPath = $forcejqueryurl;
    }
    else
    {
        $jQueryCustomPath = (defined('JS_JQUERY') && constant('JS_JQUERY')) ? JS_JQUERY : false;
        $jQueryUiCustomPath = (defined('JS_JQUERY_UI') && constant('JS_JQUERY_UI')) ? JS_JQUERY_UI : false;
    }

    // We force the content charset
    header("Content-type: text/html; charset=".$conf->file->character_set_client);
    header("X-Content-Type-Options: nosniff");

    print '<!DOCTYPE HTML>'."\n";
    print '<html>'."\n";
    print '<head>'."\n";
    print '<meta charset="'.$conf->file->character_set_client.'">'."\n";
    print '<meta name="viewport" content="width=device-width, initial-scale=1.0">'."\n";
    print '<meta name="generator" content="Dolibarr installer">'."\n";
    print '<link rel="stylesheet" type="text/css" href="default.css">'."\n";

    print '<!-- Includes CSS for JQuery -->'."\n";
    if ($jQueryUiCustomPath) print '<link rel="stylesheet" type="text/css" href="'.$jQueryUiCustomPath.'css/'.$jquerytheme.'/jquery-ui.min.css" />'."\n";  // JQuery
    else print '<link rel="stylesheet" type="text/css" href="../includes/jquery/css/'.$jquerytheme.'/jquery-ui.min.css" />'."\n";    // JQuery

    print '<!-- Includes JS for JQuery -->'."\n";
    if ($jQueryCustomPath) print '<script type="text/javascript" src="'.$jQueryCustomPath.'jquery.min.js"></script>'."\n";
    else print '<script type="text/javascript" src="../includes/jquery/js/jquery.min.js"></script>'."\n";
    if ($jQueryUiCustomPath) print '<script type="text/javascript" src="'.$jQueryUiCustomPath.'jquery-ui.min.js"></script>'."\n";
    else print '<script type="text/javascript" src="../includes/jquery/js/jquery-ui.min.js"></script>'."\n";

    print '<title>'.$langs->trans("DolibarrSetup").'</title>'."\n";
    print '</head>'."\n";

    print '<body>'."\n";

    print '<div class="divlogoinstall" style="text-align:center">';
    print '<img class="imglogoinstall" src="../theme/dolibarr_logo.png" alt="Dolibarr logo"><br>';
    print DOL_VERSION;
    print '</div><br>';

    print '<span class="titre">'.$langs->trans("DolibarrSetup");
    if ($subtitle) {
        print ' - '.$subtitle;
    }
    print '</span>'."\n";

    print '<form name="forminstall" style="width: 100%" action="'.$next.'.php'.($param?'?'.$param:'').'" method="POST"';
    if ($next == 'step5') print ' autocomplete="off"';
    print '>'."\n";
    print '<input type="hidden" name="testpost" value="ok">'."\n";
    print '<input type="hidden" name="action" value="'.$action.'">'."\n";

    print '<table class="main" width="100%"><tr><td>'."\n";

    print '<table class="'.$csstable.'" width="100%"><tr><td>'."\n";
}

/**
 * Print HTML footer of install pages
 *
 * @param 	integer	$nonext				1=No button "Next step", 2=Show button but disabled with a link to enable
 * @param	string	$setuplang			Language code
 * @param	string	$jscheckfunction	Add a javascript check function
 * @param	integer	$withpleasewait		Add also please wait tags
 * @return	void
 */
function pFooter($nonext = 0, $setuplang = '', $jscheckfunction = '', $withpleasewait = 0)
{
    global $conf,$langs;

    $langs->load("main");
    $langs->load("other");
    $langs->load("admin");

    print '</td></tr></table>'."\n";
    print '</td></tr></table>'."\n";

    if (! $nonext || ($nonext == '2'))
    {
        print '<div class="nextbutton" id="nextbutton">';
        if ($nonext == '2')
		{
			print $langs->trans("ErrorFoundDuringMigration", isset($_SERVER["REQUEST_URI"])?$_SERVER["REQUEST_URI"].'&ignoreerrors=1':'').'<br><br>';
		}

        print '<input type="submit" '.($nonext == '2' ? 'disabled="disabled" ':'').'value="'.$langs->trans("NextStep").' ->"';
        if ($jscheckfunction) print ' onClick="return '.$jscheckfunction.'();"';
        print '></div>';
        if ($withpleasewait) print '<div style="visibility: hidden;" class="pleasewait" id="pleasewait"><br>'.$langs->trans("NextStepMightLastALongTime").'<br><br><div class="blinkwait">'.$langs->trans("PleaseBePatient").'</div></div>';
    }
    if ($setuplang)
    {
        print '<input type="hidden" name="selectlang" value="'.$setuplang.'">';
    }

    print '</form>'."\n";

    // If there is some logs in buffer to show
    if (isset($conf->logbuffer) && count($conf->logbuffer))
    {
        print "\n";
        print "<!-- Start of log output\n";
        //print '<div class="hidden">'."\n";
        foreach($conf->logbuffer as $logline)
        {
            print $logline."<br>\n";
        }
        //print '</div>'."\n";
        print "End of log output -->\n";
        print "\n";
    }

    print '</body>'."\n";
    print '</html>'."\n";
}

/**
 * Log function for install pages
 *
 * @param	string	$message	Message
 * @param 	int		$level		Level of log
 * @return	void
 */
function dolibarr_install_syslog($message, $level = LOG_DEBUG)
{
    if (! defined('LOG_DEBUG')) define('LOG_DEBUG', 6);
    dol_syslog($message, $level);
}

/**
 * Automatically detect Dolibarr's main document root
 *
 * @return string
 */
function detect_dolibarr_main_document_root()
{
	// If PHP is in CGI mode, SCRIPT_FILENAME is PHP's path.
	// Since that's not what we want, we suggest $_SERVER["DOCUMENT_ROOT"]
	if ($_SERVER["SCRIPT_FILENAME"] == 'php' || preg_match('/[\\/]php$/i', $_SERVER["SCRIPT_FILENAME"]) || preg_match('/php\.exe$/i', $_SERVER["SCRIPT_FILENAME"]))
	{
		$dolibarr_main_document_root = $_SERVER["DOCUMENT_ROOT"];

		if (!preg_match('/[\\/]dolibarr[\\/]htdocs$/i', $dolibarr_main_document_root)) {
			$dolibarr_main_document_root .= "/dolibarr/htdocs";
		}
	} else {
		// We assume /install to be under /htdocs, so we get the parent directory of the current directory
		$dolibarr_main_document_root = dirname(dirname($_SERVER["SCRIPT_FILENAME"]));
	}

	return $dolibarr_main_document_root;
}

/**
 * Automatically detect Dolibarr's main data root
 *
 * @param string $dolibarr_main_document_root Current main document root
 * @return string
 */
function detect_dolibarr_main_data_root($dolibarr_main_document_root)
{
	$dolibarr_main_data_root = preg_replace("/\/htdocs$/", "", $dolibarr_main_document_root);
	$dolibarr_main_data_root .= "/documents";
	return $dolibarr_main_data_root;
}

/**
 * Automatically detect Dolibarr's main URL root
 *
 * @return string
 */
function detect_dolibarr_main_url_root()
{
	// If defined (Ie: Apache with Linux)
	if (isset($_SERVER["SCRIPT_URI"])) {
		$dolibarr_main_url_root = $_SERVER["SCRIPT_URI"];
	} // If defined (Ie: Apache with Caudium)
	elseif (isset($_SERVER["SERVER_URL"]) && isset($_SERVER["DOCUMENT_URI"])) {
		$dolibarr_main_url_root = $_SERVER["SERVER_URL"] . $_SERVER["DOCUMENT_URI"];
	} // If SCRIPT_URI, SERVER_URL, DOCUMENT_URI not defined (Ie: Apache 2.0.44 for Windows)
	else {
		$proto = ((!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on') || (! empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? 'https' : 'http';
		if (!empty($_SERVER["HTTP_HOST"])) {
			$serverport = $_SERVER["HTTP_HOST"];
		}
		elseif (!empty($_SERVER["SERVER_NAME"])) {
			$serverport = $_SERVER["SERVER_NAME"];
		}
		else {
			$serverport = 'localhost';
		}
		$dolibarr_main_url_root = $proto . "://" . $serverport . $_SERVER["SCRIPT_NAME"];
	}
	// Clean proposed URL
	// We assume /install to be under /htdocs, so we get the parent path of the current URL
	$dolibarr_main_url_root = dirname(dirname($dolibarr_main_url_root));

	return $dolibarr_main_url_root;
}

/**
 * Replaces automatic database login by actual value
 *
 * @param string $force_install_databaserootlogin Login
 * @return string
 */
function parse_database_login($force_install_databaserootlogin)
{
	return preg_replace('/__SUPERUSERLOGIN__/', 'root', $force_install_databaserootlogin);
}

/**
 * Replaces automatic database password by actual value
 *
 * @param string $force_install_databaserootpass Password
 * @return string
 */
function parse_database_pass($force_install_databaserootpass)
{
	return preg_replace('/__SUPERUSERPASSWORD__/', '', $force_install_databaserootpass);
}
