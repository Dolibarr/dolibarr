<?php
/* Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/includes/triggers/interface_all_Demo.class.php
        \ingroup    core
        \brief      Fichier de demo de personalisation des actions du workflow
        \remarks    Son propre fichier d'actions peut etre créé par recopie de celui-ci:
                    - Le nom du fichier doit etre: interface_modMymodule_Mytrigger.class.php
					                           ou: interface_all_Mytrigger.class.php
                    - Le fichier doit rester stocké dans includes/triggers
                    - Le nom de la classe doit etre InterfaceMytrigger
                    - Le nom de la methode constructeur doit etre InterfaceMytrigger
                    - Le nom de la propriete name doit etre Mytrigger
*/


/**
        \class      InterfaceDemo
        \brief      Classe des fonctions triggers des actions personalisées du workflow
*/

class InterfaceDemo
{
    var $db;
    
    /**
     *   \brief      Constructeur.
     *   \param      DB      Handler d'accès base
     */
    function InterfaceDemo($DB)
    {
        $this->db = $DB ;
    
        $this->name = "Demo";					// Put here same value than in file and class name
        $this->family = "demo";
        $this->description = "Les triggers de ce composant sont des fonctions vierges. Elles n'ont aucun effet. Ce composant est fourni à des fins de tutorial.";
        $this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
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
     *      \return     int         <0 si ko, 0 si aucune action faite, >0 si ok
     */
	function run_trigger($action,$object,$user,$langs,$conf)
    {
        // Mettre ici le code à exécuter en réaction de l'action
        // Les données de l'action sont stockées dans $object
    
        // Users
        if     ($action == 'USER_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'USER_MODIFY')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'USER_NEW_PASSWORD')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'USER_DISABLE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'USER_DELETE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }

        // Companies
        elseif     ($action == 'COMPANY_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'COMPANY_MODIFY')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'COMPANY_DELETE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }

        // Contacts
        elseif ($action == 'CONTACT_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'CONTACT_MODIFY')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'CONTACT_DELETE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }

        // Products
        elseif ($action == 'PRODUCT_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'PRODUCT_MODIFY')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'PRODUCT_DELETE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }

		// Customer orders
        elseif ($action == 'ORDER_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'ORDER_VALIDATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'ORDER_DELETE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }

		// Supplier orders
        elseif ($action == 'ORDER_SUPPLIER_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'ORDER_SUPPLIER_VALIDATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }

        // Proposals
        elseif ($action == 'PROPAL_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'PROPAL_MODIFY')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'PROPAL_VALIDATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'PROPAL_CLOSE_SIGNED')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'PROPAL_CLOSE_REFUSED')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }

        // Contracts
        elseif ($action == 'CONTRACT_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'CONTRACT_MODIFY')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'CONTRACT_ACTIVATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'CONTRACT_CANCEL')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'CONTRACT_CLOSE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'CONTRACT_DELETE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }

        // Bills
        elseif ($action == 'BILL_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'BILL_MODIFY')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'BILL_VALIDATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'BILL_CANCEL')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'BILL_DELETE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
		elseif ($action == 'LINEBILL_INSERT')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }

        // Payments
        elseif ($action == 'PAYMENT_CUSTOMER_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'PAYMENT_SUPPLIER_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }

		// Interventions
	    elseif ($action == 'FICHEINTER_VALIDATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }

        // Members
        elseif ($action == 'MEMBER_CREATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'MEMBER_VALIDATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'MEMBER_SUBSCRIPTION')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'MEMBER_MODIFY')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'MEMBER_NEW_PASSWORD')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'MEMBER_RESILIATE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'MEMBER_DELETE')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }

		return 0;
    }

}
?>
