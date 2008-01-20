<?php
/* Copyright (C) 2000-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * If chunk_split does not works on your system, change the call to chunk_split
 * to _chunk_split 
 */

/**
*       \file       htdocs/lib/CMailFile.class.php
*       \brief      Fichier de la classe permettant d'envoyer des mail avec attachements
*		\version    $Id$
*       \author     Dan Potter.
*       \author	    Eric Seigne
*       \author	    Laurent Destailleur.
*/

/**
*       \class      CMailFile
*       \brief      Classe d'envoi de mails et pieces jointes. Encapsule mail() avec d'eventuels attachements.
*       \remarks    Usage: $mailfile = new CMailFile($subject,$sendto,$replyto,$message,$filepath,$mimetype,$filename,$cc,$ccc);
*       \remarks           $mailfile->sendfile();
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
    

    /**
            \brief 	CMailFile
            \param 	subject             sujet
            \param 	to                  email destinataire (RFC 2822: "Nom prenom <email>[, ...]" ou "email[, ...]" ou "<email>[, ...]")
            \param 	from                email emetteur     (RFC 2822: "Nom prenom <email>[, ...]" ou "email[, ...]" ou "<email>[, ...]")
            \param 	msg                 message
            \param 	filename_list       tableau de fichiers attaches
            \param 	mimetype_list       tableau des types des fichiers attaches
            \param 	mimefilename_list   tableau des noms des fichiers attaches
            \param 	addr_cc             email cc
            \param 	addr_bcc            email bcc
            \param 	deliveryreceipt		demande accuse reception
            \param	msgishtml			1=message is a html message, 0=message is not html, 2=auto detect
    */
    function CMailFile($subject,$to,$from,$msg,
                       $filename_list=array(),$mimetype_list=array(),$mimefilename_list=array(),
                       $addr_cc="",$addr_bcc="",$deliveryreceipt=0,$msgishtml=0, $errors_to='')
    {
        dolibarr_syslog("CMailFile::CMailfile: from=$from, to=$to, addr_cc=$addr_cc, addr_bcc=$addr_bcc, errors_to=$errors_to");
        dolibarr_syslog("CMailFile::CMailfile: subject=$subject, deliveryreceipt=$deliveryreceipt, msgishtml=$msgishtml");
        foreach ($filename_list as $i => $val)
        {
        	if ($filename_list[$i])
        	{
        		$this->atleastonefile=1;
        		dolibarr_syslog("CMailFile::CMailfile: filename_list[$i]=".$filename_list[$i].", mimetype_list[$i]=".$mimetype_list[$i]." mimefilename_list[$i]=".$mimefilename_list[$i]);
			}
		}

		// On defini mime_boundary
        $this->mime_boundary = md5(uniqid("dolibarr"));

		// On definit fin de ligne
		$this->eol="\n";
		if (eregi('^win',PHP_OS)) $this->eol="\r\n";
		if (eregi('^mac',PHP_OS)) $this->eol="\r";

		// On defini si message HTML
		if ($msgishtml == 2)
		{
			$this->msgishtml = 0;
			if (dol_textishtml($msg,1)) $this->msgishtml = 1;	
		}
		else
		{
			$this->msgishtml = $msgishtml;
		}

		$smtp_headers = "";
		$mime_headers = "";
		$text_body = "";
		$text_encoded = "";
		
        // En-tete dans $smtp_headers
        $this->subject = $subject;
        $this->addr_from = $from;
		$this->errors_to = $errors_to;
        $this->addr_to = $to;
        $this->addr_cc = $addr_cc;
        $this->addr_bcc = $addr_bcc;
        $this->deliveryreceipt = $deliveryreceipt;
        $smtp_headers = $this->write_smtpheaders();

		// En-tete suite dans $mime_headers
        if ($this->atleastonefile)
        {
            $mime_headers = $this->write_mimeheaders($filename_list, $mimefilename_list);
		}

		// Corps message dans $text_body
        $text_body = $this->write_body($msg, $filename_list);
        
        // Corps message suite (fichiers attach�s) dans $text_encoded
        if ($this->atleastonefile)
        {
            $text_encoded = $this->write_files($filename_list,$mimetype_list,$mimefilename_list);
        }

		// On defini $this->headers et $this->message
		//$this->headers = $smtp_headers . $mime_headers . $this->eol;
		//$this->message = $text_body . $text_encoded . $this->eol;
        $this->headers = $smtp_headers . $mime_headers;
        $this->message = $text_body . $text_encoded;
		// On nettoie le header pour qu'il ne se termine pas un retour chariot.
		// Ceci evite aussi les lignes vides en fin qui peuvent etre interpretees 
		// comme des injections mail par les serveurs de messagerie.
		$this->headers = eregi_replace("[\r\n]+$","",$this->headers);
    }


    /**
            \brief      Permet d'encoder un fichier
            \param      sourcefile
            \return     <0 si erreur, fichier encode si ok
    */
    function _encode_file($sourcefile)
    {
        if (is_readable($sourcefile))
        {
            $fd = fopen($sourcefile, "r");
            $contents = fread($fd, filesize($sourcefile));
            $encoded = chunk_split(base64_encode($contents), 68, $this->eol);
            //$encoded = _chunk_split(base64_encode($contents));
            fclose($fd);
            return $encoded;
        }
        else
        {
            $this->error="Error: Can't read file '$sourcefile'";
            dolibarr_syslog("CMailFile::encode_file: ".$this->error);
            return -1;
        }
    }

    /**
            \brief     Envoi le mail
            \return    boolean     true si mail envoye, false sinon
    */
	function sendfile()
	{
		global $conf;
		
		dolibarr_syslog("CMailFile::sendfile addr_to=".$this->addr_to.", subject=".$this->subject);
		dolibarr_syslog("CMailFile::sendfile header=\n".$this->headers);
		//dolibarr_syslog("CMailFile::sendfile message=\n".$message);
		//$this->send_to_file();

		$errorlevel=error_reporting();
		error_reporting($errorlevel ^ E_WARNING);   // Desactive warnings

		$res=false;
		
		if (! $conf->global->MAIN_DISABLE_ALL_MAILS)
		{
			// Si Windows, addr_from doit obligatoirement etre defini
			if (isset($_SERVER["WINDIR"]))
			{
				if (empty($this->addr_from)) $this->addr_from = 'robot@mydomain.com';
				@ini_set('sendmail_from',getValidAddress($this->addr_from,2));
			}

			// Forcage parametres
			if (! empty($conf->global->MAIN_MAIL_SMTP_SERVER)) ini_set('SMTP',$conf->global->MAIN_MAIL_SMTP_SERVER);
			if (! empty($conf->global->MAIN_MAIL_SMTP_PORT))   ini_set('smtp_port',$conf->global->MAIN_MAIL_SMTP_PORT);
			
			$dest=getValidAddress($this->addr_to,2);
			if (! $dest)
			{
				$this->error="Failed to send mail to SMTP=".ini_get('SMTP').", PORT=".ini_get('smtp_port')."<br>Recipient address '$dest' invalid";
				dolibarr_syslog("CMailFile::sendfile: mail end error=".$this->error);
			}
			else
			{
				dolibarr_syslog("CMailFile::sendfile: mail start SMTP=".ini_get('SMTP').", PORT=".ini_get('smtp_port'));
				//dolibarr_syslog("to=".getValidAddress($this->addr_to,2).", subject=".$this->subject.", message=".stripslashes($this->message).", header=".$this->headers);
				$res = mail($dest,$this->subject,stripslashes($this->message),$this->headers);
				if (! $res) 
				{
					$this->error="Failed to send mail to SMTP=".ini_get('SMTP').", PORT=".ini_get('smtp_port')."<br>Check your server logs and your firewalls setup";
					dolibarr_syslog("CMailFile::sendfile: mail end error=".$this->error);
				}
				else
				{
					dolibarr_syslog("CMailFile::sendfile: mail end success");
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
		else
		{
			$this->error='No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
			dolibarr_syslog("CMailFile::sendfile: ".$this->error);
		}

		error_reporting($errorlevel);              // Reactive niveau erreur origine

		return $res;
	}


    /**
     *    \brief  Ecrit le mail dans un fichier.
     *            Utilisation pour le debuggage
     */
    function send_to_file()
    {
    	if (@is_writeable("/tmp"))	// Avoid fatal error on fopen with open_basedir
    	{
        	$fp = fopen("/tmp/dolibarr_mail","w");
        	fputs($fp, $this->headers);
        	fputs($fp, $this->message);
        	fclose($fp);
    	}
    }

    /**
            \brief		Creation des headers smtp
    */
    function write_smtpheaders()
    {
		global $conf;
		$out = "";

		// Sender
        //$out .= "X-Sender: ".getValidAddress($this->addr_from,2).$this->eol;
		$out .= "From: ".getValidAddress($this->addr_from,0).$this->eol;
        $out .= "Return-Path: ".getValidAddress($this->addr_from,0).$this->eol;
        if (isset($this->reply_to)  && $this->reply_to)  $out .= "Reply-To: ".getValidAddress($this->reply_to,2).$this->eol;
        if (isset($this->errors_to) && $this->errors_to) $out .= "Errors-To: ".getValidAddress($this->errors_to,2).$this->eol;

		// Receiver
        if (isset($this->addr_cc)   && $this->addr_cc)   $out .= "Cc: ".getValidAddress($this->addr_cc,2).$this->eol;
        if (isset($this->addr_bcc)  && $this->addr_bcc)  $out .= "Bcc: ".getValidAddress($this->addr_bcc,2).$this->eol;

        // Accuse reception
        if (isset($this->deliveryreceipt) && $this->deliveryreceipt == 1) $out .= "Disposition-Notification-To: ".getValidAddress($this->addr_from,2).$this->eol;

        //$out .= "X-Priority: 3".$this->eol;
        $out .= "X-Mailer: Dolibarr version " . DOL_VERSION .$this->eol;
        $out .= "MIME-Version: 1.0".$this->eol;
       
        if ($this->msgishtml)
        {
        	$out.= "Content-Type: text/html; charset=".$conf->character_set_client.$this->eol;
        	$out.= "Content-Transfer-Encoding: 8bit".$this->eol;
        }
        else
        {
			$out.= "Content-Transfer-Encoding: 7bit".$this->eol;
		}

        dolibarr_syslog("CMailFile::write_smtpheaders smtp_header=\n".$out);
        return $out;
    }


    /**
            \brief 		Creation header MIME
            \param 		filename_list
            \param 		mimefilename_list
    */
    function write_mimeheaders($filename_list, $mimefilename_list)
    {
		$mimedone=0;
        $out = "";
        for ($i = 0; $i < count($filename_list); $i++)
        {
        	if ($filename_list[$i])
        	{
				if (! $mimedone)
				{
					$out.= "Content-Type: multipart/mixed; boundary=\"".$this->mime_boundary."\"".$this->eol;
					$mimedone=1;
				}
            	if ($mimefilename_list[$i]) $filename_list[$i] = $mimefilename_list[$i];
            	$out.= "X-attachments: $filename_list[$i]".$this->eol;
        	}
        }
      	//$out.= $this->eol;
        dolibarr_syslog("CMailFile::write_mimeheaders mime_header=\n".$out);
        return $out;
    }

    /**
            \brief 		Permet d'ecrire le corps du message
            \param 		msgtext
            \param 		filename_list
    */
    function write_body($msgtext, $filename_list)
    {
        $out='';
        
        if ($this->atleastonefile)
        {
            $out.= "--" . $this->mime_boundary . $this->eol;
	        if ($this->msgishtml)
	        {
	        	$out.= "Content-Type: text/html; charset=".$conf->charset_output.$this->eol;
	        }
	        else
	        {
	        	$out.= "Content-Type: text/plain; charset=".$conf->charset_output.$this->eol;	        	
	        }
            $out.= $this->eol;
        }
        if ($this->msgishtml)
        {
			// Check if html header already in message
			$htmlalreadyinmsg=0;
			if (eregi('^[ \t]*<html>',$msgtext)) $htmlalreadyinmsg=1;
			
			if (! $htmlalreadyinmsg) $out .= "<html><head><title></title></head><body>";
			$out.= $msgtext;
			if (! $htmlalreadyinmsg) $out .= "</body></html>";
        }
        else
        {
        	$out.= $msgtext;
        }
       	$out.= $this->eol;
        return $out;
    }

    /**
            \brief 		Permet d'attacher un fichier
            \param 		filename_list		Tableau
            \param 		mimetype_list		Tableau
            \param 		mimefilename_list	Tableau
            \return		out					Chaine fichiers encod�s
    */
    function write_files($filename_list,$mimetype_list,$mimefilename_list)
    {
        $out = '';
        
        for ($i = 0; $i < count($filename_list); $i++)
        {
            if ($filename_list[$i])
            {
	            dolibarr_syslog("CMailFile::write_files: i=$i");
	            $encoded = $this->_encode_file($filename_list[$i]);
	            if ($encoded >= 0)
	            {
	                if ($mimefilename_list[$i]) $filename_list[$i] = $mimefilename_list[$i];
	                if (! $mimetype_list[$i]) { $mimetype_list[$i] = "application/octet-stream"; }

	                $out = $out . "--" . $this->mime_boundary . $this->eol;
	                $out.= "Content-Type: " . $mimetype_list[$i] . "; name=\"".$filename_list[$i]."\"".$this->eol;
	                $out.= "Content-Transfer-Encoding: base64".$this->eol;
	                $out.= "Content-Disposition: attachment; filename=\"".$filename_list[$i]."\"".$this->eol;
	                $out.= $this->eol;
	                $out.= $encoded;
	                $out.= $this->eol;
//	                $out.= $this->eol;
	            }
	            else
	            {
	            	return $encoded;
	            }
			}
        }

		// Fin de tous les attachements
        $out = $out . "--" . $this->mime_boundary . "--" . $this->eol;
        return $out;
    }
    
}


/**
        \brief      Renvoie une adresse acceptee par le serveur SMTP
        \param      adresses		Exemple: 'John Doe <john@doe.com>' ou 'john@doe.com'
        \param		format			0=Auto, 1=emails avec <>, 2=emails sans <>
        \return	    string			Renvoi: Si format 1: '<john@doe.com>' ou 'John Doe <john@doe.com>'
											Si format 2: 'john@doe.com'
*/
function getValidAddress($adresses,$format)
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
				else $newemail=$name.' <'.$email.'>';
			}

			$ret=($ret ? $ret.',' : '').$newemail;
		}
	}
	
	return $ret;
}


/**
        \brief      Permet de diviser une chaine (RFC2045)
        \param      str
        \remarks    function chunk_split qui remplace celle de php si necessaire
        \remarks    76 caracteres par ligne, termine par "\n"
*/
function _chunk_split($str)
{
    $stmp = $str;
    $len = strlen($stmp);
    $out = "";
    while ($len > 0) {
        if ($len >= 76) {
            $out = $out . substr($stmp, 0, 76) . "\n";
            $stmp = substr($stmp, 76);
            $len = $len - 76;
        }
        else {
            $out = $out . $stmp . "\n";
            $stmp = ""; $len = 0;
        }
    }
    return $out;
}


?>
