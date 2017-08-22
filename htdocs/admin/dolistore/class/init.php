<html><head><title>CRUD Tutorial - Customer's list</title></head><body>
<?php
/*
* 2007-2013 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Open Software License (OSL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/osl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2013 PrestaShop SA
*  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
* PrestaShop Webservice Library
* @package PrestaShopWebservice
*/
// Here we define constants /!\ You need to replace this parameters
//https://dolistorecatalogpublickey1234567@vmdevwww.dolistore.com/api/
define('DEBUG', true);											// Debug mode
define('PS_SHOP_PATH', 'http://vmdevwww.dolistore.com/');		// Root path of your PrestaShop store
define('PS_WS_AUTH_KEY', 'dolistorecatalogpublickey1234567');	// Auth key (Get it in your Back Office)
require_once('./PSWebServiceLibrary.php');
// Here we make the WebService Call
try
{
	$webService = new PrestaShopWebservice(PS_SHOP_PATH, PS_WS_AUTH_KEY, DEBUG);

	// Here we set the option array for the Webservice : we want customers resources
	$opt['resource'] = 'categories';
	$opt['id'] = '1';

	// Call
	$xml = $webService->get($opt);
	// Here we get the elements from children of customers markup "customer"
	$resources = $xml->categories->children();
}
catch (PrestaShopWebserviceException $e)
{
	// Here we are dealing with errors
	$trace = $e->getTrace();
	if ($trace[0]['args'][0] == 404) echo 'Bad ID';
	else if ($trace[0]['args'][0] == 401) echo 'Bad auth key';
	else echo 'Other error';
}
// We set the Title
echo "<h1>Categories's List</h1>";
echo '<table border="5">';
// if $resources is set we can lists element in it otherwise do nothing cause there's an error
if (isset($resources))
{
		echo '<tr><th>Id</th></tr>';
		foreach ($resources as $resource)
		{
			// Iterates on the found IDs
			echo '<tr><td>'.$resource->attributes().'</td></tr>';
		}
}
echo '</table>';
?>
</body></html>