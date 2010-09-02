<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Brian Fraval         <brian@fraval.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2008	   Patrick Raguin       <patrick.raguin@auguria.net>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 *  \file       htdocs/societe/soc.php
 *  \ingroup    societe
 *  \brief      Third party card page
 *  \version    $Id$
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/canvas.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formadmin.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
if ($conf->adherent->enabled) require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");

$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("banks");
$langs->load("users");
if ($conf->notification->enabled) $langs->load("mails");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);

// For canvas usage
if (empty($_GET["canvas"]))
{
	$_GET["canvas"] = 'default';
	if ($_REQUEST["private"]==1) $_GET["canvas"] = 'individual';
}

// Initialization Company Object
$socstatic = new Societe($db);

// Initialization Company Canvas
if (!empty($socid)) $socstatic->getCanvas($socid);
$canvas = (!empty($socstatic->canvas)?$socstatic->canvas:$_GET["canvas"]);
$soc = new Canvas($db);
$soc->load_canvas('thirdparty@societe',$canvas);

/*
 * Actions
 */

if ($_POST["getcustomercode"])
{
    // We defined value code_client
    $_POST["code_client"]="Acompleter";
}

if ($_POST["getsuppliercode"])
{
    // We defined value code_fournisseur
    $_POST["code_fournisseur"]="Acompleter";
}

// Add new third party
if ((! $_POST["getcustomercode"] && ! $_POST["getsuppliercode"])
&& ($_POST["action"] == 'add' || $_POST["action"] == 'update') && $user->rights->societe->creer)
{
    require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
    $error=0;

    if ($_POST["action"] == 'update')
    {
        // Load properties of company
        $soc->fetch($socid);
    }

    if ($_REQUEST["private"] == 1)
    {
        $soc->object->particulier           = $_REQUEST["private"];

        $soc->object->nom                   = empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION)?trim($_POST["prenom"].' '.$_POST["nom"]):trim($_POST["nom"].' '.$_POST["prenom"]);
        $soc->object->nom_particulier       = $_POST["nom"];
        $soc->object->prenom                = $_POST["prenom"];
        $soc->object->civilite_id           = $_POST["civilite_id"];
    }
    else
    {
        $soc->object->nom                   = $_POST["nom"];
    }
    $soc->object->address               = $_POST["adresse"];
    $soc->object->adresse               = $_POST["adresse"]; // TODO obsolete
    $soc->object->cp                    = $_POST["cp"];
    $soc->object->ville                 = $_POST["ville"];
    $soc->object->pays_id               = $_POST["pays_id"];
    $soc->object->departement_id        = $_POST["departement_id"];
    $soc->object->tel                   = $_POST["tel"];
    $soc->object->fax                   = $_POST["fax"];
    $soc->object->email                 = trim($_POST["email"]);
    $soc->object->url                   = $_POST["url"];
    $soc->object->siren                 = $_POST["idprof1"];
    $soc->object->siret                 = $_POST["idprof2"];
    $soc->object->ape                   = $_POST["idprof3"];
    $soc->object->idprof4               = $_POST["idprof4"];
    $soc->object->prefix_comm           = $_POST["prefix_comm"];
    $soc->object->code_client           = $_POST["code_client"];
    $soc->object->code_fournisseur      = $_POST["code_fournisseur"];
    $soc->object->capital               = $_POST["capital"];
    $soc->object->gencod                = $_POST["gencod"];
    $soc->object->canvas				= $_GET["canvas"];

    $soc->object->tva_assuj             = $_POST["assujtva_value"];

    // Local Taxes
    $soc->object->localtax1_assuj		= $_POST["localtax1assuj_value"];
    $soc->object->localtax2_assuj		= $_POST["localtax2assuj_value"];

    $soc->object->tva_intra             = $_POST["tva_intra"];

    $soc->object->forme_juridique_code  = $_POST["forme_juridique_code"];
    $soc->object->effectif_id           = $_POST["effectif_id"];
    if ($_REQUEST["private"] == 1)
    {
        $soc->object->typent_id             = 8; // TODO predict another method if the field "special" change of rowid
    }
    else
    {
        $soc->object->typent_id             = $_POST["typent_id"];
    }
    $soc->object->client                = $_POST["client"];
    $soc->object->fournisseur           = $_POST["fournisseur"];
    $soc->object->fournisseur_categorie = $_POST["fournisseur_categorie"];

    $soc->object->commercial_id         = $_POST["commercial_id"];
    $soc->object->default_lang          = $_POST["default_lang"];

    // Check parameters
    if (empty($_POST["cancel"]))
    {
        if (! empty($soc->object->email) && ! isValidEMail($soc->object->email))
        {
            $error = 1;
            $langs->load("errors");
            $soc->object->error = $langs->trans("ErrorBadEMail",$soc->object->email);
            $_GET["action"] = $_POST["action"]=='add'?'create':'edit';
        }
        if (! empty($soc->object->url) && ! isValidUrl($soc->object->url))
        {
            $error = 1;
            $langs->load("errors");
            $soc->object->error = $langs->trans("ErrorBadUrl",$soc->object->url);
            $_GET["action"] = $_POST["action"]=='add'?'create':'edit';
        }
        if ($soc->object->fournisseur && ! $conf->fournisseur->enabled)
        {
            $error = 1;
            $langs->load("errors");
            $soc->object->error = $langs->trans("ErrorSupplierModuleNotEnabled");
            $_GET["action"] = $_POST["action"]=='add'?'create':'edit';
        }
    }

    if (! $error)
    {
        if ($_POST["action"] == 'add')
        {
            $db->begin();

            if (empty($soc->object->client))      $soc->object->code_client='';
            if (empty($soc->object->fournisseur)) $soc->object->code_fournisseur='';

            $result = $soc->object->create($user);
            if ($result >= 0)
            {
                if ($soc->object->particulier)
                {
                    dol_syslog("This thirdparty is a personal people",LOG_DEBUG);
                    $contact=new Contact($db);

                    $contact->civilite_id = $soc->object->civilite_id;
                    $contact->name=$soc->object->nom_particulier;
                    $contact->firstname=$soc->object->prenom;
                    $contact->address=$soc->object->address;
                    $contact->cp=$soc->object->cp;
                    $contact->ville=$soc->object->ville;
                    $contact->fk_pays=$soc->object->fk_pays;
                    $contact->socid=$soc->object->id;					// fk_soc
                    $contact->status=1;
                    $contact->email=$soc->object->email;
                    $contact->priv=0;

                    $result=$contact->create($user);
                }
            }
            else
            {
                $mesg=$soc->object->error;
            }

            if ($result >= 0)
            {
                $db->commit();

                if ( $soc->object->client == 1 )
                {
                    Header("Location: ".DOL_URL_ROOT."/comm/fiche.php?socid=".$soc->object->id);
                    return;
                }
                else
                {
                    if (  $soc->object->fournisseur == 1 )
                    {
                        Header("Location: ".DOL_URL_ROOT."/fourn/fiche.php?socid=".$soc->object->id);
                        return;
                    }
                    else
                    {
                        Header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$soc->object->id);
                        return;
                    }
                }
                exit;
            }
            else
            {
                $db->rollback();

                $langs->load("errors");
                $mesg=$langs->trans($soc->object->error);
                $_GET["action"]='create';
            }
        }

        if ($_POST["action"] == 'update')
        {
            if ($_POST["cancel"])
            {
                Header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$socid);
                exit;
            }

            $oldsoc = new Canvas($db);
            $oldsoc->load_canvas('thirdparty@societe',$canvas);
            $result=$oldsoc->fetch($socid);

            // To not set code if third party is not concerned. But if it had values, we keep them.
            if (empty($soc->object->client) && empty($oldsoc->code_client))          $soc->object->code_client='';
            if (empty($soc->object->fournisseur)&& empty($oldsoc->code_fournisseur)) $soc->object->code_fournisseur='';
            //var_dump($soc);exit;

            $result = $soc->object->update($socid,$user,1,$oldsoc->object->codeclient_modifiable(),$oldsoc->object->codefournisseur_modifiable());
            if ($result >= 0)
            {
                Header("Location: ".$_SERVER["PHP_SELF"]."?socid=".$socid);
                exit;
            }
            else
            {
                $soc->object->id = $socid;
                $reload = 0;

                $mesg = $soc->object->error;
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
        Header("Location: ".DOL_URL_ROOT."/societe/societe.php?delsoc=".$soc->nom."");
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
 * Generate document
 */
if ($_REQUEST['action'] == 'builddoc')	// En get ou en post
{
    if (is_numeric($_REQUEST['model']))
    {
        $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Model"));
    }
    else
    {
        require_once(DOL_DOCUMENT_ROOT.'/includes/modules/societe/modules_societe.class.php');

        $soc = new Societe($db);
        $soc->fetch($socid);
        $soc->fetch_thirdparty();

        /*if ($_REQUEST['model'])
         {
         $fac->setDocModel($user, $_REQUEST['model']);
         }
         */

        // Define output language
        $outputlangs = $langs;
        $newlang='';
        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
        if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$fac->client->default_lang;
        if (! empty($newlang))
        {
            $outputlangs = new Translate("",$conf);
            $outputlangs->setDefaultLang($newlang);
        }
        $result=thirdparty_doc_create($db, $soc->id, '', $_REQUEST['model'], $outputlangs);
        if ($result <= 0)
        {
            dol_print_error($db,$result);
            exit;
        }
        else
        {
            Header ('Location: '.$_SERVER["PHP_SELF"].'?socid='.$soc->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc'));
            exit;
        }
    }
}


/*
 *	View
 */

$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('','',$help_url);

$form = new Form($db);
$formfile = new FormFile($db);
$formadmin = new FormAdmin($db);
$formcompany = new FormCompany($db);

if ($_POST["getcustomercode"] || $_POST["getsuppliercode"] ||
$_GET["action"] == 'create' || $_POST["action"] == 'create')
{
    /*
     *	Sheet mode creation
     */
    if ($user->rights->societe->creer)
    {
        print_fiche_titre($langs->trans("NewCompany"));
        
        $soc->object->assign_post();
        
        // Assign values
        $soc->assign_values('create');

        dol_htmloutput_errors($soc->object->error,$soc->object->errors);

        // Display canvas
        $soc->display_canvas();

    }
}
elseif ($_GET["action"] == 'edit' || $_POST["action"] == 'edit')
{
    /*
     * Company Fact Mode edition
     */
    print_fiche_titre($langs->trans("EditCompany"));

    if ($socid)
    {
    	if ($reload || ! $_POST["nom"])
        {
            $soc = new Canvas($db);
            $soc->load_canvas('thirdparty@societe',$canvas);
            $soc->object->id = $socid;
            $soc->fetch($socid, $_GET["action"]);
        }
        else
        {
        	$soc->object->assign_post();
        }

        dol_htmloutput_errors($soc->object->error,$soc->object->errors);
        
        // Assign values
        $soc->assign_values('edit');
        
        // Display canvas
        $soc->display_canvas();  
    }
}
else
{
    /*
     * Company Fact Sheet mode visu
     */
	
    $soc->id = $socid;
    $result=$soc->fetch($socid);
    if ($result < 0)
    {
        dol_print_error($db,$soc->error);
        exit;
    }

    $head = societe_prepare_head($soc);

    dol_fiche_head($head, 'company', $langs->trans("ThirdParty"),0,'company');
    
	// Assign values
    $soc->assign_values('view');
    // Display canvas
	$soc->display_canvas();

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



    print '<table width="100%"><tr><td valign="top" width="50%">';
    print '<a name="builddoc"></a>'; // ancre

    /*
     * Documents generes
     */
    $filedir=$conf->societe->dir_output.'/'.$soc->id;
    $urlsource=$_SERVER["PHP_SELF"]."?socid=".$soc->id;
    $genallowed=$user->rights->societe->creer;
    $delallowed=$user->rights->societe->supprimer;

    $var=true;

    $somethingshown=$formfile->show_documents('company',$soc->id,$filedir,$urlsource,$genallowed,$delallowed,'',0,0,0,28,0,'',0,'',$soc->default_lang);

    print '</td>';
    print '<td>';
    print '</td>';
    print '</tr>';
    print '</table>';

    print '<br>';

    // Contacts list
    $result=show_contacts($conf,$langs,$db,$soc);

    // Projects list
    $result=show_projects($conf,$langs,$db,$soc);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
