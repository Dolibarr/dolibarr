<?php
/* Copyright (C) 2023-2024 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024	Lionel Vessiller		<lvessiller@easya.solutions>
 * Copyright (C) 2023-2024	John Botella			<john.botella@atm-consulting.fr>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
	/**
	 * @var string
	 */
	public $primaryColorHex = '#263c5c';

	/**
	 * @var array{h:float,l:float,s:float,a:float}
	 */
	public $primaryColorHsl = array(
		'h' => 216, // Hue
		'l' => 42,  // lightness
		's' => 25,  // Saturation
		'a' =>  1   // Alfa
	);

	/**
	 * @var string login logo url
	 */
	public $loginLogoUrl;

	/**
	 * @var string menu logo url
	 */
	public $menuLogoUrl;

	/**
	 * @var string login background
	 */
	public $loginBackground;

	/**
	 * @var string Background of banner
	 */
	public $bannerBackground;

	/**
	 * @var int Use dark theme on banner
	 */
	public $bannerUseDarkTheme;


	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $mysoc, $conf;

		$this->loadPrimaryColor();

		$urllogo = DOL_URL_ROOT.'/theme/common/login_logo.png';
		if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small)) {
			$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_small);
		} elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo)) {
			$urllogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file='.urlencode('logos/'.$mysoc->logo);
		} elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.svg')) {
			$urllogo = DOL_URL_ROOT.'/theme/dolibarr_logo.svg';
		}

		$this->loginLogoUrl = getDolGlobalString('WEBPORTAL_LOGIN_LOGO_URL', $urllogo);
		$this->menuLogoUrl = getDolGlobalString('WEBPORTAL_MENU_LOGO_URL', $this->loginLogoUrl);
		$this->loginBackground = getDolGlobalString('WEBPORTAL_LOGIN_BACKGROUND');
		$this->bannerBackground = getDolGlobalString('WEBPORTAL_BANNER_BACKGROUND');
		$this->bannerUseDarkTheme = getDolGlobalInt('WEBPORTAL_BANNER_BACKGROUND_IS_DARK');
	}

	/**
	 * Load hex of primary theme color
	 *
	 * @return void
	 */
	public function loadPrimaryColor()
	{
		$outColor = '';

		if (getDolGlobalString('WEBPORTAL_PRIMARY_COLOR')) {
			$outColor = getDolGlobalString('WEBPORTAL_PRIMARY_COLOR');
		} elseif (getDolGlobalString('THEME_ELDY_TOPMENU_BACK1')) {
			$outColor = colorArrayToHex(colorStringToArray(getDolGlobalString('THEME_ELDY_TOPMENU_BACK1')));
		}
		if (strpos($outColor, '#') !== 0) {
			$outColor = '#'.$outColor;
		}

		// If custom color is valid, w e use it
		if (!empty($outColor) && colorValidateHex($outColor)) {
			$this->primaryColorHex = $outColor;
			$this->primaryColorHsl = colorHexToHsl($outColor, 1, true);
		}
	}
}
