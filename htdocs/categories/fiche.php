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
require_once(DOL_DOCUMENT_ROOT."/categories/categorie.class.php");

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



// Action ajout d'un produit ou service
if ($_POST["action"] == 'add' && $user->rights->produit->creer)
{
    $categorie = new Categorie($db);

    $categorie->label          = stripslashes($_POST["nom"]);
    $categorie->description    = stripslashes($_POST["description"]);
    $cats_meres = isset($_POST['catsMeres']) ? $_POST['catsMeres'] : array();
    
       if (!$categorie->label || !$categorie->description)
       {
          $_GET["action"] = 'create';
       }
       
       if (sizeof($cats_meres) > 1 && sizeof(array_unique($cats_meres)) != sizeof($cats_meres))
       { // alors il y a des valeurs en double
          print '<p>'.$langs->trans("ErrSameCatSelected").'</p>';
          $_GET["action"] = 'create';
       }
       else
       {
    	    $res = $categorie->create();
    	    if ($res < 0)
    	    {
    	  	  $_error = 3;
    	    }
    	    else
    	    {
    	  	  foreach ($cats_meres as $id)
            {
               $mere = new Categorie($db, $id);
	             $res = $mere->add_fille($categorie);
               if ($res < 0)
               {
             	    $_error = 2;
               }
               else
               {
               	$_GET["action"] = 'confirmed';
               	$_POST["addcat"] = '';
               }
            }
          }
        }

    
}


/*
 * Fiche en mode création
 */

if ($user->rights->produit->creer)
{
 if ($_GET["action"] == 'create' || $_POST["addcat"] == 'addcat')
 {
	print '<form action="fiche.php" method="post">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="addcat" value="addcat">';
	print '<input type="hidden" name="nom" value="'.$nom.'">';
  print '<input type="hidden" name="description" value="'.$description.'">';
  
  foreach ($catsMeres as $id => $cat_mere)
  {
    print '<input type="hidden" name="catsMeres[$id]" value="'.$cat_mere.'">';
	}

  print_fiche_titre($langs->trans("CreateCat"));

  print '<table class="border" width="100%" class="notopnoleftnoright">';
  print '<tr>';
	print '<td>'.$langs->trans("Label").'</td><td><input name="nom" size"25" value="'.$categorie->label.'">';
  if ($_error == 1)
  {
  	print $lang->trans("ErrCatAlreadyExists");
  }
  print'</td></tr>';
  print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
  print '<textarea name="description" rows="6" cols="50">';
  print $categorie->description.'</textarea></td></tr>';
  print '<tr><td>';
  print $langs->trans("AddIn").'  ';
  print $html->select_nombre_sous_categorie($nbcats,"cats_meres").'  ';
  print $langs->trans("categories");
  print '</td><td>';
  print '<input type="submit" class="button" value="'.$langs->trans("modify").'" name="choicenbcats" id="choicenbcats"/>';
  print '</td></tr>';
 	print $html->select_all_categories($nbcats);  
  print '<tr><td colspan="2">';
  print '<input type="submit" class="button" value="'.$langs->trans("CreateThisCat").'" name="creation" id="creation"/>';
  print '</td></tr></form>';
  
 }


/*
 * Action confirmation de création de la catégorie
 */

 if ($_GET["action"] == 'confirmed')
 {
  print_titre($langs->trans("CatCreated"));

  print '<table border="0" width="100%">';
  print '<tr><td valign="top" width="30%">';

  if ($_error == 3)
	{
	  print '<p>'.$langs->trans("ImpossibleAddCat").' '.$categorie->label.'</p>';
	}
  else
	{
	  print '<p>'.$langs->trans("TheCategorie").' '.$categorie->label.' '.$langs->trans("WasAddedSuccessfully").'</p>';
	  if ($_error == 2)
    {
        print '<p>'.$langs->trans("TheCategorie").' '.$mere->label.' ('.$res.').</p>';
    }
	}
	print '</td></tr></table>';
 }
}



print '</table>';
/*

				
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

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
