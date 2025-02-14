<?php
/**
 * Copyright (C)            Dan Potter
 * Copyright (C)            Eric Seigne
 * Copyright (C) 2000-2005  Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Jean-Louis Bergamo          <jlb@j1b.org>
 * Copyright (C) 2004-2015  Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2019-2024  Frédéric France             <frederic.france@free.fr>
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
 * Lots of code inspired from Dan Potter's CMailFile class
 */

/**
 *      \file       htdocs/core/class/CMailFile.class.php
 *      \brief      File of class to send emails (with attachments or not)
 */

use OAuth\Common\Storage\DoliStorage;
use OAuth\Common\Consumer\Credentials;

/**
 *	Class to send emails (with attachments or not)
 *  Usage: $mailfile = new CMailFile($subject,$sendto,$replyto,$message,$filepath,$mimetype,$filename,$cc,$ccc,$deliveryreceipt,$msgishtml,$errors_to,$css,$trackid,$moreinheader,$sendcontext,$replyto);
 *         $mailfile->sendfile();
 */
class CMailFile
{
	/** @var string Context of mail ('standard', 'emailing', 'ticket', 'password') */
	public $sendcontext;
	/** @var string Send mode of mail ('mail', 'smtps', 'swiftmailer', ...) */
	public $sendmode;
	/**
	 * @var mixed
	 * @deprecated Seems unused, update if used
	 */
	public $sendsetup;

	/**
	 * @var string Subject of email
	 */
	public $subject;
	/** @var string  From: Label and EMail of sender (must include '<>'). For example '<myemail@example.com>' or 'John Doe <myemail@example.com>' or '<myemail+trackingid@example.com>'). Note that with gmail smtps, value here is forced by google to account (but not the reply-to). */
	/**
	 *  @var string Sender email
	 * Sender:      Who sends the email ("Sender" has sent emails on behalf of "From").
	 *              Use it when the "From" is an email of a domain that is a SPF protected domain, and the sending smtp server is not this domain. In such case, add Sender field with an email of the protected domain.
	 */
	public $addr_from;

	// Return-Path: Email where to send bounds.

	/** @var string   Reply-To:	Email where to send replies from mailer software (mailer use From if reply-to not defined, Gmail use gmail account if reply-to not defined) */
	public $reply_to;
	/** @var string Errors-To:	Email where to send errors. */
	public $errors_to;
	/** @var string Comma separates list of destination emails */
	public $addr_to;
	/** @var string Comma separates list of cc emails */
	public $addr_cc;
	/** @var string Comma separates list of bcc emails */
	public $addr_bcc;
	/** @var string Tracking code */
	public $trackid;

	/** @var string Mixed Boundary */
	public $mixed_boundary;
	/** @var string Related Boundary */
	public $related_boundary;
	/** @var string Alternative Boundary */
	public $alternative_boundary;
	/** @var int<0,1> When 1, request delivery receipt */
	public $deliveryreceipt;

	/** @var ?int<1,1> When 1, there is at least one file */
	public $atleastonefile;

	/** @var string $msg Message to send */
	public $msg;
	/** @var string $msg End of line sequence */
	public $eol;
	/** @var string $msg End of line sequence (header ?) */
	public $eol2;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Array of Error code (or message)
	 */
	public $errors = array();


	/**
	 * @var SMTPS (if this method is used)
	 */
	public $smtps;
	/**
	 * @var Swift_Mailer (if the method is used)
	 */
	public $mailer;

	/**
	 * @var Swift_SmtpTransport
	 */
	public $transport;
	/**
	 * @var Swift_Plugins_Loggers_ArrayLogger
	 */
	public $logger;

	/**
	 * @var string|array<string,string> CSS
	 */
	public $css;
	/** @var ?string Defined css style for body background */
	public $styleCSS;
	/** @var ?string Defined background directly in body tag */
	public $bodyCSS;

	/**
	 * @var string	Message-ID of the email to send (generated)
	 */
	public $msgid;

	/**
	 * @var string	Value to use in In-reply-to when email is set as an answer of another email (The Msg-Id of received email)
	 */
	public $in_reply_to;

	/**
	 * @var string	References to add to the email to send (generated from the email we answer)
	 */
	public $references;

	/**
	 * @var string Headers
	 */
	public $headers;

	/**
	 * @var string Message
	 */
	public $message;

	/**
	 * @var ?string[] fullfilenames list (full path of filename on file system)
	 */
	public $filename_list = array();
	/**
	 * @var ?string[] mimetypes of files list (List of MIME type of attached files)
	 */
	public $mimetype_list = array();
	/**
	 * @var ?string[] filenames list (List of attached file name in message)
	 */
	public $mimefilename_list = array();
	/**
	 * @var ?string[] filenames cid
	 */
	public $cid_list = array();

	/** @var string HTML content */
	public $html;
	/** @var int<0,1> */
	public $msgishtml;
	/** @var string */
	public $image_boundary;
	/** @var int<0,1> */
	public $atleastoneimage = 0; // at least one image file with file=xxx.ext into content (TODO Debug this. How can this case be tested. Remove if not used).
	/** @var array<array{type:string,fullpath:string,content_type?:string,name:string,cid:string}> */
	public $html_images = array();
	/** @var array<array{name:string,fullpath:string,content_type:string,cid:string,image_encoded:string}> */
	public $images_encoded = array();
	public $image_types = array(
		'gif'  => 'image/gif',
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'jpe'  => 'image/jpeg',
		'bmp'  => 'image/bmp',
		'png'  => 'image/png',
		'tif'  => 'image/tiff',
		'tiff' => 'image/tiff',
	);


	/**
	 *	CMailFile
	 *
	 *	@param 	string	$subject             Topic/Subject of mail
	 *	@param 	string	$to                  Recipients emails (RFC 2822: "Name firstname <email>[, ...]" or "email[, ...]" or "<email>[, ...]"). Note: the keyword '__SUPERVISOREMAIL__' is not allowed here and must be replaced by caller.
	 *	@param 	string	$from                Sender email      (RFC 2822: "Name firstname <email>[, ...]" or "email[, ...]" or "<email>[, ...]")
	 *	@param 	string	$msg                 Message
	 *	@param 	?string[]	$filename_list       List of files to attach (full path of filename on file system)
	 *	@param 	?string[]	$mimetype_list       List of MIME type of attached files
	 *	@param 	?string[]	$mimefilename_list   List of attached file name in message
	 *	@param 	string	$addr_cc             Email cc (Example: 'abc@def.com, ghk@lmn.com')
	 *	@param 	string	$addr_bcc            Email bcc (Note: This is autocompleted with MAIN_MAIL_AUTOCOPY_TO if defined)
	 *	@param 	int<0,1>	$deliveryreceipt     Ask a delivery receipt
	 *	@param 	int<-1,1>	$msgishtml           1=String IS already html, 0=String IS NOT html, -1=Unknown make autodetection (with fast mode, not reliable)
	 *	@param 	string	$errors_to      	 Email for errors-to
	 *	@param	string|array<string,string>	$css                 Css option (should be array, legacy: empty string if none)
	 *	@param	string	$trackid             Tracking string (contains type and id of related element)
	 *  @param  string  $moreinheader        More in header. $moreinheader must contains the "\r\n" at end of each line
	 *  @param  string  $sendcontext      	 'standard', 'emailing', 'ticket', 'password', ... (used to define which sending mode and parameters to use)
	 *  @param	string	$replyto			 Reply-to email (will be set to the same value than From by default if not provided)
	 *  @param	string	$upload_dir_tmp		 Temporary directory (used to convert images embedded as img src=data:image)
	 *  @param	string	$in_reply_to		 Message-ID of the message we reply to
	 *  @param	string	$references			 String with list of Message-ID of the thread ('<123> <456> ...')
	 */
	public function __construct($subject, $to, $from, $msg, $filename_list = array(), $mimetype_list = array(), $mimefilename_list = array(), $addr_cc = "", $addr_bcc = "", $deliveryreceipt = 0, $msgishtml = 0, $errors_to = '', $css = '', $trackid = '', $moreinheader = '', $sendcontext = 'standard', $replyto = '', $upload_dir_tmp = '', $in_reply_to = '', $references = '')
	{
		global $conf, $dolibarr_main_data_root, $user;

		dol_syslog("CMailFile::CMailfile: charset=".$conf->file->character_set_client." from=$from, to=$to, addr_cc=$addr_cc, addr_bcc=$addr_bcc, errors_to=$errors_to, replyto=$replyto trackid=$trackid sendcontext=$sendcontext");
		dol_syslog("CMailFile::CMailfile: subject=".$subject.", deliveryreceipt=".$deliveryreceipt.", msgishtml=".$msgishtml, LOG_DEBUG);


		// Clean values of $mimefilename_list
		if (is_array($mimefilename_list)) {
			foreach ($mimefilename_list as $key => $val) {
				$mimefilename_list[$key] = dol_string_unaccent($mimefilename_list[$key]);
			}
		}

		$cid_list = array();

		$this->sendcontext = $sendcontext;

		// Define this->sendmode ('mail', 'smtps', 'swiftmailer', ...) according to $sendcontext ('standard', 'emailing', 'ticket', 'passwordreset')
		$this->sendmode = '';
		if (!empty($this->sendcontext)) {
			$smtpContextKey = strtoupper($this->sendcontext);
			$smtpContextSendMode = getDolGlobalString('MAIN_MAIL_SENDMODE_'.$smtpContextKey);
			if (!empty($smtpContextSendMode) && $smtpContextSendMode != 'default') {
				$this->sendmode = $smtpContextSendMode;
			}
		}
		if (empty($this->sendmode)) {
			$this->sendmode = getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail');
		}

		// Add a Feedback-ID. Must be used for stats on spam report only.
		if ($trackid) {
			//Examples:
			// LinkedIn – Feedback-ID: accept_invite_04:linkedin
			// Twitter – Feedback-ID: 0040162518f58f41d1f0:15491f3b2ee48656f8e7fb2fac:none:twitterESP
			// Amazon.com : Feedback-ID: 1.eu-west-1.kjoQSiqb8G+7lWWiDVsxjM2m0ynYd4I6yEFlfoox6aY=:AmazonSES
			$moreinheader .= "Feedback-ID: ".$trackid.':'.dol_getprefix('email').":dolib\r\n";
		}

		// We define end of line (RFC 821).
		$this->eol = "\r\n";
		// We define end of line for header fields (RFC 822bis section 2.3 says header must contains \r\n).
		$this->eol2 = "\r\n";
		if (getDolGlobalString('MAIN_FIX_FOR_BUGGED_MTA')) {
			$this->eol = "\n";
			$this->eol2 = "\n";
			$moreinheader = str_replace("\r\n", "\n", $moreinheader);
		}

		// On defini mixed_boundary
		$this->mixed_boundary = "multipart_x.".time().".x_boundary";

		// On defini related_boundary
		$this->related_boundary = 'mul_'.dol_hash(uniqid("dolibarr2"), '3'); // Force md5 hash (does not contain special chars)

		// On defini alternative_boundary
		$this->alternative_boundary = 'mul_'.dol_hash(uniqid("dolibarr3"), '3'); // Force md5 hash (does not contain special chars)

		if (empty($subject)) {
			dol_syslog("CMailFile::CMailfile: Try to send an email with empty subject");
			$this->error = 'ErrorSubjectIsRequired';
			return;
		}
		if (empty($msg)) {
			dol_syslog("CMailFile::CMailfile: Try to send an email with empty body");
			$msg = '.'; // Avoid empty message (with empty message content, you will see a multipart structure)
		}

		// Detect if message is HTML (use fast method)
		if ($msgishtml == -1) {
			$this->msgishtml = 0;
			if (dol_textishtml($msg)) {
				$this->msgishtml = 1;
			}
		} else {
			$this->msgishtml = $msgishtml;
		}

		global $dolibarr_main_url_root;

		// Define $urlwithroot
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

		// Replace relative /viewimage to absolute path
		$msg = preg_replace('/src="'.preg_quote(DOL_URL_ROOT, '/').'\/viewimage\.php/ims', 'src="'.$urlwithroot.'/viewimage.php', $msg, -1);

		if (getDolGlobalString('MAIN_MAIL_FORCE_CONTENT_TYPE_TO_HTML')) {
			$this->msgishtml = 1; // To force to send everything with content type html.
		}
		dol_syslog("CMailFile::CMailfile: msgishtml=".$this->msgishtml, LOG_DEBUG);

		// Detect images
		if ($this->msgishtml) {
			$this->html = $msg;

			$findimg = 0;
			if (getDolGlobalString('MAIN_MAIL_ADD_INLINE_IMAGES_IF_IN_MEDIAS')) {	// Off by default
				// Search into the body for <img tags of links in medias files to replace them with an embedded file
				// Note because media links are public, this should be useless, except avoid blocking images with email browser.
				// This converts an embed file with src="/viewimage.php?modulepart... into a cid link
				// TODO Exclude viewimage used for the read tracker ?
				$findimg = $this->findHtmlImages($dolibarr_main_data_root.'/medias');
				if ($findimg < 0) {
					dol_syslog("CMailFile::CMailfile: Error on findHtmlImages");
					$this->error = 'ErrorInAddAttachmentsImageBaseOnMedia';
					return;
				}
			}

			if (getDolGlobalString('MAIN_MAIL_ADD_INLINE_IMAGES_IF_DATA')) {
				// Search into the body for <img src="data:image/ext;base64,..." to replace them with an embedded file
				// This convert an embedded file with src="data:image... into a cid link + attached file
				$resultImageData = $this->findHtmlImagesIsSrcData($upload_dir_tmp);
				if ($resultImageData < 0) {
					dol_syslog("CMailFile::CMailfile: Error on findHtmlImagesInSrcData code=".$resultImageData." upload_dir_tmp=".$upload_dir_tmp);
					$this->error = 'ErrorInAddAttachmentsImageBaseIsSrcData';
					return;
				}
				$findimg += $resultImageData;
			}

			// Set atleastoneimage if there is at least one embedded file (into ->html_images)
			if ($findimg > 0) {
				foreach ($this->html_images as $i => $val) {
					if ($this->html_images[$i]) {
						$this->atleastoneimage = 1;
						if ($this->html_images[$i]['type'] == 'cidfromdata') {
							if (!in_array($this->html_images[$i]['fullpath'], $filename_list)) {
								// If this file path is not already into the $filename_list, we append it at end of array
								$posindice = count($filename_list);
								$filename_list[$posindice] = $this->html_images[$i]['fullpath'];
								$mimetype_list[$posindice] = $this->html_images[$i]['content_type'];
								$mimefilename_list[$posindice] = $this->html_images[$i]['name'];
							} else {
								$posindice = array_search($this->html_images[$i]['fullpath'], $filename_list);
							}
							// We complete the array of cid_list
							$cid_list[$posindice] = $this->html_images[$i]['cid'];
						}
						dol_syslog("CMailFile::CMailfile: html_images[$i]['name']=".$this->html_images[$i]['name'], LOG_DEBUG);
					}
				}
			}
		}
		//var_dump($filename_list);
		//var_dump($cid_list);exit;

		// Set atleastoneimage if there is at least one file (into $filename_list array)
		if (is_array($filename_list)) {
			foreach ($filename_list as $i => $val) {
				if ($filename_list[$i]) {
					$this->atleastonefile = 1;
					dol_syslog("CMailFile::CMailfile: filename_list[$i]=".$filename_list[$i].", mimetype_list[$i]=".$mimetype_list[$i]." mimefilename_list[$i]=".$mimefilename_list[$i]." cid_list[$i]=".(empty($cid_list[$i]) ? '' : $cid_list[$i]), LOG_DEBUG);
				}
			}
		}

		// Add auto copy to if not already in $to (Note: Adding bcc for specific modules are also done from pages)
		// For example MAIN_MAIL_AUTOCOPY_TO can be 'email@example.com, __USER_EMAIL__, ...'
		if (getDolGlobalString('MAIN_MAIL_AUTOCOPY_TO')) {
			$listofemailstoadd = explode(',', getDolGlobalString('MAIN_MAIL_AUTOCOPY_TO'));
			foreach ($listofemailstoadd as $key => $val) {
				$emailtoadd = $listofemailstoadd[$key];
				if (trim($emailtoadd) == '__USER_EMAIL__') {
					if (!empty($user) && !empty($user->email)) {
						$emailtoadd = $user->email;
					} else {
						$emailtoadd = '';
					}
				}
				if ($emailtoadd && preg_match('/'.preg_quote($emailtoadd, '/').'/i', $to)) {
					$emailtoadd = '';	// Email already in the "To"
				}
				if ($emailtoadd) {
					$listofemailstoadd[$key] = $emailtoadd;
				} else {
					unset($listofemailstoadd[$key]);
				}
			}
			if (!empty($listofemailstoadd)) {
				$addr_bcc .= ($addr_bcc ? ', ' : '').implode(', ', $listofemailstoadd);
			}
		}

		// Verify if $to, $addr_cc and addr_bcc have unwanted addresses
		if (getDolGlobalString('MAIN_MAIL_FORCE_NOT_SENDING_TO')) {
			//Verify for $to
			$replaceto = false;
			$tabto = explode(",", $to);
			$listofemailstonotsendto = explode(',', getDolGlobalString('MAIN_MAIL_FORCE_NOT_SENDING_TO'));
			foreach ($tabto as $key => $addrto) {
				$addrto = array_keys($this->getArrayAddress($addrto));
				if (in_array($addrto[0], $listofemailstonotsendto)) {
					unset($tabto[$key]);
					$replaceto = true;
				}
			}
			if ($replaceto && getDolGlobalString('MAIN_MAIL_FORCE_NOT_SENDING_TO_REPLACE')) {
				$tabto[] = getDolGlobalString('MAIN_MAIL_FORCE_NOT_SENDING_TO_REPLACE');
			}
			$to = implode(',', $tabto);

			//Verify for $addr_cc
			$replacecc = false;
			$tabcc = explode(',', $addr_cc);
			foreach ($tabcc as $key => $cc) {
				$cc = array_keys($this->getArrayAddress($cc));
				if (in_array($cc[0], $listofemailstonotsendto)) {
					unset($tabcc[$key]);
					$replacecc = true;
				}
			}
			if ($replacecc && getDolGlobalString('MAIN_MAIL_FORCE_NOT_SENDING_TO_REPLACE')) {
				$tabcc[] = getDolGlobalString('MAIN_MAIL_FORCE_NOT_SENDING_TO_REPLACE');
			}
			$addr_cc = implode(',', $tabcc);

			//Verify for $addr_bcc
			$replacebcc = false;
			$tabbcc = explode(',', $addr_bcc);
			foreach ($tabbcc as $key => $bcc) {
				$bcc = array_keys($this->getArrayAddress($bcc));
				if (in_array($bcc[0], $listofemailstonotsendto)) {
					unset($tabbcc[$key]);
					$replacebcc = true;
				}
			}
			if ($replacebcc && getDolGlobalString('MAIN_MAIL_FORCE_NOT_SENDING_TO_REPLACE')) {
				$tabbcc[] = getDolGlobalString('MAIN_MAIL_FORCE_NOT_SENDING_TO_REPLACE');
			}
			$addr_bcc = implode(',', $tabbcc);
		}

		// We always use a replyto
		if (empty($replyto)) {
			$replyto = dol_sanitizeEmail($from);
		}
		// We can force the from
		if (getDolGlobalString('MAIN_MAIL_FORCE_FROM')) {
			$from = getDolGlobalString('MAIN_MAIL_FORCE_FROM');
		}

		$this->subject = $subject;
		$this->addr_to = dol_sanitizeEmail($to);
		$this->addr_from = dol_sanitizeEmail($from);
		$this->msg = $msg;
		$this->addr_cc = dol_sanitizeEmail($addr_cc);
		$this->addr_bcc = dol_sanitizeEmail($addr_bcc);
		$this->deliveryreceipt = $deliveryreceipt;
		$this->reply_to = dol_sanitizeEmail($replyto);
		$this->errors_to = dol_sanitizeEmail($errors_to);
		$this->trackid = $trackid;
		$this->in_reply_to = $in_reply_to;
		$this->references = $references;
		// Set arrays with attached files info
		$this->filename_list = $filename_list;
		$this->mimetype_list = $mimetype_list;
		$this->mimefilename_list = $mimefilename_list;
		$this->cid_list = $cid_list;

		if (getDolGlobalString('MAIN_MAIL_FORCE_SENDTO')) {
			$this->addr_to = dol_sanitizeEmail(getDolGlobalString('MAIN_MAIL_FORCE_SENDTO'));
			$this->addr_cc = '';
			$this->addr_bcc = '';
		}

		$keyforsslseflsigned = 'MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED';
		if (!empty($this->sendcontext)) {
			$smtpContextKey = strtoupper($this->sendcontext);
			$smtpContextSendMode = getDolGlobalString('MAIN_MAIL_SENDMODE_'.$smtpContextKey);
			if (!empty($smtpContextSendMode) && $smtpContextSendMode != 'default') {
				$keyforsslseflsigned = 'MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED_'.$smtpContextKey;
			}
		}

		dol_syslog("CMailFile::CMailfile: sendmode=".$this->sendmode." addr_bcc=$addr_bcc, replyto=$replyto", LOG_DEBUG);

		// We set all data according to chose sending method.
		// We also set a value for ->msgid
		if ($this->sendmode == 'mail') {
			// Use mail php function (default PHP method)
			// ------------------------------------------

			$smtp_headers = "";
			$mime_headers = "";
			$text_body = "";
			$files_encoded = "";

			// Define smtp_headers (this also set SMTP headers from ->msgid, ->in_reply_to and ->references)
			$smtp_headers = $this->write_smtpheaders();
			if (!empty($moreinheader)) {
				$smtp_headers .= $moreinheader; // $moreinheader contains the \r\n
			}

			// Define mime_headers
			$mime_headers = $this->write_mimeheaders($filename_list, $mimefilename_list);

			if (!empty($this->html)) {
				if (!empty($css)) {
					$this->css = $css;
					$this->buildCSS(); // Build a css style (mode = all) into this->styleCSS and this->bodyCSS
				}

				$msg = $this->html;
			}

			// Define body in text_body
			$text_body = $this->write_body($msg);

			// Add attachments to text_encoded
			if (!empty($this->atleastonefile)) {
				$files_encoded = $this->write_files($filename_list, $mimetype_list, $mimefilename_list, $cid_list);
			}

			// We now define $this->headers and $this->message
			$this->headers = $smtp_headers.$mime_headers;
			// Clean the header to avoid that it terminates with a CR character.
			// This avoid also empty lines at end that can be interpreted as mail injection by email servers.
			$this->headers = preg_replace("/([\r\n]+)$/i", "", $this->headers);

			//$this->message = $this->eol.'This is a message with multiple parts in MIME format.'.$this->eol;
			$this->message = 'This is a message with multiple parts in MIME format.'.$this->eol;
			$this->message .= $text_body.$files_encoded;
			$this->message .= "--".$this->mixed_boundary."--".$this->eol;
		} elseif ($this->sendmode == 'smtps') {
			// Use SMTPS library
			// ------------------------------------------
			$host = dol_getprefix('email');

			require_once DOL_DOCUMENT_ROOT.'/core/class/smtps.class.php';
			$smtps = new SMTPs();
			$smtps->setCharSet($conf->file->character_set_client);

			// Encode subject if required.
			$subjecttouse = $this->subject;
			if (!ascii_check($subjecttouse)) {
				$subjecttouse = $this->encodetorfc2822($subjecttouse);
			}

			$smtps->setSubject($subjecttouse);
			$smtps->setTO($this->getValidAddress($this->addr_to, 0, 1));
			$smtps->setFrom($this->getValidAddress($this->addr_from, 0, 1));
			$smtps->setReplyTo($this->getValidAddress($this->reply_to, 0, 1));

			$smtps->setTrackId($this->trackid);

			if (!empty($this->in_reply_to)) {
				$smtps->setInReplyTo($this->in_reply_to);
			}
			if (!empty($this->references)) {
				$smtps->setReferences($this->references);
			}

			if (!empty($moreinheader)) {
				$smtps->setMoreInHeader($moreinheader);
			}

			//X-Dolibarr-TRACKID, In-Reply-To, References and $moreinheader will be added to header inside the smtps->getHeader

			if (!empty($this->html)) {
				if (!empty($css)) {
					$this->css = $css;
					$this->buildCSS();
				}
				$msg = $this->html;
				$msg = $this->checkIfHTML($msg);		// This add a header and a body including custom CSS to the HTML content
			}

			if ($msg === '.') {
				$msg = "\n.\n";
			}
			// Replace . alone on a new line with .. to avoid to have SMTP interpret this as end of message
			$msg = preg_replace('/(\r|\n)\.(\r|\n)/ims', '\1..\2', $msg);

			if ($this->msgishtml) {
				$smtps->setBodyContent($msg, 'html');
			} else {
				$smtps->setBodyContent($msg, 'plain');
			}

			if ($this->atleastoneimage) {
				foreach ($this->images_encoded as $img) {
					$smtps->setImageInline($img['image_encoded'], $img['name'], $img['content_type'], $img['cid']);
				}
			}

			if (!empty($this->atleastonefile)) {
				foreach ($filename_list as $i => $val) {
					$content = file_get_contents($filename_list[$i]);
					$smtps->setAttachment($content, $mimefilename_list[$i], $mimetype_list[$i], (empty($cid_list[$i]) ? '' : $cid_list[$i]));
				}
			}

			$smtps->setCC($this->addr_cc);
			$smtps->setBCC($this->addr_bcc);
			$smtps->setErrorsTo($this->errors_to);
			$smtps->setDeliveryReceipt($this->deliveryreceipt);
			if (getDolGlobalString($keyforsslseflsigned)) {
				$smtps->setOptions(array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true)));
			}

			$this->msgid = time().'.SMTPs-dolibarr-'.$this->trackid.'@'.$host;

			$this->smtps = $smtps;
		} elseif ($this->sendmode == 'swiftmailer') {
			// Use Swift Mailer library
			// ------------------------------------------
			$host = dol_getprefix('email');

			require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/lexer/lib/Doctrine/Common/Lexer/AbstractLexer.php';

			// egulias autoloader lib
			require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/autoload.php';

			require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/lib/swift_required.php';

			// Create the message
			//$this->message = Swift_Message::newInstance();
			$this->message = new Swift_Message();
			//$this->message = new Swift_SignedMessage();
			// Adding a trackid header to a message
			$headers = $this->message->getHeaders();

			$headers->addTextHeader('X-Dolibarr-TRACKID', $this->trackid.'@'.$host);
			$this->msgid = time().'.swiftmailer-dolibarr-'.$this->trackid.'@'.$host;
			$headerID = $this->msgid;
			$msgid = $headers->get('Message-ID');
			if ($msgid instanceof Swift_Mime_Headers_IdentificationHeader) {
				$msgid->setId($headerID);
			}

			// Add 'In-Reply-To:' header
			if (!empty($this->in_reply_to)) {
				$headers->addIdHeader('In-Reply-To', $this->in_reply_to);
			}
			// Add 'References:' header
			if (!empty($this->references)) {
				$headers->addIdHeader('References', $this->references);
			}

			if (!empty($moreinheader)) {
				$moreinheaderarray = preg_split('/[\r\n]+/', $moreinheader);
				foreach ($moreinheaderarray as $moreinheaderval) {
					$moreinheadervaltmp = explode(':', $moreinheaderval, 2);
					if (!empty($moreinheadervaltmp[0]) && !empty($moreinheadervaltmp[1])) {
						$headers->addTextHeader($moreinheadervaltmp[0], $moreinheadervaltmp[1]);
					}
				}
			}

			// Give the message a subject
			try {
				$this->message->setSubject($this->subject);
			} catch (Exception $e) {
				$this->errors[] = $e->getMessage();
			}

			// Set the From address with an associative array
			//$this->message->setFrom(array('john@doe.com' => 'John Doe'));
			if (!empty($this->addr_from)) {
				try {
					if (getDolGlobalString('MAIN_FORCE_DISABLE_MAIL_SPOOFING')) {
						// Prevent email spoofing for smtp server with a strict configuration
						$regexp = '/([a-z0-9_\.\-\+])+\@(([a-z0-9\-])+\.)+([a-z0-9]{2,4})+/i'; // This regular expression extracts all emails from a string
						$adressEmailFrom = array();
						$emailMatchs = preg_match_all($regexp, $from, $adressEmailFrom);
						$adressEmailFrom = reset($adressEmailFrom);
						if ($emailMatchs !== false && filter_var($conf->global->MAIN_MAIL_SMTPS_ID, FILTER_VALIDATE_EMAIL) && $conf->global->MAIN_MAIL_SMTPS_ID !== $adressEmailFrom) {
							$this->message->setFrom($conf->global->MAIN_MAIL_SMTPS_ID);
						} else {
							$this->message->setFrom($this->getArrayAddress($this->addr_from));
						}
					} else {
						$this->message->setFrom($this->getArrayAddress($this->addr_from));
					}
				} catch (Exception $e) {
					$this->errors[] = $e->getMessage();
				}
			}

			// Set the To addresses with an associative array
			if (!empty($this->addr_to)) {
				try {
					$this->message->setTo($this->getArrayAddress($this->addr_to));
				} catch (Exception $e) {
					$this->errors[] = $e->getMessage();
				}
			}

			if (!empty($this->reply_to)) {
				try {
					$this->message->SetReplyTo($this->getArrayAddress($this->reply_to));
				} catch (Exception $e) {
					$this->errors[] = $e->getMessage();
				}
			}

			if (!empty($this->errors_to)) {
				try {
					$headers->addMailboxHeader('Errors-To', $this->getArrayAddress($this->errors_to));
				} catch (Exception $e) {
					$this->errors[] = $e->getMessage();
				}
			}

			try {
				$this->message->setCharSet($conf->file->character_set_client);
			} catch (Exception $e) {
				$this->errors[] = $e->getMessage();
			}

			if (!empty($this->html)) {
				if (!empty($css)) {
					$this->css = $css;
					$this->buildCSS();
				}
				$msg = $this->html;
				$msg = $this->checkIfHTML($msg);		// This add a header and a body including custom CSS to the HTML content
			}

			if ($this->atleastoneimage) {
				foreach ($this->html_images as $img) {
					// $img['fullpath'],$img['image_encoded'],$img['name'],$img['content_type'],$img['cid']
					$attachment = Swift_Image::fromPath($img['fullpath']);
					// embed image
					$imgcid = $this->message->embed($attachment);
					// replace cid by the one created by swiftmail in html message
					$msg = str_replace("cid:".$img['cid'], $imgcid, $msg);
				}
				foreach ($this->images_encoded as $img) {
					//$img['fullpath'],$img['image_encoded'],$img['name'],$img['content_type'],$img['cid']
					$attachment = Swift_Image::fromPath($img['fullpath']);
					// embed image
					$imgcid = $this->message->embed($attachment);
					// replace cid by the one created by swiftmail in html message
					$msg = str_replace("cid:".$img['cid'], $imgcid, $msg);
				}
			}

			if ($this->msgishtml) {
				$this->message->setBody($msg, 'text/html');
				// And optionally an alternative body
				$this->message->addPart(html_entity_decode(strip_tags($msg)), 'text/plain');
			} else {
				$this->message->setBody($msg, 'text/plain');
				// And optionally an alternative body
				$this->message->addPart(dol_nl2br($msg), 'text/html');
			}

			if (!empty($this->atleastonefile)) {
				foreach ($filename_list as $i => $val) {
					//$this->message->attach(Swift_Attachment::fromPath($filename_list[$i],$mimetype_list[$i]));
					$attachment = Swift_Attachment::fromPath($filename_list[$i], $mimetype_list[$i]);
					if (!empty($mimefilename_list[$i])) {
						$attachment->setFilename($mimefilename_list[$i]);
					}
					$this->message->attach($attachment);
				}
			}

			if (!empty($this->addr_cc)) {
				try {
					$this->message->setCc($this->getArrayAddress($this->addr_cc));
				} catch (Exception $e) {
					$this->errors[] = $e->getMessage();
				}
			}
			if (!empty($this->addr_bcc)) {
				try {
					$this->message->setBcc($this->getArrayAddress($this->addr_bcc));
				} catch (Exception $e) {
					$this->errors[] = $e->getMessage();
				}
			}
			if (isset($this->deliveryreceipt) && $this->deliveryreceipt == 1) {
				try {
					$this->message->setReadReceiptTo($this->getArrayAddress($this->addr_from));
				} catch (Exception $e) {
					$this->errors[] = $e->getMessage();
				}
			}
		} else {
			// Send mail method not correctly defined
			// --------------------------------------
			$this->error = 'Bad value for sendmode';
		}
	}

	/**
	 * Send mail that was prepared by constructor.
	 *
	 * @return    bool	True if mail sent, false otherwise.  Negative int if error in hook.  String if incorrect send mode.
	 *
	 * @phan-suppress PhanTypeMismatchReturnNullable  False positif by phan for unclear reason.
	 */
	public function sendfile()
	{
		global $conf, $db, $langs, $hookmanager;

		$errorlevel = error_reporting();
		//error_reporting($errorlevel ^ E_WARNING);   // Desactive warnings

		$res = false;

		if (!getDolGlobalString('MAIN_DISABLE_ALL_MAILS')) {
			if (!is_object($hookmanager)) {
				include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
				$hookmanager = new HookManager($db);
			}
			$hookmanager->initHooks(array('mail'));

			$parameters = array();
			$action = '';
			$reshook = $hookmanager->executeHooks('sendMail', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
			if ($reshook < 0) {
				$this->error = "Error in hook maildao sendMail ".$reshook;
				dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);

				return false;
			}
			if ($reshook == 1) {	// Hook replace standard code
				dol_syslog("A hook has replaced code to send email", LOG_DEBUG);
				return true;
			}

			$sendingmode = $this->sendmode;
			if ($this->sendcontext == 'emailing' && getDolGlobalString('MAILING_NO_USING_PHPMAIL') && $sendingmode == 'mail') {
				// List of sending methods
				$listofmethods = array();
				$listofmethods['mail'] = 'PHP mail function';
				//$listofmethods['simplemail']='Simplemail class';
				$listofmethods['smtps'] = 'SMTP/SMTPS socket library';

				// EMailing feature may be a spam problem, so when you host several users/instance, having this option may force each user to use their own SMTP agent.
				// You ensure that every user is using its own SMTP server when using the mass emailing module.
				$linktoadminemailbefore = '<a href="'.DOL_URL_ROOT.'/admin/mails.php">';
				$linktoadminemailend = '</a>';
				$this->error = $langs->trans("MailSendSetupIs", $listofmethods[$sendingmode]);
				$this->errors[] = $langs->trans("MailSendSetupIs", $listofmethods[$sendingmode]);
				$this->error .= '<br>'.$langs->trans("MailSendSetupIs2", $linktoadminemailbefore, $linktoadminemailend, $langs->transnoentitiesnoconv("MAIN_MAIL_SENDMODE"), $listofmethods['smtps']);
				$this->errors[] = $langs->trans("MailSendSetupIs2", $linktoadminemailbefore, $linktoadminemailend, $langs->transnoentitiesnoconv("MAIN_MAIL_SENDMODE"), $listofmethods['smtps']);
				if (getDolGlobalString('MAILING_SMTP_SETUP_EMAILS_FOR_QUESTIONS')) {
					$this->error .= '<br>'.$langs->trans("MailSendSetupIs3", getDolGlobalString('MAILING_SMTP_SETUP_EMAILS_FOR_QUESTIONS'));
					$this->errors[] = $langs->trans("MailSendSetupIs3", getDolGlobalString('MAILING_SMTP_SETUP_EMAILS_FOR_QUESTIONS'));
				}

				dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_WARNING);
				return false;
			}

			// Check number of recipient is lower or equal than MAIL_MAX_NB_OF_RECIPIENTS_IN_SAME_EMAIL
			if (!getDolGlobalString('MAIL_MAX_NB_OF_RECIPIENTS_TO_IN_SAME_EMAIL')) {
				$conf->global->MAIL_MAX_NB_OF_RECIPIENTS_TO_IN_SAME_EMAIL = 10;
			}
			$tmparray1 = explode(',', $this->addr_to);
			if (count($tmparray1) > $conf->global->MAIL_MAX_NB_OF_RECIPIENTS_TO_IN_SAME_EMAIL) {
				$this->error = 'Too much recipients in to:';
				dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_WARNING);
				return false;
			}
			if (!getDolGlobalString('MAIL_MAX_NB_OF_RECIPIENTS_CC_IN_SAME_EMAIL')) {
				$conf->global->MAIL_MAX_NB_OF_RECIPIENTS_CC_IN_SAME_EMAIL = 10;
			}
			$tmparray2 = explode(',', $this->addr_cc);
			if (count($tmparray2) > $conf->global->MAIL_MAX_NB_OF_RECIPIENTS_CC_IN_SAME_EMAIL) {
				$this->error = 'Too much recipients in cc:';
				dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_WARNING);
				return false;
			}
			if (!getDolGlobalString('MAIL_MAX_NB_OF_RECIPIENTS_BCC_IN_SAME_EMAIL')) {
				$conf->global->MAIL_MAX_NB_OF_RECIPIENTS_BCC_IN_SAME_EMAIL = 10;
			}
			$tmparray3 = explode(',', $this->addr_bcc);
			if (count($tmparray3) > $conf->global->MAIL_MAX_NB_OF_RECIPIENTS_BCC_IN_SAME_EMAIL) {
				$this->error = 'Too much recipients in bcc:';
				dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_WARNING);
				return false;
			}
			if (!getDolGlobalString('MAIL_MAX_NB_OF_RECIPIENTS_IN_SAME_EMAIL')) {
				$conf->global->MAIL_MAX_NB_OF_RECIPIENTS_IN_SAME_EMAIL = 10;
			}
			if ((count($tmparray1) + count($tmparray2) + count($tmparray3)) > $conf->global->MAIL_MAX_NB_OF_RECIPIENTS_IN_SAME_EMAIL) {
				$this->error = 'Too much recipients in to:, cc:, bcc:';
				dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_WARNING);
				return false;
			}

			$keyforsmtpserver = 'MAIN_MAIL_SMTP_SERVER';
			$keyforsmtpport  = 'MAIN_MAIL_SMTP_PORT';
			$keyforsmtpid    = 'MAIN_MAIL_SMTPS_ID';
			$keyforsmtppw    = 'MAIN_MAIL_SMTPS_PW';
			$keyforsmtpauthtype = 'MAIN_MAIL_SMTPS_AUTH_TYPE';
			$keyforsmtpoauthservice = 'MAIN_MAIL_SMTPS_OAUTH_SERVICE';
			$keyfortls       = 'MAIN_MAIL_EMAIL_TLS';
			$keyforstarttls  = 'MAIN_MAIL_EMAIL_STARTTLS';
			$keyforsslseflsigned = 'MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED';
			if (!empty($this->sendcontext)) {
				$smtpContextKey = strtoupper($this->sendcontext);
				$smtpContextSendMode = getDolGlobalString('MAIN_MAIL_SENDMODE_'.$smtpContextKey);
				if (!empty($smtpContextSendMode) && $smtpContextSendMode != 'default') {
					$keyforsmtpserver = 'MAIN_MAIL_SMTP_SERVER_'.$smtpContextKey;
					$keyforsmtpport   = 'MAIN_MAIL_SMTP_PORT_'.$smtpContextKey;
					$keyforsmtpid     = 'MAIN_MAIL_SMTPS_ID_'.$smtpContextKey;
					$keyforsmtppw     = 'MAIN_MAIL_SMTPS_PW_'.$smtpContextKey;
					$keyforsmtpauthtype = 'MAIN_MAIL_SMTPS_AUTH_TYPE_'.$smtpContextKey;
					$keyforsmtpoauthservice = 'MAIN_MAIL_SMTPS_OAUTH_SERVICE_'.$smtpContextKey;
					$keyfortls        = 'MAIN_MAIL_EMAIL_TLS_'.$smtpContextKey;
					$keyforstarttls   = 'MAIN_MAIL_EMAIL_STARTTLS_'.$smtpContextKey;
					$keyforsslseflsigned = 'MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED_'.$smtpContextKey;
				}
			}

			// Action according to chose sending method
			if ($this->sendmode == 'mail') {
				// Use mail php function (default PHP method)
				// ------------------------------------------
				dol_syslog("CMailFile::sendfile addr_to=".$this->addr_to.", subject=".$this->subject, LOG_NOTICE);
				//dol_syslog("CMailFile::sendfile header=\n".$this->headers, LOG_DEBUG);
				//dol_syslog("CMailFile::sendfile message=\n".$message);

				// If Windows, sendmail_from must be defined
				if (isset($_SERVER["WINDIR"])) {
					if (empty($this->addr_from)) {
						$this->addr_from = 'robot@example.com';
					}
					@ini_set('sendmail_from', $this->getValidAddress($this->addr_from, 2));
				}

				// Force parameters
				//dol_syslog("CMailFile::sendfile conf->global->".$keyforsmtpserver."=".getDolGlobalString($keyforsmtpserver)." cpnf->global->".$keyforsmtpport."=".$conf->global->$keyforsmtpport, LOG_DEBUG);
				if (getDolGlobalString($keyforsmtpserver)) {
					ini_set('SMTP', getDolGlobalString($keyforsmtpserver));
				}
				if (getDolGlobalString($keyforsmtpport)) {
					ini_set('smtp_port', getDolGlobalString($keyforsmtpport));
				}

				$res = true;
				if ($res && !$this->subject) {
					$this->error = "Failed to send mail with php mail to HOST=".ini_get('SMTP').", PORT=".ini_get('smtp_port')."<br>Subject is empty";
					dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);
					$res = false;
				}
				$dest = $this->getValidAddress($this->addr_to, 2);
				if ($res && !$dest) {
					$this->error = "Failed to send mail with php mail to HOST=".ini_get('SMTP').", PORT=".ini_get('smtp_port')."<br>Recipient address '$dest' invalid";
					dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);
					$res = false;
				}

				if ($res) {
					$additionnalparam = ''; // By default
					if (getDolGlobalString('MAIN_MAIL_ALLOW_SENDMAIL_F')) {
						// When using the phpmail function, the mail command may force the from to the user of the login, for example: linuxuser@myserver.mydomain.com
						// You can try to set this option to have the command use the From. if it does not work, you can also try the MAIN_MAIL_SENDMAIL_FORCE_BA.
						// So forcing using the option -f of sendmail is possible if constant MAIN_MAIL_ALLOW_SENDMAIL_F is defined.
						// Having this variable defined may create problems with some sendmail (option -f refused)
						// Having this variable not defined may create problems with some other sendmail (option -f required)
						$additionnalparam .= ($additionnalparam ? ' ' : '').(getDolGlobalString('MAIN_MAIL_ERRORS_TO') ? '-f'.$this->getValidAddress($conf->global->MAIN_MAIL_ERRORS_TO, 2) : ($this->addr_from != '' ? '-f'.$this->getValidAddress($this->addr_from, 2) : ''));
					}
					if (getDolGlobalString('MAIN_MAIL_SENDMAIL_FORCE_BA')) {    // To force usage of -ba option. This option tells sendmail to read From: or Sender: to setup sender
						$additionnalparam .= ($additionnalparam ? ' ' : '').'-ba';
					}

					if (getDolGlobalString('MAIN_MAIL_SENDMAIL_FORCE_ADDPARAM')) {
						$additionnalparam .= ($additionnalparam ? ' ' : '').'-U '.$additionnalparam; // Use -U to add additional params
					}

					$linuxlike = 1;
					if (preg_match('/^win/i', PHP_OS)) {
						$linuxlike = 0;
					}
					if (preg_match('/^mac/i', PHP_OS)) {
						$linuxlike = 0;
					}

					dol_syslog("CMailFile::sendfile: mail start".($linuxlike ? '' : " HOST=".ini_get('SMTP').", PORT=".ini_get('smtp_port')).", additionnal_parameters=".$additionnalparam, LOG_DEBUG);

					$this->message = stripslashes($this->message);

					if (getDolGlobalString('MAIN_MAIL_DEBUG')) {
						$this->dump_mail();
					}

					// Encode subject if required.
					$subjecttouse = $this->subject;
					if (!ascii_check($subjecttouse)) {
						$subjecttouse = $this->encodetorfc2822($subjecttouse);
					}

					if (!empty($additionnalparam)) {
						$res = mail($dest, $subjecttouse, $this->message, $this->headers, $additionnalparam);
					} else {
						$res = mail($dest, $subjecttouse, $this->message, $this->headers);
					}

					if (!$res) {
						$langs->load("errors");
						$this->error = "Failed to send mail with php mail";
						if (!$linuxlike) {
							$this->error .= " to HOST=".ini_get('SMTP').", PORT=".ini_get('smtp_port'); // This values are value used only for non linuxlike systems
						}
						$this->error .= ".<br>";
						$this->error .= $langs->trans("ErrorPhpMailDelivery");
						dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);

						if (getDolGlobalString('MAIN_MAIL_DEBUG')) {
							$this->save_dump_mail_in_err('Mail with topic '.$this->subject);
						}
					} else {
						dol_syslog("CMailFile::sendfile: mail end success", LOG_DEBUG);
					}
				}

				if (isset($_SERVER["WINDIR"])) {
					@ini_restore('sendmail_from');
				}

				// Restore parameters
				if (getDolGlobalString($keyforsmtpserver)) {
					ini_restore('SMTP');
				}
				if (getDolGlobalString($keyforsmtpport)) {
					ini_restore('smtp_port');
				}
			} elseif ($this->sendmode == 'smtps') {
				if (!is_object($this->smtps)) {
					$this->error = "Failed to send mail with smtps lib<br>Constructor of object CMailFile was not initialized without errors.";
					dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);
					return false;
				}

				// Use SMTPS library
				// ------------------------------------------
				$this->smtps->setTransportType(0); // Only this method is coded in SMTPs library

				// Clean parameters
				if (empty($conf->global->$keyforsmtpserver)) {
					$conf->global->$keyforsmtpserver = ini_get('SMTP');
				}
				if (empty($conf->global->$keyforsmtpport)) {
					$conf->global->$keyforsmtpport = ini_get('smtp_port');
				}

				// If we use SSL/TLS
				$server = getDolGlobalString($keyforsmtpserver);
				$secure = '';
				if (getDolGlobalString($keyfortls) && function_exists('openssl_open')) {
					$secure = 'ssl';
				}
				if (getDolGlobalString($keyforstarttls) && function_exists('openssl_open')) {
					$secure = 'tls';
				}
				$server = ($secure ? $secure.'://' : '').$server;

				$port = getDolGlobalInt($keyforsmtpport);

				$this->smtps->setHost($server);
				$this->smtps->setPort($port); // 25, 465...;

				$loginid = '';
				$loginpass = '';
				if (getDolGlobalString($keyforsmtpid)) {
					$loginid = getDolGlobalString($keyforsmtpid);
					$this->smtps->setID($loginid);
				}
				if (getDolGlobalString($keyforsmtppw)) {
					$loginpass = getDolGlobalString($keyforsmtppw);
					$this->smtps->setPW($loginpass);
				}

				if (getDolGlobalString($keyforsmtpauthtype) === "XOAUTH2") {
					require_once DOL_DOCUMENT_ROOT.'/core/lib/oauth.lib.php'; // define $supportedoauth2array

					$supportedoauth2array = getSupportedOauth2Array();

					$keyforsupportedoauth2array = getDolGlobalString($keyforsmtpoauthservice);
					if (preg_match('/^.*-/', $keyforsupportedoauth2array)) {
						$keyforprovider = preg_replace('/^.*-/', '', $keyforsupportedoauth2array);
					} else {
						$keyforprovider = '';
					}
					$keyforsupportedoauth2array = preg_replace('/-.*$/', '', $keyforsupportedoauth2array);
					$keyforsupportedoauth2array = 'OAUTH_'.$keyforsupportedoauth2array.'_NAME';

					if (!empty($supportedoauth2array)) {
						$nameofservice = ucfirst(strtolower(empty($supportedoauth2array[$keyforsupportedoauth2array]['callbackfile']) ? 'Unknown' : $supportedoauth2array[$keyforsupportedoauth2array]['callbackfile']));
						$nameofservice .= ($keyforprovider ? '-'.$keyforprovider : '');
						$OAUTH_SERVICENAME = $nameofservice;
					} else {
						$OAUTH_SERVICENAME = 'Unknown';
					}

					$keyforparamtenant = 'OAUTH_'.strtoupper(empty($supportedoauth2array[$keyforsupportedoauth2array]['callbackfile']) ? 'Unknown' : $supportedoauth2array[$keyforsupportedoauth2array]['callbackfile']).($keyforprovider ? '-'.$keyforprovider : '').'_TENANT';

					require_once DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php';

					$storage = new DoliStorage($db, $conf, $keyforprovider, getDolGlobalString($keyforparamtenant));
					try {
						$tokenobj = $storage->retrieveAccessToken($OAUTH_SERVICENAME);

						$expire = false;
						// Is token expired or will token expire in the next 30 seconds
						if (is_object($tokenobj)) {
							$expire = ($tokenobj->getEndOfLife() !== -9002 && $tokenobj->getEndOfLife() !== -9001 && time() > ($tokenobj->getEndOfLife() - 30));
						}
						// Token expired so we refresh it
						if (is_object($tokenobj) && $expire) {
							$credentials = new Credentials(
								getDolGlobalString('OAUTH_'.getDolGlobalString($keyforsmtpoauthservice).'_ID'),
								getDolGlobalString('OAUTH_'.getDolGlobalString($keyforsmtpoauthservice).'_SECRET'),
								getDolGlobalString('OAUTH_'.getDolGlobalString($keyforsmtpoauthservice).'_URLCALLBACK')
							);
							$serviceFactory = new \OAuth\ServiceFactory();
							$oauthname = explode('-', $OAUTH_SERVICENAME);
							// ex service is Google-Emails we need only the first part Google
							$apiService = $serviceFactory->createService($oauthname[0], $credentials, $storage, array());

							// We have to save the refresh token because Google give it only once
							$refreshtoken = $tokenobj->getRefreshToken();

							if ($apiService instanceof OAuth\OAuth2\Service\AbstractService || $apiService instanceof OAuth\OAuth1\Service\AbstractService) {
								// ServiceInterface does not provide refreshAccessToken, AbstractService does
								$tokenobj = $apiService->refreshAccessToken($tokenobj);
								$tokenobj->setRefreshToken($refreshtoken);	// Restore the refresh token
								$storage->storeAccessToken($OAUTH_SERVICENAME, $tokenobj);
							}

							$tokenobj = $storage->retrieveAccessToken($OAUTH_SERVICENAME);
						}

						if (is_object($tokenobj)) {
							$this->smtps->setToken($tokenobj->getAccessToken());
						} else {
							$this->error = "Token not found";
						}
					} catch (Exception $e) {
						// Return an error if token not found
						$this->error = $e->getMessage();
						dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);
					}
				}

				$res = true;
				$from = $this->smtps->getFrom('org');
				if ($res && !$from) {
					$this->error = "Failed to send mail with smtps lib to HOST=".$server.", PORT=" . getDolGlobalString($keyforsmtpport)." - Sender address '$from' invalid";
					dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);
					$res = false;
				}
				$dest = $this->smtps->getTo();
				if ($res && !$dest) {
					$this->error = "Failed to send mail with smtps lib to HOST=".$server.", PORT=" . getDolGlobalString($keyforsmtpport)." - Recipient address '$dest' invalid";
					dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);
					$res = false;
				}

				if ($res) {
					dol_syslog("CMailFile::sendfile: sendMsg, HOST=".$server.", PORT=" . getDolGlobalString($keyforsmtpport), LOG_NOTICE);

					if (getDolGlobalString('MAIN_MAIL_DEBUG')) {
						$this->smtps->setDebug(true);
					}

					$result = $this->smtps->sendMsg();

					if (getDolGlobalString('MAIN_MAIL_DEBUG')) {
						$this->dump_mail();
					}

					$smtperrorcode = 0;
					if (! $result) {
						$smtperrorcode = $this->smtps->lastretval;	// SMTP error code
						dol_syslog("CMailFile::sendfile: mail SMTP error code ".$smtperrorcode, LOG_WARNING);

						if ($smtperrorcode == '421') {	// Try later
							// TODO Add a delay and try again
							/*
							dol_syslog("CMailFile::sendfile: Try later error, so we wait and we retry");
							sleep(2);

							$result = $this->smtps->sendMsg();

							if (getDolGlobalString('MAIN_MAIL_DEBUG')) {
								$this->dump_mail();
							}
							*/
						}
					}

					$result = $this->smtps->getErrors();	// applicative error code (not SMTP error code)
					if (empty($this->error) && empty($result)) {
						dol_syslog("CMailFile::sendfile: mail end success", LOG_DEBUG);
						$res = true;
					} else {
						if (empty($this->error)) {
							$this->error = $result;
						}
						dol_syslog("CMailFile::sendfile: mail end error with smtps lib to HOST=".$server.", PORT=" . getDolGlobalString($keyforsmtpport)." - ".$this->error, LOG_ERR);
						$res = false;

						if (getDolGlobalString('MAIN_MAIL_DEBUG')) {
							$this->save_dump_mail_in_err('Mail smtp error '.$smtperrorcode.' with topic '.$this->subject);
						}
					}
				}
			} elseif ($this->sendmode == 'swiftmailer') {
				// Use Swift Mailer library
				// ------------------------------------------
				require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/lib/swift_required.php';

				// Clean parameters
				if (empty($conf->global->$keyforsmtpserver)) {
					$conf->global->$keyforsmtpserver = ini_get('SMTP');
				}
				if (empty($conf->global->$keyforsmtpport)) {
					$conf->global->$keyforsmtpport = ini_get('smtp_port');
				}

				// If we use SSL/TLS
				$server = getDolGlobalString($keyforsmtpserver);
				$secure = '';
				if (getDolGlobalString($keyfortls) && function_exists('openssl_open')) {
					$secure = 'ssl';
				}
				if (getDolGlobalString($keyforstarttls) && function_exists('openssl_open')) {
					$secure = 'tls';
				}

				$this->transport = new Swift_SmtpTransport($server, getDolGlobalInt($keyforsmtpport), $secure);

				if (getDolGlobalString($keyforsmtpid)) {
					$this->transport->setUsername(getDolGlobalString($keyforsmtpid));
				}
				if (getDolGlobalString($keyforsmtppw) && getDolGlobalString($keyforsmtpauthtype) != "XOAUTH2") {
					$this->transport->setPassword(getDolGlobalString($keyforsmtppw));
				}
				if (getDolGlobalString($keyforsmtpauthtype) === "XOAUTH2") {
					require_once DOL_DOCUMENT_ROOT.'/core/lib/oauth.lib.php';

					$supportedoauth2array = getSupportedOauth2Array();

					$keyforsupportedoauth2array = getDolGlobalString($keyforsmtpoauthservice);
					if (preg_match('/^.*-/', $keyforsupportedoauth2array)) {
						$keyforprovider = preg_replace('/^.*-/', '', $keyforsupportedoauth2array);
					} else {
						$keyforprovider = '';
					}
					$keyforsupportedoauth2array = preg_replace('/-.*$/', '', $keyforsupportedoauth2array);
					$keyforsupportedoauth2array = 'OAUTH_'.$keyforsupportedoauth2array.'_NAME';

					if (!empty($supportedoauth2array)) {
						$nameofservice = ucfirst(strtolower(empty($supportedoauth2array[$keyforsupportedoauth2array]['callbackfile']) ? 'Unknown' : $supportedoauth2array[$keyforsupportedoauth2array]['callbackfile']));
						$nameofservice .= ($keyforprovider ? '-'.$keyforprovider : '');
						$OAUTH_SERVICENAME = $nameofservice;
					} else {
						$OAUTH_SERVICENAME = 'Unknown';
					}

					$keyforparamtenant = 'OAUTH_'.strtoupper(empty($supportedoauth2array[$keyforsupportedoauth2array]['callbackfile']) ? 'Unknown' : $supportedoauth2array[$keyforsupportedoauth2array]['callbackfile']).($keyforprovider ? '-'.$keyforprovider : '').'_TENANT';

					require_once DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php';

					$storage = new DoliStorage($db, $conf, $keyforprovider, getDolGlobalString($keyforparamtenant));

					try {
						$tokenobj = $storage->retrieveAccessToken($OAUTH_SERVICENAME);

						$expire = false;
						// Is token expired or will token expire in the next 30 seconds
						if (is_object($tokenobj)) {
							$expire = ($tokenobj->getEndOfLife() !== -9002 && $tokenobj->getEndOfLife() !== -9001 && time() > ($tokenobj->getEndOfLife() - 30));
						}
						// Token expired so we refresh it
						if (is_object($tokenobj) && $expire) {
							$credentials = new Credentials(
								getDolGlobalString('OAUTH_'.getDolGlobalString($keyforsmtpoauthservice).'_ID'),
								getDolGlobalString('OAUTH_'.getDolGlobalString($keyforsmtpoauthservice).'_SECRET'),
								getDolGlobalString('OAUTH_'.getDolGlobalString($keyforsmtpoauthservice).'_URLCALLBACK')
							);
							$serviceFactory = new \OAuth\ServiceFactory();
							$oauthname = explode('-', $OAUTH_SERVICENAME);
							// ex service is Google-Emails we need only the first part Google
							$apiService = $serviceFactory->createService($oauthname[0], $credentials, $storage, array());
							$refreshtoken = $tokenobj->getRefreshToken();

							if ($apiService instanceof OAuth\OAuth2\Service\AbstractService || $apiService instanceof OAuth\OAuth1\Service\AbstractService) {
								// ServiceInterface does not provide refreshAccessToken, AbstractService does
								// We must save the token because Google provides it only once
								$tokenobj = $apiService->refreshAccessToken($tokenobj);
								$tokenobj->setRefreshToken($refreshtoken);
								$storage->storeAccessToken($OAUTH_SERVICENAME, $tokenobj);

								$tokenobj = $storage->retrieveAccessToken($OAUTH_SERVICENAME);
							}
						}

						if (is_object($tokenobj)) {
							$this->transport->setAuthMode('XOAUTH2');
							$this->transport->setPassword($tokenobj->getAccessToken());
						} else {
							$this->errors[] = "Token not found";
						}
					} catch (Exception $e) {
						// Return an error if token not found
						$this->errors[] = $e->getMessage();
						dol_syslog("CMailFile::sendfile: mail end error=".$e->getMessage(), LOG_ERR);
					}
				}
				if (getDolGlobalString($keyforsslseflsigned)) {
					$this->transport->setStreamOptions(array('ssl' => array('allow_self_signed' => true, 'verify_peer' => false)));
				}
				//$smtps->_msgReplyTo  = 'reply@web.com';

				// Switch content encoding to base64 - avoid the doubledot issue with quoted-printable
				$contentEncoderBase64 = new Swift_Mime_ContentEncoder_Base64ContentEncoder();
				$this->message->setEncoder($contentEncoderBase64);

				// Create the Mailer using your created Transport
				$this->mailer = new Swift_Mailer($this->transport);

				// DKIM SIGN
				if (getDolGlobalString('MAIN_MAIL_EMAIL_DKIM_ENABLED')) {
					$privateKey = getDolGlobalString('MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY');
					$domainName = getDolGlobalString('MAIN_MAIL_EMAIL_DKIM_DOMAIN');
					$selector = getDolGlobalString('MAIN_MAIL_EMAIL_DKIM_SELECTOR');
					$signer = new Swift_Signers_DKIMSigner($privateKey, $domainName, $selector);
					$this->message->attachSigner($signer->ignoreHeader('Return-Path'));
				}

				if (getDolGlobalString('MAIN_MAIL_DEBUG')) {
					// To use the ArrayLogger
					$this->logger = new Swift_Plugins_Loggers_ArrayLogger();
					// Or to use the Echo Logger
					//$this->logger = new Swift_Plugins_Loggers_EchoLogger();
					$this->mailer->registerPlugin(new Swift_Plugins_LoggerPlugin($this->logger));
				}

				dol_syslog("CMailFile::sendfile: mailer->send, HOST=".$server.", PORT=" . getDolGlobalString($keyforsmtpport), LOG_NOTICE);

				// send mail
				$failedRecipients = array();
				try {
					$result = $this->mailer->send($this->message, $failedRecipients);
				} catch (Exception $e) {
					$this->errors[] = $e->getMessage();
				}
				if (getDolGlobalString('MAIN_MAIL_DEBUG')) {
					$this->dump_mail();
				}

				$res = true;
				if (!empty($this->error) || !empty($this->errors) || !$result) {
					if (!empty($failedRecipients)) {
						$this->error = 'Transport failed for the following addresses: "' . implode('", "', $failedRecipients) . '".';
						$this->errors[] = $this->error;
					}
					dol_syslog("CMailFile::sendfile: mail end error=". implode(' ', $this->errors), LOG_ERR);
					$res = false;

					if (getDolGlobalString('MAIN_MAIL_DEBUG')) {
						$this->save_dump_mail_in_err('Mail with topic '.$this->subject);
					}
				} else {
					dol_syslog("CMailFile::sendfile: mail end success", LOG_DEBUG);
				}
			} else {
				// Send mail method not correctly defined
				// --------------------------------------

				$this->error = 'Bad value for sendmode';
				return false;
			}

			// Now we delete image files that were created dynamically to manage data inline files
			/* Note: dol_delete call was disabled, so code commented to not trigger empty if body
			foreach ($this->html_images as $val) {
				if (!empty($val['type']) && $val['type'] == 'cidfromdata') {
					//dol_delete($val['fullpath']);
				}
			}
			*/

			$parameters = array('sent' => $res);
			$action = '';
			$reshook = $hookmanager->executeHooks('sendMailAfter', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
			if ($reshook < 0) {
				$this->error = "Error in hook maildao sendMailAfter ".$reshook;
				dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);

				return false;
			}
		} else {
			$this->error = 'No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
			dol_syslog("CMailFile::sendfile: ".$this->error, LOG_WARNING);
		}

		error_reporting($errorlevel); // Reactive niveau erreur origine
		return $res;
	}

	/**
	 * Encode subject according to RFC 2822 - http://en.wikipedia.org/wiki/MIME#Encoded-Word
	 *
	 * @param string $stringtoencode String to encode
	 * @return string                string encoded
	 */
	public static function encodetorfc2822($stringtoencode)
	{
		global $conf;
		return '=?'.$conf->file->character_set_client.'?B?'.base64_encode($stringtoencode).'?=';
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Read a file on disk and return encoded content for emails (mode = 'mail')
	 *
	 * @param	string	$sourcefile		Path to file to encode
	 * @return 	int<-1,-1>|string		Return integer <0 if KO, encoded string if OK
	 */
	private function _encode_file($sourcefile)
	{
		// phpcs:enable
		$newsourcefile = dol_osencode($sourcefile);

		if (is_readable($newsourcefile)) {
			$contents = file_get_contents($newsourcefile); // Need PHP 4.3
			$encoded = chunk_split(base64_encode($contents), 76, $this->eol); // 76 max is defined into http://tools.ietf.org/html/rfc2047
			return $encoded;
		} else {
			$this->error = "Error in _encode_file() method: Can't read file '".$sourcefile."'";
			dol_syslog("CMailFile::_encode_file: ".$this->error, LOG_ERR);
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Write content of a SMTP request into a dump file (mode = all)
	 *  Used for debugging.
	 *  Note that to see full SMTP protocol, you can use tcpdump -w /tmp/smtp -s 2000 port 25"
	 *
	 *  @return	void
	 */
	public function dump_mail()
	{
		// phpcs:enable
		global $dolibarr_main_data_root;

		if (@is_writable($dolibarr_main_data_root)) {	// Avoid fatal error on fopen with open_basedir
			$outputfile = $dolibarr_main_data_root."/dolibarr_mail.log";
			$fp = fopen($outputfile, "w");	// overwrite

			if ($this->sendmode == 'mail') {
				fwrite($fp, $this->headers);
				fwrite($fp, $this->eol); // This eol is added by the mail function, so we add it in log
				fwrite($fp, $this->message);
			} elseif ($this->sendmode == 'smtps') {
				fwrite($fp, $this->smtps->log); // this->smtps->log is filled only if MAIN_MAIL_DEBUG was set to on
			} elseif ($this->sendmode == 'swiftmailer') {
				fwrite($fp, "smtpheader=\n".$this->message->getHeaders()->toString()."\n");
				fwrite($fp, $this->logger->dump()); // this->logger is filled only if MAIN_MAIL_DEBUG was set to on
			}

			fclose($fp);
			dolChmod($outputfile);

			// Move dolibarr_mail.log into a dolibarr_mail.log.v123456789
			if (getDolGlobalInt('MAIN_MAIL_DEBUG_LOG_WITH_DATE')) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
				archiveOrBackupFile($outputfile, getDolGlobalInt('MAIN_MAIL_DEBUG_LOG_WITH_DATE'));
			}
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Save content if mail is in error
	 *  Used for debugging.
	 *
	 *  @param	string		$message		Add also a message
	 *  @return	void
	 */
	public function save_dump_mail_in_err($message = '')
	{
		global $dolibarr_main_data_root;

		if (@is_writable($dolibarr_main_data_root)) {	// Avoid fatal error on fopen with open_basedir
			$srcfile = $dolibarr_main_data_root."/dolibarr_mail.log";

			// Add message to dolibarr_mail.log. We do not use dol_syslog() on purpose,
			// to be sure to write into dolibarr_mail.log
			if ($message) {
				// Test constant SYSLOG_FILE_NO_ERROR (should stay a constant defined with define('SYSLOG_FILE_NO_ERROR',1);
				if (defined('SYSLOG_FILE_NO_ERROR')) {
					$filefd = @fopen($srcfile, 'a+');
				} else {
					$filefd = fopen($srcfile, 'a+');
				}
				if ($filefd) {
					fwrite($filefd, $message."\n");
					fclose($filefd);
					dolChmod($srcfile);
				}
			}

			// Move dolibarr_mail.log into a dolibarr_mail.err or dolibarr_mail.date.err
			if (getDolGlobalString('MAIN_MAIL_DEBUG_ERR_WITH_DATE')) {
				$destfile = $dolibarr_main_data_root."/dolibarr_mail.".dol_print_date(dol_now(), 'dayhourlog', 'gmt').".err";
			} else {
				$destfile = $dolibarr_main_data_root."/dolibarr_mail.err";
			}

			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_move($srcfile, $destfile, '0', 1, 0, 0);
		}
	}


	/**
	 * Correct an incomplete html string
	 *
	 * @param	string	$msg	String
	 * @return	string			Completed string
	 */
	public function checkIfHTML($msg)
	{
		if (!preg_match('/^[\s\t]*<html/i', $msg)) {
			$out = "<html><head><title></title>";
			if (!empty($this->styleCSS)) {
				$out .= $this->styleCSS;
			}
			$out .= "</head><body";
			if (!empty($this->bodyCSS)) {
				$out .= $this->bodyCSS;
			}
			$out .= ">";
			$out .= $msg;
			$out .= "</body></html>";
		} else {
			$out = $msg;
		}

		return $out;
	}

	/**
	 * Build a css style (mode = all) into this->styleCSS and this->bodyCSS
	 *
	 * @return void
	 */
	public function buildCSS()
	{
		if (!empty($this->css)) {
			// Style CSS
			$this->styleCSS = '<style type="text/css">';
			$this->styleCSS .= 'body {';

			if ($this->css['bgcolor']) {
				$this->styleCSS .= '  background-color: '.$this->css['bgcolor'].';';
				$this->bodyCSS .= ' bgcolor="'.$this->css['bgcolor'].'"';
			}
			if ($this->css['bgimage']) {
				// TODO recuperer cid
				$this->styleCSS .= ' background-image: url("cid:'.$this->css['bgimage_cid'].'");';
			}
			$this->styleCSS .= '}';
			$this->styleCSS .= '</style>';
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Create SMTP headers (mode = 'mail')
	 *
	 * @return	string headers
	 */
	public function write_smtpheaders()
	{
		// phpcs:enable
		$out = "";
		$host = dol_getprefix('email');

		// Sender
		//$out.= "Sender: ".getValidAddress($this->addr_from,2)).$this->eol2;
		$out .= "From: ".$this->getValidAddress($this->addr_from, 3, 1).$this->eol2;
		if (getDolGlobalString('MAIN_MAIL_SENDMAIL_FORCE_BA')) {
			$out .= "To: ".$this->getValidAddress($this->addr_to, 0, 1).$this->eol2;
		}
		if (!getDolGlobalString('MAIN_MAIL_NO_RETURN_PATH_FOR_MODE_MAIL')) {
			// Return-Path is important because it is used by SPF. Some command line MTA overwrites the Return-Path, even if already in the
			// SMTP header, with a value guessed by command line tool. See option MAIN_MAIL_ALLOW_SENDMAIL_F to provide email to the command line tool.
			// Return-Path is used for bounced emails. If not set (most cases), the From is used.
			$out .= "Return-Path: ".$this->getValidAddress($this->addr_from, 1, 1).$this->eol2;
		}
		if (isset($this->reply_to) && $this->reply_to) {
			$out .= "Reply-To: ".$this->getValidAddress($this->reply_to, 2).$this->eol2;
		}
		if (isset($this->errors_to) && $this->errors_to) {
			$out .= "Errors-To: ".$this->getValidAddress($this->errors_to, 2).$this->eol2;
		}

		// Receiver
		if (isset($this->addr_cc) && $this->addr_cc) {
			$out .= "Cc: ".$this->getValidAddress($this->addr_cc, 2).$this->eol2;
		}
		if (isset($this->addr_bcc) && $this->addr_bcc) {
			$out .= "Bcc: ".$this->getValidAddress($this->addr_bcc, 2).$this->eol2; // TODO Question: bcc must not be into header, only into SMTP command "RCPT TO". Does php mail support this ?
		}

		// Delivery receipt
		if (isset($this->deliveryreceipt) && $this->deliveryreceipt == 1) {
			$out .= "Disposition-Notification-To: ".$this->getValidAddress($this->addr_from, 2).$this->eol2;
		}

		//$out.= "X-Priority: 3".$this->eol2;
		$out .= 'Date: '.date("r").$this->eol2;

		$trackid = $this->trackid;
		if ($trackid) {
			$this->msgid = time().'.phpmail-dolibarr-'.$trackid.'@'.$host;
			$out .= 'Message-ID: <'.$this->msgid.">".$this->eol2; // Uppercase seems replaced by phpmail
			$out .= 'X-Dolibarr-TRACKID: '.$trackid.'@'.$host.$this->eol2;
		} else {
			$this->msgid = time().'.phpmail@'.$host;
			$out .= 'Message-ID: <'.$this->msgid.">".$this->eol2;
		}

		// Add 'In-Reply-To:' header with the Message-Id we answer
		if (!empty($this->in_reply_to)) {
			$out .= 'In-Reply-To: <'.$this->in_reply_to.'>'.$this->eol2;
		}
		// Add 'References:' header with list of all Message-ID in thread history
		if (!empty($this->references)) {
			$out .= 'References: '.$this->references.$this->eol2;
		}

		if (!empty($_SERVER['REMOTE_ADDR'])) {
			$out .= "X-RemoteAddr: ".$_SERVER['REMOTE_ADDR'].$this->eol2;
		}
		$out .= "X-Mailer: Dolibarr version ".DOL_VERSION." (using php mail)".$this->eol2;
		$out .= "Mime-Version: 1.0".$this->eol2;

		//$out.= "From: ".$this->getValidAddress($this->addr_from,3,1).$this->eol;

		$out .= "Content-Type: multipart/mixed;".$this->eol2." boundary=\"".$this->mixed_boundary."\"".$this->eol2;
		$out .= "Content-Transfer-Encoding: 8bit".$this->eol2; // TODO Seems to be ignored. Header is 7bit once received.

		dol_syslog("CMailFile::write_smtpheaders smtp_header=\n".$out, LOG_DEBUG);
		return $out;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Create header MIME (mode = 'mail')
	 *
	 * @param	string[]	$filename_list			Array of filenames
	 * @param 	string[]	$mimefilename_list		Array of mime types
	 * @return	string							mime headers
	 */
	public function write_mimeheaders($filename_list, $mimefilename_list)
	{
		// phpcs:enable
		$mimedone = 0;
		$out = "";

		if (is_array($filename_list)) {
			$filename_list_size = count($filename_list);
			for ($i = 0; $i < $filename_list_size; $i++) {
				if ($filename_list[$i]) {
					if ($mimefilename_list[$i]) {
						$filename_list[$i] = $mimefilename_list[$i];
					}
					$out .= "X-attachments: $filename_list[$i]".$this->eol2;
				}
			}
		}

		dol_syslog("CMailFile::write_mimeheaders mime_header=\n".$out, LOG_DEBUG);
		return $out;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return email content (mode = 'mail')
	 *
	 * @param	string		$msgtext		Message string
	 * @return	string						String content
	 */
	public function write_body($msgtext)
	{
		// phpcs:enable
		global $conf;

		$out = '';

		$out .= "--".$this->mixed_boundary.$this->eol;

		if ($this->atleastoneimage) {
			$out .= "Content-Type: multipart/alternative;".$this->eol." boundary=\"".$this->alternative_boundary."\"".$this->eol;
			$out .= $this->eol;
			$out .= "--".$this->alternative_boundary.$this->eol;
		}

		// Make RFC821 Compliant, replace bare linefeeds
		$strContent = preg_replace("/(?<!\r)\n/si", "\r\n", $msgtext); // PCRE modifier /s means new lines are common chars
		if (getDolGlobalString('MAIN_FIX_FOR_BUGGED_MTA')) {
			$strContent = preg_replace("/\r\n/si", "\n", $strContent); // PCRE modifier /s means new lines are common chars
		}

		$strContentAltText = '';
		if ($this->msgishtml) {
			// Similar code to forge a text from html is also in smtps.class.php
			$strContentAltText = preg_replace("/<br\s*[^>]*>/", " ", $strContent);
			// TODO We could replace <img ...> with [Filename.ext] like Gmail do.
			$strContentAltText = html_entity_decode(strip_tags($strContentAltText));	// Remove any HTML tags
			$strContentAltText = trim(wordwrap($strContentAltText, 75, !getDolGlobalString('MAIN_FIX_FOR_BUGGED_MTA') ? "\r\n" : "\n"));

			// Check if html header already in message, if not complete the message
			$strContent = $this->checkIfHTML($strContent);		// This add a header and a body including custom CSS to the HTML content
		}

		// Make RFC2045 Compliant, split lines
		//$strContent = rtrim(chunk_split($strContent));    // Function chunck_split seems ko if not used on a base64 content
		// TODO Encode main content into base64 and use the chunk_split, or quoted-printable
		$strContent = rtrim(wordwrap($strContent, 75, !getDolGlobalString('MAIN_FIX_FOR_BUGGED_MTA') ? "\r\n" : "\n")); // TODO Using this method creates unexpected line break on text/plain content.

		if ($this->msgishtml) {
			if ($this->atleastoneimage) {
				$out .= "Content-Type: text/plain; charset=".$conf->file->character_set_client.$this->eol;
				//$out.= "Content-Transfer-Encoding: 7bit".$this->eol;
				$out .= $this->eol.($strContentAltText ? $strContentAltText : strip_tags($strContent)).$this->eol; // Add plain text message
				$out .= "--".$this->alternative_boundary.$this->eol;
				$out .= "Content-Type: multipart/related;".$this->eol." boundary=\"".$this->related_boundary."\"".$this->eol;
				$out .= $this->eol;
				$out .= "--".$this->related_boundary.$this->eol;
			}

			if (!$this->atleastoneimage && $strContentAltText && getDolGlobalString('MAIN_MAIL_USE_MULTI_PART')) {    // Add plain text message part before html part
				$out .= "Content-Type: multipart/alternative;".$this->eol." boundary=\"".$this->alternative_boundary."\"".$this->eol;
				$out .= $this->eol;
				$out .= "--".$this->alternative_boundary.$this->eol;
				$out .= "Content-Type: text/plain; charset=".$conf->file->character_set_client.$this->eol;
				//$out.= "Content-Transfer-Encoding: 7bit".$this->eol;
				$out .= $this->eol.$strContentAltText.$this->eol;
				$out .= "--".$this->alternative_boundary.$this->eol;
			}

			$out .= "Content-Type: text/html; charset=".$conf->file->character_set_client.$this->eol;
			//$out.= "Content-Transfer-Encoding: 7bit".$this->eol;	// TODO Use base64
			$out .= $this->eol.$strContent.$this->eol;

			if (!$this->atleastoneimage && $strContentAltText && getDolGlobalString('MAIN_MAIL_USE_MULTI_PART')) {    // Add plain text message part after html part
				$out .= "--".$this->alternative_boundary."--".$this->eol;
			}
		} else {
			$out .= "Content-Type: text/plain; charset=".$conf->file->character_set_client.$this->eol;
			//$out.= "Content-Transfer-Encoding: 7bit".$this->eol;
			$out .= $this->eol.$strContent.$this->eol;
		}

		$out .= $this->eol;

		// Encode images
		if ($this->atleastoneimage) {
			$out .= $this->write_images($this->images_encoded);
			// always end related and end alternative after inline images
			$out .= "--".$this->related_boundary."--".$this->eol;
			$out .= $this->eol."--".$this->alternative_boundary."--".$this->eol;
			$out .= $this->eol;
		}

		return $out;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Attach file to email (mode = 'mail')
	 *
	 * @param	string[]	$filename_list		Tableau
	 * @param	string[]	$mimetype_list		Tableau
	 * @param 	string[]	$mimefilename_list	Tableau
	 * @param	?string[]	$cidlist			Array of CID if file must be completed with CID code
	 * @return	string|int<-1,-1>	 			String with files encoded or -1 when error
	 */
	private function write_files($filename_list, $mimetype_list, $mimefilename_list, $cidlist)
	{
		// phpcs:enable
		$out = '';

		$filename_list_size = count($filename_list);
		for ($i = 0; $i < $filename_list_size; $i++) {
			if ($filename_list[$i]) {
				dol_syslog("CMailFile::write_files: i=$i ".$filename_list[$i]);
				$encoded = $this->_encode_file($filename_list[$i]);
				if ($encoded !== -1) {
					if ($mimefilename_list[$i]) {
						$filename_list[$i] = $mimefilename_list[$i];
					}
					if (!$mimetype_list[$i]) {
						$mimetype_list[$i] = "application/octet-stream";
					}

					$out .= "--".$this->mixed_boundary.$this->eol;
					$out .= "Content-Disposition: attachment; filename=\"".$filename_list[$i]."\"".$this->eol;
					$out .= "Content-Type: ".$mimetype_list[$i]."; name=\"".$filename_list[$i]."\"".$this->eol;
					$out .= "Content-Transfer-Encoding: base64".$this->eol;
					$out .= "Content-Description: ".$filename_list[$i].$this->eol;
					if (!empty($cidlist) && is_array($cidlist) && $cidlist[$i]) {
						$out .= "X-Attachment-Id: ".$cidlist[$i].$this->eol;
						$out .= "Content-ID: <".$cidlist[$i].'>'.$this->eol;
					}
					$out .= $this->eol;
					$out .= $encoded;
					$out .= $this->eol;
					//$out.= $this->eol;
				} else {
					return $encoded;
				}
			}
		}

		return $out;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Attach an image to email (mode = 'mail')
	 *
	 * @param	array<array{name:string,fullpath:string,content_type:string,cid:string,image_encoded:string}>	$images_list	Array of array image
	 * @return	string					Chaine images encodees
	 */
	public function write_images($images_list)
	{
		// phpcs:enable
		$out = '';

		if (is_array($images_list)) {
			foreach ($images_list as $img) {
				dol_syslog("CMailFile::write_images: ".$img["name"]);

				$out .= "--".$this->related_boundary.$this->eol; // always related for an inline image
				$out .= "Content-Type: ".$img["content_type"]."; name=\"".$img["name"]."\"".$this->eol;
				$out .= "Content-Transfer-Encoding: base64".$this->eol;
				$out .= "Content-Disposition: inline; filename=\"".$img["name"]."\"".$this->eol;
				$out .= "Content-ID: <".$img["cid"].">".$this->eol;
				$out .= $this->eol;
				$out .= $img["image_encoded"];
				$out .= $this->eol;
			}
		}

		return $out;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Try to create a socket connection
	 *
	 * @param 	string		$host		Add ssl:// for SSL/TLS.
	 * @param 	int			$port		Example: 25, 465
	 * @return	int						Socket id if ok, 0 if KO
	 */
	public function check_server_port($host, $port)
	{
		// phpcs:enable
		global $conf;

		$_retVal = 0;
		$timeout = 5; // Timeout in seconds

		if (function_exists('fsockopen')) {
			$keyforsmtpserver = 'MAIN_MAIL_SMTP_SERVER';
			$keyforsmtpport  = 'MAIN_MAIL_SMTP_PORT';
			$keyforsmtpid    = 'MAIN_MAIL_SMTPS_ID';
			$keyforsmtppw    = 'MAIN_MAIL_SMTPS_PW';
			$keyforsmtpauthtype = 'MAIN_MAIL_SMTPS_AUTH_TYPE';
			$keyforsmtpoauthservice = 'MAIN_MAIL_SMTPS_OAUTH_SERVICE';
			$keyfortls       = 'MAIN_MAIL_EMAIL_TLS';
			$keyforstarttls  = 'MAIN_MAIL_EMAIL_STARTTLS';
			$keyforsslseflsigned = 'MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED';

			if (!empty($this->sendcontext)) {
				$smtpContextKey = strtoupper($this->sendcontext);
				$smtpContextSendMode = getDolGlobalString('MAIN_MAIL_SENDMODE_'.$smtpContextKey);
				if (!empty($smtpContextSendMode) && $smtpContextSendMode != 'default') {
					$keyforsmtpserver = 'MAIN_MAIL_SMTP_SERVER_'.$smtpContextKey;
					$keyforsmtpport   = 'MAIN_MAIL_SMTP_PORT_'.$smtpContextKey;
					$keyforsmtpid     = 'MAIN_MAIL_SMTPS_ID_'.$smtpContextKey;
					$keyforsmtppw     = 'MAIN_MAIL_SMTPS_PW_'.$smtpContextKey;
					$keyforsmtpauthtype = 'MAIN_MAIL_SMTPS_AUTH_TYPE_'.$smtpContextKey;
					$keyforsmtpoauthservice = 'MAIN_MAIL_SMTPS_OAUTH_SERVICE_'.$smtpContextKey;
					$keyfortls        = 'MAIN_MAIL_EMAIL_TLS_'.$smtpContextKey;
					$keyforstarttls   = 'MAIN_MAIL_EMAIL_STARTTLS_'.$smtpContextKey;
					$keyforsslseflsigned = 'MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED_'.$smtpContextKey;
				}
			}

			// If we use SSL/TLS
			if (getDolGlobalString($keyfortls) && function_exists('openssl_open')) {
				$host = 'ssl://'.$host;
			}
			// tls smtp start with no encryption
			//if (getDolGlobalString('MAIN_MAIL_EMAIL_STARTTLS') && function_exists('openssl_open')) $host='tls://'.$host;

			dol_syslog("Try socket connection to host=".$host." port=".$port." timeout=".$timeout);
			//See if we can connect to the SMTP server
			$errno = 0;
			$errstr = '';
			if ($socket = @fsockopen(
				$host, // Host to test, IP or domain. Add ssl:// for SSL/TLS.
				$port, // which Port number to use
				$errno, // actual system level error
				$errstr, // and any text that goes with the error
				$timeout     // timeout for reading/writing data over the socket
			)) {
				// Windows still does not have support for this timeout function
				if (function_exists('stream_set_timeout')) {
					stream_set_timeout($socket, $timeout, 0);
				}

				dol_syslog("Now we wait for answer 220");

				// Check response from Server
				if ($_retVal = $this->server_parse($socket, "220")) {
					$_retVal = $socket;
				} else {
					$this->error = ($this->error ? $this->error." - " : "")."Succeed in opening socket but answer 220 not received";
				}
			} else {
				$this->error = utf8_check('Error '.$errno.' - '.$errstr) ? 'Error '.$errno.' - '.$errstr : mb_convert_encoding('Error '.$errno.' - '.$errstr, 'UTF-8', 'ISO-8859-1');
			}
		}

		return $_retVal;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * This function has been modified as provided by SirSir to allow multiline responses when
	 * using SMTP Extensions.
	 *
	 * @param	resource	$socket			Socket
	 * @param   string	    $response		Response string
	 * @return  boolean		      			true if success
	 */
	public function server_parse($socket, $response)
	{
		// phpcs:enable
		$_retVal = true; // Indicates if Object was created or not
		$server_response = '';

		while (substr($server_response, 3, 1) != ' ') {
			if (!($server_response = fgets($socket, 256))) {
				$this->error = "Couldn't get mail server response codes";
				return false;
			}
		}

		if (!(substr($server_response, 0, 3) == $response)) {
			$this->error = "Ran into problems sending Mail.\r\nResponse: $server_response";
			$_retVal = false;
		}

		return $_retVal;
	}

	/**
	 * Search images into html message and init array this->images_encoded if found
	 *
	 * @param	string	$images_dir		Path to store physical images files. For example $dolibarr_main_data_root.'/medias'
	 * @return	int 		        	>0 if OK, <0 if KO
	 */
	private function findHtmlImages($images_dir)
	{
		// Build the array of image extensions
		$extensions = array_keys($this->image_types);

		// We search (into mail body this->html), if we find some strings like "... file=xxx.img"
		// For example when:
		// <img alt="" src="/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/picture.jpg" style="height:356px; width:1040px" />
		$matches = array();
		preg_match_all('/(?:"|\')([^"\']+\.('.implode('|', $extensions).'))(?:"|\')/Ui', $this->html, $matches); // If "xxx.ext" or 'xxx.ext' found

		if (!empty($matches) && !empty($matches[1])) {
			$i = 0;
			// We are interested in $matches[1] only (the second set of parenthesis into regex)
			foreach ($matches[1] as $full) {
				$regs = array();
				if (preg_match('/file=([A-Za-z0-9_\-\/]+[\.]?[A-Za-z0-9]+)?$/i', $full, $regs)) {   // If xxx is 'file=aaa'
					$img = $regs[1];

					if (file_exists($images_dir.'/'.$img)) {
						// Image path in src
						$src = preg_quote($full, '/');
						// Image full path
						$this->html_images[$i]["fullpath"] = $images_dir.'/'.$img;
						// Image name
						$this->html_images[$i]["name"] = $img;
						// Content type
						$regext = array();
						if (preg_match('/^.+\.(\w{3,4})$/', $img, $regext)) {
							$ext = strtolower($regext[1]);
							$this->html_images[$i]["content_type"] = $this->image_types[$ext];
						}
						// cid
						$this->html_images[$i]["cid"] = dol_hash($this->html_images[$i]["fullpath"], 'md5'); // Force md5 hash (does not contain special chars)
						// type
						$this->html_images[$i]["type"] = 'cidfromurl';

						$this->html = preg_replace("/src=\"$src\"|src='$src'/i", "src=\"cid:".$this->html_images[$i]["cid"]."\"", $this->html);
					}
					$i++;
				}
			}

			if (!empty($this->html_images)) {
				$inline = array();

				$i = 0;

				foreach ($this->html_images as $img) {
					$fullpath = $images_dir.'/'.$img["name"];

					// If duplicate images are embedded, they may show up as attachments, so remove them.
					if (!in_array($fullpath, $inline)) {
						// Read image file
						if ($image = file_get_contents($fullpath)) {
							// On garde que le nom de l'image
							$regs = array();
							preg_match('/([A-Za-z0-9_-]+[\.]?[A-Za-z0-9]+)?$/i', $img["name"], $regs);
							$imgName = $regs[1];
							$this->images_encoded[$i]['name'] = $imgName;
							$this->images_encoded[$i]['fullpath'] = $fullpath;
							$this->images_encoded[$i]['content_type'] = $img["content_type"];
							$this->images_encoded[$i]['cid'] = $img["cid"];
							// Encodage de l'image
							$this->images_encoded[$i]["image_encoded"] = chunk_split(base64_encode($image), 68, $this->eol);
							$inline[] = $fullpath;
						}
					}
					$i++;
				}
			} else {
				return -1;
			}

			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * Seearch images with data:image format into html message.
	 * If we find some, we create it on disk.
	 *
	 * @param	string	$images_dir		Location of where to store physically images files. For example $dolibarr_main_data_root.'/medias'
	 * @return	int 		        	>0 if OK, <0 if KO
	 */
	private function findHtmlImagesIsSrcData($images_dir)
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		// Build the array of image extensions
		$extensions = array_keys($this->image_types);

		if (empty($images_dir)) {
			//$images_dir = $conf->admin->dir_output.'/temp/'.uniqid('cmailfile');
			$images_dir = $conf->admin->dir_output.'/temp/cmailfile';
		}

		if ($images_dir && !dol_is_dir($images_dir)) {
			dol_mkdir($images_dir, DOL_DATA_ROOT);
		}

		// Uncomment this for debug
		/*
		global $dolibarr_main_data_root;
		$outputfile = $dolibarr_main_data_root."/dolibarr_mail.log";
		$fp = fopen($outputfile, "w+");
		fwrite($fp, $this->html);
		fclose($fp);
		*/

		// We search (into mail body this->html), if we find some strings like "... file=xxx.img"
		// For example when:
		// <img alt="" src="/src="data:image....;base64,...." />
		$matches = array();
		preg_match_all('/src="data:image\/('.implode('|', $extensions).');base64,([^"]+)"/Ui', $this->html, $matches); // If "xxx.ext" or 'xxx.ext' found

		if (!empty($matches) && !empty($matches[1])) {
			if (empty($images_dir)) {
				// No temp directory provided, so we are not able to support conversion of data:image into physical images.
				$this->errors[] = 'NoTempDirProvidedInCMailConstructorSoCantConvertDataImgOnDisk';
				return -1;
			}

			$i = count($this->html_images);
			foreach ($matches[1] as $key => $ext) {
				// We save the image to send in disk
				$filecontent = $matches[2][$key];

				$cid = 'cid000'.dol_hash($filecontent, 'md5');		// The id must not change if image is same

				$destfiletmp = $images_dir.'/'.$cid.'.'.$ext;

				if (!dol_is_file($destfiletmp)) {	// If file does not exist yet (this is the case for the first email sent with a data:image inside)
					dol_syslog("write the cid file ".$destfiletmp);
					$fhandle = @fopen($destfiletmp, 'w');
					if ($fhandle) {
						$nbofbyteswrote = fwrite($fhandle, base64_decode($filecontent));
						fclose($fhandle);
						dolChmod($destfiletmp);
					} else {
						$this->errors[] = "Failed to open file '".$destfiletmp."' for write";
						return -2;
					}
				}

				if (file_exists($destfiletmp)) {
					// Image full path
					$this->html_images[$i]["fullpath"] = $destfiletmp;
					// Image name
					$this->html_images[$i]["name"] = basename($destfiletmp);
					// Content type
					$this->html_images[$i]["content_type"] = $this->image_types[strtolower($ext)];
					// cid
					$this->html_images[$i]["cid"] = $cid;
					// type
					$this->html_images[$i]["type"] = 'cidfromdata';

					$this->html = str_replace('src="data:image/'.$ext.';base64,'.$filecontent.'"', 'src="cid:'.$this->html_images[$i]["cid"].'"', $this->html);
				}
				$i++;
			}

			return 1;
		} else {
			return 0;
		}
	}

	/**
	 * Return a formatted address string for SMTP protocol
	 *
	 * @param	string		$address		     Example: 'John Doe <john@doe.com>, Alan Smith <alan@smith.com>' or 'john@doe.com, alan@smith.com'
	 * @param	int			$format			     0=auto, 1=emails with <>, 2=emails without <>, 3=auto + label between ", 4 label or email, 5 mailto link
	 * @param	int			$encode			     0=No encode name, 1=Encode name to RFC2822
	 * @param   int         $maxnumberofemail    0=No limit. Otherwise, maximum number of emails returned ($address may contains several email separated with ','). Add '...' if there is more.
	 * @return	string						     If format 0: '<john@doe.com>' or 'John Doe <john@doe.com>' or '=?UTF-8?B?Sm9obiBEb2U=?= <john@doe.com>'
	 * 										     If format 1: '<john@doe.com>'
	 *										     If format 2: 'john@doe.com'
	 *										     If format 3: '<john@doe.com>' or '"John Doe" <john@doe.com>' or '"=?UTF-8?B?Sm9obiBEb2U=?=" <john@doe.com>'
	 *                                           If format 4: 'John Doe' or 'john@doe.com' if no label exists
	 *                                           If format 5: <a href="mailto:john@doe.com">John Doe</a> or <a href="mailto:john@doe.com">john@doe.com</a> if no label exists
	 * @see getArrayAddress()
	 */
	public static function getValidAddress($address, $format, $encode = 0, $maxnumberofemail = 0)
	{
		$ret = '';

		$arrayaddress = (!empty($address) ? explode(',', $address) : array());

		// Boucle sur chaque composant de l'address
		$i = 0;
		foreach ($arrayaddress as $val) {
			$regs = array();
			if (preg_match('/^(.*)<(.*)>$/i', trim($val), $regs)) {
				$name  = trim($regs[1]);
				$email = trim($regs[2]);
			} else {
				$name  = '';
				$email = trim($val);
			}

			if ($email) {
				$i++;

				$newemail = '';
				if ($format == 5) {
					$newemail = $name ? $name : $email;
					$newemail = '<a href="mailto:'.$email.'">'.$newemail.'</a>';
				}
				if ($format == 4) {
					$newemail = $name ? $name : $email;
				}
				if ($format == 2) {
					$newemail = $email;
				}
				if ($format == 1 || $format == 3) {
					$newemail = '<'.$email.'>';
				}
				if ($format == 0 || $format == 3) {
					if (getDolGlobalString('MAIN_MAIL_NO_FULL_EMAIL')) {
						$newemail = '<'.$email.'>';
					} elseif (!$name) {
						$newemail = '<'.$email.'>';
					} else {
						$newemail = ($format == 3 ? '"' : '').($encode ? self::encodetorfc2822($name) : $name).($format == 3 ? '"' : '').' <'.$email.'>';
						//$newemail = (($format == 3 && !$encode) ? '"' : '').($encode ? self::encodetorfc2822($name) : $name).(($format == 3 && !$encode) ? '"' : '').' <'.$email.'>';
					}
				}

				$ret = ($ret ? $ret.',' : '').$newemail;

				// Stop if we have too much records
				if ($maxnumberofemail && $i >= $maxnumberofemail) {
					if (count($arrayaddress) > $maxnumberofemail) {
						$ret .= '...';
					}
					break;
				}
			}
		}

		return $ret;
	}

	/**
	 * Return a formatted array of address string for SMTP protocol
	 *
	 * @param   string	$address		Example: 'John Doe <john@doe.com>, Alan Smith <alan@smith.com>' or 'john@doe.com, alan@smith.com'
	 * @return  array<string,?string>		array(email => name)
	 * @see getValidAddress()
	 */
	public static function getArrayAddress($address)
	{
		$ret = array();

		$arrayaddress = explode(',', $address);

		// Boucle sur chaque composant de l'address
		foreach ($arrayaddress as $val) {
			$regs = array();
			if (preg_match('/^(.*)<(.*)>$/i', trim($val), $regs)) {
				$name  = trim($regs[1]);
				$email = trim($regs[2]);
			} else {
				$name  = null;
				$email = trim($val);
			}

			$ret[$email] = getDolGlobalString('MAIN_MAIL_NO_FULL_EMAIL') ? null : $name;
		}

		return $ret;
	}
}
