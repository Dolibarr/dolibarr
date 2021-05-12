<?php
/* Copyright (C) 2014 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   \file       htdocs/core/modules/barcode/modules_barcode.class.php
 *   \ingroup    barcode
 *   \brief      File with parent classes for barcode document modules and numbering modules
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';


/**
 *	Parent class for barcode document models
 */
abstract class ModeleBarCode
{
<<<<<<< HEAD
	var $error='';


	/**
	 * Return if a model can be used or not
	 *
	 * @return		boolean     true if model can be used
	 */
	function isEnabled()
	{
		return true;
	}

=======
    /**
     * @var string Error code (or message)
     */
    public $error='';


    /**
     * Return if a model can be used or not
     *
     * @return		boolean     true if model can be used
     */
    public function isEnabled()
    {
        return true;
    }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}


/**
 *	Parent class for barcode numbering models
 */
abstract class ModeleNumRefBarCode
{
<<<<<<< HEAD
	var $error='';
=======
    /**
     * @var string Error code (or message)
     */
    public $error='';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    /**     Return default description of numbering model
     *
     *		@param	Translate	$langs		Object langs
     *      @return string      			Descriptive text
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

    /**     Return model name
     *
     *		@param	Translate	$langs		Object langs
     *      @return string      			Model name
     */
<<<<<<< HEAD
    function getNom($langs)
=======
    public function getNom($langs)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        return empty($this->name)?$this->nom:$this->name;
    }

    /**     Return a numbering example
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

    /**
     *  Return next value available
     *
     *	@param	Product		$objproduct	Object Product
     *	@param	string		$type		Type of barcode (EAN, ISBN, ...)
     *  @return string      			Value
     */
<<<<<<< HEAD
    function getNextValue($objproduct,$type='')
=======
    public function getNextValue($objproduct, $type = '')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        global $langs;
        return $langs->trans("Function_getNextValue_InModuleNotWorking");
    }

<<<<<<< HEAD
	/**     Return version of module
     *
     *      @return     string      Version
     */
    function getVersion()
=======
    /**     Return version of module
     *
     *      @return     string      Version
     */
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

    /**
     *      Return description of module parameters
     *
     *      @param	Translate	$langs      Output language
     *		@param	Societe		$soc		Third party object
     *		@param	int			$type		-1=Nothing, 0=Product, 1=Service
     *		@return	string					HTML translated description
     */
<<<<<<< HEAD
    function getToolTip($langs,$soc,$type)
=======
    public function getToolTip($langs, $soc, $type)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        global $conf;

        $langs->load("admin");

        $s='';
        $s.=$langs->trans("Name").': <b>'.$this->name.'</b><br>';
        $s.=$langs->trans("Version").': <b>'.$this->getVersion().'</b><br>';
        if ($type != -1) $s.=$langs->trans("ValidityControledByModule").': <b>'.$this->getNom($langs).'</b><br>';
        $s.='<br>';
        $s.='<u>'.$langs->trans("ThisIsModuleRules").':</u><br>';
        if ($type == 0)
        {
            $s.=$langs->trans("RequiredIfProduct").': ';
            if (! empty($conf->global->MAIN_BARCODE_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='<strike>';
<<<<<<< HEAD
            $s.=yn(!$this->code_null,1,2);
            if (! empty($conf->global->MAIN_BARCODE_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='</strike> '.yn(1,1,2).' ('.$langs->trans("ForcedToByAModule",$langs->transnoentities("yes")).')';
=======
            $s.=yn(!$this->code_null, 1, 2);
            if (! empty($conf->global->MAIN_BARCODE_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='</strike> '.yn(1, 1, 2).' ('.$langs->trans("ForcedToByAModule", $langs->transnoentities("yes")).')';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $s.='<br>';
        }
        if ($type == 1)
        {
            $s.=$langs->trans("RequiredIfService").': ';
            if (! empty($conf->global->MAIN_BARCODE_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='<strike>';
<<<<<<< HEAD
            $s.=yn(!$this->code_null,1,2);
            if (! empty($conf->global->MAIN_BARCODE_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='</strike> '.yn(1,1,2).' ('.$langs->trans("ForcedToByAModule",$langs->transnoentities("yes")).')';
=======
            $s.=yn(!$this->code_null, 1, 2);
            if (! empty($conf->global->MAIN_BARCODE_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='</strike> '.yn(1, 1, 2).' ('.$langs->trans("ForcedToByAModule", $langs->transnoentities("yes")).')';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $s.='<br>';
        }
        if ($type == -1)
        {
            $s.=$langs->trans("Required").': ';
            if (! empty($conf->global->MAIN_BARCODE_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='<strike>';
<<<<<<< HEAD
            $s.=yn(!$this->code_null,1,2);
            if (! empty($conf->global->MAIN_BARCODE_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='</strike> '.yn(1,1,2).' ('.$langs->trans("ForcedToByAModule",$langs->transnoentities("yes")).')';
=======
            $s.=yn(!$this->code_null, 1, 2);
            if (! empty($conf->global->MAIN_BARCODE_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='</strike> '.yn(1, 1, 2).' ('.$langs->trans("ForcedToByAModule", $langs->transnoentities("yes")).')';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $s.='<br>';
        }
        /*$s.=$langs->trans("CanBeModifiedIfOk").': ';
        $s.=yn($this->code_modifiable,1,2);
        $s.='<br>';
        $s.=$langs->trans("CanBeModifiedIfKo").': '.yn($this->code_modifiable_invalide,1,2).'<br>';
        */
<<<<<<< HEAD
        $s.=$langs->trans("AutomaticCode").': '.yn($this->code_auto,1,2).'<br>';
        $s.='<br>';

        $nextval=$this->getNextValue($soc,'');
=======
        $s.=$langs->trans("AutomaticCode").': '.yn($this->code_auto, 1, 2).'<br>';
        $s.='<br>';

        $nextval=$this->getNextValue($soc, '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        if (empty($nextval)) $nextval=$langs->trans("Undefined");
        $s.=$langs->trans("NextValue").': <b>'.$nextval.'</b><br>';

        return $s;
    }
<<<<<<< HEAD

}

=======
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
