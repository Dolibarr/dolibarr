<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

require("./pre.inc.php");

if ( $HTTP_POST_VARS["sendit"] )
{
  global $local_file, $error_msg;

  $upload_dir = OSC_CATALOG_DIRECTORY."/images/";
  
  if (! is_dir($upload_dir))
    {
      umask(0);
      mkdir($upload_dir, 0755);
    }
    
  if (move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name']))
    {
      print "Le fichier est valide, et a &eacute;t&eacute; t&eacute;l&eacute;charg&eacute; avec succ&egrave;s.\n";
      //print_r($_FILES);
    }
  else
    {
      echo "Le fichier n'a pas été téléchargé";
      // print_r($_FILES);
    }

  if ($id or $oscid)
    {
      $livre = new Livre($db);
      
      if ($id)
	{
	  $result = $livre->fetch($id, 0);
	  $livre->update_image($_FILES['userfile']['name']);
	  $livre->updateosc($user);
	}
    }
}


if ($action == 'add')
{
  $livre = new Livre($db);

  $livre->titre = $titre;
  $livre->ref = $ref;
  $livre->price = $price;
  $livre->annee = $annee;
  $livre->editeurid = $editeurid;
  $livre->description = $desc;
  $livre->frais_de_port = $HTTP_POST_VARS["fdp"];

  $id = $livre->create($user);
}

if ($action == 'addga')
{
  $livre = new Livre($db);
  $livre->linkga($id, $coauteurid);
}

if ($HTTP_POST_VARS["action"] == 'confirm_delete' && $HTTP_POST_VARS["confirm"] == "yes")
{
  $livre = new Livre($db);
  $livre->fetch($id);
  $livre->delete();
  Header("Location: index.php");
}


if ($action == 'linkcat')
{
  $livre = new Livre($db);
  $livre->fetch($id);
  $livre->linkcategorie( $catid);
}

if ($action == 'delcat')
{
  $livre = new Livre($db);
  $livre->fetch($id);
  $livre->unlinkcategorie($catid);
}

if ($action == 'delauteur' && $auteurid)
{
  $livre = new Livre($db);
  $livre->fetch($id);
  $livre->auteur_unlink($auteurid);
}

if ($action == "status")
{
  $livre = new Livre($db);
  $livre->fetch($id);
  if ($livre->update_status($status))
    {
      
    }
}

if ($action == 'update' && !$cancel)
{
  $livre = new Livre($db);

  $livre->titre = $titre;
  $livre->ref = $ref;
  $livre->price = $price;
  $livre->frais_de_port = $HTTP_POST_VARS["fdp"];
  $livre->annee = $annee;
  $livre->editeurid = $editeurid;
  $livre->description = $desc;

  if ($livre->update($id, $user))
    {
      $result = $livre->fetch($id);
      $livre->updateosc($user);
    }
  else
    {
      $action = 'edit';
    }
}

if ($action == 'updateosc')
{
  $livre = new Livre($db);
  $result = $livre->fetch($id);

  $livre->updateosc($user);
}

/*
 *
 *
 */

llxHeader();

if ($action == 'create')
{

  print "<form action=\"fiche.php?id=$id\" method=\"post\">\n";
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";

  print '<div class="titre">Nouvel ouvrage</div><br>';
      
  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
  print "<tr>";
  print '<td>Référence</td><td><input name="ref" size="20" value=""></td></tr>';
  print '<td>Titre</td><td><input name="titre" size="40" value=""></td></tr>';
  print '<tr><td>'.$langs->trans("Price").'</td><TD><input name="price" size="10" value=""></td></tr>';    

  print '<tr><td>Frais de port</td><td><select name="fdp">';
  print '<option value="1" SELECTED>oui</option>';
  print '<option value="0">non</option></td></tr>';

  $htmls = new Form($db);
  $edits = new Editeur($db);
	      
  print "<tr><td>Editeur</td><td>";
  $htmls->select_array("editeurid",  $edits->liste_array(), $livre->editeurid);
  print "</td></tr>";

  print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
  print '<textarea name="desc" rows="8" cols="50">';
  print "</textarea></td></tr>";
  print '<tr><td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>';
  print '</table>';
  print '</form>';
      

}
else
{
  if ($id or $oscid)
    {
      $livre = new Livre($db);

      if ($id)
	{
	  $result = $livre->fetch($id, 0);
	}
      if ($oscid)
	{
	  $result = $livre->fetch(0, $oscid);
	  $id = $livre->id;
	}

      if ( $result )
	{ 
	  $htmls = new Form($db);
	  $auteurs = $livre->liste_auteur();


	  /*
	   * Confirmation de la suppression de l'adhérent
	   *
	   */
	  
	  if ($action == 'delete')
	    {
	      
	      print '<form method="post" action="fiche.php?id='.$id.'">';
	      print '<input type="hidden" name="action" value="confirm_delete">';
	      print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
	      
	      print '<tr><td colspan="3">Supprimer le livre</td></tr>';	      
	      print '<tr><td class="delete">Etes-vous sur de vouloir supprimer cet ouvrage ?</td><td class="delete">';
	      $htmls = new Form($db);
	      
	      $htmls->selectyesno("confirm","no");
	      
	      print "</td>\n";
	      print '<td class="delete" align="center"><input type="submit" value="Confirmer"</td></tr>';
	      print '</table>';
	      print "</form>\n";  
	    }
	  


	  if ($action == 'edit')
	    {
	      print '<div class="titre">Edition de la fiche Livre : '.$livre->titre.'</div><br>';
	      
	      print "<form action=\"fiche.php?id=$id\" method=\"post\">\n";
	      print "<input type=\"hidden\" name=\"action\" value=\"update\">";
	      
	      print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	      print "<tr>";
	      print '<td width="20%">'.$langs->trans("Ref").'</td><td><input name="ref" size="20" value="'.$livre->ref.'"></td>';
	      print '<td valign="top">'.$langs->trans("Description").'</td></tr>';

	      print '<tr><td>'.$langs->trans("Status").'</td><td>'.$livre->status_text;
	      if ($livre->status == 0)
		{
		  print '<br><a href="fiche.php?id='.$id.'&status=1&action=status">Changer</a>';
		}
	      else
		{
		  print '<br><a href="fiche.php?id='.$id.'&status=0&action=status">Changer</a>';
		}
	      print "</td>\n";
	      
	      print '<td valign="top" width="50%" rowspan="7"><textarea name="desc" rows="14" cols="60">';
	      print $livre->description;
	      print "</textarea></td></tr>";

	      print '<tr><td>Titre</td><td><input name="titre" size="40" value="'.$livre->titre.'"></td></tr>';

	      print '<tr><td>Année</td><TD><input name="annee" size="6" maxlenght="4" value="'.$livre->annee.'"></td></tr>';
	      print '<tr><td>'.$langs->trans("Price").'</td><TD><input name="price" size="10" value="'.price($livre->price).'"></td></tr>';
	      print '<tr><td>Frais de port</td><td><select name="fdp">';
	      if ($livre->frais_de_port)
		{
		  print '<option value="1" SELECTED>oui</option>';
		  print '<option value="0">non</option>';
		}
	      else
		{
		  print '<option value="1">oui</option>';
		  print '<option value="0" SELECTED>non</option>';
		}
	      print '</select></td></tr>';

	      $htmls = new Form($db);
	      $edits = new Editeur($db);
	      
	      print "<tr><td>Editeur</td><td>";
	      $htmls->select_array("editeurid",  $edits->liste_array(), $livre->editeurid);
	      print "</td></tr>";

	      print '<tr><td>Auteur(s)</td><td>';

	      foreach ($auteurs as $key => $value)
		{
	      print '<a href="fiche.php?id='.$id.'&action=delauteur&auteurid='.$key.'">';
	      print '<img src="/theme/'.$conf->theme.'/img/editdelete.png" height="16" width="16" alt="Supprimer" border="0"></a>&nbsp;';
		  print '<a href="../auteur/fiche.php?id='.$key.'">'.$value."<br>\n";
		}
	      print "</td></tr>";


	      print '<tr><td align="center" colspan="3"><input type="submit" value="'.$langs->trans("Save").'">&nbsp;<input type="submit" value="'.$langs->trans("Cancel").'" name="cancel"></td></tr>';
	      print "</form>";

	      print '</form>';
	      
	      $auteur = new Auteur($db);
	      
	      print "<form action=\"fiche.php?id=$id\" method=\"post\">\n";
	      print "<input type=\"hidden\" name=\"action\" value=\"addga\">";

	      print '<tr><td>Auteur(s)</td><td colspan="2">';
	      $htmls->select_array("coauteurid",  $auteur->liste_array());
	      print '&nbsp;<input type="submit" value="'.$langs->trans("Add").'"></td></tr>';
	      print "</form>";

	      print "<form action=\"fiche.php?id=$id\" method=\"post\">\n";
	      print '<input type="hidden" name="action" value="linkcat">';

	      $listecat = new Categorie($db);
	      print '<td valign="top">Catégories</td><td colspan="2">';
	      $htmls->select_array("catid", $listecat->liste_array());
	      print '&nbsp;<input type="submit" value="'.$langs->trans("Add").'"></td></tr>';
	      print "</form>";
	      print "</td></tr>\n";

	      print '<td valign="top">Vignette</td><td colspan="2">';
	      echo '<FORM NAME="userfile" ACTION="fiche.php?id='.$id.'" ENCTYPE="multipart/form-data" METHOD="POST">';      
	      print '<input type="hidden" name="max_file_size" value="2000000">';
	      print '<input type="file"   name="userfile" size="40" maxlength="80"><br>';
	      print '<input type="submit" value="Upload File!" name="sendit">';
	      print '<input type="submit" value="'.$langs->trans("Cancel").'" name="cancelit"><BR>';
	      print '</form>';
	      print "</td></tr>\n";

	      print '</table><hr>';
	      
	    }    

	  /*
	   * Affichage
	   */

	  print '<div class="titre">Fiche Livre : '.$livre->titre.'</div><br>';

	  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	  print "<tr>";
	  print '<td width="15%">'.$langs->trans("Ref").'</td><td width="20%">'.$livre->ref.'</td>';
	  print '<td width="50%" valign="top">'.$langs->trans("Description").'</td>';
	  print '<td valign="top">Catégories</td></tr>';
	  print '<tr><td>'.$langs->trans("Status").'</td><td>'.$livre->status_text;
	  if ($livre->status == 0)
	    {
	      print '<br><a href="fiche.php?id='.$id.'&status=1&action=status">Changer</a>';
	    }
	  else
	    {
	      print '<br><a href="fiche.php?id='.$id.'&status=0&action=status">Changer</a>';
	    }
	  print "</td>\n";
	  print '<td rowspan="7" valign="top">'.nl2br($livre->description);

	  $img = OSC_CATALOG_DIRECTORY."images/".$livre->image;

	  if(file_exists($img))
	    {
	      print '<p><img src="'.OSC_CATALOG_URL.'/images/'.$livre->image.'">';
	    }
	  print "</td>";

	  print '<td rowspan="7" valign="top">';
	  $livre->listcategorie();
	  print "</td></tr>";
	  
	  print "<tr><td>Titre</td><td>$livre->titre</td></tr>\n";
	  print "<tr><td>Annee</td><td>$livre->annee</td></tr>\n";

	  print '<tr><td>Editeur</td><TD>';    

	  if ($livre->editeurid)
	    {
	      $editeur = new Editeur($db);
	      $editeur->fetch($livre->editeurid);
	      print $editeur->nom;    
	    }
	  print '</td></tr>';
	  print '<tr><td>Auteur(s)</td><td>';

	  foreach ($auteurs as $key => $value)
	    {	      
	      print '<a href="../auteur/fiche.php?id='.$key.'">';
	      print $value."</a><br>\n";
	    }
	  print "</td></tr>";
	  print '<tr><td>'.$langs->trans("Price").'</td><TD>'.price($livre->price).'</td></tr>';    
	  print '<tr><td>Frais de port</td><td>';
	  if ($livre->frais_de_port)
	    {
	      print 'oui</td></tr>';
	    }
	  else
	    {
	      print 'non</td></tr>';
	    }
	  print "</table>";
	}
      else
	{
	  print "Fetch failed";
	}
    

    }
  else
    {
      print "Error";
    }
}

/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print '<div class="tabsAction">';
if ($action != 'create')
{
  print '<a class="tabAction" href="fiche.php?action=edit&id='.$id.'">'.$langs->trans("Edit").'</a>';
}

print '<a class="tabAction" href="fiche.php?action=delete&id='.$id.'">'.$langs->trans("Delete").'</a>';
print '</div>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
