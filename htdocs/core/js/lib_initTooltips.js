/**
 * Initialize standard tooltips.
 *
 * @param {HTMLElement|jQuery} root
 */
function initTooltips(root) {
	const $root = jQuery(root);

	const $elements = $root
		.filter(".classfortooltip")
		.add($root.find(".classfortooltip"));

	$elements.each(function () {
		const $el = jQuery(this);

		if ($el.data("ui-tooltip")) {
			$el.tooltip("destroy");
		}

		$el.tooltip({
			tooltipClass: "mytooltip",
			show: { collision: "flipfit", effect: "toggle", delay: 50, duration: 20 },
			hide: { delay: 250, duration: 20 },
			content: function () {
				return $el.prop("title");
			}
		});
	});
}

/**
 * Initialize AJAX tooltips.
 *
 * @param {HTMLElement|jQuery} root
 * @param {string} baseUrl
 */
function initAjaxTooltips(root, baseUrl) {
	const $root = jQuery(root);

	const $elements = $root
		.filter(".classforajaxtooltip")
		.add($root.find(".classforajaxtooltip"));

	const openDelay = 100;
	const $storeElem = jQuery("#dialogforpopup");
	const currentToken = jQuery("meta[name=anti-csrf-currenttoken]").attr("content");

	$elements.each(function () {
		const $el = jQuery(this);

		if ($el.data("ui-tooltip")) {
			$el.tooltip("destroy");
		}

		$el.tooltip({
			tooltipClass: "mytooltip",
			show: { collision: "flipfit", effect: "toggle", delay: 0, duration: 20 },
			hide: { delay: 250, duration: 20 }
		});

		$el.off("mouseover.ajaxTooltip mouseout.ajaxTooltip");

		$el.on("mouseover.ajaxTooltip", function (event) {
			event.stopImmediatePropagation();
			clearTimeout($storeElem.data("openTimeoutId"));

			const params = JSON.parse($el.attr("data-params") || "{}");
			params.token = currentToken;

			$storeElem.data("openTimeoutId", setTimeout(() => {
				$elements.tooltip("close");

				jQuery.ajax({
					url: baseUrl + "/core/ajax/ajaxtooltip.php",
					type: "post",
					async: true,
					data: params,
					success: function (response) {
						if ($el.is(":hover")) {
							$el.tooltip("option", "content", response);
							$el.tooltip("open");
						}
					}
				});
			}, openDelay));
		});

		$el.on("mouseout.ajaxTooltip", function (event) {
			event.stopImmediatePropagation();
			clearTimeout($storeElem.data("openTimeoutId"));
			$elements.tooltip("close");
		});
	});
}

/**
 * Initialize click-to-open tooltip dialogs.
 * Works whether root is a container or a direct element.
 *
 * @param {HTMLElement|jQuery} root - Element or container to scan.
 * @param {number} dialogWidth - Width of the dialog.
 */
function initTooltipDialogs(root, dialogWidth) {
	const $root = jQuery(root);

	// Dialog elements (self + descendants)
	const $dialogs = $root
		.filter(".classfortooltiponclicktext")
		.add($root.find(".classfortooltiponclicktext"));

	$dialogs.each(function () {
		const $dialog = jQuery(this);

		if ($dialog.data("ui-dialog")) {
			$dialog.dialog("destroy");
		}

		$dialog.dialog({
			closeOnEscape: true,
			classes: { "ui-dialog": "highlight" },
			maxHeight: window.innerHeight - 60,
			width: dialogWidth,
			modal: true,
			autoOpen: false
		}).css("z-index", 5000);
	});

	// Trigger elements (self + descendants)
	const $triggers = $root
		.filter(".classfortooltiponclick")
		.add($root.find(".classfortooltiponclick"));

	$triggers.each(function () {
		const $trigger = jQuery(this);

		$trigger.off("click.tooltipDialog");

		$trigger.on("click.tooltipDialog", function () {
			const dolid = jQuery(this).attr("dolid");
			if (!dolid) return false;

			const $dialog = jQuery("#idfortooltiponclick_" + dolid);
			if ($dialog.length && $dialog.data("ui-dialog")) {
				$dialog.dialog("open");
			}

			return false;
		});
	});
}
