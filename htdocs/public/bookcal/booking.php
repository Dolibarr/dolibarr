<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2001-2002  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2006-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012       J. Fernando Lagrange    <fernando@demo-tic.org>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2021       Waël Almoman            <info@almoman.com>
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
 *	\file       htdocs/public/partnership/new.php
 *	\ingroup    member
 *	\brief      Example of form to add a new member
 */

use Stripe\Event;

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
require_once DOL_DOCUMENT_ROOT.'/partnership/class/partnership.class.php';
require_once DOL_DOCUMENT_ROOT.'/partnership/class/partnership_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncommreminder.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Init vars
$errmsg = '';
$num = 0;
$error = 0;
$backtopage = GETPOST('backtopage', 'alpha');
$action = GETPOST('action', 'aZ09');

// Load translation files
$langs->loadLangs(array("main", "members", "companies", "install", "other"));


// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('publicnewpartnershipcard', 'globalcard'));


$object = new ActionComm($db);
$cactioncomm = new CActionComm($db);
$contact = new Contact($db);
$formfile = new FormFile($db);
$formactions = new FormActions($db);
$extrafields = new ExtraFields($db);



$user->loadDefaultValues();


/**
 * Show header for new partnership
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
		print '<img id="dolpaymentlogo" src="'.$urllogo.'">';
		print '</div>';
		if (empty($conf->global->MAIN_HIDE_POWERED_BY)) {
			print '<div class="poweredbypublicpayment opacitymedium right"><a class="poweredbyhref" href="https://www.dolibarr.org?utm_medium=website&utm_source=poweredby" target="dolibarr" rel="noopener">'.$langs->trans("PoweredBy").'<br><img class="poweredbyimg" src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.svg" width="80px"></a></div>';
		}
		print '</div>';
	}

	if (!empty($conf->global->PARTNERSHIP_IMAGE_PUBLIC_REGISTRATION)) {
		print '<div class="backimagepublicregistration">';
		print '<img id="idPARTNERSHIP_IMAGE_PUBLIC_INTERFACE" src="'.$conf->global->PARTNERSHIP_IMAGE_PUBLIC_REGISTRATION.'">';
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

	/*if (GETPOST('typeid') <= 0) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type"))."<br>\n";
	}*/
	if (!GETPOST('lastname')) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Lastname"))."<br>\n";
	}
	if (!GETPOST('firstname')) {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Firstname"))."<br>\n";
	}
	if (empty(GETPOST('email'))) {
		$error++;
		$errmsg .= $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Email'))."<br>\n";
	} elseif (GETPOST("email") && !isValidEmail(GETPOST("email"))) {
		$langs->load('errors');
		$error++;
		$errmsg .= $langs->trans("ErrorBadEMail", GETPOST("email"))."<br>\n";
	}

	$public = GETPOSTISSET('public') ? 1 : 0;

	if (!$error) {
		//$partnership = new Partnership($db);
		$events = new Events($db);


		// We try to find the thirdparty or the member
		if (getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR', 'thirdparty') == 'thirdparty') {
			$event->fk_member = 0;
		} elseif (getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR', 'thirdparty') == 'member') {
			$event->fk_soc = 0;
		}

		$events->statut      = -1;
		$events->firstname   = GETPOST('firstname');
		$events->lastname    = GETPOST('lastname');
		$events->address     = GETPOST('address');
		$events->zip         = GETPOST('zipcode');
		$events->town        = GETPOST('town');
		$events->email       = GETPOST('email');
		$events->country_id  = GETPOST('country_id', 'int');
		$events->state_id    = GETPOST('state_id', 'int');
		//$partnership->typeid      = $conf->global->PARTNERSHIP_NEWFORM_FORCETYPE ? $conf->global->PARTNERSHIP_NEWFORM_FORCETYPE : GETPOST('typeid', 'int');
		$event->note_private = GETPOST('note_private');

		// Fill array 'array_options' with data from add form
		$extrafields->fetch_name_optionals_label($partnership->table_element);
		$ret = $extrafields->setOptionalsFromPost(null, $partnership);
		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			$result = $event->create($user);
			if ($result > 0) {
				$db->commit();
				$urlback = DOL_URL_ROOT.'/public/partnership/new.php?action=confirm&id='.$event->id;
				header('Location: '.$urlback);
				exit;
			} else {
				$db->rollback();
				$errmsg = $event->error;
				$error++;
			}
		} else {
			$error++;
			$errmsg .= join('<br>', $event->errors);
		}
	}
}

// Action called after a submitted was send and member created successfully
// If PARTNERSHIP_URL_REDIRECT_SUBSCRIPTION is set to url we never go here because a redirect was done to this url.
// backtopage parameter with an url was set on member submit page, we never go here because a redirect was done to this url.
if (empty($reshook) && $action == 'added') {
	llxHeaderVierge($langs->trans("NewPartnershipForm"));

	// Si on a pas ete redirige
	print '<br><br>';
	print '<div class="center">';
	print $langs->trans("NewPartnershipbyWeb");
	print '</div>';

	llxFooterVierge();

	exit;
}



/*
 * View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);

$extrafields->fetch_name_optionals_label($partnership->table_element); // fetch optionals attributes and labels


llxHeaderVierge($langs->trans("NewBookingRequest"));


print load_fiche_titre($langs->trans("NewBookingRequest"), '', '', 0, 0, 'center');



// View

// Add new Events form
$contact = new Contact($db);

	$socpeopleassigned = GETPOST("socpeopleassigned", 'array');
if (!empty($socpeopleassigned[0])) {
	$result = $contact->fetch($socpeopleassigned[0]);
	if ($result < 0) {
		dol_print_error($db, $contact->error);
	}
}

	dol_set_focus("#label");

if (!empty($conf->use_javascript_ajax)) {
	print "\n".'<script type="text/javascript">';
	print '$(document).ready(function () {
        			function setdatefields()
	            	{
	            		if ($("#fullday:checked").val() == null) {
	            			$(".fulldaystarthour").removeAttr("disabled");
	            			$(".fulldaystartmin").removeAttr("disabled");
	            			$(".fulldayendhour").removeAttr("disabled");
	            			$(".fulldayendmin").removeAttr("disabled");
	            			$("#p2").removeAttr("disabled");
	            		} else {
							$(".fulldaystarthour").prop("disabled", true).val("00");
							$(".fulldaystartmin").prop("disabled", true).val("00");
							$(".fulldayendhour").prop("disabled", true).val("23");
							$(".fulldayendmin").prop("disabled", true).val("59");
							$("#p2").removeAttr("disabled");
	            		}
	            	}
                    $("#fullday").change(function() {
						console.log("setdatefields");
                        setdatefields();
                    });

                    $("#selectcomplete").change(function() {
						console.log("we change the complete status - set the doneby");
                        if ($("#selectcomplete").val() == 100) {
                            if ($("#doneby").val() <= 0) $("#doneby").val(\''.((int) $user->id).'\');
                        }
                        if ($("#selectcomplete").val() == 0) {
                            $("#doneby").val(-1);
                        }
                    });

                    $("#actioncode").change(function() {
                        if ($("#actioncode").val() == \'AC_RDV\') $("#dateend").addClass("fieldrequired");
                        else $("#dateend").removeClass("fieldrequired");
                    });
					$("#aphour,#apmin").change(function() {
						if ($("#actioncode").val() == \'AC_RDV\') {
							console.log("Start date was changed, we modify end date "+(parseInt($("#aphour").val()))+" "+$("#apmin").val()+" -> "+("00" + (parseInt($("#aphour").val()) + 1)).substr(-2,2));
							$("#p2hour").val(("00" + (parseInt($("#aphour").val()) + 1)).substr(-2,2));
							$("#p2min").val($("#apmin").val());
							$("#p2day").val($("#apday").val());
							$("#p2month").val($("#apmonth").val());
							$("#p2year").val($("#apyear").val());
							$("#p2").val($("#ap").val());
						}
					});
                    if ($("#actioncode").val() == \'AC_RDV\') $("#dateend").addClass("fieldrequired");
                    else $("#dateend").removeClass("fieldrequired");
                    setdatefields();
               })';
	print '</script>'."\n";
}
	print '<form name="formaction" action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="donotclearsession" value="1">';
	print '<input type="hidden" name="page_y" value="">';
if ($backtopage) {
	print '<input type="hidden" name="backtopage" value="'.($backtopage != '1' ? $backtopage : dol_htmlentities($_SERVER["HTTP_REFERER"])).'">';
}
if (empty($conf->global->AGENDA_USE_EVENT_TYPE)) {
	print '<input type="hidden" name="actioncode" value="'.dol_getIdFromCode($db, 'AC_OTH', 'c_actioncomm').'">';
}

if (GETPOST("actioncode", 'aZ09') == 'AC_RDV') {
	print load_fiche_titre($langs->trans("AddActionRendezVous"), '', 'title_agenda');
} else {
	print load_fiche_titre($langs->trans("AddAnAction"), '', 'title_agenda');
}

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	// Type of event
if (!empty($conf->global->AGENDA_USE_EVENT_TYPE)) {
	print '<tr><td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans("Type").'</span></b></td><td>';
	$default = (empty($conf->global->AGENDA_USE_EVENT_TYPE_DEFAULT) ? 'AC_RDV' : $conf->global->AGENDA_USE_EVENT_TYPE_DEFAULT);
	print img_picto($langs->trans("ActionType"), 'square', 'class="fawidth30 inline-block" style="color: #ddd;"');
	print $formactions->select_type_actions(GETPOSTISSET("actioncode") ? GETPOST("actioncode", 'aZ09') : ($object->type_code ? $object->type_code : $default), "actioncode", "systemauto", 0, -1, 0, 1);	// TODO Replace 0 with -2 in onlyautoornot
	print '</td></tr>';
}

	// Title
	print '<tr><td'.(empty($conf->global->AGENDA_USE_EVENT_TYPE) ? ' class="fieldrequired titlefieldcreate"' : '').'>'.$langs->trans("Label").'</td><td><input type="text" id="label" name="label" class="soixantepercent" value="'.GETPOST('label').'"></td></tr>';

	// Full day
	print '<tr><td><span class="fieldrequired">'.$langs->trans("Date").'</span></td><td class="valignmiddle height30 small"><input type="checkbox" id="fullday" name="fullday" '.(GETPOST('fullday') ? ' checked' : '').'><label for="fullday">'.$langs->trans("EventOnFullDay").'</label>';

	// Recurring event
	$userepeatevent = ($conf->global->MAIN_FEATURES_LEVEL == 2 ? 1 : 0);
if ($userepeatevent) {
	// Repeat
	//print '<tr><td></td><td colspan="3" class="opacitymedium">';
	print ' &nbsp; &nbsp; &nbsp; &nbsp; <div class="opacitymedium inline-block">';
	print img_picto($langs->trans("Recurrence"), 'recurring', 'class="paddingright2"');
	print '<input type="hidden" name="recurid" value="'.$object->recurid.'">';
	$selectedrecurrulefreq = 'no';
	$selectedrecurrulebymonthday = '';
	$selectedrecurrulebyday = '';
	if ($object->recurrule && preg_match('/FREQ=([A-Z]+)/i', $object->recurrule, $reg)) {
		$selectedrecurrulefreq = $reg[1];
	}
	if ($object->recurrule && preg_match('/FREQ=MONTHLY.*BYMONTHDAY=(\d+)/i', $object->recurrule, $reg)) {
		$selectedrecurrulebymonthday = $reg[1];
	}
	if ($object->recurrule && preg_match('/FREQ=WEEKLY.*BYDAY(\d+)/i', $object->recurrule, $reg)) {
		$selectedrecurrulebyday = $reg[1];
	}
	print $form->selectarray('recurrulefreq', $arrayrecurrulefreq, $selectedrecurrulefreq, 0, 0, 0, '', 0, 0, 0, '', 'marginrightonly');
	// If recurrulefreq is MONTHLY
	print '<div class="hidden marginrightonly inline-block repeateventBYMONTHDAY">';
	print $langs->trans("DayOfMonth").': <input type="input" size="2" name="BYMONTHDAY" value="'.$selectedrecurrulebymonthday.'">';
	print '</div>';
	// If recurrulefreq is WEEKLY
	print '<div class="hidden marginrightonly inline-block repeateventBYDAY">';
	print $langs->trans("DayOfWeek").': <input type="input" size="4" name="BYDAY" value="'.$selectedrecurrulebyday.'">';
	print '</div>';
	print '<script type="text/javascript">
				jQuery(document).ready(function() {
					function init_repeat()
					{
						if (jQuery("#recurrulefreq").val() == \'MONTHLY\')
						{
							jQuery(".repeateventBYMONTHDAY").css("display", "inline-block");		/* use this instead of show because we want inline-block and not block */
							jQuery(".repeateventBYDAY").hide();
						}
						else if (jQuery("#recurrulefreq").val() == \'WEEKLY\')
						{
							jQuery(".repeateventBYMONTHDAY").hide();
							jQuery(".repeateventBYDAY").css("display", "inline-block");		/* use this instead of show because we want inline-block and not block */
						}
						else
						{
							jQuery(".repeateventBYMONTHDAY").hide();
							jQuery(".repeateventBYDAY").hide();
						}
					}
					init_repeat();
					jQuery("#recurrulefreq").change(function() {
						init_repeat();
					});
				});
				</script>';
	print '</div>';
	//print '</td></tr>';
}

	print '</td></tr>';

	$datep = ($datep ? $datep : (is_null($object->datep) ? '' : $object->datep));
if (GETPOST('datep', 'int', 1)) {
	$datep = dol_stringtotime(GETPOST('datep', 'int', 1), 'tzuser');
}
	$datef = ($datef ? $datef : $object->datef);
if (GETPOST('datef', 'int', 1)) {
	$datef = dol_stringtotime(GETPOST('datef', 'int', 1), 'tzuser');
}
if (empty($datef) && !empty($datep)) {
	if (GETPOST("actioncode", 'aZ09') == 'AC_RDV' || empty($conf->global->AGENDA_USE_EVENT_TYPE_DEFAULT)) {
		$datef = dol_time_plus_duree($datep, (empty($conf->global->AGENDA_AUTOSET_END_DATE_WITH_DELTA_HOURS) ? 1 : $conf->global->AGENDA_AUTOSET_END_DATE_WITH_DELTA_HOURS), 'h');
	}
}

	// Date start
	print '<tr><td class="nowrap">';
	/*
	print '<span class="fieldrequired">'.$langs->trans("DateActionStart").'</span>';
	print ' - ';
	print '<span id="dateend"'.(GETPOST("actioncode", 'aZ09') == 'AC_RDV' ? ' class="fieldrequired"' : '').'>'.$langs->trans("DateActionEnd").'</span>';
	*/
	print '</td><td>';
if (GETPOST("afaire") == 1) {
	print $form->selectDate($datep, 'ap', 1, 1, 0, "action", 1, 2, 0, 'fulldaystart', '', '', '', 1, '', '', 'tzuserrel'); // Empty value not allowed for start date and hours if "todo"
} else {
	print $form->selectDate($datep, 'ap', 1, 1, 1, "action", 1, 2, 0, 'fulldaystart', '', '', '', 1, '', '', 'tzuserrel');
}
	print ' <span class="hideonsmartphone">&nbsp; &nbsp; - &nbsp; &nbsp;</span> ';
if (GETPOST("afaire") == 1) {
	print $form->selectDate($datef, 'p2', 1, 1, 1, "action", 1, 0, 0, 'fulldayend', '', '', '', 1, '', '', 'tzuserrel');
} else {
	print $form->selectDate($datef, 'p2', 1, 1, 1, "action", 1, 0, 0, 'fulldayend', '', '', '', 1, '', '', 'tzuserrel');
}
	print '</td></tr>';

	print '<tr><td class="">&nbsp;</td><td></td></tr>';

	// Assigned to
	print '<tr><td class="tdtop nowrap"><span class="fieldrequired">'.$langs->trans("ActionAffectedTo").'</span></td><td>';
	$listofuserid = array();
	$listofcontactid = array();
	$listofotherid = array();

if (empty($donotclearsession)) {
	$assignedtouser = GETPOST("assignedtouser") ?GETPOST("assignedtouser") : (!empty($object->userownerid) && $object->userownerid > 0 ? $object->userownerid : $user->id);
	if ($assignedtouser) {
		$listofuserid[$assignedtouser] = array('id'=>$assignedtouser, 'mandatory'=>0, 'transparency'=>$object->transparency); // Owner first
	}
	//$listofuserid[$user->id] = array('id'=>$user->id, 'mandatory'=>0, 'transparency'=>(GETPOSTISSET('transparency') ? GETPOST('transparency', 'alpha') : 1)); // 1 by default at first init
	$listofuserid[$assignedtouser]['transparency'] = (GETPOSTISSET('transparency') ? GETPOST('transparency', 'alpha') : 1); // 1 by default at first init
	$_SESSION['assignedtouser'] = json_encode($listofuserid);
} else {
	if (!empty($_SESSION['assignedtouser'])) {
		$listofuserid = json_decode($_SESSION['assignedtouser'], true);
	}
	$firstelem = reset($listofuserid);
	if (isset($listofuserid[$firstelem['id']])) {
		$listofuserid[$firstelem['id']]['transparency'] = (GETPOSTISSET('transparency') ? GETPOST('transparency', 'alpha') : 0); // 0 by default when refreshing
	}
}
	print '<div class="assignedtouser">';
	print $form->select_dolusers_forevent(($action == 'create' ? 'add' : 'update'), 'assignedtouser', 1, '', 0, '', '', 0, 0, 0, 'AND u.statut != 0', 1, $listofuserid, $listofcontactid, $listofotherid);
	print '</div>';
	print '</td></tr>';

	// Done by
if (!empty($conf->global->AGENDA_ENABLE_DONEBY)) {
	print '<tr><td class="nowrap">'.$langs->trans("ActionDoneBy").'</td><td>';
	print $form->select_dolusers(GETPOSTISSET("doneby") ? GETPOST("doneby", 'int') : (!empty($object->userdoneid) && $percent == 100 ? $object->userdoneid : 0), 'doneby', 1);
	print '</td></tr>';
}

	// Location
if (empty($conf->global->AGENDA_DISABLE_LOCATION)) {
	print '<tr><td>'.$langs->trans("Location").'</td><td><input type="text" name="location" class="minwidth300 maxwidth150onsmartphone" value="'.(GETPOST('location') ? GETPOST('location') : $object->location).'"></td></tr>';
}

	// Status
	print '<tr><td>'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td>';
	print '<td>';
	$percent = $complete !=='' ? $complete : -1;
if (GETPOSTISSET('status')) {
	$percent = GETPOST('status');
} elseif (GETPOSTISSET('percentage')) {
	$percent = GETPOST('percentage', 'int');
} else {
	if ($complete == '0' || GETPOST("afaire") == 1) {
		$percent = '0';
	} elseif ($complete == 100 || GETPOST("afaire") == 2) {
		$percent = 100;
	}
}
	$formactions->form_select_status_action('formaction', $percent, 1, 'complete', 0, 0, 'maxwidth200');
	print '</td></tr>';

if (!empty($conf->categorie->enabled)) {
	// Categories
	print '<tr><td>'.$langs->trans("Categories").'</td><td>';
	$cate_arbo = $form->select_all_categories(Categorie::TYPE_ACTIONCOMM, '', 'parent', 64, 0, 1);
	print img_picto('', 'category').$form->multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, 'minwidth300 quatrevingtpercent widthcentpercentminusx', 0, 0);
	print "</td></tr>";
}

	print '</table>';


	print '<br><hr><br>';


	print '<table class="border centpercent">';

if (!empty($conf->societe->enabled)) {
	// Related company
	print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
	if (GETPOST('socid', 'int') > 0) {
		$societe = new Societe($db);
		$societe->fetch(GETPOST('socid', 'int'));
		print $societe->getNomUrl(1);
		print '<input type="hidden" id="socid" name="socid" value="'.GETPOST('socid', 'int').'">';
	} else {
		$events = array();
		$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1', 1), 'htmlname' => 'contactid', 'params' => array('add-customer-contact' => 'disabled'));
		//For external user force the company to user company
		if (!empty($user->socid)) {
			print img_picto('', 'company', 'class="paddingrightonly"').$form->select_company($user->socid, 'socid', '', 1, 1, 0, $events, 0, 'minwidth300');
		} else {
			print img_picto('', 'company', 'class="paddingrightonly"').$form->select_company('', 'socid', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
		}
	}
	print '</td></tr>';

	// Related contact
	print '<tr><td class="nowrap">'.$langs->trans("ActionOnContact").'</td><td>';
	$preselectedids = GETPOST('socpeopleassigned', 'array');
	if (GETPOST('contactid', 'int')) {
		$preselectedids[GETPOST('contactid', 'int')] = GETPOST('contactid', 'int');
	}
	if ($origin=='contact') $preselectedids[GETPOST('originid', 'int')] = GETPOST('originid', 'int');
	print img_picto('', 'contact', 'class="paddingrightonly"');
	print $form->selectcontacts(GETPOST('socid', 'int'), $preselectedids, 'socpeopleassigned[]', 1, '', '', 0, 'minwidth300 quatrevingtpercent', false, 0, array(), false, 'multiple', 'contactid');
	print '</td></tr>';
}

	// Project
if (!empty($conf->project->enabled)) {
	$langs->load("projects");

	$projectid = GETPOST('projectid', 'int');

	print '<tr><td class="titlefieldcreate">'.$langs->trans("Project").'</td><td id="project-input-container">';
	print img_picto('', 'project', 'class="pictofixedwidth"');
	print $formproject->select_projects(($object->socid > 0 ? $object->socid : -1), $projectid, 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'maxwidth500 widthcentpercentminusxx');

	print '&nbsp;<a href="'.DOL_URL_ROOT.'/projet/card.php?socid='.(empty($societe->id) ? '' : $societe->id).'&action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'">';
	print '<span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddProject").'"></span></a>';
	$urloption = '?action=create&donotclearsession=1';
	$url = dol_buildpath('comm/action/card.php', 2).$urloption;

	// update task list
	print "\n".'<script type="text/javascript">';
	print '$(document).ready(function () {
	               $("#projectid").change(function () {
                        var url = "'.DOL_URL_ROOT.'/projet/ajax/projects.php?mode=gettasks&socid="+$("#projectid").val()+"&projectid="+$("#projectid").val();
						console.log("Call url to get new list of tasks: "+url);
                        $.get(url, function(data) {
                            console.log(data);
                            if (data) $("#taskid").html(data).select2();
                        })
                  });
               })';
	print '</script>'."\n";

	print '</td></tr>';

	print '<tr><td class="titlefieldcreate">'.$langs->trans("Task").'</td><td id="project-task-input-container" >';
	print img_picto('', 'projecttask', 'class="paddingrightonly"');
	$projectsListId = false;
	if (!empty($projectid)) {
		$projectsListId = $projectid;
	}

	$tid = GETPOSTISSET("projecttaskid") ? GETPOST("projecttaskid", 'int') : (GETPOSTISSET("taskid") ? GETPOST("taskid", 'int') : '');

	$formproject->selectTasks((!empty($societe->id) ? $societe->id : -1), $tid, 'taskid', 24, 0, '1', 1, 0, 0, 'maxwidth500', $projectsListId);
	print '</td></tr>';
}

	// Object linked
if (!empty($origin) && !empty($originid)) {
	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

	$hasPermissionOnLinkedObject = 0;
	if ($user->hasRight($origin, 'read')) {
		$hasPermissionOnLinkedObject = 1;
	}
	//var_dump('origin='.$origin.' originid='.$originid.' hasPermissionOnLinkedObject='.$hasPermissionOnLinkedObject);

	if (! in_array($origin, array('societe', 'project', 'task', 'user'))) {
		// We do not use link for object that already contains a hard coded field to make links with agenda events
		print '<tr><td class="titlefieldcreate">'.$langs->trans("LinkedObject").'</td>';
		print '<td colspan="3">';
		if ($hasPermissionOnLinkedObject) {
			print dolGetElementUrl($originid, $origin, 1);
			print '<input type="hidden" name="fk_element" value="'.$originid.'">';
			print '<input type="hidden" name="elementtype" value="'.$origin.'">';
			print '<input type="hidden" name="originid" value="'.$originid.'">';
			print '<input type="hidden" name="origin" value="'.$origin.'">';
		} else {
			print '<!-- no permission on object to link '.$origin.' id '.$originid.' -->';
		}
		print '</td></tr>';
	}
}

	$reg = array();
if (GETPOST("datep") && preg_match('/^([0-9][0-9][0-9][0-9])([0-9][0-9])([0-9][0-9])$/', GETPOST("datep"), $reg)) {
	$object->datep = dol_mktime(0, 0, 0, $reg[2], $reg[3], $reg[1]);
}

	// Priority
if (!empty($conf->global->AGENDA_SUPPORT_PRIORITY_IN_EVENTS)) {
	print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("Priority").'</td><td colspan="3">';
	print '<input type="text" name="priority" value="'.(GETPOSTISSET('priority') ? GETPOST('priority', 'int') : ($object->priority ? $object->priority : '')).'" size="5">';
	print '</td></tr>';
}

	// Description
	print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('note', (GETPOSTISSET('note') ? GETPOST('note', 'restricthtml') : $object->note_private), '', 120, 'dolibarr_notes', 'In', true, true, $conf->fckeditor->enabled, ROWS_4, '90%');
	$doleditor->Create();
	print '</td></tr>';

	// Other attributes
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
if (empty($reshook)) {
	print $object->showOptionals($extrafields, 'create', $parameters);
}

	print '</table>';


if (getDolGlobalString('AGENDA_REMINDER_EMAIL') || getDolGlobalString('AGENDA_REMINDER_BROWSER')) {
	//checkbox create reminder
	print '<hr>';
	print '<br>';
	print '<label for="addreminder">'.img_picto('', 'bell', 'class="pictofixedwidth"').$langs->trans("AddReminder").'</label> <input type="checkbox" id="addreminder" name="addreminder"><br><br>';

	print '<div class="reminderparameters" style="display: none;">';

	//print '<hr>';
	//print load_fiche_titre($langs->trans("AddReminder"), '', '');

	print '<table class="border centpercent">';

	//Reminder
	print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("ReminderTime").'</td><td colspan="3">';
	print '<input class="width50" type="number" name="offsetvalue" value="'.(GETPOSTISSET('offsetvalue') ? GETPOST('offsetvalue', 'int') : '15').'"> ';
	print $form->selectTypeDuration('offsetunit', 'i', array('y', 'm'));
	print '</td></tr>';

	//Reminder Type
	print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("ReminderType").'</td><td colspan="3">';
	print $form->selectarray('selectremindertype', $TRemindTypes, '', 0, 0, 0, '', 0, 0, 0, '', 'minwidth200 maxwidth500', 1);
	print '</td></tr>';

	//Mail Model
	if (getDolGlobalString('AGENDA_REMINDER_EMAIL')) {
		print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("EMailTemplates").'</td><td colspan="3">';
		print $form->selectModelMail('actioncommsend', 'actioncomm_send', 1, 1);
		print '</td></tr>';
	}

	print '</table>';
	print '</div>';

	print "\n".'<script type="text/javascript">';
	print '$(document).ready(function () {
	            		$("#addreminder").click(function(){
							console.log("Click on addreminder");
	            		    if (this.checked) {
	            		    	$(".reminderparameters").show();
                            } else {
                            	$(".reminderparameters").hide();
                            }
							$("#selectremindertype").select2("destroy");
							$("#selectremindertype").select2();
							$("#select_offsetunittype_duration").select2("destroy");
							$("#select_offsetunittype_duration").select2();
	            		 });

	            		$("#selectremindertype").change(function(){
							console.log("Change on selectremindertype");
	            	        var selected_option = $("#selectremindertype option:selected").val();
	            		    if(selected_option == "email") {
	            		        $("#select_actioncommsendmodel_mail").closest("tr").show();
	            		    } else {
	            			    $("#select_actioncommsendmodel_mail").closest("tr").hide();
	            		    };
	            		});
                   })';
	print '</script>'."\n";
}

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Add");

	print "</form>";


llxFooterVierge();

$db->close();
