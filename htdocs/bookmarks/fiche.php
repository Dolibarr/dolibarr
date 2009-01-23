<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/bookmarks/fiche.php
 *       \brief      Page affichage/creation des bookmarks
 *       \ingroup    bookmark
 *       \version    $Id$
 */


require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/bookmarks/bookmark.class.php");

$langs->load("other");

$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];
$title=isset($_GET["title"])?$_GET["title"]:$_POST["title"];
$url=isset($_GET["url"])?$_GET["url"]:$_POST["url"];
$target=isset($_GET["target"])?$_GET["target"]:$_POST["target"];


/*
 * Actions
 */

if ($action == 'add' || $action == 'addproduct')
{
    $mesg='';
    
    $bookmark=new Bookmark($db);
    $bookmark->fk_user=$user->id;
    $bookmark->title=$title;
    $bookmark->url=$url;
    $bookmark->target=$target;
    
    if ($action == 'add' && $_GET["socid"])    // Link to third party card
    {
        require_once(DOL_DOCUMENT_ROOT."/societe.class.php");
        $langs->load("companies");
        $societe=new Societe($db);
        $societe->fetch($_GET["socid"]);
        $bookmark->url=DOL_URL_ROOT.'/soc.php?socid='.$societe->id;
        $bookmark->target='0';
        $bookmark->title=$langs->trans("ThirdParty").' '.$societe->nom;
        //$bookmark->title=$societe->nom;
        $title=$bookmark->title;
       	$url=$bookmark->url;
    }
    if ($action == 'addproduct' && $_GET["id"])    // Link to product card
    {
        require_once(DOL_DOCUMENT_ROOT."/product.class.php");
        $langs->load("products");
        $product=new Product($db);
        $product->fetch($_GET["id"]);
        $bookmark->url=DOL_URL_ROOT.'/product/fiche.php?id='.$product->id;
        $bookmark->target='0';
        $bookmark->title=($product->type != 1 ?$langs->trans("Product"):$langs->trans("Service")).' '.$product->ref;
        //$bookmark->title=$product->ref;
        $title=$bookmark->title;
       	$url=$bookmark->url;
    }
    
    if (! $title) $mesg.=($mesg?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->trans("BookmarkTitle"));
    if (! $url) $mesg.=($mesg?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->trans("UrlOrLink"));

    if (! $mesg)
    {
        $bookmark->favicon='none';
        
        $res=$bookmark->create();
        if ($res > 0)
        {
			$urlsource=isset($_GET["urlsource"])?$_GET["urlsource"]:DOL_URL_ROOT.'/bookmarks/liste.php';
            header("Location: ".$urlsource);
            exit;
        }
        else
        {
        	if ($bookmark->errno == 'DB_ERROR_RECORD_ALREADY_EXISTS')
        	{
        		$langs->load("errors");
            	$mesg='<div class="warning">'.$langs->trans("WarningBookmarkAlreadyExists").'</div>';
        	}
        	else
        	{
            	$mesg='<div class="error">'.$bookmark->error.'</div>';
        	}
        	$action='create';
        }
    }
    else
    {
        $mesg='<div class="error">'.$mesg.'</div>';
        $action='create';
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
        exit;
    }
    else
    {
        $mesg='<div class="error">'.$bookmark->error.'</div>';
    }
}


/*
 * View
 */

llxHeader();

$html=new Form($db);


if ($action == 'create')
{
    /*
     * Fiche bookmark en mode creation
     */

    print '<form action="fiche.php" method="post">'."\n";
    print '<input type="hidden" name="action" value="add">';

    print_fiche_titre($langs->trans("NewBookmark"));

    if ($mesg) print "$mesg<br>";

    print '<table class="border" width="100%">';

    print '<tr><td width="20%">'.$langs->trans("BookmarkTitle").'</td><td><input class="flat" name="title" size="30" value="'.$title.'"></td><td>'.$langs->trans("SetHereATitleForLink").'</td></tr>';
    print '<tr><td width="20%">'.$langs->trans("UrlOrLink").'</td><td><input class="flat" name="url" size="50" value="'.$url.'"></td><td>'.$langs->trans("UseAnExternalHttpLinkOrRelativeDolibarrLink").'</td></tr>';
    print '<tr><td width="20%">'.$langs->trans("BehaviourOnClick").'</td><td>';
    $liste=array(1=>$langs->trans("OpenANewWindow"),0=>$langs->trans("ReplaceWindow"));
    $html->select_array('target',$liste,1);
    print '</td><td>'.$langs->trans("ChooseIfANewWindowMustBeOpenedOnClickOnBookmark").'</td></tr>';
    print '<tr><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("CreateBookmark").'"></td></tr>';
    print '</table>';
    
    print '</form>';
}


if ($_GET["id"] > 0 && ! eregi('^add',$_GET["action"]))
{
    /*
     * Fiche bookmark en mode edition
     */
    $bookmark=new Bookmark($db);
    $bookmark->fetch($_GET["id"]);
    

    dolibarr_fiche_head($head, $hselected, $langs->trans("Bookmark"));

    print '<table class="border" width="100%">';

    print '<tr><td width="20%">'.$langs->trans("BookmarkTitle").'</td><td>'.$bookmark->title.'</td></tr>';
    print '<tr><td width="20%">'.$langs->trans("UrlOrLink").'</td><td>';
    print '<a href="'.(eregi('^http',$bookmark->url)?$bookmark->url:DOL_URL_ROOT.$bookmark->url).'" target="'.($bookmark->target?"":"newlink").'">'.$bookmark->url.'</a></td></tr>';
    print '<tr><td width="20%">'.$langs->trans("BehaviourOnClick").'</td><td>';
    if ($bookmark->target == 0) print $langs->trans("OpenANewWindow");
    if ($bookmark->target == 1) print $langs->trans("ReplaceWindow");
    print '</td></tr>';
    print '</table>';

    print "</div>\n";
    
    print "<div class=\"tabsAction\">\n";

    // Supprimer
    if ($user->rights->bookmark->supprimer)
    {
        print "  <a class=\"butActionDelete\" href=\"liste.php?bid=".$bookmark->id."&amp;action=delete\">".$langs->trans("Delete")."</a>\n";
    }

    print '</div>';

}

$db->close();


llxFooter('$Date$ - $Revision$');
?>
