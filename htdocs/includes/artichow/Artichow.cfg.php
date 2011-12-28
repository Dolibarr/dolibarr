<?php
/*
 * This work is hereby released into the Public Domain.
 * To view a copy of the public domain dedication,
 * visit http://creativecommons.org/licenses/publicdomain/ or send a letter to
 * Creative Commons, 559 Nathan Abbott Way, Stanford, California 94305, USA.
 *
 */

/*
 * Path to Artichow
 */

define('ARTICHOW', dirname(__FILE__).DIRECTORY_SEPARATOR.'php'.substr(phpversion(), 0, 1));


/*
 * Path to TrueType fonts
 */
if(defined('ARTICHOW_FONT') === FALSE) {

	define('ARTICHOW_FONT', ARTICHOW.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'font');

}

/*
 * Patterns directory
 */
if(defined('ARTICHOW_PATTERN') === FALSE) {

	define('ARTICHOW_PATTERN', ARTICHOW.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'patterns');

}

/*
 * Images directory
 */
if(defined('ARTICHOW_IMAGE') === FALSE) {

	define('ARTICHOW_IMAGE', ARTICHOW.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'images');

}

/*
 * Enable/disable cache support
 */
define('ARTICHOW_CACHE', TRUE);

/*
 * Cache directory
 */
if(defined('ARTICHOW_CACHE') === FALSE) {

	define('ARTICHOW_CACHE_DIRECTORY', ARTICHOW.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'cache');

}

/*
 * Prefix for class names
 * No prefix by default
 */
define('ARTICHOW_PREFIX', '');

/*
 * Trigger errors when use of a deprecated feature
 */
define('ARTICHOW_DEPRECATED', TRUE);

/*
 * Fonts to use
 */

// DOL_CHANGE LDR
if (defined('ARTICHOW_FONT_NAMES')) $fonts=explode(',',constant('ARTICHOW_FONT_NAMES'));
else $fonts = array(
	'Aerial',
	'AerialBd',
	'AerialBdIt',
	'AerialIt'
);
global $artichow_defaultfont;
$artichow_defaultfont=$fonts[0];
//var_dump(ARTICHOW_FONT);
//var_dump($fonts);
//var_dump($artichow_defaultfont);
//exit;
?>