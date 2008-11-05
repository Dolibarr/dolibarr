<?php
/* Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
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
 *  \file		htdocs/lib/date.lib.php
 *  \brief		Ensemble de fonctions de base de dolibarr sous forme d'include
 *  \version		$Id$
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


/**	  \brief      Return, in clear text, value of a number of seconds in days, hours and minutes
 *    \param      iSecond     Number of seconds
 *    \param      format      Output format (all:affichage complet, hour: n'affiche que les heures, min: n'affiche que les minutes)
 *    \return     sTime       Formated text of duration
 */
function ConvertSecondToTime($iSecond,$format='all')
{
	global $langs;

	if ($format == 'all')
	{
		if ($iSecond > 86400)
		{
			$sDay=date("d",$iSecond)-1;
			$dayTranslate = $langs->trans("Day");
			if ($iSecond >= 172800) $dayTranslate = $langs->trans("Days");
		}
		$sTime='';
		if ($sDay) $sTime.=$sDay.' '.$dayTranslate.' ';
		$sTime.=date("H",$iSecond)-1;
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
 *	\return		array	Next year,month
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


/**
 *	\brief     	Fonction retournant le nombre de jour fieries samedis et dimanches entre 2 dates entrees en timestamp
 *	\remarks	Called by function num_open_day
 *	\param	    timestampStart      Timestamp de debut
 *	\param	    timestampEnd        Timestamp de fin
 *	\return   	nbFerie             Nombre de jours feries
 */
function num_public_holiday($timestampStart, $timestampEnd, $countrycode='FR')
{
	$nbFerie = 0;

	while ($timestampStart != $timestampEnd)
	{
		$ferie=false;
		$countryfound=0;

		$jour  = date("d", $timestampStart);
		$mois  = date("m", $timestampStart);
		$annee = date("Y", $timestampStart);

		if ($countrycode == 'FR')
		{
			$countryfound=1;

			// Definition des dates feriees fixes
			if($jour == 1 && $mois == 1)   $ferie=true; // 1er janvier
			if($jour == 1 && $mois == 5)   $ferie=true; // 1er mai
			if($jour == 8 && $mois == 5)   $ferie=true; // 5 mai
			if($jour == 14 && $mois == 7)  $ferie=true; // 14 juillet
			if($jour == 15 && $mois == 8)  $ferie=true; // 15 aout
			if($jour == 1 && $mois == 11)  $ferie=true; // 1 novembre
			if($jour == 11 && $mois == 11) $ferie=true; // 11 novembre
			if($jour == 25 && $mois == 12) $ferie=true; // 25 decembre

			// Calcul du jour de paques
			$date_paques = easter_date($annee);
			$jour_paques = date("d", $date_paques);
			$mois_paques = date("m", $date_paques);
			if($jour_paques == $jour && $mois_paques == $mois) $ferie=true;
			// Paques

			// Calcul du jour de l ascension (38 jours apres Paques)
			$date_ascension = mktime(date("H", $date_paques),
			date("i", $date_paques),
			date("s", $date_paques),
			date("m", $date_paques),
			date("d", $date_paques) + 38,
			date("Y", $date_paques)
			);
			$jour_ascension = date("d", $date_ascension);
			$mois_ascension = date("m", $date_ascension);
			if($jour_ascension == $jour && $mois_ascension == $mois) $ferie=true;
			//Ascension

			// Calcul de Pentecote (11 jours apres Paques)
			$date_pentecote = mktime(date("H", $date_ascension),
			date("i", $date_ascension),
			date("s", $date_ascension),
			date("m", $date_ascension),
			date("d", $date_ascension) + 11,
			date("Y", $date_ascension)
			);
			$jour_pentecote = date("d", $date_pentecote);
			$mois_pentecote = date("m", $date_pentecote);
			if($jour_pentecote == $jour && $mois_pentecote == $mois) $ferie=true;
			//Pentecote

			// Calul des samedis et dimanches
			$jour_julien = unixtojd($timestampStart);
			$jour_semaine = jddayofweek($jour_julien, 0);
			if($jour_semaine == 0 || $jour_semaine == 6) $ferie=true;
			//Samedi (6) et dimanche (0)
		}

		// Mettre ici cas des autres pays


		// Cas pays non defini
		if (! $countryfound)
		{
			// Calul des samedis et dimanches
			$jour_julien = unixtojd($timestampStart);
			$jour_semaine = jddayofweek($jour_julien, 0);
			if($jour_semaine == 0 || $jour_semaine == 6) $ferie=true;
			//Samedi (6) et dimanche (0)
		}

		// On incremente compteur
		if ($ferie) $nbFerie++;

		// Incrementation du nombre de jour (on avance dans la boucle)
		$jour++;
		$timestampStart=mktime(0,0,0,$mois,$jour,$annee);
	}

	return $nbFerie;
}

/**
 *	\brief     Fonction retournant le nombre de jour entre deux dates
 *	\param	   timestampStart      Timestamp de debut
 *	\param	   timestampEnd        Timestamp de fin
 *	\param     lastday             On prend en compte le dernier jour, 0: non, 1:oui
 *	\return    nbjours             Nombre de jours
 */
function num_between_day($timestampStart, $timestampEnd, $lastday=0)
{
	if ($timestampStart < $timestampEnd)
	{
		if ($lastday == 1)
		{
			$bit = 0;
		}
		else
		{
			$bit = 1;
		}
		$nbjours = round(($timestampEnd - $timestampStart)/(60*60*24)-$bit);
	}
	return $nbjours;
}

/**
 *	\brief     Fonction retournant le nombre de jour entre deux dates sans les jours f�ri�s (jours ouvr�s)
 *	\param	   timestampStart      Timestamp de debut
 *	\param	   timestampEnd        Timestamp de fin
 *	\param     inhour              0: sort le nombre de jour , 1: sort le nombre d'heure (72 max)
 *	\param     lastday             On prend en compte le dernier jour, 0: non, 1:oui
 *	\return    nbjours             Nombre de jours ou d'heures
 */
function num_open_day($timestampStart, $timestampEnd,$inhour=0,$lastday=0)
{
	global $langs;

	if ($timestampStart < $timestampEnd)
	{
		$bit = 0;
		if ($lastday == 1) $bit = 1;
		$nbOpenDay = num_between_day($timestampStart, $timestampEnd, $bit) - num_public_holiday($timestampStart, $timestampEnd);
		$nbOpenDay.= " ".$langs->trans("Days");
		if ($inhour == 1 && $nbOpenDay <= 3) $nbOpenDay = $nbOpenDay*24 . $langs->trans("HourShort");
		return $nbOpenDay;
	}
	else
	{
		return $langs->trans("Error");
	}
}

?>