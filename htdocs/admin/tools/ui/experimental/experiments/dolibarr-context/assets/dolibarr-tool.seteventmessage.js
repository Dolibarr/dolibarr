document.addEventListener('Dolibarr:Ready', function(e) {

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

		let jnotifyConf = {
			delay: 1500                               // the default time to show each notification (in milliseconds)
			, type : normalizeStatus(status)
			, sticky: sticky                             // determines if the message should be considered "sticky" (user must manually close notification)
			, closeLabel: "&times;"                     // the HTML to use for the "Close" link
			, showClose: true                           // determines if the "Close" link should be shown if notification is also sticky
			, fadeSpeed: 150                           // the speed to fade messages out (in milliseconds)
			, slideSpeed: 250                           // the speed used to slide messages out (in milliseconds)
		}

		if(msg.length > 0){
			$.jnotify(msg, jnotifyConf);
		}
		else{
			Dolibarr.log('setEventMessage : Message is empty');
		}
	}, true);
});
