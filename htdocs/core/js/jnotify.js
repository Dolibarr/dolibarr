// Copyright (C) 2011 Regis Houssin        <regis.houssin@capnetworks.com>
// Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
//
// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program. If not, see <http://www.gnu.org/licenses/>.
// or see http://www.gnu.org/
//

//
// \file       htdocs/core/js/jnotify.js
// \brief      File that include javascript functions for jnotify default options
//

$(document).ready(function() {
	$.jnotify.setup({
		delay: 3000									// the default time to show each notification (in milliseconds)
		, sticky: false								// determines if the message should be considered "sticky" (user must manually close notification)
		, closeLabel: "&times;"						// the HTML to use for the "Close" link
		, showClose: true							// determines if the "Close" link should be shown if notification is also sticky
		, fadeSpeed: 1000							// the speed to fade messages out (in milliseconds)
		, slideSpeed: 250                           // the speed used to slide messages out (in milliseconds)
		, classContainer: "jnotify-container"
		, classNotification: "jnotify-notification"
		, classBackground: "jnotify-background"
		, classClose: "jnotify-close"
		, classMessage: "jnotify-message"
		, init: null                                // callback that occurs when the main jnotify container is created
		, create: null                              // callback that occurs when when the note is created (occurs just before appearing in DOM)
		, beforeRemove: null                        // callback that occurs when before the notification starts to fade away
	});
});