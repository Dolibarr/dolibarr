/**
 * TOOTLTIP keep open when alt key pressed
 * note alt key must be pressed after tooltip is open (because tooltip can include tooltip...)
 */
$(function () { // .ready() callback, is only executed when the DOM is fully loaded

	let tooltipContainerTarget = '.ui-tooltip[role="tooltip"] .ui-tooltip-content';

	let closeOpenedTooltips = function (){
		$(".classfortooltip, .classfortooltipdropdown").each(function() {
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
	 * On release key tooltip vanish
	 */
	document.addEventListener("keyup", function(evt) {
		closeOpenedTooltips();
	});

	/**
	 * allow click on links in tooltip but remove [alt] + [click] Download behavior
	 */
	$(document).on('click', tooltipContainerTarget + ' a', function(evt) {
		evt.preventDefault(); // no download

		if(evt.ctrlKey || $(this).attr('target') == '_blank' ){
			let win = window.open(this.href, '_blank');
			if (win) {
				//Browser has allowed it to be opened
				win.focus();
			}
		}
		else{
			window.location.href = this.href;
		}
	});

	/**
	 * Tooltip in a tooltip
	 */
	$(document).on('mouseenter', tooltipContainerTarget, function(evt){
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

	$(".classfortooltip, .classfortooltipdropdown").on("tooltipclose", function (e) {
		if (e.altKey && !$(this).data('opened-after-ctrl-pressed')) {
			let delay = $(this).tooltip( "option", "show.delay");
			$(this).tooltip( "option", "show.delay", 0); // save appear delay
			$(this).tooltip( "open" );
			$(this).tooltip( "option", "show.delay", delay);// restore appear delay
			$('#' + $(this).attr( 'aria-describedby' )).css({'pointer-events': 'auto'});
		}
	});

	$(".classfortooltip, .classfortooltipdropdown").on("tooltipopen", function (e) {
		$(this).data('opened-after-ctrl-pressed', e.altKey);
	});
});
