<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/rapport/Atome.class.php
        \brief      Fichier de la classe mère Atome de génération de rapports
		\version	$Id$
*/

include_once DOL_DOCUMENT_ROOT.'/core/dolgraph.class.php';


/**
        \class      Atome
        \brief      Classe mère des classes de génération des images de rapports
*/

class Atome
{
    var $id;
    var $db;
    var $name;
    var $periode;
    var $graph_values;
    
    /**
     * Initialisation de la classe
     *
     */
    function AtomeInitialize($periode, $name, $daystart)
    {
        $this->year = strftime("%Y", $daystart);
        $this->month = strftime("%m", $daystart);
        $this->periode = $periode;
        $this->name = $name;
    }
    
    /**
     * 
     *
     */
    function ShowGraph()
    {
        $dir = DOL_DATA_ROOT.'/rapport/images/';
        if (! is_dir($dir)) create_exdir($dir);

        $this->graph_values = array();
    
        if ($this->periode == 'year')
        {
            $filename = $dir . $this->name.$this->year.'.png';
    
            for ($i = 0 ; $i < 12 ; $i++)
            {
                $index = $this->year . substr('00'.($i+1),-2);
                $value = 0;
                if ($this->datas[$index])
                {
                    $value = $this->datas[$index];
                }
    
                $libelle = ucfirst(strftime("%b", dolibarr_mktime(12,0,0,($i+1),1,2004)));
    
                $this->graph_values[$i] = array($libelle, $value);
            }
        }
    
        if ($this->periode == 'month')
        {
            $filename = $dir . $this->name.$this->year.$this->month.'.png';
    
            $datex = mktime(12,0,0,$this->month, 1, $this->year);
            $i = 0;
            while (strftime("%Y%m", $datex) == $this->year.$this->month)
            {
    
                $index = $this->year . $this->month . substr('00'.($i+1),-2);
                $value = 0;
                if ($this->datas[$index])
                {
                    $value = $this->datas[$index];
                }
    
                $libelle = ($i+1);
    
                $this->graph_values[$i] = array($libelle, $value);
    
                $i++;
                $datex = $datex + 86400;
            }
        }
    
        // var_dump($this->graph_values);
    
    
        $bgraph = new DolGraph();
        $bgraph->SetData($this->graph_values);
        $bgraph->bgcolor = array(255,255,255);
        $bgraph->SetWidth(600);
        $bgraph->SetHeight(400);
        $bgraph->draw($filename);
    
        return $filename;
    }
}
?>
