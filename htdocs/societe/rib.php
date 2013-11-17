<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Peter Fontaine       <contact@peterfontaine.fr>
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
 *	    \file       htdocs/societe/rib.php
 *      \ingroup    societe
 *		\brief      BAN tab for companies
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';

$langs->load("companies");
$langs->load("banks");
$langs->load("bills");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe','','');

$soc = new Societe($db);
$soc->id = $_GET["socid"];
$soc->fetch($_GET["socid"]);


/*
 *	Actions
 */

if ($_POST["action"] == 'update' && ! $_POST["cancel"])
{
	// Modification
	$account = new CompanyBankAccount($db);

    $account->fetch($_POST["id"]);

	$account->socid           = $soc->id;

	$account->bank            = $_POST["bank"];
	$account->label           = $_POST["label"];
	$account->courant         = $_POST["courant"];
	$account->clos            = $_POST["clos"];
	$account->code_banque     = $_POST["code_banque"];
	$account->code_guichet    = $_POST["code_guichet"];
	$account->number          = $_POST["number"];
	$account->cle_rib         = $_POST["cle_rib"];
	$account->bic             = $_POST["bic"];
	$account->iban_prefix     = $_POST["iban_prefix"];
	$account->domiciliation   = $_POST["domiciliation"];
	$account->proprio         = $_POST["proprio"];
	$account->owner_address   = $_POST["owner_address"];

	$result = $account->update($user);
	if (! $result)
	{
		$message=$account->error;
		$_GET["action"]='edit';     // Force chargement page edition
	}
	else
	{
		$url=DOL_URL_ROOT.'/societe/rib.php?socid='.$soc->id;
        header('Location: '.$url);
        exit;
	}
}

if ($_POST["action"] == 'add' && ! $_POST["cancel"])
{
    // Ajout
    $account = new CompanyBankAccount($db);

    $account->socid           = $soc->id;

    $account->bank            = $_POST["bank"];
    $account->label           = $_POST["label"];
    $account->courant         = $_POST["courant"];
    $account->clos            = 0;
    $account->code_banque     = $_POST["code_banque"];
    $account->code_guichet    = $_POST["code_guichet"];
    $account->number          = $_POST["number"];
    $account->cle_rib         = $_POST["cle_rib"];
    $account->bic             = $_POST["bic"];
    $account->iban_prefix     = $_POST["iban_prefix"];
    $account->domiciliation   = $_POST["domiciliation"];
    $account->proprio         = $_POST["proprio"];
    $account->owner_address   = $_POST["owner_address"];

    $result = $account->update($user);
    if (! $result)
    {
        $message=$account->error;
        $_GET["action"]='create';     // Force chargement page création
    }
    else
    {
        $url=DOL_URL_ROOT.'/societe/rib.php?socid='.$soc->id;
        header('Location: '.$url);
        exit;
    }
}

if ($_GET['action'] == 'setasdefault')
{
    $account = new CompanyBankAccount($db);
    $res = $account->setAsDefault($_GET['ribid']);
    if ($res) {
        $url=DOL_URL_ROOT.'/societe/rib.php?socid='.$soc->id;
        header('Location: '.$url);
        exit;
    } else {
        $message=$db->lasterror;
    }
}

if ($_GET['action'] == 'changestate')
{
    $account = new CompanyBankAccount($db);
    if (GETPOST('newstate'))
        $ns = 1;
    else
        $ns = 0;
    if ($account->fetch(GETPOST('ribid')))
    {
        $account->clos = $ns;
        $account->default_rib = 0;
        $result = $account->update($user);
        var_dump($account);
        if ($result)
        {
            $url = DOL_URL_ROOT.'/societe/rib.php?socid='.$soc->id;
            header('Location: '.$url);
            exit;
        }
        else
        {
            $message = $account->error;
        }
    }
    else
    {
        $message = $account->error;
    }
}

/*
 *	View
 */

llxHeader();

$head=societe_prepare_head2($soc);

dol_fiche_head($head, 'rib', $langs->trans("ThirdParty"),0,'company');

$account = new CompanyBankAccount($db);
if (!$_GET['id'])
    $account->fetch(0,$soc->id);
else
    $account->fetch($_GET['id']);
if (empty($account->socid)) $account->socid=$soc->id;



/* ************************************************************************** */
/*                                                                            */
/* Visu et edition                                                            */
/*                                                                            */
/* ************************************************************************** */

if ($_GET["socid"] && $_GET["action"] != 'edit' && $_GET["action"] != "create")
{
    print_titre($langs->trans("DefaultRIB"));
	print '<table class="border" width="100%">';

    print '<tr><td>'.$langs->trans("LabelRIB").'</td>';
    print '<td colspan="4">'.$account->label.'</td></tr>';

	print '<tr><td valign="top" width="35%">'.$langs->trans("Bank").'</td>';
	print '<td colspan="4">'.$account->bank.'</td></tr>';

	if ($account->useDetailedBBAN() == 1)
	{
		print '<tr><td>'.$langs->trans("BankCode").'</td>';
		print '<td colspan="3">'.$account->code_banque.'</td>';
		print '</tr>';

		print '<tr><td>'.$langs->trans("DeskCode").'</td>';
		print '<td colspan="3">'.$account->code_guichet.'</td>';
		print '</tr>';
	}
    if ($account->useDetailedBBAN() == 2)
    {
        print '<tr><td>'.$langs->trans("BankCode").'</td>';
        print '<td colspan="3">'.$account->code_banque.'</td>';
        print '</tr>';
    }

	print '<tr><td>'.$langs->trans("BankAccountNumber").'</td>';
	print '<td colspan="3">'.$account->number.'</td>';
	print '</tr>';

	if ($account->useDetailedBBAN() == 1)
	{
		print '<tr><td>'.$langs->trans("BankAccountNumberKey").'</td>';
		print '<td colspan="3">'.$account->cle_rib.'</td>';
		print '</tr>';
	}

	print '<tr><td valign="top">'.$langs->trans("IBAN").'</td>';
	print '<td colspan="4">'.$account->iban_prefix.'</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("BIC").'</td>';
	print '<td colspan="4">'.$account->bic.'</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="4">';
	print $account->domiciliation;
	print "</td></tr>\n";

	print '<tr><td valign="top">'.$langs->trans("BankAccountOwner").'</td><td colspan="4">';
	print $account->proprio;
	print "</td></tr>\n";

	print '<tr><td valign="top">'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="4">';
	print $account->owner_address;
	print "</td></tr>\n";

	print '</table>';

	// Check BBAN
	if (! checkBanForAccount($account))
	{
		print '<div class="warning">'.$langs->trans("RIBControlError").'</div>';
	}

    // All RIB

    print "<br />";

    print_titre($langs->trans("AllRIB"));

    $rib_list = $soc->get_all_rib();
    $var = false;
    if (is_array($rib_list)) {
        print '<table class="liste" width="100%">';

        print '<tr class="liste_titre">';
        print_liste_field_titre($langs->trans("LabelRIB"));
        print_liste_field_titre($langs->trans("Bank"));
        print_liste_field_titre($langs->trans("RIB"));
        print_liste_field_titre($langs->trans("DefaultRIB"));
        print '<td width="100"></td>';
        print '</tr>';

        foreach ($rib_list as $rib) {
            if ($rib->clos)
                $class = ' class="disabled_rib"';
            else
                $class = '';
            print "<tr $bc[$var]>";
            print '<td'.$class.'>'.$rib->label.'</td>';
            print '<td'.$class.'>'.$rib->bank.'</td>';
            print '<td'.$class.'>'.$rib->getRibLabel(false).'</td>';
            print '<td align="center" width="100">';
            if (!$rib->clos) {
                if (!$rib->default_rib) {
                    print '<a href="'.DOL_URL_ROOT.'/societe/rib.php?socid='.$soc->id.'&ribid='.$rib->id.'&action=setasdefault">';
                    print img_picto($langs->trans("RibNotDefault"),'switch_off');
                    print '</a>';
                } else {
                    print img_picto($langs->trans("RibDefault"),'switch_on');
                }
            }
            print '</td>';
            print '<td align="right">';
            // State
            if ($rib->clos) {
                print $langs->trans("Disabled").'&nbsp;';
                print '<a href="'.DOL_URL_ROOT.'/societe/rib.php?socid='.$soc->id.'&ribid='.$rib->id.'&action=changestate">';
                print img_picto($langs->trans("Disabled"),'statut8');
                print '</a>';
            } else {
                print $langs->trans("Enabled").'&nbsp;';
                print '<a href="'.DOL_URL_ROOT.'/societe/rib.php?socid='.$soc->id.'&ribid='.$rib->id.'&action=changestate&newstate=1">';
                print img_picto($langs->trans("Enabled"),'statut4');
                print '</a>';
            }
            // Set as default
            print '<a href="'.DOL_URL_ROOT.'/societe/rib.php?socid='.$soc->id.'&id='.$rib->id.'&action=edit">';
            print img_picto($langs->trans("Modify"),'edit');
            print '</a>';
            print '</td>';
            print '</tr>';
            $var = !$var;
        }

        if (count($rib_list) == 0) {
            print '<tr><td colspan="5" align="center">'.$langs->trans("NoBANRecord").'</td></tr>';
        }

        print '</table>';
    } else {
        dol_print_error($db);
    }
}

/* ************************************************************************** */
/*                                                                            */
/* Edition                                                                    */
/*                                                                            */
/* ************************************************************************** */

if ($_GET["socid"] && $_GET["action"] == 'edit' && $user->rights->societe->creer)
{

    $form = new Form($db);

    dol_htmloutput_mesg($message);

    print '<form action="rib.php?socid='.$soc->id.'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="'.$_GET["id"].'">';

    print '<table class="border" width="100%">';

    print '<tr><td valign="top" width="35%">'.$langs->trans("LabelRIB").'</td>';
    print '<td colspan="4"><input size="30" type="text" name="label" value="'.$account->label.'"></td></tr>';

    print '<tr><td>'.$langs->trans("Bank").'</td>';
    print '<td><input size="30" type="text" name="bank" value="'.$account->bank.'"></td></tr>';

    // BBAN
    if ($account->useDetailedBBAN() == 1)
    {
        print '<tr><td>'.$langs->trans("BankCode").'</td>';
        print '<td><input size="8" type="text" class="flat" name="code_banque" value="'.$account->code_banque.'"></td>';
        print '</tr>';

        print '<tr><td>'.$langs->trans("DeskCode").'</td>';
        print '<td><input size="8" type="text" class="flat" name="code_guichet" value="'.$account->code_guichet.'"></td>';
        print '</tr>';
    }
    if ($account->useDetailedBBAN() == 2)
    {
        print '<tr><td>'.$langs->trans("BankCode").'</td>';
        print '<td><input size="8" type="text" class="flat" name="code_banque" value="'.$account->code_banque.'"></td>';
        print '</tr>';
    }

    print '<td>'.$langs->trans("BankAccountNumber").'</td>';
    print '<td><input size="15" type="text" class="flat" name="number" value="'.$account->number.'"></td>';
    print '</tr>';

    if ($account->useDetailedBBAN() == 1)
    {
        print '<td>'.$langs->trans("BankAccountNumberKey").'</td>';
        print '<td><input size="3" type="text" class="flat" name="cle_rib" value="'.$account->cle_rib.'"></td>';
        print '</tr>';
    }

    // IBAN
    print '<tr><td valign="top">'.$langs->trans("IBAN").'</td>';
    print '<td colspan="4"><input size="30" type="text" name="iban_prefix" value="'.$account->iban_prefix.'"></td></tr>';

    print '<tr><td valign="top">'.$langs->trans("BIC").'</td>';
    print '<td colspan="4"><input size="12" type="text" name="bic" value="'.$account->bic.'"></td></tr>';

    print '<tr><td valign="top">'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="4">';
    print "<textarea name=\"domiciliation\" rows=\"4\" cols=\"40\">";
    print $account->domiciliation;
    print "</textarea></td></tr>";

    print '<tr><td valign="top">'.$langs->trans("BankAccountOwner").'</td>';
    print '<td colspan="4"><input size="30" type="text" name="proprio" value="'.$account->proprio.'"></td></tr>';
    print "</td></tr>\n";

    print '<tr><td valign="top">'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="4">';
    print "<textarea name=\"owner_address\" rows=\"4\" cols=\"40\">";
    print $account->owner_address;
    print "</textarea></td></tr>";

    print '</table><br>';

    print '<center><input class="button" value="'.$langs->trans("Modify").'" type="submit">';
    print ' &nbsp; <input name="cancel" class="button" value="'.$langs->trans("Cancel").'" type="submit">';
    print '</center>';

    print '</form>';
}


/* ************************************************************************** */
/*                                                                            */
/* Création                                                                   */
/*                                                                            */
/* ************************************************************************** */

if ($_GET["socid"] && $_GET["action"] == 'create' && $user->rights->societe->creer)
{

    $form = new Form($db);

    dol_htmloutput_mesg($message);

    print '<form action="rib.php?socid='.$soc->id.'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
    print '<table class="border" width="100%">';


    print '<tr><td valign="top" width="35%">'.$langs->trans("LabelRIB").'</td>';
    print '<td colspan="4"><input size="30" type="text" name="label"></td></tr>';

    print '<tr><td>'.$langs->trans("Bank").'</td>';
    print '<td><input size="30" type="text" name="bank"></td></tr>';

    // BBAN
    if ($account->useDetailedBBAN() == 1)
    {
        print '<tr><td>'.$langs->trans("BankCode").'</td>';
        print '<td><input size="8" type="text" class="flat" name="code_banque"></td>';
        print '</tr>';

        print '<tr><td>'.$langs->trans("DeskCode").'</td>';
        print '<td><input size="8" type="text" class="flat" name="code_guichet"></td>';
        print '</tr>';
    }
    if ($account->useDetailedBBAN() == 2)
    {
        print '<tr><td>'.$langs->trans("BankCode").'</td>';
        print '<td><input size="8" type="text" class="flat" name="code_banque"></td>';
        print '</tr>';
    }

    print '<td>'.$langs->trans("BankAccountNumber").'</td>';
    print '<td><input size="15" type="text" class="flat" name="number"></td>';
    print '</tr>';

    if ($account->useDetailedBBAN() == 1)
    {
        print '<td>'.$langs->trans("BankAccountNumberKey").'</td>';
        print '<td><input size="3" type="text" class="flat" name="cle_rib"></td>';
        print '</tr>';
    }

    // IBAN
    print '<tr><td valign="top">'.$langs->trans("IBAN").'</td>';
    print '<td colspan="4"><input size="30" type="text" name="iban_prefix"></td></tr>';

    print '<tr><td valign="top">'.$langs->trans("BIC").'</td>';
    print '<td colspan="4"><input size="12" type="text" name="bic"></td></tr>';

    print '<tr><td valign="top">'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="4">';
    print "<textarea name=\"domiciliation\" rows=\"4\" cols=\"40\">";
    print "</textarea></td></tr>";

    print '<tr><td valign="top">'.$langs->trans("BankAccountOwner").'</td>';
    print '<td colspan="4"><input size="30" type="text" name="proprio"></td></tr>';
    print "</td></tr>\n";

    print '<tr><td valign="top">'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="4">';
    print "<textarea name=\"owner_address\" rows=\"4\" cols=\"40\">";
    print "</textarea></td></tr>";

    print '</table><br>';

    print '<center><input class="button" value="'.$langs->trans("Add").'" type="submit">';
    print ' &nbsp; <input name="cancel" class="button" value="'.$langs->trans("Cancel").'" type="submit">';
    print '</center>';

    print '</form>';
}


dol_fiche_end();


if ($_GET["socid"] && $_GET["action"] != 'edit' && $_GET["action"] != 'create')
{
	/*
	 * Barre d'actions
	 */
	print '<div class="tabsAction">';

	if ($user->rights->societe->creer)
	{
		print '<a class="butAction" href="rib.php?socid='.$soc->id.'&amp;action=create">'.$langs->trans("Add").'</a>';
	}

	print '</div>';
}


$db->close();


llxFooter();
?>
