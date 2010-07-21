<?PHP
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

/**
   \file       htdocs/compta/export/modules/compta.export.safran.class.php
   \ingroup    compta
   \brief      Modele d'export compta safran, export au format tableur
   \remarks    Ce fichier doit etre utilise comme un exemple, il est specifique a une utilisation particuliere
   \version    $Revision$
*/
require_once(PHP_WRITEEXCEL_PATH."/class.writeexcel_workbook.inc.php");
require_once(PHP_WRITEEXCEL_PATH."/class.writeexcel_worksheet.inc.php");

/**
   \class      ComptaExportTableur
   \brief      Classe permettant les exports comptables au format tableur
*/
class ComptaExportTableur extends ComptaExport
{

  function ComptaExportTableur ()
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

  function Export($dir, $linec)
    {
      //$fname = $dir . "/tmp/toto.xls"; DEBUG DEBUG
      $fname = "/tmp/toto.xls";

        $workbook = new writeexcel_workbook($fname);

        $worksheet = &$workbook->addworksheet();

        // Pour les factures

        // Date Operation 040604 pour 4 juin 2004
        // VE -> ventilation
        // code Compte general
        // code client
        // Intitul
        // Numero de piece
        // Montant
        // Type operation D pour Debit ou C pour Credit
        // Date d'echeance, = a la date d'operation si pas d'echeance
        // EUR pour Monnaie en Euros

        // Pour les paiements

        $worksheet->set_column('A:A', 20);
        $worksheet->set_column('B:B', 20);
        $worksheet->set_column('C:C', 15);
        $worksheet->set_column('D:D', 9);
        $worksheet->set_column('E:E', 16);
        $worksheet->set_column('F:F', 18);
        $worksheet->set_column('G:G', 20);

        $i = 0;
        $j = 0;
        $n = sizeof($linec);

        $oldfacture = 0;

        for ( $i = 0 ; $i < $n ; $i++)
        {
            if ( $oldfacture <> $linec[$i][1])
            {
                $worksheet->write_string($j, 0,  strftime("%d%m%y",$linec[$i][0]));
                $worksheet->write_string($j, 1,  'VE');
                $worksheet->write_string($j, 3,  '411000000');
                $worksheet->write_string($j, 4,  $linec[$i][3]." Facture");


                $oldfacture = $linec[$i][1];
                $j++;
            }



            $worksheet->write_string($j, 0,  strftime("%d%m%y",$linec[$i][0]));
            $worksheet->write_string($j, 1,  'VE');
            $worksheet->write_string($j, 2,  $linec[$i][4]);
            $worksheet->write_string($j, 4,  $linec[$i][3]." Facture");

            $j++;

        }

        $workbook->close();

        return 0;
    }
}
