<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011-2012 Juanjo Menent		<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/member.lib.php");

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
	$constvalue=(GETPOST('constvalue_'.$constname,'alpha') ? GETPOST('constvalue_'.$constname,'alpha') : GETPOST('constvalue','alpha'));

	if (($constname=='ADHERENT_CARD_TYPE' || $constname=='ADHERENT_ETIQUETTE_TYPE') && $constvalue == -1) $constvalue='';
	if ($constname=='ADHERENT_LOGIN_NOT_REQUIRED') // Invert choice
	{
		if ($constvalue) $constvalue=0;
		else $constvalue=1;
	}

	$consttype=GETPOST('consttype','alpha');
	$constnote=GETPOST('constnote','alpha');
	$res=dolibarr_set_const($db,$constname,$constvalue,$type[$consttype],0,$constnote,$conf->entity);

	if (! $res > 0) $error++;

	if (! $error)
	{
		$mesg = '<div class="ok">'.$langs->trans("SetupSaved").'</div>';
	}
	else
	{
		$mesg = '<div class="error">'.$langs->trans("Error").'</div>';
	}
}

// Action activation d'un sous module du module adherent
if ($action == 'set')
{
    $result=dolibarr_set_const($db, GETPOST('name','alpha'),GETPOST('value','alpha'),'',0,'',$conf->entity);
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

$help_url='EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros';

llxHeader('',$langs->trans("MembersSetup"),$help_url);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("MembersSetup"),$linkback,'setup');


$head = member_admin_prepare_head($adh);

dol_fiche_head($head, 'general', $langs->trans("Member"), 0, 'user');


dol_htmloutput_mesg($mesg);


print_fiche_titre($langs->trans("MemberMainOptions"),'','');
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td align="center">'.$langs->trans("Action").'</td>';
print "</tr>\n";
$var=true;
$form = new Form($db);

// Login/Pass required for members
if ($conf->global->MAIN_FEATURES_LEVEL > 0)
{
    $var=!$var;
    print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="rowid" value="'.$rowid.'">';
    print '<input type="hidden" name="constname" value="ADHERENT_LOGIN_NOT_REQUIRED">';
    print '<tr '.$bc[$var].'><td>'.$langs->trans("AdherentLoginRequired").'</td><td>';
    print $form->selectyesno('constvalue',!$conf->global->ADHERENT_LOGIN_NOT_REQUIRED,1);
    print '</td><td align="center" width="80">';
    print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
    print "</td></tr>\n";
    print '</form>';
}

// Mail required for members
$var=!$var;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="rowid" value="'.$rowid.'">';
print '<input type="hidden" name="constname" value="ADHERENT_MAIL_REQUIRED">';
print '<tr '.$bc[$var].'><td>'.$langs->trans("AdherentMailRequired").'</td><td>';
print $form->selectyesno('constvalue',$conf->global->ADHERENT_MAIL_REQUIRED,1);
print '</td><td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print "</td></tr>\n";
print '</form>';

// Send mail information is on by default
$var=!$var;
print '<form action="adherent.php" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="rowid" value="'.$rowid.'">';
print '<input type="hidden" name="constname" value="ADHERENT_DEFAULT_SENDINFOBYMAIL">';
print '<tr '.$bc[$var].'><td>'.$langs->trans("MemberSendInformationByMailByDefault").'</td><td>';
print $form->selectyesno('constvalue',$conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL,1);
print '</td><td align="center" width="80">';
print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
print "</td></tr>\n";
print '</form>';

// Insertion cotisations dans compte financier
$var=!$var;
print '<form action="adherent.php" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="rowid" value="'.$rowid.'">';
print '<input type="hidden" name="constname" value="ADHERENT_BANK_USE">';
print '<tr '.$bc[$var].'><td>'.$langs->trans("AddSubscriptionIntoAccount").'</td>';
if ($conf->banque->enabled)
{
    print '<td>';
    print $form->selectyesno('constvalue',$conf->global->ADHERENT_BANK_USE,1);
    print '</td><td align="center" width="80">';
    print '<input type="submit" class="button" value="'.$langs->trans("Update").'" name="Button">';
    print '</td>';
}
else
{
    print '<td align="right" colspan="2">';
    print $langs->trans("WarningModuleNotActive",$langs->transnoentities("Module85Name")).' '.img_warning("","");
    print '</td>';
}
print "</tr>\n";
print '</form>';
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

print_fiche_titre($langs->trans("MembersCards"),'','');

form_constantes($constantes);

print '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
print '%DOL_MAIN_URL_ROOT%, %ID%, %FIRSTNAME%, %LASTNAME%, %FULLNAME%, %LOGIN%, %PASSWORD%, ';
print '%COMPANY%, %ADDRESS%, %ZIP%, %TOWN%, %COUNTRY%, %EMAIL%, %NAISS%, %PHOTO%, %TYPE%, ';
print '%YEAR%, %MONTH%, %DAY%';
print '<br>';

print '<br>';


/*
 * Edition info modele document
 */
$constantes=array('ADHERENT_ETIQUETTE_TYPE','ADHERENT_ETIQUETTE_TEXT');

print_fiche_titre($langs->trans("MembersTickets"),'','');

form_constantes($constantes);

print '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
print '%DOL_MAIN_URL_ROOT%, %ID%, %FIRSTNAME%, %LASTNAME%, %FULLNAME%, %LOGIN%, %PASSWORD%, ';
print '%COMPANY%, %ADDRESS%, %ZIP%, %TOWN%, %COUNTRY%, %EMAIL%, %NAISS%, %PHOTO%, %TYPE%, ';
print '%YEAR%, %MONTH%, %DAY%';
print '<br>';

print '<br>';


/*
 * Edition des variables globales non rattache a un theme specifique
 */
$constantes=array(
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

print_fiche_titre($langs->trans("Other"),'','');

form_constantes($constantes);

print '*'.$langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
print '%DOL_MAIN_URL_ROOT%, %ID%, %FIRSTNAME%, %LASTNAME%, %FULLNAME%, %LOGIN%, %PASSWORD%, ';
print '%COMPANY%, %ADDRESS%, %ZIP%, %TOWN%, %COUNTRY%, %EMAIL%, %NAISS%, %PHOTO%, %TYPE%, ';
//print '%YEAR%, %MONTH%, %DAY%';	// Not supported
print '<br>';

dol_fiche_end();


llxFooter();

$db->close();
?>