<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Brian Fraval         <brian@fraval.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@cap-networks.com>
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
 */

/**
   \file       htdocs/soc.php
   \ingroup    societe
   \brief      Onglet societe d'une societe
   \version    $Revision$
*/

require("pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");

$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");

$socid = isset($_GET["socid"])?$_GET["socid"]:'';

// Sécurité d'accès client et commerciaux
$socid = restrictedArea($user, 'societe', $socid);

// Initialisation de l'objet Societe
$soc = new Societe($db);


/*
 * Actions
 */

if ($_POST["getcustomercode"])
{
  // On défini valeur pour code_client
  $_POST["code_client"]="aa";
}

if ($_POST["getsuppliercode"])
{
  // On défini valeur pour code_fournisseur
  $_POST["code_fournisseur"]="aa";
}

if ((! $_POST["getcustomercode"] && ! $_POST["getsuppliercode"])
    && ($_POST["action"] == 'add' || $_POST["action"] == 'update') && $user->rights->societe->creer)
{
	if ($_REQUEST["private"] == 1)
	{
		$soc->nom                   = $_POST["nom"].' '.$_POST["prenom"];
		$soc->nom_particulier       = $_POST["nom"];
		$soc->prenom                = $_POST["prenom"];
		$soc->particulier           = $_REQUEST["private"];
	}
	else
	{
		$soc->nom                   = $_POST["nom"];
	}
	$soc->adresse               = $_POST["adresse"];
	$soc->cp                    = $_POST["cp"];
	$soc->ville                 = $_POST["ville"];
	$soc->pays_id               = $_POST["pays_id"];
	$soc->departement_id        = $_POST["departement_id"];
	$soc->tel                   = $_POST["tel"];
	$soc->fax                   = $_POST["fax"];
	$soc->email                 = $_POST["email"];
	$soc->url                   = $_POST["url"];
	$soc->siren                 = $_POST["idprof1"];
	$soc->siret                 = $_POST["idprof2"];
	$soc->ape                   = $_POST["idprof3"];
	$soc->idprof4               = $_POST["idprof4"];
	$soc->prefix_comm           = $_POST["prefix_comm"];
	$soc->code_client           = $_POST["code_client"];
	$soc->code_fournisseur      = $_POST["code_fournisseur"];
	$soc->capital               = $_POST["capital"];

	$soc->tva_assuj             = $_POST["assujtva_value"];
	$soc->tva_intra_code        = $_POST["tva_intra_code"];
	$soc->tva_intra_num         = $_POST["tva_intra_num"];
	$soc->tva_intra             = $_POST["tva_intra_code"] . $_POST["tva_intra_num"];

	$soc->forme_juridique_code  = $_POST["forme_juridique_code"];
	$soc->effectif_id           = $_POST["effectif_id"];
	if ($_REQUEST["private"] == 1)
	{
		$soc->typent_id             = 8; //todo prévoir autre méthode si le champs "particulier" change de rowid
	}
	else
	{
		$soc->typent_id             = $_POST["typent_id"];
	}
	$soc->client                = $_POST["client"];
	$soc->fournisseur           = $_POST["fournisseur"];
	$soc->fournisseur_categorie = $_POST["fournisseur_categorie"];
	
	$soc->commercial_id         = $_POST["commercial_id"];

	if ($_POST["action"] == 'add')
	{
		$result = $soc->create($user);
		
		if ($result >= 0)
		{
			if (  $soc->client == 1 )
			{
				Header("Location: comm/fiche.php?socid=".$soc->id);
				return;
			}
			else
			{
				if (  $soc->fournisseur == 1 )
				{
					Header("Location: fourn/fiche.php?socid=".$soc->id);
					return;
				}
				else
				{
					Header("Location: soc.php?socid=".$soc->id);
					return;
				}
			}
			exit;
		}
		else
		{
			$mesg=$soc->error;
			$_GET["action"]='create';
		}
	}

	if ($_POST["action"] == 'update')
	{
		if ($_POST["cancel"])
		{
			Header("Location: soc.php?socid=".$socid);
			exit;
		}
		
		$oldsoc=new Societe($db);
		$result=$oldsoc->fetch($socid);
		
		$result = $soc->update($socid,$user,1,$oldsoc->codeclient_modifiable(),$oldsoc->codefournisseur_modifiable());
		if ($result >= 0)
		{
			Header("Location: soc.php?socid=".$socid);
			exit;
		}
		else
		{
			$soc->id = $socid;
			$reload = 0;
			$mesg = $soc->error;
			$_GET["action"]= "edit";
		}
	}

}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes' && $user->rights->societe->supprimer)
{
  $soc = new Societe($db);
  $soc->fetch($socid);
  $result = $soc->delete($socid);
 
  if ($result == 0)
    {
      llxHeader();
      print '<div class="ok">'.$langs->trans("CompanyDeleted",$soc->nom).'</div>';
      llxFooter();
      exit ;
    }
  else
    {
      $reload = 0;
      $_GET["action"]='';
    }
}

/**
 *
 *
 */

llxHeader();

$form = new Form($db);
$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';


if ($_POST["getcustomercode"] || $_POST["getsuppliercode"] ||
	$_GET["action"] == 'create' || $_POST["action"] == 'create')
{
	/*
	*	Fiche en mode creation
	*/
	if ($user->rights->societe->creer)
	{
		// Charge objet modCodeTiers
		$module=$conf->global->SOCIETE_CODECLIENT_ADDON;
		if (! $module) dolibarr_error('',$langs->trans("ErrorModuleThirdPartyCodeInCompanyModuleNotDefined"));
		if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
		{
			$module = substr($module, 0, strlen($module)-4);
		}
		require_once(DOL_DOCUMENT_ROOT ."/includes/modules/societe/".$module.".php");
		$modCodeClient = new $module;
		$module=$conf->global->SOCIETE_CODEFOURNISSEUR_ADDON;
		if (! $module) $module=$conf->global->SOCIETE_CODECLIENT_ADDON;
		if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
		{
			$module = substr($module, 0, strlen($module)-4);
		}
		require_once(DOL_DOCUMENT_ROOT ."/includes/modules/societe/".$module.".php");
		$modCodeFournisseur = new $module;
		
		/*
		* Fiche societe en mode création
		*/
		if ($_GET["type"]=='f') { $soc->fournisseur=1; }
		if ($_GET["type"]=='c') { $soc->client=1; }
		if ($_GET["type"]=='p') { $soc->client=2; }
		if ($_REQUEST["private"]==1) { $soc->particulier=1;	}

		$soc->nom=$_POST["nom"];
		$soc->prenom=$_POST["prenom"];
		$soc->particulier=$_REQUEST["private"];
		$soc->prefix_comm=$_POST["prefix_comm"];
		$soc->client=$_POST["client"]?$_POST["client"]:$soc->client;
		$soc->code_client=$_POST["code_client"];
		$soc->fournisseur=$_POST["fournisseur"]?$_POST["fournisseur"]:$soc->fournisseur;
		$soc->code_fournisseur=$_POST["code_fournisseur"];
		$soc->adresse=$_POST["adresse"];
		$soc->cp=$_POST["cp"];
		$soc->ville=$_POST["ville"];
		$soc->departement_id=$_POST["departement_id"];
		$soc->tel=$_POST["tel"];
		$soc->fax=$_POST["fax"];
		$soc->email=$_POST["email"];
		$soc->url=$_POST["url"];
		$soc->capital=$_POST["capital"];
		$soc->siren=$_POST["idprof1"];
		$soc->siret=$_POST["idprof2"];
		$soc->ape=$_POST["idprof3"];
		$soc->idprof4=$_POST["idprof4"];
		$soc->typent_id=($_POST["typent_id"]&&!$_POST["cleartype"])?$_POST["typent_id"]:($_REQUEST["private"]?'TE_PRIVATE':'');
		$soc->effectif_id=($_POST["effectif_id"]&&!$_POST["cleartype"])?$_POST["effectif_id_id"]:($_REQUEST["private"]?'EF1-5':'');
		
		$soc->tva_assuj = $_POST["assujtva_value"];
		$soc->tva_intra_code=$_POST["tva_intra_code"];
		$soc->tva_intra_num=$_POST["tva_intra_num"];
		
		$soc->commercial_id=$_POST["commercial_id"];

		// On positionne pays_id, pays_code et libelle du pays choisi
		$soc->pays_id=$_POST["pays_id"]?$_POST["pays_id"]:$conf->global->MAIN_INFO_SOCIETE_PAYS;
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
		
		print_titre($langs->trans("NewCompany"));

		//$conf->use_javascript=0;
		if ($conf->use_javascript)
		{
			print "<br>\n";
			print $langs->trans("ThirdPartyType").': &nbsp; ';
			print '<input type="radio" class="flat" name="private" value="0"'.(! $_REQUEST["private"]?' checked="true"':'');
			print 'onclick="dolibarr_type_reload(0)"';
			print '> '.$langs->trans("Company/Fundation");
			print ' &nbsp; &nbsp; ';
			print '<input type="radio" class="flat" name="private" value="1"'.(! $_REQUEST["private"]?'':' checked="true"');
			print 'onclick="dolibarr_type_reload(1)"';
			print '> '.$langs->trans("Individual");
			print ' ('.$langs->trans("ToCreateContactWithSameName").')';		
			print "<br>\n";
			print "<br>\n";
		}
		
		if ($soc->error)
		{
			print '<div class="error">';
			print nl2br($soc->error);
			print '</div>';
		}
		
		print '<form action="soc.php" method="post" name="formsoc">';
		
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="cleartype" value="0">';
		print '<input type="hidden" name="private" value='.$soc->particulier.'>';
		
		print '<table class="border" width="100%">';
		
		print '<tr><td>'.$langs->trans('Name').'</td><td><input type="text" size="30" name="nom" value="'.$soc->nom.'"></td>';
		print '<td>'.$langs->trans('Prefix').'</td><td><input type="text" size="5" name="prefix_comm" value="'.$soc->prefix_comm.'"></td></tr>';
		
		if ($soc->particulier)
		{
			print '<tr><td>'.$langs->trans('FirstName').'</td><td><input type="text" size="30" name="prenom" value="'.$soc->firstname.'"></td>';
			print '<td colspan=2>&nbsp;</td></tr>';
		}
		
		// Client / Prospect
		print '<tr><td width="25%">'.$langs->trans('ProspectCustomer').'</td><td width="25%"><select class="flat" name="client">';
		print '<option value="2"'.($soc->client==2?' selected="true"':'').'>'.$langs->trans('Prospect').'</option>';
		print '<option value="1"'.($soc->client==1?' selected="true"':'').'>'.$langs->trans('Customer').'</option>';
		print '<option value="0"'.($soc->client==0?' selected="true"':'').'>Ni client, ni prospect</option>';
		print '</select></td>';

		print '<td width="25%">'.$langs->trans('CustomerCode').'</td><td width="25%">';
		print '<table class="nobordernopadding"><tr><td>';
		if ($modCodeClient->code_auto == 1)
		{
			print $langs->trans('AutomaticallyGenerated').'&nbsp;';
		}
		else
		{
			print '<input type="text" name="code_client" size="16" value="'.$soc->code_client.'" maxlength="15">';
		}
		print '</td><td>';
		$s=$modCodeClient->getToolTip($langs,$soc,0);
		print $form->textwithhelp('',$s,1);
		print '</td></tr></table>';

		print '</td></tr>';

		// Fournisseur
		print '<tr>';
		print '<td>'.$langs->trans('Supplier').'</td><td>';
		print $form->selectyesno("fournisseur",$soc->fournisseur,1);
		print '</td>';
		print '<td>'.$langs->trans('SupplierCode').'</td><td>';
		print '<table class="nobordernopadding"><tr><td>';
		if ($modCodeFournisseur->code_auto == 1)
		{
			print $langs->trans('AutomaticallyGenerated').'&nbsp;';
		}
		else
		{
			print '<input type="text" name="code_fournisseur" size="16" value="'.$soc->code_fournisseur.'" maxlength="15">';
		}
		print '</td><td>';
		$s=$modCodeFournisseur->getToolTip($langs,$soc,1);
		print $form->textwithhelp('',$s,1);
		print '</td></tr></table>';

		print '</td></tr>';

		if ($soc->fournisseur)
		{
			$load = $soc->LoadSupplierCateg();
			if ( $load == 0)
			{
				if (sizeof($soc->SupplierCategories) > 0)
				{
					print '<tr>';
					print '<td>'.$langs->trans('SupplierCategory').'</td><td colspan="3">';
					$form->select_array("fournisseur_categorie",$soc->SupplierCategories);
					print '</td></tr>';
				}
			}
		}

		print '<tr><td>'.$langs->trans('Address').'</td><td colspan="3"><textarea name="adresse" cols="40" rows="3" wrap="soft">';
		print $soc->adresse;
		print '</textarea></td></tr>';

		print '<tr><td>'.$langs->trans('Zip').'</td><td><input size="6" type="text" name="cp" value="'.$soc->cp.'"';
		if ($conf->use_javascript && $conf->global->MAIN_AUTO_FILLTOWNFROMZIP) print ' onChange="autofilltownfromzip_PopupPostalCode(cp.value,ville)"';
		print '>';
		if ($conf->use_javascript && $conf->global->MAIN_AUTO_FILLTOWNFROMZIP) print ' <input class="button" type="button" name="searchpostalcode" value="'.$langs->trans('FillTownFromZip').'" onclick="autofilltownfromzip_PopupPostalCode(cp.value,ville)">';
		print '</td>';
		print '<td>'.$langs->trans('Town').'</td><td><input type="text" name="ville" value="'.$soc->ville.'"></td></tr>';

		print '<tr><td width="25%">'.$langs->trans('Country').'</td><td colspan="3">';
		$form->select_pays($soc->pays_id,'pays_id',$conf->use_javascript?' onChange="autofilltownfromzip_save_refresh_create()"':'');
		print '</td></tr>';

		print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">';
		if ($soc->pays_id)
		{
			$form->select_departement($soc->departement_id,$soc->pays_code);
		}
		else
		{
			print $countrynotdefined;
		}
		print '</td></tr>';

		print '<tr><td>'.$langs->trans('Phone').'</td><td><input type="text" name="tel" value="'.$soc->tel.'"></td>';
		print '<td>'.$langs->trans('Fax').'</td><td><input type="text" name="fax" value="'.$soc->fax.'"></td></tr>';

		print '<tr><td>'.$langs->trans('EMail').'</td><td><input type="text" name="email" size="32" value="'.$soc->email.'"></td>';
		print '<td>'.$langs->trans('Web').'</td><td><input type="text" name="url" size="32" value="'.$soc->url.'"></td></tr>';

		print '<tr><td>'.$langs->trans('Capital').'</td><td colspan="3"><input type="text" name="capital" size="10" value="'.$soc->capital.'"> '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

		if ($soc->pays_code == 'FR')
		{
			$maxlength1=9;
			$maxlength2=14;
			$maxlength3=4;
			$maxlength4=12;
		}
		
		// Id prof
		print '<tr><td>'.($langs->transcountry("ProfId1",$soc->pays_code) != '-'?$langs->transcountry('ProfId1',$soc->pays_code):'').'</td><td>';
		if ($soc->pays_id)
		{
			if ($langs->transcountry("ProfId1",$soc->pays_code) != '-') print '<input type="text" name="idprof1" size="15" maxlength="'.$maxlength1.'" value="'.$soc->siren.'">';
			else print '&nbsp;';
		}
		else
		{
			print $countrynotdefined;
		}
		print '</td>';
		print '<td>'.($langs->transcountry("ProfId2",$soc->pays_code) != '-'?$langs->transcountry('ProfId2',$soc->pays_code):'').'</td><td>';
		if ($soc->pays_id)
		{
			if ($langs->transcountry("ProfId2",$soc->pays_code) != '-') print '<input type="text" name="idprof2" size="15" maxlength="'.$maxlength2.'" value="'.$soc->siret.'">';
			else print '&nbsp;';
		}
		else
		{
			print $countrynotdefined;
		}
		print '</td></tr>';
		
		print '<tr><td>'.($langs->transcountry("ProfId3",$soc->pays_code) != '-'?$langs->transcountry('ProfId3',$soc->pays_code):'').'</td><td>';
		if ($soc->pays_id)
		{
			if ($langs->transcountry("ProfId3",$soc->pays_code) != '-') print '<input type="text" name="idprof3" size="15" maxlength="'.$maxlength3.'" value="'.$soc->ape.'">';
			else print '&nbsp;';
		}
		else
		{
			print $countrynotdefined;
		}
		print '</td>';
		print '<td>'.($langs->transcountry("ProfId4",$soc->pays_code) != '-'?$langs->transcountry('ProfId4',$soc->pays_code):'').'</td><td>';
		if ($soc->pays_id)
		{
			if ($langs->transcountry("ProfId4",$soc->pays_code) != '-') print '<input type="text" name="idprof4" size="15" maxlength="'.$maxlength4.'" value="'.$soc->idprof4.'">';
			else print '&nbsp;';
		}
		else
		{
			print $countrynotdefined;
		}
		print '</td></tr>';

		// Forme juridique
		print '<tr><td>'.$langs->trans('JuridicalStatus').'</td>';
		print '<td colspan="3">';
		if ($soc->pays_id)
		{
			$form->select_forme_juridique($soc->forme_juridique_code,$soc->pays_code);
		}
		else
		{
			print $countrynotdefined;
		}
		print '</td></tr>';
		
		print '<tr><td>'.$langs->trans("Type").'</td><td>'."\n";
		$form->select_array("typent_id",$soc->typent_array(0), $soc->typent_id);
		print '</td>';
		print '<td>'.$langs->trans("Staff").'</td><td>';
		$form->select_array("effectif_id",$soc->effectif_array(0), $soc->effectif_id);
		print '</td></tr>';
		
		// Assujeti TVA
		$html = new Form($db);
		print '<tr><td>'.$langs->trans('VATIsUsed').'</td>';
		print '<td>';
		print $html->selectyesno('assujtva_value',1,1);		// Assujeti par défaut en creation
		print '</td>';

		// Code TVA
		if ($conf->use_javascript)
		{
			print "\n";
			print '<script language="JavaScript" type="text/javascript">';
			print "function CheckVAT(a,b) {\n";
			print "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?countryCode='+a+'&vatNumber='+b,'".$langs->trans("VATIntraCheckableOnEUSite")."',500,230);\n";
			print "}\n";
			print '</script>';
			print "\n";
		}
		print '<td nowrap="nowrap">'.$langs->trans('VATIntra').'</td>';
		print '<td nowrap="nowrap">';
		$s ='<input type="text" class="flat" name="tva_intra_code" size="1" maxlength="2" value="'.$soc->tva_intra_code.'">';
		$s.='<input type="text" class="flat" name="tva_intra_num" size="12" maxlength="18" value="'.$soc->tva_intra_num.'">';
		$s.=' ';
		if ($conf->use_javascript)
		{
			$s.='<a href="#" onclick="javascript: CheckVAT(document.formsoc.tva_intra_code.value,document.formsoc.tva_intra_num.value);" alt="'.$langs->trans("VATIntraCheckableOnEUSite").'">'.$langs->trans("VATIntraCheck").'</a>';
			print $form->textwithhelp($s,$langs->trans("VATIntraCheckDesc",$langs->trans("VATIntraCheck")),1);
		}
		else
		{
			print $s.'<a href="'.$langs->transcountry("VATIntraCheckURL",$soc->id_pays).'" target="_blank" alt="'.$langs->trans("VATIntraCheckableOnEUSite").'">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</a>';
		}
		print '</td>';

		print '</tr>';
		
		if ($user->rights->commercial->client->voir)
		{
			//Affecter un commercial
			print '<tr>';
			print '<td>'.$langs->trans("AllocateCommercial").'</td>';
			print '<td colspan="3">';
			$form->select_users($soc->commercial_id,'commercial_id',1);
			print '</td></tr>';
		}



		print '<tr><td colspan="4" align="center">';
		print '<input type="submit" class="button" value="'.$langs->trans('AddThirdParty').'"></td></tr>'."\n";
		
		print '</table>'."\n";
		print '</form>'."\n";
		
	}
}
elseif ($_GET["action"] == 'edit' || $_POST["action"] == 'edit')
{
    /*
     * Fiche societe en mode edition
     */
    print_titre($langs->trans("EditCompany"));

    if ($socid)
    {
		// Charge objet modCodeTiers
		$module=$conf->global->SOCIETE_CODECLIENT_ADDON;
		if (! $module) dolibarr_error('',$langs->trans("ErrorModuleThirdPartyCodeInCompanyModuleNotDefined"));
		if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
		{
			$module = substr($module, 0, strlen($module)-4);
		}
		require_once(DOL_DOCUMENT_ROOT ."/includes/modules/societe/".$module.".php");
		$modCodeClient = new $module;
		$module=$conf->global->SOCIETE_CODEFOURNISSEUR_ADDON;
		if (! $module) $module=$conf->global->SOCIETE_CODECLIENT_ADDON;
		if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
		{
			$module = substr($module, 0, strlen($module)-4);
		}
		require_once(DOL_DOCUMENT_ROOT ."/includes/modules/societe/".$module.".php");
		$modCodeFournisseur = new $module;
		
		if ($reload || ! $_POST["nom"])
        {
            $soc = new Societe($db);
            $soc->id = $socid;
            $soc->fetch($socid);
        }
        else
        {
            $soc->id=$_POST["socid"];
            $soc->nom=$_POST["nom"];
            $soc->prefix_comm=$_POST["prefix_comm"];
            $soc->client=$_POST["client"];
            $soc->code_client=$_POST["code_client"];
            $soc->fournisseur=$_POST["fournisseur"];
            $soc->code_fournisseur=$_POST["code_fournisseur"];
            $soc->adresse=$_POST["adresse"];
            $soc->zip=$_POST["zip"];
            $soc->ville=$_POST["ville"];
            $soc->pays_id=$_POST["pays_id"]?$_POST["pays_id"]:$conf->global->MAIN_INFO_SOCIETE_PAYS;
            $soc->departement_id=$_POST["departement_id"];
            $soc->tel=$_POST["tel"];
            $soc->fax=$_POST["fax"];
            $soc->email=$_POST["email"];
            $soc->url=$_POST["url"];
            $soc->capital=$_POST["capital"];
            $soc->siren=$_POST["idprof1"];
            $soc->siret=$_POST["idprof2"];
            $soc->ape=$_POST["idprof3"];
            $soc->idprof4=$_POST["idprof4"];
            $soc->typent_id=$_POST["typent_id"];
            $soc->effectif_id=$_POST["effectif_id"];

			$soc->tva_assuj = $_POST["assujtva_value"];
            $soc->tva_intra_code=$_POST["tva_intra_code"];
            $soc->tva_intra_num=$_POST["tva_intra_num"];

            // On positionne pays_id, pays_code et libelle du pays choisi
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
                $soc->pays=$langs->trans("Country".$obj->code)?$langs->trans("Country".$obj->code):$obj->libelle;
            }
        }

        if ($soc->error)
        {
            print '<div class="error">';
            print $soc->error;
            print '</div>';
        }

        print '<form action="soc.php?socid='.$soc->id.'" method="post" name="formsoc">';
        print '<input type="hidden" name="action" value="update">';
        print '<input type="hidden" name="socid" value="'.$soc->id.'">';

        print '<table class="border" width="100%">';

        print '<tr><td>'.$langs->trans('Name').'</td><td colspan="3"><input type="text" size="40" name="nom" value="'.$soc->nom.'"></td></tr>';

        print '<tr><td>'.$langs->trans("Prefix").'</td><td colspan="3">';
        print '<input type="text" size="5" name="prefix_comm" value="'.$soc->prefix_comm.'">';
        print '</td>';

        // Client / Prospect
        print '<tr><td width="25%">'.$langs->trans('ProspectCustomer').'</td><td width="25%"><select class="flat" name="client">';
        print '<option value="2"'.($soc->client==2?' selected="true"':'').'>'.$langs->trans('Prospect').'</option>';
        print '<option value="1"'.($soc->client==1?' selected="true"':'').'>'.$langs->trans('Customer').'</option>';
        print '<option value="0"'.($soc->client==0?' selected="true"':'').'>Ni client, ni prospect</option>';
        print '</select></td>';
        print '<td width="25%">'.$langs->trans('CustomerCode').'</td><td width="25%">';

        print '<table class="nobordernopadding"><tr><td>';
        if ($soc->codeclient_modifiable())
        {
        	print '<input type="text" name="code_client" size="16" value="'.$soc->code_client.'" maxlength="15">';
        }
        else
        {
        	print $soc->code_client;
        	print '<input type="hidden" name="code_client" value="'.$soc->code_client.'">';
        }
        print '</td><td>';
		$s=$modCodeClient->getToolTip($langs,$soc,0);
        print $form->textwithhelp('',$s,1);
        print '</td></tr></table>';

        print '</td></tr>';

        // Fournisseur
        print '<tr>';
        print '<td>'.$langs->trans('Supplier').'</td><td>';
        print $form->selectyesno("fournisseur",$soc->fournisseur,1);
        print '</td>';
        print '<td>'.$langs->trans('SupplierCode').'</td><td>';

        print '<table class="nobordernopadding"><tr><td>';
        if ($soc->codefournisseur_modifiable())
        {
        	print '<input type="text" name="code_fournisseur" size="16" value="'.$soc->code_fournisseur.'" maxlength="15">';
        }
        else
        {
        	print $soc->code_fournisseur;
        	print '<input type="hidden" name="code_fournisseur" value="'.$soc->code_fournisseur.'">';
        }
        print '</td><td>';
		$s=$modCodeFournisseur->getToolTip($langs,$soc,1);
        print $form->textwithhelp('',$s,1);
        print '</td></tr></table>';

        print '</td></tr>';

	if ($soc->fournisseur)
	  {
	    $load = $soc->LoadSupplierCateg();
	    if ( $load == 0)
	      {
		if (sizeof($soc->SupplierCategories) > 0)
		  {
		    print '<tr>';
		    print '<td>'.$langs->trans('SupplierCategory').'</td><td colspan="3">';
		    $form->select_array("fournisseur_categorie",$soc->SupplierCategories);
		    print '</td></tr>';
		  }
	      }
	  }

        print '<tr><td valign="top">'.$langs->trans('Address').'</td><td colspan="3"><textarea name="adresse" cols="40" rows="3" wrap="soft">';
        print $soc->adresse;
        print '</textarea></td></tr>';

        print '<tr><td>'.$langs->trans('Zip').'</td><td><input size="6" type="text" name="cp" value="'.$soc->cp.'"';
        if ($conf->use_javascript && $conf->global->MAIN_AUTO_FILLTOWNFROMZIP) print ' onChange="autofilltownfromzip_PopupPostalCode(cp.value,ville)"';
        print '>';
        if ($conf->use_javascript && $conf->global->MAIN_AUTO_FILLTOWNFROMZIP) print ' <input class="button" type="button" name="searchpostalcode" value="'.$langs->trans('FillTownFromZip').'" onclick="autofilltownfromzip_PopupPostalCode(cp.value,ville)">';
        print '</td>';

        print '<td>'.$langs->trans('Town').'</td><td><input type="text" name="ville" value="'.$soc->ville.'"></td></tr>';

        print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
        $form->select_pays($soc->pays_id,'pays_id',$conf->use_javascript?' onChange="autofilltownfromzip_save_refresh_edit()"':'');
        print '</td></tr>';

        print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">';
        $form->select_departement($soc->departement_id,$soc->pays_code);
        print '</td></tr>';

        print '<tr><td>'.$langs->trans('Phone').'</td><td><input type="text" name="tel" value="'.$soc->tel.'"></td>';
        print '<td>'.$langs->trans('Fax').'</td><td><input type="text" name="fax" value="'.$soc->fax.'"></td></tr>';

        print '<tr><td>'.$langs->trans('EMail').'</td><td><input type="text" name="email" size="32" value="'.$soc->email.'"></td>';
        print '<td>'.$langs->trans('Web').'</td><td><input type="text" name="url" size="32" value="'.$soc->url.'"></td></tr>';

        print '<tr>';
        // IdProf1 (SIREN pour France)
        $idprof=$langs->transcountry('ProfId1',$soc->pays_code);
        if ($idprof!='-')
        {
            print '<td>'.$idprof.'</td><td>';
            $form->id_prof(1,$soc,'idprof1',$soc->siren);
            print '</td>';
        }
        else print '<td>&nbsp;</td><td>&nbsp;</td>';
        // IdProf2 (SIRET pour France)
        $idprof=$langs->transcountry('ProfId2',$soc->pays_code);
        if ($idprof!='-')
        {
            print '<td>'.$idprof.'</td><td>';
            $form->id_prof(2,$soc,'idprof2',$soc->siret);
            print '</td>';
        }
        else print '<td>&nbsp;</td><td>&nbsp;</td>';
        print '</tr>';
        print '<tr>';
        // IdProf3 (APE pour France)
        $idprof=$langs->transcountry('ProfId3',$soc->pays_code);
        if ($idprof!='-')
        {
            print '<td>'.$idprof.'</td><td>';
            $form->id_prof(3,$soc,'idprof3',$soc->ape);
            print '</td>';
        }
        else print '<td>&nbsp;</td><td>&nbsp;</td>';
        // IdProf4 (NU pour France)
        $idprof=$langs->transcountry('ProfId4',$soc->pays_code);
        if ($idprof!='-')
        {
            print '<td>'.$idprof.'</td><td>';
            $form->id_prof(4,$soc,'idprof4',$soc->idprof4);
            print '</td>';
        }
        else print '<td>&nbsp;</td><td>&nbsp;</td>';
        print '</tr>';

		// Assujeti TVA
		print '<tr><td>'.$langs->trans('VATIsUsed').'</td><td>';
		print $form->selectyesno('assujtva_value',$soc->tva_assuj,1);
		print '</td>';

		// Code TVA
		if ($conf->use_javascript)
		{
			print "\n";
			print '<script language="JavaScript" type="text/javascript">';
			print "function CheckVAT(a,b) {\n";
			print "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?countryCode='+a+'&vatNumber='+b,'".$langs->trans("VATIntraCheckableOnEUSite")."',500,260);\n";
			print "}\n";
			print '</script>';
			print "\n";
		}
        print '<td nowrap="nowrap">'.$langs->trans('VATIntra').'</td>';
        print '<td nowrap="nowrap">';
        $s ='<input type="text" class="flat" name="tva_intra_code" size="1" maxlength="2" value="'.$soc->tva_intra_code.'">';
        $s.='<input type="text" class="flat" name="tva_intra_num" size="12" maxlength="18" value="'.$soc->tva_intra_num.'">';
		$s.=' ';
		if ($conf->use_javascript)
		{
	        $s.='<a href="#" onclick="javascript: CheckVAT(document.formsoc.tva_intra_code.value,document.formsoc.tva_intra_num.value);" alt="'.$langs->trans("VATIntraCheckableOnEUSite").'">'.$langs->trans("VATIntraCheck").'</a>';
	        print $form->textwithhelp($s,$langs->trans("VATIntraCheckDesc",$langs->trans("VATIntraCheck")),1);
		}
		else
		{
			print $s.'<a href="'.$langs->transcountry("VATIntraCheckURL",$soc->id_pays).'" target="_blank" alt="'.$langs->trans("VATIntraCheckableOnEUSite").'">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</a>';
		}
        print '</td>';
        print '</tr>';

        print '<tr><td>'.$langs->trans("Capital").'</td><td colspan="3"><input type="text" name="capital" size="10" value="'.$soc->capital.'"> '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

        print '<tr><td>'.$langs->trans('JuridicalStatus').'</td><td colspan="3">';
        $form->select_forme_juridique($soc->forme_juridique_code,$soc->pays_code);
        print '</td></tr>';

        print '<tr><td>'.$langs->trans("Type").'</td><td>';
        $form->select_array("typent_id",$soc->typent_array(0), $soc->typent_id);
        print '</td>';
        print '<td>'.$langs->trans("Staff").'</td><td>';
        $form->select_array("effectif_id",$soc->effectif_array(0), $soc->effectif_id);
        print '</td></tr>';

        print '<tr><td align="center" colspan="4">';
        print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
		print ' &nbsp; ';
        print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
        print '</td></tr>';

        print '</table>';
        print '</form>';
    }
}
else
{
    /*
     * Fiche société en mode visu
     */
    $soc = new Societe($db);
    $soc->id = $socid;
    $result=$soc->fetch($socid);
    if ($result < 0)
    {
        dolibarr_print_error($db,$soc->error);
        exit;
    }

	$head = societe_prepare_head($soc);
    
    dolibarr_fiche_head($head, 'company', $soc->nom);


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

	print '<form name="formsoc" method="post">';
	print '<table class="border" width="100%">';

    print '<tr><td width="20%">'.$langs->trans('Name').'</td><td colspan="3">'.$soc->nom.'</td></tr>';

    print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$soc->prefix_comm.'</td></tr>';

    if ($soc->client) {
        print '<tr><td>';
        print $langs->trans('CustomerCode').'</td><td colspan="3">';
        print $soc->code_client;
        if ($soc->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
        print '</td></tr>';
    }

    if ($soc->fournisseur) {
        print '<tr><td>';
        print $langs->trans('SupplierCode').'</td><td colspan="3">';
        print $soc->code_fournisseur;
        if ($soc->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
        print '</td></tr>';
    }
    
    print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->adresse)."</td></tr>";

    print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$soc->cp."</td>";
    print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$soc->ville."</td></tr>";

    print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">'.$soc->pays.'</td>';
    print '</td></tr>';

    print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">'.$soc->departement.'</td>';

    print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dolibarr_print_phone($soc->tel).'</td>';
    print '<td>'.$langs->trans('Fax').'</td><td>'.dolibarr_print_phone($soc->fax).'</td></tr>';

    print '<tr><td>'.$langs->trans('EMail').'</td><td>';
    if ($soc->email) { print '<a href="mailto:'.$soc->email.'" target="_blank">'.$soc->email.'</a>'; }
    else print '&nbsp;';
    print '</td>';
    print '<td>'.$langs->trans('Web').'</td><td>';
    if ($soc->url) { print '<a href="http://'.$soc->url.'" target="_blank">http://'.dolibarr_trunc($soc->url,32).'</a>'; }
    else print '&nbsp;';
    print '</td></tr>';

    // ProfId1 (SIREN pour France)
    $profid=$langs->transcountry('ProfId1',$soc->pays_code);
    if ($profid!='-')
    {
        print '<tr><td>'.$profid.'</td><td>';
        print $soc->siren;
        if ($soc->siren)
        {
            if ($soc->id_prof_check(1,$soc) > 0) print ' &nbsp; '.$soc->id_prof_url(1,$soc);
            else print ' <font class="error">('.$langs->trans("ErrorWrongValue").')</font>';
        }
        print '</td>';
    }
    else print '<tr><td>&nbsp;</td><td>&nbsp;</td>';
    // ProfId2 (SIRET pour France)
    $profid=$langs->transcountry('ProfId2',$soc->pays_code);
    if ($profid!='-')
    {
        print '<td>'.$profid.'</td><td>';
        print $soc->siret;
        if ($soc->siret)
        {
            if ($soc->id_prof_check(2,$soc) > 0) print ' &nbsp; '.$soc->id_prof_url(2,$soc);
            else print ' <font class="error">('.$langs->trans("ErrorWrongValue").')</font>';
        }
        print '</td></tr>';
    }
    else print '<td>&nbsp;</td><td>&nbsp;</td></tr>';

    // ProfId3 (APE pour France)
    $profid=$langs->transcountry('ProfId3',$soc->pays_code);
    if ($profid!='-')
    {
        print '<tr><td>'.$profid.'</td><td>';
        print $soc->ape;
        if ($soc->ape)
        {
            if ($soc->id_prof_check(3,$soc) > 0) print ' &nbsp; '.$soc->id_prof_url(3,$soc);
            else print ' <font class="error">('.$langs->trans("ErrorWrongValue").')</font>';
        }
        print '</td>';
    }
    else print '<tr><td>&nbsp;</td><td>&nbsp;</td>';
    // ProfId4 (NU pour France)
    $profid=$langs->transcountry('ProfId4',$soc->pays_code);
    if ($profid!='-')
    {
        print '<td>'.$profid.'</td><td>';
        print $soc->idprof4;
        if ($soc->idprof4)
        {
            if ($soc->id_prof_check(4,$soc) > 0) print ' &nbsp; '.$soc->id_prof_url(4,$soc);
            else print ' <font class="error">('.$langs->trans("ErrorWrongValue").')</font>';
        }
        print '</td></tr>';
    }
    else print '<td>&nbsp;</td><td>&nbsp;</td></tr>';

	// Assujeti TVA
	$html = new Form($db);
	print '<tr><td>';
	print $langs->trans('VATIsUsed');
	print '</td><td>';
	print yn($soc->tva_assuj);
	print '</td>';

	// VAT Code
	if ($conf->use_javascript)
	{
		print "\n";
		print '<script language="JavaScript" type="text/javascript">';
		print "function CheckVAT(a,b) {\n";
		print "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?countryCode='+a+'&vatNumber='+b,'".$langs->trans("VATIntraCheckableOnEUSite")."',500,260);\n";
		print "}\n";
		print '</script>';
		print "\n";
	}
    print '<td nowrap="nowrpa">'.$langs->trans('VATIntra').'</td><td>';
	if ($soc->tva_intra) 
	{
		$s='';
		$code=substr($soc->tva_intra,0,2);
		$num=substr($soc->tva_intra,2);
		$s.=$soc->tva_intra;
		$s.='<input type="hidden" name="tva_intra_code" size="1" maxlength="2" value="'.$code.'">';
		$s.='<input type="hidden" name="tva_intra_num" size="12" maxlength="18" value="'.$num.'">';
		$s.=' &nbsp; ';
		if ($conf->use_javascript)
		{
			$s.='<a href="#" onclick="javascript: CheckVAT(document.formsoc.tva_intra_code.value,document.formsoc.tva_intra_num.value);" alt="'.$langs->trans("VATIntraCheckableOnEUSite").'">'.$langs->trans("VATIntraCheck").'</a>';
			print $form->textwithhelp($s,$langs->trans("VATIntraCheckDesc",$langs->trans("VATIntraCheck")),1);
		}
		else
		{
			print $s.'<a href="'.$langs->transcountry("VATIntraCheckURL",$soc->id_pays).'" target="_blank" alt="'.$langs->trans("VATIntraCheckableOnEUSite").'">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</a>';
		}
	}
	else
	{
		print '&nbsp;';
	}
    print '</td>';
	
	print '</tr>';

    // Capital
    print '<tr><td>'.$langs->trans('Capital').'</td><td colspan="3">';
    if ($soc->capital) print $soc->capital.' '.$langs->trans("Currency".$conf->monnaie);
    else print '&nbsp;';
    print '</td></tr>';

    // Statut juridique
    print '<tr><td>'.$langs->trans('JuridicalStatus').'</td><td colspan="3">'.$soc->forme_juridique.'</td></tr>';

    // Type + Staff
    $arr = $soc->typent_array(1);
    $soc->typent= $arr[$soc->typent_code];
    print '<tr><td>'.$langs->trans("Type").'</td><td>'.$soc->typent.'</td><td>'.$langs->trans("Staff").'</td><td>'.$soc->effectif.'</td></tr>';

    // RIB
    print '<tr><td>';
    print '<table width="100%" class="nobordernopadding"><tr><td>';
    print $langs->trans('RIB');
    print '<td><td align="right">';
    if ($user->rights->societe->creer)
        print '<a href="'.DOL_URL_ROOT.'/societe/rib.php?socid='.$soc->id.'">'.img_edit().'</a>';
    else
        print '&nbsp;';
    print '</td></tr></table>';
    print '</td>';
    print '<td colspan="3">';
    print $soc->display_rib();
    print '</td></tr>';

    // Maison mère
    print '<tr><td>';
    print '<table width="100%" class="nobordernopadding"><tr><td>';
    print $langs->trans('ParentCompany');
    print '<td><td align="right">';
    if ($user->rights->societe->creer)
        print '<a href="'.DOL_URL_ROOT.'/societe/lien.php?socid='.$soc->id.'">'.img_edit() .'</a>';
    else
        print '&nbsp;';
    print '</td></tr></table>';
    print '</td>';
    print '<td colspan="3">';
    if ($soc->parent)
    {
        $socm = new Societe($db);
        $socm->fetch($soc->parent);
        print '<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$socm->id.'">'.img_object($langs->trans("ShowCompany"),'company').' '.$socm->nom.'</a>'.($socm->code_client?"(".$socm->code_client.")":"").' - '.$socm->ville;
    }
    else {
        print $langs->trans("NoParentCompany");
    }
    print '</td></tr>';

    // Commerciaux
    print '<tr><td>';
    print '<table width="100%" class="nobordernopadding"><tr><td>';
    print $langs->trans('SalesRepresentatives');
    print '<td><td align="right">';
    if ($user->rights->societe->creer)
        print '<a href="'.DOL_URL_ROOT.'/societe/commerciaux.php?socid='.$soc->id.'">'.img_edit().'</a>';
    else
        print '&nbsp;';
    print '</td></tr></table>';
    print '</td>';
    print '<td colspan="3">';

    $sql = "SELECT count(sc.rowid) as nb";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= " WHERE sc.fk_soc =".$soc->id;

    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $obj = $db->fetch_object($resql);
        print $obj->nb?($obj->nb):$langs->trans("NoSalesRepresentativeAffected");
    }
    else {
        dolibarr_print_error($db);
    }
    print '</td></tr>';

    print '</table>';
	print '</form>';
    print "</div>\n";
    /*
    *
    */
    if ($_GET["action"] == '')
    {
        print '<div class="tabsAction">';

        if ($user->rights->societe->creer)
        {
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/soc.php?socid='.$soc->id.'&amp;action=edit">'.$langs->trans("Edit").'</a>';
        }
        
        if ($conf->projet->enabled && $user->rights->projet->creer)
        {
            $langs->load("projects");
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/projet/fiche.php?socid='.$soc->id.'&action=create">'.$langs->trans("AddProject").'</a>';
        }

        if ($user->rights->societe->contact->creer)
        {
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$soc->id.'&amp;action=create">'.$langs->trans("AddContact").'</a>';
        }
        
        if ($user->rights->societe->supprimer)
        {
	        print '<a class="butActionDelete" href="'.DOL_URL_ROOT.'/soc.php?socid='.$soc->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
        }

        print '</div>';
    }
    
}

$db->close();


llxFooter('$Date$ - $Revision$');
?>
