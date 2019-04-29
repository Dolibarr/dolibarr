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

/**
 * @package PrestaShopWebservice
 */
class PrestaShopWebservice
{

	/** @var string Shop URL */
	protected $url;

	/** @var string Authentification key */
	protected $key;

	/** @var boolean is debug activated */
	protected $debug;

	/** @var string PS version */
	protected $version;

	/** @var array compatible versions of PrestaShop Webservice */
	const PSCOMPATIBLEVERSIONMIN = '1.4.0.0';
	const PSCOMPATIBLEVERSIONMAX = '1.7.99.99';


	/**
	 * PrestaShopWebservice constructor. Throw an exception when CURL is not installed/activated
	 * <code>
	 * <?php
	 * require_once './PrestaShopWebservice.php';
	 * try
	 * {
	 * 	$ws = new PrestaShopWebservice('http://mystore.com/', 'ZQ88PRJX5VWQHCWE4EE7SQ7HPNX00RAJ', false);
	 * 	// Now we have a webservice object to play with
	 * }
	 * catch (PrestaShopWebserviceException $ex)
	 * {
	 * 	echo 'Error : '.$ex->getMessage();
	 * }
	 * ?>
	 * </code>
	 * @param string $url Root URL for the shop
	 * @param string $key Authentification key
	 * @param mixed $debug Debug mode Activated (true) or deactivated (false)
	*/
    public function __construct($url, $key, $debug = true)
    {
		if (!extension_loaded('curl'))
		  throw new PrestaShopWebserviceException('Please activate the PHP extension \'curl\' to allow use of PrestaShop webservice library');
		$this->url = $url;
		$this->key = $key;
		$this->debug = $debug;
		$this->version = 'unknown';
    }

	/**
	 * Take the status code and throw an exception if the server didn't return 200 or 201 code
	 *
	 * @param int $status_code Status code of an HTTP return
     * @return void
	 */
	protected function checkStatusCode($status_code)
	{
		$error_label = 'This call to PrestaShop Web Services failed and returned an HTTP status of %d. That means: %s.';
		switch($status_code)
		{
			case 200:
			case 201:
				break;
			case 204:
				throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'No content'));
			case 400:
				throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Bad Request'));
			case 401:
				throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Unauthorized'));
			case 404:
				throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Not Found'));
			case 405:
				throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Method Not Allowed'));
			case 500:
				throw new PrestaShopWebserviceException(sprintf($error_label, $status_code, 'Internal Server Error'));
			default:
				throw new PrestaShopWebserviceException('This call to PrestaShop Web Services returned an unexpected HTTP status of:' . $status_code);
		}
	}

	/**
	 * Handles a CURL request to PrestaShop Webservice. Can throw exception.
	 *
	 * @param 	string 	$url 			Resource name
	 * @param 	mixed 	$curl_params 	CURL parameters (sent to curl_set_opt)
	 * @return 	array 					status_code, response
	 */
	public function executeRequest($url, $curl_params = array())
	{
		$defaultParams = array(
			CURLOPT_HEADER => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLINFO_HEADER_OUT => true,
			CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
			CURLOPT_USERPWD => $this->key.':',
			CURLOPT_HTTPHEADER => array( 'Expect:' )
		);

		dol_syslog("curl_init url=".$url);
		$session = curl_init($url);

		$curl_options = array();
		foreach ($defaultParams as $defkey => $defval)
		{
			if (isset($curl_params[$defkey]))
				$curl_options[$defkey] = $curl_params[$defkey];
			else
				$curl_options[$defkey] = $defaultParams[$defkey];
		}
		foreach ($curl_params as $defkey => $defval)
			if (!isset($curl_options[$defkey]))
				$curl_options[$defkey] = $curl_params[$defkey];

		dol_syslog("curl curl_options = ".var_export($curl_options, true));
		curl_setopt_array($session, $curl_options);
		$response = curl_exec($session);

		$index = strpos($response, "\r\n\r\n");
		if ($index === false && $curl_params[CURLOPT_CUSTOMREQUEST] != 'HEAD')
			throw new PrestaShopWebserviceException('Bad HTTP response');

		$header = substr($response, 0, $index);
		$body = substr($response, $index + 4);

		$headerArrayTmp = explode("\n", $header);

		$headerArray = array();
		foreach ($headerArrayTmp as &$headerItem)
		{
			$tmp = explode(':', $headerItem);
			$tmp = array_map('trim', $tmp);
			if (count($tmp) == 2)
				$headerArray[$tmp[0]] = $tmp[1];
		}

		if (array_key_exists('PSWS-Version', $headerArray))
		{
			$this->version = $headerArray['PSWS-Version'];
			if (
				version_compare(PrestaShopWebservice::PSCOMPATIBLEVERSIONMIN, $headerArray['PSWS-Version']) == 1 ||
				version_compare(PrestaShopWebservice::PSCOMPATIBLEVERSIONMAX, $headerArray['PSWS-Version']) == -1
			)
			throw new PrestaShopWebserviceException('This library is not compatible with this version of PrestaShop. Please upgrade/downgrade this library');
		}

		if ($this->debug)
		{
			$this->printDebug('HTTP REQUEST HEADER', curl_getinfo($session, CURLINFO_HEADER_OUT));
			$this->printDebug('HTTP RESPONSE HEADER', $header);
		}
		$status_code = curl_getinfo($session, CURLINFO_HTTP_CODE);
		if ($status_code === 0)
			throw new PrestaShopWebserviceException('CURL Error: '.curl_error($session));
		curl_close($session);
		if ($this->debug)
		{
			if ($curl_params[CURLOPT_CUSTOMREQUEST] == 'PUT' || $curl_params[CURLOPT_CUSTOMREQUEST] == 'POST')
				$this->printDebug('XML SENT', urldecode($curl_params[CURLOPT_POSTFIELDS]));
			if ($curl_params[CURLOPT_CUSTOMREQUEST] != 'DELETE' && $curl_params[CURLOPT_CUSTOMREQUEST] != 'HEAD')
				$this->printDebug('RETURN HTTP BODY', $body);
		}
		return array('status_code' => $status_code, 'response' => $body, 'header' => $header);
	}

	/**
	 * Output debug info
	 *
	 * @param 	string	$title		Title
	 * @param 	string	$content	Content
	 * @return	void
	 */
	public function printDebug($title, $content)
	{
		echo '<div style="display:table;background:#CCC;font-size:8pt;padding:7px"><h6 style="font-size:9pt;margin:0">'.$title.'</h6><pre>'.htmlentities($content).'</pre></div>';
	}

	/**
	 * Return version
	 *
	 * @return	string		Version
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * Load XML from string. Can throw exception
	 *
	 * @param 	string 				$response 	String from a CURL response
	 * @return 	SimpleXMLElement 				status_code, response
	 */
	protected function parseXML($response)
	{
		if ($response != '')
		{
			libxml_clear_errors();
			libxml_use_internal_errors(true);
			$xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
			if (libxml_get_errors())
			{
				$msg = var_export(libxml_get_errors(), true);
				libxml_clear_errors();
				throw new PrestaShopWebserviceException('HTTP XML response is not parsable: '.$msg);
			}
			return $xml;
		}
		else
			throw new PrestaShopWebserviceException('HTTP response is empty');
	}

	/**
	 * Add (POST) a resource
	 * <p>Unique parameter must take : <br><br>
	 * 'resource' => Resource name<br>
	 * 'postXml' => Full XML string to add resource<br><br>
	 * Examples are given in the tutorial</p>
	 *
	 * @param 	array 				$options	Options
	 * @return 	SimpleXMLElement 				status_code, response
	 */
	public function add($options)
	{
		$xml = '';

		if (isset($options['resource'], $options['postXml']) || isset($options['url'], $options['postXml']))
		{
			$url = (isset($options['resource']) ? $this->url.'/api/'.$options['resource'] : $options['url']);
			$xml = $options['postXml'];
			if (isset($options['id_shop']))
				$url .= '&id_shop='.$options['id_shop'];
			if (isset($options['id_group_shop']))
				$url .= '&id_group_shop='.$options['id_group_shop'];
		}
		else
			throw new PrestaShopWebserviceException('Bad parameters given');
		$request = self::executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'POST', CURLOPT_POSTFIELDS => $xml));

		self::checkStatusCode($request['status_code']);
		return self::parseXML($request['response']);
	}

	/**
 	 * Retrieve (GET) a resource
	 * <p>Unique parameter must take : <br><br>
	 * 'url' => Full URL for a GET request of Webservice (ex: http://mystore.com/api/customers/1/)<br>
	 * OR<br>
	 * 'resource' => Resource name,<br>
	 * 'id' => ID of a resource you want to get<br><br>
	 * </p>
	 * <code>
	 * <?php
	 * require_once './PrestaShopWebservice.php';
	 * try
	 * {
	 * $ws = new PrestaShopWebservice('http://mystore.com/', 'ZQ88PRJX5VWQHCWE4EE7SQ7HPNX00RAJ', false);
	 * $xml = $ws->get(array('resource' => 'orders', 'id' => 1));
	 *	// Here in $xml, a SimpleXMLElement object you can parse
	 * foreach ($xml->children()->children() as $attName => $attValue)
	 * 	echo $attName.' = '.$attValue.'<br>';
	 * }
	 * catch (PrestaShopWebserviceException $ex)
	 * {
	 * 	echo 'Error : '.$ex->getMessage();
	 * }
	 * ?>
	 * </code>
	 * @param 	array 			$options 	Array representing resource to get.
	 * @return 	SimpleXMLElement 			status_code, response
	 */
	public function get($options)
	{
		if (isset($options['url']))
			$url = $options['url'];
		elseif (isset($options['resource']))
		{
			$url = $this->url.'/api/'.$options['resource'];
			$url_params = array();
			if (isset($options['id']))
				$url .= '/'.$options['id'];

			// @CHANGE LDR
			//$params = array('filter', 'display', 'sort', 'limit', 'id_shop', 'id_group_shop');
			$params = array('filter', 'display', 'sort', 'limit', 'id_shop', 'id_group_shop', 'date');
			foreach ($params as $p)
				foreach ($options as $k => $o)
					if (strpos($k, $p) !== false)
						$url_params[$k] = $options[$k];
			if (count($url_params) > 0)
				$url .= '?'.http_build_query($url_params);
		}
		else
			throw new PrestaShopWebserviceException('Bad parameters given ');

		$request = self::executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'GET'));
		self::checkStatusCode($request['status_code']);// check the response validity
		return self::parseXML($request['response']);
	}

	/**
 	 * Head method (HEAD) a resource
	 *
	 * @param 	array 				$options 	Array representing resource for head request.
	 * @return 	SimpleXMLElement 				status_code, response
	 */
	public function head($options)
	{
		if (isset($options['url']))
			$url = $options['url'];
		elseif (isset($options['resource']))
		{
			$url = $this->url.'/api/'.$options['resource'];
			$url_params = array();
			if (isset($options['id']))
				$url .= '/'.$options['id'];

			$params = array('filter', 'display', 'sort', 'limit');
			foreach ($params as $p)
				foreach ($options as $k => $o)
					if (strpos($k, $p) !== false)
						$url_params[$k] = $options[$k];
			if (count($url_params) > 0)
				$url .= '?'.http_build_query($url_params);
		}
		else
			throw new PrestaShopWebserviceException('Bad parameters given');
		$request = self::executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'HEAD', CURLOPT_NOBODY => true));
		self::checkStatusCode($request['status_code']);// check the response validity
		return $request['header'];
	}
	/**
	 * Edit (PUT) a resource
	 * <p>Unique parameter must take : <br><br>
	 * 'resource' => Resource name ,<br>
	 * 'id' => ID of a resource you want to edit,<br>
	 * 'putXml' => Modified XML string of a resource<br><br>
	 * Examples are given in the tutorial</p>
	 *
	 * @param 	array 				$options 	Array representing resource to edit.
	 * @return	SimpleXMLElement 				status_code, response
	 */
	public function edit($options)
	{
		$xml = '';
		if (isset($options['url']))
			$url = $options['url'];
		elseif ((isset($options['resource'], $options['id']) || isset($options['url'])) && $options['putXml'])
		{
			$url = (isset($options['url']) ? $options['url'] : $this->url.'/api/'.$options['resource'].'/'.$options['id']);
			$xml = $options['putXml'];
			if (isset($options['id_shop']))
				$url .= '&id_shop='.$options['id_shop'];
			if (isset($options['id_group_shop']))
				$url .= '&id_group_shop='.$options['id_group_shop'];
		}
		else
			throw new PrestaShopWebserviceException('Bad parameters given');

		$request = self::executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'PUT', CURLOPT_POSTFIELDS => $xml));
		self::checkStatusCode($request['status_code']);// check the response validity
		return self::parseXML($request['response']);
	}

	/**
	 * Delete (DELETE) a resource.
	 * Unique parameter must take : <br><br>
	 * 'resource' => Resource name<br>
	 * 'id' => ID or array which contains IDs of a resource(s) you want to delete<br><br>
	 * <code>
	 * <?php
	 * require_once('./PrestaShopWebservice.php');
	 * try
	 * {
	 * $ws = new PrestaShopWebservice('http://mystore.com/', 'ZQ88PRJX5VWQHCWE4EE7SQ7HPNX00RAJ', false);
	 * $xml = $ws->delete(array('resource' => 'orders', 'id' => 1));
	 *	// Following code will not be executed if an exception is thrown.
	 * 	echo 'Successfully deleted.';
	 * }
	 * catch (PrestaShopWebserviceException $ex)
	 * {
	 * 	echo 'Error : '.$ex->getMessage();
	 * }
	 * ?>
	 * </code>
	 *
	 * @param  array       $options        Array representing resource to delete.
	 * @return boolean                     True
	 */
	public function delete($options)
	{
	    if (isset($options['url']))
	        $url = $options['url'];
	    elseif (isset($options['resource']) && isset($options['id']))
    	    if (is_array($options['id']))
    	        $url = $this->url.'/api/'.$options['resource'].'/?id=['.implode(',', $options['id']).']';
    	    else
    	        $url = $this->url.'/api/'.$options['resource'].'/'.$options['id'];
	    if (isset($options['id_shop']))
	        $url .= '&id_shop='.$options['id_shop'];
	    if (isset($options['id_group_shop']))
	        $url .= '&id_group_shop='.$options['id_group_shop'];
	    $request = self::executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'DELETE'));
	    self::checkStatusCode($request['status_code']);// check the response validity
	    return true;
	}
}

/**
 * @package PrestaShopWebservice
 */
class PrestaShopWebserviceException extends Exception
{

}
