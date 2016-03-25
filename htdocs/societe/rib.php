<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Peter Fontaine       <contact@peterfontaine.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';

$langs->load("companies");
$langs->load("commercial");
$langs->load("banks");
$langs->load("bills");

// Security check
$socid = GETPOST("socid");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe','','');

$object = new Societe($db);
$object->fetch($socid);

$id=GETPOST("id","int");
$ribid=GETPOST("ribid","int");
$action=GETPOST("action");


/*
 *	Actions
 */

if ($action == 'update' && ! $_POST["cancel"])
{
	// Modification
	$account = new CompanyBankAccount($db);

    $account->fetch($id);

    $account->socid           = $object->id;

	$account->bank            = $_POST["bank"];
	$account->label           = $_POST["label"];
	$account->courant         = $_POST["courant"];
	$account->clos            = $_POST["clos"];
	$account->code_banque     = $_POST["code_banque"];
	$account->code_guichet    = $_POST["code_guichet"];
	$account->number          = $_POST["number"];
	$account->cle_rib         = $_POST["cle_rib"];
	$account->bic             = $_POST["bic"];
	$account->iban            = $_POST["iban"];
	$account->domiciliation   = $_POST["domiciliation"];
	$account->proprio         = $_POST["proprio"];
	$account->owner_address   = $_POST["owner_address"];
	$account->frstrecur       = GETPOST('frstrecur');

	$result = $account->update($user);
	if (! $result)
	{
		setEventMessages($account->error, $account->errors, 'errors');
		$_GET["action"]='edit';     // Force chargement page edition
	}
	else
	{
		// If this account is the default bank account, we disable others
		if ($account->default_rib)
		{
			$account->setAsDefault($id);	// This will make sure there is only one default rib
		}

		$url=DOL_URL_ROOT.'/societe/rib.php?socid='.$object->id;
        header('Location: '.$url);
        exit;
	}
}

if ($action == 'add' && ! $_POST["cancel"])
{
	$error=0;

	if (! GETPOST('label'))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
		$action='create';
		$error++;
	}
	if (! GETPOST('bank'))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BankName")), null, 'errors');
		$action='create';
		$error++;
	}

	if (! $error)
	{
	    // Ajout
	    $account = new CompanyBankAccount($db);

	    $account->socid           = $object->id;

	    $account->bank            = $_POST["bank"];
	    $account->label           = $_POST["label"];
	    $account->courant         = $_POST["courant"];
	    $account->clos            = $_POST["clos"];
	    $account->code_banque     = $_POST["code_banque"];
	    $account->code_guichet    = $_POST["code_guichet"];
	    $account->number          = $_POST["number"];
	    $account->cle_rib         = $_POST["cle_rib"];
	    $account->bic             = $_POST["bic"];
	    $account->iban            = $_POST["iban"];
	    $account->domiciliation   = $_POST["domiciliation"];
	    $account->proprio         = $_POST["proprio"];
	    $account->owner_address   = $_POST["owner_address"];
		$account->frstrecur       = GETPOST('frstrecur');

	    $result = $account->update($user);	// TODO Use create and include update into create method
	    if (! $result)
	    {
		    setEventMessages($account->error, $account->errors, 'errors');
	        $_GET["action"]='create';     // Force chargement page création
	    }
	    else
	    {
	        $url=DOL_URL_ROOT.'/societe/rib.php?socid='.$object->id;
	        header('Location: '.$url);
	        exit;
	    }
	}
}

if ($action == 'setasdefault')
{
    $account = new CompanyBankAccount($db);
    $res = $account->setAsDefault(GETPOST('ribid','int'));
    if ($res)
    {
        $url=DOL_URL_ROOT.'/societe/rib.php?socid='.$object->id;
        header('Location: '.$url);
        exit;
    } 
    else 
    {
	    setEventMessages($db->lasterror, null, 'errors');
    }
}

if ($action == 'confirm_delete' && $_GET['confirm'] == 'yes')
{
	$account = new CompanyBankAccount($db);
	if ($account->fetch($ribid?$ribid:$id))
	{
		$result = $account->delete($user);
		if ($result > 0)
		{
			$url = $_SERVER['PHP_SELF']."?socid=".$object->id;
			header('Location: '.$url);
			exit;
		}
		else
		{
			setEventMessages($account->error, $account->errors, 'errors');
		}
	}
	else
	{
		setEventMessages($account->error, $account->errors, 'errors');
    }
}


/*
 *	View
 */

$form = new Form($db);
$prelevement = new BonPrelevement($db);

llxHeader();

$head=societe_prepare_head2($object);


$account = new CompanyBankAccount($db);
if (! $id)
    $account->fetch(0,$object->id);
else
    $account->fetch($id);
if (empty($account->socid)) $account->socid=$object->id;


if ($socid && $action == 'edit' && $user->rights->societe->creer)
{
    print '<form action="rib.php?socid='.$object->id.'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="'.$_GET["id"].'">';
}
if ($socid && $action == 'create' && $user->rights->societe->creer)
{
    print '<form action="rib.php?socid='.$object->id.'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
}


// View
if ($socid && $action != 'edit' && $action != "create")
{
	dol_fiche_head($head, 'rib', $langs->trans("ThirdParty"),0,'company');

	// Confirm delete third party
    if ($action == 'delete')
    {
        print $form->formconfirm($_SERVER["PHP_SELF"]."?socid=".$object->id."&ribid=".($ribid?$ribid:$id), $langs->trans("DeleteARib"), $langs->trans("ConfirmDeleteRib", $account->getRibLabel()), "confirm_delete", '', 0, 1);
    }

    dol_banner_tab($object, 'socid', '', ($user->societe_id?0:1), 'rowid', 'nom');
        
    print '<div class="fichecenter">';
    
    print load_fiche_titre($langs->trans("DefaultRIB"), '', '');

    print '<div class="underbanner clearboth"></div>';
    print '<table class="border centpercent">';

    print '<tr><td class="titlefield" width="25%">'.$langs->trans("LabelRIB").'</td>';
    print '<td colspan="4">'.$account->label.'</td></tr>';

	print '<tr><td>'.$langs->trans("BankName").'</td>';
	print '<td colspan="4">'.$account->bank.'</td></tr>';

	// Show fields of bank account
	$fieldlists='BankCode DeskCode AccountNumber BankAccountNumberKey';
	if (! empty($conf->global->BANK_SHOW_ORDER_OPTION))
	{
		if (is_numeric($conf->global->BANK_SHOW_ORDER_OPTION))
		{
			if ($conf->global->BANK_SHOW_ORDER_OPTION == '1') $fieldlists='BankCode DeskCode BankAccountNumberKey AccountNumber';
		}
		else $fieldlists=$conf->global->BANK_SHOW_ORDER_OPTION;
	}
	$fieldlistsarray=explode(' ',$fieldlists);

	foreach($fieldlistsarray as $val)
	{
		if ($val == 'BankCode')
		{
			if ($account->useDetailedBBAN() == 1)
			{
				print '<tr><td>'.$langs->trans("BankCode").'</td>';
				print '<td colspan="3">'.$account->code_banque.'</td>';
				print '</tr>';
			}
		}
		if ($val == 'DeskCode')
		{
			if ($account->useDetailedBBAN() == 1)
			{
				print '<tr><td>'.$langs->trans("DeskCode").'</td>';
				print '<td colspan="3">'.$account->code_guichet.'</td>';
				print '</tr>';
			}
		}

		if ($val == 'BankCode')
		{
			if ($account->useDetailedBBAN() == 2)
	        {
	            print '<tr><td>'.$langs->trans("BankCode").'</td>';
	            print '<td colspan="3">'.$account->code_banque.'</td>';
	            print '</tr>';
	        }
		}

		if ($val == 'AccountNumber')
		{
			print '<tr><td>'.$langs->trans("BankAccountNumber").'</td>';
			print '<td colspan="3">'.$account->number.'</td>';
			print '</tr>';
		}

		if ($val == 'BankAccountNumberKey')
		{
			if ($account->useDetailedBBAN() == 1)
			{
				print '<tr><td>'.$langs->trans("BankAccountNumberKey").'</td>';
				print '<td colspan="3">'.$account->cle_rib.'</td>';
				print '</tr>';
			}
		}
	}

	print '<tr><td valign="top">'.$langs->trans("IBAN").'</td>';
	print '<td colspan="4">'.$account->iban . '&nbsp;';
    if (! empty($account->iban)) {
        if (! checkIbanForAccount($account)) {
            print img_picto($langs->trans("IbanNotValid"),'warning');
        } else {
            print img_picto($langs->trans("IbanValid"),'info');
        }
    }
    print '</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("BIC").'</td>';
	print '<td colspan="4">'.$account->bic.'&nbsp;';
    if (! empty($account->bic)) {
        if (! checkSwiftForAccount($account)) {
            print img_picto($langs->trans("SwiftNotValid"),'warning');
        } else {
            print img_picto($langs->trans("SwiftValid"),'info');
        }
    }
    print '</td></tr>';

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
	if ($account->label && ! checkBanForAccount($account))
	{
		print '<div class="warning">'.$langs->trans("RIBControlError").'</div>';
	}

    print "</div>";
    
    dol_fiche_end();


    /*
     * List of bank accounts
     */

    print load_fiche_titre($langs->trans("AllRIB"));

    $rib_list = $object->get_all_rib();
    $var = false;
    if (is_array($rib_list))
    {
        print '<table class="liste" width="100%">';

        print '<tr class="liste_titre">';
        print_liste_field_titre($langs->trans("LabelRIB"));
        print_liste_field_titre($langs->trans("Bank"));
        print_liste_field_titre($langs->trans("RIB"));
        print_liste_field_titre($langs->trans("IBAN"));
        print_liste_field_titre($langs->trans("BIC"));
        if (! empty($conf->prelevement->enabled))
        {
			print '<td>RUM</td>';
			print '<td>'.$langs->trans("WithdrawMode").'</td>';
        }
        print_liste_field_titre($langs->trans("DefaultRIB"), '', '', '', '', 'align="center"');
        print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
		print "</tr>\n";

        foreach ($rib_list as $rib)
        {
            print "<tr ".$bc[$var].">";
            // Label
            print '<td>'.$rib->label.'</td>';
            // Bank name
            print '<td>'.$rib->bank.'</td>';
            // Account number
            print '<td>'.$rib->getRibLabel(false).'</td>';
            // IBAN
            print '<td>'.$rib->iban.'</td>';
            // BIC
            print '<td>'.$rib->bic.'</td>';

            if (! empty($conf->prelevement->enabled))
            {
            	// RUM
				print '<td>'.$prelevement->buildRumNumber($object->code_client, $rib->datec, $rib->id).'</td>';

				// FRSTRECUR
				print '<td>'.$rib->frstrecur.'</td>';
            }

            // Default
            print '<td align="center" width="70">';
            if (!$rib->default_rib) {
                print '<a href="'.DOL_URL_ROOT.'/societe/rib.php?socid='.$object->id.'&ribid='.$rib->id.'&action=setasdefault">';
                print img_picto($langs->trans("Disabled"),'off');
                print '</a>';
            } else {
                print img_picto($langs->trans("Enabled"),'on');
            }
            print '</td>';

            // Edit/Delete
            print '<td align="right">';
            if ($user->rights->societe->creer)
            {
            	print '<a href="'.DOL_URL_ROOT.'/societe/rib.php?socid='.$object->id.'&id='.$rib->id.'&action=edit">';
            	print img_picto($langs->trans("Modify"),'edit');
            	print '</a>';

           		print '&nbsp;';

           		print '<a href="'.DOL_URL_ROOT.'/societe/rib.php?socid='.$object->id.'&id='.$rib->id.'&action=delete">';
           		print img_picto($langs->trans("Delete"),'delete');
           		print '</a>';
            }
        	print '</td>';
	        print '</tr>';
        }

        if (count($rib_list) == 0)
        {
        	$colspan=7;
        	if (! empty($conf->prelevement->enabled)) $colspan+=2;
            print '<tr '.$bc[0].'><td colspan="'.$colspan.'" align="center">'.$langs->trans("NoBANRecord").'</td></tr>';
        }

        print '</table>';
    } else {
        dol_print_error($db);
    }

}

// Edit
if ($socid && $action == 'edit' && $user->rights->societe->creer)
{
	dol_fiche_head($head, 'rib', $langs->trans("ThirdParty"),0,'company');

    dol_banner_tab($object, 'socid', '', ($user->societe_id?0:1), 'rowid', 'nom');
        
    print '<div class="fichecenter">';
    
    print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

    print '<tr><td valign="top" width="25%" class="fieldrequired">'.$langs->trans("LabelRIB").'</td>';
    print '<td colspan="4"><input size="30" type="text" name="label" value="'.$account->label.'"></td></tr>';

    print '<tr><td class="fieldrequired">'.$langs->trans("BankName").'</td>';
    print '<td><input size="30" type="text" name="bank" value="'.$account->bank.'"></td></tr>';

	// Show fields of bank account
	$fieldlists='BankCode DeskCode AccountNumber BankAccountNumberKey';
	if (! empty($conf->global->BANK_SHOW_ORDER_OPTION))
	{
		if (is_numeric($conf->global->BANK_SHOW_ORDER_OPTION))
		{
			if ($conf->global->BANK_SHOW_ORDER_OPTION == '1') $fieldlists='BankCode DeskCode BankAccountNumberKey AccountNumber';
		}
		else $fieldlists=$conf->global->BANK_SHOW_ORDER_OPTION;
	}
	$fieldlistsarray=explode(' ',$fieldlists);

	foreach($fieldlistsarray as $val)
	{
		if ($val == 'BankCode')
		{
			if ($account->useDetailedBBAN()  == 1)
			{
				print '<tr><td>'.$langs->trans("BankCode").'</td>';
				print '<td><input size="8" type="text" class="flat" name="code_banque" value="'.$account->code_banque.'"></td>';
				print '</tr>';
			}
		}

		if ($val == 'DeskCode')
		{
			if ($account->useDetailedBBAN()  == 1)
			{
				print '<tr><td>'.$langs->trans("DeskCode").'</td>';
				print '<td><input size="8" type="text" class="flat" name="code_guichet" value="'.$account->code_guichet.'"></td>';
				print '</tr>';
			}
		}

		if ($val == 'BankCode')
		{
			if ($account->useDetailedBBAN()  == 2)
	        {
	            print '<tr><td>'.$langs->trans("BankCode").'</td>';
	            print '<td><input size="8" type="text" class="flat" name="code_banque" value="'.$account->code_banque.'"></td>';
	            print '</tr>';
	        }
		}

		if ($val == 'AccountNumber')
		{
			print '<td class="fieldrequired">'.$langs->trans("BankAccountNumber").'</td>';
			print '<td><input size="18" type="text" class="flat" name="number" value="'.$account->number.'"></td>';
			print '</tr>';
		}

		if ($val == 'BankAccountNumberKey')
		{
			if ($account->useDetailedBBAN() == 1)
			{
				print '<td>'.$langs->trans("BankAccountNumberKey").'</td>';
				print '<td><input size="3" type="text" class="flat" name="cle_rib" value="'.$account->cle_rib.'"></td>';
				print '</tr>';
			}
		}
	}

    // IBAN
    print '<tr><td valign="top" class="fieldrequired">'.$langs->trans("IBAN").'</td>';
    print '<td colspan="4"><input size="30" type="text" name="iban" value="'.$account->iban.'"></td></tr>';

    print '<tr><td valign="top" class="fieldrequired">'.$langs->trans("BIC").'</td>';
    print '<td colspan="4"><input size="12" type="text" name="bic" value="'.$account->bic.'"></td></tr>';

    print '<tr><td valign="top">'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="4">';
    print '<textarea name="domiciliation" rows="4" cols="40">';
    print $account->domiciliation;
    print "</textarea></td></tr>";

    print '<tr><td valign="top">'.$langs->trans("BankAccountOwner").'</td>';
    print '<td colspan="4"><input size="30" type="text" name="proprio" value="'.$account->proprio.'"></td></tr>';
    print "</td></tr>\n";

    print '<tr><td valign="top">'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="4">';
    print "<textarea name=\"owner_address\" rows=\"4\" cols=\"40\">";
    print $account->owner_address;
    print "</textarea></td></tr>";

    print '</table>';

    if ($conf->prelevement->enabled)
    {
		print '<br>';

    	print '<table class="border" width="100%">';

    	if (empty($account->rum)) $account->rum = $prelevement->buildRumNumber($object->code_client, $account->datec, $account->id);

    	// RUM
    	print '<tr><td width="35%">'.$langs->trans("RUM").'</td>';
	    print '<td colspan="4">'.$account->rum.'</td></tr>';

	    // FRSTRECUR
	    print '<tr><td width="35%">'.$langs->trans("WithdrawMode").'</td>';
	    print '<td colspan="4"><input size="30" type="text" name="frstrecur" value="'.(GETPOST('frstrecur')?GETPOST('frstrecur'):$account->frstrecur).'"></td></tr>';

	    print '</table>';
    }

    print '</div>';
    
    dol_fiche_end();

	print '<div align="center">';
	print '<input class="button" value="'.$langs->trans("Modify").'" type="submit">';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input class="button" name="cancel" value="'.$langs->trans("Cancel").'" type="submit">';
    print '</div>';
}


// Create
if ($socid && $action == 'create' && $user->rights->societe->creer)
{
	dol_fiche_head($head, 'rib', $langs->trans("ThirdParty"),0,'company');

    dol_banner_tab($object, 'socid', '', ($user->societe_id?0:1), 'rowid', 'nom');
        
    print '<div class="fichecenter">';
    
    print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">';

    print '<tr><td valign="top" width="25%" class="fieldrequired">'.$langs->trans("LabelRIB").'</td>';
    print '<td colspan="4"><input size="30" type="text" name="label" value="'.GETPOST('label').'"></td></tr>';

    print '<tr><td class="fieldrequired">'.$langs->trans("Bank").'</td>';
    print '<td><input size="30" type="text" name="bank" value="'.GETPOST('bank').'"></td></tr>';

    // BBAN
    if ($account->useDetailedBBAN() == 1)
    {
        print '<tr><td>'.$langs->trans("BankCode").'</td>';
        print '<td><input size="8" type="text" class="flat" name="code_banque" value="'.GETPOST('code_banque').'"></td>';
        print '</tr>';

        print '<tr><td>'.$langs->trans("DeskCode").'</td>';
        print '<td><input size="8" type="text" class="flat" name="code_guichet" value="'.GETPOST('code_guichet').'"></td>';
        print '</tr>';
    }
    if ($account->useDetailedBBAN() == 2)
    {
        print '<tr><td>'.$langs->trans("BankCode").'</td>';
        print '<td><input size="8" type="text" class="flat" name="code_banque" value="'.GETPOST('code_banque').'"></td>';
        print '</tr>';
    }

    print '<td>'.$langs->trans("BankAccountNumber").'</td>';
    print '<td><input size="15" type="text" class="flat" name="number" value="'.GETPOST('number').'"></td>';
    print '</tr>';

    if ($account->useDetailedBBAN() == 1)
    {
        print '<td>'.$langs->trans("BankAccountNumberKey").'</td>';
        print '<td><input size="3" type="text" class="flat" name="cle_rib" value="'.GETPOST('value').'"></td>';
        print '</tr>';
    }

    // IBAN
    print '<tr><td valign="top">'.$langs->trans("IBAN").'</td>';
    print '<td colspan="4"><input size="30" type="text" name="iban" value="'.GETPOST('iban').'"></td></tr>';

    print '<tr><td valign="top">'.$langs->trans("BIC").'</td>';
    print '<td colspan="4"><input size="12" type="text" name="bic" value="'.GETPOST('bic').'"></td></tr>';

    print '<tr><td valign="top">'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="4">';
    print '<textarea name="domiciliation" rows="4" cols="40">';
    print GETPOST('domiciliation');
    print "</textarea></td></tr>";

    print '<tr><td valign="top">'.$langs->trans("BankAccountOwner").'</td>';
    print '<td colspan="4"><input size="30" type="text" name="proprio" value="'.GETPOST('proprio').'"></td></tr>';
    print "</td></tr>\n";

    print '<tr><td valign="top">'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="4">';
    print '<textarea name="owner_address" rows="4" cols="40">';
    print GETPOST('owner_address');
    print "</textarea></td></tr>";

    print '</table>';

    if ($conf->prelevement->enabled)
    {
		print '<br>';

    	print '<table class="border" width="100%">';

    	// RUM
    	print '<tr><td width="35%">'.$langs->trans("RUM").'</td>';
	    print '<td colspan="4">'.$langs->trans("RUMWillBeGenerated").'</td></tr>';

	    // FRSTRECUR
	    print '<tr><td width="35%">'.$langs->trans("WithdrawMode").'</td>';
	    print '<td colspan="4"><input size="30" type="text" name="frstrecur" value="'.(isset($_POST['frstrecur'])?GETPOST('frstrecur'):'FRST').'"></td></tr>';

	    print '</table>';
    }

    print '</div>';
    
	dol_fiche_end();

	print '<div align="center">';
	print '<input class="button" value="'.$langs->trans("Add").'" type="submit">';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input name="cancel" class="button" value="'.$langs->trans("Cancel").'" type="submit">';
    print '</div>';
}

if ($socid && $action == 'edit' && $user->rights->societe->creer)
{
	print '</form>';
}
if ($socid && $action == 'create' && $user->rights->societe->creer)
{
	print '</form>';
}



if ($socid && $action != 'edit' && $action != 'create')
{
	/*
	 * Barre d'actions
	 */
	print '<div class="tabsAction">';

	if ($user->rights->societe->creer)
	{
		print '<a class="butAction" href="rib.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("Add").'</a>';
	}

	print '</div>';
}


llxFooter();

$db->close();
