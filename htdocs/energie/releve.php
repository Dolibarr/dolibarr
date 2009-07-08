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
 *  \file       htdocs/energie/compteur.php
 *  \ingroup    energie
 *  \brief      Fiche compteur
 *  \version    $Revision$
 */

require("./pre.inc.php");


/*
 *	Actions
 */

if ($_GET["action"] == 'delete')
{
  if ($_GET["rowid"] > 0)
    {
      $compteur = new EnergieCompteur($db, $user);
      if ( $compteur->fetch($_GET["id"]) == 0)
	{
	  $compteur->DeleteReleve($_GET["rowid"]);
	}
    }
}


/*
 * 	View
 */

llxHeader($langs, '',$langs->trans("Compteur"),"Compteur");


if ($_GET["id"] > 0)
{
  $compteur = new EnergieCompteur($db, $user);
  if ( $compteur->fetch($_GET["id"]) == 0)
    {

      $head[0][0] = DOL_URL_ROOT.'/energie/compteur.php?id='.$compteur->id;
      $head[0][1] = $langs->trans("Compteur");
      $h++;

      $head[$h][0] = DOL_URL_ROOT.'/energie/compteur_graph.php?id='.$compteur->id;
      $head[$h][1] = $langs->trans("Graph");
      $h++;

      $head[$h][0] = DOL_URL_ROOT.'/energie/releve.php?id='.$compteur->id;
      $head[$h][1] = $langs->trans("Releves");
      $h++;
      $a = 2;

      $head[$h][0] = DOL_URL_ROOT.'/energie/compteur_groupe.php?id='.$compteur->id;
      $head[$h][1] = $langs->trans("Groups");
      $h++;

      dol_fiche_head($head, $a, $soc->nom);

      print '<table class="border" width="100%">';
      print "<tr><td>".$langs->trans("Compteur")."</td>";
      print '<td width="50%">';
      print $compteur->libelle;
      print "</td></tr>";
      print "</table><br>";
      print '</div>';

      $page = $_GET["page"];
      $limit = $conf->liste_limit;
      $offset = $limit * $page ;

      $sql = "SELECT ".$db->pdate("date_releve")." as date_releve, valeur, rowid";
      $sql .= " FROM ".MAIN_DB_PREFIX."energie_compteur_releve as ecr";
      $sql .= " WHERE ecr.fk_compteur = '".$compteur->id."'";

      $sql .= " ORDER BY ecr.date_releve DESC";
      $sql .= $db->plimit($limit + 1 ,$offset);

      $resql = $db->query($sql);
      if ($resql)
	{
	  $num = $db->num_rows($resql);
	  $i = 0;
	  $var=True;

	  print_barre_liste("Releves", $page, "", "&amp;id=".$compteur->id, $sortfield, $sortorder,'',$num, 0, '');

	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre"><td>'.$langs->trans("Date").'</td>';
	  print '<td colspan="2">'.$langs->trans("Releve").'</td></tr>';

	  while ($i < $num)
	    {
	      $obj = $db->fetch_object($resql);
	      $var=!$var;
	      print "<tr $bc[$var]><td>";
	      print dol_print_date($obj->date_releve,'%a %d %B %Y');
	      print '</td><td>'.$obj->valeur.'</td>';
	      print '<td><a href="releve.php?id='.$compteur->id.'&amp;action=delete&amp;rowid='.$obj->rowid.'&amp;page='.$page.'">';
	      print img_delete().'</a></td></tr>';
	      $i++;
	    }
	}
      print '</table>';
	  print "<br>\n";
    }
  else
    {
      /* Commande non trouv�e */
      print "Compteur inexistant";
    }
}
else
{
  print "Compteur inexistant";
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
