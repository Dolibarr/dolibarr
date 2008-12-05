<?php
/* Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/includes/boxes/box_bookmarks.php
        \ingroup    bookmark
        \brief      Module de génération de l'affichage de la box bookmark
		\version	$Id$
*/


include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");


class box_bookmarks extends ModeleBoxes {

    var $boxcode="bookmarks";
    var $boximg="object_bookmark";
    var $boxlabel;
    var $depends = array("bookmark");

	var $db;
	var $param;

    var $info_box_head = array();
    var $info_box_contents = array();

    /**
     *      \brief      Constructeur de la classe
     */
    function box_bookmarks()
    {
        global $langs;
        $langs->load("boxes");

        $this->boxlabel=$langs->trans("BoxMyLastBookmarks");
    }

    /**
     *      \brief      Charge les données en mémoire pour affichage ultérieur
     *      \param      $max        Nombre maximum d'enregistrements à charger
     */
    function loadBox($max=5)
    {
        global $user, $langs, $db;
        $langs->load("boxes");

        $this->info_box_head = array('text' => $langs->trans("BoxMyLastBookmarks",$max),
                                     'sublink' => DOL_URL_ROOT.'/bookmarks/liste.php');
        if ($user->rights->bookmark->creer)
        {
                $this->info_box_head['subpicto']='object_bookmark';
                $this->info_box_head['subtext']=$langs->trans("BookmarksManagement");
        }
        else
        {
                $this->info_box_head['subpicto']='object_bookmark';
                $this->info_box_head['subtext']=$langs->trans("ListOfBookmark");
        }

        if ($user->rights->bookmark->lire) 
        {
            $sql = "SELECT b.title, b.url, b.target, b.favicon";
            $sql.= " FROM ".MAIN_DB_PREFIX."bookmark as b";
            $sql.= " WHERE fk_user = ".$user->id;
            $sql .= " ORDER BY b.dateb DESC ";
            $sql .= $db->plimit($max, 0);

            $result = $db->query($sql);
    
            if ($result)
            {
                $num = $db->num_rows($result);
    
                $i = 0;
    
                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);
    
                    $this->info_box_contents[$i][0] = array('align' => 'left',
                    'logo' => $this->boximg,
                    'text' => stripslashes($objp->title),
                    'url' => $objp->url,
                    'target' => $objp->target?'newtab':'');
    
                    $i++;
                }
 
                $i=$num;
                while ($i < $max)
                {
                    if ($num==0 && $i==$num)
                    {
                        $this->info_box_contents[$i][0] = array('align' => 'center', 'url'=> DOL_URL_ROOT.'/bookmarks/liste.php', 'text'=>$langs->trans("NoRecordedBookmarks"));
                        $this->info_box_contents[$i][1] = array('text'=>'&nbsp;');
                    } else {
                        $this->info_box_contents[$i][0] = array('text'=>'&nbsp;');
                        $this->info_box_contents[$i][1] = array('text'=>'&nbsp;');
                    }
                    $i++;
                }

            }
            else
            {
                dolibarr_print_error($db);
            }
        }
        else {
            $this->info_box_contents[0][0] = array('align' => 'left',
            'text' => $langs->trans("ReadPermissionNotAllowed"));
        }
    }

    function showBox()
    {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }

}

?>
