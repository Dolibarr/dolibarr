<?php
/* Copyright (C) 2001-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003	Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@capnetworks.com>
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
 *       \file       htdocs/adherents/card_subscriptions.php
 *       \ingroup    member
 *       \brief      Onglet d'ajout, edition, suppression des adhesions d'un adherent
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/cotisation.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

$langs->load("companies");
$langs->load("bills");
$langs->load("members");
$langs->load("users");
$langs->load("mails");


$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$rowid=GETPOST('rowid','int');
$typeid=GETPOST('typeid','int');

// Security check
$result=restrictedArea($user,'adherent',$rowid,'','cotisation');

$object = new Adherent($db);
$extrafields = new ExtraFields($db);
$adht = new AdherentType($db);
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
			$errmsgs=$company->errors;
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
            $mesg='<div class="error">'.$langs->trans("ErrorUserPermissionAllowsToLinksToItselfOnly").'</div>';
        }
    }

    if (! $error)
    {
        if ($_POST["userid"] != $object->user_id)  // If link differs from currently in database
        {
            $result=$object->setUserId($_POST["userid"]);
            if ($result < 0) dol_print_error($object->db,$object->error);
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
                    $mesg='<div class="error">'.$langs->trans("ErrorMemberIsAlreadyLinkedToThisThirdParty",$othermember->getFullName($langs),$othermember->login,$thirdparty->name).'</div>';
                }
            }

            if (! $error)
            {
                $result=$object->setThirdPartyId(GETPOST('socid','int'));
                if ($result < 0) dol_print_error($object->db,$object->error);
                $_POST['action']='';
                $action='';
            }
        }
    }
}

if ($user->rights->adherent->cotisation->creer && $action == 'cotisation' && ! $_POST["cancel"])
{
    $error=0;

    $langs->load("banks");

    $result=$object->fetch($rowid);
    $result=$adht->fetch($object->typeid);

    // Subscription informations
    $datecotisation=0;
    $datesubend=0;
    $paymentdate=0;
    if ($_POST["reyear"] && $_POST["remonth"] && $_POST["reday"])
    {
        $datecotisation=dol_mktime(0, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
    }
    if ($_POST["endyear"] && $_POST["endmonth"] && $_POST["endday"])
    {
        $datesubend=dol_mktime(0, 0, 0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
    }
    if ($_POST["paymentyear"] && $_POST["paymentmonth"] && $_POST["paymentday"])
    {
        $paymentdate=dol_mktime(0, 0, 0, $_POST["paymentmonth"], $_POST["paymentday"], $_POST["paymentyear"]);
    }
    $cotisation=$_POST["cotisation"];	// Amount of subscription
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
    if (! $datecotisation)
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
        $datesubend=dol_time_plus_duree(dol_time_plus_duree($datecotisation,$defaultdelay,$defaultdelayunit),-1,'d');
    }
    if (($option == 'bankviainvoice' || $option == 'bankdirect') && ! $paymentdate)
    {
        $error++;
        $errmsg=$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DatePayment"));
        $action='addsubscription';
    }

    // Check if a payment is mandatory or not
    if (! $error && $adht->cotisation)	// Type adherent soumis a cotisation
    {
        if (! is_numeric($_POST["cotisation"]))
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
                if ($_POST["cotisation"])
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

    if (! $error && $action=='cotisation')
    {
        $db->begin();

        // Create subscription
        $crowid=$object->cotisation($datecotisation, $cotisation, $accountid, $operation, $label, $num_chq, $emetteur_nom, $emetteur_banque, $datesubend, $option);
        if ($crowid <= 0)
        {
            $error++;
            $errmsg=$object->error;
            $errmsgs=$object->errors;
        }

        if (! $error)
        {
            // Insert into bank account directlty (if option choosed for) + link to llx_cotisation if option is 'bankdirect'
            if ($option == 'bankdirect' && $accountid)
            {
                require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

                $acct=new Account($db);
                $result=$acct->fetch($accountid);

                $dateop=$paymentdate;

                $insertid=$acct->addline($dateop, $operation, $label, $cotisation, $num_chq, '', $user, $emetteur_nom, $emetteur_banque);
                if ($insertid > 0)
                {
                    $inserturlid=$acct->add_url_line($insertid, $object->id, DOL_URL_ROOT.'/adherents/fiche.php?rowid=', $object->getFullname($langs), 'member');
                    if ($inserturlid > 0)
                    {
                        // Met a jour la table cotisation
                        $sql ="UPDATE ".MAIN_DB_PREFIX."cotisation SET fk_bank=".$insertid;
                        $sql.=" WHERE rowid=".$crowid;

                        dol_syslog("card_subscriptions::cotisation sql=".$sql);
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
                    }
                }
                else
                {
                    $error++;
                    $errmsg=$acct->error;
                }
            }

            // If option choosed, we create invoice
            if (($option == 'bankviainvoice' && $accountid) || $option == 'invoiceonly')
            {
                require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
                require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/paymentterm.class.php';

                $invoice=new Facture($db);
                $customer=new Societe($db);
                $result=$customer->fetch($object->fk_soc);
                if ($result <= 0)
                {
                    $errmsg=$customer->error;
                    $error++;
                }

                // Create draft invoice
                $invoice->type=0;
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
                $invoice->date=$datecotisation;

                $result=$invoice->create($user);
                if ($result <= 0)
                {
                    $errmsg=$invoice->error;
                    $error++;
                }

                // Add line to draft invoice
                $idprodsubscription=0;
                $vattouse=0;
                if (isset($conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS) && $conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS == 'defaultforfoundationcountry')
                {
                	$vattouse=get_default_tva($mysoc, $mysoc, $idprodsubscription);
                }
                //print xx".$vattouse." - ".$mysoc." - ".$customer;exit;
                $result=$invoice->addline($invoice->id,$label,0,1,$vattouse,0,0,$idprodsubscription,0,$datecotisation,$datesubend,0,0,'','TTC',$cotisation,1);
                if ($result <= 0)
                {
                    $errmsg=$invoice->error;
                    $error++;
                }

                // Validate invoice
                $result=$invoice->validate($user);

                // Add payment onto invoice
                if ($option == 'bankviainvoice' && $accountid)
                {
                    require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
                    require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
                    require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

                    // Creation de la ligne paiement
                    $amounts[$invoice->id] = price2num($cotisation);
                    $paiement = new Paiement($db);
                    $paiement->datepaye     = $paymentdate;
                    $paiement->amounts      = $amounts;
                    $paiement->paiementid   = dol_getIdFromCode($db,$operation,'c_paiement');
                    $paiement->num_paiement = $num_chq;
                    $paiement->note         = $label;

                    if (! $error)
                    {
                        $paiement_id = $paiement->create($user);
                        if (! $paiement_id > 0)
                        {
                            $errmsg=$paiement->error;
                            $error++;
                        }
                    }

                    if (! $error)
                    {
                        $bank_line_id=$paiement->addPaymentToBank($user,'payment','(SubscriptionPayment)',$accountid,$emetteur_nom,$emetteur_banque);
                        if (! ($bank_line_id > 0))
                        {
                            $errmsg=$paiement->error;
                            $errmsgs=$paiement->errors;
                            $error++;
                        }
                    }

                    if (! $error)
                    {
                        // Update fk_bank for subscriptions
                        $sql = 'UPDATE '.MAIN_DB_PREFIX.'cotisation SET fk_bank='.$bank_line_id;
                        $sql.= ' WHERE rowid='.$crowid;
                        dol_syslog('sql='.$sql);
                        $result = $db->query($sql);
                        if (! $result)
                        {
                            $error++;
                        }
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
                if ($result < 0) $errmsg=$object->error;
            }

            $_POST["cotisation"]='';
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

llxHeader('',$langs->trans("Subscriptions"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

if ($rowid)
{
    $res=$object->fetch($rowid);
    if ($res < 0) { dol_print_error($db,$object->error); exit; }

    $adht->fetch($object->typeid);

    $head = member_prepare_head($object);

    dol_fiche_head($head, 'subscription', $langs->trans("Member"), 0, 'user');

    $rowspan=9;
    if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) $rowspan+=1;
    if (! empty($conf->societe->enabled)) $rowspan++;

    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="rowid" value="'.$object->id.'">';
    print '<table class="border" width="100%">';

    $linkback = '<a href="'.DOL_URL_ROOT.'/adherents/liste.php">'.$langs->trans("BackToList").'</a>';

    // Ref
    print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
    print '<td class="valeur" colspan="2">';
    print $form->showrefnav($object, 'rowid', $linkback);
    print '</td></tr>';

    $showphoto='<td rowspan="'.$rowspan.'" class="hideonsmartphone" align="center" valign="middle" width="25%">'.$form->showphoto('memberphoto',$object).'</td>';

    // Login
    if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
    {
        print '<tr><td>'.$langs->trans("Login").' / '.$langs->trans("Id").'</td><td class="valeur">'.$object->login.'&nbsp;</td>';
        print $showphoto; $showphoto='';
        print '</tr>';
    }

    // Morphy
    print '<tr><td>'.$langs->trans("Nature").'</td><td class="valeur" >'.$object->getmorphylib().'</td>';
    print $showphoto; $showphoto='';
    print '</tr>';

    // Type
    print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">'.$adht->getNomUrl(1)."</td></tr>\n";

    // Company
    print '<tr><td>'.$langs->trans("Company").'</td><td class="valeur">'.$object->societe.'</td></tr>';

    // Civility
    print '<tr><td>'.$langs->trans("UserTitle").'</td><td class="valeur">'.$object->getCivilityLabel().'&nbsp;</td>';
    print '</tr>';

    // Lastname
    print '<tr><td>'.$langs->trans("Lastname").'</td><td class="valeur">'.$object->lastname.'&nbsp;</td>';
    print '</tr>';

    // Firstname
    print '<tr><td>'.$langs->trans("Firstname").'</td><td class="valeur">'.$object->firstname.'&nbsp;</td>';
    print '</tr>';

    // EMail
    print '<tr><td>'.$langs->trans("EMail").'</td><td class="valeur">'.dol_print_email($object->email,0,$object->fk_soc,1).'</td></tr>';
    
    // Status
    print '<tr><td>'.$langs->trans("Status").'</td><td class="valeur">'.$object->getLibStatut(4).'</td></tr>';

    // Date end subscription
    print '<tr><td>'.$langs->trans("SubscriptionEndDate").'</td><td class="valeur">';
    if ($object->datefin)
    {
        print dol_print_date($object->datefin,'day');
        if ($object->datefin < ($now -  $conf->adherent->cotisation->warning_delay) && $object->statut > 0) print " ".img_warning($langs->trans("Late")); // Affiche picto retard uniquement si non brouillon et non resilie
    }
    else
    {
        print $langs->trans("SubscriptionNotReceived");
        if ($object->statut > 0) print " ".img_warning($langs->trans("Late")); // Affiche picto retard uniquement si non brouillon et non resilie
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
        print '</td><td class="valeur">';
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
    if ($action != 'editlogin' && $user->rights->adherent->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editlogin&amp;rowid='.$object->id.'">'.img_edit($langs->trans('SetLinkToUser'),1).'</a></td>';
    print '</tr></table>';
    print '</td><td class="valeur">';
    if ($action == 'editlogin')
    {
        /*$include=array();
         if (empty($user->rights->user->user->creer))    // If can edit only itself user, we can link to itself only
         {
         $include=array($object->user_id,$user->id);
         }*/
        print $form->form_users($_SERVER['PHP_SELF'].'?rowid='.$object->id,$object->user_id,'userid','');
    }
    else
    {
        if ($object->user_id)
        {
            print $form->form_users($_SERVER['PHP_SELF'].'?rowid='.$object->id,$object->user_id,'none');
        }
        else print $langs->trans("NoDolibarrAccess");
    }
    print '</td></tr>';

    print "</table>\n";
    print '</form>';

    dol_fiche_end();


    dol_htmloutput_errors($errmsg,$errmsgs);


    /*
     * Barre d'actions
     */

    // Lien nouvelle cotisation si non brouillon et non resilie
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
        $sql.= " c.rowid as crowid, c.cotisation,";
        $sql.= " c.datec,";
        $sql.= " c.dateadh,";
        $sql.= " c.datef,";
        $sql.= " c.fk_bank,";
        $sql.= " b.rowid as bid,";
        $sql.= " ba.rowid as baid, ba.label, ba.bank";
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."cotisation as c";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON c.fk_bank = b.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
        $sql.= " WHERE d.rowid = c.fk_adherent AND d.rowid=".$rowid;

        $result = $db->query($sql);
        if ($result)
        {
            $cotisationstatic=new Cotisation($db);
            $accountstatic=new Account($db);

            $num = $db->num_rows($result);
            $i = 0;

            print "<table class=\"noborder\" width=\"100%\">\n";

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
                print "<tr $bc[$var]>";
                $cotisationstatic->ref=$objp->crowid;
                $cotisationstatic->id=$objp->crowid;
                print '<td>'.$cotisationstatic->getNomUrl(1).'</td>';
                print '<td align="center">'.dol_print_date($db->jdate($objp->datec),'dayhour')."</td>\n";
                print '<td align="center">'.dol_print_date($db->jdate($objp->dateadh),'day')."</td>\n";
                print '<td align="center">'.dol_print_date($db->jdate($objp->datef),'day')."</td>\n";
                print '<td align="right">'.price($objp->cotisation).'</td>';
                if (! empty($conf->banque->enabled))
                {
                    print '<td align="right">';
                    if ($objp->bid)
                    {
                        $accountstatic->label=$objp->label;
                        $accountstatic->id=$objp->baid;
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

        print_fiche_titre($langs->trans("NewCotisation"));

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
        	if (! empty($conf->global->ADHERENT_BANK_USE) && $conf->global->ADHERENT_BANK_USE == 'bankviainvoice' && ! empty($conf->banque->enabled) && ! empty($conf->societe->enabled) && ! empty($conf->facture->enabled) && $object->fk_soc) $bankviainvoice=1;
       		else if (! empty($conf->global->ADHERENT_BANK_USE) && $conf->global->ADHERENT_BANK_USE == 'bankdirect' && ! empty($conf->banque->enabled)) $bankdirect=1;
        	else if (! empty($conf->global->ADHERENT_BANK_USE) && $conf->global->ADHERENT_BANK_USE == 'invoiceonly' && ! empty($conf->banque->enabled) && ! empty($conf->societe->enabled) && ! empty($conf->facture->enabled) && $object->fk_soc) $invoiceonly=1;
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
            if (GETPOST('paymentsave')) print '$("#'.GETPOST('paymentsave').'").attr("checked",true);';
    	    print '});';
            print '</script>'."\n";
        }


		// Confirm create third party
		if ($action == 'create_thirdparty')
		{
			$name = $object->getFullName($langs);
			if (! empty($name))
			{
				if ($object->societe) $name.=' ('.$object->societe.')';
			}
			else
			{
				$name=$object->societe;
			}

			// Create a form array
			$formquestion=array(array('label' => $langs->trans("NameToCreate"), 'type' => 'text', 'name' => 'companyname', 'value' => $name));

			$ret=$form->form_confirm($_SERVER["PHP_SELF"]."?rowid=".$object->id,$langs->trans("CreateDolibarrThirdParty"),$langs->trans("ConfirmCreateThirdParty"),"confirm_create_thirdparty",$formquestion,1);
			if ($ret == 'html') print '<br>';
		}


        print '<form name="cotisation" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="cotisation">';
        print '<input type="hidden" name="rowid" value="'.$rowid.'">';
        print '<input type="hidden" name="memberlabel" id="memberlabel" value="'.dol_escape_htmltag($object->getFullName($langs)).'">';
        print '<input type="hidden" name="thirdpartylabel" id="thirdpartylabel" value="'.dol_escape_htmltag($object->societe).'">';
        print "<table class=\"border\" width=\"100%\">\n";

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
        $form->select_date($datefrom,'','','','',"cotisation",1,1);
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
        $form->select_date($dateto,'end','','','',"cotisation");
        print "</td></tr>";

        if ($adht->cotisation)
        {
            // Amount
            print '<tr><td class="fieldrequired">'.$langs->trans("Amount").'</td><td><input type="text" name="cotisation" size="6" value="'.GETPOST('cotisation').'"> '.$langs->trans("Currency".$conf->currency).'</td></tr>';

            // Label
            print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td>';
            print '<td><input name="label" type="text" size="32" value="'.$langs->trans("Subscription").' ';
            print dol_print_date(($datefrom?$datefrom:time()),"%Y").'" ></td></tr>';

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
                print '<tr><td valign="top" class="fieldrequired">'.$langs->trans('MoreActions');
                print '</td>';
                print '<td>';
                print '<input type="radio" class="moreaction" id="none" name="paymentsave" value="none"'.(empty($bankdirect) && empty($invoiceonly) && empty($bankviainvoice)?' checked="checked"':'').'> '.$langs->trans("None").'<br>';
                // Add entry into bank accoun
                if (! empty($conf->banque->enabled))
                {
                    print '<input type="radio" class="moreaction" id="bankdirect" name="paymentsave" value="bankdirect"'.(! empty($bankdirect)?' checked="checked"':'');
                    print '> '.$langs->trans("MoreActionBankDirect").'<br>';
                }
                // Add invoice with no payments
                if (! empty($conf->societe->enabled) && ! empty($conf->facture->enabled))
                {
                    print '<input type="radio" class="moreaction" id="invoiceonly" name="paymentsave" value="invoiceonly"'.(! empty($invoiceonly)?' checked="checked"':'');
                    if (empty($object->fk_soc)) print ' disabled="disabled"';
                    print '> '.$langs->trans("MoreActionInvoiceOnly");
                    if ($object->fk_soc) print ' ('.$langs->trans("ThirdParty").': '.$company->getNomUrl(1).')';
                    else
					{
                    	print ' ('.$langs->trans("NoThirdPartyAssociatedToMember");
                    	print ' - <a href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&amp;action=create_thirdparty">';
                    	print $langs->trans("CreateDolibarrThirdParty");
                    	print '</a>)';
                    }
                    if (empty($conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS) || $conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS != 'defaultforfoundationcountry') print '. '.$langs->trans("NoVatOnSubscription",0).'.';
                    print '<br>';
                }
                // Add invoice with payments
                if (! empty($conf->banque->enabled) && ! empty($conf->societe->enabled) && ! empty($conf->facture->enabled))
                {
                    print '<input type="radio" class="moreaction" id="bankviainvoice" name="paymentsave" value="bankviainvoice"'.(! empty($bankviainvoice)?' checked="checked"':'');
                    if (empty($object->fk_soc)) print ' disabled="disabled"';
                    print '> '.$langs->trans("MoreActionBankViaInvoice");
                    if ($object->fk_soc) print ' ('.$langs->trans("ThirdParty").': '.$company->getNomUrl(1).')';
                    else
					{
                    	print ' ('.$langs->trans("NoThirdPartyAssociatedToMember");
                    	print ' - <a href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&amp;action=create_thirdparty">';
                    	print $langs->trans("CreateDolibarrThirdParty");
                    	print '</a>)';
                    }
                    if (empty($conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS) || $conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS != 'defaultforfoundationcountry') print '. '.$langs->trans("NoVatOnSubscription",0).'.';
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
                $form->select_date(isset($paymentdate)?$paymentdate:-1,'payment',0,0,1,'cotisation',1,1);
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

            $tmp='<input name="sendmail" type="checkbox"'.(GETPOST('sendmail')?GETPOST('sendmail'):(! empty($conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL)?' checked="checked"':'')).'>';
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
        print '</table>';
        print '<br>';

        print '<center>';
        print '<input type="submit" class="button" name="add" value="'.$langs->trans("AddSubscription").'">';
        print ' &nbsp; &nbsp; ';
        print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
        print '</center>';

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
?>