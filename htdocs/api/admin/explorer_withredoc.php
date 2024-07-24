<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016   Laurent Destailleur     <eldy@users.sourceforge.net>
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
 *
 * @deprecated      Old explorer. Not using Swagger. See instead explorer in htdocs/api/index.php.
 */

/**
 * 	\defgroup   api     Module DolibarrApi
 *  \brief      API explorer using the swagger.json file
 *  \file       htdocs/api/admin/explorer_withredoc.php
 */

require_once '../../main.inc.php';

// Enable and test if module Api is enabled
if (!isModEnabled('api')) {
	$langs->load("admin");
	dol_syslog("Call of Dolibarr API interfaces with module API REST are disabled");
	print $langs->trans("WarningModuleNotActive", 'Api').'.<br><br>';
	print $langs->trans("ToActivateModule");
	//session_destroy();
	exit(0);
}

// Test if explorer is not disabled
if (getDolGlobalString('API_EXPLORER_DISABLED')) {
	$langs->load("admin");
	dol_syslog("Call Dolibarr API interfaces with module REST disabled");
	print $langs->trans("WarningAPIExplorerDisabled").'.<br><br>';
	//session_destroy();
	exit(0);
}

// Restrict API to some IPs
if (getDolGlobalString('API_RESTRICT_ON_IP')) {
	$allowedip = explode(' ', getDolGlobalString('API_RESTRICT_ON_IP'));
	$ipremote = getUserRemoteIP();
	if (!in_array($ipremote, $allowedip)) {
		dol_syslog('Remote ip is '.$ipremote.', not into list ' . getDolGlobalString('API_RESTRICT_ON_IP'));
		print 'APIs are not allowed from the IP '.$ipremote;
		header('HTTP/1.1 503 API not allowed from your IP '.$ipremote);
		//session_destroy();
		exit(0);
	}
}

?>
<!DOCTYPE html>
<html>
  <head>
	<title>ReDoc</title>
	<!-- needed for adaptive design -->
	<meta charset="utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,700|Roboto:300,400,700" rel="stylesheet">

	<!--
	ReDoc doesn't change outer page styles
	-->
	<style>
	  body {
		margin: 0;
		padding: 0;
	  }
	</style>
  </head>
  <body>
	<redoc spec-url='<?php echo DOL_MAIN_URL_ROOT.'/api/index.php/explorer/swagger.json?DOLAPIKEY='.GETPOST('DOLAPIKEY', 'aZ09'); ?>'></redoc>
	<!--<redoc spec-url='https://demo.dolibarr.org/api/index.php/explorer/swagger.json'></redoc>-->
	<!--<redoc spec-url='http://petstore.swagger.io/v2/swagger.json'></redoc>-->
	<script src="https://cdn.jsdelivr.net/npm/redoc/bundles/redoc.standalone.js"> </script>
  </body>
</html>
