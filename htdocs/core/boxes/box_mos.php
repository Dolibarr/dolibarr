<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *		\file       htdocs/core/boxes/box_mos.php
 *		\ingroup    mrp
 *		\brief      Widget for latest modified MOs
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';


/**
 * Class to manage the box to show last manufacturing orders (MO)
 */
class box_mos extends ModeleBoxes
{
	public $boxcode  = "lastmos";
	public $boximg   = "object_mrp";
	public $boxlabel = "BoxTitleLatestModifiedMos";
	public $depends  = array("mrp");

	/**
	 *  Constructor
	 *
	 *  @param  DoliDB  $db         Database handler
	 *  @param  string  $param      More parameters
	 */
	public function __construct($db, $param)
	{
		global $user;

		$this->db = $db;

		$this->hidden = !$user->hasRight('bom', 'read');
	}

	/**
	 *  Load data for box to show them later
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
	 */
	public function loadBox($max = 5)
	{
		global $user, $langs, $conf;

		$this->max = $max;

		include_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';
		include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

		$mostatic = new Mo($this->db);
		$productstatic = new Product($this->db);
		$userstatic = new User($this->db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitleLatestModifiedMos", $max));

		if ($user->hasRight('mrp', 'read')) {
			$sql = "SELECT p.ref as product_ref";
			$sql .= ", p.rowid as productid";
			$sql .= ", p.tosell";
			$sql .= ", p.tobuy";
			$sql .= ", p.tobatch";
			$sql .= ", c.rowid";
			$sql .= ", c.date_creation";
			$sql .= ", c.tms";
			$sql .= ", c.ref";
			$sql .= ", c.status";
			$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
			$sql .= ", ".MAIN_DB_PREFIX."mrp_mo as c";
			$sql .= " WHERE c.fk_product = p.rowid";
			$sql .= " AND c.entity = ".$conf->entity;
			$sql .= " ORDER BY c.tms DESC, c.ref DESC";
			$sql .= $this->db->plimit($max, 0);

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);

				$line = 0;

				while ($line < $num) {
					$objp = $this->db->fetch_object($result);
					$datem = $this->db->jdate($objp->tms);
					$mostatic->id = $objp->rowid;
					$mostatic->ref = $objp->ref;
					$mostatic->status = $objp->status;
					$productstatic->id = $objp->productid;
					$productstatic->ref = $objp->product_ref;
					$productstatic->status = $objp->tosell;
					$productstatic->status_buy = $objp->tobuy;
					$productstatic->status_batch = $objp->tobatch;

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall"',
						'text' => $mostatic->getNomUrl(1),
						'asis' => 1,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="tdoverflowmax150 maxwidth150onsmartphone"',
						'text' => $productstatic->getNomUrl(1),
						'asis' => 1,
					);

					if (getDolGlobalString('MRP_BOX_LAST_MOS_SHOW_VALIDATE_USER')) {
						if ($objp->fk_user_valid > 0) {
							$userstatic->fetch($objp->fk_user_valid);
						}
						$this->info_box_contents[$line][] = array(
							'td' => 'class="right"',
							'text' => (($objp->fk_user_valid > 0) ? $userstatic->getNomUrl(1) : ''),
							'asis' => 1,
						);
					}

					$this->info_box_contents[$line][] = array(
						'td' => 'class="center nowraponall" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($datem, 'dayhour', 'tzuserrel')).'"',
						'text' => dol_print_date($datem, 'day', 'tzuserrel'),
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="right" width="18"',
						'text' => $mostatic->LibStatut($objp->status, 3),
					);

					$line++;
				}

				if ($num == 0) {
					$this->info_box_contents[$line][0] = array(
					'td' => 'class="center"',
					'text'=> '<span class="opacitymedium">'.$langs->trans("NoRecordedOrders").'</span>'
					);
				}

				$this->db->free($result);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => '',
					'maxlength'=>500,
					'text' => ($this->db->error().' sql='.$sql),
				);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="nohover left"',
				'text' => '<span class="opacitymedium">'.$langs->trans("ReadPermissionNotAllowed").'</span>'
			);
		}
	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
