<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2012      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025	   Jean-Rémi TAPONIER   <jean-remi@netlogic.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *  \file			htdocs/core/modules/accountancy/modules_accountancy.php
 *  \ingroup		accountancy
 *  \brief			File that contains parent class for orders models
 *                  and parent class for accountancy numbering models
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonnumrefgenerator.class.php';


/**
 *	Parent class of accountancy models
 */
abstract class ModelePdfAccountancy extends CommonDocGenerator
{
	/**
	 * @var int $fromDate Start timestamp
	 */
	public $fromDate;

	/**
	 * @var int $toDate Start timestamp
	 */
	public $toDate;

	/**
	 * @var array<int,array<array{start:int|float,end:int|float}>> $verticalLinesSpacesCoordinates Array to store vertical coordinates where vertical column lines should be avoid

	 */
	public $verticalLinesSpacesCoordinates = [];

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation modules
	 *
	 *  @param  DoliDB  	$db                 Database handler
	 *  @param  int<0,max>	$maxfilenamelength  Max length of value to show
	 *  @return string[]|int<-1,0>				List of templates
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		// phpcs:enable
		$type = 'accountancy';
		$list = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$list = getListOfModels($db, $type, $maxfilenamelength);

		return $list;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Function to build pdf onto disk
	 *
	 * @param		BookKeeping	$object				Object source to build document
	 * @param		Translate	$outputlangs		Lang output object
	 * @param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 * @param		bool		$directDownload		Send the generated pdf to the browser
	 * @return		int<-1,1>						1 if OK, <=0 if KO
	 */
	abstract public function write_file(BookKeeping $object, Translate $outputlangs, string $srctemplatepath = '', bool $directDownload = true);
	// phpcs:enable

	/**
	 * Add dash line
	 *
	 * @param TCPDF 	$pdf 	TCPDF object
	 * @param int 		$page 	Page number
	 * @param int|float $y		Y position
	 * @return void
	 */
	protected function addDashLine(TCPDF $pdf, int $page, $y)
	{
		// Add line
		$pdf->setPage($page);
		$pdf->SetLineStyle(array('dash' => '1,1', 'color' => array(80, 80, 80)));
		//$pdf->SetDrawColor(190,190,200);
		$pdf->line($this->marge_gauche, $y, $this->page_largeur - $this->marge_droite, $y);
		$pdf->SetLineStyle(array('dash' => 0));
	}

	/**
	 * Add a total line to pdf
	 *
	 * @param TCPDF 			$pdf 				TCPDF object
	 * @param int|float 		$curY 				Current line Y
	 * @param int|float 		$nexY 				Next line Y
	 * @param int|float 		$default_font_size 	Default font size
	 * @param string 			$label 				Line label
	 * @param int|float 		$tab_top_newpage	Table top
	 * @param int|float|string 	$debit				Debit
	 * @param int|float|string 	$credit				Credit
	 * @param bool 				$uppercase			Apply uppercase ?
	 * @return void
	 */
	abstract protected function addTotalLine(TCPDF $pdf, &$curY, &$nexY, $default_font_size, string $label, $tab_top_newpage, $debit, $credit, bool $uppercase = true);

	/**
	 * Add the total accountancy group line to pdf
	 *
	 * @param TCPDF 	$pdf 				TCPDF object
	 * @param float $curY 				Current line Y
	 * @param float $nexY				Next line Y
	 * @param int|float $default_font_size	Default font size
	 * @param string 	$columnKey 			Column where to place title
	 * @param string 	$label 				Line label
	 * @param int|float $tab_top_newpage 	Table top
	 * @param bool 		$uppercase 			Apply uppercase ?
	 * @return void
	 */
	protected function addTitleLine(TCPDF $pdf, &$curY, &$nexY, $default_font_size, string $columnKey, string $label, $tab_top_newpage, bool $uppercase = true)
	{
		$curY = $nexY;
		$pageposbefore = $pdf->getPage();
		$pdf->SetFont('', 'B', $default_font_size);
		$pdf->startTransaction();

		if ($uppercase) {
			$label = mb_strtoupper($label);
		}
		$this->printTitleContent($pdf, $curY, $columnKey, $label);

		$pageposafter = $pdf->getPage();
		if ($pageposafter > $pageposbefore) {    // There is a pagebreak
			$pdf->rollbackTransaction(true);

			$pdf->AddPage('', '', true);
			$pdf->setPage($pageposafter);
			$curY = $tab_top_newpage + $this->tabTitleHeight;
			$this->printTitleContent($pdf, $curY, $columnKey, $label);
		}

		$nexY = $pdf->GetY();

		$this->verticalLinesSpacesCoordinates[$pdf->getPage()][] = ['start' => $curY, 'end' => $nexY];
		if (getDolGlobalString('MAIN_PDF_DASH_BETWEEN_LINES')) {
			$this->addDashLine($pdf, $pageposafter, $nexY);
		}
	}

	/**
	 * Print a title using the colKey start position, and the end of table as end position
	 *
	 * @param TCPDF 		$pdf			TCPDF object
	 * @param int|float 	$curY			Current line Y
	 * @param string 		$colKey 		Column key name
	 * @param string 		$columnText		Title text
	 * @return void
	 */
	protected function printTitleContent($pdf, $curY, $colKey, $columnText)
	{
		$pdf->SetXY($this->getColumnContentXStart($colKey), $curY); // Set current position
		$colDef = $this->cols[$colKey];
		// save current cell padding
		$curentCellPaddinds = $pdf->getCellPaddings();
		// set cell padding with column content definition
		$pdf->setCellPaddings(isset($colDef['content']['padding'][3]) ? $colDef['content']['padding'][3] : 0, isset($colDef['content']['padding'][0]) ? $colDef['content']['padding'][0] : 0, isset($colDef['content']['padding'][1]) ? $colDef['content']['padding'][1] : 0, isset($colDef['content']['padding'][2]) ? $colDef['content']['padding'][2] : 0);
		$pdf->writeHTMLCell($this->page_largeur - $this->marge_droite, 2, isset($colDef['xStartPos']) ? $colDef['xStartPos'] : 0, $curY, $columnText, 0, 1, false, true, $colDef['content']['align']);
		$this->setAfterColsLinePositionsData($colKey, $pdf->GetY(), $pdf->getPage());

		// restore cell padding
		$pdf->setCellPaddings($curentCellPaddinds['L'], $curentCellPaddinds['T'], $curentCellPaddinds['R'], $curentCellPaddinds['B']);
	}

	/**
	 * Print standard column content
	 *
	 * @param TCPDI|TCPDF	$pdf            Pdf object
	 * @param float			$tab_top        Tab top position
	 * @param float			$tab_height     Default tab height
	 * @param Translate		$outputlangs    Output language
	 * @param int			$hidetop        Hide top
	 * @return float						Height of col tab titles
	 */
	public function pdfTabTitles(&$pdf, $tab_top, $tab_height, $outputlangs, $hidetop = 0)
	{
		global $hookmanager, $conf;

		foreach ($this->cols as $colKey => $colDef) {
			$parameters = array(
				'colKey' => $colKey,
				'pdf' => $pdf,
				'outputlangs' => $outputlangs,
				'tab_top' => $tab_top,
				'tab_height' => $tab_height,
				'hidetop' => $hidetop
			);

			$reshook = $hookmanager->executeHooks('pdfTabTitles', $parameters, $this); // Note that $object may have been modified by hook
			if ($reshook < 0) {
				setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
			} elseif (empty($reshook)) {
				if (!$this->getColumnStatus($colKey)) {
					continue;
				}

				// get title label
				$colDef['title']['label'] = !empty($colDef['title']['label']) ? $colDef['title']['label'] : $outputlangs->transnoentities($colDef['title']['textkey']);

				// Add column separator
				if (!empty($colDef['border-left']) && isset($colDef['xStartPos'])) {
					// Use title coordinates if exists
					if (!empty($this->verticalLinesSpacesCoordinates[$pdf->getPage()])) {
						$coordinates = $this->verticalLinesSpacesCoordinates[$pdf->getPage()];
						array_unshift($coordinates, ['start' => null, 'end' => $tab_top]);
						$coordinates[] = ['start' => $tab_top + $tab_height, 'end' => null];

						foreach ($coordinates as $key => $yCoordinates) {
							if (!isset($coordinates[$key-1]['end'])) {
								continue;
							}
							$pdf->line($colDef['xStartPos'], $coordinates[$key-1]['end'], $colDef['xStartPos'], $yCoordinates['start']);
						}
					} else {
						$pdf->line($colDef['xStartPos'], $tab_top, $colDef['xStartPos'], $tab_top + $tab_height);
					}
				}

				if (empty($hidetop)) {
					// save current cell padding
					$curentCellPaddinds = $pdf->getCellPaddings();

					// Add space for lines (more if we need to show a second alternative language)
					global $outputlangsbis;
					if (is_object($outputlangsbis)) {
						// set cell padding with column title definition
						$pdf->setCellPaddings($colDef['title']['padding'][3], $colDef['title']['padding'][0], $colDef['title']['padding'][1], 0.5);
					} else {
						// set cell padding with column title definition
						$pdf->setCellPaddings($colDef['title']['padding'][3], $colDef['title']['padding'][0], $colDef['title']['padding'][1], $colDef['title']['padding'][2]);
					}
					if (isset($colDef['title']['align'])) {
						$align = $colDef['title']['align'];
					} else {
						$align = '';
					}
					$pdf->SetXY($colDef['xStartPos'], $tab_top);
					$textWidth = $colDef['width'];
					$pdf->MultiCell($textWidth, 2, $colDef['title']['label'], '', $align);

					// Add variant of translation if $outputlangsbis is an object
					if (is_object($outputlangsbis) && trim($colDef['title']['label'])) {
						$pdf->setCellPaddings($colDef['title']['padding'][3], 0, $colDef['title']['padding'][1], $colDef['title']['padding'][2]);
						$pdf->SetXY($colDef['xStartPos'], $pdf->GetY());
						$textbis = $outputlangsbis->transnoentities($colDef['title']['textkey']);
						$pdf->MultiCell($textWidth, 2, $textbis, '', $align);
					}

					$this->tabTitleHeight = max($pdf->GetY() - $tab_top, $this->tabTitleHeight);

					// restore cell padding
					$pdf->setCellPaddings($curentCellPaddinds['L'], $curentCellPaddinds['T'], $curentCellPaddinds['R'], $curentCellPaddinds['B']);
				}
			}
		}

		return $this->tabTitleHeight;
	}
}

/**
 *  Parent class to manage numbering of Sale Orders
 */
abstract class ModeleNumRefBookkeeping extends CommonNumRefGenerator
{
	/**
	 * 	Return next free value
	 *
	 *  @param  BookKeeping		$object		Object we need next value for
	 *  @return string|int<-1,0>		Value if OK, -1 if KO
	 */
	abstract public function getNextValue(BookKeeping $object);


	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	abstract public function getExample();
}
