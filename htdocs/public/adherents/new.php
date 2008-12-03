<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2001-2002 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
 \file       htdocs/public/adherents/new.php
 \ingroup    adherent
 \brief      Form example to add a new member
 \version    $Id$
 */

require("../../master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent_type.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent_options.class.php");
require_once(DOL_DOCUMENT_ROOT."/html.form.class.php");

$langs->setDefaultLang('auto');

$langs->load("main");
$langs->load("members");
$langs->load("companies");


function llxHeaderVierge($title, $head = "")
{
	global $user, $conf, $langs;

	print "<html>\n";
	print "<head>\n";
	print "<title>".$title."</title>\n";
	if ($head) print $head."\n";
	print "</head>\n";
	print "<body>\n";
}

function llxFooter()
{
	print "</body>\n";
	print "</html>\n";
}


$adho = new AdherentOptions($db);
$errmsg='';
$num=0;
$error=0;



/*
 * Actions
 */
if ($_POST["action"] == 'add')
{
	// test si le login existe deja
	$login=$_POST["login"];
	if(!isset($_POST["login"]) || $_POST["login"]='')
		{
		$error+=1;
		$errmsg .="Login $login vide. Veuillez en positionner un<BR>\n";
	}
	$sql = "SELECT login FROM ".MAIN_DB_PREFIX."adherent WHERE login='".$login."';";
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows();
	}
	if (!isset($_POST["nom"]) || !isset($_POST["prenom"]) || $_POST["prenom"]=='' || $_POST["nom"]==''){
		$error+=1;
		$errmsg .="Nom et Prenom obligatoires<BR>\n";
	}
	if (!isset($_POST["email"]) || $_POST["email"] == '' || !ereg('@',$_POST["email"])){
		$error+=1;
		$errmsg .="Adresse Email invalide<BR>\n";
	}
	if ($num !=0){
		$error+=1;
		$errmsg .="Login ".$login." deja utilise. Veuillez en changer<BR>\n";
	}
	if (!isset($_POST["pass1"]) || !isset($_POST["pass2"]) || $_POST["pass1"] == '' || $_POST["pass2"] == '' || $_POST["pass1"]!=$_POST["pass2"]){
		$error+=1;
		$errmsg .="Password invalide<BR>\n";
	}
	if (isset($_POST["naiss"]) && $_POST["naiss"] !=''){
		if (!preg_match("/^\d\d\d\d-\d\d-\d\d$/",$_POST["naiss"])){
			$error+=1;
			$errmsg .="Date de naissance invalide (Format AAAA-MM-JJ)<BR>\n";
		}
	}
	if (isset($public)){
		$public=1;
	}else{
		$public=0;
	}

	if (! $error)
	{
		// email a peu pres correct et le login n'existe pas
		$adh = new Adherent($db);
		$adh->statut      = -1;
		$adh->public      = $_POST["public"];
		$adh->prenom      = $_POST["prenom"];
		$adh->nom         = $_POST["nom"];
		$adh->societe     = $_POST["societe"];
		$adh->adresse     = $_POST["adresse"];
		$adh->cp          = $_POST["cp"];
		$adh->ville       = $_POST["ville"];
		$adh->email       = $_POST["email"];
		$adh->login       = $login;
		$adh->pass        = $_POST["pass1"];
		$adh->naiss       = $_POST["naiss"];
		$adh->photo       = $_POST["photo"];
		$adh->note        = $_POST["note"];
		$adh->pays        = $_POST["pays"];
		$adh->typeid      = $_POST["type"];
		$adh->note        = $_POST["comment"];
		$adh->morphy      = $_POST["morphy"];

		foreach($_POST as $key => $value){
			if (ereg("^options_",$key)){
				$adh->array_options[$key]=$_POST[$key];
			}
		}

		$result=$adh->create($user->id);
		if ($result > 0)
		{
			if ($cotisation > 0)
			{
				$adh->cotisation(mktime(12, 0 , 0, $remonth, $reday, $reyear), $cotisation);
			}

			// Envoi d'un Email de confirmation au nouvel adherent
			$adh->send_an_email($email,$conf->adherent->email_new,$conf->adherent->email_new_subject);
			Header("Location: new.php?action=added");
			exit;
		}
	}
}

// On vient de s'inscrire avec succes
if (isset($_GET["action"]) && $_GET["action"] == 'added' && $conf->global->MEMBER_URL_REDIRECT_SUBSCRIPTION)
{
	// Si conf->global->MEMBER_URL_REDIRECT_SBUSCRIPTION defini, faire redirect sur page.
	Header("Location: ".$conf->global->MEMBER_URL_REDIRECT_SUBSCRIPTION);
	exit;
}



llxHeaderVierge("New member form");
$html = new Form($db);

print_titre($langs->trans("NewMember"));


/* ************************************************************************** */
/*                                                                            */
/* Creation d'une fiche
 /*                                                                            */
/* ************************************************************************** */

$adht = new AdherentType($db);

// fetch optionals attributes and labels
$adho->fetch_optionals();

if (isset($_GET["action"]) && $_GET["action"] == 'added')
{
	// Si on a pas ete redirige
	print '<br>';
	print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
	print "<tr><td><FONT COLOR=\"blue\">Nouvel Adherent ajoute. En attente de validation</FONT></td></tr>\n";
	print '</table>';
}

if ($errmsg != '')
{
	print '<br>';
	print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
	print '<th>Erreur dans le formulaire</th>';
	print "<tr><td class=\"delete\"><b>'.$errmsg.'</b></td></tr>\n";
	//  print "<FONT COLOR=\"red\">$errmsg</FONT>\n";
	print '</table>';
}

if (defined("ADH_TEXT_NEW_ADH") && ADH_TEXT_NEW_ADH !='')
{
	print ADH_TEXT_NEW_ADH;
	print "<BR>\n";
}
print '<ul>';
print '<li> Les champs Commencant par un <FONT COLOR="red">*</FONT> sont obligatoire';
print '<li> Les champs Commencant par un <FONT COLOR="blue">*</FONT> seront affiche sur la liste publique des membres. Si vous ne souhaite pas cela <b>DECOCHEZ</b> la case public ci dessous';
print "</ul><BR>\n";

print "<form action=\"new.php\" method=\"POST\">\n";
print '<input type="hidden" name="action" value="add">';

print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';

print '<tr><td width="15%">'.$langs->trans("Type").'</td><td width="35%">';
$html->select_array("type",  $adht->liste_array());
print "</td>\n";

print '<td width="50%" valign="top">'.$langs->trans("Comments").' :</td></tr>';

$morphys["phy"] = "Physique";
$morphys["mor"] = "Morale";
print '<tr><td>'.$langs->trans("MorPhy")."</td><td>\n";
$html->select_array("morphy",  $morphys);
print "</td>\n";

print '<td valign="top" rowspan="14"><textarea name="comment" wrap="soft" cols="40" rows="25">'.$comment.'</textarea></td></tr>';

print '<tr><td><FONT COLOR="red">*</FONT> <FONT COLOR="blue">*</FONT> '.$langs->trans("Surname").'</td><td><input type="text" name="prenom" size="40" value="'.$prenom.'"></td></tr>';  

print '<tr><td><FONT COLOR="red">*</FONT> <FONT COLOR="blue">*</FONT> '.$langs->trans("Name").'</td><td><input type="text" name="nom" size="40" value="'.$nom.'"></td></tr>';
print '<tr><td>'.$langs->trans("ThirdParty").'</td><td><input type="text" name="societe" size="40" value="'.$societe.'"></td></tr>';
print '<tr><td>'.$langs->trans("Address").'</td><td>';
print '<textarea name="adresse" wrap="soft" cols="40" rows="3">'.$adresse.'</textarea></td></tr>';
print '<tr><td>'.$langs->trans("Zip").'/'.$langs->trans("Town").'</td><td><input type="text" name="cp" size="8" value="'.$cp.'"> <input type="text" name="ville" size="40" value="'.$ville.'"></td></tr>';
print '<tr><td>'.$langs->trans("Country").'</td><td><input type="text" name="pays" size="40" value="'.$pays.'"></td></tr>';
print '<tr><td><FONT COLOR="red">*</FONT> <FONT COLOR="blue">*</FONT> Email</td><td><input type="text" name="email" size="40" value="'.$email.'"></td></tr>';
print '<tr><td><FONT COLOR="red">*</FONT> '.$langs->trans("Login").'</td><td><input type="text" name="login" size="40" value="'.$login.'"></td></tr>';
print '<tr><td><FONT COLOR="red">*</FONT> '.$langs->trans("Password").'</td><td><input type="password" name="pass1" size="40"><BR><input type="password" name="pass2" size="40"></td></tr>';
print '<tr><td>Date de naissance<BR>Format AAAA-MM-JJ</td><td><input type="text" name="naiss" size="40" value="'.$naiss.'"></td></tr>';
print '<tr><td><FONT COLOR="blue">*</FONT> URL Photo</td><td><input type="text" name="photo" size="40" value="'.$photo.'"></td></tr>';
print '<tr><td>Profil public ?</td><td><input type="checkbox" name="public" checked></td></tr>';
foreach($adho->attribute_label as $key=>$value){
	print "<tr><td>$value</td><td><input type=\"text\" name=\"options_$key\" size=\"40\"></td></tr>\n";
}
print '<tr><td colspan="2" align="center"><input type="submit" value="'.$langs->trans("Save").'"></td></tr>';
print "</table>\n";

print "</form>\n";

$db->close();

llxFooter('$Date$ - $Revision$');
?>
