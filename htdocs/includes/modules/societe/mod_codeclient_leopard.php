<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/includes/modules/societe/mod_codeclient_leopard.php
 *       \ingroup    societe
 *       \brief      Fichier de la classe des gestion leopard des codes clients
 *       \version    $Id: mod_codeclient_leopard.php,v 1.17 2011/07/31 23:28:14 eldy Exp $
 */

require_once(DOL_DOCUMENT_ROOT."/includes/modules/societe/modules_societe.class.php");


/**
 \class 		mod_codeclient_leopard
 \brief 		Classe permettant la gestion leopard des codes tiers
 */
class mod_codeclient_leopard extends ModeleThirdPartyCode
{
	/*
	 * Attention ce module est utilise par defaut si aucun module n'a
	 * ete definit dans la configuration
	 *
	 * Le fonctionnement de celui-ci doit dont rester le plus ouvert possible
	 */

	var $nom;							// Nom du modele
	var $code_modifiable;				// Code modifiable
	var $code_modifiable_invalide;		// Code modifiable si il est invalide
	var $code_modifiable_null;			// Code modifiables si il est null
	var $code_null;						// Code facultatif
	var $version;		// 'development', 'experimental', 'dolibarr'
	var $code_auto; 	// Numerotation automatique


	/**		\brief      Constructeur classe
	 */
	function mod_codeclient_leopard()
	{
		$this->nom = "Leopard";
		$this->version = "dolibarr";
		$this->code_null = 1;
		$this->code_modifiable = 1;
		$this->code_modifiable_invalide = 1;
		$this->code_modifiable_null = 1;
		$this->code_auto = 0;
	}


	/**
	 *		\brief      Renvoie la description du module
	 *		\return     string      Texte descripif
	 */
	function info($langs)
	{
		return $langs->trans("LeopardNumRefModelDesc");
	}


	/**     \brief      Return next value available
	 *      \return     string      Value
	 */
	function getNextValue($objsoc=0,$type=-1)
	{
		global $langs;
		return '';
	}


	/**
	 * 		\brief		Check validity of code according to its rules
	 *		\param		$db			Database handler
	 *		\param		$code		Code to check/correct
	 *		\param		$soc		Object third party
	 *   	\param    	$type   	0 = customer/prospect , 1 = supplier
	 *    	\return     int		0 if OK
	 * 							-1 ErrorBadCustomerCodeSyntax
	 * 							-2 ErrorCustomerCodeRequired
	 * 							-3 ErrorCustomerCodeAlreadyUsed
	 * 							-4 ErrorPrefixRequired
	 */
	function verif($db, &$code, $soc, $type)
	{
		global $conf;

		$result=0;
		$code = strtoupper(trim($code));

		if (empty($code) && $this->code_null && empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED))
		{
			$result=0;
		}
		else if (empty($code) && (! $this->code_null || ! empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED)) )
		{
			$result=-2;
		}

		dol_syslog("mod_codeclient_leopard::verif type=".$type." result=".$result);
		return $result;
	}
}

?>
