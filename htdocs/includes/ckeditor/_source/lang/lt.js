﻿/*
Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object for the
 * Lithuanian language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang['lt'] =
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
	editorTitle : 'Rich text editor, %1', // MISSING
	editorHelp : 'Press ALT 0 for help', // MISSING

	// ARIA descriptions.
	toolbars	: 'Redaktoriaus įrankiai',
	editor		: 'Pilnas redaktorius',

	// Toolbar buttons without dialogs.
	source			: 'Šaltinis',
	newPage			: 'Naujas puslapis',
	save			: 'Išsaugoti',
	preview			: 'Peržiūra',
	cut				: 'Iškirpti',
	copy			: 'Kopijuoti',
	paste			: 'Įdėti',
	print			: 'Spausdinti',
	underline		: 'Pabrauktas',
	bold			: 'Pusjuodis',
	italic			: 'Kursyvas',
	selectAll		: 'Pažymėti viską',
	removeFormat	: 'Panaikinti formatą',
	strike			: 'Perbrauktas',
	subscript		: 'Apatinis indeksas',
	superscript		: 'Viršutinis indeksas',
	horizontalrule	: 'Įterpti horizontalią liniją',
	pagebreak		: 'Įterpti puslapių skirtuką',
	pagebreakAlt		: 'Puslapio skirtukas',
	unlink			: 'Panaikinti nuorodą',
	undo			: 'Atšaukti',
	redo			: 'Atstatyti',

	// Common messages and labels.
	common :
	{
		browseServer	: 'Naršyti po serverį',
		url				: 'URL',
		protocol		: 'Protokolas',
		upload			: 'Siųsti',
		uploadSubmit	: 'Siųsti į serverį',
		image			: 'Vaizdas',
		flash			: 'Flash',
		form			: 'Forma',
		checkbox		: 'Žymimasis langelis',
		radio			: 'Žymimoji akutė',
		textField		: 'Teksto laukas',
		textarea		: 'Teksto sritis',
		hiddenField		: 'Nerodomas laukas',
		button			: 'Mygtukas',
		select			: 'Atrankos laukas',
		imageButton		: 'Vaizdinis mygtukas',
		notSet			: '<nėra nustatyta>',
		id				: 'Id',
		name			: 'Vardas',
		langDir			: 'Teksto kryptis',
		langDirLtr		: 'Iš kairės į dešinę (LTR)',
		langDirRtl		: 'Iš dešinės į kairę (RTL)',
		langCode		: 'Kalbos kodas',
		longDescr		: 'Ilgas aprašymas URL',
		cssClass		: 'Stilių lentelės klasės',
		advisoryTitle	: 'Konsultacinė antraštė',
		cssStyle		: 'Stilius',
		ok				: 'OK',
		cancel			: 'Nutraukti',
		close			: 'Uždaryti',
		preview			: 'Peržiūrėti',
		generalTab		: 'Bendros savybės',
		advancedTab		: 'Papildomas',
		validateNumberFailed : 'Ši reikšmė nėra skaičius.',
		confirmNewPage	: 'Visas neišsaugotas turinys bus prarastas. Ar tikrai norite įkrauti naują puslapį?',
		confirmCancel	: 'Kai kurie parametrai pasikeitė. Ar tikrai norite užverti langą?',
		options			: 'Parametrai',
		target			: 'Tikslinė nuoroda',
		targetNew		: 'Naujas langas (_blank)',
		targetTop		: 'Viršutinis langas (_top)',
		targetSelf		: 'Esamas langas (_self)',
		targetParent	: 'Paskutinis langas (_parent)',
		langDirLTR		: 'Iš kairės į dešinę (LTR)',
		langDirRTL		: 'Iš dešinės į kairę (RTL)',
		styles			: 'Stilius',
		cssClasses		: 'Stilių klasės',
		width			: 'Plotis',
		height			: 'Aukštis',
		align			: 'Lygiuoti',
		alignLeft		: 'Kairę',
		alignRight		: 'Dešinę',
		alignCenter		: 'Centrą',
		alignTop		: 'Viršūnę',
		alignMiddle		: 'Vidurį',
		alignBottom		: 'Apačią',
		invalidHeight	: 'Aukštis turi būti nurodytas skaičiais.',
		invalidWidth	: 'Plotis turi būti nurodytas skaičiais.',
		invalidCssLength	: 'Reikšmė nurodyta "%1" laukui, turi būti teigiamas skaičius su arba be tinkamo CSS matavimo vieneto (px, %, in, cm, mm, em, ex, pt arba pc).',
		invalidHtmlLength	: 'Reikšmė nurodyta "%1" laukui, turi būti teigiamas skaičius su arba be tinkamo HTML matavimo vieneto (px arba %).',
		invalidInlineStyle	: 'Reikšmė nurodyta vidiniame stiliuje turi būti sudaryta iš vieno šių reikšmių "vardas : reikšmė", atskirta kabliataškiais.',
		cssLengthTooltip	: 'Įveskite reikšmę pikseliais arba skaičiais su tinkamu CSS vienetu (px, %, in, cm, mm, em, ex, pt arba pc).',

		// Put the voice-only part of the label in the span.
		unavailable		: '%1<span class="cke_accessibility">, netinkamas</span>'
	},

	contextmenu :
	{
		options : 'Kontekstinio meniu parametrai'
	},

	// Special char dialog.
	specialChar		:
	{
		toolbar		: 'Įterpti specialų simbolį',
		title		: 'Pasirinkite specialų simbolį',
		options : 'Specialaus simbolio nustatymai'
	},

	// Link dialog.
	link :
	{
		toolbar		: 'Įterpti/taisyti nuorodą',
		other 		: '<kitas>',
		menu		: 'Taisyti nuorodą',
		title		: 'Nuoroda',
		info		: 'Nuorodos informacija',
		target		: 'Paskirties vieta',
		upload		: 'Siųsti',
		advanced	: 'Papildomas',
		type		: 'Nuorodos tipas',
		toUrl		: 'Nuoroda',
		toAnchor	: 'Žymė šiame puslapyje',
		toEmail		: 'El.paštas',
		targetFrame		: '<kadras>',
		targetPopup		: '<išskleidžiamas langas>',
		targetFrameName	: 'Paskirties kadro vardas',
		targetPopupName	: 'Paskirties lango vardas',
		popupFeatures	: 'Išskleidžiamo lango savybės',
		popupResizable	: 'Kintamas dydis',
		popupStatusBar	: 'Būsenos juosta',
		popupLocationBar: 'Adreso juosta',
		popupToolbar	: 'Mygtukų juosta',
		popupMenuBar	: 'Meniu juosta',
		popupFullScreen	: 'Visas ekranas (IE)',
		popupScrollBars	: 'Slinkties juostos',
		popupDependent	: 'Priklausomas (Netscape)',
		popupLeft		: 'Kairė pozicija',
		popupTop		: 'Viršutinė pozicija',
		id				: 'Id',
		langDir			: 'Teksto kryptis',
		langDirLTR		: 'Iš kairės į dešinę (LTR)',
		langDirRTL		: 'Iš dešinės į kairę (RTL)',
		acccessKey		: 'Prieigos raktas',
		name			: 'Vardas',
		langCode			: 'Teksto kryptis',
		tabIndex			: 'Tabuliavimo indeksas',
		advisoryTitle		: 'Konsultacinė antraštė',
		advisoryContentType	: 'Konsultacinio turinio tipas',
		cssClasses		: 'Stilių lentelės klasės',
		charset			: 'Susietų išteklių simbolių lentelė',
		styles			: 'Stilius',
		rel			: 'Sąsajos',
		selectAnchor		: 'Pasirinkite žymę',
		anchorName		: 'Pagal žymės vardą',
		anchorId			: 'Pagal žymės Id',
		emailAddress		: 'El.pašto adresas',
		emailSubject		: 'Žinutės tema',
		emailBody		: 'Žinutės turinys',
		noAnchors		: '(Šiame dokumente žymių nėra)',
		noUrl			: 'Prašome įvesti nuorodos URL',
		noEmail			: 'Prašome įvesti el.pašto adresą'
	},

	// Anchor dialog
	anchor :
	{
		toolbar		: 'Įterpti/modifikuoti žymę',
		menu		: 'Žymės savybės',
		title		: 'Žymės savybės',
		name		: 'Žymės vardas',
		errorName	: 'Prašome įvesti žymės vardą',
		remove		: 'Pašalinti žymę'
	},

	// List style dialog
	list:
	{
		numberedTitle		: 'Skaitmeninio sąrašo nustatymai',
		bulletedTitle		: 'Ženklelinio sąrašo nustatymai',
		type				: 'Rūšis',
		start				: 'Pradžia',
		validateStartNumber				:'Sąrašo pradžios skaitmuo turi būti sveikas skaičius.',
		circle				: 'Apskritimas',
		disc				: 'Diskas',
		square				: 'Kvadratas',
		none				: 'Niekas',
		notset				: '<nenurodytas>',
		armenian			: 'Armėniški skaitmenys',
		georgian			: 'Gruziniški skaitmenys (an, ban, gan, t.t)',
		lowerRoman			: 'Mažosios Romėnų (i, ii, iii, iv, v, t.t)',
		upperRoman			: 'Didžiosios Romėnų (I, II, III, IV, V, t.t)',
		lowerAlpha			: 'Mažosios Alpha (a, b, c, d, e, t.t)',
		upperAlpha			: 'Didžiosios Alpha (A, B, C, D, E, t.t)',
		lowerGreek			: 'Mažosios Graikų (alpha, beta, gamma, t.t)',
		decimal				: 'Dešimtainis (1, 2, 3, t.t)',
		decimalLeadingZero	: 'Dešimtainis su nuliu priekyje (01, 02, 03, t.t)'
	},

	// Find And Replace Dialog
	findAndReplace :
	{
		title				: 'Surasti ir pakeisti',
		find				: 'Rasti',
		replace				: 'Pakeisti',
		findWhat			: 'Surasti tekstą:',
		replaceWith			: 'Pakeisti tekstu:',
		notFoundMsg			: 'Nurodytas tekstas nerastas.',
		findOptions			: 'Paieškos nustatymai',
		matchCase			: 'Skirti didžiąsias ir mažąsias raides',
		matchWord			: 'Atitikti pilną žodį',
		matchCyclic			: 'Sutampantis cikliškumas',
		replaceAll			: 'Pakeisti viską',
		replaceSuccessMsg	: '%1 sutapimas(ų) buvo pakeisti.'
	},

	// Table Dialog
	table :
	{
		toolbar		: 'Lentelė',
		title		: 'Lentelės savybės',
		menu		: 'Lentelės savybės',
		deleteTable	: 'Šalinti lentelę',
		rows		: 'Eilutės',
		columns		: 'Stulpeliai',
		border		: 'Rėmelio dydis',
		widthPx		: 'taškais',
		widthPc		: 'procentais',
		widthUnit	: 'pločio vienetas',
		cellSpace	: 'Tarpas tarp langelių',
		cellPad		: 'Trapas nuo langelio rėmo iki teksto',
		caption		: 'Antraštė',
		summary		: 'Santrauka',
		headers		: 'Antraštės',
		headersNone		: 'Nėra',
		headersColumn	: 'Pirmas stulpelis',
		headersRow		: 'Pirma eilutė',
		headersBoth		: 'Abu',
		invalidRows		: 'Skaičius turi būti didesnis nei 0.',
		invalidCols		: 'Skaičius turi būti didesnis nei 0.',
		invalidBorder	: 'Reikšmė turi būti nurodyta skaičiumi.',
		invalidWidth	: 'Reikšmė turi būti nurodyta skaičiumi.',
		invalidHeight	: 'Reikšmė turi būti nurodyta skaičiumi.',
		invalidCellSpacing	: 'Reikšmė turi būti nurodyta skaičiumi.',
		invalidCellPadding	: 'Reikšmė turi būti nurodyta skaičiumi.',

		cell :
		{
			menu			: 'Langelis',
			insertBefore	: 'Įterpti langelį prieš',
			insertAfter		: 'Įterpti langelį po',
			deleteCell		: 'Šalinti langelius',
			merge			: 'Sujungti langelius',
			mergeRight		: 'Sujungti su dešine',
			mergeDown		: 'Sujungti su apačia',
			splitHorizontal	: 'Skaidyti langelį horizontaliai',
			splitVertical	: 'Skaidyti langelį vertikaliai',
			title			: 'Cell nustatymai',
			cellType		: 'Cell rūšis',
			rowSpan			: 'Eilučių Span',
			colSpan			: 'Stulpelių Span',
			wordWrap		: 'Sutraukti raides',
			hAlign			: 'Horizontalus lygiavimas',
			vAlign			: 'Vertikalus lygiavimas',
			alignBaseline	: 'Apatinė linija',
			bgColor			: 'Fono spalva',
			borderColor		: 'Rėmelio spalva',
			data			: 'Data',
			header			: 'Antraštė',
			yes				: 'Taip',
			no				: 'Ne',
			invalidWidth	: 'Reikšmė turi būti skaičius.',
			invalidHeight	: 'Reikšmė turi būti skaičius.',
			invalidRowSpan	: 'Reikšmė turi būti skaičius.',
			invalidColSpan	: 'Reikšmė turi būti skaičius.',
			chooseColor		: 'Pasirinkite'
		},

		row :
		{
			menu			: 'Eilutė',
			insertBefore	: 'Įterpti eilutę prieš',
			insertAfter		: 'Įterpti eilutę po',
			deleteRow		: 'Šalinti eilutes'
		},

		column :
		{
			menu			: 'Stulpelis',
			insertBefore	: 'Įterpti stulpelį prieš',
			insertAfter		: 'Įterpti stulpelį po',
			deleteColumn	: 'Šalinti stulpelius'
		}
	},

	// Button Dialog.
	button :
	{
		title		: 'Mygtuko savybės',
		text		: 'Tekstas (Reikšmė)',
		type		: 'Tipas',
		typeBtn		: 'Mygtukas',
		typeSbm		: 'Siųsti',
		typeRst		: 'Išvalyti'
	},

	// Checkbox and Radio Button Dialogs.
	checkboxAndRadio :
	{
		checkboxTitle : 'Žymimojo langelio savybės',
		radioTitle	: 'Žymimosios akutės savybės',
		value		: 'Reikšmė',
		selected	: 'Pažymėtas'
	},

	// Form Dialog.
	form :
	{
		title		: 'Formos savybės',
		menu		: 'Formos savybės',
		action		: 'Veiksmas',
		method		: 'Metodas',
		encoding	: 'Kodavimas'
	},

	// Select Field Dialog.
	select :
	{
		title		: 'Atrankos lauko savybės',
		selectInfo	: 'Informacija',
		opAvail		: 'Galimos parinktys',
		value		: 'Reikšmė',
		size		: 'Dydis',
		lines		: 'eilučių',
		chkMulti	: 'Leisti daugeriopą atranką',
		opText		: 'Tekstas',
		opValue		: 'Reikšmė',
		btnAdd		: 'Įtraukti',
		btnModify	: 'Modifikuoti',
		btnUp		: 'Aukštyn',
		btnDown		: 'Žemyn',
		btnSetValue : 'Laikyti pažymėta reikšme',
		btnDelete	: 'Trinti'
	},

	// Textarea Dialog.
	textarea :
	{
		title		: 'Teksto srities savybės',
		cols		: 'Ilgis',
		rows		: 'Plotis'
	},

	// Text Field Dialog.
	textfield :
	{
		title		: 'Teksto lauko savybės',
		name		: 'Vardas',
		value		: 'Reikšmė',
		charWidth	: 'Ilgis simboliais',
		maxChars	: 'Maksimalus simbolių skaičius',
		type		: 'Tipas',
		typeText	: 'Tekstas',
		typePass	: 'Slaptažodis'
	},

	// Hidden Field Dialog.
	hidden :
	{
		title	: 'Nerodomo lauko savybės',
		name	: 'Vardas',
		value	: 'Reikšmė'
	},

	// Image Dialog.
	image :
	{
		title		: 'Vaizdo savybės',
		titleButton	: 'Vaizdinio mygtuko savybės',
		menu		: 'Vaizdo savybės',
		infoTab		: 'Vaizdo informacija',
		btnUpload	: 'Siųsti į serverį',
		upload		: 'Nusiųsti',
		alt			: 'Alternatyvus Tekstas',
		lockRatio	: 'Išlaikyti proporciją',
		resetSize	: 'Atstatyti dydį',
		border		: 'Rėmelis',
		hSpace		: 'Hor.Erdvė',
		vSpace		: 'Vert.Erdvė',
		alertUrl	: 'Prašome įvesti vaizdo URL',
		linkTab		: 'Nuoroda',
		button2Img	: 'Ar norite mygtuką paversti paprastu paveiksliuku?',
		img2Button	: 'Ar norite paveiksliuką paversti mygtuku?',
		urlMissing	: 'Paveiksliuko nuorodos nėra.',
		validateBorder	: 'Reikšmė turi būti sveikas skaičius.',
		validateHSpace	: 'Reikšmė turi būti sveikas skaičius.',
		validateVSpace	: 'Reikšmė turi būti sveikas skaičius.'
	},

	// Flash Dialog
	flash :
	{
		properties		: 'Flash savybės',
		propertiesTab	: 'Nustatymai',
		title			: 'Flash savybės',
		chkPlay			: 'Automatinis paleidimas',
		chkLoop			: 'Ciklas',
		chkMenu			: 'Leisti Flash meniu',
		chkFull			: 'Leisti per visą ekraną',
 		scale			: 'Mastelis',
		scaleAll		: 'Rodyti visą',
		scaleNoBorder	: 'Be rėmelio',
		scaleFit		: 'Tikslus atitikimas',
		access			: 'Skripto priėjimas',
		accessAlways	: 'Visada',
		accessSameDomain: 'Tas pats domenas',
		accessNever		: 'Niekada',
		alignAbsBottom	: 'Absoliučią apačią',
		alignAbsMiddle	: 'Absoliutų vidurį',
		alignBaseline	: 'Apatinę liniją',
		alignTextTop	: 'Teksto viršūnę',
		quality			: 'Kokybė',
		qualityBest		: 'Geriausia',
		qualityHigh		: 'Gera',
		qualityAutoHigh	: 'Automatiškai Gera',
		qualityMedium	: 'Vidutinė',
		qualityAutoLow	: 'Automatiškai Žema',
		qualityLow		: 'Žema',
		windowModeWindow: 'Langas',
		windowModeOpaque: 'Nepermatomas',
		windowModeTransparent : 'Permatomas',
		windowMode		: 'Lango režimas',
		flashvars		: 'Flash kintamieji',
		bgcolor			: 'Fono spalva',
		hSpace			: 'Hor.Erdvė',
		vSpace			: 'Vert.Erdvė',
		validateSrc		: 'Prašome įvesti nuorodos URL',
		validateHSpace	: 'HSpace turi būti skaičius.',
		validateVSpace	: 'VSpace turi būti skaičius.'
	},

	// Speller Pages Dialog
	spellCheck :
	{
		toolbar			: 'Rašybos tikrinimas',
		title			: 'Tikrinti klaidas',
		notAvailable	: 'Atleiskite, šiuo metu servisas neprieinamas.',
		errorLoading	: 'Klaida įkraunant servisą: %s.',
		notInDic		: 'Žodyne nerastas',
		changeTo		: 'Pakeisti į',
		btnIgnore		: 'Ignoruoti',
		btnIgnoreAll	: 'Ignoruoti visus',
		btnReplace		: 'Pakeisti',
		btnReplaceAll	: 'Pakeisti visus',
		btnUndo			: 'Atšaukti',
		noSuggestions	: '- Nėra pasiūlymų -',
		progress		: 'Vyksta rašybos tikrinimas...',
		noMispell		: 'Rašybos tikrinimas baigtas: Nerasta rašybos klaidų',
		noChanges		: 'Rašybos tikrinimas baigtas: Nėra pakeistų žodžių',
		oneChange		: 'Rašybos tikrinimas baigtas: Vienas žodis pakeistas',
		manyChanges		: 'Rašybos tikrinimas baigtas: Pakeista %1 žodžių',
		ieSpellDownload	: 'Rašybos tikrinimas neinstaliuotas. Ar Jūs norite jį dabar atsisiųsti?'
	},

	smiley :
	{
		toolbar	: 'Veideliai',
		title	: 'Įterpti veidelį',
		options : 'Šypsenėlių nustatymai'
	},

	elementsPath :
	{
		eleLabel : 'Elemento kelias',
		eleTitle : '%1 elementas'
	},

	numberedlist	: 'Numeruotas sąrašas',
	bulletedlist	: 'Suženklintas sąrašas',
	indent			: 'Padidinti įtrauką',
	outdent			: 'Sumažinti įtrauką',

	justify :
	{
		left	: 'Lygiuoti kairę',
		center	: 'Centruoti',
		right	: 'Lygiuoti dešinę',
		block	: 'Lygiuoti abi puses'
	},

	blockquote : 'Citata',

	clipboard :
	{
		title		: 'Įdėti',
		cutError	: 'Jūsų naršyklės saugumo nustatymai neleidžia redaktoriui automatiškai įvykdyti iškirpimo operacijų. Tam prašome naudoti klaviatūrą (Ctrl/Cmd+X).',
		copyError	: 'Jūsų naršyklės saugumo nustatymai neleidžia redaktoriui automatiškai įvykdyti kopijavimo operacijų. Tam prašome naudoti klaviatūrą (Ctrl/Cmd+C).',
		pasteMsg	: 'Žemiau esančiame įvedimo lauke įdėkite tekstą, naudodami klaviatūrą (<STRONG>Ctrl/Cmd+V</STRONG>) ir paspauskite mygtuką <STRONG>OK</STRONG>.',
		securityMsg	: 'Dėl jūsų naršyklės saugumo nustatymų, redaktorius negali tiesiogiai pasiekti laikinosios atminties. Jums reikia nukopijuoti dar kartą į šį langą.',
		pasteArea	: 'Įkelti dalį'
	},

	pastefromword :
	{
		confirmCleanup	: 'Tekstas, kurį įkeliate yra kopijuojamas iš Word. Ar norite jį išvalyti prieš įkeliant?',
		toolbar			: 'Įdėti iš Word',
		title			: 'Įdėti iš Word',
		error			: 'Dėl vidinių sutrikimų, nepavyko išvalyti įkeliamo teksto'
	},

	pasteText :
	{
		button	: 'Įdėti kaip gryną tekstą',
		title	: 'Įdėti kaip gryną tekstą'
	},

	templates :
	{
		button			: 'Šablonai',
		title			: 'Turinio šablonai',
		options : 'Template Options',
		insertOption	: 'Pakeisti dabartinį turinį pasirinktu šablonu',
		selectPromptMsg	: 'Pasirinkite norimą šabloną<br>(<b>Dėmesio!</b> esamas turinys bus prarastas):',
		emptyListMsg	: '(Šablonų sąrašas tuščias)'
	},

	showBlocks : 'Rodyti blokus',

	stylesCombo :
	{
		label		: 'Stilius',
		panelTitle	: 'Stilių formatavimas',
		panelTitle1	: 'Blokų stiliai',
		panelTitle2	: 'Vidiniai stiliai',
		panelTitle3	: 'Objektų stiliai'
	},

	format :
	{
		label		: 'Šrifto formatas',
		panelTitle	: 'Šrifto formatas',

		tag_p		: 'Normalus',
		tag_pre		: 'Formuotas',
		tag_address	: 'Kreipinio',
		tag_h1		: 'Antraštinis 1',
		tag_h2		: 'Antraštinis 2',
		tag_h3		: 'Antraštinis 3',
		tag_h4		: 'Antraštinis 4',
		tag_h5		: 'Antraštinis 5',
		tag_h6		: 'Antraštinis 6',
		tag_div		: 'Normalus (DIV)'
	},

	div :
	{
		title				: 'Sukurti Div elementą',
		toolbar				: 'Sukurti Div elementą',
		cssClassInputLabel	: 'Stilių klasės',
		styleSelectLabel	: 'Stilius',
		IdInputLabel		: 'Id',
		languageCodeInputLabel	: ' Kalbos kodas',
		inlineStyleInputLabel	: 'Vidiniai stiliai',
		advisoryTitleInputLabel	: 'Patariamas pavadinimas',
		langDirLabel		: 'Kalbos nurodymai',
		langDirLTRLabel		: 'Iš kairės į dešinę (LTR)',
		langDirRTLLabel		: 'Iš dešinės į kairę (RTL)',
		edit				: 'Redaguoti Div',
		remove				: 'Pašalinti Div'
  	},

	iframe :
	{
		title		: 'IFrame nustatymai',
		toolbar		: 'IFrame',
		noUrl		: 'Nurodykite iframe nuorodą',
		scrolling	: 'Įjungti slankiklius',
		border		: 'Rodyti rėmelį'
	},

	font :
	{
		label		: 'Šriftas',
		voiceLabel	: 'Šriftas',
		panelTitle	: 'Šriftas'
	},

	fontSize :
	{
		label		: 'Šrifto dydis',
		voiceLabel	: 'Šrifto dydis',
		panelTitle	: 'Šrifto dydis'
	},

	colorButton :
	{
		textColorTitle	: 'Teksto spalva',
		bgColorTitle	: 'Fono spalva',
		panelTitle		: 'Spalva',
		auto			: 'Automatinis',
		more			: 'Daugiau spalvų...'
	},

	colors :
	{
		'000' : 'Juoda',
		'800000' : 'Kaštoninė',
		'8B4513' : 'Tamsiai ruda',
		'2F4F4F' : 'Pilka tamsaus šiferio',
		'008080' : 'Teal',
		'000080' : 'Karinis',
		'4B0082' : 'Indigo',
		'696969' : 'Tamsiai pilka',
		'B22222' : 'Ugnies',
		'A52A2A' : 'Ruda',
		'DAA520' : 'Aukso',
		'006400' : 'Tamsiai žalia',
		'40E0D0' : 'Turquoise',
		'0000CD' : 'Vidutinė mėlyna',
		'800080' : 'Violetinė',
		'808080' : 'Pilka',
		'F00' : 'Raudona',
		'FF8C00' : 'Tamsiai oranžinė',
		'FFD700' : 'Auksinė',
		'008000' : 'Žalia',
		'0FF' : 'Žydra',
		'00F' : 'Mėlyna',
		'EE82EE' : 'Violetinė',
		'A9A9A9' : 'Dim Gray',
		'FFA07A' : 'Light Salmon',
		'FFA500' : 'Oranžinė',
		'FFFF00' : 'Geltona',
		'00FF00' : 'Citrinų',
		'AFEEEE' : 'Pale Turquoise',
		'ADD8E6' : 'Šviesiai mėlyna',
		'DDA0DD' : 'Plum',
		'D3D3D3' : 'Šviesiai pilka',
		'FFF0F5' : 'Lavender Blush',
		'FAEBD7' : 'Antique White',
		'FFFFE0' : 'Šviesiai geltona',
		'F0FFF0' : 'Honeydew',
		'F0FFFF' : 'Azure',
		'F0F8FF' : 'Alice Blue',
		'E6E6FA' : 'Lavender',
		'FFF' : 'Balta'
	},

	scayt :
	{
		title			: 'Tikrinti klaidas kai rašoma',
		opera_title		: 'Nepalaikoma naršyklėje Opera',
		enable			: 'Įjungti SCAYT',
		disable			: 'Išjungti SCAYT',
		about			: 'Apie SCAYT',
		toggle			: 'Perjungti SCAYT',
		options			: 'Parametrai',
		langs			: 'Kalbos',
		moreSuggestions	: 'Daugiau patarimų',
		ignore			: 'Ignoruoti',
		ignoreAll		: 'Ignoruoti viską',
		addWord			: 'Pridėti žodį',
		emptyDic		: 'Žodyno vardas neturėtų būti tuščias.',

		optionsTab		: 'Parametrai',
		allCaps			: 'Ignoruoti visas didžiąsias raides',
		ignoreDomainNames : 'Ignoruoti domenų vardus',
		mixedCase		: 'Ignoruoti maišyto dydžio raides',
		mixedWithDigits	: 'Ignoruoti raides su skaičiais',

		languagesTab	: 'Kalbos',

		dictionariesTab	: 'Žodynai',
		dic_field_name	: 'Žodyno pavadinimas',
		dic_create		: 'Sukurti',
		dic_restore		: 'Atstatyti',
		dic_delete		: 'Ištrinti',
		dic_rename		: 'Pervadinti',
		dic_info		: 'Paprastai žodynas yra saugojamas sausainėliuose (cookies), kurių dydis, bet kokiu atveju, yra apribotas. Esant sausainėlių apimties pervišiui, viskas bus saugoma serveryje. Jei norite iš kart viską saugoti serveryje, turite sugalvoti žodynui pavadinimą. Jei jau turite žodyną, įrašykite pavadinimą ir nuspauskite Atstatyti mygtuką.',

		aboutTab		: 'Apie'
	},

	about :
	{
		title		: 'Apie CKEditor',
		dlgTitle	: 'Apie CKEditor',
		help	: 'Patikrinkite $1 dėl pagalbos.',
		userGuide : 'CKEditor Vartotojo Gidas',
		moreInfo	: 'Dėl licencijavimo apsilankykite mūsų svetainėje:',
		copy		: 'Copyright &copy; $1. Visos teiss saugomos.'
	},

	maximize : 'Išdidinti',
	minimize : 'Sumažinti',

	fakeobjects :
	{
		anchor		: 'Žymė',
		flash		: 'Flash animacija',
		iframe		: 'IFrame',
		hiddenfield	: 'Paslėptas laukas',
		unknown		: 'Nežinomas objektas'
	},

	resize : 'Pavilkite, kad pakeistumėte dydį',

	colordialog :
	{
		title		: 'Pasirinkite spalvą',
		options	:	'Spalvos nustatymai',
		highlight	: 'Paryškinti',
		selected	: 'Pasirinkta spalva',
		clear		: 'Išvalyti'
	},

	toolbarCollapse	: 'Apjungti įrankių juostą',
	toolbarExpand	: 'Išplėsti įrankių juostą',

	toolbarGroups :
	{
		document : 'Dokumentas',
		clipboard : 'Atmintinė/Atgal',
		editing : 'Redagavimas',
		forms : 'Formos',
		basicstyles : 'Pagrindiniai stiliai',
		paragraph : 'Paragrafas',
		links : 'Nuorodos',
		insert : 'Įterpti',
		styles : 'Stiliai',
		colors : 'Spalvos',
		tools : 'Įrankiai'
	},

	bidi :
	{
		ltr : 'Tekstas iš kairės į dešinę',
		rtl : 'Tekstas iš dešinės į kairę'
	},

	docprops :
	{
		label : 'Dokumento savybės',
		title : 'Dokumento savybės',
		design : 'Išdėstymas',
		meta : 'Meta duomenys',
		chooseColor : 'Pasirinkite',
		other : '<kitas>',
		docTitle :	'Puslapio antraštė',
		charset : 	'Simbolių kodavimo lentelė',
		charsetOther : 'Kita simbolių kodavimo lentelė',
		charsetASCII : 'ASCII',
		charsetCE : 'Centrinės Europos',
		charsetCT : 'Tradicinės kinų (Big5)',
		charsetCR : 'Kirilica',
		charsetGR : 'Graikų',
		charsetJP : 'Japonų',
		charsetKR : 'Korėjiečių',
		charsetTR : 'Turkų',
		charsetUN : 'Unikodas (UTF-8)',
		charsetWE : 'Vakarų Europos',
		docType : 'Dokumento tipo antraštė',
		docTypeOther : 'Kita dokumento tipo antraštė',
		xhtmlDec : 'Įtraukti XHTML deklaracijas',
		bgColor : 'Fono spalva',
		bgImage : 'Fono paveikslėlio nuoroda (URL)',
		bgFixed : 'Neslenkantis fonas',
		txtColor : 'Teksto spalva',
		margin : 'Puslapio kraštinės',
		marginTop : 'Viršuje',
		marginLeft : 'Kairėje',
		marginRight : 'Dešinėje',
		marginBottom : 'Apačioje',
		metaKeywords : 'Dokumento indeksavimo raktiniai žodžiai (atskirti kableliais)',
		metaDescription : 'Dokumento apibūdinimas',
		metaAuthor : 'Autorius',
		metaCopyright : 'Autorinės teisės',
		previewHtml : '<p>Tai yra <strong>pavyzdinis tekstas</strong>. Jūs naudojate <a href="javascript:void(0)">CKEditor</a>.</p>'
	}
};
