<?php
/* Copyright (C) 2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015 Víctor Ortiz Pérez   <victor@accett.com.mx>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */
/**
 *	\file			htdocs/core/lib/functionsnumbertoword.lib.php
 *	\brief			A set of functions for Dolibarr
 *					This file contains all frequently used functions.
 */
/**
 * Function to return number or amount in text.
 *
 * @param	float 	$numero			Number to convert
 * @param	Lang	$langs			Language
 * @param	string	$numorcurrency	'number' or 'amount'
 * @return 	string  				Text of the number or -1 in case TOO LONG (more than 1000000000000.99)
 */
function dolNumberToWord($numero, $langs, $numorcurrency='number')
{
	// If the number is negative convert to positive and return -1 if is too long
	if ($numero < 0) $numero *= -1;
	if ($numero >= 1000000000001)
		return -1;
	// Get 2 decimals to cents, another functions round or truncate
	$strnumber = number_format ($numero,10);
	$len=strlen($strnumber);
	for ($i=0; $i<$len; $i++)
	{
		if ($strnumber[$i]=='.') {
			$parte_decimal = $strnumber[$i+1].$strnumber[$i+2];
			break;
		}
	}
	/*In dolibarr 3.6.2 (my current version) doesn't have $langs->default and
	in case exist why ask $lang like a parameter?*/
	if (((is_object($langs) && $langs->default == 'es_MX') || (! is_object($langs) && $langs == 'es_MX')) && $numorcurrency == 'currency')
	{	
		if ($numero>=1 && $numero<2) {
			return ("UN PESO ".$parte_decimal." / 100 M.N.");
		}
		elseif ($numero>=0 && $numero<1){
			return ("CERO PESOS ".$parte_decimal." / 100 M.N.");
		}
		elseif ($numero>=1000000 && $numero<1000001){
			return ("UN MILL&OacuteN DE PESOS ".$parte_decimal." / 100 M.N.");
		}
		elseif ($numero>=1000000000000 && $numero<1000000000001){
			return ("UN BILL&OacuteN DE PESOS ".$parte_decimal." / 100 M.N.");
		}
		else {
			$entexto ="";
			$number = $numero;
			if ($number >= 1000000000){
				$CdMMillon = (int) ($numero / 100000000000);
				$numero = $numero - $CdMMillon * 100000000000;
				$DdMMillon = (int) ($numero / 10000000000);
				$numero = $numero - $DdMMillon * 10000000000;
				$UdMMillon = (int) ($numero / 1000000000);
				$numero = $numero - $UdMMillon * 1000000000;
				$entexto .= hundreds2text ($CdMMillon, $DdMMillon, $UdMMillon);
				$entexto .= " MIL ";
			}
			if ($number >= 1000000){
				$CdMILLON = (int) ($numero / 100000000);
				$numero = $numero - $CdMILLON * 100000000;
				$DdMILLON = (int) ($numero / 10000000);
				$numero = $numero - $DdMILLON * 10000000;
				$udMILLON = (int) ($numero / 1000000);
				$numero = $numero - $udMILLON * 1000000;
				$entexto .= hundreds2text ($CdMILLON, $DdMILLON, $udMILLON);
				if (!$CdMMillon && !$DdMMillon && !$UdMMillon && !$CdMILLON && !$DdMILLON && $udMILLON==1)
					$entexto .= " MILL&OacuteN ";
				else
					$entexto .= " MILLONES ";
			}
			if ($number >= 1000) {
				$cdm = (int) ($numero / 100000);
				$numero = $numero - $cdm * 100000;
				$ddm = (int) ($numero / 10000);
				$numero = $numero - $ddm * 10000;
				$udm = (int) ($numero / 1000);
				$numero = $numero - $udm * 1000;
				$entexto .= hundreds2text ($cdm, $ddm, $udm);
				if ($cdm || $ddm || $udm)
					$entexto .= " MIL ";
			}
			$c = (int) ($numero / 100);
			$numero = $numero - $c * 100;
			$d = (int) ($numero / 10);
			$u = (int) $numero - $d * 10;
			$entexto .= hundreds2text ($c, $d, $u);
			if (!$cdm && !$ddm && !$udm && !$c && !$d && !$u && $number>1000000)
				$entexto .= " DE";
			$entexto .= " PESOS ".$parte_decimal." / 100 M.N.";
		}
		return $entexto;
	}
}

/**
 * hundreds2text
 * 
 * @param integer $hundreds     Hundreds
 * @param integer $tens         Tens
 * @param integer $units        Units
 */
function hundreds2text($hundreds, $tens, $units)
{
	if ($hundreds==1 && $tens==0 && $units==0){
		return "CIEN";
	}
	$centenas = array("CIENTO","DOSCIENTOS","TRESCIENTOS","CUATROCIENTOS","QUINIENTOS","SEISCIENTOS","SETECIENTOS","OCHOCIENTOS","NOVECIENTOS");
	$decenas = array("","","TREINTA ","CUARENTA ","CINCUENTA ","SESENTA ","SETENTA ","OCHENTA ","NOVENTA ");
	$veintis = array("VEINTE","VEINTIUN","VEINTID&OacuteS","VEINTITR&EacuteS","VEINTICUATRO","VEINTICINCO","VEINTIS&EacuteIS","VEINTISIETE","VEINTIOCHO","VEINTINUEVE");
	$diecis = array("DIEZ","ONCE","DOCE","TRECE","CATORCE","QUINCE","DIECIS&EacuteIS","DIECISIETE","DIECIOCHO","DIECINUEVE");
    $unidades = array("UN","DOS","TRES","CUATRO","CINCO","SEIS","SIETE","OCHO","NUEVE");
	$entexto = "";
	if ($hundreds!=0){
		$entexto .= $centenas[$hundreds-1];
	}
	if ($tens>2){
		if ($hundreds!=0) $entexto .= " ";
		$entexto .= $decenas[$tens-1];
		if ($units!=0){
			$entexto .= " Y ";
			$entexto .= $unidades[$units-1];
		}
		return $entexto;
	}
	elseif ($tens==2){
		if ($hundreds!=0) $entexto .= " ";
		$entexto .= " ".$veintis[$units];
		return $entexto;
	}
	elseif ($tens==1){
		if ($hundreds!=0) $entexto .= " ";
		$entexto .= $diecis[$units];
		return $entexto;
	}
	if ($units!=0) {
		if ($hundreds!=0 || $tens!=0) $entexto .= " ";
		$entexto .= $unidades[$units-1];
	}
	return $entexto;
}
