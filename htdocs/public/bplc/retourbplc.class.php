<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * Classe de gestion du retour RSTS du systeme de paiement en ligne
 * CyberPaiement (TM) de la Banque Populaire de Lorraine
 *
 * Certaine fonction de cette classe existe de base dans PHP4 mais ont �t�
 * r�-�crites ici pour le support de PHP3
 */

class Retourbplc
{
  var $db;

  var $ipclient;
  var $montant;
  var $num_compte;
  var $ref_commande;
  var $num_contrat;
  var $num_transaction;
  var $date_transaction;
  var $heure_transaction;
  var $num_autorisation;
  var $cle_acceptation;
  var $code_retour;

  var $ref_commande;
	
  /*
   *   Initialisation des valeurs par d�faut
   */
	 
  function Retourbplc($db) 
  {
    $this->db = $db;
  }
	
  /**
   * \brief  Insertion dans la base de donn�e de la transaction
   *
   */
	 
  function insertdb()
  {


    $sql = "INSERT INTO ".MAIN_DB_PREFIX."transaction_bplc";
    $sql .= " (ipclient, 
                   num_transaction, 
                   date_transaction, 
                   heure_transaction, 
                   num_autorisation, 
                   cle_acceptation, 
                   code_retour, 
                   ref_commande)";
    
    $sql .= " VALUES ('$this->ipclient',
                      '$this->num_transaction',
                      '$this->date_transaction',
                      '$this->heure_transaction',
                      '$this->num_autorisation',
                      '$this->cle_acceptation',
                      $this->code_retour,
                       $this->ref_commande)";

    $result = $this->db->query($sql);
    
    if ($result) 
      {
	return $this->db->last_insert_id(MAIN_DB_PREFIX."transaction_bplc");
      }
    else
      {
	print $this->db->error();
	print "<h2><br>$sql<br></h2>";
	return 0;
      }             
  }
	
  /**
   * \brief  Verification de la validit�e de la cl�
   *
   */
	 
  function check_key($key)
  {

    $A = $this->montant;
    $B = $this->num_contrat;
    $C = $this->num_transaction;
    $D = $this->ref_commande;
    $E = $this->num_compte;

    /*
     * Etape 1
     *
     */
    $A1 = $A . $E;
    $B1 = $B . $E;
    $C1 = $C . $E;
    $D1 = $D . $E;

    $map = range(0, 9);

    $L1= $this->cle_luhn($A1, $map);

    $L2= $this->cle_luhn($B1, $map);

    $L3= $this->cle_luhn($C1, $map);

    $L4= $this->cle_luhn($D1, $map);
    /*
     * Etape 2
     *
     */

    $N1 = $L1 . $L2 . $L3 . $L4;
    $N0 = $L1 + $L2 + $L3 + $L4;

    $C5 = $this->corres($N0);
    /*
     * Comparaison
     *
     */

    if ($key == $this->calcul_pos($N1,$N0, $C5))
      {
	return 1;
      }
    else 
      {
	return 0;
      }
  }
	
  /**
   * \brief  Table de correspondance de l'algorithme de Luhn
   *
   */
	
  function corres($value)
  {
    $map[0] = 0;

    for ($i = 65 ; $i < 91 ; $i++)
      {
	$map[$i-64] = chr($i);
      }

    for ($i = 0 ; $i < 10 ; $i++)
      {
	$map[27+$i] = $i;
      }

    return $map[$value];

  }
	
  /**
   * \brief  Calcul de la cle de Luhn
   * 
   */
  function cle_luhn($cle, $map)
  {
    $buffer = $this->array_reverse($cle);
 
    $totalVal = 0;
    $flip = 1;
 
    reset ($buffer);

    while (list($key, $posVal) = each ($buffer))
      {

	if (!isset($map[$posVal])){
	  return FALSE;
	}

	$posVal = $map[$posVal];

	if ( $flip = !$flip)
	  {
	    $posVal *= 2;
	  }
      
	while ($posVal>0)
	  {
	    $totalVal += $posVal % 10;
	    $posVal = floor($posVal / 10);
	  }
    }

    return substr($totalVal, strlen($totalVal)-1, 1);
  }
  /**
   * \brief Postion de C5 dans N0
   *
   *
   */
	 
  function calcul_pos($N1, $N0, $C5)
  {
    if ($N0 >= 0 && $N0 <= 6)
      {
	/* cl� = 2 premiers de N0 . C5 . 2 derniers de N0 */

	$cle = substr($N1,0,2) . $C5 . substr($N1,2,2);

      }
    elseif ($N0 >= 7 && $N0 <= 14)
      {
	/* cl� = 4 premiers de N0 . C5 */

	$cle = substr($N1,0,4) . $C5;

      }
    elseif ($N0 >= 15 && $N0 <= 21)
      {
	/* cl� = premier de N1 . C5 . 3 derniers de N1 */

	$cle = substr($N1,0,1) . $C5 . substr($N1,1,3);

      }
    elseif ($N0 >= 22 && $N0 <= 29)
      {
	/* cl� = C5 . 4 derniers de N1 */

	$cle = $C5 . substr($N1,0,4);

      }
    elseif ($N0 >= 30 && $N0 <= 36)
      {
	/* cl� = 3 premiers de N1 . C5 . dernier de N1 */

	$cle = substr($N1,0,3) . $C5 . substr($N1,1,1);

      }
    else
      {
	$cle = "ERREUR";
      }

    return $cle;

  }
	
  /**
   * \brief  Retournement du tableau
   *
   */
	 
  function array_reverse($string)
  {

    $len = strlen($string);

    $i = $len;
    $j = 0;
    $rever = array();
    while ($i > 0)
      {
	$rever[$j]= substr($string, $i-1, 1);
	$i = $i - 1;
	$j = $j + 1;
      }

    return $rever;
  }
}
?>
