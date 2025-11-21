document.addEventListener('Dolibarr:Init', function(e) {
	// this tool allow overwrite because of DISABLE_JQUERY_JNOTIFY conf
	/**
	 * status : 'mesgs' by default, 'warnings', 'errors'
	 */
	Dolibarr.defineTool('setEventMessage',  (msg, status = 'mesgs', sticky = false) =>{

		// Normalize status to match jNotify expected values
		const normalizeStatus = (s) => {
			s = (s || '').toLowerCase();
			if (s === 'error' || s === 'errors') return 'error';
			if (s === 'warning' || s === 'warnings') return 'warning';
			return '';
		};

		const type = normalizeStatus(status);

		let jnotifyConf = {
			delay: 1500                               // the default time to show each notification (in milliseconds)
			, type : type
			, sticky: sticky                             // determines if the message should be considered "sticky" (user must manually close notification)
			, closeLabel: "&times;"                     // the HTML to use for the "Close" link
			, showClose: true                           // determines if the "Close" link should be shown if notification is also sticky
			, fadeSpeed: 150                           // the speed to fade messages out (in milliseconds)
			, slideSpeed: 250                           // the speed used to slide messages out (in milliseconds)
		}

		if(msg.length > 0){
			if (typeof $.jnotify === "function") {
				$.jnotify(msg, jnotifyConf);
			} else {
				const container = document.getElementById('alert-message-container');
				if (container) {
					// Add message to #alert-message-container if exist
					const div = document.createElement('div');
					div.className = type; // error, warning, success
					div.textContent = msg; // safer than innerHTML
					container.appendChild(div);
				} else {
					console.warn("jnotify is missing and setEventMessage tool wasn't replaced so use alert fallback instead");
					// fallback prefix
					let prefix = '';
					if (type === 'error') prefix = 'Error: ';
					else if (type === 'warning') prefix = 'Warning: ';
					window.alert(prefix + msg);
				}
			}
		}
		else{
			Dolibarr.log('setEventMessage : Message is empty');
		}
	}, true);
});
