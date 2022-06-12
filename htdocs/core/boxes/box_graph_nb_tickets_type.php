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
 *     \file        htdocs/core/boxes/box_graph_nb_tickets_type.php
 *     \ingroup     ticket
 *     \brief       This box shows the number of tickets types
 */
require_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";

/**
 * Class to manage the box
 */
class box_graph_nb_tickets_type extends ModeleBoxes
{

	public $boxcode = "box_graph_nb_tickets_type";
	public $boximg = "ticket";
	public $boxlabel;
	public $depends = array("ticket");

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

		$this->boxlabel = $langs->transnoentitiesnoconv("BoxTicketType");
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
		global $theme_datacolor, $badgeStatus8;

		require_once DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php";
		require_once DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/theme_vars.inc.php";


		$badgeStatus8 = '#993013';

		$text = $langs->trans("BoxTicketType");
		$this->info_box_head = array(
			'text' => $text,
			'limit' => dol_strlen($text)
		);

		$listofopplabel = array();
		$listofoppcode = array();
		$colorseriesstat = array();
		if ($user->rights->ticket->read) {
			$sql = "SELECT ctt.rowid, ctt.label, ctt.code";
			$sql .= " FROM " . MAIN_DB_PREFIX . "c_ticket_type as ctt";
			$sql .= " WHERE ctt.active = 1";
			$sql .= $this->db->order('ctt.rowid', 'ASC');
			$resql = $this->db->query($sql);

			if ($resql) {
				$num = $this->db->num_rows($resql);
				$i = 0;
				$newcolorkey = 0;
				$colorused = array();
				while ($i < $num) {
					$objp = $this->db->fetch_object($resql);
					$listofoppcode[$objp->rowid] = $objp->code;
					$listofopplabel[$objp->rowid] = $objp->label;
					if (empty($colorused[$objp->code])) {
						if ($objp->code == 'ISSUE') {
							$colorused[$objp->code] = $badgeStatus8;
						} else {
							$colorused[$objp->code] = colorArrayToHex($theme_datacolor[$newcolorkey]);
							$newcolorkey++;
						}
					}
					$colorseriesstat[$objp->rowid] = $colorused[$objp->code];

					$i++;
				}
			} else {
				dol_print_error($this->db);
			}
			$dataseries = array();
			$data = array();
			$sql = "SELECT t.type_code, COUNT(t.type_code) as nb";
			$sql .= " FROM " . MAIN_DB_PREFIX . "ticket as t";
			$sql .= " WHERE t.fk_statut <> 8";
			$sql .= " GROUP BY t.type_code";
			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$objp = $this->db->fetch_object($resql);
					$data[$objp->type_code] = $objp->nb;
					$i++;
				}
				foreach ($listofoppcode as $rowid => $code) {
					$dataseries[] = array(
						'label' => $langs->getLabelFromKey($this->db, 'TicketTypeShort' . $code, 'c_ticket_type', 'code', 'label', $code),
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
					$px1->SetDataColor(array_values($colorseriesstat));
					$data = array();
					$legend = array();
					foreach ($dataseries as $value) {
						$data[] = array($value['label'], $value['data']);
						$totalnb += $value['data'];
					}
					$px1->SetData($data);
					$px1->setShowLegend(2);
					if (!empty($conf->dol_optimize_smallscreen)) {
						$px1->SetWidth(320);
					}
					$px1->SetType(array('pie'));
					$px1->SetLegend($legend);
					$px1->SetMaxValue($px1->GetCeilMaxValue());
					$px1->SetShading(3);
					$px1->SetHorizTickIncrement(1);
					$px1->SetCssPrefix("cssboxes");
					$px1->mode = 'depth';
					$px1->draw('idgraphtickettype');
					$stringtoprint .= $px1->show($totalnb ? 0 : 1);
				}
				$stringtoprint .= '</div>';
				$this->info_box_contents[][]=array(
					'td' => 'class="center"',
					'text' => $stringtoprint
				);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => 'class="center opacitymedium"',
					'text' => $langs->trans("BoxNoTicketSeverity"),
				);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="left"',
				'text' => $langs->trans("ReadPermissionNotAllowed"),
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
