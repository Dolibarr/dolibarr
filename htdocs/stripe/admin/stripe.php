<?php
/* Copyright (C) 2017		Alexandre Spangaro		<aspangaro@zendsi.com>
 * Copyright (C) 2017		Olivier Geffroy			<jeff@jeffinfo.com>
 * Copyright (C) 2017		Saasprov				<saasprov@gmail.com>
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
 * \file       htdocs/stripe/admin/stripe.php
 * \ingroup    stripe
 * \brief      Page to setup stripe module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/stripe/lib/stripe.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

$servicename='Stripe';

$langs->load("admin");
$langs->load("other");
$langs->load("paypal");
$langs->load("paybox");
$langs->load("stripe");

if (!$user->admin)
  accessforbidden();
  
$action = GETPOST('action','alpha');


if ($action == 'setvalue' && $user->admin)
{
	$db->begin();

    $result=dolibarr_set_const($db, "STRIPE_LIVE",GETPOST('STRIPE_LIVE','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "STRIPE_TEST_SECRET_KEY",GETPOST('STRIPE_TEST_SECRET_KEY','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "STRIPE_TEST_PUBLISHABLE_KEY",GETPOST('STRIPE_TEST_PUBLISHABLE_KEY','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "STRIPE_LIVE_SECRET_KEY",GETPOST('STRIPE_LIVE_SECRET_KEY','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "STRIPE_LIVE_PUBLISHABLE_KEY",GETPOST('STRIPE_LIVE_PUBLISHABLE_KEY','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "STRIPE_CREDITOR",GETPOST('STRIPE_CREDITOR','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
	$result=dolibarr_set_const($db, "STRIPE_CSS_URL",GETPOST('STRIPE_CSS_URL','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "STRIPE_MESSAGE_OK",GETPOST('STRIPE_MESSAGE_OK','alpha'),'chaine',0,'',$conf->entity);
    if (! $result > 0) $error++;
    $result=dolibarr_set_const($db, "STRIPE_MESSAGE_KO",GETPOST('STRIPE_MESSAGE_KO','alpha'),'chaine',0,'',$conf->entity);
	if (! $result > 0) $error++;
	
    if (! $error)
  	{
  		$db->commit();
	    setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
  	}
  	else
  	{
  		$db->rollback();
		dol_print_error($db);
    }
}

if ($action=="setlive")
{
	$liveenable = GETPOST('value','int');
	$res = dolibarr_set_const($db, "STRIPE_LIVE", $liveenable,'yesno',0,'',$conf->entity);
	if (! $res > 0) $error++;
	if (! $error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

/*
 *	View
 */

$SECRET_TEST_KEY="sk_test_xxxxxxxxxxxxxxxxxxxxxxxx"; // Stripe test secret key
if (empty($conf->global->STRIPE_TEST_SECRET_KEY)) $conf->global->STRIPE_TEST_SECRET_KEY = $SECRET_TEST_KEY;
$PUBLISHABLE_TEST_KEY="pk_test_xxxxxxxxxxxxxxxxxxxxxxxx"; // Stripe test publishable key
if (empty($conf->global->STRIPE_TEST_PUBLISHABLE_KEY)) $conf->global->STRIPE_TEST_PUBLISHABLE_KEY = $PUBLISHABLE_TEST_KEY;

$SECRET_LIVE_KEY="sk_live_xxxxxxxxxxxxxxxxxxxxxxxx"; // Stripe live secret key
if (empty($conf->global->STRIPE_LIVE_SECRET_KEY)) $conf->global->STRIPE_LIVE_SECRET_KEY = $SECRET_LIVE_KEY;
$PUBLISHABLE_LIVE_KEY="pk_live_xxxxxxxxxxxxxxxxxxxxxxxx"; // Stripe live publishable key
if (empty($conf->global->STRIPE_LIVE_PUBLISHABLE_KEY)) $conf->global->STRIPE_LIVE_PUBLISHABLE_KEY = $PUBLISHABLE_LIVE_KEY;

llxHeader('',$langs->trans("StripeSetup"));


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ModuleSetup").' Stripe',$linkback);
print '<br>';

$head=stripeadmin_prepare_head();

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';

dol_fiche_head($head, 'stripeaccount', '');

print $langs->trans("StripeDesc")."<br>\n";

print '<br>';

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("AccountParameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td class="titlefield fieldrequired">';
print $langs->trans("StripeLiveEnabled").'</td><td>';
if (!empty($conf->global->STRIPE_LIVE))
{
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=setlive&value=0">';
	print img_picto($langs->trans("Activated"),'switch_on');
}
else
{
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=setlive&value=1">';
	print img_picto($langs->trans("Disabled"),'switch_off');
}
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print '<span class="titlefield fieldrequired">'.$langs->trans("STRIPE_TEST_SECRET_KEY").'</span></td><td>';
print '<input size="32" type="text" name="STRIPE_TEST_SECRET_KEY" value="'.$conf->global->STRIPE_TEST_SECRET_KEY.'">';
print '<br>'.$langs->trans("Example").': sk_test_xxxxxxxxxxxxxxxxxxxxxxxx';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print '<span class="fieldrequired">'.$langs->trans("STRIPE_TEST_PUBLISHABLE_KEY").'</span></td><td>';
print '<input size="32" type="text" name="STRIPE_TEST_PUBLISHABLE_KEY" value="'.$conf->global->STRIPE_TEST_PUBLISHABLE_KEY.'">';
print '<br>'.$langs->trans("Example").': pk_test_xxxxxxxxxxxxxxxxxxxxxxxx';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print '<span class="fieldrequired">'.$langs->trans("STRIPE_LIVE_SECRET_KEY").'</span></td><td>';
print '<input size="32" type="text" name="STRIPE_LIVE_SECRET_KEY" value="'.$conf->global->STRIPE_LIVE_SECRET_KEY.'">';
print '<br>'.$langs->trans("Example").': sk_live_xxxxxxxxxxxxxxxxxxxxxxxx';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print '<span class="fieldrequired">'.$langs->trans("STRIPE_LIVE_PUBLISHABLE_KEY").'</span></td><td>';
print '<input size="32" type="text" name="STRIPE_LIVE_PUBLISHABLE_KEY" value="'.$conf->global->STRIPE_LIVE_PUBLISHABLE_KEY.'">';
print '<br>'.$langs->trans("Example").': pk_live_xxxxxxxxxxxxxxxxxxxxxxxx';
print '</td></tr>';

print '</table>';

print '<br>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("UsageParameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("VendorName").'</td><td>';
print '<input size="64" type="text" name="STRIPE_CREDITOR" value="'.$conf->global->STRIPE_CREDITOR.'">';
print '<br>'.$langs->trans("Example").': '.$mysoc->name;
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("CSSUrlForPaymentForm").'</td><td>';
print '<input size="64" type="text" name="STRIPE_CSS_URL" value="'.$conf->global->STRIPE_CSS_URL.'">';
print '<br>'.$langs->trans("Example").': http://mysite/mycss.css';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("MessageOK").'</td><td>';
$doleditor=new DolEditor('STRIPE_MESSAGE_OK',$conf->global->STRIPE_MESSAGE_OK,'',100,'dolibarr_details','In',false,true,true,ROWS_2,'90%');
$doleditor->Create();
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("MessageKO").'</td><td>';
$doleditor=new DolEditor('STRIPE_MESSAGE_KO',$conf->global->STRIPE_MESSAGE_KO,'',100,'dolibarr_details','In',false,true,true,ROWS_2,'90%');
$doleditor->Create();
print '</td></tr>';

print '</table>';

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></div>';

print '</form>';

print '<br><br>';

/*
print '<u>'.$langs->trans("FollowingUrlAreAvailableToMakePayments").':</u><br>';
print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnFreeAmount",$servicename).':<br>';
print '<b>'.DOL_MAIN_URL_ROOT.'/public/stripe/newpayment.php?amount=<i>9.99</i>&tag=<i>your_free_tag</i></b>'."<br>\n";
if (! empty($conf->commande->enabled))
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnOrder",$servicename).':<br>';
	print '<b>'.DOL_MAIN_URL_ROOT.'/public/stripe/newpayment.php?source=order&ref=<i>order_ref</i></b>'."<br>\n";
}
if (! empty($conf->facture->enabled))
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnInvoice",$servicename).':<br>';
	print '<b>'.DOL_MAIN_URL_ROOT.'/public/stripe/newpayment.php?source=invoice&ref=<i>invoice_ref</i></b>'."<br>\n";
}
if (! empty($conf->contrat->enabled))
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnContractLine",$servicename).':<br>';
	print '<b>'.DOL_MAIN_URL_ROOT.'/public/stripe/newpayment.php?source=contractline&ref=<i>contractline_ref</i></b>'."<br>\n";
}
if (! empty($conf->adherent->enabled))
{
	print img_picto('','object_globe.png').' '.$langs->trans("ToOfferALinkForOnlinePaymentOnMemberSubscription",$servicename).':<br>';
	print '<b>'.DOL_MAIN_URL_ROOT.'/public/stripe/newpayment.php?source=membersubscription&ref=<i>member_ref</i></b>'."<br>\n";
}

print "<br>";
print info_admin($langs->trans("YouCanAddTagOnUrl"));
*/

llxFooter();

$db->close();
