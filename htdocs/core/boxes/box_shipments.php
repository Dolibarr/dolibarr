<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2019      Alexandre Spangaro   <aspangaro@open-dsi.fr>
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
 *		\file       htdocs/core/boxes/box_shipments.php
 *		\ingroup    shipment
 *		\brief      Module for generating the display of the shipment box
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last shipments
 */
class box_shipments extends ModeleBoxes
{
    public $boxcode = "lastcustomershipments";
    public $boximg = "sending";
    public $boxlabel = "BoxLastCustomerShipments";
    public $depends = array("expedition");

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

        $this->hidden = !($user->rights->expedition->lire);
    }

    /**
     *  Load data for box to show them later
     *
     *  @param	int		$max        Maximum number of records to load
     *  @return	void
     */
    public function loadBox($max = 5)
    {
        global $user, $langs, $conf;
        $langs->loadLangs(array('orders', 'sendings'));

        $this->max = $max;

        include_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
        include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
        include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

        $shipmentstatic = new Expedition($this->db);
        $orderstatic = new Commande($this->db);
        $societestatic = new Societe($this->db);

        $this->info_box_head = array('text' => $langs->trans("BoxTitleLastCustomerShipments", $max));

        if ($user->rights->expedition->lire)
        {
            $sql = "SELECT s.nom as name";
            $sql .= ", s.rowid as socid";
            $sql .= ", s.code_client";
            $sql .= ", s.logo, s.email";
            $sql .= ", e.ref, e.tms";
            $sql .= ", e.rowid";
            $sql .= ", e.ref_customer";
            $sql .= ", e.fk_statut";
            $sql .= ", e.fk_user_valid";
            $sql .= ", c.ref as commande_ref";
            $sql .= ", c.rowid as commande_id";
            $sql .= " FROM ".MAIN_DB_PREFIX."expedition as e";
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON e.rowid = el.fk_target AND el.targettype = 'shipping' AND el.sourcetype IN ('commande')";
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON el.fk_source = c.rowid AND el.sourcetype IN ('commande') AND el.targettype = 'shipping'";
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON e.fk_soc = sc.fk_soc";
            $sql .= " WHERE e.entity IN (".getEntity('expedition').")";
            if (!empty($conf->global->ORDER_BOX_LAST_SHIPMENTS_VALIDATED_ONLY)) $sql .= " AND e.fk_statut = 1";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= " AND sc.fk_user = ".$user->id;
            else $sql .= " ORDER BY e.date_delivery, e.ref DESC ";
            $sql .= $this->db->plimit($max, 0);

            $result = $this->db->query($sql);
            if ($result) {
                $num = $this->db->num_rows($result);

                $line = 0;

                while ($line < $num) {
                    $objp = $this->db->fetch_object($result);

                    $shipmentstatic->id = $objp->rowid;
                    $shipmentstatic->ref = $objp->ref;
                    $shipmentstatic->ref_customer = $objp->ref_customer;

                    $orderstatic->id = $objp->commande_id;
                    $orderstatic->ref = $objp->commande_ref;

                    $societestatic->id = $objp->socid;
                    $societestatic->name = $objp->name;
                    $societestatic->email = $objp->email;
                    $societestatic->code_client = $objp->code_client;
                    $societestatic->logo = $objp->logo;

                    $this->info_box_contents[$line][] = array(
                        'td' => '',
                        'text' => $shipmentstatic->getNomUrl(1),
                        'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
                        'text' => $societestatic->getNomUrl(1),
                        'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => '',
                        'text' => $orderstatic->getNomUrl(1),
                        'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="right" width="18"',
                        'text' => $shipmentstatic->LibStatut($objp->fk_statut, 3),
                    );

                    $line++;
                }

                if ($num == 0) $this->info_box_contents[$line][0] = array('td' => 'class="center"', 'text'=>$langs->trans("NoRecordedShipments"));

                $this->db->free($result);
            } else {
                $this->info_box_contents[0][0] = array(
                    'td' => '',
                    'maxlength'=>500,
                    'text' => ($this->db->error().' sql='.$sql),
                );
            }
        } else {
            $this->info_box_contents[0][0] = array(
                'td' => 'class="nohover opacitymedium left"',
                'text' => $langs->trans("ReadPermissionNotAllowed")
            );
        }
    }

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
    public function showBox($head = null, $contents = null, $nooutput = 0)
    {
        return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
    }
}
