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
require(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require(DOL_DOCUMENT_ROOT."/adherents/adherent_type.class.php");
require(DOL_DOCUMENT_ROOT."/adherents/adherent_options.class.php");
require(DOL_DOCUMENT_ROOT."/adherents/cotisation.class.php");
require(DOL_DOCUMENT_ROOT."/paiement.class.php");
require(DOL_DOCUMENT_ROOT."/adherents/XML-RPC.functions.php");

$adho = new AdherentOptions($db);
$errmsg='';

if (isset($action) && $action=='sendinfo')
{
  $adh = new Adherent($db);
  $adh->id = $rowid;
  $adh->fetch($rowid);
  $adh->send_an_email($adh->email,"Voici le contenu de votre fiche\n\n%INFOS%\n\n","Contenu de votre fiche adherent");
}


if ($HTTP_POST_VARS["action"] == 'cotisation') 
{
  $adh = new Adherent($db);
  $adh->id = $rowid;
  $adh->fetch($rowid);

  if ($cotisation > 0)
    {     
      // rajout du nouveau cotisant dans les listes qui vont bien
      //      if (defined("ADHERENT_MAILMAN_LISTS_COTISANT") && ADHERENT_MAILMAN_LISTS_COTISANT!='' && $adh->datefin == "0000-00-00 00:00:00"){
      if (defined("ADHERENT_MAILMAN_LISTS_COTISANT") && ADHERENT_MAILMAN_LISTS_COTISANT!='' && $adh->datefin == 0){
	$adh->add_to_mailman(ADHERENT_MAILMAN_LISTS_COTISANT);
      }
      $adh->cotisation(mktime(12, 0 , 0, $remonth, $reday, $reyear), $cotisation);
      if (defined("ADHERENT_MAIL_COTIS") && defined("ADHERENT_MAIL_COTIS_SUBJECT")){
	$adh->send_an_email($adh->email,ADHERENT_MAIL_COTIS,ADHERENT_MAIL_COTIS_SUBJECT);
      }
    }
  $action = "edit";
}

if ($HTTP_POST_VARS["action"] == 'add') 
{
  // test si le login existe deja
  if(!isset($login) || $login==''){
    $error+=1;
    $errmsg .="Login vide. Veuillez en positionner un<BR>\n";
  }
  $sql = "SELECT login FROM llx_adherent WHERE login='$login';";
  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
  }
  if (!isset($nom) || !isset($prenom) || $prenom=='' || $nom==''){
    $error+=1;
    $errmsg .="Nom et Prenom obligatoires<BR>\n";
  }
  if (!isset($email) || $email == '' || !ereg('@',$email)){
    $error+=1;
    $errmsg .="Adresse Email invalide<BR>\n";
  }
  if ($num !=0){
    $error+=1;
    $errmsg .="Login deja utilise. Veuillez en changer<BR>\n";
  }
  if (!isset($pass) || $pass == '' ){
    $error+=1;
    $errmsg .="Password invalide<BR>\n";
  }
  if (isset($naiss) && $naiss !=''){
    if (!preg_match("/^\d\d\d\d-\d\d-\d\d$/",$naiss)){
      $error+=1;
      $errmsg .="Date de naissance invalide (Format AAAA-MM-JJ)<BR>\n";
    }
  }
  if (isset($public)){
    $public=1;
  }else{
    $public=0;
  }
  if (!$error){
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
    $adh->naiss       = $naiss;
    $adh->photo       = $photo;
    $adh->note        = $note;
    $adh->pays        = $pays;
    $adh->typeid      = $type;
    $adh->commentaire = $HTTP_POST_VARS["comment"];
    $adh->morphy      = $HTTP_POST_VARS["morphy"];
    
    foreach($_POST as $key => $value){
      if (ereg("^options_",$key)){
	$adh->array_options[$key]=$_POST[$key];
      }
    }
    if ($adh->create($user->id) ) 
      {	  
	if ($cotisation > 0)
	  {     
	    $adh->cotisation(mktime(12, 0 , 0, $remonth, $reday, $reyear), $cotisation);
	  }
	Header("Location: liste.php");
      }
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
  $adh->fetch($rowid);

  $adht = new AdherentType($db);
  $adht->fetch($adh->typeid);

  if (isset($adht->mail_valid) && $adht->mail_valid != '')
    {
      $adh->send_an_email($adh->email,$adht->mail_valid,$conf->adherent->email_valid_subject);
    }
  else
    {
      $adh->send_an_email($adh->email,$conf->adherent->email_valid,$conf->adherent->email_valid_subject);
    }
  // rajoute l'utilisateur dans les divers abonnements ..
  if (!$adh->add_to_abo($adht))
    {
      // error
      $errmsg.="echec du rajout de l'utilisateur aux abonnements: ".$adh->errostr."<BR>\n";
    }
  
}

if ($HTTP_POST_VARS["action"] == 'confirm_resign' && $HTTP_POST_VARS["confirm"] == yes)
{
  $adh = new Adherent($db, $rowid);
  $adh->resiliate($user->id);
  $adh->fetch($rowid);

  $adht = new AdherentType($db);
  $adht->fetch($adh->typeid);

  $adh->send_an_email($adh->email,$conf->adherent->email_resil,$conf->adherent->email_resil_subject);

  // supprime l'utilisateur des divers abonnements ..
  if (!$adh->del_to_abo($adht))
    {
      // error
      $errmsg.="echec de la suppression de l'utilisateur aux abonnements: ".$adh->errostr."<BR>\n";
    }
}

llxHeader();

if ($HTTP_POST_VARS["action"] == 'confirm_add_glasnost' && $HTTP_POST_VARS["confirm"] == yes)
{
  $adh = new Adherent($db, $rowid);
  $adh->fetch($rowid);
  $adht = new AdherentType($db);
  $adht->fetch($adh->typeid);
  if ($adht->vote == 'yes'){
    define("XMLRPC_DEBUG", 1);
    if (!$adh->add_to_glasnost()){
      $errmsg.="Echec du rajout de l'utilisateur dans glasnost: ".$adh->errostr."<BR>\n";
    }
    if(defined('MAIN_DEBUG') && MAIN_DEBUG == 1){
      XMLRPC_debug_print();
    }
  }
}

if ($HTTP_POST_VARS["action"] == 'confirm_del_glasnost' && $HTTP_POST_VARS["confirm"] == yes)
{
  $adh = new Adherent($db, $rowid);
  $adh->fetch($rowid);
  $adht = new AdherentType($db);
  $adht->fetch($adh->typeid);
  if ($adht->vote == 'yes'){
    define("XMLRPC_DEBUG", 1);
    if(!$adh->del_to_glasnost()){
      $errmsg.="Echec de la suppression de l'utilisateur dans glasnost: ".$adh->errostr."<BR>\n";
    }
    if(defined('MAIN_DEBUG') && MAIN_DEBUG == 1){
      XMLRPC_debug_print();
    }
  }
}

if ($HTTP_POST_VARS["action"] == 'confirm_del_spip' && $HTTP_POST_VARS["confirm"] == yes)
{
  $adh = new Adherent($db, $rowid);
  $adh->fetch($rowid);
  if(!$adh->del_to_spip()){
    $errmsg.="Echec de la suppression de l'utilisateur dans spip: ".$adh->errostr."<BR>\n";
  }
}

if ($HTTP_POST_VARS["action"] == 'confirm_add_spip' && $HTTP_POST_VARS["confirm"] == yes)
{
  $adh = new Adherent($db, $rowid);
  $adh->fetch($rowid);
  if (!$adh->add_to_spip()){
    $errmsg.="Echec du rajout de l'utilisateur dans spip: ".$adh->errostr."<BR>\n";
  }
}


/* ************************************************************************** */
/*                                                                            */
/* Création d'une fiche                                                       */
/*                                                                            */
/* ************************************************************************** */
if ($errmsg != '')
{
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
  print '<th>Erreur dans l\'execution du  formulaire</th>';
  print "<tr><td class=\"delete\"><b>$errmsg</b></td></tr>\n";
  print '</table>';
}

// fetch optionals attributes and labels
$adho->fetch_optionals();
if ($action == 'create') {

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
  print '<tr><td>Password</td><td><input type="password" name="pass" size="40"></td></tr>';
  print '<tr><td>Date de Naissance<BR>Format AAAA-MM-JJ</td><td><input type="text" name="naiss" size="10"></td></tr>';
  print '<tr><td>Url photo</td><td><input type="text" name="photo" size="40"></td></tr>';
  foreach($adho->attribute_label as $key=>$value){
    print "<tr><td>$value</td><td><input type=\"text\" name=\"options_$key\" size=\"40\"></td></tr>\n";
  }

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
  $adh->fetch_optionals($rowid);
  //$myattr=$adh->fetch_name_optionals();
  $adht = new AdherentType($db);
  $adht->fetch($adh->typeid);

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

  /*
   * Confirmation de l'ajout dans glasnost
   *
   */

  if ($action == 'add_glasnost')
    {

      print '<form method="post" action="'.$PHP_SELF.'?rowid='.$rowid.'">';
      print '<input type="hidden" name="action" value="confirm_add_glasnost">';
      print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
      
      print '<tr><td colspan="3">Valider un adhérent</td></tr>';
      
      print '<tr><td class="valid">Etes-vous sur de vouloir ajouter cet adhérent dans glasnost ? (serveur : '.ADHERENT_GLASNOST_SERVEUR.')</td><td class="valid">';
      $htmls = new Form($db);
      
      $htmls->selectyesno("confirm","no");
      
      print "</td>\n";
      print '<td class="valid" align="center"><input type="submit" value="Confirmer"</td></tr>';
      print '</table>';
      print "</form>\n";  
    }

  /*
   * Confirmation de la suppression dans glasnost
   *
   */

  if ($action == 'del_glasnost')
    {

      print '<form method="post" action="'.$PHP_SELF.'?rowid='.$rowid.'">';
      print '<input type="hidden" name="action" value="confirm_del_glasnost">';
      print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
      
      print '<tr><td colspan="3">Valider un adhérent</td></tr>';
      
      print '<tr><td class="delete">Etes-vous sur de vouloir effacer cet adhérent de glasnost ? (serveur : '.ADHERENT_GLASNOST_SERVEUR.')</td><td class="delete">';
      $htmls = new Form($db);
      
      $htmls->selectyesno("confirm","no");
      
      print "</td>\n";
      print '<td class="delete" align="center"><input type="submit" value="Confirmer"</td></tr>';
      print '</table>';
      print "</form>\n";  
    }

  /*
   * Confirmation de l'ajout dans spip
   *
   */

  if ($action == 'add_spip')
    {

      print '<form method="post" action="'.$PHP_SELF.'?rowid='.$rowid.'">';
      print '<input type="hidden" name="action" value="confirm_add_spip">';
      print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
      
      print '<tr><td colspan="3">Valider un adhérent</td></tr>';
      
      print '<tr><td class="valid">Etes-vous sur de vouloir ajouter cet adhérent dans spip ? (serveur : '.ADHERENT_SPIP_SERVEUR.')</td><td class="valid">';
      $htmls = new Form($db);
      
      $htmls->selectyesno("confirm","no");
      
      print "</td>\n";
      print '<td class="valid" align="center"><input type="submit" value="Confirmer"</td></tr>';
      print '</table>';
      print "</form>\n";  
    }

  /*
   * Confirmation de la suppression dans spip
   *
   */

  if ($action == 'del_spip')
    {

      print '<form method="post" action="'.$PHP_SELF.'?rowid='.$rowid.'">';
      print '<input type="hidden" name="action" value="confirm_del_spip">';
      print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
      
      print '<tr><td colspan="3">Valider un adhérent</td></tr>';
      
      print '<tr><td class="delete">Etes-vous sur de vouloir effacer cet adhérent de glasnost ? (serveur : '.ADHERENT_SPIP_SERVEUR.')</td><td class="delete">';
      $htmls = new Form($db);
      
      $htmls->selectyesno("confirm","no");
      
      print "</td>\n";
      print '<td class="delete" align="center"><input type="submit" value="Confirmer"</td></tr>';
      print '</table>';
      print "</form>\n";  
    }

  print "<form action=\"$PHP_SELF\" method=\"post\">\n";
  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';

  print '<tr><td>Numero</td><td class="valeur">'.$adh->id.'&nbsp;</td>';
  print '<td valign="top" width="50%">Commentaires</tr>';

  print "<tr><td>Type</td><td class=\"valeur\">$adh->type</td>\n";

  print '<td rowspan="13" valign="top" width="50%">';
  print nl2br($adh->commentaire).'&nbsp;</td></tr>';

  print '<tr><td>Personne</td><td class="valeur">'.$adh->morphy.'&nbsp;</td></tr>';



  print '<tr><td width="15%">Prénom</td><td class="valeur" width="35%">'.$adh->prenom.'&nbsp;</td></tr>';

    print '<tr><td>Nom</td><td class="valeur">'.$adh->nom.'&nbsp;</td></tr>';

  print '<tr><td>Société</td><td class="valeur">'.$adh->societe.'&nbsp;</td></tr>';
  print '<tr><td>Adresse</td><td class="valeur">'.nl2br($adh->adresse).'&nbsp;</td></tr>';
  print '<tr><td>CP Ville</td><td class="valeur">'.$adh->cp.' '.$adh->ville.'&nbsp;</td></tr>';
  print '<tr><td>Pays</td><td class="valeur">'.$adh->pays.'&nbsp;</td></tr>';
  print '<tr><td>Email</td><td class="valeur">'.$adh->email.'&nbsp;</td></tr>';
  print '<tr><td>Login</td><td class="valeur">'.$adh->login.'&nbsp;</td></tr>';
  //  print '<tr><td>Pass</td><td class="valeur">'.$adh->pass.'&nbsp;</td></tr>';
  print '<tr><td>Date de Naissance</td><td class="valeur">'.$adh->naiss.'&nbsp;</td></tr>';
  print '<tr><td>URL Photo</td><td class="valeur">'.$adh->photo.'&nbsp;</td></tr>';
  print '<tr><td>Public ?</td><td class="valeur">';
  if ($adh->public==1){
    print 'Yes';
  }else{
    print "No";
  }
  print '&nbsp;</td></tr>';

  //  print "</table>\n";

  //  print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
  //  print '<tr><td colspan="2">Champs optionnels</td></tr>';
  foreach($adho->attribute_label as $key=>$value){
    print "<tr><td>$value</td><td>".$adh->array_options["options_$key"]."&nbsp;</td></tr>\n";
  }
  print "</table>\n";

  if ($user->admin)
    {
  
      print "<p><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\"><tr class=\"barreBouton\">\n";
      
      /*
       * Case 1
       */
      
      print '<td align="center" width="25%" class="bouton">[<a href="edit.php?rowid='.$adh->id.'">Editer</a>]</td>';
      
      /*
       * Case 2
       */
      
      if ($adh->statut < 1) 
	{
	  print "<td align=\"center\" width=\"25%\" class=\"bouton\">[<a href=\"$PHP_SELF?rowid=$rowid&action=valid\">Valider l'adhésion</a>]</td>\n";
	}
      else
	{
	  print "<td align=\"center\" width=\"25%\" class=\"bouton\">-</td>\n";
	}
      /*
       * Case 3
       */
      if ($adh->statut == 1) 
	{
	  print "<td align=\"center\" width=\"25%\" class=\"bouton\">[<a href=\"$PHP_SELF?rowid=$rowid&action=resign\">Résilier l'adhésion</a>]</td>\n";
	}
      else
	{
	  print "<td align=\"center\" width=\"25%\" class=\"bouton\">-</td>\n";
	}
      
      /*
       * Case 4
       */

      print "<td align=\"center\" width=\"25%\" class=\"bouton\">[<a href=\"$PHP_SELF?rowid=$adh->id&action=delete\">Supprimer</a>]</td>\n";
      
      print "</tr><tr class=\"barreBouton\">\n";

      /*
       * bouton : "Envoie des informations"
       */
      print "<td align=\"center\" width=\"25%\" class=\"bouton\" colspan=\"4\">[<a href=\"$PHP_SELF?rowid=$adh->id&action=sendinfo\">Envoyer sa fiche a l'adhérent</a>]</td>\n";

      print "</tr><tr class=\"barreBouton\">\n";

      if ($adht->vote == 'yes' && defined("ADHERENT_USE_GLASNOST") && ADHERENT_USE_GLASNOST ==1){
	define("XMLRPC_DEBUG", 1);
     
	/*
	 * Case 1 & 2
	 */
	/* retrait car bug inexplicable pour l'instant
	if ($adh->is_in_glasnost() == 1){
	  print "<td align=\"center\" width=\"25%\" class=\"bouton\">-</td>\n";
	  print "<td align=\"center\" width=\"25%\" class=\"bouton\">[<a href=\"$PHP_SELF?rowid=$adh->id&action=del_glasnost\">Suppression dans Glasnost</a>]</td>\n";
	}else{
	  print "<td align=\"center\" width=\"25%\" class=\"bouton\">[<a href=\"$PHP_SELF?rowid=$adh->id&action=add_glasnost\">Ajout dans Glasnost</a>]</td>\n";
	  print "<td align=\"center\" width=\"25%\" class=\"bouton\">-</td>\n";
	}
	*/
	print "<td align=\"center\" width=\"25%\" class=\"bouton\">[<a href=\"$PHP_SELF?rowid=$adh->id&action=add_glasnost\">Ajout dans Glasnost</a>]</td>\n";
	print "<td align=\"center\" width=\"25%\" class=\"bouton\">[<a href=\"$PHP_SELF?rowid=$adh->id&action=del_glasnost\">Suppression dans Glasnost</a>]</td>\n";
      }else{
	/*
	 * Case 1
	 */
	print "<td align=\"center\" width=\"25%\" class=\"bouton\">-</td>\n";

	/*
	 * Case 2
	 */
	print "<td align=\"center\" width=\"25%\" class=\"bouton\">-</td>\n";
      }

      if (defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1){
	/*
	 * Case 3 & 4
	 */
	if ($adh->is_in_spip() == 1){
	  print "<td align=\"center\" width=\"20%\" class=\"bouton\">-</td>\n";
	  print "<td align=\"center\" width=\"20%\" class=\"bouton\">[<a href=\"$PHP_SELF?rowid=$adh->id&action=del_spip\">Suppression dans Spip</a>]</td>\n";
	}else{
	  print "<td align=\"center\" width=\"20%\" class=\"bouton\">[<a href=\"$PHP_SELF?rowid=$adh->id&action=add_spip\">Ajout dans Spip</a>]</td>\n";
	  print "<td align=\"center\" width=\"20%\" class=\"bouton\">-</td>\n";
	}

      }else{
	/*
	 * Case 3
	 */
	print "<td align=\"center\" width=\"25%\" class=\"bouton\">-</td>\n";

	/*
	 * Case 4
	 */
	print "<td align=\"center\" width=\"25%\" class=\"bouton\">-</td>\n";
      }

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
