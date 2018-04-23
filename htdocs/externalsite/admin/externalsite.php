<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2011-2012 Juanjo Menent		<jmenent@2byte.es>
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
 *    \file       htdocs/externalsite/admin/externalsite.php
 *    \ingroup    externalsite
 *    \brief      Page de configuration du module externalsite
 */

if (! defined('NOSCANPOSTFORINJECTION')) define('NOSCANPOSTFORINJECTION','1');		// Do not check anti CSRF attack test

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';


if (!$user->admin)
    accessforbidden();


$langs->load("admin");
$langs->load("other");
$langs->load("externalsite");

$def = array();

$action = GETPOST('action','alpha');

// Sauvegardes parametres
if ($action == 'update')
{
    $i=0;

    $db->begin();

	$label  = GETPOST('EXTERNALSITE_LABEL','alpha');
    $exturl = GETPOST('EXTERNALSITE_URL','none');

    $i+=dolibarr_set_const($db,'EXTERNALSITE_LABEL',trim($label),'chaine',0,'',$conf->entity);
    $i+=dolibarr_set_const($db,'EXTERNALSITE_URL',trim($exturl),'chaine',0,'',$conf->entity);

    if ($i >= 2)
    {
        $db->commit();
	    setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        $db->rollback();
	    setEventMessages($db->lasterror(), null, 'errors');
    }
}


/**
 * View
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ExternalSiteSetup"),$linkback,'title_setup');
print '<br>';

print $langs->trans("Module100Desc")."<br>\n";
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

$var=true;


print '<tr class="oddeven">';
print '<td class="fieldrequired">'.$langs->trans("Label")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"EXTERNALSITE_LABEL\" value=\"". (GETPOST('EXTERNALSITE_LABEL','alpha')?GETPOST('EXTERNALSITE_LABEL','alpha'):((empty($conf->global->EXTERNALSITE_LABEL) || $conf->global->EXTERNALSITE_LABEL=='ExternalSite')?'':$conf->global->EXTERNALSITE_LABEL)) . "\" size=\"12\"></td>";
print "<td>".$langs->trans("ExampleMyMenuEntry")."</td>";
print "</tr>";


print '<tr class="oddeven">';
print '<td class="fieldrequired">'.$langs->trans("ExternalSiteURL")."</td>";
print '<td><textarea class="flat minwidth500" name="EXTERNALSITE_URL">';
print (GETPOST('EXTERNALSITE_URL','none')?GETPOST('EXTERNALSITE_URL','none'):(empty($conf->global->EXTERNALSITE_URL)?'':$conf->global->EXTERNALSITE_URL));
print '</textarea></td>';
print "<td>http://localhost/myurl/";
print "<br>https://wikipedia.org/";
print "<br>&lt;iframe&gt;...&lt;/iframe&gt;";
print "</td>";
print "</tr>";

print "</table>";


print '<br><div class="center">';
print '<input type="submit" name="save" class="button" value="'.$langs->trans("Save").'">';
print '</div>';

print "</form>\n";

llxFooter();

$db->close();
