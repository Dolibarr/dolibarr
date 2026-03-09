<?php

/* @(#) $Header: /sources/phpprintipp/phpprintipp/php_classes/ExtendedPrintIPP.php,v 1.1 2008/06/21 00:30:57 harding Exp $
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

    This class is intended to implement non required operations of 
    Internet Printing Protocol on client side.

    References needed to debug / add functionnalities:
        - RFC 2910
        - RFC 2911
        - RFC 3380
        - RFC 3995
        - RFC 3996
        - RFC 3997
        - RFC 3998
        - ...
*/

require_once("PrintIPP.php");

class ExtendedPrintIPP extends PrintIPP
{
    public function __construct()
    {
        parent::__construct();
    }


    // OPERATIONS
    public function printURI ($uri)
    {

        self::_putDebug( sprintf("*************************\nDate: %s\n*************************\n\n",date('Y-m-d H:i:s')));

        if (!empty($uri))
        {
            $this->document_uri = $uri;
            $this->setup->uri = 1;
        }

        if(!$this->_stringUri())
        {
            return FALSE;
        }

        $this->output = $this->stringjob;

        $post_values = array( "Content-Type" => "application/ipp",
                              "Data" => $this->output);

        if (self::_sendHttp ($post_values,$this->paths['printers']))
        {
            if(self::_parseServerOutput())
            {
                $this->_parseJobAttributes();
                $this->_getJobId();
                //$this->_getPrinterUri();
                $this->_getJobUri();
                }
            }

        $this->attributes = &$this->job_attributes;

        if (isset($this->serveroutput) && isset($this->serveroutput->status))
        {
            $this->status = array_merge($this->status,array($this->serveroutput->status));
            if ($this->serveroutput->status == "successfull-ok")
            {
                self::_errorLog(sprintf("printing uri %s, job %s: ",$uri,$this->last_job)
                            .$this->serveroutput->status,3);
            }
            else
            {
                $this->jobs = array_merge($this->jobs,array(""));
                $this->jobs_uri = array_merge($this->jobs_uri,array(""));
                self::_errorLog(sprintf("printing uri %s: ",$uri,$this->last_job)
                            .$this->serveroutput->status,1);
            }
            return $this->serveroutput->status; 
        }

        $this->status = array_merge($this->status,array("OPERATION FAILED"));
        self::_errorLog("printing uri $uri : OPERATION FAILED",1);

        return false;
    }


    public function purgeJobs()
    {
        $this->jobs = array_merge($this->jobs,array(""));
        $this->jobs_uri = array_merge($this->jobs_uri,array(""));

        self::_setOperationId();
        $this->parsed = array();
        unset($this->printer_attributes);

        if (!isset($this->setup->uri))
        {
            $this->getPrinters();
            unset($this->jobs[count($this->jobs) - 1]);
            unset($this->jobs_uri[count($this->jobs_uri) - 1]);
            unset($this->status[count($this->status) - 1]);

            if (array_key_exists(0,$this->available_printers))
            {
               self::setPrinterURI($this->available_printers[0]);
            }
            else
            {
                trigger_error(_("purgeJobs: Printer URI is not set: die"),E_USER_WARNING);
                self::_putDebug( _("purgeJobs: Printer URI is not set: die\n"));
                self::_errorLog("purgeJobs: Printer URI is not set, die",2);
                return FALSE;
                }
            }

        if (!isset($this->setup->charset))
        {
            self::setCharset();
        }

        if (!isset($this->setup->language))
        {
            self::setLanguage('en_us');
        }

        if (!isset($this->meta->username))
        {
            self::setUserName();
        }

        $this->stringjob = chr(0x01) . chr(0x01) // 1.1  | version-number
                         . chr(0x00) . chr (0x12) // purge-Jobs | operation-id
                         . $this->meta->operation_id //           request-id
                         . chr(0x01) // start operation-attributes | operation-attributes-tag
                         . $this->meta->charset
                         . $this->meta->language
                         . $this->meta->printer_uri
                         . $this->meta->username
                         . chr(0x22)
                         . self::_giveMeStringLength("purge-jobs")
                         . "purge-jobs"
                         . self::_giveMeStringLength(chr(0x01))
                         . chr(0x01)
                         . chr(0x03); // end-of-attributes | end-of-attributes-tag

        self::_putDebug(sprintf(_("String sent to the server is:\n%s\n"), $this->stringjob));

        self::_putDebug(sprintf(_("purging jobs of %s\n"),$this->printer_uri)); 

        $this->output = $this->stringjob;

        $post_values = array( "Content-Type"=>"application/ipp",
                              "Data"=>$this->output);

        if (self::_sendHttp ($post_values,$this->paths['admin']))
        {
            self::_parseServerOutput();
            self::_parseAttributes();
            }

        if (isset($this->serveroutput) && isset($this->serveroutput->status))
        {
            $this->status = array_merge($this->status,array($this->serveroutput->status));

            if ($this->serveroutput->status == "successfull-ok")
            {
                self::_errorLog(sprintf(_("purging jobs of %s: "),$this->printer_uri)
                            .$this->serveroutput->status,3);
            }
            else
            {
                 self::_errorLog(sprintf(_("purging jobs of %s: "),$this->printer_uri)
                                             .$this->serveroutput->status,1);
            }

            return $this->serveroutput->status; 
        }

        $this->status = array_merge($this->status,array("OPERATION FAILED"));
        self::_errorLog(date("Y-m-d H:i:s : ")
                        .basename($_SERVER['PHP_SELF'])
                        .sprintf(_("purging jobs of %s : OPERATION FAILED"),
                                     $this->printer_uri),3);

        return false;
    }

    public function createJob()
    {
        self::_setOperationId();
        $this->parsed = array();
        unset($this->printer_attributes);

        if (!isset($this->setup->uri))
        {
            $this->getPrinters();
            unset($this->jobs[count($this->jobs) - 1]);
            unset($this->jobs_uri[count($this->jobs_uri) - 1]);
            unset($this->status[count($this->status) - 1]);

            if (array_key_exists(0,$this->available_printers))
            {
               self::setPrinterURI($this->available_printers[0]);
            }
            else
            {
                trigger_error(_("createJob: Printer URI is not set: die"),E_USER_WARNING);
                self::_putDebug( _("createJob: Printer URI is not set: die\n"));
                self::_errorLog("createJob: Printer URI is not set, die",2);
                return FALSE;
                }
            }

        if (!isset($this->setup->charset))
        {
            self::setCharset();
        }

        if (!isset($this->setup->language))
        {
            self::setLanguage('en_us');
        }

        if (!isset($this->meta->username))
        {
            self::setUserName();
        }

        if (!isset($this->setup->copies))
        {
            self::setCopies(1);
        }

        if (!isset($this->meta->fidelity))
        {
            $this->meta->fidelity = '';
        }
 
        if (!isset($this->meta->sides))
        {
            $this->meta->sides = '';
        }

        if (!isset($this->meta->page_ranges))
        {
            $this->meta->page_ranges = '';
        }

        if (!isset($this->setup->jobname))
        {
            if (is_readable($this->data))
            {
                self::setJobName(basename($this->data),true);
            }
            else
            {
                self::setJobName();
            }
        }
        unset($this->setup->jobname);

        if (!isset($this->timeout))
        {
            $this->timeout = 60;
        }

        $timeout = self::_integerBuild($this->timeout);
        
        $this->meta->timeout = chr(0x21) // integer
                             . self::_giveMeStringLength("multiple-operation-time-out")
                             . "multiple-operation-time-out"
                             . self::_giveMeStringLength($timeout)
                             . $timeout;

        $jobattributes = '';
        $operationattributes = '';
        $printerattributes = '';
        self::_buildValues($operationattributes,$jobattributes,$printerattributes);

        $this->stringjob = chr(0x01) . chr(0x01) // 1.1  | version-number
                         . chr(0x00) . chr (0x05) // Create-Job | operation-id
                         . $this->meta->operation_id //           request-id
                         . chr(0x01) // start operation-attributes | operation-attributes-tag
                         . $this->meta->charset
                         . $this->meta->language
                         . $this->meta->printer_uri
                         . $this->meta->username
                         . $this->meta->jobname
                         . $this->meta->fidelity
                         . $this->meta->timeout
                         . $operationattributes
                         . chr(0x02) // start job-attributes | job-attributes-tag
                         . $this->meta->copies
                         . $this->meta->sides
                         . $this->meta->page_ranges
                         . $jobattributes
                         . chr(0x03); // end-of-attributes | end-of-attributes-tag

        unset ($this->meta->copies,$this->meta->sides,$this->meta->page_ranges);

        self::_putDebug(sprintf(_("String sent to the server is:\n%s\n"), $this->stringjob));

        self::_putDebug(sprintf(_("creating job %s, printer %s\n"),$this->last_job,$this->printer_uri)); 

        $this->output = $this->stringjob;

        $post_values = array( "Content-Type"=>"application/ipp",
                              "Data"=>$this->output);

        if (self::_sendHttp ($post_values,$this->paths['printers']))
        {
            if(self::_parseServerOutput())
            {
                $this->_getJobId();
                $this->_getJobUri();
                $this->_parseJobAttributes();
            }
            else
            {
                $this->jobs = array_merge($this->jobs,array(''));
                $this->jobs_uri = array_merge($this->jobs_uri,array(''));
            }
        }

        if (isset($this->serveroutput) && isset($this->serveroutput->status))
        {
            $this->status = array_merge($this->status,array($this->serveroutput->status));

            if ($this->serveroutput->status == "successfull-ok")
            {
                self::_errorLog(sprintf(_("Create job: job %s"),$this->last_job)
                            .$this->serveroutput->status,3);
            }
            else
            {

                $this->jobs = array_merge($this->jobs,array(""));
                $this->jobs_uri = array_merge($this->jobs_uri,array(""));
                self::_errorLog(sprintf(_("Create-Job: %s"),$this->serveroutput->status),1);
                }                             
            return $this->serveroutput->status; 
            }    

        $this->status = array_merge($this->status,array("OPERATION FAILED"));
        self::_errorLog(date("Y-m-d H:i:s : ")
                        .basename($_SERVER['PHP_SELF'])
                        .sprintf(_("Creating job on %s : OPERATION FAILED"),
                                     $this->printer_uri),3);

        $this->jobs = array_merge($this->jobs,array(""));
        $this->jobs_uri = array_merge($this->jobs_uri,array(""));
        return false;
    }

    public function sendDocument($job,$is_last=false)
    {
        self::_putDebug( sprintf("*************************\nDate: %s\n*************************\n\n",date('Y-m-d H:i:s')));

        if (!$this->_stringDocument($job,$is_last))
        {
            return FALSE;
        }

        if (is_readable($this->data))
        {
            self::_putDebug( _("sending Document\n")); 

            $this->output = $this->stringjob;

            if ($this->setup->datatype == "TEXT")
            {
                $this->output .= chr(0x16);
            } // ASCII "SYN"

            $post_values = array( "Content-Type" => "application/ipp",
                                  "Data" => $this->output,
                                  "File" => $this->data);

            if ($this->setup->datatype == "TEXT" && !isset($this->setup->noFormFeed))
            {
                $post_values = array_merge($post_values,array("Filetype"=>"TEXT"));
            }
        }
        else
        {
            self::_putDebug( _("sending DATA as document\n")); 

            $this->output = $this->stringjob;
            $this->output .= $this->datahead;    
            $this->output .= $this->data;
            $this->output .= $this->datatail;

            $post_values = array( "Content-Type" => "application/ipp",
                                  "Data" => $this->output);
        }

        if (self::_sendHttp ($post_values,$this->paths['printers']))
        {

            if(self::_parseServerOutput())
            {
                $this->_getJobId();
                //$this->_getPrinterUri();
                $this->_getJobUri();
                $this->_parseJobAttributes();
            }
            else
            {
                $this->jobs = array_merge($this->jobs,array($job));
                $this->jobs_uri = array_merge($this->jobs_uri,array($job));
                }
            }
        
        if (isset($this->serveroutput) && isset($this->serveroutput->status))
        {
            $this->status = array_merge($this->status,array($this->serveroutput->status));

            if ($this->serveroutput->status == "successfull-ok")
            {
                self::_errorLog(sprintf("sending document, job %s: %s",$job,$this->serveroutput->status),3);
            }
            else
            {
                $this->jobs = array_merge($this->jobs,array(""));
                $this->jobs_uri = array_merge($this->jobs_uri,array(""));
                self::_errorLog(sprintf("sending document, job %s: %s",$job,$this->serveroutput->status),1);
            }
            return $this->serveroutput->status; 
        }

        $this->status = array_merge($this->status,array("OPERATION FAILED"));
        $this->jobs = array_merge($this->jobs,array($job));
        $this->jobs_uri = array_merge($this->jobs_uri,array($job));
        self::_errorLog(sprintf("sending document, job %s : OPERATION FAILED",$job),1);

        return false;
    }


    public function sendURI ($uri,$job,$is_last=false)
    {
        self::_putDebug( sprintf("*************************\nDate: %s\n*************************\n\n",date('Y-m-d H:i:s')));

        if (!$this->_stringSendUri($uri,$job,$is_last))
        {
            return FALSE;
        }

            self::_putDebug( _("sending URI $uri\n")); 

            $this->output = $this->stringjob;

            $post_values = array( "Content-Type" => "application/ipp",
                                  "Data" => $this->output);

        if (self::_sendHttp ($post_values,$this->paths['printers']))
        {
            if(self::_parseServerOutput())
            {
                $this->_getJobId();
                //$this->_getPrinterUri();
                $this->_getJobUri();
                $this->_parseJobAttributes();
            }
            else
            {
                $this->jobs = array_merge($this->jobs,array($job));
                $this->jobs_uri = array_merge($this->jobs_uri,array($job));
                }
            }

        $this->attributes = &$this->job_attributes;

        if (isset($this->serveroutput) && isset($this->serveroutput->status))
        {
            $this->status = array_merge($this->status,array($this->serveroutput->status));

            if ($this->serveroutput->status == "successfull-ok")
            {
                self::_errorLog(sprintf("sending uri %s, job %s: %s",$uri,$job,$this->serveroutput->status),3);
            }
            else
            {
                $this->jobs = array_merge($this->jobs,array(""));
                $this->jobs_uri = array_merge($this->jobs_uri,array(""));
                self::_errorLog(sprintf("sending uri, job %s: %s",$uri,$job,$this->serveroutput->status),1);
            }
            return $this->serveroutput->status; 
        }

        $this->status = array_merge($this->status,array("OPERATION FAILED"));
        $this->jobs = array_merge($this->jobs,array($job));
        $this->jobs_uri = array_merge($this->jobs_uri,array($job));
        self::_errorLog(sprintf("sending uri %s, job %s : OPERATION FAILED",$uri,$job),1);

        return false;
    }


    public function pausePrinter()
    {
        $this->jobs = array_merge($this->jobs,array(""));
        $this->jobs_uri = array_merge($this->jobs_uri,array(""));

        self::_setOperationId();
        $this->parsed = array();
        unset($this->printer_attributes);

        if (!isset($this->setup->uri))
        {
            $this->getPrinters();
            unset($this->jobs[count($this->jobs) - 1]);
            unset($this->jobs_uri[count($this->jobs_uri) - 1]);
            unset($this->status[count($this->status) - 1]);

            if (array_key_exists(0,$this->available_printers))
            {
               self::setPrinterURI($this->available_printers[0]);
            }
            else
            {
                trigger_error(_("pausePrinter: Printer URI is not set: die"),E_USER_WARNING);
                self::_putDebug( _("pausePrinter: Printer URI is not set: die\n"));
                self::_errorLog("pausePrinter: Printer URI is not set, die",2);
                return FALSE;
            }
        }

        if (!isset($this->setup->charset))
        {
            self::setCharset();
        }

        if (!isset($this->setup->language))
        {
            self::setLanguage('en_us');
        }

        if (!isset($this->meta->username))
        {
            self::setUserName();
        }

        $this->stringjob = chr(0x01) . chr(0x01) // 1.1  | version-number
                         . chr(0x00) . chr (0x10) // Pause-Printer | operation-id
                         . $this->meta->operation_id //           request-id
                         . chr(0x01) // start operation-attributes | operation-attributes-tag
                         . $this->meta->charset
                         . $this->meta->language
                         . $this->meta->printer_uri
                         . $this->meta->username
                         /* . chr(0x22)
                         . self::_giveMeStringLength("purge-jobs")
                         . "purge-jobs"
                         . self::_giveMeStringLength(chr(0x01))
                         . chr(0x01) */
                         . chr(0x03); // end-of-attributes | end-of-attributes-tag

        self::_putDebug(sprintf(_("String sent to the server is:\n%s\n"), $this->stringjob));

        self::_putDebug(sprintf(_("pause printer %s\n"),$this->printer_uri)); 

        $this->output = $this->stringjob;

        $post_values = array( "Content-Type"=>"application/ipp",
                              "Data"=>$this->output);

        if (self::_sendHttp ($post_values,$this->paths['admin']))
        {
            self::_parseServerOutput();
            self::_parseAttributes();
        }

        if (isset($this->serveroutput) && isset($this->serveroutput->status))
        {
            $this->status = array_merge($this->status,array($this->serveroutput->status));

            if ($this->serveroutput->status == "successfull-ok")
            {
                self::_errorLog(sprintf(_("Pause printer %s: "),$this->printer_uri)
                            .$this->serveroutput->status,3);
            }
            else
            {
                 self::_errorLog(sprintf(_("pause printer %s: "),$this->printer_uri)
                                             .$this->serveroutput->status,1);
            }

            return $this->serveroutput->status; 
        }

        $this->status = array_merge($this->status,array("OPERATION FAILED"));
        self::_errorLog(date("Y-m-d H:i:s : ")
                        .basename($_SERVER['PHP_SELF'])
                        .sprintf(_("pause printer %s : OPERATION FAILED"),
                                     $this->printer_uri),3);

        return false;
    }


    public function resumePrinter()
    {
        $this->jobs = array_merge($this->jobs,array(""));
        $this->jobs_uri = array_merge($this->jobs_uri,array(""));

        self::_setOperationId();
        $this->parsed = array();
        unset($this->printer_attributes);

        if (!isset($this->setup->uri))
        {
            $this->getPrinters();
            unset($this->jobs[count($this->jobs) - 1]);
            unset($this->jobs_uri[count($this->jobs_uri) - 1]);
            unset($this->status[count($this->status) - 1]);

            if (array_key_exists(0,$this->available_printers))
            {
               self::setPrinterURI($this->available_printers[0]);
            }
            else
            {
                trigger_error(_("resumePrinter: Printer URI is not set: die"),E_USER_WARNING);
                self::_putDebug( _("resumePrinter: Printer URI is not set: die\n"));
                self::_errorLog(" Printer URI is not set, die",2);
                return FALSE;
            }
        }

        if (!isset($this->setup->charset))
        {
            self::setCharset();
        }

        if (!isset($this->setup->language))
        {
            self::setLanguage('en_us');
        }

        if (!isset($this->meta->username))
        {
            self::setUserName();
        }

        $this->stringjob = chr(0x01) . chr(0x01) // 1.1  | version-number
                         . chr(0x00) . chr (0x11) // suse-Printer | operation-id
                         . $this->meta->operation_id //           request-id
                         . chr(0x01) // start operation-attributes | operation-attributes-tag
                         . $this->meta->charset
                         . $this->meta->language
                         . $this->meta->printer_uri
                         . $this->meta->username
                         . chr(0x03); // end-of-attributes | end-of-attributes-tag

        self::_putDebug(sprintf(_("String sent to the server is:\n%s\n"), $this->stringjob));

        self::_putDebug(sprintf(_("resume printer %s\n"),$this->printer_uri)); 

        $this->output = $this->stringjob;

        $post_values = array( "Content-Type"=>"application/ipp",
                              "Data"=>$this->output);

        if (self::_sendHttp ($post_values,$this->paths['admin']))
        {
            self::_parseServerOutput();
            self::_parseAttributes();
        }

        if (isset($this->serveroutput) && isset($this->serveroutput->status))
        {
            $this->status = array_merge($this->status,array($this->serveroutput->status));

            if ($this->serveroutput->status == "successfull-ok")
            {
                self::_errorLog(sprintf(_("resume printer %s: "),$this->printer_uri)
                            .$this->serveroutput->status,3);
            }
            else
            {
                 self::_errorLog(sprintf(_("resume printer %s: "),$this->printer_uri)
                                             .$this->serveroutput->status,1);
            }

            return $this->serveroutput->status; 
        }

        $this->status = array_merge($this->status,array("OPERATION FAILED"));
        self::_errorLog(date("Y-m-d H:i:s : ")
                        .basename($_SERVER['PHP_SELF'])
                        .sprintf(_("resume printer %s : OPERATION FAILED"),
                                     $this->printer_uri),3);

        return false;
    }


    public function holdJob ($job_uri,$until='indefinite')
    {
        $this->jobs = array_merge($this->jobs,array(""));
        $this->jobs_uri = array_merge($this->jobs_uri,array(trim($job_uri)));

        self::_setOperationId();
        $this->parsed = array();
        unset($this->printer_attributes);

        if (!isset($this->setup->charset))
        {
            self::setCharset();
        }

        if (!isset($this->setup->language))
        {
            self::setLanguage('en_us');
        }

        if (!isset($this->meta->username))
        {
            self::setUserName();
        }

        if (!isset($this->meta->message))
        {
            $this->meta->message = '';
        }

        self::_setJobUri($job_uri);
        
        $until_strings = array('no-hold','day-time','evening','night','weekend','second-shift','third-shift');
        if (in_array($until,$until_strings))
        {
            true;
        }
        else 
        {
            $until = 'indefinite';
        }

        $this->meta->job_hold_until = chr(0x42) // keyword
                                    . self::_giveMeStringLength('job-hold-until')
                                    . 'job-hold-until'
                                    . self::_giveMeStringLength($until)
                                    . $until;

        $this->stringjob = chr(0x01) . chr(0x01) // 1.1  | version-number
                         . chr(0x00) . chr (0x0C) // Hold-Job | operation-id
                         . $this->meta->operation_id //           request-id
                         . chr(0x01) // start operation-attributes | operation-attributes-tag
                         . $this->meta->charset
                         . $this->meta->language
                         . $this->meta->username
                         . $this->meta->job_uri
                         . $this->meta->message
                         . $this->meta->job_hold_until
                         . chr(0x03); // end-of-attributes | end-of-attributes-tag

        self::_putDebug(sprintf(_("String sent to the server is:\n%s\n"), $this->stringjob));

        self::_putDebug(sprintf(_("hold job %s until %s\n"),$job_uri,$until)); 

        $this->output = $this->stringjob;

        $post_values = array( "Content-Type"=>"application/ipp",
                              "Data"=>$this->output);

        if (self::_sendHttp ($post_values,$this->paths['jobs']))
        {
            self::_parseServerOutput();
            self::_parseAttributes();
            }
        if (isset($this->serveroutput) && isset($this->serveroutput->status))
        {
            
            $this->status = array_merge($this->status,array($this->serveroutput->status));
            
            if ($this->serveroutput->status == "successfull-ok")
            {
                self::_errorLog(sprintf(_("hold job %s until %s: "),$job_uri,$until)
                            .$this->serveroutput->status,3);
            }
            else
            {
                 self::_errorLog(sprintf(_("hold job %s until %s: "),$job_uri,$until)
                                             .$this->serveroutput->status,1);
            }

            return $this->serveroutput->status; 
        }

        $this->status = array_merge($this->status,array("OPERATION FAILED"));
        self::_errorLog(date("Y-m-d H:i:s : ")
                        .basename($_SERVER['PHP_SELF'])
                        .sprintf(_("hold job %s until %s : OPERATION FAILED"),
                                     $job_uri,$until),3);

        return false;
    }
    

    public function releaseJob ($job_uri)
    {
        $this->jobs = array_merge($this->jobs,array(""));
        $this->jobs_uri = array_merge($this->jobs_uri,array(trim($job_uri)));

        self::_setOperationId();
        $this->parsed = array();
        unset($this->printer_attributes);

        if (!isset($this->setup->charset))
        {
            self::setCharset();
        }

        if (!isset($this->setup->language))
        {
            self::setLanguage('en_us');
        }

        if (!isset($this->meta->username))
        {
            self::setUserName();
        }

        if (!isset($this->meta->message))
        {
            $this->meta->message = '';
        }

        self::_setJobUri($job_uri);

        $this->stringjob = chr(0x01) . chr(0x01) // 1.1  | version-number
                         . chr(0x00) . chr (0x0D) // Hold-Job | operation-id
                         . $this->meta->operation_id //           request-id
                         . chr(0x01) // start operation-attributes | operation-attributes-tag
                         . $this->meta->charset
                         . $this->meta->language
                         . $this->meta->job_uri
                         . $this->meta->username
                         . $this->meta->message
                         . chr(0x03); // end-of-attributes | end-of-attributes-tag

        self::_putDebug(sprintf(_("String sent to the server is:\n%s\n"), $this->stringjob));

        self::_putDebug(sprintf(_("release job %s\n"),$job_uri)); 

        $this->output = $this->stringjob;

        $post_values = array( "Content-Type"=>"application/ipp",
                              "Data"=>$this->output);

        if (self::_sendHttp ($post_values,$this->paths['jobs']))
        {
            self::_parseServerOutput();
            self::_parseAttributes();
        }
        if (isset($this->serveroutput) && isset($this->serveroutput->status))
        {
            $this->status = array_merge($this->status,array($this->serveroutput->status));
            
            if ($this->serveroutput->status == "successfull-ok")
            {
                self::_errorLog(sprintf(_("release job %s: "),$job_uri)
                            .$this->serveroutput->status,3);
            }
            else
            {
                 self::_errorLog(sprintf(_("release job %s: "),$job_uri)
                                             .$this->serveroutput->status,1);
            }

            return $this->serveroutput->status; 
        }

        $this->status = array_merge($this->status,array("OPERATION FAILED"));
        self::_errorLog(date("Y-m-d H:i:s : ")
                        .basename($_SERVER['PHP_SELF'])
                        .sprintf(_("release job %s: OPERATION FAILED"),
                                     $job_uri),3);

        return false;
    }


    public function restartJob ($job_uri)
    {
        $this->jobs = array_merge($this->jobs,array(""));
        $this->jobs_uri = array_merge($this->jobs_uri,array(trim($job_uri)));

        self::_setOperationId();
        $this->parsed = array();
        unset($this->printer_attributes);

        if (!isset($this->setup->charset))
        {
            self::setCharset();
        }

        if (!isset($this->setup->language))
        {
            self::setLanguage('en_us');
        }

        if (!isset($this->meta->username))
        {
            self::setUserName();
        }

        if (!isset($this->meta->message))
        {
            $this->meta->message = '';
        }

        self::_setJobUri($job_uri);

        $jobattributes = '';
        $operationattributes = '';
        $printerattributes = '';
        self::_buildValues ($operationattributes,$jobattributes,$printerattributes); 
 
        $this->stringjob = chr(0x01) . chr(0x01) // 1.1  | version-number
                         . chr(0x00) . chr (0x0E) // Hold-Job | operation-id
                         . $this->meta->operation_id //           request-id
                         . chr(0x01) // start operation-attributes | operation-attributes-tag
                         . $this->meta->charset
                         . $this->meta->language
                         . $this->meta->job_uri
                         . $this->meta->username
                         . $this->meta->message
                         . $jobattributes // job-hold-until is set by setAttribute($attribute,$value)
                         . chr(0x03); // end-of-attributes | end-of-attributes-tag

        self::_putDebug(sprintf(_("String sent to the server is:\n%s\n"), $this->stringjob));

        self::_putDebug(sprintf(_("release job %s\n"),$job_uri)); 

        $this->output = $this->stringjob;

        $post_values = array( "Content-Type"=>"application/ipp",
                              "Data"=>$this->output);

        if (self::_sendHttp ($post_values,$this->paths['jobs']))
        {
            self::_parseServerOutput();
            self::_parseAttributes();
        }

        if (isset($this->serveroutput) && isset($this->serveroutput->status))
        {
            $this->status = array_merge($this->status,array($this->serveroutput->status));

            if ($this->serveroutput->status == "successfull-ok")
            {
                self::_errorLog(sprintf(_("release job %s: "),$job_uri)
                            .$this->serveroutput->status,3);
            }
            else
            {
                 self::_errorLog(sprintf(_("release job %s: "),$job_uri)
                                             .$this->serveroutput->status,1);
            }

            return $this->serveroutput->status; 
        }

        $this->status = array_merge($this->status,array("OPERATION FAILED"));
        self::_errorLog(date("Y-m-d H:i:s : ")
                        .basename($_SERVER['PHP_SELF'])
                        .sprintf(_("release job %s: OPERATION FAILED"),
                                     $job_uri),3);

        return false;
    }


    public function setJobAttributes ($job_uri,$deleted_attributes=array())
    {
        $this->jobs = array_merge($this->jobs,array(""));
        $this->jobs_uri = array_merge($this->jobs_uri,array(trim($job_uri)));

        self::_setOperationId();
        $this->parsed = array();
        unset ($this->attributes);

        if (!isset($this->setup->charset))
        {
            self::setCharset();
        }

        if (!isset($this->setup->language))
        {
            self::setLanguage('en_us');
        }

        if (!isset($this->meta->username))
        {
            self::setUserName();
        }

        if (!isset($this->meta->message))
        {
            $this->meta->message = '';
        }

        if (!isset($this->meta->copies))
        {
            $this->meta->copies = '';
        }

        if (!isset($this->meta->sides))
        {
            $this->meta->sides = '';
        }

        if (!isset($this->meta->page_ranges))
        {
            $this->meta->page_ranges = '';
        }
  
        self::_setJobUri($job_uri);

        $operationattributes = '';
        $jobattributes = '';
        $printerattributes = '';
        self::_buildValues ($operationattributes,$jobattributes,$printerattributes); 

        $this->meta->deleted_attributes = "";
        for ($i = 0 ; $i < count($deleted_attributes) ; $i++)
        {
            $this->meta->deleted_attributes .= chr(0x16) // out-of-band value
                . self::_giveMeStringLength($deleted_attributes[$i])
                . $deleted_attributes[$i]
                . chr(0x0).chr(0x0);
        } // value-length = 0;

        $this->stringjob = chr(0x01) . chr(0x01) // 1.1  | version-number
                         . chr(0x00) . chr (0x14) // Set-Job-Attributes | operation-id
                         . $this->meta->operation_id //           request-id
                         . chr(0x01) // start operation-attributes | operation-attributes-tag
                         . $this->meta->charset
                         . $this->meta->language
                         . $this->meta->job_uri
                         . $this->meta->username
                         . $this->meta->message
                         . chr(0x02) // start job-attributes
                         . $jobattributes // setteds by setAttribute($attribute,$value)
                         . $this->meta->copies
                         . $this->meta->sides
                         . $this->meta->page_ranges
                         . $this->meta->deleted_attributes
                         . chr(0x03); // end-of-attributes | end-of-attributes-tag

        self::_putDebug(sprintf(_("String sent to the server is:\n%s\n"), $this->stringjob));

        self::_putDebug(sprintf(_("set job attributes for job %s\n"),$job_uri)); 

        $this->output = $this->stringjob;

        $post_values = array( "Content-Type"=>"application/ipp",
                              "Data"=>$this->output);

        if (self::_sendHttp ($post_values,$this->paths['jobs']))
        {
            self::_parseServerOutput();
            self::_parseAttributes();
        }
        if (isset($this->serveroutput) && isset($this->serveroutput->status))
        {
            $this->status = array_merge($this->status,array($this->serveroutput->status));

            if ($this->serveroutput->status == "successfull-ok")
            {
                self::_errorLog(sprintf(_("set job attributes for job %s: "),$job_uri)
                            .$this->serveroutput->status,3);
            }
            else
            {
                 self::_errorLog(sprintf(_("set job attributes for job %s: "),$job_uri)
                                             .$this->serveroutput->status,1);
            }
            $this->last_job = $job_uri;
            $this->jobs_uri[count($this->jobs_uri) - 1] = $job_uri;
            return $this->serveroutput->status; 
        }

        $this->status = array_merge($this->status,array("OPERATION FAILED"));
        self::_errorLog(date("Y-m-d H:i:s : ")
                        .basename($_SERVER['PHP_SELF'])
                        .sprintf(_("set job attributes for job %s: OPERATION FAILED"),
                                     $job_uri),3);

        return false;
    }


    public function setPrinterAttributes ($document_format='',$deleted_attributes=array())
    {
        /* $document_format (RFC 3380)
         If the client includes this attribute, the Printer MUST change
         the supplied attributes for the document format specified by
         this attribute.  If a supplied attribute is a member of the
         "document-format-varying-attributes" (i.e., the attribute
         varies by document format, see section 6.3), the Printer MUST
         change the supplied attribute for the document format specified
         by this attribute, but not for other document formats.  If a
         supplied attribute isn't a member of the "document-format-
         varying-attributes" (i.e., it doesn't vary by document format),
         the Printer MUST change the supplied attribute for all document
         formats.

         If the client omits this attribute, the Printer MUST change the
         supplied attributes for all document formats, whether or not
         they vary by document-format.
         */

        $this->jobs = array_merge($this->jobs,array(""));
        $this->jobs_uri = array_merge($this->jobs_uri,array(""));
        unset ($this->attributes);

        self::_setOperationId();
        $this->parsed = array();

        if (!isset($this->setup->charset))
        {
            self::setCharset();
        }

        if (!isset($this->setup->language))
        {
            self::setLanguage('en_us');
        }

        if (!isset($this->meta->username))
        {
            self::setUserName();
        }

        if (!isset($this->meta->message))
        {
            $this->meta->message = '';
        }

        if (!isset($this->meta->copies))
        {
            $this->meta->copies = '';
        }

        if (!isset($this->meta->sides))
        {
            $this->meta->sides = '';
        }

        if (!isset($this->meta->page_ranges))
        {
            $this->meta->page_ranges = '';
        }

        if ($document_format)
        {
        $document_format = chr(0x49) // document-format tag
            . self::_giveMeStringLength('document-format')
            . 'document-format' // 
            . self::_giveMeStringLength($document_format)
            . $document_format;
        } // value

        $operationattributes = '';
        $jobattributes = '';
        $printerattributes = '';
        self::_buildValues ($operationattributes,$jobattributes,$printerattributes); 

        $this->meta->deleted_attributes = "";
        for ($i = 0 ; $i < count($deleted_attributes) ; $i++)
        {
                $this->meta->deleted_attributes .= chr(0x16) // out-of-band "deleted" value
                                                . self::_giveMeStringLength($deleted_attributes[$i])
                                                . $deleted_attributes[$i]
                                                . chr(0x0).chr(0x0);
        } // value-length = 0;

        $this->stringjob = chr(0x01) . chr(0x01) // 1.1  | version-number
                         . chr(0x00) . chr (0x13) // Set-Printer-Attributes | operation-id
                         . $this->meta->operation_id //           request-id
                         . chr(0x01) // start operation-attributes | operation-attributes-tag
                         . $this->meta->charset
                         . $this->meta->language
                         . $this->meta->printer_uri
                         . $this->meta->username
                         . $this->meta->message
                         . $operationattributes
                         . chr(0x02) // start job-attributes
                         . $jobattributes // setteds by setAttribute($attribute,$value)
                         . $this->meta->copies
                         . $this->meta->sides
                         . $this->meta->page_ranges
                         . $this->meta->deleted_attributes
                         . chr(0x03); // end-of-attributes | end-of-attributes-tag

        self::_putDebug(sprintf(_("String sent to the server is:\n%s\n"), $this->stringjob));

        self::_putDebug(sprintf(_("set printer attributes for job %s\n"),$this->printer_uri)); 

        $this->output = $this->stringjob;

        $post_values = array( "Content-Type"=>"application/ipp",
                              "Data"=>$this->output);

        if (self::_sendHttp ($post_values,$this->paths['printers']))
        {
            self::_parseServerOutput();
            self::_parseAttributes();
        }
        if (isset($this->serveroutput) && isset($this->serveroutput->status))
        {
            $this->status = array_merge($this->status,array($this->serveroutput->status));

            if ($this->serveroutput->status == "successfull-ok")
            {
                self::_errorLog(sprintf(_("set printer attributes for printer %s: "),$this->printer_uri)
                            .$this->serveroutput->status,3);
            }
            else
            {
                 self::_errorLog(sprintf(_("set printer attributes for printer %s: "),$this->printer_uri)
                                             .$this->serveroutput->status,1);
            }

            return $this->serveroutput->status; 
        }

        $this->status = array_merge($this->status,array("OPERATION FAILED"));
        self::_errorLog(date("Y-m-d H:i:s : ")
                        .basename($_SERVER['PHP_SELF'])
                        .sprintf(_("set printer attributes for printer %s: OPERATION FAILED"),
                                     $this->printer_uri),1);

        return false;
    }

    // REQUEST BUILDING
    protected function _setDocumentUri ()
    {
        $this->meta->document_uri = chr(0x45) // type uri
                                . chr(0x00).chr(0x0c) // name-length
                                . "document-uri"
                                . self::_giveMeStringLength($this->document_uri)
                                . $this->document_uri;

        self::_putDebug( "document uri is: ".$this->document_uri."\n");
        $this->setup->document_uri = 1;
    }


    protected function _stringUri ()
    {
        self::_setDocumentUri();

        if (!isset($this->setup->document_uri))
        {
            trigger_error(_("_stringUri: Document URI is not set: die"),E_USER_WARNING);
            self::_putDebug( _("_stringUri: Document URI is not set: die\n"));
            self::_errorLog("Document URI is not set, die",2);
            return FALSE;
        }
        unset ($this->setup->document_uri);

        if (!isset($this->setup->uri))
        {
            $this->getPrinters();
            unset($this->jobs[count($this->jobs) - 1]);
            unset($this->jobs_uri[count($this->jobs) - 1]);
            unset($this->status[count($this->status) - 1]);

            if (array_key_exists(0,$this->available_printers))
            {
               self::setPrinterURI($this->available_printers[0]);
            }
            else
            {
                trigger_error(_("_stringUri: Printer URI is not set: die"),E_USER_WARNING);
                self::_putDebug( _("_stringUri: Printer URI is not set: die\n"));
                self::_errorLog("_stringUri: Printer URI is not set, die",2);
                return FALSE;
            }
        }
  
        if (!isset($this->setup->charset))
        {
            $this->meta->charset = "";
        }
        if (!isset($this->setup->datatype))
        {
            self::setBinary();
        }
        if (!isset($this->setup->uri))
        {
            trigger_error(_("_stringUri: Printer URI is not set: die"),E_USER_WARNING);
            self::_putDebug( _("_stringUri: Printer URI is not set: die\n"));
            self::_errorLog("Printer URI is not set, die",2);
            return FALSE;
        }
        if (!isset($this->setup->copies))
        {
            self::setCopies(1);
        }

        if (!isset($this->setup->language))
        {
            self::setLanguage('en_us');
        }

        if (!isset($this->setup->mime_media_type))
        {
            self::setMimeMediaType();
        }
        unset ($this->setup->mime_media_type);

        if (!isset($this->setup->jobname))
        {
            if (is_readable($this->data))
            {
                self::setJobName(basename($this->data),true);
            }
            else
            {
                self::setJobName();
            }
        }
        unset($this->setup->jobname);

        if (!isset($this->meta->username))
        {
            self::setUserName();
        }

        if (!isset($this->meta->fidelity))
        {
            $this->meta->fidelity = '';
        }

        if (!isset($this->meta->document_name))
        {
            $this->meta->document_name = '';
        }

        if (!isset($this->meta->sides))
        {
            $this->meta->sides = '';
        }

        if (!isset($this->meta->page_ranges))
        {
            $this->meta->page_ranges = '';
        }

        $jobattributes = '';
        $operationattributes = '';
        $printerattributes = '';
        self::_buildValues($operationattributes,$jobattributes,$printerattributes);

        self::_setOperationId();

        if (!isset($this->error_generation->request_body_malformed))
        {
            $this->error_generation->request_body_malformed = "";
        }

        $this->stringjob = chr(0x01) . chr(0x01) // 1.1  | version-number
                         . chr(0x00) . chr (0x03) // Print-URI | operation-id
                         . $this->meta->operation_id //           request-id
                         . $this->error_generation->request_body_malformed
                         . chr(0x01) // start operation-attributes | operation-attributes-tag
                         . $this->meta->charset
                         . $this->meta->language
                         . $this->meta->printer_uri
                         . $this->meta->jobname
                         . $this->meta->username
                         . $this->meta->fidelity
                         . $this->meta->document_name
                         . $this->meta->document_uri
                         . $operationattributes
                         . chr(0x02) // start job-attributes | job-attributes-tag
                         . $this->meta->copies
                         . $this->meta->sides
                         . $this->meta->page_ranges
                         . $jobattributes
                         . chr(0x03); // end-of-attributes | end-of-attributes-tag

        self::_putDebug( sprintf(_("String sent to the server is:\n%s\n"), $this->stringjob));
        return TRUE;
    }


    protected function _stringDocument ($job,$is_last)
    {
        if ($is_last == false)
        {
            $is_last = chr(0x00);
        }
        else
        {
            $is_last = chr(0x01);
        }

        if (!isset($this->setup->charset))
        {
            self::setCharset();
        }
        if (!isset($this->setup->datatype))
        {
            self::setBinary();
        }

        if (!isset($this->setup->uri))
        {
            $this->getPrinters();
            unset($this->jobs[count($this->jobs) - 1]);
            unset($this->jobs_uri[count($this->jobs_uri) - 1]);
            unset($this->status[count($this->status) - 1]);

            if (array_key_exists(0,$this->available_printers))
            {
               self::setPrinterURI($this->available_printers[0]);
            }
            else
            {
                trigger_error(_("_stringJob: Printer URI is not set: die"),E_USER_WARNING);
                self::_putDebug( _("_stringJob: Printer URI is not set: die\n"));
                self::_errorLog(" Printer URI is not set, die",2);
                return FALSE;
                }
            }

        if (!isset($this->setup->copies))
        {
            $this->meta->copies = "";
        }

        if (!isset($this->setup->language))
        {
            self::setLanguage('en_us');
        }

        if (!isset($this->setup->mime_media_type))
        {
            $this->meta->mime_media_type = "";
        }
        if ($this->setup->datatype != "TEXT")
        {
        unset ($this->setup->mime_media_type);
        }

        if (!isset($this->meta->fidelity))
        {
            $this->meta->fidelity = '';
        }

        if (!isset($this->meta->document_name))
        {
            $this->meta->document_name = '';
        }

        if (!isset($this->meta->sides))
        {
            $this->meta->sides = '';
        }

        if (!isset($this->meta->page_ranges))
        {
            $this->meta->page_ranges = '';
        }

        $operationattributes = '';
        $jobattributes = '';
        $printerattributes = '';
        self::_buildValues($operationattributes,$jobattributes,$printerattributes);

        self::_setOperationId();

        $this->stringjob = chr(0x01) . chr(0x01) // 1.1  | version-number
                         . chr(0x00) . chr (0x06) // Send-Document | operation-id
                         . $this->meta->operation_id //           request-id
                         . chr(0x01) // start operation-attributes | operation-attributes-tag
                         . $this->meta->charset
                         . $this->meta->language
                         . chr(0x45) // attribute-type: uri
                         . self::_giveMeStringLength("job-uri")
                         . "job-uri"
                         . self::_giveMeStringLength($job)
                         . $job
                         . $this->meta->username
                         . $this->meta->document_name
                         . $this->meta->fidelity
                         . $this->meta->mime_media_type
                         . $operationattributes
                         . chr(0x22) // boolean
                         . self::_giveMeStringLength("last-document")
                         . "last-document"
                         . self::_giveMeStringLength($is_last)
                         . $is_last
                         . chr(0x03); // end-of-attributes | end-of-attributes-tag

        self::_putDebug( sprintf(_("String sent to the server is:\n%s\n"), $this->stringjob));
        return TRUE;
    }


    protected function _stringSendUri ($uri,$job,$is_last)
    {
        $this->document_uri = $uri;
        self::_setDocumentUri();

        if (!isset($this->setup->document_uri))
        {
            trigger_error(_("_stringUri: Document URI is not set: die"),E_USER_WARNING);
            self::_putDebug( _("_stringUri: Document URI is not set: die\n"));
            self::_errorLog("Document URI is not set, die",2);
            return FALSE;
        }
        unset ($this->setup->document_uri);

        if ($is_last == false)
        {
            $is_last = chr(0x00);
        }
        else
        {
            $is_last = chr(0x01);
        }

        if (!isset($this->setup->charset))
        {
            self::setCharset();
        }
        if (!isset($this->setup->datatype))
        {
            self::setBinary();
        }

        if (!isset($this->setup->uri))
        {
            $this->getPrinters();
            unset($this->jobs[count($this->jobs) - 1]);
            unset($this->jobs_uri[count($this->jobs_uri) - 1]);
            unset($this->status[count($this->status) - 1]);

            if (array_key_exists(0,$this->available_printers))
            {
               self::setPrinterURI($this->available_printers[0]);
            } 
            else
            {
                trigger_error(_("_stringJob: Printer URI is not set: die"),E_USER_WARNING);
                self::_putDebug( _("_stringJob: Printer URI is not set: die\n"));
                self::_errorLog(" Printer URI is not set, die",2);
                return FALSE;
                }
            }

        if (!isset($this->setup->copies))
        {
            $this->meta->copies = "";
        }

        if (!isset($this->setup->language))
        {
            self::setLanguage('en_us');
        }

        if (!isset($this->setup->mime_media_type))
        {
            $this->meta->mime_media_type = "";
        }
        unset ($this->setup->mime_media_type);

        if (!isset($this->meta->fidelity))
        {
            $this->meta->fidelity = '';
        }

        if (!isset($this->meta->document_name))
        {
            $this->meta->document_name = '';
        }

        if (!isset($this->meta->sides))
        {
            $this->meta->sides = '';
        }

        if (!isset($this->meta->page_ranges))
        {
            $this->meta->page_ranges = '';
        }

        $operationattributes = '';
        $jobattributes = '';
        $printerattributes = '';
        self::_buildValues($operationattributes,$jobattributes,$printerattributes);

        self::_setOperationId();

        $this->stringjob = chr(0x01) . chr(0x01) // 1.1  | version-number
                         . chr(0x00) . chr (0x07) // Send-Uri | operation-id
                         . $this->meta->operation_id //           request-id
                         . chr(0x01) // start operation-attributes | operation-attributes-tag
                         . $this->meta->charset
                         . $this->meta->language
                         . chr(0x45) // attribute-type: uri
                         . self::_giveMeStringLength("job-uri")
                         . "job-uri"
                         . self::_giveMeStringLength($job)
                         . $job
                         . $this->meta->username
                         . $this->meta->document_uri
                         . $this->meta->fidelity
                         . $this->meta->mime_media_type
                         . $operationattributes
                         . chr(0x22) // boolean
                         . self::_giveMeStringLength("last-document")
                         . "last-document"
                         . self::_giveMeStringLength($is_last)
                         . $is_last
                         . chr(0x03); // end-of-attributes | end-of-attributes-tag

        self::_putDebug( sprintf(_("String sent to the server is:\n%s\n"), $this->stringjob));
        return TRUE;
    }
}