<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2001-2002  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2006-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       J. Fernando Lagrange    <fernando@demo-tic.org>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2021       Waël Almoman            <info@almoman.com>
 * Copyright (C) 2022       Udo Tamm                <dev@dolibit.de>
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
 *	\file       htdocs/public/company/new.php
 *	\ingroup    member
 *	\brief      Example of form to add a new member
 *
 *  Note that you can add following constant to change behaviour of page
 *  MEMBER_NEWFORM_AMOUNT               Default amount for auto-subscribe form
 *  MEMBER_MIN_AMOUNT                   Minimum amount
 *  MEMBER_NEWFORM_PAYONLINE            Suggest payment with paypal, paybox or stripe
 *  MEMBER_NEWFORM_DOLIBARRTURNOVER     Show field turnover (specific for dolibarr foundation)
 *  MEMBER_URL_REDIRECT_SUBSCRIPTION    Url to redirect once registration form has been submitted (hidden option, by default we just show a message on same page or redirect to the payment page)
 *  MEMBER_NEWFORM_FORCETYPE            Force type of member
 *  MEMBER_NEWFORM_FORCEMORPHY          Force nature of member (mor/phy)
 *  MEMBER_NEWFORM_FORCECOUNTRYCODE     Force country
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
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}


// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';

// Init vars
$backtopage = GETPOST('backtopage', 'alpha');
$action = GETPOST('action', 'aZ09');

$errmsg = '';
$num = 0;
$error = 0;

// Load translation files
$langs->loadLangs(array("main", "members", "companies", "install", "other", "errors"));

// Security check
if (!isModEnabled('adherent')) {
	httponly_accessforbidden('Module Membership not enabled');
}

// Get parameters
$action		= (GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view');
$cancel		= GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$backtopagejsfields = GETPOST('backtopagejsfields', 'alpha');
$confirm 	= GETPOST('confirm', 'alpha');

$dol_openinpopup = '';
if (!empty($backtopagejsfields)) {
	$tmpbacktopagejsfields = explode(':', $backtopagejsfields);
	$dol_openinpopup = $tmpbacktopagejsfields[0];
}

if (empty($conf->global->MEMBER_ENABLE_PUBLIC)) {
	httponly_accessforbidden("Auto subscription form for public visitors has not been enabled");
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('publicnewmembercard', 'globalcard'));

$extrafields = new ExtraFields($db);


$objectsoc = new Societe($db);
$user->loadDefaultValues();

$socialnetworks = getArrayOfSocialNetworks();

$socid = GETPOST('socid', 'int') ?GETPOST('socid', 'int') : GETPOST('id', 'int');
if ($user->socid) {
	$socid = $user->socid;
}
// Permissions
$permissiontoread 	= $user->hasRight('societe', 'lire');
$permissiontoadd 	= $user->hasRight('societe', 'creer'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('societe', 'supprimer') || ($permissiontoadd && isset($object->status) && $object->status == 0);
$permissionnote 	= $user->hasRight('societe', 'creer'); // Used by the include of actions_setnotes.inc.php
$permissiondellink 	= $user->hasRight('societe', 'creer'); // Used by the include of actions_dellink.inc.php
$upload_dir 		= $conf->societe->multidir_output[isset($object->entity) ? $object->entity : 1];

/**
 * Show header for new member
 *
 * @param 	string		$title				Title
 * @param 	string		$head				Head array
 * @param 	int    		$disablejs			More content into html header
 * @param 	int    		$disablehead		More content into html header
 * @param 	array  		$arrayofjs			Array of complementary js files
 * @param 	array  		$arrayofcss			Array of complementary css files
 * @return	void
 */
function llxHeaderVierge($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '')
{
	global $user, $conf, $langs, $mysoc;

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss); // Show html headers

	print '<body id="mainbody" class="publicnewmemberform">';

	// Define urllogo
	$urllogo = DOL_URL_ROOT.'/theme/common/login_logo.png';

	if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small)) {
		$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_small);
	} elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo)) {
		$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/'.$mysoc->logo);
	} elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.svg')) {
		$urllogo = DOL_URL_ROOT.'/theme/dolibarr_logo.svg';
	}

	print '<header class="center">';

	// Output html code for logo
	if ($urllogo) {
		print '<div class="backgreypublicpayment">';
		print '<div class="logopublicpayment">';
		print '<img id="dolpaymentlogo" src="'.$urllogo.'">';
		print '</div>';
		if (empty($conf->global->MAIN_HIDE_POWERED_BY)) {
			print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">'.$langs->trans("PoweredBy").'<br><img class="poweredbyimg" src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" width="80px"></a></div>';
		}
		print '</div>';
	}

	if (!empty($conf->global->MEMBER_IMAGE_PUBLIC_REGISTRATION)) {
		print '<div class="backimagepublicregistration">';
		print '<img id="idEVENTORGANIZATION_IMAGE_PUBLIC_INTERFACE" src="'.$conf->global->MEMBER_IMAGE_PUBLIC_REGISTRATION.'">';
		print '</div>';
	}

	print '</header>';

	print '<div class="divmainbodylarge">';
}

/**
 * Show footer for new member
 *
 * @return	void
 */
function llxFooterVierge()
{
	global $conf,$langs;

	print '</div>';

	printCommonFooter('public');

	if (!empty($conf->use_javascript_ajax)) {
		print "\n".'<!-- Includes JS Footer of Dolibarr -->'."\n";
		print '<script src="'.DOL_URL_ROOT.'/core/js/lib_foot.js.php?lang='.$langs->defaultlang.(!empty($ext) ? '&'.$ext : '').'"></script>'."\n";
	}

	print "</body>\n";
	print "</html>\n";
}



/*
 * Actions
 */

$parameters = array();
// Note that $action and $object may have been modified by some hooks
$reshook = $hookmanager->executeHooks('doActions', $parameters, $objectsoc, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

// Action called when page is submitted
if (empty($reshook) && $action == 'add') {
	require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
	$error = 0;
	if (!GETPOST('name')) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ThirdPartyName")), null, 'errors');
		$error++;
	}
	if (GETPOST('client', 'int') && GETPOST('client', 'int') < 0) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ProspectCustomer")), null, 'errors');
		$error++;
	}
	if (GETPOSTISSET('fournisseur') && GETPOST('fournisseur', 'int') < 0) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Supplier")), null, 'errors');
		$error++;
	}

	if (isModEnabled('mailing') && !empty($conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS) && $conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS == 2 && GETPOST('contact_no_email', 'int')==-1 && !empty(GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL))) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("No_Email")), null, 'errors');
	}

	if (isModEnabled('mailing') && GETPOST("private", 'int') == 1 && !empty($conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS) && $conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS == 2 && GETPOST('contact_no_email', 'int')==-1 && !empty(GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL))) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("No_Email")), null, 'errors');
	}

	if (GETPOST("private", 'int') == 1) {	// Ask to create a contact
		$objectsoc->particulier		= GETPOST("private", 'int');

		$objectsoc->name = dolGetFirstLastname(GETPOST('firstname', 'alphanohtml'), GETPOST('name', 'alphanohtml'));
		$objectsoc->civility_id		= GETPOST('civility_id', 'alphanohtml'); // Note: civility id is a code, not an int
		// Add non official properties
		$objectsoc->name_bis			= GETPOST('name', 'alphanohtml');
		$objectsoc->firstname			= GETPOST('firstname', 'alphanohtml');
	} else {
		$objectsoc->name				= GETPOST('name', 'alphanohtml');
	}
	$objectsoc->entity					= (GETPOSTISSET('entity') ? GETPOST('entity', 'int') : $conf->entity);
	$objectsoc->name_alias				= GETPOST('name_alias', 'alphanohtml');
	$objectsoc->parent					= GETPOST('parent_company_id', 'int');
	$objectsoc->address				= GETPOST('address', 'alphanohtml');
	$objectsoc->zip					= GETPOST('zipcode', 'alphanohtml');
	$objectsoc->town					= GETPOST('town', 'alphanohtml');
	$objectsoc->country_id				= GETPOST('country_id', 'int');
	$objectsoc->state_id				= GETPOST('state_id', 'int');

	$objectsoc->socialnetworks = array();
	if (isModEnabled('socialnetworks')) {
		foreach ($socialnetworks as $key => $value) {
			if (GETPOSTISSET($key) && GETPOST($key, 'alphanohtml') != '') {
				$objectsoc->socialnetworks[$key] = GETPOST($key, 'alphanohtml');
			}
		}
	}

	$objectsoc->phone					= GETPOST('phone', 'alpha');
	$objectsoc->fax					= GETPOST('fax', 'alpha');
	$objectsoc->email					= trim(GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL));
	$objectsoc->no_email 				= GETPOST("no_email", "int");
	$objectsoc->url					= trim(GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL));
	

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost(null, $objectsoc);
	if ($ret < 0) {
		 $error++;
	}

	// Fill array 'array_languages' with data from add form
	$ret = $objectsoc->setValuesForExtraLanguages();
	if ($ret < 0) {
		$error++;
	}
	//var_dump($objectsoc->array_languages);exit;

	if (!empty($_FILES['photo']['name'])) {
		$current_logo = $objectsoc->logo;
		$objectsoc->logo = dol_sanitizeFileName($_FILES['photo']['name']);
	}

	// Check parameters
	if (!GETPOST('cancel', 'alpha')) {
		if (!empty($objectsoc->email) && !isValidEMail($objectsoc->email)) {
			$langs->load("errors");
			$error++;
			setEventMessages($langs->trans("ErrorBadEMail", $objectsoc->email), null, 'errors');
		}
		if (!empty($objectsoc->url) && !isValidUrl($objectsoc->url)) {
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorBadUrl", $objectsoc->url), null, 'errors');
		}
		if (!empty($objectsoc->webservices_url)) {
			//Check if has transport, without any the soap client will give error
			if (strpos($objectsoc->webservices_url, "http") === false) {
				$objectsoc->webservices_url = "http://".$objectsoc->webservices_url;
			}
			if (!isValidUrl($objectsoc->webservices_url)) {
				$langs->load("errors");
				$error++; $errors[] = $langs->trans("ErrorBadUrl", $objectsoc->webservices_url);
			}
		}

		// We set country_id, country_code and country for the selected country
		$objectsoc->country_id = GETPOST('country_id', 'int') != '' ? GETPOST('country_id', 'int') : $mysoc->country_id;
		if ($objectsoc->country_id) {
			$tmparray = getCountry($objectsoc->country_id, 'all');
			$objectsoc->country_code = $tmparray['code'];
			$objectsoc->country = $tmparray['label'];
		}
	}
}
	
	$urlback = '';

	$db->begin();

	// test if login already exists
	

	$public = GETPOSTISSET('public') ? 1 : 0;

	if (!$error) {
		// E-mail looks OK and login does not exist
		$adh = new Adherent($db);
		$societe = new Societe($db);
		$objectsoc = $societe;
		$adh->statut      = -1;

		$adh->ip = getUserRemoteIP();

		$nb_post_max = getDolGlobalInt("MAIN_SECURITY_MAX_POST_ON_PUBLIC_PAGES_BY_IP_ADDRESS", 200);
		$now = dol_now();
		$minmonthpost = dol_time_plus_duree($now, -1, "m");
		// Calculate nb of post for IP
		$nb_post_ip = 0;
		if ($nb_post_max > 0) {	// Calculate only if there is a limit to check
			$sql = "SELECT COUNT(ref) as nb_adh";
			$sql .= " FROM ".MAIN_DB_PREFIX."adherent";
			$sql .= " WHERE ip = '".$db->escape($adh->ip)."'";
			$sql .= " AND datec > '".$db->idate($minmonthpost)."'";
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$i++;
					$obj = $db->fetch_object($resql);
					$nb_post_ip = $obj->nb_adh;
				}
			}
		}


		
		if ($nb_post_max > 0 && $nb_post_ip >= $nb_post_max) {
			$error++;
			$errmsg .= $langs->trans("AlreadyTooMuchPostOnThisIPAdress");
			array_push($adh->errors, $langs->trans("AlreadyTooMuchPostOnThisIPAdress"));
		}

		if (!$error) {
			$result = $societe->create($user);
			if ($result > 0) {
				require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
				$object = $adh;
				$objectsoc = $societe;


				// Send email to the foundation to say a new member subscribed with autosubscribe form
				
				

				if (!empty($backtopage)) {
					$urlback = $backtopage;
				} elseif (!empty($conf->global->MEMBER_URL_REDIRECT_SUBSCRIPTION)) {
					$urlback = $conf->global->MEMBER_URL_REDIRECT_SUBSCRIPTION;
					// TODO Make replacement of __AMOUNT__, etc...
				} else {
					$urlback = $_SERVER["PHP_SELF"]."?action=added&token=".newToken();
				}

				if (!empty($conf->global->MEMBER_NEWFORM_PAYONLINE) && $conf->global->MEMBER_NEWFORM_PAYONLINE != '-1') {
					if (empty($adht->caneditamount)) {			// If edition of amount not allowed
						// TODO Check amount is same than the amount required for the type of member or if not defined as the defeault amount into $conf->global->MEMBER_NEWFORM_AMOUNT
						// It is not so important because a test is done on return of payment validation.
					}

					$urlback = getOnlinePaymentUrl(0, 'member', $adh->ref, price2num(GETPOST('amount', 'alpha'), 'MT'), '', 0);

					if (GETPOST('email')) {
						$urlback .= '&email='.urlencode(GETPOST('email'));
					}
					if ($conf->global->MEMBER_NEWFORM_PAYONLINE != '-1' && $conf->global->MEMBER_NEWFORM_PAYONLINE != 'all') {
						$urlback .= '&paymentmethod='.urlencode($conf->global->MEMBER_NEWFORM_PAYONLINE);
					}
				} else {
					if (!empty($entity)) {
						$urlback .= '&entity='.((int) $entity);
					}
				}
			} else {

				$error++;
				$errmsg .= join('<br>', $societe->errors);
			}
		}
	}

	if (!$error) {
		$db->commit();

		Header("Location: ".$urlback);
		exit;
	} else {
		$db->rollback();
		$action = "create";
	}


// Action called after a submitted was send and member created successfully
// If MEMBER_URL_REDIRECT_SUBSCRIPTION is set to an url, we never go here because a redirect was done to this url. Same if we ask to redirect to the payment page.
// backtopage parameter with an url was set on member submit page, we never go here because a redirect was done to this url.

if (empty($reshook) && $action == 'added') {
	llxHeaderVierge($langs->trans("NewMemberForm")); // new company added

	// If we have not been redirected
	print '<br><br>';
	print '<div class="center">';
	print $langs->trans("NewMemberbyWeb");
	print '</div>';

	llxFooterVierge();
	exit;
}



/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);
$adht = new AdherentType($db);
$formadmin = new FormAdmin($db);

llxHeaderVierge($langs->trans("NewSubscription"));

print '<br>';
print load_fiche_titre(img_picto('', 'member_nocolor', 'class="pictofixedwidth"').' &nbsp; '.$langs->trans("NewSubscription"), '', '', 0, 0, 'center');


print '<div align="center">';
print '<div id="divsubscribe">';

print '<div class="center subscriptionformhelptext opacitymedium justify">';
if (!empty($conf->global->MEMBER_NEWFORM_TEXT)) {
	print $langs->trans($conf->global->MEMBER_NEWFORM_TEXT)."<br>\n";
} else {
	print $langs->trans("NewSubscriptionDesc", getDolGlobalString("MAIN_INFO_SOCIETE_MAIL"))."<br>\n";
}
print '</div>';

dol_htmloutput_errors($errmsg);
dol_htmloutput_events();

// Print form
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="newmember">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'" / >';
print '<input type="hidden" name="entity" value="'.$entity.'" />';


//$action == 'create'
if (1) {
	/*
		 *  Creation
		 */
		$private = GETPOST("private", "int");
		if (!empty($conf->global->THIRDPARTY_DEFAULT_CREATE_CONTACT) && !GETPOSTISSET('private')) {
			$private = 1;
		}
		if (empty($private)) {
			$private = 0;
		}

		// Load object modCodeTiers
		$module = (!empty($conf->global->SOCIETE_CODECLIENT_ADDON) ? $conf->global->SOCIETE_CODECLIENT_ADDON : 'mod_codeclient_leopard');
		if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php') {
			$module = substr($module, 0, dol_strlen($module) - 4);
		}
		$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
		foreach ($dirsociete as $dirroot) {
			$res = dol_include_once($dirroot.$module.'.php');
			if ($res) {
				break;
			}
		}
		$modCodeClient = new $module;
		// Load object modCodeFournisseur
		$module = (!empty($conf->global->SOCIETE_CODECLIENT_ADDON) ? $conf->global->SOCIETE_CODECLIENT_ADDON : 'mod_codeclient_leopard');
		if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php') {
			$module = substr($module, 0, dol_strlen($module) - 4);
		}
		$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
		foreach ($dirsociete as $dirroot) {
			$res = dol_include_once($dirroot.$module.'.php');
			if ($res) {
				break;
			}
		}
		$modCodeFournisseur = new $module;

		// Define if customer/prospect or supplier status is set or not
		if (GETPOST("type", 'aZ') != 'f') {
			$objectsoc->client = -1;
			if (!empty($conf->global->THIRDPARTY_CUSTOMERPROSPECT_BY_DEFAULT)) {
				$object->client = 3;
			}
		}
		// Prospect / Customer
		if (GETPOST("type", 'aZ') == 'c') {
			if (!empty($conf->global->THIRDPARTY_CUSTOMERTYPE_BY_DEFAULT)) {
				$objectsoc->client = $conf->global->THIRDPARTY_CUSTOMERTYPE_BY_DEFAULT;
			} else {
				$objectsoc->client = 3;
			}
		}
		if (GETPOST("type", 'aZ') == 'p') {
			$objectsoc->client = 2;
		}

		if (!empty($conf->global->SOCIETE_DISABLE_PROSPECTSCUSTOMERS) && $objectsoc->client == 3) {
			$objectsoc->client = 1;
		}

		if ((isModEnabled("supplier_order") || isModEnabled("supplier_invoice")) && (GETPOST("type") == 'f' || (GETPOST("type") == '' && !empty($conf->global->THIRDPARTY_SUPPLIER_BY_DEFAULT)))) {
			$objectsoc->fournisseur = 1;
		}

		$objectsoc->name = GETPOST('name', 'alphanohtml');
		$objectsoc->name_alias = GETPOST('name_alias', 'alphanohtml');
		$objectsoc->firstname = GETPOST('firstname', 'alphanohtml');
		$objectsoc->particulier		= $private;
		$objectsoc->prefix_comm		= GETPOST('prefix_comm', 'alphanohtml');
		$objectsoc->client = GETPOST('client', 'int') ?GETPOST('client', 'int') : $objectsoc->client;

		if (empty($duplicate_code_error)) {
			$objectsoc->code_client		= GETPOST('customer_code', 'alpha');
			$objectsoc->fournisseur		= GETPOST('fournisseur') ? GETPOST('fournisseur', 'int') : $objectsoc->fournisseur;
			$objectsoc->code_fournisseur = GETPOST('supplier_code', 'alpha');
		} else {
			setEventMessages($langs->trans('NewCustomerSupplierCodeProposed'), null, 'warnings');
		}

		$objectsoc->address = GETPOST('address', 'alphanohtml');
		$objectsoc->zip = GETPOST('zipcode', 'alphanohtml');
		$objectsoc->town = GETPOST('town', 'alphanohtml');
		$objectsoc->state_id = GETPOST('state_id', 'int');

		$objectsoc->socialnetworks = array();

		//CHANGE
		/*
		if (isModEnabled('socialnetworks')) {
			foreach ($socialnetworks as $key => $value) {
				if (GETPOSTISSET($key) && GETPOST($key, 'alphanohtml') != '') {
					$objectsoc->socialnetworks[$key] = GETPOST($key, 'alphanohtml');
				}
			}
		}
		*/
		$objectsoc->phone				= GETPOST('phone', 'alpha');
		$objectsoc->fax				= GETPOST('fax', 'alpha');
		$objectsoc->email				= GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
		$objectsoc->url				= GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);
		$objectsoc->capital			= GETPOST('capital', 'alphanohtml');
		$objectsoc->barcode			= GETPOST('barcode', 'alphanohtml');
		
		/* Show create form */

		$linkback = "";
		print load_fiche_titre($langs->trans("NewThirdParty"), $linkback, 'building');

		if (!empty($conf->use_javascript_ajax)) {
			if (!empty($conf->global->THIRDPARTY_SUGGEST_ALSO_ADDRESS_CREATION)) {
				print "\n".'<script type="text/javascript">';
				print '$(document).ready(function () {
						id_te_private=8;
                        id_ef15=1;
                        is_private=' . $private.';
						if (is_private) {
							$(".individualline").show();
						} else {
							$(".individualline").hide();
						}
                        $("#radiocompany").click(function() {
                        	$(".individualline").hide();
                        	$("#typent_id").val(0);
                        	$("#typent_id").change();
                        	$("#effectif_id").val(0);
                        	$("#effectif_id").change();
                        	$("#TypeName").html(document.formsoc.ThirdPartyName.value);
                        	document.formsoc.private.value=0;
                        });
                        $("#radioprivate").click(function() {
                        	$(".individualline").show();
                        	$("#typent_id").val(id_te_private);
                        	$("#typent_id").change();
                        	$("#effectif_id").val(id_ef15);
                        	$("#effectif_id").change();
							/* Force to recompute the width of a select2 field when it was hidden and then shown programatically */
							if ($("#civility_id").data("select2")) {
								$("#civility_id").select2({width: "resolve"});
							}
                        	$("#TypeName").html(document.formsoc.LastName.value);
                        	document.formsoc.private.value=1;
                        });

						var canHaveCategoryIfNotCustomerProspectSupplier = ' . (empty($conf->global->THIRDPARTY_CAN_HAVE_CATEGORY_EVEN_IF_NOT_CUSTOMER_PROSPECT) ? '0' : '1') . ';

						init_customer_categ();
			  			$("#customerprospect").change(function() {
								init_customer_categ();
						});
						function init_customer_categ() {
								console.log("is customer or prospect = "+jQuery("#customerprospect").val());
								if (jQuery("#customerprospect").val() == 0 && !canHaveCategoryIfNotCustomerProspectSupplier)
								{
									jQuery(".visibleifcustomer").hide();
								}
								else
								{
									jQuery(".visibleifcustomer").show();
								}
						}

						init_supplier_categ();
			       		$("#fournisseur").change(function() {
							init_supplier_categ();
						});
						function init_supplier_categ() {
								console.log("is supplier = "+jQuery("#fournisseur").val());
								if (jQuery("#fournisseur").val() == 0)
								{
									jQuery(".visibleifsupplier").hide();
								}
								else
								{
									jQuery(".visibleifsupplier").show();
								}
						}

                        $("#selectcountry_id").change(function() {
                        	document.formsoc.action.value="create";
                        	document.formsoc.submit();
                        });';
				if ($conf->global->MAILING_CONTACT_DEFAULT_BULK_STATUS == 2) {
					print '
						function init_check_no_email(input) {
							if (input.val()!="") {
								$(".noemail").addClass("fieldrequired");
							} else {
								$(".noemail").removeClass("fieldrequired");
							}
						}
						$("#email").keyup(function() {
							init_check_no_email($(this));
						});
						init_check_no_email($("#email"));';
				}
				print '});';
				print '</script>'."\n";

				print '<div id="selectthirdpartytype">';
				print '<div class="hideonsmartphone float">';
				print $langs->trans("ThirdPartyType").': &nbsp; &nbsp; ';
				print '</div>';
				print '<label for="radiocompany" class="radiocompany">';
				print '<input type="radio" id="radiocompany" class="flat" name="private"  value="0"'.($private ? '' : ' checked').'>';
				print '&nbsp;';
				print $langs->trans("CreateThirdPartyOnly");
				print '</label>';
				print ' &nbsp; &nbsp; ';
				print '<label for="radioprivate" class="radioprivate">';
				$text = '<input type="radio" id="radioprivate" class="flat" name="private" value="1"'.($private ? ' checked' : '').'>';
				$text .= '&nbsp;';
				$text .= $langs->trans("CreateThirdPartyAndContact");
				$htmltext = $langs->trans("ToCreateContactWithSameName");
				print $form->textwithpicto($text, $htmltext, 1, 'help', '', 0, 3);
				print '</label>';
				print '</div>';
				print "<br>\n";
			} else {
				print '<script type="text/javascript">';
				print '$(document).ready(function () {
                        $("#selectcountry_id").change(function() {
                        	document.formsoc.action.value="create";
                        	document.formsoc.submit();
                        });
                     });';
				print '</script>'."\n";
			}
		}

		dol_htmloutput_mesg(is_numeric($error) ? '' : $error, $errors, 'error');

		print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'" method="post" name="formsoc" autocomplete="off">'; // Chrome ignor autocomplete

		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		print '<input type="hidden" name="backtopagejsfields" value="'.$backtopagejsfields.'">';
		print '<input type="hidden" name="dol_openinpopup" value="'.$dol_openinpopup.'">';
		print '<input type="hidden" name="private" value='.$objectsoc->particulier.'>';
		print '<input type="hidden" name="type" value='.GETPOST("type", 'alpha').'>';
		print '<input type="hidden" name="LastName" value="'.$langs->trans('ThirdPartyName').' / '.$langs->trans('LastName').'">';
		print '<input type="hidden" name="ThirdPartyName" value="'.$langs->trans('ThirdPartyName').'">';
		if ($modCodeClient->code_auto || $modCodeFournisseur->code_auto) {
			print '<input type="hidden" name="code_auto" value="1">';
		}

		print dol_get_fiche_head(null, 'card', '', 0, '');

		print '<table class="border centpercent">';

		// Name, firstname
		print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">';
		if ($objectsoc->particulier || $private) {
			print '<span id="TypeName" class="fieldrequired">'.$langs->trans('ThirdPartyName').' / '.$langs->trans('LastName', 'name').'</span>';
		} else {
			print '<span id="TypeName" class="fieldrequired">'.$form->editfieldkey('ThirdPartyName', 'name', '', $objectsoc, 0).'</span>';
		}
		print '</td><td'.(empty($conf->global->SOCIETE_USEPREFIX) ? ' colspan="3"' : '').'>';

		print '<input type="text" class="minwidth300" maxlength="128" name="name" id="name" value="'.dol_escape_htmltag($objectsoc->name).'" autofocus="autofocus">';
		print $form->widgetForTranslation("name", $objectsoc, $permissiontoadd, 'string', 'alpahnohtml', 'minwidth300');	// For some countries that need the company name in 2 languages
		
		print '</td>';
		if (!empty($conf->global->SOCIETE_USEPREFIX)) {  // Old not used prefix field
			print '<td>'.$langs->trans('Prefix').'</td><td><input type="text" size="5" maxlength="5" name="prefix_comm" value="'.dol_escape_htmltag($objectsoc->prefix_comm).'"></td>';
		}
		print '</tr>';

		// If javascript on, we show option individual
		if ($conf->use_javascript_ajax) {
			if (!empty($conf->global->THIRDPARTY_SUGGEST_ALSO_ADDRESS_CREATION)) {
				// Firstname
				print '<tr class="individualline"><td>'.$form->editfieldkey('FirstName', 'firstname', '', $objectsoc, 0).'</td>';
				print '<td colspan="3"><input type="text" class="minwidth300" maxlength="128" name="firstname" id="firstname" value="'.dol_escape_htmltag($objectsoc->firstname).'"></td>';
				print '</tr>';

				// Title
				print '<tr class="individualline"><td>'.$form->editfieldkey('UserTitle', 'civility_id', '', $objectsoc, 0).'</td><td colspan="3" class="maxwidthonsmartphone">';
				print $formcompany->select_civility($objectsoc->civility_id, 'civility_id', 'maxwidth100').'</td>';
				print '</tr>';
			}
		}

		// Alias names (commercial, trademark or alias names)
		print '<tr id="name_alias"><td><label for="name_alias_input">'.$langs->trans('AliasNames').'</label></td>';
		print '<td colspan="3"><input type="text" class="minwidth300" name="name_alias" id="name_alias_input" value="'.dol_escape_htmltag($objectsoc->name_alias).'"></td></tr>';

		// Prospect/Customer
		print '<tr><td class="titlefieldcreate">'.$form->editfieldkey('ProspectCustomer', 'customerprospect', '', $objectsoc, 0, 'string', '', 1).'</td>';
		print '<td class="maxwidthonsmartphone">';
		$selected = (GETPOSTISSET('client') ?GETPOST('client', 'int') : $objectsoc->client);
		print $formcompany->selectProspectCustomerType($selected);
		print '</td>';

		if ($conf->browser->layout == 'phone') {
			print '</tr><tr>';
		}

		print '<td>'.$form->editfieldkey('CustomerCode', 'customer_code', '', $objectsoc, 0).'</td><td>';
		print '<table class="nobordernopadding"><tr><td>';
		$tmpcode = $objectsoc->code_client;
		if (empty($tmpcode) && !empty($modCodeClient->code_auto)) {
			$tmpcode = $modCodeClient->getNextValue($objectsoc, 0);
		}
		print '<input type="text" name="customer_code" id="customer_code" class="maxwidthonsmartphone" value="'.dol_escape_htmltag($tmpcode).'" maxlength="24">';
		print '</td><td>';
		$s = $modCodeClient->getToolTip($langs, $objectsoc, 0);
		print $form->textwithpicto('', $s, 1);
		print '</td></tr></table>';
		print '</td></tr>';

		if ((isModEnabled("fournisseur") && !empty($user->rights->fournisseur->lire) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || (isModEnabled("supplier_order") && !empty($user->rights->supplier_order->lire)) || (isModEnabled("supplier_invoice") && !empty($user->rights->supplier_invoice->lire))
			|| (isModEnabled('supplier_proposal') && !empty($user->rights->supplier_proposal->lire))) {
			// Supplier
			print '<tr>';
			print '<td>'.$form->editfieldkey('Vendor', 'fournisseur', '', $objectsoc, 0, 'string', '', 1).'</td><td>';
			$default = -1;
			if (!empty($conf->global->THIRDPARTY_SUPPLIER_BY_DEFAULT)) {
				$default = 1;
			}
			print $form->selectyesno("fournisseur", (GETPOST('fournisseur', 'int') != '' ? GETPOST('fournisseur', 'int') : (GETPOST("type", 'alpha') == '' ? $default : $objectsoc->fournisseur)), 1, 0, (GETPOST("type", 'alpha') == '' ? 1 : 0), 1);
			print '</td>';


			if ($conf->browser->layout == 'phone') {
				print '</tr><tr>';
			}

			print '<td>';
			if ((isModEnabled("fournisseur") && !empty($user->rights->fournisseur->lire) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || (isModEnabled("supplier_order") && !empty($user->rights->supplier_order->lire)) || (isModEnabled("supplier_invoice") && !empty($user->rights->supplier_invoice->lire))) {
				print $form->editfieldkey('SupplierCode', 'supplier_code', '', $objectsoc, 0);
			}
			print '</td><td>';
			if ((isModEnabled("fournisseur") && !empty($user->rights->fournisseur->lire) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || (isModEnabled("supplier_order") && !empty($user->rights->supplier_order->lire)) || (isModEnabled("supplier_invoice") && !empty($user->rights->supplier_invoice->lire))) {
				print '<table class="nobordernopadding"><tr><td>';
				$tmpcode = $objectsoc->code_fournisseur;
				if (empty($tmpcode) && !empty($modCodeFournisseur->code_auto)) {
					$tmpcode = $modCodeFournisseur->getNextValue($objectsoc, 1);
				}
				print '<input type="text" name="supplier_code" id="supplier_code" class="maxwidthonsmartphone" value="'.dol_escape_htmltag($tmpcode).'" maxlength="24">';
				print '</td><td>';
				$s = $modCodeFournisseur->getToolTip($langs, $objectsoc, 1);
				print $form->textwithpicto('', $s, 1);
				print '</td></tr></table>';
			}
			print '</td></tr>';
		}

		// Status
		print '<tr><td>'.$form->editfieldkey('Status', 'status', '', $objectsoc, 0).'</td><td colspan="3">';
		print $form->selectarray('status', array('0'=>$langs->trans('ActivityCeased'), '1'=>$langs->trans('InActivity')), 1, 0, 0, 0, '', 0, 0, 0, '', 'minwidth100', 1);
		print '</td></tr>';

		// Barcode
		if (isModEnabled('barcode')) {
			print '<tr><td>'.$form->editfieldkey('Gencod', 'barcode', '', $objectsoc, 0).'</td>';
			print '<td colspan="3">';
			print img_picto('', 'barcode', 'class="pictofixedwidth"');
			print '<input type="text" name="barcode" id="barcode" value="'.dol_escape_htmltag($objectsoc->barcode).'">';
			print '</td></tr>';
		}

		// Address
		print '<tr><td class="tdtop">';
		print $form->editfieldkey('Address', 'address', '', $objectsoc, 0);
		print '</td>';
		print '<td colspan="3">';
		print '<textarea name="address" id="address" class="quatrevingtpercent" rows="'.ROWS_2.'" wrap="soft">';
		print dol_escape_htmltag($objectsoc->address, 0, 1);
		print '</textarea>';
		print $form->widgetForTranslation("address", $objectsoc, $permissiontoadd, 'textarea', 'alphanohtml', 'quatrevingtpercent');
		print '</td></tr>';

		// Zip / Town
		print '<tr><td>'.$form->editfieldkey('Zip', 'zipcode', '', $objectsoc, 0).'</td><td>';
		print $formcompany->select_ziptown($objectsoc->zip, 'zipcode', array('town', 'selectcountry_id', 'state_id'), 0, 0, '', 'maxwidth100');
		print '</td>';
		if ($conf->browser->layout == 'phone') {
			print '</tr><tr>';
		}
		print '<td class="tdtop">'.$form->editfieldkey('Town', 'town', '', $objectsoc, 0).'</td><td>';
		print $formcompany->select_ziptown($objectsoc->town, 'town', array('zipcode', 'selectcountry_id', 'state_id'), 0, 0, '', 'maxwidth150 quatrevingtpercent');
		print $form->widgetForTranslation("town", $objectsoc, $permissiontoadd, 'string', 'alphanohtml', 'maxwidth100 quatrevingtpercent');
		print '</td></tr>';

		// Country
		print '<tr><td>'.$form->editfieldkey('Country', 'selectcountry_id', '', $objectsoc, 0).'</td><td colspan="3" class="maxwidthonsmartphone">';
		print img_picto('', 'country', 'class="pictofixedwidth"');
		print $form->select_country((GETPOSTISSET('country_id') ? GETPOST('country_id') : $objectsoc->country_id), 'country_id', '', 0, 'minwidth300 maxwidth500 widthcentpercentminusx');
		if ($user->admin) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
		print '</td></tr>';

		// State
		if (empty($conf->global->SOCIETE_DISABLE_STATE)) {
			if (!empty($conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT) && ($conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT == 1 || $conf->global->MAIN_SHOW_REGION_IN_STATE_SELECT == 2)) {
				print '<tr><td>'.$form->editfieldkey('Region-State', 'state_id', '', $objectsoc, 0).'</td><td colspan="3" class="maxwidthonsmartphone">';
			} else {
				print '<tr><td>'.$form->editfieldkey('State', 'state_id', '', $objectsoc, 0).'</td><td colspan="3" class="maxwidthonsmartphone">';
			}
			/*
			if ($objectsoc->country_id) {
				print img_picto('', 'state', 'class="pictofixedwidth"');
				print $formcompany->select_state($objectsoc->state_id, $objectsoc->country_code);
			} else {
				print $countrynotdefined;
			}
			*/
			print '</td></tr>';
		}
		// Phone / Fax
		print '<tr><td>'.$form->editfieldkey('Phone', 'phone', '', $objectsoc, 0).'</td>';
		print '<td'.($conf->browser->layout == 'phone' ? ' colspan="3"' : '').'>'.img_picto('', 'object_phoning', 'class="pictofixedwidth"').' <input type="text" name="phone" id="phone" class="maxwidth200 widthcentpercentminusx" value="'.(GETPOSTISSET('phone') ?GETPOST('phone', 'alpha') : $objectsoc->phone).'"></td>';
		if ($conf->browser->layout == 'phone') {
			print '</tr><tr>';
		}
		print '<td>'.$form->editfieldkey('Fax', 'fax', '', $objectsoc, 0).'</td>';
		print '<td'.($conf->browser->layout == 'phone' ? ' colspan="3"' : '').'>'.img_picto('', 'object_phoning_fax', 'class="pictofixedwidth"').' <input type="text" name="fax" id="fax" class="maxwidth200 widthcentpercentminusx" value="'.(GETPOSTISSET('fax') ?GETPOST('fax', 'alpha') : $objectsoc->fax).'"></td></tr>';

		// Email / Web
		print '<tr><td>'.$form->editfieldkey('EMail', 'email', '', $objectsoc, 0, 'string', '', empty($conf->global->SOCIETE_EMAIL_MANDATORY) ? '' : $conf->global->SOCIETE_EMAIL_MANDATORY).'</td>';
		print '<td'.(($conf->browser->layout == 'phone') || !isModEnabled('mailing') ? ' colspan="3"' : '').'>'.img_picto('', 'object_email', 'class="pictofixedwidth"').' <input type="text" class="maxwidth200 widthcentpercentminusx" name="email" id="email" value="'.$objectsoc->email.'"></td>';
		if (isModEnabled('mailing') && !empty($conf->global->THIRDPARTY_SUGGEST_ALSO_ADDRESS_CREATION)) {
			if ($conf->browser->layout == 'phone') {
				print '</tr><tr>';
			}
			print '<td class="individualline noemail">'.$form->editfieldkey($langs->trans('No_Email') .' ('.$langs->trans('Contact').')', 'contact_no_email', '', $objectsoc, 0).'</td>';
			print '<td class="individualline" '.(($conf->browser->layout == 'phone') || !isModEnabled('mailing') ? ' colspan="3"' : '').'>'.$form->selectyesno('contact_no_email', (GETPOSTISSET("contact_no_email") ? GETPOST("contact_no_email", 'alpha') : (empty($objectsoc->no_email) ? 0 : 1)), 1, false, 1).'</td>';
		}
		print '</tr>';
		print '<tr><td>'.$form->editfieldkey('Web', 'url', '', $objectsoc, 0).'</td>';
		print '<td colspan="3">'.img_picto('', 'globe', 'class="pictofixedwidth"').' <input type="text" class="maxwidth500 widthcentpercentminusx" name="url" id="url" value="'.$objectsoc->url.'"></td></tr>';

		// Unsubscribe
		

		// Social networks
		if (isModEnabled('socialnetworks')) {
			$objectsoc->showSocialNetwork($socialnetworks, ($conf->browser->layout == 'phone' ? 2 : 4));
		}
		print dol_get_fiche_end();

		print $form->buttonsSaveCancel("AddThirdParty", 'Cancel', null, 0, '', $dol_openinpopup);

		print '</form>'."\n";

	print dol_get_fiche_end();

	// Save / Submit
	print '<div class="center">';
	print '<input type="submit" value="submit" id="submitsave" class="button">';
	if (!empty($backtopage)) {
		print ' &nbsp; &nbsp; <input type="submit" value="'.$langs->trans("Cancel").'" id="submitcancel" class="button button-cancel">';
	}
	print '</div>';


	print "</form>\n";
	print "<br>";
	print '</div></div>';
}


llxFooterVierge();

$db->close();
