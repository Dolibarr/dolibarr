<?php
/* Copyright (C) 2004      Rodolphe Quiedeville 	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012 Laurent Destailleur  	<eldy@users.sourceforge.org>
 * Copyright (C) 2011-2012 Juanjo Menent			<jmenent@2byte.es>
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
 *	    \file       htdocs/admin/mailing.php
 *		\ingroup    mailing
 *		\brief      Page to setup emailing module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

$langs->load("admin");
$langs->load("mails");

if (!$user->admin) accessforbidden();

$action = GETPOST('action','alpha');



/*
 * Actions
 */

if ($action == 'setMAILING_EMAIL_UNSUBSCRIBE')
{
	$res=dolibarr_set_const($db, "MAILING_EMAIL_UNSUBSCRIBE",1,'chaine',0,'',$conf->entity);
}
if ($action == 'unsetMAILING_EMAIL_UNSUBSCRIBE')
{
	$res=dolibarr_del_const($db, "MAILING_EMAIL_UNSUBSCRIBE");
}

if ($action == 'setvalue')
{
	$db->begin();

	$mailfrom = GETPOST('MAILING_EMAIL_FROM','alpha');
	$mailerror = GETPOST('MAILING_EMAIL_ERRORSTO','alpha');
	$checkread = GETPOST('value','alpha');
	$checkread_key = GETPOST('MAILING_EMAIL_UNSUBSCRIBE_KEY','alpha');

	$res=dolibarr_set_const($db, "MAILING_EMAIL_FROM",$mailfrom,'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	$res=dolibarr_set_const($db, "MAILING_EMAIL_ERRORSTO",$mailerror,'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	if ($checkread=='on')
	{
		$res=dolibarr_set_const($db, "MAILING_EMAIL_UNSUBSCRIBE",1,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
	}
	else if ($checkread=='off')
	{
		$res=dolibarr_set_const($db, "MAILING_EMAIL_UNSUBSCRIBE",0,'chaine',0,'',$conf->entity);
		if (! $res > 0) $error++;
	}

	//Create temporary encryption key if nedded
	if (($conf->global->MAILING_EMAIL_UNSUBSCRIBE==1) && (empty($checkread_key)))
	{
	    $checkread_key=getRandomPassword(true);
	}
	$res=dolibarr_set_const($db, "MAILING_EMAIL_UNSUBSCRIBE_KEY",$checkread_key,'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;

 	if (! $error)
    {
    	$db->commit();
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
    	$db->rollback();
        $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
    }
}


/*
 *	View
 */

llxHeader('',$langs->trans("MailingSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("MailingSetup"),$linkback,'setup');

$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/mailing.php";
$head[$h][1] = $langs->trans("Miscellaneous");
$head[$h][2] = 'general';
$hselected=$h;
$h++;

dol_fiche_head($head, $hselected, $langs->trans("ModuleSetup"));

dol_htmloutput_mesg($mesg);


if (! empty($conf->use_javascript_ajax))
{
	print "\n".'<script type="text/javascript">';
	print '$(document).ready(function () {
            $("#generate_token").click(function() {
            	$.get( "'.DOL_URL_ROOT.'/core/ajax/security.php", {
            		action: \'getrandompassword\',
            		generic: true
				},
				function(token) {
					$("#MAILING_EMAIL_UNSUBSCRIBE_KEY").val(token);
				});
            });
    });';
	print '</script>';
}

print '<br>';
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';

$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("MailingEMailFrom").'</td><td>';
print '<input size="32" type="text" name="MAILING_EMAIL_FROM" value="'.$conf->global->MAILING_EMAIL_FROM.'">';
if (!empty($conf->global->MAILING_EMAIL_FROM) && ! isValidEmail($conf->global->MAILING_EMAIL_FROM)) print ' '.img_warning($langs->trans("BadEMail"));
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("MailingEMailError").'</td><td>';
print '<input size="32" type="text" name="MAILING_EMAIL_ERRORSTO" value="'.$conf->global->MAILING_EMAIL_ERRORSTO.'">';
if (!empty($conf->global->MAILING_EMAIL_ERRORSTO) && ! isValidEmail($conf->global->MAILING_EMAIL_ERRORSTO)) print ' '.img_warning($langs->trans("BadEMail"));
print '</td></tr>';

// TODO the precedent values are deleted after turn on this switch
$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("ActivateCheckRead").'</td><td>';
if (!empty($conf->global->MAILING_EMAIL_UNSUBSCRIBE))
{
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=unsetMAILING_EMAIL_UNSUBSCRIBE">';
	print img_picto($langs->trans("Enabled"),'switch_on');
	print '</a>';
	$readonly='';
}
else
{
	print '<a href="'.$_SERVER["PHP_SELF"].'?action=setMAILING_EMAIL_UNSUBSCRIBE">';
	print img_picto($langs->trans("Disabled"),'switch_off');
	print '</a>';
	$readonly='disabled="disabled"';
}
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("ActivateCheckReadKey").'</td><td>';
print '<input size="32" type="text" name="MAILING_EMAIL_UNSUBSCRIBE_KEY" id="MAILING_EMAIL_UNSUBSCRIBE_KEY" '.$readonly.' value="'.$conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY.'">';
if (! empty($conf->use_javascript_ajax))
	print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
print '</td></tr>';

print '</table>';

print '<br>';
print '<div align="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></div>';

print '</form>';

llxFooter();

$db->close();
?>
