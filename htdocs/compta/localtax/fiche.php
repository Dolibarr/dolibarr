<?php
/* Copyright (C) 2011      Juanjo Menent <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/compta/localtax/fiche.php
 *      \ingroup    tax
 *		\brief      Page of IRPF payments
 */

require('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/compta/localtax/class/localtax.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");

$langs->load("compta");
$langs->load("banks");
$langs->load("bills");

$id=$_REQUEST["id"];

$mesg = '';

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');


/*
 * Actions 
 */

//add payment of localtax
if ($_POST["action"] == 'add' && $_POST["cancel"] <> $langs->trans("Cancel"))
{
    $localtax = new localtax($db);

    $db->begin();

    $datev=dol_mktime(12,0,0, $_POST["datevmonth"], $_POST["datevday"], $_POST["datevyear"]);
    $datep=dol_mktime(12,0,0, $_POST["datepmonth"], $_POST["datepday"], $_POST["datepyear"]);

    $localtax->accountid=$_POST["accountid"];
    $localtax->paymenttype=$_POST["paiementtype"];
    $localtax->datev=$datev;
    $localtax->datep=$datep;
    $localtax->amount=$_POST["amount"];
	$localtax->label=$_POST["label"];

    $ret=$localtax->addPayment($user);
    if ($ret > 0)
    {
        $db->commit();
        Header("Location: reglement.php");
        exit;
    }
    else
    {
        $db->rollback();
        $mesg='<div class="error">'.$localtax->error.'</div>';
        $_GET["action"]="create";
    }
}

//delete payment of localtax
if ($_GET["action"] == 'delete')
{
    $localtax = new localtax($db);
    $result=$localtax->fetch($_GET['id']);

	if ($localtax->rappro == 0)
	{
	    $db->begin();

	    $ret=$localtax->delete($user);
	    if ($ret > 0)
	    {
			if ($localtax->fk_bank)
			{
				$accountline=new AccountLine($db);
				$result=$accountline->fetch($localtax->fk_bank);
				$result=$accountline->delete($user);
			}

			if ($result > 0)
			{
				$db->commit();
				header("Location: ".DOL_URL_ROOT.'/compta/localtax/reglement.php');
				exit;
			}
			else
			{
				$localtax->error=$accountline->error;
				$db->rollback();
				$mesg='<div class="error">'.$localtax->error.'</div>';
			}
	    }
	    else
	    {
	        $db->rollback();
	        $mesg='<div class="error">'.$localtax->error.'</div>';
	    }
	}
	else
	{
        $mesg='<div class="error">Error try do delete a line linked to a conciliated bank transaction</div>';
	}
}


/*
*	View
*/

llxHeader();

$html = new Form($db);

if ($id)
{
    $vatpayment = new localtax($db);
	$result = $vatpayment->fetch($id);
	if ($result <= 0)
	{
		dol_print_error($db);
		exit;
	}
}


if ($_GET["action"] == 'create')
{
    print "<form name='add' action=\"fiche.php\" method=\"post\">\n";
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';

    print_fiche_titre($langs->transcountry("newLT2Payment",$mysoc->pays_code));

    if ($mesg) print $mesg;

    print '<table class="border" width="100%">';

    print "<tr>";
    print '<td class="fieldrequired">'.$langs->trans("DatePayment").'</td><td>';
    print $html->select_date($datep,"datep",'','','','add');
    print '</td></tr>';

    print '<tr><td class="fieldrequired">'.$langs->trans("DateValue").'</td><td>';
    print $html->select_date($datev,"datev",'','','','add');
    print '</td></tr>';

	// Label
	print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input name="label" size="40" value="'.($_POST["label"]?$_POST["label"]:$langs->transcountry("LT2Payment",$mysoc->pays_code)).'"></td></tr>';

	// Amount
	print '<tr><td class="fieldrequired">'.$langs->trans("Amount").'</td><td><input name="amount" size="10" value="'.$_POST["amount"].'"></td></tr>';

    if ($conf->banque->enabled)
    {
		print '<tr><td class="fieldrequired">'.$langs->trans("Account").'</td><td>';
        $html->select_comptes($_POST["accountid"],"accountid",0,"courant=1",1);  // Affiche liste des comptes courant
        print '</td></tr>';

	    print '<tr><td class="fieldrequired">'.$langs->trans("PaymentMode").'</td><td>';
	    $html->select_types_paiements($_POST["paiementtype"], "paiementtype");
	    print "</td>\n";
	    print "</tr>";
	}
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
    if ($mesg) print $mesg;

	$h = 0;
	$head[$h][0] = DOL_URL_ROOT.'/compta/localtax/fiche.php?id='.$vatpayment->id;
	$head[$h][1] = $langs->trans('Card');
	$head[$h][2] = 'card';
	$h++;

	dol_fiche_head($head, 'card', $langs->trans("VATPayment"), 0, 'payment');


	print '<table class="border" width="100%">';

	print "<tr>";
	print '<td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
	print $vatpayment->ref;
	print '</td></tr>';

	print "<tr>";
	print '<td>'.$langs->trans("DatePayment").'</td><td colspan="3">';
	print dol_print_date($vatpayment->datep,'day');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("DateValue").'</td><td colspan="3">';
	print dol_print_date($vatpayment->datev,'day');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Amount").'</td><td colspan="3">'.price($vatpayment->amount).'</td></tr>';

	if ($conf->banque->enabled)
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

	print '</table>';

	print '</div>';

	/*
	* Boutons d'actions
	*/
	print "<div class=\"tabsAction\">\n";
	if ($vatpayment->rappro == 0)
		print '<a class="butActionDelete" href="fiche.php?id='.$vatpayment->id.'&action=delete">'.$langs->trans("Delete").'</a>';
	else
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("LinkedToAConcialitedTransaction").'">'.$langs->trans("Delete").'</a>';
	print "</div>";
}


$db->close();

llxFooter();

?>
