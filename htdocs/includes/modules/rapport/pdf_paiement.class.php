<?PHP
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

Class pdf_paiement {

  Function pdf_paiement($db=0)
    { 
      $this->db = $db;
      $this->description = "Liste des paiements";

      $this->url = DOL_URL_ROOT."/document/rapport/" . "paiements" . ".pdf";

      $this->tab_top = 30;

      $this->line_height = 8;
      $this->line_per_page = 25;
      $this->tab_height = $this->line_height * $this->line_per_page;

    }

  Function print_link()
    {
      if (file_exists($this->file))
	{
	  print '<a href="'.$this->url.'">paiements.pdf</a>';
	  print '<table><tr>';
	  print '<td align="right">'.filesize($this->file). ' bytes</td>';
	  print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($this->file)).'</td>';
	  print '</tr></table>';
	}
    }

  function Header(&$pdf, $page, $pages)
    {
      $pdf->SetFont('Arial','B',12);
      $pdf->Text(10, 10, FAC_PDF_INTITULE);
            
      $pdf->SetFont('Arial','B',12);
      $pdf->Text(90, 10, "Liste des paiements encaissés");

      $pdf->SetFont('Arial','B',12);
      $pdf->Text(11, 16, "Date : " . strftime("%d %b %Y", time()));
      
      $pdf->SetFont('Arial','B',12);
      $pdf->Text(11, 22, "Page " . $page . " sur " . $pages);

      $pdf->SetFont('Arial','',12);
            
      $pdf->Text(11,$this->tab_top + 6,'Facture');
      
      $pdf->line(40, $this->tab_top, 40, $this->tab_top + $this->tab_height + 10);
      $pdf->Text(42, $this->tab_top + 6,'Date');
      $pdf->line(80, $this->tab_top, 80, $this->tab_top + $this->tab_height + 10);
      $pdf->Text(82, $this->tab_top + 6,'Type paiement');      

      $pdf->line(120, $this->tab_top, 120, $this->tab_top + $this->tab_height + 10);
      $pdf->Text(122, $this->tab_top + 6,'Numéro');
      
      $pdf->line(160, $this->tab_top, 160, $this->tab_top + $this->tab_height + 10);

      $pdf->SetXY (160, $this->tab_top);
      $pdf->MultiCell(40, 10, "Montant", 0, 'R');
      
      $pdf->Rect(10, $this->tab_top, 190, $this->tab_height + 10);
      $pdf->line(10, $this->tab_top + 10, 200, $this->tab_top + 10 );

    }

  Function Body(&$pdf, $page, $lines)
    {
      $pdf->SetFont('Arial','', 10);
      for ($i = 0 ; $i < $this->line_per_page ; $i++)
	{
	  $j = $i + (($page - 1) * $this->line_per_page );
	  $pdf->SetFillColor(220,220,220);

	  $pdf->SetXY (10, $this->tab_top + 10 + ($i * $this->line_height) );
	  $pdf->MultiCell(30, $this->line_height, $lines[$j][0], 0, 'J', 0);
	  
	  $pdf->SetXY (40, $this->tab_top + 10 + ($i * $this->line_height) );
	  $pdf->MultiCell(40, $this->line_height, $lines[$j][1], 0, 'J', 0);

	  $pdf->SetXY (80, $this->tab_top + 10 + ($i * $this->line_height) );
	  $pdf->MultiCell(40, $this->line_height, $lines[$j][2], 0, 'J', 0);

	  $pdf->SetXY (120, $this->tab_top + 10 + ($i * $this->line_height) );
	  $pdf->MultiCell(40, $this->line_height, $lines[$j][3], 0, 'J', 0);

	  $pdf->SetXY (160, $this->tab_top + 10 + ($i * $this->line_height) );
	  $pdf->MultiCell(40, $this->line_height, $lines[$j][4], 0, 'R', 0);

	  if ($i < $this->line_per_page - 1)
	    {
	      $pdf->line(10, $this->tab_top + 10 + (($i+1) * $this->line_height), 200, $this->tab_top + 10 + (($i+1) * $this->line_height));
	    }
	}
    }

  Function write_pdf_file($_dir, $month, $year)
    {
      if (! file_exists($_dir))
	{
	  umask(0);
	  if (! mkdir($_dir, 0755))
	    {
	      print "Impossible de créer $_dir !";
	      die;
	    }
	}
      $_dir = $_dir . '/' . $year . '/';
      if (! file_exists($_dir))
	{
	  umask(0);
	  if (! mkdir($_dir, 0755))
	    {
	      print "Impossible de créer $_dir !";
	      die;
	    }
	}

      $month = substr("0".$month, strlen("0".$month)-2,2);
      $_file = $_dir . "paiements-$month-$year" . ".pdf";


      
      $pdf = new FPDF('P','mm','A4');
      $pdf->Open();
            
      /*
       *
       */  

      $sql = "SELECT ".$this->db->pdate("p.datep")." as dp, p.amount, f.amount as fa_amount, f.facnumber";
      $sql .=", f.rowid as facid, c.libelle as paiement_type, p.num_paiement";
      $sql .= " FROM ".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."c_paiement as c";
      $sql .= " WHERE p.fk_facture = f.rowid AND p.fk_paiement = c.id";
      $sql .= " AND date_format(p.datep, '%m%Y') = " . $month.$year;
      $sql .= " ORDER BY p.datep ASC";
      $result = $this->db->query($sql);

      if ($result)
	{
	  $lignes = $this->db->num_rows();
	  $i = 0; 
	  $var=True;

	  while ($i < $lignes)
	    {
	      $objp = $this->db->fetch_object( $i);
	      $var=!$var;
	 
	      $lines[$i][0] = $objp->facnumber;
	      $lines[$i][1] = strftime("%d %B %Y",$objp->dp);
	      $lines[$i][2] = $objp->paiement_type ;
	      $lines[$i][3] = $objp->num_paiement;
	      $lines[$i][4] = price($objp->amount);
	      $i++;
	    }
	}

      $pages = intval($lignes / $this->line_per_page);

      if (($lignes % $this->line_per_page)>0)
	{
	  $pages++;
	}

      if ($pages == 0)
	{
	  // force à générer au moins une page si le rapport ne contient aucune ligne
	  $pages = 1;
	}

      for ($i = 0 ; $i < $pages ; $i++)
	{
	  $pdf->AddPage();
	  $this->Header($pdf, $i+1, $pages);
	  $this->Body($pdf, $i+1, $lines);
	}
      /*
       *
       */
      
      $pdf->Output($_file);      
    }

}

?>
