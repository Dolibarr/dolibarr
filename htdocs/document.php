<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/**
        \file       htdocs/document.php
		\brief      Wrapper permettant le téléchargement de fichier de données Dolibarr
                    L'appel ancienne méthode (non sécurisée) est document.php?file=pathcompletdufichier
                    L'appel nouvelle méthode (sécurisée) est document.php?file=pathrelatifdufichier&modulepart=typefichier
		\version    $Revision$
*/


require_once("main.inc.php");


// C'est un wrapper, donc header vierge
function llxHeader() { }


$original_file = urldecode($_GET["file"]);
$modulepart = urldecode($_GET["modulepart"]);
$type = urldecode($_GET["type"]);

$accessallowed=0;
if ($modulepart)
{
    // On fait une vérification des droits et on définit le répertoire concerné
    if ($modulepart == 'facture_paiement')
    {
        $user->getrights('facture');
        if ($user->rights->facture->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->compta->dir_output.'/'.$original_file;
    }
    if ($modulepart == 'facture')
    {
        $user->getrights('facture');
        if ($user->rights->facture->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->facture->dir_output.'/'.$original_file;
    }
}
else
{
    // A terme, on ne doit rien pouvoir télécharger via document.php sans fournir type
    // car c'est grace au type qu'on vérifie que les droits et qu'on définit le répertoire racine des fichiers

    // \todo    Corriger ce trou de sécurité pour ne plus permettre l'utilisation via un nom de fichier complet et sans test de droits

    // Pour l'instant, autorise la passage   
    $accessallowed=1;
}

// Limite accès si droits non corrects
if (! $accessallowed) { accessforbidden(); }

$filename = basename($original_file);
if (! file_exists($original_file)) { dolibarr_print_error(0,$langs->trans("FileDoesNotExist",$original_file)); exit; }

// Les drois sont ok et fichier trouvé
if ($type)
{
  header('Content-type: '.$type);
}
else
{
  header('Content-type: application/pdf');
}
header('Content-Disposition: attachment; filename="'.$filename.'"');

readfile($original_file);


?>
