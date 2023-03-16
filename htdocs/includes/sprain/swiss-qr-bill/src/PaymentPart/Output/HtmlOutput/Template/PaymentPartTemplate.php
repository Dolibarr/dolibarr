<?php declare(strict_types=1);

namespace Sprain\SwissQrBill\PaymentPart\Output\HtmlOutput\Template;

class PaymentPartTemplate
{
    public const TEMPLATE = <<<EOT
<style>
#qr-bill {
	box-sizing: border-box;
	border-collapse: collapse;
	color: #000 !important;
}

#qr-bill * {
	font-family: Arial, Frutiger, Helvetica, "Liberation Sans"  !important;
}

#qr-bill img.qr-bill-placeholder {
    margin-top: 1pt;
}

#qr-bill-separate-info {
    text-align: center;
    font-size: 8pt !important;
    line-height: 9pt;
	border-bottom: 0.75pt solid black;
	height: 5mm;
	vertical-align: middle;
}

/* h1 / h2 */
#qr-bill h1 {
	font-size: 11pt !important;
	font-weight: bold !important;
	margin: 0;
	padding: 0;
	height: 7mm;
	color: #000 !important;
}

#qr-bill h2 {
	font-weight: bold !important;
	margin: 0;
	padding: 0;
	color: #000 !important;
}

#qr-bill-payment-part h2 {
	font-size: 8pt !important;
	line-height: 11pt !important;	
    margin-top: 11pt;
	color: #000 !important;
}

#qr-bill-receipt h2 {
	font-size: 6pt !important;
	line-height: 8pt !important;	
    margin-top: 8pt;
	color: #000 !important;
}

#qr-bill-payment-part h2:first-child,
#qr-bill-receipt h2:first-child {
	margin-top: 0;
	color: #000 !important;
}

/* p */
#qr-bill p {
	font-weight: normal !important;
	margin: 0;
	padding: 0;
	color: #000 !important;
}

#qr-bill-receipt p {
	font-size: 8pt !important;
	line-height: 9pt !important;
	color: #000 !important;
}

#qr-bill-payment-part p {
	font-size: 10pt !important;
	line-height: 11pt !important;
	color: #000 !important;
}

#qr-bill-amount-area-receipt p{
    line-height: 11pt !important;
	color: #000 !important;
}

#qr-bill-amount-area p{
    line-height: 13pt !important;
	color: #000 !important;
}

#qr-bill-payment-further-information p {
    font-size: 7pt !important;
    line-height: 9pt !important;
	color: #000 !important;
}

/* Receipt */
#qr-bill-receipt {
    box-sizing: border-box;
    width: 62mm;
	border-right: 0.2mm solid black;
	padding-left: 5mm;
	padding-top: 5mm;
	vertical-align: top;
}

#qr-bill-information-receipt {
    height: 56mm;
}

#qr-bill-amount-area-receipt {
    height: 14mm;
}

#qr-bill-currency-receipt {
	float: left;
	margin-right: 2mm;
}

#qr-bill-acceptance-point {
    height: 18mm;
    text-align: right;
    margin-right: 5mm;
}

#qr-bill img#placeholder_amount_receipt {
    float: right;
    margin-top: -9pt;
    margin-right: 5mm;
}

/* Main part */
#qr-bill-payment-part {
    box-sizing: border-box;
    width: 148mm;
	padding-left: 5mm;
	padding-top: 5mm;
	padding-right: 5mm;
	vertical-align: top;
}

#qr-bill-payment-part-left {
    float: left;
    box-sizing: border-box;
    width: 51mm;
}

#qr-bill-swiss-qr-image {
	width: 46mm;
	height: 46mm;
	margin: 5mm;
	margin-left: 0;
}

#qr-bill-amount-area {
    height: 22mm;
}

#qr-bill-currency {
	float: left;
	margin-right: 2mm;
}

#qr-bill-payment-further-information {
    clear: both;
}

#qr-bill img#placeholder_amount {
    margin-left: 11mm;
    margin-top: -11pt;
}

{{ printable-content }}
</style>

<table id="qr-bill">
    <tr id="qr-bill-separate-info">
        <td colspan="99"><span id="qr-bill-separate-info-text">{{ text.separate }}</span></td>
    </tr>
	<tr>
	    <td id="qr-bill-receipt">
	        <h1>{{ text.receipt }}</h1>
	        <div id="qr-bill-information-receipt">
                {{ information-content-receipt }}
            </div>
            <div id="qr-bill-amount-area-receipt">
                <div id="qr-bill-currency-receipt">
                    {{ currency-content }}
                </div>
                <div id="qr-bill-amount-receipt">
                    {{ amount-content-receipt }}
                </div>
            </div>
            <div id="qr-bill-acceptance-point">
                <h2>{{ text.acceptancePoint }}</h2>
            </div>
        </td>

        <td id="qr-bill-payment-part">
            <div id="qr-bill-payment-part-left">
                <h1>{{ text.paymentPart }}</h1>
                <img src="{{ swiss-qr-image }}" id="qr-bill-swiss-qr-image">
                <div id="qr-bill-amount-area">
                    <div id="qr-bill-currency">
                        {{ currency-content }}
                    </div>
                    <div id="qr-bill-amount">
                        {{ amount-content }}
                    </div>
                </div>
			</div>
			<div id="qr-bill-payment-part-right">
                <div id="qr-bill-information">
                    {{ information-content }}
                </div>
			</div>
			<div id="qr-bill-payment-further-information">
			    {{ further-information-content }}
            </div>
        </td>
	</tr>
</table>
EOT;
}
