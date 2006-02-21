<?php
/* Copyright (C) 2005      Matthieu Valleton    <mv@seeschloss.org>
 * Copyright (C) 2006      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Regis Houssin        <regis.houssin@cap-networks.com>
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
 */

/**
        \file       htdocs/categories/fiche.php
        \ingroup    categorie
        \brief      Page creation nouvelle categorie
*/

require "./pre.inc.php";

if (!$user->rights->categorie->lire) accessforbidden();

if (isset ($_REQUEST['choix']))
{
  $nbcats = $_REQUEST['choix'];
}
else
{ // par défault, une nouvelle catégorie sera dans une seule catégorie mère
  $nbcats = 1;
}




llxHeader("","",$langs->trans("Categories"));
$html = new Form($db);

/*
 * Action création du produit
 */
if ($_GET["action"] == 'create' && $user->rights->produit->creer)
{
	print '<form action="fiche.php" method="post">';
	print '<input type="hidden" name="nom" value="'.$nom.'">';
  print '<input type="hidden" name="description" value="'.$description.'">';

  print_fiche_titre($langs->trans("CreateCat"));

  print '<table class="border" width="100%" class="notopnoleftnoright">';
  print '<tr>';
	print '<td>'.$langs->trans("Label").'</td><td><input name="nom" size"25" value="'.$categorie->nom.'">';
  if ($_error == 1)
  {
  	print $lang->trans("ErrCatAlreadyExists");
  }
  print'</td></tr>';
  print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
  print '<textarea name="description" rows="6" cols=""40"';
  print '</textarea></td></tr>';
  print '<tr><td>'.$html->select_nombre_sous_categorie("choix", $nbcats).' ';
  print $langs->trans("categories");
  print '</td></tr>';
}
/*
			<td>
				<input type="submit" value="<?php print $langs->trans ("modify"); ?>" name="ok" id="ok" />
			</td>
			<?php
				$all_categories = $categorie->get_all_categories();//on récupère toutes les catégories et leurs attributs

				for ($i = 0; $i < $nbcats ; $i++)
					{
					echo "<tr><td>".$langs->trans ("Categorie")." ".($i+1)."</td><td><select name='catsMeres[".$i."]'>"; //creation des categories meres
					
					echo "<option value='-1' id='choix'>".$langs->trans ("Choose")."</option>\n";

					foreach ($all_categories as $id => $cat)
						{ //ajout des categories dans la liste
						echo "<option value='$id' id='$id'";
						
						if ($_REQUEST['catsMeres'][$i] == $id)
							echo ' selected="true"';
						
						echo ">".$cat->label."</option>\n";
						}
				
					echo "</select></td></tr>";
					}
				?>
		<tr>
			<td colspan="2"><input type='submit' value='<?php print $langs->trans ("CreateThisCat"); ?>' id="creation" name="creation"/></td>
		</tr>
		</form>
				
			<?php
			if (isset ($_REQUEST['creation']))
				{	
				// doit être à true pour valider la saisie de l'utilisateur
				$OK = true;

				$cats_meres = isset ($_REQUEST['catsMeres']) ? $_REQUEST['catsMeres'] : array ();
				
				if (sizeof ($cats_meres) > 1 && sizeof (array_unique ($cats_meres)) != sizeof ($cats_meres))
					{ // alors il y a des valeurs en double
					echo "<p>".$langs->trans ("ErrSameCatSelected")."</p>";
					$OK = false;
					}

				// vérification des champs renseignés par l'utilisateur: si il y a un problème, on affiche un message d'erreur
				foreach ($cats_meres as $nb => $cat_mere)
					{
					if ($cat_mere == -1)
						{
						echo "<p>".$langs->trans ("ErrForgotCat")." ".($nb+1)."</p>";
						$OK = false;
						}
					}
					
				// si les champs de description sont mal renseignés
				if ($_POST["nom"] == '')
					{
					echo "<p>".$langs->trans ("ErrForgotField")." \"".$langs->trans ("Label")."\"</p>";
					$OK = false;
					}
				else if ($categorie->already_exists($_POST["nom"],$cat_mere)) // on regarde si le nom n'existe pas déjà en tant que catégorie ou sous-catégorie
					{
					echo "<p>".$langs->trans ("ErrCatAlreadyExists")."</p>";
					$OK = false;
					}

				if ($_POST["description"] == '')
					{
					echo "<p>".$langs->trans ("ErrForgotField")." \"".$langs->trans ("Description")."\"</p>";
					$OK = false;
					}
					
				// vérification pour savoir si tous les champs sont corrects
				if ($OK)
					{
					$nom         = htmlspecialchars(stripslashes($_REQUEST['nom'])        ,ENT_QUOTES);
					$description = htmlspecialchars(stripslashes($_REQUEST['description']),ENT_QUOTES);
					// creation de champs caches pour etre appele dans la classe de traitement
					?>
					<table class="noborder"><tr><td>
					<form method="post" action="docreate.php">
						<p><?php print $langs->trans ("ValidateFields"); ?>&nbsp;?
						<input type='submit' value='<?php print $langs->trans ("Valid"); ?>'/>
						<input type='hidden' name='nom'         value="<?php print $nom;         ?>">
						<input type='hidden' name='description' value="<?php print $description; ?>">

						<?php
						foreach ($cats_meres as $id => $cat_mere)
							{
							echo "<input type='hidden' name='cats_meres[$id]' value='$cat_mere'>";
							}
						?>
						</form></p>
					</td></tr></table>
					<?php
					}
				}
*/
print '</td></tr></table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
