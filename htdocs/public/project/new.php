<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2001-2002  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2006-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       J. Fernando Lagrange    <fernando@demo-tic.org>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
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
 *	\file       htdocs/public/project/new.php
 *	\ingroup    project
 *	\brief      Example of form to add a new lead
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/json.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

// Init vars
$errmsg = '';
$error = 0;
$backtopage = GETPOST('backtopage', 'alpha');
$action = GETPOST('action', 'aZ09');

// Load translation files
$langs->loadLangs(array("members", "companies", "install", "other"));

if (empty($conf->global->PROJECT_ENABLE_PUBLIC)) {
	print $langs->trans("Form for public lead registration has not been enabled");
	exit;
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('publicnewleadcard', 'globalcard'));

$extrafields = new ExtraFields($db);

$object = new Project($db);

$user->loadDefaultValues();

// Security check
if (empty($conf->projet->enabled)) {
	accessforbidden('', 0, 0, 1);
}


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

	if (!empty($conf->global->PROJECT_IMAGE_PUBLIC_ORGANIZEDEVENT)) {
		print '<div class="backimagepublicorganizedevent">';
		print '<img id="idPROJECT_IMAGE_PUBLIC_ORGANIZEDEVENT" src="'.$conf->global->PROJECT_IMAGE_PUBLIC_ORGANIZEDEVENT.'">';
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


$arrayofdata = array();
if (GETPOST('action') == 'addlead') {
	// When a json request is sent
	$entityBody = file_get_contents('php://input');

	if ($entityBody) {
		$arrayofdata = json_decode($entityBody, true);
	}

	print 'Date received and lead created';

	$db->close();
	exit;
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

	// test if lead already exists
	/*
	if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) {
		if (!GETPOST('login')) {
			$error++;
			$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Login"))."<br>\n";
		}
		$sql = "SELECT login FROM ".MAIN_DB_PREFIX."adherent WHERE login='".$db->escape(GETPOST('login'))."'";
		$result = $db->query($sql);
		if ($result) {
			$num = $db->num_rows($result);
		}
		if ($num != 0) {
			$error++;
			$langs->load("errors");
			$errmsg .= $langs->trans("ErrorLoginAlreadyExists")."<br>\n";
		}
		if (!GETPOSTISSET("pass1") || !GETPOSTISSET("pass2") || GETPOST("pass1", 'none') == '' || GETPOST("pass2", 'none') == '' || GETPOST("pass1", 'none') != GETPOST("pass2", 'none')) {
			$error++;
			$langs->load("errors");
			$errmsg .= $langs->trans("ErrorPasswordsMustMatch")."<br>\n";
		}
		if (!GETPOST("email")) {
			$error++;
			$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("EMail"))."<br>\n";
		}
	}
	*/
	if (GETPOST('type') <= 0) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type"))."<br>\n";
	}
	if (!in_array(GETPOST('morphy'), array('mor', 'phy'))) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Nature'))."<br>\n";
	}
	if (!GETPOST("lastname")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Lastname"))."<br>\n";
	}
	if (!GETPOST("firstname")) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Firstname"))."<br>\n";
	}
	if (GETPOST("email") && !isValidEmail(GETPOST("email"))) {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorBadEMail", GETPOST("email"))."<br>\n";
	}

	if (!$error) {
		// email a peu pres correct et le login n'existe pas
		$proj = new Project($db);
		$proj->statut      = -1;
		$proj->email       = GETPOST("email");
		$proj->note_private = GETPOST("note_private");


		// Fill array 'array_options' with data from add form
		$extrafields->fetch_name_optionals_label($proj->table_element);
		$ret = $extrafields->setOptionalsFromPost(null, $proj);
		if ($ret < 0) {
			$error++;
		}

		$result = $proj->create($user);
		if ($result > 0) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
			$object = $proj;

			if ($object->email) {
				$subject = '';
				$msg = '';

				// Send subscription email
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
				$formmail = new FormMail($db);
				// Set output language
				$outputlangs = new Translate('', $conf);
				$outputlangs->setDefaultLang(empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang);
				// Load traductions files required by page
				$outputlangs->loadLangs(array("main", "members"));
				// Get email content from template
				$arraydefaultmessage = null;
				$labeltouse = $conf->global->PROJECT_EMAIL_TEMPLATE_AUTOLEAD;

				if (!empty($labeltouse)) {
					$arraydefaultmessage = $formmail->getEMailTemplate($db, 'project', $user, $outputlangs, 0, 1, $labeltouse);
				}

				if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
					$subject = $arraydefaultmessage->topic;
					$msg     = $arraydefaultmessage->content;
				}

				$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
				complete_substitutions_array($substitutionarray, $outputlangs, $object);
				$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
				$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnValid()), $substitutionarray, $outputlangs);

				if ($subjecttosend && $texttosend) {
					$moreinheader = 'X-Dolibarr-Info: send_an_email by public/lead/new.php'."\r\n";

					$result = $object->send_an_email($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
				}
				/*if ($result < 0) {
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
				}*/
			}

			if (!empty($backtopage)) {
				$urlback = $backtopage;
			} elseif (!empty($conf->global->PROJECT_URL_REDIRECT_LEAD)) {
				$urlback = $conf->global->PROJECT_URL_REDIRECT_LEAD;
				// TODO Make replacement of __AMOUNT__, etc...
			} else {
				$urlback = $_SERVER["PHP_SELF"]."?action=added";
			}

			if (!empty($entity)) {
				$urlback .= '&entity='.$entity;
			}
			dol_syslog("project lead ".$proj->ref." was created, we redirect to ".$urlback);
		} else {
			$error++;
			$errmsg .= join('<br>', $proj->errors);
		}
	}

	if (!$error) {
		$db->commit();

		Header("Location: ".$urlback);
		exit;
	} else {
		$db->rollback();
	}
}

// Create lead from $arrayofdata
if (empty($reshook) && !empty($arrayofdata)) {
	// TODO
	dol_syslog(var_export($arrayofdata, true));
	// ...
}

// Action called after a submitted was send and member created successfully
// If MEMBER_URL_REDIRECT_SUBSCRIPTION is set to url we never go here because a redirect was done to this url.
// backtopage parameter with an url was set on member submit page, we never go here because a redirect was done to this url.
if (empty($reshook) && $action == 'added') {
	llxHeaderVierge($langs->trans("NewMemberForm"));

	// Si on a pas ete redirige
	print '<br>';
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
$extrafields->fetch_name_optionals_label('project'); // fetch optionals attributes and labels


llxHeaderVierge($langs->trans("NewContact"));


print load_fiche_titre($langs->trans("NewContact"), '', '', 0, 0, 'center');


print '<div align="center">';
print '<div id="divsubscribe">';

print '<div class="center subscriptionformhelptext justify">';
if (!empty($conf->global->PROJECT_NEWFORM_TEXT)) {
	print $langs->trans($conf->global->PROJECT_NEWFORM_TEXT)."<br>\n";
} else {
	print $langs->trans("NewLeadDesc", $conf->global->MAIN_INFO_SOCIETE_MAIL)."<br>\n";
}
print '</div>';

dol_htmloutput_errors($errmsg);

// Print form
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="newlead">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'" / >';
print '<input type="hidden" name="entity" value="'.$entity.'" />';
print '<input type="hidden" name="action" value="add" />';

print '<br>';

print '<br><span class="opacitymedium">'.$langs->trans("FieldsWithAreMandatory", '*').'</span><br>';
//print $langs->trans("FieldsWithIsForPublic",'**').'<br>';

print dol_get_fiche_head('');

print '<script type="text/javascript">
jQuery(document).ready(function () {
    jQuery(document).ready(function () {
        jQuery("#selectcountry_id").change(function() {
           document.newlead.action.value="create";
           document.newlead.submit();
        });
    });
});
</script>';


print '<table class="border" summary="form to subscribe" id="tablesubscribe">'."\n";

// Lastname
print '<tr><td>'.$langs->trans("Lastname").' <FONT COLOR="red">*</FONT></td><td><input type="text" name="lastname" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('lastname')).'"></td></tr>'."\n";
// Firstname
print '<tr><td>'.$langs->trans("Firstname").' <FONT COLOR="red">*</FONT></td><td><input type="text" name="firstname" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('firstname')).'"></td></tr>'."\n";
// Company
print '<tr id="trcompany" class="trcompany"><td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('societe')).'"></td></tr>'."\n";
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
print '<tr><td>'.$langs->trans('Country').'</td><td>';
$country_id = GETPOST('country_id');
if (!$country_id && !empty($conf->global->PROJECT_NEWFORM_FORCECOUNTRYCODE)) {
	$country_id = getCountry($conf->global->PROJECT_NEWFORM_FORCECOUNTRYCODE, 2, $db, $langs);
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
// EMail
print '<tr><td>'.$langs->trans("Email").' <FONT COLOR="red">*</FONT></td><td><input type="text" name="email" maxlength="255" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('email')).'"></td></tr>'."\n";
// Other attributes
$tpl_context = 'public'; // define template context to public
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';
// Comments
print '<tr>';
print '<td class="tdtop">'.$langs->trans("Comments").'</td>';
print '<td class="tdtop"><textarea name="note_private" id="note_private" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.dol_escape_htmltag(GETPOST('note_private', 'restricthtml'), 0, 1).'</textarea></td>';
print '</tr>'."\n";

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
