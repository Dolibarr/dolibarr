<?php
/* Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
   \file       htdocs/compta/paiement/cheque/fiche.php
   \ingroup    facture
   \brief      Onglet paiement cheque
   \version    $Revision$
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/remisecheque.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/bank/account.class.php');

$user->getrights('banque');

$langs->load('bills');
$langs->load('banks');
$langs->load('companies');

$mesg='';

/*
 * Actions
 */

if ($_GET['action'] == 'create' && $_GET["accountid"] > 0 && $user->rights->banque)
{	
  $remise = new RemiseCheque($db);
  $result = $remise->Create($user, $_GET["accountid"]);
  if ($result === 0)
    {      
      Header("Location: fiche.php?id=".$remise->id);
      exit;
    }
  else
    {
      $mesg='<div class="error">'.$paiement->error.'</div>';
    }
}

if ($_GET['action'] == 'remove' && $_GET["id"] > 0 && $_GET["lineid"] > 0 && $user->rights->banque)
{	
  $remise = new RemiseCheque($db);
  $remise->id = $_GET["id"];
  $result = $remise->RemoveCheck($_GET["lineid"]);
  if ($result === 0)
    {      
      Header("Location: fiche.php?id=".$remise->id);
      exit;
    }
  else
    {
      $mesg='<div class="error">'.$paiement->error.'</div>';
    }
}

if ($_POST['action'] == 'confirm_delete' && $_POST['confirm'] == 'yes' && $user->rights->banque)
{
  $remise = new RemiseCheque($db);
  $remise->id = $_GET["id"];
  $result = $remise->Delete();
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
  $remise = new RemiseCheque($db);
  $remise->Fetch($_GET["id"]);
  $result = $remise->Validate($user);
  if ($result == 0)
    {
      Header("Location: fiche.php?id=".$remise->id);
      exit;
    }
  else
    {
      $mesg='<div class="error">'.$paiement->error.'</div>';
    }
}

if ($_POST['action'] == 'builddoc' && $user->rights->banque)
{
  $remise = new RemiseCheque($db);
  $result = $remise->Fetch($_GET["id"]);
  if ($result == 0)
    {
      $result = $remise->GeneratePdf($_POST["model"]);
      Header("Location: fiche.php?id=".$remise->id);
      exit;
    }
  else
    {
      $mesg='<div class="error">'.$paiement->error.'</div>';
    }
}
/*
 * Visualisation de la fiche
 */

llxHeader();

$html = new Form($db);

if ($_GET['action'] == 'new')
{
  $h=0;
  $head[$h][0] = DOL_URL_ROOT.'/compta/paiement/cheque/fiche.php?action=new';
  $head[$h][1] = $langs->trans("NewCheckDeposit");
  $hselected = $h;
  $h++;      

  dolibarr_fiche_head($head, $hselected, $langs->trans("CheckReceipt"));
}
else
{
  $remise = new RemiseCheque($db);
  $result = $remise->Fetch($_GET["id"]);

  $h=0;
  $head[$h][0] = DOL_URL_ROOT.'/compta/paiement/cheque/fiche.php?id='.$_GET["id"];
  $head[$h][1] = $langs->trans("CheckReceipt");
  $hselected = $h;
  $h++;
  //  $head[$h][0] = DOL_URL_ROOT.'/compta/paiement/info.php?id='.$_GET["id"];
  //  $head[$h][1] = $langs->trans("Info");
  //  $h++;      

  dolibarr_fiche_head($head, $hselected, $langs->trans("CheckReceipt"));

  /*
   * Confirmation de la suppression du bordereau
   */
  if ($_GET['action'] == 'delete')
    {
      $html->form_confirm('fiche.php?id='.$remise->id, $langs->trans("DeleteCheckReceipt"), 'Etes-vous sûr de vouloir supprimer ce bordereau ?', 'confirm_delete');
      print '<br>';
    }
  
  /*
   * Confirmation de la validation du bordereau
   */
  if ($_GET['action'] == 'valide')
    {
      $facid = $_GET['facid'];
      $html->form_confirm('fiche.php?id='.$remise->id, $langs->trans("ValidateCheckReceipt"), 'Etes-vous sûr de vouloir valider ce bordereau, auncune modification n\'est possible une fois le bordereau validé ?', 'confirm_valide');
      print '<br>';
    }
}

if ($mesg) print $mesg.'<br>';

/*
 *
 *
 *
 */
if ($_GET['action'] == 'new')
{
	$accounts = array();
	$lines = array();
	
	print '<table class="border" width="100%">';
	print '<tr><td width="30%">'.$langs->trans('Date').'</td><td width="70%">'.dolibarr_print_date(time()).'</td></tr>';
	print '</table><br />';
	
	$sql = "SELECT ba.rowid as bid, ".$db->pdate("b.dateo")." as date,";
	$sql.= " b.amount, ba.label, b.emetteur, b.banque";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b ";
	$sql.= ",".MAIN_DB_PREFIX."bank_account as ba ";
	$sql.= " WHERE b.fk_type = 'CHQ' AND b.fk_account = ba.rowid";
	$sql.= " AND b.fk_bordereau = 0 AND b.amount > 0";
	$sql.= " ORDER BY b.emetteur ASC, b.rowid ASC;";
	
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
		print '<td>'.$langs->trans("Date")."</td>\n";
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
	
			print "<tr $bc[$var]>";
			print '<td width="120">'.dolibarr_print_date($value["date"]).'</td>';
			print '<td>'.$value["emetteur"]."</td>\n";
			print '<td>'.$value["banque"]."</td>\n";
			print '<td align="right">'.price($value["amount"]).'</td>';
			print '</tr>';
			$i++;
		}
		print "</table>";
	
		print '<div class="tabsAction">';
		print '<a class="tabAction" href="fiche.php?action=create&amp;accountid='.$bid.'">';
		print $langs->trans('NewCheckDepositOn',$account_label);
		print '</a>';
		print '</div><br />';
	}

}
else
{
	$accountstatic=new Account($db);
	
	$accountstatic->id=$remise->account_id;
	$accountstatic->label=$remise->account_label;

	$remise->load_previous_next_id();
	$previous_id = $remise->previous_id ? '<a href="'.$_SERVER["PHP_SELF"].'?id='.$remise->previous_id.'">'.img_previous().'</a>':'';
	$next_id     = $remise->next_id ? '<a href="'.$_SERVER["PHP_SELF"].'?id='.$remise->next_id.'">'.img_next().'</a>':'';

	print '<table class="border" width="100%">';
	print '<tr><td width="30%">'.$langs->trans('Numero').'</td><td width="50%">'.$remise->number.'</td><td width="20%" align="right">';
	print $previous_id.' '.$next_id;
	print "</td></tr>\n";

	print '<tr><td width="30%">'.$langs->trans('Date').'</td><td colspan="2" width="70%">'.dolibarr_print_date($remise->date_bordereau).'</td></tr>';

	print '<tr><td width="30%">'.$langs->trans('Account').'</td><td colspan="2" width="70%">';
	print $accountstatic->getNomUrl(1);
	print '</td></tr>';

	print '<tr><td width="30%">'.$langs->trans('Total').'</td><td colspan="2" width="70%">';
	print price($remise->amount);
	print '</td></tr>';

	print '</table><br />';

	$sql = "SELECT b.amount,b.emetteur,".$db->pdate("b.dateo")." as date,b.rowid,b.banque,";
	$sql.= " ba.rowid as bid, ba.label";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql.= ",".MAIN_DB_PREFIX."bank_account as ba";
	$sql.= " WHERE b.fk_type= 'CHQ' AND b.fk_account = ba.rowid";
	$sql.= " AND b.fk_bordereau = ".$remise->id;
	$sql.= " ORDER BY b.emetteur ASC, b.rowid ASC";

	$resql = $db->query($sql);

	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td>#</td>';
		print '<td>'.$langs->trans("CheckTransmitter").'</td>';
		print '<td>'.$langs->trans("Bank").'</td>';
		print '<td align="right">'.$langs->trans("Amount").'</td>';
		print "<td>&nbsp;</td></tr>\n";
		$i=1;
		$var=false;
		while ( $objp = $db->fetch_object($resql) )
		{
			$account_id = $objp->bid;
			$accounts[$objp->bid] += 1;

			print "<tr $bc[$var]><td>$i</td>";
			print '<td>'.$objp->emetteur.'</td>';
			print '<td>'.$objp->banque.'</td>';
			print '<td align="right">'.price($objp->amount).'</td>';
			if($remise->statut == 0)
			{
				print '<td align="right"><a href="fiche.php?id='.$remise->id.'&amp;action=remove&amp;lineid='.$objp->rowid.'">'.img_delete().'</a></td>';
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
  if ($remise->statut == 1)
    {
      //show_documents($modulepart,$filename,$filedir,$urlsource,$genallowed,$delallowed=0,$modelselected='',$modelliste=array(),$forcenomultilang=0);
      $dir = DOL_DATA_ROOT.'/compta/bordereau/'.get_exdir($remise->number);
      $gen = array('Blochet');
      $html->show_documents("remisecheque","",$dir,'',$gen,0);
    }
}

/*
 * Boutons Actions
 */

print '<div class="tabsAction">';

if ($user->societe_id == 0 && sizeof($accounts) == 1 && $_GET['action'] == 'new')
{
  print '<a class="tabAction" href="fiche.php?action=create&amp;accountid='.$account_id.'">'.$langs->trans('NewCheckReceipt').'</a>';
}

if ($user->societe_id == 0 && $remise->statut == 0 && $_GET['action'] == '')
{
  print '<a class="tabAction" href="fiche.php?id='.$_GET['id'].'&amp;facid='.$objp->facid.'&amp;action=valide">'.$langs->trans('Valid').'</a>';
}

if ($user->societe_id == 0 && $remise->statut == 0 && $_GET['action'] == '')
{
  print '<a class="butDelete" href="fiche.php?id='.$_GET['id'].'&amp;action=delete">'.$langs->trans('Delete').'</a>';
  
}
print '</div>';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
