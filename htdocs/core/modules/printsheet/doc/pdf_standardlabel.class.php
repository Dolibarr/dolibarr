<?php
/* Copyright (C) 2003       Steve Dillon
 * Copyright (C) 2003       Laurent Passebecq
 * Copyright (C) 2001-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2006-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2015       Francis Appels          <francis.appels@yahoo.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/core/modules/printsheet/doc/pdf_standardlabel.class.php
 *  \ingroup    core
 *  \brief      Fichier de la classe permettant d'editer au format PDF des etiquettes au format Avery ou personnalise
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonstickergenerator.class.php';

/**
 *  Class to generate stick sheet with format Avery or other personalised
 */
class pdf_standardlabel extends CommonStickerGenerator
{
	/**
	 * Output a sticker on page at position _COUNTX, _COUNTY (_COUNTX and _COUNTY start from 0)
	 *
	 * @param	PDF			$pdf			PDF reference
	 * @param	Translate	$outputlangs	Output langs
	 * @param	array		$param			Associative array containing label content and optional parameters
	 * @return	void
	 */
    public function addSticker(&$pdf, $outputlangs, $param)
    {
		// use this method in future refactoring
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Output a sticker on page at position _COUNTX, _COUNTY (_COUNTX and _COUNTY start from 0)
	 * - %LOGO% is replace with company logo
	 * - %PHOTO% is replace with photo provided as parameter
	 *
	 * @param	PDF			$pdf			PDF reference
	 * @param	string		$textleft		Text left
	 * @param	string		$header			Header
	 * @param	string		$footer			Footer
	 * @param	Translate	$outputlangs	Output langs
	 * @param	string		$textright		Text right
	 * @param	string		$photo			Photo (full path to image file used as replacement for key %PHOTOS% into left, right, header or footer text)
	 * @return	void
	 */
	public function Add_PDF_label(&$pdf, $textleft, $header, $footer, $outputlangs, $textright = '', $photo = '')
	{
        // phpcs:enable
		global $mysoc, $conf, $langs;
		global $forceimgscalewidth, $forceimgscaleheight;

		$imgscalewidth=(empty($forceimgscalewidth)?0.3:$forceimgscalewidth);	// Scale of image for width (1=Full width of sticker)
		$imgscaleheight=(empty($forceimgscaleheight)?0.5:$forceimgscaleheight);	// Scale of image for height (1=Full height of sticker)

		// We are in a new page, then we must add a page
		if (($this->_COUNTX ==0) && ($this->_COUNTY==0) and (!$this->_First==1)) {
			$pdf->AddPage();
		}
		$this->_First=0;
		$_PosX = $this->_Margin_Left+($this->_COUNTX*($this->_Width+$this->_X_Space));
		$_PosY = $this->_Margin_Top+($this->_COUNTY*($this->_Height+$this->_Y_Space));

		// Define logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$mysoc->logo;
		if (! is_readable($logo))
		{
			$logo='';
			if (! empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small))
			{
				$logo=$conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small;
			}
			elseif (! empty($mysoc->logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo))
			{
				$logo=$conf->mycompany->dir_output.'/logos/'.$mysoc->logo;
			}
		}

		// Define photo
		if (! empty($photo))
		{
			if (! is_readable($photo)) $photo='';
		}

		// Define background image
		$backgroundimage='';

		// Print lines
		if ($this->code == "CARD")
		{
			$this->Tformat=$this->_Avery_Labels["CARD"];
			//$this->_Pointille($pdf,$_PosX,$_PosY,$_PosX+$this->_Width,$_PosY+$this->_Height,0.3,25);
			$this->_Croix($pdf, $_PosX, $_PosY, $_PosX+$this->_Width, $_PosY+$this->_Height, 0.1, 10);
		}

		// Background
		if ($backgroundimage)
		{
			$pdf->image($backgroundimage, $_PosX, $_PosY, $this->_Width, $this->_Height);
		}

		$xleft=2; $ytop=2;

		// Top
		if ($header!='')
		{
			if ($this->code == "CARD")
			{
				$pdf->SetDrawColor(128, 128, 128);
				$pdf->Line($_PosX, $_PosY+$this->_Line_Height+1, $_PosX+$this->_Width, $_PosY+$this->_Line_Height+1); // Only 1 mm and not ytop for top text
				$pdf->SetDrawColor(0, 0, 0);
			}
			$pdf->SetXY($_PosX+$xleft, $_PosY+1); // Only 1 mm and not ytop for top text
			$pdf->Cell($this->_Width-2*$xleft, $this->_Line_Height, $outputlangs->convToOutputCharset($header), 0, 1, 'C');
		}


		$ytop+=(empty($header)?0:(1+$this->_Line_Height));

		// Define widthtouse and heighttouse
		$maxwidthtouse=round(($this->_Width - 2*$xleft)*$imgscalewidth); $maxheighttouse=round(($this->_Height - 2*$ytop)*$imgscaleheight);
		$defaultratio=($maxwidthtouse/$maxheighttouse);
		$widthtouse=$maxwidthtouse; $heighttouse=0;		// old value for image
		$tmp=dol_getImageSize($photo, false);
		if ($tmp['height'])
		{
			$imgratio=$tmp['width']/$tmp['height'];
			if ($imgratio >= $defaultratio) { $widthtouse = $maxwidthtouse; $heighttouse = round($widthtouse / $imgratio); }
			else { $heightouse = $maxheighttouse; $widthtouse = round($heightouse * $imgratio); }
		}
		//var_dump($this->_Width.'x'.$this->_Height.' with border and scale '.$imgscale.' => max '.$maxwidthtouse.'x'.$maxheighttouse.' => We use '.$widthtouse.'x'.$heighttouse);exit;

		// Center
		if ($textright=='')	// Only a left part
		{
			// Output left area
			if ($textleft == '%LOGO%' && $logo) $pdf->Image($logo, $_PosX+$xleft, $_PosY+$ytop, $widthtouse, $heighttouse);
			elseif ($textleft == '%PHOTO%' && $photo) $pdf->Image($photo, $_PosX+$xleft, $_PosY+$ytop, $widthtouse, $heighttouse);
			else
			{
				$pdf->SetXY($_PosX+$xleft, $_PosY+$ytop);
				$pdf->MultiCell($this->_Width, $this->_Line_Height, $outputlangs->convToOutputCharset($textleft), 0, 'L');
			}
		}
		elseif ($textleft!='' && $textright!='')	//
		{
			if ($textleft == '%LOGO%' || $textleft == '%PHOTO%')
			{
				if ($textleft == '%LOGO%' && $logo) $pdf->Image($logo, $_PosX+$xleft, $_PosY+$ytop, $widthtouse, $heighttouse);
				elseif ($textleft == '%PHOTO%' && $photo) $pdf->Image($photo, $_PosX+$xleft, $_PosY+$ytop, $widthtouse, $heighttouse);
				$pdf->SetXY($_PosX+$xleft+$widthtouse+1, $_PosY+$ytop);
				$pdf->MultiCell($this->_Width-$xleft-$xleft-$widthtouse-1, $this->_Line_Height, $outputlangs->convToOutputCharset($textright), 0, 'R');
			}
			elseif ($textright == '%LOGO%' || $textright == '%PHOTO%')
			{
				if ($textright == '%LOGO%' && $logo) $pdf->Image($logo, $_PosX+$this->_Width-$widthtouse-$xleft, $_PosY+$ytop, $widthtouse, $heighttouse);
				elseif ($textright == '%PHOTO%' && $photo) $pdf->Image($photo, $_PosX+$this->_Width-$widthtouse-$xleft, $_PosY+$ytop, $widthtouse, $heighttouse);
				$pdf->SetXY($_PosX+$xleft, $_PosY+$ytop);
				$pdf->MultiCell($this->_Width-$widthtouse-$xleft-$xleft-1, $this->_Line_Height, $outputlangs->convToOutputCharset($textleft), 0, 'L');
			}
			else	// text on halft left and text on half right
			{
				$pdf->SetXY($_PosX+$xleft, $_PosY+$ytop);
				$pdf->MultiCell(round($this->_Width/2), $this->_Line_Height, $outputlangs->convToOutputCharset($textleft), 0, 'L');
				$pdf->SetXY($_PosX+round($this->_Width/2), $_PosY+$ytop);
				$pdf->MultiCell(round($this->_Width/2)-2, $this->_Line_Height, $outputlangs->convToOutputCharset($textright), 0, 'R');
			}
		}
		else	// Only a right part
		{
			// Output right area
			if ($textright == '%LOGO%' && $logo) $pdf->Image($logo, $_PosX+$this->_Width-$widthtouse-$xleft, $_PosY+$ytop, $widthtouse, $heighttouse);
			elseif ($textright == '%PHOTO%' && $photo) $pdf->Image($photo, $_PosX+$this->_Width-$widthtouse-$xleft, $_PosY+$ytop, $widthtouse, $heighttouse);
			else
			{
				$pdf->SetXY($_PosX+$xleft, $_PosY+$ytop);
				$pdf->MultiCell($this->_Width-$xleft, $this->_Line_Height, $outputlangs->convToOutputCharset($textright), 0, 'R');
			}
		}

		// Bottom
		if ($footer!='')
		{
			if ($this->code == "CARD")
			{
				$pdf->SetDrawColor(128, 128, 128);
				$pdf->Line($_PosX, $_PosY+$this->_Height-$this->_Line_Height-2, $_PosX+$this->_Width, $_PosY+$this->_Height-$this->_Line_Height-2);
				$pdf->SetDrawColor(0, 0, 0);
			}
			$pdf->SetXY($_PosX, $_PosY+$this->_Height-$this->_Line_Height-1);
			$pdf->Cell($this->_Width, $this->_Line_Height, $outputlangs->convToOutputCharset($footer), 0, 1, 'C');
		}
		//print "$_PosY+$this->_Height-$this->_Line_Height-1<br>\n";

		$this->_COUNTY++;

		if ($this->_COUNTY == $this->_Y_Number) {
			// Si on est en bas de page, on remonte le 'curseur' de position
			$this->_COUNTX++;
			$this->_COUNTY=0;
		}

		if ($this->_COUNTX == $this->_X_Number) {
			// Si on est en bout de page, alors on repart sur une nouvelle page
			$this->_COUNTX=0;
			$this->_COUNTY=0;
		}
	}




    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Function to build PDF on disk, then output on HTTP strem.
	 *
	 *	@param	array		$arrayofrecords		Array of record informations (array('textleft'=>,'textheader'=>, ..., 'id'=>,'photo'=>)
	 *	@param	Translate	$outputlangs		Lang object for output language
	 *	@param	string		$srctemplatepath	Full path of source filename for generator using a template file
	 *	@param	string		$outputdir			Output directory for pdf file
	 *  @param  string      $filename           Short file name of PDF output file
	 *	@return int								1=OK, 0=KO
	 */
	public function write_file($arrayofrecords, $outputlangs, $srctemplatepath, $outputdir = '', $filename = 'tmp_address_sheet.pdf')
	{
        // phpcs:enable
		global $user,$conf,$langs,$mysoc,$_Avery_Labels;

		$this->code=$srctemplatepath;
		$this->Tformat = $_Avery_Labels[$this->code];
		if (empty($this->Tformat)) { dol_print_error('', 'ErrorBadTypeForCard'.$this->code); exit; }
		$this->type = 'pdf';
        // standard format or custom
        if ($this->Tformat['paper-size']!='custom') {
            $this->format = $this->Tformat['paper-size'];
        } else {
            //custom
            $resolution= array($this->Tformat['custom_x'], $this->Tformat['custom_y']);
            $this->format = $resolution;
        }

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "dict", "companies", "admin"));

		$title=$outputlangs->transnoentities('Labels');
		$keywords=$title." ".$outputlangs->convToOutputCharset($mysoc->name);

		$dir = (empty($outputdir)?$conf->adherent->dir_temp:$outputdir);
		$file = $dir."/".$filename;

		if (! file_exists($dir))
		{
			if (dol_mkdir($dir) < 0)
			{
				$this->error=$langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		}

		$pdf=pdf_getInstance($this->format, $this->Tformat['metric'], $this->Tformat['orientation']);

		if (class_exists('TCPDF'))
		{
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
		}
		$pdf->SetFont(pdf_getPDFFont($outputlangs));

		$pdf->SetTitle($title);
		$pdf->SetSubject($title);
		$pdf->SetCreator("Dolibarr ".DOL_VERSION);
		$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
		$pdf->SetKeyWords($keywords);
		if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

		$pdf->SetMargins(0, 0);
		$pdf->SetAutoPageBreak(false);

		$this->_Metric_Doc = $this->Tformat['metric'];
		// Permet de commencer l'impression de l'etiquette desiree dans le cas ou la page a deja servie
		$posX=1;
		$posY=1;
		if ($posX > 0) $posX--; else $posX=0;
		if ($posY > 0) $posY--; else $posY=0;
		$this->_COUNTX = $posX;
		$this->_COUNTY = $posY;
		$this->_Set_Format($pdf, $this->Tformat);


		$pdf->Open();
		$pdf->AddPage();


		// Add each record
		foreach($arrayofrecords as $val)
		{
			// imprime le texte specifique sur la carte
			$this->Add_PDF_label($pdf, $val['textleft'], $val['textheader'], $val['textfooter'], $langs, $val['textright'], $val['photo']);
		}

		//$pdf->SetXY(10, 295);
		//$pdf->Cell($this->_Width, $this->_Line_Height, 'XXX',0,1,'C');


		// Output to file
		$pdf->Output($file, 'F');

		if (! empty($conf->global->MAIN_UMASK))
			@chmod($file, octdec($conf->global->MAIN_UMASK));

		// Output to http stream
		clearstatcache();

		$attachment=true;
		if (! empty($conf->global->MAIN_DISABLE_FORCE_SAVEAS)) $attachment=false;
		$type=dol_mimetype($filename);

		//if ($encoding)   header('Content-Encoding: '.$encoding);
		if ($type)		 header('Content-Type: '.$type);
		if ($attachment) header('Content-Disposition: attachment; filename="'.$filename.'"');
		else header('Content-Disposition: inline; filename="'.$filename.'"');

		// Ajout directives pour resoudre bug IE
		header('Cache-Control: Public, must-revalidate');
		header('Pragma: public');

		readfile($file);

		$this->result = array('fullpath'=>$file);

		return 1;
	}
}
