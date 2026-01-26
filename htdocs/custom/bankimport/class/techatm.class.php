<?php

/**
 * Class TechATM
 * Class used for technical module updates
 */

namespace bankimport;

class TechATM
{

	/**
	 * @var DoliDb		Database handler (result of a new DoliDB)
	 */
	public $db;

	/**
	 * @var string 		Error string
	 * @see             $errors
	 */
	public $error;

	/**
	 * @var string[]	Array of error strings
	 */
	public $errors = array();

	/**
	 * @var reponse_code  a http_response_header parsed reponse code
	 */
	public $reponse_code;

	/**
	 * @var http_response_header  the last call $http_response_header
	 */

	public $http_response_header;

	/**
	 * @var TResponseHeader  the last call $http_response_header parsed <- for most common usage (see self::parseHeaders() function)
	 */
	public $TResponseHeader;

	/**
	 * URL for technical API calls
	 */
	const ATM_TECH_URL = 'https://tech.atm-consulting.fr';


	/**
	 * Version of this class
	 * If the approach changes, we can handle different response formats (e.g. JSON instead of plain HTML)
	 */
	const CALL_VERSION = 1.0;

	/**
	 *  Constructor
	 *
	 *  @param DoliDB $db
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * @param DolibarrModules $moduleDescriptor
	 */
	function getAboutPage($moduleDescriptor, $useCache = true){
		global $langs;

		$url = self::ATM_TECH_URL.'/modules/modules-page-about.php';
		$url.= '?module='.$moduleDescriptor->name;
		$url.= '&id='.$moduleDescriptor->numero;
		$url.= '&version='.$moduleDescriptor->version;
		$url.= '&langs='.$langs->defaultlang;
		$url.= '&callv='.self::CALL_VERSION;

		$cachePath = DOL_DATA_ROOT . "/modules-atm/temp/about_page";
		$cacheFileName = dol_sanitizeFileName($moduleDescriptor->name.'_'.$langs->defaultlang).'.html';
		$cacheFilePath = $cachePath.'/'.$cacheFileName;

		if($useCache && is_readable($cacheFilePath)){
			$lastChange = filemtime($cacheFilePath);
			if($lastChange > time() - 86400){
				$content = @file_get_contents($cacheFilePath);
				if($content !== false){
					return $content;
				}
			}
		}

		$content = $this->getContents($url);

		if(!$content){
			$content = '';
			// About page goes here
			$content.= '<div style="float: left;"><img src="../img/Dolibarr_Preferred_Partner_logo.png" /></div>';
			$content.= '<div>'.$langs->trans('ATMAbout').'</div>';
			$content.= '<hr/><center>';
			$content.= '<a href="http://www.atm-consulting.fr" target="_blank"><img src="../img/ATM_logo.jpg" /></a>';
			$content.= '</center>';
		}

		if($useCache){
			if(!is_dir($cachePath)){
				$res = dol_mkdir($cachePath, DOL_DATA_ROOT);
			}else{
				$res = true;
			}

			if($res){
				$comment = '<!-- Generated from '.$url.' -->'."\r\n";

				file_put_contents(
					$cacheFilePath,
					$comment.$content
				);
			}
		}

		return $content;
	}

	/**
	 * @param string $moduleTechMane
	 */
	public static function getModuleDocUrl($moduleTechMane){
		$url = self::ATM_TECH_URL.'/modules/doc-redirect.php';
		$url.= '?module='.$moduleTechMane;
		return $url;
	}

	/**
	 * @param DolibarrModules $moduleDescriptor
	 */
	public static function getLastModuleVersionUrl($moduleDescriptor){
		$url = self::ATM_TECH_URL.'/modules/modules-last-version.php';
		$url.= '?module='.$moduleDescriptor->name;
		$url.= '&number='.$moduleDescriptor->numero;
		$url.= '&version='.$moduleDescriptor->version;
		$url.= '&dolversion='.DOL_VERSION;
		$url.= '&callv='.self::CALL_VERSION;
		return $url;
	}


	/**
	 * @param $url
	 * @return false|object
	 */
	public function getJsonData($url){
		$this->data = false;
		$res = @file_get_contents($url);
		$this->http_response_header = $http_response_header;
		$this->TResponseHeader = self::parseHeaders($http_response_header);
		if($res !== false){
			$pos = strpos($res, '{');
			if($pos > 0){
				// this means there is an error or the output is not clean
				$res = substr($res, $pos);
			}

			$this->data = json_decode($res);
		}

		return $this->data;
	}

	/**
	 * @param $url
	 * @return false|string
	 */
	public function getContents($url){
		$this->data = false;
		$res = @file_get_contents($url);
		$this->http_response_header = $http_response_header;
		$this->TResponseHeader = self::parseHeaders($http_response_header);
		if($res !== false){
			$this->data = $res;
		}
		return $this->data;
	}

	public static function http_response_code_msg($code = NULL)
	{
		if ($code !== NULL) {

			switch ($code) {
				case 100:
					$text = 'Continue';
					break;
				case 101:
					$text = 'Switching Protocols';
					break;
				case 200:
					$text = 'OK';
					break;
				case 201:
					$text = 'Created';
					break;
				case 202:
					$text = 'Accepted';
					break;
				case 203:
					$text = 'Non-Authoritative Information';
					break;
				case 204:
					$text = 'No Content';
					break;
				case 205:
					$text = 'Reset Content';
					break;
				case 206:
					$text = 'Partial Content';
					break;
				case 300:
					$text = 'Multiple Choices';
					break;
				case 301:
					$text = 'Moved Permanently';
					break;
				case 302:
					$text = 'Moved Temporarily';
					break;
				case 303:
					$text = 'See Other';
					break;
				case 304:
					$text = 'Not Modified';
					break;
				case 305:
					$text = 'Use Proxy';
					break;
				case 400:
					$text = 'Bad Request';
					break;
				case 401:
					$text = 'Unauthorized';
					break;
				case 402:
					$text = 'Payment Required';
					break;
				case 403:
					$text = 'Forbidden';
					break;
				case 404:
					$text = 'Not Found';
					break;
				case 405:
					$text = 'Method Not Allowed';
					break;
				case 406:
					$text = 'Not Acceptable';
					break;
				case 407:
					$text = 'Proxy Authentication Required';
					break;
				case 408:
					$text = 'Request Time-out';
					break;
				case 409:
					$text = 'Conflict';
					break;
				case 410:
					$text = 'Gone';
					break;
				case 411:
					$text = 'Length Required';
					break;
				case 412:
					$text = 'Precondition Failed';
					break;
				case 413:
					$text = 'Request Entity Too Large';
					break;
				case 414:
					$text = 'Request-URI Too Large';
					break;
				case 415:
					$text = 'Unsupported Media Type';
					break;
				case 500:
					$text = 'Internal Server Error';
					break;
				case 501:
					$text = 'Not Implemented';
					break;
				case 502:
					$text = 'Bad Gateway';
					break;
				case 503:
					$text = 'Service Unavailable';
					break;
				case 504:
					$text = 'Gateway Time-out';
					break;
				case 505:
					$text = 'HTTP Version not supported';
					break;
				default:
					$text = 'Unknown http status code "' . htmlentities($code) . '"';
					break;
			}

			return $text;

		} else {
			return $text = 'Unknown http status code NULL';
		}
	}

	public static function parseHeaders( $headers )
	{
		$head = array();
		if(!is_array($headers)){
			return $head;
		}

		foreach( $headers as $k=>$v )
		{
			$t = explode( ':', $v, 2 );
			if( isset( $t[1] ) )
				$head[ trim($t[0]) ] = trim( $t[1] );
			else
			{
				$head[] = $v;
				if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
					$head['reponse_code'] = intval($out[1]);
			}
		}
		return $head;
	}

}
