<?php
/* Copyright (C) 2026 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/versionmod.inc.php
 * 	\ingroup	core
 *  \brief      File included by main files of the Unalterable Log module
 */

// The version of the POS system (Immutable Log system)
define('DOLCERT_VERSION', '3.0.0');


// 1 was used for beta version candidate for certification, or for stable version that has been certified.
// 2 to force LNE features for debug purposes(integrity of files will be broken). It just has one difference with 1: https is not required in this mode helping to work in development environment.
// 0 was used for old version, for version not certified but compliant with the law by using an attestation of an IT provider that guarantee
//     the the software is compliant. WARNING: In this case, you must find an IT company that give you the attestation of conformity.
if (!defined('CERTIF_LNE')) {
	define('CERTIF_LNE', '0');
}


// Array of dir/files to include in the signature of the scope of the certification files.
// This array will be used by the generate_filelist_xml.php script to generate the filelist.xml file
$arrayofunalterablefiles = array(
	//array('dir' => dirname(__FILE__).'/../../htdocs/', 'file' => 'version.inc.php'),
	array('dir' => dirname(__FILE__).'/../../htdocs/blockedlog', 'file' => 'all', 'regextoinclude' => '(\.php|\.sql)$', 'regextoexclude' => ''),
	array('dir' => dirname(__FILE__).'/../../htdocs/install/mysql/tables', 'file' => 'all', 'regextoinclude' => 'llx_blockedlog.*(\.php|\.sql)$', 'regextoexclude' => ''),
	array('dir' => dirname(__FILE__).'/../../htdocs/core/triggers', 'file' => 'interface_50_modBlockedlog_ActionsBlockedLog.class.php'),
	array('dir' => dirname(__FILE__).'/../../htdocs/core/class', 'file' => 'all', 'regextoinclude' => '(interfaces.class.php|commontrigger.class.php)$', 'regextoexclude' => ''),
	array('dir' => dirname(__FILE__).'/../../htdocs/takepos', 'file' => 'receipt.php')
);
