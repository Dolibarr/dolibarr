<?php
/* Copyright (C) 2008-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file      htdocs/support/index.php
 *       \ingroup   support
 *       \brief     Provide an Online Help support
 */


// Use its own include to not share the include of Dolibarr
// This is a standalone feature with no information from Dolibarr to show
// and no database access to do.
include_once("./inc.php");
$uri=preg_replace('/^http(s?):\/\//i','',$dolibarr_main_url_root);
$pos = strstr($uri, '/');      // $pos contient alors url sans nom domaine
if ($pos == '/') $pos = '';     // si $pos vaut /, on le met a ''
define('DOL_URL_ROOT', $pos);	// URL racine relative

$langs->load("other");
$langs->load("help");


/*
 * View
 */

pHeader($langs->trans("DolibarrHelpCenter").' '.DOL_VERSION, $_SERVER["PHP_SELF"]);

print $langs->trans("HelpCenterDesc1")."<br>\n";
print $langs->trans("HelpCenterDesc2")."<br>\n";

print '<br>';

print $langs->trans("ToGoBackToDolibarr",DOL_URL_ROOT.'/');
//print '<img src="dolibarr_logo2.png" height="22" alt="Dolibarr" title="Dolibarr">';

print '<br><br>';

$style1='color: #333344; font-size: 16px; font-weight: bold';
$style2='color: #5D4455; font-weight: bold;';

print "\n";
print '<table border="0" style="spacing: 4px; padding: 0px" width="100%">';
print '<tr><td width="50%" valign="top">';
print "\n";

// Forum/wiki support
print '<table class="login" width="100%">';
print '<tr class="title" valign="top">';
print '<td width="100%" align="left" valign="top">';

print '<table summary="who"><tr><td>'.img_picto('','who.png','',1).'</td><td>';

print '<font style="'.$style1.'">'.$langs->trans("CommunitySupport").'</font>';
print '<br>'.$langs->trans("TypeOfSupport").': <font style="'.$style2.'">'.$langs->trans("TypeSupportCommunauty").'</font>';
print '<br>'.$langs->trans("TypeOfHelp").'/'.$langs->trans("Efficiency").'/'.$langs->trans("Price").': ';
print $langs->trans("TypeHelpDev").'/'.img_picto_common('','redstar','',1).img_picto_common('','redstar','',1).'/'.img_picto_common('','star','',1).img_picto_common('','star','',1).img_picto_common('','star','',1).img_picto_common('','star','',1);

print '</td></tr></table>';

print '</td>';
print '</tr>';

print '<tr>';
print '<td align="center" valign="top">';
print '<table class="nocellnopadd">';
/*print '<tr><td align="center" valign="top">';
print img_picto_common('','who.png','',1);
print '</td></tr>';*/
print '<tr><td align="center">';
$urlwiki='http://wiki.dolibarr.org';
if (preg_match('/fr/i',$langs->defaultlang)) $urlwiki='http://wiki.dolibarr.org/index.php/Accueil';
if (preg_match('/es/i',$langs->defaultlang)) $urlwiki='http://wiki.dolibarr.org/index.php/Portada';
print '<br>'.$langs->trans("ForDocumentationSeeWiki",$urlwiki,$urlwiki);
print '<br>';
$urlforum='http://www.dolibarr.org/forum/';
if (preg_match('/fr/i',$langs->defaultlang)) $urlforum='http://www.dolibarr.fr/forum/';
print '<br>'.$langs->trans("ForAnswersSeeForum",$urlforum,$urlforum).'<br>';
print '</td></tr></table>';
print '</td>';
print '</tr>';
print '</table>'."\n";
print "\n";


print '</td><td width="50%" valign="top">'."\n";
print "\n";


// Online support
print '<table class="login" width="100%">';
print '<tr class="title">';
print '<td width="100%" align="left" valign="top">';

print '<table summary="community"><tr><td>'.img_picto('','internet.png','',1).'</td><td>';

print '<font style="'.$style1.'">'.$langs->trans("RemoteControlSupport").'</font>';
print '<br>'.$langs->trans("TypeOfSupport").': <font style="'.$style2.'">'.$langs->trans("TypeSupportCommercial").'</font>';
print '<br>'.$langs->trans("TypeOfHelp").'/'.$langs->trans("Efficiency").'/'.$langs->trans("Price").': ';
print $langs->trans("TypeHelpOnly").'/'.img_picto_common('','redstar','',1).img_picto_common('','redstar','',1).img_picto_common('','redstar','',1).img_picto_common('','redstar','',1).'/'.img_picto_common('','star','',1).img_picto_common('','star','',1);

print '</td></tr></table>';

print '</td>';
print '</tr><tr>';
print '<td align="center" valign="top">';
print '<table class="nocellnopadd">';
/*print '<tr><td align="center" valign="top">';
print img_picto_common('','internet.png','',1);
print '</td></tr>';*/
print '<tr><td align="center">';
print '<br>'.$langs->trans("ToSeeListOfAvailableRessources").'<br>';
print '<b><a href="online.php">'.$langs->trans("ClickHere").'</a></b><br>';
print '<br><br>';
print '<br><br>';
print '</td></tr></table>';
print '</td>';
print '</tr>';
print '</table>'."\n";

print '</td></tr>';
print '<tr><td width="50%" valign="top">'."\n";
print "\n";

// EMail support
print '<table class="login" width="100%">';
print '<tr class="title" valign="top">';
print '<td width="100%" align="left" valign="top">';

print '<table summary="mail"><tr><td>'.img_picto('','mail.png','',1).'</td><td>';

print '<font style="'.$style1.'">'.$langs->trans("EMailSupport").'</font>';
print '<br>'.$langs->trans("TypeOfSupport").': <font style="'.$style2.'">'.$langs->trans("TypeSupportCommercial").'</font>';
print '<br>'.$langs->trans("TypeOfHelp").'/'.$langs->trans("Efficiency").'/'.$langs->trans("Price").': ';
print $langs->trans("TypeHelpOnly").'/'.img_picto_common('','redstar','',1).img_picto_common('','redstar','',1).img_picto_common('','redstar','',1).'/'.img_picto_common('','star','',1).img_picto_common('','star','',1);

print '</td></tr></table>';

print '</td>';
print '</tr><tr>';
$urlwiki='http://wiki.dolibarr.org/index.php/List of Dolibarr partners and providers';
print '<td align="center" valign="top">';
print '<table class="nocellnopadd">';
/*print '<tr><td align="center" valign="top">';
print img_picto_common('','mail.png','',1);
print '</td></tr>';*/
print '<tr><td align="center">';
print '<br>'.$langs->trans("ToSeeListOfAvailableRessources").'<br>';
print '<b><a href="'.$urlwiki.'">'.$langs->trans("ClickHere").'</a></b><br>';
print '<br><br>';
print '<br><br>';
print '</td></tr></table>';
print '</td>';
print '</tr>';
print '</table>'."\n";

print '</td><td width="50%" valign="top">'."\n";
print "\n";

// Other support
print '<table class="login" width="100%">';
print '<tr class="title">';
print '<td width="100%" align="left" valign="top">';

print '<table summary="special"><tr><td>'.img_picto('','pagemaster.png','',1).'</td><td>';

print '<font style="'.$style1.'">'.$langs->trans("OtherSupport").'</font>';
print '<br>'.$langs->trans("TypeOfSupport").': <font style="'.$style2.'">'.$langs->trans("TypeSupportCommercial").'</font>';
//print '<br>'.$langs->trans("Efficiency").'/'.$langs->trans("Price").': '.img_picto_common('','redstar').img_picto_common('','redstar').img_picto_common('','redstar').' / '.img_picto_common('','star');
print '<br>'.$langs->trans("TypeOfHelp").'/'.$langs->trans("Efficiency").'/'.$langs->trans("Price").': ';
print $langs->trans("TypeHelpDevForm").'/?/?';

print '</td></tr></table>';

print '</td>';
print '</tr><tr>';
$urlwiki='http://wiki.dolibarr.org/index.php/List of Dolibarr partners and providers';
print '<td align="center" valign="top">';
print '<table class="nocellnopadd">';
/*print '<tr><td align="center" valign="top">';
print img_picto_common('','pagemaster.png','',1);
print '</td></tr>';*/
print '<tr><td align="center">';
print '<br>'.$langs->trans("ToSeeListOfAvailableRessources").'<br>';
print '<b><a href="'.$urlwiki.'">'.$langs->trans("ClickHere").'</a></b><br>';
print '<br><br>';
print '<br><br>';
print '</td></tr></table>';
print '</td>';
print '</tr>';
print '</table>'."\n";
print "\n";

print '</td>';
print '</tr>';
print '</table>';

pFooter();
?>
