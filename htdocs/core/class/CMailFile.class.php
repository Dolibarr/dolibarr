<?php
/**
 * Copyright (C)           Dan Potter
 * Copyright (C)           Eric Seigne
 * Copyright (C) 2000-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * Lots of code inspired from Dan Potter's CMailFile class
 */

/**
 *      \file       htdocs/core/class/CMailFile.class.php
 *      \brief      File of class to send emails (with attachments or not)
 */

/**
 *	Class to send emails (with attachments or not)
 *  Usage: $mailfile = new CMailFile($subject,$sendto,$replyto,$message,$filepath,$mimetype,$filename,$cc,$ccc,$deliveryreceipt,$msgishtml,$errors_to,$css,$trackid,$moreinheader,$sendcontext);
 *         $mailfile->sendfile();
 */
class CMailFile
{
	public $sendcontext;
	public $sendmode;
	public $sendsetup;

	var $subject;      	// Topic:       Subject of email
	var $addr_from;    	// From:		Label and EMail of sender (must include '<>'). For example '<myemail@example.com>' or 'John Doe <myemail@example.com>' or '<myemail+trackingid@example.com>'). Note that with gmail smtps, value here is forced by google to account (but not the reply-to).
	// Sender:      Who send the email ("Sender" has sent emails on behalf of "From").
	//              Use it when the "From" is an email of a domain that is a SPF protected domain, and sending smtp server is not this domain. In such case, add Sender field with an email of the protected domain.
	// Return-Path: Email where to send bounds.
	var $reply_to;		// Reply-To:	Email where to send replies from mailer software (mailer use From if reply-to not defined, Gmail use gmail account if reply-to not defined)
	var $errors_to;		// Errors-To:	Email where to send errors.
	var $addr_to;
	var $addr_cc;
	var $addr_bcc;
	var $trackid;

	var $mixed_boundary;
	var $related_boundary;
	var $alternative_boundary;
	var $deliveryreceipt;

	var $eol;
	var $eol2;
	var $error='';

	var $smtps;			// Contains SMTPs object (if this method is used)
	var $phpmailer;		// Contains PHPMailer object (if this method is used)

	//CSS
	var $css;
	//! Defined css style for body background
	var $styleCSS;
	//! Defined background directly in body tag
	var $bodyCSS;

	var $headers;
	var $message;

	// Image
	var $html;
	var $image_boundary;
	var $atleastoneimage=0;    // at least one image file with file=xxx.ext into content (TODO Debug this. How can this case be tested. Remove if not used).
	var $html_images=array();
	var $images_encoded=array();
	var $image_types = array(
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
	 *	@param 	array	$filename_list       List of files to attach (full path of filename on file system)
	 *	@param 	array	$mimetype_list       List of MIME type of attached files
	 *	@param 	array	$mimefilename_list   List of attached file name in message
	 *	@param 	string	$addr_cc             Email cc
	 *	@param 	string	$addr_bcc            Email bcc (Note: This is autocompleted with MAIN_MAIL_AUTOCOPY_TO if defined)
	 *	@param 	int		$deliveryreceipt     Ask a delivery receipt
	 *	@param 	int		$msgishtml           1=String IS already html, 0=String IS NOT html, -1=Unknown make autodetection (with fast mode, not reliable)
	 *	@param 	string	$errors_to      	 Email for errors-to
	 *	@param	string	$css                 Css option
	 *	@param	string	$trackid             Tracking string (contains type and id of related element)
	 *  @param  string  $moreinheader        More in header. $moreinheader must contains the "\r\n" (TODO not supported for other MAIL_SEND_MODE different than 'phpmail' and 'smtps' for the moment)
	 *  @param  string  $sendcontext      	 'standard', 'emailing', ... (used to define with sending mode and parameters to use)
	 *  @param	string	$replyto			 Reply-to email (will be set to same value than From by default if not provided)
	 */
	function __construct($subject,$to,$from,$msg,$filename_list=array(),$mimetype_list=array(),$mimefilename_list=array(),$addr_cc="",$addr_bcc="",$deliveryreceipt=0,$msgishtml=0,$errors_to='',$css='',$trackid='',$moreinheader='',$sendcontext='standard',$replyto='')
	{
		global $conf, $dolibarr_main_data_root;

		$this->sendcontext = $sendcontext;

		if (empty($replyto)) $replyto=$from;

		// Define this->sendmode
		$this->sendmode = '';
		if ($this->sendcontext == 'emailing' && !empty($conf->global->MAIN_MAIL_SENDMODE_EMAILING) && $conf->global->MAIN_MAIL_SENDMODE_EMAILING != 'default')
		{
			$this->sendmode = $conf->global->MAIN_MAIL_SENDMODE_EMAILING;
		}
		if (empty($this->sendmode)) $this->sendmode=$conf->global->MAIN_MAIL_SENDMODE;
		if (empty($this->sendmode)) $this->sendmode='mail';

		// We define end of line (RFC 821).
		$this->eol="\r\n";
		// We define end of line for header fields (RFC 822bis section 2.3 says header must contains \r\n).
		$this->eol2="\r\n";
		if (! empty($conf->global->MAIN_FIX_FOR_BUGGED_MTA))
		{
			$this->eol="\n";
			$this->eol2="\n";
			$moreinheader = str_replace("\r\n","\n",$moreinheader);
		}

		// On defini mixed_boundary
		$this->mixed_boundary = "multipart_x." . time() . ".x_boundary";

		// On defini related_boundary
		$this->related_boundary = 'mul_'.dol_hash(uniqid("dolibarr2"), 3);	// Force md5 hash (does not contains special chars)

		// On defini alternative_boundary
		$this->alternative_boundary = 'mul_'.dol_hash(uniqid("dolibarr3"), 3);	// Force md5 hash (does not contains special chars)

		dol_syslog("CMailFile::CMailfile: sendmode=".$this->sendmode." charset=".$conf->file->character_set_client." from=$from, to=$to, addr_cc=$addr_cc, addr_bcc=$addr_bcc, errors_to=$errors_to, trackid=$trackid sendcontext=$sendcontext", LOG_DEBUG);
		dol_syslog("CMailFile::CMailfile: subject=".$subject.", deliveryreceipt=".$deliveryreceipt.", msgishtml=".$msgishtml, LOG_DEBUG);

		if (empty($subject))
		{
			dol_syslog("CMailFile::CMailfile: Try to send an email with empty subject");
			$this->error='ErrorSubjectIsRequired';
			return;
		}
		if (empty($msg))
		{
		    dol_syslog("CMailFile::CMailfile: Try to send an email with empty body");
		    $msg='.';     // Avoid empty message (with empty message conten show a multipart structure)
		}

		// Detect if message is HTML (use fast method)
		if ($msgishtml == -1)
		{
			$this->msgishtml = 0;
			if (dol_textishtml($msg)) $this->msgishtml = 1;
		}
		else
		{
			$this->msgishtml = $msgishtml;
		}

		global $dolibarr_main_url_root;

		// Define $urlwithroot
		$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
		$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
		//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

		// Replace relative /viewimage to absolute path
		$msg = preg_replace('/src="'.preg_quote(DOL_URL_ROOT,'/').'\/viewimage\.php/ims', 'src="'.$urlwithroot.'/viewimage.php', $msg, -1, $nbrep);

		if (! empty($conf->global->MAIN_MAIL_FORCE_CONTENT_TYPE_TO_HTML)) $this->msgishtml=1; // To force to send everything with content type html.

		// Detect images
		if ($this->msgishtml)
		{
			$this->html = $msg;

			if (! empty($conf->global->MAIN_MAIL_ADD_INLINE_IMAGES_IF_IN_MEDIAS))
			{
				$findimg = $this->findHtmlImages($dolibarr_main_data_root.'/medias');
			}

			// Define if there is at least one file
			if ($findimg)
			{
				foreach ($this->html_images as $i => $val)
				{
					if ($this->html_images[$i])
					{
						$this->atleastoneimage=1;
						dol_syslog("CMailFile::CMailfile: html_images[$i]['name']=".$this->html_images[$i]['name'], LOG_DEBUG);
					}
				}
			}
		}

		// Define if there is at least one file
		foreach ($filename_list as $i => $val)
		{
			if ($filename_list[$i])
			{
				$this->atleastonefile=1;
				dol_syslog("CMailFile::CMailfile: filename_list[$i]=".$filename_list[$i].", mimetype_list[$i]=".$mimetype_list[$i]." mimefilename_list[$i]=".$mimefilename_list[$i], LOG_DEBUG);
			}
		}

		// Add autocopy to (Note: Adding bcc for specific modules are also done from pages)
		if (! empty($conf->global->MAIN_MAIL_AUTOCOPY_TO)) $addr_bcc.=($addr_bcc?', ':'').$conf->global->MAIN_MAIL_AUTOCOPY_TO;

		// Action according to choosed sending method
		if ($this->sendmode == 'mail')
		{
			// Use mail php function (default PHP method)
			// ------------------------------------------

			$smtp_headers = "";
			$mime_headers = "";
			$text_body = "";
			$files_encoded = "";

			// Define smtp_headers
			$this->subject = $subject;
			$this->addr_from = $from;
			$this->reply_to = $replyto;
			$this->errors_to = $errors_to;
			$this->addr_to = $to;
			$this->addr_cc = $addr_cc;
			$this->addr_bcc = $addr_bcc;
			$this->deliveryreceipt = $deliveryreceipt;
			$this->trackid = $trackid;

			$smtp_headers = $this->write_smtpheaders();
			if (! empty($moreinheader)) $smtp_headers.=$moreinheader;   // $moreinheader contains the \r\n

			// Define mime_headers
			$mime_headers = $this->write_mimeheaders($filename_list, $mimefilename_list);

			if (! empty($this->html))
			{
				if (!empty($css))
				{
					$this->css = $css;
					$this->buildCSS();    // Build a css style (mode = all) into this->styleCSS and this->bodyCSS
				}

				$msg = $this->html;
			}

			// Define body in text_body
			$text_body = $this->write_body($msg);

			// Add attachments to text_encoded
			if ($this->atleastonefile)
			{
				$files_encoded = $this->write_files($filename_list,$mimetype_list,$mimefilename_list);
			}

			// We now define $this->headers and $this->message
			$this->headers = $smtp_headers . $mime_headers;
			// On nettoie le header pour qu'il ne se termine pas par un retour chariot.
			// Ceci evite aussi les lignes vides en fin qui peuvent etre interpretees
			// comme des injections mail par les serveurs de messagerie.
			$this->headers = preg_replace("/([\r\n]+)$/i","",$this->headers);

			//$this->message = $this->eol.'This is a message with multiple parts in MIME format.'.$this->eol;
			$this->message = 'This is a message with multiple parts in MIME format.'.$this->eol;
			$this->message.= $text_body . $files_encoded;
			$this->message.= "--" . $this->mixed_boundary . "--" . $this->eol;
		}
		else if ($this->sendmode == 'smtps')
		{
			// Use SMTPS library
			// ------------------------------------------

			require_once DOL_DOCUMENT_ROOT.'/core/class/smtps.class.php';
			$smtps = new SMTPs();
			$smtps->setCharSet($conf->file->character_set_client);

			$smtps->setSubject($this->encodetorfc2822($subject));
			$smtps->setTO($this->getValidAddress($to,0,1));
			$smtps->setFrom($this->getValidAddress($from,0,1));
			$smtps->setTrackId($trackid);
			$smtps->setReplyTo($this->getValidAddress($replyto,0,1));

			if (! empty($moreinheader)) $smtps->setMoreInHeader($moreinheader);

			if (! empty($this->html))
			{
				if (!empty($css))
				{
					$this->css = $css;
					$this->buildCSS();
				}
				$msg = $this->html;
				$msg = $this->checkIfHTML($msg);
			}

			if ($this->msgishtml) $smtps->setBodyContent($msg,'html');
			else $smtps->setBodyContent($msg,'plain');

			if ($this->atleastoneimage)
			{
				foreach ($this->images_encoded as $img)
				{
					$smtps->setImageInline($img['image_encoded'],$img['name'],$img['content_type'],$img['cid']);
				}
			}

			if ($this->atleastonefile)
			{
				foreach ($filename_list as $i => $val)
				{
					$content=file_get_contents($filename_list[$i]);
					$smtps->setAttachment($content,$mimefilename_list[$i],$mimetype_list[$i]);
				}
			}

			$smtps->setCC($addr_cc);
			$smtps->setBCC($addr_bcc);
			$smtps->setErrorsTo($errors_to);
			$smtps->setDeliveryReceipt($deliveryreceipt);

			$this->smtps=$smtps;
		}
		else if ($this->sendmode == 'swiftmailer')
		{
			// Use Swift Mailer library
			// ------------------------------------------

            $host = dol_getprefix('email');

            require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/lexer/lib/Doctrine/Common/Lexer/AbstractLexer.php';

            require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/egulias/email-validator/EmailValidator/Exception/InvalidEmail.php';
            require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/egulias/email-validator/EmailValidator/Exception/NoDomainPart.php';
            require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/egulias/email-validator/EmailValidator/EmailParser.php';
			require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/egulias/email-validator/EmailValidator/EmailLexer.php';
			require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/egulias/email-validator/EmailValidator/EmailValidator.php';
			require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/egulias/email-validator/EmailValidator/Warning/Warning.php';
			require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/egulias/email-validator/EmailValidator/Warning/LocalTooLong.php';
			require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/egulias/email-validator/EmailValidator/Parser/Parser.php';
			require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/egulias/email-validator/EmailValidator/Parser/DomainPart.php';
			require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/egulias/email-validator/EmailValidator/Parser/LocalPart.php';
			require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/egulias/email-validator/EmailValidator/Validation/EmailValidation.php';
			require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/egulias/email-validator/EmailValidator/Validation/RFCValidation.php';

            require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/lib/classes/Swift/InputByteStream.php';
			require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/lib/classes/Swift/Signer.php';
			require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/lib/classes/Swift/Signers/HeaderSigner.php';
			require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/lib/classes/Swift/Signers/DKIMSigner.php';
			//require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/lib/classes/Swift/SignedMessage.php';
			require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/lib/swift_required.php';
			// Create the message
			//$this->message = Swift_Message::newInstance();
			$this->message = new Swift_Message();
            //$this->message = new Swift_SignedMessage();
            // Adding a trackid header to a message
			$headers = $this->message->getHeaders();
			$headers->addTextHeader('X-Dolibarr-TRACKID', $trackid);
			$headerID = time() . '.swiftmailer-dolibarr-' . $trackid . '@' . $host;
			$msgid = $headers->get('Message-ID');
			$msgid->setId($headerID);
			$headers->addIdHeader('References', $headerID);
			// TODO if (! empty($moreinheader)) ...

			// Give the message a subject
			$this->message->setSubject($this->encodetorfc2822($subject));

			// Set the From address with an associative array
			//$this->message->setFrom(array('john@doe.com' => 'John Doe'));
			if (! empty($from)) {
                try {
                    $this->message->setFrom($this->getArrayAddress($from));
                } catch (Exception $e) {
                    $this->errors[] = $e->getMessage();
                }
            }

			// Set the To addresses with an associative array
			if (! empty($to)) {
                try {
                    $this->message->setTo($this->getArrayAddress($to));
                } catch (Exception $e) {
                    $this->errors[] = $e->getMessage();
                }
            }

			if (! empty($replyto)) {
                try {
                    $this->message->SetReplyTo($this->getArrayAddress($replyto));
                } catch (Exception $e) {
                    $this->errors[] = $e->getMessage();
                }
            }

			$this->message->setCharSet($conf->file->character_set_client);

			if (! empty($this->html))
			{
				if (!empty($css))
				{
					$this->css = $css;
					$this->buildCSS();
				}
				$msg = $this->html;
				$msg = $this->checkIfHTML($msg);
			}

			if ($this->atleastoneimage)
			{
				foreach ($this->images_encoded as $img)
				{
					//$img['fullpath'],$img['image_encoded'],$img['name'],$img['content_type'],$img['cid']
					$attachment = Swift_Image::fromPath($img['fullpath'], $img['content_type']);
					// embed image
					$imgcid = $this->message->embed($attachment);
					// replace cid by the one created by swiftmail in html message
					$msg = str_replace("cid:".$img['cid'], $imgcid, $msg);
				}
			}

			if ($this->msgishtml) {
				$this->message->setBody($msg,'text/html');
				// And optionally an alternative body
				$this->message->addPart(html_entity_decode(strip_tags($msg)), 'text/plain');
			} else {
				$this->message->setBody($msg,'text/plain');
				// And optionally an alternative body
				$this->message->addPart($msg, 'text/html');
			}

			if ($this->atleastonefile)
			{
				foreach ($filename_list as $i => $val)
				{
					//$this->message->attach(Swift_Attachment::fromPath($filename_list[$i],$mimetype_list[$i]));
					$attachment = Swift_Attachment::fromPath($filename_list[$i],$mimetype_list[$i]);
					$this->message->attach($attachment);
				}
			}

			if (! empty($addr_cc)) $this->message->setCc($this->getArrayAddress($addr_cc));
			if (! empty($addr_bcc)) $this->message->setBcc($this->getArrayAddress($addr_bcc));
			//if (! empty($errors_to)) $this->message->setErrorsTo($this->getArrayAddress($errors_to);
			if (isset($this->deliveryreceipt) && $this->deliveryreceipt == 1) $this->message->setReadReceiptTo($this->getArrayAddress($from));
		}
		else
		{
			// Send mail method not correctly defined
			// --------------------------------------
			$this->error = 'Bad value for sendmode';
		}

	}


	/**
	 * Send mail that was prepared by constructor.
	 *
	 * @return    boolean     True if mail sent, false otherwise
	 */
	function sendfile()
	{
		global $conf,$db,$langs;

		$errorlevel=error_reporting();
		//error_reporting($errorlevel ^ E_WARNING);   // Desactive warnings

		$res=false;

		if (empty($conf->global->MAIN_DISABLE_ALL_MAILS) || !empty($conf->global->MAIN_MAIL_FORCE_SENDTO))
		{
			require_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($db);
			$hookmanager->initHooks(array('mail'));

			$parameters=array(); $action='';
			$reshook = $hookmanager->executeHooks('sendMail', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
			if ($reshook < 0)
			{
				$this->error = "Error in hook maildao sendMail " . $reshook;
				dol_syslog("CMailFile::sendfile: mail end error=" . $this->error, LOG_ERR);

				return $reshook;
			}
			if ($reshook == 1)	// Hook replace standard code
			{
				return true;
			}

			// Check number of recipient is lower or equal than MAIL_MAX_NB_OF_RECIPIENTS_IN_SAME_EMAIL
			if (empty($conf->global->MAIL_MAX_NB_OF_RECIPIENTS_TO_IN_SAME_EMAIL)) $conf->global->MAIL_MAX_NB_OF_RECIPIENTS_TO_IN_SAME_EMAIL=10;
			$tmparray1 = explode(',', $this->addr_to);
			if (count($tmparray1) > $conf->global->MAIL_MAX_NB_OF_RECIPIENTS_TO_IN_SAME_EMAIL)
			{
				$this->error = 'Too much recipients in to:';
				dol_syslog("CMailFile::sendfile: mail end error=" . $this->error, LOG_WARNING);
				return false;
			}
			if (empty($conf->global->MAIL_MAX_NB_OF_RECIPIENTS_CC_IN_SAME_EMAIL)) $conf->global->MAIL_MAX_NB_OF_RECIPIENTS_CC_IN_SAME_EMAIL=10;
			$tmparray2 = explode(',', $this->addr_cc);
			if (count($tmparray2) > $conf->global->MAIL_MAX_NB_OF_RECIPIENTS_CC_IN_SAME_EMAIL)
			{
				$this->error = 'Too much recipients in cc:';
				dol_syslog("CMailFile::sendfile: mail end error=" . $this->error, LOG_WARNING);
				return false;
			}
			if (empty($conf->global->MAIL_MAX_NB_OF_RECIPIENTS_BCC_IN_SAME_EMAIL)) $conf->global->MAIL_MAX_NB_OF_RECIPIENTS_BCC_IN_SAME_EMAIL=10;
			$tmparray3 = explode(',', $this->addr_bcc);
			if (count($tmparray3) > $conf->global->MAIL_MAX_NB_OF_RECIPIENTS_BCC_IN_SAME_EMAIL)
			{
				$this->error = 'Too much recipients in bcc:';
				dol_syslog("CMailFile::sendfile: mail end error=" . $this->error, LOG_WARNING);
				return false;
			}
			if (empty($conf->global->MAIL_MAX_NB_OF_RECIPIENTS_IN_SAME_EMAIL)) $conf->global->MAIL_MAX_NB_OF_RECIPIENTS_IN_SAME_EMAIL=10;
			if ((count($tmparray1)+count($tmparray2)+count($tmparray3)) > $conf->global->MAIL_MAX_NB_OF_RECIPIENTS_IN_SAME_EMAIL)
			{
				$this->error = 'Too much recipients in to:, cc:, bcc:';
				dol_syslog("CMailFile::sendfile: mail end error=" . $this->error, LOG_WARNING);
				return false;
			}

			$keyforsmtpserver='MAIN_MAIL_SMTP_SERVER';
			$keyforsmtpport  ='MAIN_MAIL_SMTP_PORT';
			$keyforsmtpid    ='MAIN_MAIL_SMTPS_ID';
			$keyforsmtppw    ='MAIN_MAIL_SMTPS_PW';
			$keyfortls       ='MAIN_MAIL_EMAIL_TLS';
			$keyforstarttls  ='MAIN_MAIL_EMAIL_STARTTLS';
			if ($this->sendcontext == 'emailing' && !empty($conf->global->MAIN_MAIL_SENDMODE_EMAILING) && $conf->global->MAIN_MAIL_SENDMODE_EMAILING != 'default')
			{
				$keyforsmtpserver='MAIN_MAIL_SMTP_SERVER_EMAILING';
				$keyforsmtpport  ='MAIN_MAIL_SMTP_PORT_EMAILING';
				$keyforsmtpid    ='MAIN_MAIL_SMTPS_ID_EMAILING';
				$keyforsmtppw    ='MAIN_MAIL_SMTPS_PW_EMAILING';
				$keyfortls       ='MAIN_MAIL_EMAIL_TLS_EMAILING';
				$keyforstarttls  ='MAIN_MAIL_EMAIL_STARTTLS_EMAILING';
			}

			if(!empty($conf->global->MAIN_MAIL_FORCE_SENDTO)) {
				$this->addr_to = $conf->global->MAIN_MAIL_FORCE_SENDTO;
				$this->addr_cc = '';
				$this->addr_bcc = '';
			}

			// Action according to choosed sending method
			if ($this->sendmode == 'mail')
			{
				// Use mail php function (default PHP method)
				// ------------------------------------------
				dol_syslog("CMailFile::sendfile addr_to=".$this->addr_to.", subject=".$this->subject, LOG_DEBUG);
				dol_syslog("CMailFile::sendfile header=\n".$this->headers, LOG_DEBUG);
				//dol_syslog("CMailFile::sendfile message=\n".$message);

				// If Windows, sendmail_from must be defined
				if (isset($_SERVER["WINDIR"]))
				{
					if (empty($this->addr_from)) $this->addr_from = 'robot@example.com';
					@ini_set('sendmail_from',$this->getValidAddress($this->addr_from,2));
				}

				// Force parameters
				if (! empty($conf->global->$keyforsmtpserver)) ini_set('SMTP',$conf->global->$keyforsmtpserver);
				if (! empty($conf->global->$keyforsmtpport))   ini_set('smtp_port',$conf->global->$keyforsmtpport);

				$res=true;
				if ($res && ! $this->subject)
				{
					$this->error="Failed to send mail with php mail to HOST=".ini_get('SMTP').", PORT=".ini_get('smtp_port')."<br>Subject is empty";
					dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);
					$res=false;
				}
				$dest=$this->getValidAddress($this->addr_to,2);
				if ($res && ! $dest)
				{
					$this->error="Failed to send mail with php mail to HOST=".ini_get('SMTP').", PORT=".ini_get('smtp_port')."<br>Recipient address '$dest' invalid";
					dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);
					$res=false;
				}

				if ($res)
				{
					$additionnalparam = '';	// By default
					if (! empty($conf->global->MAIN_MAIL_ALLOW_SENDMAIL_F))
					{
						// le "Return-Path" (retour des messages bounced) dans les header ne fonctionne pas avec tous les MTA
						// Le forcage de la valeur grace à l'option -f de sendmail est donc possible si la constante MAIN_MAIL_ALLOW_SENDMAIL_F est definie.
						// Having this variable defined may create problems with some sendmail (option -f refused)
						// Having this variable not defined may create problems with some other sendmail (option -f required)
						$additionnalparam .= ($additionnalparam?' ':'').(! empty($conf->global->MAIN_MAIL_ERRORS_TO) ? '-f' . $this->getValidAddress($conf->global->MAIN_MAIL_ERRORS_TO,2) : ($this->addr_from != '' ? '-f' . $this->getValidAddress($this->addr_from,2) : '') );
					}
					if (! empty($conf->global->MAIN_MAIL_SENDMAIL_FORCE_BA))    // To force usage of -ba option. This option tells sendmail to read From: or Sender: to setup sender
					{
						$additionnalparam .= ($additionnalparam?' ':'').'-ba';
					}

					if (! empty($conf->global->MAIN_MAIL_SENDMAIL_FORCE_ADDPARAM)) $additionnalparam .= ($additionnalparam?' ':'').'-U '.$additionnalparam; // Use -U to add additionnal params

					dol_syslog("CMailFile::sendfile: mail start HOST=".ini_get('SMTP').", PORT=".ini_get('smtp_port').", additionnal_parameters=".$additionnalparam, LOG_DEBUG);

					$this->message=stripslashes($this->message);

					if (! empty($conf->global->MAIN_MAIL_DEBUG)) $this->dump_mail();

					if (! empty($additionnalparam)) $res = mail($dest, $this->encodetorfc2822($this->subject), $this->message, $this->headers, $additionnalparam);
					else $res = mail($dest, $this->encodetorfc2822($this->subject), $this->message, $this->headers);

					if (! $res)
					{
						$this->error="Failed to send mail with php mail";
						$linuxlike=1;
						if (preg_match('/^win/i',PHP_OS)) $linuxlike=0;
						if (preg_match('/^mac/i',PHP_OS)) $linuxlike=0;
						if (! $linuxlike)
						{
							$this->error.=" to HOST=".ini_get('SMTP').", PORT=".ini_get('smtp_port');	// This values are value used only for non linuxlike systems
						}
						$this->error.=".<br>";
						$this->error.=$langs->trans("ErrorPhpMailDelivery");
						dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);
					}
					else
					{
						dol_syslog("CMailFile::sendfile: mail end success", LOG_DEBUG);
					}
				}

				if (isset($_SERVER["WINDIR"]))
				{
					@ini_restore('sendmail_from');
				}

				// Restore parameters
				if (! empty($conf->global->$keyforsmtpserver))	ini_restore('SMTP');
				if (! empty($conf->global->$keyforsmtpport)) 	ini_restore('smtp_port');
			}
			else if ($this->sendmode == 'smtps')
			{

				// Use SMTPS library
				// ------------------------------------------
				$this->smtps->setTransportType(0);	// Only this method is coded in SMTPs library

				// Clean parameters
				if (empty($conf->global->$keyforsmtpserver)) $conf->global->$keyforsmtpserver=ini_get('SMTP');
				if (empty($conf->global->$keyforsmtpport))   $conf->global->$keyforsmtpport=ini_get('smtp_port');

				// If we use SSL/TLS
				$server=$conf->global->$keyforsmtpserver;
				$secure='';
				if (! empty($conf->global->$keyfortls) && function_exists('openssl_open')) $secure='ssl';
				if (! empty($conf->global->$keyforstarttls) && function_exists('openssl_open')) $secure='tls';
				$server=($secure?$secure.'://':'').$server;

				$port=$conf->global->$keyforsmtpport;

				$this->smtps->setHost($server);
				$this->smtps->setPort($port); // 25, 465...;

				$loginid=''; $loginpass='';
				if (! empty($conf->global->$keyforsmtpid))
				{
					$loginid = $conf->global->$keyforsmtpid;
					$this->smtps->setID($loginid);
				}
				if (! empty($conf->global->$keyforsmtppw))
				{
					$loginpass = $conf->global->$keyforsmtppw;
					$this->smtps->setPW($loginpass);
				}

				$res=true;
				$from=$this->smtps->getFrom('org');
				if ($res && ! $from)
				{
					$this->error="Failed to send mail with smtps lib to HOST=".$server.", PORT=".$conf->global->$keyforsmtpport."<br>Sender address '$from' invalid";
					dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);
					$res=false;
				}
				$dest=$this->smtps->getTo();
				if ($res && ! $dest)
				{
					$this->error="Failed to send mail with smtps lib to HOST=".$server.", PORT=".$conf->global->$keyforsmtpport."<br>Recipient address '$dest' invalid";
					dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);
					$res=false;
				}

				if ($res)
				{
					if (! empty($conf->global->MAIN_MAIL_DEBUG)) $this->smtps->setDebug(true);

					$result=$this->smtps->sendMsg();
					//print $result;

					if (! empty($conf->global->MAIN_MAIL_DEBUG)) $this->dump_mail();

					$result=$this->smtps->getErrors();
					if (empty($this->error) && empty($result)) $res=true;
					else
					{
						if (empty($this->error)) $this->error=$result;
						dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);
						$res=false;
					}
				}
			}
			else if ($this->sendmode == 'swiftmailer')
			{
				// Use Swift Mailer library
				// ------------------------------------------
				require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/lib/swift_required.php';

				// Clean parameters
				if (empty($conf->global->$keyforsmtpserver)) $conf->global->$keyforsmtpserver=ini_get('SMTP');
				if (empty($conf->global->$keyforsmtpport))   $conf->global->$keyforsmtpport=ini_get('smtp_port');

				// If we use SSL/TLS
				$server = $conf->global->$keyforsmtpserver;
				$secure = '';
				if (! empty($conf->global->$keyfortls) && function_exists('openssl_open')) $secure='ssl';
				if (! empty($conf->global->$keyforstarttls) && function_exists('openssl_open')) $secure='tls';

				$this->transport = new Swift_SmtpTransport($server, $conf->global->$keyforsmtpport, $secure);

				if (! empty($conf->global->$keyforsmtpid)) $this->transport->setUsername($conf->global->$keyforsmtpid);
				if (! empty($conf->global->$keyforsmtppw)) $this->transport->setPassword($conf->global->$keyforsmtppw);
				//$smtps->_msgReplyTo  = 'reply@web.com';

				// Create the Mailer using your created Transport
				$this->mailer = new Swift_Mailer($this->transport);

                // DKIM SIGN
                if ($conf->global->MAIN_MAIL_EMAIL_DKIM_ENABLED) {
                    $privateKey = $conf->global->MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY;
                    $domainName = $conf->global->MAIN_MAIL_EMAIL_DKIM_DOMAIN;
                    $selector = $conf->global->MAIN_MAIL_EMAIL_DKIM_SELECTOR;
                    $signer = new Swift_Signers_DKIMSigner($privateKey, $domainName, $selector);
                    $this->message->attachSigner($signer->ignoreHeader('Return-Path'));
                }

                if (! empty($conf->global->MAIN_MAIL_DEBUG)) {
					// To use the ArrayLogger
					$this->logger = new Swift_Plugins_Loggers_ArrayLogger();
					// Or to use the Echo Logger
					//$this->logger = new Swift_Plugins_Loggers_EchoLogger();
					$this->mailer->registerPlugin(new Swift_Plugins_LoggerPlugin($this->logger));
				}
				// send mail
				try {
					$result = $this->mailer->send($this->message);
				} catch (Exception $e) {
					$this->error =  $e->getMessage();
				}
				if (! empty($conf->global->MAIN_MAIL_DEBUG)) $this->dump_mail();

				$res = true;
				if (! empty($this->error) || ! $result) {
					dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);
					$res=false;
				}
			}
			else
			{
				// Send mail method not correctly defined
				// --------------------------------------

				return 'Bad value for sendmode';
			}

			$parameters=array(); $action='';
			$reshook = $hookmanager->executeHooks('sendMailAfter', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
			if ($reshook < 0)
			{
				$this->error = "Error in hook maildao sendMailAfter " . $reshook;
				dol_syslog("CMailFile::sendfile: mail end error=" . $this->error, LOG_ERR);

				return $reshook;
			}
		}
		else
		{
			$this->error='No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
			dol_syslog("CMailFile::sendfile: ".$this->error, LOG_WARNING);
		}

		error_reporting($errorlevel);              // Reactive niveau erreur origine

		return $res;
	}

	/**
	 * Encode subject according to RFC 2822 - http://en.wikipedia.org/wiki/MIME#Encoded-Word
	 *
	 * @param string $stringtoencode String to encode
	 * @return string                string encoded
	 */
	static function encodetorfc2822($stringtoencode)
	{
		global $conf;
		return '=?'.$conf->file->character_set_client.'?B?'.base64_encode($stringtoencode).'?=';
	}

	/**
	 * Read a file on disk and return encoded content for emails (mode = 'mail')
	 *
	 * @param	string	$sourcefile		Path to file to encode
	 * @return 	int					    <0 if KO, encoded string if OK
	 */
	function _encode_file($sourcefile)
	{
		$newsourcefile=dol_osencode($sourcefile);

		if (is_readable($newsourcefile))
		{
			$contents = file_get_contents($newsourcefile);	// Need PHP 4.3
			$encoded = chunk_split(base64_encode($contents), 76, $this->eol);    // 76 max is defined into http://tools.ietf.org/html/rfc2047
			return $encoded;
		}
		else
		{
			$this->error="Error: Can't read file '".$sourcefile."' into _encode_file";
			dol_syslog("CMailFile::encode_file: ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *  Write content of a SMTP request into a dump file (mode = all)
	 *  Used for debugging.
	 *  Note that to see full SMTP protocol, you can use tcpdump -w /tmp/smtp -s 2000 port 25"
	 *
	 *  @return	void
	 */
	function dump_mail()
	{
		global $conf,$dolibarr_main_data_root;

		if (@is_writeable($dolibarr_main_data_root))	// Avoid fatal error on fopen with open_basedir
		{
			$outputfile=$dolibarr_main_data_root."/dolibarr_mail.log";
			$fp = fopen($outputfile,"w");

			if ($this->sendmode == 'mail')
			{
				fputs($fp, $this->headers);
				fputs($fp, $this->eol);			// This eol is added by the mail function, so we add it in log
				fputs($fp, $this->message);
			}
			elseif ($this->sendmode == 'smtps')
			{
				fputs($fp, $this->smtps->log);	// this->smtps->log is filled only if MAIN_MAIL_DEBUG was set to on
			}
			elseif ($this->sendmode == 'swiftmailer')
			{
				fputs($fp, $this->logger->dump());	// this->logger is filled only if MAIN_MAIL_DEBUG was set to on
			}

			fclose($fp);
			if (! empty($conf->global->MAIN_UMASK))
				@chmod($outputfile, octdec($conf->global->MAIN_UMASK));
		}
	}


	/**
	 * Correct an uncomplete html string
	 *
	 * @param	string	$msg	String
	 * @return	string			Completed string
	 */
	function checkIfHTML($msg)
	{
		if (!preg_match('/^[\s\t]*<html/i',$msg))
		{
			$out = "<html><head><title></title>";
			if (!empty($this->styleCSS)) $out.= $this->styleCSS;
			$out.= "</head><body";
			if (!empty($this->bodyCSS)) $out.= $this->bodyCSS;
			$out.= ">";
			$out.= $msg;
			$out.= "</body></html>";
		}
		else
		{
			$out = $msg;
		}

		return $out;
	}

	/**
	 * Build a css style (mode = all) into this->styleCSS and this->bodyCSS
	 *
	 * @return css
	 */
	function buildCSS()
	{
		if (! empty($this->css))
		{
			// Style CSS
			$this->styleCSS = '<style type="text/css">';
			$this->styleCSS.= 'body {';

			if ($this->css['bgcolor'])
			{
				$this->styleCSS.= '  background-color: '.$this->css['bgcolor'].';';
				$this->bodyCSS.= ' bgcolor="'.$this->css['bgcolor'].'"';
			}
			if ($this->css['bgimage'])
			{
				// TODO recuperer cid
				$this->styleCSS.= ' background-image: url("cid:'.$this->css['bgimage_cid'].'");';
			}
			$this->styleCSS.= '}';
			$this->styleCSS.= '</style>';
		}
	}


	/**
	 * Create SMTP headers (mode = 'mail')
	 *
	 * @return	string headers
	 */
	function write_smtpheaders()
	{
		global $conf;
		$out = "";

		$host = dol_getprefix('email');

		// Sender
		//$out.= "Sender: ".getValidAddress($this->addr_from,2)).$this->eol2;
		$out.= "From: ".$this->getValidAddress($this->addr_from,3,1).$this->eol2;
		if (! empty($conf->global->MAIN_MAIL_SENDMAIL_FORCE_BA))
		{
			$out.= "To: ".$this->getValidAddress($this->addr_to,0,1).$this->eol2;
		}
		// Return-Path is important because it is used by SPF. Some MTA does not read Return-Path from header but from command line. See option MAIN_MAIL_ALLOW_SENDMAIL_F for that.
		$out.= "Return-Path: ".$this->getValidAddress($this->addr_from,0,1).$this->eol2;
		if (isset($this->reply_to)  && $this->reply_to)  $out.= "Reply-To: ".$this->getValidAddress($this->reply_to,2).$this->eol2;
		if (isset($this->errors_to) && $this->errors_to) $out.= "Errors-To: ".$this->getValidAddress($this->errors_to,2).$this->eol2;

		// Receiver
		if (isset($this->addr_cc)   && $this->addr_cc)   $out.= "Cc: ".$this->getValidAddress($this->addr_cc,2).$this->eol2;
		if (isset($this->addr_bcc)  && $this->addr_bcc)  $out.= "Bcc: ".$this->getValidAddress($this->addr_bcc,2).$this->eol2;    // TODO Question: bcc must not be into header, only into SMTP command "RCPT TO". Does php mail support this ?

		// Delivery receipt
		if (isset($this->deliveryreceipt) && $this->deliveryreceipt == 1) $out.= "Disposition-Notification-To: ".$this->getValidAddress($this->addr_from,2).$this->eol2;

		//$out.= "X-Priority: 3".$this->eol2;

		$out.= 'Date: ' . date("r") . $this->eol2;

		$trackid = $this->trackid;
		if ($trackid)
		{
			// References is kept in response and Message-ID is returned into In-Reply-To:
			$out.= 'Message-ID: <' . time() . '.phpmail-dolibarr-'.$trackid.'@' . $host . ">" . $this->eol2;	// Uppercase seems replaced by phpmail
			$out.= 'References: <' . time() . '.phpmail-dolibarr-'.$trackid.'@' . $host . ">" . $this->eol2;
			$out.= 'X-Dolibarr-TRACKID: '.$trackid. $this->eol2;
		}
		else
		{
			$out.= 'Message-ID: <' . time() . '.phpmail@' . $host . ">" . $this->eol2;
		}

		if (! empty($_SERVER['REMOTE_ADDR'])) $out.= "X-RemoteAddr: " . $_SERVER['REMOTE_ADDR']. $this->eol2;
		$out.= "X-Mailer: Dolibarr version " . DOL_VERSION ." (using php mail)".$this->eol2;
		$out.= "Mime-Version: 1.0".$this->eol2;

		//$out.= "From: ".$this->getValidAddress($this->addr_from,3,1).$this->eol;

		$out.= "Content-Type: multipart/mixed;".$this->eol2." boundary=\"".$this->mixed_boundary."\"".$this->eol2;
		$out.= "Content-Transfer-Encoding: 8bit".$this->eol2;		// TODO Seems to be ignored. Header is 7bit once received.

		dol_syslog("CMailFile::write_smtpheaders smtp_header=\n".$out);
		return $out;
	}


	/**
	 * Create header MIME (mode = 'mail')
	 *
	 * @param	array	$filename_list			Array of filenames
	 * @param 	array	$mimefilename_list		Array of mime types
	 * @return	string							mime headers
	 */
	function write_mimeheaders($filename_list, $mimefilename_list)
	{
		$mimedone=0;
		$out = "";

		if ($filename_list)
		{
			$filename_list_size=count($filename_list);
			for($i=0;$i < $filename_list_size;$i++)
			{
				if ($filename_list[$i])
				{
					if ($mimefilename_list[$i]) $filename_list[$i] = $mimefilename_list[$i];
					$out.= "X-attachments: $filename_list[$i]".$this->eol2;
				}
			}
		}

		dol_syslog("CMailFile::write_mimeheaders mime_header=\n".$out, LOG_DEBUG);
		return $out;
	}

	/**
	 * Return email content (mode = 'mail')
	 *
	 * @param	string		$msgtext		Message string
	 * @return	string						String content
	 */
	function write_body($msgtext)
	{
		global $conf;

		$out='';

		$out.= "--" . $this->mixed_boundary . $this->eol;

		if ($this->atleastoneimage)
		{
			$out.= "Content-Type: multipart/alternative;".$this->eol." boundary=\"".$this->alternative_boundary."\"".$this->eol;
			$out.= $this->eol;
			$out.= "--" . $this->alternative_boundary . $this->eol;
		}

		// Make RFC821 Compliant, replace bare linefeeds
		$strContent = preg_replace("/(?<!\r)\n/si", "\r\n", $msgtext);	// PCRE modifier /s means new lines are common chars
		if (! empty($conf->global->MAIN_FIX_FOR_BUGGED_MTA))
		{
			$strContent = preg_replace("/\r\n/si", "\n", $strContent);	// PCRE modifier /s means new lines are common chars
		}

		$strContentAltText = '';
		if ($this->msgishtml)
		{
			$strContentAltText = html_entity_decode(strip_tags($strContent));
			$strContentAltText = rtrim(wordwrap($strContentAltText, 75, empty($conf->global->MAIN_FIX_FOR_BUGGED_MTA)?"\r\n":"\n"));

			// Check if html header already in message, if not complete the message
			$strContent = $this->checkIfHTML($strContent);
		}

		// Make RFC2045 Compliant, split lines
		//$strContent = rtrim(chunk_split($strContent));    // Function chunck_split seems ko if not used on a base64 content
		// TODO Encode main content into base64 and use the chunk_split, or quoted-printable
		$strContent = rtrim(wordwrap($strContent, 75, empty($conf->global->MAIN_FIX_FOR_BUGGED_MTA)?"\r\n":"\n"));   // TODO Using this method creates unexpected line break on text/plain content.

		if ($this->msgishtml)
		{
			if ($this->atleastoneimage)
			{
				$out.= "Content-Type: text/plain; charset=".$conf->file->character_set_client.$this->eol;
				//$out.= "Content-Transfer-Encoding: 7bit".$this->eol;
				$out.= $this->eol.($strContentAltText?$strContentAltText:strip_tags($strContent)).$this->eol; // Add plain text message
				$out.= "--" . $this->alternative_boundary . $this->eol;
				$out.= "Content-Type: multipart/related;".$this->eol." boundary=\"".$this->related_boundary."\"".$this->eol;
				$out.= $this->eol;
				$out.= "--" . $this->related_boundary . $this->eol;
			}

			if (! $this->atleastoneimage && $strContentAltText && ! empty($conf->global->MAIN_MAIL_USE_MULTI_PART))    // Add plain text message part before html part
			{
				$out.= "Content-Type: multipart/alternative;".$this->eol." boundary=\"".$this->alternative_boundary."\"".$this->eol;
				$out.= $this->eol;
				$out.= "--" . $this->alternative_boundary . $this->eol;
				$out.= "Content-Type: text/plain; charset=".$conf->file->character_set_client.$this->eol;
				//$out.= "Content-Transfer-Encoding: 7bit".$this->eol;
				$out.= $this->eol.$strContentAltText.$this->eol;
				$out.= "--" . $this->alternative_boundary . $this->eol;
			}

			$out.= "Content-Type: text/html; charset=".$conf->file->character_set_client.$this->eol;
			//$out.= "Content-Transfer-Encoding: 7bit".$this->eol;	// TODO Use base64
			$out.= $this->eol.$strContent.$this->eol;

			if (! $this->atleastoneimage && $strContentAltText && ! empty($conf->global->MAIN_MAIL_USE_MULTI_PART))    // Add plain text message part after html part
			{
				$out.= "--" . $this->alternative_boundary . "--". $this->eol;
			}
		}
		else
		{
			$out.= "Content-Type: text/plain; charset=".$conf->file->character_set_client.$this->eol;
			//$out.= "Content-Transfer-Encoding: 7bit".$this->eol;
			$out.= $this->eol.$strContent.$this->eol;
		}

		$out.= $this->eol;

		// Encode images
		if ($this->atleastoneimage)
		{
			$out .= $this->write_images($this->images_encoded);
			// always end related and end alternative after inline images
			$out .= "--" . $this->related_boundary . "--" . $this->eol;
			$out .= $this->eol . "--" . $this->alternative_boundary . "--" . $this->eol;
			$out .= $this->eol;
		}

		return $out;
	}

	/**
	 * Attach file to email (mode = 'mail')
	 *
	 * @param	array	$filename_list		Tableau
	 * @param	array	$mimetype_list		Tableau
	 * @param 	array	$mimefilename_list	Tableau
	 * @return	string						Chaine fichiers encodes
	 */
	function write_files($filename_list,$mimetype_list,$mimefilename_list)
	{
		$out = '';

		$filename_list_size=count($filename_list);
		for($i=0;$i < $filename_list_size;$i++)
		{
			if ($filename_list[$i])
			{
				dol_syslog("CMailFile::write_files: i=$i");
				$encoded = $this->_encode_file($filename_list[$i]);
				if ($encoded >= 0)
				{
					if ($mimefilename_list[$i]) $filename_list[$i] = $mimefilename_list[$i];
					if (! $mimetype_list[$i]) {
						$mimetype_list[$i] = "application/octet-stream";
					}

					$out.= "--" . $this->mixed_boundary . $this->eol;
					$out.= "Content-Disposition: attachment; filename=\"".$filename_list[$i]."\"".$this->eol;
					$out.= "Content-Type: " . $mimetype_list[$i] . "; name=\"".$filename_list[$i]."\"".$this->eol;
					$out.= "Content-Transfer-Encoding: base64".$this->eol;
					$out.= "Content-Description: File Attachment".$this->eol;
					$out.= $this->eol;
					$out.= $encoded;
					$out.= $this->eol;
					//$out.= $this->eol;
				}
				else
				{
					return $encoded;
				}
			}
		}

		return $out;
	}


	/**
	 * Attach an image to email (mode = 'mail')
	 *
	 * @param	array	$images_list	Tableau
	 * @return	string					Chaine images encodees
	 */
	function write_images($images_list)
	{
		$out = '';

		if ($images_list)
		{
			foreach ($images_list as $img)
			{
				dol_syslog("CMailFile::write_images: i=$i");

				$out.= "--" . $this->related_boundary . $this->eol; // always related for an inline image
				$out.= "Content-Type: " . $img["content_type"] . "; name=\"".$img["name"]."\"".$this->eol;
				$out.= "Content-Transfer-Encoding: base64".$this->eol;
				$out.= "Content-Disposition: inline; filename=\"".$img["name"]."\"".$this->eol;
				$out.= "Content-ID: <".$img["cid"].">".$this->eol;
				$out.= $this->eol;
				$out.= $img["image_encoded"];
				$out.= $this->eol;
			}
		}

		return $out;
	}


	/**
	 * Try to create a socket connection
	 *
	 * @param 	string		$host		Add ssl:// for SSL/TLS.
	 * @param 	int			$port		Example: 25, 465
	 * @return	int						Socket id if ok, 0 if KO
	 */
	function check_server_port($host,$port)
	{
		global $conf;

		$_retVal=0;
		$timeout=5;	// Timeout in seconds

		if (function_exists('fsockopen'))
		{
			$keyforsmtpserver='MAIN_MAIL_SMTP_SERVER';
			$keyforsmtpport  ='MAIN_MAIL_SMTP_PORT';
			$keyforsmtpid    ='MAIN_MAIL_SMTPS_ID';
			$keyforsmtppw    ='MAIN_MAIL_SMTPS_PW';
			$keyfortls       ='MAIN_MAIL_EMAIL_TLS';
			$keyforstarttls  ='MAIN_MAIL_EMAIL_STARTTLS';
			if ($this->sendcontext == 'emailing' && !empty($conf->global->MAIN_MAIL_SENDMODE_EMAILING) && $conf->global->MAIN_MAIL_SENDMODE_EMAILING != 'default')
			{
				$keyforsmtpserver='MAIN_MAIL_SMTP_SERVER_EMAILING';
				$keyforsmtpport  ='MAIN_MAIL_SMTP_PORT_EMAILING';
				$keyforsmtpid    ='MAIN_MAIL_SMTPS_ID_EMAILING';
				$keyforsmtppw    ='MAIN_MAIL_SMTPS_PW_EMAILING';
				$keyfortls       ='MAIN_MAIL_EMAIL_TLS_EMAILING';
				$keyforstarttls  ='MAIN_MAIL_EMAIL_STARTTLS_EMAILING';
			}

			// If we use SSL/TLS
			if (! empty($conf->global->$keyfortls) && function_exists('openssl_open')) $host='ssl://'.$host;
			// tls smtp start with no encryption
			//if (! empty($conf->global->MAIN_MAIL_EMAIL_STARTTLS) && function_exists('openssl_open')) $host='tls://'.$host;

			dol_syslog("Try socket connection to host=".$host." port=".$port);
			//See if we can connect to the SMTP server
			if ($socket = @fsockopen(
					$host,       // Host to test, IP or domain. Add ssl:// for SSL/TLS.
					$port,       // which Port number to use
					$errno,      // actual system level error
					$errstr,     // and any text that goes with the error
					$timeout
			))  // timeout for reading/writing data over the socket
			{
				// Windows still does not have support for this timeout function
				if (function_exists('stream_set_timeout')) stream_set_timeout($socket, $timeout, 0);

				dol_syslog("Now we wait for answer 220");

				// Check response from Server
				if ( $_retVal = $this->server_parse($socket, "220") ) $_retVal = $socket;
			}
			else
			{
				$this->error = utf8_check('Error '.$errno.' - '.$errstr)?'Error '.$errno.' - '.$errstr:utf8_encode('Error '.$errno.' - '.$errstr);
			}
		}
		return $_retVal;
	}

	/**
	 * This function has been modified as provided by SirSir to allow multiline responses when
	 * using SMTP Extensions.
	 *
	 * @param	Socket	$socket			Socket
	 * @param   string	$response		Response string
	 * @return  boolean					true if success
	 */
	function server_parse($socket, $response)
	{
		$_retVal = true;	// Indicates if Object was created or not
		$server_response = '';

		while (substr($server_response,3,1) != ' ')
		{
			if (! ($server_response = fgets($socket, 256)) )
			{
				$this->error="Couldn't get mail server response codes";
				return false;
			}
		}

		if( !( substr($server_response, 0, 3) == $response ) )
		{
			$this->error="Ran into problems sending Mail.\r\nResponse: $server_response";
			$_retVal = false;
		}

		return $_retVal;
	}

	/**
	 * Seearch images into html message and init array this->images_encoded if found
	 *
	 * @param	string	$images_dir		Location of physical images files
	 * @return	int 		        	>0 if OK, <0 if KO
	 */
	function findHtmlImages($images_dir)
	{
		// Build the list of image extensions
		$extensions = array_keys($this->image_types);


		preg_match_all('/(?:"|\')([^"\']+\.('.implode('|', $extensions).'))(?:"|\')/Ui', $this->html, $matches);  // If "xxx.ext" or 'xxx.ext' found

		if ($matches)
		{
			$i=0;
			foreach ($matches[1] as $full)
			{

				if (preg_match('/file=([A-Za-z0-9_\-\/]+[\.]?[A-Za-z0-9]+)?$/i',$full,$regs))   // If xxx is 'file=aaa'
				{
					$img = $regs[1];

					if (file_exists($images_dir.'/'.$img))
					{
						// Image path in src
						$src = preg_quote($full,'/');

						// Image full path
						$this->html_images[$i]["fullpath"] = $images_dir.'/'.$img;

						// Image name
						$this->html_images[$i]["name"] = $img;

						// Content type
						if (preg_match('/^.+\.(\w{3,4})$/', $img, $reg))
						{
							$ext=strtolower($reg[1]);
							$this->html_images[$i]["content_type"] = $this->image_types[$ext];
						}

						// cid
						$this->html_images[$i]["cid"] = dol_hash(uniqid(time()), 3);	// Force md5 hash (does not contains special chars)
						$this->html = preg_replace("/src=\"$src\"|src='$src'/i", "src=\"cid:".$this->html_images[$i]["cid"]."\"", $this->html);
					}
					$i++;
				}
			}

			if (!empty($this->html_images))
			{
				$inline = array();

				$i=0;

				foreach ($this->html_images as $img)
				{
					$fullpath = $images_dir.'/'.$img["name"];

					// If duplicate images are embedded, they may show up as attachments, so remove them.
					if (!in_array($fullpath,$inline))
					{
						// Read image file
						if ($image = file_get_contents($fullpath))
						{
							// On garde que le nom de l'image
							preg_match('/([A-Za-z0-9_-]+[\.]?[A-Za-z0-9]+)?$/i',$img["name"],$regs);
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
			}
			else
			{
				return -1;
			}

			return 1;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Return a formatted address string for SMTP protocol
	 *
	 * @param	string		$address		     Example: 'John Doe <john@doe.com>, Alan Smith <alan@smith.com>' or 'john@doe.com, alan@smith.com'
	 * @param	int			$format			     0=auto, 1=emails with <>, 2=emails without <>, 3=auto + label between "
	 * @param	int			$encode			     0=No encode name, 1=Encode name to RFC2822
	 * @param   int         $maxnumberofemail    0=No limit. Otherwise, maximum number of emails returned ($address may contains several email separated with ','). Add '...' if there is more.
	 * @return	string						     If format 0: '<john@doe.com>' or 'John Doe <john@doe.com>' or '=?UTF-8?B?Sm9obiBEb2U=?= <john@doe.com>'
	 * 										     If format 1: '<john@doe.com>'
	 *										     If format 2: 'john@doe.com'
	 *										     If format 3: '<john@doe.com>' or '"John Doe" <john@doe.com>' or '"=?UTF-8?B?Sm9obiBEb2U=?=" <john@doe.com>'
	 *                                           If format 4: 'John Doe' or 'john@doe.com' if no label exists
	 */
	static function getValidAddress($address,$format,$encode=0,$maxnumberofemail=0)
	{
		global $conf;

		$ret='';

		$arrayaddress=explode(',',$address);

		// Boucle sur chaque composant de l'adresse
		$i=0;
		foreach($arrayaddress as $val)
		{
			if (preg_match('/^(.*)<(.*)>$/i',trim($val),$regs))
			{
				$name  = trim($regs[1]);
				$email = trim($regs[2]);
			}
			else
			{
				$name  = '';
				$email = trim($val);
			}

			if ($email)
			{
				$i++;

				$newemail='';
				if ($format == 4)
				{
					$newemail = $name?$name:$email;
				}
				if ($format == 2)
				{
					$newemail=$email;
				}
				if ($format == 1 || $format == 3)
				{
					$newemail='<'.$email.'>';
				}
				if ($format == 0 || $format == 3)
				{
					if (! empty($conf->global->MAIN_MAIL_NO_FULL_EMAIL)) $newemail='<'.$email.'>';
					elseif (! $name) $newemail='<'.$email.'>';
					else $newemail=($format==3?'"':'').($encode?self::encodetorfc2822($name):$name).($format==3?'"':'').' <'.$email.'>';
				}

				$ret=($ret ? $ret.',' : '').$newemail;

				// Stop if we have too much records
				if ($maxnumberofemail && $i >= $maxnumberofemail)
				{
					if (count($arrayaddress) > $maxnumberofemail) $ret.='...';
					break;
				}
			}
		}

		return $ret;
	}

	/**
	 * Return a formatted array of address string for SMTP protocol
	 *
	 * @param   string      $address        Example: 'John Doe <john@doe.com>, Alan Smith <alan@smith.com>' or 'john@doe.com, alan@smith.com'
	 * @return  array                       array of email => name
	 */
	function getArrayAddress($address)
	{
		global $conf;

		$ret=array();

		$arrayaddress=explode(',',$address);

		// Boucle sur chaque composant de l'adresse
		foreach($arrayaddress as $val)
		{
			if (preg_match('/^(.*)<(.*)>$/i',trim($val),$regs))
			{
				$name  = trim($regs[1]);
				$email = trim($regs[2]);
			}
			else
			{
				$name  = null;
				$email = trim($val);
			}

			$ret[$email]=empty($conf->global->MAIN_MAIL_NO_FULL_EMAIL)?$name:null;
		}

		return $ret;
	}
}

