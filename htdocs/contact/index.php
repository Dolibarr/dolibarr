<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

llxHeader();


if ($sortorder == "")
{
  $sortorder="ASC";
}
if ($sortfield == "")
{
  $sortfield="p.name";
}

if ($page < 0) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

if ($_GET["view"] == 'phone')
{
  $titre = "Liste des contacts (Vue téléphone)";
}
else
{
  $titre = "Liste des contacts";
}

/*
 *
 * Mode liste
 *
 *
 */

$sql = "SELECT s.idp, s.nom, p.idp as cidp, p.name, p.firstname, p.email, p.phone, p.phone_mobile, p.fax ";
$sql .= "FROM llx_socpeople as p";
$sql .= " LEFT JOIN llx_societe as s ON (s.idp = p.fk_soc)";


if (strlen($_GET["userid"]))  // statut commercial
{
  $sql .= " WHERE p.fk_user=".$_GET["userid"];
}

if (strlen($begin)) // filtre sur la premiere lettre du nom
{
  $sql .= " AND upper(p.name) like '$begin%'";
}

if ($contactname) // acces a partir du module de recherche
{
  $sql .= " AND ( lower(p.name) like '%".strtolower($contactname)."%' OR lower(p.firstname) like '%".strtolower($contactname)."%') ";
  $sortfield = "lower(p.name)";
  $sortorder = "ASC";
}

if ($socid) 
{
  $sql .= " AND s.idp = $socid";
}

if($_GET["view"] == "recent")
{
  $sql .= " ORDER BY p.datec DESC " . $db->plimit( $limit + 1, $offset);
}
else
{
  $sql .= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit + 1, $offset);
}

$result = $db->query($sql);

if ($result) 
{
  $num = $db->num_rows();
  $i = 0;

  print_barre_liste($titre ,$page, $PHP_SELF, '&amp;view='.$_GET["view"].'&amp;userid='.$_GET["userid"], $sortfield, $sortorder,'',$num);
  
  if ($sortorder == "DESC") 
    {
      $sortorder="ASC";
    } 
  else
    {
      $sortorder="DESC";
    }
  print "<p><table border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print '<tr class="liste_titre"><td>';
  print_liste_field_titre("Nom",$PHP_SELF,"lower(p.name)", $begin);
  print "</td><td>";
  print_liste_field_titre("Prénom",$PHP_SELF,"lower(p.firstname)", $begin);
  print "</td><td>";
  print_liste_field_titre("Société",$PHP_SELF,"lower(s.nom)", $begin);
  print '</td>';

  print '<td>Téléphone</td>';

  if ($_GET["view"] == 'phone')
    {
      print '<td>Portable</td>';
      print '<td>Fax</td>';
    }
  else
    {
      print '<td>email</td>';
    }

  print "</TR>\n";
  $var=True;
  while ($i < min($num,$limit))
    {
      $obj = $db->fetch_object( $i);
    
      $var=!$var;

      print "<tr $bc[$var]>";

      print '<td valign="center">';
      print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->cidp.'">';
      print img_file();
      print '</a><a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->cidp.'">'.$obj->name.'</a></td>';
      print "<TD>$obj->firstname</TD>";
      
      print '<td>';
      if ($obj->nom)
	{
	  print '<a href="contact.php?socid='.$obj->idp.'"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filter.png" border="0" alt="Filtre"></a>&nbsp;';
	}
      print "<a href=\"".DOL_URL_ROOT."/comm/fiche.php?socid=$obj->idp\">$obj->nom</A></td>\n";
      
      
	  print '<td><a href="action/fiche.php?action=create&amp;actionid=1&amp;contactid='.$obj->cidp.'&amp;socid='.$obj->idp.'">'.$obj->phone.'</a>&nbsp;</td>';

      if ($_GET["view"] == 'phone')
	{
      
	  print '<td>'.$obj->phone_mobile.'&nbsp;</td>';
      
	  print '<td>'.$obj->fax.'&nbsp;</td>';

	}
      else
	{
	  print '<td><a href="action/fiche.php?action=create&amp;actionid=4&amp;contactid='.$obj->cidp.'&amp;socid='.$obj->idp.'">'.$obj->email.'</a>&nbsp;</td>';
	}

      print "</TR>\n";
      $i++;
    }
  print "</TABLE>";
  $db->free();
} else {
  print $db->error();
  print "<br>".$sql;
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
