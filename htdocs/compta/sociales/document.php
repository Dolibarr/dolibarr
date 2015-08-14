<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2005      Simon TOSSER          <simon@kornog-computing.com>
 * Copyright (C) 2011      Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
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
 *       \file       htdocs/compta/sociales/document.php
 *       \ingroup    tax
 *       \brief      Page with attached files on social contributions
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load("other");
$langs->load("companies");
$langs->load("compta");
$langs->load("bills");

$id = GETPOST('id','int');
$action = GETPOST("action");
$confirm = GETPOST('confirm', 'alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', $id, 'chargesociales','charges');


// Get parameters
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) {
    $page = 0;
}
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";


$object = new ChargeSociales($db);
if ($id > 0) $object->fetch($id);

$upload_dir = $conf->tax->dir_output.'/'.dol_sanitizeFileName($object->ref);
$modulepart='tax';


/*
 * Actions
 */

include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';


/*
 * View
 */

$form = new Form($db);

$help_url='EN:Module_Taxes_and_social_contributions|FR:Module Taxes et dividendes|ES:M&oacute;dulo Impuestos y cargas sociales (IVA, impuestos)';
llxHeader("",$langs->trans("SocialContribution"),$help_url);

if ($object->id)
{
	$alreadypayed=$object->getSommePaiement();

    $head=tax_prepare_head($object);

    dol_fiche_head($head, 'documents',  $langs->trans("SocialContribution"), 0, 'bill');


    // Construit liste des fichiers
    $filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
    $totalsize=0;
    foreach($filearray as $key => $file)
    {
        $totalsize+=$file['size'];
    }


    print '<table class="border" width="100%">';

    // Ref
	print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>';
	print $form->showrefnav($object,'id');
	print "</td></tr>";

    // Label
    if ($action == 'edit')
    {
        print '<tr><td>'.$langs->trans("Label").'</td><td>';
        print '<input type="text" name="label" size="40" value="'.$object->lib.'">';
        print '</td></tr>';
    }
    else
    {
        print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->lib.'</td></tr>';
    }

    // Type
    print "<tr><td>".$langs->trans("Type")."</td><td>".$object->type_libelle."</td></tr>";

    // Period end date
    print "<tr><td>".$langs->trans("PeriodEndDate")."</td>";
    print "<td>";
    if ($action == 'edit')
    {
        print $form->select_date($object->periode, 'period', 0, 0, 0, 'charge', 1);
    }
    else
    {
        print dol_print_date($object->periode,"day");
    }
    print "</td>";
    print "</tr>";

    // Due date
    if ($action == 'edit')
    {
        print '<tr><td>'.$langs->trans("DateDue")."</td><td>";
        print $form->select_date($object->date_ech, 'ech', 0, 0, 0, 'charge', 1);
        print "</td></tr>";
    }
    else {
        print "<tr><td>".$langs->trans("DateDue")."</td><td>".dol_print_date($object->date_ech,'day')."</td></tr>";
    }

    // Amount
    print '<tr><td>'.$langs->trans("AmountTTC").'</td><td>'.price($object->amount,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';

    // Status
    print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4,$alreadypayed).'</td></tr>';

    print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
    print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
    print '</table>';

    print '</div>';

    $modulepart = 'tax';
    $permission = $user->rights->tax->charges->creer;
    $param = '&id=' . $object->id;
    include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
}
else
{
    print $langs->trans("ErrorUnknown");
}


llxFooter();

$db->close();
