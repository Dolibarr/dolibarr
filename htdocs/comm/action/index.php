<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Éric Seigne <erics@rycks.com>
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

require("../../contact.class.php3");
require("../../lib/webcal.class.php3");
require("../../cactioncomm.class.php3");
require("../../actioncomm.class.php3");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

llxHeader();

$db = new Db();

/*
 *
 *
 *
 */
if ($action=='delete_action')
{
  $actioncomm = new ActionComm($db);
  $actioncomm->delete($actionid);
}
/*
 *
 */
if ($action=='add_action')
{
  $contact = new Contact($db);
  $contact->fetch($contactid);

  $actioncomm = new ActionComm($db);

  if ($actionid == 5)
    {
      $actioncomm->date = $db->idate(mktime($heurehour,$heuremin,0,$remonth,$reday,$reyear));
    }
  else
    {
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
 *  Liste
 *
 */

if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
if ($sortorder == "")
{
  $sortorder="DESC";
}
if ($sortfield == "")
{
  $sortfield="a.datea";
}


if ($socid) 
{
  $societe = new Societe($db);
  $societe->fetch($socid);

  $sql = "SELECT a.id,".$db->pdate("a.datea")." as da, c.libelle, u.code, a.note, u.name, u.firstname, a.fk_contact ";
  $sql .= " FROM llx_actioncomm as a, c_actioncomm as c, llx_user as u";
  $sql .= " WHERE a.fk_soc = $socid AND c.id=a.fk_action AND a.fk_user_author = u.rowid";
 
 if ($type)
   {
     $sql .= " AND c.id = $type";
   }

 $sql .= " ORDER BY $sortfield $sortorder, rowid DESC ";
 $sql .= $db->plimit($limit + 1,$offset);

 if ( $db->query($sql) )
   {
     $num = $db->num_rows();
     print_barre_liste("Liste des actions commerciales effectuées sur " . $societe->nom,$page, $PHP_SELF,'',$sortfield,$sortorder,'',$num);
     $i = 0;
     print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';
     print '<TR class="liste_titre">';
     print '<TD>Date</TD>';
     print "<TD>Action</TD>";
     print '<td>Contact</a></TD>';
     print "</TR>\n";
     $var=True;
     
     while ($i < min($num,$limit))
       {
	 $obj = $db->fetch_object( $i);
	 
	 $var=!$var;
     
	 print "<TR $bc[$var]>";
	 print "<TD width=\"10%\">" .strftime("%Y %b %d %H:%M",$obj->da)."</TD>\n"; 
	 print '<TD width="30%"><a href="fiche.php3?id='.$obj->id.'">'.$obj->libelle.'</a></td>';
	print '<td width="30%">';
	if ($obj->fk_contact)
	  {
	    $cont = new Contact($db);
	    $cont->fetch($obj->fk_contact);
	    print '<a href="'.DOL_URL_ROOT.'/comm/contact.php3?id='.$cont->id.'">'.$cont->fullname.'</a>';
	  }
	else
	  {
	    print "&nbsp;";
	  }
	print '</td>';
	 print "</TR>\n";
	 
	 $i++;
       }
     print "</TABLE>";
     $db->free();
   } else {
     print $db->error() . '<br>' . $sql;
   }
 
}
else
{
  $sql = "SELECT s.nom as societe, s.idp as socidp,a.id,".$db->pdate("a.datea")." as da, a.datea, c.libelle, u.code, a.fk_contact ";
  $sql .= " FROM llx_actioncomm as a, c_actioncomm as c, llx_societe as s, llx_user as u";
  $sql .= " WHERE a.fk_soc = s.idp AND c.id=a.fk_action AND a.fk_user_author = u.rowid AND a.percent = 100";
  
  if ($type)
    {
      $sql .= " AND c.id = $type";
    }
  
  $sql .= " ORDER BY a.datea DESC";
  $sql .= $db->plimit( $limit + 1, $offset);
  
  
  if ( $db->query($sql) )
    {
      $num = $db->num_rows();
      print_barre_liste("Liste des actions commerciales effectuées sur " . $societe->nom,$page, $PHP_SELF,'',$sortfield,$sortorder,'',$num);
      $i = 0;
      print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
      print '<TR class="liste_titre">';
      print '<TD colspan="4">Date</TD>';
      print '<TD>Société</a></td>';
      print '<TD>Action</a></TD>';
      print '<TD>Contact</a></TD>';
      print "<TD align=\"right\">Auteur</TD>";
      print "</TR>\n";
      $var=True;
      while ($i < min($num,$limit)) {
	$obj = $db->fetch_object( $i);
	
	$var=!$var;
	
	print "<TR $bc[$var]>";
	
	if ($oldyear == strftime("%Y",$obj->da) )
	  {
	    print '<td align="center">&nbsp;</td>';
	  }
	else
	  {
	    print "<TD>" .strftime("%Y",$obj->da)."</TD>\n"; 
	    $oldyear = strftime("%Y",$obj->da);
	  }
	
	if ($oldmonth == strftime("%Y%b",$obj->da) )
	  {
	    print '<td align="center">&nbsp;</td>';
	  }
	else
	  {
	    print "<TD>" .strftime("%b",$obj->da)."</TD>\n"; 
	    $oldmonth = strftime("%Y%b",$obj->da);
	  }
	
	print "<TD>" .strftime("%d",$obj->da)."</TD>\n"; 
	print "<TD>" .strftime("%H:%M",$obj->da)."</TD>\n";
	
	print '<TD width="20%">';
	
	print '&nbsp;<a href="'.DOL_URL_ROOT.'/comm/fiche.php3?socid='.$obj->socidp.'">'.$obj->societe.'</A></TD>';
	
	print '<TD width="30%"><a href="fiche.php3?id='.$obj->id.'">'.$obj->libelle.'</a></td>';
	/*
	 * Contact
	 */
	print '<td width="30%">';
	if ($obj->fk_contact)
	  {
	    $cont = new Contact($db);
	    $cont->fetch($obj->fk_contact);
	    print '<a href="'.DOL_URL_ROOT.'/comm/contact.php3?id='.$cont->id.'">'.$cont->fullname.'</a>';
	  }
	else
	  {
	    print "&nbsp;";
	  }
	print '</td>';
	/*
	 *
	 */
	print "<TD align=\"right\" width=\"20%\">$obj->code</TD>\n";
	
	print "</TR>\n";
	$i++;
      }
      print "</TABLE>";
      $db->free();
    }
  else
    {
      print $db->error() . ' ' . $sql ;
    }
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
