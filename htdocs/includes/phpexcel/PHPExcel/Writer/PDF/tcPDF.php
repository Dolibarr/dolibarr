<?php
/**
 * PHPExcel
 *
 * Copyright (c) 2006 - 2012 PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category	PHPExcel
 * @package		PHPExcel_Writer
 * @copyright	Copyright (c) 2006 - 2012 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license		http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version		1.7.8, 2012-10-12
 */


/** Require tcPDF library */
$pdfRendererClassFile = PHPExcel_Settings::getPdfRendererPath() . '/tcpdf.php';
if (file_exists($pdfRendererClassFile)) {
	$k_path_url = PHPExcel_Settings::getPdfRendererPath();
	require_once $pdfRendererClassFile;
} else {
	throw new Exception('Unable to load PDF Rendering library');
}

/**
 * PHPExcel_Writer_PDF_tcPDF
 *
 * @category	PHPExcel
 * @package		PHPExcel_Writer
 * @copyright	Copyright (c) 2006 - 2012 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Writer_PDF_tcPDF extends PHPExcel_Writer_PDF_Core implements PHPExcel_Writer_IWriter {
	/**
	 * Create a new PHPExcel_Writer_PDF
	 *
	 * @param 	PHPExcel	$phpExcel	PHPExcel object
	 */
	public function __construct(PHPExcel $phpExcel) {
		parent::__construct($phpExcel);
	}

	/**
	 * Save PHPExcel to file
	 *
	 * @param 	string 		$pFileName
	 * @throws 	Exception
	 */
	public function save($pFilename = null) {
		// garbage collect
		$this->_phpExcel->garbageCollect();

		$saveArrayReturnType = PHPExcel_Calculation::getArrayReturnType();
		PHPExcel_Calculation::setArrayReturnType(PHPExcel_Calculation::RETURN_ARRAY_AS_VALUE);

		// Open file
		$fileHandle = fopen($pFilename, 'w');
		if ($fileHandle === false) {
			throw new Exception("Could not open file $pFilename for writing.");
		}

		// Set PDF
		$this->_isPdf = true;
		// Build CSS
		$this->buildCSS(true);

		// Default PDF paper size
		$paperSize = 'LETTER';	//	Letter	(8.5 in. by 11 in.)

		// Check for paper size and page orientation
		if (is_null($this->getSheetIndex())) {
			$orientation = ($this->_phpExcel->getSheet(0)->getPageSetup()->getOrientation() == PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE) ? 'L' : 'P';
			$printPaperSize = $this->_phpExcel->getSheet(0)->getPageSetup()->getPaperSize();
			$printMargins = $this->_phpExcel->getSheet(0)->getPageMargins();
		} else {
			$orientation = ($this->_phpExcel->getSheet($this->getSheetIndex())->getPageSetup()->getOrientation() == PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE) ? 'L' : 'P';
			$printPaperSize = $this->_phpExcel->getSheet($this->getSheetIndex())->getPageSetup()->getPaperSize();
			$printMargins = $this->_phpExcel->getSheet($this->getSheetIndex())->getPageMargins();
		}

		//	Override Page Orientation
		if (!is_null($this->getOrientation())) {
			$orientation = ($this->getOrientation() == PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE) ?
				'L' : 'P';
		}
		//	Override Paper Size
		if (!is_null($this->getPaperSize())) {
			$printPaperSize = $this->getPaperSize();
		}


		if (isset(self::$_paperSizes[$printPaperSize])) {
			$paperSize = self::$_paperSizes[$printPaperSize];
		}


		// Create PDF
		$pdf = new TCPDF($orientation, 'pt', $paperSize);
		$pdf->setFontSubsetting(false);
		//	Set margins, converting inches to points (using 72 dpi)
		$pdf->SetMargins($printMargins->getLeft() * 72,$printMargins->getTop() * 72,$printMargins->getRight() * 72);
		$pdf->SetAutoPageBreak(true,$printMargins->getBottom() * 72);
//		$pdf->setHeaderMargin($printMargins->getHeader() * 72);
//		$pdf->setFooterMargin($printMargins->getFooter() * 72);

		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);

		$pdf->AddPage();

		// Set the appropriate font
		$pdf->SetFont($this->getFont());
		$pdf->writeHTML(
			$this->generateHTMLHeader(false) .
			$this->generateSheetData() .
			$this->generateHTMLFooter()
		);

		// Document info
		$pdf->SetTitle($this->_phpExcel->getProperties()->getTitle());
		$pdf->SetAuthor($this->_phpExcel->getProperties()->getCreator());
		$pdf->SetSubject($this->_phpExcel->getProperties()->getSubject());
		$pdf->SetKeywords($this->_phpExcel->getProperties()->getKeywords());
		$pdf->SetCreator($this->_phpExcel->getProperties()->getCreator());

		// Write to file
		fwrite($fileHandle, $pdf->output($pFilename, 'S'));

		// Close file
		fclose($fileHandle);

		PHPExcel_Calculation::setArrayReturnType($saveArrayReturnType);
	}

}
