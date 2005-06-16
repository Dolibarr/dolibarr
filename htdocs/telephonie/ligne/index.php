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

llxHeader('','Telephonie - Ligne');

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

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td width="30%" valign="top">';

print '<form method="GET" action="'.DOL_URL_ROOT.'/telephonie/ligne/liste.php">';
print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td>Recherche ligne</td>';
print "</tr>\n";
print "<tr $bc[1]>";
print '<td>Numéro <input name="search_ligne" size="12"></td></tr>';
print '</table>';

print '<br />';


$sql = "SELECT distinct statut, count(*) as cc";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " GROUP BY statut";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  $ligne = new LigneTel($db);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Lignes Statuts</td><td valign="center">Nb</td><td>&nbsp;</td>';
  print "</tr>\n";
  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	
      $values[$obj->statut] = $obj->cc;
      $i++;
    }

  foreach ($ligne->statuts as $key => $statut)
    {
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td>".$statut."</td>\n";
      print "<td>".$values[$key]."</td>\n";
      print '<td><a href="'.DOL_URL_ROOT.'/telephonie/ligne/liste.php?statut='.$key.'"><img border="0" src="./graph'.$key.'.png"></a></td>';
      print "</tr>\n";
    }

  print "</table>";
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}

print '<br />';

$sql = "SELECT distinct f.nom as fournisseur, count(*) as cc";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_fournisseur as f";
$sql .= " WHERE l.fk_soc = s.idp AND l.fk_fournisseur = f.rowid";
$sql .= " GROUP BY f.nom";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Fournisseur</td><td valign="center">Nb</td>';
  print "</tr>\n";
  $var=True;

  while ($i < $num)
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]>";
      print "<td>".$obj->fournisseur."</td>\n";
      print "<td>".$obj->cc."</td>\n";
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}
/*
 * Concurrents
 *
 */

print '<br />';

$sql = "SELECT distinct c.nom as concurrent, count(*) as cc";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_concurrents as c,".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " WHERE l.fk_concurrent = c.rowid";
$sql .= " GROUP BY c.nom";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Concurrents</td><td valign="center">Nb</td>';
  print "</tr>\n";
  $var=True;

  while ($i < $num)
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]>";
      print "<td>".$obj->concurrent."</td>\n";
      print "<td>".$obj->cc."</td>\n";
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}


print '</td><td valign="top" width="70%" rowspan="3">';

print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=telephoniegraph&file=lignes/lignes.actives.png" alt="Lignes Actives" title="Lignes Actives"><br /><br />'."\n";
print '</td></tr>';

print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
