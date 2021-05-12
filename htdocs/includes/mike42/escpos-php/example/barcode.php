<?php
require_once (dirname ( __FILE__ ) . "/../Escpos.php");
$printer = new Escpos ();
$printer->setBarcodeHeight ( 40 );

/* Text position */
$printer->selectPrintMode ( Escpos::MODE_DOUBLE_HEIGHT | Escpos::MODE_DOUBLE_WIDTH );
$printer->text ( "Text position\n" );
$printer->selectPrintMode ();
$hri = array (
		Escpos::BARCODE_TEXT_NONE => "No text",
		Escpos::BARCODE_TEXT_ABOVE => "Above",
		Escpos::BARCODE_TEXT_BELOW => "Below",
		Escpos::BARCODE_TEXT_ABOVE | Escpos::BARCODE_TEXT_BELOW => "Both" 
);
foreach ( $hri as $position => $caption ) {
	$printer->text ( $caption . "\n" );
	$printer->setBarcodeTextPosition ( $position );
	$printer->barcode ( "012345678901", Escpos::BARCODE_JAN13 );
	$printer->feed ();
}

/* Barcode types */
$standards = array (
		Escpos::BARCODE_UPCA => array (
				"title" => "UPC-A",
				"caption" => "Fixed-length numeric product barcodes.",
				"example" => array (
						array (
								"caption" => "12 char numeric including (wrong) check digit.",
								"content" => "012345678901"
						),
						array (
								"caption" => "Send 11 chars to add check digit automatically.",
								"content" => "01234567890" 
						) 
				) 
		),
		Escpos::BARCODE_UPCE => array (
				"title" => "UPC-E",
				"caption" => "Fixed-length numeric compact product barcodes.",
				"example" => array (
						array (
								"caption" => "6 char numeric - auto check digit & NSC",
								"content" => "123456" 
						),
						array (
								"caption" => "7 char numeric - auto check digit",
								"content" => "0123456"
						),
						array (
								"caption" => "8 char numeric",
								"content" => "01234567"
						),
						array (
								"caption" => "11 char numeric - auto check digit",
								"content" => "01234567890"
						),
						array (
								"caption" => "12 char numeric including (wrong) check digit",
								"content" => "012345678901"
						) 
				) 
		),
		Escpos::BARCODE_JAN13 => array (
				"title" => "JAN13/EAN13",
				"caption" => "Fixed-length numeric barcodes.",
				"example" => array (
						array (
								"caption" => "12 char numeric - auto check digit",
								"content" => "012345678901" 
						),
						array (
								"caption" => "13 char numeric including (wrong) check digit",
								"content" => "0123456789012" 
						) 
				) 
		),
		Escpos::BARCODE_JAN8 => array (
				"title" => "JAN8/EAN8",
				"caption" => "Fixed-length numeric barcodes.",
				"example" => array (
						array (
								"caption" => "7 char numeric - auto check digit",
								"content" => "0123456" 
						),
						array (
								"caption" => "8 char numeric including (wrong) check digit",
								"content" => "01234567"
						) 
				) 
		),
		Escpos::BARCODE_CODE39 => array (
				"title" => "Code39",
				"caption" => "Variable length alphanumeric w/ some special chars.",
				"example" => array (
						array (
								"caption" => "Text, numbers, spaces",
								"content" => "ABC 012" 
						),
						array (
								"caption" => "Special characters",
								"content" => "$%+-./" 
						),
						array (
								"caption" => "Extra char (*) Used as start/stop",
								"content" => "*TEXT*" 
						) 
				) 
		),
		Escpos::BARCODE_ITF => array (
				"title" => "ITF",
				"caption" => "Variable length numeric w/even number of digits,\nas they are encoded in pairs.",
				"example" => array (
						array (
								"caption" => "Numeric- even number of digits",
								"content" => "0123456789" 
						) 
				) 
		),
		Escpos::BARCODE_CODABAR => array (
				"title" => "Codabar",
				"caption" => "Varaible length numeric with some allowable\nextra characters. ABCD/abcd must be used as\nstart/stop characters (one at the start, one\nat the end) to distinguish between barcode\napplications.",
				"example" => array (
						array (
								"caption" => "Numeric w/ A A start/stop. ",
								"content" => "A012345A" 
						),
						array (
								"caption" => "Extra allowable characters",
								"content" => "A012$+-./:A" 
						) 
				) 
		),
		Escpos::BARCODE_CODE93 => array (
				"title" => "Code93",
				"caption" => "Variable length- any ASCII is available",
				"example" => array (
						array (
								"caption" => "Text",
								"content" => "012abcd" 
						) 
				) 
		),
		Escpos::BARCODE_CODE128 => array (
				"title" => "Code128",
				"caption" => "Variable length- any ASCII is available",
				"example" => array (
						array (
								"caption" => "Code set A uppercase & symbols",
								"content" => "{A" . "012ABCD" 
						),
						array (
								"caption" => "Code set B general text",
								"content" => "{B" . "012ABCDabcd" 
						),
						array (
								"caption" => "Code set C compact numbers\n Sending chr(21) chr(32) chr(43)",
								"content" => "{C" . chr ( 21 ) . chr ( 32 ) . chr ( 43 ) 
						) 
				) 
		) 
);
$printer->setBarcodeTextPosition ( Escpos::BARCODE_TEXT_BELOW );
foreach ( $standards as $type => $standard ) {
	$printer->selectPrintMode ( Escpos::MODE_DOUBLE_HEIGHT | Escpos::MODE_DOUBLE_WIDTH );
	$printer->text ( $standard ["title"] . "\n" );
	$printer->selectPrintMode ();
	$printer->text ( $standard ["caption"] . "\n\n" );
	foreach ( $standard ["example"] as $id => $barcode ) {
		$printer->setEmphasis ( true );
		$printer->text ( $barcode ["caption"] . "\n" );
		$printer->setEmphasis ( false );
		$printer->text ( "Content: " . $barcode ["content"] . "\n" );
		$printer->barcode ( $barcode ["content"], $type );
		$printer->feed ();
	}
}
$printer->cut ();
$printer->close ();

