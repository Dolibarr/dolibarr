<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 */

/**
        \file       htdocs/contact/fiche.php
        \ingroup    societe
        \brief      Onglet général d'un contact
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/contact.lib.php");

$langs->load("companies");
$langs->load("users");

$user->getrights("societe");
$user->getrights("commercial");


$error = array();
$socid=$_GET["socid"]?$_GET["socid"]:$_POST["socid"];

// Protection quand utilisateur externe
$contactid = isset($_GET["id"])?$_GET["id"]:'';

if ($user->societe_id > 0)
{
    $socid = $user->societe_id;
}

// Protection restriction commercial
if ($contactid && !$user->rights->commercial->client->voir)
{
	$sql = "SELECT sc.fk_soc, sp.fk_soc";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc, ".MAIN_DB_PREFIX."socpeople as sp";
	$sql .= " WHERE sp.rowid = ".$contactid;
	if (!$user->rights->commercial->client->voir && !$user->societe_id > 0)
	{
		$sql .= " AND sc.fk_soc = sp.fk_soc AND sc.fk_user = ".$user->id;
	}
	if ($user->societe_id > 0) $sql .= " AND sp.fk_soc = ".$socid;

	if ( $db->query($sql) )
	{
		if ( $db->num_rows() == 0) accessforbidden();
	}
}


// Creation utilisateur depuis contact
if ($user->rights->user->user->creer)
{
	if ($_GET["action"] == 'create_user')
	{
		// Recuperation contact actuel
		$contact = new Contact($db);
		$result = $contact->fetch($_GET["id"]);

		if ($result > 0)
		{
			// Creation user
			$nuser = new User($db);
			$result=$nuser->create_from_contact($contact);

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
}

// Creation contact
if ($user->rights->societe->contact->creer)
{
  if ($_POST["action"] == 'add')
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
}

if ($user->rights->societe->contact->supprimer)
{
	if ($_POST["action"] == 'confirm_delete' AND $_POST["confirm"] == 'yes')
	{
		$contact = new Contact($db);
		$result=$contact->fetch($_GET["id"]);

		$contact->old_name      = $_POST["old_name"];
		$contact->old_firstname = $_POST["old_firstname"];

		$result = $contact->delete();

		Header("Location: index.php");
		exit;
	}
}

if ($user->rights->societe->contact->creer)
{
	if ($_POST["action"] == 'update' && ! $_POST["cancel"])
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
}


/*
 *
 *
 */

llxHeader();

$form = new Form($db);

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
*
*/
if ($user->rights->societe->contact->supprimer)
{
	if ($_GET["action"] == 'delete')
	{
		$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$_GET["id"],"Supprimer le contact","Êtes-vous sûr de vouloir supprimer ce contact&nbsp;?","confirm_delete");
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
			// On remplit avec le numéro de la société par défaut
			if (strlen(trim($contact->phone_pro)) == 0)
			{
				$contact->phone_pro = $objsoc->tel;
			}

			print '<tr><td>'.$langs->trans("Company").'</td>';
			print '<td colspan="3"><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$socid.'">'.$objsoc->nom.'</a></td>';
			print '<input type="hidden" name="socid" value="'.$objsoc->id.'">';
			print '</td></tr>';
		}
		else {
			print '<tr><td>'.$langs->trans("Company").'</td><td colspan="3">';
			//print $form->select_societes('','socid','');
			print $langs->trans("ContactNotLinkedToCompany");
			print '</td></tr>';
		}

		// Civility
		print '<tr><td width="15%">'.$langs->trans("UserTitle").'</td><td colspan="3">';
		print $form->select_civilite($contact->civilite_id);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("PostOrFunction").'</td><td colspan="3"><input name="poste" type="text" size="50" maxlength="80" value="'.$contact->poste.'"></td>';

		print '<tr><td>'.$langs->trans("Address").'</td><td colspan="3"><input name="address" type="text" size="50" maxlength="80" value="'.$contact->address.'"></td>';

		print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3"><input name="cp" type="text" size="6" maxlength="80" value="'.$contact->cp.'">&nbsp;';
		print '<input name="ville" type="text" size="20" value="'.$contact->ville.'" maxlength="80"></td></tr>';

		print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
		$form->select_pays($contact->fk_pays);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("PhonePro").'</td><td><input name="phone_pro" type="text" size="18" maxlength="80" value="'.$contact->phone_pro.'"></td>';
		print '<td>'.$langs->trans("PhonePerso").'</td><td><input name="phone_perso" type="text" size="18" maxlength="80" value="'.$contact->phone_perso.'"></td></tr>';

		print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td><input name="phone_mobile" type="text" size="18" maxlength="80" value="'.$contact->phone_mobile.'"></td>';
		print '<td>'.$langs->trans("Fax").'</td><td><input name="fax" type="text" size="18" maxlength="80" value="'.$contact->fax.'"></td></tr>';

		print '<tr><td>'.$langs->trans("Email").'</td><td colspan="3"><input name="email" type="text" size="50" maxlength="80" value="'.$contact->email.'"></td></tr>';

		print '<tr><td>Jabberid</td><td colspan="3"><input name="jabberid" type="text" size="50" maxlength="80" value="'.$contact->jabberid.'"></td></tr>';

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
		print $contact->id;
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
		print $form->select_civilite($contact->civilite_id);
		print '</td></tr>';

		print '<tr><td>Poste/Fonction</td><td colspan="3"><input name="poste" type="text" size="50" maxlength="80" value="'.$contact->poste.'"></td></tr>';

		print '<tr><td>'.$langs->trans("Address").'</td><td colspan="3"><input name="address" type="text" size="50" maxlength="80" value="'.$contact->address.'"></td>';

		print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3"><input name="cp" type="text" size="6" maxlength="80" value="'.$contact->cp.'">&nbsp;';
		print '<input name="ville" type="text" size="20" value="'.$contact->ville.'" maxlength="80"></td></tr>';

		print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
		$form->select_pays($contact->fk_pays);
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

		print '<tr><td>Jabberid</td><td colspan="3"><input name="jabberid" type="text" size="40" maxlength="80" value="'.$contact->jabberid.'"></td></tr>';

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
	/*
	* Fiche en mode visualisation
	*
	*/
	if ($msg) print '<div class="error">'.$msg.'</div>';

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
		$objsoc = new Societe($db);
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
	print $form->civilite_name($contact->civilite_id);
	print '</td></tr>';

	print '<tr><td>Poste/Fonction</td><td colspan="3">'.$contact->poste.'</td>';

	print '<tr><td>'.$langs->trans("Address").'</td><td colspan="3">'.$contact->address.'</td></tr>';

	print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3">'.$contact->cp.'&nbsp;';
	print $contact->ville.'</td></tr>';

	print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
	print $contact->pays;
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("PhonePro").'</td><td>'.dolibarr_print_phone($contact->phone_pro,$objsoc->pays_code).'</td>';
	print '<td>'.$langs->trans("PhonePerso").'</td><td>'.dolibarr_print_phone($contact->phone_perso,$objsoc->pays_code).'</td></tr>';

	print '<tr><td>'.$langs->trans("PhoneMobile").'</td><td>'.dolibarr_print_phone($contact->phone_mobile,$objsoc->pays_code).'</td>';
	print '<td>'.$langs->trans("Fax").'</td><td>'.dolibarr_print_phone($contact->fax,$objsoc->pays_code).'</td></tr>';

	print '<tr><td>'.$langs->trans("EMail").'</td><td>';
	if ($contact->email && ! ValidEmail($contact->email))
	{
		print '<font class="error">'.$langs->trans("ErrorBadEMail",$contact->email)."</font>";
	}
	else
	{
		print $contact->email;
	}
	print '</td>';
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

	print '<tr><td>Jabberid</td><td colspan="3">'.$contact->jabberid.'</td></tr>';

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
			print '<a class="butAction" href="fiche.php?id='.$contact->id.'&amp;action=edit">'.$langs->trans('Edit').'</a>';
		}

		if (! $contact->user_id && $user->rights->user->user->creer && $contact->socid > 0)
		{
			print '<a class="butAction" href="fiche.php?id='.$contact->id.'&amp;action=create_user">'.$langs->trans("CreateDolibarrLogin").'</a>';
		}

		if ($user->rights->societe->contact->supprimer)
		{
			print '<a class="butActionDelete" href="fiche.php?id='.$contact->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
		}

		print "</div><br>";
	}


	// Historique des actions sur ce contact
	print_titre($langs->trans("TasksHistoryForThisContact"));
	$histo=array();
	$numaction = 0 ;

	// Recherche histo sur actioncomm
	$sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, a.note, a.percent as percentage,";
	$sql.= " c.code as acode, c.libelle,";
	$sql.= " u.rowid as user_id, u.login";
	$sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u ";
	$sql.= " WHERE fk_contact = ".$contact->id;
	$sql.= " AND u.rowid = a.fk_user_author";
	$sql.= " AND c.id=a.fk_action";
	$sql.= " ORDER BY a.datea DESC, a.id DESC";

	$resql=$db->query($sql);
	if ($resql)
	{
		$i = 0 ;
		$num = $db->num_rows($resql);
		$var=true;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			$histo[$numaction]=array('type'=>'action','id'=>$obj->id,'date'=>$obj->da,'note'=>$obj->note,'percent'=>$obj->percentage,
			'acode'=>$obj->acode,'libelle'=>$obj->libelle,
			'userid'=>$obj->user_id,'login'=>$obj->login);
			$numaction++;
			$i++;
		}
	}
	else
	{
		dolibarr_print_error($db);
	}

	// Recherche histo sur mailing
	$sql = "SELECT m.rowid as id, ".$db->pdate("mc.date_envoi")." as da, m.titre as note, '100' as percentage,";
	$sql.= " 'AC_EMAILING' as acode,";
	$sql.= " u.rowid as user_id, u.login";
	$sql.= " FROM ".MAIN_DB_PREFIX."mailing as m, ".MAIN_DB_PREFIX."mailing_cibles as mc, ".MAIN_DB_PREFIX."user as u ";
	$sql.= " WHERE mc.email = '".addslashes($contact->email)."'";
	$sql.= " AND mc.statut = 1";
	$sql.= " AND u.rowid = m.fk_user_valid";
	$sql.= " AND mc.fk_mailing=m.rowid";
	$sql.= " ORDER BY mc.date_envoi DESC, m.rowid DESC";

	$resql=$db->query($sql);
	if ($resql)
	{
		$i = 0 ;
		$num = $db->num_rows($resql);
		$var=true;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			$histo[$numaction]=array('type'=>'mailing','id'=>$obj->id,'date'=>$obj->da,'note'=>$obj->note,'percent'=>$obj->percentage,
			'acode'=>$obj->acode,'libelle'=>$obj->libelle,
			'userid'=>$obj->user_id,'login'=>$obj->login);
			$numaction++;
			$i++;
		}
	}
	else
	{
		dolibarr_print_error($db);
	}

	// Affichage actions sur contact
	print '<table width="100%" class="noborder">';

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Date").'</td>';
	print '<td>'.$langs->trans("Actions").'</td>';
	print '<td>'.$langs->trans("Comments").'</td>';
	print '<td>'.$langs->trans("Author").'</td>';
	print '<td align="center">'.$langs->trans("Status").'</td>';
	print '</tr>';

	foreach ($histo as $key=>$value)
	{
		$var=!$var;
		print "<tr $bc[$var]>";

		// Date
		print "<td>". dolibarr_print_date($histo[$key]['date'],"dayhour") ."</td>";

		// Action
		print '<td>';
		if ($histo[$key]['type']=='action')
		{
			print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowTask"),"task").' ';
			$transcode=$langs->trans("Action".$histo[$key]['acode']);
			$libelle=($transcode!="Action".$histo[$key]['acode']?$transcode:$histo[$key]['libelle']);
			print dolibarr_trunc($libelle,30);
			print '</a>';
		}
		if ($histo[$key]['type']=='mailing')
		{
			print '<a href="'.DOL_URL_ROOT.'/comm/mailing/fiche.php?id='.$histo[$key]['id'].'">'.img_object($langs->trans("ShowEMailing"),"email").' ';
			$transcode=$langs->trans("Action".$histo[$key]['acode']);
			$libelle=($transcode!="Action".$histo[$key]['acode']?$transcode:'Send mass mailing');
			print dolibarr_trunc($libelle,30);
			print '</a>';
		}
		print '</td>';

		// Note
		print '<td>'.dolibarr_trunc($histo[$key]['note'], 30).'</td>';

		// Author
		print '<td>';
		if ($histo[$key]['login'])
		{
			print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$histo[$key]['userid'].'">'.img_object($langs->trans("ShowUser"),'user').' '.$histo[$key]['login'].'</a>';
		}
		else print "&nbsp;";
		print "</td>";

		// Status/Percent
		print '<td align="right">';
		$actionstatic=new ActionComm($db);
		print $actionstatic->LibStatut($histo[$key]['percent'],5);
		print '</td>';

		print "</tr>\n";
	}
	print "</table>";

	print '<br>';
}

$db->close();

llxFooter('$Date$ - $Revision$');

?>
