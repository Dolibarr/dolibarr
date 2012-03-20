<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *       \file       htdocs/categories/photos.php
 *       \ingroup    category
 *       \brief      Gestion des photos d'une categorie
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/categories.lib.php");

$langs->load("categories");
$langs->load("bills");


$mesg = '';

$id=GETPOST('id','int');
$ref=GETPOST('ref');
$type=GETPOST('type');
$action=GETPOST('action');
$confirm=GETPOST('confirm');

if ($id == "")
{
    dol_print_error('','Missing parameter id');
    exit();
}

// Security check
$result = restrictedArea($user, 'categorie', $id, '&category');

$object = new Categorie($db);

/*
 * Actions
 */

if ($_FILES['userfile']['size'] > 0 && $_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
    if ($id)
    {
        $result = $object->fetch($id);

        $result = $object->add_photo($conf->categorie->dir_output, $_FILES['userfile']);
    }
}

if ($action == 'confirm_delete' && $_GET["file"] && $confirm == 'yes' && $user->rights->categorie->creer)
{
    $object->delete_photo($conf->categorie->dir_output."/".$_GET["file"]);
}

if ($action == 'addthumb' && $_GET["file"])
{
    $object->add_thumb($conf->categorie->dir_output."/".$_GET["file"]);
}


/*
 * View
 */

llxHeader("","",$langs->trans("Categories"));

$form = new Form($db);

if (!empty($id) || !empty($ref))
{
    $result = $object->fetch($id);

    if ($result)
    {
        $title=$langs->trans("ProductsCategoryShort");
        if ($type == 0) $title=$langs->trans("ProductsCategoryShort");
        elseif ($type == 1) $title=$langs->trans("SuppliersCategoryShort");
        elseif ($type == 2) $title=$langs->trans("CustomersCategoryShort");
        elseif ($type == 3) $title=$langs->trans("MembersCategoryShort");

        $head = categories_prepare_head($object,$type);
        dol_fiche_head($head, 'photos', $title, 0, 'category');

        /*
         * Confirmation de la suppression de photo
         */
        if ($action == 'delete')
        {
            $ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&type='.$type.'&file='.$_GET["file"], $langs->trans('DeletePicture'), $langs->trans('ConfirmDeletePicture'), 'confirm_delete', '', 0, 1);
            if ($ret == 'html') print '<br>';
        }

        print($mesg);

        print '<table class="border" width="100%">';

        // Path of category
        print '<tr><td width="20%" class="notopnoleft">';
        $ways = $object->print_all_ways();
        print $langs->trans("Ref").'</td><td>';
        print '<a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("Root").'</a> >> ';
        foreach ($ways as $way)
        {
            print $way."<br>\n";
        }
        print '</td></tr>';

        // Description
        print '<tr><td width="20%" class="notopnoleft">';
        print $langs->trans("Description").'</td><td>';
        print nl2br($object->description);
        print '</td></tr>';

        // Visibility
        /*		if ($type == 0 && $conf->global->CATEGORY_ASSIGNED_TO_A_CUSTOMER)
        {
        if ($object->socid)
        {
        $soc = new Societe($db);
        $soc->fetch($object->socid);

        print '<tr><td width="20%" class="notopnoleft">';
        print $langs->trans("AssignedToTheCustomer").'</td><td>';
        print $soc->getNomUrl(1);
        print '</td></tr>';

        $catsMeres = $object->get_meres ();

        if ($catsMeres < 0)
        {
        dol_print_error();
        }
        else if (count($catsMeres) > 0)
        {
        print '<tr><td width="20%" class="notopnoleft">';
        print $langs->trans("CategoryContents").'</td><td>';
        print ($object->visible ? $langs->trans("Visible") : $langs->trans("Invisible"));
        print '</td></tr>';
        }
        }
        else
        {
        print '<tr><td width="20%" class="notopnoleft">';
        print $langs->trans("CategoryContents").'</td><td>';
        print ($object->visible ? $langs->trans("Visible") : $langs->trans("Invisible"));
        print '</td></tr>';
        }
        }
        else
        {
        print '<tr><td width="20%" class="notopnoleft">';
        print $langs->trans("CategoryContents").'</td><td>';
        print ($object->visible ? $langs->trans("Visible") : $langs->trans("Invisible"));
        print '</td></tr>';
        }
        */

        print "</table>\n";

        print "</div>\n";



        /* ************************************************************************** */
        /*                                                                            */
        /* Barre d'action                                                             */
        /*                                                                            */
        /* ************************************************************************** */

        print "\n<div class=\"tabsAction\">\n";

        if ($action != 'ajout_photo' && $user->rights->produit->creer)
        {
            if (! empty($conf->global->MAIN_UPLOAD_DOC))
            {
                print '<a class="butAction" href="'.DOL_URL_ROOT.'/categories/photos.php?action=ajout_photo&amp;id='.$object->id.'&amp;type='.$type.'">';
                print $langs->trans("AddPhoto").'</a>';
            }
            else
            {
                print '<a class="butActionRefused" href="#">';
                print $langs->trans("AddPhoto").'</a>';
            }
        }

        print "\n</div>\n";

        /*
         * Ajouter une photo
         */
        if ($action == 'ajout_photo' && $user->rights->categorie->creer && ! empty($conf->global->MAIN_UPLOAD_DOC))
        {
            // Affiche formulaire upload
            $formfile=new FormFile($db);
            $formfile->form_attach_new_file(DOL_URL_ROOT.'/categories/photos.php?id='.$object->id.'&amp;type='.$type,$langs->trans("AddPhoto"),1);
        }

        // Affiche photos
        if ($action != 'ajout_photo')
        {
            $nbphoto=0;
            $nbbyrow=5;

            $maxWidth = 160;
            $maxHeight = 120;

            $pdir = get_exdir($object->id,2) . $object->id ."/photos/";
            $dir = $conf->categorie->dir_output.'/'.$pdir;

            print '<br>';
            print '<table width="100%" valign="top" align="center" border="0" cellpadding="2" cellspacing="2">';

            foreach ($object->liste_photos($dir) as $key => $obj)
            {
                $nbphoto++;


                if ($nbbyrow && ($nbphoto % $nbbyrow == 1)) print '<tr align=center valign=middle border=1>';
                if ($nbbyrow) print '<td width="'.ceil(100/$nbbyrow).'%" class="photo">';

                print '<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart=categorie&file='.urlencode($pdir.$obj['photo']).'" alt="Taille origine" target="_blank">';

                // Si fichier vignette disponible, on l'utilise, sinon on utilise photo origine
                if ($obj['photo_vignette'])
                {
                    $filename='thumbs/'.$obj['photo_vignette'];
                }
                else
                {
                    $filename=$obj['photo'];
                }

                // Nom affiche
                $viewfilename=$obj['photo'];

                // Taille de l'image
                $object->get_image_size($dir.$filename);
                $imgWidth = ($object->imgWidth < $maxWidth) ? $object->imgWidth : $maxWidth;
                $imgHeight = ($object->imgHeight < $maxHeight) ? $object->imgHeight : $maxHeight;

                print '<img border="0" width="'.$imgWidth.'" height="'.$imgHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=categorie&file='.urlencode($pdir.$filename).'">';

                print '</a>';
                print '<br>'.$viewfilename;
                print '<br>';

                // On propose la generation de la vignette si elle n'existe pas et si la taille est superieure aux limites
                if (!$obj['photo_vignette'] && preg_match('/(\.bmp|\.gif|\.jpg|\.jpeg|\.png)$/i',$obj['photo']) && ($object->imgWidth > $maxWidth || $object->imgHeight > $maxHeight))
                {
                    print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'&amp;action=addthumb&amp;type='.$type.'&amp;file='.urlencode($pdir.$viewfilename).'">'.img_picto($langs->trans('GenerateThumb'),'refresh').'&nbsp;&nbsp;</a>';
                }
                if ($user->rights->categorie->creer)
                {
                    print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'&amp;action=delete&amp;type='.$type.'&amp;file='.urlencode($pdir.$viewfilename).'">';
                    print img_delete().'</a>';
                }
                if ($nbbyrow) print '</td>';
                if ($nbbyrow && ($nbphoto % $nbbyrow == 0)) print '</tr>';
            }

            // Ferme tableau
            while ($nbphoto % $nbbyrow)
            {
                print '<td width="'.ceil(100/$nbbyrow).'%">&nbsp;</td>';
                $nbphoto++;
            }

            if ($nbphoto < 1)
            {
                print '<tr align=center valign=middle border=1><td class="photo">';
                print "<br>".$langs->trans("NoPhotoYet")."<br><br>";
                print '</td></tr>';
            }

            print '</table>';
        }
    }
}
else
{
    print $langs->trans("ErrorUnknown");
}



$db->close();

llxFooter();
?>
