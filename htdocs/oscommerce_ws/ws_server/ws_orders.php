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

// Soap Server.
require_once('./lib/nusoap.php');

require_once('./includes/configure.php');

// Create the soap Object
$s = new soap_server;

// Register the methods available for clients
$s->register('get_CAmensuel');
$s->register('get_orders');
$s->register('get_lastOrderClients');
$s->register('get_Order');

/*----------------------------------------------
* renvoie un tableau avec le CA mensuel réalisé
-----------------------------------------------*/
function get_CAmensuel() {

//on se connecte
	if (!($connexion = mysql_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD)))   return new soap_fault("Server", "MySQL 1", "connexion impossible");
	if (!($db = mysql_select_db(DB_DATABASE, $connexion)))  return new soap_fault("Server", "MySQL 2", mysql_error());

//la requête
	$sql = "SELECT sum(t.value) as value, MONTH(o.date_purchased) as mois, YEAR(o.date_purchased) as an";
	$sql .= " FROM orders_total as t";
	$sql .= " JOIN orders as o ON o.orders_id = t.orders_id";
	$sql .= " WHERE t.class = 'ot_subtotal' ";
//AND YEAR(o.date_purchased) = YEAR(now()) ";
	$sql .= " GROUP BY an, mois ORDER BY an desc ,mois desc limit 1,12";
 
	if (!($resquer = mysql_query($sql,$connexion)))  return new soap_fault("Server", "MySQL 3 ".$sql, mysql_error());

		switch ($numrows = mysql_numrows($resquer)) {
		case 0 : 
			return new soap_fault("Server", "MySQL 4", $sql);
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

function get_orders($limit='', $status='') {

//on se connecte
	if (!($connexion = mysql_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD)))   return new soap_fault("Server", "MySQL 1", "connexion impossible");
	if (!($db = mysql_select_db(DB_DATABASE, $connexion)))  return new soap_fault("Server", "MySQL 2", mysql_error());

//on recherche
$sql = "SELECT o.orders_id, o.customers_name, o.date_purchased, t.value, o.payment_method";
$sql .= " FROM orders_total as t JOIN orders as o on o.orders_id = t.orders_id ";
$sql .= " WHERE t.class = 'ot_subtotal'";
if ($status > 0) $sql .=  " and o.orders_status = ".$status;
$sql .= " ORDER BY o.date_purchased desc";
if ($limit > 0) $sql .= " LIMIT ".$limit;
 
	if (!($resquer = mysql_query($sql,$connexion)))  return new soap_fault("Server", "MySQL 3 ".$sql, mysql_error());
	$result ='';

		switch ($numrows = mysql_numrows($resquer)) {
		case 0 : 
			//return new soap_fault("Server", "MySQL 4", "produit inexistant");
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


function get_lastOrderClients($id='',$name='',$limit='') {

//on se connecte
	if (!($connexion = mysql_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD)))   return new soap_fault("Server", "MySQL 1", "connexion impossible");
	if (!($db = mysql_select_db(DB_DATABASE, $connexion)))  return new soap_fault("Server", "MySQL 2", mysql_error());

//on recherche
	$sql = "SELECT o.orders_id, o.customers_name, o.delivery_country, o.date_purchased, t.value, s.orders_status_name as statut, o.payment_method ";
	$sql .= " FROM orders_total as t JOIN orders as o on o.orders_id = t.orders_id ";
	$sql .= " JOIN orders_status as s on o.orders_status = s.orders_status_id and s.language_id = 1";
	$sql .= " WHERE t.class = 'ot_subtotal' and o.orders_status < 5 order by o.date_purchased desc";
	if ($limit > 0) $sql .= " LIMIT ".$limit;
 
	if (!($resquer = mysql_query($sql,$connexion)))  return new soap_fault("Server", "MySQL 3 ".$sql, mysql_error());
	$result ='';

		switch ($numrows = mysql_numrows($resquer)) {
		case 0 : 
			return new soap_fault("Server", "MySQL 4", "produit inexistant");
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

//renvoie la commande $id ou toute la liste des commandes si $id = 0

function get_Order($orderid="0")
{

//on se connecte
	if (!($connexion = mysql_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD)))   return new soap_fault("Server", "MySQL 1", "connexion impossible");
	if (!($db = mysql_select_db(DB_DATABASE, $connexion)))  return new soap_fault("Server", "MySQL 2", mysql_error());

//on recherche la commande
/*$sql = "SELECT o.orders_id, o.customers_name, o.customers_id, o.date_purchased, t.value, o.payment_method ";
$sql .= " FROM orders_total as t JOIN orders as o on o.orders_id = t.orders_id ";
$sql .= " WHERE t.class = 'ot_subtotal'";
*/
$sql = "SELECT o.orders_id, o.customers_name, o.customers_id, o.date_purchased, o.payment_method, t.value as total, sum(p.value) as port, s.orders_status_name as statut  ";
$sql .= " FROM orders as o ";
$sql .= " JOIN orders_total as t on o.orders_id = t.orders_id and t.class = 'ot_subtotal' ";
$sql .= " JOIN orders_total as p on o.orders_id = p.orders_id and (p.class = 'ot_shipping' OR p.class = 'ot_fixed_payment_chg') ";   
$sql .= " JOIN orders_status as s on o.orders_status = s.orders_status_id and s.language_id = 1";
$sql .= " WHERE o.orders_status < 5 "; // élimine les commandes annulées, remboursées
if ($orderid > 0) $sql .=  " AND o.orders_id = ".$orderid;
$sql .= " GROUP BY p.orders_id ";
$sql .= " ORDER BY o.date_purchased desc";
 
	if (!($resquer = mysql_query($sql,$connexion)))  return new soap_fault("Server", "MySQL 3 ".$sql, mysql_error());
	$result ='';

		switch ($numrows = mysql_numrows($resquer)) {
		case 0 : 
			return new soap_fault("Server", "MySQL 4", "commande inexistante ".$sql);
			break;
		default :
			$i = 0;
			while ( $i < $numrows)  {
				$result[$i] =  mysql_fetch_array($resquer, MYSQL_ASSOC);
				$i++;
			}
			break;
		}
		$j = $i--;

if ($orderid > 0) 
{	
	//on recherche les lignes de la commande
	$sql = "SELECT l.products_id, l.products_name, l.products_price, l.final_price, l.products_tax, l.products_quantity ";
	$sql .= " FROM orders_products l ";
	$sql .= " WHERE l.orders_id = ".$orderid;

 
	if (!($resquer = mysql_query($sql,$connexion)))  return new soap_fault("Server", "MySQL 3 ".$sql, mysql_error());

		switch ($numrows = mysql_numrows($resquer)) {
		case 0 : 
			return new soap_fault("Server", "MySQL 5", "commande sans articles");
			break;
		default :

			while ( $i < $numrows)  {
				$result[$j + $i] =  mysql_fetch_array($resquer, MYSQL_ASSOC);
				$i++;
			}
			break;
		}
}
mysql_close($connexion);
 /* Sends the results to the client */
return $result;
}


// Return the results.
$s->service($HTTP_RAW_POST_DATA);

?>
