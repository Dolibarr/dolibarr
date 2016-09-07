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
						$ret=$element->classifyBilled($user);
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
        				$ret=$element->classifyBilled($user);
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
        				$ret=$element->classifyBilled($user);
        			}
        		}
        		return $ret;
        	}

        	if (! empty($conf->propal->enabled) && ! empty($conf->global->WORKFLOW_INVOICE_CLASSIFY_BILLED_PROPAL))
        	{
        		$object->fetchObjectLinked('','propal',$object->id,$object->element);
        		if (! empty($object->linkedObjects))
        		{
        			foreach($object->linkedObjects['propal'] as $element)
        			{
        				$ret=$element->classifyBilled($user);
        			}
        		}
        		return $ret;
        	}
        }

        if ($action=='SHIPPING_VALIDATE') {
        	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);


        	if (! empty($conf->commande->enabled) && ! empty($conf->expedition->enabled) && ! empty($conf->global->WORKFLOW_ORDER_CLASSIFY_SHIPPED_SHIPPING))
        	{
        		$qtyshipped=array();
        		$qtyordred=array();
        		require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

        		//find all shippement on order origin
        		$order = new Commande($this->db);
        		$ret=$order->fetch($object->origin_id);
        		if ($ret<0) {
        			$this->error=$order->error; $this->errors=$order->errors;
        			return $ret;
        		}
        		$ret=$order->fetchObjectLinked($order->id,'commande',null,'shipping');
        		if ($ret<0) {
        			$this->error=$order->error; $this->errors=$order->errors;
        			return $ret;
        		}
        		//Build array of quantity shipped by product for an order
        		if (is_array($order->linkedObjects) && count($order->linkedObjects)>0) {
        			foreach($order->linkedObjects as $type=>$shipping_array) {
        				if ($type=='shipping' && is_array($shipping_array) && count($shipping_array)>0) {
        					foreach ($shipping_array as $shipping) {
		        				if (is_array($shipping->lines) && count($shipping->lines)>0) {
		        					foreach($shipping->lines as $shippingline) {
		        						$qtyshipped[$shippingline->fk_product]+=$shippingline->qty;
		        					}
		        				}
	        				}
        				}
        			}
        		}
        		//Build array of quantity ordered by product
        		if (is_array($order->lines) && count($order->lines)>0) {
        			foreach($order->lines as $orderline) {
        				$qtyordred[$orderline->fk_product]+=$orderline->qty;
        			}
        		}
        		//dol_syslog(var_export($qtyordred,true),LOG_DEBUG);
        		//dol_syslog(var_export($qtyshipped,true),LOG_DEBUG);
        		//Compare array
        		$diff_array=array_diff_assoc($qtyordred,$qtyshipped);
        		if (count($diff_array)==0) {
        			//No diff => mean everythings is shipped
        			$ret=$object->setStatut(Commande::STATUS_CLOSED, $object->origin_id, $object->origin);
        			if ($ret<0) {
        				$this->error=$object->error; $this->errors=$object->errors;
        				return $ret;
        			}
        		}
        	}
        }

        return 0;
    }

}
