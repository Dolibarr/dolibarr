
document.addEventListener("DOMContentLoaded", function() {

	// For responsive tables
	document.querySelectorAll('table[responsive="scroll"], table[responsive="collapse"]').forEach((el) => {
		let wrapper = document.createElement('figure');
		el.parentNode.insertBefore(wrapper, el);
		wrapper.appendChild(el);
	});


	// Disable double click on some elements and add loading icon
	document.body.addEventListener('click', (event) => {
		if (!event.target.matches('[busy-on-click]')) return;

		if(event.target.getAttribute('busy-on-click') == 'true'){
			event.target.setAttribute('aria-busy', 'true');
		}
	});

});

/**
 * Enables drag-to-scroll behavior on a given element.
 * Supports mouse, touch, pen, X and Y scrolling.
 *
 * @param {string | Element | jQuery} slider - A CSS selector, DOM element, or jQuery object.
 * @param {string|false} classForEnable - Optional class required to enable dragging.
 */
function dragToScroll(slider, classForEnable = false) {

	const classActive = '--drag-to-scroll-is-active';

	// Normalize input
	if (typeof slider === 'string') {
		slider = document.querySelector(slider);
	} else if (window.jQuery && slider instanceof jQuery) {
		slider = slider[0];
	} else if (!(slider instanceof Element)) {
		console.warn('dragToScroll: invalid element', slider);
		return;
	}

	let isDown = false;
	let startX;
	let startY;
	let scrollLeft;
	let scrollTop;

	slider.addEventListener('pointerdown', (e) => {
		if (classForEnable && !slider.classList.contains(classForEnable)) return;

		isDown = true;
		slider.setPointerCapture(e.pointerId);
		slider.classList.add(classActive);

		startX = e.clientX;
		startY = e.clientY;

		scrollLeft = slider.scrollLeft;
		scrollTop = slider.scrollTop;
	});

	slider.addEventListener('pointermove', (e) => {
		if (!isDown) return;

		e.preventDefault();

		const walkX = e.clientX - startX;
		const walkY = e.clientY - startY;

		slider.scrollLeft = scrollLeft - walkX;
		slider.scrollTop = scrollTop - walkY;
	});

	slider.addEventListener('pointerup', () => {
		isDown = false;
		slider.classList.remove(classActive);
	});
}
