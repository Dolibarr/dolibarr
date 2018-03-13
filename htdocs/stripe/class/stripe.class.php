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
require_once DOL_DOCUMENT_ROOT.'/stripe/config.php';


/**
 *	Stripe class
 */
class Stripe extends CommonObject
{
	public $rowid;
	public $fk_soc;
	public $fk_key;
	public $id;
	public $mode;
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
	 * @return 	int					???
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

		return $key;
	}

	/**
	 * getStripeCustomerAccount
	 *
	 * @param	int		$id		Id of third party
	 * @param	int		$status		Status
	 * @return	string				Stripe customer ref 'cu_xxxxxxxxxxxxx'
	 */
	public function getStripeCustomerAccount($id, $status=0)
	{
		global $conf;

		$sql = "SELECT sa.key_account as key_account, sa.entity";
		$sql.= " FROM " . MAIN_DB_PREFIX . "societe_account as sa";
		$sql.= " WHERE sa.fk_soc = " . $id;
		$sql.= " AND sa.entity IN (".getEntity('societe').")";
		$sql.= " AND sa.site = 'stripe' AND sa.status = ".((int) $status);

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$key = $obj->key_account;
			} else {
				$key = NULL;
			}
		} else {
			$key = NULL;
		}

		return $key;
	}


	/**
	 * customerStripe
	 *
	 * @param int	 $id		???
	 * @param string $key		???
	 * @return \Stripe\StripeObject|\Stripe\ApiResource
	 */
	public function customerStripe($id,$key)
	{
		global $conf;
		if (empty($conf->global->STRIPECONNECT_LIVE)) {
			$mode = 0;
		} else {
			if (empty($conf->global->STRIPE_LIVE)) {
				$mode = 0;
			} else {
				$mode = $conf->global->STRIPE_LIVE;
			}
		}
		$sql = "SELECT rowid,fk_soc,fk_key,mode,entity";
		$sql .= " FROM " . MAIN_DB_PREFIX . "societe_stripe";
		$sql .= " WHERE fk_soc = " . $id . " ";
		$sql .= " AND  mode=" . $mode . " AND entity IN (" . getEntity('stripe') . ")";

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$soc = new Societe($this->db);
			$soc->fetch($id);
			$num = $this->db->num_rows($resql);
			if ($num) {
				$obj = $this->db->fetch_object($resql);
				$tiers = $obj->fk_key;
				if ($conf->entity == 1) {
					$customer = \Stripe\Customer::retrieve("$tiers");
				} else {
					$customer = \Stripe\Customer::retrieve("$tiers", array(
					"stripe_account" => $key
					));
				}
			} else {
				if ($conf->entity == 1) {
					$customer = \Stripe\Customer::create(array(
					"email" => $soc->email,
					"description" => $soc->name
					));
				} else {
					$customer = \Stripe\Customer::create(array(
					"email" => $soc->email,
					"description" => $soc->name
					), array(
					"stripe_account" => $key
					));
				}
				$customer_id = "" . $customer->id . "";
				$sql = "INSERT INTO " . MAIN_DB_PREFIX . "societe_stripe (fk_soc,fk_key,mode,entity)";
				$sql .= " VALUES ($id,'$customer_id'," . $mode . "," . $conf->entity . ")";
				dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
				$resql = $this->db->query($sql);
			}
		}
		return $customer;
	}

	/**
	 * createPaymentStripe
	 *
	 * @param unknown $amount			???
	 * @param unknown $currency			???
	 * @param unknown $origin			???
	 * @param unknown $item				???
	 * @param unknown $source			???
	 * @param unknown $customer			???
	 * @param unknown $account			???
	 * @return Stripe
	 */
	public function createPaymentStripe($amount, $currency, $origin, $item, $source, $customer, $account)
	{
		global $conf;

		if (empty($conf->global->STRIPECONNECT_LIVE)) {
			$mode = 0;
		} else {
			if (empty($conf->global->STRIPE_LIVE)) {
				$mode = 0;
			} else {
				$mode = $conf->global->STRIPE_LIVE;
			}
		}
		$sql = "SELECT fk_soc,fk_key,mode,entity";
		$sql .= " FROM " . MAIN_DB_PREFIX . "societe_stripe";
		$sql .= " WHERE fk_key = '$customer' ";
		$sql .= " AND mode=" . $mode . " ";

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$entite = $obj->entity;
				$fksoc = $obj->fk_soc;
			}
		}
		$stripeamount = round($amount * 100);
		$societe = new Societe($this->db);
		$societe->fetch($fksoc);

		if ($origin == order) {
			$order = new Commande($this->db);
			$order->fetch($item);
			$ref = $order->ref;
			$description = "ORD=" . $ref . ".CUS=" . $societe->code_client;
		} elseif ($origin == invoice) {
			$invoice = new Facture($this->db);
			$invoice->fetch($item);
			$ref = $invoice->ref;
			$description = "INV=" . $ref . ".CUS=" . $societe->code_client;
		}

		$metadata = array(
		"source" => "" . $origin . "",
		"idsource" => "" . $item . "",
		"idcustomer" => "" . $societe->id . ""
		);
		$return = new Stripe($this->db);
		try {
			if ($stripeamount >= 100) {
				if ($entite == '1' or empty($conf->stripeconnect->enabled)) {
					if (preg_match('/acct_/i', $source)) {
						$charge = \Stripe\Charge::create(array(
						"amount" => "$stripeamount",
						"currency" => "$currency",
						// "statement_descriptor" => " ",
						"metadata" => $metadata,
						"source" => "$source"
						));
					} else {
						$charge = \Stripe\Charge::create(array(
						"amount" => "$stripeamount",
						"currency" => "$currency",
						// "statement_descriptor" => " ",
						"description" => "$description",
						"metadata" => $metadata,
						"receipt_email" => $societe->email,
						"source" => "$source",
						"customer" => "$customer"
						), array(
						"idempotency_key" => "$ref"
						));
					}
				} else {
					$fee = round(($amount * ($conf->global->STRIPE_APPLICATION_FEE_PERCENT / 100) + $conf->global->STRIPE_APPLICATION_FEE) * 100);
					if ($fee < ($conf->global->STRIPE_APPLICATION_FEE_MINIMAL * 100)) {
						$fee = round($conf->global->STRIPE_APPLICATION_FEE_MINIMAL * 100);
					}
					$charge = \Stripe\Charge::create(array(
					"amount" => "$stripeamount",
					"currency" => "$currency",
					// "statement_descriptor" => " ",
					"description" => "$description",
					"metadata" => $metadata,
					"source" => "$source",
					"customer" => "$customer",
					"application_fee" => "$fee"
					), array(
					"idempotency_key" => "$ref",
					"stripe_account" => "$account"
					));
				}
				if (isset($charge->id)) {}
			}

			if (empty($conf->global->STRIPE_LIVE) || GETPOST('forcesandbox','alpha')) $service = 'StripeTest';
			else $service = 'StripeLive';

			$return->statut = 'success';
			$return->id = $charge->id;
			if ($charge->source->type == 'card') {
				$return->message = $charge->source->card->brand . " ****" . $charge->source->card->last4;
			} elseif ($charge->source->type == 'three_d_secure') {
				$stripe = new Stripe($this->db);
				$src = \Stripe\Source::retrieve("" . $charge->source->three_d_secure->card . "", array(
				"stripe_account" => $stripe->getStripeAccount($service)
				));
				$return->message = $src->card->brand . " ****" . $src->card->last4;
			} else {
				$return->message = $charge->id;
			}
		} catch (\Stripe\Error\Card $e) {
			// Since it's a decline, \Stripe\Error\Card will be caught
			$body = $e->getJsonBody();
			$err = $body['error'];

			$return->statut = 'error';
			$return->id = $err['charge'];
			$return->type = $err['type'];
			$return->code = $err['code'];
			$return->message = $err['message'];
			$body = "Error: <br>" . $return->id . " " . $return->message . " ";
			$subject = '[NOTIFICATION] Erreur de paiement';
			$headers = 'From: "noreply" <' . $conf->global->MAIN_INFO_SOCIETE_MAIL . '>';
			mail('' . $conf->global->MAIN_INFO_SOCIETE_MAIL . '', $subject, $body, $headers);
			$error ++;
			dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
		} catch (\Stripe\Error\RateLimit $e) {
			// Too many requests made to the API too quickly
			$error ++;
			dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
		} catch (\Stripe\Error\InvalidRequest $e) {
			// Invalid parameters were supplied to Stripe's API
			$error ++;
			dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
		} catch (\Stripe\Error\Authentication $e) {
			// Authentication with Stripe's API failed
			// (maybe you changed API keys recently)
			$error ++;
			dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
		} catch (\Stripe\Error\ApiConnection $e) {
			// Network communication with Stripe failed
			$error ++;
			dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
		} catch (\Stripe\Error\Base $e) {
			// Display a very generic error to the user, and maybe send
			// yourself an email
			$error ++;
			dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
		} catch (Exception $e) {
			// Something else happened, completely unrelated to Stripe
			$error ++;
			dol_syslog($e->getMessage(), LOG_WARNING, 0, '_stripe');
		}
		return $return;
	}

}
