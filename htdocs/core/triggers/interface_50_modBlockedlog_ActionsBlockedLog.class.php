<?php
/* Copyright (C) 2017       ATM Consulting          <contact@atm-consulting.fr>
 * Copyright (C) 2017-2018  Laurent Destailleur	    <eldy@users.sourceforge.net>
 * Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/triggers/interface_50_modBlockedlog_ActionsBlockedLog.class.php
 *  \ingroup    system
 *  \brief      Trigger file for blockedlog module
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

/**
 *  Class of triggered functions for agenda module
 */
class InterfaceActionsBlockedLog extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "system";
		$this->description = "Triggers of this module add action for BlockedLog module (Module of unalterable logs).";
		$this->version = self::VERSIONS['prod'];
		$this->picto = 'technic';
	}

	/**
	 * Function called on Dolibarr payment or invoice event.
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param Conf		    $conf       Object conf
	 * @return int         				Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		global $mysoc;

		if (!isModEnabled('blockedlog')) {
			return 0; // Module not active, we do nothing
		}

		// List of mandatory logged actions
		$listofqualifiedelement = array('invoice', 'facture', 'don', 'payment', 'payment_donation', 'subscription', 'cashcontrol');

		// Add custom actions to log
		if (getDolGlobalString('BLOCKEDLOG_ADD_ACTIONS_SUPPORTED')) {
			$listofqualifiedelement = array_merge($listofqualifiedelement, explode(',', getDolGlobalString('BLOCKEDLOG_ADD_ACTIONS_SUPPORTED')));
		}

		// Test if event/record is qualified
		// If custom actions are not set or if action not into custom actions, we can exclude action if object->element is not valid
		if (!is_object($object) || !property_exists($object, 'element') || !in_array($object->element, $listofqualifiedelement)) {
			return 1;
		}
		/** @var Facture|Don|Paiement|PaymentDonation|Subscription|PaymentVarious|CashControl $object */
		dol_syslog("Trigger '".$this->name."' for action '".$action."' launched by ".__FILE__.". id=".$object->id);

		require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
		$b = new BlockedLog($this->db);
		$b->loadTrackedEvents();			// Get the list of tracked events into $b->trackedevents

		// Tracked or controlled events
		if (!in_array($action, array_keys($b->trackedevents)) && !in_array($action, array_keys($b->controlledevents))) {
			return 0;
		}

		// If we are here, we are on an action code that will have a control or will generate a record in blockedlog database.

		if ($action === 'PAYMENT_CUSTOMER_CREATE' && $object->element == 'payment') {
			include_once DOL_DOCUMENT_ROOT.'/blockedlog/lib/blockedlog.lib.php';
			if (isALNERunningVersion() && $mysoc->country_code == 'FR') {
				if (empty($object->paiementcode) && !empty($object->paiementid)) {
					$object->paiementcode = dol_getIdFromCode($this->db, $object->paiementid, 'c_paiement', 'id', 'code', 1);
				}

				if (!in_array($object->paiementcode, array('LIQ', 'CB', 'CHQ'))) {
					// Check that invoice of payment is not from a POS module. Refuse if yes
					$invoiceids = array();
					if (is_array($object->amounts)) {
						foreach ($object->amounts as $objid => $amount) {
							$invoiceids[] = $objid;
						}
					}
					// Test there is not invoices with id in $invoiceids and with a module_source that is not empty
					$tmpinvoice = new Facture($this->db);
					foreach ($invoiceids as $invoiceid) {
						$tmpinvoice->id = 0;
						$tmpinvoice->fetch($invoiceid);
						if ($tmpinvoice->id > 0 && $tmpinvoice->module_source == 'takepos') {		// @phpstan-ignore-line PHP think tmpinvoice->id is always 0
							$this->errors[] = 'The payment mode '.$object->paiementcode.' is not available in this version for payment of invoices generated from '.$tmpinvoice->module_source;
							return -1;
						}
					}
				}
			}
		}

		// Protect against modification of data that should be immutable on a validated invoice
		if ($action === 'BILL_MODIFY' && !empty($object->oldcopy) && in_array($object->element, array('invoice', 'facture')) && $object->status != 0) {
			if ($object->oldcopy->ref != $object->ref) {
				$this->errors[] = 'Modifying the property Ref of a non draft invoice is not allowed';
				return -2;
			}
			if (($object->oldcopy->total_ht != $object->total_ht) || ($object->oldcopy->total_tva != $object->total_tva) || ($object->oldcopy->total_ttc != $object->total_ttc)
				|| ($object->oldcopy->date != $object->date) || ($object->oldcopy->revenuestamp != $object->revenuestamp)
				|| ($object->oldcopy->thirdparty->idprof1 != $object->thirdparty->idprof1)	// Siren
				|| ($object->oldcopy->thirdparty->idprof2 != $object->thirdparty->idprof2)	// Siret
				|| ($object->oldcopy->thirdparty->tva_intra != $object->thirdparty->tva_intra)
				) {
				$this->errors[] = 'You try to modify a property that is locked once the invoice has been validated (total, revenu stamp, professional id).';
				return -2;
			}
			if ($object->oldcopy->lines != $object->lines) {
				$this->errors[] = 'Modifying the lines of invoice is not allowed';
				return -2;
			}
		}

		// Event/record is qualified
		$qualified = 0;
		$amounts_taxexcl = null;
		$amounts = 0;
		if ($action === 'BILL_VALIDATE' || (($action === 'BILL_DELETE' || $action === 'BILL_SENTBYMAIL') && ($object->statut != 0 || $object->status != 0))
			|| $action === 'BILL_SUPPLIER_VALIDATE' || (($action === 'BILL_SUPPLIER_DELETE' || $action === 'BILL_SUPPLIER_SENTBYMAIL') && ($object->statut != 0 || $object->status != 0))
			|| $action === 'MEMBER_SUBSCRIPTION_CREATE' || $action === 'MEMBER_SUBSCRIPTION_MODIFY' || $action === 'MEMBER_SUBSCRIPTION_DELETE'
			|| $action === 'DON_VALIDATE' || (($action === 'DON_MODIFY' || $action === 'DON_DELETE') && ($object->statut != 0 || $object->status != 0))
			|| $action === 'CASHCONTROL_CLOSE'
			|| (in_array($object->element, array('facture', 'supplier_invoice')) && $action === 'DOC_PREVIEW' && ($object->statut != 0 || $object->status != 0 || $object->module_source != ''))
			|| (in_array($object->element, array('facture', 'supplier_invoice')) && $action === 'DOC_DOWNLOAD' && ($object->statut != 0 || $object->status != 0 || $object->module_source != ''))
			|| (getDolGlobalString('BLOCKEDLOG_ADD_ACTIONS_SUPPORTED') && in_array($action, explode(',', getDolGlobalString('BLOCKEDLOG_ADD_ACTIONS_SUPPORTED'))))
		) {
			$qualified++;

			if (in_array($action, array(
				'MEMBER_SUBSCRIPTION_CREATE', 'MEMBER_SUBSCRIPTION_MODIFY', 'MEMBER_SUBSCRIPTION_DELETE',
				'DON_VALIDATE', 'DON_MODIFY', 'DON_DELETE'))) {
				/** @var Don|Subscription $object */
				$amounts = (float) $object->amount;
			} elseif ($action == 'CASHCONTROL_CLOSE') {
				/** @var CashControl $object */
				$amounts = (float) $object->cash + (float) $object->cheque + (float) $object->card;
			} else {
				if (property_exists($object, 'total_ht')) {
					$amounts_taxexcl = (float) $object->total_ht;
				}
				if (property_exists($object, 'total_ttc')) {
					$amounts = (float) $object->total_ttc;
				}
			}
		}
		if ($action === 'PAYMENT_CUSTOMER_CREATE' || $action === 'PAYMENT_SUPPLIER_CREATE' || $action === 'DONATION_PAYMENT_CREATE'
			|| $action === 'PAYMENT_CUSTOMER_DELETE' || $action === 'PAYMENT_SUPPLIER_DELETE' || $action === 'DONATION_PAYMENT_DELETE') {
			$qualified++;
			if (!empty($object->amounts)) {
				foreach ($object->amounts as $amount) {
					$amounts += (float) $amount;
				}
			} elseif (!empty($object->amount)) {
				$amounts = $object->amount;
			}
		} elseif (strpos($action, 'PAYMENT') !== false && !in_array($action, array('PAYMENT_ADD_TO_BANK'))) {
			$qualified++;
			$amounts = (float) $object->amount;
		}

		// Another protection.
		// May be used when event is DOC_DOWNLOAD or DOC_PREVIEW and element is not an invoice
		if (!$qualified) {
			return 0; // not implemented action log
		}

		// Set field date_object, ref_object, fk_object, element, object_data
		$result = $b->setObjectData($object, $action, $amounts, $user, $amounts_taxexcl);

		if ($result < 0) {
			$this->setErrorsFromObject($b);
			return -1;
		}

		// Insert event in unalterable log. We are in a trigger so inside a global db transaction.
		$res = $b->create($user);

		if ($res < 0) {
			$this->setErrorsFromObject($b);
			return -1;
		} else {
			return 1;
		}
	}
}
