<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */
require("./pre.inc.php");
require("./contact.class.php");
require("./lib/webcal.class.php");
require("./cactioncomm.class.php");
require("./actioncomm.class.php");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

llxHeader();

if ($sortorder == "") {
  $sortorder="ASC";
}
if ($sortfield == "") {
  $sortfield="nom";
}

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


/*
 * Recherche
 *
 *
 */
if ($mode == 'search') {
  if ($mode-search == 'soc') {
    $sql = "SELECT s.idp FROM ".MAIN_DB_PREFIX."societe as s ";
    $sql .= " WHERE lower(s.nom) like '%".strtolower($socname)."%'";
  }
      
  if ( $db->query($sql) ) {
    if ( $db->num_rows() == 1) {
      $obj = $db->fetch_object(0);
      $socid = $obj->idp;
    }
    $db->free();
  }
}

/*
 * Mode Liste
 *
 *
 */

$sql = "SELECT s.idp, s.nom, s.ville, ".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea,  st.libelle as stcomm, s.prefix_comm, s.client, s.fournisseur";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."c_stcomm as st WHERE s.fk_stcomm = st.id";

if ($user->societe_id > 0) {
  $sql .= " AND s.idp = " . $user->societe_id;
}


if (strlen($stcomm)) {
  $sql .= " AND s.fk_stcomm=$stcomm";
}

if (strlen($begin)) {
  $sql .= " AND upper(s.nom) like '$begin%'";
}

if ($socname) {
  $sql .= " AND lower(s.nom) like '%".strtolower($socname)."%'";
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;

  $params = "&amp;socname=$socname";

  print_barre_liste("Liste des societes", $page, $PHP_SELF,$params,$sortfield,$sortorder,'',$num);
    
  if ($sortorder == "DESC") 
    {
      $sortorder="ASC";
    } 
  else
    {
      $sortorder="DESC";
    }

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print '<td>';
  print_liste_field_titre("Société",$PHP_SELF,"s.nom", $params);
  print "</td><td>";
  print_liste_field_titre("Ville",$PHP_SELF,"s.ville",$params);
  print '</td><td colspan="2" align="center">Fiches</td>';
  print "</tr>\n";
  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object( $i);    
      $var=!$var;    
      print "<tr $bc[$var]><td>";
      print "<a href=\"soc.php?socid=$obj->idp\">";
      print img_file();
      print "</a>&nbsp;<a href=\"soc.php?socid=$obj->idp\">$obj->nom</a></td>\n";
      print "<td>".$obj->ville."&nbsp;</TD>\n";
      print '<td align="center">';
      if ($obj->client==1)
	{
	  print "<a href=\"comm/fiche.php?socid=$obj->idp\">Client</a>\n";
	}
      elseif ($obj->client==2)
	{
	  print "<a href=\"comm/prospect/fiche.php?id=$obj->idp\">Prospect</a>\n";
	}
      else
	{
	  print "&nbsp;";
	}
      print "</td><td align=\"center\">";
      if ($obj->fournisseur)
	{
	  print '<a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$obj->idp.'">fournisseur</a>';
	}
      else
	{
	  print "&nbsp;";
	}
      
      print '</td></tr>'."\n";
      $i++;
    }

  print "</table>";
  $db->free();
}
else
{
  print $db->error() . ' ' . $sql;
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
