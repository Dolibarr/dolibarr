<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *                         Jean-Louis Bergamo <jlb@j1b.org>
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
require("../../adherent.class.php");
require("../../adherent_type.class.php");
require("../../cotisation.class.php");
require("../../paiement.class.php");


$db = new Db();
$errmsg='';
$num=0;

if ($HTTP_POST_VARS["action"] == 'add') 
{
  // test si le login existe deja
  $sql = "SELECT login FROM llx_adherent WHERE login='$login';";
  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
  }
  
  if (isset($email) && $email != '' && ereg('@',$email) && !$num){
    // email a peu pres correct et le login n'existe pas
    $adh = new Adherent($db);
    $adh->statut      = -1;
    $adh->prenom      = $prenom;
    $adh->nom         = $nom;  
    $adh->societe     = $societe;
    $adh->adresse     = $adresse;
    $adh->cp          = $cp;
    $adh->ville       = $ville;
    $adh->email       = $email;
    $adh->login       = $login;
    $adh->pass        = $pass;
    $adh->note        = $note;
    $adh->pays        = $pays;
    $adh->typeid      = $type;
    $adh->commentaire = $HTTP_POST_VARS["comment"];
    $adh->morphy      = $HTTP_POST_VARS["morphy"];
    
    if ($adh->create($user->id) ) 
      {	  
	if ($cotisation > 0)
	  {     
	    $adh->cotisation(mktime(12, 0 , 0, $remonth, $reday, $reyear), $cotisation);
	  }
	// Envoi d'un Email de confirmation au nouvel adherent
	$mesg="Merci de votre inscription. Votre adhesion devrait etre rapidement validee.\nVoici le rappel des coordonnees que vous avez rentrees (toute information erronee entrainera la non validation de votre inscription) :\n\nPrenom : $prenom\nNom : $nom\nSociete = $societe\nAdresse = $adresse\nCode Postal : $cp\nVille : $ville\nPays : $pays\nEmail : $email\nLogin : $login\nPassword : $pass\nNote : $note\n\nVous pouvez a tout moment, grace a votre login et mot de passe, modifier vos coordonnees a l'adresse suivante :\nhttp://$SERVER_NAME/adherents/private/edit.php\n\n";
	mail($email,"Votre adhesion sur http://$SERVER_NAME/",$mesg);
	Header("Location: new.php?action=added");
      }
  }else{
    if ($num !=0){
      $errmsg .="Login deja utilise. Veuillez en changer<BR>\n";
    }
    if (!isset($email) || $email == '' || !ereg('@',$email)){
      $errmsg .="Adresse Email invalide<BR>\n";
    }
  }
}

llxHeader();


/* ************************************************************************** */
/*                                                                            */
/* Création d'une fiche                                                       */
/*                                                                            */
/* ************************************************************************** */


if (isset($action) && $action== 'added'){
  print "<FONT COLOR=\"blue\">Nouvel Adhérent ajouté. En attente de validation</FONT><BR>\n";
}
if ($errmsg != ''){
  print "<FONT COLOR=\"red\">$errmsg</FONT><BR>\n";
}

print_titre("Nouvel adhérent");
print "Les login et password vous serviront a editer vos coordonnees ulterieurement<BR>\n";
print "<form action=\"$PHP_SELF\" method=\"post\">\n";
print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';

print '<input type="hidden" name="action" value="add">';

$htmls = new Form($db);
$adht = new AdherentType($db);

print '<tr><td width="15%">Type</td><td width="35%">';
$htmls->select_array("type",  $adht->liste_array());
print "</td>\n";

print '<td width="50%" valign="top">Commentaires :</td></tr>';

$morphys["phy"] = "Physique";
$morphys["mor"] = "Morale";

print "<tr><td>Personne</td><td>\n";
$htmls->select_array("morphy",  $morphys);
print "</td>\n";

print '<td valign="top" rowspan="11"><textarea name="comment" wrap="soft" cols="40" rows="25">'.$comment.'</textarea></td></tr>';

print '<tr><td>Prénom</td><td><input type="text" name="prenom" size="40" value="'.$prenom.'"></td></tr>';  

print '<tr><td>Nom</td><td><input type="text" name="nom" size="40" value="'.$nom.'"></td></tr>';
print '<tr><td>Societe</td><td><input type="text" name="societe" size="40" value="'.$societe.'"></td></tr>';
print '<tr><td>Adresse</td><td>';
print '<textarea name="adresse" wrap="soft" cols="40" rows="3">'.$adresse.'</textarea></td></tr>';
print '<tr><td>CP Ville</td><td><input type="text" name="cp" size="8" value="'.$cp.'"> <input type="text" name="ville" size="40" value="'.$ville.'"></td></tr>';
print '<tr><td>Pays</td><td><input type="text" name="pays" size="40" value="'.$pays.'"></td></tr>';
print '<tr><td>Email</td><td><input type="text" name="email" size="40" value="'.$email.'"></td></tr>';
print '<tr><td>Login</td><td><input type="text" name="login" size="40" value="'.$login.'"></td></tr>';
print '<tr><td>Password</td><td><input type="text" name="pass" size="40" value="'.$pass.'"></td></tr>';

print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer"></td></tr>';
print "</form>\n";
print "</table>\n";

      
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
