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

llxHeader('','Telephonie - Ligne - Commande');



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


$sql = "SELECT distinct statut, count(*) as cc";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " GROUP BY statut";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;
  $ligne = new LigneTel($db);
  print_titre("Commandes");

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Lignes Statuts</td><td valign="center">Nb</td>';
  print "</tr>\n";
  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]>";
      print "<td>".$ligne->statuts[$obj->statut]."</td>\n";
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

if ($user->rights->telephonie->ligne_commander)
{
  print '<br>';

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Nouvelle commande</td>';
  print "</tr><tr $bc[1]>";
  print '<td><a href="fiche.php">'.img_edit().'</a>';
  print '&nbsp;<a href="fiche.php">Créer une nouvelle commande</a></td>';
  print "</tr>\n";
  print '</table>';
}

print '</td><td valign="top">';

/*
 * Seconde colonne
 *
 */
$sql = "SELECT ";
$sql .= " cli,mode,situation,date_mise_service,date_resiliation,motif_resiliation,commentaire,fichier, traite ";

$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_commande_retour";
$sql .= " WHERE traite = 0 AND mode = 'PRESELECTION'";
$sql .= " LIMIT 10";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;

  print_titre("Retour");

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Lignes Statuts</td><td align="center">Resultat</td>';
  print '<td align="center">Date</td><td>Commentaire</td>';
  print "</tr>\n";
  $var=True;

  while ($i < $num)
    {
      $obj = $db->fetch_object();
      $var=!$var;

      $ligne = new LigneTel($db);

      if ( $ligne->fetch($obj->cli) == 1);
      {
	print "<tr $bc[$var]><td>";
	print '<img src="'.DOL_URL_ROOT.'/telephonie/ligne/graph'.$ligne->statut.'.png">&nbsp;';
	print '<a href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?numero='.$obj->cli.'">';
	print $obj->cli."</a></td>\n";
	print '<td align="center">'.$obj->situation."</td>\n";
	print '<td align="center">'.$obj->date_mise_service."</td>\n";
	print '<td>'.$obj->commentaire."</td>\n";
	print "</tr>\n";
      }
      $i++;
    }
  print "</table>";
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}


print '</td></tr>';
print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
