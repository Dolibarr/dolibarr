<?php
if (! defined('ISLOADEDBYSTEELSHEET')) die('Must be call by steelsheet'); ?>

/*
 * Dropdown
 */

.open>.dropdown-menu{ /*, #topmenu-login-dropdown:hover .dropdown-menu*/
    display: block;
}

.dropdown-menu {
    box-shadow: none;
    border-color: #eee;
}
.dropdown-menu {
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
.login_block.usedropdown .logout-btn{
    display: none;
}

.tmenu .open.dropdown, .login_block .open.dropdown, .tmenu .open.dropdown, .login_block .dropdown:hover{
    background: rgba(0, 0, 0, 0.1);
}
.tmenu .dropdown-menu, .login_block .dropdown-menu {
    position: absolute;
    right: 0;
    <?php echo $left; ?>: auto;
    line-height:1.3em;
}
.tmenu .dropdown-menu, .login_block  .dropdown-menu .user-body {
    border-bottom-right-radius: 4px;
    border-bottom-left-radius: 4px;
}
.user-body {
    color: #333;
}
.side-nav-vert .user-menu .dropdown-menu {
    border-top-right-radius: 0;
    border-top-left-radius: 0;
    padding: 1px 0 0 0;
    border-top-width: 0;
    width: 300px;
}
.side-nav-vert .user-menu .dropdown-menu {
    margin-top: 0;
    border-top-left-radius: 0;
    border-top-right-radius: 0;
}

.side-nav-vert .user-menu .dropdown-menu > .user-header {
    height: 175px;
    padding: 10px;
    text-align: center;
    white-space: normal;
}

.dropdown-user-image {
    border-radius: 50%;
    vertical-align: middle;
    z-index: 5;
    height: 90px;
    width: 90px;
    border: 3px solid;
    border-color: transparent;
    border-color: rgba(255, 255, 255, 0.2);
    max-width: 100%;
    max-height :100%;
}

.dropdown-menu > .user-header{
    background: rgb(<?php echo $colorbackhmenu1 ?>);
}

.dropdown-menu > .user-footer {
    background-color: #f9f9f9;
    padding: 10px;
}

.user-footer:after {
    clear: both;
}

.dropdown-menu > .user-body {
    padding: 15px;
    border-bottom: 1px solid #f4f4f4;
    border-top: 1px solid #dddddd;
    white-space: normal;
}

#topmenu-login-dropdown{
    padding: 0 5px 0 5px;
}
#topmenu-login-dropdown a:hover{
    text-decoration: none;
}

#topmenuloginmoreinfo-btn{
    display: block;
    text-aling: right;
    color:#666;
    cursor: pointer;
}

#topmenuloginmoreinfo{
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

.user-footer .button-top-menu-dropdown {
    color: #666666;
    border-radius: 0;
    -webkit-box-shadow: none;
    -moz-box-shadow: none;
    box-shadow: none;
    border-width: 1px;
    background-color: #f4f4f4;
    border-color: #ddd;
}
