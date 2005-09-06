<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader('','Telephonie - Contrats');

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

print '<form method="GET" action="'.DOL_URL_ROOT.'/telephonie/contrat/liste.php">';
print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td>Recherche contrat</td>';
print "</tr>\n";
print "<tr $bc[1]>";
print '<td>Numéro <input name="search_contrat" size="12"></td></tr>';
print '</table></form>';

print '<br />';

$sql = "SELECT distinct statut, count(*) as cc";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_contrat as l";
$sql .= ",".MAIN_DB_PREFIX."societe_perms as sp";
$sql .= " WHERE l.fk_client_comm = sp.fk_soc";
$sql .= " AND sp.fk_user = ".$user->id." AND sp.pread = 1";


$sql .= " GROUP BY statut";

if ($db->query($sql))
{
  $num = $db->num_rows();
  $i = 0;

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Contrats</td><td align="center">Nb</td><td>&nbsp;</td>';
  print "</tr>\n";
  $var=True;

  $contrat = new TelephonieContrat($db);

  while ($i < $num)
    {
      $obj = $db->fetch_object();	

      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td>".$contrat->statuts[$obj->statut]."</td>\n";
      print '<td align="center">'.$obj->cc."</td>\n";
      print '<td><a href="liste.php?statut='.$obj->statut.'">';
      print '<img border="0" src="statut'.$obj->statut.'.png"></a></td>';
      print "</tr>\n";

      $values[$obj->statut] = $obj->cc;
      $i++;
    }
  print "</table>";
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}




print '</td><td valign="top" width="70%">';

$sql = "SELECT c.ref, c.rowid, c.statut";
$sql .= " ,s.idp as socidp, sf.idp as sfidp, sf.nom as nom_facture,s.nom";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_contrat as c";
$sql .= " , ".MAIN_DB_PREFIX."societe as sf";

$sql .= " WHERE c.fk_client_comm = s.idp";
$sql .= " AND c.fk_soc = sf.idp";
if ($user->rights->telephonie->ligne->lire_restreint)
{
  $sql .= " AND c.fk_commercial_suiv = ".$user->id;
}
$sql .= " ORDER BY date_creat DESC LIMIT 10;";

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  print"\n<!-- debut table -->\n";
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';

  print '<td>Référence</td>';
  print '<td>Client (Agence/Filiale)</td>';
  print '<td>Client facturé</td>';
  print "</tr>\n";

  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]><td>";
      print '<img src="statut'.$obj->statut.'.png">&nbsp;';     
      print '<a href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?id='.$obj->rowid.'">';
      print img_file();      
      print '</a>&nbsp;';

      print '<a href="fiche.php?id='.$obj->rowid.'">'.$obj->ref."</a></td>\n";

      print '<td><a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$obj->socidp.'">'.stripslashes($obj->nom).'</a></td>';
      print '<td><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$obj->sfidp.'">'.stripslashes($obj->nom_facture).'</a></td>';

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



print '</td></tr>';


print '</table>';



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
