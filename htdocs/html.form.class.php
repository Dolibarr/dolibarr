<?PHP
/* Copyright (c) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
   *
   *
   *
   */
  Function form_confirm($page, $title, $question)
  {
    print '<form method="post" action="'.$page.'">';
    print '<input type="hidden" name="action" value="confirm_valid">';
    print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
    
    print '<tr><td colspan="3">'.$title.'</td></tr>';
    
    print '<tr><td class="valid">'.$question.'</td><td class="valid">';
    $htmls = new Form($db);
    
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
  Function select_tva($name='', $defaulttx = 19.6)
  {
    if (! strlen(trim($name)))
    {
      $name = "tauxtva";
    }

    print '<select name="'.$name.'">';
    print '<option value="19.6">19.6';
    print '<option value="5.5">5.5';
    print '<option value="0">0';
    print '</select>';
  }


  Function select_date($set_time='')
  {
    if (! $set_time)
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
    
    print "<select name=\"reday\">";    
    
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
    
    
    print "<select name=\"remonth\">";    
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
    
    print "<select name=\"reyear\">";
    
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
  /*
   *
   *
   */
  Function select_array($name, $array, $id='')
    {
      print '<select name="'.$name.'">';
      
      $i = 0;

      if (strlen($id))
	{	    	      
	  reset ($array);
	  while (list($key, $value) = each ($array))
	    {
	      print "<option value=\"$key\" ";
	      if ($id == $key)
		{
		  print "SELECTED";
		}
	      print ">$value</option>\n";	      
	    }
	}
      else
	{
	  while (list($key, $value) = each ($array) )
	    {
	      print "<option value=\"$key\" ";
	      print ">$value</option>\n";
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
