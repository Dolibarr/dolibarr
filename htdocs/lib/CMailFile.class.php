<?php
/* Copyright (C) 2000-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 *
 * Lots of code inspired from Dan Potter's CMailFile class
 */

/**
 *      \file       htdocs/lib/CMailFile.class.php
 *      \brief      Fichier de la classe permettant d'envoyer des mail avec attachements
 *		\version    $Id$
 *      \author     Dan Potter.
 *      \author	    Eric Seigne
 *      \author	    Laurent Destailleur.
 */

/**
 *      \class      CMailFile
 *      \brief      Classe d'envoi de mails et pieces jointes. Encapsule mail() avec d'eventuels attachements.
 *      \remarks    Usage: $mailfile = new CMailFile($subject,$sendto,$replyto,$message,$filepath,$mimetype,$filename,$cc,$ccc,$deliveryreceipt,$msgishtml,$errors_to);
 *      \remarks           $mailfile->sendfile();
 */
class CMailFile
{
	var $subject;
	var $addr_from;
	var $errors_to;
	var $addr_to;
	var $addr_cc;
	var $addr_bcc;

	var $mime_boundary;
	var $deliveryreceipt;

	var $eol;
	var $atleastonefile=0;
	var $error='';

	var $smtps;				// Contains SMTPs object (if this method is used)

	// simplemail
	var $simplemail;  // Contains simplemail object (if this method is used)
	var $sName;
	var $sEmail;

	//CSS
	var $css;
	var $styleCSS;

	// Image
	var $html;
	var $image_boundary;
	var $atleastoneimage=0;
	var $html_images=array();
	var $images_encoded=array();
	var $image_types = array('gif'  => 'image/gif',
                           'jpg'  => 'image/jpeg',
                           'jpeg' => 'image/jpeg',
                           'jpe'  => 'image/jpeg',
                           'bmp'  => 'image/bmp',
                           'png'  => 'image/png',
                           'tif'  => 'image/tiff',
                           'tiff' => 'image/tiff');


	/**
	 *	\brief 	CMailFile
	 *	\param 	subject             Topic/Subject of mail
	 *	\param 	to                  Recipients emails (RFC 2822: "Nom prenom <email>[, ...]" ou "email[, ...]" ou "<email>[, ...]")
	 *	\param 	from                Sender email      (RFC 2822: "Nom prenom <email>[, ...]" ou "email[, ...]" ou "<email>[, ...]")
	 *	\param 	msg                 Message
	 *	\param 	filename_list       List of files to attach (full path of filename on file system)
	 *	\param 	mimetype_list       List of MIME type of attached files
	 *	\param 	mimefilename_list   List of attached file name in message
	 *	\param 	addr_cc             Email cc
	 *	\param 	addr_bcc            Email bcc
	 *	\param 	deliveryreceipt		  Ask a delivery receipt
	 *	\param 	msgishtml       		1=String IS already html, 0=String IS NOT html, -1=Unknown need autodetection
	 *	\param 	error_to        		Email errors
	 *	\param	css			            Css option
	 */
	function CMailFile($subject,$to,$from,$msg,
	$filename_list=array(),$mimetype_list=array(),$mimefilename_list=array(),
	$addr_cc="",$addr_bcc="",$deliveryreceipt=0,$msgishtml=0,$errors_to='',$css='')
	{
		global $conf;

		// On definit fin de ligne
		$this->eol="\n";
		if (eregi('^win',PHP_OS)) $this->eol="\r\n";
		if (eregi('^mac',PHP_OS)) $this->eol="\r";

		// On defini mime_boundary
		$this->mime_boundary = md5(uniqid("dolibarr"));

		// On defini related_boundary
		$this->related_boundary = md5(uniqid("dolibarr"));

		// On defini alternative_boundary
		$this->alternative_boundary = md5(uniqid("dolibarr"));

		// If ending method not defined
		if (empty($conf->global->MAIN_MAIL_SENDMODE)) $conf->global->MAIN_MAIL_SENDMODE='mail';

		dol_syslog("CMailFile::CMailfile: MAIN_MAIL_SENDMODE=".$conf->global->MAIN_MAIL_SENDMODE." charset=".$conf->file->character_set_client." from=$from, to=$to, addr_cc=$addr_cc, addr_bcc=$addr_bcc, errors_to=$errors_to", LOG_DEBUG);
		dol_syslog("CMailFile::CMailfile: subject=$subject, deliveryreceipt=$deliveryreceipt, msgishtml=$msgishtml", LOG_DEBUG);

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

		// Detect images
		if ($this->msgishtml)
		{
			$this->html = $msg;
			$findimg = $this->findHtmlImages($conf->fckeditor->dir_output);
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

		// Action according to choosed sending method
		if ($conf->global->MAIN_MAIL_SENDMODE == 'mail')
		{
			// Use mail php function (default PHP method)
			// ------------------------------------------

			$smtp_headers = "";
			$mime_headers = "";
			$text_body = "";
			$text_encoded = "";

			// Define smtp_headers
			$this->subject = $subject;
			$this->addr_from = $from;
			$this->errors_to = $errors_to;
			$this->addr_to = $to;
			$this->addr_cc = $addr_cc;
			$this->addr_bcc = $addr_bcc;
			$this->deliveryreceipt = $deliveryreceipt;
			$smtp_headers = $this->write_smtpheaders();

			// Define mime_headers
			$mime_headers = $this->write_mimeheaders($filename_list, $mimefilename_list);

			if (! empty($this->html)) $msg = $this->html;

			// Define body in text_body
			$text_body = $this->write_body($msg);

			// Encode images
			if ($this->atleastoneimage)
			{
				$images_encoded = $this->write_images($this->images_encoded);
			}

			// Add attachments to text_encoded
			if ($this->atleastonefile)
			{
				$text_encoded = $this->write_files($filename_list,$mimetype_list,$mimefilename_list);
			}

			// We now define $this->headers et $this->message
			$this->headers = $smtp_headers . $mime_headers;

			$this->message = $text_body . $images_encoded . $text_encoded;
			$this->message.= "--" . $this->mime_boundary . "--" . $this->eol;

			// On nettoie le header pour qu'il ne se termine pas par un retour chariot.
			// Ceci evite aussi les lignes vides en fin qui peuvent etre interpretees
			// comme des injections mail par les serveurs de messagerie.
			$this->headers = eregi_replace("[\r\n]+$","",$this->headers);
		}
		else if ($conf->global->MAIN_MAIL_SENDMODE == 'simplemail')
		{
			// Use simplemail library
			// ------------------------------------------

			require_once(DOL_DOCUMENT_ROOT."/includes/simplemail/class.mail.php");

			$mail = new simplemail();
			$mail->set_mode='php';		// php or socket

			// Boundaries
			$mail->B1B = $this->mime_boundary;
			$mail->B2B = $this->related_boundary;
			$mail->B3B = $this->alternative_boundary;

			$mail->XMailer = "Dolibarr version " . DOL_VERSION ." (using simplemail)";
			
			// Add Errors-To
			$mail->ErrorsTo = $this->getValidAddress($errors_to,1);
			
			//Add Return-Path
			$mail->returnpath = $this->getValidAddress($errors_to,1);

			// Add from
			$this->addr_from = $from;
			$mail->hfrom=$this->getValidAddress($this->addr_from,0,1);

			// Add delivery receipt
			if ($deliveryreceipt)
			{
				$mail->deliveryreceipt=$this->getValidAddress($this->addr_from,0,1);
			}

			// Add to
			$arrayTo=split(',',$to);
			foreach($arrayTo as $val)
			{
				$mail->recipientlist[] = array( 'mail'=>$this->getValidAddress($val,2), 'nameplusmail' => $this->getValidAddress($val,0,1));
			}

			// Add carbon copy
			if (!empty($addr_cc))
			{
				$arrayTocc=split(',',$addr_cc);
				foreach($arrayTocc as $val)
				{
					if (!empty($mail->hcc)) $mail->hcc.= ",";
					$mail->hcc.= $this->getValidAddress($val,0,1);
				}
			}

			// Add carbon copy cache
			if (!empty($addr_bcc))
			{
				$arrayTobcc=split(',',$addr_bcc);
				foreach($arrayTobcc as $val)
				{
					if (!empty($mail->hbcc)) $mail->hbcc.= ",";
					$mail->hbcc.= $this->getValidAddress($val,0,1);
				}
			}

			// Add subject
			$mail->addsubject($this->encodetorfc2822($subject));

			// Add message
			if ($this->msgishtml)
			{
				if (! empty($this->html))
				{
					if (!empty($css))
					{
						$this->css = $css;
						$this->styleCSS = $this->buildCSS();
					}

					$msg = $this->html;
					$msg = $this->checkIfHTML($msg);

					// un attachement html ( image jointe afficher ds le html ).
					if ($this->atleastoneimage)
					{
						foreach ($this->html_images as $i => $val)
						{
							$mail->addhtmlattachement($this->html_images[$i]['fullpath'],$this->html_images[$i]['cid'],$this->html_images[$i]['content_type']);
						}
					}
				}
				// HTML format
				$mail->html = $msg;
			}
			else
			{
				// Text format
				$mail->text = $msg;
			}

			// Attach-files
			if ($this->atleastonefile)
			{
				foreach ($filename_list as $i => $val)
				{
					$mail->addattachement($filename_list[$i]);
				}
			}
			$this->simplemail = $mail;
		}
		else if ($conf->global->MAIN_MAIL_SENDMODE == 'smtps')
		{
			// Use SMTPS library
			// ------------------------------------------

			require_once(DOL_DOCUMENT_ROOT."/includes/smtps/SMTPs.php");
			$smtps = new SMTPs();
			$smtps->setCharSet($conf->file->character_set_client);

			$smtps->setSubject($this->encodetorfc2822($subject));
			$smtps->setTO($this->getValidAddress($to,0,1));
			$smtps->setFrom($this->getValidAddress($from,0,1));

			if (! empty($this->html))
			{
				if (!empty($css))
				{
					$this->css = $css;
					$this->styleCSS = $this->buildCSS();
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
		else
		{
			// Send mail method not correctly defined
			// --------------------------------------

			return 'Bad value for MAIN_MAIL_SENDMODE constant';
		}

	}


	/**
	 *	\brief     Send mail that was prepared by constructor
	 *	\return    boolean     True if mail sent, false otherwise
	 */
	function sendfile()
	{
		global $conf;

		$errorlevel=error_reporting();
		error_reporting($errorlevel ^ E_WARNING);   // Desactive warnings

		$res=false;

		if (empty($conf->global->MAIN_DISABLE_ALL_MAILS))
		{
			// Action according to choosed sending method
			if ($conf->global->MAIN_MAIL_SENDMODE == 'mail')
			{
				// Use mail php function (default PHP method)
				// ------------------------------------------
				dol_syslog("CMailFile::sendfile addr_to=".$this->addr_to.", subject=".$this->subject, LOG_DEBUG);
				dol_syslog("CMailFile::sendfile header=\n".$this->headers, LOG_DEBUG);
				//dol_syslog("CMailFile::sendfile message=\n".$message);

				// If Windows, sendmail_from must be defined
				if (isset($_SERVER["WINDIR"]))
				{
					if (empty($this->addr_from)) $this->addr_from = 'robot@mydomain.com';
					@ini_set('sendmail_from',$this->getValidAddress($this->addr_from,2));
				}

				// Forcage parametres
				if (! empty($conf->global->MAIN_MAIL_SMTP_SERVER)) ini_set('SMTP',$conf->global->MAIN_MAIL_SMTP_SERVER);
				if (! empty($conf->global->MAIN_MAIL_SMTP_PORT))   ini_set('smtp_port',$conf->global->MAIN_MAIL_SMTP_PORT);

				$dest=$this->getValidAddress($this->addr_to,2);
				if (! $dest)
				{
					$this->error="Failed to send mail to SMTP=".ini_get('SMTP').", PORT=".ini_get('smtp_port')."<br>Recipient address '$dest' invalid";
					dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERROR);
				}
				else
				{
					dol_syslog("CMailFile::sendfile: mail start SMTP=".ini_get('SMTP').", PORT=".ini_get('smtp_port'), LOG_DEBUG);

					$bounce = '';
					if ($conf->global->MAIN_MAIL_ALLOW_SENDMAIL_F)
					{
						// le return-path dans les header ne fonctionne pas avec tous les MTA
						// Le passage par -f est donc possible si la constante MAIN_MAIL_ALLOW_SENDMAIL_F est definie.
						// La variable definie pose des pb avec certains sendmail securisee (option -f refusee car dangereuse)
						$bounce = $this->addr_from != '' ? "-f {$this->addr_from}" : "";
					}

					$this->message=stripslashes($this->message);

					if (! empty($conf->global->MAIN_MAIL_DEBUG)) $this->dump_mail();

					$res = mail($dest,$this->encodetorfc2822($this->subject),$this->message,$this->headers, $bounce);

					if (! $res)
					{
						$this->error="Failed to send mail to SMTP=".ini_get('SMTP').", PORT=".ini_get('smtp_port')."<br>Check your server logs and your firewalls setup";
						dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERROR);
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

				// Forcage parametres
				if (! empty($conf->global->MAIN_MAIL_SMTP_SERVER))	ini_restore('SMTP');
				if (! empty($conf->global->MAIN_MAIL_SMTP_PORT)) 	ini_restore('smtp_port');
			}
			else if ($conf->global->MAIN_MAIL_SENDMODE == 'simplemail')
			{
				// Use simplemmail function (Simplemail method)
				// --------------------------------------------
				dol_syslog("CMailFile::sendfile addr_to=".$this->addr_to.", subject=".$this->subject, LOG_DEBUG);
				dol_syslog("CMailFile::sendfile header=\n".$this->headers, LOG_DEBUG);
				//dol_syslog("CMailFile::sendfile message=\n".$message);

				// If Windows, sendmail_from must be defined
				if (isset($_SERVER["WINDIR"]))
				{
					if (empty($this->addr_from)) $this->addr_from = 'robot@mydomain.com';
					@ini_set('sendmail_from',$this->getValidAddress($this->addr_from,2));
				}

				// Forcage parametres
				if (! empty($conf->global->MAIN_MAIL_SMTP_SERVER)) ini_set('SMTP',$conf->global->MAIN_MAIL_SMTP_SERVER);
				if (! empty($conf->global->MAIN_MAIL_SMTP_PORT))   ini_set('smtp_port',$conf->global->MAIN_MAIL_SMTP_PORT);

				dol_syslog("CMailFile::sendfile: mail start SMTP=".ini_get('SMTP').", PORT=".ini_get('smtp_port'), LOG_DEBUG);

				$this->message=stripslashes($this->message);

				if (! empty($conf->global->MAIN_MAIL_DEBUG)) $this->dump_mail();

				$res =  $this->simplemail->sendmail();

				if (! $res)
				{
					$this->error="Failed to send mail to SMTP=".ini_get('SMTP').", PORT=".ini_get('smtp_port')."<br>Check your server logs and your firewalls setup";
					dol_syslog("CMailFile::sendfile: mail end error ".$this->error, LOG_ERR);
					dol_syslog("CMailFile::sendfile: ".$this->simplemail->error_log, LOG_ERR);
				}
				else
				{
					dol_syslog("CMailFile::sendfile: mail end success", LOG_DEBUG);
					dol_syslog("CMailFile::sendfile: ".$this->simplemail->error_log, LOG_DEBUG);
				}

				if (isset($_SERVER["WINDIR"]))
				{
					@ini_restore('sendmail_from');
				}

				// Forcage parametres
				if (! empty($conf->global->MAIN_MAIL_SMTP_SERVER))	ini_restore('SMTP');
				if (! empty($conf->global->MAIN_MAIL_SMTP_PORT)) 	ini_restore('smtp_port');
			}
			else if ($conf->global->MAIN_MAIL_SENDMODE == 'smtps')
			{

				// Use SMTPS library
				// ------------------------------------------
				$this->smtps->setTransportType(0);	// Only this method is coded in SMTPs library

				// Forcage parametres
				if (empty($conf->global->MAIN_MAIL_SMTP_SERVER)) $conf->global->MAIN_MAIL_SMTP_SERVER=ini_get('SMTP');
				if (empty($conf->global->MAIN_MAIL_SMTP_PORT))   $conf->global->MAIN_MAIL_SMTP_PORT=ini_get('smtp_port');

				$this->smtps->setHost($conf->global->MAIN_MAIL_SMTP_SERVER);
				$this->smtps->setPort($conf->global->MAIN_MAIL_SMTP_PORT); //587 or 25;

				if (! empty($conf->global->MAIN_MAIL_SMTPS_ID)) $this->smtps->setID($conf->global->MAIN_MAIL_SMTPS_ID);
				if (! empty($conf->global->MAIN_MAIL_SMTPS_PW)) $this->smtps->setPW($conf->global->MAIN_MAIL_SMTPS_PW);
				//$smtps->_msgReplyTo  = 'reply@web.com';

				$dest=$this->smtps->getFrom('org');
				if (! $dest)
				{
					$this->error="Failed to send mail to SMTP=".$conf->global->MAIN_MAIL_SMTP_SERVER.", PORT=".$conf->global->MAIN_MAIL_SMTP_PORT."<br>Recipient address '$dest' invalid";
					dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);
				}
				else
				{
					if (! empty($conf->global->MAIN_MAIL_DEBUG)) $this->smtps->setDebug(true);
					$result=$this->smtps->sendMsg();
					//print $resultvalue;
				}

				if (! empty($conf->global->MAIN_MAIL_DEBUG)) $this->dump_mail();

				$result=$this->smtps->getErrors();
				if (empty($this->error) && empty($result)) $res=true;
				else $res=false;

			}
			else
			{

				// Send mail method not correctly defined
				// --------------------------------------

				return 'Bad value for MAIN_MAIL_SENDMODE constant';
			}

		}
		else
		{
			$this->error='No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
			dol_syslog("CMailFile::sendfile: ".$this->error, LOG_WARN);
		}

		error_reporting($errorlevel);              // Reactive niveau erreur origine

		return $res;
	}


	// Encode subject according to RFC 2822 - http://en.wikipedia.org/wiki/MIME#Encoded-Word
	function encodetorfc2822($stringtoencode)
	{
		global $conf;
		return '=?'.$conf->file->character_set_client.'?B?'.base64_encode($stringtoencode).'?=';
	}

	/**
	 *	\brief      Permet d'encoder un fichier
	 *	\param      sourcefile
	 *	\return     <0 si erreur, fichier encode si ok
	 */
	function _encode_file($sourcefile)
	{
		if (is_readable($sourcefile))
		{
			$fd = fopen($sourcefile, "r");
			$contents = fread($fd, filesize($sourcefile));
			$encoded = chunk_split(base64_encode($contents), 68, $this->eol);
			fclose($fd);
			return $encoded;
		}
		else
		{
			$this->error="Error: Can't read file '$sourcefile'";
			dol_syslog("CMailFile::encode_file: ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *    \brief  Ecrit le mail dans un fichier. Utilisation pour le debuggage.
	 */
	function dump_mail()
	{
		global $conf,$dolibarr_main_data_root;

		if (@is_writeable($dolibarr_main_data_root))	// Avoid fatal error on fopen with open_basedir
		{
			$fp = fopen($dolibarr_main_data_root."/dolibarr_mail.log","w");

			if ($conf->global->MAIN_MAIL_SENDMODE == 'mail')
			{
				fputs($fp, $this->headers);
				fputs($fp, $this->eol);			// This eol is added by the mail function, so we add it in log
				fputs($fp, $this->message);
			}
			elseif ($conf->global->MAIN_MAIL_SENDMODE == 'smtps')
			{
				fputs($fp, $this->smtps->log);
			}

			fclose($fp);
			if (! empty($conf->global->MAIN_UMASK))
			@chmod($outputfile, octdec($conf->global->MAIN_UMASK));
		}
	}

	/**
	 *	\brief		Create SMTP headers
	 */
	function write_smtpheaders()
	{
		global $conf;
		$out = "";

		// Sender
		//$out .= "X-Sender: ".getValidAddress($this->addr_from,2).$this->eol;
		$out .= "From: ".$this->getValidAddress($this->addr_from,0,1).$this->eol;
		$out .= "Return-Path: ".$this->getValidAddress($this->addr_from,0,1).$this->eol;
		if (isset($this->reply_to)  && $this->reply_to)  $out .= "Reply-To: ".$this->getValidAddress($this->reply_to,2).$this->eol;
		if (isset($this->errors_to) && $this->errors_to) $out .= "Errors-To: ".$this->getValidAddress($this->errors_to,2).$this->eol;

		// Receiver
		if (isset($this->addr_cc)   && $this->addr_cc)   $out .= "Cc: ".$this->getValidAddress($this->addr_cc,2).$this->eol;
		if (isset($this->addr_bcc)  && $this->addr_bcc)  $out .= "Bcc: ".$this->getValidAddress($this->addr_bcc,2).$this->eol;

		// Accuse reception
		if (isset($this->deliveryreceipt) && $this->deliveryreceipt == 1) $out .= "Disposition-Notification-To: ".$this->getValidAddress($this->addr_from,2).$this->eol;

		//$out .= "X-Priority: 3".$this->eol;
		$out.= "X-Mailer: Dolibarr version " . DOL_VERSION ." (using php mail)".$this->eol;
		$out.= "MIME-Version: 1.0".$this->eol;

		$out.= "Content-Type: multipart/related; boundary=\"".$this->mime_boundary."\"".$this->eol;
		$out.= "Content-Transfer-Encoding: 8bit".$this->eol;

		$out.=$this->eol;
		dol_syslog("CMailFile::write_smtpheaders smtp_header=\n".$out);
		return $out;
	}


	/**
	 *	\brief 		Creation header MIME
	 *	\param 		filename_list
	 *	\param 		mimefilename_list
	 */
	function write_mimeheaders($filename_list, $mimefilename_list)
	{
		$mimedone=0;
		$out = "";

		if ($filename_list)
		{
			for ($i = 0; $i < count($filename_list); $i++)
			{
				if ($filename_list[$i])
				{
					if ($mimefilename_list[$i]) $filename_list[$i] = $mimefilename_list[$i];
					$out.= "X-attachments: $filename_list[$i]".$this->eol;
				}
			}
		}

		dol_syslog("CMailFile::write_mimeheaders mime_header=\n".$out, LOG_DEBUG);
		return $out;
	}

	/**
	 *	\brief 		Permet d'ecrire le corps du message (mode = 'mail')
	 *	\param 		msgtext
	 */
	function write_body($msgtext)
	{
		global $conf;

		$out='';

		if ($this->msgishtml)
		{
			$out.= "--" . $this->mime_boundary . $this->eol;
			$out.= "Content-Type: text/html; charset=\"".$conf->file->character_set_client."\"".$this->eol;
		}
		else
		{
			$out.= "--" . $this->mime_boundary . $this->eol;
			$out.= "Content-Type: text/plain; charset=\"".$conf->file->character_set_client."\"".$this->eol;
		}
		$out.= $this->eol;

		if ($this->msgishtml)
		{
			// Check if html header already in message
			$strContent = $this->checkIfHTML($msgtext);
		}
		else
		{
			$strContent.= $msgtext;
		}

		// Make RFC821 Compliant, replace bare linefeeds
    $strContent = preg_replace("/(?<!\r)\n/si", "\r\n", $strContent );

    $strContent = rtrim(wordwrap($strContent));

    $out.=$strContent.$this->eol;

		return $out;
	}

	/**
	 * Correct an uncomplete html string
	 *
	 * @param unknown_type $msg
	 * @return unknown
	 */
	function checkIfHTML($msg)
	{
		if (!eregi('^[ \t]*<html',$msg))
		{
			$out = "<html><head><title></title>";
			if (!empty($this->styleCSS)) $out.= $this->styleCSS;
			$out.= "</head><body>";
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
	 * Build a css style
	 *
	 * @return css
	 */
	function buildCSS()
	{
		$out = '';

		if (!empty($this->css))
		{
			$out.= '<style type="text/css">';
			$out.= 'body {';

			if ($this->css['bgcolor'])
			{
				$out.= '  background-color: '.$this->css['bgcolor'].';';
			}
			if ($this->css['bgimage'])
			{
				// Todo: r�cup�rer cid
				$out.= '  background-image: url("cid:'.$this->css['bgimage_cid'].'");';
			}
			$out.= '}';
			$out.= '</style>';
		}

	  return $out;
	}

	/**
	 \brief 		Permet d'attacher un fichier
	 \param 		filename_list		Tableau
	 \param 		mimetype_list		Tableau
	 \param 		mimefilename_list	Tableau
	 \return		out					Chaine fichiers encodes
	 */
	function write_files($filename_list,$mimetype_list,$mimefilename_list)
	{
		$out = '';

		for ($i = 0; $i < count($filename_list); $i++)
		{
			if ($filename_list[$i])
			{
				dol_syslog("CMailFile::write_files: i=$i");
				$encoded = $this->_encode_file($filename_list[$i]);
				if ($encoded >= 0)
				{
					if ($mimefilename_list[$i]) $filename_list[$i] = $mimefilename_list[$i];
					if (! $mimetype_list[$i]) { $mimetype_list[$i] = "application/octet-stream"; }

					$out.= "--" . $this->mime_boundary . $this->eol;
					$out.= "Content-Type: " . $mimetype_list[$i] . "; name=\"".$filename_list[$i]."\"".$this->eol;
					$out.= "Content-Transfer-Encoding: base64".$this->eol;
					$out.= "Content-Disposition: attachment; filename=\"".$filename_list[$i]."\"".$this->eol;
					$out.= "Content-Description: \""."File Attachment"."\"".$this->eol;
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
	 \brief 		Permet d'attacher une image
	 \param 		images_list		Tableau
	 \return		out					Chaine images encodees
	 */
	function write_images($images_list)
	{
		$out = '';

		if ($images_list)
		{
			foreach ($images_list as $img)
			{
				dol_syslog("CMailFile::write_images: i=$i");

				$out.= "--" . $this->mime_boundary . $this->eol;
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


	function check_server_port($host,$port)
	{
		$_retVal=0;

		if (function_exists('fsockopen'))
		{
			//See if we can connect to the SMTP server
			if ( $socket = @fsockopen($host,       // Host to 'hit', IP or domain
			$port,       // which Port number to use
			$errno,           // actual system level error
			$errstr,          // and any text that goes with the error
			5) )  // timeout for reading/writing data over the socket
			{
				// Windows still does not have support for this timeout function
				if (function_exists('socket_set_timeout'))
				socket_set_timeout($socket, 5, 0);

				// Check response from Server
				if ( $_retVal = $this->server_parse($socket, "220") )
				$_retVal = $socket;
			}
			else
			{
				$this->error = 'Error '.$errno.' - '.$errstr;
			}
		}
		return $_retVal;
	}

	// This function has been modified as provided
	// by SirSir to allow multiline responses when
	// using SMTP Extensions
	//
	function server_parse($socket, $response)
	{
		/**
		 * Default return value
		 *
		 * Returns constructed SELECT Object string or boolean upon failure
		 * Default value is set at TRUE
		 *
		 * @var mixed $_retVal Indicates if Object was created or not
		 * @access private
		 * @static
		 */
		$_retVal = true;
		$server_response = '';

		while ( substr($server_response,3,1) != ' ' )
		{
			if( !( $server_response = fgets($socket, 256) ) )
			{
				$this->error="Couldn't get mail server response codes";
				$_retVal = false;
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
	 \brief 		Recherche la presence d'images dans le message html
	 \param 		images_dir		Emplacement des images
	 \return		int         	>0 if OK, <0 if KO
	 */
	function findHtmlImages($images_dir)
	{
		// Build the list of image extensions
		$extensions = array_keys($this->image_types);

		preg_match_all('/(?:"|\')([^"\']+\.('.implode('|', $extensions).'))(?:"|\')/Ui', $this->html, $matches);

		if ($matches)
		{
			$i=0;
			foreach ($matches[1] as $full)
			{
				eregi('file=([A-Za-z0-9_\-\/]+[.]?[A-Za-z0-9]+)?$',$full,$regs);
				$img = $regs[1];

				if (file_exists($images_dir.'/'.$img))
				{
					// Image path in src
					$src = preg_quote($full);

					// Image full path
					$this->html_images[$i]["fullpath"] = $images_dir.'/'.$img;

					// Image name
					$this->html_images[$i]["name"] = $img;

					// Content type
					$ext = preg_replace('#^.*\.(\w{3,4})$#e', 'strtolower("$1")', $img);
					$this->html_images[$i]["content_type"] = $this->image_types[$ext];

					// cid
					$this->html_images[$i]["cid"] = md5(uniqid(time()));

					$this->html = preg_replace("#src=\"$src\"|src='$src'#", "src=\"cid:".$this->html_images[$i]["cid"]."\"", $this->html);
				}
				$i++;
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
							eregi('([A-Za-z0-9_-]+[.]?[A-Za-z0-9]+)?$',$img["name"],$regs);
							$imgName = $regs[1];

							$this->images_encoded[$i]['name'] = $imgName;
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
	 *	\brief      Return an address for SMTP protocol
	 *	\param      adresses		Example: 'John Doe <john@doe.com>' or 'john@doe.com'
	 *	\param		format			0=Auto, 1=emails with <>, 2=emails without <>
	 * 	\param		encode			1=Encode name to RFC2822
	 *	\return	    string			If format 1: '<john@doe.com>' or 'John Doe <john@doe.com>'
	 *								If format 2: 'john@doe.com'
	 */
	function getValidAddress($adresses,$format,$encode='')
	{
		global $conf;

		$ret='';

		$arrayaddress=split(',',$adresses);

		// Boucle sur chaque composant de l'adresse
		foreach($arrayaddress as $val)
		{
			if (eregi('^(.*)<(.*)>$',trim($val),$regs))
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
				$newemail='';
				if ($format == 2)
				{
					$newemail=$email;
				}
				if ($format == 1)
				{
					$neweamil='<'.$email.'>';
				}
				if ($format == 0)
				{
					if ($conf->global->MAIN_MAIL_NO_FULL_EMAIL) $newemail='<'.$email.'>';
					elseif (! $name) $newemail='<'.$email.'>';
					else $newemail=($encode?$this->encodetorfc2822($name):$name).' <'.$email.'>';
				}

				$ret=($ret ? $ret.',' : '').$newemail;
			}
		}

		return $ret;
	}
}

?>