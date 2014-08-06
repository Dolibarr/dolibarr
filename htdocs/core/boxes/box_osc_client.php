<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file       htdocs/core/boxes/box_osc_client.php
 * \ingroup    osc
 * \brief      Module to generate box of shop customers
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last customers of shop
 */
class box_osc_clients extends ModeleBoxes
{
    var $boxcode="nbofcustomers";
    var $boximg="object_company";
    var $boxlabel="BoxNbOfCustomers";
    var $depends = array("boutique");

	var $db;
	var $param;

    var $info_box_head = array();
    var $info_box_contents = array();


    /**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
     */
    function loadBox($max=5)
    {
        global $conf, $user, $langs, $db;
        $langs->load("boxes");

		$this->max=$max;

		$this->info_box_head = array('text' => $langs->trans("BoxTitleNbOfCustomers",$max));

        if ($user->rights->boutique->lire)
        {
            $sql = "SELECT count(*) as cus FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."customers";

            $resql = $db->query($sql);
            if ($resql)
            {
                $num = $db->num_rows($resql);

                $i = 0;

                while ($i < $num)
                {
                    $objp = $db->fetch_object($resql);

                    $this->info_box_contents[$i][0] = array('td' => 'align="center" width="16"',
                    'logo' => $this->boximg,
                    'url' => DOL_URL_ROOT."/boutique/client/index.php");
                    $this->info_box_contents[$i][1] = array('td' => 'align="center"',
                    'text' => $objp->cus,
                    'url' => DOL_URL_ROOT."/boutique/client/index.php");
                    $i++;
                }

				$db->free($resql);
            }
            else {
                $this->info_box_contents[0][0] = array( 'td' => 'align="left"',
                                                        'maxlength'=>500,
                                                        'text' => ($db->error().' sql='.$sql));
            }
        }
        else {
            $this->info_box_contents[0][0] = array('td' => 'align="left"',
            'text' => $langs->trans("ReadPermissionNotAllowed"));
        }

    }

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *	@return	void
	 */
    function showBox($head = null, $contents = null)
    {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }

}

?>
