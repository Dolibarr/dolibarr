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
 *	\file			htdocs/core/lib/functions2.lib.php
 *	\brief			A set of functions for Dolibarr
 *					This file contains all rare functions.
 */

/**
 * Function get content from an URL (use proxy if proxy defined)
 *
 * @param	string	$url 			URL to call.
 * @param	string	$postorget		'post' = POST, 'get='GET'
 * @return	array					returns an associtive array containing the response from the server.
 */
function getURLContent($url,$postorget='GET',$param)
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
    curl_setopt($ch, CURLOPT_SSLVERSION, 3); // Force SSLv3

    //turning off the server and peer verification(TrustManager Concept).
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, empty($conf->global->MAIN_USE_CONNECT_TIMEOUT)?5:$conf->global->MAIN_USE_CONNECT_TIMEOUT);
    curl_setopt($ch, CURLOPT_TIMEOUT, empty($conf->global->MAIN_USE_RESPONSE_TIMEOUT)?30:$conf->global->MAIN_USE_RESPONSE_TIMEOUT);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    if ($postorget == 'POST') curl_setopt($ch, CURLOPT_POST, 1);
    else curl_setopt($ch, CURLOPT_POST, 0);

    //if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
    if ($USE_PROXY)
    {
        dol_syslog("getURLContent set proxy to ".$PROXY_HOST. ":" . $PROXY_PORT." - ".$PROXY_USER. ":" . $PROXY_PASS);
        //curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); // Curl 7.10
        curl_setopt($ch, CURLOPT_PROXY, $PROXY_HOST. ":" . $PROXY_PORT);
        if ($PROXY_USER) curl_setopt($ch, CURLOPT_PROXYUSERPWD, $PROXY_USER. ":" . $PROXY_PASS);
    }

    //setting the nvpreq as POST FIELD to curl
    curl_setopt($ch, CURLOPT_POSTFIELDS, $param);

    //getting response from server
    $response = curl_exec($ch);

    $rep=array();
    $rep['content']=$response;
    $rep['curl_error_no']='';
    $rep['curl_error_msg']='';
    
    dol_syslog("getURLContent response=".$response);

    if (curl_errno($ch))
    {
        // moving to display page to display curl errors
		$rep['curl_error_no']=curl_errno($ch);
        $rep['curl_error_msg']=curl_error($ch);

		dol_syslog("getURLContent curl_error array is ".join(',',$rep));
    }
    else
    {
        //closing the curl
        curl_close($ch);
    }

    return $rep;
}

