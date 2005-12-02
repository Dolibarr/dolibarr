<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Brian Fraval         <brian@fraval.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne <eric.seigne@ryxeo.com>
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
        \file       htdocs/soc.php
        \ingroup    societe
        \brief      Onglet societe d'une societe
        \version    $Revision$
*/

require("pre.inc.php");
$user->getrights('societe');
$langs->load("companies");
$langs->load("commercial");
 
if (! $user->rights->societe->creer)
{
    if ($_GET["action"] == 'create' || $_POST["action"] == 'create')
    {
        accessforbidden();
    }
}

// Sécurité accés client
if ($user->societe_id > 0) 
{
  $_GET["action"] = '';
  $_POST["action"] = '';
  $_GET["socid"] = $user->societe_id;
}

$soc = new Societe($db);



/*
 * Actions
 */
if ($_POST["action"] == 'add' || $_POST["action"] == 'update')
{
    $soc->nom                   = $_POST["nom"];
    $soc->adresse               = $_POST["adresse"];
    $soc->cp                    = $_POST["cp"];
    $soc->ville                 = $_POST["ville"];
    $soc->pays_id               = $_POST["pays_id"];
    $soc->departement_id        = $_POST["departement_id"];
    $soc->tel                   = $_POST["tel"];
    $soc->fax                   = $_POST["fax"];
    $soc->url                   = ereg_replace( "http://", "", $_POST["url"] );
    $soc->siren                 = $_POST["siren"];
    $soc->siret                 = $_POST["siret"];
    $soc->ape                   = $_POST["ape"];
    $soc->prefix_comm           = $_POST["prefix_comm"];
    $soc->code_client           = $_POST["code_client"];
    $soc->code_fournisseur      = $_POST["code_fournisseur"];
    $soc->codeclient_modifiable = $_POST["codeclient_modifiable"];
    $soc->codefournisseur_modifiable = $_POST["codefournisseur_modifiable"];
    $soc->capital               = $_POST["capital"];
    $soc->tva_intra_code        = $_POST["tva_intra_code"];
    $soc->tva_intra_num         = $_POST["tva_intra_num"];
    $soc->tva_intra             = $_POST["tva_intra_code"] . $_POST["tva_intra_num"];
    $soc->forme_juridique_code  = $_POST["forme_juridique_code"];
    $soc->effectif_id           = $_POST["effectif_id"];
    $soc->typent_id             = $_POST["typent_id"];
    $soc->client                = $_POST["client"];
    $soc->fournisseur           = $_POST["fournisseur"];
    
    if ($_POST["action"] == 'update')
    {
        $result = $soc->update($_GET["socid"],$user);
        if ($result <= 0)
        {
            $soc->id = $_GET["socid"];
            // doublon sur le prefix comm
            $reload = 0;
            $mesg = $soc->error;      //"Erreur, le prefix '".$soc->prefix_comm."' existe déjà vous devez en choisir un autre";
            $_GET["action"]= "edit";
        }
        else
        {
            Header("Location: soc.php?socid=".$_GET["socid"]);
            exit;
        }
    
    }
    
    if ($_POST["action"] == 'add')
    {
        $result = $soc->create($user);
    
        if ($result >= 0)
        {
            Header("Location: soc.php?socid=".$soc->id);
            exit;
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


if ($_GET["action"] == 'create' || $_POST["action"] == 'create')
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
        if ($_POST["nom"])
        {
            $soc->nom=$_POST["nom"];
            $soc->prefix_comm=$_POST["prefix_comm"];
            $soc->client=$_POST["client"];
            $soc->code_client=$_POST["code_client"];
            $soc->fournisseur=$_POST["fournisseur"];
            $soc->code_fournisseur=$_POST["code_fournisseur"];
            $soc->adresse=$_POST["adresse"];
            $soc->zip=$_POST["zip"];
            $soc->ville=$_POST["ville"];
            $soc->departement_id=$_POST["departement_id"];
            $soc->tel=$_POST["tel"];
            $soc->fax=$_POST["fax"];
            $soc->url=$_POST["url"];
            $soc->capital=$_POST["capital"];
            $soc->siren=$_POST["siren"];
            $soc->siret=$_POST["siret"];
            $soc->ape=$_POST["ape"];
            $soc->typent_id=$_POST["typent_id"];
            $soc->effectif_id=$_POST["effectif_id"];
            $soc->tva_intra_code=$_POST["tva_intra_code"];
            $soc->tva_intra_num=$_POST["tva_intra_num"];
        }

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
        print "<br>\n";
    
        if ($soc->error)
        {
            print '<div class="error">';
            print nl2br($soc->error);
            print '</div>';
        }
    
        print '<form action="soc.php" method="post" name="formsoc">';
    
        print '<input type="hidden" name="action" value="add">';
        print '<input type="hidden" name="codeclient_modifiable" value="1">';
        print '<input type="hidden" name="codefournisseur_modifiable" value="1">';
    
        print '<table class="border" width="100%">';
    
        print '<tr><td>'.$langs->trans('Name').'</td><td><input type="text" size="30" name="nom" value="'.$soc->nom.'"></td>';
        print '<td>'.$langs->trans('Prefix').'</td><td><input type="text" size="5" name="prefix_comm" value="'.$soc->prefix_comm.'"></td></tr>';
    
        // Client / Prospect
        print '<tr><td width="25%">'.$langs->trans('ProspectCustomer').'</td><td width="25%"><select class="flat" name="client">';
        print '<option value="2"'.($soc->client==2?' selected="true"':'').'>'.$langs->trans('Prospect').'</option>';
        print '<option value="1"'.($soc->client==1?' selected="true"':'').'>'.$langs->trans('Customer').'</option>';
        print '<option value="0"'.($soc->client==0?' selected="true"':'').'>Ni client, ni prospect</option>';
        print '</select></td>';
        print '<td width="25%">'.$langs->trans('CustomerCode').'</td><td width="25%">';
        print '<input type="text" name="code_client" size="16" value="'.$soc->code_client.'" maxlength="15">';
        print '</td></tr>';

        // Fournisseur
        print '<tr>';
        print '<td>'.$langs->trans('Supplier').'</td><td>';
        $form->selectyesnonum("fournisseur",$soc->fournisseur);
        print '</td>';
        print '<td>'.$langs->trans('SupplierCode').'</td><td>';
        print '<input type="text" name="code_fournisseur" size="16" value="'.$soc->code_fournisseur.'" maxlength="15">';
        print '</td></tr>';

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

        print '<tr><td>'.$langs->trans('Web').'</td><td colspan="3"><input type="text" name="url" size="40" value="'.$soc->url.'"></td></tr>';

        print '<tr><td>'.$langs->trans('Capital').'</td><td colspan="3"><input type="text" name="capital" size="10" value="'.$soc->capital.'"> '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

        print '<tr><td>'.($langs->transcountry("ProfId1",$soc->pays_code) != '-'?$langs->transcountry('ProfId1',$soc->pays_code):'').'</td><td>';
        if ($soc->pays_id)
        {
            if ($langs->transcountry("ProfId1",$soc->pays_code) != '-') print '<input type="text" name="siren" size="15" maxlength="9" value="'.$soc->siren.'">';
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
            if ($langs->transcountry("ProfId2",$soc->pays_code) != '-') print '<input type="text" name="siret" size="15" maxlength="14" value="'.$soc->siret.'">';
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
            if ($langs->transcountry("ProfId3",$soc->pays_code) != '-') print '<input type="text" name="ape" size="5" maxlength="4" value="'.$soc->ape.'">';
            else print '&nbsp;';
        }
        else
        {
            print $countrynotdefined;
        }
        print '</td><td colspan="2">&nbsp;</td></tr>';

        print '<tr><td>'.$langs->trans('JuridicalStatus').'</td><td colspan="3">';
        if ($soc->pays_id)
        {
            $form->select_forme_juridique($soc->forme_juridique_code,$soc->pays_code);
        }
        else
        {
            print $countrynotdefined;
        }
        print '</td></tr>';

        print '<tr><td>'.$langs->trans("Type").'</td><td>';
        $form->select_array("typent_id",$soc->typent_array(), $soc->typent_id);
        print '</td>';
        print '<td>'.$langs->trans("Staff").'</td><td>';
        $form->select_array("effectif_id",$soc->effectif_array(), $soc->effectif_id);
        print '</td></tr>';

        print '<tr><td nowrap>'.$langs->trans('VATIntraShort').'</td><td colspan="3">';

        print '<input type="text" name="tva_intra_code" size="3" maxlength="2" value="'.$soc->tva_intra_code.'">';
        print '<input type="text" name="tva_intra_num" size="18" maxlength="18" value="'.$soc->tva_intra_num.'">';
        print '  '.$langs->trans("VATIntraCheckableOnEUSite");
        print '</td></tr>';

        print '<tr><td colspan="4" align="center">';
        print '<input type="submit" class="button" value="'.$langs->trans('AddCompany').'"></td></tr>'."\n";
    
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

    if ($_GET["socid"])
    {
        if ($reload || ! $_POST["nom"])
        {
            $soc = new Societe($db);
            $soc->id = $_GET["socid"];
            $soc->fetch($_GET["socid"]);
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
            $soc->url=$_POST["url"];
            $soc->capital=$_POST["capital"];
            $soc->siren=$_POST["siren"];
            $soc->siret=$_POST["siret"];
            $soc->ape=$_POST["ape"];
            $soc->typent_id=$_POST["typent_id"];
            $soc->effectif_id=$_POST["effectif_id"];
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
        print '<input type="hidden" name="codeclient_modifiable" value="'.$soc->codeclient_modifiable.'">';
        print '<input type="hidden" name="codefournisseur_modifiable" value="'.$soc->codefournisseur_modifiable.'">';

        print '<table class="border" width="100%">';

        print '<tr><td>'.$langs->trans('Name').'</td><td colspan="3"><input type="text" size="40" name="nom" value="'.$soc->nom.'"></td></tr>';

        print '<td>'.$langs->trans("Prefix").'</td><td colspan="3">';
        print '<input type="text" size="5" name="prefix_comm" value="'.$soc->prefix_comm.'">';
        print '</td>';

        // Client / Prospect
        print '<tr><td width="25%">'.$langs->trans('ProspectCustomer').'</td><td width="25%"><select class="flat" name="client">';
        print '<option value="2"'.($soc->client==2?' selected="true"':'').'>'.$langs->trans('Prospect').'</option>';
        print '<option value="1"'.($soc->client==1?' selected="true"':'').'>'.$langs->trans('Customer').'</option>';
        print '<option value="0"'.($soc->client==0?' selected="true"':'').'>Ni client, ni prospect</option>';
        print '</select></td>';
        print '<td width="25%">'.$langs->trans('CustomerCode').'</td><td width="25%">';
        if ($soc->codeclient_modifiable == 1)
        {
            print '<input type="text" name="code_client" size="16" value="'.$soc->code_client.'" maxlength="15">';
        }
        else
        {
            print $soc->code_client;
        }
        print '</td></tr>';

        // Fournisseur
        print '<tr>';
        print '<td>'.$langs->trans('Supplier').'</td><td>';
        $form->selectyesnonum("fournisseur",$soc->fournisseur);
        print '</td>';
        print '<td>'.$langs->trans('SupplierCode').'</td><td>';
        if ($soc->codefournisseur_modifiable == 1)
        {
            print '<input type="text" name="code_fournisseur" size="16" value="'.$soc->code_fournisseur.'" maxlength="15">';
        }
        else
        {
            print $soc->code_fournisseur;
        }
        print '</td></tr>';

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

        print '<tr><td>'.$langs->trans('Web').'</td><td colspan="3"><input type="text" name="url" size="40" value="'.$soc->url.'"></td></tr>';

        print '<tr>';
        // IdProf1
        $idprof=$langs->transcountry('ProfId1',$soc->pays_code);
        if ($idprof!='-')
        {
            print '<td>'.$idprof.'</td><td>';
            $form->id_prof(1,$soc,'siren',$soc->siren);
            print '</td>';
        }
        else print '<td>&nbsp;</td><td>&nbsp;</td>';
        // IdProf2
        $idprof=$langs->transcountry('ProfId2',$soc->pays_code);
        if ($idprof!='-')
        {
            print '<td>'.$idprof.'</td><td>';
            $form->id_prof(2,$soc,'siret',$soc->siret);
            print '</td>';
        }
        else print '<td>&nbsp;</td><td>&nbsp;</td>';
        print '</tr>';
        print '<tr>';
        // IdProf3
        $idprof=$langs->transcountry('ProfId3',$soc->pays_code);
        if ($idprof!='-')
        {
            print '<td>'.$idprof.'</td><td>';
            $form->id_prof(3,$soc,'ape',$soc->ape);
            print '</td>';
        }
        else print '<td>&nbsp;</td><td>&nbsp;</td>';
        // IdProf4
        //        $idprof=$langs->transcountry('ProfId4',$soc->pays_code);
        $idprof='-';    // L'identifiant 4 n'est pas encore géré
        if ($idprof!='-')
        {
            print '<td>'.$idprof.'</td><td>';
            $form->id_prof(4,$soc,'rcs',$soc->rcs);
            print '</td>';
        }
        else print '<td>&nbsp;</td><td>&nbsp;</td>';
        print '</tr>';

        print '<tr><td nowrap>'.$langs->trans('VATIntraShort').'</td><td colspan="3">';
        print '<input type="text" name="tva_intra_code" size="3" maxlength="2" value="'.$soc->tva_intra_code.'">';
        print '<input type="text" name="tva_intra_num" size="18" maxlength="18" value="'.$soc->tva_intra_num.'">';
        print '  '.$langs->trans("VATIntraCheckableOnEUSite");
        print '</td></tr>';

        print '<tr><td>'.$langs->trans("Capital").'</td><td colspan="3"><input type="text" name="capital" size="10" value="'.$soc->capital.'"> '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

        print '<tr><td>'.$langs->trans('JuridicalStatus').'</td><td colspan="3">';
        $form->select_forme_juridique($soc->forme_juridique_code,$soc->pays_code);
        print '</td></tr>';

        print '<tr><td>'.$langs->trans("Type").'</td><td>';
        $form->select_array("typent_id",$soc->typent_array(), $soc->typent_id);
        print '</td>';
        print '<td>'.$langs->trans("Staff").'</td><td>';
        $form->select_array("effectif_id",$soc->effectif_array(), $soc->effectif_id);
        print '</td></tr>';

        print '<tr><td align="center" colspan="4"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';

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
    $soc->id = $_GET["socid"];
    $result=$soc->fetch($_GET["socid"]);
    if ($result < 0)
    {
        dolibarr_print_error($db,$soc->error);
        exit;
    }

    $h=0;
    
    $head[$h][0] = DOL_URL_ROOT.'/soc.php?socid='.$soc->id;
    $head[$h][1] = $langs->trans("Company");
    $hselected=$h;
    $h++;

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
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/societe/info.php?socid='.$soc->id;
    $head[$h][1] = $langs->trans("Info");
    $h++;

    dolibarr_fiche_head($head, $hselected, $soc->nom);


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

    print '<tr><td width="20%">'.$langs->trans('Name').'</td><td colspan="3">'.$soc->nom.'</td></tr>';

    print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$soc->prefix_comm.'</td></tr>';

    if ($soc->client) {
        print '<tr><td>';
        print $langs->trans('CustomerCode').'</td><td colspan="3">';
        print $soc->code_client;
        if ($soc->check_codeclient() <> 0) print ' '.$langs->trans("WrongCustomerCode");
        print '</td></tr>';
    }

    if ($soc->fournisseur) {
        print '<tr><td>';
        print $langs->trans('SupplierCode').'</td><td colspan="3">';
        print $soc->code_fournisseur;
        if ($soc->check_codefournisseur() <> 0) print ' '.$langs->trans("WrongSupplierCode");
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

    print '<tr><td>'.$langs->trans('Web').'</td><td colspan="3">';
    if ($soc->url) { print '<a href="http://'.$soc->url.'" target="_blank">http://'.$soc->url.'</a>'; }
    print '</td></tr>';

    // ProfId1
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
    // ProfId2
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

    // ProfId3
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
    // ProfId4
    //    $profid=$langs->transcountry('ProfId4',$soc->pays_code);
    $profid='-';    // L'identifiant 4 n'est pas encore géré
    if ($profid!='-')
    {
        print '<td>'.$profid.'</td><td>';
        print $soc->rcs;
        if ($soc->rcs)
        {
            if ($soc->id_prof_check(4,$soc) > 0) print ' &nbsp; '.$soc->id_prof_url(4,$soc);
            else print ' <font class="error">('.$langs->trans("ErrorWrongValue").')</font>';
        }
        print '</td></tr>';
    }
    else print '<td>&nbsp;</td><td>&nbsp;</td></tr>';

    // TVA
    print '<tr><td nowrap>'.$langs->trans('VATIntraShort').'</td><td colspan="3">';
    print $soc->tva_intra;
    print '</td></tr>';

    print '<tr><td>'.$langs->trans('Capital').'</td><td colspan="3">'.$soc->capital.' '.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

    // Statut juridique
    print '<tr><td>'.$langs->trans('JuridicalStatus').'</td><td colspan="3">'.$soc->forme_juridique.'</td></tr>';

    // Type + Staff
    $arr = $soc->typent_array($soc->typent_id);
    $soc->typent= $arr[$soc->typent_id];
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
        print '<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$socm->idp.'">'.img_object($langs->trans("ShowCompany"),'company').' '.$socm->nom.'</a>'.($socm->code_client?"(".$socm->code_client.")":"").' - '.$socm->ville;
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
            print '<a class="butAction" href="'.DOL_URL_ROOT.'/projet/fiche.php?socidp='.$soc->id.'&action=create">'.$langs->trans("AddProject").'</a>';
        }

        if ($user->rights->societe->creer)
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

