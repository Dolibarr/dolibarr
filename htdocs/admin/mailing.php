<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.org>
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
 *		\version    $Id: mailing.php,v 1.14 2011/07/31 22:23:25 eldy Exp $
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();


/*
 * Actions
 */

if ($_POST["action"] == 'setvalue' && $user->admin)
{
	$result1=dolibarr_set_const($db, "MAILING_EMAIL_FROM",$_POST["MAILING_EMAIL_FROM"],'chaine',0,'',$conf->entity);
	$result2=dolibarr_set_const($db, "MAILING_EMAIL_ERRORSTO",$_POST["MAILING_EMAIL_ERRORSTO"],'chaine',0,'',$conf->entity);
	if (($result1 + $result2) == 2)
  	{
  		$mesg='<div class="ok">'.$langs->trans("Success").'</div>';
  	}
  	else
  	{
		dol_print_error($db);
    }
}



/*
 *	View
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("MailingSetup"),$linkback,'setup');

if ($mesg) print '<br>'.$mesg;

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
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'><td>';
print $langs->trans("MailingEMailError").'</td><td>';
print '<input size="32" type="text" name="MAILING_EMAIL_ERRORSTO" value="'.$conf->global->MAILING_EMAIL_ERRORSTO.'">';
print '</td></tr>';

print '<tr><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table></form>';


$db->close();


llxFooter('$Date: 2011/07/31 22:23:25 $ - $Revision: 1.14 $');

?>
