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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 *
 */
require (DOL_DOCUMENT_ROOT ."/includes/fpdf/fpdf_indexes.php");
require (DOL_DOCUMENT_ROOT ."/includes/fpdf/fpdf_html.php");

Class CommActionRapport {

  Function CommActionRapport($db=0, $month, $year)
    { 
      $this->db = $db;
      $this->description = "";
      $this->date_edition = strftime("%d %B %Y", time());
      $this->month = $month;
      $this->year = $year;
    }

  Function generate($socid = 0, $catid = 0)
    {
      global $user;

      $dir = DOL_DOCUMENT_ROOT . "/document/rapport/";

      if (! file_exists($dir))
	{
	  umask(0);
	  if (! mkdir($dir, 0755))
	    {
	      return "Error";
	    }
	}

      $dir = DOL_DOCUMENT_ROOT . "/document/rapport/comm/";

      if (! file_exists($dir))
	{
	  umask(0);
	  if (! mkdir($dir, 0755))
	    {
	      return "Error";
	    }
	}

      $dir = DOL_DOCUMENT_ROOT . "/document/rapport/comm/actions/";

      if (! file_exists($dir))
	{
	  umask(0);
	  if (! mkdir($dir, 0755))
	    {
	      return "Error";
	    }
	}

      $file = $dir . "rapport-action-".$this->month."-".$this->year.".pdf";

      if (file_exists($dir))
	{
	  $pdf=new PDF_html('P','mm','A4');
	  $pdf->Open();

	  $pdf->SetTitle("Rapport Commercial");
	  $pdf->SetSubject("Rapport Commercial");
	  $pdf->SetCreator("Dolibarr ".DOL_VERSION);
	  $pdf->SetAuthor("Rodolphe Quiedeville");

	  $pdf->SetFillColor(220,220,220);
	  
	  $pdf->SetFont('Arial','', 9);

	  //	  $nbpage = $this->_cover($pdf);
	  $nbpage = $this->_pages($pdf);

	  $pdf->Close();
	  
	  $pdf->Output($file);
	  
	  return 1;
	}
    }
  /*
   *
   *
   *
   */
  Function _cover(&$pdf)
    {
      $pdf->AddPage();
      $pdf->SetAutoPageBreak(false);
      $pdf->SetFont('Arial','',40);
      $pdf->SetXY (10, 80);
      $pdf->MultiCell(190, 20, "Rapport Commercial", 0, 'C', 0);

      $pdf->SetFont('Arial','',30);
      $pdf->SetXY (10, 140);
      $pdf->MultiCell(190, 20, "Edition du ".$this->date_edition, 0, 'C', 0);


      $pdf->SetXY (10, 170);
      $pdf->SetFont('Arial','B',18);
      $pdf->MultiCell(190, 15, "Rodolphe Quiédeville <rq@lolix.org>", 0, 'C');
      $pdf->SetFont('Arial','B',16);
      $pdf->MultiCell(190, 15, "Tél : +33 (0) 6 13 79 63 41", 0, 'C');

      $pdf->SetFont('Arial','',10);
      $pdf->SetXY (10, 277);
      $pdf->MultiCell(190, 10, "http://www.lafrere.com/", 1, 'C', 1);

      $pdf->Rect(10, 10, 190, 277);
      return 1;
    }
  /*
   *
   */
  Function _pages(&$pdf)
    {
      $pdf->AddPage();
      $pdf->SetAutoPageBreak(true);

      $sql = "SELECT s.nom as societe, s.idp as socidp, s.client, a.id,".$this->db->pdate("a.datea")." as da, a.datea, c.libelle, u.code, a.fk_contact, a.note, a.percent as percent";
      $sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."user as u";
      $sql .= " WHERE a.fk_soc = s.idp AND c.id=a.fk_action AND a.fk_user_author = u.rowid";
      
      $sql .= " AND date_format(a.datea, '%m') = ".$this->month;
      $sql .= " AND date_format(a.datea, '%Y') = ".$this->year;
                  
      $sql .= " ORDER BY a.datea DESC";

      if ($this->db->query($sql))
	{
	  $num = $this->db->num_rows();
	  $i = 0;
	  while ($i < $num)
	    {
	      $obj = $this->db->fetch_object($i);
	      $y = $pdf->GetY();
	      $pdf->SetFont('Arial','',11);
	      $pdf->SetXY(10, $y);
	      $pdf->MultiCell(40, 8, $obj->societe, 0, 'L', 0);
	      $pdf->SetXY(50,$y);
	      $pdf->MultiCell(40, 8, $obj->libelle, 0, 'L', 0);
	      $pdf->SetXY(90,$y);
	      $pdf->MultiCell(110, 8, $obj->note, 0, 'L', 0);
	      $i++;
	    }
	}

      $pdf->Rect(10, 10, 190, 277);
      return 1;
    }


}

?>
