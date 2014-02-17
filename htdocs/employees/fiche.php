<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2012-2013 Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2011-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com> 
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
 *       \file       htdocs/employees/fiche.php
 *       \ingroup    employee
 *       \brief      Page of employee
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/employee.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/employees/class/employee.class.php';
require_once DOL_DOCUMENT_ROOT.'/employees/class/employee_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$langs->load("companies");
$langs->load("bills");
$langs->load("employees");
$langs->load("users");
$langs->load('other');

$action=GETPOST('action','alpha');
$backtopage=GETPOST('backtopage','alpha');
$confirm=GETPOST('confirm','alpha');
$rowid=GETPOST('rowid','int');
$typeid=GETPOST('typeid','int');
$userid=GETPOST('userid','int');

if (! empty($conf->mailmanspip->enabled))
{
	include_once DOL_DOCUMENT_ROOT.'/mailmanspip/class/mailmanspip.class.php';

	$langs->load('mailmanspip');

	$mailmanspip = new MailmanSpip($db);
}

$object = new Employee($db);
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
	$objcanvas->getCanvas('employee', 'employeecard', $canvas);
}

// Security check
$result=restrictedArea($user,'employee',$rowid,'','','fk_user', 'rowid', $objcanvas);

$errmsg=''; $errmsgs=array();

if ($rowid > 0)
{
	// Load employee
	$result = $object->fetch($rowid);

	// Define variables to know what current user can do on users
	$canadduser=($user->admin || $user->rights->user->user->creer);
	// Define variables to know what current user can do on properties of user linked to edited employee
	if ($object->user_id)
	{
		// $user est le user qui edite, $object->user_id est l'id de l'utilisateur lies au membre edite
		$caneditfielduser=((($user->id == $object->user_id) && $user->rights->user->self->creer)
				|| (($user->id != $object->user_id) && $user->rights->user->user->creer));
		$caneditpassworduser=((($user->id == $object->user_id) && $user->rights->user->self->password)
				|| (($user->id != $object->user_id) && $user->rights->user->user->password));
	}
}

// Define variables to determine what the current user can do on the employees
$canaddemployee=$user->rights->employee->creer;
// Define variables to determine what the current user can do on the properties of a employee
if ($rowid)
{
	$caneditfieldemployee=$user->rights->employee->creer;
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('employeecard'));


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
			$sql ="SELECT rowid FROM ".MAIN_DB_PREFIX."employee";
			$sql.=" WHERE fk_soc = '".$socid."'";
			$sql.=" AND entity = ".$conf->entity;
			$resql = $db->query($sql);
			if ($resql)
			{
				$obj = $db->fetch_object($resql);
				if ($obj && $obj->rowid > 0)
				{
					$otheremployee=new employee($db);
					$otheremployee->fetch($obj->rowid);
					$thirdparty=new Societe($db);
					$thirdparty->fetch($socid);
					$error++;
					$errmsg='<div class="error">'.$langs->trans("ErrorEmployeeIsAlreadyLinkedToThisThirdParty",$otheremployee->getFullName($langs),$otheremployee->login,$thirdparty->name).'</div>';
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

// Create user from a employee
if ($action == 'confirm_create_user' && $confirm == 'yes' && $user->rights->user->user->creer)
{
	if ($result > 0)
	{
		// Creation user
		$nuser = new User($db);
		$result=$nuser->create_from_employee($object,GETPOST('login'));

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

if ($action == 'confirm_sendinfo' && $confirm == 'yes')
{
	if ($object->email)
	{
		$from=$conf->email_from;
		if (! empty($conf->global->EMPLOYEE_MAIL_FROM)) $from=$conf->global->EMPLOYEE_MAIL_FROM;

		$result=$object->send_an_email($langs->transnoentitiesnoconv("ThisIsContentOfYourCard")."\n\n%INFOS%\n\n",$langs->transnoentitiesnoconv("CardContent"));

		$langs->load("mails");
		$mesg=$langs->trans("MailSuccessfulySent", $from, $object->email);
	}
}

if ($action == 'update' && ! $_POST["cancel"] && $user->rights->employee->creer)
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
	$sex=$sex=$_POST["sex"];;
	if (empty($lastname)) {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorFieldRequired",$langs->transnoentities("Lastname"))."<br>\n";
	}
	if (!isset($firstname) || $firstname=='') {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorFieldRequired",$langs->transnoentities("Firstname"))."<br>\n";
	}

	// Create new object
	if ($result > 0 && ! $error)
	{
		$object->oldcopy=dol_clone($object);

		// Change values
		$object->civility_id = trim($_POST["civility_id"]);
		$object->firstname   = trim($_POST["firstname"]);
		$object->lastname    = trim($_POST["lastname"]);
		$object->login       = trim($_POST["login"]);
		$object->pass        = trim($_POST["pass"]);

		$object->user        = trim($_POST["user"]);
		
		$object->address     = trim($_POST["address"]);
		$object->zip         = trim($_POST["zipcode"]);
		$object->town        = trim($_POST["town"]);
		$object->state_id    = $_POST["state_id"];
		$object->country_id  = $_POST["country_id"];

		$object->phone_pro   = trim($_POST["phone_pro"]);
		$object->phone_perso = trim($_POST["phone_perso"]);
		$object->phone_mobile= trim($_POST["phone_mobile"]);
		$object->email       = trim($_POST["email"]);
		$object->skype       = trim($_POST["skype"]);
		$object->birth       = $birthdate;

		$object->typeid      = $_POST["typeid"];
		//$object->note        = trim($_POST["comment"]);
		$object->sex         = $_POST["sex"];

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
			// Logo/Photo save
			$dir= $conf->employee->dir_output . '/' . get_exdir($object->id,2,0,1).'/photos';
			$file_OK = is_uploaded_file($_FILES['photo']['tmp_name']);
			if ($file_OK)
			{
				if (GETPOST('deletephoto'))
				{
					$fileimg=$conf->employee->dir_output.'/'.get_exdir($object->id,2,0,1).'/photos/'.$object->photo;
					$dirthumbs=$conf->employee->dir_output.'/'.get_exdir($object->id,2,0,1).'/photos/thumbs';
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
							// Create small thumbs for user (Ratio is near 16/9)
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
			else
			{
				switch($_FILES['photo']['error'])
				{
					case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini
					case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
						$errors[] = "ErrorFileSizeTooLarge";
						break;
					case 3: //uploaded file was only partially uploaded
						$errors[] = "ErrorFilePartiallyUploaded";
						break;
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

if ($action == 'add' && $user->rights->employee->creer)
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
	$civility_id=$_POST["civility_id"];
	$lastname=$_POST["lastname"];
	$firstname=$_POST["firstname"];
	$address=$_POST["address"];
	$zip=$_POST["zipcode"];
	$town=$_POST["town"];
	$state_id=$_POST["state_id"];
	$country_id=$_POST["country_id"];

	$phone_pro=$_POST["phone_pro"];
	$phone_perso=$_POST["phone_perso"];
	$phone_mobile=$_POST["phone_mobile"];
	$skype=$_POST["employee_skype"];
	$email=$_POST["employee_email"];
	$login=$_POST["employee_login"];
	$pass=$_POST["password"];
	$photo=$_POST["photo"];
	//$comment=$_POST["comment"];
	$sex=$_POST["sex"];
	$public=$_POST["public"];

	$userid=$_POST["userid"];
	$socid=$_POST["socid"];

	$object->civility_id = $civility_id;
	$object->firstname   = $firstname;
	$object->lastname    = $lastname;
	$object->address     = $address;
	$object->zip         = $zip;
	$object->town        = $town;
	$object->state_id    = $state_id;
	$object->country_id  = $country_id;
	$object->phone_pro   = $phone_pro;
	$object->phone_perso = $phone_perso;
	$object->phone_mobile= $phone_mobile;
	$object->skype       = $skype;
	$object->email       = $email;
	$object->login       = $login;
	$object->pass        = $pass;
	$object->naiss       = $birthdate;
	$object->photo       = $photo;
	$object->typeid      = $typeid;
	$object->note        = $note;
	$object->sex         = $sex;
	$object->user_id     = $userid;
	$object->fk_user     = $userid;
	$object->public      = $public;

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost($extralabels,$object);

  // Check parameters
	if (empty($sex) || $sex == "-1") {
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Nature"))."<br>\n";
	}
  
	// Test si le login existe deja
	if (empty($conf->global->EMPLOYEE_LOGIN_NOT_REQUIRED))
	{
		if (empty($login)) {
			$error++;
			$errmsg .= $langs->trans("ErrorFieldRequired",$langs->trans("Login"))."<br>\n";
		}
		else {
			$sql = "SELECT login FROM ".MAIN_DB_PREFIX."employee WHERE login='".$db->escape($login)."'";
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
	if (empty($lastname)) {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorFieldRequired",$langs->transnoentities("Lastname"))."<br>\n";
	}
	if (!isset($firstname) || $firstname=='') {
		$error++;
		$langs->load("errors");
		$errmsg .= $langs->trans("ErrorFieldRequired",$langs->transnoentities("Firstname"))."<br>\n";
	}
	if (! ($typeid > 0)) {	// Keep () before !
		$error++;
		$errmsg .= $langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type"))."<br>\n";
	}
	if ($conf->global->EMPLOYEE_MAIL_REQUIRED && ! isValidEMail($email)) {
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

if ($user->rights->employee->supprimer && $action == 'confirm_delete' && $confirm == 'yes')
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

if ($user->rights->employee->creer && $action == 'confirm_valid' && $confirm == 'yes')
{
	$error=0;

	$db->begin();

	$empt = new EmployeeType($db);
	$empt->fetch($object->typeid);

	$result=$object->validate($user);

	if ($result >= 0 && ! count($object->errors))
	{
		// Send confirmation Email (selon param du type employee sinon generique)
		if ($object->email && GETPOST("send_mail"))
		{
			$result=$object->send_an_email($empt->getMailOnValid(),$conf->global->EMPLOYEE_MAIL_VALID_SUBJECT,array(),array(),array(),"","",0,2);
			if ($result < 0)
			{
				$error++;
				$errmsg.=$object->error;
			}
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

if ($user->rights->employee->supprimer && $action == 'confirm_resign')
{
	if ($confirm == 'yes')
	{
		$empt = new EmployeeType($db);
		$empt->fetch($object->typeid);

		$result=$object->resiliate($user);

		if ($result >= 0 && ! count($object->errors))
		{
			if ($object->email && GETPOST("send_mail"))
			{
				$result=$object->send_an_email($empt->getMailOnResiliate(),$conf->global->EMPLOYEE_MAIL_RESIL_SUBJECT,array(),array(),array(),"","",0,-1);
			}
			if ($result < 0)
			{
				$errmsg.=$object->error;
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
if ($user->rights->employee->supprimer && $action == 'confirm_del_spip' && $confirm == 'yes')
{
	if (! count($object->errors))
	{
		if (!$mailmanspip->del_to_spip($object))
		{
			$errmsg.= $langs->trans('DeleteIntoSpipError').': '.$mailmanspip->error."<BR>\n";
		}
	}
}

if ($user->rights->employee->creer && $action == 'confirm_add_spip' && $confirm == 'yes')
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

$help_url='EN:Module_Employees|FR:Module_Salariés|ES:M&oacute;dulo_Asalariados';
llxHeader('',$langs->trans("Employee"),$help_url);

$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action))
{
	// -----------------------------------------
	// When used with CANVAS
	// -----------------------------------------
	if (empty($object->error) && $rowid)
	{
		$object = new Employee($db);
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
		$object->state_id = GETPOST('state_id', 'int');

		// We set country_id, country_code and country for the selected country
		$object->country_id=GETPOST('country_id','int')?GETPOST('country_id','int'):$mysoc->country_id;
		if ($object->country_id)
		{
			$tmparray=getCountry($object->country_id,'all');
			$object->country_code=$tmparray['code'];
			$object->country=$tmparray['label'];
		}

		$empt = new EmployeeType($db);

		print_fiche_titre($langs->trans("NewEmployee"));

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
							jQuery("#tdlastname").removeClass("fieldrequired");
							jQuery("#tdfirstname").removeClass("fieldrequired");
						}
						initfieldrequired();
					})';
			print '</script>'."\n";
		}

		print '<form name="formsoc" action="'.$_SERVER["PHP_SELF"].'" method="post" enctype="multipart/form-data">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="add">';

		print '<table class="border" width="100%">';

		// Login
		if (empty($conf->global->EMPLOYEE_LOGIN_NOT_REQUIRED))
		{
			print '<tr><td><span class="fieldrequired">'.$langs->trans("Login").' / '.$langs->trans("Id").'</span></td><td><input type="text" name="employee_login" size="40" value="'.(isset($_POST["employee_login"])?$_POST["employee_login"]:$object->login).'"></td></tr>';
		}

		// Type
		print '<tr><td class="fieldrequired">'.$langs->trans("EmployeeType").'</td><td>';
		$listetype=$empt->liste_array();
		if (count($listetype))
		{
			print $form->selectarray("typeid", $listetype, GETPOST('typeid','int')?GETPOST('typeid','int'):$typeid, count($listetype)>1?1:0);
		} else {
			print '<font class="error">'.$langs->trans("NoTypeDefinedGoToSetup").'</font>';
		}
		print "</td>\n";

    // Civility
		print '<tr><td>'.$langs->trans("UserTitle").'</td><td>';
		print $formcompany->select_civility(GETPOST('civility_id','int')?GETPOST('civility_id','int'):$object->civility_id,'civility_id').'</td>';
		print '</tr>';

		// Lastname
		print '<tr><td id="tdlastname"><span class="fieldrequired">'.$langs->trans("Lastname").'</span></td><td><input type="text" name="lastname" value="'.(GETPOST('lastname','alpha')?GETPOST('lastname','alpha'):$object->lastname).'" size="40"></td>';
		print '</tr>';

		// Firstname
		print '<tr><td id="tdfirstname"><span class="fieldrequired">'.$langs->trans("Firstname").'</span></td><td><input type="text" name="firstname" size="40" value="'.(GETPOST('firstname','alpha')?GETPOST('firstname','alpha'):$object->firstname).'"></td>';
		print '</tr>';

		// EMail
		print '<tr><td>'.($conf->global->EMPLOYEE_MAIL_REQUIRED?'<span class="fieldrequired">':'').$langs->trans("EMail").($conf->global->EMPLOYEE_MAIL_REQUIRED?'</span>':'').'</td><td><input type="text" name="employee_email" size="40" value="'.(GETPOST('employee_email','alpha')?GETPOST('employee_email','alpha'):$object->email).'"></td></tr>';

		// Password
		if (empty($conf->global->EMPLOYEE_LOGIN_NOT_REQUIRED))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			$generated_password=getRandomPassword('');
			print '<tr><td><span class="fieldrequired">'.$langs->trans("Password").'</span></td><td>';
			print '<input size="30" maxsize="32" type="text" name="password" value="'.$generated_password.'">';
			print '</td></tr>';
		}

		// Sex
		$sexs["fem"] = $langs->trans("Female");
		$sexs["mal"] = $langs->trans("Male");
		print '<tr><td>'.$langs->trans("Sex")."</td><td>\n";
		print $form->selectarray("sex", $sexs, GETPOST('sex','alpha')?GETPOST('sex','alpha'):$object->sex, 1);
		print "</td>\n";
    
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
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
		print '</td></tr>';

		// State
		if (empty($conf->global->EMPLOYEE_DISABLE_STATE))
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
		print '<tr><td>'.$langs->trans("PhonePro").'</td><td><input type="text" name="phone_pro" size="20" value="'.(GETPOST('phone_pro','alpha')?GETPOST('phone_pro','alpha'):$object->phone_pro).'"></td></tr>';

		// Tel perso
		print '<tr><td>'.$langs->trans("PhonePerso").'</td><td><input type="text" name="phone_perso" size="20" value="'.(GETPOST('phone_perso','alpha')?GETPOST('phone_perso','alpha'):$object->phone_perso).'"></td></tr>';

		// Tel mobile
		print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td><input type="text" name="phone_mobile" size="20" value="'.(GETPOST('phone_mobile','alpha')?GETPOST('phone_mobile','alpha'):$object->phone_mobile).'"></td></tr>';

	  // Skype
	  if (! empty($conf->skype->enabled))
	  {
			print '<tr><td>'.$langs->trans("Skype").'</td><td><input type="text" name="employee_skype" size="40" value="'.(GETPOST('employee_skype','alpha')?GETPOST('employee_skype','alpha'):$object->skype).'"></td></tr>';
	  }

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
		// Login Dolibarr
		print '<tr><td>'.$langs->trans("LinkedToDolibarrUser").'</td><td class="valeur">';
		print $form->select_dolusers($object->user_id,'userid',1);
		print '</td></tr>';
		*/

		print "</table>\n";
		print '<br>';

		print '<center><input type="submit" class="button" value="'.$langs->trans("AddEmployee").'"></center>';

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

		$empt = new EmployeeType($db);
		$empt->fetch($object->typeid);

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

		$head = employee_prepare_head($object);

		dol_fiche_head($head, 'general', $langs->trans("Employee"), 0, 'user');

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
					jQuery("#tdlastname").removeClass("fieldrequired");
					jQuery("#tdfirstname").removeClass("fieldrequired");
				}
				initfieldrequired();
			})';
			print '</script>'."\n";
		}

		$rowspan=15;
		if (empty($conf->global->EMPLOYEE_LOGIN_NOT_REQUIRED)) $rowspan++;
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
		if (empty($conf->global->EMPLOYEE_LOGIN_NOT_REQUIRED))
		{
			print '<tr><td><span class="fieldrequired">'.$langs->trans("Login").' / '.$langs->trans("Id").'</span></td><td colspan="2"><input type="text" name="login" size="30" value="'.(isset($_POST["login"])?$_POST["login"]:$object->login).'"></td></tr>';
		}

		// Photo
		print '<td align="center" class="hideonsmartphone" valign="middle" width="25%" rowspan="'.$rowspan.'">';
		print $form->showphoto('employeephoto',$object)."\n";
		if ($caneditfieldemployee)
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
		if ($user->rights->employee->creer)
		{
			print $form->selectarray("typeid", $empt->liste_array(), (isset($_POST["typeid"])?$_POST["typeid"]:$object->typeid));
		}
		else
		{
			print $empt->getNomUrl(1);
			print '<input type="hidden" name="typeid" value="'.$object->typeid.'">';
		}
		print "</td></tr>";

	  // Civility
		print '<tr><td width="20%">'.$langs->trans("UserTitle").'</td><td width="35%">';
		print $formcompany->select_civility(isset($_POST["civility_id"])?$_POST["civility_id"]:$object->civility_id)."\n";
		print '</td>';
		print '</tr>';

		// Lastname
		print '<tr><td id="tdlastname"><span class="fieldrequired">'.$langs->trans("Lastname").'</span></td><td><input type="text" name="lastname" size="40" value="'.(isset($_POST["lastname"])?$_POST["lastname"]:$object->lastname).'"></td>';
		print '</tr>';

		// Firstname
		print '<tr><td id="tdfirstname"><span class="fieldrequired">'.$langs->trans("Firstname").'</span></td><td><input type="text" name="firstname" size="40" value="'.(isset($_POST["firstname"])?$_POST["firstname"]:$object->firstname).'"></td>';
		print '</tr>';

		// EMail
		print '<tr><td>'.($conf->global->EMPLOYEE_MAIL_REQUIRED?'<span class="fieldrequired">':'').$langs->trans("EMail").($conf->global->EMPLOYEE_MAIL_REQUIRED?'</span>':'').'</td><td><input type="text" name="email" size="40" value="'.(isset($_POST["email"])?$_POST["email"]:$object->email).'"></td></tr>';

		// Password
		if (empty($conf->global->EMPLOYEE_LOGIN_NOT_REQUIRED))
		{
			print '<tr><td class="fieldrequired">'.$langs->trans("Password").'</td><td><input type="password" name="pass" size="30" value="'.(isset($_POST["pass"])?$_POST["pass"]:$object->pass).'"></td></tr>';
		}
    
    // Sex
		$sexs["fem"] = $langs->trans("Female");
		$sexs["mal"] = $langs->trans("Male");
		print '<tr><td>'.$langs->trans("Sex").'</span></td><td>';
		print $form->selectarray("sex", $sexs, isset($_POST["sex"])?$_POST["sex"]:$object->sex);
		print "</td>";

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
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
		print '</td></tr>';

		// State
		if (empty($conf->global->EMPLOYEE_DISABLE_STATE))
		{
			print '<tr><td>'.$langs->trans('State').'</td><td>';
			print $formcompany->select_state($object->state_id,isset($_POST["country_id"])?$_POST["country_id"]:$object->country_id);
			print '</td></tr>';
		}

		// Tel pro
		print '<tr><td>'.$langs->trans("PhonePro").'</td><td><input type="text" name="phone_pro" size="20" value="'.(isset($_POST["phone_pro"])?$_POST["phone_pro"]:$object->phone_pro).'"></td></tr>';

		// Tel perso
		print '<tr><td>'.$langs->trans("PhonePerso").'</td><td><input type="text" name="phone_perso" size="20" value="'.(isset($_POST["phone_perso"])?$_POST["phone_perso"]:$object->phone_perso).'"></td></tr>';

		// Tel mobile
		print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td><input type="text" name="phone_mobile" size="20" value="'.(isset($_POST["phone_mobile"])?$_POST["phone_mobile"]:$object->phone_mobile).'"></td></tr>';

	  // Skype
	  if (! empty($conf->skype->enabled))
	  {
		  print '<tr><td>'.$langs->trans("Skype").'</td><td><input type="text" name="skype" size="40" value="'.(isset($_POST["skype"])?$_POST["skype"]:$object->skype).'"></td></tr>';
	  }

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

		$empt = new EmployeeType($db);
		$res=$empt->fetch($object->typeid);
		if ($res < 0) {
			dol_print_error($db); exit;
		}


		/*
		 * Affichage onglets
		*/
		$head = employee_prepare_head($object);

		dol_fiche_head($head, 'general', $langs->trans("Employee"), 0, 'user');

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
			print $form->formconfirm($_SERVER["PHP_SELF"]."?rowid=".$object->id,$langs->trans("CreateDolibarrLogin"),$text,"confirm_create_user",$formquestion,'yes');
		}

		// Confirm validate employee
		if ($action == 'valid')
		{
			$langs->load("mails");

			$empt = new EmployeeType($db);
			$empt->fetch($object->typeid);

			$subjecttosend=$object->makeSubstitution($conf->global->EMPLOYEE_MAIL_VALID_SUBJECT);
			$texttosend=$object->makeSubstitution($empt->getMailOnValid());

			$tmp=$langs->trans("SendAnEMailToEmployee");
			$tmp.=' ('.$langs->trans("MailFrom").': <b>'.$conf->global->EMPLOYEE_MAIL_FROM.'</b>, ';
			$tmp.=$langs->trans("MailRecipient").': <b>'.$object->email.'</b>)';
			$helpcontent='';
			$helpcontent.='<b>'.$langs->trans("MailFrom").'</b>: '.$conf->global->EMPLOYEE_MAIL_FROM.'<br>'."\n";
			$helpcontent.='<b>'.$langs->trans("MailRecipient").'</b>: '.$object->email.'<br>'."\n";
			$helpcontent.='<b>'.$langs->trans("Subject").'</b>:<br>'."\n";
			$helpcontent.=$subjecttosend."\n";
			$helpcontent.="<br>";
			$helpcontent.='<b>'.$langs->trans("Content").'</b>:<br>';
			$helpcontent.=dol_htmlentitiesbr($texttosend)."\n";
			$label=$form->textwithpicto($tmp,$helpcontent,1,'help');

			// Cree un tableau formulaire
			$formquestion=array();
			if ($object->email) $formquestion[]=array('type' => 'checkbox', 'name' => 'send_mail', 'label' => $label,  'value' => ($conf->global->EMPLOYEE_DEFAULT_SENDINFOBYMAIL?true:false));
			if (! empty($conf->mailman->enabled) && ! empty($conf->global->EMPLOYEE_USE_MAILMAN)) {
				$formquestion[]=array('type'=>'other','label'=>$langs->transnoentitiesnoconv("SynchroMailManEnabled"),'value'=>'');
			}
			if (! empty($conf->mailman->enabled) && ! empty($conf->global->EMPLOYEE_USE_SPIP))    {
				$formquestion[]=array('type'=>'other','label'=>$langs->transnoentitiesnoconv("SynchroSpipEnabled"),'value'=>'');
			}
			print $form->formconfirm("fiche.php?rowid=".$rowid,$langs->trans("ValidateEmployee"),$langs->trans("ConfirmValidateEmployee"),"confirm_valid",$formquestion,1);
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

			$empt = new EmployeeType($db);
			$empt->fetch($object->typeid);

			$subjecttosend=$object->makeSubstitution($conf->global->EMPLOYEE_MAIL_RESIL_SUBJECT);
			$texttosend=$object->makeSubstitution($empt->getMailOnResiliate());

			$tmp=$langs->trans("SendAnEMailToEmployee");
			$tmp.=' ('.$langs->trans("MailFrom").': <b>'.$conf->global->EMPLOYEE_MAIL_FROM.'</b>, ';
			$tmp.=$langs->trans("MailRecipient").': <b>'.$object->email.'</b>)';
			$helpcontent='';
			$helpcontent.='<b>'.$langs->trans("MailFrom").'</b>: '.$conf->global->EMPLOYEE_MAIL_FROM.'<br>'."\n";
			$helpcontent.='<b>'.$langs->trans("MailRecipient").'</b>: '.$object->email.'<br>'."\n";
			$helpcontent.='<b>'.$langs->trans("Subject").'</b>:<br>'."\n";
			$helpcontent.=$subjecttosend."\n";
			$helpcontent.="<br>";
			$helpcontent.='<b>'.$langs->trans("Content").'</b>:<br>';
			$helpcontent.=dol_htmlentitiesbr($texttosend)."\n";
			$label=$form->textwithpicto($tmp,$helpcontent,1,'help');

			// Cree un tableau formulaire
			$formquestion=array();
			if ($object->email) $formquestion[]=array('type' => 'checkbox', 'name' => 'send_mail', 'label' => $label, 'value' => (! empty($conf->global->EMPLOYEE_DEFAULT_SENDINFOBYMAIL)?'true':'false'));
			if ($backtopage)    $formquestion[]=array('type' => 'hidden', 'name' => 'backtopage', 'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]));
			print $form->formconfirm("fiche.php?rowid=".$rowid,$langs->trans("ResiliateEmployee"),$langs->trans("ConfirmResiliateEmployee"),"confirm_resign",$formquestion);
		}

		// Confirm remove employee
		if ($action == 'delete')
		{
			$formquestion=array();
			if ($backtopage) $formquestion[]=array('type' => 'hidden', 'name' => 'backtopage', 'value' => ($backtopage != '1' ? $backtopage : $_SERVER["HTTP_REFERER"]));
			print $form->formconfirm("fiche.php?rowid=".$rowid,$langs->trans("DeleteEmployee"),$langs->trans("ConfirmDeleteEmployee"),"confirm_delete",$formquestion,0,1);
		}

		/*
		 * Confirm add in spip
		 */
		if ($action == 'add_spip')
		{
			print $form->formconfirm("fiche.php?rowid=".$rowid, $langs->trans('AddIntoSpip'), $langs->trans('AddIntoSpipConfirmation'), 'confirm_add_spip');
		}

		/*
		 * Confirm removed from spip
		 */
		if ($action == 'del_spip')
		{
			print $form->formconfirm("fiche.php?rowid=$rowid", $langs->trans('DeleteIntoSpip'), $langs->trans('DeleteIntoSpipConfirmation'), 'confirm_del_spip');
		}

		$rowspan=18;
		if (empty($conf->global->EMPLOYEE_LOGIN_NOT_REQUIRED)) $rowspan++;
		if (! empty($conf->skype->enabled)) $rowspan++;

		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/employees/liste.php">'.$langs->trans("BackToList").'</a>';

		// Ref
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
		print '<td class="valeur" colspan="2">';
		print $form->showrefnav($object, 'rowid', $linkback);
		print '</td></tr>';

		$showphoto='<td rowspan="'.$rowspan.'" align="center" class="hideonsmartphone" valign="middle" width="25%">';
		$showphoto.=$form->showphoto('employeephoto',$object);
		$showphoto.='</td>';

		// Login
		if (empty($conf->global->EMPLOYEE_LOGIN_NOT_REQUIRED))
		{
			print '<tr><td>'.$langs->trans("Login").' / '.$langs->trans("Id").'</td><td class="valeur">'.$object->login.'&nbsp;</td>';
			// Photo
			print $showphoto; $showphoto='';
			print '</tr>';
		}

		// Type
		print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">'.$empt->getNomUrl(1)."</td></tr>\n";

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
		if (empty($conf->global->EMPLOYEE_LOGIN_NOT_REQUIRED))
		{
			print '<tr><td>'.$langs->trans("Password").'</td><td>'.preg_replace('/./i','*',$object->pass).'</td></tr>';
		}

    // Sex
		print '<tr><td>'.$langs->trans("Sex").'</td><td class="valeur" >'.$object->getsexlib().'</td>';
		print $showphoto; $showphoto='';
		print '</tr>';

		// Address
		print '<tr><td>'.$langs->trans("Address").'</td><td class="valeur">';
		dol_print_address($object->address,'gmap','employee',$object->id);
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
		print '<tr><td>'.$langs->trans("PhonePro").'</td><td class="valeur">'.dol_print_phone($object->phone_pro,$object->country_code,0,$object->fk_soc,1).'</td></tr>';

		// Tel perso
		print '<tr><td>'.$langs->trans("PhonePerso").'</td><td class="valeur">'.dol_print_phone($object->phone_perso,$object->country_code,0,$object->fk_soc,1).'</td></tr>';

		// Tel mobile
		print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td class="valeur">'.dol_print_phone($object->phone_mobile,$object->country_code,0,$object->fk_soc,1).'</td></tr>';

    // Skype
		if (! empty($conf->skype->enabled))
		{
			print '<tr><td>'.$langs->trans("Skype").'</td><td class="valeur">'.dol_print_skype($object->skype,0,$object->fk_soc,1).'</td></tr>';
		}

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

		// Login Dolibarr
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans("LinkedToDolibarrUser");
		print '</td>';
		if ($action != 'editlogin' && $user->rights->employee->creer)
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
			if ($user->rights->employee->creer)
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
				if ($user->rights->employee->creer)
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
				if ($user->rights->employee->creer)
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="fiche.php?rowid='.$rowid.'&action=valid">'.$langs->trans("Reenable")."</a></div>\n";
				}
				else
				{
					print '<div class="inline-block divButAction"><font class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Reenable")."</font></div>";
				}
			}

			// Send card by email
			if ($user->rights->employee->creer)
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
				if ($user->rights->employee->supprimer)
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="fiche.php?rowid='.$rowid.'&action=resign">'.$langs->trans("Resiliate")."</a></div>\n";
				}
				else
				{
					print '<div class="inline-block divButAction"><font class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Resiliate")."</font></div>";
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
			if ($user->rights->employee->supprimer)
			{
				print '<div class="inline-block divButAction"><a class="butActionDelete" href="fiche.php?rowid='.$object->id.'&action=delete">'.$langs->trans("Delete")."</a></div>\n";
			}
			else
			{
				print '<div class="inline-block divButAction"><font class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Delete")."</font></div>";
			}

			// Action SPIP
			if (! empty($conf->mailmanspip->enabled) && ! empty($conf->global->EMPLOYEE_USE_SPIP))
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
