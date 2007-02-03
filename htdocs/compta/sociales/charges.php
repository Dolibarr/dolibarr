<?php
/* Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/compta/sociales/charges.php
		\ingroup    tax
		\brief      Fiche d'une charge sociale
		\version    $Revision$
*/

require("./pre.inc.php");
require(DOL_DOCUMENT_ROOT."/chargesociales.class.php");

$langs->load("compta");
$langs->load("bills");

// Protection
$user->getrights('compta');

if (!$user->admin && !$user->rights->tax->charges)
  accessforbidden();

$chid=isset($_GET["id"])?$_GET["id"]:$_POST["id"];




/* *************************************************************************** */
/*                                                                             */
/* Actions                                                                     */
/*                                                                             */
/* *************************************************************************** */

/*
 * 	Classer payé
 */
if ($_POST["action"] == 'confirm_payed')
{
	if ($_POST["confirm"] == 'yes')
	{
		$chargesociales = new ChargeSociales($db);
		$result = $chargesociales->set_payed($chid);
	}
	else
	{
		$_GET["action"]='';
	}
}

/*
 *	Suppression d'une charge sociale
 */
if ($_POST["action"] == 'confirm_delete')
{
	if ($_POST["confirm"] == 'yes')
	{
		$chargesociales=new ChargeSociales($db);
		$chargesociales->id=$_GET["id"];
		$result=$chargesociales->delete($user);
		if ($result > 0)
		{
			Header("Location: index.php");
			exit;
		}
		else
		{
			$mesg='<div class="error">'.$chargesociales->error.'</div>';
		}
	}
	else
	{
		$_GET['action']='';
	}
}



llxHeader();

/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */
if ($chid > 0)
{
	$html = new Form($db);

	$cha = new ChargeSociales($db);

	/*
	*   Charge
	*/
	if ($cha->fetch($chid) > 0)
	{
		if ($mesg) print $mesg.'<br>';
	
		$h = 0;
		$head[$h][0] = DOL_URL_ROOT.'/compta/sociales/charges.php?id='.$cha->id;
		$head[$h][1] = $langs->trans('Card');
		$head[$h][2] = 'card';
		$h++;

		dolibarr_fiche_head($head, 'card', $langs->trans("SocialContribution"));

		/*
		* Confirmation de la suppression de la charge
		*
		*/
		if ($_GET["action"] == 'payed')
		{
			$text=$langs->trans('ConfirmPaySocialContribution');
			$html->form_confirm($_SERVER["PHP_SELF"]."?id=$cha->id&amp;action=confirm_payed",$langs->trans('PaySocialContribution'),$text,"confirm_payed");
			print '<br>';
		}

		if ($_GET['action'] == 'delete')
		{
			$text=$langs->trans('ConfirmDeleteSocialContribution');
			$html->form_confirm($_SERVER['PHP_SELF'].'?id='.$cha->id,$langs->trans('DeleteSocialContribution'),$text,'confirm_delete');
			print '<br />';
		}

		print "<form action=\"charges.php?id=$cha->id&amp;action=update\" method=\"post\">";

		print '<table class="border" width="100%">';

		print "<tr><td>".$langs->trans("Ref").'</td><td colspan="2">'.$cha->id."</td></tr>";

		print "<tr><td>".$langs->trans("Type")."</td><td>$cha->type_libelle</td><td>".$langs->trans("Payments")."</td></tr>";

		print "<tr><td>".$langs->trans("Period")."</td>";
		print "<td>";
		if ($cha->paye==0 && $_GET['action'] == 'edit')
		{
			print "<input type=\"text\" name=\"period\" value=\"".strftime("%Y%m%d",$cha->period)."\">";
		}
		else
		{
			print dolibarr_print_date($cha->periode,"%Y");
		}
		print "</td>";
		
		print '<td rowspan="5" valign="top">';

		/*
		* Paiements
		*/
		$sql = "SELECT ".$db->pdate("datep")." as dp, p.amount,";
		$sql .= "c.libelle as paiement_type, p.num_paiement, p.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."paiementcharge as p, ".MAIN_DB_PREFIX."c_paiement as c ";
		$sql .= " WHERE p.fk_charge = ".$chid." AND p.fk_typepaiement = c.id";
		$sql .= " ORDER BY dp DESC";

		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$i = 0; $total = 0;
			echo '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Date").'</td><td>'.$langs->trans("Type").'</td>';
			print '<td align="right">'.$langs->trans("Amount").'</td><td>&nbsp;</td></tr>';

			$var=True;
			while ($i < $num)
			{
				$objp = $db->fetch_object($result);
				$var=!$var;
				print "<tr $bc[$var]><td>";
				print img_object($langs->trans("Payment"),"payment").' ';
				print dolibarr_print_date($objp->dp)."</td>\n";
				print "<td>$objp->paiement_type $objp->num_paiement</td>\n";
				print '<td align="right">'.price($objp->amount)."</td><td>".$langs->trans("Currency".$conf->monnaie)."</td>\n";
				print "</tr>";
				$totalpaye += $objp->amount;
				$i++;
			}

			if ($cha->paye == 0)
			{
				print "<tr><td colspan=\"2\" align=\"right\">".$langs->trans("AlreadyPayed")." :</td><td align=\"right\"><b>".price($totalpaye)."</b></td><td>".$langs->trans("Currency".$conf->monnaie)."</td></tr>\n";
				print "<tr><td colspan=\"2\" align=\"right\">".$langs->trans("AmountExpected")." :</td><td align=\"right\" bgcolor=\"#d0d0d0\">".price($cha->amount)."</td><td bgcolor=\"#d0d0d0\">".$langs->trans("Currency".$conf->monnaie)."</td></tr>\n";

				$resteapayer = $cha->amount - $totalpaye;

				print "<tr><td colspan=\"2\" align=\"right\">".$langs->trans("RemainderToPay")." :</td>";
				print "<td align=\"right\" bgcolor=\"#f0f0f0\"><b>".price($resteapayer)."</b></td><td bgcolor=\"#f0f0f0\">".$langs->trans("Currency".$conf->monnaie)."</td></tr>\n";
			}
			print "</table>";
			$db->free();
		}
		else
		{
			dolibarr_print_error($db);
		}
		print "</td>";

		print "</tr>";

		if ($cha->paye==0 && $_GET['action'] == 'edit')
		{
			print '<tr><td>'.$langs->trans("Label").'</td><td>';
			print '<input type="text" name="desc" size="40" value="'.$cha->lib.'">';
			print '</td></tr>';
			print '<tr><td>'.$langs->trans("DateDue")."</td><td>";
			print "<input type=\"text\" name=\"amount\" value=\"".strftime("%Y%m%d",$cha->date_ech)."\">";
			print "</td></tr>";
		}
		else {
			print '<tr><td>'.$langs->trans("Label").'</td><td>'.$cha->lib.'</td></tr>';
			print "<tr><td>".$langs->trans("DateDue")."</td><td>".dolibarr_print_date($cha->date_ech,'day')."</td></tr>";
		}
		print '<tr><td>'.$langs->trans("AmountTTC").'</td><td>'.price($cha->amount).'</td></tr>';
	
		print '<tr><td>'.$langs->trans("Status").'</td><td>'.$cha->getLibStatut(4).'</td></tr>';
		print '</table>';


		print "</form>\n";

		print '</div>';

		if (! $_GET["action"])
		{
			/*
			*   Boutons actions
			*/

			print "<div class=\"tabsAction\">\n";

			// Editer
			if ($cha->paye == 0 && $user->rights->tax->charges->creer)
			{
				print "<a class=\"tabAction\" href=\"".DOL_URL_ROOT."/compta/sociales/charges.php?id=$cha->id&amp;action=edit\">".$langs->trans("Edit")."</a>";
			}

			// Emettre paiement
			if ($cha->paye == 0 && round($resteapayer) > 0 && $user->rights->tax->charges->creer)
			{
				print "<a class=\"tabAction\" href=\"".DOL_URL_ROOT."/compta/paiement_charge.php?id=$cha->id&amp;action=create\">".$langs->trans("DoPaiement")."</a>";
			}

			// Classer 'payé'
			if ($cha->paye == 0 && round($resteapayer) <=0 && $user->rights->tax->charges->creer)
			{
				print "<a class=\"tabAction\" href=\"".DOL_URL_ROOT."/compta/sociales/charges.php?id=$cha->id&amp;action=payed\">".$langs->trans("ClassifyPayed")."</a>";
			}

			// Supprimer
			if ($cha->paye == 0 && $totalpaye <=0 && $user->rights->tax->charges->supprimer)
			{
				print "<a class=\"butDelete\" href=\"".DOL_URL_ROOT."/compta/sociales/charges.php?id=$cha->id&amp;action=delete\">".$langs->trans("Delete")."</a>";
			}

			print "</div>";
		}
	}
	else
	{
		/* Charge non trouvée */
		dolibarr_print_error('',$cha->error);
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
