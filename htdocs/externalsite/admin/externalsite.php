<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
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
 *    \file       htdocs/externalsite/admin/externalsite.php
 *    \ingroup    externalsite
 *    \brief      Page de configuration du module externalsite
 *    \version    $Id$
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");


if (!$user->admin)
    accessforbidden();


$langs->load("admin");
$langs->load("other");
$langs->load("@externalsite");

$def = array();

// Sauvegardes parametres
if ($_POST["action"] == 'update')
{
    $i=0;

    $db->begin();

    $i+=dolibarr_set_const($db,'EXTERNALSITE_URL',trim($_POST["EXTERNALSITE_URL"]),'chaine',0,'',$conf->entity);

    if ($i >= 1)
    {
        $db->commit();
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
        $db->rollback();
        $mesg=$db->lasterror();
    }
}


/**
 * View
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("ExternalSiteSetup"),$linkback,'setup');
print '<br>';


print '<form name="externalsiteconfig" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="update">';
print "<table class=\"noborder\" width=\"100%\">";

print "<tr class=\"liste_titre\">";
print "<td width=\"30%\">".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "<td>".$langs->trans("Examples")."</td>";
print "</tr>";

print "<tr class=\"impair\">";
print "<td>".$langs->trans("ExternalSiteURL")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"EXTERNALSITE_URL\" value=\"". ($_POST["EXTERNALSITE_URL"]?$_POST["EXTERNALSITE_URL"]:$conf->global->EXTERNALSITE_URL) . "\" size=\"40\"></td>";
print "<td>http://localhost/myurl/";
print "<br>http://wikipedia.org/";
print "</td>";
print "</tr>";

print "</table>";


print '<br><center>';
print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</center>";

print "</form>\n";


if ($mesg) print "<br>$mesg<br>";

$db->close();

llxFooter('$Date$ - $Revision$');
?>
