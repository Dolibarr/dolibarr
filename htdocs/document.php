<?php
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
   \file       htdocs/document.php
   \brief      Wrapper permettant le téléchargement de fichier de données Dolibarr
   \remarks    L'appel est document.php?file=pathrelatifdufichier&modulepart=repfichierconcerne
   \version    $Revision$
*/

if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU','1');

require_once("main.inc.php");


function llxHeader()
{
  global $user,$langs;
  top_menu($head, $title);
  $menu = new Menu();
  left_menu($menu->liste);
}

$action = $_GET["action"];
$original_file = urldecode($_GET["file"]);
$modulepart = urldecode($_GET["modulepart"]);
$urlsource = urldecode($_GET["urlsource"]);
// Défini type (attachment=1 pour forcer popup 'enregistrer sous')
$type = urldecode($_GET["type"]);
$attachment = true;
if (eregi('\.sql$',$original_file))     { $type='text/plain'; $attachment = true; }
if (eregi('\.html$',$original_file)) 	{ $type='text/html'; $attachment = false; }
if (eregi('\.csv$',$original_file))  	{ $type='text/csv'; $attachment = true; }
if (eregi('\.pdf$',$original_file))  	{ $type='application/pdf'; $attachment = true; }
if (eregi('\.xls$',$original_file))  	{ $type='application/x-msexcel'; $attachment = true; }
if (eregi('\.jpg$',$original_file)) 	{ $type='image/jpeg'; $attachment = true; }
if (eregi('\.png$',$original_file)) 	{ $type='image/jpeg'; $attachment = true; }
if (eregi('\.tiff$',$original_file)) 	{ $type='image/tiff'; $attachment = true; }

// Suppression de la chaine de caractère ../ dans $original_file
$original_file = str_replace("../","/", "$original_file");
// find the subdirectory name as the reference
$refname=basename(dirname($original_file)."/");

$accessallowed=0;
$sqlprotectagainstexternals='';
if ($modulepart)
{
    // On fait une vérification des droits et on définit le répertoire concern

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
        $original_file=$conf->expedition->dir_output.'/'.$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='$refname'";
    }
    
    // Wrapping pour les bons de livraison
    if ($modulepart == 'livraison')
    {
        $user->getrights('livraison');
        if ($user->rights->expedition->livraison->lire || eregi('^specimen',$original_file))
        {
            $accessallowed=1;
        }
        $original_file=$conf->livraison->dir_output.'/'.$original_file;
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

    // Wrapping pour les documents generaux
    if ($modulepart == 'ged')
    {
        $user->getrights('document');
        if ($user->rights->document->lire )
        {
	  $accessallowed=1;
        }
        $original_file= DOL_DATA_ROOT.'/ged/'.$original_file;
    }

    // Wrapping pour les dons
    if ($modulepart == 'don')
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
        // le rep export qui est propre à l'utilisateur
        $accessallowed=1;
        $original_file=$conf->export->dir_temp.'/'.$user->id.'/'.$original_file;
		$sqlprotectagainstexternals = '';
    }
    
    // Wrapping pour l'éditeur wysiwyg
    if ($modulepart == 'editor')
    {
        // Aucun test necessaire car on force le rep de download sur
        // le rep export qui est propre à l'utilisateur
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
if ($user->societe_id>0)
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
// Limite accès si droits non corrects
if (! $accessallowed)
{
    accessforbidden();
}

// Security:
// On interdit les remontées de repertoire ainsi que les pipe dans 
// les noms de fichiers.
if (eregi('\.\.',$original_file) || eregi('[<>|]',$original_file))
{
	dolibarr_syslog("Refused to deliver file ".$original_file);
	// Do no show plain path in shown error message
	dolibarr_print_error(0,$langs->trans("ErrorFileNameInvalid",$_GET["file"]));
	exit;
}



if ($action == 'remove_file')
{
	/*
	 * Suppression fichier
	 */
	clearstatcache(); 
	$filename = basename($original_file);
	
	dolibarr_syslog("document.php remove $original_file $filename $urlsource");

	if (! file_exists($original_file)) 
	{
	    dolibarr_print_error(0,$langs->trans("ErrorFileDoesNotExists",$_GET["file"])); 
	    exit;
	}
	unlink($original_file);

	dolibarr_syslog("document.php back to ".urldecode($urlsource));

	header("Location: ".urldecode($urlsource));

	return;
}
else
{
	/*
	 * Ouvre et renvoi fichier
	 */
	clearstatcache(); 
	$filename = basename($original_file);
	
	dolibarr_syslog("document.php download $original_file $filename content-type=$type");
	
	if (! file_exists($original_file)) 
	{
	    dolibarr_print_error(0,$langs->trans("ErrorFileDoesNotExists",$original_file)); 
	    exit;
	}
	
	
	// Les drois sont ok et fichier trouvé, on l'envoie
	
	if ($type) header('Content-type: '.$type);
	if ($attachment) header('Content-Disposition: attachment; filename="'.$filename.'"');
	
	// Ajout directives pour résoudre bug IE
	header('Cache-Control: Public, must-revalidate');
	header('Pragma: public');
	 
	readfile($original_file);
}

?>
