<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/* Inspire de PDF_Label
 * PDF_Label - PDF label editing
 * @package PDF_Label
 * @author Laurent PASSEBECQ <lpasseb@numericable.fr>
 * @copyright 2003 Laurent PASSEBECQ
 * disponible ici : http://www.fpdf.org/fr/script/script29.php
 */

////////////////////////////////////////////////////
// PDF_Label
//
// Classe afin d'editer au format PDF des etiquettes
// au format Avery ou personnalise
//
//
// Copyright (C) 2003 Laurent PASSEBECQ (LPA)
// Base sur les fonctions de Steve Dillon : steved@mad.scientist.com
//
//-------------------------------------------------------------------
// VERSIONS :
// 1.0  : Initial release
// 1.1  : +	: Added unit in the constructor
//	  + : Now Positions start @ (1,1).. then the first image @top-left of a page is (1,1)
//	  + : Added in the description of a label :
//		font-size	: defaut char size (can be changed by calling Set_Char_Size(xx);
//		paper-size	: Size of the paper for this sheet (thanx to Al Canton)
//		metric		: type of unit used in this description
//				  You can define your label properties in inches by setting metric to 'in'
//				  and printing in millimiter by setting unit to 'mm' in constructor.
//	  Added some labels :
//	        5160, 5161, 5162, 5163,5164 : thanx to Al Canton : acanton@adams-blake.com
//		8600 						: thanx to Kunal Walia : kunal@u.washington.edu
//	  + : Added 3mm to the position of labels to avoid errors
////////////////////////////////////////////////////

/**
 *	\file       htdocs/includes/modules/member/labels/pdf_standardlabel.class.php
 *	\ingroup    member
 *	\brief      Fichier de la classe permettant d'editer au format PDF des etiquettes au format Avery ou personnalise
 *	\author     Steve Dillon
 *	\author	    Laurent Passebecq
 *	\author	    Rodolphe Quiedville
 *	\author	    Jean Louis Bergamo.
 */

require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/lib/format_cards.lib.php');


/**
 *	\class      pdf_standardlabel
 *	\brief      Classe afin d'editer au format PDF des pages d'etiquette adresse au format Avery ou personnalise
 */
class pdf_standardlabel {

	var $code;		// Code of format
	var $format;	// Array with informations

	// Proprietes privees
	var $_Avery_Name	= '';	// Nom du format de l'etiquette
	var $_Margin_Left	= 0;	// Marge de gauche de l'etiquette
	var $_Margin_Top	= 0;	// marge en haut de la page avant la premiere etiquette
	var $_X_Space 	= 0;	// Espace horizontal entre 2 bandes d'etiquettes
	var $_Y_Space 	= 0;	// Espace vertical entre 2 bandes d'etiquettes
	var $_X_Number 	= 0;	// NX Nombre d'etiquettes sur la largeur de la page
	var $_Y_Number 	= 0;	// NY Nombre d'etiquettes sur la hauteur de la page
	var $_Width 		= 0;	// Largeur de chaque etiquette
	var $_Height 		= 0;	// Hauteur de chaque etiquette
	var $_Char_Size	= 10;	// Hauteur des caracteres
	var $_Line_Height	= 10;	// Hauteur par defaut d'une ligne
	var $_Metric 		= 'mm';	// Type of metric.. Will help to calculate good values
	var $_Metric_Doc 	= 'mm';	// Type of metric for the doc..

	var $_COUNTX = 1;
	var $_COUNTY = 1;
	var $_First = 1;



	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$DB      Database handler
	 */
	function pdf_standard($db)
	{
		$this->db = $db;
	}

	//Methode qui permet de modifier la taille des caracteres
	// Cela modiera aussi l'espace entre chaque ligne
	function Set_Char_Size(&$pdf,$pt) {
		if ($pt > 3) {
			$this->_Char_Size = $pt;
			$this->_Line_Height = $this->_Get_Height_Chars($pt);
			$pdf->SetFont('','',$pt);
		}
	}


	// On imprime une etiquette
	function Add_PDF_card(&$pdf,$textleft,$header='',$footer='',$outputlangs,$textright='')
	{
		global $mysoc,$conf,$langs;

		// We are in a new page, then we must add a page
		if (($this->_COUNTX ==0) and ($this->_COUNTY==0) and (!$this->_First==1)) {
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

		// Print lines
		if ($this->code == "CARD")
		{
			$this->Tformat=$this->_Avery_Labels["CARD"];
			//$this->_Pointille($pdf,$_PosX,$_PosY,$_PosX+$this->_Width,$_PosY+$this->_Height,0.3,25);
			$this->_Croix($pdf,$_PosX,$_PosY,$_PosX+$this->_Width,$_PosY+$this->_Height,0.1,10);
		}

		// Top
		if ($header!='')
		{
			if ($this->code == "CARD")
			{
				$pdf->SetDrawColor(128,128,128);
				$pdf->Line($_PosX, $_PosY+$this->_Line_Height+1, $_PosX+$this->_Width, $_PosY+$this->_Line_Height+1);
				$pdf->SetDrawColor(0,0,0);
			}
			$pdf->SetXY($_PosX, $_PosY+1);
			$pdf->Cell($this->_Width, $this->_Line_Height, $outputlangs->convToOutputCharset($header),0,1,'C');
		}

		// Center
		if ($textright=='')	// Only a left part
		{
			if ($textleft == '%LOGO%' && $logo) $this->Image($logo,$_PosX+2,$_PosY+3+$this->_Line_Height,20);
			else if ($textleft == '%PHOTO%' && $photo) $this->Image($photo,$_PosX+2,$_PosY+3+$this->_Line_Height,20);
			else
			{
				$pdf->SetXY($_PosX+3, $_PosY+3+$this->_Line_Height);
				$pdf->MultiCell($this->_Width, $this->_Line_Height, $outputlangs->convToOutputCharset($textleft));
			}
		}
		else if ($textleft!='' && $textright!='')	//
		{
			if ($textleft == '%LOGO%' || $textleft == '%PHOTO%')
			{
				if ($textleft == '%LOGO%' && $logo) $pdf->Image($logo,$_PosX+2,$_PosY+3+$this->_Line_Height,20);
				else if ($textleft == '%PHOTO%' && $photo) $pdf->Image($photo,$_PosX+2,$_PosY+3+$this->_Line_Height,20);
				$pdf->SetXY($_PosX+21, $_PosY+3+$this->_Line_Height);
				$pdf->MultiCell($this->_Width-22, $this->_Line_Height, $outputlangs->convToOutputCharset($textright),0,'R');
			}
			else if ($textright == '%LOGO%' || $textright == '%PHOTO%')
			{
				if ($textright == '%LOGO%' && $logo) $pdf->Image($logo,$_PosX+$this->_Width-21,$_PosY+3+$this->_Line_Height,20);
				else if ($textright == '%PHOTO%' && $photo) $pdf->Image($photo,$_PosX+$this->_Width-21,$_PosY+3+$this->_Line_Height,20);
				$pdf->SetXY($_PosX+2, $_PosY+3+$this->_Line_Height);
				$pdf->MultiCell($this->_Width-22, $this->_Line_Height, $outputlangs->convToOutputCharset($textleft));
			}
			else
			{
				$pdf->SetXY($_PosX+2, $_PosY+3+$this->_Line_Height);
				$pdf->MultiCell(round($this->_Width/2), $this->_Line_Height, $outputlangs->convToOutputCharset($textleft));
				$pdf->SetXY($_PosX+round($this->_Width/2), $_PosY+3+$this->_Line_Height);
				$pdf->MultiCell(round($this->_Width/2)-2, $this->_Line_Height, $outputlangs->convToOutputCharset($textright),0,'R');
			}
		}
		else	// Only a right part
		{
			if ($textright == '%LOGO%' && $logo) $this->Image($logo,$_PosX+$this->_Width-21,$_PosY+1,20);
			else if ($textright == '%PHOTO%' && $photo) $this->Image($photo,$_PosX+$this->_Width-21,$_PosY+1,20);
			else
			{
				$pdf->SetXY($_PosX+2, $_PosY+3+$this->_Line_Height);
				$pdf->MultiCell($this->_Width, $this->_Line_Height, $outputlangs->convToOutputCharset($textright),0,'R');
			}
		}

		// Bottom
		if ($footer!='')
		{
			if ($this->code == "CARD")
			{
				$pdf->SetDrawColor(128,128,128);
				$pdf->Line($_PosX, $_PosY+$this->_Height-$this->_Line_Height-2, $_PosX+$this->_Width, $_PosY+$this->_Height-$this->_Line_Height-2);
				$pdf->SetDrawColor(0,0,0);
			}
			$pdf->SetXY($_PosX, $_PosY+$this->_Height-$this->_Line_Height-1);
			$pdf->Cell($this->_Width, $this->_Line_Height, $outputlangs->convToOutputCharset($footer),0,1,'C');
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


	function _Pointille(&$pdf,$x1=0,$y1=0,$x2=210,$y2=297,$epaisseur=1,$nbPointilles=15)
	{
		$pdf->SetLineWidth($epaisseur);
		$length=abs($x1-$x2);
		$hauteur=abs($y1-$y2);
		if($length>$hauteur) {
			$Pointilles=($length/$nbPointilles)/2; // taille des pointilles
		}
		else {
			$Pointilles=($hauteur/$nbPointilles)/2;
		}
		for($i=$x1;$i<=$x2;$i+=$Pointilles+$Pointilles) {
			for($j=$i;$j<=($i+$Pointilles);$j++) {
				if($j<=($x2-1)) {
		$pdf->Line($j,$y1,$j+1,$y1); // on trace le pointill? du haut, point par point
		$pdf->Line($j,$y2,$j+1,$y2); // on trace le pointill? du bas, point par point
				}
			}
		}
		for($i=$y1;$i<=$y2;$i+=$Pointilles+$Pointilles) {
			for($j=$i;$j<=($i+$Pointilles);$j++) {
				if($j<=($y2-1)) {
		$pdf->Line($x1,$j,$x1,$j+1); // on trace le pointill? du haut, point par point
		$pdf->Line($x2,$j,$x2,$j+1); // on trace le pointill? du bas, point par point
				}
			}
		}
	}

	/*
	 * Fonction realisant une croix aux 4 coins des cartes
	 */
	function _Croix(&$pdf,$x1=0,$y1=0,$x2=210,$y2=297,$epaisseur=1,$taille=4)
	{
		$pdf->SetDrawColor(192,192,192);

		$pdf->SetLineWidth($epaisseur);
		$lg=$taille/2;
		// croix haut gauche
		$pdf->Line($x1,$y1-$lg,$x1,$y1+$lg);
		$pdf->Line($x1-$lg,$y1,$x1+$lg,$y1);
		// croix bas gauche
		$pdf->Line($x1,$y2-$lg,$x1,$y2+$lg);
		$pdf->Line($x1-$lg,$y2,$x1+$lg,$y2);
		// croix haut droit
		$pdf->Line($x2,$y1-$lg,$x2,$y1+$lg);
		$pdf->Line($x2-$lg,$y1,$x2+$lg,$y1);
		// croix bas droit
		$pdf->Line($x2,$y2-$lg,$x2,$y2+$lg);
		$pdf->Line($x2-$lg,$y2,$x2+$lg,$y2);

		$pdf->SetDrawColor(0,0,0);
	}

	// convert units (in to mm, mm to in)
	// $src and $dest must be 'in' or 'mm'
	function _Convert_Metric ($value, $src, $dest) {
		if ($src != $dest) {
			$tab['in'] = 39.37008;
			$tab['mm'] = 1000;
			return $value * $tab[$dest] / $tab[$src];
		} else {
			return $value;
		}
	}

	// Give the height for a char size given.
	function _Get_Height_Chars($pt) {
		// Tableau de concordance entre la hauteur des caracteres et de l'espacement entre les lignes
		$_Table_Hauteur_Chars = array(6=>2, 7=>2.5, 8=>3, 9=>3.5, 10=>4, 11=>6, 12=>7, 13=>8, 14=>9, 15=>10);
		if (in_array($pt, array_keys($_Table_Hauteur_Chars))) {
			return $_Table_Hauteur_Chars[$pt];
		} else {
			return 100; // There is a prob..
		}
	}

	function _Set_Format(&$pdf, $format) {
		$this->_Metric 	= $format['metric'];
		$this->_Avery_Name 	= $format['name'];
		$this->_Avery_Code	= $format['code'];
		$this->_Margin_Left	= $this->_Convert_Metric($format['marginLeft'], $this->_Metric, $this->_Metric_Doc);
		$this->_Margin_Top	= $this->_Convert_Metric($format['marginTop'], $this->_Metric, $this->_Metric_Doc);
		$this->_X_Space 	= $this->_Convert_Metric($format['SpaceX'], $this->_Metric, $this->_Metric_Doc);
		$this->_Y_Space 	= $this->_Convert_Metric($format['SpaceY'], $this->_Metric, $this->_Metric_Doc);
		$this->_X_Number 	= $format['NX'];
		$this->_Y_Number 	= $format['NY'];
		$this->_Width 	= $this->_Convert_Metric($format['width'], $this->_Metric, $this->_Metric_Doc);
		$this->_Height	= $this->_Convert_Metric($format['height'], $this->_Metric, $this->_Metric_Doc);
		$this->Set_Char_Size($pdf, $format['font-size']);
	}


    /**
     *      \brief      Function to build PDF on disk, then output on HTTP strem.
     *      \param      arrayofmembers  Array of members informations
     *      \param      outputlangs     Lang object for output language
     *      \return     int             1=ok, 0=ko
     */
    function write_file($arrayofmembers,$outputlangs)
    {
        global $user,$conf,$langs,$mysoc,$_Avery_Labels;

        // Choose type (L7163 by default)
        $this->code=empty($conf->global->ADHERENT_ETIQUETTE_TYPE)?'L7163':$conf->global->ADHERENT_ETIQUETTE_TYPE;
        $this->Tformat = $_Avery_Labels[$this->code];
        if (empty($this->Tformat)) { dol_print_error('','ErrorBadTypeForCard'.$this->code); exit; }
        $this->type = 'pdf';
        $this->format = $this->Tformat['paper-size'];

        if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

        $outputlangs->load("main");
        $outputlangs->load("dict");
        $outputlangs->load("companies");
        $outputlangs->load("members");
        $outputlangs->load("admin");


        $dir = $conf->adherent->dir_temp;
        $file = $dir . "/tmplabels.pdf";

        if (! file_exists($dir))
        {
            if (create_exdir($dir) < 0)
            {
                $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                return 0;
            }
        }

        $pdf=pdf_getInstance($this->format,$this->Tformat['metric']);

        if (class_exists('TCPDF'))
        {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }
        $pdf->SetFont(pdf_getPDFFont($outputlangs));

        $pdf->SetTitle($outputlangs->transnoentities('MembersLabels'));
        $pdf->SetSubject($outputlangs->transnoentities("MembersLabels"));
        $pdf->SetCreator("Dolibarr ".DOL_VERSION);
        $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
        $pdf->SetKeyWords($outputlangs->transnoentities('MembersLabels')." ".$outputlangs->transnoentities("Foundation")." ".$outputlangs->convToOutputCharset($mysoc->name));
        if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

        $pdf->SetMargins(0,0);
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
        foreach($arrayofmembers as $val)
        {
            // imprime le texte specifique sur la carte
            $this->Add_PDF_card($pdf,$val['textleft'],$val['textheader'],$val['textfooter'],$langs,$val['textright'],$val['id'],$val['photo']);
        }

        //$pdf->SetXY(10, 295);
        //$pdf->Cell($this->_Width, $this->_Line_Height, 'XXX',0,1,'C');


        // Output to file
        $pdf->Output($file,'F');

        if (! empty($conf->global->MAIN_UMASK))
            @chmod($file, octdec($conf->global->MAIN_UMASK));



        // Output to http stream
        clearstatcache();

        $attachment=true;
        if (! empty($conf->global->MAIN_DISABLE_FORCE_SAVEAS)) $attachment=false;
        $filename='tmplabels.pdf';
        $type=dol_mimetype($filename);

        if ($encoding)   header('Content-Encoding: '.$encoding);
        if ($type)       header('Content-Type: '.$type);
        if ($attachment) header('Content-Disposition: attachment; filename="'.$filename.'"');
        else header('Content-Disposition: inline; filename="'.$filename.'"');

        // Ajout directives pour resoudre bug IE
        header('Cache-Control: Public, must-revalidate');
        header('Pragma: public');

        readfile($file);

        return 1;
    }
}
?>
