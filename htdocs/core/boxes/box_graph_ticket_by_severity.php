<?php
/* Module descriptor for ticket system
 * Copyright (C) 2013-2016  Jean-François FERRY     <hello@librethic.io>
 *               2016       Christophe Battarel     <christophe@altairis.fr>
 * Copyright (C) 2019-2021  Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *     \file        htdocs/core/boxes/box_graph_ticket_by_severity.php
 *     \ingroup     ticket
 *     \brief       This box shows open tickets by severity
 */
require_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";

/**
 * Class to manage the box
 */
class box_graph_ticket_by_severity extends ModeleBoxes
{
	public $boxcode = "box_ticket_by_severity";
	public $boximg = "ticket";
	public $boxlabel;
	public $depends = array("ticket");

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	public $param;
	public $info_box_head = array();
	public $info_box_contents = array();

	public $widgettype = 'graph';


	/**
	 * Constructor
	 *  @param  DoliDB  $db         Database handler
	 *  @param  string  $param      More parameters
	 */
	public function __construct($db, $param = '')
	{
		global $langs;
		$langs->load("boxes");
		$this->db = $db;

		$this->boxlabel = $langs->transnoentitiesnoconv("BoxTicketSeverity");
	}

	/**
	 * Load data into info_box_contents array to show array later.
	 *
	 *     @param  int $max Maximum number of records to load
	 *     @return void
	 */
	public function loadBox($max = 5)
	{
		global $conf, $user, $langs;

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
		$this->max = $max;

		require_once DOL_DOCUMENT_ROOT."/ticket/class/ticket.class.php";

		$text = $langs->trans("BoxTicketSeverity", $max);
		$this->info_box_head = array(
			'text' => $text,
			'limit' => dol_strlen($text)
		);

		$listofopplabel = array();
		$listofoppcode = array();
		$colorseriesstat = array();
		if ($user->rights->ticket->read) {
			$sql = "SELECT cts.rowid, cts.label, cts.code";
			$sql .= " FROM " . MAIN_DB_PREFIX . "c_ticket_severity as cts";
			$sql .= " WHERE cts.active = 1";
			$sql .= $this->db->order('cts.rowid', 'ASC');
			$resql = $this->db->query($sql);

			if ($resql) {
				$num = $this->db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$objp = $this->db->fetch_object($resql);
					$listofoppcode[$objp->rowid] = $objp->code;
					$listofopplabel[$objp->rowid] = $objp->label;
					switch ($objp->code) {
						case 'LOW':
							$colorseriesstat[$objp->rowid] = $badgeStatus4;
							break;
						case 'NORMAL':
							$colorseriesstat[$objp->rowid] = $badgeStatus2;
							break;
						case 'HIGH':
							$colorseriesstat[$objp->rowid] = $badgeStatus1;
							break;
						case 'BLOCKING':
							$colorseriesstat[$objp->rowid] = $badgeStatus8;
							break;
						default:
							break;
					}
					$i++;
				}
			} else {
				dol_print_error($this->db);
			}

			$dataseries = array();
			$data = array();
			$sql = "SELECT t.severity_code, COUNT(t.severity_code) as nb";
			$sql .= " FROM " . MAIN_DB_PREFIX . "ticket as t";
			$sql .= " WHERE t.fk_statut <> 8";
			$sql .= " GROUP BY t.severity_code";
			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$objp = $this->db->fetch_object($resql);
					$data[$objp->severity_code] = $objp->nb;
					$i++;
				}
				foreach ($listofoppcode as $rowid => $code) {
					$dataseries[] = array(
						'label' => $langs->getLabelFromKey($this->db, 'TicketSeverityShort' . $code, 'c_ticket_severity', 'code', 'label', $code),
						'data' => (empty($data[$code]) ? 0 : $data[$code])
					);
				}
			} else {
				dol_print_error($this->db);
			}
			$stringtoprint = '';
			$stringtoprint .= '<div class="div-table-responsive-no-min ">';
			if (!empty($dataseries) && count($dataseries) > 0) {
				include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
				$px1 = new DolGraph();
				$mesg = $px1->isGraphKo();
				$totalnb = 0;
				if (!$mesg) {
					//$px1->SetDataColor(array_values($colorseriesstat));
					$data = array();
					$legend = array();
					foreach ($dataseries as $value) {
						$data[] = array($value['label'], $value['data']);
						$totalnb += $value['data'];
					}

					$px1->SetData($data);
					$px1->setShowLegend(0);
					$px1->SetType(array('bars'));
					$px1->SetLegend($legend);
					$px1->SetMaxValue($px1->GetCeilMaxValue());
					//$px1->SetHeight($HEIGHT);
					$px1->SetShading(3);
					$px1->SetHorizTickIncrement(1);
					$px1->SetCssPrefix("cssboxes");
					$px1->mode = 'depth';

					$px1->draw('idgraphticketseverity');
					$stringtoprint .= $px1->show($totalnb ? 0 : 1);
				}
				$stringtoprint .= '</div>';
				$this->info_box_contents[][]=array(
					'td' => 'class="center"',
					'text' => $stringtoprint
				);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => '',
					'text' => '<span class="opacitymedium">'.$langs->trans("BoxNoTicketSeverity").'</span>'
				);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => '',
				'text' => '<span class="opacitymedium">'.$langs->trans("ReadPermissionNotAllowed").'</span>',
			);
		}
	}

	/**
	 *     Method to show box
	 *
	 *     @param  array $head     Array with properties of box title
	 *     @param  array $contents Array with properties of box lines
	 *     @param  int   $nooutput No print, only return string
	 *     @return string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
