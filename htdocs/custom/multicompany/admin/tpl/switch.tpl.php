<?php
/* Copyright (C) 2017-2019	Regis Houssin	<regis.houssin@inodbox.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 */
?>

<!-- BEGIN MULTICOMPANY AJAX TEMPLATE -->
<div id="dialog-delete" title="<?php echo $langs->trans('DeleteAnEntity'); ?>" class="hideobject">
	<p><?php echo img_warning().' '.$langs->trans('ConfirmDeleteEntity'); ?></p>
</div>
<script type="text/javascript">
$(document).ready(function() {
	$("#multicompany_entity_list").on('click', '.multicompany-button-active-on, .multicompany-button-visible-on', function() {
		var button = $(this);
		var type = button.attr('id').match(/^[a-z]+/g);
		var id = parseInt(button.attr('id').match(/[0-9]+$/g));
		$.get( "<?php echo dol_buildpath('/multicompany/core/ajax/functions.php',1); ?>", {
			'action': 'setStatusDisable',
			'type': type[0],
			'id': id
			},
			function (result) {
				if (result == 1) {
					var csstype = (type == 'activetemplate' ? 'active' : type);
					var title = '<?php echo $langs->transnoentities("Disabled"); ?>';
					button.attr('title', title).attr('alt', title);
					button.removeClass('fa-check-square multicompany-button-' + csstype + '-on');
					button.addClass('fa-square multicompany-button-' + csstype + '-off');
					if (type == 'active') {
						$('#visible_' + id).attr('title', title).attr('alt', title);
						$('#visible_' + id).removeClass('fa-check-square multicompany-button-visible-on');
						$('#visible_' + id).removeClass('multicompany-button-visible-off');
						$('#visible_' + id).addClass('fa-square multicompany-button-disabled');
					}
				} else {
					$.jnotify("<?php echo $langs->trans("ErrorEntityStatusDisable"); ?>", "error", true);
				}
			}
		);
	});
	$("#multicompany_entity_list").on('click', '.multicompany-button-active-off, .multicompany-button-visible-off', function() {
		var button = $(this);
		var type = button.attr('id').match(/^[a-z]+/g);
		var id = parseInt(button.attr('id').match(/[0-9]+$/g));
		$.get( "<?php echo dol_buildpath('/multicompany/core/ajax/functions.php',1); ?>", {
			'action': 'setStatusEnable',
			'type': type[0],
			'id': id
			},
			function (result) {
				if (result == 1) {
					var csstype = (type == 'activetemplate' ? 'active' : type);
					var title = '<?php echo $langs->transnoentities("Enabled"); ?>';
					button.attr('title', title).attr('alt', title);
					button.removeClass('fa-square multicompany-button-' + csstype + '-off');
					button.addClass('fa-check-square multicompany-button-' + csstype + '-on');
					if (type == 'active') {
						$('#visible_' + id).removeClass('fa-check-square').removeClass('multicompany-button-disabled');
						$('#visible_' + id).addClass('fa-square multicompany-button-visible-off');
					}
				} else {
					$.jnotify("<?php echo $langs->trans("ErrorEntityStatusEnable"); ?>", "error", true);
				}
			}
		);
	});
	$("#multicompany_entity_list").on('click', '.multicompany-button-delete', function() {
		var button = $(this);
		var id = parseInt(button.attr('id').match(/[0-9]+$/g));
		$('#dialog-delete').dialog({
			resizable: false,
			height: 170,
			width: 400,
			modal: true,
			open: function() {
				$('.ui-dialog-buttonset > button:last').focus();
			},
			buttons: {
				'<?php echo $langs->trans('Delete'); ?>': function() {
					$(this).dialog('close');
					$.get( "<?php echo dol_buildpath('/multicompany/core/ajax/functions.php',1); ?>", {
						"action" : "deleteEntity",
						"id" : id
						},
						function (result) {
							if (result == 1) {
								$('#multicompany_entity_list').dataTable().fnDraw();
								$.jnotify("<?php echo $langs->trans("ConfirmedEntityDeleted"); ?>", "ok");
							} else {
								$.jnotify("<?php echo $langs->trans("ErrorEntityDeleted"); ?>", "error", true);
							}
						}
					);
				},
				'<?php echo $langs->trans('Cancel'); ?>': function() {
					$(this).dialog('close');
				}
			}
		});
	});
	$("#multicompany_entity_list").on('click', '.multicompany-button-setup', function() {
		var button = $(this);
		var id = parseInt(button.attr('id').match(/([0-9]+)$/g));
		window.location.href = "<?php echo $_SERVER["PHP_SELF"].'?action=edit&id=' ?>" + id;
	});
});
</script>
<!-- END MULTICOMPANY AJAX TEMPLATE -->