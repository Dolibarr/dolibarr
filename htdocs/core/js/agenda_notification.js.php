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
*/
if (!defined('NOREQUIREUSER')) define('NOREQUIREUSER', '1');
if (!defined('NOREQUIRESOC')) define('NOREQUIRESOC', '1');
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN')) define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', 1);

session_cache_limiter(FALSE);

require_once '../../main.inc.php';

if(!($_SERVER['HTTP_REFERER'] === $dolibarr_main_url_root . '/' || $_SERVER['HTTP_REFERER'] === $dolibarr_main_url_root . '/index.php')){

    global $langs, $conf;

    $langs->load('agenda');

    // Define javascript type
    header('Content-type: text/javascript; charset=UTF-8');
    header('Cache-Control: no-cache');

    // Check notification permissions API HTML5
    print 'if (Notification.permission !== "granted") {
                    Notification.requestPermission()
                }' . PHP_EOL;

    session_start();
    if (!isset($_SESSION['auto_check_events'])) {

        // Round to eliminate the second part
        $_SESSION['auto_check_events'] = floor(time() / 60) * 60;
        print 'var time_session = ' . $_SESSION['auto_check_events'] . ';' . PHP_EOL;
        print 'var now = ' . $_SESSION['auto_check_events'] . ';' . PHP_EOL;
    } else {

        print 'var time_session = ' . $_SESSION['auto_check_events'] . ';' . PHP_EOL;
        print 'var now = ' . time() . ';' . PHP_EOL;
    }

    //TODO provisionally set to be checked every 60 seconds, the 1000 is because it needs to be in milliseconds
    print 'var time_auto_update = 60;' . "\n";
    ?>


    if (now > (time_session + time_auto_update) || now == time_session) {

        first_execution(); //firts run auto check
    } else {

        var time_first_execution = (time_auto_update - (now - time_session)) * 1000;

        setTimeout(first_execution, time_first_execution); //firts run auto check
    }


    function first_execution() {
        check_events();
        setInterval(check_events, time_auto_update * 1000); //program time for run check events
    }

    function check_events() {

        $.ajax("<?php print dol_buildpath('/core/ajax/check_events.php', 1); ?>", {
            type: "post",   // Usually post o get
            async: true,
            data: {time: time_session},
            success: function (result) {

                var arr = JSON.parse(result);

                if (arr.length > 0) {
                    if (Notification.permission === "granted") {

                        <?php
                        if($conf->global->AGENDA_NOTIFICATION_SOUND){
                            print 'var audio = new Audio(\''.dol_buildpath('/comm/action/sound/notification.mp3', 1).'\');';
                        }
                        ?>

                        $.each(arr, function (index, value) {
                            var body = value['tipo'] + ': ' + value['titulo'];
                            if (value['location'] != null) {
                                body += '\n <?php print $langs->transnoentities('Location')?>: ' + value['location'];
                            }


                            var title = "<?php print $langs->trans('Agenda') ?>";
                            var extra = {
                                icon: "<?php print dol_buildpath('/theme/eldy/img/bell.png', 1); ?>",
                                body: body,
                                tag: value['id']
                            };

                            // We release the notify
                            var noti = new Notification(title, extra);
                            <?php
                            if($conf->global->AGENDA_NOTIFICATION_SOUND){
                                print 'if(index==0)audio.play();'."\n";
                            }
                            ?>
                            noti.onclick = function (event) {
                                event.preventDefault(); // prevent the browser from focusing the Notification's tab
                                window.focus();
                                window.open("<?php print dol_buildpath('/comm/action/card.php?id=', 1); ?>" + value['id'], '_blank');
                                noti.close();
                            };
                        });
                    }
                }
            }
        });
        time_session += time_auto_update;
    }
<?php } ?>
