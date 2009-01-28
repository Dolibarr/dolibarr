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
 * or see http://www.gnu.org/
 *
 * $Id$
 */

/**
        \file       htdocs/compta/export/ComptaJournalPdf.php
        \ingroup    compta
        \brief      Fichier de la classe export compta journal
        \version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');


/**
        \class      ComptaJournalPdf
        \brief      Classe export compta journal
*/
class ComptaJournalPdf extends FPDF  {

  function Footer()
  {
    $this->SetY(-10);
    //Police Arial italique 8
    $this->SetFont('Arial','I',8);

    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
  }
}

?>
