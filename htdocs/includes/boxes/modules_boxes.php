<?php
/* Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 *
 */

/**
	    \file       htdocs/includes/boxes/modules_boxes.php
		\ingroup    facture
		\brief      Fichier contenant la classe mère des boites
		\version    $Revision$
*/


/**
	    \class      ModeleBoxes
		\brief      Classe mère des boites
*/

class ModeleBoxes
{
    var $MAXLENGTHBOX=70;   // Mettre 0 pour pas de limite
  
    var $error='';


   /** 
        \brief      Renvoi le dernier message d'erreur de création de facture
    */
    function error()
    {
        return $this->error;
    }


   /** 
        \brief      Methode standard d'affichage des boites
        \param      $head       tableau des caractéristiques du titre
        \param      $contents   tableau des lignes de contenu
    */
    function showBox($head, $contents)
    {
        global $langs;

        $bcx[0] = 'class="box_pair"';
        $bcx[1] = 'class="box_impair"';
    
        $var = true;
        $nbcol=sizeof($contents[0])+1;
    
        print '<table width="100%" class="noborder">';
    
        // Affiche titre de la boite
        print '<tr class="box_titre"><td';
        if ($nbcol > 0) { print ' colspan="'.$nbcol.'"'; }
        print '>'.$head['text']."</td></tr>";
    
        // Affiche chaque ligne de la boite
        for ($i=0, $n=sizeof($contents); $i < $n; $i++)
        {
            $var=!$var;
            if (sizeof($contents[$i])) print '<tr valign="top" '.$bcx[$var].'>';
    
            // Affiche chaque cellule
            for ($j=0, $m=sizeof($contents[$i]); $j < $m; $j++)
            {
                $tdparam="";
                if (isset($contents[$i][$j]['align'])) $tdparam.=' align="'. $contents[$i][$j]['align'].'"';
                if (isset($contents[$i][$j]['width'])) $tdparam.=' width="'. $contents[$i][$j]['width'].'"';
    
                if ($contents[$i][$j]['text']) {
                    $texte=$contents[$i][$j]['text'];
                    $textewithnotags=eregi_replace('<[^>]+>','',$texte);
                    $texte2=$contents[$i][$j]['text2'];
                    $texte2withnotags=eregi_replace('<[^>]+>','',$texte2);
                    //print "xxx $textewithnotags y";

                    if (isset($contents[$i][$j]['logo']) && $contents[$i][$j]['logo']) print '<td width="16">';
                    else print '<td '.$tdparam.'>';
    
                    if (isset($contents[$i][$j]['url'])) print '<a href="'.$contents[$i][$j]['url'].'" title="'.$textewithnotags.'">';
                    if (isset($contents[$i][$j]['logo']) && $contents[$i][$j]['logo'])
                    {
                        $logo=eregi_replace("^object_","",$contents[$i][$j]['logo']);
                        print img_object($langs->trans("Show"),$logo);
                        print '</a></td><td '.$tdparam.'><a href="'.$contents[$i][$j]['url'].'" title="'.$textewithnotags.'">';
                    }
                    if ($this->MAXLENGTHBOX && strlen($textewithnotags) > $this->MAXLENGTHBOX)
                    {
                        $texte=substr($texte,0,$this->MAXLENGTHBOX)."...";
                    }
                    if ($this->MAXLENGTHBOX && strlen($texte2withnotags) > $this->MAXLENGTHBOX)
                    {
                        $texte2=substr($texte2,0,$this->MAXLENGTHBOX)."...";
                    }
                    print $texte;
                    if ($contents[$i][$j]['url']) print '</a>';
                    print $texte2;
    
                    print "</td>";
                }
            }

            if (sizeof($contents[$i])) print '</tr>';
        }
    
        print "</table>";
    }
    
}


?>
