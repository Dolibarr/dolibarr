<?php
/* Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
        \file       htdocs/compta/sociales/charges.php
		\ingroup    tax
		\brief      Fiche d'une charge sociale
		\version    $Id$
*/

require("./pre.inc.php");
require(DOL_DOCUMENT_ROOT."/chargesociales.class.php");

$langs->load("compta");
$langs->load("bills");

$chid=isset($_GET["id"])?$_GET["id"]:$_POST["id"];

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');





/* *************************************************************************** */
/*                                                                             */
/* Actions                                                                     */
/*                                                                             */
/* *************************************************************************** */

/*
 * 	Classer paye
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


/*
 * Ajout d'une charge sociale
 */

if ($_POST["action"] == 'add' && $user->rights->tax->charges->creer)
{
	$dateech=@dol_mktime($_POST["echhour"],$_POST["echmin"],$_POST["echsec"],$_POST["echmonth"],$_POST["echday"],$_POST["echyear"]);
	if (! $dateech)
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("DateDue")).'</div>';
		$_GET["action"] = 'create';
	}
	elseif (! $_POST["period"])
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Period")).'</div>';
		$_GET["action"] = 'create';
	}
	elseif (! $_POST["amount"])
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Amount")).'</div>';
		$_GET["action"] = 'create';
	}
	else
	{
		$chargesociales=new ChargeSociales($db);

		$chargesociales->type=$_POST["actioncode"];
		$chargesociales->lib=$_POST["label"];
		$chargesociales->date_ech=$dateech;
		$chargesociales->periode=$_POST["period"];
		$chargesociales->amount=$_POST["amount"];

		$chid=$chargesociales->create($user);
		if ($chid > 0)
		{
			//$mesg='<div class="ok">'.$langs->trans("SocialContributionAdded").'</div>';
		}
		else
		{
			$mesg='<div class="error">'.$chargesociales->error.'</div>';
		}
	}
}


if ($_GET["action"] == 'update' && ! $_POST["cancel"] && $user->rights->tax->charges->creer)
{
	$dateech=@dol_mktime($_POST["echhour"],$_POST["echmin"],$_POST["echsec"],$_POST["echmonth"],$_POST["echday"],$_POST["echyear"]);
	if (! $dateech)
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("DateDue")).'</div>';
		$_GET["action"] = 'edit';
	}
	elseif (! $_POST["period"])
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Period")).'</div>';
		$_GET["action"] = 'edit';
	}
	else
	{
		$chargesociales=new ChargeSociales($db);
		$result=$chargesociales->fetch($_GET["id"]);

		$chargesociales->lib=$_POST["label"];
		$chargesociales->date_ech=$dateech;
		$chargesociales->periode=$_POST["period"];

		$result=$chargesociales->update($user);
		if ($result > 0)
		{
			//$mesg='<div class="ok">'.$langs->trans("SocialContributionAdded").'</div>';
		}
		else
		{
			$mesg='<div class="error">'.$chargesociales->error.'</div>';
		}
	}
}

llxHeader();

$html = new Form($db);

/*
 * Mode creation
 *
 */
if ($_GET["action"] == 'create')
{
	print_fiche_titre($langs->trans("NewSocialContribution"));
	print "<br>\n";

	if ($mesg) print $mesg.'<br>';

    $var=false;

    print '<form name="charge" method="post" action="'.DOL_URL_ROOT.'/compta/sociales/charges.php">';
    print '<input type="hidden" name="action" value="add">';

	print "<table class=\"noborder\" width=\"100%\">";
    print "<tr class=\"liste_titre\">";
    print '<td>';
    print '&nbsp;';
    print '</td><td align="left">';
    print $langs->trans("DateDue");
    print '</td><td align="left">';
    print $langs->trans("Period");
    print '</td><td align="left">';
    print $langs->trans("Type");
    print '</td><td align="left">';
    print $langs->trans("Label");
    print '</td><td align="right">';
    print $langs->trans("Amount");
    print '</td><td align="center">';
    print '&nbsp;';
    print '</td>';
    print "</tr>\n";

    print '<tr '.$bc[$var].' valign="top">';
    print '<td>&nbsp;</td>';
    print '<td>';
    print $html->select_date('-1', 'ech', 0, 0, 0, 'charge', 1);
	print '</td>';
    print '<td><input type="text" size="8" name="period"><br>YYYYMMDD</td>';

    print '<td align="left">';
    $html->select_type_socialcontrib();
    print '</td>';

    print '<td align="left"><input type="text" size="34" name="label" class="flat"></td>';

    print '<td align="right"><input type="text" size="6" name="amount" class="flat"></td>';

    print '<td align="center"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
    print '</tr>';

    print '</table>';

    print '</form>';
}

/* *************************************************************************** */
/*                                                                             */
/* Mode fiche                                                                  */
/*                                                                             */
/* *************************************************************************** */
if ($chid > 0)
{
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

		dol_fiche_head($head, 'card', $langs->trans("SocialContribution"));

		/*
		* Confirmation de la suppression de la charge
		*
		*/
		if ($_GET["action"] == 'payed')
		{
			$text=$langs->trans('ConfirmPaySocialContribution');
			$ret=$html->form_confirm($_SERVER["PHP_SELF"]."?id=$cha->id&amp;action=confirm_payed",$langs->trans('PaySocialContribution'),$text,"confirm_payed");
			if ($ret == 'html') print '<br>';
		}

		if ($_GET['action'] == 'delete')
		{
			$text=$langs->trans('ConfirmDeleteSocialContribution');
			$ret=$html->form_confirm($_SERVER['PHP_SELF'].'?id='.$cha->id,$langs->trans('DeleteSocialContribution'),$text,'confirm_delete');
			if ($ret == 'html') print '<br>';
		}

		if ($_GET['action'] == 'edit') print "<form name=\"charge\" action=\"charges.php?id=$cha->id&amp;action=update\" method=\"post\">";

		print '<table class="border" width="100%">';

		print "<tr><td>".$langs->trans("Ref").'</td><td colspan="2">'.$cha->id."</td></tr>";

		print "<tr><td>".$langs->trans("Type")."</td><td>$cha->type_libelle</td><td>".$langs->trans("Payments")."</td></tr>";

		print "<tr><td>".$langs->trans("Period")."</td>";
		print "<td>";
		if ($cha->paye==0 && $_GET['action'] == 'edit')
		{
			print "<input type=\"text\" name=\"period\" value=\"".dol_print_date($cha->periode,"%Y%m%d")."\"> (YYYYMMDD)";
		}
		else
		{
			print dol_print_date($cha->periode,"%Y");
		}
		print "</td>";

		print '<td rowspan="5" valign="top">';

		/*
		* Paiements
		*/
		$sql = "SELECT ".$db->pdate("datep")." as dp, p.amount,";
		$sql.= "c.libelle as paiement_type, p.num_paiement, p.rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."paiementcharge as p";
		$sql.= ", ".MAIN_DB_PREFIX."c_paiement as c ";
		$sql.= ", ".MAIN_DB_PREFIX."chargesociales as s";
		$sql.= " WHERE p.fk_charge = ".$chid;
		$sql.= " AND p.fk_charge = s.rowid";
		$sql.= " AND s.entity = ".$conf->entity;
		$sql.= " AND p.fk_typepaiement = c.id";
		$sql.= " ORDER BY dp DESC";

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
				print dol_print_date($objp->dp)."</td>\n";
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
			dol_print_error($db);
		}
		print "</td>";

		print "</tr>";

		if ($cha->paye==0 && $_GET['action'] == 'edit')
		{
			print '<tr><td>'.$langs->trans("Label").'</td><td>';
			print '<input type="text" name="label" size="40" value="'.$cha->lib.'">';
			print '</td></tr>';
			print '<tr><td>'.$langs->trans("DateDue")."</td><td>";
			print $html->select_date($cha->date_ech, 'ech', 0, 0, 0, 'charge', 1);
			print "</td></tr>";
		}
		else {
			print '<tr><td>'.$langs->trans("Label").'</td><td>'.$cha->lib.'</td></tr>';
			print "<tr><td>".$langs->trans("DateDue")."</td><td>".dol_print_date($cha->date_ech,'day')."</td></tr>";
		}
		print '<tr><td>'.$langs->trans("AmountTTC").'</td><td>'.price($cha->amount).'</td></tr>';

		print '<tr><td>'.$langs->trans("Status").'</td><td>'.$cha->getLibStatut(4).'</td></tr>';

		if ($_GET['action'] == 'edit')
		{
			print '<tr><td colspan="3" align="center">';
			print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
			print ' &nbsp; ';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</td></tr>';
		}

		print '</table>';

		if ($_GET['action'] == 'edit') print "</form>\n";

		print '</div>';


		/*
		*   Boutons actions
		*/
		if (! $_GET["action"] || $_GET["action"] == 'update')
		{
			print "<div class=\"tabsAction\">\n";

			// Editer
			if ($cha->paye == 0 && $user->rights->tax->charges->creer)
			{
				print "<a class=\"butAction\" href=\"".DOL_URL_ROOT."/compta/sociales/charges.php?id=$cha->id&amp;action=edit\">".$langs->trans("Modify")."</a>";
			}

			// Emettre paiement
			if ($cha->paye == 0 && round($resteapayer) > 0 && $user->rights->tax->charges->creer)
			{
				print "<a class=\"butAction\" href=\"".DOL_URL_ROOT."/compta/paiement_charge.php?id=$cha->id&amp;action=create\">".$langs->trans("DoPayment")."</a>";
			}

			// Classer 'payé'
			if ($cha->paye == 0 && round($resteapayer) <=0 && $user->rights->tax->charges->creer)
			{
				print "<a class=\"butAction\" href=\"".DOL_URL_ROOT."/compta/sociales/charges.php?id=$cha->id&amp;action=payed\">".$langs->trans("ClassifyPayed")."</a>";
			}

			// Supprimer
			if ($cha->paye == 0 && $totalpaye <=0 && $user->rights->tax->charges->supprimer)
			{
				print "<a class=\"butActionDelete\" href=\"".DOL_URL_ROOT."/compta/sociales/charges.php?id=$cha->id&amp;action=delete\">".$langs->trans("Delete")."</a>";
			}

			print "</div>";
		}
	}
	else
	{
		/* Charge non trouvé */
		dol_print_error('',$cha->error);
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
