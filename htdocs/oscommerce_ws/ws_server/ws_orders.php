<?php
/*---------------------------------------------
/ Webservices OSC pour dolibarr
/ gestion des commandes
/
/ Jean Heimburger			juin 2006
----------------------------------------------*/

set_magic_quotes_runtime(0);

// Soap Server.
require_once('./lib/nusoap.php');

require_once('./includes/configure.php');

// Create the soap Object
$s = new soap_server;

// Register the methods available for clients
$s->register('get_CAmensuel');
$s->register('get_orders');

/*----------------------------------------------
* renvoie un tableau avec le CA mensuel réalisé
-----------------------------------------------*/
function get_CAmensuel() {

//on se connecte
	if (!($connexion = mysql_connect(OSC_DB_SERVER, OSC_DB_SERVER_USERNAME, OSC_DB_SERVER_PASSWORD)))   return new soap_fault("Server", "MySQL 1", "connection impossible");
	if (!($db = mysql_select_db(OSC_DB_DATABASE, $connexion)))  return new soap_fault("Server", "MySQL 2", mysql_error());

//la requête
	$sql = "SELECT sum(t.value) as value, MONTH(o.date_purchased) as mois";
	$sql .= " FROM orders_total as t";
	$sql .= " JOIN orders as o ON o.orders_id = t.orders_id";
	$sql .= " WHERE t.class = 'ot_subtotal' AND YEAR(o.date_purchased) = YEAR(now()) ";
	$sql .= " GROUP BY mois ORDER BY mois";
 
	if (!($resquer = mysql_query($sql,$connexion)))  return new soap_fault("Server", "MySQL 3 ".$sql, mysql_error());

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

function get_orders($limit='', $status='') {

//on se connecte
	if (!($connexion = mysql_connect(OSC_DB_SERVER, OSC_DB_SERVER_USERNAME, OSC_DB_SERVER_PASSWORD)))   return new soap_fault("Server", "MySQL 1", "connection impossible");
	if (!($db = mysql_select_db(OSC_DB_DATABASE, $connexion)))  return new soap_fault("Server", "MySQL 2", mysql_error());
	
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

// Return the results.
$s->service($HTTP_RAW_POST_DATA);

?>
