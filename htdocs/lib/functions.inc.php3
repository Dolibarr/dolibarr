<?PHP
/* Copyright (C) 2000,2001 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
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
 * or see http://www.gnu.org/
 */
/*
 * $Id$
 * $Source$
 *
 */

function price($amount) {

  return number_format($amount, 2, '.', ' ');
  //return sprintf("%.2f", $amount);
}


function francs($euros) {
  return price($euros * 6.55957);
}
function tva($euros) {
  return sprintf("%01.2f",($euros * 0.196));
}
function inctva($euros) {
  return sprintf("%01.2f",($euros * 1.196));
}


function gljftime($format,$timestamp) {

  $hour = substr($timestamp,11,2);
  $min = substr($timestamp,14,2);
  $sec = substr($timestamp,17,2);
  $month = substr($timestamp,5,2);
  $day = substr($timestamp,8,2);
  $year = substr($timestamp,0,4);

  $ftime = mktime($hour,$min,$sec,$month,$day,$year);

  return strftime($format,$ftime);

}


function gljPrintTitle($title, $ispage, $page=0, $parm=0) {

  $pageprev = $page - 1;
  $pagenext = $page + 1;


  print "<table width=\"100%\" cellspacing=\"0\"><tr><td>";
  print "<b>$title</b></td><td align=\"right\">$basec <b>Page $pagenext</b>";
  print "</td>";

  if ($ispage) {

    print "</tr>";
    print "<tr><td>";
    if ($page > 0) {
      print "<A href=\"".$GLOBALS["PHP_SELF"]."?page=$pageprev&$parm\"><- ".$GLOBALS["_PAGE_PREV"]."</A>";
    }
    print "</td>\n";
    print "<td align=\"right\"><A href=\"".$GLOBALS["PHP_SELF"]."?&page=$pagenext&$parm\">$_PAGE_NEXT -></A>\n";
  }

  print "</tr></table>";
};

function gljActiveSoc($db, $socidp) {

  if ($db) {
    $sql = "UPDATE societe set datea = " . time() . " WHERE idp= " . $socidp ; 
    $result = $db->query($sql);
    if (!$result) {
      return 1;
    }
    return 0;
  }
}
function gljChangeSocAlias($db, $socidp, $alias) {

  if ($db) {
    $alias = strtolower(trim($alias));
    $sql = "UPDATE societe set alias = '$alias' WHERE idp=$socidp" ; 
    $result = $db->query($sql);
    if (!$result) {
      return 1;
    }
    return 0;
  }
}

function stat_print($basename,$bc1,$bc2,$ftc, $jour) {

  $db = pg_Connect("","","","","$basename");
  if (!$db) {
    echo "Pas de connexion a la base\n"; 
    exit ; 
  }

  $offset = $jour * 9;

  $sql="SELECT s.date, s.nb, l.libelle FROM stat_base as s, stat_cat as l WHERE s.cat = l.id ORDER by s.date DESC, s.cat ASC LIMIT 9 OFFSET $offset";
  
  $result = $db->query($sql);
  if (!$result) {
    print "Erreur SELECT<br><h1>$sql</h1><br>";
    return 1;
  }

  print "<table border=1 cellspacing=0 cellpadding=2>";
  print "<tr><td><font color=\"white\">base <b>$basename</b></font></td>";
  print "<td><font color=\"white\">libelle</font></td>";
  print "</tr>";

  $num = $db->num_rows();
  $i = 0;
  
  $tag = 1;
  while ( $i < $num) {
    $obj = $db->fetch_object( $i);

    $tag = !$tag;

    print "<TR><TD>$obj->date</TD><TD>$obj->libelle</TD>\n";
    print "<TD align=\"center\">$obj->nb</TD></TR>\n";
    $i++;
  }
  print "</TABLE>";
  $db->free();
  
  $db->close();

}

function tab_count($basename,$bc1,$bc2,$ftc) {

  $db = pg_Connect("","","","","$basename");
  if (!$db) {
    echo "Pas de connexion a la base\n"; 
    exit ; 
  }

  $sql="SELECT count(*) AS nbcv from candidat WHERE active=1";
  $result = $db->query($sql);
  if (!$result) {
    print "Erreur SELECT<br><h1>$sql</h1><br>";
    return 1;
  }
  print "<table border=0 bgcolor=black cellspacing=0 cellpadding=0><tr><td>";
  
  print "<table border=0 cellspacing=1 cellpadding=1>";
  print "<tr><td><font color=\"white\">base <b>$basename</b></font></td>";
  print "<td><font color=\"white\">libelle</font></td>";
  print "</tr>";
  $nbcv = $db->result( $i, "nbcv");
  
  print "<tr $bc1><td><b>$ftc Nombre de CV</font></b></td>\n";
  print "<td  align=\"center\">$ftc $nbcv</td>\n";
  print "</tr>\n";
  $db->free();

  $sql="SELECT count(*) AS nbcv from offre WHERE active=1";

  $result = $db->query($sql);
  if (!$result) {
    print "Erreur SELECT<br><h1>$sql</h1><br>";
  }  
  $nbcv = $db->result( $i, "nbcv");
  
  print "<tr $bc2><td><b>$ftc Nombre d'offre</font></b></td>";
  print "<td align=\"center\">$ftc $nbcv</td>";
  print "</tr>";
  
  $db->free();
	

  $sql="SELECT count(*) AS nbcv from candidat WHERE active=0";

  $result = $db->query($sql);
  if (!$result) {
    print "Erreur SELECT<br><h1>$sql</h1><br>";
  }

  $nbcv = $db->result( $i, "nbcv");
  
  print "<tr $bc1><td><b>$ftc Nombre de CV inactifs</font></b></td>\n";
  print "<td align=\"center\">$ftc $nbcv</td>";
  print "</tr>";
  
  $db->free();


  $sql="SELECT count(*) AS nbcv from offre WHERE active=0";
  
  $result = $db->query($sql);
  if (!$result) {
    print "Erreur SELECT<br><h1>$sql</h1><br>";
  }
  
  $nbcv = $db->result( $i, "nbcv");

  print "<tr $bc2><td><b>$ftc Nombre d'offres inactives</font></b></td>\n";
  print "<td  align=\"center\">$ftc $nbcv</td>\n";
  print "</tr>\n";
  
  $db->free();
  
  
  $sql="SELECT count(*) AS nbsoc from logsoc";
  
  $result = $db->query($sql);
  if (!$result) {
    print "Erreur SELECT<br><h1>$sql</h1><br>";
  }
  
  $nbsoc = $db->result( $i, "nbsoc");
  
  print "<tr $bc1><td><b>$ftc Nombre de logins societes</font></b></td>\n";
  print "<td align=\"center\">$ftc $nbsoc</td>";
  print "</tr>";

  print "</td></tr></table></td></tr></table>";
  
  $db->close();

}
?>
