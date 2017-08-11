<?php
/*
 * Copyright (C) 2017		 Oscss-Shop       <support@oscss-shop.fr>.
 *
 * This program is free software; you can redistribute it and/or modifyion 2.0 (the "License");
 * it under the terms of the GNU General Public License as published bypliance with the License.
 * the Free Software Foundation; either version 3 of the License, or
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */
$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include("../main.inc.php");
if (!$res && file_exists("../../main.inc.php")) $res = @include("../../main.inc.php");
if (!$res && file_exists("../../../main.inc.php")) $res = @include("../../../main.inc.php");
if (!$res && file_exists("../../../../main.inc.php")) $res = @include("../../../../main.inc.php");
if (!$res && file_exists("../../../dolibarr/htdocs/main.inc.php"))
        $res = @include("../../../dolibarr/htdocs/main.inc.php");     // Used on dev env only
if (!$res && file_exists("../../../../dolibarr/htdocs/main.inc.php"))
        $res = @include("../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (!$res && file_exists("../../../../../dolibarr/htdocs/main.inc.php"))
        $res = @include("../../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (!$res) die("Include of main fails");

global $langs;

ob_start();
?><script><?php ob_end_clean(); ?>

    $(function () {// les fiches détaillées
        if ($(window).width() < 800) {
            var style = {width: '90%', top: '3%', textAlign: 'left', padding: 10, left: '5%'};
        } else if ($(window).width() < 1150) {
            var style = {width: '70%', top: '4%', textAlign: 'left', padding: 10, left: '15%'};
        } else {
            var style = {width: '50%', top: '5%', textAlign: 'left', padding: 10, left: '25%'};
        }
        var closeLink = '<a title="Close" class="fermer fancybox-item fancybox-close" href="javascript:;"></a>';
        $('.details').click(function () {
            thisApp = $(this).parents('tr.app');
            message = (thisApp.children('.long_description').html());
            $.blockUI({
                message: closeLink + message + '<hr><p style="text-align:center"><button class="fermer button"><?= $langs->trans('Fermer') ?></button></p>',
                css: style,
                onBlock: function () {
                    $('.fermer').click(function () {
                        $.unblockUI();
                        return false;
                    });
                    $('.blockOverlay').attr('title', '<?= $langs->trans('Fermer') ?>').click($.unblockUI);
                }
            });
            return false;
        });

        // les galleries
        $(".fancybox").fancybox({
            openEffect: 'none',
            closeEffect: 'none',
            prevEffect: 'none',
            nextEffect: 'none',
            type : 'image',
            helpers: {
                title: {
                    type: 'inside'
                },
                thumbs: {
                    width: 50,
                    height: 50
                }
            }
        });
    });