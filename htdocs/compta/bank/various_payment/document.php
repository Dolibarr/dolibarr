<?php
/* Copyright (C) 2017       Alexandre Spangaro  <aspangaro@zendsi.com>
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
 * or see http://www.gnu.org/
 */

/**
 *  \file       htdocs/compta/bank/various_payment/document.php
 *  \ingroup    bank
 *  \brief      Page of linked files onto various payment
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "banks", "bills", "users", "accountancy"));

$id = GETPOST('id','int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action','alpha');
$confirm = GETPOST('confirm','alpha');

// Security check
$socid = GETPOST("socid","int");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'banque', '', '', '');

// Get parameters
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";


$object = new PaymentVarious($db);
$object->fetch($id, $ref);

$upload_dir = $conf->banque->dir_output.'/'.dol_sanitizeFileName($object->id);
$modulepart='banque';


/*
 * Actions
 */

include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("VariousPayment") . ' - ' . $langs->trans("Documents");
$help_url = '';
llxHeader("",$title,$help_url);

if ($object->id)
{
	$head=various_payment_prepare_head($object);

	dol_fiche_head($head, 'documents',  $langs->trans("VariousPayment"), -1, 'payment');

	$morehtmlref='<div class="refidno">';
	// Project
	if (! empty($conf->projet->enabled))
	{
		$langs->load("projects");
		$morehtmlref.=$langs->trans('Project') . ' : ';
		if ($user->rights->banque->modifier && 0)
		{
			if ($action != 'classify')
				$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
				if ($action == 'classify') {
					//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
					$morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
					$morehtmlref.='<input type="hidden" name="action" value="classin">';
					$morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					$morehtmlref.=$formproject->select_projects(0, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
					$morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
					$morehtmlref.='</form>';
				} else {
					$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
				}
		} else {
			if (! empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref.=$proj->getNomUrl(1);
			} else {
				$morehtmlref.='';
			}
		}
	}
	$morehtmlref.='</div>';
	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/various_payment/list.php?restore_lastsearch_values=1'.(! empty($socid)?'&socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

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

	print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
	print '</table>';

	print '</div>';

	print '<div class="clearboth"></div>';

	dol_fiche_end();

	$modulepart = 'banque';
	$permission = $user->rights->banque->modifier;
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
