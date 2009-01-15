<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
	    \file       htdocs/includes/modules/action/rapport.pdf.php
        \ingroup    commercial
		\brief      Fichier de generation de PDF pour les rapports d'actions
		\version    $Id$
*/

require_once(FPDFI_PATH.'fpdi_protection.php');
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");

/**
        \class      CommActionRapport
	    \brief      Classe permettant la generation des rapports d'actions
*/
class CommActionRapport
{
    var $db;
    var $description;
    var $date_edition;
    var $year;
    var $month;

    var $title;
    var $subject;

    function CommActionRapport($db=0, $month, $year)
    {
        global $langs;
        $langs->load("commercial");

        $this->db = $db;
        $this->description = "";
        $this->date_edition = time();
        $this->month = $month;
        $this->year = $year;

        // Dimension page pour format A4
        $this->type = 'pdf';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format = array($this->page_largeur,$this->page_hauteur);
        $this->marge_gauche=5;
        $this->marge_droite=5;
        $this->marge_haute=10;
        $this->marge_basse=10;

        $this->title=$langs->trans("ActionsReport").' '.$this->year."-".$this->month;
        $this->subject=$langs->trans("ActionsReport").' '.$this->year."-".$this->month;
    }

    function generate($socid = 0, $catid = 0, $outputlangs='')
    {
        global $user,$conf,$langs;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// Force output charset to ISO, because, FPDF expect text encoded in ISO
		$outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");

		$outputlangs->setPhpLang();

		$dir = $conf->actions->dir_temp."/";
        $file = $dir . "actions-".$this->month."-".$this->year.".pdf";

        if (! file_exists($dir))
        {
            if (create_exdir($dir) < 0)
            {
                $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                return 0;
            }
        }

        if (file_exists($dir))
        {
       		// Protection et encryption du pdf
			if ($conf->global->PDF_SECURITY_ENCRYPTION)
			{
				$pdf=new FPDI_Protection('P','mm',$this->format);
				$pdfrights = array('print'); // Ne permet que l'impression du document
				$pdfuserpass = ''; // Mot de passe pour l'utilisateur final
				$pdfownerpass = NULL; // Mot de passe du propri�tire, cr�e al�atoirement si pas d�fini
				$pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
			}
			else
			{
				$pdf=new FPDI('P','mm',$this->format);
			}

			$pdf->Open();

			$pdf->SetDrawColor(128,128,128);
            $pdf->SetFillColor(220,220,220);

			$pdf->SetTitle($outputlangs->convToOutputCharset($this->title));
            $pdf->SetSubject($outputlangs->convToOutputCharset($this->subject));
            $pdf->SetCreator("Dolibarr ".DOL_VERSION);
            $pdf->SetAuthor($outputlangs->convToOutputCharset($user->fullname));
            $pdf->SetKeywords($outputlangs->convToOutputCharset($this->title." ".$this->subject));

			$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
			$pdf->SetAutoPageBreak(1,0);

            $nbpage = $this->_pages($pdf, $outputlangs);

            $pdf->AliasNbPages();
            $pdf->Close();

            $pdf->Output($file);
			if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

            return 1;
        }
    }

	/**
	 * Write content of pages
	 *
	 * @param unknown_type 		$pdf
	 * @return 	int				1
	 */
    function _pages(&$pdf, $outputlangs)
    {
		$height=3;		// height for text separation
    	$pagenb=1;

		$y=$this->_pagehead($pdf, $outputlangs, $pagenb);
    	$y++;
		$pdf->SetFont('Arial','',8);

		$sql = "SELECT s.nom as societe, s.rowid as socid, s.client,";
		$sql.= " a.id,".$this->db->pdate("a.datep")." as dp, ".$this->db->pdate("a.datep2")." as dp2,";
		$sql.= " a.fk_contact, a.note, a.percent as percent,";
		$sql.= " c.libelle,";
		$sql.= " u.login";
        $sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a, ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."user as u";
        $sql.= " WHERE a.fk_soc = s.rowid AND c.id=a.fk_action AND a.fk_user_author = u.rowid";
        $sql.= " AND date_format(a.datep, '%m') = ".$this->month;
        $sql.= " AND date_format(a.datep, '%Y') = ".$this->year;
        $sql.= " ORDER BY a.datep DESC";

        dolibarr_syslog("Rapport.pdf::_page sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            $y0=$y1=$y2=$y3=0;

            while ($i < $num)
            {
        		$obj = $this->db->fetch_object($resql);

		        $y = max($y, $pdf->GetY(), $y0, $y1, $y2, $y3);

		        // Calculate height of text
        		$text=dolibarr_trunc(dol_htmlentitiesbr_decode($obj->note),150);
		        //print 'd'.$text; exit;
        		$nboflines=dol_nboflines($text);
		        $heightlinemax=max(2*$height,$nboflines*$height);
				// Check if there is enough space to print record
		        if ((1+$y+$heightlinemax) >= ($this->page_hauteur - $this->marge_haute))
		        {
		        	// We need to break page
		        	$pagenb++;
					$y=$this->_pagehead($pdf, $outputlangs, $pagenb);
			    	$y++;
					$pdf->SetFont('Arial','',8);
		        }
		        $y++;

                $pdf->SetXY($this->marge_gauche, $y);
                $pdf->MultiCell(22, $height, dolibarr_print_date($obj->dp,"day")."\n".dolibarr_print_date($obj->dp,"hour"), 0, 'L', 0);
                $y0 = $pdf->GetY();

                $pdf->SetXY(26, $y);
                $pdf->MultiCell(32, $height, dolibarr_trunc($outputlangs->convToOutputCharset($obj->societe),32), 0, 'L', 0);
                $y1 = $pdf->GetY();

                $pdf->SetXY(60,$y);
                $pdf->MultiCell(32, $height, dolibarr_trunc($outputlangs->convToOutputCharset($obj->libelle),32), 0, 'L', 0);
                $y2 = $pdf->GetY();

                $pdf->SetXY(106,$y);
                $pdf->MultiCell(94, $height, $outputlangs->convToOutputCharset($text), 0, 'L', 0);
                $y3 = $pdf->GetY();

                //$pdf->MultiCell(94,2,"y=$y y3=$y3",0,'L',0);

                $i++;
            }
        }

        return 1;
    }

    /**
     *      \brief      Affiche en-tete facture
     *      \param      pdf             Objet PDF
     *      \param      outputlang		Objet lang cible
     * 		\param		pagenb			Page nb
     */
    function _pagehead(&$pdf, $outputlangs, $pagenb)
    {
		global $conf,$langs;

		// New page
        $pdf->AddPage();

    	// Show title
        $pdf->SetFont('Arial','B',10);
    	$pdf->SetXY($this->marge_gauche, $this->marge_haute);
        $pdf->MultiCell(80, 1, $this->title, 0, 'L', 0);
		$pdf->SetXY($this->page_largeur-$this->marge_droite-40, $this->marge_haute);
        $pdf->MultiCell(40, 1, $pagenb.'/{nb}', 0, 'R', 0);

        $y=$pdf->GetY()+2;

		$pdf->Rect($this->marge_gauche, $y,
			$this->page_largeur - $this->marge_gauche - $this->marge_droite,
			$this->page_hauteur - $this->marge_haute - $this->marge_basse);
		$y=$pdf->GetY()+1;

		return $y;
    }
}

?>
