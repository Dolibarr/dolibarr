<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

require("../../../contact.class.php");
require("../../../cactioncomm.class.php");
require("../../../actioncomm.class.php");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

if ($_GET["action"] == 'pdf')
{
  $cat = new CommActionRapport($db, $_GET["month"], $_GET["year"]);
  $cat->generate($_GET["id"]);
}


llxHeader();

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

$sql = "SELECT count(*) as cc, date_format(a.datea, '%m/%Y') as df";
$sql .= ", date_format(a.datea, '%m') as month";
$sql .= ", date_format(a.datea, '%Y') as year";
$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
$sql .= " GROUP BY date_format(a.datea, '%m/%Y') ";
$sql .= " ORDER BY date_format(a.datea, '%Y %m') DESC";

  
  
if ( $db->query($sql) )
{
  $num = $db->num_rows();
  
  print_barre_liste("Liste des actions commerciales réalisées ou à faire", $page, $PHP_SELF,'',$sortfield,$sortorder,'',$num);
  
  $i = 0;
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print '<td>Date</td>';
  print '<td align="center">Nombre</td>';
  print '<td>Action</td>';
  print "</tr>\n";
  $var=True;
  while ($i < min($num,$limit))
    {
      $obj = $db->fetch_object( $i);
      
      $var=!$var;
    
      print "<TR $bc[$var]>";

      print "<td>$obj->df</TD>\n";
      print '<td align="center">'.$obj->cc.'</td>';

      print '<td><a href="index.php?action=pdf&amp;month='.$obj->month.'&amp;year='.$obj->year.'">'.img_file_new().'</a></td>';

      $root = DOL_DOCUMENT_ROOT; 
      $dir =  "/document/rapport/comm/actions/";
      $name = "rapport-action-".$obj->month."-".$obj->year.".pdf";
      $file = $root . $dir . $name;
      $url = $dir . $name;
	
      if (file_exists($file))
	{
	  print '<td ><a href="'.DOL_URL_ROOT.$url.'">'.img_pdf().'</a></td>';
	  print '<td>'.strftime("%d %b %Y %H:%M:%S",filemtime($file)).'</td>';
	  print '<td>'.filesize($file). ' bytes</td>';
	}

      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
}
else
{
  print $db->error() . ' ' . $sql ;
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
