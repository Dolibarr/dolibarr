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
	var $nom='Aquarium';
	var $name='Aquarium';
	var $version='dolibarr';        // 'development', 'experimental', 'dolibarr'

	var	$prefixcustomeraccountancycode;
	var	$prefixsupplieraccountancycode;


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
		$texte.= '<tr><td>'.$langs->trans("ModuleCompanyCode".$this->name,$s1,$s2)."<br>\n";
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
	    return $this->prefixsupplieraccountancycode.'SUPPCODE'."<br>\n".$this->prefixcustomeraccountancycode.'CUSTCODE';
	}


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
		$i = 0;
		$this->db = $db;

		dol_syslog("mod_codecompta_aquarium::get_code search code for type=".$type." company=".(! empty($societe->name)?$societe->name:''));

		// Regle gestion compte compta
		$codetouse='';
		if ($type == 'customer')
		{
			$codetouse = $this->prefixcustomeraccountancycode;
			$codetouse.= (! empty($societe->code_client)?$societe->code_client:'CUSTCODE');
		}
		else if ($type == 'supplier')
		{
			$codetouse = $this->prefixsupplieraccountancycode;
			$codetouse.= (! empty($societe->code_fournisseur)?$societe->code_fournisseur:'SUPPCODE');
		}
		$codetouse=strtoupper(preg_replace('/([^a-z0-9])/i','',$codetouse));

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
		$sql.= " = '".$this->db->escape($code)."'";
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

