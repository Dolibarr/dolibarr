<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("../contact.class.php");
require("../lib/webcal.class.php");
require("../cactioncomm.class.php");
require("../actioncomm.class.php");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}


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

$sql = "SELECT s.idp, s.nom, s.ville, ".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea,  st.libelle as stcomm, s.prefix_comm FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."c_stcomm as st WHERE s.fk_stcomm = st.id AND s.client=1";

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
      Header("Location: fiche.php?socid=$obj->idp");
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
      print "<TD><a href=\"fiche.php?socid=$obj->idp\">$obj->nom</A></td>\n";
      print "<TD>".$obj->ville."&nbsp;</TD>\n";
      print "<TD align=\"center\">$obj->prefix_comm&nbsp;</TD>\n";

      if ($user->societe_id == 0)
	{
	  if ($user->rights->propale->creer)
	    {
	      print "<TD align=\"center\"><a href=\"addpropal.php?socidp=$obj->idp&action=create\">[Propal]</A></td>\n";
	    }
	  else
	    {
	      print "<td>&nbsp;</td>\n";
	    }
	  if ($conf->fichinter->enabled)
	    {
	      print "<TD align=\"center\"><a href=\"../fichinter/fiche.php?socidp=$obj->idp&action=create\">[Fiche Inter]</A></td>\n";
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
