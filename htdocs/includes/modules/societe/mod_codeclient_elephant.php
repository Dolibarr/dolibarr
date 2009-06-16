<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Regis Houssin        <regis@dolibarr.fr>
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
 *       \file       htdocs/includes/modules/societe/mod_codeclient_elephant.class.php
 *       \ingroup    societe
 *       \brief      File of class to manage third party code with elephant rule
 *       \version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/includes/modules/societe/modules_societe.class.php");


/**
 *       \class 		mod_codeclient_elephant
 *       \brief 		Class to manage third party code with elephant rule
 */
class mod_codeclient_elephant extends ModeleThirdPartyCode
{
	var $nom;							// Nom du modele
	var $code_modifiable;				// Code modifiable
	var $code_modifiable_invalide;		// Code modifiable si il est invalide
	var $code_modifiable_null;			// Code modifiables si il est null
	var $code_null;						// Code facultatif
	var $version;		// 'development', 'experimental', 'dolibarr'
	var $code_auto; // Numerotation automatique

	var $searchcode; // String de recherche
	var $numbitcounter; // Nombre de chiffres du compteur
	var $prefixIsRequired; // Le champ prefix du tiers doit etre renseigne quand on utilise {pre}


	/**		\brief      Constructeur classe
	 */
	function mod_codeclient_elephant()
	{
		$this->nom = "Elephant";
		$this->version = "dolibarr";
		$this->code_null = 0;
		$this->code_modifiable = 1;
		$this->code_modifiable_invalide = 1;
		$this->code_modifiable_null = 1;
		$this->code_auto = 1;
		$this->prefixIsRequired = 0;
	}


	/**		\brief      Renvoi la description du module
	 *      	\return     string      Texte descripif
	 */
	function info($langs)
	{
		global $conf,$langs;

		$langs->load("companies");

		$form = new Form($db);

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$texte.= '<input type="hidden" name="action" value="updateMask">';
		$texte.= '<input type="hidden" name="maskcustomer" value="COMPANY_ELEPHANT_MASK_CUSTOMER">';
		$texte.= '<input type="hidden" name="masksupplier" value="COMPANY_ELEPHANT_MASK_SUPPLIER">';
		$texte.= '<table class="nobordernopadding" width="100%">';

		$tooltip=$langs->trans("GenericMaskCodes",$langs->transnoentities("ThirdParty"));
		//$tooltip.=$langs->trans("GenericMaskCodes2");	Not required for third party numbering
		$tooltip.=$langs->trans("GenericMaskCodes3");
		$tooltip.=$langs->trans("GenericMaskCodes4b");
		$tooltip.=$langs->trans("GenericMaskCodes5");

		// Parametrage du prefix customers
		$texte.= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("CustomerCodeModel").'):</td>';
		$texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="maskcustomer" value="'.$conf->global->COMPANY_ELEPHANT_MASK_CUSTOMER.'">',$tooltip,1,1).'</td>';

		$texte.= '<td align="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';

		$texte.= '</tr>';

		// Parametrage du prefix suppliers
		$texte.= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("SupplierCodeModel").'):</td>';
		$texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="masksupplier" value="'.$conf->global->COMPANY_ELEPHANT_MASK_SUPPLIER.'">',$tooltip,1,1).'</td>';
		$texte.= '</tr>';

		$texte.= '</table>';
		$texte.= '</form>';

		return $texte;
	}


	/**	   \brief      Return an example of number value
	 *     \param      $type       Client ou fournisseur (1:client, 2:fournisseur)
	 *     \return     string      Texte descripif
	 */
	function getExample($langs,$objsoc=0,$type=-1)
	{
		if ($type == 0 || $type == -1)
		{
			$examplecust = $this->getNextValue($objsoc,0);
			if (! $examplecust)
			{
				$examplecust = $langs->trans('NotConfigured');
			}
		}
		if ($type == 1 || $type == -1)
		{
			$examplesup = $this->getNextValue($objsoc,1);
			if (! $examplesup)
			{
				$examplesup = $langs->trans('NotConfigured');
			}
		}

		if ($type == 0) return $examplecust;
		if ($type == 1) return $examplesup;
		return $examplecust.'<br>'.$examplesup;
	}

	/**		\brief      Return next value
	 *     	\param      objsoc      Object third party
	 *	    \param      $type       Client ou fournisseur (1:client, 2:fournisseur)
	 *     	\return     string      Value if OK, '' if module not configured, <0 if KO
	 */
	function getNextValue($objsoc=0,$type=-1)
	{
		global $db,$conf;

		require_once(DOL_DOCUMENT_ROOT ."/lib/functions2.lib.php");

		// Get Mask value
		$mask = '';
		if ($type==0) $mask = $conf->global->COMPANY_ELEPHANT_MASK_CUSTOMER;
		if ($type==1) $mask = $conf->global->COMPANY_ELEPHANT_MASK_SUPPLIER;
		if (! $mask)
		{
			$this->error='NotConfigured';
			return '';
		}

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

		$now=gmmktime();

		$numFinal=get_next_value($db,$mask,'societe',$field,$where,'',$now);

		return  $numFinal;
	}


	/**
	 *   \brief  Verifie si le mask utilise le prefix
	 *
	 */
	function verif_prefixIsUsed()
	{
		global $conf;

		$mask = $conf->global->COMPANY_ELEPHANT_MASK_CUSTOMER;
		if (eregi('\{pre\}',$mask)) return 1;

		$mask = $conf->global->COMPANY_ELEPHANT_MASK_SUPPLIER;
		if (eregi('\{pre\}',$mask)) return 1;

		return 0;
	}


	/**
	 * 		\brief		Verifie la validite du code
	 *		\param		$db			Handler acces base
	 *		\param		$code		Code a verifier/corriger
	 *		\param		$soc		Objet societe
	 *   	\param    	$type   	0 = client/prospect , 1 = fournisseur
	 *		\return		int			<0 if KO, 0 if OK
	 */
	function verif($db, &$code, $soc, $type)
	{
		global $conf;

		require_once(DOL_DOCUMENT_ROOT ."/lib/functions2.lib.php");

		$result=0;
		$code = strtoupper(trim($code));

		if (! $code && $this->code_null)
		{
			$result=0;
		}
		else
		{
			// Get Mask value
			$mask = '';
			if ($type==0) $mask = $conf->global->COMPANY_ELEPHANT_MASK_CUSTOMER;
			if ($type==1) $mask = $conf->global->COMPANY_ELEPHANT_MASK_SUPPLIER;
			if (! $mask)
			{
				$this->error='NotConfigured';
				return '';
			}

			$result=check_value($mask,$code);
		}

		dol_syslog("mod_codeclient_elephant::verif type=".$type." result=".$result);
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

}

?>
