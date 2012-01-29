<?php
/* Copyright (C) 2005-2010 Laurent Destailleur       <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/bookmarks/liste.php
 *       \brief      Page to display list of bookmarks
 *       \ingroup    bookmark
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/bookmarks/class/bookmark.class.php");


$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="position";
$limit=$conf->liste_limit;


/*
 * Actions
 */

if ($_GET["action"] == 'delete')
{
    $bookmark=new Bookmark($db);
    $res=$bookmark->remove($_GET["bid"]);
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

$userstatic=new User($db);

llxHeader();

print_fiche_titre($langs->trans("Bookmarks"));

if ($mesg) print $mesg;

$sql = "SELECT b.fk_soc as rowid, b.dateb, b.rowid as bid, b.fk_user, b.url, b.target, b.title, b.favicon, b.position,";
$sql.= " u.login, u.name, u.firstname";
$sql.= " FROM ".MAIN_DB_PREFIX."bookmark as b LEFT JOIN ".MAIN_DB_PREFIX."user as u ON b.fk_user=u.rowid";
$sql.= " WHERE 1=1";
if (! $user->admin) $sql.= " AND (b.fk_user = ".$user->id." OR b.fk_user is NULL OR b.fk_user = 0)";
$sql.= $db->order($sortfield.", position",$sortorder);
$sql.= $db->plimit($limit, $offset);

$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    print "<table class=\"noborder\" width=\"100%\">";

    print "<tr class=\"liste_titre\">";
    //print "<td>&nbsp;</td>";
    print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"bid","","",'align="left"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Title"),'','')."</td>";
    print_liste_field_titre($langs->trans("Link"),'','')."</td>";
    print_liste_field_titre($langs->trans("Target"),'','','','','align="center"')."</td>";
    print_liste_field_titre($langs->trans("Owner"),$_SERVER["PHP_SELF"],"u.name","","",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"b.dateb","","",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Position"),$_SERVER["PHP_SELF"],"b.position","","",'align="right"',$sortfield,$sortorder);
    print_liste_field_titre('','','');
    print "</tr>\n";

    $var=True;
    while ($i < $num)
    {
        $obj = $db->fetch_object($resql);

        $var=!$var;
        print "<tr $bc[$var]>";

        // Id
        print '<td align="left">';
        print "<a href=\"fiche.php?id=".$obj->bid."\">".img_object($langs->trans("ShowBookmark"),"bookmark").' '.$obj->bid."</a>";
        print '</td>';

        $lieninterne=0;
        $title=dol_trunc($obj->title,24);
        $lien=dol_trunc($obj->url,24);

        // Title
        print "<td>";
        if ($obj->rowid)
        {
            // Lien interne societe
            $lieninterne=1;
            $lien="Dolibarr";
            if (! $obj->title)
            {
                // For compatibility with old Dolibarr bookmarks
                require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
                $societe=new Societe($db);
                $societe->fetch($obj->rowid);
                $obj->title=$societe->nom;
            }
            $title=img_object($langs->trans("ShowCompany"),"company").' '.$obj->title;
        }
        if ($lieninterne) print "<a href=\"".$obj->url."\">";
        print $title;
        if ($lieninterne) print "</a>";
        print "</td>\n";

        // Url
        print "<td>";
        if (! $lieninterne) print '<a href="'.$obj->url.'"'.($obj->target?' target="newlink"':'').'>';
        print $lien;
        if (! $lieninterne) print '</a>';
        print "</td>\n";

        // Target
        print '<td align="center">';
        if ($obj->target == 0) print $langs->trans("BookmarkTargetReplaceWindowShort");
        if ($obj->target == 1) print $langs->trans("BookmarkTargetNewWindowShort");
        print "</td>\n";

        // Author
        print '<td align="center">';
		if ($obj->fk_user)
		{
        	$userstatic->id=$obj->fk_user;
	    	$userstatic->lastname=$obj->login;
			print $userstatic->getNomUrl(1);
		}
		else
		{
			print $langs->trans("Public");
		}
        print "</td>\n";

        // Date creation
        print '<td align="center">'.dol_print_date($db->jdate($obj->dateb),'day')."</td>";

        // Position
        print '<td align="right">'.$obj->position."</td>";

        // Actions
        print '<td align="right">';
        if ($user->rights->bookmark->supprimer)
        {
            print "<a href=\"".$_SERVER["PHP_SELF"]."?action=delete&bid=$obj->bid\">".img_delete()."</a>";
        }
        else
        {
            print "&nbsp;";
        }
        print "</td>";
        print "</tr>\n";
        $i++;
    }
    print "</table>";
    $db->free($resql);
}
else
{
    dol_print_error($db);
}



print "<div class=\"tabsAction\">\n";

if ($user->rights->bookmark->creer)
{
    print '<a class="butAction" href="fiche.php?action=create">'.$langs->trans("NewBookmark").'</a>';
}

print '</div>';


$db->close();

llxFooter();
?>
