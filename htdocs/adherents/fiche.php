<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo <jlb@j1b.org>
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
require("../adherent.class.php");
require("../adherent_type.class.php");
require("../cotisation.class.php");
require("../paiement.class.php");


$db = new Db();

if ($HTTP_POST_VARS["action"] == 'cotisation') 
{
  $adh = new Adherent($db);
  $adh->id = $rowid;

  if ($cotisation > 0)
    {     
      $adh->cotisation(mktime(12, 0 , 0, $remonth, $reday, $reyear), $cotisation);
    }
  $action = "edit";
}

if ($HTTP_POST_VARS["action"] == 'add') 
{

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
  $adh->naiss       = $naiss;
  $adh->photo       = $photo;
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
      Header("Location: liste.php");
    }
}

if ($HTTP_POST_VARS["action"] == 'confirm_delete' && $HTTP_POST_VARS["confirm"] == yes)
{
  $adh = new Adherent($db);
  $adh->delete($rowid);
  Header("Location: liste.php");
}

if ($HTTP_POST_VARS["action"] == 'confirm_valid' && $HTTP_POST_VARS["confirm"] == yes)
{
  $adh = new Adherent($db, $rowid);
  $adh->validate($user->id);
}

if ($HTTP_POST_VARS["action"] == 'confirm_resign' && $HTTP_POST_VARS["confirm"] == yes)
{
  $adh = new Adherent($db, $rowid);
  $adh->resiliate($user->id);
}


llxHeader();

/* ************************************************************************** */
/*                                                                            */
/* Création d'une fiche                                                       */
/*                                                                            */
/* ************************************************************************** */


if ($action == 'create') {

  $sql = "SELECT s.nom,s.idp, f.amount, f.total, f.facnumber";
  $sql .= " FROM societe as s, llx_facture as f WHERE f.fk_soc = s.idp";
  $sql .= " AND f.rowid = $facid";

  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
    if ($num) {
      $obj = $db->fetch_object( 0);

      $total = $obj->total;
    }
  }
  print_titre("Nouvel adhérent");
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
  
  print '<td valign="top" rowspan="13"><textarea name="comment" wrap="soft" cols="40" rows="25"></textarea></td></tr>';

  print '<tr><td>Prénom</td><td><input type="text" name="prenom" size="40"></td></tr>';  
  




  print '<tr><td>Nom</td><td><input type="text" name="nom" size="40"></td></tr>';
  print '<tr><td>Societe</td><td><input type="text" name="societe" size="40"></td></tr>';
  print '<tr><td>Adresse</td><td>';
  print '<textarea name="adresse" wrap="soft" cols="40" rows="3"></textarea></td></tr>';
  print '<tr><td>CP Ville</td><td><input type="text" name="cp" size="8"> <input type="text" name="ville" size="40"></td></tr>';
  print '<tr><td>Pays</td><td><input type="text" name="pays" size="40"></td></tr>';
  print '<tr><td>Email</td><td><input type="text" name="email" size="40"></td></tr>';
  print '<tr><td>Login</td><td><input type="text" name="login" size="40"></td></tr>';
  print '<tr><td>Password</td><td><input type="text" name="pass" size="40"></td></tr>';
  print '<tr><td>Date de Naissance<BR>Format AAAA-MM-JJ</td><td><input type="text" name="naiss" size="10"></td></tr>';
  print '<tr><td>Url photo</td><td><input type="text" name="photo" size="40"></td></tr>';

  print "<tr><td>Date de cotisation</td><td>\n";
  print_date_select();
  print "</td></tr>\n";
  print "<tr><td>Mode de paiement</td><td>\n";
  
  $paiement = new Paiement($db);

  $paiement->select("modepaiement","crédit");

  print "</td></tr>\n";

  print '<tr><td>Cotisation</td><td><input type="text" name="cotisation" size="6"> euros</td></tr>';

  print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer"></td></tr>';
  print "</form>\n";
  print "</table>\n";
  
      
} 
/* ************************************************************************** */
/*                                                                            */
/* Edition de la fiche                                                        */
/*                                                                            */
/* ************************************************************************** */
if ($rowid > 0)
{

  $adh = new Adherent($db);
  $adh->id = $rowid;
  $adh->fetch($rowid);

  print_titre("Edition de la fiche adhérent");

  /*
   * Confirmation de la suppression de l'adhérent
   *
   */

  if ($action == 'delete')
    {

      print '<form method="post" action="'.$PHP_SELF.'?rowid='.$rowid.'">';
      print '<input type="hidden" name="action" value="confirm_delete">';
      print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
      
      print '<tr><td colspan="3">Supprimer un adhérent</td></tr>';
      print "<tr><td colspan=\"3\">La suppression d'un adhérent entraine la suppression de toutes ses cotisations !!!</td></tr>\n";
      
      print '<tr><td class="delete">Etes-vous sur de vouloir supprimer cet adhérent ?</td><td class="delete">';
      $htmls = new Form($db);
      
      $htmls->selectyesno("confirm","no");
      
      print "</td>\n";
      print '<td class="delete" align="center"><input type="submit" value="Confirmer"</td></tr>';
      print '</table>';
      print "</form>\n";  
    }


  /*
   * Confirmation de la validation
   *
   */

  if ($action == 'valid')
    {

      print '<form method="post" action="'.$PHP_SELF.'?rowid='.$rowid.'">';
      print '<input type="hidden" name="action" value="confirm_valid">';
      print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
      
      print '<tr><td colspan="3">Valider un adhérent</td></tr>';
      
      print '<tr><td class="valid">Etes-vous sur de vouloir valider cet adhérent ?</td><td class="valid">';
      $htmls = new Form($db);
      
      $htmls->selectyesno("confirm","no");
      
      print "</td>\n";
      print '<td class="valid" align="center"><input type="submit" value="Confirmer"</td></tr>';
      print '</table>';
      print "</form>\n";  
    }

  /*
   * Confirmation de la Résiliation
   *
   */

  if ($action == 'resign')
    {

      print '<form method="post" action="'.$PHP_SELF.'?rowid='.$rowid.'">';
      print '<input type="hidden" name="action" value="confirm_resign">';
      print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
      
      print '<tr><td colspan="3">Résilier une adhésion</td></tr>';
      
      print '<tr><td class="delete">Etes-vous sur de vouloir résilier cette adhésion ?</td><td class="delete">';
      $htmls = new Form($db);
      
      $htmls->selectyesno("confirm","no");
      
      print "</td>\n";
      print '<td class="delete" align="center"><input type="submit" value="Confirmer"</td></tr>';
      print '</table>';
      print "</form>\n";  
    }


  print "<form action=\"$PHP_SELF\" method=\"post\">\n";
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';

  print "<tr><td>Type</td><td class=\"valeur\">$adh->type</td>\n";
  print '<td valign="top" width="50%">Commentaires</tr>';

  print '<tr><td>Personne</td><td class="valeur">'.$adh->morphy.'&nbsp;</td>';

  print '<td rowspan="13" valign="top" width="50%">';
  print nl2br($adh->commentaire).'&nbsp;</td></tr>';

  print '<tr><td width="15%">Prénom</td><td class="valeur" width="35%">'.$adh->prenom.'&nbsp;</td></tr>';

  print '<tr><td>Nom</td><td class="valeur">'.$adh->nom.'&nbsp;</td></tr>';
  

  print '<tr><td>Société</td><td class="valeur">'.$adh->societe.'&nbsp;</td></tr>';
  print '<tr><td>Adresse</td><td class="valeur">'.nl2br($adh->adresse).'&nbsp;</td></tr>';
  print '<tr><td>CP Ville</td><td class="valeur">'.$adh->cp.' '.$adh->ville.'&nbsp;</td></tr>';
  print '<tr><td>Pays</td><td class="valeur">'.$adh->pays.'&nbsp;</td></tr>';
  print '<tr><td>Email</td><td class="valeur">'.$adh->email.'&nbsp;</td></tr>';
  print '<tr><td>Login</td><td class="valeur">'.$adh->login.'&nbsp;</td></tr>';
  print '<tr><td>Pass</td><td class="valeur">'.$adh->pass.'&nbsp;</td></tr>';
  print '<tr><td>Date de Naissance</td><td class="valeur">'.$adh->naiss.'&nbsp;</td></tr>';
  print '<tr><td>URL Photo</td><td class="valeur">'.$adh->photo.'&nbsp;</td></tr>';

  print "</table>\n";


  if ($user->admin)
    {
  
      print "<p><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\"><tr>\n";
      
      /*
       * Case 1
       */
      
      print '<td align="center" width="25%">[<a href="edit.php?rowid='.$adh->id.'">Editer</a>]</td>';
      
      /*
       * Case 2
       */
      
      if ($adh->statut < 1) 
	{
	  print "<td align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?rowid=$rowid&action=valid\">Valider l'adhésion</a>]</td>\n";
	}
      else
	{
	  print "<td align=\"center\" width=\"25%\">-</td>\n";
	}
      /*
       * Case 3
       */
      if ($adh->statut == 1) 
	{
	  print "<td align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?rowid=$rowid&action=resign\">Résilier l'adhésion</a>]</td>\n";
	}
      else
	{
	  print "<td align=\"center\" width=\"25%\">-</td>\n";
	}
      
      /*
       * Case 4
       */

      print "<td align=\"center\" width=\"25%\">[<a href=\"$PHP_SELF?rowid=$adh->id&action=delete\">Supprimer</a>]</td>\n";


      print "</tr></table></form><p>\n";
    }

  /*
   * Cotisations
   *
   *
   */

  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
  
  print '<tr>';

  print '<td rowspan="6" valign="top">';

  /*
   *
   * Liste des cotisations
   *
   */
  $sql = "SELECT d.rowid, d.prenom, d.nom, d.societe, c.cotisation, ".$db->pdate("c.dateadh")." as dateadh";
  $sql .= " FROM llx_adherent as d, llx_cotisation as c";
  $sql .= " WHERE d.rowid = c.fk_adherent AND d.rowid=$rowid";

  $result = $db->query($sql);
  if ($result) 
    {
      $num = $db->num_rows();
      $i = 0;
  
      print "<TABLE border=\"0\" cellspacing=\"0\" cellpadding=\"4\">\n";

      print '<TR class="liste_titre">';
      print "<td>Cotisations</td>\n";
      print "<td>Date</td>\n";
      print "<td align=\"right\">Montant</TD>\n";
      print "</TR>\n";
      
      $var=True;
      while ($i < $num)
	{
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	  print "<TR $bc[$var]><td>&nbsp;</td>";
	  print "<TD>".strftime("%d %B %Y",$objp->dateadh)."</td>\n";
	  print '<TD align="right">'.price($objp->cotisation).'</TD>';
	  print "</tr>";
	  $i++;
	}
      print "</table>";
    }
  else
    {
      print $sql;
      print $db->error();
    }

  print '</td>';




  /*
   * Ajout d'une nouvelle cotis
   *
   *
   */
  if ($user->admin)
    {
      print '<form method="post" action="'.$PHP_SELF.'?rowid='.$rowid.'&action=edit">';
      print '<input type="hidden" name="action" value="cotisation">';

      print '<td width="15%">Fin adhésion</td>';
      if ($adh->datefin < time())
	{
	  print '<td width="35%" class="delete">';
	}
      else
	{
	  print '<td width="35%" class="valeur">';
	}
      print strftime("%d %B %Y",$adh->datefin).'&nbsp;</td>';

      print '</tr>';
      
      print '<tr><td colspan="2">Nouvelle adhésion</td></tr>';
      
      print "<tr><td>Date de cotisation</td><td>\n";
      if ($adh->datefin > 0)
	{
	  print_date_select($adh->datefin + (3600*24));
	}
      else
	{
	  print_date_select();
	}
      print "</td></tr>";
      print "<tr><td>Mode de paiement</td><td>\n";
      
      $paiement = new Paiement($db);
      
      $paiement->select("modepaiement","crédit");
      
      print "</td></tr>\n";
      print '<tr><td>Cotisation</td><td colspan="2"><input type="text" name="cotisation" size="6"> euros</td></tr>';
      print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer"</td></tr>';
      print "</form>\n";  
    }


  print '</table>';


}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
