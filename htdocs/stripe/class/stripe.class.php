<?php
/* Copyright (C) 2018 	PtibogXIV        <support@ptibogxiv.net>
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
	public function getStripeAccount($mode='StripeTest')
	{
		global $conf;

		$sql = "SELECT tokenstring";
		$sql.= " FROM ".MAIN_DB_PREFIX."oauth_token";
		$sql.= " WHERE entity = ".$conf->entity;
		$sql.= " AND service = '".$mode."'";

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
    	if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
    			$tokenstring=$obj->tokenstring;

    			$tmparray = dol_json_decode($tokenstring);
    			$key = $tmparray->stripe_user_id;
    		}
    		else {
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
	public function getStripeCustomerAccount($id, $status=0)
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
	public function customerStripe(Societe $object, $key='', $status=0, $createifnotlinkedtostripe=0)
	{
		global $conf, $user;

		if (empty($object->id))
		{
			dol_syslog("customerStripe is called with param object not loaded");
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
					$this->error = $e->getMessage();
				}
			}
			elseif ($createifnotlinkedtostripe)
			{
				$dataforcustomer = array(
					"email" => $object->email,
					"description" => $object->name,
					"metadata" => array('dol_id'=>$object->id, 'dol_version'=>DOL_VERSION, 'dol_entity'=>$conf->entity, 'ipaddress'=>(empty($_SERVER['REMOTE_ADDR'])?'':$_SERVER['REMOTE_ADDR']))
				);

				$vatcleaned = $object->tva_intra ? $object->tva_intra : null;

				$taxinfo = array('type'=>'vat');
				if ($vatcleaned)
				{
					$taxinfo["tax_id"] = $vatcleaned;
				}
				// We force data to "null" if not defined as expected by Stripe
				if (empty($vatcleaned)) $taxinfo=null;

				$dataforcustomer["tax_info"] = $taxinfo;

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
	 * Get the Stripe card of a company payment mode (with option to create it on Stripe if not linked yet)
	 *
	 * @param	\Stripe\StripeCustomer	$cu								Object stripe customer
	 * @param	CompanyPaymentMode		$object							Object companypaymentmode to check, or create on stripe (create on stripe also update the societe_rib table for current entity)
	 * @param	string					$stripeacc						''=Use common API. If not '', it is the Stripe connect account 'acc_....' to use Stripe connect
	 * @param	int						$status							Status (0=test, 1=live)
	 * @param	int						$createifnotlinkedtostripe		1=Create the stripe card and the link if the card is not yet linked to a stripe card
	 * @return 	\Stripe\StripeCard|null 								Stripe Card or null if not found
	 */
	public function cardStripe($cu, CompanyPaymentMode $object, $stripeacc='', $status=0, $createifnotlinkedtostripe=0)
	{
		global $conf, $user;

		$card = null;

		$sql = "SELECT sa.stripe_card_ref, sa.proprio, sa.exp_date_month, sa.exp_date_year, sa.number, sa.cvn";			// stripe_card_ref is card_....
		$sql.= " FROM " . MAIN_DB_PREFIX . "societe_rib as sa";
		$sql.= " WHERE sa.rowid = " . $object->id;
		//$sql.= " AND sa.entity IN (".getEntity('societe').")";
		$sql.= " AND sa.type = 'card'";

		dol_syslog(get_class($this) . "::fetch search stripe card id for paymentmode id=".$object->id.", stripeacc=".$stripeacc.", status=".$status.", createifnotlinkedtostripe=".$createifnotlinkedtostripe, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				$obj = $this->db->fetch_object($resql);
				$cardref = $obj->stripe_card_ref;
				dol_syslog("************* cardref=".$cardref);
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
					dol_syslog("Try to create card dataforcard = ".dol_json_encode($dataforcard));
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
	public function createPaymentStripe($amount, $currency, $origin, $item, $source, $customer, $account, $status=0, $usethirdpartyemailforreceiptemail=0, $capture=true)
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
						"statement_descriptor" => dol_trunc(dol_trunc(dol_string_unaccent($mysoc->name), 8, 'right', 'UTF-8', 1).' '.$description, 22, 'right', 'UTF-8', 1),     // 22 chars that appears on bank receipt
						"description" => "Stripe payment: ".$description,
						"capture"  => $capture,
						"metadata" => $metadata,
						"source" => "$source"
					));
				} else {
					$paymentarray = array(
						"amount" => "$stripeamount",
						"currency" => "$currency",
						"statement_descriptor" => dol_trunc(dol_trunc(dol_string_unaccent($mysoc->name), 8, 'right', 'UTF-8', 1).' '.$description, 22, 'right', 'UTF-8', 1),     // 22 chars that appears on bank receipt
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

				$fee = round(($amount * ($conf->global->STRIPE_APPLICATION_FEE_PERCENT / 100) + $conf->global->STRIPE_APPLICATION_FEE) * 100);
				if ($fee < ($conf->global->STRIPE_APPLICATION_FEE_MINIMAL * 100)) {
					$fee = round($conf->global->STRIPE_APPLICATION_FEE_MINIMAL * 100);
				}

        		$paymentarray = array(
						"amount" => "$stripeamount",
						"currency" => "$currency",
						"statement_descriptor" => dol_trunc(dol_trunc(dol_string_unaccent($mysoc->name), 8, 'right', 'UTF-8', 1).' '.$description, 22, 'right', 'UTF-8', 1),     // 22 chars that appears on bank receipt
						"description" => "Stripe payment: ".$description,
						"capture"  => $capture,
						"metadata" => $metadata,
						"source" => "$source",
						"customer" => "$customer"
					);
					if ($conf->entity!=$conf->global->STRIPECONNECT_PRINCIPAL && $fee>0)
					{
						$paymentarray["application_fee"] = $fee;
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
