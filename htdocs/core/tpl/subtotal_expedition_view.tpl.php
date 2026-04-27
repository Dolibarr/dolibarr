<?php

/**
 * @var CommonObject $object
 * @var CommonObjectLine $line
 * @var Translate $langs
 *
 * @var int $i
 */

'
@phan-var-force CommonObjectLine|CommonOrderLine|ExpeditionLigne $line
@phan-var-force Commande|Expedition $object
';

if (!empty($line->origin_line_id)) {
	print '<!-- subtotal origin line id = ' . $line->origin_line_id . ' -->'; // id of order line
	$id = $line->id;
	$element = $line->element;
	$desc = $line->desc;
	$line_options = $line->extraparams["subtotal"] ?? array();
	$buttons = $object->status == Expedition::STATUS_DRAFT;
} else {
	print '<!-- subtotal commande line id = ' . $line->rowid . ' -->'; // id of order line
	$id = $line->rowid;
	$element = "commande";
	$desc = $line->description;
	$extraparams = (array) json_decode($line->extraparams, true);
	$line_options = $extraparams["subtotal"] ?? array();
}

$langs->load('subtotals');

$line_color = $object->getSubtotalColors((int) $line->qty);
$colspan = 7;

if (isModEnabled('productbatch')) {
	$colspan++;
}
if (isModEnabled('stock')) {
	$colspan++;
}

print '<tr id="row-' . $id . '" data-id="' . $id . '" data-element="' . $element . '" style="background:#' . $line_color . '" >';

if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
	print '<td class="center linecolnum">' . ($i + 1) . '</td>';
}

if ($line->qty > 0) { ?>
	<td class="linecollabel" colspan="<?php echo $colspan ?>" <?php echo !colorIsLight($line_color) ? ' style="color: white"' : ' style="color: black"' ?>><?php echo str_repeat('&nbsp;', (int) ($line->qty - 1) * 8); ?>
		<?php
		echo $desc;
		if (array_key_exists('titleshowuponpdf', $line_options)) {
			echo '&nbsp;' . img_picto($langs->trans("ShowUPOnPDF"), 'invoicing');
		}
		if (array_key_exists('titleshowtotalexludingvatonpdf', $line_options)) {
			echo '&nbsp; <span title="' . $langs->trans("ShowTotalExludingVATOnPDF") . '">%</span>';
		}
		if (array_key_exists('titleforcepagebreak', $line_options)) {
			echo '&nbsp;' . img_picto($langs->trans("ForcePageBreak"), 'file');
		}
		?>
	</td>
<?php } elseif ($line->qty < 0) { ?>
<td class="linecollabel nowrap right" <?php echo !colorIsLight($line_color) ? ' style="color: white"' : ' style="color: black"' ?> colspan="<?php echo $colspan ?>">
	<?php
	echo $desc;
	if (array_key_exists('subtotalshowtotalexludingvatonpdf', $line_options)) {
		echo '&nbsp; <span title="' . $langs->trans("ShowTotalExludingVATOnPDF") . '">%</span>';
	}
	?>
</td>
<?php }

if (isset($buttons) && $buttons) {
	// Delete picto
	echo '<td class="linecoldelete center">';
	echo '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=ask_subtotal_deleteline&token=' . newToken() . '&lineid=' . $id;
	if ($line->qty > 0) {
		echo '&type=title';
	}
	echo '">';
	if (!colorIsLight($line_color)) {
		echo img_delete('default', 'class="pictodelete" style="color: white"');
	} else {
		echo img_delete('default', 'class="pictodelete" style="color: #666"');
	}
	echo '</a> </td>';
}

print "</tr>";
