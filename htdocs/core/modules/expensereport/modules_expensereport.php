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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';


/**
 *	Parent class for trips and expenses templates
 */
abstract class ModeleExpenseReport extends CommonDocGenerator
{
<<<<<<< HEAD
	var $error='';


	/**
	 *  Return list of active generation modules
	 *
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
	 */
	static function liste_modeles($db,$maxfilenamelength=0)
	{
		global $conf;

		$type='expensereport';
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
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}

/**
 * expensereport_pdf_create
 *
 *  @param	    DoliDB		$db  			Database handler
<<<<<<< HEAD
 *  @param	    ExpenseReport		$object			Object order
 *  @param		string		$message		Message
 *  @param	    string		$modele			Force le modele a utiliser ('' to not force)
 *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
=======
 *  @param	    ExpenseReport	$object		Object ExpenseReport
 *  @param		string		$message		Message
 *  @param	    string		$modele			Force the model to use ('' to not force)
 *  @param		Translate	$outputlangs	lang object to use for translation
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 *  @param      int			$hidedetails    Hide details of lines
 *  @param      int			$hidedesc       Hide description
 *  @param      int			$hideref        Hide ref
 *  @return     int         				0 if KO, 1 if OK
 */
<<<<<<< HEAD
function expensereport_pdf_create(DoliDB $db, ExpenseReport $object, $message, $modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
{
	return $object->generateDocument($modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
=======
function expensereport_pdf_create(DoliDB $db, ExpenseReport $object, $message, $modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
{
    return $object->generateDocument($modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}

/**
 *  \class      ModeleNumRefExpenseReport
 *  \brief      Parent class for numbering masks of expense reports
 */

abstract class ModeleNumRefExpenseReport
{
<<<<<<< HEAD
	var $error='';

	/**
	 *	Return if a module can be used or not
	 *
	 *	@return		boolean     true if module can be used
	 */
	function isEnabled()
=======
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
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return true;
	}

	/**
<<<<<<< HEAD
	 *	Renvoie la description par defaut du modele de numerotation
	 *
	 *	@return     string      Texte descripif
	 */
	function info()
=======
	 *	Returns the default description of the numbering model
	 *
	 *	@return     string      Descriptive text
	 */
    public function info()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		$langs->load("orders");
		return $langs->trans("NoDescription");
	}

	/**
<<<<<<< HEAD
	 *	Renvoie un exemple de numerotation
	 *
	 *	@return     string      Example
	 */
	function getExample()
=======
	 *	Returns an example of numbering
	 *
	 *	@return     string      Example
	 */
    public function getExample()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		$langs->load("trips");
		return $langs->trans("NoExample");
	}

	/**
<<<<<<< HEAD
	 *	Test si les numeros deja en vigueur dans la base ne provoquent pas de conflits qui empecheraient cette numerotation de fonctionner.
	 *
	 *	@return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
=======
	 *	Test whether the numbers already in force in the base do not cause conflicts that would prevent this numbering from working.
	 *
	 *	@return     boolean     false if conflict, true if ok
	 */
    public function canBeActivated()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return true;
	}

	/**
<<<<<<< HEAD
	 *	Renvoie prochaine valeur attribuee
	 *
	 *	@param	Object		$object		Object we need next value for
	 *	@return	string      Valeur
	 */
	function getNextValue($object)
=======
	 *	Returns next assigned value
	 *
	 *	@param	Object		$object		Object we need next value for
	 *	@return	string      Value
	 */
    public function getNextValue($object)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

<<<<<<< HEAD
	/**
	 *	Renvoie version du module numerotation
	 *
	 *	@return     string      Valeur
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
=======
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
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
