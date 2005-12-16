<?php
/* Copyright (C) 2004-2005 Destailleur Laurent <eldy@users.sourceforge.net>
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
 */

/**
    \file       htdocs/includes/boxes/box_fournisseurs.php
    \ingroup    fournisseurs
    \brief      Module de génération de l'affichage de la box fournisseurs
*/

include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");


class box_fournisseurs extends ModeleBoxes {

    var $boxcode="lastsuppliers";
    var $boximg="object_company";
    var $boxlabel;
    var $depends = array("fournisseur");

    var $info_box_head = array();
    var $info_box_contents = array();

    /**
     *      \brief      Constructeur de la classe
     */
    function box_fournisseurs()
    {
        global $langs;
        $langs->load("boxes");

        $this->boxlabel=$langs->trans("BoxLastSuppliers");
    }

    /**
     *      \brief      Charge les données en mémoire pour affichage ultérieur
     *      \param      $max        Nombre maximum d'enregistrements à charger
     */
    function loadBox($max=5)
    {
        global $user, $langs, $db;
        $langs->load("boxes");

        $this->info_box_head = array('text' => $langs->trans("BoxTitleLastSuppliers",$max));

        if ($user->rights->societe->lire)
        {
            $sql = "SELECT s.nom,s.idp";
            $sql .= " FROM ".MAIN_DB_PREFIX."societe as s WHERE s.fournisseur = 1";
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
                    'logo' => $this->boximg,
                    'text' => $objp->nom,
                    'url' => DOL_URL_ROOT."/fourn/fiche.php?socid=".$objp->idp);
    
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
