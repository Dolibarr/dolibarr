// This file is purely for IDE autocompletion and developer convenience.
// It is never executed or loaded in Dolibarr itself.

// MOCK DEFINITION: Dolibarr.tools
// This mock helps your code editor understand the structure of Dolibarr.tools
// and provides autocomplete hints, parameter hints, and inline documentation.
// You can safely edit this file to add all standard Dolibarr tools for autocompletion.

var Dolibarr = {
	tools: {

		/**
		 * Displays a Dolibarr notification message (success, warning, or error).
		 * This is the JavaScript equivalent of the PHP setEventMessage tool.
		 *
		 * @param {string} msg      The message text to display
		 * @param {string=} type    Optional: 'mesgs' (default), 'warnings', or 'errors'
		 * @param {boolean=} sticky Optional: true if the message should stay until manually closed
		 *
		 * Example usage in your IDE:
		 * Dolibarr.tools.setEventMessage('Operation successful', 'success');
		 */
		setEventMessage: function(msg, type, sticky) {},

		// You can add more standard Dolibarr tools here for IDE autocompletion.
		// Example:
		// alertUser: function(msg) {},
	}
};
