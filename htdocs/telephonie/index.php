<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

if (!$user->rights->telephonie->lire) accessforbidden();

llxHeader('','Telephonie');

/*
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
print '<td>Numéro <input name="search_ligne" size="12"><input type="submit"></td></tr>';
print '</table></form><br />';

print '<form method="GET" action="'.DOL_URL_ROOT.'/telephonie/contrat/liste.php">';
print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td>Recherche contrat</td>';
print "</tr>\n";
print "<tr $bc[1]>";
print '<td>Numéro <input name="search_contrat" size="12"></td></tr>';
print '</table></form><br />';

print '<form method="GET" action="'.DOL_URL_ROOT.'/telephonie/client/liste.php">';
print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td>Recherche client</td>';
print "</tr>\n";
print "<tr $bc[1]>";
print '<td>Nom <input name="search_client" size="12"><input type="submit"></td></tr>';
print '</table></form>';

print '<br />';

$sql = "SELECT distinct statut, count(*) as cc";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
if ($user->rights->telephonie->ligne->lire_restreint)
{
  $sql .= " WHERE l.fk_commercial_suiv = ".$user->id;
}
$sql .= " GROUP BY statut";

$resql = $db->query($sql);

if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  $ligne = new LigneTel($db);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Lignes Statuts</td><td align="right">Nb</td>';
  print "<td>&nbsp;</td></tr>\n";
  $var=True;

  while ($i < $num)
    {
      $obj = $db->fetch_object($resql);	
      $var=!$var;

      print "<tr $bc[$var]>";
      print "<td>".$ligne->statuts[$obj->statut]."</td>\n";
      print '<td align="right">'.$obj->cc."</td>\n";
      print '<td><a href="'.DOL_URL_ROOT.'/telephonie/ligne/liste.php?statut='.$obj->statut.'"><img border="0" src="./ligne/graph'.$obj->statut.'.png"></a></td>';
      print "</tr>\n";
      $i++;
    }
  
  print "</table>";
  $db->free($resql);
}
else 
{
  print $db->error() . ' ' . $sql;
}

if ($user->rights->telephonie->fournisseur->lire)
{
  print '<br />';

  /*
   * Fournisseurs
   *
   */
  
  $sql = "SELECT distinct f.nom as fournisseur, f.rowid, count(*) as cc";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
  $sql .= " ,".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
  $sql .= " ,".MAIN_DB_PREFIX."telephonie_fournisseur as f";
  $sql .= " WHERE l.fk_soc = s.idp AND l.fk_fournisseur = f.rowid";
  if ($user->rights->telephonie->ligne->lire_restreint)
    {
      $sql .= " AND l.fk_commercial_suiv = ".$user->id;
    }
  $sql .= " GROUP BY f.nom";
  
  $resql = $db->query($sql);
  
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      
      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
      print '<tr class="liste_titre"><td>Fournisseur</td><td align="center">Nb lignes</td>';
      print "</tr>\n";
      $var=True;
      
      while ($i < min($num,$conf->liste_limit))
	{
	  $obj = $db->fetch_object($resql);	
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  print '<td><a href="'.DOL_URL_ROOT.'/telephonie/ligne/liste.php?fournisseur='.$obj->rowid.'">';
	  print $obj->fournisseur.'</a></td>';
	  print '<td align="center">'.$obj->cc."</td>\n";
	  print "</tr>\n";
	  $i++;
	}
      print "</table>";
      $db->free($resql);
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }
}
print '</td><td width="70%" valign="top">';

print '</td></tr>';
print '</table>';
  
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
