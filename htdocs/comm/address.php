<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 *      \file       htdocs/comm/address.php
 *      \ingroup    societe
 *      \brief      Tab address of customer
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php");
require_once(DOL_DOCUMENT_ROOT."/societe/class/address.class.php");

$langs->load("companies");
$langs->load("commercial");

$id = isset($_GET["id"])?$_GET["id"]:'';
$origin = isset($_GET["origin"])?$_GET["origin"]:'';
$originid = isset($_GET["originid"])?$_GET["originid"]:'';
$socid = isset($_REQUEST["socid"])?$_REQUEST["socid"]:'';
if (! $socid && ($_REQUEST["action"] != 'create' && $_REQUEST["action"] != 'add' && $_REQUEST["action"] != 'update')) accessforbidden();

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);


/*
 * Actions
 */

if ($_POST["action"] == 'add' || $_POST["action"] == 'update')
{
    $address = new Address($db);

    $address->socid		= $_POST["socid"];
    $address->label		= ($_POST["label"]!=$langs->trans('RequiredField')?$_POST["label"]:'');
    $address->name		= ($_POST["name"]!=$langs->trans('RequiredField')?$_POST["name"]:'');
    $address->address	= $_POST["address"];
    $address->cp		= $_POST["zipcode"];
    $address->ville		= $_POST["town"];
    $address->pays_id	= $_POST["pays_id"];
    $address->tel		= $_POST["tel"];
    $address->fax		= $_POST["fax"];
    $address->note		= $_POST["note"];

    if ($_POST["action"] == 'add')
    {
        $socid		= $_POST["socid"];
        $origin		= $_POST["origin"];
        $originid 	= $_POST["originid"];
        $result		= $address->create($socid, $user);

        if ($result >= 0)
        {
            if ($origin == 'commande')
            {
                Header("Location: ../commande/fiche.php?action=editdelivery_adress&socid=".$socid."&id=".$originid);
                exit;
            }
            elseif ($origin == 'propal')
            {
                Header("Location: ../comm/propal.php?action=editdelivery_adress&socid=".$socid."&id=".$originid);
                exit;
            }
            else
            {
                Header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid);
                exit;
            }
        }
        else
        {
            $mesg = $address->error;
            $_GET["action"]='create';
        }
    }

    if ($_POST["action"] == 'update')
    {
        $socid		= $_POST["socid"];
        $origin		= $_POST["origin"];
        $originid 	= $_POST["originid"];
        $result 	= $address->update($_POST["id"], $socid, $user);

        if ($result >= 0)
        {
            if ($origin == 'commande')
            {
                Header("Location: ../commande/fiche.php?id=".$originid);
                exit;
            }
            elseif ($origin == 'propal')
            {
                Header("Location: ../comm/propal.php?id=".$originid);
                exit;
            }
            elseif ($origin == 'shipment')
            {
                Header("Location: ../expedition/fiche.php?id=".$originid);
                exit;
            }
            else
            {
                Header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid);
                exit;
            }
        }
        else
        {
            $reload = 0;
            $mesg = $address->error;
            $_GET["action"]= "edit";
        }
    }

}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes' && $user->rights->societe->supprimer)
{
    $address = new Address($db);
    $result = $address->delete($_GET["id"], $socid);

    if ($result == 0)
    {
        Header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid);
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
$formcompany = new FormCompany($db);
$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

dol_htmloutput_errors($mesg);


if ($_GET["action"] == 'create' || $_POST["action"] == 'create')
{
    if ($user->rights->societe->creer)
    {
        /*
         * Creation
         */

        $address = new Address($db);

        $societe=new Societe($db);
        $societe->fetch($socid);
        $head = societe_prepare_head($societe);

        dol_fiche_head($head, 'customer', $societe->nom);

        if ($_POST["label"] && $_POST["name"])
        {
            $address->socid		=	$_POST["socid"];
            $address->label		=	$_POST["label"];
            $address->name		=	$_POST["name"];
            $address->address	=	$_POST["address"];
            $address->cp		=	$_POST["zipcode"];
            $address->ville		=	$_POST["town"];
            $address->tel		=	$_POST["tel"];
            $address->fax		=	$_POST["fax"];
            $address->note		=	$_POST["note"];
        }

        // On positionne pays_id, pays_code et libelle du pays choisi
        $address->pays_id=$_POST["pays_id"]?$_POST["pays_id"]:$mysoc->pays_id;
        if ($address->pays_id)
        {
            $sql = "SELECT code, libelle";
            $sql.= " FROM ".MAIN_DB_PREFIX."c_pays";
            $sql.= " WHERE rowid = ".$address->pays_id;

            $resql=$db->query($sql);
            if ($resql)
            {
                $obj = $db->fetch_object($resql);
            }
            else
            {
                dol_print_error($db);
            }
            $address->pays_code	=	$obj->code;
            $address->pays		=	$obj->libelle;
        }

        print_titre($langs->trans("NewAddress"));
        print "<br>\n";

        if ($address->error)
        {
            print '<div class="error">';
            print nl2br($address->error);
            print '</div>';
        }

        // If javascript enabled, we add interactivity on mandatory fields
        if ($conf->use_javascript_ajax)
        {
            print "\n".'<script type="text/javascript" language="javascript">';
            print 'jQuery(document).ready(function () {
                        jQuery("#label").focus(function() {
                            hideMessage("label","'.$langs->trans('RequiredField').'");
                        });
                        jQuery("#label").blur(function() {
                            displayMessage("label","'.$langs->trans('RequiredField').'");
                        });
                        jQuery("#name").focus(function() {
                            hideMessage("name","'.$langs->trans('RequiredField').'");
                        });
                        jQuery("#name").blur(function() {
                            displayMessage("name","'.$langs->trans('RequiredField').'");
                        });
                        displayMessage("label","'.$langs->trans('RequiredField').'");
                        displayMessage("name","'.$langs->trans('RequiredField').'");
                        jQuery("#label").css("color","grey");
                        jQuery("#name").css("color","grey");
                    })';
            print '</script>'."\n";
        }

        print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" name="formsoc">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="socid" value="'.$socid.'">';
        print '<input type="hidden" name="origin" value="'.$origin.'">';
        print '<input type="hidden" name="originid" value="'.$originid.'">';
        print '<input type="hidden" name="action" value="add">';

        print '<table class="border" width="100%">';

        print '<tr><td class="fieldrequired">'.$langs->trans('AddressLabel').'</td><td><input type="text" size="30" name="label" id="label" value="'.($address->label?$address->label:$langs->trans('RequiredField')).'"></td></tr>';
        print '<tr><td class="fieldrequired">'.$langs->trans('Name').'</td><td><input type="text" size="30" name="name" id="name" value="'.($address->name?$address->name:$langs->trans('RequiredField')).'"></td></tr>';

        print '<tr><td valign="top">'.$langs->trans('Address').'</td><td colspan="3"><textarea name="address" cols="40" rows="3" wrap="soft">';
        print $address->address;
        print '</textarea></td></tr>';

        // Zip
        print '<tr><td>'.$langs->trans('Zip').'</td><td>';
        print $formcompany->select_ziptown($address->cp,'zipcode',array('town','selectpays_id'),6);
        print '</td></tr>';

        // Town
        print '<tr><td>'.$langs->trans('Town').'</td><td>';
        print $formcompany->select_ziptown($address->ville,'town',array('zipcode','selectpays_id'));
        print '</td></tr>';

        print '<tr><td width="25%">'.$langs->trans('Country').'</td><td colspan="3">';
        $form->select_pays($address->pays_id,'pays_id');
        print '</td></tr>';

        print '<tr><td>'.$langs->trans('Phone').'</td><td><input type="text" name="tel" value="'.$address->tel.'"></td></tr>';

        print '<tr><td>'.$langs->trans('Fax').'</td><td><input type="text" name="fax" value="'.$address->fax.'"></td></tr>';

        print '<tr><td>'.$langs->trans('Note').'</td><td colspan="3"><textarea name="note" cols="40" rows="6" wrap="soft">';
        print $address->note;
        print '</textarea></td></tr>';

        print '<tr><td colspan="4" align="center">';
        print '<input type="submit" class="button" value="'.$langs->trans('AddAddress').'"></td></tr>'."\n";

        print '</table>'."\n";
        print '</form>'."\n";

    }
}
elseif ($_GET["action"] == 'edit' || $_POST["action"] == 'edit')
{
    /*
     * Fiche societe en mode edition
     */
    $address = new Address($db);

    $societe=new Societe($db);
    $societe->fetch($_GET["socid"]);
    $head = societe_prepare_head($societe);

    dol_fiche_head($head, 'customer', $societe->nom);

    print_titre($langs->trans("EditAddress"));
    print "<br>\n";

    if ($socid)
    {
        if ($reload || ! $_POST["name"])
        {
            $address->socid = $socid;
            $address->fetch_address($id);
        }
        else
        {
            $address->id		=	$_POST["id"];
            $address->socid		=	$_POST["socid"];
            $address->label		=	$_POST["label"];
            $address->name		=	$_POST["name"];
            $address->address	=	$_POST["address"];
            $address->cp		=	$_POST["zipcode"];
            $address->ville		=	$_POST["town"];
            $address->pays_id	=	$_POST["pays_id"]?$_POST["pays_id"]:$mysoc->pays_id;
            $address->tel		=	$_POST["tel"];
            $address->fax		=	$_POST["fax"];
            $address->note		=	$_POST["note"];

            // On positionne pays_id, pays_code et libelle du pays choisi
            if ($address->pays_id)
            {
                $sql = "SELECT code, libelle";
                $sql.= " FROM ".MAIN_DB_PREFIX."c_pays";
                $sql.= "WHERE rowid = ".$address->pays_id;

                $resql=$db->query($sql);
                if ($resql)
                {
                    $obj = $db->fetch_object($resql);
                }
                else
                {
                    dol_print_error($db);
                }
                $address->pays_code	=	$obj->code;
                $address->pays		=	$langs->trans("Country".$obj->code)?$langs->trans("Country".$obj->code):$obj->libelle;
            }
        }

        if ($address->error)
        {
            print '<div class="error">';
            print $address->error;
            print '</div>';
        }

        print '<form action="'.$_SERVER['PHP_SELF'].'?socid='.$address->socid.'" method="POST" name="formsoc">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="update">';
        print '<input type="hidden" name="socid" value="'.$address->socid.'">';
        print '<input type="hidden" name="origin" value="'.$origin.'">';
        print '<input type="hidden" name="originid" value="'.$originid.'">';
        print '<input type="hidden" name="id" value="'.$address->id.'">';

        print '<table class="border" width="100%">';

        print '<tr><td>'.$langs->trans('AddressLabel').'</td><td colspan="3"><input type="text" size="40" name="label" value="'.$address->label.'"></td></tr>';
        print '<tr><td>'.$langs->trans('Name').'</td><td colspan="3"><input type="text" size="40" name="name" value="'.$address->name.'"></td></tr>';

        print '<tr><td valign="top">'.$langs->trans('Address').'</td><td colspan="3"><textarea name="address" cols="40" rows="3" wrap="soft">';
        print $address->address;
        print '</textarea></td></tr>';

        // Zip
        print '<tr><td>'.$langs->trans('Zip').'</td><td>';
        print $formcompany->select_ziptown($address->cp,'zipcode',array('town','selectpays_id'),6);
        print '</td></tr>';

        // Town
        print '<tr><td>'.$langs->trans('Town').'</td><td>';
        print $formcompany->select_ziptown($address->ville,'town',array('zipcode','selectpays_id'));
        print '</td></tr>';

        print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">';
        $form->select_pays($address->pays_id,'pays_id');
        print '</td></tr>';

        print '<tr><td>'.$langs->trans('Phone').'</td><td><input type="text" name="tel" value="'.$address->tel.'"></td></tr>';

        print '<tr><td>'.$langs->trans('Fax').'</td><td><input type="text" name="fax" value="'.$address->fax.'"></td></tr>';

        print '<tr><td>'.$langs->trans('Note').'</td><td colspan="3"><textarea name="note" cols="40" rows="6" wrap="soft">';
        print $address->note;
        print '</textarea></td></tr>';

        print '<tr><td align="center" colspan="4"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';

        print '</table>';
        print '</form>';
    }
}
else
{
    /*
     * Fiche societe en mode visu
     */
    $address = new Address($db);
    $result=$address->fetch($socid);
    if ($result < 0)
    {
        dol_print_error($db,$address->error);
        exit;
    }

    $societe=new Societe($db);
    $societe->fetch($address->socid);
    $head = societe_prepare_head($societe);

    dol_fiche_head($head, 'customer', $societe->nom);


    // Confirmation delete
    if ($_GET["action"] == 'delete')
    {
        $html = new Form($db);
        $ret=$html->form_confirm($_SERVER['PHP_SELF']."?socid=".$address->socid."&amp;id=".$_GET["id"],$langs->trans("DeleteAddress"),$langs->trans("ConfirmDeleteAddress"),"confirm_delete");
        if ($ret == 'html') print '<br>';
    }

    if ($address->error)
    {
        print '<div class="error">';
        print $address->error;
        print '</div>';
    }

    $nblines = count($address->lines);
    if ($nblines)
    {
        for ($i = 0 ; $i < $nblines ; $i++)
        {

            print '<table class="border" width="100%">';

            print '<tr><td width="20%">'.$langs->trans('AddressLabel').'</td><td colspan="3">'.$address->lines[$i]->label.'</td>';
            print '<td valign="top" colspan="2" width="50%" rowspan="6">'.$langs->trans('Note').' :<br>'.nl2br($address->lines[$i]->note).'</td></tr>';
            print '<tr><td width="20%">'.$langs->trans('Name').'</td><td colspan="3">'.$address->lines[$i]->name.'</td></tr>';

            print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($address->lines[$i]->address)."</td></tr>";

            print '<tr><td width="25%">'.$langs->trans('Zip').'</td><td width="25%">'.$address->lines[$i]->cp."</td></tr>";
            print '<tr><td width="25%">'.$langs->trans('Town').'</td><td width="25%">'.$address->lines[$i]->ville."</td></tr>";

            print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">'.$address->lines[$i]->pays.'</td>';

            print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dol_print_phone($address->lines[$i]->tel,$address->lines[$i]->pays_code,0,$address->socid,'AC_TEL').'</td></tr>';

            print '<tr><td>'.$langs->trans('Fax').'</td><td>'.dol_print_phone($address->lines[$i]->fax,$address->lines[$i]->pays_code,0,$address->socid,'AC_FAX').'</td></tr>';

            print '</td></tr>';

            print '</table>';


            /*
             *
             */

            print '<div class="tabsAction">';

            if ($user->rights->societe->creer)
            {
                print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?socid='.$address->socid.'&amp;id='.$address->lines[$i]->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
            }

            if ($user->rights->societe->supprimer)
            {
                print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?socid='.$address->socid.'&amp;id='.$address->lines[$i]->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
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

    if ($_GET["action"] == '')
    {
        print '<div class="tabsAction">';

        if ($user->rights->societe->creer)
        {
            print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?socid='.$address->socid.'&amp;action=create">'.$langs->trans("Add").'</a>';
        }
        print '</div>';
    }

}

$db->close();


llxFooter();
?>
