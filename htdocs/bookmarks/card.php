<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2022 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
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
 *    \file       htdocs/bookmarks/card.php
 *    \ingroup    bookmark
 *    \brief      Page display/creation of bookmarks
 */


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/bookmarks/class/bookmark.class.php';


// Load translation files required by the page
$langs->loadLangs(array('bookmarks', 'other'));


// Get Parameters
$id = GETPOST("id", 'int');
$action = GETPOST("action", "alpha");
$title = (string) GETPOST("title", "alpha");
$url = (string) GETPOST("url", "alpha");
$urlsource = GETPOST("urlsource", "alpha");
$target = GETPOST("target", "int");
$userid = GETPOST("userid", "int");
$position = GETPOST("position", "int");
$backtopage = GETPOST('backtopage', 'alpha');


// Initialize Objects
$object = new Bookmark($db);
if ($id > 0) {
	$object->fetch($id);
}

// Security check
restrictedArea($user, 'bookmark', $object);

$permissiontoread = $user->hasRight('bookmark', 'lire');
$permissiontoadd = $user->hasRight('bookmark', 'creer');
$permissiontodelete = $user->hasRight('bookmark', 'supprimer');



/*
 * Actions
 */

if ($action == 'add' || $action == 'addproduct' || $action == 'update') {
	if ($action == 'update') {
		$invertedaction = 'edit';
	} else {
		$invertedaction = 'create';
	}

	$error = 0;

	if (GETPOST('cancel', 'alpha')) {
		if (empty($backtopage)) {
			$backtopage = ($urlsource ? $urlsource : ((!empty($url) && !preg_match('/^http/i', $url)) ? $url : DOL_URL_ROOT.'/bookmarks/list.php'));
		}
		header("Location: ".$backtopage);
		exit;
	}

	if ($action == 'update') {
		$object->fetch(GETPOST("id", 'int'));
	}
	// Check if null because user not admin can't set an user and send empty value here.
	if (!empty($userid)) {
		$object->fk_user = $userid;
	}
	$object->title = $title;
	$object->url = $url;
	$object->target = $target;
	$object->position = $position;

	if (!$title) {
		$error++;
		setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->trans("BookmarkTitle")), null, 'errors');
	}

	if (!$url) {
		$error++;
		setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->trans("UrlOrLink")), null, 'errors');
	}

	if (!$error) {
		$object->favicon = 'none';

		if ($action == 'update') {
			$res = $object->update();
		} else {
			$res = $object->create();
		}

		if ($res > 0) {
			if (empty($backtopage)) {
				$backtopage = ($urlsource ? $urlsource : ((!empty($url) && !preg_match('/^http/i', $url)) ? $url : DOL_URL_ROOT.'/bookmarks/list.php'));
			}
			header("Location: ".$backtopage);
			exit;
		} else {
			if ($object->errno == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				$langs->load("errors");
				setEventMessages($langs->transnoentities("WarningBookmarkAlreadyExists"), null, 'warnings');
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
			$action = $invertedaction;
		}
	} else {
		$action = $invertedaction;
	}
}



/*
 * View
 */

llxHeader();

$form = new Form($db);


$head = array();
$h = 1;

$head[$h][0] = $_SERVER["PHP_SELF"].($object->id ? '?id='.$object->id : '');
$head[$h][1] = $langs->trans("Bookmark");
$head[$h][2] = 'card';
$h++;

$hselected = 'card';


if ($action == 'create') {
	/*
	 * Fact bookmark creation mode
	 */

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" enctype="multipart/form-data">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print load_fiche_titre($langs->trans("NewBookmark"), '', 'bookmark');

	print dol_get_fiche_head(null, 'bookmark', '', 0, '');

	print '<table class="border centpercent tableforfieldcreate">';

	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("BookmarkTitle").'</td><td><input id="titlebookmark" class="flat minwidth250" name="title" value="'.dol_escape_htmltag($title).'"></td><td class="hideonsmartphone"><span class="opacitymedium">'.$langs->trans("SetHereATitleForLink").'</span></td></tr>';
	dol_set_focus('#titlebookmark');

	// Url
	print '<tr><td class="fieldrequired">'.$langs->trans("UrlOrLink").'</td><td><input class="flat quatrevingtpercent minwidth500" name="url" value="'.dol_escape_htmltag($url).'"></td><td class="hideonsmartphone"><span class="opacitymedium">'.$langs->trans("UseAnExternalHttpLinkOrRelativeDolibarrLink").'</span></td></tr>';

	// Target
	print '<tr><td>'.$langs->trans("BehaviourOnClick").'</td><td>';
	$liste = array(0=>$langs->trans("ReplaceWindow"), 1=>$langs->trans("OpenANewWindow"));
	$defaulttarget = 1;
	if ($url && !preg_match('/^http/i', $url)) {
		$defaulttarget = 0;
	}
	print $form->selectarray('target', $liste, GETPOSTISSET('target') ? GETPOST('target', 'int') : $defaulttarget, 0, 0, 0, '', 0, 0, 0, '', 'maxwidth300');
	print '</td><td class="hideonsmartphone"><span class="opacitymedium">'.$langs->trans("ChooseIfANewWindowMustBeOpenedOnClickOnBookmark").'</span></td></tr>';

	// Visibility / Owner
	print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
	print img_picto('', 'user', 'class="pictofixedwidth"');
	print $form->select_dolusers(GETPOSTISSET('userid') ? GETPOST('userid', 'int') : $user->id, 'userid', 0, '', 0, ($user->admin ? '' : array($user->id)), '', 0, 0, 0, '', ($user->admin) ? 1 : 0, '', 'maxwidth300 widthcentpercentminusx');
	print '</td><td class="hideonsmartphone"></td></tr>';

	// Position
	print '<tr><td>'.$langs->trans("Position").'</td><td>';
	print '<input class="flat width50" name="position" value="'.(GETPOSTISSET("position") ? GETPOST("position", 'int') : $object->position).'">';
	print '</td><td class="hideonsmartphone"></td></tr>';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("CreateBookmark");

	print '</form>';
}


if ($id > 0 && !preg_match('/^add/i', $action)) {
	if ($action == 'edit') {
		print '<form name="edit" method="POST" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		print '<input type="hidden" name="urlsource" value="'.DOL_URL_ROOT.'/bookmarks/card.php?id='.$object->id.'">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}

	print dol_get_fiche_head($head, $hselected, $langs->trans("Bookmark"), -1, 'bookmark');

	$linkback = '<a href="'.DOL_URL_ROOT.'/bookmarks/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', '', '', 0, '', '', 0);

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Title
	print '<tr><td class="titlefield">';
	if ($action == 'edit') {
		print '<span class="fieldrequired">';
	}

	print $langs->trans("BookmarkTitle");

	if ($action == 'edit') {
		print '</span>';
	}

	print '</td><td>';
	if ($action == 'edit') {
		print '<input class="flat minwidth250" name="title" value="'.(GETPOSTISSET("title") ? GETPOST("title", '', 2) : $object->title).'">';
	} else {
		print dol_escape_htmltag($object->title);
	}
	print '</td></tr>';

	// URL
	print '<tr><td>';
	if ($action == 'edit') {
		print '<span class="fieldrequired">';
	}
	print $langs->trans("UrlOrLink");
	if ($action == 'edit') {
		print '</span>';
	}
	print '</td><td class="tdoverflowmax500">';
	if ($action == 'edit') {
		print '<input class="flat minwidth500 quatrevingtpercent" name="url" value="'.(GETPOSTISSET("url") ? GETPOST("url") : $object->url).'">';
	} else {
		print '<a href="'.(preg_match('/^http/i', $object->url) ? $object->url : DOL_URL_ROOT.$object->url).'"'.($object->target ? ' target="_blank" rel="noopener noreferrer"' : '').'>';
		print img_picto('', 'globe', 'class="paddingright"');
		print $object->url;
		print '</a>';
	}
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("BehaviourOnClick").'</td><td>';
	if ($action == 'edit') {
		$liste = array(1=>$langs->trans("OpenANewWindow"), 0=>$langs->trans("ReplaceWindow"));
		print $form->selectarray('target', $liste, GETPOSTISSET("target") ? GETPOST("target") : $object->target);
	} else {
		if ($object->target == 0) {
			print $langs->trans("ReplaceWindow");
		}
		if ($object->target == 1) {
			print $langs->trans("OpenANewWindow");
		}
	}
	print '</td></tr>';

	// Visibility / owner
	print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
	if ($action == 'edit' && $user->admin) {
		print img_picto('', 'user', 'class="pictofixedwidth"');
		print $form->select_dolusers(GETPOSTISSET('userid') ? GETPOST('userid', 'int') : ($object->fk_user ? $object->fk_user : ''), 'userid', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300 widthcentpercentminusx');
	} else {
		if ($object->fk_user > 0) {
			$fuser = new User($db);
			$fuser->fetch($object->fk_user);
			print $fuser->getNomUrl(-1);
		} else {
			print '<span class="opacitymedium">'.$langs->trans("Everybody").'</span>';
		}
	}
	print '</td></tr>';

	// Position
	print '<tr><td>'.$langs->trans("Position").'</td><td>';
	if ($action == 'edit') {
		print '<input class="flat" name="position" size="5" value="'.(GETPOSTISSET("position") ? GETPOST("position", 'int') : $object->position).'">';
	} else {
		print $object->position;
	}
	print '</td></tr>';

	// Date creation
	print '<tr><td>'.$langs->trans("DateCreation").'</td><td>'.dol_print_date($object->datec, 'dayhour').'</td></tr>';

	print '</table>';

	print '</div>';

	print dol_get_fiche_end();

	if ($action == 'edit') {
		print $form->buttonsSaveCancel();

		print '</form>';
	}


	// Buttons

	print "<div class=\"tabsAction\">\n";

	// Edit
	if ($permissiontoadd && $action != 'edit') {
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit&token='.newToken().'">'.$langs->trans("Edit").'</a>'."\n";
	}

	// Remove
	if ($permissiontodelete && $action != 'edit') {
		print '<a class="butActionDelete" href="list.php?id='.$object->id.'&action=delete&token='.newToken().'">'.$langs->trans("Delete").'</a>'."\n";
	}

	print '</div>';
}

// End of page
llxFooter();
$db->close();
