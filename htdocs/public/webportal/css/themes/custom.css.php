<?php
/* Copyright (C) 2023-2024 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024	Lionel Vessiller		<lvessiller@easya.solutions>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/public/webportal/css/themes/custom.css.php
 * \ingroup webportal
 * \brief   Custom css files for WebPortal
 */

if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}

if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1); // File must be accessed by logon page so without login.
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

session_cache_limiter('public');

if (!defined('MAIN_INC_REL_DIR')) {
	define('MAIN_INC_REL_DIR', '../../');
}
require_once __DIR__.'/../../webportal.main.inc.php';
dol_include_once('/webportal/class/webPortalTheme.class.php');

// Define css type
// top_httphead('text/css');
header("Content-Type: text/css");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");

// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
// if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=10800, public, must-revalidate');
/* } else {
	header('Cache-Control: no-cache');
} */

$webPortalTheme = new WebPortalTheme();

?>
[data-theme="custom"], :root{
	--primary-color-hue: <?php print $webPortalTheme->primaryColorHsl['h']; ?>;
	--primary-color-saturation: <?php print $webPortalTheme->primaryColorHsl['s']; ?>%;
	--primary-color-lightness: <?php print $webPortalTheme->primaryColorHsl['l']; ?>%;
	--banner-background: url(<?php print !empty($webPortalTheme->bannerBackground) ? $webPortalTheme->bannerBackground : '../img/banner.svg' ?>);
}

.login-page {
	<?php
	if (!empty($webPortalTheme->loginBackground)) {
		print '--login-background: rgba(0, 0, 0, 0.4) url("'.$webPortalTheme->loginBackground.'");'."\n";
	}

	if (!empty($webPortalTheme->loginLogoUrl)) {
		print '--login-logo: url("'.$webPortalTheme->loginLogoUrl.'"); /* for relative path, must be relative to the css file or use full url starting by http:// */'."\n";
	}
	?>
}
<?php

print '/* Here, the content of the common custom CSS defined into Home - Setup - Display - CSS */'."\n";
print getDolGlobalString('WEBPORTAL_CUSTOM_CSS');
