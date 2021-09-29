<?php
/* Copyright (C) 2008-2020	Laurent Destailleur			<eldy@users.sourceforge.net>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file			htdocs/core/lib/geturl.lib.php
 *	\brief			This file contains functions dedicated to get URLs.
 */

/**
 * Function to get a content from an URL (use proxy if proxy defined).
 * Support Dolibarr setup for timeout and proxy.
 * Enhancement of CURL to add an anti SSRF protection:
 * - you can set MAIN_SECURITY_ANTI_SSRF_SERVER_IP to set static ip of server
 * - common local lookup ips like 127.*.*.* are automatically added
 *
 * @param	string	  $url 				    URL to call.
 * @param	string    $postorget		    'POST', 'GET', 'HEAD', 'PUT', 'PUTALREADYFORMATED', 'POSTALREADYFORMATED', 'DELETE'
 * @param	string    $param			    Parameters of URL (x=value1&y=value2) or may be a formated content with $postorget='PUTALREADYFORMATED'
 * @param	integer   $followlocation		0=Do not follow, 1=Follow location.
 * @param	string[]  $addheaders			Array of string to add into header. Example: ('Accept: application/xrds+xml', ....)
 * @param	string[]  $allowedschemes		List of schemes that are allowed ('http' + 'https' only by default)
 * @param	int		  $localurl				0=Only external URL are possible, 1=Only local URL, 2=Both external and local URL are allowed.
 * @return	array						    Returns an associative array containing the response from the server array('content'=>response, 'curl_error_no'=>errno, 'curl_error_msg'=>errmsg...)
 */
function getURLContent($url, $postorget = 'GET', $param = '', $followlocation = 1, $addheaders = array(), $allowedschemes = array('http', 'https'), $localurl = 0)
{
	//declaring of global variables
	global $conf;
	$USE_PROXY = empty($conf->global->MAIN_PROXY_USE) ? 0 : $conf->global->MAIN_PROXY_USE;
	$PROXY_HOST = empty($conf->global->MAIN_PROXY_HOST) ? 0 : $conf->global->MAIN_PROXY_HOST;
	$PROXY_PORT = empty($conf->global->MAIN_PROXY_PORT) ? 0 : $conf->global->MAIN_PROXY_PORT;
	$PROXY_USER = empty($conf->global->MAIN_PROXY_USER) ? 0 : $conf->global->MAIN_PROXY_USER;
	$PROXY_PASS = empty($conf->global->MAIN_PROXY_PASS) ? 0 : $conf->global->MAIN_PROXY_PASS;

	dol_syslog("getURLContent postorget=".$postorget." URL=".$url." param=".$param);

	//setting the curl parameters.
	$ch = curl_init();

	/*print $API_Endpoint."-".$API_version."-".$PAYPAL_API_USER."-".$PAYPAL_API_PASSWORD."-".$PAYPAL_API_SIGNATURE."<br>";
	 print $USE_PROXY."-".$gv_ApiErrorURL."<br>";
	 print $nvpStr;
	 exit;*/
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Dolibarr geturl function');

	// We use @ here because this may return warning if safe mode is on or open_basedir is on (following location is forbidden when safe mode is on).
	// We force value to false so we will manage redirection ourself later.
	@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

	if (is_array($addheaders) && count($addheaders)) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $addheaders);
	}
	curl_setopt($ch, CURLINFO_HEADER_OUT, true); // To be able to retrieve request header and log it

	// By default use tls decied by PHP.
	// You can force, if supported a version like TLSv1 or TLSv1.2
	if (!empty($conf->global->MAIN_CURL_SSLVERSION)) {
		curl_setopt($ch, CURLOPT_SSLVERSION, $conf->global->MAIN_CURL_SSLVERSION);
	}
	//curl_setopt($ch, CURLOPT_SSLVERSION, 6); for tls 1.2

	// Turning off the server and peer verification(TrustManager Concept).
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

	// Restrict use to some protocols only
	$protocols = 0;
	if (is_array($allowedschemes)) {
		foreach ($allowedschemes as $allowedscheme) {
			if ($allowedscheme == 'http') {
				$protocols |= CURLPROTO_HTTP;
			}
			if ($allowedscheme == 'https') {
				$protocols |= CURLPROTO_HTTPS;
			}
		}
		curl_setopt($ch, CURLOPT_PROTOCOLS, $protocols);
		curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, $protocols);
	}

	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, empty($conf->global->MAIN_USE_CONNECT_TIMEOUT) ? 5 : $conf->global->MAIN_USE_CONNECT_TIMEOUT);
	curl_setopt($ch, CURLOPT_TIMEOUT, empty($conf->global->MAIN_USE_RESPONSE_TIMEOUT) ? 30 : $conf->global->MAIN_USE_RESPONSE_TIMEOUT);

	//curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);	// PHP 5.5
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // We want response
	if ($postorget == 'POST') {
		curl_setopt($ch, CURLOPT_POST, 1); // POST
		curl_setopt($ch, CURLOPT_POSTFIELDS, $param); // Setting param x=a&y=z as POST fields
	} elseif ($postorget == 'POSTALREADYFORMATED') {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST'); // HTTP request is 'POST' but param string is taken as it is
		curl_setopt($ch, CURLOPT_POSTFIELDS, $param); // param = content of post, like a xml string
	} elseif ($postorget == 'PUT') {
		$array_param = null;
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); // HTTP request is 'PUT'
		if (!is_array($param)) {
			parse_str($param, $array_param);
		} else {
			dol_syslog("parameter param must be a string", LOG_WARNING);
			$array_param = $param;
		}
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array_param)); // Setting param x=a&y=z as PUT fields
	} elseif ($postorget == 'PUTALREADYFORMATED') {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); // HTTP request is 'PUT'
		curl_setopt($ch, CURLOPT_POSTFIELDS, $param); // param = content of post, like a xml string
	} elseif ($postorget == 'HEAD') {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD'); // HTTP request is 'HEAD'
		curl_setopt($ch, CURLOPT_NOBODY, true);
	} elseif ($postorget == 'DELETE') {
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE'); // POST
	} else {
		curl_setopt($ch, CURLOPT_POST, 0); // GET
	}

	//if USE_PROXY constant set at begin of this method.
	if ($USE_PROXY) {
		dol_syslog("getURLContent set proxy to ".$PROXY_HOST.":".$PROXY_PORT." - ".$PROXY_USER.":".$PROXY_PASS);
		//curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); // Curl 7.10
		curl_setopt($ch, CURLOPT_PROXY, $PROXY_HOST.":".$PROXY_PORT);
		if ($PROXY_USER) {
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $PROXY_USER.":".$PROXY_PASS);
		}
	}

	$newUrl = $url;
	$maxRedirection = 5;
	$info = array();
	$response = '';

	do {
		if ($maxRedirection < 1) {
			break;
		}

		curl_setopt($ch, CURLOPT_URL, $newUrl);

		// Parse $newUrl
		$newUrlArray = parse_url($newUrl);
		$hosttocheck = $newUrlArray['host'];
		$hosttocheck = str_replace(array('[', ']'), '', $hosttocheck); // Remove brackets of IPv6

		// Deny some reserved host names
		if (in_array($hosttocheck, array('metadata.google.internal'))) {
			$info['http_code'] = 400;
			$info['content'] = 'Error bad hostname '.$hosttocheck.' (Used by Google metadata). This value for hostname is not allowed.';
			break;
		}

		// Clean host name $hosttocheck to convert it into an IP $iptocheck
		if (in_array($hosttocheck, array('localhost', 'localhost.domain'))) {
			$iptocheck = '127.0.0.1';
		} elseif (in_array($hosttocheck, array('ip6-localhost', 'ip6-loopback'))) {
			$iptocheck = '::1';
		} else {
			// Resolve $hosttocheck to get the IP $iptocheck and set CURLOPT_CONNECT_TO to use this ip so curl will not try another resolution that may give a different result
			if (function_exists('gethostbyname')) {
				$iptocheck = gethostbyname($hosttocheck);
			} else {
				$iptocheck = $hosttocheck;
			}
			// TODO Resolve ip v6
		}

		// Check $iptocheck is an IP (v4 or v6), if not clear value.
		if (!filter_var($iptocheck, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {	// This is not an IP, we clean data
			$iptocheck = '0'; //
		}

		if ($iptocheck) {
			if ($localurl == 0) {	// Only external url allowed (dangerous, may allow to get malware)
				if (!filter_var($iptocheck, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
					// Deny ips like 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, 0.0.0.0/8, 169.254.0.0/16, 127.0.0.0/8 et 240.0.0.0/4, ::1/128, ::/128, ::ffff:0:0/96, fe80::/10...
					$info['http_code'] = 400;
					$info['content'] = 'Error bad hostname IP (private or reserved range). Must be an external URL.';
					break;
				}
				if (!empty($_SERVER["SERVER_ADDR"]) && $iptocheck == $_SERVER["SERVER_ADDR"]) {
					$info['http_code'] = 400;
					$info['content'] = 'Error bad hostname IP (IP is a local IP). Must be an external URL.';
					break;
				}
				if (!empty($conf->global->MAIN_SECURITY_ANTI_SSRF_SERVER_IP) && in_array($iptocheck, explode(',', $conf->global->MAIN_SECURITY_ANTI_SSRF_SERVER_IP))) {
					$info['http_code'] = 400;
					$info['content'] = 'Error bad hostname IP (IP is a local IP defined into MAIN_SECURITY_SERVER_IP). Must be an external URL.';
					break;
				}
			}
			if ($localurl == 1) {	// Only local url allowed (dangerous, may allow to get metadata on server or make internal port scanning)
				// Deny ips NOT like 10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16, 0.0.0.0/8, 169.254.0.0/16, 127.0.0.0/8 et 240.0.0.0/4, ::1/128, ::/128, ::ffff:0:0/96, fe80::/10...
				if (filter_var($iptocheck, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
					$info['http_code'] = 400;
					$info['content'] = 'Error bad hostname '.$iptocheck.'. Must be a local URL.';
					break;
				}
				if (!empty($conf->global->MAIN_SECURITY_ANTI_SSRF_SERVER_IP) && !in_array($iptocheck, explode(',', $conf->global->MAIN_SECURITY_ANTI_SSRF_SERVER_IP))) {
					$info['http_code'] = 400;
					$info['content'] = 'Error bad hostname IP (IP is not a local IP defined into list MAIN_SECURITY_SERVER_IP). Must be a local URL in allowed list.';
					break;
				}
			}

			// Common check (local and external)
			if (in_array($iptocheck, array('100.100.100.200'))) {
				$info['http_code'] = 400;
				$info['content'] = 'Error bad hostname IP (Used by Alibaba metadata). Must be an external URL.';
				break;
			}

			// Set CURLOPT_CONNECT_TO so curl will not try another resolution that may give a different result. Possible only on PHP v7+
			if (defined('CURLOPT_CONNECT_TO')) {
				$connect_to = array(sprintf("%s:%d:%s:%d", $newUrlArray['host'], empty($newUrlArray['port'])?'':$newUrlArray['port'], $iptocheck, empty($newUrlArray['port'])?'':$newUrlArray['port']));
				//var_dump($newUrlArray);
				//var_dump($connect_to);
				curl_setopt($ch, CURLOPT_CONNECT_TO, $connect_to);
			}
		}

		// Getting response from server
		$response = curl_exec($ch);

		$info = curl_getinfo($ch); // Reading of request must be done after sending request
		$http_code = $info['http_code'];

		if ($followlocation && ($http_code == 301 || $http_code == 302 || $http_code == 303 || $http_code == 307)) {
			$newUrl = $info['redirect_url'];
			$maxRedirection--;
			// TODO Use $info['local_ip'] and $info['primary_ip'] ?
			continue;
		} else {
			$http_code = 0;
		}
	} while ($http_code);

	$request = curl_getinfo($ch, CURLINFO_HEADER_OUT); // Reading of request must be done after sending request

	dol_syslog("getURLContent request=".$request);
	//dol_syslog("getURLContent response =".response);	// This may contains binary data, so we dont output it
	dol_syslog("getURLContent response size=".strlen($response)); // This may contains binary data, so we dont output it

	$rep = array();
	if (curl_errno($ch)) {
		// Add keys to $rep
		$rep['content'] = $response;

		// moving to display page to display curl errors
		$rep['curl_error_no'] = curl_errno($ch);
		$rep['curl_error_msg'] = curl_error($ch);

		dol_syslog("getURLContent response array is ".join(',', $rep));
	} else {
		//$info = curl_getinfo($ch);

		// Add keys to $rep
		$rep = $info;
		//$rep['header_size']=$info['header_size'];
		//$rep['http_code']=$info['http_code'];
		dol_syslog("getURLContent http_code=".$rep['http_code']);

		// Add more keys to $rep
		if ($response) {
			$rep['content'] = $response;
		}
		$rep['curl_error_no'] = '';
		$rep['curl_error_msg'] = '';
	}

	//closing the curl
	curl_close($ch);

	return $rep;
}


/**
 * Function get second level domain name.
 * For example: https://www.abc.mydomain.com/dir/page.html return 'mydomain'
 *
 * @param	string	  $url 				    Full URL.
 * @param	int	 	  $mode					0=return 'mydomain', 1=return 'mydomain.com', 2=return 'abc.mydomain.com'
 * @return	string						    Returns domaine name
 */
function getDomainFromURL($url, $mode = 0)
{
	$tmpdomain = preg_replace('/^https?:\/\//i', '', $url); // Remove http(s)://
	$tmpdomain = preg_replace('/\/.*$/i', '', $tmpdomain); // Remove part after domain
	if ($mode == 2) {
		$tmpdomain = preg_replace('/^.*\.([^\.]+)\.([^\.]+)\.([^\.]+)$/', '\1.\2.\3', $tmpdomain); // Remove part 'www.' before 'abc.mydomain.com'
	} else {
		$tmpdomain = preg_replace('/^.*\.([^\.]+)\.([^\.]+)$/', '\1.\2', $tmpdomain); // Remove part 'www.abc.' before 'mydomain.com'
	}
	if (empty($mode)) {
		$tmpdomain = preg_replace('/\.[^\.]+$/', '', $tmpdomain); // Remove first level domain (.com, .net, ...)
	}

	return $tmpdomain;
}

/**
 * Function root url from a long url
 * For example: https://www.abc.mydomain.com/dir/page.html return 'https://www.abc.mydomain.com'
 * For example: http://www.abc.mydomain.com/ return 'https://www.abc.mydomain.com'
 *
 * @param	string	  $url 				    Full URL.
 * @return	string						    Returns root url
 */
function getRootURLFromURL($url)
{
	$prefix = '';
	$tmpurl = $url;
	$reg = null;
	if (preg_match('/^(https?:\/\/)/i', $tmpurl, $reg)) {
		$prefix = $reg[1];
	}
	$tmpurl = preg_replace('/^https?:\/\//i', '', $tmpurl); // Remove http(s)://
	$tmpurl = preg_replace('/\/.*$/i', '', $tmpurl); // Remove part after domain

	return $prefix.$tmpurl;
}

/**
 * Function to remove comments into HTML content
 *
 * @param	string	  $content 				Text content
 * @return	string						    Returns text without HTML comments
 */
function removeHtmlComment($content)
{
	$content = preg_replace('/<!--[^\-]+-->/', '', $content);
	return $content;
}
