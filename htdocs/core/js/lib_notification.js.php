<?php
/* Copyright (C) 2016	   Sergio Sanchis		<sergiosanchis@hotmail.com>
 * Copyright (C) 2017	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2020-2023 Destailleur Laurent  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * Library javascript to enable Browser notifications
 */

/**
 *	\file			htdocs/core/js/lib_notification.js.php
 *  \brief			Javascript code to manage browser reminers
 */

if (!defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1);
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}

session_cache_limiter('public');

require_once '../../main.inc.php';


/*
 * View
 */

top_httphead('text/javascript; charset=UTF-8');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=10800, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}


print "jQuery(document).ready(function () {\n";

//print "	console.log('referrer=".dol_escape_js($_SERVER['HTTP_REFERER'])."');\n";

print '	var nowtime = Date.now();';
print '	var time_auto_update = '.max(1, getDolGlobalInt('MAIN_BROWSER_NOTIFICATION_FREQUENCY')).';'."\n"; // Always defined
print '	var time_js_next_test;'."\n";
print '	var dolnotif_nb_test_for_page = 0;'."\n";
print ' var dolnotif_idinterval = null;'."\n";
?>

/* Check if Notification is supported */
if ("Notification" in window) {
	/* Check if permission ok */
	if (Notification.permission !== "granted") {
		console.log("Ask Notification.permission");
		Notification.requestPermission(function(result) {
			console.log("result for Notification.requestPermission is "+result);
		});
	}

	/* Launch timer */

	// We set a delay before launching first test so next check will arrive after the time_auto_update compared to previous one.
	//var time_first_execution = (time_auto_update + (time_js_next_test - nowtime)) * 1000;	//need milliseconds
	var time_first_execution = <?php echo max(3, !getDolGlobalString('MAIN_BROWSER_NOTIFICATION_CHECK_FIRST_EXECUTION') ? 0 : $conf->global->MAIN_BROWSER_NOTIFICATION_CHECK_FIRST_EXECUTION); ?>;

	setTimeout(first_execution, time_first_execution * 1000);	// Launch a first execution after a time_first_execution delay
	time_js_next_test = nowtime + time_first_execution;
	console.log("Launch browser notif check: setTimeout is set to launch 'first_execution' function after a wait of time_first_execution="+time_first_execution+". nowtime (time php page generation) = "+nowtime+" time_js_next_check = "+time_js_next_test);
} else {
	console.log("This browser in this context does not support Notification.");
}

/* The method called after time_first_execution on each page */
function first_execution() {
	console.log("Call first_execution of check_events()");
	result = check_events();	//one check before setting the new time for other checks
	if (result > 0) {
		console.log("check_events() is scheduled as a repeated task with a time_auto_update = MAIN_BROWSER_NOTIFICATION_FREQUENCY = "+time_auto_update+"s");
		dolnotif_idinterval = setInterval(check_events, time_auto_update * 1000); // Set new time to run next check events. time_auto_update=nb of seconds
	}
}

/* the method call frequently every time_auto_update */
function check_events() {
	var result = 0;
	dolnotif_nb_test_for_page += 1;

	if (Notification.permission === "granted") {
		var currentToken = 'notrequired';
		const allMeta = document.getElementsByTagName("meta");
		for (let i = 0; i < allMeta.length; i++) {
			if (allMeta[i].getAttribute("name") == 'anti-csrf-currenttoken') {
				currentToken = allMeta[i].getAttribute('content');
				console.log("currentToken in page = "+currentToken);
			}
		}
		time_js_next_test += time_auto_update;

		console.log("Call ajax to check events with time_js_next_test = "+time_js_next_test+" dolnotif_nb_test_for_page="+dolnotif_nb_test_for_page);

		$.ajax("<?php print DOL_URL_ROOT.'/core/ajax/check_notifications.php'; ?>", {
			type: "POST",   // Usually post or get
			async: true,
			data: { time_js_next_test: time_js_next_test, forcechecknow: 1, token: currentToken, dolnotif_nb_test_for_page: dolnotif_nb_test_for_page },
			dataType: "json",
			success: function (result) {
				//console.log(result);
				var arrayofpastreminders = Object.values(result.pastreminders);
				if (arrayofpastreminders && arrayofpastreminders.length > 0) {
					console.log("Retrieved "+arrayofpastreminders.length+" reminders to do.");
					var audio = null;
					<?php
					if (getDolGlobalString('AGENDA_REMINDER_BROWSER_SOUND')) {
						print 'audio = new Audio(\''.DOL_URL_ROOT.'/theme/common/sound/notification_agenda.wav\');';
					}
					?>
					var listofreminderids = '';
					var noti = []

					$.each(arrayofpastreminders, function (index, value) {
						console.log(value);
						var url = "notdefined";
						var title = "Not defined";
						var body = value.label;
						var icon = '<?php print DOL_URL_ROOT.'/theme/common/octicons/build/svg/bell.svg'; ?>';
						var image = '<?php print DOL_URL_ROOT.'/theme/common/octicons/build/svg/bell.svg'; ?>';
						if (value.type == 'agenda' && value.location != null && value.location != '') {
							body += '\n' + value.location;
						}

						if (value.type == 'agenda' && (value.event_date_start_formated != null || value.event_date_start_formated['event_date_start'] != '')) {
							body += '\n' + value.event_date_start_formated;
						}

						if (value.type == 'agenda')
						{
							url = '<?php print DOL_URL_ROOT.'/comm/action/card.php?id='; ?>' + value.id_agenda;
							title = '<?php print dol_escape_js($langs->transnoentities('EventReminder')) ?>';
						}
						var extra = {
							icon: icon,
							body: body,
							lang: '<?php print dol_escape_js($langs->getDefaultLang(1)); ?>',
							tag: value.id_agenda,
							requireInteraction: true	/* wait that the user click or close the notification */
							/* only supported for persistent notification shown using ServiceWorkerRegistration.showNotification() so disabled */
							/* actions: [{ action: 'action1', title: 'New Button Label' }, { action: 'action2', title: 'Another Button' }] */
						};

						// We release the notify
						console.log("Send notification on browser url="+url);
						noti[index] = new Notification(title, extra);
						if (index==0 && audio)
						{
							audio.play();
						}

						if (noti[index]) {
							noti[index].onclick = function (event) {
								/* If the user has clicked on button Activate */
								console.log("A click on notification on browser has been done for url="+url);
								event.preventDefault(); // prevent the browser from focusing the Notification's tab
								window.focus();
								window.open(url, '_blank');
								noti[index].close();
							};

							listofreminderids = (listofreminderids == '' ? '' : listofreminderids + ',') + value.id_reminder
						}
					});

					// Update status of all notifications we sent on browser (listofreminderids)
					console.log("Flag notification as done for listofreminderids="+listofreminderids);
					$.ajax("<?php print DOL_URL_ROOT.'/core/ajax/check_notifications.php?action=stopreminder&listofreminderids='; ?>"+listofreminderids, {
						type: "POST",   // Usually post or get
						async: true,
						data: { time_js_next_test: time_js_next_test, token: currentToken }
					});
				} else {
					console.log("No remind to do found, next search at "+time_js_next_test);
				}
			}
		});

		result = 1;
	} else {
		console.log("Cancel check_events() with dolnotif_nb_test_for_page="+dolnotif_nb_test_for_page+". Check is useless because javascript Notification.permission is "+Notification.permission+" (blocked manually or web site is not https or browser is in Private mode).");

		result = 2;	// We return a positive so the repeated check will done even if authorization is not yet allowed may be after this check)
	}

	if (dolnotif_nb_test_for_page >= 5) {
		console.log("We did "+dolnotif_nb_test_for_page+" consecutive test on this page. We stop checking now from here by clearing dolnotif_idinterval="+dolnotif_idinterval);
		clearInterval(dolnotif_idinterval);
	}

	return result;
}
<?php

print "\n";
print '})'."\n";
