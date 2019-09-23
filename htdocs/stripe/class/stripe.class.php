<?php
/* Copyright (C) 2018-2019 	Thibault FOUCART       <support@ptibogxiv.net>
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

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/config.php';						// This set stripe global env


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
	 * @return 	string				Stripe account 'acc_....' or '' if no OAuth token found
	 */
	public function getStripeAccount($mode = 'StripeTest')
	{
		global $conf;

		$sql = "SELECT tokenstring";
		$sql.= " FROM ".MAIN_DB_PREFIX."oauth_token";
		$sql.= " WHERE entity = ".$conf->entity;
		$sql.= " AND service = '".$mode."'";

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
    	if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
    			$tokenstring=$obj->tokenstring;

    			$tmparray = dol_json_decode($tokenstring);
    			$key = $tmparray->stripe_user_id;
    		} else {
    			$tokenstring='';
    		}
    	}
    	else {
    		dol_print_error($this->db);
    	}

    	dol_syslog("No dedicated Stripe Connect account available for entity ".$conf->entity);
		return $key;
	}

	/**
	 * getStripeCustomerAccount
	 *
	 * @param	int		$id		Id of third party
	 * @param	int		$status		Status
	 * @return	string				Stripe customer ref 'cu_xxxxxxxxxxxxx' or ''
	 */
	public function getStripeCustomerAccount($id, $status = 0)
	{
		include_once DOL_DOCUMENT_ROOT.'/societe/class/societeaccount.class.php';
		$societeaccount = new SocieteAccount($this->db);
		return $societeaccount->getCustomerAccount($id, 'stripe', $status);		// Get thirdparty cus_...
	}


	/**
	 * Get the Stripe customer of a thirdparty (with option to create it if not linked yet)
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

		if (empty($object->id))
		{
			dol_syslog("customerStripe is called with the parameter object that is not loaded");
			return null;
		}

		$customer = null;

		$sql = "SELECT sa.key_account as key_account, sa.entity";			// key_account is cus_....
		$sql.= " FROM " . MAIN_DB_PREFIX . "societe_account as sa";
		$sql.= " WHERE sa.fk_soc = " . $object->id;
		$sql.= " AND sa.entity IN (".getEntity('societe').")";
		$sql.= " AND sa.site = 'stripe' AND sa.status = ".((int) $status);
		$sql.= " AND key_account IS NOT NULL AND key_account <> ''";

		dol_syslog(get_class($this) . "::customerStripe search stripe customer id for thirdparty id=".$object->id, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				$obj = $this->db->fetch_object($resql);
				$tiers = $obj->key_account;

				dol_syslog(get_class($this) . "::customerStripe found stripe customer key_account = ".$tiers);

				// Force to use the correct API key
				global $stripearrayofkeysbyenv;
				\Stripe\Stripe::setApiKey($stripearrayofkeysbyenv[$status]['secret_key']);

				try {
					if (empty($key)) {				// If the Stripe connect account not set, we use common API usage
						$customer = \Stripe\Customer::retrieve("$tiers");
					} else {
						$customer = \Stripe\Customer::retrieve("$tiers", array("stripe_account" => $key));
					}
				}
				catch(Exception $e)
				{
					// For exemple, we may have error: 'No such customer: cus_XXXXX; a similar object exists in live mode, but a test mode key was used to make this request.'
					$this->error = $e->getMessage();
				}
			}
			elseif ($createifnotlinkedtostripe)
			{
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
					if (! empty($conf->global->STRIPE_SAVE_TAX_IDS))	// We setup to save Tax info on Stripe side. Warning: This may result in error when saving customer
					{
						if (! empty($vatcleaned))
						{
							$isineec=isInEEC($object);
							if ($object->country_code && $isineec)
							{
								//$taxids = $customer->allTaxIds($customer->id);
								$customer->createTaxId($customer->id, array('type'=>'eu_vat', 'value'=>$vatcleaned));
							}
						}
					}

					// Create customer in Dolibarr
					$sql = "INSERT INTO " . MAIN_DB_PREFIX . "societe_account (fk_soc, login, key_account, site, status, entity, date_creation, fk_user_creat)";
					$sql .= " VALUES (".$object->id.", '', '".$this->db->escape($customer->id)."', 'stripe', " . $status . ", " . $conf->entity . ", '".$this->db->idate(dol_now())."', ".$user->id.")";
					$resql = $this->db->query($sql);
					if (! $resql)
					{
						$this->error = $this->db->lasterror();
					}
				}
				catch(Exception $e)
				{
					$this->error = $e->getMessage();
				}
			}
		}
		else
		{
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
		}
		catch(Exception $e)
		{
			$this->error = $e->getMessage();
		}

		return $stripepaymentmethod;
	}

    /**
	 * Get the Stripe payment intent. Create it with confirm=false
     * Warning. If a payment was tried and failed, a payment intent was created.
	 * But if we change someting on object to pay (amount or other), reusing same payment intent is not allowed.
	 * Recommanded solution is to recreate a new payment intent each time we need one (old one will be automatically closed after a delay),
	 * that's why i comment the part of code to retreive a payment intent with object id (never mind if we cumulate payment intent with old ones that will not be used)
	 *
	 * @param   double  $amount                             Amount
	 * @param   string  $currency_code                      Currency code
	 * @param   string  $tag                                Tag
	 * @param   string  $description                        Description
	 * @param	Societe	$object							    Object to pay with Stripe
	 * @param	string 	$customer							Stripe customer ref 'cus_xxxxxxxxxxxxx' via customerStripe()
	 * @param	string	$key							    ''=Use common API. If not '', it is the Stripe connect account 'acc_....' to use Stripe connect
	 * @param	int		$status							    Status (0=test, 1=live)
	 * @param	int		$usethirdpartyemailforreceiptemail	1=use thirdparty email for receipt
	 * @param	int		$mode		                        automatic=automatic confirmation/payment when conditions are ok, manual=need to call confirm() on intent
	 * @param   boolean $confirmnow                         false=default, true=try to confirm immediatly after create (if conditions are ok)
	 * @return 	\Stripe\PaymentIntent|null 			        Stripe PaymentIntent or null if not found
	 */
	public function getPaymentIntent($amount, $currency_code, $tag, $description = '', $object = null, $customer = null, $key = null, $status = 0, $usethirdpartyemailforreceiptemail = 0, $mode = 'automatic', $confirmnow = false)
	{
		global $conf;

		dol_syslog("getPaymentIntent");

		$error = 0;

		if (empty($status)) $service = 'StripeTest';
		else $service = 'StripeLive';

		$arrayzerounitcurrency=array('BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF');
		if (! in_array($currency_code, $arrayzerounitcurrency)) $stripeamount = $amount * 100;
		else $stripeamount = $amount;

		$fee = $amount * ($conf->global->STRIPE_APPLICATION_FEE_PERCENT / 100) + $conf->global->STRIPE_APPLICATION_FEE;
		if ($fee >= $conf->global->STRIPE_APPLICATION_FEE_MAXIMAL && $conf->global->STRIPE_APPLICATION_FEE_MAXIMAL > $conf->global->STRIPE_APPLICATION_FEE_MINIMAL) {
		    $fee = $conf->global->STRIPE_APPLICATION_FEE_MAXIMAL;
		} elseif ($fee < $conf->global->STRIPE_APPLICATION_FEE_MINIMAL) {
		    $fee = $conf->global->STRIPE_APPLICATION_FEE_MINIMAL;
		}
				if (! in_array($currency_code, $arrayzerounitcurrency)) $stripefee = round($fee * 100);
				else $stripefee = round($fee);

		$paymentintent = null;

		if (is_object($object))
		{
			// Warning. If a payment was tried and failed, a payment intent was created.
			// But if we change someting on object to pay (amount or other), reusing same payment intent is not allowed.
			// Recommanded solution is to recreate a new payment intent each time we need one (old one will be automatically closed after a delay),
			// that's why i comment the part of code to retreive a payment intent with object id (never mind if we cumulate payment intent with old that will not be used)
			/*
			$sql = "SELECT pi.ext_payment_id, pi.entity, pi.fk_facture, pi.sourcetype, pi.ext_payment_site";
    		$sql.= " FROM " . MAIN_DB_PREFIX . "prelevement_facture_demande as pi";
    		$sql.= " WHERE pi.fk_facture = " . $object->id;
    		$sql.= " AND pi.sourcetype = '" . $object->element . "'";
    		$sql.= " AND pi.entity IN (".getEntity('societe').")";
    		$sql.= " AND pi.ext_payment_site = '" . $service . "'";

    		dol_syslog(get_class($this) . "::getPaymentIntent search stripe payment intent for object id = ".$object->id, LOG_DEBUG);
    		$resql = $this->db->query($sql);
    		if ($resql) {
    			$num = $this->db->num_rows($resql);
    			if ($num)
    			{
    				$obj = $this->db->fetch_object($resql);
    				$intent = $obj->ext_payment_id;

    				dol_syslog(get_class($this) . "::getPaymentIntent found existing payment intent record");

    				// Force to use the correct API key
    				global $stripearrayofkeysbyenv;
    				\Stripe\Stripe::setApiKey($stripearrayofkeysbyenv[$status]['secret_key']);

    				try {
    					if (empty($key)) {				// If the Stripe connect account not set, we use common API usage
    						$paymentintent = \Stripe\PaymentIntent::retrieve($intent);
    					} else {
    						$paymentintent = \Stripe\PaymentIntent::retrieve($intent, array("stripe_account" => $key));
    					}
    				}
    				catch(Exception $e)
    				{
    				    $error++;
    					$this->error = $e->getMessage();
    				}
    			}
    		}*/
		}

		if (empty($paymentintent))
		{
    		$ipaddress=getUserRemoteIP();
    		$metadata = array('dol_version'=>DOL_VERSION, 'dol_entity'=>$conf->entity, 'ipaddress'=>$ipaddress);
            if (is_object($object))
            {
                $metadata['dol_type'] = $object->element;
                $metadata['dol_id'] = $object->id;
				if (is_object($object->thirdparty) && $object->thirdparty->id > 0) $metadata['dol_thirdparty_id'] = $object->thirdparty->id;
            }

    		$dataforintent = array(
    		    "confirm" => $confirmnow,	// Do not confirm immediatly during creation of intent
    		    "confirmation_method" => $mode,
    		    "amount" => $stripeamount,
    			"currency" => $currency_code,
    		    "payment_method_types" => array("card"),
    		    "description" => $description,
    		    "statement_descriptor" => dol_trunc($tag, 10, 'right', 'UTF-8', 1),     // 22 chars that appears on bank receipt (company + description)
    			//"save_payment_method" => true,
    			"metadata" => $metadata
    		);
    		if (! is_null($customer)) $dataforintent["customer"]=$customer;
    		// payment_method =
    		// payment_method_types = array('card')
            //var_dump($dataforintent);

    		if ($conf->entity!=$conf->global->STRIPECONNECT_PRINCIPAL && $stripefee > 0)
    		{
    			$dataforintent["application_fee_amount"] = $stripefee;
    		}
    		if ($usethirdpartyemailforreceiptemail && is_object($object) && $object->thirdparty->email)
    		{
    		    $dataforintent["receipt_email"] = $object->thirdparty->email;
    		}

    		try {
    			// Force to use the correct API key
    			global $stripearrayofkeysbyenv;
    			\Stripe\Stripe::setApiKey($stripearrayofkeysbyenv[$status]['secret_key']);

    			// Note: If all data for payment intent are same than a previous on, even if we use 'create', Stripe will return ID of the old existing payment intent.
    			if (empty($key)) {				// If the Stripe connect account not set, we use common API usage
    				$paymentintent = \Stripe\PaymentIntent::create($dataforintent, array("idempotency_key" => "$description"));
    			    //$paymentintent = \Stripe\PaymentIntent::create($dataforintent, array());
    			} else {
    				$paymentintent = \Stripe\PaymentIntent::create($dataforintent, array("idempotency_key" => "$description", "stripe_account" => $key));
    			    //$paymentintent = \Stripe\PaymentIntent::create($dataforintent, array("stripe_account" => $key));
    			}
    			//var_dump($paymentintent->id);

    			// Store the payment intent
    			if (is_object($object))
    			{
    				$paymentintentalreadyexists = 0;
    				// Check that payment intent $paymentintent->id is not already recorded.
    				$sql = "SELECT pi.rowid";
    				$sql.= " FROM " . MAIN_DB_PREFIX . "prelevement_facture_demande as pi";
    				$sql.= " WHERE pi.entity IN (".getEntity('societe').")";
    				$sql.= " AND pi.ext_payment_site = '" . $service . "'";
    				$sql.= " AND pi.ext_payment_id = '".$this->db->escape($paymentintent->id)."'";

    				dol_syslog(get_class($this) . "::getPaymentIntent search if payment intent already in prelevement_facture_demande", LOG_DEBUG);
    				$resql = $this->db->query($sql);
    				if ($resql) {
    					$num = $this->db->num_rows($resql);
    					if ($num)
    					{
    						$obj = $this->db->fetch_object($resql);
    						if ($obj) $paymentintentalreadyexists++;
    					}
    				}
    				else dol_print_error($this->db);

    				// If not, we create it.
    				if (! $paymentintentalreadyexists)
    				{
	    				$now=dol_now();
	    				$sql = "INSERT INTO " . MAIN_DB_PREFIX . "prelevement_facture_demande (date_demande, fk_user_demande, ext_payment_id, fk_facture, sourcetype, entity, ext_payment_site)";
	    				$sql .= " VALUES ('".$this->db->idate($now)."', '0', '".$this->db->escape($paymentintent->id)."', ".$object->id.", '".$this->db->escape($object->element)."', " . $conf->entity . ", '" . $service . "')";
	    				$resql = $this->db->query($sql);
	    				if (! $resql)
	    				{
	    				    $error++;
	    					$this->error = $this->db->lasterror();
	                        dol_syslog(get_class($this) . "::PaymentIntent failed to insert paymentintent with id=".$paymentintent->id." into database.");
	    				}
    				}
    			}
    			else
    			{
    			    $_SESSION["stripe_payment_intent"] = $paymentintent;
    			}
    		}
    		catch(Exception $e)
    		{
    		    /*var_dump($dataforintent);
    		    var_dump($description);
    		    var_dump($key);
    		    var_dump($paymentintent);
    		    var_dump($e->getMessage());*/
                $error++;
    			$this->error = $e->getMessage();
    		}
		}

		dol_syslog("getPaymentIntent return error=".$error);

		if (! $error)
		{
			return $paymentintent;
		}
		else
		{
			return null;
		}
	}

	/**
	 * Get the Stripe card of a company payment mode (with option to create it on Stripe if not linked yet)
	 *
	 * @param	\Stripe\StripeCustomer	$cu								Object stripe customer
	 * @param	CompanyPaymentMode		$object							Object companypaymentmode to check, or create on stripe (create on stripe also update the societe_rib table for current entity)
	 * @param	string					$stripeacc						''=Use common API. If not '', it is the Stripe connect account 'acc_....' to use Stripe connect
	 * @param	int						$status							Status (0=test, 1=live)
	 * @param	int						$createifnotlinkedtostripe		1=Create the stripe card and the link if the card is not yet linked to a stripe card
	 * @return 	\Stripe\StripeCard|null 								Stripe Card or null if not found
	 */
	public function cardStripe($cu, CompanyPaymentMode $object, $stripeacc = '', $status = 0, $createifnotlinkedtostripe = 0)
	{
		global $conf, $user;

		$card = null;

		$sql = "SELECT sa.stripe_card_ref, sa.proprio, sa.exp_date_month, sa.exp_date_year, sa.number, sa.cvn";			// stripe_card_ref is card_....
		$sql.= " FROM " . MAIN_DB_PREFIX . "societe_rib as sa";
		$sql.= " WHERE sa.rowid = " . $object->id;		// We get record from ID, no need for filter on entity
		$sql.= " AND sa.type = 'card'";

		dol_syslog(get_class($this) . "::fetch search stripe card id for paymentmode id=".$object->id.", stripeacc=".$stripeacc.", status=".$status.", createifnotlinkedtostripe=".$createifnotlinkedtostripe, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				$obj = $this->db->fetch_object($resql);
				$cardref = $obj->stripe_card_ref;
				dol_syslog(get_class($this) . "::cardStripe cardref=".$cardref);
				if ($cardref)
				{
					try {
						if (empty($stripeacc)) {				// If the Stripe connect account not set, we use common API usage
							$card = $cu->sources->retrieve($cardref);
						} else {
							//$card = $cu->sources->retrieve($cardref, array("stripe_account" => $stripeacc));		// this API fails when array stripe_account is provided
							$card = $cu->sources->retrieve($cardref);
						}
					}
					catch(Exception $e)
					{
						$this->error = $e->getMessage();
						dol_syslog($this->error, LOG_WARNING);
					}
				}
				elseif ($createifnotlinkedtostripe)
				{
					$exp_date_month=$obj->exp_date_month;
					$exp_date_year=$obj->exp_date_year;
					$number=$obj->number;
					$cvc=$obj->cvn;								// cvn in database, cvc for stripe
					$cardholdername=$obj->proprio;

					$dataforcard = array(
						"source" => array('object'=>'card', 'exp_month'=>$exp_date_month, 'exp_year'=>$exp_date_year, 'number'=>$number, 'cvc'=>$cvc, 'name'=>$cardholdername),
						"metadata" => array('dol_id'=>$object->id, 'dol_version'=>DOL_VERSION, 'dol_entity'=>$conf->entity, 'ipaddress'=>(empty($_SERVER['REMOTE_ADDR'])?'':$_SERVER['REMOTE_ADDR']))
					);

					//$a = \Stripe\Stripe::getApiKey();
					//var_dump($a);var_dump($stripeacc);exit;
					dol_syslog("Try to create card dataforcard = ".json_encode($dataforcard));
					try {
						if (empty($stripeacc)) {				// If the Stripe connect account not set, we use common API usage
							$card = $cu->sources->create($dataforcard);
						} else {
							$card = $cu->sources->create($dataforcard, array("stripe_account" => $stripeacc));
						}

						if ($card)
						{
							$sql = "UPDATE " . MAIN_DB_PREFIX . "societe_rib";
							$sql.= " SET stripe_card_ref = '".$this->db->escape($card->id)."', card_type = '".$this->db->escape($card->brand)."',";
							$sql.= " country_code = '".$this->db->escape($card->country)."',";
							$sql.= " approved = ".($card->cvc_check == 'pass' ? 1 : 0);
							$sql.= " WHERE rowid = " . $object->id;
							$sql.= " AND type = 'card'";
							$resql = $this->db->query($sql);
							if (! $resql)
							{
								$this->error = $this->db->lasterror();
							}
						}
						else
						{
							$this->error = 'Call to cu->source->create return empty card';
						}
					}
					catch(Exception $e)
					{
						$this->error = $e->getMessage();
						dol_syslog($this->error, LOG_WARNING);
					}
				}
			}
		}
		else
		{
			dol_print_error($this->db);
		}

		return $card;
	}

	/**
	 * Create charge with public/payment/newpayment.php, stripe/card.php, cronjobs or REST API
	 *
	 * @param	int 	$amount									Amount to pay
	 * @param	string 	$currency								EUR, GPB...
	 * @param	string 	$origin									Object type to pay (order, invoice, contract...)
	 * @param	int 	$item									Object id to pay
	 * @param	string 	$source									src_xxxxx or card_xxxxx
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

		if (empty($status)) $service = 'StripeTest';
		else $service = 'StripeLive';

		$sql = "SELECT sa.key_account as key_account, sa.fk_soc, sa.entity";
		$sql.= " FROM " . MAIN_DB_PREFIX . "societe_account as sa";
		$sql.= " WHERE sa.key_account = '" . $this->db->escape($customer) . "'";
		//$sql.= " AND sa.entity IN (".getEntity('societe').")";
		$sql.= " AND sa.site = 'stripe' AND sa.status = ".((int) $status);

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
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

		$arrayzerounitcurrency=array('BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'MGA', 'PYG', 'RWF', 'VND', 'VUV', 'XAF', 'XOF', 'XPF');
		if (! in_array($currency, $arrayzerounitcurrency)) $stripeamount=$amount * 100;
		else $stripeamount = $amount;

		$societe = new Societe($this->db);
		if ($key > 0) $societe->fetch($key);

		$description = "";
		$ref = "";
		if ($origin == order) {
			$order = new Commande($this->db);
			$order->fetch($item);
			$ref = $order->ref;
			$description = "ORD=" . $ref . ".CUS=" . $societe->id.".PM=stripe";
		} elseif ($origin == invoice) {
			$invoice = new Facture($this->db);
			$invoice->fetch($item);
			$ref = $invoice->ref;
			$description = "INV=" . $ref . ".CUS=" . $societe->id.".PM=stripe";
		}

		$metadata = array(
			"dol_id" => "" . $item . "",
			"dol_type" => "" . $origin . "",
			"dol_thirdparty_id" => "" . $societe->id . "",
			'dol_thirdparty_name' => $societe->name,
			'dol_version'=>DOL_VERSION,
			'dol_entity'=>$conf->entity,
			'ipaddress'=>(empty($_SERVER['REMOTE_ADDR'])?'':$_SERVER['REMOTE_ADDR'])
		);
		$return = new Stripe($this->db);
		try {
			// Force to use the correct API key
			global $stripearrayofkeysbyenv;
			\Stripe\Stripe::setApiKey($stripearrayofkeysbyenv[$status]['secret_key']);

			if (empty($conf->stripeconnect->enabled))
			{
				if (preg_match('/acct_/i', $source))
				{
                    $charge = \Stripe\Charge::create(array(
						"amount" => "$stripeamount",
						"currency" => "$currency",
                        "statement_descriptor" => dol_trunc($description, 10, 'right', 'UTF-8', 1),     // 22 chars that appears on bank receipt (company + description)
						"description" => "Stripe payment: ".$description,
						"capture"  => $capture,
						"metadata" => $metadata,
						"source" => "$source"
					));
				} else {
					$paymentarray = array(
						"amount" => "$stripeamount",
						"currency" => "$currency",
					    "statement_descriptor" => dol_trunc($description, 10, 'right', 'UTF-8', 1),     // 22 chars that appears on bank receipt (company + description)
						"description" => "Stripe payment: ".$description,
						"capture"  => $capture,
						"metadata" => $metadata,
						"source" => "$source",
						"customer" => "$customer"
					);

					if ($societe->email && $usethirdpartyemailforreceiptemail)
					{
						$paymentarray["receipt_email"] = $societe->email;
					}

					$charge = \Stripe\Charge::create($paymentarray, array("idempotency_key" => "$description"));
				}
			} else {
		$fee = $amount * ($conf->global->STRIPE_APPLICATION_FEE_PERCENT / 100) + $conf->global->STRIPE_APPLICATION_FEE;
		if ($fee >= $conf->global->STRIPE_APPLICATION_FEE_MAXIMAL && $conf->global->STRIPE_APPLICATION_FEE_MAXIMAL > $conf->global->STRIPE_APPLICATION_FEE_MINIMAL) {
		    $fee = $conf->global->STRIPE_APPLICATION_FEE_MAXIMAL;
		} elseif ($fee < $conf->global->STRIPE_APPLICATION_FEE_MINIMAL) {
		    $fee = $conf->global->STRIPE_APPLICATION_FEE_MINIMAL;
		}
				if (! in_array($currency, $arrayzerounitcurrency)) $stripefee = round($fee * 100);
				else $stripefee = round($fee);

        		$paymentarray = array(
					"amount" => "$stripeamount",
					"currency" => "$currency",
        		    "statement_descriptor" => dol_trunc($description, 10, 'right', 'UTF-8', 1),     // 22 chars that appears on bank receipt (company + description)
					"description" => "Stripe payment: ".$description,
					"capture"  => $capture,
					"metadata" => $metadata,
					"source" => "$source",
					"customer" => "$customer"
				);
				if ($conf->entity!=$conf->global->STRIPECONNECT_PRINCIPAL && $stripefee > 0)
				{
					$paymentarray["application_fee_amount"] = $stripefee;
				}
				if ($societe->email && $usethirdpartyemailforreceiptemail)
				{
					$paymentarray["receipt_email"] = $societe->email;
				}

				$charge = \Stripe\Charge::create($paymentarray, array("idempotency_key" => "$description", "stripe_account" => "$account"));
			}
			if (isset($charge->id)) {}

			$return->statut = 'success';
			$return->id = $charge->id;
			if ($charge->source->type == 'card') {
				$return->message = $charge->source->card->brand . " ...." . $charge->source->card->last4;
			} elseif ($charge->source->type == 'three_d_secure') {
				$stripe = new Stripe($this->db);
				$src = \Stripe\Source::retrieve("" . $charge->source->three_d_secure->card . "", array(
				"stripe_account" => $stripe->getStripeAccount($service)
				));
				$return->message = $src->card->brand . " ...." . $src->card->last4;
			} else {
				$return->message = $charge->id;
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
			$body = "Error: <br>" . $return->id . " " . $return->message . " ";
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
