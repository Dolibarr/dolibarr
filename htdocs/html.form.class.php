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
                        print '<option value="'.$obj->rowid.'" selected>';
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
		      print '<option value="'.$obj->code.'" selected>'.$obj->libelle.'</option>';
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
   *    \todo      trier liste sur noms après traduction plutot que avant
   */
	 
  function select_pays($selected='',$htmlname='pays_id')
  {
    global $conf,$langs;
    $langs->load("dict");
    
    $sql = "SELECT rowid, libelle, code, active FROM ".MAIN_DB_PREFIX."c_pays";
    $sql .= " WHERE active = 1";
    $sql .= " ORDER BY code ASC;";
    
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
                if ($selected > 0 && $selected == $obj->rowid)
		  {
                    $foundselected=true;
                    print '<option value="'.$obj->rowid.'" selected>';
		  }
                else
		  {
                    print '<option value="'.$obj->rowid.'">';
		  }
                // Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
                if ($obj->code) { print $obj->code . ' - '; }
                print ($obj->code && $langs->trans("Country".$obj->code)!="Country".$obj->code?$langs->trans("Country".$obj->code):($obj->libelle!='-'?$obj->libelle:''));
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
   *    \brief     Retourne la liste déroulante des langues disponibles
   *    \param     
   */
	 
  function select_lang($selected='',$htmlname='lang_id')
  {
    global $langs;
    
    $langs_available=$langs->get_available_languages();
    
    print '<select class="flat" name="'.$htmlname.'">';
    $num = count($langs_available);
    $i = 0;
    if ($num)
      {
	while ($i < $num)
	  {
	    if ($selected == $langs_available[$i])
	      {
		print '<option value="'.$langs_available[$i].'" selected>'.$langs_available[$i].'</option>';
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
		print '<option value="'.$type_available[$i].'" selected>'.$langs->trans("BankType".$type_available[$i]).'</option>';
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
  function select_societes($selected='',$htmlname='soc_id',$filter)
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
                        print '<option value="'.$obj->idp.'" selected>'.$obj->nom.'</option>';
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
   *    \param      selected        Id contact pré-sélectionné
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
		    print '<option value="'.$obj->idp.'" selected>'.$obj->name.' '.$obj->firstname.'</option>';
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
	 *    \brief  Affiche la liste déroulante des projets d'une société donnée
	 *
	 */
	function select_projects($socid='', $selected='', $htmlname='projectid')
	{
		$socid=intVal($socid);
		if (empty($socid))
			return;
		// On recherche les societes
		$sql = 'SELECT p.rowid, p.title FROM ';
		$sql .= MAIN_DB_PREFIX .'projet as p';
		$sql .= ' WHERE fk_soc='.$socid;
		$sql .= ' ORDER BY p.title ASC';

		$result=$this->db->query($sql);
		if ($result)
		{
			print '<select class="flat" name="'.$htmlname.'">';
			$num = $this->db->num_rows($result);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object();
					if (!empty($selected) && $selected == $obj->rowid)
					{
						print '<option value="'.$obj->rowid.'" selected>'.$obj->title.'</option>';
					}
					else
					{
						print '<option value="'.$obj->rowid.'">'.$obj->title.'</option>';
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
   *    \brief      Retourne la liste des produits
   *    \param      selected        Produit présélectionné
   *    \param      htmlname        Nom de la zone select
   *    \param      filtretype      Pour filtre sur type de produit
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
        print "<option value=\"0\" selected>&nbsp;</option>";

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
   *    \brief      Retourne la liste des types de paiements possibles
   *    \param      selected        Type de praiement présélectionné
   *    \param      htmlname        Nom de la zone select
   *    \param      filtretype      Pour filtre
   */
    function select_types_paiements($selected='',$htmlname='paiementtype',$filtretype='')
    {
        global $langs;
        
        $sql = "SELECT id, code, libelle";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_paiement";
        $sql.= " WHERE active > 0";
        $sql.= " ORDER BY id";
        $result = $this->db->query($sql);
        if ($result)
        {
            print '<select class="flat" name="'.$htmlname.'">';
            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                if ($selected == $obj->code)
                {
                    print '<option value="'.$obj->id.'" selected>';
                }
                else
                {
                    print '<option value="'.$obj->id.'">';
                }
                // Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
                print ($langs->trans($obj->code)!=$obj->code?$langs->trans($obj->code):($obj->libelle!='-'?$obj->libelle:''));
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
   *    \brief      Retourne la liste des comptes
   *    \param      selected        Type de praiement présélectionné
   *    \param      htmlname        Nom de la zone select
   *    \param      filtretype      Pour filtre
   */
    function select_comptes($selected='',$htmlname='paiementtype',$statut=0,$filtre='')
    {
        global $langs;
    
        $sql = "SELECT rowid, label, bank";
        $sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
        $sql.= " WHERE clos = '".$satut."'";
        if ($filtre) $sql.=" AND ".$filtre;
        $sql.= " ORDER BY rowid";
        $result = $this->db->query($sql);
        if ($result)
        {
            print '<select class="flat" name="'.$htmlname.'">';
            $num = $this->db->num_rows($result);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($result);
                if ($selected == $obj->rowid)
                {
                    print '<option value="'.$obj->rowid.'" selected>';
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
                    print '<option value="'.$obj->code.'" selected>';
		  }
                else
		  {
                    print '<option value="'.$obj->code.'">';
		  }
                // Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
                print ($langs->trans($obj->code)!=$obj->code?$langs->trans($obj->code):($obj->civilite!='-'?$obj->civilite:''));
                print '</option>';
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
   *    \brief      Retourne la liste déroulante des formes juridiques tous pays confondus ou pour un pays donné.
   *    \remarks    Dans le cas d'une liste tous pays confondu, on affiche une rupture sur le pays
   *    \param      selected        Code forme juridique a présélectionné
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
                        print '<option value="'.$obj->code.'" selected>';
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
    else {
        dolibarr_print_error($this->db);
    }
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
   *    \param      code_iso    Code iso de la devise
   *    \param      withcode    1=affiche code + nom
   *    \return     string      Nom traduit de la devise
   */
    function currency_name($code_iso,$withcode=0)
    {
        global $langs;

        $sql = "SELECT label FROM ".MAIN_DB_PREFIX."c_currencies";
        $sql.= " WHERE code_iso='$code_iso';";
    
        if ($this->db->query($sql))
        {
            $num = $this->db->num_rows();
    
            if ($num)
            {
                $obj = $this->db->fetch_object();
                $label=$langs->trans("Currency".$obj->code_iso)!="Currency".$obj->code_iso?$langs->trans("Currency".$obj->code_iso):($obj->label!='-'?$obj->label:'');
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
   *    \brief     Retourne la liste des devies, dans la langue de l'utilisateur
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
                        print '<option value="'.$obj->code_iso.'" selected>';
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
     *      \param      name            Nom champ html
     *      \param      defaulttx       Taux tva présélectionné
     *      \param      empy            Proposer ligne "Defaut"
     */
    function select_tva($name='tauxtva', $defaulttx='', $default=0)
    {
        global $langs;
        
        $file = DOL_DOCUMENT_ROOT . "/conf/tva.local.php";
        if (is_readable($file))
        {
            include $file;
        }
        else
        {
            $txtva[0] = '19.6';
            $txtva[1] = '5.5';
            $txtva[2] = '0';
        }
    
        if ($defaulttx == '')
        {
            $defaulttx = $txtva[0];
        }
    
        $taille = sizeof($txtva);
    
        print '<select class="flat" name="'.$name.'">';
        if ($default) print '<option value="0">'.$langs->trans("Default").'</option>';
        
        for ($i = 0 ; $i < $taille ; $i++)
        {
            print '<option value="'.$txtva[$i].'"';
            if ($txtva[$i] == $defaulttx)
            {
                print ' selected>'.$txtva[$i].' %</option>';
            }
            else
            {
                print '>'.$txtva[$i].' %</option>';
            }
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
    global $langs;
    
    if (! $set_time && ! $empty)
      {
	$set_time = time();
      }

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
    else {
      // Date est un timestamps
      $syear = date("Y", $set_time);
      $smonth = date("n", $set_time);
      $sday = date("d", $set_time);
      $shour = date("H", $set_time);
      $smin = date("i", $set_time);
    }
    
    // Jour
    print '<select class="flat" name="'.$prefix.'day">';    

    if ($empty || $set_time == -1)
      {
    	$sday = 0;
    	$smonth = 0;
    	$syear = 0;
    	$shour = 0;
    	$smin = 0;

    	print '<option value="0" selected>';
      }
    
    for ($day = 1 ; $day <= 31; $day++) 
      {
	if ($day == $sday)
	  {
	    print "<option value=\"$day\" selected>$day";
	  }
	else 
	  {
	    print "<option value=\"$day\">$day";
	  }
      }
    
    print "</select>";
    
    print '<select class="flat" name="'.$prefix.'month">';    
    if ($empty || $set_time == -1)
      {
	print '<option value="0" selected>';
      }

    // Mois
    for ($month = 1 ; $month <= 12 ; $month++)
      {
	if ($month == $smonth)
	  {
	    print "<option value=\"$month\" selected>" . $strmonth[$month];
	  }
	else
	  {
	    print "<option value=\"$month\">" . $strmonth[$month];
	  }
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
		print "<option value=\"$year\" selected>$year";
	      }
	    else
	      {
		print "<option value=\"$year\">$year";
	      }
	  }
	print "</select>\n";
      }

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
		print "<option value=\"$hour\" selected>$hour";
	      }
	    else
	      {
		print "<option value=\"$hour\">$hour";
	      }
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
		    print "<option value=\"$min\" selected>$min";
		  }
		else
		  {
		    print "<option value=\"$min\">$min";
		  }
	      }
	    print "</select>M\n";
	  }
	
      }
  }
	
  /**
   *    \brief      Affiche liste déroulante
   *
   */
  function select($name, $sql, $id='')
  {

    $result = $this->db->query($sql);
    if ($result)
      {

	print '<select class="flat" name="'.$name.'">';

	$num = $this->db->num_rows();
	$i = 0;
	  
	if (strlen("$id"))
	  {	    	      
	    while ($i < $num)
	      {
		$row = $this->db->fetch_row($i);
		print "<option value=\"$row[0]\" ";
		if ($id == $row[0])
		  {
		    print "selected";
		  }
		print ">$row[1]</option>\n";
		$i++;
	      }
	  }
	else
	  {
	    while ($i < $num)
	      {
		$row = $this->db->fetch_row($i);
		print "<option value=\"$row[0]\">$row[1]</option>\n";
		$i++;
	      }
	  }

	print "</select>";
      }
    else 
      {
	print $this->db->error();
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
	    print "selected";
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
        print '<option value="'.$yes.'" selected>'.$langs->trans("yes").'</option>'."\n";
        print '<option value="'.$no.'">'.$langs->trans("no").'</option>'."\n";
    }
    else
    {
        print '<option value="'.$yes.'">'.$langs->trans("yes").'</option>'."\n";
        print '<option value="'.$no.'" selected>'.$langs->trans("no").'</option>'."\n";
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
   *    \param      nom             valeur pour nom du rapport
   *    \param      variante        lien optionnel du variante du rapport
   *    \param      period          periode du reporting
   *    \param      description     description
   *    \param      builddate       date génération 
   *    \param      exportlink      lien pour export
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
    
    print '</table><br>';
    print '</div>';
    print "\n<!-- fin cartouche rapport -->\n\n";
  }

}

?>
