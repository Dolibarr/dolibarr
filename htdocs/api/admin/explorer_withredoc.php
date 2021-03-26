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
