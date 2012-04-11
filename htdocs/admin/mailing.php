<?php
/* Copyright (C) 2004      Rodolphe Quiedeville 	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2011 Laurent Destailleur  	<eldy@users.sourceforge.org>
 * Copyright (C) 2011-2012 Juanjo Menent			<jmenent@2byte.es>
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
 *	    \file       htdocs/admin/mailing.php
 *		\ingroup    mailing
 *		\brief      Page to setup emailing module
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

$langs->load("admin");
$langs->load("mails");

if (!$user->admin)
  accessforbidden();

$action = GETPOST('action','alpha');

/*
 * Actions
 */

if ($action == 'setvalue' && $user->admin)
{
	$db->begin();
	
	$mailfrom = GETPOST('MAILING_EMAIL_FROM','alpha');
	$mailerror = GETPOST('MAILING_EMAIL_ERRORSTO','alpha');
	
	$res=dolibarr_set_const($db, "MAILING_EMAIL_FROM",$mailfrom,'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	$res=dolibarr_set_const($db, "MAILING_EMAIL_ERRORSTO",$mailerror,'chaine',0,'',$conf->entity);
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

dol_htmloutput_mesg($mesg);

print '<br>';
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="setvalue">';

$var=true;

print '<table class="nobordernopadding" width="100%">';
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

print '<tr><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table></form>';

$db->close();

llxFooter();

?>
