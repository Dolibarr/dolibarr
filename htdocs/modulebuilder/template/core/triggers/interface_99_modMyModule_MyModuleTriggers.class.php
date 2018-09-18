<?php
/* Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modMyModule_MyModuleTriggers.class.php
 * \ingroup mymodule
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modMyModule_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for MyModule module
 */
class InterfaceMyModuleTriggers extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "MyModule triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'mymodule@mymodule';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
        if (empty($conf->mymodule->enabled)) return 0;     // Module not active, we do nothing

	    // Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action

        switch ($action) {

            // Users
		    //case 'USER_CREATE':
		    //case 'USER_MODIFY':
		    //case 'USER_NEW_PASSWORD':
		    //case 'USER_ENABLEDISABLE':
		    //case 'USER_DELETE':
		    //case 'USER_SETINGROUP':
		    //case 'USER_REMOVEFROMGROUP':

		    // Actions
		    //case 'ACTION_MODIFY':
		    //case 'ACTION_CREATE':
		    //case 'ACTION_DELETE':

		    // Groups
		    //case 'GROUP_CREATE':
		    //case 'GROUP_MODIFY':
		    //case 'GROUP_DELETE':

		    // Companies
		    //case 'COMPANY_CREATE':
		    //case 'COMPANY_MODIFY':
		    //case 'COMPANY_DELETE':

		    // Contacts
		    //case 'CONTACT_CREATE':
		    //case 'CONTACT_MODIFY':
		    //case 'CONTACT_DELETE':
		    //case 'CONTACT_ENABLEDISABLE':

		    // Products
		    //case 'PRODUCT_CREATE':
		    //case 'PRODUCT_MODIFY':
		    //case 'PRODUCT_DELETE':
		    //case 'PRODUCT_PRICE_MODIFY':
		    //case 'PRODUCT_SET_MULTILANGS':
		    //case 'PRODUCT_DEL_MULTILANGS':

		    //Stock mouvement
		    //case 'STOCK_MOVEMENT':

		    //MYECMDIR
		    //case 'MYECMDIR_CREATE':
		    //case 'MYECMDIR_MODIFY':
		    //case 'MYECMDIR_DELETE':

		    // Customer orders
		    //case 'ORDER_CREATE':
		    //case 'ORDER_MODIFY':
		    //case 'ORDER_VALIDATE':
		    //case 'ORDER_DELETE':
		    //case 'ORDER_CANCEL':
		    //case 'ORDER_SENTBYMAIL':
		    //case 'ORDER_CLASSIFY_BILLED':
		    //case 'ORDER_SETDRAFT':
		    //case 'LINEORDER_INSERT':
		    //case 'LINEORDER_UPDATE':
		    //case 'LINEORDER_DELETE':

		    // Supplier orders
		    //case 'ORDER_SUPPLIER_CREATE':
		    //case 'ORDER_SUPPLIER_MODIFY':
		    //case 'ORDER_SUPPLIER_VALIDATE':
		    //case 'ORDER_SUPPLIER_DELETE':
		    //case 'ORDER_SUPPLIER_APPROVE':
		    //case 'ORDER_SUPPLIER_REFUSE':
		    //case 'ORDER_SUPPLIER_CANCEL':
		    //case 'ORDER_SUPPLIER_SENTBYMAIL':
		    //case 'ORDER_SUPPLIER_DISPATCH':
		    //case 'LINEORDER_SUPPLIER_DISPATCH':
		    //case 'LINEORDER_SUPPLIER_CREATE':
		    //case 'LINEORDER_SUPPLIER_UPDATE':
		    //case 'LINEORDER_SUPPLIER_DELETE':

		    // Proposals
		    //case 'PROPAL_CREATE':
		    //case 'PROPAL_MODIFY':
		    //case 'PROPAL_VALIDATE':
		    //case 'PROPAL_SENTBYMAIL':
		    //case 'PROPAL_CLOSE_SIGNED':
		    //case 'PROPAL_CLOSE_REFUSED':
		    //case 'PROPAL_DELETE':
		    //case 'LINEPROPAL_INSERT':
		    //case 'LINEPROPAL_UPDATE':
		    //case 'LINEPROPAL_DELETE':

		    // SupplierProposal
		    //case 'SUPPLIER_PROPOSAL_CREATE':
		    //case 'SUPPLIER_PROPOSAL_MODIFY':
		    //case 'SUPPLIER_PROPOSAL_VALIDATE':
		    //case 'SUPPLIER_PROPOSAL_SENTBYMAIL':
		    //case 'SUPPLIER_PROPOSAL_CLOSE_SIGNED':
		    //case 'SUPPLIER_PROPOSAL_CLOSE_REFUSED':
		    //case 'SUPPLIER_PROPOSAL_DELETE':
		    //case 'LINESUPPLIER_PROPOSAL_INSERT':
		    //case 'LINESUPPLIER_PROPOSAL_UPDATE':
		    //case 'LINESUPPLIER_PROPOSAL_DELETE':

		    // Contracts
		    //case 'CONTRACT_CREATE':
		    //case 'CONTRACT_MODIFY':
		    //case 'CONTRACT_ACTIVATE':
		    //case 'CONTRACT_CANCEL':
		    //case 'CONTRACT_CLOSE':
		    //case 'CONTRACT_DELETE':
		    //case 'LINECONTRACT_INSERT':
		    //case 'LINECONTRACT_UPDATE':
		    //case 'LINECONTRACT_DELETE':

		    // Bills
		    //case 'BILL_CREATE':
		    //case 'BILL_MODIFY':
		    //case 'BILL_VALIDATE':
		    //case 'BILL_UNVALIDATE':
		    //case 'BILL_SENTBYMAIL':
		    //case 'BILL_CANCEL':
		    //case 'BILL_DELETE':
		    //case 'BILL_PAYED':
		    //case 'LINEBILL_INSERT':
		    //case 'LINEBILL_UPDATE':
		    //case 'LINEBILL_DELETE':

		    //Supplier Bill
		    //case 'BILL_SUPPLIER_CREATE':
		    //case 'BILL_SUPPLIER_UPDATE':
		    //case 'BILL_SUPPLIER_DELETE':
		    //case 'BILL_SUPPLIER_PAYED':
		    //case 'BILL_SUPPLIER_UNPAYED':
		    //case 'BILL_SUPPLIER_VALIDATE':
		    //case 'BILL_SUPPLIER_UNVALIDATE':
		    //case 'LINEBILL_SUPPLIER_CREATE':
		    //case 'LINEBILL_SUPPLIER_UPDATE':
		    //case 'LINEBILL_SUPPLIER_DELETE':

		    // Payments
		    //case 'PAYMENT_CUSTOMER_CREATE':
		    //case 'PAYMENT_SUPPLIER_CREATE':
		    //case 'PAYMENT_ADD_TO_BANK':
		    //case 'PAYMENT_DELETE':

		    // Online
		    //case 'PAYMENT_PAYBOX_OK':
		    //case 'PAYMENT_PAYPAL_OK':
		    //case 'PAYMENT_STRIPE_OK':

		    // Donation
		    //case 'DON_CREATE':
		    //case 'DON_UPDATE':
		    //case 'DON_DELETE':

		    // Interventions
		    //case 'FICHINTER_CREATE':
		    //case 'FICHINTER_MODIFY':
		    //case 'FICHINTER_VALIDATE':
		    //case 'FICHINTER_DELETE':
		    //case 'LINEFICHINTER_CREATE':
		    //case 'LINEFICHINTER_UPDATE':
		    //case 'LINEFICHINTER_DELETE':

		    // Members
		    //case 'MEMBER_CREATE':
		    //case 'MEMBER_VALIDATE':
		    //case 'MEMBER_SUBSCRIPTION':
		    //case 'MEMBER_MODIFY':
		    //case 'MEMBER_NEW_PASSWORD':
		    //case 'MEMBER_RESILIATE':
		    //case 'MEMBER_DELETE':

		    // Categories
		    //case 'CATEGORY_CREATE':
		    //case 'CATEGORY_MODIFY':
		    //case 'CATEGORY_DELETE':
		    //case 'CATEGORY_SET_MULTILANGS':

		    // Projects
		    //case 'PROJECT_CREATE':
		    //case 'PROJECT_MODIFY':
		    //case 'PROJECT_DELETE':

		    // Project tasks
		    //case 'TASK_CREATE':
		    //case 'TASK_MODIFY':
		    //case 'TASK_DELETE':

		    // Task time spent
		    //case 'TASK_TIMESPENT_CREATE':
		    //case 'TASK_TIMESPENT_MODIFY':
		    //case 'TASK_TIMESPENT_DELETE':

		    // Shipping
		    //case 'SHIPPING_CREATE':
		    //case 'SHIPPING_MODIFY':
		    //case 'SHIPPING_VALIDATE':
		    //case 'SHIPPING_SENTBYMAIL':
		    //case 'SHIPPING_BILLED':
		    //case 'SHIPPING_CLOSED':
		    //case 'SHIPPING_REOPEN':
			//case 'SHIPPING_DELETE':
			//	break;
			default:
		        dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
		        break;
		    }

		return 0;
	}
}
