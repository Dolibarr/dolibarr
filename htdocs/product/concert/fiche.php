<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

llxHeader();

if ($action == 'add') {
  $concert = new Concert($db);

  $concert->groupartid = $_POST["ga"];
  $concert->lieuid = $_POST["lc"];
  $concert->date = $db->idate(mktime(12, 0 , 0, $remonth, $reday, $reyear)); 
  $concert->description = $desc;

  $id = $concert->create($user);
}

if ($action == 'update') {
  $concert = new Concert($db);

  $concert->groupartid = $_POST["ga"];
  $concert->lieuid = $_POST["lc"];
  $concert->date = $db->idate(mktime(12, 0 , 0, $remonth, $reday, $reyear)); 
  $concert->description = $desc;

  $concert->update($id, $user);
}

if ($action == 'updateosc') {
  $concert = new Concert($db);
  $result = $concert->fetch($id);

  $concert->updateosc($user);
}

/*
 *
 *
 */
if ($action == 'create')
{

  print "<form action=\"fiche.php?id=$id\" method=\"post\">\n";
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";

  print '<div class="titre">Nouveau concert</div><br>';

  $htmls = new Form($db);
      
  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';

  $ga = new Groupart($db);

  print "<tr><td>Date</td><td>";
  print_date_select();
  print "</td></tr>";

  print "<tr><td>Lieu</td><td>";
  $htmls->select_array("ga",  $ga->liste_array());
  print '</td></tr>';

  $lc = new LieuConcert($db);

  print "<tr><td>Lieu</td><td>";
  $htmls->select_array("lc",  $lc->liste());
  print '<a href="fichelieu.php?action=create">Nouveau lieu</a></td></tr>';

  print '<tr><td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>';
  print '</table>';
  print '</form>';
      

}
else
{
  if ($id)
    {

      $concert = new Concert($db);
      $result = $concert->fetch($id);

      $groupart = new Groupart($db);
      $result = $groupart->fetch($concert->groupartid);

      $lieuconcert = new LieuConcert($db);
      $result = $lieuconcert->fetch($concert->lieuid);

      if ( $result )
	{ 
	  print '<div class="titre">Fiche Concert : '.$concert->titre.'</div><br>';
      
	  print '<table border="1" width="50%" cellspacing="0" cellpadding="4">';
	  print "<tr>";
	  print "<td>Date</td><td>".strftime("%A %d %B %Y",$concert->date)."</td>\n";

	  print '<tr><td valign="top">Artiste/Groupe</td><td valign="top">'.$groupart->nom_url."</td>";

	  print '<tr><td valign="top">Lieu</td><td valign="top">'.$lieuconcert->nom_url."</td>";

	  print '<tr><td valign="top">'.$langs->trans("Description").'</td><td valign="top">'.nl2br($concert->description)."</td>";
	  

	  print "</table>";
	}
    
      if ($action == 'edit')
	{
	  print '<hr><div class="titre">Edition de la fiche Concert : '.$concert->titre.'</div><br>';

	  print "<form action=\"fiche.php?id=$id\" method=\"post\">\n";
	  print "<input type=\"hidden\" name=\"action\" value=\"update\">";
	  
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
	  print "<tr>";
	  print '<td>'.$langs->trans("Ref").'</td><td><input name="ref" size="20" value="'.$concert->ref.'"></td></tr>';
	  print '<td>'.$langs->trans("Label").'</td><td><input name="titre" size="40" value="'.$concert->titre.'"></td></tr>';
	  print '<tr><td>'.$langs->trans("Price").'</td><TD><input name="price" size="10" value="'.$concert->price.'"></td></tr>';    
	  print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
	  print '<textarea name="desc" rows="8" cols="50">';
	  print $concert->description;
	  print "</textarea></td></tr>";



	  print '</form>';
	  print '</table>';

	}    
    }
  else
    {
      print "Error";
    }
}

/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print '<br><table width="100%" border="1" cellspacing="0" cellpadding="3">';
print '<td width="20%" align="center">-</td>';

print '<td width="20%" align="center">[<a href="fiche.php?action=updateosc&id='.$id.'">Update Osc</a>]</td>';

print '<td width="20%" align="center">-</td>';

if ($action == 'create')
{
  print '<td width="20%" align="center">-</td>';
}
else
{
  print '<td width="20%" align="center">[<a href="fiche.php?action=edit&id='.$id.'">'.$langs->trans("Edit").'</a>]</td>';
}
print '<td width="20%" align="center">-</td>';    
print '</table><br>';



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
