<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Philippe Grand	    <philippe.grand@atoo-net.com>
 * Copyright (C) 2020      Open-DSI	            <support@open-dsi.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file       htdocs/core/modules/takepos/modules_takepos.php
 *  \ingroup    takepos
 *  \brief      Fichier contenant la classe mere de numerotation des tickets de caisse
 */


/**
 *  \class      ModeleNumRefTakepos
 *  \brief      Classe mere des modeles de numerotation des tickets de caisse
 */
abstract class ModeleNumRefTakepos
{
	/**
	 * @var string Error code (or message)
	 */
	public $error = '';


	public $version = '';

	/**
	 * Return if a module can be used or not
	 *
	 * @return	boolean     true if module can be used
	 */
	public function isEnabled()
	{
		return true;
	}

	/**
	 * Renvoi la description par defaut du modele de numerotation
	 *
	 * @return    string      Texte descripif
	 */
	public function info()
	{
		global $langs;
		$langs->load("cashdesk@cashdesk");
		return $langs->trans("NoDescription");
	}

	/**
	 * Return an example of numbering
	 *
	 * @return	string      Example
	 */
	public function getExample()
	{
		global $langs;
		$langs->load('cashdesk@cashdesk');
		return $langs->trans('NoExample');
	}

	/**
	 *  Checks if the numbers already in the database do not
	 *  cause conflicts that would prevent this numbering working.
	 *
	 * @return	boolean     false if conflict, true if ok
	 */
	public function canBeActivated()
	{
		return true;
	}

	/**
	 * Renvoi prochaine valeur attribuee
	 *
	 * @return     string      Valeur
	 */
	public function getNextValue()
	{
		global $langs;
		return $langs->trans('NotAvailable');
	}

	/**
	 * Renvoi version du modele de numerotation
	 *
	 * @return    string      Valeur
	 */
	public function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans('VersionDevelopment');
		if ($this->version == 'experimental') return $langs->trans('VersionExperimental');
		if ($this->version == 'dolibarr') return DOL_VERSION;
		if ($this->version) return $this->version;
		return $langs->trans('NotAvailable');
	}
}
