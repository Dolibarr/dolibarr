<?php
/* Copyright (C) 2000-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
        \file       htdocs/telephonie/dolibarrmail.class.php
        \brief      Classe permettant d'envoyer des mail avec attachements, reecriture de CMailFile
        \author     Dan Potter.
        \author     Eric Seigne
        \author     Rodolphe Quiedeville
        \author     Laurent Destailleur.
        \version    $Revision$
*/

/** 
  \class      	DolibarrMail
  \brief      	Classe permettant d'envoyer des attachements par mail
  \deprecated	Utiliser CMailFile a la place car plus fiable et plus performant
*/

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

  /**
     \brief DolibarrMail
     \param subject
     \param to
     \param from
     \param msg
  */

  function DolibarrMail($subject, $to, $from, $msg)
  {
    $this->subject = $subject;
    $this->addr_to = $to;
    $this->from = $from;
    
    $this->message = wordwrap($msg, 78);

    $this->errors_to = $from;

    $this->boundary = md5( uniqid("dolibarr") );

    $this->addr_bcc = "";
    $this->addr_cc = "";
    $this->reply_to = "";

    $this->filename_list = array();
  }

  /**
     \brief PrepareFile
     \param filename_list
     \param mimetype_list
     \param mimefilename_list
  */

  function PrepareFile($filename_list, $mimetype_list, $mimefilename_list)
  {
    $this->filename_list = $filename_list;

    $this->mime_headers="";

    if (count($filename_list))
      {
	$this->mime_headers = $this->write_mimeheaders($filename_list, $mimefilename_list);

	$this->text_encoded = $this->attach_file($filename_list,
						 $mimetype_list,
						 $mimefilename_list);
      }
  }
    
  /**
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

	$out .= "Content-Type: " . $mimetype_list[$i]."\n";
	$out .= ' name="'.$filename_list[$i].'"'."\n";
	$out .= "Content-Transfer-Encoding: base64\n";
	$out .= "Content-Disposition: inline;\n";
	$out .= " filename=\"".$filename_list[$i]."\"\n\n";
	$out .= $encoded . "\n";
      }
    $out = $out . "--".$this->boundary . "\n";

    return $out; 
    // added -- to notify email client attachment is done
  }
  
  /**
     \brief     Permet d'encoder un fichier
     \param     sourcefile
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
	dolibarr_syslog("DolibarrMail::encode_file Erreur");
      }
    return $encoded;
  }
  
  /**
     \brief     Envoi le mail
     \return    boolean     vrai si mail envoyé, faux sinon
  */
  
  function sendfile()
  {

    $this->smtp_headers = $this->write_smtpheaders();

    $this->text_body = $this->write_body();

    $headers = $this->smtp_headers . $this->mime_headers;
    $message_comp = $this->text_body . $this->text_encoded;

    if ($this->errors_to)
      {
	//dolibarr_syslog("DolibarrMail::sendfile with errorsto : ".$this->errors_to);
	$res = mail($this->addr_to,$this->subject,stripslashes($message_comp),$headers,"-f".$this->errors_to);
      }
    else
      {
	//dolibarr_syslog("DolibarrMail::sendfile without errorsto");
	$res = mail($this->addr_to,$this->subject,stripslashes($message_comp),$headers);
      }

    $this->write_to_file();

    return $res; 
  }

  /**
   *    \brief  Ecrit le mail dans un fichier
   *            Utilisation pour le debuggage
   */
  function write_to_file()
  {
    $this->smtp_headers = $this->write_smtpheaders();

    $this->text_body = $this->write_body();

    $headers = $this->smtp_headers . $this->mime_headers;
    $message_comp = $this->text_body . $this->text_encoded;

    $fp = fopen("/tmp/mail","w");
    fputs($fp, $headers);
    fputs($fp, $message_comp);
    fclose($fp);
  }

  /**
        \brief  Permet d'ecrire le corps du message
  */
  
  function write_body()
  {
    $out = "\n";
    if (count($this->filename_list))
      {
	$out = $out . "--".$this->boundary . "\n";
	$out = $out . 'Content-Type: text/plain; charset="iso-8859-15"'."\n";
	$out .= "Content-Transfer-Encoding: 8bit\n\n";
      }
    else
      {
	//dolibarr_syslog("DolibarrMail::write_body");
      }

    $out = $out . $this->message . "\n\n";
    return $out;
  }
  
  /**
     \brief création des headers mime
     \param filename_list
     \param mimefilename_list
  */
  
  function write_mimeheaders($filename_list, $mimefilename_list)
  {
    $out = "MIME-Version: 1.0\n";
    $out = $out . 'Content-type: multipart/mixed; '."\n";
    $out = $out . ' boundary="'.$this->boundary.'"'."\n";

    //    $out = $out . "Content-transfer-encoding: 8BIT\n";

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

  /**
     \brief création des headers smtp
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
    
    //    if($this->errors_to != "")
    //$out = $out . "Errors-to: ".$this->errors_to."\n";

    $out = $out . "X-Mailer: Dolibarr version " . DOL_VERSION ."\n";
    $out = $out . "X-Sender: $this->from\n";
    
    
    return $out;
  }

}

?>
