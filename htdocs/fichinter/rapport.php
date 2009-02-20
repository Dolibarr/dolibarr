<?php
/* Copyright (C) 2003 Xavier DUTOIT        <doli@sydesy.com>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");

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

$sql = "SELECT s.nom,s.rowid as socid, f.description, f.ref,".$db->pdate("f.datei")." as dp, f.rowid as fichid, f.fk_statut, f.duree";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."fichinter as f ";
$sql .= " WHERE f.fk_soc = s.rowid";


if ($socid > 0)
{
  $sql .= " AND s.rowid = " . $socid;
}

if (empty ($MM))
  $MM=strftime("%m",time());
if (empty($YY))
  $YY=strftime("%Y",time());;
echo "<div class='noprint'>";
echo "\n<form action='rapport.php'>";
echo "<input type='hidden' name='socid' value='$socid'>";
echo $langs->trans("Month")." <input name='MM' size='2' value='$MM'>";
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
  $title = $langs->trans("Report")." ".dol_print_date(strtotime($start),"%B %Y");
  print_barre_liste($title, $page, "rapport.php","&socid=$socid",$sortfield,$sortorder,'',$num);

  $i = 0;
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="3">';
  print "<tr class=\"liste_titre\">";
  print '<td>Num</td>';
  if (empty($socid))
    print '<td>Société</td>';
  print '<td align="center">'.$langs->trans("Description").'</td>';
    
  print '<td align="center">Date</td>';
  print '<td align="center">'.$langs->trans("Duration").'</td>';
  print "</tr>\n";
  $var=True;
  $DureeTotal = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object();
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td><a href=\"fiche.php?id=$objp->fichid\">$objp->ref</a></td>\n";

      if (empty($socid))
      {
	if (!empty($MM))
	  $filter="&MM=$MM&YY=$YY";
        print '<td><a href="rapport.php?socid='.$objp->socid.$filter.'"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filter.png" border="0"></a>&nbsp;';
        print "<a href=\"".DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->rowid.$filter."\">".$objp->nom."</a></TD>\n";
      }
      print '<td>'.nl2br($objp->description).'</td>';
      print "<td>".dol_print_date($objp->dp,"%d %B %Y")."</td>\n";
      print '<td align="center">'.sprintf("%.1f",$objp->duree).'</td>';
      $DureeTotal += $objp->duree;
      print "</tr>\n";
      
      $i++;
    }
  print "</table>";
  $db->free();
  print "<br />".$langs->trans("Total")." $DureeTotal jour[s]";
}
else
{
  dol_print_error($db);
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
