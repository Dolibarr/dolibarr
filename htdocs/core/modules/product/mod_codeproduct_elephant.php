<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2012 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
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
 *       \file       htdocs/core/modules/product/mod_codeproduct_elephant.php
 *       \ingroup    product
 *       \brief      File of class to manage product code with elephant rule
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/product/modules_product.class.php';


/**
 *       \class 		mod_codeproduct_elephant
 *       \brief 		Class to manage product code with elephant rule
 */
class mod_codeproduct_elephant extends ModeleProductCode
{
	var $nom='Elephant';				// Nom du modele
	var $code_modifiable;				// Code modifiable
	var $code_modifiable_invalide;		// Code modifiable si il est invalide
	var $code_modifiable_null;			// Code modifiables si il est null
	var $code_null;						// Code facultatif
	var $version='dolibarr';    		// 'development', 'experimental', 'dolibarr'
	var $code_auto;                     // Numerotation automatique

	var $searchcode; // String de recherche
	var $numbitcounter; // Nombre de chiffres du compteur
	var $prefixIsRequired; // Le champ prefix du tiers doit etre renseigne quand on utilise {pre}


	/**
	 *	Constructor
	 */
	function __construct()
	{
		$this->code_null = 0;
		$this->code_modifiable = 1;
		$this->code_modifiable_invalide = 1;
		$this->code_modifiable_null = 1;
		$this->code_auto = 1;
		$this->prefixIsRequired = 0;
	}


	/**		Return description of module
	 *
	 * 		@param	string 		$langs		Object langs
	 * 		@return string      			Description of module
	 */
	function info($langs)
	{
		global $conf, $mc;
		global $form;

		$langs->load("products");

		$disabled = ((! empty($mc->sharings['referent']) && $mc->sharings['referent'] != $conf->entity) ? ' disabled="disabled"' : '');

		$texte = $langs->trans('GenericNumRefModelDesc')."<br>\n";
		$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$texte.= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte.= '<input type="hidden" name="param1" value="PRODUCT_ELEPHANT_MASK_PRODUCT">';
		$texte.= '<input type="hidden" name="param2" value="PRODUCT_ELEPHANT_MASK_SERVICE">';
		$texte.= '<table class="nobordernopadding" width="100%">';

		$tooltip=$langs->trans("GenericMaskCodes",$langs->transnoentities("Product"),$langs->transnoentities("Product"));
		$tooltip.=$langs->trans("GenericMaskCodes3");
		$tooltip.=$langs->trans("GenericMaskCodes4c");
		$tooltip.=$langs->trans("GenericMaskCodes5");

		// Parametrage du prefix customers
		$texte.= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("ProductCodeModel").'):</td>';
		$texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="value1" value="'.(! empty($conf->global->PRODUCT_ELEPHANT_MASK_PRODUCT)?$conf->global->PRODUCT_ELEPHANT_MASK_PRODUCT:'').'"'.$disabled.'>',$tooltip,1,1).'</td>';

		$texte.= '<td align="left" rowspan="2">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"'.$disabled.'></td>';

		$texte.= '</tr>';

		// Parametrage du prefix suppliers
		$texte.= '<tr><td>'.$langs->trans("Mask").' ('.$langs->trans("ServiceCodeModel").'):</td>';
		$texte.= '<td align="right">'.$form->textwithpicto('<input type="text" class="flat" size="24" name="value2" value="'.(! empty($conf->global->PRODUCT_ELEPHANT_MASK_SERVICE)?$conf->global->PRODUCT_ELEPHANT_MASK_SERVICE:'').'"'.$disabled.'>',$tooltip,1,1).'</td>';
		$texte.= '</tr>';

		$texte.= '</table>';
		$texte.= '</form>';

		return $texte;
	}


	/**
	 * Return an example of result returned by getNextValue
	 *
	 * @param	Translate	$langs		Object langs
	 * @param	product		$objproduct		Object product
	 * @param	int			$type		Type of third party (1:customer, 2:supplier, -1:autodetect)
	 * @return	string					Return string example
	 */
	function getExample($langs,$objproduct=0,$type=-1)
	{
		if ($type == 0 || $type == -1)
		{
			$exampleproduct = $this->getNextValue($objproduct,0);
			if (! $exampleproduct)
			{
				$exampleproduct = $langs->trans('NotConfigured');
			}
			if($exampleproduct=="ErrorBadMask")
			{
				$langs->load("errors");
				$exampleproduct=$langs->trans($exampleproduct);
			}
		}
		if ($type == 1 || $type == -1)
		{
			$exampleservice = $this->getNextValue($objproduct,1);
			if (! $exampleservice)
			{
				$exampleservice = $langs->trans('NotConfigured');
			}
			if($exampleservice=="ErrorBadMask")
			{
				$langs->load("errors");
				$exampleservice=$langs->trans($exampleservice);
			}
		}

		if ($type == 0) return $exampleproduct;
		if ($type == 1) return $exampleservice;
		return $exampleproduct.'<br>'.$exampleservice;
	}

	/**
	 * Return next value
	 *
	 * @param	Product		$objproduct     Object product
	 * @param  	int		    $type       Produit ou service (0:product, 1:service)
	 * @return 	string      			Value if OK, '' if module not configured, <0 if KO
	 */
	function getNextValue($objproduct=0,$type=-1)
	{
		global $db,$conf;

		require_once DOL_DOCUMENT_ROOT .'/core/lib/functions2.lib.php';

		// Get Mask value
		$mask = '';
		if ($type == 0 && ! empty($conf->global->PRODUCT_ELEPHANT_MASK_PRODUCT))
			$mask = $conf->global->PRODUCT_ELEPHANT_MASK_PRODUCT;
		else if ($type == 1 && ! empty($conf->global->PRODUCT_ELEPHANT_MASK_SERVICE))
			$mask = $conf->global->PRODUCT_ELEPHANT_MASK_SERVICE;

		if (empty($mask))
		{
			$this->error='NotConfigured';
			return '';
		}

		$field='';$where='';
		if ($type == 0)
		{
			$field = 'ref';
			//$where = ' AND client in (1,2)';
		}
		else if ($type == 1)
		{
			$field = 'ref';
			//$where = ' AND fournisseur = 1';
		}
		else return -1;

		$now=dol_now();

		$numFinal=get_next_value($db,$mask,'product',$field,$where,'',$now);

		return  $numFinal;
	}


	/**
	 *   Check if mask/numbering use prefix
	 *
	 *   @return	int			0 or 1
	 */
	function verif_prefixIsUsed()
	{
		global $conf;

		$mask = $conf->global->PRODUCT_ELEPHANT_MASK_PRODUCT;
		if (preg_match('/\{pre\}/i',$mask)) return 1;

		$mask = $conf->global->PRODUCT_ELEPHANT_MASK_SERVICE;
		if (preg_match('/\{pre\}/i',$mask)) return 1;

		return 0;
	}


	/**
	 * 	Check validity of code according to its rules
	 *
	 *	@param	DoliDB		$db		Database handler
	 *	@param	string		&$code	Code to check/correct
	 *	@param	Product		$product	Object product
	 *  @param  int		  	$type   0 = customer/prospect , 1 = supplier
	 *  @return int					0 if OK
	 * 								-1 ErrorBadCustomerCodeSyntax
	 * 								-2 ErrorCustomerCodeRequired
	 * 								-3 ErrorCustomerCodeAlreadyUsed
	 * 								-4 ErrorPrefixRequired
	 */
	function verif($db, &$code, $product, $type)
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT .'/core/lib/functions2.lib.php';

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
		else
		{
			// Get Mask value
			$mask = '';
			if ($type==0) $mask = empty($conf->global->PRODUCT_ELEPHANT_MASK_PRODUCT)?'':$conf->global->PRODUCT_ELEPHANT_MASK_PRODUCT;
			if ($type==1) $mask = empty($conf->global->PRODUCT_ELEPHANT_MASK_SSERVICE)?'':$conf->global->PRODUCT_ELEPHANT_MASK_SERVICE;
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
	 *		Renvoi si un code est pris ou non (par autre tiers)
	 *
	 *		@param	DoliDB		$db			Handler acces base
	 *		@param	string		$code		Code a verifier
	 *		@param	Product		$product		Objet product
	 *		@return	int						0 if available, <0 if KO
	 */
	function verif_dispo($db, $code, $product)
	{
		$sql = "SELECT ref FROM ".MAIN_DB_PREFIX."product";
		$sql.= " WHERE ref = '".$code."'";
		if ($product->id > 0) $sql.= " AND rowid <> ".$product->id;

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
