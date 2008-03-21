<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2001-2002 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2007      Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/public/adherents/priv_edit.php
        \ingroup    adherent
        \brief      Page edition de sa fiche adherent
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent_type.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent_options.class.php");

$adho = new AdherentOptions($db);

$langs->load("companies");
$langs->load("main");
$langs->load("other");
$langs->load("users");

$errmsg='';
$num=0;
$error=0;


/* 
 * Actions
 */

if ($action == 'update')
{

	if ($_POST["bouton"] == $langs->trans("Save"))
	{
		if (isset($user->login)){
			$adh = new Adherent($db);
			$adh->fetch_login($user->login);
			if ($_POST["rowid"] == $adh->id){
				// user and rowid is the same => good

				// test some values
				// test si le login existe deja
				$sql = "SELECT rowid,login FROM ".MAIN_DB_PREFIX."adherent WHERE login='".$user->login."';";
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
					$obj=$db->fetch_object(0);
					if ($obj->rowid != $adh->id){
						$error+=1;
						$errmsg .="Login deja utilise. Veuillez en changer<BR>\n";
					}
				}
				if (isset($naiss) && $naiss !=''){
					if (!preg_match("/^\d\d\d\d-\d\d-\d\d$/",$naiss)){
						$error+=1;
						$errmsg .="Date de naissance invalide (Format AAAA-MM-JJ)<BR>\n";
					}
				}
				if (!$error){
					// email a peu pres correct et le login n'existe pas
					$adh->id          = $_POST["rowid"];
					$adh->prenom      = $prenom;
					$adh->nom         = $nom;  
					$adh->societe     = $societe;
					$adh->adresse     = $adresse;
					$adh->amount      = $amount;
					$adh->cp          = $cp;
					$adh->ville       = $_POST["ville"];
					$adh->email       = $_POST["email"];
					// interdiction de la modif du login adherent
					//	    $adh->login       = $_POST["login"];
					$adh->login       = $adh->login;
					$adh->pass        = $_POST["pass"];
					$adh->naiss       = $_POST["naiss"];
					$adh->photo       = $_POST["photo"];
					$adh->date        = mktime(12, 0 , 0, $remonth, $reday, $reyear);
					$adh->note        = $_POST["note"];
					$adh->pays        = $_POST["pays"];
					$adh->typeid      = $_POST["type"];
					$adh->note        = $_POST["comment"];
					$adh->morphy      = $_POST["morphy"];
					// recuperation du statut et public
					$adh->statut      = $_POST["statut"];
					if (isset($public)){
						$public=1;
					}else{
						$public=0;
					}
					$adh->public      = $public;
					foreach($_POST as $key => $value){
						if (ereg("^options_",$key)){
							$adh->array_options[$key]=$_POST[$key];
						}
					}
					if ($adh->update($user->id) ) 
					{	  
						$adh->send_an_email($email,$conf->adherent->email_edit,$conf->adherent->email_edit_subject);
						//Header("Location: fiche.php?rowid=$adh->id&action=edit");
						Header("Location: priv_edit.php");
					}
				}
			}else{
				Header("Location: priv_edit.php");
			}
		}
	}
	else
	{
		//Header("Location: fiche.php?rowid=$rowid&action=edit");
		Header("Location: priv_edit.php");
	}
}


llxHeaderVierge();

if (isset($_GET["id"]))
{
	$adh = new Adherent($db);
	$result=$adh->fetch($_GET["id"]);
	$adh->fetch_optionals($adh->id);
	// fetch optionals attibutes
	$adho->fetch_optionals();

	$adht = new AdherentType($db);

	print_titre("Edition de la fiche adhérent de $adh->prenom $adh->nom");

	if ($errmsg != ''){
		print '<table width="100%">';
		
		print '<th>Erreur dans le formulaire</th>';
		print "<tr><td class=\"delete\"><b>$errmsg</b></td></tr>\n";
		//  print "<FONT COLOR=\"red\">$errmsg</FONT>\n";
		print '</table>';
	}

	// Formulaire modifications
	print '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="post" enctype="multipart/form-data">';
	print "<input type=\"hidden\" name=\"action\" value=\"update\">";
	print "<input type=\"hidden\" name=\"rowid\" value=\"$adh->id\">";
	print "<input type=\"hidden\" name=\"statut\" value=\"".$adh->statut."\">";
	print "<input type=\"hidden\" name=\"login\" value=\"".$adh->login."\">";
	print "<input type=\"hidden\" name=\"type\" value=\"".$adh->typeid."\">";
	print "<input type=\"hidden\" name=\"morphy\" value=\"".$adh->morphy."\">";
	//  print "<input type=\"hidden\" name=\"public\" value=\"".$adh->public."\">";

	$htmls = new Form($db);
	$caneditfield=1;
	
	print '<table class="border" width="100%">';

	// Nom
	print '<tr><td>'.$langs->trans("Lastname").'*</td><td><input type="text" name="nom" size="40" value="'.$adh->nom.'"></td>';

	// Photo
	$rowspan=14;
	$rowspan+=sizeof($adho->attribute_label);
    print '<td align="center" valign="middle" width="25%" rowspan="'.$rowspan.'">';
    if (file_exists($conf->adherent->dir_output."/".$adh->id.".jpg"))
    {
        print '<img width="100" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=memberphoto&file='.$adh->id.'.jpg">';
    }
    else
    {
        print '<img src="'.DOL_URL_ROOT.'/theme/common/nophoto.jpg">';
    }
    if ($caneditfield)
    {
        print '<br><br><table class="noborder"><tr><td>'.$langs->trans("PhotoFile").'</td></tr>';
        print '<tr><td>';
        print '<input type="file" class="flat" name="photo">';
        print '</td></tr></table>';
	}
	print '</td>';
	print '</tr>';
	
	// Prenom
	print '<tr><td width="20%">'.$langs->trans("Firstname").'*</td><td width="35%"><input type="text" name="prenom" size="40" value="'.$adh->prenom.'"></td>';
	print '</tr>';
	
	// Login
//	print '<tr><td>'.$langs->trans("Login").'*</td><td><input type="text" name="login" size="30" value="'.$adh->login.'"></td></tr>';
	print '<tr><td>'.$langs->trans("Login").'*</td><td>'.$adh->login.'</td></tr>';
	
	// Password
	print '<tr><td>'.$langs->trans("Password").'*</td><td><input type="password" name="pass" size="30" value="'.$adh->pass.'"></td></tr>';

	// Société
	print '<tr><td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" size="40" value="'.$adh->societe.'"></td></tr>';

	// Adresse
	print '<tr><td>'.$langs->trans("Address").'</td><td>';
	print '<textarea name="adresse" wrap="soft" cols="40" rows="2">'.$adh->adresse.'</textarea></td></tr>';

	// Cp
	print '<tr><td>'.$langs->trans("Zip").'/'.$langs->trans("Town").'</td><td><input type="text" name="cp" size="6" value="'.$adh->cp.'"> <input type="text" name="ville" size="32" value="'.$adh->ville.'"></td></tr>';

	// Pays
	print '<tr><td>'.$langs->trans("Country").'</td><td>';
	$htmls->select_pays($adh->pays_code?$adh->pays_code:$mysoc->pays_code,'pays');
	print '</td></tr>';

	// Tel
	print '<tr><td>'.$langs->trans("PhonePro").'</td><td><input type="text" name="phone" size="20" value="'.$adh->phone.'"></td></tr>';

	// Tel perso
	print '<tr><td>'.$langs->trans("PhonePerso").'</td><td><input type="text" name="phone_perso" size="20" value="'.$adh->phone_perso.'"></td></tr>';

	// Tel mobile
	print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td><input type="text" name="phone_mobile" size="20" value="'.$adh->phone_mobile.'"></td></tr>';

	// EMail
	print '<tr><td>'.$langs->trans("EMail").($conf->global->ADHERENT_MAIL_REQUIRED?'*':'').'</td><td><input type="text" name="email" size="40" value="'.$adh->email.'"></td></tr>';

	// Date naissance
    print "<tr><td>".$langs->trans("Birthday")."</td><td>\n";
    $htmls->select_date(($adh->naiss ? $adh->naiss : -1),'naiss','','',1,'update');
    print "</td></tr>\n";

	// Profil public
    print "<tr><td>".$langs->trans("Public")."</td><td>\n";
    print $htmls->selectyesno("public",$adh->public,1);
    print "</td></tr>\n";

	// Attributs supplémentaires
	foreach($adho->attribute_label as $key=>$value)
	{
		print "<tr><td>$value</td><td><input type=\"text\" name=\"options_$key\" size=\"40\" value=\"".$adh->array_options["options_$key"]."\"></td></tr>\n";
	}

	print '<tr><td colspan="3" align="center">';
	print '<input type="submit" class="button" name="submit" value="'.$langs->trans("Save").'">';
//	print ' &nbsp; &nbsp; &nbsp; ';
//	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</td></tr>';

	print '</table>';
	
	print '</form>';
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
