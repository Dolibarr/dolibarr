<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/notify.class.php
        \brief      Fichier de la classe de gestion des notifications
        \version    $Revision$
*/


/**
        \class      Notify
        \brief      Classe de gestion des notifications
*/

class Notify
{
    var $id;
    var $db;
    var $error;

    var $socidp;
    var $author;
    var $ref;
    var $date;
    var $duree;
    var $note;
    var $projet_id;



    /**
     *    \brief      Constructeur
     *    \param      DB      Handler accès base
     */
    function Notify($DB)
    {
        $this->db = $DB ;
        include_once(DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");
    }


    /**
     *    \brief      Envoi mail et sauve trace
     *
     */
    function send($action, $socid, $texte, $objet_type, $objet_id, $file="")
    {
        global $conf,$langs;

        $sql = "SELECT s.nom, c.email, c.idp, c.name, c.firstname, a.titre,n.rowid";
        $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as c, ".MAIN_DB_PREFIX."action_def as a, ".MAIN_DB_PREFIX."notify_def as n, ".MAIN_DB_PREFIX."societe as s";
        $sql .= " WHERE n.fk_contact = c.idp AND a.rowid = n.fk_action";
        $sql .= " AND n.fk_soc = s.idp AND n.fk_action = ".$action;
        $sql .= " AND s.idp = ".$socid;

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
                    $subject = $langs->trans("DolibarrNotififcation");
                    $message = $texte;
                    $filename = split("/",$file);
                    $replyto = $conf->email_from;

                    $mailfile = new CMailFile($subject,
                    $sendto,
                    $replyto,
                    $message,
                    array($file),
                    array("application/pdf"),
                    array($filename[sizeof($filename)-1])
                    );

                    if ( $mailfile->sendfile() )
                    {
                        $sendto = htmlentities($sendto);

                        $sql = "INSERT INTO ".MAIN_DB_PREFIX."notify (daten, fk_action, fk_contact, objet_type, objet_id)";
                        $sql .= " VALUES (now(), $action ,$obj->idp , '$objet_type', $objet_id);";
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
        }
        else
        {
            $this->error=$this->db->error();
        }

    }

}

?>
