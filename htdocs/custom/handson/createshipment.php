<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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

/**
 *    \file       handson/handsonindex.php
 *    \ingroup    handson
 *    \brief      Home page of handson top menu
 */


// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");


// Security check
if (!$user->rights->handson->createdhllabel) {
	accessforbidden();
}

$action = GETPOST('action', 'alphanohtml');

$datastring = base64_decode(GETPOST('data', 'alphanohtml'));
$data = explode(',', $datastring);
$addrstring = base64_decode(GETPOST('address', 'alphanohtml'));
$address = explode(',', $addrstring);

$soapxmlstring = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:cis=\"http://dhl.de/webservice/cisbase\" xmlns:ns=\"http://dhl.de/webservices/businesscustomershipping/3.0\">
   <soapenv:Header>
      <cis:Authentification>
         <cis:user>".$conf->global->DHL_USER."</cis:user>
         <cis:signature>".$conf->global->DHL_SIGNATURE."</cis:signature>
      </cis:Authentification>
   </soapenv:Header>
   <soapenv:Body>";
$soapxmlstring .= $action == 'create' ? "<ns:CreateShipmentOrderRequest>" : "<ns:ValidateShipmentOrderRequest>";
$soapxmlstring .= "<ns:Version>
            <majorRelease>3</majorRelease>
            <minorRelease>2</minorRelease>
         </ns:Version>
         <ShipmentOrder>
            <sequenceNumber></sequenceNumber>
            <Shipment>
               <ShipmentDetails>
                  <product>V01PAK</product>
                  <cis:accountNumber>".$conf->global->DHL_ACCOUNTNUM."</cis:accountNumber>
				  <customerReference>" . $data[6] . "</customerReference>
                  <shipmentDate>" . $data[7] . "</shipmentDate>
				  <costCentre>" . $data[5] . "</costCentre>
                  <ShipmentItem>
                     <weightInKG>" . $data[1] . "</weightInKG>
                     <lengthInCM>" . $data[4] . "</lengthInCM>
                     <widthInCM>" . $data[2] . "</widthInCM>
                     <heightInCM>" . $data[3] . "</heightInCM>
                  </ShipmentItem>
                  <Service>
                  </Service>
                  <Notification>
                     <recipientEmailAddress>" . $address[8] . "</recipientEmailAddress>
                  </Notification>
               </ShipmentDetails>
               <Shipper>
                  <Name>
                     <cis:name1>HANDS on TECHNOLOGY e.V.</cis:name1>
                     <!--<cis:name2>Absender Zeile 2</cis:name2>-->
                     <!--<cis:name3>Absender Zeile 3</cis:name3>-->
                  </Name>
                  <Address>
                     <cis:streetName>Plautstraße</cis:streetName>
                     <cis:streetNumber>80</cis:streetNumber>
                     <cis:zip>04179</cis:zip>
                     <cis:city>Leipzig</cis:city>
                     <cis:Origin>
                        <cis:country></cis:country>
                        <cis:countryISOCode>DE</cis:countryISOCode>
                     </cis:Origin>
                  </Address>
                  <!--<Packstation>
					  <cis:postNumber>123456789</cis:postNumber>
					  <cis:packstationNumber>425</cis:packstationNumber>
					  <cis:zip>69226</cis:zip>
					  <cis:city>Nußloch</cis:city>
					</Packstation>
					<Postfiliale>
					  <cis:postNumber>123456789</cis:postNumber>
					  <cis:postfilialeNumber>425</cis:postfilialeNumber>
					  <cis:zip>69226</cis:zip>
					  <cis:city>Nußloch</cis:city>
					</Postfiliale>
					<ParcelShop>
					  <cis:parcelShopNumber>123456789</cis:parcelShopNumber>
					  <cis:streetName>425</cis:streetName>
					  <cis:streetNumber>425</cis:streetNumber>
					  <cis:zip>69226</cis:zip>
					  <cis:city>Nußloch</cis:city>
					</ParcelShop>-->
                  <Communication>
                     <!--Optional:-->
                     <cis:phone>+493412461583</cis:phone>
                     <cis:email>info@hands-on-technology.org</cis:email>
                     <!--Optional:-->
                     <cis:contactPerson></cis:contactPerson>
                  </Communication>
               </Shipper>
               <Receiver>
                  <cis:name1>" . $address[0] . " " . $address[1] . "</cis:name1>
                  <Address>
                     <cis:name2></cis:name2>
                     <cis:name3></cis:name3>
                     <cis:streetName>" . $address[2] . "</cis:streetName>
                     <cis:streetNumber>" . $address[3] . "</cis:streetNumber>
                     <cis:zip>" . $address[6] . "</cis:zip>
                     <cis:city>" . $address[7] . "</cis:city>
                     <cis:Origin>
                        <cis:country></cis:country>
                        <cis:countryISOCode>" . $address[10] . "</cis:countryISOCode>
                     </cis:Origin>
                  </Address>
                  <Communication>
                     <cis:phone>" . $address[9] . "</cis:phone>
                     <cis:email>" . $address[8] . "</cis:email>
                     <cis:contactPerson>" . $address[0] . " " . $address[1] . "</cis:contactPerson>
                  </Communication>
               </Receiver>
            </Shipment>
            <PrintOnlyIfCodeable active=\"1\"/>
         </ShipmentOrder>
         <labelResponseType>B64</labelResponseType>
         <groupProfileName></groupProfileName>
         <labelFormat></labelFormat>
         <labelFormatRetoure></labelFormatRetoure>
         <combinedPrinting>0</combinedPrinting>";
$soapxmlstring .= $action == 'create' ? "</ns:CreateShipmentOrderRequest>" : "</ns:ValidateShipmentOrderRequest>";
$soapxmlstring .= "</soapenv:Body></soapenv:Envelope>";

$auth = base64_encode($conf->global->DHL_APP_ID . ':' . $conf->global->DHL_APP_TOKEN);

$curl = curl_init();
curl_setopt_array($curl, array(
		CURLOPT_URL => $conf->global->DHL_SEND_SOAP_ENDPOINT,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $soapxmlstring,
		CURLOPT_HTTPHEADER => array(
			"content-type: text/xml; charset=utf-8",
			'authorization: Basic ' . $auth,
			"SOAPAction: urn:createShipmentOrder",
		)
	)
);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);
if ($action == 'create') {
	if ($err) {
		echo "cURL Error #:" . $err;
	} else {

		$shipNum = explode('<shipmentNumber>', $response);
		$shipNum = explode('</shipmentNumber>', $shipNum[1]);

		$pdfEnc = explode('<labelData>', $response);
		$pdfEnc = explode('</labelData>', $pdfEnc[1]);
		if ($shipNum[0] != '') {
			dol_include_once('/custom/handson/class/label.class.php');
			$label = new Label($db);

			$label->ref = $shipNum[0];
			$label->contact = $address[11];
			$label->date_creation = dol_now('tzref');
			$label->create($user);

			header('Content-Type: application/pdf', 'charset: utf-8');
			echo base64_decode($pdfEnc[0]);
		} else {
			print 'Das hat leider nicht funktioniert. Fehler: <br>';
			print $response;
		}
	}
} elseif ($action == 'validate') {
	if ($err) {
		print "cURL Error #:" . $err;
	} else {
		$statusCode = explode('<statusCode>', $response);
		$statusCode = explode('</statusCode>', $statusCode[1]);

		$statusMessage = explode('<statusMessage>', $response);
		$statusMessage = explode('</statusMessage>', $statusMessage[1]);

		print $statusCode[0] . ';' . $response;
	}
}
// app-id (user) draht_1
// token (pw) x2neeNdsOjjWa3K3fRdzB9L69QdaYq
//var_dump(base64_encode('draht_1:x2neeNdsOjjWa3K3fRdzB9L69QdaYq'));
// ergibt ZHJhaHRfMTp4Mm5lZU5kc09qaldhM0szZlJkekI5TDY5UWRhWXE=
// test DPDHL MjIyMjIyMjIyMl9jdXN0b21lcjp1QlFiWjYyIVppQmlWVmJoYw==

//var_dump(base64_encode('jshot:G<j+,T9T/NYitfJ3=.&nwAL|lEy(eg'));
// auth token anNob3Q6RzxqKyxUOVQvTllpdGZKMz0uJm53QUx8bEV5KGVn


// REST for Retoure
/*$data = array(
	"receiverId" => "deu",
	"customerReference" => "string",
	"shipmentReference" => "string",
	"senderAddress" => array(
		"name1" => "string",
		"name2" => "string",
		"name3" => "string",
		"streetName" => "string",
		"houseNumber" => "string",
		"postCode" => "12345",
		"city" => "string",
		"country" => array(
			"countryISOCode" => "string",
			"country" => "string",
			"state" => "string"
		)
	),
	"email" => "user@example.com",
	"telephoneNumber" => "string",
	"weightInGrams" => 0,
	"value" => 0,
	"customsDocument" => array(
		"currency" => "EUR",
		"originalShipmentNumber" => "string",
		"originalOperator" => "string",
		"acommpanyingDocument" => "string",
		"originalInvoiceNumber" => "string",
		"originalInvoiceDate" => "string",
		"comment" => "string",
		"positions" => array(
			"positionDescription" => "string",
			"count" => 0,
			"weightInGrams" => 0,
			"values" => 0,
			"originCountry" => "string",
			"articleReference" => "string",
			"tarifNumber" => "string"
		)
	),
	"returnDocumentType" => "SHIPMENT_LABEL"
);

$ch = curl_init();
curl_setopt_array($ch, array(
	CURLOPT_HTTPHEADER => array(
		'accept: application/json',
		'authorization: Basic anNob3Q6RzxqKyxUOVQvTllpdGZKMz0uJm53QUx8bEV5KGVn',
		'Content-type: application/json',
		'DPDHL-User-Authentication-Token: MjIyMjIyMjIyMl9jdXN0b21lcjp1QlFiWjYyIVppQmlWVmJoYw=='
	),
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_POST => true,
	CURLOPT_URL => 'https://cig.dhl.de/services/sandbox/rest/returns',
	CURLOPT_POSTFIELDS => $data,
	//CURLOPT_RETURNTRANSFER => true,
));

//$result = curl_exec($ch);
$result = json_decode($result);
curl_close($ch);
var_dump($result);
*/

// End of page
llxFooter();
$db->close();
