<?php
/* Copyright (C) 2003       Steve Dillon
 * Copyright (C) 2003       Laurent Passebecq
 * Copyright (C) 2001-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2006-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2015       Francis Appels          <francis.appels@yahoo.com>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/* Inspired by PDF_Label
 * PDF_Label - PDF label editing
 * @package PDF_Label
 * @author Laurent PASSEBECQ <lpasseb@numericable.fr>
 * @copyright 2003 Laurent PASSEBECQ
 * available here : http://www.fpdf.org/fr/script/script29.php
 */

//-------------------------------------------------------------------
// VERSIONS :
// 1.0  : Initial release
// 1.1  : +	: Added unit in the constructor
//	  + : Now Positions start @ (1,1).. then the first image @top-left of a page is (1,1)
//	  + : Added in the description of a label :
//		font-size	: default char size (can be changed by calling Set_Char_Size(xx);
//		paper-size	: Size of the paper for this sheet (thanx to Al Canton)
//		metric		: type of unit used in this description
//				  You can define your label properties in inches by setting metric to 'in'
//				  and printing in millimeter by setting unit to 'mm' in constructor.
//	  Added some labels :
//	        5160, 5161, 5162, 5163,5164 : thanx to Al Canton : acanton@adams-blake.com
//		8600 						: thanx to Kunal Walia : kunal@u.washington.edu
//	  + : Added 3mm to the position of labels to avoid errors
////////////////////////////////////////////////////

/**
 *	\file       htdocs/core/class/commonstickergenerator.class.php
 *	\ingroup    core
 *	\brief      generate pdf document with labels or cards in Avery or custom format
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/format_cards.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';


/**
 *	Class to generate stick sheet with format Avery or other personalised format
 */
abstract class CommonStickerGenerator extends CommonDocGenerator
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Code of format
	 */
	public $code;

	// phpcs:disable PEAR.NamingConventions.ValidVariableName.PublicUnderscore
	// protected
	/**
	 * @var string Name of the label sheet
	 */
	protected $_Avery_Name = '';

	/**
	 * @var string Code of the labal sheet
	 */
	protected $_Avery_Code = '';

	/**
	 * @var float Left margin of the label
	 */
	protected $_Margin_Left = 0;

	/**
	 * @var float top margin of the page before the first label
	 */
	protected $_Margin_Top = 0;

	/**
	 * @var float Horizontal space between 2 columns of labels
	 */
	protected $_X_Space = 0;

	/**
	 * @var float Vertical space between 2 rows of labels
	 */
	protected $_Y_Space = 0;

	/**
	 * @var int<0,max> NX Number of labels on the width of the page
	 */
	protected $_X_Number = 0;

	/**
	 * @var int<0,max> NY Number of labels on the height of a page
	 */
	protected $_Y_Number = 0;

	/**
	 * @var float Label Width
	 */
	protected $_Width = 0;

	/**
	 * @var float Label Height
	 */
	protected $_Height = 0;

	/**
	 * @var float Character Height
	 */
	protected $_Char_Size = 10;

	/**
	 * @var float Default Height of a line
	 */
	protected $_Line_Height = 10;

	/**
	 * @var 'in'|'mm' Type of metric.. Will help to calculate good values
	 */
	protected $_Metric = 'mm';

	/**
	 * @var 'in'|'mm' Type of metric for the doc..
	 */
	protected $_Metric_Doc = 'mm';

	/**
	 * @var int<0,max>
	 */
	protected $_COUNTX = 1;

	/**
	 * @var int<0,max>
	 */
	protected $_COUNTY = 1;

	/**
	 * @var int<0,max>
	 */
	protected $_First = 1;

	/**
	 * @var ?array{name:string,paper-size:'custom'|array{0:float,1:float},orientation:string,metric:'in'|'mm',marginLeft:float,marginTop:float,NX:int<0,max>,NY:int<0,max>,SpaceX:float,SpaceY:float,width:float,height:float,font-size:int,custom_x:float,custom_y:float}
	 */
	public $Tformat;

	/**
	 * @var ?array<string,array{name:string,paper-size:'custom'|array{0:float,1:float},orientation:string,metric:'in'|'mm',marginLeft:float,marginTop:float,NX:int<0,max>,NY:int<0,max>,SpaceX:float,SpaceY:float,width:float,height:float,font-size:int,custom_x:float,custom_y:float}>
	 */
	public $_Avery_Labels;
	// phpcs:enable

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build PDF on disk, then output on HTTP stream.
	 *
	 *  @param  Adherent|array<array{textleft:string,textheader:string,textfooter:string,textright:string,id:string,photo:string}>   $arrayofrecords     Array of record information (array('textleft'=>,'textheader'=>, ..., 'id'=>,'photo'=>)
	 *  @param  Translate	$outputlangs     	Lang object for output language
	 *  @param	string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param  string		$outputdir			Output directory for pdf file
	 *  @param  string		$filename           Short file name of output file
	 *  @return int<-1,1>                       1=OK, <=0=KO
	 */
	abstract public function write_file($arrayofrecords, $outputlangs, $srctemplatepath, $outputdir = '', $filename = '');
	// phpcs:enable

	/**
	 * Output a sticker on page at position _COUNTX, _COUNTY (_COUNTX and _COUNTY start from 0)
	 *
	 * @param   TCPDF       $pdf            PDF reference
	 * @param   Translate  	$outputlangs    Output langs
	 * @param   array<string,mixed>		$param	Associative array containing label content and optional parameters
	 * @return  void
	 */
	abstract public function addSticker(&$pdf, $outputlangs, $param);

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Method to modify the size of characters
	 * This will also modify the space between lines
	 *
	 * @param    TCPDF      $pdf   PDF reference
	 * @param    int        $pt    Point
	 * @return   void
	 */
	public function Set_Char_Size(&$pdf, $pt)
	{
		// phpcs:enable
		if ($pt > 3) {
			$this->_Char_Size = $pt;
			$this->_Line_Height = $this->_Get_Height_Chars($pt);
			$pdf->SetFont('', '', $pt);
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * protected Print dot line
	 *
	 * @param	TCPDF   $pdf                PDF reference
	 * @param 	int		$x1					X1
	 * @param 	int		$y1					Y1
	 * @param 	int		$x2					X2
	 * @param 	int		$y2					Y2
	 * @param 	int		$epaisseur			Thickness
	 * @param 	int		$nbPointilles		Nb of dots
	 * @return	void
	 */
	protected function _Pointille(&$pdf, $x1 = 0, $y1 = 0, $x2 = 210, $y2 = 297, $epaisseur = 1, $nbPointilles = 15)
	{
		// phpcs:enable
		$pdf->SetLineWidth($epaisseur);
		$length = abs($x1 - $x2);
		$hauteur = abs($y1 - $y2);
		if ($length > $hauteur) {
			$Pointilles = ($length / $nbPointilles) / 2; // size of the dots
		} else {
			$Pointilles = ($hauteur / $nbPointilles) / 2;
		}
		for ($i = $x1; $i <= $x2; $i += $Pointilles + $Pointilles) {
			for ($j = $i; $j <= ($i + $Pointilles); $j++) {
				if ($j <= ($x2 - 1)) {
					// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
					$pdf->Line($j, $y1, $j + 1, $y1); // we trace the top dot, point by point
					// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
					$pdf->Line($j, $y2, $j + 1, $y2); // we trace the bottom dot, point by point
				}
			}
		}
		for ($i = $y1; $i <= $y2; $i += $Pointilles + $Pointilles) {
			for ($j = $i; $j <= ($i + $Pointilles); $j++) {
				if ($j <= ($y2 - 1)) {
					// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
					$pdf->Line($x1, $j, $x1, $j + 1); // we trace the top dot, point by point
					// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
					$pdf->Line($x2, $j, $x2, $j + 1); // we trace the bottom dot, point by point
				}
			}
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * protected Function making a cross at the 4 corners of the labels
	 *
	 * @param TCPDF $pdf                PDF reference
	 * @param float $x1					X1
	 * @param float	$y1					Y1
	 * @param float	$x2					X2
	 * @param float	$y2					Y2
	 * @param float	$epaisseur			Thickness
	 * @param int	$taille             Size
	 * @return void
	 *
	 * @phan-suppress PhanPluginSuspiciousParamPosition
	 */
	protected function _Croix(&$pdf, $x1 = 0, $y1 = 0, $x2 = 210, $y2 = 297, $epaisseur = 1, $taille = 4)
	{
		// phpcs:enable
		$pdf->SetDrawColor(192, 192, 192);

		$pdf->SetLineWidth($epaisseur);
		$lg = $taille / 2;
		// top left cross
		$pdf->Line($x1, $y1 - $lg, $x1, $y1 + $lg);
		$pdf->Line($x1 - $lg, $y1, $x1 + $lg, $y1);
		// bottom left cross
		$pdf->Line($x1, $y2 - $lg, $x1, $y2 + $lg);
		$pdf->Line($x1 - $lg, $y2, $x1 + $lg, $y2);
		// top right cross
		$pdf->Line($x2, $y1 - $lg, $x2, $y1 + $lg);
		$pdf->Line($x2 - $lg, $y1, $x2 + $lg, $y1);
		// bottom right cross
		$pdf->Line($x2, $y2 - $lg, $x2, $y2 + $lg);
		$pdf->Line($x2 - $lg, $y2, $x2 + $lg, $y2);

		$pdf->SetDrawColor(0, 0, 0);
	}

	/**
	 * Convert units (in to mm, mm to in)
	 * $src and $dest must be 'in' or 'mm'
	 *
	 * @param float     $value  value
	 * @param 'in'|'mm' $src    from ('in' or 'mm')
	 * @param 'in'|'mm' $dest   to ('in' or 'mm')
	 * @return float    value   value after conversion
	 */
	private function convertMetric($value, $src, $dest)
	{
		if ($src != $dest) {
			$tab = array(
				'in' => 39.37008,
				'mm' => 1000
			);
			return $value * $tab[$dest] / $tab[$src];
		}

		return $value;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * protected Give the height for a char size given.
	 *
	 * @param  int    $pt    Point
	 * @return int           Height chars
	 */
	protected function _Get_Height_Chars($pt)
	{
		// phpcs:enable
		// Array for link between height of characters and space between lines
		$_Table_Hauteur_Chars = array(6 => 2, 7 => 2.5, 8 => 3, 9 => 3.5, 10 => 4, 11 => 6, 12 => 7, 13 => 8, 14 => 9, 15 => 10);
		if (in_array($pt, array_keys($_Table_Hauteur_Chars))) {
			return $_Table_Hauteur_Chars[$pt];
		} else {
			return 100; // There is a prob..
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * protected Set format
	 *
	 * @param    TCPDF     $pdf     PDF reference
	 * @param    array{metric:'in'|'mm',name:string,code?:string,marginLeft:float,marginTop:float,SpaceX:float,SpaceY:float,NX:int<0,max>,NY:int<0,max>,width:float,height:float,font-size:int}	$format  Format
	 * @return   void
	 */
	protected function _Set_Format(&$pdf, $format)
	{
		// phpcs:enable
		$this->_Metric = $format['metric'];
		$this->_Avery_Name = $format['name'];
		$this->_Avery_Code = empty($format['code']) ? '' : $format['code'];
		$this->_Margin_Left = $this->convertMetric($format['marginLeft'], $this->_Metric, $this->_Metric_Doc);
		$this->_Margin_Top = $this->convertMetric($format['marginTop'], $this->_Metric, $this->_Metric_Doc);
		$this->_X_Space = $this->convertMetric($format['SpaceX'], $this->_Metric, $this->_Metric_Doc);
		$this->_Y_Space = $this->convertMetric($format['SpaceY'], $this->_Metric, $this->_Metric_Doc);
		$this->_X_Number = $format['NX'];
		$this->_Y_Number = $format['NY'];
		$this->_Width = $this->convertMetric($format['width'], $this->_Metric, $this->_Metric_Doc);
		$this->_Height = $this->convertMetric($format['height'], $this->_Metric, $this->_Metric_Doc);
		$this->Set_Char_Size($pdf, $format['font-size']);
	}
}
