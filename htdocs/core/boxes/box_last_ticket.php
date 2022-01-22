<?php
/* Module descriptor for ticket system
 * Copyright (C) 2013-2016  Jean-François FERRY <hello@librethic.io>
 * Copyright (C) 2016       Christophe Battarel <christophe@altairis.fr>
 * Copyright (C) 2018-2019  Frédéric France     <frederic.france@netlogic.fr>
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
 *     \file        htdocs/core/boxes/box_last_ticket.php
 *     \ingroup     ticket
 *     \brief       This box shows latest created tickets
 */
require_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";

/**
 * Class to manage the box
 */
class box_last_ticket extends ModeleBoxes
{

	public $boxcode = "box_last_ticket";
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

		$this->boxlabel = $langs->transnoentitiesnoconv("BoxLastTicket");
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

		$this->max = $max;

		require_once DOL_DOCUMENT_ROOT."/ticket/class/ticket.class.php";

		$text = $langs->trans("BoxLastTicketDescription", $max);
		$this->info_box_head = array(
			'text' => $text,
			'limit' => dol_strlen($text),
		);

		$this->info_box_contents[0][0] = array(
			'td' => 'class="left"',
			'text' => $langs->trans("BoxLastTicketContent"),
		);

		if ($user->rights->ticket->read) {
			$sql = "SELECT t.rowid as id, t.ref, t.track_id, t.fk_soc, t.fk_user_create, t.fk_user_assign, t.subject, t.message, t.fk_statut, t.type_code, t.category_code, t.severity_code, t.datec, t.date_read, t.date_close, t.origin_email ";
			$sql .= ", type.label as type_label, category.label as category_label, severity.label as severity_label";
			$sql .= ", s.nom as company_name, s.email as socemail, s.client, s.fournisseur";
			$sql .= " FROM ".MAIN_DB_PREFIX."ticket as t";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_type as type ON type.code=t.type_code";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_category as category ON category.code=t.category_code";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_severity as severity ON severity.code=t.severity_code";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid=t.fk_soc";

			$sql .= " WHERE t.entity = ".$conf->entity;
			//          $sql.= " AND e.rowid = er.fk_event";
			//if (!$user->rights->societe->client->voir && !$user->socid) $sql.= " WHERE s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
			if ($user->socid) {
				$sql .= " AND t.fk_soc= ".$user->socid;
			}

			//$sql.= " AND t.fk_statut > 9";

			$sql .= " ORDER BY t.datec DESC, t.rowid DESC ";
			$sql .= $this->db->plimit($max, 0);

			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);

				$i = 0;

				while ($i < $num) {
					$objp = $this->db->fetch_object($resql);
					$datec = $this->db->jdate($objp->datec);
					$dateterm = $this->db->jdate($objp->fin_validite);
					$dateclose = $this->db->jdate($objp->date_cloture);
					$late = '';

					$ticket = new Ticket($this->db);
					$ticket->id = $objp->id;
					$ticket->track_id = $objp->track_id;
					$ticket->ref = $objp->ref;
					$ticket->fk_statut = $objp->fk_statut;
					$ticket->subject = $objp->subject;
					if ($objp->fk_soc > 0) {
						$thirdparty = new Societe($this->db);
						$thirdparty->id = $objp->fk_soc;
						$thirdparty->email = $objp->socemail;
						$thirdparty->client = $objp->client;
						$thirdparty->fournisseur = $objp->fournisseur;
						$thirdparty->name = $objp->company_name;
						$link = $thirdparty->getNomUrl(1);
					} else {
						$link = '<span title="'.$objp->origin_email.'">'.dol_print_email($objp->origin_email).'</span>';
					}

					$r = 0;

					// Ticket
					$this->info_box_contents[$i][$r] = array(
						'td' => 'class="nowraponall"',
						'text' => $ticket->getNomUrl(1),
						'asis' => 1
					);
					$r++;

					// Subject
					$this->info_box_contents[$i][$r] = array(
						'td' => 'class="tdoverflowmax200"',
						'text' => '<span title="'.$objp->subject.'">'.$objp->subject.'</span>', // Some event have no ref
						'url' => DOL_URL_ROOT."/ticket/card.php?track_id=".$objp->track_id,
					);
					$r++;

					// Customer
					$this->info_box_contents[$i][$r] = array(
						'td' => 'class="tdoverflowmax100"',
						'text' => $link,
						'asis' => 1,
					);
					$r++;

					// Date creation
					$this->info_box_contents[$i][$r] = array(
						'td' => 'class="right"',
						'text' => dol_print_date($datec, 'dayhour'),
					);
					$r++;

					// Statut
					$this->info_box_contents[$i][$r] = array(
						'td' => 'class="right nowraponall"',
						'text' => $ticket->getLibStatut(3),
					);
					$r++;

					$i++;
				}

				if ($num == 0) {
					$this->info_box_contents[$i][0] = array('td' => 'class="center"', 'text' => $langs->trans("BoxLastTicketNoRecordedTickets"));
				}
			} else {
				dol_print_error($this->db);
			}
		} else {
			$this->info_box_contents[0][0] = array('td' => 'class="left"',
				'text' => $langs->trans("ReadPermissionNotAllowed"));
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
