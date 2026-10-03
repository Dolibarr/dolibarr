<?php
/* Copyright (C) 2026		Philippe Grand			<philippe.grand@atoo-net.com>
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
 *	\file       htdocs/core/modules/dons/pdf_cerfafr_16216.modules.php
 *	\ingroup    don
 *	\brief      File of class to generate the French tax receipt for donations of companies (form 2041-MEC-SD, Cerfa 16216*03)
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/dons/pdf_cerfafr_11580.modules.php';


/**
 *	Class to generate the French tax receipt for donations of companies,
 *	article 238 bis of the French general tax code (form 2041-MEC-SD, Cerfa 16216*03).
 *	The layout is shared with the receipt for private individuals, only the content of the form differs.
 */
class pdf_cerfafr_16216 extends pdf_cerfafr_11580
{
	/**
	 * @var string	Number of the form for the tax administration
	 */
	protected $formNumber = '2041-MEC-SD';

	/**
	 * @var string	Cerfa number of the form, with its version
	 */
	protected $cerfaNumber = '16216*03';

	/**
	 * @var string	Title of the form
	 */
	protected $formTitle = "Reçu des dons et versements effectués par les entreprises au titre de l'article 238 bis du code général des impôts";

	/**
	 *  Constructor
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		global $langs;

		parent::__construct($db);

		$this->name = "cerfafr_16216";
		$this->description = $langs->trans('DonationsReceiptModel').' - fr_FR - Cerfa 16216*03 (2041-MEC-SD)';
	}


	/**
	 *  Return the categories of beneficiary organizations listed on the form, with their legal label.
	 *  Categories of group 'oig' are the sub-cases of "general interest work or organisation".
	 *  Codes are the same as on the form for private individuals when the category exists on both forms.
	 *
	 *  @return	array<string,array{label:string,group:string}>		Categories, by code
	 */
	public static function getOrganismTypes()
	{
		return array(
			'oig_association' => array('group' => 'oig', 'label' => "Association loi 1901"),
			'oig_rup' => array('group' => 'oig', 'label' => "Association ou fondation reconnue d'utilité publique par décret en date du %s publié au Journal officiel du %s ou association située dans le département de la Moselle, du Bas-Rhin ou du Haut-Rhin dont la mission a été reconnue d'utilité publique par arrêté en date du %s"),
			'oig_university_foundation' => array('group' => 'oig', 'label' => "Fondation universitaire ou fondation partenariale mentionnées respectivement aux articles L.719-12 et L.719-13 du code de l'éducation"),
			'oig_company_foundation' => array('group' => 'oig', 'label' => "Fondation d'entreprise"),
			'oig_museum' => array('group' => 'oig', 'label' => "Musée de France"),
			'oig_food_aid' => array('group' => 'oig', 'label' => "Organismes sans but lucratif fournissant gratuitement une aide alimentaire, des soins médicaux ou des produits de première nécessité à des personnes en difficulté ou favorisant leur logement"),
			'oig_other' => array('group' => 'oig', 'label' => "Autres (précisez) : %s"),
			'alsace_moselle_worship' => array('group' => '', 'label' => "Association cultuelle ou établissement public des cultes reconnus d'Alsace-Moselle"),
			'higher_education' => array('group' => '', 'label' => "Établissement d'enseignement supérieur ou d'enseignement artistique public ou privé, d'intérêt général, à but non lucratif"),
			'consular_school' => array('group' => '', 'label' => "Établissement d'enseignement supérieur consulaire mentionné à l'article L. 711-17 du code de commerce"),
			'approved_research' => array('group' => '', 'label' => "Société ou organisme public ou privé agréé par le ministre chargé du budget en vertu de l'article 4 de l'ordonnance n° 58-882 du 25 septembre 1958 relative à la fiscalité en matière de recherche scientifique et technique. Date de l'agrément : %s"),
			'performing_arts' => array('group' => '', 'label' => "Organisme public ou privé dont la gestion est désintéressée et qui a pour activité principale la présentation au public d'œuvres dramatiques, lyriques, musicales, chorégraphiques, cinématographiques, audiovisuelles et de cirque ou l'organisation d'expositions d'art contemporain"),
			'doctoral_thesis' => array('group' => '', 'label' => "Projet de thèse proposé au mécénat de doctorat par une école doctorale"),
			'world_expo' => array('group' => '', 'label' => "Société, dont l'État est l'actionnaire unique, qui a pour activité la représentation de la France aux expositions universelles"),
			'public_broadcaster' => array('group' => '', 'label' => "Société nationale de programme mentionnée à l'article 44 de la loi n° 86-1067 du 30 septembre 1986 relative à la liberté de communication et affectés au financement de programmes audiovisuels culturels"),
			'public_broadcaster_music' => array('group' => '', 'label' => "Société nationale de programme mentionnée au III de l'article 44 de la loi n° 86-1067 du 30 septembre 1986 relative à la liberté de communication et affectés au financement des activités des formations musicales dont elle assure la gestion et le développement"),
			'heritage_foundation' => array('group' => '', 'label' => "Fondation du patrimoine ou fondation ou association reconnue d'utilité publique qui subventionnent des travaux sur des monuments historiques dans le cadre des conventions prévues à l'article L.143-2-1 et L. 143-15 du code du patrimoine. Le cas échéant, date de l'agrément : %s"),
			'endowment_fund' => array('group' => '', 'label' => "Fonds de dotation"),
			'sme_support' => array('group' => '', 'label' => "Organisme agréé ayant pour objet exclusif d'accorder des aides financières ou de fournir des prestations d'accompagnement à des petites et moyennes entreprises (4 de l'article 238 bis du CGI). Date de l'agrément : %s"),
			'sme_support_federation' => array('group' => '', 'label' => "Fédération ou union d'organismes ayant pour objet exclusif de fédérer, d'organiser, de représenter et de promouvoir les organismes agréés en application du 4 de l'article 238 bis du code général des impôts. Date de l'agrément : %s"),
			'cultural_property' => array('group' => '', 'label' => "Organismes ayant pour objet la sauvegarde, contre les effets d'un conflit armé, des biens culturels mentionnés à l'article 1er de la Convention du 14 mai 1954 pour la protection des biens culturels en cas de conflit armé (5 de l'article 238 bis du CGI)"),
			'eu_organism' => array('group' => '', 'label' => "Organisme établi dans un État membre de l'Union européenne autre que la France (ou en Norvège, Islande ou Liechtenstein) poursuivant des objectifs et présentant des caractéristiques similaires aux organismes précités. Le cas échéant, date de l'agrément : %s"),
		);
	}


	/**
	 *  Return the warnings to show when the form does not fit the kind of donor
	 *
	 *  @param	array{company:string,lastname:string,firstname:string,name:string,address:string,zip:string,town:string,country_code:string,country:string,idprof1:string,thirdparty:?Societe}	$donor	Identity of the donor
	 *  @return	string[]
	 */
	protected function checkDonor($donor)
	{
		global $langs;

		$warnings = array();
		// The form 16216 is only for companies, private individuals must receive the form 11580
		if (!$this->isCompanyDonor($donor)) {
			$warnings[] = $langs->trans("DonationCerfa16216ForCompaniesOnly");
		}

		return $warnings;
	}


	/**
	 *  Show the block of the beneficiary organization
	 *
	 *  @param	TCPDF		$pdf			Object PDF
	 *  @param	float		$posy			Y position
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @return	float						Y position after the block
	 */
	protected function showBeneficiary(&$pdf, $posy, $outputlangs)
	{
		$posy = $this->sectionTitle($pdf, $posy, "Organisme bénéficiaire des dons et versements", $outputlangs);
		$posy = $this->showBeneficiaryIdentity($pdf, $posy, "Dénomination de l'organisme :", $outputlangs);

		$generalinterest = "Œuvre ou organisme d'intérêt général ayant un caractère philanthropique, éducatif, scientifique, social, humanitaire, sportif, familial, culturel ou concourant à l'égalité entre les femmes et les hommes, à la mise en valeur du patrimoine artistique, à la défense de l'environnement naturel ou à la diffusion de la culture, de la langue et des connaissances scientifiques françaises. Précisez si vous êtes :";

		return $this->showOrganismTypes($pdf, $posy, $generalinterest, "Cochez la case qui vous concerne :", $outputlangs);
	}


	/**
	 *  Show the block of the donor company
	 *
	 *  @param	TCPDF		$pdf			Object PDF
	 *  @param	array{company:string,lastname:string,firstname:string,name:string,address:string,zip:string,town:string,country_code:string,country:string,idprof1:string,thirdparty:?Societe}	$donor	Identity of the donor
	 *  @param	float		$posy			Y position
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @return	float						Y position after the block
	 */
	protected function showDonor(&$pdf, $donor, $posy, $outputlangs)
	{
		$width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
		$posx = $this->marge_gauche;

		// Keep the block on one page
		if ($posy + 40 > $this->page_hauteur - $this->heightforfooter) {
			$pdf->AddPage();
			$posy = $this->marge_haute;
		}

		$posy = $this->sectionTitle($pdf, $posy, "Entreprise donatrice", $outputlangs);

		$name = ($donor['company'] !== '' ? $donor['company'] : $donor['name']);
		$legalform = '';
		if (is_object($donor['thirdparty']) && !empty($donor['thirdparty']->forme_juridique_code)) {
			$legalform = dol_html_entity_decode(getFormeJuridiqueLabel((string) $donor['thirdparty']->forme_juridique_code), ENT_QUOTES | ENT_HTML5);
		}

		$posy = $this->showField($pdf, $posx, $posy, $width, "Dénomination de l'entreprise :", $name, $outputlangs);
		$posy = $this->showField($pdf, $posx, $posy, $width / 2, "Forme juridique :", $legalform, $outputlangs) - 5;
		$posy = $this->showField($pdf, $posx + $width / 2, $posy, $width / 2, "Numéro SIREN :", $donor['idprof1'], $outputlangs);
		$posy = $this->showField($pdf, $posx, $posy, $width, "Adresse :", str_replace(array("\r\n", "\n"), ', ', $donor['address']), $outputlangs);
		$posy = $this->showField($pdf, $posx, $posy, $width / 3, "Code postal :", $donor['zip'], $outputlangs) - 5;
		$posy = $this->showField($pdf, $posx + $width / 3, $posy, $width * 2 / 3, "Commune :", $donor['town'], $outputlangs);

		return $posy;
	}


	/**
	 *  Show the block of the donation: donations in kind, payments, total, date or period, date and signature.
	 *  The nature of the donations set up in the module decides whether the donation is printed as
	 *  a payment (nature "cash") or as a donation in kind, described by the public note of the donation.
	 *
	 *  @param	TCPDF		$pdf			Object PDF
	 *  @param	Don			$don			Donation object
	 *  @param	float		$posy			Y position
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @return	float						Y position after the block
	 */
	protected function showDonation(&$pdf, $don, $posy, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
		$posx = $this->marge_gauche;

		// Keep the block on one page
		if ($posy + 125 > $this->page_hauteur - $this->heightforfooter) {
			$pdf->AddPage();
			$posy = $this->marge_haute;
		}

		$posy = $this->sectionTitle($pdf, $posy, "Dons et versements effectués par l'entreprise", $outputlangs);

		$amount = (float) price2num($don->amount, 'MT');
		$inkind = (getDolGlobalString('DONATION_CERFA_NATURE', 'cash') != 'cash');

		// Donations in kind
		$text = "L'organisme bénéficiaire reconnaît avoir reçu, au titre de la réduction d'impôt prévue à l'article 238 bis du code général des impôts, des dons en nature pour une valeur en euros égale à (1) :";
		$posy = $this->showAmount($pdf, $posy, $text, "Indiquez la valeur totale des dons en nature en toutes lettres :", ($inkind ? $amount : 0), $outputlangs);

		$pdf->SetFont('', 'B', $default_font_size - 1);
		$pdf->SetTextColor(0, 0, 100);
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell($width, 4, $outputlangs->convToOutputCharset("Description exhaustive des biens et prestations reçus et acceptés (2) (nature et quantité) (3) et détail des salariés mis à disposition :"), 0, 'L');
		$pdf->SetTextColor(0, 0, 0);
		$posy = $pdf->GetY() + 0.5;
		$description = ($inkind ? dol_string_nohtmltag((string) $don->note_public, 0) : '');
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetXY($posx + 1, $posy + 1);
		$pdf->MultiCell($width - 2, 4, $outputlangs->convToOutputCharset($description), 0, 'L');
		$heightdescription = max(10, $pdf->GetY() - $posy + 1);
		$pdf->Rect($posx, $posy, $width, $heightdescription);
		$posy += $heightdescription + 2;

		// Payments
		$text = "L'organisme bénéficiaire reconnaît avoir reçu, au titre de la réduction d'impôt prévue à l'article 238 bis du code général des impôts, des versements pour une valeur totale égale à :";
		$posy = $this->showAmount($pdf, $posy, $text, "Indiquez le total des versements en toutes lettres :", ($inkind ? 0 : $amount), $outputlangs);

		$paymentmode = '';
		if (!$inkind && !empty($don->mode_reglement_code)) {
			if ($don->mode_reglement_code == 'LIQ') {
				$paymentmode = 'cash';
			} elseif ($don->mode_reglement_code == 'CHQ') {
				$paymentmode = 'cheque';
			} elseif (in_array($don->mode_reglement_code, array('VIR', 'PRE', 'CB'))) {
				$paymentmode = 'transfer';
			} else {
				$paymentmode = 'other';
			}
		}
		$posy = $this->showField($pdf, $posx, $posy, $width, "Forme des versements (4) :", '', $outputlangs) - 1;
		$pdf->SetFont('', '', $default_font_size - 1);
		$paymentmodes = array('cash' => "Remise d'espèces", 'cheque' => "Chèque", 'transfer' => "Virement, prélèvement ou carte bancaire", 'other' => "Autre");
		$posy = $this->checkboxRow($pdf, $posy, $paymentmodes, $paymentmode, $outputlangs) + 2;

		// Total
		$posy = $this->showAmount($pdf, $posy, "Montant total des dons et versements reçus par l'organisme :", "Indiquez le montant total des dons et versements en toutes lettres :", $amount, $outputlangs);

		// Date or period on the left of the box for the date and the signature
		$pdf->SetFont('', 'B', $default_font_size - 1);
		$pdf->SetTextColor(0, 0, 100);
		$pdf->SetXY($posx, $posy + 5);
		$pdf->MultiCell($width - 80, 4, $outputlangs->convToOutputCharset("Date ou période au cours de laquelle les dons et versements ont été effectués (5) :"), 0, 'L');
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetXY($posx, $pdf->GetY());
		$pdf->MultiCell($width - 80, 4, dol_print_date($don->date, 'day', false, $outputlangs, true), 0, 'L');
		$posyfield = $pdf->GetY() + 1;

		return max($posyfield, $this->showSignature($pdf, $posy, $outputlangs, 18));
	}


	/**
	 *  Show an amount of the form: the sentence of the form, the amount in a box and the amount in letters
	 *
	 *  @param	TCPDF		$pdf			Object PDF
	 *  @param	float		$posy			Y position
	 *  @param	string		$text			Sentence of the form before the amount
	 *  @param	string		$letterslabel	Label of the amount in letters
	 *  @param	float		$amount			Amount, in euros
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @return	float						Y position after the amount
	 */
	protected function showAmount(&$pdf, $posy, $text, $letterslabel, $amount, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
		$posx = $this->marge_gauche;

		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell($width, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		$posy = $pdf->GetY() + 1;

		$pdf->Rect($posx, $posy, 40, 7);
		$pdf->SetFont('', 'B', $default_font_size + 1);
		$pdf->SetXY($posx, $posy + 1);
		$pdf->MultiCell(38, 5, price($amount, 0, $outputlangs, 1, -1, 'MT'), 0, 'R');
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetXY($posx + 42, $posy + 1.5);
		$pdf->MultiCell(15, 4, 'euros', 0, 'L');

		// Amount in letters, on the right of the amount: the label, then the amount below it
		$amountinletters = ($amount > 0 ? dol_convertToWord($amount, $outputlangs, 'euros', true) : "néant");
		$pdf->SetFont('', 'B', $default_font_size - 1);
		$pdf->SetTextColor(0, 0, 100);
		$pdf->SetXY($posx + 56, $posy - 0.5);
		$pdf->MultiCell($width - 56, 4, $outputlangs->convToOutputCharset($letterslabel), 0, 'L');
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetXY($posx + 56, $pdf->GetY());
		$pdf->MultiCell($width - 56, 4, $outputlangs->convToOutputCharset($amountinletters ? $amountinletters : ''), 0, 'L');

		return max($posy + 8, $pdf->GetY()) + 1;
	}


	/**
	 *  Show the legal notes of the form
	 *
	 *  @param	TCPDF		$pdf			Object PDF
	 *  @param	float		$posy			Y position
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @return	float						Y position after the notes
	 */
	protected function showFootnotes(&$pdf, $posy, $outputlangs)
	{
		$notes = "(1) L'organisme bénéficiaire des dons en nature reporte sur le reçu fiscal le montant indiqué par l'entreprise donatrice.\n";
		$notes .= "(2) L'entreprise ne peut pas prétendre au bénéfice de la réduction d'impôt à raison des dons en nature refusés par l'organisme.\n";
		$notes .= "(3) La description peut être établie par l'organisme bénéficiaire sur papier libre signé, daté et joint à la présente attestation.\n";
		$notes .= "(4) L'organisme bénéficiaire des versements peut cocher une ou plusieurs cases.\n";
		$notes .= "(5) L'organisme bénéficiaire peut établir un reçu unique pour plusieurs dons et versements effectués lors d'une période déterminée (à titre d'exemple, un mois, un trimestre, l'année civile ou encore l'exercice fiscal de l'entreprise donatrice). ";
		$notes .= "L'organisme bénéficiaire devra cependant s'assurer que la période sur laquelle porte le reçu fiscal n'est pas à cheval sur deux exercices fiscaux différents de l'entreprise donatrice, notamment dans le cas où l'exercice fiscal de l'entreprise donatrice ne coïncide pas avec l'année civile.";

		return $this->showNotes($pdf, $posy, $notes, $outputlangs);
	}
}
