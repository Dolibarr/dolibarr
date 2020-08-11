<?php
/* Copyright (C) 2005-2018 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010-2012 Destailleur Laurent <eldy@users.sourceforge.net>
 * Copyright (C) 2014 	   Henry Florian <florian.henry@open-concept.pro>
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
 *	\file       htdocs/adherents/type_translation.php
 *	\ingroup    product
 *	\brief      Member translation page
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';

// Load translation files required by the page
$langs->loadLangs(array('members', 'languages'));

$id = GETPOST('rowid', 'int');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
if ($user->socid) $socid = $user->socid;
// Security check
$result = restrictedArea($user, 'adherent', $id, 'adherent_type');


/*
 * Actions
 */

// return to translation display if cancellation
if ($cancel == $langs->trans("Cancel"))
{
	$action = '';
}

if ($action == 'delete' && GETPOST('langtodelete', 'alpha'))
{
	$object = new AdherentType($db);
	$object->fetch($id);
	$object->delMultiLangs(GETPOST('langtodelete', 'alpha'), $user);
}

// Add translation
if ($action == 'vadd' && $cancel != $langs->trans("Cancel") && $user->rights->adherent->configurer)
{
	$object = new AdherentType($db);
	$object->fetch($id);
	$current_lang = $langs->getDefaultLang();

	// update of object
	if ( $_POST["forcelangprod"] == $current_lang )
	{
		$object->label			= $_POST["libelle"];
		$object->description	= dol_htmlcleanlastbr($_POST["desc"]);
		$object->other			= dol_htmlcleanlastbr($_POST["other"]);
	}
	else
	{
		$object->multilangs[$_POST["forcelangprod"]]["label"]		= $_POST["libelle"];
		$object->multilangs[$_POST["forcelangprod"]]["description"]	= dol_htmlcleanlastbr($_POST["desc"]);
		$object->multilangs[$_POST["forcelangprod"]]["other"]		= dol_htmlcleanlastbr($_POST["other"]);
	}

	// backup into database
	if ( $object->setMultiLangs($user) > 0 )
	{
		$action = '';
	}
	else
	{
		$action = 'add';
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// Edit translation
if ($action == 'vedit' && $cancel != $langs->trans("Cancel") && $user->rights->adherent->configurer)
{
	$object = new AdherentType($db);
	$object->fetch($id);
	$current_lang = $langs->getDefaultLang();

	foreach ($object->multilangs as $key => $value) // saving new values in the object
	{
		if ($key == $current_lang)
		{
			$object->label			= $_POST["libelle-".$key];
			$object->description = dol_htmlcleanlastbr($_POST["desc-".$key]);
			$object->other			= dol_htmlcleanlastbr($_POST["other-".$key]);
		}
		else
		{
			$object->multilangs[$key]["label"]			= $_POST["libelle-".$key];
			$object->multilangs[$key]["description"] = dol_htmlcleanlastbr($_POST["desc-".$key]);
			$object->multilangs[$key]["other"]			= dol_htmlcleanlastbr($_POST["other-".$key]);
		}
	}

	if ($object->setMultiLangs($user) > 0)
	{
		$action = '';
	}
	else
	{
		$action = 'edit';
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// Delete translation
if ($action == 'vdelete' && $cancel != $langs->trans("Cancel") && $user->rights->adherent->configurer)
{
	$object = new AdherentType($db);
	$object->fetch($id);
	$langtodelete = GETPOST('langdel', 'alpha');


	if ($object->delMultiLangs($langtodelete, $user) > 0)
	{
		$action = '';
	}
	else
	{
		$action = 'edit';
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

$object = new AdherentType($db);
$result = $object->fetch($id);


/*
 * View
 */

$title = $langs->trans('MemberTypeCard');
$helpurl = '';
$shortlabel = dol_trunc($object->label, 16);

$title = $langs->trans('MemberType')." ".$shortlabel." - ".$langs->trans('Translation');
$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';

llxHeader('', $title, $helpurl);

$form = new Form($db);
$formadmin = new FormAdmin($db);

$head = member_type_prepare_head($object);
$titre = $langs->trans("MemberType".$object->type);

// Calculate $cnt_trans
$cnt_trans = 0;
if (!empty($object->multilangs))
{
    foreach ($object->multilangs as $key => $value)
    {
        $cnt_trans++;
    }
}


dol_fiche_head($head, 'translation', $titre, 0, 'group');

$linkback = '<a href="'.dol_buildpath('/adherents/type.php', 1).'">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'rowid', $linkback);

dol_fiche_end();



/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

print "\n<div class=\"tabsAction\">\n";

if ($action == '')
{
    if ($user->rights->produit->creer || $user->rights->service->creer)
    {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/adherents/type_translation.php?action=add&rowid='.$object->id.'">'.$langs->trans("Add").'</a>';
        if ($cnt_trans > 0) print '<a class="butAction" href="'.DOL_URL_ROOT.'/adherents/type_translation.php?action=edit&rowid='.$object->id.'">'.$langs->trans("Update").'</a>';
    }
}

print "\n</div>\n";



if ($action == 'edit')
{
	//WYSIWYG Editor
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="vedit">';
	print '<input type="hidden" name="rowid" value="'.$object->id.'">';

	if (!empty($object->multilangs))
	{
		foreach ($object->multilangs as $key => $value)
		{
			$s = picto_from_langcode($key);
			print "<br>".($s ? $s.' ' : '')." <b>".$langs->trans('Language_'.$key).":</b> ".'<a href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&action=delete&langtodelete='.$key.'">'.img_delete('', 'class="valigntextbottom"')."</a><br>";

			print '<div class="underbanner clearboth"></div>';
			print '<table class="border centpercent">';
			print '<tr><td class="tdtop titlefieldcreate fieldrequired">'.$langs->trans('Label').'</td><td><input name="libelle-'.$key.'" size="40" value="'.dol_escape_htmltag($object->multilangs[$key]["label"]).'"></td></tr>';
			print '<tr><td class="tdtop">'.$langs->trans('Description').'</td><td>';
			$doleditor = new DolEditor("desc-$key", $object->multilangs[$key]["description"], '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_3, '90%');
			$doleditor->Create();
			print '</td></tr>';
			print '</td></tr>';
			print '</table>';
		}
	}

	print '<br>';

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}
elseif ($action != 'add')
{
	if (!empty($object->multilangs))
	{
		foreach ($object->multilangs as $key => $value)
		{
			$s = picto_from_langcode($key);
			print ($s ? $s.' ' : '')." <b>".$langs->trans('Language_'.$key).":</b> ".'<a href="'.$_SERVER["PHP_SELF"].'?rowid='.$object->id.'&action=delete&langtodelete='.$key.'">'.img_delete('', 'class="valigntextbottom"').'</a>';

			print '<div class="fichecenter">';
			print '<div class="underbanner clearboth"></div>';
			print '<table class="border centpercent">';
			print '<tr><td class="titlefieldcreate">'.$langs->trans('Label').'</td><td>'.$object->multilangs[$key]["label"].'</td></tr>';
			print '<tr><td class="tdtop">'.$langs->trans('Description').'</td><td>'.$object->multilangs[$key]["description"].'</td></tr>';
			print '</table>';
			print '</div>';
		}
	}
	if (!$cnt_trans && $action != 'add') print '<div class="opacitymedium">'.$langs->trans('NoTranslation').'</div>';
}



/*
 * Form to add a new translation
 */

if ($action == 'add' && $user->rights->adherent->configurer)
{
	//WYSIWYG Editor
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

	print '<br>';
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="vadd">';
	print '<input type="hidden" name="rowid" value="'.GETPOST("rowid", 'int').'">';

	dol_fiche_head();

	print '<table class="border centpercent">';
	print '<tr><td class="tdtop titlefieldcreate fieldrequired">'.$langs->trans('Language').'</td><td>';
    print $formadmin->select_language('', 'forcelangprod', 0, $object->multilangs, 1);
	print '</td></tr>';
	print '<tr><td class="tdtop fieldrequired">'.$langs->trans('Label').'</td><td><input name="libelle" size="40"></td></tr>';
	print '<tr><td class="tdtop">'.$langs->trans('Description').'</td><td>';
	$doleditor = new DolEditor('desc', '', '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';

	print '<br>';
}

// End of page
llxFooter();
$db->close();
