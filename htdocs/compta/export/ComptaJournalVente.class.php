<?PHP
/* Copyright (C) 2005-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007      Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/compta/export/ComptaJournalVente.php
        \ingroup    compta
        \brief      Fichier de la classe export compta journal
        \version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT.'/compta/export/ComptaJournalPdf.class.php');


/**
        \class      ComptaJournalVente
        \brief      Classe export compta journal
*/
class ComptaJournalVente  {

  function ComptaJournalVente ($db=0)
    { 
      $this->db = $db;
    }


  function GeneratePdf($user, $dir, $excid, $excref)
    {
      $date = strftime("%Y%m",time());

      $file = $dir . "JournalVente".$excref . ".pdf";

      if (file_exists($dir))
	{
	  $pdf = new ComptaJournalPdf('P','mm','A4');
	  $pdf->AliasNbPages();

	  $pdf->Open();
	  $pdf->AddPage();
	  
	  $this->tab_top = 90;
	  $this->tab_height = 90;
	  
	  $pdf->SetTitle("Journal des ventes");
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
	    
	  $sql = "SELECT f.rowid as facid, f.facnumber, ".$this->db->pdate("f.datef")." as dp";
	  $sql .= " , f.total_ttc as amount, f.tva";
	  $sql .= " , s.nom, s.code_compta";
	  $sql .= " , l.price, l.tva_taux";
	  $sql .= " , c.numero, f.increment";
	  $sql .= " , l.rowid as lrowid";
	  
	  $sql .= " FROM ".MAIN_DB_PREFIX."facturedet as l";
	  $sql .= " , ".MAIN_DB_PREFIX."facture as f";
	  $sql .= " , ".MAIN_DB_PREFIX."societe as s";
	  $sql .= " , ".MAIN_DB_PREFIX."compta_compte_generaux as c";
	  
	  $sql .= " WHERE f.rowid = l.fk_facture ";
	  $sql .= " AND s.rowid = f.fk_soc";
	  $sql .= " AND f.fk_statut = 1 ";
	  $sql .= " AND l.fk_code_ventilation <> 0 ";
	  $sql .= " AND l.fk_export_compta <> 0";	  
	  $sql .= " AND c.rowid = l.fk_code_ventilation";

	  $sql .= " AND l.fk_export_compta = ".$excid;
	  
	  $sql .= " ORDER BY date_format(f.datef,'%Y%m%d') ASC, f.rowid, l.rowid";
	  
	  $oldate = '';
	  $oldfac = '';

	  $resql = $this->db->query($sql);

	  if ($resql)
	    {
	      $num = $this->db->num_rows($resql);
	      $i = 0; 
	      $var = True;
	      $journ = 'VI';
	      $hligne = 5;

	      $wc = array(0,14,6,16,16,90,16,12,4,16);

	      while ($i < $num)
		{
		  $obj = $this->db->fetch_object($resql);

		  if ($oldate <> strftime("%d%m%Y",$obj->dp))
		    {

		      $journal = "Journal $journ du ".strftime('%A, %e %B %G',$obj->dp);
		      $total_credit = 0 ;
		      $total_debit = 0 ;
		      $pdf->SetFont('Arial','B',10);

		      $pdf->cell(10,$hligne,"$journal");
		      $pdf->ln();

		      $pdf->SetFont('Arial','',9);

		      $pdf->cell($wc[1],$hligne,'Date');
		      $pdf->cell($wc[2],$hligne,'');
		      $pdf->cell($wc[3],$hligne,'Compte');
		      $pdf->cell($wc[4],$hligne,'Tiers');
		      $pdf->cell($wc[5],$hligne,'Libellé');
		      $pdf->cell($wc[6],$hligne,'Facture');
		      $pdf->cell($wc[7],$hligne,'Montant',0,0,'R');
		      $pdf->cell($wc[8],$hligne,'');
		      $pdf->cell($wc[9],$hligne,'Echeance',0,0,'R');

		      $pdf->ln();

		      $oldate = strftime("%d%m%Y",$obj->dp);
		    }

		  /*
		   *
		   */
		  $socnom = $obj->nom;
		  $libelle = "Facture";
		  $amount = abs($obj->amount);
		  $price = abs(price($obj->price));
		  $tva = abs($obj->tva);

		  $facnumber = $obj->facnumber;
		  if (strlen(trim($obj->increment)) > 0)
		    {
		      $facnumber = $obj->increment;
		    }

		  if (strlen($obj->nom) > 31)
		  {
		    $socnom = substr($obj->nom, 0 , 31);
		  }

		  $pdf->SetFont('Arial','',9);

		  if ($obj->amount >= 0)
		    {
		      $d = "D";
		      $c = "C";

		      $credit = '';
		      $debit = $amount;
		      $total_debit = $total_debit + $debit;
		      $grand_total_debit = $grand_total_debit + $debit;

		    }
		  else
		    {
		      $d = "C";
		      $c = "D";

		      $credit = $amount;
		      $debit = '';
		      $total_credit = $total_credit + $credit;
		      $grand_total_credit = $grand_total_credit + $credit;
		    }

		  if ($oldfac <> $obj->facid)
		    {
		      $oldfac = $obj->facid;

		      $pdf->cell($wc[1],$hligne,strftime('%d%m%y',$obj->dp));
		      $pdf->cell($wc[2],$hligne,'VI');
		      $pdf->cell($wc[3],$hligne,'41100000');
		      $pdf->cell($wc[4],$hligne,$obj->code_compta);
		      $pdf->cell($wc[5],$hligne,$socnom .' '.$libelle);
		      $pdf->cell($wc[6],$hligne,$facnumber);
		      $pdf->cell($wc[7],$hligne,$amount,0,0,'R');
		      $pdf->cell($wc[8],$hligne,$d);
		      $pdf->cell($wc[9],$hligne,strftime('%d%m%y',$obj->dp),0,0,'R');
		      $pdf->ln();
		      
		      $pdf->cell($wc[1],$hligne,strftime('%d%m%y',$obj->dp));
		      $pdf->cell($wc[2],$hligne,'VI');
		      $pdf->cell($wc[3],$hligne,'4457119');
		      $pdf->cell($wc[4],$hligne,'');
		      $pdf->cell($wc[5],$hligne,$socnom .' '.$libelle);
		      $pdf->cell($wc[6],$hligne,$facnumber);
		      $pdf->cell($wc[7],$hligne,$tva,0,0,'R');
		      $pdf->cell($wc[8],$hligne,$c);
		      $pdf->cell($wc[9],$hligne,strftime('%d%m%y',$obj->dp),0,0,'R');
		      $pdf->ln();
		    }

		  $pdf->cell($wc[1],$hligne,strftime('%d%m%y',$obj->dp));
		  $pdf->cell($wc[2],$hligne,'VI');
		  $pdf->cell($wc[3],$hligne,$obj->numero);
		  $pdf->cell($wc[4],$hligne,'');
		  $pdf->cell($wc[5],$hligne,$socnom .' '.$libelle);
		  $pdf->cell($wc[6],$hligne,$facnumber);
		  $pdf->cell($wc[7],$hligne,$price,0,0,'R');
		  $pdf->cell($wc[8],$hligne,$c);
		  $pdf->cell($wc[9],$hligne,strftime('%d%m%y',$obj->dp),0,0,'R');
		  $pdf->ln();

		  $i++; 
		}
	  	      
	      /*
	       *
	       *
	       */

	      $pdf->Close();
	      
	      $pdf->Output($file);

	      $result = 0;
	    }
	  else
	    {
	      $result -1;
	    }
	}
      else
	{
	  $result = -2;
	}

      return $result;

    }
  /*
   *
   *
   */
}

?>
