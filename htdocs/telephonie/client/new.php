<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

/**
 \file       htdocs/telephonie/client/new.php
 \ingroup    telephonie
 \brief      Creation d'un nouveau client
 \version    $Id$
 */

require("pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/contact.class.php');
require_once(DOL_DOCUMENT_ROOT.'/companybankaccount.class.php');
require_once(DOL_DOCUMENT_ROOT."/telephonie/lignetel.class.php");
require_once(DOL_DOCUMENT_ROOT."/telephonie/telephonie.contrat.class.php");

if (!$user->rights->telephonie->ligne->creer) accessforbidden();

$user->getrights('societe');
$langs->load("companies");

$soc = new Societe($db);
$contact = new Contact($db);
$rib = new CompanyBankAccount($db);


/*
 * Actions
 */

if ($_POST["action"] == 'add')
{
	$error = 0;
	$verif = "ok";
	$mesg = '';


	$contact->name         = $_POST["cnom"];
	$contact->firstname    = $_POST["cprenom"];
	$contact->email        = strtolower($_POST["cmail"]);

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
	$rib->iban         = $_POST["rib_iban"];
	$rib->proprio      = $_POST["titulaire"];


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

		if (strlen(trim($_POST["titulaire"])) == 0 && $verif == 'ok')
		{
			$mesg = "Vous devez indiquer le titulaire du compte";
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

		if (strlen(trim($_POST["cnom"])) == 0 && $verif == 'ok')
		{
			$mesg = "Nom de contact manquant";
			$verif = "nok";
		}

		if (! isValidEmail(trim($contact->email)) && $verif == 'ok')
		{
			$mesg = "Email invalide";
			$verif = "nok";
		}

		if (! isValidMailDomain(trim($contact->email)) && $verif == 'ok')
		{
			$mesg = "Email invalide (domaine invalide)";
			$verif = "nok";
		}
	}


	if (strlen(trim($_POST["cli"])) <> 9 && $verif == 'ok')
	{
		$mesg = "Numéro de ligne #1 (0".$_POST["cli"].") incorrect";
		$verif = "nok";
	}

	if (strlen(trim($_POST["cliend"])) > 0 && strlen(trim($_POST["cliend"])) <> 9 && $verif == 'ok')
	{
		$mesg = "Numéro de ligne dernier SDA (0".$_POST["cliend"].") incorrect";
		$verif = "nok";
	}

	$p = array("1","2","3","4","5");

	if (!in_array(substr(trim($_POST["cli"]),0,1), $p) && $verif == 'ok')
	{
		$mesg = "Numéro de ligne #1 (0".$_POST["cli"].") incorrect";
		$verif = "nok";
	}

	$ligne = new LigneTel($db);
	$ligne->fetch("0".trim($_POST["cli"]));
	if ($ligne->id > 0 && $verif == 'ok')
	{
		$mesg = "La ligne #1 : 0".$_POST["cli"]." existe déjà !";
		$verif = "nok";
	}


	/* Ligne #2 */

	if (strlen(trim($_POST["cli2"])) > 0 && $verif == 'ok')
	{
		if (strlen(trim($_POST["cli2"])) <> 9 && $verif == 'ok')
		{
			$mesg = "Numéro de ligne #2 (0".$_POST["cli2"].") incorrect";
			$verif = "nok";
		}

		if (!in_array(substr(trim($_POST["cli2"]),0,1), $p) && $verif == 'ok')
		{
			$mesg = "Numéro de ligne #2 (0".$_POST["cli2"].") incorrect";
			$verif = "nok";
		}

		$ligne = new LigneTel($db);
		$ligne->fetch("0".trim($_POST["cli2"]));
		if ($ligne->id > 0 && $verif == 'ok')
		{
			$mesg = "La ligne #2 : 0".$_POST["cli2"]." existe déjà !";
			$verif = "nok";
		}
	}
	/* Ligne #3 */

	if (strlen(trim($_POST["cli3"])) > 0 && $verif == 'ok')
	{
		if (strlen(trim($_POST["cli3"])) <> 9 && $verif == 'ok')
		{
			$mesg = "Numéro de ligne #3 (0".$_POST["cli3"].") incorrect";
			$verif = "nok";
		}

		if (!in_array(substr(trim($_POST["cli3"]),0,1), $p) && $verif == 'ok')
		{
			$mesg = "Numéro de ligne #3 (0".$_POST["cli3"].") incorrect";
			$verif = "nok";
		}

		$ligne = new LigneTel($db);
		$ligne->fetch("0".trim($_POST["cli3"]));
		if ($ligne->id > 0 && $verif == 'ok')
		{
			$mesg = "La ligne #3 : 0".$_POST["cli3"]." existe déjà !";
			$verif = "nok";
		}
	}

	/* Verif Tarif */
	if (strlen(trim($_POST["france"])) > 0 && $verif == "ok")
	{
		$temporel = ereg_replace(",",".",trim($_POST["france"]));

		if(! is_numeric($temporel))
		{
			$error = 1030;
			$verif = "nok";
			$mesg .= "Tarif France Invalide";
		}
		else
		{
			if ($temporel > 0.04 )
			{
				$error = 1031;
				$verif = "nok";
				$mesg .= "Tarif France Invalide : $temporel > 0.04 !";
			}

			if ($temporel < 0.016 )
			{
				$error = 1031;
				$verif = "nok";
				$mesg .= "Tarif France Invalide : $temporel <  0.016 !";
			}
		}
	}
	if (strlen(trim($_POST["mobil"])) > 0 && $verif == "ok")
	{
		$temporel = ereg_replace(",",".",trim($_POST["mobil"]));

		if(! is_numeric($temporel))
		{
			$error++;
			$verif = "nok";
			$mesg .= "Tarif Mobile Invalide";
		}
		else
		{
			if ($temporel > 0.40 )
			{
				$error = 1033;
				$verif = "nok";
				$mesg .= "Tarif Mobile Invalide : $temporel > 0.40 !";
			}
			if ($temporel <  0.14 )
			{
				$error = 1034;
				$verif = "nok";
				$mesg .= "Tarif Mobile Invalide : $temporel <  0.14 !";
			}
		}
	}

	/* Fin Verif Tarif */

	$soc->nom                   = $_POST["nom"];
	$soc->adresse               = $_POST["adresse"];
	$soc->cp                    = $_POST["cp"];
	$soc->ville                 = $_POST["ville"];
	$soc->pays_id               = $_POST["pays_id"];
	$soc->tel                   = $_POST["tel"];
	$soc->fax                   = $_POST["fax"];
	$soc->url                   = ereg_replace( "http://", "", $_POST["url"] );
	$soc->code_client           = $_POST["code_client"];
	$soc->code_fournisseur      = $_POST["code_fournisseur"];
	$soc->codeclient_modifiable = $_POST["codeclient_modifiable"];
	$soc->codefournisseur_modifiable = $_POST["codefournisseur_modifiable"];
	$soc->client                = 1;
	$soc->fournisseur           = 0;

	if (!$error && $verif == "ok")
	{
		$soc->code_client = $_POST["code_client"]."00";
		$result = $soc->create($user);

		if ($result == 0)
		{
			$soc->AddPerms(1,1,1,1);
			$soc->AddPerms(5,1,1,1);
			$soc->AddPerms(9,1,1,1);
			$soc->AddPerms($user->id,1,1,1);
			$soc->AddPerms($_POST["commercial_sign"],1,0,0);
		}
		else
		{
			$mesg = nl2br($soc->error) . " (result $result)\n";
			$error = 1035;
		}
	}

	if (!$error && $verif == "ok")
	{
		$contact->socid = $soc->id;

		if ( $contact->create($user) > 0)
		{

		}
		else
		{
			$error = 1024;
		}
	}


	if ((strlen(trim($_POST["rib_banque"])) + strlen(trim($_POST["rib_guichet"])) + strlen(trim($_POST["rib_compte"])) + strlen(trim($_POST["rib_cle"])))<> 0 && $verif == 'ok' && !$error)
	{
		$rib->socid = $soc->id;
		if ( $rib->update($user) > 0)
		{

		}
		else
		{
			$error = 1025;
		}
	}

	if (!$error && $verif == "ok")
	{
		$contrat = new TelephonieContrat($db);

		$contrat->client_comm     = $soc->id;
		$contrat->client          = $soc->id;
		$contrat->client_facture  = $soc->id;
		$contrat->commercial_sign = $_POST["commercial_sign"];

		if ( $contrat->create($user,'oui',$_POST["mode_paiement"]) == 0)
		{
			$contrat->add_contact_facture($contact->id);
		}
		else
		{
			$error = 1026;
		}
	}

	if(!$error && $verif == "ok")
	{
		$contrat->commercial_sign_id = $_POST["commercial_sign"];
		$contrat->addpo($_POST["montantpo"], $user);
	}

	/* Ligne 1 */

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

	  if ( $ligne->create($user, $_POST["mode_paiement"]) == 0)
	  {

	  }
	  else
	  {
	  	$error = 1027;
	  	$mesg.= "Impossible de créer la ligne #1 0".$_POST["cli"];
	  }
		}
	}

	/* SDA */

	if(!$error && $verif == "ok")
	{
		if (strlen(trim($_POST["cli"])) == 9 && strlen(trim($_POST["cliend"])) == 9)
		{
	  $cbegin = trim($_POST["cli"]) + 1;
	  $cend = trim($_POST["cliend"]);

	  $cli = $cbegin;

	  while ($cli <= $cend)
	  {
	  	$ligne = new LigneTel($db);
	  	$ligne->contrat         = $contrat->id;
	  	$ligne->numero          = "0".$cli;
	  	$ligne->client_comm     = $soc->id;
	  	$ligne->client          = $soc->id;
	  	$ligne->client_facture  = $soc->id;
	  	$ligne->fournisseur     = $_POST["fournisseur"];
	  	$ligne->commercial_sign = $_POST["commercial_sign"];
	  	$ligne->commercial_suiv = $_POST["commercial_sign"];
	  	$ligne->concurrent      = $_POST["concurrent"];
	  	$ligne->remise          = "0";
	  	$ligne->note            = $_POST["note"];

	  	if ( $ligne->create($user, $_POST["mode_paiement"]) == 0)
	  	{

	  	}
	  	else
	  	{
	  		$error = 1027;
	  		$mesg.= "Impossible de créer la ligne 0$cli";
	  	}

	  	$cli++;
	  }
		}
	}

	/* Ligne 2 */

	$ligne = new LigneTel($db);
	$ligne->contrat         = $contrat->id;
	$ligne->numero          = "0".$_POST["cli2"];
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
		if (strlen(trim($_POST["cli2"])) == 9)
		{

	  if ( $ligne->create($user, $_POST["mode_paiement"]) == 0)
	  {

	  }
	  else
	  {
	  	//$error++;
	  	$error = 1028;
	  	$mesg.= "Impossible de créer la ligne #2 0".$_POST["cli2"];
	  }
		}
	}

	/* Ligne 3 */
	$ligne = new LigneTel($db);
	$ligne->contrat         = $contrat->id;
	$ligne->numero          = "0".$_POST["cli3"];
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
		if (strlen(trim($_POST["cli3"])) == 9)
		{

	  if ( $ligne->create($user, $_POST["mode_paiement"]) == 0)
	  {

	  }
	  else
	  {
	  	//$error++;
	  	$error = 1029;
	  	$mesg.= "Impossible de créer la ligne #3 0".$_POST["cli3"];
	  }
		}
	}

	/* DEBUT TARIFS */
	if (strlen(trim($_POST["france"])) > 0 && $verif == "ok")
	{
		$temporel = ereg_replace(",",".",trim($_POST["france"]));

		if (!$error)
		{
	  $db->begin();

	  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."telephonie_tarif_client";
	  $sql .= " (fk_tarif, fk_client, temporel, fixe, fk_user) VALUES ";
	  $sql .= " (1293,".$soc->id.",'".$temporel."','0',".$user->id.")";

	  if (! $db->query($sql) )
	  {
	  	$error++;
	  }

	  $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_tarif_client_log";
	  $sql .= " (fk_tarif, fk_client, temporel, fixe, fk_user, datec) VALUES ";
	  $sql .= " (1293,".$soc->id.",'".$temporel."','0',".$user->id.",now())";

	  if (! $db->query($sql) )
	  {
	  	$error++;
	  }

	  if ( $error == 0 )
	  {
	  	$db->commit();
	  }
	  else
	  {
	  	$db->rollback();
	  	$mesg = "Erreur tarifs !";
	  }
		}
	}

	/* mobiles */
	if (strlen(trim($_POST["mobil"])) > 0 && $verif == "ok")
	{
		$mobil_ids = array(1289,1290,1291,1292);
		foreach ($mobil_ids as $mobil_id)
		{
	  $temporel = ereg_replace(",",".",trim($_POST["mobil"]));

	  if (!$error)
	  {
	  	$db->begin();

	  	$sql = "REPLACE INTO ".MAIN_DB_PREFIX."telephonie_tarif_client";
	  	$sql .= " (fk_tarif, fk_client, temporel, fixe, fk_user) VALUES ";
	  	$sql .= " (".$mobil_id.",".$soc->id.",'".$temporel."','0',".$user->id.")";

	  	if (! $db->query($sql) )
	  	{
	  		$error++;
	  	}

	  	$sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_tarif_client_log";
	  	$sql .= " (fk_tarif, fk_client, temporel, fixe, fk_user, datec) VALUES ";
	  	$sql .= " (".$mobil_id.",".$soc->id.",'".$temporel."','0',".$user->id.",now())";

	  	if (! $db->query($sql) )
	  	{
	  		$error++;
	  	}

	  	if ( $error == 0 )
	  	{
	  		$db->commit();
	  	}
	  	else
	  	{
	  		$db->rollback();
	  		$mesg = "Erreur tarifs !";
	  	}
	  }
		}
	}

	/* FIN TARIFS */

	if (!$error && $verif == "ok")
	{
		Header("Location: ".DOL_URL_ROOT."/telephonie/contrat/fiche.php?id=".$contrat->id);
	}
	else
	{
		$mesg .= " (numéro erreur : $error)";
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

	dol_fiche_head($head, $hselected, 'Nouveau client');
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

	}

	$focus = " onfocus=\"this.className='focus';\" onblur=\"this.className='normal';\" ";


	print '<FORM NAME="formClient" action="new.php" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="codeclient_modifiable" value="1">';
	print '<input type="hidden" name="codefournisseur_modifiable" value="1">';
	print '<input type="hidden" name="action" value="add">';

	print '<div id="corpForm">';
	print '<fieldset id="societe">';
	print "<legend>Société</legend>\n";

	print '<table class="noborder" width="100%">';

	print '<tr><td width="20%">'.$langs->trans('Name').'</td><td>';
	print '<input type="text" size="30" name="nom" ';
	print $focus .' value="'.$soc->nom.'"></td><td>';

	print "Attention ce formulaire n'est a utiliser uniquement pour les nouveaux clients.</td></tr>";

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
	  dol_print_error($db);
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

	print "</table>\n";
	print "</fieldset><br />\n";
	print '<fieldset id="contact">';
	print "<legend>Coordonnées bancaires</legend>\n";
	print '<table class="noborder" width="100%">';

	print '<tr><td width="20%">Titulaire du compte</td><td><input type="text" size="30" '.$focus.' name="titulaire" value="'.$_POST["titulaire"].'"></td></tr>';

	print '<tr><td width="20%">RIB</td><td colspan="3">';
	print '<input type="text" size="6" maxlength="5" '.$focus.' name="rib_banque" value="'.$_POST["rib_banque"].'">';
	print '<input type="text" size="6" maxlength="5" '.$focus.' name="rib_guichet" value="'.$_POST["rib_guichet"].'">';
	print '<input type="text" size="12" maxlength="11" '.$focus.' name="rib_compte" value="'.$_POST["rib_compte"].'">';
	print '<input type="text" size="3" maxlength="2" '.$focus.' name="rib_cle" value="'.$_POST["rib_cle"].'">';
	print '&nbsp;&nbsp;IBAN&nbsp;&nbsp;';
	print '<input type="text" size="4" maxlength="4" '.$focus.' name="rib_iban" value="'.$_POST["rib_iban"].'">';
	print '</td></tr>';

	print '<tr><td width="20%">Règlement</td><td colspan="3">';
	print '<select name="mode_paiement">';
	if ($_POST["mode_paiement"] == 'vir')
	{
		print '<option value="pre">Prélèvement</option>';
		print '<option value="vir" SELECTED>Virement</option>';
	}
	else
	{
		print '<option value="pre" SELECTED>Prélèvement</option>';
		print '<option value="vir">Virement</option>';
	}
	print '</select>';
	print '</td></tr>';

	print "</table>\n";
	print "</fieldset><br />\n";

	print '<fieldset id="contact">';
	print "<legend>Contact</legend>\n";
	print '<table class="noborder" width="100%">';

	print '<tr><td width="20%">'.$langs->trans('Name').'</td><td><input type="text" size="30" '.$focus.' name="cnom" value="'.$contact->name.'"></td>';
	print '<td width="20%">'.$langs->trans('Firstname').'</td><td><input type="text" size="20" '.$focus.' name="cprenom" value="'.$contact->firstname.'"></td></tr>';
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
	print "<legend>Lignes téléphoniques à présélectionner</legend>\n";

	print '<table class="noborder" width="100%">';

	print '<tr><td width="20%">Ligne téléphonique #1</td><td>0<input type="text" size="10" maxlength="9" '.$focus.' name="cli" value="'.$_POST["cli"].'"></td>';

	print '<td>Derniere SDA</td><td>0<input type="text" size="10" maxlength="9" '.$focus.' name="cliend" value="'.$_POST["cliend"].'"></td></tr>';



	print '<tr><td width="20%">Ligne téléphonique #2</td><td>0<input type="text" size="10" maxlength="9" '.$focus.' name="cli2" value="'.$_POST["cli2"].'"></td></tr>';

	print '<tr><td width="20%">Ligne téléphonique #3</td><td>0<input type="text" size="10" maxlength="9" '.$focus.' name="cli3" value="'.$_POST["cli3"].'"></td></tr>';

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

	$def =$ligne->fournisseur?$ligne->fournisseur:TELEPHONIE_FOURNISSEUR_DEFAUT_ID;

	$form->select_array("fournisseur",$ff,$def);
	print '</td>';

	print '<td width="20%">Fournisseur précédent</td><td>';
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

	/* DEBUT TARIFS */

	print '<fieldset id="ligne">';
	print "<legend>Tarifs</legend>\n";
	if ($user->rights->telephonie->tarif->client_modifier)
	{
		print '<table class="noborder" width="100%">';

		print '<tr><td width="20%">France</td><td><input type="text" size="10" maxlength="9" '.$focus.' name="france" value="'.$_POST["france"].'"></td><td>Laissez vide si tarifs par défaut</tr>';

		print '<tr><td width="20%">Mobiles</td><td><input type="text" size="10" maxlength="9" '.$focus.' name="mobil" value="'.$_POST["mobil"].'"></td><td>Tous réseaux confondus</td></tr>';

		print "</table>\n";
	}
	else
	{
		print "Vous n'avez pas les droits pour modifier les tarifs";
	}
	print "</fieldset><br />\n";

	/* FIN TARIFS */

	print '<input type="submit" value="'.$langs->trans('Save').'">'."\n";

	print '</form>'."\n";
	print "</div>\n";
}


$db->close();


llxFooter('$Date$ - $Revision$');
?>

