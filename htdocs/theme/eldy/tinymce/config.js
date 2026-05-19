/* Copyright (C) 2026 Eric Seigne <eric.seigne@cap-rel.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * Dolibarr toolbar presets for TinyMCE.
 *
 * Mirrors the CKEditor presets declared in theme/eldy/ckeditor/config.js
 * (Full, dolibarr_mailings, dolibarr_notes, dolibarr_details, dolibarr_readonly).
 * Loaded by main.inc.php before tinymce.init() is called per field.
 */
window.dolTinymceToolbars = {
	'Full': 'fullscreen preview searchreplace visualblocks | undo redo | styles fontfamily fontsize | bold italic underline strikethrough superscript | forecolor backcolor removeformat align | numlist bullist | outdent indent blockquote | ltr rtl | link unlink | image table hr charmap | pastetext | code',
	'dolibarr_mailings': 'fullscreen preview searchreplace | styles fontfamily fontsize | bold italic underline strikethrough forecolor removeformat | numlist bullist | outdent indent align | link unlink image table hr charmap | code',
	'dolibarr_notes': 'fullscreen searchreplace | styles fontsize | bold italic underline strikethrough forecolor removeformat | numlist bullist | outdent indent align | link unlink image table hr charmap | code',
	'dolibarr_details': 'fullscreen | styles fontsize | bold italic underline strikethrough forecolor removeformat | numlist bullist | outdent indent align | link unlink charmap | code',
	'dolibarr_readonly': 'fullscreen'
};

window.dolTinymcePluginsFor = function (toolbarName) {
	var base = 'fullscreen advlist autolink lists link image charmap preview anchor searchreplace visualblocks code table help wordcount';
	if (toolbarName === 'Full' || toolbarName === 'dolibarr_mailings') {
		base += ' directionality';
	}
	return base;
};
