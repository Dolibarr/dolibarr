<?php
/*
 * Copyright (C) 2013-2016  Jean-François FERRY <hello@librethic.io>
 * Copyright (C) 2016       Christophe Battarel <christophe@altairis.fr>
 * Copyright (C) 2018-2023  Frédéric France     <frederic.france@netlogic.fr>
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
 *     \file        htdocs/core/boxes/box_last_modified_knowledgerecord.php
 *     \ingroup     knowledgerecord
 *     \brief       This box shows latest created knowledgerecords
 */
require_once DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php";

/**
 * Class to manage the box
 */
class box_last_modified_knowledgerecord extends ModeleBoxes
{
	/**
	 * @var string boxcode
	 */
	public $boxcode = "box_last_modified_knowledgerecord";

	/**
	 * @var string box img
	 */
	public $boximg = "knowledgemanagement";

	/**
	 * @var string boc label
	 */
	public $boxlabel;

	/**
	 * @var array box dependancies
	 */
	public $depends = array("knowledgemanagement");

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string param
	 */
	public $param;

	/**
	 * @var array box info heads
	 */
	public $info_box_head = array();

	/**
	 * @var array box info content
	 */
	public $info_box_contents = array();

	/**
	 * Constructor
	 *  @param  DoliDB  $db         Database handler
	 *  @param  string  $param      More parameters
	 */
	public function __construct($db, $param = '')
	{
		global $langs;
		$langs->loadLangs(array("boxes", "knowledgemanagement", "languages"));
		$this->db = $db;

		$this->boxlabel = $langs->transnoentitiesnoconv("BoxLastModifiedKnowledgerecord");
	}

	/**
	 * Load data into info_box_contents array to show array later.
	 *
	 *     @param  int $max Maximum number of records to load
	 *     @return void
	 */
	public function loadBox($max = 5)
	{
		global $user, $langs;

		$this->max = $max;

		require_once DOL_DOCUMENT_ROOT."/knowledgemanagement/class/knowledgerecord.class.php";

		$text = $langs->trans("BoxLastModifiedKnowledgerecordDescription", $max);
		$this->info_box_head = array(
			'text' => $text,
			'limit' => dol_strlen($text),
		);

		$this->info_box_contents[0][0] = array(
			'td' => 'class="left"',
			'text' => $langs->trans("BoxLastKnowledgerecordContent"),
		);

		if ($user->hasRight('knowledgemanagement', 'knowledgerecord', 'read')) {
			$sql = 'SELECT k.rowid as id, k.date_creation, k.ref, k.lang, k.question, k.status as status';
			$sql .= " FROM ".MAIN_DB_PREFIX."knowledgemanagement_knowledgerecord as k";
			$sql .= " WHERE k.entity IN (".getEntity('knowledgemanagement').")";

			if ($user->socid) {
				$sql .= " AND k.fk_soc= ".((int) $user->socid);
			}

			$sql.= " AND k.status > 0";

			$sql .= " ORDER BY k.tms DESC, k.rowid DESC ";
			$sql .= $this->db->plimit($max, 0);

			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);

				$i = 0;

				while ($i < $num) {
					$objp = $this->db->fetch_object($resql);

					$datec = $this->db->jdate($objp->date_creation);

					$knowledgerecord = new KnowledgeRecord($this->db);
					$knowledgerecord->id = $objp->id;
					$knowledgerecord->date_creation = $objp->date_creation;
					$knowledgerecord->ref = $objp->ref;
					$knowledgerecord->status = $objp->status;
					$knowledgerecord->question = $objp->question;

					$r = 0;

					// Ticket
					$this->info_box_contents[$i][$r] = array(
						'td' => 'class="nowraponall"',
						'text' => $knowledgerecord->getNomUrl(1),
						'asis' => 1
					);
					$r++;

					// Question
					$this->info_box_contents[$i][$r] = array(
						'td' => 'class="tdoverflowmax200"',
						'text' => '<span title="'.dol_escape_htmltag($objp->question).'">'.dol_escape_htmltag($objp->question).'</span>',
						'url' => DOL_URL_ROOT."/knowledgemanagement/knowledgerecord_card.php?id=".urlencode($objp->id),
					);
					$r++;

					// Language
					$labellang = ($objp->lang ? $langs->trans('Language_'.$objp->lang) : '');
					$this->info_box_contents[$i][$r] = array(
						'td' => 'class="tdoverflowmax100"',
						'text' => picto_from_langcode($objp->lang, 'class="paddingrightonly saturatemedium opacitylow"') . $labellang,
						'asis' => 1,
					);
					$r++;

					// Date creation
					$this->info_box_contents[$i][$r] = array(
						'td' => 'class="center nowraponall" title="'.dol_escape_htmltag($langs->trans("DateCreation").': '.dol_print_date($datec, 'dayhour', 'tzuserrel')).'"',
						'text' => dol_print_date($datec, 'dayhour', 'tzuserrel'),
					);
					$r++;

					// Statut
					$this->info_box_contents[$i][$r] = array(
						'td' => 'class="right nowraponall"',
						'text' => $knowledgerecord->getLibStatut(3),
					);
					$r++;

					$i++;
				}

				if ($num == 0) {
					$this->info_box_contents[$i][0] = array(
						'td' => '',
						'text' => '<span class="opacitymedium">'.$langs->trans("BoxLastTicketNoRecordedTickets").'</span>',
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
