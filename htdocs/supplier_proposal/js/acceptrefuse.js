/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Show the supplier proposal decision form in a native dialog.
 */
jQuery(function () {
	var form = jQuery("#formacceptrefuse");
	if (!form.length || !jQuery.fn.dialog || form.hasClass("ui-dialog-content")) {
		return;
	}

	var buttons = form.find(".formacceptrefuse-buttons button").map(function () {
		var submitter = this;
		return {
			text: jQuery(submitter).text(),
			"class": submitter.className,
			click: function () {
				form[0].requestSubmit(submitter);
			}
		};
	}).get();

	form.dialog({
		title: form.attr("data-title"),
		modal: true,
		resizable: false,
		width: Math.min(600, window.innerWidth * 0.95),
		height: "auto",
		buttons: buttons,
		open: function () {
			jQuery(this).dialog("widget").find(".ui-dialog-buttonpane button").last().trigger("focus");
		},
		close: function () {
			window.location.href = form.attr("data-cancel-url");
		}
	});
	form.find(".formacceptrefuse-buttons").hide();
	form.on("submit", function () {
		form.dialog("widget").find(".ui-dialog-buttonpane button").prop("disabled", true);
	});
});
