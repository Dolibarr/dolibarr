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


require("../contact.class.php3");
require("../lib/webcal.class.php3");
require("cactioncomm.class.php3");
require("actioncomm.class.php3");
require("../todocomm.class.php3");

llxHeader();
$db = new Db();


if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
/*
 *
 *
 *
 */
if ($action=='add_action') {
  $contact = new Contact($db);
  $contact->fetch($contactid);


  $actioncomm = new ActionComm($db);

  if ($actionid == 5) {
    $actioncomm->date = $db->idate(mktime($heurehour,$heuremin,0,$remonth,$reday,$reyear));
  } else {
    $actioncomm->date = $date;
  }
  $actioncomm->type = $actionid;
  $actioncomm->contact = $contactid;

  $actioncomm->societe = $socid;
  $actioncomm->note = $note;

  $actioncomm->add($user);


  $societe = new Societe($db);
  $societe->fetch($socid);


  $todo = new TodoComm($db);
  $todo->date = mktime(12,0,0,$remonth, $reday, $reyear);

  $todo->libelle = $todo_label;

  $todo->societe = $societe->id;
  $todo->contact = $contactid;

  $todo->note = $todo_note;

  $todo->add($user);

  $webcal = new Webcal();

  $webcal->heure = $heurehour . $heuremin . '00';
  $webcal->duree = ($dureehour * 60) + $dureemin;

  if ($actionid == 5) {
    $libelle = "Rendez-vous avec ".$contact->fullname;
    $libelle .= "\n" . $todo->libelle;
  } else {
    $libelle = $todo->libelle;
  }


  $webcal->add($user, $todo->date, $societe->nom, $libelle);
  
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

  /*
   * Rendez-vous
   *
   */
  if ($actionid == 5) 
    {
      print '<form action="'.$PHP_SELF.'?socid='.$socid.'" method="post">';
      print '<input type="hidden" name="action" value="add_action">';

      print '<input type="hidden" name="date" value="'.$db->idate(time()).'">';
      print '<input type="hidden" name="actionid" value="'.$actionid.'">';
      print '<input type="hidden" name="contactid" value="'.$contactid.'">';
      print '<input type="hidden" name="socid" value="'.$socid.'">';
      
      print '<table width="100%" border="1" cellspacing="0" cellpadding="3">';

      print '<tr><td colspan="2" bgcolor="#e0e0e0"><div class="titre">Rendez-vous</div></td></tr>';
      print '<tr><td width="10%">Société</td><td width="40%"bgcolor="#e0e0e0">';
      print '<b><a href="index.php3?socid='.$socid.'">'.$societe->nom.'</a></td></tr>';
      print '<tr><td width="10%">Contact</td><td width="40%"bgcolor="#e0e0e0"><b>'.$contact->fullname.'</td></tr>';
      print '<tr><td width="10%">Date</td><td width="40%">';
      print_date_select();
      print '</td></tr>';
      print '<tr><td width="10%">Heure</td><td width="40%">';
      print_heure_select("heure",8,20);
      print '</td></tr>';
      print '<tr><td width="10%">Durée</td><td width="40%">';
      print_duree_select("duree");
      print '</td></tr>';

      print '<tr><td valign="top">Commentaire</td><td>';
      print '<textarea cols="60" rows="6" name="todo_note"></textarea></td></tr>';
      
      print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer"></td></tr>';
  
      print '</form></table>';

    }
  /* 
   *Autre action
   *
   *
   */   
  else 
    {

      print '<form action="index.php3?socid='.$socid.'" method="post">';
      print '<input type="hidden" name="action" value="add_action">';

      print '<input type="hidden" name="date" value="'.$db->idate(time()).'">';
      print '<input type="hidden" name="actionid" value="'.$actionid.'">';
      print '<input type="hidden" name="contactid" value="'.$contactid.'">';
      print '<input type="hidden" name="socid" value="'.$socid.'">';
      
      print '<table width="100%" border="1" cellspacing="0" cellpadding="3">';
      
      print '<tr><td colspan="2" bgcolor="#e0e0e0"><div class="titre">Action effectuée</div></td></tr>';
      
      print '<tr><td width="10%">Action</td><td bgcolor="#e0e0e0"><b>'.$caction->libelle.'</td></tr>';
      print '<tr><td width="10%">Société</td><td width="40%"bgcolor="#e0e0e0">';
      print '<b><a href="index.php3?socid='.$socid.'">'.$societe->nom.'</a></td></tr>';
      print '<tr><td width="10%">Contact</td><td width="40%"bgcolor="#e0e0e0"><b>'.$contact->fullname.'</td></tr>';
      print '<td>Date</td><td>'.strftime('%d %B %Y %H:%M',time()).'</td></tr>';
      print '<tr><td valign="top">Commentaire</td><td>';
      print '<textarea cols="60" rows="6" name="note"></textarea></td></tr>';
      
      print '<tr><td colspan="2" bgcolor="#e0e0e0"><div class="titre">Prochaine Action à faire</div></td></tr>';

      print '<tr><td width="10%">Date</td><td width="40%">';
      print_date_select();
      print '</td></tr>';
      print '<tr><td width="10%">Action</td><td><input type="text" name="todo_label" size="30"></td></tr>';
      print '<tr><td valign="top">Commentaire</td><td>';
      print '<textarea cols="60" rows="6" name="todo_note"></textarea></td></tr>';
      
      print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer"></td></tr>';
  
      print '</form></table>';
  
    }
}
/*
 *
 *
 *
 */
if ($id) {
  $act = new ActionComm($db);
  $act->fetch($id);

  print '<table width="100%" border="1" cellspacing="0" cellpadding="3">';
  print '<tr><td width="10%">Action</td><td colspan="3" bgcolor="#e0e0e0"><b>'.$act->type.'</td></tr>';
  print '<tr><td width="10%">Société</td><td width="40%"bgcolor="#e0e0e0"><b>'.$nom.'</td>';
  print '<td width="10%">Contact</td><td width="40%"bgcolor="#e0e0e0"><b>'.$fullname.'</td></tr>';
  print '<tr><td>Auteur</td><td>'.$fullname.'</td>';
  print '<td>Date</td><td>'.strftime('%d %B %Y %H:%M',time()).'</td></tr>';
  print '<tr><td valign="top">Commentaire</td><td colspan="3">';
  print nl2br($act->note).'</td></tr>';

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
  $societe = new Societe($db);
  $societe->fetch($socid);

  print_barre_liste("Liste des actions commerciales effectuées sur " . $societe->nom,$page, $PHP_SELF);

  $sql = "SELECT a.id,".$db->pdate("a.datea")." as da, c.libelle, u.code, a.note, u.name, u.firstname ";
  $sql .= " FROM actioncomm as a, c_actioncomm as c, llx_user as u";
$sql .= " WHERE a.fk_soc = $socid AND c.id=a.fk_action AND a.fk_user_author = u.rowid";
 
 if ($type) {
   $sql .= " AND c.id = $type";
 }

 $sql .= " ORDER BY a.datea DESC";

 if ( $db->query($sql) ) {
   $num = $db->num_rows();
   $i = 0;
   print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
   print '<TR class="liste_titre">';
   print '<TD>Date</TD>';
   print "<TD>Action</TD>";
   print "</TR>\n";
   $var=True;
   while ($i < $num) {
     $obj = $db->fetch_object( $i);

     $var=!$var;
     
     print "<TR $bc[$var]>";
     print "<TD width=\"10%\">" .strftime("%Y %b %d %H:%M",$obj->da)."</TD>\n"; 
     print '<TD width="30%"><a href="actioncomm.php3?id='.$obj->id.'">'.$obj->libelle.'</a></td>';
     print "</TR>\n";

     $i++;
   }
   print "</TABLE>";
   $db->free();
 } else {
   print $db->error() . '<br>' . $sql;
 }

} else {

  print_barre_liste("Liste des actions commerciales effectuées",$page, $PHP_SELF);

  $sql = "SELECT s.nom as societe, s.idp as socidp,a.id,".$db->pdate("a.datea")." as da, a.datea, c.libelle, u.code ";
  $sql .= " FROM actioncomm as a, c_actioncomm as c, societe as s, llx_user as u";
  $sql .= " WHERE a.fk_soc = s.idp AND c.id=a.fk_action AND a.fk_user_author = u.rowid";
  
  if ($type) {
    $sql .= " AND c.id = $type";
  }
  
  $sql .= " ORDER BY a.datea DESC";
  $sql .= $db->plimit( $limit, $offset);
  
  
  if ( $db->query($sql) ) {
    $num = $db->num_rows();
    $i = 0;
    print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
    print '<TR class="liste_titre">';
    print '<TD colspan="4">Date</TD>';
    print '<TD>Société</a></td>';
    print '<TD>Action</a></TD>';
    print "<TD>Auteur</TD>";
    print "</TR>\n";
    $var=True;
    while ($i < $num) {
      $obj = $db->fetch_object( $i);
      
      $var=!$var;
      
      print "<TR $bc[$var]>";

      if ($oldyear == strftime("%Y",$obj->da) ) {
	print '<td align="center">|</td>';
      } else {
	print "<TD>" .strftime("%Y",$obj->da)."</TD>\n"; 
	$oldyear = strftime("%Y",$obj->da);
      }

      if ($oldmonth == strftime("%Y%b",$obj->da) ) {
	print '<td align="center">|</td>';
      } else {
	print "<TD>" .strftime("%b",$obj->da)."</TD>\n"; 
	$oldmonth = strftime("%Y%b",$obj->da);
      }

      print "<TD>" .strftime("%d",$obj->da)."</TD>\n"; 
      print "<TD>" .strftime("%H:%M",$obj->da)."</TD>\n";

      print '<TD width="50%">';

      print '&nbsp;<a href="index.php3?socid='.$obj->socidp.'">'.$obj->societe.'</A></TD>';
      
      print '<TD width="30%"><a href="actioncomm.php3?id='.$obj->id.'">'.$obj->libelle.'</a></td>';
      print "<TD width=\"20%\">$obj->code</TD>\n";
      print "<TD align=\"center\">$obj->stcomm</TD>\n";
      print "</TR>\n";
      $i++;
    }
    print "</TABLE>";
    $db->free();
  } else {
    print $db->error() . ' ' . $sql ;
  }
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
