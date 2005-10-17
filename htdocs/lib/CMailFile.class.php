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
 *
 * If chunk_split does not works on your system, change the call to chunk_split
 * to my_chunk_split 
 */

/**
        \file       htdocs/lib/CMailFile.class.php
        \brief      Classe permettant d'envoyer des mail avec attachements
        \author     Dan Potter.
        \author	    Eric Seigne
        \author	    Laurent Destailleur.
        \version    $Revision$
*/

/**
        \class      CMailFile
        \brief      Classe d'envoi de mails et pièces jointes. Encapsule mail() avec d'éventuel attachements.
        \remarks    Usage:
        \remarks    $mailfile = new CMailFile($subject,$sendto,$replyto,$message,$filepath,$mimetype,$filename,$cc,$ccc);
        \remarks    $mailfile->sendfile();
*/

class CMailFile
{
    var $subject;
    var $addr_from_email;
    var $addr_from_name;
    var $addr_to;
    var $addr_cc;
    var $addr_bcc;
    var $text_body;
    var $text_encoded;
    var $mime_headers;
    var $mime_boundary;
    var $smtp_headers;

    /**
            \brief CMailFile
            \param subject              sujet
            \param to                   email destinataire ("Nom <email>" ou "email" ou "<email>")
            \param from                 email emetteur ("Nom <email>" ou "email" ou "<email>")
            \param msg                  message
            \param filename_list        tableau de fichiers attachés
            \param mimetype_list        tableau des types des fichiers attachés
            \param mimefilename_list    tableau des noms des fichiers attachés
            \param addr_cc              email cc
            \param addr_bcc             email bcc
    */
    function CMailFile($subject,$to,$from,$msg,
                       $filename_list=array(),$mimetype_list=array(),$mimefilename_list=array(),
                       $addr_cc="",$addr_bcc="")
    {
        dolibarr_syslog("CMailFile::CMailfile: from=$from, filename_list[0]=$filename_list[0], mimetype_list[0]=$mimetype_list[0] mimefilename_list[0]=$mimefilename_list[0]");

        $this->mime_boundary = md5( uniqid("dolibarr") );

        $this->subject = $subject;
        if (eregi('(.*)<(.+)>',$from,$regs))
        {
            $this->addr_from_name  = trim($regs[1]);
            $this->addr_from_email = trim($regs[2]);
        }
        else
        {
            $this->addr_from_name  = $from;
            $this->addr_from_email = $from;
        }
        $this->addr_to = $to;
        $this->addr_cc = $addr_cc;
        $this->addr_bcc = $addr_bcc;
        $this->smtp_headers = $this->write_smtpheaders();
        $this->text_body = $this->write_body($msg, $filename_list);
        if (count($filename_list))
        {
            $this->mime_headers = $this->write_mimeheaders($filename_list, $mimefilename_list);
            $this->text_encoded = $this->attach_file($filename_list,$mimetype_list,$mimefilename_list);
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
            dolibarr_syslog("CMailFile::attach_file: i=$i");
            $encoded = $this->encode_file($filename_list[$i]);
            if ($encoded != -1)
            {
                if ($mimefilename_list[$i]) $filename_list[$i] = $mimefilename_list[$i];
                $out = $out . "--" . $this->mime_boundary . "\n";
                if (! $mimetype_list[$i]) { $mimetype_list[$i] = "application/octet-stream"; }
                $out .= "Content-type: " . $mimetype_list[$i] . "; name=\"$filename_list[$i]\";\n";
                $out .= "Content-Transfer-Encoding: base64\n";
                $out .= "Content-disposition: attachment; filename=\"$filename_list[$i]\"\n\n";
                $out .= $encoded . "\n";
            }
        }
        $out = $out . "--" . $this->mime_boundary . "--" . "\n";
        return $out;
    }


    /**
            \brief      Permet d'encoder un fichier
            \param      sourcefile
            \return     <0 si erreur, fichier encodé si ok
    */
    function encode_file($sourcefile)
    {
        if (is_readable($sourcefile))
        {
            $fd = fopen($sourcefile, "r");
            $contents = fread($fd, filesize($sourcefile));
            $encoded = chunk_split(base64_encode($contents));
            //$encoded = my_chunk_split(base64_encode($contents));
            fclose($fd);
            return $encoded;
        }
        else
        {
            dolibarr_syslog("CMailFile::encode_file: Can't read file '$sourcefile'");
            return -1;
        }
    }

    /**
            \brief     Envoi le mail
            \return    boolean     vrai si mail envoyé, faux sinon
    */
    function sendfile()
    {
        $headers = $this->smtp_headers . $this->mime_headers;
        $message=$this->text_body . $this->text_encoded;

        // Fix si windows, la fonction mail ne traduit pas les \n, il faut donc y mettre
        // des champs \r\n (imposés par SMTP).
        if (eregi('^win',PHP_OS))
        {
            $message = eregi_replace("\r","", $this->text_body . $this->text_encoded);
            $message = eregi_replace("\n","\r\n", $message);
        }
        
        dolibarr_syslog("CMailFile::sendfile addr_to=".$this->addr_to.", subject=".$this->subject);
        //dolibarr_syslog("CMailFile::sendfile message=\n".$message);
        //dolibarr_syslog("CMailFile::sendfile header=\n".$headers);

        $errorlevel=error_reporting();
        //error_reporting($errorlevel ^ E_WARNING);   // Désactive warnings
        if ($this->errors_to)
        {
            dolibarr_syslog("CMailFile::sendfile with errorsto : ".$this->errors_to);
            $res = mail($this->addr_to,$this->subject,stripslashes($message),$headers,"-f".$this->errors_to);
        }
        else
        {
            dolibarr_syslog("CMailFile::sendfile");
            $res = mail($this->addr_to,$this->subject,stripslashes($message),$headers);
        }
        //if (! $res) $this->error= 
        error_reporting($errorlevel);              // Réactive niveau erreur origine

        //$this->write_to_file();

        return $res;
    }

    /**
     *    \brief  Ecrit le mail dans un fichier.
     *            Utilisation pour le debuggage
     */
    function write_to_file()
    {
        $headers = $this->smtp_headers . $this->mime_headers;
        $message = $this->text_body . $this->text_encoded;

        $fp = fopen("/tmp/mail","w");
        fputs($fp, $headers);
        fputs($fp, $message);
        fclose($fp);
    }

    /**
            \brief 	Permet d'ecrire le corps du message
            \param 	msgtext
            \param 	filename_list
    */
    function write_body($msgtext, $filename_list)
    {
        $out='';
        if (count($filename_list))
        {
            $out = "--" . $this->mime_boundary . "\n";
            $out = $out . "Content-Type: text/plain; charset=\"iso8859-15\"\n\n";
            //	  $out = $out . "Content-Type: text/plain; charset=\"us-ascii\"\n\n";
        }
        $out = $out . $msgtext . "\n";
        return $out;
    }

    /**
            \brief création des headers mime
            \param filename_list
            \param mimefilename_list
    */
    function write_mimeheaders($filename_list, $mimefilename_list) {
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

    /**
            \brief création des headers smtp
    */
    function write_smtpheaders()
    {
        $out = "";

        $out .= "X-Mailer: Dolibarr version " . DOL_VERSION ."\n";
        $out .= "X-Sender: <$this->addr_from_email>\n";
        //$out .= "X-Priority: 3\n";

        $out .= "Return-path: <$this->addr_from_email>\n";
        $out .= "From: $this->addr_from_name <".$this->addr_from_email.">\n";

        if (isset($this->addr_cc)  && $this->addr_cc)  $out .= "Cc: ".$this->addr_cc."\n";
        if (isset($this->addr_bcc) && $this->addr_bcc) $out .= "Bcc: ".$this->addr_bcc."\n";
        if (isset($this->reply_to) && $this->reply_to) $out .= "Reply-To: ".$this->reply_to."\n";
        //    if($this->errors_to != "")
        //$out = $out . "Errors-to: ".$this->errors_to."\n";

        dolibarr_syslog("CMailFile::write_smtpheaders $out");
        return $out;
    }

}


/**
        \brief      Permet de diviser une chaine (RFC2045)
        \param      str
        \remarks    function chunk_split qui remplace celle de php si nécéssaire
        \remarks    76 caractères par ligne, terminé par "\r\n"
*/
function my_chunk_split($str)
{
    $stmp = $str;
    $len = strlen($stmp);
    $out = "";
    while ($len > 0) {
        if ($len >= 76) {
            $out = $out . substr($stmp, 0, 76) . "\r\n";
            $stmp = substr($stmp, 76);
            $len = $len - 76;
        }
        else {
            $out = $out . $stmp . "\r\n";
            $stmp = ""; $len = 0;
        }
    }
    return $out;
}


?>
