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
 *
 * $Id$
 * $Source$
 *
 */

/**
    \file       htdocs/includes/boxes/box_prospect.php
    \ingroup    commercial
    \brief      Module de génération de l'affichage de la box prospect
*/


include_once("./includes/boxes/modules_boxes.php");


class box_prospect extends ModeleBoxes {

    var $info_box_head = array();
    var $info_box_contents = array();

    function loadBox($max=5)
    {
        global $user, $langs, $db;

        $this->info_box_head = array('text' => "Les $max derniers prospects enregistrés");

        $sql = "SELECT s.nom,s.idp";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe as s WHERE s.client = 2";
        if ($user->societe_id > 0)
        {
            $sql .= " AND s.idp = $user->societe_id";
        }
        $sql .= " ORDER BY s.datec DESC ";
        $sql .= $db->plimit($max, 0);

        $result = $db->query($sql);

        if ($result)
        {
            $num = $db->num_rows();

            $i = 0;

            while ($i < $num)
            {
                $objp = $db->fetch_object($result);

                $this->info_box_contents[$i][0] = array('align' => 'left',
                'logo' => 'object_company',
                'text' => stripslashes($objp->nom),
                'url' => DOL_URL_ROOT."/comm/prospect/fiche.php?id=".$objp->idp);

                $i++;
            }
        }

    }

    function showBox()
    {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }

}

?>
