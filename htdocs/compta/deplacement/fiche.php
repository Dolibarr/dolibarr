<?php
/* Copyright (C) 2003		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin        <regis@dolibarr.fr>
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
 *  \file       	htdocs/compta/deplacement/fiche.php
 *  \brief      	Page to show a trip card
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/trip.lib.php");
require_once(DOL_DOCUMENT_ROOT."/compta/deplacement/class/deplacement.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
if ($conf->projet->enabled)
{
    require_once(DOL_DOCUMENT_ROOT."/core/lib/project.lib.php");
    require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
}

$langs->load("trips");


// Security check
$id = GETPOST('id','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'deplacement', $id,'');

$action = GETPOST('action');
$confirm = GETPOST('confirm');

$mesg = '';

$object = new Deplacement($db);


/*
 * Actions
*/
if ($action == 'validate' && $user->rights->deplacement->creer)
{
    $object->fetch($id);
    if ($object->statut == 0)
    {
        $result = $object->setStatut(1);
        if ($result > 0)
        {
            Header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
            exit;
        }
        else
        {
            $mesg=$object->error;
        }
    }
}

/*
else if ($action == 'unblock' && $user->rights->deplacement->unvalidate)
{
    $object->fetch($id);
    if ($object->fk_statut == '1') 	// Not blocked...
    {
        $mesg='<div class="error">'.$langs->trans("Error").'</div>';
        $action='';
        $error++;
    }
    else
    {
        $result = $object->fetch($id);

        $object->fk_statut	= '1';

        $result = $object->update($user);

        if ($result > 0)
        {
            Header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
            exit;
        }
        else
        {
            $mesg=$object->error;
        }
    }
}*/

else if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->deplacement->supprimer)
{
    $result=$object->delete($id);
    if ($result >= 0)
    {
        Header("Location: index.php");
        exit;
    }
    else
    {
        $mesg=$object->error;
    }
}

else if ($action == 'add' && $user->rights->deplacement->creer)
{
    if (! $_POST["cancel"])
    {
        $error=0;

        $object->date			= dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
        $object->km				= $_POST["km"];
        $object->type			= $_POST["type"];
        $object->socid			= $_POST["socid"];
        $object->fk_user		= $_POST["fk_user"];
        $object->note_private	= $_POST["note_private"];
        $object->note_public	= $_POST["note_public"];
        $object->statut     	= 0;

        if (! $object->date)
        {
            $mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date"));
            $error++;
        }
        if ($object->type == '-1') 	// Otherwise it is TF_LUNCH,...
        {
            $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type")).'</div>';
            $error++;
        }
        if (! ($object->fk_user > 0))
        {
            $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Person")).'</div>';
            $error++;
        }

        if (! $error)
        {
            $id = $object->create($user);

            if ($id > 0)
            {
                Header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
                exit;
            }
            else
            {
                $mesg=$object->error;
                $action='create';
            }
        }
        else
        {
            $action='create';
        }
    }
    else
    {
        Header("Location: index.php");
        exit;
    }
}

// Update record
else if ($action == 'update' && $user->rights->deplacement->creer)
{
    if (empty($_POST["cancel"]))
    {
        $result = $object->fetch($id);

        $object->date			= dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);
        $object->km				= $_POST["km"];
        $object->type			= $_POST["type"];
        $object->fk_user		= $_POST["fk_user"];
        $object->socid			= $_POST["socid"];
        $object->note_private	= $_POST["note_private"];
        $object->note_public	= $_POST["note_public"];

        $result = $object->update($user);

        if ($result > 0)
        {
            Header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
            exit;
        }
        else
        {
            $mesg=$object->error;
        }
    }
    else
    {
        Header("Location: " . $_SERVER["PHP_SELF"] . "?id=" . $id);
        exit;
    }
}

// Set into a project
else if ($action == 'classin' && $user->rights->deplacement->creer)
{
    $object->fetch($id);
    $result=$object->setProject($_POST['projectid']);
    if ($result < 0) dol_print_error($db, $object->error);
}

// Set fields
else if ($action == 'setdated' && $user->rights->deplacement->creer)
{
    $dated=dol_mktime($_POST['datedhour'], $_POST['datedmin'], $_POST['datedsec'], $_POST['datedmonth'], $_POST['datedday'], $_POST['datedyear']);
    $object->fetch($id);
    $result=$object->setValueFrom('dated',$dated,'','','date');
    if ($result < 0) dol_print_error($db, $object->error);
}
else if ($action == 'setkm' && $user->rights->deplacement->creer)
{
    $object->fetch($id);
    $result=$object->setValueFrom('km',GETPOST('km'));
    if ($result < 0) dol_print_error($db, $object->error);
}
else if ($action == 'setnote_public' && $user->rights->deplacement->creer)
{
    $object->fetch($id);
    $result=$object->setValueFrom('note_public',GETPOST('note_public'));
    if ($result < 0) dol_print_error($db, $object->error);
}
else if ($action == 'setnote' && $user->rights->deplacement->creer)
{
    $object->fetch($id);
    $result=$object->setValueFrom('note',GETPOST('note'));
    if ($result < 0) dol_print_error($db, $object->error);
}


/*
 * View
*/

llxHeader();

$form = new Form($db);

/*
 * Action create
*/
if ($action == 'create')
{
    print_fiche_titre($langs->trans("NewTrip"));

    dol_htmloutput_errors($mesg);

    $datec = dol_mktime(12, 0, 0, $_POST["remonth"], $_POST["reday"], $_POST["reyear"]);

    print '<form name="add" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';

    print '<table class="border" width="100%">';

    print "<tr>";
    print '<td width="25%" class="fieldrequired">'.$langs->trans("Type").'</td><td>';
    print $form->select_type_fees(GETPOST("type"),'type',1);
    print '</td></tr>';

    print "<tr>";
    print '<td class="fieldrequired">'.$langs->trans("Person").'</td><td>';
    print $form->select_users(GETPOST("fk_user"),'fk_user',1);
    print '</td></tr>';

    print "<tr>";
    print '<td class="fieldrequired">'.$langs->trans("Date").'</td><td>';
    print $form->select_date($datec?$datec:-1,'','','','','add',1,1);
    print '</td></tr>';

    // Km
    print '<tr><td class="fieldrequired">'.$langs->trans("FeesKilometersOrAmout").'</td><td><input name="km" size="10" value="' . GETPOST("km") . '"></td></tr>';

    // Company
    print "<tr>";
    print '<td>'.$langs->trans("CompanyVisited").'</td><td>';
    print $form->select_company(GETPOST('socid','int'),'socid','',1);
    print '</td></tr>';

    // Public note
    print '<tr>';
    print '<td class="border" valign="top">'.$langs->trans('NotePublic').'</td>';
    print '<td valign="top" colspan="2">';
    require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
    $doleditor=new DolEditor('note_public',GETPOST('note_public'),600,200,'dolibarr_notes','In',false,true,true,ROWS_8,100);
    print $doleditor->Create(1);
    print '</td></tr>';

    // Private note
    if (! $user->societe_id)
    {
        print '<tr>';
        print '<td class="border" valign="top">'.$langs->trans('NotePrivate').'</td>';
        print '<td valign="top" colspan="2">';
        require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
        $doleditor=new DolEditor('note_private',GETPOST('note_private'),600,200,'dolibarr_notes','In',false,true,true,ROWS_8,100);
        print $doleditor->Create(1);
        print '</td></tr>';
    }

    print '</table>';

    print '<br><center><input class="button" type="submit" value="'.$langs->trans("Save").'"> &nbsp; &nbsp; ';
    print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></center';

    print '</form>';
}
else if ($id)
{
    $result = $object->fetch($id);
    if ($result > 0)
    {
        dol_htmloutput_mesg($mesg);

        $head = trip_prepare_head($object);

        dol_fiche_head($head, 'card', $langs->trans("TripCard"), 0, 'trip');

        if ($action == 'edit' && $user->rights->deplacement->creer)
        {
            $soc = new Societe($db);
            if ($object->socid)
            {
                $soc->fetch($object->socid);
            }

            print '<form name="update" action="' . $_SERVER["PHP_SELF"] . '" method="POST">' . "\n";
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="id" value="'.$id.'">';

            print '<table class="border" width="100%">';

            // Ref
            print "<tr>";
            print '<td width="20%">'.$langs->trans("Ref").'</td><td>';
            print $object->ref;
            print '</td></tr>';

            // Type
            print "<tr>";
            print '<td class="fieldrequired">'.$langs->trans("Type").'</td><td>';
            print $form->select_type_fees($_POST["type"]?$_POST["type"]:$object->type,'type',0);
            print '</td></tr>';

            // Who
            print "<tr>";
            print '<td class="fieldrequired">'.$langs->trans("Person").'</td><td>';
            print $form->select_users($_POST["fk_user"]?$_POST["fk_user"]:$object->fk_user,'fk_user',0);
            print '</td></tr>';

            // Date
            print '<tr><td class="fieldrequired">'.$langs->trans("Date").'</td><td>';
            print $form->select_date($object->date,'','','','','update');
            print '</td></tr>';

            // Km
            print '<tr><td class="fieldrequired">'.$langs->trans("FeesKilometersOrAmout").'</td><td>';
            print '<input name="km" class="flat" size="10" value="'.$object->km.'">';
            print '</td></tr>';

            // Where
            print "<tr>";
            print '<td>'.$langs->trans("CompanyVisited").'</td><td>';
            print $form->select_company($soc->id,'socid','',1);
            print '</td></tr>';

            // Public note
            print '<tr><td valign="top">'.$langs->trans("NotePublic").'</td>';
            print '<td valign="top" colspan="3">';
            require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
            $doleditor=new DolEditor('note_public',$object->note_public,600,200,'dolibarr_notes','In',false,true,true,ROWS_8,'100');
            print $doleditor->Create(1);
            print "</td></tr>";

            // Private note
            if (! $user->societe_id)
            {
                print '<tr><td valign="top">'.$langs->trans("NotePrivate").'</td>';
                print '<td valign="top" colspan="3">';
                require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
                $doleditor=new DolEditor('note_private',$object->note_private,600,200,'dolibarr_notes','In',false,true,true,ROWS_8,'100');
                print $doleditor->Create(1);
                print "</td></tr>";
            }

            print '</table>';

            print '<br><center><input type="submit" class="button" value="'.$langs->trans("Save").'"> &nbsp; ';
            print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'">';
            print '</center>';

            print '</form>';

            print '</div>';
        }
        else
        {
            /*
             * Confirmation de la suppression du deplacement
            */
            if ($action == 'delete')
            {
                $ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$id,$langs->trans("DeleteTrip"),$langs->trans("ConfirmDeleteTrip"),"confirm_delete");
                if ($ret == 'html') print '<br>';
            }

            $soc = new Societe($db);
            if ($object->socid) $soc->fetch($object->socid);

            print '<table class="border" width="100%">';

            // Ref
            print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>';
            print $form->showrefnav($object,'id','',1,'rowid','ref','');
            print '</td></tr>';

            // Type
            print '<tr><td>';
            print $form->editfieldkey("Type",'type',$langs->trans($object->type),$object,$conf->global->MAIN_EDIT_ALSO_INLINE && $user->rights->deplacement->creer,'select:types_fees');
            print '</td><td>';
            print $form->editfieldval("Type",'type',$langs->trans($object->type),$object,$conf->global->MAIN_EDIT_ALSO_INLINE && $user->rights->deplacement->creer,'select:types_fees');
            print '</td></tr>';

            // Who
            print '<tr><td>'.$langs->trans("Person").'</td><td>';
            $userfee=new User($db);
            $userfee->fetch($object->fk_user);
            print $userfee->getNomUrl(1);
            print '</td></tr>';

            // Date
            print '<tr><td>';
            print $form->editfieldkey("Date",'dated',$object->date,$object,$conf->global->MAIN_EDIT_ALSO_INLINE && $user->rights->deplacement->creer,'datepicker');
            print '</td><td>';
            print $form->editfieldval("Date",'dated',$object->date,$object,$conf->global->MAIN_EDIT_ALSO_INLINE && $user->rights->deplacement->creer,'datepicker');
            print '</td></tr>';

            // Km/Price
            print '<tr><td valign="top">';
            print $form->editfieldkey("FeesKilometersOrAmout",'km',$object->km,$object,$conf->global->MAIN_EDIT_ALSO_INLINE && $user->rights->deplacement->creer,'numeric:6');
            print '</td><td>';
            print $form->editfieldval("FeesKilometersOrAmout",'km',$object->km,$object,$conf->global->MAIN_EDIT_ALSO_INLINE && $user->rights->deplacement->creer,'numeric:6');
            print "</td></tr>";

            // Where
            print '<tr><td>'.$langs->trans("CompanyVisited").'</td>';
            print '<td>';
            if ($soc->id) print $soc->getNomUrl(1);
            print '</td></tr>';

            // Project
            if ($conf->projet->enabled)
            {
                $langs->load('projects');
                print '<tr>';
                print '<td>';

                print '<table class="nobordernopadding" width="100%"><tr><td>';
                print $langs->trans('Project');
                print '</td>';
                if ($action != 'classify' && $user->rights->deplacement->creer)
                {
                    print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classify&amp;id='.$object->id.'">';
                    print img_edit($langs->trans('SetProject'),1);
                    print '</a></td>';
                }
                print '</tr></table>';
                print '</td><td colspan="3">';
                if ($action == 'classify')
                {
                    $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project,'projectid');
                }
                else
                {
                    $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project,'none');
                }
                print '</td>';
                print '</tr>';
            }

            // Statut
            print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';

            print "</table><br>";

            // Notes
            $blocname = 'notes';
            $title = $langs->trans('Notes');
            include(DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php');

            print '</div>';

            /*
             * Barre d'actions
            */

            print '<div class="tabsAction">';

            if ($object->statut == 0) 	// if blocked...
            {
                if ($user->rights->deplacement->creer)
                {
                    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=validate&id='.$id.'">'.$langs->trans('Validate').'</a>';
                }
                else
                {
                    print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Validate').'</a>';
                }
            }

            if ($user->rights->deplacement->creer)
            {
                print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&id='.$id.'">'.$langs->trans('Modify').'</a>';
            }
            else
            {
                print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Modify').'</a>';
            }
            if ($user->rights->deplacement->supprimer)
            {
                print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
            }
            else
            {
                print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('Delete').'</a>';
            }

            print '</div>';
        }
    }
    else
    {
        dol_print_error($db);
    }
}

$db->close();

llxFooter();
?>
