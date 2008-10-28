<?php
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \file       htdocs/compta/paiement/cheque/fiche.php
 \ingroup    facture
 \brief      Onglet paiement cheque
 \version    $Id$
 */

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT.'/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/remisecheque.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/bank/account.class.php');

$langs->load('bills');
$langs->load('banks');
$langs->load('companies');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'cheque', '','');

$mesg='';

$sortfield=isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder=isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page=$_GET["page"];
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="b.emetteur";
if ($page < 0) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;


/*
 * Actions
 */

if ($_GET['action'] == 'create' && $_GET["accountid"] > 0 && $user->rights->banque->cheque)
{
	$remisecheque = new RemiseCheque($db);
	$result = $remisecheque->Create($user, $_GET["accountid"]);
	if ($result > 0)
	{
		Header("Location: fiche.php?id=".$remisecheque->id);
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$remisecheque->error.'</div>';
	}
}

if ($_GET['action'] == 'remove' && $_GET["id"] > 0 && $_GET["lineid"] > 0 && $user->rights->banque)
{
	$remisecheque = new RemiseCheque($db);
	$remisecheque->id = $_GET["id"];
	$result = $remisecheque->RemoveCheck($_GET["lineid"]);
	if ($result === 0)
	{
		Header("Location: fiche.php?id=".$remisecheque->id);
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$paiement->error.'</div>';
	}
}

if ($_POST['action'] == 'confirm_delete' && $_POST['confirm'] == 'yes' && $user->rights->banque)
{
	$remisecheque = new RemiseCheque($db);
	$remisecheque->id = $_GET["id"];
	$result = $remisecheque->Delete();
	if ($result == 0)
	{
		Header("Location: index.php");
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$paiement->error.'</div>';
	}
}

if ($_POST['action'] == 'confirm_valide' && $_POST['confirm'] == 'yes' && $user->rights->banque)
{
	$remisecheque = new RemiseCheque($db);
	$remisecheque->Fetch($_GET["id"]);
	$result = $remisecheque->Validate($user);
	if ($result == 0)
	{
		Header("Location: fiche.php?id=".$remisecheque->id);
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$paiement->error.'</div>';
	}
}

if ($_POST['action'] == 'builddoc' && $user->rights->banque)
{
	$remisecheque = new RemiseCheque($db);
	$result = $remisecheque->Fetch($_GET["id"]);
	if ($result == 0)
	{
		$result = $remisecheque->GeneratePdf($_POST["model"], $langs);
		Header("Location: fiche.php?id=".$remisecheque->id);
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$paiement->error.'</div>';
	}
}


/*
 * View
 */

llxHeader();

$html = new Form($db);
$formfile = new FormFile($db);

if ($_GET['action'] == 'new')
{
	$h=0;
	$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/cheque/fiche.php?action=new';
	$head[$h][1] = $langs->trans("MenuChequeDeposits");
	$hselected = $h;
	$h++;

	dolibarr_fiche_head($head, $hselected, $langs->trans("Cheques"));
}
else
{
	$remisecheque = new RemiseCheque($db);
	if ($_GET["id"])
	{
		$result = $remisecheque->Fetch($_GET["id"]);
	}
	if ($_GET["ref"])
	{
		$result = $remisecheque->Fetch('',$_GET["ref"]);
	}



	$h=0;
	$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/cheque/fiche.php?id='.$remisecheque->id;
	$head[$h][1] = $langs->trans("CheckReceipt");
	$hselected = $h;
	$h++;
	//  $head[$h][0] = DOL_URL_ROOT.'/compta/paiement/cheque/info.php?id='.$remisecheque->id;
	//  $head[$h][1] = $langs->trans("Info");
	//  $h++;

	dolibarr_fiche_head($head, $hselected, $langs->trans("Cheques"));

	/*
	 * Confirmation de la suppression du bordereau
	 */
	if ($_GET['action'] == 'delete')
	{
		$html->form_confirm('fiche.php?id='.$remisecheque->id, $langs->trans("DeleteCheckReceipt"), 'Etes-vous sûr de vouloir supprimer ce bordereau ?', 'confirm_delete');
		print '<br>';
	}

	/*
	 * Confirmation de la validation du bordereau
	 */
	if ($_GET['action'] == 'valide')
	{
		$facid = $_GET['facid'];
		$html->form_confirm('fiche.php?id='.$remisecheque->id, $langs->trans("ValidateCheckReceipt"), 'Etes-vous sûr de vouloir valider ce bordereau, auncune modification n\'est possible une fois le bordereau validé ?', 'confirm_valide');
		print '<br>';
	}
}

if ($mesg) print $mesg.'<br>';


if ($_GET['action'] == 'new')
{
	$accounts = array();
	$lines = array();

	$now=time();

	print '<table class="border" width="100%">';
	print '<tr><td width="30%">'.$langs->trans('Date').'</td><td width="70%">'.dolibarr_print_date($now,'day').'</td></tr>';
	print '</table><br />';

	$sql = "SELECT ba.rowid as bid, ".$db->pdate("b.dateo")." as date,";
	$sql.= " b.amount, ba.label, b.emetteur, b.num_chq, b.banque";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b ";
	$sql.= ",".MAIN_DB_PREFIX."bank_account as ba ";
	$sql.= " WHERE b.fk_type = 'CHQ' AND b.fk_account = ba.rowid";
	$sql.= " AND b.fk_bordereau = 0 AND b.amount > 0";
	$sql.= " ORDER BY b.emetteur ASC, b.rowid ASC";

	$resql = $db->query($sql);
	if ($resql)
	{
		$i = 0;
		while ( $obj = $db->fetch_object($resql) )
		{
			$accounts[$obj->bid] = $obj->label;
			$lines[$obj->bid][$i]["date"] = $obj->date;
			$lines[$obj->bid][$i]["amount"] = $obj->amount;
			$lines[$obj->bid][$i]["emetteur"] = $obj->emetteur;
			$lines[$obj->bid][$i]["numero"] = $obj->num_chq;
			$lines[$obj->bid][$i]["banque"] = $obj->banque;
			$i++;
		}

		if ($i == 0)
		{
			print $langs->trans("NoWaitingChecks").'<br>';
		}
	}

	foreach ($accounts as $bid => $account_label)
	{
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("DateChequeReceived")." &nbsp;</td>\n";
		print '<td width="120">'.$langs->trans("ChequeNumber")."</td>\n";
		print '<td>'.$langs->trans("CheckTransmitter")."</td>\n";
		print '<td>'.$langs->trans("Bank")."</td>\n";
		print '<td align="right">'.$langs->trans("Amount")."</td>\n";
		print "</tr>\n";

		$var=true;

		foreach ($lines[$bid] as $lid => $value)
		{
			$var=!$var;

			$account_id = $objp->bid;
			$accounts[$objp->bid] += 1;

			print "<tr ".$bc[$var].">";
			print '<td width="120">'.dolibarr_print_date($value["date"],'day').'</td>';
			print '<td>'.$value["numero"]."</td>\n";
			print '<td>'.$value["emetteur"]."</td>\n";
			print '<td>'.$value["banque"]."</td>\n";
			print '<td align="right">'.price($value["amount"]).'</td>';
			print '</tr>';
			$i++;
		}
		print "</table>";

		print '<div class="tabsAction">';
		if ($user->rights->banque->cheque)
		{
			print '<a class="butAction" href="fiche.php?action=create&amp;accountid='.$bid.'">'.$langs->trans('NewCheckDepositOn',$account_label).'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('NewCheckDepositOn',$account_label).'</a>';
		}
		print '</div><br />';
	}

}
else
{
	$paymentstatic=new Paiement($db);
	$accountlinestatic=new AccountLine($db);
	$accountstatic=new Account($db);

	$accountstatic->id=$remisecheque->account_id;
	$accountstatic->label=$remisecheque->account_label;

	print '<table class="border" width="100%">';
	print '<tr><td width="20%">'.$langs->trans('Ref').'</td><td colspan="2" >';

	print $html->showrefnav($remisecheque,'ref','', 1, 'number');

	print "</td>";
	print "</tr>\n";

	print '<tr><td>'.$langs->trans('DateCreation').'</td><td colspan="2">'.dolibarr_print_date($remisecheque->date_bordereau,'day').'</td></tr>';

	print '<tr><td>'.$langs->trans('Account').'</td><td colspan="2">';
	print $accountstatic->getNomUrl(1);
	print '</td></tr>';

	// Nb of cheques
	print '<tr><td>'.$langs->trans('NbOfCheques').'</td><td colspan="2">';
	print $remisecheque->nbcheque;
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Total').'</td><td colspan="2">';
	print price($remisecheque->amount);
	print '</td></tr>';

	print '</table><br />';


	// Liste des cheques
	$sql = "SELECT b.rowid, b.amount, b.num_chq, b.emetteur,";
	$sql.= " ".$db->pdate("b.dateo")." as date,".$db->pdate("b.datec")." as datec, b.banque,";
	$sql.= " p.rowid as pid";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement as p ON p.fk_bank = b.rowid";
	$sql.= " WHERE b.fk_type= 'CHQ'";
	$sql.= " AND b.fk_bordereau = ".$remisecheque->id;
	$sql.= " ORDER BY $sortfield $sortorder";

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';

		$param="&amp;id=".$remisecheque->id;
		print '<tr class="liste_titre">';
		'<td width="30">'.$langs->trans("Num").'</td>';
		print '<td>'.$langs->trans("Cheque").'</td>';
		print_liste_field_titre($langs->trans("Numero"),$_SERVER["PHP_SELF"],"b.num_chq", "",$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("CheckTransmitter"),$_SERVER["PHP_SELF"],"b.emetteur", "",$param,"",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Bank"),$_SERVER["PHP_SELF"],"b.banque", "",$param,"",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Amount"),$_SERVER["PHP_SELF"],"b.amount", "",$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("LineRecord"),$_SERVER["PHP_SELF"],"b.rowid", "",$param,'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("DateChequeReceived"),$_SERVER["PHP_SELF"],"b.datec", "",$param,'align="center"',$sortfield,$sortorder);
		print "<td>&nbsp;</td></tr>\n";
		$i=1;
		$var=false;
		while ( $objp = $db->fetch_object($resql) )
		{
			$account_id = $objp->bid;
			$accounts[$objp->bid] += 1;

			print "<tr $bc[$var]>";
			print '<td align="center" width="24">'.$i.'</td>';
			print '<td align="center">'.($objp->num_chq?$objp->num_chq:'&nbsp;').'</td>';
			print '<td>'.dolibarr_trunc($objp->emetteur,24).'</td>';
			print '<td>'.dolibarr_trunc($objp->banque,24).'</td>';
			print '<td align="right">'.price($objp->amount).'</td>';
			print '<td align="center">';
			$accountlinestatic->rowid=$objp->rowid;
			if ($accountlinestatic->rowid)
			{
				print $accountlinestatic->getNomUrl(1);
			}
			else
			{
				print '&nbsp;';
			}
			print '</td>';
			print '<td align="center">'.dolibarr_print_date($objp->datec,'day').'</td>';
			if($remisecheque->statut == 0)
			{
				print '<td align="right"><a href="fiche.php?id='.$remisecheque->id.'&amp;action=remove&amp;lineid='.$objp->rowid.'">'.img_delete().'</a></td>';
			}
			else
			{
				print '<td>&nbsp;</td>';
			}
			print '</tr>';
			$var=!$var;
			$i++;
		}
		print "</table>";
	}
	else
	{
		dolibarr_print_error($db);
	}

}

print '</div>';

if ($_GET['action'] != 'new')
{
	if ($remisecheque->statut == 1)
	{
		$dir = DOL_DATA_ROOT.'/compta/bordereau/'.get_exdir($remisecheque->number);
		$gen = array('Blochet');
		$formfile->show_documents("remisecheque","",$dir,'',$gen,0);
	}
}

/*
 * Boutons Actions
 */

print '<div class="tabsAction">';

if ($user->societe_id == 0 && sizeof($accounts) == 1 && $_GET['action'] == 'new')
{
	print '<a class="butAction" href="fiche.php?action=create&amp;accountid='.$account_id.'">'.$langs->trans('NewCheckReceipt').'</a>';
}

if ($user->societe_id == 0 && $remisecheque->statut == 0 && $_GET['action'] == '')
{
	print '<a class="butAction" href="fiche.php?id='.$_GET['id'].'&amp;facid='.$objp->facid.'&amp;action=valide">'.$langs->trans('Valid').'</a>';
}

if ($user->societe_id == 0 && $remisecheque->statut == 0 && $_GET['action'] == '')
{
	print '<a class="butActionDelete" href="fiche.php?id='.$_GET['id'].'&amp;action=delete">'.$langs->trans('Delete').'</a>';

}
print '</div>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
