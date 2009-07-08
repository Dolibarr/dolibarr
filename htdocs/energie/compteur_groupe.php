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
 */

/**
 *  \file       htdocs/energie/compteur_groupe.php
 *  \ingroup    energie
 *  \brief      Fiche de gestion des groupes des compteurs
 *  \version    $Id$
 */

require("./pre.inc.php");


/*
 * Actions
 */

if ($_POST["action"] == 'addvalue')
{
	$compteur = new EnergieCompteur($db, $user);
	if ( $compteur->fetch($_GET["id"]) == 0)
	{
		if ( $compteur->AddGroup($_POST["groupe"]) == 0)
		{
	  Header("Location: compteur.php?id=".$_GET["id"]);
		}
	}
}



/*
 *	View
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

		$head[$h][0] = DOL_URL_ROOT.'/energie/compteur_groupe.php?id='.$compteur->id;
		$head[$h][1] = $langs->trans("Groups");
		$a = $h;
		$h++;

		dol_fiche_head($head, $a, $soc->nom);


		print '<table class="border" width="100%">';
		print "<tr><td>".$langs->trans("Compteur")."</td>";
		print '<td width="50%">';
		print $compteur->libelle;
		print "</td></tr>";
		print "</table><br>";

		$html = new Form($db);
		print '<form action="compteur_groupe.php?id='.$compteur->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="addvalue">';
		print '<table class="border" width="100%">';

		$var=!$var;
		print "<tr $bc[$var]>";

		$compteur->GroupsAvailable();

		print '<td>Groupe</td><td>';
		print $html->select_array("groupe", $compteur->groups_available);
		print '</td>';

		print '<td align="center"><input type="submit" value="'.$langs->trans("Add").'"></td></tr>';

		print "</table></form><br>";
		print '</div>';
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
