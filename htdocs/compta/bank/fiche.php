<?PHP
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Jean-Louis Bergamo <jlb@j1b.org>
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

$user->getrights('compta');

if (!$user->admin && !$user->rights->compta->bank)
  accessforbidden();

llxHeader();

if ($HTTP_POST_VARS["action"] == 'add')
{
  $account = new Account($db,0);

  $account->bank          = $HTTP_POST_VARS["bank"];
  $account->label         = $HTTP_POST_VARS["label"];
  $account->courant       = $HTTP_POST_VARS["courant"];
  $account->clos          = $HTTP_POST_VARS["clos"];
  $account->code_banque   = $HTTP_POST_VARS["code_banque"];
  $account->code_guichet  = $HTTP_POST_VARS["code_guichet"];
  $account->number        = $HTTP_POST_VARS["number"];
  $account->cle_rib       = $HTTP_POST_VARS["cle_rib"];
  $account->bic           = $HTTP_POST_VARS["bic"];
  $account->iban_prefix   = $HTTP_POST_VARS["iban_prefix"];
  $account->domiciliation = $HTTP_POST_VARS["domiciliation"];
  $account->solde         = $HTTP_POST_VARS["solde"];
  $account->date_solde    = mktime(12,0,0,$HTTP_POST_VARS["remonth"],$HTTP_POST_VARS["reday"],$HTTP_POST_VARS["reyear"]);

  $id = $account->create($user->id);
}

if ($action == 'update')
{
  $account = new Account($db, $id);
  $account->fetch($id);

  $account->bank         = $HTTP_POST_VARS["bank"];
  $account->label        = $HTTP_POST_VARS["label"];

  $account->courant      = $HTTP_POST_VARS["courant"];
  $account->clos         = $HTTP_POST_VARS["clos"];

  $account->code_banque  = $HTTP_POST_VARS["code_banque"];
  $account->code_guichet = $HTTP_POST_VARS["code_guichet"];
  $account->number       = $HTTP_POST_VARS["number"];
  $account->cle_rib      = $HTTP_POST_VARS["cle_rib"];
  $account->bic          = $HTTP_POST_VARS["bic"];
  $account->iban_prefix  = $HTTP_POST_VARS["iban_prefix"];
  $account->domiciliation = $HTTP_POST_VARS["domiciliation"];
  $account->update($id, $user);
}



/* ************************************************************************** */
/*                                                                            */
/* Nouvel compte                                                              */
/*                                                                            */
/* ************************************************************************** */

if ($action == 'create')
{
  print_titre("Nouveau compte bancaire");

  print '<p><form action="'.$PHP_SELF.'" method="post">';
  print '<input type="hidden" name="action" value="add">';
  print '<input type="hidden" name="clos" value="0">';

  print '<table border="1" cellpadding="3" cellspacing="0">';

  print '<tr><td valign="top">Banque</td>';
  print '<td colspan="3"><input size="30" type="text" name="bank" value=""></td></tr>';

  print '<tr><td valign="top">Libellé</td>';
  print '<td colspan="3"><input size="30" type="text" name="label" value=""></td></tr>';

  print '<tr><td>Code Banque</td><td>Code Guichet</td><td>Numéro</td><td>Clé RIB</td></tr>';
  print '<tr><td><input size="8" type="text" name="code_banque"></td>';
  print '<td><input size="8" type="text" name="code_guichet"></td>';
  print '<td><input size="15" type="text" name="number"></td>';
  print '<td><input size="3" type="text" name="cle_rib"></td></tr>';
  
  print '<tr><td valign="top">Clé IBAN</td>';
  print '<td colspan="3"><input size="5" type="text" name="iban_prefix" value=""></td></tr>';

  print '<tr><td valign="top">Identifiant BIC</td>';
  print '<td colspan="3"><input size="12" type="text" name="bic" value=""></td></tr>';

  print '<tr><td valign="top">Compte Courant</td>';
  print '<td colspan="3"><select name="courant">';
  print '<option value="0">non<option value="1">oui</select></td></tr>';

  print '<tr><td valign="top">Domiciliation</td><td colspan="3">';
  print "<textarea name=\"domiciliation\" rows=\"5\" cols=\"40\">";
  //  print $user->description;
  print "</textarea></td></tr>";

  print '<tr><td valign="top">Solde</td>';
  print '<td colspan="3"><input size="30" type="text" name="solde" value="0.00"></td></tr>';

  print '<tr><td valign="top">Date Solde</td>';
  print '<td colspan="3">'; $now=time();
  print '<input type="text" size="2" maxlength="2" name="reday" value="'.strftime("%d",$now).'">/';
  print '<input type="text" size="2" maxlength="2" name="remonth" value="'.strftime("%m",$now).'">/';
  print '<input type="text" size="4" maxlength="4" name="reyear" value="'.strftime("%Y",$now).'">';
  print '</td></tr>';
  
  print '<tr><td align="center" colspan="4"><input value="Enregistrer" type="submit"></td></tr>';
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
  if ($id) 
    {
      $account = new Account($db, $id);
      $account->fetch($id);

      print '<div class="titre">Compte bancaire</div><br>';

      print '<table border="1" cellpadding="3" cellspacing="0">';
      
      print '<tr><td valign="top">Banque</td>';
      print '<td colspan="3">'.$account->bank.'</td></tr>';

      print '<tr><td valign="top">Libellé</td>';
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

      /*      
      print '<tr><td valign="top">Domiciliation</td>';
      print '<td colspan="3">'.$account->domiciliation.'</td></tr>';
      */

      print '<tr><td valign="top">Compte Courant</td>';
      print '<td colspan="3">'.$yn[$account->courant].'</td></tr>';

      print '<tr><td valign="top">Compte Clos</td>';
      print '<td colspan="3">'.$yn[$account->clos].'</td></tr>';
  
      print '<tr><td valign="top">Domiciliation</td><td colspan="3">';
      print $account->domiciliation;
      print "</td></tr>\n";

      print '</table>';

      print '<br><table width="100%" border="1" cellspacing="0" cellpadding="2">';

      if ($user->admin) 
	{
	  print '<td width="25%" bgcolor="#e0E0E0" align="center">[<a href="fiche.php?action=edit&id='.$id.'">Editer</a>]</td>';
	}
      else
	{
	  print '<td width="25%" align="center">-</td>';
	}

      print '<td width="25%" align="center">-</td>';
      print '<td width="25%" align="center">-</td>';
      print '<td width="25%" align="center">-</td>';

      print '</table><br>';

      /* ************************************************************************** */
      /*                                                                            */
      /* Edition                                                                    */
      /*                                                                            */
      /* ************************************************************************** */
      
      if ($action == 'edit' && $user->admin) 
	{

	  $form = new Form($db);

	  print '<p><form action="'.$PHP_SELF.'?id='.$id.'" method="post">';
	  print '<input type="hidden" name="action" value="update">';
	  
	  print '<table border="1" cellpadding="3" cellspacing="0">';
	  
	  print '<tr><td valign="top">Banque</td>';
	  print '<td colspan="3"><input size="30" type="text" name="bank" value="'.$account->bank.'"></td></tr>';
	  
	  print '<tr><td valign="top">Libellé</td>';
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

	  /*	  
	  print '<tr><td valign="top">Domiciliation</td>';
	  print '<td colspan="3"><input size="20" type="text" name="domiciliation" value="'.$account->domiciliation.'"></td></tr>';
	  */

	  print '<tr><td valign="top">Compte Courant</td>';
	  print '<td colspan="3">';
	  $form->selectyesnonum("courant",$account->courant);
	  print '</td></tr>';
	  
	  print '<tr><td valign="top">Compte Cloturé</td>';
	  print '<td colspan="3">';
	  $form->selectyesnonum("clos",$account->clos);
	  print '</td></tr>';

	  print '<tr><td valign="top">Domiciliation</td><td colspan="3">';
	  print "<textarea name=\"domiciliation\" rows=\"5\" cols=\"40\">";
	  print $account->domiciliation;
	  print "</textarea></td></tr>";

	  print '<tr><td align="center" colspan="4"><input value="Enregistrer" type="submit"></td></tr>';
	  print '</form>';
	  print '</table>';
	}
      
    }
  
}



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
