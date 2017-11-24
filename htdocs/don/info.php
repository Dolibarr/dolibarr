<?php
/* Copyright (C) 2015-2016	Alexandre Spangaro		<aspangaro@zendsi.com>
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
 * 	\file       htdocs/don/info.php
 * 	\ingroup    donations
 * 	\brief      Page to show a donation information
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/donation.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
if (! empty($conf->projet->enabled))
{
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

$langs->load("donations");

$id = GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');
$projectid = (GETPOST('projectid') ? GETPOST('projectid', 'int') : 0);

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'don', $id, '');

$object = new Don($db);
$object->fetch($id);
/*
 * Actions
 */
if ($action == 'classin' && $user->rights->don->creer)
{
	$object->fetch($id);
	$object->setProject($projectid);
}

/*
 * View
 */
$title = $langs->trans('Donation') . " - " . $langs->trans('Info');
$helpurl = "";
llxHeader('', $title, $helpurl);

$form = new Form($db);
if (! empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

$object->info($id);

$head = donation_prepare_head($object);

dol_fiche_head($head, 'info', $langs->trans("Donation"), -1, 'generic');

$linkback = '<a href="'.DOL_URL_ROOT.'/don/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

$morehtmlref='<div class="refidno">';
// Project
if (! empty($conf->projet->enabled))
{
    $langs->load("projects");
    $morehtmlref.=$langs->trans('Project') . ' ';
    if ($user->rights->don->creer)
    {
        if ($action != 'classify')
            // $morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
            if ($action == 'classify') {
                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
                $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
                $morehtmlref.='<input type="hidden" name="action" value="classin">';
                $morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
                $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
                $morehtmlref.='</form>';
            } else {
                $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
            }
    } else {
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
}
$morehtmlref.='</div>';

dol_banner_tab($object, 'rowid', $linkback, 1, 'rowid', 'ref', $morehtmlref);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<br>';

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';

dol_fiche_end();

llxFooter();
$db->close();
