/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.plugins.add( 'contextmenu', {
	requires: 'menu',
	lang: 'af,ar,bg,bn,bs,ca,cs,cy,da,de,el,en,en-au,en-ca,en-gb,eo,es,et,eu,fa,fi,fo,fr,fr-ca,gl,gu,he,hi,hr,hu,id,is,it,ja,ka,km,ko,ku,lt,lv,mk,mn,ms,nb,nl,no,pl,pt,pt-br,ro,ru,si,sk,sl,sq,sr,sr-latn,sv,th,tr,ug,uk,vi,zh,zh-cn', // %REMOVE_LINE_CORE%

	// Make sure the base class (CKEDITOR.menu) is loaded before it (#3318).
	onLoad: function() {
		/**
		 * Class replacing the non-configurable native context menu with configurable CKEditor's equivalent.
		 *
		 * @class
		 * @extends CKEDITOR.menu
		 */
		CKEDITOR.plugins.contextMenu = CKEDITOR.tools.createClass( {
			base: CKEDITOR.menu,

			/**
			 * Creates the CKEDITOR.plugins.contextMenu class instance.
			 *
			 * @constructor
			 * @param {CKEDITOR.editor} editor
			 */
			$: function( editor ) {
				this.base.call( this, editor, {
					panel: {
						className: 'cke_menu_panel',
						attributes: {
							'aria-label': editor.lang.contextmenu.options
						}
					}
				} );
			},

			proto: {
				/**
				 * Starts watching on native context menu triggers (option key, right click) on given element.
				 *
				 * @param {CKEDITOR.dom.element} element
				 * @param {Boolean} [nativeContextMenuOnCtrl] Whether to open native context menu if
				 * *Ctrl* key is hold on opening the context menu. See {@link CKEDITOR.config#browserContextMenuOnCtrl}.
				 */
				addTarget: function( element, nativeContextMenuOnCtrl ) {
					element.on( 'contextmenu', function( event ) {
						var domEvent = event.data,
							isCtrlKeyDown =
								// Safari on Windows always show 'ctrlKey' as true in 'contextmenu' event,
								// which make this property unreliable. (#4826)
								( CKEDITOR.env.webkit ? holdCtrlKey : ( CKEDITOR.env.mac ? domEvent.$.metaKey : domEvent.$.ctrlKey ) );

						if ( nativeContextMenuOnCtrl && isCtrlKeyDown )
							return;

						// Cancel the browser context menu.
						domEvent.preventDefault();

						var doc = domEvent.getTarget().getDocument(),
							offsetParent = domEvent.getTarget().getDocument().getDocumentElement(),
							fromFrame = !doc.equals( CKEDITOR.document ),
							scroll = doc.getWindow().getScrollPosition(),
							offsetX = fromFrame ? domEvent.$.clientX : domEvent.$.pageX || scroll.x + domEvent.$.clientX,
							offsetY = fromFrame ? domEvent.$.clientY : domEvent.$.pageY || scroll.y + domEvent.$.clientY;

						CKEDITOR.tools.setTimeout( function() {
							this.open( offsetParent, null, offsetX, offsetY );

							// IE needs a short while to allow selection change before opening menu. (#7908)
						}, CKEDITOR.env.ie ? 200 : 0, this );
					}, this );

					if ( CKEDITOR.env.webkit ) {
						var holdCtrlKey,
							onKeyDown = function( event ) {
								holdCtrlKey = CKEDITOR.env.mac ? event.data.$.metaKey : event.data.$.ctrlKey;
							},
							resetOnKeyUp = function() {
								holdCtrlKey = 0;
							};

						element.on( 'keydown', onKeyDown );
						element.on( 'keyup', resetOnKeyUp );
						element.on( 'contextmenu', resetOnKeyUp );
					}
				},

				/**
				 * Opens context menu in given location. See the {@link CKEDITOR.menu#show} method.
				 *
				 * @param {CKEDITOR.dom.element} offsetParent
				 * @param {Number} [corner]
				 * @param {Number} [offsetX]
				 * @param {Number} [offsetY]
				 */
				open: function( offsetParent, corner, offsetX, offsetY ) {
					this.editor.focus();
					offsetParent = offsetParent || CKEDITOR.document.getDocumentElement();

					// #9362: Force selection check to update commands' states in the new context.
					this.editor.selectionChange( 1 );

					this.show( offsetParent, corner, offsetX, offsetY );
				}
			}
		} );
	},

	beforeInit: function( editor ) {
		/**
		 * @readonly
		 * @property {CKEDITOR.plugins.contextMenu} contextMenu
		 * @member CKEDITOR.editor
		 */
		var contextMenu = editor.contextMenu = new CKEDITOR.plugins.contextMenu( editor );

		editor.on( 'contentDom', function() {
			contextMenu.addTarget( editor.editable(), editor.config.browserContextMenuOnCtrl !== false );
		} );

		editor.addCommand( 'contextMenu', {
			exec: function() {
				editor.contextMenu.open( editor.document.getBody() );
			}
		} );

		editor.setKeystroke( CKEDITOR.SHIFT + 121 /*F10*/, 'contextMenu' );
		editor.setKeystroke( CKEDITOR.CTRL + CKEDITOR.SHIFT + 121 /*F10*/, 'contextMenu' );
	}
} );

/**
 * Whether to show the browser native context menu when the *Ctrl* or
 * *Meta* (Mac) key is pressed on opening the context menu with the
 * right mouse button click or the *Menu* key.
 *
 *		config.browserContextMenuOnCtrl = false;
 *
 * @since 3.0.2
 * @cfg {Boolean} [browserContextMenuOnCtrl=true]
 * @member CKEDITOR.config
 */
