<?PHP
/* Copyright (C) 2005-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
  var $messages;
  /**
   * Constructeur
   *
   */
  function CommunicationTelephonique()
  {
    $this->messages = array();
    return 1;
  }

  /**
   * Calcul le cout de la communication
   *
   */
  function cout($tarif_achat, $tarif_vente, $ligne, $_db)
  {
    $error = 0;
    /*
    if (substr($this->numero,0,2) == '00')
      {
	$nbinter++;
	$duree_international += $objp->duree;
	
	$num = $this->numero;

	$this->remise = 0;
      }
    elseif (substr($this->numero,0,2) == '06')
      {
	$dureemob += $objp->duree;
	$nbmob++;
	
	$num = "0033".substr($this->numero,1);

	$this->remise = $ligne->remise;
      }
    */
    /* Tarif Local */
    /*
    elseif (substr($this->numero,0,4) == substr($objp->client,0,4) )
      {
	$dureenat += $objp->duree;
	$nbnat++;	
	$num = "0033999".substr($this->numero, 1);
	$this->remise = $ligne->remise;
      }*/
      //else
      //{
	$dureenat += $objp->duree;
	$nbnat++;
	
	$num = "00".$this->numero;

	$this->remise = $ligne->remise;
	//}	  
    
    /*
     *
     *
     */    
    /* Numeros speciaux */
	/*
    if (substr($num,4,1) == 8)
      {
	$this->remise = 0;
	$this->cout_temp_vente = 0;
	$this->tarif_libelle_vente = "Numeros speciaux";
	$this->cout_fixe_vente = ereg_replace(",",".", $this->montant);
      }
    else
      {
	*/
	/* Fin Numeros speciaux */
	if ($tarif_achat->cout($num, $this->cout_temp_achat, $this->cout_fixe_achat, $tarif_libelle_achat) == 0)
	  {
	    dol_syslog("CommunicationTelephonique::Cout Tarif achat manquant pour $num");
	    array_push($this->messages, array('warning',"Tarif achat manquant pour le numero $this->numero"));
	    //$error++;
	  }
	
	if ($tarif_vente->cout($num, $this->cout_temp_vente, $this->cout_fixe_vente, $this->tarif_libelle_vente) == 0)
	  {
	    dol_syslog("CommunicationTelephonique::Cout Tarif vente manquant pour $num");
	    array_push($this->messages, array('error',"Tarif vente manquant pour le numero $this->numero"));
	    $error++;
	  }
	//}
    /* Specification VoIP */
    if ($ligne->techno == 'voip')
      {
	if (substr($num,4,1) < 6)
	  {
	    $lignedest = new LigneTel($_db);

	    if ($lignedest->fetch("0".substr($num, -9)) == 1)
	      {
		if ($lignedest->techno == 'voip' && ($ligne->client_comm_id == $lignedest->client_comm_id))
		  {
		    $this->remise = 0;
		    $this->cout_fixe_vente = 0;
		    $this->cout_temp_vente = 0;
		    $this->tarif_libelle_vente = "Appel Interne VoIP";
		  }
	      }
	  }
      }
    /* Fin VoIP */

    $this->cout_achat = ( ($this->duree * $this->cout_temp_achat / 60) + $this->cout_fixe_achat);
    
    if ($ligne->facturable == 1)
      {
	$this->cout_vente = ( ($this->duree * $this->cout_temp_vente / 60));

	$this->cout_vente = ( $this->cout_vente * ( 1 - ($this->remise / 100)));
	/* Ajouté round le 2/12/05 */
	$this->cout_vente = round(($this->cout_vente + $this->cout_fixe_vente), 3);
      }
    else
      {
	$this->cout_vente = 0;
      }
    
    return $error;
  }

  /*
   * Enregistre la ligne de communications dans 
   * llx_telephonie_communications_details
   *
   */

  function logsql($db)
  {

    $this->cout_achat = ereg_replace(",",".", $this->cout_achat);
    $this->cout_vente = ereg_replace(",",".", $this->cout_vente);
    $this->remise     = ereg_replace(",",".", $this->remise);
    $this->montant    = ereg_replace(",",".", $this->montant);

    $this->dateheure = mktime(substr($this->heure, 0,2),
			      substr($this->heure, 3,2),
			      substr($this->heure, 6,2),
			      substr($this->date, 3,2),
			      substr($this->date, 0,2),
			      substr($this->date, 6,4));


    $sql = "INSERT INTO ".MAIN_DB_PREFIX."telephonie_communications_details";
    $sql .= " (fk_ligne,ligne, date, numero, duree";
    $sql .= ", tarif_achat_temp, tarif_achat_fixe, tarif_vente_temp, tarif_vente_fixe";
    $sql .= ", cout_achat, cout_vente, remise,dest, fourn_montant";
    $sql .= " , fichier_cdr, fk_fournisseur, fk_telephonie_facture,ym)";

    $sql .= " VALUES (";
    $sql .=  $this->fk_ligne.",";
    $sql .= "'$this->ligne','".$db->idate($this->dateheure)."','$this->numero','$this->duree'";

    $sql .= ", '$this->cout_temp_achat','$this->cout_fixe_achat','$this->cout_temp_vente','$this->cout_fixe_vente'";
    $sql .= ", '$this->cout_achat','$this->cout_vente', '$this->remise'";
    $sql .= ",'".addslashes($this->tarif_libelle_vente)."','$this->montant'";
    $sql .= ",'".$this->fichier_cdr."','".$this->fournisseur."'";
    $sql .= ",'".$this->facture_id."','".strftime("%y%m",$this->dateheure)."')";

    if (! $db->query($sql))
      {
	dol_syslog("CommunicationTelephonique::logsql Erreur");
	dol_syslog("CommunicationTelephonique::logsql ".$db->error());
	return 1;
      }
    else
      {
	return 0;
      }
  }

  /*
   *
   */

  function _log( $text)
  {
    if ($this->file_details)
      {
	fputs($this->file_details, $text);
      }
  }

  /*
   *
   */

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
}
?>
