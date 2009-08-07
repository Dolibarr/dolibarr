<?php
/* Copyright (c) 2002-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier        <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien Di Cintio   <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2006      Marc Barilley/Ocebo   <marc@ocebo.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerker@telenet.be>
 * Copyright (C) 2007      Patrick Raguin 		   <patrick.raguin@gmail.com>
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
 *	\file       htdocs/html.form.class.php
 *	\brief      Fichier de la classe des fonctions predefinie de composants html
 *	\version	$Id$
 */


/**
 *	\class      Form
 *	\brief      Classe permettant la generation de composants html
 *	\remarks	Only common components must be here.
 */
class Form
{
	var $db;
	var $error;

	// Cache arrays
	var $cache_types_paiements=array();
	var $cache_conditions_paiements=array();

	var $tva_taux_value;
	var $tva_taux_libelle;


	/**
	 *	\brief     Constructor
	 *	\param     DB      Database handler
	 */
	function Form($DB)
	{
		$this->db = $DB;

		return 1;
	}


	/**
	 *	\brief	Affiche un texte+picto avec tooltip sur texte ou sur picto
	 *	\param  text				Texte a afficher
	 *	\param  htmltext	    	Contenu html du tooltip, code en html
	 *	\param	tooltipon			1=tooltip sur texte, 2=tooltip sur picto, 3=tooltip sur les 2, 4=tooltip sur les 2 et force en Ajax
	 *	\param	direction			-1=Le picto est avant, 0=pas de picto, 1=le picto est apres
	 *	\param	img					Code img du picto
	 * 	\param	i					Numero of tooltip
	 * 	\param	width				Width of tooltip
	 * 	\param	shiftX				Shift of tooltip
	 *	\return	string				Code html du tooltip (texte+picto)
	 */
	function textwithtooltip($text,$htmltext,$tooltipon=1,$direction=0,$img='',$i=1,$width='200',$shiftX='10')
	{
		global $conf;

		if (! $htmltext) return $text;

		$paramfortooltiptext ='';
		$paramfortooltippicto ='';

		// Sanitize tooltip
		$htmltext=ereg_replace("'","\'",$htmltext);
		$htmltext=ereg_replace("&#039;","\'",$htmltext);
		$htmltext=ereg_replace("\r","",$htmltext);
		$htmltext=ereg_replace("<br>\n","<br>",$htmltext);
		$htmltext=ereg_replace("\n","",$htmltext);

		if ($conf->use_javascript_ajax && $tooltipon == 4)
		{
			$s = '<div id="tip'.$i.'">'."\n";
			$s.= $text;
			$s.= '</div>'."\n";
			$s.= '<div id="tooltip_content" style="display:none">'."\n";
			$s.= $htmltext."\n";
			$s.= '</div>'."\n";
			$s.= '<script type=\'text/javascript\'>'."\n";
			$s.= 'TooltipManager.init("","",{width:'.$width.', shiftX:'.$shiftX.'});'."\n";
			$s.= 'TooltipManager.addHTML("tip'.$i.'", "tooltip_content");'."\n";
			$s.= '</script>'."\n";
		}
		else
		{
			if ($conf->use_javascript_ajax)
			{
				$htmltext=eregi_replace('"',"\'",$htmltext);
				if ($tooltipon==1 || $tooltipon==3)
				{
					$paramfortooltiptext.=' onmouseover="showtip(\''.$htmltext.'\')"';
					$paramfortooltiptext.=' onMouseout="hidetip()"';
				}
				if ($tooltipon==2 || $tooltipon==3)
				{
					$paramfortooltippicto.=' onmouseover="showtip(\''.$htmltext.'\')"';
					$paramfortooltippicto.=' onMouseout="hidetip()"';
				}
			}

			$s="";
			$s.='<table class="nobordernopadding" summary=""><tr>';
			if ($direction > 0)
			{
				if ($text)
				{
					$s.='<td'.$paramfortooltiptext.'>'.$text;
					if ($direction) $s.='&nbsp;';
					$s.='</td>';
				}
				if ($direction) $s.='<td'.$paramfortooltippicto.' valign="top" width="14">'.$img.'</td>';
			}
			else
			{
				if ($direction) $s.='<td'.$paramfortooltippicto.' valign="top" width="14">'.$img.'</td>';
				if ($text)
				{
					$s.='<td'.$paramfortooltiptext.'>';
					if ($direction) $s.='&nbsp;';
					$s.=$text.'</td>';
				}
			}
			$s.='</tr></table>';
		}
		return $s;
	}

	/**
	 *	\brief     	Show a text with a picto and a tooltip on picto
	 *	\param     	text				Text to show
	 *	\param   	htmltooltip     	Content of tooltip
	 *	\param		direction			1=Icon is after text, -1=Icon is before text
	 * 	\param		type				Type of picto (info, help, warning, superadmin...)
	 * 	\return		string				HTML code of text, picto, tooltip
	 */
	function textwithpicto($text,$htmltext,$direction=1,$type='help')
	{
		global $conf;

		if ("$type" == "0") $type='info';	// For backward compatibility

		$alt='';
		if (empty($conf->use_javascript_ajax)) $alt='Help disabled (javascript disabled)';
		if ($type == 'info') 				$img=img_help(0,$alt);
		if ($type == 'help' || $type ==1)	$img=img_help(1,$alt);
		if ($type == 'warning') 			$img=img_warning($alt);
		if ($type == 'superadmin') 			$img=img_redstar($alt);
		return $this->textwithtooltip($text,$htmltext,2,$direction,$img);
	}


	/**
	 *    \brief     Retourne la liste deroulante des pays actifs, dans la langue de l'utilisateur
	 *    \param     selected         Id ou Code pays ou Libelle pays pre-selectionne
	 *    \param     htmlname         Nom de la liste deroulante
	 *    \param     htmloption       Options html sur le select
	 *    \todo      trier liste sur noms apres traduction plutot que avant
	 */
	function select_pays($selected='',$htmlname='pays_id',$htmloption='')
	{
		global $conf,$langs;
		$langs->load("dict");

		$sql = "SELECT rowid, code, libelle, active";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_pays";
		$sql.= " WHERE active = 1";
		// \TODO A virer
		if ($conf->use_javascript_ajax && $conf->global->CODE_DE_TEST)
		{
			if (is_numeric($selected))
			{
				$sql.= " AND rowid = ".$selected;
			}
			else
			{
				$sql.= " AND code = '".$selected."'";
			}
		}
		$sql.= " ORDER BY code ASC";

		dol_syslog("Form::select_pays sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			// \TODO A virer
			if ($conf->use_javascript_ajax && $conf->global->CODE_DE_TEST)
			{
				$langs->load("companies");
				$obj = $this->db->fetch_object($resql);
				$pays_id = $obj->rowid?$obj->rowid:'';

				// On applique un delai d'execution pour le bon fonctionnement
				$mode_create = substr($htmloption,-9,6);
				$mode_edit = substr($htmloption,-7,4);
				$mode_company = substr($htmloption,-10,7);
				if ($mode_create == 'create')
				{
					$htmloption = 'onChange="ac_delay(\'autofilltownfromzip_save_refresh_create()\',\'500\')"';
				}
				else if ($mode_edit == 'edit')
				{
					$htmloption = 'onChange="ac_delay(\'autofilltownfromzip_save_refresh_edit()\',\'500\')"';
				}
				else if ($mode_company == 'refresh')
				{
					$htmloption = 'onChange="ac_delay(\'company_save_refresh()\',\'500\')"';
				}

				print '<div>';
				if ($obj->rowid == 0)
				{
					print '<input type="text" size="45" id="pays" name="pays" value="'.$langs->trans("SelectCountry").'" '.$htmloption.' />';
				}
				else
				{
					print '<input type="text" size="45" id="pays" name="pays" value="'.$obj->libelle.'" '.$htmloption.' />';
				}

				print ajax_autocompleter($pays_id,'pays','/societe/ajaxcountries.php','working');
			}
			else
			{
				print '<select class="flat" name="'.$htmlname.'" '.$htmloption.'>';
				$num = $this->db->num_rows($resql);
				$i = 0;
				if ($num)
				{
					$foundselected=false;
					while ($i < $num)
					{
						$obj = $this->db->fetch_object($resql);
						if ($selected && $selected != '-1' &&
						($selected == $obj->rowid || $selected == $obj->code || $selected == $obj->libelle) )
						{
							$foundselected=true;
							print '<option value="'.$obj->rowid.'" selected="true">';
						}
						else
						{
							print '<option value="'.$obj->rowid.'">';
						}
						// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
						if ($obj->code) { print $obj->code . ' - '; }
						print ($obj->code && $langs->trans("Country".$obj->code)!="Country".$obj->code?$langs->trans("Country".$obj->code):($obj->libelle!='-'?$obj->libelle:'&nbsp;'));
						print '</option>';
						$i++;
					}
				}
				print '</select>';
				return 0;
			}
		}
		else
		{
			dol_print_error($this->db);
			return 1;
		}
	}


	/**
	 *    \brief      Retourne la liste des types de comptes financiers
	 *    \param      selected        Type pre-selectionne
	 *    \param      htmlname        Nom champ formulaire
	 */
	function select_type_comptes_financiers($selected=1,$htmlname='type')
	{
		global $langs;
		$langs->load("banks");

		$type_available=array(0,1,2);

		print '<select class="flat" name="'.$htmlname.'">';
		$num = count($type_available);
		$i = 0;
		if ($num)
		{
			while ($i < $num)
			{
				if ($selected == $type_available[$i])
				{
					print '<option value="'.$type_available[$i].'" selected="true">'.$langs->trans("BankType".$type_available[$i]).'</option>';
				}
				else
				{
					print '<option value="'.$type_available[$i].'">'.$langs->trans("BankType".$type_available[$i]).'</option>';
				}
				$i++;
			}
		}
		print '</select>';
	}


	/**
	 *		\brief      Return list of social contributions
	 *		\param      selected        Preselected type
	 *		\param      htmlname        Name of field in form
	 * 		\param		useempty		Set to 1 if we want an empty value
	 * 		\param		maxlen			Max length of text in combo box
	 * 		\param		help			Add or not the admin help picto
	 */
	function select_type_socialcontrib($selected='',$htmlname='actioncode', $useempty=0, $maxlen=40, $help=1)
	{
		global $db,$langs,$user;

		$sql = "SELECT c.id, c.libelle as type";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
		$sql.= " WHERE active = 1";
		$sql.= " ORDER BY c.libelle ASC";

		dol_syslog("Form::select_type_socialcontrib sql=".$sql, LOG_DEBUG);
		$resql=$db->query($sql);
		if ($resql)
		{
			print '<select class="flat" name="'.$htmlname.'">';
			$num = $db->num_rows($resql);
			$i = 0;

			if ($useempty) print '<option value="0">&nbsp;</option>';

			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				print '<option value="'.$obj->id.'"';
				if ($obj->id == $selected) print ' selected="true"';
				print '>'.dol_trunc($obj->type,$maxlen);
				$i++;
			}
			print '</select>';
			if ($user->admin && $help) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
		}
		else
		{
			dol_print_error($db,$db->lasterror());
		}
	}

	/**
	 *		\brief      Return list of types of lines (product or service)
	 *		\param      selected        Preselected type
	 *		\param      htmlname        Name of field in form
	 * 		\param		showempty		Add an empty field
	 */
	function select_type_of_lines($selected='',$htmlname='type',$showempty=0,$hidetext=0)
	{
		global $db,$langs,$user,$conf;

		// If product & services are enabled or both disabled.
		if (($conf->produit->enabled && $conf->service->enabled)
			|| (empty($conf->produit->enabled) && empty($conf->service->enabled)))
		{
			if (empty($hidetext)) print $langs->trans("Type").': ';
			print '<select class="flat" name="'.$htmlname.'">';
			if ($showempty)
			{
				print '<option value="-1"';
				if ($selected == -1) print ' selected="true"';
				print '>&nbsp;</option>';
			}

			print '<option value="0"';
			if (0 == $selected) print ' selected="true"';
			print '>'.$langs->trans("Product");

			print '<option value="1"';
			if (1 == $selected) print ' selected="true"';
			print '>'.$langs->trans("Service");

			print '</select>';
			//if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
		}
		if (empty($conf->produit->enabled) && $conf->service->enabled)
		{
			print '<input type="hidden" name="'.$htmlname.'" value="1">';
		}
		if ($conf->produit->enabled && empty($conf->service->enabled))
		{
			print '<input type="hidden" name="'.$htmlname.'" value="0">';
		}

	}

	/**
	 *		\brief      Return list of types of notes
	 *		\param      selected        Preselected type
	 *		\param      htmlname        Name of field in form
	 * 		\param		showempty		Add an empty field
	 */
	function select_type_fees($selected='',$htmlname='type',$showempty=0)
	{
		global $db,$langs,$user;
		$langs->load("trips");

		print '<select class="flat" name="'.$htmlname.'">';
		if ($showempty)
		{
			print '<option value="-1"';
			if ($selected == -1) print ' selected="true"';
			print '>&nbsp;</option>';
		}

		$sql = "SELECT c.code, c.libelle as type FROM ".MAIN_DB_PREFIX."c_type_fees as c";
		$sql.= " ORDER BY lower(c.libelle) ASC";
		$resql=$db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;

			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				print '<option value="'.$obj->code.'"';
				if ($obj->code == $selected) print ' selected="true"';
				print '>';
				if ($obj->code != $langs->trans($obj->code)) print $langs->trans($obj->code);
				else print $langs->trans($obj->type);
				$i++;
			}
		}
		print '</select>';
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
	}

	/**
	 *    	\brief      Output html form to select a third party
	 *		\param      selected        Preselected type
	 *		\param      htmlname        Name of field in form
	 *    	\param      filter          Optionnal filters criteras
	 *		\param		showempty		Add an empty field
	 * 		\param		showtype		Show if third party is customer, prospect or supplier
	 */
	function select_societes($selected='',$htmlname='socid',$filter='',$showempty=0, $showtype=0)
	{
		global $conf,$user,$langs;

		// On recherche les societes
		$sql = "SELECT s.rowid, s.nom, s.client, s.fournisseur, s.code_client, s.code_fournisseur";
		$sql.= " FROM ".MAIN_DB_PREFIX ."societe as s";
		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE s.entity = ".$conf->entity;
		if ($filter) $sql.= " AND ".$filter;
		if ($selected && $conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT)	$sql.= " AND rowid = ".$selected;
		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
		$sql.= " ORDER BY nom ASC";

		dol_syslog("Form::select_societes sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT)
			{
				$socid = 0;
				if ($selected)
				{
					$obj = $this->db->fetch_object($resql);
					$socid = $obj->rowid?$obj->rowid:'';
				}
				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td class="nobordernopadding">';
				print '<div>';
				if ($socid == 0)
				{
					print '<input type="text" size="30" id="'.$htmlname.'" name="'.$htmlname.'" value=""/>';
				}
				else
				{
					print '<input type="text" size="30" id="'.$htmlname.'" name="'.$htmlname.'" value="'.$obj->nom.'"/>';
				}
				print ajax_autocompleter(($socid?$socid:-1),$htmlname,'/societe/ajaxcompanies.php?filter='.urlencode($filter), '');
				print '</td>';
				print '<td class="nobordernopadding" align="left" width="16">';
				print ajax_indicator($htmlname,'working');
				print '</td></tr>';
				print '</table>';
			}
			else
			{
				print '<select class="flat" name="'.$htmlname.'">';
				if ($showempty) print '<option value="-1">&nbsp;</option>';
				$num = $this->db->num_rows($resql);
				$i = 0;
				if ($num)
				{
					while ($i < $num)
					{
						$obj = $this->db->fetch_object($resql);
						$label=$obj->nom;
						if ($showtype)
						{
							if ($obj->client || $obj->fournisseur) $label.=' (';
							if ($obj->client == 1) $label.=$langs->trans("Customer");
							if ($obj->client == 2) $label.=$langs->trans("Prospect");
							if ($obj->fournisseur) $label.=($obj->client?', ':'').$langs->trans("Supplier");
							if ($obj->client || $obj->fournisseur) $label.=')';
						}
						if ($selected > 0 && $selected == $obj->rowid)
						{
							print '<option value="'.$obj->rowid.'" selected="true">'.$label.'</option>';
						}
						else
						{
							print '<option value="'.$obj->rowid.'">'.$label.'</option>';
						}
						$i++;
					}
				}
				print '</select>';
			}
		}
		else
		{
			dol_print_error($this->db);
		}
	}


	/**
	 *    	\brief      Return HTML combo list of absolute discounts
	 *    	\param      selected        Id remise fixe pre-selectionnee
	 *    	\param      htmlname        Nom champ formulaire
	 *    	\param      filter          Criteres optionnels de filtre
	 * 		\param		maxvalue		Max value for lines that can be selected
	 * 		\return		int				Return number of qualifed lines in list
	 */
	function select_remises($selected='',$htmlname='remise_id',$filter='',$socid, $maxvalue=0)
	{
		global $langs,$conf;

		// On recherche les remises
		$sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
		$sql.= " re.description, re.fk_facture_source";
		$sql.= " FROM ".MAIN_DB_PREFIX ."societe_remise_except as re";
		$sql.= " WHERE fk_soc = ".$socid;
		if ($filter) $sql.= " AND ".$filter;
		$sql.= " ORDER BY re.description ASC";

		dol_syslog("Form::select_remises sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			print '<select class="flat" name="'.$htmlname.'">';
			$num = $this->db->num_rows($resql);

			$qualifiedlines=$num;

			$i = 0;
			if ($num)
			{
				print '<option value="0">&nbsp;</option>';
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					$desc=dol_trunc($obj->description,40);
					if ($desc=='(CREDIT_NOTE)') $desc=$langs->trans("CreditNote");
					if ($desc=='(DEPOSIT)')     $desc=$langs->trans("Deposit");

					$selectstring='';
					if ($selected > 0 && $selected == $obj->rowid) $selectstring=' selected="true"';

					$disabled='';
					if ($maxvalue && $obj->amount_ttc > $maxvalue)
					{
						$qualifiedlines--;
						$disabled=' disabled="true"';
					}

					print '<option value="'.$obj->rowid.'"'.$selectstring.$disabled.'>'.$desc.' ('.price($obj->amount_ht).' '.$langs->trans("HT").' - '.price($obj->amount_ttc).' '.$langs->trans("TTC").')</option>';
					$i++;
				}
			}
			print '</select>';
			return $qualifiedlines;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *    	\brief      Retourne la liste deroulante des contacts d'une societe donnee
	 *    	\param      socid      	    Id de la societe
	 *    	\param      selected   	    Id contact pre-selectionne
	 *    	\param      htmlname  	    Nom champ formulaire ('none' pour champ non editable)
	 *      \param      show_empty      0=liste sans valeur nulle, 1=ajoute valeur inconnue
	 *      \param      exclude         Liste des id contacts a exclure
	 *		\return		int				<0 if KO, Nb of contact in list if OK
	 */
	function select_contacts($socid,$selected='',$htmlname='contactid',$showempty=0,$exclude='')
	{
		// Permettre l'exclusion de contacts
		if (is_array($exclude))
		{
			$excludeContacts = implode("','",$exclude);
		}

		// On recherche les societes
		$sql = "SELECT s.rowid, s.name, s.firstname FROM";
		$sql.= " ".MAIN_DB_PREFIX ."socpeople as s";
		$sql.= " WHERE fk_soc=".$socid;
		if (is_array($exclude) && $excludeContacts) $sql.= " AND s.rowid NOT IN ('".$excludeContacts."')";
		$sql.= " ORDER BY s.name ASC";

		dol_syslog("Form::select_contacts sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			if ($num == 0) return 0;

			if ($htmlname != 'none') print '<select class="flat" name="'.$htmlname.'">';
			if ($showempty) print '<option value="0">&nbsp;</option>';
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					if ($htmlname != 'none')
					{
						if ($selected && $selected == $obj->rowid)
						{
							print '<option value="'.$obj->rowid.'" selected="true">'.$obj->name.' '.$obj->firstname.'</option>';
						}
						else
						{
							print '<option value="'.$obj->rowid.'">'.$obj->name.' '.$obj->firstname.'</option>';
						}
					}
					else
					{
						if ($selected == $obj->rowid) print $obj->name.' '.$obj->firstname;
					}
					$i++;
				}
			}
			if ($htmlname != 'none')
			{
				print '</select>';
			}
			return $num;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *	\brief      Return select list of users
	 *  \param      selected        Id user preselected
	 *  \param      htmlname        Field name in form
	 *  \param      show_empty      0=liste sans valeur nulle, 1=ajoute valeur inconnue
	 *  \param      exclude         List of users id to exclude
	 * 	\param		disabled		If select list must be disabled
	 *  \param      include         List of users id to include
	 */
	function select_users($selected='',$htmlname='userid',$show_empty=0,$exclude='',$disabled=0,$include='')
	{
		global $conf;

		// Permettre l'exclusion d'utilisateurs
		if (is_array($exclude))	$excludeUsers = implode("','",$exclude);
		// Permettre l'inclusion d'utilisateurs
		if (is_array($include))	$includeUsers = implode("','",$include);

		// On recherche les utilisateurs
		$sql = "SELECT u.rowid, u.name, u.firstname, u.login FROM";
		$sql.= " ".MAIN_DB_PREFIX ."user as u";
		$sql.= " WHERE u.entity IN (0,".$conf->entity.")";
		if (is_array($exclude) && $excludeUsers) $sql.= " AND u.rowid NOT IN ('".$excludeUsers."')";
		if (is_array($include) && $includeUsers) $sql.= " AND u.rowid IN ('".$includeUsers."')";
		$sql.= " ORDER BY u.name ASC";

		dol_syslog("Form::select_users sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			print '<select class="flat" name="'.$htmlname.'"'.($disabled?' disabled="true"':'').'>';
			if ($show_empty) print '<option value="-1"'.($id==-1?' selected="true"':'').'>&nbsp;</option>'."\n";
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);

					if ((is_object($selected) && $selected->id == $obj->rowid) || (! is_object($selected) && $selected == $obj->rowid))
					{
						print '<option value="'.$obj->rowid.'" selected="true">';
					}
					else
					{
						print '<option value="'.$obj->rowid.'">';
					}
					print $obj->name.($obj->name && $obj->firstname?' ':'').$obj->firstname;
					print ' ('.$obj->login.')';
					print '</option>';
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
	 *  \brief    Return list of products for customer in Ajax if Ajax activated or go to select_produits_do
	 *  \param    selected        Preselected products
	 *  \param    htmlname        Name of HTML select
	 *  \param    filtertype      Filter on product type (''=nofilter, 0=product, 1=service)
	 *  \param    limit           Limit sur le nombre de lignes retournees
	 *  \param    price_level     Level of price to show
	 *  \param	  status		  -1=Return all products, 0=Products not on sell, 1=Products on sell
	 *  \param	  finished     	  2=all, 1=finished, 0=raw material
	 */
	function select_produits($selected='',$htmlname='productid',$filtertype='',$limit=20,$price_level=0,$status=1,$finished=2)
	{
		global $langs,$conf;

		if ($conf->global->PRODUIT_USE_SEARCH_TO_SELECT)
		{
			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td class="nobordernopadding" width="80" nowrap="nowrap">';
			print $langs->trans("RefOrLabel").':</td>';
			print '<td class="nobordernopadding" align="left" width="16">';
			print ajax_indicator($htmlname,'working');
			print '</td>';
			print '<td align="left"><input type="text" size="16" name="keysearch'.$htmlname.'" id="keysearch'.$htmlname.'"> ';
			print '</td>';
			print '</tr>';
			print '<tr class="nocellnopadd">';
			print '<td class="nobordernopadding" colspan="3">';
			print ajax_updater($htmlname,'keysearch','/product/ajaxproducts.php','&price_level='.$price_level.'&type='.$filtertype.'&mode=1&status='.$status.'&finished='.$finished,'');
			print '</td></tr>';
			print '</table>';
		}
		else
		{
			$this->select_produits_do($selected,$htmlname,$filtertype,$limit,$price_level,'',$status,$finished);
		}
	}

	/**
	 *	\brief      Return list of products for a customer
	 *	\param      selected        Preselected product
	 *	\param      htmlname        Name of select html
	 *  \param		filtertype      Filter on product type (''=nofilter, 0=product, 1=service)
	 *	\param      limit           Limite sur le nombre de lignes retournees
	 *	\param      price_level     Level of price to show
	 * 	\param      ajaxkeysearch   Filter on product if ajax is used
	 *	\param		status			-1=Return all products, 0=Products not on sell, 1=Products on sell
	 */
	function select_produits_do($selected='',$htmlname='productid',$filtertype='',$limit=20,$price_level=0,$ajaxkeysearch='',$status=1,$finished=2)
	{
		global $langs,$conf,$user;

		$sql = "SELECT ";
		if ($conf->categorie->enabled && ! $user->rights->categorie->voir)
		{
			$sql.="DISTINCT";
		}
		$sql.= " p.rowid, p.label, p.ref, p.fk_product_type, p.price, p.price_ttc, p.price_base_type, p.duration, p.stock";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p ";
		if ($conf->categorie->enabled && ! $user->rights->categorie->voir)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON p.rowid = cp.fk_product";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON cp.fk_categorie = c.rowid";
		}
		$sql.= " WHERE p.entity = ".$conf->entity;

		if($finished == 0)
		{
			$sql.= " AND p.finished = ".$finished;
		}
		elseif($finished == 1)
		{
			$sql.= " AND p.finished = ".$finished;
			if ($status >= 0)  $sql.= " AND p.envente = ".$status;
		}
		elseif($status >= 0)
		{
			$sql.= " AND p.envente = ".$status;
		}

		if ($conf->categorie->enabled && ! $user->rights->categorie->voir)
		{
			$sql.= ' AND IFNULL(c.visible,1)=1';
		}
		if (strval($filtertype) != '') $sql.=" AND p.fk_product_type=".$filtertype;
		if ($ajaxkeysearch && $ajaxkeysearch != '') $sql.=" AND (p.ref like '%".$ajaxkeysearch."%' OR p.label like '%".$ajaxkeysearch."%')";
		$sql.= " ORDER BY p.ref";
		if ($limit) $sql.= " LIMIT $limit";

		dol_syslog("Form::select_produits_do sql=".$sql, LOG_DEBUG);
		$result=$this->db->query($sql);
		if (! $result) dol_print_error($this->db);

		// Multilang : on construit une liste des traductions des produits listes
		if ($conf->global->MAIN_MULTILANGS)
		{
			$sqld = "SELECT d.fk_product, d.label";
			$sqld.= " FROM ".MAIN_DB_PREFIX."product as p, ".MAIN_DB_PREFIX."product_det as d ";
			$sqld.= " WHERE d.fk_product = p.rowid";
			$sqld.= " AND p.entity = ".$conf->entity;
			$sqld.= " AND p.envente = 1";
			$sqld.= " AND d.lang='". $langs->getDefaultLang() ."'";
			$sqld.= " ORDER BY p.ref";

			dol_syslog("Form::select_produits_do sql=".$sql, LOG_DEBUG);
			$resultd = $this->db->query($sqld);
		}

		if ($result)
		{
			$num = $this->db->num_rows($result);

			if ($conf->use_javascript_ajax)
			{
				if (! $num)
				{
					print '<select class="flat" name="'.$htmlname.'">';
					print '<option value="0">-- '.$langs->trans("NoProductMatching").' --</option>';
				}
				else
				{
					print '<select class="flat" name="'.$htmlname.'"';
					if ($conf->global->PRODUIT_USE_SEARCH_TO_SELECT) print ' onchange="publish_selvalue(this);"';
					print '>';
					print '<option value="0" selected="true">-- '.$langs->trans("MatchingProducts").' --</option>';
				}
			}
			else
			{
				print '<select class="flat" name="'.$htmlname.'">';
				print '<option value="0" selected="true">&nbsp;</option>';
			}

			$i = 0;
			while ($num && $i < $num)
			{
				$objp = $this->db->fetch_object($result);

				// Multilangs : modification des donnees si une traduction existe
				if ($conf->global->MAIN_MULTILANGS)
				{
					if ( $resultd ) $objtp = $this->db->fetch_object($resultd);
					if ( $objp->rowid == $objtp->fk_product ) // si on a une traduction
					{
						if ( $objtp->label != '') $objp->label = $objtp->label;
					}
				}
				$opt = '<option value="'.$objp->rowid.'"';
				$opt.= ($objp->rowid == $selected)?' selected="true"':'';
				$opt.= ($conf->stock->enabled && isset($objp->stock) && $objp->fk_product_type == 0 && $objp->stock <= 0) ? ' style="background-color:#FF0000; color:#FFFFFF"':'';
				$opt.= '>'.$objp->ref.' - ';
				$opt.= dol_trunc($objp->label,32).' - ';

				$found=0;
				$currencytext=$langs->trans("Currency".$conf->monnaie);
				if (strlen($currencytext) > 10) $currencytext=$conf->monnaie;	// If text is too long, we use the short code

				// Multiprice
				if ($price_level >= 1)		// If we need a particular price level (from 1 to 6)
				{
					$sql= "SELECT price, price_ttc, price_base_type ";
					$sql.= "FROM ".MAIN_DB_PREFIX."product_price ";
					$sql.= "WHERE fk_product='".$objp->rowid."'";
					$sql.= " AND price_level=".$price_level;
					$sql.= " ORDER BY date_price";
					$sql.= " DESC limit 1";

					dol_syslog("Form::select_produits_do sql=".$sql);
					$result2 = $this->db->query($sql);
					if ($result2)
					{
						$objp2 = $this->db->fetch_object($result2);
						if ($objp2)
						{
							$found=1;
							if ($objp2->price_base_type == 'HT')
							$opt.= price($objp2->price,1).' '.$currencytext.' '.$langs->trans("HT");
							else
							$opt.= price($objp2->price_ttc,1).' '.$currencytext.' '.$langs->trans("TTC");
						}
					}
					else
					{
						dol_print_error($this->db);
					}
				}

				// If level no defined or multiprice not found, we used the default price
				if (! $found)
				{
					if ($objp->price_base_type == 'HT')
					$opt.= price($objp->price,1).' '.$currencytext.' '.$langs->trans("HT");
					else
					$opt.= price($objp->price_ttc,1).' '.$currencytext.' '.$langs->trans("TTC");
				}

				if ($conf->stock->enabled && isset($objp->stock) && $objp->fk_product_type == 0)
				{
					$opt.= ' - '.$langs->trans("Stock").':'.$objp->stock;
				}

				if ($objp->duration)
				{
					$duration_value = substr($objp->duration,0,strlen($objp->duration)-1);
					$duration_unit = substr($objp->duration,-1);
					if ($duration_value > 1)
					{
						$dur=array("h"=>$langs->trans("Hours"),"d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
					}
					else
					{
						$dur=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
					}
					$opt.= ' - '.$duration_value.' '.$langs->trans($dur[$duration_unit]);
				}

				$opt.= "</option>\n";
				print $opt;
				$i++;
			}

			print '</select>';

			$this->db->free($result);
		}
		else
		{
			dol_print_error($db);
		}
	}

	/**
	 *	\brief     	Return list of products for customer in Ajax if Ajax activated or go to select_produits_fournisseurs_do
	 *	\param		socid			Id third party
	 *	\param     	selected        Preselected product
	 *	\param     	htmlname        Name of HTML Select
	 *  \param		filtertype      Filter on product type (''=nofilter, 0=product, 1=service)
	 *	\param     	filtre          For a SQL filter
	 */
	function select_produits_fournisseurs($socid,$selected='',$htmlname='productid',$filtertype='',$filtre)
	{
		global $langs,$conf;
		if ($conf->global->PRODUIT_USE_SEARCH_TO_SELECT)
		{
			print $langs->trans("RefOrLabel").' : <input type="text" size="16" name="keysearch'.$htmlname.'" id="keysearch'.$htmlname.'">';
			print ajax_updater($htmlname,'keysearch','/product/ajaxproducts.php','&socid='.$socid.'&type='.$filtertype.'&mode=2','working');
		}
		else
		{
			$this->select_produits_fournisseurs_do($socid,$selected,$htmlname,$filtertype,$filtre,'');
		}
	}

	/**
	 *	\brief      Retourne la liste des produits de fournisseurs
	 *	\param		socid   		Id societe fournisseur (0 pour aucun filtre)
	 *	\param      selected        Produit pre-selectionne
	 *	\param      htmlname        Nom de la zone select
	 *  \param		filtertype      Filter on product type (''=nofilter, 0=product, 1=service)
	 *	\param      filtre          Pour filtre sql
	 *	\param      ajaxkeysearch   Filtre des produits si ajax est utilise
	 */
	function select_produits_fournisseurs_do($socid,$selected='',$htmlname='productid',$filtertype='',$filtre='',$ajaxkeysearch='')
	{
		global $langs,$conf;

		$langs->load('stocks');

		$sql = "SELECT p.rowid, p.label, p.ref, p.price, p.duration,";
		$sql.= " pf.ref_fourn,";
		$sql.= " pfp.rowid as idprodfournprice, pfp.price as fprice, pfp.quantity, pfp.unitprice,";
		$sql.= " s.nom";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur as pf ON p.rowid = pf.fk_product";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON pf.fk_soc = s.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON pf.rowid = pfp.fk_product_fournisseur";
		$sql.= " WHERE p.entity = ".$conf->entity;
		$sql.= " AND p.envente = 1";
		if ($socid) $sql.= " AND pf.fk_soc = ".$socid;
		if (strval($filtertype) != '') $sql.=" AND p.fk_product_type=".$filtertype;
		if (! empty($filtre)) $sql.=" ".$filtre;
		if ($ajaxkeysearch && $ajaxkeysearch != '') $sql.=" AND (pf.ref_fourn like '%".$ajaxkeysearch."%' OR p.ref like '%".$ajaxkeysearch."%' OR p.label like '%".$ajaxkeysearch."%')";
		$sql.= " ORDER BY pf.ref_fourn DESC";

		dol_syslog("Form::select_produits_fournisseurs_do sql=".$sql,LOG_DEBUG);
		$result=$this->db->query($sql);
		if ($result)
		{

			$num = $this->db->num_rows($result);

			if ($conf->use_javascript_ajax)
			{
				if (! $num)
				{
					print '<select class="flat" name="'.$htmlname.'">';
					print '<option value="0">-- '.$langs->trans("NoProductMatching").' --</option>';
				}
				else
				{
					print '<select class="flat" name="'.$htmlname.'" onchange="publish_selvalue(this);">';
					print '<option value="0" selected="true">-- '.$langs->trans("MatchingProducts").' --</option>';
				}
			}
			else
			{
				print '<select class="flat" name="'.$htmlname.'">';
				if (! $selected) print '<option value="0" selected="true">&nbsp;</option>';
				else print '<option value="0">&nbsp;</option>';
			}

			$i = 0;
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($result);

				$opt = '<option value="'.$objp->idprodfournprice.'"';
				if ($selected == $objp->idprodfournprice) $opt.= ' selected="true"';
				if ($objp->fprice == '') $opt.=' disabled="disabled"';
				$opt.= '>'.$objp->ref.' ('.$objp->ref_fourn.') - ';
				$opt.= dol_trunc($objp->label,18).' - ';
				if ($objp->fprice != '') 	// Keep != ''
				{
					$currencytext=$langs->trans("Currency".$conf->monnaie);
					if (strlen($currencytext) > 10) $currencytext=$conf->monnaie;	// If text is too long, we use the short code

					$opt.= price($objp->fprice);
					$opt.= ' '.$currencytext."/".$objp->quantity;
					if ($objp->quantity == 1)
					{
						$opt.= strtolower($langs->trans("Unit"));
					}
					else
					{
						$opt.= strtolower($langs->trans("Units"));
					}
					if ($objp->quantity >= 1)
					{
						$opt.=" (";
						$opt.= price($objp->unitprice).' '.$currencytext."/".strtolower($langs->trans("Unit"));
						$opt.=")";
					}
					if ($objp->duration) $opt .= " - ".$objp->duration;
					if (! $socid) $opt .= " - ".dol_trunc($objp->nom,8);
				}
				else
				{
					$opt.= $langs->trans("NoPriceDefinedForThisSupplier");
				}
				$opt .= "</option>\n";

				print $opt;
				$i++;
			}
			print '</select>';

			$this->db->free($result);
		}
		else
		{
			dol_print_error($db);
		}
	}

	/**
		\brief		Retourne la liste des tarifs fournisseurs pour un produit
		\param		productid   		    Id product
		*/
	function select_product_fourn_price($productid,$htmlname='productfournpriceid')
	{
		global $langs,$conf;

		$langs->load('stocks');

		$sql = "SELECT p.rowid, p.label, p.ref, p.price, p.duration,";
		$sql.= " pf.ref_fourn,";
		$sql.= " pfp.rowid as idprodfournprice, pfp.price as fprice, pfp.quantity, pfp.unitprice,";
		$sql.= " s.nom";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur as pf ON p.rowid = pf.fk_product";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = pf.fk_soc";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON pf.rowid = pfp.fk_product_fournisseur";
		$sql.= " WHERE p.envente = 1";
		$sql.= " AND s.fournisseur = 1";
		$sql.= " AND p.rowid = ".$productid;
		$sql.= " ORDER BY s.nom, pf.ref_fourn DESC";

		dol_syslog("Form::select_product_fourn_price sql=".$sql,LOG_DEBUG);
		$result=$this->db->query($sql);

		if ($result)
		{
			$num = $this->db->num_rows($result);

			$form = '<select class="flat" name="'.$htmlname.'">';

			if (! $num)
			{
				$form.= '<option value="0">-- '.$langs->trans("NoSupplierPriceDefinedForThisProduct").' --</option>';
			}
			else
			{
				$form.= '<option value="0">&nbsp;</option>';

				$i = 0;
				while ($i < $num)
				{
					$objp = $this->db->fetch_object($result);

					$opt = '<option value="'.$objp->idprodfournprice.'"';
					$opt.= '>'.$objp->nom.' - '.$objp->ref_fourn.' - ';

					if ($objp->quantity == 1)
					{
						$opt.= price($objp->fprice);
						$opt.= $langs->trans("Currency".$conf->monnaie)."/";
					}

					$opt.= $objp->quantity.' ';

					if ($objp->quantity == 1)
					{
						$opt.= strtolower($langs->trans("Unit"));
					}
					else
					{
						$opt.= strtolower($langs->trans("Units"));
					}
					if ($objp->quantity > 1)
					{
						$opt.=" - ";
						$opt.= price($objp->unitprice).$langs->trans("Currency".$conf->monnaie)."/".strtolower($langs->trans("Unit"));
					}
					if ($objp->duration) $opt .= " - ".$objp->duration;
					$opt .= "</option>\n";

					$form.= $opt;
					$i++;
				}
				$form.= '</select>';

				$this->db->free($result);
			}
			return $form;
		}
		else
		{
			dol_print_error($db);
		}
	}

	/**
	 *    \brief      Retourne la liste deroulante des adresses de livraison
	 *    \param      selected        Id contact pre-selectionn
	 *    \param      htmlname        Nom champ formulaire
	 */
	function select_adresse_livraison($selected='', $socid, $htmlname='adresse_livraison_id',$showempty=0)
	{
		// On recherche les utilisateurs
		$sql = "SELECT a.rowid, a.label";
		$sql .= " FROM ".MAIN_DB_PREFIX ."societe_adresse_livraison as a";
		$sql .= " WHERE a.fk_societe = ".$socid;
		$sql .= " ORDER BY a.label ASC";

		dol_syslog("Form::select_adresse_livraison sql=".$sql);
		if ($this->db->query($sql))
		{
			print '<select class="flat" name="'.$htmlname.'">';
			if ($showempty) print '<option value="0">&nbsp;</option>';
			$num = $this->db->num_rows();
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object();

					if ($selected && $selected == $obj->rowid)
					{
						print '<option value="'.$obj->rowid.'" selected="true">'.$obj->label.'</option>';
					}
					else
					{
						print '<option value="'.$obj->rowid.'">'.$obj->label.'</option>';
					}
					$i++;
				}
			}
			print '</select>';
			return $num;
		}
		else
		{
			dol_print_error($this->db);
		}
	}


	/**
	 *      \brief      Charge dans cache la liste des conditions de paiements possibles
	 *      \return     int             Nb lignes chargees, 0 si deja chargees, <0 si ko
	 */
	function load_cache_conditions_paiements()
	{
		global $langs;

		if (sizeof($this->cache_conditions_paiements)) return 0;    // Cache deja charge

		$sql = "SELECT rowid, code, libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."cond_reglement";
		$sql.= " WHERE active=1";
		$sql.= " ORDER BY sortorder";
		dol_syslog('Form::load_cache_conditions_paiements sql='.$sql,LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
				$libelle=($langs->trans("PaymentConditionShort".$obj->code)!=("PaymentConditionShort".$obj->code)?$langs->trans("PaymentConditionShort".$obj->code):($obj->libelle!='-'?$obj->libelle:''));
				$this->cache_conditions_paiements[$obj->rowid]['code'] =$obj->code;
				$this->cache_conditions_paiements[$obj->rowid]['label']=$libelle;
				$i++;
			}
			return 1;
		}
		else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *      \brief      Charge dans cache la liste des types de paiements possibles
	 *      \return     int             Nb lignes chargees, 0 si deja chargees, <0 si ko
	 */
	function load_cache_types_paiements()
	{
		global $langs;

		if (sizeof($this->cache_types_paiements)) return 0;    // Cache deja charge

		$sql = "SELECT id, code, libelle, type";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_paiement";
		$sql.= " WHERE active > 0";
		$sql.= " ORDER BY id";
		dol_syslog('Form::load_cache_types_paiements sql='.$sql,LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
				$libelle=($langs->trans("PaymentTypeShort".$obj->code)!=("PaymentTypeShort".$obj->code)?$langs->trans("PaymentTypeShort".$obj->code):($obj->libelle!='-'?$obj->libelle:''));
				$this->cache_types_paiements[$obj->id]['code'] =$obj->code;
				$this->cache_types_paiements[$obj->id]['label']=$libelle;
				$this->cache_types_paiements[$obj->id]['type'] =$obj->type;
				$i++;
			}
			return $num;
		}
		else {
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *      \brief      Retourne la liste des types de paiements possibles
	 *      \param      selected        Id du type de paiement pre-selectionne
	 *      \param      htmlname        Nom de la zone select
	 *      \param      filtertype      Pour filtre
	 *		\param		addempty		Ajoute entree vide
	 */
	function select_conditions_paiements($selected='',$htmlname='condid',$filtertype=-1,$addempty=0)
	{
		global $langs,$user;

		$this->load_cache_conditions_paiements();

		print '<select class="flat" name="'.$htmlname.'">';
		if ($addempty) print '<option value="0">&nbsp;</option>';
		foreach($this->cache_conditions_paiements as $id => $arrayconditions)
		{
			if ($selected == $id)
			{
				print '<option value="'.$id.'" selected="true">';
			}
			else
			{
				print '<option value="'.$id.'">';
			}
			print $arrayconditions['label'];
			print '</option>';
		}
		print '</select>';
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
	}


	/**
	 *      \brief      Retourne la liste des modes de paiements possibles
	 *      \param      selected        Id du mode de paiement pre-selectionne
	 *      \param      htmlname        Nom de la zone select
	 *      \param      filtertype      Pour filtre
	 *      \param      format          0=id+libelle, 1=code+code, 2=code+libelle
	 *      \param      empty			1=peut etre vide, 0 sinon
	 * 		\param		noadmininfo		0=Add admin info, 1=Disable admin info
	 */
	function select_types_paiements($selected='',$htmlname='paiementtype',$filtertype='',$format=0, $empty=0, $noadmininfo=0)
	{
		global $langs,$user;

		dol_syslog("Form::select_type_paiements $selected, $htmlname, $filtertype, $format",LOG_DEBUG);

		$filterarray=array();
		if ($filtertype == 'CRDT')  	$filterarray=array(0,2);
		elseif ($filtertype == 'DBIT') 	$filterarray=array(1,2);
		elseif ($filtertype != '' && $filtertype != '-1') $filterarray=split(',',$filtertype);

		$this->load_cache_types_paiements();

		print '<select class="flat" name="'.$htmlname.'">';
		if ($empty) print '<option value="">&nbsp;</option>';
		foreach($this->cache_types_paiements as $id => $arraytypes)
		{
			// On passe si on a demand� de filtrer sur des modes de paiments particuliers
			if (sizeof($filterarray) && ! in_array($arraytypes['type'],$filterarray)) continue;

			if ($format == 0) print '<option value="'.$id.'"';
			if ($format == 1) print '<option value="'.$arraytypes['code'].'"';
			if ($format == 2) print '<option value="'.$arraytypes['code'].'"';
			// Si selected est text, on compare avec code, sinon avec id
			if (eregi('[a-z]', $selected) && $selected == $arraytypes['code']) print ' selected="true"';
			elseif ($selected == $id) print ' selected="true"';
			print '>';
			if ($format == 0) $value=$arraytypes['label'];
			if ($format == 1) $value=$arraytypes['code'];
			if ($format == 2) $value=$arraytypes['label'];
			print $value?$value:'&nbsp;';
			print '</option>';
		}
		print '</select>';
		if ($user->admin && ! $noadmininfo) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
	}

	/**
	 *      \brief      Selection HT ou TTC
	 *      \param      selected        Id pre-selectionne
	 *      \param      htmlname        Nom de la zone select
	 */
	function select_PriceBaseType($selected='',$htmlname='price_base_type')
	{
		global $langs;
		print '<select class="flat" name="'.$htmlname.'">';
		$options = array(
					'HT'=>$langs->trans("HT"),
					'TTC'=>$langs->trans("TTC")
		);
		foreach($options as $id => $value)
		{
			if ($selected == $id)
			{
				print '<option value="'.$id.'" selected="true">'.$value;
			}
			else
			{
				print '<option value="'.$id.'">'.$value;
			}
			print '</option>';
		}
		print '</select>';
	}

	/**
	 *    \brief      Retourne la liste deroulante des differents etats d'une propal.
	 *                Les valeurs de la liste sont les id de la table c_propalst
	 *    \param      selected    etat pre-selectionne
	 */
	function select_propal_statut($selected='')
	{
		$sql = "SELECT id, code, label, active FROM ".MAIN_DB_PREFIX."c_propalst";
		$sql .= " WHERE active = 1";

		dol_syslog("Form::select_propal_statut sql=".$sql);
		if ($this->db->query($sql))
		{
			print '<select class="flat" name="propal_statut">';
			print '<option value="">&nbsp;</option>';
			$num = $this->db->num_rows();
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object();
					if ($selected == $obj->id)
					{
						print '<option value="'.$obj->id.'" selected="true">';
					}
					else
					{
						print '<option value="'.$obj->id.'">';
					}
					// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
					//print ($langs->trans("Civility".$obj->code)!="Civility".$obj->code ? $langs->trans("Civility".$obj->code) : ($obj->civilite!='-'?$obj->civilite:''));
					print $obj->label;
					print '</option>';
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
	 *    \brief      Retourne la liste des comptes
	 *    \param      selected          Id compte pre-selectionne
	 *    \param      htmlname          Nom de la zone select
	 *    \param      statut            Statut des comptes recherches (0=open, 1=closed)
	 *    \param      filtre            Pour filtre sur la liste
	 *    \param      useempty          Affiche valeur vide dans liste
	 */
	function select_comptes($selected='',$htmlname='accountid',$statut=0,$filtre='',$useempty=0)
	{
		global $langs, $conf;

		$langs->load("admin");

		$sql = "SELECT rowid, label, bank";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
		$sql.= " WHERE clos = '".$statut."'";
		$sql.= " AND entity = ".$conf->entity;
		if ($filtre) $sql.=" AND ".$filtre;
		$sql.= " ORDER BY rowid";

		dol_syslog("Form::select_comptes sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num)
			{
				print '<select class="flat" name="'.$htmlname.'">';
				if ($useempty)
				{
					print '<option value="'.$obj->rowid.'">&nbsp;</option>';
				}

				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					if ($selected == $obj->rowid)
					{
						print '<option value="'.$obj->rowid.'" selected="true">';
					}
					else
					{
						print '<option value="'.$obj->rowid.'">';
					}
					print $obj->label;
					print '</option>';
					$i++;
				}
				print "</select>";
			}
			else
			{
				print $langs->trans("NoActiveBankAccountDefined");
			}
		}
		else {
			dol_print_error($this->db);
		}
	}

	/**
	 *    \brief    Retourne la liste des categories du type choisi
	 *    \param    type			Type de categories (0=produit, 1=fournisseur, 2=client)
	 *    \param    selected    	Id categorie preselectionnee
	 *    \param    select_name		Nom formulaire HTML
	 */
	function select_all_categories($type,$selected='',$select_name="",$maxlength=64)
	{
		global $langs;
		$langs->load("categories");

		if ($select_name=="") $select_name="catMere";

		$cat = new Categorie($this->db);
		$cate_arbo = $cat->get_full_arbo($type);

		$output = '<select class="flat" name="'.$select_name.'">';
		if (is_array($cate_arbo))
		{
			if (! sizeof($cate_arbo)) $output.= '<option value="-1" disabled="true">'.$langs->trans("NoCategoriesDefined").'</option>';
			else
			{
				$output.= '<option value="-1">&nbsp;</option>';
				foreach($cate_arbo as $key => $value)
				{
					if ($cate_arbo[$key]['id'] == $selected)
					{
						$add = 'selected="true" ';
					}
					else
					{
						$add = '';
					}
					$output.= '<option '.$add.'value="'.$cate_arbo[$key]['id'].'">'.dol_trunc($cate_arbo[$key]['fulllabel'],$maxlength,'middle').'</option>';
				}
			}
		}
		$output.= '</select>';
		$output.= "\n";
		return $output;
	}


	/**
	 *    	\brief  Show a confirmation HTML form or AJAX popup
	 *    	\param  page        	page		Url of page to call if confirmation is OK
	 *    	\param  title       	title
	 *    	\param  question    	question
	 *    	\param  action      	action
	 *		\param	formquestion	an array with forms complementary inputs
	 * 		\param	selectedchoice	"" or "no" or "yes"
	 * 		\param	useajax			0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No
	 * 		\param	string			'ajax' if a confirm ajax popup is shown, 'html' if it's an html form
	 */
	function form_confirm($page, $title, $question, $action, $formquestion='', $selectedchoice="", $useajax=0)
	{
		global $langs,$conf;

		$more='';
		if ($formquestion)
		{
			$more.='<tr class="valid"><td class="valid" colspan="3">';
			$more.='<table class="nobordernopadding" width="100%">';
			$more.='<tr><td colspan="3" valign="top">'.$formquestion['text'].'</td></tr>';
			foreach ($formquestion as $key => $input)
			{
				if ($input['type'] == 'text')
				{
					$more.='<tr><td valign="top">'.$input['label'].'</td><td colspan="2"><input type="text" class="flat" name="'.$input['name'].'" size="'.$input['size'].'" value="'.$input['value'].'"></td></tr>';
				}
				if ($input['type'] == 'select')
				{
					$more.='<tr><td valign="top">';
					$more.=$this->selectarray($input['name'],$input['values'],'',1);
					$more.='</td></tr>';
				}
				if ($input['type'] == 'checkbox')
				{
					$more.='<tr>';
					$more.='<td valign="top">'.$input['label'].' &nbsp;';
					$more.='<input type="checkbox" class="flat" name="'.$input['name'].'"';
					if ($input['value'] != 'false') $more.=' checked="true"';
					if ($input['disabled']) $more.=' disabled="true"';
					$more.='></td>';
					$more.='<td valign="top" align="left">&nbsp;</td>';
					$more.='<td valign="top" align="left">&nbsp;</td>';
					$more.='</tr>';
				}
				if ($input['type'] == 'radio')
				{
					$i=0;
					foreach($input['values'] as $selkey => $selval)
					{
						$more.='<tr>';
						if ($i==0) $more.='<td valign="top">'.$input['label'].'</td>';
						else $more.='<td>&nbsp;</td>';
						$more.='<td valign="top" width="20"><input type="radio" class="flat" name="'.$input['name'].'" value="'.$selkey.'"';
						if ($input['disabled']) $more.=' disabled="true"';
						$more.='></td>';
						$more.='<td valign="top" align="left">';
						$more.=$selval;
						$more.='</td></tr>';
						$i++;
					}
				}
			}
			$more.='</table>';
			$more.='</td></tr>';
		}

		print "\n<!-- begin form_confirm -->\n";

		if ($useajax && $conf->use_javascript_ajax && $conf->global->MAIN_CONFIRM_AJAX)
		{
			$pageyes=$page.'&action='.$action.'&confirm=yes';
			$pageno=($useajax == 2?$page.'&confirm=no':'');
			// Note: Title is not used by dialogConfirm function
			print '<script type="text/javascript">window.onload = function(){ dialogConfirm(\''.$title.'\',\''.$pageyes.'\',\''.$pageno.'\',\''.dol_escape_js('<b>'.$title.'</b><br>'.$more.$question).'\',\''.$langs->trans("Yes").'\',\''.$langs->trans("No").'\',\'validate\'); }</script>';
//			print '<script id="eee" type="text/javascript">dialogConfirm(\'aaa\',\'bbb\',\'ccc\',\'eee\',\'yyy\',\'zzz\',\'validate\')</script>';

			print "\n";
			$ret='ajax';
		}
		else
		{
			print '<form method="post" action="'.$page.'" class="notoptoleftroright">';
			print '<input type="hidden" name="action" value="'.$action.'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

			print '<table width="100%" class="valid">';

			// Ligne titre
			print '<tr class="validtitre"><td class="validtitre" colspan="3">'.img_picto('','recent').' '.$title.'</td></tr>';

			// Ligne formulaire
			print $more;

			// Ligne message
			print '<tr class="valid">';
			print '<td class="valid">'.$question.'</td>';
			print '<td class="valid">';
			$newselectedchoice=empty($selectedchoice)?"no":$selectedchoice;
			print $this->selectyesno("confirm",$newselectedchoice);
			print '</td>';
			print '<td class="valid" align="center"><input class="button" type="submit" value="'.$langs->trans("Validate").'"></td>';
			print '</tr>';

			print '</table>';

			if (is_array($formquestion))
			{
				foreach ($formquestion as $key => $input)
				{
					if ($input['type'] == 'hidden') print '<input type="hidden" name="'.$input['name'].'" value="'.$input['value'].'">';
				}
			}

			print "</form>\n";
			$ret='html';
		}

		print "<!-- end form_confirm -->\n";
		return $ret;
	}


	/**
	 *    \brief      Affiche formulaire de selection de projet
	 *    \param      page        Page
	 *    \param      socid       Id societe
	 *    \param      selected    Id projet pre-selectionne
	 *    \param      htmlname    Nom du formulaire select
	 */
	function form_project($page, $socid, $selected='', $htmlname='projectid')
	{
		global $langs;

		require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");

		$langs->load("project");
		if ($htmlname != "none")
		{
			print '<form method="post" action="'.$page.'">';
			print '<input type="hidden" name="action" value="classin">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			select_projects($socid,$selected,$htmlname);
			print '</td>';
			print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
			print '</tr></table></form>';
		}
		else
		{
			if ($selected) {
				$projet = new Project($this->db);
				$projet->fetch($selected);
				//print '<a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$selected.'">'.$projet->title.'</a>';
				print $projet->getNomUrl(0);
			} else {
				print "&nbsp;";
			}
		}
	}

	/**
	 *    	\brief      Affiche formulaire de selection de conditions de paiement
	 *    	\param      page        	Page
	 *    	\param      selected    	Id condition pre-selectionne
	 *    	\param      htmlname    	Name of select html field
	 *		\param		addempty		Ajoute entree vide
	 */
	function form_conditions_reglement($page, $selected='', $htmlname='cond_reglement_id', $addempty=0)
	{
		global $langs;
		if ($htmlname != "none")
		{
			print '<form method="post" action="'.$page.'">';
			print '<input type="hidden" name="action" value="setconditions">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			$this->select_conditions_paiements($selected,$htmlname,-1,$addempty);
			print '</td>';
			print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
			print '</tr></table></form>';
		}
		else
		{
			if ($selected)
			{
				$this->load_cache_conditions_paiements();
				print $this->cache_conditions_paiements[$selected]['label'];
			} else {
				print "&nbsp;";
			}
		}
	}


	/**
	 *    \brief      Affiche formulaire de selection d'une date
	 *    \param      page        Page
	 *    \param      selected    Date preselected
	 *    \param      htmlname    Name of input html field
	 */
	function form_date($page, $selected='', $htmlname)
	{
		global $langs;

		if ($htmlname != "none")
		{
			print '<form method="post" action="'.$page.'" name="form'.$htmlname.'">';
			print '<input type="hidden" name="action" value="set'.$htmlname.'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			print $this->select_date($selected,$htmlname,0,0,1,'form'.$htmlname);
			print '</td>';
			print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
			print '</tr></table></form>';
		}
		else
		{
			if ($selected)
			{
				$this->load_cache_types_paiements();
				print $this->cache_types_paiements[$selected]['label'];
			} else {
				print "&nbsp;";
			}
		}
	}


	/**
	 *    	\brief      Affiche formulaire de selection d'un utilisateur
	 *    	\param      page        	Page
	 *   	\param      selected    	Id of user preselected
	 *    	\param      htmlname    	Name of input html field
	 *  	\param      exclude         List of users id to exclude
	 *  	\param      include         List of users id to include
	 */
	function form_users($page, $selected='', $htmlname='userid', $exclude='', $include='')
	{
		global $langs;

		if ($htmlname != "none")
		{
			print '<form method="post" action="'.$page.'" name="form'.$htmlname.'">';
			print '<input type="hidden" name="action" value="set'.$htmlname.'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			print $this->select_users($selected,$htmlname,1,$exclude,0,$include);
			print '</td>';
			print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
			print '</tr></table></form>';
		}
		else
		{
			if ($selected)
			{
				require_once(DOL_DOCUMENT_ROOT ."/user.class.php");
				//$this->load_cache_contacts();
				//print $this->cache_contacts[$selected];
				$theuser=new User($this->db);
				$theuser->id=$selected;
				$theuser->fetch();
				print $theuser->getNomUrl(1);
			} else {
				print "&nbsp;";
			}
		}
	}


	/**
	 *    \brief      Affiche formulaire de selection des modes de reglement
	 *    \param      page        Page
	 *    \param      selected    Id mode pre-selectionne
	 *    \param      htmlname    Name of select html field
	 */
	function form_modes_reglement($page, $selected='', $htmlname='mode_reglement_id')
	{
		global $langs;
		if ($htmlname != "none")
		{
			print '<form method="post" action="'.$page.'">';
			print '<input type="hidden" name="action" value="setmode">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			$this->select_types_paiements($selected,$htmlname);
			print '</td>';
			print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
			print '</tr></table></form>';
		}
		else
		{
			if ($selected)
			{
				$this->load_cache_types_paiements();
				print $this->cache_types_paiements[$selected]['label'];
			} else {
				print "&nbsp;";
			}
		}
	}


	/**
	 *    	\brief      Affiche formulaire de selection de la remise fixe
	 *    	\param      page        	Page URL where form is shown
	 *    	\param      selected    	Value pre-selected
	 *		\param      htmlname    	Nom du formulaire select. Si none, non modifiable
	 *		\param		socid			Third party id
	 * 		\param		amount			Total amount available
	 * 	  	\param		filter			SQL filter on discounts
	 * 	  	\param		maxvalue		Max value for lines that can be selected
	 */
	function form_remise_dispo($page, $selected='', $htmlname='remise_id',$socid, $amount, $filter='', $maxvalue=0)
	{
		global $conf,$langs;
		if ($htmlname != "none")
		{
			print '<form method="post" action="'.$page.'">';
			print '<input type="hidden" name="action" value="setabsolutediscount">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			if (! $filter || $filter=='fk_facture_source IS NULL') print $langs->trans("CompanyHasAbsoluteDiscount",price($amount),$langs->transnoentities("Currency".$conf->monnaie)).': ';
			else print $langs->trans("CompanyHasCreditNote",price($amount),$langs->transnoentities("Currency".$conf->monnaie)).': ';
			//			print $langs->trans("AvailableGlobalDiscounts").': ';
			$newfilter='fk_facture IS NULL AND fk_facture_line IS NULL';	// Remises disponibles
			if ($filter) $newfilter.=' AND '.$filter;
			$nbqualifiedlines=$this->select_remises('',$htmlname,$newfilter,$socid,$maxvalue);
			print '</td>';
			print '<td align="left">';
			if ($nbqualifiedlines > 0)
			{
				print ' &nbsp; <input type="submit" class="button" value="';
				if (! $filter || $filter=='fk_facture_source IS NULL') print $langs->trans("UseDiscount");
				else print $langs->trans("UseCredit");
				print '" title="'.$langs->trans("UseCreditNoteInInvoicePayment").'">';
			}
			print '</td>';
			print '</tr></table></form>';
		}
		else
		{
			if ($selected)
			{
				print $selected;
			}
			else
			{
				print "0";
			}
		}
	}


	/**
	 *    \brief      Affiche formulaire de selection des contacts
	 *    \param      page        Page
	 *    \param      selected    Id contact pre-selectionne
	 *    \param      htmlname    Nom du formulaire select
	 */
	function form_contacts($page, $societe, $selected='', $htmlname='contactidp')
	{
		global $langs;
		if ($htmlname != "none")
		{
			print '<form method="post" action="'.$page.'">';
			print '<input type="hidden" name="action" value="set_contact">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			$num=$this->select_contacts($societe->id, $selected, $htmlname);
			if ($num==0)
			{
				print '<font class="error">Cette societe n\'a pas de contact, veuillez en cr�er un avant de faire votre proposition commerciale</font><br>';
				print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?socid='.$societe->id.'&amp;action=create&amp;backtoreferer=1">'.$langs->trans('AddContact').'</a>';
			}
			print '</td>';
			print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
			print '</tr></table></form>';
		}
		else
		{
			if ($selected)
			{
				require_once(DOL_DOCUMENT_ROOT ."/contact.class.php");
				//$this->load_cache_contacts();
				//print $this->cache_contacts[$selected];
				$contact=new Contact($this->db);
				$contact->fetch($selected);
				print $contact->nom.' '.$contact->prenom;
			} else {
				print "&nbsp;";
			}
		}
	}

	/**
	 *    	\brief      Affiche formulaire de selection de l'adresse de livraison
	 *    	\param      page        	Page
	 *    	\param      selected    	Id condition pre-selectionne
	 *    	\param      htmlname    	Nom du formulaire select
	 *		\param		origin        	Origine de l'appel pour pouvoir creer un retour
	 *      \param      originid      	Id de l'origine
	 */
	function form_adresse_livraison($page, $selected='', $socid, $htmlname='adresse_livraison_id', $origin='', $originid='')
	{
		global $langs,$conf;
		if ($htmlname != "none")
		{
			print '<form method="post" action="'.$page.'">';
			print '<input type="hidden" name="action" value="setdeliveryadress">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			$this->select_adresse_livraison($selected, $socid, $htmlname, 1);
			print '</td>';
			print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'">';
			$langs->load("companies");
			print ' &nbsp; <a href='.DOL_URL_ROOT.'/comm/adresse_livraison.php?socid='.$socid.'&action=create&origin='.$origin.'&originid='.$originid.'>'.$langs->trans("AddAddress").'</a>';
			print '</td></tr></table></form>';
		}
		else
		{
			if ($selected)
			{
				require_once(DOL_DOCUMENT_ROOT ."/comm/adresse_livraison.class.php");
				$livraison=new AdresseLivraison($this->db);
				$result=$livraison->fetch_adresse($selected);
				print '<a href='.DOL_URL_ROOT.'/comm/adresse_livraison.php?socid='.$livraison->socid.'&id='.$livraison->id.'&action=edit&origin='.$origin.'&originid='.$originid.'>'.$livraison->label.'</a>';
			}
			else
			{
				print "&nbsp;";
			}
		}
	}

	/**
	 *    \brief     Retourne la liste des devises, dans la langue de l'utilisateur
	 *    \param     selected    code devise pre-selectionne
	 *    \param     htmlname    nom de la liste deroulante
	 *    \todo      trier liste sur noms apres traduction plutot que avant
	 */
	function select_currency($selected='',$htmlname='currency_id')
	{
		global $conf,$langs,$user;
		$langs->load("dict");

		if ($selected=='euro' || $selected=='euros') $selected='EUR';   // Pour compatibilite

		$sql = "SELECT code, code_iso, label, active";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_currencies";
		$sql.= " WHERE active = 1";
		$sql.= " ORDER BY code_iso ASC";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			print '<select class="flat" name="'.$htmlname.'">';
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				$foundselected=false;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					if ($selected && $selected == $obj->code_iso)
					{
						$foundselected=true;
						print '<option value="'.$obj->code_iso.'" selected="true">';
					}
					else
					{
						print '<option value="'.$obj->code_iso.'">';
					}
					if ($obj->code_iso) { print $obj->code_iso . ' - '; }
					// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
					print ($obj->code_iso && $langs->trans("Currency".$obj->code_iso)!="Currency".$obj->code_iso?$langs->trans("Currency".$obj->code_iso):($obj->label!='-'?$obj->label:''));
					print '</option>';
					$i++;
				}
			}
			print '</select>';
			if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
			return 0;
		}
		else {
			dol_print_error($this->db);
			return 1;
		}
	}


	/**
	 *      \brief      Output an HTML select vat rate
	 *      \param      name                Nom champ html
	 *      \param      selectedrate        Forcage du taux tva pre-selectionne. Mettre '' pour aucun forcage.
	 *      \param      societe_vendeuse    Objet societe vendeuse
	 *      \param      societe_acheteuse   Objet societe acheteuse
	 *      \param      taux_produit        Taux par defaut du produit vendu
	 *      \param      info_bits           Miscellanous information on line
	 *      \remarks    Si vendeur non assujeti a TVA, TVA par defaut=0. Fin de regle.
	 *                  Si le (pays vendeur = pays acheteur) alors la TVA par defaut=TVA du produit vendu. Fin de regle.
	 *                  Si (vendeur et acheteur dans Communaute europeenne) et bien vendu = moyen de transports neuf (auto, bateau, avion), TVA par defaut=0 (La TVA doit etre paye par l'acheteur au centre d'impots de son pays et non au vendeur). Fin de regle.
	 *                  Si (vendeur et acheteur dans Communaute europeenne) et bien vendu autre que transport neuf alors la TVA par defaut=TVA du produit vendu. Fin de regle.
	 *                  Sinon la TVA proposee par defaut=0. Fin de regle.
	 */
	function select_tva($name='tauxtva', $selectedrate='', $societe_vendeuse='', $societe_acheteuse='', $taux_produit='', $info_bits=0)
	{
		global $langs,$conf,$mysoc;

		$txtva=array();
		$libtva=array();
		$nprtva=array();

		// Define defaultnpr and defaultttx
		$defaultnpr=($info_bits & 0x01);
		$defaultnpr=(eregi('\*',$selectedrate) ? 1 : $defaultnpr);
		$defaulttx=eregi_replace('\*','',$selectedrate);

		//print $societe_vendeuse."-".$societe_acheteuse;
		if (is_object($societe_vendeuse) && ! $societe_vendeuse->pays_code)
		{
			if ($societe_vendeuse->id == $mysoc->id)
			{
				print '<font class="error">'.$langs->trans("ErrorYourCountryIsNotDefined").'</div>';
			}
			else
			{
				print '<font class="error">'.$langs->trans("ErrorSupplierCountryIsNotDefined").'</div>';
			}
			return;
		}

		if (is_object($societe_vendeuse))
		{
			$code_pays=$societe_vendeuse->pays_code;
		}
		else
		{
			$code_pays=$mysoc->pays_code;	// Pour compatibilite ascendente
		}

		// Recherche liste des codes TVA du pays vendeur
		$sql  = "SELECT t.taux,t.recuperableonly";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_pays as p";
		$sql .= " WHERE t.fk_pays = p.rowid AND p.code = '".$code_pays."'";
		$sql .= " AND t.active = 1";
		$sql .= " ORDER BY t.taux ASC, t.recuperableonly ASC";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				for ($i = 0; $i < $num; $i++)
				{
					$obj = $this->db->fetch_object($resql);
					$txtva[$i]  = $obj->taux;
					$libtva[$i] = $obj->taux.'%';
					$nprtva[$i] = $obj->recuperableonly;
				}
			}
			else
			{
				print '<font class="error">'.$langs->trans("ErrorNoVATRateDefinedForSellerCountry",$code_pays).'</font>';
			}
		}
		else
		{
			print '<font class="error">'.$this->db->error().'</font>';
		}

		// Definition du taux a pre-selectionner (si defaulttx non force et donc vaut -1 ou '')
		if ($defaulttx < 0 || strlen($defaulttx) == 0)
		{
			$defaulttx=get_default_tva($societe_vendeuse,$societe_acheteuse,$taux_produit);
			$defaultnpr=get_default_npr($societe_vendeuse,$societe_acheteuse,$taux_produit);
		}
		// Si taux par defaut n'a pu etre determine, on prend dernier de la liste.
		// Comme ils sont tries par ordre croissant, dernier = plus eleve = taux courant
		if ($defaulttx < 0 || strlen($defaulttx) == 0)
		{
			$defaulttx = $txtva[sizeof($txtva)-1];
		}

		$nbdetaux = sizeof($txtva);

		if (sizeof($txtva))
		{
			print '<select class="flat" name="'.$name.'">';

			for ($i = 0 ; $i < $nbdetaux ; $i++)
			{
				//print "xxxxx".$txtva[$i]."-".$nprtva[$i];
				print '<option value="'.$txtva[$i];
				print $nprtva[$i] ? '*': '';
				print '"';
				if ($txtva[$i] == $defaulttx && $nprtva[$i] == $defaultnpr)
				{
					print ' selected="true"';
				}
				print '>'.vatrate($libtva[$i]);
				print $nprtva[$i] ? ' *': '';
				print '</option>';

				$this->tva_taux_value[$i] = $txtva[$i];
				$this->tva_taux_libelle[$i] = $libtva[$i];
				$this->tva_taux_npr[$i] = $nprtva[$i];
			}
			print '</select>';
		}
	}


	/**
	 *		Affiche zone de selection de date
	 *      Liste deroulante pour les jours, mois, annee et eventuellement heurs et minutes
	 *      Les champs sont pre-selectionnes avec:
	 *            	- La date set_time (timestamps ou date au format YYYY-MM-DD ou YYYY-MM-DD HH:MM)
	 *            	- La date du jour si set_time vaut ''
	 *            	- Aucune date (champs vides) si set_time vaut -1 (dans ce cas empty doit valoir 1)
	 *		@param	set_time 		Date de pre-selection
	 *		@param	prefix			Prefix pour nom champ
	 *		@param	h				1=Affiche aussi les heures
	 *		@param	m				1=Affiche aussi les minutes
	 *		@param	empty			0=Champ obligatoire, 1=Permet une saisie vide
	 *		@param	form_name 		Nom du formulaire de provenance. Utilise pour les dates en popup.
	 *		@param	d				1=Affiche aussi les jours, mois, annees
	 * 		@param	addnowbutton	Add a button "Now"
	 */
	function select_date($set_time='', $prefix='re', $h=0, $m=0, $empty=0, $form_name="", $d=1, $addnowbutton=0)
	{
		global $conf,$langs;

		if($prefix=='') $prefix='re';
		if($h == '') $h=0;
		if($m == '') $m=0;
		if($empty == '') $empty=0;

		if (! $set_time && $empty == 0) $set_time = time();

		// Analyse de la date de pr�-selection
		if (eregi('^([0-9]+)\-([0-9]+)\-([0-9]+)\s?([0-9]+)?:?([0-9]+)?',$set_time,$reg))
		{
			// Date au format 'YYYY-MM-DD' ou 'YYYY-MM-DD HH:MM:SS'
			$syear = $reg[1];
			$smonth = $reg[2];
			$sday = $reg[3];
			$shour = $reg[4];
			$smin = $reg[5];
		}
		elseif (strval($set_time) != '' && $set_time != -1)
		{
			// Date est un timestamps (0 possible)
			$syear = date("Y", $set_time);
			$smonth = date("n", $set_time);
			$sday = date("d", $set_time);
			$shour = date("H", $set_time);
			$smin = date("i", $set_time);
		}
		else
		{
			// Date est '' ou vaut -1
			$syear = '';
			$smonth = '';
			$sday = '';
			$shour = '';
			$smin = '';
		}

		if ($d)
		{
			/*
			 * Affiche date en popup
			 */
			if ($conf->use_javascript_ajax && $conf->use_popup_calendar)
			{
				//print "e".$set_time." t ".$conf->format_date_short;
				if (strval($set_time) != '' && $set_time != -1)
				{
					$formated_date=dol_print_date($set_time,$conf->format_date_short);
				}

				// Calendrier popup version eldy
				if ("$conf->use_popup_calendar" == "eldy")	// Laisser conf->use_popup_calendar entre quote
				{
					// Zone de saisie manuelle de la date
					print '<input id="'.$prefix.'" name="'.$prefix.'" type="text" size="9" maxlength="11" value="'.$formated_date.'"';
					print ' onChange="dpChangeDay(\''.$prefix.'\',\''.$conf->format_date_short_java.'\'); "';
					print '>';

					// Icone calendrier
					print '<button id="'.$prefix.'Button" type="button" class="dpInvisibleButtons"';
					$base=DOL_URL_ROOT.'/lib/';
					print ' onClick="showDP(\''.$base.'\',\''.$prefix.'\',\''.$conf->format_date_short_java.'\');">'.img_object($langs->trans("SelectDate"),'calendar').'</button>';

					print '<input type="hidden" id="'.$prefix.'day"   name="'.$prefix.'day"   value="'.$sday.'">'."\n";
					print '<input type="hidden" id="'.$prefix.'month" name="'.$prefix.'month" value="'.$smonth.'">'."\n";
					print '<input type="hidden" id="'.$prefix.'year"  name="'.$prefix.'year"  value="'.$syear.'">'."\n";
				}
				else
				{
					// Calendrier popup version defaut
					if ($langs->defaultlang != "")
					{
						print '<script type="text/javascript">';
						print 'selectedLanguage = "'.substr($langs->defaultlang,0,2).'"';
						print '</script>';
					}
					print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/lib/lib_calendar.js"></script>';
					print '<input id="'.$prefix.'" type="text" name="'.$prefix.'" size="9" value="'.$formated_date.'"';
					print ' onChange="dpChangeDay(\''.$prefix.'\',\''.$conf->format_date_short_java.'\')"';
					print '> ';
					print '<input type="hidden" id="'.$prefix.'day"   name="'.$prefix.'day"   value="'.$sday.'">'."\n";
					print '<input type="hidden" id="'.$prefix.'month" name="'.$prefix.'month" value="'.$smonth.'">'."\n";
					print '<input type="hidden" id="'.$prefix.'year"  name="'.$prefix.'year"  value="'.$syear.'">'."\n";
					if ($form_name =="")
					{
						print '<A HREF="javascript:showCalendar(document.forms[3].'.$prefix.')">';
						print '<img style="vertical-align:middle" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/calendar.png" border="0" alt="" title="">';
						print '</a>';
					}
					else
					{
						print '<A HREF="javascript:showCalendar(document.forms[\''.$form_name.'\'].'.$prefix.')">';
						print '<img style="vertical-align:middle" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/calendar.png" border="0" alt="" title="">';
						print '</a>';
					}
				}
			}

			/*
			 * Affiche date en select
			 */
			if (! $conf->use_javascript_ajax || ! $conf->use_popup_calendar)
			{
				// Jour
				print '<select class="flat" name="'.$prefix.'day">';

				if ($empty || $set_time == -1)
				{
					print '<option value="0" selected="true">&nbsp;</option>';
				}

				for ($day = 1 ; $day <= 31; $day++)
				{
					if ($day == $sday)
					{
						print "<option value=\"$day\" selected=\"true\">$day";
					}
					else
					{
						print "<option value=\"$day\">$day";
					}
					print "</option>";
				}

				print "</select>";

				print '<select class="flat" name="'.$prefix.'month">';
				if ($empty || $set_time == -1)
				{
					print '<option value="0" selected="true">&nbsp;</option>';
				}

				// Mois
				for ($month = 1 ; $month <= 12 ; $month++)
				{
					print '<option value="'.$month.'"'.($month == $smonth?' selected="true"':'').'>';
					print dol_print_date(mktime(1,1,1,$month,1,2000),"%b");
					print "</option>";
				}
				print "</select>";

				// Ann�e
				if ($empty || $set_time == -1)
				{
					print '<input class="flat" type="text" size="3" maxlength="4" name="'.$prefix.'year" value="'.$syear.'">';
				}
				else
				{
					print '<select class="flat" name="'.$prefix.'year">';

					for ($year = $syear - 5; $year < $syear + 10 ; $year++)
					{
						if ($year == $syear)
						{
							print "<option value=\"$year\" selected=\"true\">$year";
						}
						else
						{
							print "<option value=\"$year\">$year";
						}
						print "</option>";
					}
					print "</select>\n";
				}
			}
		}

		if ($d && $h) print '&nbsp;';

		if ($h)
		{
			/*
			 * Affiche heure en select
			 */
			print '<select class="flat" name="'.$prefix.'hour">';
			if ($empty) print '<option value="-1">&nbsp;</option>';
			for ($hour = 0; $hour < 24; $hour++)
			{
				if (strlen($hour) < 2)
				{
					$hour = "0" . $hour;
				}
				if ($hour == $shour)
				{
					print "<option value=\"$hour\" selected=\"true\">$hour</option>";
				}
				else
				{
					print "<option value=\"$hour\">$hour</option>";
				}
			}
			print "</select>";
			print "H\n";
		}

		if ($m)
		{
			/*
			 * Affiche min en select
			 */
			print '<select class="flat" name="'.$prefix.'min">';
			if ($empty) print '<option value="-1">&nbsp;</option>';
			for ($min = 0; $min < 60 ; $min++)
			{
				if (strlen($min) < 2)
				{
					$min = "0" . $min;
				}
				if ($min == $smin)
				{
					print "<option value=\"$min\" selected=\"true\">$min</option>";
				}
				else
				{
					print "<option value=\"$min\">$min</option>";
				}
			}
			print "</select>";
			print "M\n";
		}

		// Added by Matelli http://matelli.fr/showcases/patchs-dolibarr/update-date-input-in-action-form.html)
		// "Now" button
		if ($conf->use_javascript_ajax && $addnowbutton)
		{
			// Script which will be inserted in the OnClick of the "Now" button
			$reset_scripts = "";

			// Generate the date part, depending on the use or not of the javascript calendar
			if ($conf->use_popup_calendar)
			{
				$base=DOL_URL_ROOT.'/lib/';
				$reset_scripts .= 'resetDP(\''.$base.'\',\''.$prefix.'\',\''.$conf->format_date_short_java.'\');';
			}
			else
			{
				$reset_scripts .= 'this.form.elements[\''.$prefix.'day\'].value=formatDate(new Date(), \'dds\');';
				$reset_scripts .= 'this.form.elements[\''.$prefix.'hour\'].value=formatDate(new Date(), \'MM\');';
				$reset_scripts .= 'this.form.elements[\''.$prefix.'year\'].value=formatDate(new Date(), \'YYYY\');';
			}
			// Generate the hour part
			if ($h)
			{
				$reset_scripts .= 'this.form.elements[\''.$prefix.'hour\'].value=formatDate(new Date(), \'HH\');';
			}
			// Generate the minute part
			if ($m)
			{
				$reset_scripts .= 'this.form.elements[\''.$prefix.'min\'].value=formatDate(new Date(), \'mm\');';
			}
			// If reset_scripts is not empty, print the button with the reset_scripts in OnClick
			if ($reset_scripts)
			{
				print '<button class="dpInvisibleButtons" id="'.$prefix.'ButtonNow" type="button" name="_useless" value="Maintenant" onClick="'.$reset_scripts.'">';
				print $langs->trans("Now");
				//print img_refresh($langs->trans("Now"));
				print '</button> ';
			}
		}

	}

	/**
	 *	\brief  	Fonction servant a afficher une duree dans une liste deroulante
	 *	\param		prefix   	prefix
	 *	\param  	iSecond  	Nombre de secondes
	 */
	function select_duree($prefix,$iSecond='')
	{
		if ($iSecond)
		{
			require_once(DOL_DOCUMENT_ROOT."/lib/date.lib.php");

			$hourSelected = ConvertSecondToTime($iSecond,'hour');
			$minSelected = ConvertSecondToTime($iSecond,'min');
		}

		print '<select class="flat" name="'.$prefix.'hour">';
		for ($hour = 0; $hour < 24; $hour++)
		{
			print '<option value="'.$hour.'"';
			if ($hourSelected == $hour || ($iSecond == '' && $hour == 1))
			{
				print " selected=\"true\"";
			}
			print ">".$hour."</option>";
		}
		print "</select>";
		print "H &nbsp;";
		print '<select class="flat" name="'.$prefix.'min">';
		for ($min = 0; $min <= 55; $min=$min+5)
		{
			print '<option value="'.$min.'"';
			if ($minSelected == $min) print ' selected="true"';
			print '>'.$min.'</option>';
		}
		print "</select>";
		print "M&nbsp;";
	}


	/**
	 *	\brief  Show a select form from an array
	 *	\param	htmlname        Nom de la zone select
	 *	\param	array           Tableau de key+valeur
	 *	\param	id              Preselected key
	 *	\param	show_empty      1 si il faut ajouter une valeur vide dans la liste, 0 sinon
	 *	\param	key_in_label    1 pour afficher la key dans la valeur "[key] value"
	 *	\param	value_as_key    1 to use value as key
	 *	\param	optionType      Type de l'option: 1 pour des fonctions javascript
	 *	\param  option          Valeur de l'option en fonction du type choisi
	 *	\param  translate       Traduire la valeur
	 * 	\param	maxlen			Length maximum for labels
	 */
	function selectarray($htmlname, $array, $id='', $show_empty=0, $key_in_label=0, $value_as_key=0, $optionType=0, $option='', $translate=0, $maxlen=0)
	{
		global $langs;

		$out='';

		// \TODO Simplify optionType and option (only one should be necessary)
		if ($optionType == 1 && $option != '')
		{
			$out.='<select class="flat" name="'.$htmlname.'" '.$option.'>';
		}
		else
		{
			$out.='<select class="flat" name="'.$htmlname.'">';
		}

		if ($show_empty)
		{
			$out.='<option value="-1"'.($id==-1?' selected="true"':'').'>&nbsp;</option>'."\n";
		}

		if (is_array($array))
		{
			while (list($key, $value) = each ($array))
			{
				$out.='<option value="'.($value_as_key?$value:$key).'"';
				// Si il faut pre-selectionner une valeur
				if ($id != '' && ($id == $key || $id == $value))
				{
					$out.=' selected="true"';
				}

				$out.='>';

				if ($key_in_label)
				{
					$newval=($translate?$langs->trans($value):$value);
					$selectOptionValue = $key.' - '.($maxlen?dol_trunc($newval,$maxlen):$newval);
					$out.=$selectOptionValue;
				}
				else
				{
					$newval=($translate?$langs->trans($value):$value);
					$selectOptionValue = ($maxlen?dol_trunc($newval,$maxlen):$newval);
					if ($value == '' || $value == '-') { $selectOptionValue='&nbsp;'; }
					$out.=$selectOptionValue;
				}
				$out.="</option>\n";
			}
		}

		$out.="</select>";
		return $out;
	}

	/**
	 *	\brief  Show a select form from an array
	 *	\param	htmlname        Nom de la zone select
	 *	\param	array           Tableau de key+valeur
	 *	\param	id              Preselected key
	 *	\param	show_empty      1 si il faut ajouter une valeur vide dans la liste, 0 sinon
	 *	\param	key_in_label    1 pour afficher la key dans la valeur "[key] value"
	 *	\param	value_as_key    1 to use value as key
	 *	\param	optionType      Type de l'option: 1 pour des fonctions javascript
	 *	\param  option          Valeur de l'option en fonction du type choisi
	 *	\param  translate       Traduire la valeur
	 * 	\param	maxlen			Length maximum for labels
	 * 	\deprecated				Use selectarray instead
	 */
	function select_array($htmlname, $array, $id='', $show_empty=0, $key_in_label=0, $value_as_key=0, $optionType=0, $option='', $translate=0, $maxlen=0)
	{
		print $this->selectarray($htmlname, $array, $id, $show_empty, $key_in_label, $value_as_key, $optionType, $option, $translate, $maxlen);
	}


	/**
	 *    \brief      Selection de oui/non en chaine (renvoie yes/no)
	 *    \param      name            Nom du select
	 *    \param      value           Valeur pre-selectionnee
	 *    \param      option          0 retourne yes/no, 1 retourne 1/0
	 */
	function selectyesno($htmlname,$value='',$option=0)
	{
		global $langs;

		$yes="yes"; $no="no";

		if ($option)
		{
			$yes="1";
			$no="0";
		}

		$resultyesno = '<select class="flat" name="'.$htmlname.'">'."\n";
		if (("$value" == 'yes') || ($value == 1))
		{
			$resultyesno .= '<option value="'.$yes.'" selected="true">'.$langs->trans("Yes").'</option>'."\n";
			$resultyesno .= '<option value="'.$no.'">'.$langs->trans("No").'</option>'."\n";
		}
		else
		{
			$resultyesno .= '<option value="'.$yes.'">'.$langs->trans("Yes").'</option>'."\n";
			$resultyesno .= '<option value="'.$no.'" selected="true">'.$langs->trans("No").'</option>'."\n";
		}
		$resultyesno .= '</select>'."\n";
		return $resultyesno;
	}



	/**
	 *    \brief      Retourne la liste des modeles d'export
	 *    \param      selected          Id modele pre-selectionne
	 *    \param      htmlname          Nom de la zone select
	 *    \param      type              Type des modeles recherches
	 *    \param      useempty          Affiche valeur vide dans liste
	 */
	function select_export_model($selected='',$htmlname='exportmodelid',$type='',$useempty=0)
	{

		$sql = "SELECT rowid, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."export_model";
		$sql.= " WHERE type = '".$type."'";
		$sql.= " ORDER BY rowid";
		$result = $this->db->query($sql);
		if ($result)
		{
			print '<select class="flat" name="'.$htmlname.'">';
			if ($useempty)
			{
				print '<option value="-1">&nbsp;</option>';
			}

			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				if ($selected == $obj->rowid)
				{
					print '<option value="'.$obj->rowid.'" selected="true">';
				}
				else
				{
					print '<option value="'.$obj->rowid.'">';
				}
				print $obj->label;
				print '</option>';
				$i++;
			}
			print "</select>";
		}
		else {
			dol_print_error($this->db);
		}
	}

	/**
	 *    \brief      Retourne la liste des mois
	 *    \param      selected          Id mois pre-selectionne
	 *    \param      htmlname          Nom de la zone select
	 *    \param      useempty          Affiche valeur vide dans liste
	 */
	function select_month($selected='',$htmlname='monthid',$useempty=0)
	{
		$month = monthArrayOrSelected(-1);	// Get array

		$select_month = '<select class="flat" name="'.$htmlname.'">';
		if ($useempty)
		{
			$select_month .= '<option value="0">&nbsp;</option>';
		}
		foreach ($month as $key => $val)
		{
			if ($selected == $key)
			{
				$select_month .= '<option value="'.$key.'" selected="true">';
			}
			else
			{
				$select_month .= '<option value="'.$key.'">';
			}
			$select_month .= $val;
		}
		$select_month .= '</select>';
		return $select_month;
	}
	/**
	 *    \brief      Retourne la liste des annees
	 *    \param      selected          Annee pre-selectionne
	 *    \param      htmlname          Nom de la zone select
	 *    \param      useempty          Affiche valeur vide dans liste
	 *    \param      $min_year         Valeur minimum de l'annee dans la liste (par defaut annee courante -10)
	 *    \param      $max_year         Valeur maximum de l'annee dans la liste (par defaut annee courante + 5)
	 */
	function select_year($selected='',$htmlname='yearid',$useempty=0, $min_year='', $max_year='')
	{
		if($max_year == '')
		$max_year = date("Y") +5;
		if($min_year == '')
		$min_year = date("Y") - 10;

		print '<select class="flat" name="' . $htmlname . '">';
		if($useempty)
		{
			if($selected == '')
			$selected_html = 'selected="true"';
			print '<option value="" ' . $selected_html . ' >&nbsp;</option>';
		}
		for ($y = $max_year; $y >= $min_year; $y--)
		{
			$selected_html='';
			if ($y == $selected)
			{
				$selected_html = 'selected="true"';
			}
			print "<option value=\"$y\" $selected_html >$y";
			print "</option>";
		}
		print "</select>\n";
	}

	/**
	 *    \brief      Affiche tableau avec ref et bouton navigation pour un objet metier
	 *    \param      object		Object to show
	 *    \param      paramid   	Nom du parametre a utiliser pour nommer id dans liens URL
	 *    \param      morehtml  	Code html supplementaire a afficher avant barre nav
	 *    \param	  shownav	  	Show Condition
	 *    \param      fieldid   	Nom du champ en base a utiliser pour select next et previous
	 *    \param      fieldref   	Nom du champ objet ref (object->ref) a utiliser pour select next et previous
	 *    \param      morehtmlref  	Code html supplementaire a afficher apres ref
	 *    \param      moreparam  	More param to ad in nav link url.
	 * 	  \return     string    	Portion HTML avec ref + boutons nav
	 */
	function showrefnav($object,$paramid,$morehtml='',$shownav=1,$fieldid='rowid',$fieldref='ref',$morehtmlref='',$moreparam='')
	{
		$ret='';

		$object->load_previous_next_ref($object->next_prev_filter,$fieldid);
		$previous_ref = $object->ref_previous?'<a href="'.$_SERVER["PHP_SELF"].'?'.$paramid.'='.urlencode($object->ref_previous).$moreparam.'">'.img_previous().'</a>':'';
		$next_ref     = $object->ref_next?'<a href="'.$_SERVER["PHP_SELF"].'?'.$paramid.'='.urlencode($object->ref_next).$moreparam.'">'.img_next().'</a>':'';

		//print "xx".$previous_ref."x".$next_ref;
		if ($previous_ref || $next_ref || $morehtml) {
			$ret.='<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">';
		}

		$ret.=$object->$fieldref;
		if ($morehtmlref) {
			$ret.=' '.$morehtmlref;
		}

		if ($morehtml) {
			$ret.='</td><td class="nobordernopadding" align="right">'.$morehtml;
		}
		if ($shownav && ($previous_ref || $next_ref)) {
			$ret.='</td><td class="nobordernopadding" align="center" width="20">'.$previous_ref.'</td>';
			$ret.='<td class="nobordernopadding" align="center" width="20">'.$next_ref;
		}
		if ($previous_ref || $next_ref || $morehtml)
		{
			$ret.='</td></tr></table>';
		}
		return $ret;
	}


	/**
	 *    	\brief      Return HTML code to output a photo
	 *    	\param      modulepart		Id to define module concerned
	 *     	\param      object			Object containing data to retreive file name
	 * 		\param		width			Width of photo
	 * 	  	\return     string    		HTML code to output photo
	 */
	function showphoto($modulepart,$object,$width=100)
	{
		global $conf;

		$ret='';$dir='';$file='';$email='';

		if ($modulepart=='userphoto')
		{
			$dir=$conf->user->dir_output;
			$file=$object->id.".jpg";
			$email=$object->email;
		}
		if ($modulepart=='member')
		{
			$dir=$conf->adherent->dir_output;
			$file=$object->id.".jpg";
			$email=$object->email;
		}

		if ($dir && $file)
		{
			if (file_exists($dir."/".$file))
		    {
		        $ret.='<img alt="Photo" width="'.$width.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&file='.urlencode($file).'">';
		    }
		    else
		    {
		    	if ($conf->gravatar->enabled)
		    	{
		    		global $dolibarr_main_url_root;
		    		$ret.='<!-- Put link to gravatar -->';
		    		$ret.='<img alt="Photo found on Gravatar" title="Photo Gravatar.com - email '.$email.'" width="'.$width.'" src="http://www.gravatar.com/avatar/'.md5($email).'?s='.$width.'&d='.urlencode($dolibarr_main_url_root.'/theme/common/nophoto.jpg').'">';
		    	}
		    	else
		    	{
		        	$ret.='<img alt="No photo" width="'.$width.'" src="'.DOL_URL_ROOT.'/theme/common/nophoto.jpg">';
		    	}
		    }
		}
		else
		{
			dol_print_error('','Call to showrefnav with wrong parameters');
		}

		return $ret;
	}
}

?>
