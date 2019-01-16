<?php
/* Copyright (C) 2014-2018  Alexandre Spangaro  <aspangaro@zendsi.com>
 * Copyright (C) 2017       Ferran Marcet       <fmarcet@2byte.es>
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
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("other","companies","compta","bills","loan"));

$id = GETPOST('id','int');
$action = GETPOST('action','aZ09');
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

include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("Loan") . ' - ' . $langs->trans("Documents");
$help_url = 'EN:Module_Loan|FR:Module_Emprunt';
llxHeader("",$title,$help_url);

if ($object->id)
{
	$totalpaid=$object->getSumPayment();

    $head = loan_prepare_head($object);

    dol_fiche_head($head, 'documents',  $langs->trans("Loan"), -1, 'bill');

	$morehtmlref='<div class="refidno">';
	// Ref loan
	$morehtmlref.=$form->editfieldkey("Label", 'label', $object->label, $object, 0, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("Label", 'label', $object->label, $object, 0, 'string', '', null, null, '', 1);
	// Project
	if (! empty($conf->projet->enabled)) {
		$langs->load("projects");
		$morehtmlref .= '<br>' . $langs->trans('Project') . ' : ';
		if ($user->rights->loan->write) {
			//if ($action != 'classify')
			//	$morehtmlref .= '<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
			if ($action == 'classify') {
				// $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
				$morehtmlref .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
				$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
				$morehtmlref .= '<input type="submit" class="button valignmiddle" value="' . $langs->trans("Modify") . '">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
			}
		} else {
			if (! empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= '<a href="' . DOL_URL_ROOT . '/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
				$morehtmlref .= $proj->ref;
				$morehtmlref .= '</a>';
			} else {
				$morehtmlref .= '';
			}
		}
	}
	$morehtmlref.='</div>';

	$linkback = '<a href="' . DOL_URL_ROOT . '/loan/list.php">' . $langs->trans("BackToList") . '</a>';

	$object->totalpaid = $totalpaid;   // To give a chance to dol_banner_tab to use already paid amount to show correct status

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlright);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';


    // Build file list
    $filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
    $totalsize=0;
    foreach($filearray as $key => $file)
    {
        $totalsize+=$file['size'];
    }


    print '<table class="border" width="100%">';
    print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td>'.count($filearray).'</td></tr>';
    print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td>'.dol_print_size($totalsize,1,1).'</td></tr>';
    print "</table>\n";

    print "</div>\n";

    dol_fiche_end();

    $modulepart = 'loan';
    $permission = $user->rights->loan->write;
    $permtoedit = $user->rights->loan->write;
    $param = '&id=' . $object->id;
    include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
}
else
{
    print $langs->trans("ErrorUnknown");
}

// End of page
llxFooter();
$db->close();
