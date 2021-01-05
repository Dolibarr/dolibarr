<?php
/* Copyright (C) 2012-2014 Charles-François BENKE <charles.fr@benke.fr>
 * Copyright (C) 2014      Marcos García          <marcosgdf@gmail.com>
 * Copyright (C) 2015      Frederic France        <frederic.france@free.fr>
 * Copyright (C) 2016      Juan José Menent       <jmenent@2byte.es>
 * Copyright (C) 2020      Pierre Ardoin          <mapiolca@me.com>
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
 *  \file       htdocs/core/boxes/box_funnel_of_prospection.php
 *  \ingroup    projet
 *  \brief      Module to show the funnel of prospection
 */
include_once DOL_DOCUMENT_ROOT . "/core/boxes/modules_boxes.php";

/**
 * Class to manage the box to show last projet
 */
class box_funnel_of_prospection extends ModeleBoxes
{
	public $boxcode = "FunnelOfProspection";
	public $boximg = "object_projectpub";
	public $boxlabel = "BoxTitleFunnelOfProspection";
	public $depends = array("projet");

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
	public function __construct($db, $param = '')
	{
		global $user, $langs, $conf;

		// Load translation files required by the page
		$langs->loadLangs(array('boxes', 'projects'));

		$this->db = $db;

		$this->enabled = ($conf->global->MAIN_FEATURES_LEVEL >= 1 ? 1 : 0); // Not enabled by default, still need some work

		$this->hidden = empty($user->rights->projet->lire);
	}

	/**
	 *  Load data for box to show them later
	 *
	 *  @param   int		$max        Maximum number of records to load
	 *  @return  void
	 */
	public function loadBox($max = 5)
	{
		global $conf;

		// default values
		$badgeStatus0 = '#cbd3d3'; // draft
		$badgeStatus1 = '#bc9526'; // validated
		$badgeStatus1b = '#bc9526'; // validated
		$badgeStatus2 = '#9c9c26'; // approved
		$badgeStatus3 = '#bca52b';
		$badgeStatus4 = '#25a580'; // Color ok
		$badgeStatus4b = '#25a580'; // Color ok
		$badgeStatus5 = '#cad2d2';
		$badgeStatus6 = '#cad2d2';
		$badgeStatus7 = '#baa32b';
		$badgeStatus8 = '#993013';
		$badgeStatus9 = '#e7f0f0';
		if (file_exists(DOL_DOCUMENT_ROOT . '/theme/' . $conf->theme . '/theme_vars.inc.php')) {
			include DOL_DOCUMENT_ROOT . '/theme/' . $conf->theme . '/theme_vars.inc.php';
		}
		$listofoppstatus = array();
		$listofopplabel = array();
		$listofoppcode = array();
		$colorseriesstat = array();
		$sql = "SELECT cls.rowid, cls.code, cls.percent, cls.label";
		$sql .= " FROM " . MAIN_DB_PREFIX . "c_lead_status as cls";
		$sql .= " WHERE active=1";
		$sql .= " AND cls.code <> 'LOST'";
		$sql .= $this->db->order('cls.rowid', 'ASC');
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ($i < $num) {
				$objp = $this->db->fetch_object($resql);
				$listofoppstatus[$objp->rowid] = $objp->percent;
				$listofopplabel[$objp->rowid] = $objp->label;
				$listofoppcode[$objp->rowid] = $objp->code;
				switch ($objp->code) {
					case 'PROSP':
						$colorseriesstat[$objp->rowid] = "-" . $badgeStatus0;
						break;
					case 'QUAL':
						$colorseriesstat[$objp->rowid] = "-" . $badgeStatus1;
						break;
					case 'PROPO':
						$colorseriesstat[$objp->rowid] = $badgeStatus1;
						break;
					case 'NEGO':
						$colorseriesstat[$objp->rowid] = $badgeStatus4;
						break;
					case 'WON':
						$colorseriesstat[$objp->rowid] = $badgeStatus6;
						break;
					default:
						break;
				}
				$i++;
			}
		} else {
			dol_print_error($this->db);
		}

		global $conf, $user, $langs;
		$this->max = $max;

		$this->info_box_head = array(
			'text' => $langs->trans("Statistics") . ' - ' . $langs->trans("BoxTitleFunnelOfProspection"),
			'graph' => '1'
		);

		if ($user->rights->projet->lire || !empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
			$sql = "SELECT p.fk_opp_status as opp_status, cls.code, COUNT(p.rowid) as nb, SUM(p.opp_amount) as opp_amount, SUM(p.opp_amount * p.opp_percent) as ponderated_opp_amount";
			$sql .= " FROM " . MAIN_DB_PREFIX . "projet as p, " . MAIN_DB_PREFIX . "c_lead_status as cls";
			$sql .= " WHERE p.entity IN (" . getEntity('project') . ")";
			$sql .= " AND p.fk_opp_status = cls.rowid";
			$sql .= " AND p.fk_statut = 1"; // Opend projects only
			$sql .= " AND cls.code NOT IN ('LOST')";
			$sql .= " GROUP BY p.fk_opp_status, cls.code";
			$resql = $this->db->query($sql);

			$form = new Form($this->db);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				$i = 0;

				$totalnb = 0;
				$totaloppnb = 0;
				$totalamount = 0;
				$ponderated_opp_amount = 0;
				$valsnb = array();
				$valsamount = array();
				$dataseries = array();

				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					if ($obj) {
						$valsnb[$obj->opp_status] = $obj->nb;
						$valsamount[$obj->opp_status] = $obj->opp_amount;
						$totalnb += $obj->nb;
						if ($obj->opp_status) {
							$totaloppnb += $obj->nb;
						}
						if (!in_array($obj->code, array('WON', 'LOST'))) {
							$totalamount += $obj->opp_amount;
							$ponderated_opp_amount += $obj->ponderated_opp_amount;
						}
					}
					$i++;
				}
				$this->db->free($resql);
				$ponderated_opp_amount = $ponderated_opp_amount / 100;

				$stringtoprint = '';
				$stringtoprint .= '<div class="div-table-responsive-no-min ">';
				$listofstatus = array_keys($listofoppstatus);
				$liststatus = array();
				$data = array('');
				foreach ($listofstatus as $status) {
					$labelStatus = '';
					if ($status != 7) {
						$code = dol_getIdFromCode($this->db, $status, 'c_lead_status', 'rowid', 'code');
						if ($code) {
							$labelStatus = $langs->transnoentitiesnoconv("OppStatus" . $code);
						}
						if (empty($labelStatus)) {
							$labelStatus = $listofopplabel[$status];
						}

						$data[] = (isset($valsamount[$status]) ? (float) $valsamount[$status] : 0);
						$liststatus[] = $labelStatus;
						if (!$conf->use_javascript_ajax) {
							$stringtoprint .= '<tr class="oddeven">';
							$stringtoprint .= '<td>' . $labelStatus . '</td>';
							$stringtoprint .= '<td class="right"><a href="list.php?statut=' . $status . '">' . price((isset($valsamount[$status]) ? (float) $valsamount[$status] : 0), 0, '', 1, -1, -1, $conf->currency) . '</a></td>';
							$stringtoprint .= "</tr>\n";
						}
					}
				}
				$dataseries[] = $data;
				if ($conf->use_javascript_ajax) {
					include_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';
					$dolgraph = new DolGraph();
					$dolgraph->SetMinValue(0);
					$dolgraph->SetData($dataseries);
					$dolgraph->SetLegend($liststatus);
					$dolgraph->SetDataColor(array_values($colorseriesstat));
					$dolgraph->setShowLegend(2);
					$dolgraph->setShowPercent(1);
					$dolgraph->setTitle('');
					$dolgraph->SetType(array('horizontalbars'));
					$dolgraph->SetHeight('200');
					$dolgraph->SetWidth('600');
					$dolgraph->mode = 'depth';
					$dolgraph->draw('idgraphleadfunnel');
					$stringtoprint .= $dolgraph->show($totaloppnb ? 0 : 1);
				}
				$stringtoprint .= '</div>';

				$line = 0;
				$this->info_box_contents[$line][] = array(
					'tr' => 'class="nohover left "',
					'text' => ''
				);
				$this->info_box_contents[$line][] = array(
					'tr' => 'class="nohover left "',
					'text' => ''
				);
				$line++;
				$this->info_box_contents[$line][] = array(
					'tr' => '',
					'td' => 'class="center nopaddingleftimp nopaddingrightimp" colspan="2"',
					'text' => $stringtoprint
				);
				$line++;
				$this->info_box_contents[$line][] = array(
					'tr' => 'class="oddeven"',
					'td' => 'class="left "',
					'maxlength' => 500,
					'text' => $langs->trans("OpportunityTotalAmount") . ' (' . $langs->trans("WonLostExcluded") . ')'
				);
				$this->info_box_contents[$line][] = array(
					'tr' => 'class="oddeven"',
					'td' => 'class="right "',
					'maxlength' => 500,
					'text' => price($totalamount, 0, '', 1, -1, -1, $conf->currency)
				);
				$line++;
				$this->info_box_contents[$line][] = array(
					'tr' => 'class="oddeven"',
					'td' => 'class="left "',
					'maxlength' => 500,
					'text' => $form->textwithpicto($langs->trans("OpportunityPonderatedAmount") . ' (' . $langs->trans("WonLostExcluded") . ')', $langs->trans("OpportunityPonderatedAmountDesc"), 1)

				);
				$this->info_box_contents[$line][] = array(
					'td' => 'class="right "',
					'maxlength' => 500,
					'text' => price(price2num($ponderated_opp_amount, 'MT'), 0, '', 1, -1, -1, $conf->currency)
				);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => 'class="center opacitymedium"',
					'text' => $langs->trans("NoRecordedCustomers")
				);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => '',
				'text' => $langs->trans("ReadPermissionNotAllowed")
			);
		}
	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No $stringtoprint .=, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
