<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013	   Cedric GROSS         <c.gross@kreiz-it.fr>
 * Copyright (C) 2014       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2015       Bahfir Abbes        <bafbes@gmail.com>
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
 *	\file       htdocs/core/triggers/interface_50_modAgenda_ActionsAuto.class.php
 *  \ingroup    agenda
 *  \brief      Trigger file for agenda module
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggered functions for agenda module
 */
class InterfaceActionsAuto extends DolibarrTriggers
{
	public $family = 'agenda';
	public $description = "Triggers of this module add actions in agenda according to setup made in agenda setup.";
	public $version = self::VERSION_DOLIBARR;
	public $picto = 'action';

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * Following properties may be set before calling trigger. The may be completed by this trigger to be used for writing the event into database:
	 *      $object->actiontypecode (translation action code: AC_OTH, ...)
	 *      $object->actionmsg (note, long text)
	 *      $object->actionmsg2 (label, short text)
	 *      $object->sendtoid (id of contact)
	 *      $object->socid
	 *      $object->fk_project
	 *      $object->fk_element
	 *      $object->elementtype
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
		// Module not active, we do nothing
        if (empty($conf->agenda->enabled)) {
	        return 0;
        }

		$key = 'MAIN_AGENDA_ACTIONAUTO_'.$action;

		// Do not log events not enabled for this action
		if (empty($conf->global->$key)) {
			return 0;
		}

		$langs->load("agenda");

		// Actions
		if ($action == 'COMPANY_CREATE')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("companies");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("NewCompanyToDolibarr",$object->name);
            $object->actionmsg=$langs->transnoentities("NewCompanyToDolibarr",$object->name);
            if (! empty($object->prefix)) $object->actionmsg.=" (".$object->prefix.")";
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
			$object->socid=$object->id;
        }
        elseif ($action == 'COMPANY_SENTBYMAIL')
        {
            $langs->load("agenda");
            $langs->load("other");
        	$langs->load("orders");

            if (empty($object->actiontypecode)) $object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) dol_syslog('Trigger called with property actionmsg2 on object not defined', LOG_ERR);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
		}
        elseif ($action == 'CONTRACT_VALIDATE')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("contracts");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ContractValidatedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("ContractValidatedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
		}
		elseif ($action == 'PROPAL_VALIDATE')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("propal");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("PropalValidatedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("PropalValidatedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
		}
        elseif ($action == 'PROPAL_SENTBYMAIL')
        {
            $langs->load("agenda");
            $langs->load("other");
        	$langs->load("propal");

            $object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ProposalSentByEMail",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("ProposalSentByEMail",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
		}
		elseif ($action == 'PROPAL_CLOSE_SIGNED')
        {
            $langs->load("agenda");
            $langs->load("other");
        	$langs->load("propal");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("PropalClosedSignedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("PropalClosedSignedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
		}
		elseif ($action == 'PROPAL_CLASSIFY_BILLED')
        {
            $langs->load("agenda");
            $langs->load("other");
        	$langs->load("propal");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("PropalClassifiedBilledInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("PropalClassifiedBilledInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
		}
		elseif ($action == 'PROPAL_CLOSE_REFUSED')
        {
            $langs->load("agenda");
            $langs->load("other");
        	$langs->load("propal");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("PropalClosedRefusedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("PropalClosedRefusedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
		}
		elseif ($action == 'ORDER_VALIDATE')
        {
            $langs->load("agenda");
            $langs->load("orders");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderValidatedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("OrderValidatedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
		}
		elseif ($action == 'ORDER_CLOSE')
        {
            $langs->load("agenda");
            $langs->load("other");
        	$langs->load("orders");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderDeliveredInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("OrderDeliveredInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
		}
		elseif ($action == 'ORDER_CLASSIFY_BILLED')
        {
            $langs->load("agenda");
            $langs->load("other");
        	$langs->load("orders");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderBilledInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("OrderBilledInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
		}
		elseif ($action == 'ORDER_CANCEL')
        {
            $langs->load("agenda");
            $langs->load("other");
        	$langs->load("orders");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderCanceledInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("OrderCanceledInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
		}
		elseif ($action == 'ORDER_SENTBYMAIL')
        {
            $langs->load("agenda");
            $langs->load("other");
        	$langs->load("orders");

            $object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderSentByEMail",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("OrderSentByEMail",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
		}
		elseif ($action == 'BILL_VALIDATE')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("bills");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceValidatedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("InvoiceValidatedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
		}
		elseif ($action == 'BILL_UNVALIDATE')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("bills");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceBackToDraftInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InvoiceBackToDraftInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
		}
        elseif ($action == 'BILL_SENTBYMAIL')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("bills");

            $object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceSentByEMail",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("InvoiceSentByEMail",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
		}
		elseif ($action == 'BILL_PAYED')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("bills");

            // Values for this action can't be defined by caller.
			$object->actiontypecode='AC_OTH_AUTO';
            $object->actionmsg2=$langs->transnoentities("InvoicePaidInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InvoicePaidInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
		}
		elseif ($action == 'BILL_CANCEL')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("bills");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceCanceledInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InvoiceCanceledInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
		}
		elseif ($action == 'FICHINTER_CREATE')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("interventions");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InterventionCreatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InterventionCreatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
			$object->fk_element=0;
			$object->elementtype='';
		}
		elseif ($action == 'FICHINTER_VALIDATE')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("interventions");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InterventionValidatedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("InterventionValidatedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
			$object->fk_element=0;
			$object->elementtype='';
		}
		elseif ($action == 'FICHINTER_MODIFY')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("interventions");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InterventionModifiedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InterventionModifiedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
			$object->fk_element=0;
			$object->elementtype='';
		}
		elseif ($action == 'FICHINTER_SENTBYMAIL')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("interventions");

            $object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InterventionSentByEMail",$object->ref);
            $object->actionmsg=$langs->transnoentities("InterventionSentByEMail",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
        }
        elseif ($action == 'FICHINTER_CLASSIFY_BILLED')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("interventions");

            $object->actiontypecode='AC_OTH_AUTO';
           	if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InterventionClassifiedBilledInDolibarr",$object->ref);
           	$object->actionmsg=$langs->transnoentities("InterventionClassifiedBilledInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
        }
	    elseif ($action == 'FICHINTER_CLASSIFY_UNBILLED')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("interventions");

            $object->actiontypecode='AC_OTH_AUTO';
           	if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InterventionClassifiedUnbilledInDolibarr",$object->ref);
           	$object->actionmsg=$langs->transnoentities("InterventionClassifiedUnbilledInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
        }
        elseif ($action == 'FICHINTER_DELETE')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("interventions");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InterventionDeletedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InterventionDeletedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
			$object->fk_element=0;
			$object->elementtype='';
		}
        elseif ($action == 'SHIPPING_VALIDATE')
        {
            $langs->load("agenda");
            $langs->load("other");
        	$langs->load("sendings");

        	$object->actiontypecode='AC_OTH_AUTO';
        	if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ShippingValidated",($object->newref?$object->newref:$object->ref));
        	if (empty($object->actionmsg))
        	{
        		$object->actionmsg=$langs->transnoentities("ShippingValidated",($object->newref?$object->newref:$object->ref));
        		$object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
        	}

        	// Parameters $object->sendtoid defined by caller
        	//$object->sendtoid=0;
        }
		elseif ($action == 'SHIPPING_SENTBYMAIL')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("sendings");

            $object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ShippingSentByEMail",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("ShippingSentByEMail",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
		}
		elseif ($action == 'ORDER_SUPPLIER_CREATE')
        {
            $langs->load("agenda");
            $langs->load("other");
        	$langs->load("orders");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderCreatedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("OrderCreatedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
		}
		elseif ($action == 'ORDER_SUPPLIER_VALIDATE')
        {
            $langs->load("agenda");
            $langs->load("other");
        	$langs->load("orders");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderValidatedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("OrderValidatedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
		}
		elseif ($action == 'ORDER_SUPPLIER_APPROVE')
		{
            $langs->load("agenda");
		    $langs->load("other");
			$langs->load("orders");

			$object->actiontypecode='AC_OTH_AUTO';
			if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderApprovedInDolibarr",$object->ref);
			$object->actionmsg=$langs->transnoentities("OrderApprovedInDolibarr",$object->ref);
			$object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
		}
		elseif ($action == 'ORDER_SUPPLIER_REFUSE')
		{
            $langs->load("agenda");
		    $langs->load("other");
			$langs->load("orders");

			$object->actiontypecode='AC_OTH_AUTO';
			if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderRefusedInDolibarr",$object->ref);
			$object->actionmsg=$langs->transnoentities("OrderRefusedInDolibarr",$object->ref);
			$object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
		}
		elseif ($action == 'ORDER_SUPPLIER_SUBMIT')
        {
            $langs->load("agenda");
            $langs->load("other");
        	$langs->load("orders");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("SupplierOrderSubmitedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("SupplierOrderSubmitedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
		}
		elseif ($action == 'ORDER_SUPPLIER_RECEIVE')
        {
            $langs->load("agenda");
            $langs->load("other");
        	$langs->load("orders");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("SupplierOrderReceivedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("SupplierOrderReceivedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
		}
		elseif ($action == 'ORDER_SUPPLIER_SENTBYMAIL')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("bills");
            $langs->load("orders");

            $object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("SupplierOrderSentByEMail",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("SupplierOrderSentByEMail",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
        }
		elseif ($action == 'ORDER_SUPPLIER_CLASSIFY_BILLED')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("bills");
            $langs->load("orders");

            $object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("SupplierOrderClassifiedBilled",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("SupplierOrderClassifiedBilled",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            $object->sendtoid=0;
        }
		elseif ($action == 'BILL_SUPPLIER_VALIDATE')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("bills");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceValidatedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("InvoiceValidatedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
		}
		elseif ($action == 'BILL_SUPPLIER_UNVALIDATE')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("bills");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceBackToDraftInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InvoiceBackToDraftInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
		}
        elseif ($action == 'BILL_SUPPLIER_SENTBYMAIL')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("bills");
            $langs->load("orders");

            $object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("SupplierInvoiceSentByEMail",$object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("SupplierInvoiceSentByEMail",$object->ref);
                $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
        }
		elseif ($action == 'BILL_SUPPLIER_PAYED')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("bills");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoicePaidInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InvoicePaidInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
		}
		elseif ($action == 'BILL_SUPPLIER_CANCELED')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("bills");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceCanceledInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("InvoiceCanceledInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
		}

        // Members
        elseif ($action == 'MEMBER_VALIDATE')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("members");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("MemberValidatedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("MemberValidatedInDolibarr",($object->newref?$object->newref:$object->ref));
            $object->actionmsg.="\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
            $object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->type;
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
        }
		elseif ($action == 'MEMBER_MODIFY')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("members");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("MemberModifiedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("MemberModifiedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
            $object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->type;
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

            $object->sendtoid=0;
		}
        elseif ($action == 'MEMBER_SUBSCRIPTION')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("members");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("MemberSubscriptionAddedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("MemberSubscriptionAddedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
            $object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->type;
            $object->actionmsg.="\n".$langs->transnoentities("Amount").': '.$object->last_subscription_amount;
            $object->actionmsg.="\n".$langs->transnoentities("Period").': '.dol_print_date($object->last_subscription_date_start,'day').' - '.dol_print_date($object->last_subscription_date_end,'day');
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
        }
        elseif ($action == 'MEMBER_RESILIATE')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("members");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("MemberResiliatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("MemberResiliatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
            $object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->type;
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
        }
        elseif ($action == 'MEMBER_DELETE')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("members");

			$object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("MemberDeletedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("MemberDeletedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
            $object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->type;
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
        }

        // Projects
        elseif ($action == 'PROJECT_CREATE')
        {
            $langs->load("agenda");
            $langs->load("other");
        	$langs->load("projects");

        	$object->actiontypecode='AC_OTH_AUTO';
        	if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ProjectCreatedInDolibarr",$object->ref);
        	$object->actionmsg=$langs->transnoentities("ProjectCreatedInDolibarr",$object->ref);
        	$object->actionmsg.="\n".$langs->transnoentities("Project").': '.$object->ref;
        	$object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

        	$object->sendtoid=0;
        }
        elseif($action == 'PROJECT_VALIDATE') {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("projects");
        
            $object->actiontypecode='AC_OTH_AUTO';
        
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ProjectValidatedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("ProjectValidatedInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Project").': '.$object->ref;
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
        
            $object->sendtoid=0;
        }
        elseif($action == 'PROJECT_MODIFY') {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("projects");
        
            $object->actiontypecode='AC_OTH_AUTO';
            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ProjectModifiedInDolibarr",$object->ref);
            $object->actionmsg=$langs->transnoentities("ProjectModifieddInDolibarr",$object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Task").': '.$object->ref;
            $object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;
        
            $object->sendtoid=0;
        }
        
		// Project tasks
		elseif($action == 'TASK_CREATE') {
            $langs->load("agenda");
		    $langs->load("other");
			$langs->load("projects");

			$object->actiontypecode='AC_OTH_AUTO';

			if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("TaskCreatedInDolibarr",$object->ref);
			$object->actionmsg=$langs->transnoentities("TaskCreatedInDolibarr",$object->ref);
			$object->actionmsg.="\n".$langs->transnoentities("Task").': '.$object->ref;
			$object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
		}

		elseif($action == 'TASK_MODIFY') {
            $langs->load("agenda");
		    $langs->load("other");
			$langs->load("projects");

			$object->actiontypecode='AC_OTH_AUTO';
			if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("TaskModifiedInDolibarr",$object->ref);
			$object->actionmsg=$langs->transnoentities("TaskModifieddInDolibarr",$object->ref);
			$object->actionmsg.="\n".$langs->transnoentities("Task").': '.$object->ref;
			$object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
		}

		elseif($action == 'TASK_DELETE') {
            $langs->load("agenda");
		    $langs->load("other");
			$langs->load("projects");

			$object->actiontypecode='AC_OTH_AUTO';
			if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("TaskDeletedInDolibarr",$object->ref);
			$object->actionmsg=$langs->transnoentities("TaskDeletedInDolibarr",$object->ref);
			$object->actionmsg.="\n".$langs->transnoentities("Task").': '.$object->ref;
			$object->actionmsg.="\n".$langs->transnoentities("Author").': '.$user->login;

			$object->sendtoid=0;
		}

		// The trigger was enabled but we are missing the implementation, let the log know
		else
		{
			dol_syslog("Trigger '".$this->name."' for action '$action' was ran by ".__FILE__." but no handler found for this action.", LOG_WARNING);
			return 0;
		}

		dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

        // Add entry in event table
		$now=dol_now();

		if (isset($_SESSION['listofnames-'.$object->trackid]))
		{
			$attachs=$_SESSION['listofnames-'.$object->trackid];
			if ($attachs && strpos($action,'SENTBYMAIL'))
			{
                $object->actionmsg=dol_concatdesc($object->actionmsg, "\n".$langs->transnoentities("AttachedFiles").': '.$attachs);
			}
		}

        require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		$contactforaction=new Contact($this->db);
        $societeforaction=new Societe($this->db);
        if ($object->sendtoid > 0) $contactforaction->fetch($object->sendtoid);
        if ($object->socid > 0)    $societeforaction->fetch($object->socid);

		// Insertion action
		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($this->db);
		$actioncomm->type_code   = $object->actiontypecode;		// code of parent table llx_c_actioncomm (will be deprecated)
		$actioncomm->code        = 'AC_'.$action;
		$actioncomm->label       = $object->actionmsg2;
		$actioncomm->note        = $object->actionmsg;          // TODO Replace with $actioncomm->email_msgid ? $object->email_content : $object->actionmsg
		$actioncomm->fk_project  = isset($object->fk_project)?$object->fk_project:0;
		$actioncomm->datep       = $now;
		$actioncomm->datef       = $now;
		$actioncomm->durationp   = 0;
		$actioncomm->punctual    = 1;
		$actioncomm->percentage  = -1;   // Not applicable
		$actioncomm->societe     = $societeforaction;
		$actioncomm->contact     = $contactforaction;
		$actioncomm->socid       = $societeforaction->id;
		$actioncomm->contactid   = $contactforaction->id;
		$actioncomm->authorid    = $user->id;   // User saving action
		$actioncomm->userownerid = $user->id;	// Owner of action
        // Fields when action is en email (content should be added into note)
		$actioncomm->email_msgid = $object->email_msgid;
		$actioncomm->email_from  = $object->email_from;
		$actioncomm->email_sender= $object->email_sender;
		$actioncomm->email_to    = $object->email_to;
		$actioncomm->email_tocc  = $object->email_tocc;
		$actioncomm->email_tobcc = $object->email_tobcc;
		$actioncomm->email_subject = $object->email_subject;
		$actioncomm->errors_to   = $object->errors_to;

		$actioncomm->fk_element  = $object->id;
		$actioncomm->elementtype = $object->element;

		$ret=$actioncomm->create($user);       // User creating action
		
		unset($object->actionmsg); unset($object->actionmsg2); unset($object->actiontypecode);	// When several action are called on same object, we must be sure to not reuse value of first action.
		
		if ($ret > 0)
		{
			$_SESSION['LAST_ACTION_CREATED'] = $ret;
			return 1;
		}
		else
		{
            $error ="Failed to insert event : ".$actioncomm->error." ".join(',',$actioncomm->errors);
            $this->error=$error;
            $this->errors=$actioncomm->errors;

            dol_syslog("interface_modAgenda_ActionsAuto.class.php: ".$this->error, LOG_ERR);
            return -1;
		}
    }

}
