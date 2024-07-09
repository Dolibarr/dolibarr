<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/projet/comment.php
 *	\ingroup    project
 *	\brief      Page of a project
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array('projects', 'companies'));

$id = GETPOSTINT('id');
$idcomment = GETPOSTINT('idcomment');
$ref = GETPOST("ref", 'alpha', 1); // task ref
$objectref = GETPOST("taskref", 'alpha'); // task ref
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$withproject = GETPOSTINT('withproject');

// Security check
$socid = 0;
//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignment.
if (!$user->hasRight('projet', 'lire')) {
	accessforbidden();
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('projectcard', 'globalcard'));

$extrafields = new ExtraFields($db);
$object = new Project($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref); // If we create project, ref may be defined into POST but record does not yet exists into database
	if ($ret > 0) {
		$object->fetch_thirdparty();
		if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_PROJECT') && method_exists($object, 'fetchComments') && empty($object->comments)) {
			$object->fetchComments();
		}
		$id = $object->id;
	}
}

// include comment actions
include DOL_DOCUMENT_ROOT.'/core/actions_comments.inc.php';

/*
 * View
*/

$title = $langs->trans('CommentPage');

llxHeader('', $title, '');

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);

// Tabs for project
$tab = 'project_comment';
$head = project_prepare_head($object);
print dol_get_fiche_head($head, $tab, $langs->trans("Project"), - 1, ($object->public ? 'projectpub' : 'project'));

$param = ($mode == 'mine' ? '&mode=mine' : '');

// Project card

if (!empty($_SESSION['pageforbacktolist']) && !empty($_SESSION['pageforbacktolist']['project'])) {
	$tmpurl = $_SESSION['pageforbacktolist']['project'];
	$tmpurl = preg_replace('/__SOCID__/', (string) $object->socid, $tmpurl);
	$linkback = '<a href="'.$tmpurl.(preg_match('/\?/', $tmpurl) ? '&' : '?'). 'restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
} else {
	$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
}

$morehtmlref = '<div class="refidno">';
// Title
$morehtmlref .= $object->title;
// Thirdparty
if (!empty($object->thirdparty->id) && $object->thirdparty->id > 0) {
	$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1, 'project');
}
$morehtmlref .= '</div>';

// Define a complementary filter for search of next/prev ref.
if (!$user->hasRight('projet', 'all', 'lire')) {
	$objectsListId = $object->getProjectsAuthorizedForUser($user, 0, 0);
	$object->next_prev_filter = "rowid IN (".$db->sanitize(count($objectsListId) ? implode(',', array_keys($objectsListId)) : '0').")";
}

dol_banner_tab($object, 'project_ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

print '<div class="fichecenter">';
print '<div class="fichehalfleft">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent">';

// Visibility
print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
if ($object->public) {
	print img_picto($langs->trans('SharedProject'), 'world', 'class="paddingrightonly"');
	print $langs->trans('SharedProject');
} else {
	print img_picto($langs->trans('PrivateProject'), 'private', 'class="paddingrightonly"');
	print $langs->trans('PrivateProject');
}
print '</td></tr>';

// Budget
print '<tr><td>'.$langs->trans("Budget").'</td><td>';
if (!is_null($object->budget_amount) && strcmp($object->budget_amount, '')) {
	print price($object->budget_amount, 0, $langs, 1, 0, 0, $conf->currency);
}
print '</td></tr>';

// Date start - end project
print '<tr><td>'.$langs->trans("Dates").'</td><td>';
print dol_print_date($object->date_start, 'day');
$end = dol_print_date($object->date_end, 'day');
if ($end) {
	print ' - '.$end;
}
print '</td></tr>';

// Other attributes
$cols = 2;
// include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

print '</table>';

print '</div>';
print '<div class="fichehalfright">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent">';

// Description
print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
print nl2br($object->description);
print '</td></tr>';

// Categories
if (isModEnabled('category')) {
	print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
	print $form->showCategories($object->id, Categorie::TYPE_PROJECT, 1);
	print "</td></tr>";
}

// Nb comments
print '<td class="titlefield">'.$langs->trans("NbComments").'</td><td>';
print $object->getNbComments();
print '</td></tr>';

print '</table>';

print '</div>';
print '</div>';

print '<div class="clearboth"></div>';

print dol_get_fiche_end();

print '<br>';

// Include comment tpl view
include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_comment.tpl.php';

// End of page
llxFooter();
$db->close();
