<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2001-2002  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2006-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       J. Fernando Lagrange    <fernando@demo-tic.org>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018       Alexandre Spangaro      <aspangaro@open-dsi.fr>
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
 *	\file       htdocs/public/members/new.php
 *	\ingroup    member
 *	\brief      Example of form to add a new member
 *
 *  Note that you can add following constant to change behaviour of page
 *  MEMBER_NEWFORM_AMOUNT               Default amount for auto-subscribe form
 *  MEMBER_NEWFORM_EDITAMOUNT           0 or 1 = Amount can be edited
 *  MEMBER_NEWFORM_PAYONLINE            Suggest payment with paypal, paybox or stripe
 *  MEMBER_NEWFORM_DOLIBARRTURNOVER     Show field turnover (specific for dolibarr foundation)
 *  MEMBER_URL_REDIRECT_SUBSCRIPTION    Url to redirect once subscribe submitted
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
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// TODO This should be useless. Because entity must be retrieve from object ref and not from url.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorbooth.class.php';
require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorboothattendee.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

// Init vars
$errmsg = '';
$num = 0;
$error = 0;
$backtopage = GETPOST('backtopage', 'alpha');
$action = GETPOST('action', 'aZ09');

$email = GETPOST("email");

// Getting id from Post and decoding it
$encodedid = GETPOST('id');
$id = dol_decode($encodedid, $dolibarr_main_instance_unique_id);

// Getting 'securekey'.'id' from Post and decoding it
$encodedsecurekeyandid = GETPOST('securekey', 'alpha');
$securekeyandid = dol_decode($encodedsecurekeyandid, $dolibarr_main_instance_unique_id);

// Securekey decomposition into pure securekey and id added at the end
$securekey = substr($securekeyandid, 0, strlen($securekeyandid)-strlen($encodedid));
$idgotfromsecurekey = dol_decode(substr($securekeyandid, -strlen($encodedid), strlen($encodedid)), $dolibarr_main_instance_unique_id);

// We check if the securekey collected is OK and if the id collected is the same than the id in the securekey
if ($securekey != $conf->global->EVENTORGANIZATION_SECUREKEY || $idgotfromsecurekey != $id) {
	print $langs->trans('MissingOrBadSecureKey');
	exit;
}

// Load translation files
$langs->loadLangs(array("main", "companies", "install", "other", "eventorganization"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('publicnewmembercard', 'globalcard'));

$extrafields = new ExtraFields($db);

$user->loadDefaultValues();


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

	print '<div class="center">';
	// Output html code for logo
	if ($urllogo) {
		print '<div class="backgreypublicpayment">';
		print '<div class="logopublicpayment">';
		print '<img id="dolpaymentlogo" src="'.$urllogo.'"';
		print '>';
		print '</div>';
		if (empty($conf->global->MAIN_HIDE_POWERED_BY)) {
			print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">'.$langs->trans("PoweredBy").'<br><img class="poweredbyimg" src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" width="80px"></a></div>';
		}
		print '</div>';
	}
	print '</div>';

	print '<div class="divmainbodylarge">';
}

/**
 * Show footer for new member
 *
 * @return	void
 */
function llxFooterVierge()
{
	print '</div>';

	printCommonFooter('public');

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
if (empty($reshook) && $action == 'add') {
	$error = 0;

	$urlback = '';

	$db->begin();

	if (!GETPOST("email")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Email"))."<br>\n";
	}
	/*if (!GETPOST("societe")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Societe"))."<br>\n";
	}*/
	if (GETPOST("email") && !isValidEmail(GETPOST("email"))) {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorBadEMail", GETPOST("email"))."<br>\n";
	}
	if (!GETPOST("country_id")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Country"))."<br>\n";
	}

	if (!$error) {
		// Vérifier si client existe par l'email
		$thirdparty = new Societe($db);
		$resultfetchthirdparty = $thirdparty->fetch('', '', '', '', '', '', '', '', '', '', $email);

		if ($resultfetchthirdparty<0) {
			$error++;
			$errmsg .= $thirdparty->error;
			$readythirdparty = -1;
		} elseif ($resultfetchthirdparty==0) {
			// creation of a new thirdparty
			if (!empty(GETPOST("societe"))) {
				$thirdparty->name        = GETPOST("societe");
			} else {
				$thirdparty->name        = $email;
			}

			$thirdparty->address     = GETPOST("address");
			$thirdparty->zip         = GETPOST("zipcode");
			$thirdparty->town        = GETPOST("town");
			$thirdparty->client      = 2;
			$thirdparty->fournisseur = 0;
			$thirdparty->country_id  = GETPOST("country_id", 'int');
			$thirdparty->state_id    = GETPOST("state_id", 'int');
			$thirdparty->email       = $email;

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
			$modCodeClient = new $module($db);

			if (empty($tmpcode) && !empty($modCodeClient->code_auto)) {
				$tmpcode = $modCodeClient->getNextValue($thirdparty, 0);
			}
			$thirdparty->code_client = $tmpcode;
			$readythirdparty = $thirdparty->create($user);
		} else {
			// We have an existing thirdparty ready to use
			$readythirdparty = 1;
		}

		if ($readythirdparty < 0) {
			$error++;
			$errmsg .= $thirdparty->error;
		} else {
			// creation of an attendee
			$confattendee = new ConferenceOrBoothAttendee($db);
			$confattendee->fk_soc = $thirdparty->id;
			$confattendee->date_subscription = dol_now();
			$confattendee->email = GETPOST("email");
			$confattendee->fk_actioncomm = $id;
			$resultconfattendee = $confattendee->create($user);
			if ($resultconfattendee < 0) {
				$error++;
				$errmsg .= $confattendee->error;
			}
		}
	}

	if (!$error) {
		$db->commit();
		//Header("Location: ".$urlback);
		//exit;
	} else {
		$db->rollback();
	}
}


/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);

$conference = new ConferenceOrBooth($db);
$resultconf = $conference->fetch($id);
if ($resultconf < 0) {
	setEventMessages(null, $object->errors, "errors");
}

llxHeaderVierge($langs->trans("NewSubscription"));


print load_fiche_titre($langs->trans("NewSubscription"), '', '', 0, 0, 'center');


print '<div align="center">';
print '<div id="divsubscribe">';
print '<div class="center subscriptionformhelptext justify">';

// Welcome message
print $langs->trans("EvntOrgWelcomeMessage");
print $id.".".'<br>';
print $langs->trans("EvntOrgStartDuration");
print dol_print_date($conference->datep).' ';
print $langs->trans("EvntOrgEndDuration");
print ' '.dol_print_date($conference->datef).".";
print '</div>';

dol_htmloutput_errors($errmsg);

// Print form
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="newmember">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'" / >';
print '<input type="hidden" name="entity" value="'.$entity.'" />';
print '<input type="hidden" name="action" value="add" />';
print '<input type="hidden" name="id" value="'.$encodedid.'" />';
print '<input type="hidden" name="securekey" value="'.$encodedsecurekeyandid.'" />';

print '<br>';

print '<br><span class="opacitymedium">'.$langs->trans("FieldsWithAreMandatory", '*').'</span><br>';
//print $langs->trans("FieldsWithIsForPublic",'**').'<br>';

print dol_get_fiche_head('');

print '<script type="text/javascript">
jQuery(document).ready(function () {
    jQuery(document).ready(function () {
        jQuery("#selectcountry_id").change(function() {
           document.newmember.action.value="create";
           document.newmember.submit();
        });
    });
});
</script>';

print '<table class="border" summary="form to subscribe" id="tablesubscribe">'."\n";

// Email
print '<tr><td>'.$langs->trans("Email").' <FONT COLOR="red">*</FONT></td><td><input type="text" name="email" maxlength="255" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('email')).'"></td></tr>'."\n";
// Company
print '<tr id="trcompany" class="trcompany"><td>'.$langs->trans("Company").' </td><td><input type="text" name="societe" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('societe')).'"></td></tr>'."\n";
// Address
print '<tr><td>'.$langs->trans("Address").'</td><td>'."\n";
print '<textarea name="address" id="address" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.dol_escape_htmltag(GETPOST('address', 'restricthtml'), 0, 1).'</textarea></td></tr>'."\n";
// Zip / Town
print '<tr><td>'.$langs->trans('Zip').' / '.$langs->trans('Town').'</td><td>';
print $formcompany->select_ziptown(GETPOST('zipcode'), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6, 1);
print ' / ';
print $formcompany->select_ziptown(GETPOST('town'), 'town', array('zipcode', 'selectcountry_id', 'state_id'), 0, 1);
print '</td></tr>';
// Country
print '<tr><td>'.$langs->trans('Country').'<FONT COLOR="red">*</FONT></td><td>';
$country_id = GETPOST('country_id');
if (!$country_id && !empty($conf->global->MEMBER_NEWFORM_FORCECOUNTRYCODE)) {
	$country_id = getCountry($conf->global->MEMBER_NEWFORM_FORCECOUNTRYCODE, 2, $db, $langs);
}
if (!$country_id && !empty($conf->geoipmaxmind->enabled)) {
	$country_code = dol_user_country();
	//print $country_code;
	if ($country_code) {
		$new_country_id = getCountry($country_code, 3, $db, $langs);
		//print 'xxx'.$country_code.' - '.$new_country_id;
		if ($new_country_id) {
			$country_id = $new_country_id;
		}
	}
}
$country_code = getCountry($country_id, 2, $db, $langs);
print $form->select_country($country_id, 'country_id');
print '</td></tr>';
// State
if (empty($conf->global->SOCIETE_DISABLE_STATE)) {
	print '<tr><td>'.$langs->trans('State').'</td><td>';
	if ($country_code) {
		print $formcompany->select_state(GETPOST("state_id"), $country_code);
	} else {
		print '';
	}
	print '</td></tr>';
}

print "</table>\n";

print dol_get_fiche_end();

// Save
print '<div class="center">';
print '<input type="submit" value="'.$langs->trans("Submit").'" id="submitsave" class="button">';
if (!empty($backtopage)) {
	print ' &nbsp; &nbsp; <input type="submit" value="'.$langs->trans("Cancel").'" id="submitcancel" class="button button-cancel">';
}
print '</div>';


print "</form>\n";
print "<br>";
print '</div></div>';


llxFooterVierge();

$db->close();
