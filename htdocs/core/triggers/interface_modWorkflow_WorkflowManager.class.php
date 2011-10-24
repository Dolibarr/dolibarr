<?php
/* Copyright (C) 2010 Regis Houssin       <regis@dolibarr.fr>
 * Copyright (C) 2011 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/core/triggers/interface_modWorkflow_WorkflowManager.class.php
 *      \ingroup    core
 *      \brief      Trigger file for workflows
 */


/**
 *      \class      InterfaceWorkflowManager
 *      \brief      Class of triggers for workflow module
 */

class InterfaceWorkflowManager
{
    var $db;

    /**
     *   Constructor.
     *   @param      DB      Database handler
     */
    function InterfaceWorkflowManager($DB)
    {
        $this->db = $DB ;

        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "core";
        $this->description = "Triggers of this module allows to manage workflows";
        $this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'technic';
    }


    /**
     *   Return name of trigger file
     *   @return     string      Name of trigger file
     */
    function getName()
    {
        return $this->name;
    }

    /**
     *   Return description of trigger file
     *   @return     string      Description of trigger file
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *   @return     string      Version of trigger file
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
     *      Function called when a Dolibarrr business event is done.
     *      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
     *
     *      @param      action      Event code (COMPANY_CREATE, PROPAL_VALIDATE, ...)
     *      @param      object      Object action is done on
     *      @param      user        Object user
     *      @param      langs       Object langs
     *      @param      conf        Object conf
     *      @return     int         <0 if KO, 0 if no action are done, >0 if OK
     */
	function run_trigger($action,$object,$user,$langs,$conf)
    {
        if (empty($conf->workflow->enabled)) return 0;     // Module not active, we do nothing

        // Proposals to order
        if ($action == 'PROPAL_CLOSE_SIGNED')
        {
        	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            if (! empty($conf->commande->enabled) && ! empty($conf->global->WORKFLOW_PROPAL_AUTOCREATE_ORDER))
            {
                include_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
                $newobject = new Commande($this->db);

                $ret=$newobject->createFromProposal($object);
                if ($ret < 0) { $this->error=$newobject->error; $this->errors[]=$newobject->error; }
                return $ret;
            }
        }

        // Order to invoice
        if ($action == 'ORDER_CLOSE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            if (! empty($conf->facture->enabled) && ! empty($conf->global->WORKFLOW_ORDER_AUTOCREATE_INVOICE))
            {
                include_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
                $newobject = new Facture($this->db);

                $ret=$newobject->createFromOrder($object);
                if ($ret < 0) { $this->error=$newobject->error; $this->errors[]=$newobject->error; }
                return $ret;
            }
        }

        return 0;
    }

}
?>
