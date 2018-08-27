<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * \file       htdocs/core/boxes/box_propales.php
 * \ingroup    propales
 * \brief      Module de generation de l'affichage de la box propales
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last proposals
 */
class box_propales extends ModeleBoxes
{
    var $boxcode="lastpropals";
    var $boximg="object_propal";
    var $boxlabel="BoxLastProposals";
    var $depends = array("propal");	// conf->propal->enabled

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

        $this->hidden=! ($user->rights->propale->lire);
    }

    /**
	 *  Load data into info_box_contents array to show array later.
	 *
	 *  @param	int		$max        Maximum number of records to load
     *  @return	void
     */
    function loadBox($max=5)
    {
    	global $user, $langs, $db, $conf;

    	$this->max=$max;

    	include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
        include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
    	$propalstatic=new Propal($db);
        $societestatic = new Societe($db);

        $this->info_box_head = array('text' => $langs->trans("BoxTitleLast".($conf->global->MAIN_LASTBOX_ON_OBJECT_DATE?"":"Modified")."Propals",$max));

    	if ($user->rights->propale->lire)
    	{
    		$sql = "SELECT s.nom as name, s.rowid as socid, s.code_client, s.logo,";
    		$sql.= " p.rowid, p.ref, p.fk_statut, p.datep as dp, p.datec, p.fin_validite, p.date_cloture, p.total_ht, p.tva as total_tva, p.total as total_ttc, p.tms";
    		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
    		$sql.= ", ".MAIN_DB_PREFIX."propal as p";
    		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    		$sql.= " WHERE p.fk_soc = s.rowid";
    		$sql.= " AND p.entity = ".$conf->entity;
    		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    		if($user->societe_id) $sql.= " AND s.rowid = ".$user->societe_id;
            if ($conf->global->MAIN_LASTBOX_ON_OBJECT_DATE) $sql.= " ORDER BY p.datep DESC, p.ref DESC ";
            else $sql.= " ORDER BY p.tms DESC, p.ref DESC ";
    		$sql.= $db->plimit($max, 0);

    		$result = $db->query($sql);
    		if ($result)
    		{
    			$num = $db->num_rows($result);
    			$now=dol_now();

    			$line = 0;

                while ($line < $num) {
    				$objp = $db->fetch_object($result);
    				$date=$db->jdate($objp->dp);
    				$datec=$db->jdate($objp->datec);
    				$datem=$db->jdate($objp->tms);
    				$dateterm=$db->jdate($objp->fin_validite);
    				$dateclose=$db->jdate($objp->date_cloture);
                    $propalstatic->id = $objp->rowid;
                    $propalstatic->ref = $objp->ref;
                    $propalstatic->total_ht = $objp->total_ht;
                    $propalstatic->total_tva = $objp->total_tva;
                    $propalstatic->total_ttc = $objp->total_ttc;
                    $societestatic->id = $objp->socid;
                    $societestatic->name = $objp->name;
                    $societestatic->code_client = $objp->code_client;
                    $societestatic->logo = $objp->logo;

    				$late = '';
    				if ($objp->fk_statut == 1 && $dateterm < ($now - $conf->propal->cloture->warning_delay)) {
    					$late = img_warning($langs->trans("Late"));
    				}

                    $this->info_box_contents[$line][] = array(
                        'td' => '',
                        'text' => $propalstatic->getNomUrl(1),
                        'text2'=> $late,
                        'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
                        'text' => $societestatic->getNomUrl(1),
                        'asis' => 1,
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="right nowraponall"',
                        'text' => price($objp->total_ht, 0, $langs, 0, -1, -1, $conf->currency),
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'class="right"',
                        'text' => dol_print_date($date,'day'),
                    );

                    $this->info_box_contents[$line][] = array(
                        'td' => 'align="right" width="18"',
                        'text' => $propalstatic->LibStatut($objp->fk_statut,3),
                    );

                    $line++;
                }

                if ($num==0)
                    $this->info_box_contents[$line][0] = array(
                        'td' => 'align="center"',
                        'text'=>$langs->trans("NoRecordedProposals"),
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

