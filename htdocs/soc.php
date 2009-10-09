<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Brian Fraval         <brian@fraval.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2008	   Patrick Raguin       <patrick.raguin@auguria.net>
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
 *  \file       htdocs/soc.php
 *  \ingroup    societe
 *  \brief      Third party card page
 *  \version    $Id$
 */

require("pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/html.formcompany.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
if ($conf->adherent->enabled) require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");

$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("banks");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);

// Initialisation de l'objet Societe
$soc = new Societe($db);


/*
 * Actions
 */

if ($_POST["getcustomercode"])
{
	// On defini valeur pour code_client
	$_POST["code_client"]="aa";
}

if ($_POST["getsuppliercode"])
{
	// On defini valeur pour code_fournisseur
	$_POST["code_fournisseur"]="aa";
}

// Add new third party
if ((! $_POST["getcustomercode"] && ! $_POST["getsuppliercode"])
&& ($_POST["action"] == 'add' || $_POST["action"] == 'update') && $user->rights->societe->creer)
{
	require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
	$error=0;

	if ($_REQUEST["private"] == 1)
	{
		$soc->particulier           = $_REQUEST["private"];

		$soc->nom                   = $_POST["nom"].' '.$_POST["prenom"];
		$soc->nom_particulier       = $_POST["nom"];
		$soc->prenom                = $_POST["prenom"];
		$soc->civilite_id           = $_POST["civilite_id"];
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
	$soc->email                 = trim($_POST["email"]);
	$soc->url                   = $_POST["url"];
	$soc->siren                 = $_POST["idprof1"];
	$soc->siret                 = $_POST["idprof2"];
	$soc->ape                   = $_POST["idprof3"];
	$soc->idprof4               = $_POST["idprof4"];
	$soc->prefix_comm           = $_POST["prefix_comm"];
	$soc->code_client           = $_POST["code_client"];
	$soc->code_fournisseur      = $_POST["code_fournisseur"];
	$soc->capital               = $_POST["capital"];
	$soc->gencod                = $_POST["gencod"];

	$soc->tva_assuj             = $_POST["assujtva_value"];
	$soc->tva_intra_code        = $_POST["tva_intra_code"];
	$soc->tva_intra_num         = $_POST["tva_intra_num"];
	$soc->tva_intra             = $_POST["tva_intra_code"] . $_POST["tva_intra_num"];

	$soc->forme_juridique_code  = $_POST["forme_juridique_code"];
	$soc->effectif_id           = $_POST["effectif_id"];
	if ($_REQUEST["private"] == 1)
	{
		$soc->typent_id             = 8; // TODO prevoir autre methode si le champs "particulier" change de rowid
	}
	else
	{
		$soc->typent_id             = $_POST["typent_id"];
	}
	$soc->client                = $_POST["client"];
	$soc->fournisseur           = $_POST["fournisseur"];
	$soc->fournisseur_categorie = $_POST["fournisseur_categorie"];

	$soc->commercial_id         = $_POST["commercial_id"];

	// Check parameters
	if (empty($_POST["cancel"]))
	{
		if (! empty($soc->email) && ! isValidEMail($soc->email))
		{
			$error = 1;
			$soc->error = $langs->trans("ErrorBadEMail",$soc->email);
			$_GET["action"] = $_POST["action"]=='add'?'create':'edit';
		}
		if ($soc->fournisseur && ! $conf->fournisseur->enabled)
		{
			$error = 1;
			$soc->error = $langs->trans("ErrorSupplierModuleNotEnabled");
			$_GET["action"] = $_POST["action"]=='add'?'create':'edit';
		}
	}

	if (! $error)
	{
		if ($_POST["action"] == 'add')
		{
			$db->begin();

			if (empty($soc->client))      $soc->code_client='';
			if (empty($soc->fournisseur)) $soc->code_fournisseur='';

			$result = $soc->create($user);
			if ($result >= 0)
			{
				if ($soc->particulier)
				{
					dol_syslog("This thirdparty is a personal people",LOG_DEBUG);
					$contact=new Contact($db);

					$contact->civilite_id = $soc->civilite_id;
					$contact->name=$soc->nom_particulier;
					$contact->firstname=$soc->prenom;
					$contact->address=$soc->adresse;
					$contact->cp=$soc->cp;
					$contact->ville=$soc->ville;
					$contact->fk_pays=$soc->fk_pays;
					$contact->socid=$soc->id;					// fk_soc
					$contact->status=1;
					$contact->email=$soc->email;
					$contact->priv=0;

					$result=$contact->create($user);
				}
			}

			if ($result >= 0)
			{
				$db->commit();

				if ( $soc->client == 1 )
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
				$db->rollback();

				$langs->load("errors");
				$mesg=$langs->trans($soc->error);
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

			// To not set code if third party is not concerned. But if it had values, we keep them.
			if (empty($soc->client) && empty($oldsoc->code_client))          $soc->code_client='';
			if (empty($soc->fournisseur)&& empty($oldsoc->code_fournisseur)) $soc->code_fournisseur='';

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
}

if ($_REQUEST["action"] == 'confirm_delete' && $_REQUEST["confirm"] == 'yes' && $user->rights->societe->supprimer)
{
	$soc = new Societe($db);
	$soc->fetch($socid);
	$result = $soc->delete($socid);

	if ($result >= 0)
	{
		Header("Location: ".DOL_URL_ROOT."/societe.php?delsoc=".$soc->nom."");
		exit;
	}
	else
	{
		$reload = 0;
		$langs->load("errors");
		$mesg=$langs->trans($soc->error);
		$_GET["action"]='';
	}
}


/*
 *	View
 */

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('','',$help_url);

$form = new Form($db);
$formcompany = new FormCompany($db);

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
		 * Fiche societe en mode creation
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
		$soc->gencod=$_POST["gencod"];
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
				dol_print_error($db);
			}
			$soc->pays_code=$obj->code;
			$soc->pays=$obj->libelle;
		}

		print_fiche_titre($langs->trans("NewCompany"));

		if ($conf->use_javascript_ajax)
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

		dol_htmloutput_errors($soc->error,$soc->errors);

		print '<form action="soc.php" method="post" name="formsoc">';

		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="cleartype" value="0">';
		print '<input type="hidden" name="private" value='.$soc->particulier.'>';
		if ($modCodeClient->code_auto || $modCodeFournisseur->code_auto) print '<input type="hidden" name="code_auto" value="1">';

		print '<table class="border" width="100%">';

		if ($soc->particulier)
		{
			print '<tr><td>'.$langs->trans('LastName').'</td><td><input type="text" size="30" name="nom" value="'.$soc->nom.'"></td>';
			print '<td>'.$langs->trans('Prefix').'</td><td><input type="text" size="5" name="prefix_comm" value="'.$soc->prefix_comm.'"></td></tr>';
			print '<tr><td>'.$langs->trans('FirstName').'</td><td><input type="text" size="30" name="prenom" value="'.$soc->firstname.'"></td>';
			print '<td colspan=2>&nbsp;</td></tr>';
			print '<tr><td>'.$langs->trans("UserTitle").'</td><td>';
			print $formcompany->select_civilite($contact->civilite_id).'</td>';
			print '<td colspan=2>&nbsp;</td></tr>';
		}
		else
		{
			print '<tr><td>'.$langs->trans('Name').'</td><td><input type="text" size="30" name="nom" value="'.$soc->nom.'"></td>';
			print '<td>'.$langs->trans('Prefix').'</td><td><input type="text" size="5" name="prefix_comm" value="'.$soc->prefix_comm.'"></td></tr>';
		}

		// Client / Prospect
		print '<tr><td width="25%">'.$langs->trans('ProspectCustomer').'</td><td width="25%"><select class="flat" name="client">';
		print '<option value="2"'.($soc->client==2?' selected="true"':'').'>'.$langs->trans('Prospect').'</option>';
		print '<option value="1"'.($soc->client==1?' selected="true"':'').'>'.$langs->trans('Customer').'</option>';
		print '<option value="0"'.($soc->client==0?' selected="true"':'').'>'.$langs->trans('NorProspectNorCustomer').'</option>';
		print '</select></td>';

		print '<td width="25%">'.$langs->trans('CustomerCode').'</td><td width="25%">';
		print '<table class="nobordernopadding"><tr><td>';
		$tmpcode=$soc->code_client;
		if ($modCodeClient->code_auto) $tmpcode=$modCodeClient->getNextValue($soc,0);
		print '<input type="text" name="code_client" size="16" value="'.$tmpcode.'" maxlength="15">';
		print '</td><td>';
		$s=$modCodeClient->getToolTip($langs,$soc,0);
		print $form->textwithpicto('',$s,1);
		print '</td></tr></table>';

		print '</td></tr>';

		// Fournisseur
		print '<tr>';
		print '<td>'.$langs->trans('Supplier').'</td><td>';
		print $form->selectyesno("fournisseur",$soc->fournisseur,1);
		print '</td>';
		print '<td>'.$langs->trans('SupplierCode').'</td><td>';
		print '<table class="nobordernopadding"><tr><td>';
		$tmpcode=$soc->code_fournisseur;
		if ($modCodeFournisseur->code_auto) $tmpcode=$modCodeFournisseur->getNextValue($soc,1);
		print '<input type="text" name="code_fournisseur" size="16" value="'.$tmpcode.'" maxlength="15">';
		print '</td><td>';
		$s=$modCodeFournisseur->getToolTip($langs,$soc,1);
		print $form->textwithpicto('',$s,1);
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

		if ($conf->global->MAIN_MODULE_BARCODE)
		{
			print '<tr><td>'.$langs->trans('Gencod').'</td><td colspan="3"><input type="text" name="gencod">';
			print $soc->gencod;
			print '</textarea></td></tr>';
		}

		print '<tr><td valign="top">'.$langs->trans('Address').'</td><td colspan="3"><textarea name="adresse" cols="40" rows="3" wrap="soft">';
		print $soc->adresse;
		print '</textarea></td></tr>';

		print '<tr><td>'.$langs->trans('Zip').'</td><td><input size="6" type="text" name="cp" value="'.$soc->cp.'">';
		if ($conf->use_javascript_ajax && $conf->global->MAIN_AUTOFILL_TOWNFROMZIP) print ' <input class="button" type="button" name="searchpostalcode" value="'.$langs->trans('FillTownFromZip').'" onclick="autofilltownfromzip_PopupPostalCode(cp.value,ville,pays_id,departement_id)">';
		print '</td>';
		print '<td>'.$langs->trans('Town').'</td><td><input type="text" name="ville" value="'.$soc->ville.'"></td></tr>';

		print '<tr><td width="25%">'.$langs->trans('Country').'</td><td colspan="3">';
		$form->select_pays($soc->pays_id,'pays_id',$conf->use_javascript_ajax?' onChange="company_save_refresh_create()"':'');
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">';
		if ($soc->pays_id)
		{
			$formcompany->select_departement($soc->departement_id,$soc->pays_code);
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


		print '<tr>';
		// IdProf1 (SIREN pour France)
		$idprof=$langs->transcountry('ProfId1',$soc->pays_code);
		if ($idprof!='-')
		{
			print '<td>'.$idprof.'</td><td>';
			$soc->show_input_id_prof(1,'idprof1',$soc->siren);
			print '</td>';
		}
		else print '<td>&nbsp;</td><td>&nbsp;</td>';
		// IdProf2 (SIRET pour France)
		$idprof=$langs->transcountry('ProfId2',$soc->pays_code);
		if ($idprof!='-')
		{
			print '<td>'.$idprof.'</td><td>';
			$soc->show_input_id_prof(2,'idprof2',$soc->siret);
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
			$soc->show_input_id_prof(3,'idprof3',$soc->ape);
			print '</td>';
		}
		else print '<td>&nbsp;</td><td>&nbsp;</td>';
		// IdProf4 (NU pour France)
		$idprof=$langs->transcountry('ProfId4',$soc->pays_code);
		if ($idprof!='-')
		{
			print '<td>'.$idprof.'</td><td>';
			$soc->show_input_id_prof(4,'idprof4',$soc->idprof4);
			print '</td>';
		}
		else print '<td>&nbsp;</td><td>&nbsp;</td>';
		print '</tr>';

		// Forme juridique
		print '<tr><td>'.$langs->trans('JuridicalStatus').'</td>';
		print '<td colspan="3">';
		if ($soc->pays_id)
		{
			$formcompany->select_forme_juridique($soc->forme_juridique_code,$soc->pays_code);
		}
		else
		{
			print $countrynotdefined;
		}
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("Type").'</td><td>'."\n";
		$form->select_array("typent_id",$formcompany->typent_array(0), $soc->typent_id);
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
		print '</td>';
		print '<td>'.$langs->trans("Staff").'</td><td>';
		$form->select_array("effectif_id",$formcompany->effectif_array(0), $soc->effectif_id);
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
		print '</td></tr>';

		// Assujeti TVA
		$html = new Form($db);
		print '<tr><td>'.$langs->trans('VATIsUsed').'</td>';
		print '<td>';
		print $html->selectyesno('assujtva_value',1,1);		// Assujeti par defaut en creation
		print '</td>';

		// Code TVA
		if ($conf->use_javascript_ajax)
		{
			print "\n";
			print '<script language="JavaScript" type="text/javascript">';
			print "function CheckVAT(a,b) {\n";
			print "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?countryCode='+a+'&vatNumber='+b,'".dol_escape_js($langs->trans("VATIntraCheckableOnEUSite"))."',500,230);\n";
			print "}\n";
			print '</script>';
			print "\n";
		}
		print '<td nowrap="nowrap">'.$langs->trans('VATIntra').'</td>';
		print '<td nowrap="nowrap">';
		$s ='<input type="text" class="flat" name="tva_intra_code" size="1" maxlength="2" value="'.$soc->tva_intra_code.'">';
		$s.='<input type="text" class="flat" name="tva_intra_num" size="12" maxlength="18" value="'.$soc->tva_intra_num.'">';
		$s.=' ';
		if ($conf->use_javascript_ajax)
		{
			$s.='<a href="#" onclick="javascript: CheckVAT(document.formsoc.tva_intra_code.value,document.formsoc.tva_intra_num.value);">'.$langs->trans("VATIntraCheck").'</a>';
			print $form->textwithpicto($s,$langs->trans("VATIntraCheckDesc",$langs->trans("VATIntraCheck")),1);
		}
		else
		{
			print $s.'<a href="'.$langs->transcountry("VATIntraCheckURL",$soc->id_pays).'" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</a>';
		}
		print '</td>';

		print '</tr>';

		if ($user->rights->societe->client->voir)
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
	print_fiche_titre($langs->trans("EditCompany"));

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
		// On verifie si la balise prefix est utilisee
		if ($modCodeClient->code_auto)
		{
			$prefixCustomerIsUsed = $modCodeClient->verif_prefixIsUsed();
		}
		$module=$conf->global->SOCIETE_CODEFOURNISSEUR_ADDON;
		if (! $module) $module=$conf->global->SOCIETE_CODECLIENT_ADDON;
		if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
		{
			$module = substr($module, 0, strlen($module)-4);
		}
		require_once(DOL_DOCUMENT_ROOT ."/includes/modules/societe/".$module.".php");
		$modCodeFournisseur = new $module;
		// On verifie si la balise prefix est utilisee
		if ($modCodeFournisseur->code_auto)
		{
			$prefixSupplierIsUsed = $modCodeFournisseur->verif_prefixIsUsed();
		}

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
			$soc->cp=$_POST["cp"];
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
			$soc->gencod=$_POST["gencod"];
			$soc->forme_juridique_code=$_POST["forme_juridique_code"];

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
					dol_print_error($db);
				}
				$soc->pays_code=$obj->code;
				$soc->pays=$langs->trans("Country".$obj->code)?$langs->trans("Country".$obj->code):$obj->libelle;
			}
		}

		dol_htmloutput_errors($soc->error,$soc->errors);

		print '<form action="soc.php?socid='.$soc->id.'" method="post" name="formsoc">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="socid" value="'.$soc->id.'">';
		if ($modCodeClient->code_auto || $modCodeFournisseur->code_auto) print '<input type="hidden" name="code_auto" value="1">';

		print '<table class="border" width="100%">';

		print '<tr><td>'.$langs->trans('Name').'</td><td colspan="3"><input type="text" size="40" name="nom" value="'.$soc->nom.'"></td></tr>';

		print '<tr><td>'.$langs->trans("Prefix").'</td><td colspan="3">';
		// On ne permet pas la modification du prefix en mode de numerotation auto utilisant le prefix
		if (($prefixCustomerIsUsed || $prefixSupplierIsUsed) && $soc->prefix_comm)
		{
			print '<input type="hidden" name="prefix_comm" value="'.$soc->prefix_comm.'">';
			print $soc->prefix_comm;
		}
		else
		{
			print '<input type="text" size="5" name="prefix_comm" value="'.$soc->prefix_comm.'">';
		}
		print '</td>';

		// Client / Prospect
		print '<tr><td width="25%">'.$langs->trans('ProspectCustomer').'</td><td width="25%"><select class="flat" name="client">';
		print '<option value="2"'.($soc->client==2?' selected="true"':'').'>'.$langs->trans('Prospect').'</option>';
		print '<option value="1"'.($soc->client==1?' selected="true"':'').'>'.$langs->trans('Customer').'</option>';
		print '<option value="0"'.($soc->client==0?' selected="true"':'').'>'.$langs->trans('NorProspectNorCustomer').'</option>';
		print '</select></td>';
		print '<td width="25%">'.$langs->trans('CustomerCode').'</td><td width="25%">';

		print '<table class="nobordernopadding"><tr><td>';
		if ((!$soc->code_client || $soc->code_client == -1) && $modCodeClient->code_auto)
		{
			$tmpcode=$soc->code_client;
			if (empty($tmpcode) && $modCodeClient->code_auto) $tmpcode=$modCodeClient->getNextValue($soc,0);
			print '<input type="text" name="code_client" size="16" value="'.$tmpcode.'" maxlength="15">';
		}
		else if ($soc->codeclient_modifiable())
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
		print $form->textwithpicto('',$s,1);
		print '</td></tr></table>';

		print '</td></tr>';

		// Fournisseur
		print '<tr>';
		print '<td>'.$langs->trans('Supplier').'</td><td>';
		print $form->selectyesno("fournisseur",$soc->fournisseur,1);
		print '</td>';
		print '<td>'.$langs->trans('SupplierCode').'</td><td>';

		print '<table class="nobordernopadding"><tr><td>';
		if ((!$soc->code_fournisseur || $soc->code_fournisseur == -1) && $modCodeFournisseur->code_auto)
		{
			$tmpcode=$soc->code_fournisseur;
			if (empty($tmpcode) && $modCodeFournisseur->code_auto) $tmpcode=$modCodeFournisseur->getNextValue($soc,1);
			print '<input type="text" name="code_fournisseur" size="16" value="'.$tmpcode.'" maxlength="15">';
		}
		else if ($soc->codefournisseur_modifiable())
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
		print $form->textwithpicto('',$s,1);
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

		if ($conf->global->MAIN_MODULE_BARCODE)
		{
			print '<tr><td valign="top">'.$langs->trans('Gencod').'</td><td colspan="3"><input type="text" name="gencod" value="'.$soc->gencod.'">';
			print '</td></tr>';
		}

		print '<tr><td valign="top">'.$langs->trans('Address').'</td><td colspan="3"><textarea name="adresse" cols="40" rows="3" wrap="soft">';
		print $soc->adresse;
		print '</textarea></td></tr>';

		print '<tr><td>'.$langs->trans('Zip').'</td><td><input size="6" type="text" name="cp" value="'.$soc->cp.'">';
		if ($conf->use_javascript_ajax && $conf->global->MAIN_AUTOFILL_TOWNFROMZIP) print ' <input class="button" type="button" name="searchpostalcode" value="'.$langs->trans('FillTownFromZip').'" onclick="autofilltownfromzip_PopupPostalCode(cp.value,ville,pays_id,departement_id)">';
		print '</td>';

		print '<td>'.$langs->trans('Town').'</td><td><input type="text" name="ville" value="'.$soc->ville.'"></td></tr>';

		print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
		$form->select_pays($soc->pays_id,'pays_id',$conf->use_javascript_ajax?' onChange="company_save_refresh_edit()"':'');
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">';
		$formcompany->select_departement($soc->departement_id,$soc->pays_code);
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
			$soc->show_input_id_prof(1,'idprof1',$soc->siren);
			print '</td>';
		}
		else print '<td>&nbsp;</td><td>&nbsp;</td>';
		// IdProf2 (SIRET pour France)
		$idprof=$langs->transcountry('ProfId2',$soc->pays_code);
		if ($idprof!='-')
		{
			print '<td>'.$idprof.'</td><td>';
			$soc->show_input_id_prof(2,'idprof2',$soc->siret);
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
			$soc->show_input_id_prof(3,'idprof3',$soc->ape);
			print '</td>';
		}
		else print '<td>&nbsp;</td><td>&nbsp;</td>';
		// IdProf4 (NU pour France)
		$idprof=$langs->transcountry('ProfId4',$soc->pays_code);
		if ($idprof!='-')
		{
			print '<td>'.$idprof.'</td><td>';
			$soc->show_input_id_prof(4,'idprof4',$soc->idprof4);
			print '</td>';
		}
		else print '<td>&nbsp;</td><td>&nbsp;</td>';
		print '</tr>';

		// Assujeti TVA
		print '<tr><td>'.$langs->trans('VATIsUsed').'</td><td>';
		print $form->selectyesno('assujtva_value',$soc->tva_assuj,1);
		print '</td>';

		// Code TVA
		if ($conf->use_javascript_ajax)
		{
			print "\n";
			print '<script language="JavaScript" type="text/javascript">';
			print "function CheckVAT(a,b) {\n";
			print "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?countryCode='+a+'&vatNumber='+b,'".dol_escape_js($langs->trans("VATIntraCheckableOnEUSite"))."',500,260);\n";
			print "}\n";
			print '</script>';
			print "\n";
		}
		print '<td nowrap="nowrap">'.$langs->trans('VATIntra').'</td>';
		print '<td nowrap="nowrap">';
		$s ='<input type="text" class="flat" name="tva_intra_code" size="1" maxlength="2" value="'.$soc->tva_intra_code.'">';
		$s.='<input type="text" class="flat" name="tva_intra_num" size="12" maxlength="18" value="'.$soc->tva_intra_num.'">';
		$s.=' ';
		if ($conf->use_javascript_ajax)
		{
			$s.='<a href="#" onclick="javascript: CheckVAT(document.formsoc.tva_intra_code.value,document.formsoc.tva_intra_num.value);">'.$langs->trans("VATIntraCheck").'</a>';
			print $form->textwithpicto($s,$langs->trans("VATIntraCheckDesc",$langs->trans("VATIntraCheck")),1);
		}
		else
		{
			print $s.'<a href="'.$langs->transcountry("VATIntraCheckURL",$soc->id_pays).'" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</a>';
		}
		print '</td>';
		print '</tr>';

		print '<tr><td>'.$langs->trans("Capital").'</td><td colspan="3"><input type="text" name="capital" size="10" value="'.$soc->capital.'"> '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

		print '<tr><td>'.$langs->trans('JuridicalStatus').'</td><td colspan="3">';
		$formcompany->select_forme_juridique($soc->forme_juridique_code,$soc->pays_code);
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("Type").'</td><td>';
		$form->select_array("typent_id",$formcompany->typent_array(0), $soc->typent_id);
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
		print '</td>';
		print '<td>'.$langs->trans("Staff").'</td><td>';
		$form->select_array("effectif_id",$formcompany->effectif_array(0), $soc->effectif_id);
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
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
	 * Fiche societe en mode visu
	 */
	$soc = new Societe($db);
	$soc->id = $socid;
	$result=$soc->fetch($socid);
	if ($result < 0)
	{
		dol_print_error($db,$soc->error);
		exit;
	}

	$head = societe_prepare_head($soc);

	dol_fiche_head($head, 'company', $langs->trans("ThirdParty"),0,'company');


	// Confirm delete third party
	if ($_GET["action"] == 'delete')
	{
		$html = new Form($db);
		$ret=$html->form_confirm("soc.php?socid=".$soc->id,$langs->trans("DeleteACompany"),$langs->trans("ConfirmDeleteCompany"),"confirm_delete",'',0,2);
		if ($ret == 'html') print '<br>';
	}

	if ($mesg)
	{
		print '<div class="error">';
		print $mesg;
		print '</div>';
	}

	print '<form name="formsoc" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="border" width="100%">';

	// Name
	print '<tr><td width="20%">'.$langs->trans('Name').'</td>';
	print '<td colspan="3">';
	print $form->showrefnav($soc,'socid','',1,'rowid','nom');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$soc->prefix_comm.'</td></tr>';

	if ($soc->client)
	{
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

	if ($conf->global->MAIN_MODULE_BARCODE)
	{
		print '<tr><td>'.$langs->trans('Gencod').'</td><td colspan="3">'.$soc->gencod.'</td></tr>';
	}

	print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->adresse)."</td></tr>";

	print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$soc->cp."</td>";
	print '<td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$soc->ville."</td></tr>";

	// Country
	print '<tr><td>'.$langs->trans("Country").'</td><td colspan="3">';
	if ($soc->isInEEC()) print $form->textwithpicto($soc->pays,$langs->trans("CountryIsInEEC"),1,0);
	else print $soc->pays;
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">'.$soc->departement.'</td>';

	print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($soc->tel,$soc->pays_code,0,$soc->id,'AC_TEL').'</td>';
	print '<td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($soc->fax,$soc->pays_code,0,$soc->id,'AC_FAX').'</td></tr>';

	// EMail
	print '<tr><td>'.$langs->trans('EMail').'</td><td>';
	print dol_print_email($soc->email,0,$soc->id,'AC_EMAIL');
	print '</td>';

	// Web
	print '<td>'.$langs->trans('Web').'</td><td>';
	print dol_print_url($soc->url);
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
	if ($conf->use_javascript_ajax)
	{
		print "\n";
		print '<script language="JavaScript" type="text/javascript">';
		print "function CheckVAT(a,b) {\n";
		print "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?countryCode='+a+'&vatNumber='+b,'".dol_escape_js($langs->trans("VATIntraCheckableOnEUSite"))."',500,260);\n";
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
		if ($conf->use_javascript_ajax)
		{
			$s.='<a href="#" onclick="javascript: CheckVAT(document.formsoc.tva_intra_code.value,document.formsoc.tva_intra_num.value);">'.$langs->trans("VATIntraCheck").'</a>';
			print $form->textwithpicto($s,$langs->trans("VATIntraCheckDesc",$langs->trans("VATIntraCheck")),1);
		}
		else
		{
			print $s.'<a href="'.$langs->transcountry("VATIntraCheckURL",$soc->id_pays).'" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</a>';
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
	$arr = $formcompany->typent_array(1);
	$soc->typent= $arr[$soc->typent_code];
	print '<tr><td>'.$langs->trans("Type").'</td><td>'.$soc->typent.'</td><td>'.$langs->trans("Staff").'</td><td>'.$soc->effectif.'</td></tr>';

	// Ban
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

	// Parent company
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
		print $socm->getNomUrl(1).' '.($socm->code_client?"(".$socm->code_client.")":"");
		print $socm->ville?' - '.$socm->ville:'';
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
		dol_print_error($db);
	}
	print '</td></tr>';

	// Module Adherent
	if ($conf->adherent->enabled)
	{
		$langs->load("members");
		print '<tr><td width="25%" valign="top">'.$langs->trans("LinkedToDolibarrMember").'</td>';
		print '<td colspan="3">';
		$adh=new Adherent($db);
		$result=$adh->fetch('','',$soc->id);
		if ($result > 0)
		{
			$adh->ref=$adh->fullname;
			print $adh->getNomUrl(1);
		}
		else
		{
			print $langs->trans("UserNotLinkedToMember");
		}
		print '</td>';
		print "</tr>\n";
	}

	print '</table>';
	print '</form>';
	print "</div>\n";

	/*
	 *	Actions
	 */
	if ($_GET["action"] == '')
	{
		print '<div class="tabsAction">';

		if ($user->rights->societe->creer)
		{
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?socid='.$soc->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
		}

		/* Not specific to third party. Must go on Project menu to create a project.
		if ($conf->projet->enabled && $user->rights->projet->creer)
		{
			$langs->load("projects");
			print '<a class="butAction" href="'.DOL_URL_ROOT.'/projet/fiche.php?socid='.$soc->id.'&action=create">'.$langs->trans("AddProject").'</a>';
		}
		*/

		if ($user->rights->societe->contact->creer)
		{
			print '<a class="butAction" href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$soc->id.'&amp;action=create">'.$langs->trans("AddContact").'</a>';
		}

		if ($user->rights->societe->supprimer)
		{
			print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?socid='.$soc->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
		}

		print '</div>';
		print '<br>';
	}

	/*
	 * Liste des contacts
	 */
	show_contacts($conf,$langs,$db,$soc);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
