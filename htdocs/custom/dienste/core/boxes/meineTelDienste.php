<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
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
 *    \file       htdocs/core/boxes/box_clients.php
 *    \ingroup    societes
 *    \brief      Module de generation de l'affichage de la box clients
 */

include_once DOL_DOCUMENT_ROOT . '/core/boxes/modules_boxes.php';
include_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
include_once DOL_DOCUMENT_ROOT . '/custom/dienste/class/telefon.class.php';
include_once DOL_DOCUMENT_ROOT . '/core/modules/modUser.class.php';

/**
 * Class to manage the box to show last thirdparties
 */
class meineTelDienste extends ModeleBoxes
{


	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	public $enabled = 1;

	public $info_box_head = array();
	public $info_box_contents = array();
	public $boxcode = "meineTelDienste";
	public $boxlabel = 'meineTelDienste';

	/**
	 *  Constructor
	 *
	 * @param DoliDB $db Database handler
	 * @param string $param More parameters
	 */
	public function __construct($db, $param = '')
	{
		global $conf, $user;

		$this->db = $db;

	}

	/**
	 *  Load data for box to show them later
	 *
	 * @param int $max Maximum number of records to load
	 * @return    void
	 */
	public function loadBox($max = 5)
	{
		global $conf, $user, $langs, $db;

		$sql = "select d.ref, d.user, d.description, u.rowid, u.firstname from " . MAIN_DB_PREFIX . "dienste_telefon as d ";
		$sql .= "join " . MAIN_DB_PREFIX . "user as u on d.user=u.rowid ";
		$sql .= "where d.user='" . $user->id . "' ";
		$sql .= "order by d.ref asc";

		$res = $db->query($sql);

		$textHead = $langs->trans("Meine kommenden Telefondienste");
		$this->info_box_head = array(
			'text' => $textHead,
			'limit' => dol_strlen($textHead),
		);
		$line = 0;

		for ($i = 0; $i < $res->num_rows; $i++) {
			$obj = $db->fetch_object($res);
			$usr = new User($db);
			$usr->fetch($obj->rowid);
			$tel = new Telefon($db);
			$tel->fetch('', $obj->ref);

			if($tel->ref > time()) {
				$this->info_box_contents[$line][0] = array(
					'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
					'text' => $tel->getNomUrl(null, null, null, null, null, dol_print_date($obj->ref, 'day')),
					'asis' => 1,
				);
				$this->info_box_contents[$line][1] = array(
					'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
					'text' => $usr->getNomUrl(),
					'asis' => 1
				);
				$this->info_box_contents[$line][2] = array(
					'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
					'text' => $obj->description,
					'asis' => 1
				);

				$line++;
			}
		}
	}


	/**
	 *    Method to show box
	 *
	 * @param array $head Array with properties of box title
	 * @param array $contents Array with properties of box lines
	 * @param int $nooutput No print, only return string
	 * @return    string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
