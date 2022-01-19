<?php
/* Copyright (C) 2018-2021 	Thibault FOUCART       <support@ptibogxiv.net>
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

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/config.php'; // This set stripe global env


/**
 *	Stripe class
 */
class Stripe extends CommonObject
{
	/**
	 * @var int ID
	 */
	public $rowid;

	/**
	 * @var int Thirdparty ID
	 */
	public $fk_soc;

	/**
	 * @var int ID
	 */
	public $fk_key;

	/**
	 * @var int ID
	 */
	public $id;

	public $mode;

	/**
	 * @var int Entity
	 */
	public $entity;

	public $statut;

	public $type;

	public $code;
	public $declinecode;

	/**
	 * @var string Message
	 */
	public $message;

	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB		$db			Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Return main company OAuth Connect stripe account
	 *
	 * @param 	string	$mode		'StripeTest' or 'StripeLive'
	 * @param	int		$fk_soc		Id of thirdparty
	 * @param	int		$entity		Id of entity (-1 = current environment)
	 * @return 	string				Stripe account 'acc_....' or '' if no OAuth token found
	 */
	public function getStripeAccount($mode = 'StripeTest', $fk_soc = 0, $entity = -1)
	{
		global $conf;

		$key = '';
		if ($entity < 0) {
			$entity = $conf->entity;
		}

		$sql = "SELECT tokenstring";
		$sql .= " FROM ".MAIN_DB_PREFIX."oauth_token";
		$sql .= " WHERE service = '".$this->db->escape($mode)."'";
		$sql .= " AND entity = ".((int) $entity);
		if ($fk_soc > 0) {
			$sql .= " AND fk_soc = ".((int) $fk_soc);
		} else {
			$sql .= " AND fk_soc IS NULL";
		}
		$sql .= " AND fk_user IS NULL AND fk_adherent IS NULL";

		dol_syslog(get_class($this)."::getStripeAccount", LOG_DEBUG);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$tokenstring = $obj->tokenstring;

				$tmparray = json_decode($tokenstring);
				$key = $tmparray->stripe_user_id;
			} else {
				$tokenstring = '';
			}
		} else {
			dol_print_error($this->db);
		}

		dol_syslog("No dedicated Stripe Connect account available for entity ".$conf->entity);
		return $key;
	}

	/**
	 * getStripeCustomerAccount
	 *
	 * @param	int		$id				Id of third party
	 * @param	int		$status			Status
	 * @param	string	$site_account 	Value to use to identify with account to use on site when site can offer several accounts. For example: 'pk_live_123456' when using Stripe service.
	 * @return	string					Stripe customer ref 'cu_xxxxxxxxxxxxx' or ''
	 */
	public function getStripeCustomerAccount($id, $status = 0, $site_account = '')
	{
		include_once DOL_DOCUMENT_ROOT.'/societe/class/societeaccount.class.php';
		$societeaccount = new SocieteAccount($this->db);
		return $societeaccount->getCustomerAccount($id, 'stripe', $status, $site_account); // Get thirdparty cus_...
	}


	/**
	 * Get the Stripe customer of a thirdparty (with option to create it in Stripe if not linked yet).
	 * Search on site_account = 0 or = $stripearrayofkeysbyenv[$status]['publishable_key']
	 *
	 * @param	Societe	$object							Object thirdparty to check, or create on stripe (create on stripe also update the stripe_account table for current entity)
	 * @param	string	$key							''=Use common API. If not '', it is the Stripe connect account 'acc_....' to use Stripe connect
	 * @param	int		$status							Status (0=test, 1=live)
	 * @param	int		$createifnotlinkedtostripe		1=Create the stripe customer and the link if the thirdparty is not yet linked to a stripe customer
	 * @return 	\Stripe\StripeCustomer|null 			Stripe Customer or null if not found
	 */
	public function customerStripe(Societe $object, $key = '', $status = 0, $createifnotlinkedtostripe = 0)
	{
		global $conf, $user;

		if (empty($object->id)) {
			dol_syslog("customerStripe is called with the parameter object that is not loaded");
			return null;
		}

		$customer = null;

		// Force to use the correct API key
		global $stripearrayofkeysbyenv;
		\Stripe\Stripe::setApiKey($stripearrayofkeysbyenv[$status]['secret_key']);

		$sql = "SELECT sa.key_account as key_account, sa.entity"; // key_account is cus_....
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_account as sa";
		$sql .= " WHERE sa.fk_soc = ".((int) $object->id);
		$sql .= " AND sa.entity IN (".getEntity('societe').")";
		$sql .= " AND sa.site = 'stripe' AND sa.status = ".((int) $status);
		$sql .= " AND (sa.site_account IS NULL OR sa.site_account = '' OR sa.site_account = '".$this->db->escape($stripearrayofkeysbyenv[$status]['publishable_key'])."')";
		$sql .= " AND sa.key_account IS NOT NULL AND sa.key_account <> ''";

		dol_syslog(get_class($this)."::customerStripe search stripe customer id for thirdparty id=".$object->id, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				$obj = $this->db->fetch_object($resql);
				$tiers = $obj->key_account;

				dol_syslog(get_class($this)."::customerStripe found stripe customer key_account = ".$tiers.". We will try to read it on Stripe with publishable_key = ".$stripearrayofkeysbyenv[$status]['publishable_key']);

				try {
					if (empty($key)) {				// If the Stripe connect account not set, we use common API usage
						//$customer = \Stripe\Customer::retrieve("$tiers");
						$customer = \Stripe\Customer::retrieve(array('id'=>"$tiers", 'expand[]'=>'sources'));
					} else {
						//$customer = \Stripe\Customer::retrieve("$tiers", array("stripe_account" => $key));
						$customer = \Stripe\Customer::retrieve(array('id'=>"$tiers", 'expand[]'=>'sources'), array("stripe_account" => $key));
					}
				} catch (Exception $e) {
					// For exemple, we may have error: 'No such customer: cus_XXXXX; a similar object exists in live mode, but a test mode key was used to make this request.'
					$this->error = $e->getMessage();
				}
			} elseif ($createifnotlinkedtostripe) {
				$ipaddress = getUserRemoteIP();

				$dataforcustomer = array(
					"email" => $object->email,
					"description" => $object->name,
					"metadata" => array('dol_id'=>$object->id, 'dol_version'=>DOL_VERSION, 'dol_entity'=>$conf->entity, 'ipaddress'=>$ipaddress)
				);

				$vatcleaned = $object->tva_intra ? $object->tva_intra : null;

				/*
				$taxinfo = array('type'=>'vat');
				if ($vatcleaned)
				{
					$taxinfo["tax_id"] = $vatcleaned;
				}
				// We force data to "null" if not defined as expected by Stripe
				if (empty($vatcleaned)) $taxinfo=null;
				$dataforcustomer["tax_info"] = $taxinfo;
				*/

				//$a = \Stripe\Stripe::getApiKey();
				//var_dump($a);var_dump($key);exit;
				try {
					// Force to use the correct API key
					global $stripearrayofkeysbyenv;
					\Stripe\Stripe::setApiKey($stripearrayofkeysbyenv[$status]['secret_key']);

					if (empty($key)) {				// If the Stripe connect account not set, we use common API usage
						$customer = \Stripe\Customer::create($dataforcustomer);
					} else {
						$customer = \Stripe\Customer::create($dataforcustomer, array("stripe_account" => $key));
					}

					// Create the VAT record in Stripe
					if (!empty($conf->global->STRIPE_SAVE_TAX_IDS)) {	// We setup to save Tax info on Stripe side. Warning: This may result in error when saving customer
						if (!empty($vatcleaned)) {
							$isineec = isInEEC($object);
							if ($object->country_code && $isineec) {
								//$taxids = $customer->allTaxIds($customer->id);
								$customer->createTaxId($customer->id, array('type'=>'eu_vat', 'value'=>$vatcleaned));
							}
						}
					}

					// Create customer in Dolibarr
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_account (fk_soc, login, key_account, site, site_account, status, entity, date_creation, fk_user_creat)";
					$sql .= " VALUES (".((int) $object->id).", '', '".$this->db->escape($customer->id)."', 'stripe', '".$this->db->escape($stripearrayofkeysbyenv[$status]['publishable_key'])."', ".((int) $status).", ".((int) $conf->entity).", '".$this->db->idate(dol_now())."', ".((int) $user->id).")";
					$resql = $this->db->query($sql);
					if (!$resql) {
						$this->error = $this->db->lasterror();
					}
				} catch (Exception $e) {
					$this->error = $e->getMessage();
				}
			}
		} else {
			dol_print_error($this->db);
		}

		return $customer;
	}

	/**
	 * Get the Stripe payment method Object from its ID
	 *
	 * @param	string	$paymentmethod	   			Payment Method ID
	 * @param	string	$key						''=Use common API. If not '', it is the Stripe connect account 'acc_....' to use Stripe connect
	 * @param	int		$status						Status (0=test, 1=live)
	 * @return 	\Stripe\PaymentMethod|null 			Stripe PaymentMethod or null if not found
	 */
	public function getPaymentMethodStripe($paymentmethod, $key = '', $status = 0)
	{
		$stripepaymentmethod = null;

		try {
			// Force to use the correct API key
			global $stripearrayofkeysbyenv;
			\Stripe\Stripe::setApiKey($stripearrayofkeysbyenv[$status]['secret_key']);
			if (empty($key)) {				// If the Stripe connect account not set, we use common API usage
				$stripepaymentmethod = \Stripe\PaymentMethod::retrieve(''.$paymentmethod->id.'');
			} else {
				$stripepaymentmethod = \Stripe\PaymentMethod::retrieve(''.$paymentmethod->id.'', array("stripe_account" => $key));
			}
		} catch (Exception $e) {
			$this->error = $e->getMessage();
		}

		return $stripepaymentmethod;
	}

	/**
	 * Get the Stripe payment intent. Create it with confirmnow=false
	 * Warning. If a payment was tried and failed, a payment intent was created.
	 * But if we change something on object to pay (amount or other), reusing same payment intent is not allowed by Stripe.
	 * Recommended solution is to recreate a new payment intent each time we need one (old one will be automatically closed after a delay),
	 * that's why i comment the part of code to retrieve a payment intent with object id (never mind if we cumulate payment intent with old ones that will not be used)
	 * Note: This is used when option STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION is on when making a payment from the public/payment/newpayment.php page
	 * but not when using the STRIPE_USE_NEW_CHECKOUT.
	 *
	 * @param   double  $amount                             Amount
	 * @param   string  $currency_code                      Currency code
	 * @param   string  $tag                                Tag
	 * @param   string  $description                        Description
	 * @param	mixed	$object							    Object to pay with Stripe
	 * @param	string 	$customer							Stripe customer ref 'cus_xxxxxxxxxxxxx' via customerStripe()
	 * @param	string	$key							    ''=Use common API. If not '', it is the Stripe connect account 'acc_....' to use Stripe connect
	 * @param	int		$status							    Status (0=test, 1=live)
	 * @param	int		$usethirdpartyemailforreceiptemail	1=use thirdparty email for receipt
	 * @param	int		$mode		                        automatic=automatic confirmation/payment when conditions are ok, manual=need to call confirm() on intent
	 * @param   boolean $confirmnow                         false=default, true=try to confirm immediatly after create (if conditions are ok)
	 * @param   string  $payment_method                     'pm_....' (if known)
	 * @param   string  $off_session                        If we use an already known payment method to pay when customer is not available during the checkout flow.
	 * @param	string	$noidempotency_key					Do not use the idempotency_key when creating the PaymentIntent
	 * @return 	\Stripe\PaymentIntent|null 			        Stripe PaymentIntent or null if not found and failed to create
	 */
	public function getPaymentIntent($amount, $currency_code, $tag, $description = '', $object = null, $customer = null, $key = null, $status = 0, $usethirdpartyemailforreceiptemail = 0, $mode = 'automatic', $confirmnow = false, $payment_method = null, $off_session = 0, $noidempotency_key = 1)
	{
		global $conf, $user;

		dol_syslog(get_class($this)."::getPaymentIntent", LOG_INFO, 1);

		$error = 0;

		if (empty($status)) {
			$service = 'StripeTest';
		} else {
			$service = 'StripeLive';
		}

		$arrayzerounitcurrency = array('BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF');
		if (!in_array($currency_code, $arrayzerounitcurrency)) {
			$stripeamount = $amount * 100;
		} else {
			$stripeamount = $amount;
		}

		$fee = $amount * ($conf->global->STRIPE_APPLICATION_FEE_PERCENT / 100) + $conf->global->STRIPE_APPLICATION_FEE;
		if ($fee >= $conf->global->STRIPE_APPLICATION_FEE_MAXIMAL && $conf->global->STRIPE_APPLICATION_FEE_MAXIMAL > $conf->global->STRIPE_APPLICATION_FEE_MINIMAL) {
			$fee = $conf->global->STRIPE_APPLICATION_FEE_MAXIMAL;
		} elseif ($fee < $conf->global->STRIPE_APPLICATION_FEE_MINIMAL) {
			$fee = $conf->global->STRIPE_APPLICATION_FEE_MINIMAL;
		}
		if (!in_array($currency_code, $arrayzerounitcurrency)) {
			$stripefee = round($fee * 100);
		} else {
			$stripefee = round($fee);
		}

		$paymentintent = null;

		if (is_object($object) && !empty($conf->global->STRIPE_REUSE_EXISTING_INTENT_IF_FOUND)) {
			// Warning. If a payment was tried and failed, a payment intent was created.
			// But if we change something on object to pay (amount or other that does not change the idempotency key), reusing same payment intent is not allowed by Stripe.
			// Recommended solution is to recreate a new payment intent each time we need one (old one will be automatically closed by Stripe after a delay), Stripe will
			// automatically return the existing payment intent if idempotency is provided when we try to create the new one.
			// That's why we can comment the part of code to retrieve a payment intent with object id (never mind if we cumulate payment intent with old ones that will not be used)

			$sql = "SELECT pi.ext_payment_id, pi.entity, pi.fk_facture, pi.sourcetype, pi.ext_payment_site";
			$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pi";
			$sql .= " WHERE pi.fk_facture = ".((int) $object->id);
			$sql .= " AND pi.sourcetype = '".$this->db->escape($object->element)."'";
			$sql .= " AND pi.entity IN (".getEntity('societe').")";
			$sql .= " AND pi.ext_payment_site = '".$this->db->escape($service)."'";

			dol_syslog(get_class($this)."::getPaymentIntent search stripe payment intent for object id = ".$object->id, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				if ($num) {
					$obj = $this->db->fetch_object($resql);
					$intent = $obj->ext_payment_id;

					dol_syslog(get_class($this)."::getPaymentIntent found existing payment intent record");

					// Force to use the correct API key
					global $stripearrayofkeysbyenv;
					\Stripe\Stripe::setApiKey($stripearrayofkeysbyenv[$status]['secret_key']);

					try {
						if (empty($key)) {				// If the Stripe connect account not set, we use common API usage
							$paymentintent = \Stripe\PaymentIntent::retrieve($intent);
						} else {
							$paymentintent = \Stripe\PaymentIntent::retrieve($intent, array("stripe_account" => $key));
						}
					} catch (Exception $e) {
						$error++;
						$this->error = $e->getMessage();
					}
				}
			}
		}

		if (empty($paymentintent)) {
			// Try to create intent. See https://stripe.com/docs/api/payment_intents/create
			$ipaddress = getUserRemoteIP();
			$metadata = array('dol_version'=>DOL_VERSION, 'dol_entity'=>$conf->entity, 'ipaddress'=>$ipaddress);
			if (is_object($object)) {
				$metadata['dol_type'] = $object->element;
				$metadata['dol_id'] = $object->id;
				if (is_object($object->thirdparty) && $object->thirdparty->id > 0) {
					$metadata['dol_thirdparty_id'] = $object->thirdparty->id;
				}
			}

			// list of payment method types
			$paymentmethodtypes = array("card");
			if (!empty($conf->global->STRIPE_SEPA_DIRECT_DEBIT)) {
				$paymentmethodtypes[] = "sepa_debit"; //&& ($object->thirdparty->isInEEC())
			}
			if (!empty($conf->global->STRIPE_KLARNA)) {
				$paymentmethodtypes[] = "klarna";
			}
			if (!empty($conf->global->STRIPE_BANCONTACT)) {
				$paymentmethodtypes[] = "bancontact";
			}
			if (!empty($conf->global->STRIPE_IDEAL)) {
				$paymentmethodtypes[] = "ideal";
			}
			if (!empty($conf->global->STRIPE_GIROPAY)) {
				$paymentmethodtypes[] = "giropay";
			}
			if (!empty($conf->global->STRIPE_SOFORT)) {
				$paymentmethodtypes[] = "sofort";
			}

			$dataforintent = array(
				"confirm" => $confirmnow, // Do not confirm immediatly during creation of intent
				"confirmation_method" => $mode,
				"amount" => $stripeamount,
				"currency" => $currency_code,
				"payment_method_types" => $paymentmethodtypes,
				"description" => $description,
				"statement_descriptor_suffix" => dol_trunc($tag, 10, 'right', 'UTF-8', 1), // 22 chars that appears on bank receipt (company + description)
				//"save_payment_method" => true,
				"setup_future_usage" => "on_session",
				"metadata" => $metadata
			);
			if (!is_null($customer)) {
				$dataforintent["customer"] = $customer;
			}
			// payment_method =
			// payment_method_types = array('card')
			//var_dump($dataforintent);
			if ($off_session) {
				unset($dataforintent['setup_future_usage']);
				// We can't use both "setup_future_usage" = "off_session" and "off_session" = true.
				// Because $off_session parameter is dedicated to create paymentintent off_line (and not future payment), we need to use "off_session" = true.
				//$dataforintent["setup_future_usage"] = "off_session";
				$dataforintent["off_session"] = true;
			}
			if (!empty($conf->global->STRIPE_GIROPAY)) {
				unset($dataforintent['setup_future_usage']);
			}
			if (!empty($conf->global->STRIPE_KLARNA)) {
				unset($dataforintent['setup_future_usage']);
			}
			if (!is_null($payment_method)) {
				$dataforintent["payment_method"] = $payment_method;
				$description .= ' - '.$payment_method;
			}

			if ($conf->entity != $conf->global->STRIPECONNECT_PRINCIPAL && $stripefee > 0) {
				$dataforintent["application_fee_amount"] = $stripefee;
			}
			if ($usethirdpartyemailforreceiptemail && is_object($object) && $object->thirdparty->email) {
				$dataforintent["receipt_email"] = $object->thirdparty->email;
			}

			try {
				// Force to use the correct API key
				global $stripearrayofkeysbyenv;
				\Stripe\Stripe::setApiKey($stripearrayofkeysbyenv[$status]['secret_key']);

				$arrayofoptions = array();
				if (empty($noidempotency_key)) {
					$arrayofoptions["idempotency_key"] = $description;
				}
				// Note: If all data for payment intent are same than a previous on, even if we use 'create', Stripe will return ID of the old existing payment intent.
				if (!empty($key)) {				// If the Stripe connect account not set, we use common API usage
					$arrayofoptions["stripe_account"] = $key;
				}

				dol_syslog("dataforintent to create paymentintent = ".var_export($dataforintent, true));

				$paymentintent = \Stripe\PaymentIntent::create($dataforintent, $arrayofoptions);

				// Store the payment intent
				if (is_object($object)) {
					$paymentintentalreadyexists = 0;
					// Check that payment intent $paymentintent->id is not already recorded.
					$sql = "SELECT pi.rowid";
					$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_facture_demande as pi";
					$sql .= " WHERE pi.entity IN (".getEntity('societe').")";
					$sql .= " AND pi.ext_payment_site = '".$this->db->escape($service)."'";
					$sql .= " AND pi.ext_payment_id = '".$this->db->escape($paymentintent->id)."'";

					dol_syslog(get_class($this)."::getPaymentIntent search if payment intent already in prelevement_facture_demande", LOG_DEBUG);
					$resql = $this->db->query($sql);
					if ($resql) {
						$num = $this->db->num_rows($resql);
						if ($num) {
							$obj = $this->db->fetch_object($resql);
							if ($obj) {
								$paymentintentalreadyexists++;
							}
						}
					} else {
						dol_print_error($this->db);
					}

					// If not, we create it.
					if (!$paymentintentalreadyexists) {
						$now = dol_now();
						$sql = "INSERT INTO ".MAIN_DB_PREFIX."prelevement_facture_demande (date_demande, fk_user_demande, ext_payment_id, fk_facture, sourcetype, entity, ext_payment_site, amount)";
						$sql .= " VALUES ('".$this->db->idate($now)."', ".((int) $user->id).", '".$this->db->escape($paymentintent->id)."', ".((int) $object->id).", '".$this->db->escape($object->element)."', ".((int) $conf->entity).", '".$this->db->escape($service)."', ".((float) $amount).")";
						$resql = $this->db->query($sql);
						if (!$resql) {
							$error++;
							$this->error = $this->db->lasterror();
							dol_syslog(get_class($this)."::PaymentIntent failed to insert paymentintent with id=".$paymentintent->id." into database.", LOG_ERR);
						}
					}
				} else {
					$_SESSION["stripe_payment_intent"] = $paymentintent;
				}
			} catch (Stripe\Error\Card $e) {
				$error++;
				$this->error = $e->getMessage();
				$this->code = $e->getStripeCode();
				$this->declinecode = $e->getDeclineCode();
			} catch (Exception $e) {
				/*var_dump($dataforintent);
				var_dump($description);
				var_dump($key);
				var_dump($paymentintent);
				var_dump($e->getMessage());
				var_dump($e);*/
				$error++;
				$this->error = $e->getMessage();
				$this->code = '';
				$this->declinecode = '';
			}
		}

		dol_syslog(get_class($this)."::getPaymentIntent return error=".$error." this->error=".$this->error, LOG_INFO, -1);

		if (!$error) {
			return $paymentintent;
		} else {
			return null;
		}
	}


	/**
	 * Get the Stripe payment intent. Create it with confirmnow=false
	 * Warning. If a payment was tried and failed, a payment intent was created.
	 * But if we change something on object to pay (amount or other), reusing same payment intent is not allowed.
	 * Recommanded solution is to recreate a new payment intent each time we need one (old one will be automatically closed after a delay),
	 * that's why i comment the part of code to retrieve a payment intent with object id (never mind if we cumulate payment intent with old ones that will not be used)
	 * Note: This is used when option STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION is on when making a payment from the public/payment/newpayment.php page
	 * but not when using the STRIPE_USE_NEW_CHECKOUT.
	 *
	 * @param   string  $description                        Description
	 * @param	Societe	$object							    Object to pay with Stripe
	 * @param	string 	$customer							Stripe customer ref 'cus_xxxxxxxxxxxxx' via customerStripe()
	 * @param	string	$key							    ''=Use common API. If not '', it is the Stripe connect account 'acc_....' to use Stripe connect
	 * @param	int		$status							    Status (0=test, 1=live)
	 * @param	int		$usethirdpartyemailforreceiptemail	1=use thirdparty email for receipt
	 * @param   boolean $confirmnow                         false=default, true=try to confirm immediatly after create (if conditions are ok)
	 * @return 	\Stripe\SetupIntent|null 			        Stripe SetupIntent or null if not found and failed to create
	 */
	public function getSetupIntent($description, $object, $customer, $key, $status, $usethirdpartyemailforreceiptemail = 0, $confirmnow = false)
	{
		global $conf;

		dol_syslog("getSetupIntent description=".$description.' confirmnow='.$confirmnow, LOG_INFO, 1);

		$error = 0;

		if (empty($status)) {
			$service = 'StripeTest';
		} else {
			$service = 'StripeLive';
		}

		$setupintent = null;

		if (empty($setupintent)) {
			$ipaddress = getUserRemoteIP();
			$metadata = array('dol_version'=>DOL_VERSION, 'dol_entity'=>$conf->entity, 'ipaddress'=>$ipaddress);
			if (is_object($object)) {
				$metadata['dol_type'] = $object->element;
				$metadata['dol_id'] = $object->id;
				if (is_object($object->thirdparty) && $object->thirdparty->id > 0) {
					$metadata['dol_thirdparty_id'] = $object->thirdparty->id;
				}
			}

			// list of payment method types
			$paymentmethodtypes = array("card");
			if (!empty($conf->global->STRIPE_SEPA_DIRECT_DEBIT)) {
				$paymentmethodtypes[] = "sepa_debit"; //&& ($object->thirdparty->isInEEC())
			}
			if (!empty($conf->global->STRIPE_BANCONTACT)) {
				$paymentmethodtypes[] = "bancontact";
			}
			if (!empty($conf->global->STRIPE_IDEAL)) {
				$paymentmethodtypes[] = "ideal";
			}
			// Giropay not possible for setup intent
			if (!empty($conf->global->STRIPE_SOFORT)) {
				$paymentmethodtypes[] = "sofort";
			}

			$dataforintent = array(
				"confirm" => $confirmnow, // Do not confirm immediatly during creation of intent
				"payment_method_types" => $paymentmethodtypes,
				"usage" => "off_session",
				"metadata" => $metadata
			);
			if (!is_null($customer)) {
				$dataforintent["customer"] = $customer;
			}
			if (!is_null($description)) {
				$dataforintent["description"] = $description;
			}
			// payment_method =
			// payment_method_types = array('card')
			//var_dump($dataforintent);

			if ($usethirdpartyemailforreceiptemail && is_object($object) && $object->thirdparty->email) {
				$dataforintent["receipt_email"] = $object->thirdparty->email;
			}

			try {
				// Force to use the correct API key
				global $stripearrayofkeysbyenv;
				\Stripe\Stripe::setApiKey($stripearrayofkeysbyenv[$status]['secret_key']);

				dol_syslog("getSetupIntent ".$stripearrayofkeysbyenv[$status]['publishable_key'], LOG_DEBUG);

				// Note: If all data for payment intent are same than a previous on, even if we use 'create', Stripe will return ID of the old existing payment intent.
				if (empty($key)) {				// If the Stripe connect account not set, we use common API usage
					//$setupintent = \Stripe\SetupIntent::create($dataforintent, array("idempotency_key" => "$description"));
					$setupintent = \Stripe\SetupIntent::create($dataforintent, array());
				} else {
					//$setupintent = \Stripe\SetupIntent::create($dataforintent, array("idempotency_key" => "$description", "stripe_account" => $key));
					$setupintent = \Stripe\SetupIntent::create($dataforintent, array("stripe_account" => $key));
				}
				//var_dump($setupintent->id);

				// Store the setup intent
				/*if (is_object($object))
				{
					$setupintentalreadyexists = 0;
					// Check that payment intent $setupintent->id is not already recorded.
					$sql = "SELECT pi.rowid";
					$sql.= " FROM " . MAIN_DB_PREFIX . "prelevement_facture_demande as pi";
					$sql.= " WHERE pi.entity IN (".getEntity('societe').")";
					$sql.= " AND pi.ext_payment_site = '" . $this->db->escape($service) . "'";
					$sql.= " AND pi.ext_payment_id = '".$this->db->escape($setupintent->id)."'";

					dol_syslog(get_class($this) . "::getPaymentIntent search if payment intent already in prelevement_facture_demande", LOG_DEBUG);
					$resql = $this->db->query($sql);
					if ($resql) {
						$num = $this->db->num_rows($resql);
						if ($num)
						{
							$obj = $this->db->fetch_object($resql);
							if ($obj) $setupintentalreadyexists++;
						}
					}
					else dol_print_error($this->db);

					// If not, we create it.
					if (! $setupintentalreadyexists)
					{
						$now=dol_now();
						$sql = "INSERT INTO " . MAIN_DB_PREFIX . "prelevement_facture_demande (date_demande, fk_user_demande, ext_payment_id, fk_facture, sourcetype, entity, ext_payment_site)";
						$sql .= " VALUES ('".$this->db->idate($now)."', ".((int) $user->id).", '".$this->db->escape($setupintent->id)."', ".((int) $object->id).", '".$this->db->escape($object->element)."', " . ((int) $conf->entity) . ", '" . $this->db->escape($service) . "', ".((float) $amount).")";
						$resql = $this->db->query($sql);
						if (! $resql)
						{
							$error++;
							$this->error = $this->db->lasterror();
							dol_syslog(get_class($this) . "::PaymentIntent failed to insert paymentintent with id=".$setupintent->id." into database.");
						}
					}
				}
				else
				{
					$_SESSION["stripe_setup_intent"] = $setupintent;
				}*/
			} catch (Exception $e) {
				/*var_dump($dataforintent);
				 var_dump($description);
				 var_dump($key);
				 var_dump($setupintent);
				 var_dump($e->getMessage());*/
				$error++;
				$this->error = $e->getMessage();
			}
		}

		if (!$error) {
			dol_syslog("getSetupIntent ".(is_object($setupintent) ? $setupintent->id : ''), LOG_INFO, -1);
			return $setupintent;
		} else {
			dol_syslog("getSetupIntent return error=".$error, LOG_INFO, -1);
			return null;
		}
	}


	/**
	 * Get the Stripe card of a company payment mode (option to create it on Stripe if not linked yet is no more available on new Stripe API)
	 *
	 * @param	\Stripe\StripeCustomer	$cu								Object stripe customer.
	 * @param	CompanyPaymentMode		$object							Object companypaymentmode to check, or create on stripe (create on stripe also update the societe_rib table for current entity)
	 * @param	string					$stripeacc						''=Use common API. If not '', it is the Stripe connect account 'acc_....' to use Stripe connect
	 * @param	int						$status							Status (0=test, 1=live)
	 * @param	int						$createifnotlinkedtostripe		1=Create the stripe card and the link if the card is not yet linked to a stripe card. Deprecated with new Stripe API and SCA.
	 * @return 	\Stripe\StripeCard|\Stripe\PaymentMethod|null 			Stripe Card or null if not found
	 */
	public function cardStripe($cu, CompanyPaymentMode $object, $stripeacc = '', $status = 0, $createifnotlinkedtostripe = 0)
	{
		global $conf, $user, $langs;

		$card = null;

		$sql = "SELECT sa.stripe_card_ref, sa.proprio, sa.exp_date_month, sa.exp_date_year, sa.number, sa.cvn"; // stripe_card_ref is card_....
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_rib as sa";
		$sql .= " WHERE sa.rowid = ".((int) $object->id); // We get record from ID, no need for filter on entity
		$sql .= " AND sa.type = 'card'";

		dol_syslog(get_class($this)."::fetch search stripe card id for paymentmode id=".$object->id.", stripeacc=".$stripeacc.", status=".$status.", createifnotlinkedtostripe=".$createifnotlinkedtostripe, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				$obj = $this->db->fetch_object($resql);
				$cardref = $obj->stripe_card_ref;
				dol_syslog(get_class($this)."::cardStripe cardref=".$cardref);
				if ($cardref) {
					try {
						if (empty($stripeacc)) {				// If the Stripe connect account not set, we use common API usage
							if (!preg_match('/^pm_/', $cardref) && !empty($cu->sources)) {
								$card = $cu->sources->retrieve($cardref);
							} else {
								$card = \Stripe\PaymentMethod::retrieve($cardref);
							}
						} else {
							if (!preg_match('/^pm_/', $cardref) && !empty($cu->sources)) {
								//$card = $cu->sources->retrieve($cardref, array("stripe_account" => $stripeacc));		// this API fails when array stripe_account is provided
								$card = $cu->sources->retrieve($cardref);
							} else {
								//$card = \Stripe\PaymentMethod::retrieve($cardref, array("stripe_account" => $stripeacc));		// Don't know if this works
								$card = \Stripe\PaymentMethod::retrieve($cardref);
							}
						}
					} catch (Exception $e) {
						$this->error = $e->getMessage();
						dol_syslog($this->error, LOG_WARNING);
					}
				} elseif ($createifnotlinkedtostripe) {
					$exp_date_month = $obj->exp_date_month;
					$exp_date_year = $obj->exp_date_year;
					$number = $obj->number;
					$cvc = $obj->cvn; // cvn in database, cvc for stripe
					$cardholdername = $obj->proprio;

					$ipaddress = getUserRemoteIP();

					$dataforcard = array(
						"source" => array('object'=>'card', 'exp_month'=>$exp_date_month, 'exp_year'=>$exp_date_year, 'number'=>$number, 'cvc'=>$cvc, 'name'=>$cardholdername),
						"metadata" => array('dol_id'=>$object->id, 'dol_version'=>DOL_VERSION, 'dol_entity'=>$conf->entity, 'ipaddress'=>$ipaddress)
					);

					//$a = \Stripe\Stripe::getApiKey();
					//var_dump($a);var_dump($stripeacc);exit;
					try {
						if (empty($stripeacc)) {				// If the Stripe connect account not set, we use common API usage
							if (empty($conf->global->STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION)) {
								dol_syslog("Try to create card with dataforcard = ".json_encode($dataforcard));
								$card = $cu->sources->create($dataforcard);
								if (!$card) {
									$this->error = 'Creation of card on Stripe has failed';
								}
							} else {
								$connect = '';
								if (!empty($stripeacc)) {
									$connect = $stripeacc.'/';
								}
								$url = 'https://dashboard.stripe.com/'.$connect.'test/customers/'.$cu->id;
								if ($status) {
									$url = 'https://dashboard.stripe.com/'.$connect.'customers/'.$cu->id;
								}
								$urtoswitchonstripe = ' <a href="'.$url.'" target="_stripe">'.img_picto($langs->trans('ShowInStripe'), 'globe').'</a>';

								//dol_syslog("Error: This case is not supported", LOG_ERR);
								$this->error = $langs->trans('CreationOfPaymentModeMustBeDoneFromStripeInterface', $urtoswitchonstripe);
							}
						} else {
							if (empty($conf->global->STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION)) {
								dol_syslog("Try to create card with dataforcard = ".json_encode($dataforcard));
								$card = $cu->sources->create($dataforcard, array("stripe_account" => $stripeacc));
								if (!$card) {
									$this->error = 'Creation of card on Stripe has failed';
								}
							} else {
								$connect = '';
								if (!empty($stripeacc)) {
									$connect = $stripeacc.'/';
								}
								$url = 'https://dashboard.stripe.com/'.$connect.'test/customers/'.$cu->id;
								if ($status) {
									$url = 'https://dashboard.stripe.com/'.$connect.'customers/'.$cu->id;
								}
								$urtoswitchonstripe = ' <a href="'.$url.'" target="_stripe">'.img_picto($langs->trans('ShowInStripe'), 'globe').'</a>';

								//dol_syslog("Error: This case is not supported", LOG_ERR);
								$this->error = $langs->trans('CreationOfPaymentModeMustBeDoneFromStripeInterface', $urtoswitchonstripe);
							}
						}

						if ($card) {
							$sql = "UPDATE ".MAIN_DB_PREFIX."societe_rib";
							$sql .= " SET stripe_card_ref = '".$this->db->escape($card->id)."', card_type = '".$this->db->escape($card->brand)."',";
							$sql .= " country_code = '".$this->db->escape($card->country)."',";
							$sql .= " approved = ".($card->cvc_check == 'pass' ? 1 : 0);
							$sql .= " WHERE rowid = ".((int) $object->id);
							$sql .= " AND type = 'card'";
							$resql = $this->db->query($sql);
							if (!$resql) {
								$this->error = $this->db->lasterror();
							}
						}
					} catch (Exception $e) {
						$this->error = $e->getMessage();
						dol_syslog($this->error, LOG_WARNING);
					}
				}
			}
		} else {
			dol_print_error($this->db);
		}

		return $card;
	}

	/**
	 * Create charge.
	 * This is called by page htdocs/stripe/payment.php and may be deprecated.
	 *
	 * @param	int 	$amount									Amount to pay
	 * @param	string 	$currency								EUR, GPB...
	 * @param	string 	$origin									Object type to pay (order, invoice, contract...)
	 * @param	int 	$item									Object id to pay
	 * @param	string 	$source									src_xxxxx or card_xxxxx or pm_xxxxx
	 * @param	string 	$customer								Stripe customer ref 'cus_xxxxxxxxxxxxx' via customerStripe()
	 * @param	string 	$account								Stripe account ref 'acc_xxxxxxxxxxxxx' via  getStripeAccount()
	 * @param	int		$status									Status (0=test, 1=live)
	 * @param	int		$usethirdpartyemailforreceiptemail		Use thirdparty email as receipt email
	 * @param	boolean	$capture								Set capture flag to true (take payment) or false (wait)
	 * @return Stripe
	 */
	public function createPaymentStripe($amount, $currency, $origin, $item, $source, $customer, $account, $status = 0, $usethirdpartyemailforreceiptemail = 0, $capture = true)
	{
		global $conf;

		$error = 0;

		if (empty($status)) {
			$service = 'StripeTest';
		} else {
			$service = 'StripeLive';
		}

		$sql = "SELECT sa.key_account as key_account, sa.fk_soc, sa.entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_account as sa";
		$sql .= " WHERE sa.key_account = '".$this->db->escape($customer)."'";
		//$sql.= " AND sa.entity IN (".getEntity('societe').")";
		$sql .= " AND sa.site = 'stripe' AND sa.status = ".((int) $status);

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$key = $obj->fk_soc;
			} else {
				$key = null;
			}
		} else {
			$key = null;
		}

		$arrayzerounitcurrency = array('BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF');
		if (!in_array($currency, $arrayzerounitcurrency)) {
			$stripeamount = $amount * 100;
		} else {
			$stripeamount = $amount;
		}

		$societe = new Societe($this->db);
		if ($key > 0) {
			$societe->fetch($key);
		}

		$description = "";
		$ref = "";
		if ($origin == 'order') {
			$order = new Commande($this->db);
			$order->fetch($item);
			$ref = $order->ref;
			$description = "ORD=".$ref.".CUS=".$societe->id.".PM=stripe";
		} elseif ($origin == 'invoice') {
			$invoice = new Facture($this->db);
			$invoice->fetch($item);
			$ref = $invoice->ref;
			$description = "INV=".$ref.".CUS=".$societe->id.".PM=stripe";
		}

		$ipaddress = getUserRemoteIP();

		$metadata = array(
			"dol_id" => "".$item."",
			"dol_type" => "".$origin."",
			"dol_thirdparty_id" => "".$societe->id."",
			'dol_thirdparty_name' => $societe->name,
			'dol_version'=>DOL_VERSION,
			'dol_entity'=>$conf->entity,
			'ipaddress'=>$ipaddress
		);
		$return = new Stripe($this->db);
		try {
			// Force to use the correct API key
			global $stripearrayofkeysbyenv;
			\Stripe\Stripe::setApiKey($stripearrayofkeysbyenv[$status]['secret_key']);

			if (empty($conf->stripeconnect->enabled)) {	// With a common Stripe account
				if (preg_match('/pm_/i', $source)) {
					$stripecard = $source;
					$amountstripe = $stripeamount;
					$FULLTAG = 'PFBO'; // Payment From Back Office
					$stripe = $return;
					$amounttopay = $amount;
					$servicestatus = $status;

					dol_syslog("* createPaymentStripe get stripeacc", LOG_DEBUG);
					$stripeacc = $stripe->getStripeAccount($service); // Get Stripe OAuth connect account if it exists (no network access here)

					dol_syslog("* createPaymentStripe Create payment for customer ".$customer->id." on source card ".$stripecard->id.", amounttopay=".$amounttopay.", amountstripe=".$amountstripe.", FULLTAG=".$FULLTAG, LOG_DEBUG);

					// Create payment intent and charge payment (confirmnow = true)
					$paymentintent = $stripe->getPaymentIntent($amounttopay, $currency, $FULLTAG, $description, $invoice, $customer->id, $stripeacc, $servicestatus, 0, 'automatic', true, $stripecard->id, 1);

					$charge = new stdClass();
					if ($paymentintent->status == 'succeeded') {
						$charge->status = 'ok';
					} else {
						$charge->status = 'failed';
						$charge->failure_code = $stripe->code;
						$charge->failure_message = $stripe->error;
						$charge->failure_declinecode = $stripe->declinecode;
						$stripefailurecode = $stripe->code;
						$stripefailuremessage = $stripe->error;
						$stripefailuredeclinecode = $stripe->declinecode;
					}
				} elseif (preg_match('/acct_/i', $source)) {
					$charge = \Stripe\Charge::create(array(
						"amount" => "$stripeamount",
						"currency" => "$currency",
						"statement_descriptor_suffix" => dol_trunc($description, 10, 'right', 'UTF-8', 1), // 22 chars that appears on bank receipt (company + description)
						"description" => "Stripe payment: ".$description,
						"capture"  => $capture,
						"metadata" => $metadata,
						"source" => "$source"
					));
				} else {
					$paymentarray = array(
						"amount" => "$stripeamount",
						"currency" => "$currency",
						"statement_descriptor_suffix" => dol_trunc($description, 10, 'right', 'UTF-8', 1), // 22 chars that appears on bank receipt (company + description)
						"description" => "Stripe payment: ".$description,
						"capture"  => $capture,
						"metadata" => $metadata,
						"source" => "$source",
						"customer" => "$customer"
					);

					if ($societe->email && $usethirdpartyemailforreceiptemail) {
						$paymentarray["receipt_email"] = $societe->email;
					}

					$charge = \Stripe\Charge::create($paymentarray, array("idempotency_key" => "$description"));
				}
			} else {
				// With Stripe Connect
				$fee = $amount * ($conf->global->STRIPE_APPLICATION_FEE_PERCENT / 100) + $conf->global->STRIPE_APPLICATION_FEE;
				if ($fee >= $conf->global->STRIPE_APPLICATION_FEE_MAXIMAL && $conf->global->STRIPE_APPLICATION_FEE_MAXIMAL > $conf->global->STRIPE_APPLICATION_FEE_MINIMAL) {
					$fee = $conf->global->STRIPE_APPLICATION_FEE_MAXIMAL;
				} elseif ($fee < $conf->global->STRIPE_APPLICATION_FEE_MINIMAL) {
					$fee = $conf->global->STRIPE_APPLICATION_FEE_MINIMAL;
				}

				if (!in_array($currency, $arrayzerounitcurrency)) {
					$stripefee = round($fee * 100);
				} else {
					$stripefee = round($fee);
				}

				$paymentarray = array(
					"amount" => "$stripeamount",
					"currency" => "$currency",
				"statement_descriptor_suffix" => dol_trunc($description, 10, 'right', 'UTF-8', 1), // 22 chars that appears on bank receipt (company + description)
					"description" => "Stripe payment: ".$description,
					"capture"  => $capture,
					"metadata" => $metadata,
					"source" => "$source",
					"customer" => "$customer"
				);
				if ($conf->entity != $conf->global->STRIPECONNECT_PRINCIPAL && $stripefee > 0) {
					$paymentarray["application_fee_amount"] = $stripefee;
				}
				if ($societe->email && $usethirdpartyemailforreceiptemail) {
					$paymentarray["receipt_email"] = $societe->email;
				}

				if (preg_match('/pm_/i', $source)) {
					$stripecard = $source;
					$amountstripe = $stripeamount;
					$FULLTAG = 'PFBO'; // Payment From Back Office
					$stripe = $return;
					$amounttopay = $amount;
					$servicestatus = $status;

					dol_syslog("* createPaymentStripe get stripeacc", LOG_DEBUG);
					$stripeacc = $stripe->getStripeAccount($service); // Get Stripe OAuth connect account if it exists (no network access here)

					dol_syslog("* createPaymentStripe Create payment on card ".$stripecard->id.", amounttopay=".$amounttopay.", amountstripe=".$amountstripe.", FULLTAG=".$FULLTAG, LOG_DEBUG);

					// Create payment intent and charge payment (confirmnow = true)
					$paymentintent = $stripe->getPaymentIntent($amounttopay, $currency, $FULLTAG, $description, $invoice, $customer->id, $stripeacc, $servicestatus, 0, 'automatic', true, $stripecard->id, 1);

					$charge = new stdClass();
					if ($paymentintent->status == 'succeeded') {
						$charge->status = 'ok';
						$charge->id = $paymentintent->id;
					} else {
						$charge->status = 'failed';
						$charge->failure_code = $stripe->code;
						$charge->failure_message = $stripe->error;
						$charge->failure_declinecode = $stripe->declinecode;
					}
				} else {
					$charge = \Stripe\Charge::create($paymentarray, array("idempotency_key" => "$description", "stripe_account" => "$account"));
				}
			}
			if (isset($charge->id)) {
			}

			$return->statut = 'success';
			$return->id = $charge->id;

			if (preg_match('/pm_/i', $source)) {
				$return->message = 'Payment retrieved by card status = '.$charge->status;
			} else {
				if ($charge->source->type == 'card') {
					$return->message = $charge->source->card->brand." ....".$charge->source->card->last4;
				} elseif ($charge->source->type == 'three_d_secure') {
					$stripe = new Stripe($this->db);
					$src = \Stripe\Source::retrieve("".$charge->source->three_d_secure->card."", array(
					"stripe_account" => $stripe->getStripeAccount($service)
					));
					$return->message = $src->card->brand." ....".$src->card->last4;
				} else {
					$return->message = $charge->id;
				}
			}
		} catch (\Stripe\Error\Card $e) {
			include DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
			// Since it's a decline, \Stripe\Error\Card will be caught
			$body = $e->getJsonBody();
			$err = $body['error'];

			$return->statut = 'error';
			$return->id = $err['charge'];
			$return->type = $err['type'];
			$return->code = $err['code'];
			$return->message = $err['message'];
			$body = "Error: <br>".$return->id." ".$return->message." ";
			$subject = '[Alert] Payment error using Stripe';
			$cmailfile = new CMailFile($subject, $conf->global->ONLINE_PAYMENT_SENDEMAIL, $conf->global->MAIN_INFO_SOCIETE_MAIL, $body);
			$cmailfile->sendfile();

			$error++;
			dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
		} catch (\Stripe\Error\RateLimit $e) {
			// Too many requests made to the API too quickly
			$error++;
			dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
		} catch (\Stripe\Error\InvalidRequest $e) {
			// Invalid parameters were supplied to Stripe's API
			$error++;
			dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
		} catch (\Stripe\Error\Authentication $e) {
			// Authentication with Stripe's API failed
			// (maybe you changed API keys recently)
			$error++;
			dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
		} catch (\Stripe\Error\ApiConnection $e) {
			// Network communication with Stripe failed
			$error++;
			dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
		} catch (\Stripe\Error\Base $e) {
			// Display a very generic error to the user, and maybe send
			// yourself an email
			$error++;
			dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
		} catch (Exception $e) {
			// Something else happened, completely unrelated to Stripe
			$error++;
			dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
		}
		return $return;
	}
}
