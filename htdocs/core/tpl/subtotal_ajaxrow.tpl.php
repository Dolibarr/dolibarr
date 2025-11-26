<?php
/* Copyright (C) 2014-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 * Javascript code to activate the drag and drop on lines
 * while using subtotal module
 * You can use this if you want to be able to drag and drop rows of a HTML table.
 * You must add id="tablelines" on table level tag
 * $object and $object->id must be defined
 * $object->fk_element or $fk_element must be defined
 * you must have ($nboflines or count($object->lines) or count($taskarray) > 0)
 * you must have $table_element_line = 'tablename' or $object->table_element_line with line to move
 */

/**
 * @var Conf $conf
 * @var CommonObject $object
 * @var Translate $langs
 *
 * @var ?string	$table_element_line
 * @var ?string $filepath
 * @var ?string $fk_element
 * @var ?int 	$nboflines
 * @var ?string $tagidfortablednd
 * @var ?string $urltorefreshaftermove
 */
// Protection to avoid direct call of template
if (empty($object) || !is_object($object)) {
	print "Error, template page ".basename(__FILE__)." can't be called with no object defined.";
	exit;
}
'
@phan-var-force ?string $fk_element
@phan-var-force ?Task[] $tasksarray
';

?>

<!-- BEGIN PHP TEMPLATE SUBTOTAL_AJAXROW.TPL.PHP - Script to enable drag and drop on lines of a table using subtotal lines -->
<?php
$id = $object->id;
$fk_element = empty($object->fk_element) ? $fk_element : $object->fk_element;
$table_element_line = (empty($table_element_line) ? $object->table_element_line : $table_element_line);
$nboflines = count($object->lines);
$forcereloadpage = getDolGlobalInt('MAIN_FORCE_RELOAD_PAGE');
$tagidfortablednd = (empty($tagidfortablednd) ? 'tablelines' : $tagidfortablednd);
$filepath = (empty($filepath) ? '' : $filepath);
$langs->load("subtotals");

if (GETPOST('action', 'aZ09') != 'editline' && $nboflines > 1 && $conf->browser->layout != 'phone') { ?>
<div id="notification-message" hidden=""></div>
<script>
function openDialog() {
	jQuery(function() {
		jQuery("#notification-message").dialog({
			resizable: false,
			modal: true,
			buttons: {
				Ok: function() {
					jQuery(this).dialog('close');
				}
			}
		});
	});
}

function init(){
	$(".imgupforline").hide();
	$(".imgdownforline").hide();
	$(".lineupdown").removeAttr('href');
	console.log($(".tdlineupdown"));
	$(".tdlineupdown").each(function (tdindex, tdline) {
		var gripimg = tdline.dataset.gripimg ?? 'grip.png';
		console.log(gripimg);
		$(tdline).css("background-image",'url(<?php echo DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/'; ?>' + gripimg + ')');
		$(tdline).css("background-repeat","no-repeat");
		$(tdline).css("background-position","center center");
		console.log($(".tdlineupdown")[tdindex], tdline);
	})

	console.log("Prepare tableDnd for #<?php echo $tagidfortablednd; ?>");
	var inital_table = $("#<?php echo $tagidfortablednd; ?> .drag").map((_, el) => $(el)[0]).get();
	var rowsToMove = [];
	$("#<?php echo $tagidfortablednd; ?>").tableDnD({
		onDragStart: function (table, row) {
			if (row.parentNode.dataset.level > 0) {
				var hide = false;
				$("#<?php echo $tagidfortablednd; ?> .drag").each(
					function (intIndex) {
						if (hide) {
							if ($(this)[0].dataset.level>-row.parentNode.dataset.level && $(this)[0].dataset.level<=row.parentNode.dataset.level) {
								hide = false;
								return false;
							}
							rowsToMove.unshift($(this));
							if (Math.abs($(this)[0].dataset.level) <= Math.abs(row.parentNode.dataset.level)) {
								hide = false;
							}
						}
						if ($(this)[0].id === row.parentNode.id) {
							rowsToMove.unshift($(this));
							hide = true;
						}
					});
				if (!hide) {
					rowsToMove.forEach(function ($hiddenRow, i) {
						if (i<rowsToMove.length-1) {
							$hiddenRow.hide();
						}
					});
				} else {
					rowsToMove = [];
				}
			}
		},
		onDragStop: function(table, row) {
			if (rowsToMove.length !== 0) {
				rowsToMove.forEach(function (hiddenRow) {
					hiddenRow.insertAfter($("#" + row.id));
					hiddenRow.show();
				});
				rowsToMove = [];
			}

			if (row.dataset.desc !== undefined) {
				checkLinePosition(row, inital_table);
			}
			inital_table = $("#<?php echo $tagidfortablednd; ?> .drag").map((_, el) => $(el)[0]).get();

			var reloadpage = "<?php echo $forcereloadpage; ?>";
			console.log("tableDND onDrop");
			console.log(decodeURI($("#<?php echo $tagidfortablednd; ?>").tableDnDSerialize()));
			$('#<?php echo $tagidfortablednd; ?> tr[data-element=extrafield]').attr('id', '');	// Set extrafields id to empty value in order to ignore them in tableDnDSerialize function
			$('#<?php echo $tagidfortablednd; ?> tr[data-ignoreidfordnd=1]').attr('id', '');	// Set id to empty value in order to ignore them in tableDnDSerialize function
			var roworder = cleanSerialize(decodeURI($("#<?php echo $tagidfortablednd; ?>").tableDnDSerialize()));
			var table_element_line = "<?php echo $table_element_line; ?>";
			var fk_element = "<?php echo $fk_element; ?>";
			var element_id = "<?php echo $id; ?>";
			var filepath = "<?php echo urlencode($filepath); ?>";
			var token = "<?php echo currentToken(); ?>";	// We use old 'token' and not 'newtoken' for Ajax call because the ajax page has the NOTOKENRENEWAL constant set.
			$.post("<?php echo DOL_URL_ROOT; ?>/core/ajax/row.php",
				{
					roworder: roworder,
					table_element_line: table_element_line,
					fk_element: fk_element,
					element_id: element_id,
					filepath: filepath,
					token: token
				},
				function() {
					console.log("tableDND end of ajax call");
					console.log(roworder, table_element_line, fk_element, element_id, filepath, token);
					if (reloadpage == 1) {
						<?php
						$redirectURL = empty($urltorefreshaftermove) ? ($_SERVER['PHP_SELF'].'?'.dol_escape_js($_SERVER['QUERY_STRING'])) : $urltorefreshaftermove;
						// remove action parameter from URL
						$redirectURL = preg_replace('/(&|\?)action=[^&#]*/', '', $redirectURL);
						?>
						location.href = '<?php echo dol_escape_js($redirectURL); ?>';
					}
				});
		},
		onDragClass: "dragClass",
		dragHandle: "td.tdlineupdown"
	});
	$(".tdlineupdown").hover( function() { $(this).addClass('showDragHandle'); },
		function() { $(this).removeClass('showDragHandle'); }
	);
}

function checkLinePosition(row, inital_table) {
	const tbody = $("#<?php echo $tagidfortablednd; ?> .drag").map((_, el) => $(el)[0]).get();

	for (var k = 0; k < tbody.length; k++) {
		const currentRow = tbody[k];
		if (currentRow.dataset.rang !== undefined) {
			currentRow.dataset.rang = k+1;
		}
	}

	var rowLevel = parseInt(row.dataset.level);
	var cancelLineMove = false;
	var found_title = rowLevel >= 0;
	var ignore_level = 0;

	for (var i = row.dataset.rang-2; i >= 0; i--) {

		if (tbody[i].dataset.desc !== undefined) {
			const currentRowLevel1 = parseInt(tbody[i].dataset.level, 10);
			console.log(currentRowLevel1, -rowLevel, currentRowLevel1 < -rowLevel);
			if (rowLevel > 0) {
				// Title line placement managing
				if (currentRowLevel1 <= ignore_level && ignore_level !== 0) {
					console.log("here");
					// continue
				} else if (-currentRowLevel1 === rowLevel) {
					break;
				} else if (currentRowLevel1 > 0 && currentRowLevel1 < rowLevel) {
					if (rowLevel - currentRowLevel1 > 1) {
						$("#notification-message").text("<?php echo $langs->trans("PreviousTitleLevelTooHigh"); ?>");
						cancelLineMove = true;
						break;
					}
					break;
					// console.log(tbody[i].dataset.desc, currentRowLevel1, rowLevel);
				} else if (currentRowLevel1 === rowLevel) {
					for (var j = row.dataset.rang; j < tbody.length; j++) {
						if (tbody[j].dataset.desc !== undefined) {
							const currentRowLevel2 = parseInt(tbody[j].dataset.level, 10);
							if (tbody[i].dataset.desc === tbody[j].dataset.desc && currentRowLevel1 === -currentRowLevel2) {
								$("#notification-message").text("<?php echo $langs->trans("TitleUnderSameLevelSTLine"); ?>");
								cancelLineMove = true;
								break;
							}
						}
					}
				} else if (currentRowLevel1 > rowLevel) {
					for (var j = row.dataset.rang; j < tbody.length; j++) {
						if (tbody[j].dataset.desc !== undefined) {
							const currentRowLevel2 = parseInt(tbody[j].dataset.level, 10);
							if (row.dataset.desc !== tbody[j].dataset.desc && currentRowLevel2 <= -rowLevel) {
								$("#notification-message").text("<?php echo $langs->trans("TitleAfterStLineOfSameLevelTitle"); ?>");
								cancelLineMove = true;
								break;
							}
						}
					}
					break;
				} else if (currentRowLevel1 < -rowLevel) {
					ignore_level = currentRowLevel1;
				} else {
					$("#notification-message").text("<?php echo $langs->trans("TitleUnderSameLevelOrGreater"); ?>");
					cancelLineMove = true;
					break;
				}
			} else if (rowLevel < 0) {
				// Subtotal line placement managing
				if (currentRowLevel1 < 0 && rowLevel <= currentRowLevel1 || currentRowLevel1 >0 && -rowLevel >= currentRowLevel1) {
					console.log(rowLevel, currentRowLevel1);
					if (tbody[i].dataset.desc === row.dataset.desc) {
						found_title = true;
						break;
					}else if (-rowLevel === currentRowLevel1) {
						$("#notification-message").text("<?php echo $langs->trans("STLineUnderCorrespondingTitleDesc"); ?>");
						cancelLineMove = true;
						break;
					} else if (-rowLevel > currentRowLevel1) {
						$("#notification-message").text("<?php echo $langs->trans("STLineUnderCorrespondingTitle"); ?>");
						cancelLineMove = true;
						break;
					}
				}
			}
		}
	}
	if (!found_title) {
		$("#notification-message").text("<?php echo $langs->trans("STLineUnderTitle"); ?>");
		cancelLineMove = true;
	}

	if (cancelLineMove) {
		const tbody_to_replace = $(row).parent()[0];
		$(tbody_to_replace).empty();
		$(tbody_to_replace).append(inital_table);
		init();
		openDialog();
	}
}

$(document).ready(function(){
	init()
});

</script>
<?php } else { ?>
<script>
$(document).ready(function(){
	$(".imgupforline").hide();
	$(".imgdownforline").hide();
	$(".lineupdown").removeAttr('href');
});
</script>
<?php } ?>
<!-- END PHP TEMPLATE AJAXROW.TPL.PHP -->
