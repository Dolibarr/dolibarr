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
        \file       htdocs/compta/export/ComptaJournalPaiement.php
        \ingroup    compta
        \brief      Fichier de la classe export compta journal
        \version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT.'/compta/export/ComptaJournalPdf.class.php');


/**
        \class      ComptaJournalPaiement
        \brief      Classe export compta journal
*/
class ComptaJournalPaiement  {

  function ComptaJournalPaiement ($db=0)
  { 
    $this->db = $db;
    }
  
  
  function GeneratePdf($user, $dir, $excid, $excref)
  {
    $date = strftime("%Y%m",time());
    
    $file = $dir . "JournalPaiement".$excref . ".pdf";
    
    $grand_total_debit = 0;
    $grand_total_credit = 0;
    
    if (file_exists($dir))
      {
	$this->pdf = new ComptaJournalPdf('P','mm','A4');
	
	$this->pdf->AliasNbPages();
	
	$this->pdf->Open();
	$this->pdf->AddPage();
	
	$this->tab_top = 90;
	$this->tab_height = 90;
	
	$this->pdf->SetTitle("Journal des paiements");
	$this->pdf->SetCreator("Dolibarr ".DOL_VERSION);
	$this->pdf->SetAuthor($user->fullname);
	  
	$this->pdf->SetMargins(10, 10, 10);
	$this->pdf->SetAutoPageBreak(1,10);
	  
	/*
	 *
	 */  
	  
	$this->pdf->SetFillColor(220,220,220);
	  
	$this->pdf->SetFont('Arial','', 9);
	  
	$this->pdf->SetXY (10, 10 );
	$nexY = $this->pdf->GetY();
	    
	$sql = "SELECT p.rowid,".$this->db->pdate("p.datep")." as dp, p.statut";
	$sql .= ", pf.amount";
	$sql .= ", c.libelle, p.num_paiement";
	$sql .= ", f.facnumber, f.increment";
	$sql .= " , s.nom";
	$sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";
	$sql .= " , ".MAIN_DB_PREFIX."c_paiement as c";
	$sql .= " , ".MAIN_DB_PREFIX."paiement_facture as pf";
	$sql .= " , ".MAIN_DB_PREFIX."facture as f";
	$sql .= " , ".MAIN_DB_PREFIX."societe as s";
	$sql .= " WHERE p.fk_paiement = c.id";
	$sql .= " AND pf.fk_paiement = p.rowid";
	$sql .= " AND f.fk_soc = s.rowid";
	$sql .= " AND p.statut = 1 ";
	$sql .= " AND pf.fk_facture = f.rowid";
	$sql .= " AND p.fk_export_compta = ".$excid;

	$sql .= " ORDER BY date_format(p.datep,'%Y%m%d') ASC, s.nom ASC";

	$oldate = '';

	$resql = $this->db->query($sql);

	if ($resql)
	  {
	    $num = $this->db->num_rows($resql);
	    $i = 0; 
	    $var = True;
	    $journ = 'CE';	      
	    $this->hligne = 5;

	    while ($i < $num)
	      {
		$obj = $this->db->fetch_object($resql);

		if ($oldate <> strftime("%d%m%Y",$obj->dp))
		  {

		    if ($oldate <> '')
		      {
			$this->pdf->SetFont('Arial','B',9);

			$this->pdf->cell(143,$this->hligne,'');

			$this->pdf->cell(16,$this->hligne,'Total : ',0,0,'R');
			$this->pdf->cell(18,$this->hligne,$total_debit,0,0,'R');
			$this->pdf->cell(18,$this->hligne,$total_credit,0,0,'R');
			$this->pdf->ln();
		      }

		    $journal = "Journal $journ du ".strftime('%A, %e %B %G',$obj->dp);
		    $total_credit = 0 ;
		    $total_debit = 0 ;
		    $this->pdf->SetFont('Arial','B',10);

		    $this->pdf->cell(10,$this->hligne,"$journal");
		    $this->pdf->ln();

		    $this->pdf->SetFont('Arial','',9);

		    $this->pdf->cell(16,$this->hligne,'Date');

		    $this->pdf->cell(20,$this->hligne,'N Facture');

		    $this->pdf->cell(20,$this->hligne,'Tiers');

		    $this->pdf->cell(87,$this->hligne,'Libellé');

		    $this->pdf->cell(16,$this->hligne,'Echeance',0,0,'R');

		    $this->pdf->cell(18,$this->hligne,'Débit',0,0,'R');

		    $this->pdf->cell(18,$this->hligne,'Crédit',0,0,'R');
		    $this->pdf->ln();

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

		$this->pdf->SetFont('Arial','',9);

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

		$s = $socnom . ' '.$libelle;

		$facnumber = $obj->facnumber;
		if (strlen(trim($obj->increment)) > 0)
		  {
		    $facnumber = $obj->increment;
		  }


		$this->_print_ligne($obj->dp, $facnumber, '41100000', $s, $credit, $debit);

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


		$s = $socnom . ' '.$libelle;
		$this->_print_ligne($obj->dp, $facnumber, '5122000', $s, $credit, $debit);

		$i++; 
	      }

	    $this->pdf->SetFont('Arial','B',9);
	      
	    $this->pdf->cell(143,$this->hligne,'');
	      
	    $this->pdf->cell(16,$this->hligne,'Total : ',0,0,'R');
	    $this->pdf->cell(18,$this->hligne,$total_debit,0,0,'R');
	    $this->pdf->cell(18,$this->hligne,$total_credit,0,0,'R');
	    $this->pdf->ln();
	    /*
	     *
	     */	  	      
	    $this->pdf->cell(143,$this->hligne,'');
	      
	    $this->pdf->cell(16,$this->hligne,'Grand Total : ',0,0,'R');
	    $this->pdf->cell(18,$this->hligne,$grand_total_debit,0,0,'R');
	    $this->pdf->cell(18,$this->hligne,$grand_total_credit,0,0,'R');
	    $this->pdf->ln();

	    /*
	     *
	     *
	     */

	    $this->pdf->Close();
	      
	    $this->pdf->Output($file);


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
  Function _print_ligne($a, $b, $c, $d, $e, $f)
  {
    $this->pdf->cell(16,$this->hligne, strftime('%d%m%y',$a));
    $this->pdf->cell(20,$this->hligne, $b);
    $this->pdf->cell(20,$this->hligne, $c);
    $this->pdf->cell(87,$this->hligne, $d);
    $this->pdf->cell(16,$this->hligne, strftime('%d%m%y',$a),0,0,'R');
    $this->pdf->cell(18,$this->hligne, $e,0,0,'R');
    $this->pdf->cell(18,$this->hligne, $f,0,0,'R');
    $this->pdf->ln();

  }
}

?>
