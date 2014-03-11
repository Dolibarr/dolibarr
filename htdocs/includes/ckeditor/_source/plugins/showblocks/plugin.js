/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

/**
 * @fileOverview The "showblocks" plugin. Enable it will make all block level
 *               elements being decorated with a border and the element name
 *               displayed on the left-right corner.
 */

( function() {
	'use strict';

	var commandDefinition = {
		readOnly: 1,
		preserveState: true,
		editorFocus: false,

		exec: function( editor ) {
			this.toggleState();
			this.refresh( editor );
		},

		refresh: function( editor ) {
			if ( editor.document ) {
				// Show blocks turns inactive after editor loses focus when in inline.
				var showBlocks = this.state == CKEDITOR.TRISTATE_ON &&
				   ( editor.elementMode != CKEDITOR.ELEMENT_MODE_INLINE ||
					   editor.focusManager.hasFocus );

				var funcName = showBlocks ? 'attachClass' : 'removeClass';
				editor.editable()[ funcName ]( 'cke_show_blocks' );
			}
		}
	};

	CKEDITOR.plugins.add( 'showblocks', {
		lang: 'af,ar,bg,bn,bs,ca,cs,cy,da,de,el,en,en-au,en-ca,en-gb,eo,es,et,eu,fa,fi,fo,fr,fr-ca,gl,gu,he,hi,hr,hu,id,is,it,ja,ka,km,ko,ku,lt,lv,mk,mn,ms,nb,nl,no,pl,pt,pt-br,ro,ru,si,sk,sl,sq,sr,sr-latn,sv,th,tr,ug,uk,vi,zh,zh-cn', // %REMOVE_LINE_CORE%
		icons: 'showblocks,showblocks-rtl', // %REMOVE_LINE_CORE%
		hidpi: true, // %REMOVE_LINE_CORE%
		onLoad: function() {
			var tags = [ 'p', 'div', 'pre', 'address', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ],
				cssStd, cssImg, cssLtr, cssRtl,
				path = CKEDITOR.getUrl( this.path ),
				// #10884 don't apply showblocks styles to non-editable elements and chosen ones.
				// IE8 does not support :not() pseudoclass, so we need to reset showblocks rather
				// than 'prevent' its application. We do that by additional rules.
				supportsNotPseudoclass = !( CKEDITOR.env.ie && CKEDITOR.env.version < 9 ),
				notDisabled = supportsNotPseudoclass ? ':not([contenteditable=false]):not(.cke_show_blocks_off)' : '',
				tag, trailing;

			cssStd = cssImg = cssLtr = cssRtl = '';

			while ( ( tag = tags.pop() ) ) {
				trailing = tags.length ? ',' : '';

				cssStd += '.cke_show_blocks ' + tag + notDisabled + trailing;
				cssLtr += '.cke_show_blocks.cke_contents_ltr ' + tag + notDisabled + trailing;
				cssRtl += '.cke_show_blocks.cke_contents_rtl ' + tag + notDisabled + trailing;
				cssImg += '.cke_show_blocks ' + tag + notDisabled + '{' +
					'background-image:url(' + path + 'images/block_' + tag + '.png)' +
				'}';
			}

			// .cke_show_blocks p { ... }
			cssStd += '{' +
				'background-repeat:no-repeat;' +
				'border:1px dotted gray;' +
				'padding-top:8px' +
			'}';

			// .cke_show_blocks.cke_contents_ltr p { ... }
			cssLtr += '{' +
				'background-position:top left;' +
				'padding-left:8px' +
			'}';

			// .cke_show_blocks.cke_contents_rtl p { ... }
			cssRtl += '{' +
				'background-position:top right;' +
				'padding-right:8px' +
			'}';

			CKEDITOR.addCss( cssStd.concat( cssImg, cssLtr, cssRtl ) );

			// [IE8] Reset showblocks styles for non-editables and chosen elements, because
			// it could not be done using :not() pseudoclass (#10884).
			if ( !supportsNotPseudoclass ) {
				CKEDITOR.addCss(
					'.cke_show_blocks [contenteditable=false],.cke_show_blocks .cke_show_blocks_off{' +
						'border:none;' +
						'padding-top:0;' +
						'background-image:none' +
					'}' +
					'.cke_show_blocks.cke_contents_rtl [contenteditable=false],.cke_show_blocks.cke_contents_rtl .cke_show_blocks_off{' +
						'padding-right:0' +
					'}' +
					'.cke_show_blocks.cke_contents_ltr [contenteditable=false],.cke_show_blocks.cke_contents_ltr .cke_show_blocks_off{' +
						'padding-left:0' +
					'}'
				);
			}
		},
		init: function( editor ) {
			if ( editor.blockless )
				return;

			var command = editor.addCommand( 'showblocks', commandDefinition );
			command.canUndo = false;

			if ( editor.config.startupOutlineBlocks )
				command.setState( CKEDITOR.TRISTATE_ON );

			editor.ui.addButton && editor.ui.addButton( 'ShowBlocks', {
				label: editor.lang.showblocks.toolbar,
				command: 'showblocks',
				toolbar: 'tools,20'
			} );

			// Refresh the command on setData.
			editor.on( 'mode', function() {
				if ( command.state != CKEDITOR.TRISTATE_DISABLED )
					command.refresh( editor );
			} );

			// Refresh the command on focus/blur in inline.
			if ( editor.elementMode == CKEDITOR.ELEMENT_MODE_INLINE ) {
				editor.on( 'focus', onFocusBlur );
				editor.on( 'blur', onFocusBlur );
			}

			// Refresh the command on setData.
			editor.on( 'contentDom', function() {
				if ( command.state != CKEDITOR.TRISTATE_DISABLED )
					command.refresh( editor );
			} );

			function onFocusBlur() {
				command.refresh( editor );
			}
		}
	} );
} )();

/**
 * Whether to automaticaly enable the show block" command when the editor loads.
 *
 *		config.startupOutlineBlocks = true;
 *
 * @cfg {Boolean} [startupOutlineBlocks=false]
 * @member CKEDITOR.config
 */
