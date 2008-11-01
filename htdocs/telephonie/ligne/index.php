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

if (!$user->rights->telephonie->lire)
  accessforbidden();

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];

llxHeader('','Telephonie - Lignes');

/*
 * S�curit� acc�s client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

/*
 * Mode Liste
 *
 *
 *
 */
print '<form method="GET" action="'.DOL_URL_ROOT.'/telephonie/ligne/liste.php">';
print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td width="30%" valign="top">';


print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre"><td>Recherche ligne</td>';
print "</tr>\n";
print "<tr $bc[1]>";
print '<td>Num�ro <input name="search_ligne" size="12"></td></tr>';
print '</table>';

print '<br />';


$sql = "SELECT distinct statut, count(*) as cc";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= ",".MAIN_DB_PREFIX."societe_perms as sp";
$sql .= " WHERE l.fk_client_comm = sp.fk_soc";
$sql .= " AND sp.fk_user = ".$user->id." AND sp.pread = 1";
$sql .= " GROUP BY statut";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  $ligne = new LigneTel($db);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>Lignes Statuts</td><td valign="center">Nb</td><td>&nbsp;</td>';
  print "</tr>\n";
  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($resql);	
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



if ($user->rights->telephonie->fournisseur->lire)
{
  print '<br />';
  $sql = "SELECT distinct f.nom as fournisseur, f.rowid, count(*) as cc";
  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
  $sql .= " , ".MAIN_DB_PREFIX."telephonie_fournisseur as f";
  $sql .= " WHERE l.fk_soc = s.rowid AND l.fk_fournisseur = f.rowid";
  if ($user->rights->telephonie->ligne->lire_restreint)
    {
      $sql .= " AND l.fk_commercial_suiv = ".$user->id;
    }
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
	  print '<td><a href="'.DOL_URL_ROOT.'/telephonie/ligne/liste.php?fournisseur='.$obj->rowid.'">';
	  print $obj->fournisseur.'</a></td>';
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
}

print '</td><td valign="top" width="70%">';

$sql = "SELECT s.rowid as socid, sf.rowid as sfidp, sf.nom as nom_facture,s.nom, l.ligne, f.nom as fournisseur, l.statut, l.rowid, l.remise";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= ",".MAIN_DB_PREFIX."telephonie_societe_ligne as l";
$sql .= " , ".MAIN_DB_PREFIX."societe as sf";
$sql .= " , ".MAIN_DB_PREFIX."telephonie_fournisseur as f";

$sql .= ",".MAIN_DB_PREFIX."societe_perms as sp";

$sql .= " WHERE l.fk_soc = s.rowid AND l.fk_fournisseur = f.rowid";

$sql .= " AND s.rowid = sp.fk_soc";
$sql .= " AND sp.fk_user = ".$user->id." AND sp.pread = 1";

$sql .= " AND l.fk_soc_facture = sf.rowid";

$sql .= " ORDER BY rowid DESC LIMIT 10";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  

  print"\n<!-- debut table -->\n";
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print '<td>'.min(10,$num).' Derni�res lignes</td>';
  print '<td>Client (Agence/Filiale)</td>';
  print '<td align="center">Statut</td>';

  if ($user->rights->telephonie->fournisseur->lire)
    print '<td>Fournisseur</td>';

  print "</tr>\n";

  $var=True;

  $ligne = new LigneTel($db);

  while ($i < $num)
    {
      $obj = $db->fetch_object();	
      $var=!$var;

      print "<tr $bc[$var]><td>";
      print '<img src="./graph'.$obj->statut.'.png">&nbsp;';
      
      print '<a href="'.DOL_URL_ROOT.'/telephonie/ligne/fiche.php?id='.$obj->rowid.'">';
      print img_file();      
      print '</a>&nbsp;';

      print '<a href="fiche.php?id='.$obj->rowid.'">'.dolibarr_print_phone($obj->ligne,0,0,true)."</a></td>\n";

      $nom = stripslashes($obj->nom);
      if (strlen(stripslashes($obj->nom)) > 20)
	{
	  $nom = substr(stripslashes($obj->nom),0,20)."...";
	}

      print '<td><a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$obj->socid.'">'.$nom.'</a></td>';

      print '<td align="center">'.$ligne->statuts[$obj->statut]."</td>\n";

      if ($user->rights->telephonie->fournisseur->lire)
	print "<td>".$obj->fournisseur."</td>\n";
      print "</tr>\n";
      $i++;
    }
  print "</table><br />";
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}



$sql = "SELECT distinct c.nom as concurrent, count(*) as cc";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_concurrents as c,".MAIN_DB_PREFIX."telephonie_societe_ligne as l";

$sql .= ",".MAIN_DB_PREFIX."societe_perms as sp";
$sql .= " WHERE l.fk_client_comm = sp.fk_soc";
$sql .= " AND sp.fk_user = ".$user->id." AND sp.pread = 1";
$sql .= " AND l.fk_concurrent = c.rowid";

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

print "</td></tr>\n";
print "</table>\n</form>\n";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
