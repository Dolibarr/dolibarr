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
   \file       htdocs/compta/paiement/fiche.php
   \ingroup    facture
   \brief      Onglet paiement d'un paiement client
   \remarks	Fichier presque identique a fournisseur/paiement/fiche.php
   \version    $Revision$
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT ."/includes/modules/facture/modules_facture.php");
if ($conf->banque->enabled) require_once(DOL_DOCUMENT_ROOT.'/compta/bank/account.class.php');

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
  $remise->id = $_GET["id"];
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

/*
 * Visualisation de la fiche
 */

llxHeader();

$html = new Form($db);

if ($_GET['action'] == 'new')
{
  $h=0;
  $head[$h][0] = DOL_URL_ROOT.'/compta/paiement/cheque/fiche.php?action=new';
  $head[$h][1] = $langs->trans("NewCheckReceipt");
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
  $sql.= " b.amount, ba.label, b.emetteur"; 
  $sql.= " FROM ".MAIN_DB_PREFIX."bank as b ";
  $sql.= ",".MAIN_DB_PREFIX."bank_account as ba ";
  $sql.= " WHERE b.fk_type = 'CHQ' AND b.fk_account = ba.rowid";
  $sql.= " AND b.fk_bordereau = 0 AND b.amount > 0";
  $sql.= " ORDER BY ba.rowid ASC, b.dateo ASC;";


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
	  $i++;
	}
    }

  foreach ($accounts as $bid => $account_label)
    {
      $num = $db->num_rows($resql);
      
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre">';
      print '<td width="10%">'.$langs->trans("Date")."</td>\n";
      print '<td width="50%">'.$langs->trans("CheckTransmitter")."</td>\n";
      print '<td align="right">'.$langs->trans("Amount")."</td>\n";
      print "</tr>\n";
      
      $var=true;

      foreach ($lines[$bid] as $lid => $value)
	{
	  $var=!$var;

	  $account_id = $objp->bid;
	  $accounts[$objp->bid] += 1;

	  print "<tr $bc[$var]>";	  
	  print '<td>'.dolibarr_print_date($value["date"]).'</td>';
	  print '<td>'.stripslashes($value["emetteur"])."</td>\n";
	  print '<td align="right">'.price($value["amount"]).'</td>';
	  print '</tr>';
	  $i++;
	}
      print "</table>";

      print '<div class="tabsAction">';      
      print '<a class="tabAction" href="fiche.php?action=create&amp;accountid='.$bid.'">';
      print $langs->trans('NewCheckReceipt');
      print ' : '.stripslashes($account_label).'</a>';
      print '</div><br />';
    }

}
else
{
  print '<table class="border" width="100%">';
  print '<tr><td width="30%">'.$langs->trans('Numero').'</td><td width="70%">'.$remise->number.'</td></tr>';
  print '<tr><td width="30%">'.$langs->trans('Date').'</td><td width="70%">'.dolibarr_print_date($remise->date_bordereau).'</td></tr>';
  print '<tr><td width="30%">'.$langs->trans('Account').'</td><td width="70%">';
  print '<a href="'.DOL_URL_ROOT.'/compta/bank/account.php?account='.$remise->account_id.'">'.img_object($langs->trans("ShowAccount"),'account').' '.$remise->account_label.'</a>';

  print '</td></tr>';

  print '</table><br />';

  $sql = "SELECT b.rowid,".$db->pdate("p.datep")." as dp, p.amount,b.banque,b.emetteur,";
  $sql.= " p.statut, p.num_paiement,";
  $sql.= " c.code as paiement_code,"; 
  $sql.= " ba.rowid as bid, ba.label";
  $sql.= " FROM ".MAIN_DB_PREFIX."c_paiement as c,";
  $sql.= " ".MAIN_DB_PREFIX."paiement as p";
  $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON p.fk_bank = b.rowid";
  $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
  $sql.= " WHERE p.fk_paiement = c.id AND c.code = 'CHQ'";
  $sql.= " AND b.fk_bordereau = ".$remise->id;
  $sql.= " AND p.statut = 1";
  $sql.= " ORDER BY p.datep ASC;";

  $resql = $db->query($sql);
 
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre">';
      print_liste_field_titre($langs->trans("Date"),"liste.php","dp","",$paramlist,'align="center"',$sortfield);
      print_liste_field_titre($langs->trans("Type"),"liste.php","c.libelle","",$paramlist,"",$sortfield);
      print_liste_field_titre($langs->trans("Amount"),"liste.php","p.amount","",$paramlist,'align="right"',$sortfield);
      print_liste_field_titre($langs->trans("Bank"),"liste.php","p.amount","",$paramlist,'align="right"',$sortfield);
      print_liste_field_titre($langs->trans("CheckTransmitter"),"liste.php","p.amount","",$paramlist,'align="right"',$sortfield);
      print "<td>&nbsp;</td></tr>\n";
      
      $var=true;
      while ( $objp = $db->fetch_object($resql) )
	{
	  $account_id = $objp->bid;
	  $accounts[$objp->bid] += 1;

	  print "<tr $bc[$var]>";
	  print '<td align="center">'.dolibarr_print_date($objp->dp).'</td>';
	  print '<td>'.$langs->trans("PaymentTypeShort".$objp->paiement_code).' '.$objp->num_paiement.'</td>';
	  print '<td align="right">'.price($objp->amount).'</td>';
	  print '<td>'.stripslashes($objp->banque).'</td>';  
	  print '<td>'.stripslashes($objp->emetteur).'</td>';
	  if($remise->statut == 0)
	    {
	      print '<td align="right"><a href="fiche.php?id='.$remise->id.'&amp;action=remove&amp;lineid='.$objp->rowid.'">'.img_delete().'</a></td>';
	    }
	  else
	    {
	      print '<td>&nbsp;</td>';
	    }

	  print '</tr>';
	  $i++;
	  $var=!$var;
	}
      print "</table>";
    }
  else
    {
      dolibarr_print_error($db);
    }


  
}

print '</div>';


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
