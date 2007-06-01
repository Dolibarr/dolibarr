<?php
/* Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/adherents/fiche_subscription.php
        \ingroup    adherent
        \brief      Page d'ajout, edition, suppression d'une fiche adhésion
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/member.lib.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/cotisation.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");

$langs->load("companies");
$langs->load("bills");
$langs->load("members");
$langs->load("users");

$user->getrights('adherent');

$adh = new Adherent($db);
$subscription = new Cotisation($db);
$errmsg='';

$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];
$rowid=isset($_GET["rowid"])?$_GET["rowid"]:$_POST["rowid"];
$typeid=isset($_GET["typeid"])?$_GET["typeid"]:$_POST["typeid"];

if (! $user->rights->adherent->cotisation->lire)
	 accessforbidden();

/*
 * 	Actions
 */

if ($user->rights->adherent->cotisation->creer && $_REQUEST["action"] == 'update' && ! $_POST["cancel"])
{
	// Charge objet actuel
	$result=$subscription->fetch($_POST["rowid"]);
	if ($result > 0)
	{
		// Modifie valeures

		
		$result=$subscription->update($user,0);
		if ($result >= 0 && ! sizeof($subscription->errors))
		{
			Header("Location: fiche_subscription.php?rowid=".$subscription->id);
			exit;
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

if ($user->rights->adherent->cotisation->creer && $_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes')
{
	$result=$subscription->fetch($rowid);
    $result=$subscription->delete($rowid);
    if ($result > 0)
    {
    	Header("Location: card_subscriptions.php?rowid=".$subscription->fk_adherent);
    	exit;
    }
    else
    {
    	$mesg=$adh->error;
    }
}



/*
 * 
 */

llxHeader();


if ($errmsg)
{
    print '<div class="error">'.$errmsg.'</div>';
    print "\n";
}


if ($user->rights->adherent->cotisation->creer && $action == 'edit')
{
	/********************************************
	 *
	 * Fiche en mode edition
	 *
	 ********************************************/



	/*
	 * Affichage onglets
	 */
	$head = member_prepare_head($adh);
	
	dolibarr_fiche_head($head, 'general', $langs->trans("Member"));


	print '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print "<input type=\"hidden\" name=\"action\" value=\"update\">";
	print "<input type=\"hidden\" name=\"rowid\" value=\"$rowid\">";
	print "<input type=\"hidden\" name=\"statut\" value=\"".$adh->statut."\">";

	print '<table class="border" width="100%">';
	
	$htmls = new Form($db);

    // Ref
    print '<tr><td>'.$langs->trans("Ref").'</td><td class="valeur" colspan="2">'.$adh->id.'&nbsp;</td></tr>';
	
	// Nom
	print '<tr><td>'.$langs->trans("Lastname").'</td><td><input type="text" name="nom" size="40" value="'.$adh->nom.'"></td>';
	// Photo
	$rowspan=17;
	$rowspan+=sizeof($adho->attribute_label);
	print '<td rowspan="'.$rowspan.'" valign="top">';
	print '&nbsp;';
	print '</td>';
	print '</tr>';

	// Prenom
	print '<tr><td width="20%">'.$langs->trans("Firstname").'</td><td width="35%"><input type="text" name="prenom" size="40" value="'.$adh->prenom.'"></td>';
	print '</tr>';
	
	// Login
	print '<tr><td>'.$langs->trans("Login").'</td><td><input type="text" name="login" size="40" value="'.$adh->login.'"></td></tr>';
	
	// Password
	print '<tr><td>'.$langs->trans("Password").'</td><td><input type="password" name="pass" size="40" value="'.$adh->pass.'"></td></tr>';

	// Type
	print '<tr><td>'.$langs->trans("Type").'</td><td>';
	$htmls->select_array("type",  $adht->liste_array(), $adh->typeid);
	print "</td></tr>";
	
	// Physique-Moral	
	$morphys["phy"] = $langs->trans("Physical");
	$morphys["mor"] = $langs->trans("Morale");
	print "<tr><td>".$langs->trans("Person")."</td><td>";
	$htmls->select_array("morphy",  $morphys, $adh->morphy);
	print "</td></tr>";
	
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

	// Url photo
	print '<tr><td>URL photo</td><td><input type="text" name="photo" size="40" value="'.$adh->photo.'"></td></tr>';

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
	print ' &nbsp; &nbsp; &nbsp; ';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</td></tr>';

	print '</table>';
	print '</form>';
	
	print '</div>'; 
}

if ($rowid && $action != 'edit')
{
	/* ************************************************************************** */
	/*                                                                            */
	/* Mode affichage                                                             */
	/*                                                                            */
	/* ************************************************************************** */

    $subscription->fetch($rowid);

    $html = new Form($db);

	/*
	 * Affichage onglets
	 */
	$h = 0;
	$head = array();
	
	$head[$h][0] = DOL_URL_ROOT.'/adherents/fiche_subscription.php?rowid='.$subscription->id;
	$head[$h][1] = $langs->trans("SubscriptionCard");
	$head[$h][2] = 'general';
	$h++;

	dolibarr_fiche_head($head, 'general', $langs->trans("Subscription"));

	if ($msg) print '<div class="error">'.$msg.'</div>';

	//$result=$subscription->load_previous_next_id($adh->next_prev_filter);
	//if ($result < 0) dolibarr_print_error($db,$subscription->error);
	//$previous_id = $adh->id_previous?'<a href="'.$_SERVER["PHP_SELF"].'?rowid='.urlencode($adh->id_previous).'">'.img_previous().'</a>':'';
	//$next_id     = $adh->id_next?'<a href="'.$_SERVER["PHP_SELF"].'?rowid='.urlencode($adh->id_next).'">'.img_next().'</a>':'';

    // Confirmation de la suppression de l'adhérent
    if ($action == 'delete')
    {
		//$formquestion=array();
        //$formquestion['text']='<b>'.$langs->trans("ThisWillAlsoDeleteBankRecord").'</b>';
		$text=$langs->trans("ConfirmDeleteSubscription");
		if ($conf->banque->enabled && $conf->global->ADHERENT_BANK_USE) $text.='<br>'.img_warning().' '.$langs->trans("ThisWillAlsoDeleteBankRecord");
		$html->form_confirm($_SERVER["PHP_SELF"]."?rowid=".$subscription->id,$langs->trans("DeleteSubscription"),$text,"confirm_delete",$formquestion);
        print '<br>';
    }

    print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
	print '<td class="valeur" colspan="2">';
	if ($previous_id || $next_id) print '<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">';
	print $subscription->id;
	if ($previous_id || $next_id) print '</td><td class="nobordernopadding" align="center" width="20">'.$previous_id.'</td><td class="nobordernopadding" align="center" width="20">'.$next_id.'</td></tr></table>';
	print '</td></tr>';

    // Date
    print '<tr><td>'.$langs->trans("Date").'</td><td class="valeur">'.dolibarr_print_date($subscription->dateh,'dayhour').'</td>';
    print '</tr>';

    // Amount
    print '<tr><td>'.$langs->trans("Amount").'</td><td class="valeur">'.$subscription->amount.'</td></tr>';

    print "</table>\n";
    print '</form>';
    
    print "</div>\n";

    
    /*
     * Barre d'actions
     *
     */
    print '<div class="tabsAction">';
    
//    if ($user->rights->adherent->cotisation->creer)
//	{
//		print "<a class=\"butAction\" href=\"".$_SERVER["PHP_SELF"]."?rowid=".$subscription->id."&action=edit\">".$langs->trans("Edit")."</a>";
//    }
	
    // Supprimer
    if ($user->rights->adherent->cotisation->creer)
    {
        print "<a class=\"butActionDelete\" href=\"".$_SERVER["PHP_SELF"]."?rowid=".$subscription->id."&action=delete\">".$langs->trans("Delete")."</a>\n";
    }
        
    print '</div>';
    print "<br>\n";
    
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
