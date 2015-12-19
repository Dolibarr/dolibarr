<?php
/* Copyright (C) 2010 Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2011 Laurent Destailleur <eldy@users.sourceforge.net>
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
 *  \file       htdocs/core/triggers/interface_20_modWorkflow_WorkflowManager.class.php
 *  \ingroup    core
 *  \brief      Trigger file for workflows
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for workflow module
 */

class InterfaceWorkflowManager extends DolibarrTriggers
{
	public $picto = 'technic';
	public $family = 'core';
	public $description = "Triggers of this module allows to manage workflows";
	public $version = self::VERSION_DOLIBARR;

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param conf		    $conf       Object conf
	 * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        if (empty($conf->workflow->enabled)) return 0;     // Module not active, we do nothing

        // Proposals to order
        if ($action == 'PROPAL_CLOSE_SIGNED')
        {
        	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            if (! empty($conf->commande->enabled) && ! empty($conf->global->WORKFLOW_PROPAL_AUTOCREATE_ORDER))
            {
                include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
                $newobject = new Commande($this->db);

                $newobject->context['createfrompropal'] = 'createfrompropal';
                $newobject->context['origin'] = $object->element;
                $newobject->context['origin_id'] = $object->id;
                
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
                include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
                $newobject = new Facture($this->db);

                $newobject->context['createfromorder'] = 'createfromorder';
                $newobject->context['origin'] = $object->element;
                $newobject->context['origin_id'] = $object->id;
                
                $ret=$newobject->createFromOrder($object);
                if ($ret < 0) { $this->error=$newobject->error; $this->errors[]=$newobject->error; }
                return $ret;
            }
        }

        // Order classify billed proposal
        if ($action == 'ORDER_CLASSIFY_BILLED')
        {
        	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        	if (! empty($conf->propal->enabled) && ! empty($conf->global->WORKFLOW_ORDER_CLASSIFY_BILLED_PROPAL))
        	{
        		$object->fetchObjectLinked('','propal',$object->id,$object->element);
				if (! empty($object->linkedObjects))
				{
					foreach($object->linkedObjects['propal'] as $element)
					{
						$ret=$element->classifyBilled();
					}
				}
        		return $ret;
        	}
        }

        // Invoice classify billed order
        if ($action == 'BILL_PAYED')
        {
        	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

        	if (! empty($conf->commande->enabled) && ! empty($conf->global->WORKFLOW_INVOICE_CLASSIFY_BILLED_ORDER))
        	{
        		$object->fetchObjectLinked('','commande',$object->id,$object->element);
        		if (! empty($object->linkedObjects))
        		{
        			foreach($object->linkedObjects['commande'] as $element)
        			{
        				$ret=$element->classifyBilled();
        			}
        		}
        		return $ret;
        	}
        }

        // classify billed order
        if ($action == 'BILL_VALIDATE')
        {
        	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

        	if (! empty($conf->commande->enabled) && ! empty($conf->global->WORKFLOW_INVOICE_AMOUNT_CLASSIFY_BILLED_ORDER))
        	{
        		$object->fetchObjectLinked('','commande',$object->id,$object->element);
        		if (! empty($object->linkedObjects))
        		{
        			foreach($object->linkedObjects['commande'] as $element)
        			{
        				$ret=$element->classifyBilled();
        			}
        		}
        		return $ret;
        	}
        }

        return 0;
    }

}
