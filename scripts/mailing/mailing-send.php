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
 * L'utilisation d'adresses de courriers �lectroniques dans les op�rations
 * de prospection commerciale est subordonn�e au recueil du consentement 
 * pr�alable des personnes concern�es.
 *
 * Le dispositif juridique applicable a �t� introduit par l'article 22 de 
 * la loi du 21 juin 2004  pour la confiance dans l'�conomie num�rique.
 *
 * Les dispositions applicables sont d�finies par les articles L. 34-5 du 
 * code des postes et des t�l�communications et L. 121-20-5 du code de la 
 * consommation. L'application du principe du consentement pr�alable en 
 * droit fran�ais r�sulte de la transposition de l'article 13 de la Directive 
 * europ�enne du 12 juillet 2002 � Vie priv�e et communications �lectroniques �. 
 */


/**
        \file       scripts/mailing/mailing-send.php
        \ingroup    mailing
        \brief      Script d'envoi d'un mailing pr�par� et valid�
*/


// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer mailing-send.php en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
    exit;
}

if (! isset($argv[1]) || ! $argv[1]) {
    print "Usage:  mailing-send.php ID_MAILING\n";   
    exit;
}
$id=$argv[1];

// Recupere root dolibarr
$path=eregi_replace('mailing-send.php','',$_SERVER["PHP_SELF"]);


require_once ($path."../../htdocs/master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");


$error = 0;


// On r�cup�re donn�es du mail
$sql = "SELECT m.rowid, m.titre, m.sujet, m.body";
$sql .= " , m.email_from, m.email_replyto, m.email_errorsto";
$sql .= " FROM ".MAIN_DB_PREFIX."mailing as m";
$sql .= " WHERE m.statut >= 1";
$sql .= " AND m.rowid= ".$id;
$sql .= " LIMIT 1";

$resql=$db->query($sql);
if ($resql) 
{
	$num = $db->num_rows($resql);
	$i = 0;

	if ($num == 1)
	{
		$obj = $db->fetch_object($resql);

		dolibarr_syslog("mailing-send: mailing ".$id);

		$id       = $obj->rowid;
		$subject  = $obj->sujet;
		$message  = $obj->body;
		$from     = $obj->email_from;
		$errorsto = $obj->email_errorsto;

		// Le message est-il en html
		$msgishtml=-1;	// Inconnu par defaut
		if (eregi('[ \t]*<html>',$message)) $msgishtml=1;						
		
		$i++;
	}
}


$nbok=0; $nbko=0;

// On choisit les mails non d�j� envoy�s pour ce mailing (statut=0)
// ou envoy�s en erreur (statut=-1)
$sql = "SELECT mc.rowid, mc.nom, mc.prenom, mc.email";
$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
$sql .= " WHERE mc.statut < 1 AND mc.fk_mailing = ".$id;

$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);

    if ($num) 
    {
        dolibarr_syslog("mailing-send: target number = $num");

        // Positionne date debut envoi
        $sql="UPDATE ".MAIN_DB_PREFIX."mailing SET date_envoi=SYSDATE() WHERE rowid=".$id;
        $resql2=$db->query($sql);
        if (! $resql2)
        {
            dolibarr_print_error($db);
        }
    
        // Boucle sur chaque adresse et envoie le mail
        $i = 0;
        while ($i < $num )
        {
            $res=1;
            
            $obj = $db->fetch_object($resql);

            // sendto en RFC2822
            $sendto = stripslashes($obj->prenom). " ".stripslashes($obj->nom) ." <".$obj->email.">";

			// Pratique les substitutions sur le sujet et message
			$substitutionarray=array(	
				'__ID__' => $obj->rowid,
				'__EMAIL__' => $obj->email,
				'__LASTNAME__' => $obj->nom,
				'__FIRSTNAME__' => $obj->prenom
			);

			$substitutionisok=true;
			$subject=make_substitutions($subject,$substitutionarray);
			$message=make_substitutions($message,$substitutionarray);
			
            // Fabrication du mail
            $mail = new CMailFile($subject, $sendto, $from, $message, 
            						array(), array(), array(),
            						'', '', 0, $msgishtml);
            $mail->errors_to = $errorsto;
			
            						
			if ($mail->error)
			{
				$res=0;
			}
			if (! $substitutionisok)
			{
				$mail->error='Some substitution failed';
				$res=0;
			}

            // Envoi du mail
			if ($res)
			{
    			$res=$mail->sendfile();
			}
			
            if ($res)
            {
                // Mail envoye avec succes
                $nbok++;
    
		        dolibarr_syslog("mailing-send: ok for #".$i.' - '.$mail->error);

                $sql="UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
				$sql.=" SET statut=1, date_envoi=SYSDATE() WHERE rowid=".$obj->rowid;
                $resql2=$db->query($sql);
                if (! $resql2)
                {
                    dolibarr_print_error($db);   
                }
            }
            else
            {
                // Mail en echec
                $nbko++;
    
		        dolibarr_syslog("mailing-send: error for #".$i.' - '.$mail->error);

                $sql="UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
				$sql.=" SET statut=-1, date_envoi=SYSDATE() WHERE rowid=".$obj->rowid;
                $resql2=$db->query($sql);
                if (! $resql2)
                {
                    dolibarr_print_error($db);   
                }
            }
    
            $i++;
        }
    }

    // Loop finished, set global statut of mail
    $statut=2;
    if (! $nbko) $statut=3;

    $sql="UPDATE ".MAIN_DB_PREFIX."mailing SET statut=".$statut." WHERE rowid=".$id;
    dolibarr_syslog("mailing-send: update global status sql=".$sql);
    $resql2=$db->query($sql);
    if (! $resql2)
    {
        dolibarr_print_error($db);
    }
}
else
{
    dolibarr_syslog($db->error());
    dolibarr_print_error($db);
}

?>
