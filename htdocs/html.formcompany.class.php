<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *	\file       htdocs/html.formcompany.class.php
 *	\brief      File of class to build HTML component for third parties management
 *	\version	$Id$
 */


/**
 *	\class      FormCompany
 *	\brief      Class to build HTML component for third parties management
 *	\remarks	Only common components must be here.
 */
class FormCompany
{
	var $db;
	var $error;



	/**
	 *	\brief     Constructeur
	 *	\param     DB      handler d'acc�s base de donn�e
	 */
	function FormCompany($DB)
	{
		$this->db = $DB;

		return 1;
	}


	/**
	 *    	\brief      Renvoie la liste des libelles traduits des types actifs de societes
	 *		\param		mode		0=renvoi id+libelle, 1=renvoi code+libelle
	 *    	\return     array      	tableau des types
	 */
	function typent_array($mode=0)
	{
		global $langs;

		$effs = array();

		$sql = "SELECT id, code, libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_typent";
		$sql.= " WHERE active = 1";
		$sql.= " ORDER by id";
		dol_syslog('Form::typent_array sql='.$sql,LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ($i < $num)
			{
				$objp = $this->db->fetch_object($resql);
				if (! $mode) $key=$objp->id;
				else $key=$objp->code;

				if ($langs->trans($objp->code) != $objp->code)
				$effs[$key] = $langs->trans($objp->code);
				else
				$effs[$key] = $objp->libelle!='-'?$objp->libelle:'';
				$i++;
			}
			$this->db->free($resql);
		}

		return $effs;
	}

	/**
	 *		\brief      Renvoie la liste des types d'effectifs possibles (pas de traduction car nombre)
	 *		\param		mode		0=renvoi id+libelle, 1=renvoi code+libelle
	 *    	\return     array		tableau des types d'effectifs
	 */
	function effectif_array($mode=0)
	{
		$effs = array();

		$sql = "SELECT id, code, libelle";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_effectif";
		$sql.= " WHERE active = 1";
		$sql .= " ORDER BY id ASC";
		dol_syslog('Form::effectif_array sql='.$sql,LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ($i < $num)
			{
				$objp = $this->db->fetch_object($resql);
				if (! $mode) $key=$objp->id;
				else $key=$objp->code;

				$effs[$key] = $objp->libelle!='-'?$objp->libelle:'';
				$i++;
			}
			$this->db->free($resql);
		}
		return $effs;
	}


	/**
	 *  \brief      Affiche formulaire de selection des modes de reglement
	 *  \param      page        Page
	 *  \param      selected    Id or code preselected
	 *  \param      htmlname    Nom du formulaire select
	 *	\param		empty		Add empty value in list
	 */
	function form_prospect_level($page, $selected='', $htmlname='prospect_level_id', $empty=0)
	{
		global $langs;

		print '<form method="post" action="'.$page.'">';
		print '<input type="hidden" name="action" value="setprospectlevel">';
		print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
		print '<table class="noborder" cellpadding="0" cellspacing="0">';
		print '<tr><td>';

		print '<select class="flat" name="'.$htmlname.'">';
		if ($empty) print '<option value="">&nbsp;</option>';

		dol_syslog('Form::form_prospect_level',LOG_DEBUG);
		$sql = "SELECT code, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_prospectlevel";
		$sql.= " WHERE active > 0";
		$sql.= " ORDER BY sortorder";
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				print '<option value="'.$obj->code.'"';
				if ($selected == $obj->code) print ' selected="true"';
				print '>';
				$level=$langs->trans($obj->code);
				if ($level == $obj->code) $level=$langs->trans($obj->label);
				print $level;
				print '</option>';

				$i++;
			}
		}
		else dol_print_error($this->db);
		print '</select>';

		print '</td>';
		print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
		print '</tr></table></form>';
	}


	/**
	 *    \brief      Retourne la liste d�roulante des d�partements/province/cantons tout pays confondu ou pour un pays donn�.
	 *    \remarks    Dans le cas d'une liste tout pays confondus, l'affichage fait une rupture sur le pays.
	 *    \remarks    La cle de la liste est le code (il peut y avoir plusieurs entr�e pour
	 *                un code donn�e mais dans ce cas, le champ pays diff�re).
	 *                Ainsi les liens avec les d�partements se font sur un d�partement ind�pendemment de son nom.
	 *    \param      selected        code forme juridique a pr�s�lectionn�
	 *    \param      pays_code       0=liste tous pays confondus, sinon code du pays � afficher
	 */
	function select_departement($selected='',$pays_code=0)
	{
		global $conf,$langs,$user;
			
		dol_syslog("Form::select_departement selected=$selected, pays_code=$pays_code",LOG_DEBUG);
			
		$langs->load("dict");

		$htmlname='departement_id';

		// On recherche les d�partements/cantons/province active d'une region et pays actif
		$sql = "SELECT d.rowid, d.code_departement as code , d.nom, d.active, p.libelle as libelle_pays, p.code as code_pays FROM";
		$sql .= " ".MAIN_DB_PREFIX ."c_departements as d, ".MAIN_DB_PREFIX."c_regions as r,".MAIN_DB_PREFIX."c_pays as p";
		$sql .= " WHERE d.fk_region=r.code_region and r.fk_pays=p.rowid";
		$sql .= " AND d.active = 1 AND r.active = 1 AND p.active = 1";
		if ($pays_code) $sql .= " AND p.code = '".$pays_code."'";
		$sql .= " ORDER BY p.code, d.code_departement";

		dol_syslog("Form::select_departement sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
			print '<select class="flat" name="'.$htmlname.'">';
			if ($pays_code) print '<option value="0">&nbsp;</option>';
			$num = $this->db->num_rows($result);
			$i = 0;
			dol_syslog("Form::select_departement num=$num",LOG_DEBUG);
			if ($num)
			{
				$pays='';
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					if ($obj->code == '0')		// Le code peut etre une chaine
					{
						print '<option value="0">&nbsp;</option>';
					}
					else {
						if (! $pays || $pays != $obj->libelle_pays)
						{
							// Affiche la rupture si on est en mode liste multipays
							if (! $pays_code && $obj->code_pays)
							{
								print '<option value="-1">----- '.$obj->libelle_pays." -----</option>\n";
								$pays=$obj->libelle_pays;
							}
						}

						if ($selected > 0 && $selected == $obj->rowid)
						{
							print '<option value="'.$obj->rowid.'" selected="true">';
						}
						else
						{
							print '<option value="'.$obj->rowid.'">';
						}
						// Si traduction existe, on l'utilise, sinon on prend le libell� par d�faut
						print $obj->code . ' - ' . ($langs->trans($obj->code)!=$obj->code?$langs->trans($obj->code):($obj->nom!='-'?$obj->nom:''));
						print '</option>';
					}
					$i++;
				}
			}
			print '</select>';
			if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
		}
		else
		{
			dol_print_error($this->db);
		}
	}


	/**
	 *    \brief      Retourne la liste d�roulante des regions actives dont le pays est actif
	 *    \remarks    La cle de la liste est le code (il peut y avoir plusieurs entr�e pour
	 *                un code donn�e mais dans ce cas, le champ pays et lang diff�re).
	 *                Ainsi les liens avec les regions se font sur une region independemment
	 *                de son nom.
	 */
	function select_region($selected='',$htmlname='region_id')
	{
		global $conf,$langs;
		$langs->load("dict");

		$sql = "SELECT r.rowid, r.code_region as code, r.nom as libelle, r.active, p.libelle as libelle_pays FROM ".MAIN_DB_PREFIX."c_regions as r, ".MAIN_DB_PREFIX."c_pays as p";
		$sql .= " WHERE r.fk_pays=p.rowid AND r.active = 1 and p.active = 1 ORDER BY libelle_pays, libelle ASC";

		dol_syslog("Form::select_region sql=".$sql);
		if ($this->db->query($sql))
		{
			print '<select class="flat" name="'.$htmlname.'">';
			$num = $this->db->num_rows();
			$i = 0;
			if ($num)
			{
				$pays='';
				while ($i < $num)
				{
					$obj = $this->db->fetch_object();
					if ($obj->code == 0) {
						print '<option value="0">&nbsp;</option>';
					}
					else {
						if ($pays == '' || $pays != $obj->libelle_pays)
						{
							// Affiche la rupture
							print '<option value="-1" disabled="disabled">----- '.$obj->libelle_pays." -----</option>\n";
							$pays=$obj->libelle_pays;
						}

						if ($selected > 0 && $selected == $obj->code)
						{
							print '<option value="'.$obj->code.'" selected="true">'.$obj->libelle.'</option>';
						}
						else
						{
							print '<option value="'.$obj->code.'">'.$obj->libelle.'</option>';
						}
					}
					$i++;
				}
			}
			print '</select>';
		}
		else
		{
			dol_print_error($this->db);
		}
	}


	/**
	 *    \brief      Retourne la liste d�roulante des civilite actives
	 *    \param      selected    civilite pr�-s�lectionn�e
	 */
	function select_civilite($selected='')
	{
		global $conf,$langs,$user;
		$langs->load("dict");

		$sql = "SELECT rowid, code, civilite, active FROM ".MAIN_DB_PREFIX."c_civilite";
		$sql .= " WHERE active = 1";

		dol_syslog("Form::select_civilite sql=".$sql);
		if ($this->db->query($sql))
		{
			print '<select class="flat" name="civilite_id">';
			print '<option value="">&nbsp;</option>';
			$num = $this->db->num_rows();
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object();
					if ($selected == $obj->code)
					{
						print '<option value="'.$obj->code.'" selected="true">';
					}
					else
					{
						print '<option value="'.$obj->code.'">';
					}
					// Si traduction existe, on l'utilise, sinon on prend le libell� par d�faut
					print ($langs->trans("Civility".$obj->code)!="Civility".$obj->code ? $langs->trans("Civility".$obj->code) : ($obj->civilite!='-'?$obj->civilite:''));
					print '</option>';
					$i++;
				}
			}
			print '</select>';
			if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
		}
		else
		{
			dol_print_error($this->db);
		}
	}


	/**
	 *    \brief      Retourne la liste d�roulante des formes juridiques tous pays confondus ou pour un pays donn�.
	 *    \remarks    Dans le cas d'une liste tous pays confondu, on affiche une rupture sur le pays
	 *    \param      selected        Code forme juridique a pr�-s�lectionn�
	 *    \param      pays_code       0=liste tous pays confondus, sinon code du pays � afficher
	 */
	function select_forme_juridique($selected='',$pays_code=0)
	{
		global $conf,$langs,$user;
		$langs->load("dict");

		// On recherche les formes juridiques actives des pays actifs
		$sql  = "SELECT f.rowid, f.code as code , f.libelle as nom, f.active, p.libelle as libelle_pays, p.code as code_pays";
		$sql .= " FROM llx_c_forme_juridique as f, llx_c_pays as p";
		$sql .= " WHERE f.fk_pays=p.rowid";
		$sql .= " AND f.active = 1 AND p.active = 1";
		if ($pays_code) $sql .= " AND p.code = '".$pays_code."'";
		$sql .= " ORDER BY p.code, f.code";

		dol_syslog("Form::select_forme_juridique sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
			print '<div id="particulier2" class="visible">';
			print '<select class="flat" name="forme_juridique_code">';
			if ($pays_code) print '<option value="0">&nbsp;</option>';
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num)
			{
				$pays='';
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					if ($obj->code == 0) {
						print '<option value="0">&nbsp;</option>';
					}
					else {
						if (! $pays || $pays != $obj->libelle_pays) {
							// Affiche la rupture si on est en mode liste multipays
							if (! $pays_code && $obj->code_pays) {
								print '<option value="0">----- '.$obj->libelle_pays." -----</option>\n";
								$pays=$obj->libelle_pays;
							}
						}

						if ($selected > 0 && $selected == $obj->code)
						{
							print '<option value="'.$obj->code.'" selected="true">';
						}
						else
						{
							print '<option value="'.$obj->code.'">';
						}
						// Si translation exists, we use it, otherwise we use default label in database
						print $obj->code . ' - ';
						print ($langs->trans("JuridicalStatus".$obj->code)!="JuridicalStatus".$obj->code?$langs->trans("JuridicalStatus".$obj->code):($obj->nom!='-'?$obj->nom:''));	// $obj->nom is alreay in output charset (converted by database driver)
						print '</option>';
					}
					$i++;
				}
			}
			print '</select>';
			if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
			print '</div>';
		}
		else
		{
			dol_print_error($this->db);
		}
	}


	/**
	 *    \brief      Return list of third parties
	 *    \param      object          Object we try to find contacts
	 *    \param      var_id          Name of id field
	 *    \param      selected        Pre-selected third party
	 *    \param      htmlname        Name of HTML form
	 */
	function selectCompaniesForNewContact($object, $var_id, $selected = '', $htmlname = 'newcompany')
	{
		global $conf, $langs;

		// On recherche les societes
		$sql = "SELECT s.rowid, s.nom FROM";
		$sql .= " ".MAIN_DB_PREFIX."societe as s";
		if ($conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT)
		{
			$sql.= " WHERE rowid = ".$selected;
		}
		$sql .= " ORDER BY nom ASC";

		$resql = $object->db->query($sql);
		if ($resql)
		{
			if ($conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT)
			{
				$langs->load("companies");
				$obj = $this->db->fetch_object($resql);
				$socid = $obj->rowid?$obj->rowid:'';
				$javaScript = "window.location=\'./contact.php?".$var_id."=".$object->id."&amp;".$htmlname."=\' + document.getElementById(\'newcompany_id\').value;";

				// On applique un delai d'execution pour le bon fonctionnement
				$htmloption = 'onChange="ac_delay(\''.$javaScript.'\',\'500\')"';

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td class="nobordernopadding">';
				print '<div>';
				if ($obj->rowid == 0)
				{
					print '<input type="text" size="30" id="newcompany" name="newcompany" value="'.$langs->trans("SelectCompany").'" '.$htmloption.' />';
				}
				else
				{
					print '<input type="text" size="30" id="newcompany" name="newcompany" value="'.$obj->nom.'" '.$htmloption.' />';
				}
				print ajax_autocompleter($socid,'newcompany','/societe/ajaxcompanies.php','');
				print '</td>';
				print '<td class="nobordernopadding" align="left" width="16">';
				print ajax_indicator($htmlname,'working');
				print '</td></tr>';
				print '</table>';
				return $socid;
			}
			else
			{
				$javaScript = "window.location='./contact.php?".$var_id."=".$object->id."&amp;".$htmlname."=' + form.".$htmlname.".options[form.".$htmlname.".selectedIndex].value;";
				print '<select class="flat" name="'.$htmlname.'" onChange="'.$javaScript.'">';
				$num = $object->db->num_rows($resql);
				$i = 0;
				if ($num)
				{
					while ($i < $num)
					{
						$obj = $object->db->fetch_object($resql);
						if ($i == 0) $firstCompany = $obj->rowid;
						if ($selected > 0 && $selected == $obj->rowid)
						{
							print '<option value="'.$obj->rowid.'" selected="true">'.dol_trunc($obj->nom,24).'</option>';
							$firstCompany = $obj->rowid;
						}
						else
						{
							print '<option value="'.$obj->rowid.'">'.dol_trunc($obj->nom,24).'</option>';
						}
						$i ++;
					}
				}
				print "</select>\n";
				return $firstCompany;
			}
		}
		else
		{
			dol_print_error($object->db);
		}
	}


	/**
	 *
	 */
	function selectTypeContact($object, $defValue, $htmlname = 'type', $source)
	{
	 $lesTypes = $object->liste_type_contact($source);
	 print '<select class="flat" name="'.$htmlname.'">';
	 foreach($lesTypes as $key=>$value)
	 {
		 print '<option value="'.$key.'">'.$value.'</option>';
	 }
	 print "</select>\n";
	}


}

?>
