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
   \file       htdocs/energie/groupe.php
   \ingroup    energie
   \brief      Fiche groupe
   \version    $Revision$
*/

require("./pre.inc.php");

/*
 *
 */	

if ($_POST["action"] == 'add')
{
  $groupe = new EnergieGroupe($db, $user);

  if ( $groupe->create($_POST["libelle"]) == 0)
    {
      Header("Location: groupe.php?id=".$groupe->id);
    }
  else
    {
      Header("Location: groupe.php?action=create");
    }
}

/*
 *
 */	

llxHeader($langs, '',$langs->trans("Groupe"),"Groupe");

/*********************************************************************
 *
 * Mode creation
 *
 *
 ************************************************************************/
if ($_GET["action"] == 'create') 
{
  $head[0][0] = DOL_URL_ROOT.'/energie/groupe.php?action=create';
  $head[0][1] = "Nouveau groupe";
  $h++;
  $a = 0;

  dol_fiche_head($head, $a, $soc->nom);

  $html = new Form($db);

  print '<form action="groupe.php" method="post">';
  print '<input type="hidden" name="action" value="add">';
  
  print '<table class="border" width="100%">';
  print "<tr $bc[$var]>";
  print '<td>Libellé</td>';
  
  print '<td><input type="text" size="40" maxlength="255" name="libelle"></td></tr>';
  
  print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td></tr>';
  
  print "</table></form>";
	  
  print '</div>';
  
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
      $groupe = new EnergieGroupe($db, $user);
      if ( $groupe->fetch($_GET["id"]) == 0)
	{	  
	  
	  $head[0][0] = DOL_URL_ROOT.'/energie/groupe.php?id='.$commande->id;
	  $head[0][1] = "Groupe";
	  $h++;
	  $a = 0;

	  dol_fiche_head($head, $a, $soc->nom);
	  	  
	  print '<table class="border" width="100%">';
	  print "<tr><td>".$langs->trans("Groupe")."</td>";
	  print '<td width="50%">';
	  print $groupe->libelle;
	  print "</td></tr>";	  	  
	  print "</table><br>";
	  	      
	  /*
	   *
	   */	  


	  print '<table class="noborder" width="100%">';

	  print '<tr><td align="center">';
	  $file = "groupe.day.".$groupe->id.".png";
	  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=energie&file='.$file.'" alt="" title="">';
	  print '</td><td align="center">';
	  $file = "groupe.week.".$groupe->id.".png";
	  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=energie&file='.$file.'" alt="" title="">';
	  print '</td></tr>';

	  print '<tr><td align="center">';
	  $file = "groupe.month.".$groupe->id.".png";
	  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=energie&file='.$file.'" alt="" title="">';
	  print '</td><td align="center">';
	  $file = "groupe.year.".$groupe->id.".png";
	  print '<img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=energie&file='.$file.'" alt="" title="">';
	  print '</td></tr></table><br>';

	  print '</div>';	  
	  print "<br>\n";
	}
      else
	{
	  /* Commande non trouvée */
	  print "Groupe inexistant";
	}
    }  
  else
    {
      print "Groupe inexistant";
    }
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
