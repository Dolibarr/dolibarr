<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/adherents/fiche_subscription.php
 *       \ingroup    member
 *       \brief      Page to add/edit/remove a member subscription
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/cotisation.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("companies");
$langs->load("bills");
$langs->load("members");
$langs->load("users");

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
		$db->begin();

		$errmsg='';

		if ($subscription->fk_bank)
		{
			$accountline=new AccountLine($db);
			$result=$accountline->fetch($subscription->fk_bank);

			// If transaction consolidated
			if ($accountline->rappro)
			{
				$errmsg=$langs->trans("SubscriptionLinkedToConciliatedTransaction");
			}
			else
			{
				$accountline->datev=dol_mktime($_POST['datesubhour'], $_POST['datesubmin'], 0, $_POST['datesubmonth'], $_POST['datesubday'], $_POST['datesubyear']);
				$accountline->dateo=dol_mktime($_POST['datesubhour'], $_POST['datesubmin'], 0, $_POST['datesubmonth'], $_POST['datesubday'], $_POST['datesubyear']);
				$accountline->amount=$_POST["amount"];
				$result=$accountline->update($user);
				if ($result < 0)
				{
					$errmsg=$accountline->error;
				}
			}
		}

		if (! $errmsg)
		{
			// Modifie valeures
			$subscription->dateh=dol_mktime($_POST['datesubhour'], $_POST['datesubmin'], 0, $_POST['datesubmonth'], $_POST['datesubday'], $_POST['datesubyear']);
			$subscription->datef=dol_mktime($_POST['datesubendhour'], $_POST['datesubendmin'], 0, $_POST['datesubendmonth'], $_POST['datesubendday'], $_POST['datesubendyear']);
			$subscription->note=$_POST["note"];
			$subscription->amount=$_POST["amount"];
			//print 'datef='.$subscription->datef.' '.$_POST['datesubendday'];

			$result=$subscription->update($user);
			if ($result >= 0 && ! count($subscription->errors))
			{
				$db->commit();

				header("Location: fiche_subscription.php?rowid=".$subscription->id);
				exit;
			}
			else
			{
				$db->rollback();

			    if ($subscription->error)
				{
					$errmsg=$subscription->error;
				}
				else
				{
					foreach($subscription->errors as $error)
					{
						if ($errmsg) $errmsg.='<br>';
						$errmsg.=$error;
					}
				}
				$action='';
			}
		}
		else
		{
			$db->rollback();
		}
	}
}

if ($_REQUEST["action"] == 'confirm_delete' && $_REQUEST["confirm"] == 'yes' && $user->rights->adherent->cotisation->creer)
{
	$result=$subscription->fetch($rowid);
    $result=$subscription->delete($user);
    if ($result > 0)
    {
    	header("Location: card_subscriptions.php?rowid=".$subscription->fk_adherent);
    	exit;
    }
    else
    {
    	$mesg=$adh->error;
    }
}



/*
 * View
 */

llxHeader('',$langs->trans("SubscriptionCard"),'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

$form = new Form($db);


dol_htmloutput_errors($errmsg);


if ($user->rights->adherent->cotisation->creer && $action == 'edit')
{
	/********************************************
	 *
	 * Fiche en mode edition
	 *
	 ********************************************/

    $subscription->fetch($rowid);
	$result=$adh->fetch($subscription->fk_adherent);

	/*
	 * Affichage onglets
	 */
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/adherents/fiche_subscription.php?rowid='.$subscription->id;
	$head[$h][1] = $langs->trans("SubscriptionCard");
	$head[$h][2] = 'general';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/adherents/info_subscription.php?rowid='.$subscription->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	print '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"update\">";
	print "<input type=\"hidden\" name=\"rowid\" value=\"$rowid\">";
	print "<input type=\"hidden\" name=\"fk_bank\" value=\"".$subscription->fk_bank."\">";
	
	dol_fiche_head($head, 'general', $langs->trans("Subscription"), 0, 'payment');

	print "\n";
	print '<table class="border" width="100%">';

    $linkback = '<a href="'.DOL_URL_ROOT.'/adherents/cotisations.php">'.$langs->trans("BackToList").'</a>';

    // Ref
    print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
	print '<td class="valeur" colspan="3">';
	print $form->showrefnav($subscription, 'rowid', $linkback, 1);
	print '</td></tr>';	

    // Member
	$adh->ref=$adh->getFullName($langs);
    print '<tr>';
	print '<td>'.$langs->trans("Member").'</td><td class="valeur" colspan="3">'.$adh->getNomUrl(1,0,'subscription').'</td>';
    print '</tr>';

    // Date start subscription
    print '<tr><td>'.$langs->trans("DateSubscription").'</td><td class="valeur" colspan="2">';
	$form->select_date($subscription->dateh,'datesub',1,1,0,'update',1);
	print '</td>';
    print '</tr>';

    // Date end subscription
    print '<tr><td>'.$langs->trans("DateEndSubscription").'</td><td class="valeur" colspan="2">';
	$form->select_date($subscription->datef,'datesubend',0,0,0,'update',1);
	print '</td>';
    print '</tr>';

    // Amount
    print '<tr><td>'.$langs->trans("Amount").'</td><td class="valeur" colspan="2">';
	print '<input type="text" class="flat" size="10" name="amount" value="'.price($subscription->amount).'"></td></tr>';

    // Label
    print '<tr><td>'.$langs->trans("Label").'</td><td class="valeur" colspan="2">';
	print '<input type="text" class="flat" size="60" name="note" value="'.$subscription->note.'"></td></tr>';

	// Bank line
	if (! empty($conf->banque->enabled))
	{
		if ($conf->global->ADHERENT_BANK_USE || $subscription->fk_bank)
	    {
    		print '<tr><td>'.$langs->trans("BankTransactionLine").'</td><td class="valeur" colspan="2">';
			if ($subscription->fk_bank)
			{
	    		$bankline=new AccountLine($db);
		    	$result=$bankline->fetch($subscription->fk_bank);
				print $bankline->getNomUrl(1,0,'showall');
			}
			else
			{
				print $langs->trans("NoneF");
			}
	    	print '</td></tr>';
	    }
	}

	print '</table>';
	
	dol_fiche_end();
	
    print '<div class="center">';
    print '<input type="submit" class="button" name="submit" value="'.$langs->trans("Save").'">';
	print ' &nbsp; &nbsp; &nbsp; ';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
    print '</div>';
    
	print '</form>';
	print "\n";
}

if ($rowid && $action != 'edit')
{
	/* ************************************************************************** */
	/*                                                                            */
	/* Mode affichage                                                             */
	/*                                                                            */
	/* ************************************************************************** */

    $result=$subscription->fetch($rowid);
	$result=$adh->fetch($subscription->fk_adherent);

	/*
	 * Affichage onglets
	 */
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/adherents/fiche_subscription.php?rowid='.$subscription->id;
	$head[$h][1] = $langs->trans("SubscriptionCard");
	$head[$h][2] = 'general';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/adherents/info_subscription.php?rowid='.$subscription->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	dol_fiche_head($head, 'general', $langs->trans("Subscription"), '', 'payment');

    // Confirmation to delete subscription
    if ($action == 'delete')
    {
		//$formquestion=array();
        //$formquestion['text']='<b>'.$langs->trans("ThisWillAlsoDeleteBankRecord").'</b>';
		$text=$langs->trans("ConfirmDeleteSubscription");
		if (! empty($conf->banque->enabled) && ! empty($conf->global->ADHERENT_BANK_USE)) $text.='<br>'.img_warning().' '.$langs->trans("ThisWillAlsoDeleteBankRecord");
		print $form->formconfirm($_SERVER["PHP_SELF"]."?rowid=".$subscription->id,$langs->trans("DeleteSubscription"),$text,"confirm_delete",$formquestion,0,1);
    }

    print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<table class="border" width="100%">';

    $linkback = '<a href="'.DOL_URL_ROOT.'/adherents/cotisations.php">'.$langs->trans("BackToList").'</a>';

    // Ref
    print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
	print '<td class="valeur" colspan="3">';
	print $form->showrefnav($subscription, 'rowid', $linkback, 1);
	print '</td></tr>';

    // Member
	$adh->ref=$adh->getFullName($langs);
    print '<tr>';
	print '<td>'.$langs->trans("Member").'</td><td class="valeur" colspan="3">'.$adh->getNomUrl(1,0,'subscription').'</td>';
    print '</tr>';

    // Date record
    /*print '<tr>';
	print '<td>'.$langs->trans("DateSubscription").'</td><td class="valeur" colspan="3">'.dol_print_date($subscription->datec,'dayhour').'</td>';
    print '</tr>';*/

    // Date subscription
    print '<tr>';
	print '<td>'.$langs->trans("DateSubscription").'</td><td class="valeur" colspan="3">'.dol_print_date($subscription->dateh,'day').'</td>';
    print '</tr>';

    // Date end subscription
    print '<tr>';
	print '<td>'.$langs->trans("DateEndSubscription").'</td><td class="valeur" colspan="3">'.dol_print_date($subscription->datef,'day').'</td>';
    print '</tr>';

    // Amount
    print '<tr><td>'.$langs->trans("Amount").'</td><td class="valeur" colspan="3">'.price($subscription->amount).'</td></tr>';

    // Amount
    print '<tr><td>'.$langs->trans("Label").'</td><td class="valeur" colspan="3">'.$subscription->note.'</td></tr>';

    // Bank line
	if (! empty($conf->banque->enabled))
	{
		if ($conf->global->ADHERENT_BANK_USE || $subscription->fk_bank)
	    {
    		print '<tr><td>'.$langs->trans("BankTransactionLine").'</td><td class="valeur" colspan="3">';
			if ($subscription->fk_bank)
			{
	    		$bankline=new AccountLine($db);
		    	$result=$bankline->fetch($subscription->fk_bank);
		    	print $bankline->getNomUrl(1,0,'showall');
			}
			else
			{
				print $langs->trans("NoneF");
			}
	    	print '</td></tr>';
	    }
	}


    print "</table>\n";
    print '</form>';

    dol_fiche_end();

    /*
     * Barre d'actions
     *
     */
    print '<div class="tabsAction">';

    if ($user->rights->adherent->cotisation->creer)
	{
		if (! $bankline->rappro)
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"]."?rowid=".$subscription->id."&action=edit\">".$langs->trans("Modify")."</a></div>";
		}
		else
		{
			print '<div class="inline-block divButAction"><a class="butActionRefused" title="'.$langs->trans("BankLineConciliated")."\" href=\"#\">".$langs->trans("Modify")."</a></div>";
		}
	}

    // Supprimer
    if ($user->rights->adherent->cotisation->creer)
    {
        print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"]."?rowid=".$subscription->id."&action=delete\">".$langs->trans("Delete")."</a></div>\n";
    }

    print '</div>';
    print "<br>\n";

}


llxFooter();

$db->close();
