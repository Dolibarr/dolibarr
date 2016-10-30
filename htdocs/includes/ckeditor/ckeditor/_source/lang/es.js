/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview Defines the {@link CKEDITOR.lang} object, for the
 * Spanish language.
 */

/**#@+
   @type String
   @example
*/

/**
 * Contains the dictionary of language entries.
 * @namespace
 */
CKEDITOR.lang[ 'es' ] = {
	// ARIA description.
	editor: 'Editor de texto enriquecido',
	editorPanel: 'Panel del Editor de Texto Enriquecido',

	// Common messages and labels.
	common: {
		// Screenreader titles. Please note that screenreaders are not always capable
		// of reading non-English words. So be careful while translating it.
		editorHelp: 'Pulse ALT 0 para ayuda',

		browseServer: 'Ver Servidor',
		url: 'URL',
		protocol: 'Protocolo',
		upload: 'Cargar',
		uploadSubmit: 'Enviar al Servidor',
		image: 'Imagen',
		flash: 'Flash',
		form: 'Formulario',
		checkbox: 'Casilla de Verificación',
		radio: 'Botones de Radio',
		textField: 'Campo de Texto',
		textarea: 'Area de Texto',
		hiddenField: 'Campo Oculto',
		button: 'Botón',
		select: 'Campo de Selección',
		imageButton: 'Botón Imagen',
		notSet: '<No definido>',
		id: 'Id',
		name: 'Nombre',
		langDir: 'Orientación',
		langDirLtr: 'Izquierda a Derecha (LTR)',
		langDirRtl: 'Derecha a Izquierda (RTL)',
		langCode: 'Cód. de idioma',
		longDescr: 'Descripción larga URL',
		cssClass: 'Clases de hojas de estilo',
		advisoryTitle: 'Título',
		cssStyle: 'Estilo',
		ok: 'Aceptar',
		cancel: 'Cancelar',
		close: 'Cerrar',
		preview: 'Previsualización',
		resize: 'Arrastre para redimensionar',
		generalTab: 'General',
		advancedTab: 'Avanzado',
		validateNumberFailed: 'El valor no es un número.',
		confirmNewPage: 'Cualquier cambio que no se haya guardado se perderá.\r\n¿Está seguro de querer crear una nueva página?',
		confirmCancel: 'Algunas de las opciones se han cambiado.\r\n¿Está seguro de querer cerrar el diálogo?',
		options: 'Opciones',
		target: 'Destino',
		targetNew: 'Nueva ventana (_blank)',
		targetTop: 'Ventana principal (_top)',
		targetSelf: 'Misma ventana (_self)',
		targetParent: 'Ventana padre (_parent)',
		langDirLTR: 'Izquierda a derecha (LTR)',
		langDirRTL: 'Derecha a izquierda (RTL)',
		styles: 'Estilos',
		cssClasses: 'Clase de la hoja de estilos',
		width: 'Anchura',
		height: 'Altura',
		align: 'Alineación',
		alignLeft: 'Izquierda',
		alignRight: 'Derecha',
		alignCenter: 'Centrado',
		alignTop: 'Tope',
		alignMiddle: 'Centro',
		alignBottom: 'Pie',
		invalidValue	: 'Valor no válido',
		invalidHeight: 'Altura debe ser un número.',
		invalidWidth: 'Anchura debe ser un número.',
		invalidCssLength: 'El valor especificado para el campo "%1" debe ser un número positivo, incluyendo optionalmente una unidad de medida CSS válida (px, %, in, cm, mm, em, ex, pt, o pc).',
		invalidHtmlLength: 'El valor especificado para el campo "%1" debe ser un número positivo, incluyendo optionalmente una unidad de medida HTML válida (px o %).',
		invalidInlineStyle: 'El valor especificado para el estilo debe consistir en uno o más pares con el formato "nombre: valor", separados por punto y coma.',
		cssLengthTooltip: 'Introduca un número para el valor en pixels o un número con una unidad de medida CSS válida (px, %, in, cm, mm, em, ex, pt, o pc).',

		// Put the voice-only part of the label in the span.
		unavailable: '%1<span class="cke_accessibility">, no disponible</span>'
	}
};
