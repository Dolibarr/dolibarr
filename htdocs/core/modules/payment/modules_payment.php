<?php
/* Copyright (C) 2015      Juanjo Menent	    <jmenent@2byte.es>
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

require_once DOL_DOCUMENT_ROOT.'/core/class/commonnumrefgenerator.class.php';

/**
 * Payment numbering references mother class
 */
abstract class ModeleNumRefPayments extends CommonNumRefGenerator
{
	/**
	 * 	Return next free value
	 *
	 *  @param	Societe			$objsoc     Object thirdparty
	 *  @param  ?Paiement		$object		Object we need next value for
	 *  @return string|int<-1,0>			Value if OK, <=0 if KO
	 */
	abstract public function getNextValue($objsoc, $object);
}
