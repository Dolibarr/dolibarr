<?PHP
/* Copyright (c) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Benoit Mortier <benoit.mortier@opensides.be>
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

class Form {
  var $db;
  var $errorstr;


  Function Form($DB)
    {

      $this->db = $DB;

      return 1;
    }

  /*
   * Retourne la liste déroulante des départements/province/cantons
   * avec un affichage avec rupture sur le pays
   *
   */
    Function select_departement($selected='')
    {
        print '<select name="departement_id">';
    
        // On recherche les départements/cantons/province active d'une region et pays actif
        $sql = "SELECT d.rowid, d.code_departement as code , d.nom, d.active, p.libelle as libelle_pays, p.code as code_pays FROM llx_c_departements as d, llx_c_regions as r, llx_c_pays as p";
        $sql .= " WHERE d.fk_region=r.code_region and r.fk_pays=p.rowid";
        $sql .= " AND d.active = 1 AND r.active = 1 AND p.active = 1 ORDER BY code_pays, code ASC";
    
        if ($this->db->query($sql))
        {
            $num = $this->db->num_rows();
            $i = 0;
            if ($num)
            {
                $pays='';
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object( $i);
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
                            print '<option value="'.$obj->rowid.'" selected>['.$obj->code.'] '.$obj->nom.'</option>';
                        }
                        else
                        {
                            print '<option value="'.$obj->rowid.'">['.$obj->code.'] '.$obj->nom.'</option>';
                        }
                    }
                    $i++;
                }
            }
        }
        else {
            print "Erreur : $sql : ".$this->db->error();
        }
        print '</select>';
    }

 /*
   * Retourne la liste déroulante des pays actifs
   *
   */
  Function select_pays($selected='')
  {
    print '<select name="pays_id">';

    $sql = "SELECT rowid, libelle, active FROM llx_c_pays";
    $sql .= " WHERE active = 1 ORDER BY libelle ASC";

    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	$i = 0;
	if ($num)
	  {
	    while ($i < $num)
	      {
		$obj = $this->db->fetch_object( $i);
		if ($selected == $obj->rowid)
		  {
		    print '<option value="'.$obj->rowid.'" selected>'.$obj->libelle.'</option>';
		  }
		else
		  {
		    print '<option value="'.$obj->rowid.'">'.$obj->libelle.'</option>';
		  }
		$i++;
	      }
	  }
      }
    print '</select>';
  }


/*
   * Retourne la liste déroulante des civilite actives
   *
   */

  Function select_civilite($selected='')
  {
    print '<select name="civilite_id">';

    $sql = "SELECT rowid, civilite, active FROM llx_c_civilite";
    $sql .= " WHERE active = 1";

    if ($this->db->query($sql))
      {
	$num = $this->db->num_rows();
	$i = 0;
	if ($num)
	  {
	    while ($i < $num)
	      {
		$obj = $this->db->fetch_object( $i);
		if ($selected == $obj->rowid)
		  {
		    print '<option value="'.$obj->rowid.'" selected>'.$obj->civilite.'</option>';
		  }
		else
		  {
		    print '<option value="'.$obj->rowid.'">'.$obj->civilite.'</option>';
		  }
		$i++;
	      }
	  }
      }
    print '</select>';
  }

  /*
   * Retourne la liste déroulante des formes juridiques
   * avec un affichage avec rupture sur le pays
   *
   */
    Function select_forme_juridique($selected='')
    {
        print '<select name="forme_juridique_code">';
    
        // On recherche les formes juridiques actives des pays actifs
        $sql = "SELECT f.rowid, f.code as code , f.libelle as nom, f.active, p.libelle as libelle_pays, p.code as code_pays FROM llx_c_forme_juridique as f, llx_c_pays as p";
        $sql .= " WHERE f.fk_pays=p.rowid";
        $sql .= " AND f.active = 1 AND p.active = 1 ORDER BY code_pays, code ASC";

        if ($this->db->query($sql))
        {
            $num = $this->db->num_rows();
            $i = 0;
            if ($num)
            {
                $pays='';
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object( $i);
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
                            print '<option value="'.$obj->code.'" selected>['.$obj->code.'] '.$obj->nom.'</option>';
                        }
                        else
                        {
                            print '<option value="'.$obj->code.'">['.$obj->code.'] '.$obj->nom.'</option>';
                        }
                    }
                    $i++;
                }
            }
        }
        else {
            print "Erreur : $sql : ".$this->db->error();
        }
        print '</select>';
    }

  /*
   *
   *
   *
   */
  Function form_confirm($page, $title, $question, $action)
  {
    print '<form method="post" action="'.$page.'">';
    print '<input type="hidden" name="action" value="'.$action.'">';
    print '<table cellspacing="0" class="border" width="100%" cellpadding="3">';
    print '<tr><td colspan="3">'.$title.'</td></tr>';
    
    print '<tr><td class="valid">'.$question.'</td><td class="valid">';
    
    $this->selectyesno("confirm","no");
    
    print "</td>\n";
    print '<td class="valid" align="center"><input type="submit" value="Confirmer"</td></tr>';
    print '</table>';
    print "</form>\n";  
  }
  /*
   *
   *
   */
  Function select_tva($name='', $defaulttx = '')
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


  Function select_date($set_time='', $prefix='re', $h = 0, $m = 0, $empty=0)
  {
    if (! $set_time && !$empty)
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
    
    $smonth = 1;
    
    $cday = date("d", $set_time);
    $cmonth = date("n", $set_time);
    $syear = date("Y", $set_time);
    $shour = date("H", $set_time);
    $smin = date("i", $set_time);

    print '<select name="'.$prefix.'day">';    

    if ($empty)
      {
	$cday = 0;
	$cmonth = 0;
	$syear = 0;

	print '<option value="0" SELECTED>';
      }
    
    for ($day = 1 ; $day < $sday + 32 ; $day++) 
      {
	if ($day == $cday)
	  {
	    print "<option value=\"$day\" SELECTED>$day";
	  }
	else 
	  {
	    print "<option value=\"$day\">$day";
	  }
      }
    
    print "</select>";
    
    
    print '<select name="'.$prefix.'month">';    
    if ($empty)
      {
	print '<option value="0" SELECTED>';
      }


    for ($month = $smonth ; $month < $smonth + 12 ; $month++)
      {
	if ($month == $cmonth)
	  {
	    print "<option value=\"$month\" SELECTED>" . $strmonth[$month];
	  }
	else
	  {
	    print "<option value=\"$month\">" . $strmonth[$month];
	  }
      }
    print "</select>";

    if ($empty)
      {
	print '<input type="text" size="5" maxlength="4" name="'.$prefix.'year">';
      }
    else
      {
    
	print '<select name="'.$prefix.'year">';
	
	for ($year = $syear - 2; $year < $syear + 5 ; $year++)
	  {
	    if ($year == $syear)
	      {
		print "<option value=\"$year\" SELECTED>$year";
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
		print "<option value=\"$hour\" SELECTED>$hour";
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
		    print "<option value=\"$min\" SELECTED>$min";
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
   *
   *
   */
  Function select($name, $sql, $id='')
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
		      print "SELECTED";
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
   * Affiche un select à partir d'un tableau
   *
   */
  Function select_array($name, $array, $id='', $empty=0, $key_libelle=0)
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
		  print "SELECTED";
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
   * Renvoie la chaîne de caractère décrivant l'erreur
   *
   *
   */
  Function error()
    {
      return $this->errorstr;
    }
  /*
   *
   * Yes/No
   *
   */
  Function selectyesno($name,$value='')
  {
    print '<select name="'.$name.'">';

    if ($value == 'yes') 
      {
	print '<option value="yes" SELECTED>oui</option>';
	print '<option value="no">non</option>';
      }
    else
      {
	print '<option value="yes">oui</option>';
	print '<option value="no" SELECTED>non</option>';
      }
    print '</select>';
  }
  /*
   *
   * Yes/No
   *
   */
  Function selectyesnonum($name,$value='')
  {
    print '<select name="'.$name.'">';

    if ($value == 1) 
      {
	print '<option value="1" SELECTED>oui</option>';
	print '<option value="0">non</option>';
      }
    else
      {
	print '<option value="1">oui</option>';
	print '<option value="0" SELECTED>non</option>';
      }
    print '</select>';
  }
  /*
   *
   * Checkbox
   *
   */
  Function checkbox($name,$checked=0,$value=1)
    {
      if ($checked==1){
	print "<input type=\"checkbox\" name=\"$name\" value=\"$value\" checked />\n";
      }else{
	print "<input type=\"checkbox\" name=\"$name\" value=\"$value\" />\n";
      }
    }
}

?>
