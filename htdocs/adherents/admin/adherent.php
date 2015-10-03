<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012      J. Fernando Lagrange <fernando@demo-tic.org>
 * Copyright (C) 2015       Jean-François Ferry		<jfefe@aternatik.fr>
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
 *   	\file       htdocs/adherents/admin/adherent.php
 *		\ingroup    member
 *		\brief      Page to setup the module Foundation
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';

$langs->load("admin");
$langs->load("members");

if (! $user->admin) accessforbidden();


$type=array('yesno','texte','chaine');

$action = GETPOST('action','alpha');


/*
 * Actions
 */

// Action mise a jour ou ajout d'une constante
if ($action == 'update' || $action == 'add')
{
	$constname=GETPOST('constname','alpha');
	$constvalue=(GETPOST('constvalue_'.$constname) ? GETPOST('constvalue_'.$constname) : GETPOST('constvalue'));

	if (($constname=='ADHERENT_CARD_TYPE' || $constname=='ADHERENT_ETIQUETTE_TYPE' || $constname=='ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS') && $constvalue == -1) $constvalue='';
	if ($constname=='ADHERENT_LOGIN_NOT_REQUIRED') // Invert choice
	{
		if ($constvalue) $constvalue=0;
		else $constvalue=1;
	}

	$consttype=GETPOST('consttype','alpha');
	$constnote=GETPOST('constnote');
	$res=dolibarr_set_const($db,$constname,$constvalue,$type[$consttype],0,$constnote,$conf->entity);

	if (! $res > 0) $error++;

	if (! $error)
	{
		setEventMessage($langs->trans("SetupSaved"));
	}
	else
	{
		setEventMessage($langs->trans("Error"), 'errors');
	}
}

// Action activation d'un sous module du module adherent
if ($action == 'set')
{
    $result=dolibarr_set_const($db, GETPOST('name','alpha'),GETPOST('value'),'',0,'',$conf->entity);
    if ($result < 0)
    {
        print $db->error();
    }
}

// Action desactivation d'un sous module du module adherent
if ($action == 'unset')
{
    $result=dolibarr_del_const($db,GETPOST('name','alpha'),$conf->entity);
    if ($result < 0)
    {
        print $db->error();
    }
}



/*
 * View
 */

$form = new Form($db);

$help_url='EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros';

llxHeader('',$langs->trans("MembersSetup"),$help_url);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("MembersSetup"),$linkback,'title_setup');


$head = member_admin_prepare_head();

dol_fiche_head($head, 'general', $langs->trans("Members"), 0, 'user');

print load_fiche_titre($langs->trans("MemberMainOptions"),'','');
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td align="center">'.$langs->trans("Action").'</td>';
print "</tr>\n";
$var=true;

// Login/Pass required for members
$var=!$var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="constname" value="ADHERENT_LOGIN_NOT_REQUIRED">';
print '<tr '.$bc[$var].'><td>'.$langs->trans("AdherentLoginRequired").'</td><td>';
print $form->selectyesno('constvalue',(! empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)?0:1),1);
print '</td><td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print "</td></tr>\n";
print '</form>';

// Mail required for members
$var=!$var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="constname" value="ADHERENT_MAIL_REQUIRED">';
print '<tr '.$bc[$var].'><td>'.$langs->trans("AdherentMailRequired").'</td><td>';
print $form->selectyesno('constvalue',(! empty($conf->global->ADHERENT_MAIL_REQUIRED)?$conf->global->ADHERENT_MAIL_REQUIRED:0),1);
print '</td><td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print "</td></tr>\n";
print '</form>';

// Send mail information is on by default
$var=!$var;
print '<form action="adherent.php" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="constname" value="ADHERENT_DEFAULT_SENDINFOBYMAIL">';
print '<tr '.$bc[$var].'><td>'.$langs->trans("MemberSendInformationByMailByDefault").'</td><td>';
print $form->selectyesno('constvalue',(! empty($conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL)?$conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL:0),1);
print '</td><td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print "</td></tr>\n";
print '</form>';

// Insert subscription into bank account
$var=!$var;
print '<form action="adherent.php" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="constname" value="ADHERENT_BANK_USE">';
print '<tr '.$bc[$var].'><td>'.$langs->trans("MoreActionsOnSubscription").'</td>';
$arraychoices=array('0'=>$langs->trans("None"));
if (! empty($conf->banque->enabled)) $arraychoices['bankdirect']=$langs->trans("MoreActionBankDirect");
if (! empty($conf->banque->enabled) && ! empty($conf->societe->enabled) && ! empty($conf->facture->enabled)) $arraychoices['invoiceonly']=$langs->trans("MoreActionInvoiceOnly");
if (! empty($conf->banque->enabled) && ! empty($conf->societe->enabled) && ! empty($conf->facture->enabled)) $arraychoices['bankviainvoice']=$langs->trans("MoreActionBankViaInvoice");
print '<td>';
print $form->selectarray('constvalue',$arraychoices,$conf->global->ADHERENT_BANK_USE,0);
print '</td><td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print '</td>';
print "</tr>\n";
print '</form>';

// Use vat for invoice creation
if ($conf->facture->enabled)
{
	$var=!$var;
	print '<form action="adherent.php" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="constname" value="ADHERENT_VAT_FOR_SUBSCRIPTIONS">';
	print '<tr '.$bc[$var].'><td>'.$langs->trans("VATToUseForSubscriptions").'</td>';
	if (! empty($conf->banque->enabled))
	{
		print '<td>';
		print $form->selectarray('constvalue', array('0'=>$langs->trans("NoVatOnSubscription"),'defaultforfoundationcountry'=>$langs->trans("Default")), (empty($conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS)?'0':$conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS), 0);
		print '</td><td align="center" width="80">';
		print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
		print '</td>';
	}
	else
	{
		print '<td align="right" colspan="2">';
		print $langs->trans("WarningModuleNotActive",$langs->transnoentities("Module85Name"));
		print '</td>';
	}
	print "</tr>\n";
	print '</form>';

	if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
	{
		$var=!$var;
		print '<form action="adherent.php" method="POST">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="constname" value="ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS">';
		print '<tr '.$bc[$var].'><td>'.$langs->trans("ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS").'</td>';
		print '<td>';
		$form->select_produits($conf->global->ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS, 'constvalue_ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS');
		print '</td><td align="center" width="80">';
		print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
		print '</td>';
	}
	print "</tr>\n";
	print '</form>';
}

print '</table>';
print '<br>';


/*
 * Edition info modele document
 */
$constantes=array(
		'ADHERENT_CARD_TYPE',
//		'ADHERENT_CARD_BACKGROUND',
		'ADHERENT_CARD_HEADER_TEXT',
		'ADHERENT_CARD_TEXT',
		'ADHERENT_CARD_TEXT_RIGHT',
		'ADHERENT_CARD_FOOTER_TEXT'
		);

print load_fiche_titre($langs->trans("MembersCards"),'','');

form_constantes($constantes);

print '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
print '%DOL_MAIN_URL_ROOT%, %ID%, %FIRSTNAME%, %LASTNAME%, %FULLNAME%, %LOGIN%, %PASSWORD%, ';
print '%COMPANY%, %ADDRESS%, %ZIP%, %TOWN%, %COUNTRY%, %EMAIL%, %BIRTH%, %PHOTO%, %TYPE%, ';
print '%YEAR%, %MONTH%, %DAY%';
print '<br>';

print '<br>';


/*
 * Edition info modele document
 */
$constantes=array('ADHERENT_ETIQUETTE_TYPE','ADHERENT_ETIQUETTE_TEXT');

print load_fiche_titre($langs->trans("MembersTickets"),'','');

form_constantes($constantes);

print '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
print '%DOL_MAIN_URL_ROOT%, %ID%, %FIRSTNAME%, %LASTNAME%, %FULLNAME%, %LOGIN%, %PASSWORD%, ';
print '%COMPANY%, %ADDRESS%, %ZIP%, %TOWN%, %COUNTRY%, %EMAIL%, %BIRTH%, %PHOTO%, %TYPE%, ';
print '%YEAR%, %MONTH%, %DAY%';
print '<br>';

print '<br>';


/*
 * Editing global variables not related to a specific theme
 */
$constantes=array(
		'ADHERENT_AUTOREGISTER_NOTIF_MAIL_SUBJECT',
		'ADHERENT_AUTOREGISTER_NOTIF_MAIL',
		'ADHERENT_AUTOREGISTER_MAIL_SUBJECT',
		'ADHERENT_AUTOREGISTER_MAIL',
		'ADHERENT_MAIL_VALID_SUBJECT',
		'ADHERENT_MAIL_VALID',
		'ADHERENT_MAIL_COTIS_SUBJECT',
		'ADHERENT_MAIL_COTIS',
		'ADHERENT_MAIL_RESIL_SUBJECT',
		'ADHERENT_MAIL_RESIL',
		'ADHERENT_MAIL_FROM',
		);

print load_fiche_titre($langs->trans("Other"),'','');

form_constantes($constantes);

print '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
print '%DOL_MAIN_URL_ROOT%, %ID%, %FIRSTNAME%, %LASTNAME%, %FULLNAME%, %LOGIN%, %PASSWORD%, ';
print '%COMPANY%, %ADDRESS%, %ZIP%, %TOWN%, %COUNTRY%, %EMAIL%, %BIRTH%, %PHOTO%, %TYPE%, ';
//print '%YEAR%, %MONTH%, %DAY%';	// Not supported
print '<br>';

dol_fiche_end();


llxFooter();

$db->close();
