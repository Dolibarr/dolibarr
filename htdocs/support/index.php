<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file      htdocs/install/phpinfo.php
 *       \ingroup   install
 *       \brief     Provide an Online Help support
 *       \version   $Id$
 */

// Use its own include to not share the include of Dolibarr
// This is a standalone feature with no information from Dolibarr to show
// and no database access to do.
include_once("./inc.php");
$uri=eregi_replace('^http(s?)://','',$dolibarr_main_url_root);
$pos = strstr ($uri, '/');      // $pos contient alors url sans nom domaine
if ($pos == '/') $pos = '';     // si $pos vaut /, on le met a ''
define('DOL_URL_ROOT', $pos);	// URL racine relative

$langs->load("other");
$langs->load("help");


pHeader($langs->trans("DolibarrHelpCenter"),$_SERVER["PHP_SELF"]);

print $langs->trans("HelpCenterDesc1")."<br>\n";
print $langs->trans("HelpCenterDesc2")."<br>\n";

print '<br>';

print $langs->trans("ToGoBackToDolibarr",DOL_URL_ROOT.'/');

print '<br><br>';

$style1='color: #333344; font-size: 18px; font-weight: bold';
$style2='color: #5D4455; font-weight: bold;';


print '<table border="1" style="spacing: 4px; padding: 2px">';

// Line of possible services
print '<tr class="title">';
// Forum/wiki support
print '<td align="center" valign="middle">';
//print img_picto('','/theme/common/who.png','',1).'<br>';
print '<font style="'.$style1.'">'.$langs->trans("CommunitySupport").'</font>';
print '<br>'.$langs->trans("TypeOfSupport").': <font style="'.$style2.'">'.$langs->trans("TypeSupportCommunauty").'</font>';
print '</td>';
// EMail support
print '<td align="center">';
//print img_picto('','/theme/common/mail.png','',1).'<br>';
print '<font style="'.$style1.'">'.$langs->trans("EMailSupport").'</font>';
print '<br>'.$langs->trans("TypeOfSupport").': <font style="'.$style2.'">'.$langs->trans("TypeSupportCommercial").'</font>';
print '</td>';
print '</tr>';

// Area of support cells
print '<tr>';

// Forum/wiki support
print '<td width="33%" align="center" valign="top" style="background:#FFFFFF">';
print '<table class="nocellnopadd"><tr><td align="center" valign="top">';
print img_picto('','/theme/common/who.png','',1);
print '</td></tr><tr><td align="center">';
$urlwiki='http://wiki.dolibarr.org';
if ($langs->defaultlang == 'fr_FR') $urlwiki='http://wiki.dolibarr.org/index.php/Accueil';
if ($langs->defaultlang == 'es_ES') $urlwiki='http://wiki.dolibarr.org/index.php/Portada';
print '<br>'.$langs->trans("ForDocumentationSeeWiki",$urlwiki,$urlwiki).'<br>';
print '<br>'.$langs->trans("ForAnswersSeeForum",'http://www.dolibarr.org/forum/','http://www.dolibarr.org/forum/').'<br>';
print '<br>';
print '</td></tr></table>';
print '</td>';

// EMail support
print '<td width="34%" align="center" valign="top" style="background:#FFFFFF">';
print '<table class="nocellnopadd"><tr><td align="center" valign="top">';
print img_picto('','/theme/common/mail.png','',1);
print '</td></tr><tr><td align="center">';
print '<br>'.$langs->trans("FeatureNotYetAvailable").'.';
print '</td></tr></table>';
print '</td>';

print '</tr>';


// Line of possible services
print '<tr class="title">';
// Forum/wiki support
print '<td align="center">';
print '<font style="'.$style1.'">'.$langs->trans("RemoteControlSupport").'</font>';
print '<br>'.$langs->trans("TypeOfSupport").': <font style="'.$style2.'">'.$langs->trans("TypeSupportCommercial").'</font>';
print '</td>';
// EMail support
print '<td align="center">';
print '<font style="'.$style1.'">'.$langs->trans("OtherSupport").'</font>';
print '<br>'.$langs->trans("TypeOfSupport").': <font style="'.$style2.'">'.$langs->trans("TypeSupportCommercial").'</font>';
print '</td>';
print '</tr>';

// Area of support cells
print '<tr>';

// Online support
print '<td width="33%" align="center" valign="top" style="background:#FFFFFF">';
print '<table class="nocellnopadd"><tr><td align="center" valign="top">';
print img_picto('','/theme/common/internet.png','',1);
print '</td></tr><tr><td align="center">';
print '<br>';
print '<br>'.$langs->trans("ToSeeListOfAvailableRessources").'<br>';
print '<b><a href="online.php">'.$langs->trans("ClickHere").'</a></b>';
print '<br><br>';
print '<br><br>';
print '</td></tr></table>';
print '</td>';

// Other support
$urlwiki='http://wiki.dolibarr.org/index.php/List_of_OpenSource_Software_companies_and_freelancers';
print '<td width="33%" align="center" valign="top" style="background:#FFFFFF">';
print '<table class="nocellnopadd"><tr><td align="center" valign="top">';
print img_picto('','/theme/common/pagemaster.png','',1);
print '</td></tr><tr><td align="center">';
print '<br>';
print '<br>'.$langs->trans("ToSeeListOfAvailableRessources").'<br>';
print '<b><a href="'.$urlwiki.'">'.$langs->trans("ClickHere").'</a></b>';
print '</td></tr></table>';
print '</td>';

print '</tr>';


print '</table>';

pFooter();
?>
