<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur       <eldy@users.sourceforge.net>
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
 *
 */
 
/**
        \file       htdocs/comm/bookmark.php
        \brief      Page affichage des bookmarks
        \version    $Revision$
*/

 
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/bookmarks/bookmark.class.php");


/*
 * Actions
 */
 
if ($_GET["action"] == 'add')
{
    $bookmark=new Bookmark($db);
    $bookmark->fk_user=$user->id;
    if ($_GET["socid"])    // Lien vers fiche comm société
    {
        require_once(DOL_DOCUMENT_ROOT."/societe.class.php");
        $societe=new Societe($db);
        $societe->fetch($_GET["socid"]);
        $bookmark->fk_soc=$societe->id;
        $bookmark->url=DOL_URL_ROOT.'/comm/fiche.php?socidp='.$societe->id;
        $bookmark->target='';
        $bookmark->title=$societe->nom;
    }
    else
    {
        $bookmark->url=$_GET["url"];
        $bookmark->target=$_GET["target"];
        $bookmark->title=$_GET["title"];
    }
    $bookmark->favicon='xxx';
    
    $res=$bookmark->create();
    if ($res > 0)
    {
        $urlsource=isset($_GET["urlsource"])?$_GET["urlsource"]:$_SERVER["PHP_SELF"];
        header("Location: ".$urlsource);
    }
    else
    {
        $mesg='<div class="error">'.$bookmark->error.'</div>';
    }
}

if ($_GET["action"] == 'delete')
{
    $bookmark=new Bookmark($db);
    $bookmark->id=$_GET["bid"];
    $bookmark->url=$user->id;
    $bookmark->target=$user->id;
    $bookmark->title='xxx';
    $bookmark->favicon='xxx';
    
    $res=$bookmark->remove();
    if ($res > 0)
    {
        header("Location: ".$_SERVER["PHP_SELF"]);
    }
    else
    {
        $mesg='<div class="error">'.$bookmark->error.'</div>';
    }
}



llxHeader();

print_fiche_titre($langs->trans("Bookmarks"));
 

print 'En construction';


$db->close();

llxFooter('$Date$ - $Revision$');
?>
