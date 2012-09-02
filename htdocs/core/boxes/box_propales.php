<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
    var $boxlabel;
    var $depends = array("propal");	// conf->propal->enabled

    var $db;
    var $param;

    var $info_box_head = array();
    var $info_box_contents = array();


    /**
     *  Constructor
     */
    function __construct()
    {
    	global $langs;
      $langs->load("boxes");

      $this->boxlabel=$langs->transnoentitiesnoconv("BoxLastProposals");
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
      $propalstatic=new Propal($db);

      $this->info_box_head = array('text' => $langs->trans("BoxTitleLastPropals",$max));

      if ($user->rights->propale->lire)
      {
      	$sql = "SELECT s.nom, s.rowid as socid,";
        $sql.= " p.rowid, p.ref, p.fk_statut, p.datep as dp, p.datec, p.fin_validite, p.date_cloture";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
        $sql.= ", ".MAIN_DB_PREFIX."propal as p";
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE p.fk_soc = s.rowid";
        $sql.= " AND p.entity = ".$conf->entity;
        if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        if($user->societe_id) $sql.= " AND s.rowid = ".$user->societe_id;
        $sql.= " ORDER BY p.datep DESC, p.ref DESC ";
        $sql.= $db->plimit($max, 0);

        $result = $db->query($sql);

        if ($result)
        {
        	$num = $db->num_rows($result);
        	$now=dol_now();

        	$i = 0;

        	while ($i < $num)
        	{
        		$objp = $db->fetch_object($result);
        		$datec=$db->jdate($objp->datec);
        		$dateterm=$db->jdate($objp->fin_validite);
        		$dateclose=$db->jdate($objp->date_cloture);

        		$late = '';
        		if ($objp->fk_statut == 1 && $dateterm < ($now - $conf->propal->cloture->warning_delay)) { $late = img_warning($langs->trans("Late")); }

        		$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
        		'logo' => $this->boximg,
        		'url' => DOL_URL_ROOT."/comm/propal.php?id=".$objp->rowid);

        		$this->info_box_contents[$i][1] = array('td' => 'align="left"',
        		'text' => $objp->ref,
        		'text2'=> $late,
        		'url' => DOL_URL_ROOT."/comm/propal.php?id=".$objp->rowid);

				$this->info_box_contents[$i][2] = array('td' => 'align="left" width="16"',
                'logo' => 'company',
                'url' => DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->socid);

				$this->info_box_contents[$i][3] = array('td' => 'align="left"',
        		'text' => dol_trunc($objp->nom,40),
        		'url' => DOL_URL_ROOT."/comm/fiche.php?socid=".$objp->socid);

        		$this->info_box_contents[$i][4] = array('td' => 'align="right"',
        		'text' => dol_print_date($datec,'day'));

        		$this->info_box_contents[$i][5] = array('td' => 'align="right" width="18"',
        		'text' => $propalstatic->LibStatut($objp->fk_statut,3));

        		$i++;
        	}

        	if ($num==0) $this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=>$langs->trans("NoRecordedProposals"));
        }
        else
        {
        	$this->info_box_contents[0][0] = array(    'td' => 'align="left"',
                                                        'maxlength'=>500,
                                                        'text' => ($db->error().' sql='.$sql));
        }
      }
      else
      {
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
