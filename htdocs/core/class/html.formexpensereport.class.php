<?php
/* Copyright (C) 2012-2013  Charles-Fr BENKE		<charles.fr@benke.fr>
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
 * or see https://www.gnu.org/
 */

/**
 * \file       htdocs/core/class/html.formexpensereport.class.php
 * \ingroup    core
 * \brief      File of class with all html predefined components
 */

/**
 *	Class to manage generation of HTML components for contract module
 */
class FormExpenseReport
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';


	/**
	 * Constructor
	 *
	 * @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *    Return the combobox for the different statuses of an expense report
	 *    The list values are the ids from the labelStatus.
	 *
	 *    @param    int     $selected       preselect status
	 *    @param    string  $htmlname       Name of HTML select
	 *    @param    int     $useempty       1=Add empty line
	 *    @param    int     $useshortlabel  Use short labels
	 *    @param	string	$morecss		More CSS
	 *    @return   string                  HTML select with status
	 */
	public function selectExpensereportStatus($selected = 0, $htmlname = 'fk_statut', $useempty = 1, $useshortlabel = 0, $morecss = '')
	{
		global $langs;

		$tmpep = new ExpenseReport($this->db);

		$html = '<select class="flat'.($morecss ? ' '.$morecss : '').'" id="'.$htmlname.'" name="'.$htmlname.'">';
		if ($useempty) {
			$html.='<option value="-1">&nbsp;</option>';
		}
		$arrayoflabels = $tmpep->labelStatus;
		if ($useshortlabel) {
			$arrayoflabels = $tmpep->labelStatusShort;
		}
		foreach ($arrayoflabels as $key => $val) {
			if (!empty($selected) && $selected == $key) {
				$html .= '<option value="'.$key.'" selected>';
			} else {
				$html .=  '<option value="'.$key.'">';
			}
			$html .= $langs->trans($val);
			$html .= '</option>';
		}
		$html .= '</select>'."\n";

		$html .= ajax_combobox($htmlname, array(), 0, 0, 'resolve', ($useempty < 0 ? (string) $useempty : '-1'), $morecss);

		return $html;
	}

	/**
	 *  Return list of types of notes with select value = id
	 *
	 *  @param      int     $selected       Preselected type
	 *  @param      string  $htmlname       Name of field in form
	 *  @param      int     $showempty      Add an empty field
	 *  @param      int     $active         1=Active only, 0=Unactive only, -1=All
	 *  @return     string                  Select html
	 */
	public function selectTypeExpenseReport($selected = 0, $htmlname = 'type', $showempty = 0, $active = 1)
	{
		// phpcs:enable
		global $langs, $user;
		$langs->load("trips");

		$out = '';

		$out .= '<select class="flat" name="'.$htmlname.'" id="'.$htmlname.'">';
		if ($showempty) {
			$out .= '<option value="-1"';
			if ($selected == -1) {
				$out .= ' selected';
			}
			$out .= '>&nbsp;</option>';
		}

		$sql = "SELECT c.id, c.code, c.label as type FROM ".$this->db->prefix()."c_type_fees as c";
		if ($active >= 0) {
			$sql .= " WHERE c.active = ".((int) $active);
		}
		$sql .= " ORDER BY c.label ASC";
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$out .= '<option value="'.$obj->id.'"';
				if ($obj->code == $selected || $obj->id == $selected) {
					$out .= ' selected';
				}
				$out .= '>';
				if ($obj->code != $langs->trans($obj->code)) {
					$out .= $langs->trans($obj->code);
				} else {
					$out .= $langs->trans($obj->type);
				}
				$i++;
			}
		}
		$out .= '</select>';
		$out .= ajax_combobox($htmlname);

		return $out;
	}
}
