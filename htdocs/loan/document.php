<?php
/* Copyright (C) 2014		Alexandre Spangaro	<alexandre.spangaro@gmail.com>
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
 *       \file       htdocs/loan/document.php
 *       \ingroup    loan
 *       \brief      Page with attached files on loan
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/loan.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load("other");
$langs->load("companies");
$langs->load("compta");
$langs->load("bills");
$langs->load("loan");

$id = GETPOST('id','int');
$action = GETPOST("action");
$confirm = GETPOST('confirm', 'alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'loan', $id, '','');

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

$object = new Loan($db);
if ($id > 0) $object->fetch($id);

$upload_dir = $conf->loan->dir_output.'/'.dol_sanitizeFileName($object->ref);
$modulepart='loan';


/*
 * Actions
 */

include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';


/*
 * View
 */

$form = new Form($db);

$help_url='EN:Module_Loan|FR:Module_Emprunt';
llxHeader("",$langs->trans("Loan"),$help_url);

if ($object->id)
{
	$alreadypayed=$object->getSumPayment();
	
    $head = loan_prepare_head($object, $user);

    dol_fiche_head($head, 'documents',  $langs->trans("Loan"), 0, 'bill');


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
        print '<input type="text" name="label" size="40" value="'.$object->label.'">';
        print '</td></tr>';
    }
    else
    {
        print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->label.'</td></tr>';
    }

    // Amount
    print '<tr><td>'.$langs->trans("Capital").'</td><td>'.price($object->capital,0,$outputlangs,1,-1,-1,$conf->currency).'</td></tr>';

	// Date start
    print "<tr><td>".$langs->trans("DateStart")."</td>";
    print "<td>";
    if ($action == 'edit')
    {
        print $form->select_date($object->datestart, 'start', 0, 0, 0, 'loan', 1);
    }
    else
    {
        print dol_print_date($object->datestart,"day");
    }
    print "</td>";
    print "</tr>";

    // Date end
    print "<tr><td>".$langs->trans("DateEnd")."</td>";
    print "<td>";
    if ($action == 'edit')
    {
        print $form->select_date($object->dateend, 'end', 0, 0, 0, 'loan', 1);
    }
    else
    {
        print dol_print_date($object->dateend,"day");
    }
    print "</td>";
    print "</tr>";

    // Status
    print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4,$alreadypayed).'</td></tr>';

    print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
    print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
    print '</table>';

    print '</div>';

    $modulepart = 'loan';
    $permission = $user->rights->loan->write;
    $param = '&id=' . $object->id;
    include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
}
else
{
    print $langs->trans("ErrorUnknown");
}


llxFooter();

$db->close();
