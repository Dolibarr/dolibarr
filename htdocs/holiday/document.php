<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2005      Simon TOSSER          <simon@kornog-computing.com>
 * Copyright (C) 2011-2012 Juanjo Menent         <jmenent@2byte.es>
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
 *       \file       htdocs/holiday/document.php
 *       \ingroup    fichinter
 *       \brief      Page des documents joints sur les contrats
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/holiday.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load("other");
$langs->load("holidays");
$langs->load("companies");

$id = GETPOST('id','int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action','alpha');
$confirm = GETPOST('confirm','alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'holiday', $id, 'holiday');

$langs->load("holiday");

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


$object = new Holiday($db);
$object->fetch($id, $ref);

$upload_dir = $conf->holiday->dir_output.'/'.get_exdir($object->id, 0, 0, 0, $object, 'holiday').dol_sanitizeFileName($object->ref);
$modulepart='holiday';


/*
 * Actions
 */

include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new Form($db);

$listhalfday=array('morning'=>$langs->trans("Morning"),"afternoon"=>$langs->trans("Afternoon"));

llxHeader("","",$langs->trans("InterventionCard"));


if ($object->id)
{
	$valideur = new User($db);
	$valideur->fetch($object->fk_validator);

	$userRequest = new User($db);
	$userRequest->fetch($object->fk_user);

	$head=holiday_prepare_head($object);

	dol_fiche_head($head, 'documents', $langs->trans("CPTitreMenu"), -1,'holiday');


	// Construit liste des fichiers
	$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}


	$linkback='<a href="'.DOL_URL_ROOT.'/holiday/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref');


	print '<div class="fichecenter">';
	//print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent">';

    print '<tr>';
    print '<td class="titlefield">'.$langs->trans("User").'</td>';
	print '<td>';
	print $userRequest->getNomUrl(-1, 'leave');
	print '</td></tr>';

    // Type
    print '<tr>';
    print '<td>'.$langs->trans("Type").'</td>';
    print '<td>';
    $typeleaves=$object->getTypes(1,-1);
    print empty($typeleaves[$object->fk_type]['label']) ? $langs->trans("TypeWasDisabledOrRemoved",$object->fk_type) : $typeleaves[$object->fk_type]['label'];
    print '</td>';
    print '</tr>';

    $starthalfday=($object->halfday == -1 || $object->halfday == 2)?'afternoon':'morning';
    $endhalfday=($object->halfday == 1 || $object->halfday == 2)?'morning':'afternoon';

    if(!$edit)
    {
        print '<tr>';
        print '<td>'.$langs->trans('DateDebCP').' ('.$langs->trans("FirstDayOfHoliday").')</td>';
        print '<td>'.dol_print_date($object->date_debut,'day');
        print ' &nbsp; &nbsp; ';
        print $langs->trans($listhalfday[$starthalfday]);
        print '</td>';
        print '</tr>';
    }
    else
    {
        print '<tr>';
        print '<td>'.$langs->trans('DateDebCP').' ('.$langs->trans("FirstDayOfHoliday").')</td>';
        print '<td>';
        $form->select_date($object->date_debut,'date_debut_');
        print ' &nbsp; &nbsp; ';
		print $form->selectarray('starthalfday', $listhalfday, (GETPOST('starthalfday')?GETPOST('starthalfday'):$starthalfday));
        print '</td>';
        print '</tr>';
    }

    if (!$edit)
    {
        print '<tr>';
        print '<td>'.$langs->trans('DateFinCP').' ('.$langs->trans("LastDayOfHoliday").')</td>';
        print '<td>'.dol_print_date($object->date_fin,'day');
        print ' &nbsp; &nbsp; ';
        print $langs->trans($listhalfday[$endhalfday]);
        print '</td>';
        print '</tr>';
    }
    else
    {
        print '<tr>';
        print '<td>'.$langs->trans('DateFinCP').' ('.$langs->trans("LastDayOfHoliday").')</td>';
        print '<td>';
        $form->select_date($object->date_fin,'date_fin_');
        print ' &nbsp; &nbsp; ';
		print $form->selectarray('endhalfday', $listhalfday, (GETPOST('endhalfday')?GETPOST('endhalfday'):$endhalfday));
        print '</td>';
        print '</tr>';
    }
    print '<tr>';
    print '<td>'.$langs->trans('NbUseDaysCP').'</td>';
    print '<td>'.num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday).'</td>';
    print '</tr>';

    if ($object->statut == 5)
    {
    	print '<tr>';
    	print '<td>'.$langs->trans('DetailRefusCP').'</td>';
    	print '<td>'.$object->detail_refuse.'</td>';
    	print '</tr>';
    }

    // Description
    if (!$edit)
    {
        print '<tr>';
        print '<td>'.$langs->trans('DescCP').'</td>';
        print '<td>'.nl2br($object->description).'</td>';
        print '</tr>';
    }
    else
    {
        print '<tr>';
        print '<td>'.$langs->trans('DescCP').'</td>';
        print '<td><textarea name="description" class="flat" rows="'.ROWS_3.'" cols="70">'.$object->description.'</textarea></td>';
        print '</tr>';
    }

    print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
    print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize,1,1).'</td></tr>';

    print '</tbody>';
    print '</table>'."\n";
/*
    print '</div>';
    print '<div class="fichehalfright">';
    print '<div class="ficheaddleft">';

    print '<div class="underbanner clearboth"></div>';

	// Info workflow
    print '<table class="border centpercent">'."\n";
    print '<tbody>';

    if (! empty($object->fk_user_create))
    {
    	$userCreate=new User($db);
    	$userCreate->fetch($object->fk_user_create);
        print '<tr>';
        print '<td class="titlefield">'.$langs->trans('RequestByCP').'</td>';
        print '<td>'.$userCreate->getNomUrl(-1).'</td>';
        print '</tr>';
    }

    if (!$edit) {
        print '<tr>';
        print '<td class="titlefield">'.$langs->trans('ReviewedByCP').'</td>';
        print '<td>'.$valideur->getNomUrl(-1).'</td>';
        print '</tr>';
    } else {
        print '<tr>';
        print '<td class="titlefield">'.$langs->trans('ReviewedByCP').'</td>';
        print '<td>';
		print $form->select_dolusers($object->fk_user, "valideur", 1, ($user->admin ? '' : array($user->id)));	// By default, hierarchical parent
        print '</td>';
        print '</tr>';
    }

    print '<tr>';
    print '<td>'.$langs->trans('DateCreateCP').'</td>';
    print '<td>'.dol_print_date($object->date_create,'dayhour').'</td>';
    print '</tr>';
    if ($object->statut == 3) {
        print '<tr>';
        print '<td>'.$langs->trans('DateValidCP').'</td>';
        print '<td>'.dol_print_date($object->date_valid,'dayhour').'</td>';
        print '</tr>';
    }
    if ($object->statut == 4) {
        print '<tr>';
        print '<td>'.$langs->trans('DateCancelCP').'</td>';
        print '<td>'.dol_print_date($object->date_cancel,'dayhour').'</td>';
        print '</tr>';
    }
    if ($object->statut == 5) {
        print '<tr>';
        print '<td>'.$langs->trans('DateRefusCP').'</td>';
        print '<td>'.dol_print_date($object->date_refuse,'dayhour').'</td>';
        print '</tr>';
    }
    print '</tbody>';
    print '</table>';

    print '</div>';
    print '</div>'; */
    print '</div>';

    print '<div class="clearboth"></div>';

    dol_fiche_end();



    $modulepart = 'holiday';
    $permission = $user->rights->holiday->write;
    $permtoedit = $user->rights->holiday->write;
    $param = '&id=' . $object->id;
    include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
}
else
{
	print $langs->trans("ErrorUnknown");
}


llxFooter();

$db->close();
