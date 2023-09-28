<?php
/* Copyright (C) 2017 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023	Lionel Vessiller		<lvessiller@open-dsi.fr>
 * Copyright (C) 2023	John Botella			<john.botella@atm-consulting.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */


/**
 * \file       htdocs/webportal/class/webPortalTheme.class.php
 * \ingroup    webportal
 * \brief      File of class with theme definition for WebPortal
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php'; // used for color functions

/**
 * Class WebPortalTheme
 */
class WebPortalTheme
{
	public $primaryColorHex = '#263c5c';
	public $primaryColorHsl = array(
		'h' => 216, // Hue
		'l' => 42,  // lightness
		's' => 25,  // Saturation
		'a' =>  1   // Alfa
	);


	public $loginLogoUrl;
	public $menuLogoUrl;
	public $loginBackground;


	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->loadPrimaryColor();

		$this->loginLogoUrl = getDolGlobalString('WEBPORTAL_LOGIN_LOGO_URL');
		$this->menuLogoUrl = getDolGlobalString('WEBPORTAL_MENU_LOGO_URL', $this->loginLogoUrl);
		$this->loginBackground = getDolGlobalString('WEBPORTAL_LOGIN_BACKGROUND');
		$this->bannerBackground = getDolGlobalString('WEBPORTAL_BANNER_BACKGROUND');
		$this->bannerUseDarkTheme = getDolGlobalInt('WEBPORTAL_BANNER_BACKGROUND_IS_DARK');
	}

	/**
	 * return hex of primary theme color
	 *
	 * @return string
	 */
	public function loadPrimaryColor()
	{
		global $conf;

		$outColor = '';

		if (!empty($conf->global->WEBPORTAL_PRIMARY_COLOR)) {
			$outColor = $conf->global->WEBPORTAL_PRIMARY_COLOR;
		} elseif (!empty($conf->global->THEME_ELDY_TOPMENU_BACK1)) {
			$outColor = '#' . colorArrayToHex(colorStringToArray($conf->global->THEME_ELDY_TOPMENU_BACK1));
		}

		if (empty($outColor) || !colorValidateHex($outColor)) {
			$outColor = '#263c5c';
		}

		$this->primaryColorHex = $outColor;
		$this->primaryColorHsl = $this->colorHexToHsl($outColor, true, true);
	}

	/**
	 * @param string 	$hex 			color in hex
	 * @param float|false 	$alpha 			0 to 1 to add alpha channel
	 * @param bool 		$returnArray	true=return an array instead, false=return string
	 * @return string|array				String or array
	 */
	public function colorHexToHsl($hex, $alpha = false, $returnArray = false)
	{
		if (function_exists('colorHexToHsl')) {
			return colorHexToHsl($hex, $alpha, $returnArray);
		}

		// For retro compatibility
		// TODO : Remove this when webportal is included in DOLIBARR standard
		$hex      = str_replace('#', '', $hex);
		$red = hexdec(substr($hex, 0, 2)) / 255;
		$green = hexdec(substr($hex, 2, 2)) / 255;
		$blue = hexdec(substr($hex, 4, 2)) / 255;

		$cmin = min($red, $green, $blue);
		$cmax = max($red, $green, $blue);
		$delta = $cmax - $cmin;

		if ($delta == 0) {
			$hue = 0;
		} elseif ($cmax === $red) {
			$hue = (($green - $blue) / $delta);
		} elseif ($cmax === $green) {
			$hue = ($blue - $red) / $delta + 2;
		} else {
			$hue = ($red - $green) / $delta + 4;
		}

		$hue = round($hue * 60);
		if ($hue < 0) {
			$hue += 360;
		}

		$lightness = (($cmax + $cmin) / 2);
		$saturation = $delta === 0 ? 0 : ($delta / (1 - abs(2 * $lightness - 1)));
		if ($saturation < 0) {
			$saturation += 1;
		}

		$lightness = round($lightness*100);
		$saturation = round($saturation*100);

		if ($returnArray) {
			return array(
				'h' => $hue,
				'l' => $lightness,
				's' => $saturation,
				'a' => $alpha === false ? 1 : $alpha
			);
		} elseif ($alpha) {
			return 'hsla(' . $hue . ', ' . $saturation . ', ' . $lightness . ' / ' . $alpha . ')';
		} else {
			return 'hsl(' . $hue . ', ' . $saturation . ', ' . $lightness . ')';
		}
	}
}
