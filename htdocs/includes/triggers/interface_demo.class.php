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
        \brief      Fichier des actions de demo de workflow
        \remarks    Son propre fichier d'actions peut etre créés par recopie de celui-ci:
                    - Le nom du fichier doit etre interface_xxx.class.php
                    - Le fichier doit rester stocké dans includes/modules/triggers
                    - Le nom de la classe doit etre InterfaceXxx
*/


/**
        \class      interface_demo
        \brief      Classe de la fonction trigger des actions de workflow
*/

class InterfaceDemo
{

   /**
    *   \brief      Constructeur.
    *   \param      DB      handler d'accès base
    */
  function InterfaceDemo($DB)
  {
    $this->db = $DB ;
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
        if ($action == 'COMPANY_CREATE')
        {
            dolibarr_syslog("Trigger for action '$action' launched. id=".$object->id);
        }
        elseif ($action == 'COMPANY_MODIFY')
        {        
            dolibarr_syslog("Trigger for action '$action' launched. id=".$object->id);
        }
        elseif ($action == 'COMPANY_DELETE')
        {        
            dolibarr_syslog("Trigger for action '$action' launched. id=".$object->id);
        }
        elseif ($action == 'BILL_CREATE')
        {        
            dolibarr_syslog("Trigger for action '$action' launched");
        }
        elseif ($action == 'BILL_MODIFY')
        {        
            dolibarr_syslog("Trigger for action '$action' launched");
        }
        elseif ($action == 'BILL_DELETE')
        {        
            dolibarr_syslog("Trigger for action '$action' launched");
        }
        elseif ($action == 'PRODUCT_CREATE')
        {        
            dolibarr_syslog("Trigger for action '$action' launched");
        }
        elseif ($action == 'PRODUCT_MODIFY')
        {        
            dolibarr_syslog("Trigger for action '$action' launched");
        }
        elseif ($action == 'PRODUCT_DELETE')
        {        
            dolibarr_syslog("Trigger for action '$action' launched");
        }
        elseif ($action == 'ORDER_CREATE')
        {        
            dolibarr_syslog("Trigger for action '$action' launched");
        }
        elseif ($action == 'ORDER_MODIFY')
        {        
            dolibarr_syslog("Trigger for action '$action' launched");
        }
        elseif ($action == 'ORDER_DELETE')
        {        
            dolibarr_syslog("Trigger for action '$action' launched");
        }
        else
        {
            dolibarr_syslog("A trigger for action '$action' was ran but no handler found.");
        }
  }

}
?>
