/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Italian language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'it' ] = {
	// ARIA description.
	editor: 'Rich Text Editor',
	editorPanel: 'Pannello Rich Text Editor',

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Premi ALT 0 per aiuto',

		browseServer: 'Cerca sul server',
		url: 'URL',
		protocol: 'Protocollo',
		upload: 'Carica',
		uploadSubmit: 'Invia al server',
		image: 'Immagine',
		flash: 'Oggetto Flash',
		form: 'Modulo',
		checkbox: 'Checkbox',
		radio: 'Radio Button',
		textField: 'Campo di testo',
		textarea: 'Area di testo',
		hiddenField: 'Campo nascosto',
		button: 'Bottone',
		select: 'Menu di selezione',
		imageButton: 'Bottone immagine',
		notSet: '<non impostato>',
		id: 'Id',
		name: 'Nome',
		langDir: 'Direzione scrittura',
		langDirLtr: 'Da Sinistra a Destra (LTR)',
		langDirRtl: 'Da Destra a Sinistra (RTL)',
		langCode: 'Codice Lingua',
		longDescr: 'URL descrizione estesa',
		cssClass: 'Nome classe CSS',
		advisoryTitle: 'Titolo',
		cssStyle: 'Stile',
		ok: 'OK',
		cancel: 'Annulla',
		close: 'Chiudi',
		preview: 'Anteprima',
		resize: 'Trascina per ridimensionare',
		generalTab: 'Generale',
		advancedTab: 'Avanzate',
		validateNumberFailed: 'Il valore inserito non è un numero.',
		confirmNewPage: 'Ogni modifica non salvata sarà persa. Sei sicuro di voler caricare una nuova pagina?',
		confirmCancel: 'Alcune delle opzioni sono state cambiate. Sei sicuro di voler chiudere la finestra di dialogo?',
		options: 'Opzioni',
		target: 'Destinazione',
		targetNew: 'Nuova finestra (_blank)',
		targetTop: 'Finestra in primo piano (_top)',
		targetSelf: 'Stessa finestra (_self)',
		targetParent: 'Finestra Padre (_parent)',
		langDirLTR: 'Da sinistra a destra (LTR)',
		langDirRTL: 'Da destra a sinistra (RTL)',
		styles: 'Stile',
		cssClasses: 'Classi di stile',
		width: 'Larghezza',
		height: 'Altezza',
		align: 'Allineamento',
		alignLeft: 'Sinistra',
		alignRight: 'Destra',
		alignCenter: 'Centrato',
		alignTop: 'In Alto',
		alignMiddle: 'Centrato',
		alignBottom: 'In Basso',
		invalidValue	: 'Valore non valido.',
		invalidHeight: 'L\'altezza dev\'essere un numero',
		invalidWidth: 'La Larghezza dev\'essere un numero',
		invalidCssLength: 'Il valore indicato per il campo "%1" deve essere un numero positivo con o senza indicazione di una valida unità di misura per le classi CSS (px, %, in, cm, mm, em, ex, pt, o pc).',
		invalidHtmlLength: 'Il valore indicato per il campo "%1" deve essere un numero positivo con o senza indicazione di una valida unità di misura per le pagine HTML (px o %).',
		invalidInlineStyle: 'Il valore specificato per lo stile inline deve consistere in una o più tuple con il formato di "name : value", separati da semicolonne.',
		cssLengthTooltip: 'Inserisci un numero per il valore in pixel oppure un numero con una valida unità CSS (px, %, in, cm, mm, ex, pt, o pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, non disponibile</span>'
	}
};
