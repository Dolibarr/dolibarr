<?php
/* Copyright (C) 2010  Regis Houssin     <regis@dolibarr.fr>
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
 *      \file       htdocs/includes/triggers/interface_modWorkflow_WorkflowManager.class.php
 *      \ingroup    core
 *      \brief      Trigger file for workflows
 *      \version	$Id$
 */


/**
 *      \class      InterfaceWorkflow
 *      \brief      Classe des fonctions triggers des actions personalisees du workflow
 */

class InterfaceWorkflowManager
{
    var $db;

    /**
     *   \brief      Constructeur.
     *   \param      DB      Handler d'acces base
     */
    function InterfaceWorkflowManager($DB)
    {
        $this->db = $DB ;

        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "core";
        $this->description = "Triggers of this module allows to manage workflows";
        $this->version = 'experimental';            // 'development', 'experimental', 'dolibarr' or version
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

        if ($this->version == 'development') return $langs->trans("Development");
        elseif ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

    /**
     *      Fonction appelee lors du declenchement d'un evenement Dolibarr.
     *                  D'autres fonctions run_trigger peuvent etre presentes dans includes/triggers
     *      \param      action      Code de l'evenement
     *      \param      object      Objet concerne
     *      \param      user        Objet user
     *      \param      lang        Objet lang
     *      \param      conf        Objet conf
     *      \return     int         <0 if fatal error, 0 si nothing done, >0 if ok
     */
	function run_trigger($action,$object,$user,$langs,$conf)
    {
        // Mettre ici le code a executer en reaction de l'action
        // Les donnees de l'action sont stockees dans $object

        // Proposals
        if ($action == 'PROPAL_CLOSE_SIGNED')
        {
        	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            if (! empty($conf->commande->enabled) && ! empty($conf->global->WORKFLOW_PROPAL_AUTOCREATE_ORDER))
            {
                include_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
                $order = new Commande($this->db);
                return $order->createFromProposal($object,0);
            }
        }

		return 0;
    }

}
?>
