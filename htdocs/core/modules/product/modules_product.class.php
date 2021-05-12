<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
<<<<<<< HEAD
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *  \file       htdocs/core/modules/product/modules_product.class.php
 *  \ingroup    contract
 *  \brief      File with parent class for generating products to PDF and File of class to manage product numbering
 */

 require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';

/**
 *	Parent class to manage intervention document templates
 */
abstract class ModelePDFProduct extends CommonDocGenerator
{
<<<<<<< HEAD
	var $error='';


	/**
	 *	Return list of active generation modules
	 *
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
	 */
	static function liste_modeles($db,$maxfilenamelength=0)
	{
		global $conf;

		$type='product';
		$liste=array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$liste=getListOfModels($db,$type,$maxfilenamelength);
		return $liste;
	}
=======
    /**
     * @var string Error code (or message)
     */
    public $error='';


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Return list of active generation modules
     *
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
     */
    public static function liste_modeles($db, $maxfilenamelength = 0)
    {
        // phpcs:enable
        global $conf;

        $type='product';
        $liste=array();

        include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
        $liste=getListOfModels($db, $type, $maxfilenamelength);
        return $liste;
    }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}

abstract class ModeleProductCode
{
<<<<<<< HEAD
    var $error='';
=======
    /**
     * @var string Error code (or message)
     */
    public $error='';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    /**     Renvoi la description par defaut du modele de numerotation
     *
     *		@param	Translate	$langs		Object langs
     *      @return string      			Texte descripif
     */
<<<<<<< HEAD
    function info($langs)
=======
    public function info($langs)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        $langs->load("bills");
        return $langs->trans("NoDescription");
    }

    /**     Renvoi nom module
     *
     *		@param	Translate	$langs		Object langs
     *      @return string      			Nom du module
     */
<<<<<<< HEAD
    function getNom($langs)
=======
    public function getNom($langs)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        return empty($this->name)?$this->nom:$this->name;
    }


    /**     Renvoi un exemple de numerotation
     *
     *		@param	Translate	$langs		Object langs
     *      @return string      			Example
     */
<<<<<<< HEAD
    function getExample($langs)
=======
    public function getExample($langs)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        $langs->load("bills");
        return $langs->trans("NoExample");
    }

    /**     Test si les numeros deja en vigueur dans la base ne provoquent pas de
     *      de conflits qui empechera cette numerotation de fonctionner.
     *
     *      @return     boolean     false si conflit, true si ok
     */
<<<<<<< HEAD
    function canBeActivated()
=======
    public function canBeActivated()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        return true;
    }

    /**
     *  Return next value available
     *
     *	@param	Product		$objproduct		Object product
     *	@param	int			$type		Type
     *  @return string      			Value
     */
<<<<<<< HEAD
    function getNextValue($objproduct=0,$type=-1)
=======
    public function getNextValue($objproduct = 0, $type = -1)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        global $langs;
        return $langs->trans("Function_getNextValue_InModuleNotWorking");
    }


    /**     Return version of module
     *
     *      @return     string      Version
     */
<<<<<<< HEAD
    function getVersion()
=======
    public function getVersion()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') return $langs->trans("VersionDevelopment");
        if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
        if ($this->version == 'dolibarr') return DOL_VERSION;
        if ($this->version) return $this->version;
        return $langs->trans("NotAvailable");
    }

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     *  Renvoi la liste des modeles de numérotation
     *
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of numbers
     */
<<<<<<< HEAD
    static function liste_modeles($db,$maxfilenamelength=0)
    {
=======
    public static function liste_modeles($db, $maxfilenamelength = 0)
    {
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $liste=array();
        $sql ="";

        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $row = $db->fetch_row($resql);
                $liste[$row[0]]=$row[1];
                $i++;
            }
        }
        else
        {
            return -1;
        }
        return $liste;
    }

    /**
<<<<<<< HEAD
     *      Return description of module parameters
     *
     *      @param	Translate	$langs      Output language
     *		@param	Product		$product	Product object
     *		@param	int			$type		-1=Nothing, 0=Customer, 1=Supplier
     *		@return	string					HTML translated description
     */
    function getToolTip($langs,$product,$type)
=======
     *  Return description of module parameters
     *
     *  @param	Translate	$langs      Output language
     *  @param	Product		$product	Product object
     *  @param	int			$type		-1=Nothing, 0=Customer, 1=Supplier
     *  @return	string					HTML translated description
     */
    public function getToolTip($langs, $product, $type)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        global $conf;

        $langs->load("admin");

        $s='';
<<<<<<< HEAD
        if ($type == -1) $s.=$langs->trans("Name").': <b>'.$this->getNom($langs).'</b><br>';
        if ($type == -1) $s.=$langs->trans("Version").': <b>'.$this->getVersion().'</b><br>';
=======
        if ($type == -1) {
            $s.=$langs->trans("Name").': <b>'.$this->getNom($langs).'</b><br>';
            $s.=$langs->trans("Version").': <b>'.$this->getVersion().'</b><br>';
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        if ($type == 0)  $s.=$langs->trans("ProductCodeDesc").'<br>';
        if ($type == 1)  $s.=$langs->trans("ServiceCodeDesc").'<br>';
        if ($type != -1) $s.=$langs->trans("ValidityControledByModule").': <b>'.$this->getNom($langs).'</b><br>';
        $s.='<br>';
        $s.='<u>'.$langs->trans("ThisIsModuleRules").':</u><br>';
        if ($type == 0)
        {
            $s.=$langs->trans("RequiredIfProduct").': ';
            if (! empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='<strike>';
<<<<<<< HEAD
            $s.=yn(!$this->code_null,1,2);
            if (! empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='</strike> '.yn(1,1,2).' ('.$langs->trans("ForcedToByAModule",$langs->transnoentities("yes")).')';
            $s.='<br>';
        }
        if ($type == 1)
        {
            $s.=$langs->trans("RequiredIfService").': ';
            if (! empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='<strike>';
            $s.=yn(!$this->code_null,1,2);
            if (! empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='</strike> '.yn(1,1,2).' ('.$langs->trans("ForcedToByAModule",$langs->transnoentities("yes")).')';
            $s.='<br>';
        }
        if ($type == -1)
        {
            $s.=$langs->trans("Required").': ';
            if (! empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='<strike>';
            $s.=yn(!$this->code_null,1,2);
            if (! empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='</strike> '.yn(1,1,2).' ('.$langs->trans("ForcedToByAModule",$langs->transnoentities("yes")).')';
            $s.='<br>';
        }
        $s.=$langs->trans("CanBeModifiedIfOk").': ';
        $s.=yn($this->code_modifiable,1,2);
        $s.='<br>';
        $s.=$langs->trans("CanBeModifiedIfKo").': '.yn($this->code_modifiable_invalide,1,2).'<br>';
        $s.=$langs->trans("AutomaticCode").': '.yn($this->code_auto,1,2).'<br>';
        $s.='<br>';
        if ($type == 0 || $type == -1)
        {
            $nextval=$this->getNextValue($product,0);
=======
            $s.=yn(!$this->code_null, 1, 2);
            if (! empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='</strike> '.yn(1, 1, 2).' ('.$langs->trans("ForcedToByAModule", $langs->transnoentities("yes")).')';
            $s.='<br>';
        }
        elseif ($type == 1)
        {
            $s.=$langs->trans("RequiredIfService").': ';
            if (! empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='<strike>';
            $s.=yn(!$this->code_null, 1, 2);
            if (! empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='</strike> '.yn(1, 1, 2).' ('.$langs->trans("ForcedToByAModule", $langs->transnoentities("yes")).')';
            $s.='<br>';
        }
        elseif ($type == -1)
        {
            $s.=$langs->trans("Required").': ';
            if (! empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='<strike>';
            $s.=yn(!$this->code_null, 1, 2);
            if (! empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='</strike> '.yn(1, 1, 2).' ('.$langs->trans("ForcedToByAModule", $langs->transnoentities("yes")).')';
            $s.='<br>';
        }
        $s.=$langs->trans("CanBeModifiedIfOk").': ';
        $s.=yn($this->code_modifiable, 1, 2);
        $s.='<br>';
        $s.=$langs->trans("CanBeModifiedIfKo").': '.yn($this->code_modifiable_invalide, 1, 2).'<br>';
        $s.=$langs->trans("AutomaticCode").': '.yn($this->code_auto, 1, 2).'<br>';
        $s.='<br>';
        if ($type == 0 || $type == -1)
        {
            $nextval=$this->getNextValue($product, 0);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            if (empty($nextval)) $nextval=$langs->trans("Undefined");
            $s.=$langs->trans("NextValue").($type == -1?' ('.$langs->trans("Product").')':'').': <b>'.$nextval.'</b><br>';
        }
        if ($type == 1 || $type == -1)
        {
<<<<<<< HEAD
            $nextval=$this->getNextValue($product,1);
=======
            $nextval=$this->getNextValue($product, 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            if (empty($nextval)) $nextval=$langs->trans("Undefined");
            $s.=$langs->trans("NextValue").($type == -1?' ('.$langs->trans("Service").')':'').': <b>'.$nextval.'</b>';
        }
        return $s;
    }

<<<<<<< HEAD
	/**
	 *   Check if mask/numbering use prefix
	 *
	 *   @return	int		0=no, 1=yes
	 */
    function verif_prefixIsUsed()
    {
        return 0;
    }

}

=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *   Check if mask/numbering use prefix
     *
     *   @return	int		0=no, 1=yes
     */
    public function verif_prefixIsUsed()
    {
        // phpcs:enable
        return 0;
    }
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
