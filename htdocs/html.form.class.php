<?php
/* Copyright (c) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@cap-networks.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/html.form.class.php
        \brief      Fichier de la classe des fonctions prédéfinie de composants html
        \version    $Revision$
*/


/**
        \class      Form
        \brief      Classe permettant la génération de composants html
*/

class Form
{
    var $db;
    var $errorstr;
    
    var $cache_types_paiements_code=array();
    var $cache_types_paiements_libelle=array();
    var $cache_conditions_paiements_code=array();
    var $cache_conditions_paiements_libelle=array();


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
    	\brief      Retourne propriétés pour affichae d'un tooltip
        \param      htmltooltip     Contenu html du tooltip
        \return     string          Chaine des propriétés de declenchement du tooltip
    */
    function tooltip_properties($htmltooltip='Text for tooltip')
    {
        global $conf;
        $s ='';
        if ($conf->use_javascript && $htmltooltip)
        {
        	$htmltooltip=ereg_replace("'","\'",$htmltooltip);
            $s.=' onmouseover="showtip(\''.$htmltooltip.'\')"';
            $s.=' onMouseout="hidetip()"';
        }
        return $s;
    }

    /**
    	\brief      Efface champ alt et title pour permettre utiliser dans un tooltip
        \param      string          Chaine a nettoyer
        \return     string          Chaine nettoyé
    */
    function tooltip_sanitize($string)
    {
        global $conf;
        if ($conf->use_javascript)
        {
            // Supprime alt et title de text pour eviter conflit avec tooltip
            $string=eregi_replace('alt="[^"]+"','',$string);
            $string=eregi_replace('title="[^"]+"','',$string);
        }
        return $string;
    }

    /**
     *    \brief      Retourne la liste déroulante des départements/province/cantons tout pays confondu ou pour un pays donné.
     *    \remarks    Dans le cas d'une liste tout pays confondus, l'affichage fait une rupture sur le pays.
     *    \remarks    La cle de la liste est le code (il peut y avoir plusieurs entrée pour
     *                un code donnée mais dans ce cas, le champ pays diffère).
     *                Ainsi les liens avec les départements se font sur un département indépendemment de nom som.
     *    \param      selected        code forme juridique a présélectionné
     *    \param      pays_code       0=liste tous pays confondus, sinon code du pays à afficher
     */
    function select_departement($selected='',$pays_code=0)
    {
	    dolibarr_syslog("html.form.class::select_departement selected=$selected, pays_code=$pays_code");
	    
	    global $conf,$langs;
	    $langs->load("dict");
	    
	    $htmlname='departement_id';
	    
	    // On recherche les départements/cantons/province active d'une region et pays actif
	    $sql = "SELECT d.rowid, d.code_departement as code , d.nom, d.active, p.libelle as libelle_pays, p.code as code_pays FROM";
	    $sql .= " ".MAIN_DB_PREFIX ."c_departements as d, ".MAIN_DB_PREFIX."c_regions as r,".MAIN_DB_PREFIX."c_pays as p";
	    $sql .= " WHERE d.fk_region=r.code_region and r.fk_pays=p.rowid";
	    $sql .= " AND d.active = 1 AND r.active = 1 AND p.active = 1";
	    if ($pays_code) $sql .= " AND p.code = '".$pays_code."'";
	    $sql .= " ORDER BY p.code, d.code_departement";
	    dolibarr_syslog("html.form.class::select_departement sql=$sql");
	    
	    $result=$this->db->query($sql);
	    if ($result)
	    {
	        print '<select class="flat" name="'.$htmlname.'">';
	        if ($pays_code) print '<option value="0">&nbsp;</option>';
	        $num = $this->db->num_rows($result);
	        $i = 0;
	    	dolibarr_syslog("html.form.class::select_departement num=$num");
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
   *                de nom som.
   */
	 
  function select_region($selected='',$htmlname='region_id')
  {
    global $conf,$langs;
    $langs->load("dict");

    $sql = "SELECT r.rowid, r.code_region as code, r.nom as libelle, r.active, p.libelle as libelle_pays FROM ".MAIN_DB_PREFIX."c_regions as r, ".MAIN_DB_PREFIX."c_pays as p";
    $sql .= " WHERE r.fk_pays=p.rowid AND r.active = 1 and p.active = 1 ORDER BY libelle_pays, libelle ASC";

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
		  if ($pays == '' || $pays != $obj->libelle_pays) {
		    // Affiche la rupture
		    print '<option value="-1">----- '.$obj->libelle_pays." -----</option>\n";
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

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$i = 0;
			$num = $this->db->num_rows();
			while ($i < $num)
			{
				$row = $this->db->fetch_row();
	
				$listemethodes[$row[0]] = $row[1];
	
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
	 *    \param     selected         Code pays pré-sélectionné
	 *    \param     htmlname         Nom de la liste deroulante
	 *    \param     htmloption       Options html sur le select
	 *    \todo      trier liste sur noms après traduction plutot que avant
	 */
	function select_pays($selected='',$htmlname='pays_id',$htmloption='')
	{
	    global $conf,$langs;
	    $langs->load("dict");
	    
	    $sql = "SELECT rowid, libelle, code, active FROM ".MAIN_DB_PREFIX."c_pays";
	    $sql .= " WHERE active = 1";
	    $sql .= " ORDER BY code ASC;";
	    
	    if ($this->db->query($sql))
      {
        print '<select class="flat" name="'.$htmlname.'" '.$htmloption.'>';
        $num = $this->db->num_rows();
        $i = 0;
        if ($num)
	  {
            $foundselected=false;
            while ($i < $num)
	      {
                $obj = $this->db->fetch_object();
                if ($selected > 0 && $selected == $obj->rowid)
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
    else {
      dolibarr_print_error($this->db);
      return 1;
    }
  }


    /**
     *    \brief      Retourne la liste déroulante des langues disponibles
     *    \param      selected        Langue présélectionnée
     *    \param      htmlname        Nom de la zone select
     *    \param      showauto        Affiche choix auto
     */
    function select_lang($selected='',$htmlname='lang_id',$showauto=0)
    {
        global $langs;
    
        $langs_available=$langs->get_available_languages();
    
        print '<select class="flat" name="'.$htmlname.'">';
        if ($showauto)
        {
            print '<option value="auto"';
            if ($selected == 'auto') print ' selected="true"';
            print '>'.$langs->trans("AutoDetectLang").'</option>';
        }
        $num = count($langs_available);
        $i = 0;
        if ($num)
        {
            while ($i < $num)
            {
                if ($selected == $langs_available[$i])
                {
                    print '<option value="'.$langs_available[$i].'" selected="true">'.$langs_available[$i].'</option>';
                }
                else
                {
                    print '<option value="'.$langs_available[$i].'">'.$langs_available[$i].'</option>';
                }
                $i++;
            }
        }
        print '</select>';
    }


    /**
     *    \brief      Retourne la liste déroulante des menus disponibles
     *    \param      selected        Menu pré-sélectionnée
     *    \param      htmlname        Nom de la zone select
     *    \param      dirmenu         Repértoire à scanner
     */
    function select_menu($selected='',$htmlname,$dirmenu)
    {
        global $langs;
    
        if ($selected == 'eldy.php') $selected='eldy_backoffice.php';  // Pour compatibilité
        
        print '<select class="flat" name="'.$htmlname.'">';
        $handle=opendir($dirmenu);
        while (($file = readdir($handle))!==false)
        {
            if (is_file($dirmenu."/".$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
            {
                $filelib=eregi_replace('\.php$','',$file);
                if ($file == $selected)
                {
                    print '<option value="'.$file.'" selected="true">'.$filelib.'</option>';
                }
                else
                {
                    print '<option value="'.$file.'">'.$filelib.'</option>';
                }
            }
        }
        print '</select>';
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
	 *    \brief      Retourne la liste déroulante des sociétés
	 *    \param      selected        Societe présélectionnée
	 *    \param      htmlname        Nom champ formulaire
	 *    \param      filter          Criteres optionnels de filtre
	 */
	function select_societes($selected='',$htmlname='soc_id',$filter='')
    {
        // On recherche les societes
        $sql = "SELECT s.idp, s.nom FROM";
        $sql.= " ".MAIN_DB_PREFIX ."societe as s";
        if ($filter) $sql.= " WHERE $filter";
        $sql.= " ORDER BY nom ASC";
    
        $resql=$this->db->query($sql);
        if ($resql)
        {
            print '<select class="flat" name="'.$htmlname.'">';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    if ($selected > 0 && $selected == $obj->idp)
                    {
                        print '<option value="'.$obj->idp.'" selected="true">'.$obj->nom.'</option>';
                    }
                    else
                    {
                        print '<option value="'.$obj->idp.'">'.$obj->nom.'</option>';
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
	 *    \brief      Retourne la liste déroulante des remises fixes
	 *    \param      selected        Id remise fixe présélectionnée
	 *    \param      htmlname        Nom champ formulaire
	 *    \param      filter          Criteres optionnels de filtre
	 */
	function select_remises($selected='',$htmlname='remise_id',$filter='',$socid)
    {
        global $langs,$conf;
        
        // On recherche les societes
        $sql = "SELECT re.rowid, re.amount_ht as amount, re.description FROM";
        $sql.= " ".MAIN_DB_PREFIX ."societe_remise_except as re";
        $sql.= " WHERE fk_soc = ".$socid;
        if ($filter) $sql.= " AND $filter";
        $sql.= " ORDER BY re.description ASC";
    
        $resql=$this->db->query($sql);
        if ($resql)
        {
            print '<select class="flat" name="'.$htmlname.'">';
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                print '<option value="0">&nbsp;</option>';
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    if ($selected > 0 && $selected == $obj->rowid)
                    {
                        print '<option value="'.$obj->rowid.'" selected="true">'.dolibarr_trunc($obj->description,40).' ('.$obj->amount.' '.$langs->trans("Currency".$conf->monnaie).')'.'</option>';
                    }
                    else
                    {
                        print '<option value="'.$obj->rowid.'">'.dolibarr_trunc($obj->description,40).' ('.$obj->amount.' '.$langs->trans("Currency".$conf->monnaie).')'.'</option>';
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
     *    	\brief      Retourne la liste déroulante des contacts d'une société donnée
     *    	\param      socid      	Id de la société
     *    	\param      selected   	Id contact pré-sélectionn
     *    	\param      htmlname  	Nom champ formulaire ('none' pour champ non editable)
     *		\return		int			<0 si ko, >=0 si ok
     */
    function select_contacts($socid,$selected='',$htmlname='contactid')
    {
	        // On recherche les societes
	        $sql = "SELECT s.idp, s.name, s.firstname FROM";
	        $sql.= " ".MAIN_DB_PREFIX ."socpeople as s";
	        $sql.= " WHERE fk_soc=".$socid;
	        $sql.= " ORDER BY s.name ASC";
	    
	        $resql=$this->db->query($sql);
	        if ($resql)
	        {
				$num=$this->db->num_rows();
                if ($num==0) return 0;
	        	
	            if ($htmlname != 'none') print '<select class="flat" name="'.$htmlname.'">';
	            $num = $this->db->num_rows();
	            $i = 0;
	            if ($num)
	            {
	                while ($i < $num)
	                {
	                    $obj = $this->db->fetch_object();

			            if ($htmlname != 'none')
			            {
		                    if ($selected && $selected == $obj->idp)
		                    {
		                        print '<option value="'.$obj->idp.'" selected="true">'.$obj->name.' '.$obj->firstname.'</option>';
		                    }
		                    else
		                    {
		                        print '<option value="'.$obj->idp.'">'.$obj->name.' '.$obj->firstname.'</option>';
		                    }
						}
						else
						{
							if ($selected == $obj->idp) print $obj->name.' '.$obj->firstname;
						}
	                    $i++;
	                }
	            }
	            if ($htmlname != 'none') print '</select>';
	            return 1;
	        }
	        else
	        {
	            dolibarr_print_error($this->db);
	            return -1;
	        }
    }
    
    
    /**
     *    \brief      Retourne la liste déroulante des utilisateurs
     *    \param      selected        Id contact pré-sélectionn
     *    \param      htmlname        Nom champ formulaire
     */
    function select_users($selected='',$htmlname='userid')
    {
        // On recherche les utilisateurs
        $sql = "SELECT u.rowid, u.name, u.firstname FROM ";
        $sql .= MAIN_DB_PREFIX ."user as u";
        $sql .= " ORDER BY u.name ASC";
    
        if ($this->db->query($sql))
        {
            print '<select class="flat" name="'.$htmlname.'">';
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
	 *      \brief      Affiche la liste déroulante des projets d'une société donnée
	 *      \param      socid       Id société
	 *      \param      selected    Id projet présélectionné
	 *      \param      htmlname    Nom de la zone html
	 *      \return     int         Nbre de projet si ok, <0 si ko
	 */
	function select_projects($socid='', $selected='', $htmlname='projectid')
	{
		// On recherche les projets
		$sql = 'SELECT p.rowid, p.title FROM ';
		$sql.= MAIN_DB_PREFIX .'projet as p';
		$sql.= " WHERE fk_soc='".$socid."'";
		$sql.= " ORDER BY p.title ASC";

		$result=$this->db->query($sql);
		if ($result)
		{
			print '<select class="flat" name="'.$htmlname.'">';
    		print '<option value="0">&nbsp;</option>';
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object();
					if (!empty($selected) && $selected == $obj->rowid)
					{
						print '<option value="'.$obj->rowid.'" selected="true">'.$obj->title.'</option>';
					}
					else
					{
						print '<option value="'.$obj->rowid.'">'.$obj->title.'</option>';
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
			return -1;
		}
	}
/**
    *    \brief      Retourne la liste des produits en Ajax si ajax activé ou renvoie à select_produits_do
    *    \param      selected        Produit présélectionné
    *    \param      htmlname        Nom de la zone select
    *    \param      filtretype      Pour filtre sur type de produit
    *    \param      limit           Limite sur le nombre de lignes retournées
    */
    function select_produits($selected='',$htmlname='productid',$filtretype='',$limit=20,$price_level=0)
    {
		global $langs,$conf,$user;
		if($conf->use_ajax)
		{
			print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/lib/prototype.js"></script>';
			print $langs->trans("Ref").':<input type="text" size="8" name="ajkeyref'.$htmlname.'" id="ajkeyref'.$htmlname.'">&nbsp; &nbsp;';
			print $langs->trans("Label").':<input type="text" size="16" name="ajkeylabel'.$htmlname.'" id="ajkeylabel'.$htmlname.'">';
			print '<input type="hidden" name="'.$htmlname.'" id="'.$htmlname.'" value="">';
			print '<script type="text/javascript">';
			print 'var url = \''.DOL_URL_ROOT.'/ajaxresponse.php\';';
			print 'new Form.Element.Observer($("ajkeyref'.$htmlname.'"), 1, function(){var myAjax = new Ajax.Updater( {success: \'ajdynfield'.$htmlname.'\'}, url, {method: \'get\', parameters: "keyref="+$("ajkeyref'.$htmlname.'").value+"&htmlname='.$htmlname.'&price_level='.$price_level.'"});});';
			print 'new Form.Element.Observer($("ajkeylabel'.$htmlname.'"), 1, function(){var myAjax = new Ajax.Updater( {success: \'ajdynfield'.$htmlname.'\'}, url, {method: \'get\', parameters: "keylabel="+$("ajkeylabel'.$htmlname.'").value+"&htmlname='.$htmlname.'&price_level='.$price_level.'"});});';
			print 'function publish_selvalue(obj){$("'.$htmlname.'").value = obj.options[obj.selectedIndex].value;}';
			print '</script>';
			print '<div class="notopnoleftnoright" id="ajdynfield'.$htmlname.'"></div>';
		}
		else
		{
			$this->select_produits_do($selected,$htmlname,$filtretype,$limit,$price_level);
		}
		
	}
	
    /**
     *    \brief      Retourne la liste des produits
     *    \param      selected        Produit présélectionné
     *    \param      htmlname        Nom de la zone select
     *    \param      filtretype      Pour filtre sur type de produit
     *    \param      limit           Limite sur le nombre de lignes retournées
     */
    function select_produits_do($selected='',$htmlname='productid',$filtretype='',$limit=20,$price_level=0,$ajaxkeyref='',$ajaxkeylabel='')
    {
        global $langs,$conf,$user;
    	$user->getrights("categorie");

        $sql = "SELECT ";
        if ($conf->categorie->enabled && ! $user->rights->categorie->voir)
        {
        	$sql.="DISTINCT";	
        }
        $sql.= " p.rowid, p.label, p.ref, p.price, p.duration";
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p ";
        if ($conf->categorie->enabled && ! $user->rights->categorie->voir)
        {
           	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON p.rowid = cp.fk_product";
        	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as c ON cp.fk_categorie = c.rowid";
        }
        $sql.= " WHERE p.envente = 1";
        if ($conf->categorie->enabled && ! $user->rights->categorie->voir)
        {
        	$sql.= ' AND IFNULL(c.visible,1)=1';
        }
        if ($filtretype && $filtretype != '') $sql.=" AND p.fk_product_type=".$filtretype;
        if ($ajaxkeyref && $ajaxkeyref != '') $sql.=" AND p.ref like '%".$ajaxkeyref."%'";
        if ($ajaxkeylabel && $ajaxkeylabel != '') $sql.=" AND p.label like '%".$ajaxkeylabel."%'";
        $sql.= " ORDER BY p.nbvente DESC";
        if ($limit) $sql.= " LIMIT $limit";

		$result=$this->db->query($sql);
		if (! $result) dolibarr_print_error($this->db);
		
		// Multilang : on construit une liste des traductions des produits listés
		if ($conf->global->MAIN_MULTILANGS)
		{
			$sqld = "SELECT d.fk_product, d.label";
			$sqld.= " FROM ".MAIN_DB_PREFIX."product as p, ".MAIN_DB_PREFIX."product_det as d ";
			$sqld.= " WHERE d.fk_product=p.rowid AND p.envente=1 AND d.lang='". $langs->getDefaultLang() ."'";
			$sqld.= " ORDER BY p.nbvente DESC";
			$resultd = $this->db->query($sqld);
			if ( $resultd ) $objtp = $this->db->fetch_object($resultd);
		}
        
        if ($result)
        {
            $num = $this->db->num_rows($result);

            if ($conf->use_ajax)
            {
				if (! $num)
				{
					print $langs->trans("NoProductMatching").' ';	
				}
//				else
//				{
					print '<select class="flat" name="'.$htmlname.'" onchange="publish_selvalue(this);">';
		            print "<option value=\"0\" selected=\"true\">&nbsp;</option>";
//				}
			}
			else
			{
				print '<select class="flat" name="'.$htmlname.'">';
	            print "<option value=\"0\" selected=\"true\">&nbsp;</option>";
            }
    
            $i = 0;
            while ($i < $num)
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
						$sql= "SELECT price ";
						$sql.= "FROM ".MAIN_DB_PREFIX."product_price ";
						$sql.= "where fk_product='".$objp->rowid."' and price_level=".$price_level;
						$sql.= " order by date_price DESC limit 1";
						$result2 = $this->db->query($sql) ;
						$result2 = $this->db->fetch_array($result2);
						if($result2["price"])
							$opt.= $result2["price"].' '.$langs->trans("Currency".$conf->monnaie);
						else
							$opt.= $objp->price.' '.$langs->trans("Currency".$conf->monnaie);
				}
				else
                	$opt.= $objp->price.' '.$langs->trans("Currency".$conf->monnaie);
                if ($objp->duration) $opt.= ' - '.$objp->duration;
                $opt.= "</option>\n";
                print $opt;
                $i++;
            }
			if ($conf->use_ajax)
            {
//				if ($num)
//				{
					print '</select>';
//    			}
    		}
    		else
    		{
					print '</select>';
			}    			
            $this->db->free($result);
        }
        else
        {
            dolibarr_print_error($db);
        }
    }
  

    /**
     *    	\brief      Retourne la liste des produits de fournisseurs
     *		\param		socid			Id société (0 pour aucun filtre)
     *    	\param      selected        Produit présélectionné
     *    	\param      htmlname        Nom de la zone select
     *    	\param      filtretype      Pour filtre sur type de produit
     *    	\param      limit           Limite sur le nombre de lignes retournées
     *    	\param      filtre          Pour filtre
     */
    function select_produits_fournisseurs($socid,$selected='',$htmlname='productid',$filtretype='',$filtre='')
    {
        global $langs,$conf;
    
        $sql = "SELECT p.rowid, p.label, p.ref, p.price, pf.price as fprice, pf.quantity, p.duration";
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pf ON p.rowid = pf.fk_product";
		$sql.= " WHERE p.envente = 1";
        if ($socid) $sql.= " AND pf.fk_soc = ".$socid;
        if ($filtretype && $filtretype != '') $sql.=" AND p.fk_product_type=".$filtretype;
        if ($filtre) $sql.="$filtre";
        $sql.= " ORDER BY p.ref DESC";
    	
    	dolibarr_syslog("html.form.class.php::select_produits_fournisseurs sql=$sql");
    	
        $result=$this->db->query($sql);
        if ($result)
        {
            print '<select class="flat" name="'.$htmlname.'">';
            print "<option value=\"0\" selected=\"true\">&nbsp;</option>";
    
            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num)
            {
                $objp = $this->db->fetch_object($result);
                $opt = '<option value="'.$objp->rowid.'">'.$objp->ref.' - ';
                $opt.= dolibarr_trunc($objp->label,24).' - ';
                $opt.= $objp->fprice.$langs->trans("Currency".$conf->monnaie)."/".$objp->quantity.$langs->trans("Units");
                if ($objp->quantity > 1)
                {
                    $opt.=" - ";
                    $opt.= round($objp->fprice/$objp->quantity,4).$langs->trans("Currency".$conf->monnaie)."/".$langs->trans("Unit");
                }
                if ($objp->duration) $opt .= " - ".$objp->duration;
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
     *    \brief      Retourne la liste déroulante des adresses de livraison
     *    \param      selected        Id contact pré-sélectionn
     *    \param      htmlname        Nom champ formulaire
     */
    function select_adresse_livraison($selected='', $socid, $htmlname='adresse_livraison_id')
    {
        // On recherche les utilisateurs
        $sql = "SELECT a.rowid, a.label";
        $sql .= " FROM ".MAIN_DB_PREFIX ."societe_adresse_livraison as a";
        $sql .= " WHERE a.fk_societe = ".$socid;
        $sql .= " ORDER BY a.label ASC";
    
        if ($this->db->query($sql))
        {
            print '<select class="flat" name="'.$htmlname.'">';
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
     *      \brief      Charge dans cache la liste des conditions de paiements possibles
     *      \return     int             Nb lignes chargées, 0 si déjà chargées, <0 si ko
     */
    function load_cache_conditions_paiements()
    {
        global $langs;

        if (sizeof($this->cache_conditions_paiements_code)) return 0;    // Cache déja chargé

        dolibarr_syslog('html.form.class.php::load_cache_conditions_paiements');
        $sql = "SELECT rowid, code, libelle";
        $sql.= " FROM ".MAIN_DB_PREFIX."cond_reglement";
        $sql.= " WHERE active=1";
        $sql.= " ORDER BY sortorder";
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                // Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
                $libelle=($langs->trans("PaymentConditionShort".$obj->code)!=("PaymentConditionShort".$obj->code)?$langs->trans("PaymentConditionShort".$obj->code):($obj->libelle!='-'?$obj->libelle:''));
                $this->cache_conditions_paiements_code[$obj->rowid]=$obj->code;
                $this->cache_conditions_paiements_libelle[$obj->rowid]=$libelle;
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

        if (sizeof($this->cache_types_paiements_code)) return 0;    // Cache déja chargé

        dolibarr_syslog('html.form.class.php::load_cache_types_paiements');
        $sql = "SELECT id, code, libelle, type";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_paiement";
        $sql.= " WHERE active > 0";
        $sql.= " ORDER BY id";
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);

                // Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
                $libelle=($langs->trans($obj->code)!=$obj->code?$langs->trans($obj->code):($obj->libelle!='-'?$obj->libelle:''));
                $this->cache_types_paiements_code[$obj->id]=$obj->code;
                $this->cache_types_paiements_libelle[$obj->id]=$libelle;
                $this->cache_types_paiements_type[$obj->id]=$obj->type;
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
     *      \param      selected        Id du type de paiement présélectionné
     *      \param      htmlname        Nom de la zone select
     *      \param      filtertype      Pour filtre
     *		\param		addempty		Ajoute entrée vide
     */
    function select_conditions_paiements($selected='',$htmlname='condid',$filtertype=-1,$addempty=0)
    {
        global $langs;
        
        $this->load_cache_conditions_paiements();
 
        print '<select class="flat" name="'.$htmlname.'">';
		if ($addempty) print '<option value="0">&nbsp;</option>';
        foreach($this->cache_conditions_paiements_code as $id => $code)
        {
            if ($selected == $id)
            {
                print '<option value="'.$id.'" selected="true">';
            }
            else
            {
                print '<option value="'.$id.'">';
            }
            print $this->cache_conditions_paiements_libelle[$id];
            print '</option>';
        }
        print '</select>';
    }
    
	/**
     *      \brief      Retourne la liste des types de paiements possibles
     *      \param      selected        Id du type de paiement présélectionné
     *      \param      htmlname        Nom de la zone select
     */
    function select_assujetti_tva($selected='',$htmlname='')
    {
        global $langs;
        print '<select class="flat" name="'.$htmlname.'">';
		$options = array(
					'-1'=>'&nbsp;',
					'0'=>$langs->trans("No"),
					'1'=>$langs->trans("Yes")
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
     *      \brief      Retourne la liste des modes de paiements possibles
     *      \param      selected        Id du mode de paiement présélectionné
     *      \param      htmlname        Nom de la zone select
     *      \param      filtertype      Pour filtre
     *      \param      format          0=id+libelle, 1=code+code, 2=code+libelle
     */
    function select_types_paiements($selected='',$htmlname='paiementtype',$filtertype='',$format=0)
    {
        global $langs;

        $filterarray=array();
        if ($filtertype && $filtertype != '-1') $filterarray=split(',',$filtertype);
        
        $this->load_cache_types_paiements();

        print '<select class="flat" name="'.$htmlname.'">';
        foreach($this->cache_types_paiements_code as $id => $code)
        {
            // On passe si on a demandé de filtrer sur des modes de paiments particulièrs
            if (sizeof($filterarray) && ! in_array($this->cache_types_paiements_type[$id],$filterarray)) continue;

            if ($format == 0) print '<option value="'.$id.'"';
            if ($format == 1) print '<option value="'.$code.'"';
            if ($format == 2) print '<option value="'.$code.'"';
            // Si selected est text, on compare avec code, sinon avec id
            if (eregi('[a-z]', $selected) && $selected == $code) print ' selected="true"';
            elseif ($selected == $id) print ' selected="true"';
            print '>';
            if ($format == 0) $value=$this->cache_types_paiements_libelle[$id];
            if ($format == 1) $value=$code;
            if ($format == 2) $value=$this->cache_types_paiements_libelle[$id];
            print $value?$value:'&nbsp;';
            print '</option>';
        }
        print '</select>';
    }


    /**
     *    \brief      Retourne la liste des comptes
     *    \param      selected          Id compte présélectionné
     *    \param      htmlname          Nom de la zone select
     *    \param      statut            Statut des comptes recherchés
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
        $result = $this->db->query($sql);
        if ($result)
        {
            print '<select class="flat" name="'.$htmlname.'">';
            if ($useempty) 
            {
                print '<option value="'.$obj->rowid.'">&nbsp</option>';
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
     *    \brief      Retourne la ou les listes déroulante des catégories en fonction du nombre choisi
     *    \param      selected    nombre de catégorie à créer
     */
    function select_all_categories($selected='',$select_name="")
    {
        global $langs;
        $langs->load("categorie");
		if($select_name=="")
			$select_name="catMere";
        $cat = new Categorie ($this -> db);
		$cat->get_categories_arbo();
		$cate_arbo = $cat->get_arbo_each_cate();
		$output = '<select name="'.$select_name.'">'; //creation des categories meres
		$output.= '<option value="-1" id="choix">&nbsp;</option>\n';
		
		if ($cate_arbo)
		{
			foreach($cate_arbo as $key => $value)
			{
				if($value[1] == $selected)
					$add = "selected='true' ";
				else
					$add = "";
				$output.= '<option '.$add.'value="'.$value[1].'">'.$value[0].'</option>';
			}
		}
							 
		$output.= '</select>';
		return $output; 
	}
        
        

    /**
     *    \brief      Retourne la liste déroulante des civilite actives
     *    \param      selected    civilite pré-sélectionnée
     */
    function select_civilite($selected='')
    {
        global $conf,$langs;
        $langs->load("dict");
    
        $sql = "SELECT rowid, code, civilite, active FROM ".MAIN_DB_PREFIX."c_civilite";
        $sql .= " WHERE active = 1";
    
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
        }
        else
        {
            dolibarr_print_error($this->db);
        }
    }


    /**
     *    \brief      Retourne le nom traduit de la civilité
     *    \param      code        Code de la civilité
     *    \return     string      Nom traduit de la civilité
     */
    function civilite_name($code)
    {
        global $langs;
        $langs->load("dict");
        return $langs->trans("Civility".$code)!="Civility".$code ? $langs->trans("Civility".$code) : $code;           
    }


    /**
     *    \brief      Retourne la liste déroulante des formes juridiques tous pays confondus ou pour un pays donné.
     *    \remarks    Dans le cas d'une liste tous pays confondu, on affiche une rupture sur le pays
     *    \param      selected        Code forme juridique a présélectionn
     *    \param      pays_code       0=liste tous pays confondus, sinon code du pays à afficher
     */
    function select_forme_juridique($selected='',$pays_code=0)
    {
        global $conf,$langs;
        $langs->load("dict");
    
        // On recherche les formes juridiques actives des pays actifs
        $sql  = "SELECT f.rowid, f.code as code , f.libelle as nom, f.active, p.libelle as libelle_pays, p.code as code_pays";
        $sql .= " FROM llx_c_forme_juridique as f, llx_c_pays as p";
        $sql .= " WHERE f.fk_pays=p.rowid";
        $sql .= " AND f.active = 1 AND p.active = 1";
        if ($pays_code) $sql .= " AND p.code = '".$pays_code."'";
        $sql .= " ORDER BY p.code, f.code";
    
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
                        print $obj->code . ' - ' .($langs->trans($obj->code)!=$obj->code?$langs->trans($obj->code):($obj->nom!='-'?$obj->nom:''));
                        print '</option>';
                    }
                    $i++;
                }
            }
            print '</select>';
            print '</div>';
        }
        else
        {
            dolibarr_print_error($this->db);
        }
    }
  

    /**
     *    \brief      Retourne le nom traduit de la forme juridique
     *    \param      code        Code de la forme juridique
     *    \return     string      Nom traduit du pays
     */
    function forme_juridique_name($code)
    {
        global $langs;
    
        if (! $code) return '';
        
        $sql = "SELECT libelle FROM ".MAIN_DB_PREFIX."c_forme_juridique";
        $sql.= " WHERE code='$code';";
    
        if ($this->db->query($sql))
        {
            $num = $this->db->num_rows();
    
            if ($num)
            {
                $obj = $this->db->fetch_object();
                $label=($obj->libelle!='-' ? $obj->libelle : '');
                return $label;
            }
            else
            {
                return $langs->trans("NotDefined");
            }
    
        }
    }


    /**
     *      \brief      Retourne le formulaire de saisie d'un identifiant professionnel (siren, siret, etc...)
     *      \param      idprof          1,2,3,4 (Exemple: 1=siren,2=siret,3=naf,4=rcs/rm)
     *      \param      soc             Objet societe
     *      \param      htmlname        Nom de la zone input
     */
    function id_prof($idprof,$soc,$htmlname,$selected='')
    {
        global $langs;
        $formlength=16;
        if ($idprof==1 && $soc->pays_code == 'FR') $formlength=9;
        if ($idprof==2 && $soc->pays_code == 'FR') $formlength=14;
        if ($idprof==3 && $soc->pays_code == 'FR') $formlength=4;
        if ($idprof==4 && $soc->pays_code == 'FR') $formlength=4;
        print '<input type="text" name="'.$htmlname.'" size="'.($formlength+1).'" maxlength="'.$formlength.'" value="'.$selected.'">';
    }


  /**
   *    \brief      Retourne le nom traduit ou code+nom d'un pays
   *    \param      id          id du pays
   *    \param      withcode    1=affiche code + nom
   *    \return     string      Nom traduit du pays
   */
    function pays_name($id,$withcode=0)
    {
        global $langs;
    
        $sql = "SELECT rowid, code, libelle FROM ".MAIN_DB_PREFIX."c_pays";
        $sql.= " WHERE rowid=$id;";
    
        if ($this->db->query($sql))
        {
            $num = $this->db->num_rows();
    
            if ($num)
            {
                $obj = $this->db->fetch_object();
                $label=$obj->code && $langs->trans("Country".$obj->code)!="Country".$obj->code?$langs->trans("Country".$obj->code):($obj->libelle!='-'?$obj->libelle:'');
                if ($withcode) return $label==$obj->code?"$obj->code":"$obj->code - $label";
                else return $label;
            }
            else
            {
                return $langs->trans("NotDefined");
            }
    
        }
    }
    
    
   /**
    *    \brief      Retourne le nom traduit ou code+nom d'une devise
    *    \param      code_iso       Code iso de la devise
    *    \param      withcode       1=affiche code + nom
    *    \return     string         Nom traduit de la devise
    */
   function currency_name($code_iso,$withcode=0)
    {
        global $langs;

        // Si il existe une traduction, on peut renvoyer de suite le libellé
        if ($langs->trans("Currency".$code_iso)!="Currency".$code_iso)
        {
            return $langs->trans("Currency".$code_iso);
        }
        
        // Si pas de traduction, on consulte libellé par défaut en table
        $sql = "SELECT label FROM ".MAIN_DB_PREFIX."c_currencies";
        $sql.= " WHERE code_iso='$code_iso';";
    
        if ($this->db->query($sql))
        {
            $num = $this->db->num_rows();
    
            if ($num)
            {
                $obj = $this->db->fetch_object();
                $label=($obj->label!='-'?$obj->label:'');
                if ($withcode) return $label==$code_iso?"$code_iso":"$code_iso - $label";
                else return $label;
            }
            else
            {
                return $code_iso;
            }
    
        }
    }


  /**
   *    \brief  Affiche formulaire de demande de confirmation
   *    \param  page        page
   *    \param  title       title
   *    \param  question    question
   *    \param  action      action
   */
	 
  function form_confirm($page, $title, $question, $action)
  {
    global $langs;
    
    print '<form method="post" action="'.$page.'">';
    print '<input type="hidden" name="action" value="'.$action.'">';
    print '<table class="border" width="100%">';
    // Ligne titre
    print '<tr><td class="validtitle" colspan="3">'.$title.'</td></tr>';
    // Ligne message
    print '<tr><td class="valid">'.$question.'</td><td class="valid">';
    $this->selectyesno("confirm","no");
    print "</td>\n";
    print '<td class="valid" align="center"><input class="button" type="submit" value="'.$langs->trans("Confirm").'"></td></tr>';

    print '</table>';
    print "</form>\n";  
  }


    /**
     *    \brief      Affiche formulaire de selection de projet
     *    \param      page        Page
     *    \param      socid       Id societe
     *    \param      selected    Id projet présélectionné
     *    \param      htmlname    Nom du formulaire select
     */
    function form_project($page, $socid, $selected='', $htmlname='projectid')
    {
        global $langs;
        $langs->load("project");
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="classin">';
            print '<table class="noborder" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $this->select_projects($socid,$selected,$htmlname);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected) {
                $projet = New Project($this->db);
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
     *    	\param      selected    	Id condition présélectionnée
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
                print $this->cache_conditions_paiements_libelle[$selected];
            } else {
                print "&nbsp;";
            }
        }
    }
    
	/**
     *    \brief      Affiche formulaire de selection de l'assujétissement à la TVA
     *    \param      page        Page
     *    \param      selected    Id condition présélectionnée
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
     *    \param      selected    Id mode présélectionné
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
                print $this->cache_types_paiements_libelle[$selected];
            } else {
                print "&nbsp;";
            }
        }
    }


    /**
     *    \brief      Affiche formulaire de selection de la remise relative
     *    \param      page        Page
     *    \param      selected    Valeur remise
     *    \param      htmlname    Nom du formulaire select. Si none, non modifiable
     */
    function form_remise_percent($page, $selected='', $htmlname='remise_percent')
    {
        global $langs;
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
			print '<input type="hidden" name="action" value="setremisepercent">';
            print '<table class="noborder" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
			print '<input type="text" name="'.$htmlname.'" size="1" value="'.$selected.'">%';
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                print $selected;
            } else {
                print "0%";
            }
        }
    }
    
        
    /**
     *    \brief      Affiche formulaire de selection de la remise fixe
     *    \param      page        Page
     *    \param      selected    Valeur à appliquer
     *    \param      htmlname    Nom du formulaire select. Si none, non modifiable
     */
    function form_remise_dispo($page, $selected='', $htmlname='remise_id',$socid)
    {
        global $langs;
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
			print '<input type="hidden" name="action" value="setabsolutediscount">';
            print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
			print $langs->trans("AvailableGlobalDiscounts").': ';
			print $this->select_remises('',$htmlname,'fk_facture IS NULL',$socid);
            print '</td>';
            print '<td align="left"> <input type="submit" class="button" value="'.$langs->trans("UseDiscount").'"></td>';
            print '</tr></table></form>';
        }
        else
        {
            if ($selected)
            {
                print $selected;
            } else {
                print "0";
            }
        }
    }
    
    
    /**
     *    \brief      Affiche formulaire de selection des contacts
     *    \param      page        Page
     *    \param      selected    Id contact présélectionné
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
     *    	\param      selected    	Id condition présélectionnée
     *    	\param      htmlname    	Nom du formulaire select
     *		  \param		  origin        origine de l'appel pour pouvoir créer un retour
     *      \param      originid      id de l'origine
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
            $numaddress = $this->select_adresse_livraison($selected, $socid, $htmlname);
            print '</td>';
            print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'">';
            if ($numaddress==0)
            {
            	$langs->load("companies");
              print ' &nbsp; <a href=/comm/adresse_livraison.php?socid='.$socid.'&action=create&origin='.$origin.'&originid='.$originid.'>'.$langs->trans("AddAddress").'</a>';
            }
            print '</td></tr></table></form>';
        }
        else
        {
            if ($selected)
            {
            	require_once(DOL_DOCUMENT_ROOT ."/comm/adresse_livraison.class.php");
            	$livraison=new Livraison($this->db);
				      $livraison->fetch_adresse($selected);
				      print '<a href=/comm/adresse_livraison.php?socid='.$livraison->socid.'&idl='.$livraison->idl.'&action=edit&origin='.$origin.'&originid='.$originid.'>'.$livraison->label.'</a>';
            }
            else
            {
              print "&nbsp;";
            }
        }
    }    
    
    /**
     *    \brief     Retourne la liste des devises, dans la langue de l'utilisateur
     *    \param     selected    code devise pré-sélectionnée
     *    \param     htmlname    nom de la liste deroulante
     *    \todo      trier liste sur noms après traduction plutot que avant
     */
    function select_currency($selected='',$htmlname='currency_id')
    {
        global $conf,$langs;
        $langs->load("dict");
    
        if ($selected=='euro' || $selected=='euros') $selected='EUR';   // Pour compatibilité
    
        $sql = "SELECT code_iso, label, active FROM ".MAIN_DB_PREFIX."c_currencies";
        $sql .= " WHERE active = 1";
        $sql .= " ORDER BY code_iso ASC;";
    
        if ($this->db->query($sql))
        {
            print '<select class="flat" name="'.$htmlname.'">';
            $num = $this->db->num_rows();
            $i = 0;
            if ($num)
            {
                $foundselected=false;
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object();
                    if ($selected && $selected == $obj->code_iso)
                    {
                        $foundselected=true;
                        print '<option value="'.$obj->code_iso.'" selected="true">';
                    }
                    else
                    {
                        print '<option value="'.$obj->code_iso.'">';
                    }
                    // Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
                    if ($obj->code_iso) { print $obj->code_iso . ' - '; }
                    print ($obj->code_iso && $langs->trans("Currency".$obj->code_iso)!="Currency".$obj->code_iso?$langs->trans("Currency".$obj->code_iso):($obj->label!='-'?$obj->label:''));
                    print '</option>';
                    $i++;
                }
            }
            print '</select>';
            return 0;
        }
        else {
            dolibarr_print_error($this->db);
            return 1;
        }
    }


    /**
     *      \brief      Selection du taux de tva appliqué par vendeur
     *      \param      name                Nom champ html
     *      \param      defaulttx           Forçage du taux tva présélectionné. Mettre '' pour appliquer règle par défaut.
     *      \param      societe_vendeuse    Objet société vendeuse
     *      \param      societe_acheteuse   Objet société acheteuse
     *      \param      taux_produit        Taux par defaut du produit vendu
     *      \remarks    Si vendeur non assujeti à TVA, TVA par défaut=0. Fin de règle.
     *                  Si le (pays vendeur = pays acheteur) alors la TVA par défaut=TVA du produit vendu. Fin de règle.
     *                  Si vendeur et acheteur dans Communauté européenne et bien vendu = moyen de transports neuf (auto, bateau, avion), TVA par défaut=0 (La TVA doit être payé par l'acheteur au centre d'impots de son pays et non au vendeur). Fin de règle.
     *                  Si vendeur et acheteur dans Communauté européenne et bien vendu autre que transport neuf alors la TVA par défaut=TVA du produit vendu. Fin de règle.
     *                  Sinon la TVA proposée par défaut=0. Fin de règle.
     */
    function select_tva($name='tauxtva', $defaulttx='', $societe_vendeuse='', $societe_acheteuse='', $taux_produit='')
    {
        global $langs,$conf,$mysoc;

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

		if (is_object($societe_vendeuse->pays_code))
		{
			$code_pays=$societe_vendeuse->pays_code;
		}
		else
		{
			$code_pays=$mysoc->pays_code;
		}
		
		// Recherche liste des codes TVA du pays vendeur
        $sql  = "SELECT t.taux,t.recuperableonly";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_tva AS t";
        $sql .= " WHERE t.fk_pays = '".$code_pays."'";
        $sql .= " AND t.active = 1";
        $sql .= " ORDER BY t.taux ASC, t.recuperableonly ASC";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            for ($i = 0; $i < $num; $i++)
            {
                $obj = $this->db->fetch_object($resql);
                $txtva[ $i ] = $obj->taux;
                $libtva[ $i ] = $obj->taux.'%'.($obj->recuperableonly ? ' *':'');
            }
        }
        else
        {
            print '<font class="error">'.$langs->trans("ErrorNoVATRateDefinedForSellerCountry").'</div>';
		}        	

		// Définition du taux à présélectionner
		if ($defaulttx == '') $defaulttx=get_default_tva($societe_vendeuse,$societe_acheteuse,$taux_produit);
        // Si taux par defaut n'a pu etre trouvé, on prend dernier.
        // Comme ils sont triés par ordre croissant, dernier = plus élevé = taux courant
        if ($defaulttx == '') $defaulttx = $txtva[sizeof($txtva)-1];

        $nbdetaux = sizeof($txtva);

        print '<select class="flat" name="'.$name.'">';
        if ($default) print '<option value="0">'.$langs->trans("Default").'</option>';

        for ($i = 0 ; $i < $nbdetaux ; $i++)
        {
            print '<option value="'.$txtva[$i].'"';
            if ($txtva[$i] == $defaulttx)
            {
                print ' selected="true"';
            }
            print '>'.$libtva[$i].'</option>';
        }
        print '</select>';
    }


    /**
     *		\brief  Affiche zone de selection de date
     *      		Liste deroulante pour les jours, mois, annee et eventuellement heurs et minutes
     *            	Les champs sont présélectionnées avec:
     *            	- La date set_time (timestamps ou date au format YYYY-MM-DD ou YYYY-MM-DD HH:MM)
     *            	- La date du jour si set_time vaut ''
     *            	- Aucune date (champs vides) si set_time vaut -1 (dans ce cas empty doit valoir 0)
	 *		\param	set_time 		Date de pré-sélection
	 *		\param	prefix			Prefix pour nom champ
	 *		\param	h				Heure
	 *		\param	m				Minutes
	 *		\param	empty			0=Champ obligatoire, 1=Permet une saisie vide
	 *		\param	form_name 		Nom du formulaire de provenance. Utilisé pour les dates en popup style andre.
     */
	function select_date($set_time='', $prefix='re', $h = 0, $m = 0, $empty=0, $form_name="")
    {
        global $conf,$langs;
    	
    	if($prefix=='') $prefix='re';
		if($h == '') $h=0;
		if($m == '') $m=0;
		if($empty == '') $empty=0;
	
        if (! $set_time && $empty == 0) $set_time = time();
    
        // Analyse de la date de préselection
        if (eregi('^([0-9]+)\-([0-9]+)\-([0-9]+)\s?([0-9]+)?:?([0-9]+)?',$set_time,$reg))
        {
            // Date au format 'YYYY-MM-DD' ou 'YYYY-MM-DD HH:MM:SS'
            $syear = $reg[1];
            $smonth = $reg[2];
            $sday = $reg[3];
            $shour = $reg[4];
            $smin = $reg[5];
        }
        elseif ($set_time > 0)
        {
            // Date est un timestamps
            $syear = date("Y", $set_time);
            $smonth = date("n", $set_time);
            $sday = date("d", $set_time);
            $shour = date("H", $set_time);
            $smin = date("i", $set_time);
        }
        else
        {
            // Date est vide ou vaut -1
            $syear = '';
            $smonth = '';
            $sday = '';
            $shour = '';
            $smin = '';
        }

        /*
         * Affiche date en popup
         */
		if ($conf->use_javascript && $conf->use_popup_calendar && $h==0 && $m==0)
        {
            //print "e".$set_time." t ".$conf->format_date_short;
            if ($set_time > 0)
            {
	            $formated_date=dolibarr_print_date($set_time,$conf->format_date_short);
			}

			// Calendrier popup version eldy
			if ("$conf->use_popup_calendar" == "eldy")	// Laisser conf->use_popup_calendar entre quote
			{
	            // Zone de saisie manuelle de la date
	            print '<input id="'.$prefix.'" name="'.$prefix.'" type="text" size="11" maxlength="11" value="'.$formated_date.'"';
	            print ' onChange="dpChangeDay(\''.$prefix.'\',\''.$conf->format_date_short_java.'\')"';
	            print '> ';
	            
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
					print '<script language="javascript" type="text/javascript">';
					print 'selectedLanguage = "'.substr($langs->defaultlang,0,2).'"';
					print '</script>';
				}
				print '<script language="javascript" type="text/javascript" src="'.DOL_URL_ROOT.'/lib/lib_calendar.js"></script>';
				print '<input id="'.$prefix.'" type="text" name="'.$prefix.'" size="10" value="'.$formated_date.'"> ';
				print '<input type="hidden" id="'.$prefix.'day"   name="'.$prefix.'day"   value="'.$sday.'">'."\n";
	            print '<input type="hidden" id="'.$prefix.'month" name="'.$prefix.'month" value="'.$smonth.'">'."\n";
	            print '<input type="hidden" id="'.$prefix.'year"  name="'.$prefix.'year"  value="'.$syear.'">'."\n";
				if($form_name =="")
					print '<A HREF="javascript:showCalendar(document.forms[3].'.$prefix.')">'.img_cal().'</a>';
				else
					print '<A HREF="javascript:showCalendar(document.forms[\''.$form_name.'\'].'.$prefix.')">'.img_cal().'</a>';
			}
        }
        
        /*
         * Affiche date en select
         */
        if (! $conf->use_javascript || ! $conf->use_popup_calendar)
        {

            // Jour
            print '<select class="flat" name="'.$prefix.'day">';
        
            if ($empty || $set_time == -1)
            {
                $sday = 0;
                $smonth = 0;
                $syear = 0;
                $shour = 0;
                $smin = 0;
        
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
                print '<input class="flat" type="text" size="3" maxlength="4" name="'.$prefix.'year">';
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

        /*
         * Affiche heure en select
         */
        if ($h)
        {
            print '<select class="flat" name="'.$prefix.'hour">';
    
            for ($hour = 0; $hour < 24 ; $hour++)
            {
                if (strlen($hour) < 2)
                {
                    $hour = "0" . $hour;
                }
                if ($hour == $shour)
                {
                    print "<option value=\"$hour\" selected=\"true\">$hour";
                }
                else
                {
                    print "<option value=\"$hour\">$hour";
                }
                print "</option>";
            }
            print "</select>H\n";
    
            if ($m)
            {
                print '<select class="flat" name="'.$prefix.'min">';
    
                for ($min = 0; $min < 60 ; $min++)
                {
                    if (strlen($min) < 2)
                    {
                        $min = "0" . $min;
                    }
                    if ($min == $smin)
                    {
                        print "<option value=\"$min\" selected=\"true\">$min";
                    }
                    else
                    {
                        print "<option value=\"$min\">$min";
                    }
                    print "</option>";
                }
                print "</select>M\n";
            }
    
        }

    }
	
    /**
     *      \brief      Affiche liste déroulante depuis requete SQL
     *      \param      name        Nom de la zone select
     *      \param      sql         Requete sql
     *      \param      id          Id présélectionné
     */
    function select($name, $sql, $id='')
    {
        $resql = $this->db->query($sql);
        if ($resql)
        {
            print '<select class="flat" name="'.$name.'">';
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $row = $this->db->fetch_row($resql);
                print '<option value="'.$row[0].'"';
                if ($id != '' && $id == $row[0])
                {
                    print ' selected="true"';
                }
                print '>'.$row[1].'</option>';
                $i++;
            }
            print "</select>\n";
        }
        else
        {
            dolibarr_print_error($this->db);
        }
    }
    
    /**
        \brief  Affiche un select à partir d'un tableau
        \param	htmlname        Nom de la zone select
        \param	array           Tableau de key+valeur
        \param	id              Key pré-sélectionnée
        \param	show_empty      1 si il faut ajouter une valeur " " dans la liste, 0 sinon
        \param	key_in_label    1 pour afficher la key dans la valeur "[key] value"
        \param	value_as_key    1 pour utiliser la valeur comme clé
    */
    function select_array($htmlname, $array, $id='', $show_empty=0, $key_in_label=0, $value_as_key=0, $use_java=0, $fonction='')
    {
        if ($use_java == 1 && $fonction != '')
        {
        	print '<select class="flat" name="'.$htmlname.'" '.$fonction.'>';
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
            // Si il faut présélectionner une valeur
            if ($id && $id == $key)
            {
                print ' selected="true"';
            }
    
            if ($key_in_label)
            {
                print '>'.$key.' - '.$value."</option>\n";
            }
            else
            {
                if ($value == '' || $value == '-') { $value='&nbsp;'; }
                print ">".$value."</option>\n";
            }
        }
    
        print "</select>\n";
    }


    /**
     *    \brief  Renvoie la chaîne de caractère décrivant l'erreur
     */
    function error()
    {
        return $this->errorstr;
    }


  /**
   *    \brief      Selection de oui/non en chaine (renvoie yes/no)
   *    \param      name            Nom du select
   *    \param      value           Valeur présélectionnée
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

    print '<select class="flat" name="'.$name.'">'."\n";
    if (("$value" == 'yes') || ($value == 1))
    {
        print '<option value="'.$yes.'" selected="true">'.$langs->trans("yes").'</option>'."\n";
        print '<option value="'.$no.'">'.$langs->trans("no").'</option>'."\n";
    }
    else
    {
        print '<option value="'.$yes.'">'.$langs->trans("yes").'</option>'."\n";
        print '<option value="'.$no.'" selected="true">'.$langs->trans("no").'</option>'."\n";
    }
    print '</select>'."\n";
  }
	
  /**
   *    \brief      Selection de oui/non en chiffre (renvoie 1/0)
   *    \param      name            Nom du select
   *    \param      value           Valeur présélectionnée
   */
  function selectyesnonum($name,$value='')
  {
    $this->selectyesno($name,$value,1);
  }
	
  /**
   *    \brief  Checkbox
   *
   */
  function checkbox($name,$checked=0,$value=1)
  {
    if ($checked==1){
      print "<input type=\"checkbox\" name=\"$name\" value=\"$value\" checked />\n";
    }else{
      print "<input type=\"checkbox\" name=\"$name\" value=\"$value\" />\n";
    }
  }


  /**
   *    \brief      Affiche la cartouche générique d'un rapport
   *    \param      nom             Valeur pour nom du rapport
   *    \param      variante        Lien optionnel de variante du rapport
   *    \param      period          Periode du reporting
   *    \param      periodlink      Lien pour changer de période
   *    \param      description     Description
   *    \param      builddate       Date génération 
   *    \param      exportlink      Lien pour export
   */
  function report_header($nom,$variante='',$period,$periodlink,$description,$builddate,$exportlink)
  {
    global $langs;
    
    print "\n\n<!-- debut cartouche rapport -->\n";

    $h=0;
   	$head[$h][0] = $_SERVER["PHP_SELF"];
   	$head[$h][1] = $langs->trans("Report");
    dolibarr_fiche_head($head, $hselected, $societe->nom);
    
    print '<table width="100%" class="border">';

    // Ligne de titre
    print '<tr>';
    print '<td valign="top" width="110px">'.$langs->trans("ReportName").'</td>';
    if (! $variante) print '<td colspan="3">';
    else print '<td>';
    print $nom;
    if ($variante) print '</td><td colspan="2">'.$variante;
    print '</td>';
    print '</tr>';
    
    // Ligne de la periode d'analyse du rapport
    print '<tr>';
    print '<td>'.$langs->trans("ReportPeriod").'</td>';
    if (! $periodlink) print '<td colspan="3">';
    else print '<td>';
    print $period;
    if ($periodlink) print '</td><td colspan="2">'.$periodlink;
    print '</td>';
    print '</tr>';

    // Ligne de description
    print '<tr>';
    print '<td valign="top">'.$langs->trans("ReportDescription").'</td>';
    print '<td colspan="3">'.$description.'</td>';
    print '</tr>';

    // Ligne d'export
    print '<tr>';
    print '<td>'.$langs->trans("GeneratedOn").'</td>';
    if (! $exportlink) print '<td colspan="3">';
    else print '<td>';
    print dolibarr_print_date($builddate);
    if ($exportlink) print '</td><td>'.$langs->trans("Export").'</td><td>'.$exportlink;
    print '</td></tr>';
    
    print '</table>';
    print '</div>';
    print "\n<!-- fin cartouche rapport -->\n\n";
  }

    /**
     *      \brief      Affiche la cartouche de la liste des documents d'une propale, facture...
     *      \param      modulepart          propal=propal, facture=facture, ...
     *      \param      filename            Nom fichier sans extension
     *      \param      filedir             Repertoire à scanner
     *      \param      urlsource           Url page origine
     *      \param      genallowed          Génération autorisée (1/0 ou array des formats)
     *      \param      delallowed          Suppression autorisée (1/0)
     *      \param      modelselected       Modele à présélectionner par défaut
     *      \param      modelliste			Tableau des modeles possibles
     *      \param      forcenomultilang	N'affiche pas option langue meme si MAIN_MULTILANGS défini
     *      \remarks    Le fichier de facture détaillée est de la forme
     *                  REFFACTURE-XXXXXX-detail.pdf ou XXXXX est une forme diverse
     *		\return		int					<0 si ko, nbre de fichiers affichés si ok
     */
    function show_documents($modulepart,$filename,$filedir,$urlsource,$genallowed,$delallowed=0,$modelselected='',$modelliste=array(),$forcenomultilang=0)
    {
        // filedir = conf->...dir_ouput."/".get_exdir(id)
        
        global $langs,$bc,$conf;
        $var=true;
 
        $filename = sanitize_string($filename);
        $headershown=0;
        $i=0;

        // Affiche en-tete tableau
        if ($genallowed)
        {
            $modellist=array();
            if ($modulepart == 'propal')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/includes/modules/propale/modules_propale.php');
                    $model=new ModelePDFPropales();
                    $modellist=$model->liste_modeles($this->db);
                }
            }
			else if ($modulepart == 'commande')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/includes/modules/commande/modules_commande.php');
                    $model=new ModelePDFCommandes();
                    $modellist=$model->liste_modeles($this->db);
                }
            }
            elseif ($modulepart == 'expedition')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/expedition/mods/pdf/ModelePdfExpedition.class.php');
                    $model=new ModelePDFExpedition();
                    $modellist=$model->liste_modeles($this->db);
                }
            }
            elseif ($modulepart == 'livraison')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/livraison/mods/modules_livraison.php');
                    $model=new ModelePDFDeliveryOrder();
                    $modellist=$model->liste_modeles($this->db);
                }
            }
            else if ($modulepart == 'ficheinter')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/includes/modules/fichinter/modules_fichinter.php');
                    $model=new ModelePDFFicheinter();
                    $modellist=$model->liste_modeles($this->db);
                }
            }
            elseif ($modulepart == 'facture')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/includes/modules/facture/modules_facture.php');
                    $model=new ModelePDFFactures();
                    $modellist=$model->liste_modeles($this->db);
                }
            }
            elseif ($modulepart == 'export')
            {
                if (is_array($genallowed)) $modellist=$genallowed;
                else
                {
                    include_once(DOL_DOCUMENT_ROOT.'/includes/modules/export/modules_export.php');
                    $model=new ModeleExports();
                    $modellist=$model->liste_modeles($this->db);
                }
            }
            else if ($modulepart == 'commande_fournisseur')
  	        {
  	            if (is_array($genallowed)) $modellist=$genallowed;
  	            else
  	            {
  	                include_once(DOL_DOCUMENT_ROOT.'/fourn/commande/modules/modules_commandefournisseur.php');
  	                $model=new ModelePDFCommandesSuppliers();
  	                $modellist=$model->liste_modeles($this->db);
  	            }
  	        }
  	        else if ($modulepart == 'facture_fournisseur')
  	        {
  	            if (is_array($genallowed)) $modellist=$genallowed;
  	            else
  	            {
  	                include_once(DOL_DOCUMENT_ROOT.'/fourn/facture/modules/modules_facturefournisseur.php');
  	                $model=new ModelePDFFacturesSuppliers();
  	                $modellist=$model->liste_modeles($this->db);
  	            }
  	        }
            else
            {
                dolibarr_print_error($this->db,'Bad value for modulepart');
                return -1;
            }

            $headershown=1;

            print '<form action="'.$urlsource.'" method="post">';
            print '<input type="hidden" name="action" value="builddoc">';

            print_titre($langs->trans("Documents"));
            print '<table class="border" width="100%">';

            print '<tr '.$bc[$var].'>';
            print '<td>'.$langs->trans('Model').'</td>';
            print '<td align="center">';
            $this->select_array('model',$modellist,$modelselected,0,0,1);
            $texte=$langs->trans('Generate');
            print '</td>';
            print '<td align="center">';
            if($conf->global->MAIN_MULTILANGS && ! $forcenomultilang)
            {
              $this->select_lang($langs->getDefaultLang());
            }
            else
            {
            	print '&nbsp;';
            }
            print '</td>';
            print '<td align="center" colspan="'.($delallowed?'2':'1').'">';
            print '<input class="button" type="submit" value="'.$texte.'">';
            print '</td></tr>';
        }

        // Affiche lignes
        if (is_dir($filedir))
        {
            $handle=opendir($filedir);
            while (($file = readdir($handle))!==false)
            {
                // Si fichier non lisible ou mauvaise extension, on passe au suivant
                if (! is_readable($filedir."/".$file) ||
                    eregi('\.meta$',$file) ||
                    eregi('\.$',$file)
                    ) continue;

                if (! $headershown)
                {
                    // Affiche en-tete tableau
                    $headershown=1;

                print_titre($langs->trans("Documents"));
                print '<table class="border" width="100%">';
            }

	        // Défini chemin relatif par rapport au module pour lien download
	        $relativepath=$filename."/".$file;
            if ($modulepart == 'don')        { $relativepath = get_exdir($filename).$file; }
            if ($modulepart == 'export')     { $relativepath = $file; }

            // Défini le type MIME du document
            if (eregi('\.([^\.]+)$',$file,$reg)) $extension=$reg[1];
            $mimetype=strtoupper($extension);
            if ($extension == 'pdf') $mimetype='PDF';
            if ($extension == 'html') $mimetype='HTML';
            if (eregi('\-detail\.pdf',$file)) $mimetype='PDF Détaillé';

            print "<tr $bc[$var]>";

            // Affiche colonne type MIME
            print '<td nowrap>'.$mimetype.'</td>';
            // Affiche nom fichier avec lien download
	        print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart='.$modulepart.'&file='.urlencode($relativepath).'">'.$file.'</a>';
			print '</td>';
            // Affiche taille fichier
            print '<td align="right">'.filesize($filedir."/".$file). ' bytes</td>';
            // Affiche date fichier
            print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($filedir."/".$file)).'</td>';

			if ($delallowed)
			{
            	print '<td><a href="'.DOL_URL_ROOT.'/document.php?action=remove_file&modulepart='.$modulepart.'&file='.urlencode($relativepath).'&urlsource='.urlencode($urlsource).'">'.img_delete().'</a></td>';
			}

            print '</tr>';

            $i++;
        }
    }
    
    if ($headershown)
    {
        // Affiche pied du tableau
        print "</table>\n";
        if ($genallowed)
        {
            print '</form>';
        }
    }

	return $i;
}

}

?>
