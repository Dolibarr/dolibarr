<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2001-2002  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2006-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       J. Fernando Lagrange    <fernando@demo-tic.org>
 * Copyright (C) 2018-2025  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2018       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2021       Waël Almoman            <info@almoman.com>
 * Copyright (C) 2022       Udo Tamm                <dev@dolibit.de>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/public/invoice/new.php
 *	\ingroup    invoice
 *	\brief      Example of form to add a new invoice
 *
 *  Note that you can add following constant to change behaviour of page
 *  DONATION_INVOICE_MIN_AMOUNT                   Minimum amount
 *  PRODUCT_ID_FOR_FREE_AMOUNT_INVOICE            Product ID used for free amount invoice line
 */

if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOCSRFCHECK')) {
	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}


// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// Because 2 entities can have the same ref.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
// if (is_numeric($entity)) { // $entity is casted to int
define("DOLENTITY", $entity);
// }


// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (isModEnabled('project') || isModEnabled('eventorganization')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Init vars
$backtopage = GETPOST('backtopage', 'alpha');
$action = GETPOST('action', 'aZ09');
$ws = GETPOST('ws', 'aZ09'); // Website reference where the this public page is embedded or from where is called
$paymentmethod = GETPOST('paymentmethod', 'aZ09'); // Payment method to use

$errmsg = '';
$num = 0;
$error = 0;

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Load translation files
$langs->loadLangs(array("main", "donations", "companies", "install", "other", "errors"));

// Security check
if (!isModEnabled('invoice')) {
	httponly_accessforbidden('Module invoice not enabled');
}

if (!getDolGlobalString('PRODUCT_ID_FOR_FREE_AMOUNT_INVOICE')) {
	httponly_accessforbidden('PRODUCT_ID_FOR_FREE_AMOUNT_INVOICE is not defined');
}

if (!getDolGlobalString('DONATION_INVOICE_MIN_AMOUNT')) {
	httponly_accessforbidden('DONATION_INVOICE_MIN_AMOUNT is not defined');
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
//$hookmanager->initHooks(array( 'globalcard'));

$extrafields = new ExtraFields($db);

$object = new Facture($db);

$user->loadDefaultValues();


/**
 * Show header for new donation
 *
 * Note: also called by functions.lib:recordNotFound
 *
 * @param 	string		$title				Title
 * @param 	string		$head				Head array
 * @param 	int    		$disablejs			More content into html header
 * @param 	int    		$disablehead		More content into html header
 * @param 	string[]|string	$arrayofjs			Array of complementary js files
 * @param 	string[]|string	$arrayofcss			Array of complementary css files
 * @param 	string			$ws					Website ref if we are called from a website
 * @return	void
 */
function llxHeaderVierge($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = [], $arrayofcss = [], $ws = '')  // @phan-suppress-current-line PhanRedefineFunction
{
	global $conf, $langs, $mysoc;

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss); // Show html headers

	print '<body id="mainbody" class="publicnewmemberform">';

	if (!$ws) {
		include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
		htmlPrintOnlineHeader($mysoc, $langs, 1);
	}

	print '<div class="divmainbodylarge">';
}

/**
 * Show footer for new donation
 *
 * Note: also called by functions.lib:recordNotFound
 *
 * @return	void
 */
function llxFooterVierge()  // @phan-suppress-current-line PhanRedefineFunction
{
	global $conf, $langs;

	print '</div>';

	printCommonFooter('public');

	if (!empty($conf->use_javascript_ajax)) {
		print "\n".'<!-- Includes JS Footer of Dolibarr -->'."\n";
		print '<script src="'.DOL_URL_ROOT.'/core/js/lib_foot.js.php?lang='.$langs->defaultlang.'"></script>'."\n";
	}

	print "</body>\n";
	print "</html>\n";
}



/*
 * Actions
 */

$parameters = array();
// Note that $action and $object may have been modified by some hooks
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

// Action called when page is submitted
if (empty($reshook) && $action == 'add') {	// Test on permission not required here. This is an anonymous form. Check is done on constant to enable and mitigation.
	$error = 0;
	$urlback = '';

	$email = GETPOST("email", "aZ09arobase");
	$firstname = GETPOST("firstname", "aZ09");
	$lastname = GETPOST("lastname", "aZ09");
	$societe = GETPOST("societe", "aZ09");
	$idprof2 = GETPOST("idprof2", "aZ09");
	$tva_intra = GETPOST("tva_intra", "aZ09");
	$address = GETPOST("address");
	$zipcode = GETPOST("zipcode", "aZ09");
	$town = GETPOST("town", "aZ09");
	$country_id = GETPOSTINT("country_id");
	$amount = (float) GETPOST("amount", "int");
	$companyId = 0;
	$productIdForFreeAmountInvoice = (int) getDolGlobalString('PRODUCT_ID_FOR_FREE_AMOUNT_INVOICE');

	if (!$email || !isValidEmail($email)) {
		$langs->load('errors');
		$error++;
		$errmsg .= $langs->trans("ErrorBadEMail", $email)."<br>\n";
	}
	if ($firstname && !preg_match('/^[a-zA-Z0-9À-ÖØ-öø-ÿ \'\-]*$/u', $firstname)) {
		$langs->load('errors');
		$error++;
		$errmsg .= $langs->trans("firstnameContainsLettersOnly")."<br>\n";
	}
	if ($lastname && !preg_match('/^[a-zA-Z0-9À-ÖØ-öø-ÿ \'\-]*$/u', $lastname)) {
		$langs->load('errors');
		$error++;
		$errmsg .= $langs->trans("lastnameContainsLettersOnly")."<br>\n";
	}
	if (!$idprof2) {
		$langs->load('errors');
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentities("companyBusinessNumber"))."<br>\n";
	}
	if (!$address) {
		$langs->load('errors');
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentities("Address"))."<br>\n";
	}
	if (!$zipcode) {
		$langs->load('errors');
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentities("Zip"))."<br>\n";
	}
	if (!$town) {
		$langs->load('errors');
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentities("Town"))."<br>\n";
	}
	if (!$country_id) {
		$langs->load('errors');
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentities("Country"))."<br>\n";
	}
	if (!$societe) {
		$langs->load('errors');
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentities("Company"))."<br>\n";
	} else {
		// Check if company exists
		$company = new Societe($db);
		$result = $company->findNearest(0, '', '', '', '', $idprof2, '', '', '', '', $email);
		if ($result > 0) {
			$companyId = $result;
		} elseif ($result < 0) {
			$error++;
			$errmsg .= $langs->trans("donationErrorMessageContactEmail", $mysoc->email)."<br>\n";
		}
	}
	if (!$amount || $amount <= (float) getDolGlobalString("DONATION_INVOICE_MIN_AMOUNT")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldMinimumAmount", (float) getDolGlobalString("DONATION_INVOICE_MIN_AMOUNT"))."<br>\n";
	}

	// Check Captcha code if is enabled
	$sessionkey = 'dol_antispam_value';
	$ok = (array_key_exists($sessionkey, $_SESSION) && (strtolower($_SESSION[$sessionkey]) == strtolower(GETPOST('code'))));
	if (!$ok) {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorBadValueForCode")."<br>\n";
		$action = '';
	}

	// Start of transaction
	$db->begin();

	// Create invoice for this donation
	$invoice = new Facture($db);

	if (!$error && $companyId <= 0) {
		// create company
		$company = new Societe($db);

		if (!empty($societe)) {
			$company->name = $societe;
		} else {
			$company->name = dolGetFirstLastname($firstname, $lastname);
		}

		$company->name_alias = "";
		$company->idprof2 = $idprof2;
		$company->address = $address;
		$company->zip = $zipcode;
		$company->town = $town;
		$company->country_id = $country_id;
		$company->email = $email;
		$company->client = 1;
		$company->code_client = 'auto';
		$company->status = 1; // client
		$company->tva_intra = $tva_intra;

		$company->ip = getUserRemoteIP();

		$result = $company->create($user);
		if ($result < 0) {
			$langs->load('errors');
			$error++;
			$errmsg .= implode('<br>', $company->errors)."<br>\n";
		} else {
			$companyId = $result;
		}
	}

	if (!$error && $companyId > 0) {
		$invoice->socid = $companyId;
		$invoice->type = Facture::TYPE_STANDARD;
		$invoice->cond_reglement_id = 1;
		$invoice->date = dol_now();
		$invoice->module_source = 'donation';
		if (!empty($ws)) {
			$invoice->module_source .= '@' . $ws;
		}
		$invoice->status = Facture::STATUS_DRAFT;
		$invoice->ip = getUserRemoteIP();

		$nb_post_max = getDolGlobalInt("MAIN_SECURITY_MAX_POST_ON_PUBLIC_PAGES_BY_IP_ADDRESS", 1);
		$now = dol_now();
		$minmonthpost = dol_time_plus_duree($now, -1, "m");
		// Calculate nb of post for IP
		$nb_post_ip = 0;
		if ($nb_post_max > 0) {	// Calculate only if there is a limit to check
			$sql = "SELECT COUNT(rowid) as nb_invoice";
			$sql .= " FROM ".MAIN_DB_PREFIX."facture";
			$sql .= " WHERE ip = '".$db->escape($invoice->ip)."'";
			$sql .= " AND datec > '".$db->idate($minmonthpost)."'";
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$i++;
					$obj = $db->fetch_object($resql);
					$nb_post_ip = $obj->nb_invoice;
				}
			}
		}

		if ($nb_post_max > 0 && $nb_post_ip >= $nb_post_max) {
			$error++;
			$errmsg .= $langs->trans("AlreadyTooMuchPostOnThisIPAdress");
			array_push($invoice->errors, $langs->trans("AlreadyTooMuchPostOnThisIPAdress"));
		}

		if (!$error) {
			$result = $invoice->create($user);
			if ($result <= 0) {
				$error++;
				$errmsg .= $invoice->error."<br>\n";
			}
		}

		// Issuer Company
		$issuerCompany = new Societe($db);
		$result = $issuerCompany->fetch($companyId);
		if ($result < 0) {
			$error++;
			$errmsg .= $issuerCompany->error."<br>\n";
		}

		$tva_tx = get_default_tva($mysoc, $issuerCompany, $productIdForFreeAmountInvoice);

		// Get product for free amount invoice line
		$product = new Product($db);
		$result = $product->fetch($productIdForFreeAmountInvoice);
		if ($result < 0) {
			$error++;
			$errmsg .= $product->error."<br>\n";
		} else {
			$desc = $product->label;
			$productId = $product->id;
			// Add line for the invoice
			$result = $invoice->addline($desc, $amount, 1, $tva_tx, 0, 0, $productId, 0, "", "", 0, 0, 0, 'TTC', $amount);
			if ($result <= 0) {
				$error++;
				$errmsg .= $invoice->error."<br>\n";
			}
		}

		// Fill array 'array_options' with data from add form
		/*$extrafields->fetch_name_optionals_label($donation->table_element);
		$ret = $extrafields->setOptionalsFromPost(null, $donation);
		if ($ret < 0) {
			$error++;
			$errmsg .= $donation->error;
		}*/
	}

	if (!$error) {
		$urlback = getOnlinePaymentUrl(0, 'invoice', (string) $invoice->ref, 0, '');
		if ($ws) {
			$urlback .= (strpos($urlback, '?') ? '&' : '?').'ws='.urlencode($ws);
		}
		if ($paymentmethod) {
			$urlback .= (strpos($urlback, '?') ? '&' : '?').'paymentmethod='.urlencode($paymentmethod);
		}
		$db->commit();

		header("Location: ".$urlback);
		exit;
	} else {
		$db->rollback();
		$action = "create";
	}
}

// Action called after a submitted was send and donation created successfully
// If we ask to redirect to the payment page, we never go here because a redirect was done to the payment url.
// backtopage parameter with an url was set on donation submit page, we never go here because a redirect was done to this url.

if (empty($reshook) && $action == 'added') {	// Test on permission not required here
	llxHeaderVierge($langs->trans("NewDonationForm"));

	// If we have not been redirected
	print '<br><br>';
	print '<div class="center">';
	print $langs->trans("NewDonationbyWeb").'<br>';
	print '</div>';

	llxFooterVierge();
	exit;
}


/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);
$extrafields->fetch_name_optionals_label($object->table_element); // fetch optionals attributes and labels


llxHeaderVierge($langs->trans("NewDonation"), '', 0, 0, array(), array(), $ws);
if (!$ws) {
	print '<br>';
	print load_fiche_titre(img_picto('', '', 'class="pictofixedwidth"').' &nbsp; '.$langs->trans("NewDonation"), '', '', 0, '', 'center');


	print '<div align="center">';
	print '<div id="divsubscribe">';

	print '<div class="center subscriptionformhelptext opacitymedium justify">';
	print $langs->trans("NewDonationDesc", getDolGlobalString("MAIN_INFO_SOCIETE_MAIL"))."<br>\n";

	print '</div>';
}

dol_htmloutput_errors($errmsg);
dol_htmloutput_events();

// Print form
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="newdonation">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'" />';
print '<input type="hidden" name="entity" value="'.$entity.'" />';
print '<input type="hidden" name="page_y" value="" />';

if (!$action || $action == 'create') {
	print '<input type="hidden" name="action" value="add" />';
	print '<input type="hidden" name="ws" value="'.$ws.'">';
	print '<input type="hidden" name="paymentmethod" value="'.$paymentmethod.'">';
	print '<br>';

	$messagemandatory = '<span class="">'.$langs->trans("FieldsWithAreMandatory", '*').'</span>';
	//print '<br><span class="opacitymedium">'.$langs->trans("FieldsWithAreMandatory", '*').'</span><br>';
	//print $langs->trans("FieldsWithIsForPublic",'**').'<br>';

	print dol_get_fiche_head();

	print '<table class="border" summary="form to subscribe" id="tablesubscribe">'."\n";

	// Add a specific style or table head for the project row
	if ((isModEnabled('project') || isModEnabled('eventorganization')) && !empty($projectTitle)) {
		print '<tr>';
		print '<td class="project-label">' . $langs->trans("project") . '</td>';
		print '<td class="project-value">' . dol_escape_htmltag($projectTitle) . '</td>';
		print '</tr>';
	}

	// EMail
	print '<tr id="tremail"><td class="fieldrequired minwidth300">'.$langs->trans("Email").'</td><td>';
	print '<input type="email" name="email" maxlength="255" class="minwidth200" value="'.dol_escape_htmltag(GETPOST('email', "aZ09arobase")).'"></td></tr>'."\n";

	// Company
	print '<tr id="trcompany" class="trcompany"><td class="fieldrequired">'.$langs->trans("Company").'</td><td>';
	print img_picto('', 'company', 'class="pictofixedwidth paddingright"');
	print '<input type="text" name="societe" class="minwidth300" value="'.dol_escape_htmltag(GETPOST('societe')).'"></td></tr>'."\n";

	// Firstname
	print '<tr id="trfirstname"><td class="classfortooltip">'.$langs->trans("Firstname").'</td><td><input type="text" name="firstname" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('firstname')).'"></td></tr>'."\n";

	// Lastname
	print '<tr id="trlastname"><td class="classfortooltip">'.$langs->trans("Lastname").'</td><td><input type="text" name="lastname" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('lastname')).'"></td></tr>'."\n";

	// Address
	print '<tr id="tradress"><td class="fieldrequired">'.$langs->trans("Address").'</td><td>'."\n";
	print '<textarea name="address" id="address" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.dol_escape_htmltag(GETPOST('address', 'restricthtml'), 0, 1).'</textarea></td></tr>'."\n";

	// Zip / Town
	print '<tr id="trzip"><td class="fieldrequired">'.$langs->trans('Zip').' / '.$langs->trans('Town').'</td><td>';
	print $formcompany->select_ziptown(GETPOST('zipcode'), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 0, 1, '', 'width75');
	print ' / ';
	print $formcompany->select_ziptown(GETPOST('town'), 'town', array('zipcode', 'selectcountry_id', 'state_id'), 0, 1);
	print '</td></tr>';

	// Country
	print '<tr id="trcountry"><td class="fieldrequired">'.$langs->trans('Country').'</td><td>';
	print img_picto('', 'country', 'class="pictofixedwidth paddingright"');
	$country_id = GETPOSTINT('country_id');
	if (!$country_id && !empty($conf->geoipmaxmind->enabled)) {
		$country_code = dol_user_country();
		//print $country_code;
		if ($country_code) {
			$new_country_id = getCountry($country_code, '3', $db, $langs);
			//print 'xxx'.$country_code.' - '.$new_country_id;
			if ($new_country_id) {
				$country_id = $new_country_id;
			}
		}
	}
	$country_code = getCountry($country_id, '2', $db, $langs);
	print $form->select_country($country_id, 'country_id');
	print '</td></tr>';

	//Idprof2 (siret...)
	print '<tr id="trsiret"><td class="fieldrequired">'.$langs->trans("companyBusinessNumber").'</td><td><input type="text" name="idprof2" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('idprof2')).'"></td></tr>'."\n";

	//Tva_intra
	print '<tr id="trtva"><td>'.$langs->trans("companyTIN").'</td><td><input type="text" name="tva_intra" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('tva_intra')).'"></td></tr>'."\n";

	print '<tr><td colspan="2"><hr></td></tr>';

	// Amount
	$amount = (float) (GETPOST('amount') ? price2num(GETPOST('amount', 'alpha'), 'MT', 2) : '');

	// - If a min is set, we take it into account
	$amount = max(0, (float) $amount, (float) getDolGlobalInt("DONATION_INVOICE_MIN_AMOUNT"));

	// Clean the amount
	$amount = price2num($amount);
	$showedamount = $amount > 0 ? $amount : 5;
	print '<tr><td class="fieldrequired">'.$langs->trans("donationAmount");
	print '</td><td class="nowrap">';

	print '<input type="text" name="amount" id="amount" class="flat amount width50" value="'.$showedamount.'">';
	print ' '.$langs->trans("Currency".getDolCurrency()).'<span class="opacitymedium hideifautoturnover"> - ';
	print $langs->trans("AnyAmountForDonation");
	print '</span>';

	print '</td></tr>';

	// Display Captcha code if is enabled
	require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
	print '<tr><td class="titlefield"><label><span class="fieldrequired">'.$langs->trans("SecurityCode").'</span></label></td><td>';
	print '<span class="span-icon-security inline-block">';
	print '<input id="securitycode" placeholder="'.$langs->trans("SecurityCode").'" class="flat input-icon-security width150" type="text" maxlength="5" name="code" tabindex="3" />';
	print '</span>';
	print '<span class="nowrap inline-block">';
	print '<img class="inline-block valignmiddle" src="'.DOL_URL_ROOT.'/core/antispamimage.php" border="0" width="80" height="32" id="img_securitycode" />';
	print '<a class="inline-block valignmiddle" href="" tabindex="4" data-role="button">'.img_picto($langs->trans("Refresh"), 'refresh', 'id="captcha_refresh_img"').'</a>';
	print '</span>';
	print '</td></tr>';



	print "</table>\n";

	print dol_get_fiche_end();

	// Save / Submit
	print '<div class="center">';
	print '<input type="submit" value="'.$langs->trans("GetDonationButtonLabel").'" id="submitsave" class="button">';
	if (!empty($backtopage)) {
		print ' &nbsp; &nbsp; <input type="submit" value="'.$langs->trans("Cancel").'" id="submitcancel" class="button button-cancel">';
	}
	print '</div>';


	print "</form>\n";
	print "<br>";
	print '</div></div>';
}

//htmlPrintOnlineFooter($mysoc, $langs);
llxFooterVierge();

$db->close();
