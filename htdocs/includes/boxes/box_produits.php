<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
    \file       htdocs/includes/boxes/box_produits.php
    \ingroup    produits,services
    \brief      Module de génération de l'affichage de la box produits
*/

include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");


class box_produits extends ModeleBoxes {

    var $boxcode="lastproducts";
    var $boximg="product";
    var $boxlabel;
    var $depends = array("produit");
    
    var $info_box_head = array();
    var $info_box_contents = array();


    /**
     *      \brief      Constructeur de la classe
     */
    function box_produits()
    {
        global $langs;
        $langs->load("boxes");

        $this->boxlabel=$langs->trans("BoxLastProducts");
    }
    
    /**
     *      \brief      Charge les données en mémoire pour affichage ultérieur
     *      \param      $max        Nombre maximum d'enregistrements à charger
     */
    function loadBox($max=5)
    {
        global $user, $langs, $db;
        $langs->load("boxes");

        $this->info_box_head = array('text' => "Les $max derniers produits/services enregistrés");

        if ($user->rights->produit->lire)
        {
            $sql = "SELECT p.label, p.rowid, p.price, p.fk_product_type";
            $sql .= " FROM ".MAIN_DB_PREFIX."product as p";
            $sql .= " ORDER BY p.datec DESC";
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
                    'logo' => ($objp->fk_product_type?'object_service':'object_product'),
                    'text' => $objp->label,
                    'url' => DOL_URL_ROOT."/product/fiche.php?id=".$objp->rowid);
    
                    $this->info_box_contents[$i][1] = array('align' => 'right',
                    'text' => price($objp->price));
                    $i++;
                }
            }
            else {
                dolibarr_print_error($db);
            }
        }
    }
    
    function showBox()
    {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }
   
}

?>
