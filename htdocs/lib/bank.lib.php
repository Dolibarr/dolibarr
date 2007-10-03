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
 *
 * $Id$
 * $Source$
 */

/**
	    \file       htdocs/lib/bank.lib.php
		\brief      Ensemble de fonctions de base pour le module banque
        \ingroup    banque
        \version    $Revision$

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
    
    $head[$h][0] = DOL_URL_ROOT."/compta/bank/annuel.php?account=".$obj->id;
    $head[$h][1] = $langs->trans("IOMonthlyReporting");
    $head[$h][2] = 'annual';
    $h++;

    $head[$h][0] = DOL_URL_ROOT."/compta/bank/graph.php?account=".$obj->id;
    $head[$h][1] = $langs->trans("Graph");
    $head[$h][2] = 'graph';
    $h++;
    
    if ($conf->global->MAIN_FEATURES_LEVEL >= 1)
	{
		$head[$h][0] = DOL_URL_ROOT."/compta/bank/treso.php?account=".$obj->id;
		$head[$h][1] = $langs->trans("CashBudget");
		$head[$h][2] = 'cash';
		$h++;
	}
	
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
		\brief    Verifie le RIB d'un compte bancaire grace à sa clé
		\param    code_banque     code banque
		\param    code_guichet    code guichet
		\param    num_compte      numero de compte
		\param    cle             cle
		\param    iban            Ne sert pas pour le calcul de cle mais sert pour determiner le pays
		\return   int             true si les infos sont bonnes, false si la clé ne correspond pas
*/
function verif_rib($code_banque , $code_guichet , $num_compte , $cle, $iban)
{
	if (eregi("^FR",$iban))
	{    // Cas de la France

		$coef = array(62, 34, 3) ;

		// Concatenation des differents codes.
		$rib = strtolower(trim($code_banque).trim($code_guichet).trim($num_compte).trim($cle));

		// On remplace les eventuelles lettres par des chiffres.

		//Ne marche pas
		//$rib = strtr($rib, "abcdefghijklmnopqrstuvwxyz","12345678912345678912345678");

		$rib = strtr($rib, "abcdefghijklmnopqrstuvwxyz","12345678912345678923456789");

		// Separation du rib en 3 groupes de 7 + 1 groupe de 2.
		// Multiplication de chaque groupe par les coef du tableau
		for ($i=0, $s=0; $i<3; $i++)
		{
			$code = substr($rib, 7 * $i, 7) ;
			$s += (0 + $code) * $coef[$i] ;
		}

		// Soustraction du modulo 97 de $s à 97 pour obtenir la clé RIB
		$cle_rib = 97 - ($s % 97) ;

		if ($cle_rib == $cle)
		{
			return true;
		}

		return false;
	}

	return true;
}

?>