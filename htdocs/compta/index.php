<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php3");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

llxHeader();

/*
 *
 */


if ($action == 'add_bookmark')
{
  $sql = "DELETE FROM llx_bookmark WHERE fk_soc = ".$socidp." AND fk_user=".$user->id;
  if (! $db->query($sql) )
    {
      print $db->error();
    }
  $sql = "INSERT INTO llx_bookmark (fk_soc, dateb, fk_user) VALUES ($socidp, now(),".$user->id.");";
  if (! $db->query($sql) )
    {
      print $db->error();
    }
}

if ($action == 'del_bookmark')
{
  $sql = "DELETE FROM llx_bookmark WHERE rowid=$bid";
  $result = $db->query($sql);
}
/*
 *
 *
 */
print_titre(translate("Accueil comptabilité"));

print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td valign="top" width="33%">';
/*
 *
 */
print '<TABLE border="0" cellspacing="0" cellpadding="3" width="100%">';
print "<TR class=\"liste_titre\">";
print '<td colspan="2">Rechercher une facture</td></tr>';
print '<form method="post" action="facture.php3">';
print "<tr $bc[1]><td>";
print 'Num. : <input type="text" name="sf_ref"><input type="submit" value="Rechercher" class="flat"></td></tr>';
print "</form></table><br>";

/*
 * Propales à facturer
 */
if ($user->comm > 0 && $conf->commercial ) 
{
  $sql = "SELECT p.rowid, p.ref, s.nom FROM llx_propal as p, llx_societe as s";
  $sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut = 2";
  if ($socidp)
    {
      $sql .= " AND p.fk_soc = $socidp";
    }

  if ( $db->query($sql) ) 
    {
      $num = $db->num_rows();
      if ($num)
	{
	  $i = 0;
	  print '<TABLE border="0" cellspacing="0" cellpadding="3" width="100%">';
	  print "<TR class=\"liste_titre\">";
	  print '<td colspan="2">'.translate("Propositions comm. à facturer").'</td>';
	  print "</TR>\n";
  
	  while ($i < $num)
	    {
	      $var=!$var;
	      $obj = $db->fetch_object($i);
	      print "<tr $bc[$var]><td><a href=\"propal.php3?propalid=$obj->rowid\">$obj->ref</a></td><td align=\"right\">".$obj->nom."</td></tr>";
	      $i++;
	    }
	  print "</table><br>";
	}
    }
}

/*
 * Charges a payer
 *
 */
if ($user->societe_id == 0)
{
 
  $sql = "SELECT c.amount, cc.libelle";
  $sql .= " FROM llx_chargesociales as c, c_chargesociales as cc";
  $sql .= " WHERE c.fk_type = cc.id AND c.paye=0";
  
  if ( $db->query($sql) ) 
    {
      $num = $db->num_rows();
      if ($num)
	{
	  print "<table border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
	  print "<TR class=\"liste_titre\">";
	  print "<TD colspan=\"2\">Charges à payer</td>";
	  print "</TR>\n";
	  $i = 0;
	  
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object( $i);
	      $var = !$var;
	      print "<tr $bc[$var]>";
	      print '<td>'.$obj->libelle.'</td>';
	      print '<td align="right">'.price($obj->amount).'</td>';
	      print '</tr>';
	      $i++;
	    }
	  print '</table><br>';
	}
    }
  else
    {
      print $db->error();
    }
}
/*
 * Factures impayées
 */

$sql = "SELECT f.facnumber, f.rowid, s.nom, s.idp FROM llx_facture as f, llx_societe as s WHERE s.idp = f.fk_soc AND f.paye = 0 AND f.fk_statut > 0";
if ($socidp)
{
  $sql .= " AND f.fk_soc = $socidp";
}

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;

  if ($num)
    {
      print '<TABLE border="0" cellspacing="0" cellpadding="3" width="100%">';
      print "<TR class=\"liste_titre\">";
      print '<td colspan="2">Factures impayées</td></tr>';

      while ($i < $num)
	{
	  $obj = $db->fetch_object( $i);
	  $var=!$var;
	  print '<tr '.$bc[$var].'><td><a href="facture.php3?facid='.$obj->rowid.'">'.$obj->facnumber.'</td>';
	  print '<td><a href="fiche.php3?socid='.$obj->idp.'">'.$obj->nom.'</a></td></tr>';
	  $i++;
	}
      print "</table><br>";
    }
  $db->free();
}
else
{
  print $sql;
}

/*
 * Bookmark
 *
 */
$sql = "SELECT s.idp, s.nom,b.rowid as bid";
$sql .= " FROM llx_societe as s, llx_bookmark as b";
$sql .= " WHERE b.fk_soc = s.idp AND b.fk_user = ".$user->id;
$sql .= " ORDER BY lower(s.nom) ASC";

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;

  print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<TR class=\"liste_titre\">";
  print "<TD colspan=\"2\">Bookmark</td>";
  print "</TR>\n";

  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);
      $var = !$var;
      print "<tr $bc[$var]>";
      print '<td><a href="fiche.php3?socid='.$obj->idp.'">'.$obj->nom.'</a></td>';
      print '<td align="right"><a href="index.php?action=del_bookmark&bid='.$obj->bid.'">';
      print '<img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/editdelete.png" border="0"></a></td>';
      print '</tr>';
      $i++;
    }
  print '</table>';
}
/*
 * Actions a faire
 *
 *
 */
print '</td><td valign="top" width="33%">';


print '<div class="menus">';
if ($user->societe_id == 0) 
{
  print '<ul>';
  print '<li><a href="./charges/index.php">Charges</a></li>';
  print '<li><a href="./resultat/">Résultats</a></li>';
  print '<li><a href="bank/index.php">Banque</a></li>';
}
print '</ul></div>';



$result = 0;
if ( $result ) {

  print '<TABLE border="0" cellspacing="0" cellpadding="3" width="100%">';
  print "<TR class=\"liste_titre\">";
  print "<td colspan=\"2\">Actions à faire</td>";
  print "</TR>\n";

  $i = 0;
  while ($i < $db->num_rows() ) {
    $obj = $db->fetch_object($i);
    $var=!$var;
    
    print "<tr $bc[$var]><td>".strftime("%d %b %Y",$obj->da)."</td><td><a href=\"action/fiche.php3\">$obj->libelle $obj->label</a></td></tr>";
    $i++;
  }
  $db->free();
  print "</table><br>";
} else {
  print $db->error();
}
/*
 *
 *
 */

/*
 * Factures brouillons
 */

$sql = "SELECT f.facnumber, f.rowid, s.nom, s.idp FROM llx_facture as f, llx_societe as s WHERE s.idp = f.fk_soc AND f.fk_statut = 0";

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;

  if ($num)
    {
      print '<TABLE border="0" cellspacing="0" cellpadding="3" width="100%">';
      print '<TR class="liste_titre">';
      print '<td colspan="2">Factures brouillons</td></tr>';

      while ($i < $num)
	{
	  $obj = $db->fetch_object( $i);
	  $var=!$var;
	  print '<tr '.$bc[$var].'><td><a href="facture.php3?facid='.$obj->rowid.'">'.$obj->facnumber.'</td>';
	  print '<td><a href="fiche.php3?socid='.$obj->idp.'">'.$obj->nom.'</a></td></tr>';
	  $i++;
	}
      
      print "</table><br>";
    }
}
else
{
  print $sql;
}

/*
 * Factures a payer
 *
 */
if ($user->societe_id == 0)
{
  $sql = "SELECT ff.total_ttc as amount, ff.libelle, ff.rowid";
  $sql .= " FROM llx_facture_fourn as ff";
  $sql .= " WHERE ff.paye=0";
  
  if ( $db->query($sql) ) 
    {
      $num = $db->num_rows();
      if ($num)
	{
	  print '<table border="0" width="100%" cellspacing="0" cellpadding="4">';
	  print '<TR class="liste_titre">';
	  print '<TD colspan="2">Factures à payer</td>';
	  print "</TR>\n";
	  $i = 0;
	  
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object( $i);
	      $var = !$var;
	      print "<tr $bc[$var]>";
	      print '<td><a href="../fourn/facture/fiche.php3?facid='.$obj->rowid.'">'.$obj->libelle.'</a></td>';
	      print '<td align="right">'.price($obj->amount).'</td>';
	      print '</tr>';
	      $i++;
	}
	  print '</table><br>';
	}
    }
  else
    {
      print $db->error();
    }
}

print '</td><td width="40%">&nbsp;</td></tr>';

print '</table>';

$db->close();
 
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
