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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/core/triggers/interface_80_modStripe_Stripe.class.php
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


/**
 *  Class of triggers for stripe module
 */
class InterfaceStripe extends DolibarrTriggers
{
    /**
     * @var DoliDB Database handler.
     */
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
	    $this->family = 'stripe';
        $this->description = "Triggers of the module Stripe";
        $this->version = 'dolibarr'; // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'stripe';
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
	 * @param 	string 			$action 	Event action code
	 * @param 	CommonObject 	$object 	Object
	 * @param 	User 			$user 		Object user
	 * @param 	Translate 		$langs 		Object langs
	 * @param 	Conf 			$conf 		Object conf
	 * @return 	int              			<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		// Put here code you want to execute when a Dolibarr business event occurs.
		// Data and type of action are stored into $object and $action
		global $langs, $db, $conf;

		require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';
		$stripe = new Stripe($db);

		if (empty($conf->stripe->enabled)) return 0;

		$ok = 1;

		$service = 'StripeTest';
		$servicestatus = 0;
		if (!empty($conf->global->STRIPE_LIVE) && !GETPOST('forcesandbox', 'alpha'))
		{
			$service = 'StripeLive';
			$servicestatus = 1;
		}

		// If customer is linked to Stripe, we update/delete Stripe too
		if ($action == 'COMPANY_MODIFY') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

			$stripeacc = $stripe->getStripeAccount($service); // No need of network access for this. May return '' if no Oauth defined.

			if ($object->client != 0) {
				$customer = $stripe->customerStripe($object, $stripeacc, $servicestatus); // This make a network request
				if ($customer)
				{
					$namecleaned = $object->name ? $object->name : null;
					$vatcleaned = $object->tva_intra ? $object->tva_intra : null; // Example of valid numbers are 'FR12345678901' or 'FR12345678902'
					$desccleaned = $object->name_alias ? $object->name_alias : null;
					$taxexemptcleaned = $object->tva_assuj ? 'none' : 'exempt';
					$langcleaned = $object->default_lang ? array(substr($object->default_lang, 0, 2)) : null;
					/*$taxinfo = array('type'=>'vat');
					if ($vatcleaned)
					{
						$taxinfo["tax_id"] = $vatcleaned;
					}
					// We force data to "null" if not defined as expected by Stripe
					if (empty($vatcleaned)) $taxinfo=null;*/

					// Detect if we change a Stripe info (email, description, vat id)
					$changerequested = 0;
					if (!empty($object->email) && $object->email != $customer->email) $changerequested++;
					/* if ($namecleaned != $customer->description) $changerequested++;
					if (! isset($customer->tax_info['tax_id']) && ! is_null($vatcleaned)) $changerequested++;
					elseif (isset($customer->tax_info['tax_id']) && is_null($vatcleaned)) $changerequested++;
					elseif (isset($customer->tax_info['tax_id']) && ! is_null($vatcleaned))
					{
						if ($vatcleaned != $customer->tax_info['tax_id']) $changerequested++;
					} */
					if ($namecleaned != $customer->name) $changerequested++;
					if ($desccleaned != $customer->description) $changerequested++;
					if (($customer->tax_exempt == 'exempt' && !$object->tva_assuj) || (!$customer->tax_exempt == 'exempt' && empty($object->tva_assuj))) $changerequested++;
					if (!isset($customer->tax_ids['data']) && !is_null($vatcleaned)) $changerequested++;
					elseif (isset($customer->tax_ids['data']))
					{
						$taxinfo = reset($customer->tax_ids['data']);
						if (empty($taxinfo) && !empty($vatcleaned)) $changerequested++;
						if (isset($taxinfo->value) && $vatcleaned != $taxinfo->value) $changerequested++;
					}

					if ($changerequested)
					{
						/*if (! empty($object->email)) $customer->email = $object->email;
						$customer->description = $namecleaned;
						if (empty($taxinfo)) $customer->tax_info = array('type'=>'vat', 'tax_id'=>null);
						else $customer->tax_info = $taxinfo; */
						$customer->name = $namecleaned;
						$customer->description = $desccleaned;
						$customer->preferred_locales = $langcleaned;
						$customer->tax_exempt = $taxexemptcleaned;

						try {
							// Update Tax info on Stripe
							if (!empty($conf->global->STRIPE_SAVE_TAX_IDS))	// We setup to save Tax info on Stripe side. Warning: This may result in error when saving customer
							{
								if (!empty($vatcleaned))
								{
									$isineec = isInEEC($object);
									if ($object->country_code && $isineec)
									{
										//$taxids = $customer->allTaxIds($customer->id);
										$customer->createTaxId($customer->id, array('type'=>'eu_vat', 'value'=>$vatcleaned));
									}
								}
								else
								{
									$taxids = $customer->allTaxIds($customer->id);
									if (is_array($taxids->data))
									{
										foreach ($taxids->data as $taxidobj)
										{
											$customer->deleteTaxId($customer->id, $taxidobj->id);
										}
									}
								}
							}

							// Update Customer on Stripe
							$customer->save();
						}
						catch (Exception $e)
						{
						    //var_dump(\Stripe\Stripe::getApiVersion());
							$this->errors[] = $e->getMessage();
							$ok = -1;
						}
					}
				}
			}
		}
		if ($action == 'COMPANY_DELETE') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

			$stripeacc = $stripe->getStripeAccount($service); // No need of network access for this. May return '' if no Oauth defined.

			$customer = $stripe->customerStripe($object, $stripeacc, $servicestatus);
			if ($customer)
			{
				try {
					$customer->delete();
				}
				catch (Exception $e)
				{
					dol_syslog("Failed to delete Stripe customer ".$e->getMessage(), LOG_WARNING);
				}
			}

			$sql = "DELETE FROM ".MAIN_DB_PREFIX."societe_account";
			$sql .= " WHERE site='stripe' AND fk_soc = ".$object->id;
			$this->db->query($sql);
		}

		// If payment mode is linked to Stripee, we update/delete Stripe too
		if ($action == 'COMPANYPAYMENTMODE_MODIFY' && $object->type == 'card') {
			// For creation of credit card, we do not create in Stripe automatically
		}
		if ($action == 'COMPANYPAYMENTMODE_MODIFY' && $object->type == 'card') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

			if (!empty($object->stripe_card_ref))
			{
				$stripeacc = $stripe->getStripeAccount($service); // No need of network access for this. May return '' if no Oauth defined.
				$stripecu = $stripe->getStripeCustomerAccount($object->fk_soc); // No need of network access for this

				if ($stripecu)
				{
					// Get customer (required to get a card)
					if (empty($stripeacc)) {				// If the Stripe connect account not set, we use common API usage
						$customer = \Stripe\Customer::retrieve($stripecu);
					} else {
						$customer = \Stripe\Customer::retrieve($stripecu, array("stripe_account" => $stripeacc));
					}

					if ($customer)
					{
						$card = $stripe->cardStripe($customer, $object, $stripeacc, $servicestatus);
						if ($card) {
							$card->metadata = array('dol_id'=>$object->id, 'dol_version'=>DOL_VERSION, 'dol_entity'=>$conf->entity, 'ipaddress'=>(empty($_SERVER['REMOTE_ADDR']) ? '' : $_SERVER['REMOTE_ADDR']));
							try {
								$card->save();
							}
							catch (Exception $e)
							{
								$ok = -1;
								$this->error = $e->getMessages();
							}
						}
					}
				}
			}
		}
		if ($action == 'COMPANYPAYMENTMODE_DELETE' && $object->type == 'card') {
			dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

			if (!empty($object->stripe_card_ref))
			{
				$stripeacc = $stripe->getStripeAccount($service); // No need of network access for this. May return '' if no Oauth defined.
				$stripecu = $stripe->getStripeCustomerAccount($object->fk_soc); // No need of network access for this

				if ($stripecu)
				{
					// Get customer (required to get a card)
					if (empty($stripeacc)) {				// If the Stripe connect account not set, we use common API usage
						$customer = \Stripe\Customer::retrieve($stripecu);
					} else {
						$customer = \Stripe\Customer::retrieve($stripecu, array("stripe_account" => $stripeacc));
					}

					if ($customer)
					{
						$card = $stripe->cardStripe($customer, $object, $stripeacc, $servicestatus);
						if ($card) {
							if (method_exists($card, 'detach')) $card->detach();
							else $card->delete();
						}
					}
				}
			}
		}

		return $ok;
	}
}
