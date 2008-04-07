<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/notify.class.php
        \brief      Fichier de la classe de gestion des notifications
        \version    $Id$
*/
require_once(DOL_DOCUMENT_ROOT ."/lib/CMailFile.class.php");


/**
        \class      Notify
        \brief      Classe de gestion des notifications
*/

class Notify
{
    var $id;
    var $db;
    var $error;

    var $author;
    var $ref;
    var $date;
    var $duree;
    var $note;
    var $projet_id;

	// Les codes actions sont définis dans la table llx_notify_def
	// \todo utiliser des codes texte plutot que numérique
	// 1 = Validation fiche inter
	// 2 = Validation facture

    /**
     *    \brief      Constructeur
     *    \param      DB      Handler accès base
     */
    function Notify($DB)
    {
        $this->db = $DB ;
    }


    /**
     *    	\brief      Renvoie le message signalant les notifications qui auront lieu sur
     *					un evenement pour affichage dans texte de confirmation evenement.
     *		\return		string		Message
     */
	function confirmMessage($action,$socid)
	{
		global $langs;
		$langs->load("mails");
		
		$nb=$this->countDefinedNotifications($action,$socid);
		if ($nb <= 0) $texte=$langs->trans("NoNotificationsWillBeSent");
		if ($nb == 1) $texte=img_object($langs->trans("Notifications"),'email').' '.$langs->trans("ANotificationsWillBeSent");
		if ($nb >= 2) $texte=img_object($langs->trans("Notifications"),'email').' '.$langs->trans("SomeNotificationsWillBeSent",$nb);
		return $texte;
	}
	
    /**
     *    	\brief      Renvoie le nombre de notifications configurés pour l'action et la société donnée
     *		\return		int		<0 si ko, sinon nombre de notifications définies
     */
	function countDefinedNotifications($action,$socid)
	{
        $num=-1;
        
        $sql = "SELECT n.rowid, c.email, c.rowid, c.name, c.firstname, a.titre, s.nom";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c, ".MAIN_DB_PREFIX."action_def as a, ".MAIN_DB_PREFIX."notify_def as n, ".MAIN_DB_PREFIX."societe as s";
        $sql.= " WHERE n.fk_contact = c.rowid AND a.rowid = n.fk_action";
        $sql.= " AND n.fk_soc = s.rowid";
        $sql.= " AND n.fk_action = ".$action;
        $sql.= " AND s.rowid = ".$socid;

		dolibarr_syslog("Notify.class::countDefinedNotifications $action, $socid");

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
		}
		else
		{
			$this->error=$this->db->error.' sql='.$sql;
			return -1;
		}
		
		return $num;
	}

    /**
     *    	\brief      Vérifie si notification actice. Si oui, envoi mail et sauve trace
     *		\return		int		<0 si ko, sinon nombre de notifications faites
     */
    function send($action, $socid, $texte, $objet_type, $objet_id, $file="")
    {
        global $conf,$langs;

        $sql = "SELECT s.nom, c.email, c.rowid, c.name, c.firstname, a.titre,n.rowid";
        $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c, ".MAIN_DB_PREFIX."action_def as a, ".MAIN_DB_PREFIX."notify_def as n, ".MAIN_DB_PREFIX."societe as s";
        $sql .= " WHERE n.fk_contact = c.rowid AND a.rowid = n.fk_action";
        $sql .= " AND n.fk_soc = s.rowid AND n.fk_action = ".$action;
        $sql .= " AND s.rowid = ".$socid;

		dolibarr_syslog("Notify.class::send $action, $socid, $texte, $objet_type, $objet_id, $file");

        $result = $this->db->query($sql);
        if ($result)
        {
            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);

                $sendto = $obj->firstname . " " . $obj->name . " <".$obj->email.">";

                if (strlen($sendto))
                {
                    $subject = $langs->trans("DolibarrNotification");
                    $message = $texte;
                    $filename = split("/",$file);
					$msgishtml=0;
					
                    $replyto = $conf->notification->email_from;

                    $mailfile = new CMailFile($subject,
	                    $sendto,
	                    $replyto,
	                    $message,
	                    array($file),
	                    array("application/pdf"),
	                    array($filename[sizeof($filename)-1]),
	                    '', '', 0, $msgishtml
	                    );

                    if ( $mailfile->sendfile() )
                    {
                        $sendto = htmlentities($sendto);

                        $sql = "INSERT INTO ".MAIN_DB_PREFIX."notify (daten, fk_action, fk_contact, objet_type, objet_id)";
                        $sql .= " VALUES (now(), ".$action." ,".$obj->rowid." , '".$objet_type."', ".$objet_id.");";
                        if (! $this->db->query($sql) )
                        {
                            dolibarr_print_error($db);
                        }
                    }
                    else
                    {
                        $this->error=$mailfile->error;
                    }
                }
                $i++;
            }
            return $i;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }

    }

}

?>
