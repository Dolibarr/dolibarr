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

require("./pre.inc.php");

$user->getrights('commande');
if (!$user->rights->commande->lire)
  accessforbidden();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

llxHeader();

if ($sortfield == "")
{
  $sortfield="c.rowid";
}
if ($sortorder == "")
{
  $sortorder="DESC";
}

if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$sql = "SELECT s.nom, s.idp, c.rowid, c.ref, c.total_ht,".$db->pdate("c.date_commande")." as date_commande, c.fk_statut" ;
$sql .= " FROM llx_societe as s, llx_commande as c WHERE c.fk_soc = s.idp";

if ($socidp)
{ 
  $sql .= " AND s.idp = $socidp"; 
}

if ($_GET["month"] > 0)
{
  $sql .= " AND date_format(c.date_commande, '%Y-%m') = '$year-$month'";
}
if ($_GET["year"] > 0)
{
  $sql .= " AND date_format(c.date_commande, '%Y') = $year";
}

if (strlen($HTTP_POST_VARS["sf_ref"]) > 0)
{
  $sql .= " AND c.ref like '%".$HTTP_POST_VARS["sf_ref"] . "%'";
}

$sql .= " ORDER BY $sortfield $sortorder";
$sql .= $db->plimit($limit + 1,$offset);

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  print_barre_liste("Commandes", $page, $PHP_SELF,"&amp;socidp=$socidp",$sortfield,$sortorder,'',$num);
    
  $i = 0;
  print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';
  
  print '<TR class="liste_titre">';
  
  print_liste_field_titre_new ("Réf",$PHP_SELF,"c.ref","","&amp;socidp=$socidp",'width="15%"',$sortfield);
  
  print_liste_field_titre_new ("Société",$PHP_SELF,"s.nom","","&amp;socidp=$socidp",'width="30%"',$sortfield);
  
  print_liste_field_titre_new ("Date",$PHP_SELF,"c.date_commande","","&amp;socidp=$socidp", 'width="25%" align="right" colspan="2"',$sortfield);
  
  print_liste_field_titre_new ("Statut",$PHP_SELF,"c.fk_statut","","&amp;socidp=$socidp",'width="10%" align="center"',$sortfield);
  print "</tr>\n";
  $var=True;
  
  $generic_commande = new Commande($db);

  while ($i < min($num,$limit))
    {
      $objp = $db->fetch_object( $i);
      
	  $var=!$var;
	  print "<tr $bc[$var]>";
	  print "<td><a href=\"fiche.php?id=$objp->rowid\">$objp->ref</a></td>\n";
	  print "<td><a href=\"../comm/fiche.php?socid=$objp->idp\">$objp->nom</a></td>\n";
	  
	  $now = time();
	  $lim = 3600 * 24 * 15 ;
	  
	  if ( ($now - $objp->date_commande) > $lim && $objp->statutid == 1 )
	    {
	      print "<td><b> &gt; 15 jours</b></td>";
	    }
	  else
	    {
	      print "<td>&nbsp;</td>";
	    }
	  
	  print "<td align=\"right\">";
	  $y = strftime("%Y",$objp->date_commande);
	  $m = strftime("%m",$objp->date_commande);
	  
	  print strftime("%d",$objp->date_commande)."\n";
	  print " <a href=\"liste.php?year=$y&amp;month=$m\">";
	  print strftime("%B",$objp->date_commande)."</a>\n";
	  print " <a href=\"liste.php?year=$y\">";
	  print strftime("%Y",$objp->date_commande)."</a></TD>\n";      

	  print '<td align="center">'.$generic_commande->statuts[$objp->fk_statut].'</td>';
	  print "</tr>\n";
	  
	  $total = $total + $objp->price;
	  $subtotal = $subtotal + $objp->price;
	  
	  $i++;
	}
            
      print "</table>";
      $db->free();
    }
  else
    {
      print $db->error();
    }

$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
