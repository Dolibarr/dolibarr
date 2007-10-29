<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur   <eldy@users.sourceforge.net>
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
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

$langs->load("other");
$langs->load("products");

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


/*
 *
 */
 
$html = new Form($db);

llxHeader("","",$langs->trans("CardProduct".$product->type));


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

	$head=product_prepare_head($product, $user);
	$titre=$langs->trans("CardProduct".$product->type);
	dolibarr_fiche_head($head, 'documents', $titre);


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
	print $html->showrefnav($product,'ref');
    print '</td>';
    print '</tr>';

    // Libelle
    print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$product->libelle.'</td></tr>';

    // Prix
    print '<tr><td>'.$langs->trans("SellingPrice").'</td><td colspan="2">';
	if ($product->price_base_type == 'TTC')
	{
		print price($product->price_ttc).' '.$langs->trans($product->price_base_type);
	}
	else
	{
		print price($product->price).' '.$langs->trans($product->price_base_type);
	}
	print '</td></tr>';

    // Statut
    print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">';
	print $product->getLibStatut(2);
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';
    print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
    print '</table>';

    print '</div>';


    // Affiche formulaire upload
	$html=new Form($db);
	$html->form_attach_new_file('document.php?id='.$product->id);

   
    $errorlevel=error_reporting();
	error_reporting(0);
	$handle=opendir($upload_dir);
	error_reporting($errorlevel);

	print '<table width="100%" class="noborder">';
	
	// Affiche liste des documents existant
  print_titre($langs->trans("AttachedFiles"));
  
  print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('Document').'</td>';
	print '<td align="right">'.$langs->trans('Size').'</td>';
	print '<td align="center">'.$langs->trans('Date').'</td>';
	print '<td>&nbsp;</td>';
	print '</tr>';
	$var=true;
	
	if ($handle)
	{
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
				print '<td align="center">'.dolibarr_print_date(filemtime($upload_dir.'/'.$file),'dayhour').'</td>';
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
