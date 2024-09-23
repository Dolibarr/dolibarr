<?php
/* Copyright (C) 2010      Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2017 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014      Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2022      Ferran Marcet       <fmarcet@2byte.es>
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
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "core";
		$this->description = "Triggers of this module allows to manage workflows";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = self::VERSION_DOLIBARR;
		$this->picto = 'technic';
	}

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
		if (empty($conf->workflow) || empty($conf->workflow->enabled)) {
			return 0; // Module not active, we do nothing
		}

		$ret = 0;

		// Proposals to order
		if ($action == 'PROPAL_CLOSE_SIGNED') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (isModEnabled('commande') && !empty($conf->global->WORKFLOW_PROPAL_AUTOCREATE_ORDER)) {
				$object->fetchObjectLinked();
				if (!empty($object->linkedObjectsIds['commande'])) {
					if (empty($object->context['closedfromonlinesignature'])) {
						$langs->load("orders");
						setEventMessages($langs->trans("OrderExists"), null, 'warnings');
					}
					return $ret;
				} else {
					include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
					$newobject = new Commande($this->db);

					$newobject->context['createfrompropal'] = 'createfrompropal';
					$newobject->context['origin'] = $object->element;
					$newobject->context['origin_id'] = $object->id;

					$ret = $newobject->createFromProposal($object, $user);
					if ($ret < 0) {
						$this->error = $newobject->error;
						$this->errors[] = $newobject->error;
					}

					$object->clearObjectLinkedCache();

					return $ret;
				}
			}
		}

		// Order to invoice
		if ($action == 'ORDER_CLOSE') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (isModEnabled('facture') && !empty($conf->global->WORKFLOW_ORDER_AUTOCREATE_INVOICE)) {
				include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
				$newobject = new Facture($this->db);

				$newobject->context['createfromorder'] = 'createfromorder';
				$newobject->context['origin'] = $object->element;
				$newobject->context['origin_id'] = $object->id;

				$ret = $newobject->createFromOrder($object, $user);
				if ($ret < 0) {
					$this->error = $newobject->error;
					$this->errors[] = $newobject->error;
				} else {
					if (empty($object->fk_account) && !empty($object->thirdparty->fk_account) && !getDolGlobalInt('BANK_ASK_PAYMENT_BANK_DURING_ORDER')) {
						$res = $newobject->setBankAccount($object->thirdparty->fk_account, true, $user);
						if ($ret < 0) {
							$this->error = $newobject->error;
							$this->errors[] = $newobject->error;
						}
					}
				}

				$object->clearObjectLinkedCache();

				return $ret;
			}
		}

		// Order classify billed proposal
		if ($action == 'ORDER_CLASSIFY_BILLED') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if (isModEnabled("propal") && !empty($conf->workflow->enabled) && !empty($conf->global->WORKFLOW_ORDER_CLASSIFY_BILLED_PROPAL)) {
				$object->fetchObjectLinked('', 'propal', $object->id, $object->element);
				if (!empty($object->linkedObjects)) {
					$totalonlinkedelements = 0;
					foreach ($object->linkedObjects['propal'] as $element) {
						if ($element->statut == Propal::STATUS_SIGNED || $element->statut == Propal::STATUS_BILLED) {
							$totalonlinkedelements += $element->total_ht;
						}
					}
					dol_syslog("Amount of linked proposals = ".$totalonlinkedelements.", of order = ".$object->total_ht.", egality is ".($totalonlinkedelements == $object->total_ht));
					if ($this->shouldClassify($conf, $totalonlinkedelements, $object->total_ht)) {
						foreach ($object->linkedObjects['propal'] as $element) {
							$ret = $element->classifyBilled($user);
						}
					}
				}
				return $ret;
			}
		}

		// classify billed order & billed propososal
		if ($action == 'BILL_VALIDATE') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

			// First classify billed the order to allow the proposal classify process
			if (isModEnabled('commande') && !empty($conf->workflow->enabled) && !empty($conf->global->WORKFLOW_INVOICE_AMOUNT_CLASSIFY_BILLED_ORDER)) {
				$object->fetchObjectLinked('', 'commande', $object->id, $object->element);
				if (!empty($object->linkedObjects)) {
					$totalonlinkedelements = 0;
					foreach ($object->linkedObjects['commande'] as $element) {
						if ($element->statut == Commande::STATUS_VALIDATED || $element->statut == Commande::STATUS_SHIPMENTONPROCESS || $element->statut == Commande::STATUS_CLOSED) {
							$totalonlinkedelements += $element->total_ht;
						}
					}
					dol_syslog("Amount of linked orders = ".$totalonlinkedelements.", of invoice = ".$object->total_ht.", egality is ".($totalonlinkedelements == $object->total_ht));
					if ($this->shouldClassify($conf, $totalonlinkedelements, $object->total_ht)) {
						foreach ($object->linkedObjects['commande'] as $element) {
							$ret = $element->classifyBilled($user);
						}
					}
				}
			}

			// Second classify billed the proposal.
			if (isModEnabled("propal") && !empty($conf->workflow->enabled) && !empty($conf->global->WORKFLOW_INVOICE_CLASSIFY_BILLED_PROPAL)) {
				$object->fetchObjectLinked('', 'propal', $object->id, $object->element);
				if (!empty($object->linkedObjects)) {
					$totalonlinkedelements = 0;
					foreach ($object->linkedObjects['propal'] as $element) {
						if ($element->statut == Propal::STATUS_SIGNED || $element->statut == Propal::STATUS_BILLED) {
							$totalonlinkedelements += $element->total_ht;
						}
					}
					dol_syslog("Amount of linked proposals = ".$totalonlinkedelements.", of invoice = ".$object->total_ht.", egality is ".($totalonlinkedelements == $object->total_ht));
					if ($this->shouldClassify($conf, $totalonlinkedelements, $object->total_ht)) {
						foreach ($object->linkedObjects['propal'] as $element) {
							$ret = $element->classifyBilled($user);
						}
					}
				}
			}

			if (isModEnabled("expedition") && !empty($conf->workflow->enabled) && !empty($conf->global->WORKFLOW_SHIPPING_CLASSIFY_CLOSED_INVOICE)) {
				$object->fetchObjectLinked('', 'shipping', $object->id, $object->element);
				if (!empty($object->linkedObjects)) {
					$totalonlinkedelements = 0;
					foreach ($object->linkedObjects['shipping'] as $element) {
						if ($element->statut == Expedition::STATUS_VALIDATED) {
							$totalonlinkedelements += $element->total_ht;
						}
					}
					dol_syslog("Amount of linked shipment = ".$totalonlinkedelements.", of invoice = ".$object->total_ht.", egality is ".($totalonlinkedelements == $object->total_ht), LOG_DEBUG);
					if ($totalonlinkedelements == $object->total_ht) {
						foreach ($object->linkedObjects['shipping'] as $element) {
							$ret = $element->setClosed();
							if ($ret < 0) {
								return $ret;
							}
						}
					}
				}
			}

			return $ret;
		}

		// classify billed order & billed proposal
		if ($action == 'BILL_SUPPLIER_VALIDATE') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

			// Firstly, we set to purchase order to "Billed" if WORKFLOW_INVOICE_AMOUNT_CLASSIFY_BILLED_SUPPLIER_ORDER is set.
			// After we will set proposals
			if ((isModEnabled("supplier_order") || isModEnabled("supplier_invoice")) && !empty($conf->global->WORKFLOW_INVOICE_AMOUNT_CLASSIFY_BILLED_SUPPLIER_ORDER)) {
				$object->fetchObjectLinked('', 'order_supplier', $object->id, $object->element);
				if (!empty($object->linkedObjects)) {
					$totalonlinkedelements = 0;
					foreach ($object->linkedObjects['order_supplier'] as $element) {
						if ($element->statut == CommandeFournisseur::STATUS_ACCEPTED || $element->statut == CommandeFournisseur::STATUS_ORDERSENT || $element->statut == CommandeFournisseur::STATUS_RECEIVED_PARTIALLY || $element->statut == CommandeFournisseur::STATUS_RECEIVED_COMPLETELY) {
							$totalonlinkedelements += $element->total_ht;
						}
					}
					dol_syslog("Amount of linked orders = ".$totalonlinkedelements.", of invoice = ".$object->total_ht.", egality is ".($totalonlinkedelements == $object->total_ht));
					if ($this->shouldClassify($conf, $totalonlinkedelements, $object->total_ht)) {
						foreach ($object->linkedObjects['order_supplier'] as $element) {
							$ret = $element->classifyBilled($user);
							if ($ret < 0) {
								return $ret;
							}
						}
					}
				}
			}

			// Secondly, we set to linked Proposal to "Billed" if WORKFLOW_INVOICE_CLASSIFY_BILLED_SUPPLIER_PROPOSAL is set.
			if (isModEnabled('supplier_proposal') && !empty($conf->global->WORKFLOW_INVOICE_CLASSIFY_BILLED_SUPPLIER_PROPOSAL)) {
				$object->fetchObjectLinked('', 'supplier_proposal', $object->id, $object->element);
				if (!empty($object->linkedObjects)) {
					$totalonlinkedelements = 0;
					foreach ($object->linkedObjects['supplier_proposal'] as $element) {
						if ($element->statut == SupplierProposal::STATUS_SIGNED || $element->statut == SupplierProposal::STATUS_BILLED) {
							$totalonlinkedelements += $element->total_ht;
						}
					}
					dol_syslog("Amount of linked supplier proposals = ".$totalonlinkedelements.", of supplier invoice = ".$object->total_ht.", egality is ".($totalonlinkedelements == $object->total_ht));
					if ($this->shouldClassify($conf, $totalonlinkedelements, $object->total_ht)) {
						foreach ($object->linkedObjects['supplier_proposal'] as $element) {
							$ret = $element->classifyBilled($user);
							if ($ret < 0) {
								return $ret;
							}
						}
					}
				}
			}

			// Then set reception to "Billed" if WORKFLOW_RECEPTION_CLASSIFY_CLOSED_INVOICE is set
			if (isModEnabled("reception") && !empty($conf->workflow->enabled) && !empty($conf->global->WORKFLOW_RECEPTION_CLASSIFY_CLOSED_INVOICE)) {
				$object->fetchObjectLinked('', 'reception', $object->id, $object->element);
				if (!empty($object->linkedObjects)) {
					$totalonlinkedelements = 0;
					foreach ($object->linkedObjects['reception'] as $element) {
						if ($element->statut == Reception::STATUS_VALIDATED || $element->statut == Reception::STATUS_CLOSED) {
							$totalonlinkedelements += $element->total_ht;
						}
					}
					dol_syslog("Amount of linked reception = ".$totalonlinkedelements.", of invoice = ".$object->total_ht.", egality is ".($totalonlinkedelements == $object->total_ht), LOG_DEBUG);
					if ($totalonlinkedelements == $object->total_ht) {
						foreach ($object->linkedObjects['reception'] as $element) {
							$ret = $element->setBilled();
							if ($ret < 0) {
								return $ret;
							}
						}
					}
				}
			}

			return $ret;
		}

		// Invoice classify billed order
		if ($action == 'BILL_PAYED') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

			if (isModEnabled('commande') && !empty($conf->global->WORKFLOW_INVOICE_CLASSIFY_BILLED_ORDER)) {
				$object->fetchObjectLinked('', 'commande', $object->id, $object->element);
				if (!empty($object->linkedObjects)) {
					$totalonlinkedelements = 0;
					foreach ($object->linkedObjects['commande'] as $element) {
						if ($element->statut == Commande::STATUS_VALIDATED || $element->statut == Commande::STATUS_SHIPMENTONPROCESS || $element->statut == Commande::STATUS_CLOSED) {
							$totalonlinkedelements += $element->total_ht;
						}
					}
					dol_syslog("Amount of linked orders = ".$totalonlinkedelements.", of invoice = ".$object->total_ht.", egality is ".($totalonlinkedelements == $object->total_ht));
					if ($this->shouldClassify($conf, $totalonlinkedelements, $object->total_ht)) {
						foreach ($object->linkedObjects['commande'] as $element) {
							$ret = $element->classifyBilled($user);
						}
					}
				}
				return $ret;
			}
		}

		// If we validate or close a shipment
		if (($action == 'SHIPPING_VALIDATE') || ($action == 'SHIPPING_CLOSED')) {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

			if (isModEnabled('commande') && isModEnabled("expedition") && !empty($conf->workflow->enabled) &&
				(
					(!empty($conf->global->WORKFLOW_ORDER_CLASSIFY_SHIPPED_SHIPPING) && ($action == 'SHIPPING_VALIDATE')) ||
					(!empty($conf->global->WORKFLOW_ORDER_CLASSIFY_SHIPPED_SHIPPING_CLOSED) && ($action == 'SHIPPING_CLOSED'))
				)
			) {
				$qtyshipped = array();
				$qtyordred = array();
				require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

				// Find all shipments on order origin
				$order = new Commande($this->db);
				$ret = $order->fetch($object->origin_id);
				if ($ret < 0) {
					$this->error = $order->error;
					$this->errors = $order->errors;
					return $ret;
				}
				$ret = $order->fetchObjectLinked($order->id, 'commande', null, 'shipping');
				if ($ret < 0) {
					$this->error = $order->error;
					$this->errors = $order->errors;
					return $ret;
				}
				//Build array of quantity shipped by product for an order
				if (is_array($order->linkedObjects) && count($order->linkedObjects) > 0) {
					foreach ($order->linkedObjects as $type => $shipping_array) {
						if ($type == 'shipping' && is_array($shipping_array) && count($shipping_array) > 0) {
							foreach ($shipping_array as $shipping) {
								if (is_array($shipping->lines) && count($shipping->lines) > 0) {
									foreach ($shipping->lines as $shippingline) {
										$qtyshipped[$shippingline->fk_product] += $shippingline->qty;
									}
								}
							}
						}
					}
				}

				//Build array of quantity ordered to be shipped
				if (is_array($order->lines) && count($order->lines) > 0) {
					foreach ($order->lines as $orderline) {
						// Exclude lines not qualified for shipment, similar code is found into calcAndSetStatusDispatch() for vendors
						if (empty($conf->global->STOCK_SUPPORTS_SERVICES) && $orderline->product_type > 0) {
							continue;
						}
						$qtyordred[$orderline->fk_product] += $orderline->qty;
					}
				}
				//dol_syslog(var_export($qtyordred,true),LOG_DEBUG);
				//dol_syslog(var_export($qtyshipped,true),LOG_DEBUG);
				//Compare array
				$diff_array = array_diff_assoc($qtyordred, $qtyshipped);
				if (count($diff_array) == 0) {
					//No diff => mean everythings is shipped
					$ret = $order->setStatut(Commande::STATUS_CLOSED, $object->origin_id, $object->origin, 'ORDER_CLOSE');
					if ($ret < 0) {
						$this->error = $order->error;
						$this->errors = $order->errors;
						return $ret;
					}
				}
			}
		}

		// If we validate or close a shipment
		if (($action == 'RECEPTION_VALIDATE') || ($action == 'RECEPTION_CLOSED')) {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

			if ((isModEnabled("fournisseur") || isModEnabled("supplier_order")) && isModEnabled("reception") && !empty($conf->workflow->enabled) &&
				(
					(!empty($conf->global->WORKFLOW_ORDER_CLASSIFY_RECEIVED_RECEPTION) && ($action == 'RECEPTION_VALIDATE')) ||
					(!empty($conf->global->WORKFLOW_ORDER_CLASSIFY_RECEIVED_RECEPTION_CLOSED) && ($action == 'RECEPTION_CLOSED'))
				)
			) {
				$qtyshipped = array();
				$qtyordred = array();
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

				// Find all reception on purchase order origin
				$order = new CommandeFournisseur($this->db);
				$ret = $order->fetch($object->origin_id);
				if ($ret < 0) {
					$this->error = $order->error;
					$this->errors = $order->errors;
					return $ret;
				}
				$ret = $order->fetchObjectLinked($order->id, $order->element, null, 'reception');
				if ($ret < 0) {
					$this->error = $order->error;
					$this->errors = $order->errors;
					return $ret;
				}
				//Build array of quantity received by product for a purchase order
				if (is_array($order->linkedObjects) && count($order->linkedObjects) > 0) {
					foreach ($order->linkedObjects as $type => $shipping_array) {
						if ($type == 'reception' && is_array($shipping_array) && count($shipping_array) > 0) {
							foreach ($shipping_array as $shipping) {
								if (is_array($shipping->lines) && count($shipping->lines) > 0) {
									foreach ($shipping->lines as $shippingline) {
										$qtyshipped[$shippingline->fk_product] += $shippingline->qty;
									}
								}
							}
						}
					}
				}

				//Build array of quantity ordered to be received
				if (is_array($order->lines) && count($order->lines) > 0) {
					foreach ($order->lines as $orderline) {
						// Exclude lines not qualified for shipment, similar code is found into calcAndSetStatusDispatch() for vendors
						if (empty($conf->global->STOCK_SUPPORTS_SERVICES) && $orderline->product_type > 0) {
							continue;
						}
						$qtyordred[$orderline->fk_product] += $orderline->qty;
					}
				}
				//dol_syslog(var_export($qtyordred,true),LOG_DEBUG);
				//dol_syslog(var_export($qtyshipped,true),LOG_DEBUG);
				//Compare array
				$diff_array = array_diff_assoc($qtyordred, $qtyshipped);
				if (count($diff_array) == 0) {
					//No diff => mean everythings is received
					$ret = $order->setStatut(CommandeFournisseur::STATUS_RECEIVED_COMPLETELY, null, null, 'SUPPLIER_ORDER_CLOSE');
					if ($ret < 0) {
						$this->error = $order->error;
						$this->errors = $order->errors;
						return $ret;
					}
				}
			}
		}

		if ($action == 'TICKET_CREATE') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			// Auto link ticket to contract
			if (isModEnabled('contract') && isModEnabled('ticket') && isModEnabled('workflow') && getDolGlobalString('WORKFLOW_TICKET_LINK_CONTRACT') && getDolGlobalString('TICKET_PRODUCT_CATEGORY') && !empty($object->fk_soc)) {
				$societe = new Societe($this->db);
				$company_ids = (!getDolGlobalString('WORKFLOW_TICKET_USE_PARENT_COMPANY_CONTRACTS')) ? [$object->fk_soc] : $societe->getParentsForCompany($object->fk_soc, [$object->fk_soc]);

				$contrat = new Contrat($this->db);
				$number_contracts_found = 0;
				foreach ($company_ids as $company_id) {
					$contrat->socid = $company_id;
					$list = $contrat->getListOfContracts('all', array(Contrat::STATUS_DRAFT, Contrat::STATUS_VALIDATED), array(getDolGlobalString('TICKET_PRODUCT_CATEGORY')), array(ContratLigne::STATUS_INITIAL, ContratLigne::STATUS_OPEN));
					if (!is_array($list) || empty($list)) {
						continue;
					}
					$number_contracts_found = count($list);

					foreach ($list as $linked_contract) {
						$object->setContract($linked_contract->id);
						// don't set '$contractid' so it is not used when creating an intervention.
					}

					if ($number_contracts_found > 1 && !defined('NOLOGIN')) {
						setEventMessage($langs->trans('TicketManyContractsLinked'), 'warnings');
					}
				}
				if ($number_contracts_found == 0 && !defined('NOLOGIN')) {
					setEventMessage($langs->trans('TicketNoContractFoundToLink'), 'mesgs');
				}
			}
			// Automatically create intervention
			if (isModEnabled('ficheinter') && isModEnabled('ticket') && isModEnabled('workflow') && getDolGlobalString('WORKFLOW_TICKET_CREATE_INTERVENTION')) {
				$fichinter = new Fichinter($this->db);
				$fichinter->socid = (int) $object->fk_soc;
				$fichinter->fk_project = (int) $object->fk_project;
				$fichinter->fk_contrat = (int) $object->fk_contract;
				$fichinter->author = $user->id;
				$fichinter->model_pdf = getDolGlobalString('FICHEINTER_ADDON_PDF', 'soleil');
				$fichinter->origin = $object->element;
				$fichinter->origin_id = $object->id;

				// Extrafields
				$extrafields = new ExtraFields($this->db);
				$extrafields->fetch_name_optionals_label($fichinter->table_element);
				$array_options = $extrafields->getOptionalsFromPost($fichinter->table_element);
				$fichinter->array_options = $array_options;

				$id = $fichinter->create($user);
				if ($id <= 0) {
					setEventMessages($fichinter->error, null, 'errors');
				}
			}
		}
		return 0;
	}

	/**
	 * @param Object $conf                  Dolibarr settings object
	 * @param float $totalonlinkedelements  Sum of total amounts (excl VAT) of
	 *                                      invoices linked to $object
	 * @param float $object_total_ht        The total amount (excl VAT) of the object
	 *                                      (an order, a proposal, a bill, etc.)
	 * @return bool  True if the amounts are equal (rounded on total amount)
	 *               True if the module is configured to skip the amount equality check
	 *               False otherwise.
	 */
	private function shouldClassify($conf, $totalonlinkedelements, $object_total_ht)
	{
		// if the configuration allows unmatching amounts, allow classification anyway
		if (!empty($conf->global->WORKFLOW_CLASSIFY_IF_AMOUNTS_ARE_DIFFERENTS)) {
			return true;
		}
		// if the amount are same, allow classification, else deny
		return (price2num($totalonlinkedelements, 'MT') == price2num($object_total_ht, 'MT'));
	}
}
