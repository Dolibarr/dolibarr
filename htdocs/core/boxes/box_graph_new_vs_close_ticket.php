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
 *     \file        htdocs/core/boxes/box_graph_new_vs_close_ticket.php
 *     \ingroup     ticket
 *     \brief       This box shows the number of new daily tickets the last X days
 */
require_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";

/**
 * Class to manage the box
 */
class box_graph_new_vs_close_ticket extends ModeleBoxes
{
	public $boxcode = "box_nb_tickets_type";
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

		$this->boxlabel = $langs->transnoentitiesnoconv("BoxNewTicketVSClose");
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
		$text = $langs->trans("BoxNewTicketVSClose");
		$this->info_box_head = array(
			'text' => $text,
			'limit' => dol_strlen($text)
		);

		if ($user->hasRight('ticket', 'read')) {
			$data = array();
			$totalnb = 0;
			$sql = "SELECT COUNT(t.datec) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."ticket as t";
			$sql .= " WHERE CAST(t.datec AS DATE) = CURRENT_DATE";
			$sql .= " AND t.fk_statut <> 8";
			$sql .= " GROUP BY CAST(t.datec AS DATE)";
			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				if ($num > 0) {
					$objp = $this->db->fetch_object($resql);
					$data[] = array($langs->transnoentitiesnoconv('TicketCreatedToday'), $objp->nb);
					$totalnb += $objp->nb;
				} else {
					$data[] = array($langs->transnoentitiesnoconv('TicketCreatedToday'), 0);
				}
			} else {
				dol_print_error($this->db);
			}
			$sql = "SELECT COUNT(t.date_close) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."ticket as t";
			$sql .= " WHERE CAST(t.date_close AS DATE) = CURRENT_DATE";
			$sql .= " AND t.fk_statut = 8";
			$sql .= " GROUP BY CAST(t.date_close AS DATE)";
			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				if ($num > 0) {
					$objp = $this->db->fetch_object($resql);
					$data[] = array($langs->transnoentitiesnoconv('TicketClosedToday'), $objp->nb);
					$totalnb += $objp->nb;
				} else {
					$data[] = array($langs->transnoentitiesnoconv('TicketClosedToday'), 0);
				}
			} else {
				dol_print_error($this->db);
			}
			$colorseries = array();
			$colorseries[] = $badgeStatus8;
			$colorseries[] = $badgeStatus2;
			$stringtoprint = '';
			$stringtoprint .= '<div class="div-table-responsive-no-min ">';
			if (!empty($data) && count($data) > 0) {
				include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
				$px1 = new DolGraph();
				$mesg = $px1->isGraphKo();
				if (!$mesg) {
					$px1->SetDataColor(array_values($colorseries));
					$px1->SetData($data);
					$px1->setShowLegend(2);
					if (!empty($conf->dol_optimize_smallscreen)) {
						$px1->SetWidth(320);
					}
					$px1->SetType(array('pie'));
					$px1->SetMaxValue($px1->GetCeilMaxValue());
					$px1->SetShading(3);
					$px1->SetHorizTickIncrement(1);
					$px1->SetCssPrefix("cssboxes");
					$px1->mode = 'depth';

					$px1->draw('idgraphticketnewvsclosetoday');
					$stringtoprint .= $px1->show($totalnb ? 0 : 1);
				}
				$stringtoprint .= '</div>';
				$this->info_box_contents[][] = array(
					'td' => 'class="center"',
					'text' => $stringtoprint
				);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => 'class="center"',
					'text' => '<span class="opacitymedium">'.$langs->trans("BoxNoTicketSeverity").'</span>'
				);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="left"',
				'text' => '<span class="opacitymedium">'.$langs->trans("ReadPermissionNotAllowed").'</span>'
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
