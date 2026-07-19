<?php
/* Copyright (C) 2025		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 *	\file       htdocs/core/class/html.formfiscalyear.class.php
 *  \ingroup    Accountancy (Double entries)
 *	\brief      File of class with all html predefined components
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

/**
 *	Class to manage generation of HTML components for accounting management
 */
class FormFiscalYear extends Form
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
	 *	Return list of fiscal year
	 *
	 *	@param	int		$selected       Preselected type
	 *	@param  string	$htmlname       Name of field in form
	 * 	@param	int		$useempty		Set to 1 if we want an empty value
	 * 	@param	int		$maxlen			Max length of text in combo box
	 * 	@param	int		$help			Add or not the admin help picto
	 * 	@return	void|string				HTML component with the select
	 */
	public function selectFiscalYear($selected = 0, $htmlname = 'fiscalyear', $useempty = 0, $maxlen = 0, $help = 1)
	{
		global $conf, $langs;

		$out = '';

		$sql = "SELECT f.rowid, f.label, f.date_start, f.date_end, f.statut as status";
		$sql .= " FROM ".$this->db->prefix()."accounting_fiscalyear as f";
		$sql .= " WHERE f.entity = ".$conf->entity;
		$sql .= " ORDER BY f.date_start ASC";

		dol_syslog(get_class($this).'::'.__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				$out .= '<select class="flat minwidth200" id="'.$htmlname.'" name="'.$htmlname.'">';
				$i = 0;

				if ($useempty) {
					$out .= '<option value="0">&nbsp;</option>';
				}
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);

					$titletoshowhtml = ($maxlen ? dol_trunc($obj->label, $maxlen) : $obj->label).' <span class="opacitymedium">('.$obj->date_start . " - " . $obj->date_end.')</span>';
					$titletoshow = ($maxlen ? dol_trunc($obj->label, $maxlen) : $obj->label).' <span class="opacitymedium">('.$langs->transnoentitiesnoconv("FiscalYearFromTo", $obj->date_start, $obj->date_end).')</span>';

					$out .= '<option value="'.$obj->rowid.'"';
					if ($obj->rowid == $selected) {
						$out .= ' selected';
					}
					//$out .= ' data-html="'.dol_escape_htmltag(dol_string_onlythesehtmltags($titletoshowhtml, 1, 0, 0, 0, array('span'))).'"';
					$out .= ' data-html="'.dolPrintHTMLForAttribute($titletoshowhtml).'"';
					$out .= '>';
					$out .= dol_escape_htmltag($titletoshow);
					$out .= '</option>';
					$i++;
				}
				$out .= '</select>';
				//if ($user->admin && $help) $out .= info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);

				$out .= ajax_combobox($htmlname, array());
			} else {
				$out .= '<span class="opacitymedium">'.$langs->trans("ErrorNoFiscalyearDefined", $langs->transnoentitiesnoconv("Accounting"), $langs->transnoentitiesnoconv("Setup"), $langs->transnoentitiesnoconv("Fiscalyear")).'</span>';
			}
		} else {
			dol_print_error($this->db);
		}

		return $out;
	}
}
