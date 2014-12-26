<?php
/* Copyright (C) 2011-2012  Regis Houssin  <regis.houssin@capnetworks.com>
 * Copyright (C) 2014       Marcos García       <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for paypal module
 */
class InterfacePaypalWorkflow extends DolibarrTriggers
{
	public $picto = 'paypal@paypal';
	public $family = 'paypal';
	public $description = "Triggers of this module allows to manage paypal workflow";
	public $version = self::VERSION_DOLIBARR;

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User			$user       Object user
	 * @param Translate		$langs      Object langs
	 * @param conf			$conf       Object conf
	 * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        // Mettre ici le code a executer en reaction de l'action
        // Les donnees de l'action sont stockees dans $object

        if ($action == 'PAYPAL_PAYMENT_OK')
        {
        	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". source=".$object->source." ref=".$object->ref);

        	if (! empty($object->source))
        	{
        		if ($object->source == 'membersubscription')
        		{
        			//require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherents.class.php';

        			// TODO add subscription treatment
        		}
        		else
        		{
        			require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

        			$soc = new Societe($this->db);

        			// Parse element/subelement (ex: project_task)
        			$element = $path = $filename = $object->source;
        			if (preg_match('/^([^_]+)_([^_]+)/i',$object->source,$regs))
        			{
        				$element = $path = $regs[1];
        				$filename = $regs[2];
        			}
        			// For compatibility
        			if ($element == 'order') {
        				$path = $filename = 'commande';
        			}
        			if ($element == 'invoice') {
        				$path = 'compta/facture'; $filename = 'facture';
        			}

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
        	}
        	else
        	{
        		// TODO add free tag treatment
        	}

        }

		return 0;
    }

}
