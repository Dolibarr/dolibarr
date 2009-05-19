<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 * or see http://www.gnu.org/
 */

/**
		\file       htdocs/viewimage.php
		\brief      Wrapper permettant l'affichage de fichiers images Dolibarr
        \remarks    L'appel est viewimage.php?file=pathrelatifdufichier&modulepart=repfichierconcerne
		\version    $Id$
*/

$original_file = isset($_GET["file"])?urldecode($_GET["file"]):'';
$modulepart = urldecode($_GET["modulepart"]);
$type = isset($_GET["type"]) ? urldecode($_GET["type"]) : '';

// Define if we need master or master+main
$needmasteronly=false;
if ($modulepart == 'companylogo') $needmasteronly=true;

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

// Load master or main
if ($needmasteronly)
{
	// Pour companylogo, on charge juste environnement sans logon qui charge le user
	require("./master.inc.php");
}
else
{
	if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU','1');
	if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML','1');
	if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX','1');

	// Pour autre que companylogo, on charge environnement + info issus de logon comme le user
	require("./main.inc.php");
	// master.inc.php is included in main.inc.php
}


// C'est un wrapper, donc header vierge
function llxHeader() { }



// Protection, on interdit les .. dans les chemins
$original_file = eregi_replace('\.\.','',$original_file);



$accessallowed=0;
if ($modulepart)
{
    // Check permissions and define directory

    // Wrapping pour les photo utilisateurs
    if ($modulepart == 'companylogo')
    {
    	$accessallowed=1;
   		$original_file=$conf->societe->dir_output.'/logos/'.$original_file;
    }

    // Wrapping pour les photos utilisateurs
    elseif ($modulepart == 'userphoto')
    {
    	$accessallowed=1;
    	$original_file=$conf->user->dir_output.'/'.$original_file;
    }

    // Wrapping pour les photos adherents
    elseif ($modulepart == 'memberphoto')
    {
    	$accessallowed=1;
    	$original_file=$conf->adherent->dir_output.'/'.$original_file;
    }

    // Wrapping pour les apercu factures
    elseif ($modulepart == 'apercufacture')
    {
        $user->getrights('facture');
        if ($user->rights->facture->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->facture->dir_output.'/'.$original_file;
    }

    // Wrapping pour les apercu propal
    elseif ($modulepart == 'apercupropal')
    {
        $user->getrights('propale');
        if ($user->rights->propale->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->propale->dir_output.'/'.$original_file;
    }

    // Wrapping pour les apercu commande
    elseif ($modulepart == 'apercucommande')
    {
        $user->getrights('commande');
        if ($user->rights->commande->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->commande->dir_output.'/'.$original_file;
    }

    // Wrapping pour les apercu intervention
    elseif ($modulepart == 'apercufichinter')
    {
        $user->getrights('ficheinter');
        if ($user->rights->ficheinter->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->ficheinter->dir_output.'/'.$original_file;
    }

    // Wrapping pour les images des stats propales
    elseif ($modulepart == 'propalstats')
    {
        $user->getrights('propale');
        if ($user->rights->propale->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->propale->dir_temp.'/'.$original_file;
    }

    // Wrapping pour les images des stats commandes
    elseif ($modulepart == 'orderstats')
    {
        $user->getrights('commande');
        if ($user->rights->commande->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->commande->dir_temp.'/'.$original_file;
    }
    elseif ($modulepart == 'orderstatssupplier')
    {
        $user->getrights('fournisseur');
        if ($user->rights->fournisseur->commande->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->fournisseur->commande->dir_temp.'/'.$original_file;
    }

    // Wrapping pour les images des stats factures
    elseif ($modulepart == 'billstats')
    {
        $user->getrights('facture');
        if ($user->rights->facture->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->facture->dir_temp.'/'.$original_file;
    }
    elseif ($modulepart == 'billstatssupplier')
    {
        $user->getrights('fourn');
        if ($user->rights->fournisseur->facture->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->fournisseur->facture->dir_temp.'/'.$original_file;
    }

    // Wrapping pour les images des stats expeditions
    elseif ($modulepart == 'expeditionstats')
    {
        $user->getrights('expedition');
        if ($user->rights->expedition->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->expedition->dir_temp.'/'.$original_file;
    }

    // Wrapping pour les images des stats produits
    elseif (eregi('^productstats_',$modulepart))
    {
        $user->getrights('produit');
        if ($user->rights->produit->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->produit->dir_temp.'/'.$original_file;
    }

    // Wrapping pour les produits
    elseif ($modulepart == 'product')
    {
        $user->getrights('produit');
        if ($user->rights->produit->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->produit->dir_output.'/'.$original_file;
    }

    // Wrapping pour les prelevements
    elseif ($modulepart == 'prelevement')
    {
        $user->getrights('prelevement');
        if ($user->rights->prelevement->bons->lire) $accessallowed=1;

        $original_file=$conf->prelevement->dir_output.'/receipts/'.$original_file;
    }

    // Wrapping pour les graph telephonie
    elseif ($modulepart == 'telephoniegraph')
    {
        $user->getrights('telephonie');
        if ($user->rights->telephonie->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->telephonie->dir_temp.'/'.$original_file;
    }

    // Wrapping pour les graph energie
    elseif ($modulepart == 'energie')
    {
      $accessallowed=1;
      $original_file=$conf->energie->dir_temp.'/'.$original_file;
    }

    // Wrapping pour les graph bank
    elseif ($modulepart == 'bank')
    {
      $accessallowed=1;
      $original_file=$conf->banque->dir_temp.'/'.$original_file;
    }

    // Wrapping pour les images wysiwyg
    elseif ($modulepart == 'fckeditor')
    {
      $accessallowed=1;
      $original_file=$conf->fckeditor->dir_output.'/'.$original_file;
    }

    // Wrapping pour les images wysiwyg mailing
    elseif ($modulepart == 'mailing')
    {
      $accessallowed=1;
      $original_file=$conf->mailing->dir_output.'/'.$original_file;
    }

    // Wrapping pour les graph energie
    elseif ($modulepart == 'graph_stock')
    {
      $accessallowed=1;
      $original_file=$conf->stock->dir_temp.'/'.$original_file;
    }

    // Wrapping pour les graph fournisseurs
    elseif ($modulepart == 'graph_fourn')
    {
      $accessallowed=1;
      $original_file=$conf->fournisseur->dir_temp.'/'.$original_file;
    }

    // Wrapping pour les graph des produits
    elseif ($modulepart == 'graph_product')
    {
      $accessallowed=1;
      $original_file=$conf->produit->dir_temp.'/'.$original_file;
    }

    // Wrapping pour les code barre
    elseif ($modulepart == 'barcode')
    {
    	$accessallowed=1;
		// If viewimage is called for barcode, we try to output an image on the fly,
		// with not build of file on disk.
    	//$original_file=$conf->barcode->dir_temp.'/'.$original_file;
    	$original_file='';
    }
    
    // Wrapping pour les icones de background des mailings
    elseif ($modulepart == 'iconmailing')
    {
      $accessallowed=1;
      $original_file=$conf->mailing->dir_temp.'/'.$original_file;
    }

    // Wrapping generique (allows any module to open a file if file is in directory
    // called DOL_DATA_ROOT/modulepart).
    else
    {
    	$accessallowed=1;
    	$original_file=DOL_DATA_ROOT.'/'.$modulepart.'/'.$original_file;
    }
}

// Security:
// Limit access if permissions are wrong
if (! $accessallowed)
{
    accessforbidden();
}

// Security:
// On interdit les remontees de repertoire ainsi que les pipe dans
// les noms de fichiers.
if (eregi('\.\.',$original_file) || eregi('[<>|]',$original_file))
{
	$langs->load("main");
	dol_syslog("Refused to deliver file ".$original_file);
	// Do no show plain path in shown error message
	dol_print_error(0,$langs->trans("ErrorFileNameInvalid",$_GET["file"]));
	exit;
}



if ($modulepart == 'barcode')
{
	$generator=$_GET["generator"];
	$code=$_GET["code"];
	$encoding=$_GET["encoding"];
	$readable=$_GET["readable"]?$_GET["readable"]:"Y";

	// Output files with barcode generators
	foreach ($conf->file->dol_document_root as $dirroot)
	{
		$dir=$dirroot . "/includes/modules/barcode/";
		$result=@include_once($dir.$generator.".modules.php");
		if ($result) break;
	}

	// Chargement de la classe de codage
	$classname = "mod".ucfirst($generator);
	$module = new $classname($db);
	if ($module->encodingIsSupported($encoding))
	{
		$result=$module->buildBarCode($code,$encoding,$readable);
	}
}
else
{
	// Ouvre et renvoi fichier
	clearstatcache();

	// Output files on disk
	$filename = basename($original_file);

	dol_syslog("viewimage.php return file $original_file $filename content-type=$type");

	if (! file_exists($original_file))
	{
		$langs->load("main");
		dol_print_error(0,$langs->trans("ErrorFileDoesNotExists",$_GET["file"]));
		exit;
	}

	// Les drois sont ok et fichier trouve
	if ($type)
	{
	  header('Content-type: '.$type);
	}
	else
	{
	  header('Content-type: image/png');
	}

	readfile($original_file);
}

?>
