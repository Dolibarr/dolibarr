<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 *       \brief      Onglet g�n�ral d'un contact
 *       \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/contact.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/html.formcompany.class.php");

$langs->load("companies");
$langs->load("users");

$error = array();
$socid=$_GET["socid"]?$_GET["socid"]:$_POST["socid"];

// Security check
$contactid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contact', $contactid, 'socpeople');


/*
*	Actions
*/

// Creation utilisateur depuis contact
if ($_POST["action"] == 'confirm_create_user' && $_POST["confirm"] == 'yes' && $user->rights->user->user->creer)
{
	// Recuperation contact actuel
	$contact = new Contact($db);
	$result = $contact->fetch($_GET["id"]);

	if ($result > 0)
	{
		// Creation user
		$nuser = new User($db);
		$result=$nuser->create_from_contact($contact,$_POST["login"]);

		if ($result < 0)
		{
			$msg=$nuser->error;
		}
	}
	else
	{
		$msg=$contact->error;
	}
}

// Creation contact
if ($_POST["action"] == 'add' && $user->rights->societe->contact->creer)
{
    $contact = new Contact($db);

    $contact->socid        = $_POST["socid"];

    $contact->name         = $_POST["name"];
    $contact->firstname    = $_POST["firstname"];
    $contact->civilite_id  = $_POST["civilite_id"];
    $contact->poste        = $_POST["poste"];
    $contact->address      = $_POST["address"];
    $contact->cp           = $_POST["cp"];
    $contact->ville        = $_POST["ville"];
    $contact->fk_pays      = $_POST["pays_id"];
    $contact->email        = $_POST["email"];
    $contact->phone_pro    = $_POST["phone_pro"];
    $contact->phone_perso  = $_POST["phone_perso"];
    $contact->phone_mobile = $_POST["phone_mobile"];
    $contact->fax          = $_POST["fax"];
    $contact->jabberid     = $_POST["jabberid"];
    $contact->priv         = $_POST["priv"];

    $contact->note         = $_POST["note"];

    if (! $_POST["name"])
    {
        array_push($error,$langs->trans("ErrorFieldRequired",$langs->trans("Lastname")));
        $_GET["action"]="create";
    }

    if ($_POST["name"])
    {
        $id =  $contact->create($user);
        if ($id > 0)
        {
              Header("Location: fiche.php?id=".$id);
              exit;
        }
		else
		{
			$error=array($contact->error);
			$_GET["action"] = 'create';
		}
	}
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes' && $user->rights->societe->contact->supprimer)
{
	$contact = new Contact($db);
	$result=$contact->fetch($_GET["id"]);

	$contact->old_name      = $_POST["old_name"];
	$contact->old_firstname = $_POST["old_firstname"];

	$result = $contact->delete();

	Header("Location: index.php");
	exit;
}

if ($_POST["action"] == 'update' && ! $_POST["cancel"] && $user->rights->societe->contact->creer)
{
	$contact = new Contact($db);

	$contact->old_name      = $_POST["old_name"];
	$contact->old_firstname = $_POST["old_firstname"];

	$contact->socid         = $_POST["socid"];
	$contact->name          = $_POST["name"];
	$contact->firstname     = $_POST["firstname"];
	$contact->civilite_id   = $_POST["civilite_id"];
	$contact->poste         = $_POST["poste"];

	$contact->address       = $_POST["address"];
	$contact->cp            = $_POST["cp"];
	$contact->ville         = $_POST["ville"];
	$contact->fk_pays       = $_POST["pays_id"];

	$contact->email         = $_POST["email"];
	$contact->phone_pro     = $_POST["phone_pro"];
	$contact->phone_perso   = $_POST["phone_perso"];
	$contact->phone_mobile  = $_POST["phone_mobile"];
	$contact->fax           = $_POST["fax"];
	$contact->jabberid      = $_POST["jabberid"];
	$contact->priv          = $_POST["priv"];

	$contact->note          = $_POST["note"];

	$result = $contact->update($_POST["contactid"], $user);

	if ($result > 0)
	{
		$contact->old_name='';
		$contact->old_firstname='';
	}
	else
	{
		$error = $contact->error;
	}
}


/*
*	View
*/

llxHeader();

$form = new Form($db);
$formcompany = new FormCompany($db);

if ($socid)
{
    $objsoc = new Societe($db);
    $objsoc->fetch($socid);
}


/*
* Onglets
*/
if ($_GET["id"] > 0)
{
	// Si edition contact deja existant
	$contact = new Contact($db);
	$return=$contact->fetch($_GET["id"], $user);
	if ($return <= 0)
	{
		dolibarr_print_error('',$contact->error);
		$_GET["id"]=0;
	}

	/*
	 * Affichage onglets
	 */
	$head = contact_prepare_head($contact);

	dolibarr_fiche_head($head, 'general', $langs->trans("Contact"));
}


/*
* Confirmation de la suppression du contact
*/
if ($user->rights->societe->contact->supprimer)
{
	if ($_GET["action"] == 'delete')
	{
		$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$_GET["id"],$langs->trans("DeleteContact"),$langs->trans("ConfirmDeleteContact"),"confirm_delete");
		print '<br>';
	}
}

if ($user->rights->societe->contact->creer)
{
	if ($_GET["action"] == 'create')
	{
		/*
		* Fiche en mode creation
		*
		*/
		print_fiche_titre($langs->trans("AddContact"));

		// Affiche les erreurs
		if (sizeof($error))
		{
			print "<div class='error'>";
			print join("<br>",$error);
			print "</div>\n";
		}

		print '<br>';
		print '<form method="post" action="fiche.php">';
		print '<input type="hidden" name="action" value="add">';
		print '<table class="border" width="100%">';

		// Name
		print '<tr><td width="15%">'.$langs->trans("Lastname").'</td><td><input name="name" type="text" size="20" maxlength="80" value="'.$contact->name.'"></td>';
		print '<td width="20%">'.$langs->trans("Firstname").'</td><td width="25%"><input name="firstname" type="text" size="15" maxlength="80" value="'.$contact->firstname.'"></td></tr>';

		// Company
		if ($socid)
		{
			print '<tr><td>'.$langs->trans("Company").'</td>';
			print '<td colspan="3"><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$socid.'">'.$objsoc->nom.'</a></td>';
			print '<input type="hidden" name="socid" value="'.$objsoc->id.'">';
			print '</td></tr>';
		}
		else {
			print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">';
            print $form->select_societes('','socid','',1);
			//print $form->select_societes('','socid','');
			//print $langs->trans("ContactNotLinkedToCompany");
			print '</td></tr>';
		}

		// Civility
		print '<tr><td width="15%">'.$langs->trans("UserTitle").'</td><td colspan="3">';
		print $formcompany->select_civilite($contact->civilite_id);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("PostOrFunction").'</td><td colspan="3"><input name="poste" type="text" size="50" maxlength="80" value="'.$contact->poste.'"></td>';

		// Address
		if (($objsoc->typent_code == 'TE_PRIVATE') && strlen(trim($contact->address)) == 0) $contact->address = $objsoc->adresse;	// Predefined with third party
		print '<tr><td>'.$langs->trans("Address").'</td><td colspan="3"><textarea class="flat" name="address" cols="70">'.$contact->address.'</textarea></td>';

		if (($objsoc->typent_code == 'TE_PRIVATE') && strlen(trim($contact->cp)) == 0) $contact->cp = $objsoc->cp;			// Predefined with third party
		if (($objsoc->typent_code == 'TE_PRIVATE') && strlen(trim($contact->ville)) == 0) $contact->ville = $objsoc->ville;	// Predefined with third party
		print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3"><input name="cp" type="text" size="6" maxlength="80" value="'.$contact->cp.'">&nbsp;';
		print '<input name="ville" type="text" size="20" value="'.$contact->ville.'" maxlength="80"></td></tr>';

		if (strlen(trim($contact->fk_pays)) == 0) $contact->fk_pays = $objsoc->pays_id;	// Predefined with third party
		print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
		$form->select_pays($contact->fk_pays);
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
		print '</td></tr>';

		if (($objsoc->typent_code == 'TE_PRIVATE') && strlen(trim($contact->phone_pro)) == 0) $contact->phone_pro = $objsoc->tel;	// Predefined with third party
		print '<tr><td>'.$langs->trans("PhonePro").'</td><td><input name="phone_pro" type="text" size="18" maxlength="80" value="'.$contact->phone_pro.'"></td>';
		print '<td>'.$langs->trans("PhonePerso").'</td><td><input name="phone_perso" type="text" size="18" maxlength="80" value="'.$contact->phone_perso.'"></td></tr>';

		if (($objsoc->typent_code == 'TE_PRIVATE') && strlen(trim($contact->fax)) == 0) $contact->fax = $objsoc->fax;	// Predefined with third party
		print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td><input name="phone_mobile" type="text" size="18" maxlength="80" value="'.$contact->phone_mobile.'"></td>';
		print '<td>'.$langs->trans("Fax").'</td><td><input name="fax" type="text" size="18" maxlength="80" value="'.$contact->fax.'"></td></tr>';

		// EMail
		if (($objsoc->typent_code == 'TE_PRIVATE') && strlen(trim($contact->email)) == 0) $contact->email = $objsoc->email;	// Predefined with third party
		print '<tr><td>'.$langs->trans("Email").'</td><td colspan="3"><input name="email" type="text" size="50" maxlength="80" value="'.$contact->email.'"></td></tr>';

		// Jabberid
		print '<tr><td>Jabberid</td><td colspan="3"><input name="jabberid" type="text" size="50" maxlength="80" value="'.$contact->jabberid.'"></td></tr>';

		// Visibility
		print '<tr><td>'.$langs->trans("ContactVisibility").'</td><td colspan="3">';
		$selectarray=array('0'=>$langs->trans("ContactPublic"),'1'=>$langs->trans("ContactPrivate"));
		$form->select_array('priv',$selectarray,$contact->priv,0);
		print '</td></tr>';

		// Note
		print '<tr><td valign="top">'.$langs->trans("Note").'</td><td colspan="3" valign="note"><textarea name="note" cols="70" rows="'.ROWS_3.'">'.$contact->note.'</textarea></td></tr>';

		print '<tr><td align="center" colspan="4"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td></tr>';
		print "</table><br>";

		print "</form>";
	}
	elseif ($_GET["action"] == 'edit' && $_GET["id"])
	{
		/*
		* Fiche en mode edition
		*
		*/

		// Affiche les erreurs
		if (sizeof($error))
		{
			print "<div class='error'>";
			print join("<br>",$error);
			print "</div>\n";
		}

		print '<form method="post" action="fiche.php?id='.$_GET["id"].'">';
		print '<input type="hidden" name="id" value="'.$_GET["id"].'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="contactid" value="'.$contact->id.'">';
		print '<input type="hidden" name="old_name" value="'.$contact->name.'">';
		print '<input type="hidden" name="old_firstname" value="'.$contact->firstname.'">';
		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td>'.$langs->trans("Ref").'</td><td colspan="3">';
		print $contact->ref;
		print '</td></tr>';

		// Name
		print '<tr><td>'.$langs->trans("Lastname").'</td><td><input name="name" type="text" size="20" maxlength="80" value="'.$contact->name.'"></td>';
		print '<td width="20%">'.$langs->trans("Firstname").'</td><td width="25%"><input name="firstname" type="text" size="20" maxlength="80" value="'.$contact->firstname.'"></td></tr>';

		// Company
		print '<tr><td width="20%">'.$langs->trans("Company").'</td>';
		print '<td colspan="3">';
		print $form->select_societes($contact->socid?$contact->socid:-1,'socid','',1);
		print '</td>';
		print '</tr>';

		// Civility
		print '<tr><td>'.$langs->trans("UserTitle").'</td><td colspan="3">';
		print $formcompany->select_civilite($contact->civilite_id);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("PostOrFunction" ).'</td><td colspan="3"><input name="poste" type="text" size="50" maxlength="80" value="'.$contact->poste.'"></td></tr>';

		// Address
		print '<tr><td>'.$langs->trans("Address").'</td><td colspan="3"><textarea class="flat" name="address" cols="70">'.$contact->address.'</textarea></td>';

		print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3"><input name="cp" type="text" size="6" maxlength="80" value="'.$contact->cp.'">&nbsp;';
		print '<input name="ville" type="text" size="20" value="'.$contact->ville.'" maxlength="80"></td></tr>';

		print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
		$form->select_pays($contact->fk_pays);
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("PhonePro").'</td><td><input name="phone_pro" type="text" size="18" maxlength="80" value="'.$contact->phone_pro.'"></td>';
		print '<td>'.$langs->trans("PhonePerso").'</td><td><input name="phone_perso" type="text" size="18" maxlength="80" value="'.$contact->phone_perso.'"></td></tr>';

		print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td><input name="phone_mobile" type="text" size="18" maxlength="80" value="'.$contact->phone_mobile.'"></td>';
		print '<td>'.$langs->trans("Fax").'</td><td><input name="fax" type="text" size="18" maxlength="80" value="'.$contact->fax.'"></td></tr>';

		print '<tr><td>'.$langs->trans("EMail").'</td><td><input name="email" type="text" size="40" maxlength="80" value="'.$contact->email.'"></td>';
		if ($conf->mailing->enabled)
		{
			$langs->load("mails");
			print '<td nowrap>'.$langs->trans("NbOfEMailingsReceived").'</td>';
			print '<td>'.$contact->getNbOfEMailings().'</td>';
		}
		else
		{
			print '<td colspan="2">&nbsp;</td>';
		}
		print '</tr>';

		// Jabberid
		print '<tr><td>Jabberid</td><td colspan="3"><input name="jabberid" type="text" size="40" maxlength="80" value="'.$contact->jabberid.'"></td></tr>';

		// Visibility
		print '<tr><td>'.$langs->trans("ContactVisibility").'</td><td colspan="3">';
		$selectarray=array('0'=>$langs->trans("ContactPublic"),'1'=>$langs->trans("ContactPrivate"));
		$form->select_array('priv',$selectarray,$contact->priv,0);
		print '</td></tr>';

		print '<tr><td valign="top">'.$langs->trans("Note").'</td><td colspan="3">';
		print '<textarea name="note" cols="70" rows="'.ROWS_3.'">';
		print $contact->note;
		print '</textarea></td></tr>';

		$contact->load_ref_elements();

		if ($conf->commande->enabled)
		{
			print '<tr><td>'.$langs->trans("ContactForOrders").'</td><td colspan="3">';
			print $contact->ref_commande?$contact->ref_commande:$langs->trans("NoContactForAnyOrder");
			print '</td></tr>';
		}

		if ($conf->propal->enabled)
		{
			print '<tr><td>'.$langs->trans("ContactForProposals").'</td><td colspan="3">';
			print $contact->ref_propal?$contact->ref_propal:$langs->trans("NoContactForAnyProposal");
			print '</td></tr>';
		}

		if ($conf->contrat->enabled)
		{
			print '<tr><td>'.$langs->trans("ContactForContracts").'</td><td colspan="3">';
			print $contact->ref_contrat?$contact->ref_contrat:$langs->trans("NoContactForAnyContract");
			print '</td></tr>';
		}

		if ($conf->facture->enabled)
		{
			print '<tr><td>'.$langs->trans("ContactForInvoices").'</td><td colspan="3">';
			print $contact->ref_facturation?$contact->ref_facturation:$langs->trans("NoContactForAnyInvoice");
			print '</td></tr>';
		}

		// Login Dolibarr
		print '<tr><td>'.$langs->trans("DolibarrLogin").'</td><td colspan="3">';
		if ($contact->user_id)
		{
			$dolibarr_user=new User($db);
			$dolibarr_user->id=$contact->user_id;
			$result=$dolibarr_user->fetch();
			print $dolibarr_user->getLoginUrl(1);
		}
		else print $langs->trans("NoDolibarrAccess");
		print '</td></tr>';

		print '<tr><td colspan="4" align="center">';
		print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
		print ' &nbsp; ';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</td></tr>';
		print '</table>';

		print "</form>";
	}
}

if ($_GET["id"] && $_GET["action"] != 'edit')
{
	$objsoc = new Societe($db);

	/*
	* Fiche en mode visualisation
	*
	*/
	if ($msg)
	{
		$langs->load("errors");
		print '<div class="error">'.$langs->trans($msg).'</div>';
	}

	if ($_GET["action"] == 'create_user')
	{
		$login=strtolower(substr($contact->prenom, 0, 4)) . strtolower(substr($contact->nom, 0, 4));

		// Create a form array
		$formquestion=array(
		array('label' => $langs->trans("LoginToCreate"), 'type' => 'text', 'name' => 'login', 'value' => $login));

		$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$contact->id,$langs->trans("CreateDolibarrLogin"),$langs->trans("ConfirmCreateContact"),"confirm_create_user",$formquestion);
		print '<br>';
	}

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td width="20%">'.$langs->trans("Ref").'</td><td colspan="3">';
	print $form->showrefnav($contact,'id');
	print '</td></tr>';

	// Name
	print '<tr><td>'.$langs->trans("Lastname").'</td><td>'.$contact->name.'</td>';
	print '<td>'.$langs->trans("Firstname").'</td><td width="25%">'.$contact->firstname.'</td></tr>';

	// Company
	if ($contact->socid > 0)
	{
		$objsoc->fetch($contact->socid);

		print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">'.$objsoc->getNomUrl(1).'</td></tr>';
	}
	else
	{
		print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">';
		print $langs->trans("ContactNotLinkedToCompany");
		print '</td></tr>';
	}

	// Civility
	print '<tr><td width="15%">'.$langs->trans("UserTitle").'</td><td colspan="3">';
	print $contact->getCivilityLabel();
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("PostOrFunction" ).'</td><td colspan="3">'.$contact->poste.'</td>';

	// Address
	print '<tr><td>'.$langs->trans("Address").'</td><td colspan="3">'.nl2br($contact->address).'</td></tr>';

	print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3">'.$contact->cp.'&nbsp;';
	print $contact->ville.'</td></tr>';

	print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
	print $contact->pays;
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("PhonePro").'</td><td>'.dol_print_phone($contact->phone_pro,$contact->pays_code,$contact->id,$contact->socid,'AC_TEL').'</td>';
	print '<td>'.$langs->trans("PhonePerso").'</td><td>'.dol_print_phone($contact->phone_perso,$contact->pays_code,$contact->id,$contact->socid,'AC_TEL').'</td></tr>';

	print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td>'.dol_print_phone($contact->phone_mobile,$contact->pays_code,$contact->id,$contact->socid,'AC_TEL').'</td>';
	print '<td>'.$langs->trans("Fax").'</td><td>'.dol_print_phone($contact->fax,$contact->pays_code,$contact->id,$contact->socid,'AC_FAX').'</td></tr>';

	print '<tr><td>'.$langs->trans("EMail").'</td><td>'.dol_print_email($contact->email,$contact->id,$contact->socid,'AC_EMAIL').'</td>';
	if ($conf->mailing->enabled)
	{
		$langs->load("mails");
		print '<td nowrap>'.$langs->trans("NbOfEMailingsReceived").'</td>';
		print '<td>'.$contact->getNbOfEMailings().'</td>';
	}
	else
	{
		print '<td colspan="2">&nbsp;</td>';
	}
	print '</tr>';

	// Jabberid
	print '<tr><td>Jabberid</td><td colspan="3">'.$contact->jabberid.'</td></tr>';

	print '<tr><td>'.$langs->trans("ContactVisibility").'</td><td colspan="3">';
	print $contact->LibPubPriv($contact->priv);
	print '</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("Note").'</td><td colspan="3">';
	print nl2br($contact->note);
	print '</td></tr>';

	$contact->load_ref_elements();

	if ($conf->commande->enabled)
	{
		print '<tr><td>'.$langs->trans("ContactForOrders").'</td><td colspan="3">';
		print $contact->ref_commande?$contact->ref_commande:$langs->trans("NoContactForAnyOrder");
		print '</td></tr>';
	}

	if ($conf->propal->enabled)
	{
		print '<tr><td>'.$langs->trans("ContactForProposals").'</td><td colspan="3">';
		print $contact->ref_propal?$contact->ref_propal:$langs->trans("NoContactForAnyProposal");
		print '</td></tr>';
	}

	if ($conf->contrat->enabled)
	{
		print '<tr><td>'.$langs->trans("ContactForContracts").'</td><td colspan="3">';
		print $contact->ref_contrat?$contact->ref_contrat:$langs->trans("NoContactForAnyContract");
		print '</td></tr>';
	}

	if ($conf->facture->enabled)
	{
		print '<tr><td>'.$langs->trans("ContactForInvoices").'</td><td colspan="3">';
		print $contact->ref_facturation?$contact->ref_facturation:$langs->trans("NoContactForAnyInvoice");
		print '</td></tr>';
	}

	print '<tr><td>'.$langs->trans("DolibarrLogin").'</td><td colspan="3">';
	if ($contact->user_id)
	{
		$dolibarr_user=new User($db);
		$dolibarr_user->id=$contact->user_id;
		$result=$dolibarr_user->fetch();
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
			print '<a class="butAction" href="fiche.php?id='.$contact->id.'&amp;action=edit">'.$langs->trans('Modify').'</a>';
		}

		if (! $contact->user_id && $user->rights->user->user->creer)
		{
			print '<a class="butAction" href="fiche.php?id='.$contact->id.'&amp;action=create_user">'.$langs->trans("CreateDolibarrLogin").'</a>';
		}

		if ($user->rights->societe->contact->supprimer)
		{
			print '<a class="butActionDelete" href="fiche.php?id='.$contact->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
		}

		print "</div><br>";
	}


	print show_actions_todo($conf,$langs,$db,$objsoc,$contact);

	print show_actions_done($conf,$langs,$db,$objsoc,$contact);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
