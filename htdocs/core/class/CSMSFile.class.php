<?php
/* Copyright (C) 2000-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *      Usage:	$smsfile = new CSMSFile($subject,$sendto,$replyto,$message,$filepath,$mimetype,$filename,$cc,$ccc,$deliveryreceipt,$msgishtml,$errors_to);
 *      		$smsfile->socid=...; $smsfile->contact_id=...; $smsfile->member_id=...; $smsfile->fk_project=...;
 *             	$smsfile->sendfile();
 */
class CSMSFile
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Array of Error code (or message)
	 */
	public $errors = array();

	/**
	 * @var string end of line character
	 */
	public $eol;

	/**
	 * @var string address from
	 */
	public $addr_from;

	/**
	 * @var string address to
	 */
	public $addr_to;
	/**
	 * @var int
	 */
	public $deferred;
	/**
	 * @var int
	 */
	public $priority;
	/**
	 * @var int
	 */
	public $class;
	/**
	 * @var string
	 */
	public $message;
	/**
	 * @var bool
	 */
	public $nostop;

	/**
	 * @var int
	 */
	public $socid;
	/**
	 * @var int
	 */
	public $contact_id;
	/**
	 * @var int
	 */
	public $member_id;

	/**
	 * @var int
	 */
	public $fk_project;

	/**
	 * @var int
	 */
	public $deliveryreceipt;


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
	 */
	public function __construct($to, $from, $msg, $deliveryreceipt = 0, $deferred = 0, $priority = 3, $class = 1)
	{
		global $conf;

		// Define the line ending (TODO: Why not use PHP_EOL?)
		$this->eol = "\n";
		if (preg_match('/^win/i', PHP_OS)) {
			$this->eol = "\r\n";
		}
		if (preg_match('/^mac/i', PHP_OS)) {
			$this->eol = "\r";
		}

		// If SMS sending method not defined
		if (!getDolGlobalString('MAIN_SMS_SENDMODE')) {
			$this->error = 'No SMS Engine defined';
			throw new Exception('No SMS Engine defined');
		}

		dol_syslog("CSMSFile::CSMSFile: MAIN_SMS_SENDMODE=".getDolGlobalString('MAIN_SMS_SENDMODE')." charset=".$conf->file->character_set_client." from=".$from.", to=".$to.", msg length=".strlen($msg), LOG_DEBUG);
		dol_syslog("CSMSFile::CSMSFile: deferred=".$deferred." priority=".$priority." class=".$class, LOG_DEBUG);

		// Action according to chosen sending method
		$this->addr_from = $from;
		$this->addr_to = $to;
		$this->deferred = $deferred;
		$this->priority = $priority;
		$this->class = $class;
		$this->deliveryreceipt = $deliveryreceipt;
		$this->message = $msg;
		$this->nostop = false;
	}


	/**
	 * Send SMS that was prepared by constructor
	 *
	 * @return    boolean     True if SMS sent, false otherwise
	 */
	public function sendfile()
	{
		$errorlevel = error_reporting();
		error_reporting($errorlevel ^ E_WARNING); // Disable warnings

		$res = false;

		dol_syslog("CSMSFile::sendfile addr_to=".$this->addr_to, LOG_DEBUG);
		dol_syslog("CSMSFile::sendfile message=\n".$this->message);

		$this->message = stripslashes($this->message);

		if (getDolGlobalString('MAIN_SMS_DEBUG')) {
			$this->dump_sms();
		}

		if (!getDolGlobalString('MAIN_DISABLE_ALL_SMS')) {
			// Action according to the chose sending method
			if (getDolGlobalString('MAIN_SMS_SENDMODE')) {
				$sendmode = getDolGlobalString('MAIN_SMS_SENDMODE');	// $conf->global->MAIN_SMS_SENDMODE looks like a value 'module'
				$classmoduleofsender = getDolGlobalString('MAIN_MODULE_'.strtoupper($sendmode).'_SMS', $sendmode);	// $conf->global->MAIN_MODULE_XXX_SMS looks like a value 'class@module'
				if ($classmoduleofsender == 'ovh') {
					$classmoduleofsender = 'ovhsms@ovh';	// For backward compatibility
				}

				$tmp = explode('@', $classmoduleofsender);
				$classfile = $tmp[0];
				$module = (empty($tmp[1]) ? $tmp[0] : $tmp[1]);
				dol_include_once('/'.$module.'/class/'.strtolower($classfile).'.class.php');
				try {
					$classname = ucfirst($classfile);

					dol_syslog("CSMSFile::sendfile: try to include class ".$classname);

					if (class_exists($classname)) {
						$sms = new $classname($this->db);
						'@phan-var-force OvhSms $sms';  // Using original for analysis

						$sms->expe = $this->addr_from;
						$sms->dest = $this->addr_to;
						$sms->deferred = $this->deferred;
						$sms->priority = $this->priority;
						$sms->class = $this->class;
						$sms->message = $this->message;
						$sms->nostop = $this->nostop;
						$sms->deliveryreceipt = $this->deliveryreceipt;

						$sms->socid = $this->socid;
						$sms->contact_id = $this->contact_id;
						$sms->member_id = $this->member_id;
						$sms->fk_project = $this->fk_project;

						$res = $sms->SmsSend();

						$this->error = $sms->error;
						$this->errors = $sms->errors;
						if ($res <= 0) {
							dol_syslog("CSMSFile::sendfile: sms send error=".$this->error, LOG_ERR);
							if (getDolGlobalString('MAIN_SMS_DEBUG')) {
								$this->dump_sms_result($res);
							}
							$res = false;
						} else {
							dol_syslog("CSMSFile::sendfile: sms send success with id=".$res, LOG_DEBUG);
							//var_dump($res);        // 1973128
							if (getDolGlobalString('MAIN_SMS_DEBUG')) {
								$this->dump_sms_result($res);
							}
						}
					} else {
						$this->error = 'The SMS manager "'.$classfile.'" defined into SMS setup MAIN_MODULE_'.strtoupper($sendmode).'_SMS is not found';
					}
				} catch (Exception $e) {
					dol_print_error(null, 'Error to get list of senders: '.$e->getMessage());
				}
			} else {
				// Send sms method not correctly defined
				// --------------------------------------
				$this->error = 'Bad value for MAIN_SMS_SENDMODE constant';
				$res = false;
			}
		} else {
			$this->error = 'No sms sent. Feature is disabled by option MAIN_DISABLE_ALL_SMS';
			dol_syslog("CSMSFile::sendfile: ".$this->error, LOG_WARNING);
		}

		error_reporting($errorlevel); // Reactive niveau erreur origine

		return $res;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Write content of a SendSms request into a dump file (mode = all)
	 *  Used for debugging.
	 *
	 *  @return	void
	 */
	public function dump_sms()
	{
		// phpcs:enable
		global $conf, $dolibarr_main_data_root;

		if (@is_writable($dolibarr_main_data_root)) {	// Avoid fatal error on fopen with open_basedir
			$outputfile = $dolibarr_main_data_root."/dolibarr_sms.log";
			$fp = fopen($outputfile, "w");

			fwrite($fp, "From: ".$this->addr_from."\n");
			fwrite($fp, "To: ".$this->addr_to."\n");
			fwrite($fp, "Priority: ".$this->priority."\n");
			fwrite($fp, "Class: ".$this->class."\n");
			fwrite($fp, "Deferred: ".$this->deferred."\n");
			fwrite($fp, "DisableStop: ".((string) (int) $this->nostop)."\n");
			fwrite($fp, "DeliveryReceipt: ".$this->deliveryreceipt."\n");
			fwrite($fp, "Message:\n".$this->message);

			fclose($fp);
			dolChmod($outputfile);
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Write content of a SendSms result into a dump file (mode = all)
	 *  Used for debugging.
	 *
	 *  @param	int		$result		Result of sms sending
	 *  @return	void
	 */
	public function dump_sms_result($result)
	{
		// phpcs:enable
		global $dolibarr_main_data_root;

		if (@is_writable($dolibarr_main_data_root)) {    // Avoid fatal error on fopen with open_basedir
			$outputfile = $dolibarr_main_data_root."/dolibarr_sms.log";
			$fp = fopen($outputfile, "a+");

			fwrite($fp, "\nResult of SmsSend = ".$result);

			fclose($fp);
			dolChmod($outputfile);
		}
	}
}
