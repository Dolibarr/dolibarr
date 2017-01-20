<?php
/* Copyright (C) 2016	   Sergio Sanchis		<sergiosanchis@hotmail.com>
 * Copyright (C) 2017	   Juanjo Menent		<jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * 
 * Library javascript to enable Browser notifications
 */

if (!defined('NOREQUIREUSER')) define('NOREQUIREUSER', '1');
if (!defined('NOREQUIRESOC')) define('NOREQUIRESOC', '1');
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN')) define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', 1);

require_once '../../main.inc.php';

if (!($_SERVER['HTTP_REFERER'] === $dolibarr_main_url_root . '/' || $_SERVER['HTTP_REFERER'] === $dolibarr_main_url_root . '/index.php'))
{
    global $langs, $conf;

    // Define javascript type
    header('Content-type: text/javascript; charset=UTF-8');

    // TODO Try to make a solution with only a javascript timer that is easier. Difficulty is to avoid notification twice when.
    session_cache_limiter(FALSE);
    header('Cache-Control: no-cache');
    session_start();
    if (!isset($_SESSION['auto_check_events'])) {
        // Round to eliminate the second part
        $_SESSION['auto_check_events'] = floor(time() / 60) * 60;
        print 'var time_session = ' . $_SESSION['auto_check_events'] . ';'."\n";
        print 'var now = ' . $_SESSION['auto_check_events'] . ';' . "\n";
    } else {
        print 'var time_session = ' . $_SESSION['auto_check_events'] . ';' . "\n";
        print 'var now = ' . time() . ';' . "\n";
    }
    print 'var time_auto_update = '.(empty($conf->global->MAIN_BROWSER_NOTIFICATION_FREQUENCY)?'3':(int) $conf->global->MAIN_BROWSER_NOTIFICATION_FREQUENCY).';' . "\n";
    ?>
	
	/* Check if permission ok */
	if (Notification.permission !== "granted") {
        Notification.requestPermission()
    }

    if (now > (time_session + time_auto_update) || now == time_session) {

        first_execution(); //firts run auto check
    } else {

        var time_first_execution = (time_auto_update - (now - time_session)) * 1000;	//need milliseconds

        setTimeout(first_execution, time_first_execution); //first run auto check
    }


    function first_execution() {
    	console.log("Call first_execution");
        check_events();
        setInterval(check_events, time_auto_update * 1000); //program time for run check events
    }

    function check_events() {
    	if (Notification.permission === "granted")
    	{
    		console.log("Call check_events");
            $.ajax("<?php print dol_buildpath('/core/ajax/check_notifications.php', 1); ?>", {
                type: "post",   // Usually post o get
                async: true,
                data: {time: time_session},
                success: function (result) {
                    var arr = JSON.parse(result);
                    if (arr.length > 0) {
                        <?php
                        if (! empty($conf->global->AGENDA_NOTIFICATION_SOUND)) {
                            print 'var audio = new Audio(\''.DOL_URL_ROOT.'/theme/common/sound/notification_agenda.wav'.'\');';
                        }
                        ?>
    
                        $.each(arr, function (index, value) {
                            var url="notdefined";
                            var title="Not defined";
                            var body = value['tipo'] + ': ' + value['titulo'];
                            if (value['type'] == 'agenda' && value['location'] != null && value['location'] != '') {
                                body += '\n <?php print $langs->transnoentities('Location')?>: ' + value['location'];
                            }
    
                            if (value['type'] == 'agenda')
                            {
                             	url = '<?php echo DOL_URL_ROOT.'/comm/action/card.php?id='; ?>' + value['id'];
                                title = '<?php print $langs->trans('Agenda') ?>';
                            }
                            var extra = {
                                icon: '<?php print DOL_URL_ROOT.'/theme/common/bell.png'; ?>',
                                body: body,
                                tag: value['id']
                            };
    
                            // We release the notify
                            var noti = new Notification(title, extra);
                            if (index==0 && audio) 
                            {
                            	audio.play();
                            }
                            noti.onclick = function (event) {
                                console.log("An event to notify on browser was received");
                                event.preventDefault(); // prevent the browser from focusing the Notification's tab
                                window.focus();
                                window.open(url, '_blank');
                                noti.close();
                            };
                        });
                    }
                }
            });
        }
        else
        {
        	console.log("Cancel check_events. Useless because Notification.permission is "+Notification.permission);
        }

        time_session += time_auto_update;
    }
<?php 
}
