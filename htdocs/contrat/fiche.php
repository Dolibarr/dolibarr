<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2010-2012 Juanjo Menent        <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/contrat/fiche.php
 *       \ingroup    contrat
 *       \brief      Page of a contract
 */

require ("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php');
require_once(DOL_DOCUMENT_ROOT."/core/modules/contract/modules_contract.php");
if ($conf->projet->enabled)  require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
if ($conf->propal->enabled)  require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->contrat->enabled) require_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
if ($conf->projet->enabled)  require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");

$langs->load("contracts");
$langs->load("orders");
$langs->load("companies");
$langs->load("bills");
$langs->load("products");

$action=GETPOST('action');
$socid = GETPOST('socid','int');
$contratid = GETPOST('id','int');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'contrat',$contratid,'contrat');

$usehm=$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE;

$object = new Contrat($db);


/*
 * Actions
 */

if ($action == 'confirm_active' && $_REQUEST["confirm"] == 'yes' && $user->rights->contrat->activer)
{
    $object->fetch($_GET["id"]);
    $result = $object->active_line($user, $_GET["ligne"], $_GET["date"], $_GET["dateend"], $_GET["comment"]);

    if ($result > 0)
    {
        Header("Location: fiche.php?id=".$object->id);
        exit;
    }
    else {
        $mesg=$object->error;
    }
}

if ($action == 'confirm_closeline' && $_REQUEST["confirm"] == 'yes' && $user->rights->contrat->activer)
{
    $object->fetch($_GET["id"]);
    $result = $object->close_line($user, $_GET["ligne"], $_GET["dateend"], urldecode($_GET["comment"]));

    if ($result > 0)
    {
        Header("Location: fiche.php?id=".$object->id);
        exit;
    }
    else {
        $mesg=$object->error;
    }
}

// Si ajout champ produit predefini
if ($_POST["mode"]=='predefined')
{
    $date_start='';
    $date_end='';
    if ($_POST["date_startmonth"] && $_POST["date_startday"] && $_POST["date_startyear"])
    {
        $date_start=dol_mktime($_POST["date_starthour"], $_POST["date_startmin"], 0, $_POST["date_startmonth"], $_POST["date_startday"], $_POST["date_startyear"]);
    }
    if ($_POST["date_endmonth"] && $_POST["date_endday"] && $_POST["date_endyear"])
    {
        $date_end=dol_mktime($_POST["date_endhour"], $_POST["date_endmin"], 0, $_POST["date_endmonth"], $_POST["date_endday"], $_POST["date_endyear"]);
    }
}

// Si ajout champ produit libre
if ($_POST["mode"]=='libre')
{
    $date_start_sl='';
    $date_end_sl='';
    if ($_POST["date_start_slmonth"] && $_POST["date_start_slday"] && $_POST["date_start_slyear"])
    {
        $date_start_sl=dol_mktime($_POST["date_start_slhour"], $_POST["date_start_slmin"], 0, $_POST["date_start_slmonth"], $_POST["date_start_slday"], $_POST["date_start_slyear"]);
    }
    if ($_POST["date_end_slmonth"] && $_POST["date_end_slday"] && $_POST["date_end_slyear"])
    {
        $date_end_sl=dol_mktime($_POST["date_end_slhour"], $_POST["date_end_slmin"], 0, $_POST["date_end_slmonth"], $_POST["date_end_slday"], $_POST["date_end_slyear"]);
    }
}

// Param dates
$date_contrat='';
$date_start_update='';
$date_end_update='';
$date_start_real_update='';
$date_end_real_update='';
if ($_POST["date_start_updatemonth"] && $_POST["date_start_updateday"] && $_POST["date_start_updateyear"])
{
    $date_start_update=dol_mktime($_POST["date_start_updatehour"], $_POST["date_start_updatemin"], 0, $_POST["date_start_updatemonth"], $_POST["date_start_updateday"], $_POST["date_start_updateyear"]);
}
if ($_POST["date_end_updatemonth"] && $_POST["date_end_updateday"] && $_POST["date_end_updateyear"])
{
    $date_end_update=dol_mktime($_POST["date_end_updatehour"], $_POST["date_end_updatemin"], 0, $_POST["date_end_updatemonth"], $_POST["date_end_updateday"], $_POST["date_end_updateyear"]);
}
if ($_POST["date_start_real_updatemonth"] && $_POST["date_start_real_updateday"] && $_POST["date_start_real_updateyear"])
{
    $date_start_real_update=dol_mktime($_POST["date_start_real_updatehour"], $_POST["date_start_real_updatemin"], 0, $_POST["date_start_real_updatemonth"], $_POST["date_start_real_updateday"], $_POST["date_start_real_updateyear"]);
}
if ($_POST["date_end_real_updatemonth"] && $_POST["date_end_real_updateday"] && $_POST["date_end_real_updateyear"])
{
    $date_end_real_update=dol_mktime($_POST["date_end_real_updatehour"], $_POST["date_end_real_updatemin"], 0, $_POST["date_end_real_updatemonth"], $_POST["date_end_real_updateday"], $_POST["date_end_real_updateyear"]);
}
if ($_POST["remonth"] && $_POST["reday"] && $_POST["reyear"])
{
    $datecontrat = dol_mktime($_POST["rehour"], $_POST["remin"], 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
}

if ($action == 'add')
{
    $object->socid         = $_POST["socid"];
    $object->date_contrat   = $datecontrat;

    $object->commercial_suivi_id      = $_POST["commercial_suivi_id"];
    $object->commercial_signature_id  = $_POST["commercial_signature_id"];

    $object->note           = trim($_POST["note"]);
    $object->fk_project     = trim($_POST["projectid"]);
    $object->remise_percent = trim($_POST["remise_percent"]);
    $object->ref            = trim($_POST["ref"]);

    // Check
    if (empty($datecontrat))
    {
        $error++;
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date")).'</div>';
        $_GET["socid"]=$_POST["socid"];
        $action='create';
    }

    if (! $error)
    {
        $result = $object->create($user,$langs,$conf);
        if ($result > 0)
        {
            Header("Location: fiche.php?id=".$object->id);
            exit;
        }
        else {
            $mesg='<div class="error">'.$object->error.'</div>';
        }
        $_GET["socid"]=$_POST["socid"];
        $action='create';
    }
}

if ($action == 'classin')
{
    $object->fetch($_GET["id"]);
    $object->setProject($_POST["projectid"]);
}

if ($action == 'addline' && $user->rights->contrat->creer)
{
    if ($_POST["pqty"] && (($_POST["pu"] != '' && $_POST["desc"]) || $_POST["idprod"]))
    {
        $ret=$object->fetch($_GET["id"]);
        if ($ret < 0)
        {
            dol_print_error($db,$object->error);
            exit;
        }
        $ret=$object->fetch_thirdparty();

        $date_start='';
        $date_end='';
        // Si ajout champ produit libre
        if ($_POST['mode'] == 'libre')
        {
            if ($_POST["date_start_slmonth"] && $_POST["date_start_slday"] && $_POST["date_start_slyear"])
            {
                $date_start=dol_mktime($_POST["date_start_slhour"], $_POST["date_start_slmin"], 0, $_POST["date_start_slmonth"], $_POST["date_start_slday"], $_POST["date_start_slyear"]);
            }
            if ($_POST["date_end_slmonth"] && $_POST["date_end_slday"] && $_POST["date_end_slyear"])
            {
                $date_end=dol_mktime($_POST["date_end_slhour"], $_POST["date_end_slmin"], 0, $_POST["date_end_slmonth"], $_POST["date_end_slday"], $_POST["date_end_slyear"]);
            }
        }
        // Si ajout champ produit predefini
        if ($_POST['mode'] == 'predefined')
        {
            if ($_POST["date_startmonth"] && $_POST["date_startday"] && $_POST["date_startyear"])
            {
                $date_start=dol_mktime($_POST["date_starthour"], $_POST["date_startmin"], 0, $_POST["date_startmonth"], $_POST["date_startday"], $_POST["date_startyear"]);
            }
            if ($_POST["date_endmonth"] && $_POST["date_endday"] && $_POST["date_endyear"])
            {
                $date_end=dol_mktime($_POST["date_endhour"], $_POST["date_endmin"], 0, $_POST["date_endmonth"], $_POST["date_endday"], $_POST["date_endyear"]);
            }
        }

        // Ecrase $pu par celui du produit
        // Ecrase $desc par celui du produit
        // Ecrase $txtva par celui du produit
        // Ecrase $base_price_type par celui du produit
        if ($_POST['idprod'])
        {
            $prod = new Product($db);
            $prod->fetch($_POST['idprod']);

            $tva_tx = get_default_tva($mysoc,$object->thirdparty,$prod->id);
            $tva_npr = get_default_npr($mysoc,$object->thirdparty,$prod->id);

            // On defini prix unitaire
            if ($conf->global->PRODUIT_MULTIPRICES && $object->thirdparty->price_level)
            {
                $pu_ht = $prod->multiprices[$object->thirdparty->price_level];
                $pu_ttc = $prod->multiprices_ttc[$object->thirdparty->price_level];
                $price_min = $prod->multiprices_min[$object->thirdparty->price_level];
                $price_base_type = $prod->multiprices_base_type[$object->thirdparty->price_level];
            }
            else
            {
                $pu_ht = $prod->price;
                $pu_ttc = $prod->price_ttc;
                $price_min = $prod->price_min;
                $price_base_type = $prod->price_base_type;
            }

            // On reevalue prix selon taux tva car taux tva transaction peut etre different
            // de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
            if ($tva_tx != $prod->tva_tx)
            {
                if ($price_base_type != 'HT')
                {
                    $pu_ht = price2num($pu_ttc / (1 + ($tva_tx/100)), 'MU');
                }
                else
                {
                    $pu_ttc = price2num($pu_ht * (1 + ($tva_tx/100)), 'MU');
                }
            }

           	$desc = $prod->description;
           	$desc.= $prod->description && $_POST['desc'] ? "\n" : "";
           	$desc.= $_POST['desc'];
        }
        else
        {
            $pu_ht=$_POST['pu'];
            $price_base_type = 'HT';
            $tva_tx=str_replace('*','',$_POST['tva_tx']);
            $tva_npr=preg_match('/\*/',$_POST['tva_tx'])?1:0;
            $desc=$_POST['desc'];
        }

        $localtax1_tx=get_localtax($tva_tx,1,$object->societe);
        $localtax2_tx=get_localtax($tva_tx,2,$object->societe);

        $info_bits=0;
        if ($tva_npr) $info_bits |= 0x01;

        if($price_min && (price2num($pu_ht)*(1-price2num($_POST['remise_percent'])/100) < price2num($price_min)))
        {
            $object->error = $langs->trans("CantBeLessThanMinPrice",price2num($price_min,'MU').' '.$langs->trans("Currency".$conf->currency));
            $result = -1 ;
        }
        else
        {
            // Insert line
            $result = $object->addline(
                $desc,
                $pu_ht,
                $_POST["pqty"],
                $tva_tx,
                $localtax1_tx,
                $localtax2_tx,
                $_POST["idprod"],
                $_POST["premise"],
                $date_start,
                $date_end,
                $price_base_type,
                $pu_ttc,
                $info_bits
            );
        }

        if ($result > 0)
        {
            /*
             // Define output language
             $outputlangs = $langs;
             $newlang='';
             if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
             if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
             if (! empty($newlang))
             {
             $outputlangs = new Translate("",$conf);
             $outputlangs->setDefaultLang($newlang);
             }
             if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
             {
	            $ret=$object->fetch($id);    // Reload to get new records
             	contrat_pdf_create($db, $object->id, $object->modelpdf, $outputlangs);
             }
             */
        }
        else
        {
            $mesg='<div class="error">'.$object->error.'</div>';
        }
    }
}

if ($action == 'updateligne' && $user->rights->contrat->creer && ! $_POST["cancel"])
{
	$ret=$object->fetch($_GET["id"]);
	if ($ret < 0)
	{
		dol_print_error($db,$object->error);
		exit;
	}

	$object->fetch_thirdparty();
    $objectline = new ContratLigne($db);
    if ($objectline->fetch($_POST["elrowid"]))
    {
        $db->begin();

        if ($date_start_real_update == '') $date_start_real_update=$objectline->date_ouverture;
        if ($date_end_real_update == '')   $date_end_real_update=$objectline->date_cloture;

		$localtax1_tx=get_localtax($_POST["eltva_tx"],1,$object->thirdparty);
        $localtax2_tx=get_localtax($_POST["eltva_tx"],2,$object->thirdparty);

        $objectline->description=$_POST["eldesc"];
        $objectline->price_ht=$_POST["elprice"];
        $objectline->subprice=$_POST["elprice"];
        $objectline->qty=$_POST["elqty"];
        $objectline->remise_percent=$_POST["elremise_percent"];
        $objectline->tva_tx=$_POST["eltva_tx"];
        $objectline->localtax1_tx=$localtax1_tx;
        $objectline->localtax2_tx=$localtax2_tx;
        $objectline->date_ouverture_prevue=$date_start_update;
        $objectline->date_ouverture=$date_start_real_update;
        $objectline->date_fin_validite=$date_end_update;
        $objectline->date_cloture=$date_end_real_update;
        $objectline->fk_user_cloture=$user->id;

        // TODO verifier price_min si fk_product et multiprix

        $result=$objectline->update($user);
        if ($result > 0)
        {
            $db->commit();
        }
        else
        {
            dol_print_error($db,'Failed to update contrat_det');
            $db->rollback();
        }
    }
    else
    {
        dol_print_error($db);
    }
}

if ($action == 'confirm_deleteline' && $_REQUEST["confirm"] == 'yes' && $user->rights->contrat->creer)
{
    $object->fetch($_GET["id"]);
    $result = $object->deleteline($_GET["lineid"],$user);

    if ($result >= 0)
    {
        Header("Location: fiche.php?id=".$object->id);
        exit;
    }
    else
    {
        $mesg=$object->error;
    }
}

if ($action == 'confirm_valid' && $_REQUEST["confirm"] == 'yes' && $user->rights->contrat->creer)
{
    $object->fetch($_GET["id"]);
    $result = $object->validate($user,$langs,$conf);
}

// Close all lines
if ($action == 'confirm_close' && $_REQUEST["confirm"] == 'yes' && $user->rights->contrat->creer)
{
    $object->fetch($_GET["id"]);
    $result = $object->cloture($user,$langs,$conf);
}

if ($action == 'confirm_delete' && $_REQUEST["confirm"] == 'yes')
{
    if ($user->rights->contrat->supprimer)
    {
        $object->id = $_GET["id"];
        $result=$object->delete($user,$langs,$conf);
        if ($result >= 0)
        {
            Header("Location: index.php");
            return;
        }
        else
        {
            $mesg='<div class="error">'.$object->error.'</div>';
        }
    }
}

if ($action == 'confirm_move' && $_REQUEST["confirm"] == 'yes')
{
    if ($user->rights->contrat->creer)
    {
        if ($_POST['newcid'] > 0)
        {
            $contractline = new ContratLigne($db);
            $result=$contractline->fetch($_GET["lineid"]);
            $contractline->fk_contrat = $_POST["newcid"];
            $result=$contractline->update($user,1);
            if ($result >= 0)
            {
                Header("Location: ".$_SERVER['PHP_SELF'].'?id='.$_GET['id']);
                return;
            }
            else
            {
                $mesg='<div class="error">'.$object->error.'</div>';
            }
        }
        else
        {
            $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("RefNewContract")).'</div>';
        }
    }
}


/*
 * View
 */

llxHeader('',$langs->trans("ContractCard"),"Contrat");

$form = new Form($db);
$form = new Form($db);

$objectlignestatic=new ContratLigne($db);


/*********************************************************************
 *
 * Mode creation
 *
 *********************************************************************/
if ($action == 'create')
{
    dol_fiche_head($head, $a, $langs->trans("AddContract"), 0, 'contract');

    dol_htmloutput_errors($mesg,'');

    $soc = new Societe($db);
    $soc->fetch($socid);

    $object->date_contrat = dol_now();
    if ($contratid) $result=$object->fetch($contratid);

    $numct = $object->getNextNumRef($soc);

    print '<form name="contrat" action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="socid" value="'.$soc->id.'">'."\n";
    print '<input type="hidden" name="remise_percent" value="0">';

    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td>'.$langs->trans("Ref").'</td>';
    print '<td><input type="text" maxlength="30" name="ref" size="20" value="'.$numct.'"></td></tr>';

    // Customer
    print '<tr><td>'.$langs->trans("Customer").'</td><td>'.$soc->getNomUrl(1).'</td></tr>';

    // Ligne info remises tiers
    print '<tr><td>'.$langs->trans('Discount').'</td><td>';
    if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
    else print $langs->trans("CompanyHasNoRelativeDiscount");
    $absolute_discount=$soc->getAvailableDiscounts();
    print '. ';
    if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->currency));
    else print $langs->trans("CompanyHasNoAbsoluteDiscount");
    print '.';
    print '</td></tr>';

    // Commercial suivi
    print '<tr><td width="20%" nowrap><span class="fieldrequired">'.$langs->trans("TypeContact_contrat_internal_SALESREPFOLL").'</span></td><td>';
    print $form->select_users(GETPOST("commercial_suivi_id")?GETPOST("commercial_suivi_id"):$user->id,'commercial_suivi_id',1,'');
    print '</td></tr>';

    // Commercial signature
    print '<tr><td width="20%" nowrap><span class="fieldrequired">'.$langs->trans("TypeContact_contrat_internal_SALESREPSIGN").'</span></td><td>';
    print $form->select_users(GETPOST("commercial_signature_id")?GETPOST("commercial_signature_id"):$user->id,'commercial_signature_id',1,'');
    print '</td></tr>';

    print '<tr><td><span class="fieldrequired">'.$langs->trans("Date").'</span></td><td>';
    $form->select_date($datecontrat,'',0,0,'',"contrat");
    print "</td></tr>";

    if ($conf->projet->enabled)
    {
        print '<tr><td>'.$langs->trans("Project").'</td><td>';
        select_projects($soc->id,GETPOST("projectid"),"projectid");
        print "</td></tr>";
    }

    print '<tr><td>'.$langs->trans("NotePublic").'</td><td valign="top">';
    print '<textarea name="note_public" wrap="soft" cols="70" rows="'.ROWS_3.'">';
    print GETPOST("note_public");
    print '</textarea></td></tr>';

    if (! $user->societe_id)
    {
        print '<tr><td>'.$langs->trans("NotePrivate").'</td><td valign="top">';
        print '<textarea name="note" wrap="soft" cols="70" rows="'.ROWS_3.'">';
        print GETPOST("note");
        print '</textarea></td></tr>';
    }

    print "</table>\n";

    print '<br><center><input type="submit" class="button" value="'.$langs->trans("Create").'"></center>';

    print "</form>\n";

    dol_fiche_end();
}
else
/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
{
    $now=dol_now();

    $id = $_GET["id"];
    $ref= $_GET['ref'];
    if ($id > 0 || ! empty($ref))
    {
        $result=$object->fetch($_GET['id'],$_GET['ref']);
        if ($result > 0)
        {
            $id = $object->id; // if $_GET['ref']
            $result=$object->fetch_lines();
        }
        if ($result < 0)
        {
            dol_print_error($db,$object->error);
            exit;
        }

        dol_htmloutput_errors($mesg,'');

        $nbofservices=count($object->lines);

        $author = new User($db);
        $author->fetch($object->user_author_id);

        $commercial_signature = new User($db);
        $commercial_signature->fetch($object->commercial_signature_id);

        $commercial_suivi = new User($db);
        $commercial_suivi->fetch($object->commercial_suivi_id);

        $head = contract_prepare_head($object);

        $hselected = 0;

        dol_fiche_head($head, $hselected, $langs->trans("Contract"), 0, 'contract');


        /*
         * Confirmation de la suppression du contrat
         */
        if ($action == 'delete')
        {
            $ret=$form->form_confirm("fiche.php?id=$id",$langs->trans("DeleteAContract"),$langs->trans("ConfirmDeleteAContract"),"confirm_delete",'',0,1);
            if ($ret == 'html') print '<br>';
        }

        /*
         * Confirmation de la validation
         */
        if ($action == 'valid')
        {
            //$numfa = contrat_get_num($soc);
            $ret=$form->form_confirm("fiche.php?id=$id",$langs->trans("ValidateAContract"),$langs->trans("ConfirmValidateContract"),"confirm_valid",'',0,1);
            if ($ret == 'html') print '<br>';
        }

        /*
         * Confirmation de la fermeture
         */
        if ($action == 'close')
        {
            $ret=$form->form_confirm("fiche.php?id=$id",$langs->trans("CloseAContract"),$langs->trans("ConfirmCloseContract"),"confirm_close",'',0,1);
            if ($ret == 'html') print '<br>';
        }

        /*
         *   Contrat
         */
        if ($object->brouillon && $user->rights->contrat->creer)
        {
            print '<form action="fiche.php?id='.$id.'" method="post">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="setremise">';
        }

        print '<table class="border" width="100%">';

        // Ref du contrat
        print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">';
        print $form->showrefnav($object,'ref','',1,'ref','ref','');
        print "</td></tr>";

        // Customer
        print "<tr><td>".$langs->trans("Customer")."</td>";
        print '<td colspan="3">'.$object->societe->getNomUrl(1).'</td></tr>';

        // Ligne info remises tiers
        print '<tr><td>'.$langs->trans('Discount').'</td><td colspan="3">';
        if ($object->societe->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$object->societe->remise_client);
        else print $langs->trans("CompanyHasNoRelativeDiscount");
        $absolute_discount=$object->societe->getAvailableDiscounts();
        print '. ';
        if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->currency));
        else print $langs->trans("CompanyHasNoAbsoluteDiscount");
        print '.';
        print '</td></tr>';

        // Statut contrat
        print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">';
        if ($object->statut==0) print $object->getLibStatut(2);
        else print $object->getLibStatut(4);
        print "</td></tr>";

        // Date
        print '<tr><td>'.$langs->trans("Date").'</td>';
        print '<td colspan="3">'.dol_print_date($object->date_contrat,"dayhour")."</td></tr>\n";

        // Projet
        if ($conf->projet->enabled)
        {
            $langs->load("projects");
            print '<tr><td>';
            print '<table width="100%" class="nobordernopadding"><tr><td>';
            print $langs->trans("Project");
            print '</td>';
            if ($action != "classify" && $user->rights->projet->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classify&amp;id='.$id.'">'.img_edit($langs->trans("SetProject")).'</a></td>';
            print '</tr></table>';
            print '</td><td colspan="3">';
            if ($action == "classify")
            {
                $form->form_project("fiche.php?id=$id",$object->socid,$object->fk_project,"projectid");
            }
            else
            {
                $form->form_project("fiche.php?id=$id",$object->socid,$object->fk_project,"none");
            }
            print "</td></tr>";
        }

        print "</table>";

        if ($object->brouillon == 1 && $user->rights->contrat->creer)
        {
            print '</form>';
        }

        echo '<br>';

        $servicepos=(isset($_REQUEST["servicepos"])?$_REQUEST["servicepos"]:1);
        $colorb='666666';

        $arrayothercontracts=$object->getListOfContracts('others');

        /*
         * Lines of contracts
         */
        $productstatic=new Product($db);

        // Title line for service
        print '<table class="notopnoleft" width="100%">';	// Array with (n*2)+1 lines
        $cursorline=1;
        while ($cursorline <= $nbofservices)
        {
            print '<tr height="16" '.$bc[false].'>';
            print '<td class="liste_titre" width="90" style="border-left: 1px solid #'.$colorb.'; border-top: 1px solid #'.$colorb.'; border-bottom: 1px solid #'.$colorb.';">';
            print $langs->trans("ServiceNb",$cursorline).'</td>';

            print '<td class="tab" style="border-right: 1px solid #'.$colorb.'; border-top: 1px solid #'.$colorb.'; border-bottom: 1px solid #'.$colorb.';" rowspan="2">';

            // Area with common detail of line
            print '<table class="notopnoleft" width="100%">';

            $sql = "SELECT cd.rowid, cd.statut, cd.label as label_det, cd.fk_product, cd.description, cd.price_ht, cd.qty,";
            $sql.= " cd.tva_tx, cd.remise_percent, cd.info_bits, cd.subprice,";
            $sql.= " cd.date_ouverture_prevue as date_debut, cd.date_ouverture as date_debut_reelle,";
            $sql.= " cd.date_fin_validite as date_fin, cd.date_cloture as date_fin_reelle,";
            $sql.= " cd.commentaire as comment,";
            $sql.= " p.rowid as pid, p.ref as pref, p.label as label, p.fk_product_type as ptype";
            $sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
            $sql.= " WHERE cd.rowid = ".$object->lines[$cursorline-1]->id;

            $result = $db->query($sql);
            if ($result)
            {
                $total = 0;

                print '<tr class="liste_titre">';
                print '<td>'.$langs->trans("Service").'</td>';
                print '<td width="50" align="center">'.$langs->trans("VAT").'</td>';
                print '<td width="50" align="right">'.$langs->trans("PriceUHT").'</td>';
                print '<td width="30" align="center">'.$langs->trans("Qty").'</td>';
                print '<td width="50" align="right">'.$langs->trans("ReductionShort").'</td>';
                print '<td width="30">&nbsp;</td>';
                print "</tr>\n";

                $var=true;

                $objp = $db->fetch_object($result);

                $var=!$var;

                if ($_REQUEST["action"] != 'editline' || $_GET["rowid"] != $objp->rowid)
                {
                    print '<tr '.$bc[$var].' valign="top">';
                    // Libelle
                    if ($objp->fk_product > 0)
                    {
                        print '<td>';
                        $productstatic->id=$objp->fk_product;
                        $productstatic->type=$objp->ptype;
                        $productstatic->ref=$objp->pref;
                        print $productstatic->getNomUrl(1,'',20);
                        print $objp->label?' - '.dol_trunc($objp->label,16):'';
                        if ($objp->description) print '<br>'.dol_nl2br($objp->description);
                        print '</td>';
                    }
                    else
                    {
                        print "<td>".nl2br($objp->description)."</td>\n";
                    }
                    // TVA
                    print '<td align="center">'.vatrate($objp->tva_tx,'%',$objp->info_bits).'</td>';
                    // Prix
                    print '<td align="right">'.price($objp->subprice)."</td>\n";
                    // Quantite
                    print '<td align="center">'.$objp->qty.'</td>';
                    // Remise
                    if ($objp->remise_percent > 0)
                    {
                        print '<td align="right">'.$objp->remise_percent."%</td>\n";
                    }
                    else
                    {
                        print '<td>&nbsp;</td>';
                    }
                    // Icon move, update et delete (statut contrat 0=brouillon,1=valide,2=ferme)
                    print '<td align="right" nowrap="nowrap">';
                    if ($user->rights->contrat->creer && count($arrayothercontracts) && ($object->statut >= 0))
                    {
                        print '<a href="fiche.php?id='.$object->id.'&amp;action=move&amp;rowid='.$objp->rowid.'">';
                        print img_picto($langs->trans("MoveToAnotherContract"),'uparrow');
                        print '</a>';
                    }
                    else {
                        print '&nbsp;';
                    }
                    if ($user->rights->contrat->creer && ($object->statut >= 0))
                    {
                        print '<a href="fiche.php?id='.$object->id.'&amp;action=editline&amp;rowid='.$objp->rowid.'">';
                        print img_edit();
                        print '</a>';
                    }
                    else {
                        print '&nbsp;';
                    }
                    if ( $user->rights->contrat->creer && ($object->statut >= 0))
                    {
                        print '&nbsp;';
                        print '<a href="fiche.php?id='.$object->id.'&amp;action=deleteline&amp;rowid='.$objp->rowid.'">';
                        print img_delete();
                        print '</a>';
                    }
                    print '</td>';

                    print "</tr>\n";

                    // Dates de en service prevues et effectives
                    if ($objp->subprice >= 0)
                    {
                        print '<tr '.$bc[$var].'>';
                        print '<td colspan="6">';

                        // Date planned
                        print $langs->trans("DateStartPlanned").': ';
                        if ($objp->date_debut)
                        {
                            print dol_print_date($db->jdate($objp->date_debut));
                            // Warning si date prevu passee et pas en service
                            if ($objp->statut == 0 && $db->jdate($objp->date_debut) < ($now - $conf->contrat->services->inactifs->warning_delay)) { print " ".img_warning($langs->trans("Late")); }
                        }
                        else print $langs->trans("Unknown");
                        print ' &nbsp;-&nbsp; ';
                        print $langs->trans("DateEndPlanned").': ';
                        if ($objp->date_fin)
                        {
                            print dol_print_date($db->jdate($objp->date_fin));
                            if ($objp->statut == 4 && $db->jdate($objp->date_fin) < ($now - $conf->contrat->services->expires->warning_delay)) { print " ".img_warning($langs->trans("Late")); }
                        }
                        else print $langs->trans("Unknown");

                        print '</td>';
                        print '</tr>';
                    }
                }
                // Ligne en mode update
                else
                {
                    print "<form name='update' action=\"fiche.php?id=$id\" method=\"post\">";
                    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                    print '<input type="hidden" name="action" value="updateligne">';
                    print '<input type="hidden" name="elrowid" value="'.$_GET["rowid"].'">';
                    // Ligne carac
                    print "<tr $bc[$var]>";
                    print '<td>';
                    if ($objp->fk_product)
                    {
                        $productstatic->id=$objp->fk_product;
                        $productstatic->type=$objp->ptype;
                        $productstatic->ref=$objp->pref;
                        print $productstatic->getNomUrl(1,'',20);
                        print $objp->label?' - '.dol_trunc($objp->label,16):'';
                        print '<br>';
                    }
                    else
                    {
                        print $objp->label?$objp->label.'<br>':'';
                    }
                    print '<textarea name="eldesc" cols="70" rows="1">'.$objp->description.'</textarea></td>';
                    print '<td align="right">';
                    print $form->load_tva("eltva_tx",$objp->tva_tx,$mysoc,$object->societe);
                    print '</td>';
                    print '<td align="right"><input size="5" type="text" name="elprice" value="'.price($objp->subprice).'"></td>';
                    print '<td align="center"><input size="2" type="text" name="elqty" value="'.$objp->qty.'"></td>';
                    print '<td align="right"><input size="1" type="text" name="elremise_percent" value="'.$objp->remise_percent.'">%</td>';
                    print '<td align="center" rowspan="2" valign="middle"><input type="submit" class="button" name="save" value="'.$langs->trans("Modify").'">';
                    print '<br><input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
                    print '</td>';
                    // Ligne dates prevues
                    print "<tr $bc[$var]>";
                    print '<td colspan="5">';
                    print $langs->trans("DateStartPlanned").' ';
                    $form->select_date($db->jdate($objp->date_debut),"date_start_update",$usehm,$usehm,($db->jdate($objp->date_debut)>0?0:1),"update");
                    print '<br>'.$langs->trans("DateEndPlanned").' ';
                    $form->select_date($db->jdate($objp->date_fin),"date_end_update",$usehm,$usehm,($db->jdate($objp->date_fin)>0?0:1),"update");
                    print '</td>';
                    print '</tr>';

                    print "</form>\n";
                }

                $db->free($result);
            }
            else
            {
                dol_print_error($db);
            }

            if ($object->statut > 0)
            {
                print '<tr '.$bc[false].'>';
                print '<td colspan="6"><hr></td>';
                print "</tr>\n";
            }

            print "</table>";


            /*
             * Confirmation to delete service line of contract
             */
            if ($_REQUEST["action"] == 'deleteline' && ! $_REQUEST["cancel"] && $user->rights->contrat->creer && $object->lines[$cursorline-1]->id == $_GET["rowid"])
            {
                $ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$object->id."&lineid=".$_GET["rowid"],$langs->trans("DeleteContractLine"),$langs->trans("ConfirmDeleteContractLine"),"confirm_deleteline",'',0,1);
                if ($ret == 'html') print '<table class="notopnoleftnoright" width="100%"><tr '.$bc[false].' height="6"><td></td></tr></table>';
            }

            /*
             * Confirmation to move service toward another contract
             */
            if ($_REQUEST["action"] == 'move' && ! $_REQUEST["cancel"] && $user->rights->contrat->creer && $object->lines[$cursorline-1]->id == $_GET["rowid"])
            {
                $arraycontractid=array();
                foreach($arrayothercontracts as $contractcursor)
                {
                    $arraycontractid[$contractcursor->id]=$contractcursor->ref;
                }
                //var_dump($arraycontractid);
                // Cree un tableau formulaire
                $formquestion=array(
				'text' => $langs->trans("ConfirmMoveToAnotherContractQuestion"),
                array('type' => 'select', 'name' => 'newcid', 'values' => $arraycontractid));

                $form->form_confirm($_SERVER["PHP_SELF"]."?id=".$object->id."&lineid=".$_GET["rowid"],$langs->trans("MoveToAnotherContract"),$langs->trans("ConfirmMoveToAnotherContract"),"confirm_move",$formquestion);
                print '<table class="notopnoleftnoright" width="100%"><tr '.$bc[false].' height="6"><td></td></tr></table>';
            }

            /*
             * Confirmation de la validation activation
             */
            if ($_REQUEST["action"] == 'active' && ! $_REQUEST["cancel"] && $user->rights->contrat->activer && $object->lines[$cursorline-1]->id == $_GET["ligne"])
            {
                $dateactstart = dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
                $dateactend   = dol_mktime(12, 0, 0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
                $comment      = $_POST["comment"];
                $form->form_confirm($_SERVER["PHP_SELF"]."?id=".$object->id."&ligne=".$_GET["ligne"]."&date=".$dateactstart."&dateend=".$dateactend."&comment=".urlencode($comment),$langs->trans("ActivateService"),$langs->trans("ConfirmActivateService",dol_print_date($dateactstart,"%A %d %B %Y")),"confirm_active", '', 0, 1);
                print '<table class="notopnoleftnoright" width="100%"><tr '.$bc[false].' height="6"><td></td></tr></table>';
            }

            /*
             * Confirmation de la validation fermeture
             */
            if ($_REQUEST["action"] == 'closeline' && ! $_REQUEST["cancel"] && $user->rights->contrat->activer && $object->lines[$cursorline-1]->id == $_GET["ligne"])
            {
                $dateactstart = dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
                $dateactend   = dol_mktime(12, 0, 0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
                $comment      = $_POST["comment"];
                $form->form_confirm($_SERVER["PHP_SELF"]."?id=".$object->id."&ligne=".$_GET["ligne"]."&date=".$dateactstart."&dateend=".$dateactend."&comment=".urlencode($comment), $langs->trans("CloseService"), $langs->trans("ConfirmCloseService",dol_print_date($dateactend,"%A %d %B %Y")), "confirm_closeline", '', 0, 1);
                print '<table class="notopnoleftnoright" width="100%"><tr '.$bc[false].' height="6"><td></td></tr></table>';
            }


            // Area with status and activation info of line
            if ($object->statut > 0)
            {
                print '<table class="notopnoleft" width="100%">';

                print '<tr '.$bc[false].'>';
                print '<td>'.$langs->trans("ServiceStatus").': '.$object->lines[$cursorline-1]->getLibStatut(4).'</td>';
                print '<td width="30" align="right">';
                if ($user->societe_id == 0)
                {
                    if ($object->statut > 0 && $_REQUEST["action"] != 'activateline' && $_REQUEST["action"] != 'unactivateline')
                    {
                        $action='activateline';
                        if ($objp->statut == 4) $action='unactivateline';
                        print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;ligne='.$object->lines[$cursorline-1]->id.'&amp;action='.$action.'">';
                        print img_edit();
                        print '</a>';
                    }
                }
                print '</td>';
                print "</tr>\n";

                print '<tr '.$bc[false].'>';

                print '<td>';
                // Si pas encore active
                if (! $objp->date_debut_reelle) {
                    print $langs->trans("DateStartReal").': ';
                    if ($objp->date_debut_reelle) print dol_print_date($objp->date_debut_reelle);
                    else print $langs->trans("ContractStatusNotRunning");
                }
                // Si active et en cours
                if ($objp->date_debut_reelle && ! $objp->date_fin_reelle) {
                    print $langs->trans("DateStartReal").': ';
                    print dol_print_date($objp->date_debut_reelle);
                }
                // Si desactive
                if ($objp->date_debut_reelle && $objp->date_fin_reelle) {
                    print $langs->trans("DateStartReal").': ';
                    print dol_print_date($objp->date_debut_reelle);
                    print ' &nbsp;-&nbsp; ';
                    print $langs->trans("DateEndReal").': ';
                    print dol_print_date($objp->date_fin_reelle);
                }
                if (! empty($objp->comment)) print "<br>".$objp->comment;
                print '</td>';

                print '<td align="center">&nbsp;</td>';

                print '</tr>';
                print '</table>';
            }

            if ($user->rights->contrat->activer && $_REQUEST["action"] == 'activateline' && $object->lines[$cursorline-1]->id == $_GET["ligne"])
            {
                /**
                 * Activer la ligne de contrat
                 */
                print '<form name="active" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;ligne='.$_GET["ligne"].'&amp;action=active" method="post">';
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

                print '<table class="noborder" width="100%">';
                //print '<tr class="liste_titre"><td colspan="5">'.$langs->trans("Status").'</td></tr>';

                // Definie date debut et fin par defaut
                $dateactstart = $objp->date_debut;
                if ($_POST["remonth"]) $dateactstart = dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
                elseif (! $dateactstart) $dateactstart = time();

                $dateactend = $objp->date_fin;
                if ($_POST["endmonth"]) $dateactend = dol_mktime(12, 0, 0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
                elseif (! $dateactend)
                {
                    if ($objp->fk_product > 0)
                    {
                        $product=new Product($db);
                        $product->fetch($objp->fk_product);
                        $dateactend = dol_time_plus_duree(time(), $product->duration_value, $product->duration_unit);
                    }
                }

                print '<tr '.$bc[$var].'><td>'.$langs->trans("DateServiceActivate").'</td><td>';
                print $form->select_date($dateactstart,'',$usehm,$usehm,'',"active");
                print '</td>';

                print '<td>'.$langs->trans("DateEndPlanned").'</td><td>';
                print $form->select_date($dateactend,"end",$usehm,$usehm,'',"active");
                print '</td>';

                print '<td align="center" rowspan="2" valign="middle">';
                print '<input type="submit" class="button" name="activate" value="'.$langs->trans("Activate").'"><br>';
                print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
                print '</td>';

                print '</tr>';

                print '<tr '.$bc[$var].'><td>'.$langs->trans("Comment").'</td><td colspan="3"><input size="80" type="text" name="comment" value="'.$_POST["comment"].'"></td></tr>';

                print '</table>';

                print '</form>';
            }

            if ($user->rights->contrat->activer && $_REQUEST["action"] == 'unactivateline' && $object->lines[$cursorline-1]->id == $_GET["ligne"])
            {
                /**
                 * Desactiver la ligne de contrat
                 */
                print '<form name="closeline" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;ligne='.$object->lines[$cursorline-1]->id.'&amp;action=closeline" method="post">';
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

                print '<table class="noborder" width="100%">';

                // Definie date debut et fin par defaut
                $dateactstart = $objp->date_debut_reelle;
                if ($_POST["remonth"]) $dateactstart = dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
                elseif (! $dateactstart) $dateactstart = time();

                $dateactend = $objp->date_fin_reelle;
                if ($_POST["endmonth"]) $dateactend = dol_mktime(12, 0, 0, $_POST["endmonth"], $_POST["endday"], $_POST["endyear"]);
                elseif (! $dateactend)
                {
                    if ($objp->fk_product > 0)
                    {
                        $product=new Product($db);
                        $product->fetch($objp->fk_product);
                        $dateactend = dol_time_plus_duree(time(), $product->duration_value, $product->duration_unit);
                    }
                }
                $now=mktime();
                if ($dateactend > $now) $dateactend=$now;

                print '<tr '.$bc[$var].'><td colspan="2">';
                if ($objp->statut >= 4)
                {
                    if ($objp->statut == 4)
                    {
                        print $langs->trans("DateEndReal").' ';
                        $form->select_date($dateactend,"end",$usehm,$usehm,($objp->date_fin_reelle>0?0:1),"closeline");
                    }
                }
                print '</td>';

                print '<td align="right" rowspan="2"><input type="submit" class="button" name="close" value="'.$langs->trans("Close").'"><br>';
                print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
                print '</td></tr>';

                print '<tr '.$bc[$var].'><td>'.$langs->trans("Comment").'</td><td><input size="70" type="text" class="flat" name="comment" value="'.$_POST["comment"].'"></td></tr>';
                print '</table>';

                print '</form>';
            }

            print '</td>';	// End td if line is 1

            print '</tr>';
            print '<tr><td style="border-right: 1px solid #'.$colorb.'">&nbsp;</td></tr>';
            $cursorline++;
        }
        print '</table>';

        /*
         * Ajouter une ligne produit/service
         */
        if ($user->rights->contrat->creer && ($object->statut >= 0))
        {
            print '<br>';
            print '<table class="noborder" width="100%">';	// Array with (n*2)+1 lines

            print "<tr class=\"liste_titre\">";
            print '<td>'.$langs->trans("Service").'</td>';
            print '<td align="center">'.$langs->trans("VAT").'</td>';
            print '<td align="right">'.$langs->trans("PriceUHT").'</td>';
            print '<td align="center">'.$langs->trans("Qty").'</td>';
            print '<td align="right">'.$langs->trans("ReductionShort").'</td>';
            print '<td>&nbsp;</td>';
            print '<td>&nbsp;</td>';
            print "</tr>\n";

            $var=false;

            // Service sur produit predefini
            print '<form name="addline" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="post">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="addline">';
            print '<input type="hidden" name="mode" value="predefined">';
            print '<input type="hidden" name="id" value="'.$id.'">';

            print "<tr $bc[$var]>";
            print '<td colspan="3">';
            // multiprix
            if($conf->global->PRODUIT_MULTIPRICES)
            $form->select_produits('','idprod',1,$conf->product->limit_size,$object->societe->price_level);
            else
            $form->select_produits('','idprod',1,$conf->product->limit_size);
            if (! $conf->global->PRODUIT_USE_SEARCH_TO_SELECT) print '<br>';
            print '<textarea name="desc" cols="70" rows="'.ROWS_2.'"></textarea>';
            print '</td>';

            print '<td align="center"><input type="text" class="flat" size="2" name="pqty" value="1"></td>';
            print '<td align="right" nowrap><input type="text" class="flat" size="1" name="premise" value="'.$object->societe->remise_client.'">%</td>';
            print '<td align="center" colspan="2" rowspan="2"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';
            print '</tr>'."\n";

            print "<tr $bc[$var]>";
            print '<td colspan="8">';
            print $langs->trans("DateStartPlanned").' ';
            $form->select_date('',"date_start",$usehm,$usehm,1,"addline");
            print ' &nbsp; '.$langs->trans("DateEndPlanned").' ';
            $form->select_date('',"date_end",$usehm,$usehm,1,"addline");
            print '</td>';
            print '</tr>';

            print '</form>';

            $var=!$var;

            // Service libre
            print '<form name="addline_sl" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="post">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="addline">';
            print '<input type="hidden" name="mode" value="libre">';
            print '<input type="hidden" name="id" value="'.$id.'">';

            print "<tr $bc[$var]>";
            print '<td><textarea name="desc" cols="70" rows="'.ROWS_2.'"></textarea></td>';

            print '<td>';
            print $form->load_tva("tva_tx",-1,$mysoc,$object->societe);
            print '</td>';
            print '<td align="right"><input type="text" class="flat" size="4" name="pu" value=""></td>';
            print '<td align="center"><input type="text" class="flat" size="2" name="pqty" value="1"></td>';
            print '<td align="right" nowrap><input type="text" class="flat" size="1" name="premise" value="'.$object->societe->remise_client.'">%</td>';
            print '<td align="center" rowspan="2" colspan="2"><input type="submit" class="button" value="'.$langs->trans("Add").'"></td>';

            print '</tr>'."\n";

            print "<tr $bc[$var]>";
            print '<td colspan="8">';
            print $langs->trans("DateStartPlanned").' ';
            $form->select_date('',"date_start_sl",$usehm,$usehm,1,"addline_sl");
            print ' &nbsp; '.$langs->trans("DateEndPlanned").' ';
            $form->select_date('',"date_end_sl",$usehm,$usehm,1,"addline_sl");
            print '</td>';
            print '</tr>';

            print '</form>';

            print '</table>';
        }



        //print '</td><td align="center" class="tab" style="padding: 4px; border-right: 1px solid #'.$colorb.'; border-top: 1px solid #'.$colorb.'; border-bottom: 1px solid #'.$colorb.';">';

        //print '</td></tr></table>';

        print '</div>';


        /*************************************************************
         * Boutons Actions
         *************************************************************/

        if ($user->societe_id == 0)
        {
            print '<div class="tabsAction">';

            if ($object->statut == 0 && $nbofservices)
            {
                if ($user->rights->contrat->creer) print '<a class="butAction" href="fiche.php?id='.$id.'&amp;action=valid">'.$langs->trans("Validate").'</a>';
                else print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("Validate").'</a>';
            }

            if ($conf->facture->enabled && $object->statut > 0)
            {
                $langs->load("bills");
                if ($user->rights->facture->creer) print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->societe->id.'">'.$langs->trans("CreateBill").'</a>';
                else print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("CreateBill").'</a>';
            }

            if ($object->nbofservicesclosed < $nbofservices)
            {
                //if (! $numactive)
                //{
                print '<a class="butAction" href="fiche.php?id='.$id.'&amp;action=close">'.$langs->trans("CloseAllContracts").'</a>';
                //}
                //else
                //{
                //	print '<a class="butActionRefused" href="#" title="'.$langs->trans("CloseRefusedBecauseOneServiceActive").'">'.$langs->trans("Close").'</a>';
                //}
            }

            // On peut supprimer entite si
            // - Droit de creer + mode brouillon (erreur creation)
            // - Droit de supprimer
            if (($user->rights->contrat->creer && $object->statut == 0) || $user->rights->contrat->supprimer)
            {
                print '<a class="butActionDelete" href="fiche.php?id='.$id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
            }

            print "</div>";
            print '<br>';
        }

        print '<table width="100%"><tr><td width="50%" valign="top">';

        /*
         * Linked object block
         */
        $somethingshown=$object->showLinkedObjectBlock();

        print '</td><td valign="top" width="50%">';
        print '</td></tr></table>';
    }
}

$db->close();

llxFooter();
?>
