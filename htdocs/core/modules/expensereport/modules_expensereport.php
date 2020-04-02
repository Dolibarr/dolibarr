<?php
/* Copyright (C) 2015 Laurent Destailleur    <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';


/**
 *	Parent class for trips and expenses templates
 */
abstract class ModeleExpenseReport extends CommonDocGenerator
{
	/**
	 * @var string Error code (or message)
	 */
	public $error='';


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active models generation
     *
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
     */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
        // phpcs:enable
		global $conf;

		$type='expensereport';
		$list=array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$list = getListOfModels($db, $type, $maxfilenamelength);

		return $list;
	}
}

/**
 * expensereport_pdf_create
 *
 *  @param	    DoliDB		$db  			Database handler
 *  @param	    ExpenseReport	$object		Object ExpenseReport
 *  @param		string		$message		Message
 *  @param	    string		$modele			Force the model to use ('' to not force)
 *  @param		Translate	$outputlangs	lang object to use for translation
 *  @param      int			$hidedetails    Hide details of lines
 *  @param      int			$hidedesc       Hide description
 *  @param      int			$hideref        Hide ref
 *  @return     int         				0 if KO, 1 if OK
 */
function expensereport_pdf_create(DoliDB $db, ExpenseReport $object, $message, $modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
{
    return $object->generateDocument($modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
}

/**
 *  \class      ModeleNumRefExpenseReport
 *  \brief      Parent class for numbering masks of expense reports
 */

abstract class ModeleNumRefExpenseReport
{
	/**
	 * @var string Error code (or message)
	 */
	public $error='';

	/**
	 *	Return if a model can be used or not
	 *
	 *	@return		boolean     true if model can be used
	 */
    public function isEnabled()
	{
		return true;
	}

	/**
	 *	Returns the default description of the numbering model
	 *
	 *	@return     string      Descriptive text
	 */
    public function info()
	{
		global $langs;
		$langs->load("orders");
		return $langs->trans("NoDescription");
	}

	/**
	 *	Returns an example of numbering
	 *
	 *	@return     string      Example
	 */
    public function getExample()
	{
		global $langs;
		$langs->load("trips");
		return $langs->trans("NoExample");
	}

	/**
	 *	Test whether the numbers already in force in the base do not cause conflicts that would prevent this numbering from working.
	 *
	 *	@return     boolean     false if conflict, true if ok
	 */
    public function canBeActivated()
	{
		return true;
	}

	/**
	 *	Returns next assigned value
	 *
	 *	@param	Object		$object		Object we need next value for
	 *	@return	string      Value
	 */
    public function getNextValue($object)
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

    /**
     *  Returns the version of the numbering module
     *
     *  @return     string      Value
     */
    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') return $langs->trans("VersionDevelopment");
        elseif ($this->version == 'experimental') return $langs->trans("VersionExperimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("NotAvailable");
    }
}
