<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */
require("./pre.inc.php3");

require("../societe.class.php3");
require("../contact.class.php3");
require("cactioncomm.class.php3");
require("actioncomm.class.php3");
llxHeader();
$db = new Db();
if ($sortfield == "") {
  $sortfield="a.datea";
}
if ($sortorder == "") {
  $sortorder="DESC";
}

if ($page == -1) { $page = 0 ; }
$limit = 26;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$bc1="bgcolor=\"#90c090\"";
$bc2="bgcolor=\"#b0e0b0\"";
/*
 *
 *
 *
 */
if ($action=='add') {
  $actioncomm = new ActionComm($db);
  $actioncomm->date = $date;
  $actioncomm->type = $actionid;
  $actioncomm->contact = $contactid;
  $actioncomm->user = $user->id;
  $actioncomm->societe = $socid;
  $actioncomm->note = $note;

  $actioncomm->add();
}
/*
 *
 *
 *
 */
if ($action=='create' && $actionid && $contactid) {
  $caction = new CActioncomm();
  $caction->fetch($db, $actionid);

  $contact = new Contact($db);
  $contact->fetch($contactid);

  $societe = new Societe($db);
  $societe->get_nom($socid);


  print '<form action="actioncomm.php3" method="post">';
  print '<input type="hidden" name="action" value="add">';
  print '<input type="hidden" name="date" value="'.$db->idate(time()).'">';
  print '<input type="hidden" name="actionid" value="'.$actionid.'">';
  print '<input type="hidden" name="contactid" value="'.$contactid.'">';
  print '<input type="hidden" name="socid" value="'.$socid.'">';

  print '<table width="100%" border="1" cellspacing="0" cellpadding="3">';
  print '<tr><td width="10%">Action</td><td bgcolor="#e0e0e0"><b>'.$caction->libelle.'</td></tr>';
  print '<tr><td width="10%">Société</td><td width="40%"bgcolor="#e0e0e0"><b><a href="index.php3?socid='.$socid.'">'.$societe->nom.'</a></td></tr>';
  print '<tr><td width="10%">Contact</td><td width="40%"bgcolor="#e0e0e0"><b>'.$contact->fullname.'</td></tr>';
  print '<td>Date</td><td>'.strftime('%d %B %Y %H:%M',time()).'</td></tr>';
  print '<tr><td valign="top">Commentaire</td><td>';
  print '<textarea cols="60" rows="6" name="note"></textarea></td></tr>';

  print '<tr><td width="10%">A recontacter le</td><td width="40%"bgcolor="#e0e0e0"><b>'.$contact->fullname.'</td></tr>';

  print '<tr><td colspan="2" align="center"><input type="submit"></td></tr>';

  print '</form></table>';
  $limit = 10;
  print '<p>Vos 10 dernières actions';
}
/*
 *
 *
 *
 */
if ($id) {
  print '<table width="100%" border="1" cellspacing="0" cellpadding="3">';
  print '<tr><td width="10%">Action</td><td colspan="3" bgcolor="#e0e0e0"><b>'.$libelle.'</td></tr>';
  print '<tr><td width="10%">Société</td><td width="40%"bgcolor="#e0e0e0"><b>'.$nom.'</td>';
  print '<td width="10%">Contact</td><td width="40%"bgcolor="#e0e0e0"><b>'.$fullname.'</td></tr>';
  print '<tr><td>Auteur</td><td>'.$fullname.'</td>';
  print '<td>Date</td><td>'.strftime('%d %B %Y %H:%M',time()).'</td></tr>';
  print '<tr><td valign="top">Commentaire</td><td colspan="3">';
  print '<textarea cols="60" rows="6" name="note"></textarea></td></tr>';

  print '</table>';
  $limit = 10;
  print '<p>Vos 10 dernières actions';
}

/*
 *
 *  Liste
 *
 */

if ($socid) {

  $sql = "SELECT s.nom as societe, s.idp as socidp,a.id,".$db->pdate("a.datea")." as da, c.libelle, u.code, a.note, u.name, u.firstname ";
  $sql .= " FROM actioncomm as a, c_actioncomm as c, societe as s, llx_user as u";
$sql .= " WHERE a.fk_soc = s.idp AND c.id=a.fk_action AND a.fk_user_author = u.rowid";
 
 if ($type) {
   $sql .= " AND c.id = $type";
 }

 $sql .= " ORDER BY $sortfield $sortorder ";

 if ( $db->query($sql) ) {
   $num = $db->num_rows();
   $i = 0;
   print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
   print "<TR bgcolor=\"orange\">";
   print '<TD>Date</TD>';
   print '<TD>Societe</td>';
   print "<TD>Note</TD>";
   print "</TR>\n";
   $var=True;
   while ($i < $num) {
     $obj = $db->fetch_object( $i);
     if ($i == 0) {
       print "<TR bgcolor=\"orange\">";
       print '<TD>Auteur</TD>';
       print "<TD width=\"30%\"><a href=\"index.php3?socid=$obj->socidp\">$obj->societe</A></TD>\n";
       print "<TD>&nbsp;</TD>";
       print "</TR>\n";
     }
     $var=!$var;
     
     if (!$var) {
       $bc=$bc1;
     } else {
       $bc=$bc2;
     }
     print "<TR $bc>";
     print "<TD>" .strftime("%d %b %Y %H:%M",$obj->da)."</TD>\n"; 

     print "<TD width=\"30%\"><a href=\"index.php3?socid=$obj->socidp\">$obj->societe</A></TD>\n";
     print '<TD align="left" valign="top" rowspan="2">'.nl2br($obj->note).'</TD>';
     print "</TR>\n";

     print "<TR $bc>";
     print "<TD width=\"20%\">$obj->firstname $obj->name</TD>\n";
     print '<TD width="30%"><a href="actioncomm.php3?id='.$obj->id.'">'.$obj->libelle.'</a></td>';
     print "</TR>\n";

     $i++;
   }
   print "</TABLE>";
   $db->free();
 }

} else {


$sql = "SELECT s.nom as societe, s.idp as socidp,a.id,".$db->pdate("a.datea")." as da, a.datea, c.libelle, u.code ";
$sql .= " FROM actioncomm as a, c_actioncomm as c, societe as s, llx_user as u";
$sql .= " WHERE a.fk_soc = s.idp AND c.id=a.fk_action AND a.fk_user_author = u.rowid";

if ($type) {
  $sql .= " AND c.id = $type";
}

$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit( $limit, $offset);


if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;
  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<TR bgcolor=\"orange\">";
  print '<TD colspan="4">Date</TD>';
  print '<TD><a href="'.$PHP_SELF.'?sortfield=lower(s.nom)&sortorder=ASC">Societe</a></td>';
  print '<TD><a href="'.$PHP_SELF.'?sortfield=c.libelle">Action</a></TD>';
  print "<TD>Auteur</TD>";
  print "</TR>\n";
  $var=True;
  while ($i < $num) {
    $obj = $db->fetch_object( $i);
    
    $var=!$var;
    
    if (!$var) {
      $bc=$bc1;
    } else {
      $bc=$bc2;
    }
    print "<TR $bc>";
    print "<TD>" .strftime("%d",$obj->da)."</TD>\n"; 
    print "<TD>" .strftime("%b",$obj->da)."</TD>\n"; 
    print "<TD>" .strftime("%Y",$obj->da)."</TD>\n"; 
    print "<TD>" .strftime("%H:%M",$obj->da)."</TD>\n";
    print "<TD width=\"50%\"><a href=\"index.php3?socid=$obj->socidp\">$obj->societe</A></TD>\n";
    
    print '<TD width="30%"><a href="actioncomm.php3?id='.$obj->id.'">'.$obj->libelle.'</a></td>';
    print "<TD width=\"20%\">$obj->code</TD>\n";
    print "<TD align=\"center\">$obj->stcomm</TD>\n";
    print "</TR>\n";
    $i++;
  }
  print "</TABLE>";
  $db->free();
}


}

$db->close();

llxFooter();
?>
