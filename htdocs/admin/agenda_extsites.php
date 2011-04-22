<?php
/* Copyright (C) 2011 Juanjo Menent  <jmenent@2byte.es>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	    \file       htdocs/admin/agenda_extsites.php
 *      \ingroup    agenda
 *      \brief      Page to setup external calendars for agenda module
 *      \version    $Id$
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/agenda.lib.php");

if (!$user->admin)
    accessforbidden();

$langs->load("admin");
$langs->load("other");
$langs->load("agenda");

$action=$_POST["action"];

/*
*	Actions
*/


/*
 * View
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("AgendaSetup"),$linkback,'setup');
print "<br>\n";

print $langs->trans("AgendaExtSitesDesc")."<br>\n";
print "<br>\n";

$head=agenda_prepare_head();

dol_fiche_head($head, 'extsites', $langs->trans("Agenda"));

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="save">';

$var=true;

print "</form>\n";

if ($mesg) print "<br>$mesg<br>";
print "<br>";

llxFooter('$Date$ - $Revision$');
?>
