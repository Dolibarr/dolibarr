<?php
/* Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
   \file		htdocs/lib/date.lib.php
   \brief		Ensemble de fonctions de base de dolibarr sous forme d'include
   \version		$Id$
*/


/**   \brief      Converti les heures et minutes en secondes
 *    \param      iHours      Heures
 *    \param      iMinutes    Minutes
 *    \param      iSeconds    Secondes
 *    \return     iResult	    Temps en secondes
 */
function ConvertTime2Seconds($iHours=0,$iMinutes=0,$iSeconds=0)
{
	$iResult=($iHours*3600)+($iMinutes*60)+$iSeconds;
	return $iResult;
}


/**	  \brief      Converti les secondes en heures et minutes
 *    \param      iSecond     Nombre de secondes
 *    \param      format      Choix de l'affichage (all:affichage complet, hour: n'affiche que les heures, min: n'affiche que les minutes)
 *    \return     sTime       Temps formaté 	
 */
function ConvertSecondToTime($iSecond,$format='all'){
	if ($format == 'all'){
		$sTime=date("H",$iSecond)-1;
		$sTime.='h'.date("i",$iSecond);
	}else if ($format == 'hour'){
		$sTime=date("H",$iSecond)-1;
	}else if ($format == 'min'){
		$sTime=date("i",$iSecond);
	}
	return $sTime;
}


/**	\brief		Return previous month
 *	\param		month	Month
 *	\param		year	Year
 *	\return		array	Previous year,month
 */
function dol_get_prev_month($month, $year)
{
	if ($month == 1)
	{
		$prev_month = 12;
		$prev_year  = $year - 1;
	}
	else
	{
		$prev_month = $month-1;
		$prev_year  = $year;
	}
	return array('year' => $prev_year, 'month' => $prev_month);
}

/**	\brief		Return next month
 *	\param		month	Month
 *	\param		year	Year
 *	\return		array	Next year,mont
 */
function dol_get_next_month ($month, $year)
{
	if ($month == 12)
	{
		$next_month = 1;
		$next_year  = $year + 1;
	}
	else
	{
		$next_month = $month + 1;
		$next_year  = $year;
	}
	return array('year' => $next_year, 'month' => $next_month);
}
?>