<?php
/* Copyright (C) 2012      Charles-François BENKE <charles.fr@benke.fr>
 * Copyright (C) 2005-2015 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2019 Frederic France        <frederic.france@netlogic.fr>
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
 *  \file       htdocs/core/boxes/box_activity.php
 *  \ingroup    societes
 *  \brief      Module to show box of bills, orders & propal of the current year
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 * Class to manage the box of customer activity (invoice, order, proposal)
 */
class box_activity extends ModeleBoxes
{
    public $boxcode = "activity";
    public $boximg = "object_bill";
    public $boxlabel = 'BoxGlobalActivity';
    public $depends = array("facture");

    /**
     * @var DoliDB Database handler.
     */
    public $db;

    public $param;
    public $enabled = 1;

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
        global $conf, $user;

        $this->db = $db;

        // FIXME: Pb into some status
        $this->enabled = ($conf->global->MAIN_FEATURES_LEVEL); // Not enabled by default due to bugs (see previous comments)

        $this->hidden = !((!empty($conf->facture->enabled) && $user->rights->facture->lire)
            || (!empty($conf->commande->enabled) && $user->rights->commande->lire)
            || (!empty($conf->propal->enabled) && $user->rights->propale->lire)
            );
    }

    /**
     *  Charge les donnees en memoire pour affichage ulterieur
     *
     *  @param  int     $max        Maximum number of records to load
     *  @return void
     */
    public function loadBox($max = 5)
    {
        global $conf, $user, $langs;

        include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
        include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        $totalnb = 0;
        $line = 0;
        $cachetime = 3600;
        $fileid = '-e'.$conf->entity.'-u'.$user->id.'-s'.$user->socid.'-r'.($user->rights->societe->client->voir ? '1' : '0').'.cache';
        $now = dol_now();
        $nbofperiod = 3;

        if (!empty($conf->global->MAIN_BOX_ACTIVITY_DURATION)) $nbofperiod = $conf->global->MAIN_BOX_ACTIVITY_DURATION;
        $textHead = $langs->trans("Activity").' - '.$langs->trans("LastXMonthRolling", $nbofperiod);
        $this->info_box_head = array(
            'text' => $textHead,
            'limit'=> dol_strlen($textHead),
        );

        // compute the year limit to show
        $tmpdate = dol_time_plus_duree(dol_now(), -1 * $nbofperiod, "m");


        // list the summary of the propals
        if (!empty($conf->propal->enabled) && $user->rights->propale->lire)
        {
        	include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
        	$propalstatic = new Propal($this->db);

        	$cachedir = DOL_DATA_ROOT.'/propale/temp';
        	$filename = '/boxactivity-propal'.$fileid;
        	$refresh = dol_cache_refresh($cachedir, $filename, $cachetime);
        	$data = array();
        	if ($refresh)
        	{
        		$sql = "SELECT p.fk_statut, SUM(p.total) as Mnttot, COUNT(*) as nb";
        		$sql .= " FROM (".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p";
        		if (!$user->rights->societe->client->voir && !$user->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        		$sql .= ")";
        		$sql .= " WHERE p.entity IN (".getEntity('propal').")";
        		$sql .= " AND p.fk_soc = s.rowid";
        		if (!$user->rights->societe->client->voir && !$user->socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
        		if ($user->socid) $sql .= " AND s.rowid = ".$user->socid;
        		$sql .= " AND p.datep >= '".$this->db->idate($tmpdate)."'";
        		$sql .= " AND p.date_cloture IS NULL"; // just unclosed
        		$sql .= " GROUP BY p.fk_statut";
        		$sql .= " ORDER BY p.fk_statut DESC";

        		$result = $this->db->query($sql);
        		if ($result)
        		{
        			$num = $this->db->num_rows($result);

        			$j = 0;
        			while ($j < $num) {
        				$data[$j] = $this->db->fetch_object($result);
        				$j++;
        			}
        			if (!empty($conf->global->MAIN_ACTIVATE_FILECACHE)) {
        				dol_filecache($cachedir, $filename, $data);
        			}
        			$this->db->free($result);
        		} else {
        			dol_print_error($this->db);
        		}
        	}
        	else
        	{
        		$data = dol_readcachefile($cachedir, $filename);
        	}

        	if (!empty($data))
        	{
        		$j = 0;
        		while ($j < count($data))
        		{
        			$this->info_box_contents[$line][0] = array(
                        'td' => 'class="left" width="16"',
                        'url' => DOL_URL_ROOT."/comm/propal/list.php?mainmenu=commercial&amp;leftmenu=propals&amp;search_status=".$data[$j]->fk_statut,
                        'tooltip' => $langs->trans("Proposals")."&nbsp;".$propalstatic->LibStatut($data[$j]->fk_statut, 0),
                        'logo' => 'object_propal'
        			);

        			$this->info_box_contents[$line][1] = array(
                        'td' => '',
                        'text' => $langs->trans("Proposals")."&nbsp;".$propalstatic->LibStatut($data[$j]->fk_statut, 0),
        			);

        			$this->info_box_contents[$line][2] = array(
                        'td' => 'class="right"',
                        'text' => $data[$j]->nb,
                        'tooltip' => $langs->trans("Proposals")."&nbsp;".$propalstatic->LibStatut($data[$j]->fk_statut, 0),
                        'url' => DOL_URL_ROOT."/comm/propal/list.php?mainmenu=commercial&amp;leftmenu=propals&amp;search_status=".$data[$j]->fk_statut,
        			);
        			$totalnb += $data[$j]->nb;

        			$this->info_box_contents[$line][3] = array(
                        'td' => 'class="nowraponall right"',
                        'text' => price($data[$j]->Mnttot, 1, $langs, 0, 0, -1, $conf->currency),
        			);
        			$this->info_box_contents[$line][4] = array(
                        'td' => 'class="right" width="18"',
                        'text' => $propalstatic->LibStatut($data[$j]->fk_statut, 3),
        			);

        			$line++;
        			$j++;
        		}
        	}
        }

        // list the summary of the orders
        if (!empty($conf->commande->enabled) && $user->rights->commande->lire) {
            include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
            $commandestatic = new Commande($this->db);

            $langs->load("orders");

            $cachedir = DOL_DATA_ROOT.'/commande/temp';
            $filename = '/boxactivity-order'.$fileid;
            $refresh = dol_cache_refresh($cachedir, $filename, $cachetime);
            $data = array();

            if ($refresh) {
                $sql = "SELECT c.fk_statut, sum(c.total_ttc) as Mnttot, count(*) as nb";
                $sql .= " FROM (".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
                if (!$user->rights->societe->client->voir && !$user->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
                $sql .= ")";
                $sql .= " WHERE c.entity = ".$conf->entity;
                $sql .= " AND c.fk_soc = s.rowid";
                if (!$user->rights->societe->client->voir && !$user->socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
                if ($user->socid) $sql .= " AND s.rowid = ".$user->socid;
                $sql .= " AND c.date_commande >= '".$this->db->idate($tmpdate)."'";
                $sql .= " GROUP BY c.fk_statut";
                $sql .= " ORDER BY c.fk_statut DESC";

                $result = $this->db->query($sql);
                if ($result) {
                    $num = $this->db->num_rows($result);
                    $j = 0;
                    while ($j < $num) {
                        $data[$j] = $this->db->fetch_object($result);
                        $j++;
                    }
                    if (!empty($conf->global->MAIN_ACTIVATE_FILECACHE)) {
                        dol_filecache($cachedir, $filename, $data);
                    }
                    $this->db->free($result);
                } else {
                    dol_print_error($this->db);
                }
            } else {
                $data = dol_readcachefile($cachedir, $filename);
            }

            if (!empty($data)) {
                $j = 0;
                while ($j < count($data)) {
                    $this->info_box_contents[$line][0] = array(
                        'td' => 'class="left" width="16"',
                        'url' => DOL_URL_ROOT."/commande/list.php?mainmenu=commercial&amp;leftmenu=orders&amp;search_status=".$data[$j]->fk_statut,
                        'tooltip' => $langs->trans("Orders")."&nbsp;".$commandestatic->LibStatut($data[$j]->fk_statut, 0, 0),
                        'logo' => 'object_order',
                    );

                    $this->info_box_contents[$line][1] = array(
                        'td' => '',
                        'text' =>$langs->trans("Orders")."&nbsp;".$commandestatic->LibStatut($data[$j]->fk_statut, 0, 0),
                    );

                    $this->info_box_contents[$line][2] = array(
                        'td' => 'class="right"',
                        'text' => $data[$j]->nb,
                        'tooltip' => $langs->trans("Orders")."&nbsp;".$commandestatic->LibStatut($data[$j]->fk_statut, 0, 0),
                        'url' => DOL_URL_ROOT."/commande/list.php?mainmenu=commercial&amp;leftmenu=orders&amp;search_status=".$data[$j]->fk_statut,
                    );
                    $totalnb += $data[$j]->nb;

                    $this->info_box_contents[$line][3] = array(
                        'td' => 'class="nowraponall right"',
                        'text' => price($data[$j]->Mnttot, 1, $langs, 0, 0, -1, $conf->currency),
                    );
                    $this->info_box_contents[$line][4] = array(
                        'td' => 'class="right" width="18"',
                        'text' => $commandestatic->LibStatut($data[$j]->fk_statut, 0, 3),
                    );

                    $line++;
                    $j++;
                }
            }
        }


        // list the summary of the bills
        if (!empty($conf->facture->enabled) && $user->rights->facture->lire)
        {
        	include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
        	$facturestatic = new Facture($this->db);

        	// part 1
        	$cachedir = DOL_DATA_ROOT.'/facture/temp';
        	$filename = '/boxactivity-invoice'.$fileid;

        	$refresh = dol_cache_refresh($cachedir, $filename, $cachetime);
        	$data = array();
        	if ($refresh)
        	{
        		$sql = "SELECT f.fk_statut, SUM(f.total_ttc) as Mnttot, COUNT(*) as nb";
        		$sql .= " FROM (".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
        		if (!$user->rights->societe->client->voir && !$user->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        		$sql .= ")";
        		$sql .= " WHERE f.entity IN (".getEntity('invoice').')';
        		if (!$user->rights->societe->client->voir && !$user->socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
        		if ($user->socid) $sql .= " AND s.rowid = ".$user->socid;
        		$sql .= " AND f.fk_soc = s.rowid";
        		$sql .= " AND f.datef >= '".$this->db->idate($tmpdate)."' AND f.paye=1";
        		$sql .= " GROUP BY f.fk_statut";
        		$sql .= " ORDER BY f.fk_statut DESC";

        		$result = $this->db->query($sql);
        		if ($result) {
        			$num = $this->db->num_rows($result);
        			$j = 0;
        			while ($j < $num) {
        				$data[$j] = $this->db->fetch_object($result);
        				$j++;
        			}
        			if (!empty($conf->global->MAIN_ACTIVATE_FILECACHE)) {
        				dol_filecache($cachedir, $filename, $data);
        			}
        			$this->db->free($result);
        		} else {
        			dol_print_error($this->db);
        		}
        	} else {
        		$data = dol_readcachefile($cachedir, $filename);
        	}

        	if (!empty($data)) {
        		$j = 0;
        		while ($j < count($data)) {
        			$billurl = "search_status=2&amp;paye=1&amp;year=".$data[$j]->annee;
        			$this->info_box_contents[$line][0] = array(
                        'td' => 'class="left" width="16"',
                        'tooltip' => $langs->trans('Bills').'&nbsp;'.$facturestatic->LibStatut(1, $data[$j]->fk_statut, 0),
                        'url' => DOL_URL_ROOT."/compta/facture/list.php?".$billurl."&amp;mainmenu=accountancy&amp;leftmenu=customers_bills",
                        'logo' => 'bill',
        			);

        			$this->info_box_contents[$line][1] = array(
                        'td' => '',
                        'text' => $langs->trans("Bills")."&nbsp;".$facturestatic->LibStatut(1, $data[$j]->fk_statut, 0)." ".$data[$j]->annee,
        			);

        			$this->info_box_contents[$line][2] = array(
                        'td' => 'class="right"',
                        'tooltip' => $langs->trans('Bills').'&nbsp;'.$facturestatic->LibStatut(1, $data[$j]->fk_statut, 0),
                        'text' => $data[$j]->nb,
                        'url' => DOL_URL_ROOT."/compta/facture/list.php?".$billurl."&amp;mainmenu=accountancy&amp;leftmenu=customers_bills",
        			);

        			$this->info_box_contents[$line][3] = array(
                        'td' => 'class="nowraponall right"',
                        'text' => price($data[$j]->Mnttot, 1, $langs, 0, 0, -1, $conf->currency)
        			);

        			// We add only for the current year
       				$totalnb += $data[$j]->nb;

        			$this->info_box_contents[$line][4] = array(
                        'td' => 'class="right" width="18"',
                        'text' => $facturestatic->LibStatut(1, $data[$j]->fk_statut, 3),
        			);
        			$line++;
        			$j++;
        		}
        		if (count($data) == 0)
        			$this->info_box_contents[$line][0] = array(
                        'td' => 'class="center"',
                        'text'=>$langs->trans("NoRecordedInvoices"),
        			);
        	}

        	// part 2
        	$cachedir = DOL_DATA_ROOT.'/facture/temp';
        	$filename = '/boxactivity-invoice2'.$fileid;

        	$refresh = dol_cache_refresh($cachedir, $filename, $cachetime);

        	$data = array();
        	if ($refresh) {
        		$sql = "SELECT f.fk_statut, SUM(f.total_ttc) as Mnttot, COUNT(*) as nb";
        		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
        		$sql .= " WHERE f.entity IN (".getEntity('invoice').')';
        		$sql .= " AND f.fk_soc = s.rowid";
        		$sql .= " AND f.datef >= '".$this->db->idate($tmpdate)."' AND f.paye=0";
        		$sql .= " GROUP BY f.fk_statut";
        		$sql .= " ORDER BY f.fk_statut DESC";

        		$result = $this->db->query($sql);
        		if ($result) {
        			$num = $this->db->num_rows($result);
        			$j = 0;
        			while ($j < $num) {
        				$data[$j] = $this->db->fetch_object($result);
        				$j++;
        			}
        			if (!empty($conf->global->MAIN_ACTIVATE_FILECACHE)) {
        				dol_filecache($cachedir, $filename, $data);
        			}
        			$this->db->free($result);
        		} else {
        			dol_print_error($this->db);
        		}
        	} else {
        		$data = dol_readcachefile($cachedir, $filename);
        	}

        	if (!empty($data)) {
        		$alreadypaid = -1;

        		$j = 0;
        		while ($j < count($data)) {
        			$billurl = "search_status=".$data[$j]->fk_statut."&amp;paye=0";
        			$this->info_box_contents[$line][0] = array(
                        'td' => 'class="left" width="16"',
                        'tooltip' => $langs->trans('Bills').'&nbsp;'.$facturestatic->LibStatut(0, $data[$j]->fk_statut, 0),
                        'url' => DOL_URL_ROOT."/compta/facture/list.php?".$billurl."&amp;mainmenu=accountancy&amp;leftmenu=customers_bills",
                        'logo' => 'bill',
        			);

        			$this->info_box_contents[$line][1] = array(
                        'td' => '',
                        'text' => $langs->trans("Bills")."&nbsp;".$facturestatic->LibStatut(0, $data[$j]->fk_statut, 0),
        			);

        			$this->info_box_contents[$line][2] = array(
                        'td' => 'class="right"',
                        'text' => $data[$j]->nb,
                        'tooltip' => $langs->trans('Bills').'&nbsp;'.$facturestatic->LibStatut(0, $data[$j]->fk_statut, 0),
                        'url' => DOL_URL_ROOT."/compta/facture/list.php?".$billurl."&amp;mainmenu=accountancy&amp;leftmenu=customers_bills",
        			);
        			$totalnb += $data[$j]->nb;
        			$this->info_box_contents[$line][3] = array(
                        'td' => 'class="nowraponall right"',
                        'text' => price($data[$j]->Mnttot, 1, $langs, 0, 0, -1, $conf->currency),
        			);
        			$this->info_box_contents[$line][4] = array(
                        'td' => 'class="right" width="18"',
                        'text' => $facturestatic->LibStatut(0, $data[$j]->fk_statut, 3, $alreadypaid),
        			);
        			$line++;
        			$j++;
        		}
        		if (count($data) == 0) {
        			$this->info_box_contents[$line][0] = array(
                        'td' => 'class="center"',
                        'text'=>$langs->trans("NoRecordedInvoices"),
                    );
                }
        	}
        }

		// Add the sum in the bottom of the boxes
		$this->info_box_contents[$line][0] = array('tr' => 'class="liste_total_wrap"');
		$this->info_box_contents[$line][1] = array('td' => 'class="liste_total left" ', 'text' => $langs->trans("Total")."&nbsp;".$textHead);
		$this->info_box_contents[$line][2] = array('td' => 'class="liste_total right" ', 'text' => $totalnb);
		$this->info_box_contents[$line][3] = array('td' => 'class="liste_total right" ', 'text' => '');
		$this->info_box_contents[$line][4] = array('td' => 'class="liste_total right" ', 'text' => "");
    }


    /**
     *  Method to show box
     *
     *  @param	array	$head       Array with properties of box title
     *  @param  array	$contents   Array with properties of box lines
     *  @param	int		$nooutput	No print, only return string
     *  @return	string
     */
    public function showBox($head = null, $contents = null, $nooutput = 0)
    {
        return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
    }
}
