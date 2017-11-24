<?php
/* Copyright (C) 2004-2006 Destailleur Laurent  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
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
 * \file       htdocs/core/boxes/box_fournisseurs.php
 * \ingroup    fournisseurs
 * \brief      Module to generate box of suppliers
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last suppliers
 */
class box_fournisseurs extends ModeleBoxes
{
    var $boxcode="lastsuppliers";
    var $boximg="object_company";
    var $boxlabel="BoxLastSuppliers";
    var $depends = array("fournisseur");

	var $db;
	var $param;

    var $info_box_head = array();
    var $info_box_contents = array();


    /**
     *  Constructor
     *
     *  @param  DoliDB  $db         Database handler
     *  @param  string  $param      More parameters
     */
    function __construct($db,$param)
    {
        global $user;

        $this->db=$db;

        $this->hidden=! ($user->rights->societe->lire && empty($user->socid));
    }

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

        include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        $thirdpartystatic=new Societe($db);
		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
		$thirdpartytmp=new Fournisseur($db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLastModifiedSuppliers",$max));

        if ($user->rights->societe->lire)
        {
            $sql = "SELECT s.nom as name, s.rowid as socid, s.datec, s.tms, s.status,";
            $sql.= " s.code_fournisseur,";
            $sql.= " s.logo";
            $sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
            $sql.= " WHERE s.fournisseur = 1";
            $sql.= " AND s.entity IN (".getEntity('societe').")";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
            if ($user->societe_id) $sql.= " AND s.rowid = ".$user->societe_id;
            $sql.= " ORDER BY s.tms DESC ";
            $sql.= $db->plimit($max, 0);

            $result = $db->query($sql);
            if ($result)
            {
                $num = $db->num_rows($result);

                $line = 0;
                while ($line < $num)
                {
                    $objp = $db->fetch_object($result);
    				$datec=$db->jdate($objp->datec);
    				$datem=$db->jdate($objp->tms);
					$thirdpartytmp->id = $objp->socid;
                    $thirdpartytmp->name = $objp->name;
                    $thirdpartytmp->code_client = $objp->code_client;
                    $thirdpartytmp->logo = $objp->logo;

                   	$this->info_box_contents[$line][] = array(
                        'td' => '',
                        'text' => $thirdpartytmp->getNomUrl(1, '', 40),
                        'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="right"',
                        'text' => dol_print_date($datem, "day"),
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'align="right" width="18"',
                        'text' => $thirdpartystatic->LibStatut($objp->status,3),
                    );

                    $line++;
                }

                if ($num==0) $this->info_box_contents[$line][0] = array(
                    'td' => 'align="center"',
                    'text'=>$langs->trans("NoRecordedSuppliers"),
                );

                $db->free($result);
            } else {
                $this->info_box_contents[0][0] = array(
                    'td' => '',
                    'maxlength'=>500,
                    'text' => ($db->error().' sql='.$sql),
                );
            }
        } else {
            $this->info_box_contents[0][0] = array(
                'td' => 'align="left" class="nohover opacitymedium"',
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
    function showBox($head = null, $contents = null, $nooutput=0)
    {
        return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
    }

}

