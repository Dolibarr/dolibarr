<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) <year>  <name of author>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    class/MyTrigger.php
 * \ingroup mymodule
 * \brief   Compatibility class for triggers in Dolibarr < 3.7.
 *
 * Hack for compatibility with Dolibarr versions < 3.7.
 * Remove this and extend DolibarrTriggers directly from interface_99_modMyModule_MyTrigger.class.php
 * if you don't intend to support these versions.
 */

// We ignore the PSR1.Classes.ClassDeclaration.MultipleClasses rule.
// @codingStandardsIgnoreStart
$dolibarr_version = versiondolibarrarray();
if ($dolibarr_version[0] < 3 || ($dolibarr_version[0] == 3 && $dolibarr_version[1] < 7)) { // DOL_VERSION < 3.7
	/**
	 * Class MyTrigger
	 *
	 * For Dolibarr < 3.7.
	 */
	abstract class MyTrigger
	{
	}
} else {
	/**
	 * Class MyTrigger
	 *
	 * For Dolibarr >= 3.7
	 */
	abstract class MyTrigger extends DolibarrTriggers
	{
	}
}
// @codingStandardsIgnoreEnd
