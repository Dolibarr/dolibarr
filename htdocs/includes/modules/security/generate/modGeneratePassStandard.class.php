<?php
/* Copyright (C) 2006-2007 Laurent Destailleur <eldy@users.sourceforge.net>
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
 */

/**
 *      \file       htdocs/includes/modules/security/generate/modGeneratePassStandard.class.php
 *      \ingroup    core
 *		\brief      File to manage password generation according to standard rule
 *		\version	$Id$
 */

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/security/generate/modules_genpassword.php");


/**
	    \class      modGeneratePassNone
		\brief      Classe permettant de g�n�rer un mot de passe selon r�gle standard
*/
class modGeneratePassStandard extends ModeleGenPassword
{
	var $id;
	var $length;

	var $db;
	var $conf;
	var $lang;
	var $user;

	
	/**
	 *		\brief      Constructeur
	 *		\param		db			Handler d'acc�s base
	 *		\param		conf		Handler de conf
	 *		\param		lang		Handler de langue
	 *		\param		user		Handler du user connect�
	 */
	function modGeneratePassStandard($db, $conf, $langs, $user)
	{
		$this->id = "standard";
		$this->length = 8;

		$this->db=$db;
		$this->conf=$conf;
		$this->langs=$langs;
		$this->user=$user;
	}

	/**
	 *		\brief      Renvoi la description du module
 	 *      \return     string      Texte descripif
	 */
	function getDescription()
	{
		global $langs;
		return $langs->trans("PasswordGenerationStandard");
	}

	/**
	 * 		\brief		Renvoie exemple de mot de passe g�n�r� par cette r�gle
 	 *      \return     string      Exemple
	 */
	function getExample()
	{
		return $this->getNewGeneratedPassword();
	}

	/**
	 * 		\brief		G�n�re le mot de passe
 	 *      \return     string      Renvoi mot de passe g�n�r�
	 */
	function getNewGeneratedPassword()
	{
		// start with a blank password
		$password = "";
		
		// define possible characters
		$possible = "0123456789bcdfghjkmnpqrstvwxyz";
		
		// set up a counter
		$i = 0;

		// add random characters to $password until $length is reached
		while ($i < $this->length)
		{
		
			// pick a random character from the possible ones
			$char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
		
			// we don't want this character if it's already in the password
			if (!strstr($password, $char))
			{
				$password .= $char;
				$i++;
			}
		
		}
		
		// done!
		return $password;
	}

}

?>
