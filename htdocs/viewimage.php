<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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


require_once("main.inc.php");


// C'est un wrapper, donc header vierge
function llxHeader() { }



$original_file = urldecode($_GET["file"]);
$modulepart = urldecode($_GET["modulepart"]);
$type = urldecode($_GET["type"]);

$filename = basename ($original_file);


$accessallowed=0;
if ($modulepart)
{
    // On fait une vérification des droits et on définit le répertoire concerné

    // Wrapping pour les photo utilisateurs
    if ($modulepart == 'userphoto')
    {
        //$user->getrights('facture');
        //if ($user->rights->facture->lire)
        //{
            $accessallowed=1;
        //}
        $original_file=$conf->users->dir_output.'/'.$original_file;
    }
    
    // Wrapping pour les apercu factures
    if ($modulepart == 'apercufacture')
    {
        $user->getrights('facture');
        if ($user->rights->facture->lire)
        {
            $accessallowed=1;
        }
        $original_file=$conf->facture->dir_output.'/'.$original_file;
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
  header('Content-type: image/png');
}

readfile($original_file);

?>
