<?php
/* Copyright (C) 2008-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2016      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/core/class/html.formorder.class.php
 *  \ingroup    core
 *	\brief      File of predefined functions for HTML forms for order module
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

/**
 *	Class to manage HTML output components for orders
 *	Before adding component here, check they are not into common part Form.class.php
 */
class FormOrder extends Form
{
	/**
	 *  Return combo list of different statuses of orders
	 *
	 *  @param	string	$selected   Preselected value
	 *  @param	int		$short		Use short labels
	 *  @param	string	$htmlname	Name of HTML select element
	 *  @param	string	$morecss	More CSS
	 *  @param	int		$multi		Use a multiselect
	 *  @return	void
	 */
	public function selectSupplierOrderStatus($selected = '', $short = 0, $htmlname = 'order_status', $morecss = '', $multi = 1)
	{
		$options = array();

		// 7 is same label than 6. 8 does not exists (billed is another field)
		$statustohow = array(
			'0' => '0',
			'1' => '1',
			'2' => '2',
			'3' => '3',
			'4' => '4',
			'5' => '5',
			'6' => '6,7',
			'9' => '9'
		);

		$tmpsupplierorder = new CommandeFournisseur($this->db);

		foreach ($statustohow as $key => $value) {
			$tmpsupplierorder->statut = $key;
			$tmpsupplierorder->status = $key;
			$options[$value] = $tmpsupplierorder->getLibStatut($short);
		}

		if (is_array($selected)) {
			$selectedarray = $selected;
		} else {
			$selectedarray = explode(',', $selected);
		}

		if (!empty($selectedarray[6])) {	// special case for status '6,7'
			unset($selectedarray[6]);
			unset($selectedarray[7]);
			$selectedarray['6,7'] = '6,7';
		}

		if ($multi) {
			print Form::multiselectarray($htmlname, $options, $selectedarray, 0, 0, $morecss, 0, 0);
		} else {
			print Form::selectarray($htmlname, $options, $selectedarray, 0, 0, 0, '', 0, 0, 0, '', $morecss);  // $selectedarray is ok for $id param @phan-suppress-current-line PhanPluginSuspiciousParamOrder
		}
	}

	/**
	 *  Return combo list of different status of orders
	 *
	 *  @param	string	$selected   Preselected value
	 *  @param	int		$short		Use short labels
	 *  @param	string	$htmlname	Name of HTML select element
	 *  @return	void
	 */
	public function selectOrderStatus($selected = '', $short = 0, $htmlname = 'order_status')
	{
		$options = array();

		$statustohow = array(
			Commande::STATUS_DRAFT,
			Commande::STATUS_VALIDATED,
			Commande::STATUS_SHIPMENTONPROCESS,
			Commande::STATUS_CLOSED,
			Commande::STATUS_CANCELED
		);

		$tmpsupplierorder = new Commande($this->db);

		foreach ($statustohow as $value) {
			$tmpsupplierorder->statut = $value;
			$options[$value] = $tmpsupplierorder->getLibStatut($short);
		}

		if (is_array($selected)) {
			$selectedarray = $selected;
		} else {
			$selectedarray = explode(',', $selected);
		}

		print Form::multiselectarray($htmlname, $options, $selectedarray, 0, 0, '', 0, 150);
	}

	/**
	 *	Return list of input method (mode used to receive order, like order received by email, fax, online)
	 *  List found into table c_input_method.
	 *
	 *	@param	string	$selected		Id of preselected input method
	 *  @param  string	$htmlname 		Name of HTML select list
	 *  @param  int		$addempty		0=list with no empty value, 1=list with empty value
	 *  @return	int						Return integer <0 if KO, >0 if OK
	 */
	public function selectInputMethod($selected = '', $htmlname = 'source_id', $addempty = 0)
	{
		global $langs;

		$listofmethods = array();

		$sql = "SELECT rowid, code, libelle as label";
		$sql .= " FROM ".$this->db->prefix()."c_input_method";
		$sql .= " WHERE active = 1";

		dol_syslog(get_class($this)."::selectInputMethod", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (!$resql) {
			dol_print_error($this->db);
			return -1;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$listofmethods[$obj->rowid] = $langs->trans($obj->code) != $obj->code ? $langs->trans($obj->code) : $obj->label;
		}

		print Form::selectarray($htmlname, $listofmethods, $selected, $addempty);

		return 1;
	}
}
