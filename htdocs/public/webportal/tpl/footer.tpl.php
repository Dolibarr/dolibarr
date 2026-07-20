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
				const modalTitle = $(this).data('modal-title') || $(this).attr('title') || '<?php print dol_escape_js($langs->transnoentities("Preview")) ?>';
				document_preview($(this).attr('href'), $(this).attr('mime'), modalTitle);
				return false;
			});
		});

		/* JS CODE TO ENABLE modal_card */
		jQuery(document).ready(function () {
			jQuery(".modal_card").click(function (event) {
				console.log("We click on card link for element with href=" + $(this).attr('href'));
				const modalTitle = $(this).data('modal-title') || $(this).attr('title') || '';
				modal_card($(this).attr('href'), modalTitle);
				event.preventDefault();
				return false;
			});
		});

		/**
		 * Show modal card using Pico.css modal system
		 *
		 * @param {string} url   - URL to load in the iframe
		 * @param {string} title - Modal title
		 */
		function modal_card(url, title = '') {
			console.log("modal_card called: url=" + url);

			const modal = document.getElementById('modalforcard'); // Pico.css modal container
			if (!modal) {
				console.error('Modal container not found');
				return;
			}

			// Insert structured modal content
			modal.querySelector('article').innerHTML = `
				<div class="dialog-header">
					<div class="dialog-header-title-container" >
						<h2 class="dialog-header-title">${title}</h2>
					</div>
					<div class="dialog-header-action-container" >
						<button data-role="close-dialog" class="dialog-header-btn dialog-close-btn btn-low-emphasis close"></button>
					</div>
				</div>
				<div class="dialog-body">
					<iframe class="dialog-iframe" src="${url}" title="${title}"></iframe>
				</div>
			`;

			// Add close button handler
			const closeBtn = modal.querySelector('.dialog-close-btn');
			if (closeBtn) {
				closeBtn.addEventListener('click', () => close_modal_card());
			}

			// Open modal via Pico.css function
			openModal(modal);
		}

		/**
		 * Close modal card using Pico.css modal system
		 */
		function close_modal_card() {
			const modal = document.getElementById('modalforcard');
			if (!modal) return;

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

				let moreButtons = '';
				if (mode == 'image' && showOriginalSizeButton) {
					// TODO remove PHP in js...
					moreButtons += `
					<button  class="dialog-header-btn btn-low-emphasis" onClick="document_preview_original_size()" ><?php print dol_escape_js($langs->trans("OriginalSize"), 1) ?></button>
					`;
				}

				let newElem = `
					<div class="dialog-header">
						<div class="dialog-header-title-container" >
							<h2 class="dialog-header-title">${title}</h2>
						</div>
						<div class="dialog-header-action-container" >
							<button data-role="close-dialog" class="dialog-header-btn dialog-close-btn btn-low-emphasis close"></button>
							${moreButtons}
						</div>
					</div>
					<div class="dialog-body">
						<object class="object-preview" name="objectpreview" data="${file}" type="${type}"  width="${object_width}" height="${object_height}" param="noparam" ></object>
					</div>
				`;

				$('#modalforpopup article').html(newElem);

				if (mode == 'image' && showOriginalSizeButton) {
					dragToScroll($('#modalforpopup .dialog-body'), '--drag-to-scroll');
				}

				let modal = document.getElementById('modalforpopup');

				// Add close button handler
				const closeBtn = modal.querySelector('.dialog-close-btn');
				if (closeBtn) {
					closeBtn.addEventListener('click', () => closeModal(modal));
				}

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
			jQuery("#modalforpopup object.object-preview").toggleClass('--show-original-size');
			jQuery("#modalforpopup .dialog-body").toggleClass('--drag-to-scroll');
		}
	</script>

	<!-- A div to allow modal popup by modal -->
	<dialog id="modalforcard" class="dialog-full-screen" ><article></article></dialog>
	<dialog id="modalforpopup" class="dialog-popup" ><article></article></dialog>
<?php } ?>

</body>
</html>
