<?php
/* Copyright (C) 2017		Alexandre Spangaro		<aspangaro@zendsi.com>
 * Copyright (C) 2017		Saasprov				<saasprov@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require '../main.inc.php';
 
// Load Dolibarr environment
require_once DOL_DOCUMENT_ROOT.'/stripe/config.php');
require_once DOL_DOCUMENT_ROOT.'/includes/stripe/init.php');

define("NOLOGIN",1);
define("NOCSRFCHECK",1);

$langs->load("main");
$langs->load("other");
$langs->load("stripe");

$source=GETPOST("source",'alpha');
$ref=GETPOST('ref','alpha');

$form = new Form($db);

/**
 * Header empty
 *
 * @return	void
 */
function llxHeader() {}
/**
 * Footer empty
 *
 * @return	void
 */
function llxFooter() {}

$invoice = null;

// Payment on customer invoice
if ($source == 'invoice')
{
	$found=true;
	$langs->load("bills");

	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

	$invoice=new Facture($db);
	$result=$invoice->fetch('',$ref);
	if ($result < 0)
	{
		$mesg=$invoice->error;
		$error++;
	}
	else
	{
		$result=$invoice->fetch_thirdparty($invoice->socid);
	}


}

$pay = false;
$ttc = $invoice->total_ttc ;
$ttc = $ttc * 100;
if (GETPOST("action") == 'charge')
{
  $token = GETPOST("stripeToken");
  $email = GETPOST("stripeEmail");

  $customer = \Stripe\Customer::create(array(
      'email' => $email,
      'card'  => $token
  ));
	
	$ttc = round($ttc, 2);
  $charge = \Stripe\Charge::create(array(
      'customer' => $customer->id,
      'amount'   => $ttc,
      'currency' => $conf->currency,
      'description' => 'Invoice payment N: '.$ref
  ));

	$pay = true;
	
}



?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title><?php echo $langs->trans('PaymentForm'); ?></title>
    <link rel='stylesheet' type='text/css' href='css/style.css' />
</head>

<body>
    <div class="invoice-box">		
        <table cellpadding="0" cellspacing="0">
            <tr class="top">
                <td colspan="2">
                    <table>
                        <tr>
                            <td class="title">                                
								<?php 
									if(empty($pay)):
									print '<form  action="' . $_SERVER['REQUEST_URI'] . '" method="POST">';
									print '<input type="hidden" name="action" value="charge" />';
									
								?>
								  <script src="https://checkout.stripe.com/checkout.js" 
										class="stripe-button"
										data-key="<?php echo $stripe['publishable_key']; ?>"
										data-amount="<?php echo $ttc; ?>"
										data-currency="<?php echo $conf->currency; ?>"
										data-description="<?php echo 'Invoice payment N: '.$ref; ?>">
									</script>
								</form>
								<?php endif; ?>
                            </td>
                            
                            <td>
                                Invoice #: <?php echo $invoice->ref; ?><br>
                                <?php echo $langs->trans('Date') . ' : ' . dol_print_date($invoice->date, 'day'); ?><br>
                                <?php echo $langs->trans('DateMaxPayment') . ' : ' . dol_print_date($invoice->date_validation, 'day'); ?>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="information">
                <td colspan="2">
                    <table>
                        <tr>
                            <td>
                                <?php echo $invoice->thirdparty->name; ?><br>
                                <?php echo $invoice->thirdparty->address; ?><br>
								<?php echo $invoice->thirdparty->zip . ', ' . $invoice->thirdparty->town .' '. $invoice->thirdparty->country_code ; ?>
                            </td>
                            
                            <td>
                               
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            
            <tr class="heading">
                <td>
                    <?php echo $langs->trans('PaymentConditionsShort'); ?>
                </td>
                
                <td>
                    <?php echo $form->form_conditions_reglement('', $invoice->cond_reglement_id, 'none'); ?>
                </td>
            </tr>
            
            
            <tr class="heading">
                <td>
                    TOTAL
                </td>
                
                <td>
                </td>
            </tr>
            
            <tr class="item">
                <td>
                   <?php echo $langs->trans('AmountHT'); ?>
                </td>
                
                <td>
                    <?php echo price($invoice->total_ht, 1, '', 1, - 1, - 1, $conf->currency); ?>
                </td>
            </tr>
            
            <tr class="item">
                <td>
                    <?php echo $langs->trans('AmountVAT'); ?>
                </td>
                
                <td>
                    <?php echo price($invoice->total_tva, 1, '', 1, - 1, - 1, $conf->currency); ?>
                </td>
            </tr>
            
            <tr class="item last">
                <td>
                    <?php echo $langs->trans('AmountTTC'); ?>
                </td>
                
                <td>
                   <?php echo price($invoice->total_ttc, 1, '', 1, - 1, - 1, $conf->currency); ?>
                </td>
            </tr>
            
            <tr class="total">
                <td></td>
                
                <td>
                   Total: <?php echo price($invoice->total_ttc, 1, '', 1, - 1, - 1, $conf->currency); ?>
                </td>
            </tr>
			
			
        </table>
		<?php //var_dump($mysoc); ?>
		
		<span class="center">
			<?php html_print_paypal_footer($mysoc,$langs); ?>
		</span>
		
		<?php //echo var_dump($mysoc); ?>
		
    </div>

</body>
</html>




