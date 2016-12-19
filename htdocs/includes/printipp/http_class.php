<?php
/* @(#) $Header: /sources/phpprintipp/phpprintipp/php_classes/http_class.php,v 1.7 2010/08/22 15:45:17 harding Exp $ */
/* vim: set expandtab tabstop=2 shiftwidth=2 foldmethod=marker: */
/* ====================================================================
 * GNU Lesser General Public License
 * Version 2.1, February 1999
 * 
 * Class http_class - Basic http client with "Basic" and Digest/MD5
 * authorization mechanism.
 * handle ipv4/v6 addresses, Unix sockets, http and https
 * have file streaming capability, to cope with php "memory_limit"
 *
 *   Copyright (C) 2006,2007,2008  Thomas HARDING
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * $Id: http_class.php,v 1.7 2010/08/22 15:45:17 harding Exp $
 */
/**
 *  This class is intended to implement a subset of Hyper Text Transfer Protocol
 *  (HTTP/1.1) on client side  (currently: POST operation), with file streaming
 *  capability.
 *  
 *  It can perform Basic and Digest authentication.
 *  
 *  References needed to debug / add functionnalities:
 *  - RFC 2616
 *  - RFC 2617
 *  
 *
 * Class and Function List:
 * Function list:
 * - __construct()
 * - getErrorFormatted()
 * - getErrno()
 * - __construct()
 * - GetRequestArguments()
 * - Open()
 * - SendRequest()
 * - ReadReplyHeaders()
 * - ReadReplyBody()
 * - Close()
 * - _StreamRequest()
 * - _ReadReply()
 * - _ReadStream()
 * - _BuildDigest()
 * Classes list:
 * - httpException extends Exception
 * - http_class
 */
/***********************
 *
 * httpException class
 *
 ************************/
class httpException extends Exception
{
    protected $errno;

    public function __construct ($msg, $errno = null)
    {
        parent::__construct ($msg);
        $this->errno = $errno;
    }

    public function getErrorFormatted ()
    {
        return sprintf ("[http_class]: %s -- "._(" file %s, line %s"),
            $this->getMessage (), $this->getFile (), $this->getLine ());
    }

    public function getErrno ()
    {
        return $this->errno;
    }
}

function error2string($value)
{
    $level_names = array(
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE'
    );
    if(defined('E_STRICT')) {
        $level_names[E_STRICT]='E_STRICT';
    }
    $levels=array();
    if(($value&E_ALL)==E_ALL)
    {
        $levels[]='E_ALL';
        $value&=~E_ALL;
    }
    foreach($level_names as $level=>$name)
    {
        if(($value&$level)==$level)
        {
            $levels[]=$name;
        }
    }
    return implode(' | ',$levels);
}

/***********************
 *
 * class http_class
 *
 ************************/
class http_class
{
    // variables declaration
    public $debug;
    public $html_debug;
    public $timeout = 30;  // time waiting for connection, seconds
    public $data_timeout = 30; // time waiting for data, milliseconds
    public $data_chunk_timeout = 1; // time waiting between data chunks, millisecond
    public $force_multipart_form_post;
    public $username;
    public $password;
    public $request_headers = array ();
    public $request_body = "Not a useful information";
    public $status;
    public $window_size = 1024; // chunk size of data
    public $with_exceptions = 0; // compatibility mode for old scripts
    public $port;
    public $host;
    private $default_port = 631;
    private $headers;
    private $reply_headers = array ();
    private $reply_body = array ();
    private $connection;
    private $arguments;
    private $bodystream = array ();
    private $last_limit;
    private $connected;
    private $nc = 1;
    private $user_agent = "PRINTIPP/0.81+CVS";
    private $readed_bytes = 0;

    public function __construct ()
    {
        true;
    }

    /*********************
     *
     * Public functions
     *
     **********************/

    public function GetRequestArguments ($url, &$arguments)
    {
        $this->arguments = array ();
        $this->arguments["URL"] = $arguments["URL"] = $url;
        $this->arguments["RequestMethod"] = $arguments["RequestMethod"] = "POST";
        $this->headers["Content-Length"] = 0;
        $this->headers["Content-Type"] = "application/octet-stream";
        $this->headers["Host"] = $this->host;
        $this->headers["User-Agent"] = $this->user_agent;
        //$this->headers["Expect"] = "100-continue";
    }

    public function Open ($arguments)
    {
        $this->connected = false;
        $url = $arguments["URL"];
        $port = $this->default_port;
        #$url = split (':', $url, 2);
        $url = preg_split ('#:#', $url, 2);
        $transport_type = $url[0];
        $unix = false;
        switch ($transport_type)
        {
            case 'http':
                $transport_type = 'tcp://';
                break;

            case 'https':
                $transport_type = 'tls://';
                break;

            case 'unix':
                $transport_type = 'unix://';
                $port = 0;
                $unix = true;
                break;

            default:
                $transport_type = 'tcp://';
                break;
        }
        $url = $url[1];
        if (!$unix)
        {
            #$url = split ("/", preg_replace ("#^/{1,}#", '', $url), 2);
            $url = preg_split ("#/#", preg_replace ("#^/{1,}#", '', $url), 2);
            $url = $url[0];
            $port = $this->port;
            $error = sprintf (_("Cannot resolve url: %s"), $url);
            $ip = gethostbyname ($url);
            $ip = @gethostbyaddr ($ip);
            if (!$ip)
            {
                return $this->_HttpError ($error, E_USER_WARNING);
            }
            if (strstr ($url, ":")) // we got an ipv6 address
            {
                if (!strstr ($url, "[")) // it is not escaped
                {
                    $url = sprintf ("[%s]", $url);
                }
            }
        }
        $this->connection = @fsockopen ($transport_type.$url, $port, $errno, $errstr, $this->timeout);
        $error =
            sprintf (_('Unable to connect to "%s%s port %s": %s'), $transport_type,
                $url, $port, $errstr);
        if (!$this->connection)
        {
            return $this->_HttpError ($error, E_USER_WARNING);
        }
        $this->connected = true;
        return array (true, "success");
    }

    public function SendRequest ($arguments)
    {
        $error =
            sprintf (_('Streaming request failed to %s'), $arguments['RequestURI']);
        $result = self::_StreamRequest ($arguments);
        if (!$result[0])
        {
            return $this->_HttpError ($error." ".$result[1], E_USER_WARNING);
        }
        self::_ReadReply ();
        if (!preg_match ('#http/1.1 401 unauthorized#', $this->status))
        {
            return array (true, "success");
        }
        $headers = array_keys ($this->reply_headers);
        $error = _("need authentication but no mechanism provided");
        if (!in_array ("www-authenticate", $headers))
        {
            return $this->_HttpError ($error, E_USER_WARNING);
        }
        #$authtype = split (' ', $this->reply_headers["www-authenticate"]);
        $authtype = preg_split ('# #', $this->reply_headers["www-authenticate"]);
        $authtype = strtolower ($authtype[0]);
        switch ($authtype)
        {
            case 'basic':
                $pass = base64_encode ($this->user.":".$this->password);
                $arguments["Headers"]["Authorization"] = "Basic ".$pass;
                break;

            case 'digest':
                $arguments["Headers"]["Authorization"] = self::_BuildDigest ();
                break;

            default:
                $error =
                    sprintf (_("need '%s' authentication mechanism, but have not"),
                        $authtype[0]);
                return $this->_HttpError ($error, E_USER_WARNING);
                break;
        }
        self::Close ();
        self::Open ($arguments);

        $error = sprintf(_('Streaming request failed to %s after a try to authenticate'), $arguments['RequestURI']);
        $result = self::_StreamRequest ($arguments);
        if (!$result[0])
        {
            return $this->_HttpError ($error.": ".$result[1], E_USER_WARNING);
        }
        self::_ReadReply ();
        return array (true, "success");
    }

    public function ReadReplyHeaders (&$headers)
    {
        $headers = $this->reply_headers;
    }

    public function ReadReplyBody (&$body, $chunk_size)
    {
        $body = substr ($this->reply_body, $this->last_limit, $chunk_size);
        $this->last_limit += $chunk_size;
    }

    public function Close ()
    {
        if (!$this->connected)
        {
            return;
        }
        fclose ($this->connection);
    }

    /*********************
     *
     *  Private functions
     *
     *********************/

    private function _HttpError ($msg, $level, $errno = null)
    {
        $trace = '';
        $backtrace = debug_backtrace();
        foreach ($backtrace as $trace)
        {
            $trace .= sprintf ("in [file: '%s'][function: '%s'][line: %s];\n", $trace['file'], $trace['function'],$trace['line']);
        }
        $msg = sprintf ( '%s\n%s: [errno: %s]: %s',
            $trace, error2string ($level), $errno, $msg);
        if ($this->with_exceptions)
        {
            throw new httpException ($msg, $errno);
        }
        else
        {
            trigger_error ($msg, $level);
            return array (false, $msg);
        }
    }

    private function _streamString ($string)
    {
        $success = fwrite ($this->connection, $string);
        if (!$success)
        {
            return false;
        }
        return true;
    }

    private function _StreamRequest ($arguments)
    {
        $this->status = false;
        $this->reply_headers = array ();
        $this->reply_body = "";
        if (!$this->connected)
        {
            return $this->_HttpError (_("not connected"), E_USER_WARNING);
        }
        $this->arguments = $arguments;
        $content_length = 0;
        foreach ($this->arguments["BodyStream"] as $argument)
        {
            list ($type, $value) = each ($argument);
            reset ($argument);
            if ($type == "Data")
            {
                $length = strlen ($value);
            }
            elseif ($type == "File")
            {
                if (is_readable ($value))
                {
                    $length = filesize ($value);
                }
                else
                {
                    $length = 0;
                    return $this->_HttpError (sprintf (_("%s: file is not readable"), $value), E_USER_WARNING);
                }
            }
            else
            {
                $length = 0;
                return $this->_HttpError (sprintf(_("%s: not a valid argument for content"), $type), E_USER_WARNING);
            }
            $content_length += $length;
        }
        $this->request_body = sprintf (_("%s Bytes"), $content_length);
        $this->headers["Content-Length"] = $content_length;
        $this->arguments["Headers"] = array_merge ($this->headers, $this->arguments["Headers"]);
        if ($this->arguments["RequestMethod"] != "POST")
        {
            return $this->_HttpError (sprintf(_("%s: method not implemented"), $arguments["RequestMethod"]), E_USER_WARNING);
        }
        $string = sprintf ("POST %s HTTP/1.1\r\n", $this->arguments["RequestURI"]);
        $this->request_headers[$string] = '';
        if (!$this->_streamString ($string))
        {
            return $this->_HttpError (_("Error while puts POST operation"), E_USER_WARNING);
        }
        foreach ($this->arguments["Headers"] as $header => $value)
        {
            $string = sprintf ("%s: %s\r\n", $header, $value);
            $this->request_headers[$header] = $value;
            if (!$this->_streamString ($string))
            {
                return $this->_HttpError (_("Error while puts HTTP headers"), E_USER_WARNING);
            }
        }
        $string = "\r\n";
        if (!$this->_streamString ($string))
        {
            return $this->_HttpError (_("Error while ends HTTP headers"), E_USER_WARNING);
        }
        foreach ($this->arguments["BodyStream"] as $argument)
        {
            list ($type, $value) = each ($argument);
            reset ($argument);
            if ($type == "Data")
            {
                $streamed_length = 0;
                while ($streamed_length < strlen ($value))
                {
                    $string = substr ($value, $streamed_length, $this->window_size);
                    if (!$this->_streamString ($string))
                    {
                        return $this->_HttpError (_("error while sending body data"), E_USER_WARNING);
                    }
                    $streamed_length += $this->window_size;
                }
            }
            elseif ($type == "File")
            {
                if (is_readable ($value))
                {
                    $file = fopen ($value, 'rb');
                    while (!feof ($file))
                    {
                        if (gettype ($block = @fread ($file, $this->window_size)) != "string")
                        {
                            return $this->_HttpError (_("cannot read file to upload"), E_USER_WARNING);
                        }
                        if (!$this->_streamString ($block))
                        {
                            return $this->_HttpError (_("error while sending body data"), E_USER_WARNING);
                        }
                    }
                }
            }
        }
        return array (true, "success");
    }

    private function _ReadReply ()
    {
        if (!$this->connected)
        {
            return array (false, _("not connected"));
        }
        $this->reply_headers = array ();
        $this->reply_body = "";
        $headers = array ();
        $body = "";
        while (!feof ($this->connection))
        {
            $line = fgets ($this->connection, 1024);
            if (strlen (trim($line)) == 0)
            {
                break;
            } // \r\n => end of headers
            if (preg_match ('#^[[:space:]]#', $line))
            {
                $headers[-1] .= sprintf(' %s', trim ($line));
                continue;
            }
            $headers[] = trim ($line);
        }
        $this->status = isset ($headers[0]) ? strtolower ($headers[0]) : false;
        foreach ($headers as $header)
        {
            $header = preg_split ("#: #", $header);
            $header[0] = strtolower ($header[0]);
            if ($header[0] !== "www-authenticate")
            {
                $header[1] = isset ($header[1]) ? strtolower ($header[1]) : "";
            }
            if (!isset ($this->reply_headers[$header[0]]))
            {
                $this->reply_headers[$header[0]] = $header[1];
            }
        }
        self::_ReadStream ();
        return true;
    }

    private function _ReadStream ()
    {
        if (! array_key_exists ("content-length", $this->reply_headers))
        {
            stream_set_blocking($this->connection, 0);
            $this->reply_body = stream_get_contents($this->connection);
            return true;
        }
        stream_set_blocking($this->connection, 1);
        $content_length = $this->reply_headers["content-length"];
        $this->reply_body = stream_get_contents($this->connection,$content_length);
        return true;
    }

    private function _BuildDigest ()
    {
        $auth = $this->reply_headers["www-authenticate"];
        #list ($head, $auth) = split (" ", $auth, 2);
        list ($head, $auth) = preg_split ("# #", $auth, 2);
        #$auth = split (", ", $auth);
        $auth = preg_split ("#, #", $auth);
        foreach ($auth as $sheme)
        {
            #list ($sheme, $value) = split ('=', $sheme);
            list ($sheme, $value) = preg_split ('#=#', $sheme);
            $fields[$sheme] = trim (trim ($value), '"');
        }
        $nc = sprintf ('%x', $this->nc);
        $prepend = "";
        while ((strlen ($nc) + strlen ($prepend)) < 8)
            $prependi .= "0";
        $nc = $prepend.$nc;
        $cnonce = "printipp";
        $username = $this->user;
        $password = $this->password;
        $A1 = $username.":".$fields["realm"].":".$password;
        if (array_key_exists ("algorithm", $fields))
        {
            $algorithm = strtolower ($fields["algorithm"]);
            switch ($algorithm)
            {
                case "md5":
                    break;

                case "md5-sess":
                    $A1 =
                    $username.":".$fields["realm"].":".$password.":".
                    $fields['nonce'].":".$cnonce;
                    break;

                default:
                    return $this->_HttpError(
                        sprintf (_("digest Authorization: algorithm '%s' not implemented"),
                            $algorithm),
                        E_USER_WARNING);
                return false;
                break;
            }
        }
        $A2 = "POST:".$this->arguments["RequestURI"];
        if (array_key_exists ("qop", $fields))
        {
            $qop = strtolower ($fields["qop"]);
            #$qop = split (" ", $qop);
            $qop = preg_split ("# #", $qop);
            if (in_array ("auth", $qop))
            {
                $qop = "auth";
            }
            else
            {
                self::_HttpError(
                    sprintf (_("digest Authorization: algorithm '%s' not implemented"),
                        $qop),
                    E_USER_WARNING);
                return false;
            }
        }
        $response = md5 (md5 ($A1).":".$fields["nonce"].":".md5 ($A2));
        if (isset ($qop) && ($qop == "auth"))
        {
            $response =
                md5 (md5 ($A1).":".$fields["nonce"].":".$nc.":".$cnonce.":".$qop.
                    ":".$A2);
        }
        $auth_scheme =
            sprintf
            ('Digest username="%s", realm="%s", nonce="%s", uri="%s", response="%s"',
                $username, $fields["realm"], $fields['nonce'],
                $this->arguments["RequestURI"], $response);
        if (isset ($algorithm))
        {
            $auth_scheme .= sprintf (', algorithm="%s"', $algorithm);
        }
        if (isset ($qop))
        {
            $auth_scheme .= sprintf (', cnonce="%s"', $cnonce);
        }
        if (array_key_exists ("opaque", $fields))
        {
            $auth_scheme .= sprintf (', opaque="%s"', $fields['opaque']);
        }
        if (isset ($qop))
        {
            $auth_scheme .= sprintf (', qop="%s"', $qop);
        }
        $auth_scheme .= sprintf (', nc=%s', $nc);
        $this->nc++;
        return $auth_scheme;
    }
}
