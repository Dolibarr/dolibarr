<?PHP
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require_once DOL_DOCUMENT_ROOT . "/companybankaccount.class.php";

$user->getrights('societe');

if ( !$user->rights->societe->creer)
  accessforbidden();


llxHeader();

$soc = new Societe($db);
$soc->id = $_GET["socid"];
$soc->fetch($_GET["socid"]);

if ($_POST["action"] == 'update' && ! $_POST["cancel"])
{
  // Modification
  $account = new CompanyBankAccount($db, $soc->id);

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
  if (! $result)
    {
      $message=$account->error(); 
      $_GET["action"]='edit';     // Force chargement page edition
    }
  else
    {
      $_GET["id"]=$_POST["id"];   // Force chargement page en mode visu
    }  
}


/*
 * Affichage onglets
 */
$h = 0;

$head[0][0] = DOL_URL_ROOT .'/soc.php?socid='.$soc->id;
$head[0][1] = "Fiche société";
$h = 1;

$head[$h][0] = "fiche.php?id=$account->id";
$head[$h][1] = "Compte bancaire $account->number";
$h++;

dolibarr_fiche_head($head, 1);

$account = new CompanyBankAccount($db, $soc->id);
$account->fetch();


/* ************************************************************************** */
/*                                                                            */
/* Visu et edition                                                            */
/*                                                                            */
/* ************************************************************************** */

  if ($_GET["socid"] && $_GET["action"] != 'edit') 
    {

      if (!$account->verif())
	{
	  print "Le contrôle de la clé indique que les informations de votre compte bancaire sont incorrectes.";
	}

      print '<table class="border" cellpadding="3" cellspacing="0" width="100%">';
      
      print '<tr><td valign="top">Banque</td>';
      print '<td colspan="4">'.$account->bank.'</td></tr>';
    
      print '<tr><td>RIB</td><td align="center">Code Banque</td><td align="center">Code Guichet</td><td align="center">Numéro</td><td align="center">Clé RIB</td></tr>';
      print '<tr><td>&nbsp;</td><td align="center">'.$account->code_banque.'</td>';
      print '<td align="center">'.$account->code_guichet.'</td>';
      print '<td align="center">'.$account->number.'</td>';
      print '<td align="center">'.$account->cle_rib.'</td></tr>';
      
      print '<tr><td valign="top">Clé IBAN</td>';
      print '<td colspan="4">'.$account->iban_prefix.'</td></tr>';
      
      print '<tr><td valign="top">Identifiant BIC</td>';
      print '<td colspan="4">'.$account->bic.'</td></tr>';
      
      print '<tr><td valign="top">Domiciliation</td><td colspan="4">';
      print $account->domiciliation;
      print "</td></tr>\n";
      
      print '<tr><td valign="top">Nom propriétaire du compte</td><td colspan="4">';
      print $account->proprio;
      print "</td></tr>\n";
    
      print '<tr><td valign="top">Adresse propriétaire du compte</td><td colspan="4">';
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

      if ($user->rights->societe->creer) 
	{
	  print '<a class="tabAction" href="rib.php?socid='.$soc->id.'&amp;action=edit">'.$langs->trans("Edit").'</a>';
	}

    print '</div>';

    }

  /* ************************************************************************** */
  /*                                                                            */
  /* Edition                                                                    */
  /*                                                                            */
  /* ************************************************************************** */
      
  if ($_GET["socid"] && $_GET["action"] == 'edit' && $user->rights->societe->creer) 
    {

      $form = new Form($db);
      
      print_titre("Edition compte bancaire");
      print "<br>";
      
      if ($message) { print "$message<br><br>\n"; }
      
      print '<form action="rib.php?socid='.$soc->id.'" method="post">';
      print '<input type="hidden" name="action" value="update">';
      print '<input type="hidden" name="id" value="'.$_GET["id"].'">';
      
      print '<table class="border" cellpadding="3" cellspacing="0">';
      
      print '<tr><td valign="top">Banque</td>';
      print '<td colspan="3"><input size="30" type="text" name="bank" value="'.$account->bank.'"></td></tr>';
      
      print '<tr><td>RIB</td><td>Code Banque</td><td>Code Guichet</td><td>Numéro</td><td>Clé RIB</td></tr>';
      print '<tr><td>&nbsp;</td><td><input size="8" type="text" name="code_banque" value="'.$account->code_banque.'"></td>';
      print '<td><input size="8" type="text" name="code_guichet" value="'.$account->code_guichet.'"></td>';
      print '<td><input size="15" type="text" name="number" value="'.$account->number.'"></td>';
      print '<td><input size="3" type="text" name="cle_rib" value="'.$account->cle_rib.'"></td></tr>';
      
      print '<tr><td valign="top">Clé IBAN</td>';
      print '<td colspan="4"><input size="5" type="text" name="iban_prefix" value="'.$account->iban_prefix.'"></td></tr>';
      
      print '<tr><td valign="top">Identifiant BIC</td>';
      print '<td colspan="4"><input size="12" type="text" name="bic" value="'.$account->bic.'"></td></tr>';
      
      print '<tr><td valign="top">Domiciliation</td><td colspan="4">';
      print "<textarea name=\"domiciliation\" rows=\"4\" cols=\"40\">";
      print $account->domiciliation;
      print "</textarea></td></tr>";
      
      print '<tr><td valign="top">Nom propriétaire du compte</td>';
      print '<td colspan="4"><input size="30" type="text" name="proprio" value="'.$account->proprio.'"></td></tr>';
      print "</td></tr>\n";
      
      print '<tr><td valign="top">Adresse propriétaire du compte</td><td colspan="4">';
      print "<textarea name=\"adresse_proprio\" rows=\"4\" cols=\"40\">";
      print $account->adresse_proprio;
      print "</textarea></td></tr>";
      
      print '<tr><td align="center" colspan="5"><input value="'.$langs->trans("Modify").'" type="submit">';
      print ' &nbsp; <input name="cancel" value="'.$langs->trans("Cancel").'" type="submit">';
      print '</td></tr>';
      print '</form>';
      print '</table>';
    }
  




$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
