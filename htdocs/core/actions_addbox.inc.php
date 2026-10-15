<?php
/* Copyright (C) 2026		Frédéric France			<frederic.france@free.fr>
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
 *	\file			htdocs/core/actions_addbox.inc.php
 *  \brief			Code to add a widget on a home page from the "add widget" form
 *  				(fallback used when ajax is disabled; with ajax, core/ajax/box.php does the job)
 */

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

if (GETPOST('addbox')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';

	$zone = GETPOSTINT('areacode');
	$boxcombo = GETPOSTINT('boxcombo');

	// Current order of boxes, format is 'A:12,34,-B:56,' (see FormOther::getBoxesArea())
	$boxorder = GETPOST('boxorder', 'alphanohtml');
	if (!preg_match('/^[A-Z]:[0-9,]*(-[A-Z]:[0-9,]*)*$/', $boxorder)) {
		$boxorder = 'A:';
	}

	if ($boxcombo > 0) {
		// Append the new box at the end of the last column
		if (!preg_match('/[:,]$/', $boxorder)) {
			$boxorder .= ',';
		}
		$boxorder .= $boxcombo;

		// The layout saved is always the one of the logged user, whatever the posted userid
		$result = InfoBox::saveboxorder($db, $zone, $boxorder, $user->id);
		if ($result > 0) {
			setEventMessages($langs->trans("BoxAdded"), null);
		}
	}
}
