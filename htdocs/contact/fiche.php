<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
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
 *       \file       htdocs/contact/fiche.php
 *       \ingroup    societe
 *       \brief      Onglet general d'un contact
 *       \version    $Id$
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/contact.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php");

$langs->load("companies");
$langs->load("users");

$errors = array();
$socid = GETPOST("socid");

// Security check
$id = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;

$object = new Contact($db);

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
if (!empty($id)) $object->getCanvas($id);
$canvas = (!empty($object->canvas)?$object->canvas:GETPOST("canvas"));
if (! empty($canvas))
{
	require_once(DOL_DOCUMENT_ROOT."/core/class/canvas.class.php");
	$objcanvas = new Canvas($db);

	$objcanvas->getCanvas('contact','contactcard',$canvas);

	// Security check
	$result = $objcanvas->restrictedArea($user, 'contact', $id, 'socpeople');
}
else
{
	// Security check
	$result = restrictedArea($user, 'contact', $id, 'socpeople'); // If we create a contact with no company (shared contacts), no check on write permission
}


/*
 *	Actions
 */

// If canvas is defined, because on url, or because contact was created with canvas feature on,
// we use the canvas feature.
// If canvas is not defined, we use standard feature.
if (! empty($canvas))
{
	// -----------------------------------------
	// When used with CANVAS
	// -----------------------------------------

	// Load data control
	$objcanvas->doActions($id);
}
else
{
	// Creation utilisateur depuis contact
	if ($_POST["action"] == 'confirm_create_user' && $_POST["confirm"] == 'yes' && $user->rights->user->user->creer)
	{
		// Recuperation contact actuel
		$result = $object->fetch($_GET["id"]);

		if ($result > 0)
		{
			// Creation user
			$nuser = new User($db);
			$result=$nuser->create_from_contact($object,$_POST["login"]);

			if ($result < 0)
			{
				$msg=$nuser->error;
			}
		}
		else
		{
			$msg=$object->error;
		}
	}

	// Creation contact
	if ($_POST["action"] == 'add' && $user->rights->societe->contact->creer)
	{
	    $error=0;

	    $db->begin();

		$object->socid        = $_POST["socid"];

		$object->name         = $_POST["name"];
		$object->firstname    = $_POST["firstname"];
		$object->civilite_id  = $_POST["civilite_id"];
		$object->poste        = $_POST["poste"];
		$object->address      = $_POST["address"];
		$object->cp           = $_POST["cp"];
		$object->ville        = $_POST["ville"];
		$object->fk_pays      = $_POST["pays_id"];
		$object->fk_departement = $_POST["departement_id"];
		$object->email        = $_POST["email"];
		$object->phone_pro    = $_POST["phone_pro"];
		$object->phone_perso  = $_POST["phone_perso"];
		$object->phone_mobile = $_POST["phone_mobile"];
		$object->fax          = $_POST["fax"];
		$object->jabberid     = $_POST["jabberid"];
		$object->priv         = $_POST["priv"];
		$object->note         = $_POST["note"];

        // Note: Correct date should be completed with location to have exact GM time of birth.
        $object->birthday = dol_mktime(0,0,0,$_POST["birthdaymonth"],$_POST["birthdayday"],$_POST["birthdayyear"]);
        $object->birthday_alert = $_POST["birthday_alert"];

		if (! $_POST["name"])
		{
		    $error++;
			array_push($errors,$langs->trans("ErrorFieldRequired",$langs->transnoentities("Lastname").' / '.$langs->transnoentities("Label")));
			$_GET["action"] = $_POST["action"] = 'create';
		}

		if ($_POST["name"])
		{
			$id =  $object->create($user);
			if ($id <= 0)
			{
                $error++;
			    $errors=($object->error?array($object->error):$object->errors);
				$_GET["action"] = $_POST["action"] = 'create';
			}
		}

		if (! $error && $id > 0)
		{
             $db->commit();
             Header("Location: fiche.php?id=".$id);
             exit;
		}
		else
		{
		    $db->rollback();
		}
	}

	if (GETPOST("action") == 'confirm_delete' && GETPOST("confirm") == 'yes' && $user->rights->societe->contact->supprimer)
	{
		$result=$object->fetch($_GET["id"]);

		$object->old_name      = $_POST["old_name"];
		$object->old_firstname = $_POST["old_firstname"];

		$result = $object->delete();
		if ($result > 0)
		{
			Header("Location: index.php");
			exit;
		}
		else
		{
			$mesg=$object->error;
		}
	}

	if ($_POST["action"] == 'update' && ! $_POST["cancel"] && $user->rights->societe->contact->creer)
	{
		if (empty($_POST["name"]))
		{
			$errors=array($langs->trans("ErrorFieldRequired",$langs->transnoentities("Name").' / '.$langs->transnoentities("Label")));
			$error++;
			$_GET["action"] = $_POST["action"] = 'edit';
		}

		if (! sizeof($errors))
		{
			$object->fetch($_POST["contactid"]);

			$object->oldcopy=dol_clone($object);

			$object->old_name      = $_POST["old_name"];
			$object->old_firstname = $_POST["old_firstname"];

			$object->socid         = $_POST["socid"];
			$object->name          = $_POST["name"];
			$object->firstname     = $_POST["firstname"];
			$object->civilite_id   = $_POST["civilite_id"];
			$object->poste         = $_POST["poste"];

			$object->address       = $_POST["address"];
			$object->cp            = $_POST["cp"];
			$object->ville         = $_POST["ville"];
			$object->fk_departement= $_POST["departement_id"];
			$object->fk_pays       = $_POST["pays_id"];

			$object->email         = $_POST["email"];
			$object->phone_pro     = $_POST["phone_pro"];
			$object->phone_perso   = $_POST["phone_perso"];
			$object->phone_mobile  = $_POST["phone_mobile"];
			$object->fax           = $_POST["fax"];
			$object->jabberid      = $_POST["jabberid"];
			$object->priv          = $_POST["priv"];
			$object->note          = $_POST["note"];

			$result = $object->update($_POST["contactid"], $user);

			if ($result > 0)
			{
				$object->old_name='';
				$object->old_firstname='';
			}
			else
			{
				$mesg=$object->error;
			}
		}
	}
}


/*
 *	View
 */

llxHeader('',$langs->trans("Contacts"),'EN:Module_Third_Parties|FR:Module_Tiers|ES:M&oacute;dulo_Empresas');

$form = new Form($db);
$formcompany = new FormCompany($db);

$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

if ($socid > 0)
{
    $objsoc = new Societe($db);
    $objsoc->fetch($socid);
}

if (! empty($canvas))
{
	// -----------------------------------------
	// When used with CANVAS
	// -----------------------------------------

	if (GETPOST("action") == 'create')
	{
		// Set action type
		$objcanvas->setAction(GETPOST("action"));

		// Card header
		$title = $objcanvas->getTitle();
		print_fiche_titre($title);

		// Assign _POST data
		$objcanvas->assign_post();

		// Assign template values
		$objcanvas->assign_values();

		// Show errors
		dol_htmloutput_errors($objcanvas->error,$objcanvas->errors);

		// Display canvas
		$objcanvas->display_canvas();
	}
	else if (GETPOST("id") && GETPOST("action") == 'edit')
	{
		/*
		 * Mode edition
		 */

		// Set action type
		$objcanvas->setAction(GETPOST("action"));

		// Fetch object
		$result=$objcanvas->fetch($id);
		if ($result > 0)
		{
			// Card header
			$objcanvas->showHead();

			if ($_POST["name"])
			{
				// Assign _POST data
				$objcanvas->assign_post();
			}

			// Assign values
			$objcanvas->assign_values();

			// Display canvas
			$objcanvas->display_canvas();
		}
		else
		{
			dol_htmloutput_errors($objcanvas->error,$objcanvas->errors);
		}
	}

	if (GETPOST("id") && GETPOST("action") != 'edit')
	{
		// Set action type
		$objcanvas->setAction('view');

		// Fetch object
		$result=$objcanvas->fetch($id);
		if ($result > 0)
		{
			// Card header
			$objcanvas->showHead();

			// Assign values
			$objcanvas->assign_values();

			//Show errors
			dol_htmloutput_errors($objcanvas->error,$objcanvas->errors);

			// Display canvas
			$objcanvas->display_canvas();

			print show_actions_todo($conf,$langs,$db,$objsoc,$objcanvas->control->object);

			print show_actions_done($conf,$langs,$db,$objsoc,$objcanvas->control->object);
		}
		else
		{
			dol_htmloutput_errors($objcanvas->error,$objcanvas->errors);
		}
	}

}
else
{
	// -----------------------------------------
	// When used in standard mode
	// -----------------------------------------

	/*
	 * Confirmation de la suppression du contact
	 */
	if ($user->rights->societe->contact->supprimer)
	{
		if ($_GET["action"] == 'delete')
		{
			$ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$_GET["id"],$langs->trans("DeleteContact"),$langs->trans("ConfirmDeleteContact"),"confirm_delete",'',0,1);
			if ($ret == 'html') print '<br>';
		}
	}

	/*
	 * Onglets
	 */
	if (GETPOST("id") > 0)
	{
		// Si edition contact deja existant
		$object = new Contact($db);
		$return=$object->fetch(GETPOST("id"), $user);
		if ($return <= 0)
		{
			dol_print_error('',$object->error);
			$_GET["id"]=0;
		}

		/*
		 * Affichage onglets
		 */
		$head = contact_prepare_head($object);

		dol_fiche_head($head, 'card', $langs->trans("Contact"), 0, 'contact');
	}

	if ($user->rights->societe->contact->creer)
	{
		if (GETPOST("action") == 'create')
		{
			/*
			 * Fiche en mode creation
			 */
			$object->fk_departement = $_POST["departement_id"];

			// We set pays_id, pays_code and label for the selected country
			$object->fk_pays=$_POST["pays_id"]?$_POST["pays_id"]:$mysoc->pays_id;
			if ($object->fk_pays)
			{
				$sql = "SELECT code, libelle";
				$sql.= " FROM ".MAIN_DB_PREFIX."c_pays";
				$sql.= " WHERE rowid = ".$object->fk_pays;
				$resql=$db->query($sql);
				if ($resql)
				{
					$obj = $db->fetch_object($resql);
				}
				else
				{
					dol_print_error($db);
				}
				$object->pays_code=$obj->code;
				$object->pays=$obj->libelle;
			}

			print_fiche_titre($langs->trans("AddContact"));

			// Affiche les erreurs
			dol_htmloutput_errors($mesg,$errors);

			if ($conf->use_javascript_ajax)
			{
				print "\n".'<script type="text/javascript" language="javascript">';
				print 'jQuery(document).ready(function () {
							jQuery("#selectpays_id").change(function() {
								document.formsoc.action.value="create";
								document.formsoc.submit();
                        	});
						})';
				print '</script>'."\n";
			}

			print '<br>';
			print '<form method="post" name="formsoc" action="'.$_SERVER["PHP_SELF"].'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="add">';
			print '<table class="border" width="100%">';

			// Name
			print '<tr><td width="20%" class="fieldrequired">'.$langs->trans("Lastname").' / '.$langs->trans("Label").'</td><td width="30%"><input name="name" type="text" size="30" maxlength="80" value="'.(isset($_POST["name"])?$_POST["name"]:$object->name).'"></td>';
			print '<td width="20%">'.$langs->trans("Firstname").'</td><td width="30%"><input name="firstname" type="text" size="30" maxlength="80" value="'.(isset($_POST["firstname"])?$_POST["firstname"]:$object->firstname).'"></td></tr>';

			// Company
			if ($socid > 0)
			{
				print '<tr><td>'.$langs->trans("Company").'</td>';
				print '<td colspan="3">';
				print $objsoc->getNomUrl(1);
				print '</td>';
				print '<input type="hidden" name="socid" value="'.$objsoc->id.'">';
				print '</td></tr>';
			}
			else {
				print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">';
				print $form->select_societes(isset($_POST["socid"])?$_POST["socid"]:'','socid','',1);
				//print $form->select_societes('','socid','');
				//print $langs->trans("ContactNotLinkedToCompany");
				print '</td></tr>';
			}

			// Civility
			print '<tr><td width="15%">'.$langs->trans("UserTitle").'</td><td colspan="3">';
			print $formcompany->select_civilite(isset($_POST["civilite_id"])?$_POST["civilite_id"]:$object->civilite_id);
			print '</td></tr>';

			print '<tr><td>'.$langs->trans("PostOrFunction").'</td><td colspan="3"><input name="poste" type="text" size="50" maxlength="80" value="'.(isset($_POST["poste"])?$_POST["poste"]:$object->poste).'"></td>';

			// Address
			if (($objsoc->typent_code == 'TE_PRIVATE') && dol_strlen(trim($object->address)) == 0) $object->address = $objsoc->address;	// Predefined with third party
			print '<tr><td>'.$langs->trans("Address").'</td><td colspan="3"><textarea class="flat" name="address" cols="70">'.(isset($_POST["address"])?$_POST["address"]:$object->address).'</textarea></td>';

			// Zip / Town
			if (($objsoc->typent_code == 'TE_PRIVATE') && dol_strlen(trim($object->cp)) == 0) $object->cp = $objsoc->cp;			// Predefined with third party
			if (($objsoc->typent_code == 'TE_PRIVATE') && dol_strlen(trim($object->ville)) == 0) $object->ville = $objsoc->ville;	// Predefined with third party
			print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3"><input name="cp" type="text" size="6" maxlength="80" value="'.(isset($_POST["cp"])?$_POST["cp"]:$object->cp).'">&nbsp;';
			print '<input name="ville" type="text" size="20" value="'.(isset($_POST["ville"])?$_POST["ville"]:$object->ville).'" maxlength="80"></td></tr>';

			// Country
			if (dol_strlen(trim($object->fk_pays)) == 0) $object->fk_pays = $objsoc->pays_id;	// Predefined with third party
			print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
			$form->select_pays((isset($_POST["pays_id"])?$_POST["pays_id"]:$object->fk_pays),'pays_id');
			if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
			print '</td></tr>';

			// State
			print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">';
			if ($object->fk_pays)
			{
				$formcompany->select_departement(isset($_POST["departement_id"])?$_POST["departement_id"]:$object->fk_departement,$object->pays_code);
			}
			else
			{
				print $countrynotdefined;
			}
			print '</td></tr>';

			// Phone / Fax
			if (($objsoc->typent_code == 'TE_PRIVATE') && dol_strlen(trim($object->phone_pro)) == 0) $object->phone_pro = $objsoc->tel;	// Predefined with third party
			print '<tr><td>'.$langs->trans("PhonePro").'</td><td><input name="phone_pro" type="text" size="18" maxlength="80" value="'.(isset($_POST["phone_pro"])?$_POST["phone_pro"]:$object->phone_pro).'"></td>';
			print '<td>'.$langs->trans("PhonePerso").'</td><td><input name="phone_perso" type="text" size="18" maxlength="80" value="'.(isset($_POST["phone_perso"])?$_POST["phone_perso"]:$object->phone_perso).'"></td></tr>';

			if (($objsoc->typent_code == 'TE_PRIVATE') && dol_strlen(trim($object->fax)) == 0) $object->fax = $objsoc->fax;	// Predefined with third party
			print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td><input name="phone_mobile" type="text" size="18" maxlength="80" value="'.(isset($_POST["phone_mobile"])?$_POST["phone_mobile"]:$object->phone_mobile).'"></td>';
			print '<td>'.$langs->trans("Fax").'</td><td><input name="fax" type="text" size="18" maxlength="80" value="'.(isset($_POST["fax"])?$_POST["fax"]:$object->fax).'"></td></tr>';

			// EMail
			if (($objsoc->typent_code == 'TE_PRIVATE') && dol_strlen(trim($object->email)) == 0) $object->email = $objsoc->email;	// Predefined with third party
			print '<tr><td>'.$langs->trans("Email").'</td><td colspan="3"><input name="email" type="text" size="50" maxlength="80" value="'.(isset($_POST["email"])?$_POST["email"]:$object->email).'"></td></tr>';

			// Jabberid
			print '<tr><td>Jabberid</td><td colspan="3"><input name="jabberid" type="text" size="50" maxlength="80" value="'.(isset($_POST["jabberid"])?$_POST["jabberid"]:$object->jabberid).'"></td></tr>';

			// Visibility
			print '<tr><td>'.$langs->trans("ContactVisibility").'</td><td colspan="3">';
			$selectarray=array('0'=>$langs->trans("ContactPublic"),'1'=>$langs->trans("ContactPrivate"));
			print $form->selectarray('priv',$selectarray,(isset($_POST["priv"])?$_POST["priv"]:$object->priv),0);
			print '</td></tr>';

			// Note
			print '<tr><td valign="top">'.$langs->trans("Note").'</td><td colspan="3" valign="top"><textarea name="note" cols="70" rows="'.ROWS_3.'">'.(isset($_POST["note"])?$_POST["note"]:$object->note).'</textarea></td></tr>';

            print "</table><br>";


            // Add personnal information
            print_fiche_titre('<div class="comboperso">'.$langs->trans("PersonalInformations").'</div>','','');

            print '<table class="border" width="100%">';

            // Date To Birth
            print '<tr><td width="20%">'.$langs->trans("DateToBirth").'</td><td width="30%">';
            $html=new Form($db);
            if ($object->birthday)
            {
                print $html->select_date($object->birthday,'birthday',0,0,0,"perso");
            }
            else
            {
                print $html->select_date('','birthday',0,0,1,"perso");
            }
            print '</td>';

            print '<td colspan="2">'.$langs->trans("Alert").': ';
            if ($object->birthday_alert)
            {
                print '<input type="checkbox" name="birthday_alert" checked></td>';
            }
            else
            {
                print '<input type="checkbox" name="birthday_alert"></td>';
            }
            print '</tr>';

            print "</table><br>";


            print '<center><input type="submit" class="button" value="'.$langs->trans("Add").'"></center>';

			print "</form>";
		}
		elseif (GETPOST("action") == 'edit' && GETPOST("id"))
		{
			/*
			 * Fiche en mode edition
			 */

			// We set pays_id, and pays_code label of the chosen country
			if (isset($_POST["pays_id"]) || $object->fk_pays)
			{
				$sql = "SELECT code, libelle from ".MAIN_DB_PREFIX."c_pays where rowid = ".(isset($_POST["pays_id"])?$_POST["pays_id"]:$object->fk_pays);
				$resql=$db->query($sql);
				if ($resql)
				{
					$obj = $db->fetch_object($resql);
				}
				else
				{
					dol_print_error($db);
				}
				$object->pays_code=$obj->code;
				$object->pays=$langs->trans("Country".$obj->code)?$langs->trans("Country".$obj->code):$obj->libelle;
			}

			// Affiche les erreurs
			dol_htmloutput_errors($mesg,$errors);

			if ($conf->use_javascript_ajax)
			{
				print '<script type="text/javascript" language="javascript">';
				print 'jQuery(document).ready(function () {
							jQuery("#selectpays_id").change(function() {
								document.formsoc.action.value="edit";
								document.formsoc.submit();
							});
						})';
				print '</script>';
			}

			print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?id='.GETPOST("id").'" name="formsoc">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="id" value="'.GETPOST("id").'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="contactid" value="'.$object->id.'">';
			print '<input type="hidden" name="old_name" value="'.$object->name.'">';
			print '<input type="hidden" name="old_firstname" value="'.$object->firstname.'">';
			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td>'.$langs->trans("Ref").'</td><td colspan="3">';
			print $object->ref;
			print '</td></tr>';

			// Name
			print '<tr><td width="20%" class="fieldrequired">'.$langs->trans("Lastname").' / '.$langs->trans("Label").'</td><td width="30%"><input name="name" type="text" size="20" maxlength="80" value="'.(isset($_POST["name"])?$_POST["name"]:$object->name).'"></td>';
			print '<td width="20%">'.$langs->trans("Firstname").'</td><td width="30%"><input name="firstname" type="text" size="20" maxlength="80" value="'.(isset($_POST["firstname"])?$_POST["firstname"]:$object->firstname).'"></td></tr>';

			// Company
			print '<tr><td>'.$langs->trans("Company").'</td>';
			print '<td colspan="3">';
			print $form->select_societes(isset($_POST["socid"])?$_POST["socid"]:($object->socid?$object->socid:-1),'socid','',1);
			print '</td>';
			print '</tr>';

			// Civility
			print '<tr><td>'.$langs->trans("UserTitle").'</td><td colspan="3">';
			print $formcompany->select_civilite(isset($_POST["civilite_id"])?$_POST["civilite_id"]:$object->civilite_id);
			print '</td></tr>';

			print '<tr><td>'.$langs->trans("PostOrFunction" ).'</td><td colspan="3"><input name="poste" type="text" size="50" maxlength="80" value="'.(isset($_POST["poste"])?$_POST["poste"]:$object->poste).'"></td></tr>';

			// Address
			print '<tr><td>'.$langs->trans("Address").'</td><td colspan="3"><textarea class="flat" name="address" cols="70">'.(isset($_POST["address"])?$_POST["address"]:$object->address).'</textarea></td>';

			// Zip / Town
			print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3"><input name="cp" type="text" size="6" maxlength="80" value="'.(isset($_POST["cp"])?$_POST["cp"]:$object->cp).'">&nbsp;';
			print '<input name="ville" type="text" size="20" value="'.(isset($_POST["ville"])?$_POST["ville"]:$object->ville).'" maxlength="80"></td></tr>';

			// Country
			print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
			$form->select_pays(isset($_POST["pays_id"])?$_POST["pays_id"]:$object->fk_pays,'pays_id');
			if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
			print '</td></tr>';

			// Department
			print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">';
			$formcompany->select_departement($object->fk_departement,$object->pays_code);
			print '</td></tr>';

			// Phone
			print '<tr><td>'.$langs->trans("PhonePro").'</td><td><input name="phone_pro" type="text" size="18" maxlength="80" value="'.(isset($_POST["phone_pro"])?$_POST["phone_pro"]:$object->phone_pro).'"></td>';
			print '<td>'.$langs->trans("PhonePerso").'</td><td><input name="phone_perso" type="text" size="18" maxlength="80" value="'.(isset($_POST["phone_perso"])?$_POST["phone_perso"]:$object->phone_perso).'"></td></tr>';

			print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td><input name="phone_mobile" type="text" size="18" maxlength="80" value="'.(isset($_POST["phone_mobile"])?$_POST["phone_mobile"]:$object->phone_mobile).'"></td>';
			print '<td>'.$langs->trans("Fax").'</td><td><input name="fax" type="text" size="18" maxlength="80" value="'.(isset($_POST["fax"])?$_POST["fax"]:$object->fax).'"></td></tr>';

			// EMail
			print '<tr><td>'.$langs->trans("EMail").'</td><td><input name="email" type="text" size="40" maxlength="80" value="'.(isset($_POST["email"])?$_POST["email"]:$object->email).'"></td>';
			if ($conf->mailing->enabled)
			{
				$langs->load("mails");
				print '<td nowrap>'.$langs->trans("NbOfEMailingsReceived").'</td>';
				print '<td>'.$object->getNbOfEMailings().'</td>';
			}
			else
			{
				print '<td colspan="2">&nbsp;</td>';
			}
			print '</tr>';

			// Jabberid
			print '<tr><td>Jabberid</td><td colspan="3"><input name="jabberid" type="text" size="40" maxlength="80" value="'.(isset($_POST["jabberid"])?$_POST["jabberid"]:$object->jabberid).'"></td></tr>';

			// Visibility
			print '<tr><td>'.$langs->trans("ContactVisibility").'</td><td colspan="3">';
			$selectarray=array('0'=>$langs->trans("ContactPublic"),'1'=>$langs->trans("ContactPrivate"));
			print $form->selectarray('priv',$selectarray,$object->priv,0);
			print '</td></tr>';

			print '<tr><td valign="top">'.$langs->trans("Note").'</td><td colspan="3">';
			print '<textarea name="note" cols="70" rows="'.ROWS_3.'">';
			print isset($_POST["note"])?$_POST["note"]:$object->note;
			print '</textarea></td></tr>';

			$object->load_ref_elements();

			if ($conf->commande->enabled)
			{
				print '<tr><td>'.$langs->trans("ContactForOrders").'</td><td colspan="3">';
				print $object->ref_commande?$object->ref_commande:$langs->trans("NoContactForAnyOrder");
				print '</td></tr>';
			}

			if ($conf->propal->enabled)
			{
				print '<tr><td>'.$langs->trans("ContactForProposals").'</td><td colspan="3">';
				print $object->ref_propal?$object->ref_propal:$langs->trans("NoContactForAnyProposal");
				print '</td></tr>';
			}

			if ($conf->contrat->enabled)
			{
				print '<tr><td>'.$langs->trans("ContactForContracts").'</td><td colspan="3">';
				print $object->ref_contrat?$object->ref_contrat:$langs->trans("NoContactForAnyContract");
				print '</td></tr>';
			}

			if ($conf->facture->enabled)
			{
				print '<tr><td>'.$langs->trans("ContactForInvoices").'</td><td colspan="3">';
				print $object->ref_facturation?$object->ref_facturation:$langs->trans("NoContactForAnyInvoice");
				print '</td></tr>';
			}

			// Login Dolibarr
			print '<tr><td>'.$langs->trans("DolibarrLogin").'</td><td colspan="3">';
			if ($object->user_id)
			{
				$dolibarr_user=new User($db);
				$result=$dolibarr_user->fetch($object->user_id);
				print $dolibarr_user->getLoginUrl(1);
			}
			else print $langs->trans("NoDolibarrAccess");
			print '</td></tr>';

            print '</table><br>';

            print '<center>';
			print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
			print ' &nbsp; ';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</center>';

			print "</form>";
		}
	}

	if (GETPOST("id") && GETPOST("action") != 'edit')
	{
		$objsoc = new Societe($db);

		/*
		 * Fiche en mode visualisation
		 */
		if ($msg)
		{
			$langs->load("errors");
			print '<div class="error">'.$langs->trans($msg).'</div>';
		}

		if ($_GET["action"] == 'create_user')
		{
			// Full firstname and name separated with a dot : firstname.name 
			// TODO add function
			$login=strtolower(dol_string_unaccent($object->prenom)) .'.'. strtolower(dol_string_unaccent($object->nom));
			$login=dol_string_nospecial($login,''); // For special names

			// Create a form array
			$formquestion=array(array('label' => $langs->trans("LoginToCreate"), 'type' => 'text', 'name' => 'login', 'value' => $login));

			$ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$object->id,$langs->trans("CreateDolibarrLogin"),$langs->trans("ConfirmCreateContact"),"confirm_create_user",$formquestion);
			if ($ret == 'html') print '<br>';
		}

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $form->showrefnav($object,'id');
		print '</td></tr>';

		// Name
		print '<tr><td width="20%">'.$langs->trans("Lastname").'</td><td width="30%">'.$object->name.'</td>';
		print '<td width="20%">'.$langs->trans("Firstname").'</td><td width="30%">'.$object->firstname.'</td></tr>';

		// Company
		print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">';
		if ($object->socid > 0)
		{
			$objsoc->fetch($object->socid);
			print $objsoc->getNomUrl(1);
		}
		else
		{
			print $langs->trans("ContactNotLinkedToCompany");
		}
		print '</td></tr>';

		// Civility
		print '<tr><td width="15%">'.$langs->trans("UserTitle").'</td><td colspan="3">';
		print $object->getCivilityLabel();
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("PostOrFunction" ).'</td><td colspan="3">'.$object->poste.'</td>';

		// Address
		print '<tr><td>'.$langs->trans("Address").'</td><td colspan="3">'.nl2br($object->address).'</td></tr>';

		// Zip Town
		print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3">';
		print $object->cp;
		if ($object->cp) print '&nbsp;';
		print $object->ville.'</td></tr>';

		// Country
		print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
		$img=picto_from_langcode($object->pays_code);
		if ($img) print $img.' ';
		print $object->pays;
		print '</td></tr>';

		// Department
		print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">'.$object->departement.'</td>';

		// Phone
		print '<tr><td>'.$langs->trans("PhonePro").'</td><td>'.dol_print_phone($object->phone_pro,$object->pays_code,$object->id,$object->socid,'AC_TEL').'</td>';
		print '<td>'.$langs->trans("PhonePerso").'</td><td>'.dol_print_phone($object->phone_perso,$object->pays_code,$object->id,$object->socid,'AC_TEL').'</td></tr>';

		print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td>'.dol_print_phone($object->phone_mobile,$object->pays_code,$object->id,$object->socid,'AC_TEL').'</td>';
		print '<td>'.$langs->trans("Fax").'</td><td>'.dol_print_phone($object->fax,$object->pays_code,$object->id,$object->socid,'AC_FAX').'</td></tr>';

		// Email
		print '<tr><td>'.$langs->trans("EMail").'</td><td>'.dol_print_email($object->email,$object->id,$object->socid,'AC_EMAIL').'</td>';
		if ($conf->mailing->enabled)
		{
			$langs->load("mails");
			print '<td nowrap>'.$langs->trans("NbOfEMailingsReceived").'</td>';
			print '<td><a href="'.DOL_URL_ROOT.'/comm/mailing/liste.php?filteremail='.urlencode($object->email).'">'.$object->getNbOfEMailings().'</a></td>';
		}
		else
		{
			print '<td colspan="2">&nbsp;</td>';
		}
		print '</tr>';

		// Jabberid
		print '<tr><td>Jabberid</td><td colspan="3">'.$object->jabberid.'</td></tr>';

		print '<tr><td>'.$langs->trans("ContactVisibility").'</td><td colspan="3">';
		print $object->LibPubPriv($object->priv);
		print '</td></tr>';

		print '<tr><td valign="top">'.$langs->trans("Note").'</td><td colspan="3">';
		print nl2br($object->note);
		print '</td></tr>';

		$object->load_ref_elements();

		if ($conf->commande->enabled)
		{
			print '<tr><td>'.$langs->trans("ContactForOrders").'</td><td colspan="3">';
			print $object->ref_commande?$object->ref_commande:$langs->trans("NoContactForAnyOrder");
			print '</td></tr>';
		}

		if ($conf->propal->enabled)
		{
			print '<tr><td>'.$langs->trans("ContactForProposals").'</td><td colspan="3">';
			print $object->ref_propal?$object->ref_propal:$langs->trans("NoContactForAnyProposal");
			print '</td></tr>';
		}

		if ($conf->contrat->enabled)
		{
			print '<tr><td>'.$langs->trans("ContactForContracts").'</td><td colspan="3">';
			print $object->ref_contrat?$object->ref_contrat:$langs->trans("NoContactForAnyContract");
			print '</td></tr>';
		}

		if ($conf->facture->enabled)
		{
			print '<tr><td>'.$langs->trans("ContactForInvoices").'</td><td colspan="3">';
			print $object->ref_facturation?$object->ref_facturation:$langs->trans("NoContactForAnyInvoice");
			print '</td></tr>';
		}

		print '<tr><td>'.$langs->trans("DolibarrLogin").'</td><td colspan="3">';
		if ($object->user_id)
		{
			$dolibarr_user=new User($db);
			$result=$dolibarr_user->fetch($object->user_id);
			print $dolibarr_user->getLoginUrl(1);
		}
		else print $langs->trans("NoDolibarrAccess");
		print '</td></tr>';

		print "</table>";

		print "</div>";

		// Barre d'actions
		if (! $user->societe_id)
		{
			print '<div class="tabsAction">';

			if ($user->rights->societe->contact->creer)
			{
				print '<a class="butAction" href="fiche.php?id='.$object->id.'&amp;action=edit">'.$langs->trans('Modify').'</a>';
			}

			if (! $object->user_id && $user->rights->user->user->creer)
			{
				print '<a class="butAction" href="fiche.php?id='.$object->id.'&amp;action=create_user">'.$langs->trans("CreateDolibarrLogin").'</a>';
			}

			if ($user->rights->societe->contact->supprimer)
			{
				print '<a class="butActionDelete" href="fiche.php?id='.$object->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
			}

			print "</div><br>";
		}

		print show_actions_todo($conf,$langs,$db,$objsoc,$object);

		print show_actions_done($conf,$langs,$db,$objsoc,$object);
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
