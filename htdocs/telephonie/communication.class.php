<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class CommunicationTelephonique {

  var $index;
  var $ligne;
  var $date;
  var $duree;
  var $dest;
  var $numero;
  var $montant;


  function CommunicationTelephonique()
  {
    return 1;
  }



  function loghtml($file)
  {
    $this->file_details = $file;

    
    $this->_log( '<tr>');
    $this->_log( "<td>$this->index");
    $this->_log( "<td>$this->ligne");
    $this->_log( "<td>".$this->dest);
    $this->_log( "<td>".$this->duree);
    $this->_log( "<td>".$this->montant);
    $this->_log( "<td>".$cout_calcul);

    if (round($cout_calcul,3) <> $objp->montant)
      {
	_log($file_details, "<td bgcolor=pink>".round($cout_calcul,3));
	$err++;
      }
    else
      {
	_log($file_details, "<td>".round($cout_calcul,3));
      }
    _log($file_details, "<td>$err");
    
  }


  function logxls()
  {

    /*
      $worksheet->write_string($i, 0,  "$objp->ligne");
      $worksheet->write($i, 1,  "$objp->client");
      $worksheet->write($i, 2,  "$objp->num");
      $worksheet->write($i, 3,  $objp->dest);
      $worksheet->write($i, 4,  $objp->duree);
      $worksheet->write($i, 5,  $objp->montant);
            
      $worksheet->write($i, 7,  $cout_temp_achat, $num1_format);
      $worksheet->write($i, 8,  $cout_temp_vente, $num1_format);
      $worksheet->write($i, 9,  $cout_fixe_achat, $num1_format);
      $worksheet->write($i, 10, $cout_fixe_vente, $num1_format);
      
      $j = $i+1;
      
      $worksheet->write($i, 11, "=(E$j * H$j / 60) + K$j ", $num1_format);
    */
  }

  function cout($tarif_achat, $tarif_vente, $ligne)
  {
    $error = 0;

    if (substr($this->numero,0,2) == '00') /* International */
      {
	$nbinter++;
	$duree_international += $objp->duree;
	
	$num = $this->numero;

	$this->remise = 0;
      }
    
    elseif (substr($this->numero,0,2) == '06') /* Telephones Mobiles */
      {
	$dureemob += $objp->duree;
	$nbmob++;
	
	$num = "0033".substr($this->numero,1);

	$this->remise = $ligne->remise;
      }
    elseif (substr($this->numero,0,4) == substr($objp->client,0,4) ) /* Tarif Local */
      {
	$dureenat += $objp->duree;
	$nbnat++;
	
	$num = "0033999".substr($this->numero, 1);

	$this->remise = $ligne->remise;
      }
    else
      {
	$dureenat += $objp->duree;
	$nbnat++;
	
	$num = "0033".substr($this->numero, 1);

	$this->remise = $ligne->remise;
      }	  
    
    
    if (! $tarif_achat->cout($num, $this->cout_temp_achat, $this->cout_fixe_achat, $tarif_libelle_achat))
      {
	print "3- Tarif achat manquant pour $num\n";
	dolibarr_syslog("CommunicationTelephonique::Cout Tarif achat manquant pour $num");
	$error++;
      }
    
    if (! $tarif_vente->cout($num, $this->cout_temp_vente, $this->cout_fixe_vente, $this->tarif_libelle_vente))
      {
	print "3- Tarif vente manquant pour $num\n";
	dolibarr_syslog("CommunicationTelephonique::Cout Tarif vente manquant pour $num");
	$error++;
      }
    else
      {

      }

    $this->cout_achat = ( ($this->duree * $this->cout_temp_achat / 60) + $this->cout_fixe_achat);
    
    if ($ligne->facturable == 1)
      {
	$this->cout_vente = ( ($this->duree * $this->cout_temp_vente / 60));

	$this->cout_vente = ( $this->cout_vente * ( 1 - ($this->remise / 100)));

	$this->cout_vente = $this->cout_vente + $this->cout_fixe_vente;

      }
    else
      {
	$this->cout_vente = 0;
      }
    
    return $error;
  }

  /*
   * Enregistre la ligne de communications dans llx_telephonie_communications_details
   *
   *
   */

  function logsql($db)
  {

    $this->cout_achat = ereg_replace(",",".", $this->cout_achat);
    $this->cout_vente = ereg_replace(",",".", $this->cout_vente);
    $this->remise     = ereg_replace(",",".", $this->remise);
    $this->montant     = ereg_replace(",",".", $this->montant);

    $this->dateheure = mktime(substr($this->heure, 0,2),
			      substr($this->heure, 3,2),
			      substr($this->heure, 6,2),
			      substr($this->date, 3,2),
			      substr($this->date, 0,2),
			      substr($this->date, 6,4));


    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_communications_details";
    $sql .= " (ligne, date, numero, duree";
    $sql .= ", tarif_achat_temp, tarif_achat_fixe, tarif_vente_temp, tarif_vente_fixe";
    $sql .= ", cout_achat, cout_vente, remise,dest, fourn_montant";
    $sql .= " , fichier_cdr, fk_fournisseur, fk_facture)";

    $sql .= " VALUES (";
    $sql .= "'$this->ligne','".$db->idate($this->dateheure)."','$this->numero','$this->duree'";

    $sql .= ", '$this->cout_temp_achat','$this->cout_fixe_achat','$this->cout_temp_vente','$this->cout_fixe_vente'";
    $sql .= ", '$this->cout_achat','$this->cout_vente', '$this->remise'";
    $sql .= ",'".addslashes($this->tarif_libelle_vente)."','$this->montant'";
    $sql .= ",'".$this->fichier_cdr."','".$this->fournisseur."'";
    $sql .= ",'".$this->facture_id."')";

    if (! $db->query($sql))
      {
	dolibarr_syslog("CommunicationTelephonique::logsql Erreur");
	return 1;
      }
    else
      {
	return 0;
      }
  }



  function _log( $text)
  {
    if ($this->file_details)
      {
	fputs($this->file_details, $text);
      }
  }

}

?>
