<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Jean-Louis Bergamo <jlb@j1b.org>
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

/*! \file htdocs/adherents/htpasswd.php
        \ingroup    adherent
		\brief      Page d'export htpasswd du fichier des adherents
		\author     Rodolphe Quiedeville
		\version    $Revision$
*/

require("./pre.inc.php");

llxHeader();

if ($sortorder == "") {  $sortorder="ASC"; }
if ($sortfield == "") {  $sortfield="d.login"; }

if (! isset($statut))
{
  $statut = 1 ;
}

if (! isset($cotis))
{
  // par defaut les adherents doivent etre a jour de cotisation
  $cotis=1;
}
$sql = "SELECT d.login, d.pass, ".$db->pdate("d.datefin")." as datefin";
$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d ";
$sql .= " WHERE d.statut = $statut ";
if ($cotis==1){
  $sql .= " AND datefin > now() ";
}
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit, $offset);

$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  
  print "<BR><DIV class=\"titre\">Export au format htpasswd des login des adhérents</DIV><BR>\n";
  //print_barre_liste("Export au format htpasswd des login des adhérents", $page, "htpasswd.php", "");
  print "<HR>\n";
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $htpass=crypt($objp->pass,initialiser_sel());
      print $objp->login.":".$htpass."<BR>\n";
      $i++;
    }
  print "<HR>\n";
}
else
{
  print $sql;
  print $db->error();
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
