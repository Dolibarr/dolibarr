<?php
/* Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 */

/**
		\file       htdocs/includes/modules/facture/modules_genpassword.php
		\ingroup    core
		\brief      Fichier contenant la classe mère de generation des mots de passe
		\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT.'/lib/functions.inc.php');


/**
   \class      ModeleGenPassword
   \brief      Classe mère des modèles de generation des mots de passe
*/

class ModeleGenPassword
{
	var $error='';

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
	 * 		\brief		Génère le mot de passe
 	 *      \return     string      Renvoi mot de passe généré
	 */
	function getNewGeneratedPassword()
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}
}

?>
