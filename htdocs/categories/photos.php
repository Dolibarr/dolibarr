<?php
/* Copyright (C) 2001-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2014       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *       \file       htdocs/categories/photos.php
 *       \ingroup    category
 *       \brief      Gestion des photos d'une categorie
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';

// Load translation files required by the page
$langs->loadlangs(array('categories', 'bills'));


$id      = GETPOST('id', 'int');
$label   = GETPOST('label', 'alpha');
$type    = GETPOST('type');
$action  = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm');

if ($id == '' && $label == '')
{
    dol_print_error('', 'Missing parameter id');
    exit();
}

// Security check
$result = restrictedArea($user, 'categorie', $id, '&category');

$object = new Categorie($db);
$result = $object->fetch($id, $label, $type);
if ($result <= 0) {
	dol_print_error($db, $object->error); exit;
}
$object->fetch_optionals();
if ($result <= 0) {
	dol_print_error($db, $object->error); exit;
}
$upload_dir = $conf->categorie->multidir_output[$object->entity];

if (is_numeric($type)) $type = Categorie::$MAP_ID_TO_CODE[$type]; // For backward compatibility

/*
 * Actions
 */

if (isset($_FILES['userfile']) && $_FILES['userfile']['size'] > 0 && $_POST["sendit"] && !empty($conf->global->MAIN_UPLOAD_DOC))
{
    if ($object->id) {
        $file = $_FILES['userfile'];
        if (is_array($file['name']) && count($file['name']) > 0)
        {
            foreach ($file['name'] as $i => $name)
            {
                if (empty($file['tmp_name'][$i]) || intval($conf->global->MAIN_UPLOAD_DOC) * 1000 <= filesize($file['tmp_name'][$i]))
                {
                    setEventMessage($file['name'][$i].' : '.$langs->trans(empty($file['tmp_name'][$i]) ? 'ErrorFailedToSaveFile' : 'MaxSizeForUploadedFiles'));
                    unset($file['name'][$i], $file['type'][$i], $file['tmp_name'][$i], $file['error'][$i], $file['size'][$i]);
                }
            }
        }

        if (!empty($file['tmp_name'])) {
            $object->add_photo($upload_dir, $file);
        }
    }
}

if ($action == 'confirm_delete' && $_GET["file"] && $confirm == 'yes' && $user->rights->categorie->creer)
{
    $object->delete_photo($upload_dir."/".$_GET["file"]);
}

if ($action == 'addthumb' && $_GET["file"])
{
    $object->addThumbs($upload_dir."/".$_GET["file"]);
}


/*
 * View
 */

llxHeader("", "", $langs->trans("Categories"));

$form = new Form($db);
$formother = new FormOther($db);

if ($object->id)
{
	$title = Categorie::$MAP_TYPE_TITLE_AREA[$type];

	$head = categories_prepare_head($object, $type);


	dol_fiche_head($head, 'photos', $langs->trans($title), -1, 'category');

	$linkback = '<a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("BackToList").'</a>';
	$object->next_prev_filter = ' type = '.$object->type;
	$object->ref = $object->label;
	$morehtmlref = '<br><div class="refidno"><a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("Root").'</a> >> ';
	$ways = $object->print_all_ways(" &gt;&gt; ", '', 1);
	foreach ($ways as $way)
	{
	    $morehtmlref .= $way."<br>\n";
	}
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'label', $linkback, ($user->socid ? 0 : 1), 'label', 'label', $morehtmlref, '&type='.$type, 0, '', '', 1);

	/*
	 * Confirmation de la suppression de photo
	*/
	if ($action == 'delete')
	{
	    print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&type='.$type.'&file='.$_GET["file"], $langs->trans('DeletePicture'), $langs->trans('ConfirmDeletePicture'), 'confirm_delete', '', 0, 1);
	}

	print '<br>';

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Description
	print '<tr><td class="titlefield notopnoleft">';
	print $langs->trans("Description").'</td><td>';
	print dol_htmlentitiesbr($object->description);
	print '</td></tr>';

	// Color
	print '<tr><td class="notopnoleft">';
	print $langs->trans("Color").'</td><td>';
	print $formother->showColor($object->color);
	print '</td></tr>';

	print "</table>\n";
    print '</div>';

	dol_fiche_end();



	/* ************************************************************************** */
	/*                                                                            */
	/* Barre d'action                                                             */
	/*                                                                            */
	/* ************************************************************************** */

	print '<div class="tabsAction">'."\n";

	if ($action != 'ajout_photo' && $user->rights->categorie->creer)
	{
		if (!empty($conf->global->MAIN_UPLOAD_DOC))
		{
			print '<a class="butAction hideonsmartphone" href="'.$_SERVER['PHP_SELF'].'?action=ajout_photo&amp;id='.$object->id.'&amp;type='.$type.'">';
			print $langs->trans("AddPhoto").'</a>';
		}
		else
		{
			print '<a class="butActionRefused classfortooltip hideonsmartphone" href="#">';
			print $langs->trans("AddPhoto").'</a>';
		}
	}

	print '</div>'."\n";

	/*
	 * Ajouter une photo
	*/
	if ($action == 'ajout_photo' && $user->rights->categorie->creer && !empty($conf->global->MAIN_UPLOAD_DOC))
	{
		// Affiche formulaire upload
		$formfile = new FormFile($db);
		$formfile->form_attach_new_file($_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;type='.$type, $langs->trans("AddPhoto"), 1, '', $user->rights->categorie->creer, 50, $object, '', false, '', 0);
	}

	// Affiche photos
	if ($action != 'ajout_photo')
	{
		$nbphoto = 0;
		$nbbyrow = 5;

		$maxWidth = 160;
		$maxHeight = 120;

		$pdir = get_exdir($object->id, 2, 0, 0, $object, 'category').$object->id."/photos/";
		$dir = $upload_dir.'/'.$pdir;

		$listofphoto = $object->liste_photos($dir);

		if (is_array($listofphoto) && count($listofphoto))
		{
    		print '<br>';
            print '<table width="100%" valign="top" align="center">';

    		foreach ($listofphoto as $key => $obj)
    		{
    			$nbphoto++;

    			if ($nbbyrow && ($nbphoto % $nbbyrow == 1)) print '<tr align=center valign=middle border=1>';
    			if ($nbbyrow) print '<td width="'.ceil(100 / $nbbyrow).'%" class="photo">';

    			print '<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart=category&entity='.$object->entity.'&file='.urlencode($pdir.$obj['photo']).'" alt="Taille origine" target="_blank">';

    			// Si fichier vignette disponible, on l'utilise, sinon on utilise photo origine
    			if ($obj['photo_vignette'])
    			{
    				$filename = $obj['photo_vignette'];
    			}
    			else
    			{
    				$filename = $obj['photo'];
    			}

    			// Nom affiche
    			$viewfilename = $obj['photo'];

    			// Taille de l'image
    			$object->get_image_size($dir.$filename);
    			$imgWidth = ($object->imgWidth < $maxWidth) ? $object->imgWidth : $maxWidth;
    			$imgHeight = ($object->imgHeight < $maxHeight) ? $object->imgHeight : $maxHeight;

    			print '<img border="0" width="'.$imgWidth.'" height="'.$imgHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=category&entity='.$object->entity.'&file='.urlencode($pdir.$filename).'">';

    			print '</a>';
    			print '<br>'.$viewfilename;
    			print '<br>';

    			// On propose la generation de la vignette si elle n'existe pas et si la taille est superieure aux limites
    			if (!$obj['photo_vignette'] && preg_match('/(\.bmp|\.gif|\.jpg|\.jpeg|\.png)$/i', $obj['photo']) && ($object->imgWidth > $maxWidth || $object->imgHeight > $maxHeight))
    			{
    				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=addthumb&amp;type='.$type.'&amp;file='.urlencode($pdir.$viewfilename).'">'.img_picto($langs->trans('GenerateThumb'), 'refresh').'&nbsp;&nbsp;</a>';
    			}
    			if ($user->rights->categorie->creer)
    			{
    				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete&amp;type='.$type.'&amp;file='.urlencode($pdir.$viewfilename).'">';
    				print img_delete().'</a>';
    			}
    			if ($nbbyrow) print '</td>';
    			if ($nbbyrow && ($nbphoto % $nbbyrow == 0)) print '</tr>';
    		}

    		// Ferme tableau
    		while ($nbphoto % $nbbyrow)
    		{
    			print '<td width="'.ceil(100 / $nbbyrow).'%">&nbsp;</td>';
    			$nbphoto++;
    		}

    		print '</table>';
		}

		if ($nbphoto < 1)
		{
			print '<div class="opacitymedium">'.$langs->trans("NoPhotoYet")."</div>";
		}
	}
}
else
{
    print $langs->trans("ErrorUnknown");
}

// End of page
llxFooter();
$db->close();
