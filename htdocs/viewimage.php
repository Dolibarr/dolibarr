<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 *		\file       htdocs/viewimage.php
 *		\brief      Wrapper permettant l'affichage de fichiers images Dolibarr
 *      \remarks    L'appel est viewimage.php?file=pathrelatifdufichier&modulepart=repfichierconcerne
 *		\version    $Id$
 */

define('NOTOKENRENEWAL',1); // Disables token renewal

// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
$action = isset($_GET["action"])?$_GET["action"]:'';
$original_file = isset($_GET["file"])?$_GET["file"]:'';
$modulepart = isset($_GET["modulepart"])?$_GET["modulepart"]:'';
$urlsource = isset($_GET["urlsource"])?$_GET["urlsource"]:'';

// Pour autre que companylogo, on charge environnement + info issus de logon comme le user
if (($modulepart == 'companylogo') && ! defined("NOLOGIN")) define("NOLOGIN",1);

if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX','1');

require("./main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');


// C'est un wrapper, donc header vierge
function llxHeader() { }


// Define mime type
$type = 'application/octet-stream';
if (! empty($_GET["type"])) $type=$_GET["type"];
else $type=dol_mimetype($original_file);

// Suppression de la chaine de caractere ../ dans $original_file
$original_file = str_replace("../","/", $original_file);

$accessallowed=0;
if ($modulepart)
{
	// Check permissions and define directory

	// Wrapping pour les photo utilisateurs
	if ($modulepart == 'companylogo')
	{
		$accessallowed=1;
		$original_file=$conf->mycompany->dir_output.'/logos/'.$original_file;
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
		$original_file=$conf->fournisseur->dir_output.'/commande/temp/'.$original_file;
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
		$original_file=$conf->fournisseur->dir_output.'/facture/temp/'.$original_file;
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
	elseif (preg_match('/^productstats_/i',$modulepart))
	{
		$user->getrights('produit');
		if ($user->rights->produit->lire || $user->rights->service->lire)
		{
			$accessallowed=1;
		}
		$original_file=(!empty($conf->produit->dir_temp)?$conf->produit->dir_temp:$conf->service->dir_temp).'/'.$original_file;
	}

	// Wrapping for products or services
	elseif ($modulepart == 'product')
	{
		$user->getrights('produit');
		if ($user->rights->produit->lire || $user->rights->service->lire)
		{
			$accessallowed=1;
		}
		$original_file=(!empty($conf->produit->dir_output)?$conf->produit->dir_output:$conf->service->dir_output).'/'.$original_file;
	}

	// Wrapping for categories
	elseif ($modulepart == 'category')
	{
		$user->getrights('categorie');
		if ($user->rights->categorie->lire)
		{
			$accessallowed=1;
		}
		$original_file=$conf->categorie->dir_output.'/'.$original_file;
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
if (preg_match('/\.\./',$original_file) || preg_match('/[<>|]/',$original_file))
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
else					// Open and return file
{
	clearstatcache();

	// Output files on browser
	dol_syslog("viewimage.php return file $original_file content-type=$type");
	$original_file_osencoded=dol_osencode($original_file);

	// This test if file exists should be useless. We keep it to find bug more easily
	if (! file_exists($original_file_osencoded))
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

	readfile($original_file_osencoded);
}

?>
