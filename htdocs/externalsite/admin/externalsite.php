<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2011 	   Juanjo Menent		<jmenent@2byte.es>
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
 *    \file       htdocs/externalsite/admin/externalsite.php
 *    \ingroup    externalsite
 *    \brief      Page de configuration du module externalsite
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");


if (!$user->admin)
    accessforbidden();


$langs->load("admin");
$langs->load("other");
$langs->load("externalsite");

$def = array();

$action = GETPOST("action");

// Sauvegardes parametres
if ($action == 'update')
{
    $i=0;

    $db->begin();
    
    $exturl = GETPOST("EXTERNALSITE_URL");

    $i+=dolibarr_set_const($db,'EXTERNALSITE_URL',trim($exturl),'chaine',0,'',$conf->entity);
    //$i+=dolibarr_set_const($db,'EXTERNALSITE_LABEL',trim($_POST["EXTERNALSITE_LABEL"]),'chaine',0,'',$conf->entity);

    if ($i >= 1)
    {
        $db->commit();
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
        $db->rollback();
        $mesg="<div class=\"error\">".$db->lasterror()."</div>";
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

/*print "<tr class=\"impair\">";
print "<td>".$langs->trans("Label")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"EXTERNALSITE_LABEL\" value=\"". ($_POST["EXTERNALSITE_LABEL"]?$_POST["EXTERNALSITE_LABEL"]:$conf->global->EXTERNALSITE_LABEL) . "\" size=\"40\"></td>";
print "<td>My menu";
print "</td>";
print "</tr>";
*/

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


dol_htmloutput_mesg($mesg);

$db->close();

llxFooter();
?>
