<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/admin/societe_extrafields.php
 *		\ingroup    societe
 *		\brief      Page to setup extra fields of third party
 *		\version    $Id: societe_extrafields.php,v 1.6 2011/07/31 22:23:21 eldy Exp $
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/extrafields.class.php");

$langs->load("companies");
$langs->load("admin");

$extrafields = new ExtraFields($db);
$form = new Form($db);

// List of supported format
$type2label=array(
'varchar'=>$langs->trans('String'),
'text'=>$langs->trans('Text'),
'int'=>$langs->trans('Int'),
//'date'=>$langs->trans('Date'),
//'datetime'=>$langs->trans('DateAndTime')
);

$action=GETPOST("action");
$elementtype='company';

if (!$user->admin)
accessforbidden();


/*
 * Actions
 */

if ($action == 'add')
{
    if ($_POST["button"] != $langs->trans("Cancel"))
    {
        // Check values
        if (GETPOST('type')=='varchar' && GETPOST('size') > 255)
        {
            $error++;
            $langs->load("errors");
            $mesg=$langs->trans("ErrorSizeTooLongForVarcharType");
            $action = 'create';
        }

        if (! $error)
        {
            // Type et taille non encore pris en compte => varchar(255)
            if (isset($_POST["attrname"]) && preg_match("/^\w[a-zA-Z0-9-_]*$/",$_POST['attrname']))
            {
                $result=$extrafields->addExtraField($_POST['attrname'],$_POST['label'],$_POST['type'],$_POST['pos'],$_POST['size'],$elementtype);
                if ($result > 0)
                {
                    Header("Location: ".$_SERVER["PHP_SELF"]);
                    exit;
                }
                else
                {
                    $error++;
                    $mesg=$extrafields->error;
                }
            }
            else
            {
                $error++;
                $langs->load("errors");
                $mesg=$langs->trans("ErrorFieldCanNotContainSpecialCharacters",$langs->transnoentities("AttributeCode"));
                $action = 'create';
            }
        }
    }
}

// Rename field
if ($action == 'update')
{
    if ($_POST["button"] != $langs->trans("Cancel"))
    {
        // Check values
        if (GETPOST('type')=='varchar' && GETPOST('size') > 255)
        {
            $error++;
            $langs->load("errors");
            $mesg=$langs->trans("ErrorSizeTooLongForVarcharType");
            $action = 'edit';
        }

        if (! $error)
        {
            if (isset($_POST["attrname"]) && preg_match("/^\w[a-zA-Z0-9-_]*$/",$_POST['attrname']))
            {
                $result=$extrafields->update($_POST['attrname'],$_POST['type'],$_POST['size'],$elementtype);
                if ($result > 0)
                {
                    if (isset($_POST['label']))
                    {
                        $extrafields->update_label($_POST['attrname'],$_POST['label'],$_POST['type'],$_POST['size'],$elementtype);
                    }
                    Header("Location: ".$_SERVER["PHP_SELF"]);
                    exit;
                }
                else
                {
                    $error++;
                    $mesg=$extrafields->error;
                }
            }
            else
            {
                $error++;
                $langs->load("errors");
                $mesg=$langs->trans("ErrorFieldCanNotContainSpecialCharacters",$langs->transnoentities("AttributeCode"));
            }
        }
    }
}

# Suppression attribut
if ($action == 'delete')
{
    if(isset($_GET["attrname"]) && preg_match("/^\w[a-zA-Z0-9-_]*$/",$_GET["attrname"]))
    {
        $result=$extrafields->delete($_GET["attrname"],$elementtype);
        if ($result >= 0)
        {
            Header("Location: ".$_SERVER["PHP_SELF"]);
            exit;
        }
        else $mesg=$extrafields->error;
    }
    else
    {
        $error++;
        $langs->load("errors");
        $mesg=$langs->trans("ErrorFieldCanNotContainSpecialCharacters",$langs->transnoentities("AttributeCode"));
    }
}



/*
 * View
 */

$textobject=$langs->transnoentitiesnoconv("ThirdParty");

$help_url='EN:Module Third Parties setup|FR:Paramétrage_du_module_Tiers';
llxHeader('',$langs->trans("CompanySetup"),$help_url);


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("CompanySetup"),$linkback,'setup');


$head = societe_admin_prepare_head($adh);

dol_fiche_head($head, 'attributes', $langs->trans("ThirdParty"), 0, 'company');


print $langs->trans("DefineHereComplementaryAttributes",$textobject).'<br>'."\n";
print '<br>';

dol_htmloutput_errors($mesg);

// Load attribute_label
$extrafields->fetch_name_optionals_label($elementtype);

print "<table summary=\"listofattributes\" class=\"noborder\" width=\"100%\">";

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Label").'</td>';
print '<td>'.$langs->trans("AttributeCode").'</td>';
print '<td>'.$langs->trans("Type").'</td>';
print '<td align="right">'.$langs->trans("Size").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

$var=True;
foreach($extrafields->attribute_type as $key => $value)
{
    $var=!$var;
    print "<tr $bc[$var]>";
    print "<td>".$extrafields->attribute_label[$key]."</td>\n";
    print "<td>".$key."</td>\n";
    print "<td>".$type2label[$extrafields->attribute_type[$key]]."</td>\n";
    print '<td align="right">'.$extrafields->attribute_size[$key]."</td>\n";
    print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=edit&attrname='.$key.'">'.img_edit().'</a>';
    print "&nbsp; <a href=\"".$_SERVER["PHP_SELF"]."?action=delete&attrname=$key\">".img_delete()."</a></td>\n";
    print "</tr>";
    //      $i++;
}

print "</table>";

dol_fiche_end();

/*
 * Barre d'actions
 *
 */
if ($action != 'create' && $action != 'edit')
{
    print '<div class="tabsAction">';
    print "<a class=\"butAction\" href=\"".$_SERVER["PHP_SELF"]."?action=create\">".$langs->trans("NewAttribute")."</a>";
    print "</div>";
}


/* ************************************************************************** */
/*                                                                            */
/* Creation d'un champ optionnel
 /*                                                                            */
/* ************************************************************************** */

if ($action == 'create')
{
    print "<br>";
    print_titre($langs->trans('NewAttribute'));

    print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<table summary="listofattributes" class="border" width="100%">';

    print '<input type="hidden" name="action" value="add">';

    // Label
    print '<tr><td class="fieldrequired" required>'.$langs->trans("Label").'</td><td class="valeur"><input type="text" name="label" size="40" value="'.GETPOST('label').'"></td></tr>';
    // Code
    print '<tr><td class="fieldrequired" required>'.$langs->trans("AttributeCode").' ('.$langs->trans("AlphaNumOnlyCharsAndNoSpace").')</td><td class="valeur"><input type="text" name="attrname" size="10" value"'.GETPOST('attrname').'"></td></tr>';
    // Type
    print '<tr><td class="fieldrequired" required>'.$langs->trans("Type").'</td><td class="valeur">';
    print $form->selectarray('type',$type2label,GETPOST('type'));
    print '</td></tr>';
    // Size
    print '<tr><td class="fieldrequired" required>'.$langs->trans("Size").'</td><td><input type="text" name="size" size="5" value="'.(GETPOST('size')?GETPOST('size'):'255').'"></td></tr>';

    print '<tr><td colspan="2" align="center"><input type="submit" name="button" class="button" value="'.$langs->trans("Save").'"> &nbsp; ';
    print '<input type="submit" name="button" class="button" value="'.$langs->trans("Cancel").'"></td></tr>';
    print "</form>\n";
    print "</table>\n";
}

/* ************************************************************************** */
/*                                                                            */
/* Edition d'un champ optionnel                                               */
/*                                                                            */
/* ************************************************************************** */
if ($_GET["attrname"] && $action == 'edit')
{
    print "<br>";
    print_titre($langs->trans("FieldEdition",$_GET["attrname"]));

    /*
     * formulaire d'edition
     */
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?attrname='.$_GET["attrname"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="attrname" value="'.$_GET["attrname"].'">';
    print '<input type="hidden" name="action" value="update">';
    print '<table summary="listofattributes" class="border" width="100%">';

    // Label
    print '<tr>';
    print '<td class="fieldrequired" required>'.$langs->trans("Label").'</td><td class="valeur"><input type="text" name="label" size="40" value="'.$extrafields->attribute_label[$_GET["attrname"]].'"></td>';
    print '</tr>';
    // Code
    print '<tr>';
    print '<td class="fieldrequired" required>'.$langs->trans("AttributeCode").'</td>';
    print '<td class="valeur">'.$_GET["attrname"].'&nbsp;</td>';
    print '</tr>';
    // Type
    $type=$extrafields->attribute_type[$_GET["attrname"]];
    $size=$extrafields->attribute_size[$_GET["attrname"]];
    print '<tr><td class="fieldrequired" required>'.$langs->trans("Type").'</td>';
    print '<td class="valeur">';
    print $type2label[$type];
    print '<input type="hidden" name="type" value="'.$type.'">';
    print '</td></tr>';
    // Size
    print '<tr><td class="fieldrequired" required>'.$langs->trans("Size").'</td><td class="valeur"><input type="text" name="size" size="5" value="'.$size.'"></td></tr>';
    print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"> &nbsp; ';
    print '<input type="submit" name="button" class="button" value="'.$langs->trans("Cancel").'"></td></tr>';
    print '</table>';
    print "</form>";

}

$db->close();

llxFooter('$Date: 2011/07/31 22:23:21 $ - $Revision: 1.6 $');
?>
