/*
 * FCKeditor - The text editor for internet
 * Copyright (C) 2003-2006 Frederico Caldeira Knabben
 * 
 * Licensed under the terms of the GNU Lesser General Public License:
 * 		http://www.opensource.org/licenses/lgpl-license.php
 * 
 * For further information visit:
 * 		http://www.fckeditor.net/
 * 
 * "Support Open Source software. What about a donation today?"
 * 
 * File Name: fckregexlib.js
 * 	These are some Regular Expresions used by the editor.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKRegexLib = new Object() ;

// This is the Regular expression used by the SetHTML method for the "&apos;" entity.
FCKRegexLib.AposEntity		= /&apos;/gi ;

// Used by the Styles combo to identify styles that can't be applied to text.
FCKRegexLib.ObjectElements	= /^(?:IMG|TABLE|TR|TD|TH|INPUT|SELECT|TEXTAREA|HR|OBJECT|A|UL|OL|LI)$/i ;

// START iCM MODIFICATIONS
// Added TABLE and CAPTION to the block elements for ENTER key handling
// Block Elements.
/*
FCKRegexLib.BlockElements	= /^(?:P|DIV|H1|H2|H3|H4|H5|H6|ADDRESS|PRE|OL|UL|LI|TD|TABLE|CAPTION)$/i ;
*/
// END iCM MODIFICATIONS
FCKRegexLib.BlockElements	= /^(?:P|DIV|H1|H2|H3|H4|H5|H6|ADDRESS|PRE|OL|UL|LI|TD|TH)$/i ;

// Elements marked as empty "Empty" in the XHTML DTD.
FCKRegexLib.EmptyElements	= /^(?:BASE|META|LINK|HR|BR|PARAM|IMG|AREA|INPUT)$/i ;

// List all named commands (commands that can be interpreted by the browser "execCommand" method.
FCKRegexLib.NamedCommands	= /^(?:Cut|Copy|Paste|Print|SelectAll|RemoveFormat|Unlink|Undo|Redo|Bold|Italic|Underline|StrikeThrough|Subscript|Superscript|JustifyLeft|JustifyCenter|JustifyRight|JustifyFull|Outdent|Indent|InsertOrderedList|InsertUnorderedList|InsertHorizontalRule)$/i ;

FCKRegexLib.BodyContents	= /([\s\S]*\<body[^\>]*\>)([\s\S]*)(\<\/body\>[\s\S]*)/i ;

// Temporary text used to solve some browser specific limitations.
FCKRegexLib.ToReplace		= /___fcktoreplace:([\w]+)/ig ;

// Get the META http-equiv attribute from the tag.
FCKRegexLib.MetaHttpEquiv	= /http-equiv\s*=\s*["']?([^"' ]+)/i ;

FCKRegexLib.HasBaseTag		= /<base /i ;

FCKRegexLib.HeadOpener		= /<head\s?[^>]*>/i ;
FCKRegexLib.HeadCloser		= /<\/head\s*>/i ;

FCKRegexLib.TableBorderClass = /\s*FCK__ShowTableBorders\s*/ ;

// Validate element names.
FCKRegexLib.ElementName = /(^[A-Za-z_:][\w.\-:]*\w$)|(^[A-Za-z_]$)/ ;

// Used in conjuction with the FCKConfig.ForceSimpleAmpersand configuration option.
FCKRegexLib.ForceSimpleAmpersand = /___FCKAmp___/g ;

// Get the closing parts of the tags with no closing tags, like <br/>... gets the "/>" part.
FCKRegexLib.SpaceNoClose = /\/>/g ;

FCKRegexLib.EmptyParagraph = /^<(p|div)>\s*<\/\1>$/i ;

FCKRegexLib.TagBody = /></ ;

// START iCM MODIFICATIONS
/*
// HTML table cell elements
FCKRegexLib.TableCellElements	= /^(?:TD|TH)$/i ;
// Block elements that can themselves contain block elements - used within the FCKTools.SplitNode
// function. I know BODY isn't really a block element but means can do the check in one hit.
FCKRegexLib.SpecialBlockElements	= /^(?:BODY|TH|TD|CAPTION)$/i ;
// Block elements that can validly contain a nested table. Ditto above for the BODY entry.
FCKRegexLib.TableBlockElements	= /^(?:BODY|DIV|LI|TD|TH)$/i ;
// List elements
FCKRegexLib.ListElements	= /^(?:OL|UL)$/i ;
// Used to remove empty tags after the split process used on ENTER key press
FCKRegexLib.EmptyElement = /<(P|DIV|H1|H2|H3|H4|H5|H6|ADDRESS|PRE|SPAN|A)[^\>]*>\s*<\/\1>/gi ;
*/
// END iCM MODIFICATIONS

FCKRegexLib.StrongOpener = /<STRONG([ \>])/gi ;
FCKRegexLib.StrongCloser = /<\/STRONG>/gi ;
FCKRegexLib.EmOpener = /<EM([ \>])/gi ;
FCKRegexLib.EmCloser = /<\/EM>/gi ;

FCKRegexLib.GeckoEntitiesMarker = /#\?-\:/g ;

FCKRegexLib.ProtectUrlsAApo		= /(<a\s.*?href=)("|')(.+?)\2/gi ;
FCKRegexLib.ProtectUrlsANoApo	= /(<a\s.*?href=)([^"'][^ >]+)/gi ;

FCKRegexLib.ProtectUrlsImgApo	= /(<img\s.*?src=)("|')(.+?)\2/gi ;
FCKRegexLib.ProtectUrlsImgNoApo	= /(<img\s.*?src=)([^"'][^ >]+)/gi ;

FCKRegexLib.Html4DocType		= /HTML 4\.0 Transitional/i ;