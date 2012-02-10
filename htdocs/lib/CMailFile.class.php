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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 *
 * Lots of code inspired from Dan Potter's CMailFile class
 */

/**
 *      \file       htdocs/lib/CMailFile.class.php
 *      \brief      File of class to send emails (with attachments or not)
 *      \author     Dan Potter.
 *      \author	    Eric Seigne
 *      \author	    Laurent Destailleur.
 */

/**
 *      \class      CMailFile
 *      \brief      Class to send emails (with attachments or not)
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

	var $mixed_boundary;
	var $related_boundary;
	var $alternative_boundary;
	var $deliveryreceipt;

	var $eol;
	var $atleastonefile=0;
	var $error='';

	var $smtps;			// Contains SMTPs object (if this method is used)

	//CSS
	var $css;
	//! Defined css style for body background
	var $styleCSS;
	//! Defined bacckground directly in body tag
	var $bodyCSS;

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
	 *	CMailFile
	 *
	 *	@param 	subject             Topic/Subject of mail
	 *	@param 	to                  Recipients emails (RFC 2822: "Nom prenom <email>[, ...]" ou "email[, ...]" ou "<email>[, ...]")
	 *	@param 	from                Sender email      (RFC 2822: "Nom prenom <email>[, ...]" ou "email[, ...]" ou "<email>[, ...]")
	 *	@param 	msg                 Message
	 *	@param 	filename_list       List of files to attach (full path of filename on file system)
	 *	@param 	mimetype_list       List of MIME type of attached files
	 *	@param 	mimefilename_list   List of attached file name in message
	 *	@param 	addr_cc             Email cc
	 *	@param 	addr_bcc            Email bcc
	 *	@param 	deliveryreceipt		Ask a delivery receipt
	 *	@param 	msgishtml       	1=String IS already html, 0=String IS NOT html, -1=Unknown need autodetection
	 *	@param 	errors_to      		Email errors
	 *	@param	css			        Css option
	 */
	function CMailFile($subject,$to,$from,$msg,
	$filename_list=array(),$mimetype_list=array(),$mimefilename_list=array(),
	$addr_cc="",$addr_bcc="",$deliveryreceipt=0,$msgishtml=0,$errors_to='',$css='')
	{
		global $conf;

		// We define end of line (RFC 822bis section 2.3)
		$this->eol="\r\n";
		// eol2 is for header fields to manage bugged MTA with option MAIN_FIX_FOR_BUGGED_MTA
		$this->eol2=$this->eol;
		if (! empty($conf->global->MAIN_FIX_FOR_BUGGED_MTA)) $this->eol2="\n";

		// On defini mixed_boundary
		$this->mixed_boundary = md5(uniqid("dolibarr1"));

		// On defini related_boundary
		$this->related_boundary = md5(uniqid("dolibarr2"));

		// On defini alternative_boundary
		$this->alternative_boundary = md5(uniqid("dolibarr3"));

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

		// Add autocopy to
		if (! empty($conf->global->MAIN_MAIL_AUTOCOPY_TO)) $addr_bcc.=($addr_bcc?', ':'').$conf->global->MAIN_MAIL_AUTOCOPY_TO;

		// Action according to choosed sending method
		if ($conf->global->MAIN_MAIL_SENDMODE == 'mail')
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
			$this->errors_to = $errors_to;
			$this->addr_to = $to;
			$this->addr_cc = $addr_cc;
			$this->addr_bcc = $addr_bcc;
			$this->deliveryreceipt = $deliveryreceipt;
			$smtp_headers = $this->write_smtpheaders();

			// Define mime_headers
			$mime_headers = $this->write_mimeheaders($filename_list, $mimefilename_list);

			if (! empty($this->html))
			{
				if (!empty($css))
				{
					$this->css = $css;
					$this->buildCSS();
				}

				$msg = $this->html;
			}

			// Define body in text_body
			$text_body = $this->write_body($msg);

			// Encode images
			if ($this->atleastoneimage)
			{
				$images_encoded = $this->write_images($this->images_encoded);
				// always end related and end alternative after inline images
				$images_encoded.= "--" . $this->related_boundary . "--" . $this->eol;
				$images_encoded.= $this->eol . "--" . $this->alternative_boundary . "--" . $this->eol;
				$images_encoded.= $this->eol;
			}

			// Add attachments to text_encoded
			if ($this->atleastonefile)
			{
				$files_encoded = $this->write_files($filename_list,$mimetype_list,$mimefilename_list);
			}

			// We now define $this->headers and $this->message
			$this->headers = $smtp_headers . $mime_headers;

			$this->message = $text_body . $images_encoded . $files_encoded;
			$this->message.= "--" . $this->mixed_boundary . "--" . $this->eol;

			// On nettoie le header pour qu'il ne se termine pas par un retour chariot.
			// Ceci evite aussi les lignes vides en fin qui peuvent etre interpretees
			// comme des injections mail par les serveurs de messagerie.
			$this->headers = preg_replace("/([\r\n]+)$/i","",$this->headers);
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
					$this->error="Failed to send mail with php mail to HOST=".ini_get('SMTP').", PORT=".ini_get('smtp_port')."<br>Recipient address '$dest' invalid";
					dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);
				}
				else
				{
					dol_syslog("CMailFile::sendfile: mail start HOST=".ini_get('SMTP').", PORT=".ini_get('smtp_port'), LOG_DEBUG);

					$bounce = '';	// By default
					if (! empty($conf->global->MAIN_MAIL_ALLOW_SENDMAIL_F))
					{
						// le return-path dans les header ne fonctionne pas avec tous les MTA
						// Le passage par -f est donc possible si la constante MAIN_MAIL_ALLOW_SENDMAIL_F est definie.
						// La variable definie pose des pb avec certains sendmail securisee (option -f refusee car dangereuse)
						$bounce .= ($bounce?' ':'').(! empty($conf->global->MAIN_MAIL_ERRORS_TO) ? '-f' . $conf->global->MAIN_MAIL_ERRORS_TO : ($this->addr_from != '' ? '-f' . $this->addr_from : '') );
					}
                    if (! empty($conf->global->MAIN_MAIL_SENDMAIL_FORCE_BA))    // To force usage of -ba option. This option tells sendmail to read From: or Sender: to setup sender
                    {
                        $bounce .= ($bounce?' ':'').'-ba';
                    }

					$this->message=stripslashes($this->message);

					if (! empty($conf->global->MAIN_MAIL_DEBUG)) $this->dump_mail();

					if (! empty($bounce)) $res = mail($dest,$this->encodetorfc2822($this->subject),$this->message,$this->headers, $bounce);
					else $res = mail($dest,$this->encodetorfc2822($this->subject),$this->message,$this->headers);

					if (! $res)
					{
						$this->error="Failed to send mail with php mail to HOST=".ini_get('SMTP').", PORT=".ini_get('smtp_port')."<br>Check your server logs and your firewalls setup";
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

				// If we use SSL/TLS
				$server=$conf->global->MAIN_MAIL_SMTP_SERVER;
				if (! empty($conf->global->MAIN_MAIL_EMAIL_TLS) && function_exists('openssl_open')) $server='ssl://'.$server;

				$this->smtps->setHost($server);
				$this->smtps->setPort($conf->global->MAIN_MAIL_SMTP_PORT); // 25, 465...;

				if (! empty($conf->global->MAIN_MAIL_SMTPS_ID)) $this->smtps->setID($conf->global->MAIN_MAIL_SMTPS_ID);
				if (! empty($conf->global->MAIN_MAIL_SMTPS_PW)) $this->smtps->setPW($conf->global->MAIN_MAIL_SMTPS_PW);
				//$smtps->_msgReplyTo  = 'reply@web.com';

				$res=true;
				$from=$this->smtps->getFrom('org');
				if (! $from)
				{
					$this->error="Failed to send mail with smtps lib to HOST=".$server.", PORT=".$conf->global->MAIN_MAIL_SMTP_PORT."<br>Sender address '$from' invalid";
					dol_syslog("CMailFile::sendfile: mail end error=".$this->error, LOG_ERR);
					$res=false;
				}
				$dest=$this->smtps->getTo();
				if (! $dest)
				{
					$this->error="Failed to send mail with smtps lib to HOST=".$server.", PORT=".$conf->global->MAIN_MAIL_SMTP_PORT."<br>Recipient address '$dest' invalid";
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
			dol_syslog("CMailFile::sendfile: ".$this->error, LOG_WARNING);
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
	 * Read a file on disk and return encoded content for emails (mode = 'mail')
	 *
	 * @param      sourcefile
	 * @return     <0 if KO, encoded string if OK
	 */
	function _encode_file($sourcefile)
	{
		$newsourcefile=dol_osencode($sourcefile);

		if (is_readable($newsourcefile))
		{
			$contents = file_get_contents($newsourcefile);	// Need PHP 4.3
			$encoded = chunk_split(base64_encode($contents), 76, $this->eol);
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
	 *  Write content of a SMTP request into a dump file (mode = all)
	 *  Used for debugging.
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
     * Correct an uncomplete html string
     *
     * @param       $msg
     * @return
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
     * Build a css style (mode = all)
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
                $this->bodyCSS.= ' BGCOLOR="'.$this->css['bgcolor'].'"';
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
	 * @return	smtp headers
	 */
	function write_smtpheaders()
	{
		global $conf;
		$out = "";

		// Sender
		//$out .= "Sender: ".getValidAddress($this->addr_from,2).$this->eol2;
		$out .= "From: ".$this->getValidAddress($this->addr_from,0,1).$this->eol2;
		$out .= "Return-Path: ".$this->getValidAddress($this->addr_from,0,1).$this->eol2;
		if (isset($this->reply_to)  && $this->reply_to)  $out .= "Reply-To: ".$this->getValidAddress($this->reply_to,2).$this->eol2;
		if (isset($this->errors_to) && $this->errors_to) $out .= "Errors-To: ".$this->getValidAddress($this->errors_to,2).$this->eol2;

		// Receiver
		if (isset($this->addr_cc)   && $this->addr_cc)   $out .= "Cc: ".$this->getValidAddress($this->addr_cc,2).$this->eol2;
		if (isset($this->addr_bcc)  && $this->addr_bcc)  $out .= "Bcc: ".$this->getValidAddress($this->addr_bcc,2).$this->eol2;

		// Delivery receipt
		if (isset($this->deliveryreceipt) && $this->deliveryreceipt == 1) $out .= "Disposition-Notification-To: ".$this->getValidAddress($this->addr_from,2).$this->eol2;

		//$out .= "X-Priority: 3".$this->eol2;
		$out.= "X-Mailer: Dolibarr version " . DOL_VERSION ." (using php mail)".$this->eol2;
		$out.= "MIME-Version: 1.0".$this->eol2;

		$out.= "Content-Type: multipart/mixed; boundary=\"".$this->mixed_boundary."\"".$this->eol2;
		$out.= "Content-Transfer-Encoding: 8bit".$this->eol2;

		dol_syslog("CMailFile::write_smtpheaders smtp_header=\n".$out);
		return $out;
	}


	/**
	 * Create header MIME (mode = 'mail')
	 *
	 * @param 		filename_list
	 * @param 		mimefilename_list
	 * @return		mime headers
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
	 * @param 		msgtext
	 */
	function write_body($msgtext)
	{
		global $conf;

		$out='';

		if ($this->atleastoneimage)
		{
			$out.= "--" . $this->mixed_boundary . $this->eol;
			$out.= "Content-Type: multipart/alternative; boundary=\"".$this->alternative_boundary."\"".$this->eol;
			$out.= $this->eol;
			$out.= "--" . $this->alternative_boundary . $this->eol;
		}
		else
		{
			$out.= "--" . $this->mixed_boundary . $this->eol;
		}

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

        //$strContent = rtrim(chunk_split($strContent));    // Function chunck_split seems bugged
        $strContent = rtrim(wordwrap($strContent));

		if ($this->msgishtml)
		{
			if ($this->atleastoneimage)
			{
				$out.= "Content-Type: text/plain; charset=".$conf->file->character_set_client.$this->eol;
				$out.= $this->eol.strip_tags($strContent).$this->eol; // Add plain text message
				$out.= "--" . $this->alternative_boundary . $this->eol;
				$out.= "Content-Type: multipart/related; boundary=\"".$this->related_boundary."\"".$this->eol;
				$out.= $this->eol;
				$out.= "--" . $this->related_boundary . $this->eol;
			}
			$out.= "Content-Type: text/html; charset=".$conf->file->character_set_client.$this->eol;
			$out.= $this->eol.$strContent.$this->eol;
		}
		else
		{
			$out.= "Content-Type: text/plain; charset=".$conf->file->character_set_client.$this->eol;
			$out.= $this->eol.$strContent.$this->eol;
		}

		$out.= $this->eol;

		return $out;
	}

	/**
	 * Attach file to email (mode = 'mail')
	 *
	 * @param 		filename_list		Tableau
	 * @param 		mimetype_list		Tableau
	 * @param 		mimefilename_list	Tableau
	 * @return		out					Chaine fichiers encodes
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
					if (! $mimetype_list[$i]) { $mimetype_list[$i] = "application/octet-stream"; }

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
	 * @param 		images_list		Tableau
	 * @return		out				Chaine images encodees
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
	 * @param 		$host		Add ssl:// for SSL/TLS.
	 * @param 		$port		Example: 25, 465
	 * @return 		Socket id if ok, 0 if KO
	 */
	function check_server_port($host,$port)
	{
		$_retVal=0;
		$timeout=5;	// Timeout in seconds

		if (function_exists('fsockopen'))
		{
			dol_syslog("Try socket connection to host=".$host." port=".$port);
			//See if we can connect to the SMTP server
			if ( $socket = @fsockopen($host,       // Host to test, IP or domain. Add ssl:// for SSL/TLS.
			$port,       // which Port number to use
			$errno,      // actual system level error
			$errstr,     // and any text that goes with the error
			$timeout) )  // timeout for reading/writing data over the socket
			{
				// Windows still does not have support for this timeout function
				if (function_exists('stream_set_timeout')) stream_set_timeout($socket, $timeout, 0);

				dol_syslog("Now we wait for answer 220");

				// Check response from Server
				if ( $_retVal = $this->server_parse($socket, "220") ) $_retVal = $socket;
			}
			else
			{
				$this->error = 'Error '.$errno.' - '.$errstr;
			}
		}
		return $_retVal;
	}

	/**
	 * This function has been modified as provided by SirSir to allow multiline responses when
	 * using SMTP Extensions.
	 *
	 * @param      socket
	 * @param      response
	 * @return     boolean
	 */
	function server_parse($socket, $response)
	{
		$_retVal = true;	// Indicates if Object was created or not
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
	 * Seearch images into html message and init array this->images_encoded if found
	 *
	 * @param 		images_dir		Location of physical images files
	 * @return		int         	>0 if OK, <0 if KO
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
				if (preg_match('/file=([A-Za-z0-9_\-\/]+[\.]?[A-Za-z0-9]+)?$/i',$full,$regs))
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
						$ext = preg_replace('/^.*\.(\w{3,4})$/e', 'strtolower("$1")', $img);
						$this->html_images[$i]["content_type"] = $this->image_types[$ext];

						// cid
						$this->html_images[$i]["cid"] = md5(uniqid(time()));
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
	 * Return an address for SMTP protocol
	 *
	 * @param       adresses		Example: 'John Doe <john@doe.com>' or 'john@doe.com'
	 * @param		format			0=Auto, 1=emails with <>, 2=emails without <>
	 * @param		encode			1=Encode name to RFC2822
	 * @return	    string			If format 1: '<john@doe.com>' or 'John Doe <john@doe.com>'
	 *								If format 2: 'john@doe.com'
	 */
	function getValidAddress($adresses,$format,$encode='')
	{
		global $conf;

		$ret='';

		$arrayaddress=explode(',',$adresses);

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
					$newemail='<'.$email.'>';
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