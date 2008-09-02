<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
    \file       htdocs/includes/boxes/box_osc_client.php
    \ingroup    osc
    \brief      Module de génération de l'affichage de la box osc client
	\version	$Id$
*/

include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");


class box_osc_clients extends ModeleBoxes {

    var $boxcode="nbofcustomers";
    var $boximg="object_company";
    var $boxlabel;
    var $depends = array("boutique");

	var $db;
	var $param;

    var $info_box_head = array();
    var $info_box_contents = array();

    /**
     *      \brief      Constructeur de la classe
     */
    function box_osc_clients()
    {
        global $langs;
        $langs->load("boxes");

        $this->boxlabel=$langs->trans("BoxNbOfCustomers");
    }

    /**
     *      \brief      Charge les données en mémoire pour affichage ultérieur
     *      \param      $max        Nombre maximum d'enregistrements à charger
     */
    function loadBox($max=5)
    {
        global $user, $langs, $db;
        $langs->load("boxes");

        $this->info_box_head = array('text' => $langs->trans("BoxTitleNbOfCustomers",$max));

        if ($user->rights->boutique->lire)
        {
            $sql = "SELECT count(*) as cus FROM ".OSC_DB_NAME.".".OSC_DB_TABLE_PREFIX."customers";
    
            $result = $db->query($sql);
            if ($result)
            {
                $num = $db->num_rows();
    
                $i = 0;
    
                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);
    
                    $this->info_box_contents[$i][0] = array('align' => 'center',
                    'logo' => $this->boximg,
                    'text' => $objp->cus,
                    'url' => DOL_URL_ROOT."/boutique/client/index.php");
                    $i++;
                }
            }
            else {
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
