<?PHP
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

require_once (DOL_DOCUMENT_ROOT."/facture.class.php");
require_once (DOL_DOCUMENT_ROOT."/societe.class.php");

class BonPrelevement
{
  var $db;

  var $date_echeance;
  var $raison_sociale;
  var $reference_remise;
  var $emetteur_code_guichet;
  var $emetteur_numero_compte;
  var $emetteur_code_etablissement;

  function BonPrelevement($DB, $filename) 
    {
      $error = 0;
      $this->db = $DB;

      $this->file = fopen ($filename,"w");
      
      $this->date_echeance = time();
      $this->raison_sociale = "";
      $this->reference_remise = "";

      $this->emetteur_code_guichet = "";
      $this->emetteur_numero_compte = "";
      $this->emetteur_code_etablissement = "";

      $this->factures = array();

      $this->numero_national_emetteur = "";

      return 1;
    }
  /*
   *
   *
   */
  function Generate()
  {
    $this->EnregEmetteur();

    $nbfactures = sizeof($this->factures);

    $total = 0;

    for ($i = 0 ; $i < $nbfactures ; $i++)
      {
	$fac = new Facture($this->db);
	$fac->fetch($this->factures[$i]);
	$fac->fetch_client();

	$fac->client->rib(); // Set client->bank_account


	if ($fac->client->bank_account->verif())
	  {
	    $total = $total + $fac->total_ttc;

	    $this->EnregDestinataire($fac);
	  }
	else
	  {
	    print $fac->client->bank_account->error_message;
	    print $fac->client->nom;
	  }
      }

    $this->EnregTotal($total);

    fclose($this->file);
  }


  /*
   *
   *
   */

  function EnregEmetteur()
  {
    fputs ($this->file, "03");
    fputs ($this->file, "08"); // Prélèvement ordinaire

    fputs ($this->file, "        "); // Zone Réservée B2

    fputs ($this->file, $this->numero_national_emetteur); // Numéro National d'emmetteur B3

    // Date d'échéance C1

    fputs ($this->file, "       "); 
    fputs ($this->file, strftime("%d%m", $this->date_echeance));
    fputs ($this->file, substr(strftime("%y", $this->date_echeance),1));
    
    // Raison Sociale C2

    fputs ($this->file, substr($this->raison_sociale. "                           ",0,24));

    // Reference de la remise créancier D1 sur 7 caractéres

    fputs ($this->file, substr($this->reference_remise. "                           ",0,7));

    // Zone Réservée D1-2
 
    fputs ($this->file, substr("                                    ",0,17));

    // Zone Réservée D2

    fputs ($this->file, substr("                             ",0,2));
    fputs ($this->file, "E");
    fputs ($this->file, substr("                             ",0,5));
    
    // Code Guichet  D3

    fputs ($this->file, $this->emetteur_code_guichet);

    // Numero de compte D4

    fputs ($this->file, substr("000000000000000".$this->emetteur_numero_compte, -11));

    // Zone Réservée E
 
    fputs ($this->file, substr("                                        ",0,16));

    // Zone Réservée F
 
    fputs ($this->file, substr("                                        ",0,31));

    // Code établissement

    fputs ($this->file, $this->emetteur_code_etablissement);

    // Zone Réservée G
 
    fputs ($this->file, substr("                                        ",0,5));

    fputs ($this->file, "\n");

  }

  /*
   * Enregistrements destinataires
   *
   *
   */


  function EnregDestinataire($fac)
  {
    fputs ($this->file, "06");
    fputs ($this->file, "08"); // Prélèvement ordinaire

    fputs ($this->file, "        "); // Zone Réservée B2

    fputs ($this->file, $this->numero_national_emetteur); // Numéro National d'emmetteur B3

    // Date d'échéance C1

    fputs ($this->file, "       "); 
    fputs ($this->file, strftime("%d%m", $this->date_echeance));
    fputs ($this->file, substr(strftime("%y", $this->date_echeance),1));
    
    // Raison Sociale Destinataire C2

    fputs ($this->file, substr($fac->client->nom. "                           ",0,24));

    // Reference de la remise créancier D1

    fputs ($this->file, substr("                                    ",0,24));

    // Zone Réservée D2

    fputs ($this->file, substr("                             ",0,8));
    
    // Code Guichet  D3

    fputs ($this->file, $fac->client->bank_account->code_guichet);

    // Numero de compte D4

    fputs ($this->file, substr("000000000000000".$fac->client->bank_account->number, -11));

    // Zone E Montant
 
    $montant = (round($fac->total_ttc,2) * 100);

    fputs ($this->file, substr("000000000000000".$montant, -16));

    // Libellé F
 
    fputs ($this->file, substr("*".$fac->ref."                                   ",0,13));
    fputs ($this->file, substr("                                        ",0,18));

    // Code établissement G1

    fputs ($this->file, $fac->client->bank_account->code_banque);

    // Zone Réservée G2
 
    fputs ($this->file, substr("                                        ",0,5));

    fputs ($this->file, "\n");
  }



  function EnregTotal($total)
  {
    fputs ($this->file, "08");
    fputs ($this->file, "08"); // Prélèvement ordinaire

    fputs ($this->file, "        "); // Zone Réservée B2

    fputs ($this->file, $this->numero_national_emetteur); // Numéro National d'emmetteur B3

    // Réservé C1

    fputs ($this->file, substr("                           ",0,12));

    
    // Raison Sociale C2

    fputs ($this->file, substr("                           ",0,24));

    // D1

    fputs ($this->file, substr("                                    ",0,24));

    // Zone Réservée D2

    fputs ($this->file, substr("                             ",0,8));
    
    // Code Guichet  D3

    fputs ($this->file, substr("                             ",0,5));

    // Numero de compte D4

    fputs ($this->file, substr("                             ",0,11));
    
    // Zone E Montant
 
    $montant = ($total * 100);

    fputs ($this->file, substr("000000000000000".$montant, -16));

    // Zone Réservée F
 
    fputs ($this->file, substr("                                        ",0,31));

    // Code établissement

    fputs ($this->file, substr("                                        ",0,5));

    // Zone Réservée F
 
    fputs ($this->file, substr("                                        ",0,5));

    fputs ($this->file, "\n");
  }
}
?>
