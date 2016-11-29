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
 *        \file       resource/placement.php
 *        \ingroup    resource
 *        \brief      Page to manage resource placement
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/resourceplacement.class.php';
include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/resource.lib.php';

// Load traductions files required by page
$langs->load('bills');
$langs->load("other");

// Get parameters
$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');

// Protection if external user
if ($user->socid > 0)
{
    accessforbidden();
}
if( ! $user->rights->resource->read || ! $user->rights->resource->placement_read )
{
    accessforbidden();
}

// Load objects
$object = new ResourcePlacement($db);
$soc = new Societe($db);
$resource = new Dolresource($db);
$userobj = new User($db);
if (($id > 0 || ! empty($ref)) && $action != 'add')
{
    $result=$object->fetch($id,$ref);
    if ($result <= 0) dol_print_error($db);
    $soc->fetch($object->fk_soc);
    $resource->fetch($object->fk_resource);
    $userobj->fetch($object->fk_user);
}

// Initialize technical object to manage hooks of modules. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('resource', 'resource_placement'));

/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    // Update ref client
    if ($action == 'set_ref_client' && $user->rights->resource->placement_write) {
        $object->ref_client = GETPOST('ref_client');
        $object->update($user);
    }

    // Action to delete
    if ($action == 'confirm_delete' && $user->rights->resource->placement_delete)
    {
        if ($confirm == 'yes') {
            //Release the occupied sections
            $error = 0;
            $result = $resource->freeResource($user, $object->date_start, $object->date_end, null, ResourceStatus::OCCUPIED, $object->id, $object->element);
            if ($result < 0)
            {
                setEventMessages($resource->error, $resource->errors, 'errors');
                $error++;
            }
            //Delete if no error occurred
            if (!$error)
            {
                $result = $object->delete($user);
                if ($result > 0)
                {
                    Header("Location: list_placement.php?id=".$object->fk_resource);
                    exit;
                }
                else
                {
                    setEventMessages($object->error, $object->errors, 'errors');
                }
            }
        }
        else
        {
            $action='';
        }
    }
}


/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

$title = $langs->trans('ResourcePlacement');
llxHeader('',$title,'');

$form=new Form($db);

// Part to show record
if ($id)
{
    $head=resource_prepare_head($resource);
    dol_fiche_head($head, 'placement', $langs->trans("ResourceSingular"),0,'resource@resource');

    if ($action == 'delete') {
        $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteResourcePlacement'), $langs->trans('ConfirmDeleteResourcePlacement'), 'confirm_delete', '', 0, 1);
        print $formconfirm;
    }

    print '<table class="border" width="100%">';

    //Resource ref
    print '<tr><td style="width:30%">'.$langs->trans("ResourceFormLabel_ref").'</td><td>'.$resource->ref;
    print '<div class="inline-block floatright">';
    print '<a href="list_placement.php?id='.$object->fk_resource.'">';
    print $langs->trans("BackToList");
    print '</a></div>';
    print '</td></tr>';

    // Ref customer
    print '<tr><td>';
    print '<table class="nobordernopadding" width="100%"><tr><td class="nowrap">';
    print $langs->trans('RefCustomer') . '</td><td align="left">';
    print '</td>';
    if ($action != 'refcustomer')
        print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=refcustomer&amp;id='.$object->id.'">'.img_edit($langs->trans('Modify')).'</a></td>';
    print '</tr></table>';
    print '</td><td>';
    if ($user->rights->resource->placement_write && $action == 'refcustomer') {
        print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
        print '<input type="hidden" name="token" value="'.$_SESSION ['newtoken'].'">';
        print '<input type="hidden" name="action" value="set_ref_client">';
        print '<input type="text" class="flat" size="20" name="ref_client" value="'.$object->ref_client.'">';
        print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
        print '</form>';
    } else {
        print $object->ref_client;
    }
    print '</td></tr>';

    // The rest
    print '<tr><td style="width:30%">'.$langs->trans("Customer").'</td><td>'.$soc->getNomUrl(1).'</td></tr>';
    print '<tr><td style="width:30%">'.$langs->trans("DateCreation").'</td><td>'.dol_print_date($object->date_creation, 'daytext').'</td></tr>';
    print '<tr><td style="width:30%">'.$langs->trans("DateStart").'</td><td>'.dol_print_date($object->date_start, 'dayhourtext').'</td></tr>';
    print '<tr><td style="width:30%">'.$langs->trans("DateEnd").'</td><td>'.dol_print_date($object->date_end, 'dayhourtext').'</td></tr>';
    print '<tr><td style="width:30%">'.$langs->trans("Author").'</td><td>'.$userobj->getNomUrl(1).'</td></tr>';
    print '</table>';

    dol_fiche_end();

    // Buttons
    print '<div class="tabsAction">'."\n";
    $parameters=array();
    $reshook=$hookmanager->executeHooks('addMoreActionsButtons',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
    if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

    if (empty($reshook))
    {
        print '<div class="inline-block divButAction">';
        print '<a href="./list_placement.php?id='.$object->fk_resource.'&amp;socid='.$object->fk_soc;
        print '&amp;placements_selection[]='.$object->id.'&amp;action=to_bill" class="butAction">'.$langs->trans('CreateBill').'</a>';
        print '</div>';
        if ($user->rights->resource->placement_delete)
        {
            print '<div class="inline-block divButAction">';
            print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
            print '</div>'."\n";
        }
    }
    print '</div>'."\n";
}


// End of page
llxFooter();
$db->close();
