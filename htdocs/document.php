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

/**     \file       htdocs/document.php
		\brief      Wrapper permettant le téléchargement de fichier de données Dolibarr
        \remarks    L'appel est document.php?file=pathrelatifdufichier&modulepart=repfichierconcerne
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
        $original_file=$conf->ficheinter->dir_output.'/'.$original_file;
    }

    // Wrapping pour les prelevements
    if ($modulepart == 'prelevement')
    {
        $user->getrights('prelevement');
        if ($user->rights->prelevement->lire)
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

    // Wrapping pour les rapport de paiements
    if ($modulepart == 'facture_paiement')
    {
        $user->getrights('facture');
        if ($user->rights->facture->lire)
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


    // Wrapping pour la telephonie
    if ($modulepart == 'actionscomm')
    {
        $user->getrights('commercial');
        //if ($user->rights->commercial->lire)      // Ce droit n'existe pas encore
        //{
            $accessallowed=1;
        //}
        $original_file=$conf->commercial->dir_output.'/'.$original_file;
    }

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
