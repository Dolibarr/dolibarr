<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
* Copyright (C) 2003 Jean-Louis Bergamo <jlb@j1b.org>
* Copyright (C) 2004-2011 Laurent Destailleur <eldy@users.sourceforge.net>
* Copyright (C) 2012 Regis Houssin <regis.houssin@capnetworks.com>
* Copyright (C) 2013 Jean-Francois FERRY <jfefe@aternatik.fr>
* Copyright (C) 2013 Florian Henry	<florian.henry@open-concept.pro>
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

/**
* \file htdocs/compta/facture/admin/facture_cust_extrafields.php
* \ingroup invoice
* \brief Page to setup extra fields of customer invoice
*/

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

$langs->load("companies");
$langs->load("admin");
$langs->load("bills");

$extrafields = new ExtraFields($db);
$form = new Form($db);

// List of supported format
$tmptype2label=getStaticMember(get_class($extrafields),'type2label');
$type2label=array('');
foreach ($tmptype2label as $key => $val) $type2label[$key]=$langs->trans($val);

$action=GETPOST('action', 'alpha');
$attrname=GETPOST('attrname', 'alpha');
$elementtype='facturedet'; //Must be the $table_element of the class that manage extrafield

if (!$user->admin) accessforbidden();


/*
* Actions
*/

require DOL_DOCUMENT_ROOT.'/core/admin_extrafields.inc.php';



/*
* View
*/

$textobject=strtolower($langs->transnoentitiesnoconv("BillsCustomers"));


llxHeader('',$langs->trans("BillsSetup"));


$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("BillsSetup"),$linkback,'setup');
print '<br>';

$head = invoice_admin_prepare_head(null);

dol_fiche_head($head, 'attributeslines', $langs->trans("Invoices"), 0, 'invoice');


print $langs->trans("DefineHereComplementaryAttributes",$textobject).'<br>'."\n";

// Load attribute_label
$extrafields->fetch_name_optionals_label($elementtype);

print "<table summary=\"listofattributes\" class=\"noborder\" width=\"100%\">";

print '<tr class="liste_titre">';
print '<td align="center">'.$langs->trans("Position").'</td>';
print '<td>'.$langs->trans("Label").'</td>';
print '<td>'.$langs->trans("AttributeCode").'</td>';
print '<td>'.$langs->trans("Type").'</td>';
print '<td align="right">'.$langs->trans("Size").'</td>';
print '<td align="center">'.$langs->trans("Unique").'</td>';
print '<td align="center">'.$langs->trans("Required").'</td>';
print '<td width="80">&nbsp;</td>';
print "</tr>\n";

$var=True;
foreach($extrafields->attribute_type as $key => $value)
{
    $var=!$var;
    print "<tr ".$bc[$var].">";
    print "<td>".$extrafields->attribute_pos[$key]."</td>\n";
    print "<td>".$extrafields->attribute_label[$key]."</td>\n";
    print "<td>".$key."</td>\n";
    print "<td>".$type2label[$extrafields->attribute_type[$key]]."</td>\n";
    print '<td align="right">'.$extrafields->attribute_size[$key]."</td>\n";
    print '<td align="center">'.yn($extrafields->attribute_unique[$key])."</td>\n";
    print '<td align="center">'.yn($extrafields->attribute_required[$key])."</td>\n";
    print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=edit&attrname='.$key.'">'.img_edit().'</a>';
    print "&nbsp; <a href=\"".$_SERVER["PHP_SELF"]."?action=delete&attrname=$key\">".img_delete()."</a></td>\n";
    print "</tr>";
    // $i++;
}

print "</table>";

dol_fiche_end();


// Buttons
if ($action != 'create' && $action != 'edit')
{
    print '<div class="tabsAction">';
    print "<a class=\"butAction\" href=\"".$_SERVER["PHP_SELF"]."?action=create\">".$langs->trans("NewAttribute")."</a>";
    print "</div>";
}


/* ************************************************************************** */
/* */
/* Creation d'un champ optionnel
/* */
/* ************************************************************************** */

if ($action == 'create')
{
    print "<br>";
    print_titre($langs->trans('NewAttribute'));

    require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_add.tpl.php';
}

/* ************************************************************************** */
/* */
/* Edition d'un champ optionnel */
/* */
/* ************************************************************************** */
if ($action == 'edit' && ! empty($attrname))
{
    print "<br>";
    print_titre($langs->trans("FieldEdition", $attrname));

    require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_edit.tpl.php';
}

llxFooter();

$db->close();
?>