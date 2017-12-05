<?php
/* Copyright (C) 2017 ATM Consulting <contact@atm-consulting.fr>
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
 *	\file       htdocs/core/triggers/interface_50_modBlockedlog_ActionsBlockedLog.class.php
 *  \ingroup    system
 *  \brief      Trigger file for blockedlog module
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';


/**
 *  Class of triggered functions for agenda module
 */
class InterfaceActionsBlockedLog extends DolibarrTriggers
{
	public $family = 'system';
	public $description = "Triggers of this module add action for BlockedLog module.";
	public $version = self::VERSION_DOLIBARR;
	public $picto = 'technic';

	/**
	 * Function called on Dolibarrr payment or invoice event.
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
        if (empty($conf->blockedlog->enabled)) return 0;     // Module not active, we do nothing

		// Test if event/record is qualified
		$listofqualifiedelement = array('payment', 'facture');
		if (! in_array($object->element, $listofqualifiedelement)) return 1;

		dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

		// Event/record is qualified
		if ($action==='BILL_VALIDATE' || $action === 'BILL_PAYED' || $action==='BILL_UNPAYED' || $action==='BILL_DELETE'
			|| $action === 'BILL_SENTBYMAIL' || $action === 'DOC_DOWNLOAD' || $action === 'DOC_PREVIEW'
			|| $action === 'BILL_SUPPLIER_PAYED')
		{
			$amounts=  (double) $object->total_ttc;
		}
		else if ($action === 'PAYMENT_CUSTOMER_CREATE' || $action === 'PAYMENT_SUPPLIER_CREATE'
			|| $action === 'PAYMENT_CUSTOMER_DELETE' || $action === 'PAYMENT_SUPPLIER_DELETE')			// 'PAYMENT_ADD_TO_BANK'
		{
			$amounts = 0;
			if(!empty($object->amounts)) {
				foreach($object->amounts as $amount) {
					$amounts+= price2num($amount);
				}
			}
		}
		else if (strpos($action,'PAYMENT')!==false && ! in_array($action, array('PAYMENT_ADD_TO_BANK'))) {
			$amounts= (double) $object->amount;
		}
		else {
			return 0; // not implemented action log
		}


		$b=new BlockedLog($this->db);
		$b->setObjectData($object, $action, $amounts);		// Set field date_object, ref_object, fk_object, element, object_data

		$res = $b->create($user);

		if ($res<0)
		{
			setEventMessage($b->error,'errors');
			return -1;
		}
		else
		{
			return 1;
		}
    }

}
