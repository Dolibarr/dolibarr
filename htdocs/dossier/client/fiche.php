<?PHP
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *       \file       htdocs/dossier/client/fiche.php
 *       \brief      Page des dossiers clients
 *       \version    $Id$
 *		\TODO	Remove dossier directory and link to it on code where a test
 * 				is made on MAIN_MODULE_DOSSIER.
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/client.class.php');

if (!$user->rights->facture->lire)
  accessforbidden();


$facs = array();
$client = new client($db, $_GET["id"]);
$client->fetch($_GET["id"]);
$client->read_factures();
$facs = $client->factures;

llxHeader("",'Dossier', $client);


/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

if ($_GET["facid"])
{
    require_once(DOL_DOCUMENT_ROOT.'/facture.class.php');
    
    $fac = new Facture($db);
    $fac->fetch($_GET["facid"]);
    
    $file = DOL_DATA_ROOT."/facture/".$fac->ref."/".$fac->ref.".pdf";
    $file_img = DOL_DATA_ROOT."/facture/".$fac->ref."/".$fac->ref.".pdf.png";
    
    // Si image n'existe pas, on la genere
    if (! file_exists($file_img))
    {
        $converttool="";
        if (file_exists("/usr/bin/convert")) $converttool="/usr/bin/convert";
        elseif (file_exists("/usr/local/bin/convert")) $converttool="/usr/local/bin/convert";
        if ($converttool) {
            // Si convert est dispo
            //print "x $file_img $converttool $file $file_img x";
            exec("$converttool $file $file_img");
        }
    }
    
    if (file_exists($file_img))
    {
    	// image.php has been deleted because was a serious security hole
    	// All image output must be throw wrapper viewimage.php
        print '<br><img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=todo&file='.urlencode($file_img).'">';
    }
    
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
