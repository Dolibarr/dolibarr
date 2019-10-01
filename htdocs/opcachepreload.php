<?php
/* Copyright (C) 2019  Laurent Destailleur     <eldy@users.sourceforge.net>
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
 *	\file       htdocs/opcachepreload.php
 *	\ingroup	core
 *	\brief      File that preload PHP files. Used for performance purposes if PHP >= 7.4
 */

// Preload some PHP files.
// WARNING: They won't be reloaded until you restart the Web/PHP server, event if you modify the files.

$files = array(); 	/* An array of files you want to preload */

foreach ($files as $file) {
	opcache_compile_file($file);
}
