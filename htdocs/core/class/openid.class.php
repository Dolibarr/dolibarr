<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *      \file       htdocs/core/class/openid.class.php
 *      \ingroup    core
 *      \brief      Class to manage authentication with OpenId
 */

/**
 * 	Class to manage OpenID
 */
class SimpleOpenID
{
<<<<<<< HEAD
    var $openid_url_identity;
    var $URLs = array();
    var $error = array();
    var $fields = array(
		'required'	 => array(),
		'optional'	 => array(),
=======
    public $openid_url_identity;
    public $URLs = array();
    public $error = array();
    public $fields = array(
        'required' => array(),
        'optional' => array(),
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    );

    /**
     * Constructor
     */
<<<<<<< HEAD
    function __construct()
=======
    public function __construct()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        if (!function_exists('curl_exec'))
        {
            die('Error: Class SimpleOpenID requires curl extension to work');
        }
    }

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * SetOpenIDServer
     *
     * @param	string	$a		Server
     * @return	void
     */
<<<<<<< HEAD
    function SetOpenIDServer($a)
    {
        $this->URLs['openid_server'] = $a;
    }

=======
    public function SetOpenIDServer($a)
    {
        // phpcs:enable
        $this->URLs['openid_server'] = $a;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * SetOpenIDServer
     *
     * @param	string	$a		Server
     * @return	void
     */
<<<<<<< HEAD
    function SetTrustRoot($a)
    {
        $this->URLs['trust_root'] = $a;
    }

=======
    public function SetTrustRoot($a)
    {
        // phpcs:enable
        $this->URLs['trust_root'] = $a;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * SetOpenIDServer
     *
     * @param	string	$a		Server
     * @return	void
     */
<<<<<<< HEAD
    function SetCancelURL($a)
    {
        $this->URLs['cancel'] = $a;
    }

=======
    public function SetCancelURL($a)
    {
        // phpcs:enable
        $this->URLs['cancel'] = $a;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * SetApprovedURL
     *
     * @param	string	$a		Server
     * @return	void
     */
<<<<<<< HEAD
    function SetApprovedURL($a)
    {
        $this->URLs['approved'] = $a;
    }

=======
    public function SetApprovedURL($a)
    {
        // phpcs:enable
        $this->URLs['approved'] = $a;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * SetRequiredFields
     *
     * @param	string	$a		Server
     * @return	void
     */
<<<<<<< HEAD
    function SetRequiredFields($a)
    {
        if (is_array($a)){
            $this->fields['required'] = $a;
        }else{
=======
    public function SetRequiredFields($a)
    {
        // phpcs:enable
        if (is_array($a)) {
            $this->fields['required'] = $a;
        } else {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $this->fields['required'][] = $a;
        }
    }

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * SetOptionalFields
     *
     * @param	string	$a		Server
     * @return	void
     */
<<<<<<< HEAD
    function SetOptionalFields($a)
    {
        if (is_array($a)){
            $this->fields['optional'] = $a;
        }else{
=======
    public function SetOptionalFields($a)
    {
        // phpcs:enable
        if (is_array($a)) {
            $this->fields['optional'] = $a;
        } else {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $this->fields['optional'][] = $a;
        }
    }

<<<<<<< HEAD
    /**
     * SetIdentity
     *
     * @param	string	$a		Server
     * @return	void
     */
    function SetIdentity($a)
    { 	// Set Identity URL
        if ((stripos($a, 'http://') === false)
        && (stripos($a, 'https://') === false)){
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     * SetIdentity
     *
     * @param	string  $a		Server
     * @return	void
     */
    public function SetIdentity($a)
    {
        // phpcs:enable
        // Set Identity URL
        if ((stripos($a, 'http://') === false)
        && (stripos($a, 'https://') === false)) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $a = 'http://'.$a;
        }
        /*
         $u = parse_url(trim($a));
         if (!isset($u['path'])){
         $u['path'] = '/';
         }else if(substr($u['path'],-1,1) == '/'){
         $u['path'] = substr($u['path'], 0, strlen($u['path'])-1);
         }
         if (isset($u['query'])){ // If there is a query string, then use identity as is
         $identity = $a;
         }else{
         $identity = $u['scheme'] . '://' . $u['host'] . $u['path'];
         }
         //*/
        $this->openid_url_identity = $a;
    }

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * GetIdentity
     *
     * @return	string
     */
<<<<<<< HEAD
    function GetIdentity()
    { 	// Get Identity
        return $this->openid_url_identity;
    }

=======
    public function GetIdentity()
    {
        // phpcs:enable
        // Get Identity
        return $this->openid_url_identity;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * SetOpenIDServer
     *
     * @return	void
     */
<<<<<<< HEAD
    function GetError()
    {
=======
    public function GetError()
    {
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $e = $this->error;
        return array('code'=>$e[0],'description'=>$e[1]);
    }

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * ErrorStore
     *
     * @param	string	$code		Code
     * @param	string	$desc		Description
     * @return	void
     */
<<<<<<< HEAD
    function ErrorStore($code, $desc = null)
    {
=======
    public function ErrorStore($code, $desc = null)
    {
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $errs['OPENID_NOSERVERSFOUND'] = 'Cannot find OpenID Server TAG on Identity page.';
        if ($desc == null){
            $desc = $errs[$code];
        }
        $this->error = array($code,$desc);
    }

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * IsError
     *
     * @return	boolean
     */
<<<<<<< HEAD
    function IsError()
    {
=======
    public function IsError()
    {
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        if (count($this->error) > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
     * splitResponse
     *
     * @param	string	$response		Server
     * @return	void
     */
<<<<<<< HEAD
    function splitResponse($response)
=======
    public function splitResponse($response)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        $r = array();
        $response = explode("\n", $response);
        foreach($response as $line) {
            $line = trim($line);
            if ($line != "") {
                list($key, $value) = explode(":", $line, 2);
                $r[trim($key)] = trim($value);
            }
        }
        return $r;
    }

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * OpenID_Standarize
     *
     * @param	string	$openid_identity		Server
     * @return	string
     */
<<<<<<< HEAD
    function OpenID_Standarize($openid_identity = null)
    {
=======
    public function OpenID_Standarize($openid_identity = null)
    {
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        if ($openid_identity === null)
        $openid_identity = $this->openid_url_identity;

        $u = parse_url(strtolower(trim($openid_identity)));

        if (!isset($u['path']) || ($u['path'] == '/')) {
            $u['path'] = '';
        }
<<<<<<< HEAD
        if(substr($u['path'],-1,1) == '/'){
=======
        if(substr($u['path'], -1, 1) == '/'){
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $u['path'] = substr($u['path'], 0, strlen($u['path'])-1);
        }
        if (isset($u['query'])){ // If there is a query string, then use identity as is
            return $u['host'] . $u['path'] . '?' . $u['query'];
        }else{
            return $u['host'] . $u['path'];
        }
    }

    /**
     * array2url
     *
     * @param 	array	$arr		An array
     * @return false|string		false if KO, string of url if OK
     */
<<<<<<< HEAD
    function array2url($arr)
    { // converts associated array to URL Query String
=======
    public function array2url($arr)
    {
        // converts associated array to URL Query String
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        if (!is_array($arr)){
            return false;
        }
        $query = '';
        foreach($arr as $key => $value){
            $query .= $key . "=" . $value . "&";
        }
        return $query;
    }

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * FSOCK_Request
     *
     * @param string 	$url		URL
     * @param string	$method		Method
     * @param string	$params		Params
     * @return boolean|unknown
     */
<<<<<<< HEAD
    function FSOCK_Request($url, $method="GET", $params = "")
    {
=======
    public function FSOCK_Request($url, $method = "GET", $params = "")
    {
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $fp = fsockopen("ssl://www.myopenid.com", 443, $errno, $errstr, 3); // Connection timeout is 3 seconds
        if (!$fp) {
            $this->ErrorStore('OPENID_SOCKETERROR', $errstr);
            return false;
        } else {
            $request = $method . " /server HTTP/1.0\r\n";
            $request .= "User-Agent: Dolibarr\r\n";
            $request .= "Connection: close\r\n\r\n";
            fwrite($fp, $request);
            stream_set_timeout($fp, 4); // Connection response timeout is 4 seconds
            $res = fread($fp, 2000);
            $info = stream_get_meta_data($fp);
            fclose($fp);

            if ($info['timed_out']) {
                $this->ErrorStore('OPENID_SOCKETTIMEOUT');
            } else {
                return $res;
            }
        }
    }

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * CURL_Request
     *
     * @param 	string	$url		URL
     * @param 	string	$method		Method
     * @param 	string	$params		Params
     * @return string
     */
<<<<<<< HEAD
    function CURL_Request($url, $method="GET", $params = "")
    { // Remember, SSL MUST BE SUPPORTED
=======
    public function CURL_Request($url, $method = "GET", $params = "")
    {
        // phpcs:enable
        // Remember, SSL MUST BE SUPPORTED
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        if (is_array($params)) $params = $this->array2url($params);

        $curl = curl_init($url . ($method == "GET" && $params != "" ? "?" . $params : ""));
        @curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HTTPGET, ($method == "GET"));
        curl_setopt($curl, CURLOPT_POST, ($method == "POST"));
        if ($method == "POST") curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($curl);

        if (curl_errno($curl) == 0){
            $response;
        }else{
            $this->ErrorStore('OPENID_CURL', curl_error($curl));
        }
        return $response;
    }

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * HTML2OpenIDServer
     *
     * @param string	$content	Content
     * @return array				Array of servers
     */
<<<<<<< HEAD
    function HTML2OpenIDServer($content)
    {
=======
    public function HTML2OpenIDServer($content)
    {
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $get = array();

        // Get details of their OpenID server and (optional) delegate
        preg_match_all('/<link[^>]*rel=[\'"]openid.server[\'"][^>]*href=[\'"]([^\'"]+)[\'"][^>]*\/?>/i', $content, $matches1);
        preg_match_all('/<link[^>]*href=\'"([^\'"]+)[\'"][^>]*rel=[\'"]openid.server[\'"][^>]*\/?>/i', $content, $matches2);
        $servers = array_merge($matches1[1], $matches2[1]);

        preg_match_all('/<link[^>]*rel=[\'"]openid.delegate[\'"][^>]*href=[\'"]([^\'"]+)[\'"][^>]*\/?>/i', $content, $matches1);

        preg_match_all('/<link[^>]*href=[\'"]([^\'"]+)[\'"][^>]*rel=[\'"]openid.delegate[\'"][^>]*\/?>/i', $content, $matches2);

        $delegates = array_merge($matches1[1], $matches2[1]);

        $ret = array($servers, $delegates);
        return $ret;
    }


<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * Get openid server
     *
     * @param	string	$url	Url to found endpoint
     * @return 	string			Endpoint
     */
<<<<<<< HEAD
    function GetOpenIDServer($url='')
    {
    	global $conf;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
		if (empty($url)) $url=$conf->global->MAIN_AUTHENTICATION_OPENID_URL;
=======
    public function GetOpenIDServer($url = '')
    {
        // phpcs:enable
        global $conf;

        include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
        if (empty($url)) $url=$conf->global->MAIN_AUTHENTICATION_OPENID_URL;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

        $response = getURLContent($url);

        list($servers, $delegates) = $this->HTML2OpenIDServer($response);
<<<<<<< HEAD
        if (count($servers) == 0){
=======
        if (count($servers) == 0) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $this->ErrorStore('OPENID_NOSERVERSFOUND');
            return false;
        }
        if (isset($delegates[0])
<<<<<<< HEAD
        && ($delegates[0] != "")){
=======
        && ($delegates[0] != "")) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $this->SetIdentity($delegates[0]);
        }
        $this->SetOpenIDServer($servers[0]);
        return $servers[0];
    }

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * GetRedirectURL
     *
     * @return	string
     */
<<<<<<< HEAD
    function GetRedirectURL()
    {
=======
    public function GetRedirectURL()
    {
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $params = array();
        $params['openid.return_to'] = urlencode($this->URLs['approved']);
        $params['openid.mode'] = 'checkid_setup';
        $params['openid.identity'] = urlencode($this->openid_url_identity);
        $params['openid.trust_root'] = urlencode($this->URLs['trust_root']);

        if (isset($this->fields['required'])
        && (count($this->fields['required']) > 0)) {
<<<<<<< HEAD
            $params['openid.sreg.required'] = implode(',',$this->fields['required']);
        }
        if (isset($this->fields['optional'])
        && (count($this->fields['optional']) > 0)) {
            $params['openid.sreg.optional'] = implode(',',$this->fields['optional']);
=======
            $params['openid.sreg.required'] = implode(',', $this->fields['required']);
        }
        if (isset($this->fields['optional'])
        && (count($this->fields['optional']) > 0)) {
            $params['openid.sreg.optional'] = implode(',', $this->fields['optional']);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }
        return $this->URLs['openid_server'] . "?". $this->array2url($params);
    }

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * Redirect
     *
     * @return	void
     */
<<<<<<< HEAD
    function Redirect()
    {
=======
    public function Redirect()
    {
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $redirect_to = $this->GetRedirectURL();
        if (headers_sent())
        { // Use JavaScript to redirect if content has been previously sent (not recommended, but safe)
            echo '<script language="JavaScript" type="text/javascript">window.location=\'';
            echo $redirect_to;
            echo '\';</script>';
        }
        else
        {	// Default Header Redirect
            header('Location: ' . $redirect_to);
        }
    }

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * ValidateWithServer
     *
     * @return	boolean
     */
<<<<<<< HEAD
    function ValidateWithServer()
    {
        $params = array(
			'openid.assoc_handle' => urlencode($_GET['openid_assoc_handle']),
			'openid.signed' => urlencode($_GET['openid_signed']),
			'openid.sig' => urlencode($_GET['openid_sig'])
        );
        // Send only required parameters to confirm validity
        $arr_signed = explode(",",str_replace('sreg.','sreg_',$_GET['openid_signed']));
        $num = count($arr_signed);
        for ($i = 0; $i < $num; $i++)
        {
            $s = str_replace('sreg_','sreg.', $arr_signed[$i]);
=======
    public function ValidateWithServer()
    {
        // phpcs:enable
        $params = array(
            'openid.assoc_handle' => urlencode($_GET['openid_assoc_handle']),
            'openid.signed' => urlencode($_GET['openid_signed']),
            'openid.sig' => urlencode($_GET['openid_sig'])
        );
        // Send only required parameters to confirm validity
        $arr_signed = explode(",", str_replace('sreg.', 'sreg_', $_GET['openid_signed']));
        $num = count($arr_signed);
        for ($i = 0; $i < $num; $i++)
        {
            $s = str_replace('sreg_', 'sreg.', $arr_signed[$i]);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $c = $_GET['openid_' . $arr_signed[$i]];
            // if ($c != ""){
            $params['openid.' . $s] = urlencode($c);
            // }
        }
        $params['openid.mode'] = "check_authentication";

        $openid_server = $this->GetOpenIDServer();
        if ($openid_server == false)
        {
            return false;
        }
<<<<<<< HEAD
        $response = $this->CURL_Request($openid_server,'POST',$params);
=======
        $response = $this->CURL_Request($openid_server, 'POST', $params);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $data = $this->splitResponse($response);
        if ($data['is_valid'] == "true")
        {
            return true;
        }
        else
        {
            return false;
        }
    }




    /**
     * Get XRDS response and set possible servers.
     *
     * @param	string	$url	Url of endpoint to request
     * @return 	string			First endpoint OpenID server found. False if it failed to found.
     */
<<<<<<< HEAD
    function sendDiscoveryRequestToGetXRDS($url='')
    {
    	global $conf;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
		if (empty($url)) $url=$conf->global->MAIN_AUTHENTICATION_OPENID_URL;

		dol_syslog(get_class($this).'::sendDiscoveryRequestToGetXRDS get XRDS');

		$addheaders=array('Accept: application/xrds+xml');
        $response = getURLContent($url, 'GET', '', 1, $addheaders);
		/* response should like this:
		<?xml version="1.0" encoding="UTF-8"?>
		<xrds:XRDS xmlns:xrds="xri://$xrds" xmlns="xri://$xrd*($v*2.0)">
		<XRD>
		<Service priority="0">
		<Type>http://specs.openid.net/auth/2.0/server</Type>
		<Type>http://openid.net/srv/ax/1.0</Type>
		...
		<URI>https://www.google.com/accounts/o8/ud</URI>
		</Service>
		</XRD>
		</xrds:XRDS>
		*/
		$content=$response['content'];

        $server='';
        if (preg_match('/'.preg_quote('<URI>','/').'(.*)'.preg_quote('</URI>','/').'/is', $content, $reg))
        {
        	$server=$reg[1];
=======
    public function sendDiscoveryRequestToGetXRDS($url = '')
    {
        global $conf;

        include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
        if (empty($url)) $url=$conf->global->MAIN_AUTHENTICATION_OPENID_URL;

        dol_syslog(get_class($this).'::sendDiscoveryRequestToGetXRDS get XRDS');

        $addheaders=array('Accept: application/xrds+xml');
        $response = getURLContent($url, 'GET', '', 1, $addheaders);
        /* response should like this:
        <?xml version="1.0" encoding="UTF-8"?>
        <xrds:XRDS xmlns:xrds="xri://$xrds" xmlns="xri://$xrd*($v*2.0)">
        <XRD>
        <Service priority="0">
        <Type>http://specs.openid.net/auth/2.0/server</Type>
        <Type>http://openid.net/srv/ax/1.0</Type>
        ...
        <URI>https://www.google.com/accounts/o8/ud</URI>
        </Service>
        </XRD>
        </xrds:XRDS>
        */
        $content=$response['content'];

        $server='';
        if (preg_match('/'.preg_quote('<URI>', '/').'(.*)'.preg_quote('</URI>', '/').'/is', $content, $reg))
        {
            $server=$reg[1];
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }

        if (empty($server))
        {
            $this->ErrorStore('OPENID_NOSERVERSFOUND');
            return false;
        }
        else
       {
<<<<<<< HEAD
       		dol_syslog(get_class($this).'::sendDiscoveryRequestToGetXRDS found endpoint = '.$server);
        	$this->SetOpenIDServer($server);
        	return $server;
	    }
    }

}

=======
               dol_syslog(get_class($this).'::sendDiscoveryRequestToGetXRDS found endpoint = '.$server);
            $this->SetOpenIDServer($server);
            return $server;
        }
    }
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
