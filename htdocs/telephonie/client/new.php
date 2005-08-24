<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Brian Fraval         <brian@fraval.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005 Eric Seigne <eric.seigne@ryxeo.com>
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

/**
   \file       htdocs/telephonie/client/new.php
   \ingroup    telephonie
   \brief      Creation d'un nouveau client
   \version    $Revision$
*/

require("pre.inc.php");

if (!$user->rights->telephonie->ligne->creer) accessforbidden();

require_once(DOL_DOCUMENT_ROOT.'/contact.class.php');
require_once(DOL_DOCUMENT_ROOT.'/companybankaccount.class.php');
require_once(DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once(DOL_DOCUMENT_ROOT."/telephonie/telephonie.contrat.class.php");

$user->getrights('societe');
$langs->load("companies");

$soc = new Societe($db);
$contact = new Contact($db);
$rib = new CompanyBankAccount($db, 0);

/*
 * Actions
 */

if ($_POST["action"] == 'add')
{
  $error = 0;
  $verif = "ok";
  $mesg = '';

  if (strlen(trim($_POST["nom"])) == 0)
    {
      $mesg = "Nom de société incorrect";
      $verif = "nok";
    }

  if (strlen(trim($_POST["code_client"])) <> 6 && $verif == 'ok')
    {
      $mesg = "Code client incorrect";
      $verif = "nok";
    }

  if (strlen(trim($_POST["adresse"])) == 0 && $verif == 'ok')
    {
      $mesg = "Adresse de société manquante";
      $verif = "nok";
    }

  if (strlen(trim($_POST["cp"])) == 0 && $verif == 'ok')
    {
      $mesg = "Code postal manquant";
      $verif = "nok";
    }

  if (strlen(trim($_POST["ville"])) == 0 && $verif == 'ok')
    {
      $mesg = "Ville manquante";
      $verif = "nok";
    }

  $rib->code_banque  = $_POST["rib_banque"];
  $rib->code_guichet = $_POST["rib_guichet"];
  $rib->number       = $_POST["rib_compte"];
  $rib->cle_rib      = $_POST["rib_cle"];
  $rib->iban_prefix  = $_POST["rib_iban"];

  
  if ((strlen(trim($_POST["rib_banque"])) + strlen(trim($_POST["rib_guichet"])) + strlen(trim($_POST["rib_compte"])) + strlen(trim($_POST["rib_cle"])))<> 0 && $verif == 'ok')
    {
      if (strlen(trim($_POST["rib_banque"])) <> 5 && $verif == 'ok')
	{
	  $mesg = "Rib code banque incomplet";
	  $verif = "nok";
	}

      if (strlen(trim($_POST["rib_guichet"])) <> 5 && $verif == 'ok')
	{
	  $mesg = "Rib code agence incomplet";
	  $verif = "nok";
	}

      if ($rib->verif() <> 1 && $verif == 'ok')
	{
	  $mesg = "Rib incorrect ".$rib->error_message;
	  $verif = "nok";
	}
    }

  if (strlen(trim($_POST["cmail"])) > 0 && $verif == 'ok')
    {
      if (!ValidEmail(trim($_POST["cmail"]))  && $verif == 'ok')
	{
	  $mesg = "Email invalide";
	  $verif = "nok";
	}

      if (!check_mail(trim($_POST["cmail"]))  && $verif == 'ok')
	{
	  $mesg = "Email invalide (domaine invalide)";
	  $verif = "nok";
	}
    }


  if (strlen(trim($_POST["cli"])) <> 9 && $verif == 'ok')
    {
      $mesg = "Numéro de ligne incorrecte";
      $verif = "nok";
    }

  $soc->nom                   = stripslashes($_POST["nom"]);
  $soc->adresse               = stripslashes($_POST["adresse"]);
  $soc->cp                    = stripslashes($_POST["cp"]);
  $soc->ville                 = stripslashes($_POST["ville"]);
  $soc->pays_id               = stripslashes($_POST["pays_id"]);
  $soc->tel                   = stripslashes($_POST["tel"]);
  $soc->fax                   = stripslashes($_POST["fax"]);
  $soc->url                   = ereg_replace( "http://", "", $_POST["url"] );
  $soc->code_client           = $_POST["code_client"];
  $soc->code_fournisseur      = stripslashes($_POST["code_fournisseur"]);
  $soc->codeclient_modifiable = stripslashes($_POST["codeclient_modifiable"]);
  $soc->codefournisseur_modifiable = stripslashes($_POST["codefournisseur_modifiable"]);
  $soc->client                = 1;
  $soc->fournisseur           = 0;

  if (!$error && $verif == "ok")
    {     
      $soc->code_client           = $_POST["code_client"]."00";
      $result = $soc->create($user);
      
      if ($result == 0)
	{
	  
	}
      else
	{
	  $error++;
	}
    }

  $contact->name         = $_POST["cnom"];
  $contact->firstname    = $_POST["cprenom"];
  $contact->email        = $_POST["cmail"];
  
  if (!$error && $verif == "ok")
    {
      $contact->socid = $soc->id;
      
      if ( $contact->create($user) > 0)
	{
	  
	}
      else
	{
	  $error++;
	}
    }

  if (!$error && $verif == "ok")
    {
      $contrat = new TelephonieContrat($db);
      
      $contrat->client_comm     = $soc->id;
      $contrat->client          = $soc->id;
      $contrat->client_facture  = $soc->id;
      $contrat->commercial_sign = $_POST["commercial_sign"];
      
      if ( $contrat->create($user) == 0)
	{
	  $contrat->add_contact_facture($contact->id);
	}
      else
	{
	  $error++;
	}
    }
  
  if(!$error && $verif == "ok")
    {
      $contrat->commercial_sign_id = $_POST["commercial_sign"];
      $contrat->addpo($_POST["montantpo"], $user);
    }

  $ligne = new LigneTel($db);
  $ligne->contrat         = $contrat->id;
  $ligne->numero          = "0".$_POST["cli"];
  $ligne->client_comm     = $soc->id;
  $ligne->client          = $soc->id;
  $ligne->client_facture  = $soc->id;
  $ligne->fournisseur     = $_POST["fournisseur"];
  $ligne->commercial_sign = $_POST["commercial_sign"];
  $ligne->commercial_suiv = $_POST["commercial_sign"];
  $ligne->concurrent      = $_POST["concurrent"];
  $ligne->remise          = "0";
  $ligne->note            = $_POST["note"];
  
  if(!$error && $verif == "ok")
    {
      if (strlen(trim($_POST["cli"])) == 9)
	{

	  
	  if ( $ligne->create($user) == 0)
	    {
	      
	    }
	  else
	    {
	      $error++;
	      $msg.= "Impossible de créer la ligne";
	    }
	}
    }
  
  if (!$error && $verif == "ok")
    {
      Header("Location: ".DOL_URL_ROOT."/telephonie/contrat/fiche.php?id=".$contrat->id);
    }	  
  
}

/**
 *
 *
 */

llxHeader();

$form = new Form($db);

if ($user->rights->telephonie->ligne->creer)
{

  dolibarr_fiche_head($head, $hselected, 'Nouveau client');
  /*

   */

  if ($mesg)
    {
      print '<div class="error">';
      print $mesg;
      print '</div>';
    }
  else
    {
  print "<p>Attention ce formulaire n'est a utiliser uniquement pour les nouveaux clients. Il créé automatiquement un client, ses informations bancaires, son contrat (en mode paiement prélèvement) et la première ligne téléphonie, si il y a plusieurs ligne il suffit de les ajouter au contrat ultérieurement</p>";
    }

  $focus = " onfocus=\"this.className='focus';\" onblur=\"this.className='normal';\" ";   


  print '<FORM NAME="formClient" action="new.php" method="post">';
  print '<input type="hidden" name="codeclient_modifiable" value="1">';
  print '<input type="hidden" name="codefournisseur_modifiable" value="1">';
  print '<input type="hidden" name="action" value="add">';

  print '<div id="corpForm">';
  print '<fieldset id="societe">';
  print "<legend>Société</legend>\n";

  print '<table class="noborder" width="100%">';
  
  print '<tr><td width="20%">'.$langs->trans('Name').'</td><td colspan="3">';
  print '<input type="text" size="30" name="nom" ';
  print $focus .' value="'.$soc->nom.'"></td></tr>';
  
  // On positionne pays_id, pays_code et libelle du pays choisi
  $soc->pays_id=$_POST["pays_id"]?$_POST["pays_id"]:(defined(MAIN_INFO_SOCIETE_PAYS)?MAIN_INFO_SOCIETE_PAYS:'');
  if ($soc->pays_id)
    {
      $sql = "SELECT code, libelle from ".MAIN_DB_PREFIX."c_pays where rowid = ".$soc->pays_id;
      $resql=$db->query($sql);
      if ($resql)
	{
	  $obj = $db->fetch_object($resql);
	}
      else
	{
	  dolibarr_print_error($db);
	}
      $soc->pays_code=$obj->code;
      $soc->pays=$obj->libelle;
    }  
    
  print '<tr><td width="20%">'.$langs->trans('CustomerCode').'</td><td colspan="3">';

  print '<input size="7" type="text" name="code_client" maxlength="6"';
  print " onfocus=\"this.className='focus';\" onblur=\"this.className='normal';\" "; 
  print ' value="'.$soc->code_client.'">00</td>';

  print '<tr><td>'.$langs->trans('Address').'</td><td colspan="3"><textarea '.$focus.' name="adresse" cols="40" rows="2" wrap="soft">';
  print $soc->adresse;
  print '</textarea></td></tr>';
  
  print '<tr><td>'.$langs->trans('Zip').'</td><td colspan="3">';
  print '<input size="7" maxlength="6" type="text" name="cp" ';
  print " onfocus=\"this.className='focus';\" onblur=\"this.className='normal';\" "; 
  print ' value="'.$soc->cp.'">&nbsp;';

  print $langs->trans('Town').'&nbsp;<input type="text" '.$focus.' name="ville" value="'.$soc->ville.'"></td></tr>';
  
    print '<tr><td>'.$langs->trans('Phone').'</td><td><input type="text" size="11" maxlength="10" '.$focus.' name="tel" value="'.$soc->tel.'"></td>';
    print '<td>'.$langs->trans('Fax').'</td><td><input type="text" '.$focus.' name="fax" size="11" maxlength="10" value="'.$soc->fax.'"></td></tr>';
        	         	   

    print '<tr><td>RIB</td><td colspan="3">';
    print '<input type="text" size="6" maxlength="5" '.$focus.' name="rib_banque" value="'.$_POST["rib_banque"].'">';
    print '<input type="text" size="6" maxlength="5" '.$focus.' name="rib_guichet" value="'.$_POST["rib_guichet"].'">';
    print '<input type="text" size="12" maxlength="11" '.$focus.' name="rib_compte" value="'.$_POST["rib_compte"].'">';
    print '<input type="text" size="3" maxlength="2" '.$focus.' name="rib_cle" value="'.$_POST["rib_cle"].'">';
    print '&nbsp;&nbsp;IBAN&nbsp;&nbsp;';
    print '<input type="text" size="4" maxlength="4" '.$focus.' name="rib_iban" value="'.$_POST["rib_iban"].'">';
    print '</td></tr>';
      
    print "</table>\n";
    print "</fieldset><br />\n";

    print '<fieldset id="contact">';
    print "<legend>Contact</legend>\n";
    print '<table class="noborder" width="100%">';
    
    print '<tr><td width="20%">'.$langs->trans('Name').'</td><td><input type="text" size="30" '.$focus.' name="cnom" value="'.$contact->nom.'"></td></tr>';
    print '<tr><td width="20%">'.$langs->trans('Firstname').'</td><td><input type="text" size="20" '.$focus.' name="cprenom" value="'.$contact->prenom.'"></td></tr>';
    print '<tr><td>'.$langs->trans('Mail').'</td><td><input type="text" size="40" '.$focus.' name="cmail" value="'.$contact->email.'"></td></tr>';
    
    print "</table>\n";
    print "</fieldset><br />\n";

    print '<fieldset id="contact">';
    print "<legend>Commercial</legend>\n";
    print '<table class="noborder" width="100%">';
    print '<tr><td width="20%">Commercial Signature</td><td >';
    $ff = array();
    $sql = "SELECT u.rowid, u.firstname, u.name";
    $sql .= " FROM ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."usergroup_user as ug";
    $sql .= " WHERE u.rowid = ug.fk_user";
    $sql .= " AND ug.fk_usergroup = '".TELEPHONIE_GROUPE_COMMERCIAUX_ID."'";
    $sql .= " ORDER BY name ";
    if ( $db->query( $sql) )
      {
	$num = $db->num_rows();
	if ( $num > 0 )
	  {
	    while ($row = $db->fetch_row($resql))
	      {
		$ff[$row[0]] = $row[1] . " " . $row[2];
	      }
	  }
	$db->free();
	
      }
    
    $form->select_array("commercial_sign",$ff,$ligne->commercial_sign);
    
    print '</td>';
    
    print '<td width="20%">PO mensuelle</td><td valign="top" colspan="2">';
    print '<input '.$focus.' name="montantpo" size="8" value="'.$_POST["montantpo"].'"> euros HT</td></tr>';	  
    print '</td></tr>';	  	  
    
    print "</table>\n";
    print "</fieldset><br />\n";

    print '<fieldset id="ligne">';
    print "<legend>Ligne téléphonique à présélectionner</legend>\n";
  
    print '<table class="noborder" width="100%">';
    
    print '<tr><td width="20%">Ligne téléphonique #1</td><td>0<input type="text" size="10" maxlength="9" '.$focus.' name="cli" value="'.$_POST["cli"].'"></td>';
    print '<td colspan="2">Si le client a plusieurs lignes vous pourrez les ajouter au contrat ultérieuremnt</td></tr>';
    
    print '<tr><td width="20%">Fournisseur</td><td>';
    $ff = array();
    $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."telephonie_fournisseur WHERE commande_active = 1 ORDER BY nom ";
    $resql = $db->query($sql);
    if ($resql)
      {
	while ($row = $db->fetch_row($resql))
	  {
	    $ff[$row[0]] = $row[1];
	  }
	$db->free($resql);	
      }
    $form->select_array("fournisseur",$ff,$ligne->fournisseur);
    print '</td></tr>';
    
    print '<tr><td width="20%">Fournisseur précédent</td><td colspan="3">';
    $ff = array();
    $sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."telephonie_concurrents ORDER BY rowid ";
    $resql =  $db->query( $sql) ;
    if ($resql)
      {
	$num = $db->num_rows($resql);
	if ( $num > 0 )
	  {
	    while ($row = $db->fetch_row($resql))
	      {
		$ff[$row[0]] = $row[1];
	      }
	  }
	$db->free();
	
      }
    $form->select_array("concurrent",$ff,$ligne->concurrent);
    print '</td></tr>';
    
    print "</table>\n";
    print "</fieldset><br />\n";
        
    print '<input type="submit" value="'.$langs->trans('Save').'">'."\n";
    
    print '</form>'."\n";
    print "</div>\n";
}


$db->close();


llxFooter('$Date$ - $Revision$');
?>

