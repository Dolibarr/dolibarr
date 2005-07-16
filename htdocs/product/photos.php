<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Regis Houssin        <regis.houssin@cap-networks.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/product/photos.php
        \ingroup    product
        \brief      Onglet photos de la fiche produit
        \version    $Revision$
*/

require("./pre.inc.php");
require("../propal.class.php");
require("../facture.class.php");

$langs->load("products");
$langs->load("bills");

$user->getrights('produit');
$user->getrights('propale');
$user->getrights('facture');
$mesg = '';

if (!$user->rights->produit->lire) accessforbidden();

$types[0] = $langs->trans("Product");
$types[1] = $langs->trans("Service");


/*
 * Actions
 */

if ($_POST["sendit"] && defined('MAIN_UPLOAD_DOC') && MAIN_UPLOAD_DOC == 1)
{
    if ($_GET["id"])
    {
        $product = new Product($db);
        $result = $product->fetch($_GET["id"]);

        // if (doliMoveFileUpload($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name']))

        //      var_dump($_FILES);

        $product->add_photo($conf->produit->dir_output, $_FILES['photofile']);
    }
}

if ($_GET["action"] == 'delete' && $_GET["file"]) 
{
    unlink($conf->produit->dir_output."/".$_GET["file"]);
}


/*
 *
 */

llxHeader("","",$langs->trans("CardProduct0"));


if ($_GET["id"])
{

    $product = new Product($db);
    $result = $product->fetch($_GET["id"]);

    if ($result)
    {
        /*
         *  En mode visu
         */

        $h=0;

        $head[$h][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
        $head[$h][1] = $langs->trans("Card");
        $h++;

        $head[$h][0] = DOL_URL_ROOT."/product/price.php?id=".$product->id;
        $head[$h][1] = $langs->trans("Price");
        $h++;

        if($product->type == 0)
        {
           if ($user->rights->barcode->lire)
           {
               if ($conf->barcode->enabled)
               {
                   $head[$h][0] = DOL_URL_ROOT."/product/barcode.php?id=".$product->id;
                   $head[$h][1] = $langs->trans("BarCode");
                   $h++;
               }
            }
        }


        $head[$h][0] = DOL_URL_ROOT."/product/photos.php?id=".$product->id;
        $head[$h][1] = $langs->trans("Photos");
        $hselected = $h;
        $h++;

        if ($conf->stock->enabled)
        {
            $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$product->id;
            $head[$h][1] = $langs->trans("Stock");
            $h++;
        }

        if ($conf->fournisseur->enabled)
        {
            $head[$h][0] = DOL_URL_ROOT."/product/fournisseurs.php?id=".$product->id;
            $head[$h][1] = $langs->trans("Suppliers");
            $h++;
        }

        $head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
        $head[$h][1] = $langs->trans('Statistics');
        $h++;

	//erics: pour créer des produits composés de x 'sous' produits
	$head[$h][0] = DOL_URL_ROOT."/product/pack.php?id=".$product->id;
	$head[$h][1] = $langs->trans('Packs');
	$h++;
	
        $head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?id=".$product->id;
        $head[$h][1] = $langs->trans('Bills');
        $h++;

        dolibarr_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);

        print($mesg);

        print '<table class="border" width="100%">';
        print '<tr>';
        print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="2">'.$product->ref.'</td>';
        print '</tr>';
        print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$product->libelle.'</td>';
        print '</tr>';

        // Prix
        print '<tr><td>'.$langs->trans("SellingPrice").'</td><td colspan="2">'.price($product->price).'</td></tr>';

        // Statut
        print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">';
        if ($product->envente) print $langs->trans("OnSell");
        else print $langs->trans("NotOnSell");
        print '</td></tr>';

        print "</table>\n";

        print "</div>\n";



        /* ************************************************************************** */
        /*                                                                            */
        /* Barre d'action                                                             */
        /*                                                                            */
        /* ************************************************************************** */

        print "\n<div class=\"tabsAction\">\n";

        if ($_GET["action"] != 'ajout_photo' && $user->rights->produit->creer && $conf->upload)
        {
            print '<a class="tabAction" href="'.DOL_URL_ROOT.'/product/photos.php?action=ajout_photo&amp;id='.$product->id.'">';
            print $langs->trans("AddPhoto").'</a>';
        }

        print "\n</div>\n";

        /*
         * Ajouter une photo
         */
        if ($_GET["action"] == 'ajout_photo' && $conf->upload && $user->rights->produit->creer)
        {
            print_titre($langs->trans("AddPhoto"));

            print '<form name="userfile" action="'.DOL_URL_ROOT.'/product/photos.php?id='.$product->id.'" enctype="multipart/form-data" METHOD="POST">';
            print '<input type="hidden" name="max_file_size" value="'.$conf->maxfilesize.'">';
            print '<input type="hidden" name="id" value="'.$product->id.'">';

            print '<table class="border" width="100%"><tr>';
            print '<td>'.$langs->trans("File").' ('.$langs->trans("Size").' <= '.$conf->maxfilesize.')</td>';
            print '<td><input type="file" class="flat" name="photofile"></td></tr>';

            print '<tr><td colspan="2" align="center">';
            print '<input type="submit" class="button" name="sendit" value="'.$langs->trans("Upload").'"> &nbsp; ';

            print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
            print '</table>';
            print '</form>';
        }

        // Affiche photos
        if ($_GET["action"] != 'ajout_photo')
        {
            $nbphoto=0;
            $nbbyrow=5;
            
            $pdir = get_exdir($product->id) . $product->id ."/photos/";
            $dir = $conf->produit->dir_output . '/'. $pdir;

            print '<br><table width="100%" valign="top" align="center" border="0" cellpadding="2" cellspacing="2">';
            
            foreach ($product->liste_photos($dir) as $obj)
            {
                $nbphoto++;

//                if ($nbbyrow && $nbphoto == 1) print '<table width="100%" valign="top" align="center" border="0" cellpadding="2" cellspacing="2">';

                if ($nbbyrow && ($nbphoto % $nbbyrow == 1)) print '<tr align=center valign=middle border=1>';
                if ($nbbyrow) print '<td width="'.ceil(100/$nbbyrow).'%" class="photo">';
                
                print '<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode($pdir.$obj->photo).'" alt="Taille origine" target="_blank">';

                // Si fichier vignette disponible, on l'utilise, sinon on utilise photo origine
                if ($obj->photo_vignette) $filename=$obj->photo_vignette;
                else $filename=$obj->photo;
                print '<img border="0" height="120" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=product&file='.urlencode($pdir.$filename).'">';

                print '</a>';
                print '<br>'.$langs->trans("File").': '.dolibarr_trunc($filename,16);
                if ($user->rights->produit->creer)
                {
                    print '<br>'.'<a href="'.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'&amp;action=delete&amp;file='.urlencode($pdir.$filename).'">'.img_delete().'</a>';
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
                print '</td></tr></table>';
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

llxFooter('$Date$ - $Revision$');
?>
