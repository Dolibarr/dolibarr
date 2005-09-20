<?php
/* Copyright (C) 2005 Matthieu Valleton <mv@seeschloss.org>
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

require "./pre.inc.php";

if (!$user->rights->categorie->creer) accessforbidden();

if (isset ($_REQUEST['choix']))
{
  $nbcats = $_REQUEST['choix'];
}
else
{ // par défault, une nouvelle catégorie sera dans une seule catégorie mère
  $nbcats = 1;
}

llxHeader("","",$langs->trans("Categories"));

print_titre($langs->trans("CreateCat"));

print '<table border="0" width="100%">';

print '<tr><td valign="top" width="30%">';
?>
<form method="post" action="<?php print $_SERVER['REQUEST_URI']; ?>">
	<table class="border">
		<tr>
			<td><?php print $langs->trans ("Label"); ?>&nbsp;:</td>

			<td><input type='text' size='25' id='nom' name ='nom' value='<?php 
					print htmlspecialchars (stripslashes ($_REQUEST['nom']), ENT_QUOTES); 
				?>' />
		</tr>
		<tr>
			<td><?php print $langs->trans ("Description"); ?>&nbsp;:</td>
			
			<td><textarea name='description' cols='40' rows='6' id='description'><?php
					print htmlspecialchars (stripslashes ($_REQUEST['description']), ENT_QUOTES); 
			?></textarea></td>
			
		</tr>
		<tr>
			<td><?php print $langs->trans ("In"); ?> <select name="choix">
					<?php
					// création d'un objet de type catégorie pour faire des requêtes sur la table
					$c = new Categorie ($db);
					
					$nb = $c->get_nb_categories ();
					
					for ($i = 0 ; $i <= $nb ; $i++)
						{
						echo "<option value='$i' ";//creation d'une valeur dans la liste
							
						if ($_REQUEST['choix'] == $i)
								echo 'selected="true"'; // permet de rendre le choix toujours selectionne
						else if (!isset ($_REQUEST['choix']) && $i == $nbcats) // nombre de catégories mères par défaut.
								echo 'selected="true"';

						echo ">$i</option>";
						}
					?>
				</select> <?php print $langs->trans ("categories"); ?>
			</td>
			<td>
				<input type="submit" value="<?php print $langs->trans ("modify"); ?>" name="ok" id="ok" />
			</td>
			<?php
				$cats = $c->get_all_categories ();//on récupère toutes les catégories et leurs attributs

				for ($i = 0; $i < $nbcats ; $i++)
					{
					echo "<tr><td>".$langs->trans ("Categorie")." ".($i+1)."</td><td><select name='catsMeres[".$i."]'>"; //creation des categories meres
					
					echo "<option value='-1' id='choix'>".$langs->trans ("Choose")."</option>\n";

					foreach ($cats as $id => $cat)
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
				else if ($c->already_exists ($_POST["nom"]))
					{
					// on regarde si le champ nom n'est pas déjà dans la table catégorie (rappel: un nom est unique dans la table catégorie
					// nb a déjà été renseigné, il contient le nombre de catégories
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

print '</td></tr></table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
