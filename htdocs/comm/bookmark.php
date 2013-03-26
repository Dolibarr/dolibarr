<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/comm/bookmark.php
 *      \brief      Page affichage des bookmarks
 */


require '../main.inc.php';


$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="bid";
$limit = $conf->liste_limit;


llxHeader();


/*
 * Actions
 */

if ($_GET["action"] == 'add')
{
    $bookmark=new Bookmark($db);
    $bookmark->fk_user=$user->id;
    $bookmark->url=$user->id;
    $bookmark->target=$user->id;
    $bookmark->title='xxx';
    $bookmark->favicon='xxx';

    $res=$bookmark->create();
    if ($res > 0)
    {
        header("Location: ".$_SERVER["PHP_SELF"]);
    }
    else
    {
        $mesg='<div class="error">'.$bookmark->error.'</div>';
    }
}

if ($_GET["action"] == 'delete')
{
    $bookmark=new Bookmark($db);
    $bookmark->id=$_GET["bid"];
    $bookmark->url=$user->id;
    $bookmark->target=$user->id;
    $bookmark->title='xxx';
    $bookmark->favicon='xxx';

    $res=$bookmark->remove();
    if ($res > 0)
    {
        header("Location: ".$_SERVER["PHP_SELF"]);
    }
    else
    {
        $mesg='<div class="error">'.$bookmark->error.'</div>';
    }
}



print_fiche_titre($langs->trans("Bookmarks"));

$sql = "SELECT s.rowid, s.nom as name, b.dateb as dateb, b.rowid as bid, b.fk_user, b.url, b.target, u.lastname, u.firstname";
$sql.= " FROM ".MAIN_DB_PREFIX."bookmark as b, ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."user as u";
$sql.= " WHERE b.fk_soc = s.rowid AND b.fk_user=u.rowid";
if (! $user->admin) $sql.= " AND b.fk_user = ".$user->id;
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit, $offset);

$resql=$db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;

  if ($sortorder == "DESC") $sortorder="ASC";
  else $sortorder="DESC";

  print "<table class=\"noborder\" width=\"100%\">";

  print "<tr class=\"liste_titre\">";
  //print "<td>&nbsp;</td>";
  print_liste_field_titre($langs->trans("Id"),$_SERVER["PHP_SELF"],"bid","","",'align="center"',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Author"),$_SERVER["PHP_SELF"],"u.lastname","","","",$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"b.dateb","","",'align="center"',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","","",$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Url"),$_SERVER["PHP_SELF"],"b.url","","",'',$sortfield,$sortorder);
  print "<td>".$langs->trans("Target")."</td>";
  print "<td>&nbsp;</td>";
  print "</tr>\n";

  $var=True;
  while ($i < $num)
    {
      $obj = $db->fetch_object($resql);

      $var=!$var;
      print "<tr $bc[$var]>";
      //print "<td>" . ($i + 1 + ($limit * $page)) . "</td>";
      print "<td align=\"center\"><b>".$obj->bid."</b></td>";
      print "<td><a href='".DOL_URL_ROOT."/user/fiche.php?id=".$obj->fk_user."'>".img_object($langs->trans("ShowUser"),"user").' '.$obj->lastname." ".$obj->firstname."</a></td>\n";
      print '<td align="center">'.dol_print_date($db->jdate($obj->dateb))."</td>";
      print "<td><a href=\"index.php?socid=".$obj->rowid."\">".img_object($langs->trans("ShowCompany"),"company").' '.$obj->name."</a></td>\n";
      print '<td align="center">'.$obj->url."</td>";
      print '<td align="center">'.$obj->target."</td>";
      print "<td><a href=\"bookmark.php?action=delete&bid=".$obj->bid."\">".img_delete()."</a></td>\n";
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free($resql);
}
else
{
  dol_print_error($db);
}


$db->close();

llxFooter();
?>
