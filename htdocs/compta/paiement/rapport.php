<?PHP
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php");
/*
 * Sécurité accés
 */
if ($user->societe_id > 0) 
{
  block_access();
  exit;
}

require("../../includes/modules/rapport/pdf_paiement.class.php");

$dir = DOL_DOCUMENT_ROOT."/document/rapport/";

if ($_POST["action"] == 'gen')
{
  $rap = new pdf_paiement($db);
  $rap->write_pdf_file($dir, $_POST["remonth"], $_POST["reyear"]);
  
  $year = $_POST["reyear"];
}

llxHeader();


/*
 *
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
      print "<option value=\"$month\" SELECTED>" . $strmonth[$month];
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
      print "<option value=\"$formyear\" SELECTED>$formyear";
    }
  else
    {
      print "<option value=\"$formyear\">$formyear";
    }
}
print "</select>\n";
print '<input type="submit" value="Générer">';
print '</form>';

clearstatcache();

$handle=opendir($dir);

while (($file = readdir($handle))!==false)
{
  if (is_dir($dir.$file) && substr($file, 0, 1) <> '.')
    {
      print '<a href="index.php?year='.$file.'">'.$file.'</a> ';
    }
}

if ($year)
{
  $handle=opendir($dir.'/'.$year);
  
  print '<table width="100%" id="wiborder">';
  print '<tr><td>Rapport</td><td>Taille</td><td>Date de génération</td></tr>';
  while (($file = readdir($handle))!==false)
    {
      if (substr($file, 0, 8) == 'paiement')
	{
	  $tfile = $dir . '/'.$year.'/'.$file;
	  print '<tr><td><a href="'.DOL_URL_ROOT.'/document/rapport/'.$year.'/'.$file.'">'.$file.'</a></td>';
	  print '<td align="right">'.filesize($tfile). ' bytes</td>';
	  print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($tfile)).'</td></tr>';
	}
    }
  print '</table>';
}
$db->close();
 
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
