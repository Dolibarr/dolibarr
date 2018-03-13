<?php
/*
 * Copyright (C) 2018  ptibogxiv	<support@ptibogxiv.net>
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
 *  \file       htdocs/core/triggers/interface_50_modStripe_Stripe.class.php
 *  \ingroup    core
 *  \brief      Fichier
 *  \remarks    Son propre fichier d'actions peut etre cree par recopie de celui-ci:
 *              - Le nom du fichier doit etre: interface_99_modMymodule_Mytrigger.class.php
 *                                           ou: interface_99_all_Mytrigger.class.php
 *              - Le fichier doit rester stocke dans core/triggers
 *              - Le nom de la classe doit etre InterfaceMytrigger
 *              - Le nom de la propriete name doit etre Mytrigger
 */
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
dol_include_once('/stripe/class/stripe.class.php');
$path=dirname(__FILE__).'/';
/**
 *  Class of triggers for stripe module
 */
class InterfaceStripe
{
    public $db;

    /**
     *   Constructor
     *
     *   @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
	      $this->family = 'Stripeconnect';
        $this->description = "Triggers of the module Stripeconnect";
        $this->version = 'dolibarr'; // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'stripe@stripe';
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
	 * Trigger version
	 *
	 * @return string Version of trigger file
	 */
	public function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') {
			return $langs->trans("Development");
		} elseif ($this->version == 'experimental') {
			return $langs->trans("Experimental");
		} elseif ($this->version == 'dolibarr') {
			return DOL_VERSION;
		} elseif ($this->version) {
			return $this->version;
		} else {
			return $langs->trans("Unknown");
		}
	}

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param 	string 			$action 	Event action code
	 * @param 	CommonObject 	$object 	Object
	 * @param 	User 			$user 		Object user
	 * @param 	Translate 		$langs 		Object langs
	 * @param 	Conf 			$conf 		Object conf
	 * @return 	int              			<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action
		global $langs, $db, $conf;
		$langs->load("members");
		$langs->load("users");
		$langs->load("mails");
		$langs->load('other');

		$ok = 0;
		$stripe = new Stripe($db);
		if (empty($conf->stripe->enabled)) return 0;

		if (empty($conf->global->STRIPE_LIVE) || GETPOST('forcesandbox', 'alpha'))
		{
			$service = 'StripeTest';
		}
		else
		{
			$service = 'StripeLive';
		}

		if ($action == 'COMPANY_MODIFY') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
			if ($stripe->getStripeAccount($service) && $object->client != 0) {
				$cu = $stripe->customerStripe($object->id, $stripe->getStripeAccount($service));
				if ($cu) {
					if ($conf->entity == '1') {
						$customer = \Stripe\Customer::retrieve("$cu->id");
					} else {
						$customer = \Stripe\Customer::retrieve("$cu->id", array(
						"stripe_account" => $stripe->getStripeAccount($service)
						));
					}
					if (! empty($object->email)) {
						$customer->email = "$object->email";
					}
					$customer->description = "$object->name";
					$customer->save();
				}
			}
		} elseif ($action == 'COMPANY_DELETE') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
			$cu = $stripe->customerStripe($object->id, $stripe->getStripeAccount($service));
			if ($cu) {
				if ($conf->entity == 1) {
					$customer = \Stripe\Customer::retrieve("$cu->id");
				} else {
					$customer = \Stripe\Customer::retrieve("$cu->id", array(
					"stripe_account" => $stripe->getStripeAccount($service)
					));
				}
				$customer->delete();
			}
		}

		return $ok;
	}
}
