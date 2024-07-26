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
 *     \file        htdocs/core/boxes/box_graph_nb_ticket_last_x_days.php
 *     \ingroup     ticket
 *     \brief       This box shows the number of new daily tickets the last X days
 */
require_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";

/**
 * Class to manage the box to show new daily tickets
 */
class box_graph_nb_ticket_last_x_days extends ModeleBoxes
{
	public $boxcode = "box_graph_nb_ticket_last_x_days";
	public $boximg  = "ticket";
	public $boxlabel;
	public $depends = array("ticket");

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

		$this->boxlabel = $langs->transnoentitiesnoconv("BoxNumberOfTicketByDay");
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
		$dataseries = array();
		$graphtoshow = "";

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
		if (file_exists(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php')) {
			include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';
		}
		$this->max = $max;


		$param_day = 'DOLUSERCOOKIE_ticket_last_days';
		if (GETPOST($param_day)) {
			if (GETPOST($param_day) >= 15) {
				$days = 14;
			} else {
				$days = GETPOST($param_day);
			}
		} else {
			$days = 7;
		}
		require_once DOL_DOCUMENT_ROOT."/ticket/class/ticket.class.php";
		$text = $langs->trans("BoxTicketLastXDays", $days).'&nbsp;'.img_picto('', 'filter.png', 'id="idsubimgDOLUSERCOOKIE_ticket_last_days" class="linkobject"');
		$this->info_box_head = array(
			'text' => $text,
			'limit' => dol_strlen($text)
		);
		$today = dol_now();
		$intervaltoadd = 1;
		$minimumdatec = dol_time_plus_duree($today, -1 * ($days - 1), 'd');
		$minimumdatecformated = dol_print_date($minimumdatec, 'dayrfc');

		if ($user->hasRight('ticket', 'read')) {
			$sql = "SELECT CAST(t.datec AS DATE) as datec, COUNT(t.datec) as nb";
			$sql .= " FROM ".MAIN_DB_PREFIX."ticket as t";
			$sql .= " WHERE CAST(t.datec AS DATE) > '".$this->db->idate($minimumdatec)."'";
			$sql .= " GROUP BY CAST(t.datec AS DATE)";

			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$objp = $this->db->fetch_object($resql);
					while ($minimumdatecformated < $objp->datec) {
						$dataseries[] = array('label' => dol_print_date($minimumdatec, 'day'), 'data' => 0);
						$minimumdatec = dol_time_plus_duree($minimumdatec, $intervaltoadd, 'd');
						$minimumdatecformated = dol_print_date($minimumdatec, 'dayrfc');
					}
					$dataseries[] = array('label' => dol_print_date($this->db->jdate($objp->datec), 'day'), 'data' => $objp->nb);
					$minimumdatec = dol_time_plus_duree($minimumdatec, $intervaltoadd, 'd');
					$minimumdatecformated = dol_print_date($minimumdatec, 'dayrfc');
					$i++;
				}
				while (count($dataseries) < $days) {
					$dataseries[] = array('label' => dol_print_date($minimumdatec, 'day'), 'data' => 0);
					$minimumdatec = dol_time_plus_duree($minimumdatec, $intervaltoadd, 'd');
					$minimumdatecformated = dol_print_date($minimumdatec, 'dayrfc');
					$i++;
				}
			} else {
				dol_print_error($this->db);
			}
			$stringtoshow = '<div class="div-table-responsive-no-min">';
			$stringtoshow .= '<script nonce="'.getNonce().'" type="text/javascript">
				jQuery(document).ready(function() {
					jQuery("#idsubimgDOLUSERCOOKIE_ticket_last_days").click(function() {
						jQuery("#idfilterDOLUSERCOOKIE_ticket_last_days").toggle();
					});
				});
				</script>';
			$stringtoshow .= '<div class="center hideobject" id="idfilterDOLUSERCOOKIE_ticket_last_days">'; // hideobject is to start hidden
			$stringtoshow .= '<form class="flat formboxfilter" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
			$stringtoshow .= '<input type="hidden" name="token" value="'.newToken().'">';
			$stringtoshow .= '<input type="hidden" name="action" value="refresh">';
			$stringtoshow .= '<input type="hidden" name="DOL_AUTOSET_COOKIE" value="DOLUSERCOOKIE_ticket_last_days:days">';
			$stringtoshow .= ' <input class="flat" size="4" type="text" name="'.$param_day.'" value="'.$days.'">'.$langs->trans("Days");
			$stringtoshow .= '<input type="image" alt="'.$langs->trans("Refresh").'" src="'.img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1).'">';
			$stringtoshow .= '</form>';
			$stringtoshow .= '</div>';

			include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
			$px1 = new DolGraph();

			$mesg = $px1->isGraphKo();
			$totalnb = 0;
			if (!$mesg) {
				$data = array();
				foreach ($dataseries as $value) {
					$data[] = array($value['label'], $value['data']);
					$totalnb += $value['data'];
				}
				$px1->SetData($data);
				//$px1->setShowLegend(2);
				$px1->setShowLegend(0);
				$px1->SetType(array('bars'));
				$px1->SetLegend(array($langs->trans('BoxNumberOfTicketByDay')));
				$px1->SetMaxValue($px1->GetCeilMaxValue());
				$px1->SetHeight(192);
				$px1->SetShading(3);
				$px1->SetHorizTickIncrement(1);
				$px1->SetCssPrefix("cssboxes");
				$px1->mode = 'depth';

				$px1->draw('idgraphticketlastxdays');
				$graphtoshow = $px1->show($totalnb ? 0 : 1);
			}
			if ($totalnb) {
				$stringtoshow .= $graphtoshow;
			}
			$stringtoshow .= '</div>';
			if ($totalnb) {
				$this->info_box_contents[][] = array(
					'td' => 'center',
					'text' => $stringtoshow
				);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => 'class="center"',
					'text' => '<span class="opacitymedium">'.$stringtoshow.$langs->trans("BoxNoTicketLastXDays", $days).'</span>'
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
