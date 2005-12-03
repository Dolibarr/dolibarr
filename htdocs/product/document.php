<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005      Regis Houssin         <regis.houssin@cap-networks.com>
 * Copyright (C) 2005      Simon TOSSER         <simon@kornog-computing.com>
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
        \file       htdocs/product/document.php
        \ingroup    product
        \brief      Page des documents joints sur les produits
        \version    $Revision$
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

$langs->load("other");

$user->getrights('produit');

if (!$user->rights->produit->lire)
	accessforbidden();

$action=empty($_GET['action']) ? (empty($_POST['action']) ? '' : $_POST['action']) : $_GET['action'];

$product = new Product($db);
if ($_GET['id'] || $_GET["ref"])
{
    if ($_GET["ref"]) $result = $product->fetch('',$_GET["ref"]);
    if ($_GET["id"]) $result = $product->fetch($_GET["id"]);

    $prodref = sanitize_string($product->ref);
    $upload_dir = $conf->produit->dir_output.'/'.$prodref;
}


/*
 * Action envoie fichier
 */
if ($_POST["sendit"] && $conf->upload)
{
    /*
     * Creation répertoire si n'existe pas
     */
    if (! is_dir($upload_dir)) create_exdir($upload_dir);

    if (is_dir($upload_dir))
    {
        if (doliMoveFileUpload($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name']))
        {
            $mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
            //print_r($_FILES);
        }
        else
        {
            // Echec transfert (fichier dépassant la limite ?)
            $mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
            // print_r($_FILES);
        }
    }
}



llxHeader();


if ($product->id)
{
	if ( $error_msg )
	{ 
		echo '<div class="error">'.$error_msg.'</div><br>';
	}

	if ($action=='delete')
	{
		$file = $upload_dir . '/' . urldecode($_GET['urlfile']);
		dol_delete_file($file);
	}

	$h=0;

    $head[$h][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
    $head[$h][1] = $langs->trans("Card");
    $hselected = $h;
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
    $h++;

    if($product->type == 0)
    {
        if ($conf->stock->enabled)
        {
            $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$product->id;
            $head[$h][1] = $langs->trans("Stock");
            $h++;
        }
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
    /*
    $head[$h][0] = DOL_URL_ROOT."/product/pack.php?id=".$product->id;
    $head[$h][1] = $langs->trans('Packs');
    $h++;
    */

    $head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?id=".$product->id;
    $head[$h][1] = $langs->trans('Referers');
    $h++;

	$head[$h][0] = DOL_URL_ROOT.'/product/document.php?id='.$product->id;
	$head[$h][1] = $langs->trans('Documents');
	$hselected=$h;
	$h++;

    $titre=$langs->trans("CardProduct".$product->type);
    dolibarr_fiche_head($head, $hselected, $titre);

    // Construit liste des fichiers
    clearstatcache();

    $totalsize=0;
    $filearray=array();

    $errorlevel=error_reporting();
	error_reporting(0);
	$handle=opendir($upload_dir);
	error_reporting($errorlevel);
    if ($handle)
    {
        $i=0;
        while (($file = readdir($handle))!==false)
        {
            if (!is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
            {
                $filearray[$i]=$file;
                $totalsize+=filesize($upload_dir."/".$file);
                $i++;
            }
        }
        closedir($handle);
    }
    else
    {
//            print '<div class="error">'.$langs->trans("ErrorCanNotReadDir",$upload_dir).'</div>';
    }
    
    print '<table class="border" width="100%">';

    // Reference
    print '<tr>';
    print '<td width="28%">'.$langs->trans("Ref").'</td><td colspan="3">';
    $product->load_previous_next_ref();
    $previous_ref = $product->ref_previous?'<a href="'.$_SERVER["PHP_SELF"].'?ref='.$product->ref_previous.'">'.img_previous().'</a>':'';
    $next_ref     = $product->ref_next?'<a href="'.$_SERVER["PHP_SELF"].'?ref='.$product->ref_next.'">'.img_next().'</a>':'';
    if ($previous_ref || $next_ref) print '<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">';
    print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'">'.$product->ref.'</a>';
    if ($previous_ref || $next_ref) print '</td><td class="nobordernopadding" align="center" width="20">'.$previous_ref.'</td><td class="nobordernopadding" align="center" width="20">'.$next_ref.'</td></tr></table>';
    print '</td>';
    print '</tr>';

    // Libelle
    print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$product->libelle.'</td></tr>';

    print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';
    print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
    print '</table>';

    print '</div>';


    // Affiche forumlaire upload
    if (defined('MAIN_UPLOAD_DOC') && $conf->upload)
    {
		print_titre($langs->trans('AttachANewFile'));

		print '<form name="userfile" action="document.php?id='.$product->id.'" enctype="multipart/form-data" method="POST">';

        print '<table class="noborder" width="100%">';
        print '<tr><td width="50%" valign="top">';

        print '<input type="hidden" name="max_file_size" value="2000000">';
        print '<input class="flat" type="file" name="userfile" size="40" maxlength="80">';
        print ' &nbsp; ';
        print '<input type="submit" class="button" value="'.$langs->trans("Add").'" name="sendit">';

        print "</td></tr>";
        print "</table>";

        print '</form>';
        print '<br>';
    }
    
    $errorlevel=error_reporting();
	error_reporting(0);
	$handle=opendir($upload_dir);
	error_reporting($errorlevel);

	print '<table width="100%" class="noborder">';

	if ($handle)
	{
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('Document').'</td>';
		print '<td align="right">'.$langs->trans('Size').'</td>';
		print '<td align="center">'.$langs->trans('Date').'</td>';
		print '<td>&nbsp;</td>';
		print '</tr>';
		$var=true;
		while (($file = readdir($handle))!==false)
		{
			if (!is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
			{
				$var=!$var;
				print '<tr '.$bc[$var].'>';
				print '<td>';
				echo '<a href="'.DOL_URL_ROOT.'/document.php?modulepart=produit&file='.$prodref.'/'.urlencode($file).'">'.$file.'</a>';
				print "</td>\n";
				print '<td align="right">'.filesize($upload_dir.'/'.$file). ' bytes</td>';
				print '<td align="center">'.strftime('%d %b %Y %H:%M:%S',filemtime($upload_dir.'/'.$file)).'</td>';
				print '<td align="center">';
				if ($file == $propref . '.pdf')
				{
					echo '-';
				}
				else
				{
					echo '<a href="'.DOL_URL_ROOT.'/product/document.php?id='.$product->id.'&action=delete&urlfile='.urlencode($file).'">'.img_delete($langs->trans('Delete')).'</a>';
				}
				print "</td></tr>\n";
			}
		}
		closedir($handle);
	}
	print '</table>';

}
else
{
	print $langs->trans("UnkownError");
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
