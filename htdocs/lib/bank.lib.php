<?php
/* Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/lib/bank.lib.php
		\brief      Ensemble de fonctions de base pour le module banque
        \ingroup    banque
        \version    $Id$

		Ensemble de fonctions de base de dolibarr sous forme d'include
*/

function bank_prepare_head($obj)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();
	
	$head[$h][0] = DOL_URL_ROOT.'/compta/bank/fiche.php?id='.$obj->id;
	$head[$h][1] = $langs->trans("AccountCard");
	$head[$h][2] = 'bankname';
	$h++;

	if ($obj->type == 0 || $obj->type == 1)
	{
		$head[$h][0] = DOL_URL_ROOT.'/compta/bank/bankid_fr.php?id='.$obj->id;
		$head[$h][1] = $langs->trans("RIB");
		$head[$h][2] = 'bankid';
		$h++;
	}

    $head[$h][0] = DOL_URL_ROOT."/compta/bank/account.php?account=".$obj->id;
    $head[$h][1] = $langs->trans("Transactions");
    $head[$h][2] = 'journal';
    $h++;
    
//    if ($conf->global->MAIN_FEATURES_LEVEL >= 1)
//	{
		$head[$h][0] = DOL_URL_ROOT."/compta/bank/treso.php?account=".$obj->id;
		$head[$h][1] = $langs->trans("PlannedTransactions");
		$head[$h][2] = 'cash';
		$h++;
//	}
	
    $head[$h][0] = DOL_URL_ROOT."/compta/bank/annuel.php?account=".$obj->id;
    $head[$h][1] = $langs->trans("IOMonthlyReporting");
    $head[$h][2] = 'annual';
    $h++;

    $head[$h][0] = DOL_URL_ROOT."/compta/bank/graph.php?account=".$obj->id;
    $head[$h][1] = $langs->trans("Graph");
    $head[$h][2] = 'graph';
    $h++;
    
    if ($obj->courant != 2) 
    {
    	$head[$h][0] = DOL_URL_ROOT."/compta/bank/releve.php?account=".$obj->id;
	    $head[$h][1] = $langs->trans("AccountStatement");
	    $head[$h][2] = 'statement';
	    $h++;
	}
	
	return $head;
}


/**
 *		\brief    Check account number informations for a bank account
 *		\param    code_banque     code banque
 *		\param    code_guichet    code guichet
 *		\param    num_compte      numero de compte
 *		\param    cle             cle
 *		\param    iban            Ne sert pas pour le calcul de cle mais sert pour determiner le pays
 *		\return   int             true si les infos sont bonnes, false si erreur
 */
function checkBanForAccount($account)
{
	$country_code=$account->getCountryCode();
	
	// For compatibility between 	
	// account of type CompanyBankAccount class (we use number, cle_rib)
	// account of type Account class (we use num_compte, cle)
	if (empty($account->number)) $account->number=$account->num_compte;
	if (empty($account->cle))    $account->cle=$account->cle_rib;
	
	dol_syslog("Bank.lib::checkBanForAccount account->code_banque=".$account->code_banque." account->code_guichet=".$account->code_guichet." account->number=".$account->number." account->cle=".$account->cle." account->iban=".$account->iban." country_code=".$country_code, LOG_DEBUG);
	
	if ($country_code == 'FR')	// France rules
	{
		$coef = array(62, 34, 3) ;
		// Concatenation des differents codes.
		$rib = strtolower(trim($account->code_banque).trim($account->code_guichet).trim($account->number).trim($account->cle));
		// On remplace les eventuelles lettres par des chiffres.
		//$rib = strtr($rib, "abcdefghijklmnopqrstuvwxyz","12345678912345678912345678");	//Ne marche pas
		$rib = strtr($rib, "abcdefghijklmnopqrstuvwxyz","12345678912345678923456789");
		// Separation du rib en 3 groupes de 7 + 1 groupe de 2.
		// Multiplication de chaque groupe par les coef du tableau
		for ($i=0, $s=0; $i<3; $i++)
		{
			$code = substr($rib, 7 * $i, 7) ;
			$s += (0 + $code) * $coef[$i] ;
		}
		// Soustraction du modulo 97 de $s a 97 pour obtenir la cle
		$cle_rib = 97 - ($s % 97) ;
		if ($cle_rib == $account->cle)
		{
			return true;
		}
		return false;
	}
	
	if ($country_code == 'BE')	// Belgium rules
	{
	}

	if ($country_code == 'ES')	// Spanish rules
	{
		$CCC = strtolower(trim($account->number));

		$rib = strtolower(trim($account->code_banque).trim($account->code_guichet));
    	
    	$cle_rib=strtolower(CheckES($rib,$CCC));
    	
		if ($cle_rib == strtolower($account->cle))
    	{
    		return true;
		}   
		return false; 
    }	

	// No particular rule
	// If account is CompanyBankAccount class, we use number
	// If account is Account class, we use num_compte
	if (empty($account->num_compte) && empty($account->number))
	{
		return false;
	}

	return true;
}


/** 
 * 	Returns the key for Spanish Banks Accounts  
 *  @return		string		Key
 */ 
Function CheckES($IentOfi,$InumCta)
{
	$APesos = Array(1,2,4,8,5,10,9,7,3,6); // Array de "pesos"
	$DC1=0;
	$DC2=0;
	$x=8;
	while($x>0) {
		$digito=$IentOfi[$x-1];
		$DC1=$DC1+($APesos[$x+2-1]*($digito));
		$x = $x - 1;
	}
	$Resto = $DC1%11;
	$DC1=11-$Resto;
	if ($DC1==10) $DC1=1;
	if ($DC1==11) $DC1=0;              // Digito control Entidad-Oficina

	$x=10;
	while($x>0) {
		$digito=$InumCta[$x-1];
		$DC2=$DC2+($APesos[$x-1]*($digito));
		$x = $x - 1;
	}
	$Resto = $DC2%11;
	$DC2=11-$Resto;
	if ($DC2==10) $DC1=1;
	if ($DC2==11) $DC1=0;         // Digito Control C/C

	$DigControl=($DC1)."".($DC2);   // los 2 numeros del D.C.
	return $DigControl;
}


?>