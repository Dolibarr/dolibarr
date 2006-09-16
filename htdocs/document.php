<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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

require_once("main.inc.php");


// C'est un wrapper, donc header vierge
function llxHeader() { }


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

//Suppression de la chaine de caractère ../ dans $original_file
$original_file = str_replace("../","/", "$original_file");

$accessallowed=0;
if ($modulepart)
{
    // On fait une vérification des droits et on définit le répertoire concern

    // Wrapping pour les factures
    if ($modulepart == 'facture')
    {
        $user->getrights('facture');
        if ($user->rights->facture->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->facture->dir_output.'/'.$original_file;
    }

    // Wrapping pour les fiches intervention
    if ($modulepart == 'ficheinter')
    {
        $user->getrights('ficheinter');
        if ($user->rights->ficheinter->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->fichinter->dir_output.'/'.$original_file;
    }

    // Wrapping pour les prelevements
    if ($modulepart == 'prelevement')
    {
        $user->getrights('prelevement');
        if ($user->rights->prelevement->bons->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->prelevement->dir_output.'/'.$original_file;
    }

    // Wrapping pour les propales
    if ($modulepart == 'propal')
    {
        $user->getrights('propale');
        if ($user->rights->propale->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->propal->dir_output.'/'.$original_file;
    }
	 // Wrapping pour les commandes
    if ($modulepart == 'commande')
    {
        $user->getrights('commande');
        if ($user->rights->commande->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->commande->dir_output.'/'.$original_file;
    }
    
    // Wrapping pour les commandes fournisseurs
    if ($modulepart == 'commande_fournisseur')
    {
        $user->getrights('fournisseur');
        if ($user->rights->fournisseur->commande->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->fournisseur->commande->dir_output.'/'.$original_file;
    }
    
    // Wrapping pour les factures fournisseurs
    if ($modulepart == 'facture_fournisseur')
    {
        $user->getrights('fournisseur');
        if ($user->rights->fournisseur->facture->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->fournisseur->facture->dir_output.'/'.$original_file;
    }

    // Wrapping pour les rapport de paiements
    if ($modulepart == 'facture_paiement')
    {
        $user->getrights('facture');
        if ($user->rights->facture->lire)
        {
            $accessallowed=1;
        }
        if ($user->societe_id > 0) $original_file=DOL_DATA_ROOT.'/private/'.$user->id.'/compta/'.$original_file;
        else $original_file=$conf->compta->dir_output.'/'.$original_file;
    }

    // Wrapping pour les exports de compta
    if ($modulepart == 'export_compta')
    {
        $user->getrights('compta');
        if ($user->rights->compta->ventilation->creer)
        {
            $accessallowed=1;
        }
        $original_file=$conf->compta->dir_output.'/'.$original_file;
    }

    // Wrapping pour les societe
    if ($modulepart == 'societe')
    {
        $user->getrights('societe');
        if ($user->rights->societe->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->societe->dir_output.'/'.$original_file;
    }

    // Wrapping pour les expedition
    if ($modulepart == 'expedition')
    {
        $user->getrights('expedition');
        if ($user->rights->expedition->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->expedition->dir_output.'/'.$original_file;
    }
    
    // Wrapping pour les bons de livraison
    if ($modulepart == 'livraison')
    {
        $user->getrights('livraison');
        if ($user->rights->expedition->livraison->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->livraison->dir_output.'/'.$original_file;
    }

    // Wrapping pour la telephonie
    if ($modulepart == 'telephonie')
    {
        $user->getrights('telephonie');
        if ($user->rights->telephonie->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->telephonie->dir_output.'/'.$original_file;
    }

    // Wrapping pour les actions
    if ($modulepart == 'actions')
    {
        $user->getrights('commercial');
        //if ($user->rights->commercial->actions->lire)      // Ce droit n'existe pas encore
        //{
        $accessallowed=1;
        //}
        $original_file=$conf->actions->dir_output.'/'.$original_file;
    }

    // Wrapping pour les actions
    if ($modulepart == 'actionsreport')
    {
        $user->getrights('commercial');
        //if ($user->rights->commercial->actions->lire)      // Ce droit n'existe pas encore
        //{
        $accessallowed=1;
        //}
		$original_file = $conf->actions->dir_temp."/".$original_file;
	}

    // Wrapping pour les produits et services
    if ($modulepart == 'produit')
    {
        $user->getrights('produit');
        //if ($user->rights->commercial->lire)      // Ce droit n'existe pas encore
        //{
        $accessallowed=1;
        //}
        $original_file=$conf->produit->dir_output.'/'.$original_file;
    }

    // Wrapping pour les dons
    if ($modulepart == 'don')
    {
        $user->getrights('don');
        if ($user->rights->don->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->don->dir_output.'/'.$original_file;
    }

    // Wrapping pour les exports
    if ($modulepart == 'export')
    {
        // Aucun test necessaire car on force le rep de doanwload sur
        // le rep export qui est propre à l'utilisateur
        $accessallowed=1;
        $original_file=$conf->export->dir_temp.'/'.$user->id.'/'.$original_file;
    }
    
    // Wrapping pour l'éditeur wysiwyg
    if ($modulepart == 'editor')
    {
        // Aucun test necessaire car on force le rep de doanwload sur
        // le rep export qui est propre à l'utilisateur
        $accessallowed=1;
        $original_file=$conf->fckeditor->dir_output.'/'.$original_file;
    }

    // Wrapping pour les backups
    if ($modulepart == 'systemtools')
    {
        if ($user->admin)
        {
            $accessallowed=1;
        }
        $original_file=DOL_DATA_ROOT.'/admin/temp/'.$original_file;
    }


}

// Limite accès si droits non corrects
if (! $accessallowed)
{
    accessforbidden();
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
	    dolibarr_print_error(0,$langs->trans("ErrorFileDoesNotExists",$original_file)); 
	    exit;
	}
	unlink($original_file);

	dolibarr_syslog("document.php back to ".urldecode($urlsource));
	Header("Location: ".urldecode($urlsource));
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
