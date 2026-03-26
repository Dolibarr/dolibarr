<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
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
 * \file       htdocs/core/modules/accountancy/mod_bookkeeping_neon.php
 * \ingroup    accountancy
 *  \brief     File of class to manage Bookkeeping numbering rules Neon
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/accountancy/modules_accountancy.php';

/**
 *	Class to manage numbering of thirdparties code
 */
class mod_bookkeeping_neon extends ModeleNumRefBookkeeping
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var string Error message
	 */
	public $error = '';

	/**
	 * @var string name
	 */
	public $name = 'Neon';

	/**
	 * @var int	position
	 */
	public $position = 40;


	/**
	 *  Return description of module
	 *
	 *  @param  Translate   $langs  Object langs
	 *  @return string              Description of module
	 */
	public function info($langs)
	{
		$langs->load("companies");
		return $langs->trans("LeopardNumRefModelDesc");
	}

	/**
	 * Return an example of result returned by getNextValue
	 *
	 * @return	string						Return string example
	 */
	public function getExample()
	{
		return '';
	}

	/**
	 * 	Return next free value
	 *
	 *  @param  BookKeeping		$object		Object we need next value for
	 *  @return string
	 */
	public function getNextValue(BookKeeping $object)
	{
		return '';
	}
}
