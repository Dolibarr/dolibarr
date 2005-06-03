<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require DOL_DOCUMENT_ROOT.'/telephonie/distributeurtel.class.php';

if (!$user->rights->telephonie->lire) accessforbidden();

llxHeader('','Telephonie - Statistiques - Distributeur');

/*
 *
 */
$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/index.php';
$head[$h][1] = "Liste";
$h++;

if ($_GET["id"])
{

  $distri = new DistributeurTelephonie($db);
  $distri->fetch($_GET["id"]);

  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/distributeur.php?id='.$distri->id;
  $head[$h][1] = $distri->nom;
  $hselected = $h;
  $h++;

  $sql = "SELECT d.nom, d.rowid";
  $sql .= " FROM ".MAIN_DB_PREFIX."telephonie_distributeur as d";
  $sql .= " WHERE d.rowid <> ".$distri->id;
  $sql .= " ORDER BY d.nom ASC";
  
  $resql = $db->query($sql);
  
  if ($resql)
    {
      $num = $db->num_rows();
      $i = 0;
      $total = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row($resql);
	  
	  $head[$h][0] = DOL_URL_ROOT.'/telephonie/distributeurs/distributeur.php?id='.$row[1];
	  $head[$h][1] = $row[0];	    
	  $h++;
	  $i++;
	}
    }


  dolibarr_fiche_head($head, $hselected, "Distributeur");

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td width="30%" valign="top">';
  
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print '<td>Prénom Nom</td></tr>';

  $sql = "SELECT u.firstname, u.name";
  $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
  $sql .= " , ".MAIN_DB_PREFIX."telephonie_distributeur_commerciaux as dc";
  
  $sql .= " WHERE u.rowid = dc.fk_user ";
  $sql .= " AND dc.fk_distributeur = ".$_GET["id"];

  $sql .= " ORDER BY u.name ASC";
    
  $resql = $db->query($sql);
  
  if ($resql)
    {
      $num = $db->num_rows();
      $i = 0;
      $total = 0;
      
      while ($i < $num)
	{
	  $row = $db->fetch_row($i);	
	  
	  $var=!$var;
	  
	  print "<tr $bc[$var]>";
	  
	  print '<td>'.$row[0].' '.$row[1].'</td>';
	  
	  $i++;
	}
      $db->free();
    }
  else 
    {
      print $db->error() . ' ' . $sql;
    }
  print '</table><br />';

 
  print '</td><td valign="top" width="70%">';
  
  print '</td></tr>';
  print '</table>';
 
 $db->close();
}

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
