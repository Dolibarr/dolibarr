<?PHP
/* Copyright (C) 2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * Format de CDR Messidor
 *
 * 1;"0240020000";02/08/2006;12:04:21;"0620770000";FRANCE MOBILE;01mn14;MX2;0,148;60;
 * 6;"0240020000";02/08/2006;16:36:45;"0674290000";FRANCE MOBILE;08mn13;MX2;0,986;480;
 * 3;"0240020000";02/08/2006;12:32:44;"0620770000";FRANCE MOBILE;02mn10;MX2;0,260;60;
 *
 */

class CdrFormatMessidor 
{

  function CdrFormatMessidor()
  {
    $this->nom = "Messidor";
    $this->datas = array();
    $this->messages = array();
  }

  function ShowSample()
  {
    $sample = '
1;"0240020000";02/08/2006;12:04:21;"0620770000";FRANCE MOBILE;01mn14;MX2;0,148;60;
6;"0240020000";02/08/2006;16:36:45;"0674290000";FRANCE MOBILE;08mn13;MX2;0,986;480;
3;"0240020000";02/08/2006;12:32:44;"0620770000";FRANCE MOBILE;02mn10;MX2;0,260;60;';

    return $sample;
  }

  function ReadFile($file)
  {
    dol_syslog("CdrFormatMessidor::ReadFile($file)", LOG_DEBUG);
    
    $error = 0;
    $i = 0;
    $line = 1;
    $hf = fopen ($file, "r");
		
    while (!feof($hf))
      {
	$cont = fgets($hf, 1024);
	
	if (strlen(trim($cont)) > 0)
	  {
	    $tabline = explode(";", $cont);
	    if (sizeof($tabline) == 11)
	      {
		$this->datas[$i]['index']  = $tabline[0];
		$this->datas[$i]['ligne']  = ereg_replace('"','',$tabline[1]);
		$date  = $tabline[2];
		$this->datas[$i]['date']   = $date;
		$this->datas[$i]['heure']  = $tabline[3];
		$this->datas[$i]['numero'] = ereg_replace('"','',$tabline[4]);
						
		$this->datas[$i]['montant']           = trim($tabline[8]);
		$this->datas[$i]['duree']             = trim($tabline[9]);
		$i++;
	      }	  
	    else
	      {
		dol_syslog("CdrFormatMessidor::ReadFile Mauvais format de fichier ligne $line");
	      }  
	  }
	$line++;
      }
    fclose($hf);
    array_push($this->messages,array('info',"Fichier ".basename($file)." : $line lignes lues dans le fichier"));
    dol_syslog("CdrFormatMessidor::ReadFile read $i lines", LOG_DEBUG);
  }
}
