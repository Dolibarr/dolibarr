<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *        \file       resource/list_placement.php
 *        \ingroup    resource
 *        \brief      Page to list resource placements
 */


require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/resourceplacement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/resource.lib.php';
if (! empty($conf->projet->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

// Load traductions files required by page
$langs->load("resource");
$langs->load("companies");
$langs->load('orders');
$langs->load('bills');
$langs->load("products");
$langs->load("other");

// Get parameters
$id         = GETPOST('id','int');
$socid      = GETPOST('socid','int');
$action     = GETPOST('action','alpha');
$selected   = GETPOST('placements_selection');
if (!is_array($selected)) $selected = array();
$project_id = GETPOST('project_id','int')?GETPOST('project_id','int'):0;
$service_id = GETPOST('service_id','int')?GETPOST('service_id','int'):0;

$search_ref_client = GETPOST('search_ref_client','alpha');
$search_soc        = GETPOST('search_soc','alpha');

$optioncss = GETPOST('optioncss','alpha');

// Load variable for pagination
$limit = GETPOST("limit")?GETPOST("limit","int"):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="rowid"; // Set here default search field
if (! $sortorder) $sortorder="ASC";

// Protection if external user
if ($user->socid > 0)
{
    accessforbidden();
}
if( ! $user->rights->resource->read || ! $user->rights->resource->placement_read )
{
    accessforbidden();
}

// Load object
$resource = new Dolresource($db);
if ($id > 0)
{
    $result=$resource->fetch($id);
    if (!$result) {
        dol_print_error($db, $resource->error);
    }
}

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('resource', 'resource_placement_list'));

// Definition of fields for list
$arrayfields=array(
    'id'=>array('label'=>$langs->trans("Id"), 'checked'=>1),
    'ref_client'=>array('label'=>$langs->trans("RefCustomer"), 'checked'=>1),
    'soc'=>array('label'=>$langs->trans("Customer"), 'checked'=>1),
    'date_creation'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>1, 'position'=>500),
    'date_start'=>array('label'=>$langs->trans("DateStart"), 'checked'=>1, 'position'=>500),
    'date_end'=>array('label'=>$langs->trans("DateEnd"), 'checked'=>1, 'position'=>500),
    'user'=>array('label'=>$langs->trans("Author"), 'checked'=>1),
);

/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // All test are required to be compatible with all browsers
{
    $search_ref_client='';
    $search_soc='';
    $search_array_options=array();
}

if (empty($reshook))
{
    if ($action)
    {
        if (empty($socid))
        {
            $action = '';
        }
    }

    if ($action == 'generate_bill' && $user->rights->facture->creer)
    {
        $object=new Facture($db);
        $object->socid = $socid;
        $db->begin();
        $error=0;
        $facture_id=0;

        if (empty($service_id))
        {
            setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Service")), null, 'errors');
            $error++;
        }

        // Standard or deposit or proforma invoice
        if ($_POST['type'] == 0 && !$error)
        {
            $datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
            if (empty($datefacture))
            {
                $datefacture = dol_mktime(date("h"), date("M"), 0, date("m"), date("d"), date("Y"));
            }

            $object->type               = $_POST['type'];
            $object->date               = $datefacture;
            $object->note_public        = trim($_POST['note_public']);
            $object->note               = trim($_POST['note']);
            $object->modelpdf           = $_POST['model'];
            $object->fk_project         = $project_id;
            $object->cond_reglement_id  = ($_POST['type'] == 3?1:$_POST['cond_reglement_id']);
            $object->mode_reglement_id  = $_POST['mode_reglement_id'];

            $facture_id = $object->create($user);
            if ($facture_id > 0)
            {
                $result=$object->fetch_thirdparty();
                if ($result < 0)
                {
                    setEventMessages($object->thirdparty->error, $object->thirdparty->errors, 'errors');
                    $error++;
                }

                $service = new Product($db);
                $result=$service->fetch($service_id);
                if ($result > 0 && !$error)
                {
                    // Prices fields
                    $tva_tx = get_default_tva($mysoc, $object->thirdparty, $service->id);
                    $tva_npr = get_default_npr($mysoc, $object->thirdparty, $service->id);
                    $pu_ht = $service->price;
                    $pu_ttc = $service->price_ttc;
                    $price_base_type = $service->price_base_type;
                    if ($tva_tx != $service->tva_tx) {
                        if ($price_base_type != 'HT') {
                            $pu_ht = price2num($pu_ttc / (1 + ($tva_tx / 100)), 'MU');
                        } else {
                            $pu_ttc = price2num($pu_ht * (1 + ($tva_tx / 100)), 'MU');
                        }
                    }
                    $localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty);
                    $localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty);

                    foreach ($selected as $i)
                    {
                        //Generate a line per each placement
                        $resplacement = new ResourcePlacement($db);
                        $result=$resplacement->fetch($i);
                        if ($result > 0)
                        {
                            $desc = empty($resplacement->ref_client)?"":$langs->trans("RefCustomer").": ".$resplacement->ref_client;
                            $desc = dol_concatdesc($service->description, $desc);
                            $result = $object->addline(
                                $desc,
                                $pu_ht,
                                1, //$qty
                                $tva_tx,
                                $localtax1_tx,
                                $localtax2_tx,
                                $service_id,
                                0, //$remise_percent,
                                $resplacement->date_start,
                                $resplacement->date_end,
                                0,
                                0,
                                '',
                                $price_base_type,
                                $pu_ttc
                            );
                            if ($result <= 0)
                            {
                                setEventMessages($object->error, $object->errors, 'errors');
                                $error++;
                                break;
                            }
                        }
                        else
                        {
                            setEventMessages($resplacement->error, $resplacement->errors, 'errors');
                            $error++;
                            break;
                        }
                    }
                }
                else
                {
                    setEventMessages($service->error, $service->errors, 'errors');
                    $error++;
                }
            }
        }

        // Check if object was created
        if ($facture_id <= 0)
        {
            setEventMessages($object->error, $object->errors, 'errors');
            $error++;
        }

        // We try to delete the placements and occupy their sections
        if (!$error)
        {
            //We delete the used placements
            foreach ($selected as $i) {
                $resplacement = new ResourcePlacement($db);
                $result = $resplacement->fetch($i);
                if ($result > 0) {
                    $result = $resource->switchResource($user, $resplacement->date_start, $resplacement->date_end, ResourceStatus::OCCUPIED, $resplacement->id, $resplacement->element, $object->id, $object->element, true);
                    if ($result < 0)
                    {
                        setEventMessages($resource->error, $resource->errors, 'errors');
                        $error++;
                    }
                    else
                    {
                        $result = $resplacement->delete($user);
                        if ($result < 0)
                        {
                            setEventMessages($resplacement->error, $resplacement->errors, 'errors');
                            $error++;
                        }
                    }
                }
            }
        }

        // End of object creation, we show it
        if (! $error)
        {
            $db->commit();
            header('Location: '.DOL_URL_ROOT.'/compta/facture.php?facid='.$facture_id);
            exit;
        }
        else
        {
            $db->rollback();
            $action='prepare_bill';
        }
    }

    if ($action == 'prepare_bill')
    {
        if (empty($selected))
        {
            $action = 'to_bill';
        }
    }
}

/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

$form = new Form($db);
$soc = new Societe($db);
$placement = new ResourcePlacement($db);
$userstatic = new User($db);

if ($resource->id)
{
    if ($action == 'prepare_bill')
    {
        $title = $langs->trans('NewBill');
        llxHeader('',$title,'');
        print load_fiche_titre($title);

        $res=$soc->fetch($socid);
        if ($res < 0) dol_print_error($db, $soc->error, $soc->errors);
        $cond_reglement_id = (!isset($_POST['cond_reglement_id']))?$soc->cond_reglement_id:$_POST['cond_reglement_id'];
        $mode_reglement_id = (!isset($_POST['mode_reglement_id']))?$soc->mode_reglement_id:$_POST['mode_reglement_id'];

        $absolute_discount=$soc->getAvailableDiscounts();
        print '<form method="POST" id="generateForm" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'&amp;action=generate_bill">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="socid" value="'.$socid.'">';

        dol_fiche_head();

        print '<table class="border" width="100%">';

        // Ref
        print '<tr><td class="fieldrequired" style="width:25%">'.$langs->trans('Ref').'</td><td colspan="2">'.$langs->trans('Draft').'</td></tr>';

        // Third party
        print '<tr><td class="fieldrequired">'.$langs->trans('Customer').'</td><td colspan="2">';
        print $soc->getNomUrl(1);
        print '</td>';
        print '</tr>'."\n";

        // Type
        print '<tr><td valign="top" class="fieldrequired">'.$langs->trans('Type').'</td><td colspan="2">';
        print '<table class="nobordernopadding">'."\n";

        // Standard invoice
        print '<tr height="18"><td width="16px" valign="middle">';
        print '<input type="radio" name="type" value="0"'.(GETPOST('type')==0?' checked':'').'>';
        print '</td><td valign="middle">';
        $desc=$form->textwithpicto($langs->trans("InvoiceStandardAsk"),$langs->transnoentities("InvoiceStandardDesc"),1);
        print $desc;
        print '</td></tr>'."\n";
        print '</table>';

        // Date invoice
        print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td colspan="2">';
        $form->select_date('','','','','',"add",1,1);
        print '</td></tr>';

        // Payment term
        print '<tr><td class="nowrap">'.$langs->trans('PaymentConditionsShort').'</td><td colspan="2">';
        $form->select_conditions_paiements($cond_reglement_id,'cond_reglement_id');
        print '</td></tr>';

        // Payment mode
        print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="2">';
        $form->select_types_paiements($mode_reglement_id,'mode_reglement_id');
        print '</td></tr>';

        // Project
        if (! empty($conf->projet->enabled))
        {
            $formproject=new FormProjets($db);

            $langs->load('projects');
            print '<tr><td>'.$langs->trans('Project').'</td><td colspan="2">';
            $formproject->select_projects($soc->id, $project_id, 'project_id');
            print '</td></tr>';
        }

        // Modele PDF
        print '<tr><td>'.$langs->trans('Model').'</td>';
        print '<td>';
        include_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
        $liste=ModelePDFFactures::liste_modeles($db);
        print $form->selectarray('model',$liste,$conf->global->FACTURE_ADDON_PDF);
        print "</td></tr>";

        // Public note
        print '<tr>';
        print '<td class="border" valign="top">'.$langs->trans('NotePublic').'</td>';
        print '<td valign="top" colspan="2">';
        print '<textarea name="note_public" wrap="soft" cols="70" rows="'.ROWS_3.'">';

        print $langs->trans("Placements").": ".implode(', ', $selected);

        print '</textarea></td></tr>';

        // Private note
        if (empty($user->societe_id))
        {
            print '<tr>';
            print '<td class="border" valign="top">'.$langs->trans('NotePrivate').'</td>';
            print '<td valign="top" colspan="2">';
            print '<textarea name="note" wrap="soft" cols="70" rows="'.ROWS_3.'">';

            print '</textarea></td></tr>';
        }

        print '</table><br>';

        print load_fiche_titre($langs->trans("Placements"), "", "");
        print '<table class="border" width="100%">';

        //Service selector
        print '<tr><td style="width:25%">'.$langs->trans('ServiceForResource', $resource->getNomUrl(1)).'</td><td colspan="2">';
        $reslink = new ResourceLink($db);
        $elements = $reslink->getElementLinked($resource->id, '', 'service', true);
        $element_list = array();
        if (is_array($elements))
        {
            $maxlengtharticle=(empty($conf->global->PRODUCT_MAX_LENGTH_COMBO)?48:$conf->global->PRODUCT_MAX_LENGTH_COMBO);
            foreach ($elements as $element_id => $element_type)
            {
                $element = fetchObjectByElement($element_id, $element_type);
                if (is_object($element) || $element->id != $element_id)
                {
                    //Check if has sell status
                    if ($element->status != 1) continue;
                    $label = $element->ref.' - '.dol_trunc($element->label, $maxlengtharticle);
                    $element_list[$element_id] = $label;
                }
            }
        }
        else
        {
            dol_print_error($db, $reslink->error." -> ".$elements, $reslink->errors);
        }
        print $form->selectarray('service_id', $element_list, $service_id);
        print '</td></tr>';

        print '</table>';

        foreach ($selected as $i)
        {
            print '<input type="hidden" name="placements_selection[]" value="'.$i.'">';
        }

        dol_fiche_end();

        // Button "Create Draft"
        print '<div class="center">';
        print '<input type="submit" class="button" name="button" value="'.$langs->trans('CreateDraft').'" />';
        print '<div class="inline-block divButAction">';
        print '<a href="./list_placement.php?id='.$resource->id.'" class="butAction">'.$langs->trans('Cancel').'</a>';
        print '</div>';
        print '</div>';
        print "</form>\n";

        print '</td></tr>';
        print "</table>\n";
    }
    else if (empty($action) || $action=='to_bill')
    {
        $title = $langs->trans('ResourcePlacement');
        llxHeader('',$title,'');
        $head=resource_prepare_head($resource);
        dol_fiche_head($head, 'placement', $langs->trans("ResourceSingular"),0,'resource@resource');

        /*
         * View object
         */
        print '<table width="100%" class="border">';
        print '<tr><td style="width:35%">'.$langs->trans("ResourceFormLabel_ref").'</td><td>';
        print '<div class="inline-block floatleft refid">'.$resource->ref.'</div>';
        print '</td></tr>';
        print '</table>';
        print '</div>';

        /*
         * List placements
         */
        $sql = "SELECT";
        $sql.= " p.rowid,";
        $sql.= " p.ref_client,";
        $sql.= " p.fk_soc,";
        $sql.= " p.fk_resource,";
        $sql.= " p.fk_user,";
        $sql.= " p.date_creation,";
        $sql.= " p.date_start,";
        $sql.= " p.date_end,";
        $sql.= " s.code_client,";
        $sql.= " s.nom as name_client,";
        $sql.= " s.client";
        $sql.= " FROM ".MAIN_DB_PREFIX."resource_placement as p";
        $sql.= ", ".MAIN_DB_PREFIX."societe as s";
        $sql.= ", ".MAIN_DB_PREFIX."resource as r";
        $sql.= " WHERE p.fk_resource = ".$resource->id;
        $sql.= ' AND p.fk_soc = s.rowid';
        $sql.= ' AND p.fk_resource = r.rowid';
        $sql.= ' AND p.entity IN ('.getEntity('resource', 1).')';
        $sql.= " AND s.entity IN (".getEntity('societe', 1).")";
        if ($action == "to_bill" && $socid)
        {
            $sql.= ' AND p.fk_soc = '.$socid;
        }
        else
        {
            if ($search_ref_client)  $sql.= natural_search("p.ref_client",$search_ref_client);
            if ($search_soc)         $sql.= natural_search("s.nom",$search_soc);
            if ($sall)               $sql.= natural_search(array_keys($fieldstosearchall), $sall);
        }

        $no_search = empty($search_ref_client) && empty($search_soc) && empty($sall);

        // Add where from hooks
        $parameters=array();
        $reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
        $sql.=$hookmanager->resPrint;

        $sql.=$db->order($sortfield,$sortorder);
        $sql.= $db->plimit($limit+1, $offset);
        $resql=$db->query($sql);

        $num = $resql ? $db->num_rows($resql) : -1;
        if ($num < 0)
        {
            dol_print_error($db, $resql);
        }
        else if ($num == 0 && $no_search)
        {
            print '<div class="warning">'.$langs->trans('ResourcePlacementNone').'</div>';
        }
        else
        {
            $param = '&amp;id='.$id;

            if ($search_ref_client != '') $param.= '&amp;search_ref_client='.urlencode($search_ref_client);
            if ($search_soc != '') $param.= '&amp;search_soc='.urlencode($search_soc);
            if ($optioncss != '') $param.='&amp;optioncss='.$optioncss;

            if ($action == 'to_bill') {
                print load_fiche_titre($langs->trans("SelectPlacementsToBill"), '', '');
                print '<form method="POST" id="prepareForm" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'&amp;socid='.$socid.'&amp;action=prepare_bill">';
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            }
            else
            {
                print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'">';
                if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
                print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
                print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
                $varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
                $selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);    // This also change content of $arrayfields
            }

            if ($sall)
            {
                foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
                print $langs->trans("FilterOnInto", $sall) . join(', ',$fieldstosearchall);
                $sortfield = "";
                $sortorder = "";
            }

            if (! empty($moreforfilter))
            {
                print '<div class="liste_titre liste_titre_bydiv centpercent">';
                print $moreforfilter;
                $parameters=array();
                $reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
                print $hookmanager->resPrint;
                print '</div>';
            }

            print '<table class="liste '.($moreforfilter?"listwithfilterbefore":"").'">';

            // Fields title
            print '<tr class="liste_titre">';

            $sortable = empty($action);
            if (! empty($arrayfields['id']['checked'])) print_liste_field_titre($arrayfields['id']['label'],$_SERVER['PHP_SELF'],$sortable?'id':'','',$param,'',$sortfield,$sortorder);
            if (! empty($arrayfields['ref_client']['checked'])) print_liste_field_titre($arrayfields['ref_client']['label'],$_SERVER['PHP_SELF'],$sortable?'ref_client':'','',$param,'',$sortfield,$sortorder);
            if (! empty($arrayfields['soc']['checked'])) print_liste_field_titre($arrayfields['soc']['label'],$_SERVER['PHP_SELF'],$sortable?'name_client':'','',$param,'',$sortfield,$sortorder);

            // Hook fields
            $parameters=array('arrayfields'=>$arrayfields);
            $reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;

            if (! empty($arrayfields['date_creation']['checked']))  print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],$sortable?'date_creation':'',"",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
            if (! empty($arrayfields['date_start']['checked']))  print_liste_field_titre($langs->trans("DateStart"),$_SERVER["PHP_SELF"],$sortable?'date_start':'',"",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
            if (! empty($arrayfields['date_end']['checked']))  print_liste_field_titre($langs->trans("DateEnd"),$_SERVER["PHP_SELF"],$sortable?'date_end':'',"",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
            if (! empty($arrayfields['user']['checked']))  print_liste_field_titre($langs->trans("Author"),$_SERVER["PHP_SELF"],$sortable?'fk_user':'',"",$param,'align="center" class="nowrap"',$sortfield,$sortorder);

            print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
            print '</tr>'."\n";

            // Fields title search
            if (empty($action)) {
                print '<tr class="liste_titre">';

                if (! empty($arrayfields['id']['checked'])) print '<td class="liste_titre"></td>';
                if (! empty($arrayfields['ref_client']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_ref_client" value="'.$search_ref_client.'" size="10"></td>';
                if (! empty($arrayfields['soc']['checked'])) print '<td class="liste_titre"><input type="text" class="flat" name="search_soc" value="'.$search_soc.'" size="10"></td>';

                // Fields from hook
                $parameters=array('arrayfields'=>$arrayfields);
                $reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
                print $hookmanager->resPrint;

                if (! empty($arrayfields['date_creation']['checked'])) print '<td class="liste_titre"></td>';
                if (! empty($arrayfields['date_start']['checked'])) print '<td class="liste_titre"></td>';
                if (! empty($arrayfields['date_end']['checked'])) print '<td class="liste_titre"></td>';
                if (! empty($arrayfields['user']['checked'])) print '<td class="liste_titre"></td>';

                // Action column
                print '<td class="liste_titre" align="right">';
                print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
                print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
                print '</td>';

                print '</tr>'."\n";
            }

            $i = 0;
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                if ($obj)
                {
                    print '<tr>';

                    // Id
                    if (! empty($arrayfields['id']['checked']))
                    {
                        $placement->id=$obj->rowid;
                        print '<td>';
                        print $placement->getNomUrl(1);
                        print '</td>';
                    }

                    // Ref client
                    if (! empty($arrayfields['ref_client']['checked'])) print '<td>'.$obj->ref_client.'</td>';

                    // Client
                    if (! empty($arrayfields['soc']['checked']))
                    {
                        $soc->id=$obj->fk_soc;
                        $soc->code_client = $obj->code_client;
                        $soc->name=$obj->name_client;
                        $soc->client=$obj->client;
                        print '<td>';
                        print $soc->getNomUrl(1,'customer');
                        print '</td>';
                    }

                    // Fields from hook
                    $parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
                    $reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
                    print $hookmanager->resPrint;

                    // Date creation
                    if (! empty($arrayfields['date_creation']['checked']))
                    {
                        print '<td align="center">';
                        print dol_print_date($db->jdate($obj->date_creation), 'dayhour');
                        print '</td>';
                    }

                    // Date start
                    if (! empty($arrayfields['date_start']['checked']))
                    {
                        print '<td align="center">';
                        print dol_print_date($db->jdate($obj->date_start), 'dayhour');
                        print '</td>';
                    }

                    // Date end
                    if (! empty($arrayfields['date_end']['checked']))
                    {
                        print '<td align="center">';
                        print dol_print_date($db->jdate($obj->date_end), 'dayhour');
                        print '</td>';
                    }

                    // User
                    if (! empty($arrayfields['user']['checked']))
                    {
                        $userstatic->fetch($obj->fk_user);
                        print '<td>';
                        print $userstatic->getNomUrl(1);
                        print '</td>';
                    }

                    // Action column
                    print '<td align="right">';
                    if ($action == 'to_bill')
                    {
                        // Checkbox
                        print '<input class="flat checkformerge" type="checkbox" name="placements_selection[]"';
                        print (in_array($obj->rowid, $selected)?"checked":"").' value="'.$obj->rowid.'">';
                    }
                    else
                    {
                        // Bill conversion
                        if (! empty($conf->facture->enabled) && $user->rights->facture->creer)
                        {
                            print '<a href="./list_placement.php?id='.$obj->fk_resource.'&amp;socid='.$obj->fk_soc;
                            print '&amp;placements_selection[]='.$obj->rowid.'&amp;action=to_bill">';
                            print img_picto($langs->trans("CreateBill"), 'object_bill').'</a>';
                        }
                        print '&nbsp;';
                        // Delete
                        print '<a href="./placement.php?id='.$obj->rowid.'&amp;action=delete">';
                        print img_delete();
                        print '</a>';
                    }
                    print '&nbsp;&nbsp;';
                    print '</td>';
                    print '</tr>';
                }
                $i++;
            }

            $db->free($resql);

            $parameters=array('sql' => $sql);
            $reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;

            print "</table>\n";

            // Buttons
            print '<div class="tabsAction">'."\n";
            $parameters=array();
            $reshook=$hookmanager->executeHooks('addMoreActionsButtons',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

            if (empty($reshook))
            {
                if ($action == 'to_bill') {
                    print '<input type="submit" class="butAction" value="'.$langs->trans("CreateBill").'">';
                    print '<div class="inline-block divButAction">';
                    print '<a href="./list_placement.php?id='.$resource->id.'" class="butAction">'.$langs->trans('Cancel').'</a>';
                    print '</div>';
                }
            }
            print "</form>\n";
            print '</div>'."\n";
        }
    }
}
else
{
    dol_print_error($db, $resource->error);
}

// End of page
llxFooter();
$db->close();
