<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/PdfDocTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 *      			See also BuildDocTest to PDF generation
 */

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/pdf.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/doc.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class PdfDocTest extends CommonClassTest
{
	/**
	 * testPdfDocGetLineDesc
	 *
	 * @return void
	 */
	public function testPdfDocGetLineDesc()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localproduct = new Product($db);
		$result = $localproduct->fetch(0, 'PINKDRESS');
		if ($result < 0) {
			print "\n".__METHOD__." Failed to make the fetch of product PINKDRESS. ".$localproduct->error;
			die(1);
		}
		$product_id = $localproduct->id;
		if ($product_id <= 0) {
			print "\n".__METHOD__." A product with ref PINKDRESS must exists into database. Create it manually before running the test";
			die(1);
		}

		$localobject = new Facture($db);
		$localobject->initAsSpecimen();
		$localobject->lines = array();
		$localobject->lines[0] = new FactureLigne($db);
		$localobject->lines[0]->fk_product = $product_id;
		$localobject->lines[0]->label = 'Label 1';
		$localobject->lines[0]->desc = "This is a description with a é accent\n(Country of origin: France)";

		$result = pdf_getlinedesc($localobject, 0, $langs);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("PINKDRESS - Label 1<br>This is a description with a &eacute; accent<br>(Country of origin: France)", $result);

		$result = doc_getlinedesc($localobject->lines[0], $langs);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("PINKDRESS - Label 1\nThis is a description with a é accent\n(Country of origin: France)", $result);
	}

	/**
	* testPdfGetHeightForLogo
	*
	* @return void
	*/
	public function testPdfGetHeightForLogo()
	{
		$file = dirname(__FILE__).'/img250x50.jpg';
		$result = pdf_getHeightForLogo($file);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, 20);
		$file = dirname(__FILE__).'/img250x20.png';
		$result = pdf_getHeightForLogo($file);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, 10.4);
	}

	/**
	 * Build a minimal PDF document with a classic xref table
	 *
	 * @param	array<int,string>	$objects	Content of each object, the key is the object number
	 * @param	string				$trailer	Entries to add into the trailer
	 * @param	int					$shift		Value added to each offset of the xref table (to make it wrong)
	 * @param	string				$sep		What separates the object number, the generation number and the keyword obj
	 * @return	string							Content of the PDF document
	 */
	private function buildPdf($objects, $trailer = '', $shift = 0, $sep = ' ')
	{
		$out = "%PDF-1.4\n";
		$offsets = [];
		ksort($objects);
		foreach ($objects as $num => $content) {
			$offsets[$num] = strlen($out);
			$out .= $num.$sep.'0'.$sep."obj\n".$content."\nendobj\n";
		}
		$size = max(array_keys($objects)) + 1;
		$startxref = strlen($out);
		$out .= "xref\n0 ".$size."\n0000000000 65535 f \n";
		for ($num = 1; $num < $size; $num++) {
			$out .= isset($offsets[$num]) ? sprintf("%010d 00000 n \n", $offsets[$num] + $shift) : "0000000000 65535 f \n";
		}
		$out .= "trailer\n<< /Size ".$size." /Root 1 0 R ".$trailer.">>\nstartxref\n".$startxref."\n%%EOF\n";

		return $out;
	}

	/**
	 * Build a minimal PDF document the way cairo does: the objects are in an object stream whose length is an indirect
	 * object declared after it, and the xref is a stream that does not use any predictor.
	 *
	 * @param	array<int,string>	$objects	Content of each object stored into the object stream, the key is the object number
	 * @param	string				$content	Content stream of the page (object 4)
	 * @param	string				$sep		What separates the numbers into the header of the object stream
	 * @param	string				$filter		Value of the /Filter entry of the object stream
	 * @return	string							Content of the PDF document
	 */
	private function buildPdfWithObjectStream($objects, $content, $sep = ' ', $filter = '/FlateDecode')
	{
		$out = "%PDF-1.5\n";
		$entries = [];	// object number => type, field 2, field 3

		$entries[4] = [1, strlen($out), 0];
		$out .= "4 0 obj\n<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream\nendobj\n";

		$header = '';
		$body = '';
		$index = 0;
		foreach ($objects as $num => $object) {
			$header .= $num.$sep.strlen($body).$sep;
			$body .= $object."\n";
			$entries[$num] = [2, 6, $index++];
		}
		$data = gzcompress($header.$body);
		$entries[6] = [1, strlen($out), 0];
		$out .= "6 0 obj\n<< /Type /ObjStm /N ".count($objects)." /First ".strlen($header)." /Filter ".$filter." /Length 8 0 R >>\nstream\n".$data."\nendstream\nendobj\n";
		$entries[8] = [1, strlen($out), 0];
		$out .= "8 0 obj\n".strlen($data)."\nendobj\n";

		$startxref = strlen($out);
		$entries[7] = [1, $startxref, 0];
		$rows = '';
		for ($num = 0; $num < 9; $num++) {
			$entry = isset($entries[$num]) ? $entries[$num] : [0, 0, 255];
			$rows .= pack('Cnn', $entry[0], $entry[1], $entry[2]);
		}
		$data = gzcompress($rows);
		$out .= "7 0 obj\n<< /Type /XRef /Size 9 /W [1 2 2] /Root 1 0 R /Filter /FlateDecode /Length ".strlen($data)." >>\nstream\n".$data."\nendstream\nendobj\n";
		$out .= "startxref\n".$startxref."\n%%EOF\n";

		return $out;
	}

	/**
	 * testPdfImportPageWithTcpdi
	 * Import a page of PDF documents written the way some PDF producers write them.
	 *
	 * @return void
	 */
	public function testPdfImportPageWithTcpdi()
	{
		pdf_getInstance();	// Load the TCPDF and TCPDI classes and define their constants

		$content = "BT /F1 24 Tf 72 700 Td (Hello TCPDI) Tj ET";
		$base = [
			1 => '<< /Type /Catalog /Pages 2 0 R >>',
			2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
			3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
			4 => "<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream",
			5 => '<< /Type /Font /Subtype /Type1 /BaseFont /TcpdiTestFont >>',
		];
		$instream = $base;
		unset($instream[4]);

		$sources = [];
		$sources['simple document'] = $this->buildPdf($base);

		$objects = $base;
		$objects[3] = str_replace('/Type /Page ', "/Type /Page % a comment\n", $objects[3]);
		$objects[2] = str_replace('[3 0 R]', "[3 0 R % another one\n]", $objects[2]);
		$sources['comment inside an object'] = $this->buildPdf($objects);

		$objects = $base;
		$objects[5] = '<</Type /Font /Subtype /Type1 /BaseFont /TcpdiTestFont /FontDescriptor <</Type /FontDescriptor /Style <</Panose <010502030405060708090a0b>>>>>>>';
		$sources['hexadecimal string closed just before the end of a dictionary (FPDI, xdvipdfmx)'] = $this->buildPdf($objects);

		$objects = $base;
		$objects[5] = "<< /Empty <> /Type /Font /OnTwoLines <0105\n0203> /Subtype /Type1 /BaseFont /TcpdiTestFont >>";
		$sources['empty hexadecimal string and hexadecimal string on two lines'] = $this->buildPdf($objects);

		$sources['object headers with several spaces (HP Exstream)'] = $this->buildPdf($base, '', 0, '        ');
		$sources['object headers on several lines'] = $this->buildPdf($base, '', 0, "\n");
		$sources['offsets of the xref table out of the file'] = $this->buildPdf($base, '', 5000000);
		$sources['wrong startxref'] = str_replace("startxref\n", "startxref\n99", $this->buildPdf($base));

		$objects = $base;
		$objects[2] = '<< /Type /Pages /Kids 6 0 R /Count 1 >>';
		$objects[6] = '[3 0 R]';
		$sources['indirect reference to the list of pages'] = $this->buildPdf($objects);

		$objects = $base;
		$objects[3] = str_replace('/Font << /F1 5 0 R >>', '/Font << /F1 5 0 R >> /Properties << /MC0 6 0 R >>', $objects[3]);
		$objects[6] = '<< /Length 128 /Type /OCG >>';
		$sources['dictionary with a Length entry that is not a stream'] = $this->buildPdf($objects);

		$objects = $base;
		$compressed = substr(gzcompress($content), 0, -4);
		$objects[4] = "<< /Filter /FlateDecode /Length ".strlen($compressed)." >>\nstream\n".$compressed."\nendstream";
		$sources['compressed stream without its end'] = $this->buildPdf($objects);

		$objects = $base;
		$objects[4] = "<< /Length 6 0 R >>\nstream\n".$content."\nendstream";
		$objects[6] = '999';
		$sources['wrong length of a stream'] = $this->buildPdf($objects);

		$sources['object stream and xref stream without predictor (cairo, PDFlib)'] = $this->buildPdfWithObjectStream($instream, $content);
		$sources['header of object stream using CRLF (Amyuni)'] = $this->buildPdfWithObjectStream($instream, $content, "\r\n");
		$sources['filter of object stream into an array'] = $this->buildPdfWithObjectStream($instream, $content, ' ', '[/FlateDecode]');

		foreach ($sources as $label => $source) {
			$pdf = new TCPDI('P', 'mm', 'A4', true, 'UTF-8', false);
			$pdf->setCompression(false);
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);

			$pagecount = $pdf->setSourceData($source);
			print __METHOD__." ".$label." pagecount=".$pagecount."\n";
			$this->assertEquals(1, $pagecount, 'Number of pages for: '.$label);

			$tplidx = $pdf->importPage(1);
			$pdf->AddPage();
			$pdf->useTemplate($tplidx);
			$result = $pdf->Output('', 'S');
			$this->assertTrue(strpos($result, 'Hello TCPDI') !== false, 'Content of the page not found for: '.$label);
			$this->assertTrue(strpos($result, '/BaseFont /TcpdiTestFont') !== false, 'Font of the page not found for: '.$label);
		}

		// An encrypted document can not be imported: the error must say it
		$message = '';
		try {
			$pdf = new TCPDI('P', 'mm', 'A4', true, 'UTF-8', false);
			$pdf->setSourceData($this->buildPdf($base, '/Encrypt 9 0 R '));
		} catch (Exception $e) {
			$message = $e->getMessage();
		}
		print __METHOD__." encrypted document message=".$message."\n";
		$this->assertTrue(strpos($message, 'encrypted') !== false, 'Error on an encrypted document');
	}
}
