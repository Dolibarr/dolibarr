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
require("../contact.class.php3");
require("../lib/webcal.class.php3");
require("../cactioncomm.class.php3");
require("../actioncomm.class.php3");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

$db = new Db();

if ($action=='add_action') {
  /*
   * Vient de actioncomm.php3
   *
   */
  $actioncomm = new ActionComm($db);
  $actioncomm->date = $date;
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
  $webcal->add($user, $todo->date, $societe->nom, $todo->libelle);
}


if ($action == 'attribute_prefix')
{
  $societe = new Societe($db, $socid);
  $societe->attribute_prefix($db, $socid);
}

if ($action == 'recontact')
{
  $dr = mktime(0, 0, 0, $remonth, $reday, $reyear);
  $sql = "INSERT INTO llx_soc_recontact (fk_soc, datere, author) VALUES ($socid, $dr,'". $GLOBALS["REMOTE_USER"]."')";
  $result = $db->query($sql);
}

if ($action == 'note')
{
  $sql = "UPDATE llx_societe SET note='$note' WHERE idp=$socid";
  $result = $db->query($sql);
}

if ($action == 'stcomm')
{
  if ($stcommid <> 'null' && $stcommid <> $oldstcomm)
    {
      $sql = "INSERT INTO socstatutlog (datel, fk_soc, fk_statut, author) ";
      $sql .= " VALUES ('$dateaction',$socid,$stcommid,'" . $GLOBALS["REMOTE_USER"] . "')";
      $result = @$db->query($sql);
      
      if ($result)
	{
	  $sql = "UPDATE llx_societe SET fk_stcomm=$stcommid WHERE idp=$socid";
	  $result = $db->query($sql);
	}
      else
	{
	  $errmesg = "ERREUR DE DATE !";
	}
    }

  if ($actioncommid)
    {
      $sql = "INSERT INTO llx_actioncomm (datea, fk_action, fk_soc, fk_user_author) VALUES ('$dateaction',$actioncommid,$socid,'" . $user->id . "')";
      $result = @$db->query($sql);
      
      if (!$result)
	{
	  $errmesg = "ERREUR DE DATE !";
	}
    }
}

/*
 * Recherche
 *
 *
 */
/*
if ($mode == 'search')
{
  if ($mode-search == 'soc')
    {
      $sql = "SELECT s.idp FROM llx_societe as s ";
      $sql .= " WHERE lower(s.nom) like '%".strtolower($socname)."%'";
    }
  
  if ( $db->query($sql) )
    {
      if ( $db->num_rows() == 1)
	{
	  $obj = $db->fetch_object(0);
	  $socid = $obj->idp;
	}
      $db->free();
    }
}
*/
if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

/*
 * Mode Liste
 *
 *
 *
 */

$sql = "SELECT s.idp, s.nom, s.ville, ".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea,  st.libelle as stcomm, s.prefix_comm FROM llx_societe as s, c_stcomm as st WHERE s.fk_stcomm = st.id AND s.client=1";

if (strlen($stcomm))
{
  $sql .= " AND s.fk_stcomm=$stcomm";
}

if (strlen($begin))
{
  $sql .= " AND upper(s.nom) like '$begin%'";
}

if ($user->societe_id)
{
  $sql .= " AND s.idp = " .$user->societe_id;
}

if ($socname)
{
  $sql .= " AND lower(s.nom) like '%".strtolower($socname)."%'";
  $sortfield = "lower(s.nom)";
  $sortorder = "ASC";
}

if ($sortorder == "")
{
  $sortorder="DESC";
}
if ($sortfield == "")
{
  $sortfield="s.datec";
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit +1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();

  if ($num == 1)
    {
      $obj = $db->fetch_object(0);
      Header("Location: fiche.php3?socid=$obj->idp");
    }
  else
    {
      llxHeader();
    }


  print_barre_liste("Liste des clients", $page, $PHP_SELF,"",$sortfield,$sortorder,'',$num);

  $i = 0;
  
  if ($sortorder == "DESC")
    {
      $sortorder="ASC";
    }
  else
    {
      $sortorder="DESC";
    }
  print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';
  print '<TR class="liste_titre">';
  print "<TD valign=\"center\">";
  print_liste_field_titre("Société",$PHP_SELF,"s.nom");
  print "</td><TD>Ville</TD>";
  print "<TD align=\"center\">Préfix</td><td colspan=\"2\">&nbsp;</td>";
  print "</TR>\n";
  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object( $i);
      
      $var=!$var;

      print "<TR $bc[$var]>";
      print "<TD><a href=\"fiche.php3?socid=$obj->idp\">$obj->nom</A></td>\n";
      print "<TD>".$obj->ville."&nbsp;</TD>\n";
      print "<TD align=\"center\">$obj->prefix_comm&nbsp;</TD>\n";

      if ($user->societe_id == 0)
	{
	  if ($user->rights->propale->creer)
	    {
	      print "<TD align=\"center\"><a href=\"addpropal.php3?socidp=$obj->idp&action=create\">[Propal]</A></td>\n";
	    }
	  else
	    {
	      print "<td>&nbsp;</td>\n";
	    }
	  if ($conf->fichinter->enabled)
	    {
	      print "<TD align=\"center\"><a href=\"../fichinter/fiche.php3?socidp=$obj->idp&action=create\">[Fiche Inter]</A></td>\n";
	    }
	  else
	    {
	      print "<TD>&nbsp;</TD>\n";
	    }
	}
      else
	{
	  print "<TD>&nbsp;</TD>\n";
	  print "<TD>&nbsp;</TD>\n";
	}
      print "</TR>\n";
      $i++;
    }
  print "</TABLE>";
  $db->free();
}
else
{
  print $db->error() . ' ' . $sql;
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
