<?php
/* Copyright (C) 2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * 		\file       htdocs/core/boxes/box_services_expired.php
 * 		\ingroup    contracts
 * 		\brief      Module to show the box of last expired services
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show expired services
 */
class box_services_expired extends ModeleBoxes
{

    var $boxcode="expiredservices";     // id of box
    var $boximg="object_contract";
    var $boxlabel="BoxOldestExpiredServices";
    var $depends = array("contrat");	// conf->propal->enabled

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

        $this->hidden=! ($user->rights->contrat->lire);
    }

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

    	$now=dol_now();

    	$this->info_box_head = array('text' => $langs->trans("BoxLastExpiredServices",$max));

    	if ($user->rights->contrat->lire)
    	{
    	    // Select contracts with at least one expired service
			$sql = "SELECT ";
    		$sql.= " c.rowid, c.ref, c.statut as fk_statut, c.date_contrat, c.ref_customer, c.ref_supplier,";
			$sql.= " s.nom as name, s.rowid as socid,";
			$sql.= " MIN(cd.date_fin_validite) as date_line, COUNT(cd.rowid) as nb_services";
    		$sql.= " FROM ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."societe s, ".MAIN_DB_PREFIX."contratdet as cd";
            if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    		$sql.= " WHERE cd.statut = 4 AND cd.date_fin_validite <= '".$db->idate($now)."'";
    		$sql.= " AND c.entity = ".$conf->entity;
    		$sql.= " AND c.fk_soc=s.rowid AND cd.fk_contrat=c.rowid AND c.statut > 0";
            if ($user->societe_id) $sql.=' AND c.fk_soc = '.$user->societe_id;
            if (!$user->rights->societe->client->voir  && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    		$sql.= " GROUP BY c.rowid, c.ref, c.statut, c.date_contrat, c.ref_customer, c.ref_supplier, s.nom, s.rowid";
    		$sql.= " ORDER BY date_line ASC";
    		$sql.= $db->plimit($max, 0);

    		$resql = $db->query($sql);
    		if ($resql)
    		{
    			$num = $db->num_rows($resql);

    			$i = 0;

    			$thirdpartytmp = new Societe($this->db);
    			$contract = new Contrat($this->db);

    			while ($i < $num)
    			{
    			    $late='';

    				$objp = $db->fetch_object($resql);

    				$thirdpartytmp->id = $objp->socid;
    				$thirdpartytmp->name = $objp->name;

    				$contract->id = $objp->rowid;
    				$contract->ref = $objp->ref;
    				$contract->statut = $objp->fk_statut;
    				$contract->ref_customer = $objp->ref_customer;
    				$contract->ref_supplier = $objp->ref_supplier;

					$dateline=$db->jdate($objp->date_line);
					if (($dateline + $conf->contrat->services->expires->warning_delay) < $now) $late=img_warning($langs->trans("Late"));

    				$this->info_box_contents[$i][] = array('td' => '',
    				'text' => $contract->getNomUrl(1),
    				'asis' => 1
    				);

    				$this->info_box_contents[$i][] = array('td' => 'class="tdoverflowmax150 maxwidth150onsmartphone" align="left"',
    				'text' => $thirdpartytmp->getNomUrl(1, 'customer'),
    				'asis' => 1
    				);

    				$this->info_box_contents[$i][] = array('td' => 'align="center"',
    				'text' => dol_print_date($dateline,'day'),
    				'text2'=> $late);

    				$this->info_box_contents[$i][] = array('td' => 'class="right"',
    				'text' => $objp->nb_services);


    				$i++;
    			}

    			if ($num==0)
    			{
    			    $langs->load("contracts");
    			    $this->info_box_contents[$i][] = array('td' => 'align="center" class="nohover opacitymedium"','text'=>$langs->trans("NoExpiredServices"));
    			}

				$db->free($resql);
    		}
    		else
    		{
    			$this->info_box_contents[0][] = array(  'td' => '',
                                                        'maxlength'=>500,
                                                        'text' => ($db->error().' sql='.$sql));
    		}


    	}
    	else
    	{
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

