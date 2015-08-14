<?php

/* @(#) $Header: /sources/phpprintipp/phpprintipp/php_classes/BasicIPP.php,v 1.7 2012/03/01 17:21:04 harding Exp $
 *
 * Class BasicIPP - Send Basic IPP requests, Get and parses IPP Responses.
 *
 *   Copyright (C) 2005-2009  Thomas HARDING
 *   Parts Copyright (C) 2005-2006 Manuel Lemos
 *
 *   This library is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Library General Public
 *   License as published by the Free Software Foundation; either
 *   version 2 of the License, or (at your option) any later version.
 *
 *   This library is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *   Library General Public License for more details.
 *
 *   You should have received a copy of the GNU Library General Public
 *   License along with this library; if not, write to the Free Software
 *   Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *   mailto:thomas.harding@laposte.net
 *   Thomas Harding, 56 rue de la bourie rouge, 45 000 ORLEANS -- FRANCE
 *
 */
/*

   This class is intended to implement Internet Printing Protocol on client side.

   References needed to debug / add functionnalities:
   - RFC 2910
   - RFC 2911
   - RFC 3380
   - RFC 3382
 */

require_once ("http_class.php");

class ippException extends \Exception
{
    protected $errno;

    public function __construct($msg, $errno = null)
    {
        parent::__construct($msg);
        $this->errno = $errno;
    }

    public function getErrorFormatted()
    {
        $return = sprintf("[ipp]: %s -- " . _(" file %s, line %s"),
            $this->getMessage() , $this->getFile() , $this->getLine());
        return $return;
    }

    public function getErrno()
    {
        return $this->errno;
    }
}

class BasicIPP
{
    public $paths = array(
        "root" => "/",
        "admin" => "/admin/",
        "printers" => "/printers/",
        "jobs" => "/jobs/"
    );
    public $http_timeout = 30; // timeout at http connection (seconds) 0 => default => 30.
    public $http_data_timeout = 30; // data reading timeout (milliseconds) 0 => default => 30.
    public $ssl = false;
    public $debug_level = 3; // max 3: almost silent
    public $alert_on_end_tag; // debugging purpose: echo "END tag OK" if (1 and  reads while end tag)
    public $with_exceptions = 1; // compatibility mode for old scripts		// DOL_LDR_CHANGE set this to 1
    public $handle_http_exceptions = 1;

    // readables variables
    public $jobs = array();
    public $jobs_uri = array();
    public $status = array();
    public $response_completed = array();
    public $last_job = "";
    public $attributes; // object you can read: attributes after validateJob()
    public $printer_attributes; // object you can read: printer's attributes after getPrinterAttributes()
    public $job_attributes; // object you can read: last job attributes
    public $jobs_attributes; // object you can read: jobs attributes after getJobs()
    public $available_printers = array();
    public $printer_map = array();
    public $printers_uri = array();
    public $debug = array();
    public $response;
    public $meta;

    // protected variables;
    protected $log_level = 2; // max 3: very verbose
    protected $log_type = 3; // 3: file | 1: e-mail | 0: logger
    protected $log_destination; // e-mail or file
    protected $serveroutput;
    protected $setup;
    protected $stringjob;
    protected $data;
    protected $debug_count = 0;
    protected $username;
    protected $charset;
    protected $password;
    protected $requesring_user;
    protected $client_hostname = "localhost";
    protected $stream;
    protected $host = "localhost";
    protected $port = "631";
    protected $requesting_user = '';
    protected $printer_uri;
    protected $timeout = "20"; //20 secs
    protected $errNo;
    protected $errStr;
    protected $datatype;
    protected $datahead;
    protected $datatail;
    protected $operation_id;
    protected $delay;
    protected $error_generation; //devel feature
    protected $debug_http = 0;
    protected $no_disconnect;
    protected $job_tags;
    protected $operation_tags;
    protected $index;
    protected $collection; //RFC3382
    protected $collection_index; //RFC3382
    protected $collection_key = array(); //RFC3382
    protected $collection_depth = - 1; //RFC3382
    protected $end_collection = false; //RFC3382
    protected $collection_nbr = array(); //RFC3382
    protected $unix = false; // true -> use unix sockets instead of http
    protected $output;

    public function __construct()
    {
        $tz = getenv("date.timezone");
        if (!$tz)
        {
            $tz = @date_default_timezone_get();
        }

        date_default_timezone_set($tz);
        $this->meta = new \stdClass();
        $this->setup = new \stdClass();
        $this->values = new \stdClass();
        $this->serveroutput = new \stdClass();
        $this->error_generation = new \stdClass();
        $this->_parsing = new \stdClass();
        self::_initTags();
    }

    public function setPort($port = '631')
    {
        $this->port = $port;
        self::_putDebug("Port is " . $this->port, 2);
    }

    public function setUnix($socket = '/var/run/cups/cups.sock')
    {
        $this->host = $socket;
        $this->unix = true;
        self::_putDebug("Host is " . $this->host, 2);
    }

    public function setHost($host = 'localhost')
    {
        $this->host = $host;
        $this->unix = false;
        self::_putDebug("Host is " . $this->host, 2);
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    public function setPrinterURI($uri)
    {
        $length = strlen($uri);
        $length = chr($length);
        while (strlen($length) < 2) $length = chr(0x00) . $length;
        $this->meta->printer_uri = chr(0x45) // uri type | value-tag
            . chr(0x00) . chr(0x0B) // name-length
            . "printer-uri" // printer-uri | name
            . $length . $uri;
        $this->printer_uri = $uri;
        self::_putDebug(sprintf(_("Printer URI: %s") , $uri) , 2);
        $this->setup->uri = 1;
    }

    public function setData($data)
    {
        $this->data = $data;
        self::_putDebug("Data set", 2);
    }

    public function setRawText()
    {
        $this->setup->datatype = 'TEXT';
        $this->meta->mime_media_type = "";
        $this->setup->mime_media_type = 1;
        $this->datahead = chr(0x16);
        if (is_readable($this->data))
        {
            //It's a filename.  Open and stream.
            $data = fopen($this->data, "rb");
            while (!feof($data)) $output = fread($data, 8192);
        }
        else
        {
            $output = $this->data;
        }
        if (substr($output, -1, 1) != chr(0x0c)) {
            if (!isset($this->setup->noFormFeed))
            {
                $this->datatail = chr(0x0c);
            }
        }
        self::_putDebug(_("Forcing data to be interpreted as RAW TEXT") , 2);
    }

    public function unsetRawText()
    {
        $this->setup->datatype = 'BINARY';
        $this->datahead = '';
        $this->datatail = '';
        self::_putDebug(_("Unset forcing data to be interpreted as RAW TEXT") , 2);
    }

    public function setBinary()
    {
        self::unsetRawText();
    }

    public function setFormFeed()
    {
        $this->datatail = "\r\n" . chr(0x0c);
        unset($this->setup->noFormFeed);
    }

    public function unsetFormFeed()
    {
        $this->datatail = '';
        $this->setup->noFormFeed = 1;
    }

    public function setCharset($charset = 'utf-8')
    {
        $charset = strtolower($charset);
        $this->charset = $charset;
        $this->meta->charset = chr(0x47) // charset type | value-tag
            . chr(0x00) . chr(0x12) // name-length
            . "attributes-charset" // attributes-charset | name
            . self::_giveMeStringLength($charset) // value-length
            . $charset; // value
        self::_putDebug(sprintf(_("Charset: %s") , $charset) , 2);
        $this->setup->charset = 1;
    }

    public function setLanguage($language = 'en_us')
    {
        $language = strtolower($language);
        $this->meta->language = chr(0x48) // natural-language type | value-tag
            . chr(0x00) . chr(0x1B) //  name-length
            . "attributes-natural-language" //attributes-natural-language
            . self::_giveMeStringLength($language) // value-length
            . $language; // value
        self::_putDebug(sprintf(_("Language: %s") , $language) , 2);
        $this->setup->language = 1;
    }

    public function setDocumentFormat($mime_media_type = 'application/octet-stream')
    {
        self::setBinary();
        $length = chr(strlen($mime_media_type));
        while (strlen($length) < 2) $length = chr(0x00) . $length;
        self::_putDebug(sprintf(_("mime type: %s") , $mime_media_type) , 2);
        $this->meta->mime_media_type = chr(0x49) // document-format tag
            . self::_giveMeStringLength('document-format') . 'document-format' //
            . self::_giveMeStringLength($mime_media_type) . $mime_media_type; // value
        $this->setup->mime_media_type = 1;
    }

    // setDocumentFormat alias for backward compatibility
    public function setMimeMediaType($mime_media_type = "application/octet-stream")
    {
        self::setDocumentFormat($mime_media_type);
    }

    public function setCopies($nbrcopies = 1)
    {
        $this->meta->copies = "";

        if ($nbrcopies == 1 || !$nbrcopies)
        {
            return true;
        }

        $copies = self::_integerBuild($nbrcopies);
        $this->meta->copies = chr(0x21) // integer type | value-tag
            . chr(0x00) . chr(0x06) //             name-length
            . "copies" // copies    |             name
            . self::_giveMeStringLength($copies) // value-length
            . $copies;
        self::_putDebug(sprintf(_("Copies: %s") , $nbrcopies) , 2);
        $this->setup->copies = 1;
    }

    public function setDocumentName($document_name = "")
    {
        $this->meta->document_name = "";
        if (!$document_name) {
            return true;
        }
        $document_name = substr($document_name, 0, 1023);
        $length = strlen($document_name);
        $length = chr($length);
        while (strlen($length) < 2) $length = chr(0x00) . $length;
        self::_putDebug(sprintf(_("document name: %s") , $document_name) , 2);
        $this->meta->document_name = chr(0x41) // textWithoutLanguage tag
            . chr(0x00) . chr(0x0d) // name-length
            . "document-name" // mimeMediaType
            . self::_giveMeStringLength($document_name) . $document_name; // value

    }

    public function setJobName($jobname = '', $absolute = false)
    {
        $this->meta->jobname = '';
        if ($jobname == '')
        {
            $this->meta->jobname = '';
            return true;
        }
        $postpend = date('-H:i:s-') . $this->_setJobId();
        if ($absolute) {
            $postpend = '';
        }
        if (isset($this->values->jobname) && $jobname == '(PHP)')
        {
            $jobname = $this->values->jobname;
        }
        $this->values->jobname = $jobname;
        $jobname.= $postpend;
        $this->meta->jobname = chr(0x42) // nameWithoutLanguage type || value-tag
            . chr(0x00) . chr(0x08) //  name-length
            . "job-name" //  job-name || name
            . self::_giveMeStringLength($jobname) // value-length
            . $jobname; // value
        self::_putDebug(sprintf(_("Job name: %s") , $jobname) , 2);
        $this->setup->jobname = 1;
    }

    public function setUserName($username = 'PHP-SERVER')
    {
        $this->requesting_user = $username;
        $this->meta->username = '';
        if (!$username) {
            return true;
        }
        if ($username == 'PHP-SERVER' && isset($this->meta->username)) {
            return TRUE;
        }
        /*
        $value_length = 0x00;
        for ($i = 0; $i < strlen($username); $i++)
        {
            $value_length+= 0x01;
        }
        $value_length = chr($value_length);
        while (strlen($value_length) < 2) $value_length = chr(0x00) . $value_length;
        */
        $this->meta->username = chr(0x42) // keyword type || value-tag
            . chr(0x00) . chr(0x14) // name-length
            . "requesting-user-name"
            . self::_giveMeStringLength($username) // value-length
            . $username;
        self::_putDebug(sprintf(_("Username: %s") , $username) , 2);
        $this->setup->username = 1;
    }

    public function setAuthentification($username, $password)
    {
        self::setAuthentication($username, $password);
    }

    public function setAuthentication($username, $password)
    {
        $this->password = $password;
        $this->username = $username;
        self::_putDebug(_("Setting password") , 2);
        $this->setup->password = 1;
    }

    public function setSides($sides = 2)
    {
        $this->meta->sides = '';
        if (!$sides)
        {
            return true;
        }

        switch ($sides)
        {
            case 1:
                $sides = "one-sided";
                break;

            case 2:
                $sides = "two-sided-long-edge";
                break;

            case "2CE":
                $sides = "two-sided-short-edge";
                break;
        }

        $this->meta->sides = chr(0x44) // keyword type | value-tag
            . chr(0x00) . chr(0x05) //        name-length
            . "sides" // sides |             name
            . self::_giveMeStringLength($sides) //               value-length
            . $sides; // one-sided |          value
        self::_putDebug(sprintf(_("Sides value set to %s") , $sides) , 2);
    }

    public function setFidelity()
    {
        // whether the server can't replace any attributes
        // (eg, 2 sided print is not possible,
        // so print one sided) and DO NOT THE JOB.
        $this->meta->fidelity = chr(0x22) // boolean type  |  value-tag
            . chr(0x00) . chr(0x16) //                  name-length
            . "ipp-attribute-fidelity" // ipp-attribute-fidelity | name
            . chr(0x00) . chr(0x01) //  value-length
            . chr(0x01); //  true | value
        self::_putDebug(_("Fidelity attribute is set (paranoid mode)") , 3);
    }

    public function unsetFidelity()
    {
        // whether the server can replace any attributes
        // (eg, 2 sided print is not possible,
        // so print one sided) and DO THE JOB.
        $this->meta->fidelity = chr(0x22) //  boolean type | value-tag
            . chr(0x00) . chr(0x16) //        name-length
            . "ipp-attribute-fidelity" // ipp-attribute-fidelity | name
            . chr(0x00) . chr(0x01) //               value-length
            . chr(0x00); // false |                   value
        self::_putDebug(_("Fidelity attribute is unset") , 2);
    }

    public function setMessage($message = '')
    {
        $this->meta->message = '';
        if (!$message) {
            return true;
        }
        $this->meta->message =
            chr(0x41) // attribute type = textWithoutLanguage
                . chr(0x00)
                . chr(0x07)
                . "message"
                . self::_giveMeStringLength(substr($message, 0, 127))
                . substr($message, 0, 127);
        self::_putDebug(sprintf(_('Setting message to "%s"') , $message) , 2);
    }

    public function setPageRanges($page_ranges)
    {
        // $pages_ranges = string:  "1:5 10:25 40:52 ..."
        // to unset, specify an empty string.
        $this->meta->page_range = '';
        if (!$page_ranges) {
            return true;
        }
        $page_ranges = trim(str_replace("-", ":", $page_ranges));
        $first = true;
        #$page_ranges = split(' ', $page_ranges);
        $page_ranges = preg_split('# #', $page_ranges);
        foreach($page_ranges as $page_range)
        {
            $value = self::_rangeOfIntegerBuild($page_range);
            if ($first)
            {
                $this->meta->page_ranges .=
                $this->tags_types['rangeOfInteger']['tag']
                    . self::_giveMeStringLength('page-ranges')
                    . 'page-ranges'
                    . self::_giveMeStringLength($value)
                    . $value;
            }
            else
            {
                $this->meta->page_ranges .=
                $this->tags_types['rangeOfInteger']['tag']
                    . self::_giveMeStringLength('')
                    . self::_giveMeStringLength($value)
                    . $value;
                $first = false;
            }
        }
    }

    public function setAttribute($attribute, $values)
    {
        $operation_attributes_tags = array_keys($this->operation_tags);
        $job_attributes_tags = array_keys($this->job_tags);
        $printer_attributes_tags = array_keys($this->printer_tags);
        self::unsetAttribute($attribute);
        if (in_array($attribute, $operation_attributes_tags))
        {
            if (!is_array($values))
            {
                self::_setOperationAttribute($attribute, $values);
            }
            else
            {
                foreach($values as $value)
                {
                    self::_setOperationAttribute($attribute, $value);
                }
            }
        }
        elseif (in_array($attribute, $job_attributes_tags))
        {
            if (!is_array($values))
            {
                self::_setJobAttribute($attribute, $values);
            }
            else
            {
                foreach($values as $value)
                {
                    self::_setJobAttribute($attribute, $value);
                }
            }
        }
        elseif (in_array($attribute, $printer_attributes_tags))
        {
            if (!is_array($values))
            {
                self::_setPrinterAttribute($attribute, $values);
            }
            else
            {
                foreach($values as $value)
                {
                    self::_setPrinterAttribute($attribute, $value);
                }
            }
        }
        else
        {
            trigger_error(
                sprintf(_('SetAttribute: Tag "%s" is not a printer or a job attribute'),
                    $attribute) , E_USER_NOTICE);
            self::_putDebug(
                sprintf(_('SetAttribute: Tag "%s" is not a printer or a job attribute'),
                    $attribute) , 3);
            self::_errorLog(
                sprintf(_('SetAttribute: Tag "%s" is not a printer or a job attribute'),
                    $attribute) , 2);
            return FALSE;
        }
    }

    public function unsetAttribute($attribute)
    {
        $operation_attributes_tags = array_keys($this->operation_tags);
        $job_attributes_tags = array_keys($this->job_tags);
        $printer_attributes_tags = array_keys($this->printer_tags);
        if (in_array($attribute, $operation_attributes_tags))
        {
            unset(
                $this->operation_tags[$attribute]['value'],
                $this->operation_tags[$attribute]['systag']
            );
        }
        elseif (in_array($attribute, $job_attributes_tags))
        {
            unset(
                $this->job_tags[$attribute]['value'],
                $this->job_tags[$attribute]['systag']
            );
        }
        elseif (in_array($attribute, $printer_attributes_tags))
        {
            unset(
                $this->printer_tags[$attribute]['value'],
                $this->printer_tags[$attribute]['systag']
            );
        }
        else
        {
            trigger_error(
                sprintf(_('unsetAttribute: Tag "%s" is not a printer or a job attribute'),
                    $attribute) , E_USER_NOTICE);
            self::_putDebug(
                sprintf(_('unsetAttribute: Tag "%s" is not a printer or a job attribute'),
                    $attribute) , 3);
            self::_errorLog(
                sprintf(_('unsetAttribute: Tag "%s" is not a printer or a job attribute'),
                    $attribute) , 2);
            return FALSE;
        }
        return true;
    }

    //
    // LOGGING / DEBUGGING
    //
    /**
     * Sets log file destination. Creates the file if has permission.
     *
     * @param string $log_destination
     * @param string $destination_type
     * @param int $level
     *
     * @throws ippException
     */
    public function setLog($log_destination, $destination_type = 'file', $level = 2)
    {
        if (!file_exists($log_destination) && is_writable(dirname($log_destination)))
        {
            touch($log_destination);
            chmod($log_destination, 0777);
        }

        switch ($destination_type)
        {
            case 'file':
            case 3:
                $this->log_destination = $log_destination;
                $this->log_type = 3;
                break;

            case 'logger':
            case 0:
                $this->log_destination = '';
                $this->log_type = 0;
                break;

            case 'e-mail':
            case 1:
                $this->log_destination = $log_destination;
                $this->log_type = 1;
                break;
        }
        $this->log_level = $level;
    }

    public function printDebug()
    {
        for ($i = 0; $i < $this->debug_count; $i++)
        {
            echo $this->debug[$i], "\n";
        }
        $this->debug = array();
        $this->debug_count = 0;
    }

    public function getDebug()
    {
        $debug = '';
        for ($i = 0; $i < $this->debug_count; $i++)
        {
            $debug.= $this->debug[$i];
        }
        $this->debug = array();
        $this->debug_count = 0;
        return $debug;
    }

    //
    // OPERATIONS
    //
    public function printJob()
    {
        // this BASIC version of printJob do not parse server
        // output for job's attributes
        self::_putDebug(
            sprintf(
                "************** Date: %s ***********",
                date('Y-m-d H:i:s')
            )
        );
        if (!$this->_stringJob()) {
            return FALSE;
        }
        if (is_readable($this->data))
        {
            self::_putDebug(_("Printing a FILE"));
            $this->output = $this->stringjob;
            if ($this->setup->datatype == "TEXT")
            {
                $this->output.= chr(0x16);
            }
            $post_values = array(
                "Content-Type" => "application/ipp",
                "Data" => $this->output,
                "File" => $this->data
            );
            if ($this->setup->datatype == "TEXT" && !isset($this->setup->noFormFeed))
            {
                $post_values = array_merge(
                    $post_values,
                    array(
                        "Filetype" => "TEXT"
                    )
                );
            }
        }
        else
        {
            self::_putDebug(_("Printing DATA"));
            $this->output =
                $this->stringjob
                    . $this->datahead
                    . $this->data
                    . $this->datatail;
            $post_values = array(
                "Content-Type" => "application/ipp",
                "Data" => $this->output
            );
        }
        if (self::_sendHttp($post_values, $this->paths["printers"]))
        {
            self::_parseServerOutput();
        }
        if (isset($this->serveroutput) && isset($this->serveroutput->status))
        {
            $this->status = array_merge($this->status, array(
                $this->serveroutput->status
            ));
            if ($this->serveroutput->status == "successfull-ok")
            {
                self::_errorLog(
                    sprintf("printing job %s: ", $this->last_job)
                        . $this->serveroutput->status,
                    3);
            }
            else
            {
                self::_errorLog(
                    sprintf("printing job: ", $this->last_job)
                        . $this->serveroutput->status,
                    1);
            }
                return $this->serveroutput->status;
        }

    $this->status =
        array_merge($this->status, array("OPERATION FAILED"));
        $this->jobs =
            array_merge($this->jobs, array(""));
        $this->jobs_uri =
            array_merge($this->jobs_uri, array(""));

        self::_errorLog("printing job : OPERATION FAILED", 1);
        return false;
    }

    //
    // HTTP OUTPUT
    //
    protected function _sendHttp($post_values, $uri)
    {
        /*
            This function Copyright (C) 2005-2006 Thomas Harding, Manuel Lemos
        */
        $this->response_completed[] = "no";
        unset($this->serverouptut);
        self::_putDebug(_("Processing HTTP request") , 2);
        $this->serveroutput->headers = array();
        $this->serveroutput->body = "";
        $http = new http_class;
        if (!$this->unix) {
        	// DOL_LDR_CHANGE
        	if (empty($this->host)) $this->host='127.0.0.1';
            $http->host = $this->host;
        }
        else {
            $http->host = "localhost";
        }
        $http->with_exceptions = $this->with_exceptions;
        if ($this->debug_http)
        {
            $http->debug = 1;
            $http->html_debug = 0;
        }
        else
        {
            $http->debug = 0;
            $http->html_debug = 0;
        }
        $url = "http://" . $this->host;
        if ($this->ssl) {
            $url = "https://" . $this->host;
        }
        if ($this->unix) {
            $url = "unix://" . $this->host;
        }
        $http->port = $this->port;
        $http->timeout = $this->http_timeout;
        $http->data_timeout = $this->http_data_timeout;
        $http->force_multipart_form_post = false;
        $http->user = $this->username;
        $http->password = $this->password;
        $error = $http->GetRequestArguments($url, $arguments);
        $arguments["RequestMethod"] = "POST";
        $arguments["Headers"] = array(
            "Content-Type" => "application/ipp"
        );
        $arguments["BodyStream"] = array(
            array(
                "Data" => $post_values["Data"]
            )
        );
        if (isset($post_values["File"])) {
            $arguments["BodyStream"][] = array(
                "File" => $post_values["File"]
            );
        }
        if (isset($post_values["FileType"])
            && !strcmp($post_values["FileType"], "TEXT")
        )
        {
            $arguments["BodyStream"][] = array("Data" => Chr(12));
        }
        $arguments["RequestURI"] = $uri;
        if ($this->with_exceptions && $this->handle_http_exceptions)
        {
            try
            {
                $success = $http->Open($arguments);
            }
            catch(httpException $e)
            {
                throw new ippException(
                    sprintf("http error: %s", $e->getMessage()),
                        $e->getErrno());
            }
        }
        else
        {
        	$success = $http->Open($arguments);
        }
        if ($success[0] == true)
        {
            $success = $http->SendRequest($arguments);
            if ($success[0] == true)
            {
                self::_putDebug("H T T P    R E Q U E S T :");
                self::_putDebug("Request headers:");
                for (Reset($http->request_headers) , $header = 0; $header < count($http->request_headers); Next($http->request_headers) , $header++)
                {
                    $header_name = Key($http->request_headers);
                    if (GetType($http->request_headers[$header_name]) == "array")
                    {
                        for ($header_value = 0; $header_value < count($http->request_headers[$header_name]); $header_value++)
                        {
                            self::_putDebug($header_name . ": " . $http->request_headers[$header_name][$header_value]);
                        }
                    }
                    else
                    {
                        self::_putDebug($header_name . ": " . $http->request_headers[$header_name]);
                    }
                }
                self::_putDebug("Request body:");
                self::_putDebug(
                    htmlspecialchars($http->request_body)
                        . "*********** END REQUEST BODY *********"
                );
                $i = 0;
                $headers = array();
                unset($this->serveroutput->headers);
                $http->ReadReplyHeaders($headers);
                self::_putDebug("H T T P    R E S P O N S E :");
                self::_putDebug("Response headers:");
                for (Reset($headers) , $header = 0; $header < count($headers); Next($headers) , $header++)
                {
                    $header_name = Key($headers);
                    if (GetType($headers[$header_name]) == "array")
                    {
                        for ($header_value = 0; $header_value < count($headers[$header_name]); $header_value++)
                        {
                            self::_putDebug($header_name . ": " . $headers[$header_name][$header_value]);
                            $this->serveroutput->headers[$i] =
                                $header_name . ": "
                                    . $headers[$header_name][$header_value];
                            $i++;
                        }
                    }
                    else
                    {
                        self::_putDebug($header_name . ": " . $headers[$header_name]);
                        $this->serveroutput->headers[$i] =
                            $header_name
                                . ": "
                                . $headers[$header_name];
                        $i++;
                    }
                }
                self::_putDebug("\n\nResponse body:\n");
                $this->serveroutput->body = "";
                for (;;)
                {
                    $http->ReadReplyBody($body, 1024);
                    if (strlen($body) == 0) {
                        break;
                    }

                    self::_putDebug(htmlentities($body));
                    $this->serveroutput->body.= $body;
                }
                self::_putDebug("********* END RESPONSE BODY ********");
            }
        }
        $http->Close();
        return true;
    }

    //
    // INIT
    //
    protected function _initTags()
    {
        $this->tags_types = array(
            "unsupported" => array(
                "tag" => chr(0x10) ,
                "build" => ""
            ) ,
            "reserved" => array(
                "tag" => chr(0x11) ,
                "build" => ""
            ) ,
            "unknown" => array(
                "tag" => chr(0x12) ,
                "build" => ""
            ) ,
            "no-value" => array(
                "tag" => chr(0x13) ,
                "build" => "no_value"
            ) ,
            "integer" => array(
                "tag" => chr(0x21) ,
                "build" => "integer"
            ) ,
            "boolean" => array(
                "tag" => chr(0x22) ,
                "build" => "boolean"
            ) ,
            "enum" => array(
                "tag" => chr(0x23) ,
                "build" => "enum"
            ) ,
            "octetString" => array(
                "tag" => chr(0x30) ,
                "build" => "octet_string"
            ) ,
            "datetime" => array(
                "tag" => chr(0x31) ,
                "build" => "datetime"
            ) ,
            "resolution" => array(
                "tag" => chr(0x32) ,
                "build" => "resolution"
            ) ,
            "rangeOfInteger" => array(
                "tag" => chr(0x33) ,
                "build" => "range_of_integers"
            ) ,
            "textWithLanguage" => array(
                "tag" => chr(0x35) ,
                "build" => "string"
            ) ,
            "nameWithLanguage" => array(
                "tag" => chr(0x36) ,
                "build" => "string"
            ) ,
            /*
            "text" => array ("tag" => chr(0x40),
            "build" => "string"),
            "text string" => array ("tag" => chr(0x40),
            "build" => "string"),
            */
            "textWithoutLanguage" => array(
                "tag" => chr(0x41) ,
                "build" => "string"
            ) ,
            "nameWithoutLanguage" => array(
                "tag" => chr(0x42) ,
                "buid" => "string"
            ) ,
            "keyword" => array(
                "tag" => chr(0x44) ,
                "build" => "string"
            ) ,
            "uri" => array(
                "tag" => chr(0x45) ,
                "build" => "string"
            ) ,
            "uriScheme" => array(
                "tag" => chr(0x46) ,
                "build" => "string"
            ) ,
            "charset" => array(
                "tag" => chr(0x47) ,
                "build" => "string"
            ) ,
            "naturalLanguage" => array(
                "tag" => chr(0x48) ,
                "build" => "string"
            ) ,
            "mimeMediaType" => array(
                "tag" => chr(0x49) ,
                "build" => "string"
            ) ,
            "extendedAttributes" => array(
                "tag" => chr(0x7F) ,
                "build" => "extended"
            ) ,
        );
        $this->operation_tags = array(
            "compression" => array(
                "tag" => "keyword"
            ) ,
            "document-natural-language" => array(
                "tag" => "naturalLanguage"
            ) ,
            "job-k-octets" => array(
                "tag" => "integer"
            ) ,
            "job-impressions" => array(
                "tag" => "integer"
            ) ,
            "job-media-sheets" => array(
                "tag" => "integer"
            ) ,
        );
        $this->job_tags = array(
            "job-priority" => array(
                "tag" => "integer"
            ) ,
            "job-hold-until" => array(
                "tag" => "keyword"
            ) ,
            "job-sheets" => array(
                "tag" => "keyword"
            ) , //banner page
            "multiple-document-handling" => array(
                "tag" => "keyword"
            ) ,
            //"copies" => array("tag" => "integer"),
            "finishings" => array(
                "tag" => "enum"
            ) ,
            //"page-ranges" => array("tag" => "rangeOfInteger"), // has its own function
            //"sides" => array("tag" => "keyword"), // has its own function
            "number-up" => array(
                "tag" => "integer"
            ) ,
            "orientation-requested" => array(
                "tag" => "enum"
            ) ,
            "media" => array(
                "tag" => "keyword"
            ) ,
            "printer-resolution" => array(
                "tag" => "resolution"
            ) ,
            "print-quality" => array(
                "tag" => "enum"
            ) ,
            "job-message-from-operator" => array(
                "tag" => "textWithoutLanguage"
            ) ,
        );
        $this->printer_tags = array(
            "requested-attributes" => array(
                "tag" => "keyword"
            )
        );
    }

    //
    // SETUP
    //
    protected function _setOperationId()
    {
        $prepend = '';
        $this->operation_id+= 1;
        $this->meta->operation_id = self::_integerBuild($this->operation_id);
        self::_putDebug("operation id is: " . $this->operation_id, 2);
    }

    protected function _setJobId()
    {
        $this->meta->jobid+= 1;
        $prepend = '';
        $prepend_length = 4 - strlen($this->meta->jobid);
        for ($i = 0; $i < $prepend_length; $i++) {
            $prepend.= '0';
        }
        return $prepend . $this->meta->jobid;
    }

    protected function _setJobUri($job_uri)
    {
        $this->meta->job_uri = chr(0x45) // type uri
            . chr(0x00) . chr(0x07) // name-length
            . "job-uri"
            //. chr(0x00).chr(strlen($job_uri))
            . self::_giveMeStringLength($job_uri) . $job_uri;
        self::_putDebug("job-uri is: " . $job_uri, 2);
    }

    //
    // RESPONSE PARSING
    //
    protected function _parseServerOutput()
    {
        $this->serveroutput->response = array();
        if (!self::_parseHttpHeaders()) {
            return FALSE;
        }
        $this->_parsing->offset = 0;
        self::_parseIppVersion();
        self::_parseStatusCode();
        self::_parseRequestID();
        $this->_parseResponse();
        //devel
        self::_putDebug(
            sprintf("***** IPP STATUS: %s ******", $this->serveroutput->status),
            4);
        self::_putDebug("****** END OF OPERATION ****");
        return true;
    }

    protected function _parseHttpHeaders()
    {
        $response = "";
        switch ($this->serveroutput->headers[0])
        {
            case "http/1.1 200 ok: ":
                $this->serveroutput->httpstatus = "HTTP/1.1 200 OK";
                $response = "OK";
                break;

            // primitive http/1.0 for Lexmark printers (from Rick Baril)
            case "http/1.0 200 ok: ":
                $this->serveroutput->httpstatus = "HTTP/1.0 200 OK";
                $response = "OK";
                break;

            case "http/1.1 100 continue: ":
                $this->serveroutput->httpstatus = "HTTP/1.1 100 CONTINUE";
                $response = "OK";
                break;

            case "":
                $this->serveroutput->httpstatus = "HTTP/1.1 000 No Response From Server";
                $this->serveroutput->status = "HTTP-ERROR-000_NO_RESPONSE_FROM_SERVER";
                trigger_error("No Response From Server", E_USER_WARNING);
                self::_errorLog("No Response From Server", 1);
                $this->disconnected = 1;
                return FALSE;
                break;

            default:
                $server_response = preg_replace("/: $/", '', $this->serveroutput->headers[0]);
                #$strings = split(' ', $server_response, 3);
                $strings = preg_split('# #', $server_response, 3);
                $errno = $strings[1];
                $string = strtoupper(str_replace(' ', '_', $strings[2]));
                trigger_error(
                    sprintf(_("server responds %s") , $server_response),
                    E_USER_WARNING);
                self::_errorLog("server responds " . $server_response, 1);
                $this->serveroutput->httpstatus =
                    strtoupper($strings[0])
                        . " "
                        . $errno
                        . " "
                        . ucfirst($strings[2]);

                $this->serveroutput->status =
                    "HTTP-ERROR-"
                        . $errno
                        . "-"
                        . $string;
                $this->disconnected = 1;
                return FALSE;
                break;
        }
        unset($this->serveroutput->headers);
        return TRUE;
    }

    protected function _parseIppVersion()
    {
        $ippversion =
            (ord($this->serveroutput->body[$this->_parsing->offset]) * 256)
                + ord($this->serveroutput->body[$this->_parsing->offset + 1]);
        switch ($ippversion)
        {
            case 0x0101:
                $this->serveroutput->ipp_version = "1.1";
                break;

            default:
                $this->serveroutput->ipp_version =
                    sprintf("%u.%u (Unknown)",
                        ord($this->serveroutput->body[$this->_parsing->offset]) * 256,
                        ord($this->serveroutput->body[$this->_parsing->offset + 1]));
                break;
        }
        self::_putDebug("I P P    R E S P O N S E :\n\n");
        self::_putDebug(
            sprintf("IPP version %s%s: %s",
                ord($this->serveroutput->body[$this->_parsing->offset]),
                ord($this->serveroutput->body[$this->_parsing->offset + 1]),
                $this->serveroutput->ipp_version));
        $this->_parsing->offset+= 2;
        return;
    }

    protected function _parseStatusCode()
    {
        $status_code =
            (ord($this->serveroutput->body[$this->_parsing->offset]) * 256)
            + ord($this->serveroutput->body[$this->_parsing->offset + 1]);
        $this->serveroutput->status = "NOT PARSED";
        $this->_parsing->offset+= 2;
        if (strlen($this->serveroutput->body) < $this->_parsing->offset)
        {
            return false;
        }
        if ($status_code < 0x00FF)
        {
            $this->serveroutput->status = "successfull";
        }
        elseif ($status_code < 0x01FF)
        {
            $this->serveroutput->status = "informational";
        }
        elseif ($status_code < 0x02FF)
        {
            $this->serveroutput->status = "redirection";
        }
        elseif ($status_code < 0x04FF)
        {
            $this->serveroutput->status = "client-error";
        }
        elseif ($status_code < 0x05FF)
        {
            $this->serveroutput->status = "server-error";
        }
        switch ($status_code)
        {
            case 0x0000:
                $this->serveroutput->status = "successfull-ok";
                break;

            case 0x0001:
                $this->serveroutput->status = "successful-ok-ignored-or-substituted-attributes";
                break;

            case 0x002:
                $this->serveroutput->status = "successful-ok-conflicting-attributes";
                break;

            case 0x0400:
                $this->serveroutput->status = "client-error-bad-request";
                break;

            case 0x0401:
                $this->serveroutput->status = "client-error-forbidden";
                break;

            case 0x0402:
                $this->serveroutput->status = "client-error-not-authenticated";
                break;

            case 0x0403:
                $this->serveroutput->status = "client-error-not-authorized";
                break;

            case 0x0404:
                $this->serveroutput->status = "client-error-not-possible";
                break;

            case 0x0405:
                $this->serveroutput->status = "client-error-timeout";
                break;

            case 0x0406:
                $this->serveroutput->status = "client-error-not-found";
                break;

            case 0x0407:
                $this->serveroutput->status = "client-error-gone";
                break;

            case 0x0408:
                $this->serveroutput->status = "client-error-request-entity-too-large";
                break;

            case 0x0409:
                $this->serveroutput->status = "client-error-request-value-too-long";
                break;

            case 0x040A:
                $this->serveroutput->status = "client-error-document-format-not-supported";
                break;

            case 0x040B:
                $this->serveroutput->status = "client-error-attributes-or-values-not-supported";
                break;

            case 0x040C:
                $this->serveroutput->status = "client-error-uri-scheme-not-supported";
                break;

            case 0x040D:
                $this->serveroutput->status = "client-error-charset-not-supported";
                break;

            case 0x040E:
                $this->serveroutput->status = "client-error-conflicting-attributes";
                break;

            case 0x040F:
                $this->serveroutput->status = "client-error-compression-not-supported";
                break;

            case 0x0410:
                $this->serveroutput->status = "client-error-compression-error";
                break;

            case 0x0411:
                $this->serveroutput->status = "client-error-document-format-error";
                break;

            case 0x0412:
                $this->serveroutput->status = "client-error-document-access-error";
                break;

            case 0x0413: // RFC3380
                $this->serveroutput->status = "client-error-attributes-not-settable";
                break;

            case 0x0500:
                $this->serveroutput->status = "server-error-internal-error";
                break;

            case 0x0501:
                $this->serveroutput->status = "server-error-operation-not-supported";
                break;

            case 0x0502:
                $this->serveroutput->status = "server-error-service-unavailable";
                break;

            case 0x0503:
                $this->serveroutput->status = "server-error-version-not-supported";
                break;

            case 0x0504:
                $this->serveroutput->status = "server-error-device-error";
                break;

            case 0x0505:
                $this->serveroutput->status = "server-error-temporary-error";
                break;

            case 0x0506:
                $this->serveroutput->status = "server-error-not-accepting-jobs";
                break;

            case 0x0507:
                $this->serveroutput->status = "server-error-busy";
                break;

            case 0x0508:
                $this->serveroutput->status = "server-error-job-canceled";
                break;

            case 0x0509:
                $this->serveroutput->status = "server-error-multiple-document-jobs-not-supported";
                break;

            default:
                break;
        }
        self::_putDebug(
            sprintf(
                "status-code: %s%s: %s ",
                $this->serveroutput->body[$this->_parsing->offset],
                $this->serveroutput->body[$this->_parsing->offset + 1],
                $this->serveroutput->status),
            4);
        return;
    }

    protected function _parseRequestID()
    {
        $this->serveroutput->request_id =
            self::_interpretInteger(
                substr($this->serveroutput->body, $this->_parsing->offset, 4)
            );
        self::_putDebug("request-id " . $this->serveroutput->request_id, 2);
        $this->_parsing->offset+= 4;
        return;
    }

    protected function _interpretInteger($value)
    {
        // they are _signed_ integers
        $value_parsed = 0;
        for ($i = strlen($value); $i > 0; $i --)
        {
            $value_parsed +=
                (
                    (1 << (($i - 1) * 8))
                        *
                        ord($value[strlen($value) - $i])
                );
        }
        if ($value_parsed >= 2147483648)
        {
            $value_parsed -= 4294967296;
        }
        return $value_parsed;
    }

    protected function _parseResponse()
    {
    }

    //
    // REQUEST BUILDING
    //
    protected function _stringJob()
    {
        if (!isset($this->setup->charset)) {
            self::setCharset();
        }
        if (!isset($this->setup->datatype)) {
            self::setBinary();
        }
        if (!isset($this->setup->uri))
        {
            $this->getPrinters();
            unset($this->jobs[count($this->jobs) - 1]);
            unset($this->jobs_uri[count($this->jobs_uri) - 1]);
            unset($this->status[count($this->status) - 1]);
            if (array_key_exists(0, $this->available_printers))
            {
                self::setPrinterURI($this->available_printers[0]);
            }
            else
            {
                trigger_error(
                    _("_stringJob: Printer URI is not set: die"),
                    E_USER_WARNING);
                self::_putDebug(_("_stringJob: Printer URI is not set: die") , 4);
                self::_errorLog(" Printer URI is not set, die", 2);
                return FALSE;
            }
        }
        if (!isset($this->setup->copies)) {
            self::setCopies(1);
        }
        if (!isset($this->setup->language)) {
            self::setLanguage('en_us');
        }
        if (!isset($this->setup->mime_media_type)) {
            self::setMimeMediaType();
        }
        if (!isset($this->setup->jobname)) {
            self::setJobName();
        }
        unset($this->setup->jobname);
        if (!isset($this->meta->username)) {
            self::setUserName();
        }
        if (!isset($this->meta->fidelity)) {
            $this->meta->fidelity = '';
        }
        if (!isset($this->meta->document_name)) {
            $this->meta->document_name = '';
        }
        if (!isset($this->meta->sides)) {
            $this->meta->sides = '';
        }
        if (!isset($this->meta->page_ranges)) {
            $this->meta->page_ranges = '';
        }
        $jobattributes = '';
        $operationattributes = '';
        $printerattributes = '';
        $this->_buildValues($operationattributes, $jobattributes, $printerattributes);
        self::_setOperationId();
        if (!isset($this->error_generation->request_body_malformed))
        {
            $this->error_generation->request_body_malformed = "";
        }
        $this->stringjob = chr(0x01) . chr(0x01) // 1.1  | version-number
            . chr(0x00) . chr(0x02) // Print-Job | operation-id
            . $this->meta->operation_id //           request-id
            . chr(0x01) // start operation-attributes | operation-attributes-tag
            . $this->meta->charset
            . $this->meta->language
            . $this->meta->printer_uri
            . $this->meta->username
            . $this->meta->jobname
            . $this->meta->fidelity
            . $this->meta->document_name
            . $this->meta->mime_media_type
            . $operationattributes;
        if ($this->meta->copies || $this->meta->sides || $this->meta->page_ranges || !empty($jobattributes))
        {
            $this->stringjob .=
                chr(0x02) // start job-attributes | job-attributes-tag
                    . $this->meta->copies
                    . $this->meta->sides
                    . $this->meta->page_ranges
                    . $jobattributes;
        }
        $this->stringjob.= chr(0x03); // end-of-attributes | end-of-attributes-tag
        self::_putDebug(
            sprintf(_("String sent to the server is: %s"),
                $this->stringjob)
            );
        return TRUE;
    }

    protected function _buildValues(&$operationattributes, &$jobattributes, &$printerattributes)
    {
        $operationattributes = '';
        foreach($this->operation_tags as $key => $values)
        {
            $item = 0;
            if (array_key_exists('value', $values))
            {
                foreach($values['value'] as $item_value)
                {
                    if ($item == 0)
                    {
                        $operationattributes .=
                            $values['systag']
                                . self::_giveMeStringLength($key)
                                . $key
                                . self::_giveMeStringLength($item_value)
                                . $item_value;
                    }
                    else
                    {
                        $operationattributes .=
                            $values['systag']
                                . self::_giveMeStringLength('')
                                . self::_giveMeStringLength($item_value)
                                . $item_value;
                    }
                    $item++;
                }
            }
        }
        $jobattributes = '';
        foreach($this->job_tags as $key => $values)
        {
            $item = 0;
            if (array_key_exists('value', $values))
            {
                foreach($values['value'] as $item_value)
                {
                    if ($item == 0)
                    {
                        $jobattributes .=
                            $values['systag']
                                . self::_giveMeStringLength($key)
                                . $key
                                . self::_giveMeStringLength($item_value)
                                . $item_value;
                    }
                    else
                    {
                        $jobattributes .=
                            $values['systag']
                                . self::_giveMeStringLength('')
                                . self::_giveMeStringLength($item_value)
                                . $item_value;
                    }
                    $item++;
                }
            }
        }
        $printerattributes = '';
        foreach($this->printer_tags as $key => $values)
        {
            $item = 0;
            if (array_key_exists('value', $values))
            {
                foreach($values['value'] as $item_value)
                {
                    if ($item == 0)
                    {
                        $printerattributes .=
                            $values['systag']
                                . self::_giveMeStringLength($key)
                                . $key
                                . self::_giveMeStringLength($item_value)
                                . $item_value;
                    }
                    else
                    {
                        $printerattributes .=
                            $values['systag']
                                . self::_giveMeStringLength('')
                                . self::_giveMeStringLength($item_value)
                                . $item_value;
                    }
                    $item++;
                }
            }
        }
        reset($this->job_tags);
        reset($this->operation_tags);
        reset($this->printer_tags);
        return true;
    }

    protected function _giveMeStringLength($string)
    {
        $length = strlen($string);
        if ($length > ((0xFF << 8) + 0xFF)  )
        {
            $errmsg = sprintf (
                _('max string length for an ipp meta-information = %d, while here %d'),
                ((0xFF << 8) + 0xFF), $length);

            if ($this->with_exceptions)
            {
                throw new ippException($errmsg);
            }
            else
            {
                trigger_error ($errmsg, E_USER_ERROR);
            }
        }
        $int1 = $length & 0xFF;
        $length -= $int1;
        $length = $length >> 8;
        $int2 = $length & 0xFF;
        return chr($int2) . chr($int1);
    }

    protected function _enumBuild($tag, $value)
    {
        switch ($tag)
        {
            case "orientation-requested":
                switch ($value)
                {
                    case 'portrait':
                        $value = chr(3);
                        break;

                    case 'landscape':
                        $value = chr(4);
                        break;

                    case 'reverse-landscape':
                        $value = chr(5);
                        break;

                    case 'reverse-portrait':
                        $value = chr(6);
                        break;
                }
                break;

            case "print-quality":
                switch ($value)
                {
                    case 'draft':
                        $value = chr(3);
                        break;

                    case 'normal':
                        $value = chr(4);
                        break;

                    case 'high':
                        $value = chr(5);
                        break;
                }
                break;

            case "finishing":
                switch ($value)
                {
                    case 'none':
                        $value = chr(3);
                        break;

                    case 'staple':
                        $value = chr(4);
                        break;

                    case 'punch':
                        $value = chr(5);
                        break;

                    case 'cover':
                        $value = chr(6);
                        break;

                    case 'bind':
                        $value = chr(7);
                        break;

                    case 'saddle-stitch':
                        $value = chr(8);
                        break;

                    case 'edge-stitch':
                        $value = chr(9);
                        break;

                    case 'staple-top-left':
                        $value = chr(20);
                        break;

                    case 'staple-bottom-left':
                        $value = chr(21);
                        break;

                    case 'staple-top-right':
                        $value = chr(22);
                        break;

                    case 'staple-bottom-right':
                        $value = chr(23);
                        break;

                    case 'edge-stitch-left':
                        $value = chr(24);
                        break;

                    case 'edge-stitch-top':
                        $value = chr(25);
                        break;

                    case 'edge-stitch-right':
                        $value = chr(26);
                        break;

                    case 'edge-stitch-bottom':
                        $value = chr(27);
                        break;

                    case 'staple-dual-left':
                        $value = chr(28);
                        break;

                    case 'staple-dual-top':
                        $value = chr(29);
                        break;

                    case 'staple-dual-right':
                        $value = chr(30);
                        break;

                    case 'staple-dual-bottom':
                        $value = chr(31);
                        break;
                }
                break;
        }
        $prepend = '';
        while ((strlen($value) + strlen($prepend)) < 4)
        {
            $prepend .= chr(0);
        }
        return $prepend . $value;
    }

    protected function _integerBuild($value)
    {
        if ($value >= 2147483647 || $value < - 2147483648)
        {
            trigger_error(
                _("Values must be between -2147483648 and 2147483647: assuming '0'") , E_USER_WARNING);
            return chr(0x00) . chr(0x00) . chr(0x00) . chr(0x00);
        }
        $initial_value = $value;
        $int1 = $value & 0xFF;
        $value -= $int1;
        $value = $value >> 8;
        $int2 = $value & 0xFF;
        $value-= $int2;
        $value = $value >> 8;
        $int3 = $value & 0xFF;
        $value-= $int3;
        $value = $value >> 8;
        $int4 = $value & 0xFF; //64bits
        if ($initial_value < 0) {
            $int4 = chr($int4) | chr(0x80);
        }
        else {
            $int4 = chr($int4);
        }
        $value = $int4 . chr($int3) . chr($int2) . chr($int1);
        return $value;
    }

    protected function _rangeOfIntegerBuild($integers)
    {
        #$integers = split(":", $integers);
        $integers = preg_split("#:#", $integers);
        for ($i = 0; $i < 2; $i++) {
            $outvalue[$i] = self::_integerBuild($integers[$i]);
        }
        return $outvalue[0] . $outvalue[1];
    }

    protected function _setJobAttribute($attribute, $value)
    {
        //used by setAttribute
        $tag_type = $this->job_tags[$attribute]['tag'];
        switch ($tag_type)
        {
            case 'integer':
                $this->job_tags[$attribute]['value'][] = self::_integerBuild($value);
                break;

            case 'boolean':
            case 'nameWithoutLanguage':
            case 'nameWithLanguage':
            case 'textWithoutLanguage':
            case 'textWithLanguage':
            case 'keyword':
            case 'naturalLanguage':
                $this->job_tags[$attribute]['value'][] = $value;
                break;

            case 'enum':
                $value = $this->_enumBuild($attribute, $value); // may be overwritten by children
                $this->job_tags[$attribute]['value'][] = $value;
                break;

            case 'rangeOfInteger':
                // $value have to be: INT1:INT2 , eg 100:1000
                $this->job_tags[$attribute]['value'][] = self::_rangeOfIntegerBuild($value);
                break;

            case 'resolution':
                if (preg_match("#dpi#", $value)) {
                    $unit = chr(0x3);
                }
                if (preg_match("#dpc#", $value)) {
                    $unit = chr(0x4);
                }
                $search = array(
                    "#(dpi|dpc)#",
                    '#(x|-)#'
                );
                $replace = array(
                    "",
                    ":"
                );
                $value = self::_rangeOfIntegerBuild(preg_replace($search, $replace, $value)) . $unit;
                $this->job_tags[$attribute]['value'][] = $value;
                break;

            default:
                trigger_error(sprintf(_('SetAttribute: Tag "%s": cannot set attribute') , $attribute) , E_USER_NOTICE);
                self::_putDebug(sprintf(_('SetAttribute: Tag "%s": cannot set attribute') , $attribute) , 2);
                self::_errorLog(sprintf(_('SetAttribute: Tag "%s": cannot set attribute') , $attribute) , 2);
                return FALSE;
                break;
        }
        $this->job_tags[$attribute]['systag'] = $this->tags_types[$tag_type]['tag'];
    }

    protected function _setOperationAttribute($attribute, $value)
    {
        //used by setAttribute
        $tag_type = $this->operation_tags[$attribute]['tag'];
        switch ($tag_type)
        {
            case 'integer':
                $this->operation_tags[$attribute]['value'][] = self::_integerBuild($value);
                break;

            case 'keyword':
            case 'naturalLanguage':
                $this->operation_tags[$attribute]['value'][] = $value;
                break;

            default:
                trigger_error(sprintf(_('SetAttribute: Tag "%s": cannot set attribute') , $attribute) , E_USER_NOTICE);
                self::_putDebug(sprintf(_('SetAttribute: Tag "%s": cannot set attribute') , $attribute) , 2);
                self::_errorLog(sprintf(_('SetAttribute: Tag "%s": cannot set attribute') , $attribute) , 2);
                return FALSE;
                break;
        }
        $this->operation_tags[$attribute]['systag'] = $this->tags_types[$tag_type]['tag'];
    }

    protected function _setPrinterAttribute($attribute, $value)
    {
        //used by setAttribute
        $tag_type = $this->printer_tags[$attribute]['tag'];
        switch ($tag_type)
        {
            case 'integer':
                $this->printer_tags[$attribute]['value'][] = self::_integerBuild($value);
                break;

            case 'keyword':
            case 'naturalLanguage':
                $this->printer_tags[$attribute]['value'][] = $value;
                break;

            default:
                trigger_error(sprintf(_('SetAttribute: Tag "%s": cannot set attribute') , $attribute) , E_USER_NOTICE);
                self::_putDebug(sprintf(_('SetAttribute: Tag "%s": cannot set attribute') , $attribute) , 2);
                self::_errorLog(sprintf(_('SetAttribute: Tag "%s": cannot set attribute') , $attribute) , 2);
                return FALSE;
                break;
        }
        $this->printer_tags[$attribute]['systag'] = $this->tags_types[$tag_type]['tag'];
    }

    //
    // DEBUGGING
    //
    protected function _putDebug($string, $level = 1)
    {
        if ($level === false) {
            return;
        }

        if ($level < $this->debug_level) {
            return;
        }

        $this->debug[$this->debug_count] = substr($string, 0, 1024);
        $this->debug_count++;
        //$this->debug .= substr($string,0,1024);

    }

    //
    // LOGGING
    //
    protected function _errorLog($string_to_log, $level)
    {
        if ($level > $this->log_level) {
            return;
        }

        $string = sprintf('%s : %s:%s user %s : %s', basename($_SERVER['PHP_SELF']) , $this->host, $this->port, $this->requesting_user, $string_to_log);

        if ($this->log_type == 0)
        {
            error_log($string);
            return;
        }

        $string = sprintf("%s %s Host %s:%s user %s : %s\n", date('M d H:i:s') , basename($_SERVER['PHP_SELF']) , $this->host, $this->port, $this->requesting_user, $string_to_log);
        error_log($string, $this->log_type, $this->log_destination);
        return;
    }
}
