<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
		\file       htdocs/includes/boxes/box_commandes.php
		\ingroup    commande
		\brief      Module de generation de l'affichage de la box commandes
		\version	$Id$
*/

include_once(DOL_DOCUMENT_ROOT."/includes/boxes/modules_boxes.php");


class box_commandes extends ModeleBoxes {

    var $boxcode="lastcustomerorders";
    var $boximg="object_order";
    var $boxlabel;
    var $depends = array("commandes");

	var $db;
	var $param;

    var $info_box_head = array();
    var $info_box_contents = array();

    /**
     *      \brief      Constructeur de la classe
     */
    function box_commandes()
    {
        global $langs;
        $langs->load("boxes");

        $this->boxlabel=$langs->trans("BoxLastCustomerOrders");
    }

    /**
     *      \brief      Charge les donn�es en m�moire pour affichage ult�rieur
     *      \param      $max        Nombre maximum d'enregistrements � charger
     */
    function loadBox($max=5)
    {
        global $user, $langs, $db;
            
		$this->max=$max;
        
		include_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
        $commandestatic=new Commande($db);

        $this->info_box_head = array('text' => $langs->trans("BoxTitleLastCustomerOrders",$max));

        if ($user->rights->commande->lire)
        {

            $sql = "SELECT s.nom, s.rowid as socid,";
            $sql.= " p.ref, p.tms, p.rowid,";
            $sql.= " p.fk_statut, p.facture";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", sc.fk_soc, sc.fk_user";
            $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as p";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql .= " WHERE p.fk_soc = s.rowid";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
            if($user->societe_id)
            {
                $sql .= " AND s.rowid = ".$user->societe_id;
            }
            $sql .= " ORDER BY p.date_commande DESC, p.ref DESC ";
            $sql .= $db->plimit($max, 0);

            $result = $db->query($sql);

            if ($result)
            {
                $num = $db->num_rows($result);
				
                $i = 0;

                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);
					$datem=$db->jdate($objp->tms);
                    
                    $this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
                    'logo' => $this->boximg,
                    'url' => DOL_URL_ROOT."/commande/fiche.php?id=".$objp->rowid);
                    
                    $this->info_box_contents[$i][1] = array('td' => 'align="left"',
                    'text' => $objp->ref,
                    'url' => DOL_URL_ROOT."/commande/fiche.php?id=".$objp->rowid);

                    $this->info_box_contents[$i][2] = array('td' => 'align="left"',
                    'text' => $objp->nom,
                    'url' => DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->socid);

                    $this->info_box_contents[$i][3] = array('td' => 'align="right"',
                    'text' => dol_print_date($datem,'day'),
                    );
                    
                    $this->info_box_contents[$i][4] = array('td' => 'align="right" width="18"',
                    'text' => $commandestatic->LibStatut($objp->fk_statut,$objp->facturee,3));

                    $i++;
                }
                
                if ($num==0) $this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=>$langs->trans("NoRecordedOrders"));
            }
            else {
                dol_print_error($db);
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
