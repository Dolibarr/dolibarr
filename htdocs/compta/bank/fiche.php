<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("./pre.inc.php");

$user->getrights('banque');

if (!$user->admin && !$user->rights->banque)
  accessforbidden();


llxHeader();


if ($_POST["action"] == 'add')
{
  // Creation compte
  $account = new Account($db,0);

  $account->bank          = $_POST["bank"];
  $account->label         = $_POST["label"];

  $account->courant       = $_POST["courant"];
  $account->clos          = $_POST["clos"];

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

  $id = $account->create($user->id);
  if (! $id) {
        $message=$account->error(); 
        $_GET["action"]='create';   // Force chargement page en mode creation
  }
  else {
        $_GET["id"]=$id;            // Force chargement page en mode visu
  }
}

if ($_POST["action"] == 'update' && ! $_POST["cancel"])
{
  // Modification
  $account = new Account($db, $_POST["id"]);
  $account->fetch($_POST["id"]);

  $account->bank            = $_POST["bank"];
  $account->label           = $_POST["label"];
  $account->courant         = $_POST["courant"];
  $account->clos            = $_POST["clos"];
  $account->code_banque     = $_POST["code_banque"];
  $account->code_guichet    = $_POST["code_guichet"];
  $account->number          = $_POST["number"];
  $account->cle_rib         = $_POST["cle_rib"];
  $account->bic             = $_POST["bic"];
  $account->iban_prefix     = $_POST["iban_prefix"];
  $account->domiciliation   = $_POST["domiciliation"];
  $account->proprio 	    = $_POST["proprio"];
  $account->adresse_proprio = $_POST["adresse_proprio"];

  $result = $account->update($user);
  if (! $result) {
        $message=$account->error(); 
        $_GET["action"]='edit';     // Force chargement page edition
  }
  else {
        $_GET["id"]=$_POST["id"];   // Force chargement page en mode visu
  }
  
}



/* ************************************************************************** */
/*                                                                            */
/* Nouvel compte                                                              */
/*                                                                            */
/* ************************************************************************** */

if ($_GET["action"] == 'create')
{
  print_titre("Nouveau compte bancaire");

  if ($message) { print "<br>$message<br><br>\n"; }

  print '<form action="fiche.php" method="post">';
  print '<input type="hidden" name="action" value="add">';
  print '<input type="hidden" name="clos" value="0">';

  print '<table class="border" cellpadding="3" cellspacing="0">';

  print '<tr><td valign="top">Banque</td>';
  print '<td colspan="3"><input size="30" type="text" name="bank" value="'.$_POST["bank"].'"></td></tr>';

  print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
  print '<td colspan="3"><input size="30" type="text" name="label" value="'.$_POST["label"].'"></td></tr>';

  print '<tr><td>Code Banque</td><td>Code Guichet</td><td>Numéro</td><td>Clé RIB</td></tr>';
  print '<tr><td><input size="8" type="text" name="code_banque" value="'.$_POST["code_banque"].'"></td>';
  print '<td><input size="8" type="text" name="code_guichet" value="'.$_POST["code_guichet"].'"></td>';
  print '<td><input size="15" type="text" name="number" value="'.$_POST["number"].'"></td>';
  print '<td><input size="3" type="text" name="cle_rib" value="'.$_POST["cle_rib"].'"></td></tr>';
  
  print '<tr><td valign="top">Clé IBAN</td>';
  print '<td colspan="3"><input size="5" type="text" name="iban_prefix" value="'.$_POST["iban_prefix"].'"></td></tr>';

  print '<tr><td valign="top">Identifiant BIC</td>';
  print '<td colspan="3"><input size="12" type="text" name="bic" value="'.$_POST["bic"].'"></td></tr>';

  print '<tr><td valign="top">Compte Courant</td>';
  print '<td colspan="3">';
  $form=new Form($db);
  print $form->selectyesnonum("courant",isset($_POST["courant"])?$_POST["courant"]:1);
  print '</td></tr>';

  print '<tr><td valign="top">Domiciliation</td><td colspan="3">';
  print "<textarea name=\"domiciliation\" rows=\"4\" cols=\"40\">".$_POST["domiciliation"];
  print "</textarea></td></tr>";

  print '<tr><td valign="top">Nom propriétaire du compte</td>';
  print '<td colspan="3"><input size="12" type="text" name="proprio" value="'.$_POST["proprio"].'"></td></tr>';

  print '<tr><td valign="top">Adresse propriétaire du compte</td><td colspan="3">';
  print "<textarea name=\"adresse_proprio\" rows=\"4\" cols=\"40\">".$_POST["adresse_proprio"];
  print "</textarea></td></tr>";

  print '<tr><td valign="top">Solde</td>';
  print '<td colspan="3"><input size="30" type="text" name="solde" value="0.00"></td></tr>';

  print '<tr><td valign="top">Date Solde</td>';
  print '<td colspan="3">'; $now=time();
  print '<input type="text" size="2" maxlength="2" name="reday" value="'.strftime("%d",$now).'">/';
  print '<input type="text" size="2" maxlength="2" name="remonth" value="'.strftime("%m",$now).'">/';
  print '<input type="text" size="4" maxlength="4" name="reyear" value="'.strftime("%Y",$now).'">';
  print '</td></tr>';
  
  print '<tr><td align="center" colspan="4"><input value="'.$langs->trans("Add").'" type="submit"></td></tr>';
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
    $head[$h][1] = "Compte bancaire $account->number";
    $h++;

    dolibarr_fiche_head($head, $hselected);

    print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
      
    print '<tr><td valign="top">Banque</td>';
    print '<td colspan="3">'.$account->bank.'</td></tr>';

    print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
    print '<td colspan="3">'.$account->label.'</td></tr>';
    
    print '<tr><td>Code Banque</td><td>Code Guichet</td><td>Numéro</td><td>Clé RIB</td></tr>';
    print '<tr><td>'.$account->code_banque.'</td>';
    print '<td>'.$account->code_guichet.'</td>';
    print '<td>'.$account->number.'</td>';
    print '<td>'.$account->cle_rib.'</td></tr>';
    
    print '<tr><td valign="top">Clé IBAN</td>';
    print '<td colspan="3">'.$account->iban_prefix.'</td></tr>';
    
    print '<tr><td valign="top">Identifiant BIC</td>';
    print '<td colspan="3">'.$account->bic.'</td></tr>';
    
    print '<tr><td valign="top">Compte Courant</td>';
    print '<td colspan="3">'.yn($account->courant).'</td></tr>';
    
    print '<tr><td valign="top">Compte Clos</td>';
    print '<td colspan="3">'.yn($account->clos).'</td></tr>';
    
    print '<tr><td valign="top">Domiciliation</td><td colspan="3">';
    print $account->domiciliation;
    print "</td></tr>\n";
    
    print '<tr><td valign="top">Nom propriétaire du compte</td><td colspan="3">';
    print $account->proprio;
    print "</td></tr>\n";
    
    print '<tr><td valign="top">Adresse propriétaire du compte</td><td colspan="3">';
    print $account->adresse_proprio;
    print "</td></tr>\n";
    
    print '</table>';
    print '<br>';
    
    print '</div>';
     
    /*
     * Barre d'actions
     *
     */
    print '<div class="tabsAction">';

      if ($user->rights->banque->configurer) 
	{
	  print '<a class="tabAction" href="fiche.php?action=edit&id='.$account->id.'">'.$langs->trans("Edit").'</a>';
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
      
      $form = new Form($db);
      
      print_titre("Edition compte bancaire");
      print "<br>";
      
      if ($message) { print "$message<br><br>\n"; }
      
      print '<form action="fiche.php?id='.$account->id.'" method="post">';
      print '<input type="hidden" name="action" value="update">';
      print '<input type="hidden" name="id" value="'.$_GET["id"].'">';
      
      print '<table class="border" cellpadding="3" cellspacing="0">';
      
      print '<tr><td valign="top">Banque</td>';
      print '<td colspan="3"><input size="30" type="text" name="bank" value="'.$account->bank.'"></td></tr>';
      
	  print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
	  print '<td colspan="3"><input size="30" type="text" name="label" value="'.$account->label.'"></td></tr>';
	  
	  print '<tr><td>Code Banque</td><td>Code Guichet</td><td>Numéro</td><td>Clé RIB</td></tr>';
	  print '<tr><td><input size="8" type="text" name="code_banque" value="'.$account->code_banque.'"></td>';
	  print '<td><input size="8" type="text" name="code_guichet" value="'.$account->code_guichet.'"></td>';
	  print '<td><input size="15" type="text" name="number" value="'.$account->number.'"></td>';
	  print '<td><input size="3" type="text" name="cle_rib" value="'.$account->cle_rib.'"></td></tr>';
	  
	  print '<tr><td valign="top">Clé IBAN</td>';
	  print '<td colspan="3"><input size="5" type="text" name="iban_prefix" value="'.$account->iban_prefix.'"></td></tr>';
	  
	  print '<tr><td valign="top">Identifiant BIC</td>';
	  print '<td colspan="3"><input size="12" type="text" name="bic" value="'.$account->bic.'"></td></tr>';

	  print '<tr><td valign="top">Compte Courant</td>';
	  print '<td colspan="3">';
	  $form->selectyesnonum("courant",$account->courant);
	  print '</td></tr>';
	  
	  print '<tr><td valign="top">Compte Cloturé</td>';
	  print '<td colspan="3">';
	  $form->selectyesnonum("clos",$account->clos);
	  print '</td></tr>';

	  print '<tr><td valign="top">Domiciliation</td><td colspan="3">';
	  print "<textarea name=\"domiciliation\" rows=\"4\" cols=\"40\">";
	  print $account->domiciliation;
	  print "</textarea></td></tr>";

      print '<tr><td valign="top">Nom propriétaire du compte</td>';
	  print '<td colspan="3"><input size="30" type="text" name="proprio" value="'.$account->proprio.'"></td></tr>';
      print "</td></tr>\n";

      print '<tr><td valign="top">Adresse propriétaire du compte</td><td colspan="3">';
	  print "<textarea name=\"adresse_proprio\" rows=\"4\" cols=\"40\">";
	  print $account->adresse_proprio;
	  print "</textarea></td></tr>";

	  print '<tr><td align="center" colspan="4"><input value="'.$langs->trans("Modify").'" type="submit">';
	  print ' &nbsp; <input name="cancel" value="'.$langs->trans("Cancel").'" type="submit">';
	  print '</td></tr>';
	  print '</form>';
	  print '</table>';
	}
      
}



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
