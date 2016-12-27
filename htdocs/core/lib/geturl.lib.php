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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/core/lib/geturl.lib.php
 *	\brief			This file contains functions dedicated to get URL.
 */

/**
 * Function get content from an URL (use proxy if proxy defined)
 *
 * @param	string	  $url 				    URL to call.
 * @param	string    $postorget		    'POST', 'GET', 'HEAD', 'PUT', 'PUTALREADYFORMATED', 'DELETE'
 * @param	string    $param			    Parameters of URL (x=value1&y=value2) or may be a formated content with PUTALREADYFORMATED
 * @param	integer   $followlocation		1=Follow location, 0=Do not follow
 * @param	string[]  $addheaders			Array of string to add into header. Example: ('Accept: application/xrds+xml', ....)
 * @return	array						    Returns an associative array containing the response from the server array('content'=>response,'curl_error_no'=>errno,'curl_error_msg'=>errmsg...)
 */
function getURLContent($url,$postorget='GET',$param='',$followlocation=1,$addheaders=array())
{
    //declaring of global variables
    global $conf, $langs;
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

	// TLSv1 by default or change to TLSv1.2 in module configuration
    //curl_setopt($ch, CURLOPT_SSLVERSION, (empty($conf->global->MAIN_CURL_SSLVERSION)?1:$conf->global->MAIN_CURL_SSLVERSION));
    
    //turning off the server and peer verification(TrustManager Concept).
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, empty($conf->global->MAIN_USE_CONNECT_TIMEOUT)?5:$conf->global->MAIN_USE_CONNECT_TIMEOUT);
    curl_setopt($ch, CURLOPT_TIMEOUT, empty($conf->global->MAIN_USE_RESPONSE_TIMEOUT)?30:$conf->global->MAIN_USE_RESPONSE_TIMEOUT);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);		// We want response
    if ($postorget == 'POST')
    {
    	curl_setopt($ch, CURLOPT_POST, 1);	// POST
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $param);	// Setting param x=a&y=z as POST fields
    }
    else if ($postorget == 'PUT')
    {
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); // HTTP request is 'PUT'
    	if (! is_array($param)) parse_str($param, $array_param);
    	else 
    	{
    	    dol_syslog("parameter param must be a string", LOG_WARNING);
    	    $array_param=$param;
    	}
    	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($array_param));	// Setting param x=a&y=z as PUT fields	
    }
    else if ($postorget == 'PUTALREADYFORMATED')
    {
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT'); // HTTP request is 'PUT'
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $param);	// param = content of post, like a xml string
    }
    else if ($postorget == 'HEAD')
    {
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD'); // HTTP request is 'HEAD'
    	curl_setopt($ch, CURLOPT_NOBODY, true);
    }
    else if ($postorget == 'DELETE')
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
    dol_syslog("getURLContent response=".$response);

    $rep=array();
    if (curl_errno($ch))
    {
        // Ad keys to $rep
        $rep['content']=$response;
        
        // moving to display page to display curl errors
		$rep['curl_error_no']=curl_errno($ch);
        $rep['curl_error_msg']=curl_error($ch);

		dol_syslog("getURLContent response array is ".join(',',$rep));
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

