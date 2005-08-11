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

$html = new Form($db);

/*********************************************************************
 *
 * Mode creation
 *
 *
 ************************************************************************/
if ($_GET["action"] == 'create') 
{
  print_titre("Ajouter une compteur");

} 
else 
/* *************************************************************************** */
/*                                                                             */
/* Mode vue                                                                    */
/*                                                                             */
/* *************************************************************************** */
{  
  if ($_GET["id"] > 0)
    {
      $compteur = new EnergieCompteur($db, $user);
      if ( $compteur->fetch($_GET["id"]) == 0)
	{	  
	  $head[0][0] = DOL_URL_ROOT.'/energie/compteur.php?id='.$compteur->id;
	  $head[0][1] = "Compteur";
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT.'/energie/graph.php?id='.$compteur->id;
	  $head[$h][1] = "Graph";
	  $a = 1;
	  
	  dolibarr_fiche_head($head, $a, $soc->nom);	  
	  
	  print '<table class="border" width="100%">';
	  print "<tr><td>".$langs->trans("Compteur")."</td>";
	  print '<td width="50%">';
	  print $compteur->libelle;
	  print "</td></tr>";	  	  
	  print "</table><br>";
	  	  
	  print '</div>';
	  

	  print '<table class="noborder" width="100%">';

	  print '<tr><td align="center">';
	  $file = "day.".$compteur->id.".png";
	  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=energie&file='.$file.'" alt="" title="">';
	  print '</td><td align="center">';
	  $file = "week.".$compteur->id.".png";
	  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=energie&file='.$file.'" alt="" title="">';
	  print '</td></tr>';

	  print '<tr><td align="center">';
	  $file = "month.".$compteur->id.".png";
	  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=energie&file='.$file.'" alt="" title="">';
	  print '</td><td align="center">';
	  $file = "year.".$compteur->id.".png";
	  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=energie&file='.$file.'" alt="" title="">';
	  print '</td></tr></table><br>';

	  print "<br>\n";
	}
      else
	{
	  /* Commande non trouvée */
	  print "Compteur inexistant";
	}
    }  
  else
    {
      print "Compteur inexistant";
    }
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
