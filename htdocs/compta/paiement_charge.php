<?php
/* Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/compta/paiement_charge.php
 *		\ingroup    tax
 *		\brief      Page to add payment of a tax
 *		\version    $Id$
 */

include_once("./pre.inc.php");
include_once(DOL_DOCUMENT_ROOT."/chargesociales.class.php");
include_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");

$langs->load("bills");

$chid=isset($_GET["id"])?$_GET["id"]:$_POST["id"];

// Securite acces client
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}


/*
 * Actions ajoute paiement
 */
if ($_POST["action"] == 'add_paiement')
{
	if ($_POST["cancel"])
	{
		$loc = DOL_URL_ROOT.'/compta/sociales/charges.php?id='.$chid;
		Header("Location: $loc");
		exit;
	}

	$datepaye = dol_mktime(12, 0 , 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);

	if (! $_POST["paiementtype"] > 0)
	{
		$mesg = $langs->trans("ErrorFieldRequired",$langs->transnoentities("PaymentMode"));
		$error++;
	}
	if ($datepaye == '')
	{
		$mesg = $langs->trans("ErrorFieldRequired",$langs->transnoentities("Date"));
		$error++;
	}

	if (! $error)
	{
		$paymentid = 0;

		// Read possible payments
		$amounts = array();
		foreach ($_POST as $key => $value)
		{
			if (substr($key,0,7) == 'amount_')
			{
				$other_chid = substr($key,7);

				$amounts[$other_chid] = $_POST[$key];
			}
		}

		$db->begin();

		// Creation de la ligne paiement
		$paiement = new PaiementCharge($db);
		$paiement->chid         = $chid;
		$paiement->datepaye     = $datepaye;
		$paiement->amounts      = $amounts;   // Tableau de montant
		$paiement->paiementtype = $_POST["paiementtype"];
		$paiement->num_paiement = $_POST["num_paiement"];
		$paiement->note         = $_POST["note"];
		$paymentid = $paiement->create($user);

		if ($paymentid > 0)
		{
			// On determine le montant total du paiement
			$total=0;
			foreach ($paiement->amounts as $key => $value)
			{
				$chid = $key;
				$value = trim($value);
				$amount = price2num(trim($value), 'MT');   // Un round est ok si nb avec '.'
				$total += $amount;
			}

			// Insertion dans llx_bank
			$langs->load("banks");
			$label = $langs->transnoentities("SocialContributionPayment");
			$acc = new Account($db, $_POST["accountid"]);
			$bank_line_id = $acc->addline($paiement->datepaye, $paiement->paiementtype, $label, -abs($total), $paiement->num_paiement, '', $user);

			// Mise a jour fk_bank dans llx_paiementcharge. On connait ainsi le paiement qui a genere l'ecriture bancaire
			if ($bank_line_id > 0)
			{
				$paiement->update_fk_bank($bank_line_id);

				// Mise a jour liens (pour chaque charge concernee par le paiement)
				foreach ($paiement->amounts as $key => $value)
				{
					//$acc->add_url_line($bank_line_id, $chid, DOL_URL_ROOT.'/compta/charges.php?id=', '(socialcontribution)','payment_sc');
					$acc->add_url_line($bank_line_id, $paymentid, DOL_URL_ROOT.'/compta/payment_sc/fiche.php?id=', '(paiement)','payment_sc');
				}

				$db->commit();

				$loc = DOL_URL_ROOT.'/compta/sociales/charges.php?id='.$chid;
				Header("Location: $loc");
				exit;
			}
			else {
				$db->rollback();
				$mesg = "Echec de la creation entree compte: ".$db->error();
			}
		}
		else
		{
			$db->rollback();
			$mesg = "Failed to create payment: paiement_id=$paymentid ".$db->error();
		}
	}

	$_GET["action"]='create';
}


/*
 * Affichage
 */

llxHeader();

$html=new Form($db);


/*
 * Formulaire de creation d'un paiement de charge
 */
if ($_GET["action"] == 'create')
{

	$charge = new ChargeSociales($db);
	$charge->fetch($chid);

	$total = $charge->amount;

	print_fiche_titre($langs->trans("DoPayment"));
	print "<br>\n";

	if ($mesg)
	{
		print "<div class=\"error\">$mesg</div>";
	}

	print '<form name="add_paiement" action="paiement_charge.php" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"id\" value=\"$charge->id\">";
	print '<input type="hidden" name="action" value="add_paiement">';

	print '<table cellspacing="0" class="border" width="100%" cellpadding="2">';

	print "<tr class=\"liste_titre\"><td colspan=\"3\">Charge</td>";

	print '<tr><td>'.$langs->trans("Ref").'</td><td colspan="2">';
	print '<a href="'.DOL_URL_ROOT.'/compta/sociales/charges.php?id='.$chid.'">'.$chid.'</a></td></tr>';
	print '<tr><td>'.$langs->trans("Type")."</td><td colspan=\"2\">".$charge->type_libelle."</td></tr>\n";
	print '<tr><td>'.$langs->trans("Period")."</td><td colspan=\"2\">".dol_print_date($charge->periode,'day')."</td></tr>\n";
	print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$charge->lib."</td></tr>\n";
	print '<tr><td>'.$langs->trans("DateDue")."</td><td colspan=\"2\">".dol_print_date($charge->date_ech,'day')."</td></tr>\n";

	print '<tr><td>'.$langs->trans("AmountTTC")."</td><td colspan=\"2\"><b>".price($charge->amount).'</b> '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

	$sql = "SELECT sum(p.amount) as total";
	$sql.= " FROM ".MAIN_DB_PREFIX."paiementcharge as p";
	$sql.= " WHERE p.fk_charge = ".$chid;
	$resql = $db->query($sql);
	if ($resql)
	{
		$obj=$db->fetch_object($resql);
		$sumpaid = $obj->total;
		$db->free();
	}
	print '<tr><td>'.$langs->trans("AlreadyPaid").'</td><td colspan="2"><b>'.price($sumpaid).'</b> '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
	print "<tr><td valign=\"top\">".$langs->trans("RemainderToPay")."</td><td colspan=\"3\"><b>".price($total - $sumpaid).'</b> '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

	print "<tr class=\"liste_titre\"><td colspan=\"3\">".$langs->trans("Payment").'</td>';

	print "<input type=\"hidden\" name=\"chid\" value=\"$chid\">";

	print '<tr><td class="fieldrequired">'.$langs->trans("Date").'</td><td>';
	$datepayment=empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;
	$html->select_date($datepayment,'','','','',"add_paiement",1,1);
	print "</td>";
	print '<td>'.$langs->trans("Comments").'</td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("PaymentMode").'</td><td>';
	$html->select_types_paiements($charge->paiementtype, "paiementtype");
	print "</td>\n";

	print '<td rowspan="3" valign="top"><textarea name="comment" wrap="soft" cols="40" rows="'.ROWS_3.'"></textarea></td></tr>';

	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans('AccountToCredit').'</td>';
	print '<td>';
	$html->select_comptes($charge->accountid, "accountid", 0, "courant=1");  // Affiche liste des comptes courant
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Numero');
	print ' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
	print "<td><input name=\"num_paiement\" type=\"text\"></td></tr>\n";

	/*
 	 * Autres charges impayees
	 */
	$num = 1;
	$i = 0;
	print '<tr><td colspan="3">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	//print '<td>'.$langs->trans("SocialContribution").'</td>';
	print '<td align="left">'.$langs->trans("DateDue").'</td>';
	print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
	print '<td align="right">'.$langs->trans("AlreadyPaid").'</td>';
	print '<td align="right">'.$langs->trans("RemainderToPay").'</td>';
	print '<td align="right">'.$langs->trans("Amount").'</td>';
	print "</tr>\n";

	$var=True;
	$total=0;
	$totalrecu=0;

	while ($i < $num)
	{
		//$objp = $db->fetch_object($result);
		$objp = $charge;

		$var=!$var;

		print "<tr $bc[$var]>";

		//print '<td>'.$charge->getNomUrl(1)."</td>\n";

		if ($objp->date_ech > 0)
		{
			print "<td align=\"left\">".dol_print_date($objp->date_ech,'day')."</td>\n";
		}
		else
		{
			print "<td align=\"center\"><b>!!!</b></td>\n";
		}

		print '<td align="right">'.price($objp->amount)."</td>";

		print '<td align="right">'.price($sumpaid)."</td>";

		print '<td align="right">'.price($objp->amount-$sumpaid)."</td>";

		print '<td align="right">';
		if ($sumpaid < $objp->amount)
		{
			$namef = "amount_".$objp->id;
			print '<input type="text" size="8" name="'.$namef.'">';
		}
		else
		{
			print '-';
		}
		print "</td>";

		print "</tr>\n";
		$total+=$objp->total;
		$total_ttc+=$objp->total_ttc;
		$totalrecu+=$objp->am;
		$i++;
	}
	if ($i > 1)
	{
		// Print total
		print "<tr ".$bc[!$var].">";
		print '<td colspan="2" align="left">'.$langs->trans("TotalTTC").':</td>';
		print "<td align=\"right\"><b>".price($total_ttc)."</b></td>";
		print "<td align=\"right\"><b>".price($totalrecu)."</b></td>";
		print "<td align=\"right\"><b>".price($total_ttc - $totalrecu)."</b></td>";
		print '<td align="center">&nbsp;</td>';
		print "</tr>\n";
	}
	print "</table></td></tr>\n";
	//		}
	//	      $db->free();
	//	    }
	//	  else
	//	    {
	//	      print $sql ."<br>".$db->error();
	//	    }
	/*
	*
	*/

	print "</table>";

	print '<br><center>';
	//print '<tr><td colspan="3" align="center">';
	print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print '&nbsp; &nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';

	print '</center>';
	//print '</td></tr>';
	print "</form>\n";
	//    }
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
