<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *      \file       htdocs/comm/address.php
 *      \ingroup    societe
 *      \brief      Tab address of thirdparty
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/address.class.php';

$langs->load("companies");
$langs->load("commercial");

$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');
$backtopage	= GETPOST('backtopage','alpha');
$origin		= GETPOST('origin','alpha');
$originid	= GETPOST('originid','int');
$socid		= GETPOST('socid','int');
if (! $socid && ($action != 'create' && $action != 'add' && $action != 'update')) accessforbidden();

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);

$object = new Address($db);


/*
 * Actions
 */

// Cancel
if (GETPOST("cancel") && ! empty($backtopage))
{
	header("Location: ".$backtopage);
	exit;
}

if ($action == 'add' || $action == 'update')
{
    $object->socid		= $socid;
    $object->label		= ($_POST["label"]!=$langs->trans('RequiredField')?$_POST["label"]:'');
    $object->name		= ($_POST["name"]!=$langs->trans('RequiredField')?$_POST["name"]:'');
    $object->address	= $_POST["address"];
    $object->zip		= $_POST["zipcode"];
    $object->town		= $_POST["town"];
    $object->country_id = $_POST["country_id"];
    $object->phone		= $_POST["phone"];
    $object->fax		= $_POST["fax"];
    $object->note		= $_POST["note"];

    // Add new address
    if ($action == 'add')
    {
        $result	= $object->create($socid, $user);

        if ($result >= 0)
        {
        	if (! empty($backtopage))
        	{
        		header("Location: ".$backtopage);
        		exit;
        	}
            else if ($origin == 'commande')
            {
                header("Location: ../commande/contact.php?action=editdelivery_adress&socid=".$socid."&id=".$originid);
                exit;
            }
            elseif ($origin == 'propal')
            {
                header("Location: ../comm/propal/contact.php?action=editdelivery_adress&socid=".$socid."&id=".$originid);
                exit;
            }
            elseif ($origin == 'shipment')
            {
            	header("Location: ../expedition/card.php?id=".$originid);
            	exit;
            }
            else
            {
                header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid);
                exit;
            }
        }
        else
        {
	        setEventMessages($object->error, $object->errors, 'errors');
            $action='create';
        }
    }

    // Update address
    else if ($action == 'update')
    {
        $result 	= $object->update($id, $socid, $user);

        if ($result >= 0)
        {
        	if (! empty($backtopage))
        	{
        		header("Location: ".$backtopage);
        		exit;
        	}
            else if ($origin == 'commande')
            {
                header("Location: ../commande/contact.php?id=".$originid);
                exit;
            }
            elseif ($origin == 'propal')
            {
                header("Location: ../comm/propal/contact.php?id=".$originid);
                exit;
            }
            elseif ($origin == 'shipment')
            {
                header("Location: ../expedition/card.php?id=".$originid);
                exit;
            }
            else
            {
                header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid);
                exit;
            }
        }
        else
        {
            $reload = 0;
	        setEventMessages($object->error, $object->errors, 'errors');
            $action= "edit";
        }
    }

}

else if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->societe->supprimer)
{
    $result = $object->delete($id, $socid);

    if ($result == 0)
    {
        header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid);
        exit ;
    }
    else
    {
        $reload = 0;
        $action='';
    }
}

/**
 *
 *
 */

llxHeader();

$form = new Form($db);
$formcompany = new FormCompany($db);
$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

if ($action == 'create')
{
    if ($user->rights->societe->creer)
    {
        /*
         * Creation
         */

        if ($_POST["label"] && $_POST["name"])
        {
            $object->socid		=	$socid;
            $object->label		=	$_POST["label"];
            $object->name		=	$_POST["name"];
            $object->address	=	$_POST["address"];
            $object->zip		=	$_POST["zipcode"];
            $object->town		=	$_POST["town"];
            $object->phone		=	$_POST["phone"];
            $object->fax		=	$_POST["fax"];
            $object->note		=	$_POST["note"];
        }

        // On positionne country_id, country_code and label of the chosen country
        $object->country_id = (GETPOST('country_id','int') ? GETPOST('country_id','int') : $mysoc->country_id);
        if ($object->country_id)
        {
        	$tmparray=getCountry($object->country_id,'all');
            $object->country_code	= $tmparray['code'];
            $object->country		= $tmparray['label'];
        }

        print load_fiche_titre($langs->trans("AddAddress"));

        print "<br>\n";

        // If javascript enabled, we add interactivity on mandatory fields
        if ($conf->use_javascript_ajax)
        {
            print "\n".'<script type="text/javascript" language="javascript">';
            print '$(document).ready(function () {
                        $("#label").focus(function() {
                            hideMessage("label","'.$langs->trans('RequiredField').'");
                        });
                        $("#label").blur(function() {
                            displayMessage("label","'.$langs->trans('RequiredField').'");
                        });
                        $("#name").focus(function() {
                            hideMessage("name","'.$langs->trans('RequiredField').'");
                        });
                        $("#name").blur(function() {
                            displayMessage("name","'.$langs->trans('RequiredField').'");
                        });
                        displayMessage("label","'.$langs->trans('RequiredField').'");
                        displayMessage("name","'.$langs->trans('RequiredField').'");
                        $("#label").css("color","grey");
                        $("#name").css("color","grey");
                    })';
            print '</script>'."\n";
        }

        print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" name="formsoc">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'"/>';
        print '<input type="hidden" name="socid" value="'.$socid.'"/>';
        print '<input type="hidden" name="backtopage" value="'.$backtopage.'"/>';
        print '<input type="hidden" name="origin" value="'.$origin.'"/>';
        print '<input type="hidden" name="originid" value="'.$originid.'"/>';
        print '<input type="hidden" name="action" value="add"/>';

        print '<table class="border" width="100%">';

        print '<tr><td class="fieldrequired">'.$langs->trans('Label').'</td><td><input type="text" size="30" name="label" id="label" value="'.($object->label?$object->label:$langs->trans('RequiredField')).'"></td></tr>';
        print '<tr><td class="fieldrequired">'.$langs->trans('Name').'</td><td><input type="text" size="30" name="name" id="name" value="'.($object->name?$object->name:$langs->trans('RequiredField')).'"></td></tr>';

        print '<tr><td class="tdtop">'.$langs->trans('Address').'</td><td colspan="3"><textarea name="address" class="quatrevingtpercent" rows="3" wrap="soft">';
        print $object->address;
        print '</textarea></td></tr>';

        // Zip
        print '<tr><td>'.$langs->trans('Zip').'</td><td>';
        print $formcompany->select_ziptown($object->zip,'zipcode',array('town','selectcountry_id'),6);
        print '</td></tr>';

        // Town
        print '<tr><td>'.$langs->trans('Town').'</td><td>';
        print $formcompany->select_ziptown($object->town,'town',array('zipcode','selectcountry_id'));
        print '</td></tr>';

        print '<tr><td width="25%">'.$langs->trans('Country').'</td><td colspan="3">';
        print $form->select_country($object->country_id,'selectcountry_id');
        print '</td></tr>';

        print '<tr><td>'.$langs->trans('Phone').'</td><td><input type="text" name="phone" value="'.$object->phone.'"></td></tr>';

        print '<tr><td>'.$langs->trans('Fax').'</td><td><input type="text" name="fax" value="'.$object->fax.'"></td></tr>';

        print '<tr><td>'.$langs->trans('Note').'</td><td colspan="3"><textarea name="note" cols="40" rows="6" wrap="soft">';
        print $object->note;
        print '</textarea></td></tr>';

        print '</table>'."\n";

        print '<br><div class="center">';
        print '<input type="submit" class="button" value="'.$langs->trans('Add').'">';
        if (! empty($backtopage))
        {
        	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
        }
        print '</div>'."\n";

        print '</form>'."\n";

    }
}
elseif ($action == 'edit')
{
    /*
     * Fiche societe en mode edition
     */

    $societe=new Societe($db);
    $societe->fetch($socid);
    $head = societe_prepare_head($societe);

    dol_fiche_head($head, 'card', $societe->name);

    print load_fiche_titre($langs->trans("EditAddress"));
    print "<br>\n";

    if ($socid)
    {
        if ($reload || ! $_POST["name"])
        {
            $object->socid = $socid;
            $object->fetch_address($id);
        }
        else
        {
            $object->id			=	$id;
            $object->socid		=	$socid;
            $object->label		=	$_POST["label"];
            $object->name		=	$_POST["name"];
            $object->address	=	$_POST["address"];
            $object->zip		=	$_POST["zipcode"];
            $object->town		=	$_POST["town"];
            $object->country_id	=	$_POST["country_id"]?$_POST["country_id"]:$mysoc->country_id;
            $object->phone		=	$_POST["phone"];
            $object->fax		=	$_POST["fax"];
            $object->note		=	$_POST["note"];

            // On positionne country_id, country_code and label of the chosen country
            if ($object->country_id)
            {
	        	$tmparray=getCountry($object->country_id,'all');
	            $object->country_code	= $tmparray['code'];
	            $object->country		= $tmparray['label'];
            }
        }

        print '<form action="'.$_SERVER['PHP_SELF'].'?socid='.$object->socid.'" method="POST" name="formsoc">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'"/>';
        print '<input type="hidden" name="action" value="update"/>';
        print '<input type="hidden" name="socid" value="'.$object->socid.'"/>';
        print '<input type="hidden" name="backtopage" value="'.$backtopage.'"/>';
        print '<input type="hidden" name="origin" value="'.$origin.'"/>';
        print '<input type="hidden" name="originid" value="'.$originid.'"/>';
        print '<input type="hidden" name="id" value="'.$object->id.'"/>';

        print '<table class="border" width="100%">';

        print '<tr><td>'.$langs->trans('AddressLabel').'</td><td colspan="3"><input type="text" size="40" name="label" value="'.$object->label.'"></td></tr>';
        print '<tr><td>'.$langs->trans('Name').'</td><td colspan="3"><input type="text" size="40" name="name" value="'.$object->name.'"></td></tr>';

        print '<tr><td class="tdtop">'.$langs->trans('Address').'</td><td colspan="3"><textarea name="address" class="quatrevingtpercent" rows="3" wrap="soft">';
        print $object->address;
        print '</textarea></td></tr>';

        // Zip
        print '<tr><td>'.$langs->trans('Zip').'</td><td>';
        print $formcompany->select_ziptown($object->zip,'zipcode',array('town','selectcountry_id'),6);
        print '</td></tr>';

        // Town
        print '<tr><td>'.$langs->trans('Town').'</td><td>';
        print $formcompany->select_ziptown($object->town,'town',array('zipcode','selectcountry_id'));
        print '</td></tr>';

        print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
        print $form->select_country($object->country_id,'country_id');
        print '</td></tr>';

        print '<tr><td>'.$langs->trans('Phone').'</td><td><input type="text" name="phone" value="'.$object->phone.'"></td></tr>';

        print '<tr><td>'.$langs->trans('Fax').'</td><td><input type="text" name="fax" value="'.$object->fax.'"></td></tr>';

        print '<tr><td>'.$langs->trans('Note').'</td><td colspan="3"><textarea name="note" cols="40" rows="6" wrap="soft">';
        print $object->note;
        print '</textarea></td></tr>';

        print '</table><br>';

        print '<div class="center">';
        print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
        print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
        print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
        print '</div>';

        print '</form>';
    }
}
else
{
    /*
     * Fiche societe en mode visu
     */

    $result=$object->fetch_lines($socid);
    if ($result < 0)
    {
        dol_print_error($db,$object->error);
        exit;
    }

    $societe=new Societe($db);
    $societe->fetch($object->socid);
    $head = societe_prepare_head($societe);

    dol_fiche_head($head, 'customer', $societe->name);


    // Confirmation delete
    if ($action == 'delete')
    {
        print $form->formconfirm($_SERVER['PHP_SELF']."?socid=".$object->socid."&amp;id=".$id,$langs->trans("DeleteAddress"),$langs->trans("ConfirmDeleteAddress"),"confirm_delete");
    }

    $nblines = count($object->lines);
    if ($nblines)
    {
        for ($i = 0 ; $i < $nblines ; $i++)
        {

            print '<table class="border" width="100%">';

            print '<tr><td width="20%">'.$langs->trans('AddressLabel').'</td><td colspan="3">'.$object->lines[$i]->label.'</td>';
            print '<td valign="top" colspan="2" width="50%" rowspan="6">'.$langs->trans('Note').' :<br>'.nl2br($object->lines[$i]->note).'</td></tr>';
            print '<tr><td width="20%">'.$langs->trans('Name').'</td><td colspan="3">'.$object->lines[$i]->name.'</td></tr>';

            print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($object->lines[$i]->address)."</td></tr>";

            print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$object->lines[$i]->zip."</td></tr>";
            print '<tr><td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$object->lines[$i]->town."</td></tr>";

            print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">'.$object->lines[$i]->country.'</td>';

            print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($object->lines[$i]->phone,$object->lines[$i]->country_code,0,$object->socid,'AC_TEL').'</td></tr>';

            print '<tr><td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($object->lines[$i]->fax,$object->lines[$i]->country_code,0,$object->socid,'AC_FAX').'</td></tr>';

            print '</td></tr>';

            print '</table>';


            /*
             *
             */

            print '<div class="tabsAction">';

            if ($user->rights->societe->creer)
            {
                print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?socid='.$object->socid.'&amp;id='.$object->lines[$i]->id.'&amp;action=edit">'.$langs->trans("Modify").'</a></div>';
            }

            if ($user->rights->societe->supprimer)
            {
                print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?socid='.$object->socid.'&amp;id='.$object->lines[$i]->id.'&amp;action=delete">'.$langs->trans("Delete").'</a></div>';
            }


            print '</div>';
            print '<br>';
        }
    }
    else
    {
        print $langs->trans("None");
    }
    print '</div>';


    /*
     * Bouton actions
     */

    if ($action == '')
    {
        print '<div class="tabsAction">';

        if ($user->rights->societe->creer)
        {
            print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?socid='.$object->socid.'&amp;action=create">'.$langs->trans("Add").'</a></div>';
        }
        print '</div>';
    }

}


// End of page
llxFooter();
$db->close();
