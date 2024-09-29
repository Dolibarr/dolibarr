<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Philippe Grand	    <philippe.grand@atoo-net.com>
 * Copyright (C) 2020      Open-DSI	            <support@open-dsi.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *  \file       htdocs/core/modules/takepos/modules_takepos.php
 *  \ingroup    takepos
 *  \brief      File containing the parent class for the numbering of cash register receipts
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonnumrefgenerator.class.php';


/**
 *  Parent Class of the models to number the cash register receipts
 */
abstract class ModeleNumRefTakepos extends CommonNumRefGenerator
{
	/**
	 * Return next free value
	 *
	 * @param	?Societe	$objsoc		Object third party
	 * @param	?Facture	$invoice	Object invoice
	 * @param	string		$mode		'next' for next value or 'last' for last value
	 * @return	string|int<-1,0>		Next ref value or last ref if $mode is 'last'
	 */
	abstract public function getNextValue($objsoc = null, $invoice = null, $mode = 'next');

	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	abstract public function getExample();
}
