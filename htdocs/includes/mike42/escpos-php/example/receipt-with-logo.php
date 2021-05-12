<?php
require_once(dirname(__FILE__) . "/../Escpos.php");

/* Information for the receipt */
$items = array(
	new item("Example item #1", "4.00"),
	new item("Another thing", "3.50"),
	new item("Something else", "1.00"),
	new item("A final item", "4.45"),
);
$subtotal = new item('Subtotal', '12.95');
$tax = new item('A local tax', '1.30');
$total = new item('Total', '14.25', true);
/* Date is kept the same for testing */
// $date = date('l jS \of F Y h:i:s A');
$date = "Monday 6th of April 2015 02:56:25 PM";

/* Start the printer */
$logo = new EscposImage("resources/escpos-php.png");
$printer = new Escpos();

/* Print top logo */
$printer -> setJustification(Escpos::JUSTIFY_CENTER);
$printer -> graphics($logo);

/* Name of shop */
$printer -> selectPrintMode(Escpos::MODE_DOUBLE_WIDTH);
$printer -> text("ExampleMart Ltd.\n");
$printer -> selectPrintMode();
$printer -> text("Shop No. 42.\n");
$printer -> feed();

/* Title of receipt */
$printer -> setEmphasis(true);
$printer -> text("SALES INVOICE\n");
$printer -> setEmphasis(false);

/* Items */
$printer -> setJustification(Escpos::JUSTIFY_LEFT);
$printer -> setEmphasis(true);
$printer -> text(new item('', '$'));
$printer -> setEmphasis(false);
foreach($items as $item) {
	$printer -> text($item);
}
$printer -> setEmphasis(true);
$printer -> text($subtotal);
$printer -> setEmphasis(false);
$printer -> feed();

/* Tax and total */
$printer -> text($tax);
$printer -> selectPrintMode(Escpos::MODE_DOUBLE_WIDTH);
$printer -> text($total);
$printer -> selectPrintMode();

/* Footer */
$printer -> feed(2);
$printer -> setJustification(Escpos::JUSTIFY_CENTER);
$printer -> text("Thank you for shopping at ExampleMart\n");
$printer -> text("For trading hours, please visit example.com\n");
$printer -> feed(2);
$printer -> text($date . "\n");

/* Cut the receipt and open the cash drawer */
$printer -> cut();
$printer -> pulse();

$printer -> close();

/* A wrapper to do organise item names & prices into columns */
class item {
	private $name;
	private $price;
	private $dollarSign;

	public function __construct($name = '', $price = '', $dollarSign = false) {
		$this -> name = $name;
		$this -> price = $price;
		$this -> dollarSign = $dollarSign;
	}
	
	public function __toString() {
		$rightCols = 10;
		$leftCols = 38;
		if($this -> dollarSign) {
			$leftCols = $leftCols / 2 - $rightCols / 2;
		}
		$left = str_pad($this -> name, $leftCols) ;
		
		$sign = ($this -> dollarSign ? '$ ' : '');
		$right = str_pad($sign . $this -> price, $rightCols, ' ', STR_PAD_LEFT);
		return "$left$right\n";
	}
}
?>
