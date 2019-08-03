<?php
/* Copyright (C) 2005-2017	Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2017	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2014	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013		Cedric GROSS			<c.gross@kreiz-it.fr>
 * Copyright (C) 2014		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2015		Bahfir Abbes			<bafbes@gmail.com>
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

	/**
	 * Version of the trigger
	 * @var string
	 */
	public $version = self::VERSION_DOLIBARR;

	/**
	 * @var string Image of the trigger
	 */
	public $picto = 'action';

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * Following properties may be set before calling trigger. The may be completed by this trigger to be used for writing the event into database:
	 *      $object->actiontypecode (translation action code: AC_OTH, ...)
	 *      $object->actionmsg (note, long text)
	 *      $object->actionmsg2 (label, short text)
	 *      $object->sendtoid (id of contact or array of ids)
	 *      $object->socid (id of thirdparty)
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
        if (empty($conf->agenda->enabled)) return 0;     // Module not active, we do nothing

		$key = 'MAIN_AGENDA_ACTIONAUTO_'.$action;

		// Do not log events not enabled for this action
		if (empty($conf->global->$key)) {
			return 0;
		}

		$langs->load("agenda");

		if (empty($object->actiontypecode)) $object->actiontypecode='AC_OTH_AUTO';

		// Actions
		if ($action == 'COMPANY_CREATE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","companies"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("NewCompanyToDolibarr", $object->name);
            $object->actionmsg=$langs->transnoentities("NewCompanyToDolibarr", $object->name);
            if (! empty($object->prefix)) $object->actionmsg.=" (".$object->prefix.")";

			$object->sendtoid=0;
			$object->socid=$object->id;
        }
        elseif ($action == 'COMPANY_SENTBYMAIL')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","orders"));

            if (empty($object->actionmsg2)) dol_syslog('Trigger called with property actionmsg2 on object not defined', LOG_ERR);

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
		}
        elseif ($action == 'CONTRACT_VALIDATE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","contracts"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ContractValidatedInDolibarr", ($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("ContractValidatedInDolibarr", ($object->newref?$object->newref:$object->ref));

            $object->sendtoid=0;
		}
		elseif ($action == 'CONTRACT_SENTBYMAIL')
		{
			// Load translation files required by the page
            $langs->loadLangs(array("agenda","other","contracts"));

			if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ContractSentByEMail", $object->ref);
			if (empty($object->actionmsg))
			{
				$object->actionmsg=$langs->transnoentities("ContractSentByEMail", $object->ref);
			}

			// Parameters $object->sendtoid defined by caller
			//$object->sendtoid=0;
		}
		elseif ($action == 'PROPAL_VALIDATE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","propal"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("PropalValidatedInDolibarr", ($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("PropalValidatedInDolibarr", ($object->newref?$object->newref:$object->ref));

			$object->sendtoid=0;
		}
        elseif ($action == 'PROPAL_SENTBYMAIL')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","propal"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ProposalSentByEMail", $object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("ProposalSentByEMail", $object->ref);
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
		}
		elseif ($action == 'PROPAL_CLOSE_SIGNED')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","propal"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("PropalClosedSignedInDolibarr", $object->ref);
            $object->actionmsg=$langs->transnoentities("PropalClosedSignedInDolibarr", $object->ref);

			$object->sendtoid=0;
		}
		elseif ($action == 'PROPAL_CLASSIFY_BILLED')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","propal"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("PropalClassifiedBilledInDolibarr", $object->ref);
            $object->actionmsg=$langs->transnoentities("PropalClassifiedBilledInDolibarr", $object->ref);

			$object->sendtoid=0;
		}
		elseif ($action == 'PROPAL_CLOSE_REFUSED')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","propal"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("PropalClosedRefusedInDolibarr", $object->ref);
            $object->actionmsg=$langs->transnoentities("PropalClosedRefusedInDolibarr", $object->ref);

			$object->sendtoid=0;
		}
		elseif ($action == 'ORDER_VALIDATE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","orders"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderValidatedInDolibarr", ($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("OrderValidatedInDolibarr", ($object->newref?$object->newref:$object->ref));

			$object->sendtoid=0;
		}
		elseif ($action == 'ORDER_CLOSE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","orders"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderDeliveredInDolibarr", $object->ref);
            $object->actionmsg=$langs->transnoentities("OrderDeliveredInDolibarr", $object->ref);

			$object->sendtoid=0;
		}
		elseif ($action == 'ORDER_CLASSIFY_BILLED')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","orders"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderBilledInDolibarr", $object->ref);
            $object->actionmsg=$langs->transnoentities("OrderBilledInDolibarr", $object->ref);

			$object->sendtoid=0;
		}
		elseif ($action == 'ORDER_CANCEL')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","orders"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderCanceledInDolibarr", $object->ref);
            $object->actionmsg=$langs->transnoentities("OrderCanceledInDolibarr", $object->ref);

			$object->sendtoid=0;
		}
		elseif ($action == 'ORDER_SENTBYMAIL')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","orders"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderSentByEMail", $object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("OrderSentByEMail", $object->ref);
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
		}
		elseif ($action == 'BILL_VALIDATE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","bills"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceValidatedInDolibarr", ($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("InvoiceValidatedInDolibarr", ($object->newref?$object->newref:$object->ref));

			$object->sendtoid=0;
		}
		elseif ($action == 'BILL_UNVALIDATE')
        {
           // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","bills"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceBackToDraftInDolibarr", $object->ref);
            $object->actionmsg=$langs->transnoentities("InvoiceBackToDraftInDolibarr", $object->ref);

			$object->sendtoid=0;
		}
        elseif ($action == 'BILL_SENTBYMAIL')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","bills"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceSentByEMail", $object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("InvoiceSentByEMail", $object->ref);
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
		}
		elseif ($action == 'BILL_PAYED')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","bills"));

            // Values for this action can't be defined by caller.
            $object->actionmsg2=$langs->transnoentities("InvoicePaidInDolibarr", $object->ref);
            $object->actionmsg=$langs->transnoentities("InvoicePaidInDolibarr", $object->ref);

            $object->sendtoid=0;
		}
		elseif ($action == 'BILL_CANCEL')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","bills"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceCanceledInDolibarr", $object->ref);
            $object->actionmsg=$langs->transnoentities("InvoiceCanceledInDolibarr", $object->ref);

            $object->sendtoid=0;
		}
		elseif ($action == 'FICHINTER_CREATE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","interventions"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InterventionCreatedInDolibarr", $object->ref);
            $object->actionmsg=$langs->transnoentities("InterventionCreatedInDolibarr", $object->ref);

            $object->sendtoid=0;
			$object->fk_element=0;
			$object->elementtype='';
		}
		elseif ($action == 'FICHINTER_VALIDATE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","interventions"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InterventionValidatedInDolibarr", ($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("InterventionValidatedInDolibarr", ($object->newref?$object->newref:$object->ref));

            $object->sendtoid=0;
			$object->fk_element=0;
			$object->elementtype='';
		}
		elseif ($action == 'FICHINTER_MODIFY')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","interventions"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InterventionModifiedInDolibarr", $object->ref);
            $object->actionmsg=$langs->transnoentities("InterventionModifiedInDolibarr", $object->ref);

            $object->sendtoid=0;
			$object->fk_element=0;
			$object->elementtype='';
		}
		elseif ($action == 'FICHINTER_SENTBYMAIL')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","interventions"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InterventionSentByEMail", $object->ref);
            if (empty($object->actionmsg))
            {
            	$object->actionmsg=$langs->transnoentities("InterventionSentByEMail", $object->ref);
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
        }
        elseif ($action == 'FICHINTER_CLASSIFY_BILLED')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","interventions"));

           	if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InterventionClassifiedBilledInDolibarr", $object->ref);
           	$object->actionmsg=$langs->transnoentities("InterventionClassifiedBilledInDolibarr", $object->ref);

            $object->sendtoid=0;
        }
	    elseif ($action == 'FICHINTER_CLASSIFY_UNBILLED')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","interventions"));

           	if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InterventionClassifiedUnbilledInDolibarr", $object->ref);
           	$object->actionmsg=$langs->transnoentities("InterventionClassifiedUnbilledInDolibarr", $object->ref);

            $object->sendtoid=0;
        }
        elseif ($action == 'FICHINTER_DELETE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","interventions"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InterventionDeletedInDolibarr", $object->ref);
            $object->actionmsg=$langs->transnoentities("InterventionDeletedInDolibarr", $object->ref);

            $object->sendtoid=0;
			$object->fk_element=0;
			$object->elementtype='';
		}
        elseif ($action == 'SHIPPING_VALIDATE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","sendings"));

        	if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ShippingValidated", ($object->newref?$object->newref:$object->ref));
        	if (empty($object->actionmsg))
        	{
        		$object->actionmsg=$langs->transnoentities("ShippingValidated", ($object->newref?$object->newref:$object->ref));
        	}

        	// Parameters $object->sendtoid defined by caller
        	//$object->sendtoid=0;
        }
		elseif ($action == 'SHIPPING_SENTBYMAIL')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","sendings"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ShippingSentByEMail", $object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("ShippingSentByEMail", $object->ref);
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
		} elseif ($action == 'RECEPTION_VALIDATE')
        {
            $langs->load("agenda");
            $langs->load("other");
        	$langs->load("receptions");

        	if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ReceptionValidated", ($object->newref?$object->newref:$object->ref));
        	if (empty($object->actionmsg))
        	{
        		$object->actionmsg=$langs->transnoentities("ReceptionValidated", ($object->newref?$object->newref:$object->ref));
        	}

        	// Parameters $object->sendtoid defined by caller
        	//$object->sendtoid=0;
        }
		elseif ($action == 'RECEPTION_SENTBYMAIL')
        {
            $langs->load("agenda");
            $langs->load("other");
            $langs->load("receptions");

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ReceptionSentByEMail", $object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("ReceptionSentByEMail", $object->ref);
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
		}
		elseif ($action == 'PROPOSAL_SUPPLIER_VALIDATE')
		{
			// Load translation files required by the page
            $langs->loadLangs(array("agenda","other","propal"));

			if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("PropalValidatedInDolibarr", ($object->newref?$object->newref:$object->ref));
			$object->actionmsg=$langs->transnoentities("PropalValidatedInDolibarr", ($object->newref?$object->newref:$object->ref));

			$object->sendtoid=0;
		}
		elseif ($action == 'PROPOSAL_SUPPLIER_SENTBYMAIL')
		{
			// Load translation files required by the page
            $langs->loadLangs(array("agenda","other","propal"));

			if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ProposalSentByEMail", $object->ref);
			if (empty($object->actionmsg))
			{
				$object->actionmsg=$langs->transnoentities("ProposalSentByEMail", $object->ref);
			}

			// Parameters $object->sendtoid defined by caller
			//$object->sendtoid=0;
		}
		elseif ($action == 'PROPOSAL_SUPPLIER_CLOSE_SIGNED')
		{
			// Load translation files required by the page
            $langs->loadLangs(array("agenda","other","propal"));

			if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("PropalClosedSignedInDolibarr", $object->ref);
			$object->actionmsg=$langs->transnoentities("PropalClosedSignedInDolibarr", $object->ref);

			$object->sendtoid=0;
		}
		elseif ($action == 'PROPOSAL_SUPPLIER_CLOSE_REFUSED')
		{
			// Load translation files required by the page
            $langs->loadLangs(array("agenda","other","propal"));

			if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("PropalClosedRefusedInDolibarr", $object->ref);
			$object->actionmsg=$langs->transnoentities("PropalClosedRefusedInDolibarr", $object->ref);

			$object->sendtoid=0;
		}
		elseif ($action == 'ORDER_SUPPLIER_CREATE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","orders"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderCreatedInDolibarr", ($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("OrderCreatedInDolibarr", ($object->newref?$object->newref:$object->ref));

            $object->sendtoid=0;
		}
		elseif ($action == 'ORDER_SUPPLIER_VALIDATE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","orders"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderValidatedInDolibarr", ($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("OrderValidatedInDolibarr", ($object->newref?$object->newref:$object->ref));

            $object->sendtoid=0;
		}
		elseif ($action == 'ORDER_SUPPLIER_APPROVE')
		{
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","orders"));

			if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderApprovedInDolibarr", $object->ref);
			$object->actionmsg=$langs->transnoentities("OrderApprovedInDolibarr", $object->ref);

			$object->sendtoid=0;
		}
		elseif ($action == 'ORDER_SUPPLIER_REFUSE')
		{
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","orders"));

			if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("OrderRefusedInDolibarr", $object->ref);
			$object->actionmsg=$langs->transnoentities("OrderRefusedInDolibarr", $object->ref);

			$object->sendtoid=0;
		}
		elseif ($action == 'ORDER_SUPPLIER_SUBMIT')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","orders"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("SupplierOrderSubmitedInDolibarr", ($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("SupplierOrderSubmitedInDolibarr", ($object->newref?$object->newref:$object->ref));

            $object->sendtoid=0;
		}
		elseif ($action == 'ORDER_SUPPLIER_RECEIVE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","orders"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("SupplierOrderReceivedInDolibarr", ($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("SupplierOrderReceivedInDolibarr", ($object->newref?$object->newref:$object->ref));

            $object->sendtoid=0;
		}
		elseif ($action == 'ORDER_SUPPLIER_SENTBYMAIL')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","bills","orders"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("SupplierOrderSentByEMail", $object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("SupplierOrderSentByEMail", $object->ref);
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
        }
		elseif ($action == 'ORDER_SUPPLIER_CLASSIFY_BILLED')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","bills","orders"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("SupplierOrderClassifiedBilled", $object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("SupplierOrderClassifiedBilled", $object->ref);
            }

            $object->sendtoid=0;
        }
		elseif ($action == 'BILL_SUPPLIER_VALIDATE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","bills"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceValidatedInDolibarr", ($object->newref?$object->newref:$object->ref));
            $object->actionmsg=$langs->transnoentities("InvoiceValidatedInDolibarr", ($object->newref?$object->newref:$object->ref));

            $object->sendtoid=0;
		}
		elseif ($action == 'BILL_SUPPLIER_UNVALIDATE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","bills"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceBackToDraftInDolibarr", $object->ref);
            $object->actionmsg=$langs->transnoentities("InvoiceBackToDraftInDolibarr", $object->ref);

            $object->sendtoid=0;
		}
        elseif ($action == 'BILL_SUPPLIER_SENTBYMAIL')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","bills","orders"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("SupplierInvoiceSentByEMail", $object->ref);
            if (empty($object->actionmsg))
            {
                $object->actionmsg=$langs->transnoentities("SupplierInvoiceSentByEMail", $object->ref);
            }

            // Parameters $object->sendtoid defined by caller
            //$object->sendtoid=0;
        }
		elseif ($action == 'BILL_SUPPLIER_PAYED')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","bills"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoicePaidInDolibarr", $object->ref);
            $object->actionmsg=$langs->transnoentities("InvoicePaidInDolibarr", $object->ref);

			$object->sendtoid=0;
		}
		elseif ($action == 'BILL_SUPPLIER_CANCELED')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","bills"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("InvoiceCanceledInDolibarr", $object->ref);
            $object->actionmsg=$langs->transnoentities("InvoiceCanceledInDolibarr", $object->ref);

			$object->sendtoid=0;
		}

        // Members
        elseif ($action == 'MEMBER_VALIDATE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","members"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("MemberValidatedInDolibarr", $object->getFullName($langs));
            $object->actionmsg=$langs->transnoentities("MemberValidatedInDolibarr", $object->getFullName($langs));
            $object->actionmsg.="\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
            $object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->type;

			$object->sendtoid=0;
        }
		elseif ($action == 'MEMBER_MODIFY')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","members"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("MemberModifiedInDolibarr", $object->getFullName($langs));
            $object->actionmsg=$langs->transnoentities("MemberModifiedInDolibarr", $object->getFullName($langs));
            $object->actionmsg.="\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
            $object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->type;

            $object->sendtoid=0;
		}
        elseif ($action == 'MEMBER_SUBSCRIPTION_CREATE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","members"));

            $member = $this->context['member'];
            if (! is_object($member))	// This should not happen
            {
	            include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
	            $member = new Adherent($this->db);
	            $member->fetch($this->fk_adherent);
            }

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("MemberSubscriptionAddedInDolibarr", $object->id, $member->getFullName($langs));
            $object->actionmsg=$langs->transnoentities("MemberSubscriptionAddedInDolibarr", $object->id, $member->getFullName($langs));
            $object->actionmsg.="\n".$langs->transnoentities("Member").': '.$member->getFullName($langs);
            $object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->fk_type;
            $object->actionmsg.="\n".$langs->transnoentities("Amount").': '.$object->amount;
            $object->actionmsg.="\n".$langs->transnoentities("Period").': '.dol_print_date($object->dateh, 'day').' - '.dol_print_date($object->datef, 'day');

			$object->sendtoid=0;
			if ($object->fk_soc > 0) $object->socid=$object->fk_soc;
        }
        elseif ($action == 'MEMBER_SUBSCRIPTION_MODIFY')
        {
        	// Load translation files required by the page
            $langs->loadLangs(array("agenda","other","members"));

            $member = $this->context['member'];
            if (! is_object($member))	// This should not happen
            {
            	include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
            	$member = new Adherent($this->db);
            	$member->fetch($this->fk_adherent);
            }

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("MemberSubscriptionModifiedInDolibarr", $object->id, $member->getFullName($langs));
        	$object->actionmsg=$langs->transnoentities("MemberSubscriptionModifiedInDolibarr", $object->id, $member->getFullName($langs));
        	$object->actionmsg.="\n".$langs->transnoentities("Member").': '.$member->getFullName($langs);
        	$object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->fk_type;
        	$object->actionmsg.="\n".$langs->transnoentities("Amount").': '.$object->amount;
        	$object->actionmsg.="\n".$langs->transnoentities("Period").': '.dol_print_date($object->dateh, 'day').' - '.dol_print_date($object->datef, 'day');

        	$object->sendtoid=0;
        	if ($object->fk_soc > 0) $object->socid=$object->fk_soc;
        }
        elseif ($action == 'MEMBER_SUBSCRIPTION_DELETE')
        {
        	// Load translation files required by the page
            $langs->loadLangs(array("agenda","other","members"));

        	if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("MemberSubscriptionDeletedInDolibarr", $object->ref, $object->getFullName($langs));
        	$object->actionmsg=$langs->transnoentities("MemberSubscriptionDeletedInDolibarr", $object->ref, $object->getFullName($langs));
        	$object->actionmsg.="\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
        	$object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->type;
        	$object->actionmsg.="\n".$langs->transnoentities("Amount").': '.$object->last_subscription_amount;
        	$object->actionmsg.="\n".$langs->transnoentities("Period").': '.dol_print_date($object->last_subscription_date_start, 'day').' - '.dol_print_date($object->last_subscription_date_end, 'day');

        	$object->sendtoid=0;
        	if ($object->fk_soc > 0) $object->socid=$object->fk_soc;
        }
        elseif ($action == 'MEMBER_RESILIATE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","members"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("MemberResiliatedInDolibarr", $object->getFullName($langs));
            $object->actionmsg=$langs->transnoentities("MemberResiliatedInDolibarr", $object->getFullName($langs));
            $object->actionmsg.="\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
            $object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->type;

			$object->sendtoid=0;
        }
        elseif ($action == 'MEMBER_DELETE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","members"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("MemberDeletedInDolibarr", $object->getFullName($langs));
            $object->actionmsg=$langs->transnoentities("MemberDeletedInDolibarr", $object->getFullName($langs));
            $object->actionmsg.="\n".$langs->transnoentities("Member").': '.$object->getFullName($langs);
            $object->actionmsg.="\n".$langs->transnoentities("Type").': '.$object->type;

			$object->sendtoid=0;
        }

        // Projects
        elseif ($action == 'PROJECT_CREATE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","projects"));

        	if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ProjectCreatedInDolibarr", $object->ref);
        	$object->actionmsg=$langs->transnoentities("ProjectCreatedInDolibarr", $object->ref);
        	$object->actionmsg.="\n".$langs->transnoentities("Project").': '.$object->ref;

        	$object->sendtoid=0;
        }
        elseif($action == 'PROJECT_VALIDATE')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","projects"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ProjectValidatedInDolibarr", $object->ref);
            $object->actionmsg=$langs->transnoentities("ProjectValidatedInDolibarr", $object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Project").': '.$object->ref;

            $object->sendtoid=0;
        }
        elseif($action == 'PROJECT_MODIFY')
        {
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","projects"));

            if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("ProjectModifiedInDolibarr", $object->ref);
            $object->actionmsg=$langs->transnoentities("ProjectModifiedInDolibarr", $object->ref);
            $object->actionmsg.="\n".$langs->transnoentities("Task").': '.$object->ref;

            $object->sendtoid=0;
        }

		// Project tasks
		elseif($action == 'TASK_CREATE')
		{
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","projects"));

			if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("TaskCreatedInDolibarr", $object->ref);
			$object->actionmsg=$langs->transnoentities("TaskCreatedInDolibarr", $object->ref);
			$object->actionmsg.="\n".$langs->transnoentities("Task").': '.$object->ref;

			$object->sendtoid=0;
		}
		elseif($action == 'TASK_MODIFY')
		{
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","projects"));

			if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("TaskModifiedInDolibarr", $object->ref);
			$object->actionmsg=$langs->transnoentities("TaskModifieddInDolibarr", $object->ref);
			$object->actionmsg.="\n".$langs->transnoentities("Task").': '.$object->ref;

			$object->sendtoid=0;
		}
		elseif($action == 'TASK_DELETE')
		{
            // Load translation files required by the page
            $langs->loadLangs(array("agenda","other","projects"));

			if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("TaskDeletedInDolibarr", $object->ref);
			$object->actionmsg=$langs->transnoentities("TaskDeletedInDolibarr", $object->ref);
			$object->actionmsg.="\n".$langs->transnoentities("Task").': '.$object->ref;

			$object->sendtoid=0;
		}
		elseif($action == 'TICKET_ASSIGNED')
		{
		    // Load translation files required by the page
		    $langs->loadLangs(array("agenda","other","projects"));

		    if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities("TICKET_ASSIGNEDInDolibarr", $object->ref);
		    $object->actionmsg=$langs->transnoentities("TICKET_ASSIGNEDInDolibarr", $object->ref);
		    if ($object->oldcopy->fk_user_assign > 0)
		    {
		      $tmpuser=new User($this->db);
		      $tmpuser->fetch($object->oldcopy->fk_user_assign);
		      $object->actionmsg.="\n".$langs->transnoentities("OldUser").': '.$tmpuser->getFullName($langs);
		    }
		    else
		    {
		        $object->actionmsg.="\n".$langs->transnoentities("OldUser").': '.$langs->trans("None");
		    }
		    if ($object->fk_user_assign > 0)
		    {
		        $tmpuser=new User($this->db);
		        $tmpuser->fetch($object->fk_user_assign);
		        $object->actionmsg.="\n".$langs->transnoentities("NewUser").': '.$tmpuser->getFullName($langs);
		    }
		    else
		    {
		        $object->actionmsg.="\n".$langs->transnoentities("NewUser").': '.$langs->trans("None");
		    }
		    $object->sendtoid=0;
		}
		// TODO Merge all previous cases into this generic one
		else	// $action = TICKET_CREATE, TICKET_MODIFY, TICKET_DELETE, ...
		{
		    // Note: We are here only if $conf->global->MAIN_AGENDA_ACTIONAUTO_action is on (tested at begining of this function). Key can be set in agenda setup if defined into c_action_trigger
		    // Load translation files required by the page
            $langs->loadLangs(array("agenda","other"));

		    if (empty($object->actionmsg2)) $object->actionmsg2=$langs->transnoentities($action."InDolibarr", $object->ref);
		    if (empty($object->actionmsg))  $object->actionmsg=$langs->transnoentities($action."InDolibarr", $object->ref);

		    $object->sendtoid=0;
		}

		$object->actionmsg = $langs->transnoentities("Author").': '.$user->login."\n".$object->actionmsg;

		dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

        // Add entry in event table
		$now=dol_now();

		if (isset($_SESSION['listofnames-'.$object->trackid]))
		{
			$attachs=$_SESSION['listofnames-'.$object->trackid];
			if ($attachs && strpos($action, 'SENTBYMAIL'))
			{
                $object->actionmsg=dol_concatdesc($object->actionmsg, "\n".$langs->transnoentities("AttachedFiles").': '.$attachs);
			}
		}

        require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		$contactforaction=new Contact($this->db);
        $societeforaction=new Societe($this->db);
        // Set contactforaction if there is only 1 contact.
        if (is_array($object->sendtoid))
        {
            if (count($object->sendtoid) == 1) $contactforaction->fetch(reset($object->sendtoid));
        }
        else
        {
            if ($object->sendtoid > 0) $contactforaction->fetch($object->sendtoid);
        }
        // Set societeforaction.
        if ($object->socid > 0)			$societeforaction->fetch($object->socid);
        elseif ($object->fk_soc > 0)	$societeforaction->fetch($object->fk_soc);

        $projectid = isset($object->fk_project)?$object->fk_project:0;
        if ($object->element == 'project') $projectid = $object->id;

        $elementid = $object->id;
        $elementtype = $object->element;
        if ($object->element == 'subscription')
        {
        	$elementid = $object->fk_adherent;
        	$elementtype = 'member';
        }
        //var_dump($societeforaction);var_dump($contactforaction);exit;

		// Insertion action
		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($this->db);
		$actioncomm->type_code   = $object->actiontypecode;		// Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
		$actioncomm->code        = 'AC_'.$action;
		$actioncomm->label       = $object->actionmsg2;
		$actioncomm->note        = $object->actionmsg;          // TODO Replace with ($actioncomm->email_msgid ? $object->email_content : $object->actionmsg)
		$actioncomm->fk_project  = $projectid;
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
        // Fields defined when action is an email (content should be into object->actionmsg to be added into note, subject into object->actionms2 to be added into label)
		$actioncomm->email_msgid   = $object->email_msgid;
		$actioncomm->email_from    = $object->email_from;
		$actioncomm->email_sender  = $object->email_sender;
		$actioncomm->email_to      = $object->email_to;
		$actioncomm->email_tocc    = $object->email_tocc;
		$actioncomm->email_tobcc   = $object->email_tobcc;
		$actioncomm->email_subject = $object->email_subject;
		$actioncomm->errors_to     = $object->errors_to;

		// Object linked (if link is for thirdparty, contact, project it is a recording error. We should not have links in link table
		// for such objects because there is already a dedicated field into table llx_actioncomm.
		if (! in_array($elementtype, array('societe','contact','project')))
		{
			$actioncomm->fk_element  = $elementid;
			$actioncomm->elementtype = $elementtype;
		}

		if (property_exists($object, 'attachedfiles') && is_array($object->attachedfiles) && count($object->attachedfiles)>0) {
			$actioncomm->attachedfiles=$object->attachedfiles;
		}
		if (property_exists($object, 'sendtouserid') && is_array($object->sendtouserid) && count($object->sendtouserid)>0) {
			$actioncomm->userassigned=$object->sendtouserid;
		}

		$ret=$actioncomm->create($user);       // User creating action

		if ($ret > 0 && $conf->global->MAIN_COPY_FILE_IN_EVENT_AUTO)
		{
			if (is_array($object->attachedfiles) && array_key_exists('paths', $object->attachedfiles) && count($object->attachedfiles['paths'])>0) {
				foreach($object->attachedfiles['paths'] as $key=>$filespath) {
					$srcfile = $filespath;
					$destdir = $conf->agenda->dir_output . '/' . $ret;
					$destfile = $destdir . '/' . $object->attachedfiles['names'][$key];
					if (dol_mkdir($destdir) >= 0) {
						require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
						dol_copy($srcfile, $destfile);
					}
				}
			}
		}

		unset($object->actionmsg); unset($object->actionmsg2); unset($object->actiontypecode);	// When several action are called on same object, we must be sure to not reuse value of first action.

		if ($ret > 0)
		{
			$_SESSION['LAST_ACTION_CREATED'] = $ret;
			return 1;
		}
		else
		{
            $error ="Failed to insert event : ".$actioncomm->error." ".join(',', $actioncomm->errors);
            $this->error=$error;
            $this->errors=$actioncomm->errors;

            dol_syslog("interface_modAgenda_ActionsAuto.class.php: ".$this->error, LOG_ERR);
            return -1;
		}
    }
}
