<?php
if (! defined('ISLOADEDBYSTEELSHEET')) die('Must be call by steelsheet'); ?>

/*
 * Dropdown
 */

/* Not supported yet by this theme */

#dropdown-icon-up, #dropdown-icon-down, .login-dropdown-btn {
	display: none !important;
}

/* Disable the hover underline on the login */
a.login-dropdown-a:hover, a.login-dropdown-a span:hover {
	text-decoration: none !important;
	cursor: default;
}