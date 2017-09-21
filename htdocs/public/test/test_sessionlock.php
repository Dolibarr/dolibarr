<?php

if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK','1');		// Do not check style html tag into posted data
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');		// If there is no need to load and show top and left menu
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');		// If we don't need to load the html.form.class.php
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');       // Do not load ajax.lib.php library
if (! defined("NOLOGIN"))        define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)
// If you don't need session management (can't be logged if no session used). You must also set
// NOCSRFCHECK, NOTOKENRENEWAL, NOLOGIN
// Disable module with GETPOST('disablemodules') won't work. Variable 'dol_...' will not be set.
// $_SESSION are then simple vars if sessions are not active.
// TODO We can close session with session_write_close() as soon as we just need read access.
if (! defined("NOSESSION"))      define("NOSESSION",'1');

define('REQUIRE_JQUERY_MULTISELECT','select2');

print PHP_SESSION_DISABLED;
print PHP_SESSION_NONE;
print PHP_SESSION_ACTIVE;
print '<br>';

print session_status();
require '../../main.inc.php';
print session_status();
print '<br>';

//print 'a'.$_SESSION['disablemodules'].'b';

print 'This page is visible. It means you are not locked.';

//session_write_close();
