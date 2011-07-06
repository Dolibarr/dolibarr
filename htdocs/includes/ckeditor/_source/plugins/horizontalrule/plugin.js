/*
Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

/**
 * @file Horizontal Rule plugin.
 */

(function()
{
	var horizontalruleCmd =
	{
		canUndo : false,    // The undo snapshot will be handled by 'insertElement'.
		exec : function( editor )
		{
			var hr = editor.document.createElement( 'hr' ),
				range = new CKEDITOR.dom.range( editor.document );

			editor.insertElement( hr );

			// If there's nothing or a non-editable block followed by, establish a new paragraph
			// to make sure cursor is not trapped.
			range.moveToPosition( hr, CKEDITOR.POSITION_AFTER_END );
			var next = hr.getNext();
			if ( !next || next.type == CKEDITOR.NODE_ELEMENT && !next.isEditable() )
				range.fixBlock( true, editor.config.enterMode == CKEDITOR.ENTER_DIV ? 'div' : 'p'  );

			range.select();
		}
	};

	var pluginName = 'horizontalrule';

	// Register a plugin named "horizontalrule".
	CKEDITOR.plugins.add( pluginName,
	{
		init : function( editor )
		{
			editor.addCommand( pluginName, horizontalruleCmd );
			editor.ui.addButton( 'HorizontalRule',
				{
					label : editor.lang.horizontalrule,
					command : pluginName
				});
		}
	});
})();
