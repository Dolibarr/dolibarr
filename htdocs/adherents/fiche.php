<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/adherents/fiche.php
        \ingroup    adherent
        \brief      Page d'ajout, edition, suppression d'une fiche adhérent
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent_type.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent_options.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/XML-RPC.functions.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");

$langs->load("companies");
$langs->load("bills");
$langs->load("members");
$langs->load("users");

$adho = new AdherentOptions($db);
$errmsg='';

$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];
$rowid=isset($_GET["rowid"])?$_GET["rowid"]:$_POST["rowid"];


if ($_POST["action"] == 'sendinfo')
{
    $adh = new Adherent($db);
    $adh->id = $rowid;
    $adh->fetch($rowid);
    $adh->send_an_email($adh->email,"Voici le contenu de votre fiche\n\n%INFOS%\n\n","Contenu de votre fiche adherent");
}


if ($_POST["action"] == 'cotisation')
{
    $reday=$_POST["reday"];
    $remonth=$_POST["remonth"];
    $reyear=$_POST["reyear"];
    $cotisation=$_POST["cotisation"];

    $adh = new Adherent($db);
    $adh->id = $rowid;
    $adh->fetch($rowid);
    if ($cotisation >= 0)
    {
        // rajout du nouveau cotisant dans les listes qui vont bien
        //      if (defined("ADHERENT_MAILMAN_LISTS_COTISANT") && ADHERENT_MAILMAN_LISTS_COTISANT!='' && $adh->datefin == "0000-00-00 00:00:00"){
        if (defined("ADHERENT_MAILMAN_LISTS_COTISANT") && ADHERENT_MAILMAN_LISTS_COTISANT!='' && $adh->datefin == 0){
            $adh->add_to_mailman(ADHERENT_MAILMAN_LISTS_COTISANT);
        }
        $crowid=$adh->cotisation(mktime(12, 0 , 0, $remonth, $reday, $reyear), $cotisation);
        if (defined("ADHERENT_MAIL_COTIS") && defined("ADHERENT_MAIL_COTIS_SUBJECT")){
            $adh->send_an_email($adh->email,ADHERENT_MAIL_COTIS,ADHERENT_MAIL_COTIS_SUBJECT);
        }
        // insertion dans la gestion banquaire si configure pour
        if (defined("ADHERENT_BANK_USE") && ADHERENT_BANK_USE !=0 &&
        defined("ADHERENT_BANK_USE_AUTO") && ADHERENT_BANK_USE_AUTO !=0){
            $dateop=strftime("%Y%m%d",time());
            $amount=$cotisation;
            $acct=new Account($db,ADHERENT_BANK_ACCOUNT);
            $insertid=$acct->addline($dateop, $_POST["operation"], $_POST["label"], $amount, $_POST["num_chq"],ADHERENT_BANK_CATEGORIE,$user);
            if ($insertid == '')
            {
                dolibarr_print_error($db);
            }
            else
            {
                // met a jour la table cotisation
                $sql="UPDATE ".MAIN_DB_PREFIX."cotisation SET fk_bank=$insertid WHERE rowid=$crowid ";
                $result = $db->query($sql);
                if ($result)
                {
                    //Header("Location: fiche.php");
                }
                else
                {
                    dolibarr_print_error($db);
                }
            }
        }
    }
    $action = "edit";
}

if ($_POST["action"] == 'add')
{
    $type=$_POST["type"];
    $nom=$_POST["nom"];
    $prenom=$_POST["prenom"];
    $societe=$_POST["societe"];
    $adresse=$_POST["adresse"];
    $cp=$_POST["cp"];
    $ville=$_POST["ville"];
    $naiss=$_POST["pays"];
    $email=$_POST["email"];
    $login=$_POST["login"];
    $pass=$_POST["pass"];
    $naiss=$_POST["naiss"];
    $naiss=$_POST["photo"];
    $naiss=$_POST["note"];
    $comment=$_POST["comment"];
    $morphy=$_POST["morphy"];
    $reday=$_POST["reday"];
    $remonth=$_POST["remonth"];
    $reyear=$_POST["reyear"];
    $cotisation=$_POST["cotisation"];

    $adh = new Adherent($db);
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
    $adh->commentaire = $comment;
    $adh->morphy      = $morphy;
    foreach($_POST as $key => $value){
        if (ereg("^options_",$key)){
            $adh->array_options[$key]=$_POST[$key];
        }
    }

    // Test validite des paramètres
    if(!isset($type) || $type==''){
        $error++;
        $errmsg .= $langs->trans("ErrorMemberTypeNotDefined")."<br>\n";
    }
    // Test si le login existe deja
    if(!isset($login) || $login==''){
        $error++;
        $errmsg .= $langs->trans("ErrorFieldRequired",$langs->trans("Login"))."<br>\n";
    }
    else {
        $sql = "SELECT login FROM ".MAIN_DB_PREFIX."adherent WHERE login='$login';";
        $result = $db->query($sql);
        if ($result) {
            $num = $db->num_rows($result);
        }
        if ($num) {
            $error++;
            $errmsg .= $langs->trans("ErrorLoginAlreadyExists",$login)."<br>\n";
        }
    }
    if (!isset($nom) || $nom=='') {
        $error++;
        $errmsg .= $langs->trans("ErrorFieldRequired",$langs->trans("Lastname"))."<br>\n";
    }
    if (!isset($prenom) || $prenom=='') {
        $error++;
        $errmsg .= $langs->trans("ErrorFieldRequired",$langs->trans("Firstname"))."<br>\n";
    }
    if (ADHERENT_MAIL_REQUIRED && ADHERENT_MAIL_REQUIRED == 1 && ! ValidEMail($email)) {
        $error++;
        $errmsg .= $langs->trans("ErrorBadEMail",$email)."<br>\n";
    }
    if (!isset($pass) || $pass == '' ) {
        $error++;
        $errmsg .= $langs->trans("ErrorFieldRequired",$langs->trans("Password"))."<br>\n";
    }
    if (isset($naiss) && $naiss !=''){
        if (!preg_match("/^\d\d\d\d-\d\d-\d\d$/",$naiss)) {
            $error++;
            $errmsg .= $langs->trans("DateSubscription")." (".$langs->trans("DateFormatYYYYMMDD").")<br>\n";
        }
    }
    if (isset($public)) {
        $public=1;
    } else {
        $public=0;
    }
    if (!$error) {

        // Email a peu pres correct et le login n'existe pas
        if ($adh->create($user->id))
        {
            if ($cotisation > 0)
            {
                $crowid=$adh->cotisation(mktime(12, 0 , 0, $remonth, $reday, $reyear), $cotisation);
                // insertion dans la gestion banquaire si configure pour
                if (defined("ADHERENT_BANK_USE") && ADHERENT_BANK_USE !=0 &&
                defined("ADHERENT_BANK_USE_AUTO") && ADHERENT_BANK_USE_AUTO !=0){
                    $dateop=strftime("%Y%m%d",time());
                    $amount=$cotisation;
                    $acct=new Account($db,ADHERENT_BANK_ACCOUNT);
                    $insertid=$acct->addline($dateop, $_POST["operation"], $_POST["label"], $amount, $_POST["num_chq"],ADHERENT_BANK_CATEGORIE);
                    if ($insertid == '')
                    {
                        dolibarr_print_error($db);
                    }
                    else
                    {
                        // met a jour la table cotisation
                        $sql="UPDATE ".MAIN_DB_PREFIX."cotisation SET fk_bank=$insertid WHERE rowid=$crowid ";
                        $result = $db->query($sql);
                        if ($result)
                        {
                            //Header("Location: fiche.php");
                        }
                        else
                        {
                            dolibarr_print_error($db);
                        }
                    }
                }
            }
            Header("Location: liste.php?statut=-1");
        }
        else {
            dolibarr_print_error($db);
        }
    }
    else {
        $action = 'create';   
    }
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes')
{
    $adh = new Adherent($db);
    $adh->delete($rowid);
    Header("Location: liste.php");
}


if ($_POST["action"] == 'confirm_valid' && $_POST["confirm"] == 'yes')
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

if ($_POST["action"] == 'confirm_resign' && $_POST["confirm"] == 'yes')
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

if ($_POST["action"] == 'confirm_add_glasnost' && $_POST["confirm"] == 'yes')
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
        XMLRPC_debug_print();
    }
}

if ($_POST["action"] == 'confirm_del_glasnost' && $_POST["confirm"] == 'yes')
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
        XMLRPC_debug_print();
    }
}

if ($_POST["action"] == 'confirm_del_spip' && $_POST["confirm"] == 'yes')
{
    $adh = new Adherent($db, $rowid);
    $adh->fetch($rowid);
    if(!$adh->del_to_spip()){
        $errmsg.="Echec de la suppression de l'utilisateur dans spip: ".$adh->errostr."<BR>\n";
    }
}

if ($_POST["action"] == 'confirm_add_spip' && $_POST["confirm"] == 'yes')
{
    $adh = new Adherent($db, $rowid);
    $adh->fetch($rowid);
    if (!$adh->add_to_spip()){
        $errmsg.="Echec du rajout de l'utilisateur dans spip: ".$adh->errostr."<BR>\n";
    }
}



llxHeader();



/* ************************************************************************** */
/*                                                                            */
/* Création d'une fiche                                                       */
/*                                                                            */
/* ************************************************************************** */
if ($errmsg != '')
{
    print '<table class="border" width="100%">';
    print "<tr><td class=\"error\"><b>$errmsg</b></td></tr>\n";
    print '</table><br>';
}

// fetch optionals attributes and labels
$adho->fetch_optionals();


if ($action == 'create')
{

    print_titre($langs->trans("NewMember"));
    print "<form action=\"fiche.php\" method=\"post\">\n";
    print '<table class="border" width="100%">';

    print '<input type="hidden" name="action" value="add">';

    $htmls = new Form($db);
    $adht = new AdherentType($db);

    print '<tr><td width="15%">'.$langs->trans("MemberType").'</td><td width="35%">';
    $listetype=$adht->liste_array();
    if (sizeof($listetype)) {
        $htmls->select_array("type", $listetype);
    } else {
        print '<font class="error">'.$langs->trans("NoTypeDefinedGoToSetup").'</font>';   
    }
    print "</td>\n";

    print '<td width="50%" valign="top">'.$langs->trans("Comments").' :</td></tr>';

    $morphys["phy"] = "Physique";
    $morphys["mor"] = "Morale";

    print "<tr><td>".$langs->trans("Person")."</td><td>\n";
    $htmls->select_array("morphy",  $morphys);
    print "</td>\n";

    print '<td valign="top" rowspan="13"><textarea name="comment" wrap="soft" cols="40" rows="25"></textarea></td></tr>';

    print '<tr><td>'.$langs->trans("Firstname").'*</td><td><input type="text" name="prenom" size="40"></td></tr>';
    print '<tr><td>'.$langs->trans("Lastname").'*</td><td><input type="text" name="nom" size="40"></td></tr>';
    print '<tr><td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" size="40"></td></tr>';
    print '<tr><td>'.$langs->trans("Address").'</td><td>';
    print '<textarea name="adresse" wrap="soft" cols="40" rows="3"></textarea></td></tr>';
    print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td><input type="text" name="cp" size="8"> <input type="text" name="ville" size="40"></td></tr>';
    print '<tr><td>'.$langs->trans("Country").'</td><td><input type="text" name="pays" size="40"></td></tr>';
    print '<tr><td>'.$langs->trans("EMail").(ADHERENT_MAIL_REQUIRED&&ADHERENT_MAIL_REQUIRED==1?'*':'').'</td><td><input type="text" name="email" size="40"></td></tr>';
    print '<tr><td>'.$langs->trans("Login").'*</td><td><input type="text" name="login" size="40"></td></tr>';
    print '<tr><td>'.$langs->trans("Password").'*</td><td><input type="password" name="pass" size="40"></td></tr>';
    print '<tr><td>'.$langs->trans("Birthday").'<br>('.$langs->trans("DateFormatYYYYMMDD").')</td><td><input type="text" name="naiss" size="10"></td></tr>';
    print '<tr><td>Url photo</td><td><input type="text" name="photo" size="40"></td></tr>';
    foreach($adho->attribute_label as $key=>$value){
        print "<tr><td>$value</td><td><input type=\"text\" name=\"options_$key\" size=\"40\"></td></tr>\n";
    }

    print "<tr><td>".$langs->trans("DateSubscription")."</td><td>\n";
    $htmls->select_date();
    print "</td></tr>\n";
    print '<tr><td>Mode de paiment</td><td>';

    print '<select name="operation">';
    print '<option value="CHQ" selected>Chèque';
    print '<option value="CB">Carte Bleue';
    print '<option value="DEP">Espece';
    print '<option value="TIP">TIP';
    print '<option value="PRE">PRE';
    print '<option value="VIR">Virement';
    print '</select>';
    //  $paiement = new Paiement($db);

    //  $paiement->select("modepaiement","crédit");

    print "</td></tr>\n";

    if (defined("ADHERENT_BANK_USE") && ADHERENT_BANK_USE !=0 &&
    defined("ADHERENT_BANK_USE_AUTO") && ADHERENT_BANK_USE_AUTO !=0){
        print "<tr><td>Numero de cheque</td><td>\n";
        print '<input name="num_chq" type="text" size="6">';
        print "</td></tr>\n";
    }
    print '<tr><td>'.$langs->trans("Subscription").'</td><td><input type="text" name="cotisation" size="6">'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

    if (defined("ADHERENT_BANK_USE") && ADHERENT_BANK_USE !=0 && defined("ADHERENT_BANK_USE_AUTO") && ADHERENT_BANK_USE_AUTO !=0){
        print '<tr><td>'.$langs->trans("Label").'</td><td><input name="label" type="text" size=20 value="'.$langs->trans("Subscription").' " ></td></tr>';
    }

    print '<tr><td colspan="2" align="center"><input type="submit" value="'.$langs->trans("AddMember").'"></td></tr>';
    print "</form>\n";
    print "</table>\n";


}


/* ************************************************************************** */
/*                                                                            */
/* Edition de la fiche                                                        */
/*                                                                            */
/* ************************************************************************** */
if ($rowid)
{
    $adh = new Adherent($db);
    $adh->id = $rowid;
    $adh->fetch($rowid);
    $adh->fetch_optionals($rowid);

    $adht = new AdherentType($db);
    $adht->fetch($adh->typeid);

    $html = new Form($db);

    /*
    * Affichage onglets
    */
    $h = 0;

    $head[$h][0] = DOL_URL_ROOT.'/adherents/fiche.php?rowid='.$rowid;
    $head[$h][1] = $langs->trans("Member");
    $hselected=$h;
    $h++;

    dolibarr_fiche_head($head, $hselected, $adh->fullname);

    /*
    * Confirmation de la suppression de l'adhérent
    */
    if ($action == 'delete')
    {
        $html->form_confirm("fiche.php?rowid=$rowid","Supprimer un adhérent","Etes-vous sûr de vouloir supprimer cet adhérent (La suppression d'un adhérent entraine la suppression de toutes ses cotisations !)","confirm_delete");
    }

    /*
    * Confirmation de la validation
    */
    if ($action == 'valid')
    {
        $html->form_confirm("fiche.php?rowid=$rowid","Valider un adhérent","Etes-vous sûr de vouloir valider cet adhérent ?","confirm_valid");
    }

    /*
    * Confirmation de la Résiliation
    */
    if ($action == 'resign')
    {
        $html->form_confirm("fiche.php?rowid=$rowid","Résilier une adhésion","Etes-vous sûr de vouloir résilier cet adhérent ?","confirm_resign");
    }

    /*
    * Confirmation de l'ajout dans glasnost
    */
    if ($action == 'add_glasnost')
    {
        $html->form_confirm("fiche.php?rowid=$rowid","Ajouter dans glasnost","Etes-vous sur de vouloir ajouter cet adhérent dans glasnost ? (serveur : ".ADHERENT_GLASNOST_SERVEUR.")","confirm_add_glasnost");
    }

    /*
    * Confirmation de la suppression dans glasnost
    */
    if ($action == 'del_glasnost')
    {
        $html->form_confirm("fiche.php?rowid=$rowid","Supprimer dans glasnost","Etes-vous sur de vouloir effacer cet adhérent dans glasnost ? (serveur : ".ADHERENT_GLASNOST_SERVEUR.")","confirm_del_glasnost");
    }

    /*
    * Confirmation de l'ajout dans spip
    */
    if ($action == 'add_spip')
    {
        $html->form_confirm("fiche.php?rowid=$rowid","Ajouter dans spip","Etes-vous sur de vouloir ajouter cet adhérent dans spip ? (serveur : ".ADHERENT_SPIP_SERVEUR.")","confirm_add_spip");
    }

    /*
    * Confirmation de la suppression dans spip
    */
    if ($action == 'del_spip')
    {
        $html->form_confirm("fiche.php?rowid=$rowid","Supprimer dans spip","Etes-vous sur de vouloir effacer cet adhérent dans spip ? (serveur : ".ADHERENT_SPIP_SERVEUR.")","confirm_del_spip");
        $html->form_confirm("fiche.php?rowid=$rowid","Ajouter dans glasnost","Etes-vous sur de vouloir ajouter cet adhérent dans glasnost ? (serveur : ".ADHERENT_GLASNOST_SERVEUR.")","confirm_del_spip");
    }


    print '<table class="border" width="100%">';
    print '<form action="fiche.php" method="post">';

    print '<tr><td>'.$langs->trans("Ref").'</td><td class="valeur">'.$adh->id.'&nbsp;</td>';
    print '<td valign="top" width="50%">'.$langs->trans("Comments").'</tr>';

    print '<tr><td>'.$langs->trans("Type").'*</td><td class="valeur">'.$adh->type."</td>\n";

    print '<td rowspan="'.(14+count($adh->array_options)).'" valign="top" width="50%">';
    print nl2br($adh->commentaire).'&nbsp;</td></tr>';

    print '<tr><td>Personne</td><td class="valeur">'.$adh->getmorphylib().'&nbsp;</td></tr>';

    print '<tr><td width="15%">'.$langs->trans("Firstname").'*</td><td class="valeur" width="35%">'.$adh->prenom.'&nbsp;</td></tr>';

    print '<tr><td>'.$langs->trans("Lastname").'*</td><td class="valeur">'.$adh->nom.'&nbsp;</td></tr>';

    print '<tr><td>'.$langs->trans("Company").'</td><td class="valeur">'.$adh->societe.'&nbsp;</td></tr>';
    print '<tr><td>'.$langs->trans("Address").'</td><td class="valeur">'.nl2br($adh->adresse).'&nbsp;</td></tr>';
    print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td class="valeur">'.$adh->cp.' '.$adh->ville.'&nbsp;</td></tr>';
    print '<tr><td>'.$langs->trans("Country").'</td><td class="valeur">'.$adh->pays.'&nbsp;</td></tr>';
    print '<tr><td>'.$langs->trans("EMail").(ADHERENT_MAIL_REQUIRED&&ADHERENT_MAIL_REQUIRED==1?'*':'').'</td><td class="valeur">'.$adh->email.'&nbsp;</td></tr>';
    print '<tr><td>'.$langs->trans("Login").'*</td><td class="valeur">'.$adh->login.'&nbsp;</td></tr>';
    //  print '<tr><td>Pass</td><td class="valeur">'.$adh->pass.'&nbsp;</td></tr>';
    print '<tr><td>'.$langs->trans("Birthday").'</td><td class="valeur">'.$adh->naiss.'&nbsp;</td></tr>';
    print '<tr><td>URL Photo</td><td class="valeur">'.$adh->photo.'&nbsp;</td></tr>';
    print '<tr><td>'.$langs->trans("Public").'</td><td class="valeur">';
    if ($adh->public==1) print $langs->trans("Yes");
    else print $langs->trans("No");
    print '</td></tr>';
    print '<tr><td>'.$langs->trans("Status").'</td><td class="valeur">'.$adh->getLibStatut($adh).'</td></tr>';
    
    foreach($adho->attribute_label as $key=>$value){
        print "<tr><td>$value</td><td>".$adh->array_options["options_$key"]."&nbsp;</td></tr>\n";
    }
    
    print '</form>';
    print "</table>\n";
    print "<br>";
    
    print "</div>\n";

    
    /*
     * Barre d'actions
     *
     */
    print '<div class="tabsAction">';
    
    
    print "<a class=\"tabAction\" href=\"edit.php?rowid=$rowid\">".$langs->trans("Edit")."</a>";
    
    // Valider
    if ($adh->statut < 1)
    {
        print "<a class=\"tabAction\" href=\"fiche.php?rowid=$rowid&action=valid\">".$langs->trans("Validate")."</a>\n";
    }
    
    // Envoi fiche par mail
    print "<a class=\"tabAction\" href=\"fiche.php?rowid=$adh->id&action=sendinfo\">".$langs->trans("SendCardByMail")."</a>\n";
    
    // Résilier
    if ($adh->statut == 1)
    {
        print "<a class=\"tabAction\" href=\"fiche.php?rowid=$rowid&action=resign\">".$langs->trans("Resiliate")."</a>\n";
    }
    
    // Supprimer
    if ($user->admin) {
        print "<a class=\"butDelete\" href=\"fiche.php?rowid=$adh->id&action=delete\">".$langs->trans("Delete")."</a>\n";
    }
        
    // Action Glasnost
    if ($adht->vote == 'yes' && defined("ADHERENT_USE_GLASNOST") && ADHERENT_USE_GLASNOST ==1)
    {
        define("XMLRPC_DEBUG", 1);
        $isinglasnost=$adh->is_in_glasnost();
        if ($isinglasnost == 1)
        {
            print "<a class=\"tabAction\" href=\"fiche.php?rowid=$adh->id&action=del_glasnost\">Suppression dans Glasnost</a>\n";
        }
        if ($isinglasnost == 0) {
            print "<a class=\"tabAction\" href=\"fiche.php?rowid=$adh->id&action=add_glasnost\">Ajout dans Glasnost</a>\n";
        }
        if ($isinglasnost == -1) {
            print '<br><font class="error">Failed to connect to SPIP: '.$adh->errorstr.'</font>';
        }
    }
    
    // Action SPIP
    if (defined("ADHERENT_USE_SPIP") && ADHERENT_USE_SPIP ==1)
    {
        $isinspip=$adh->is_in_spip();
        if ($isinspip == 1)
        {
            print "<a class=\"tabAction\" href=\"fiche.php?rowid=$adh->id&action=del_spip\">Suppression dans Spip</a>\n";
        }
        if ($isinspip == 0)
        {
            print "<a class=\"tabAction\" href=\"fiche.php?rowid=$adh->id&action=add_spip\">Ajout dans Spip</a>\n";
        }
        if ($isinspip == -1) {
            print '<br><font class="error">Failed to connect to SPIP: '.$adh->errorstr.'</font>';
        }
    }
    
    print '</div>';
    print "<br>\n";
    
    
    
    /*
     * Bandeau des cotisations
     *
     */
    
    print '<table border=0 width="100%">';
    
    print '<tr>';
    print '<td valign="top">';
    
    
    /*
     * Liste des cotisations
     *
     */
    $sql = "SELECT d.rowid, d.prenom, d.nom, d.societe, c.cotisation, ".$db->pdate("c.dateadh")." as dateadh";
    $sql .= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."cotisation as c";
    $sql .= " WHERE d.rowid = c.fk_adherent AND d.rowid=$rowid";
    
    $result = $db->query($sql);
    if ($result)
    {
        $num = $db->num_rows();
        $i = 0;
    
        print "<table class=\"noborder\" width=\"100%\">\n";
    
        print '<tr class="liste_titre">';
        print "<td>Date cotisations</td>\n";
        print "<td align=\"right\">".$langs->trans("Amount")."</td>\n";
        print "</tr>\n";
    
        $var=True;
        while ($i < $num)
        {
            $objp = $db->fetch_object($result);
            $var=!$var;
            print "<tr $bc[$var]>";
            print "<td>".dolibarr_print_date($objp->dateadh)."</td>\n";
            print '<td align="right">'.price($objp->cotisation).'</td>';
            print "</tr>";
            $i++;
        }
        print "</table>";
    }
    else
    {
        dolibarr_print_error($db);
    }
    
    print '</td><td>';
    
    
    /*
     * Ajout d'une nouvelle cotisation
     *
     */
    // \todo Ajout du droit adherent cotisation
    if ($user->rights->adherent->cotisation || 1==1)
    {
        print "<table class=\"border\" width=\"100%\">\n";
    
        print '<form method="post" action="fiche.php">';
        print '<input type="hidden" name="action" value="cotisation">';
        print '<input type="hidden" name="rowid" value="'.$rowid.'">';
    
        print '<tr><td width="15%">'.$langs->trans("SubscriptionEndDate").'</td>';
        if ($adh->datefin < time())
        {
            print '<td width="35%">';
            print dolibarr_print_date($adh->datefin)." ".img_warning($langs->trans("Late"));
        }
        else
        {
            print '<td width="35%">';
            print dolibarr_print_date($adh->datefin);
        }
        print '</td>';
        print '</tr>';
    
        print '<tr><td colspan="2">'.$langs->trans("NewCotisation").'</td></tr>';
    
        print "<tr><td>Date de cotisation</td><td>\n";
        if ($adh->datefin > 0)
        {
            $html->select_date($adh->datefin + (3600*24));
        }
        else
        {
            $html->select_date();
        }
        print "</td></tr>";
    
    
        print "<tr><td>Mode de paiement</td><td>\n";
        print_type_paiement_select($db,'operation');
        print "</td></tr>\n";
    
        if (defined("ADHERENT_BANK_USE") && ADHERENT_BANK_USE !=0 &&
        defined("ADHERENT_BANK_USE_AUTO") && ADHERENT_BANK_USE_AUTO !=0){
            print "<tr><td>Numero de cheque</td><td>\n";
            print '<input name="num_chq" type="text" size="6">';
            print "</td></tr>\n";
        }
        print '<tr><td>'.$langs->trans("Subscription").'</td><td><input type="text" name="cotisation" size="6"> '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
        if (defined("ADHERENT_BANK_USE") && ADHERENT_BANK_USE !=0 &&
        defined("ADHERENT_BANK_USE_AUTO") && ADHERENT_BANK_USE_AUTO !=0){
            print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2"><input name="label" type="text" size=20 value="Cotisation '.stripslashes($adh->prenom).' '.stripslashes($adh->nom).' '.strftime("%Y",$adh->datefin).'" ></td></tr>';
        }
        print '<tr><td colspan="2" align="center"><input type="submit" value="'.$langs->trans("Save").'"</td></tr>';
    
        print '</form>';
        print '</table>';
    }
    
    print '</td></tr>';
    print '</table>';
    
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
