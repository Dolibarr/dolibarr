<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];

if (!$user->rights->telephonie->lire)
  accessforbidden();


$upload_dir = DOL_DATA_ROOT."/telephonie/ligne/commande/retour/traite";

llxHeader('','Telephonie - Ligne - Commande - Retour - Fichiers');

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

/*
 * Mode Liste
 *
 *
 *
 */


print_titre("20 derniers Fichiers traités");

$upload_dir = $upload_dir."/";
$handle=opendir($upload_dir);

$files = array();
$i = 0;
while (($file = readdir($handle))!==false)
{
  if (is_readable($upload_dir.$file) && is_file($upload_dir.$file))
    {
      $files[$i][0] = $file;
      $files[$i][1] = filesize($upload_dir.$file); 
      $files[$i][2] = filemtime($upload_dir.$file);
      $i++;
    }
}


print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td>Fichier</td><td>Taille</td><td>Date</td>';
print "</tr>\n";
$var=True;

sort($files, 2);

$n = min(20, sizeof($files));

for ($i = 0 ; $i < $n ; $i++)
{
  $var=!$var;

  print "<tr $bc[$var]>";
  print '<td><a href="'.DOL_URL_ROOT.'/document.php?file='.$upload_dir.$files[$i][0].'&amp;type=text/plain">';
  print $files[$i][0].'</a></td>';                       
  
  print '<td>'.$files[$i][1]. ' bytes</td>';
  print '<td>'.strftime("%A %d %b %Y %H:%M:%S", $files[$i][2]).'</td>';      
  print '</tr>';
}






print "</table>";


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
