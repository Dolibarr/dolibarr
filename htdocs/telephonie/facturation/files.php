<?PHP
/* Copyright (C) 2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

$dir = $conf->telephonie->dir_output."/cdr/atraiter/" ;

$handle=opendir($dir);

$files = array();

$var=true;
while (($file = readdir($handle))!==false)
{
  if (is_file($dir.'/'.$file))
    array_push($files, $file);
}
closedir($handle);



if (!$user->rights->telephonie->facture->lire) accessforbidden();

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

if ($sortorder == "") {
  $sortorder="";
}
if ($sortfield == "") {
  $sortfield="f.date DESC, f.gain ASC";
}

/*
 * Recherche
 *
 *
 */

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

/*
 * Mode Liste
 *
 *
 *
 */

print_barre_liste("Fichiers CDR a traiter", $page, "files.php", "", $sortfield, $sortorder, '', $num);

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre">';
print '<td>Fichier</td><td>Date</td>';
print '<td align="right">Taille</td>';
print "</tr>\n";

$var=True;

foreach ($files as $file)
{
  $obj = $db->fetch_object($i);	
  $var=!$var;
  
  print "<tr $bc[$var]>";
  
  print '<td>';
  print img_file();      
  print '&nbsp;';
  print $file."</td>\n";
  
  print '<td>'.date("d F Y H:i:s", filemtime($dir.$file)).'</td>';
  print '<td align="right">'.filesize($dir.$file).' octets</td>';
}
print "</table>";


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
