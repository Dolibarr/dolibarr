<?php

/* @(#) $Header: /sources/phpprintipp/phpprintipp/php_classes/CupsPrintIPP.php,v 1.1 2008/06/21 00:30:56 harding Exp $
 *
 * Class PrintIPP - Send extended IPP requests.
 *
 *   Copyright (C) 2005-2006  Thomas HARDING
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
        - RFC 3382
        - ...
        - CUPS-IPP-1.1
*/

require_once("ExtendedPrintIPP.php");

class CupsPrintIPP extends ExtendedPrintIPP
{
    public $printers_attributes;
    public $defaults_attributes;

    protected $parsed;
    protected $output;

    public function __construct()
    {
        parent::__construct();
        self::_initTags();
    }

//
// OPERATIONS
//
    public function cupsGetDefaults($attributes=array("all"))
    {
        //The CUPS-Get-Default operation returns the default printer URI and attributes

        $this->jobs = array_merge($this->jobs,array(""));
        $this->jobs_uri = array_merge($this->jobs_uri,array(""));
        $this->parsed = array();
        unset($this->printer_attributes);

        if (!isset($this->setup->charset))
        {
            self::setCharset();
        }

        if (!isset($this->setup->language))
        {
            self::setLanguage('en');
        }

        self::_setOperationId();

        for($i = 0 ; $i < count($attributes) ; $i++)
        {
            if ($i == 0)
            {
                $this->meta->attributes = chr(0x44) // Keyword
                                        . self::_giveMeStringLength('requested-attributes')
                                        . 'requested-attributes'
                                        . self::_giveMeStringLength($attributes[0])
                                        . $attributes[0];
            }
            else
            {
                $this->meta->attributes .= chr(0x44) // Keyword
                                        .  chr(0x0).chr(0x0) // zero-length name
                                        .  self::_giveMeStringLength($attributes[$i])
                                        .  $attributes[$i];
            }
        }

        $this->stringjob = chr(0x01) . chr(0x01) // IPP version 1.1
                         . chr(0x40). chr(0x01) // operation:  cups vendor extension: get defaults
                         . $this->meta->operation_id // request-id
                         . chr(0x01) // start operation-attributes | operation-attributes-tag
                         . $this->meta->charset
                         . $this->meta->language
                         . $this->meta->attributes
                         . chr(0x03); // end operations attribute

        $this->output = $this->stringjob;

        self::_putDebug("Request: ".$this->output);

        $post_values = array( "Content-Type" => "application/ipp",
                              "Data" => $this->output);

        if (self::_sendHttp ($post_values,'/'))
        {

            if(self::_parseServerOutput())
            {
                self::_parsePrinterAttributes();
            }
        }

       $this->attributes = &$this->printer_attributes;

       if (isset($this->printer_attributes->printer_type))
       {
                    $printer_type = $this->printer_attributes->printer_type->_value0;
                    $table = self::_interpretPrinterType($printer_type);

                    for($i = 0 ; $i < count($table) ; $i++ )
                    {
                        $index = '_value'.$i;
                        $this->printer_attributes->printer_type->$index = $table[$i];
                        }
                    }

        if (isset($this->serveroutput) && isset($this->serveroutput->status))
        {

            $this->status = array_merge($this->status,array($this->serveroutput->status));
            if ($this->serveroutput->status == "successfull-ok")
            {
                self::_errorLog("getting defaults: ".$this->serveroutput->status,3);
            }
            else
            {
                self::_errorLog("getting defaults: ".$this->serveroutput->status,1);
            }

            return $this->serveroutput->status;
        }
        else
        {
            $this->status = array_merge($this->status,array("OPERATION FAILED"));
            self::_errorLog("getting defaults : OPERATION FAILED",1);
            }
    return false;
    }


    public function cupsAcceptJobs($printer_uri)
    {
    //The CUPS-Get-Default operation returns the default printer URI and attributes

        $this->jobs = array_merge($this->jobs,array(""));
        $this->jobs_uri = array_merge($this->jobs_uri,array(""));
        $this->parsed = array();
        unset($this->printer_attributes);

        if (!isset($this->setup->charset))
        {
            self::setCharset();
        }

        if (!isset($this->setup->language))
        {
            self::setLanguage('en');
        }

        self::_setOperationId();

        $this->stringjob = chr(0x01) . chr(0x01) // IPP version 1.1
                         . chr(0x40). chr(0x08) // operation:  cups vendor extension: Accept-Jobs
                         . $this->meta->operation_id // request-id
                         . chr(0x01) // start operation-attributes | operation-attributes-tag
                         . $this->meta->charset
                         . $this->meta->language
                         . chr(0x45) // uri
                         . self::_giveMeStringLength('printer-uri')
                         . 'printer-uri'
                         . self::_giveMeStringLength($printer_uri)
                         . $printer_uri
                         . chr(0x03); // end operations attribute

        $this->output = $this->stringjob;

        self::_putDebug("Request: ".$this->output);

        $post_values = array( "Content-Type" => "application/ipp",
                              "Data" => $this->output);

        if (self::_sendHttp ($post_values,'/admin/'))
        {

            if(self::_parseServerOutput())
            {
                self::_parseAttributes();
            }
        }

        if (isset($this->serveroutput) && isset($this->serveroutput->status))
        {

            $this->status = array_merge($this->status,array($this->serveroutput->status));
            if ($this->serveroutput->status == "successfull-ok")
            {
                self::_errorLog("getting defaults: ".$this->serveroutput->status,3);
            }
            else
            {
                self::_errorLog("getting defaults: ".$this->serveroutput->status,1);
            }

            return $this->serveroutput->status;
        }
        else
        {
            $this->status = array_merge($this->status,array("OPERATION FAILED"));
            self::_errorLog("getting defaults : OPERATION FAILED",1);
            }
    return false;
    }


    public function cupsRejectJobs($printer_uri,$printer_state_message)
    {
    //The CUPS-Get-Default operation returns the default printer URI and attributes

        $this->jobs = array_merge($this->jobs,array(""));
        $this->jobs_uri = array_merge($this->jobs_uri,array(""));
        $this->parsed = array();
        unset($this->attributes);

        if (!isset($this->setup->charset))
        {
            self::setCharset();
        }

        if (!isset($this->setup->language))
        {
            self::setLanguage('en');
        }

        self::_setOperationId();

        $message = "";
        if ($printer_state_message)
        {
            $message = chr(0x04) // start printer-attributes
                     . chr(0x41) // textWithoutLanguage
                     . self::_giveMeStringLength("printer-state-message")
                     . "printer-state-message"
                     . self::_giveMeStringLength($printer_state_message)
                     . $printer_state_message;
        }

       $this->stringjob = chr(0x01) . chr(0x01) // IPP version 1.1
                         . chr(0x40). chr(0x09) // operation:  cups vendor extension: Reject-Jobs
                         . $this->meta->operation_id // request-id
                         . chr(0x01) // start operation-attributes | operation-attributes-tag
                         . $this->meta->charset
                         . $this->meta->language
                         . chr(0x45) // uri
                         . self::_giveMeStringLength('printer-uri')
                         . 'printer-uri'
                         . self::_giveMeStringLength($printer_uri)
                         . $printer_uri
                         . $message
                         . chr(0x03); // end operations attribute

        $this->output = $this->stringjob;

        self::_putDebug("Request: ".$this->output);

        $post_values = array( "Content-Type" => "application/ipp",
                              "Data" => $this->output);

        if (self::_sendHttp ($post_values,'/admin/'))
        {

            if(self::_parseServerOutput())
            {
                self::_parseAttributes();
            }
        }

        if (isset($this->serveroutput) && isset($this->serveroutput->status))
        {

            $this->status = array_merge($this->status,array($this->serveroutput->status));
            if ($this->serveroutput->status == "successfull-ok")
            {
                self::_errorLog("getting defaults: ".$this->serveroutput->status,3);
            }
            else
            {
                self::_errorLog("getting defaults: ".$this->serveroutput->status,1);
            }

            return $this->serveroutput->status;
        }
        else
        {
            $this->status = array_merge($this->status,array("OPERATION FAILED"));
            self::_errorLog("getting defaults : OPERATION FAILED",1);
        }
        return false;
    }


    public function getPrinters($printer_location=false,$printer_info=false,$attributes=array())
    {
        if (count($attributes) == 0)
        {
            true;
        }
        $attributes=array('printer-uri-supported', 'printer-location', 'printer-info', 'printer-type', 'color-supported', 'printer-name');
        $this->jobs = array_merge($this->jobs,array(""));
        $this->jobs_uri = array_merge($this->jobs_uri,array(""));

        unset ($this->printers_attributes);

        if (!isset($this->setup->charset))
        {
            self::setCharset();
        }

        if (!isset($this->setup->language))
        {
            self::setLanguage('en-us');
        }

        self::_setOperationId();

        $this->meta->attributes='';

        if ($printer_location)
        {
            $this->meta->attributes .= chr(0x41) // textWithoutLanguage
                                    . self::_giveMeStringLength('printer-location')
                                    . 'printer-location'
                                    . self::_giveMeStringLength($printer_location)
                                    . $printer_location;
        }

        if ($printer_info)
        {
            $this->meta->attributes .= chr(0x41) // textWithoutLanguage
                                    . self::_giveMeStringLength('printer-info')
                                    . 'printer-info'
                                    . self::_giveMeStringLength($printer_info)
                                    . $printer_info;
        }

        for($i = 0 ; $i < count($attributes) ; $i++)
        {
            if ($i == 0)
            {
                $this->meta->attributes .= chr(0x44) // Keyword
                                        . self::_giveMeStringLength('requested-attributes')
                                        . 'requested-attributes'
                                        . self::_giveMeStringLength($attributes[0])
                                        . $attributes[0];
            }
            else
            {
                $this->meta->attributes .= chr(0x44) // Keyword
                                        .  chr(0x0).chr(0x0) // zero-length name
                                        .  self::_giveMeStringLength($attributes[$i])
                                        .  $attributes[$i];
            }
        }

        $this->stringjob = chr(0x01) . chr(0x01) // IPP version 1.1
                         . chr(0x40). chr(0x02) // operation:  cups vendor extension: get printers
                         . $this->meta->operation_id //           request-id
                         . chr(0x01) // start operation-attributes | operation-attributes-tag
                         . $this->meta->charset
                         . $this->meta->language
                         . $this->meta->attributes
                         . chr(0x03); // end operations attribute

        $this->output = $this->stringjob;

        $post_values = array( "Content-Type" => "application/ipp",
                              "Data" => $this->output);

        if (self::_sendHttp ($post_values,'/'))
        {

            if(self::_parseServerOutput())
            {
                $this->_getAvailablePrinters();
            }
        }

        if (isset($this->serveroutput) && isset($this->serveroutput->status))
        {

            $this->status = array_merge($this->status,array($this->serveroutput->status));
            if ($this->serveroutput->status == "successfull-ok")
            {
                self::_errorLog("getting printers: ".$this->serveroutput->status,3);
            }
            else
            {
                self::_errorLog("getting printers: ".$this->serveroutput->status,1);
            }
            return $this->serveroutput->status;
        }
        else
        {
            $this->status = array_merge($this->status,array("OPERATION FAILED"));
            self::_errorLog("getting printers : OPERATION FAILED",1);
        }
        return false;
    }


    public function cupsGetPrinters ()
    {
        // alias for getPrinters();
        self::getPrinters();
    }


    public function getPrinterAttributes()
    {
        // complete informations from parent with Cups-specific stuff

        if(!$result = parent::getPrinterAttributes())
        {
            return FALSE;
        }
        if(!isset($this->printer_attributes))
        {
            return FALSE;
        }

        if (isset ($this->printer_attributes->printer_type))
        {
            $printer_type = $this->printer_attributes->printer_type->_value0;
            $table = self::_interpretPrinterType($printer_type);

            for($i = 0 ; $i < count($table) ; $i++ )
            {
                $index = '_value'.$i;
                $this->printer_attributes->printer_type->$index = $table[$i];
            }
        }

        return $result;
    }

//
// SETUP
//
    protected function _initTags ()
    {
        // override parent with specific cups attributes

        $operation_tags = array ();
        $this->operation_tags = array_merge ($this->operation_tags, $operation_tags);

        $job_tags = array ( "job-billing" => array("tag" => "textWithoutLanguage"),
                            "blackplot" => array("tag" => "boolean"),
                            "brightness" => array("tag" => "integer"),
                            "columns" => array("tag" => "integer"),
                            "cpi" => array("tag" => "enum"),
                            "fitplot" => array("tag" => "boolean"),
                            "gamma" => array("tag" => "integer"),
                            "hue" => array("tag" => "integer"),
                            "lpi" => array("tag" => "enum"),
                            "mirror" => array("tag","boolean"),
                            "natural-scaling" => array("tag" => "integer"),
                            "number-up-layout" => array("tag" => "keyword"),
                            "page-border" => array("tag" => "keyword"),
                            "page-bottom" => array("tag" => "integer"),
                            "page-label" => array("tag" => "textWithoutLanguage"),
                            "page-left" => array("tag" => "integer"),
                            "page-right" => array("tag" => "integer"),
                            "page-set" => array("tag" => "keyword"),
                            "page-top" => array("tag" => "integer"),
                            "penwidth" => array("tag" => "integer"),
                            "position" => array("tag" => "keyword"),
                            "ppi" => array("tag" => "integer"),
                            "prettyprint" => array("tag","boolean"),
                            "saturation" => array("tag" => "integer"),
                            "scaling" => array("tag" => "integer"),
                            "wrap" => array("tag","boolean"),

                            );
        $this->job_tags = array_merge ($this->job_tags, $job_tags);
    }

    //
    // REQUEST BUILDING
    //
    protected function _enumBuild ($tag,$value)
    {
        $value_built = parent::_enumBuild($tag,$value);

        switch ($tag)
        {
           case "cpi":
                switch ($value)
                {
                    case '10':
                        $value_built = chr(10);
                        break;
                    case '12':
                        $value_built = chr(12);
                        break;
                    case '17':
                        $value_built = chr(17);
                        break;
                    default:
                        $value_built = chr(10);
                }
            break;
            case "lpi":
                switch ($value)
                {
                    case '6':
                        $value_built = chr(6);
                        break;
                    case '8':
                        $value_built = chr(8);
                        break;
                    default:
                        $value_built = chr(6);
                }
            break;
            }

        $prepend = '';
        while ((strlen($value_built) + strlen($prepend)) < 4)
            $prepend .= chr(0);
        return $prepend.$value_built;
    }

    //
    // RESPONSE PARSING
    //
    private function _getAvailablePrinters ()
    {
        $this->available_printers = array();
        $this->printer_map = array();
        $k = 0;
        $this->printers_attributes = new \stdClass();

        for ($i = 0 ; (array_key_exists($i,$this->serveroutput->response)) ; $i ++)
        {
            if (($this->serveroutput->response[$i]['attributes']) == "printer-attributes")
            {
                $phpname = "_printer".$k;
                $this->printers_attributes->$phpname = new \stdClass();
                for ($j = 0 ; array_key_exists($j,$this->serveroutput->response[$i]) ; $j++)
                {

                    $value = $this->serveroutput->response[$i][$j]['value'];
                    $name = str_replace("-","_",$this->serveroutput->response[$i][$j]['name']);

                    switch ($name)
                    {
                        case "printer_uri_supported":
                            $this->available_printers = array_merge($this->available_printers,array($value));
                            break;
                        case "printer_type":
                            $table = self::_interpretPrinterType($value);
                            $this->printers_attributes->$phpname->$name = new \stdClass();

                            for($l = 0 ; $l < count($table) ; $l++ )
                            {
                                $index = '_value'.$l;
                                $this->printers_attributes->$phpname->$name->$index = $table[$l];
                            }

                            break;
                        case '':
                            break;
                        case 'printer_name':
                            $this->printer_map[$value] = $k;
                            break;
                        default:
                            $this->printers_attributes->$phpname->$name = $value;
                            break;
                    }
                }
                $k ++;
            }
        }
    }

    protected function _getEnumVendorExtensions ($value_parsed)
    {
        switch ($value_parsed)
        {
            case 0x4002:
                $value = 'Get-Availables-Printers';
                break;
            default:
                $value = sprintf('Unknown(Cups extension for operations): 0x%x',$value_parsed);
                break;
        }

        if (isset($value))
        {
            return ($value);
        }

        return sprintf('Unknown: 0x%x',$value_parsed);
    }


    private function _interpretPrinterType($value)
    {
        $value_parsed = 0;
        for ($i = strlen($value) ; $i > 0 ; $i --)
        {
            $value_parsed += pow(256,($i - 1)) * ord($value[strlen($value) - $i]);
        }

        $type[0] = $type[1] = $type[2] = $type[3] = $type[4] = $type[5] = '';
        $type[6] = $type[7] = $type[8] = $type[9] = $type[10] = '';
        $type[11] = $type[12] = $type[13] = $type[14] = $type[15] = '';
        $type[16] = $type[17] = $type[18] = $type[19] = '';

        if ($value_parsed %2 == 1)
        {
            $type[0] = 'printer-class';
            $value_parsed -= 1;
        }

        if ($value_parsed %4 == 2 )
        {
            $type[1] = 'remote-destination';
            $value_parsed -= 2;
        }

        if ($value_parsed %8 == 4 )
        {
            $type[2] = 'print-black';
            $value_parsed -= 4;
        }

        if ($value_parsed %16 == 8 )
        {
            $type[3] = 'print-color';
            $value_parsed -= 8;
        }

        if ($value_parsed %32 == 16)
        {
            $type[4] = 'hardware-print-on-both-sides';
            $value_parsed -= 16;
        }

        if ($value_parsed %64 == 32)
        {
            $type[5] = 'hardware-staple-output';
            $value_parsed -= 32;
        }

        if ($value_parsed %128 == 64)
        {
            $type[6] = 'hardware-fast-copies';
            $value_parsed -= 64;
        }

        if ($value_parsed %256 == 128)
        {
            $type[7] = 'hardware-fast-copy-collation';
            $value_parsed -= 128;
        }

        if ($value_parsed %512 == 256)
        {
            $type[8] = 'punch-output';
            $value_parsed -= 256;
        }

        if ($value_parsed %1024 == 512)
        {
            $type[9] = 'cover-output';
            $value_parsed -= 512;
        }

        if ($value_parsed %2048 == 1024)
        {
            $type[10] = 'bind-output';
            $value_parsed -= 1024;
        }

        if ($value_parsed %4096 == 2048)
        {
            $type[11] = 'sort-output';
            $value_parsed -= 2048;
        }

        if ($value_parsed %8192 == 4096)
        {
            $type[12] = 'handle-media-up-to-US-Legal-A4';
            $value_parsed -= 4096;
        }

        if ($value_parsed %16384 == 8192)
        {
            $type[13] = 'handle-media-between-US-Legal-A4-and-ISO_C-A2';
            $value_parsed -= 8192;
        }

        if ($value_parsed %32768 == 16384)
        {
            $type[14] = 'handle-media-larger-than-ISO_C-A2';
            $value_parsed -= 16384;
        }

        if ($value_parsed %65536 == 32768)
        {
            $type[15] = 'handle-user-defined-media-sizes';
            $value_parsed -= 32768;
        }

        if ($value_parsed %131072 == 65536)
        {
            $type[16] = 'implicit-server-generated-class';
            $value_parsed -= 65536;
        }

        if ($value_parsed %262144 == 131072)
        {
            $type[17] = 'network-default-printer';
            $value_parsed -= 131072;
        }

        if ($value_parsed %524288 == 262144)
        {
            $type[18] = 'fax-device';
            $value_parsed -= 262144;
        }

        return $type;
    }


    protected function _interpretEnum($attribute_name,$value)
    {
        $value_parsed = self::_interpretInteger($value);

        switch ($attribute_name)
        {
            case 'cpi':
            case 'lpi':
                $value = $value_parsed;
                break;
            default:
                $value = parent::_interpretEnum($attribute_name,$value);
                break;
        }

        return $value;
    }
}