<?php
/* Copyright (C) 2004-2006 Destailleur Laurent  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015-2019 Frederic France      <frederic.france@netlogic.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       htdocs/core/boxes/box_supplier_orders.php
 * \ingroup    fournisseurs
 * \brief      Module that generates the latest supplier orders box
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 * Class that manages the box showing latest supplier orders
 */
class box_supplier_orders extends ModeleBoxes
{

    public $boxcode = "latestsupplierorders";
    public $boximg = "object_order";
    public $boxlabel="BoxLatestSupplierOrders";
    public $depends = array("fournisseur");

    /**
     * @var DoliDB Database handler.
     */
    public $db;

    public $param;
    public $info_box_head = array();
    public $info_box_contents = array();


    /**
     *  Constructor
     *
     *  @param  DoliDB  $db         Database handler
     *  @param  string  $param      More parameters
     */
    public function __construct($db, $param)
    {
        global $user;

        $this->db = $db;

        $this->hidden = ! ($user->rights->fournisseur->commande->lire);
    }

    /**
     *  Load data into info_box_contents array to show array later.
     *
     *  @param	int		$max        Maximum number of records to load
     *  @return	void
     */
    public function loadBox($max = 5)
    {
        global $conf, $user, $langs;
        $langs->load("boxes");

        $this->max = $max;

        include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
        $supplierorderstatic=new CommandeFournisseur($this->db);
        include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
        $thirdpartytmp = new Fournisseur($this->db);

        $this->info_box_head = array('text' => $langs->trans("BoxTitleLatest".($conf->global->MAIN_LASTBOX_ON_OBJECT_DATE?"":"Modified")."SupplierOrders", $max));

        if ($user->rights->fournisseur->commande->lire)
        {
            $sql = "SELECT s.nom as name, s.rowid as socid,";
            $sql.= " s.code_client, s.code_fournisseur,";
            $sql.= " s.logo, s.email,";
            $sql.= " c.rowid, c.ref, c.tms, c.date_commande,";
            $sql.= " c.total_ht,";
            $sql.= " c.tva as total_tva,";
            $sql.= " c.total_ttc,";
            $sql.= " c.fk_statut";
            $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
            $sql.= ", ".MAIN_DB_PREFIX."commande_fournisseur as c";
            if (!$user->rights->societe->client->voir && !$user->socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql.= " WHERE c.fk_soc = s.rowid";
            $sql.= " AND c.entity IN (".getEntity('supplier_order').")";
            if (!$user->rights->societe->client->voir && !$user->socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
            if ($user->socid) $sql.= " AND s.rowid = ".$user->socid;
            if ($conf->global->MAIN_LASTBOX_ON_OBJECT_DATE) $sql.= " ORDER BY c.date_commande DESC, c.ref DESC ";
            else $sql.= " ORDER BY c.tms DESC, c.ref DESC ";
            $sql.= $this->db->plimit($max, 0);

            $result = $this->db->query($sql);
            if ($result)
            {
                $num = $this->db->num_rows($result);

                $line = 0;
                while ($line < $num) {
                    $objp = $this->db->fetch_object($result);
                    $date=$this->db->jdate($objp->date_commande);
					$datem=$this->db->jdate($objp->tms);

					$supplierorderstatic->id = $objp->rowid;
					$supplierorderstatic->ref = $objp->ref;

					$thirdpartytmp->id = $objp->socid;
                    $thirdpartytmp->name = $objp->name;
                    $thirdpartytmp->email = $objp->email;
                    $thirdpartytmp->fournisseur = 1;
                    $thirdpartytmp->code_fournisseur = $objp->code_fournisseur;
                    $thirdpartytmp->logo = $objp->logo;

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="nowraponall"',
                        'text' => $supplierorderstatic->getNomUrl(1),
                    	'asis' => 1
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
                        'text' => $thirdpartytmp->getNomUrl(1, 'supplier'),
                        'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="right nowraponall"',
                        'text' => price($objp->total_ht, 0, $langs, 0, -1, -1, $conf->currency),
                    );

					$this->info_box_contents[$line][] = array(
                        'td' => 'class="right"',
                        'text' => dol_print_date($date, 'day'),
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="right" width="18"',
                        'text' => $supplierorderstatic->LibStatut($objp->fk_statut, 3),
                    );

                    $line++;
                }

                if ($num == 0)
                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="center"',
                        'text' => $langs->trans("NoSupplierOrder"),
                    );

                $this->db->free($result);
            } else {
                $this->info_box_contents[0][] = array(
                    'td' => '',
                    'maxlength'=>500,
                    'text' => ($this->db->error().' sql='.$sql),
                );
            }
        }
        else
        {
            $this->info_box_contents[0][] = array(
                'td' => 'class="nohover opacitymedium left"',
                'text' => $langs->trans("ReadPermissionNotAllowed")
            );
        }
    }

    /**
     *  Method to show box
     *
     *  @param  array   $head       Array with properties of box title
     *  @param  array   $contents   Array with properties of box lines
     *  @param  int     $nooutput   No print, only return string
     *  @return string
     */
    public function showBox($head = null, $contents = null, $nooutput = 0)
    {
        return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
    }
}
