/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * French language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'fr' ] = {
	// ARIA description.
	editor: 'Éditeur de Texte Enrichi',
	editorPanel: 'Tableau de bord de l\'éditeur de texte enrichi',

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Appuyez sur ALT-0 pour l\'aide',

		browseServer: 'Explorer le serveur',
		url: 'URL',
		protocol: 'Protocole',
		upload: 'Envoyer',
		uploadSubmit: 'Envoyer sur le serveur',
		image: 'Image',
		flash: 'Flash',
		form: 'Formulaire',
		checkbox: 'Case à cocher',
		radio: 'Bouton Radio',
		textField: 'Champ texte',
		textarea: 'Zone de texte',
		hiddenField: 'Champ caché',
		button: 'Bouton',
		select: 'Liste déroulante',
		imageButton: 'Bouton image',
		notSet: '<non défini>',
		id: 'Id',
		name: 'Nom',
		langDir: 'Sens d\'écriture',
		langDirLtr: 'Gauche à droite (LTR)',
		langDirRtl: 'Droite à gauche (RTL)',
		langCode: 'Code de langue',
		longDescr: 'URL de description longue (longdesc => malvoyant)',
		cssClass: 'Classe CSS',
		advisoryTitle: 'Description (title)',
		cssStyle: 'Style',
		ok: 'OK',
		cancel: 'Annuler',
		close: 'Fermer',
		preview: 'Aperçu',
		resize: 'Déplacer pour modifier la taille',
		generalTab: 'Général',
		advancedTab: 'Avancé',
		validateNumberFailed: 'Cette valeur n\'est pas un nombre.',
		confirmNewPage: 'Les changements non sauvegardés seront perdus. Êtes-vous sûr de vouloir charger une nouvelle page?',
		confirmCancel: 'Certaines options ont été modifiées. Êtes-vous sûr de vouloir fermer?',
		options: 'Options',
		target: 'Cible (Target)',
		targetNew: 'Nouvelle fenêtre (_blank)',
		targetTop: 'Fenêtre supérieure (_top)',
		targetSelf: 'Même fenêtre (_self)',
		targetParent: 'Fenêtre parent (_parent)',
		langDirLTR: 'Gauche à Droite (LTR)',
		langDirRTL: 'Droite à Gauche (RTL)',
		styles: 'Style',
		cssClasses: 'Classes de style',
		width: 'Largeur',
		height: 'Hauteur',
		align: 'Alignement',
		alignLeft: 'Gauche',
		alignRight: 'Droite',
		alignCenter: 'Centré',
		alignTop: 'Haut',
		alignMiddle: 'Milieu',
		alignBottom: 'Bas',
		invalidValue	: 'Valeur incorrecte.',
		invalidHeight: 'La hauteur doit être un nombre.',
		invalidWidth: 'La largeur doit être un nombre.',
		invalidCssLength: 'La valeur spécifiée pour le champ "%1" doit être un nombre positif avec ou sans unité de mesure CSS valide (px, %, in, cm, mm, em, ex, pt, ou pc).',
		invalidHtmlLength: 'La valeur spécifiée pour le champ "%1" doit être un nombre positif avec ou sans unité de mesure HTML valide (px ou %).',
		invalidInlineStyle: 'La valeur spécifiée pour le style inline doit être composée d\'un ou plusieurs couples de valeur au format "nom : valeur", separés par des points-virgules.',
		cssLengthTooltip: 'Entrer un nombre pour une valeur en pixels ou un nombre avec une unité de mesure CSS valide (px, %, in, cm, mm, em, ex, pt, ou pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, Indisponible</span>'
	}
};
