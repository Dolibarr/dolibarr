<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Brian Fraval         <brian@fraval.org>
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
 *
 */

/**	    \file       htdocs/soc.php
		\ingroup    societe
		\brief      Onglet societe d'une societe
		\version    $Revision$
*/

require("pre.inc.php");
$user->getrights('societe');
$langs->load("companies");
 

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $_GET["action"] = '';
  $_POST["action"] = '';
  $_GET["socid"] = $user->societe_id;
}

$soc = new Societe($db);

if ($_POST["action"] == 'add' or $_POST["action"] == 'update')
{
  $soc->nom                   = stripslashes($_POST["nom"]);
  $soc->adresse               = stripslashes($_POST["adresse"]);
  $soc->cp                    = stripslashes($_POST["cp"]);
  $soc->ville                 = stripslashes($_POST["ville"]);
  $soc->pays_id               = stripslashes($_POST["pays_id"]);
  $soc->departement_id        = stripslashes($_POST["departement_id"]);
  $soc->tel                   = stripslashes($_POST["tel"]);
  $soc->fax                   = stripslashes($_POST["fax"]);
  $soc->url                   = ereg_replace( "http://", "", $_POST["url"] );
  $soc->siren                 = stripslashes($_POST["siren"]);
  $soc->siret                 = stripslashes($_POST["siret"]);
  $soc->ape                   = stripslashes($_POST["ape"]);
  $soc->prefix_comm           = stripslashes($_POST["prefix_comm"]);
  $soc->code_client           = stripslashes($_POST["code_client"]);
  $soc->codeclient_modifiable = stripslashes($_POST["codeclient_modifiable"]);
  $soc->capital               = stripslashes($_POST["capital"]);
  $soc->tva_intra             = stripslashes($_POST["tva_intra_code"] . $_POST["tva_intra_num"]);
  $soc->forme_juridique_code  = stripslashes($_POST["forme_juridique_code"]);
  $soc->effectif_id           = stripslashes($_POST["effectif_id"]);
  $soc->client                = stripslashes($_POST["client"]);
  $soc->fournisseur           = stripslashes($_POST["fournisseur"]);

  if ($_POST["action"] == 'update')
    {
      $result = $soc->update($_GET["socid"],$user);
      if ($result <> 0)
	{
	  $soc->id = $_GET["socid"];
	  // doublon sur le prefix comm
	  $no_reload = 1;
	  $mesg = $soc->error;//"Erreur, le prefix '".$soc->prefix_comm."' existe déjà vous devez en choisir un autre";
	  $_GET["action"]= "edit";
	}
      else
	{
	  Header("Location: soc.php?socid=".$_GET["socid"]);
	}
	
    }

  if ($_POST["action"] == 'add')
    {
      $result = $soc->create($user);

      if ($result == 0)
	{
	  Header("Location: soc.php?socid=".$soc->id);
	}
      else
	{
	  $_GET["action"]='create';
	  //dolibarr_print_error($db); 
	}
    }
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes' && $user->rights->societe->creer)
{
  $soc = new Societe($db);
  $soc->fetch($_GET["socid"]);
  $result = $soc->delete($_GET["socid"]);
 
  if ($result == 0)
    {
      llxHeader();
      print '<div class="ok">'.$langs->trans("CompanyDeleted",$soc->nom).'</div>';
      llxFooter();
      exit ;
    }
  else
    {
      $no_reload = 1;
      $_GET["action"]='';
    }
}

/**
 *
 *
 *
 *
 */

llxHeader();

$form = new Form($db);

if ($_GET["action"] == 'create')
{
  if ($user->rights->societe->creer)
    {
      /*
       * Fiche societe en mode création
       */
      $soc->fournisseur=0;
      if ($_GET["type"]=='f') { $soc->fournisseur=1; }
      if ($_GET["type"]=='c') { $soc->client=1; }
      if ($_GET["type"]=='p') { $soc->client=2; }
      
      print_titre($langs->trans("NewCompany"));
      print "<br>\n";
      
      if ($soc->error)
	{
	  print '<div class="error">';
	  print nl2br($soc->error);
	  print '</div>';
	}

      print '<form action="soc.php" method="post">';
      print '<input type="hidden" name="action" value="add">';
      print '<input type="hidden" name="codeclient_modifiable" value="1">';
      
      print '<table class="border" width="100%">';
      
      print '<tr><td>'.$langs->trans('Name').'</td><td colspan="3"><input type="text" name="nom" value="'.$soc->nom.'"></td></tr>';
      print '<tr><td>'.$langs->trans('Address').'</td><td colspan="3"><textarea name="adresse" cols="40" rows="3" wrap="soft">';
      print $soc->adresse;
      print '</textarea></td></tr>';
      
      print '<tr><td>'.$langs->trans('Zip').'</td><td><input size="6" type="text" name="cp" value="'.$soc->cp.'"></td>';
      print '<td>'.$langs->trans('Town').'</td><td><input type="text" name="ville" value="'.$soc->ville.'"></td></tr>';

      print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">';
      $form->select_departement($soc->departement_id);
      print '</td></tr>';

      print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
      $form->select_pays($soc->pays?$soc->pays:(defined(MAIN_INFO_SOCIETE_PAYS)?MAIN_INFO_SOCIETE_PAYS:''));
      print '</td></tr>';

      print '<tr><td>'.$langs->trans('Phone').'</td><td><input type="text" name="tel"></td>';
      print '<td>'.$langs->trans('Fax').'</td><td><input type="text" name="fax"></td></tr>';
      
      print '<tr><td>'.$langs->trans('CustomerCode').'</td><td colspan="3"><input size="16" type="text" name="code_client" maxlength="15" value="'.$soc->code_client.'"></td></tr>';
      print '<tr><td>'.$langs->trans('Web').'</td><td colspan="3"><input type="text" name="url" size="40" value="'.$soc->url.'"></td></tr>';

      print '<tr><td>'.$langs->trans('ProfIdSiren').'</td><td><input type="text" name="siren" size="10" maxlength="9" value="'.$soc->siren.'"></td>';
      print '<td>'.$langs->trans('ProfIdSiret').'</td><td><input type="text" name="siret" size="15" maxlength="14" value="'.$soc->siret.'"></td></tr>';

      print '<tr><td>'.$langs->trans('ProfIdApe').'</td><td><input type="text" name="ape" size="5" maxlength="4" value="'.$soc->ape.'"></td>';
      print '<td>Capital</td><td><input type="text" name="capital" size="10" value="'.$soc->capital.'"> '.$conf->monnaie.'</td></tr>';
  
      print '<tr><td>Forme juridique</td><td colspan="3">';
      $form->select_forme_juridique($soc->forme_juridique_code);
      print '</td></tr>';
  
      print '<tr><td>Effectif</td><td colspan="3">';
      $form->select_array("effectif_id",$soc->effectif_array(), $soc->effectif_id);
      print '</td></tr>';

      print '<tr><td colspan="2">'.$langs->trans('TVAIntra').'</td><td colspan="2">';
  
      print '<input type="text" name="tva_intra_code" size="3" maxlength="2" value="'.$soc->tva_intra_code.'">';
      print '<input type="text" name="tva_intra_num" size="18" maxlength="18" value="'.$soc->tva_intra_num.'">';
      print '<br>Vous pouvez vérifier ce numéro sur le <a href="http://europa.eu.int/comm/taxation_customs/vies/fr/vieshome.htm" target="_blank">site</a> de la commission européenne';
      print '</td></tr>';
  
      print '<tr><td>'.$langs->trans('ProspectCustomer').'</td><td><select name="client">';
      print '<option value="2"'.($soc->client==2?' selected':'').'>'.$langs->trans('Prospect').'</option>';
      print '<option value="1"'.($soc->client==1?' selected':'').'>'.$langs->trans('Customer').'</option>';
      print '<option value="0"'.($soc->client==0?' selected':'').'>Ni client, ni prospect</option>';
      print '</select></td>'."\n";

      print '<td>'.$langs->trans('Supplier').'</td><td>'."\n";
      $form->selectyesnonum("fournisseur",$soc->fournisseur);
      print '</td></tr>'."\n";

      print '<tr><td colspan="4" align="center"><input type="submit" value="'.$langs->trans('Add').'"></td></tr>'."\n";
      print '</table>'."\n";
      print '</form>'."\n";
    }
}
elseif ($_GET["action"] == 'edit')
{
  /*
   * Fiche societe en mode edition
   */
   
  print_titre("Edition de la société");

  if ($_GET["socid"])
    {
      if ($no_reload <> 1)
	{
	  $soc = new Societe($db);
	  $soc->id = $_GET["socid"];
	  $soc->fetch($_GET["socid"]);
	}

      if ($soc->error)
	{
	  print '<div class="error">';
	  print $soc->error;
	  print '</div>';
	}

      print '<form action="soc.php?socid='.$soc->id.'" method="post">';
      print '<input type="hidden" name="action" value="update">';
      print '<input type="hidden" name="codeclient_modifiable" value="'.$soc->codeclient_modifiable.'">';

      print '<table class="border" width="100%">';

      print '<tr><td>'.$langs->trans('Name').'</td><td><input type="text" size="40" name="nom" value="'.$soc->nom.'"></td>';
      print '<td>'.$langs->trans('Prefix').'</td><td><input type="text" size="5" name="prefix_comm" value="'.$soc->prefix_comm.'"></td></tr>';

      print '<tr><td valign="top">'.$langs->trans('Address').'</td><td colspan="3"><textarea name="adresse" cols="40" rows="3" wrap="soft">';
      print $soc->adresse;
      print '</textarea></td></tr>';
      
      print '<tr><td>'.$langs->trans('Zip').'</td><td><input size="6" type="text" name="cp" value="'.$soc->cp.'"></td>';
      print '<td>'.$langs->trans('Town').'</td><td><input type="text" name="ville" value="'.$soc->ville.'"></td></tr>';

      print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">';
      $form->select_departement($soc->departement_id);
      print '</td></tr>';      

      print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
      $form->select_pays($soc->pays_id);
      print '</td></tr>';

      print '<tr><td>'.$langs->trans('Phone').'</td><td><input type="text" name="tel" value="'.$soc->tel.'"></td>';
      print '<td>'.$langs->trans('Fax').'</td><td><input type="text" name="fax" value="'.$soc->fax.'"></td></tr>';

      print '<tr><td>'.$langs->trans('CustomerCode').'</td><td>';

      if ($soc->codeclient_modifiable == 1)
	{
	  print '<input type="text" name="code_client" size="16" value="'.$soc->code_client.'" maxlength="15">';
	}
      else
	{
	  print $soc->code_client;
	}

      print '<td>Type</td><td>';
      $form->select_array("typent_id",$soc->typent_array(), $soc->typent_id);
      print '</td></tr>';

      print '<tr><td>'.$langs->trans('Web').'</td><td colspan="3"><input type="text" name="url" size="40" value="'.$soc->url.'"></td></tr>';
      
      print '<tr><td>'.$langs->trans('ProfIdSiren').'</td><td><input type="text" name="siren" size="10" maxlength="9" value="'.$soc->siren.'"></td>';
      print '<td>'.$langs->trans('ProfIdSiret').'</td><td><input type="text" name="siret" size="15" maxlength="14" value="'.$soc->siret.'"></td></tr>';

      print '<tr><td>'.$langs->trans('ProfIdApe').'</td><td><input type="text" name="ape" size="5" maxlength="4" value="'.$soc->ape.'"></td>';
      print '<td>Capital</td><td><input type="text" name="capital" size="10" value="'.$soc->capital.'"> '.$conf->monnaie.'</td></tr>';

      print '<tr><td>Forme juridique</td><td colspan="3">';
      $form->select_forme_juridique($soc->forme_juridique_code);
      print '</td></tr>';

      print '<tr><td>Effectif</td><td colspan="3">';
      $form->select_array("effectif_id",$soc->effectif_array(), $soc->effectif_id);
      print '</td></tr>';

      print '<tr><td colspan="2">'.$langs->trans('TVAIntra').'</td><td colspan="2">';

      print '<input type="text" name="tva_intra_code" size="3" maxlength="2" value="'.$soc->tva_intra_code.'">';
      print '<input type="text" name="tva_intra_num" size="18" maxlength="18" value="'.$soc->tva_intra_num.'">';

      print '</td></tr>';

      print '<tr><td>'.$langs->trans('ProspectCustomer').'</td><td><select name="client">';
      print '<option value="2"'.($soc->client==2?' selected':'').'>'.$langs->trans('Prospect').'</option>';
      print '<option value="1"'.($soc->client==1?' selected':'').'>'.$langs->trans('Customer').'</option>';
      print '<option value="0"'.($soc->client==0?' selected':'').'>Ni client, ni prospect</option>';
      print '</select></td>';

      print '<td>'.$langs->trans('Supplier').'</td><td>';
      $form->selectyesnonum("fournisseur",$soc->fournisseur);
      print '</td></tr>';
      
      print '<tr><td align="center" colspan="4"><input type="submit" value="'.$langs->trans("Save").'"></td></tr>';

      print '</table>';
      print '</form>';

      print 'Astuce : Vous pouvez vérifier le numéro de TVA intra communautaire sur le <a href="http://europa.eu.int/comm/taxation_customs/vies/fr/vieshome.htm" target="_blank">site</a> de la commission européenne';

    }
}
else
{
  if ($no_reload <> 1)
    {      
      $soc = new Societe($db);
      $soc->id = $_GET["socid"];
      $soc->fetch($_GET["socid"]);
    }

  $head[0][0] = 'soc.php?socid='.$soc->id;
  $head[0][1] = $langs->trans("Company");
  $h = 1;

  if ($soc->client==1)
    {
      $head[$h][0] = DOL_URL_ROOT.'/comm/fiche.php?socid='.$soc->id;
      $head[$h][1] = $langs->trans("Customer");
      $h++;
    }
  if ($soc->client==2)
    {
      $head[$h][0] = DOL_URL_ROOT.'/comm/prospect/fiche.php?id='.$soc->id;
      $head[$h][1] = $langs->trans("Prospect");
      $h++;
    }
  if ($soc->fournisseur)
    {
      $head[$h][0] = DOL_URL_ROOT.'/fourn/fiche.php?socid='.$soc->id;
      $head[$h][1] = $langs->trans("Supplier");;
      $h++;
    }

  if ($conf->compta->enabled) {
      $langs->load("compta");
      $head[$h][0] = DOL_URL_ROOT.'/compta/fiche.php?socid='.$soc->id;
      $head[$h][1] = $langs->trans("Accountancy");
      $h++;
  }

  $head[$h][0] = DOL_URL_ROOT.'/socnote.php?socid='.$soc->id;
  $head[$h][1] = $langs->trans("Note");
  $h++;
  
  if ($user->societe_id == 0)
    {
      $head[$h][0] = DOL_URL_ROOT.'/docsoc.php?socid='.$soc->id;
      $head[$h][1] = $langs->trans("Documents");
      $h++;
    }

  $head[$h][0] = DOL_URL_ROOT.'/societe/notify/fiche.php?socid='.$soc->id;
  $head[$h][1] = $langs->trans("Notifications");

  dolibarr_fiche_head($head, 0, $soc->nom);

  /*
   * Fiche société en mode visu
   */

  // Confirmation de la suppression de la facture
  if ($_GET["action"] == 'delete')
    {
      $html = new Form($db);
      $html->form_confirm("soc.php?socid=".$soc->id,$langs->trans("DeleteACompany"),$langs->trans("ConfirmDeleteCompany"),"confirm_delete");
      print "<br />\n";
    }
  

  if ($soc->error)
    {
      print '<div class="error">';
      print $soc->error;
      print '</div>';
    }

  print '<table class="border" width="100%">';

  print '<tr><td width="20%">'.$langs->trans('Name').'</td><td>'.$soc->nom.'</td><td>'.$langs->trans('Prefix').'</td><td>'.$soc->prefix_comm.'</td></tr>';

  print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->adresse)."</td></tr>";

  print '<tr><td>'.$langs->trans('Zip').'</td><td>'.$soc->cp."</td>";
  print '<td>'.$langs->trans('Town').'</td><td>'.$soc->ville."</td></tr>";

  print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">'.$soc->pays.'</td>';

  print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dolibarr_print_phone($soc->tel).'</td>';
  print '<td>'.$langs->trans('Fax').'</td><td>'.dolibarr_print_phone($soc->fax).'</td></tr>';

  print '<tr><td>';
  print $langs->trans('CustomerCode').'</td><td colspan="3">';
  print $soc->code_client;
  if ($soc->check_codeclient() <> 0)
    {
      print "Code incorrect";
    }
  print '</td></tr>';

  print '<tr><td>'.$langs->trans('Web').'</td><td colspan="3">';
  if ($soc->url) { print '<a href="http://'.$soc->url.'">http://'.$soc->url.'</a>'; }
  print '</td></tr>';
  
  print '<tr><td>'.$langs->trans('ProfIdSiren').'</td><td>';

  if ($soc->check_siren() == 0)
    {
      print '<a target="_blank" href="http://www.societe.com/cgi-bin/recherche?rncs='.$soc->siren.'">'.$soc->siren.'</a>&nbsp;';
    }
  else
    {
      print '<a class="error">'.$soc->siren;
      // Siren invalide
      print "&nbsp;Code Siren Invalide !</a>";
    }

  print '</td>';

  print '<td>'.$langs->trans('ProfIdSiret').'</td><td>'.$soc->siret.'</td></tr>';

  print '<tr><td>'.$langs->trans('ProfIdApe').'</td><td>'.$soc->ape.'</td>';
  print '<td>Capital</td><td>'.$soc->capital.' '.$conf->monnaie.'</td></tr>';

  print '<tr><td>Forme juridique</td><td colspan="3">'.$soc->forme_juridique.'</td></tr>';
  print '<tr><td>Effectif</td><td colspan="3">'.$soc->effectif.'</td></tr>';

  print '<tr><td colspan="2">'.$langs->trans('TVAIntra').'</td><td colspan="2">';
  print $soc->tva_intra;
  print '</td></tr>';

  print '<tr><td><a href="'.DOL_URL_ROOT.'/societe/rib.php?socid='.$soc->id.'">'.img_edit() ."</a>&nbsp;";
  print $langs->trans('RIB').'</td><td colspan="3">';
  print $soc->display_rib();
  print '</td></tr>';

  print '</table>';
  print "<br></div>\n";
  /*
   *
   */  
  if ($_GET["action"] == '')
    {

      print '<div class="tabsAction">';
      
      print '<a class="tabAction" href="'.DOL_URL_ROOT.'/societe/commerciaux.php?socid='.$soc->id.'">'.$langs->trans("SalesRepresentative").'</a>';

      print '<a class="tabAction" href="'.DOL_URL_ROOT.'/societe/lien.php?socid='.$soc->id.'">'.$langs->trans("ParentCompany").'</a>';

      print '<a class="tabAction" href="'.DOL_URL_ROOT.'/soc.php?socid='.$soc->id.'&amp;action=edit">'.$langs->trans("Edit").'</a>';
      
      print '<a class="tabAction" href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$soc->id.'&amp;action=create">'.$langs->trans("AddContact").'</a>';

      if ($user->rights->societe->supprimer)
	{	  
	  print '<a class="butDelete" href="'.DOL_URL_ROOT.'/soc.php?socid='.$soc->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';

	}
      print '</div>';
    }
/*
 *
 */
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>

