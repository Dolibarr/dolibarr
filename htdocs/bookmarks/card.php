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

$langs->load("bookmarks");
$langs->load("other");

// Security check
if (! $user->rights->bookmark->lire) {
    restrictedArea($user, 'bookmarks');
}

$id=GETPOST("id");
$action=GETPOST("action","alpha");
$title=GETPOST("title","alpha");
$url=GETPOST("url","alpha");
$target=GETPOST("target","alpha");
$userid=GETPOST("userid","int");
$position=GETPOST("position","int");
$backtopage=GETPOST('backtopage','alpha');

$bookmark=new Bookmark($db);


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

	if (GETPOST("cancel"))
	{
		if (empty($backtopage)) $backtopage=(GETPOST("urlsource")?GETPOST("urlsource"):((! empty($url))?$url:DOL_URL_ROOT.'/bookmarks/list.php'));
		header("Location: ".$backtopage);
		exit;
	}

	if ($action == 'update') $bookmark->fetch($_POST["id"]);
	// Check if null because user not admin can't set an user and send empty value here.
	if(!empty($userid))
		$bookmark->fk_user=$userid;
	$bookmark->title=$title;
	$bookmark->url=$url;
	$bookmark->target=$target;
	$bookmark->position=$position;

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
		$bookmark->favicon='none';

		if ($action == 'update') $res=$bookmark->update();
		else $res=$bookmark->create();

		if ($res > 0)
		{
			if (empty($backtopage)) $backtopage=(GETPOST("urlsource")?GETPOST("urlsource"):DOL_URL_ROOT.'/bookmarks/list.php');
			header("Location: ".$backtopage);
			exit;
		}
		else
		{
			if ($bookmark->errno == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$langs->load("errors");
				setEventMessages($langs->transnoentities("WarningBookmarkAlreadyExists"), null, 'warnings');
			}
			else
			{
				setEventMessages($bookmark->error, $bookmark->errors, 'errors');
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

$head[$h][0] = $_SERVER["PHP_SELF"].($bookmark->id?'id='.$bookmark->id:'');
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

	dol_fiche_head($head, $hselected, $langs->trans("Bookmark"),0,'bookmark');

	print '<table class="border" width="100%">';

	print '<tr><td width="25%" class="fieldrequired">'.$langs->trans("BookmarkTitle").'</td><td><input class="flat" name="title" size="30" value="'.$title.'"></td><td class="hideonsmartphone">'.$langs->trans("SetHereATitleForLink").'</td></tr>';

	print '<tr><td class="fieldrequired">'.$langs->trans("UrlOrLink").'</td><td><input class="flat" name="url" size="50" value="'.$url.'"></td><td class="hideonsmartphone">'.$langs->trans("UseAnExternalHttpLinkOrRelativeDolibarrLink").'</td></tr>';

	print '<tr><td>'.$langs->trans("BehaviourOnClick").'</td><td>';
	$liste=array(0=>$langs->trans("ReplaceWindow"),1=>$langs->trans("OpenANewWindow"));
	print $form->selectarray('target',$liste,1);
	print '</td><td class="hideonsmartphone">'.$langs->trans("ChooseIfANewWindowMustBeOpenedOnClickOnBookmark").'</td></tr>';

	print '<tr><td>'.$langs->trans("Owner").'</td><td>';
	print $form->select_dolusers(isset($_POST['userid'])?$_POST['userid']:$user->id, 'userid', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
	print '</td><td class="hideonsmartphone">&nbsp;</td></tr>';

	// Position
	print '<tr><td>'.$langs->trans("Position").'</td><td>';
	print '<input class="flat" name="position" size="5" value="'.(isset($_POST["position"])?$_POST["position"]:$bookmark->position).'">';
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
	$bookmark->fetch($id);

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
		print '<input type="hidden" name="id" value="'.$bookmark->id.'">';
		print '<input type="hidden" name="urlsource" value="'.DOL_URL_ROOT.'/bookmarks/card.php?id='.$bookmark->id.'">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}

	dol_fiche_head($head, $hselected, $langs->trans("Bookmark"),0,'bookmark');

	print '<table class="border" width="100%">';

	print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td>'.$bookmark->ref.'</td></tr>';

	print '<tr><td>';
	if ($action == 'edit') {
		print '<span class="fieldrequired">';
	}

	print $langs->trans("BookmarkTitle");

	if ($action == 'edit') {
		print '</span>';
	}

	print '</td><td>';
	if ($action == 'edit') print '<input class="flat" name="title" size="30" value="'.(isset($_POST["title"])?$_POST["title"]:$bookmark->title).'">';
	else print $bookmark->title;
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
	if ($action == 'edit') print '<input class="flat" name="url" size="80" value="'.(isset($_POST["url"])?$_POST["url"]:$bookmark->url).'">';
	else print '<a href="'.(preg_match('/^http/i',$bookmark->url)?$bookmark->url:DOL_URL_ROOT.$bookmark->url).'"'.($bookmark->target?' target="_blank"':'').'>'.$bookmark->url.'</a>';
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("BehaviourOnClick").'</td><td>';
	if ($action == 'edit')
	{
		$liste=array(1=>$langs->trans("OpenANewWindow"),0=>$langs->trans("ReplaceWindow"));
		print $form->selectarray('target',$liste,isset($_POST["target"])?$_POST["target"]:$bookmark->target);
	}
	else
	{
		if ($bookmark->target == 0) print $langs->trans("ReplaceWindow");
		if ($bookmark->target == 1) print $langs->trans("OpenANewWindow");
	}
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("Owner").'</td><td>';
	if ($action == 'edit' && $user->admin)
	{
		print $form->select_dolusers(isset($_POST['userid'])?$_POST['userid']:($bookmark->fk_user?$bookmark->fk_user:''), 'userid', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
	}
	else
	{
		if ($bookmark->fk_user)
		{
			$fuser=new User($db);
			$fuser->fetch($bookmark->fk_user);
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
	if ($action == 'edit') print '<input class="flat" name="position" size="5" value="'.(isset($_POST["position"])?$_POST["position"]:$bookmark->position).'">';
	else print $bookmark->position;
	print '</td></tr>';

	// Date creation
	print '<tr><td>'.$langs->trans("DateCreation").'</td><td>'.dol_print_date($bookmark->datec,'dayhour').'</td></tr>';

	print '</table>';

	dol_fiche_end();

	if ($action == 'edit')
	{
		print '<div align="center"><input class="button" type="submit" name="save" value="'.$langs->trans("Save").'"> &nbsp; &nbsp; <input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></div>';
		print '</form>';
	}


	print "<div class=\"tabsAction\">\n";

	// Edit
	if ($user->rights->bookmark->creer && $action != 'edit')
	{
		print "  <a class=\"butAction\" href=\"".$_SERVER["PHP_SELF"]."?id=".$bookmark->id."&amp;action=edit\">".$langs->trans("Edit")."</a>\n";
	}

	// Remove
	if ($user->rights->bookmark->supprimer && $action != 'edit')
	{
		print "  <a class=\"butActionDelete\" href=\"list.php?bid=".$bookmark->id."&amp;action=delete\">".$langs->trans("Delete")."</a>\n";
	}

	print '</div>';

}


llxFooter();

$db->close();
