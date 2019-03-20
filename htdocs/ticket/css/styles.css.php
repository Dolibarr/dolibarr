<?php
/* Copyright (C) 2018	Laurent Destailleur		<eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FI8TNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/ticket/css/styles.css.php
 *		\brief      File for CSS style sheet for ticket module
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (! defined('NOLOGIN'))         define('NOLOGIN', 1);          // File must be accessed by logon page so without login
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML', 1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', '1');

session_cache_limiter(false);

require_once '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Define css type
top_httphead('text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

?>

html {
    min-height: 100%; height: 100%;
}

html {
<?php
if (! empty($conf->global->TICKET_SHOW_MODULE_LOGO)) {
    print 'background: url("../public/img/bg_ticket.png") no-repeat 95% 90%;';
}
?>
}


div.ticketform {
    font-family: arial;
    position: static;
    padding: 2em 1em;
    overflow-x: auto;
    border: 2px solid rgb(153, 153, 153);
    background-color: rgb(255, 255, 255);
    box-shadow: 2px 2px 2px rgb(245, 245, 245);
    border-radius: 10px 10px 10px 10px;
    margin: 1.5em;
    background : #ffffff;
	text-align: center;
}

div.ticketform .index_create, .index_display {
	display: inline-block;
    width: 200px;
    height: 58px;
    text-align: center;
    vertical-align: middle;
    margin: 20px;
    text-transform: uppercase;
}

div.ticketform .orange {
    color: #fef4e9;
    border: solid 1px #da7c0c;
    background: #f78d1d;
    background: -webkit-gradient(linear, left top, left bottom, from(#faa51a), to(#f47a20));
    background: -moz-linear-gradient(top, #faa51a, #f47a20);
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#faa51a', endColorstr='#f47a20');
}
div.ticketform .orange:active {
    color: #fcd3a5;
    background: -webkit-gradient(linear, left top, left bottom, from(#f47a20), to(#faa51a));
    background: -moz-linear-gradient(top, #f47a20, #faa51a);
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#f47a20', endColorstr='#faa51a');
}

div.ticketform .orange:hover {
    background: #f47c20;
    background: -webkit-gradient(linear, left top, left bottom, from(#f88e11), to(#f06015));
    background: -moz-linear-gradient(top, #f88e11, #f06015);
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#f88e11', endColorstr='#f06015');
}

div.ticketform .blue {
    color: #d9eef7;
    border: solid 1px #0076a3;
    background: #0095cd;
    background: -webkit-gradient(linear, left top, left bottom, from(#00adee), to(#0078a5));
    background: -moz-linear-gradient(top, #00adee, #0078a5);
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#00adee', endColorstr='#0078a5');
}
div.ticketform .blue:active {
    color: #80bed6;
    background: -webkit-gradient(linear, left top, left bottom, from(#0078a5), to(#00adee));
    background: -moz-linear-gradient(top, #0078a5, #00adee);
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#0078a5', endColorstr='#00adee');
}
div.ticketform .blue:hover {
    background: #007ead;
    background: -webkit-gradient(linear, left top, left bottom, from(#0095cc), to(#00678e));
    background: -moz-linear-gradient(top, #0095cc, #00678e);
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#0095cc', endColorstr='#00678e');
}

#form_create_ticket, #form_view_ticket
{
    margin-left: 10px;
    margin-right: 10px;
    padding-left:1em;
    padding-right:1em;
    padding-top:1.5em;
    padding-bottom:12px;

    border: 1px solid #C0C0C0;
    background-color: #E0E0E0;

    -moz-box-shadow: 4px 4px 4px #DDD;
    -webkit-box-shadow: 4px 4px 4px #DDD;
    box-shadow: 4px 4px 4px #DDD;

    border-radius: 8px;
    border:solid 1px rgba(168,168,168,.4);
    border-top:solid 1px f8f8f8;
    background-color: #f8f8f8;
    background-image: -o-linear-gradient(top, rgba(250,250,250,.6) 0%, rgba(192,192,192,.3) 100%);
    background-image: -moz-linear-gradient(top, rgba(250,250,250,.6) 0%, rgba(192,192,192,.3) 100%);
    background-image: -webkit-linear-gradient(top, rgba(250,250,250,.6) 0%, rgba(192,192,192,.3) 100%);
    background-image: -ms-linear-gradient(top, rgba(250,250,250,.6) 0%, rgba(192,192,192,.3) 100%);
    background-image: linear-gradient(top, rgba(250,250,250,.6) 0%, rgba(192,192,192,.3) 100%);
}
#form_create_ticket input.text,
#form_create_ticket textarea { width:450px;}
