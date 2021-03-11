<?php
/* Copyright (C) 2019	Regis Houssin	<regis.houssin@inodbox.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

if (! defined('ISLOADEDBYSTEELSHEET')) die('Must be call by steelsheet');
$colorbackhmenu1='60,70,100';      // topmenu
?>
/* <style type="text/css" > */
/*
 Dropdown
*/

.open>.mc-dropdown-menu { /*, #topmenu-login-dropdown:hover .dropdown-menu*/
    display: block;
}

.mc-dropdown-menu {
    box-shadow: none;
    border-color: #eee;
}
.mc-dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    display: none;
    float: left;
    min-width: 160px;
    padding: 5px 0;
    margin: 2px 0 0;
    font-size: 14px;
    text-align: left;
    list-style: none;
    background-color: #fff;
    -webkit-background-clip: padding-box;
    background-clip: padding-box;
    border: 1px solid #ccc;
    border: 1px solid rgba(0,0,0,.15);
    border-radius: 4px;
    -webkit-box-shadow: 0 6px 12px rgba(0,0,0,.175);
    box-shadow: 0 6px 12px rgba(0,0,0,.175);
}



/*
* MENU Dropdown
*/
.login_block.usedropdown .logout-btn {
    display: none;
}

.login_block .open.mcdropdown, .login_block .mcdropdown:hover {
    background: rgba(0, 0, 0, 0.1);
}
.login_block .mc-dropdown-menu {
    position: absolute;
    right: 0;
    left: auto;
    line-height:1.3em;
}
.login_block .mc-dropdown-menu .mc-body {
    border-bottom-right-radius: 4px;
    border-bottom-left-radius: 4px;
}
.mc-body {
    color: #333;
}
.side-nav-vert .mc-menu .mc-dropdown-menu {
    border-top-right-radius: 0;
    border-top-left-radius: 0;
    padding: 1px 0 0 0;
    border-top-width: 0;
    width: 300px;
}
.side-nav-vert .mc-menu .mc-dropdown-menu {
    margin-top: 0;
    border-top-left-radius: 0;
    border-top-right-radius: 0;
}

.side-nav-vert .mc-menu .mc-dropdown-menu > .mc-header {
    height: 175px;
    padding: 10px;
    text-align: center;
    white-space: normal;
}

.dropdown-mc-image {
	font-size: 90px;
    border-radius: 50%;
    vertical-align: middle;
    z-index: 5;
    height: 90px;
    width: 90px;
    /*border: 3px solid;*/
    border-color: transparent;
    border-color: rgba(255, 255, 255, 0.2);
    max-width: 100%;
    max-height :100%;
}

.mc-dropdown-menu > .mc-header {
    background: rgb(<?php echo $colorbackhmenu1 ?>);
}

.mc-dropdown-menu > .mc-footer {
    background-color: #f9f9f9;
    padding: 10px;
}

.mc-footer:after {
    clear: both;
}

.mc-dropdown-menu > .mc-body {
    padding: 15px;
    border-bottom: 1px solid #f4f4f4;
    border-top: 1px solid #dddddd;
    white-space: normal;
}

#topmenu-mc-dropdown {
    padding: 0 5px 0 5px;
}
#topmenu-mc-dropdown a:hover {
    text-decoration: none;
}
.topmenu-mc-label {
	font-family: roboto,arial,tahoma,verdana,helvetica;
	font-size: 13px;
	padding-left: 4px;
}
.atoplogin #mc-dropdown-icon-down, .atoplogin #mc-dropdown-icon-up {
    font-size: 0.7em;
}
.atoplogin #mc-dropdown-icon {
	cursor:pointer;
}

#topmenumcmoreinfo-btn {
    display: block;
    text-aling: right;
    color:#666;
    cursor: pointer;
}

#topmenumcmoreinfo {
    display: none;
    clear: both;
    font-size: 0.95em;
}

.button-top-menu-dropdown {
    display: inline-block;
    padding: 6px 12px;
    margin-bottom: 0;
    font-size: 14px;
    font-weight: 400;
    line-height: 1.42857143;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    -ms-touch-action: manipulation;
    touch-action: manipulation;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    background-image: none;
    border: 1px solid transparent;
    border-radius: 4px;
}

.mc-footer .button-top-menu-dropdown {
    color: #666666;
    border-radius: 0;
    -webkit-box-shadow: none;
    -moz-box-shadow: none;
    box-shadow: none;
    border-width: 1px;
    background-color: #f4f4f4;
    border-color: #ddd;
}
