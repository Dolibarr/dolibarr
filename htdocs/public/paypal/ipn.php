<?php
/* Copyright (C) 2011 Regis Houssin	<regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *e
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *     	\file       htdocs/public/paypal/ipn.php
 *		\ingroup    paypal
 *		\brief      Instant Payment Notification script (IPN)
 *					Send an e-mail when the seller has received a Paypal payment.
 *					If the transaction is OK, PayPal has the script connects and sends data, then the script sends an e-mail summarizing the seller.
 *					Add the URL of the script during the creation of a Paypal button or the preferences of his Paypal account was: Preferences Instant Payment Notification.
 *		\version    $Id: ipn.php,v 1.2 2011/07/08 18:08:28 hregis Exp $
 */

if (! defined('NOLOGIN'))			define("NOLOGIN",1);		// This means this output page does not require to be logged.
if (! defined('NOCSRFCHECK'))		define("NOCSRFCHECK",1);	// We accept to go on this page from external web site.
if (! defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL',1);	// Disables token renewal
if (! defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX',1);
if (! defined('NOREQUIREMENU'))		define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))		define('NOREQUIREHTML',1);

function llxHeader() { }

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/CMailFile.class.php');


if (! empty($conf->paypal->enabled) && ! empty($conf->global->PAYPAL_IPN_MAIL_ADDRESS) && ! empty($_POST) && $_GET['token'] == $conf->global->PAYPAL_SECURITY_TOKEN)
{
	$langs->load("main");
	$langs->load("other");
	
	
	// read the post from PayPal system and add 'cmd'
	$req = 'cmd=_notify-validate';
	
	foreach ($_POST as $key => $value) {
		$value=GETPOST($key);
		$$key=$value;
		$value = trim(urlencode(stripslashes($value)));
		$req .= '&'.$key.'='.$value;
	}
	
	// post back to PayPal system to validate
	$header .= "POST /cgi-bin/webscr HTTP/1.0\r\n";
	$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
	$fp = fsockopen ('ssl://www.paypal.com', 443, $errno, $errstr, 30);
	
	if (!$fp) {
		// HTTP ERROR
	} else {
		fputs ($fp, $header . $req);
		while (!feof($fp))
		{
			$res = fgets ($fp, 1024);
			if (strcmp ($res, "VERIFIED") == 0)
			{
				// From
				$from = GETPOST('receiver_email','',2);
				
				// To
				$sendto = GETPOST('payer_email','',2);
				
				// CC
				$sendtocc = GETPOST('receiver_email','',2);
				
				// Send mail
				// TODO add translation
				$subject = "[PAYPAL] Paiement PAYPAL valide et verifie";
				
				$message = "Paypal vient de valider et recevoir un paiement par carte bancaire. \nConnectez-vous a votre compte Paypal pour connaitre les details de cette transaction.";
				$message.= "\n";
				$message.= "\n====================================================";
				$message.= "\n" . "Voici quelques informations sur la transaction:\n";
				$message.= "\n" . "Transaction ID: " .  $txn_id ;
				$message.= "\n" . "Date de paiement: " . $payment_date;
				$message.= "\n" . "Etat du paiement: " . $payment_status;
				$message.= "\n====================================================";
				$message.= "\n";
				$message.= "\n" . "Attention, les informations ci-dessous peuvent etre incompletes.";
				$message.= "\n====================================================";
				$message.= "\n" . "Nombre d'objets dans le panier: " . $num_cart_items;
				
				$i=1;
				
				// Items
				while(true)
				{
					if (strlen($item_name.$i) > 1) $message .= "\n\n" . "1er objet en commande: " . $item_name.$i . "\n" . "Numero de l'objet 1: " . $item_number.$i . " - " . "Quantite: " . $quantity.$i;
					else break;
					
					$i++;
				}
				if (strlen($item_name) > 1) {
					$message.= "\n" . "Objet en commande: " . $item_name;
					$message.= "\n" . "Numero de l'objet: " . $item_number;
					$message.= "\n" . "Quantite: " . $quantity;
				}
				
				$message.= "\n";
				$message.= "\n" . "Facture numero: " . $invoice;
				$message.= "\n" . "Montant: " . $mc_gross . " " .$mc_currency;
				$message.= "\n" . "Frais Paypal: " . $mc_fee . " " .$mc_currency;
				$message.= "\n" . "Montant sur le compte: " . ($mc_gross - $mc_fee) . " " .$mc_currency;
				$message.= "\n";
				$message.= "\n" . "Nom: " . $first_name . " " .$last_name;
				$message.= "\n" . "Rue: " . $address_street;
				$message.= "\n" . "Code postal: " . $address_zip;
				$message.= "\n" . "Ville: " . $address_city;
				$message.= "\n" . "Etat et Pays: " . $address_state . " " .$address_country . " " .$address_country_code;
				$message.= "\n" . "Adresse e-mail: " . $payer_email;
				$message.= "\n" . "Nom de l'entreprise: " . $payer_business_name;
				$message.= "\n";
				$message.= "\n" . "Message du client: " . $memo;
				$message.= "\n";
				$message.= "\n" . "Statut Paypal du client: " . $payer_status;
				$message.= "\n====================================================";
				$message.= "\n\n" . "Voici les donnees brutes envoyées par Paypal:";
				$message.= "\n\n";
				foreach ($_POST as $key => $value) {
					$message.= $key . " = " .$value ."\n";
				}
				
				$mail = new CMailFile($subject,$sendto,$from,$message);
				if (! $mail->error) $result=$mail->sendfile();
			}
			else if (strcmp ($res, "INVALID") == 0)
			{
				// From
				$from = GETPOST('receiver_email','',2);
				
				// To
				$sendto = GETPOST('receiver_email','',2);
				
				// Envoi d'un mail si invalide
				$subject = "[PAYPAL] Paiement PAYPAL NON VALIDE";
				
				$message = "Un client a voulu payer par Paypal mais la transaction n'est pas valide. La commande est annulee.";
				$message.= "\n" . "Ce message est envoye pour information, il n'y a rien a faire.";
				$message.= "\n";
				$message.= "\n====================================================";
				$message.= "\n\n" . "Voici les donnees brutes envoyées par Paypal:";
				$message.= "\n\n";
				foreach ($_POST as $key => $value) {
					$message.= $key . " = " .$value ."\n";
				}
				
				$mail = new CMailFile($subject,$sendto,$from,$message);
				if (! $mail->error) $result=$mail->sendfile();
			}
		}
		fclose ($fp);
	}
}
else
{
	accessforbidden('',1,1,1);
}

$db->close();

?>
