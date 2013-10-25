<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	    \file       htdocs/compta/tva/fiche.php
 *      \ingroup    tax
 *		\brief      Page of VAT payments
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("compta");
$langs->load("banks");
$langs->load("bills");

$id=GETPOST("id");
$action=GETPOST('action');

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');

$tva = new Tva($db);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('taxvatcard'));



/**
 * Actions
 */

if ($_POST["cancel"] == $langs->trans("Cancel"))
{
	header("Location: reglement.php");
	exit;
}

if ($action == 'add' && $_POST["cancel"] <> $langs->trans("Cancel"))
{
    $db->begin();

    $datev=dol_mktime(12,0,0, $_POST["datevmonth"], $_POST["datevday"], $_POST["datevyear"]);
    $datep=dol_mktime(12,0,0, $_POST["datepmonth"], $_POST["datepday"], $_POST["datepyear"]);

    $tva->accountid=$_POST["accountid"];
    $tva->paymenttype=$_POST["paiementtype"];
    $tva->datev=$datev;
    $tva->datep=$datep;
    $tva->amount=$_POST["amount"];
	$tva->label=$_POST["label"];

    $ret=$tva->addPayment($user);
    if ($ret > 0)
    {
        $db->commit();
        header("Location: reglement.php");
        exit;
    }
    else
    {
        $db->rollback();
        setEventMessage($tva->error, 'errors');
        $action="create";
    }
}

if ($action == 'delete')
{
    $result=$tva->fetch($_GET['id']);

	if ($tva->rappro == 0)
	{
	    $db->begin();

	    $ret=$tva->delete($user);
	    if ($ret > 0)
	    {
			if ($tva->fk_bank)
			{
				$accountline=new AccountLine($db);
				$result=$accountline->fetch($tva->fk_bank);
				$result=$accountline->delete($user);
			}

			if ($result > 0)
			{
				$db->commit();
				header("Location: ".DOL_URL_ROOT.'/compta/tva/reglement.php');
				exit;
			}
			else
			{
				$tva->error=$accountline->error;
				$db->rollback();
				setEventMessage($tva->error,'errors');
			}
	    }
	    else
	    {
	        $db->rollback();
	        setEventMessage($tva->error,'errors');
	    }
	}
	else
	{
        setEventMessage('Error try do delete a line linked to a conciliated bank transaction','errors');
	}
}


/*
*	View
*/

llxHeader();

$form = new Form($db);

if ($id)
{
    $vatpayment = new Tva($db);
	$result = $vatpayment->fetch($id);
	if ($result <= 0)
	{
		dol_print_error($db);
		exit;
	}
}

// Formulaire saisie tva
if ($action == 'create')
{
    print "<form name='add' action=\"fiche.php\" method=\"post\">\n";
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';

    print_fiche_titre($langs->trans("NewVATPayment"));

    print '<table class="border" width="100%">';

    print "<tr>";
    print '<td class="fieldrequired">'.$langs->trans("DatePayment").'</td><td>';
    print $form->select_date($datep,"datep",'','','','add');
    print '</td></tr>';

    print '<tr><td class="fieldrequired">'.$langs->trans("DateValue").'</td><td>';
    print $form->select_date($datev,"datev",'','','','add');
    print '</td></tr>';

	// Label
	print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input name="label" size="40" value="'.($_POST["label"]?$_POST["label"]:$langs->trans("VATPayment")).'"></td></tr>';

	// Amount
	print '<tr><td class="fieldrequired">'.$langs->trans("Amount").'</td><td><input name="amount" size="10" value="'.$_POST["amount"].'"></td></tr>';

    if (! empty($conf->banque->enabled))
    {
		print '<tr><td class="fieldrequired">'.$langs->trans("Account").'</td><td>';
        $form->select_comptes($_POST["accountid"],"accountid",0,"courant=1",1);  // Affiche liste des comptes courant
        print '</td></tr>';

	    print '<tr><td class="fieldrequired">'.$langs->trans("PaymentMode").'</td><td>';
	    $form->select_types_paiements($_POST["paiementtype"], "paiementtype");
	    print "</td>\n";
	    print "</tr>";
	}

    // Other attributes
    $parameters=array('colspan' => ' colspan="1"');
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

    print '</table>';

	print "<br>";

	print '<center><input type="submit" class="button" value="'.$langs->trans("Save").'"> &nbsp; ';
    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></center>';

    print '</form>';
}


/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

if ($id)
{
	$h = 0;
	$head[$h][0] = DOL_URL_ROOT.'/compta/tva/fiche.php?id='.$vatpayment->id;
	$head[$h][1] = $langs->trans('Card');
	$head[$h][2] = 'card';
	$h++;

	dol_fiche_head($head, 'card', $langs->trans("VATPayment"), 0, 'payment');


	print '<table class="border" width="100%">';

	print "<tr>";
	print '<td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
	print $vatpayment->ref;
	print '</td></tr>';

	// Label
	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$vatpayment->label.'</td></tr>';

	print "<tr>";
	print '<td>'.$langs->trans("DatePayment").'</td><td colspan="3">';
	print dol_print_date($vatpayment->datep,'day');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("DateValue").'</td><td colspan="3">';
	print dol_print_date($vatpayment->datev,'day');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Amount").'</td><td colspan="3">'.price($vatpayment->amount).'</td></tr>';

	if (! empty($conf->banque->enabled))
	{
		if ($vatpayment->fk_account > 0)
		{
 		   	$bankline=new AccountLine($db);
    		$bankline->fetch($vatpayment->fk_bank);

	    	print '<tr>';
	    	print '<td>'.$langs->trans('BankTransactionLine').'</td>';
			print '<td colspan="3">';
			print $bankline->getNomUrl(1,0,'showall');
	    	print '</td>';
	    	print '</tr>';
		}
	}

        // Other attributes
        $parameters=array('colspan' => ' colspan="3"');
        $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$vatpayment,$action);    // Note that $action and $object may have been modified by hook

	print '</table>';

	print '</div>';

	/*
	* Boutons d'actions
	*/
	print "<div class=\"tabsAction\">\n";
	if ($vatpayment->rappro == 0)
	{
		if (! empty($user->rights->tax->charges->supprimer))
		{
			print '<a class="butActionDelete" href="fiche.php?id='.$vatpayment->id.'&action=delete">'.$langs->trans("Delete").'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.(dol_escape_htmltag($langs->trans("NotAllowed"))).'">'.$langs->trans("Delete").'</a>';
		}
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("LinkedToAConcialitedTransaction").'">'.$langs->trans("Delete").'</a>';
	}
	print "</div>";
}


$db->close();

llxFooter();
?>