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
 * Format de CDR Fructidor
 *
 * 0297500000;05/09/2006;12:50:34;33617000000;France-Mobile-SFR;5;0.0116
 * 0297500001;05/09/2006;12:58:45;33240000000;France national ;124;0.0256
 * 0297500000;27/09/2006;16:18:18;33625000000;France-Mobile-SFR;4;0.0093
 * 0297500000;27/09/2006;16:22:32;33240000000;France national ;14;0.0029
 *
 */
class CdrFormatFructidor 
{

  function CdrFormatFructidor()
  {
    $this->nom = "Fructidor";
    $this->datas = array();
    $this->messages = array();
  }

  function showSample()
  {
    $sample = '
0297500000;05/09/2006;12:50:34;33617000000;France-Mobile-SFR;5;0.0116
0297500001;05/09/2006;12:58:45;33240000000;France national ;124;0.0256
0297500000;27/09/2006;16:18:18;33625000000;France-Mobile-SFR;4;0.0093
0297500000;27/09/2006;16:22:32;33240000000;France national ;14;0.0029';

    return $sample;
  }

  function ReadFile($file)
  {
    $this->messages = array();
    dol_syslog("CdrFormatFructidor::ReadFile($file)", LOG_DEBUG);
    $badformat = 0;
    $error = 0;
    $i = 0;
    $line = 0;
    $hf = fopen ($file, "r");
		
    while (!feof($hf))
      {
	$cont = fgets($hf, 1024);
	
	if (strlen(trim($cont)) > 0)
	  {
	    $tabline = explode(";", $cont);
	    if (sizeof($tabline) == 7)
	      {
		$this->datas[$i]['index']   = $i;
		$this->datas[$i]['ligne']   = ereg_replace('"','',$tabline[0]);
		$this->datas[$i]['date']    = $tabline[1];
		$this->datas[$i]['heure']   = $tabline[2];
		$this->datas[$i]['numero']  = ereg_replace('"','',$tabline[3]);
		$this->datas[$i]['tarif']   = trim($tabline[4]);
		$this->datas[$i]['duree']   = trim($tabline[5]);
		$this->datas[$i]['montant'] = trim($tabline[6]);

		if (preg_match("/\D/",$this->datas[$i]['numero']))
		  {
		    array_push($this->messages,array('error',"Une ligne du fichier contient un numero invalide : ".$this->datas[$i]['numero']));
		    $error++;
		  }

		$i++;
	      }	  
	    else
	      {
		dol_syslog("CdrFormatFructidor::ReadFile Mauvais format de fichier ligne $line", LOG_ERR);
		$badformat++;
	      }  
	    $line++;
	  }
      }
    fclose($hf);
    array_push($this->messages,array('info',"$line lignes lues dans le fichier"));

    if ($badformat > 0)
      {
	array_push($this->messages,array('error',"$badformat lignes ont un mauvais format dans le fichier"));
      }

    dol_syslog("CdrFormatFructidor::ReadFile read $i lines", LOG_DEBUG);

    return $error;
  }
}
