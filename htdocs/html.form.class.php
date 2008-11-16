<?php
/* Copyright (c) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2006      Marc Barilley/Ocebo  <marc@ocebo.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerker@telenet.be>
 * Copyright (C) 2007      Patrick Raguin 		<patrick.raguin@gmail.com>
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
 *	\brief      Fichier de la classe des fonctions prédéfinie de composants html
 *	\version	$Id$
 */


/**
 *	\class      Form
 *	\brief      Classe permettant la génération de composants html
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
	 \brief     Constructeur
	 \param     DB      handler d'accès base de donnée
	 */
	function Form($DB)
	{
		$this->db = $DB;

		return 1;
	}


	/**
		\brief	Affiche un texte+picto avec tooltip sur texte ou sur picto
		\param  text				Texte à afficher
		\param  htmltext	    	Contenu html du tooltip, codé en html
		\param	tooltipon			1=tooltip sur texte, 2=tooltip sur picto, 3=tooltip sur les 2, 4=tooltip sur les 2 et forcé en Ajax
		\param	direction			-1=Le picto est avant, 0=pas de picto, 1=le picto est après
		\param	img					Code img du picto
		\return	string				Code html du tooltip (texte+picto)
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
			$s.='<table class="nobordernopadding"><tr>';
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
	 \brief     Affiche un texte avec picto help qui affiche un tooltip
	 \param     text				Texte à afficher
	 \param     htmltooltip     	Contenu html du tooltip
	 \param		direction			1=Le picto est après, -1=le picto est avant
	 \param		usehelpcursor		1=Utilise curseur help, 0=Curseur par defaut
	 \return	string				Code html du texte,picto
	 */
	function textwithhelp($text,$htmltext,$direction=1,$usehelpcursor=1)
	{
		return $this->textwithtooltip($text,$htmltext,2,$direction,img_help($usehelpcursor,0));
	}

	/**
	 \brief     Affiche un texte avec picto warning qui affiche un tooltip
	 \param     text				Texte à afficher
	 \param     htmltooltip     	Contenu html du tooltip
	 \param		direction			1=Le picto est après, -1=le picto est avant
	 \return	string				Code html du texte,picto
	 */
	function textwithwarning($text,$htmltext,$direction=1)
	{
		return $this->textwithtooltip($text,$htmltext,2,$direction,img_warning(""));
	}


	/**
	 *    \brief      Retourne la liste déroulante des départements/province/cantons tout pays confondu ou pour un pays donné.
	 *    \remarks    Dans le cas d'une liste tout pays confondus, l'affichage fait une rupture sur le pays.
	 *    \remarks    La cle de la liste est le code (il peut y avoir plusieurs entrée pour
	 *                un code donnée mais dans ce cas, le champ pays diffère).
	 *                Ainsi les liens avec les départements se font sur un département indépendemment de son nom.
	 *    \param      selected        code forme juridique a présélectionné
	 *    \param      pays_code       0=liste tous pays confondus, sinon code du pays à afficher
	 */
	function select_departement($selected='',$pays_code=0)
	{
		global $conf,$langs,$user;
		 
		dolibarr_syslog("Form::select_departement selected=$selected, pays_code=$pays_code",LOG_DEBUG);
	  
		$langs->load("dict");

		$htmlname='departement_id';

		// On recherche les départements/cantons/province active d'une region et pays actif
		$sql = "SELECT d.rowid, d.code_departement as code , d.nom, d.active, p.libelle as libelle_pays, p.code as code_pays FROM";
		$sql .= " ".MAIN_DB_PREFIX ."c_departements as d, ".MAIN_DB_PREFIX."c_regions as r,".MAIN_DB_PREFIX."c_pays as p";
		$sql .= " WHERE d.fk_region=r.code_region and r.fk_pays=p.rowid";
		$sql .= " AND d.active = 1 AND r.active = 1 AND p.active = 1";
		if ($pays_code) $sql .= " AND p.code = '".$pays_code."'";
		$sql .= " ORDER BY p.code, d.code_departement";

		dolibarr_syslog("Form::select_departement sql=".$sql);
		$result=$this->db->query($sql);
		if ($result)
		{
	  print '<select class="flat" name="'.$htmlname.'">';
	  if ($pays_code) print '<option value="0">&nbsp;</option>';
	  $num = $this->db->num_rows($result);
	  $i = 0;
	  dolibarr_syslog("Form::select_departement num=$num",LOG_DEBUG);
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
	  			// Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
	  			print $obj->code . ' - ' . ($langs->trans($obj->code)!=$obj->code?$langs->trans($obj->code):($obj->nom!='-'?$obj->nom:''));
	  			print '</option>';
	  		}
	  		$i++;
	  	}
	  }
	  print '</select>';
			if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
		}
		else {
			dolibarr_print_error($this->db);
		}
	}


	/**
	 *    \brief      Retourne la liste déroulante des regions actives dont le pays est actif
	 *    \remarks    La cle de la liste est le code (il peut y avoir plusieurs entrée pour
	 *                un code donnée mais dans ce cas, le champ pays et lang diffère).
	 *                Ainsi les liens avec les regions se font sur une region independemment
	 *                de son nom.
	 */

	function select_region($selected='',$htmlname='region_id')
	{
		global $conf,$langs;
		$langs->load("dict");

		$sql = "SELECT r.rowid, r.code_region as code, r.nom as libelle, r.active, p.libelle as libelle_pays FROM ".MAIN_DB_PREFIX."c_regions as r, ".MAIN_DB_PREFIX."c_pays as p";
		$sql .= " WHERE r.fk_pays=p.rowid AND r.active = 1 and p.active = 1 ORDER BY libelle_pays, libelle ASC";

		dolibarr_syslog("Form::select_region sql=".$sql);
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
		else {
			dolibarr_print_error($this->db);
		}
	}


	/**
	 *   	\brief      Renvoie la liste des sources de commandes
	 *		\param      selected		Id de la source pré-sélectionnée
	 *    	\param     	htmlname 		Nom de la liste deroulante
	 *      \param     	addempty		0=liste sans valeur nulle, 1=ajoute valeur inconnue
	 *      \return		array			Tableau des sources de commandes
	 */
	function selectSourcesCommande($selected='',$htmlname='source_id',$addempty=0)
	{
		global $conf,$langs;
		print '<select class="flat" name="'.$htmlname.'" '.$htmloption.'>';
		if ($addempty) print '<option value="-1" selected="true">&nbsp;</option>';

		// \TODO Aller cherches les sources dans dictionnaire
		print '<option value="0"'.($selected=='0'?' selected="true"':'').'>'.$langs->trans('OrderSource0').'</option>';
		print '<option value="1"'.($selected=='1'?' selected="true"':'').'>'.$langs->trans('OrderSource1').'</option>';
		print '<option value="2"'.($selected=='2'?' selected="true"':'').'>'.$langs->trans('OrderSource2').'</option>';
		print '<option value="3"'.($selected=='3'?' selected="true"':'').'>'.$langs->trans('OrderSource3').'</option>';
		print '<option value="4"'.($selected=='4'?' selected="true"':'').'>'.$langs->trans('OrderSource4').'</option>';
		print '<option value="5"'.($selected=='5'?' selected="true"':'').'>'.$langs->trans('OrderSource5').'</option>';
		print '<option value="6"'.($selected=='6'?' selected="true"':'').'>'.$langs->trans('OrderSource6').'</option>';

		print '</select>';
	}


	/**
	 *
	 *
	 */
	function select_methodes_commande($selected='',$htmlname='source_id',$addempty=0)
	{
		global $conf,$langs;
		$listemethodes=array();

		$sql = "SELECT rowid, libelle ";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_methode_commande_fournisseur";
		$sql.= " WHERE active = 1";

		dolibarr_syslog("Form::select_methodes_commande sql=".$sql);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$i = 0;
			$num = $this->db->num_rows($resql);
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$listemethodes[$obj->rowid] = $obj->libelle;
				$i++;
			}
		}
		else
		{
			dolibarr_print_error($this->db);
			return -1;
		}

		print $this->select_array($htmlname,$listemethodes,$selected,$addempty);
		return 1;
	}

	/**
	 *    \brief     Retourne la liste déroulante des pays actifs, dans la langue de l'utilisateur
	 *    \param     selected         Id ou Code pays ou Libelle pays pré-sélectionné
	 *    \param     htmlname         Nom de la liste deroulante
	 *    \param     htmloption       Options html sur le select
	 *    \todo      trier liste sur noms après traduction plutot que avant
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

		dolibarr_syslog("Form::select_pays sql=".$sql);
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
						// Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
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
			dolibarr_print_error($this->db);
			return 1;
		}
	}


	/**
	 *    \brief      Retourne la liste des types de comptes financiers
	 *    \param      selected        Type pré-sélectionné
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
	 *    \brief      Retourne la liste des types de comptes financiers
	 *    \param      selected        Type pré-sélectionné
	 *    \param      htmlname        Nom champ formulaire
	 */
	function select_type_actions($selected='',$htmlname='actioncode')
	{
		global $langs,$user;

		require_once(DOL_DOCUMENT_ROOT."/cactioncomm.class.php");
		$caction=new CActionComm($this->db);

		$arraylist=$caction->liste_array(1,'code');
		$arraylist[0]='&nbsp;';
		asort($arraylist);

		$this->select_array($htmlname, $arraylist, $selected);
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
	}


	/**
	 *		\brief      Return list of social contributions
	 *		\param      selected        Preselected type
	 *		\param      htmlname        Name of field in form
	 */
	function select_type_socialcontrib($selected='',$htmlname='actioncode')
	{
		global $db,$langs,$user;

		$sql = "SELECT c.id, c.libelle as type";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c";
		$sql.= " WHERE active = 1";
		$sql.= " ORDER BY c.libelle ASC";

		dolibarr_syslog("Form::select_type_socialcontrib sql=".$sql, LOG_DEBUG);
		$resql=$db->query($sql);
		if ($resql)
		{
			print '<select class="flat" name="'.$htmlname.'">';
			$num = $db->num_rows($resql);
			$i = 0;

			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				print '<option value="'.$obj->id.'"';
				if ($obj->id == $selected) print ' selected="true"';
				print '>'.$obj->type;
				$i++;
			}
			print '</select>';
			if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
		}
		else
		{
			dolibarr_print_error($db,$db->lasterror());
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
	 *    	\param      filter          Criteres optionnels de filtre
	 *		\param		showempty		Add an empty field
	 */
	function select_societes($selected='',$htmlname='socid',$filter='',$showempty=0)
	{
		global $conf,$user;

		// On recherche les societes
		$sql = "SELECT s.rowid, s.nom";
		$sql.= " FROM ".MAIN_DB_PREFIX ."societe as s";
		if (!$user->rights->societe->client->voir && !$user->societe_id) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE 1=1";
		if ($filter) $sql.= " AND ".$filter;
		if ($selected && $conf->use_javascript_ajax && $conf->global->COMPANY_USE_SEARCH_TO_SELECT)
		{
			$sql.= " AND rowid = ".$selected;
		}
		if (!$user->rights->societe->client->voir && !$user->societe_id) //restriction
		{
			$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
		}
		$sql.= " ORDER BY nom ASC";

		dolibarr_syslog("Form::select_societes sql=".$sql);
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
						if ($selected > 0 && $selected == $obj->rowid)
						{
							print '<option value="'.$obj->rowid.'" selected="true">'.$obj->nom.'</option>';
						}
						else
						{
							print '<option value="'.$obj->rowid.'">'.$obj->nom.'</option>';
						}
						$i++;
					}
				}
				print '</select>';
			}
		}
		else
		{
			dolibarr_print_error($this->db);
		}
	}


	/**
	 *    	\brief      Retourne la liste déroulante des remises fixes
	 *    	\param      selected        Id remise fixe pré-sélectionnée
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

		dolibarr_syslog("Form::select_remises sql=".$sql);
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
					$desc=dolibarr_trunc($obj->description,40);
					if ($desc=='(CREDIT_NOTE)')
					{
						$desc=$langs->trans("CreditNote");
						//$desc.=$obj->fk_facture_source;
					}
						
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
			dolibarr_print_error($this->db);
			return -1;
		}
	}


	/**
	 *    	\brief      Retourne la liste déroulante des contacts d'une société donnée
	 *    	\param      socid      	    Id de la société
	 *    	\param      selected   	    Id contact pré-sélectionné
	 *    	\param      htmlname  	    Nom champ formulaire ('none' pour champ non editable)
	 *      \param      show_empty      0=liste sans valeur nulle, 1=ajoute valeur inconnue
	 *      \param      exclude         Liste des id contacts à exclure
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

		dolibarr_syslog("Form::select_contacts sql=".$sql);
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
			dolibarr_print_error($this->db);
			return -1;
		}
	}


	/**
	 *	\brief      Retourne la liste déroulante des utilisateurs
	 *  \param      selected        Id user preselected
	 *  \param      htmlname        Field name in form
	 *  \param      show_empty      0=liste sans valeur nulle, 1=ajoute valeur inconnue
	 *  \param      exclude         List of users id to exclude
	 * 	\param		disabled		If select list must be disabled
	 */
	function select_users($selected='',$htmlname='userid',$show_empty=0,$exclude='',$disabled=0)
	{
		// Permettre l'exclusion d'utilisateurs
		if (is_array($exclude))
		{
			$excludeUsers = implode("','",$exclude);
		}

		// On recherche les utilisateurs
		$sql = "SELECT u.rowid, u.name, u.firstname FROM ";
		$sql.= MAIN_DB_PREFIX ."user as u";
		if (is_array($exclude) && $excludeUsers) $sql.= " WHERE u.rowid NOT IN ('".$excludeUsers."')";
		$sql.= " ORDER BY u.name ASC";

		dolibarr_syslog("Form::select_users sql=".$sql);
		if ($this->db->query($sql))
		{
			print '<select class="flat" name="'.$htmlname.'"'.($disabled?' disabled="true"':'').'>';
			if ($show_empty) print '<option value="-1"'.($id==-1?' selected="true"':'').'>&nbsp;</option>'."\n";
			$num = $this->db->num_rows();
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object();
						
					if ($selected && $selected == $obj->rowid)
					{
						print '<option value="'.$obj->rowid.'" selected="true">'.$obj->name.' '.$obj->firstname.'</option>';
					}
					else
					{
						print '<option value="'.$obj->rowid.'">'.$obj->name.' '.$obj->firstname.'</option>';
					}
					$i++;
				}
			}
			print '</select>';
		}
		else
		{
			dolibarr_print_error($this->db);
		}
	}


	/**
	 *  \brief    Retourne la liste des produits en Ajax si ajax activé ou renvoie à select_produits_do
	 *  \param    selected        Produit pré-sélectionné
	 *  \param    htmlname        Nom de la zone select
	 *  \param    filtertype      Filter on product type (''=nofilter, 0=product, 1=service)
	 *  \param    limit           Limite sur le nombre de lignes retournées
	 *  \param    price_level     Niveau de prix en fonction du client
	 *  \param	status			-1=Return all products, 0=Products not on sell, 1=Products on sell
	 */
	function select_produits($selected='',$htmlname='productid',$filtertype='',$limit=20,$price_level=0,$status=1)
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
			print ajax_updater($htmlname,'keysearch','/product/ajaxproducts.php','&price_level='.$price_level.'&type='.$filtertype.'&mode=1&status='.$status,'');
			print '</td></tr>';
			print '</table>';
		}
		else
		{
			$this->select_produits_do($selected,$htmlname,$filtertype,$limit,$price_level,'',$status);
		}
	}

	/**
	 *	\brief      Retourne la liste des produits
	 *	\param      selected        Produit pré-sélectionné
	 *	\param      htmlname        Nom de la zone select
	 *  \param		filtertype      Filter on product type (''=nofilter, 0=product, 1=service)
	 *	\param      limit           Limite sur le nombre de lignes retournées
	 *	\param      price_level     Niveau de prix en fonction du client
	 * 	\param      ajaxkeysearch   Filtre des produits si ajax est utilisé
	 *	\param		status			-1=Return all products, 0=Products not on sell, 1=Products on sell
	 */
	function select_produits_do($selected='',$htmlname='productid',$filtertype='',$limit=20,$price_level=0,$ajaxkeysearch='',$status=1)
	{
		global $langs,$conf,$user;

		$sql = "SELECT ";
		if ($conf->categorie->enabled && ! $user->rights->categorie->voir)
		{
			$sql.="DISTINCT";
		}
		$sql.= " p.rowid, p.label, p.ref, p.price, p.price_ttc, p.price_base_type, p.duration";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p ";
		if ($conf->categorie->enabled && ! $user->rights->categorie->voir)
		{
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON p.rowid = cp.fk_product";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON cp.fk_categorie = c.rowid";
		}
		if ($status >= 0)  $sql.= " WHERE p.envente = ".$status;
		else $sql.= " WHERE 1 = 1";
		if ($conf->categorie->enabled && ! $user->rights->categorie->voir)
		{
			$sql.= ' AND IFNULL(c.visible,1)=1';
		}
		if (strval($filtertype) != '') $sql.=" AND p.fk_product_type=".$filtertype;
		if ($ajaxkeysearch && $ajaxkeysearch != '') $sql.=" AND (p.ref like '%".$ajaxkeysearch."%' OR p.label like '%".$ajaxkeysearch."%')";
		$sql.= " ORDER BY p.ref";
		if ($limit) $sql.= " LIMIT $limit";

		dolibarr_syslog("Form::select_produits_do sql=".$sql, LOG_DEBUG);
		$result=$this->db->query($sql);
		if (! $result) dolibarr_print_error($this->db);

		// Multilang : on construit une liste des traductions des produits listés
		if ($conf->global->MAIN_MULTILANGS)
		{
			$sqld = "SELECT d.fk_product, d.label";
			$sqld.= " FROM ".MAIN_DB_PREFIX."product as p, ".MAIN_DB_PREFIX."product_det as d ";
			$sqld.= " WHERE d.fk_product=p.rowid AND p.envente=1 AND d.lang='". $langs->getDefaultLang() ."'";
			$sqld.= " ORDER BY p.ref";

			dolibarr_syslog("Form::select_produits_do sql=".$sql, LOG_DEBUG);
			$resultd = $this->db->query($sqld);
			if ( $resultd ) $objtp = $this->db->fetch_object($resultd);
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

				// Multilangs : modification des donnée si une traduction existe
				if ($conf->global->MAIN_MULTILANGS)
				{
					if ( $objp->rowid == $objtp->fk_product ) // si on a une traduction
					{
						if ( $objtp->label != '') $objp->label = $objtp->label;
						if ( $resultd ) $objtp = $this->db->fetch_object($resultd); // on charge la traduction suivante
					}
				}
				$opt = '<option value="'.$objp->rowid.'">'.$objp->ref.' - ';
				$opt.= dolibarr_trunc($objp->label,32).' - ';

				// Multiprix
				if ($price_level > 1)
				{
					$sql= "SELECT price, price_ttc, price_base_type ";
					$sql.= "FROM ".MAIN_DB_PREFIX."product_price ";
					$sql.= "where fk_product='".$objp->rowid."' and price_level=".$price_level;
					$sql.= " order by date_price DESC limit 1";

					dolibarr_syslog("Form::select_produits_do sql=".$sql);
					$result2 = $this->db->query($sql);
					$objp2 = $this->db->fetch_object($result2);
					if ($objp2)
					{
						if ($objp2->price_base_type == 'HT')
						$opt.= price($objp2->price,1).' '.$langs->trans("Currency".$conf->monnaie).' '.$langs->trans("HT");
						else
						$opt.= price($objp2->price_ttc,1).' '.$langs->trans("Currency".$conf->monnaie).' '.$langs->trans("TTC");
					}
					//si il n'y a pas de prix multiple on prend le prix de base du produit/service
					else
					{
						if ($objp->price_base_type == 'HT')
						$opt.= price($objp->price,1).' '.$langs->trans("Currency".$conf->monnaie).' '.$langs->trans("HT");
						else
						$opt.= price($objp->price_ttc,1).' '.$langs->trans("Currency".$conf->monnaie).' '.$langs->trans("TTC");
					}
				}
				else
				{
					if ($objp->price_base_type == 'HT')
					$opt.= price($objp->price,1).' '.$langs->trans("Currency".$conf->monnaie).' '.$langs->trans("HT");
					else
					$opt.= price($objp->price_ttc,1).' '.$langs->trans("Currency".$conf->monnaie).' '.$langs->trans("TTC");
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
			dolibarr_print_error($db);
		}
	}

	/**
	 *	\brief     	Retourne la liste des produits fournisseurs en Ajax si ajax activé ou renvoie à select_produits_fournisseurs_do
	 *	\param		socid			Id third party
	 *	\param     	selected        Produit pré-sélectionné
	 *	\param     	htmlname        Nom de la zone select
	 *  \param		filtertype      Filter on product type (''=nofilter, 0=product, 1=service)
	 *	\param     	filtre          Pour filtre sql
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
	 *	\param		socid   		Id société fournisseur (0 pour aucun filtre)
	 *	\param      selected        Produit pré-sélectionné
	 *	\param      htmlname        Nom de la zone select
	 *  \param		filtertype      Filter on product type (''=nofilter, 0=product, 1=service)
	 *	\param      filtre          Pour filtre sql
	 *	\param      ajaxkeysearch   Filtre des produits si ajax est utilisé
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
		$sql.= " WHERE p.envente = 1";
		if ($socid) $sql.= " AND pf.fk_soc = ".$socid;
		if (strval($filtertype) != '') $sql.=" AND p.fk_product_type=".$filtertype;
		if (! empty($filtre)) $sql.=" ".$filtre;
		if ($ajaxkeysearch && $ajaxkeysearch != '') $sql.=" AND (pf.ref_fourn like '%".$ajaxkeysearch."%' OR p.label like '%".$ajaxkeysearch."%')";
		$sql.= " ORDER BY pf.ref_fourn DESC";

		dolibarr_syslog("Form::select_produits_fournisseurs_do sql=".$sql,LOG_DEBUG);
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
				$opt.= dolibarr_trunc($objp->label,18).' - ';
				if ($objp->fprice != '') 	// Keep != ''
				{
					$opt.= price($objp->fprice);
					$opt.= $langs->trans("Currency".$conf->monnaie)."/".$objp->quantity;
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
						$opt.= price($objp->unitprice).$langs->trans("Currency".$conf->monnaie)."/".strtolower($langs->trans("Unit"));
						$opt.=")";
					}
					if ($objp->duration) $opt .= " - ".$objp->duration;
					if (! $socid) $opt .= " - ".dolibarr_trunc($objp->nom,8);
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
			dolibarr_print_error($db);
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

		dolibarr_syslog("Form::select_product_fourn_price sql=".$sql,LOG_DEBUG);
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
			dolibarr_print_error($db);
		}
	}

	/**
	 *    \brief      Retourne la liste déroulante des adresses de livraison
	 *    \param      selected        Id contact pré-sélectionn
	 *    \param      htmlname        Nom champ formulaire
	 */
	function select_adresse_livraison($selected='', $socid, $htmlname='adresse_livraison_id',$showempty=0)
	{
		// On recherche les utilisateurs
		$sql = "SELECT a.rowid, a.label";
		$sql .= " FROM ".MAIN_DB_PREFIX ."societe_adresse_livraison as a";
		$sql .= " WHERE a.fk_societe = ".$socid;
		$sql .= " ORDER BY a.label ASC";

		dolibarr_syslog("Form::select_adresse_livraison sql=".$sql);
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
			dolibarr_print_error($this->db);
		}
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
		dolibarr_syslog('Form::effectif_array sql='.$sql,LOG_DEBUG);
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
	 *    \brief      Renvoie la liste des formes juridiques existantes (pas de traduction car unique au pays)
	 *    \return     array      tableau des formes juridiques
	 */
	function forme_juridique_array()
	{
		$fj = array();

		$sql = "SELECT code, libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_forme_juridique";
		$sql.= " WHERE active = 1";
		$sql.= " ORDER BY code ASC";
		dolibarr_syslog('Form::forme_juridique_array sql='.$sql,LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ($i < $num)
			{
				$objp = $this->db->fetch_object($resql);
				$fj[$objp->code] = $objp->libelle!='-'?$objp->libelle:'';
				$i++;
			}
			$this->db->free($resql);
		}
		return $fj;
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
		dolibarr_syslog('Form::typent_array sql='.$sql,LOG_DEBUG);
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
	 *      \brief      Charge dans cache la liste des conditions de paiements possibles
	 *      \return     int             Nb lignes chargées, 0 si déjà chargées, <0 si ko
	 */
	function load_cache_conditions_paiements()
	{
		global $langs;

		if (sizeof($this->cache_conditions_paiements)) return 0;    // Cache déja chargé

		$sql = "SELECT rowid, code, libelle";
		$sql.= " FROM ".MAIN_DB_PREFIX."cond_reglement";
		$sql.= " WHERE active=1";
		$sql.= " ORDER BY sortorder";
		dolibarr_syslog('Form::load_cache_conditions_paiements sql='.$sql,LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				// Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
				$libelle=($langs->trans("PaymentConditionShort".$obj->code)!=("PaymentConditionShort".$obj->code)?$langs->trans("PaymentConditionShort".$obj->code):($obj->libelle!='-'?$obj->libelle:''));
				$this->cache_conditions_paiements[$obj->rowid]['code'] =$obj->code;
				$this->cache_conditions_paiements[$obj->rowid]['label']=$libelle;
				$i++;
			}
			return 1;
		}
		else {
			dolibarr_print_error($this->db);
			return -1;
		}
	}

	/**
	 *      \brief      Charge dans cache la liste des types de paiements possibles
	 *      \return     int             Nb lignes chargées, 0 si déjà chargées, <0 si ko
	 */
	function load_cache_types_paiements()
	{
		global $langs;

		if (sizeof($this->cache_types_paiements)) return 0;    // Cache déja chargé

		$sql = "SELECT id, code, libelle, type";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_paiement";
		$sql.= " WHERE active > 0";
		$sql.= " ORDER BY id";
		dolibarr_syslog('Form::load_cache_types_paiements sql='.$sql,LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				// Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
				$libelle=($langs->trans("PaymentTypeShort".$obj->code)!=("PaymentTypeShort".$obj->code)?$langs->trans("PaymentTypeShort".$obj->code):($obj->libelle!='-'?$obj->libelle:''));
				$this->cache_types_paiements[$obj->id]['code'] =$obj->code;
				$this->cache_types_paiements[$obj->id]['label']=$libelle;
				$this->cache_types_paiements[$obj->id]['type'] =$obj->type;
				$i++;
			}
			return $num;
		}
		else {
			dolibarr_print_error($this->db);
			return -1;
		}
	}


	/**
	 *      \brief      Retourne la liste des types de paiements possibles
	 *      \param      selected        Id du type de paiement pré-sélectionné
	 *      \param      htmlname        Nom de la zone select
	 *      \param      filtertype      Pour filtre
	 *		\param		addempty		Ajoute entrée vide
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
	 *      \param      selected        Id du mode de paiement pré-sélectionné
	 *      \param      htmlname        Nom de la zone select
	 *      \param      filtertype      Pour filtre
	 *      \param      format          0=id+libelle, 1=code+code, 2=code+libelle
	 *      \param      empty			1=peut etre vide, 0 sinon
	 * 		\param		noadmininfo		0=Add admin info, 1=Disable admin info
	 */
	function select_types_paiements($selected='',$htmlname='paiementtype',$filtertype='',$format=0, $empty=0, $noadmininfo=0)
	{
		global $langs,$user;

		dolibarr_syslog("Form::select_type_paiements $selected, $htmlname, $filtertype, $format",LOG_DEBUG);

		$filterarray=array();
		if ($filtertype == 'CRDT')  	$filterarray=array(0,2);
		elseif ($filtertype == 'DBIT') 	$filterarray=array(1,2);
		elseif ($filtertype != '' && $filtertype != '-1') $filterarray=split(',',$filtertype);

		$this->load_cache_types_paiements();

		print '<select class="flat" name="'.$htmlname.'">';
		if ($empty) print '<option value="">&nbsp;</option>';
		foreach($this->cache_types_paiements as $id => $arraytypes)
		{
			// On passe si on a demandé de filtrer sur des modes de paiments particuliers
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
	 *      \param      selected        Id pré-sélectionné
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
	 *    \brief      Retourne la liste déroulante des différents états d'une propal.
	 *                Les valeurs de la liste sont les id de la table c_propalst
	 *    \param      selected    etat pre-séléctionné
	 */
	function select_propal_statut($selected='')
	{
		$sql = "SELECT id, code, label, active FROM ".MAIN_DB_PREFIX."c_propalst";
		$sql .= " WHERE active = 1";

		dolibarr_syslog("Form::select_propal_statut sql=".$sql);
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
					// Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
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
			dolibarr_print_error($this->db);
		}
	}


	/**
	 *    \brief      Retourne la liste des comptes
	 *    \param      selected          Id compte pré-sélectionné
	 *    \param      htmlname          Nom de la zone select
	 *    \param      statut            Statut des comptes recherchés (0=open, 1=closed)
	 *    \param      filtre            Pour filtre sur la liste
	 *    \param      useempty          Affiche valeur vide dans liste
	 */
	function select_comptes($selected='',$htmlname='accountid',$statut=0,$filtre='',$useempty=0)
	{
		global $langs;

		$sql = "SELECT rowid, label, bank";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
		$sql.= " WHERE clos = '".$statut."'";
		if ($filtre) $sql.=" AND ".$filtre;
		$sql.= " ORDER BY rowid";

		dolibarr_syslog("Form::select_comptes sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			print '<select class="flat" name="'.$htmlname.'">';
			if ($useempty)
			{
				print '<option value="'.$obj->rowid.'">&nbsp;</option>';
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
			dolibarr_print_error($this->db);
		}
	}
	 
	/**
	 *    \brief    Retourne la liste des catégories du type choisi
	 *    \param    type			Type de categories (0=produit, 1=fournisseur, 2=client)
	 *    \param    selected    	Id categorie preselectionnee
	 *    \param    select_name		Nom formulaire HTML
	 */
	function select_all_categories($type,$selected='',$select_name="")
	{
		global $langs;
		$langs->load("categorie");

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
					$output.= '<option '.$add.'value="'.$cate_arbo[$key]['id'].'">'.$cate_arbo[$key]['fulllabel'].'</option>';
				}
			}
		}
		$output.= '</select>';
		$output.= "\n";
		return $output;
	}



	/**
	 *    \brief      Retourne la liste déroulante des civilite actives
	 *    \param      selected    civilite pré-sélectionnée
	 */
	function select_civilite($selected='')
	{
		global $conf,$langs,$user;
		$langs->load("dict");

		$sql = "SELECT rowid, code, civilite, active FROM ".MAIN_DB_PREFIX."c_civilite";
		$sql .= " WHERE active = 1";

		dolibarr_syslog("Form::select_civilite sql=".$sql);
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
					// Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
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
			dolibarr_print_error($this->db);
		}
	}


	/**
	 *    \brief      Retourne la liste déroulante des formes juridiques tous pays confondus ou pour un pays donné.
	 *    \remarks    Dans le cas d'une liste tous pays confondu, on affiche une rupture sur le pays
	 *    \param      selected        Code forme juridique a pré-sélectionné
	 *    \param      pays_code       0=liste tous pays confondus, sinon code du pays à afficher
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

		dolibarr_syslog("Form::select_forme_juridique sql=".$sql);
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
						// Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
						print $obj->code . ' - ';
						print ($langs->trans("JuridicalStatus".$obj->code)!="JuridicalStatus".$obj->code?$langs->trans("JuridicalStatus".$obj->code):($obj->nom!='-'?$langs->convToOutputCharset($obj->nom):''));
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
			dolibarr_print_error($this->db);
		}
	}


	/**
	 *    	\brief  Affiche formulaire de demande de confirmation
	 *    	\param  page        	page
	 *    	\param  title       	title
	 *    	\param  question    	question
	 *    	\param  action      	action
	 *		\param	formquestion	an array with forms complementary inputs
	 * 		\param	selectedchoice	"" or "no" or "yes"
	 */
	function form_confirm($page, $title, $question, $action, $formquestion='', $selectedchoice="")
	{
		global $langs;

		print '<form method="post" action="'.$page.'" class="notoptoleftroright">';
		print '<input type="hidden" name="action" value="'.$action.'">';

		print '<table width="100%" class="valid">';

		// Ligne titre
		print '<tr class="validtitre"><td class="validtitre" colspan="3">'.img_picto('','recent').' '.$title.'</td></tr>';

		// Ligne formulaire
		if ($formquestion)
		{
			print '<tr class="valid"><td class="valid" colspan="3">';
			print '<table class="notopnoleftnoright" width="100%">';
			print '<tr><td colspan="3" valign="top">'.$formquestion['text'].'</td></tr>';
			foreach ($formquestion as $key => $input)
			{
				if ($input['type'] == 'text')
				{
					print '<tr><td valign="top">'.$input['label'].'</td><td colspan="2"><input type="text" class="flat" name="'.$input['name'].'" size="'.$input['size'].'" value="'.$input['value'].'"></td></tr>';
				}
				if ($input['type'] == 'select')
				{
					print '<tr><td valign="top">';
					print $this->select_array($input['name'],$input['values'],'',1);
					print '</td></tr>';
				}
				if ($input['type'] == 'checkbox')
				{
					print '<tr>';
					print '<td valign="top">'.$input['label'].' &nbsp;';
					print '<input type="checkbox" class="flat" name="'.$input['name'].'"';
					if ($input['value'] != 'false') print ' checked="true"';
					if ($input['disabled']) print ' disabled="true"';
					print '></td>';
					print '<td valign="top" align="left">&nbsp;</td>';
					print '<td valign="top" align="left">&nbsp;</td>';
					print '</tr>';
				}
				if ($input['type'] == 'radio')
				{
					$i=0;
					foreach($input['values'] as $selkey => $selval)
					{
						print '<tr>';
						if ($i==0) print '<td valign="top">'.$input['label'].'</td>';
						else print '<td>&nbsp;</td>';
						print '<td valign="top" width="20"><input type="radio" class="flat" name="'.$input['name'].'" value="'.$selkey.'"';
						if ($input['disabled']) print ' disabled="true"';
						print '></td>';
						print '<td valign="top" align="left">';
						print $selval;
						print '</td></tr>';
						$i++;
					}
				}
			}
			print '</table>';
			print '</td></tr>';

			//print '<tr class="valid"><td class="valid" colspan="3"><hr></td></tr>';
		}

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
	}


	/**
	 *    \brief      Affiche formulaire de selection de projet
	 *    \param      page        Page
	 *    \param      socid       Id societe
	 *    \param      selected    Id projet pré-sélectionné
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
			print '<table class="noborder" cellpadding="0" cellspacing="0">';
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
				print '<a href="'.DOL_URL_ROOT.'/projet/fiche.php?id='.$selected.'">'.$projet->title.'</a>';
			} else {
				print "&nbsp;";
			}
		}
	}

	/**
	 *    	\brief      Affiche formulaire de selection de conditions de paiement
	 *    	\param      page        	Page
	 *    	\param      selected    	Id condition pré-sélectionné
	 *    	\param      htmlname    	Nom du formulaire select
	 *		\param		addempty		Ajoute entrée vide
	 */
	function form_conditions_reglement($page, $selected='', $htmlname='cond_reglement_id', $addempty=0)
	{
		global $langs;
		if ($htmlname != "none")
		{
			print '<form method="post" action="'.$page.'">';
			print '<input type="hidden" name="action" value="setconditions">';
			print '<table class="noborder" cellpadding="0" cellspacing="0">';
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
	 *    \brief      Affiche formulaire de selection de l'assujétissement à la TVA
	 *    \param      page        Page
	 *    \param      selected    Id condition pré-sélectionnée
	 *    \param      htmlname    Nom du formulaire select
	 */
	function form_assujetti_tva($page, $selected='', $htmlname='')
	{
		global $langs;
		$options = array(0=>"non",1=>"oui");
		if ($htmlname != "none")
		{
			print '<form method="post" action="'.$page.'">';
			print '<input type="hidden" name="action" value="setassujtva">';
			print '<table class="noborder" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			$this->select_assujetti_tva($selected,$htmlname);
			print '</td>';
			print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
			print '</tr></table></form>';
		}
		else
		{
			if ($selected != "")
			{
				print $options[$selected];
			} else {
				print "&nbsp;";
			}
		}
	}


	/**
	 *    \brief      Affiche formulaire de selection des modes de reglement
	 *    \param      page        Page
	 *    \param      selected    Id mode pré-sélectionné
	 *    \param      htmlname    Nom du formulaire select
	 */
	function form_modes_reglement($page, $selected='', $htmlname='mode_reglement_id')
	{
		global $langs;
		if ($htmlname != "none")
		{
			print '<form method="post" action="'.$page.'">';
			print '<input type="hidden" name="action" value="setmode">';
			print '<table class="noborder" cellpadding="0" cellspacing="0">';
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
		print '<table class="noborder" cellpadding="0" cellspacing="0">';
		print '<tr><td>';

		print '<select class="flat" name="'.$htmlname.'">';
		if ($empty) print '<option value="">&nbsp;</option>';

		dolibarr_syslog('Form::form_prospect_level',LOG_DEBUG);
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
		else dolibarr_print_error($this->db);
		print '</select>';

		print '</td>';
		print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
		print '</tr></table></form>';
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
				print ' <input type="submit" class="button" value="';
				if (! $filter || $filter=='fk_facture_source IS NULL') print $langs->trans("UseDiscount");
				else print $langs->trans("UseCreditNoteInInvoicePayment");
				print '">';
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
	 *    \param      selected    Id contact pré-sélectionné
	 *    \param      htmlname    Nom du formulaire select
	 */
	function form_contacts($page, $societe, $selected='', $htmlname='contactidp')
	{
		global $langs;
		if ($htmlname != "none")
		{
			print '<form method="post" action="'.$page.'">';
			print '<input type="hidden" name="action" value="set_contact">';
			print '<table class="noborder" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			$num=$this->select_contacts($societe->id, $selected, $htmlname);
			if ($num==0)
			{
				print '<font class="error">Cette societe n\'a pas de contact, veuillez en créer un avant de faire votre proposition commerciale</font><br>';
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
	 *    	\param      selected    	Id condition pré-sélectionné
	 *    	\param      htmlname    	Nom du formulaire select
	 *		\param		origin        	Origine de l'appel pour pouvoir créer un retour
	 *      \param      originid      	Id de l'origine
	 */
	function form_adresse_livraison($page, $selected='', $socid, $htmlname='adresse_livraison_id', $origin='', $originid='')
	{
		global $langs,$conf;
		if ($htmlname != "none")
		{
			print '<form method="post" action="'.$page.'">';
			print '<input type="hidden" name="action" value="setdeliveryadress">';
			print '<table class="noborder" cellpadding="0" cellspacing="0">';
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
				$livraison->fetch_adresse($selected);
				print '<a href='.DOL_URL_ROOT.'/comm/adresse_livraison.php?socid='.$livraison->socid.'&idl='.$livraison->idl.'&action=edit&origin='.$origin.'&originid='.$originid.'>'.$livraison->label.'</a>';
			}
			else
			{
				print "&nbsp;";
			}
		}
	}

	/**
	 *    \brief     Retourne la liste des devises, dans la langue de l'utilisateur
	 *    \param     selected    code devise pré-sélectionné
	 *    \param     htmlname    nom de la liste deroulante
	 *    \todo      trier liste sur noms après traduction plutot que avant
	 */
	function select_currency($selected='',$htmlname='currency_id')
	{
		global $conf,$langs,$user;
		$langs->load("dict");

		if ($selected=='euro' || $selected=='euros') $selected='EUR';   // Pour compatibilité

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
					// Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
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
			dolibarr_print_error($this->db);
			return 1;
		}
	}


	/**
	 *      \brief      Output an HTML select vat rate
	 *      \param      name                Nom champ html
	 *      \param      selectedrate        Forçage du taux tva pré-sélectionné. Mettre '' pour aucun forcage.
	 *      \param      societe_vendeuse    Objet société vendeuse
	 *      \param      societe_acheteuse   Objet société acheteuse
	 *      \param      taux_produit        Taux par defaut du produit vendu
	 *      \param      info_bits           Miscellanous information on line
	 *      \remarks    Si vendeur non assujeti à TVA, TVA par défaut=0. Fin de règle.
	 *                  Si le (pays vendeur = pays acheteur) alors la TVA par défaut=TVA du produit vendu. Fin de règle.
	 *                  Si (vendeur et acheteur dans Communauté européenne) et bien vendu = moyen de transports neuf (auto, bateau, avion), TVA par défaut=0 (La TVA doit être payé par l'acheteur au centre d'impots de son pays et non au vendeur). Fin de règle.
	 *                  Si (vendeur et acheteur dans Communauté européenne) et bien vendu autre que transport neuf alors la TVA par défaut=TVA du produit vendu. Fin de règle.
	 *                  Sinon la TVA proposée par défaut=0. Fin de règle.
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

		// Définition du taux à pré-sélectionner (si defaulttx non forcé et donc vaut -1 ou '')
		if ($defaulttx < 0 || strlen($defaulttx) == 0)
		{
			$defaulttx=get_default_tva($societe_vendeuse,$societe_acheteuse,$taux_produit);
			$defaultnpr=get_default_npr($societe_vendeuse,$societe_acheteuse,$taux_produit);
		}
		// Si taux par defaut n'a pu etre déterminé, on prend dernier de la liste.
		// Comme ils sont triés par ordre croissant, dernier = plus élevé = taux courant
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
	 *      Les champs sont pré-sélectionnés avec:
	 *            	- La date set_time (timestamps ou date au format YYYY-MM-DD ou YYYY-MM-DD HH:MM)
	 *            	- La date du jour si set_time vaut ''
	 *            	- Aucune date (champs vides) si set_time vaut -1 (dans ce cas empty doit valoir 1)
	 *		@param	set_time 		Date de pré-sélection
	 *		@param	prefix			Prefix pour nom champ
	 *		@param	h				1=Affiche aussi les heures
	 *		@param	m				1=Affiche aussi les minutes
	 *		@param	empty			0=Champ obligatoire, 1=Permet une saisie vide
	 *		@param	form_name 		Nom du formulaire de provenance. Utilisé pour les dates en popup style andre.
	 *		@param	d				1=Affiche aussi les jours, mois, annees
	 */
	function select_date($set_time='', $prefix='re', $h=0, $m=0, $empty=0, $form_name="", $d=1)
	{
		global $conf,$langs;

		if($prefix=='') $prefix='re';
		if($h == '') $h=0;
		if($m == '') $m=0;
		if($empty == '') $empty=0;

		if (! $set_time && $empty == 0) $set_time = time();

		// Analyse de la date de pré-selection
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
					$formated_date=dolibarr_print_date($set_time,$conf->format_date_short);
				}

				// Calendrier popup version eldy
				if ("$conf->use_popup_calendar" == "eldy")	// Laisser conf->use_popup_calendar entre quote
				{
					// Zone de saisie manuelle de la date
					print '<input id="'.$prefix.'" name="'.$prefix.'" type="text" size="10" maxlength="11" value="'.$formated_date.'"';
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
					print '<input id="'.$prefix.'" type="text" name="'.$prefix.'" size="10" value="'.$formated_date.'"';
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
					print dolibarr_print_date(mktime(1,1,1,$month,1,2000),"%b");
					print "</option>";
				}
				print "</select>";
	    
				// Année
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

	}

	/**
	 \brief  	Fonction servant a afficher une durée dans une liste déroulante
	 \param		prefix   	prefix
	 \param  	iSecond  	Nombre de secondes
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
	 *	\param	value_as_key    1 pour utiliser la valeur comme clé
	 *	\param	optionType      Type de l'option: 1 pour des fonctions javascript
	 *	\param  option          Valeur de l'option en fonction du type choisi
	 *	\param  translate       Traduire la valeur
	 * 	\param	maxlen			Length maximum for labels
	 */
	function select_array($htmlname, $array, $id='', $show_empty=0, $key_in_label=0, $value_as_key=0, $optionType=0, $option='', $translate=0, $maxlen=0)
	{
		global $langs;
		// \TODO Simplify optionType and option (only one should be necessary)
		if ($optionType == 1 && $option != '')
		{
			print '<select class="flat" name="'.$htmlname.'" '.$option.'>';
		}
		else
		{
			print '<select class="flat" name="'.$htmlname.'">';
		}

		if ($show_empty)
		{
			print '<option value="-1"'.($id==-1?' selected="true"':'').'>&nbsp;</option>'."\n";
		}

		while (list($key, $value) = each ($array))
		{
			print '<option value="'.($value_as_key?$value:$key).'"';
			// Si il faut pré-sélectionner une valeur
			if ($id != '' && ($id == $key || $id == $value))
			{
				print ' selected="true"';
			}

			print '>';

			if ($key_in_label)
			{
				$newval=($translate?$langs->trans($value):$value);
				$selectOptionValue = $key.' - '.($maxlen?dolibarr_trunc($newval,$maxlen):$newval);
				print $selectOptionValue;
			}
			else
			{
				$newval=($translate?$langs->trans($value):$value);
				$selectOptionValue = ($maxlen?dolibarr_trunc($newval,$maxlen):$newval); 
				if ($value == '' || $value == '-') { $selectOptionValue='&nbsp;'; }
				print $selectOptionValue;
			}
			print "</option>\n";
		}

		print "</select>";
	}


	/**
	 *    \brief      Selection de oui/non en chaine (renvoie yes/no)
	 *    \param      name            Nom du select
	 *    \param      value           Valeur pré-sélectionnée
	 *    \param      option          0 retourne yes/no, 1 retourne 1/0
	 */
	function selectyesno($name,$value='',$option=0)
	{
		global $langs;

		$yes="yes"; $no="no";

		if ($option)
		{
			$yes="1";
			$no="0";
		}

		$resultyesno = '<select class="flat" name="'.$name.'">'."\n";
		if (("$value" == 'yes') || ($value == 1))
		{
			$resultyesno .= '<option value="'.$yes.'" selected="true">'.$langs->trans("yes").'</option>'."\n";
			$resultyesno .= '<option value="'.$no.'">'.$langs->trans("no").'</option>'."\n";
		}
		else
		{
			$resultyesno .= '<option value="'.$yes.'">'.$langs->trans("yes").'</option>'."\n";
			$resultyesno .= '<option value="'.$no.'" selected="true">'.$langs->trans("no").'</option>'."\n";
		}
		$resultyesno .= '</select>'."\n";
		return $resultyesno;
	}



	/**
	 *    \brief      Retourne la liste des modèles d'export
	 *    \param      selected          Id modèle pré-sélectionné
	 *    \param      htmlname          Nom de la zone select
	 *    \param      type              Type des modèles recherchés
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
			dolibarr_print_error($this->db);
		}
	}

	/**
	 *    \brief      Retourne la liste des mois
	 *    \param      selected          Id mois pré-sélectionné
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
	 *    \brief      Retourne la liste des années
	 *    \param      selected          Année pré-sélectionné
	 *    \param      htmlname          Nom de la zone select
	 *    \param      useempty          Affiche valeur vide dans liste
	 *    \param      $min_year         Valeur minimum de l'année dans la liste (par défaut année courante -10)
	 *    \param      $max_year         Valeur maximum de l'année dans la liste (par défaut année courante + 5)
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
	 * 	  \return     string    	Portion HTML avec ref + boutons nav
	 */
	function showrefnav($object,$paramid,$morehtml='',$shownav=1,$fieldid='rowid',$fieldref='ref',$morehtmlref='')
	{
		$ret='';

		$object->load_previous_next_ref($object->next_prev_filter,$fieldid);
		$previous_ref = $object->ref_previous?'<a href="'.$_SERVER["PHP_SELF"].'?'.$paramid.'='.urlencode($object->ref_previous).'">'.img_previous().'</a>':'';
		$next_ref     = $object->ref_next?'<a href="'.$_SERVER["PHP_SELF"].'?'.$paramid.'='.urlencode($object->ref_next).'">'.img_next().'</a>':'';

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

}

?>
