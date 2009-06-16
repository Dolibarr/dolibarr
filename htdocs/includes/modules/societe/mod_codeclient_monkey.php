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
 *       \file       htdocs/includes/modules/societe/mod_codeclient_monkey.class.php
 *       \ingroup    societe
 *       \brief      Fichier de la classe des gestion lion des codes clients
 *       \version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/includes/modules/societe/modules_societe.class.php");


/**
        \class 		mod_codeclient_monkey
        \brief 		Classe permettant la gestion monkey des codes tiers
*/
class mod_codeclient_monkey extends ModeleThirdPartyCode
{
	var $nom;							// Nom du modele
	var $code_modifiable;				// Code modifiable
	var $code_modifiable_invalide;		// Code modifiable si il est invalide
	var $code_modifiable_null;			// Code modifiables si il est null
	var $code_null;						// Code facultatif
	var $version;		// 'development', 'experimental', 'dolibarr'
	var $code_auto; // Numerotation automatique

	var $prefixcustomer='CU';
	var $prefixsupplier='SU';

	/**		\brief      Constructeur classe
	*/
	function mod_codeclient_monkey()
	{
		$this->nom = "Monkey";
		$this->version = "dolibarr";
		$this->code_null = 1;
		$this->code_modifiable = 1;
		$this->code_modifiable_invalide = 1;
		$this->code_modifiable_null = 1;
		$this->code_auto = 1;
	}


	/**		\brief      Renvoi la description du module
	*      	\return     string      Texte descripif
	*/
	function info($langs)
	{
		return $langs->trans("MonkeyNumRefModelDesc",$this->prefixcustomer,$this->prefixsupplier);
	}


	/**		\brief      Renvoi la description du module
	*      	\return     string      Texte descripif
	*/
	function getExample($langs,$objsoc=0,$type=-1)
	{
		return $this->prefixcustomer.'0901-0001<br>'.$this->prefixsupplier.'0901-0001';
	}


	/**		\brief      Return next value
	 *     	\param      objsoc      Object third party
	 *	    \param      $type       Client ou fournisseur (1:client, 2:fournisseur)
	 *     	\return     string      Value if OK, '' if module not configured, <0 if KO
	 */
	function getNextValue($objsoc=0,$type=-1)
	{
		global $db, $conf;

		$return='000001';

		$field='';$where='';
		if ($type == 0)
		{
			$field = 'code_client';
			//$where = ' AND client in (1,2)';
		}
		else if ($type == 1)
		{
			$field = 'code_fournisseur';
			//$where = ' AND fournisseur = 1';
		}
		else return -1;


		if ($type == 0) $prefix=$this->prefixcustomer;
		if ($type == 1) $prefix=$this->prefixsupplier;

        // D'abord on r�cup�re la valeur max (r�ponse imm�diate car champ ind�x�)
        $posindice=8;
        $sql = "SELECT MAX(0+SUBSTRING(".$field.",".$posindice.")) as max";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe";
		$sql.= " WHERE ".$field." LIKE '".$prefix."%'";

        $resql=$db->query($sql);
        if ($resql)
        {
            $obj = $db->fetch_object($resql);
            if ($obj) $max = $obj->max;
            else $max=0;
        }
        else
        {
        	dol_syslog("mod_codeclient_monkey::getNextValue sql=".$sql);
        	return -1;
        }

        //$date=time();
        $date=gmmktime();
        $yymm = strftime("%y%m",$date);
        $num = sprintf("%04s",$max+1);

        dol_syslog("mod_codeclient_monkey::getNextValue return ".$prefix.$yymm."-".$num);
        return $prefix.$yymm."-".$num;
	}


	/**
	* 		\brief		Verifie la validite du code
	*		\param		$db			Handler acces base
	*		\param		$code		Code a verifier/corriger
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
		dol_syslog("mod_codeclient_monkey::verif result=".$result);
		return $result;
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

		if (strlen($code) < 11)
		{
			$res = -1;
		}
		else
		{
			$res = 0;
		}
		return $res;
	}

}

?>
