<?php
/* Copyright (C) 2003      Steve Dillon
 * Copyright (C) 2003      Laurent Passebecq
 * Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2006-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015      Francis Appels  <francis.appels@yahoo.com>
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

/* Inspire de PDF_Label
 * PDF_Label - PDF label editing
 * @package PDF_Label
 * @author Laurent PASSEBECQ <lpasseb@numericable.fr>
 * @copyright 2003 Laurent PASSEBECQ
 * disponible ici : http://www.fpdf.org/fr/script/script29.php
 */

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
 *	\file       htdocs/core/class/commonstickergenerator.class.php
 *	\ingroup    core
 *	\brief      generate pdf document with labels or cards in Avery or custom format
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/format_cards.lib.php';


/**
 *	Class to generate stick sheet with format Avery or other personalised
 */
abstract class CommonStickerGenerator
{
    public $code; // Code of format

    /**
     * @var array format Array with informations
     */
    public $format;

    // phpcs:disable PEAR.NamingConventions.ValidVariableName.PublicUnderscore
    // protected
    // Nom du format de l'etiquette
    protected $_Avery_Name = '';
    // Marge de gauche de l'etiquette
    protected $_Margin_Left = 0;
    // marge en haut de la page avant la premiere etiquette
    protected $_Margin_Top = 0;
    // Espace horizontal entre 2 bandes d'etiquettes
    protected $_X_Space = 0;
    // Espace vertical entre 2 bandes d'etiquettes
    protected $_Y_Space = 0;
    // NX Nombre d'etiquettes sur la largeur de la page
    protected $_X_Number = 0;
    // NY Nombre d'etiquettes sur la hauteur de la page
    protected $_Y_Number = 0;
    // Largeur de chaque etiquette
    protected $_Width = 0;
    // Hauteur de chaque etiquette
    protected $_Height = 0;
    // Hauteur des caracteres
    protected $_Char_Size = 10;
    // Hauteur par defaut d'une ligne
    protected $_Line_Height = 10;
    // Type of metric.. Will help to calculate good values
    protected $_Metric = 'mm';
    // Type of metric for the doc..
    protected $_Metric_Doc = 'mm';
    protected $_COUNTX = 1;
    protected $_COUNTY = 1;
    protected $_First = 1;
    public $Tformat;
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
	 *  Function to build PDF on disk, then output on HTTP strem.
	 *
	 *  @param	array		$arrayofrecords  	Array of record informations (array('textleft'=>,'textheader'=>, ..., 'id'=>,'photo'=>)
	 *  @param  Translate	$outputlangs     	Lang object for output language
	 *  @param	string		$srctemplatepath	Full path of source filename for generator using a template file
	 *	@param	string		$outputdir			Output directory for pdf file
	 *  @return int             				1=OK, 0=KO
	 */
	public abstract function write_file($arrayofrecords, $outputlangs, $srctemplatepath, $outputdir = '');
    // phpcs:enable

	/**
	 * Output a sticker on page at position _COUNTX, _COUNTY (_COUNTX and _COUNTY start from 0)
	 *
	 * @param   PDF         $pdf            PDF reference
	 * @param   Translate  	$outputlangs    Output langs
	 * @param   array     	$param          Associative array containing label content and optional parameters
	 * @return  void
	 */
	public abstract function addSticker(&$pdf, $outputlangs, $param);

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Methode qui permet de modifier la taille des caracteres
	 * Cela modiera aussi l'espace entre chaque ligne
	 *
	 * @param    PDF        $pdf   PDF reference
	 * @param    int        $pt    point
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
	 * @param	PDF     $pdf                PDF reference
	 * @param 	int		$x1					X1
	 * @param 	int		$y1					Y1
	 * @param 	int		$x2					X2
	 * @param 	int		$y2					Y2
	 * @param 	int		$epaisseur			Epaisseur
	 * @param 	int		$nbPointilles		Nb pointilles
	 * @return	void
	 */
    protected function _Pointille(&$pdf, $x1 = 0, $y1 = 0, $x2 = 210, $y2 = 297, $epaisseur = 1, $nbPointilles = 15)
    {
        // phpcs:enable
		$pdf->SetLineWidth($epaisseur);
		$length = abs($x1 - $x2);
		$hauteur = abs($y1 - $y2);
		if ($length > $hauteur) {
			$Pointilles = ($length / $nbPointilles) / 2; // taille des pointilles
		}
		else {
			$Pointilles = ($hauteur / $nbPointilles) / 2;
		}
		for ($i = $x1; $i <= $x2; $i += $Pointilles + $Pointilles) {
			for ($j = $i; $j <= ($i + $Pointilles); $j++) {
				if ($j <= ($x2 - 1)) {
		            $pdf->Line($j, $y1, $j + 1, $y1); // on trace le pointill? du haut, point par point
		            $pdf->Line($j, $y2, $j + 1, $y2); // on trace le pointill? du bas, point par point
				}
			}
		}
		for ($i = $y1; $i <= $y2; $i += $Pointilles + $Pointilles) {
			for ($j = $i; $j <= ($i + $Pointilles); $j++) {
				if ($j <= ($y2 - 1)) {
		            $pdf->Line($x1, $j, $x1, $j + 1); // on trace le pointill? du haut, point par point
		            $pdf->Line($x2, $j, $x2, $j + 1); // on trace le pointill? du bas, point par point
				}
			}
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * protected Function realisant une croix aux 4 coins des cartes
	 *
	 * @param PDF   $pdf                PDF reference
	 * @param int   $x1					X1
	 * @param int	$y1					Y1
	 * @param int	$x2					X2
	 * @param int	$y2					Y2
	 * @param int	$epaisseur			Epaisseur
	 * @param int	$taille             Size
	 * @return void
	 */
	protected function _Croix(&$pdf, $x1 = 0, $y1 = 0, $x2 = 210, $y2 = 297, $epaisseur = 1, $taille = 4)
	{
        // phpcs:enable
		$pdf->SetDrawColor(192, 192, 192);

		$pdf->SetLineWidth($epaisseur);
		$lg = $taille / 2;
		// croix haut gauche
		$pdf->Line($x1, $y1 - $lg, $x1, $y1 + $lg);
		$pdf->Line($x1 - $lg, $y1, $x1 + $lg, $y1);
		// croix bas gauche
		$pdf->Line($x1, $y2 - $lg, $x1, $y2 + $lg);
		$pdf->Line($x1 - $lg, $y2, $x1 + $lg, $y2);
		// croix haut droit
		$pdf->Line($x2, $y1 - $lg, $x2, $y1 + $lg);
		$pdf->Line($x2 - $lg, $y1, $x2 + $lg, $y1);
		// croix bas droit
		$pdf->Line($x2, $y2 - $lg, $x2, $y2 + $lg);
		$pdf->Line($x2 - $lg, $y2, $x2 + $lg, $y2);

		$pdf->SetDrawColor(0, 0, 0);
	}

	/**
	 * Convert units (in to mm, mm to in)
	 * $src and $dest must be 'in' or 'mm'
	 *
	 * @param int       $value  value
	 * @param string    $src    from ('in' or 'mm')
	 * @param string    $dest   to ('in' or 'mm')
	 * @return float    value   value after conversion
	 */
	private function convertMetric($value, $src, $dest)
	{
		if ($src != $dest) {
			$tab = array(
				'in'=>39.37008,
				'mm'=>1000
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
		$_Table_Hauteur_Chars = array(6=>2, 7=>2.5, 8=>3, 9=>3.5, 10=>4, 11=>6, 12=>7, 13=>8, 14=>9, 15=>10);
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
	 * @param    PDF       $pdf     PDF reference
	 * @param    string    $format  Format
	 * @return   void
	 */
	protected function _Set_Format(&$pdf, $format)
	{
        // phpcs:enable
		$this->_Metric = $format['metric'];
		$this->_Avery_Name = $format['name'];
		$this->_Avery_Code = $format['code'];
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
