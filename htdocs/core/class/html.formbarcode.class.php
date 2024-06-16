<?php
/* Copyright (C) 2007-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2008-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
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
 *
 */

/**
 *      \file       htdocs/core/class/html.formbarcode.class.php
 *      \brief      Fichier de la class des functions predefinie de composants html
 */


/**
 *      Class to manage barcode HTML
 */
class FormBarCode
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
	 *  Constructor
	 *
	 *  @param  DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *	Return HTML select with list of bar code generators
	 *
	 *  @param	int		$selected       Id code pre-selected
	 *  @param 	array	$barcodelist	Array of barcodes generators
	 *  @param  int		$code_id        Id du code barre
	 *  @param  string	$idForm			Id of html form, ex id="idform"
	 * 	@return	string					HTML select string
	 */
	public function setBarcodeEncoder($selected, $barcodelist, $code_id, $idForm = 'formbarcode')
	{
		global $conf, $langs;

		$disable = '';

		if (!empty($conf->use_javascript_ajax)) {
			print "\n".'<script nonce="'.getNonce().'" type="text/javascript">';
			print 'jQuery(document).ready(function () {
                        jQuery("#select'.$idForm.'").change(function() {
                            var formName = document.getElementById("form'.$idForm.'");
                            formName.action.value="setcoder";
                            formName.submit();
                        });
               });';
			print '</script>'."\n";
			//onChange="barcode_coder_save(\''.$idForm.'\')
		}

		// We check if barcode is already selected by default
		if (((isModEnabled("product") || isModEnabled("service")) && getDolGlobalString('PRODUIT_DEFAULT_BARCODE_TYPE') == $code_id) ||
		(isModEnabled("societe") && getDolGlobalString('GENBARCODE_BARCODETYPE_THIRDPARTY') == $code_id)) {
			$disable = 'disabled';
		}

		if (!empty($conf->use_javascript_ajax)) {
			$select_encoder = '<form action="'.DOL_URL_ROOT.'/admin/barcode.php" method="POST" id="form'.$idForm.'">';
			$select_encoder .= '<input type="hidden" name="token" value="'.newToken().'">';
			$select_encoder .= '<input type="hidden" name="action" value="update">';
			$select_encoder .= '<input type="hidden" name="code_id" value="'.$code_id.'">';
		}

		$selectname = (!empty($conf->use_javascript_ajax) ? 'coder' : 'coder'.$code_id);
		$select_encoder .= '<select id="select'.$idForm.'" class="flat" name="'.$selectname.'">';
		$select_encoder .= '<option value="0"'.($selected == 0 ? ' selected' : '').' '.$disable.'>'.$langs->trans('Disable').'</option>';
		$select_encoder .= '<option value="-1" disabled>--------------------</option>';
		foreach ($barcodelist as $key => $value) {
			$select_encoder .= '<option value="'.$key.'"'.($selected == $key ? ' selected' : '').'>'.$value.'</option>';
		}
		$select_encoder .= '</select>';

		if (!empty($conf->use_javascript_ajax)) {
			$select_encoder .= '</form>';
		}

		return $select_encoder;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Print form to select type of barcode
	 *
	 *  @param  int     $selected          Id code pre-selected
	 *  @param  string  $htmlname          Name of HTML select field
	 *  @param  int     $useempty          Affiche valeur vide dans liste
	 *  @return void
	 *  @deprecated
	 */
	public function select_barcode_type($selected = 0, $htmlname = 'barcodetype_id', $useempty = 0)
	{
		// phpcs:enable
		print $this->selectBarcodeType($selected, $htmlname, $useempty);
	}

	/**
	 *  Return html form to select type of barcode
	 *
	 *  @param  int     $selected          Id code pre-selected
	 *  @param  string  $htmlname          Name of HTML select field
	 *  @param  int     $useempty          Display empty value in select
	 *  @return string
	 */
	public function selectBarcodeType($selected = 0, $htmlname = 'barcodetype_id', $useempty = 0)
	{
		global $langs, $conf;

		$out = '';

		$sql = "SELECT rowid, code, libelle as label";
		$sql .= " FROM ".$this->db->prefix()."c_barcode_type";
		$sql .= " WHERE coder <> '0'";
		$sql .= " AND entity = ".$conf->entity;
		$sql .= " ORDER BY code";

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;

			if ($useempty && $num > 0) {
				$out .= '<select class="flat minwidth75imp" name="'.$htmlname.'" id="select_'.$htmlname.'">';
				$out .= '<option value="0">&nbsp;</option>';
			} else {
				$langs->load("errors");
				$out .= '<select disabled class="flat minwidth75imp" name="'.$htmlname.'" id="select_'.$htmlname.'">';
				$out .= '<option value="0" selected>'.$langs->trans('ErrorNoActivatedBarcode').'</option>';
			}

			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				if ($selected == $obj->rowid) {
					$out .= '<option value="'.$obj->rowid.'" selected>';
				} else {
					$out .= '<option value="'.$obj->rowid.'">';
				}
				$out .= $obj->label;
				$out .= '</option>';
				$i++;
			}
			$out .= "</select>";
			$out .= ajax_combobox("select_".$htmlname);
		} else {
			dol_print_error($this->db);
		}
		return $out;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Show form to select type of barcode
	 *
	 *  @param  string		$page        	Page
	 *  @param  int			$selected    	Id condition preselected
	 *  @param  string		$htmlname    	Nom du formulaire select
	 *  @return	void
	 *  @deprecated
	 */
	public function form_barcode_type($page, $selected = 0, $htmlname = 'barcodetype_id')
	{
		// phpcs:enable
		print $this->formBarcodeType($page, $selected, $htmlname);
	}

	/**
	 *  Return html form to select type of barcode
	 *
	 *  @param  string      $page           Page
	 *  @param  int         $selected       Id condition preselected
	 *  @param  string      $htmlname       Nom du formulaire select
	 *  @return string
	 */
	public function formBarcodeType($page, $selected = 0, $htmlname = 'barcodetype_id')
	{
		global $langs, $conf;
		$out = '';
		if ($htmlname != "none") {
			$out .= '<form method="post" action="'.$page.'">';
			$out .= '<input type="hidden" name="token" value="'.newToken().'">';
			$out .= '<input type="hidden" name="action" value="set'.$htmlname.'">';
			$out .= '<table class="nobordernopadding">';
			$out .= '<tr><td>';
			$out .= $this->selectBarcodeType($selected, $htmlname, 1);
			$out .= '</td>';
			$out .= '<td class="left"><input type="submit" class="button smallpaddingimp" value="'.$langs->trans("Modify").'">';
			$out .= '</td></tr></table></form>';
		}
		return $out;
	}
}
