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

  function Export($linec)
  {
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
    $n = sizeof($linec);

    $oldfacture = 0;

    for ( $i = 0 ; $i < $n ; $i++)
      {
	if ( $oldfacture <> $linec[$i][1])
	  {
	    // Ligne client
	    fputs($fp, strftime("%d%m%y",$linec[$i][0]) . "\t");
	    fputs($fp, "VE" ."\t");
	    fputs($fp, "\t");
	    fputs($fp, '411000000' ."\t");
	    fputs($fp, $linec[$i][3]." Facture" ."\t");
	    fputs($fp, $linec[$i][5] . "\t"); // Numéro de facture
	    fputs($fp, ereg_replace(",",".",$linec[$i][7]) ."\t"); // Montant total TTC de la facture
	    fputs($fp, 'D' . "\t"); // D pour débit
	    fputs($fp, strftime("%d%m%y",$linec[$i][0]) . "\t"); // Date d'échéance
	    fputs($fp, "EUR"); // Monnaie
	    fputs($fp, "\n");

	    // Ligne TVA
	    fputs($fp, strftime("%d%m%y",$linec[$i][0]) . "\t");
	    fputs($fp, "VE" ."\t");
	    fputs($fp, "\t");
	    fputs($fp, '4457119' ."\t");
	    fputs($fp, $linec[$i][3]." Facture" ."\t");
	    fputs($fp, $linec[$i][5] . "\t");             // Numéro de facture
	    fputs($fp, ereg_replace(",",".",$linec[$i][6]) ."\t");              // Montant de TVA
	    fputs($fp, 'C' . "\t");                       // C pour crédit
	    fputs($fp, strftime("%d%m%y",$linec[$i][0]) . "\t"); // Date d'échéance
	    fputs($fp, "EUR"); // Monnaie
	    fputs($fp, "\n");
	    
	    $oldfacture = $linec[$i][1];
	    $j++;
	  }

	fputs($fp, strftime("%d%m%y",$linec[$i][0]) ."\t");
	fputs($fp, 'VE' ."\t");
	fputs($fp, $linec[$i][4]."\t"); // Code Comptable
	fputs($fp, "\t");
	fputs($fp, $linec[$i][3]." Facture" ."\t");
	fputs($fp, $linec[$i][5]."\t");                  // Numéro de facture
	fputs($fp, ereg_replace(",",".",round($linec[$i][8], 2)) ."\t");            // Montant de la ligne
	fputs($fp, 'C' . "\t");                     // C pour crédit
	fputs($fp, strftime("%d%m%y",$linec[$i][0]) . "\t"); // Date d'échéance
	fputs($fp, "EUR"); // Monnaie
	fputs($fp, "\n");



	$j++;

      }

    fclose($fp);
    
    return 0;
  }
}
