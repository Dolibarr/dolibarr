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
 * $Source$
 *
 */

require_once(DOL_DOCUMENT_ROOT.'/compta/export/ComptaJournalPdf.class.php');

class ComptaJournalPaiement  {

  function ComptaJournalPaiement ($db=0)
    { 
      $this->db = $db;
    }


  function GeneratePdf($user, $excid, $excref)
    {
      $date = strftime("%Y%m",time());

      $dir = DOL_DATA_ROOT."/compta/export/";
      $file = $dir . "JournalPaiement".$excref . ".pdf";

      $grand_total_debit = 0;
      $grand_total_credit = 0;

      if (file_exists($dir))
	{
	  $pdf = new ComptaJournalPdf('P','mm','A4');
	  $pdf->AliasNbPages();

	  $pdf->Open();
	  $pdf->AddPage();
	  
	  $this->tab_top = 90;
	  $this->tab_height = 90;
	  
	  $pdf->SetTitle("Journal des paiements");
	  $pdf->SetCreator("Dolibarr ".DOL_VERSION);
	  $pdf->SetAuthor($user->fullname);
	  
	  $pdf->SetMargins(10, 10, 10);
	  $pdf->SetAutoPageBreak(1,10);
	  
	  /*
	   *
	   */  
	  
	  $pdf->SetFillColor(220,220,220);
	  
	  $pdf->SetFont('Arial','', 9);
	  
	  $pdf->SetXY (10, 10 );
	  $nexY = $pdf->GetY();
	    
	  $sql = "SELECT p.rowid,".$this->db->pdate("p.datep")." as dp, p.statut";
	  $sql .= ", pf.amount";
	  $sql .= ", c.libelle, p.num_paiement";
	  $sql .= ", f.facnumber";
	  $sql .= " , s.nom";
	  $sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";
	  $sql .= " , ".MAIN_DB_PREFIX."c_paiement as c";
	  $sql .= " , ".MAIN_DB_PREFIX."paiement_facture as pf";
	  $sql .= " , ".MAIN_DB_PREFIX."facture as f";
	  $sql .= " , ".MAIN_DB_PREFIX."societe as s";
	  $sql .= " WHERE p.fk_paiement = c.id";
	  $sql .= " AND pf.fk_paiement = p.rowid";
	  $sql .= " AND f.fk_soc = s.idp";
	  $sql .= " AND p.statut = 1 ";
	  $sql .= " AND pf.fk_facture = f.rowid";

	  $sql .= " AND p.fk_export_compta = ".$excid;

	  $sql .= " ORDER BY date_format(p.datep,'%Y%m%d') ASC, s.nom ASC";

	  $oldate = '';
	  
	  if ($this->db->query($sql))
	    {
	      $num = $this->db->num_rows();
	      $i = 0; 
	      $var = True;
	      $journ = 'CE';	      
	      $hligne = 5;

	      while ($i < $num)
		{
		  $obj = $this->db->fetch_object();

		  if ($oldate <> strftime("%d%m%Y",$obj->dp))
		    {

		      if ($oldate <> '')
			{
			  $pdf->SetFont('Arial','B',9);

			  $pdf->cell(143,$hligne,'');

			  $pdf->cell(16,$hligne,'Total : ',0,0,'R');
			  $pdf->cell(18,$hligne,$total_debit,0,0,'R');
			  $pdf->cell(18,$hligne,$total_credit,0,0,'R');
			  $pdf->ln();
			}

		      $journal = "Journal $journ du ".strftime('%A, %e %B %G',$obj->dp);
		      $total_credit = 0 ;
		      $total_debit = 0 ;
		      $pdf->SetFont('Arial','B',10);

		      $pdf->cell(10,$hligne,"$journal");
		      $pdf->ln();

		      $pdf->SetFont('Arial','',9);

		      $pdf->cell(16,$hligne,'Date');

		      $pdf->cell(20,$hligne,'N Facture');

		      $pdf->cell(20,$hligne,'Tiers');

		      $pdf->cell(87,$hligne,'Libellé');

		      $pdf->cell(16,$hligne,'Echeance',0,0,'R');

		      $pdf->cell(18,$hligne,'Débit',0,0,'R');

		      $pdf->cell(18,$hligne,'Crédit',0,0,'R');
		      $pdf->ln();

		      $oldate = strftime("%d%m%Y",$obj->dp);
		    }

		  /*
		   *
		   */
		  $socnom = $obj->nom;
		  $libelle = $obj->libelle;

		  if (strlen($obj->nom) > 31)
		  {
		    $socnom = substr($obj->nom, 0 , 31);
		  }

		  $pdf->SetFont('Arial','',9);

		  if ($obj->amount >= 0)
		    {
		      $credit = '';
		      $debit = abs($obj->amount);
		      $total_debit = $total_debit + $debit;
		      $grand_total_debit = $grand_total_debit + $debit;
		    }
		  else
		    {
		      $credit = abs($obj->amount);
		      $debit = '';
		      $total_credit = $total_credit + $credit;
		      $grand_total_credit = $grand_total_credit + $credit;
		      $libelle = "Rejet Prélèvement";
		    }

		  $pdf->cell(16,$hligne,strftime('%d%m%y',$obj->dp));

		  $pdf->cell(20,$hligne,$obj->facnumber);

		  $pdf->cell(20,$hligne,'4110000');

		  $pdf->cell(87,$hligne,$socnom .' '.$libelle);

		  /* Echeance */

		  $pdf->cell(16,$hligne,strftime('%d%m%y',$obj->dp),0,0,'R');

		  $pdf->cell(18,$hligne,$credit,0,0,'R');

		  $pdf->cell(18,$hligne,$debit,0,0,'R');

		  $pdf->ln();

		  /*
		   *
		   *
		   */

		  if ($obj->amount >= 0)
		    {
		      $credit = abs($obj->amount);
		      $debit = '';
		      $total_credit = $total_credit + $credit;
		      $grand_total_credit = $grand_total_credit + $credit;
		    }
		  else
		    {
		      $credit = '';
		      $debit = abs($obj->amount);
		      $total_debit = $total_debit + $debit;
		      $grand_total_debit = $grand_total_debit + $debit;
		    }

		  $pdf->cell(16,$hligne,strftime('%d%m%y',$obj->dp));

		  $pdf->cell(20,$hligne,$obj->facnumber);

		  $pdf->cell(20,$hligne,'5122000');

		  $pdf->cell(87,$hligne,$socnom . ' '.$libelle);

		  /* Echeance */

		  $pdf->cell(16,$hligne,strftime('%d%m%y',$obj->dp),0,0,'R');

		  $pdf->cell(18,$hligne,$credit,0,0,'R');

		  $pdf->cell(18,$hligne,$debit,0,0,'R');

		  $pdf->ln();

		  $i++; 
		}

	      $pdf->SetFont('Arial','B',9);
	      
	      $pdf->cell(143,$hligne,'');
	      
	      $pdf->cell(16,$hligne,'Total : ',0,0,'R');
	      $pdf->cell(18,$hligne,$total_debit,0,0,'R');
	      $pdf->cell(18,$hligne,$total_credit,0,0,'R');
	      $pdf->ln();
	      /*
	       *
	       */	  	      
	      $pdf->cell(143,$hligne,'');
	      
	      $pdf->cell(16,$hligne,'Grand Total : ',0,0,'R');
	      $pdf->cell(18,$hligne,$grand_total_debit,0,0,'R');
	      $pdf->cell(18,$hligne,$grand_total_credit,0,0,'R');
	      $pdf->ln();

	      /*
	       *
	       *
	       */

	      $pdf->Close();
	      
	      $pdf->Output($file);


	      return 1;
	    }
	  else
	    {
	      $this->error="";
	      return 0;
	    }
	}
      else
	{
	  $this->error="Erreur: FAC_OUTPUTDIR non défini !";
	  return 0;
	}
    }
  /*
   *
   *
   *
   */
}

?>
