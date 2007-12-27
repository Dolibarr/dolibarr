<?php
/* Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/includes/triggers/interface_modNotification_notification.class.php
        \ingroup    notification
        \brief      Fichier de gestion des notifications sur evenement Dolibarr
*/


/**
        \class      InterfaceNotification
        \brief      Classe des fonctions triggers des actions personalisées du workflow
*/

class InterfaceNotification
{
    var $db;
    
    /**
     *   \brief      Constructeur.
     *   \param      DB      Handler d'accès base
     */
    function InterfaceNotification($DB)
    {
        $this->db = $DB ;
    
        $this->name = "Notification";
        $this->family = "notification";
        $this->description = "Les triggers de ce composant envoie les notifications par mail selon configuration du module Notification.";
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
     *      \return     int         <0 si ko, 0 si aucune action faite, >0 si ok
     */
	function run_trigger($action,$object,$user,$langs,$conf)
    {
        // Mettre ici le code à exécuter en réaction de l'action
        // Les données de l'action sont stockées dans $object
    
		// Si module notification non actif, on ne fait rien
		if (! $conf->notification->enabled) return 0;

		require_once(DOL_DOCUMENT_ROOT .'/notify.class.php');

		if ($action == 'BILL_VALIDATE')
		{
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

			$action_notify = 2;
            $ref = sanitize_string($object->ref);
            $filepdf = $conf->facture->dir_output . '/' . $ref . '/' . $ref . '.pdf';
            $mesg = 'La facture '.$object->ref." a été validée.\n";

            $notify = new Notify($this->db); 
            $notify->send($action_notify, $object->socid, $mesg, 'facture', $object->id, $filepdf);
		}

		elseif ($action == 'FICHEINTER_VALIDATE')
		{
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

			$action_notify = 1;
            $ref = sanitize_string($object->ref);
            $filepdf = $conf->facture->dir_output . '/' . $ref . '/' . $ref . '.pdf';
            $mesg = 'La fiche intervention '.$object->ref." a été validée.\n";

            $notify = new Notify($this->db); 
            $notify->send($action_notify, $object->socid, $mesg, 'ficheinter', $object->id, $filepdf);
		}

		elseif ($action == 'ORDER_SUPPLIER_VALIDATE')
		{
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

			$action_notify = 3;
            $ref = sanitize_string($object->ref);
            $filepdf = $conf->fournisseur->commande->dir_output . '/' . $ref . '/' . $ref . '.pdf';
            $mesg = 'La commande fournisseur '.$object->ref." a été validée.\n";

            $notify = new Notify($this->db); 
            $notify->send($action_notify, $object->socid, $mesg, 'order_supplier', $object->id, $filepdf);
		}

		// If not found
/*
        else
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' was ran by ".__FILE__." but no handler found for this action.");
			return -1;
        }
*/
		return 0;
    }

}
?>
