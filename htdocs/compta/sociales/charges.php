<?php
/* Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/compta/sociales/charges.php
 *		\ingroup    tax
 *		\brief      Social contribution card page
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsocialcontrib.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';

$langs->load("compta");
$langs->load("bills");

$id=GETPOST('id','int');
$action=GETPOST("action");
$confirm=GETPOST('confirm');

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', $id, 'chargesociales','charges');




/* *************************************************************************** */
/*                                                                             */
/* Actions                                                                     */
/*                                                                             */
/* *************************************************************************** */

// Classify paid
if ($action == 'confirm_paid' && $confirm == 'yes')
{
	$chargesociales = new ChargeSociales($db);
	$chargesociales->fetch($id);
	$result = $chargesociales->set_paid($user);
}

// Delete social contribution
if ($action == 'confirm_delete' && $confirm == 'yes')
{
	$chargesociales=new ChargeSociales($db);
	$chargesociales->fetch($id);
	$result=$chargesociales->delete($user);
	if ($result > 0)
	{
		header("Location: index.php");
		exit;
	}
	else
	{
		setEventMessages($chargesociales->error, $chargesociales->errors, 'errors');
	}
}


// Add social contribution
if ($action == 'add' && $user->rights->tax->charges->creer)
{
	$dateech=@dol_mktime(GETPOST('echhour'),GETPOST('echmin'),GETPOST('echsec'),GETPOST('echmonth'),GETPOST('echday'),GETPOST('echyear'));
	$dateperiod=@dol_mktime(GETPOST('periodhour'),GETPOST('periodmin'),GETPOST('periodsec'),GETPOST('periodmonth'),GETPOST('periodday'),GETPOST('periodyear'));
    $amount=GETPOST('amount');
    $actioncode=GETPOST('actioncode');
	if (! $dateech)
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("DateDue")), 'errors');
		$action = 'create';
	}
	elseif (! $dateperiod)
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Period")), 'errors');
		$action = 'create';
	}
	elseif (! $actioncode > 0)
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Type")), 'errors');
		$action = 'create';
	}
	elseif (empty($amount))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Amount")), 'errors');
		$action = 'create';
	}
	elseif (! is_numeric($amount))
	{
		setEventMessage($langs->trans("ErrorFieldMustBeANumeric",$langs->transnoentities("Amount")), 'errors');
		$action = 'create';
	}
	else
	{
		$chargesociales=new ChargeSociales($db);

		$chargesociales->type=$actioncode;
		$chargesociales->lib=GETPOST('label');
		$chargesociales->date_ech=$dateech;
		$chargesociales->periode=$dateperiod;
		$chargesociales->amount=price2num($amount);

		$id=$chargesociales->create($user);
		if ($id <= 0)
		{
			setEventMessages($chargesociales->error, $chargesociales->errors, 'errors');
			$action='create';
		}
	}
}


if ($action == 'update' && ! $_POST["cancel"] && $user->rights->tax->charges->creer)
{
    $dateech=dol_mktime(GETPOST('echhour'),GETPOST('echmin'),GETPOST('echsec'),GETPOST('echmonth'),GETPOST('echday'),GETPOST('echyear'));
    $dateperiod=dol_mktime(GETPOST('periodhour'),GETPOST('periodmin'),GETPOST('periodsec'),GETPOST('periodmonth'),GETPOST('periodday'),GETPOST('periodyear'));
    $amount=GETPOST('amount');
    if (! $dateech)
    {
        setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("DateDue")), 'errors');
        $action = 'edit';
    }
    elseif (! $dateperiod)
    {
        setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Period")), 'errors');
        $action = 'edit';
    }
    elseif (empty($amount))
    {
        setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Amount")), 'errors');
        $action = 'edit';
    }
	else
	{
        $chargesociales=new ChargeSociales($db);
        $result=$chargesociales->fetch($id);

        $chargesociales->lib=GETPOST('label');
        $chargesociales->date_ech=$dateech;
        $chargesociales->periode=$dateperiod;
        $chargesociales->amount=price2num($amount);

        $result=$chargesociales->update($user);
        if ($result <= 0)
        {
            setEventMessage($chargesociales->error, 'errors');
        }
	}
}

// Action clone object
if ($action == 'confirm_clone' && $confirm != 'yes') { $action=''; }

if ($action == 'confirm_clone' && $confirm == 'yes' && ($user->rights->tax->charges->creer))
{
	$db->begin();

	$originalId = $id;

	$object = new ChargeSociales($db);
	$object->fetch($id);

	if ($object->id > 0)
	{
		$object->paye = 0;
		$object->id = $object->ref = null;

		if(GETPOST('clone_for_next_month') != '') {

			$object->date_ech = strtotime('+1month', $object->date_ech);
			$object->periode = strtotime('+1month', $object->periode);
		}

		if ($object->check())
		{
			$id = $object->create($user);
			if ($id > 0)
			{
				$db->commit();
				$db->close();

				header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
				exit;
			}
			else
			{
				$id=$originalId;
				$db->rollback();

				setEventMessages($object->error,$object->errors, 'errors');
			}
		}
	}
	else
	{
		$db->rollback();
		dol_print_error($db,$object->error);
	}
}





/*
 * View
 */

$form = new Form($db);
$formsocialcontrib = new FormSocialContrib($db);

$help_url='EN:Module_Taxes_and_social_contributions|FR:Module Taxes et dividendes|ES:M&oacute;dulo Impuestos y cargas sociales (IVA, impuestos)';
llxHeader("",$langs->trans("SocialContribution"),$help_url);


// Mode creation
if ($action == 'create')
{
	print load_fiche_titre($langs->trans("NewSocialContribution"));

    $var=false;

    print '<form name="charge" method="post" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';

    dol_fiche_head();

	print '<table class="border" width="100%">';
    print "<tr>";
    // Label
    print '<td class="fieldrequired">';
    print $langs->trans("Label");
    print '</td>';
    print '<td align="left"><input type="text" size="34" name="label" class="flat" value="'.GETPOST('label').'"></td>';
    print '</tr>';
    print '<tr>';
    // Type
    print '<td class="fieldrequired">';
    print $langs->trans("Type");
    print '</td>';
    print '<td>';
    $formsocialcontrib->select_type_socialcontrib(GETPOST("actioncode")?GETPOST("actioncode"):'','actioncode',1);
    print '</td>';
    print '</tr>';
	// Date end period
    print '<tr>';
    print '<td class="fieldrequired">';
    print $langs->trans("PeriodEndDate");
    print '</td>';
   	print '<td>';
    print $form->select_date(! empty($dateperiod)?$dateperiod:'-1', 'period', 0, 0, 0, 'charge', 1);
	print '</td>';
    print '</tr>';
    // Amount
    print '<tr>';
    print '<td class="fieldrequired">';
    print $langs->trans("Amount");
    print '</td>';
	print '<td><input type="text" size="6" name="amount" class="flat" value="'.GETPOST('amount').'"></td>';
    print '</tr>';
    // Date due
    print '<tr>';
    print '<td class="fieldrequired">';
    print $langs->trans("DateDue");
    print '</td>';
    print '<td>';
    print $form->select_date(! empty($dateech)?$dateech:'-1', 'ech', 0, 0, 0, 'charge', 1);
	print '</td>';
    print "</tr>\n";

    print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
	print '<div>';

    print '</form>';
}

/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */
if ($id > 0)
{
	$object = new ChargeSociales($db);
    $result=$object->fetch($id);

	if ($result > 0)
	{
		$head=tax_prepare_head($object);

		dol_fiche_head($head, 'card', $langs->trans("SocialContribution"),0,'bill');

		// Clone confirmation
		if ($action === 'clone')
		{
			$formclone=array(
				array('type' => 'checkbox', 'name' => 'clone_for_next_month','label' => $langs->trans("CloneTaxForNextMonth"), 'value' => 1),

			);

		    print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('CloneTax'),$langs->trans('ConfirmCloneTax',$object->ref),'confirm_clone',$formclone,'yes');
		}

		// Confirmation de la suppression de la charge
		if ($action == 'paid')
		{
			$text=$langs->trans('ConfirmPaySocialContribution');
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id,$langs->trans('PaySocialContribution'),$text,"confirm_paid",'','',2);
		}

		if ($action == 'delete')
		{
			$text=$langs->trans('ConfirmDeleteSocialContribution');
			print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id,$langs->trans('DeleteSocialContribution'),$text,'confirm_delete','','',2);
		}

		if ($action == 'edit')
		{
			print "<form name=\"charge\" action=\"charges.php?id=$object->id&amp;action=update\" method=\"post\">";
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		}

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="2">';
		print $form->showrefnav($object,'id');
		print "</td></tr>";

		// Label
		if ($action == 'edit')
		{
			print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">';
			print '<input type="text" name="label" size="40" value="'.$object->lib.'">';
			print '</td></tr>';
		}
		else
		{
			print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$object->lib.'</td></tr>';
		}

		// Type
		print "<tr><td>".$langs->trans("Type")."</td><td>".$object->type_libelle."</td>";

		$rowspan=5;
		print '<td rowspan="'.$rowspan.'" valign="top">';

		/*
		 * Payments
		 */
		$sql = "SELECT p.rowid, p.num_paiement, datep as dp, p.amount,";
		$sql.= "c.code as type_code,c.libelle as paiement_type";
		$sql.= " FROM ".MAIN_DB_PREFIX."paiementcharge as p";
		$sql.= ", ".MAIN_DB_PREFIX."c_paiement as c ";
		$sql.= ", ".MAIN_DB_PREFIX."chargesociales as cs";
		$sql.= " WHERE p.fk_charge = ".$id;
		$sql.= " AND p.fk_charge = cs.rowid";
		$sql.= " AND cs.entity = ".$conf->entity;
		$sql.= " AND p.fk_typepaiement = c.id";
		$sql.= " ORDER BY dp DESC";

		//print $sql;
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0; $total = 0;
			print '<table class="nobordernopadding" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("RefPayment").'</td>';
			print '<td>'.$langs->trans("Date").'</td>';
			print '<td>'.$langs->trans("Type").'</td>';
      		print '<td align="right">'.$langs->trans("Amount").'</td>';
      		print '<td>&nbsp;</td>';
      		print '</tr>';

			$var=True;
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;
				print "<tr ".$bc[$var]."><td>";
				print '<a href="'.DOL_URL_ROOT.'/compta/payment_sc/card.php?id='.$objp->rowid.'">'.img_object($langs->trans("Payment"),"payment").' '.$objp->rowid.'</a></td>';
				print '<td>'.dol_print_date($db->jdate($objp->dp),'day')."</td>\n";
			        $labeltype=$langs->trans("PaymentType".$objp->type_code)!=("PaymentType".$objp->type_code)?$langs->trans("PaymentType".$objp->type_code):$objp->paiement_type;
                                print "<td>".$labeltype.' '.$objp->num_paiement."</td>\n";
				print '<td align="right">'.price($objp->amount)."</td><td>&nbsp;".$langs->trans("Currency".$conf->currency)."</td>\n";
				print "</tr>";
				$totalpaye += $objp->amount;
				$i++;
			}

			if ($object->paye == 0)
			{
				print "<tr><td colspan=\"2\" align=\"right\">".$langs->trans("AlreadyPaid")." :</td><td align=\"right\"><b>".price($totalpaye)."</b></td><td>&nbsp;".$langs->trans("Currency".$conf->currency)."</td></tr>\n";
				print "<tr><td colspan=\"2\" align=\"right\">".$langs->trans("AmountExpected")." :</td><td align=\"right\" bgcolor=\"#d0d0d0\">".price($object->amount)."</td><td bgcolor=\"#d0d0d0\">&nbsp;".$langs->trans("Currency".$conf->currency)."</td></tr>\n";

				$resteapayer = $object->amount - $totalpaye;

				print "<tr><td colspan=\"2\" align=\"right\">".$langs->trans("RemainderToPay")." :</td>";
				print "<td align=\"right\" bgcolor=\"#f0f0f0\"><b>".price($resteapayer)."</b></td><td bgcolor=\"#f0f0f0\">&nbsp;".$langs->trans("Currency".$conf->currency)."</td></tr>\n";
			}
			print "</table>";
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}
		print "</td>";

		print "</tr>";

    	// Period end date
		print "<tr><td>".$langs->trans("PeriodEndDate")."</td>";
		print "<td>";
		if ($action == 'edit')
		{
			print $form->select_date($object->periode, 'period', 0, 0, 0, 'charge', 1);
		}
		else
		{
			print dol_print_date($object->periode,"day");
		}
		print "</td></tr>";

		// Due date
		if ($action == 'edit')
		{
			print '<tr><td>'.$langs->trans("DateDue")."</td><td>";
			print $form->select_date($object->date_ech, 'ech', 0, 0, 0, 'charge', 1);
			print "</td></tr>";
		}
		else {
			print "<tr><td>".$langs->trans("DateDue")."</td><td>".dol_print_date($object->date_ech,'day')."</td></tr>";
		}

		// Amount
        if ($action == 'edit')
        {
            print '<tr><td>'.$langs->trans("AmountTTC")."</td><td>";
            print '<input type="text" name="amount" size="12" class="flat" value="'.$object->amount.'">';
            print "</td></tr>";
        }
        else {
            print '<tr><td>'.$langs->trans("AmountTTC").'</td><td>'.price($object->amount,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';
        }

		// Status
		print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4, $totalpaye).'</td></tr>';

		print '</table>';

		if ($action == 'edit')
		{
			print '<br><div align="center">';
			print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
			print ' &nbsp; ';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</div';
		}

		if ($action == 'edit') print "</form>\n";

		dol_fiche_end();


		/*
		*   Actions buttons
		*/
		if ($action != 'edit')
		{
			print "<div class=\"tabsAction\">\n";

			// Edit
			if ($object->paye == 0 && $user->rights->tax->charges->creer)
			{
				print "<a class=\"butAction\" href=\"".DOL_URL_ROOT."/compta/sociales/charges.php?id=$object->id&amp;action=edit\">".$langs->trans("Modify")."</a>";
			}

			// Emit payment
			if ($object->paye == 0 && ((price2num($object->amount) < 0 && price2num($resteapayer, 'MT') < 0) || (price2num($object->amount) > 0 && price2num($resteapayer, 'MT') > 0)) && $user->rights->tax->charges->creer)
			{
				print "<a class=\"butAction\" href=\"".DOL_URL_ROOT."/compta/paiement_charge.php?id=$object->id&amp;action=create\">".$langs->trans("DoPayment")."</a>";
			}

			// Classify 'paid'
			if ($object->paye == 0 && round($resteapayer) <=0 && $user->rights->tax->charges->creer)
			{
				print "<a class=\"butAction\" href=\"".DOL_URL_ROOT."/compta/sociales/charges.php?id=$object->id&amp;action=paid\">".$langs->trans("ClassifyPaid")."</a>";
			}

			// Clone
			if ($user->rights->tax->charges->creer)
			{
				print "<a class=\"butAction\" href=\"".dol_buildpath("/compta/sociales/charges.php",1). "?id=$object->id&amp;action=clone\">".$langs->trans("ToClone")."</a>";
			}

			// Delete
			if ($user->rights->tax->charges->supprimer)
			{
				print "<a class=\"butActionDelete\" href=\"".DOL_URL_ROOT."/compta/sociales/charges.php?id=$object->id&amp;action=delete\">".$langs->trans("Delete")."</a>";
			}

			print "</div>";
		}
	}
	else
	{
		/* Social contribution not found */
		dol_print_error('',$object->error);
	}
}


llxFooter();

$db->close();
