<?php
/* Copyright (C) 2000-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * $Id$
 * $Source$
 *
 * Lots of code inspired from Dan Potter's CMailFile class
 */


/*!	\file htdocs/lib/CMailFile.class.php
  \brief Classe permettant d'envoyer des mail avec attachements, recriture de CMailFile
  \author Dan Potter.
  \author Eric Seigne
  \author Rodolphe Quiedeville
  \author Laurent Destailleur.
  \version $Revision$
*/

/*! \class CMailFile
  \brief Classe permettant d'envoyer des attachements par mail
  \remarks Eric Seigne <eric.seigne@ryxeo.com> 2004.01.08
  \remarks ajout de la gestion des cc:
  \remarks ajout de l'expedition de plusieurs fichiers
  
  \remarks Laurent Destailleur 2004.02.10
  \remarks correction d'un disfonctionnement à la gestion des attachements multiples
*/

// simple class that encapsulates mail() with addition of mime file attachment.
class DolibarrMail
{
  var $subject;
  var $addr_to;
  var $addr_cc;
  var $addr_bcc;
  var $text_body;
  var $text_encoded;
  var $mime_headers;
  var $boundary;
  var $smtp_headers;

/*!
  \brief DolibarrMail
  \param subject
  \param to
  \param from
  \param msg
  \param filename_list
  \param mimetype_list
  \param mimefilename_list
  \param addr_cc
  \param addr_bcc
*/

  function DolibarrMail($subject, $to, $from, $msg)
    {
      $this->from = $from;

      $this->message = wordwrap($msg, 78);
      $this->boundary= md5( uniqid("dolibarr") );

      $this->subject = $subject;
      $this->addr_to = $to;

      $this->errors_to = $from;

      $this->addr_bcc = "";
      $this->addr_cc = "";
      $this->reply_to = "";

      $this->filename_list = array();

      dolibarr_syslog("DolibarrMail::DolibarrMail");
      dolibarr_syslog("DolibarrMail::DolibarrMail to : ".$this->addr_to);
      dolibarr_syslog("DolibarrMail::DolibarrMail from : ".$this->from);
    }

  /*!
    \brief PrepareFile
    \param filename_list
    \param mimetype_list
    \param mimefilename_list
  */

  function PrepareFile($filename_list, $mimetype_list, $mimefilename_list)
  {
    $this->filename_list = $filename_list;

    dolibarr_syslog("DolibarrMail::PrepareFile");

    $this->mime_headers="";

    if (count($filename_list))
      {
	$this->mime_headers = $this->write_mimeheaders($filename_list, $mimefilename_list);

	$this->text_encoded = $this->attach_file($filename_list,
						 $mimetype_list,
						 $mimefilename_list);
      }
  }
    
  /*!
    \brief permet d'attacher un fichier
    \param filename_list
    \param mimetype_list
    \param mimefilename_list
  */
  
  function attach_file($filename_list,$mimetype_list,$mimefilename_list)
  {
    for ($i = 0; $i < count($filename_list); $i++)
      {
	$encoded = $this->encode_file($filename_list[$i]);

	if ($mimefilename_list[$i]) 
	  {
	    $filename_list[$i] = $mimefilename_list[$i];
	  }

	$out = $out . "--".$this->boundary . "\n";

	if (! $mimetype_list[$i])
	  { 
	    $mimetype_list[$i] = "application/octet-stream"; 
	  }

	//$out = $out . "Content-type: " . $mimetype_list[$i] . "; name=\"".$filename_list[$i]."\";\n";         
	$out = $out . "Content-type: " . $mimetype_list[$i]."\n";// . "; name=\"".$filename_list[$i]."\";\n";         
	$out = $out . "Content-Transfer-Encoding: base64\n";
	$out = $out . "Content-Disposition: attachment; filename=\"".$filename_list[$i]."\"\n\n";
	$out = $out . $encoded . "\n";
      }
    $out = $out . "--".$this->boundary . "\n";

    return $out; 
    // added -- to notify email client attachment is done
  }
  
/*!
  \brief permet d'encoder un fichier
  \param sourcefile
*/
  
  function encode_file($sourcefile)
  {
    if (is_readable($sourcefile))
      {
	$fd = fopen($sourcefile, "r");
	$contents = fread($fd, filesize($sourcefile));
	$encoded = chunk_split(base64_encode($contents));
	fclose($fd);
      }
    else
      {
	dolibarr_syslog("DolibarrMail::encode_file");
      }
    return $encoded;
  }
  
  /*!
    \brief permet d'envoyer un fichier
  */
  
  function sendfile()
  {
    dolibarr_syslog("DolibarrMail::sendfile");

    $this->smtp_headers = $this->write_smtpheaders();

    $this->text_body = $this->write_body();

    print nl2br($this->smtp_headers);
    print nl2br($this->mime_headers);
    print nl2br($this->text_body);

    $headers = $this->smtp_headers . $this->mime_headers;
    $message_comp = $this->text_body . $this->text_encoded;

    $res = mail($this->addr_to,$this->subject,stripslashes($message_comp),$headers);

    return $res; 
  }
  /*
   *
   *
   */
  function write_to_file()
  {
    dolibarr_syslog("DolibarrMail::write_to_file");

    $this->smtp_headers = $this->write_smtpheaders();

    $this->text_body = $this->write_body();

    $headers = $this->smtp_headers . $this->mime_headers;
    $message_comp = $this->text_body . $this->text_encoded;

    $fp = fopen("/tmp/mail","w");
    fputs($fp, $headers);
    fputs($fp, $message_comp);
    fclose($fp);
  }
  /*!
    \brief permet d'ecrire le corps du message
    \param msgtext
    \param filename_list
  */
  
  function write_body()
  {
    $out = "\n";
    if (count($this->filename_list))
      {
	$out = $out . "--".$this->boundary . "\n";
	$out = $out . "Content-Type: text/plain; charset=\"iso-8859-15\"\n";
	$out = $out . "Content-Disposition: inline\n\n";
      }
    else
      {
	dolibarr_syslog("DolibarrMail::write_body");
      }

    $out = $out . $this->message . "\n\n";
    return $out;
  }
  
  /*!
    \brief création des headers mime
    \param filename_list
    \param mimefilename_list
  */
  
  function write_mimeheaders($filename_list, $mimefilename_list)
  {
    $out = "Mime-version: 1.0\n";
    $out = $out . "Content-type: multipart/mixed; boundary=\"$this->boundary\"\n";
    $out = $out . "Content-transfer-encoding: 8BIT\n";

    for($i = 0; $i < count($filename_list); $i++)
      {
	if ($mimefilename_list[$i]) 
	  {
	    $filename_list[$i] = $mimefilename_list[$i];
	  }

	//$out = $out . "X-attachments: $filename_list[$i];\n\n";
      }

    return $out;
  }

/*!
  \brief création des headers smtp
  \param addr_from
  \param addr_cc
*/
  
  function write_smtpheaders()
  {
    $out = "From: $this->from\n";

    if($this->addr_cc != "")
      $out = $out . "Cc: ".$this->addr_cc."\n";
    
    if($this->addr_bcc != "")
      $out = $out . "BCc: ".$this->addr_bcc."\n";
    
    if($this->reply_to != "")
      $out = $out . "Reply-To: ".$this->reply_to."\n";
    
    if($this->errors_to != "")
      $out = $out . "Errors-to: ".$this->errors_to."\n";

    $out = $out . "X-Mailer: Dolibarr version " . DOL_VERSION ."\n";
    $out = $out . "X-Sender: $this->from\n";
    
    
    return $out;
  }

}
// end script
?>
