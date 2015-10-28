/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Canadian French language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'fr-ca' ] = {
	// ARIA description.
	editor: 'Éditeur de texte enrichi',
	editorPanel: 'Rich Text Editor panel', // MISSING

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Appuyez sur 0 pour de l\'aide',

		browseServer: 'Parcourir le serveur',
		url: 'URL',
		protocol: 'Protocole',
		upload: 'Envoyer',
		uploadSubmit: 'Envoyer au serveur',
		image: 'Image',
		flash: 'Animation Flash',
		form: 'Formulaire',
		checkbox: 'Case à cocher',
		radio: 'Bouton radio',
		textField: 'Champ texte',
		textarea: 'Zone de texte',
		hiddenField: 'Champ caché',
		button: 'Bouton',
		select: 'Liste déroulante',
		imageButton: 'Bouton image',
		notSet: '<Par défaut>',
		id: 'Id',
		name: 'Nom',
		langDir: 'Sens d\'écriture',
		langDirLtr: 'De gauche à droite (LTR)',
		langDirRtl: 'De droite à gauche (RTL)',
		langCode: 'Code langue',
		longDescr: 'URL de description longue',
		cssClass: 'Classes CSS',
		advisoryTitle: 'Titre',
		cssStyle: 'Style',
		ok: 'OK',
		cancel: 'Annuler',
		close: 'Fermer',
		preview: 'Aperçu',
		resize: 'Redimensionner',
		generalTab: 'Général',
		advancedTab: 'Avancé',
		validateNumberFailed: 'Cette valeur n\'est pas un nombre.',
		confirmNewPage: 'Les changements non sauvegardés seront perdus. Êtes-vous certain de vouloir charger une nouvelle page?',
		confirmCancel: 'Certaines options ont été modifiées.  Êtes-vous certain de vouloir fermer?',
		options: 'Options',
		target: 'Cible',
		targetNew: 'Nouvelle fenêtre (_blank)',
		targetTop: 'Fenêtre supérieur (_top)',
		targetSelf: 'Cette fenêtre (_self)',
		targetParent: 'Fenêtre parent (_parent)',
		langDirLTR: 'De gauche à droite (LTR)',
		langDirRTL: 'De droite à gauche (RTL)',
		styles: 'Style',
		cssClasses: 'Classe CSS',
		width: 'Largeur',
		height: 'Hauteur',
		align: 'Alignement',
		alignLeft: 'Gauche',
		alignRight: 'Droite',
		alignCenter: 'Centré',
		alignTop: 'Haut',
		alignMiddle: 'Milieu',
		alignBottom: 'Bas',
		invalidValue	: 'Valeur invalide.',
		invalidHeight: 'La hauteur doit être un nombre.',
		invalidWidth: 'La largeur doit être un nombre.',
		invalidCssLength: 'La valeur spécifiée pour le champ "%1" doit être un nombre positif avec ou sans unité de mesure CSS valide (px, %, in, cm, mm, em, ex, pt, ou pc).',
		invalidHtmlLength: 'La valeur spécifiée pour le champ "%1" doit être un nombre positif avec ou sans unité de mesure HTML valide (px ou %).',
		invalidInlineStyle: 'La valeur spécifiée pour le style intégré doit être composée d\'un ou plusieurs couples de valeur au format "nom : valeur", separés par des points-virgules.',
		cssLengthTooltip: 'Entrer un nombre pour la valeur en pixel ou un nombre avec une unité CSS valide (px, %, in, cm, mm, em, ex, pt, ou pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, indisponible</span>'
	}
};
