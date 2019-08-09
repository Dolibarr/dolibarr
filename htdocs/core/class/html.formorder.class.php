<?php
/* Copyright (C) 2008-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2016      Marcos García        <marcosgdf@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/class/html.formorder.class.php
 *  \ingroup    core
 *	\brief      File of predefined functions for HTML forms for order module
 */

require_once DOL_DOCUMENT_ROOT .'/core/class/html.form.class.php';

/**
 *	Class to manage HTML output components for orders
 *	Before adding component here, check they are not into common part Form.class.php
 */
class FormOrder extends Form
{

    /**
     *  Return combo list of differents status of a orders
     *
     *  @param	string	$selected   Preselected value
     *  @param	int		$short		Use short labels
     *  @param	string	$hmlname	Name of HTML select element
     *  @return	void
     */
    public function selectSupplierOrderStatus($selected = '', $short = 0, $hmlname = 'order_status')
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
		    $options[$value] = $tmpsupplierorder->getLibStatut($short);
	    }

	    print Form::selectarray($hmlname, $options, $selected, 1);
    }

	/**
	 *	Return list of input method (mode used to receive order, like order received by email, fax, online)
	 *  List found into table c_input_method.
	 *
	 *	@param	string	$selected		Id of preselected input method
	 *  @param  string	$htmlname 		Name of HTML select list
	 *  @param  int		$addempty		0=list with no empty value, 1=list with empty value
	 *  @return	array					Tableau des sources de commandes
	 */
	public function selectInputMethod($selected = '', $htmlname = 'source_id', $addempty = 0)
	{
		global $langs;

        $listofmethods=array();

		$sql = "SELECT rowid, code, libelle as label";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_input_method";
		$sql.= " WHERE active = 1";

		dol_syslog(get_class($this)."::selectInputMethod", LOG_DEBUG);
		$resql=$this->db->query($sql);

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
