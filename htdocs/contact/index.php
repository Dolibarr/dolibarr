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


$page = $_GET["page"];
$sortfield = $_GET["sortfield"];
$sortorder = $_GET["sortorder"];

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

if ($_GET["view"] == 'phone') { $text="(Vue Téléphones)"; }
if ($_GET["view"] == 'mail') { $text="(Vue EMail)"; }
if ($_GET["view"] == 'recent') { $text="(Récents)"; }

$titre = "Liste des contacts $text";

/*
 *
 * Mode liste
 *
 *
 */

$sql = "SELECT s.idp, s.nom, p.idp as cidp, p.name, p.firstname, p.email, p.phone, p.phone_mobile, p.fax ";
$sql .= "FROM ".MAIN_DB_PREFIX."socpeople as p";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON (s.idp = p.fk_soc)";


if (strlen($_GET["userid"]))  // statut commercial
{
  $sql .= " WHERE p.fk_user=".$_GET["userid"];
}

if (strlen($_GET["begin"])) // filtre sur la premiere lettre du nom
{
  $sql .= " WHERE upper(p.name) like '".$_GET["begin"]."%'";
}

if (strlen($_GET["contactname"]) && $_GET["mode"] == "search") // acces a partir du module de recherche
{
  $sql .= " WHERE ( lower(p.name) like '%".strtolower($_GET["contactname"])."%' OR lower(p.firstname) like '%".strtolower($_GET["contactname"])."%') ";
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

  print_barre_liste($titre ,$page, "index.php", '&amp;begin='.$_GET["begin"].'&amp;view='.$_GET["view"].'&amp;userid='.$_GET["userid"], $sortfield, $sortorder,'',$num);

  print "<div align=\"center\">";

  print "| <A href=\"index.php?page=$pageprev&stcomm=$stcomm&sortfield=$sortfield&sortorder=$sortorder&aclasser=$aclasser&coord=$coord\">*</A>\n| ";
  for ($ij = 65 ; $ij < 91; $ij++) {
    print "<A href=\"index.php?begin=" . chr($ij) . "&stcomm=$stcomm\" class=\"T3\">";
    
    if ($_GET["begin"] == chr($ij) )
      {
	print  "<b>&gt;" . chr($ij) . "&lt;</b>" ; 
      } 
    else
      {
	print  chr($ij);
      } 
    print "</A> | ";
  }
  print "</div>";
  
  if ($sortorder == "DESC") 
    {
      $sortorder="ASC";
    } 
  else
    {
      $sortorder="DESC";
    }
  print '<p><table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>';
  print_liste_field_titre("Nom","index.php","lower(p.name)", $begin);
  print "</td><td>";
  print_liste_field_titre("Prénom","index.php","lower(p.firstname)", $begin);
  print "</td><td>";
  print_liste_field_titre("Société","index.php","lower(s.nom)", $begin);
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

  print "</tr>\n";
  $var=True;
  while ($i < min($num,$limit))
    {
      $obj = $db->fetch_object( $i);
    
      $var=!$var;

      print "<tr $bc[$var]>";

      print '<td valign="center">';
      print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->cidp.'">';
      print img_file();
      print '</a>&nbsp;<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$obj->cidp.'">'.$obj->name.'</a></td>';
      print "<TD>$obj->firstname</TD>";
      
      print '<td>';
      if ($obj->nom)
	{
	  print '<a href="contact.php?socid='.$obj->idp.'"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filter.png" border="0" alt="Filtre"></a>&nbsp;';
	}
      print "<a href=\"".DOL_URL_ROOT."/comm/fiche.php?socid=$obj->idp\">$obj->nom</A></td>\n";
      
      
      print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&amp;actionid=1&amp;contactid='.$obj->cidp.'&amp;socid='.$obj->idp.'">'.dolibarr_print_phone($obj->phone).'</a>&nbsp;</td>';

      if ($_GET["view"] == 'phone')
	{      
	  print '<td>'.dolibarr_print_phone($obj->phone_mobile).'&nbsp;</td>';
      
	  print '<td>'.dolibarr_print_phone($obj->fax).'&nbsp;</td>';
	}
      else
	{
	  print '<td><a href="mailto:'.$obj->email.'">'.$obj->email.'</a>&nbsp;</td>';
	}

      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
}
else
{
  print $db->error();
  print "<br>".$sql;
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
