<?php
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon Tosser         <simon@kornog-computing.com>
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
 *	\file       htdocs/document.php
 *  \brief      Wrapper to download data files
 *  \version    $Id$
 *  \remarks    L'appel est document.php?file=pathrelatifdufichier&modulepart=repfichierconcerne
 */

$original_file = urldecode($_GET["file"]);
$modulepart = urldecode($_GET["modulepart"]);
$type = isset($_GET["type"]) ? urldecode($_GET["type"]) : '';

// Define if we need master or master+main
$needmasteronly=false;
//if ($modulepart == 'webcal') $needmasteronly=true;
//if ($modulepart == 'agenda') $needmasteronly=true;

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

// Load master or main
if ($needmasteronly)
{
	// For some download we don't need login
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
require_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');


// C'est un wrapper, donc header vierge
function llxHeader() { }

// Default encoding for HTTP output if no encoding can be found for file to download
//$encoding='ISO-8859-1';

$action = $_GET["action"];
$original_file = urldecode($_GET["file"]);
$modulepart = urldecode($_GET["modulepart"]);
$urlsource = urldecode($_GET["urlsource"]);

// Define mime type
$type = 'application/octet-stream';
if (! empty($_GET["type"])) $type=urldecode($_GET["type"]);
else $type=dol_mimetype($original_file);

// Define attachment (attachment=true to force choice popup 'open'/'save as')
$attachment = true;
if (eregi('\.sql$',$original_file))     { $attachment = true; }
if (eregi('\.html$',$original_file)) 	{ $attachment = false; }
if (eregi('\.csv$',$original_file))  	{ $attachment = true; }
if (eregi('\.tsv$',$original_file))  	{ $attachment = true; }
if (eregi('\.pdf$',$original_file))  	{ $attachment = true; }
if (eregi('\.xls$',$original_file))  	{ $attachment = true; }
if (eregi('\.jpg$',$original_file)) 	{ $attachment = true; }
if (eregi('\.png$',$original_file)) 	{ $attachment = true; }
if (eregi('\.tiff$',$original_file)) 	{ $attachment = true; }
if (eregi('\.vcs$',$original_file))  	{ $attachment = true; }
if (eregi('\.ics$',$original_file))  	{ $attachment = true; }
if (! empty($conf->global->MAIN_DISABLE_FORCE_SAVEAS)) $attachment=false;


// Suppression de la chaine de caractere ../ dans $original_file
$original_file = str_replace("../","/", "$original_file");
// find the subdirectory name as the reference
$refname=basename(dirname($original_file)."/");

$accessallowed=0;
$sqlprotectagainstexternals='';
if ($modulepart)
{
    // On fait une verification des droits et on definit le repertoire concerne

    // Wrapping pour les factures
    if ($modulepart == 'facture')
    {
        $user->getrights('facture');
        if ($user->rights->facture->lire || eregi('^specimen',$original_file))
        {
            $accessallowed=1;
        }
        $original_file=$conf->facture->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."facture WHERE ref='$refname'";
    }

	if ($modulepart == 'unpayed')
    {
        $user->getrights('facture');
        if ($user->rights->facture->lire || eregi('^specimen',$original_file))
        {
            $accessallowed=1;
        }
        $original_file=$conf->facture->dir_output.'/unpayed/temp/'.$original_file;
    }

    // Wrapping pour les fiches intervention
    if ($modulepart == 'ficheinter')
    {
        $user->getrights('ficheinter');
        if ($user->rights->ficheinter->lire || eregi('^specimen',$original_file))
        {
            $accessallowed=1;
        }
        $original_file=$conf->fichinter->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='$refname'";
    }

    // Wrapping pour les prelevements
    if ($modulepart == 'prelevement')
    {
        $user->getrights('prelevement');
        if ($user->rights->prelevement->bons->lire || eregi('^specimen',$original_file))
        {
            $accessallowed=1;
        }
        $original_file=$conf->prelevement->dir_output.'/'.$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."$modulepart WHERE ref='$refname'";
    }

    // Wrapping pour les propales
    if ($modulepart == 'propal')
    {
        $user->getrights('propale');
        if ($user->rights->propale->lire || eregi('^specimen',$original_file))
        {
            $accessallowed=1;
        }

        $original_file=$conf->propal->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."propal WHERE ref='$refname'";
    }
	 // Wrapping pour les commandes
    if ($modulepart == 'commande')
    {
        $user->getrights('commande');
        if ($user->rights->commande->lire || eregi('^specimen',$original_file))
        {
            $accessallowed=1;
        }
        $original_file=$conf->commande->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."commande WHERE ref='$refname'";
    }

    // Wrapping pour les commandes fournisseurs
    if ($modulepart == 'commande_fournisseur')
    {
        $user->getrights('fournisseur');
        if ($user->rights->fournisseur->commande->lire || eregi('^specimen',$original_file))
        {
            $accessallowed=1;
        }
        $original_file=$conf->fournisseur->commande->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."commande_fournisseur WHERE ref='$refname'";
    }

    // Wrapping pour les factures fournisseurs
    if ($modulepart == 'facture_fournisseur')
    {
        $user->getrights('fournisseur');
        if ($user->rights->fournisseur->facture->lire || eregi('^specimen',$original_file))
        {
            $accessallowed=1;
        }
        $original_file=$conf->fournisseur->facture->dir_output.'/'.$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."facture_fourn WHERE facnumber='$refname'";
    }

    // Wrapping pour les rapport de paiements
    if ($modulepart == 'facture_paiement')
    {
        $user->getrights('facture');
        if ($user->rights->facture->lire || eregi('^specimen',$original_file))
        {
            $accessallowed=1;
        }
        if ($user->societe_id > 0) $original_file=DOL_DATA_ROOT.'/private/'.$user->id.'/compta/'.$original_file;
        else $original_file=$conf->compta->dir_output.'/payments/'.$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='$refname'";
    }

    // Wrapping pour les exports de compta
    if ($modulepart == 'export_compta')
    {
        $user->getrights('compta');
        if ($user->rights->compta->ventilation->creer || eregi('^specimen',$original_file))
        {
            $accessallowed=1;
        }
        $original_file=$conf->compta->dir_output.'/'.$original_file;
    }

    // Wrapping pour les societe
    if ($modulepart == 'societe')
    {
        $user->getrights('societe');
        if ($user->rights->societe->lire || eregi('^specimen',$original_file))
        {
            $accessallowed=1;
        }
        $original_file=$conf->societe->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT rowid as fk_soc FROM ".MAIN_DB_PREFIX."societe WHERE idp='$refname'";
    }

    // Wrapping pour les expedition
    if ($modulepart == 'expedition')
    {
        $user->getrights('expedition');
        if ($user->rights->expedition->lire || eregi('^specimen',$original_file))
        {
            $accessallowed=1;
        }
        $original_file=$conf->expedition_bon->dir_output.'/'.$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='$refname'";
    }

    // Wrapping pour les bons de livraison
    if ($modulepart == 'livraison')
    {
        $user->getrights('expedition');
        if ($user->rights->expedition->livraison->lire || eregi('^specimen',$original_file))
        {
            $accessallowed=1;
        }
        $original_file=$conf->livraison_bon->dir_output.'/'.$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='$refname'";
	}

    // Wrapping pour la telephonie
    if ($modulepart == 'telephonie')
    {
        $user->getrights('telephonie');
        if ($user->rights->telephonie->lire || eregi('^specimen',$original_file))
        {
            $accessallowed=1;
        }
        $original_file=$conf->telephonie->dir_output.'/'.$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='$refname'";
    }

    // Wrapping pour les actions
    if ($modulepart == 'actions')
    {
        $user->getrights('commercial');
        //if ($user->rights->commercial->actions->lire || eregi('^specimen',$original_file))	// Ce droit n'existe pas encore
        //{
        $accessallowed=1;
        //}
        $original_file=$conf->actions->dir_output.'/'.$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='$refname'";
    }

    // Wrapping pour les actions
    if ($modulepart == 'actionsreport')
    {
        $user->getrights('commercial');
        //if ($user->rights->commercial->actions->lire || eregi('^specimen',$original_file))	// Ce droit n'existe pas encore
        //{
        $accessallowed=1;
        //}
		$original_file = $conf->actions->dir_temp."/".$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='$refname'";
	}

    // Wrapping pour les produits et services
    if ($modulepart == 'produit')
    {
        $user->getrights('produit');
        //if ($user->rights->commercial->lire || eregi('^specimen',$original_file))	// Ce droit n'existe pas encore
        //{
        $accessallowed=1;
        //}
        $original_file=$conf->produit->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = '';
    }

    // Wrapping pour les produits et services
    if ($modulepart == 'contract')
    {
        $user->getrights('contrat');
        if ($user->rights->contrat->lire || eregi('^specimen',$original_file))	// Ce droit n'existe pas encore
        {
			$accessallowed=1;
        }
        $original_file=$conf->contrat->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = '';
    }

    // Wrapping pour les documents generaux
    if ($modulepart == 'ged')
    {
        $user->getrights('document');
        if ($user->rights->document->lire)
        {
			$accessallowed=1;
        }
        $original_file= DOL_DATA_ROOT.'/ged/'.$original_file;
    }

    // Wrapping pour les documents generaux
    if ($modulepart == 'ecm')
    {
        $user->getrights('ecm');
        if ($user->rights->ecm->download)
        {
			$accessallowed=1;
        }
        $original_file= DOL_DATA_ROOT.'/ecm/'.$original_file;
    }

    // Wrapping pour les dons
    if ($modulepart == 'donation')
    {
        $user->getrights('don');
        if ($user->rights->don->lire || eregi('^specimen',$original_file))
        {
            $accessallowed=1;
        }
        $original_file=$conf->don->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = '';
    }

    // Wrapping pour les remises de cheques
    if ($modulepart == 'remisecheque')
    {
        $user->getrights('banque');
        if ($user->rights->banque || eregi('^specimen',$original_file))
        {
            $accessallowed=1;
        }

        $original_file=DOL_DATA_ROOT.'/compta/bordereau/'.get_exdir(basename($original_file,".pdf")).$original_file;
		$sqlprotectagainstexternals = '';
    }

    // Wrapping pour les exports
    if ($modulepart == 'export')
    {
        // Aucun test necessaire car on force le rep de doanwload sur
        // le rep export qui est propre a l'utilisateur
        $accessallowed=1;
        $original_file=$conf->export->dir_temp.'/'.$user->id.'/'.$original_file;
		$sqlprotectagainstexternals = '';
    }

    // Wrapping pour l'editeur wysiwyg
    if ($modulepart == 'editor')
    {
        // Aucun test necessaire car on force le rep de download sur
        // le rep export qui est propre a l'utilisateur
        $accessallowed=1;
        $original_file=$conf->fckeditor->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = '';
    }

    // Wrapping pour les backups
    if ($modulepart == 'systemtools')
    {
        if ($user->admin)
        {
            $accessallowed=1;
        }
        $original_file=DOL_DATA_ROOT.'/admin/temp/'.$original_file;
		$sqlprotectagainstexternals = '';
    }
}

// Basic protection (against external users only)
if ($user->societe_id > 0)
{
	if ($sqlprotectagainstexternals)
	{
		$resql = $db->query($sqlprotectagainstexternals);
		if ($resql)
		{
		   $obj = $db->fetch_object($resql);
		   $num=$db->num_rows($resql);
		   if ($num>0 && $user->societe_id != $obj->fk_soc)
		      $accessallowed=0;
		}
	}
}

// Security:
// Limite acces si droits non corrects
if (! $accessallowed)
{
    accessforbidden();
}

// Security:
// On interdit les remontees de repertoire ainsi que les pipe dans
// les noms de fichiers.
if (eregi('\.\.',$original_file) || eregi('[<>|]',$original_file))
{
	dol_syslog("Refused to deliver file ".$original_file);
	// Do no show plain path in shown error message
	dol_print_error(0,$langs->trans("ErrorFileNameInvalid",$_GET["file"]));
	exit;
}



if ($action == 'remove_file')
{
	/*
	 * Suppression fichier
	 */
	clearstatcache();
	$filename = basename($original_file);

	dol_syslog("document.php remove $original_file $filename $urlsource", LOG_DEBUG);

	if (! file_exists($original_file))
	{
	    dol_print_error(0,$langs->trans("ErrorFileDoesNotExists",$_GET["file"]));
	    exit;
	}
	unlink($original_file);

	dol_syslog("document.php back to ".urldecode($urlsource), LOG_DEBUG);

	header("Location: ".urldecode($urlsource));

	return;
}
else
{
	/*
	 * Open and return file
	 */
	clearstatcache();
	$filename = basename($original_file);

	dol_syslog("document.php download $original_file $filename content-type=$type");

	if (! file_exists($original_file))
	{
	    dol_print_error(0,$langs->trans("ErrorFileDoesNotExists",$original_file));
	    exit;
	}


	// Les drois sont ok et fichier trouve, on l'envoie

	if ($encoding)   header('Content-Encoding: '.$encoding);
	if ($type)       header('Content-Type: '.$type);
	if ($attachment) header('Content-Disposition: attachment; filename="'.$filename.'"');
	else header('Content-Disposition: inline; filename="'.$filename.'"');

	// Ajout directives pour resoudre bug IE
	header('Cache-Control: Public, must-revalidate');
	header('Pragma: public');

	readfile($original_file);
}

?>
