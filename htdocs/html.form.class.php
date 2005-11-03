<?PHP
/* Copyright (c) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
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
    
    $result=$this->db->query($sql);
    if ($result)
    {
        print '<select class="flat" name="'.$htmlname.'">';
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
     *    \brief      Retourne la liste déroulante des contacts d'une société donnée
     *    \param      socid           Id de la société
     *    \param      selected        Id contact pré-sélectionn
     *    \param      htmlname        Nom champ formulaire
     */
    function select_contacts($socid,$selected='',$htmlname='contactid')
    {
        // On recherche les societes
        $sql = "SELECT s.idp, s.name, s.firstname FROM ";
        $sql .= MAIN_DB_PREFIX ."socpeople as s";
        $sql .= " WHERE fk_soc=".$socid;
        $sql .= " ORDER BY s.name ASC";
    
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
    
                    if ($selected && $selected == $obj->idp)
                    {
                        print '<option value="'.$obj->idp.'" selected="true">'.$obj->name.' '.$obj->firstname.'</option>';
                    }
                    else
                    {
                        print '<option value="'.$obj->idp.'">'.$obj->name.' '.$obj->firstname.'</option>';
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
     *    \brief      Retourne la liste déroulante des utilisateurs
     *    \param      selected        Id contact pré-sélectionn
     *    \param      htmlname        Nom champ formulaire
     */
    function select_users($selected='',$htmlname='userid')
    {
        // On recherche les societes
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
    *    \brief      Retourne la liste des produits
    *    \param      selected        Produit présélectionné
    *    \param      htmlname        Nom de la zone select
    *    \param      filtretype      Pour filtre sur type de produit
    *    \param      limit           Limite sur le nombre de lignes retournées
    */
    function select_produits($selected='',$htmlname='productid',$filtretype='',$limit=20)
    {
        global $langs,$conf;
    
        $sql = "SELECT p.rowid, p.label, p.ref, p.price, p.duration";
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p ";
        $sql.= " WHERE p.envente = 1";
        if ($filtretype && $filtretype != '') $sql.=" AND p.fk_product_type=".$filtretype;
        $sql.= " ORDER BY p.nbvente DESC";
        if ($limit) $sql.= " LIMIT $limit";
    
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
                $opt = "<option value=\"$objp->rowid\">[$objp->ref] $objp->label - $objp->price ".$langs->trans("Currency".$conf->monnaie);
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
    *    \brief      Retourne la liste des produits fournisseurs
    *    \param      selected        Produit présélectionné
    *    \param      htmlname        Nom de la zone select
    *    \param      filtretype      Pour filtre sur type de produit
    *    \param      limit           Limite sur le nombre de lignes retournées
    *    \param      filtre          Pour filtre
    */
    function select_produits_fournisseurs($socid,$selected='',$htmlname='productid',$filtretype='',$filtre='')
    {
        global $langs,$conf;
    
        $sql = "SELECT p.rowid, p.label, p.ref, p.price, pf.quantity, p.duration";
        $sql.= " FROM ".MAIN_DB_PREFIX."product as p ";
        $sql .= " , ".MAIN_DB_PREFIX."product_fournisseur_price as pf ";
        $sql.= " WHERE p.rowid = pf.fk_product AND pf.fk_soc = ".$socid;
        if ($filtretype && $filtretype != '') $sql.=" AND p.fk_product_type=".$filtretype;
        if ($filtre) $sql.="$filtre";
        $sql.= " ORDER BY p.ref DESC";
    
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
                $opt = "<option value=\"$objp->rowid\">[$objp->ref] $objp->label - ";
                $opt.= $objp->price." ".$langs->trans("Currency".$conf->monnaie)." / ".$objp->quantity." ".$langs->trans("Units");
                if ($objp->quantity > 1)
                {
                    $opt.=" - ";
                    $opt.= round($objp->price/$objp->quantity,4)." ".$langs->trans("Currency".$conf->monnaie)." / ".$langs->trans("Unit");
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
     *      \brief      Charge dans cache la liste des conditions de paiements possibles
     *      \return     int             Nb lignes chargées, 0 si déjà chargées, <0 si ko
     */
    function load_cache_conditions_paiements()
    {
        global $langs;

        if (sizeof($this->cache_conditions_paiements_code)) return 0;    // Cache déja chargé

        dolibarr_syslog('html.form.class.php::load_cache_conditions_paiements');
        $sql = "SELECT rowid, libelle";
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
                $libelle=($langs->trans($obj->code)!=$obj->code?$langs->trans($obj->code):($obj->libelle!='-'?$obj->libelle:''));
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
     */
    function select_conditions_paiements($selected='',$htmlname='condid',$filtertype=-1)
    {
        global $langs;
        
        $this->load_cache_conditions_paiements();
 
        print '<select class="flat" name="'.$htmlname.'">';
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
            if ($format == 0) print $this->cache_types_paiements_libelle[$id];
            if ($format == 1) print $code;
            if ($format == 2) print $this->cache_types_paiements_libelle[$id];
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
    print '<td class="valid" align="center"><input class="button" type="submit" value="'.$langs->trans("Confirm").'"</td></tr>';

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
     *    \brief      Affiche formulaire de selection de conditions de paiement
     *    \param      page        Page
     *    \param      selected    Id condition présélectionnée
     *    \param      htmlname    Nom du formulaire select
     */
    function form_conditions_reglement($page, $selected='', $htmlname='cond_reglement_id')
    {
        global $langs;
        if ($htmlname != "none")
        {
            print '<form method="post" action="'.$page.'">';
            print '<input type="hidden" name="action" value="setconditions">';
            print '<table class="noborder" cellpadding="0" cellspacing="0">';
            print '<tr><td>';
            $this->select_conditions_paiements($selected,$htmlname);
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
     *      \brief      Selection du taux de tva
     *      \param      name                Nom champ html
     *      \param      defaulttx           Taux tva présélectionné (deprecated)
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
        global $langs,$conf;

        // \todo Si pays vendeur non défini
        if (1 == 2)
        {
            // \todo si pays vendeur = soi-même
            if (1 == 1)
            {
                print '<font class="error">'.$langs->trans("ErrorYourCountryIsNotDefined").'</div>';
            }
            else
            {
                print '<font class="error">'.$langs->trans("ErrorSupplierCountryIsNotDefined").'</div>';
            }
            return;
        }

        // \todo Ecraser defaulttx par valeur en fonction de la règle de gestion TVA
        //$defaulttx=get_default_tva($societe_vendeuse,$societe_acheteuse,$taux_produit);

        // \todo Initialiser code_pays avec code_pays société vendeuse
        $code_pays=$conf->global->MAIN_INFO_SOCIETE_PAYS;
        $sql  = "SELECT t.taux,t.recuperableonly";
        $sql .= " FROM ".MAIN_DB_PREFIX."c_tva AS t";
        $sql .= " WHERE t.fk_pays = '".$code_pays."'";
        $sql .= " AND t.active = 1";
        $sql .= " ORDER BY t.taux ASC, t.recuperableonly ASC";

        if ($this->db->query($sql))
        {
            $num = $this->db->num_rows();
            for ($i = 0; $i < $num; $i++)
            {
                $obj = $this->db->fetch_object();
                $txtva[ $i ] = $obj->taux;
                $libtva[ $i ] = $obj->taux.'%'.($obj->recuperableonly ? ' *':'');
            }
        }

        // Si taux par defaut n'a pu etre trouvé, on prend dernier.
        // Comme ils sont triés par ordre croissant, dernier = plus élevé = taux courant
        if ($defaulttx == '') $defaulttx = $txtva[sizeof($txtva)-1];

        $taille = sizeof($txtva);

        print '<select class="flat" name="'.$name.'">';
        if ($default) print '<option value="0">'.$langs->trans("Default").'</option>';

        for ($i = 0 ; $i < $taille ; $i++)
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
     *    \brief  Affiche zone de selection de date
     *            Liste deroulante pour les jours, mois, annee et eventuellement heurs et minutes
     *            Les champs sont présélectionnées avec:
     *            - La date set_time (timestamps ou date au format YYYY-MM-DD ou YYYY-MM-DD HH:MM)
     *            - La date du jour si set_time vaut ''
     *            - Aucune date (champs vides) si set_time vaut -1
     */
    function select_date($set_time='', $prefix='re', $h = 0, $m = 0, $empty=0)
    {
        global $conf,$langs;
    
        if (! $set_time && $empty == 0) $set_time = time();
    
        $strmonth[1]  = $langs->trans("January");
        $strmonth[2]  = $langs->trans("February");
        $strmonth[3]  = $langs->trans("March");
        $strmonth[4]  = $langs->trans("April");
        $strmonth[5]  = $langs->trans("May");
        $strmonth[6]  = $langs->trans("June");
        $strmonth[7]  = $langs->trans("July");
        $strmonth[8]  = $langs->trans("August");
        $strmonth[9]  = $langs->trans("September");
        $strmonth[10] = $langs->trans("October");
        $strmonth[11] = $langs->trans("November");
        $strmonth[12] = $langs->trans("December");
    
        // Analyse de la date de préselection
        if (eregi('^([0-9]+)\-([0-9]+)\-([0-9]+)\s?([0-9]+)?:?([0-9]+)?',$set_time,$reg)) {
            // Date au format 'YYYY-MM-DD' ou 'YYYY-MM-DD HH:MM:SS'
            $syear = $reg[1];
            $smonth = $reg[2];
            $sday = $reg[3];
            $shour = $reg[4];
            $smin = $reg[5];
        }
        elseif ($set_time) {
            // Date est un timestamps
            $syear = date("Y", $set_time);
            $smonth = date("n", $set_time);
            $sday = date("d", $set_time);
            $shour = date("H", $set_time);
            $smin = date("i", $set_time);
        }
        else {
            // Date est vide
            $syear = '';
            $smonth = '';
            $sday = '';
            $shour = '';
            $smin = '';
        }

        $conf->use_popup_date=0;    // Mettre 1 pour avoir date en popup (experimental)

        /*
         * Affiche date en popup
         */
        if ($conf->use_javascript && $conf->use_popup_date)
        {
            $timearray=getDate($set_time);
            $formated_date=dolibarr_print_date($set_time,$conf->format_date_short);
            print '<input id="'.$prefix.'" name="'.$prefix.'" type="text" size="11" maxlength="11" value="'.$formated_date.'"> <button id="'.$prefix.'Button" type="button" class="dpInvisibleButtons" onClick="showDP(\''.DOL_URL_ROOT.'/theme/'.$conf->theme.'/\',\''.$prefix.'\',\''.$conf->format_date_short.'\');">'.img_object($langs->trans("SelectDate"),'calendar').'</button>';
            print '<input type="hidden" name="'.$prefix.'day" value="'.$timearray['mday'].'">'."\n";
            print '<input type="hidden" name="'.$prefix.'month" value="'.$timearray['mon'].'">'."\n";
            print '<input type="hidden" name="'.$prefix.'year" value="'.$timearray['year'].'">'."\n";
        }

        /*
         * Affiche date en select
         */
        if (! $conf->use_javascript || ! $conf->use_popup_date)
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
                if ($month == $smonth)
                {
                    print "<option value=\"$month\" selected=\"true\">" . $strmonth[$month];
                }
                else
                {
                    print "<option value=\"$month\">" . $strmonth[$month];
                }
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
        
                for ($year = $syear - 3; $year < $syear + 5 ; $year++)
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
    \param	name            nom de la zone select
    \param	array           tableau de key+valeur
    \param	id              key présélectionnée
    \param	empty           1 si il faut un valeur " " dans la liste, 0 sinon
    \param	key_libelle     1 pour afficher la key dans la valeur "[key] value"
  */
		
  function select_array($name, $array, $id='', $empty=0, $key_libelle=0)
  {
    print '<select class="flat" name="'.$name.'">';
        
    $i = 0;
        
    if (strlen($id)) {
      if ($empty == 1)
	{
	  $array[0] = "&nbsp;";
	}
      reset($array);
    }
        
    while (list($key, $value) = each ($array))
      {
	print "<option value=\"$key\" ";

	// Si il faut présélectionner une valeur
	if ($id && $id == $key)
	  {
	    print 'selected="true"';
	  }

	if ($key_libelle)
	  {
	    print ">[$key] $value</option>\n";  
	  }
	else
	  {
	    if ($value=="-") { $value="&nbsp;"; }
	    print ">$value</option>\n";
	  }
      }

    print "</select>";
  }

  /**
   *    \brief  Renvoie la chaîne de caractère décrivant l'erreur
   *
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
     *      \param      filename            Nom fichier
     *      \param      filedir             Repertoire à scanner
     *      \param      urlsource           Url page origine
     *      \param      genallowed          Génération autorisée
     *      \param      delallowed          Suppression autorisée
     *      \param      modelselected       Modele à présélectionner par défaut
     *      \remarks    Le fichier de facture détaillée est de la forme
     *                  REFFACTURE-XXXXXX-detail.pdf ou XXXXX est une forme diverse
     */
    function show_documents($modulepart,$filename,$filedir,$urlsource,$genallowed,$delallowed=0,$modelselected='')
    {
        // filedir = conf->...dir_ouput."/".get_exdir(id)
        
        global $langs,$bc;
        $var=true;
 
        $filename = sanitize_string($filename);
        if ($modulepart != 'expedition') $relativepath = "${filename}/${filename}.pdf";
        else $relativepath = get_exdir("${filename}")."${filename}.pdf";
                    
        $i=0;
        if (is_dir($filedir))
        {
            $handle=opendir($filedir);
            while (($file = readdir($handle))!==false)
            {
                // Si fichier non lisible ou non .pdf, on passe au suivant
                if (! is_readable($filedir."/".$file) || ! eregi('\.pdf$',$file)) continue;

                
                if ($i==0)
                {
                    // Affiche en-tete tableau
                    if ($genallowed)
                    {
                        print '<form action="'.$urlsource.'" method="post">';
                        print '<input type="hidden" name="action" value="setpdfmodel">';
                    }

                    print_titre($langs->trans("Documents"));
                    print '<table class="border" width="100%">';
            
                    if ($genallowed)
                    {
                        $liste=array();
                        if ($modulepart == 'propal')
                        {
                            include_once(DOL_DOCUMENT_ROOT.'/includes/modules/propale/modules_propale.php');
                            $model=new ModelePDFPropales();
                            $liste=$model->liste_modeles($this->db);
                        }
                        elseif ($modulepart == 'facture')
                        {
                            include_once(DOL_DOCUMENT_ROOT.'/includes/modules/facture/modules_facture.php');
                            $model=new ModelePDFFactures();
                            $liste=$model->liste_modeles($this->db);
                        }
                        else
                        {
                            dolibarr_print_error($this->db,'Bad value for modulepart');
                        }
                        print '<tr '.$bc[$var].'><td>'.$langs->trans('Model').'</td><td align="center">';
                        $this->select_array('modelpdf',$liste,$modelselected);
                        $texte=$langs->trans('Generate');
                        print '</td><td align="center" colspan="2"><input class="button" type="submit" value="'.$texte.'">';
                        print '</td></tr>';
                    }
                }
                
                print "<tr $bc[$var]>";
                if (eregi('\-detail\.pdf',$file)) print '<td nowrap>PDF Détaillé</td>';
                else print '<td nowrap>PDF</td>';
                
                print '<td><a href="'.DOL_URL_ROOT . '/document.php?modulepart='.$modulepart.'&file='.urlencode($relativepath).'">'.$file.'</a></td>';
                print '<td align="right">'.filesize($filedir."/".$file). ' bytes</td>';
                print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($filedir."/".$file)).'</td>';
                print '</tr>';

                $i++;
            }
        }
        
        if ($i > 0)
        {
            // Affiche pied du tableau
            print "</table>\n";
            if ($genallowed)
            {
                print '</form>';
            }
        }

    }

}

?>
