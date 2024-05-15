
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
