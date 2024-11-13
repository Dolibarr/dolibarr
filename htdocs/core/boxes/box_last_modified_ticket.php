<?php
/* Module descriptor for ticket system
 * Copyright (C) 2013-2016  Jean-François FERRY     <hello@librethic.io>
 *               2016       Christophe Battarel     <christophe@altairis.fr>
 * Copyright (C) 2019-2021  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *     \file        core/boxes/box_last_modified_ticket.php
 *     \ingroup     ticket
 *     \brief       This box shows latest modified tickets
 */
require_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";

/**
 * Class to manage the box to show last modified tickets
 */
class box_last_modified_ticket extends ModeleBoxes
{
	public $boxcode = "box_last_modified_ticket";
	public $boximg  = "ticket";
	public $boxlabel;
	public $depends = array("ticket");

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

		$this->boxlabel = $langs->transnoentitiesnoconv("BoxLastModifiedTicket");
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

		$text = $langs->trans("BoxLastModifiedTicketDescription", $max);
		$this->info_box_head = array(
			'text' => $text.'<a class="paddingleft" href="'.DOL_URL_ROOT.'/ticket/list.php?sortfield=t.tms&sortorder=DESC"><span class="badge">...</span></a>',
			'limit' => dol_strlen($text)
		);

		$this->info_box_contents[0][0] = array(
			'td' => 'class="left"',
			'text' => $langs->trans("BoxLastModifiedTicketContent"),
		);

		if ($user->hasRight('ticket', 'read')) {
			$sql = "SELECT t.rowid as id, t.ref, t.track_id, t.fk_soc, t.fk_user_create, t.fk_user_assign, t.subject, t.message, t.fk_statut as status, t.type_code, t.category_code, t.severity_code, t.datec, t.tms as datem, t.date_read, t.date_close, t.origin_email ";
			$sql .= ", type.label as type_label, category.label as category_label, severity.label as severity_label";
			$sql .= ", s.nom as company_name, s.email as socemail, s.client, s.fournisseur";
			$sql .= " FROM ".MAIN_DB_PREFIX."ticket as t";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_type as type ON type.code=t.type_code";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_category as category ON category.code=t.category_code";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_severity as severity ON severity.code=t.severity_code";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid=t.fk_soc";

			$sql .= " WHERE t.entity IN (".getEntity('ticket').')';
			//  		$sql.= " AND e.rowid = er.fk_event";
			//if (empty($user->rights->societe->client->voir) && !$user->socid) $sql.= " WHERE s.rowid = sc.fk_soc AND sc.fk_user = " .((int) $user->id);
			if ($user->socid) {
				$sql .= " AND t.fk_soc = ".((int) $user->socid);
			}

			$sql .= " ORDER BY t.tms DESC, t.rowid DESC";
			$sql .= $this->db->plimit($max, 0);

			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);

				$i = 0;

				while ($i < $num) {
					$objp = $this->db->fetch_object($resql);
					$datec = $this->db->jdate($objp->datec);
					$datem = $this->db->jdate($objp->datem);

					$ticket = new Ticket($this->db);
					$ticket->id = $objp->id;
					$ticket->track_id = $objp->track_id;
					$ticket->ref = $objp->ref;
					$ticket->subject = $objp->subject;
					$ticket->date_creation = $datec;
					$ticket->date_modification = $datem;
					//$ticket->fk_statut = $objp->status;
					//$ticket->fk_statut = $objp->status;
					$ticket->status = $objp->status;
					$ticket->statut = $objp->status;
					if ($objp->fk_soc > 0) {
						$thirdparty = new Societe($this->db);
						$thirdparty->id = $objp->fk_soc;
						$thirdparty->email = $objp->socemail;
						$thirdparty->client = $objp->client;
						$thirdparty->fournisseur = $objp->fournisseur;
						$thirdparty->name = $objp->company_name;
						$link = $thirdparty->getNomUrl(1);
					} else {
						$link = dol_print_email($objp->origin_email);
					}


					$r = 0;

					// Ticket
					$this->info_box_contents[$i][0] = array(
						'td' => 'class="nowraponall"',
						'text' => $ticket->getNomUrl(1),
						'asis' => 1,
					);
					$r++;

					// Subject
					$this->info_box_contents[$i][$r] = array(
						'td' => 'class="nowrap tdoverflowmax150"',
						'text' => $objp->subject, // Some event have no ref
						'url' => DOL_URL_ROOT."/ticket/card.php?track_id=".$objp->track_id,
					);
					$r++;

					// Customer
					$this->info_box_contents[$i][$r] = array(
						'td' => 'class="tdoverflowmax150"',
						'text' => $link,
						'asis' => 1,
					);
					$r++;

					// Date creation
					$this->info_box_contents[$i][$r] = array(
						'td' => 'class="center nowraponall" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($datem, 'dayhour', 'tzuserrel')).'"',
						'text' => dol_print_date($datem, 'dayhour', 'tzuserrel')
					);
					$r++;

					// Statut
					$this->info_box_contents[$i][$r] = array(
						'td' => 'class="right nowraponall"',
						'text' => $ticket->getLibStatut(3)
					);
					$r++;

					$i++;
				}

				if ($num == 0) {
					$this->info_box_contents[$i][0] = array(
						'td' => '',
						'text' => '<span class="opacitymedium">'.$langs->trans("BoxLastModifiedTicketNoRecordedTickets").'</span>'
					);
				}
			} else {
				dol_print_error($this->db);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => '',
				'text' => '<span class="opacitymedium">'.$langs->trans("ReadPermissionNotAllowed").'</span>',
			);
		}
	}



	/**
	 *	Method to show box.  Called when the box needs to be displayed.
	 *
	 *	@param	?array<array{text?:string,sublink?:string,subtext?:string,subpicto?:?string,picto?:string,nbcol?:int,limit?:int,subclass?:string,graph?:int<0,1>,target?:string}>   $head       Array with properties of box title
	 *	@param	?array<array{tr?:string,td?:string,target?:string,text?:string,text2?:string,textnoformat?:string,tooltip?:string,logo?:string,url?:string,maxlength?:int,asis?:int<0,1>}>   $contents   Array with properties of box lines
	 *	@param	int<0,1>	$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
