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
 * $Source$
 *
 */

set_magic_quotes_runtime(0);

require_once("./includes/configure.php");

// Soap Server.
require_once('./lib/nusoap.php');

// Create the soap Object
$s = new soap_server;
$ns='oscommerce';
$s->configureWSDL('WebServicesOSCommerceForDolibarr',$ns);
$s->wsdl->schemaTargetNamespace=$ns;

// Register a method available for clients
$s->register('get_article');
$s->register('get_listearticles');


function get_article($id='',$ref='') {

//on se connecte
	if (!($connexion = mysql_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD)))   return new soap_fault("Server", "MySQL 1", "connection impossible");
	if (!($db = mysql_select_db(DB_DATABASE, $connexion)))  return new soap_fault("Server", "MySQL 2", mysql_error());

//on recherche
		$sql = "SELECT p.products_id, p.products_model, p.products_quantity, p.products_status, p.products_price, d.products_name, d.products_description, m.manufacturers_name, m.manufacturers_id";
		$sql .= " FROM products as p, products_description as d, manufacturers as m";
		$sql .= " WHERE p.products_id = d.products_id AND d.language_id =" . OSC_LANGUAGE_ID;
		$sql .= " AND p.manufacturers_id=m.manufacturers_id";
      if ($id) $sql.= " AND p.products_id = ".$id;
      if ($ref) $sql.= " AND p.products_model = '".addslashes($ref)."'";

	if (!($resquer = mysql_query($sql,$connexion)))  return new soap_fault("Server", "MySQL 3 ".$sql, mysql_error());

		switch (mysql_numrows($resquer)) {
		case 0 : 
			return new soap_fault("Server", "MySQL 4", "produit inexistant");
			break;
		case 1 : 
			$res_article =   @mysql_fetch_array($resquer, MYSQL_ASSOC);
  			$res_article["time"] = time();
			break;
		default : 
			return new soap_fault("Server", "MySQL 5", "erreur requete");
		}		
	mysql_close($connexion);
 /* Sends the results to the client */
return $res_article;
}

function get_listearticles() {

//on se connecte
	if (!($connexion = mysql_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD)))   return new soap_fault("Server", "MySQL 1", "connection impossible");
	if (!($db = mysql_select_db(DB_DATABASE, $connexion)))  return new soap_fault("Server", "MySQL 2", mysql_error());

//on recherche
	$sql = "SELECT p.products_id as OSC_id, p.products_model as model, p.products_quantity as quantity, p.products_status as status, d.products_name as name, m.manufacturers_name as manufacturer, m.manufacturers_id";
	$sql .= " FROM products as p, products_description as d, manufacturers as m";
	$sql .= " WHERE p.products_id = d.products_id AND d.language_id =" . OSC_LANGUAGE_ID;
	$sql .= " AND p.manufacturers_id=m.manufacturers_id";

	if (!($resquer = mysql_query($sql,$connexion)))  return new soap_fault("Server", "MySQL 3 ".$sql, mysql_error());

		switch ($numrows = mysql_numrows($resquer)) {
		case 0 : 
			return new soap_fault("Server", "MySQL 4", "produit inexistant");
			break;
		default : 
			$i = 0;
			while ( $i < $numrows)  {
				$liste_articles[$i] =  mysql_fetch_array($resquer, MYSQL_ASSOC);
				$i++;
			}
		}

	mysql_close($connexion);
 /* Sends the results to the client */
return $liste_articles;
}

// Return the results.
$s->service($HTTP_RAW_POST_DATA);

?>
