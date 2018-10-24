<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/bookmarks/card.php
 *       \brief      Page display/creation of bookmarks
 *       \ingroup    bookmark
 */


require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/bookmarks/class/bookmark.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bookmarks', 'other'));

// Security check
if (! $user->rights->bookmark->lire) {
    restrictedArea($user, 'bookmarks');
}

$id=GETPOST("id",'int');
$action=GETPOST("action","alpha");
$title=GETPOST("title","alpha");
$url=GETPOST("url","alpha");
$urlsource=GETPOST("urlsource","alpha");
$target=GETPOST("target","alpha");
$userid=GETPOST("userid","int");
$position=GETPOST("position","int");
$backtopage=GETPOST('backtopage','alpha');

$object=new Bookmark($db);


/*
 * Actions
 */

if ($action == 'add' || $action == 'addproduct' || $action == 'update')
{

	if ($action == 'update') {
		$invertedaction = 'edit';
	} else {
		$invertedaction = 'create';
	}

	$error = 0;

	if (GETPOST('cancel','alpha'))
	{
		if (empty($backtopage)) $backtopage=($urlsource?$urlsource:((! empty($url) && ! preg_match('/^http/i', $url))?$url:DOL_URL_ROOT.'/bookmarks/list.php'));
		header("Location: ".$backtopage);
		exit;
	}

	if ($action == 'update') $object->fetch(GETPOST("id",'int'));
	// Check if null because user not admin can't set an user and send empty value here.
	if(!empty($userid))
		$object->fk_user=$userid;
	$object->title=$title;
	$object->url=$url;
	$object->target=$target;
	$object->position=$position;

	if (! $title) {
		$error++;
		setEventMessages($langs->transnoentities("ErrorFieldRequired",$langs->trans("BookmarkTitle")), null, 'errors');
	}

	if (! $url) {
		$error++;
		setEventMessages($langs->transnoentities("ErrorFieldRequired",$langs->trans("UrlOrLink")), null, 'errors');
	}

	if (! $error)
	{
		$object->favicon='none';

		if ($action == 'update') $res=$object->update();
		else $res=$object->create();

		if ($res > 0)
		{
			if (empty($backtopage)) $backtopage=($urlsource?$urlsource:((! empty($url) && ! preg_match('/^http/i', $url))?$url:DOL_URL_ROOT.'/bookmarks/list.php'));
			header("Location: ".$backtopage);
			exit;
		}
		else
		{
			if ($object->errno == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$langs->load("errors");
				setEventMessages($langs->transnoentities("WarningBookmarkAlreadyExists"), null, 'warnings');
			}
			else
			{
				setEventMessages($object->error, $object->errors, 'errors');
			}
			$action = $invertedaction;
		}
	}
	else
	{
		$action = $invertedaction;
	}
}


/*
 * View
 */

llxHeader();

$form=new Form($db);


$head = array();
$h=1;

$head[$h][0] = $_SERVER["PHP_SELF"].($object->id?'id='.$object->id:'');
$head[$h][1] = $langs->trans("Card");
$head[$h][2] = 'card';
$h++;

$hselected='card';


if ($action == 'create')
{
	/*
	 * Fact bookmark creation mode
	 */

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" enctype="multipart/form-data">'."\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	print load_fiche_titre($langs->trans("NewBookmark"));

	dol_fiche_head($head, $hselected, $langs->trans("Bookmark"), 0, 'bookmark');

	print '<table class="border" width="100%">';

	print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("BookmarkTitle").'</td><td><input id="titlebookmark" class="flat minwidth100" name="title" value="'.$title.'"></td><td class="hideonsmartphone">'.$langs->trans("SetHereATitleForLink").'</td></tr>';
	dol_set_focus('#titlebookmark');

	// Url
	print '<tr><td class="fieldrequired">'.$langs->trans("UrlOrLink").'</td><td><input class="flat quatrevingtpercent" name="url" value="'.dol_escape_htmltag($url).'"></td><td class="hideonsmartphone">'.$langs->trans("UseAnExternalHttpLinkOrRelativeDolibarrLink").'</td></tr>';

	// Target
	print '<tr><td>'.$langs->trans("BehaviourOnClick").'</td><td>';
	$liste=array(0=>$langs->trans("ReplaceWindow"),1=>$langs->trans("OpenANewWindow"));
	print $form->selectarray('target',$liste,1);
	print '</td><td class="hideonsmartphone">'.$langs->trans("ChooseIfANewWindowMustBeOpenedOnClickOnBookmark").'</td></tr>';

	// Owner
	print '<tr><td>'.$langs->trans("Owner").'</td><td>';
	print $form->select_dolusers(isset($_POST['userid'])?$_POST['userid']:$user->id, 'userid', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
	print '</td><td class="hideonsmartphone">&nbsp;</td></tr>';

	// Position
	print '<tr><td>'.$langs->trans("Position").'</td><td>';
	print '<input class="flat" name="position" size="5" value="'.(isset($_POST["position"])?$_POST["position"]:$object->position).'">';
	print '</td><td class="hideonsmartphone">&nbsp;</td></tr>';

	print '</table>';

	dol_fiche_end();

	print '<div align="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("CreateBookmark").'" name="create"> &nbsp; ';
	print '<input type="submit" class="button" value="'.$langs->trans("Cancel").'" name="cancel">';
	print '</div>';

	print '</form>';
}


if ($id > 0 && ! preg_match('/^add/i',$action))
{
	/*
	 * Fact bookmark mode or visually edition
	 */
	$object->fetch($id);

	$hselected = 'card';
	$head = array(
		array(
			'',
			$langs->trans('Card'),
			'card'
		)
	);

	if ($action == 'edit')
	{
		print '<form name="edit" method="POST" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		print '<input type="hidden" name="urlsource" value="'.DOL_URL_ROOT.'/bookmarks/card.php?id='.$object->id.'">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}


	dol_fiche_head($head, $hselected, $langs->trans("Bookmark"), -1, 'bookmark');

	$linkback = '<a href="'.DOL_URL_ROOT.'/bookmarks/list.php">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', '', '', 0, '', '', 0);

    print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';
	print '<table class="border" width="100%">';

	print '<tr><td class="titlefield">';
	if ($action == 'edit') {
		print '<span class="fieldrequired">';
	}

	print $langs->trans("BookmarkTitle");

	if ($action == 'edit') {
		print '</span>';
	}

	print '</td><td>';
	if ($action == 'edit') print '<input class="flat minwidth200" name="title" value="'.(isset($_POST["title"])?GETPOST("title",'',2):$object->title).'">';
	else print $object->title;
	print '</td></tr>';

	print '<tr><td>';
	if ($action == 'edit') {
		print '<span class="fieldrequired">';
	}
	print $langs->trans("UrlOrLink");
	if ($action == 'edit') {
		print '</span>';
	}
	print '</td><td>';
	if ($action == 'edit') print '<input class="flat" name="url" size="80" value="'.(isset($_POST["url"])?$_POST["url"]:$object->url).'">';
	else print '<a href="'.(preg_match('/^http/i',$object->url)?$object->url:DOL_URL_ROOT.$object->url).'"'.($object->target?' target="_blank"':'').'>'.$object->url.'</a>';
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("BehaviourOnClick").'</td><td>';
	if ($action == 'edit')
	{
		$liste=array(1=>$langs->trans("OpenANewWindow"),0=>$langs->trans("ReplaceWindow"));
		print $form->selectarray('target',$liste,isset($_POST["target"])?$_POST["target"]:$object->target);
	}
	else
	{
		if ($object->target == 0) print $langs->trans("ReplaceWindow");
		if ($object->target == 1) print $langs->trans("OpenANewWindow");
	}
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Owner").'</td><td>';
	if ($action == 'edit' && $user->admin)
	{
		print $form->select_dolusers(isset($_POST['userid'])?$_POST['userid']:($object->fk_user?$object->fk_user:''), 'userid', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
	}
	else
	{
		if ($object->fk_user)
		{
			$fuser=new User($db);
			$fuser->fetch($object->fk_user);
			print $fuser->getNomUrl(1);
		}
		else
		{
			print $langs->trans("Public");
		}
	}
	print '</td></tr>';

	// Position
	print '<tr><td>'.$langs->trans("Position").'</td><td>';
	if ($action == 'edit') print '<input class="flat" name="position" size="5" value="'.(isset($_POST["position"])?$_POST["position"]:$object->position).'">';
	else print $object->position;
	print '</td></tr>';

	// Date creation
	print '<tr><td>'.$langs->trans("DateCreation").'</td><td>'.dol_print_date($object->datec,'dayhour').'</td></tr>';

	print '</table>';

	print '</div>';

	dol_fiche_end();

	if ($action == 'edit')
	{
		print '<div align="center"><input class="button" type="submit" name="save" value="'.$langs->trans("Save").'"> &nbsp; &nbsp; <input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></div>';
		print '</form>';
	}


	// Buttons

	print "<div class=\"tabsAction\">\n";

	// Edit
	if ($user->rights->bookmark->creer && $action != 'edit')
	{
		print "  <a class=\"butAction\" href=\"".$_SERVER["PHP_SELF"]."?id=".$object->id."&amp;action=edit\">".$langs->trans("Edit")."</a>\n";
	}

	// Remove
	if ($user->rights->bookmark->supprimer && $action != 'edit')
	{
		print "  <a class=\"butActionDelete\" href=\"list.php?bid=".$object->id."&amp;action=delete\">".$langs->trans("Delete")."</a>\n";
	}

	print '</div>';
}

// End of page
llxFooter();
$db->close();
