<?php
/* Copyright (C) 2000,2001 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * $Id$
 * $Source$
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

/*!	\file htdocs/compta/bank/bank.lib.php
        \ingroup    banque
        \brief      librairie contenant les fonctions bancaires.
        \author     Laurent Destailleur
        \version    $Revision$
  
        Ensemble des fonctions en rapport avec les modules bancaires
*/



/*!
  \brief    Verifie le RIB d'un compte bancaire grace à sa clé
  \param    code_banque     code banque
  \param    code_guichet    code guichet
  \param    num_compte      numero de compte
  \param    cle             cle
  \param    iban            Ne sert pas pour le calcul de cle mais sert pour determiner le pays
  \return   int             true si les infos sont bonnes, false si la clé ne correspond pas
*/

function verif_rib($code_banque , $code_guichet , $num_compte , $cle, $iban) {
    if (eregi("^FR",$iban)) {    // Cas de la France
        $coef = array(62, 34, 3) ;
        // Concatenation des differents codes.
        $rib = strtolower($code_banque.$code_guichet.$num_compte.$cle);
        // On remplca les eventuelles lettres par des chiffres.
        $rib = strtr($rib, "abcdefghijklmnopqrstuvwxyz","12345678912345678912345678");
        
        // Separation du rib en 3 groupes de 7 + 1 groupe de 2.
        // Multiplication de chaque groupe par les coef du tableau
        for ($i=0, $s=0; $i<3; $i++) {
            $code = substr($rib, 7 * $i, 7) ;
            $s += (0 + $code) * $coef[$i] ;
        }
        
        // Soustraction du modulo 97 de $s à 97 pour obtenir la clé RIB
        $cle_rib = 97 - ($s % 97) ;
        if ($cle_rib == $cle) { return true; }
        return false;
    }

    return true;
}


?>
