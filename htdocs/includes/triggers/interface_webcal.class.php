<?php
/* Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/includes/triggers/interface_webcal.class.php
        \ingroup    webcalendar
        \brief      Fichier de demo de personalisation des actions du workflow
        \remarks    Son propre fichier d'actions peut etre créé par recopie de celui-ci:
                    - Le nom du fichier doit etre interface_xxx.class.php
                    - Le fichier doit rester stocké dans includes/triggers
                    - Le nom de la classe doit etre InterfaceXxx
*/

include_once(DOL_DOCUMENT_ROOT.'/lib/webcal.class.php');


/**
        \class      InterfaceWebCal
        \brief      Classe des fonctions triggers des actions personalisées du workflow
*/

class InterfaceWebCal
{
    var $db;
    var $error;
    
    
    /**
     *   \brief      Constructeur.
     *   \param      DB      handler d'accès base
     */
    function InterfaceWebCal($DB)
    {
        $this->db = $DB ;
    
        $this->name = "WebCal";
        $this->family = "webcal";
        $this->description = "Les triggers de ce composant permettent d'insérer un évênement dans le calendrier webcalendar pour chaque grand évênement Dolibarr.";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
    }
    
    /**
     *   \brief      Renvoi nom du lot de triggers
     *   \return     string      Nom du lot de triggers
     */
    function getName()
    {
        return $this->name;
    }
    
    /**
     *   \brief      Renvoi descriptif du lot de triggers
     *   \return     string      Descriptif du lot de triggers
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   \brief      Renvoi version du lot de triggers
     *   \return     string      Version du lot de triggers
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

    /**
     *      \brief      Fonction appelée lors du déclenchement d'un évènement Dolibarr.
     *                  D'autres fonctions run_trigger peuvent etre présentes dans includes/triggers
     *      \param      action      Code de l'evenement
     *      \param      object      Objet concerné
     *      \param      user        Objet user
     *      \param      lang        Objet lang
     *      \param      conf        Objet conf
     *      \return     int         <0 si ko, >0 si ok
     */
    function run_trigger($action,$object,$user,$langs,$conf)
    {
        // Mettre ici le code à exécuter en réaction de l'action
        // Les données de l'action sont stockées dans $object

        if (! $conf->webcal->enabled) return 0;     // Module non actif
        if (! $object->use_webcal) return 0;        // Option syncro webcal non active

        // Actions
        if ($action == 'ACTION_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched. id=".$object->id);

            // Crée objet webcal et connexion avec params $conf->webcal->db->xxx
            $webcal = new Webcal();

            if (! $webcal->localdb->ok)
            {
                // Si la creation de l'objet n'as pu se connecter
                $error ="Dolibarr n'a pu se connecter à la base Webcalendar avec les identifiants définis (host=".$conf->webcal->db->host." dbname=".$conf->webcal->db->name." user=".$conf->webcal->db->user.").";
                $error.=" L'option de mise a jour Webcalendar a été ignorée.";
                $this->error=$error;
                return -1;
            }

            // Initialisation donnees webcal
            if ($object->type_id == 5 && $object->contact->fullname)
            {
                $libellecal =$langs->trans("TaskRDVWith",$object->contact->fullname)."\n";
                $libellecal.=$object->note;
            }
            else
            {
                $libellecal="";
                if ($langs->trans("Action".$object->type_code) != "Action".$object->type_code)
                {
                    $libellecal.=$langs->trans("Action".$object->type_code)."\n";
                }
                $libellecal.=($object->label!=$libellecal?$object->label."\n":"");
                $libellecal.=($object->note?$object->note:"");
            }
            $webcal->date=$object->date;
            $webcal->duree=$object->duree;
            $webcal->texte=$object->societe->nom;
            $webcal->desc=$libellecal;

            // Ajoute entrée dans webcal
            $result=$webcal->add($user);
            if ($result)
            {
                return 1;
            }
            else
            {
                $this->error="Echec insertion dans webcal: ".$webcal->error;
                return -1;
            }
        }

    }

}
?>
