<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/action/rapport.pdf.php
 *	\ingroup    commercial
 *	\brief      File to build PDF with events
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

/**
 *	Class to generate event report
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

	var $marge_gauche;
	var	$marge_droite;
	var	$marge_haute;
	var	$marge_basse;


	/**
	 * Constructor
	 *
	 * @param 	DoliDB	$db		Database handler
	 * @param	int		$month	Month
	 * @param	int		$year	Year
	 */
	function __construct($db, $month, $year)
	{
		global $conf,$langs;
		$langs->load("commercial");

		$this->db = $db;
		$this->description = "";
		$this->date_edition = time();
		$this->month = $month;
		$this->year = $year;

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;

        $this->title=$langs->transnoentitiesnoconv("ActionsReport").' '.$this->year."-".$this->month;
        $this->subject=$langs->transnoentitiesnoconv("ActionsReport").' '.$this->year."-".$this->month;
	}

	/**
     *      Write the object to document file to disk
     *
     *      @param	int			$socid			Thirdparty id
     *      @param  int			$catid			Cat id
     *      @param  Translate	$outputlangs    Lang object for output language
     *      @return int             			1=OK, 0=KO
	 */
	function write_file($socid = 0, $catid = 0, $outputlangs='')
	{
		global $user,$conf,$langs;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");

        $dir = $conf->agenda->dir_temp."/";
		$file = $dir . "actions-".$this->month."-".$this->year.".pdf";

		if (! file_exists($dir))
		{
			if (dol_mkdir($dir) < 0)
			{
				$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}

		if (file_exists($dir))
		{
            $pdf=pdf_getInstance($this->format);
            $heightforinfotot = 50;	// Height reserved to output the info and total part
            $heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
            $heightforfooter = $this->marge_basse + 8;	// Height reserved to output the footer (value include bottom margin)
            $pdf->SetAutoPageBreak(1,0);

            if (class_exists('TCPDF'))
            {
                $pdf->setPrintHeader(false);
                $pdf->setPrintFooter(false);
            }
            $pdf->SetFont(pdf_getPDFFont($outputlangs));

			$pdf->Open();
			$pagenb=0;
			$pdf->SetDrawColor(128,128,128);
			$pdf->SetFillColor(220,220,220);

			$pdf->SetTitle($outputlangs->convToOutputCharset($this->title));
			$pdf->SetSubject($outputlangs->convToOutputCharset($this->subject));
			$pdf->SetCreator("Dolibarr ".DOL_VERSION);
			$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
			$pdf->SetKeywords($outputlangs->convToOutputCharset($this->title." ".$this->subject));

			$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

			$nbpage = $this->_pages($pdf, $outputlangs);

			if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();
			$pdf->Close();

			$pdf->Output($file,'F');
			if (! empty($conf->global->MAIN_UMASK))
			@chmod($file, octdec($conf->global->MAIN_UMASK));

			return 1;
		}
	}

	/**
	 * Write content of pages
	 *
	 * @param   PDF			&$pdf			Object pdf
     * @param	Translate   $outputlangs	Object langs
	 * @return  int							1
	 */
	function _pages(&$pdf, $outputlangs)
	{
		$height=3;		// height for text separation
		$pagenb=1;

		$y=$this->_pagehead($pdf, $outputlangs, $pagenb);
		$y++;
		$pdf->SetFont('','',8);

		$sql = "SELECT s.nom as societe, s.rowid as socid, s.client,";
		$sql.= " a.id, a.datep as dp, a.datep2 as dp2,";
		$sql.= " a.fk_contact, a.note, a.percent as percent, a.label,";
		$sql.= " c.code, c.libelle,";
		$sql.= " u.login";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."user as u, ".MAIN_DB_PREFIX."actioncomm as a";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON a.fk_soc = s.rowid";
		$sql.= " WHERE c.id=a.fk_action AND a.fk_user_author = u.rowid";
		$sql.= " AND a.datep BETWEEN '".$this->db->idate(dol_get_first_day($this->year,$this->month,false))."'";
		$sql.= " AND '".$this->db->idate(dol_get_last_day($this->year,$this->month,false))."'";
		$sql.= " ORDER BY a.datep DESC";

		dol_syslog(get_class($this)."::_page sql=".$sql);
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
				$text='';
				if (! preg_match('/^'.preg_quote($obj->label).'/',$obj->note)) $text=$obj->label."\n";
				$text.=$obj->note;
				$text=dol_trunc(dol_htmlentitiesbr_decode($text),150);
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
					$pdf->SetFont('','',8);
				}
				$y++;

				// Date
				$pdf->SetXY($this->marge_gauche, $y);
				$pdf->MultiCell(22, $height, dol_print_date($this->db->jdate($obj->dp),"day")."\n".dol_print_date($this->db->jdate($obj->dp),"hour"), 0, 'L', 0);
				$y0 = $pdf->GetY();

				// Third party
				$pdf->SetXY(26, $y);
				$pdf->MultiCell(32, $height, dol_trunc($outputlangs->convToOutputCharset($obj->societe),32), 0, 'L', 0);
				$y1 = $pdf->GetY();

				// Action code
				$code=$obj->code;
				if (empty($conf->global->AGENDA_USE_EVENT_TYPE))
				{
					if ($code == 'AC_OTH')      $code='AC_MANUAL';
					if ($code == 'AC_OTH_AUTO') $code='AC_AUTO';
				}
				$pdf->SetXY(60,$y);
				$pdf->MultiCell(32, $height, dol_trunc($outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Action".$code)),32), 0, 'L', 0);
				$y2 = $pdf->GetY();

				// Description of event
				$pdf->SetXY(106,$y);
				$pdf->MultiCell(94, $height, $outputlangs->convToOutputCharset($text), 0, 'L', 0);
				$y3 = $pdf->GetY();

				$i++;
			}
		}

		return 1;
	}

	/**
	 *  Show top header of page.
	 *
	 * 	@param	PDF			&$pdf     		Object PDF
	 *  @param  Translate	$outputlangs	Object lang for output
	 * 	@param	int			$pagenb			Page nb
	 *  @return	void
	 */
	function _pagehead(&$pdf, $outputlangs, $pagenb)
	{
		global $conf,$langs;

		// Do not add the BACKGROUND as this is a report
		//pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		// New page
		$pdf->AddPage();

		// Show title
		$pdf->SetFont('','B',10);
		$pdf->SetXY($this->marge_gauche, $this->marge_haute);
		$pdf->MultiCell(120, 1, $outputlangs->convToOutputCharset($this->title), 0, 'L', 0);
        // Show page nb only on iso languages (so default Helvetica font)
        if (pdf_getPDFFont($outputlangs) == 'Helvetica')
        {
		    $pdf->SetXY($this->page_largeur-$this->marge_droite-40, $this->marge_haute);
            $pdf->MultiCell(40, 1, $pagenb.'/'.$pdf->getAliasNbPages(), 0, 'R', 0);
        }

		$y=$pdf->GetY()+2;

		$pdf->Rect($this->marge_gauche, $y, ($this->page_largeur - $this->marge_gauche - $this->marge_droite), ($this->page_hauteur - $this->marge_haute - $this->marge_basse));
		$y=$pdf->GetY()+1;

		return $y;
	}
}

?>
