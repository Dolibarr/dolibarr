<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 *
 */
 
/**     \file       htdocs/compta/paiement/fiche.php
		\ingroup    facture
		\brief      Onglet paiement d'un paiement
		\version    $Revision$
*/

require("./pre.inc.php");


/*
 * Sécurité accés
 */
if (!$user->admin && $user->societe_id > 0)
  accessforbidden();



$year = $_GET["year"];

require("../../includes/modules/rapport/pdf_paiement.class.php");

$dir = $conf->compta->dir_output;


/*
 * Action générer fichier rapport paiements
 */
if ($_POST["action"] == 'gen')
{
  $rap = new pdf_paiement($db);
  $rap->write_pdf_file($dir, $_POST["remonth"], $_POST["reyear"]);
  
  $year = $_POST["reyear"];
}

llxHeader();


/*
 * Affichage liste des paiements
 *
 */
print_titre("Rapport paiements");

print '<form method="post" action="rapport.php?year='.$year.'">';
print '<input type="hidden" name="action" value="gen">';
$cmonth = date("n", time());
$syear = date("Y", time());
    
$strmonth[1] = "Janvier";
$strmonth[2] = "F&eacute;vrier";
$strmonth[3] = "Mars";
$strmonth[4] = "Avril";
$strmonth[5] = "Mai";
$strmonth[6] = "Juin";
$strmonth[7] = "Juillet";
$strmonth[8] = "Ao&ucirc;t";
$strmonth[9] = "Septembre";
$strmonth[10] = "Octobre";
$strmonth[11] = "Novembre";
$strmonth[12] = "D&eacute;cembre";

print '<select name="remonth">';    
for ($month = 1 ; $month < 13 ; $month++)
{
  if ($month == $cmonth)
    {
      print "<option value=\"$month\" selected>" . $strmonth[$month];
    }
  else
    {
      print "<option value=\"$month\">" . $strmonth[$month];
    }
}
print "</select>";
    
print '<select name="reyear">';

for ($formyear = $syear - 2; $formyear < $syear +1 ; $formyear++)
{
  if ($formyear == $syear)
    {
      print "<option value=\"$formyear\" selected>$formyear";
    }
  else
    {
      print "<option value=\"$formyear\">$formyear";
    }
}
print "</select>\n";
print '<input type="submit" value="'.$langs->trans("Create").'">';
print '</form>';

clearstatcache();

$handle=opendir($dir);

while (($file = readdir($handle))!==false)
{
  if (is_dir($dir.$file) && substr($file, 0, 1) <> '.')
    {
      print '<a href="rapport.php?year='.$file.'">'.$file.'</a> ';
    }
}

if ($year)
{
  $handle=opendir($dir.'/'.$year);
  print '<br>';
  print '<table width="100%" class="noborder">';
  print '<tr class="liste_titre"><td>Rapport</td><td align="right">'.$langs->trans("Size").'</td><td align="right">'.$langs->trans("Date").'</td></tr>';
  $var=true;
  while (($file = readdir($handle))!==false)
    {
      if (substr($file, 0, 8) == 'paiement')
	{
	  $var=!$var;
	  $tfile = $dir . '/'.$year.'/'.$file;
	  $relativepath = $year.'/'.$file;
	  print "<tr $bc[$var]>".'<td><a href="/document.php?modulepart=facture_paiement&file='.urlencode($relativepath).'">'.img_pdf().' '.$file.'</a></td>';
	  print '<td align="right">'.filesize($tfile). ' bytes</td>';
	  print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($tfile)).'</td></tr>';
	}
    }
  print '</table>';
}
$db->close();
 
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
