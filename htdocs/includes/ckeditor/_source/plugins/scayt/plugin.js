/**
 * @license Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

/**
 * @fileOverview Spell Check As You Type (SCAYT).
 * Button name : Scayt.
 */

(function() {
	var commandName = 'scaytcheck',
		wscCommandName = 'checkspell',
		openPage = '';

	// Checks if a value exists in an array
	function in_array( needle, haystack ) {
		var found = 0,
			key;
		for ( key in haystack ) {
			if ( haystack[ key ] == needle ) {
				found = 1;
				break;
			}
		}
		return found;
	}

	var onEngineLoad = function() {
			var editor = this;

			var createInstance = function( ev ) // Create new instance every time Document is created.
				{
					if ( typeof plugin.instances[ editor.name ] != 'undefined' || plugin.instances[ editor.name ] != null )
						return;

					var config = editor.config;
					// Initialise Scayt instance.
					var oParams = {};
					// Get the iframe.

					if(editor.editable().$.nodeName == 'BODY')
						oParams.srcNodeRef = editor.document.getWindow().$.frameElement;
					else
						oParams.srcNodeRef = editor.editable().$;

					// syntax : AppName.AppVersion@AppRevision
					oParams.assocApp = 'CKEDITOR.' + CKEDITOR.version + '@' + CKEDITOR.revision;
					oParams.customerid = config.scayt_customerid || '1:WvF0D4-UtPqN1-43nkD4-NKvUm2-daQqk3-LmNiI-z7Ysb4-mwry24-T8YrS3-Q2tpq2';
					oParams.customDictionaryIds = config.scayt_customDictionaryIds || '';
					oParams.userDictionaryName = config.scayt_userDictionaryName || '';
					oParams.sLang = config.scayt_sLang || 'en_US';

					// Introduce SCAYT onLoad callback. (#5632)
					oParams.onLoad = function() {
						// Draw down word marker to avoid being covered by background-color style.(#5466)
						if ( !( CKEDITOR.env.ie && CKEDITOR.env.version < 8 ) )
							this.addStyle( this.selectorCss(), 'padding-bottom: 2px !important;' );

						// Call scayt_control.focus when SCAYT loaded
						// and only if editor has focus and scayt control creates at first time (#5720)
						if ( editor.editable().hasFocus && !plugin.isControlRestored( editor ) )
							this.focus();

					};

					/*
					** commented due to #23630 BCG: IE 9: SCAYT plugin for CKEditor 4: checkDirty issue
					** We redefined checkDirty of CKEDITOR already - so this is waste functionality, that
					** cause recursive calling of checkDirty in IE
					*/
					// oParams.onBeforeChange = function() {
					// 	if ( plugin.getScayt( editor ) && !editor.checkDirty() ) {
					// 		setTimeout( function() {
					// 			editor.resetDirty();
					// 		}, 0 );
					// 	}
					// };

					var scayt_custom_params = window.scayt_custom_params;
					if ( typeof scayt_custom_params == 'object' ) {
						for ( var k in scayt_custom_params )
							oParams[ k ] = scayt_custom_params[ k ];
					}
					// needs for restoring a specific scayt control settings
					if ( plugin.getControlId( editor ) )
						oParams.id = plugin.getControlId( editor );

					var scayt_control = new window.scayt( oParams );

					scayt_control.afterMarkupRemove.push( function( node ) {
						( new CKEDITOR.dom.element( node, scayt_control.document ) ).mergeSiblings();
					});

					// Copy config.
					var lastInstance = plugin.instances[ editor.name ];
					if ( lastInstance ) {
						scayt_control.sLang = lastInstance.sLang;
						scayt_control.option( lastInstance.option() );
						scayt_control.paused = lastInstance.paused;
					}

					plugin.instances[ editor.name ] = scayt_control;

					try {
						scayt_control.setDisabled( plugin.isPaused( editor ) === false );
					} catch ( e ) {}

					editor.fire( 'showScaytState' );
				};

			function bindInlineModeEvents() {
				editor.once( 'focus', createInstance );
				editor.once( 'blur', destroyInstance );
			}

			function destroyInstance( ev ) {
				var editor = ev.editor,
					scayt_instance = plugin.getScayt( editor ),
					inline_mode = ( editor.elementMode == CKEDITOR.ELEMENT_MODE_INLINE );

				// SCAYT instance might already get destroyed by mode switch (#5744).
				if ( !scayt_instance )
					return;

				plugin.setPaused( editor, !scayt_instance.disabled );
				// store a control id for restore a specific scayt control settings
				plugin.setControlId( editor, scayt_instance.id );
				scayt_instance.destroy( true );
				delete plugin.instances[ editor.name ];

				if ( inline_mode ) bindInlineModeEvents();
			}

			( editor.elementMode == CKEDITOR.ELEMENT_MODE_INLINE ) ? bindInlineModeEvents() : editor.on( 'contentDom', createInstance );

			editor.on( 'contentDomUnload', function() {
				// Remove scripts.
				var scripts = CKEDITOR.document.getElementsByTag( 'script' ),
					scaytIdRegex = /^dojoIoScript(\d+)$/i,
					scaytSrcRegex = /^https?:\/\/svc\.webspellchecker\.net\/spellcheck\/script\/ssrv\.cgi/i;

				for ( var i = 0; i < scripts.count(); i++ ) {
					var script = scripts.getItem( i ),
						id = script.getId(),
						src = script.getAttribute( 'src' );

					if ( id && src && id.match( scaytIdRegex ) && src.match( scaytSrcRegex ) )
						script.remove();
				}
			});

			editor.on( 'beforeCommandExec', function( ev ) // Disable SCAYT before Source command execution.
			{
				if ( ev.data.name == 'source'  && editor.mode == 'source' )
					plugin.markControlRestore( editor );
			});

			editor.on( 'afterCommandExec', function( ev ) {
				if ( !plugin.isScaytEnabled( editor ) )
					return;

				if ( editor.mode == 'wysiwyg' && ( ev.data.name == 'undo' || ev.data.name == 'redo' ) ) {
					plugin.getScayt( editor ).setDisabled(true);
					if (plugin.refresh_timeout) {
						window.clearTimeout(plugin.refresh_timeout);
					}
					plugin.refresh_timeout = window.setTimeout( function() {
						plugin.getScayt( editor ).setDisabled(false);

						plugin.getScayt( editor ).focus();
						plugin.getScayt( editor ).refresh();
					}, 10 );
				}
			});

			editor.on( 'destroy', destroyInstance );

			// Listen to data manipulation to reflect scayt markup.
			editor.on( 'setData', destroyInstance );

			// Reload spell-checking for current word after insertion completed.
			editor.on( 'insertElement', function() {
				var scayt_instance = plugin.getScayt( editor );
				if ( plugin.isScaytEnabled( editor ) ) {
					// Unlock the selection before reload, SCAYT will take
					// care selection update.
					if ( CKEDITOR.env.ie )
						editor.getSelection().unlock( true );

					// Return focus to the editor and refresh SCAYT markup (#5573).
					window.setTimeout( function() {
						scayt_instance.focus();
						scayt_instance.refresh();
					}, 10 );
				}
			}, this, null, 50 );

			editor.on( 'insertHtml', function() {
				var scayt_instance = plugin.getScayt( editor );
				if ( plugin.isScaytEnabled( editor ) ) {
					// Unlock the selection before reload, SCAYT will take
					// care selection update.
					if ( CKEDITOR.env.ie )
						editor.getSelection().unlock( true );

					// Return focus to the editor (#5573)
					// Refresh SCAYT markup
					window.setTimeout( function() {
						scayt_instance.focus();
						scayt_instance.refresh();
					}, 10 );
				}
			}, this, null, 50 );

			editor.on( 'scaytDialog', function( ev ) // Communication with dialog.
			{
				ev.data.djConfig = window.djConfig;
				ev.data.scayt_control = plugin.getScayt( editor );
				ev.data.tab = openPage;
				ev.data.scayt = window.scayt;
			});

			var dataProcessor = editor.dataProcessor,
				htmlFilter = dataProcessor && dataProcessor.htmlFilter;

			if ( htmlFilter ) {
				htmlFilter.addRules({
					elements: {
						span: function( element ) {
							if ( element.attributes[ 'data-scayt_word' ] && element.attributes[ 'data-scaytid' ] ) {
								delete element.name; // Write children, but don't write this node.
								return element;
							}
						}
					}
				});
			}

			// Override Image.equals method avoid CK snapshot module to add SCAYT markup to snapshots. (#5546)
			var undoImagePrototype = CKEDITOR.plugins.undo.Image.prototype;

			// add backword compatibility for CKEDITOR 4.2. method equals was repleced on other method
			var equalsContentMethodName = (typeof undoImagePrototype.equalsContent == "function") ? 'equalsContent' : 'equals';

			undoImagePrototype[equalsContentMethodName] = CKEDITOR.tools.override( undoImagePrototype[equalsContentMethodName], function( org ) {
				return function( otherImage ) {
					//
					var thisContents = this.contents,
						otherContents = otherImage.contents;
					var scayt_instance = plugin.getScayt( this.editor );
					// Making the comparison based on content without SCAYT word markers.
					if ( scayt_instance && plugin.isScaytReady( this.editor ) ) {
						// scayt::reset might return value undefined. (#5742)
						this.contents = scayt_instance.reset( thisContents ) || '';
						otherImage.contents = scayt_instance.reset( otherContents ) || '';
					}

					var retval = org.apply( this, arguments );

					this.contents = thisContents;
					otherImage.contents = otherContents;
					return retval;
				};
			});

			// added due to #23630 (checkDirty wrong behaviour on SCAYT load)
			var editorPrototype = CKEDITOR.editor.prototype;
			// Override editor.checkDirty method avoid CK checkDirty functionality to fix SCAYT issues with incorrect checkDirty behavior.
			editorPrototype.checkDirty = CKEDITOR.tools.override(editorPrototype.checkDirty, function( org ){
				return function() {
					var retval = null,
						scayt_instance = plugin.getScayt( this );

					if ( scayt_instance && plugin.isScaytReady( this ) ) {
						var currentData = scayt_instance.reset(this.getSnapshot());
						var prevData = scayt_instance.reset(this._.previousValue);
						retval = ( currentData !== prevData );
					} else {
						retval = org.apply(this);
					}

					return retval;
				}
			});

		   if(editor.document && (editor.elementMode != CKEDITOR.ELEMENT_MODE_INLINE || editor.focusManager.hasFocus)){
				createInstance();
		   }
		};

	CKEDITOR.plugins.scayt = {
		engineLoaded: false,
		instances: {},
		// Data storage for SCAYT control, based on editor instances
		controlInfo: {},
		setControlInfo: function( editor, o ) {
			if ( editor && editor.name && typeof( this.controlInfo[ editor.name ] ) != 'object' )
				this.controlInfo[ editor.name ] = {};

			for ( var infoOpt in o )
				this.controlInfo[ editor.name ][ infoOpt ] = o[ infoOpt ];
		},
		isControlRestored: function( editor ) {
			if ( editor && editor.name && this.controlInfo[ editor.name ] ) {
				return this.controlInfo[ editor.name ].restored;
			}
			return false;
		},
		markControlRestore: function( editor ) {
			this.setControlInfo( editor, { restored: true } );
		},
		setControlId: function( editor, id ) {
			this.setControlInfo( editor, { id: id } );
		},
		getControlId: function( editor ) {
			if ( editor && editor.name && this.controlInfo[ editor.name ] && this.controlInfo[ editor.name ].id ) {
				return this.controlInfo[ editor.name ].id;
			}
			return null;
		},
		setPaused: function( editor, bool ) {
			this.setControlInfo( editor, { paused: bool } );
		},
		isPaused: function( editor ) {
			if ( editor && editor.name && this.controlInfo[ editor.name ] ) {
				return this.controlInfo[ editor.name ].paused;
			}
			return undefined;
		},
		getScayt: function( editor ) {
			return this.instances[ editor.name ];
		},
		isScaytReady: function( editor ) {
			return this.engineLoaded === true && 'undefined' !== typeof window.scayt && this.getScayt( editor );
		},
		isScaytEnabled: function( editor ) {
			var scayt_instance = this.getScayt( editor );
			return ( scayt_instance ) ? scayt_instance.disabled === false : false;
		},
		getUiTabs: function( editor ) {
			var uiTabs = [];

			// read UI tabs value from config
			var configUiTabs = editor.config.scayt_uiTabs || "1,1,1";

			// convert string to array
			configUiTabs = configUiTabs.split( ',' );

			// "About us" should be always shown for standard config
			configUiTabs[ 3 ] = "1";

			for ( var i = 0; i < 4; i++ ) {
				uiTabs[ i ] = ( typeof window.scayt != "undefined" && typeof window.scayt.uiTags != "undefined" ) ? ( parseInt( configUiTabs[ i ], 10 ) && window.scayt.uiTags[ i ] ) : parseInt( configUiTabs[ i ], 10 );
			}

			if(typeof editor.plugins.wsc == "object")
				uiTabs.push(1);
			else
				uiTabs.push(0);

			return uiTabs;
		},
		loadEngine: function( editor ) {
			// SCAYT doesn't work with Firefox2, Opera and AIR.
			if ( CKEDITOR.env.gecko && CKEDITOR.env.version < 10900 || CKEDITOR.env.opera || CKEDITOR.env.air )
				return editor.fire( 'showScaytState' );

			if ( this.engineLoaded === true )
				return onEngineLoad.apply( editor ); // Add new instance.
			else if ( this.engineLoaded == -1 ) // We are waiting.
			return CKEDITOR.on( 'scaytReady', function() {
				onEngineLoad.apply( editor );
			}); // Use function(){} to avoid rejection as duplicate.

			CKEDITOR.on( 'scaytReady', onEngineLoad, editor );
			CKEDITOR.on( 'scaytReady', function() {
				this.engineLoaded = true;
			}, this, null, 0 ); // First to run.

			this.engineLoaded = -1; // Loading in progress.

			// compose scayt url
			var protocol = document.location.protocol;
			// Default to 'http' for unknown.
			protocol = protocol.search( /https?:/ ) != -1 ? protocol : 'http:';
			var baseUrl = 'svc.webspellchecker.net/scayt26/loader__base.js';

			var scaytUrl = editor.config.scayt_srcUrl || ( protocol + '//' + baseUrl );
			var scaytConfigBaseUrl = plugin.parseUrl( scaytUrl ).path + '/';

			if ( window.scayt == undefined ) {
				CKEDITOR._djScaytConfig = {
					baseUrl: scaytConfigBaseUrl,
					addOnLoad: [
						function()
						{
						CKEDITOR.fireOnce( 'scaytReady' );
					}
					],
					isDebug: false
				};
				// Append javascript code.
				CKEDITOR.document.getHead().append( CKEDITOR.document.createElement( 'script', {
					attributes: {
						type: 'text/javascript',
						async: 'true',
						src: scaytUrl
					}
				}));
			} else
				CKEDITOR.fireOnce( 'scaytReady' );

			return null;
		},
		parseUrl: function( data ) {
			var match;
			if ( data.match && ( match = data.match( /(.*)[\/\\](.*?\.\w+)$/ ) ) )
				return { path: match[ 1 ], file: match[ 2 ] };
			else
				return data;
		}
	};

	var plugin = CKEDITOR.plugins.scayt;

	// Context menu constructing.
	var addButtonCommand = function( editor, buttonName, buttonLabel, commandName, command, menugroup, menuOrder ) {
			editor.addCommand( commandName, command );

			// If the "menu" plugin is loaded, register the menu item.
			editor.addMenuItem( commandName, {
				label: buttonLabel,
				command: commandName,
				group: menugroup,
				order: menuOrder
			});
		};

	var commandDefinition = {
		preserveState: true,
		editorFocus: false,
		canUndo: false,

		exec: function( editor ) {
			if ( plugin.isScaytReady( editor ) ) {
				var isEnabled = plugin.isScaytEnabled( editor );

				this.setState( isEnabled ? CKEDITOR.TRISTATE_OFF : CKEDITOR.TRISTATE_ON );

				var scayt_control = plugin.getScayt( editor );
				// the place where the status of editor focus should be restored
				// after there will be ability to store its state before SCAYT button click
				// if (storedFocusState is focused )
				//   scayt_control.focus();
				//
				// now focus is set certainly
				scayt_control.focus();
				scayt_control.setDisabled( isEnabled );
			} else if ( !editor.config.scayt_autoStartup && plugin.engineLoaded >= 0 ) // Load first time
			{
				editor.focus();
				this.setState( CKEDITOR.TRISTATE_DISABLED );
				plugin.loadEngine( editor );
			}
		}
	};

	// Add scayt plugin.
	CKEDITOR.plugins.add( 'scayt', {
		requires: 'menubutton,dialog',
		lang: 'af,ar,bg,bn,bs,ca,cs,cy,da,de,el,en-au,en-ca,en-gb,en,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,ka,km,ko,lt,lv,mk,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,ug,uk,vi,zh-cn,zh', // %REMOVE_LINE_CORE%
		icons: 'scayt', // %REMOVE_LINE_CORE%
		hidpi: true, // %REMOVE_LINE_CORE%

		beforeInit: function( editor ) {

			var items_order = editor.config.scayt_contextMenuItemsOrder || 'suggest|moresuggest|control',
				items_order_str = "";

			items_order = items_order.split( '|' );

			if ( items_order && items_order.length ) {
				for ( var pos = 0; pos < items_order.length; pos++ )
					items_order_str += 'scayt_' + items_order[ pos ] + ( items_order.length != parseInt( pos, 10 ) + 1 ? ',' : '' );
			}

			// Put it on top of all context menu items (#5717)
			editor.config.menu_groups = items_order_str + ',' + editor.config.menu_groups;
		},

		checkEnvironment: function(){
			return ( CKEDITOR.env.opera || CKEDITOR.env.air ) ? 0 : 1;
		},

		init: function( editor ) {

			var self = this;
			// Delete span[data-scaytid] when text pasting in editor (#6921)
			var dataFilter = editor.dataProcessor && editor.dataProcessor.dataFilter;
			var dataFilterRules = {
				elements: {
					span: function( element ) {
						var attrs = element.attributes;
						if ( attrs && attrs[ 'data-scaytid' ] )
							delete element.name;
					}
				}
			};
			dataFilter && dataFilter.addRules( dataFilterRules );

			var moreSuggestions = {},
				mainSuggestions = {};

			// Scayt command.
			var command = editor.addCommand( commandName, commandDefinition );

			// Add Options dialog.
			CKEDITOR.dialog.add( commandName, CKEDITOR.getUrl( this.path + 'dialogs/options.js' ) );

			var uiTabs = plugin.getUiTabs( editor );

			var menuGroup = 'scaytButton';
			editor.addMenuGroup( menuGroup );
			// Temporary hack - place scayt's groups at the beginning of the context menu.
			editor.addMenuGroup( 'scayt_suggest', -10 );
			editor.addMenuGroup( 'scayt_moresuggest', -9 );
			editor.addMenuGroup( 'scayt_control', -8 );

			// combine menu items to render
			var uiMenuItems = {};

			var lang = editor.lang.scayt;

			// always added
			uiMenuItems.scaytToggle = {
				label: lang.enable,
				command: commandName,
				group: menuGroup
			};

			if ( uiTabs[ 0 ] == 1 )
				uiMenuItems.scaytOptions = {
				label: lang.options,
				group: menuGroup,
				onClick: function() {
					openPage = 'options';
					editor.openDialog( commandName );
				}
			};

			if ( uiTabs[ 1 ] == 1 )
				uiMenuItems.scaytLangs = {
				label: lang.langs,
				group: menuGroup,
				onClick: function() {
					openPage = 'langs';
					editor.openDialog( commandName );
				}
			};
			if ( uiTabs[ 2 ] == 1 )
				uiMenuItems.scaytDict = {
				label: lang.dictionariesTab,
				group: menuGroup,
				onClick: function() {
					openPage = 'dictionaries';
					editor.openDialog( commandName );
				}
			};
			// always added
			uiMenuItems.scaytAbout = {
				label: editor.lang.scayt.about,
				group: menuGroup,
				onClick: function() {
					openPage = 'about';
					editor.openDialog( commandName );
				}
			};

			if ( uiTabs[4] == 1 )
				uiMenuItems.scaytWSC =	{
						label : editor.lang.wsc.toolbar,
						group : menuGroup,
						command : wscCommandName
				};

			editor.addMenuItems( uiMenuItems );

			editor.ui.add( 'Scayt', CKEDITOR.UI_MENUBUTTON, {
				label: lang.title,
				title: CKEDITOR.env.opera ? lang.opera_title : lang.title,
				modes: { wysiwyg: self.checkEnvironment() },
				toolbar: 'spellchecker,20',
				onRender: function() {
					command.on( 'state', function() {
						this.setState( command.state );
					}, this );
				},
				onMenu: function() {
					var isEnabled = plugin.isScaytEnabled( editor );

					editor.getMenuItem( 'scaytToggle' ).label = lang[ isEnabled ? 'disable' : 'enable' ];

					var uiTabs = plugin.getUiTabs( editor );

					return {
						scaytToggle: CKEDITOR.TRISTATE_OFF,
						scaytOptions: isEnabled && uiTabs[ 0 ] ? CKEDITOR.TRISTATE_OFF : CKEDITOR.TRISTATE_DISABLED,
						scaytLangs: isEnabled && uiTabs[ 1 ] ? CKEDITOR.TRISTATE_OFF : CKEDITOR.TRISTATE_DISABLED,
						scaytDict: isEnabled && uiTabs[ 2 ] ? CKEDITOR.TRISTATE_OFF : CKEDITOR.TRISTATE_DISABLED,
						scaytAbout: isEnabled && uiTabs[ 3 ] ? CKEDITOR.TRISTATE_OFF : CKEDITOR.TRISTATE_DISABLED,
						scaytWSC: uiTabs[4] ? CKEDITOR.TRISTATE_OFF : CKEDITOR.TRISTATE_DISABLED
					};
				}
			});

			// If the "contextmenu" plugin is loaded, register the listeners.
			if ( editor.contextMenu && editor.addMenuItems ) {
				editor.contextMenu.addListener( function( element, selection ) {
					if ( !plugin.isScaytEnabled( editor ) || selection.getRanges()[ 0 ].checkReadOnly() )
						return null;

					var scayt_control = plugin.getScayt( editor ),
						node = scayt_control.getScaytNode();

					if ( !node )
						return null;

					var word = scayt_control.getWord( node );

					if ( !word )
						return null;

					var sLang = scayt_control.getLang(),
						_r = {}, contextCommands = editor.config.scayt_contextCommands || 'all',
						items_suggestion = window.scayt.getSuggestion( word, sLang );

					contextCommands = contextCommands.split( '|' );

					// Remove unused commands and menuitems

					for ( var m in moreSuggestions ) {
						delete editor._.menuItems[ m ];
						delete editor.commands[ m ];
					}
					for ( m in mainSuggestions ) {
						delete editor._.menuItems[ m ];
						delete editor.commands[ m ];
					}

					if ( !items_suggestion || !items_suggestion.length ){
							var no_sugg = {
								exec: function() {}
							};
							addButtonCommand( editor, 'no_sugg', lang.noSuggestions, 'scayt_no_sugg', no_sugg, 'scayt_control', 1, true );
							mainSuggestions[ 'scayt_no_sugg' ] = CKEDITOR.TRISTATE_OFF;
					}else{
						// Reset items.
						moreSuggestions = {};
						mainSuggestions = {};

						var moreSuggestionsUnable = editor.config.scayt_moreSuggestions || 'on';
						var moreSuggestionsUnableAdded = false;

						var maxSuggestions = editor.config.scayt_maxSuggestions;
						( typeof maxSuggestions != 'number' ) && ( maxSuggestions = 5 );
						!maxSuggestions && ( maxSuggestions = items_suggestion.length );

						for ( var i = 0, l = items_suggestion.length; i < l; i += 1 ) {
							var commandName = 'scayt_suggestion_' + items_suggestion[ i ].replace( ' ', '_' );
							var exec = (function( el, s ) {
								return {
									exec: function() {
										scayt_control.replace( el, s );
									}
								};
							})( node, items_suggestion[ i ] );

							if ( i < maxSuggestions ) {
								addButtonCommand( editor, 'button_' + commandName, items_suggestion[ i ], commandName, exec, 'scayt_suggest', i + 1 );
								_r[ commandName ] = CKEDITOR.TRISTATE_OFF;
								mainSuggestions[ commandName ] = CKEDITOR.TRISTATE_OFF;
							} else if ( moreSuggestionsUnable == 'on' ) {
								addButtonCommand( editor, 'button_' + commandName, items_suggestion[ i ], commandName, exec, 'scayt_moresuggest', i + 1 );
								moreSuggestions[ commandName ] = CKEDITOR.TRISTATE_OFF;
								moreSuggestionsUnableAdded = true;
							}
						}

						if ( moreSuggestionsUnableAdded ) {
							// Register the More suggestions group;
							editor.addMenuItem( 'scayt_moresuggest', {
								label: lang.moreSuggestions,
								group: 'scayt_moresuggest',
								order: 10,
								getItems: function() {
									return moreSuggestions;
								}
							});
							mainSuggestions[ 'scayt_moresuggest' ] = CKEDITOR.TRISTATE_OFF;
						}
					}

					if ( in_array( 'all', contextCommands ) || in_array( 'ignore', contextCommands ) ) {
						var ignore_command = {
							exec: function() {
								scayt_control.ignore( node );
							}
						};
						addButtonCommand( editor, 'ignore', lang.ignore, 'scayt_ignore', ignore_command, 'scayt_control', 2 );
						mainSuggestions[ 'scayt_ignore' ] = CKEDITOR.TRISTATE_OFF;
					}

					if ( in_array( 'all', contextCommands ) || in_array( 'ignoreall', contextCommands ) ) {
						var ignore_all_command = {
							exec: function() {
								scayt_control.ignoreAll( node );
							}
						};
						addButtonCommand( editor, 'ignore_all', lang.ignoreAll, 'scayt_ignore_all', ignore_all_command, 'scayt_control', 3 );
						mainSuggestions[ 'scayt_ignore_all' ] = CKEDITOR.TRISTATE_OFF;
					}

					if ( in_array( 'all', contextCommands ) || in_array( 'add', contextCommands ) ) {
						var addword_command = {
							exec: function() {
								window.scayt.addWordToUserDictionary( node );
							}
						};
						addButtonCommand( editor, 'add_word', lang.addWord, 'scayt_add_word', addword_command, 'scayt_control', 4 );
						mainSuggestions[ 'scayt_add_word' ] = CKEDITOR.TRISTATE_OFF;
					}

					if ( scayt_control.fireOnContextMenu )
						scayt_control.fireOnContextMenu( editor );

					return mainSuggestions;
				});
			}

			var showInitialState = function( evt ) {
					evt.removeListener();
					if ( CKEDITOR.env.opera || CKEDITOR.env.air )
						command.setState( CKEDITOR.TRISTATE_DISABLED );
					else
						command.setState( plugin.isScaytEnabled( editor ) ? CKEDITOR.TRISTATE_ON : CKEDITOR.TRISTATE_OFF );
				};

			editor.on( 'showScaytState', showInitialState );
			editor.on( 'instanceReady', showInitialState );

			// Start plugin
			if ( editor.config.scayt_autoStartup ) {
				editor.on( 'instanceReady', function() {
					plugin.loadEngine( editor );
				});
			}
		},

		afterInit: function( editor ) {

			// Prevent word marker line from displaying in elements path and been removed when cleaning format. (#3570) (#4125)
			var elementsPathFilters,
				scaytFilter = function( element ) {
					if ( element.hasAttribute( 'data-scaytid' ) )
						return false;
				};

			if ( editor._.elementsPath && ( elementsPathFilters = editor._.elementsPath.filters ) )
				elementsPathFilters.push( scaytFilter );

			editor.addRemoveFormatFilter && editor.addRemoveFormatFilter( scaytFilter );

		}
	});
})();

/**
 * If enabled (set to `true`), turns on SCAYT automatically
 * after loading the editor.
 *
 *		config.scayt_autoStartup = true;
 *
 * @cfg {Boolean} [scayt_autoStartup=false]
 * @member CKEDITOR.config
 */

/**
 * Defines the number of SCAYT suggestions to show in the main context menu.
 * Possible values are:
 *
 * * `0` (zero) &ndash; All suggestions are displayed in the main context menu.
 * * Positive number &ndash; The maximum number of suggestions to show in the context
 *     menu. Other entries will be shown in the "More Suggestions" sub-menu.
 * * Negative number &ndash; No suggestions are shown in the main context menu. All
 *     entries will be listed in the the "Suggestions" sub-menu.
 *
 * Examples:
 *
 *		// Display only three suggestions in the main context menu.
 *		config.scayt_maxSuggestions = 3;
 *
 *		// Do not show the suggestions directly.
 *		config.scayt_maxSuggestions = -1;
 *
 * @cfg {Number} [scayt_maxSuggestions=5]
 * @member CKEDITOR.config
 */

/**
 * Sets the customer ID for SCAYT. Required for migration from free,
 * ad-supported version to paid, ad-free version.
 *
 *		// Load SCAYT using my customer ID.
 *		config.scayt_customerid  = 'your-encrypted-customer-id';
 *
 * @cfg {String} [scayt_customerid='']
 * @example
 * @member CKEDITOR.config
 */

/**
 * Enables/disables the "More Suggestions" sub-menu in the context menu.
 * Possible values are `'on'` and `'off'`.
 *
 *		// Disables the "More Suggestions" sub-menu.
 *		config.scayt_moreSuggestions = 'off';
 *
 * @cfg {String} [scayt_moreSuggestions='on']
 * @member CKEDITOR.config
 */

/**
 * Customizes the display of SCAYT context menu commands ("Add Word", "Ignore"
 * and "Ignore All"). This must be a string with one or more of the following
 * words separated by a pipe character (`'|'`):
 *
 * * `off` &ndash; disables all options.
 * * `all` &ndash; enables all options.
 * * `ignore` &ndash; enables the "Ignore" option.
 * * `ignoreall` &ndash; enables the "Ignore All" option.
 * * `add` &ndash; enables the "Add Word" option.
 *
 * Example:
 *
 *		// Show only "Add Word" and "Ignore All" in the context menu.
 *		config.scayt_contextCommands = 'add|ignoreall';
 *
 * @cfg {String} [scayt_contextCommands='all']
 * @member CKEDITOR.config
 */

/**
 * Sets the default spell checking language for SCAYT. Possible values are:
 * `'en_US'`, `'en_GB'`, `'pt_BR'`, `'da_DK'`,
 * `'nl_NL'`, `'en_CA'`, `'fi_FI'`, `'fr_FR'`,
 * `'fr_CA'`, `'de_DE'`, `'el_GR'`, `'it_IT'`,
 * `'nb_NO'`, `'pt_PT'`, `'es_ES'`, `'sv_SE'`.
 *
 *		// Sets SCAYT to German.
 *		config.scayt_sLang = 'de_DE';
 *
 * @cfg {String} [scayt_sLang='en_US']
 * @member CKEDITOR.config
 */

/**
 * Sets the visibility of particular tabs in the SCAYT dialog window and toolbar
 * button. This setting must contain a `1` (enabled) or `0`
 * (disabled) value for each of the following entries, in this precise order,
 * separated by a comma (`','`): `'Options'`, `'Languages'`, and `'Dictionary'`.
 *
 *		// Hides the "Languages" tab.
 *		config.scayt_uiTabs = '1,0,1';
 *
 * @cfg {String} [scayt_uiTabs='1,1,1']
 * @member CKEDITOR.config
 */


/**
 * Sets the URL to SCAYT core. Required to switch to the licensed version of SCAYT application.
 *
 * Further details available at [http://wiki.webspellchecker.net/doku.php?id=migration:hosredfreetolicensedck](http://wiki.webspellchecker.net/doku.php?id=migration:hosredfreetolicensedck)
 *
 *		config.scayt_srcUrl = "http://my-host/spellcheck/lf/scayt/scayt.js";
 *
 * @cfg {String} [scayt_srcUrl='']
 * @member CKEDITOR.config
 */

/**
 * Links SCAYT to custom dictionaries. This is a string containing dictionary IDs
 * separared by commas (`','`). Available only for the licensed version.
 *
 * Further details at [http://wiki.webspellchecker.net/doku.php?id=installationandconfiguration:customdictionaries:licensed](http://wiki.webspellchecker.net/doku.php?id=installationandconfiguration:customdictionaries:licensed)
 *
 *		config.scayt_customDictionaryIds = '3021,3456,3478"';
 *
 * @cfg {String} [scayt_customDictionaryIds='']
 * @member CKEDITOR.config
 */

/**
 * Makes it possible to activate a custom dictionary in SCAYT. The user
 * dictionary name must be used. Available only for the licensed version.
 *
 *		config.scayt_userDictionaryName = 'MyDictionary';
 *
 * @cfg {String} [scayt_userDictionaryName='']
 * @member CKEDITOR.config
 */

/**
 * Defines the order SCAYT context menu items by groups.
 * This must be a string with one or more of the following
 * words separated by a pipe character (`'|'`):
 *
 * * `suggest` &ndash; main suggestion word list,
 * * `moresuggest` &ndash; more suggestions word list,
 * * `control` &ndash; SCAYT commands, such as "Ignore" and "Add Word".
 *
 * Example:
 *
 *		config.scayt_contextMenuItemsOrder = 'moresuggest|control|suggest';
 *
 * @cfg {String} [scayt_contextMenuItemsOrder='suggest|moresuggest|control']
 * @member CKEDITOR.config
 */
