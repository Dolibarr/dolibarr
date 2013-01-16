<?php
/* Copyright (C) 2000-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *
 * Lots of code inspired from Dan Potter's CSMSFile class
 */

/**
 *      \file       htdocs/core/class/CSMSFile.class.php
 *      \brief      File of class to send sms
 *      \author	    Laurent Destailleur.
 */

/**
 *		Class to send SMS
 *      Usage: $smsfile = new CSMSFile($subject,$sendto,$replyto,$message,$filepath,$mimetype,$filename,$cc,$ccc,$deliveryreceipt,$msgishtml,$errors_to);
 *             $smsfile->sendfile();
 */
class CSMSFile
{
    var $error='';

	var $addr_from;
	var $addr_to;
	var $deferred;
	var $priority;
	var $class;
	var $message;


	/**
	 *	CSMSFile
	 *
	 *	@param	string	$to                 Recipients SMS
	 *	@param 	string	$from               Sender SMS
	 *	@param 	string	$msg                Message
	 *	@param 	int		$deliveryreceipt	Not used
	 *	@param 	int		$deferred			Deferred or not
	 *	@param 	int		$priority			Priority
	 *	@param 	int		$class				Class
	 *	@return	int
	 */
	function __construct($to,$from,$msg,$deliveryreceipt=0,$deferred=0,$priority=3,$class=1)
	{
		global $conf;

		// On definit fin de ligne
		$this->eol="\n";
		if (preg_match('/^win/i',PHP_OS)) $this->eol="\r\n";
		if (preg_match('/^mac/i',PHP_OS)) $this->eol="\r";

		// If ending method not defined
		if (empty($conf->global->MAIN_SMS_SENDMODE))
		{
		    $this->error='No SMS Engine defined';
		    return -1;
		}

		dol_syslog("CSMSFile::CSMSFile: MAIN_SMS_SENDMODE=".$conf->global->MAIN_SMS_SENDMODE." charset=".$conf->file->character_set_client." from=".$from.", to=".$to.", msg length=".count($msg), LOG_DEBUG);
		dol_syslog("CSMSFile::CSMSFile: deferred=".$deferred." priority=".$priority." class=".$class, LOG_DEBUG);

		// Action according to choosed sending method
	    $this->addr_from=$from;
	    $this->addr_to=$to;
        $this->deferred=$deferred;
        $this->priority=$priority;
        $this->class=$class;
        $this->message=$msg;
	}


	/**
	 * Send mail that was prepared by constructor
	 *
	 * @return    boolean     True if mail sent, false otherwise
	 */
	function sendfile()
	{
		global $conf;

		$errorlevel=error_reporting();
		error_reporting($errorlevel ^ E_WARNING);   // Desactive warnings

		$res=false;

        dol_syslog("CSMSFile::sendfile addr_to=".$this->addr_to, LOG_DEBUG);
        dol_syslog("CSMSFile::sendfile message=\n".$this->message);

        $this->message=stripslashes($this->message);

        if (! empty($conf->global->MAIN_SMS_DEBUG)) $this->dump_sms();

		if (empty($conf->global->MAIN_DISABLE_ALL_SMS))
		{
		    // Action according to choosed sending method
		    if ($conf->global->MAIN_SMS_SENDMODE == 'ovh')    // Backward compatibility    @deprecated
			{
				dol_include_once('/ovh/class/ovhsms.class.php');
				$sms=new OvhSms($this->db);
				$sms->expe=$this->addr_from;
				$sms->dest=$this->addr_to;
				$sms->message=$this->message;
				$sms->deferred=$this->deferred;
				$sms->priority=$this->priority;
                $sms->class=$this->class;

                $res=$sms->SmsSend();
				if ($res <= 0)
				{
					$this->error=$sms->error;
					dol_syslog("CSMSFile::sendfile: sms send error=".$this->error, LOG_ERR);
				}
				else
				{
					dol_syslog("CSMSFile::sendfile: sms send success with id=".$res, LOG_DEBUG);
					//var_dump($res);        // 1973128
					$this->dump_sms_result($res);
				}
			}
		    else if (! empty($conf->global->MAIN_SMS_SENDMODE))    // $conf->global->MAIN_SMS_SENDMODE looks like a value 'class@module'
		    {
		        $tmp=explode('@',$conf->global->MAIN_SMS_SENDMODE);
		        $classfile=$tmp[0]; $module=(empty($tmp[1])?$tmp[0]:$tmp[1]);
		        dol_include_once('/'.$module.'/class/'.$classfile.'.class.php');
		        try
		        {
		            $classname=ucfirst($classfile);
		            $sms = new $classname($this->db);
		            $sms->expe=$this->addr_from;
		            $sms->dest=$this->addr_to;
		            $sms->deferred=$this->deferred;
		            $sms->priority=$this->priority;
		            $sms->class=$this->class;
		            $sms->message=$this->message;

                    $res=$sms->SmsSend();
    				if ($res <= 0)
    				{
    					$this->error=$sms->error;
    					dol_syslog("CSMSFile::sendfile: sms send error=".$this->error, LOG_ERR);
    				}
    				else
    				{
    					dol_syslog("CSMSFile::sendfile: sms send success with id=".$res, LOG_DEBUG);
    					//var_dump($res);        // 1973128
    					$this->dump_sms_result($res);
    				}
		        }
		        catch(Exception $e)
		        {
		            dol_print_error('','Error to get list of senders: '.$e->getMessage());
		        }
		    }
			else
			{
				// Send mail method not correctly defined
				// --------------------------------------

				return 'Bad value for MAIN_SMS_SENDMODE constant';
			}
		}
		else
		{
			$this->error='No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_SMS';
			dol_syslog("CSMSFile::sendfile: ".$this->error, LOG_WARNING);
		}

		error_reporting($errorlevel);              // Reactive niveau erreur origine

		return $res;
	}


	/**
	 *  Write content of a SMTP request into a dump file (mode = all)
	 *  Used for debugging.
	 *
	 *  @return	void
	 */
	function dump_sms()
	{
		global $conf,$dolibarr_main_data_root;

		if (@is_writeable($dolibarr_main_data_root))	// Avoid fatal error on fopen with open_basedir
		{
			$outputfile=$dolibarr_main_data_root."/dolibarr_sms.log";
			$fp = fopen($outputfile,"w");

			fputs($fp, "From: ".$this->addr_from."\n");
			fputs($fp, "To: ".$this->addr_to."\n");
			fputs($fp, "Priority: ".$this->priority."\n");
			fputs($fp, "Class: ".$this->class."\n");
			fputs($fp, "Deferred: ".$this->deferred."\n");
			fputs($fp, "Message:\n".$this->message);

			fclose($fp);
			if (! empty($conf->global->MAIN_UMASK))
			@chmod($outputfile, octdec($conf->global->MAIN_UMASK));
		}
	}

    /**
     *  Write content of a SMTP request into a dump file (mode = all)
     *  Used for debugging.
     *
     *  @param	int		$result		Result of sms sending
     *  @return	void
     */
    function dump_sms_result($result)
    {
        global $conf,$dolibarr_main_data_root;

        if (@is_writeable($dolibarr_main_data_root))    // Avoid fatal error on fopen with open_basedir
        {
        	$outputfile=$dolibarr_main_data_root."/dolibarr_sms.log";
            $fp = fopen($outputfile,"a+");

            fputs($fp, "\nResult id=".$result);

            fclose($fp);
            if (! empty($conf->global->MAIN_UMASK))
            @chmod($outputfile, octdec($conf->global->MAIN_UMASK));
        }
    }

}

?>