<?php
/* Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2009 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/core/login/functions_myopenid.php
 *      \ingroup    core
 *      \brief      Authentication functions for OpenId mode
 */


/**
 * Check validity of user/password/entity
 * If test is ko, reason must be filled into $_SESSION["dol_loginmesg"]
 *
 * @param	string	$usertotest		Login
 * @param	string	$passwordtotest	Password
 * @param   int		$entitytotest   Number of instance (always 1 if module multicompany not enabled)
 * @return	string					Login if OK, '' if KO
 */
function check_user_password_myopenid($usertotest,$passwordtotest,$entitytotest)
{
    global $_POST,$db,$conf,$langs;

    dol_syslog("functions_dolibarr::check_user_password_myopenid usertotest=".$usertotest);

    $login='';

    // Get identity from user and redirect browser to OpenID Server
    if (isset($_POST['username']))
    {
        $openid = new SimpleOpenID();
        $openid->SetIdentity($_POST['username']);
        $protocol = ($conf->file->main_force_https ? 'https://' : 'http://');
        $openid->SetTrustRoot($protocol . $_SERVER["HTTP_HOST"]);
        $openid->SetRequiredFields(array('email','fullname'));
        $_SESSION['dol_entity'] = $_POST["entity"];
        //$openid->SetOptionalFields(array('dob','gender','postcode','country','language','timezone'));
        if ($openid->GetOpenIDServer())
        {
            $openid->SetApprovedURL($protocol . $_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_NAME"]);      // Send Response from OpenID server to this script
            $openid->Redirect();     // This will redirect user to OpenID Server
        }
        else
        {
            $error = $openid->GetError();
            return false;
        }
        return false;
    }
    // Perform HTTP Request to OpenID server to validate key
    elseif($_GET['openid_mode'] == 'id_res')
    {
        $openid = new SimpleOpenID();
        $openid->SetIdentity($_GET['openid_identity']);
        $openid_validation_result = $openid->ValidateWithServer();
        if ($openid_validation_result == true)
        {
            // OK HERE KEY IS VALID

            $sql ="SELECT login";
            $sql.=" FROM ".MAIN_DB_PREFIX."user";
            $sql.=" WHERE openid = '".$db->escape($_GET['openid_identity'])."'";
            $sql.=" AND entity IN (0," . ($_SESSION["dol_entity"] ? $_SESSION["dol_entity"] : 1) . ")";

            dol_syslog("functions_dolibarr::check_user_password_myopenid sql=".$sql);
            $resql=$db->query($sql);
            if ($resql)
            {
                $obj=$db->fetch_object($resql);
                if ($obj)
                {
                    $login=$obj->login;
                }
            }
        }
        else if($openid->IsError() == true)
        {
            // ON THE WAY, WE GOT SOME ERROR
            $error = $openid->GetError();
            return false;
        }
        else
        {
            // Signature Verification Failed
            //echo "INVALID AUTHORIZATION";
            return false;
        }
    }
    else if ($_GET['openid_mode'] == 'cancel')
    {
        // User Canceled your Request
        //echo "USER CANCELED REQUEST";
        return false;
    }

    return $login;
}



/**
 * 	Class to manage OpenID
 */
class SimpleOpenID
{
    var $openid_url_identity;
    var $URLs = array();
    var $error = array();
    var $fields = array(
		'required'	 => array(),
		'optional'	 => array(),
    );

    /**
     * Constructor
     */
    function SimpleOpenID()
    {
        if (!function_exists('curl_exec'))
        {
            die('Error: Class SimpleOpenID requires curl extension to work');
        }
    }

    function SetOpenIDServer($a)
    {
        $this->URLs['openid_server'] = $a;
    }

    function SetTrustRoot($a)
    {
        $this->URLs['trust_root'] = $a;
    }

    function SetCancelURL($a)
    {
        $this->URLs['cancel'] = $a;
    }

    function SetApprovedURL($a)
    {
        $this->URLs['approved'] = $a;
    }

    function SetRequiredFields($a)
    {
        if (is_array($a)){
            $this->fields['required'] = $a;
        }else{
            $this->fields['required'][] = $a;
        }
    }

    function SetOptionalFields($a)
    {
        if (is_array($a)){
            $this->fields['optional'] = $a;
        }else{
            $this->fields['optional'][] = $a;
        }
    }

    function SetIdentity($a)
    { 	// Set Identity URL
        if ((stripos($a, 'http://') === false)
        && (stripos($a, 'https://') === false)){
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

    function GetIdentity()
    { 	// Get Identity
        return $this->openid_url_identity;
    }

    function GetError()
    {
        $e = $this->error;
        return array('code'=>$e[0],'description'=>$e[1]);
    }

    function ErrorStore($code, $desc = null)
    {
        $errs['OPENID_NOSERVERSFOUND'] = 'Cannot find OpenID Server TAG on Identity page.';
        if ($desc == null){
            $desc = $errs[$code];
        }
        $this->error = array($code,$desc);
    }

    function IsError()
    {
        if (count($this->error) > 0){
            return true;
        }else{
            return false;
        }
    }

    function splitResponse($response)
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

    function OpenID_Standarize($openid_identity = null)
    {
        if ($openid_identity === null)
        $openid_identity = $this->openid_url_identity;

        $u = parse_url(strtolower(trim($openid_identity)));

        if (!isset($u['path']) || ($u['path'] == '/')) {
            $u['path'] = '';
        }
        if(substr($u['path'],-1,1) == '/'){
            $u['path'] = substr($u['path'], 0, strlen($u['path'])-1);
        }
        if (isset($u['query'])){ // If there is a query string, then use identity as is
            return $u['host'] . $u['path'] . '?' . $u['query'];
        }else{
            return $u['host'] . $u['path'];
        }
    }

    function array2url($arr)
    { // converts associated array to URL Query String
        if (!is_array($arr)){
            return false;
        }
        $query = '';
        foreach($arr as $key => $value){
            $query .= $key . "=" . $value . "&";
        }
        return $query;
    }

    function FSOCK_Request($url, $method="GET", $params = "")
    {
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

    function CURL_Request($url, $method="GET", $params = "")
    { // Remember, SSL MUST BE SUPPORTED
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

    function HTML2OpenIDServer($content)
    {
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

    function GetOpenIDServer()
    {
        $response = $this->CURL_Request($this->openid_url_identity);
        list($servers, $delegates) = $this->HTML2OpenIDServer($response);
        if (count($servers) == 0){
            $this->ErrorStore('OPENID_NOSERVERSFOUND');
            return false;
        }
        if (isset($delegates[0])
        && ($delegates[0] != "")){
            $this->SetIdentity($delegates[0]);
        }
        $this->SetOpenIDServer($servers[0]);
        return $servers[0];
    }

    function GetRedirectURL()
    {
        $params = array();
        $params['openid.return_to'] = urlencode($this->URLs['approved']);
        $params['openid.mode'] = 'checkid_setup';
        $params['openid.identity'] = urlencode($this->openid_url_identity);
        $params['openid.trust_root'] = urlencode($this->URLs['trust_root']);

        if (isset($this->fields['required'])
        && (count($this->fields['required']) > 0)) {
            $params['openid.sreg.required'] = implode(',',$this->fields['required']);
        }
        if (isset($this->fields['optional'])
        && (count($this->fields['optional']) > 0)) {
            $params['openid.sreg.optional'] = implode(',',$this->fields['optional']);
        }
        return $this->URLs['openid_server'] . "?". $this->array2url($params);
    }

    function Redirect()
    {
        $redirect_to = $this->GetRedirectURL();
        if (headers_sent()){ // Use JavaScript to redirect if content has been previously sent (not recommended, but safe)
            echo '<script language="JavaScript" type="text/javascript">window.location=\'';
            echo $redirect_to;
            echo '\';</script>';
        }else{	// Default Header Redirect
            header('Location: ' . $redirect_to);
        }
    }

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
            $c = $_GET['openid_' . $arr_signed[$i]];
            // if ($c != ""){
            $params['openid.' . $s] = urlencode($c);
            // }
        }
        $params['openid.mode'] = "check_authentication";

        $openid_server = $this->GetOpenIDServer();
        if ($openid_server == false){
            return false;
        }
        $response = $this->CURL_Request($openid_server,'POST',$params);
        $data = $this->splitResponse($response);
        if ($data['is_valid'] == "true") {
            return true;
        }else{
            return false;
        }
    }
}

?>