<?php
/* Copyright (C) 2001-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003	Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2014	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2015-2016	Alexandre Spangaro		<aspangaro.dolibarr@gmail.com>
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
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

$langs->load("companies");
$langs->load("bills");
$langs->load("members");
$langs->load("users");
$langs->load("mails");
$langs->load('other');

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$rowid=GETPOST('rowid','int');
$typeid=GETPOST('typeid','int');

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
		// Creation user
		$company = new Societe($db);
		$result=$company->create_from_member($object,$_POST["companyname"]);

		if ($result < 0)
		{
			$langs->load("errors");
			$errmsg=$langs->trans($company->error);
			setEventMessages($company->error, $company->errors, 'errors');
		}
		else
		{
			$action='addsubscription';
		}
	}
	else
	{
		$errmsg=$object->error;
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

    $result=$object->fetch($rowid);
    $result=$adht->fetch($object->typeid);

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
    $subscription=$_POST["subscription"];	// Amount of subscription
    $label=$_POST["label"];

    // Payment informations
    $accountid=$_POST["accountid"];
    $operation=$_POST["operation"]; // Payment mode
    $num_chq=$_POST["num_chq"];
    $emetteur_nom=$_POST["chqemetteur"];
    $emetteur_banque=$_POST["chqbank"];
    $option=$_POST["paymentsave"];
    if (empty($option)) $option='none';

    // Check parameters
    if (! $datesubscription)
    {
        $error++;
        $langs->load("errors");
        $errmsg=$langs->trans("ErrorBadDateFormat",$langs->transnoentitiesnoconv("DateSubscription"));
        $action='addsubscription';
    }
    if (GETPOST('end') && ! $datesubend)
    {
        $error++;
        $langs->load("errors");
        $errmsg=$langs->trans("ErrorBadDateFormat",$langs->transnoentitiesnoconv("DateEndSubscription"));
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
        $action='addsubscription';
    }

    // Check if a payment is mandatory or not
    if (! $error && $adht->subscription)	// Member type need subscriptions
    {
        if (! is_numeric($_POST["subscription"]))
        {
            // If field is '' or not a numeric value
            $errmsg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Amount"));
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
                    if ($_POST["paymentsave"] != 'invoiceonly' && ! $_POST["accountid"]) $errmsg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("FinancialAccount"));
                }
                else
                {
                    if ($_POST["accountid"])   $errmsg=$langs->trans("ErrorDoNotProvideAccountsIfNullAmount");
                }
                if ($errmsg) $action='addsubscription';
            }
        }
    }

    if (! $error && $action=='subscription')
    {
        $db->begin();

        // Create subscription
        $crowid=$object->subscription($datesubscription, $subscription, $accountid, $operation, $label, $num_chq, $emetteur_nom, $emetteur_banque, $datesubend);
        if ($crowid <= 0)
        {
            $error++;
            $errmsg=$object->error;
	        setEventMessages($object->error,$object->errors, 'errors');
        }

        if (! $error)
        {
            // Insert into bank account directlty (if option choosed for) + link to llx_subscription if option is 'bankdirect'
            if ($option == 'bankdirect' && $accountid)
            {
                require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

                $acct=new Account($db);
                $result=$acct->fetch($accountid);

                $dateop=$paymentdate;

                $insertid=$acct->addline($dateop, $operation, $label, $subscription, $num_chq, '', $user, $emetteur_nom, $emetteur_banque);
                if ($insertid > 0)
                {
                    $inserturlid=$acct->add_url_line($insertid, $object->id, DOL_URL_ROOT.'/adherents/card.php?rowid=', $object->getFullname($langs), 'member');
                    if ($inserturlid > 0)
                    {
                        // Update table subscription
                        $sql ="UPDATE ".MAIN_DB_PREFIX."subscription SET fk_bank=".$insertid;
                        $sql.=" WHERE rowid=".$crowid;

                        dol_syslog("subscription::subscription", LOG_DEBUG);
                        $resql = $db->query($sql);
                        if (! $resql)
                        {
                            $error++;
                            $errmsg=$db->lasterror();
                        }
                    }
                    else
					{
                        $error++;
                        $errmsg=$acct->error;
                        $errmsgs=$acct->errors;
                    }
                }
                else
				{
                    $error++;
                    $errmsg=$acct->error;
                    $errmsgs=$acct->errors;
                }
            }

            // If option choosed, we create invoice
            if (($option == 'bankviainvoice' && $accountid) || $option == 'invoiceonly')
            {
                require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
                require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/paymentterm.class.php';

                $invoice=new Facture($db);
                $customer=new Societe($db);

                if (! $error)
                {
                	if (! ($object->fk_soc > 0))
                	{
                		$langs->load("errors");
                		$errmsg=$langs->trans("ErrorMemberNotLinkedToAThirpartyLinkOrCreateFirst");
                		$error++;
                	}
                }
                if (! $error)
                {
	                $result=$customer->fetch($object->fk_soc);
	                if ($result <= 0)
	                {
	                    $errmsg=$customer->error;
	                    $errmsgs=$acct->errors;
	                    $error++;
	                }
                }

                if (! $error)
                {
	                // Create draft invoice
	                $invoice->type= Facture::TYPE_STANDARD;
	                $invoice->cond_reglement_id=$customer->cond_reglement_id;
	                if (empty($invoice->cond_reglement_id))
	                {
	                    $paymenttermstatic=new PaymentTerm($db);
	                    $invoice->cond_reglement_id=$paymenttermstatic->getDefaultId();
	                    if (empty($invoice->cond_reglement_id))
	                    {
	                        $error++;
	                        $errmsg='ErrorNoPaymentTermRECEPFound';
	                    }
	                }
	                $invoice->socid=$object->fk_soc;
	                $invoice->date=$datesubscription;

	                // Possibility to add external linked objects with hooks
	                $invoice->linked_objects['subscription'] = $crowid;
	                if (! empty($_POST['other_linked_objects']) && is_array($_POST['other_linked_objects']))
	                {
	                    $invoice->linked_objects = array_merge($invoice->linked_objects, $_POST['other_linked_objects']);
	                }
	                 
	                $result=$invoice->create($user);
	                if ($result <= 0)
	                {
	                    $errmsg=$invoice->error;
	                    $errmsgs=$invoice->errors;
	                    $error++;
	                }
                }

                if (! $error)
                {
	                // Add line to draft invoice
	                $idprodsubscription=0;
			        if (! empty($conf->global->ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS) && (! empty($conf->product->enabled) || ! empty($conf->service->enabled))) $idprodsubscription = $conf->global->ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS;

	                $vattouse=0;
	                if (isset($conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS) && $conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS == 'defaultforfoundationcountry')
	                {
	                	$vattouse=get_default_tva($mysoc, $mysoc, $idprodsubscription);
	                }
	                //print xx".$vattouse." - ".$mysoc." - ".$customer;exit;
	                $result=$invoice->addline($label,0,1,$vattouse,0,0,$idprodsubscription,0,$datesubscription,$datesubend,0,0,'','TTC',$subscription,1);
	                if ($result <= 0)
	                {
	                    $errmsg=$invoice->error;
	                    $error++;
	                }
                }

                if (! $error)
                {
	                // Validate invoice
	                $result=$invoice->validate($user);
   	                if ($result <= 0)
	                {
	                    $errmsg=$invoice->error;
	                    $errmsgs=$invoice->errors;
	                    $error++;
	                }
                }

                // Add payment onto invoice
                if ($option == 'bankviainvoice' && $accountid)
                {
                    require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
                    require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
                    require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

                    $amounts[$invoice->id] = price2num($subscription);
                    $paiement = new Paiement($db);
                    $paiement->datepaye     = $paymentdate;
                    $paiement->amounts      = $amounts;
                    $paiement->paiementid   = dol_getIdFromCode($db,$operation,'c_paiement');
                    $paiement->num_paiement = $num_chq;
                    $paiement->note         = $label;

                    if (! $error)
                    {
	                    // Create payment line for invoice
                    	$paiement_id = $paiement->create($user);
                        if (! $paiement_id > 0)
                        {
                            $errmsg=$paiement->error;
                            $errmsgs=$paiement->errors;
                            $error++;
                        }
                    }

                    if (! $error)
                    {
                    	// Add transaction into bank account
                        $bank_line_id=$paiement->addPaymentToBank($user,'payment','(SubscriptionPayment)',$accountid,$emetteur_nom,$emetteur_banque);
                        if (! ($bank_line_id > 0))
                        {
                            $errmsg=$paiement->error;
                            $errmsgs=$paiement->errors;
	                        setEventMessages($paiement->error, $paiement->errors, 'errors');
                            $error++;
                        }
                    }

                    if (! $error)
                    {
                        // Update fk_bank into subscription table
                        $sql = 'UPDATE '.MAIN_DB_PREFIX.'subscription SET fk_bank='.$bank_line_id;
                        $sql.= ' WHERE rowid='.$crowid;

	                    $result = $db->query($sql);
                        if (! $result)
                        {
                            $error++;
                        }
                    }

                    if (! $error)
                    {
                        // Set invoice as paid
                    	$invoice->set_paid($user);
                    }

                    if (! $error)
                    {
						// Define output language
						$outputlangs = $langs;
						$newlang = '';
						if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
							$newlang = $_REQUEST['lang_id'];
						if ($conf->global->MAIN_MULTILANGS && empty($newlang))
							$newlang = $customer->default_lang;
						if (! empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
						}
                    	// Generate PDF (whatever is option MAIN_DISABLE_PDF_AUTOUPDATE) so we can include it into email
						//if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))

	                    $invoice->generateDocument($invoice->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
                    }
                }
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

        // Send email
        if (! $error)
        {
            // Send confirmation Email
            if ($object->email && $_POST["sendmail"])
            {
                $subjecttosend=$object->makeSubstitution($conf->global->ADHERENT_MAIL_COTIS_SUBJECT);
                $texttosend=$object->makeSubstitution($adht->getMailOnSubscription());

                $result=$object->send_an_email($texttosend,$subjecttosend,array(),array(),array(),"","",0,-1);
                if ($result < 0)
                {
                	$errmsg=$object->error;
                }
            }

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

    dol_fiche_head($head, 'subscription', $langs->trans("Member"), 0, 'user');

    $linkback = '<a href="'.DOL_URL_ROOT.'/adherents/list.php">'.$langs->trans("BackToList").'</a>';
    
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
	$parameters=array('colspan'=>2);
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	if (empty($reshook) && ! empty($extrafields->attribute_label))
	{
		print $object->showOptionals($extrafields, 'view', $parameters);
	}

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
     * Hotbar
     */

    // Button to create a new subscription if member no draft neither resiliated
    if ($user->rights->adherent->cotisation->creer)
    {
        if ($action != 'addsubscription' && $action != 'create_thirdparty')
        {
            print '<div class="tabsAction">';

            if ($object->statut > 0) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?rowid='.$rowid.'&action=addsubscription">'.$langs->trans("AddSubscription")."</a></div>";
            else print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("ValidateBefore")).'">'.$langs->trans("AddSubscription").'</a></div>';

            print "<br>\n";

            print '</div>';
            print '<br>';
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
        $sql.= " ba.rowid as baid, ba.label, ba.bank, ba.ref, ba.account_number, ba.accountancy_journal, ba.number";
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."subscription as c";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON c.fk_bank = b.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
        $sql.= " WHERE d.rowid = c.fk_adherent AND d.rowid=".$rowid;

        $result = $db->query($sql);
        if ($result)
        {
            $subscriptionstatic=new Subscription($db);
            $accountstatic=new Account($db);

            $num = $db->num_rows($result);
            $i = 0;

            print '<table class="noborder" width="100%">'."\n";

            print '<tr class="liste_titre">';
            print '<td>'.$langs->trans("Ref").'</td>';
            print '<td align="center">'.$langs->trans("DateCreation").'</td>';
            print '<td align="center">'.$langs->trans("DateStart").'</td>';
            print '<td align="center">'.$langs->trans("DateEnd").'</td>';
            print '<td align="right">'.$langs->trans("Amount").'</td>';
            if (! empty($conf->banque->enabled))
            {
                print '<td align="right">'.$langs->trans("Account").'</td>';
            }
            print "</tr>\n";

            $var=True;
            while ($i < $num)
            {
                $objp = $db->fetch_object($result);
                $var=!$var;
                print "<tr ".$bc[$var].">";
                $subscriptionstatic->ref=$objp->crowid;
                $subscriptionstatic->id=$objp->crowid;
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
                        $accountstatic->accountancy_journal=$objp->accountancy_journal;
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


        // Link for paypal payment
        if (! empty($conf->paypal->enabled))
        {
            include_once DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php';
            print showPaypalPaymentUrl('membersubscription',$object->ref);
        }

    }

    /*
     * Add new subscription form
     */
    if (($action == 'addsubscription' || $action == 'create_thirdparty') && $user->rights->adherent->cotisation->creer)
    {
        print '<br>';

        print load_fiche_titre($langs->trans("NewCotisation"));

        // Define default choice to select
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
			$name = $object->getFullName($langs);
			if (! empty($name))
			{
				if ($object->morphy == 'mor' && ! empty($object->societe)) $name=$object->societe.' ('.$name.')';
				else if ($object->societe) $name.=' ('.$object->societe.')';
			}
			else
			{
				$name=$object->societe;
			}

			// Create a form array
			$formquestion=array(array('label' => $langs->trans("NameToCreate"), 'type' => 'text', 'name' => 'companyname', 'value' => $name));

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

        // Date start subscription
        print '<tr><td width="30%" class="fieldrequired">'.$langs->trans("DateSubscription").'</td><td>';
        if (GETPOST('reday'))
        {
            $datefrom=dol_mktime(0,0,0,GETPOST('remonth'),GETPOST('reday'),GETPOST('reyear'));
        }
        if (! $datefrom)
        {
            if ($object->datefin > 0)
            {
                $datefrom=dol_time_plus_duree($object->datefin,1,'d');
            }
            else
			{
                //$datefrom=dol_now();
				$datefrom=$object->datevalid;
            }
        }
        print $form->select_date($datefrom,'','','','',"subscription",1,1,1);
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
        print $form->select_date($dateto,'end','','','',"subscription",1,0,1);
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
                print $form->select_date(isset($paymentdate)?$paymentdate:-1,'payment',0,0,1,'subscription',1,1,1);
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

            $subjecttosend=$object->makeSubstitution($conf->global->ADHERENT_MAIL_COTIS_SUBJECT);
            $texttosend=$object->makeSubstitution($adht->getMailOnSubscription());

            $tmp='<input name="sendmail" type="checkbox"'.(GETPOST('sendmail')?GETPOST('sendmail'):(! empty($conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL)?' checked':'')).'>';
            $helpcontent='';
            $helpcontent.='<b>'.$langs->trans("MailFrom").'</b>: '.$conf->global->ADHERENT_MAIL_FROM.'<br>'."\n";
            $helpcontent.='<b>'.$langs->trans("MailRecipient").'</b>: '.$object->email.'<br>'."\n";
            $helpcontent.='<b>'.$langs->trans("MailTopic").'</b>:<br>'."\n";
            $helpcontent.=$subjecttosend."\n";
            $helpcontent.="<br>";
            $helpcontent.='<b>'.$langs->trans("MailText").'</b>:<br>';
            $helpcontent.=dol_htmlentitiesbr($texttosend)."\n";

            print $form->textwithpicto($tmp,$helpcontent,1,'help');
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


llxFooter();

$db->close();
