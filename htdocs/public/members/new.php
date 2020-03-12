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

if (!defined('NOLOGIN'))		define("NOLOGIN", 1); // This means this output page does not require to be logged.
if (!defined('NOCSRFCHECK'))	define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.
if (!defined('NOIPCHECK'))		define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// TODO This should be useless. Because entity must be retrieve from object ref and not from url.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) define("DOLENTITY", $entity);

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

// Init vars
$errmsg = '';
$num = 0;
$error = 0;
$backtopage = GETPOST('backtopage', 'alpha');
$action = GETPOST('action', 'alpha');

// Load translation files
$langs->loadLangs(array("main", "members", "companies", "install", "other"));

// Security check
if (empty($conf->adherent->enabled)) accessforbidden('', 0, 0, 1);

if (empty($conf->global->MEMBER_ENABLE_PUBLIC))
{
    print $langs->trans("Auto subscription form for public visitors has not been enabled");
    exit;
}

$extrafields = new ExtraFields($db);

$object = new Adherent($db);

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
    $width = 0;
    $urllogo = DOL_URL_ROOT.'/theme/common/login_logo.png';

    if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small))
    {
        $urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_small);
        $width = 150;
    }
    elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo))
    {
        $urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/'.$mysoc->logo);
        $width = 150;
    }
    elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png'))
    {
        $urllogo = DOL_URL_ROOT.'/theme/dolibarr_logo.png';
        $width = 150;
    }

	print '<div class="center">';
    // Output html code for logo
    if ($urllogo)
    {
    	print '<div class="backgreypublicpayment">';
    	print '<div class="logopublicpayment">';
    	print '<img id="dolpaymentlogo" src="'.$urllogo.'"';
    	if ($width) print ' width="'.$width.'"';
    	print '>';
    	print '</div>';
    	if (empty($conf->global->MAIN_HIDE_POWERED_BY)) {
    		print '<div class="poweredbypublicpayment opacitymedium right"><a href="https://www.dolibarr.org" target="dolibarr">'.$langs->trans("PoweredBy").'<br><img src="'.DOL_URL_ROOT.'/theme/dolibarr_logo.png" width="80px"></a></div>';
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

// Action called when page is submitted
if ($action == 'add')
{
	$error = 0;
	$urlback = '';

	$db->begin();

    // test if login already exists
    if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
    {
        if (!GETPOST('login'))
        {
            $error++;
            $errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Login"))."<br>\n";
        }
        $sql = "SELECT login FROM ".MAIN_DB_PREFIX."adherent WHERE login='".$db->escape(GETPOST('login'))."'";
        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
        }
        if ($num != 0)
        {
            $error++;
            $langs->load("errors");
            $errmsg .= $langs->trans("ErrorLoginAlreadyExists")."<br>\n";
        }
        if (!isset($_POST["pass1"]) || !isset($_POST["pass2"]) || $_POST["pass1"] == '' || $_POST["pass2"] == '' || $_POST["pass1"] != $_POST["pass2"])
        {
            $error++;
            $langs->load("errors");
            $errmsg .= $langs->trans("ErrorPasswordsMustMatch")."<br>\n";
        }
        if (!GETPOST("email"))
        {
            $error++;
            $errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("EMail"))."<br>\n";
        }
    }
    if (GETPOST('type') <= 0)
    {
        $error++;
        $errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type"))."<br>\n";
    }
    if (!in_array(GETPOST('morphy'), array('mor', 'phy')))
    {
        $error++;
        $errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('Nature'))."<br>\n";
    }
    if (empty($_POST["lastname"]))
    {
        $error++;
        $errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Lastname"))."<br>\n";
    }
    if (empty($_POST["firstname"]))
    {
        $error++;
        $errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Firstname"))."<br>\n";
    }
    if (GETPOST("email") && !isValidEmail(GETPOST("email")))
    {
        $error++;
        $langs->load("errors");
        $errmsg .= $langs->trans("ErrorBadEMail", GETPOST("email"))."<br>\n";
    }
    $birthday = dol_mktime($_POST["birthhour"], $_POST["birthmin"], $_POST["birthsec"], $_POST["birthmonth"], $_POST["birthday"], $_POST["birthyear"]);
    if ($_POST["birthmonth"] && empty($birthday))
    {
        $error++;
        $langs->load("errors");
        $errmsg .= $langs->trans("ErrorBadDateFormat")."<br>\n";
    }
    if (!empty($conf->global->MEMBER_NEWFORM_DOLIBARRTURNOVER))
    {
        if (GETPOST("morphy") == 'mor' && GETPOST('budget') <= 0)
        {
            $error++;
            $errmsg .= $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TurnoverOrBudget"))."<br>\n";
        }
    }

    if (isset($public)) $public = 1;
    else $public = 0;

    if (!$error)
    {
        // email a peu pres correct et le login n'existe pas
        $adh = new Adherent($db);
        $adh->statut      = -1;
        $adh->public      = $public;
        $adh->firstname   = $_POST["firstname"];
        $adh->lastname    = $_POST["lastname"];
        $adh->gender      = $_POST["gender"];
        $adh->civility_id = $_POST["civility_id"];
        $adh->societe     = $_POST["societe"];
        $adh->address     = $_POST["address"];
        $adh->zip         = $_POST["zipcode"];
        $adh->town        = $_POST["town"];
        $adh->email       = $_POST["email"];
        if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
        {
            $adh->login       = $_POST["login"];
            $adh->pass        = $_POST["pass1"];
        }
        $adh->photo       = $_POST["photo"];
        $adh->country_id  = $_POST["country_id"];
        $adh->state_id    = $_POST["state_id"];
        $adh->typeid      = $_POST["type"];
        $adh->note_private = $_POST["note_private"];
        $adh->morphy      = $_POST["morphy"];
        $adh->birth       = $birthday;


        // Fill array 'array_options' with data from add form
        $extrafields->fetch_name_optionals_label($adh->table_element);
        $ret = $extrafields->setOptionalsFromPost(null, $adh);
		if ($ret < 0) $error++;

        $result = $adh->create($user);
        if ($result > 0)
        {
			require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
            $object = $adh;

            $adht = new AdherentType($db);
            $adht->fetch($object->typeid);

            if ($object->email)
            {
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
            	$labeltouse = $conf->global->ADHERENT_EMAIL_TEMPLATE_AUTOREGISTER;

            	if (!empty($labeltouse)) $arraydefaultmessage = $formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);

            	if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0)
            	{
            		$subject = $arraydefaultmessage->topic;
            		$msg     = $arraydefaultmessage->content;
            	}

            	$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
            	complete_substitutions_array($substitutionarray, $outputlangs, $object);
            	$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
            	$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnValid()), $substitutionarray, $outputlangs);

            	if ($subjecttosend && $texttosend)
            	{
            		$moreinheader = 'X-Dolibarr-Info: send_an_email by public/members/new.php'."\r\n";

            		$result = $object->send_an_email($texttosend, $subjecttosend, array(), array(), array(), "", "", 0, -1, '', $moreinheader);
            	}
            	/*if ($result < 0)
            	{
            		$error++;
            		setEventMessages($object->error, $object->errors, 'errors');
            	}*/
            }

            // Send email to the foundation to say a new member subscribed with autosubscribe form
            if (!empty($conf->global->MAIN_INFO_SOCIETE_MAIL) && !empty($conf->global->ADHERENT_AUTOREGISTER_NOTIF_MAIL_SUBJECT) &&
                  !empty($conf->global->ADHERENT_AUTOREGISTER_NOTIF_MAIL))
            {
            	// Define link to login card
            	$appli = constant('DOL_APPLICATION_TITLE');
            	if (!empty($conf->global->MAIN_APPLICATION_TITLE))
            	{
            		$appli = $conf->global->MAIN_APPLICATION_TITLE;
            		if (preg_match('/\d\.\d/', $appli))
            		{
            			if (!preg_match('/'.preg_quote(DOL_VERSION).'/', $appli)) $appli .= " (".DOL_VERSION.")"; // If new title contains a version that is different than core
            		}
            		else $appli .= " ".DOL_VERSION;
            	}
            	else $appli .= " ".DOL_VERSION;

            	$to = $adh->makeSubstitution($conf->global->MAIN_INFO_SOCIETE_MAIL);
            	$from = $conf->global->ADHERENT_MAIL_FROM;
				$mailfile = new CMailFile(
					'['.$appli.'] '.$conf->global->ADHERENT_AUTOREGISTER_NOTIF_MAIL_SUBJECT,
					$to,
					$from,
					$adh->makeSubstitution($conf->global->ADHERENT_AUTOREGISTER_NOTIF_MAIL),
					array(),
					array(),
					array(),
					"",
					"",
					0,
					-1
				);

            	if (!$mailfile->sendfile())
            	{
            		dol_syslog($langs->trans("ErrorFailedToSendMail", $from, $to), LOG_ERR);
            	}
            }

            if (!empty($backtopage)) $urlback = $backtopage;
            elseif (!empty($conf->global->MEMBER_URL_REDIRECT_SUBSCRIPTION))
            {
                $urlback = $conf->global->MEMBER_URL_REDIRECT_SUBSCRIPTION;
                // TODO Make replacement of __AMOUNT__, etc...
            }
            else $urlback = $_SERVER["PHP_SELF"]."?action=added";

            if (!empty($conf->global->MEMBER_NEWFORM_PAYONLINE) && $conf->global->MEMBER_NEWFORM_PAYONLINE != '-1')
            {
                if ($conf->global->MEMBER_NEWFORM_PAYONLINE == 'all')
                {
                    $urlback = DOL_MAIN_URL_ROOT.'/public/payment/newpayment.php?from=membernewform&source=membersubscription&ref='.urlencode($adh->ref);
                    if (price2num(GETPOST('amount', 'alpha'))) $urlback .= '&amount='.price2num(GETPOST('amount', 'alpha'));
                    if (GETPOST('email')) $urlback .= '&email='.urlencode(GETPOST('email'));
                    if (!empty($conf->global->PAYMENT_SECURITY_TOKEN))
                    {
                        if (!empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE))
                        {
                    	    $urlback .= '&securekey='.urlencode(dol_hash($conf->global->PAYMENT_SECURITY_TOKEN.'membersubscription'.$adh->ref, 2));
                        }
                        else
                        {
                            $urlback .= '&securekey='.urlencode($conf->global->PAYMENT_SECURITY_TOKEN);
                        }
                    }
                }
            	elseif ($conf->global->MEMBER_NEWFORM_PAYONLINE == 'paybox')
                {
                    $urlback = DOL_MAIN_URL_ROOT.'/public/paybox/newpayment.php?from=membernewform&source=membersubscription&ref='.urlencode($adh->ref);
                    if (price2num(GETPOST('amount', 'alpha'))) $urlback .= '&amount='.price2num(GETPOST('amount', 'alpha'));
                    if (GETPOST('email')) $urlback .= '&email='.urlencode(GETPOST('email'));
                    if (!empty($conf->global->PAYMENT_SECURITY_TOKEN))
                    {
                    	if (!empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE))
                    	{
                    		$urlback .= '&securekey='.urlencode(dol_hash($conf->global->PAYMENT_SECURITY_TOKEN.'membersubscription'.$adh->ref, 2));
                    	}
                    	else
                    	{
                    		$urlback .= '&securekey='.urlencode($conf->global->PAYMENT_SECURITY_TOKEN);
                    	}
                    }
                }
                elseif ($conf->global->MEMBER_NEWFORM_PAYONLINE == 'paypal')
                {
                    $urlback = DOL_MAIN_URL_ROOT.'/public/paypal/newpayment.php?from=membernewform&source=membersubscription&ref='.urlencode($adh->ref);
                    if (price2num(GETPOST('amount', 'alpha'))) $urlback .= '&amount='.price2num(GETPOST('amount', 'alpha'));
                    if (GETPOST('email')) $urlback .= '&email='.urlencode(GETPOST('email'));
                    if (!empty($conf->global->PAYMENT_SECURITY_TOKEN))
                    {
                    	if (!empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE))
                        {
                        	$urlback .= '&securekey='.urlencode(dol_hash($conf->global->PAYMENT_SECURITY_TOKEN.'membersubscription'.$adh->ref, 2));
                        }
                        else
                        {
                        	$urlback .= '&securekey='.urlencode($conf->global->PAYMENT_SECURITY_TOKEN);
                        }
                    }
                }
				elseif ($conf->global->MEMBER_NEWFORM_PAYONLINE == 'stripe')
                {
                    $urlback = DOL_MAIN_URL_ROOT.'/public/stripe/newpayment.php?from=membernewform&source=membersubscription&ref='.$adh->ref;
                    if (price2num(GETPOST('amount', 'alpha'))) $urlback .= '&amount='.price2num(GETPOST('amount', 'alpha'));
                    if (GETPOST('email')) $urlback .= '&email='.urlencode(GETPOST('email'));
                    if (!empty($conf->global->PAYMENT_SECURITY_TOKEN))
                    {
                    	if (!empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE))
                        {
                        	$urlback .= '&securekey='.urlencode(dol_hash($conf->global->PAYMENT_SECURITY_TOKEN.'membersubscription'.$adh->ref, 2));
                        }
                        else
                        {
                        	$urlback .= '&securekey='.urlencode($conf->global->PAYMENT_SECURITY_TOKEN);
                        }
                    }
                }
                else
                {
                    dol_print_error('', "Autosubscribe form is setup to ask an online payment for a not managed online payment");
                    exit;
                }
            }

            if (!empty($entity)) $urlback .= '&entity='.$entity;
            dol_syslog("member ".$adh->ref." was created, we redirect to ".$urlback);
        }
        else
        {
        	$error++;
            $errmsg .= join('<br>', $adh->errors);
        }
    }

    if (!$error)
    {
    	$db->commit();

    	Header("Location: ".$urlback);
    	exit;
    }
    else
    {
    	$db->rollback();
    }
}

// Action called after a submitted was send and member created successfully
// If MEMBER_URL_REDIRECT_SUBSCRIPTION is set to url we never go here because a redirect was done to this url.
// backtopage parameter with an url was set on member submit page, we never go here because a redirect was done to this url.
if ($action == 'added')
{
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
$adht = new AdherentType($db);
$extrafields->fetch_name_optionals_label('adherent'); // fetch optionals attributes and labels


llxHeaderVierge($langs->trans("NewSubscription"));


print load_fiche_titre($langs->trans("NewSubscription"), '', '', 0, 0, 'center');


print '<div align="center">';
print '<div id="divsubscribe">';

print '<div class="center subscriptionformhelptext justify">';
if (!empty($conf->global->MEMBER_NEWFORM_TEXT)) print $langs->trans($conf->global->MEMBER_NEWFORM_TEXT)."<br>\n";
else print $langs->trans("NewSubscriptionDesc", $conf->global->MAIN_INFO_SOCIETE_MAIL)."<br>\n";
print '</div>';

dol_htmloutput_errors($errmsg);

// Print form
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="newmember">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'" / >';
print '<input type="hidden" name="entity" value="'.$entity.'" />';
print '<input type="hidden" name="action" value="add" />';

print '<br>';

print '<br><span class="opacitymedium">'.$langs->trans("FieldsWithAreMandatory", '*').'</span><br>';
//print $langs->trans("FieldsWithIsForPublic",'**').'<br>';

dol_fiche_head('');

print '<script type="text/javascript">
jQuery(document).ready(function () {
    jQuery(document).ready(function () {
        function initmorphy()
        {
                if (jQuery("#morphy").val()==\'phy\') {
                    jQuery("#trcompany").hide();
                }
                if (jQuery("#morphy").val()==\'mor\') {
                    jQuery("#trcompany").show();
                }
        };
        initmorphy();
        jQuery("#morphy").click(function() {
            initmorphy();
        });
        jQuery("#selectcountry_id").change(function() {
           document.newmember.action.value="create";
           document.newmember.submit();
        });
    });
});
</script>';


print '<table class="border" summary="form to subscribe" id="tablesubscribe">'."\n";

// Type
if (empty($conf->global->MEMBER_NEWFORM_FORCETYPE))
{
    $listoftype = $adht->liste_array();
    $tmp = array_keys($listoftype);
    $defaulttype = '';
    $isempty = 1;
    if (count($listoftype) == 1) { $defaulttype = $tmp[0]; $isempty = 0; }
    print '<tr><td class="titlefield">'.$langs->trans("Type").' <FONT COLOR="red">*</FONT></td><td>';
    print $form->selectarray("type", $adht->liste_array(), GETPOST('type') ?GETPOST('type') : $defaulttype, $isempty);
    print '</td></tr>'."\n";
}
else
{
    $adht->fetch($conf->global->MEMBER_NEWFORM_FORCETYPE);
    print '<input type="hidden" id="type" name="type" value="'.$conf->global->MEMBER_NEWFORM_FORCETYPE.'">';
}
// Moral/Physic attribute
$morphys["phy"] = $langs->trans("Physical");
$morphys["mor"] = $langs->trans("Moral");
if (empty($conf->global->MEMBER_NEWFORM_FORCEMORPHY))
{
    print '<tr class="morphy"><td class="titlefield">'.$langs->trans('MemberNature').' <FONT COLOR="red">*</FONT></td><td>'."\n";
    print $form->selectarray("morphy", $morphys, GETPOST('morphy'), 1);
    print '</td></tr>'."\n";
}
else
{
    print $morphys[$conf->global->MEMBER_NEWFORM_FORCEMORPHY];
    print '<input type="hidden" id="morphy" name="morphy" value="'.$conf->global->MEMBER_NEWFORM_FORCEMORPHY.'">';
}
// Civility
print '<tr><td class="titlefield">'.$langs->trans('UserTitle').'</td><td>';
print $formcompany->select_civility(GETPOST('civility_id'), 'civility_id').'</td></tr>'."\n";
// Lastname
print '<tr><td>'.$langs->trans("Lastname").' <FONT COLOR="red">*</FONT></td><td><input type="text" name="lastname" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('lastname')).'"></td></tr>'."\n";
// Firstname
print '<tr><td>'.$langs->trans("Firstname").' <FONT COLOR="red">*</FONT></td><td><input type="text" name="firstname" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('firstname')).'"></td></tr>'."\n";
// Gender
print '<tr><td>'.$langs->trans("Gender").'</td>';
print '<td>';
$arraygender = array('man'=>$langs->trans("Genderman"), 'woman'=>$langs->trans("Genderwoman"));
print $form->selectarray('gender', $arraygender, GETPOST('gender') ?GETPOST('gender') : $object->gender, 1);
print '</td></tr>';
// Company
print '<tr id="trcompany" class="trcompany"><td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('societe')).'"></td></tr>'."\n";
// Address
print '<tr><td>'.$langs->trans("Address").'</td><td>'."\n";
print '<textarea name="address" id="address" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.dol_escape_htmltag(GETPOST('address', 'none'), 0, 1).'</textarea></td></tr>'."\n";
// Zip / Town
print '<tr><td>'.$langs->trans('Zip').' / '.$langs->trans('Town').'</td><td>';
print $formcompany->select_ziptown(GETPOST('zipcode'), 'zipcode', array('town', 'selectcountry_id', 'state_id'), 6, 1);
print ' / ';
print $formcompany->select_ziptown(GETPOST('town'), 'town', array('zipcode', 'selectcountry_id', 'state_id'), 0, 1);
print '</td></tr>';
// Country
print '<tr><td>'.$langs->trans('Country').'</td><td>';
$country_id = GETPOST('country_id');
if (!$country_id && !empty($conf->global->MEMBER_NEWFORM_FORCECOUNTRYCODE)) $country_id = getCountry($conf->global->MEMBER_NEWFORM_FORCECOUNTRYCODE, 2, $db, $langs);
if (!$country_id && !empty($conf->geoipmaxmind->enabled))
{
    $country_code = dol_user_country();
    //print $country_code;
    if ($country_code)
    {
        $new_country_id = getCountry($country_code, 3, $db, $langs);
        //print 'xxx'.$country_code.' - '.$new_country_id;
        if ($new_country_id) $country_id = $new_country_id;
    }
}
$country_code = getCountry($country_id, 2, $db, $langs);
print $form->select_country($country_id, 'country_id');
print '</td></tr>';
// State
if (empty($conf->global->SOCIETE_DISABLE_STATE))
{
    print '<tr><td>'.$langs->trans('State').'</td><td>';
    if ($country_code) print $formcompany->select_state(GETPOST("state_id"), $country_code);
    else print '';
    print '</td></tr>';
}
// EMail
print '<tr><td>'.$langs->trans("Email").' <FONT COLOR="red">*</FONT></td><td><input type="text" name="email" maxlength="255" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('email')).'"></td></tr>'."\n";
// Login
if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
{
    print '<tr><td>'.$langs->trans("Login").' <FONT COLOR="red">*</FONT></td><td><input type="text" name="login" maxlength="50" class="minwidth100"value="'.dol_escape_htmltag(GETPOST('login')).'"></td></tr>'."\n";
    print '<tr><td>'.$langs->trans("Password").' <FONT COLOR="red">*</FONT></td><td><input type="password" maxlength="128" name="pass1" class="minwidth100" value="'.GETPOST("pass1").'"></td></tr>'."\n";
    print '<tr><td>'.$langs->trans("PasswordAgain").' <FONT COLOR="red">*</FONT></td><td><input type="password" maxlength="128" name="pass2" class="minwidth100" value="'.GETPOST("pass2").'"></td></tr>'."\n";
}
// Birthday
print '<tr id="trbirth" class="trbirth"><td>'.$langs->trans("DateToBirth").'</td><td>';
print $form->selectDate($birthday, 'birth', 0, 0, 1, "newmember", 1, 0);
print '</td></tr>'."\n";
// Photo
print '<tr><td>'.$langs->trans("URLPhoto").'</td><td><input type="text" name="photo" class="minwidth150" value="'.dol_escape_htmltag(GETPOST('photo')).'"></td></tr>'."\n";
// Public
print '<tr><td>'.$langs->trans("Public").'</td><td><input type="checkbox" name="public"></td></tr>'."\n";
// Other attributes
$tpl_context = 'public'; //BUG #11554 : define templae context to public
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';
// Comments
print '<tr>';
print '<td class="tdtop">'.$langs->trans("Comments").'</td>';
print '<td class="tdtop"><textarea name="note_private" id="note_private" wrap="soft" class="quatrevingtpercent" rows="'.ROWS_3.'">'.dol_escape_htmltag(GETPOST('note_private', 'none'), 0, 1).'</textarea></td>';
print '</tr>'."\n";

// Add specific fields used by Dolibarr foundation for example
if (!empty($conf->global->MEMBER_NEWFORM_DOLIBARRTURNOVER))
{
    $arraybudget = array('50'=>'<= 100 000', '100'=>'<= 200 000', '200'=>'<= 500 000', '300'=>'<= 1 500 000', '600'=>'<= 3 000 000', '1000'=>'<= 5 000 000', '2000'=>'5 000 000+');
    print '<tr id="trbudget" class="trcompany"><td>'.$langs->trans("TurnoverOrBudget").' <FONT COLOR="red">*</FONT></td><td>';
    print $form->selectarray('budget', $arraybudget, GETPOST('budget'), 1);
    print ' € or $';

    print '<script type="text/javascript">
    jQuery(document).ready(function () {
        initturnover();
        jQuery("#morphy").click(function() {
            initturnover();
        });
        jQuery("#budget").change(function() {
                if (jQuery("#budget").val() > 0) { jQuery(".amount").val(jQuery("#budget").val()); }
                else { jQuery("#budget").val(\'\'); }
        });
        /*jQuery("#type").change(function() {
            if (jQuery("#type").val()==1) { jQuery("#morphy").val(\'mor\'); }
            if (jQuery("#type").val()==2) { jQuery("#morphy").val(\'phy\'); }
            if (jQuery("#type").val()==3) { jQuery("#morphy").val(\'mor\'); }
            if (jQuery("#type").val()==4) { jQuery("#morphy").val(\'mor\'); }
            initturnover();
        });*/
        function initturnover() {
            if (jQuery("#morphy").val()==\'phy\') {
                jQuery(".amount").val(20);
                jQuery("#trbudget").hide();
                jQuery("#trcompany").hide();
            }
            if (jQuery("#morphy").val()==\'mor\') {
                jQuery(".amount").val(\'\');
                jQuery("#trcompany").show();
                jQuery("#trbirth").hide();
                jQuery("#trbudget").show();
                if (jQuery("#budget").val() > 0) { jQuery(".amount").val(jQuery("#budget").val()); }
                else { jQuery("#budget").val(\'\'); }
            }
        }
    });
    </script>';
    print '</td></tr>'."\n";
}
if (!empty($conf->global->MEMBER_NEWFORM_AMOUNT)
|| !empty($conf->global->MEMBER_NEWFORM_PAYONLINE))
{
    // $conf->global->MEMBER_NEWFORM_SHOWAMOUNT is an amount
    $amount = 0;
    if (!empty($conf->global->MEMBER_NEWFORM_AMOUNT)) {
        $amount = $conf->global->MEMBER_NEWFORM_AMOUNT;
    }

    if (!empty($conf->global->MEMBER_NEWFORM_PAYONLINE))
    {
        $amount = GETPOST('amount') ?GETPOST('amount') : $conf->global->MEMBER_NEWFORM_AMOUNT;
    }
    // $conf->global->MEMBER_NEWFORM_PAYONLINE is 'paypal', 'paybox' or 'stripe'
    print '<tr><td>'.$langs->trans("Subscription").'</td><td class="nowrap">';
    if (!empty($conf->global->MEMBER_NEWFORM_EDITAMOUNT))
    {
        print '<input type="text" name="amount" id="amount" class="flat amount" size="6" value="'.$amount.'">';
    }
    else
    {
        print '<input type="text" name="amount" id="amounthidden" class="flat amount" disabled size="6" value="'.$amount.'">';
        print '<input type="hidden" name="amount" id="amount" class="flat amount" size="6" value="'.$amount.'">';
    }
    print ' '.$langs->trans("Currency".$conf->currency);
    print '</td></tr>';
}
print "</table>\n";

dol_fiche_end();

// Save
print '<div class="center">';
print '<input type="submit" value="'.$langs->trans("Submit").'" id="submitsave" class="button">';
if (!empty($backtopage))
{
    print ' &nbsp; &nbsp; <input type="submit" value="'.$langs->trans("Cancel").'" id="submitcancel" class="button">';
}
print '</div>';


print "</form>\n";
print "<br>";
print '</div></div>';


llxFooterVierge();

$db->close();
