<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/adherents/fiche.php
 *       \ingroup    adherent
 *       \brief      Page d'ajout, edition, suppression d'une fiche adherent
 *       \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/member.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent_type.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent_options.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/cotisation.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");

$langs->load("companies");
$langs->load("bills");
$langs->load("members");
$langs->load("users");

// Defini si peux creer un utilisateur ou gerer groupe sur un utilisateur
$canadduser=$user->rights->adherent->creer;
// Defini si peux lire/modifier info user ou mot de passe
if ($_GET["rowid"])
{
	$caneditfield=$user->rights->adherent->creer;
	$caneditpassword=$user->rights->adherent->creer;
}
if (! $user->rights->adherent->lire)
{
	accessforbidden();
}


$adh = new Adherent($db);
$adho = new AdherentOptions($db);
$errmsg='';

$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];
$rowid=isset($_GET["rowid"])?$_GET["rowid"]:$_POST["rowid"];
$typeid=isset($_GET["typeid"])?$_GET["typeid"]:$_POST["typeid"];



/*
 * 	Actions
 */

// Create user from a member
if ($_POST["action"] == 'confirm_create_user' && $_POST["confirm"] == 'yes' && $user->rights->user->user->creer)
{
	// Recuperation contact actuel
	$adh = new Adherent($db);
	$result = $adh->fetch($_GET["rowid"]);

	if ($result > 0)
	{
		// Creation user
		$nuser = new User($db);
		$result=$nuser->create_from_member($adh,$_POST["login"]);

		if ($result < 0)
		{
			$langs->load("errors");
			$msg=$langs->trans($nuser->error);
		}
	}
	else
	{
		$msg=$adh->error;
	}
}

// Create third party from a member
if ($_POST["action"] == 'confirm_create_thirdparty' && $_POST["confirm"] == 'yes' && $user->rights->societe->creer)
{
	$adh = new Adherent($db);
	$result = $adh->fetch($_GET["rowid"]);

	if ($result > 0)
	{
		// Creation user
		$company = new Societe($db);
		$result=$company->create_from_member($adh,$_POST["name"]);

		if ($result < 0)
		{
			$langs->load("errors");
			$msg=$langs->trans($company->error);
		}
	}
	else
	{
		$msg=$adh->error;
	}
}

if ($_POST["action"] == 'confirm_sendinfo' && $_POST["confirm"] == 'yes')
{
    $adh->id = $rowid;
    $adh->fetch($rowid);

	if ($adh->email)
	{
		$result=$adh->send_an_email("Voici le contenu de votre fiche\n\n%INFOS%\n\n","Contenu de votre fiche adherent");
	}
}

if ($_REQUEST["action"] == 'update' && ! $_POST["cancel"])
{
	$result=$adh->fetch($_POST["rowid"]);

	// If change (allowed on all members) or (allowed on myself and i am edited memeber)
	if ($user->rights->adherent->creer || ($user->rights->adherent->self->creer && $adh->user_id == $user->id))
	{
		$datenaiss='';
		if (isset($_POST["naissday"]) && $_POST["naissday"]
			&& isset($_POST["naissmonth"]) && $_POST["naissmonth"]
			&& isset($_POST["naissyear"]) && $_POST["naissyear"])
		{
			$datenaiss=dol_mktime(12, 0, 0, $_POST["naissmonth"], $_POST["naissday"], $_POST["naissyear"]);
		}
		//print $_POST["naissmonth"].", ".$_POST["naissday"].", ".$_POST["naissyear"]." ".$datenaiss." ".adodb_strftime('%Y-%m-%d %H:%M:%S',$datenaiss);

		// Charge objet actuel
		if ($result > 0)
		{
			// Modifie valeures
			$adh->prenom      = trim($_POST["prenom"]);
			$adh->nom         = trim($_POST["nom"]);
			$adh->fullname    = trim($adh->prenom.' '.$adh->nom);
			$adh->login       = trim($_POST["login"]);
			$adh->pass        = trim($_POST["pass"]);

			$adh->societe     = trim($_POST["societe"]);
			$adh->adresse     = trim($_POST["adresse"]);
			$adh->cp          = trim($_POST["cp"]);
			$adh->ville       = trim($_POST["ville"]);
			$adh->pays_id     = $_POST["pays"];

			$adh->phone       = trim($_POST["phone"]);
			$adh->phone_perso = trim($_POST["phone_perso"]);
			$adh->phone_mobile= trim($_POST["phone_mobile"]);
			$adh->email       = trim($_POST["email"]);
			$adh->naiss       = $datenaiss;

			$adh->typeid      = $_POST["typeid"];
			$adh->note        = trim($_POST["comment"]);
			$adh->morphy      = $_POST["morphy"];

			$adh->amount      = $_POST["amount"];

			// recuperation du statut et public
			$adh->statut      = $_POST["statut"];
			$adh->public      = $_POST["public"];

			foreach($_POST as $key => $value)
			{
				if (ereg("^options_",$key))
				{
					//escape values from POST, at least with addslashes, to avoid obvious SQL injections
					//(array_options is directly input in the DB in adherent.class.php::update())
					$adh->array_options[$key]=addslashes($_POST[$key]);
				}
			}

			$result=$adh->update($user,0);
			if ($result >= 0 && ! sizeof($adh->errors))
			{
				if (isset($_FILES['photo']['tmp_name']) && trim($_FILES['photo']['tmp_name']))
				{
					// If photo is provided
					if (! is_dir($conf->adherent->dir_output))
					{
						create_exdir($conf->adherent->dir_output);
					}
					if (is_dir($conf->adherent->dir_output))
					{
						$newfile=$conf->adherent->dir_output . "/" . $adh->id . ".jpg";
						if (! dol_move_uploaded_file($_FILES['photo']['tmp_name'],$newfile,1) > 0)
						{
							$message .= '<div class="error">'.$langs->trans("ErrorFailedToSaveFile").'</div>';
						}
					}
				}

				$_GET["rowid"]=$adh->id;
				$_REQUEST["action"]='';
			}
			else
			{
			    if ($adh->error)
				{
					$errmsg=$adh->error;
				}
				else
				{
					foreach($adh->errors as $error)
					{
						if ($errmsg) $errmsg.='<br>';
						$errmsg.=$error;
					}
				}
				$action='';
			}
		}
	}
}

if ($user->rights->adherent->creer && $_POST["action"] == 'add')
{
	$datenaiss='';
	if (isset($_POST["naissday"]) && $_POST["naissday"]
		&& isset($_POST["naissmonth"]) && $_POST["naissmonth"]
		&& isset($_POST["naissyear"]) && $_POST["naissyear"])
	{
		$datenaiss=dol_mktime(12, 0, 0, $_POST["naissmonth"], $_POST["naissday"], $_POST["naissyear"]);
	}
	$datecotisation='';
	if (isset($_POST["reday"]) && isset($_POST["remonth"]) && isset($_POST["reyear"]))
    {
		$datecotisation=dol_mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
	}

    $typeid=$_POST["typeid"];
    $nom=$_POST["nom"];
    $prenom=$_POST["prenom"];
    $societe=$_POST["societe"];
    $adresse=$_POST["adresse"];
    $cp=$_POST["cp"];
    $ville=$_POST["ville"];
    $pays_id=$_POST["pays_id"];

    $phone=$_POST["phone"];
    $phone_perso=$_POST["phone_perso"];
    $phone_mobile=$_POST["phone_mobile"];
    $email=$_POST["member_email"];
    $login=$_POST["member_login"];
    $pass=$_POST["password"];
    $photo=$_POST["photo"];
    $comment=$_POST["comment"];
    $morphy=$_POST["morphy"];
    $cotisation=$_POST["cotisation"];

    $adh->prenom      = $prenom;
    $adh->nom         = $nom;
    $adh->societe     = $societe;
    $adh->adresse     = $adresse;
    $adh->cp          = $cp;
    $adh->ville       = $ville;
    $adh->pays_id     = $pays_id;
    $adh->phone       = $phone;
    $adh->phone_perso = $phone_perso;
    $adh->phone_mobile= $phone_mobile;
    $adh->email       = $email;
    $adh->login       = $login;
    $adh->pass        = $pass;
    $adh->naiss       = $datenaiss;
    $adh->photo       = $photo;
    $adh->typeid      = $typeid;
    $adh->note        = $comment;
    $adh->morphy      = $morphy;
    foreach($_POST as $key => $value){
        if (ereg("^options_",$key)){
			//escape values from POST, at least with addslashes, to avoid obvious SQL injections
			//(array_options is directly input in the DB in adherent.class.php::update())
			$adh->array_options[$key]=addslashes($_POST[$key]);
        }
    }

    // Test validite des parametres
    if (empty($typeid)) {
        $error++;
        $errmsg .= $langs->trans("ErrorMemberTypeNotDefined")."<br>\n";
    }
    // Test si le login existe deja
    if (empty($login)) {
        $error++;
        $errmsg .= $langs->trans("ErrorFieldRequired",$langs->trans("Login"))."<br>\n";
    }
    else {
        $sql = "SELECT login FROM ".MAIN_DB_PREFIX."adherent WHERE login='".$login."'";
        $result = $db->query($sql);
        if ($result) {
            $num = $db->num_rows($result);
        }
        if ($num) {
            $error++;
            $langs->load("errors");
            $errmsg .= $langs->trans("ErrorLoginAlreadyExists",$login)."<br>\n";
        }
    }
    if (empty($nom)) {
        $error++;
        $errmsg .= $langs->trans("ErrorFieldRequired",$langs->transnoentities("Lastname"))."<br>\n";
    }
	if ($morphy != 'mor' && (!isset($prenom) || $prenom=='')) {
		$error++;
        $errmsg .= $langs->trans("ErrorFieldRequired",$langs->transnoentities("Firstname"))."<br>\n";
    }
    if ($conf->global->ADHERENT_MAIL_REQUIRED && ! isValidEMail($email)) {
        $error++;
        $errmsg .= $langs->trans("ErrorBadEMail",$email)."<br>\n";
    }
    if (empty($pass)) {
        $error++;
        $errmsg .= $langs->trans("ErrorFieldRequired",$langs->transnoentities("Password"))."<br>\n";
    }
    $public=0;
    if (isset($public)) $public=1;

    if (! $error)
    {
		$db->begin();

		// Email a peu pres correct et le login n'existe pas
        $result=$adh->create($user);
		if ($result > 0)
        {
			if ($cotisation > 0)
            {
                $crowid=$adh->cotisation($datecotisation, $cotisation);

                // insertion dans la gestion banquaire si configure pour
                if ($global->conf->ADHERENT_BANK_USE)
                {
                    $dateop=time();
                    $amount=$cotisation;
                    $acct=new Account($db,$_POST["accountid"]);
                    $insertid=$acct->addline($dateop, $_POST["operation"], $_POST["label"], $amount, $_POST["num_chq"], '', $user);
                    if ($insertid == '')
                    {
                        dol_print_error($db);
                    }
                    else
                    {
                        // met a jour la table cotisation
                        $sql ="UPDATE ".MAIN_DB_PREFIX."cotisation";
                        $sql.=" SET fk_bank=$insertid WHERE rowid=$crowid ";
                        $result = $db->query($sql);
                        if ($result)
                        {
                            //Header("Location: fiche.php");
                        }
                        else
                        {
                            dol_print_error($db);
                        }
                    }
                }
            }

			$db->commit();

            Header("Location: liste.php?statut=-1");
            exit;
        }
        else
		{
			$db->rollback();

			if ($adh->error) $errmsg=$adh->error;
			else $errmsg=$adh->errors[0];

			$action = 'create';
        }
    }
    else {
        $action = 'create';
    }
}

if ($user->rights->adherent->supprimer && $_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes')
{
	$result=$adh->fetch($rowid);
    $result=$adh->delete($rowid);
    if ($result > 0)
    {
    	Header("Location: liste.php");
    	exit;
    }
    else
    {
    	$mesg=$adh->error;
    }
}

if ($user->rights->adherent->creer && $_POST["action"] == 'confirm_valid' && $_POST["confirm"] == 'yes')
{
	$result=$adh->fetch($rowid);
    $result=$adh->validate($user);

    $adht = new AdherentType($db);
    $adht->fetch($adh->typeid);

	if ($result >= 0 && ! sizeof($adh->errors))
	{
		// Envoi mail validation (selon param du type adherent sinon generique)
		if ($adh->email && $_POST["send_mail"])
		{
			if (isset($adht->mail_valid) && $adht->mail_valid)
		    {
				$result=$adh->send_an_email($adht->mail_valid,$conf->global->ADHERENT_MAIL_VALID_SUBJECT,array(),array(),array(),"","",0,2);
		    }
		    else
		    {
				$result=$adh->send_an_email($conf->global->ADHERENT_MAIL_VALID,$conf->global->ADHERENT_MAIL_VALID_SUBJECT,array(),array(),array(),"","",0,2);
		    }
			if ($result < 0)
			{
				$errmsg.=$adh->error;
			}
		}

	    // Rajoute l'utilisateur dans les divers abonnements (mailman, spip, etc...)
	    if ($adh->add_to_abo($adht) < 0)
	    {
	        // error
	        $errmsg.="Echec du rajout de l'utilisateur aux abonnements mailman: ".$adh->error."<BR>\n";
	    }
	}
	else
	{
	    // \TODO Mettre fonction qui fabrique errmsg depuis this->error||this->errors
	    if ($adh->error)
		{
			$errmsg=$adh->error;
		}
		else
		{
			foreach($adh->errors as $error)
			{
				if ($errmsg) $errmsg.='<br>';
				$errmsg.=$error;
			}
		}
		$action='';
	}
}

if ($user->rights->adherent->supprimer && $_POST["action"] == 'confirm_resign' && $_POST["confirm"] == 'yes')
{
    $result=$adh->fetch($rowid);
    $result=$adh->resiliate($user);

    $adht = new AdherentType($db);
    $adht->fetch($adh->typeid);

	if ($result >= 0 && ! sizeof($adh->errors))
	{
		if ($adh->email && $_POST["send_mail"])
		{
			$result=$adh->send_an_email($conf->global->ADHERENT_MAIL_RESIL,$conf->global->ADHERENT_MAIL_RESIL_SUBJECT,array(),array(),array(),"","",0,-1);
		}
		if ($result < 0)
		{
			$errmsg.=$adh->error;
		}

	    // supprime l'utilisateur des divers abonnements ..
	    if (! $adh->del_to_abo($adht))
	    {
	        // error
	        $errmsg.="Echec de la suppression de l'utilisateur aux abonnements mailman: ".$adh->error."<BR>\n";
	    }
	}
	else
	{
	    // \TODO Mettre fonction qui fabrique errmsg depuis this->error||this->errors
		if ($adh->error)
		{
			$errmsg=$adh->error;
		}
		else
		{
			foreach($adh->errors as $error)
			{
				if ($errmsg) $errmsg.='<br>';
				$errmsg.=$error;
			}
		}
		$action='';
	}
}

if ($user->rights->adherent->supprimer && $_POST["action"] == 'confirm_del_spip' && $_POST["confirm"] == 'yes')
{
    $result=$adh->fetch($rowid);
	if ($result >= 0 && ! sizeof($adh->errors))
	{
	    if(!$adh->del_to_spip()){
	        $errmsg.="Echec de la suppression de l'utilisateur dans spip: ".$adh->error."<BR>\n";
	    }
	}
}

if ($user->rights->adherent->creer && $_POST["action"] == 'confirm_add_spip' && $_POST["confirm"] == 'yes')
{
    $result=$adh->fetch($rowid);
	if ($result >= 0 && ! sizeof($adh->errors))
	{
	    if (!$adh->add_to_spip())
	    {
	        $errmsg.="Echec du rajout de l'utilisateur dans spip: ".$adh->error."<BR>\n";
	    }
	}
}



/*
 * View
 */

llxHeader();


if ($errmsg)
{
    print '<div class="error">'.$errmsg.'</div>';
    print "\n";
}

// fetch optionals attributes and labels
$adho->fetch_name_optionals_label();


if ($action == 'edit')
{
	/********************************************
	 *
	 * Fiche en mode edition
	 *
	 ********************************************/

	$adho = new AdherentOptions($db);
	$adh = new Adherent($db);
	$adh->id = $rowid;
	$adh->fetch($rowid);
	// fetch optionals value
	$adh->fetch_optionals($rowid);
	// fetch optionals attributes and labels
	$adho->fetch_name_optionals_label();

	$adht = new AdherentType($db);
    $adht->fetch($adh->typeid);


	/*
	 * Affichage onglets
	 */
	$head = member_prepare_head($adh);

	dol_fiche_head($head, 'general', $langs->trans("Member"));


	print '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="post" enctype="multipart/form-data">';
	print "<input type=\"hidden\" name=\"action\" value=\"update\">";
	print "<input type=\"hidden\" name=\"rowid\" value=\"$rowid\">";
	print "<input type=\"hidden\" name=\"statut\" value=\"".$adh->statut."\">";

	$htmls = new Form($db);

	print '<table class="border" width="100%">';

    // Ref
    print '<tr><td>'.$langs->trans("Ref").'</td><td class="valeur" colspan="2">'.$adh->id.'&nbsp;</td></tr>';

	// Nom
	print '<tr><td>'.$langs->trans("Lastname").'*</td><td><input type="text" name="nom" size="40" value="'.$adh->nom.'"></td>';

	// Photo
	$rowspan=16;
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
	print '<tr><td>'.$langs->trans("Login").'*</td><td><input type="text" name="login" size="30" value="'.$adh->login.'"></td></tr>';

	// Password
	print '<tr><td>'.$langs->trans("Password").'*</td><td><input type="password" name="pass" size="30" value="'.$adh->pass.'"></td></tr>';

	// Type
	print '<tr><td>'.$langs->trans("Type").'*</td><td>';
	if ($user->rights->adherent->creer)	// If $user->rights->adherent->self->creer, we do not allow.
	{
		$htmls->select_array("typeid",  $adht->liste_array(), $adh->typeid);
	}
	else
	{
		print $adht->getNomUrl(1);
		print '<input type="hidden" name="typeid" value="'.$adh->typeid.'">';
	}
	print "</td></tr>";

	// Physique-Moral
	$morphys["phy"] = $langs->trans("Physical");
	$morphys["mor"] = $langs->trans("Morale");
	print "<tr><td>".$langs->trans("Person")."*</td><td>";
	$htmls->select_array("morphy",  $morphys, $adh->morphy);
	print "</td></tr>";

	// Societe
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

	// Attributs supplementaires
	foreach($adho->attribute_label as $key=>$value)
	{
		print "<tr><td>$value</td><td><input type=\"text\" name=\"options_$key\" size=\"40\" value=\"".$adh->array_options["options_$key"]."\"></td></tr>\n";
	}

	print '<tr><td colspan="3" align="center">';
	print '<input type="submit" class="button" name="submit" value="'.$langs->trans("Save").'">';
	print ' &nbsp; &nbsp; &nbsp; ';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</td></tr>';

	print '</table>';

	print '</form>';

	print '</div>';
}

if ($action == 'create')
{
	/* ************************************************************************** */
	/*                                                                            */
	/* Fiche creation                                                             */
	/*                                                                            */
	/* ************************************************************************** */

    $htmls = new Form($db);
    $adht = new AdherentType($db);

    print_fiche_titre($langs->trans("NewMember"));

	print '<form name="add" action="fiche.php" method="post" enctype="multipart/form-data">';
    print '<input type="hidden" name="action" value="add">';

    print '<table class="border" width="100%">';

    // Nom
    print '<tr><td>'.$langs->trans("Lastname").'*</td><td><input type="text" name="nom" value="'.$adh->nom.'" size="40"></td>';
    print '</tr>';

	// Prenom
    print '<tr><td>'.$langs->trans("Firstname").'*</td><td><input type="text" name="prenom" size="40" value="'.$adh->prenom.'"></td>';
    print '</tr>';

	// Login
    print '<tr><td>'.$langs->trans("Login").'*</td><td><input type="text" name="member_login" size="40" value="'.$adh->login.'"></td></tr>';

	// Mot de passe
	$generated_password='';
	if ($conf->global->USER_PASSWORD_GENERATED)
	{
		$nomclass="modGeneratePass".ucfirst($conf->global->USER_PASSWORD_GENERATED);
		$nomfichier=$nomclass.".class.php";
		//print DOL_DOCUMENT_ROOT."/includes/modules/security/generate/".$nomclass;
		require_once(DOL_DOCUMENT_ROOT."/includes/modules/security/generate/".$nomfichier);
		$genhandler=new $nomclass($db,$conf,$langs,$user);
		$generated_password=$genhandler->getNewGeneratedPassword();
	}
    print '<tr><td>'.$langs->trans("Password").'*</td><td>';
	print '<input size="30" maxsize="32" type="text" name="password" value="'.$generated_password.'">';
	print '</td></tr>';

	// Type
    print '<tr><td>'.$langs->trans("MemberType").'*</td><td>';
    $listetype=$adht->liste_array();
    if (sizeof($listetype))
    {
        $htmls->select_array("typeid", $listetype, $typeid);
    } else {
        print '<font class="error">'.$langs->trans("NoTypeDefinedGoToSetup").'</font>';
    }
    print "</td>\n";


	// Moral-Physique
    $morphys["phy"] = "Physique";
    $morphys["mor"] = "Morale";
    print "<tr><td>".$langs->trans("Person")."*</td><td>\n";
    $htmls->select_array("morphy", $morphys, $adh->morphy);
    print "</td>\n";

    print '<tr><td>'.$langs->trans("Company").'</td><td><input type="text" name="societe" size="40" value="'.$adh->societe.'"></td></tr>';

    // Adresse
    print '<tr><td valign="top">'.$langs->trans("Address").'</td><td>';
    print '<textarea name="adresse" wrap="soft" cols="40" rows="2">'.$adh->adresse.'</textarea></td></tr>';

    // CP / Ville
    print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td><input type="text" name="cp" size="8"> <input type="text" name="ville" size="32" value="'.$adh->ville.'"></td></tr>';

	// Pays
    print '<tr><td>'.$langs->trans("Country").'</td><td>';
    $htmls->select_pays($adh->pays_id ? $adh->pays_id : $mysoc->pays_id,'pays_id');
    print '</td></tr>';

    // Tel pro
    print '<tr><td>'.$langs->trans("PhonePro").'</td><td><input type="text" name="phone" size="20" value="'.$adh->phone.'"></td></tr>';

    // Tel perso
    print '<tr><td>'.$langs->trans("PhonePerso").'</td><td><input type="text" name="phone_perso" size="20" value="'.$adh->phone_perso.'"></td></tr>';

    // Tel mobile
    print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td><input type="text" name="phone_mobile" size="20" value="'.$adh->phone_mobile.'"></td></tr>';

    // EMail
    print '<tr><td>'.$langs->trans("EMail").($conf->global->ADHERENT_MAIL_REQUIRED?'*':'').'</td><td><input type="text" name="member_email" size="40" value="'.$adh->email.'"></td></tr>';

	// Date naissance
    print "<tr><td>".$langs->trans("Birthday")."</td><td>\n";
    $htmls->select_date(($adh->naiss ? $adh->naiss : -1),'naiss','','',1,'add');
    print "</td></tr>\n";

	// Attribut optionnels
    foreach($adho->attribute_label as $key=>$value)
    {
        print "<tr><td>$value</td><td><input type=\"text\" name=\"options_$key\" size=\"40\"></td></tr>\n";
    }

	// Profil public
    print "<tr><td>".$langs->trans("Public")."</td><td>\n";
    print $htmls->selectyesno("public",$adh->public,1);
    print "</td></tr>\n";


    print "</table>\n";
    print '<br>';

    print '<center><input type="submit" class="button" value="'.$langs->trans("AddMember").'"></center>';

    print "</form>\n";

}

if ($rowid && $action != 'edit')
{
	/* ************************************************************************** */
	/*                                                                            */
	/* Mode affichage                                                             */
	/*                                                                            */
	/* ************************************************************************** */

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
	$head = member_prepare_head($adh);

	dol_fiche_head($head, 'general', $langs->trans("Member"));

	if ($msg) print '<div class="error">'.$msg.'</div>';

	// Confirm create user
	if ($_GET["action"] == 'create_user')
	{
		$login=$adh->login;
		if (empty($login)) $login=strtolower(substr($adh->prenom, 0, 4)) . strtolower(substr($adh->nom, 0, 4));

		// Create a form array
		$formquestion=array(
		array('label' => $langs->trans("LoginToCreate"), 'type' => 'text', 'name' => 'login', 'value' => $login));

		$ret=$html->form_confirm($_SERVER["PHP_SELF"]."?rowid=".$adh->id,$langs->trans("CreateDolibarrLogin"),$langs->trans("ConfirmCreateLogin"),"confirm_create_user",$formquestion);
		if ($ret == 'html') print '<br>';
	}

	// Confirm create third party
	if ($_GET["action"] == 'create_thirdparty')
	{
		$name =$adh->nom;
		if ($adh->nom && $adh->prenom) $name.=' ';
		$name.=$adh->prenom;
		if (! empty($name))
		{
			if ($adh->societe) $name.=' ('.$adh->societe.')';
		}
		else
		{
			$name=$adh->societe;
		}

		// Create a form array
		$formquestion=array(
		array('label' => $langs->trans("NameToCreate"), 'type' => 'text', 'name' => 'companyname', 'value' => $name));

		$ret=$html->form_confirm($_SERVER["PHP_SELF"]."?rowid=".$adh->id,$langs->trans("CreateDolibarrThirdParty"),$langs->trans("ConfirmCreateThirdParty"),"confirm_create_thirdparty",$formquestion);
		if ($ret == 'html') print '<br>';
	}

	// Confirm remove member
    if ($action == 'delete')
    {
        $ret=$html->form_confirm("fiche.php?rowid=$rowid",$langs->trans("DeleteMember"),$langs->trans("ConfirmDeleteMember"),"confirm_delete");
        if ($ret == 'html') print '<br>';
    }

    // Confirm validate member
    if ($action == 'valid')
    {
		// Cree un tableau formulaire
		$formquestion=array();
		if ($adh->email) $formquestion[0]=array('type' => 'checkbox', 'name' => 'send_mail', 'label' => $langs->trans("SendAnEMailToMember",$adh->email),  'value' => 'true');

        $ret=$html->form_confirm("fiche.php?rowid=$rowid",$langs->trans("ValidateMember"),$langs->trans("ConfirmValidateMember"),"confirm_valid",$formquestion);
        if ($ret == 'html') print '<br>';
    }

    // Confirm send card by mail
    if ($action == 'sendinfo')
    {
        $ret=$html->form_confirm("fiche.php?rowid=$rowid",$langs->trans("SendCardByMail"),$langs->trans("ConfirmSendCardByMail"),"confirm_sendinfo");
        if ($ret == 'html') print '<br>';
    }

    // Confirm resiliate
    if ($action == 'resign')
    {
		$langs->load("mails");

    	// Cree un tableau formulaire
		$formquestion=array();
		$label=$langs->trans("SendAnEMailToMember").' ('.$langs->trans("MailFrom").': <b>'.$conf->global->ADHERENT_MAIL_FROM.'</b>, ';
		$label.=$langs->trans("MailRecipient").': <b>'.$adh->email.'</b>';
		$label.=')';
		if ($adh->email) $formquestion[0]=array('type' => 'checkbox', 'name' => 'send_mail', 'label' => $label, 'value' => ($conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL?'true':'false'));

		$ret=$html->form_confirm("fiche.php?rowid=$rowid",$langs->trans("ResiliateMember"),$langs->trans("ConfirmResiliateMember"),"confirm_resign",$formquestion);
        if ($ret == 'html') print '<br>';
    }

    /*
    * Confirm add in spip
    */
    if ($action == 'add_spip')
    {
        $ret=$html->form_confirm("fiche.php?rowid=$rowid","Ajouter dans spip","Etes-vous sur de vouloir ajouter cet adherent dans spip ? (serveur : ".ADHERENT_SPIP_SERVEUR.")","confirm_add_spip");
        if ($ret == 'html') print '<br>';
    }

    /*
    * Confirm removed from spip
    */
    if ($action == 'del_spip')
    {
        $ret=$html->form_confirm("fiche.php?rowid=$rowid","Supprimer dans spip","Etes-vous sur de vouloir effacer cet adherent dans spip ? (serveur : ".ADHERENT_SPIP_SERVEUR.")","confirm_del_spip");
        if ($ret == 'html') print '<br>';
    }


    print '<form action="fiche.php" method="post" enctype="multipart/form-data">';
    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
	print '<td class="valeur" colspan="2">';
	print $html->showrefnav($adh,'rowid');
	print '</td></tr>';

    // Nom
    print '<tr><td>'.$langs->trans("Lastname").'</td><td class="valeur" colspan="2">'.$adh->nom.'&nbsp;</td>';
	print '</tr>';

    // Prenom
    print '<tr><td>'.$langs->trans("Firstname").'</td><td class="valeur" colspan="2">'.$adh->prenom.'&nbsp;</td></tr>';

    // Login
    print '<tr><td>'.$langs->trans("Login").'</td><td class="valeur">'.$adh->login.'&nbsp;</td>';
    $rowspan=16+sizeof($adho->attribute_label);
	print '<td rowspan="'.$rowspan.'" align="center" valign="middle" width="25%">';
    if (file_exists($conf->adherent->dir_output."/".$adh->id.".jpg"))
    {
        print '<img width="100" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=memberphoto&file='.$adh->id.'.jpg">';
    }
    else
    {
        print '<img width="100" src="'.DOL_URL_ROOT.'/theme/common/nophoto.jpg">';
    }
    print '</td>';
	print '</tr>';

	// Password
	print '<tr><td>'.$langs->trans("Password").'</td><td>'.eregi_replace('.','*',$adh->pass).'</td></tr>';

	// Type
	print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">'.$adht->getNomUrl(1)."</td></tr>\n";

    // Morphy
    print '<tr><td>'.$langs->trans("Person").'</td><td class="valeur">'.$adh->getmorphylib().'</td></tr>';

    // Company
    print '<tr><td>'.$langs->trans("Company").'</td><td class="valeur">'.$adh->societe.'</td></tr>';

    // Adresse
    print '<tr><td>'.$langs->trans("Address").'</td><td class="valeur">'.nl2br($adh->adresse).'</td></tr>';

    // CP / Ville
    print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td class="valeur">'.$adh->cp.' '.$adh->ville.'</td></tr>';

    // Pays
    print '<tr><td>'.$langs->trans("Country").'</td><td class="valeur">'.getCountryLabel($adh->pays_id).'</td></tr>';

    // Tel pro.
    print '<tr><td>'.$langs->trans("PhonePro").'</td><td class="valeur">'.dol_print_phone($adh->phone,$adh->pays_code,0,$adh->fk_soc,1).'</td></tr>';

    // Tel perso
    print '<tr><td>'.$langs->trans("PhonePerso").'</td><td class="valeur">'.dol_print_phone($adh->phone_perso,$adh->pays_code,0,$adh->fk_soc,1).'</td></tr>';

    // Tel mobile
    print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td class="valeur">'.dol_print_phone($adh->phone_mobile,$adh->pays_code,0,$adh->fk_soc,1).'</td></tr>';

    // EMail
    print '<tr><td>'.$langs->trans("EMail").'</td><td class="valeur">'.dol_print_email($adh->email,0,$adh->fk_soc,1).'</td></tr>';

	// Date naissance
    print '<tr><td>'.$langs->trans("Birthday").'</td><td class="valeur">'.dol_print_date($adh->naiss,'day').'</td></tr>';

    // Public
    print '<tr><td>'.$langs->trans("Public").'</td><td class="valeur">'.yn($adh->public).'</td></tr>';

    // Status
    print '<tr><td>'.$langs->trans("Status").'</td><td class="valeur">'.$adh->getLibStatut(4).'</td></tr>';

	// Login Dolibarr
	print '<tr><td>'.$langs->trans("DolibarrLogin").'</td><td class="valeur">';
	if ($adh->user_id)
	{
		$dolibarr_user=new User($db);
		$dolibarr_user->id=$adh->user_id;
		$result=$dolibarr_user->fetch();
		print $dolibarr_user->getLoginUrl(1);
	}
	else print $langs->trans("NoDolibarrAccess");
	print '</td></tr>';

	// Third party Dolibarr
    if ($conf->societe->enabled)
    {
	    print '<tr><td>'.$langs->trans("ThirdPartyDolibarr").'</td><td class="valeur">';
	    if ($adh->fk_soc)
	    {
	    	$company=new Societe($db);
	    	$result=$company->fetch($adh->fk_soc);
	    	print $company->getNomUrl(1);
	    }
	    else
	    {
	    	print $langs->trans("NoThirdPartyAssociatedToMember");
	    }
	    print '</td></tr>';
    }

    // Other attributs
    foreach($adho->attribute_label as $key=>$value)
    {
        print "<tr><td>$value</td><td>".$adh->array_options["options_$key"]."&nbsp;</td></tr>\n";
    }

    print "</table>\n";
    print '</form>';

    print "</div>\n";


    /*
     * Barre d'actions
     *
     */
    print '<div class="tabsAction">';

    // Modify
	if ($user->rights->adherent->creer || ($user->rights->adherent->self->creer && $adh->user_id == $user->id))
	{
		print "<a class=\"butAction\" href=\"fiche.php?rowid=$rowid&action=edit\">".$langs->trans("Modify")."</a>";
    }
	else
	{
		print "<font class=\"butActionRefused\" href=\"#\">".$langs->trans("Modify")."</font>";
	}

	// Valider
	if ($adh->statut == -1)
	{
		if ($user->rights->adherent->creer)
		{
			print "<a class=\"butAction\" href=\"fiche.php?rowid=$rowid&action=valid\">".$langs->trans("Validate")."</a>\n";
		}
		else
		{
			print "<font class=\"butActionRefused\" href=\"#\">".$langs->trans("Validate")."</font>";
		}
	}

	// Reactiver
	if ($adh->statut == 0)
	{
		if ($user->rights->adherent->creer)
		{
	        print "<a class=\"butAction\" href=\"fiche.php?rowid=$rowid&action=valid\">".$langs->trans("Reenable")."</a>\n";
	    }
		else
		{
			print "<font class=\"butActionRefused\" href=\"#\">".$langs->trans("Reenable")."</font>";
		}
	}

	// Envoi fiche par mail
	if ($adh->statut >= 1 && $adh->email)
	{
		if ($user->rights->adherent->creer)
		{
	    	print "<a class=\"butAction\" href=\"fiche.php?rowid=$adh->id&action=sendinfo\">".$langs->trans("SendCardByMail")."</a>\n";
	    }
		else
		{
			print "<font class=\"butActionRefused\" href=\"#\">".$langs->trans("SendCardByMail")."</font>";
		}
	}

	// Resilier
	if ($adh->statut >= 1)
	{
		if ($user->rights->adherent->supprimer)
		{
	        print "<a class=\"butAction\" href=\"fiche.php?rowid=$rowid&action=resign\">".$langs->trans("Resiliate")."</a>\n";
	    }
		else
		{
			print "<font class=\"butActionRefused\" href=\"#\">".$langs->trans("Resiliate")."</font>";
		}
	}

	// Create user
	if (! $user->societe_id && ! $adh->user_id)
	{
		if ($user->rights->user->user->creer)
		{
			print '<a class="butAction" href="fiche.php?rowid='.$adh->id.'&amp;action=create_user">'.$langs->trans("CreateDolibarrLogin").'</a>';
		}
		else
		{
			print "<font class=\"butActionRefused\" href=\"#\">".$langs->trans("CreateDolibarrLogin")."</font>";
		}
	}

	// Create third party
	if ($conf->societe->enabled && ! $adh->fk_soc)
	{
		if ($user->rights->societe->creer)
		{
			print '<a class="butAction" href="fiche.php?rowid='.$adh->id.'&amp;action=create_thirdparty">'.$langs->trans("CreateDolibarrThirdParty").'</a>';
		}
		else
		{
			print "<font class=\"butActionRefused\" href=\"#\">".$langs->trans("CreateDolibarrThirdParty")."</font>";
		}
	}

	// Supprimer
    if ($user->rights->adherent->supprimer)
    {
        print "<a class=\"butActionDelete\" href=\"fiche.php?rowid=$adh->id&action=delete\">".$langs->trans("Delete")."</a>\n";
    }
	else
	{
		print "<font class=\"butActionRefused\" href=\"#\">".$langs->trans("Delete")."</font>";
	}

    // Action SPIP
    if ($conf->global->ADHERENT_USE_SPIP)
    {
        $isinspip=$adh->is_in_spip();
        if ($isinspip == 1)
        {
            print "<a class=\"butAction\" href=\"fiche.php?rowid=$adh->id&action=del_spip\">Suppression dans Spip</a>\n";
        }
        if ($isinspip == 0)
        {
            print "<a class=\"butAction\" href=\"fiche.php?rowid=$adh->id&action=add_spip\">Ajout dans Spip</a>\n";
        }
        if ($isinspip == -1) {
            print '<br><font class="error">Failed to connect to SPIP: '.$adh->error.'</font>';
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
    print '<td valign="top" width="50%">';

    print '</td><td valign="top">';

    print '</td></tr>';
    print '</table>';

}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
