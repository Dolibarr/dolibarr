<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2011-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com> 
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
 *  \file       htdocs/employees/document.php
 *  \brief      Tab for documents linked to third party
 *  \ingroup    societe
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/employee.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/employees/class/employee.class.php';
require_once DOL_DOCUMENT_ROOT.'/employees/class/employee_type.class.php';

$langs->load("employees");
$langs->load("companies");
$langs->load('other');

$id=GETPOST('id','int');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');

// Security check
if ($user->societe_id > 0)
{
	$id = $user->societe_id;
}
$result=restrictedArea($user,'employee',$id);

// Get parameters
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";


$upload_dir = $conf->employee->dir_output . "/" . get_exdir($id,2,0,1) . '/' . $id;
$form = new Form($db);
$object=new Employee($db);
$employeet=new EmployeeType($db);
$result=$object->fetch($id);
if ($result < 0)
{
	dol_print_error($db);
	exit;
}
/*
 * Actions
 */

include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';


/*
 * View
 */


llxHeader();

if ($id > 0)
{
    $result=$employeet->fetch($object->typeid);
	if ($result > 0)
	{
		/*
		 * Affichage onglets
		 */
		if (! empty($conf->notification->enabled))
			$langs->load("mails");

		$head = employee_prepare_head($object);

		$form=new Form($db);

		dol_fiche_head($head, 'document', $langs->trans("Employee"),0,'user');


		// Construit liste des fichiers
		$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
		$totalsize=0;
		foreach($filearray as $key => $file)
		{
			$totalsize+=$file['size'];
		}


		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/employees/liste.php">'.$langs->trans("BackToList").'</a>';

    // Ref
    print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
    print '<td class="valeur">';
    print $form->showrefnav($object, 'rowid', $linkback);
    print '</td></tr>';

    // Civility
    print '<tr><td>'.$langs->trans("UserTitle").'</td><td class="valeur">'.$object->getCivilityLabel().'&nbsp;</td>';
    print '</tr>';

    // Lastname
    print '<tr><td>'.$langs->trans("Lastname").'</td><td class="valeur">'.$object->lastname.'&nbsp;</td>';
    print '</tr>';

    // Firstname
    print '<tr><td>'.$langs->trans("Firstname").'</td><td class="valeur">'.$object->firstname.'&nbsp;</td>';
    print '</tr>';

    // Nbre fichiers
		print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';

		//Total taille
		print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

		print '</table>';

		print '</div>';

		$modulepart = 'employee';
		$permission = $user->rights->employee->creer;
		$param = '&id=' . $object->id;
		include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
		print "<br><br>";
	}
	else
	{
		dol_print_error($db);
	}
}
else
{
    $langs->load("errors");
	print $langs->trans("ErrorRecordNotFound");
}


llxFooter();
$db->close();
?>
