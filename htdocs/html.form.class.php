<?PHP
/* Copyright (c) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
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

/*!	    \file       htdocs/html.form.class.php
		\brief      Fichier de la classe des fonctions prédéfinie de composants html
		\version    $Revision$
*/


/*!     \class Form
		\brief Classe permettant la génération de composants html
*/

class Form
{
  var $db;
  var $errorstr;
  
  /*!	\brief     Constructeur
		\param     DB      handler d'accès base de donnée
  */
	
  function Form($DB)
  {
    $this->db = $DB;
    
    return 1;
  }
  
  /*
   *    \brief      Retourne la liste déroulante des départements/province/cantons 
   *                avec un affichage avec rupture sur le pays
   *    \remarks    La cle de la liste est le code (il peut y avoir plusieurs entrée pour
   *                un code donnée mais dans ce cas, le champ pays et lang diffère).
   *                Ainsi les liens avec les départements se font sur un département
   *                independemment de nom som.
   */
	 
  function select_departement($selected='',$htmlname='departement_id')
  {
    global $conf,$langs;
    $langs->load("dict");

    // On recherche les départements/cantons/province active d'une region et pays actif
    $sql = "SELECT d.rowid, d.code_departement as code , d.nom, d.active, p.libelle as libelle_pays, p.code as code_pays FROM ";
    $sql .= MAIN_DB_PREFIX ."c_departements as d, ".MAIN_DB_PREFIX."c_regions as r,".MAIN_DB_PREFIX."c_pays as p";
    $sql .= " WHERE d.fk_region=r.code_region and r.fk_pays=p.rowid";
    $sql .= " AND d.active = 1 AND r.active = 1 AND p.active = 1 ";
    $sql .= "ORDER BY code_pays, code ASC";
    
    if ($this->db->query($sql))
    {
        print '<select name="'.$htmlname.'">';
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
    
                    if ($selected > 0 && $selected == $obj->rowid)
                    {
                        print '<option value="'.$obj->rowid.'" selected>';
                    }
                    else
                    {
                        print '<option value="'.$obj->rowid.'">';
                    }
                    # Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
                    print '['.$obj->code.'] '.($langs->trans($obj->code)!=$obj->code?$langs->trans($obj->code):($obj->nom!='-'?$obj->nom:''));
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
  
  /*
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
    print '<select name="'.$htmlname.'">';
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

  /*
   *    \brief     Retourne la liste déroulante des pays actifs, dans la langue de l'utilisateur
   *    \param     selected    code pays pré-sélectionné
   *    \param     htmlname    nom de la liste deroulante
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
        print '<select name="'.$htmlname.'">';
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
                # Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
                if ($obj->code) { print '['.$obj->code.'] '; }
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


  /*
   *    \brief     Retourne la liste déroulante des langues disponibles
   *    \param     
   */
	 
  function select_lang($selected='',$htmlname='lang_id')
  {
    global $langs;
    
    $langs_available=$langs->get_available_languages();
    
    print '<select name="'.$htmlname.'">';
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


  /*
   *    \brief   Retourne la liste déroulante des sociétés
   *    \param
   */
	 
  function select_societes($selected='',$htmlname='soc_id')
  {
    // On recherche les societes
    $sql = "SELECT s.idp, s.nom FROM ";
    $sql .= MAIN_DB_PREFIX ."societe as s ";
    $sql .= "ORDER BY nom ASC";

    if ($this->db->query($sql))
      {
    print '<select name="'.$htmlname.'">';
	$num = $this->db->num_rows();
	$i = 0;
	if ($num)
	  {
	    while ($i < $num)
	      {
		$obj = $this->db->fetch_object();
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
  
  /*
   *    \brief  Retourne la liste déroulante des contacts d'une société donnée
   *
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
    print '<select name="'.$htmlname.'">';
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
    else {
      dolibarr_print_error($this->db);
    }
  }

  
  /*
   *    \brief      Retourne le nom d'un pays
   *    \param      id      id du pays
   */
	 
  function pays_name($id)
  {
    $sql = "SELECT rowid, libelle FROM ".MAIN_DB_PREFIX."c_pays";
    $sql .= " WHERE rowid=$id;";
		
    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
  
	if ($num)
	  {
	    $obj = $this->db->fetch_object();
	    return $obj->libelle;
	  }
	else
	  {
	    return "Non défini";
	  }

      }
	
  }



  /*
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
        print '<select name="civilite_id">';
        $num = $this->db->num_rows();
        $i = 0;
        if ($num)
        {
            while ($i < $num)
            {
                $obj = $this->db->fetch_object();
                if ($selected == $obj->rowid)
                {
                    print '<option value="'.$obj->code.'" selected>';
                }
                else
                {
                    print '<option value="'.$obj->code.'">';
                }
                # Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
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

  /*
   *    \brief  Retourne la liste déroulante des formes juridiques avec un affichage avec rupture sur le pays
   *
   */
	 
  function select_forme_juridique($selected='')
  {
    global $conf,$langs;
    $langs->load("dict");

    // On recherche les formes juridiques actives des pays actifs
    $sql = "SELECT f.rowid, f.code as code , f.libelle as nom, f.active, p.libelle as libelle_pays, p.code as code_pays FROM llx_c_forme_juridique as f, llx_c_pays as p";
    $sql .= " WHERE f.fk_pays=p.rowid";
    $sql .= " AND f.active = 1 AND p.active = 1 ORDER BY code_pays, code ASC";
    
    if ($this->db->query($sql))
    {
        print '<select name="forme_juridique_code">';
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
                        print '<option value="0">----- '.$obj->libelle_pays." -----</option>\n";
                        $pays=$obj->libelle_pays;
                    }
    
                    if ($selected > 0 && $selected == $obj->code)
                    {
                        print '<option value="'.$obj->code.'" selected>';
                    }
                    else
                    {
                        print '<option value="'.$obj->code.'">';
                    }
                    # Si traduction existe, on l'utilise, sinon on prend le libellé par défaut
                    print '['.$obj->code.'] '.($langs->trans($obj->code)!=$obj->code?$langs->trans($obj->code):($obj->nom!='-'?$obj->nom:''));
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
  
  /*
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
    print '<tr><td colspan="3">'.$title.'</td></tr>';
    
    print '<tr><td class="valid">'.$question.'</td><td class="valid">';
    
    $this->selectyesno("confirm","no");
    
    print "</td>\n";
    print '<td class="valid" align="center"><input type="submit" value="'.$langs->trans("Confirm").'"</td></tr>';
    print '</table>';
    print "</form>\n";  
  }
	
  /*
   *    \brief  Selection du taux de tva
   *
   */
	 
  function select_tva($name='', $defaulttx = '')
  {
    if (! strlen(trim($name)))
    {
      $name = "tauxtva";
    }

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

    print '<select name="'.$name.'">';

    for ($i = 0 ; $i < $taille ; $i++)
      {
	print '<option value="'.$txtva[$i].'"';
	if ($txtva[$i] == $defaulttx)
	  {
	    print ' SELECTED>'.$txtva[$i].' %</option>';
	  }
	else
	  {
	    print '>'.$txtva[$i].' %</option>';
	  }
      }
    print '</select>';
  }

  /*
   *    \brief  Affiche zone de selection de date
   *            Liste deroulante pour les jours, mois, annee et eventuellement heurs et minutes
   *            Les champs sont présélectionnées avec:
   *            - La date set_time (timestamps ou date au format YYYY-MM-DD ou YYYY-MM-DD HH:MM)
   *            - La date du jour si set_time vaut ''
   *            - Aucune date (champs vides) si set_time vaut -1
   */
  function select_date($set_time='', $prefix='re', $h = 0, $m = 0, $empty=0)
  {
    if (! $set_time && ! $empty)
      {
	    $set_time = time();
      }

    $strmonth[1] = "Janvier";
    $strmonth[2] = "F&eacute;vrier";
    $strmonth[3] = "Mars";
    $strmonth[4] = "Avril";
    $strmonth[5] = "Mai";
    $strmonth[6] = "Juin";
    $strmonth[7] = "Juillet";
    $strmonth[8] = "Ao&ucirc;t";
    $strmonth[9] = "Septembre";
    $strmonth[10] = "Octobre";
    $strmonth[11] = "Novembre";
    $strmonth[12] = "D&eacute;cembre";
    
    # Analyse de la date de préselection
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
    
    print '<select name="'.$prefix.'day">';    

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
    
    
    print '<select name="'.$prefix.'month">';    
    if ($empty || $set_time == -1)
      {
	print '<option value="0" selected>';
      }


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

    if ($empty || $set_time == -1)
      {
	print '<input type="text" size="5" maxlength="4" name="'.$prefix.'year">';
      }
    else
      {
    
	print '<select name="'.$prefix.'year">';
	
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
	print '<select name="'.$prefix.'hour">';
    
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
	    print '<select name="'.$prefix.'min">';
	    
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
	
  /*
   *    \brief      Affiche liste déroulante
   *
   */
  function select($name, $sql, $id='')
    {

      $result = $this->db->query($sql);
      if ($result)
	{

	  print '<select name="'.$name.'">';

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
    
    /*!
    		\brief Affiche un select à partir d'un tableau
    		\param	name            nom de la zone select
    		\param	array           tableau de key+valeur
    		\param	id              key présélectionnée
    		\param	empty           1 si il faut un valeur "-" dans la liste, 0 sinon
    		\param	key_libelle     1 pour afficher la key dans la valeur "[key] value"
    */
		
  function select_array($name, $array, $id='', $empty=0, $key_libelle=0)
    {
      print '<select name="'.$name.'">';
      
      $i = 0;

      if (strlen($id))
	{
	  if ($empty == 1)
	    {
	      $array[0] = "-";
	    }
	  reset($array);

	  while (list($key, $value) = each ($array))
	    {
	      print "<option value=\"$key\" ";
	      if ($id == $key)
		{
		  print "selected";
		}
	      if ($key_libelle)
		{
		  print ">[$key] $value</option>\n";  
		}
	      else
		{
		  print ">$value</option>\n";
		}
	    }
	}
      else
	{
	  while (list($key, $value) = each ($array) )
	    {
	      print "<option value=\"$key\" ";
	      if ($key_libelle)
		{
		  print ">[$key] $value</option>\n";  
		}
	      else
		{
		  print ">$value</option>\n";
		}
	    }
	
	}

      print "</select>";
    
    }
  /*
   *    \brief  Renvoie la chaîne de caractère décrivant l'erreur
   *
   */
	 
  function error()
    {
      return $this->errorstr;
    }


  /*
   *    \brief      Selection de oui/non en caractere (renvoi yes/no)
   *    \param      name        nom du select
   *    \param      value       valeur présélectionnée
   *    \param      option      0 retourne yes/no, 1 retourne 1/0
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

    print '<select name="'.$name.'">'."\n";

    if ($value == 'no' || $value == 0) 
      {
	print '<option value="'.$yes.'">'.$langs->trans("yes").'</option>'."\n";
	print '<option value="'.$no.'" selected>'.$langs->trans("no").'</option>'."\n";
      }
    else
      {
	print '<option value="'.$yes.'" selected>'.$langs->trans("yes").'</option>'."\n";
	print '<option value="'.$no.'">'.$langs->trans("no").'</option>'."\n";
      }
    print '</select>'."\n";
  }
	
  /*
   *    \brief      Selection de oui/non en chiffre (renvoi 1/0)
   *    \param      name        nom du select
   *    \param      value       valeur présélectionnée
   */
  function selectyesnonum($name,$value='')
  {
    $this->selectyesno($name,$value,1);
  }
	
  /*
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

  /*
   *    \brief  Affiche la partie de formulaire pour saisie d'un mail
   *    \param  withtopic   1 pour proposer à la saisie le sujet
   *    \param  withbody    1 pour proposer à la saisie le corps du message
   *    \param  withfile    1 pour proposer à la saisie l'ajout d'un fichier joint
   */
  function mail_topicmessagefile($withtopic=1,$withbody=1,$withfile=1,$defaultbody) {
    global $langs;

    $langs->load("other");

	print "<table class=\"border\" width=\"100%\">";

	// Topic
    if ($withtopic) {
        print "<tr>";
    	print "<td width=\"180\">".$langs->trans("MailTopic")."</td>";
        print "<td>";
    	print "<input type=\"text\" size=\"60\" name=\"subject\" value=\"\">";
        print "</td></tr>";
    }
    
    // Message
    if ($withbody) {
        print "<tr>";
        print "<td width=\"180\" valign=\"top\">".$langs->trans("MailText")."</td>";
        print "<td>";
    	print "<textarea rows=\"8\" cols=\"72\" name=\"message\">";
        print $defaultbody;
    	print "</textarea>";
    	print "</td></tr>";
    }
    	
	// Si fichier joint
    if ($withfile) {
        print "<tr>";
        print "<td width=\"180\">".$langs->trans("MailFile")."</td>";
    	print "<td>";
    	print "<input type=\"file\" value=\"".$langs->trans("Upload")."\"/>";
    	print "</td></tr>";
    }
    
    print "</table>";
    
  }

}

?>
