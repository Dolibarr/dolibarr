<?php
/* Copyright (C) 2007-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *		\file       htdocs/includes/modules/security/generate/modules_genpassword.php
 *		\ingroup    core
 *		\brief      File with parent class for password generating classes
 *		\version    $Id: modules_genpassword.php,v 1.7 2011/07/31 23:28:16 eldy Exp $
 */
require_once(DOL_DOCUMENT_ROOT.'/lib/functions.lib.php');


/**
 *  \class      ModeleGenPassword
 *  \brief      Parent class for password rules/management modules
 */
class ModeleGenPassword
{
	var $error='';

	/**     \brief     	Return if a module can be used or not
	*      	\return		boolean     true if module can be used
	*/
	function isEnabled()
	{
		return true;
	}

	/**		\brief		Renvoi la description par defaut du modele
	*      	\return     string      Texte descripif
	*/
	function info()
	{
		global $langs;
		$langs->load("bills");
		return $langs->trans("NoDescription");
	}

	/**     \brief     	Renvoi un exemple de generation
	*		\return		string      Example
	*/
	function getExample()
	{
		global $langs;
		$langs->load("bills");
		return $langs->trans("NoExample");
	}

	/**
	 * 		\brief		Build new password
 	 *      \return     string      Return a new generated password
	 */
	function getNewGeneratedPassword()
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 * 		\brief		Validate a password
 	 *      \return     int		0 if KO, >0 if OK
	 */
	function validatePassword($password)
	{
		return 1;
	}

}

?>
