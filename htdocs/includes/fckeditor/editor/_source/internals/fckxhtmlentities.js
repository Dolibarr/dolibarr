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
 * File Name: fckxhtmlentities.js
 * 	This file define the HTML entities handled by the editor.
 * 
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */

var FCKXHtmlEntities = new Object() ;

FCKXHtmlEntities.Initialize = function()
{
	if ( FCKXHtmlEntities.Entities )
		return ;

	if ( FCKConfig.ProcessHTMLEntities )
	{
		FCKXHtmlEntities.Entities = {
			// Latin-1 Entities
			' ':'nbsp',
			'¡':'iexcl',
			'¢':'cent',
			'£':'pound',
			'¤':'curren',
			'¥':'yen',
			'¦':'brvbar',
			'§':'sect',
			'¨':'uml',
			'©':'copy',
			'ª':'ordf',
			'«':'laquo',
			'¬':'not',
			'­':'shy',
			'®':'reg',
			'¯':'macr',
			'°':'deg',
			'±':'plusmn',
			'²':'sup2',
			'³':'sup3',
			'´':'acute',
			'µ':'micro',
			'¶':'para',
			'·':'middot',
			'¸':'cedil',
			'¹':'sup1',
			'º':'ordm',
			'»':'raquo',
			'¼':'frac14',
			'½':'frac12',
			'¾':'frac34',
			'¿':'iquest',
			'×':'times',
			'÷':'divide',

			// Symbols and Greek Letters 

			'ƒ':'fnof',
			'•':'bull',
			'…':'hellip',
			'′':'prime',
			'″':'Prime',
			'‾':'oline',
			'⁄':'frasl',
			'℘':'weierp',
			'ℑ':'image',
			'ℜ':'real',
			'™':'trade',
			'ℵ':'alefsym',
			'←':'larr',
			'↑':'uarr',
			'→':'rarr',
			'↓':'darr',
			'↔':'harr',
			'↵':'crarr',
			'⇐':'lArr',
			'⇑':'uArr',
			'⇒':'rArr',
			'⇓':'dArr',
			'⇔':'hArr',
			'∀':'forall',
			'∂':'part',
			'∃':'exist',
			'∅':'empty',
			'∇':'nabla',
			'∈':'isin',
			'∉':'notin',
			'∋':'ni',
			'∏':'prod',
			'∑':'sum',
			'−':'minus',
			'∗':'lowast',
			'√':'radic',
			'∝':'prop',
			'∞':'infin',
			'∠':'ang',
			'∧':'and',
			'∨':'or',
			'∩':'cap',
			'∪':'cup',
			'∫':'int',
			'∴':'there4',
			'∼':'sim',
			'≅':'cong',
			'≈':'asymp',
			'≠':'ne',
			'≡':'equiv',
			'≤':'le',
			'≥':'ge',
			'⊂':'sub',
			'⊃':'sup',
			'⊄':'nsub',
			'⊆':'sube',
			'⊇':'supe',
			'⊕':'oplus',
			'⊗':'otimes',
			'⊥':'perp',
			'⋅':'sdot',
			'◊':'loz',
			'♠':'spades',
			'♣':'clubs',
			'♥':'hearts',
			'♦':'diams',

			// Other Special Characters 

			'"':'quot',
		//	'&':'amp',		// This entity is automatically handled by the XHTML parser.
		//	'<':'lt',		// This entity is automatically handled by the XHTML parser.
		//	'>':'gt',		// This entity is automatically handled by the XHTML parser.
			'ˆ':'circ',
			'˜':'tilde',
			' ':'ensp',
			' ':'emsp',
			' ':'thinsp',
			'‌':'zwnj',
			'‍':'zwj',
			'‎':'lrm',
			'‏':'rlm',
			'–':'ndash',
			'—':'mdash',
			'‘':'lsquo',
			'’':'rsquo',
			'‚':'sbquo',
			'“':'ldquo',
			'”':'rdquo',
			'„':'bdquo',
			'†':'dagger',
			'‡':'Dagger',
			'‰':'permil',
			'‹':'lsaquo',
			'›':'rsaquo',
			'€':'euro'
		} ;

		FCKXHtmlEntities.Chars = '' ;

		// Process Base Entities.
		for ( var e in FCKXHtmlEntities.Entities )
			FCKXHtmlEntities.Chars += e ;

		// Include Latin Letters Entities.
		if ( FCKConfig.IncludeLatinEntities )
		{
			var oEntities = {
				'À':'Agrave',
				'Á':'Aacute',
				'Â':'Acirc',
				'Ã':'Atilde',
				'Ä':'Auml',
				'Å':'Aring',
				'Æ':'AElig',
				'Ç':'Ccedil',
				'È':'Egrave',
				'É':'Eacute',
				'Ê':'Ecirc',
				'Ë':'Euml',
				'Ì':'Igrave',
				'Í':'Iacute',
				'Î':'Icirc',
				'Ï':'Iuml',
				'Ð':'ETH',
				'Ñ':'Ntilde',
				'Ò':'Ograve',
				'Ó':'Oacute',
				'Ô':'Ocirc',
				'Õ':'Otilde',
				'Ö':'Ouml',
				'Ø':'Oslash',
				'Ù':'Ugrave',
				'Ú':'Uacute',
				'Û':'Ucirc',
				'Ü':'Uuml',
				'Ý':'Yacute',
				'Þ':'THORN',
				'ß':'szlig',
				'à':'agrave',
				'á':'aacute',
				'â':'acirc',
				'ã':'atilde',
				'ä':'auml',
				'å':'aring',
				'æ':'aelig',
				'ç':'ccedil',
				'è':'egrave',
				'é':'eacute',
				'ê':'ecirc',
				'ë':'euml',
				'ì':'igrave',
				'í':'iacute',
				'î':'icirc',
				'ï':'iuml',
				'ð':'eth',
				'ñ':'ntilde',
				'ò':'ograve',
				'ó':'oacute',
				'ô':'ocirc',
				'õ':'otilde',
				'ö':'ouml',
				'ø':'oslash',
				'ù':'ugrave',
				'ú':'uacute',
				'û':'ucirc',
				'ü':'uuml',
				'ý':'yacute',
				'þ':'thorn',
				'ÿ':'yuml',
				'Œ':'OElig',
				'œ':'oelig',
				'Š':'Scaron',
				'š':'scaron',
				'Ÿ':'Yuml'
			} ; 
			
			for ( var e in oEntities )
			{
				FCKXHtmlEntities.Entities[ e ] = oEntities[ e ] ;
				FCKXHtmlEntities.Chars += e ;
			}
			
			oEntities = null ;
		}

		// Include Greek Letters Entities.
		if ( FCKConfig.IncludeGreekEntities )
		{
			var oEntities = {
				'Α':'Alpha',
				'Β':'Beta',
				'Γ':'Gamma',
				'Δ':'Delta',
				'Ε':'Epsilon',
				'Ζ':'Zeta',
				'Η':'Eta',
				'Θ':'Theta',
				'Ι':'Iota',
				'Κ':'Kappa',
				'Λ':'Lambda',
				'Μ':'Mu',
				'Ν':'Nu',
				'Ξ':'Xi',
				'Ο':'Omicron',
				'Π':'Pi',
				'Ρ':'Rho',
				'Σ':'Sigma',
				'Τ':'Tau',
				'Υ':'Upsilon',
				'Φ':'Phi',
				'Χ':'Chi',
				'Ψ':'Psi',
				'Ω':'Omega',
				'α':'alpha',
				'β':'beta',
				'γ':'gamma',
				'δ':'delta',
				'ε':'epsilon',
				'ζ':'zeta',
				'η':'eta',
				'θ':'theta',
				'ι':'iota',
				'κ':'kappa',
				'λ':'lambda',
				'μ':'mu',
				'ν':'nu',
				'ξ':'xi',
				'ο':'omicron',
				'π':'pi',
				'ρ':'rho',
				'ς':'sigmaf',
				'σ':'sigma',
				'τ':'tau',
				'υ':'upsilon',
				'φ':'phi',
				'χ':'chi',
				'ψ':'psi',
				'ω':'omega'
			} ;

			for ( var e in oEntities )
			{
				FCKXHtmlEntities.Entities[ e ] = oEntities[ e ] ;
				FCKXHtmlEntities.Chars += e ;
			}

			oEntities = null ;
		}

		// Create and Compile the Regex used to separate the entities from the text.
		FCKXHtmlEntities.EntitiesRegex = new RegExp('[' + FCKXHtmlEntities.Chars + ']|[^' + FCKXHtmlEntities.Chars + ']+','g') ;
	//	FCKXHtmlEntities.EntitiesRegex.compile( '[' + FCKXHtmlEntities.Chars + ']|[^' + FCKXHtmlEntities.Chars + ']+', 'g' ) ;
	}
	else
	{
		// Even if we are not processing the entities, we must respect the &nbsp;.
		FCKXHtmlEntities.Entities = { ' ':'nbsp' } ;
		FCKXHtmlEntities.EntitiesRegex = /[ ]|[^ ]+/g ;
	}
}