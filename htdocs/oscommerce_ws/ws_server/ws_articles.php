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

require_once("./includes/configure.php");

define(OSC_IMG_URL, 'http://www.tiaris.info/catalog/images/'); // url du site OSC

// OSC

define('OSCADMIN', '/home/tiaris.info/catalog/admin/');
define('OSCIMAGES', '/home/tiaris.info/catalog/images/');

require(OSCADMIN.'includes/configure.php');
require(OSCADMIN.DIR_WS_CLASSES . 'object_info.php');
require(OSCADMIN.DIR_WS_INCLUDES . 'database_tables.php');
require(OSCADMIN.DIR_WS_FUNCTIONS . 'database.php');
require(OSCADMIN.DIR_WS_FUNCTIONS . 'general.php');


// Soap Server.
require_once('./lib/nusoap.php');

// Create the soap Object
$s = new soap_server;
/* $ns='oscommerce';
$s->configureWSDL('WebServicesOSCommerceForDolibarr',$ns);
$s->wsdl->schemaTargetNamespace=$ns;
*/

// Register a method available for clients
$s->register('get_article');
$s->register('get_listearticles');
$s->register('create_article');
$s->register('get_categorylist');


function create_article($prod)
{
// make a connection to the database... now
tep_db_connect() or die('Unable to connect to database server!');
  
// vérifier les paramètres
$sql_data_array = array('products_quantity' => $prod['quant'],
                       'products_model' => $prod['ref'],
                       'products_image' => $prod['image'],
                       'products_price' => $prod['prix'],
                       'products_weight' => $prod['poids'],
                       'products_date_added' => 'now()',
                       'products_last_modified' => '',
                       'products_date_available' => $prod['dispo'],
                       'products_status' => $prod['status'],
                       'products_tax_class_id' => $prod['ttax'],
                       'manufacturers_id' => $prod['fourn']);

            tep_db_perform(TABLE_PRODUCTS, $sql_data_array);
            $products_id = tep_db_insert_id();

            $category_id = 2;
            tep_db_query("insert into " . TABLE_PRODUCTS_TO_CATEGORIES . " (products_id, categories_id) values ('" . (int)$products_id . "', '" . (int)$category_id . "')");          

          $languages = tep_get_languages();
          for ($i=0, $n=sizeof($languages); $i<$n; $i++) {
            $language_id = $languages[$i]['id'];
            $sql_data_array = array('products_name' => $prod['nom'],
                                    'products_description' => $prod['desc'],
                                    'products_url' => $prod['url'],
                                    //'products_head_title_tag' => $prod['nom'],
                                    //'products_head_desc_tag' => $prod['desc'],
                                    //'products_head_keywords_tag' => '',
                                 	'products_id' => $products_id,
                                    'language_id' => $language_id
                                    );  
            tep_db_perform(TABLE_PRODUCTS_DESCRIPTION, $sql_data_array); 
          }

return $products_id;
} 


function get_article($id='',$ref='')
{
	//on se connecte
	if (!($connexion = mysql_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD)))   return new soap_fault("Server", "MySQL 1", "connexion impossible");
	if (!($db = mysql_select_db(DB_DATABASE, $connexion)))  return new soap_fault("Server", "MySQL 2", mysql_error());

	//on recherche
	$sql = "SELECT p.products_id, p.products_model, p.products_quantity, p.products_status, concat('".OSC_IMG_URL."',p.products_image) as image, p.products_price, d.products_name, d.products_description, m.manufacturers_name, m.manufacturers_id, pc.categories_id";
	$sql .= " FROM products as p ";
	$sql .= " JOIN products_description as d ON p.products_id = d.products_id ";
	$sql .= " JOIN products_to_categories pc ON p.products_id = pc.products_id ";
	$sql .= " LEFT JOIN manufacturers as m ON p.manufacturers_id=m.manufacturers_id";
	$sql .= " WHERE d.language_id =" . OSC_LANGUAGE_ID;
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
	if (!($connexion = mysql_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD)))   return new soap_fault("Server", "MySQL 1", "connexion impossible");
	if (!($db = mysql_select_db(DB_DATABASE, $connexion)))  return new soap_fault("Server", "MySQL 2", mysql_error());

//on recherche
	$sql = "SELECT p.products_id as OSC_id, p.products_model as model, p.products_quantity as quantity, p.products_status as status, concat('".OSC_IMG_URL."',p.products_image) as image, d.products_name as name, m.manufacturers_name as manufacturer, m.manufacturers_id";
	$sql .= " FROM products as p";
	$sql .= " JOIN products_description as d ON p.products_id = d.products_id "; 		 		$sql .= " LEFT JOIN manufacturers as m ON p.manufacturers_id=m.manufacturers_id";
	$sql .= " WHERE d.language_id =" . OSC_LANGUAGE_ID;

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

function saveImage($name,$content)
{
	$fich = fopen(OSCIMAGES.$name, 'wb');
	fwrite($fich,base64_decode($content));
	fclose($fich);
	return $name.' enregistré';
}


  
// OSC categories list from $catid 
function get_categorylist($catid)
{
 //on se connecte
 	if (!($connexion = mysql_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD)))   return new soap_fault("Server", "MySQL 1", "connexion impossible");
 	if (!($db = mysql_select_db(DB_DATABASE, $connexion)))  return new soap_fault("Server", "MySQL 2", mysql_error());
 
 	$sql = "select c.categories_id, cd.categories_name, c.parent_id ";
 	$sql .= " FROM categories c, categories_description cd ";
 	$sql .= " WHERE c.parent_id = '".$catid."' and c.categories_id = cd.categories_id and cd.language_id='" . OSC_LANGUAGE_ID ."' order by sort_order, cd.categories_name";
 
 	if (!($resquer = mysql_query($sql,$connexion)))  return new soap_fault("Server", "MySQL gey_categorylist ".$sql, mysql_error());
 
 		switch ($numrows = mysql_numrows($resquer)) {
 		case 0 : 
 			return new soap_fault("Server", "MySQL gey_categorylist", "pas de categories");
 			break;
 		default : 
 			$i = 0;
 			while ( $i < $numrows)  
 			{
 				$liste_cat[$i] =  mysql_fetch_array($resquer, MYSQL_ASSOC);
 				$i++;
 			}
 		}		
 	mysql_close($connexion);
  /* Sends the results to the client */
return $liste_cat;		
}
 

// Return the results.
$s->service($HTTP_RAW_POST_DATA);

?>
