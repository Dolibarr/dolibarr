#!/usr/bin/php
<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 *
 *
 * !!! Envoi mailing !!!
 *
 * L'utilisation d'adresses de courriers électroniques dans les opérations
 * de prospection commerciale est subordonnée au recueil du consentement 
 * préalable des personnes concernées.
 *
 * Le dispositif juridique applicable a été introduit par l'article 22 de 
 * la loi du 21 juin 2004  pour la confiance dans l'économie numérique.
 *
 * Les dispositions applicables sont définies par les articles L. 34-5 du 
 * code des postes et des télécommunications et L. 121-20-5 du code de la 
 * consommation. L'application du principe du consentement préalable en 
 * droit français résulte de la transposition de l'article 13 de la Directive 
 * européenne du 12 juillet 2002 « Vie privée et communications électroniques ». 

 */

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer mailing-send.php en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
    exit;
}

if (! $argv[1]) {
    print "Syntax:  mailing-send.php ID_MAILING\n";   
    exit;
}
$id=$argv[1];

require ("../htdocs/master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/lib/dolibarrmail.class.php");

$error = 0;

$sql = "SELECT m.rowid, m.titre, m.sujet, m.body";
$sql .= " , m.email_from, m.email_replyto, m.email_errorsto";
$sql .= " FROM ".MAIN_DB_PREFIX."mailing as m";
$sql .= " WHERE m.statut >= 1";
$sql .= " AND m.rowid= ".$id;
$sql .= " LIMIT 1";

if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  $i = 0;
  
  if ($num == 1)
    {
      $obj = $db->fetch_object();

      dolibarr_syslog("mailing-send: mailing ".$id);

      $id       = $obj->rowid;
      $subject  = $obj->sujet;
      $message  = $obj->body;
      $from     = $obj->email_from;
      $errorsto = $obj->email_errorsto;

      $i++;
      
    }
}

$sql = "SELECT mc.nom, mc.prenom, mc.email";
$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
$sql .= " WHERE mc.fk_mailing = ".$id;

if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  $i = 0;
  
  dolibarr_syslog("mailing-send: mailing $num cibles");

  while ($i < $num )
    {
      $obj = $db->fetch_object();

      $sendto = stripslashes($obj->prenom). " ".stripslashes($obj->nom) ."<".$obj->email.">";

      $mail = new DolibarrMail($subject,
			       $sendto,
			       $from,
			       $message);
      
      $mail->errors_to = $errorsto;                 
      
      if ( $mail->sendfile() )
	{

	}

      $i++;
      
    }
}
else
{
  dolibarr_syslog($db->error());
}

?>
