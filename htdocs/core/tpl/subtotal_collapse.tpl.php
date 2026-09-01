<?php
/* Copyright (C) 2025		Ashutosh Mundra			<ashutosh.mundra@accellier.com>
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
 * Javascript code to collapse/expand the lines of a block of the subtotals module.
 * A block is made of a title line, of all the lines that follow it and of the subtotal line that closes it.
 * The title line and the subtotal line always stay visible, only the lines between them are hidden.
 * The state of each block is saved in the local storage of the browser, so it survives a reload of the page.
 * $object and $object->id must be defined.
 */

/**
 * @var CommonObject $object
 * @var Translate $langs
 *
 * @var ?string $tagidfortablednd
 */
// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page ".basename(__FILE__)." can't be called with no object defined.";
	exit;
}
'
@phan-var-force CommonObject $object
';

$tagidfortablednd = (empty($tagidfortablednd) ? 'tablelines' : $tagidfortablednd);
$langs->load("subtotals");

?>
<!-- BEGIN PHP TEMPLATE SUBTOTAL_COLLAPSE.TPL.PHP - Script to collapse/expand the blocks of the subtotals module -->
<script>
var subtotalCollapseKey = 'subtotal_collapsed_<?php echo dol_escape_js($object->element.'_'.$object->id); ?>';
var subtotalCollapsedRows = [];

/**
 * Read the list of the collapsed blocks from the local storage
 */
function subtotalLoadCollapsedRows() {
	try {
		subtotalCollapsedRows = JSON.parse(window.localStorage.getItem(subtotalCollapseKey)) || [];
	} catch (e) {
		subtotalCollapsedRows = [];
	}
}

/**
 * Save the list of the collapsed blocks into the local storage
 */
function subtotalSaveCollapsedRows() {
	try {
		window.localStorage.setItem(subtotalCollapseKey, JSON.stringify(subtotalCollapsedRows));
	} catch (e) {
		console.log("subtotals: can not save the collapsed blocks in the local storage");
	}
}

/**
 * Hide or show every line according to the blocks that are currently collapsed.
 * Only the lines we hid ourselves are shown again, so we never interfere with lines hidden by another code.
 */
function subtotalRefreshRows() {
	var hideUntilLevel = 0;

	$("#<?php echo $tagidfortablednd; ?> tr.drag").each(function () {
		var row = this;
		var level = (row.dataset.level !== undefined && row.dataset.level !== '') ? parseInt(row.dataset.level, 10) : null;

		// The subtotal line that closes the collapsed block, or a title line of the same or of a higher rank, ends the hidden area
		if (hideUntilLevel > 0 && level !== null && Math.abs(level) <= hideUntilLevel) {
			hideUntilLevel = 0;
		}

		if (hideUntilLevel > 0) {
			$(row).find('input.linecheckbox:checked').prop('checked', false);	// Avoid a mass action on a line that is not visible
			$(row).addClass('subtotalhiddenline').hide();
			return;
		}

		if ($(row).hasClass('subtotalhiddenline')) {
			$(row).removeClass('subtotalhiddenline').show();
		}

		if (level !== null && level > 0) {
			var collapsed = (subtotalCollapsedRows.indexOf(row.id) !== -1);
			$(row).find('.subtotalcollapsepicto')
				.toggleClass('fa-chevron-down', !collapsed)
				.toggleClass('fa-chevron-right', collapsed)
				.attr('title', collapsed ? '<?php echo dol_escape_js($langs->trans("ExpandBlock")); ?>' : '<?php echo dol_escape_js($langs->trans("CollapseBlock")); ?>');
			if (collapsed) {
				hideUntilLevel = level;
			}
		}
	});
}

$(document).ready(function () {
	subtotalLoadCollapsedRows();
	subtotalRefreshRows();

	$("#<?php echo $tagidfortablednd; ?>").on('click', '.subtotalcollapse', function (event) {
		event.preventDefault();
		var rowid = $(this).closest('tr').attr('id');
		var pos = subtotalCollapsedRows.indexOf(rowid);
		if (pos === -1) {
			subtotalCollapsedRows.push(rowid);
		} else {
			subtotalCollapsedRows.splice(pos, 1);
		}
		subtotalSaveCollapsedRows();
		subtotalRefreshRows();
	});
});
</script>
<!-- END PHP TEMPLATE SUBTOTAL_COLLAPSE.TPL.PHP -->
