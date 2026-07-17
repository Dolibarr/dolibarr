/**
 * Tooltip: keep tooltip open when Alt key is pressed
 * Note: Alt key must be pressed AFTER the tooltip is opened
 * because tooltips can contain links or other tooltips
 */
$(function () {  // Execute when DOM is fully loaded

	let altPressed = false;

	/**
	 * Track Alt key state globally
	 * Tooltip events do not provide keyboard state (e.altKey is always undefined),
	 * so we must store the real keyboard state manually.
	 */
	document.addEventListener("keydown", e => {
		if(e.key === "Alt") altPressed = true;
	});

	document.addEventListener("keyup", e => {
		if(e.key === "Alt") altPressed = false;
	});

	let tooltipContainerTarget = '.ui-tooltip[role="tooltip"] .ui-tooltip-content';
	let noAjaxTooltipClass = '.classfortooltip, .classfortooltipdropdown';
	let tooltipClass = noAjaxTooltipClass + ', .classforajaxtooltip';

	/**
	 * Close all opened tooltips from scope
	 */
	let closeOpenedTooltips = function (){
		$(tooltipClass).each(function() {
			if($( this ).data('ui-tooltip')){
				$( this ).tooltip( "close" );
			}
		});
	}

	// /**
	//  * Not needed due to keyup event listener, but i keep it in case of adding an other behavior to maintain tooltip open
	//  * this part of code could be important
	//  */
	// document.addEventListener("keydown", function(evt) {
	// 	evt = evt || window.event;
	// 	var isEscape = false;
	// 	if ("key" in evt) {
	// 		isEscape = (evt.key === "Escape" || evt.key === "Esc");
	// 	} else {
	// 		isEscape = (evt.keyCode === 27);
	// 	}
	// 	if (isEscape) {
	// 		closeOpenedTooltips();
	// 	}
	// });

	/**
	 * Any key release closes all tooltips
	 * (Alt key release ends the "stay open" behavior)
	 */
	document.addEventListener("keyup", function(evt) {
		closeOpenedTooltips();
	});

	/**
	 * Allow clicking links inside tooltips without triggering
	 * the browser's default "Alt + click = download" behavior
	 */
	$(document).on('click', tooltipContainerTarget + ' a', function(evt) {
		evt.preventDefault(); // no download

		if(evt.ctrlKey || $(this).attr('target') == '_blank' ){
			// Open link in new tab
			let win = window.open(this.href, '_blank');
			if (win) win.focus();
		}
		else{
			// Standard navigation
			window.location.href = this.href;
		}
	});

	/**
	 * Support nested tooltips (tooltip inside another tooltip)
	 * Create sub-tooltips dynamically on first mouse enter
	 * but not for ajax tooltips yet
	 */
	$(document).on('mouseenter', noAjaxTooltipClass, function(evt){
		$(this).find('.classfortooltip').each(function() {
			if(!$( this ).data("tooltipset")){
				console.log('ok');
				$( this ).data("tooltipset", true);
				$( this ).tooltip({
					show: { collision: "flipfit", effect:"toggle", delay:50 },
					hide: { delay: 50 },
					tooltipClass: "mytooltip-hover-tooltip",
					content: function () {
						return $(this).prop("title");		/* To force to get title as is */
					}
				});
			}
		});
	});

	/**
	 * When tooltip closes:
	 * If Alt is pressed and tooltip wasn't opened while Alt was already pressed,
	 * reopen the tooltip and keep it open (Alt-hold mode)
	 */
	$(tooltipClass).on("tooltipclose", function (e) {
		if (altPressed && !$(this).data('opened-after-alt-pressed')) {
			let delay = $(this).tooltip( "option", "show.delay");
			$(this).tooltip( "option", "show.delay", 0); // save appear delay
			$(this).tooltip( "open" );
			$(this).tooltip( "option", "show.delay", delay);// restore appear delay
			$('#' + $(this).attr( 'aria-describedby' )).css({'pointer-events': 'auto'});
		}
	});

	/**
	 * When tooltip opens, store whether Alt key was pressed at that moment
	 * This prevents infinite reopen loops
	 */
	$(tooltipClass).on("tooltipopen", function (e) {
		$(this).data('opened-after-alt-pressed', altPressed);
	});
});
