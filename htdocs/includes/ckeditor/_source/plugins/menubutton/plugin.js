/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.plugins.add( 'menubutton', {
	requires: 'button,menu',
	onLoad: function() {
		var clickFn = function( editor ) {
				var _ = this._,
					menu = _.menu;

				// Do nothing if this button is disabled.
				if ( _.state === CKEDITOR.TRISTATE_DISABLED )
					return;

				if ( _.on && menu ) {
					menu.hide();
					return;
				}

				_.previousState = _.state;

				// Check if we already have a menu for it, otherwise just create it.
				if ( !menu ) {
					menu = _.menu = new CKEDITOR.menu( editor, {
						panel: {
							className: 'cke_menu_panel',
							attributes: { 'aria-label': editor.lang.common.options }
						}
					} );

					menu.onHide = CKEDITOR.tools.bind( function() {
						var modes = this.command ? editor.getCommand( this.command ).modes : this.modes;
						this.setState( !modes || modes[ editor.mode ] ? _.previousState : CKEDITOR.TRISTATE_DISABLED );
						_.on = 0;
					}, this );

					// Initialize the menu items at this point.
					if ( this.onMenu )
						menu.addListener( this.onMenu );
				}

				this.setState( CKEDITOR.TRISTATE_ON );
				_.on = 1;

				// This timeout is needed to give time for the panel get focus
				// when JAWS is running. (#9842)
				setTimeout( function() {
					menu.show( CKEDITOR.document.getById( _.id ), 4 );
				}, 0 );
			};

		/**
		 * @class
		 * @extends CKEDITOR.ui.button
		 * @todo
		 */
		CKEDITOR.ui.menuButton = CKEDITOR.tools.createClass( {
			base: CKEDITOR.ui.button,

			/**
			 * Creates a menuButton class instance.
			 *
			 * @constructor
			 * @param Object definition
			 * @todo
			 */
			$: function( definition ) {
				// We don't want the panel definition in this object.
				var panelDefinition = definition.panel;
				delete definition.panel;

				this.base( definition );

				this.hasArrow = true;

				this.click = clickFn;
			},

			statics: {
				handler: {
					create: function( definition ) {
						return new CKEDITOR.ui.menuButton( definition );
					}
				}
			}
		} );
	},
	beforeInit: function( editor ) {
		editor.ui.addHandler( CKEDITOR.UI_MENUBUTTON, CKEDITOR.ui.menuButton.handler );
	}
} );

/**
 * Button UI element.
 *
 * @readonly
 * @property {String} [='menubutton']
 * @member CKEDITOR
 */
CKEDITOR.UI_MENUBUTTON = 'menubutton';
