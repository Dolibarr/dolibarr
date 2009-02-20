<?php
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

/**
   \file       htdocs/commande/fiche.php
   \ingroup    commande
   \brief      Fiche commande
   \version    $Revision$
*/

require("./pre.inc.php");

/*
 *
 */	
if ($_POST["action"] == 'addvalue')
{
  if ($_POST["releve"] > 0)
    {
      $compteur = new EnergieCompteur($db, $user);
      if ( $compteur->fetch($_GET["id"]) == 0)
	{
	  $date = mktime(12, 
			 0 , 
			 0, 
			 $_POST["remonth"], 
			 $_POST["reday"], 
			 $_POST["reyear"]);
	  
	  $compteur->AjoutReleve($date, $_POST["releve"]);
	  Header("Location: compteur.php?id=".$_GET["id"]);
	}
    }
}
/*
 *
 */	

llxHeader($langs, '',$langs->trans("Compteur"),"Compteur");

if ($_GET["period"] == '')
{
  $period = "day";
}
else
{
  $period = $_GET["period"];
}

$head[0][0] = DOL_URL_ROOT.'/energie/graph.php?period=day';
$head[0][1] = $langs->trans("Day");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/energie/graph.php?period=week';
$head[$h][1] = $langs->trans("Week");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/energie/graph.php?period=month';
$head[$h][1] = $langs->trans("Month");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/energie/graph.php?period=year';
$head[$h][1] = $langs->trans("Year");
$h++;

$as["day"] = 0;
$as["week"] = 1;
$as["month"] = 2;
$as["year"] = 3;

dol_fiche_head($head, $as[$period], $soc->nom);	  

print '<table class="noborder" width="100%">';

$sql = "SELECT c.rowid, c.libelle";
$sql .= " FROM ".MAIN_DB_PREFIX."energie_compteur as c";
$sql .= " ORDER BY c.libelle DESC";
$resql = $db->query($sql);
if ( $resql) 
{
  $num = $db->num_rows($resql);
  if ($num)
    {
      $i = 0;
      
      $var = True;
      while ($i < $num)
	{
	  $var=!$var;
	  $obj = $db->fetch_object($resql);
	  	  
	  print '<tr><td align="center">';
	  $file = $period.".".$obj->rowid.".png";
	  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=energie&file='.$file.'" alt="" title="">';
	  print '</td></tr>';
	  
	  $i++;
	}
      
    }
}
print '</table><br>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
