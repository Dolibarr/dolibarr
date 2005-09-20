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
 */

require "./pre.inc.php";


if (false && (!isset ($_REQUEST['id']) || !$user->rights->categories->lire))
  accessforbidden();
// toujours pas trouvé comment mettre les droits qui vont bien

llxHeader("","",$langs->trans("Categories"));

print_titre($langs->trans("ModifCat"));

print '<table border="0" width="100%">';

print '<tr><td valign="top" width="30%">';


$c = new Categorie ($db, $_REQUEST['id']);

if (isset ($_REQUEST['cats_meres']))
{
  $cats_meres = $_REQUEST['cats_meres'];
}
else
{
  $cats_meres = array ();
  foreach ($c->get_meres () as $mere)
    {
      $cats_meres[] = $mere->id;
    }
}
$label       = isset ($_REQUEST['nom'])         ? $_REQUEST['nom']         : $c->label;
$description = isset ($_REQUEST['description']) ? $_REQUEST['description'] : $c->description;
$nbcats      = isset ($_REQUEST['choix'])       ? $_REQUEST['choix']       : sizeof ($cats_meres);

print '<tr><td valign="top" width="30%">';
?>
<form method="post" action="<?php print $_SERVER['REQUEST_URI']; ?>">
<table class="border">
	<tr>
		<td><?php print $langs->trans("Label"); ?>&nbsp;:</td>

		<td><input type='text' size='25' id='nom' name ='nom' value='<?php 
				print htmlspecialchars (stripslashes ($label), ENT_QUOTES); 
			?>' />
	</tr>
	<tr>
		<td><?php print $langs->trans("Description"); ?>&nbsp;:</td>
		
		<td><textarea name='description' cols='40' rows='6' id='description'><?php
				print htmlspecialchars (stripslashes ($description), ENT_QUOTES); 
		?></textarea></td>
		
	</tr>
	
	<tr>
		<td>
			<?php print $langs->trans("In"); ?>
			<select name="choix">
				<?php
				// création d'un objet de type catégorie pour faire des requêtes sur la table
				$c = new Categorie ($db);
				
				$nb = $c->get_nb_categories ();
				
				for ($i = 0 ; $i <= $nb ; $i++)
					{
					echo "<option value='$i' ";//creation d'une valeur dans la liste
						
					if ($i == $nbcats)
							echo 'selected="true"'; // permet de rendre le choix toujours selectionne

					echo ">$i</option>";
					}
				?>
			</select>
			<?php print $langs->trans("categories"); ?>
		</td>
		<td>
			<input type="submit" value="<?php print $langs->trans ("modify"); ?>" name="ok" id="ok" />
		</td>
	</tr>
	<?php
	$cats = $c->get_all_categories ();//on récupère toutes les catégories et leurs attributs

	for ($i = 0 ; $i < $nbcats ; $i++)
		{
		print "<tr><td>";
		print $langs->trans("Categorie")." ".($i+1)."</td><td><select name='cats_meres[".$i."]'>"; //creation des categories meres
		
		echo "<option value='-1' id='choix'>".$langs->trans ("Choose")."</option>\n";

		foreach ($cats as $id => $cat)
			{ //ajout des categories dans la liste
			echo "<option value='$id' id='$id'";
			
			if ($cats_meres[$i] == $id)
				echo ' selected="true"';
			
			echo ">".$cat->label."</option>\n";
			}
	
		echo "</select></td></tr>\n";
		}

		?>
	<tr>
		<td colspan="2" align="center"><input type='submit' value='<?php print $langs->trans ("Modify"); ?>' id="modif" name="modif"/></td>
	</tr>
</table>
</form>
		
	<?php
	if (isset ($_REQUEST['modif']))
		{	
		// doit être à true pour valider la saisie de l'utilisateur
		$OK = true;

		if (sizeof ($cats_meres) > 1 && sizeof (array_unique ($cats_meres)) != sizeof ($cats_meres))
			{ // alors il y a des valeurs en double
			echo "<p>".$langs->trans ("ErrSameCatSelected")."</p>";
			$OK = false;
			}

		// vérification des champs renseignés par l'utilisateur: si il y a un problème, on affiche un message d'erreur
		if (sizeof ($cats_meres) > 0) foreach ($cats_meres as $nb => $cat_mere)
			{
			if ($cat_mere == -1)
				{
				echo "<p>".$langs->trans ("ErrForgotCat")."</p>";
				$OK = false;
				}
			}
			
		// si les champs de description sont mal renseignés
		if ($label == '')
			{
			echo "<p>".$langs->trans ("ErrForgotField")."</p>";
			$OK = false;
			}
			
		// vérification pour savoir si tous les champs sont corrects
		if ($OK)
			{
			// creation de champs caches pour etre appele dans la classe de traitement
			?>
			<form method="post" action="domodif.php">
				<p><?php print $langs->trans("ValidateFields"); ?> ? <input type='submit' value='<?php print $langs->trans("Valid"); ?>'/></p>
				<input type='hidden' name='id'          value="<?php print $_REQUEST['id']; ?>">
				<input type='hidden' name='nom'         value="<?php print $label;          ?>">
				<input type='hidden' name='description' value="<?php print $description;    ?>">

				<?php
				foreach ($cats_meres as $id => $cat_mere)
					{
					echo "<input type='hidden' name='cats_meres[$id]' value='$cat_mere'>";
					}
				?>
			</form>
			<?php
			}
		}


print '</td></tr></table>';

$db->close();
?>
