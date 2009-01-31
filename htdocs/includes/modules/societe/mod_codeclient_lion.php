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
 */

/**
 *       \file       htdocs/includes/modules/societe/mod_codeclient_lion.class.php
 *       \ingroup    societe
 *       \brief      Fichier de la classe des gestion lion des codes clients
 *       \version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/includes/modules/societe/modules_societe.class.php");


/**
        \class 		mod_codeclient_lion
        \brief 		Classe permettant la gestion lion des codes tiers
*/
class mod_codeclient_lion extends ModeleThirdPartyCode
{
	var $nom;							// Nom du modele
	var $code_modifiable;				// Code modifiable
	var $code_modifiable_invalide;		// Code modifiable si il est invalide
	var $code_modifiable_null;			// Code modifiables si il est null
	var $code_null;						// Code facultatif
	var $version;		// 'development', 'experimental', 'dolibarr'
	var $code_auto; // Numerotation automatique


	/**		\brief      Constructeur classe
	*/
	function mod_codeclient_lion()
	{
		$this->nom = "Lion";
		$this->version = "dolibarr";
		$this->code_modifiable = 0;
		$this->code_modifiable_invalide = 1;
		$this->code_modifiable_null = 1;
		$this->code_null = 0;
		$this->code_auto = 0;
	}


	/**		\brief      Renvoi la description du module
	*      	\return     string      Texte descripif
	*/
	function info($langs)
	{
		return "Verifie si le code client/fournisseur est de la forme numerique 999 et sur au moins 3 chiffres. Verification mais pas de generation automatique.";
	}


	/**		\brief      Renvoi la description du module
	*      	\return     string      Texte descripif
	*/
	function getExample($langs)
	{
		return "001";
	}


	/**
	* 		\brief		V�rifie la validit� du code
	*		\param		$db			Handler acces base
	*		\param		$code		Code a v�rifier/corriger
	*		\param		$soc		Objet societe
	*		\return		int			<0 si KO, 0 si OK
	*/
	function verif($db, &$code, $soc)
	{
		$result=0;
		$code = strtoupper(trim($code));

		if (! $code && $this->code_null)
		{
			$result=0;
		}
		else
		{
			if ($this->verif_syntax($code) >= 0)
			{
				$is_dispo = $this->verif_dispo($db, $code, $soc);
				if ($is_dispo <> 0)
				{
					$result=-3;
				}
				else
				{
					$result=0;
				}
			}
			else
			{
				if (strlen($code) == 0)
				{
					$result=-2;
				}
				else
				{
					$result=-1;
				}
			}
		}
		dolibarr_syslog("mod_codeclient_lion::verif result=".$result);
		return $result;
	}


	/**
	*		\brief		Renvoi une valeur correcte
	*		\param		$db			Handler acces base
	*		\param		$code		Code reference eventuel
	*		\return		string		Code correct, <0 si KO
	*/
	function get_correct($db, $code)
	{
		$return='001';

		$sql = "SELECT MAX(code_client) as maxval FROM ".MAIN_DB_PREFIX."societe";
		$resql=$db->query($sql);
		if ($resql)
		{
			$obj=$db->fetch_object($resql);
			if ($obj)
			{
				$newval=$obj->maxval+1;
				$return=sprintf('%03d',$newval);
				return $return;
			}
		}
		else
		{
			return -1;
		}
	}


	/**
	*		\brief		Renvoi si un code est pris ou non (par autre tiers)
	*		\param		$db			Handler acces base
	*		\param		$code		Code a verifier
	*		\param		$soc		Objet societe
	*		\return		int			0 si dispo, <0 si erreur
	*/
	function verif_dispo($db, $code, $soc)
	{
		$sql = "SELECT code_client FROM ".MAIN_DB_PREFIX."societe";
		$sql.= " WHERE code_client = '".$code."'";
		$sql.= " AND rowid != '".$soc->id."'";

		$resql=$db->query($sql);
		if ($resql)
		{
			if ($db->num_rows($resql) == 0)
			{
				return 0;
			}
			else
			{
				return -1;
			}
		}
		else
		{
			return -2;
		}

	}


	/**
	*	\brief		Renvoi si un code respecte la syntaxe
	*	\param		$code		Code a verifier
	*	\return		int			0 si OK, <0 si KO
	*/
	function verif_syntax($code)
	{
		$res = 0;

		if (strlen($code) < 3)
		{
			$res = -1;
		}
		else
		{
			if (eregi('[0-9][0-9][0-9]+',$code))
			{
				$res = 0;
			}
			else
			{
				$res = -2;
			}

		}
		return $res;
	}


	/**
	*	Renvoi 0 si numerique, sinon renvoi nb de car non numerique
	*/
	function is_num($str)
	{
		$ok = 0;

		$alpha = '0123456789';

		for ($i = 0 ; $i < length($str) ; $i++)
		{
			if (strpos($alpha, substr($str,$i, 1)) === false)
			{
				$ok++;
			}
		}

		return $ok;
	}

}

?>
