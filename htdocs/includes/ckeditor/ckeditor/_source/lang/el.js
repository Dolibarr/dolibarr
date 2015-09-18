/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Greek language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'el' ] = {
	// ARIA description.
	editor: 'Επεξεργαστής Πλούσιου Κειμένου',
	editorPanel: 'Πίνακας Επεξεργαστή Πλούσιου Κειμένου',

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Πατήστε το ALT 0 για βοήθεια',

		browseServer: 'Εξερεύνηση Διακομιστή',
		url: 'URL',
		protocol: 'Πρωτόκολλο',
		upload: 'Αποστολή',
		uploadSubmit: 'Αποστολή στον Διακομιστή',
		image: 'Εικόνα',
		flash: 'Flash',
		form: 'Φόρμα',
		checkbox: 'Κουτί Επιλογής',
		radio: 'Κουμπί Επιλογής',
		textField: 'Πεδίο Κειμένου',
		textarea: 'Περιοχή Κειμένου',
		hiddenField: 'Κρυφό Πεδίο',
		button: 'Κουμπί',
		select: 'Πεδίο Επιλογής',
		imageButton: 'Κουμπί Εικόνας',
		notSet: '<δεν έχει ρυθμιστεί>',
		id: 'Id',
		name: 'Όνομα',
		langDir: 'Κατεύθυνση Κειμένου',
		langDirLtr: 'Αριστερά προς Δεξιά (LTR)',
		langDirRtl: 'Δεξιά προς Αριστερά (RTL)',
		langCode: 'Κωδικός Γλώσσας',
		longDescr: 'Αναλυτική Περιγραφή URL',
		cssClass: 'Κλάσεις Φύλλων Στυλ',
		advisoryTitle: 'Ενδεικτικός Τίτλος',
		cssStyle: 'Μορφή Κειμένου',
		ok: 'OK',
		cancel: 'Ακύρωση',
		close: 'Κλείσιμο',
		preview: 'Προεπισκόπηση',
		resize: 'Αλλαγή Μεγέθους',
		generalTab: 'Γενικά',
		advancedTab: 'Για Προχωρημένους',
		validateNumberFailed: 'Αυτή η τιμή δεν είναι αριθμός.',
		confirmNewPage: 'Οι όποιες αλλαγές στο περιεχόμενο θα χαθούν. Είσαστε σίγουροι ότι θέλετε να φορτώσετε μια νέα σελίδα;',
		confirmCancel: 'Μερικές επιλογές έχουν αλλάξει. Είσαστε σίγουροι ότι θέλετε να κλείσετε το παράθυρο διαλόγου;',
		options: 'Επιλογές',
		target: 'Προορισμός',
		targetNew: 'Νέο Παράθυρο (_blank)',
		targetTop: 'Αρχική Περιοχή (_top)',
		targetSelf: 'Ίδιο Παράθυρο (_self)',
		targetParent: 'Γονεϊκό Παράθυρο (_parent)',
		langDirLTR: 'Αριστερά προς Δεξιά (LTR)',
		langDirRTL: 'Δεξιά προς Αριστερά (RTL)',
		styles: 'Μορφή',
		cssClasses: 'Κλάσεις Φύλλων Στυλ',
		width: 'Πλάτος',
		height: 'Ύψος',
		align: 'Στοίχιση',
		alignLeft: 'Αριστερά',
		alignRight: 'Δεξιά',
		alignCenter: 'Κέντρο',
		alignTop: 'Πάνω',
		alignMiddle: 'Μέση',
		alignBottom: 'Κάτω',
		invalidValue	: 'Μη έγκυρη τιμή.',
		invalidHeight: 'Το ύψος πρέπει να είναι ένας αριθμός.',
		invalidWidth: 'Το πλάτος πρέπει να είναι ένας αριθμός.',
		invalidCssLength: 'Η τιμή που ορίζεται για το πεδίο "%1" πρέπει να είναι ένας θετικός αριθμός με ή χωρίς μια έγκυρη μονάδα μέτρησης CSS (px, %, in, cm, mm, em, ex, pt, ή pc).',
		invalidHtmlLength: 'Η τιμή που ορίζεται για το πεδίο "%1" πρέπει να είναι ένας θετικός αριθμός με ή χωρίς μια έγκυρη μονάδα μέτρησης HTML (px ή %).',
		invalidInlineStyle: 'Η τιμή για το εν σειρά στυλ πρέπει να περιέχει ένα ή περισσότερα ζεύγη με την μορφή "όνομα: τιμή" διαχωρισμένα με Ελληνικό ερωτηματικό.',
		cssLengthTooltip: 'Εισάγεται μια τιμή σε pixel ή έναν αριθμό μαζί με μια έγκυρη μονάδα μέτρησης CSS (px, %, in, cm, mm, em, ex, pt, ή pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, δεν είναι διαθέσιμο</span>'
	}
};
