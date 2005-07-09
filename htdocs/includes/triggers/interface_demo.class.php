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
        \file       htdocs/includes/triggers/interface_demo.class.php
        \ingroup    core
        \brief      Fichier de demo de personalisation des actions du workflow
        \remarks    Son propre fichier d'actions peut etre créé par recopie de celui-ci:
                    - Le nom du fichier doit etre interface_xxx.class.php
                    - Le fichier doit rester stocké dans includes/triggers
                    - Le nom de la classe doit etre InterfaceXxx
*/


/**
        \class      interface_demo
        \brief      Classe des fonctions triggers des actions personalisées du workflow
*/

class InterfaceDemo
{
    var $db;
    
    /**
     *   \brief      Constructeur.
     *   \param      DB      handler d'accès base
     */
    function InterfaceDemo($DB)
    {
        $this->db = $DB ;
    
        $this->name = "Demo";
        $this->family = "demo";
        $this->description = "Les triggers de ce composant sont des fonctions vierges. Elles n'ont aucun effet. Ce composant est fourni à des fins de tutorial.";
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
     *   \brief      Fonction appelée lors du déclenchement d'un évènement Dolibarr.
     *               D'autres fonctions run_trigger peuvent etre présentes dans includes/triggers
     *   \param      action      Code de l'evenement
     *   \param      object      Objet concerné
     *   \param      user        Objet user
     *   \param      lang        Objet lang
     *   \param      conf        Objet conf
     */
    function run_trigger($action,$object,$user,$lang,$conf)
    {
        // Mettre ici le code à exécuter en réaction de l'action
        // Les données de l'action sont stockées dans $object
    
        // Companies
        if     ($action == 'COMPANY_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched. id=".$object->id);
        }
        elseif ($action == 'COMPANY_MODIFY')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched. id=".$object->id);
        }
        elseif ($action == 'COMPANY_DELETE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched. id=".$object->id);
        }
        // Contracts
        elseif ($action == 'CONTRACT_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched");
        }
        elseif ($action == 'CONTRACT_MODIFY')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched");
        }
        elseif ($action == 'CONTRACT_ACTIVATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched");
        }
        elseif ($action == 'CONTRACT_CANCEL')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched");
        }
        elseif ($action == 'CONTRACT_CLOSE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched");
        }
        elseif ($action == 'CONTRACT_DELETE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched");
        }
        // Bills
        elseif ($action == 'BILL_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched");
        }
        elseif ($action == 'BILL_MODIFY')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched");
        }
        elseif ($action == 'BILL_VALIDATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched");
        }
        elseif ($action == 'BILL_CANCEL')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched");
        }
        elseif ($action == 'BILL_DELETE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched");
        }
        // Products
        elseif ($action == 'PRODUCT_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched");
        }
        elseif ($action == 'PRODUCT_MODIFY')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched");
        }
        elseif ($action == 'PRODUCT_DELETE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched");
        }
        elseif ($action == 'ORDER_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched");
        }
        elseif ($action == 'ORDER_MODIFY')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched");
        }
        elseif ($action == 'ORDER_DELETE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched");
        }
        else
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' was ran but no handler found for this action.");
        }
    }

}
?>
