<?php
/* Copyright (C) 2002 "stichting Blender Foundation, Timothy Kanters"
 * Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

 /*! \file htdocs/lib/thermometer.php
			\brief      Classe permettant d'afficher un thermometre.
			\author     Rodolphe Quiedeville.
			\author	    Timothy Kanters.
			\version    $Revision$

		Ensemble des fonctions permettant d'afficher un thermometre monetaire.
*/


/*!
		\brief permet d'afficher un thermometre monetaire.
		\param actualValue
		\param pendingValue
		\param intentValue
		\return thermometer htmlLegenda
*/
function moneyMeter($actualValue=0, $pendingValue=0, $intentValue=0)

	/*
    This function returns the html for the moneymeter.
    cachedValue: amount of actual money
    pendingValue: amount of money of pending memberships
    intentValue: amount of intended money (that's without the amount of actual money)
  */
{

    // variables
  $height="200";
  $maximumValue=125000;
        
  $imageDir = "http://eucd.info/images/";
                
  $imageTop = $imageDir . "therm_top.png";    
  $imageMiddleActual = $imageDir . "therm_actual.png";
  $imageMiddlePending = $imageDir . "therm_pending.png";
  $imageMiddleIntent = $imageDir . "therm_intent.png";
  $imageMiddleGoal = $imageDir . "therm_goal.png";
  $imageIndex = $imageDir . "therm_index.png";
  $imageBottom =  $imageDir . "therm_bottom.png";
  $imageColorActual = $imageDir . "therm_color_actual.png";
  $imageColorPending = $imageDir . "therm_color_pending.png";
  $imageColorIntent = $imageDir . "therm_color_intent.png";
       
  $htmlThermTop = '
        <!-- Thermometer Begin -->
        <table cellpadding="0" cellspacing="4" border="0">
        <tr><td>
        <table cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td colspan="2"><img src="' . $imageTop . '" width="58" height="6" border="0"></td>
          </tr>
          <tr>
            <td>
              <table cellpadding="0" cellspacing="0" border="0">';
                  
  $htmlSection = '
          <tr><td><img src="{image}" width="26" height="{height}" border="0"></td></tr>';
         
  $htmlThermbottom = '        
              </table>
            </td>
            <td><img src="' . $imageIndex . '" width="32" height="200" border="0"></td>
          </tr>
          <tr>
            <td colspan="2"><img src="' . $imageBottom . '" width="58" height="32" border="0"></td>
          </tr>
        </table>
        </td>
      </tr></table>';        

  // legenda
    
  $legendaActual = "&euro; " . round($actualValue);
  $legendaPending = "&euro; " . round($pendingValue);
  $legendaIntent = "&euro; " . round($intentValue);
  $legendaTotal = "&euro; " . round($actualValue + $pendingValue + $intentValue);
  $htmlLegenda = '

        <table cellpadding="0" cellspacing="0" border="0">
          <tr><td><img src="' . $imageColorActual . '" width="9" height="9">&nbsp;</td><td><font size="1" face="Verdana, Arial, Helvetica, sans-serif"><b>Payé:<br />' . $legendaActual . '</b></font></td></tr>
          <tr><td><img src="' . $imageColorPending . '" width="9" height="9">&nbsp;</td><td><font size="1" face="Verdana, Arial, Helvetica, sans-serif">En attente:<br />' . $legendaPending . '</font></td></tr>
          <tr><td><img src="' . $imageColorIntent . '" width="9" height="9">&nbsp;</td><td><font size="1" face="Verdana, Arial, Helvetica, sans-serif">Promesses:<br />' . $legendaIntent . '</font></td></tr>
          <tr><td>&nbsp;</td><td><font size="1" face="Verdana, Arial, Helvetica, sans-serif">Total:<br />' . $legendaTotal . '</font></td></tr>
        </table>

        <!-- Thermometer End -->';
      
  // check and edit some values
      
  $error = 0;    
  if ( $maximumValue <= 0 || $height <= 0 || $actualValue < 0 || $pendingValue < 0 || $intentValue < 0)
    {
      return "The money meter could not be processed<br>\n";        
    }
  if ( $actualValue > $maximumValue ) 
    {
      $actualValue = $maximumValue;
      $pendingValue = 0;
      $intentValue = 0;
    }
  else
    {
      if ( ($actualValue + $pendingValue) > $maximumValue )
	{
	  $pendingValue = $maximumValue - $actualValue;
	  $intentValue = 0;
	}
      else
	{
	  if ( ($actualValue + $pendingValue + $intentValue) > $maximumValue )
	    {
	      $intentValue = $maximumValue - $actualValue - $pendingValue;
	    }            
	}
    }
    
  // start writing the html (from bottom to top)
        
  // bottom    
  $thermometer = $htmlThermbottom;
    
  // actual
  $sectionHeight = round(($actualValue / $maximumValue) * $height);
  $totalHeight = $totalHeight + $sectionHeight;
  if ( $sectionHeight > 0 )
    {
      $section = $htmlSection;
      $section = str_replace("{image}", $imageMiddleActual, $section);
      $section = str_replace("{height}", $sectionHeight, $section);
      $thermometer = $section . $thermometer;
    }
  
  // pending
  $sectionHeight = round(($pendingValue / $maximumValue) * $height);
  $totalHeight = $totalHeight + $sectionHeight;
  if ( $sectionHeight > 0 )
    {
      $section = $htmlSection;
      $section = str_replace("{image}", $imageMiddlePending, $section);
      $section = str_replace("{height}", $sectionHeight, $section);
      $thermometer = $section . $thermometer;
    }
  
  // intent
  $sectionHeight = round(($intentValue / $maximumValue) * $height);
  $totalHeight = $totalHeight + $sectionHeight;
  if ( $sectionHeight > 0 )
    {
      $section = $htmlSection;
      $section = str_replace("{image}", $imageMiddleIntent, $section);
      $section = str_replace("{height}", $sectionHeight, $section);
      $thermometer = $section . $thermometer;
    }
  
  // goal        
  $sectionHeight = $height- $totalHeight;
  if ( $sectionHeight > 0 )
    {
      $section = $htmlSection;
      $section = str_replace("{image}", $imageMiddleGoal, $section);
      $section = str_replace("{height}", $sectionHeight, $section);
      $thermometer = $section . $thermometer;
    }      
  
  // top
  $thermometer = $htmlThermTop . $thermometer;
  
  return $thermometer . $htmlLegenda;
}

?>



