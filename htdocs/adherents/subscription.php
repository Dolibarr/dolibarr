<?php
/* Copyright (C) 2001-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2012-2017  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2016  Alexandre Spangaro      <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/adherents/subscription.php
 *       \ingroup    member
 *       \brief      Onglet d'ajout, edition, suppression des adhesions d'un adherent
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';

$langs->loadLangs(array("companies","bills","members","users","mails",'other'));

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$rowid=GETPOST('rowid','int')?GETPOST('rowid','int'):GETPOST('id','int');
$typeid=GETPOST('typeid','int');

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Default sort order (if not yet defined by previous GETPOST)
if (! $sortfield) $sortfield="c.rowid";
if (! $sortorder) $sortorder="DESC";


// Security check
$result=restrictedArea($user,'adherent',$rowid,'','cotisation');

$object = new Adherent($db);
$extrafields = new ExtraFields($db);
$adht = new AdherentType($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

$errmsg='';
$errmsgs=array();

$defaultdelay=1;
$defaultdelayunit='y';

if ($rowid)
{
    // Load member
    $result = $object->fetch($rowid);

    // Define variables to know what current user can do on users
    $canadduser=($user->admin || $user->rights->user->user->creer);
    // Define variables to know what current user can do on properties of user linked to edited member
    if ($object->user_id)
    {
        // $user est le user qui edite, $object->user_id est l'id de l'utilisateur lies au membre edite
        $caneditfielduser=( (($user->id == $object->user_id) && $user->rights->user->self->creer)
        || (($user->id != $object->user_id) && $user->rights->user->user->creer) );
        $caneditpassworduser=( (($user->id == $object->user_id) && $user->rights->user->self->password)
        || (($user->id != $object->user_id) && $user->rights->user->user->password) );
    }
}

// Define variables to know what current user can do on members
$canaddmember=$user->rights->adherent->creer;
// Define variables to know what current user can do on properties of a member
if ($rowid)
{
    $caneditfieldmember=$user->rights->adherent->creer;
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('subscription'));

// PDF
$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0));
$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));


/*
 * 	Actions
 */

// Create third party from a member
if ($action == 'confirm_create_thirdparty' && $confirm == 'yes' && $user->rights->societe->creer)
{
	if ($result > 0)
	{
		// Creation of thirdparty
		$company = new Societe($db);
		$result=$company->create_from_member($object, GETPOST('companyname', 'alpha'), GETPOST('companyalias', 'alpha'), GETPOST('customercode', 'alpha'));

		if ($result < 0)
		{
			$langs->load("errors");
			setEventMessages($company->error, $company->errors, 'errors');
		}
		else
		{
			$action='addsubscription';
		}
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if ($action == 'setuserid' && ($user->rights->user->self->creer || $user->rights->user->user->creer))
{
    $error=0;
    if (empty($user->rights->user->user->creer))    // If can edit only itself user, we can link to itself only
    {
        if ($_POST["userid"] != $user->id && $_POST["userid"] != $object->user_id)
        {
            $error++;
            setEventMessages($langs->trans("ErrorUserPermissionAllowsToLinksToItselfOnly"), null, 'errors');
        }
    }

    if (! $error)
    {
        if ($_POST["userid"] != $object->user_id)  // If link differs from currently in database
        {
            $result=$object->setUserId($_POST["userid"]);
            if ($result < 0) dol_print_error('',$object->error);
            $_POST['action']='';
            $action='';
        }
    }
}

if ($action == 'setsocid')
{
    $error=0;
    if (! $error)
    {
        if (GETPOST('socid','int') != $object->fk_soc)    // If link differs from currently in database
        {
            $sql ="SELECT rowid FROM ".MAIN_DB_PREFIX."adherent";
            $sql.=" WHERE fk_soc = '".GETPOST('socid','int')."'";
            $resql = $db->query($sql);
            if ($resql)
            {
                $obj = $db->fetch_object($resql);
                if ($obj && $obj->rowid > 0)
                {
                    $othermember=new Adherent($db);
                    $othermember->fetch($obj->rowid);
                    $thirdparty=new Societe($db);
                    $thirdparty->fetch(GETPOST('socid','int'));
                    $error++;
	                setEventMessages($langs->trans("ErrorMemberIsAlreadyLinkedToThisThirdParty",$othermember->getFullName($langs),$othermember->login,$thirdparty->name), null, 'errors');
                }
            }

            if (! $error)
            {
                $result=$object->setThirdPartyId(GETPOST('socid','int'));
                if ($result < 0) dol_print_error('',$object->error);
                $_POST['action']='';
                $action='';
            }
        }
    }
}

if ($user->rights->adherent->cotisation->creer && $action == 'subscription' && ! $_POST["cancel"])
{
    $error=0;

    $langs->load("banks");

    $result = $object->fetch($rowid);
    $result = $adht->fetch($object->typeid);

    // Subscription informations
    $datesubscription=0;
    $datesubend=0;
    $paymentdate=0;
    if ($_POST["reyear"] && $_POST["remonth"] && $_POST["reday"])
    {
        $datesubscription=dol_mktime(0, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
    }
    if ($_POST["endyear"] && $_POST["endmonth"] && $_POST["endday"])
    {
        $datesubend=dol_mktime(0, 0, 0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
    }
    if ($_POST["paymentyear"] && $_POST["paymentmonth"] && $_POST["paymentday"])
    {
        $paymentdate=dol_mktime(0, 0, 0, $_POST["paymentmonth"], $_POST["paymentday"], $_POST["paymentyear"]);
    }
    $amount=price2num(GETPOST("subscription",'alpha'));	// Amount of subscription
    $label=$_POST["label"];

    // Payment informations
    $accountid=$_POST["accountid"];
    $operation=$_POST["operation"]; // Payment mode
    $num_chq=$_POST["num_chq"];
    $emetteur_nom=$_POST["chqemetteur"];
    $emetteur_banque=$_POST["chqbank"];
    $option=$_POST["paymentsave"];
    if (empty($option)) $option='none';
    $sendalsoemail=GETPOST("sendmail",'alpha');

    // Check parameters
    if (! $datesubscription)
    {
        $error++;
        $langs->load("errors");
        $errmsg=$langs->trans("ErrorBadDateFormat",$langs->transnoentitiesnoconv("DateSubscription"));
        setEventMessages($errmsg, null, 'errors');
        $action='addsubscription';
    }
    if (GETPOST('end') && ! $datesubend)
    {
        $error++;
        $langs->load("errors");
        $errmsg=$langs->trans("ErrorBadDateFormat",$langs->transnoentitiesnoconv("DateEndSubscription"));
        setEventMessages($errmsg, null, 'errors');
        $action='addsubscription';
    }
    if (! $datesubend)
    {
        $datesubend=dol_time_plus_duree(dol_time_plus_duree($datesubscription,$defaultdelay,$defaultdelayunit),-1,'d');
    }
    if (($option == 'bankviainvoice' || $option == 'bankdirect') && ! $paymentdate)
    {
        $error++;
        $errmsg=$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DatePayment"));
        setEventMessages($errmsg, null, 'errors');
        $action='addsubscription';
    }

    // Check if a payment is mandatory or not
    if (! $error && $adht->subscription)	// Member type need subscriptions
    {
        if (! is_numeric($amount))
        {
            // If field is '' or not a numeric value
            $errmsg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Amount"));
        	setEventMessages($errmsg, null, 'errors');
            $error++;
            $action='addsubscription';
        }
        else
        {
            if (! empty($conf->banque->enabled) && $_POST["paymentsave"] != 'none')
            {
                if ($_POST["subscription"])
                {
                    if (! $_POST["label"])     $errmsg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label"));
                    if ($_POST["paymentsave"] != 'invoiceonly' && ! $_POST["operation"]) $errmsg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("PaymentMode"));
                    if ($_POST["paymentsave"] != 'invoiceonly' && ! ($_POST["accountid"] > 0)) $errmsg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("FinancialAccount"));
                }
                else
                {
                    if ($_POST["accountid"])   $errmsg=$langs->trans("ErrorDoNotProvideAccountsIfNullAmount");
                }
                if ($errmsg)
                {
                	$error++;
        			setEventMessages($errmsg, null, 'errors');
        			$error++;
                	$action='addsubscription';
                }
            }
        }
    }

    // Record the subscription then complementary actions
    if (! $error && $action=='subscription')
    {
        $db->begin();

        // Create subscription
        $crowid=$object->subscription($datesubscription, $amount, $accountid, $operation, $label, $num_chq, $emetteur_nom, $emetteur_banque, $datesubend);
        if ($crowid <= 0)
        {
            $error++;
            $errmsg=$object->error;
	        setEventMessages($object->error,$object->errors, 'errors');
        }

        if (! $error)
        {
        	$result = $object->subscriptionComplementaryActions($crowid, $option, $accountid, $datesubscription, $paymentdate, $operation, $label, $amount, $num_chq, $emetteur_nom, $emetteur_banque);
			if ($result < 0)
			{
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
			else
			{
				// If an invoice was created, it is into $object->invoice
			}
        }

        if (! $error)
        {
            $db->commit();
        }
        else
        {
            $db->rollback();
            $action = 'addsubscription';
        }

        if (! $error)
        {
        	setEventMessages("SubscriptionRecorded", null, 'mesgs');
        }

        // Send email
        if (! $error)
        {
            // Send confirmation Email
            if ($object->email && $sendalsoemail)
            {
            	$subject = '';
            	$msg= '';

            	// Send subscription email
            	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
            	$formmail=new FormMail($db);
            	// Set output language
            	$outputlangs = new Translate('', $conf);
            	$outputlangs->setDefaultLang(empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang);
            	// Load traductions files requiredby by page
            	$outputlangs->loadLangs(array("main", "members"));

            	// Get email content from template
            	$arraydefaultmessage=null;
            	$labeltouse = $conf->global->ADHERENT_EMAIL_TEMPLATE_SUBSCRIPTION;

            	if (! empty($labeltouse)) $arraydefaultmessage=$formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);

            	if (! empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0)
            	{
            		$subject = $arraydefaultmessage->topic;
            		$msg     = $arraydefaultmessage->content;
            	}

            	$substitutionarray=getCommonSubstitutionArray($outputlangs, 0, null, $object);
            	complete_substitutions_array($substitutionarray, $outputlangs, $object);
            	$subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
            	$texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnSubscription()), $substitutionarray, $outputlangs);

                // Attach a file ?
                $file='';
                $listofpaths=array();
                $listofnames=array();
                $listofmimes=array();
                if (is_object($object->invoice))
                {
                	$invoicediroutput = $conf->facture->dir_output;
                	$fileparams = dol_most_recent_file($invoicediroutput . '/' . $object->invoice->ref, preg_quote($object->invoice->ref, '/').'[^\-]+');
                	$file = $fileparams['fullname'];

                	$listofpaths=array($file);
                	$listofnames=array(basename($file));
                	$listofmimes=array(dol_mimetype($file));
                }

                $moreinheader='X-Dolibarr-Info: send_an_email by adherents/subscription.php'."\r\n";

                $result=$object->send_an_email($texttosend, $subjecttosend, $listofpaths, $listofnames, $listofmimes, "", "", 0, -1, '', $moreinheader);
                if ($result < 0)
                {
                	$errmsg=$object->error;
                	setEventMessages($object->error, $object->errors, 'errors');
                }
                else
                {
                	setEventMessages($langs->trans("EmailSentToMember", $object->email), null, 'mesgs');
                }
            }
            else
            {
            	setEventMessages($langs->trans("NoEmailSentToMember"), null, 'mesgs');
            }
        }

        // Clean some POST vars
        if (! $error)
        {
            $_POST["subscription"]='';
            $_POST["accountid"]='';
            $_POST["operation"]='';
            $_POST["label"]='';
            $_POST["num_chq"]='';
        }
    }
}



/*
 * View
 */

$form = new Form($db);

$now=dol_now();

$title=$langs->trans("Member") . " - " . $langs->trans("Subscriptions");
$helpurl="EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros";
llxHeader("",$title,$helpurl);


$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
$param.= '&id='.$rowid;
if ($optioncss != '')     $param.='&optioncss='.urlencode($optioncss);
// Add $param from extra fields
//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';


if ($rowid > 0)
{
    $res=$object->fetch($rowid);
    if ($res < 0) { dol_print_error($db,$object->error); exit; }

    $adht->fetch($object->typeid);

    $head = member_prepare_head($object);

    $rowspan=10;
    if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) $rowspan++;
    if (! empty($conf->societe->enabled)) $rowspan++;

    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="rowid" value="'.$object->id.'">';

    dol_fiche_head($head, 'subscription', $langs->trans("Member"), -1, 'user');

    $linkback = '<a href="'.DOL_URL_ROOT.'/adherents/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'rowid', $linkback);

    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';

    print '<div class="underbanner clearboth"></div>';
    print '<table class="border" width="100%">';

	// Login
	if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
	{
		print '<tr><td class="titlefield">'.$langs->trans("Login").' / '.$langs->trans("Id").'</td><td class="valeur">'.$object->login.'&nbsp;</td></tr>';
	}

	// Type
	print '<tr><td class="titlefield">'.$langs->trans("Type").'</td><td class="valeur">'.$adht->getNomUrl(1)."</td></tr>\n";

	// Morphy
	print '<tr><td>'.$langs->trans("Nature").'</td><td class="valeur" >'.$object->getmorphylib().'</td>';
	print '</tr>';

	// Company
	print '<tr><td>'.$langs->trans("Company").'</td><td class="valeur">'.$object->societe.'</td></tr>';

	// Civility
	print '<tr><td>'.$langs->trans("UserTitle").'</td><td class="valeur">'.$object->getCivilityLabel().'&nbsp;</td>';
	print '</tr>';

	// Password
	if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
	{
		print '<tr><td>'.$langs->trans("Password").'</td><td>'.preg_replace('/./i','*',$object->pass);
		if ((! empty($object->pass) || ! empty($object->pass_crypted)) && empty($object->user_id))
		{
		    $langs->load("errors");
		    $htmltext=$langs->trans("WarningPasswordSetWithNoAccount");
		    print ' '.$form->textwithpicto('', $htmltext,1,'warning');
		}
		print '</td></tr>';
	}

    print '</table>';

    print '</div>';
    print '<div class="fichehalfright"><div class="ficheaddleft">';

    print '<div class="underbanner clearboth"></div>';
    print '<table class="border tableforfield" width="100%">';

	// Birthday
	print '<tr><td class="titlefield">'.$langs->trans("Birthday").'</td><td class="valeur">'.dol_print_date($object->birth,'day').'</td></tr>';

	// Public
	print '<tr><td>'.$langs->trans("Public").'</td><td class="valeur">'.yn($object->public).'</td></tr>';

	// Categories
	if (! empty($conf->categorie->enabled)  && ! empty($user->rights->categorie->lire))
	{
		print '<tr><td>' . $langs->trans("Categories") . '</td>';
		print '<td colspan="2">';
		print $form->showCategories($object->id, 'member', 1);
		print '</td></tr>';
	}

    // Other attributes
    $cols=2;
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	// Date end subscription
	print '<tr><td>'.$langs->trans("SubscriptionEndDate").'</td><td class="valeur">';
	if ($object->datefin)
	{
	    print dol_print_date($object->datefin,'day');
	    if ($object->hasDelay()) {
	        print " ".img_warning($langs->trans("Late"));
	    }
	}
	else
	{
	    if (! $adht->subscription)
	    {
	        print $langs->trans("SubscriptionNotRecorded");
	        if ($object->statut > 0) print " ".img_warning($langs->trans("Late")); // Affiche picto retard uniquement si non brouillon et non resilie
	    }
	    else
	    {
	        print $langs->trans("SubscriptionNotReceived");
	        if ($object->statut > 0) print " ".img_warning($langs->trans("Late")); // Affiche picto retard uniquement si non brouillon et non resilie
	    }
	}
	print '</td></tr>';

	// Third party Dolibarr
	if (! empty($conf->societe->enabled))
	{
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans("LinkedToDolibarrThirdParty");
		print '</td>';
		if ($action != 'editthirdparty' && $user->rights->adherent->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editthirdparty&amp;rowid='.$object->id.'">'.img_edit($langs->trans('SetLinkToThirdParty'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="2" class="valeur">';
		if ($action == 'editthirdparty')
		{
			$htmlname='socid';
			print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" name="form'.$htmlname.'">';
			print '<input type="hidden" name="rowid" value="'.$object->id.'">';
			print '<input type="hidden" name="action" value="set'.$htmlname.'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			print $form->select_company($object->fk_soc,'socid','',1);
			print '</td>';
			print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
			print '</tr></table></form>';
		}
		else
		{
			if ($object->fk_soc)
			{
				$company=new Societe($db);
				$result=$company->fetch($object->fk_soc);
				print $company->getNomUrl(1);
			}
			else
			{
				print $langs->trans("NoThirdPartyAssociatedToMember");
			}
		}
		print '</td></tr>';
	}

	// Login Dolibarr
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans("LinkedToDolibarrUser");
	print '</td>';
	if ($action != 'editlogin' && $user->rights->adherent->creer)
	{
		print '<td align="right">';
		if ($user->rights->user->user->creer)
		{
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=editlogin&amp;rowid='.$object->id.'">'.img_edit($langs->trans('SetLinkToUser'),1).'</a>';
		}
		print '</td>';
	}
	print '</tr></table>';
	print '</td><td colspan="2" class="valeur">';
	if ($action == 'editlogin')
	{
		$form->form_users($_SERVER['PHP_SELF'].'?rowid='.$object->id,$object->user_id,'userid','');
	}
	else
	{
		if ($object->user_id)
		{
			$form->form_users($_SERVER['PHP_SELF'].'?rowid='.$object->id,$object->user_id,'none');
		}
		else print $langs->trans("NoDolibarrAccess");
	}
	print '</td></tr>';

    print "</table>\n";

	print "</div></div></div>\n";
    print '<div style="clear:both"></div>';

    dol_fiche_end();

    print '</form>';


    /*
     * Action buttons
     */

    // Button to create a new subscription if member no draft neither resiliated
    if ($user->rights->adherent->cotisation->creer)
    {
        if ($action != 'addsubscription' && $action != 'create_thirdparty')
        {
            print '<div class="tabsAction">';

            if ($object->statut > 0) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?rowid='.$rowid.'&action=addsubscription">'.$langs->trans("AddSubscription")."</a></div>";
            else print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("ValidateBefore")).'">'.$langs->trans("AddSubscription").'</a></div>';

            print '</div>';
        }
    }

    /*
     * List of subscriptions
     */
    if ($action != 'addsubscription' && $action != 'create_thirdparty')
    {
        $sql = "SELECT d.rowid, d.firstname, d.lastname, d.societe,";
        $sql.= " c.rowid as crowid, c.subscription,";
        $sql.= " c.datec,";
        $sql.= " c.dateadh as dateh,";
        $sql.= " c.datef,";
        $sql.= " c.fk_bank,";
        $sql.= " b.rowid as bid,";
        $sql.= " ba.rowid as baid, ba.label, ba.bank, ba.ref, ba.account_number, ba.fk_accountancy_journal, ba.number";
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."subscription as c";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON c.fk_bank = b.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
        $sql.= " WHERE d.rowid = c.fk_adherent AND d.rowid=".$rowid;
		$sql.= $db->order($sortfield, $sortorder);

        $result = $db->query($sql);
        if ($result)
        {
            $subscriptionstatic=new Subscription($db);

            $num = $db->num_rows($result);
            $i = 0;

            print '<table class="noborder" width="100%">'."\n";

            print '<tr class="liste_titre">';
            print_liste_field_titre('Ref',$_SERVER["PHP_SELF"],'c.rowid','',$param,'',$sortfield,$sortorder);
            print '<td align="center">'.$langs->trans("DateCreation").'</td>';
            print '<td align="center">'.$langs->trans("DateStart").'</td>';
            print '<td align="center">'.$langs->trans("DateEnd").'</td>';
            print '<td align="right">'.$langs->trans("Amount").'</td>';
            if (! empty($conf->banque->enabled))
            {
                print '<td align="right">'.$langs->trans("Account").'</td>';
            }
            print "</tr>\n";

            $accountstatic=new Account($db);

            while ($i < $num)
            {
                $objp = $db->fetch_object($result);

                $subscriptionstatic->ref=$objp->crowid;
                $subscriptionstatic->id=$objp->crowid;

                print '<tr class="oddeven">';
                print '<td>'.$subscriptionstatic->getNomUrl(1).'</td>';
                print '<td align="center">'.dol_print_date($db->jdate($objp->datec),'dayhour')."</td>\n";
                print '<td align="center">'.dol_print_date($db->jdate($objp->dateh),'day')."</td>\n";
                print '<td align="center">'.dol_print_date($db->jdate($objp->datef),'day')."</td>\n";
                print '<td align="right">'.price($objp->subscription).'</td>';
				if (! empty($conf->banque->enabled))
				{
					print '<td align="right">';
					if ($objp->bid)
					{
						$accountstatic->label=$objp->label;
						$accountstatic->id=$objp->baid;
						$accountstatic->number=$objp->number;
						$accountstatic->account_number=$objp->account_number;

						if (! empty($conf->accounting->enabled))
						{
							$accountingjournal = new AccountingJournal($db);
							$accountingjournal->fetch($objp->fk_accountancy_journal);

							$accountstatic->accountancy_journal = $accountingjournal->getNomUrl(0,1,1,'',1);
						}

                        $accountstatic->ref=$objp->ref;
                        print $accountstatic->getNomUrl(1);
                    }
                    else
                    {
                        print '&nbsp;';
                    }
                    print '</td>';
                }
                print "</tr>";
                $i++;
            }
            print "</table>";
        }
        else
        {
            dol_print_error($db);
        }
    }


    if (($action != 'addsubscription' && $action != 'create_thirdparty'))
    {
	    // Shon online payment link
	    $useonlinepayment = (! empty($conf->paypal->enabled) || ! empty($conf->stripe->enabled) || ! empty($conf->paybox->enabled));

	    if ($useonlinepayment)
	    {
	    	print '<br>';

	    	require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
	    	print showOnlinePaymentUrl('membersubscription', $object->ref);
	    	print '<br>';
	    }
    }

    /*
     * Add new subscription form
     */
    if (($action == 'addsubscription' || $action == 'create_thirdparty') && $user->rights->adherent->cotisation->creer)
    {
        print '<br>';

        print load_fiche_titre($langs->trans("NewCotisation"));

        // Define default choice for complementary actions
        $bankdirect=0;        // 1 means option by default is write to bank direct with no invoice
        $invoiceonly=0;		  // 1 means option by default is invoice only
        $bankviainvoice=0;    // 1 means option by default is write to bank via invoice
        if (GETPOST('paymentsave'))
        {
        	if (GETPOST('paymentsave') == 'bankdirect')     $bankdirect=1;
        	if (GETPOST('paymentsave') == 'invoiceonly')    $invoiceonly=1;
        	if (GETPOST('paymentsave') == 'bankviainvoice') $bankviainvoice=1;
        }
        else
        {
        	if (! empty($conf->global->ADHERENT_BANK_USE) && $conf->global->ADHERENT_BANK_USE == 'bankviainvoice' && ! empty($conf->banque->enabled) && ! empty($conf->societe->enabled) && ! empty($conf->facture->enabled)) $bankviainvoice=1;
        	else if (! empty($conf->global->ADHERENT_BANK_USE) && $conf->global->ADHERENT_BANK_USE == 'bankdirect' && ! empty($conf->banque->enabled)) $bankdirect=1;
        	else if (! empty($conf->global->ADHERENT_BANK_USE) && $conf->global->ADHERENT_BANK_USE == 'invoiceonly' && ! empty($conf->banque->enabled) && ! empty($conf->societe->enabled) && ! empty($conf->facture->enabled)) $invoiceonly=1;
        }

        print "\n\n<!-- Form add subscription -->\n";

        if ($conf->use_javascript_ajax)
        {
        	//var_dump($bankdirect.'-'.$bankviainvoice.'-'.$invoiceonly.'-'.empty($conf->global->ADHERENT_BANK_USE));
            print "\n".'<script type="text/javascript" language="javascript">';
            print '$(document).ready(function () {
                        $(".bankswitchclass, .bankswitchclass2").'.(($bankdirect||$bankviainvoice)?'show()':'hide()').';
                        $("#none, #invoiceonly").click(function() {
                            $(".bankswitchclass").hide();
                            $(".bankswitchclass2").hide();
                        });
                        $("#bankdirect, #bankviainvoice").click(function() {
                            $(".bankswitchclass").show();
                            $(".bankswitchclass2").show();
                        });
                        $("#selectoperation").change(function() {
                            var code = $(this).val();
                            if (code == "CHQ")
                            {
                                $(".fieldrequireddyn").addClass("fieldrequired");
                            	if ($("#fieldchqemetteur").val() == "")
                            	{
                                	$("#fieldchqemetteur").val($("#memberlabel").val());
                            	}
                            }
                            else
                            {
                                $(".fieldrequireddyn").removeClass("fieldrequired");
                            }
                        });
                        ';
            if (GETPOST('paymentsave')) print '$("#'.GETPOST('paymentsave').'").prop("checked",true);';
    	    print '});';
            print '</script>'."\n";
        }


		// Confirm create third party
		if ($action == 'create_thirdparty')
		{
			$companyalias='';
			$fullname = $object->getFullName($langs);

			if ($object->morphy == 'mor')
			{
				$companyname=$object->societe;
				if (! empty($fullname)) $companyalias=$fullname;
			}
			else
			{
				$companyname=$fullname;
				if (! empty($object->societe)) $companyalias=$object->societe;
			}

			// Create a form array
			$formquestion=array(
				array('label' => $langs->trans("NameToCreate"), 'type' => 'text', 'name' => 'companyname', 'value' => $companyname, 'morecss' => 'minwidth300', 'moreattr' => 'maxlength="128"'),
				array('label' => $langs->trans("AliasNames"), 'type' => 'text', 'name' => 'companyalias', 'value' => $companyalias, 'morecss' => 'minwidth300', 'moreattr' => 'maxlength="128"')
			);
			// If customer code was forced to "required", we ask it at creation to avoid error later
			if (! empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED))
			{
				$tmpcompany = new Societe($db);
				$tmpcompany->name=$companyname;
				$customercode = $tmpcompany->get_codeclient($tmpcompany,0);
				$formquestion[]=array('label' => $langs->trans("CustomerCode"), 'type' => 'text', 'name' => 'customercode', 'value' => $customercode, 'morecss' => 'minwidth300', 'moreattr' => 'maxlength="128"');
			}
			// @TODO Add other extrafields mandatory for thirdparty creation

			print $form->formconfirm($_SERVER["PHP_SELF"]."?rowid=".$object->id,$langs->trans("CreateDolibarrThirdParty"),$langs->trans("ConfirmCreateThirdParty"),"confirm_create_thirdparty",$formquestion,1);
		}


        print '<form name="subscription" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="subscription">';
        print '<input type="hidden" name="rowid" value="'.$rowid.'">';
        print '<input type="hidden" name="memberlabel" id="memberlabel" value="'.dol_escape_htmltag($object->getFullName($langs)).'">';
        print '<input type="hidden" name="thirdpartylabel" id="thirdpartylabel" value="'.dol_escape_htmltag($object->societe).'">';

		dol_fiche_head('');

		print "<table class=\"border\" width=\"100%\">\n";
        print '<tbody>';

		$today=dol_now();
        $datefrom=0;
        $dateto=0;
        $paymentdate=-1;

        // Date payment
        if (GETPOST('paymentyear') && GETPOST('paymentmonth') && GETPOST('paymentday'))
        {
            $paymentdate=dol_mktime(0, 0, 0, GETPOST('paymentmonth'), GETPOST('paymentday'), GETPOST('paymentyear'));
        }

        print '<tr>';
        // Date start subscription
        print '<td class="fieldrequired">'.$langs->trans("DateSubscription").'</td><td>';
        if (GETPOST('reday'))
        {
            $datefrom=dol_mktime(0,0,0,GETPOST('remonth'),GETPOST('reday'),GETPOST('reyear'));
        }
        if (! $datefrom)
        {
        	$datefrom=$object->datevalid;
        	if ($object->datefin > 0)
            {
                $datefrom=dol_time_plus_duree($object->datefin,1,'d');
            }
        }
        print $form->selectDate($datefrom, '', '', '', '', "subscription", 1, 1);
        print "</td></tr>";

        // Date end subscription
        if (GETPOST('endday'))
        {
            $dateto=dol_mktime(0,0,0,GETPOST('endmonth'),GETPOST('endday'),GETPOST('endyear'));
        }
        if (! $dateto)
        {
            $dateto=-1;		// By default, no date is suggested
        }
        print '<tr><td>'.$langs->trans("DateEndSubscription").'</td><td>';
        print $form->selectDate($dateto, 'end', '', '', '', "subscription", 1, 0);
        print "</td></tr>";

        if ($adht->subscription)
        {
            // Amount
            print '<tr><td class="fieldrequired">'.$langs->trans("Amount").'</td><td><input type="text" name="subscription" size="6" value="'.GETPOST('subscription').'"> '.$langs->trans("Currency".$conf->currency).'</td></tr>';

            // Label
            print '<tr><td>'.$langs->trans("Label").'</td>';
            print '<td><input name="label" type="text" size="32" value="';
            if (empty($conf->global->MEMBER_NO_DEFAULT_LABEL)) print $langs->trans("Subscription").' '.dol_print_date(($datefrom?$datefrom:time()),"%Y");
            print '"></td></tr>';

            // Complementary action
            if (! empty($conf->banque->enabled) || ! empty($conf->facture->enabled))
            {
                $company=new Societe($db);
                if ($object->fk_soc)
                {
                    $result=$company->fetch($object->fk_soc);
                }

                // Title payments
                //print '<tr><td colspan="2"><b>'.$langs->trans("Payment").'</b></td></tr>';

                // No more action
                print '<tr><td class="tdtop fieldrequired">'.$langs->trans('MoreActions');
                print '</td>';
                print '<td>';
                print '<input type="radio" class="moreaction" id="none" name="paymentsave" value="none"'.(empty($bankdirect) && empty($invoiceonly) && empty($bankviainvoice)?' checked':'').'> '.$langs->trans("None").'<br>';
                // Add entry into bank accoun
                if (! empty($conf->banque->enabled))
                {
                    print '<input type="radio" class="moreaction" id="bankdirect" name="paymentsave" value="bankdirect"'.(! empty($bankdirect)?' checked':'');
                    print '> '.$langs->trans("MoreActionBankDirect").'<br>';
                }
                // Add invoice with no payments
                if (! empty($conf->societe->enabled) && ! empty($conf->facture->enabled))
                {
                    print '<input type="radio" class="moreaction" id="invoiceonly" name="paymentsave" value="invoiceonly"'.(! empty($invoiceonly)?' checked':'');
                    //if (empty($object->fk_soc)) print ' disabled';
                    print '> '.$langs->trans("MoreActionInvoiceOnly");
                    if ($object->fk_soc) print ' ('.$langs->trans("ThirdParty").': '.$company->getNomUrl(1).')';
                    else
					{
                    	print ' (';
                    	if (empty($object->fk_soc)) print img_warning($langs->trans("NoThirdPartyAssociatedToMember"));
                    	print $langs->trans("NoThirdPartyAssociatedToMember");
                    	print ' - <a href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&amp;action=create_thirdparty">';
                    	print $langs->trans("CreateDolibarrThirdParty");
                    	print '</a>)';
                    }
                    if (empty($conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS) || $conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS != 'defaultforfoundationcountry') print '. '.$langs->trans("NoVatOnSubscription",0);
					if (! empty($conf->global->ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS) && (! empty($conf->product->enabled) || ! empty($conf->service->enabled)))
					{
						$prodtmp=new Product($db);
						$prodtmp->fetch($conf->global->ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS);
						print '. '.$langs->transnoentitiesnoconv("ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS", $prodtmp->getNomUrl(1));	// must use noentitiesnoconv to avoid to encode html into getNomUrl of product
					}
                    print '<br>';
                }
                // Add invoice with payments
                if (! empty($conf->banque->enabled) && ! empty($conf->societe->enabled) && ! empty($conf->facture->enabled))
                {
                    print '<input type="radio" class="moreaction" id="bankviainvoice" name="paymentsave" value="bankviainvoice"'.(! empty($bankviainvoice)?' checked':'');
                    //if (empty($object->fk_soc)) print ' disabled';
                    print '> '.$langs->trans("MoreActionBankViaInvoice");
                    if ($object->fk_soc) print ' ('.$langs->trans("ThirdParty").': '.$company->getNomUrl(1).')';
                    else
					{
                    	print ' (';
                    	if (empty($object->fk_soc)) print img_warning($langs->trans("NoThirdPartyAssociatedToMember"));
                    	print $langs->trans("NoThirdPartyAssociatedToMember");
                    	print ' - <a href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&amp;action=create_thirdparty">';
                    	print $langs->trans("CreateDolibarrThirdParty");
                    	print '</a>)';
                    }
                    if (empty($conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS) || $conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS != 'defaultforfoundationcountry') print '. '.$langs->trans("NoVatOnSubscription",0);
					if (! empty($conf->global->ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS) && (! empty($conf->product->enabled) || ! empty($conf->service->enabled)))
					{
						$prodtmp=new Product($db);
						$prodtmp->fetch($conf->global->ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS);
						print '. '.$langs->transnoentitiesnoconv("ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS", $prodtmp->getNomUrl(1));	// must use noentitiesnoconv to avoid to encode html into getNomUrl of product
					}
                    print '<br>';
                }
                print '</td></tr>';

                // Bank account
                print '<tr class="bankswitchclass"><td class="fieldrequired">'.$langs->trans("FinancialAccount").'</td><td>';
                $form->select_comptes(GETPOST('accountid'),'accountid',0,'',1);
                print "</td></tr>\n";

                // Payment mode
                print '<tr class="bankswitchclass"><td class="fieldrequired">'.$langs->trans("PaymentMode").'</td><td>';
                $form->select_types_paiements(GETPOST('operation'),'operation','',2);
                print "</td></tr>\n";

                // Date of payment
                print '<tr class="bankswitchclass"><td class="fieldrequired">'.$langs->trans("DatePayment").'</td><td>';
                print $form->selectDate(isset($paymentdate)?$paymentdate:-1, 'payment', 0, 0, 1, 'subscription', 1, 1);
                print "</td></tr>\n";

                print '<tr class="bankswitchclass2"><td>'.$langs->trans('Numero');
                print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
                print '</td>';
                print '<td><input id="fieldnum_chq" name="num_chq" type="text" size="8" value="'.(! GETPOST('num_chq')?'':GETPOST('num_chq')).'"></td></tr>';

                print '<tr class="bankswitchclass2 fieldrequireddyn"><td>'.$langs->trans('CheckTransmitter');
                print ' <em>('.$langs->trans("ChequeMaker").')</em>';
                print '</td>';
                print '<td><input id="fieldchqemetteur" name="chqemetteur" size="32" type="text" value="'.(! GETPOST('chqemetteur')?'':GETPOST('chqemetteur')).'"></td></tr>';

                print '<tr class="bankswitchclass2"><td>'.$langs->trans('Bank');
                print ' <em>('.$langs->trans("ChequeBank").')</em>';
                print '</td>';
                print '<td><input id="chqbank" name="chqbank" size="32" type="text" value="'.(! GETPOST('chqbank')?'':GETPOST('chqbank')).'"></td></tr>';
            }
        }

        print '<tr><td colspan="2">&nbsp;</td>';

        print '<tr><td width="30%">'.$langs->trans("SendAcknowledgementByMail").'</td>';
        print '<td>';
        if (! $object->email)
        {
            print $langs->trans("NoEMail");
        }
        else
        {
            $adht = new AdherentType($db);
            $adht->fetch($object->typeid);

            // Send subscription email
            $subject = '';
            $msg= '';

            // Send subscription email
            include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
            $formmail=new FormMail($db);
            // Set output language
            $outputlangs = new Translate('', $conf);
            $outputlangs->setDefaultLang(empty($object->thirdparty->default_lang) ? $mysoc->default_lang : $object->thirdparty->default_lang);
            // Load traductions files requiredby by page
            $outputlangs->loadLangs(array("main", "members"));
            // Get email content from template
            $arraydefaultmessage=null;
            $labeltouse = $conf->global->ADHERENT_EMAIL_TEMPLATE_SUBSCRIPTION;

            if (! empty($labeltouse)) $arraydefaultmessage=$formmail->getEMailTemplate($db, 'member', $user, $outputlangs, 0, 1, $labeltouse);

            if (! empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0)
            {
            	$subject = $arraydefaultmessage->topic;
            	$msg     = $arraydefaultmessage->content;
            }

            $substitutionarray=getCommonSubstitutionArray($outputlangs, 0, null, $object);
            complete_substitutions_array($substitutionarray, $outputlangs, $object);
            $subjecttosend = make_substitutions($subject, $substitutionarray, $outputlangs);
            $texttosend = make_substitutions(dol_concatdesc($msg, $adht->getMailOnSubscription()), $substitutionarray, $outputlangs);

            $tmp='<input name="sendmail" type="checkbox"'.(GETPOST('sendmail','alpha')?' checked':(! empty($conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL)?' checked':'')).'>';
            $helpcontent='';
            $helpcontent.='<b>'.$langs->trans("MailFrom").'</b>: '.$conf->global->ADHERENT_MAIL_FROM.'<br>'."\n";
            $helpcontent.='<b>'.$langs->trans("MailRecipient").'</b>: '.$object->email.'<br>'."\n";
            $helpcontent.='<b>'.$langs->trans("MailTopic").'</b>:<br>'."\n";
            $helpcontent.=$subjecttosend."\n";
            $helpcontent.="<br>";
            $helpcontent.='<b>'.$langs->trans("MailText").'</b>:<br>';
            $helpcontent.=dol_htmlentitiesbr($texttosend)."\n";

            print $form->textwithpicto($tmp, $helpcontent, 1, 'help', '', 0, 2, 'helpemailtosend');
        }
        print '</td></tr>';
        print '</tbody>';
        print '</table>';

        dol_fiche_end();

        print '<div class="center">';
        print '<input type="submit" class="button" name="add" value="'.$langs->trans("AddSubscription").'">';
        print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
        print '</div>';

        print '</form>';

        print "\n<!-- End form subscription -->\n\n";
    }

    //print '</td></tr>';
    //print '</table>';
}
else
{
    $langs->load("errors");
    print $langs->trans("ErrorRecordNotFound");
}

// End of page
llxFooter();
$db->close();
