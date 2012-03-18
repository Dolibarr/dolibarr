<?php
/* Copyright (C) 2011  Regis Houssin  <regis@dolibarr.fr>
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
 *      \file       /htdocs/core/triggers/interface_20_modPaypal_PaypalWorkflow.class.php
 *      \ingroup    paypal
 *      \brief      Trigger file for paypal workflow
 */


/**
 *      \class      InterfacePaypalWorkflow
 *      \brief      Class of triggers for paypal module
 */
class InterfacePaypalWorkflow
{
    var $db;

    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    function InterfacePaypalWorkflow($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "paypal";
        $this->description = "Triggers of this module allows to manage paypal workflow";
        $this->version = 'dolibarr';            // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'paypal@paypal';
    }


    /**
     *  Renvoi nom du lot de triggers
     *
     *  @return     string      Nom du lot de triggers
     */
    function getName()
    {
        return $this->name;
    }

    /**
     *  Renvoi descriptif du lot de triggers
     *
     *  @return     string      Descriptif du lot de triggers
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *  Renvoi version du lot de triggers
     *
     *  @return     string      Version du lot de triggers
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
     *  Fonction appelee lors du declenchement d'un evenement Dolibarr.
     *  D'autres fonctions run_trigger peuvent etre presentes dans core/triggers
     *
     *  @param	string			$action     Code de l'evenement
     *  @param  CommonObject	$object     Objet concerne
     *  @param  User			$user       Objet user
     *  @param  Translate		$langs      Objet lang
     *  @param  Conf			$conf       Objet conf
     *  @return int         				<0 if fatal error, 0 si nothing done, >0 if ok
     */
	function run_trigger($action,$object,$user,$langs,$conf)
    {
        // Mettre ici le code a executer en reaction de l'action
        // Les donnees de l'action sont stockees dans $object

        if ($action == 'PAYPAL_PAYMENT_OK')
        {
        	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". source=".$object->source." ref=".$object->ref);

        	require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");

        	$soc = new Societe($this->db);

        	// Parse element/subelement (ex: project_task)
	        $element = $path = $filename = $object->source;
	        if (preg_match('/^([^_]+)_([^_]+)/i',$object->source,$regs))
	        {
	            $element = $path = $regs[1];
	            $filename = $regs[2];
	        }
	        // For compatibility
            if ($element == 'order') { $path = $filename = 'commande'; }
            if ($element == 'invoice') { $path = 'compta/facture'; $filename = 'facture'; }

            dol_include_once('/'.$path.'/class/'.$filename.'.class.php');

            $classname = ucfirst($filename);
            $obj = new $classname($this->db);

            $ret = $obj->fetch('',$object->ref);
            if ($ret < 0) return -1;

            // Add payer id
            $soc->setValueFrom('ref_int', $object->payerID, 'societe', $obj->socid);

            // Add transaction id
            $obj->setValueFrom('ref_int',$object->resArray["TRANSACTIONID"]);

        }

		return 0;
    }

}
?>
