<?php
/* Copyright (C) 2011 Regis Houssin  <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
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
 *       \file       htdocs/paypal/ajaxtransaction.php
 *       \brief      File to return Ajax response on paypal transaction details
 *       \version    $Id$
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL',1); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');

require('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php');
require_once(DOL_DOCUMENT_ROOT."/paypal/lib/paypalfunctions.lib.php");

$langs->load('main');
$langs->load('users');
$langs->load('companies');


/*
 * View
 */

// Ajout directives pour resoudre bug IE
//header('Cache-Control: Public, must-revalidate');
//header('Pragma: public');

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead();

//echo '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

//echo '<body class="nocellnopadd">'."\n";

dol_syslog(join(',',$_GET));

if (isset($_GET['action']) && ! empty($_GET['action']) && isset($_GET['transaction_id']) && ! empty($_GET['transaction_id']) )
{
	$langs->load("paypal");

	if ($_GET['action'] == 'add')
	{
		$soc = new Societe($db);

		$error=0;
		$return_arr = array();

		$db->begin();

		// Create customer if not exists
		$ret = $soc->fetchObjectFrom($soc->table_element,'ref_int',$_SESSION[$_GET['transaction_id']]['PAYERID']);
		if ($ret < 0)
		{
			// Load object modCodeTiers
			$module=$conf->global->SOCIETE_CODECLIENT_ADDON;
			if (! $module) dolibarr_error('',$langs->trans("ErrorModuleThirdPartyCodeInCompanyModuleNotDefined"));
			if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
			{
				$module = substr($module, 0, dol_strlen($module)-4);
			}
			require_once(DOL_DOCUMENT_ROOT ."/includes/modules/societe/".$module.".php");
			$modCodeClient = new $module;

			// Create customer and return rowid
			$soc->ref_int			= $_SESSION[$_GET['transaction_id']]['PAYERID'];
			$soc->name              = empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION)?trim($_SESSION[$_GET['transaction_id']]['FIRSTNAME'].' '.$_SESSION[$_GET['transaction_id']]['LASTNAME']):trim($_SESSION[$_GET['transaction_id']]['LASTNAME'].' '.$_SESSION[$_GET['transaction_id']]['FIRSTNAME']);
			$soc->nom_particulier	= $_SESSION[$_GET['transaction_id']]['LASTNAME'];
			$soc->prenom			= $_SESSION[$_GET['transaction_id']]['FIRSTNAME'];
			$soc->address			= $_SESSION[$_GET['transaction_id']]['SHIPTOSTREET'];
			$soc->zip				= $_SESSION[$_GET['transaction_id']]['SHIPTOZIP'];
			$soc->town				= $_SESSION[$_GET['transaction_id']]['SHIPTOCITY'];
			//$soc->pays_id			= $_POST["pays_id"];
			$soc->email				= $_SESSION[$_GET['transaction_id']]['EMAIL'];
			$soc->code_client		= ($modCodeClient->code_auto ? $modCodeClient->getNextValue($soc,0):'');
			$soc->tva_assuj			= 1;
			$soc->client			= 1;
			$soc->particulier		= 1;

			$result = $soc->create($user);
			if ($result >= 0)
			{
				if ($soc->particulier)
				{
					$contact=new Contact($db);

					$contact->civilite_id = $soc->civilite_id;
					$contact->name=$soc->nom_particulier;
					$contact->firstname=$soc->prenom;
					$contact->address=$soc->address;
					$contact->zip=$soc->zip;
					$contact->cp=$soc->cp;
					$contact->town=$soc->town;
					$contact->ville=$soc->ville;
					$contact->fk_pays=$soc->fk_pays;
					$contact->socid=$soc->id;
					$contact->status=1;
					$contact->email=$soc->email;
					$contact->priv=0;

					$result=$contact->create($user);
					if ($result < 0)
					{
						$langs->load("errors");
						$return_arr['error'] = 'Contact::create '.$langs->trans($contact->error);
						$error++;
					}
				}
			}
			else
			{
				$langs->load("errors");
				$return_arr['error'] = 'Societe::create '.$langs->trans($soc->error);
				$error++;
			}
		}

		// Add element (order, bill, etc.)
		if (! $error && $soc->id > 0 && isset($_GET['element']) && ! empty($_GET['element']))
		{
			// Parse element/subelement (ex: project_task)
	        $element = $subelement = $_GET['element'];
	        if (preg_match('/^([^_]+)_([^_]+)/i',$_GET['element'],$regs))
	        {
	            $element = $regs[1];
	            $subelement = $regs[2];
	        }
	        // For compatibility
            if ($element == 'order') { $element = $subelement = 'commande'; }
            if ($element == 'invoice') { $element = 'compta/facture'; $subelement = 'facture'; }

            dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

            $classname = ucfirst($subelement);
            $object = new $classname($db);

            $object->socid=$soc->id;
            $object->fetch_thirdparty();

            $object->date				= dol_now();
            $object->mode_reglement_id 	= 6; // Credit card by default
            $object->cond_reglement_id	= 1;
            $object->ref_int			= $_SESSION[$_GET['transaction_id']]['TRANSACTIONID'];
            $shipamount					= ($_SESSION[$_GET['transaction_id']]['SHIPPINGAMT']?$_SESSION[$_GET['transaction_id']]['SHIPPINGAMT']:$_SESSION[$_GET['transaction_id']]['SHIPAMOUNT']);

            $object_id = $object->create($user);
            if ($object_id > 0)
            {
	            $i=0;

	            // Add element lines
	            while (isset($_SESSION[$_GET['transaction_id']]["L_NAME".$i]))
				{
					$product = new Product($db);
					$ret = $product->fetch('',$_SESSION[$_GET['transaction_id']]["L_NUMBER".$i]);

					if ($ret > 0)
					{
						$qty=$_SESSION[$_GET['transaction_id']]["L_QTY".$i];
						if ($_SESSION[$_GET['transaction_id']]["L_AMT".$i]) $amount_ht = ($_SESSION[$_GET['transaction_id']]["L_AMT".$i] - $_SESSION[$_GET['transaction_id']]["L_SHIPPINGAMT".$i]);
						else $amount_ht = ($_SESSION[$_GET['transaction_id']]["AMT"] - $_SESSION[$_GET['transaction_id']]["SHIPAMOUNT"] - $_SESSION[$_GET['transaction_id']]["L_TAXAMT".$i]);
						$unitprice_ht = ($amount_ht / $qty);

						if ($conf->global->PAYPAL_USE_PRICE_DEFINED_IN_PAYPAL)
						{
							$price=$unitprice_ht;
						}
						else
						{
							$price=$product->price;
							if ($price != $unitprice_ht)
							{
								$error++;
								$return_arr['error'].= $langs->trans('ErrorProductWithRefNotSamePrice', $_SESSION[$_GET['transaction_id']]["L_NUMBER".$i]).'<br />';
								break;
							}
						}

						if ($subelement == 'commande') $fields = array($object_id,$product->description,$price,$qty,$product->tva_tx,$product->localtax1_tx,$product->localtax2_tx,$product->id,0,0,0,$product->price_base_type,0,'','',$product->product_type);
						if ($subelement == 'facture') $fields = array($object_id,$product->description,$price,$qty,$product->tva_tx,$product->localtax1_tx,$product->localtax2_tx,$product->id,0,'','',0,0,0,$product->price_base_type,0,$product->product_type);

						$result = $object->addline($fields[0],$fields[1],$fields[2],$fields[3],$fields[4],$fields[5],$fields[6],$fields[7],$fields[8],$fields[9],$fields[10],$fields[11],$fields[12],$fields[13],$fields[14],$fields[15],$fields[16]);

	                    if ($result < 0)
	                    {
	                        $error++;
	                        $langs->load("errors");
	                        $return_arr['error'] = ucfirst($subelement).'::addline '.$langs->trans($object->error);
	                        break;
	                    }
					}
					else
					{
						$error++;
						$langs->load("errors");
						$return_arr['error'].= $langs->trans('ErrorProductWithRefNotExist', $_SESSION[$_GET['transaction_id']]["L_NUMBER".$i]).'<br />';
					}

					$i++;
				}

				// Add shipping costs
				if (! $error && $shipamount > 0)
				{
					if ($conf->global->PAYPAL_PRODUCT_SHIPPING_COSTS)
					{
						$product = new Product($db);
						$ret = $product->fetch($conf->global->PAYPAL_PRODUCT_SHIPPING_COSTS);

						if ($ret > 0)
						{
							$product_type=($product->product_type?$product->product_type:0);

							if ($subelement == 'commande') $fields = array($object_id,$product->description,$shipamount,1,$product->tva_tx,$product->localtax1_tx,$product->localtax2_tx,$product->id,0,0,0,$product->price_base_type,$shipamount,'','',$product_type);
							if ($subelement == 'facture') $fields = array($object_id,$product->description,$shipamount,1,$product->tva_tx,$product->localtax1_tx,$product->localtax2_tx,$product->id,0,'','',0,0,0,$product->price_base_type,$shipamount,$product_type);

							$result = $object->addline($fields[0],$fields[1],$fields[2],$fields[3],$fields[4],$fields[5],$fields[6],$fields[7],$fields[8],$fields[9],$fields[10],$fields[11],$fields[12],$fields[13],$fields[14],$fields[15],$fields[16]);

		                    if ($result < 0)
		                    {
		                        $error++;
		                        $langs->load("errors");
		                        $return_arr['error'] = ucfirst($subelement).'::addline '.$langs->trans($object->error);
		                        break;
		                    }
						}
						else
						{
							$error++;
							$langs->load("errors");
							$return_arr['error'].= $langs->trans('ErrorProductWithRefNotExist', $conf->global->PAYPAL_PRODUCT_SHIPPING_COSTS).'<br />';
						}
					}
					else
					{
						$error++;
						$return_arr['error'].= $langs->trans('ErrorUndefinedProductForShippingCost').'<br />';
					}
				}

				// Add contact customer
            	if (! $error && $contact->id > 0)
			    {
			        $result=$object->add_contact($contact->id,'CUSTOMER','external');
			        if ($result < 0)
			        {
			        	$error++;
			        	$langs->load("errors");
			        	$return_arr['error'].= $langs->trans('ErrorToAddContactCustomer').'<br />';
			        }
			    }
            }
            else
            {
            	$langs->load("errors");
				$return_arr['error'] = ucfirst($subelement).'::create '.$langs->trans($object->error);
				$error++;
            }

            if (! $error)
		    {
		        $db->commit();
		        $return_arr['elementurl'] = $object->getNomUrl(0,'',0,1);
		    }
		    else
		    {
		        $db->rollback();
		    }
		}

		echo json_encode($return_arr);

	}
	else if ($_GET['action'] == 'showdetails')
	{
		$langs->load('orders');
		$langs->load('bills');

		$return_arr = array();
		$return_arr['element_created'] = false;

		// For paypal request optimization
		if (! isset($_SESSION[$_GET['transaction_id']]) ) $_SESSION[$_GET['transaction_id']] = GetTransactionDetails($_GET['transaction_id']);

		// Check if already import
		$i=0;

		$objects = getLinkedObjects($_GET['transaction_id']);
		if (! empty($objects)) $return_arr['element_created'] = true;

		$soc = new Societe($db);
		$ret = $soc->fetchObjectFrom($soc->table_element, 'ref_int', $_SESSION[$_GET['transaction_id']]['PAYERID']);

		$var=true;

		$return_arr['contents'] = '<table style="noboardernopading" width="100%">';
		$return_arr['contents'].= '<tr class="liste_titre">';
		$return_arr['contents'].= '<td colspan="2">'.$langs->trans('ThirdParty').'</td>';
		$return_arr['contents'].= '</tr>';

		if ($ret > 0)
		{
			$var=!$var;
			$return_arr['contents'].= '<tr '.$bc[$var].'><td>'.$langs->trans('ThirdPartyName').'</td><td>'.$soc->getNomUrl(1).'</td></tr>';
		}
		else
		{
			$var=!$var;
			$return_arr['contents'].= '<tr '.$bc[$var].'><td>'.$langs->trans('LastName').'</td><td>'.$_SESSION[$_GET['transaction_id']]['LASTNAME'].'</td></tr>';
			$var=!$var;
			$return_arr['contents'].= '<tr '.$bc[$var].'><td>'.$langs->trans('FirstName').'</td><td>'.$_SESSION[$_GET['transaction_id']]['FIRSTNAME'].'</td></tr>';
		}
		$var=!$var;
		$return_arr['contents'].= '<tr '.$bc[$var].'><td>'.$langs->trans('Address').'</td><td>'.$_SESSION[$_GET['transaction_id']]['SHIPTOSTREET'].'</td></tr>';
		$var=!$var;
		$return_arr['contents'].= '<tr '.$bc[$var].'><td>'.$langs->trans('Zip').' / '.$langs->trans('Town').'</td><td>'.$_SESSION[$_GET['transaction_id']]['SHIPTOZIP'].' '.$_SESSION[$_GET['transaction_id']]['SHIPTOCITY'].'</td></tr>';
		$var=!$var;
		$return_arr['contents'].= '<tr '.$bc[$var].'><td>'.$langs->trans('Country').'</td><td>'.$_SESSION[$_GET['transaction_id']]['SHIPTOCOUNTRYNAME'].'</td></tr>';
		$var=!$var;
		$return_arr['contents'].= '<tr '.$bc[$var].'><td>'.$langs->trans('Email').'</td><td>'.$_SESSION[$_GET['transaction_id']]['EMAIL'].'</td>';
		$var=!$var;
		$return_arr['contents'].= '<tr '.$bc[$var].'><td>'.$langs->trans('Date').'</td><td>'.dol_print_date(dol_stringtotime($_SESSION[$_GET['transaction_id']]['ORDERTIME']),'dayhour').'</td>';

		$var=!$var;
		$payerstatus=strtolower($_SESSION[$_GET['transaction_id']]['PAYERSTATUS']);
		$img_payerstatus=($payerstatus=='verified' ? img_tick($langs->trans(ucfirst($payerstatus))) : img_warning($langs->trans(ucfirst($payerstatus))) );
		$return_arr['contents'].= '<tr '.$bc[$var].'><td>'.$langs->trans('PAYERSTATUS').'</td><td>'.$img_payerstatus.'</td>';

		$var=!$var;
		$addressstatus=strtolower($_SESSION[$_GET['transaction_id']]['ADDRESSSTATUS']);
		$img_addressstatus=($addressstatus=='confirmed' ? img_tick($langs->trans(ucfirst($addressstatus))) : img_warning($langs->trans(ucfirst($addressstatus))) );
		$return_arr['contents'].= '<tr '.$bc[$var].'><td>'.$langs->trans('ADDRESSSTATUS').'</td><td>'.$img_addressstatus.'</td>';

		$return_arr['contents'].= '</table>';

		$i=0;
		$total_ht=0;

		$return_arr['contents'].= '<table style="noboardernopading" width="100%">';

		$return_arr['contents'].= '<tr class="liste_titre">';
		$return_arr['contents'].= '<td>'.$langs->trans('Ref').'</td>';
		$return_arr['contents'].= '<td>'.$langs->trans('Label').'</td>';
		$return_arr['contents'].= '<td align="right">'.$langs->trans('UnitPriceHT').'</td>';
		$return_arr['contents'].= '<td align="right">'.$langs->trans('Qty').'</td>';
		$return_arr['contents'].= '<td align="right">'.$langs->trans('AmountHT').'</td>';
		$return_arr['contents'].= '</tr>';

		while (isset($_SESSION[$_GET['transaction_id']]["L_NAME".$i]))
		{
			$var=!$var;

			$qty = $_SESSION[$_GET['transaction_id']]["L_QTY".$i];

			if ($_SESSION[$_GET['transaction_id']]["L_AMT".$i])
			{
				$amount_ht = ($_SESSION[$_GET['transaction_id']]["L_AMT".$i] - $_SESSION[$_GET['transaction_id']]["L_SHIPPINGAMT".$i]);
			}
			else
			{
				$amount_ht = ($_SESSION[$_GET['transaction_id']]["AMT"] - $_SESSION[$_GET['transaction_id']]["SHIPAMOUNT"] - $_SESSION[$_GET['transaction_id']]["L_TAXAMT".$i]);
			}

			$unitprice_ht = ($amount_ht / $qty);

			$return_arr['contents'].= '<tr '.$bc[$var].'>';
			$return_arr['contents'].= '<td>'.$_SESSION[$_GET['transaction_id']]["L_NUMBER".$i].'</td>';
			$return_arr['contents'].= '<td>'.$_SESSION[$_GET['transaction_id']]["L_NAME".$i].'</td>';
			$return_arr['contents'].= '<td align="right">'.price($unitprice_ht).' '.$_SESSION[$_GET['transaction_id']]['CURRENCYCODE'].'</td>';
			$return_arr['contents'].= '<td align="right">'.$_SESSION[$_GET['transaction_id']]["L_QTY".$i].'</td>';
			$return_arr['contents'].= '<td align="right">'.price($amount_ht).' '.$_SESSION[$_GET['transaction_id']]['CURRENCYCODE'].'</td>';
			$return_arr['contents'].= '</tr>';

			$total_ht+=$amount_ht;

			$i++;
		}

		$var=!$var;
		$return_arr['contents'].= '<tr '.$bc[$var].'><td colspan="4" align="right"><strong>'.$langs->trans('TotalHT').'</strong></td><td align="right"><strong>'.price($total_ht).' '.$_SESSION[$_GET['transaction_id']]['CURRENCYCODE'].'</strong></td>';

		$var=!$var;
		$return_arr['contents'].= '<tr '.$bc[$var].'><td colspan="4" align="right"><strong>'.$langs->trans('TotalVAT').'</strong></td><td align="right"><strong>'.price($_SESSION[$_GET['transaction_id']]['TAXAMT']).' '.$_SESSION[$_GET['transaction_id']]['CURRENCYCODE'].'</strong></td>';

		$shipamount=($_SESSION[$_GET['transaction_id']]['SHIPPINGAMT']?$_SESSION[$_GET['transaction_id']]['SHIPPINGAMT']:$_SESSION[$_GET['transaction_id']]['SHIPAMOUNT']);
		$var=!$var;
		$return_arr['contents'].= '<tr '.$bc[$var].'><td colspan="4" align="right"><strong>'.$langs->trans('SHIPAMOUNT').'</strong></td><td align="right"><strong>'.price($shipamount).' '.$_SESSION[$_GET['transaction_id']]['CURRENCYCODE'].'</strong></td>';

		$var=!$var;
		$return_arr['contents'].= '<tr '.$bc[$var].'><td colspan="4" align="right"><strong>'.$langs->trans('TotalTTC').'</strong></td><td align="right"><strong>'.price($_SESSION[$_GET['transaction_id']]['AMT']).' '.$_SESSION[$_GET['transaction_id']]['CURRENCYCODE'].'</strong></td>';

		$return_arr['contents'].= '</table>';

		if (! empty($objects))
		{
			$return_arr['contents'].= '<table style="noboardernopading" width="100%">';

			$return_arr['contents'].= '<tr class="liste_titre">';
			$return_arr['contents'].= '<td colspan="3">'.$langs->trans('BuildDocuments').'</td>';
			$return_arr['contents'].= '</tr>';

			if (! empty($objects['order']))
			{
				$var=!$var;
				$return_arr['contents'].= '<tr '.$bc[$var].'>';
				$return_arr['contents'].= '<td>'.$langs->trans('RefOrder').'</td>';
				$return_arr['contents'].= '<td>'.$objects['order']->getNomUrl(1).'</td>';
				$return_arr['contents'].= '<td align="center">'.$objects['order']->getLibStatut(3).'</td>';
				$return_arr['contents'].= '</tr>';
			}
			if (! empty($objects['invoice']))
			{
				$var=!$var;
				$return_arr['contents'].= '<tr '.$bc[$var].'>';
				$return_arr['contents'].= '<td>'.$langs->trans('InvoiceRef').'</td>';
				$return_arr['contents'].= '<td>'.$objects['invoice']->getNomUrl(1).'</td>';
				$return_arr['contents'].= '<td align="center">'.$objects['invoice']->getLibStatut(3).'</td>';
				$return_arr['contents'].= '</tr>';
			}

			$return_arr['contents'].= '</table>';
		}

/*
		$return_arr['contents'].= '<br />';
		foreach ($_SESSION[$_GET['transaction_id']] as $key => $value)
		{
			$return_arr['contents'].= $key.': '.$value.'<br />';
		}
*/

		echo json_encode($return_arr);
	}
}

//echo "</body>";
//echo "</html>";
?>