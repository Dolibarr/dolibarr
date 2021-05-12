<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
<<<<<<< HEAD
 * Copyright (C) 2005-2009 Regis Houssin         <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2009 Regis Houssin         <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
if (! empty($conf->projet->enabled))
{
<<<<<<< HEAD
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
=======
    include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
    include_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}

// Load translation files required by the page
$langs->loadLangs(array('other', 'companies', 'compta', 'bills'));

<<<<<<< HEAD
$id = GETPOST('id','int');
$action = GETPOST('action','aZ09');
=======
$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$confirm = GETPOST('confirm', 'alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
<<<<<<< HEAD
$result = restrictedArea($user, 'tax', $id, 'chargesociales','charges');


// Get parameters
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) {
=======
$result = restrictedArea($user, 'tax', $id, 'chargesociales', 'charges');


// Get parameters
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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

<<<<<<< HEAD
include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';
=======
require_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

if ($action == 'setlib' && $user->rights->tax->charges->creer)
{
    $object->fetch($id);
    $result = $object->setValueFrom('libelle', GETPOST('lib'), '', '', 'text', '', $user, 'TAX_MODIFY');
    if ($result < 0)
        setEventMessages($object->error, $object->errors, 'errors');
}


/*
 * View
 */

$form = new Form($db);
if (! empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

$title = $langs->trans("SocialContribution") . ' - ' . $langs->trans("Documents");
$help_url='EN:Module_Taxes_and_social_contributions|FR:Module Taxes et dividendes|ES:M&oacute;dulo Impuestos y cargas sociales (IVA, impuestos)';
<<<<<<< HEAD
llxHeader("",$title,$help_url);
=======
llxHeader("", $title, $help_url);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

if ($object->id)
{
	$alreadypayed=$object->getSommePaiement();

    $head=tax_prepare_head($object);

<<<<<<< HEAD
    dol_fiche_head($head, 'documents',  $langs->trans("SocialContribution"), -1, 'bill');
=======
    dol_fiche_head($head, 'documents', $langs->trans("SocialContribution"), -1, 'bill');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	$morehtmlref='<div class="refidno">';
	// Label of social contribution
	$morehtmlref.=$form->editfieldkey("Label", 'lib', $object->lib, $object, $user->rights->tax->charges->creer, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("Label", 'lib', $object->lib, $object, $user->rights->tax->charges->creer, 'string', '', null, null, '', 1);
    // Project
	if (! empty($conf->projet->enabled))
	{
	    $langs->load("projects");
	    $morehtmlref.='<br>'.$langs->trans('Project') . ' : ';
        if (! empty($object->fk_project)) {
            $proj = new Project($db);
            $proj->fetch($object->fk_project);
            $morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
            $morehtmlref.=$proj->ref;
            $morehtmlref.='</a>';
        } else {
            $morehtmlref.='';
        }
	}
	$morehtmlref.='</div>';

<<<<<<< HEAD
	$linkback = '<a href="' . DOL_URL_ROOT . '/compta/sociales/index.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';
=======
	$linkback = '<a href="' . DOL_URL_ROOT . '/compta/sociales/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	$object->totalpaye = $totalpaye;   // To give a chance to dol_banner_tab to use already paid amount to show correct status

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlright);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

<<<<<<< HEAD
    // Construit liste des fichiers
    $filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
=======
    // Build file list
    $filearray=dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC), 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    $totalsize=0;
    foreach($filearray as $key => $file)
    {
        $totalsize+=$file['size'];
    }


<<<<<<< HEAD
    print '<table class="border" width="100%">';

    print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
    print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize,1,1).'</td></tr>';
=======
    print '<table class="border tableforfield centpercent">';

    print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
    print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize, 1, 1).'</td></tr>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    print '</table>';

    print '</div>';

    print '<div class="clearboth"></div>';

    dol_fiche_end();

    $modulepart = 'tax';
    $permission = $user->rights->tax->charges->creer;
    $permtoedit = $user->rights->fournisseur->facture->creer;
    $param = '&id=' . $object->id;
    include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
}
else
{
    print $langs->trans("ErrorUnknown");
}

<<<<<<< HEAD

llxFooter();

=======
// End of page
llxFooter();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$db->close();
