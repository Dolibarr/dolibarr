<?php
/* Copyright (C) 2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015 Frederic France      <frederic.france@free.fr>
 * Copyright (C) 2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * 		\file       htdocs/core/boxes/box_contracts.php
 * 		\ingroup    contracts
 * 		\brief      Module de generation de l'affichage de la box contracts
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last contracts
 */
class box_contracts extends ModeleBoxes
{
    var $boxcode="lastcontracts";
    var $boximg="object_contract";
    var $boxlabel="BoxLastContracts";
    var $depends = array("contrat");	// conf->contrat->enabled

    var $db;
    var $param;

    var $info_box_head = array();
    var $info_box_contents = array();


    /**
     *  Load data for box to show them later
     *
     *  @param	int		$max        Maximum number of records to load
     *  @return	void
     */
    function loadBox($max=5)
    {
    	global $user, $langs, $db, $conf;

    	$this->max=$max;

    	include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';

    	$this->info_box_head = array('text' => $langs->trans("BoxTitleLastContracts",$max));

    	if ($user->rights->contrat->lire)
    	{
        	$contractstatic=new Contrat($db);
        	$thirdpartytmp=new Societe($db);

    	    $sql = "SELECT s.nom as name, s.rowid as socid,";
    		$sql.= " c.rowid, c.ref, c.statut as fk_statut, c.date_contrat, c.datec, c.fin_validite, c.date_cloture";
    		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."contrat as c";
    		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    		$sql.= " WHERE c.fk_soc = s.rowid";
    		$sql.= " AND c.entity = ".$conf->entity;
    		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    		if($user->societe_id) $sql.= " AND s.rowid = ".$user->societe_id;
    		if ($conf->global->MAIN_LASTBOX_ON_OBJECT_DATE) $sql.= " ORDER BY c.date_contrat DESC, c.ref DESC ";
    		else $sql.= " ORDER BY c.tms DESC, c.ref DESC ";
    		$sql.= $db->plimit($max, 0);

    		$resql = $db->query($sql);
    		if ($resql)
    		{
    			$num = $db->num_rows($resql);
    			$now=dol_now();

    			$line = 0;

    			$langs->load("contracts");

                while ($line < $num)
                {
    				$objp = $db->fetch_object($resql);
    				$datec=$db->jdate($objp->datec);
    				$dateterm=$db->jdate($objp->fin_validite);
    				$dateclose=$db->jdate($objp->date_cloture);
    				$late = '';

    				$contractstatic->statut=$objp->fk_statut;
    				$contractstatic->id=$objp->rowid;
    				$contractstatic->ref=$objp->ref;
    				$result=$contractstatic->fetch_lines();

    				$thirdpartytmp->name = $objp->name;
    				$thirdpartytmp->id = $objp->socid;

    				// fin_validite is no more on contract but on services
    				// if ($objp->fk_statut == 1 && $dateterm < ($now - $conf->contrat->cloture->warning_delay)) { $late = img_warning($langs->trans("Late")); }

                    $this->info_box_contents[$line][] = array(
                        'td' => 'align="left"',
                        'text' => $contractstatic->getNomUrl(1),
                        'text2'=> $late,
                        'asis'=>1
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'align="left"',
                        'text' => $thirdpartytmp->getNomUrl(1),
                        'asis'=>1
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'align="right"',
                        'text' => dol_print_date($datec,'day'),
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'align="right" class="nowrap"',
                        'text' => $contractstatic->getLibStatut(6),
                        'asis'=>1,
                    );

                    $line++;
                }

                if ($num==0)
                    $this->info_box_contents[$line][0] = array(
                        'td' => 'align="center"',
                        'text'=>$langs->trans("NoRecordedContracts"),
                    );

                $db->free($resql);
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
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	void
	 */
    function showBox($head = null, $contents = null, $nooutput=0)
    {
        parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
    }

}

