<?php
/* Copyright (C) 2011-2013 Regis Houssin <regis.houssin@inodbox.com>
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

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

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

	// Events
	$('#fileupload').fileupload({
		stop: function (e, data) {
			location.href='<?php echo dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]); ?>';
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
