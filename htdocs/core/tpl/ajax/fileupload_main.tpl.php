<?php
/* Copyright (C) 2011-2012 Regis Houssin <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 */
?>

<!-- START TEMPLATE FILE UPLOAD MAIN -->
<script type="text/javascript">
window.locale = {
    "fileupload": {
        "errors": {
            "maxFileSize": "<?php echo $langs->trans('FileIsTooBig'); ?>",
            "minFileSize": "<?php echo $langs->trans('FileIsTooSmall'); ?>",
            "acceptFileTypes": "<?php echo $langs->trans('FileTypeNotAllowed'); ?>",
            "maxNumberOfFiles": "<?php echo $langs->trans('MaxNumberOfFilesExceeded'); ?>",
            "uploadedBytes": "<?php echo $langs->trans('UploadedBytesExceedFileSize'); ?>",
            "emptyResult": "<?php echo $langs->trans('EmptyFileUploadResult'); ?>"
        },
        "error": "<?php echo $langs->trans('Error'); ?>",
        "start": "<?php echo $langs->trans('Start'); ?>",
        "cancel": "<?php echo $langs->trans('Cancel'); ?>",
        "destroy": "<?php echo $langs->trans('Delete'); ?>"
    }
};

$(function () {
	'use strict';

	// Initialize the jQuery File Upload widget:
	$('#fileupload').fileupload();

	// Options
	$('#fileupload').fileupload('option', {
		// Enable iframe cross-domain access via redirect option
		redirect: window.location.href.replace(/\/[^\/]*$/,'<?php echo DOL_URL_ROOT; ?>/includes/jquery/plugins/fileupload/cors/result.html?%s'),
		maxFileSize: '<?php echo $max_file_size; ?>'
	});

	// Events
	$('#fileupload').fileupload({
		completed: function (e, data) {
			location.href='<?php echo $_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"]; ?>';
		},
		destroy: function (e, data) {
			var that = $(this).data('fileupload');
			$( "#confirm-delete" ).dialog({
				resizable: false,
				width: 400,
				modal: true,
				buttons: {
					"<?php echo $langs->trans('Ok'); ?>": function() {
						$( "#confirm-delete" ).dialog( "close" );
						if (data.url) {
							$.ajax(data)
								.success(function (data) {
									if (data) {
										that._adjustMaxNumberOfFiles(1);
										$(this).fadeOut(function () {
											$(this).remove();
											$.jnotify("<?php echo $langs->trans('FileIsDelete'); ?>");
										});
									} else {
										$.jnotify("<?php echo $langs->trans('ErrorFileNotDeleted'); ?>", "error", true);
									}
								});
						} else {
							data.context.fadeOut(function () {
								$(this).remove();
							});
						}
					},
					"<?php echo $langs->trans('Cancel'); ?>": function() {
						$( "#confirm-delete" ).dialog( "close" );
					}
				}
			});
		}
	});
});
</script>
<!-- END TEMPLATE FILE UPLOAD MAIN -->