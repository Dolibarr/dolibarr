<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/compta/bank/fiche.php
        \ingroup    banque
		\brief      Fiche création compte bancaire
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("banks");

$user->getrights('banque');

if (!$user->admin && !$user->rights->banque)
  accessforbidden();


/*
 * Actions
 */
if ($_POST["action"] == 'add')
{
    // Creation compte
    $account = new Account($db,0);
    
    $account->label         = trim($_POST["label"]);
    $account->courant       = $_POST["type"];
    $account->clos          = $_POST["clos"];
    $account->rappro        = $_POST["norappro"]?1:0;
    
    $account->bank          = trim($_POST["bank"]);
    $account->code_banque   = $_POST["code_banque"];
    $account->code_guichet  = $_POST["code_guichet"];
    $account->number        = $_POST["number"];
    $account->cle_rib       = $_POST["cle_rib"];
    $account->bic           = $_POST["bic"];
    $account->iban_prefix   = $_POST["iban_prefix"];
    $account->domiciliation = $_POST["domiciliation"];
    
    $account->proprio 	  = $_POST["proprio"];
    $account->adresse_proprio = $_POST["adresse_proprio"];
    
    $account->solde         = $_POST["solde"];
    $account->date_solde    = mktime(12,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);
    
    if ($account->label) {
        $id = $account->create($user->id);
        if ($id > 0) {
            $_GET["id"]=$id;            // Force chargement page en mode visu
        }
        else {
            $message='<div class="error">'.$account->error().'</div>';
            $_GET["action"]='create';   // Force chargement page en mode creation
        }
    } else {
        $message='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("LabelBankCashAccount")).'</div>';
        $_GET["action"]='create';       // Force chargement page en mode creation
    }
}

if ($_POST["action"] == 'update' && ! $_POST["cancel"])
{
    // Modification
    $account = new Account($db, $_POST["id"]);
    $account->fetch($_POST["id"]);

    $account->label           = trim($_POST["label"]);
    $account->courant         = $_POST["type"];
    $account->clos            = $_POST["clos"];
    $account->rappro          = (isset($_POST["norappro"]) && $_POST["norappro"]=='on')?0:1;

    $account->bank            = $_POST["bank"];
    $account->code_banque     = $_POST["code_banque"];
    $account->code_guichet    = $_POST["code_guichet"];
    $account->number          = $_POST["number"];
    $account->cle_rib         = $_POST["cle_rib"];
    $account->bic             = $_POST["bic"];
    $account->iban_prefix     = $_POST["iban_prefix"];
    $account->domiciliation   = $_POST["domiciliation"];
    $account->proprio 	    = $_POST["proprio"];
    $account->adresse_proprio = $_POST["adresse_proprio"];

    if ($account->label)
    {
        $result = $account->update($user);
        if (! $result)
        {
            $message=$account->error();
            $_GET["action"]='edit';     // Force chargement page edition
        }
        else
        {
            $_GET["id"]=$_POST["id"];   // Force chargement page en mode visu
        }
    } else {
        $message='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("LabelBankCashAccount")).'</div>';
        $_GET["action"]='create';       // Force chargement page en mode creation
    }
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == "yes" && $user->rights->banque->configurer)
{
    // Modification
    $account = new Account($db, $_GET["id"]);
    $account->delete($_GET["id"]);

    header("Location: ".DOL_URL_ROOT."/compta/bank/index.php");
    exit;
}


llxHeader();

$form = new Form($db);

/* ************************************************************************** */
/*                                                                            */
/* Affichage page en mode création                                            */
/*                                                                            */
/* ************************************************************************** */

if ($_GET["action"] == 'create')
{
  print_titre($langs->trans("NewFinancialAccount"));
  print '<br>';
  
  if ($message) { print "$message<br>\n"; }

  print '<form action="fiche.php" method="post">';
  print '<input type="hidden" name="action" value="add">';
  print '<input type="hidden" name="clos" value="0">';

  print '<table class="border" width="100%">';

  print '<tr><td valign="top">'.$langs->trans("LabelBankCashAccount").'</td>';
  print '<td colspan="3"><input size="30" type="text" class="flat" name="label" value="'.$_POST["label"].'"></td></tr>';

  print '<tr><td valign="top">'.$langs->trans("AccountType").'</td>';
  print '<td colspan="3">';
  $form=new Form($db);
  print $form->select_type_comptes_financiers(isset($_POST["type"])?$_POST["type"]:1,"type");
  print '</td></tr>';

  print '<tr><td valign="top">'.$langs->trans("InitialBankBalance").'</td>';
  print '<td colspan="3"><input size="12" type="text" class="flat" name="solde" value="0.00"></td></tr>';

  print '<tr><td valign="top">'.$langs->trans("Date").'</td>';
  print '<td colspan="3">'; $now=time();
  print '<input type="text" size="2" maxlength="2" name="reday" value="'.strftime("%d",$now).'">/';
  print '<input type="text" size="2" maxlength="2" name="remonth" value="'.strftime("%m",$now).'">/';
  print '<input type="text" size="4" maxlength="4" name="reyear" value="'.strftime("%Y",$now).'">';
  print '</td></tr>';
  
  print '<tr><td valign="top">&nbsp;</td>';
  print '<td colspan="3"><input type="checkbox" name="norappro" value="'.$_POST["norappro"].'"> '.$langs->trans("DisableConciliation").'</td></tr>';

  print '<tr><td colspan="4"><b>'.$langs->trans("IfBankAccount").'...</b></td></tr>';

  print '<tr><td valign="top">'.$langs->trans("Bank").'</td>';
  print '<td colspan="3"><input size="30" type="text" class="flat" name="bank" value="'.$_POST["bank"].'"></td></tr>';

  print '<tr><td>Code Banque</td><td>Code Guichet</td><td>Numéro</td><td>Clé RIB</td></tr>';
  print '<tr><td><input size="8" type="text" class="flat" name="code_banque" value="'.$_POST["code_banque"].'"></td>';
  print '<td><input size="8" type="text" class="flat" name="code_guichet" value="'.$_POST["code_guichet"].'"></td>';
  print '<td><input size="15" type="text" class="flat" name="number" value="'.$_POST["number"].'"></td>';
  print '<td><input size="3" type="text" class="flat" name="cle_rib" value="'.$_POST["cle_rib"].'"></td></tr>';
  
  print '<tr><td valign="top">'.$langs->trans("IBAN").'</td>';
  print '<td colspan="3"><input size="24" type="text" class="flat" name="iban_prefix" value="'.$_POST["iban_prefix"].'"></td></tr>';

  print '<tr><td valign="top">'.$langs->trans("BIC").'</td>';
  print '<td colspan="3"><input size="24" type="text" class="flat" name="bic" value="'.$_POST["bic"].'"></td></tr>';

  print '<tr><td valign="top">'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="3">';
  print "<textarea class=\"flat\" name=\"domiciliation\" rows=\"2\" cols=\"40\">".$_POST["domiciliation"];
  print "</textarea></td></tr>";

  print '<tr><td valign="top">'.$langs->trans("BankAccountOwner").'</td>';
  print '<td colspan="3"><input size="12" type="text" class="flat" name="proprio" value="'.$_POST["proprio"].'"></td></tr>';

  print '<tr><td valign="top">'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="3">';
  print "<textarea class=\"flat\" name=\"adresse_proprio\" rows=\"2\" cols=\"40\">".$_POST["adresse_proprio"];
  print "</textarea></td></tr>";

  print '<tr><td align="center" colspan="4"><input value="'.$langs->trans("CreateAccount").'" type="submit" class="button"></td></tr>';
  print '</form>';
  print '</table>';
}
/* ************************************************************************** */
/*                                                                            */
/* Visu et edition                                                            */
/*                                                                            */
/* ************************************************************************** */
else
{
  if ($_GET["id"] && $_GET["action"] != 'edit') 
    {
      $account = new Account($db, $_GET["id"]);
      $account->fetch($_GET["id"]);

    /*
     * Affichage onglets
     */
    $h = 0;
    
    $head[$h][0] = "fiche.php?id=$account->id";
    $head[$h][1] = $langs->trans("AccountCard");
    $h++;

    dolibarr_fiche_head($head, $hselected, $langs->trans("FinancialAccount")." ".($account->number?$account->number:$account->label));

    /*
     * Confirmation de la suppression
     */
    if ($_GET["action"] == 'delete')
    {
        $form->form_confirm($_SERVER["PHP_SELF"]."?id=$account->id",$langs->trans("DeleteAccount"),$langs->trans("ConfirmDeleteAccount"),"confirm_delete");
        print '<br />';
    }

    print '<table class="border" width="100%">';
      
    print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
    print '<td colspan="3">'.$account->label.'</td></tr>';
    
    print '<tr><td valign="top">'.$langs->trans("AccountType").'</td>';
    print '<td colspan="3">'.$account->type_lib[$account->type].'</td></tr>';
    
    print '<tr><td valign="top">'.$langs->trans("Status").'</td>';
    print '<td colspan="3">'.$account->status[$account->clos].'</td></tr>';

    print '<tr><td valign="top">'.$langs->trans("Conciliable").'</td>';
    print '<td colspan="3">';
    if ($account->type == 0 || $account->type == 1) print ($account->rappro==1 ? $langs->trans("Yes") : ($langs->trans("No").' ('.$langs->trans("ConciliationDisabled").')'));
    if ($account->type == 2)                        print $langs->trans("No").' ('.$langs->trans("CashAccount").')';
    print '</td></tr>';

    if ($account->type == 0 || $account->type == 1)
    {
        print '<tr><td valign="top">'.$langs->trans("Bank").'</td>';
        print '<td colspan="3">'.$account->bank.'</td></tr>';
    
        print '<tr><td>Code Banque</td><td>Code Guichet</td><td>Numéro</td><td>Clé RIB</td></tr>';
        print '<tr><td>'.$account->code_banque.'</td>';
        print '<td>'.$account->code_guichet.'</td>';
        print '<td>'.$account->number.'</td>';
        print '<td>'.$account->cle_rib.'</td></tr>';
        
        print '<tr><td valign="top">'.$langs->trans("IBAN").'</td>';
        print '<td colspan="3">'.$account->iban_prefix.'</td></tr>';
        
        print '<tr><td valign="top">'.$langs->trans("BIC").'</td>';
        print '<td colspan="3">'.$account->bic.'</td></tr>';
        
        print '<tr><td valign="top">'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="3">';
        print nl2br($account->domiciliation);
        print "</td></tr>\n";
        
        print '<tr><td valign="top">'.$langs->trans("BankAccountOwner").'</td><td colspan="3">';
        print $account->proprio;
        print "</td></tr>\n";
        
        print '<tr><td valign="top">'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="3">';
        print nl2br($account->adresse_proprio);
        print "</td></tr>\n";
    }
        
    print '</table>';
    
    print '</div>';


    /*
     * Barre d'actions
     *
     */
    print '<div class="tabsAction">';

    if ($user->rights->banque->configurer) 
	{
	  print '<a class="butAction" href="fiche.php?action=edit&id='.$account->id.'">'.$langs->trans("Edit").'</a>';
	}

    $canbedeleted=$account->can_be_deleted();   // Renvoi vrai si compte sans mouvements
    if ($user->rights->banque->configurer && $canbedeleted) 
	{
	  print '<a class="butActionDelete" href="fiche.php?action=delete&id='.$account->id.'">'.$langs->trans("Delete").'</a>';
	}

    print '</div>';

    }

  /* ************************************************************************** */
  /*                                                                            */
  /* Edition                                                                    */
  /*                                                                            */
  /* ************************************************************************** */
      
  if ($_GET["id"] && $_GET["action"] == 'edit' && $user->rights->banque->configurer) 
    {
	  
      $account = new Account($db, $_GET["id"]);
      $account->fetch($_GET["id"]);
      
      print_titre($langs->trans("EditFinancialAccount"));
      print "<br>";
      
      if ($message) { print "$message<br><br>\n"; }
      
      print '<form action="fiche.php?id='.$account->id.'" method="post">';
      print '<input type="hidden" name="action" value="update">';
      print '<input type="hidden" name="id" value="'.$_GET["id"].'">';
      
      print '<table class="border" width="100%">';
      
	  print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
	  print '<td colspan="3"><input size="30" type="text" class="flat" name="label" value="'.$account->label.'"></td></tr>';
	  
	  print '<tr><td valign="top">'.$langs->trans("AccountType").'</td>';
      print '<td colspan="3">'.$account->type_lib[$account->type].'</td></tr>';
      print '<input type="hidden" name="type" value="'.$account->type.'">';

	  print '<tr><td valign="top">'.$langs->trans("Status").'</td>';
	  print '<td colspan="3">';
	  $form->select_array("clos",array(0=>$account->status[0],1=>$account->status[1]),$account->clos);
	  print '</td></tr>';

      print '<tr><td valign="top">'.$langs->trans("Conciliable").'</td>';
      print '<td colspan="3">';
      if ($account->type == 0 || $account->type == 1) print '<input type="checkbox" class="flat" name="norappro" '.($account->rappro?'':'checked="true"').'"> '.$langs->trans("DisableConciliation");
      if ($account->type == 2)                        print $langs->trans("No").' ('.$langs->trans("CashAccount").')';
      print '</td></tr>';

      if ($account->type == 0 || $account->type == 1) {

          print '<tr><td valign="top">'.$langs->trans("Bank").'</td>';
          print '<td colspan="3"><input size="30" type="text" class="flat" name="bank" value="'.$account->bank.'"></td></tr>';
      
    	  print '<tr><td>Code Banque</td><td>Code Guichet</td><td>Numéro</td><td>Clé RIB</td></tr>';
    	  print '<tr><td><input size="8" type="text" class="flat" name="code_banque" value="'.$account->code_banque.'"></td>';
    	  print '<td><input size="8" type="text" class="flat" name="code_guichet" value="'.$account->code_guichet.'"></td>';
    	  print '<td><input size="15" type="text" class="flat" name="number" value="'.$account->number.'"></td>';
    	  print '<td><input size="3" type="text" class="flat" name="cle_rib" value="'.$account->cle_rib.'"></td></tr>';
    	  
    	  print '<tr><td valign="top">'.$langs->trans("IBAN").'</td>';
    	  print '<td colspan="3"><input size="24" type="text" class="flat" name="iban_prefix" value="'.$account->iban_prefix.'"></td></tr>';
    	  
    	  print '<tr><td valign="top">'.$langs->trans("BIC").'</td>';
    	  print '<td colspan="3"><input size="24" type="text" class="flat" name="bic" value="'.$account->bic.'"></td></tr>';
    
    	  print '<tr><td valign="top">'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="3">';
    	  print "<textarea class=\"flat\" name=\"domiciliation\" rows=\"2\" cols=\"40\">";
    	  print $account->domiciliation;
    	  print "</textarea></td></tr>";
    
          print '<tr><td valign="top">'.$langs->trans("BankAccountOwner").'</td>';
    	  print '<td colspan="3"><input size="30" type="text" class="flat" name="proprio" value="'.$account->proprio.'"></td></tr>';
          print "</td></tr>\n";
    
          print '<tr><td valign="top">'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="3">';
    	  print "<textarea class=\"flat\" name=\"adresse_proprio\" rows=\"2\" cols=\"40\">";
    	  print $account->adresse_proprio;
    	  print "</textarea></td></tr>";
      }
      
	  print '<tr><td align="center" colspan="4"><input value="'.$langs->trans("Modify").'" type="submit" class="button">';
	  print ' &nbsp; <input name="cancel" value="'.$langs->trans("Cancel").'" type="submit" class="button">';
	  print '</td></tr>';
	  print '</form>';
	  print '</table>';
	}
      
}



$db->close();

llxFooter('$Date$ - $Revision$');
?>
