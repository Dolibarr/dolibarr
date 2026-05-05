<?php
/* Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2008      Raphael Bertrand     <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2015 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2017-2018 Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018-2020 Frédéric France      <frederic.france@netlogic.fr>
 * Copyright (C) 2019      Pierre Ardoin      	<mapiolca@me.com>
 * Copyright (C) 2023      Daniel Bauer     	<d.bauer@elaax.net>
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
 *	\file       htdocs/core/modules/propale/doc/pdf_elaax.modules.php
 *	\ingroup    propale
 *	\brief      File of Class to generate PDF proposal with ELAAX template
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/propale/modules_propale.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/**
 *	Class to generate PDF proposal elaax
 */
class pdf_elaax extends ModelePDFPropales
{
	/**
	 * @var DoliDb Database handler
	 */
	public $db;

	/**
	 * @var string model name
	 */
	public $name;

	/**
	 * @var string model description (short text)
	 */
	public $description;

	/**
	 * @var string	Save the name of generated file as the main doc when generating a doc with this template
	 */
	public $update_main_doc_field;

	/**
	 * @var string document type
	 */
	public $type;

	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.6 = array(5, 6)
	 */
	public $phpmin = array(5, 6);

	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr';

	/**
	 * @var int page_largeur
	 */
	public $page_largeur;

	/**
	 * @var int page_hauteur
	 */
	public $page_hauteur;

	/**
	 * @var array format
	 */
	public $format;

	/**
	 * @var int marge_gauche
	 */
	public $marge_gauche;

	/**
	 * @var int marge_droite
	 */
	public $marge_droite;

	/**
	 * @var int marge_haute
	 */
	public $marge_haute;

	/**
	 * @var int marge_basse
	 */
	public $marge_basse;

	/**
	 * Issuer
	 * @var Societe Object that emits
	 */
	public $emetteur;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Translations
		$langs->loadLangs(array("main", "bills"));

		$this->db = $db;
		$this->name = "elaax";
		$this->description = $langs->trans('DocModelelaaxDescription');
		$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template

		// Dimension page
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = isset($conf->global->MAIN_PDF_MARGIN_LEFT) ? $conf->global->MAIN_PDF_MARGIN_LEFT : 10;
		$this->marge_droite = isset($conf->global->MAIN_PDF_MARGIN_RIGHT) ? $conf->global->MAIN_PDF_MARGIN_RIGHT : 10;
		$this->marge_haute = isset($conf->global->MAIN_PDF_MARGIN_TOP) ? $conf->global->MAIN_PDF_MARGIN_TOP : 10;
		$this->marge_basse = isset($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? $conf->global->MAIN_PDF_MARGIN_BOTTOM : 10;

		$this->option_logo = 1; // Display logo
		$this->option_tva = 1; // Manage the vat option FACTURE_TVAOPTION
		$this->option_modereg = 1; // Display payment mode
		$this->option_condreg = 1; // Display payment terms
		$this->option_multilang = 1; // Available in several languages
		$this->option_escompte = 0; // Displays if there has been a discount
		$this->option_credit_note = 0; // Support credit notes
		$this->option_freetext = 1; // Support add of a personalised text
		$this->option_draft_watermark = 1; // Support add of a watermark on drafts
		$this->watermark = '';

		// Get source company
		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if was not defined
		}

		// Define position of columns
		$this->posxdesc = $this->marge_gauche + 1;
		if (!empty($conf->global->PRODUCT_USE_UNITS)) {
			$this->posxtva = 103;
			$this->posxup = 115;
			$this->posxqty = 137;
			$this->posxunit = 151;
		} else {
			$this->posxtva = 107;
			$this->posxup = 118;
			$this->posxqty = 147;
			$this->posxunit = 162;
		}
		$this->posxdiscount = 162;
		$this->postotalht = 174;
		if (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) || !empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN)) {
			$this->posxtva = $this->posxup;
		}
		$this->posxpicture = $this->posxtva - (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH) ? 20 : $conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH); // width of images
		if ($this->page_largeur < 210) { // To work with US executive format
			$this->posxpicture -= 20;
			$this->posxtva -= 20;
			$this->posxup -= 20;
			$this->posxqty -= 20;
			$this->posxunit -= 20;
			$this->posxdiscount -= 20;
			$this->postotalht -= 20;
		}

		$this->tva = array();
		$this->tva_array = array();
		$this->localtax1 = array();
		$this->localtax2 = array();
		$this->atleastoneratenotnull = 0;
		$this->atleastonediscount = 0;
	}


	// AGBS Text am unterem ände der angebote 

	protected function _agbs($pdf)
	{

		$default_font_size = 10;
		$pdf->SetFont('', 'B', $default_font_size + 2);
		$pdf->MultiCell(0, 10, " ", 0, " ", "L");
		$pdf->MultiCell(0, 15, "ALLGEMEINE GESCHÄFTSBEDINGUNGEN (AGB)
Daniel Bauer – ELAAX, Am Baunsberg 1, 34270 Schauenburg
(Stand 03/2023)", 0, "", "L",);
		$pdf->MultiCell(0, 5, " ", 0, " ", "L");
		$pdf->SetFont('', 'B', $default_font_size + 1);
		$pdf->MultiCell(0, 6,  "§ 1 Geltungsbereich", 0, "L");
		$pdf->SetFont('', '', $default_font_size);
		$pdf->MultiCell(0, 5, "(1)	Diese Allgemeinen Geschäftsbedingungen (nachfolgend „AGB“) gelten für alle Verträge über die Lieferung von Waren, die ein Kunde (nachfolgend „Kunde“), gleich ob Verbraucher oder Unternehmer, mit ELAAX abschließt. Hiermit wird die Einbeziehung von eigenen Bedingungen des Kunden widersprochen.", 0, "L");
		$pdf->MultiCell(0, 5, "(2)	Unsere Leistungen sind grundsätzlich an Verbraucher i.S.d. § 13 BGB gerichtet. Verbraucher ist jede natürliche Person, die ein Rechtsgeschäft zu Zwecken abschließt, die überwiegend weder ihrer gewerblichen noch ihrer selbständigen beruflichen Tätigkeit zugerechnet werden kann. Im Folgenden werden diese als „Verbraucher“ bezeichnet.", 0, "L");
		$pdf->MultiCell(0, 5, "(3)	Zudem bieten wir unsere Leistungen auch Unternehmern an, d.h. eine natürliche oder juristische Person oder eine rechtsfähige Personengesellschaft, die bei Abschluss eines Rechtsgeschäfts in Ausübung ihrer gewerblichen oder selbständigen beruflichen Tätigkeit handelt (§ 14 BGB). Für Unternehmer gelten diese AGB ebenfalls unter z.T. abweichenden Bedingungen, auf die gesondert hingewiesen wird. Im Folgenden werden diese als „Geschäftskunden“ bezeichnet.", 0, "L");
		$pdf->MultiCell(0, 10, " ", 0, " ", "L");
		$pdf->SetFont('', 'B', $default_font_size + 1);
		$pdf->MultiCell(0, 6,  "§ 2 Vertragsschluss", 0, "L");
		$pdf->SetFont('', '', $default_font_size);
		$pdf->MultiCell(0, 5, "(1)	Die Darstellung der Produkte im ELAAX- Onlineshop sind freibleibende und unverbindliche Informationen und verstehen sich als Einladung zur Offertlegung durch den Kunden.", 0, "L");
		$pdf->MultiCell(0, 5, "(2)	Die Bestellung durch den Kunden ist das Angebot an ELAXX zum Abschluss eines Vertrages.", 0, "L");
		$pdf->MultiCell(0, 5, "(3)	Der Kunde wird per E-Mail über den erfolgreichen Abschluss seiner Bestellung benachrichtigt, sobald der Bestellvorgang abgeschlossen ist (Bestellbestätigung). Die Bestellbestätigung stellt noch keine Annahme des Angebotes dar, sondern informiert den Kunden lediglich, dass die Bestellung eingegangen ist.", 0, "L");
		$pdf->MultiCell(0, 5, "(4)	ELAAX kann Angebote ohne Angabe von Gründen ablehnen. Im Fall einer ausdrücklichen Ablehnung des Angebots wird der Kunde unverzüglich davon verständigt. Schweigen seitens ELAAX ist jedoch keine Annahme des Angebots.", 0, "L");
		$pdf->MultiCell(0, 5, "(5)	Der Kaufvertrag kommt zustande, wenn ELAAX das Angebot annimmt (Annahme) oder innerhalb von fünf Werktagen das/die bestellte(n) Produkt(e) an den Kunden tatsächlich versendet und dieser Versand mit einer weiteren E-Mail (Versandbestätigung) an den Kunden bestätigt wird. ", 0, "L");
		$pdf->MultiCell(0, 10, " ", 0, " ", "L");
		$pdf->SetFont('', 'B', $default_font_size + 1);
		$pdf->MultiCell(0, 6,  "§ 3 Anlegen eines Benutzerkontos", 0, "L");
		$pdf->SetFont('', '', $default_font_size);
		$pdf->MultiCell(0, 5, "(1)	Der Kunde kann ein persönliches Benutzerkonto anlegen, über das er seine Bestellungen bei abwickeln kann. Bei erfolgreicher Anlage des Benutzerkontos erhält der Kunde eine Bestätigung per E-Mail. Danach kann er sich mittels seiner E-Mail-Adresse und seines Passworts anmelden. Der Kunde hat über sein Benutzerkonto die Möglichkeit, die von ihm getätigten Bestellungen und seine Kontoeinstellungen in seinem Benutzerkonto abzurufen.", 0, "L");
		$pdf->MultiCell(0, 5, "(2)	Der Kunde hat keinen Anspruch auf Nutzung und Funktion des Benutzerkontos generell oder nach einem bestimmten Stand der Technik. ELAAX übernimmt insbesondere keine Verantwortung für die ununterbrochene Verfügbarkeit der Benutzerkonten und der darin verwalteten Daten. Auch Störungen im Kommunikationsnetz, Ausfälle der Server, Stromausfälle und sonstige elektronische oder mechanische Fehler können nicht ausgeschlossen werden. ELAAX übernimmt diesbezüglich keine Haftung.", 0, "L");
		$pdf->MultiCell(0, 10, " ", 0, " ", "L");
		$pdf->SetFont('', 'B', $default_font_size + 1);
		$pdf->MultiCell(0, 6,  "§ 4 Widerrufsrecht (Rücktrittsrecht)", 0, "L");
		$pdf->SetFont('', '', $default_font_size);
		$pdf->MultiCell(0, 5, "Verbrauchern steht ein Widerrufsrecht zu.", 0, "L");
		$pdf->MultiCell(0, 5, " ", 0, " ", "L");
		$pdf->SetFont('', 'B', $default_font_size);
		$pdf->MultiCell(0, 6,  "WIDERRUFSBELEHRUNG", 0, "L");
		$pdf->SetFont('', '', $default_font_size);
		$pdf->MultiCell(0, 5, "Widerrufsrecht ", 0, "L");
		$pdf->MultiCell(0, 55, "Sie haben das Recht binnen vierzehn Tagen ohne Angabe von Gründen diesen Vertrag zu widerrufen. Die Widerrufsfrist beträgt vierzehn Tage ab dem Tag des Vertragsabschlusses, bzw. bei Kaufverträgen ab dem Tag an dem Sie oder ein von Ihnen benannter Dritter, der nicht Beförderer ist, die Waren in Besitz genommen haben bzw. hat. 
Um das Widerrufsrecht auszuüben, müssen Sie uns, Daniel Bauer – ELAAX, Am Baunsberg 1, 34270 Schauenburg Deutschland,  E-Mail: info@elaax.de mittels einer eindeutigen Erklärung (z. B. ein mit der Post versandter Brief oder eine E-Mail) über Ihren Entschluss, diesen Vertrag zu widerrufen, informieren. Sie können dafür das beigefügte Muster-Widerrufsformular verwenden, das jedoch nicht vorgeschrieben ist.
Zur Wahrung der Widerrufsfrist reicht es aus, dass Sie die Mitteilung über die Ausübung des Widerrufsrechts vor Ablauf der Widerrufsfrist absenden.", 0, "L");
		$pdf->SetFont('', 'B', $default_font_size);
		$pdf->MultiCell(0, 5, "FOLGEN DES WIDERRUFS ", 0, "L");
		$pdf->SetFont('', '', $default_font_size);
		$pdf->MultiCell(0, 5, "Wenn Sie diesen Vertrag widerrufen, haben wir Ihnen alle Zahlungen, die wir von Ihnen erhalten haben, einschließlich der Lieferkosten (mit Ausnahme der zusätzlichen Kosten, die sich daraus ergeben, dass Sie eine andere Art der Lieferung als die von uns angebotene, günstigste Standardlieferung gewählt haben), unverzüglich und spätestens binnen vierzehn Tagen ab dem Tag zurückzuzahlen, an dem die Mitteilung über Ihren Widerruf dieses Vertrags bei uns eingegangen ist. Für diese Rückzahlung verwenden wir dasselbe Zahlungsmittel, das Sie bei der ursprünglichen Transaktion eingesetzt haben, es sei denn, mit Ihnen wurde ausdrücklich etwas anderes vereinbart; in keinem Fall werden Ihnen wegen dieser Rückzahlung Entgelte berechnet.
Sie tragen die unmittelbaren Kosten der Rücksendung der Waren. Sie müssen für einen etwaigen Wertverlust der Waren nur aufkommen, wenn dieser Wertverlust auf einen zur Prüfung der Beschaffenheit, Eigenschaften und Funktionsweise der Waren nicht notwendigen Umgang mit ihnen zurückzuführen ist", 0, "L");
		$pdf->MultiCell(0, 5, " ", 0, " ", "L");
		$pdf->SetFont('', 'B', $default_font_size);
		$pdf->MultiCell(0, 5, "MUSTER-WIDERRUFSFORMULAR: ", 0, "L");
		$pdf->SetFont('', '', $default_font_size);
		$pdf->Ln(10);
		$pdf->MultiCell(0, 5, "
		
		Muster-Widerrufsformular
(Wenn Sie den Vertrag widerrufen wollen, dann füllen Sie bitte dieses Formular aus und senden Sie es zurück.)
–
An [hier ist der Name, die Anschrift und die E-Mail-Adresse des Unternehmers durch den Unternehmer einzufügen]:
–
Hiermit widerrufe(n) ich/wir (*) den von mir/uns (*) abgeschlossenen Vertrag über den Kauf der folgenden Waren (*)/die Erbringung der folgenden Dienstleistung (*)
–
Bestellt am (*)/erhalten am (*)
–
Name des/der Verbraucher(s)
–
Anschrift des/der Verbraucher(s)
–
Unterschrift des/der Verbraucher(s) (nur bei Mitteilung auf Papier)
–
Datum
(*) Unzutreffendes streichen.

		", 0, "L");
		$pdf->Ln(10);

		$pdf->MultiCell(0, 5, " ", 0, " ", "L");

		$pdf->MultiCell(0, 15, "Das Widerrufsrecht besteht, soweit die Parteien nichts anderes vereinbart haben, u.a. nicht bei folgenden Verträgen:
", 0, " ", "L");
		$pdf->MultiCell(0, 15, "a) Verträge zur Lieferung von Waren, die nicht vorgefertigt sind und für deren Herstellung eine individuelle Auswahl oder Bestimmung durch den Verbraucher maßgeblich ist oder die eindeutig auf die persönlichen Bedürfnisse des Verbrauchers zugeschnitten sind,
 ", 0, " ", "L");
		$pdf->MultiCell(0, 15, "b) Verträge zur Lieferung von Ton- oder Videoaufnahmen oder Computersoftware in einer versiegelten Packung, wenn die Versiegelung nach der Lieferung entfernt wurde,
 ", 0, " ", "L");

		$pdf->MultiCell(0, 5, "c) Verträge, bei denen der Verbraucher den Unternehmer ausdrücklich aufgefordert hat, ihn aufzusuchen, um dringende Reparatur- oder Instandhaltungsarbeiten vorzunehmen; dies gilt nicht hinsichtlich weiterer bei dem Besuch erbrachter Dienstleistungen, die der Verbraucher nicht ausdrücklich verlangt hat, oder hinsichtlich solcher bei dem Besuch gelieferter Waren, die bei der Instandhaltung oder Reparatur nicht unbedingt als Ersatzteile benötigt werden,
 ", 0, " ", "L");


		$pdf->MultiCell(0, 5, " ", 0, " ", "L");
		$pdf->SetFont('', 'B', $default_font_size);
		$pdf->MultiCell(0, 5, "ENDE DER WIDERRUFSBELEHRUNG: ", 0, "L");
		$pdf->SetFont('', '', $default_font_size);
		$pdf->MultiCell(0, 5, "Geschäftskunden ist als Unternehmern die Ausübung des Widerrufsrechts gem. § 312g BGB verwehrt. ", 0, "L");

		$pdf->MultiCell(0, 10, " ", 0, " ", "L");
		$pdf->SetFont('', 'B', $default_font_size + 1);
		$pdf->MultiCell(0, 6,  "§ 5 Mitwirkungspflichten des Kunden", 0, "L");
		$pdf->SetFont('', '', $default_font_size);
		$pdf->MultiCell(0, 5, "(1)	Der Kunde ist verpflichtet, unser Angebot sorgfältig auf Richtigkeit und Zweckmäßigkeit zu prüfen. Das gilt insbesondere für Projektangebote, in denen wir als solche bezeichneten Annahmen getroffen haben, die wir unserer Kalkulation und Leistungsbeschreibung zugrunde gelegt haben. Treffen derartige Annahmen nicht zu, wird uns der Besteller davon unterrichten, damit wir das Angebot korrigieren können. ", 0, "L");
		$pdf->MultiCell(0, 5, "(2)	Während Testbetrieben und während der Installation wird der Kunde die Anwesenheit kompetenter und geschulter Mitarbeiter sicherstellen und andere Arbeiten mit der Computeranlage erforderlichenfalls einstellen. Er wird vor jeder Installation für die Sicherung aller seiner Daten sorgen. ", 0, "L");
		$pdf->MultiCell(0, 10, " ", 0, " ", "L");
		$pdf->SetFont('', 'B', $default_font_size + 1);
		$pdf->MultiCell(0, 6,  "§ 6 Lieferung, Lieferadresse, Gefahrenübergang, Versandkosten", 0, "L");
		$pdf->SetFont('', '', $default_font_size);
		$pdf->MultiCell(0, 5, "(1)	Der Versand ist derzeit grundsätzlich nur innerhalb Deutschlands möglich.", 0, "L");
		$pdf->MultiCell(0, 5, "(2)	Für Terminlieferungen, Lieferungen in das Ausland und Artikel mit Sonderabmessungen oder - gewichten behalten wir uns vor, gesondert Versandkosten zu berechnen.", 0, "L");
		$pdf->MultiCell(0, 5, "(3)	Wir behalten uns bis zur Lieferung handelsübliche technische Änderungen, insbesondere Verbesserungen vor, wenn hierdurch nur unwesentliche Änderungen in der Beschaffenheit eintreten und der Besteller nicht unzumutbar beeinträchtigt wird.", 0, "L");
		$pdf->MultiCell(0, 5, "(4)	Das Risiko für Verlust oder Beschädigung der Ware geht erst auf den Kunden über, wenn diese an den Kunden oder einen von ihm bestimmten Dritten (der nicht der Beförderer ist), abgeliefert wird.", 0, "L");
		$pdf->MultiCell(0, 5, "(5)	Handelt der Kunde als Unternehmer, so geht die Gefahr des zufälligen Untergangs und der zufälligen Verschlechterung der verkauften Ware auf den Geschäftskunden über, sobald ELAAX die Ware der zur Ausführung Versendung bestimmten Person oder Anstalt ausgeliefert hat.", 0, "L");
		$pdf->MultiCell(0, 10, " ", 0, " ", "L");
		$pdf->SetFont('', 'B', $default_font_size + 1);
		$pdf->MultiCell(0, 6,  "§ 7 Preise, Zahlungsarten, Fälligkeiten", 0, "L");
		$pdf->SetFont('', '', $default_font_size);
		$pdf->MultiCell(0, 5, "(1)	Die Verrechnung erfolgt ausschließlich in Euro (€). ", 0, "L");
		$pdf->MultiCell(0, 5, "(2)	Die im Internet angeführten Preise enthalten nicht die Kosten für die Zustellung. Für alle Bestellungen haben Sie hierfür die von uns mitgeteilten und erläuterten Versandkosten zu tragen.", 0, "L");
		$pdf->MultiCell(0, 5, "(3)	Sofern sich aus der Produktbeschreibung des Verkäufers nichts anderes ergibt, handelt es sich bei den angegebenen Preisen um Gesamtpreise, die gesetzliche Umsatzsteuer ist ausgewiesen.", 0, "L");
		$pdf->MultiCell(0, 5, "(4)	Wird im Auftrag des Kunden ein Kostenvoranschlag erstellt, so sind die Kosten entsprechend Zeitaufwand vom Besteller zu erstatten. ", 0, "L");
		$pdf->MultiCell(0, 5, "(5)	Unsere Kostenvoranschläge stellen eine Einschätzung der Positionskosten mit Standardkomponenten dar. Die Nachlieferung von einzelnen Komponenten ist zum angegebenen Einzelpreis möglich. Autorenkorrekturen, Änderungen und Arbeiten, die bis jetzt nicht erkennbar sind oder über den beschriebenen Aufwand hinausgehen, werden nach Aufwand zusätzlich berechnet. Stundenlohnarbeiten werden nach Stundennachweisen abgerechnet. ", 0, "L");
		$pdf->MultiCell(0, 5, "(6)	Die Zahlungsmöglichkeit/en wird/werden dem Kunden auf unserer Internetpräsenz mitgeteilt. Im Moment sind dies, PayPal, Überweisung und Lastschrifteinzug.", 0, "L");
		$pdf->MultiCell(0, 5, "(7)	Unsere Forderungen gegen den Kunden werden mit Abschluss des Vertrags sofort fällig.", 0, "L");
		$pdf->MultiCell(0, 5, "(8)	Die erste Mahnung wird mit einer Mahngebühr von 3,50 € berechnet.", 0, "L");
		$pdf->MultiCell(0, 5, "(9)	Ab der zweiten Mahnung wird eine Mahngebühr von 5,- € berechnet.", 0, "L");
		$pdf->MultiCell(0, 5, "(10)	Ab der vierten Mahnung behalten wir uns die Abgabe an ein Inkassobüro und ggf. eine Anzeigenerstattung vor.", 0, "L");
		$pdf->MultiCell(0, 10, " ", 0, " ", "L");
		$pdf->SetFont('', 'B', $default_font_size + 1);
		$pdf->MultiCell(0, 6,  "§ 8 Eigentumsvorbehalt", 0, "L");
		$pdf->SetFont('', '', $default_font_size);
		$pdf->MultiCell(0, 5, "(1)	Alle Lieferungen an Verbraucher erfolgen unter einfachem Eigentumsvorbehalt. Somit bleibt die Ware bis zur vollständigen Begleichung des Kaufpreises im Eigentum von Daniel Bauer - ELAAX.", 0, "L");
		$pdf->MultiCell(0, 5, "(2)	Alle Lieferungen an Geschäftskunden erfolgen unter erweitertem Eigentumsvorbehalt. Somit bleibt die Ware bis zur vollständigen Begleichung aller Forderungen aus der laufenden Geschäftsbeziehung im Eigentum von Daniel Bauer - ELAAX.", 0, "L");
		$pdf->MultiCell(0, 5, "(3)	Daniel Bauer - ELAAX behält sich das Recht am Quellcode von eigens entwickelten Softwareprodukten vor.", 0, "L");
		$pdf->MultiCell(0, 45, "(4)	An allen dem Kunden überlassenen Unterlagen, insbesondere Datenträgern, Dokumentationen, Abbildungen, Zeichnungen, Kalkulationen behalten wir uns Eigentums- und Urheberrechte vor; sie dürfen nicht für andere als vertragsgemäße Zwecke benutzt und Dritten nicht zugänglich gemacht werden und sind uns unverzüglich frei Haus zurückzugeben, wenn der Vertrag beendet oder soweit der vertragliche Nutzungszweck erfüllt ist. Der Kunde ist verpflichtet, die darin enthaltenen Informationen und Daten geheim zu halten. Dies gilt insbesondere für solche Unterlagen und Informationen, die als „vertraulich“ bezeichnet sind. Wir sind berechtigt, Unterlagen jederzeit herauszuverlangen, wenn die Geheimhaltung nicht sichergestellt ist. Die Verpflichtung zur Geheimhaltung wird von einer Beendigung des Vertrages nicht berührt.", 0, "L");
		$pdf->MultiCell(0, 5, "(5)	Unsere Waren sind ausschließlich für die Nutzung durch den Kunden bestimmt. Beabsichtigt der Geschäftskunde, die von uns erworbene Ware an einen Verbraucher, Unternehmer oder an einen Wiederverkäufer zu liefern, der seinerseits Verbraucher oder Unternehmer mit derartigen Waren beliefert, so hat er uns darauf hinzuweisen.", 0, "L");
		$pdf->MultiCell(0, 5, "(6)	Medien sämtlicher Art sind zweckgebunden und dürfen nur im Rahmen der gesetzlichen Bestimmungen an Dritte weitergegeben werden; dies bedarf ggf. der Genehmigung.", 0, "L");
		$pdf->MultiCell(0, 10, " ", 0, " ", "L");
		$pdf->SetFont('', 'B', $default_font_size + 1);
		$pdf->MultiCell(0, 6,  "§ 9 Datenverarbeitung und Datenschutz", 0, "L");
		$pdf->SetFont('', '', $default_font_size);
		$pdf->MultiCell(0, 5, "ELAAX speichert und verarbeitet bestimmte personenbezogene Daten im Rahmen der Bestellung und zur Verwaltung des Benutzerkontos. Die Information zur Verarbeitung dieser Daten nach Art 13 und 14 DSGVO ist auf unserer Website unter Datenschutzerklärung zu finden. (www.elaax.de)", 0, "L");
		$pdf->MultiCell(0, 10, " ", 0, " ", "L");
		$pdf->SetFont('', 'B', $default_font_size + 1);
		$pdf->MultiCell(0, 6,  "§ 10 Haftung/ Schadenersatz", 0, "L");
		$pdf->SetFont('', '', $default_font_size);
		$pdf->MultiCell(0, 5, "(1)	ELAAX haftet unbeschränkt bei Vorsatz oder grober Fahrlässigkeit, für die Verletzung von Leben, Leib oder Gesundheit, nach den Vorschriften des Produkthaftungsgesetzes sowie im Falle der Übernahme einer Garantie. ", 0, "L");
		$pdf->MultiCell(0, 5, "(2)	Bei leicht fahrlässiger Verletzung einer Pflicht, die wesentlich für die Erreichung des Vertragszwecks ist (Kardinalpflicht), ist die Haftung der Höhe nach begrenzt auf den Schaden, der nach der Art des fraglichen Geschäfts vorhersehbar und typisch ist.", 0, "L");
		$pdf->MultiCell(0, 5, "(3)	Eine weitergehende Haftung besteht nicht.", 0, "L");
		$pdf->MultiCell(0, 5, "(4)	Die vorstehende Haftungsbeschränkung gilt auch für die persönliche Haftung der Mitarbeiter, Vertreter und Organe von ELAAX.", 0, "L");
		$pdf->MultiCell(0, 10, " ", 0, " ", "L");
		$pdf->SetFont('', 'B', $default_font_size + 1);
		$pdf->MultiCell(0, 6,  "§ 11 Anwendbares Recht", 0, "L");
		$pdf->SetFont('', '', $default_font_size);
		$pdf->MultiCell(0, 5, "Für sämtliche Rechtsbeziehungen der Parteien gilt das Recht der Bundesrepublik Deutschland unter Ausschluss der Gesetze über den internationalen Kauf beweglicher Waren. Bei Verbrauchern gilt diese Rechtswahl nur insoweit, als nicht der gewährte Schutz durch zwingende Bestimmungen des Rechts des Staates, in dem der Verbraucher seinen gewöhnlichen Aufenthalt hat, entzogen wird.", 0, "L");
		$pdf->MultiCell(0, 10, " ", 0, " ", "L");
		$pdf->SetFont('', 'B', $default_font_size + 1);
		$pdf->MultiCell(0, 6,  "§ 12 Alternative Streitbeilegung", 0, "L");
		$pdf->SetFont('', '', $default_font_size);
		$pdf->MultiCell(0, 35, "OS-Plattform entsprechend der EU-Verordnung Nr. 524/2013:
Die EU-Kommission stellt ab 15.02.2016 gemäß Verordnung eine Plattform (OS) bereit, die der Beilegung außergerichtlicher Streitigkeiten aus Online-Rechtsgeschäften dient. Die OS-Plattform finden Sie unter dem externen Link http://ec.europa.eu/consumers/odr/.
", 0, "L");

		$pdf->MultiCell(0, 15, "Die vorstehenden AGB gelten vom 01. März 2023 an.", 0, "R");
		return $pdf;
	}





	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		Propal		$object				Object to generate
	 *  @param		Translate	$outputlangs		Lang output object
	 *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *  @return     int             				1=OK, 0=KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines;

		dol_syslog("write_file outputlangs->defaultlang=".(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (!empty($conf->global->MAIN_USE_FPDF)) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load translation files required by page
		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "propal", "products"));

		global $outputlangsbis;
		$outputlangsbis = null;
		if (!empty($conf->global->PDF_USE_ALSO_LANGUAGE_CODE) && $outputlangs->defaultlang != $conf->global->PDF_USE_ALSO_LANGUAGE_CODE) {
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang($conf->global->PDF_USE_ALSO_LANGUAGE_CODE);
			$outputlangsbis->loadLangs(array("main", "dict", "companies", "bills", "products", "propal"));
		}

		//  Show Draft Watermark
		if ($object->statut == $object::STATUS_DRAFT && (!empty($conf->global->PROPALE_DRAFT_WATERMARK))) {
			$this->watermark = $conf->global->PROPALE_DRAFT_WATERMARK;
		}

		$nblines = count($object->lines);

		// Loop on each lines to detect if there is at least one image to show
		$realpatharray = array();
		$this->atleastonephoto = false;
		if (!empty($conf->global->MAIN_GENERATE_PROPOSALS_WITH_PICTURE)) {
			$objphoto = new Product($this->db);

			for ($i = 0; $i < $nblines; $i++) {
				if (empty($object->lines[$i]->fk_product)) {
					continue;
				}

				$objphoto->fetch($object->lines[$i]->fk_product);
				//var_dump($objphoto->ref);exit;
				if (!empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) {
					$pdir[0] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product').$objphoto->id."/photos/";
					$pdir[1] = get_exdir(0, 0, 0, 0, $objphoto, 'product').dol_sanitizeFileName($objphoto->ref).'/';
				} else {
					$pdir[0] = get_exdir(0, 0, 0, 0, $objphoto, 'product'); // default
					$pdir[1] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product').$objphoto->id."/photos/"; // alternative
				}

				$arephoto = false;
				$realpath = '';
				foreach ($pdir as $midir) {
					if (!$arephoto) {
						if ($conf->entity != $objphoto->entity) {
							$dir = $conf->product->multidir_output[$objphoto->entity].'/'.$midir; //Check repertories of current entities
						} else {
							$dir = $conf->product->dir_output.'/'.$midir; //Check repertory of the current product
						}
						foreach ($objphoto->liste_photos($dir, 1) as $key => $obj) {
							if (empty($conf->global->CAT_HIGH_QUALITY_IMAGES)) {		// If CAT_HIGH_QUALITY_IMAGES not defined, we use thumb if defined and then original photo
								if ($obj['photo_vignette']) {
									$filename = $obj['photo_vignette'];
								} else {
									$filename = $obj['photo'];
								}
							} else {
								$filename = $obj['photo'];
							}

							$realpath = $dir.$filename;
							$arephoto = true;
							$this->atleastonephoto = true;
						}
					}
				}

				if ($realpath && $arephoto) {
					$realpatharray[$i] = $realpath;
				}
			}
		}

		if (count($realpatharray) == 0) {
			$this->posxpicture = $this->posxtva;
		}

		if ($conf->propal->multidir_output[$conf->entity]) {
			$object->fetch_thirdparty();

			$deja_regle = 0;

			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->propal->multidir_output[$conf->entity];
				$file = $dir."/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->propal->multidir_output[$object->entity]."/".$objectref;
				$file = $dir."/".$objectref.".pdf";
			}

			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}

			if (file_exists($dir)) {
				// Add pdfgeneration hook
				if (!is_object($hookmanager)) {
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager = new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

				// Set nblines with the new content of lines after hook
				$nblines = count($object->lines);
				//$nbpayments = count($object->getListOfPayments());

				// Create pdf instance
				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
				$pdf->SetAutoPageBreak(1, 0);

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));
				// Set path to the background PDF File
				if (!empty($conf->global->MAIN_ADD_PDF_BACKGROUND)) {
					$logodir = $conf->mycompany->dir_output;
					if (!empty($conf->mycompany->multidir_output[$object->entity])) {
						$logodir = $conf->mycompany->multidir_output[$object->entity];
					}
					$pagecount = $pdf->setSourceFile($logodir.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
					$tplidx = $pdf->importPage(1);
				}

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("PdfCommercialProposalTitle"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfCommercialProposalTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
				if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) {
					$pdf->SetCompression(false);
				}

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

				// Set $this->atleastonediscount if you have at least one discount
				for ($i = 0; $i < $nblines; $i++) {
					if ($object->lines[$i]->remise_percent) {
						$this->atleastonediscount++;
					}
				}
				if (empty($this->atleastonediscount)) {
					$delta = ($this->postotalht - $this->posxdiscount);
					$this->posxpicture += $delta;
					$this->posxtva += $delta;
					$this->posxup += $delta;
					$this->posxqty += $delta;
					$this->posxunit += $delta;
					$this->posxdiscount += $delta;
					// post of fields after are not modified, stay at same position
				}

				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}
				$pagenb++;

				$heightforinfotot = 40; // Height reserved to output the info and total part
				$heightforsignature = empty($conf->global->PROPAL_DISABLE_SIGNATURE) ? (pdfGetHeightForHtmlContent($pdf, $outputlangs->transnoentities("ProposalCustomerSignature")) + 10) : 0;
				$heightforfreetext = (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT) ? $conf->global->MAIN_PDF_FREETEXT_HEIGHT : 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + 8; // Height reserved to output the footer (value include bottom margin)
				if (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS)) {
					$heightforfooter += 6;
				}
				//print $heightforinfotot + $heightforsignature + $heightforfreetext + $heightforfooter;exit;

				$top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs, $outputlangsbis);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);


				$tab_top = 90 + $top_shift;
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD) ? 42 + $top_shift : 10);

				// Incoterm
				$height_incoterms = 0;
				if (!empty($conf->incoterm->enabled)) {
					$desc_incoterms = $object->getIncotermsForPDF();
					if ($desc_incoterms) {
						$tab_top -= 2;

						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top - 1, dol_htmlentitiesbr($desc_incoterms), 0, 1);
						$nexY = $pdf->GetY();
						$height_incoterms = $nexY - $tab_top;

						// Rect takes a length in 3rd parameter
						$pdf->SetDrawColor(192, 192, 192);
						$pdf->Rect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_incoterms + 1);

						$tab_top = $nexY + 6;
						$height_incoterms += 4;
					}
				}

				// Displays notes
				$notetoshow = empty($object->note_public) ? '' : $object->note_public;
				if (!empty($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_NOTE)) {
					// Get first sale rep
					if (is_object($object->thirdparty)) {
						$salereparray = $object->thirdparty->getSalesRepresentatives($user);
						$salerepobj = new User($this->db);
						$salerepobj->fetch($salereparray[0]['id']);
						if (!empty($salerepobj->signature)) {
							$notetoshow = dol_concatdesc($notetoshow, $salerepobj->signature);
						}
					}
				}
				// Extrafields in note
				$extranote = $this->getExtrafieldsInHtml($object, $outputlangs);
				if (!empty($extranote)) {
					$notetoshow = dol_concatdesc($notetoshow, $extranote);
				}
				if (!empty($conf->global->MAIN_ADD_CREATOR_IN_NOTE) && $object->user_author_id > 0) {
					$tmpuser = new User($this->db);
					$tmpuser->fetch($object->user_author_id);


					$creator_info = $langs->trans("CaseFollowedBy").' '.$tmpuser->getFullName($langs);
					if ($tmpuser->email) $creator_info .= ',  '.$langs->trans("EMail").': '.$tmpuser->email;
					if ($tmpuser->office_phone) $creator_info .= ', '.$langs->trans("Phone").': '.$tmpuser->office_phone;

					$notetoshow = dol_concatdesc($notetoshow, $creator_info);
				}

				if ($notetoshow) {
					$tab_top += 20;

					$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);
					$notetoshow = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top - 1, dol_htmlentitiesbr($notetoshow), 0, 1);
					$nexY = $pdf->GetY();
					$height_note = $nexY - $tab_top;

					/*// Rect takes a length in 3rd parameter
					$pdf->SetDrawColor(192, 192, 192);
					$pdf->Rect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 1);
					*/
					$tab_top = $nexY + 6;
				}

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 7;

				// Loop on each lines
				for ($i = 0; $i < $nblines; $i++) {
					$curY = $nexY;
					$pdf->SetFont('', '', $default_font_size - 1); // Into loop to work with multipage
					$pdf->SetTextColor(0, 0, 0);

					// Define size of image if we need it
					$imglinesize = array();
					if (!empty($realpatharray[$i])) {
						$imglinesize = pdf_getSizeForImage($realpatharray[$i]);
					}

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext + $heightforsignature + $heightforinfotot); // The only function to edit the bottom margin of current page to set it.
					$pageposbefore = $pdf->getPage();

					$showpricebeforepagebreak = 1;
					$posYAfterImage = 0;
					$posYAfterDescription = 0;

					// We start with Photo of product line
					if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($curY + $imglinesize['height']) > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforsignature + $heightforinfotot))) {	// If photo too high, we moved completely on new page
						$pdf->AddPage('', '', true);
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
						$pdf->setPage($pageposbefore + 1);

						$curY = $tab_top_newpage;

						// Allows data in the first page if description is long enough to break in multiples pages
						if (!empty($conf->global->MAIN_PDF_DATA_ON_FIRST_PAGE)) {
							$showpricebeforepagebreak = 1;
						} else {
							$showpricebeforepagebreak = 0;
						}
					}

					if (isset($imglinesize['width']) && isset($imglinesize['height'])) {
						$curX = $this->posxpicture - 1;
						$pdf->Image($realpatharray[$i], $curX + (($this->posxtva - $this->posxpicture - $imglinesize['width']) / 2), $curY, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300); // Use 300 dpi
						// $pdf->Image does not increase value return by getY, so we save it manually
						$posYAfterImage = $curY + $imglinesize['height'];
					}

					// Description of product line
					$curX = $this->posxdesc - 1;

					$pdf->startTransaction();
					pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->posxpicture - $curX, 3, $curX, $curY, $hideref, $hidedesc);
					$pageposafter = $pdf->getPage();
					if ($pageposafter > $pageposbefore) {	// There is a pagebreak
						$pdf->rollbackTransaction(true);
						$pageposafter = $pageposbefore;
						//print $pageposafter.'-'.$pageposbefore;exit;
						$pdf->setPageOrientation('', 1, $heightforfooter); // The only function to edit the bottom margin of current page to set it.
						pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->posxpicture - $curX, 3, $curX, $curY, $hideref, $hidedesc);

						$pageposafter = $pdf->getPage();
						$posyafter = $pdf->GetY();
						//var_dump($posyafter); var_dump(($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot))); exit;
						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforsignature + $heightforinfotot))) {	// There is no space left for total+free text
							if ($i == ($nblines - 1)) {	// No more lines, and no space left to show total, so we create a new page
								$pdf->AddPage('', '', true);
								if (!empty($tplidx)) {
									$pdf->useTemplate($tplidx);
								}
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
									$this->_pagehead($pdf, $object, 0, $outputlangs);
								}
								$pdf->setPage($pageposafter + 1);
							}
						} else {
							// We found a page break

							// Allows data in the first page if description is long enough to break in multiples pages
							if (!empty($conf->global->MAIN_PDF_DATA_ON_FIRST_PAGE)) {
								$showpricebeforepagebreak = 1;
							} else {
								$showpricebeforepagebreak = 0;
							}
						}
					} else // No pagebreak
					{
						$pdf->commitTransaction();
					}
					$posYAfterDescription = $pdf->GetY();

					$nexY = $pdf->GetY();
					$pageposafter = $pdf->getPage();

					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description or photo were moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage;
					}

					$pdf->SetFont('', '', $default_font_size - 1); // On repositionne la police par defaut

					// VAT Rate
					if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) && empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN)) {
						$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY($this->posxtva - 5, $curY);
						$pdf->MultiCell($this->posxup - $this->posxtva + 4, 3, $vat_rate, 0, 'R');
					}

					// Unit price before discount
					$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY($this->posxup, $curY);
					$pdf->MultiCell($this->posxqty - $this->posxup - 0.8, 3, $up_excl_tax . " €", 0, 'R', 0);

					// Quantity
					$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY($this->posxqty, $curY);
					$pdf->MultiCell($this->posxunit - $this->posxqty - 0.8, 4, $qty, 0, 'R'); // Enough for 6 chars

					// Unit
					if (!empty($conf->global->PRODUCT_USE_UNITS)) {
						$unit = pdf_getlineunit($object, $i, $outputlangs, $hidedetails, $hookmanager);
						$pdf->SetXY($this->posxunit, $curY);
						$pdf->MultiCell($this->posxdiscount - $this->posxunit - 0.8, 4, $unit, 0, 'L');
					}

					// Discount on line
					$pdf->SetXY($this->posxdiscount, $curY);
					if ($object->lines[$i]->remise_percent) {
						$pdf->SetXY($this->posxdiscount - 2, $curY);
						$remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
						$pdf->MultiCell($this->postotalht - $this->posxdiscount + 2, 3, $remise_percent, 0, 'R');
					}
					$formatter = new \NumberFormatter("de_DE", \NumberFormatter::DECIMAL); 
					// Total HT line
					$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY($this->postotalht, $curY);
					$vat =  floatval($vat_rate) / 100;
					$gsamtHT = floatval(str_replace(",",".", str_replace(".", "",$total_excl_tax)));
					$gsamtvat = $gsamtHT * $vat;
					$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->postotalht, 3, $formatter->format($gsamtHT + $gsamtvat) . " €", 0, 'R', 0);

					// Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
					if (!empty($conf->multicurrency->enabled) && $object->multicurrency_tx != 1) {
						$tvaligne = $object->lines[$i]->multicurrency_total_tva;
					} else {
						$tvaligne = $object->lines[$i]->total_tva;
					}

					$localtax1ligne = $object->lines[$i]->total_localtax1;
					$localtax2ligne = $object->lines[$i]->total_localtax2;
					$localtax1_rate = $object->lines[$i]->localtax1_tx;
					$localtax2_rate = $object->lines[$i]->localtax2_tx;
					$localtax1_type = $object->lines[$i]->localtax1_type;
					$localtax2_type = $object->lines[$i]->localtax2_type;

					if ($object->remise_percent) {
						$tvaligne -= ($tvaligne * $object->remise_percent) / 100;
					}
					if ($object->remise_percent) {
						$localtax1ligne -= ($localtax1ligne * $object->remise_percent) / 100;
					}
					if ($object->remise_percent) {
						$localtax2ligne -= ($localtax2ligne * $object->remise_percent) / 100;
					}

					$vatrate = (string) $object->lines[$i]->tva_tx;

					// Retrieve type from database for backward compatibility with old records
					if ((!isset($localtax1_type) || $localtax1_type == '' || !isset($localtax2_type) || $localtax2_type == '') // if tax type not defined
					&& (!empty($localtax1_rate) || !empty($localtax2_rate))) { // and there is local tax
						$localtaxtmp_array = getLocalTaxesFromRate($vatrate, 0, $object->thirdparty, $mysoc);
						$localtax1_type = isset($localtaxtmp_array[0]) ? $localtaxtmp_array[0] : '';
						$localtax2_type = isset($localtaxtmp_array[2]) ? $localtaxtmp_array[2] : '';
					}

					// retrieve global local tax
					if ($localtax1_type && $localtax1ligne != 0) {
						$this->localtax1[$localtax1_type][$localtax1_rate] += $localtax1ligne;
					}
					if ($localtax2_type && $localtax2ligne != 0) {
						$this->localtax2[$localtax2_type][$localtax2_rate] += $localtax2ligne;
					}

					if (($object->lines[$i]->info_bits & 0x01) == 0x01) {
						$vatrate .= '*';
					}

					// Fill $this->tva and $this->tva_array
					if (!isset($this->tva[$vatrate])) {
						$this->tva[$vatrate] = 0;
					}
					$this->tva[$vatrate] += $tvaligne;
					$vatcode = $object->lines[$i]->vat_src_code;
					if (empty($this->tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')]['amount'])) {
						$this->tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')]['amount'] = 0;
					}
					$this->tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')] = array('vatrate'=>$vatrate, 'vatcode'=>$vatcode, 'amount'=> $this->tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')]['amount'] + $tvaligne);

					if ($posYAfterImage > $posYAfterDescription) {
						$nexY = $posYAfterImage;
					}

					// Add line
					if (!empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblines - 1)) {
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash'=>'0','width' => 0.2, 'color'=>array(45, 44, 49)));
						//$pdf->SetDrawColor(252, 199, 26);
						$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1);
						$pdf->SetLineStyle(array('dash'=>0));
					}

					$nexY += 2; // Add space between lines
					
					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter) {
						$pdf->setPage($pagenb);
						if ($pagenb == 1) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
					}
					if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
						if ($pagenb == 1) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						// New page
						$pdf->AddPage();
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
						$pagenb++;
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
					}
				}
				
				// Show square
				if ($pagenb == 1) {
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforsignature - $heightforfooter, 0, $outputlangs, 0, 0, $object->multicurrency_code);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforsignature - $heightforfooter + 1;
				} else {
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforsignature - $heightforfooter, 0, $outputlangs, 1, 0, $object->multicurrency_code);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforsignature - $heightforfooter + 1;
				}
				
				// Affiche zone infos
				$posy = $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

				// Affiche zone totaux
				$posy = $this->_tableau_tot($pdf, $object, 0, $bottomlasttab, $outputlangs);

				// Affiche zone versements
				/*
				if ($deja_regle || $amount_credit_notes_included || $amount_deposits_included)
				{
					$posy=$this->_tableau_versements($pdf, $object, $posy, $outputlangs);
				}
				*/

				// Customer signature area
				if (empty($conf->global->PROPAL_DISABLE_SIGNATURE)) {
					$posy = $this->_signature_area($pdf, $object, $posy, $outputlangs);
				}
				
				// Pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
				}
				$this->_agbs($pdf);
				//If propal merge product PDF is active
				if (!empty($conf->global->PRODUIT_PDF_MERGE_PROPAL)) {
					require_once DOL_DOCUMENT_ROOT.'/product/class/propalmergepdfproduct.class.php';

					$already_merged = array();
					foreach ($object->lines as $line) {
						if (!empty($line->fk_product) && !(in_array($line->fk_product, $already_merged))) {
							// Find the desire PDF
							$filetomerge = new Propalmergepdfproduct($this->db);

							if ($conf->global->MAIN_MULTILANGS) {
								$filetomerge->fetch_by_product($line->fk_product, $outputlangs->defaultlang);
							} else {
								$filetomerge->fetch_by_product($line->fk_product);
							}

							$already_merged[] = $line->fk_product;

							$product = new Product($this->db);
							$product->fetch($line->fk_product);

							if ($product->entity != $conf->entity) {
								$entity_product_file = $product->entity;
							} else {
								$entity_product_file = $conf->entity;
							}

							// If PDF is selected and file is not empty
							if (count($filetomerge->lines) > 0) {
								foreach ($filetomerge->lines as $linefile) {
									if (!empty($linefile->id) && !empty($linefile->file_name)) {
										if (!empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) {
											if (!empty($conf->product->enabled)) {
												$filetomerge_dir = $conf->product->multidir_output[$entity_product_file].'/'.get_exdir($product->id, 2, 0, 0, $product, 'product').$product->id."/photos";
											} elseif (!empty($conf->service->enabled)) {
												$filetomerge_dir = $conf->service->multidir_output[$entity_product_file].'/'.get_exdir($product->id, 2, 0, 0, $product, 'product').$product->id."/photos";
											}
										} else {
											if (!empty($conf->product->enabled)) {
												$filetomerge_dir = $conf->product->multidir_output[$entity_product_file].'/'.get_exdir(0, 0, 0, 0, $product, 'product');
											} elseif (!empty($conf->service->enabled)) {
												$filetomerge_dir = $conf->service->multidir_output[$entity_product_file].'/'.get_exdir(0, 0, 0, 0, $product, 'product');
											}
										}

										dol_syslog(get_class($this).':: upload_dir='.$filetomerge_dir, LOG_DEBUG);

										$infile = $filetomerge_dir.'/'.$linefile->file_name;
										if (file_exists($infile) && is_readable($infile)) {
											$pagecount = $pdf->setSourceFile($infile);
											for ($i = 1; $i <= $pagecount; $i++) {
												$tplIdx = $pdf->importPage($i);
												if ($tplIdx !== false) {
													$s = $pdf->getTemplatesize($tplIdx);
													$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
													$pdf->useTemplate($tplIdx);
												} else {
													setEventMessages(null, array($infile.' cannot be added, probably protected PDF'), 'warnings');
												}
											}
										}
									}
								}
							}
						}
					}
				}
				
				$pdf->Close();

				$pdf->Output($file, 'F');

				//Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$this->error = $hookmanager->error;
					$this->errors = $hookmanager->errors;
				}

				if (!empty($conf->global->MAIN_UMASK)) {
					@chmod($file, octdec($conf->global->MAIN_UMASK));
				}

				$this->result = array('fullpath'=>$file);

				return 1; // No error
			} else {
				$this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $langs->trans("ErrorConstantNotDefined", "PROP_OUTPUTDIR");
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Show payments table
	 *
	 *  @param	TCPDF		$pdf            Object PDF
	 *  @param  Propal		$object         Object proposal
	 *  @param  int			$posy           Position y in PDF
	 *  @param  Translate	$outputlangs    Object langs for output
	 *  @return int             			<0 if KO, >0 if OK
	 */
	protected function _tableau_versements(&$pdf, $object, $posy, $outputlangs)
	{
		// phpcs:enable
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *   Show miscellaneous information (payment mode, payment term, ...)
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		Propal		$object			Object to show
	 *   @param		int			$posy			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	void
	 */
	protected function _tableau_info(&$pdf, $object, $posy, $outputlangs)
	{
		// phpcs:enable
		global $conf, $mysoc;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('', '', $default_font_size - 1);

		// If France, show VAT mention if not applicable
		if ($this->emetteur->country_code == 'FR' && empty($mysoc->tva_assuj)) {
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

			$posy = $pdf->GetY() + 4;
		}

		$posxval = 52;
		if (!empty($conf->global->MAIN_PDF_DELIVERY_DATE_TEXT)) {
			$displaydate = "daytext";
		} else {
			$displaydate = "day";
		}

		// Show shipping date
		if (!empty($object->delivery_date)) {
			$outputlangs->load("sendings");
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = $outputlangs->transnoentities("DateDeliveryPlanned").':';
			$pdf->MultiCell(80, 4, $titre, 0, 'L');
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posxval, $posy);
			$dlp = dol_print_date($object->delivery_date, $displaydate, false, $outputlangs, true);
			$pdf->MultiCell(80, 4, $dlp, 0, 'L');

			$posy = $pdf->GetY() + 1;
		} elseif ($object->availability_code || $object->availability) {    // Show availability conditions
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = $outputlangs->transnoentities("AvailabilityPeriod").':';
			$pdf->MultiCell(80, 4, $titre, 0, 'L');
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posxval, $posy);
			$lib_availability = $outputlangs->transnoentities("AvailabilityType".$object->availability_code) != ('AvailabilityType'.$object->availability_code) ? $outputlangs->transnoentities("AvailabilityType".$object->availability_code) : $outputlangs->convToOutputCharset($object->availability);
			$lib_availability = str_replace('\n', "\n", $lib_availability);
			$pdf->MultiCell(80, 4, $lib_availability, 0, 'L');

			$posy = $pdf->GetY() + 1;
		}

		// Show payments conditions
		if (empty($conf->global->PROPOSAL_PDF_HIDE_PAYMENTTERM) && ($object->cond_reglement_code || $object->cond_reglement)) {
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = $outputlangs->transnoentities("PaymentConditions").':';
			$pdf->MultiCell(43, 4, $titre, 0, 'L');

			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posxval, $posy);
			$lib_condition_paiement = $outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code) != ('PaymentCondition'.$object->cond_reglement_code) ? $outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code) : $outputlangs->convToOutputCharset($object->cond_reglement_doc ? $object->cond_reglement_doc : $object->cond_reglement_label);
			$lib_condition_paiement = str_replace('\n', "\n", $lib_condition_paiement);
			if ($object->deposit_percent > 0) {
				$lib_condition_paiement = str_replace('__DEPOSIT_PERCENT__', $object->deposit_percent, $lib_condition_paiement);
			}
			$pdf->MultiCell(67, 4, $lib_condition_paiement, 0, 'L');

			$posy = $pdf->GetY() + 3;
		}

		if (empty($conf->global->PROPOSAL_PDF_HIDE_PAYMENTMODE)) {
			// Check a payment mode is defined
			/* Not required on a proposal
			if (empty($object->mode_reglement_code)
			&& ! $conf->global->FACTURE_CHQ_NUMBER
			&& ! $conf->global->FACTURE_RIB_NUMBER)
			{
				$pdf->SetXY($this->marge_gauche, $posy);
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B', $default_font_size - 2);
				$pdf->MultiCell(90, 3, $outputlangs->transnoentities("ErrorNoPaiementModeConfigured"),0,'L',0);
				$pdf->SetTextColor(0,0,0);

				$posy=$pdf->GetY()+1;
			}
			*/

			// Show payment mode
			if ($object->mode_reglement_code
			&& $object->mode_reglement_code != 'CHQ'
			&& $object->mode_reglement_code != 'VIR') {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche, $posy);
				$titre = $outputlangs->transnoentities("PaymentMode").':';
				$pdf->MultiCell(80, 5, $titre, 0, 'L');
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posxval, $posy);
				$lib_mode_reg = $outputlangs->transnoentities("PaymentType".$object->mode_reglement_code) != ('PaymentType'.$object->mode_reglement_code) ? $outputlangs->transnoentities("PaymentType".$object->mode_reglement_code) : $outputlangs->convToOutputCharset($object->mode_reglement);
				$pdf->MultiCell(80, 5, $lib_mode_reg, 0, 'L');

				$posy = $pdf->GetY() + 2;
			}

			// Show payment mode CHQ
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'CHQ') {
				// Si mode reglement non force ou si force a CHQ
				if (!empty($conf->global->FACTURE_CHQ_NUMBER)) {
					$diffsizetitle = (empty($conf->global->PDF_DIFFSIZE_TITLE) ? 3 : $conf->global->PDF_DIFFSIZE_TITLE);

					if ($conf->global->FACTURE_CHQ_NUMBER > 0) {
						$account = new Account($this->db);
						$account->fetch($conf->global->FACTURE_CHQ_NUMBER);

						$pdf->SetXY($this->marge_gauche, $posy);
						$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
						$pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo', $account->proprio), 0, 'L', 0);
						$posy = $pdf->GetY() + 1;

						if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS)) {
							$pdf->SetXY($this->marge_gauche, $posy);
							$pdf->SetFont('', '', $default_font_size - $diffsizetitle);
							$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($account->owner_address), 0, 'L', 0);
							$posy = $pdf->GetY() + 2;
						}
					}
					if ($conf->global->FACTURE_CHQ_NUMBER == -1) {
						$pdf->SetXY($this->marge_gauche, $posy);
						$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
						$pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo', $this->emetteur->name), 0, 'L', 0);
						$posy = $pdf->GetY() + 1;

						if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS)) {
							$pdf->SetXY($this->marge_gauche, $posy);
							$pdf->SetFont('', '', $default_font_size - $diffsizetitle);
							$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($this->emetteur->getFullAddress()), 0, 'L', 0);
							$posy = $pdf->GetY() + 2;
						}
					}
				}
			}

			// If payment mode not forced or forced to VIR, show payment with BAN
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'VIR') {
				if (!empty($object->fk_account) || !empty($object->fk_bank) || !empty($conf->global->FACTURE_RIB_NUMBER)) {
					$bankid = (empty($object->fk_account) ? $conf->global->FACTURE_RIB_NUMBER : $object->fk_account);
					if (!empty($object->fk_bank)) {
						$bankid = $object->fk_bank; // For backward compatibility when object->fk_account is forced with object->fk_bank
					}
					$account = new Account($this->db);
					$account->fetch($bankid);

					$curx = $this->marge_gauche;
					$cury = $posy;

					$posy = pdf_bank($pdf, $outputlangs, $curx, $cury, $account, 0, $default_font_size);

					$posy += 2;
				}
			}
		}

		return $posy;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Show total to pay
	 *
	 *	@param	TCPDF		$pdf            Object PDF
	 *	@param  Propal		$object         Object propal
	 *	@param  int			$deja_regle     Amount already paid
	 *	@param	int			$posy			Start position
	 *	@param	Translate	$outputlangs	Objet langs
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *	@return int							Position for continuation
	 */
	protected function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs, $outputlangsbis = null)
	{
		// phpcs:enable
		global $conf, $mysoc;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$tab2_top = $posy;
		$tab2_hl = 4;
		$pdf->SetFont('', '', $default_font_size - 1);

		// Total table
		$col1x = 120;
		$col2x = 170;
		if ($this->page_largeur < 210) { // To work with US executive format
			$col2x -= 20;
		}
		$largcol2 = ($this->page_largeur - $this->marge_droite - $col2x);

		$useborder = 0;
		$index = 0;

		// Total HT
		$pdf->SetFillColor(255, 255, 255);
		$pdf->SetXY($col1x, $tab2_top + 0);
		$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);

		$total_ht = ((!empty($conf->multicurrency->enabled) && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ht : $object->total_ht);
		$pdf->SetXY($col2x, $tab2_top + 0);
		$pdf->MultiCell($largcol2, $tab2_hl, price($total_ht + (!empty($object->remise) ? $object->remise : 0), 0, $outputlangs)  . " €", 0, 'R', 1);

		// Show VAT by rates and total
		$pdf->SetFillColor(248, 248, 248);

		$total_ttc = (!empty($conf->multicurrency->enabled) && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ttc : $object->total_ttc;
		
		$style = array('width' => 0.4, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252, 199, 29));
		$pdf->line(10, $tab2_top - 1, 200, $tab2_top - 1, $style);

		$this->atleastoneratenotnull = 0;
		
		if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT)) {
			$tvaisnull = ((!empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000'])) ? true : false);
			if (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_IFNULL) && $tvaisnull) {
				// Nothing to do
			} else {
				//Local tax 1 before VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on')
				//{
				foreach ($this->localtax1 as $localtax_type => $localtax_rate) {
					if (in_array((string) $localtax_type, array('1', '3', '5'))) {
						continue;
					}

					foreach ($localtax_rate as $tvakey => $tvaval) {
						if ($tvakey != 0) {    // On affiche pas taux 0
							//$this->atleastoneratenotnull++;

							$index++;
							$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

							$tvacompl = '';
							if (preg_match('/\*/', $tvakey)) {
								$tvakey = str_replace('*', '', $tvakey);
								$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
							}
							$totalvat = $outputlangs->transcountrynoentities("TotalLT1", $mysoc->country_code).' ';
							$totalvat .= vatrate(abs($tvakey), 1).$tvacompl;
							$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

							$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
							$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs)  . " €", 0, 'R', 1);
						}
					}
				}
				//}
				//Local tax 2 before VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on')
				//{
				foreach ($this->localtax2 as $localtax_type => $localtax_rate) {
					if (in_array((string) $localtax_type, array('1', '3', '5'))) {
						continue;
					}

					foreach ($localtax_rate as $tvakey => $tvaval) {
						if ($tvakey != 0) {    // On affiche pas taux 0
							//$this->atleastoneratenotnull++;



							$index++;
							$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

							$tvacompl = '';
							if (preg_match('/\*/', $tvakey)) {
								$tvakey = str_replace('*', '', $tvakey);
								$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
							}
							$totalvat = $outputlangs->transcountrynoentities("TotalLT2", $mysoc->country_code).' ';
							$totalvat .= vatrate(abs($tvakey), 1).$tvacompl;
							$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

							$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
							$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs)  . " €", 0, 'R', 1);
						}
					}
				}
				//}

				// VAT
				foreach ($this->tva_array as $tvakey => $tvaval) {
					if ($tvakey != 0) {    // On affiche pas taux 0
						$this->atleastoneratenotnull++;

						$index++;
						$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

						$tvacompl = '';
						if (preg_match('/\*/', $tvakey)) {
							$tvakey = str_replace('*', '', $tvakey);
							$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
						}
						$totalvat = $outputlangs->transcountrynoentities("TotalVAT", $mysoc->country_code).(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transcountrynoentities("TotalVAT", $mysoc->country_code) : '');
						$totalvat .= ' ';
						if (getDolGlobalString('PDF_VAT_LABEL_IS_CODE_OR_RATE') == 'rateonly') {
							$totalvat .= vatrate($tvaval['vatrate'], 1).$tvacompl;
						} elseif (getDolGlobalString('PDF_VAT_LABEL_IS_CODE_OR_RATE') == 'codeonly') {
							$totalvat .= ($tvaval['vatcode'] ? $tvaval['vatcode'] : vatrate($tvaval['vatrate'], 1)).$tvacompl;
						} else {
							$totalvat .= vatrate($tvaval['vatrate'], 1).($tvaval['vatcode'] ? ' ('.$tvaval['vatcode'].')' : '').$tvacompl;
						}
						$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

						$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
						$pdf->MultiCell($largcol2, $tab2_hl, price(price2num($tvaval['amount'], 'MT'), 0, $outputlangs)  . " €", 0, 'R', 1);
					}
				}

				//Local tax 1 after VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on')
				//{
				foreach ($this->localtax1 as $localtax_type => $localtax_rate) {
					if (in_array((string) $localtax_type, array('2', '4', '6'))) {
						continue;
					}

					foreach ($localtax_rate as $tvakey => $tvaval) {
						if ($tvakey != 0) {    // On affiche pas taux 0
							//$this->atleastoneratenotnull++;

							$index++;
							$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

							$tvacompl = '';
							if (preg_match('/\*/', $tvakey)) {
								$tvakey = str_replace('*', '', $tvakey);
								$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
							}
							$totalvat = $outputlangs->transcountrynoentities("TotalLT1", $mysoc->country_code).' ';

							$totalvat .= vatrate(abs($tvakey), 1).$tvacompl;
							$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);
							$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
							$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs) . " €", 0, 'R', 1);
						}
					}
				}
				//}
				//Local tax 2 after VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on')
				//{
				foreach ($this->localtax2 as $localtax_type => $localtax_rate) {
					if (in_array((string) $localtax_type, array('2', '4', '6'))) {
						continue;
					}

					foreach ($localtax_rate as $tvakey => $tvaval) {
						// retrieve global local tax
						if ($tvakey != 0) {    // On affiche pas taux 0
							//$this->atleastoneratenotnull++;

							$index++;
							$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

							$tvacompl = '';
							if (preg_match('/\*/', $tvakey)) {
								$tvakey = str_replace('*', '', $tvakey);
								$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
							}
							$totalvat = $outputlangs->transcountrynoentities("TotalLT2", $mysoc->country_code).' ';

							$totalvat .= vatrate(abs($tvakey), 1).$tvacompl;
							$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

							$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
							$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
						}
					}
				}
				//}

				// Total TTC
				$index++;
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->SetFillColor(224, 224, 224);
				$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($total_ttc, 0, $outputlangs)  . " €", $useborder, 'R', 1);
			}
		}

		$pdf->SetTextColor(0, 0, 0);

		/*
		$resteapayer = $object->total_ttc - $deja_regle;
		if (! empty($object->paye)) $resteapayer=0;
		*/

		if ($deja_regle > 0) {
			$index++;

			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("AlreadyPaid"), 0, 'L', 0);
			$pdf->SetFont('', 'B', $default_font_size + 1);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle, 0, $outputlangs)  . " €", 0, 'R', 0);

			/*
			if ($object->close_code == 'discount_vat')
			{
				$index++;
				$pdf->SetFillColor(255,255,255);

				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("EscompteOfferedShort"), $useborder, 'L', 1);

				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc - $deja_regle, 0, $outputlangs), $useborder, 'R', 1);

				$resteapayer=0;
			}
			*/

			$index++;
			$pdf->SetTextColor(0, 0, 60);
			$pdf->SetFillColor(224, 224, 224);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);

			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer, 0, $outputlangs), $useborder, 'R', 1);

			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColor(0, 0, 0);
		}

		$index++;
		return ($tab2_top + ($tab2_hl * $index));
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Show table for lines
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @param		string		$currency		Currency code
	 *   @return	void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '')
	{
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom = 0;
		if ($hidetop) {
			$hidetop = -1;
		}

		$currency = !empty($currency) ? $currency : $conf->currency;
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$style_tabel = array('width' => 0.2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(45, 44, 49));
		// Amount in (at tab_top - 1)
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 2);

		if (empty($hidetop)) {
			$titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
			$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top - 4);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

			$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR= '252, 199, 26';
			if (!empty($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR)) {
				$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite - $this->marge_gauche, 5, 'F', null, explode(',', $conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR));
			}
		}

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', '', $default_font_size - 1);

		// Output Rect
		//$this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $hidetop, $hidebottom); // Rect takes a length in 3rd parameter and 4th parameter

		if (empty($hidetop)) {
			$pdf->line($this->marge_gauche, $tab_top + 5, $this->page_largeur - $this->marge_droite, $tab_top + 5 , $style_tabel); // line takes a position y in 2nd parameter and 4th parameter
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$pdf->SetXY($this->posxdesc - 1, $tab_top + 1);
			$pdf->MultiCell(108, 2, $outputlangs->transnoentities("Designation"), '', 'L');
		}

		if (!empty($conf->global->MAIN_GENERATE_PROPOSALS_WITH_PICTURE)) {
			$pdf->line($this->posxpicture - 1, $tab_top, $this->posxpicture - 1, $tab_top + $tab_height, $style_tabel);
			if (empty($hidetop)) {
				//$pdf->SetXY($this->posxpicture-1, $tab_top+1);
				//$pdf->MultiCell($this->posxtva-$this->posxpicture-1,2, $outputlangs->transnoentities("Photo"),'','C');
			}
		}

		if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) && empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN)) {
			$pdf->line($this->posxtva - 1, $tab_top, $this->posxtva - 1, $tab_top + $tab_height, $style_tabel);
			if (empty($hidetop)) {
				// Not do -3 and +3 instead of -1 -1 to have more space for text 'Sales tax'
				$pdf->SetXY($this->posxtva - 3, $tab_top + 1);
				$pdf->MultiCell($this->posxup - $this->posxtva + 3, 2, $outputlangs->transnoentities("VAT"), '', 'C');
			}
		}

		$pdf->line($this->posxup - 1, $tab_top, $this->posxup - 1, $tab_top + $tab_height, $style_tabel);
		if (empty($hidetop)) {
			$pdf->SetXY($this->posxup - 1, $tab_top + 1);
			$pdf->MultiCell($this->posxqty - $this->posxup - 1, 2, $outputlangs->transnoentities("PriceUHT"), '', 'C');
		}

		$pdf->line($this->posxqty - 1, $tab_top, $this->posxqty - 1, $tab_top + $tab_height, $style_tabel);
		if (empty($hidetop)) {
			$pdf->SetXY($this->posxqty - 1, $tab_top + 1);
			$pdf->MultiCell($this->posxunit - $this->posxqty - 1, 2, $outputlangs->transnoentities("Qty"), '', 'C');
		}

		if (!empty($conf->global->PRODUCT_USE_UNITS)) {
			//$pdf->line($this->posxunit - 1, $tab_top, $this->posxunit - 1, $tab_top + $tab_height);
			if (empty($hidetop)) {
				$pdf->SetXY($this->posxunit - 1, $tab_top + 1);
				$pdf->MultiCell(
					$this->posxdiscount - $this->posxunit - 1, 2, $outputlangs->transnoentities("Unit"),'','C');
			}
		}

		$pdf->line($this->posxdiscount - 1, $tab_top, $this->posxdiscount - 1, $tab_top + $tab_height , $style_tabel);
		if (empty($hidetop)) {
			if ($this->atleastonediscount) {
				$pdf->SetXY($this->posxdiscount - 1, $tab_top + 1);
				$pdf->MultiCell($this->postotalht - $this->posxdiscount + 1, 2, $outputlangs->transnoentities("ReductionShort"), '', 'C');
			}
		}
		if ($this->atleastonediscount) {
			$pdf->line($this->postotalht, $tab_top, $this->postotalht, $tab_top + $tab_height, $style_tabel);
		}
		if (empty($hidetop)) {
			$pdf->SetXY($this->postotalht - 1, $tab_top + 1);
			$pdf->MultiCell(30, 2, $outputlangs->transnoentities("TotalTTC"), '', 'C');
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Propal		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *  @return	void
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $outputlangsbis = null)
	{
		global $conf, $langs;

		$ltrdirection = 'L';
		if ($outputlangs->trans("DIRECTION") == 'rtl') $ltrdirection = 'R';

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "propal", "companies", "bills"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);
		// Post knickpunkt
		$style = array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0));
		$pdf->line(5, 97, 10, 97, $style);

		// Header Gelb
		if (empty($conf->global->MAIN_PDF_NO_SENDER_FRAME)) {
			$pdf->SetXY(0, 0);
			$pdf->SetFillColor(252, 199, 26);
			$pdf->MultiCell(300, 23, "", 0, 'R', 1);
		}

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);


		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		if (empty($conf->global->PDF_DISABLE_MYCOMPANY_LOGO)) {
			if ($this->emetteur->logo) {
				$logodir = $conf->mycompany->dir_output;
				if (!empty($conf->mycompany->multidir_output[$object->entity])) {
					$logodir = $conf->mycompany->multidir_output[$object->entity];
				}
				if (empty($conf->global->MAIN_PDF_USE_LARGE_LOGO)) {
					$logo = $logodir . '/logos/thumbs/' . $this->emetteur->logo_small;
				} else {
					$logo = $logodir . '/logos/' . $this->emetteur->logo;
				}
				if (is_readable($logo)) {
					$height = pdf_getHeightForLogo($logo);
					$pdf->SetXY(10, 5);
					$pdf->Image($logo, $this->marge_gauche, $posy, 0, 15); // width=0 (auto)
				} else {
					$pdf->SetTextColor(200, 0, 0);
					$pdf->SetFont('', 'B', $default_font_size - 2);
					$pdf->Cell(50, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
					$pdf->Cell(50, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
				}
			} else {
				$text = $this->emetteur->name;
				$pdf->Cell(50, 4, $outputlangs->convToOutputCharset($text), 0, $ltrdirection);
			}
		}


		/*
		$posy += 5;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref), '', 'R');
		*/  
		

		// Get contact
		if (!empty($conf->global->DOC_SHOW_FIRST_SALES_REP)) {
			$arrayidcontact = $object->getIdContact('internal', 'SALESREPFOLL');
			if (count($arrayidcontact) > 0) {
				$usertmp = new User($this->db);
				$usertmp->fetch($arrayidcontact[0]);
				$posy += 4;
				$pdf->SetXY($posx, $posy);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->MultiCell(100, 3, $langs->trans("SalesRepresentative") . " : " . $usertmp->getFullName($langs), '', 'R');
			}
		}

		$posy += 2;

		$top_shift = 0;
		// Show list of linked objects
		$current_y = $pdf->getY();
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size);
		if ($current_y < $pdf->getY()) {
			$top_shift = $pdf->getY() - $current_y;
		}

		if ($showaddress) {
			// Sender properties
			$carac_emetteur = '';
			// Add internal contact of proposal if defined
			$arrayidcontact = $object->getIdContact('internal', 'SALESREPFOLL');
			if (count($arrayidcontact) > 0) {
				$object->fetch_user($arrayidcontact[0]);
				$labelbeforecontactname = ($outputlangs->transnoentities("FromContactName") != 'FromContactName' ? $outputlangs->transnoentities("FromContactName") : $outputlangs->transnoentities("Name"));
				$carac_emetteur .= ($carac_emetteur ? "\n" : '') . $labelbeforecontactname . " " . $outputlangs->convToOutputCharset($object->user->getFullName($outputlangs)) . "\n";
			}

			$carac_emetteur .= pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

			// Show sender
			$posy = 25 + $top_shift;
			$posx = $this->marge_gauche;
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) {
				$posx = $this->page_largeur - $this->marge_droite - 50;
			}
			$hautcadre = 40;
/*
			// Show sender frame
			if (empty($conf->global->MAIN_PDF_NO_SENDER_FRAME)) {
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posx, $posy - 5);
				$pdf->MultiCell(10, 5, $outputlangs->transnoentities("BillFrom"), 0, $ltrdirection);
				//$pdf->SetXY($posx, $posy);
				$pdf->SetXY(140, 40);
				$pdf->SetFillColor(255, 200, 200);
				$pdf->MultiCell(60, $hautcadre, "", 0, 'R', 1);
				$pdf->SetTextColor(0, 0, 60);
			}
*/
			// Show sender name
			if (empty($conf->global->MAIN_PDF_HIDE_SENDER_NAME)) {
				$pdf->SetXY($posx + 2, $posy + 3);
				$pdf->SetFont('', 'B', $default_font_size + 1);
				$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, $ltrdirection);
				$posy = $pdf->getY();
			}

			// Show sender information
			$pdf->SetXY($posx + 2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(80, 4, $carac_emetteur, 0, $ltrdirection);
			
			//info
			if (!empty($conf->global->MAIN_PDF_DATE_TEXT)) {
				$displaydate = "daytext";
			} else {
				$displaydate = "day";
			}
			$style_db = array('width' => 0.7, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252, 199, 26));
			$pdf->line(10, 107, 200, 107, $style_db);

			$pdf->SetXY($posx + 2, $posy + 26);
			$pdf->SetFont('', 'B', $default_font_size + 1);
			$pdf->MultiCell(80, 4, "Info:", 0, $ltrdirection);

			//Angebotsdatum
			$pdf->SetXY($posx + 2, $posy + 30);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(80, 4, $outputlangs->transnoentities("DatePropal") . " : " . dol_print_date($object->date, $displaydate, false, $outputlangs, true), 0, $ltrdirection);

			// Gültig bis
			$pdf->SetXY($posx + 2, $posy + 34);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("DateEndPropal") . " : " . dol_print_date($object->fin_validite, $displaydate, false, $outputlangs, true), 0, $ltrdirection);

			// Kundennummer
			if (empty($conf->global->MAIN_PDF_HIDE_CUSTOMER_CODE) && $object->thirdparty->code_client) {
				$pdf->SetXY($posx + 2, $posy + 38);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(80, 3, $outputlangs->transnoentities("CustomerCode") . " : " . $outputlangs->transnoentities($object->thirdparty->code_client), 0, $ltrdirection);
			}

			if (!empty($conf->global->PDF_SHOW_PROJECT_TITLE)) {
				$object->fetch_projet();
				if (!empty($object->project->ref)) {
					$pdf->SetXY($posx, $posy + 42);
					$pdf->SetTextColor(0, 0, 60);
					$pdf->MultiCell(80, 3, $outputlangs->transnoentities("Project") . " : " . (empty($object->project->title) ? '' : $object->project->title),0, $ltrdirection);
				}
			}

			if (!empty($conf->global->PDF_SHOW_PROJECT)) {
				$object->fetch_projet();
				if (!empty($object->project->ref)) {
					$outputlangs->load("projects");
					$pdf->SetXY($posx, $posy + 46);
					$pdf->SetTextColor(0, 0, 60);
					$pdf->MultiCell(80, 3, $outputlangs->transnoentities("RefProject") . " : " . (empty($object->project->ref) ? '' : $object->project->ref),0, $ltrdirection);
				}
			}

			// If CUSTOMER contact defined, we use it
			$usecontact = false;
			$arrayidcontact = $object->getIdContact('external', 'CUSTOMER');
			if (count($arrayidcontact) > 0) {
				$usecontact = true;
				$result = $object->fetch_contact($arrayidcontact[0]);
			}
			
			// Recipient name
			if ($usecontact && ($object->contact->socid != $object->thirdparty->id && (!isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) || !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)))) {
				$thirdparty = $object->contact;
			} else {
				$thirdparty = $object->thirdparty;
			}

			$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

			$mode =  'target';
			$carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), $usecontact, $mode, $object);

			// Show recipient
			$widthrecbox = 100;
			if ($this->page_largeur < 210) {
				$widthrecbox = 84; // To work with US executive format
			}
			$posy = 50 + $top_shift;
			$posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
			if (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) {
				$posx = $this->marge_gauche;
			}

			// Show recipient frame
			if (empty($conf->global->MAIN_PDF_NO_RECIPENT_FRAME)) {
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetFont('', '', $default_font_size - 3);
				$pdf->SetXY($posx + 3, $posy - 4);
				$pdf->MultiCell($widthrecbox, 5, $outputlangs->convToOutputCharset($this->emetteur->name) . " Am Baunsberg 1 / 34270 Schauenburg", 0, $ltrdirection);
				//$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);
			}

			// Show recipient name
			$pdf->SetXY($posx + 3 , $posy);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 4, $carac_client_name, 0, $ltrdirection);

			$posy = $pdf->getY();

			// Show recipient information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx + 3 , $posy);
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, $ltrdirection);


			// überschrift (angebot: xy)
			$pdf->SetFont('', 'B', $default_font_size + 3);
			$pdf->SetXY($posx, 100);
			$pdf->SetTextColor(0, 0, 60);
			$title = $outputlangs->transnoentities("PdfCommercialProposalTitle");
			$title .= ' ' . $outputlangs->convToOutputCharset($object->ref);
			if ($object->statut == $object::STATUS_DRAFT) {
				$pdf->SetTextColor(128, 0, 0);
				$title .= ' - ' . $outputlangs->transnoentities("NotValidated");
			}
			$pdf->MultiCell($widthrecbox, 4, $title, 0, $ltrdirection);

			$pdf->SetFont('', 'B', $default_font_size);
		}



		$pdf->SetTextColor(0, 0, 0);
		return $top_shift;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	TCPDF		$pdf     			PDF
	 * 		@param	Propal		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails = empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS) ? 0 : $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_pagefoot($pdf, $outputlangs, 'PROPOSAL_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext, $this->page_largeur, $this->watermark);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Show area for the customer to sign
	 *
	 *	@param	TCPDF		$pdf            Object PDF
	 *	@param  Propal		$object         Object invoice
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
	protected function _signature_area(&$pdf, $object, $posy, $outputlangs)
	{

		// phpcs:enable
		global $conf;
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$tab_top = $posy + 10;
		$tab_hl = 4;

		$posx = 10;
		$largcol = ($this->page_largeur - $this->marge_droite - $posx);
		$useborder = 0;
		$index = 0;
		// Total HT
		$pdf->SetFillColor(255, 255, 255);
		$pdf->SetXY($posx, $tab_top + $tab_hl + 10);
		$pdf->SetFont('', 'B', $default_font_size - 2);
		$pdf->MultiCell($largcol, $tab_hl - 3 , "Hirmit bestelle ich & bestätige das ich die AGB's gelesen habe und stimme diesen zu.", 0, 'L', 1);
		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->MultiCell($largcol, $tab_hl, $outputlangs->transnoentities("ProposalCustomerSignature") . ":", 0, 'L', 1);


		$pdf->SetXY($posx, $tab_top + $tab_hl + 20);
		$pdf->MultiCell($largcol, $tab_hl, '', 0, 'R');
		$style = array('width' => 0.8, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(252, 199, 29));
		$pdf->line(10, $tab_top + $tab_hl + 18, 200, $tab_top + $tab_hl + 18, $style);
		if (!empty($conf->global->MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING)) {
			$pdf->addEmptySignatureAppearance($posx, $tab_top + $tab_hl + 15, $largcol, $tab_hl * 3);
		}
		return ($tab_hl * 7);
	}
}
