<?php
/*  Copyright (C) 2006      Jean Heimburger     <jean@tiaris.info>
 *
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
 *
 * $Id$
 */

set_magic_quotes_runtime(0);

// Soap Server.
require_once('./lib/nusoap.php');

require_once('./includes/configure.php');

// Create the soap Object
$s = new soap_server;
/* $ns='oscommerce';
$s->configureWSDL('WebServicesOSCommerceForDolibarr',$ns);
$s->wsdl->schemaTargetNamespace=$ns;
*/

// Register the methods available for clients
$s->register('get_Client');

// méthodes
function get_Client($custid='') {

//on se connecte
	if (!($connexion = mysql_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD)))   return new soap_fault("Server", "MySQL 1", "connexion impossible");
	if (!($db = mysql_select_db(DB_DATABASE, $connexion)))  return new soap_fault("Server", "MySQL 2", mysql_error());

//la requête
	$sql = "SELECT c.customers_id, a.entry_company, a.entry_firstname, a.entry_lastname, a.entry_street_address, a.entry_postcode, a.entry_city, c.customers_telephone, c.customers_fax, c.customers_email_address, a.entry_country_id, b.countries_iso_code_2, b.countries_name ";
	$sql .= " from customers c JOIN address_book a ON a.customers_id = c.customers_id JOIN countries b ON b.countries_id = a.entry_country_id JOIN orders o ON o.customers_id = c.customers_id ";
if ($custid > 0)	$sql .= "WHERE c.customers_id = ".$custid;
	$sql .= " GROUP BY c.customers_id ORDER BY c.customers_id";

	if (!($resquer = mysql_query($sql,$connexion)))  return new soap_fault("Server", "MySQL 3 ".$sql, mysql_error());
	
//	$result[$i] = $numrows." lignes trouvées ".$sql;
		switch ($numrows = mysql_numrows($resquer)) {
		case 0 : 
			return new soap_fault("Server", "MySQL 4", "client inexistant ".$sql);
			break;
		default :
			$i = 0;
			while ( $i < $numrows)  {
				$result[$i] =  mysql_fetch_array($resquer, MYSQL_ASSOC);
				$i++;
			}
			break;
		}		
	mysql_close($connexion);
 /* Sends the results to the client */
return $result;
}

// Return the results.
$s->service($HTTP_RAW_POST_DATA);

?>
