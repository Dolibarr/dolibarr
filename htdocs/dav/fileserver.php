<?php
/* Copyright (C) 2018	Destailleur Laurent	<eldy@users.sourceforge.net>
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
 *      \file       htdocs/dav/fileserver.php
 *      \ingroup    dav
 *      \brief      Server DAV
 */

require ("../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

// Files we need
require_once DOL_DOCUMENT_ROOT.'/includes/sabre/autoload.php';

$user = new User($db);
if(isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER']!='')
{
	$user->fetch('',$_SERVER['PHP_AUTH_USER']);
	$user->getrights();
}

$langs->loadLangs(array("main","other"));


//if(empty($conf->dav->enabled))
//	accessforbidden();

// If you want to run the SabreDAV server in a custom location (using mod_rewrite for instance)
// You can override the baseUri here.
$baseUri = DOL_URL_ROOT.'/dav/fileserver.php';


// settings
$publicDir = $conf->dav->dir_output.'/public';
$tmpDir = $conf->dav->dir_output.'/tmp';


// Create the root node
// Setting up the directory tree //
$nodes = array(
	// /principals
	//new \Sabre\DAVACL\PrincipalCollection($principalBackend),
	// /addressbook
	//new \Sabre\CardDAV\AddressBookRoot($principalBackend, $carddavBackend),
	// /calendars
	//new \Sabre\CalDAV\CalendarRoot($principalBackend, $caldavBackend),
	// / Public docs
	new \Sabre\DAV\FS\Directory($dolibarr_main_data_root. '/dav/public')
);

// The rootnode needs in turn to be passed to the server class
$server = new \Sabre\DAV\Server($nodes);

if (isset($baseUri))
    $server->setBaseUri($baseUri);

// Support for LOCK and UNLOCK
$lockBackend = new \Sabre\DAV\Locks\Backend\File($tmpDir . '/.locksdb');
$lockPlugin = new \Sabre\DAV\Locks\Plugin($lockBackend);
$server->addPlugin($lockPlugin);

// Support for html frontend
$browser = new \Sabre\DAV\Browser\Plugin();
$server->addPlugin($browser);

//$server->addPlugin(new \Sabre\CardDAV\Plugin());
//$server->addPlugin(new \Sabre\CalDAV\Plugin());
//$server->addPlugin(new \Sabre\DAVACL\Plugin());

// Automatically guess (some) contenttypes, based on extension
$server->addPlugin(new \Sabre\DAV\Browser\GuessContentType());

// Authentication backend
/*$authBackend = new \Sabre\DAV\Auth\Backend\File('.htdigest');
$auth = new \Sabre\DAV\Auth\Plugin($authBackend);
$server->addPlugin($auth);
*/

// Temporary file filter
/*$tempFF = new \Sabre\DAV\TemporaryFileFilterPlugin($tmpDir);
$server->addPlugin($tempFF);
*/

// And off we go!
$server->exec();

if (is_object($db)) $db->close();
