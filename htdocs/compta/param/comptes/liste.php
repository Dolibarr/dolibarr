<?PHP
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 *      \file       htdocs/compta/param/comptes/liste.php
 *      \ingroup    compta
 *      \brief      Onglet de gestion de parametrages des ventilations
 */

require("../../../main.inc.php");


llxHeader('','Compta - Liste des comptes');

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];
if ($sortorder == "") $sortorder="ASC";
if ($sortfield == "") $sortfield="cg.numero";

$offset = $conf->liste_limit * $page ;

/*
 * Mode Liste
 *
 *
 *
 */

$sql = "SELECT cg.rowid, cg.numero, cg.intitule, cg.date_creation as dc";

$sql .= " FROM ".MAIN_DB_PREFIX."compta_compte_generaux as cg";

if (dol_strlen(trim($_GET["search_numero"])) )
{

  $sql .= " WHERE cg.numero LIKE '%".$_GET["search_numero"]."%'";

  if ( dol_strlen(trim($_GET["search_intitule"])))
    {
      $sql .= " AND cg.intitule LIKE '%".$_GET["search_intitule"]."%'";
    }

}
else
{
  if ( dol_strlen(trim($_GET["search_intitule"])))
    {
      $sql .= " WHERE cg.intitule LIKE '%".$_GET["search_intitule"]."%'";
    }
}


$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;

  print_barre_liste("Comptes généraux", $page, "liste.php", "", $sortfield, $sortorder, '', $num);

  print '<table class="liste">';
  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("AccountNumberShort"),"liste.php","cg.numero");
  print_liste_field_titre($langs->trans("Label"),"liste.php","cg.intitule");
  print_liste_field_titre($langs->trans("DateCreation"),"liste.php","cg.date_creation");
  print "</tr>\n";

  print '<tr class="liste_titre">';
  print '<form action="liste.php" method="GET">';
  print '<td><input type="text" name="search_numero" value="'.$_GET["search_numero"].'"></td>';
  print '<td><input type="text" name="search_intitule" value="'.$_GET["search_intitule"].'"></td>';
  print '<td align="right">';
  print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
  print '</td>';
  print '</form>';
  print '</tr>';

  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($resql);
      $var=!$var;

      print "<tr $bc[$var]>";

      print '<td>'.$obj->numero.'</td>'."\n";
      print '<td>'.$obj->intitule.'</td>';
      print '<td align="right" width="100">';
      print dol_print_date($db->jdate($obj->dc));

      print '</td>';
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
