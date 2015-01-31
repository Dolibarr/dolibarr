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
 * @param	Lang	$lang			Language
 * @return 	string  $entexto        Text of the number
 */
function dolNumberToWord($numero, $langs, $numorcurrency='number')
{
	$entexto=$numero;

	if ($langs->default == 'es_MX' && $numorcurrency == 'currency')
	{
	    $veintis = array("VEINTE","VEINTIUN","VEINTID&OacuteS","VEINTITR&EacuteS","VEINTICUATRO","VEINTICINCO","VEINTIS&EacuteIS","VEINTISIETE","VEINTIOCHO","VEINTINUEVE");
	    $unidades = array("UN","DOS","TRES","CUATRO","CINCO","SEIS","SIETE","OCHO","NUEVE");
	    $decenas = array("","","TREINTA ","CUARENTA ","CINCUENTA ","SESENTA ","SETENTA ","OCHENTA ","NOVENTA ");
	    $centenas = array("CIENTO","DOSCIENTOS","TRESCIENTOS","CUATROCIENTOS","QUINIENTOS","SEISCIENTOS","SETECIENTOS","OCHOCIENTOS","NOVECIENTOS");
	    $number = $numero;
	    $parte_decimal = $numero - (int) $numero;
	    $parte_decimal = (int) round($parte_decimal*100);
	    if ($parte_decimal < 10)
		$parte_decimal = "0".$parte_decimal;
	    $entexto ="";
	    if ($numero>=1 && $numero<2) {
		$entexto .= " UN PESO ".$parte_decimal." / 100 M.N.";
	    }
	    elseif ($numero>=0 && $numero<1){
		$entexto .= " CERO PESOS ".$parte_decimal." / 100 M.N.";
	    }
	    elseif ($numero>=100 && $numero<101){
		$entexto .= " CIEN PESOS ".$parte_decimal." / 100 M.N.";
	    }
	    else {
		$cdm = (int) ($numero / 100000);
		$numero = $numero - $cdm * 100000;
		$ddm = (int) ($numero / 10000);
		$numero = $numero - $ddm * 10000;
		$udm = (int) ($numero / 1000);
		$numero = $numero - $udm * 1000;
		$c = (int) ($numero / 100);
		$numero = $numero - $c * 100;
		$d = (int) ($numero / 10);
		$u = (int) $numero - $d * 10;
		$completo=FALSE;
		if ($cdm==1 && $ddm==0 && $udm==0){
	            $entexto .= "CIEN";
	            $completo = TRUE;
		}
		if ($cdm!=0 && !$completo){
	            $entexto .= $centenas[$cdm-1]." ";
		}
		$completo=FALSE;
	        if ($ddm>2){
	            $entexto .= " ".$decenas[$ddm-1];
	            if ($udm!=0){
			$entexto .= " Y ";
	            }
		}
		elseif ($ddm!=0){
	            $completo=TRUE;
	            if ($ddm==1){
			$entexto .= " ".$diecis[$udm];
	            }
	            else{
			$entexto .= " ".$veintis[$udm];
	            }
		}
		if ($udm!=0 && !$completo){
	            $entexto .= $unidades[$udm-1];
		}
		$completo=FALSE;
		if ($number>=1000){
	            $entexto .= " MIL ";
		}

	        if ($c==1 && $d==0 && $u==0){
	            $entexto .= "CIEN";
	            $completo = TRUE;
		}
		if ($c!=0 && !$completo){
	            $entexto .= $centenas[$c-1]." ";
		}
	        if ($d>2){
	            $entexto .= " ".$decenas[$d-1];
	            if ($u!=0){
			$entexto .= " Y ";
	            }
	        }
		elseif ($d!=0){
	            $completo=TRUE;
	            if ($d==1){
			$entexto .= " ".$diecis[$u];
	            }
	            else{
			$entexto .= " ".$veintis[$u];
	            }
		}
		if ($u!=0 && !$completo){
	            $entexto .= $unidades[$u-1];
		}
		$entexto .= " PESOS ".$parte_decimal." / 100 M.N.";
	    }
	}

    return $entexto;
}
