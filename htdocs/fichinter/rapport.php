<?PHP
/* Copyright (C) 2003 Xavier DUTOIT <doli@sydesy.com>
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
require("../contact.class.php");

if ($user->societe_id > 0)
{
  $socid = $user->societe_id ;
}

llxHeader();
/*
 * Liste
 *
 */

if ($sortorder == "")
{
  $sortorder="ASC";
}
if ($sortfield == "")
{
  $sortfield="f.datei";
}

if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$sql = "SELECT s.nom,s.idp, f.note, f.ref,".$db->pdate("f.datei")." as dp, f.rowid as fichid, f.fk_statut, f.duree";
$sql .= " FROM llx_societe as s, llx_fichinter as f ";
$sql .= " WHERE f.fk_soc = s.idp";


if ($socid > 0)
{
  $sql .= " AND s.idp = " . $socid;
}

if (empty ($MM))
  $MM=strftime("%m",time());
if (empty($YY))
  $YY=strftime("%Y",time());;
echo "<div class='noprint'>";
echo "\n<form action='$PHP_SELF'>";
echo "<input type='hidden' name='socid' value='$socid'>";
echo "Mois <input name='MM' size='2' value='$MM'>";
echo " Ann&eacute;e <input size='4' name='YY' value='$YY'>";
echo "<input type='submit' name='g' value='Genérer le rapport'>";
echo "<form>";
echo "</div>";

$start="$YY-$MM-01 00:00:00";
if ($MM ==12)
{
  $y = $YY+1;
  $end="$y-01-01 00:00:00";
}
else
{
  $m = $MM+1;
  $end="$YY-$m-01 00:00:00";
}
$sql .= " AND datei >= '$start' AND datei < '$end'" ;

$sql .= " ORDER BY $sortfield $sortorder ";

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $title = "Rapport d'activité de " . strftime("%B %Y",strtotime ($start));
  print_barre_liste($title, $page, $PHP_SELF,"&socid=$socid",$sortfield,$sortorder,'',$num);

  $i = 0;
  print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';
  print "<TR class=\"liste_titre\">";
  print '<td>Num</td>';
  if (empty($socid))
    print '<td>Société</td>';
  print '<TD align="center">Description</TD>';
    
  print '<TD align="center">Date</TD>';
  print '<TD align="center">Durée</TD>';
//  print '<TD align="center">Statut</TD><td>&nbsp;</td>';
  print "</TR>\n";
  $var=True;
  $DureeTotal = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD><a href=\"fiche.php?id=$objp->fichid\">$objp->ref</a></TD>\n";

      if (empty($socid))
      {
	if (!empty($MM))
	  $filter="&MM=$MM&YY=$YY";
        print '<td><a href="rapport.php?socid='.$objp->idp.$filter.'"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filter.png" border="0"></a>&nbsp;';
        print "<a href=\"../comm/fiche.php?socid=$objp->idp$filter\">$objp->nom</a></TD>\n";
      }
      print '<TD>'.nl2br($objp->note).'</TD>';
      print "<TD>".strftime("%d %B %Y",$objp->dp)."</TD>\n";
      print '<TD align="center">'.sprintf("%.1f",$objp->duree).'</TD>';
      $DureeTotal += $objp->duree;
/*      print '<TD align="center">'.$objp->fk_statut.'</TD>';

      if ($user->societe_id == 0)
	{
	  print '<TD align="center"><a href="fiche.php3?socidp='.$objp->idp.'&action=create">[Fiche Inter]</A></td>';
	}
      else
	{
	  print "<td>&nbsp;</td>";
	}
  */    print "</TR>\n";
      
      $i++;
    }
  print "</TABLE>";
  $db->free();
  print "<br />Total $DureeTotal jour[s]";
}
else
{
  print $db->error();
  print "<p>$sql";
}
$db->close();

//llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
