<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	    \file       htdocs/core/modules/societe/modules_societe.class.php
 *		\ingroup    societe
 *		\brief      File with parent class of submodules to manage numbering and document generation
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';


/**
 *	\class      ModeleThirdPartyDoc
 *	\brief      Parent class for third parties models of doc generators
 */
abstract class ModeleThirdPartyDoc extends CommonDocGenerator
{
    var $error='';

    /**
     *  Return list of active generation modules
     *
	 * 	@param	DoliDB		$db					Database handler
     *  @param	integer		$maxfilenamelength  Max length of value to show
     * 	@return	array							List of templates
     */
    static function liste_modeles($db,$maxfilenamelength=0)
    {
        global $conf;

        $type='company';
        $liste=array();

        include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
        $liste=getListOfModels($db,$type,$maxfilenamelength);

        return $liste;
    }

}

/**
 *	    \class      ModeleThirdPartyCode
 *		\brief  	Parent class for third parties code generators
 */
abstract class ModeleThirdPartyCode
{
    var $error='';

    /**     Renvoi la description par defaut du modele de numerotation
     *
     *		@param	Translate	$langs		Object langs
     *      @return string      			Texte descripif
     */
    function info($langs)
    {
        $langs->load("bills");
        return $langs->trans("NoDescription");
    }

    /**     Renvoi nom module
     *
     *		@param	Translate	$langs		Object langs
     *      @return string      			Nom du module
     */
    function getNom($langs)
    {
        return $this->nom;
    }


    /**     Renvoi un exemple de numerotation
     *
     *		@param	Translate	$langs		Object langs
     *      @return string      			Example
     */
    function getExample($langs)
    {
        $langs->load("bills");
        return $langs->trans("NoExample");
    }

    /**     Test si les numeros deja en vigueur dans la base ne provoquent pas de
     *      de conflits qui empechera cette numerotation de fonctionner.
     *
     *      @return     boolean     false si conflit, true si ok
     */
    function canBeActivated()
    {
        return true;
    }

    /**
     *  Return next value available
     *
     *	@param	Societe		$objsoc		Object thirdparty
     *	@param	int			$type		Type
     *  @return string      			Value
     */
    function getNextValue($objsoc=0,$type=-1)
    {
        global $langs;
        return $langs->trans("Function_getNextValue_InModuleNotWorking");
    }


    /**     Return version of module
     *
     *      @return     string      Version
     */
    function getVersion()
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
     *  Renvoi la liste des modeles de numéroation
     *
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of numbers
     */
    static function liste_modeles($db,$maxfilenamelength=0)
    {
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
     *      Return description of module parameters
     *
     *      @param	Translate	$langs      Output language
     *		@param	Societe		$soc		Third party object
     *		@param	int			$type		-1=Nothing, 0=Customer, 1=Supplier
     *		@return	string					HTML translated description
     */
    function getToolTip($langs,$soc,$type)
    {
        global $conf;

        $langs->load("admin");

        $s='';
        if ($type == -1) $s.=$langs->trans("Name").': <b>'.$this->getNom($langs).'</b><br>';
        if ($type == -1) $s.=$langs->trans("Version").': <b>'.$this->getVersion().'</b><br>';
        if ($type == 0)  $s.=$langs->trans("CustomerCodeDesc").'<br>';
        if ($type == 1)  $s.=$langs->trans("SupplierCodeDesc").'<br>';
        if ($type != -1) $s.=$langs->trans("ValidityControledByModule").': <b>'.$this->getNom($langs).'</b><br>';
        $s.='<br>';
        $s.='<u>'.$langs->trans("ThisIsModuleRules").':</u><br>';
        if ($type == 0)
        {
            $s.=$langs->trans("RequiredIfCustomer").': ';
            if (! empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='<strike>';
            $s.=yn(!$this->code_null,1,2);
            if (! empty($conf->global->MAIN_COMPANY_CODE_ALWAYS_REQUIRED) && ! empty($this->code_null)) $s.='</strike> '.yn(1,1,2).' ('.$langs->trans("ForcedToByAModule",$langs->transnoentities("yes")).')';
            $s.='<br>';
        }
        if ($type == 1)
        {
            $s.=$langs->trans("RequiredIfSupplier").': ';
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
            $nextval=$this->getNextValue($soc,0);
            if (empty($nextval)) $nextval=$langs->trans("Undefined");
            $s.=$langs->trans("NextValue").($type == -1?' ('.$langs->trans("Customer").')':'').': <b>'.$nextval.'</b><br>';
        }
        if ($type == 1 || $type == -1)
        {
            $nextval=$this->getNextValue($soc,1);
            if (empty($nextval)) $nextval=$langs->trans("Undefined");
            $s.=$langs->trans("NextValue").($type == -1?' ('.$langs->trans("Supplier").')':'').': <b>'.$nextval.'</b>';
        }
        return $s;
    }

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


/**
 *		\class		ModeleAccountancyCode
 *		\brief  	Parent class for third parties accountancy code generators
 */
abstract class ModeleAccountancyCode
{
    var $error='';


    /**		Return description of module
     *
     * 		@param	Translate	$langs		Object langs
     * 		@return string      			Description of module
     */
    function info($langs)
    {
        $langs->load("bills");
        return $langs->trans("NoDescription");
    }

    /**		Return an example of result returned by getNextValue
     *
     *      @param	Translate	$langs		Object langs
     *      @param	societe		$objsoc		Object thirdparty
     *      @param	int			$type		Type of third party (1:customer, 2:supplier, -1:autodetect)
     *      @return	string					Example
     */
    function getExample($langs,$objsoc=0,$type=-1)
    {
        $langs->load("bills");
        return $langs->trans("NoExample");
    }

    /**     Test si les numeros deja en vigueur dans la base ne provoquent pas de
     *      de conflits qui empechera cette numerotation de fonctionner.
     *
     *      @return     boolean     false si conflit, true si ok
     */
    function canBeActivated()
    {
        return true;
    }

    /**     Return version of module
     *
     *      @return     string      Version
     */
    function getVersion()
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
     *		@param	int			$type		-1=Nothing, 0=Customer, 1=Supplier
     *		@return	string					HTML translated description
     */
    function getToolTip($langs,$soc,$type)
    {
        global $conf,$db;

        $langs->load("admin");

        $s='';
        if ($type == -1) $s.=$langs->trans("Name").': <b>'.$this->nom.'</b><br>';
        if ($type == -1) $s.=$langs->trans("Version").': <b>'.$this->getVersion().'</b><br>';
        //$s.='<br>';
        //$s.='<u>'.$langs->trans("ThisIsModuleRules").':</u><br>';
        $s.='<br>';
        if ($type == 0 || $type == -1)
        {
            $result=$this->get_code($db,$soc,'customer');
            $nextval=$this->code;
            if (empty($nextval)) $nextval=$langs->trans("Undefined");
            $s.=$langs->trans("NextValue").($type == -1?' ('.$langs->trans("Customer").')':'').': <b>'.$nextval.'</b><br>';
        }
        if ($type == 1 || $type == -1)
        {
            $result=$this->get_code($db,$soc,'supplier');
            $nextval=$this->code;
            if (empty($nextval)) $nextval=$langs->trans("Undefined");
            $s.=$langs->trans("NextValue").($type == -1?' ('.$langs->trans("Supplier").')':'').': <b>'.$nextval.'</b>';
        }
        return $s;
    }

    /**
     *  Set accountancy account code for a third party into this->code
     *
     *  @param	DoliDB	$db             Database handler
     *  @param  Societe	$societe        Third party object
     *  @param  int		$type			'customer' or 'supplier'
     *  @return	int						>=0 if OK, <0 if KO
     */
    function get_code($db, $societe, $type='')
    {
	    global $langs;

        return $langs->trans("NotAvailable");
    }
}



/**
 *  Create a document onto disk according to template module.
 *
 *	@param	DoliDB		$db  			Database handler
 *	@param  Facture		$object			Object invoice
 *  @param  string      $message        Message (not used, deprecated)
 *	@param	string		$modele			Force template to use ('' to not force)
 *	@param	Translate	$outputlangs	objet lang a utiliser pour traduction
 *  @param  int			$hidedetails    Hide details of lines
 *  @param  int			$hidedesc       Hide description
 *  @param  int			$hideref        Hide ref
 *	@return int        					<0 if KO, >0 if OK
 *  @deprecated Use the new function generateDocument of Facture class
 *  @see Societe::generateDocument()
 */
function thirdparty_doc_create(DoliDB $db, Societe $object, $message, $modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
{
	dol_syslog(__METHOD__ . " is deprecated", LOG_WARNING);

	return $object->generateDocument($modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
}
