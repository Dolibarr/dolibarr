<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2012-2013 Philippe Grand       <philippe.grand@atoo-net.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/adherents/fiche.php
 *       \ingroup    member
 *       \brief      Page of member
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/cotisation.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$langs->load("companies");
$langs->load("bills");
$langs->load("members");
$langs->load("users");
$langs->load('other');

$action=GETPOST('action','alpha');
$backtopage=GETPOST('backtopage','alpha');
$confirm=GETPOST('confirm','alpha');
$rowid=GETPOST('rowid','int');
$typeid=GETPOST('typeid','int');
$userid=GETPOST('userid','int');
$socid=GETPOST('socid','int');

if (! empty($conf->mailmanspip->enabled))
{
	include_once DOL_DOCUMENT_ROOT.'/mailmanspip/class/mailmanspip.class.php';

	$langs->load('mailmanspip');

	$mailmanspip = new MailmanSpip($db);
}

$object = new Adherent($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$object->getCanvas($rowid);
$canvas = $object->canvas?$object->canvas:GETPOST("canvas");
$objcanvas='';
if (! empty($canvas))
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('adherent', 'membercard', $canvas);
}

// Security check
$result=restrictedArea($user,'adherent',$rowid,'','','fk_soc', 'rowid', $objcanvas);

$errmsg=''; $errmsgs=array();

if ($rowid > 0)
{
	// Load member
	$result = $object->fetch($rowid);

	// Define variables to know what current user can do on users
	$canadduser=($user->admin || $user->rights->user->user->creer);
	// Define variables to know what current user can do on properties of user linked to edited member
	if ($object->user_id)
	{
		// $user est le user qui edite, $object->user_id est l'id de l'utilisateur lies au membre edite
		$caneditfielduser=((($user->id == $object->user_id) && $user->rights->user->self->creer)
				|| (($user->id != $object->user_id) && $user->rights->user->user->creer));
		$caneditpassworduser=((($user->id == $object->user_id) && $user->rights->user->self->password)
				|| (($user->id != $object->user_id) && $user->rights->user->user->password));
	}
}

// Define variables to determine what the current user can do on the members
$canaddmember=$user->rights->adherent->creer;
// Define variables to determine what the current user can do on the properties of a member
if ($rowid)
{
	$caneditfieldmember=$user->rights->adherent->creer;
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('membercard'));


/*
 * 	Actions
*/

$parameters=array('rowid'=>$rowid, 'objcanvas'=>$objcanvas);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

if ($action == 'setuserid' && ($user->rights->user->self->creer || $user->rights->user->user->creer))
{
	$error=0;
	if (empty($user->rights->user->user->creer))	// If can edit only itself user, we can link to itself only
	{
		if ($userid != $user->id && $userid != $object->user_id)
		{
			$error++;
			$mesg='<div class="error">'.$langs->trans("ErrorUserPermissionAllowsToLinksToItselfOnly").'</div>';
		}
	}

	if (! $error)
	{
		if ($userid != $object->user_id)	// If link differs from currently in database
		{
			$result=$object->setUserId($userid);
			if ($result < 0) dol_print_error($object->db,$object->error);
			$action='';
		}
	}
}

if ($action == 'setsocid')
{
	$error=0;
	if (! $error)
	{
		if ($socid != $object->fk_soc)	// If link differs from currently in database
		{
			$sql ="SELECT rowid FROM ".MAIN_DB_PREFIX."adherent";
			$sql.=" WHERE fk_soc = '".$socid."'";
			$sql.=" AND entity = ".$conf->entity;
			$resql = $db->query($sql);
			if ($resql)
			{
				$obj = $db->fetch_object($resql);
				if ($obj && $obj->rowid > 0)
				{
					$othermember=new Adherent($db);
					$othermember->fetch($obj->rowid);
					$thirdparty=new Societe($db);
					$thirdparty->fetch($socid);
					$error++;
					$errmsg='<div class="error">'.$langs->trans("ErrorMemberIsAlreadyLinkedToThisThirdParty",$othermember->getFullName($langs),$othermember->login,$thirdparty->name).'</div>';
				}
			}

			if (! $error)
			{
				$result=$object->setThirdPartyId($socid);
				if ($result < 0) dol_print_error($object->db,$object->error);
				$action='';
			}
		}
	}
}

// Create user from a member
if ($action == 'confirm_create_user' && $confirm == 'yes' && $user->rights->user->user->creer)
{
	if ($result > 0)
	{
		// Creation user
		$nuser = new User($db);
		$result=$nuser->create_from_member($object,GETPOST('login'));

		if ($result < 0)
		{
			$langs->load("errors");
			$errmsg=$langs->trans($nuser->error);
		}
	}
	else
	{
		$errmsg=$object->error;
	}
}

// Create third party from a member
if ($action == 'confirm_create_thirdparty' && $confirm == 'yes' && $user->rights->societe->creer)
{
	if ($result > 0)
	{
		// Creation user
		$company = new Societe($db);
		$result=$company->create_from_member($object,GETPOST('companyname'));

		if ($result < 0)
		{
			$langs->load("errors");
			$errmsg=$langs->trans($company->error);
			$errmsgs=$company->errors;
		}
	}
	else
	{
		$errmsg=$object->error;
	}
}

if ($action == 'confirm_sendinfo' && $confirm == 'yes')
{
	if ($object->email)
	{
		$from=$conf->email_from;
		if (! empty($conf->global->ADHERENT_MAIL_FROM)) $from=$conf->global->ADHERENT_MAIL_FROM;

		$result=$object->send_an_email($langs->transnoentitiesnoconv("ThisIsContentOfYourCard")."\n\n%INFOS%\n\n",$langs->transnoentitiesnoconv("CardContent"));

		$langs->load("mails");
		$mesg=$langs->trans("MailSuccessfulySent", $from, $object->email);
	}
}

if ($action == 'update' && ! $_POST["cancel"] && $user->rights->adherent->creer)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$birthdate='';
	if (isset($_POST["birthday"]) && $_POST["birthday"]
			&& isset($_POST["birthmonth"]) && $_POST["birthmonth"]
			&& isset($_POST["birthyear"]) && $_POST["birthyear"])
	{
		$birthdate=dol_mktime(12, 0, 0, $_POST["birthmonth"], $_POST["birthday"], $_POST["birthyear"]);
	}
	$lastname=$_POST["lastname"];
	$firstname=$_POST["firstname"];
	$morphy=$morphy=$_POST["morphy"];;
	if ($morphy != 'mor' && empty($lastname)) {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorFieldRequired",$langs->transnoentities("Lastname"))."<br>\n";
	}
	if ($morphy != 'mor' && (!isset($firstname) || $firstname=='')) {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorFieldRequired",$langs->transnoentities("Firstname"))."<br>\n";
	}

	// Create new object
	if ($result > 0 && ! $error)
	{
		$object->oldcopy=dol_clone($object);

		// Change values
		$object->civilite_id = trim($_POST["civilite_id"]);
		$object->firstname   = trim($_POST["firstname"]);
		$object->lastname    = trim($_POST["lastname"]);
		$object->login       = trim($_POST["login"]);
		$object->pass        = trim($_POST["pass"]);

		$object->societe     = trim($_POST["societe"]);
		$object->address     = trim($_POST["address"]);
		$object->zip         = trim($_POST["zipcode"]);
		$object->town        = trim($_POST["town"]);
		$object->state_id    = $_POST["state_id"];
		$object->country_id  = $_POST["country_id"];

		$object->phone       = trim($_POST["phone"]);
		$object->phone_perso = trim($_POST["phone_perso"]);
		$object->phone_mobile= trim($_POST["phone_mobile"]);
		$object->email       = trim($_POST["email"]);
		$object->birth       = $birthdate;

		$object->typeid      = $_POST["typeid"];
		//$object->note        = trim($_POST["comment"]);
		$object->morphy      = $_POST["morphy"];

		$object->amount      = $_POST["amount"];

		if (GETPOST('deletephoto')) $object->photo='';
		elseif (! empty($_FILES['photo']['name'])) $object->photo  = dol_sanitizeFileName($_FILES['photo']['name']);

		// Get status and public property
		$object->statut      = $_POST["statut"];
		$object->public      = $_POST["public"];

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost($extralabels,$object);

		// Check if we need to also synchronize user information
		$nosyncuser=0;
		if ($object->user_id)	// If linked to a user
		{
			if ($user->id != $object->user_id && empty($user->rights->user->user->creer)) $nosyncuser=1;		// Disable synchronizing
		}

		// Check if we need to also synchronize password information
		$nosyncuserpass=0;
		if ($object->user_id)	// If linked to a user
		{
			if ($user->id != $object->user_id && empty($user->rights->user->user->password)) $nosyncuserpass=1;	// Disable synchronizing
		}

		$result=$object->update($user,0,$nosyncuser,$nosyncuserpass);
		if ($result >= 0 && ! count($object->errors))
		{
			$dir= $conf->adherent->dir_output . '/' . get_exdir($object->id,2,0,1).'/photos';
			$file_OK = is_uploaded_file($_FILES['photo']['tmp_name']);
			if ($file_OK)
			{
				if (GETPOST('deletephoto'))
				{
					$fileimg=$conf->adherent->dir_output.'/'.get_exdir($object->id,2,0,1).'/photos/'.$object->photo;
					$dirthumbs=$conf->adherent->dir_output.'/'.get_exdir($object->id,2,0,1).'/photos/thumbs';
					dol_delete_file($fileimg);
					dol_delete_dir_recursive($dirthumbs);
				}

				if (image_format_supported($_FILES['photo']['name']) > 0)
				{
					dol_mkdir($dir);

					if (@is_dir($dir))
					{
						$newfile=$dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
						if (! dol_move_uploaded_file($_FILES['photo']['tmp_name'],$newfile,1,0,$_FILES['photo']['error']) > 0)
						{
							$message .= '<div class="error">'.$langs->trans("ErrorFailedToSaveFile").'</div>';
						}
						else
						{
							// Create small thumbs for company (Ratio is near 16/9)
							// Used on logon for example
							$imgThumbSmall = vignette($newfile, $maxwidthsmall, $maxheightsmall, '_small', $quality);

							// Create mini thumbs for company (Ratio is near 16/9)
							// Used on menu or for setup page for example
							$imgThumbMini = vignette($newfile, $maxwidthmini, $maxheightmini, '_mini', $quality);
						}
					}
				}
				else
				{
					$errmsgs[] = "ErrorBadImageFormat";
				}
			}

			// Rajoute l'utilisateur dans les divers abonnements (mailman, spip, etc...)
			if (($object->oldcopy->email != $object->email) || ($object->oldcopy->typeid != $object->typeid))
			{
				if ($object->oldcopy->email != $object->email)    // If email has changed we delete mailman subscription for old email
				{
					if ($object->oldcopy->del_to_abo() < 0)
					{
						if (! empty($object->oldcopy->error)) setEventMessage($langs->trans("ErrorFailedToRemoveToMailmanList").': '.$object->oldcopy->error, 'errors');
						setEventMessage($object->oldcopy->errors, 'errors');
					}
					else
					{
						setEventMessage($object->oldcopy->mesgs,'mesgs');
					}
				}
    			// We add subscription if new email or new type (new type may means more mailing-list to subscribe)
    			if ($object->add_to_abo() < 0)
    			{
    				 if (! empty($object->error)) setEventMessage($langs->trans("ErrorFailedToAddToMailmanList").': '.$object->error, 'errors');
    				 setEventMessage($object->errors, 'errors');
    			}
				else
				{
					setEventMessage($object->mesgs, 'mesgs');
				}
			}

			$rowid=$object->id;
			$action='';

			if (! empty($backtopage))
			{
				header("Location: ".$backtopage);
				exit;
			}
		}
		else
		{
			if ($object->error) $errmsg=$object->error;
			else $errmsgs=$object->errors;
			$action='';
		}
	}
	else
	{
		$action='edit';
	}
}

if ($action == 'add' && $user->rights->adherent->creer)
{
	if ($canvas) $object->canvas=$canvas;
	$birthdate='';
	if (isset($_POST["birthday"]) && $_POST["birthday"]
			&& isset($_POST["birthmonth"]) && $_POST["birthmonth"]
			&& isset($_POST["birthyear"]) && $_POST["birthyear"])
	{
		$birthdate=dol_mktime(12, 0, 0, $_POST["birthmonth"], $_POST["birthday"], $_POST["birthyear"]);
	}
	$datecotisation='';
	if (isset($_POST["reday"]) && isset($_POST["remonth"]) && isset($_POST["reyear"]))
	{
		$datecotisation=dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
	}

	$typeid=$_POST["typeid"];
	$civilite_id=$_POST["civilite_id"];
	$lastname=$_POST["lastname"];
	$firstname=$_POST["firstname"];
	$societe=$_POST["societe"];
	$address=$_POST["address"];
	$zip=$_POST["zipcode"];
	$town=$_POST["town"];
	$state_id=$_POST["state_id"];
	$country_id=$_POST["country_id"];

	$phone=$_POST["phone"];
	$phone_perso=$_POST["phone_perso"];
	$phone_mobile=$_POST["phone_mobile"];
	$email=$_POST["member_email"];
	$login=$_POST["member_login"];
	$pass=$_POST["password"];
	$photo=$_POST["photo"];
	//$comment=$_POST["comment"];
	$morphy=$_POST["morphy"];
	$cotisation=$_POST["cotisation"];
	$public=$_POST["public"];

	$userid=$_POST["userid"];
	$socid=$_POST["socid"];

	$object->civilite_id = $civilite_id;
	$object->firstname   = $firstname;
	$object->lastname    = $lastname;
	$object->societe     = $societe;
	$object->address     = $address;
	$object->zip         = $zip;
	$object->town        = $town;
	$object->state_id    = $state_id;
	$object->country_id  = $country_id;
	$object->phone       = $phone;
	$object->phone_perso = $phone_perso;
	$object->phone_mobile= $phone_mobile;
	$object->email       = $email;
	$object->login       = $login;
	$object->pass        = $pass;
	$object->naiss       = $birthdate;
	$object->photo       = $photo;
	$object->typeid      = $typeid;
	//$object->note        = $comment;
	$object->morphy      = $morphy;
	$object->user_id     = $userid;
	$object->fk_soc      = $socid;
	$object->public      = $public;

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost($extralabels,$object);

	// Check parameters
	if (empty($morphy) || $morphy == "-1") {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Nature"))."<br>\n";
	}
	// Test si le login existe deja
	if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
	{
		if (empty($login)) {
			$error++;
			$errmsg .= $langs->trans("ErrorFieldRequired",$langs->trans("Login"))."<br>\n";
		}
		else {
			$sql = "SELECT login FROM ".MAIN_DB_PREFIX."adherent WHERE login='".$db->escape($login)."'";
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
		if (empty($pass)) {
			$error++;
			$errmsg .= $langs->trans("ErrorFieldRequired",$langs->transnoentities("Password"))."<br>\n";
		}
	}
	if ($morphy != 'mor' && empty($lastname)) {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorFieldRequired",$langs->transnoentities("Lastname"))."<br>\n";
	}
	if ($morphy != 'mor' && (!isset($firstname) || $firstname=='')) {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorFieldRequired",$langs->transnoentities("Firstname"))."<br>\n";
	}
	if (! ($typeid > 0)) {	// Keep () before !
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type"))."<br>\n";
	}
	if ($conf->global->ADHERENT_MAIL_REQUIRED && ! isValidEMail($email)) {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorBadEMail",$email)."<br>\n";
	}
	$public=0;
	if (isset($public)) $public=1;

	if (! $error)
	{
		$db->begin();

		// Email a peu pres correct et le login n'existe pas
		$result=$object->create($user);
		if ($result > 0)
		{
			$db->commit();
			$rowid=$object->id;
			$action='';
		}
		else
		{
			$db->rollback();

			if ($object->error) $errmsg=$object->error;
			else $errmsgs=$object->errors;

			$action = 'create';
		}
	}
	else {
		$action = 'create';
	}
}

if ($user->rights->adherent->supprimer && $action == 'confirm_delete' && $confirm == 'yes')
{
	$result=$object->delete($rowid);
	if ($result > 0)
	{
		if (! empty($backtopage))
		{
			header("Location: ".$backtopage);
			exit;
		}
		else
		{
			header("Location: liste.php");
			exit;
		}
	}
	else
	{
		$errmesg=$object->error;
	}
}

if ($user->rights->adherent->creer && $action == 'confirm_valid' && $confirm == 'yes')
{
	$error=0;

	$db->begin();

	$adht = new AdherentType($db);
	$adht->fetch($object->typeid);

	$result=$object->validate($user);

	if ($result >= 0 && ! count($object->errors))
	{
		// Send confirmation Email (selon param du type adherent sinon generique)
		if ($object->email && ! empty($_POST["send_mail"]))
		{
			$result=$object->send_an_email($adht->getMailOnValid(),$conf->global->ADHERENT_MAIL_VALID_SUBJECT,array(),array(),array(),"","",0,2);
			if ($result < 0)
			{
				$error++;
				$errmsg.=$object->error;
			}
		}

		// Add user to other systems (mailman, spip, etc...)
		// TODO Move this into trigger on validate action
		if (! $error && $object->add_to_abo() < 0)
		{
			$langs->load("errors");
			$error++;
			$errmsg.= $langs->trans("ErrorFailedToAddToMailmanList").': '.$object->error." ".join(',',$object->errors)."<br>\n";
		}
	}
	else
	{
		$error++;
		if ($object->error) $errmsg=$object->error;
		else $errmsgs=$object->errors;
	}

	if (! $error)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
	}
	$action='';
}

if ($user->rights->adherent->supprimer && $action == 'confirm_resign')
{
	if ($confirm == 'yes')
	{
		$adht = new AdherentType($db);
		$adht->fetch($object->typeid);

		$result=$object->resiliate($user);

		if ($result >= 0 && ! count($object->errors))
		{
			if ($object->email && $_POST["send_mail"])
			{
				$result=$object->send_an_email($adht->getMailOnResiliate(),$conf->global->ADHERENT_MAIL_RESIL_SUBJECT,array(),array(),array(),"","",0,-1);
			}
			if ($result < 0)
			{
				$errmsg.=$object->error;
			}

			// supprime l'utilisateur des divers abonnements ..
			if ($object->del_to_abo() < 0)
			{
				// error
				$errmsg.=$langs->trans("FaildToRemoveFromMailmanList").': '.$object->error."<br>\n";
			}
		}
		else
		{
			if ($object->error) $errmsg=$object->error;
			else $errmsgs=$object->errors;
			$action='';
		}
	}
	if (! empty($backtopage) && ! $errmsg)
	{
		header("Location: ".$backtopage);
		exit;
	}
}

// SPIP Management
if ($user->rights->adherent->supprimer && $action == 'confirm_del_spip' && $confirm == 'yes')
{
	if (! count($object->errors))
	{
		if (!$mailmanspip->del_to_spip($object))
		{
			$errmsg.= $langs->trans('DeleteIntoSpipError').': '.$mailmanspip->error."<BR>\n";
		}
	}
}

if ($user->rights->adherent->creer && $action == 'confirm_add_spip' && $confirm == 'yes')
{
	if (! count($object->errors))
	{
		if (!$mailmanspip->add_to_spip($object))
		{
			$errmsg.= $langs->trans('AddIntoSpipError').': '.$mailmanspip->error."<BR>\n";
		}
	}
}



/*
 * View
*/

$form = new Form($db);
$formcompany = new FormCompany($db);

$help_url='EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros';
llxHeader('',$langs->trans("Member"),$help_url);

$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action))
{
	// -----------------------------------------
	// When used with CANVAS
	// -----------------------------------------
	if (empty($object->error) && $rowid)
	{
		$object = new Adherent($db);
		$result=$object->fetch($rowid);
		if ($result <= 0) dol_print_error('',$object->error);
	}
   	$objcanvas->assign_values($action, $object->id, $object->ref);	// Set value for templates
    $objcanvas->display_canvas($action);							// Show template
}
else
{
	// -----------------------------------------
	// When used in standard mode
	// -----------------------------------------

	if ($action == 'create')
	{
		/* ************************************************************************** */
		/*                                                                            */
		/* Fiche creation                                                             */
		/*                                                                            */
		/* ************************************************************************** */
		$object->canvas=$canvas;
		$object->state_id = GETPOST('departement_id', 'int');

		// We set country_id, country_code and country for the selected country
		$object->country_id=GETPOST('country_id','int')?GETPOST('country_id','int'):$mysoc->country_id;
		if ($object->country_id)
		{
			$tmparray=getCountry($object->country_id,'all');
			$object->country_code=$tmparray['code'];
			$object->country=$tmparray['label'];
		}

		$adht = new AdherentType($db);

		print_fiche_titre($langs->trans("NewMember"));

		dol_htmloutput_mesg($errmsg,$errmsgs,'error');
		dol_htmloutput_mesg($mesg,$mesgs);

		if ($conf->use_javascript_ajax)
		{
			print "\n".'<script type="text/javascript" language="javascript">';
			print 'jQuery(document).ready(function () {
						jQuery("#selectcountry_id").change(function() {
							document.formsoc.action.value="create";
							document.formsoc.submit();
						});
						function initfieldrequired()
						{
							jQuery("#tdcompany").removeClass("fieldrequired");
							jQuery("#tdlastname").removeClass("fieldrequired");
							jQuery("#tdfirstname").removeClass("fieldrequired");
							if (jQuery("#morphy").val() == \'mor\')
							{
								jQuery("#tdcompany").addClass("fieldrequired");
							}
							if (jQuery("#morphy").val() == \'phy\')
							{
								jQuery("#tdlastname").addClass("fieldrequired");
								jQuery("#tdfirstname").addClass("fieldrequired");
							}
						}
						jQuery("#morphy").change(function() {
							initfieldrequired();
						});
						initfieldrequired();
					})';
			print '</script>'."\n";
		}

		print '<form name="formsoc" action="'.$_SERVER["PHP_SELF"].'" method="post" enctype="multipart/form-data">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="add">';

		print '<table class="border" width="100%">';

		// Login
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
		{
			print '<tr><td><span class="fieldrequired">'.$langs->trans("Login").' / '.$langs->trans("Id").'</span></td><td><input type="text" name="member_login" size="40" value="'.(isset($_POST["member_login"])?$_POST["member_login"]:$object->login).'"></td></tr>';
		}

		// Moral-Physique
		$morphys["phy"] = $langs->trans("Physical");
		$morphys["mor"] = $langs->trans("Moral");
		print '<tr><td class="fieldrequired">'.$langs->trans("Nature")."</td><td>\n";
		print $form->selectarray("morphy", $morphys, GETPOST('morphy','alpha')?GETPOST('morphy','alpha'):$object->morphy, 1);
		print "</td>\n";

		// Type
		print '<tr><td class="fieldrequired">'.$langs->trans("MemberType").'</td><td>';
		$listetype=$adht->liste_array();
		if (count($listetype))
		{
			print $form->selectarray("typeid", $listetype, GETPOST('typeid','int')?GETPOST('typeid','int'):$typeid, count($listetype)>1?1:0);
		} else {
			print '<font class="error">'.$langs->trans("NoTypeDefinedGoToSetup").'</font>';
		}
		print "</td>\n";

		// Company
		print '<tr><td id="tdcompany">'.$langs->trans("Company").'</td><td><input type="text" name="societe" size="40" value="'.(GETPOST('societe','alpha')?GETPOST('societe','alpha'):$object->societe).'"></td></tr>';

		// Civility
		print '<tr><td>'.$langs->trans("UserTitle").'</td><td>';
		print $formcompany->select_civility(GETPOST('civilite_id','int')?GETPOST('civilite_id','int'):$object->civilite_id,'civilite_id').'</td>';
		print '</tr>';

		// Lastname
		print '<tr><td id="tdlastname">'.$langs->trans("Lastname").'</td><td><input type="text" name="lastname" value="'.(GETPOST('lastname','alpha')?GETPOST('lastname','alpha'):$object->lastname).'" size="40"></td>';
		print '</tr>';

		// Firstname
		print '<tr><td id="tdfirstname">'.$langs->trans("Firstname").'</td><td><input type="text" name="firstname" size="40" value="'.(GETPOST('firstname','alpha')?GETPOST('firstname','alpha'):$object->firstname).'"></td>';
		print '</tr>';

		// EMail
		print '<tr><td>'.($conf->global->ADHERENT_MAIL_REQUIRED?'<span class="fieldrequired">':'').$langs->trans("EMail").($conf->global->ADHERENT_MAIL_REQUIRED?'</span>':'').'</td><td><input type="text" name="member_email" size="40" value="'.(GETPOST('member_email','alpha')?GETPOST('member_email','alpha'):$object->email).'"></td></tr>';

		// Password
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			$generated_password=getRandomPassword('');
			print '<tr><td><span class="fieldrequired">'.$langs->trans("Password").'</span></td><td>';
			print '<input size="30" maxsize="32" type="text" name="password" value="'.$generated_password.'">';
			print '</td></tr>';
		}

		// Address
		print '<tr><td valign="top">'.$langs->trans("Address").'</td><td>';
		print '<textarea name="address" wrap="soft" cols="40" rows="2">'.(GETPOST('address','alpha')?GETPOST('address','alpha'):$object->address).'</textarea>';
		print '</td></tr>';

		// Zip / Town
		print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>';
		print $formcompany->select_ziptown((GETPOST('zipcode','alpha')?GETPOST('zipcode','alpha'):$object->zip),'zipcode',array('town','selectcountry_id','state_id'),6);
		print ' ';
		print $formcompany->select_ziptown((GETPOST('town','alpha')?GETPOST('town','alpha'):$object->town),'town',array('zipcode','selectcountry_id','state_id'));
		print '</td></tr>';

		// Country
		$object->country_id=$object->country_id?$object->country_id:$mysoc->country_id;
		print '<tr><td width="25%">'.$langs->trans('Country').'</td><td>';
		print $form->select_country(GETPOST('country_id','alpha')?GETPOST('country_id','alpha'):$object->country_id,'country_id');
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
		print '</td></tr>';

		// State
		if (empty($conf->global->MEMBER_DISABLE_STATE))
		{
			print '<tr><td>'.$langs->trans('State').'</td><td>';
			if ($object->country_id)
			{
				print $formcompany->select_state(GETPOST('state_id','int')?GETPOST('state_id','int'):$object->state_id,$object->country_code);
			}
			else
			{
				print $countrynotdefined;
			}
			print '</td></tr>';
		}

		// Tel pro
		print '<tr><td>'.$langs->trans("PhonePro").'</td><td><input type="text" name="phone" size="20" value="'.(GETPOST('phone','alpha')?GETPOST('phone','alpha'):$object->phone).'"></td></tr>';

		// Tel perso
		print '<tr><td>'.$langs->trans("PhonePerso").'</td><td><input type="text" name="phone_perso" size="20" value="'.(GETPOST('phone_perso','alpha')?GETPOST('phone_perso','alpha'):$object->phone_perso).'"></td></tr>';

		// Tel mobile
		print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td><input type="text" name="phone_mobile" size="20" value="'.(GETPOST('phone_mobile','alpha')?GETPOST('phone_mobile','alpha'):$object->phone_mobile).'"></td></tr>';

		// Birthday
		print "<tr><td>".$langs->trans("Birthday")."</td><td>\n";
		$form->select_date(($object->naiss ? $object->naiss : -1),'naiss','','',1,'formsoc');
		print "</td></tr>\n";

		// Profil public
		print "<tr><td>".$langs->trans("Public")."</td><td>\n";
		print $form->selectyesno("public",$object->public,1);
		print "</td></tr>\n";

		// Other attributes
		$parameters=array();
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
			print $object->showOptionals($extrafields,'edit');
		}

		/*
		 // Third party Dolibarr
		if (! empty($conf->societe->enabled))
		{
		print '<tr><td>'.$langs->trans("LinkedToDolibarrThirdParty").'</td><td class="valeur">';
		print $form->select_company($object->fk_soc,'socid','',1);
		print '</td></tr>';
		}

		// Login Dolibarr
		print '<tr><td>'.$langs->trans("LinkedToDolibarrUser").'</td><td class="valeur">';
		print $form->select_users($object->user_id,'userid',1);
		print '</td></tr>';
		*/

		print "</table>\n";
		print '<br>';

		print '<center><input type="submit" class="button" value="'.$langs->trans("AddMember").'"></center>';

		print "</form>\n";

	}

	if ($action == 'edit')
	{
		/********************************************
		 *
		* Fiche en mode edition
		*
		********************************************/

		$res=$object->fetch($rowid);
		if ($res < 0) {
			dol_print_error($db,$object->error); exit;
		}
		$res=$object->fetch_optionals($object->id,$extralabels);
		if ($res < 0) {
			dol_print_error($db); exit;
		}

		$adht = new AdherentType($db);
		$adht->fetch($object->typeid);

		// We set country_id, and country_code, country of the chosen country
		$country=GETPOST('country','int');
		if (!empty($country) || $object->country_id)
		{
			$sql = "SELECT rowid, code, libelle as label from ".MAIN_DB_PREFIX."c_pays where rowid = ".(!empty($country)?$country:$object->country_id);
			$resql=$db->query($sql);
			if ($resql)
			{
				$obj = $db->fetch_object($resql);
			}
			else
			{
				dol_print_error($db);
			}
			$object->country_id=$obj->rowid;
			$object->country_code=$obj->code;
			$object->country=$langs->trans("Country".$obj->code)?$langs->trans("Country".$obj->code):$obj->label;
		}

		$head = member_prepare_head($object);

		dol_fiche_head($head, 'general', $langs->trans("Member"), 0, 'user');

		dol_htmloutput_errors($errmsg,$errmsgs);
		dol_htmloutput_mesg($mesg);

		if ($conf->use_javascript_ajax)
		{
			print "\n".'<script type="text/javascript" language="javascript">';
			print 'jQuery(document).ready(function () {
				jQuery("#selectcountry_id").change(function() {
					document.formsoc.action.value="edit";
					document.formsoc.submit();
				});
				function initfieldrequired()
				{
					jQuery("#tdcompany").removeClass("fieldrequired");
					jQuery("#tdlastname").removeClass("fieldrequired");
					jQuery("#tdfirstname").removeClass("fieldrequired");
					if (jQuery("#morphy").val() == \'mor\')
					{
						jQuery("#tdcompany").addClass("fieldrequired");
					}
					if (jQuery("#morphy").val() == \'phy\')
					{
						jQuery("#tdlastname").addClass("fieldrequired");
						jQuery("#tdfirstname").addClass("fieldrequired");
					}
				}
				jQuery("#morphy").change(function() {
					initfieldrequired();
				});
				initfieldrequired();
			})';
			print '</script>'."\n";
		}

		$rowspan=15;
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) $rowspan++;
		if (! empty($conf->societe->enabled)) $rowspan++;

		print '<form name="formsoc" action="'.$_SERVER["PHP_SELF"].'" method="post" enctype="multipart/form-data">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
		print '<input type="hidden" name="action" value="update" />';
		print '<input type="hidden" name="rowid" value="'.$rowid.'" />';
		print '<input type="hidden" name="statut" value="'.$object->statut.'" />';
		if ($backtopage) print '<input type="hidden" name="backtopage" value="'.($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]).'">';

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td>'.$langs->trans("Ref").'</td><td class="valeur" colspan="2">'.$object->id.'</td></tr>';

		// Login
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
		{
			print '<tr><td><span class="fieldrequired">'.$langs->trans("Login").' / '.$langs->trans("Id").'</span></td><td colspan="2"><input type="text" name="login" size="30" value="'.(isset($_POST["login"])?$_POST["login"]:$object->login).'"></td></tr>';
		}

		// Physique-Moral
		$morphys["phy"] = $langs->trans("Physical");
		$morphys["mor"] = $langs->trans("Morale");
		print '<tr><td><span class="fieldrequired">'.$langs->trans("Nature").'</span></td><td>';
		print $form->selectarray("morphy", $morphys, isset($_POST["morphy"])?$_POST["morphy"]:$object->morphy);
		print "</td>";
		// Photo
		print '<td align="center" class="hideonsmartphone" valign="middle" width="25%" rowspan="'.$rowspan.'">';
		print $form->showphoto('memberphoto',$object)."\n";
		if ($caneditfieldmember)
		{
			if ($object->photo) print "<br>\n";
			print '<table class="nobordernopadding">';
			if ($object->photo) print '<tr><td align="center"><input type="checkbox" class="flat" name="deletephoto" id="photodelete"> '.$langs->trans("Delete").'<br><br></td></tr>';
			print '<tr><td>'.$langs->trans("PhotoFile").'</td></tr>';
			print '<tr><td><input type="file" class="flat" name="photo" id="photoinput"></td></tr>';
			print '</table>';
		}
		print '</td>';

		// Type
		print '<tr><td class="fieldrequired">'.$langs->trans("Type").'</td><td>';
		if ($user->rights->adherent->creer)
		{
			print $form->selectarray("typeid", $adht->liste_array(), (isset($_POST["typeid"])?$_POST["typeid"]:$object->typeid));
		}
		else
		{
			print $adht->getNomUrl(1);
			print '<input type="hidden" name="typeid" value="'.$object->typeid.'">';
		}
		print "</td></tr>";

		// Company
		print '<tr><td id="tdcompany">'.$langs->trans("Company").'</td><td><input type="text" name="societe" size="40" value="'.(isset($_POST["societe"])?$_POST["societe"]:$object->societe).'"></td></tr>';

		// Civilite
		print '<tr><td width="20%">'.$langs->trans("UserTitle").'</td><td width="35%">';
		print $formcompany->select_civility(isset($_POST["civilite_id"])?$_POST["civilite_id"]:$object->civilite_id)."\n";
		print '</td>';
		print '</tr>';

		// Lastname
		print '<tr><td id="tdlastname">'.$langs->trans("Lastname").'</td><td><input type="text" name="lastname" size="40" value="'.(isset($_POST["lastname"])?$_POST["lastname"]:$object->lastname).'"></td>';
		print '</tr>';

		// Firstname
		print '<tr><td id="tdfirstname">'.$langs->trans("Firstname").'</td><td><input type="text" name="firstname" size="40" value="'.(isset($_POST["firstname"])?$_POST["firstname"]:$object->firstname).'"></td>';
		print '</tr>';

		// EMail
		print '<tr><td>'.($conf->global->ADHERENT_MAIL_REQUIRED?'<span class="fieldrequired">':'').$langs->trans("EMail").($conf->global->ADHERENT_MAIL_REQUIRED?'</span>':'').'</td><td><input type="text" name="email" size="40" value="'.(isset($_POST["email"])?$_POST["email"]:$object->email).'"></td></tr>';

		// Password
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
		{
			print '<tr><td class="fieldrequired">'.$langs->trans("Password").'</td><td><input type="password" name="pass" size="30" value="'.(isset($_POST["pass"])?$_POST["pass"]:$object->pass).'"></td></tr>';
		}

		// Address
		print '<tr><td>'.$langs->trans("Address").'</td><td>';
		print '<textarea name="address" wrap="soft" cols="40" rows="2">'.(isset($_POST["address"])?$_POST["address"]:$object->address).'</textarea>';
		print '</td></tr>';

		// Zip / Town
		print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td>';
		print $formcompany->select_ziptown((isset($_POST["zipcode"])?$_POST["zipcode"]:$object->zip),'zipcode',array('town','selectcountry_id','state_id'),6);
		print ' ';
		print $formcompany->select_ziptown((isset($_POST["town"])?$_POST["town"]:$object->town),'town',array('zipcode','selectcountry_id','state_id'));
		print '</td></tr>';

		// Country
		//$object->country_id=$object->country_id?$object->country_id:$mysoc->country_id;    // In edit mode we don't force to company country if not defined
		print '<tr><td width="25%">'.$langs->trans('Country').'</td><td>';
		print $form->select_country(isset($_POST["country_id"])?$_POST["country_id"]:$object->country_id,'country_id');
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
		print '</td></tr>';

		// State
		if (empty($conf->global->MEMBER_DISABLE_STATE))
		{
			print '<tr><td>'.$langs->trans('State').'</td><td>';
			print $formcompany->select_state($object->state_id,isset($_POST["country_id"])?$_POST["country_id"]:$object->country_id);
			print '</td></tr>';
		}

		// Tel
		print '<tr><td>'.$langs->trans("PhonePro").'</td><td><input type="text" name="phone" size="20" value="'.(isset($_POST["phone"])?$_POST["phone"]:$object->phone).'"></td></tr>';

		// Tel perso
		print '<tr><td>'.$langs->trans("PhonePerso").'</td><td><input type="text" name="phone_perso" size="20" value="'.(isset($_POST["phone_perso"])?$_POST["phone_perso"]:$object->phone_perso).'"></td></tr>';

		// Tel mobile
		print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td><input type="text" name="phone_mobile" size="20" value="'.(isset($_POST["phone_mobile"])?$_POST["phone_mobile"]:$object->phone_mobile).'"></td></tr>';

		// Birthday
		print "<tr><td>".$langs->trans("Birthday")."</td><td>\n";
		$form->select_date(($object->birth ? $object->birth : -1),'birth','','',1,'formsoc');
		print "</td></tr>\n";

		// Profil public
		print "<tr><td>".$langs->trans("Public")."</td><td>\n";
		print $form->selectyesno("public",(isset($_POST["public"])?$_POST["public"]:$object->public),1);
		print "</td></tr>\n";

		// Other attributes
		$parameters=array();
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
			print $object->showOptionals($extrafields,'edit');
		}

		// Third party Dolibarr
		if (! empty($conf->societe->enabled))
		{
			print '<tr><td>'.$langs->trans("LinkedToDolibarrThirdParty").'</td><td colspan="2" class="valeur">';
			if ($object->fk_soc)
			{
				$company=new Societe($db);
				$result=$company->fetch($object->fk_soc);
				print $company->getNomUrl(1);
			}
			else
			{
				print $langs->trans("NoThirdPartyAssociatedToMember");
			}
			print '</td></tr>';
		}

		// Login Dolibarr
		print '<tr><td>'.$langs->trans("LinkedToDolibarrUser").'</td><td colspan="2" class="valeur">';
		if ($object->user_id)
		{
			print $form->form_users($_SERVER['PHP_SELF'].'?rowid='.$object->id,$object->user_id,'none');
		}
		else print $langs->trans("NoDolibarrAccess");
		print '</td></tr>';

		print '</table>';

		print '<br><center>';
		print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
		print ' &nbsp; &nbsp; &nbsp; ';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</center';

		print '</form>';

		print '</div>';
	}

	if ($rowid && $action != 'edit')
	{
		dol_htmloutput_mesg($mesg);

		/* ************************************************************************** */
		/*                                                                            */
		/* Mode affichage                                                             */
		/*                                                                            */
		/* ************************************************************************** */

		$res=$object->fetch($rowid);
		if ($res < 0) {
			dol_print_error($db,$object->error); exit;
		}
		$res=$object->fetch_optionals($object->id,$extralabels);
		if ($res < 0) {
			dol_print_error($db); exit;
		}

		$adht = new AdherentType($db);
		$res=$adht->fetch($object->typeid);
		if ($res < 0) {
			dol_print_error($db); exit;
		}


		/*
		 * Affichage onglets
		*/
		$head = member_prepare_head($object);

		dol_fiche_head($head, 'general', $langs->trans("Member"), 0, 'user');

		dol_htmloutput_errors($errmsg,$errmsgs);

		// Confirm create user
		if ($action == 'create_user')
		{
			$login=$object->login;
			if (empty($login))
			{
				// Full firstname and name separated with a dot : firstname.name
				include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
				$login=dol_buildlogin($object->lastname,$object->firstname);
			}
			if (empty($login)) $login=strtolower(substr($object->firstname, 0, 4)) . strtolower(substr($object->lastname, 0, 4));

			// Create a form array
			$formquestion=array(
					array('label' => $langs->trans("LoginToCreate"), 'type' => 'text', 'name' => 'login', 'value' => $login)
			);
			$text=$langs->trans("ConfirmCreateLogin").'<br>';
			if (! empty($conf->societe->enabled))
			{
				if ($object->fk_soc > 0) $text.=$langs->trans("UserWillBeExternalUser");
				else $text.=$langs->trans("UserWillBeInternalUser");
			}
			$ret=$form->form_confirm($_SERVER["PHP_SELF"]."?rowid=".$object->id,$langs->trans("CreateDolibarrLogin"),$text,"confirm_create_user",$formquestion,'yes');
			if ($ret == 'html') print '<br>';
		}

		// Confirm create third party
		if ($action == 'create_thirdparty')
		{
			$name = $object->getFullName($langs);
			if (! empty($name))
			{
				if ($object->societe) $name.=' ('.$object->societe.')';
			}
			else
			{
				$name=$object->societe;
			}

			// Create a form array
			$formquestion=array(		array('label' => $langs->trans("NameToCreate"), 'type' => 'text', 'name' => 'companyname', 'value' => $name));

			$ret=$form->form_confirm($_SERVER["PHP_SELF"]."?rowid=".$object->id,$langs->trans("CreateDolibarrThirdParty"),$langs->trans("ConfirmCreateThirdParty"),"confirm_create_thirdparty",$formquestion,1);
			if ($ret == 'html') print '<br>';
		}

		// Confirm validate member
		if ($action == 'valid')
		{
			$langs->load("mails");

			$adht = new AdherentType($db);
			$adht->fetch($object->typeid);

			$subjecttosend=$object->makeSubstitution($conf->global->ADHERENT_MAIL_VALID_SUBJECT);
			$texttosend=$object->makeSubstitution($adht->getMailOnValid());

			$tmp=$langs->trans("SendAnEMailToMember");
			$tmp.=' ('.$langs->trans("MailFrom").': <b>'.$conf->global->ADHERENT_MAIL_FROM.'</b>, ';
			$tmp.=$langs->trans("MailRecipient").': <b>'.$object->email.'</b>)';
			$helpcontent='';
			$helpcontent.='<b>'.$langs->trans("MailFrom").'</b>: '.$conf->global->ADHERENT_MAIL_FROM.'<br>'."\n";
			$helpcontent.='<b>'.$langs->trans("MailRecipient").'</b>: '.$object->email.'<br>'."\n";
			$helpcontent.='<b>'.$langs->trans("Subject").'</b>:<br>'."\n";
			$helpcontent.=$subjecttosend."\n";
			$helpcontent.="<br>";
			$helpcontent.='<b>'.$langs->trans("Content").'</b>:<br>';
			$helpcontent.=dol_htmlentitiesbr($texttosend)."\n";
			$label=$form->textwithpicto($tmp,$helpcontent,1,'help');

			// Cree un tableau formulaire
			$formquestion=array();
			if ($object->email) $formquestion[]=array('type' => 'checkbox', 'name' => 'send_mail', 'label' => $label,  'value' => ($conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL?true:false));
			if (! empty($conf->global->ADHERENT_USE_MAILMAN)) {
				$formquestion[]=array('type'=>'other','label'=>$langs->transnoentitiesnoconv("SynchroMailManEnabled"),'value'=>'');
			}
			if (! empty($conf->global->ADHERENT_USE_SPIP))    {
				$formquestion[]=array('type'=>'other','label'=>$langs->transnoentitiesnoconv("SynchroSpipEnabled"),'value'=>'');
			}
			print $form->formconfirm("fiche.php?rowid=".$rowid,$langs->trans("ValidateMember"),$langs->trans("ConfirmValidateMember"),"confirm_valid",$formquestion,1);
		}

		// Confirm send card by mail
		if ($action == 'sendinfo')
		{
			print $form->formconfirm("fiche.php?rowid=".$rowid,$langs->trans("SendCardByMail"),$langs->trans("ConfirmSendCardByMail",$object->email),"confirm_sendinfo",'',0,1);
		}

		// Confirm resiliate
		if ($action == 'resign')
		{
			$langs->load("mails");

			$adht = new AdherentType($db);
			$adht->fetch($object->typeid);

			$subjecttosend=$object->makeSubstitution($conf->global->ADHERENT_MAIL_RESIL_SUBJECT);
			$texttosend=$object->makeSubstitution($adht->getMailOnResiliate());

			$tmp=$langs->trans("SendAnEMailToMember");
			$tmp.=' ('.$langs->trans("MailFrom").': <b>'.$conf->global->ADHERENT_MAIL_FROM.'</b>, ';
			$tmp.=$langs->trans("MailRecipient").': <b>'.$object->email.'</b>)';
			$helpcontent='';
			$helpcontent.='<b>'.$langs->trans("MailFrom").'</b>: '.$conf->global->ADHERENT_MAIL_FROM.'<br>'."\n";
			$helpcontent.='<b>'.$langs->trans("MailRecipient").'</b>: '.$object->email.'<br>'."\n";
			$helpcontent.='<b>'.$langs->trans("Subject").'</b>:<br>'."\n";
			$helpcontent.=$subjecttosend."\n";
			$helpcontent.="<br>";
			$helpcontent.='<b>'.$langs->trans("Content").'</b>:<br>';
			$helpcontent.=dol_htmlentitiesbr($texttosend)."\n";
			$label=$form->textwithpicto($tmp,$helpcontent,1,'help');

			// Cree un tableau formulaire
			$formquestion=array();
			if ($object->email) $formquestion[]=array('type' => 'checkbox', 'name' => 'send_mail', 'label' => $label, 'value' => (! empty($conf->global->ADHERENT_DEFAULT_SENDINFOBYMAIL)?'true':'false'));
			if ($backtopage)    $formquestion[]=array('type' => 'hidden', 'name' => 'backtopage', 'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]));
			$ret=$form->form_confirm("fiche.php?rowid=".$rowid,$langs->trans("ResiliateMember"),$langs->trans("ConfirmResiliateMember"),"confirm_resign",$formquestion);
			if ($ret == 'html') print '<br>';
		}

		// Confirm remove member
		if ($action == 'delete')
		{
			$formquestion=array();
			if ($backtopage) $formquestion[]=array('type' => 'hidden', 'name' => 'backtopage', 'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]));
			$ret=$form->form_confirm("fiche.php?rowid=".$rowid,$langs->trans("DeleteMember"),$langs->trans("ConfirmDeleteMember"),"confirm_delete",$formquestion,0,1);
			if ($ret == 'html') print '<br>';
		}

		/*
		 * Confirm add in spip
		*/
		if ($action == 'add_spip')
		{
			$ret=$form->form_confirm("fiche.php?rowid=".$rowid, $langs->trans('AddIntoSpip'), $langs->trans('AddIntoSpipConfirmation'), 'confirm_add_spip');
			if ($ret == 'html') print '<br>';
		}

		/*
		 * Confirm removed from spip
		*/
		if ($action == 'del_spip')
		{
			$ret=$form->form_confirm("fiche.php?rowid=$rowid", $langs->trans('DeleteIntoSpip'), $langs->trans('DeleteIntoSpipConfirmation'), 'confirm_del_spip');
			if ($ret == 'html') print '<br>';
		}

		$rowspan=17;
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) $rowspan++;
		if (! empty($conf->societe->enabled)) $rowspan++;

		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/liste.php">'.$langs->trans("BackToList").'</a>';

		// Ref
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
		print '<td class="valeur" colspan="2">';
		print $form->showrefnav($object, 'rowid', $linkback);
		print '</td></tr>';

		$showphoto='<td rowspan="'.$rowspan.'" align="center" class="hideonsmartphone" valign="middle" width="25%">';
		$showphoto.=$form->showphoto('memberphoto',$object);
		$showphoto.='</td>';

		// Login
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
		{
			print '<tr><td>'.$langs->trans("Login").' / '.$langs->trans("Id").'</td><td class="valeur">'.$object->login.'&nbsp;</td>';
			// Photo
			print $showphoto; $showphoto='';
			print '</tr>';
		}

		// Morphy
		print '<tr><td>'.$langs->trans("Nature").'</td><td class="valeur" >'.$object->getmorphylib().'</td>';
		print $showphoto; $showphoto='';
		print '</tr>';

		// Type
		print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">'.$adht->getNomUrl(1)."</td></tr>\n";

		// Company
		print '<tr><td>'.$langs->trans("Company").'</td><td class="valeur">'.$object->societe.'</td></tr>';

		// Civility
		print '<tr><td>'.$langs->trans("UserTitle").'</td><td class="valeur">'.$object->getCivilityLabel().'&nbsp;</td>';
		print '</tr>';

		// Lastname
		print '<tr><td>'.$langs->trans("Lastname").'</td><td class="valeur">'.$object->lastname.'&nbsp;</td>';
		print '</tr>';

		// Firstname
		print '<tr><td>'.$langs->trans("Firstname").'</td><td class="valeur">'.$object->firstname.'&nbsp;</td></tr>';

		// EMail
		print '<tr><td>'.$langs->trans("EMail").'</td><td class="valeur">'.dol_print_email($object->email,0,$object->fk_soc,1).'</td></tr>';
		
		// Password
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
		{
			print '<tr><td>'.$langs->trans("Password").'</td><td>'.preg_replace('/./i','*',$object->pass).'</td></tr>';
		}

		// Address
		print '<tr><td>'.$langs->trans("Address").'</td><td class="valeur">';
		dol_print_address($object->address,'gmap','member',$object->id);
		print '</td></tr>';

		// Zip / Town
		print '<tr><td class="nowrap">'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td class="valeur">'.$object->zip.(($object->zip && $object->town)?' / ':'').$object->town.'</td></tr>';

		// Country
		print '<tr><td>'.$langs->trans("Country").'</td><td class="valeur">';
		$img=picto_from_langcode($object->country_code);
		if ($img) print $img.' ';
		print getCountry($object->country_code);
		print '</td></tr>';

		// State
		print '<tr><td>'.$langs->trans('State').'</td><td class="valeur">'.$object->state.'</td>';

		// Tel pro.
		print '<tr><td>'.$langs->trans("PhonePro").'</td><td class="valeur">'.dol_print_phone($object->phone,$object->country_code,0,$object->fk_soc,1).'</td></tr>';

		// Tel perso
		print '<tr><td>'.$langs->trans("PhonePerso").'</td><td class="valeur">'.dol_print_phone($object->phone_perso,$object->country_code,0,$object->fk_soc,1).'</td></tr>';

		// Tel mobile
		print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td class="valeur">'.dol_print_phone($object->phone_mobile,$object->country_code,0,$object->fk_soc,1).'</td></tr>';

		// Birthday
		print '<tr><td>'.$langs->trans("Birthday").'</td><td class="valeur">'.dol_print_date($object->birth,'day').'</td></tr>';

		// Public
		print '<tr><td>'.$langs->trans("Public").'</td><td class="valeur">'.yn($object->public).'</td></tr>';

		// Status
		print '<tr><td>'.$langs->trans("Status").'</td><td class="valeur">'.$object->getLibStatut(4).'</td></tr>';

		// Other attributes
		$parameters=array();
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
			print $object->showOptionals($extrafields);
		}

		// Third party Dolibarr
		if (! empty($conf->societe->enabled))
		{
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans("LinkedToDolibarrThirdParty");
			print '</td>';
			if ($action != 'editthirdparty' && $user->rights->adherent->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editthirdparty&amp;rowid='.$object->id.'">'.img_edit($langs->trans('SetLinkToThirdParty'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="2" class="valeur">';
			if ($action == 'editthirdparty')
			{
				$htmlname='socid';
				print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" name="form'.$htmlname.'">';
				print '<input type="hidden" name="rowid" value="'.$object->id.'">';
				print '<input type="hidden" name="action" value="set'.$htmlname.'">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
				print '<tr><td>';
				print $form->select_company($object->fk_soc,'socid','',1);
				print '</td>';
				print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
				print '</tr></table></form>';
			}
			else
			{
				if ($object->fk_soc)
				{
					$company=new Societe($db);
					$result=$company->fetch($object->fk_soc);
					print $company->getNomUrl(1);
				}
				else
				{
					print $langs->trans("NoThirdPartyAssociatedToMember");
				}
			}
			print '</td></tr>';
		}

		// Login Dolibarr
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans("LinkedToDolibarrUser");
		print '</td>';
		if ($action != 'editlogin' && $user->rights->adherent->creer)
		{
			print '<td align="right">';
			if ($user->rights->user->user->creer)
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=editlogin&amp;rowid='.$object->id.'">'.img_edit($langs->trans('SetLinkToUser'),1).'</a>';
			}
			print '</td>';
		}
		print '</tr></table>';
		print '</td><td colspan="2" class="valeur">';
		if ($action == 'editlogin')
		{
			print $form->form_users($_SERVER['PHP_SELF'].'?rowid='.$object->id,$object->user_id,'userid','');
		}
		else
		{
			if ($object->user_id)
			{
				print $form->form_users($_SERVER['PHP_SELF'].'?rowid='.$object->id,$object->user_id,'none');
			}
			else print $langs->trans("NoDolibarrAccess");
		}
		print '</td></tr>';

		print "</table>\n";

		print "</div>\n";


		/*
		 * Barre d'actions
		*
		*/
		print '<div class="tabsAction">';

		if ($action != 'valid' && $action != 'editlogin' && $action != 'editthirdparty')
		{
			// Modify
			if ($user->rights->adherent->creer)
			{
				print '<div class="inline-block divButAction"><a class="butAction" href="fiche.php?rowid='.$rowid.'&action=edit">'.$langs->trans("Modify")."</a></div>";
			}
			else
			{
				print '<div class="inline-block divButAction"><font class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Modify").'</font></div>';
			}

			// Valider
			if ($object->statut == -1)
			{
				if ($user->rights->adherent->creer)
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="fiche.php?rowid='.$rowid.'&action=valid">'.$langs->trans("Validate")."</a></div>\n";
				}
				else
				{
					print '<div class="inline-block divButAction"><font class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Validate").'</font></div>';
				}
			}

			// Reactiver
			if ($object->statut == 0)
			{
				if ($user->rights->adherent->creer)
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="fiche.php?rowid='.$rowid.'&action=valid">'.$langs->trans("Reenable")."</a></div>\n";
				}
				else
				{
					print '<div class="inline-block divButAction"><font class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Reenable")."</font></div>";
				}
			}

			// Send card by email
			if ($user->rights->adherent->creer)
			{
				if ($object->statut >= 1)
				{
					if ($object->email) print '<div class="inline-block divButAction"><a class="butAction" href="fiche.php?rowid='.$object->id.'&action=sendinfo">'.$langs->trans("SendCardByMail")."</a></div>\n";
					else print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans("SendCardByMail")."</a></div>\n";
				}
				else
				{
					print '<div class="inline-block divButAction"><font class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("ValidateBefore")).'">'.$langs->trans("SendCardByMail")."</font></div>";
				}
			}
			else
			{
				print '<div class="inline-block divButAction"><font class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("SendCardByMail")."</font></div>";
			}

			// Resilier
			if ($object->statut >= 1)
			{
				if ($user->rights->adherent->supprimer)
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="fiche.php?rowid='.$rowid.'&action=resign">'.$langs->trans("Resiliate")."</a></div>\n";
				}
				else
				{
					print '<div class="inline-block divButAction"><font class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Resiliate")."</font></div>";
				}
			}

			// Create third party
			if (! empty($conf->societe->enabled) && ! $object->fk_soc)
			{
				if ($user->rights->societe->creer)
				{
					if ($object->statut != -1) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&amp;action=create_thirdparty">'.$langs->trans("CreateDolibarrThirdParty").'</a></div>';
					else print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("ValidateBefore")).'">'.$langs->trans("CreateDolibarrThirdParty").'</a></div>';
				}
				else
				{
					print '<div class="inline-block divButAction"><font class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("CreateDolibarrThirdParty")."</font></div>";
				}
			}

			// Create user
			if (! $user->societe_id && ! $object->user_id)
			{
				if ($user->rights->user->user->creer)
				{
					if ($object->statut != -1) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&amp;action=create_user">'.$langs->trans("CreateDolibarrLogin").'</a></div>';
					else print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("ValidateBefore")).'">'.$langs->trans("CreateDolibarrLogin").'</a></div>';
				}
				else
				{
					print '<div class="inline-block divButAction"><font class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("CreateDolibarrLogin")."</font></div>";
				}
			}

			// Delete
			if ($user->rights->adherent->supprimer)
			{
				print '<div class="inline-block divButAction"><a class="butActionDelete" href="fiche.php?rowid='.$object->id.'&action=delete">'.$langs->trans("Delete")."</a></div>\n";
			}
			else
			{
				print '<div class="inline-block divButAction"><font class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Delete")."</font></div>";
			}

			// Action SPIP
			if (! empty($conf->mailmanspip->enabled) && ! empty($conf->global->ADHERENT_USE_SPIP))
			{
				$isinspip = $mailmanspip->is_in_spip($object);

				if ($isinspip == 1)
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="fiche.php?rowid='.$object->id.'&action=del_spip">'.$langs->trans("DeleteIntoSpip")."</a></div>\n";
				}
				if ($isinspip == 0)
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="fiche.php?rowid='.$object->id.'&action=add_spip">'.$langs->trans("AddIntoSpip")."</a></div>\n";
				}
			}

		}

		print '</div>';

		if ($isinspip == -1)
		{
			print '<br><br><font class="error">'.$langs->trans('SPIPConnectionFailed').': '.$mailmanspip->error.'</font>';
		}
		print "<br>\n";

	}
}

llxFooter();

$db->close();
?>
