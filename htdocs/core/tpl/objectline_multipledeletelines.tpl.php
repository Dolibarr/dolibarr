<?php
/* Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014		Romain Ducher		<r.ducher@agence-codecouleurs.fr>
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
 *
 * Need to have following variables defined:
 * $object (invoice, order, ...)
 * $conf
 * $langs
 * $dateSelector
 * $forceall (0 by default, 1 for supplier invoices/orders)
 * $senderissupplier (0 by default, 1 for supplier invoices/orders)
 * $inputalsopricewithtax (0 by default, 1 to also show column with unit price including tax)
 */

global $langs;
$langs->load("cc_extras");
?>

<!-- BEGIN PHP TEMPLATE objectline_multipledeletelines.tpl.php -->

<tr>
	<td width="10" align="center">&#x21B3;</td>
	<td colspan="9">
		<input type="checkbox" name="multiple_delete_select_all" id="multiple_delete_select_all" title="<?php echo $langs->trans('CheckAll') ?>"/>&nbsp;
		<label for="multiple_delete_select_all"><?php echo $langs->trans('CheckAll') ?></label>&nbsp;&nbsp;
		<a id="multiple_deleteline_submit" href="<?php printf("%s?id=%d&amp;action=ask_multipledeleteline", $_SERVER["PHP_SELF"], intval($this->id)); ?>">
			<?php echo img_delete(); ?>
		</a>
	</td>
</tr>

<script type="text/javascript">
	$(document).ready(function () {
		// Check / uncheck all
		$('#multiple_delete_select_all').change(function () {
			var newCheck, newCheckAllLegend;
			
			if ($('#multiple_delete_select_all').is(':checked')) {
				newCheck = 'checked';
				newCheckAllLegend = '<?php echo $langs->trans('UncheckAll') ?>';
			}
			else {
				newCheck = false;
				newCheckAllLegend = '<?php echo $langs->trans('CheckAll') ?>';
			}
			
			$("input[name='multiple_delete_lines[]']").attr('checked', newCheck);
			set_multiple_delete_select_all_label(newCheckAllLegend);
		});
		
		//* Check / uncheck #multiple_delete_select_all if all the checkboxes are checked or not
		$("input[name='multiple_delete_lines[]']").change(function () {
			var nb_checked = $("input[name='multiple_delete_lines[]']:checked").length;
			
			if (nb_checked === 0) {
				// Aucune case cochée, on màj #multiple_delete_select_all pour pouvoir tout cocher.
				$('#multiple_delete_select_all').attr('checked', false);
				set_multiple_delete_select_all_label('<?php echo $langs->trans('CheckAll') ?>');
			}
			else if ($("#tablelines tbody tr[id^='row-']").length === nb_checked) {
				// Toutes les cases sont cochées, on màj #multiple_delete_select_all pour pouvoir tout décocher.
				$('#multiple_delete_select_all').attr('checked', 'checked');
				set_multiple_delete_select_all_label('<?php echo $langs->trans('UncheckAll') ?>');
			}
		});
		
		// Ask to delete (and then delete if it's OK) !
		$('#multiple_deleteline_submit').click(function () {
			$('#addproduct').attr('action', $('#multiple_deleteline_submit').attr('href'));
			$('#addproduct').submit();
			return false;
		});
		
		function set_multiple_delete_select_all_label(newLabel) {
			$("#multiple_delete_select_all").attr('title', newLabel);
			$("label[for='multiple_delete_select_all']").html(newLabel);
		}
	});
</script>

<!-- END PHP TEMPLATE objectline_multipledeletelines.tpl.php -->
