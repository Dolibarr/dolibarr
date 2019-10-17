<?php
/* Copyright (C) 2008-2013	Laurent Destailleur			<eldy@users.sourceforge.net>
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
 *	\brief			This file contains functions dedicated to get URL.
 */

/**
 * Function to get a content from an URL (use proxy if proxy defined)
 *
 * @param	string	  $url 				    URL to call.
 * @param	string    $postorget		    'POST', 'GET', 'HEAD', 'PUT', 'PUTALREADYFORMATED', 'POSTALREADYFORMATED', 'DELETE'
 * @param	string    $param			    Parameters of URL (x=value1&y=value2) or may be a formated content with PUTALREADYFORMATED
 * @param	integer   $followlocation		1=Follow location, 0=Do not follow
 * @param	string[]  $addheaders			Array of string to add into header. Example: ('Accept: application/xrds+xml', ....)
 * @return	array						    Returns an associative array containing the response from the server array('content'=>response,'curl_error_no'=>errno,'curl_error_msg'=>errmsg...)
 */
function getURLContent($url, $postorget = 'GET', $param = '', $followlocation = 1, $addheaders = array())
{
    //declaring of global variables
    global $conf;
    $USE_PROXY=empty($conf->global->MAIN_PROXY_USE)?0:$conf->global->MAIN_PROXY_USE;
    $PROXY_HOST=empty($conf->global->MAIN_PROXY_HOST)?0:$conf->global->MAIN_PROXY_HOST;
    $PROXY_PORT=empty($conf->global->MAIN_PROXY_PORT)?0:$conf->global->MAIN_PROXY_PORT;
    $PROXY_USER=empty($conf->global->MAIN_PROXY_USER)?0:$conf->global->MAIN_PROXY_USER;
    $PROXY_PASS=empty($conf->global->MAIN_PROXY_PASS)?0:$conf->global->MAIN_PROXY_PASS;

	dol_syslog("getURLContent postorget=".$postorget." URL=".$url." param=".$param);

    //setting the curl parameters.
    $ch = curl_init();

    /*print $API_Endpoint."-".$API_version."-".$PAYPAL_API_USER."-".$PAYPAL_API_PASSWORD."-".$PAYPAL_API_SIGNATURE."<br>";
     print $USE_PROXY."-".$gv_ApiErrorURL."<br>";
     print $nvpStr;
     exit;*/
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Dolibarr geturl function');

	@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, ($followlocation?true:false));   // We use @ here because this may return warning if safe mode is on or open_basedir is on

	if (count($addheaders)) curl_setopt($ch, CURLOPT_HTTPHEADER, $addheaders);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);	// To be able to retrieve request header and log it

	// By default use tls decied by PHP.
	// You can force, if supported a version like TLSv1 or TLSv1.2
	if (! empty($conf->global->MAIN_CURL_SSLVERSION)) curl_setopt($ch, CURLOPT_SSLVERSION, $conf->global->MAIN_CURL_SSLVERSION);
	//curl_setopt($ch, CURLOPT_SSLVERSION, 6); for tls 1.2

    //turning off the server and peer verification(TrustManager Concept).
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, empty($conf->global->MAIN_USE_CONNECT_TIMEOUT)?5:$conf->global->MAIN_USE_CONNECT_TIMEOUT);
    curl_setopt($ch, CURLOPT_TIMEOUT, empty($conf->global->MAIN_USE_RESPONSE_TIMEOUT)?30:$conf->global->MAIN_USE_RESPONSE_TIMEOUT);

    //curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);	// PHP 5.5
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		// We want response
    if ($postorget == 'POST')
    {
    	curl_setopt($ch, CURLOPT_POST, 1);	// POST
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $param);	// Setting param x=a&y=z as POST fields
    }
    elseif ($postorget == 'POSTALREADYFORMATED')
    {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST'); // HTTP request is 'POST' but param string is taken as it is
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);	// param = content of post, like a xml string
    }
    elseif ($postorget == 'PUT')
    {
        $array_param=null;
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); // HTTP request is 'PUT'
    	if (! is_array($param)) parse_str($param, $array_param);
    	else
    	{
    	    dol_syslog("parameter param must be a string", LOG_WARNING);
    	    $array_param=$param;
    	}
    	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array_param));	// Setting param x=a&y=z as PUT fields
    }
    elseif ($postorget == 'PUTALREADYFORMATED')
    {
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); // HTTP request is 'PUT'
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $param);	// param = content of post, like a xml string
    }
    elseif ($postorget == 'HEAD')
    {
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD'); // HTTP request is 'HEAD'
    	curl_setopt($ch, CURLOPT_NOBODY, true);
    }
    elseif ($postorget == 'DELETE')
    {
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');	// POST
    }
    else
    {
    	curl_setopt($ch, CURLOPT_POST, 0);			// GET
    }

    //if USE_PROXY constant set at begin of this method.
    if ($USE_PROXY)
    {
        dol_syslog("getURLContent set proxy to ".$PROXY_HOST. ":" . $PROXY_PORT." - ".$PROXY_USER. ":" . $PROXY_PASS);
        //curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); // Curl 7.10
        curl_setopt($ch, CURLOPT_PROXY, $PROXY_HOST. ":" . $PROXY_PORT);
        if ($PROXY_USER) curl_setopt($ch, CURLOPT_PROXYUSERPWD, $PROXY_USER. ":" . $PROXY_PASS);
    }

    //getting response from server
    $response = curl_exec($ch);

    $request = curl_getinfo($ch, CURLINFO_HEADER_OUT);	// Reading of request must be done after sending request

    dol_syslog("getURLContent request=".$request);
    //dol_syslog("getURLContent response =".response);	// This may contains binary data, so we dont output it
    dol_syslog("getURLContent response size=".strlen($response));	// This may contains binary data, so we dont output it

    $rep=array();
    if (curl_errno($ch))
    {
        // Ad keys to $rep
        $rep['content']=$response;

        // moving to display page to display curl errors
		$rep['curl_error_no']=curl_errno($ch);
        $rep['curl_error_msg']=curl_error($ch);

		dol_syslog("getURLContent response array is ".join(',', $rep));
    }
    else
    {
    	$info = curl_getinfo($ch);

    	// Ad keys to $rep
    	$rep = $info;
    	//$rep['header_size']=$info['header_size'];
    	//$rep['http_code']=$info['http_code'];
    	dol_syslog("getURLContent http_code=".$rep['http_code']);

        // Add more keys to $rep
        $rep['content']=$response;
    	$rep['curl_error_no']='';
    	$rep['curl_error_msg']='';

    	//closing the curl
        curl_close($ch);
    }

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
	$tmpdomain = preg_replace('/^https?:\/\//i', '', $url);				// Remove http(s)://
	$tmpdomain = preg_replace('/\/.*$/i', '', $tmpdomain);				// Remove part after domain
	if ($mode == 2)
	{
		$tmpdomain = preg_replace('/^.*\.([^\.]+)\.([^\.]+)\.([^\.]+)$/', '\1.\2.\3', $tmpdomain);	// Remove part 'www.' before 'abc.mydomain.com'
	}
	else
	{
		$tmpdomain = preg_replace('/^.*\.([^\.]+)\.([^\.]+)$/', '\1.\2', $tmpdomain);				// Remove part 'www.abc.' before 'mydomain.com'
	}
	if (empty($mode))
	{
		$tmpdomain = preg_replace('/\.[^\.]+$/', '', $tmpdomain);			// Remove first level domain (.com, .net, ...)
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
	$prefix='';
	$tmpurl = $url;
	$reg = null;
	if (preg_match('/^(https?:\/\/)/i', $tmpurl, $reg)) $prefix = $reg[1];
	$tmpurl = preg_replace('/^https?:\/\//i', '', $tmpurl);				// Remove http(s)://
	$tmpurl = preg_replace('/\/.*$/i', '', $tmpurl);					// Remove part after domain

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
