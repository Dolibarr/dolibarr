<?php
/* Copyright (C) 2018 Andreu Bisquerra Gaya  <jove@bisquerra.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
$res=@include("../../main.inc.php");
?>
<!DOCTYPE html>
<html>
    <head>
        <title>TakePOS</title>

        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
        <meta http-equiv="content-type" content="text/html, charset=utf-8"/>

        <meta name="viewport" content=" width=1024, user-scalable=no"/>
        <meta name="apple-mobile-web-app-capable" content="yes"/>
        <meta name="mobile-web-app-capable" content="yes"/>

        <link rel="shortcut icon" sizes="196x196" href="/point_of_sale/static/src/img/touch-icon-196.png"/>
        <link rel="shortcut icon" sizes="128x128" href="/point_of_sale/static/src/img/touch-icon-128.png"/>
        <link rel="apple-touch-icon" href="/point_of_sale/static/src/img/touch-icon-iphone.png"/>
        <link rel="apple-touch-icon" sizes="76x76" href="/point_of_sale/static/src/img/touch-icon-ipad.png"/>
        <link rel="apple-touch-icon" sizes="120x120" href="/point_of_sale/static/src/img/touch-icon-iphone-retina.png"/>
        <link rel="apple-touch-icon" sizes="152x152" href="/point_of_sale/static/src/img/touch-icon-ipad-retina.png"/>

        <style> body { background: #222; } </style>

        <link rel="shortcut icon" href="/web/static/src/img/favicon.ico" type="image/x-icon"/>

        <script type="text/javascript">
            var odoo = {
                csrf_token: "01b9f3f957a45e1ea9faff8a10fda90af39b7921o",
                session_info: {"is_system": true, "expiration_date": false, "server_version": "saas~11.3+e", "user_context": {"tz": "Europe/Brussels", "uid": 1, "lang": "en_US"}, "session_id": "15792d67c19c2ac5263ab7229c80e50f163260e4", "warning": "admin", "company_id": 1, "username": "admin", "fcm_project_id": "", "inbox_action": 122, "multi_lang": false, "partner_id": 3, "web.base.url": "https://demo3.odoo.com", "db": "demo_saas-113_1535734510", "web_tours": ["project_tour"], "partner_display_name": "YourCompany, Administrator", "dbuuid": "34eda548-ad3f-11e8-a712-f8bc125520c4", "server_version_info": ["saas~11", 3, 0, "final", 0, "e"], "user_companies": false, "device_subscription_ids": [], "name": "Administrator", "max_time_between_keys_in_ms": 55, "company_currency_id": 3, "uid": 1, "expiration_reason": false, "is_superuser": true, "currencies": {"1": {"symbol": "\u20ac", "digits": [69, 2], "position": "after"}, "3": {"symbol": "$", "digits": [69, 2], "position": "before"}}},
            };
        </script>

        <script type="text/javascript" src="js/web.assets_common.js"></script>
        <script type="text/javascript" src="js/web.assets_backend.js"></script>
        <script type="text/javascript" src="js/web_editor.summernote.js"></script>
        <script type="text/javascript" src="js/web_editor.assets_editor.js"></script>
        <link type="text/css" rel="stylesheet" href="css/point_of_sale.assets.0.css"/>
        <script type="text/javascript" src="js/point_of_sale.assets.js"></script>

        <script type="text/javascript" id="loading-script">
            odoo.define('web.web_client', function (require) {
                var WebClient = require('web.AbstractWebClient');
                var web_client = new WebClient();

                web_client._title_changed = function() {};
                web_client.show_application = function() {
                    return web_client.action_manager.do_action("pos.ui");
                };

                $(function () {
                    web_client.setElement($(document.body));
                    web_client.start();
                });
                return web_client;
            });
        </script>
    </head>
    <body>
        <div class="o_main_content"></div>
    </body>
</html>
