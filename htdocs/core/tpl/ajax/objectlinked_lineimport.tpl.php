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
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

$objectUrl = $object->getNomUrl(0, '', 0, 1);
if ($object->element == 'propal') {
	$objectUrl = DOL_URL_ROOT.'/comm/propal/card.php?id='.$object->id;
}

?>

<!-- START TEMPLATE IMPORT OBJECT LINKED LINES -->
<script>

$(document).ready(function(){
	$('.objectlinked_importbtn').click(function (e) {

		e.preventDefault();
		var page = $(this).attr("href");

		var fromelement = $(this).attr("data-element");
		var fromelementid = $(this).attr("data-id");

		if( page != undefined && fromelement != undefined && fromelementid != undefined)
		{
			var windowWidth = $(window).width()*0.8; //retrieve current window width
			var windowHeight = $(window).height()*0.8; //retrieve current window height
			var htmlLines;
			var formId = "ajaxloaded_tablelinesform_" + fromelement + "_" + fromelementid;
			$.get(page, function (data) {
				htmlLines = $(data).find('#tablelines') ;
			});


			var $dialog = $('<form id="' + formId + '" action="<?php print $objectUrl; ?>"  method="post" ></form>')
			.load( page + " #tablelines", function() {

				$("#" + formId + " #tablelines").prop("id", "ajaxloaded_tablelines"); // change id attribute

				$("#" + formId + "  .linecheckbox,#" + formId + " .linecheckboxtoggle").prop("checked", true); // checked by default

				// reload checkbox toggle function
				$("#" + formId + " .linecheckboxtoggle").click(function(){
					var checkBoxes = $("#" + formId + " .linecheckbox");
					checkBoxes.prop("checked", this.checked);
				});


			})
			.html(htmlLines)
			.dialog({
				autoOpen: false,
				modal: true,
				height: windowHeight,
				width: windowWidth,
				title: "<?php echo $langs->transnoentities('LinesToImport'); ?>",
				buttons: {
						"<?php echo $langs->trans('Import'); ?>": function() {
							  $( this ).dialog( "close" );
							  $("#" + formId).append('<input type="hidden" name="action" value="import_lines_from_object" />');
							  $("#" + formId).append('<input type="hidden" name="fromelement" value="' + fromelement + '" />');
							  $("#" + formId).append('<input type="hidden" name="fromelementid" value="' + fromelementid + '" />');
							  $("#" + formId).submit();
						},
						"<?php echo $langs->trans("Cancel"); ?>": function() {
						  $( this ).dialog( "close" );
						}
				}
			});

			$dialog.dialog('open');
		}
		else
		{
			$.jnotify("<?php echo $langs->trans('ErrorNoUrl'); ?>", "error", true);
		}

	});




});

</script>
<style type="text/css">
.objectlinked_importbtn{
	cursor:pointer;
}
</style>
<!-- END TEMPLATE IMPORT OBJECT LINKED LINES -->
