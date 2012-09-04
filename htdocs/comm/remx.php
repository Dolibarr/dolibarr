<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
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
 *	    \file       htdocs/comm/remx.php
 *      \ingroup    societe
 *		\brief      Page to edit absolute discounts for a customer
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

$langs->load("orders");
$langs->load("bills");
$langs->load("companies");

$action=GETPOST('action','alpha');
$backtopage=GETPOST('backtopage','alpha');

// Security check
$socid = GETPOST('id','int');
if ($user->societe_id > 0)
{
	$socid = $user->societe_id;
}


/*
 * Actions
 */

if (GETPOST('cancel') && ! empty($backtopage))
{
     header("Location: ".$backtopage);
     exit;
}

if ($action == 'confirm_split' && GETPOST("confirm") == 'yes')
{
	//if ($user->rights->societe->creer)
	//if ($user->rights->facture->creer)

	$error=0;
	$remid=GETPOST("remid")?GETPOST("remid"):0;
	$discount=new DiscountAbsolute($db);
	$res=$discount->fetch($remid);
	if (! $res > 0)
	{
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFailedToLoadDiscount").'</div>';
	}
	if (! $error && price2num($_POST["amount_ttc_1"]+$_POST["amount_ttc_2"]) != $discount->amount_ttc)
	{
		$error++;
		$mesg='<div class="error">'.$langs->trans("TotalOfTwoDiscountMustEqualsOriginal").'</div>';
	}
	if (! $error && $discount->fk_facture_line)
	{
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorCantSplitAUsedDiscount").'</div>';
	}
	if (! $error)
	{
		$newdiscount1=new DiscountAbsolute($db);
		$newdiscount2=new DiscountAbsolute($db);
		$newdiscount1->fk_facture_source=$discount->fk_facture_source;
		$newdiscount2->fk_facture_source=$discount->fk_facture_source;
		$newdiscount1->fk_facture=$discount->fk_facture;
		$newdiscount2->fk_facture=$discount->fk_facture;
		$newdiscount1->fk_facture_line=$discount->fk_facture_line;
		$newdiscount2->fk_facture_line=$discount->fk_facture_line;
		if ($discount->description == '(CREDIT_NOTE)')
		{
			$newdiscount1->description=$discount->description;
			$newdiscount2->description=$discount->description;
		}
		else
		{
			$newdiscount1->description=$discount->description.' (1)';
			$newdiscount2->description=$discount->description.' (2)';
		}
		$newdiscount1->fk_user=$discount->fk_user;
		$newdiscount2->fk_user=$discount->fk_user;
		$newdiscount1->fk_soc=$discount->fk_soc;
		$newdiscount2->fk_soc=$discount->fk_soc;
		$newdiscount1->datec=$discount->datec;
		$newdiscount2->datec=$discount->datec;
		$newdiscount1->tva_tx=$discount->tva_tx;
		$newdiscount2->tva_tx=$discount->tva_tx;
		$newdiscount1->amount_ttc=$_POST["amount_ttc_1"];
		$newdiscount2->amount_ttc=price2num($discount->amount_ttc-$newdiscount1->amount_ttc);
		$newdiscount1->amount_ht=price2num($newdiscount1->amount_ttc/(1+$newdiscount1->tva_tx/100),'MT');
		$newdiscount2->amount_ht=price2num($newdiscount2->amount_ttc/(1+$newdiscount2->tva_tx/100),'MT');
		$newdiscount1->amount_tva=price2num($newdiscount1->amount_ttc-$newdiscount2->amount_ht);
		$newdiscount2->amount_tva=price2num($newdiscount2->amount_ttc-$newdiscount2->amount_ht);

		$db->begin();
		$discount->fk_facture_source=0;	// This is to delete only the require record (that we will recreate with two records) and not all family with same fk_facture_source
		$res=$discount->delete($user);
		$newid1=$newdiscount1->create($user);
		$newid2=$newdiscount2->create($user);
		if ($res > 0 && $newid1 > 0 && $newid2 > 0)
		{
			$db->commit();
			header("Location: ".$_SERVER["PHP_SELF"].'?id='.$_REQUEST['id']);	// To avoid pb whith back
			exit;
		}
		else
		{
			$db->rollback();
		}
	}
}

if ($action == 'setremise')
{
	//if ($user->rights->societe->creer)
	//if ($user->rights->facture->creer)

	if (price2num($_POST["amount_ht"]) > 0)
	{
		$error=0;
		if (empty($_POST["desc"]))
		{
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("ReasonDiscount")).'</div>';
			$error++;
		}

		if (! $error)
		{
			$soc = new Societe($db);
			$soc->fetch($_GET["id"]);
			$discountid=$soc->set_remise_except($_POST["amount_ht"],$user,$_POST["desc"],$_POST["tva_tx"]);

			if ($discountid > 0)
			{
			    if (! empty($backtopage))
			    {
			        header("Location: ".$backtopage.'&discountid='.$discountid);
			        exit;
			    }
				else
				{
				    header("Location: remx.php?id=".$_GET["id"]);
				    exit;
				}
			}
			else
			{
				$error++;
				$mesg='<div class="error">'.$soc->error.'</div>';
			}
		}
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldFormat",$langs->trans("NewGlobalDiscount")).'</div>';
	}
}

if (GETPOST("action") == 'confirm_remove' && GETPOST("confirm")=='yes')
{
	//if ($user->rights->societe->creer)
	//if ($user->rights->facture->creer)

	$db->begin();

	$discount = new DiscountAbsolute($db);
	$result=$discount->fetch(GETPOST("remid"));
	$result=$discount->delete($user);
	if ($result > 0)
	{
		$db->commit();
		header("Location: ".$_SERVER["PHP_SELF"].'?id='.GETPOST('id','int'));	// To avoid pb whith back
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$discount->error.'</div>';
		$db->rollback();
	}
}


/*
 * View
 */

$form=new Form($db);
$facturestatic=new Facture($db);

llxHeader('',$langs->trans("GlobalDiscount"));

if ($socid > 0)
{
	dol_htmloutput_mesg($mesg);

	// On recupere les donnees societes par l'objet
	$objsoc = new Societe($db);
	$objsoc->id=$socid;
	$objsoc->fetch($socid);

	/*
	 * Affichage onglets
	 */
	$head = societe_prepare_head($objsoc);

	dol_fiche_head($head, 'absolutediscount', $langs->trans("ThirdParty"),0,'company');


	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$objsoc->id.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="setremise">';
    print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print '<table class="border" width="100%">';

	// Name
	print '<tr><td width="38%">'.$langs->trans('Name').'</td>';
	print '<td>';
	print $form->showrefnav($objsoc,'id','',1,'rowid','nom');
	print '</td></tr>';

	// Calcul avoirs en cours
	$remise_all=$remise_user=0;
	$sql = "SELECT SUM(rc.amount_ht) as amount, rc.fk_user";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe_remise_except as rc";
	$sql.= " WHERE rc.fk_soc =". $objsoc->id;
	$sql.= " AND (fk_facture_line IS NULL AND fk_facture IS NULL)";
	$sql.= " GROUP BY rc.fk_user";
	$resql=$db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		$remise_all+=$obj->amount;
		if ($obj->fk_user == $user->id) $remise_user+=$obj->amount;
	}
	else
	{
		dol_print_error($db);
	}

	print '<tr><td width="38%">'.$langs->trans("CustomerAbsoluteDiscountAllUsers").'</td>';
	print '<td>'.$remise_all.'&nbsp;'.$langs->trans("Currency".$conf->currency).' '.$langs->trans("HT").'</td></tr>';

	print '<tr><td>'.$langs->trans("CustomerAbsoluteDiscountMy").'</td>';
	print '<td>'.$remise_user.'&nbsp;'.$langs->trans("Currency".$conf->currency).' '.$langs->trans("HT").'</td></tr>';
	print '</table>';
	print '<br>';

	print_fiche_titre($langs->trans("NewGlobalDiscount"),'','');
	print '<table class="border" width="100%">';
	print '<tr><td width="38%">'.$langs->trans("AmountHT").'</td>';
	print '<td><input type="text" size="5" name="amount_ht" value="'.$_POST["amount_ht"].'">&nbsp;'.$langs->trans("Currency".$conf->currency).'</td></tr>';
	print '<tr><td width="38%">'.$langs->trans("VAT").'</td>';
	print '<td>';
	print $form->load_tva('tva_tx',GETPOST('tva_tx'),$mysoc,$objsoc);
	print '</td></tr>';
	print '<tr><td>'.$langs->trans("NoteReason").'</td>';
	print '<td><input type="text" size="60" name="desc" value="'.$_POST["desc"].'"></td></tr>';

	print "</table>";

	print '<center>';
	print '<input type="submit" class="button" name="submit" value="'.$langs->trans("AddGlobalDiscount").'">';
    if (! empty($backtopage))
    {
        print '&nbsp; &nbsp; ';
	    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
    }
	print '</center>';

	print '</form>';

	dol_fiche_end();

	print '<br>';

	if ($_GET['action'] == 'remove')
	{
		$ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$objsoc->id.'&remid='.$_GET["remid"], $langs->trans('RemoveDiscount'), $langs->trans('ConfirmRemoveDiscount'), 'confirm_remove', '', 0, 1);
	}

	/*
	 * Liste remises fixes restant en cours (= liees a acune facture ni ligne de facture)
	 */
	$sql = "SELECT rc.rowid, rc.amount_ht, rc.amount_tva, rc.amount_ttc, rc.tva_tx,";
	$sql.= " rc.datec as dc, rc.description,";
	$sql.= " rc.fk_facture_source,";
	$sql.= " u.login, u.rowid as user_id,";
	$sql.= " fa.facnumber as ref, fa.type as type";
	$sql.= " FROM  ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."societe_remise_except as rc";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as fa ON rc.fk_facture_source = fa.rowid";
	$sql.= " WHERE rc.fk_soc =". $objsoc->id;
	$sql.= " AND u.rowid = rc.fk_user";
	$sql.= " AND (rc.fk_facture_line IS NULL AND rc.fk_facture IS NULL)";
	$sql.= " ORDER BY rc.datec DESC";

	$resql=$db->query($sql);
	if ($resql)
	{
		print_titre($langs->trans("DiscountStillRemaining"));
		print '<table width="100%" class="noborder">';
		print '<tr class="liste_titre">';
		print '<td width="120" align="left">'.$langs->trans("Date").'</td>';	// Need 120+ for format with AM/PM
		print '<td align="left">'.$langs->trans("ReasonDiscount").'</td>';
		print '<td width="150" nowrap="nowrap">'.$langs->trans("ConsumedBy").'</td>';
		print '<td width="120" align="right">'.$langs->trans("AmountHT").'</td>';
		print '<td width="80" align="right">'.$langs->trans("VATRate").'</td>';
		print '<td width="120" align="right">'.$langs->trans("AmountTTC").'</td>';
		print '<td width="100" align="center">'.$langs->trans("DiscountOfferedBy").'</td>';
		print '<td width="50">&nbsp;</td>';
		print '</tr>';

		$var = true;
		$i = 0 ;
		$num = $db->num_rows($resql);
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			$var = !$var;
			print "<tr $bc[$var]>";
			print '<td>'.dol_print_date($db->jdate($obj->dc),'dayhour').'</td>';
			if ($obj->description == '(CREDIT_NOTE)')
			{
				print '<td nowrap="nowrap">';
				$facturestatic->id=$obj->fk_facture_source;
				$facturestatic->ref=$obj->ref;
				$facturestatic->type=$obj->type;
				print $langs->trans("CreditNote").' '.$facturestatic->getNomURl(1);
				print '</td>';
			}
			elseif ($obj->description == '(DEPOSIT)')
			{
				print '<td nowrap="nowrap">';
				$facturestatic->id=$obj->fk_facture_source;
				$facturestatic->ref=$obj->ref;
				$facturestatic->type=$obj->type;
				print $langs->trans("InvoiceDeposit").' '.$facturestatic->getNomURl(1);
				print '</td>';
			}
			else
			{
				print '<td>';
				print $obj->description;
				print '</td>';
			}
			print '<td nowrap="nowrap">'.$langs->trans("NotConsumed").'</td>';
			print '<td align="right">'.price($obj->amount_ht).'</td>';
			print '<td align="right">'.price2num($obj->tva_tx,'MU').'%</td>';
			print '<td align="right">'.price($obj->amount_ttc).'</td>';
			print '<td align="center">';
			print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->login.'</a>';
			print '</td>';
			if ($user->rights->societe->creer || $user->rights->facture->creer)
			{
				print '<td nowrap="nowrap">';
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$objsoc->id.'&amp;action=split&amp;remid='.$obj->rowid.'">'.img_picto($langs->trans("SplitDiscount"),'split').'</a>';
				print ' &nbsp; ';
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$objsoc->id.'&amp;action=remove&amp;remid='.$obj->rowid.'">'.img_delete($langs->trans("RemoveDiscount")).'</a>';
				print '</td>';
			}
			else print '<td>&nbsp;</td>';
			print '</tr>';

			if ($_GET["action"]=='split' && $_GET['remid'] == $obj->rowid)
			{
				print "<tr $bc[$var]>";
				print '<td colspan="8">';
				$amount1=price2num($obj->amount_ttc/2,'MT');
				$amount2=($obj->amount_ttc-$amount1);
				$formquestion=array(
				'text' => $langs->trans('TypeAmountOfEachNewDiscount'),
				array('type' => 'text', 'name' => 'amount_ttc_1', 'label' => $langs->trans("AmountTTC").' 1', 'value' => $amount1, 'size' => '5'),
				array('type' => 'text', 'name' => 'amount_ttc_2', 'label' => $langs->trans("AmountTTC").' 2', 'value' => $amount2, 'size' => '5')
				);
				$langs->load("dict");
				$ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$objsoc->id.'&remid='.$obj->rowid, $langs->trans('SplitDiscount'), $langs->trans('ConfirmSplitDiscount',price($obj->amount_ttc),$langs->transnoentities("Currency".$conf->currency)), 'confirm_split', $formquestion, 0, 0);
				print '</td>';
				print '</tr>';
			}
			$i++;
		}
		$db->free($resql);
		print "</table>";
	}
	else
	{
		dol_print_error($db);
	}

	print '<br>';

	/*
	 * Liste ristournes appliquees (=liees a une ligne de facture ou facture)
	 */

	// Remises liees a lignes de factures
	$sql = "SELECT rc.rowid, rc.amount_ht, rc.amount_tva, rc.amount_ttc, rc.tva_tx,";
	$sql.= " rc.datec as dc, rc.description, rc.fk_facture_line, rc.fk_facture,";
	$sql.= " rc.fk_facture_source,";
	$sql.= " u.login, u.rowid as user_id,";
	$sql.= " f.rowid, f.facnumber,";
	$sql.= " fa.facnumber as ref, fa.type as type";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql.= " , ".MAIN_DB_PREFIX."user as u";
	$sql.= " , ".MAIN_DB_PREFIX."facturedet as fc";
	$sql.= " , ".MAIN_DB_PREFIX."societe_remise_except as rc";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as fa ON rc.fk_facture_source = fa.rowid";
	$sql.= " WHERE rc.fk_soc =". $objsoc->id;
	$sql.= " AND rc.fk_facture_line = fc.rowid";
	$sql.= " AND fc.fk_facture = f.rowid";
	$sql.= " AND rc.fk_user = u.rowid";
	$sql.= " ORDER BY dc DESC";
	//$sql.= " UNION ";
	// Remises liees a factures
	$sql2 = "SELECT rc.rowid, rc.amount_ht, rc.amount_tva, rc.amount_ttc, rc.tva_tx,";
	$sql2.= " rc.datec as dc, rc.description, rc.fk_facture_line, rc.fk_facture,";
	$sql2.= " rc.fk_facture_source,";
	$sql2.= " u.login, u.rowid as user_id,";
	$sql2.= " f.rowid, f.facnumber,";
	$sql2.= " fa.facnumber as ref, fa.type as type";
	$sql2.= " FROM ".MAIN_DB_PREFIX."facture as f";
	$sql2.= " , ".MAIN_DB_PREFIX."user as u";
	$sql2.= " , ".MAIN_DB_PREFIX."societe_remise_except as rc";
	$sql2.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as fa ON rc.fk_facture_source = fa.rowid";
	$sql2.= " WHERE rc.fk_soc =". $objsoc->id;
	$sql2.= " AND rc.fk_facture = f.rowid";
	$sql2.= " AND rc.fk_user = u.rowid";

	$sql2.= " ORDER BY dc DESC";

	$resql=$db->query($sql);
	$resql2=null;
	if ($resql) $resql2=$db->query($sql2);
	if ($resql2)
	{
		print_titre($langs->trans("DiscountAlreadyCounted"));
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td width="120" align="left">'.$langs->trans("Date").'</td>';	// Need 120+ for format with AM/PM
		print '<td align="left">'.$langs->trans("ReasonDiscount").'</td>';
		print '<td width="150" nowrap="nowrap">'.$langs->trans("ConsumedBy").'</td>';
		print '<td width="120" align="right">'.$langs->trans("AmountHT").'</td>';
		print '<td width="80" align="right">'.$langs->trans("VATRate").'</td>';
		print '<td width="120" align="right">'.$langs->trans("AmountTTC").'</td>';
		print '<td width="100" align="center">'.$langs->trans("Author").'</td>';
		print '<td width="50">&nbsp;</td>';
		print '</tr>';

		$var = true;
		$tab_sqlobj=array();
		$tab_sqlobjOrder=array();
		$num = $db->num_rows($resql);
		for ($i = 0;$i < $num;$i++)
		{
			$sqlobj = $db->fetch_object($resql);
			$tab_sqlobj[] = $sqlobj;
			$tab_sqlobjOrder[]=$db->jdate($sqlobj->dc);
		}
		$db->free($resql);

		$num = $db->num_rows($resql2);
		for ($i = 0;$i < $num;$i++)
		{
			$sqlobj = $db->fetch_object($resql2);
			$tab_sqlobj[] = $sqlobj;
			$tab_sqlobjOrder[]= $db->jdate($sqlobj->dc);
		}
		$db->free($resql2);
		array_multisort($tab_sqlobjOrder,SORT_DESC,$tab_sqlobj);

		$num = count($tab_sqlobj);
		$i = 0 ;
		while ($i < $num )
		{
			$obj = array_shift($tab_sqlobj);
			$var = !$var;
			print "<tr $bc[$var]>";
			print '<td>'.dol_print_date($db->jdate($obj->dc),'dayhour').'</td>';
			if ($obj->description == '(CREDIT_NOTE)')
			{
				print '<td nowrap="nowrap">';
				$facturestatic->id=$obj->fk_facture_source;
				$facturestatic->ref=$obj->ref;
				$facturestatic->type=$obj->type;
				print $langs->trans("CreditNote").' '.$facturestatic->getNomURl(1);
				print '</td>';
			}
			elseif ($obj->description == '(DEPOSIT)')
			{
				print '<td nowrap="nowrap">';
				$facturestatic->id=$obj->fk_facture_source;
				$facturestatic->ref=$obj->ref;
				$facturestatic->type=$obj->type;
				print $langs->trans("InvoiceDeposit").' '.$facturestatic->getNomURl(1);
				print '</td>';
			}
			else
			{
				print '<td>';
				print $obj->description;
				print '</td>';
			}
			print '<td align="left" nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$obj->rowid.'">'.img_object($langs->trans("ShowBill"),'bill').' '.$obj->facnumber.'</a></td>';
			print '<td align="right">'.price($obj->amount_ht).'</td>';
			print '<td align="right">'.price2num($obj->tva_tx,'MU').'%</td>';
			print '<td align="right">'.price($obj->amount_ttc).'</td>';
			print '<td align="center">';
			print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->user_id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$obj->login.'</a>';
			print '</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
			$i++;
		}
		print "</table>";
	}
	else
	{
		print dol_print_error($db);
	}

}

$db->close();

llxFooter();
?>
