<?php
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/modules/societe/mod_codecompta_aquarium.php
 *	\ingroup    societe
 *	\brief      File of class to manage accountancy code of thirdparties with Panicum rules
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php';


/**
 *	Class to manage accountancy code of thirdparties with Aquarium rules
 */
class mod_codecompta_aquarium extends ModeleAccountancyCode
{
	/**
	 * @var string Nom du modele
	 * @deprecated
	 * @see name
	 */
	public $nom='Aquarium';

	/**
	 * @var string model name
	 */
	public $name='Aquarium';

	/**
     * Dolibarr version of the loaded document
     * @public string
     */
	public $version = 'dolibarr';        // 'development', 'experimental', 'dolibarr'

	public	$prefixcustomeraccountancycode;

	public	$prefixsupplieraccountancycode;


	/**
	 * 	Constructor
	 */
	function __construct()
	{
	    global $conf;
		if (! isset($conf->global->COMPANY_AQUARIUM_MASK_CUSTOMER) || trim($conf->global->COMPANY_AQUARIUM_MASK_CUSTOMER) == '') $conf->global->COMPANY_AQUARIUM_MASK_CUSTOMER='411';
        if (! isset($conf->global->COMPANY_AQUARIUM_MASK_SUPPLIER) || trim($conf->global->COMPANY_AQUARIUM_MASK_SUPPLIER) == '') $conf->global->COMPANY_AQUARIUM_MASK_SUPPLIER='401';
		$this->prefixcustomeraccountancycode=$conf->global->COMPANY_AQUARIUM_MASK_CUSTOMER;
	    $this->prefixsupplieraccountancycode=$conf->global->COMPANY_AQUARIUM_MASK_SUPPLIER;
	}


	/**
	 * Return description of module
	 *
	 * @param	Translate	$langs		Object langs
	 * @return	string   		   		Description of module
	 */
	function info($langs)
	{
	    global $conf;
	    global $form;

		$langs->load("companies");

        $tooltip='';
		$texte = '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$texte.= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte.= '<input type="hidden" name="param1" value="COMPANY_AQUARIUM_MASK_SUPPLIER">';
		$texte.= '<input type="hidden" name="param2" value="COMPANY_AQUARIUM_MASK_CUSTOMER">';
		$texte.= '<table class="nobordernopadding" width="100%">';
		$s1= $form->textwithpicto('<input type="text" class="flat" size="4" name="value1" value="'.$conf->global->COMPANY_AQUARIUM_MASK_SUPPLIER.'">',$tooltip,1,1);
		$s2= $form->textwithpicto('<input type="text" class="flat" size="4" name="value2" value="'.$conf->global->COMPANY_AQUARIUM_MASK_CUSTOMER.'">',$tooltip,1,1);
		$texte.= '<tr><td>';
		$texte.=$langs->trans("ModuleCompanyCodeCustomer".$this->name,$s2)."<br>\n";
		$texte.=$langs->trans("ModuleCompanyCodeSupplier".$this->name,$s1)."<br>\n";
		$texte.="<br>\n";
		if (! isset($conf->global->COMPANY_AQUARIUM_REMOVE_SPECIAL) || ! empty($conf->global->$conf->global->COMPANY_AQUARIUM_REMOVE_SPECIAL)) $texte.=$langs->trans('COMPANY_AQUARIUM_REMOVE_SPECIAL').' = '.yn(1)."<br>\n";
		//if (! empty($conf->global->COMPANY_AQUARIUM_REMOVE_ALPHA)) $texte.=$langs->trans('COMPANY_AQUARIUM_REMOVE_ALPHA').' = '.yn($conf->global->COMPANY_AQUARIUM_REMOVE_ALPHA)."<br>\n";
		if (! empty($conf->global->COMPANY_AQUARIUM_CLEAN_REGEX))  $texte.=$langs->trans('COMPANY_AQUARIUM_CLEAN_REGEX').' = '.$conf->global->COMPANY_AQUARIUM_CLEAN_REGEX."<br>\n";
		$texte.= '</td>';
		$texte.= '<td align="left">&nbsp; <input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button"></td>';
        $texte.= '</tr></table>';
        $texte.= '</form>';

		return $texte;
	}

	/**
	 * Return an example of result returned by getNextValue
	 *
	 * @param	Translate	$langs		Object langs
	 * @param	societe		$objsoc		Object thirdparty
	 * @param	int			$type		Type of third party (1:customer, 2:supplier, -1:autodetect)
	 * @return	string					Return string example
	 */
	function getExample($langs,$objsoc=0,$type=-1)
	{
		$s='';
		$s.=$this->prefixcustomeraccountancycode.'CUSTCODE';
	    $s.="<br>\n";
	    $s.=$this->prefixsupplieraccountancycode.'SUPPCODE';
	    return $s;
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Set accountancy account code for a third party into this->code
	 *
	 *  @param	DoliDB		$db             Database handler
	 *  @param  Societe		$societe        Third party object
	 *  @param  string		$type			'customer' or 'supplier'
	 *  @return	int							>=0 if OK, <0 if KO
	 */
	function get_code($db, $societe, $type='')
	{
        // phpcs:enable
		global $conf;

		$i = 0;
		$this->db = $db;

		dol_syslog("mod_codecompta_aquarium::get_code search code for type=".$type." company=".(! empty($societe->name)?$societe->name:''));

		// Regle gestion compte compta
		if ($type == 'customer')
		{
			$codetouse=(! empty($societe->code_client)?$societe->code_client:'CUSTCODE');
			$prefix = $this->prefixcustomeraccountancycode;
		}
		else if ($type == 'supplier')
		{
			$codetouse=(! empty($societe->code_fournisseur)?$societe->code_fournisseur:'SUPPCODE');
			$prefix = $this->prefixsupplieraccountancycode;
		}
		else
		{
			$this->error = 'Bad value for parameter type';
			return -1;
		}

		//$conf->global->COMPANY_AQUARIUM_CLEAN_REGEX='^..(..)..';

		// Remove special char if COMPANY_AQUARIUM_REMOVE_SPECIAL is set to 1 or not set (default)
		if (! isset($conf->global->COMPANY_AQUARIUM_REMOVE_SPECIAL) || ! empty($conf->global->COMPANY_AQUARIUM_REMOVE_SPECIAL)) $codetouse=preg_replace('/([^a-z0-9])/i','',$codetouse);
		// Remove special alpha if COMPANY_AQUARIUM_REMOVE_ALPHA is set to 1
		if (! empty($conf->global->COMPANY_AQUARIUM_REMOVE_ALPHA))   $codetouse=preg_replace('/([a-z])/i','',$codetouse);
		// Apply a regex replacement pattern on code if COMPANY_AQUARIUM_CLEAN_REGEX is set. Value must be a regex with parenthesis. The part into parenthesis is kept, the rest removed.
		if (! empty($conf->global->COMPANY_AQUARIUM_CLEAN_REGEX))	// Example: $conf->global->COMPANY_AQUARIUM_CLEAN_REGEX='^..(..)..';
		{
			$codetouse=preg_replace('/'.$conf->global->COMPANY_AQUARIUM_CLEAN_REGEX.'/','\1\2\3',$codetouse);
		}

		$codetouse=$prefix.strtoupper($codetouse);

		$is_dispo = $this->verif($db, $codetouse, $societe, $type);
		if (! $is_dispo)
		{
			$this->code=$codetouse;
		}
		else
		{
			// Pour retour
			$this->code=$codetouse;
		}
		dol_syslog("mod_codecompta_aquarium::get_code found code=".$this->code);
		return $is_dispo;
	}


	/**
	 *  Return if a code is available
	 *
	 *	@param	DoliDB		$db			Database handler
	 * 	@param	string		$code		Code of third party
	 * 	@param	Societe		$societe	Object third party
	 * 	@param	string		$type		'supplier' or 'customer'
	 *	@return	int						0 if OK but not available, >0 if OK and available, <0 if KO
	 */
	function verif($db, $code, $societe, $type)
	{
		$sql = "SELECT ";
		if ($type == 'customer') $sql.= "code_compta";
		else if ($type == 'supplier') $sql.= "code_compta_fournisseur";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe";
		$sql.= " WHERE ";
		if ($type == 'customer') $sql.= "code_compta";
		else if ($type == 'supplier') $sql.= "code_compta_fournisseur";
		$sql.= " = '".$db->escape($code)."'";
		if (! empty($societe->id)) $sql.= " AND rowid <> ".$societe->id;

		$resql=$db->query($sql);
		if ($resql)
		{
			if ($db->num_rows($resql) == 0)
			{
				dol_syslog("mod_codecompta_aquarium::verif code '".$code."' available");
				return 1;	// Dispo
			}
			else
			{
				dol_syslog("mod_codecompta_aquarium::verif code '".$code."' not available");
				return 0;	// Non dispo
			}
		}
		else
		{
			$this->error=$db->error()." sql=".$sql;
			return -1;		// Erreur
		}
	}
}
