<?php
/* Copyright (C) 2005-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
   \file       htdocs/energie/compteur.php
   \ingroup    energie
   \brief      Fiche compteur
   \version    $Revision$
*/

require("./pre.inc.php");

/*
 *
 */	

if ($_POST["action"] == 'add')
{
  $compteur = new EnergieCompteur($db, $user);

  if ( $compteur->create($_POST["libelle"],$_POST["energie"]) == 0)
    {
      Header("Location: compteur.php?id=".$compteur->id);
    }
  else
    {
      Header("Location: compteur.php?action=create");
    }
}


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

/*********************************************************************
 *
 * Mode creation
 *
 *
 ************************************************************************/
if ($_GET["action"] == 'create') 
{
  $head[0][0] = DOL_URL_ROOT.'/energie/compteur.php?action=create';
  $head[0][1] = "Nouveau compteur";
  $h++;
  $a = 0;

  dol_fiche_head($head, $a, $soc->nom);

  $html = new Form($db);
  $compteur = new EnergieCompteur($db, $user);

  print '<form action="compteur.php" method="post">';
  print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
  print '<input type="hidden" name="action" value="add">';
  
  print '<table class="border" width="100%">';

  print "<tr $bc[$var]>";
  
  print '<td>Libellé</td>';
  
  print '<td><input type="text" size="40" maxlength="255" name="libelle"></td></tr>';
  
  print "<tr $bc[$var]>";
  print '<td>Energie</td><td>';
  print $html->select_array("energie", $compteur->energies);
  print '</td></tr>';

  print '<tr><td colspan="2" align="center"><input type="submit" value="'.$langs->trans("Add").'" class="button"></td></tr>';
  
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
      $compteur = new EnergieCompteur($db, $user);
      if ( $compteur->fetch($_GET["id"]) == 0)
	{	  
	  
	  $head[0][0] = DOL_URL_ROOT.'/energie/compteur.php?id='.$compteur->id;
	  $head[0][1] = "Compteur";
	  $h++;
	  $a = 0;

	  $head[$h][0] = DOL_URL_ROOT.'/energie/compteur_graph.php?id='.$compteur->id;
	  $head[$h][1] = "Graph";
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT.'/energie/releve.php?id='.$compteur->id;
	  $head[$h][1] = "Relevés";
	  $h++;

	  $head[$h][0] = DOL_URL_ROOT.'/energie/compteur_groupe.php?id='.$compteur->id;
	  $head[$h][1] = "Groupe";
	  $h++;

	  dol_fiche_head($head, $a, $soc->nom);
	  
	  
	  print '<table class="border" width="100%">';
	  print "<tr><td>".$langs->trans("Compteur")."</td>";
	  print '<td width="50%">';
	  print $compteur->libelle;
	  print "</td></tr>";	  	  
	  print "</table><br>";
	  
	      
	  $html = new Form($db);
	  print '<form name="addvalue" action="compteur.php?id='.$compteur->id.'" method="post">';
	  print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
	  print '<input type="hidden" name="action" value="addvalue">';
	  print '<table class="border" width="100%">';	  

	  $var=!$var;
	  print "<tr $bc[$var]>";
	  
	  print '<td>Date</td><td>';
	  print $html->select_date('','','','','',"addvalue");
	  print '</td><td>Valeur relevée</td>';

	  print '<td align="center"><input type="text" size="11" maxlength="10" name="releve"></td>';
	  print '<td align="center"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td></tr>';
	  print "</table></form><br>";
	
	  print '<table class="noborder" width="100%">';
	  print '<tr><td><a href="compteur_graph.php?id='.$compteur->id.'">';
	  $file = "all.".$compteur->id.".png";
	  print '<img border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=energie&file='.$file.'" alt="" title="">';
	  print '</a></td></tr></table>';

	  print '</div>';

	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre"><td>'.$langs->trans("Date").'</td>';
	  print '<td>'.$langs->trans("Relevé").'</td></tr>';
	  
	  $sql = "SELECT ".$db->pdate("date_releve")." as date_releve, valeur";
	  $sql .= " FROM ".MAIN_DB_PREFIX."energie_compteur_releve as ecr";
	  $sql .= " WHERE ecr.fk_compteur = '".$compteur->id."'";
	  $sql .= " ORDER BY ecr.date_releve DESC LIMIT 5";
	  $resql = $db->query($sql);
	  if ($resql)
	    {
	      $num = $db->num_rows($resql);
	      $i = 0;
	      $var=True;
	      while ($i < $num)
		{
		  $obj = $db->fetch_object($resql);
		  $var=!$var;
		  print "<tr $bc[$var]><td>";
		  print dol_print_date($obj->date_releve,'%a %d %B %Y');
		  print '</td><td>'.$obj->valeur.'</td>';

		  $i++;
		}
	    }
	  print '</table>';
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
