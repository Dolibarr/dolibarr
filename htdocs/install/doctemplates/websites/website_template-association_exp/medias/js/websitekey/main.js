$(function() {

var siteSticky = function() {
	$(".js-sticky-header").sticky({topSpacing:0});
	/*$(window).on('scroll', function() {
		if ($(this).scrollTop() > 0) {
			$(".js-sticky-header").css('border-bottom', '3px solid var(--secondary-color)');
		} else {
			$(".js-sticky-header").css('border-bottom', '1px solid rgba(255, 255, 255, 0.1)');
		}
	});*/
	$(".js-sticky-header").css('border-bottom', '3px solid var(--secondary-color)');
	$(window).on('scroll', function() {
		if ($(this).scrollTop() > 0) {
			$(".js-sticky-header").addClass('scrolled');
		} else {
			$(".js-sticky-header").removeClass('scrolled');
		}
	});
};
siteSticky();

var siteMenuClone = function() {

	$('.js-clone-nav').each(function() {
	var $this = $(this);
	$this.clone().attr('class', 'site-nav-wrap').appendTo('.site-mobile-menu-body');
	});

	setTimeout(function() {
	var counter = 0;
	$('.site-mobile-menu .has-children').each(function(){
		var $this = $(this);

		$this.prepend('<span class="arrow-collapse collapsed" data-bs-toggle="collapse" data-bs-target="#collapseItem' + counter + '">');

		$this.find('> ul').attr({
		'class' : 'collapse',
		'id' : 'collapseItem' + counter
		});

		counter++;
	});
	}, 1000);

	$('body').on('click', '.arrow-collapse', function(e) {
	var $this = $(this);
	if ($this.closest('li').find('.collapse').hasClass('show')) {
		$this.removeClass('active');
	} else {
		$this.addClass('active');
	}
	e.preventDefault();
	});

	$(window).resize(function() {
	var w = $(this).width();
	if (w > 768 && $('body').hasClass('offcanvas-menu')) {
		$('body').removeClass('offcanvas-menu');
	}
	});

	$('body').on('click', '.js-menu-toggle', function(e) {
	e.preventDefault();
	var $this = $(this);
	if ($('body').hasClass('offcanvas-menu')) {
		$('body').removeClass('offcanvas-menu');
		$this.removeClass('active');
	} else {
		$('body').addClass('offcanvas-menu');
		$this.addClass('active');
	}
	});

	$(document).mouseup(function(e) {
	var container = $(".site-mobile-menu");
	if (!container.is(e.target) && container.has(e.target).length === 0) {
		if ($('body').hasClass('offcanvas-menu')) {
		$('body').removeClass('offcanvas-menu');
		}
	}
	});
};

siteMenuClone();

});


$(document).ready(function() {
	if ($('body').attr('id') === 'mainbody') {
		$(".site-navbar").css('position', 'initial');
	}
});
