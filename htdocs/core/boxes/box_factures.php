<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/boxes/box_factures.php
 *	\ingroup    factures
 *	\brief      Module de generation de l'affichage de la box factures
 */
include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 * Class to manage the box to show last invoices
 */
class box_factures extends ModeleBoxes
{
	var $boxcode="lastcustomerbills";
	var $boximg="object_bill";
	var $boxlabel="BoxLastCustomerBills";
	var $depends = array("facture");

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

		$this->max=$max;

        include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
        include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

        $facturestatic=new Facture($db);
        $societestatic = new Societe($db);

		$text = $langs->trans("BoxTitleLast".($conf->global->MAIN_LASTBOX_ON_OBJECT_DATE?"":"Modified")."CustomerBills",$max);
		$this->info_box_head = array(
				'text' => $text,
				'limit'=> dol_strlen($text)
		);

        if ($user->rights->facture->lire) {
            $sql = "SELECT f.rowid as facid";
            $sql.= ", f.facnumber, f.type, f.total as total_ht";
            $sql.= ", f.tva as total_tva";
            $sql.= ", f.total_ttc";
            $sql.= ", f.datef as df";
			$sql.= ", f.paye, f.fk_statut, f.datec, f.tms";
            $sql.= ", s.nom as name";
            $sql.= ", s.rowid as socid";
            $sql.= ", s.code_client";
			$sql.= ", f.date_lim_reglement as datelimite";
			$sql.= " FROM (".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql.= ")";
			$sql.= " WHERE f.fk_soc = s.rowid";
			$sql.= " AND f.entity = ".$conf->entity;
			if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
			if($user->societe_id)	$sql.= " AND s.rowid = ".$user->societe_id;
            if ($conf->global->MAIN_LASTBOX_ON_OBJECT_DATE) $sql.= " ORDER BY f.datef DESC, f.facnumber DESC ";
            else $sql.= " ORDER BY f.tms DESC, f.facnumber DESC ";
			$sql.= $db->plimit($max, 0);

			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);
				$now=dol_now();

				$line = 0;
				$l_due_date = $langs->trans('Late').' ('.strtolower($langs->trans('DateEcheance')).': %s)';

                while ($line < $num) {
                    $objp = $db->fetch_object($result);
                    $datelimite = $db->jdate($objp->datelimite);
                    $date = $db->jdate($objp->df);
                    $datem = $db->jdate($objp->tms);
                    $facturestatic->id = $objp->facid;
                    $facturestatic->ref = $objp->facnumber;
                    $facturestatic->type = $objp->type;
                    $facturestatic->total_ht = $objp->total_ht;
                    $facturestatic->total_tva = $objp->total_tva;
                    $facturestatic->total_ttc = $objp->total_ttc;
                    $facturestatic->statut = $objp->fk_statut;
                    $facturestatic->date_lim_reglement = $db->jdate($objp->datelimite);

                    $societestatic->id = $objp->socid;
                    $societestatic->name = $objp->name;
                    $societestatic->code_client = $objp->code_client;


					$late = '';
					if ($facturestatic->hasDelay()) {
                        $late = img_warning(sprintf($l_due_date,dol_print_date($datelimite,'day')));
                    }

                    $this->info_box_contents[$line][] = array(
                        'td' => 'align="left"',
                        'text' => $facturestatic->getNomUrl(1),
                        'text2'=> $late,
                        'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'align="left"',
                        'text' => $societestatic->getNomUrl(1, '', 40),
                        'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'align="right"',
                        'text' => price($objp->total_ht, 0, $langs, 0, -1, -1, $conf->currency),
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'align="right"',
                        'text' => dol_print_date($date,'day'),
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'align="right" width="18"',
                        'text' => $facturestatic->LibStatut($objp->paye,$objp->fk_statut,3),
                    );

                    $line++;
                }

                if ($num==0)
                    $this->info_box_contents[$line][0] = array(
                        'td' => 'align="center"',
                        'text'=>$langs->trans("NoRecordedInvoices"),
                    );

                $db->free($result);
            } else {
                $this->info_box_contents[0][0] = array(
                    'td' => 'align="left"',
                    'maxlength'=>500,
                    'text' => ($db->error().' sql='.$sql),
                );
            }

        } else {
            $this->info_box_contents[0][0] = array(
                'td' => 'align="left"',
                'text' => $langs->trans("ReadPermissionNotAllowed"),
            );
        }
    }

	/**
	 *  Method to show box
	 *
	 *  @param  array   $head       Array with properties of box title
	 *  @param  array   $contents   Array with properties of box lines
	 *  @return void
	 */
	function showBox($head = null, $contents = null)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}

}
