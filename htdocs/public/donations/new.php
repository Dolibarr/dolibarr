<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2001-2002  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2006-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       J. Fernando Lagrange    <fernando@demo-tic.org>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
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
 *	\file       htdocs/public/donations/new.php
 *	\ingroup    don
 *	\brief      Example of form to add a new donation
 *
 *  Note that you can add following constant to change behaviour of page
 *  DONATION_MIN_AMOUNT                   Minimum amount
 *  DONATION_NEWFORM_PAYONLINE            Suggest payment with paypal, paybox or stripe
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
if (!isModEnabled('don')) {
	httponly_accessforbidden('Module don not enabled');
}

if (!getDolGlobalString('DONATION_ENABLE_PUBLIC')) {
	httponly_accessforbidden("Donation form for public visitors has not been enabled");
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
//$hookmanager->initHooks(array( 'globalcard'));

$extrafields = new ExtraFields($db);

$object = new Don($db);

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
 * @return	void
 */
function llxHeaderVierge($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = [], $arrayofcss = [])  // @phan-suppress-current-line PhanRedefineFunction
{
	global $conf, $langs, $mysoc;

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss); // Show html headers

	print '<body id="mainbody" class="publicnewmemberform">';

	include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	htmlPrintOnlineHeader($mysoc, $langs, 1, getDolGlobalString('DONATION_PUBLIC_INTERFACE'), 'DONATION_IMAGE_PUBLIC_REGISTRATION');

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

	$db->begin();

	if (GETPOST("email", "aZ09arobase") && !isValidEmail(GETPOST("email", "aZ09arobase"))) {
		$langs->load('errors');
		$error++;
		$errmsg .= $langs->trans("ErrorBadEMail", GETPOST("email", "aZ09arobase"))."<br>\n";
	}
	if (!GETPOST('amount') || GETPOST('amount') < getDolGlobalInt('DONATION_MIN_AMOUNT')) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldMinimumAmount", getDolGlobalInt('DONATION_MIN_AMOUNT'))."<br>\n";
	}

	// Check Captcha code if is enabled
	if (getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_DONATION')) {
		$sessionkey = 'dol_antispam_value';
		$ok = (array_key_exists($sessionkey, $_SESSION) && (strtolower($_SESSION[$sessionkey]) == strtolower(GETPOST('code'))));
		if (!$ok) {
			$error++;
			$errmsg .= $langs->trans("ErrorBadValueForCode")."<br>\n";
			$action = '';
		}
	}

	$public = GETPOSTISSET('public') ? 1 : 0;
	if ((isModEnabled('project') || isModEnabled('eventorganization')) && GETPOSTINT('project_id')) {
		// Check if project is valid
		$project = new Project($db);
		$result = $project->fetch(GETPOSTINT('project_id'));
		if ($result > 0) {
			$projectId = $project->id;
		}
	}

	if (!$error) {
		$donation = new Don($db);

		$donation->amount 		= (float) GETPOST('amount');
		$donation->status      	= Don::STATUS_DRAFT;
		$donation->public      	= $public;
		$donation->date 		= dol_now();
		$donation->firstname   	= GETPOST('firstname');
		$donation->lastname    	= GETPOST('lastname');
		$donation->company     	= GETPOST('societe');
		$donation->societe     	= $donation->company;
		$donation->address     	= GETPOST('address');
		$donation->zip         	= GETPOST('zipcode');
		$donation->town        	= GETPOST('town');
		$donation->email       	= GETPOST('email', 'aZ09arobase');
		$donation->country_id  	= GETPOSTINT('country_id');
		// Assign project ID to the donation if a valid project is selected
		if (!empty($projectId)) {
			$donation->fk_project = $projectId;
		}

		$donation->state_id    	= GETPOSTINT('state_id');
		$donation->note_private = GETPOST('note_private');

		$donation->ip = getUserRemoteIP();

		$nb_post_max = getDolGlobalInt("MAIN_SECURITY_MAX_POST_ON_PUBLIC_PAGES_BY_IP_ADDRESS", 200);
		$now = dol_now();
		$minmonthpost = dol_time_plus_duree($now, -1, "m");
		// Calculate nb of post for IP
		$nb_post_ip = 0;
		if ($nb_post_max > 0) {	// Calculate only if there is a limit to check
			$sql = "SELECT COUNT(rowid) as nb_don";
			$sql .= " FROM ".MAIN_DB_PREFIX."don";
			$sql .= " WHERE ip = '".$db->escape($donation->ip)."'";
			$sql .= " AND datedon > '".$db->idate($minmonthpost)."'";
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$i++;
					$obj = $db->fetch_object($resql);
					$nb_post_ip = $obj->nb_don;
				}
			}
		}

		if ($nb_post_max > 0 && $nb_post_ip >= $nb_post_max) {
			$error++;
			$errmsg .= $langs->trans("AlreadyTooMuchPostOnThisIPAdress");
			array_push($donation->errors, $langs->trans("AlreadyTooMuchPostOnThisIPAdress"));
		}

		// Fill array 'array_options' with data from add form
		$extrafields->fetch_name_optionals_label($donation->table_element);
		$ret = $extrafields->setOptionalsFromPost(null, $donation);
		if ($ret < 0) {
			$error++;
			$errmsg .= $donation->error;
		}

		if (!$error) {
			$result = $donation->create($user);
			if ($result > 0) {
				if (!empty($backtopage)) {
					$urlback = $backtopage;
				} else {
					$urlback = $_SERVER["PHP_SELF"]."?action=added&token=".newToken();
				}

				if (getDolGlobalString('DONATION_NEWFORM_PAYONLINE') && getDolGlobalString('DONATION_NEWFORM_PAYONLINE') != '-1') {
					$urlback = getOnlinePaymentUrl(0, 'donation', (string) $donation->id, 0, '');

					if (GETPOST('email')) {
						$urlback .= '&email='.urlencode(GETPOST('email'));
					}
					if (getDolGlobalString('DONATION_NEWFORM_PAYONLINE') != '-1' && getDolGlobalString('DONATION_NEWFORM_PAYONLINE') != 'all') {
						$urlback .= '&paymentmethod='.urlencode(getDolGlobalString('DONATION_NEWFORM_PAYONLINE'));
					}
				} else {
					if (!empty($entity)) {
						$urlback .= '&entity='.((int) $entity);
					}
				}
			} else {
				$error++;
				$errmsg .= implode('<br>', $donation->errors);
			}
		}
	}

	if (!$error) {
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

if (isModEnabled('project') || isModEnabled('eventorganization')) {
	$project = new Project($db);
	$result = $project->fetch(GETPOSTINT('project_id'));
	if ($result > 0) {
		$projectId = $project->id;
		$projectTitle = $project->title;
	}
}


llxHeaderVierge($langs->trans("NewDonation"));

print '<br>';
print load_fiche_titre(img_picto('', '', 'class="pictofixedwidth"').' &nbsp; '.$langs->trans("NewDonation"), '', '', 0, '', 'center');


print '<div align="center">';
print '<div id="divsubscribe">';

print '<div class="center subscriptionformhelptext opacitymedium justify">';
print $langs->trans("NewDonationDesc", getDolGlobalString("MAIN_INFO_SOCIETE_MAIL"))."<br>\n";

print '</div>';

dol_htmloutput_errors($errmsg);
dol_htmloutput_events();

// Print form
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="newdonation">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'" />';
print '<input type="hidden" name="entity" value="'.$entity.'" />';
print '<input type="hidden" name="page_y" value="" />';
print '<input type="hidden" name="project_id" value="'.GETPOST('project_id').'" />';

if (!$action || $action == 'create') {
	print '<input type="hidden" name="action" value="add" />';
	print '<br>';

	$messagemandatory = '<span class="">'.$langs->trans("FieldsWithAreMandatory", '*').'</span>';
	//print '<br><span class="opacitymedium">'.$langs->trans("FieldsWithAreMandatory", '*').'</span><br>';
	//print $langs->trans("FieldsWithIsForPublic",'**').'<br>';

	print dol_get_fiche_head();

	print '<script type="text/javascript">
	jQuery(document).ready(function () {
		jQuery(document).ready(function () {
			jQuery("#selectcountry_id").change(function() {
			document.newdonation.action.value="create";
			document.newdonation.submit();
			});
		});
	});
	</script>';

	print '<table class="border" summary="form to subscribe" id="tablesubscribe">'."\n";

	// Add a specific style or table head for the project row
	if ((isModEnabled('project') || isModEnabled('eventorganization')) && !empty($projectTitle)) {
		print '<tr>';
		print '<td class="project-label">' . $langs->trans("project") . '</td>';
		print '<td class="project-value">' . dol_escape_htmltag($projectTitle) . '</td>';
		print '</tr>';
	}

	// Anonymous
	/*
	print '<tr>';
	print '<td class="titlefieldcreate"><label for="anonymous">'.$form->textwithpicto($langs->trans("donAnonymous"), $langs->trans("AnonymousDonationTooltip")).'</label></td>';
	print '<td><input type="checkbox" name="anonymous" id="anonymous" '.(GETPOST('anonymous') ? 'checked' : '').'></td>';
	print '</tr>'."\n";
	print '<script type="text/javascript">
	jQuery(document).ready(function () {
		function toggleFields() {
			if (jQuery("#anonymous").is(":checked")) {
				jQuery("#tablesubscribe").find("#trcompany, #trfirstname, #trlastname, #tremail, #tradress, #trzip, #trcountry, #trstate, #trseparator").hide();
			} else {
				jQuery("#tablesubscribe tr").show();
			}
		}

		// Initial toggle on page load
		toggleFields();

		// Toggle fields on checkbox change
		jQuery("#anonymous").change(function () {
			toggleFields();
		});
	});
	</script>';
	*/

	// EMail
	print '<tr id="tremail"><td class="fieldrequired" title="'.dol_escape_htmltag($messagemandatory).'">'.$langs->trans("Email").'</td><td>';
	//print img_picto('', 'email', 'class="pictofixedwidth"');
	print '<input type="email" name="email" maxlength="255" class="minwidth200" value="'.dol_escape_htmltag(GETPOST('email', "aZ09arobase")).'"></td></tr>'."\n";

	// Company
	print '<tr id="trcompany" class="trcompany"><td>'.$langs->trans("Company").'</td><td>';
	print img_picto('', 'company', 'class="pictofixedwidth paddingright"');
	print '<input type="text" name="societe" class="minwidth150 widthcentpercentminusx" value="'.dol_escape_htmltag(GETPOST('societe')).'"></td></tr>'."\n";

	// Firstname
	print '<tr id="trfirstname"><td class="classfortooltip" title="'.dol_escape_htmltag($messagemandatory).'">'.$langs->trans("Firstname").'</td><td><input type="text" name="firstname" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('firstname')).'"></td></tr>'."\n";

	// Lastname
	print '<tr id="trlastname"><td class="classfortooltip" title="'.dol_escape_htmltag($messagemandatory).'">'.$langs->trans("Lastname").'</td><td><input type="text" name="lastname" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('lastname')).'"></td></tr>'."\n";

	// Address
	print '<tr id="tradress"><td>'.$langs->trans("Address").'</td><td>'."\n";
	print '<textarea name="address" id="address" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.dol_escape_htmltag(GETPOST('address', 'restricthtml'), 0, 1).'</textarea></td></tr>'."\n";

	// Zip / Town
	print '<tr id="trzip"><td>'.$langs->trans('Zip').' / '.$langs->trans('Town').'</td><td>';
	print $formcompany->select_ziptown(GETPOST('zipcode'), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 0, 1, '', 'width75');
	print ' / ';
	print $formcompany->select_ziptown(GETPOST('town'), 'town', array('zipcode', 'selectcountry_id', 'state_id'), 0, 1);
	print '</td></tr>';

	// Country
	print '<tr id="trcountry"><td>'.$langs->trans('Country').'</td><td>';
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

	// State
	if (!getDolGlobalString('SOCIETE_DISABLE_STATE')) {
		print '<tr id="trstate"><td>'.$langs->trans('State').'</td><td>';
		if ($country_code) {
			print img_picto('', 'state', 'class="pictofixedwidth paddingright"');
			print $formcompany->select_state(GETPOSTINT("state_id"), $country_code);
		}
		print '</td></tr>';
	}

	// Other attributes
	$parameters['tpl_context'] = 'public';	// define template context to public
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '<tr id="trseparator"><td colspan="2"><hr></td></tr>';

	// Public
	$publiclabel = $langs->trans("publicDonationFieldHelp", getDolGlobalString('MAIN_INFO_SOCIETE_NOM'));
	print '<tr><td><label for="public">'.$form->textwithpicto($langs->trans("donationPublic"), $publiclabel).'</label></td>';
	print '<td><input type="checkbox" name="public" id="public"></td></tr>'."\n";

	if (getDolGlobalString('DONATION_NEWFORM_PAYONLINE')) {
		$amount = (GETPOST('amount') ? price2num(GETPOST('amount', 'alpha'), 'MT', 2) : '');

		// - If a min is set, we take it into account
		$amount = max(0, (float) $amount, (float) getDolGlobalInt("DONATION_MIN_AMOUNT"));

		// Clean the amount
		$amount = price2num($amount);
		$showedamount = $amount > 0 ? $amount : 0;
		print '<tr><td>'.$langs->trans("donationAmount");
		print ' <span>*</span></td><td class="nowrap">';

		print '<input type="text" name="amount" id="amount" class="flat amount width50" value="'.$showedamount.'">';
		print ' '.$langs->trans("Currency".$conf->currency).'<span class="opacitymedium hideifautoturnover"> - ';
		print $langs->trans("AnyAmountForDonation");
		print '</span>';

		print '</td></tr>';
	}

	// Comments
	print '<tr>';
	print '<td class="tdtop">'.$langs->trans("Comments").'</td>';
	print '<td class="tdtop"><textarea name="note_private" id="note_private" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.dol_escape_htmltag(GETPOST('note_private', 'restricthtml'), 0, 1).'</textarea></td>';
	print '</tr>'."\n";

	// Display Captcha code if is enabled
	if (getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_DONATION')) {
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
	}

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
