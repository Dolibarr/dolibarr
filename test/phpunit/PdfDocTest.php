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
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
require_once dirname(__FILE__).'/../../htdocs/comm/propal/class/propal.class.php';
require_once dirname(__FILE__).'/../../htdocs/supplier_proposal/class/supplier_proposal.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/pdf.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/doc.lib.php';

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
	 * testPdfGetLinkedObjectsHidesSupplierProposalOfOtherThirdparty
	 *
	 * @return void
	 */
	public function testPdfGetLinkedObjectsHidesSupplierProposalOfOtherThirdparty()
	{
		global $conf, $db, $langs;

		$hasAllowOtherSoc = isset($conf->global->PDF_ALLOW_DISPLAY_LINKED_OBJECT_FOR_OTHER_SOC);
		$savedAllowOtherSoc = $hasAllowOtherSoc ? $conf->global->PDF_ALLOW_DISPLAY_LINKED_OBJECT_FOR_OTHER_SOC : null;
		$conf->global->PDF_ALLOW_DISPLAY_LINKED_OBJECT_FOR_OTHER_SOC = 0;

		$supplierProposal = new SupplierProposal($db);
		$supplierProposal->ref = 'RQ0000-0001';
		$supplierProposal->socid = 2;

		$proposal = new class ($db) extends Propal {
			/** @var array<string,array<int,CommonObject>> */
			public $fakeLinkedObjects = array();

			/**
			 * @param ?int		$sourceid			Unused
			 * @param string	$sourcetype			Unused
			 * @param ?int		$targetid			Unused
			 * @param string	$targettype			Unused
			 * @param string	$clause				Unused
			 * @param int		$alsosametype		Unused
			 * @param string	$orderby			Unused
			 * @param int		$loadalsoobjects	Unused
			 * @return int
			 */
			public function fetchObjectLinked($sourceid = null, $sourcetype = '', $targetid = null, $targettype = '', $clause = 'OR', $alsosametype = 1, $orderby = 'sourcetype', $loadalsoobjects = 1)
			{
				$this->linkedObjects = $this->fakeLinkedObjects;
				return 1;
			}
		};
		$proposal->socid = 1;
		$proposal->fakeLinkedObjects = array('supplier_proposal' => array(1 => $supplierProposal));

		try {
			$result = pdf_getLinkedObjects($proposal, $langs);
			$this->assertArrayNotHasKey('supplier_proposal', $result);

			$supplierProposal->socid = 1;
			$result = pdf_getLinkedObjects($proposal, $langs);
			$this->assertSame('RQ0000-0001', $result['supplier_proposal']['ref_value']);
		} finally {
			if ($hasAllowOtherSoc) {
				$conf->global->PDF_ALLOW_DISPLAY_LINKED_OBJECT_FOR_OTHER_SOC = $savedAllowOtherSoc;
			} else {
				unset($conf->global->PDF_ALLOW_DISPLAY_LINKED_OBJECT_FOR_OTHER_SOC);
			}
		}
	}

	/**
	 * Build a minimal one page PDF document holding the given objects.
	 *
	 * @param	array<int,string>	$objects	Content of each object, the key is the object number
	 * @param	string				$sep		What separates the numbers and the keyword into the header of each object
	 * @param	string				$trailer	Entries added to the trailer dictionary
	 * @return	string							Content of the PDF document
	 */
	private function buildPdf($objects, $sep = ' ', $trailer = '')
	{
		$out = "%PDF-1.4\n";
		$offsets = array();
		ksort($objects);
		foreach ($objects as $num => $content) {
			$offsets[$num] = strlen($out);
			$out .= $num.$sep."0".$sep."obj\n".$content."\nendobj\n";
		}
		$size = max(array_keys($objects)) + 1;
		$startxref = strlen($out);
		$out .= "xref\n0 ".$size."\n0000000000 65535 f \n";
		for ($num = 1; $num < $size; $num++) {
			$out .= isset($offsets[$num]) ? sprintf("%010d 00000 n \n", $offsets[$num]) : "0000000000 65535 f \n";
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
	 * @param	string				$trailer	Entries added to the dictionary of the cross-reference stream
	 * @return	string							Content of the PDF document
	 */
	private function buildPdfWithObjectStream($objects, $content, $sep = ' ', $filter = '/FlateDecode', $trailer = '')
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
		$out .= "7 0 obj\n<< /Type /XRef /Size 9 /W [1 2 2] /Root 1 0 R ".$trailer."/Filter /FlateDecode /Length ".strlen($data)." >>\nstream\n".$data."\nendstream\nendobj\n";
		$out .= "startxref\n".$startxref."\n%%EOF\n";

		return $out;
	}

	/**
	 * Import a page of documents whose dictionaries hold what used to stop the parser: a hexadecimal string
	 * closed right before the end of its dictionary, an empty one, one written on two lines, and a comment.
	 * Each of these used to loop forever while writing warnings, so the test would not end at all.
	 *
	 * @return void
	 */
	public function testPdfImportPageWithADictionaryTheParserUsedToLoopOn()
	{
		pdf_getInstance();	// Load the TCPDF and TCPDI classes and define their constants

		$content = "BT /F1 24 Tf 72 700 Td (Hello TCPDI) Tj ET";
		$styles = array(
			'spaced' => "<< /Panose <0105020102010507> >>",				// what the parser already read
			'glued' => "<< /Panose <0105020102010507>>>",				// written by FPDI and by xdvipdfmx
			'empty' => "<< /Panose <> >>",
			'online2' => "<< /Panose <01050201\n02010507> >>",
			'comment' => "<< % the style of the font\n/Panose <0105020102010507> >>",
		);

		$sizes = array();
		foreach ($styles as $case => $style) {
			$pdf = $this->buildPdf(array(
				1 => "<< /Type /Catalog /Pages 2 0 R >>",
				2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
				3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>",
				4 => "<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream",
				5 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /FontDescriptor 6 0 R >>",
				6 => "<< /Type /FontDescriptor /FontName /Helvetica /Flags 32 /Style ".$style." /ItalicAngle 0 >>",
			));

			$file = tempnam(sys_get_temp_dir(), 'tcpdi').'.pdf';
			file_put_contents($file, $pdf);

			$tcpdi = new TCPDI();
			$nbpages = $tcpdi->setSourceFile($file);
			$this->assertSame(1, $nbpages, 'Case '.$case.': the document should hold one page');
			$tpl = $tcpdi->importPage(1);
			$tcpdi->AddPage();
			$tcpdi->useTemplate($tpl);
			$sizes[$case] = strlen($tcpdi->Output('', 'S'));
			unlink($file);
		}

		// The four other ways of writing the same style give the same page as the one the parser already read,
		// except the empty string, which holds 16 characters less
		$this->assertSame($sizes['spaced'], $sizes['glued']);
		$this->assertSame($sizes['spaced'], $sizes['online2'] - 1);
		$this->assertSame($sizes['spaced'], $sizes['comment']);
		$this->assertSame($sizes['spaced'] - 16, $sizes['empty']);
	}

	/**
	 * Import a page of a document written the way cairo and PDFlib write them: the objects are in an object
	 * stream and the cross-reference is a stream that uses no predictor. The same document is also built with
	 * its filter written as an array, which was never decoded.
	 *
	 * @return void
	 */
	public function testPdfImportPageWithAnXrefStreamWithoutPredictor()
	{
		pdf_getInstance();	// Load the TCPDF and TCPDI classes and define their constants

		$content = "BT /F1 24 Tf 72 700 Td (Hello TCPDI) Tj ET";
		$objects = array(
			1 => "<< /Type /Catalog /Pages 2 0 R >>",
			2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
			3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>",
			5 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
		);

		$sizes = array();
		foreach (array('name' => '/FlateDecode', 'array' => '[/FlateDecode]') as $case => $filter) {
			$file = tempnam(sys_get_temp_dir(), 'tcpdi').'.pdf';
			file_put_contents($file, $this->buildPdfWithObjectStream($objects, $content, ' ', $filter));

			$tcpdi = new TCPDI();
			$this->assertSame(1, $tcpdi->setSourceFile($file), 'Case '.$case.': the document should hold one page');
			$tpl = $tcpdi->importPage(1);
			$tcpdi->AddPage();
			$tcpdi->useTemplate($tpl);
			$sizes[$case] = strlen($tcpdi->Output('', 'S'));
			unlink($file);
		}

		// Both ways of writing the filter give the same page
		$this->assertSame($sizes['name'], $sizes['array']);
	}
	/**
	 * Import a page of a document whose object headers do not separate the numbers and the keyword with one single
	 * space: some writers align them on a column with several spaces, others put a line break. The header was
	 * compared to the string "num gen obj", so none of these objects was ever found.
	 *
	 * @return void
	 */
	public function testPdfImportPageWithObjectHeadersWithSeveralSpaces()
	{
		pdf_getInstance();	// Load the TCPDF and TCPDI classes and define their constants

		$content = "BT /F1 24 Tf 72 700 Td (Hello TCPDI) Tj ET";
		$objects = array(
			1 => "<< /Type /Catalog /Pages 2 0 R >>",
			2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
			3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>",
			4 => "<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream",
			5 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
		);

		$sizes = array();
		foreach (array('space' => ' ', 'spaces' => '    ', 'newline' => "\n", 'tab' => "\t") as $case => $sep) {
			$file = tempnam(sys_get_temp_dir(), 'tcpdi').'.pdf';
			file_put_contents($file, $this->buildPdf($objects, $sep));

			$tcpdi = new TCPDI();
			$this->assertSame(1, $tcpdi->setSourceFile($file), 'Case '.$case.': the document should hold one page');
			$tpl = $tcpdi->importPage(1);
			$tcpdi->AddPage();
			$tcpdi->useTemplate($tpl);
			$sizes[$case] = strlen($tcpdi->Output('', 'S'));
			unlink($file);
		}

		// Whatever separates them, the same page is imported
		$this->assertSame($sizes['space'], $sizes['spaces']);
		$this->assertSame($sizes['space'], $sizes['newline']);
		$this->assertSame($sizes['space'], $sizes['tab']);
	}
	/**
	 * Refuse a document that is encrypted. Its streams can not be read, so the page imported used to be either
	 * filled with the encrypted bytes - a page of garbage, written without any error - or to stop later on an
	 * obscure "decodeFilterFlateDecode: invalid code".
	 *
	 * @return void
	 */
	public function testPdfImportPageOfAnEncryptedDocumentIsRefused()
	{
		pdf_getInstance();	// Load the TCPDF and TCPDI classes and define their constants

		$content = "BT /F1 24 Tf 72 700 Td (Hello TCPDI) Tj ET";
		$objects = array(
			1 => "<< /Type /Catalog /Pages 2 0 R >>",
			2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
			3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>",
			5 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
		);
		$encrypt = "<< /Filter /Standard /V 2 /R 3 /Length 128 /P -1 /O <00> /U <00> >>";

		$documents = array(
			// the encryption dictionary of the trailer of a cross-reference table, as a reference and written in place
			'reference' => $this->buildPdf($objects + array(4 => "<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream", 9 => $encrypt), ' ', '/Encrypt 9 0 R '),
			'inplace' => $this->buildPdf($objects + array(4 => "<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream"), ' ', '/Encrypt '.$encrypt.' '),
			// the same entry in the dictionary of a cross-reference stream
			'xrefstream' => $this->buildPdfWithObjectStream($objects, $content, ' ', '/FlateDecode', '/Encrypt '.$encrypt.' '),
		);

		foreach ($documents as $case => $document) {
			$file = tempnam(sys_get_temp_dir(), 'tcpdi').'.pdf';
			file_put_contents($file, $document);

			$message = '';
			try {
				$tcpdi = new TCPDI();
				$tcpdi->setSourceFile($file);
			} catch (Exception $e) {
				$message = $e->getMessage();
			}
			unlink($file);

			$this->assertStringContainsString('encrypted', $message, 'Case '.$case.': the document should be refused as encrypted, got "'.$message.'"');
		}
	}
	/**
	 * Import a page whose content stream is deflated but has no end marker: the writer did not flush it, or the
	 * /Length declared is shorter than the data. gzuncompress() refuses it, so the import used to stop on
	 * "decodeFilterFlateDecode: invalid code", while every PDF reader opens the document and draws the page.
	 *
	 * @return void
	 */
	public function testPdfImportPageWithATruncatedDeflatedStream()
	{
		pdf_getInstance();	// Load the TCPDF and TCPDI classes and define their constants

		$content = "BT /F1 24 Tf 72 700 Td (Hello TCPDI) Tj ET";
		$deflated = gzcompress($content);
		$objects = array(
			1 => "<< /Type /Catalog /Pages 2 0 R >>",
			2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
			3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>",
			5 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
		);

		// The same stream, complete and with its end marker cut off
		$streams = array('complete' => $deflated, 'truncated' => substr($deflated, 0, -4));

		$sizes = array();
		foreach ($streams as $case => $stream) {
			$file = tempnam(sys_get_temp_dir(), 'tcpdi').'.pdf';
			$object = "<< /Filter /FlateDecode /Length ".strlen($stream)." >>\nstream\n".$stream."\nendstream";
			file_put_contents($file, $this->buildPdf($objects + array(4 => $object)));

			$tcpdi = new TCPDI();
			$this->assertSame(1, $tcpdi->setSourceFile($file), 'Case '.$case.': the document should hold one page');
			$tpl = $tcpdi->importPage(1);
			$tcpdi->AddPage();
			$tcpdi->useTemplate($tpl);
			$sizes[$case] = strlen($tcpdi->Output('', 'S'));
			unlink($file);
		}

		// The page drawn from the truncated stream is the page drawn from the complete one
		$this->assertSame($sizes['complete'], $sizes['truncated']);
	}

	/**
	 * Import a page of a document whose object stream separates the object numbers and their offsets with something
	 * else than one single space: the list was split on each single white space character, so a CRLF or two spaces
	 * gave empty values between the numbers, and the offset added to /First was a string.
	 *
	 * @return void
	 */
	public function testPdfImportPageWithAnObjectStreamHeaderNotSeparatedBySingleSpaces()
	{
		pdf_getInstance();	// Load the TCPDF and TCPDI classes and define their constants

		$content = "BT /F1 24 Tf 72 700 Td (Hello TCPDI) Tj ET";
		$objects = array(
			1 => "<< /Type /Catalog /Pages 2 0 R >>",
			2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
			3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>",
			5 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
		);

		$sizes = array();
		foreach (array('space' => ' ', 'crlf' => "\r\n", 'spaces' => '  ', 'newline' => "\n") as $case => $sep) {
			$file = tempnam(sys_get_temp_dir(), 'tcpdi').'.pdf';
			file_put_contents($file, $this->buildPdfWithObjectStream($objects, $content, $sep));

			$tcpdi = new TCPDI();
			$this->assertSame(1, $tcpdi->setSourceFile($file), 'Case '.$case.': the document should hold one page');
			$tpl = $tcpdi->importPage(1);
			$tcpdi->AddPage();
			$tcpdi->useTemplate($tpl);
			$sizes[$case] = strlen($tcpdi->Output('', 'S'));
			unlink($file);
		}

		// Whatever separates them, the same page is imported
		$this->assertSame($sizes['space'], $sizes['crlf']);
		$this->assertSame($sizes['space'], $sizes['spaces']);
		$this->assertSame($sizes['space'], $sizes['newline']);
	}

	/**
	 * Import a page of a document whose startxref does not point at a cross-reference section: beyond the end of
	 * the file, or inside it but somewhere else. strpos() was given that offset, which throws a ValueError with
	 * PHP 8 when it is beyond the data, and the page was not imported.
	 *
	 * @return void
	 */
	public function testPdfImportPageWithAWrongStartxref()
	{
		pdf_getInstance();	// Load the TCPDF and TCPDI classes and define their constants

		$content = "BT /F1 24 Tf 72 700 Td (Hello TCPDI) Tj ET";
		$objects = array(
			1 => "<< /Type /Catalog /Pages 2 0 R >>",
			2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
			3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>",
			4 => "<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream",
			5 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
		);
		$document = $this->buildPdf($objects);

		$documents = array(
			'right' => $document,
			// Beyond the end of the file, and inside it but not on the "xref" keyword
			'beyond' => preg_replace('/startxref\n[0-9]+\n/', "startxref\n9999999\n", $document),
			'inside' => preg_replace('/startxref\n[0-9]+\n/', "startxref\n10\n", $document),
		);

		$sizes = array();
		foreach ($documents as $case => $data) {
			$file = tempnam(sys_get_temp_dir(), 'tcpdi').'.pdf';
			file_put_contents($file, $data);

			$tcpdi = new TCPDI();
			$this->assertSame(1, $tcpdi->setSourceFile($file), 'Case '.$case.': the document should hold one page');
			$tpl = $tcpdi->importPage(1);
			$tcpdi->AddPage();
			$tcpdi->useTemplate($tpl);
			$sizes[$case] = strlen($tcpdi->Output('', 'S'));
			unlink($file);
		}

		// The cross-reference table of the file is found again, so the same page is imported
		$this->assertSame($sizes['right'], $sizes['beyond']);
		$this->assertSame($sizes['right'], $sizes['inside']);
	}

	/**
	 * Import a page of a document that holds a dictionary with a /Length entry which is not the dictionary of a
	 * stream - an encryption dictionary, a layer, an optional content group all have one. It was read as a stream
	 * anyway, and /Length bytes were taken from the file: 256 MB on the file of the audit, which exhausts the
	 * memory, and in any case the parser lost its place in the object.
	 *
	 * @return void
	 */
	public function testPdfImportPageWithANonStreamDictionaryHoldingALengthKey()
	{
		pdf_getInstance();	// Load the TCPDF and TCPDI classes and define their constants

		$content = "BT /F1 24 Tf 72 700 Td (Hello TCPDI) Tj ET";
		$objects = array(
			1 => "<< /Type /Catalog /Pages 2 0 R >>",
			2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
			3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> /Properties << /MC0 6 0 R >> >> /Contents 4 0 R >>",
			4 => "<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream",
			5 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
		);

		// The same document, the dictionary of object 6 declaring two lengths it does not have. Both are written
		// with the same number of digits, so the only thing that could change the size of the page imported is
		// the number of bytes read after that dictionary.
		$sizes = array();
		foreach (array('small' => 123456789, 'huge' => 268435456) as $case => $length) {
			$file = tempnam(sys_get_temp_dir(), 'tcpdi').'.pdf';
			file_put_contents($file, $this->buildPdf($objects + array(6 => "<< /Length ".$length." /Kind /Layer >>")));

			$tcpdi = new TCPDI();
			$this->assertSame(1, $tcpdi->setSourceFile($file), 'Case '.$case.': the document should hold one page');
			$tpl = $tcpdi->importPage(1);
			$tcpdi->AddPage();
			$tcpdi->useTemplate($tpl);
			$sizes[$case] = strlen($tcpdi->Output('', 'S'));
			unlink($file);
		}

		// The /Length of that dictionary is not a stream length, so it changes nothing to the page imported
		$this->assertSame($sizes['small'], $sizes['huge']);
	}
	/**
	 * Import a page whose content stream declares a /Length that is not where the stream ends. The declared value
	 * was taken as is, so the data read was cut short or ran over the end of the stream, and the parser went on
	 * from the wrong place.
	 *
	 * @return void
	 */
	public function testPdfImportPageWithAWrongStreamLength()
	{
		pdf_getInstance();	// Load the TCPDF and TCPDI classes and define their constants

		$content = "BT /F1 24 Tf 72 700 Td (Hello TCPDI) Tj ET";
		$objects = array(
			1 => "<< /Type /Catalog /Pages 2 0 R >>",
			2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
			3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>",
			5 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
		);

		$lengths = array('right' => strlen($content), 'short' => strlen($content) - 10, 'long' => strlen($content) + 10);

		$sizes = array();
		foreach ($lengths as $case => $length) {
			$file = tempnam(sys_get_temp_dir(), 'tcpdi').'.pdf';
			$object = "<< /Length ".$length." >>\nstream\n".$content."\nendstream";
			file_put_contents($file, $this->buildPdf($objects + array(4 => $object)));

			$tcpdi = new TCPDI();
			$this->assertSame(1, $tcpdi->setSourceFile($file), 'Case '.$case.': the document should hold one page');
			$tpl = $tcpdi->importPage(1);
			$tcpdi->AddPage();
			$tcpdi->useTemplate($tpl);
			$sizes[$case] = strlen($tcpdi->Output('', 'S'));
			unlink($file);
		}

		// The end of the stream is where "endstream" is, whatever /Length says
		$this->assertSame($sizes['right'], $sizes['short']);
		$this->assertSame($sizes['right'], $sizes['long']);
	}
	/**
	 * Import a page of a document whose page tree does not say everything in place: the /Kids of the
	 * /Pages node is an indirect reference to the array instead of the array itself, and the page
	 * carries neither /Resources of its own nor /Annots. Both are allowed by the specification - any
	 * value of a dictionary may be an indirect reference, and /Resources is inherited from the parent
	 * node - and both are produced by real writers.
	 *
	 * Watched failing before the fix: the direct form imports, the indirect one does not.
	 *
	 * @return	void
	 */
	public function testPdfImportPageWhoseKidsAreIndirectAndWithoutResources()
	{
		pdf_getInstance();	// Load the TCPDF and TCPDI classes and define their constants

		$content = "BT /F1 24 Tf 72 700 Td (Hello TCPDI) Tj ET";
		$stream = "<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream";

		// The page holds no /Resources: it inherits the ones of its parent node, as the specification says.
		$cases = array(
			// /Kids written in place, the form every writer of the test suite uses so far
			'direct' => array(
				1 => "<< /Type /Catalog /Pages 2 0 R >>",
				2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 /Resources << /Font << /F1 5 0 R >> >> >>",
				3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R >>",
				4 => $stream,
				5 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
			),
			// The same document, with /Kids an indirect reference to the array
			'indirect' => array(
				1 => "<< /Type /Catalog /Pages 2 0 R >>",
				2 => "<< /Type /Pages /Kids 6 0 R /Count 1 /Resources << /Font << /F1 5 0 R >> >> >>",
				3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R >>",
				4 => $stream,
				5 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
				6 => "[3 0 R]",
			),
		);

		$sizes = array();
		foreach ($cases as $case => $objects) {
			$file = tempnam(sys_get_temp_dir(), 'tcpdi').'.pdf';
			file_put_contents($file, $this->buildPdf($objects));

			$tcpdi = new TCPDI();
			$this->assertSame(1, $tcpdi->setSourceFile($file), 'Case '.$case.': the document should hold one page');
			$tpl = $tcpdi->importPage(1);
			$tcpdi->AddPage();
			$tcpdi->useTemplate($tpl);
			$sizes[$case] = strlen($tcpdi->Output('', 'S'));
			unlink($file);
		}

		// Both say the same thing, so both import to the same document
		$this->assertSame($sizes['direct'], $sizes['indirect']);
	}
}
