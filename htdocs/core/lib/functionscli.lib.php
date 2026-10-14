<?php
/* Copyright (C) 2024	Laurent Destailleur			<eldy@users.sourceforge.net>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file			htdocs/core/lib/functionscli.lib.php
 *	\brief			A set of functions for CLI scripts of Dolibarr
 *					This file contains only functions used by script run on command line.
 */


// Define ANSI color constant to use colors and bold in CLI script
define('DOL_COLOR_RESET', "\033[0m");		// Restore color
define('DOL_COLOR_BOLD', "\033[1m");			// Bold
define('DOL_COLOR_RED', "\033[31m");
define('DOL_COLOR_GREEN', "\033[32m");
define('DOL_COLOR_YELLOW', "\033[33m");
define('DOL_COLOR_BLUE', "\033[34m");
define('DOL_COLOR_MAGENTA', "\033[35m");
define('DOL_COLOR_CYAN', "\033[36m");
define('DOL_COLOR_WHITE', "\033[37m");


/**
 * Output text in color or bold
 *
 * @param	string		$text		Text to show
 * @param	string		$color		Color code
 * @param	boolean		$bold		Bold or not
 * @return	string					Text enhanced with colors
 */
function coloredText($text, $color, $bold = false)
{
	$boldCode = $bold ? DOL_COLOR_BOLD : '';
	return $boldCode . $color . $text . DOL_COLOR_RESET;
}
