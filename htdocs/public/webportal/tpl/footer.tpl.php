<!-- file footer.tpl.php -->
<?php
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 */
// Protection to avoid direct call of template
if (empty($context) || !is_object($context)) {
	print "Error, template page can't be called as URL";
	exit(1);
}
'@phan-var-force Context $context';

global $langs;

// load messages
$html = '';
$htmlSuccess = '';
$htmlWarning = '';
$htmlError = '';
$jsOut = '';
$jsSuccess = '';
$jsWarning = '';
$jsError = '';
//$useJNotify = false;
//if (!empty($conf->use_javascript_ajax) && empty($conf->global->MAIN_DISABLE_JQUERY_JNOTIFY)) {
//$useJNotify = true;
//}
$useJNotify = true;
$context->loadEventMessages();
// alert success
if (!empty($context->eventMessages['mesgs'])) {
	$htmlSuccess = $useJNotify ? '' : '<div class="success" role="alert">';
	$msgNum = 0;
	foreach ($context->eventMessages['mesgs'] as $mesg) {
		if ($msgNum > 0) {
			$htmlSuccess .= '<br>';
		}
		$htmlSuccess .= $langs->trans($mesg);
		$msgNum++;
	}
	$htmlSuccess .= $useJNotify ? '' : '</div>';
	if ($useJNotify) {
		$jsSuccess = '
               jQuery.jnotify("' . dol_escape_js($htmlSuccess) . '",
                        "success",
                        3000
               );';
	}
}
// alert warning
if (!empty($context->eventMessages['warnings'])) {
	$htmlWarning = $useJNotify ? '' : '<div class="warning" role="alert">';
	$msgNum = 0;
	foreach ($context->eventMessages['warnings'] as $mesg) {
		if ($msgNum > 0) {
			$htmlWarning .= '<br>';
		}
		$htmlWarning .= $langs->trans($mesg);
		$msgNum++;
	}
	$htmlWarning .= $useJNotify ? '' : '</div>';
	if ($useJNotify) {
		$jsWarning .= 'jQuery.jnotify("' . dol_escape_js($htmlWarning) . '", "warning", true);';
	}
}
// alert error
if (!empty($context->eventMessages['errors'])) {
	$htmlError = $useJNotify ? '' : '<div class="error" role="alert">';
	$msgNum = 0;
	foreach ($context->eventMessages['errors'] as $mesg) {
		if ($msgNum > 0) {
			$htmlError .= '<br>';
		}
		$htmlError .= $langs->trans($mesg);
		$msgNum++;
	}
	$htmlError .= $useJNotify ? '' : '</div>';
	if ($useJNotify) {
		$jsError .= 'jQuery.jnotify("' . dol_escape_js($htmlError) . '", "error", true );';
	}
}
$html .= $htmlError . $htmlWarning . $htmlSuccess;
if ($html) {
	$jsOut = $jsSuccess . $jsWarning . $jsError;
	if ($jsOut == '') {
		print $html;
	}
}
$context->clearEventMessages();

if ($context->getErrors()) {
	include __DIR__ . '/errors.tpl.php';
}
if ($jsOut) {
	$js = '<script nonce="' . getNonce() . '">';
	$js .= 'jQuery(document).ready(function() {';
	$js .= $jsOut;
	$js .= '});';
	$js .= '</script>';
	print $js;
}

print '<script src="'.$context->getControllerUrl().'/js/theme.js"></script>';

// Wrapper to manage document_preview and modal_card
if (empty($conf->browser->layout) || $conf->browser->layout != 'phone') { ?>
	<script nonce="<?php print dolPrintHTMLForAttribute(getNonce()) ?>">
		/* JS CODE TO ENABLE document_preview */
		jQuery(document).ready(function () {
			jQuery(".documentpreview").click(function () {
				console.log("We click on preview for element with href=" + $(this).attr('href') + " mime=" + $(this).attr('mime'));
				document_preview($(this).attr('href'), $(this).attr('mime'), '<?php print dol_escape_js($langs->transnoentities("Preview")) ?>');
				return false;
			});
		});

		/* JS CODE TO ENABLE modal_card */
		jQuery(document).ready(function () {
			jQuery(".modal_card").click(function (event) {
				console.log("We click on card link for element with href=" + $(this).attr('href'));
				modal_card($(this).attr('href'));
				event.preventDefault();
				return false;
			});
		});

		/**
		 * Function show modal card. It uses the "modal" function.
		 *
		 * @param 	url 		Url
		 * @param   title       Dialog box title
		 * @return	void
		 * @see newpopup()
		 */
		function modal_card(url, title) {
			console.log("modal_card A click was done: url=" + url);

			let width = "85%";
			let object_width = '100%';
			let height = ($(window).height() - 60) * 0.90;
			let object_height = '98%';
			let newElem = '<a href="#close" aria-label="Close" class="close" onClick="close_modal_card()"></a><iframe id="card-frame" title="' +
				title + '" width="' + object_width + '" height="' + object_height + '" src="' + url + '"></iframe>';

			$('#modalforcard article').css('width', width).css('height', height).html(newElem);
			let modal = document.getElementById('modalforcard');
			openModal(modal);
		}

		/**
		 * Function close modal card. It uses the "modal" function.
		 *
		 * @return	void
		 */
		function close_modal_card() {
			console.log("close_modal_card A click was done");

			let modal = document.getElementById('modalforcard');
			closeModal(modal);
		}

		/**
		 * Function show modal document preview. It uses the "modal" function.
		 * The a tag around the img must have the src='', class='documentpreview', mime='image/xxx', target='_blank' from getAdvancedPreviewUrl().
		 *
		 * @param 	file 		Url
		 * @param 	type 		Mime file type ("image/jpeg", "application/pdf", "text/html")
		 * @param 	title		Title of popup
		 * @return	void
		 * @see newpopup()
		 */
		function document_preview(file, type, title)
		{
			var ValidImageTypes = ["image/gif", "image/jpeg", "image/png", "image/webp"];
			var showOriginalSizeButton = false;

			console.log("document_preview A click was done: file="+file+", type="+type+", title="+title);

			if ($.inArray(type, ValidImageTypes) < 0) {
				/* Not an image */
				var width = '85%';
				var object_width = '100%';
				var height = ($(window).height() - 60) * 0.90;
				var object_height = '98%';

				show_preview('notimage');
			} else {
				/* This is an image */
				var object_width = 0;
				var object_height = 0;
				var img = new Image();

				img.onload = function () {
					object_width = this.width;
					object_height = this.height;

					width = $(window).width() * 0.90;
					console.log("object_width=" + object_width + " window width=" + width);
					if (object_width < width) {
						console.log("Object width is small, we set width of popup according to image width.");
						width = object_width + 60;
					}
					height = $(window).height() * 0.85;
					console.log("object_height=" + object_height + " window height=" + height);
					if (object_height < height) {
						console.log("Object height is small, we set height of popup according to image height.");
						height = object_height + 125;
					} else {
						showOriginalSizeButton = true;
					}

					show_preview('image');
				};

				img.src = file;
			}

			function show_preview(mode) {
				let newElem = '<a href="#close" aria-label="Close" class="close" data-target="modalforpopup" onClick="toggleModal(event)"></a><object name="objectpreview" data="' +
					file + '" type="' + type + '" width="' + object_width + '" height="' + object_height + '" param="noparam"></object>';
				if (mode == 'image' && showOriginalSizeButton) {
					newElem += '<footer>';
					newElem += '<a href="#cancel" role="button" onClick="document_preview_original_size()"><?php print dol_escape_js($langs->trans("OriginalSize"), 1) ?></a>';
					newElem += '<a href="#close" role="button" class="secondary" data-target="modalforpopup" onClick="toggleModal(event)"><?php print dol_escape_js($langs->trans("CloseWindow"), 1) ?></a>';
					newElem += '</footer>';
				}

				$('#modalforpopup article').css('width', width).css('height', height).html(newElem);
				if (showOriginalSizeButton) {
					jQuery("#modalforpopup article > object").css({
						"max-height": "100%",
						"width": "auto",
						"margin-left": "auto",
						"margin-right": "auto",
						"display": "block"
					});
				}

				let modal = document.getElementById('modalforpopup');
				openModal(modal);
			}
		}

		/**
		 * Function set original size of image on modal document preview. It uses the "modal" function.
		 *
		 * @return	void
		 */
		function document_preview_original_size() {
			console.log("document_preview_original_size A click on original size");
			jQuery("#modalforpopup article > object").css({ "max-height": "none" });
		}
	</script>

	<!-- A div to allow modal popup by modal -->
	<dialog id="modalforcard"><article></article></dialog>
	<dialog id="modalforpopup"><article></article></dialog>
<?php } ?>

</body>
</html>
