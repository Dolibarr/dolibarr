<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */
 
/**
        \file       htdocs/includes/modules/societe/mod_codeclient_leopard.class.php
        \ingroup    societe
        \brief      Fichier de la classe des gestion leopard des codes clients
        \version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT."/includes/modules/societe/modules_societe.class.php");


/**
        \class 		mod_codeclient_leopard
        \brief 		Classe permettant la gestion leopard des codes tiers
*/
class mod_codeclient_leopard extends ModeleThirdPartyCode
{
	/*
	* Attention ce module est utilisé par défaut si aucun module n'a 
	* été définit dans la configuration
	*
	* Le fonctionnement de celui-ci doit dont rester le plus ouvert
	* possible
	*/

	var $nom;							// Nom du modele
	var $code_modifiable;				// Code modifiable
	var $code_modifiable_invalide;		// Code modifiable si il est invalide
	var $code_modifiable_null;			// Code modifiables si il est null
	var $code_null;						// Code facultatif
	var $version;		// 'development', 'experimental', 'dolibarr'
	var $code_auto; // Numérotation automatique

	
	/**		\brief      Constructeur classe
	*/
	function mod_codeclient_leopard()
	{
		$this->nom = "Léopard";
		$this->version = "dolibarr";
		$this->code_modifiable = 1;
		$this->code_modifiable_invalide = 1;
		$this->code_modifiable_null = 1;
		$this->code_null = 1;
		$this->code_auto = 0;
	}

	
	/**
	*		\brief      Renvoie la description du module
	*		\return     string      Texte descripif
	*/
	function info($langs)
	{
		return "Renvoie toujours ok, pour ceux qui ne veulent pas faire de test.";
	}

	
	/**
	* 		\brief		Vérifie la validité du code
	*		\param		$db			Handler acces base
	*		\param		$code		Code a vérifier
	*		\param		$soc		Objet societe
	*/
	function verif($db, $code, $soc)
	{
		$code = strtoupper(trim($code));

		// Renvoie toujours ok
		return 0;
	}
}

?>
