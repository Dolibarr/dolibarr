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
 *      \file       htdocs/workflow/triggers/interface_modWorkflow_WorkflowManager.class.php
 *      \ingroup    workflow
 *      \brief      Trigger file for workflow manager
 *      \version	$Id$
 */


/**
 *      \class      InterfaceWorkflowManager
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
        $this->family = "workflow";
        $this->description = "Triggers of this module allows to manage workflow";
        $this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'workflow@workflow';
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
     *      \brief      Fonction appelee lors du declenchement d'un evenement Dolibarr.
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

		return 0;
    }

}
?>
