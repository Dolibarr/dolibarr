<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

require_once DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_workbook.inc.php";
require_once DOL_DOCUMENT_ROOT."/includes/php_writeexcel/class.writeexcel_worksheet.inc.php";

class ComptaExportPoivre
{
  function ComptaExportPoivre ()
  {

  }

  function Create()
  {

    $this->date = time();

    $this->datef = "commande-".strftime("%d%b%y-%HH%M", $this->date);

    $fname = DOL_DATA_ROOT ."/telephonie/ligne/commande/".$this->datef.".xls";

    if (strlen(trim($this->fournisseur->email_commande)) == 0)
      {
	return -3;
      }

    if (file_exists($fname))
      {
	return 2;
      }
    else
      {
	$res = $this->CreateFile($fname);
	$res = $res + $this->LogSql();
	$res = $res + $this->MailFile($fname);

	return $res;
      }
  }

  /**
   * Agrégation des lignes de facture
   *
   *
   */

  function Agregate($line_in)
  {
    dolibarr_syslog("ComptaExportPoivre::Agregate");
    dolibarr_syslog("ComptaExportPoivre::Agregate " . sizeof($line_in) . " lignes en entrées");
    $i = 0;
    $j = 0;
    $n = sizeof($line_in);

    $oldfacture = $line_in[0][1];

    // On commence par la ligne 0

    $line_out[$j] = $line_in[$i];

    for ( $i = 1 ; $i < $n ; $i++)
      {
	// On agrège les lignes avec le même code comptable

	if ( $line_in[$i][1] == $line_in[$i-1][1] && $line_in[$i][4] == $line_in[$i-1][4])
	  {
	    $line_out[$j][8] = ($line_out[$j][8] + $line_in[$i][8]);
	  }
	else
	  {
	    $line_out[$j] = $line_in[$i];
	    $j++;
	  }
      }

    dolibarr_syslog("ComptaExportPoivre::Agregate " . sizeof($line_out) . " lignes en sorties");

    return $line_out;
  }

  function Export($linec)
  {

    dolibarr_syslog("ComptaExportPoivre::Export");
    dolibarr_syslog("ComptaExportPoivre::Export " . sizeof($linec) . " lignes en entrées");

    $lines = $this->Agregate($linec);
    
    $fname = "/tmp/exportcompta";

    $fp = fopen($fname,'w');

    // Pour les factures

    // Date Opération 040604 pour 4 juin 2004
    // VE -> ventilation
    // code Compte général
    // code client
    // Intitulé
    // Numéro de pièce
    // Montant
    // Type opération D pour Débit ou C pour Crédit
    // Date d'échéance, = à la date d'opération si pas d'échéance
    // EUR pour Monnaie en Euros
    
    // Pour les paiements

    $i = 0;
    $j = 0;
    $n = sizeof($lines);

    $oldfacture = 0;

    for ( $i = 0 ; $i < $n ; $i++)
      {
	if ( $oldfacture <> $lines[$i][1])
	  {
	    // Ligne client
	    fputs($fp, strftime("%d%m%y",$lines[$i][0]) . "\t");
	    fputs($fp, "VE" ."\t");
	    fputs($fp, "\t");
	    fputs($fp, '411000000' ."\t");
	    fputs($fp, $lines[$i][3]." Facture" ."\t");
	    fputs($fp, $lines[$i][5] . "\t"); // Numéro de facture
	    fputs($fp, ereg_replace(",",".",$lines[$i][7]) ."\t"); // Montant total TTC de la facture
	    fputs($fp, 'D' . "\t"); // D pour débit
	    fputs($fp, strftime("%d%m%y",$lines[$i][0]) . "\t"); // Date d'échéance
	    fputs($fp, "EUR"); // Monnaie
	    fputs($fp, "\n");

	    // Ligne TVA
	    fputs($fp, strftime("%d%m%y",$lines[$i][0]) . "\t");
	    fputs($fp, "VE" ."\t");
	    fputs($fp, "\t");
	    fputs($fp, '4457119' ."\t");
	    fputs($fp, $lines[$i][3]." Facture" ."\t");
	    fputs($fp, $lines[$i][5] . "\t");             // Numéro de facture
	    fputs($fp, ereg_replace(",",".",$lines[$i][6]) ."\t");              // Montant de TVA
	    fputs($fp, 'C' . "\t");                       // C pour crédit
	    fputs($fp, strftime("%d%m%y",$lines[$i][0]) . "\t"); // Date d'échéance
	    fputs($fp, "EUR"); // Monnaie
	    fputs($fp, "\n");
	    
	    $oldfacture = $lines[$i][1];
	    $j++;
	  }

	fputs($fp, strftime("%d%m%y",$lines[$i][0]) ."\t");
	fputs($fp, 'VE' ."\t");
	fputs($fp, $lines[$i][4]."\t"); // Code Comptable
	fputs($fp, "\t");
	fputs($fp, $lines[$i][3]." Facture" ."\t");
	fputs($fp, $lines[$i][5]."\t");                  // Numéro de facture
	fputs($fp, ereg_replace(",",".",round($lines[$i][8], 2)) ."\t");            // Montant de la ligne
	fputs($fp, 'C' . "\t");                     // C pour crédit
	fputs($fp, strftime("%d%m%y",$lines[$i][0]) . "\t"); // Date d'échéance
	fputs($fp, "EUR"); // Monnaie
	fputs($fp, "\n");



	$j++;

      }

    fclose($fp);
    
    return 0;
  }
}
