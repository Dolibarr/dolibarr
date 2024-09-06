<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2001-2002  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2006-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       J. Fernando Lagrange    <fernando@demo-tic.org>
 * Copyright (C) 2018-2024	Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2018       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2021       Waël Almoman            <info@almoman.com>
 * Copyright (C) 2022       Udo Tamm                <dev@dolibit.de>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\ingroup    prospect
 *	\brief      Example of form to add a new prospect
 *
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
// Because 2 entities can have the same ref
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}


// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/cunits.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';
// Init vars
$backtopage = GETPOST('backtopage', 'alpha');
$action = GETPOST('action', 'aZ09');

$errmsg = '';
$num = 0;
$error = 0;

// Load translation files
$langs->loadLangs(array("main", "members", "companies", "install", "other", "errors"));

// Security check
if (!isModEnabled('societe')) {
	httponly_accessforbidden('Module Thirdparty not enabled');
}

if (!getDolGlobalString('SOCIETE_ENABLE_PUBLIC')) {
	httponly_accessforbidden("Online form for contact for public visitors has not been enabled");
}


//permissions

$permissiontoadd 	= $user->hasRight('societe', 'creer');

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('publicnewmembercard', 'globalcard'));

$extrafields = new ExtraFields($db);


$objectsoc = new Societe($db);
$user->loadDefaultValues();


/**
 * Show header for new prospect
 *
 * @param 	string		$title				Title
 * @param 	string		$head				Head array
 * @param 	int    		$disablejs			More content into html header
 * @param 	int    		$disablehead		More content into html header
 * @param 	array  		$arrayofjs			Array of complementary js files
 * @param 	array  		$arrayofcss			Array of complementary css files
 * @return	void
 */
function llxHeaderVierge($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = [], $arrayofcss = [])
{
	global $conf, $langs, $mysoc;

	top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss); // Show html headers

	print '<body id="mainbody" class="publicnewmemberform">';

	// Define urllogo
	$urllogo = DOL_URL_ROOT . '/theme/common/login_logo.png';

	if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output . '/logos/thumbs/' . $mysoc->logo_small)) {
		$urllogo = DOL_URL_ROOT . '/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file=' . urlencode('logos/thumbs/' . $mysoc->logo_small);
	} elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output . '/logos/' . $mysoc->logo)) {
		$urllogo = DOL_URL_ROOT . '/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file=' . urlencode('logos/' . $mysoc->logo);
	} elseif (is_readable(DOL_DOCUMENT_ROOT . '/theme/dolibarr_logo.svg')) {
		$urllogo = DOL_URL_ROOT . '/theme/dolibarr_logo.svg';
	}

	print '<header class="center">';

	// Output html code for logo
	if ($urllogo) {
		print '<div class="backgreypublicpayment">';
		print '<div class="logopublicpayment">';
		print '<img id="dolpaymentlogo" src="' . $urllogo . '">';
		print '</div>';
		if (!getDolGlobalString('MAIN_HIDE_POWERED_BY')) {
			print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">' . $langs->trans("PoweredBy") . '<br><img class="poweredbyimg" src="' . DOL_URL_ROOT . '/theme/dolibarr_logo.svg" width="80px"></a></div>';
		}
		print '</div>';
	}

	if (getDolGlobalString('MEMBER_IMAGE_PUBLIC_REGISTRATION')) {
		print '<div class="backimagepublicregistration">';
		print '<img id="idEVENTORGANIZATION_IMAGE_PUBLIC_INTERFACE" src="' . getDolGlobalString('MEMBER_IMAGE_PUBLIC_REGISTRATION') . '">';
		print '</div>';
	}

	print '</header>';

	print '<div class="divmainbodylarge">';
}

/**
 * Show footer for new societe
 *
 * @return	void
 */
function llxFooterVierge()
{
	global $conf, $langs;

	$ext = '';

	print '</div>';

	printCommonFooter('public');

	if (!empty($conf->use_javascript_ajax)) {
		print "\n" . '<!-- Includes JS Footer of Dolibarr -->' . "\n";
		print '<script src="' . DOL_URL_ROOT . '/core/js/lib_foot.js.php?lang=' . $langs->defaultlang . (!empty($ext) ? '&' . $ext : '') . '"></script>' . "\n";
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
if (empty($reshook) && $action == 'add') {
	$error = 0;
	$urlback = '';

	$db->begin();

	if (!GETPOST('name')) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Company")), null, 'errors');
		$error++;
	}

	// Check Captcha code if is enabled
	if (getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA')) {
		$sessionkey = 'dol_antispam_value';
		$ok = (array_key_exists($sessionkey, $_SESSION) && (strtolower($_SESSION[$sessionkey]) == strtolower(GETPOST('code'))));
		if (!$ok) {
			$error++;
			$errmsg .= $langs->trans("ErrorBadValueForCode") . "<br>\n";
			$action = '';
		}
	}

	if (!$error) {
		$societe = new Societe($db);

		$societe->name = GETPOST('name', 'alphanohtml');

		$societe->client = GETPOSTINT('client') ? GETPOSTINT('client') : $societe->client;

		$societe->address	= GETPOST('address', 'alphanohtml');

		$societe->country_id				= GETPOSTINT('country_id');

		$societe->phone					= GETPOST('phone', 'alpha');

		$societe->fax				= GETPOST('fax', 'alpha');

		$societe->email					= trim(GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL));

		$societe->client = 2 ; // our client is a prospect

		$societe->code_client		= '-1';

		$societe->name_alias = GETPOST('name_alias', 'alphanohtml');

		$societe->note_private = GETPOST('note_private');
		if (!$error) {
			$result = $societe->create($user);
			if ($result > 0) {
				require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
				$objectsoc = $societe;

				if (!empty($backtopage)) {
					$urlback = $backtopage;
				} elseif (getDolGlobalString('MEMBER_URL_REDIRECT_SUBSCRIPTION')) {
					$urlback = getDolGlobalString('MEMBER_URL_REDIRECT_SUBSCRIPTION');
					// TODO Make replacement of __AMOUNT__, etc...
				} else {
					$urlback = $_SERVER["PHP_SELF"] . "?action=added&token=" . newToken();
				}
			} else {
				$error++;
				$errmsg .= implode('<br>', $societe->errors);
			}
		}
	}

	if (!$error) {
		$db->commit();

		header("Location: " . $urlback);
		exit;
	} else {
		$db->rollback();
		$action = "create";
	}
}

// Action called after a submitted was send and prospect created successfully
// If MEMBER_URL_REDIRECT_SUBSCRIPTION is set to an url, we never go here because a redirect was done to this url. Same if we ask to redirect to the payment page.
// backtopage parameter with an url was set on prospect submit page, we never go here because a redirect was done to this url.

if (empty($reshook) && $action == 'added') {
	llxHeaderVierge("newSocieteAdded");

	// If we have not been redirected
	print '<br><br>';
	print '<div class="center">';
	print $langs->trans("newSocieteAdded");
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
$extrafields->fetch_name_optionals_label($objectsoc->table_element); // fetch optionals attributes and labels


llxHeaderVierge($langs->trans("ContactUs"));

print '<br>';
print load_fiche_titre(img_picto('', 'member_nocolor', 'class="pictofixedwidth"') . ' &nbsp; ' . $langs->trans("ContactUs"), '', '', 0, '', 'center');


print '<div align="center">';
print '<div id="divsubscribe">';

print '<div class="center subscriptionformhelptext opacitymedium justify">';
if (getDolGlobalString('COMPANY_NEWFORM_TEXT')) {
	print $langs->trans(getDolGlobalString('COMPANY_NEWFORM_TEXT')) . "<br>\n";
} else {
	print $langs->trans("ContactUsDesc", getDolGlobalString("MAIN_INFO_SOCIETE_MAIL")) . "<br>\n";
}
print '</div>';

dol_htmloutput_errors($errmsg);
dol_htmloutput_events();

// Print form
print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST" name="newprospect">' . "\n";
print '<input type="hidden" name="token" value="' . newToken() . '" / >';
print '<input type="hidden" name="entity" value="' . $entity . '" />';
print '<input type="hidden" name="action" value="add" />';
print '<br>';

$messagemandatory = '<span class="">' . $langs->trans("FieldsWithAreMandatory", '*') . '</span>';
//print '<br><span class="opacitymedium">'.$langs->trans("FieldsWithAreMandatory", '*').'</span><br>';
//print $langs->trans("FieldsWithIsForPublic",'**').'<br>';

print dol_get_fiche_head();

print '<script type="text/javascript">
jQuery(document).ready(function () {
	jQuery(document).ready(function () {
		function initmorphy()
		{
			console.log("Call initmorphy");
			if (jQuery("#morphy").val() == \'phy\') {
				jQuery("#trcompany").hide();
			}
			if (jQuery("#morphy").val() == \'mor\') {
				jQuery("#trcompany").show();
			}
		}
		initmorphy();
		jQuery("#morphy").change(function() {
			initmorphy();
		});
		jQuery("#selectcountry_id").change(function() {
		document.newprospect.action.value="create";
		document.newprospect.submit();
		});
		jQuery("#typeid").change(function() {
		document.newprospect.action.value="create";
		document.newprospect.submit();
		});
	});
});
</script>';


print '<table class="border" summary="form to subscribe" id="tablesubscribe">' . "\n";
//Third party name
/*
if ($objectsoc->particulier || $private) {
	print '<span id="TypeName" class="fieldrequired">'.$langs->trans('ThirdPartyName').' / '.$langs->trans('LastName', 'name').'</span>';
} else {
	print '<span id="TypeName" class="fieldrequired">'.$form->editfieldkey('ThirdPartyName', 'name', '', $objectsoc, 0).'</span>';
}
*/
print '<tr class="tr-field-thirdparty-name"><td class="titlefieldcreate">'; // text appreas left
print '<input type="hidden" name="ThirdPartyName" value="' . $langs->trans('ThirdPartyName') . '">';
print '<span id="TypeName" class="fieldrequired"  title="' .dol_escape_htmltag($langs->trans("FieldsWithAreMandatory", '*')) . '" >' . $form->editfieldkey('Company', 'name', '', $objectsoc, 0) . '<span class="star"> *</span></span>';
print '</td><td>'; // inline input
print '<input type="text" class="minwidth300" maxlength="128" name="name" id="name" value="' . dol_escape_htmltag($objectsoc->name) . '" autofocus="autofocus">';
//

// Name and lastname
print '<tr><td class="classfortooltip" title="' . dol_escape_htmltag($messagemandatory) . '">' . $langs->trans("Firstname") . ' <span class="star">*</span></td><td><input type="text" name="firstname" class="minwidth150" value="' . dol_escape_htmltag(GETPOST('firstname')) . '"></td></tr>' . "\n";

print '<tr><td class="classfortooltip" title="' . dol_escape_htmltag($messagemandatory) . '">' . $langs->trans("Lastname") . ' <span class="star">*</span></td><td><input type="text" name="lastname" class="minwidth150" value="' . dol_escape_htmltag(GETPOST('lastname')) . '"></td></tr>' . "\n";

// Address
print '<tr><td class="tdtop">';
print $form->editfieldkey('Address', 'address', '', $objectsoc, 0);
print '</td>';
print '<td>';
print '<textarea name="address" id="address" class="quatrevingtpercent" rows="' . ROWS_2 . '" wrap="soft">';
print dol_escape_htmltag($objectsoc->address, 0, 1);
print '</textarea>';
print $form->widgetForTranslation("address", $objectsoc, $permissiontoadd, 'textarea', 'alphanohtml', 'quatrevingtpercent');
print '</td></tr>';

// Country
print '<tr><td>' . $form->editfieldkey('Country', 'selectcountry_id', '', $objectsoc, 0) . '</td><td class="maxwidthonsmartphone">';
print img_picto('', 'country', 'class="pictofixedwidth"');
print $form->select_country((GETPOSTISSET('country_id') ? GETPOST('country_id') : $objectsoc->country_id), 'country_id', '', 0, 'minwidth300 maxwidth500 widthcentpercentminusx');
if ($user->admin) {
	print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
}
print '</td></tr>';

// Phone / Fax
print '<tr><td>' . $form->editfieldkey('Phone', 'phone', '', $objectsoc, 0) . '</td>';
print '<td>' . img_picto('', 'object_phoning', 'class="pictofixedwidth"') . ' <input type="text" name="phone" id="phone" class="maxwidth200 widthcentpercentminusx" value="' . (GETPOSTISSET('phone') ? GETPOST('phone', 'alpha') : $objectsoc->phone) . '"></td>';
print '</tr>';

print '<tr>';
print '<td>' . $form->editfieldkey('Fax', 'fax', '', $objectsoc, 0) . '</td>';
print '<td>' . img_picto('', 'object_phoning_fax', 'class="pictofixedwidth"') . ' <input type="text" name="fax" id="fax" class="maxwidth200 widthcentpercentminusx" value="' . (GETPOSTISSET('fax') ? GETPOST('fax', 'alpha') : $objectsoc->fax) . '"></td>';
print '</tr>';

// Email / Web
print '<tr><td>' . $form->editfieldkey('EMail', 'email', '', $objectsoc, 0, 'string', '', !getDolGlobalString('SOCIETE_EMAIL_MANDATORY') ? '' : $conf->global->SOCIETE_EMAIL_MANDATORY) . '</td>';
print '<td>' . img_picto('', 'object_email', 'class="pictofixedwidth"') . ' <input type="text" class="maxwidth200 widthcentpercentminusx" name="email" id="email" value="' . $objectsoc->email . '"></td>';
if (isModEnabled('mailing') && getDolGlobalString('THIRDPARTY_SUGGEST_ALSO_ADDRESS_CREATION')) {
	if ($conf->browser->layout == 'phone') {
		print '</tr><tr>';
	}
	print '<td class="individualline noemail">' . $form->editfieldkey($langs->trans('No_Email') . ' (' . $langs->trans('Contact') . ')', 'contact_no_email', '', $objectsoc, 0) . '</td>';
	print '<td class="individualline" ' . (($conf->browser->layout == 'phone') || !isModEnabled('mailing') ? ' colspan="3"' : '') . '>' . $form->selectyesno('contact_no_email', (GETPOSTISSET("contact_no_email") ? GETPOST("contact_no_email", 'alpha') : (empty($objectsoc->no_email) ? 0 : 1)), 1, false, 1) . '</td>';
}
print '</tr>';

print '<tr><td>' . $form->editfieldkey('Web', 'url', '', $objectsoc, 0) . '</td>';
print '<td>' . img_picto('', 'globe', 'class="pictofixedwidth"') . ' <input type="text" class="maxwidth500 widthcentpercentminusx" name="url" id="url" value="' . $objectsoc->url . '"></td></tr>';


// Comments
print '<tr>';
print '<td class="tdtop">' . $langs->trans("Comments") . '</td>';
print '<td class="tdtop"><textarea name="note_private" id="note_private" wrap="soft" class="quatrevingtpercent" rows="' . ROWS_3 . '">' . dol_escape_htmltag(GETPOST('note_private', 'restricthtml'), 0, 1) . '</textarea></td>';
print '</tr>' . "\n";
// TODO Move this into generic feature.



// Display Captcha code if is enabled
if (getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA')) {
	require_once DOL_DOCUMENT_ROOT . '/core/lib/security2.lib.php';
	print '<tr><td class="titlefield"><label for="email"><span class="fieldrequired">' . $langs->trans("SecurityCode") . '</span></label></td><td>';
	print '<span class="span-icon-security inline-block">';
	print '<input id="securitycode" placeholder="' . $langs->trans("SecurityCode") . '" class="flat input-icon-security width150" type="text" maxlength="5" name="code" tabindex="3" />';
	print '</span>';
	print '<span class="nowrap inline-block">';
	print '<img class="inline-block valignmiddle" src="' . DOL_URL_ROOT . '/core/antispamimage.php" border="0" width="80" height="32" id="img_securitycode" />';
	print '<a class="inline-block valignmiddle" href="' . $php_self . '" tabindex="4" data-role="button">' . img_picto($langs->trans("Refresh"), 'refresh', 'id="captcha_refresh_img"') . '</a>';
	print '</span>';
	print '</td></tr>';
}

print "</table>\n";

print dol_get_fiche_end();

// Save / Submit
print '<div class="center">';
print '<input type="submit" value="' . $langs->trans("Send") . '" id="submitsave" class="button">';
if (!empty($backtopage)) {
	print ' &nbsp; &nbsp; <input type="submit" value="' . $langs->trans("Cancel") . '" id="submitcancel" class="button button-cancel">';
}
print '</div>';


print "</form>\n";
print "<br>";
print '</div></div>';



llxFooterVierge();

$db->close();
