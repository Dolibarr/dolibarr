<?php
/* notes from Dan Potter:
Sure. I changed a few other things in here too though. One is that I let
you specify what the destination filename is (i.e., what is shows up as in
the attachment). This is useful since in a web submission you often can't
tell what the filename was supposed to be from the submission itself. I
also added my own version of chunk_split because our production version of
PHP doesn't have it. You can change that back or whatever though =).
Finally, I added an extra "\n" before the message text gets added into the
MIME output because otherwise the message text wasn't showing up.
/*
note: someone mentioned a command-line utility called 'mutt' that 
can mail attachments.
*/
/* 
If chunk_split works on your system, change the call to my_chunk_split
to chunk_split 
*/
/* Note: if you don't have base64_encode on your sytem it will not work */

/*!	\file htdocs/lib/CMailFile.class.php
		\brief Classe permettant d'envoyer des attachements par mail
		\author Dan Potter.
		\author	Eric Seigne
		\author	Rodolphe Quiedeville
		\author	Laurent Destailleur.
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
  var $mime_boundary = "--==================_846811060==_";
  var $smtp_headers;

/*!
		\brief CMailFile
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

  // CMail("sujet","email_to","email_from","email_msg",tableau du path de fichiers,tableau de type mime,tableau de noms fichiers,"chaine cc")
  function DolibarrMail($subject,$to,$from,$msg)
    {
      $this->from = $from;

      $this->message = wordwrap( $msg, 78);

      $this->subject = $subject;
      $this->addr_to = $to;
      $this->addr_bcc = "";
      $this->addr_cc = "";
      $this->reply_to = "";

    }
  

  function PrepareFile($filename_list,$mimetype_list,$mimefilename_list)
  {
    
    $this->smtp_headers = $this->write_smtpheaders();
    $this->text_body = $this->write_body($this->message, $filename_list);
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
    for ($i = 0; $i < count($filename_list); $i++) {
      $encoded = $this->encode_file($filename_list[$i]);
      if ($mimefilename_list[$i]) $filename_list[$i] = $mimefilename_list[$i];
      $out = $out . "--" . $this->mime_boundary . "\n";
      if (! $mimetype_list[$i]) { $mimetype_list[$i] = "application/octet-stream"; }
      $out = $out . "Content-type: " . $mimetype_list[$i] . "; name=\"$filename_list[$i]\";\n";         
      $out = $out . "Content-Transfer-Encoding: base64\n";
      $out = $out . "Content-disposition: attachment; filename=\"$filename_list[$i]\"\n\n";
      $out = $out . $encoded . "\n";
    }
    $out = $out . "--" . $this->mime_boundary . "--" . "\n";
    return $out; 
    // added -- to notify email client attachment is done
  }
  
/*!
  \brief permet d'encoder un fichier
  \param sourcefile
*/
  
  function encode_file($sourcefile)
  {
    //      print "<pre> on encode $sourcefile </pre>\n";
    if (is_readable($sourcefile))
      {
	$fd = fopen($sourcefile, "r");
	$contents = fread($fd, filesize($sourcefile));
	$encoded = my_chunk_split(base64_encode($contents));
	fclose($fd);
      }
    return $encoded;
  }
  
  /*!
    \brief permet d'envoyer un fichier
  */
  
  function sendfile()
  {
    $headers .= $this->smtp_headers . $this->mime_headers;
    $message = $this->text_body . $this->text_encoded;
    return mail($this->addr_to,$this->subject,stripslashes($message),$headers);
  }
  
  /*!
    \brief permet d'ecrire le body d'un message
    \param msgtext
    \param filename_list
  */
  
  function write_body($msgtext, $filename_list)
  {
    if (count($filename_list))
      {
	$out = "--" . $this->mime_boundary . "\n";
	$out = $out . "Content-Type: text/plain; charset=\"iso8859-15\"\n\n";
	//	  $out = $out . "Content-Type: text/plain; charset=\"us-ascii\"\n\n";
      }
    $out = $out . $this->message . "\n";
    return $out;
  }
  
  /*!
    \brief création des headers mime
    \param filename_list
    \param mimefilename_list
  */
  
  function write_mimeheaders($filename_list, $mimefilename_list)
  {
    $out = "MIME-version: 1.0\n";
    $out = $out . "Content-type: multipart/mixed; ";
    $out = $out . "boundary=\"$this->mime_boundary\"\n";
    $out = $out . "Content-transfer-encoding: 7BIT\n";
    for($i = 0; $i < count($filename_list); $i++) {
      if ($mimefilename_list[$i]) $filename_list[$i] = $mimefilename_list[$i];
      $out = $out . "X-attachments: $filename_list[$i];\n\n";
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
    
    $out = $out . "X-Mailer: Dolibarr version " . DOL_VERSION ."\n";
    $out = $out . "X-Sender: $this->from\n";
    $out = $out . "Errors-to: $this->from\n";
    
    return $out;
  }
}

/*!
  \brief permet de diviser une chaine (RFC2045)
  \param str
  \remarks function chunk_split qui remplace celle de php si nécéssaire
  \remarks 76 caractères par ligne, terminé par "\r\n"
*/

// usage - mimetype example "image/gif"
// $mailfile = new CMailFile($subject,$sendto,$replyto,$message,$filename,$mimetype);
// $mailfile->sendfile();

// Splits a string by RFC2045 semantics (76 chars per line, end with \r\n).
// This is not in all PHP versions so I define one here manuall.

function my_chunk_split($str)
{
  $stmp = $str;
  $len = strlen($stmp);
  $out = "";
  while ($len > 0)
    {
      if ($len >= 76)
	{
	  $out = $out . substr($stmp, 0, 76) . "\r\n";
	  $stmp = substr($stmp, 76);
	  $len = $len - 76;
	}
      else
	{
	  $out = $out . $stmp . "\r\n";
	  $stmp = ""; $len = 0;
	}
    }
  return $out;
}

// end script
?>
