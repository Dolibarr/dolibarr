<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/includes/boxes/box_external_rss.php
        \ingroup    external_rss
		\brief      Fichier de gestion d'une box pour le module external_rss
		\version    $Revision$
*/

include_once(MAGPIERSS_PATH."rss_fetch.inc");
include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");


class box_external_rss extends ModeleBoxes {

    var $boxcode="lastrssinfos";
    var $boximg="object_rss";
    var $boxlabel;
    var $depends = array();

    var $info_box_head = array();
    var $info_box_contents = array();

    /**
     *      \brief      Constructeur de la classe
     */
    function box_external_rss()
    {
        global $langs;
        $langs->load("boxes");

        $this->boxlabel=$langs->trans("BoxLastRssInfos");
    }

    /**
     *      \brief      Charge les données en mémoire pour affichage ultérieur
     *      \param      $max        Nombre maximum d'enregistrements à charger
     */
    function loadBox($max=5)
    {
        global $user, $langs, $db;
        $langs->load("boxes");

        for($site = 0; $site < 1; $site++) {
            $rss = fetch_rss( @constant("EXTERNAL_RSS_URLRSS_" . $site) );
            
            $title=$langs->trans("BoxTitleLastRssInfos",$max, @constant("EXTERNAL_RSS_TITLE_". $site));
            if ($rss->ERROR) {
                // Affiche warning car il y a eu une erreur
                $title.=" ".img_error($langs->trans("FailedToRefreshDataInfoNotUpToDate",(isset($rss->date)?dolibarr_print_date($rss->date,"%d %b %Y %H:%M"):'unknown date')));
                $this->info_box_head = array('text' => $title);
            }
            
            $this->info_box_head = array('text' => $title);

            for($i = 0; $i < $max ; $i++){
                $item = $rss->items[$i];
                $href = $item['link'];
                $title = utf8_decode(urldecode($item['title']));
                $title=ereg_replace("([[:alnum:]])\?([[:alnum:]])","\\1'\\2",$title);   // Gère problème des apostrophes mal codée/décodée par utf8
                $title=ereg_replace("^\s+","",$title);                                  // Supprime espaces de début
                $this->info_box_contents["$href"]="$title";
                $this->info_box_contents[$i][0] = array('align' => 'left',
                'logo' => $this->boximg,
                'text' => $title,
                'url' => $href);
            }


        }
    }

    function showBox()
    {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }

}

?>
