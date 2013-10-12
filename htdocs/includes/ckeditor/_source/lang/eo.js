﻿/*
Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Esperanto language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang['eo'] =
{
	/**
	 * The language reading direction. Possible values are "rtl" for
	 * Right-To-Left languages (like Arabic) and "ltr" for Left-To-Right
	 * languages (like English).
	 * @default 'ltr'
	 */
	dir : 'ltr',

	/*
	 * Screenreader titles. Please note that screenreaders are not always capable
	 * of reading non-English words. So be careful while translating it.
	 */
	editorTitle : 'riĉteksta redaktilo, %1',
	editorHelp : 'Premu ALT 0 por helpilo',

	// ARIA descriptions.
	toolbars	: 'Ilobretoj de la redaktilo',
	editor		: 'Redaktilo por Riĉiga Teksto',

	// Toolbar buttons without dialogs.
	source			: 'Fonto',
	newPage			: 'Nova Paĝo',
	save			: 'Konservi',
	preview			: 'Vidigi Aspekton',
	cut				: 'Eltondi',
	copy			: 'Kopii',
	paste			: 'Interglui',
	print			: 'Presi',
	underline		: 'Substreko',
	bold			: 'Grasa',
	italic			: 'Kursiva',
	selectAll		: 'Elekti ĉion',
	removeFormat	: 'Forigi Formaton',
	strike			: 'Trastreko',
	subscript		: 'Suba indico',
	superscript		: 'Supra indico',
	horizontalrule	: 'Enmeti Horizontalan Linion',
	pagebreak		: 'Enmeti Paĝavancon por Presado',
	pagebreakAlt		: 'Paĝavanco',
	unlink			: 'Forigi Ligilon',
	undo			: 'Malfari',
	redo			: 'Refari',

	// Common messages and labels.
	common :
	{
		browseServer	: 'Foliumi en la Servilo',
		url				: 'URL',
		protocol		: 'Protokolo',
		upload			: 'Alŝuti',
		uploadSubmit	: 'Sendu al Servilo',
		image			: 'Bildo',
		flash			: 'Flaŝo',
		form			: 'Formularo',
		checkbox		: 'Markobutono',
		radio			: 'Radiobutono',
		textField		: 'Teksta kampo',
		textarea		: 'Teksta Areo',
		hiddenField		: 'Kaŝita Kampo',
		button			: 'Butono',
		select			: 'Elekta Kampo',
		imageButton		: 'Bildbutono',
		notSet			: '<Defaŭlta>',
		id				: 'Id',
		name			: 'Nomo',
		langDir			: 'Skribdirekto',
		langDirLtr		: 'De maldekstro dekstren (LTR)',
		langDirRtl		: 'De dekstro maldekstren (RTL)',
		langCode		: 'Lingva Kodo',
		longDescr		: 'URL de Longa Priskribo',
		cssClass		: 'Klasoj de Stilfolioj',
		advisoryTitle	: 'Priskriba Titolo',
		cssStyle		: 'Stilo',
		ok				: 'Akcepti',
		cancel			: 'Rezigni',
		close			: 'Fermi',
		preview			: 'Vidigi Aspekton',
		generalTab		: 'Ĝenerala',
		advancedTab		: 'Speciala',
		validateNumberFailed : 'Tiu valoro ne estas nombro.',
		confirmNewPage	: 'La neregistritaj ŝanĝoj estas perdotaj. Ĉu vi certas, ke vi volas ŝargi novan paĝon?',
		confirmCancel	: 'Iuj opcioj esta ŝanĝitaj. Ĉu vi certas, ke vi volas fermi la dialogon?',
		options			: 'Opcioj',
		target			: 'Celo',
		targetNew		: 'Nova Fenestro (_blank)',
		targetTop		: 'Supra Fenestro (_top)',
		targetSelf		: 'Sama Fenestro (_self)',
		targetParent	: 'Patra Fenestro (_parent)',
		langDirLTR		: 'De maldekstro dekstren (LTR)',
		langDirRTL		: 'De dekstro maldekstren (RTL)',
		styles			: 'Stilo',
		cssClasses		: 'Stilfoliaj Klasoj',
		width			: 'Larĝo',
		height			: 'Alto',
		align			: 'Ĝisrandigo',
		alignLeft		: 'Maldekstre',
		alignRight		: 'Dekstre',
		alignCenter		: 'Centre',
		alignTop		: 'Supre',
		alignMiddle		: 'Centre',
		alignBottom		: 'Malsupre',
		invalidHeight	: 'Alto devas esti nombro.',
		invalidWidth	: 'Larĝo devas esti nombro.',
		invalidCssLength	: 'La valoro indikita por la "%1" kampo devas esti pozitiva nombro kun aŭ sen valida CSSmezurunuo (px, %, in, cm, mm, em, ex, pt, or pc).',
		invalidHtmlLength	: 'La valoro indikita por la "%1" kampo devas esti pozitiva nombro kun aŭ sen valida HTMLmezurunuo (px or %).',
		invalidInlineStyle	: 'La valoro indikita por la enlinia stilo devas konsisti el unu aŭ pluraj elementoj kun la formato de "nomo : valoro", apartigitaj per punktokomoj.',
		cssLengthTooltip	: 'Entajpu nombron por rastrumera valoro aŭ nombron kun valida CSSunuo (px, %, in, cm, mm, em, ex, pt, or pc).',

		// Put the voice-only part of the label in the span.
		unavailable		: '%1<span class="cke_accessibility">, nehavebla</span>'
	},

	contextmenu :
	{
		options : 'Opcioj de Kunteksta Menuo'
	},

	// Special char dialog.
	specialChar		:
	{
		toolbar		: 'Enmeti Specialan Signon',
		title		: 'Selekti Specialan Signon',
		options : 'Opcioj pri Specialaj Signoj'
	},

	// Link dialog.
	link :
	{
		toolbar		: 'Enmeti/Ŝanĝi Ligilon',
		other 		: '<alia>',
		menu		: 'Ŝanĝi Ligilon',
		title		: 'Ligilo',
		info		: 'Informoj pri la Ligilo',
		target		: 'Celo',
		upload		: 'Alŝuti',
		advanced	: 'Speciala',
		type		: 'Tipo de Ligilo',
		toUrl		: 'URL',
		toAnchor	: 'Ankri en tiu ĉi paĝo',
		toEmail		: 'Retpoŝto',
		targetFrame		: '<kadro>',
		targetPopup		: '<ŝprucfenestro>',
		targetFrameName	: 'Nomo de CelKadro',
		targetPopupName	: 'Nomo de Ŝprucfenestro',
		popupFeatures	: 'Atributoj de la Ŝprucfenestro',
		popupResizable	: 'Dimensiŝanĝebla',
		popupStatusBar	: 'Statobreto',
		popupLocationBar: 'Adresobreto',
		popupToolbar	: 'Ilobreto',
		popupMenuBar	: 'Menubreto',
		popupFullScreen	: 'Tutekrane (IE)',
		popupScrollBars	: 'Rulumskaloj',
		popupDependent	: 'Dependa (Netscape)',
		popupLeft		: 'Maldekstra Pozicio',
		popupTop		: 'Supra Pozicio',
		id				: 'Id',
		langDir			: 'Skribdirekto',
		langDirLTR		: 'De maldekstro dekstren (LTR)',
		langDirRTL		: 'De dekstro maldekstren (RTL)',
		acccessKey		: 'Fulmoklavo',
		name			: 'Nomo',
		langCode			: 'Lingva Kodo',
		tabIndex			: 'Taba Indekso',
		advisoryTitle		: 'Priskriba Titolo',
		advisoryContentType	: 'Enhavotipo',
		cssClasses		: 'Klasoj de Stilfolioj',
		charset			: 'Signaro de la Ligita Rimedo',
		styles			: 'Stilo',
		rel			: 'Rilato',
		selectAnchor		: 'Elekti Ankron',
		anchorName		: 'Per Ankronomo',
		anchorId			: 'Per Elementidentigilo',
		emailAddress		: 'Retpoŝto',
		emailSubject		: 'Mesaĝa Temo',
		emailBody		: 'Mesaĝa korpo',
		noAnchors		: '<Ne disponeblas ankroj en la dokumento>',
		noUrl			: 'Bonvolu entajpi la URL-on',
		noEmail			: 'Bonvolu entajpi la retpoŝtadreson'
	},

	// Anchor dialog
	anchor :
	{
		toolbar		: 'Ankro',
		menu		: 'Enmeti/Ŝanĝi Ankron',
		title		: 'Ankraj Atributoj',
		name		: 'Ankra Nomo',
		errorName	: 'Bv entajpi la ankran nomon',
		remove		: 'Forigi Ankron'
	},

	// List style dialog
	list:
	{
		numberedTitle		: 'Atributoj de Numera Listo',
		bulletedTitle		: 'Atributoj de Bula Listo',
		type				: 'Tipo',
		start				: 'Komenco',
		validateStartNumber				:'La unua listero devas esti entjera nombro.',
		circle				: 'Cirklo',
		disc				: 'Disko',
		square				: 'kvadrato',
		none				: 'Neniu',
		notset				: '<Defaŭlta>',
		armenian			: 'Armena nombrado',
		georgian			: 'Gruza nombrado (an, ban, gan, ktp.)',
		lowerRoman			: 'Minusklaj Romanaj Nombroj (i, ii, iii, iv, v, ktp.)',
		upperRoman			: 'Majusklaj Romanaj Nombroj (I, II, III, IV, V, ktp.)',
		lowerAlpha			: 'Minusklaj Literoj (a, b, c, d, e, ktp.)',
		upperAlpha			: 'Majusklaj Literoj (A, B, C, D, E, ktp.)',
		lowerGreek			: 'Grekaj Minusklaj Literoj (alpha, beta, gamma, ktp.)',
		decimal				: 'Dekumaj Nombroj (1, 2, 3, ktp.)',
		decimalLeadingZero	: 'Dekumaj Nombroj malantaŭ nulo (01, 02, 03, ktp.)'
	},

	// Find And Replace Dialog
	findAndReplace :
	{
		title				: 'Serĉi kaj Anstataŭigi',
		find				: 'Serĉi',
		replace				: 'Anstataŭigi',
		findWhat			: 'Serĉi:',
		replaceWith			: 'Anstataŭigi per:',
		notFoundMsg			: 'La celteksto ne estas trovita.',
		findOptions			: 'Opcioj pri Serĉado',
		matchCase			: 'Kongruigi Usklecon',
		matchWord			: 'Tuta Vorto',
		matchCyclic			: 'Cikla Serĉado',
		replaceAll			: 'Anstataŭigi Ĉion',
		replaceSuccessMsg	: '%1 anstataŭigita(j) apero(j).'
	},

	// Table Dialog
	table :
	{
		toolbar		: 'Tabelo',
		title		: 'Atributoj de Tabelo',
		menu		: 'Atributoj de Tabelo',
		deleteTable	: 'Forigi Tabelon',
		rows		: 'Linioj',
		columns		: 'Kolumnoj',
		border		: 'Bordero',
		widthPx		: 'Rastrumeroj',
		widthPc		: 'elcentoj',
		widthUnit	: 'unuo de larĝo',
		cellSpace	: 'Spaco inter la Ĉeloj',
		cellPad		: 'Interna Marĝeno de la ĉeloj',
		caption		: 'Tabeltitolo',
		summary		: 'Resumo',
		headers		: 'Supraj Paĝotitoloj',
		headersNone		: 'Neniu',
		headersColumn	: 'Unua kolumno',
		headersRow		: 'Unua linio',
		headersBoth		: 'Ambaŭ',
		invalidRows		: 'La nombro de la linioj devas superi 0.',
		invalidCols		: 'La nombro de la kolumnoj devas superi 0.',
		invalidBorder	: 'La bordergrando devas esti nombro.',
		invalidWidth	: 'La tabellarĝo devas esti nombro.',
		invalidHeight	: 'La tabelalto devas esti nombro.',
		invalidCellSpacing	: 'La spaco inter la ĉeloj devas esti pozitiva nombro.',
		invalidCellPadding	: 'La interna marĝeno en la ĉeloj devas esti pozitiva nombro.',

		cell :
		{
			menu			: 'Ĉelo',
			insertBefore	: 'Enmeti Ĉelon Antaŭ',
			insertAfter		: 'Enmeti Ĉelon Post',
			deleteCell		: 'Forigi la Ĉelojn',
			merge			: 'Kunfandi la Ĉelojn',
			mergeRight		: 'Kunfandi dekstren',
			mergeDown		: 'Kunfandi malsupren ',
			splitHorizontal	: 'Horizontale dividi',
			splitVertical	: 'Vertikale dividi',
			title			: 'Ĉelatributoj',
			cellType		: 'Ĉeltipo',
			rowSpan			: 'Kunfando de linioj',
			colSpan			: 'Kunfando de kolumnoj',
			wordWrap		: 'Cezuro',
			hAlign			: 'Horizontala ĝisrandigo',
			vAlign			: 'Vertikala ĝisrandigo',
			alignBaseline	: 'Malsupro de la teksto',
			bgColor			: 'Fonkoloro',
			borderColor		: 'Borderkoloro',
			data			: 'Datenoj',
			header			: 'Supra paĝotitolo',
			yes				: 'Jes',
			no				: 'No',
			invalidWidth	: 'Ĉellarĝo devas esti nombro.',
			invalidHeight	: 'Ĉelalto devas esti nombro.',
			invalidRowSpan	: 'Kunfando de linioj devas esti entjera nombro.',
			invalidColSpan	: 'Kunfando de kolumnoj devas esti entjera nombro.',
			chooseColor		: 'Elektu'
		},

		row :
		{
			menu			: 'Linio',
			insertBefore	: 'Enmeti linion antaŭ',
			insertAfter		: 'Enmeti linion post',
			deleteRow		: 'Forigi Liniojn'
		},

		column :
		{
			menu			: 'Kolumno',
			insertBefore	: 'Enmeti kolumnon antaŭ',
			insertAfter		: 'Enmeti kolumnon post',
			deleteColumn	: 'Forigi Kolumnojn'
		}
	},

	// Button Dialog.
	button :
	{
		title		: 'Butonaj atributoj',
		text		: 'Teksto (Valoro)',
		type		: 'Tipo',
		typeBtn		: 'Butono',
		typeSbm		: 'Validigi (submit)',
		typeRst		: 'Remeti en la originstaton (Reset)'
	},

	// Checkbox and Radio Button Dialogs.
	checkboxAndRadio :
	{
		checkboxTitle : 'Markobutonaj Atributoj',
		radioTitle	: 'Radiobutonaj Atributoj',
		value		: 'Valoro',
		selected	: 'Selektita'
	},

	// Form Dialog.
	form :
	{
		title		: 'Formularaj Atributoj',
		menu		: 'Formularaj Atributoj',
		action		: 'Ago',
		method		: 'Metodo',
		encoding	: 'Kodoprezento'
	},

	// Select Field Dialog.
	select :
	{
		title		: 'Atributoj de Elekta Kampo',
		selectInfo	: 'Informoj pri la rulummenuo',
		opAvail		: 'Elektoj Disponeblaj',
		value		: 'Valoro',
		size		: 'Grando',
		lines		: 'Linioj',
		chkMulti	: 'Permesi Plurajn Elektojn',
		opText		: 'Teksto',
		opValue		: 'Valoro',
		btnAdd		: 'Aldoni',
		btnModify	: 'Modifi',
		btnUp		: 'Supren',
		btnDown		: 'Malsupren',
		btnSetValue : 'Agordi kiel Elektitan Valoron',
		btnDelete	: 'Forigi'
	},

	// Textarea Dialog.
	textarea :
	{
		title		: 'Atributoj de Teksta Areo',
		cols		: 'Kolumnoj',
		rows		: 'Linioj'
	},

	// Text Field Dialog.
	textfield :
	{
		title		: 'Atributoj de Teksta Kampo',
		name		: 'Nomo',
		value		: 'Valoro',
		charWidth	: 'Signolarĝo',
		maxChars	: 'Maksimuma Nombro da Signoj',
		type		: 'Tipo',
		typeText	: 'Teksto',
		typePass	: 'Pasvorto'
	},

	// Hidden Field Dialog.
	hidden :
	{
		title	: 'Atributoj de Kaŝita Kampo',
		name	: 'Nomo',
		value	: 'Valoro'
	},

	// Image Dialog.
	image :
	{
		title		: 'Atributoj de Bildo',
		titleButton	: 'Bildbutonaj Atributoj',
		menu		: 'Atributoj de Bildo',
		infoTab		: 'Informoj pri Bildo',
		btnUpload	: 'Sendu al Servilo',
		upload		: 'Alŝuti',
		alt			: 'Anstataŭiga Teksto',
		lockRatio	: 'Konservi Proporcion',
		resetSize	: 'Origina Grando',
		border		: 'Bordero',
		hSpace		: 'Horizontala Spaco',
		vSpace		: 'Vertikala Spaco',
		alertUrl	: 'Bonvolu tajpi la retadreson de la bildo',
		linkTab		: 'Ligilo',
		button2Img	: 'Ĉu vi volas transformi la selektitan bildbutonon en simplan bildon?',
		img2Button	: 'Ĉu vi volas transformi la selektitan bildon en bildbutonon?',
		urlMissing	: 'La fontretadreso de la bildo mankas.',
		validateBorder	: 'La bordero devas esti entjera nombro.',
		validateHSpace	: 'La horizontala spaco devas esti entjera nombro.',
		validateVSpace	: 'La vertikala spaco devas esti entjera nombro.'
	},

	// Flash Dialog
	flash :
	{
		properties		: 'Flaŝatributoj',
		propertiesTab	: 'Atributoj',
		title			: 'Flaŝatributoj',
		chkPlay			: 'Aŭtomata legado',
		chkLoop			: 'Iteracio',
		chkMenu			: 'Ebligi flaŝmenuon',
		chkFull			: 'Permesi tutekranon',
 		scale			: 'Skalo',
		scaleAll		: 'Montri ĉion',
		scaleNoBorder	: 'Neniu bordero',
		scaleFit		: 'Origina grando',
		access			: 'Atingi skriptojn',
		accessAlways	: 'Ĉiam',
		accessSameDomain: 'Sama domajno',
		accessNever		: 'Neniam',
		alignAbsBottom	: 'Absoluta Malsupro',
		alignAbsMiddle	: 'Absoluta Centro',
		alignBaseline	: 'TekstoMalsupro',
		alignTextTop	: 'TekstoSupro',
		quality			: 'Kvalito',
		qualityBest		: 'Plej bona',
		qualityHigh		: 'Alta',
		qualityAutoHigh	: 'Aŭtomate alta',
		qualityMedium	: 'Meza',
		qualityAutoLow	: 'Aŭtomate malalta',
		qualityLow		: 'Malalta',
		windowModeWindow: 'Fenestro',
		windowModeOpaque: 'Opaka',
		windowModeTransparent : 'Travidebla',
		windowMode		: 'Fenestra reĝimo',
		flashvars		: 'Variabloj por Flaŝo',
		bgcolor			: 'Fona Koloro',
		hSpace			: 'Horizontala Spaco',
		vSpace			: 'Vertikala Spaco',
		validateSrc		: 'Bonvolu entajpi la retadreson (URL)',
		validateHSpace	: 'Horizontala Spaco devas esti nombro.',
		validateVSpace	: 'Vertikala Spaco devas esti nombro.'
	},

	// Speller Pages Dialog
	spellCheck :
	{
		toolbar			: 'Kontroli la ortografion',
		title			: 'Kontroli la ortografion',
		notAvailable	: 'Bedaŭrinde la servo ne funkcias nuntempe.',
		errorLoading	: 'Eraro en la servoelŝuto el la gastiga komputiko: %s.',
		notInDic		: 'Ne trovita en la vortaro',
		changeTo		: 'Ŝanĝi al',
		btnIgnore		: 'Ignori',
		btnIgnoreAll	: 'Ignori Ĉion',
		btnReplace		: 'Anstataŭigi',
		btnReplaceAll	: 'Anstataŭigi Ĉion',
		btnUndo			: 'Malfari',
		noSuggestions	: '- Neniu propono -',
		progress		: 'La ortografio estas kontrolata...',
		noMispell		: 'Ortografikontrolado finita: neniu eraro trovita',
		noChanges		: 'Ortografikontrolado finita: neniu vorto korektita',
		oneChange		: 'Ortografikontrolado finita: unu vorto korektita',
		manyChanges		: 'Ortografikontrolado finita: %1 vortoj korektitaj',
		ieSpellDownload	: 'Ortografikontrolilo ne instalita. Ĉu vi volas elŝuti ĝin nun?'
	},

	smiley :
	{
		toolbar	: 'Mienvinjeto',
		title	: 'Enmeti Mienvinjeton',
		options : 'Opcioj pri mienvinjetoj'
	},

	elementsPath :
	{
		eleLabel : 'Vojo al Elementoj',
		eleTitle : '%1 elementoj'
	},

	numberedlist	: 'Numera Listo',
	bulletedlist	: 'Bula Listo',
	indent			: 'Pligrandigi Krommarĝenon',
	outdent			: 'Malpligrandigi Krommarĝenon',

	justify :
	{
		left	: 'Ĝisrandigi maldekstren',
		center	: 'Centrigi',
		right	: 'Ĝisrandigi dekstren',
		block	: 'Ĝisrandigi Ambaŭflanke'
	},

	blockquote : 'Citaĵo',

	clipboard :
	{
		title		: 'Interglui',
		cutError	: 'La sekurecagordo de via TTT-legilo ne permesas, ke la redaktilo faras eltondajn operaciojn. Bonvolu uzi la klavaron por tio (Ctrl/Cmd-X).',
		copyError	: 'La sekurecagordo de via TTT-legilo ne permesas, ke la redaktilo faras kopiajn operaciojn. Bonvolu uzi la klavaron por tio (Ctrl/Cmd-C).',
		pasteMsg	: 'Bonvolu glui la tekston en la jenan areon per uzado de la klavaro (<strong>Ctrl/Cmd+V</strong>) kaj premu OK',
		securityMsg	: 'Pro la sekurecagordo de via TTT-legilo, la redaktilo ne povas rekte atingi viajn datenojn en la poŝo. Bonvolu denove interglui la datenojn en tiun fenestron.',
		pasteArea	: 'Intergluoareo'
	},

	pastefromword :
	{
		confirmCleanup	: 'La teksto, kiun vi volas interglui, ŝajnas esti kopiita el Word. Ĉu vi deziras purigi ĝin antaŭ intergluo?',
		toolbar			: 'Interglui el Word',
		title			: 'Interglui el Word',
		error			: 'Ne eblis purigi la intergluitajn datenojn pro interna eraro'
	},

	pasteText :
	{
		button	: 'Interglui kiel platan tekston',
		title	: 'Interglui kiel platan tekston'
	},

	templates :
	{
		button			: 'Ŝablonoj',
		title			: 'Enhavo de ŝablonoj',
		options : 'Opcioj pri ŝablonoj',
		insertOption	: 'Anstataŭigi la nunan enhavon',
		selectPromptMsg	: 'Bonvolu selekti la ŝablonon por malfermi ĝin en la redaktilo',
		emptyListMsg	: '(Neniu ŝablono difinita)'
	},

	showBlocks : 'Montri la blokojn',

	stylesCombo :
	{
		label		: 'Stiloj',
		panelTitle	: 'Stiloj pri enpaĝigo',
		panelTitle1	: 'Stiloj de blokoj',
		panelTitle2	: 'Enliniaj Stiloj',
		panelTitle3	: 'Stiloj de objektoj'
	},

	format :
	{
		label		: 'Formato',
		panelTitle	: 'ParagrafFormato',

		tag_p		: 'Normala',
		tag_pre		: 'Formatita',
		tag_address	: 'Adreso',
		tag_h1		: 'Titolo 1',
		tag_h2		: 'Titolo 2',
		tag_h3		: 'Titolo 3',
		tag_h4		: 'Titolo 4',
		tag_h5		: 'Titolo 5',
		tag_h6		: 'Titolo 6',
		tag_div		: 'Normala (DIV)'
	},

	div :
	{
		title				: 'Krei DIV ujon',
		toolbar				: 'Krei DIV ujon',
		cssClassInputLabel	: 'Stilfolioklasoj',
		styleSelectLabel	: 'Stilo',
		IdInputLabel		: 'Id',
		languageCodeInputLabel	: ' Lingvokodo',
		inlineStyleInputLabel	: 'Enlinia stilo',
		advisoryTitleInputLabel	: 'Priskriba Titolo',
		langDirLabel		: 'Skribdirekto',
		langDirLTRLabel		: 'Maldekstre dekstren (angle LTR)',
		langDirRTLLabel		: 'Dekstre maldekstren (angle RTL)',
		edit				: 'Redakti Div',
		remove				: 'Forigi Div'
  	},

	iframe :
	{
		title		: 'Atributoj de la enlinia kadro (IFrame)',
		toolbar		: 'Enlinia kadro (IFrame)',
		noUrl		: 'Bonvolu entajpi la retadreson de la ligilo al la enlinia kadro (IFrame)',
		scrolling	: 'Ebligi rulumskalon',
		border		: 'Montri borderon de kadro (frame)'
	},

	font :
	{
		label		: 'Tiparo',
		voiceLabel	: 'Tiparo',
		panelTitle	: 'Tipara nomo'
	},

	fontSize :
	{
		label		: 'Grado',
		voiceLabel	: 'Tipara grado',
		panelTitle	: 'Tipara grado'
	},

	colorButton :
	{
		textColorTitle	: 'Teksta Koloro',
		bgColorTitle	: 'Fona Koloro',
		panelTitle		: 'Koloroj',
		auto			: 'Aŭtomata',
		more			: 'Pli da Koloroj...'
	},

	colors :
	{
		'000' : 'Nigra',
		'800000' : 'Kaŝtankolora',
		'8B4513' : 'Mezbruna',
		'2F4F4F' : 'Ardezgriza',
		'008080' : 'Marĉanaskolora',
		'000080' : 'Maristblua',
		'4B0082' : 'Indigokolora',
		'696969' : 'Malhelgriza',
		'B22222' : 'Brikruĝa',
		'A52A2A' : 'Bruna',
		'DAA520' : 'Senbrilorkolora',
		'006400' : 'Malhelverda',
		'40E0D0' : 'Turkisblua',
		'0000CD' : 'Reĝblua',
		'800080' : 'Purpura',
		'808080' : 'Griza',
		'F00' : 'Ruĝa',
		'FF8C00' : 'Malheloranĝkolora',
		'FFD700' : 'Orkolora',
		'008000' : 'Verda',
		'0FF' : 'Verdblua',
		'00F' : 'Blua',
		'EE82EE' : 'Viola',
		'A9A9A9' : 'Mezgriza',
		'FFA07A' : 'Salmokolora',
		'FFA500' : 'Oranĝkolora',
		'FFFF00' : 'Flava',
		'00FF00' : 'Limetkolora',
		'AFEEEE' : 'Helturkiskolora',
		'ADD8E6' : 'Helblua',
		'DDA0DD' : 'Prunkolora',
		'D3D3D3' : 'Helgriza',
		'FFF0F5' : 'Lavendkolora vangoŝminko',
		'FAEBD7' : 'Antikvablanka',
		'FFFFE0' : 'Helflava',
		'F0FFF0' : 'Vintromelonkolora',
		'F0FFFF' : 'Lazura',
		'F0F8FF' : 'Aliceblua',
		'E6E6FA' : 'Lavendkolora',
		'FFF' : 'Blanka'
	},

	scayt :
	{
		title			: 'OrtografiKontrolado Dum Vi Tajpas (OKDVT)',
		opera_title		: 'Ne subportata de Opera',
		enable			: 'Ebligi OKDVT',
		disable			: 'Malebligi OKDVT',
		about			: 'Pri OKDVT',
		toggle			: 'Baskuligi OKDVT',
		options			: 'Opcioj',
		langs			: 'Lingvoj',
		moreSuggestions	: 'Pli da sugestoj',
		ignore			: 'Ignori',
		ignoreAll		: 'Ignori ĉion',
		addWord			: 'Almeti la vorton',
		emptyDic		: 'La vortaronomo ne devus esti malplena.',

		optionsTab		: 'Opcioj',
		allCaps			: 'Ignori la vortojn skribitajn nur per ĉefliteroj',
		ignoreDomainNames : 'Ignori domajnajn nomojn',
		mixedCase		: 'Ignori vortojn kun miksa uskleco',
		mixedWithDigits	: 'Ignori vortojn kun nombroj',

		languagesTab	: 'Lingvoj',

		dictionariesTab	: 'Vortaroj',
		dic_field_name	: 'Vortaronomo',
		dic_create		: 'Krei',
		dic_restore		: 'Restaŭri',
		dic_delete		: 'Forigi',
		dic_rename		: 'Renomi',
		dic_info		: 'Komence la vortaro de la uzanto estas konservita en kuketo. Tamen la kuketgrando estas limigita. Kiam la vortaro de la uzanto atingas grandon, kiu ne plu ebligas konservi ĝin en kuketo, tiam la vortaro povas esti konservata en niaj serviloj. Por konservi vian personan vortaron en nian servilon, vi devas indiki nomon por tiu vortaro. Se vi jam havas konservitan vortaron, bonvolu entajpi ties nomon kaj alklaki la restaŭrbutonon.',

		aboutTab		: 'Pri'
	},

	about :
	{
		title		: 'Pri CKEditor',
		dlgTitle	: 'Pri CKEditor',
		help	: 'Kontroli $1 por helpo.',
		userGuide : 'CKEditor Uzindikoj',
		moreInfo	: 'Por informoj pri licenco, bonvolu viziti nian retpaĝaron:',
		copy		: 'Copyright &copy; $1. Ĉiuj rajtoj rezervitaj.'
	},

	maximize : 'Pligrandigi',
	minimize : 'Malgrandigi',

	fakeobjects :
	{
		anchor		: 'Ankro',
		flash		: 'FlaŝAnimacio',
		iframe		: 'Enlinia Kadro (IFrame)',
		hiddenfield	: 'Kaŝita kampo',
		unknown		: 'Nekonata objekto'
	},

	resize : 'Movigi por ŝanĝi la grandon',

	colordialog :
	{
		title		: 'Selekti koloron',
		options	:	'Opcioj pri koloroj',
		highlight	: 'Detaloj',
		selected	: 'Selektita koloro',
		clear		: 'Forigi'
	},

	toolbarCollapse	: 'Faldi la ilbreton',
	toolbarExpand	: 'Malfaldi la ilbreton',

	toolbarGroups :
	{
		document : 'Dokumento',
		clipboard : 'Poŝo/Malfari',
		editing : 'Redaktado',
		forms : 'Formularoj',
		basicstyles : 'Bazaj stiloj',
		paragraph : 'Paragrafo',
		links : 'Ligiloj',
		insert : 'Enmeti',
		styles : 'Stiloj',
		colors : 'Koloroj',
		tools : 'Iloj'
	},

	bidi :
	{
		ltr : 'Tekstdirekto de maldekstre dekstren',
		rtl : 'Tekstdirekto de dekstre maldekstren'
	},

	docprops :
	{
		label : 'Dokumentaj Atributoj',
		title : 'Dokumentaj Atributoj',
		design : 'Dizajno',
		meta : 'Metadatenoj',
		chooseColor : 'Elektu',
		other : '<alia>',
		docTitle :	'Paĝotitolo',
		charset : 	'Signara Kodo',
		charsetOther : 'Alia Signara Kodo',
		charsetASCII : 'ASCII',
		charsetCE : 'Centra Eŭropa',
		charsetCT : 'Tradicia Ĉina (Big5)',
		charsetCR : 'Cirila',
		charsetGR : 'Greka',
		charsetJP : 'Japana',
		charsetKR : 'Korea',
		charsetTR : 'Turka',
		charsetUN : 'Unikodo (UTF-8)',
		charsetWE : 'Okcidenta Eŭropa',
		docType : 'Dokumenta Tipo',
		docTypeOther : 'Alia Dokumenta Tipo',
		xhtmlDec : 'Inkluzivi XHTML Deklarojn',
		bgColor : 'Fona Koloro',
		bgImage : 'URL de Fona Bildo',
		bgFixed : 'Neruluma Fono',
		txtColor : 'Teksta Koloro',
		margin : 'Paĝaj Marĝenoj',
		marginTop : 'Supra',
		marginLeft : 'Maldekstra',
		marginRight : 'Dekstra',
		marginBottom : 'Malsupra',
		metaKeywords : 'Ŝlosilvortoj de la Dokumento (apartigitaj de komoj)',
		metaDescription : 'Dokumenta Priskribo',
		metaAuthor : 'Verkinto',
		metaCopyright : 'Kopirajto',
		previewHtml : '<p>Tio estas <strong>sampla teksto</strong>. Vi estas uzanta <a href="javascript:void(0)">CKEditor</a>.</p>'
	}
};
